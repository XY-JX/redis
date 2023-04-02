<?php
// +----------------------------------------------------------------------
// | DATE: 2023/4/1 13:33
// +----------------------------------------------------------------------
// | Author: xy <zhangschooi@qq.com>
// +----------------------------------------------------------------------
// | Notes:  PhpStorm Redis.php
// +----------------------------------------------------------------------
namespace xy_jx\Redis;

use xy_jx\Redis\bin\RedisClient;

class Redis
{
    protected static $_connections = [];
    protected static $config = [];

    public function __construct(array $config = [])
    {
        self::$config = $config;
    }

    /**
     * redis连接组
     * @return mixed
     */
    public static function connection($config = [])
    {
        empty($config) && $config = self::$config;
        $connectName = md5(serialize($config));
        if (!isset(static::$_connections[$connectName]))
            self::$_connections[$connectName] = self::connect($config);

        return static::$_connections[$connectName];
    }


    /**
     * 连接redis
     * @param array $config
     * @return RedisClient
     */
    private static function connect(array $config): RedisClient
    {
        if (!extension_loaded('redis')) {
            throw new \RuntimeException('Please make sure the PHP Redis extension is installed and enabled.');
        }

        $redis = new RedisClient();

        $redis->connectWithConfig($config);
        return $redis;
    }

    public static function __callStatic($name, $arguments)
    {
        return static::connection()->{$name}(... $arguments);
    }

    public function __call($name, $arguments)
    {
        return static::connection()->{$name}(... $arguments);
    }


}