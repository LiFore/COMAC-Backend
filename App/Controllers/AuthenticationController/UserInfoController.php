<?php


namespace App\Controllers\AuthenticationController;

use App\Utils\ResponseUtils;
use PhpBoot\DB\DB;
use PhpBoot\DI\Traits\EnableDIAnnotations;
use phpDocumentor\Reflection\DocBlock\Tags\PropertyRead;

/**
 * Class UserInfoController
 * @package App\Controllers\AuthenticationController
 *
 * @path /user
 *
 * 用户信息控制器
 */
class UserInfoController
{
    use EnableDIAnnotations; //启用通过@inject标记注入依赖

    /**
     * @inject
     * @var DB
     */
    private $db;

    /**
     * 此方法实现了用户信息的获取
     *
     * @hook \App\Hooks\AuthenticationHooks\UserAuth
     *
     * @route GET /info
     */
    public function getUserInfo(){
        $userInfo = (new TokenUtils())->getUUIDByToken($this->db);
        if(!$userInfo) return (new ResponseUtils())->getResponse(false,-4001,"找不到用户");

        unset($userInfo['id']);
        unset($userInfo['password']);
        unset($userInfo['accessToken']);
        unset($userInfo['password']);
        unset($userInfo['headBase64']);
        unset($userInfo['tokenExpire']);

        $userInfo['headUrl'] = HOST."user/head/".$userInfo['uniqueId'];

        return (new ResponseUtils())->getResponse($userInfo);
    }


    /**
     * 此方法实现了用户头像的获取
     *
     * @route GET /head/{userUUID}
     * @return array
     */
    public function getUserHead($userUUID = ''){
        $userInfo = (new TokenUtils())->getInfoByUUID($this->db,$userUUID);
        if(!$userInfo) return (new ResponseUtils())->getResponse(false,-4001,"找不到用户");

        $img = base64_decode($userInfo['headBase64']);
        if($img == false) Header("Location: ".$userInfo['headBase64']);
         //$a = file_put_contents('./test.jpg', $img);//保存图片，返回的是字节数
        //print_r($a);
        Header( "Content-type: image/png");//直接输出显示jpg格式图片
        echo $img;
        exit();
    }
}