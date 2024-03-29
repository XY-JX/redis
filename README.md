<h1 align="center">redis</h1>

[![Latest Stable Version](http://poser.pugx.org/xy_jx/redis/v)](https://packagist.org/packages/xy_jx/redis) 
[![Total Downloads](http://poser.pugx.org/xy_jx/redis/downloads)](https://packagist.org/packages/xy_jx/redis)
[![Latest Unstable Version](http://poser.pugx.org/xy_jx/redis/v/unstable)](https://packagist.org/packages/xy_jx/redis) 
[![License](http://poser.pugx.org/xy_jx/redis/license)](https://packagist.org/packages/xy_jx/redis) 
[![PHP Version Require](http://poser.pugx.org/xy_jx/redis/require/php)](https://packagist.org/packages/xy_jx/redis)

### 第一步：composer安装

```
 composer require xy_jx/redis
```

### 第二步使用：
#### redis基本使用
```
use xy_jx\Redis\Redis;

  //redis 配置
        $config = [
            'host' => '127.0.0.1',
            'port' => 6379,
            'password' => '',
            'select' => 0,
            'timeout' => 0,
            'expire' => 0,
            'prefix' => 'key_da',//前缀
        ];
   $redis = new  Redis($config);
      $redis->set('aaaa',11111);
      echo $redis->get('aaaa').PHP_EOL;//11111
      echo Redis::get('aaaa');//11111

```
#### redis访问限制
```
use xy_jx\Redis\RedisRestrict;

$redis = new RedisRestrict(
    [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => '',
    ]
);
if ($redis::restrict('aaaaaaaaaaaaaaa', 3, 'm')) {
    echo '正常';
} else {
    echo '限流拉';
}
```
#### redis队列
```
<?php

use xy_jx\Redis\RedisQueue;

        //redis 配置
        $config = [
            'host' => '127.0.0.1',
            'port' => 6379,
            'password' => '',
            'select' => 0,
            'timeout' => 0,
            'expire' => 0,
            'prefix' => 'key_da',//前缀
        ];
        
        //可以直接使用静态方法
        $RedisQueue = new  RedisQueue($config);
        //队列名称
        $queue = 'send_user';
        //设置队列名称
        $RedisQueue->setQueue($queue);
        $sendData = ['content' => 'hello', 'db' => 0, 'id' => 1];
        //投递及时消息
        $RedisQueue->send($sendData);
        //投递延迟消息
        $RedisQueue->send($sendData, 600);
        //获取到消息
        if ($message = RedisQueue::consume()) {
            $data = json_decode($message, true);
            //业务代码
            $isSuccess = 1;
            if ($isSuccess) {//业务处理成功
                //处理成功后删除消息
                $RedisQueue->delData($message);
            } else {
                //处理失败后重新投递消息
                $RedisQueue->retryData($message, $data);
            }
        }

```