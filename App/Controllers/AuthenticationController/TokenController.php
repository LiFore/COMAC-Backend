<?php


namespace App\Controllers\AuthenticationController;

use App\Utils\HeaderUtils;
use App\Utils\ResponseUtils;
use App\Utils\TimeUtils;
use PhpBoot\DB\DB;
use PhpBoot\DI\Traits\EnableDIAnnotations;

/**
 * Class TokenController
 * @package App\Controllers\AuthenticationController
 *
 * @path /auth/token/
 *
 * 口令控制器
 */
class TokenController
{
    use EnableDIAnnotations; //启用通过@inject标记注入依赖

    /**
     * @inject
     * @var DB
     */
    private $db;

    /**
     * 此方法实现了用户登入获取令牌
     *
     * @route POST /login
     *
     * @param $username
     * @param $passwd
     * @param $clientToken
     */
    public function login($username, $passwd, $clientToken = null){
        $userResult = $this->db->select('*')
            ->from('users')
            ->where("workId = '" . $username . "'")->get();
        $userResult = $userResult[0];

        // 检查用户是否存在
        if($userResult == null){
            return (new ResponseUtils())->getResponse( '', -4001, '用户名或密码错误' );
        }

        if($userResult['permission'] == 0){
            return (new ResponseUtils())->getResponse( '', -4001, '您的账户未激活 请先激活' );
        }

        // 检查密码是否正确
        if (!AuthenticationUtils::verfiyPassword($passwd,$userResult["password"])) {
            return (new ResponseUtils())->getResponse( '', -4003, '用户名或密码错误' );
        }

        // 检查先前token是否失效
        if($userResult['tokenExpire'] < TimeUtils::getUNIXTime()){
            $this->db->update('users')
                ->set([
                    'accessToken'=> '',
                    'loginAt'=> TimeUtils::getNormalTime(),
                    'clientToken' => ''
                ])
                ->where(['uniqueId'=> $userResult['uniqueId']])
                ->exec();
        }

        // 检查完成 生成新Token
        $newToken = AuthenticationUtils::geneNewAccessToken();
        if(!$clientToken) $clientToken = AuthenticationUtils::geneNewAccessToken();
        $tokenExpire = TimeUtils::getUNIXTime(259200); // tokenExpire 三天

        $this->db->update('users')
            ->set([
                'accessToken'=> $newToken,
                'tokenExpire'=> $tokenExpire,
                'clientToken' => $clientToken,
                'loginAt' => TimeUtils::getNormalTime(),
                "lastIp" => HeaderUtils::getIp()
            ])
            ->where(['uniqueId'=> $userResult['uniqueId']])
            ->exec();

        // 入库成功 返回数据
        return (new ResponseUtils())->getResponse( [
            "username" => $username,
            "accessToken" => $newToken,
            "clientToken" => $clientToken,
            "expireTime" => $tokenExpire,
        ], 200, '' );
    }

    /**
     * 此方法实现了用户令牌刷新
     *
     * @route POST /refresh
     *
     * @param $username
     * @param $accessToken
     * @param $clientTokenOld
     * @param $clientTokenNew
     */
    public function refresh($username,$accessToken,$clientTokenOld,$clientTokenNew = null){
        $userResult = $this->db->select('*')
            ->from('users')
            ->where("clientToken = '" . $clientTokenOld . "'")->get();
        $userResult = $userResult[0];
        if($userResult['accessToken'] != $accessToken){
            return (new ResponseUtils())->getResponse( '', -4003, 'accessToken不匹配' ,$clientTokenOld);
        }
        if($userResult['username'] != $username){
            return (new ResponseUtils())->getResponse( '', -5001, '内部错误 请联系管理员' );
        }
        if($userResult['tokenExpire'] < TimeUtils::getUNIXTime()){
            $this->db->update('users')
                ->set([
                    'accessToken'=> '',
                    'loginAt'=> TimeUtils::getNormalTime(),
                    'clientToken' => ''
                ])
                ->where(['uniqueId'=>$userResult['uniqueId']])
                ->exec();
            return (new ResponseUtils())->getResponse( '', -4001, 'accessToken 已过期' ,$clientTokenOld);
        }
        $newAccessToken = AuthenticationUtils::geneNewAccessToken();
        $expireTime = TimeUtils::getUNIXTime(295200); // token 有效期三天
        $this->db->update('users')
            ->set([
                'accessToken'=> $newAccessToken,
                'tokenExpire'=> $expireTime,
                'clientToken' => $clientTokenNew != null ? $clientTokenNew : $clientTokenOld,
                'loginAt' => TimeUtils::getNormalTime(),
                "lastIp" => HeaderUtils::getIp()
            ])
            ->where(['uniqueId'=>$userResult['uniqueId']])
            ->exec();
        return (new ResponseUtils())->getResponse( [
            "username" => $username,
            "accessToken" => $newAccessToken,
            "clientToken" =>$clientTokenNew != null ? $clientTokenNew : $clientTokenOld,
            "expireTime" => $expireTime,
        ], 200, '' );
    }

    /**
     * 此方法实现了token的登出
     *
     * @route POST /logout
     *
     * @param $username
     * @param $accessToken
     * @param $clientToken
     */
    public function logout($username,$accessToken,$clientToken){
        $userResult = $this->db->select('*')
            ->from('users')
            ->where("clientToken = '" . $clientToken . "'")->get();
        $userResult = $userResult[0];
        if($userResult['accessToken'] != $accessToken){
            return (new ResponseUtils())->getResponse( '', -4003, 'accessToken不匹配' );
        }
        if($userResult['username'] != $username){
            return (new ResponseUtils())->getResponse( '', -5001, '内部错误 请联系管理员' );
        }

        if($userResult['tokenExpire'] < TimeUtils::getUNIXTime()){
            $this->db->update('users')
                ->set([
                    'accessToken'=> '',
                    'loginAt'=> TimeUtils::getNormalTime(),
                    'clientToken' => ''
                ])
                ->where(['uniqueId'=>$userResult['uniqueId']])
                ->exec();
            return (new ResponseUtils())->getResponse( '', -4001, 'accessToken 已过期' );
        }
        $this->db->update('users')
            ->set([
                'accessToken'=> '',
                'loginAt'=> TimeUtils::getNormalTime(),
                'clientToken' => ''
            ])
            ->where(['uniqueId'=>$userResult['uniqueId']])
            ->exec();
        return (new ResponseUtils())->getResponse( true, 200, 'success' );
    }

    /**
     * 此方法检查token是否有效
     *
     * @route POST /vaild
     *
     * @param $username
     * @param $accessToken
     * @param $clientToken
     */
    public function vaild($username,$accessToken,$clientToken){
        $userResult = $this->db->select('*')
            ->from('users')
            ->where("clientToken = '" . $clientToken . "'")->get();
        $userResult = $userResult[0];
        if($userResult['accessToken'] != $accessToken){
            return (new ResponseUtils())->getResponse( false, -4003, 'accessToken不匹配' );
        }
        if($userResult['username'] != $username){
            return (new ResponseUtils())->getResponse( false, -5001, '内部错误 请联系管理员' );
        }
        if($userResult['tokenExpire'] < TimeUtils::getUNIXTime()){
            $this->db->update('users')
                ->set([
                    'accessToken'=> '',
                    'loginAt'=> TimeUtils::getNormalTime(),
                    'clientToken' => ''
                ])
                ->where(['uniqueId'=>$userResult['uniqueId']])
                ->exec();
            return (new ResponseUtils())->getResponse( false, -4001, 'accessToken 已过期' );
        }

        return (new ResponseUtils())->getResponse( true, 200, 'Success' );
    }


}