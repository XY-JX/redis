<?php
// +----------------------------------------------------------------------
// | DATE: 2023/4/1 14:33
// +----------------------------------------------------------------------
// | Author: xy <zhangschooi@qq.com>
// +----------------------------------------------------------------------
// | Notes:  PhpStorm RedisRestrict.php
// +----------------------------------------------------------------------
namespace xy_jx\Redis;
class RedisRestrict extends Redis
{
    private static $duration = [
        's' => 1,
        'm' => 60,
        'h' => 3600,
        'd' => 86400,
    ];

    /**
     * 访问限制
     * @param string $key ip|uid
     * @param int $limit 限制次数
     * @param string $time 时间范围 s m h d
     * @return bool
     */
    public static function restrict(string $key, int $limit = 3, string $time = 's'): bool
    {
        $key = 'throttle_:' . $key;
        if (parent::execCommand('get', $key)) {
            if (parent::execCommand('incr', $key) > $limit) return false; //键值递增,大于限制
        } else {
            parent::execCommand('set', $key, 1, ['nx', 'ex' => self::$duration[$time]]);
        }
        return true;
    }

}