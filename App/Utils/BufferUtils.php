<?php

namespace App\Utils;

class BufferUtils
{
    public static function getBufferFromDB($db,$bufferName,$bufferTime){
        $bufferRet = $db->select('*')->from("buffer")->where("bufferTypeName = '".$bufferName."'")->orderBy("bufferTime","DESC")->getFirst();

        if($bufferRet){
            if(strtotime($bufferRet['bufferTime']) + $bufferTime > time() ){
                //缓存存在且未过期
                $buffer = json_decode($bufferRet['bufferContent'],true);
                return [$buffer,$bufferRet['bufferTime']];
            }
            $db->update("buffer")->set(["isOutDate"=>1])->where(['bufferId' => $bufferRet['bufferId']])->exec();
        }
        return [null,TimeUtils::getNormalTime()];
    }

    public static function setBufferToDB($db,$bufferName,$bufferContent){
        return $db->insertInto("buffer")->values(["bufferTime" => TimeUtils::getNormalTime(),"bufferContent" => json_encode($bufferContent),"isOutDate"=>0,"bufferTypeName"=>$bufferName])->exec();
    }
}