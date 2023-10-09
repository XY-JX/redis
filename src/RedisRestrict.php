<?php
// +----------------------------------------------------------------------
// | DATE: 2023/4/1 14:33
// +----------------------------------------------------------------------
// | Author: xy <zhangschooi@qq.com>
// +----------------------------------------------------------------------
// | Notes: RedisRestrict.php
// +----------------------------------------------------------------------
namespace xy_jx\Redis;
class RedisRestrict extends Redis
{
    public static $duration = [
        's' => 1,
        'm' => 60,
        'h' => 3600,
        'd' => 86400,
        'lock' => 3,
    ];

    public static $restrictKey = 'throttle_restrict:';

    /**
     * 访问限制
     * @param string $key ip|uid
     * @param int $limit 限制次数
     * @param string|int $time 时间范围 s m h d
     * @return bool
     */
    public static function restrict(string $key, int $limit = 3, $time = 's'): bool
    {
        $key = self::$restrictKey . $key;
        //判断是否大于大于限制
        if (($newVal = parent::execCommand('incr', $key)) > $limit) {
            return false; //键值递增,大于限制
        }
        if (1 === $newVal) {  //第一次设置过期时间
            //如果是int就是视为过期时间秒
            $ttl = is_int($time) ? $time : (self::$duration[$time] ?? reset(self::$duration));
            //设置过期时间
            parent::execCommand('expire', $key, $ttl);
        }

        return true;
    }

}