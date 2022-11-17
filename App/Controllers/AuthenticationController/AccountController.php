<?php


namespace App\Controllers\AuthenticationController;

use App\Utils\HeaderUtils;
use App\Utils\ResponseUtils;
use App\Utils\TimeUtils;
use App\Utils\UniqueIDUtils;
use PhpBoot\DB\DB;
use PhpBoot\DI\Traits\EnableDIAnnotations;

/**
 * Class AccountController
 * @package App\Controllers\AuthenticationController
 *
 * @path /auth/account/
 *
 * 用户账户控制器
 */
class AccountController
{

    use EnableDIAnnotations; //启用通过@inject标记注入依赖

    /**
     * @inject
     * @var DB
     */
    private $db;

    /**
     * 此方法实现了新建用户
     *
     * @route POST /admin/create
     *
     * @hook \App\Hooks\AuthenticationHooks\AdminAuth
     *
     * @param $username string 用户名
     * @param $passwd string 密码
     *
     */
    public function createUser($username, $passwd){

        $InsertResult = $this->db->insertInto('users')->values(array(
            "username" => $username,
            "password" => AuthenticationUtils::hashPasswordSHA256WithNewSALT($passwd),
            "accessToken" => AuthenticationUtils::geneNewAccessToken(),
            "registerAt" => TimeUtils::getNormalTime(),
            "loginAt" => TimeUtils::getNormalTime(),
            "headBase64" => AuthenticationUtils::geneNewHead(),
            "lastIp" => HeaderUtils::getIp(),
            "permission" => 0,
            "uniqueId" => UniqueIDUtils::build(),
            "clientToken" => "",
            "accountMoney"=>0,
        ))->exec();
        if($InsertResult){
            return (new ResponseUtils())->getResponse( true , 200 , $username." 已添加" );
        }else{
            return (new ResponseUtils())->getResponse( false , -6001 , 'Server Error' );
        }
    }


}