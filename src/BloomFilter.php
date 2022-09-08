<?php

use Redis;

/**
 * 抽象类
 *
 * 使用bloom过滤器需要集成该类根据自己的需求并重写响应的属性和方法
 */
abstract class BloomFilter
{

    /**
     * 批量添加的数量限制
     * 通过反复测试 2000一批 无论是速度还是占用redis时间都是最佳的
     *
     * @var int
     */
    protected int $limit = 2000;

    /**
     * 使用的hash函数名称
     *
     * @var string[]
     */
    protected array $func = ['Fnv164Hash', 'Md5Hash', 'RipeMd160Hash'];

    /**
     * @var BloomFilterHash
     */
    protected BloomFilterHash $hash;


    /**
     * 要添加到布隆过略器中的元素个数
     *
     * @var float
     */
    protected float $members = 1e7; // 默认10000000

    /**
     * 能接受的误判率
     *
     * @var float
     */
    protected float $fpp = 0.0001;

    /**
     * 保存数据的bit分配大小默认1m
     *
     * @var int
     */
    protected int $bitSize = 1 << 23;


    /**
     * redis连接地址
     *
     * @var string
     */
    protected string $host = 'localhost';
    /**
     * 端口
     *
     * @var int
     */
    protected int $port = 6379;

    /**
     * redis连接密码
     *
     * @var string
     */
    protected string $password = '';

    /**
     * redis使用的db
     *
     * @var int
     */
    protected int $db = 0;

    /**
     * redis连接
     *
     */
    private ?\Redis $redis = null;

    /**
     * @param array $config
     *
     * @throws Exception
     */
    public function __construct(array $config = [])
    {
        foreach ($config as $k => $v) {
            if (property_exists($this, $k)) {
                $this->$k = $v;
            }
        }
        if (empty($this->getBucket())) {
            throw new Exception('请设置bucket值');
        }
        if (!class_exists('Redis')) {
            throw new Exception('redis扩展不存在');
        }
        $this->redis = new Redis();
        $this->redis->connect($this->host, $this->port);
        if ($this->password !== null) {
            $this->redis->auth($this->password);
        }
        $this->redis->select($this->db);
        $this->hash = new BloomFilterHash(['bitSize' => $this->bitSize]);
    }

    /**
     * 设置
     *
     * @return string
     */
    abstract protected function getBucket(): string;

    /**
     * 批量添加数据到redis
     *
     * @param ...$strings
     *
     * @throws Exception
     */
    public function add(...$strings): bool
    {
        if (count($strings) > $this->limit) {
            throw new Exception('参数错误');
        }
        $offsets = [];
        foreach ($this->func as $func) {
            foreach ($strings as $string) {
                $hash           = $this->hash->$func($string, strlen($string));
                $offsets[$hash] = $hash;
            }
        }
        $lua = <<<script
            for key, value in ipairs(KEYS)
            do
               redis.call('setBit',  '{$this->getBucket()}', KEYS[key], 1)
            end
            return true
        script;
        return $this->redis->eval($lua, $offsets, count($offsets));
    }

    /**
     * 判断bloom过滤器中是否有该值 true有 false无
     *
     * @param string $string
     *
     * @return bool
     */
    public function has(string $string): bool
    {
        $len     = strlen($string);
        $offsets = [];
        foreach ($this->func as $func) {
            $hash           = $this->hash->$func($string, $len);
            $offsets[$hash] = $hash;
        }
        $lua = <<<script
            for key, value in ipairs(KEYS)
            do
               local ret = redis.call('getBit',  '{$this->getBucket()}', KEYS[key])
               if (ret == 0)
               then
                    return 0
               end
            end
            return 1
        script;
        return boolval($this->redis->eval($lua, $offsets, count($offsets)));
    }

    /**
     * 查询是否存在 如果存在返回true 如果不存在返回false 并添加到过滤
     *
     * @param string $string
     *
     * @return bool
     */
    public function hasAdd(string $string): bool
    {
        $len     = strlen($string);
        $offsets = [];
        foreach ($this->func as $func) {
            $hash           = $this->hash->$func($string, $len);
            $offsets[$hash] = $hash;
        }
        $lua = <<<script
            local result = 1
            for key, value in ipairs(KEYS)
            do
               local ret = redis.call('getBit',  '{$this->getBucket()}', KEYS[key])
               if (ret == 0)
               then
                    redis.call('setBit', '{$this->getBucket()}', KEYS[key])
                    result = 0
               end
            end
            return result
        script;
        return boolval($this->redis->eval($lua, $offsets, count($offsets)));

    }

    /**
     * 计算获取需要存储数据使用的空间大小
     *
     * @param false $human
     *
     * @return float|int|string
     */
    public function bitSize(bool $human = false)
    {
        // 通过存储数据和误判率获取
        // 获取bit大小
        $m = -($this->members * log($this->fpp, M_E)) / pow(log(2, M_E), 2);
        if ($human) {
            return bcdiv($m, 1 << 23, 2) . 'M';
        }
        return $m;
    }

    /**
     * 获取要使用hash的个数
     *
     * @return false|float
     */
    public function hashFuncCount()
    {
        $m = $this->bitSize();
        $k = ($m / $this->members) * log(2, M_E);
        return floor($k);
    }
}