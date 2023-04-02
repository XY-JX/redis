<?php
// +----------------------------------------------------------------------
// | DATE: 2023/4/1 13:30
// +----------------------------------------------------------------------
// | Author: xy <zhangschooi@qq.com>
// +----------------------------------------------------------------------
// | Notes:  PhpStorm RedisClient.php
// +----------------------------------------------------------------------
namespace xy_jx\Redis\bin;
class RedisClient extends \Redis
{
    /**
     * @var array
     */
    protected $config = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => '',
        'select' => 0,
        'timeout' => 0,
        'expire' => 0,
        'persistent' => false,//是否开启长链接
        'prefix' => '',//前缀
        'tag_prefix' => 'tag:',//标签需要自己处理
        'serializer' => self::SERIALIZER_NONE, //redis的序列化
        'serialize' => [],//序列化
    ];

    /**
     * @param array $config
     */
    public function connectWithConfig(array $config = [])
    {
        if (!empty($config)) { // 加载配置参数，替换默认参数
            $this->config = array_merge($this->config, $config);
        }
        if ($this->config['persistent']) {
            if (false === $this->pconnect($this->config['host'], $this->config['port'], $this->config['timeout'], $this->config['host'] . ':' . $this->config['port'] . ':' . $this->config['select'])) {
                throw new \RuntimeException("Redis connect {$this->config['host']}:{$this->config['port']} fail.");
            }
        } else {
            if (false === $this->connect($this->config['host'], $this->config['port'], $this->config['timeout'])) {
                throw new \RuntimeException("Redis connect {$this->config['host']}:{$this->config['port']} fail.");
            }
        }

        if (!empty($this->config['password'])) {
            $this->auth($this->config['password']);
        }
        if (!empty($this->config['select'])) {
            $this->select($this->config['select']);
        }
        //设置前缀
        if (!empty($this->config['prefix'])) {
            $this->setOption(self::OPT_PREFIX, $this->config['prefix']);
        }
        //设置序列化程序
        if (!empty($this->config['serialize'])) {
            $this->setOption(self::OPT_SERIALIZER, $this->config['serialize']);
        }
    }


    /**
     * @param $command
     * @param ...$args
     * @return mixed
     */
    protected function execCommand($command, ...$args)
    {
        try {
            if (method_exists($this, $command)) {
                return $this->{$command}(...$args);
            } else {
                throw new \RuntimeException($command . ' method does not exist');
            }
        } catch (\RuntimeException $e) {
            $msg = strtolower($e->getMessage());
            if ($msg === 'connection lost' || strpos($msg, 'went away')) {
                $this->connectWithConfig();
                return $this->{$command}(...$args);
            }
            throw $e;
        }
    }


    /**
     * 序列化数据
     * @access protected
     * @param mixed $data 缓存数据
     * @return string
     */
    public function serialize($data): string
    {
        if (is_numeric($data)) {
            return (string)$data;
        }

        $serialize = $this->config['serialize'][0] ?? "serialize";

        return $serialize($data);
    }

    /**
     * 反序列化数据
     * @access protected
     * @param string $data 缓存数据
     * @return float|int|string
     */
    public function unserialize(string $data)
    {
        if (is_numeric($data)) {
            return $data;
        }

        $unserialize = $this->config['serialize'][1] ?? "unserialize";

        return $unserialize($data);
    }

    /**
     * 获取tag的所有值
     * @param $tag
     * @return mixed
     */
    public function getTag($tag)
    {
        return $this->execCommand('smembers', $this->getTagKey($tag));
    }

    /** 给key设置tag
     * @param $tag
     * @param $key
     * @return mixed
     */
    public function setTag($tag, $key)
    {
        return $this->execCommand('sadd', $this->getTagKey($tag), $key);
    }

    /**
     * 删除tag下所有的key慎用
     * @param $tag
     * @return mixed
     */
    public function delTag($tag)
    {
        return $this->execCommand('del', $this->getTagKey($tag));
    }

    public function getTagKey($tag): string
    {
        if (!empty($this->config['tag_prefix'])) {
            $tag = $this->config['tag_prefix'] . $tag;
        }
        return md5($tag);
    }

    /**
     * @param $command
     * @param $args
     * @return mixed
     */
    public function __call($command, $args)
    {
        return $this->execCommand($command, ...$args);
    }


}