<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use xu\Bloom\BloomFilterHash;

final class HashTest extends TestCase
{

    public function testHashFunc()
    {
        $hash       = new BloomFilterHash(['bitSize' => 1 << 23]);
        $testString = 'Hello World!';
        $funcs      = [
            'JSHash'   => 7890106,
            'PJWHash'  => 696689,
            'ELEHash'  => 4899185,
            'BKDRHash' => 4837970,
            'SDBMHash' => 8201036,
            'DJBHash'  => 2383482,
            'DEKHash'  => 7520290,
            'FNVHash'  => 6738950,
            'Crc32'    => 2694307,

        ];
        foreach ($funcs as $func => $v) {
            $h = $hash->$func($testString);
            $this->assertEquals($v, $h);
        }
    }

    //public function testHashFuncSpeed()
    //{
    //    $hash  = new BloomFilterHash();
    //    $funcs = [
    //        'Crc32',
    //        'JSHash',
    //        'DEKHash',
    //        'FNVHash',
    //        'PJWHash',
    //        'DJBHash',
    //        'SDBMHash',
    //        'BKDRHash',
    //        'ELEHash',
    //    ];
    //    $speed = [];
    //    foreach ($funcs as $func) {
    //        $t1 = microtime(true);
    //        for ($i = 0; $i < 1000; $i++) {
    //            $str = $func . microtime(true);
    //            $hash->$func($str);
    //        }
    //        $t2           = microtime(true);
    //        $speed[$func] = $t2 - $t1;
    //    }
    //    asort($speed);
    //    echo "\n--------------\n";
    //    echo "测试速度显示:\n";
    //    print_r($speed);
    //    echo "--------------\n";
    //}

}
