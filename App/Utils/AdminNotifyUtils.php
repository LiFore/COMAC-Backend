<?php


namespace App\Utils;


class AdminNotifyUtils
{
    private static $adminAPIName = ['x2uKV2okLyfnzvr5PCAWoQ'];
    public static function sendAdminNotify($title,$content){
        foreach (AdminNotifyUtils::$adminAPIName as $val){
            file_get_contents("https://api.day.app/".$val."/GMSH+".$title."/".$content);
        }

    }
}