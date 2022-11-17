<?php


namespace App\Utils;

/**
 * Class TimeUtils
 * @package App\Utils
 *
 * 时间工具
 */
class TimeUtils
{
    /**
     * 此方法实现了获取Unix时间戳
     *
     * @param $addon int 增加的时间
     *
     * @return string unix时间戳
     */
    public static function getUNIXTime($addon = 0)
    {
        return strtotime(date("Y-m-d H:i:s")) + $addon;
    }

    /**
     * 此方法实现了获取当前时间
     *
     * @param $addon int 增加的时间
     *
     * @return string unix时间戳
     */
    public static function getNormalTime($addon = 0)
    {
        return date("Y-m-d H:i:s", self::getUNIXTime($addon));
    }

    /**
     * 此方法实现了获取当前时间
     *
     * @param $addon int 增加的时间
     *
     * @return string unix时间戳
     */
    public static function getWXTime($addon = 0)
    {
        return date("Y年m月d日 H:i", self::getUNIXTime($addon));
    }

    /**
     * 此方法实现了获取距今时间
     *
     * @param $time
     * @return string|string[]
     */
    public static function getTillToday($time)
    {
        $now_time = date("Y-m-d H:i:s", time());
        $now_time = strtotime($now_time);
        $show_time = strtotime($time);
        $dur = $now_time - $show_time;
        if ($dur < 0) {
            return $time;
        } else {
            if ($dur < 60) {
                return $dur . '秒前';
            } else {
                if ($dur < 3600) {
                    return floor($dur / 60) . '分钟前';
                } else {
                    if ($dur < 86400) {
                        return floor($dur / 3600) . '小时前';
                    } else {
                        if ($dur < 259200) {//3天内
                            return floor($dur / 86400) . '天前';
                        } else {
                            return $time;
                        }
                    }
                }
            }
        }
    }

    public static function getBirthdayAge($birthday)
    {
        $age = strtotime($birthday);
        if ($age === false) {
            return false;
        }
        list($y1, $m1, $d1) = explode("-", date("Y-m-d", $age));
        $now = strtotime("now");
        list($y2, $m2, $d2) = explode("-", date("Y-m-d", $now));
        $age = $y2 - $y1;
        if ((int)($m2 . $d2) < (int)($m1 . $d1))
            $age -= 1;
        return $age;
    }
}