<?php
// +----------------------------------------------------------------------
// | DATE: 2023/4/1 14:33
// +----------------------------------------------------------------------
// | Author: xy <zhangschooi@qq.com>
// +----------------------------------------------------------------------
// | Notes: RedisQueue.php
// +----------------------------------------------------------------------
namespace xy_jx\Redis;
class RedisQueue extends Redis
{
    /**
     * 及时消费队列
     */
    const QUEUE_WAITING = '{queue-waiting}:';

    /**
     * 延迟队列
     */
    const QUEUE_DELAY = '{queue-delayed}:';
    /**
     * 在消费的队列
     */
    const QUEUE_CONSUME = '{queue-consume}:';

    /**
     * 队列名称
     * @var string
     */
    private static $queue = 'queue';
    /**
     * 最大重次数
     * @var int
     */
    private static $maxRetry = 3;

    public function __construct(array $config = [], string $queue = null)
    {
        parent::__construct($config);
        if (!empty($queue)) {
            self::$queue = $queue;
        }
    }

    /**
     * 设置队列名称
     * @param string|null $queue
     * @return bool
     */
    public static function setQueue(string $queue = null): bool
    {
        if (!empty($queue)) {
            self::$queue = $queue;
            return true;
        }
        return false;
    }

    /**
     * 设置队列最大重试次数
     * @param int $maxRetry
     * @return bool
     */
    public static function setMaxRetry(int $maxRetry = 0): bool
    {
        if (!empty($maxRetry)) {
            self::$maxRetry = $maxRetry;
            return true;
        }
        return false;
    }

    /**
     * 发送消息
     * @param array $data
     * @param int $delay
     * @param null $queue
     * @return mixed
     */

    public static function send(array $data, int $delay = 0, $queue = null)
    {
        empty($queue) && $queue = self::$queue;
        $time = time();
        $packageStr = json_encode([
            'id' => $time . rand(),
            'time' => $time,
            'delay' => $delay,
            'try' => 0,
            'queue' => self::$queue,
            'data' => $data
        ]);
        if ($delay) return parent::execCommand('zAdd', self::QUEUE_DELAY . $queue, $time + $delay, $packageStr);

        return parent::execCommand('lPush', self::QUEUE_WAITING . $queue, $packageStr);
    }

    /**
     * 获取一条消费消息
     * @param string|null $queue
     * @param int $timeout
     * @return string
     */
    public static function consume(string $queue = null, int $timeout = 1): string
    {
        empty($queue) && $queue = self::$queue;
        if ($queueDelayList = parent::execCommand('zRangeByScore', self::QUEUE_DELAY . $queue, 0, time(), ['limit' => [0, 500]])) {
            foreach ($queueDelayList as $packageStr) {
                parent::execCommand('multi')->execCommand('lPush', self::QUEUE_WAITING . $queue, $packageStr)->execCommand('zRem', self::QUEUE_DELAY . $queue, $packageStr)->execCommand('exec');
            }
        }
        return parent::execCommand('brpoplpush', self::QUEUE_WAITING . $queue, self::QUEUE_CONSUME . $queue, $timeout);
    }

    /**
     * 失败重试
     * @param string $message
     * @param array $data
     * @param null $queue
     * @return mixed
     */
    public static function retryData(string $message, array $data = [], $queue = null)
    {
        empty($queue) && $queue = self::$queue;
        empty($data) && $data = json_decode($message, true);
        if (($data['try'] += 1) > self::$maxRetry) {
            return self::delData($message, $queue);
        } else {
            $delay = $data['try'] * 15;
            return parent::execCommand('multi')
                ->execCommand('zAdd', self::QUEUE_DELAY . $queue, time() + $delay, json_encode(array_merge($data, ['try' => $data['try']])))
                ->execCommand('lRem', self::QUEUE_CONSUME . $queue, $message, 0)
                ->execCommand('exec')
                ? 1 : false;
        }
    }

    /**
     * 消费成功删除消息
     * @param string $message
     * @param $queue
     * @return mixed
     */
    public static function delData(string $message, $queue = null)
    {
        empty($queue) && $queue = self::$queue;
        return parent::execCommand('lRem', self::QUEUE_CONSUME . $queue, $message, 0);
    }

    /**
     * 获取队列的长度
     * @param null $queue
     * @return mixed
     */
    public static function lLen($queue = null)
    {
        empty($queue) && $queue = self::$queue;
        return parent::execCommand('lLen', self::QUEUE_WAITING . $queue) + parent::execCommand('zCard', self::QUEUE_DELAY . $queue);
    }
}