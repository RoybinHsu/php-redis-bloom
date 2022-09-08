<?php

namespace xushunbin\Bloom;

/**
 * 布隆过滤器hash
 *
 * 执行10w次hash所消耗的时间
 * [
 * 'Fnv164Hash' => '0.3583000'
 * 'Md5Hash' => '0.4924000'
 * 'RipeMd160Hash' => '0.5923000'
 * 'Sha256Hash' => '0.8314000'
 * 'Haval2563Hash' => '0.8340000'
 * 'GostHash' => '0.8962000'
 * 'SneFruHash' => '0.9837000'
 * 'DEKHash' => '1.0073000'
 * 'JSHash' => '1.0186000'
 * 'FNVHash' => '1.0412000'
 * 'DJBHash' => '1.0417000'
 * 'SDBMHash' => '1.0581000'
 * 'PJWHash' => '1.0615000'
 * 'BKDRHash' => '1.0859000'
 * 'ELEHash' => '1.2474000'
 * 'WhirlpoolHash' => '1.5332000'
 * ]
 *
 *
 * @property $bitSize
 */
class BloomFilterHash
{

    /**
     * 初始化
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        foreach ($config as $k => $v) {
            if (property_exists($this, $k)) {
                $this->$k = $v;
            }
        }
    }

    /**
     * @var int
     */
    public int $bitSize = 0xFFFFFFFF;

    /**
     * Justin Sobel 编写的按位散列函数.
     *
     * @param string $string
     * @param null $len
     *
     * @return int
     */
    public function JSHash(string $string, $len = null): int
    {
        $hash = 1315423911;
        $len || $len = strlen($string);
        for ($i = 0; $i < $len; $i++) {
            $hash ^= (($hash << 5) + ord($string[$i]) + ($hash >> 2));
        }
        return (($hash % 0xFFFFFFFF) & 0xFFFFFFFF) % $this->bitSize;
    }

    /**
     * 该哈希算法基于AT＆T贝尔实验室的Peter J. Weinberger的工作。
     * Aho Sethi和Ulman编写的“编译器（原理，技术和工具）”一书建议使用采用此特定算法中的散列方法的散列函数。
     *
     * @param string $string
     * @param null $len
     *
     * @return int
     */
    public function PJWHash(string $string, $len = null): int
    {
        $bitsInUnsignedInt = 4 * 8;
        $threeQuarters     = ($bitsInUnsignedInt * 3) / 4;
        $oneEighth         = $bitsInUnsignedInt / 8;
        $highBits          = 0xFFFFFFFF << (int)($bitsInUnsignedInt - $oneEighth);
        $hash              = 0;
        $len || $len = strlen($string);
        for ($i = 0; $i < $len; $i++) {
            $hash = ($hash << (int)($oneEighth)) + ord($string[$i]);
        }
        $test = $hash & $highBits;
        if ($test != 0) {
            $hash = (($hash ^ ($test >> (int)($threeQuarters))) & (~$highBits));
        }
        return (($hash % 0xFFFFFFFF) & 0xFFFFFFFF) % $this->bitSize;
    }

    /**
     * 类似PJW Hash功能，但是针对32位处理器做了调整。它是基于unix系统上的widely使用哈希函数。
     *
     * @param string $string
     * @param null $len
     *
     * @return int
     */
    public function ELEHash(string $string, $len = null): int
    {
        $hash = 0;
        $len || $len = strlen($string);
        for ($i = 0; $i < $len; $i++) {
            $hash = ($hash << 4) + ord($string[$i]);
            $x    = $hash & 0xF0000000;
            if ($x != 0) {
                $hash ^= ($x >> 24);
            }
            $hash &= ~$x;
        }
        return (($hash % 0xFFFFFFFF) & 0xFFFFFFFF) % $this->bitSize;
    }

    /**
     * 这个哈希函数来自Brian Kernighan和Dennis Ritchie的书“The C Programming Language”。
     * 它是一个简单的哈希函数，使用一组奇怪的可能种子，它们都构成了31 .... 31 ... 31等模式，它似乎与DJB哈希函数非常相似。
     */
    public function BKDRHash($string, $len = null): int
    {
        $seed = 131;  # 31 131 1313 13131 131313 etc..
        $hash = 0;
        $len || $len = strlen($string);
        for ($i = 0; $i < $len; $i++) {
            $hash = (int)(($hash * $seed) + ord($string[$i]));
        }
        return (($hash % 0xFFFFFFFF) & 0xFFFFFFFF) % $this->bitSize;
    }

    /**
     * 这是在开源SDBM项目中使用的首选算法。
     * 哈希函数似乎对许多不同的数据集具有良好的总体分布。它似乎适用于数据集中元素的MSB存在高差异的情况。
     */
    public function SDBMHash(string $string, $len = null): int
    {
        $hash = 0;
        $len || $len = strlen($string);
        for ($i = 0; $i < $len; $i++) {
            $hash = (int)(ord($string[$i]) + ($hash << 6) + ($hash << 16) - $hash);
        }
        return (($hash % 0xFFFFFFFF) & 0xFFFFFFFF) % $this->bitSize;
    }

    /**
     * 由Daniel J. Bernstein教授制作的算法，首先在usenet新闻组comp.lang.c上向世界展示。
     * 它是有史以来发布的最有效的哈希函数之一。
     */
    public function DJBHash(string $string, $len = null): int
    {
        $hash = 5381;
        $len || $len = strlen($string);
        for ($i = 0; $i < $len; $i++) {
            $hash = (int)(($hash << 5) + $hash) + ord($string[$i]);
        }
        return (($hash % 0xFFFFFFFF) & 0xFFFFFFFF) % $this->bitSize;
    }

    /**
     * Donald E. Knuth在“计算机编程艺术第3卷”中提出的算法，主题是排序和搜索第6.4章。
     */
    public function DEKHash(string $string, $len = null): int
    {
        $len || $len = strlen($string);
        $hash = $len;
        for ($i = 0; $i < $len; $i++) {
            $hash = (($hash << 5) ^ ($hash >> 27)) ^ ord($string[$i]);
        }
        return (($hash % 0xFFFFFFFF) & 0xFFFFFFFF) % $this->bitSize;
    }

    /**
     * 参考 http://www.isthe.com/chongo/tech/comp/fnv/
     */
    public function FNVHash(string $string, $len = null): int
    {
        $prime = 16777619;   //32位的prime 2^24 + 2^8 + 0x93 = 16777619
        $hash  = 2166136261; //32位的offset
        $len || $len = strlen($string);
        for ($i = 0; $i < $len; $i++) {
            $hash = (int)($hash * $prime) % 0xFFFFFFFF;
            $hash ^= ord($string[$i]);
        }
        return (($hash % 0xFFFFFFFF) & 0xFFFFFFFF) % $this->bitSize;
    }

    /**
     * @param string $string
     * @param string $name
     *
     * @return int
     */
    private function _hash(string $string, string $name): int
    {
        $hash = hexdec(hash($name, $string));
        $hash = number_format($hash, 0, '', '');
        return (($hash % 0xFFFFFFFF) & 0xFFFFFFFF) % $this->bitSize;
    }

    /**
     * md5变型的hash
     *
     * @param string $string
     * @param $len
     *
     * @return int
     */
    public function Md5Hash(string $string, $len = null): int
    {
        return $this->_hash($string, 'md5');
    }


    /**
     * @param string $string
     * @param $len
     *
     * @return int
     */
    public function Sha256Hash(string $string, $len = null): int
    {
        return $this->_hash($string, 'sha256');
    }


    /**
     * ripemd160 hash算法
     *
     * @param string $string
     * @param null $len
     *
     * @return int
     */
    public function RipeMd160Hash(string $string, $len = null): int
    {
        return $this->_hash($string, 'ripemd160');
    }

    /**
     * @param string $string
     * @param null $len
     *
     * @return int
     */
    public function GostHash(string $string, $len = null): int
    {
        return $this->_hash($string, 'gost');
    }

    /**
     * @param string $string
     * @param null $len
     *
     * @return int
     */
    public function WhirlpoolHash(string $string, $len = null): int
    {
        return $this->_hash($string, 'whirlpool');
    }

    /**
     * @param string $string
     * @param null $len
     *
     * @return int
     */
    public function SneFruHash(string $string, $len = null): int
    {
        return $this->_hash($string, 'snefru');
    }

    /**
     * @param string $string
     * @param null $len
     *
     * @return int
     */
    public function Haval2563Hash(string $string, $len = null): int
    {
        return $this->_hash($string, 'haval256,3');
    }

    public function Fnv164Hash(string $string, $len = null): int
    {
        return $this->_hash($string, 'fnv164');

    }


}

