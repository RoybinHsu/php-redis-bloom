# php-redis-bloom
通过php redis实现bloom过滤器


### Install
```
composer require xushunbin/php-redis-bloom 1.0
```



### Example 基类的属性可以被覆盖
```
use 

class TestBloomFilter extends BloomFilter
{

    /**
     * redis连接
     *
     * @var string
     */
    public string $redis = 'redis://:123456@127.0.0.1:6379/0';

    /**
     * 获取存储bloom的键
     *
     * @return string
     */
    protected function getBucket(): string
    {
        return 'BLOOM:TEST';
    }

}
```

```
$bloom  = new TestBloomFilter();
$phones = ['138000000000', '138000000001', '138000000002'];
try {
    $bloom->add(...$phones);
} catch (Exception $e) {
    var_dump($e->getMessage());
}
$ret1 = $bloom->has('138000000001');
var_dump($ret1); // true
$ret2 = $bloom->has('138000000009');
var_dump($ret2); // false

```
