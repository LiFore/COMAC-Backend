<?php


namespace App\Utils;

/**
 * Class HeaderUtils
 * @package App\Utils
 *
 * 请求头工具
 */
class HeaderUtils
{

    /**
     * 此方法实现了对请求头的登入信息的解析
     *
     * @return array
     */
    public static function getTokens($authInfo)
    {
        $authInfo = explode(' ', $authInfo);
        $authInfo = explode(':', base64_decode($authInfo[1]));

        return ["clientToken" => $authInfo[0], "accessToken" => $authInfo[1]];
    }


    /**
     * 此方法实现了对于IP的查找
     * @return mixed|string
     */
    public static function getIp()
    {
        if ($_SERVER["HTTP_CLIENT_IP"] && strcasecmp($_SERVER["HTTP_CLIENT_IP"], "unknown")) {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        } else {
            if ($_SERVER["HTTP_X_FORWARDED_FOR"] && strcasecmp($_SERVER["HTTP_X_FORWARDED_FOR"], "unknown")) {
                $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
            } else {
                if ($_SERVER["REMOTE_ADDR"] && strcasecmp($_SERVER["REMOTE_ADDR"], "unknown")) {
                    $ip = $_SERVER["REMOTE_ADDR"];
                } else {
                    if (isset ($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'],
                            "unknown")
                    ) {
                        $ip = $_SERVER['REMOTE_ADDR'];
                    } else {
                        $ip = "unknown";
                    }
                }
            }
        }
        return ($ip);
    }

}