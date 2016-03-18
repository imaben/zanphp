<?php
/**
 * Created by PhpStorm.
 * User: liuxinlong
 * Date: 16/3/9
 * Time: 14:11
 */

namespace Zan\Framework\Network\Common;



class RedisManager {

    private static $redis = null;

    public function __construct($serverIp='localhost', $port=6379) {
        self::$redis = new RedisClient($serverIp, $port);
    }

    public function get($key) {
        $result = new RedisResult();
        self::$redis->get($key, [$result, 'response']);

        yield $result;
    }

    public function set($key, $value) {
        $result = new RedisResult();
        self::$redis->set($key, $value, [$result, 'response']);

        yield $result;
    }



}