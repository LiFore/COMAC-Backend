<?php


namespace App\Controllers\AuthenticationController;

use App\Utils\HeaderUtils;
use PhpBoot\DB\DB;
use PhpBoot\DI\Traits\EnableDIAnnotations;

/**
 * Class TokenUtils
 * @package App\Controllers\AuthenticationController
 *
 * 注册工具控制器
 */
class TokenUtils
{
    /**
     * 此方法实现了由token获取用户uuid
     *
     * @return bool
     */
    public function getUUIDByToken($db,$process=false){
        $authInfo = (new HeaderUtils())::getTokens($_SERVER['HTTP_AUTHORIZATION']);

        $userResult = $db->select('*')
            ->from('users')
            ->where(["accessToken" => $authInfo['accessToken'] , "clientToken" => $authInfo['clientToken'] ])->getFirst();
        if($userResult == null){
            return false;
        }else{
            if($process){
                unset($userResult['id']);
                unset($userResult['password']);
                unset($userResult['accessToken']);
                unset($userResult['password']);
                unset($userResult['headBase64']);
                unset($userResult['tokenExpire']);

                $userResult['headUrl'] = HOST."user/head/".$userResult['uniqueId'];
            }
            if($userResult['workId'] == 101001) $userResult['workId'] = 120211;
            return $userResult;
        }
    }

    /**
     * 此方法实现了由token获取用户uuid
     *
     * @return bool
     */
    public function getInfoByUUID($db,$UUID,$process=false){

        $userResult = $db->select('*')
            ->from('users')
            ->where(["uniqueId" => $UUID ])->getFirst();
        if($userResult == null){
            return false;
        }else{
            if($process){
                unset($userResult['id']);
                unset($userResult['password']);
                unset($userResult['accessToken']);
                unset($userResult['password']);
                unset($userResult['headBase64']);
                unset($userResult['tokenExpire']);

                $userResult['headUrl'] = HOST."user/head/".$userResult['uniqueId'];
            }
            if($userResult['workId'] == 101001) $userResult['workId'] = 120211;
            return $userResult;
        }
    }

    /**
     * 此方法实现了由token获取用户uuid
     *
     * @return bool
     */
    public function getInfoByWorkId($db,$workId,$process=false){

        $userResult = $db->select('*')
            ->from('users')
            ->where(["workId" => $workId ])->getFirst();
        if($userResult == null){
            return false;
        }else{
            if($process){
                unset($userResult['id']);
                unset($userResult['password']);
                unset($userResult['accessToken']);
                unset($userResult['password']);
                unset($userResult['headBase64']);
                unset($userResult['tokenExpire']);

                $userResult['headUrl'] = HOST."user/head/".$userResult['uniqueId'];
            }
            if($userResult['workId'] == 101001) $userResult['workId'] = 120211;
            return $userResult;
        }
    }
}