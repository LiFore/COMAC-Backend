<?php


namespace App\Controllers\UserController;

use App\Controllers\AuthenticationController\AuthenticationUtils;
use App\Controllers\PxSamcController\SamcUtils;
use App\Utils\HeaderUtils;
use App\Utils\ResponseUtils;
use App\Utils\TimeUtils;
use App\Utils\UniqueIDUtils;
use PhpBoot\DB\DB;
use PhpBoot\DI\Traits\EnableDIAnnotations;

/**
 * Class UserController
 * @package App\Controllers\UserController
 *
 * @path /users/
 *
 * 用户控制器
 */
class UserController
{
    use EnableDIAnnotations;

    //启用通过@inject标记注入依赖

    /**
     * @inject
     * @var DB
     */
    private $db;

    /**
     * 此方法实现了获取用户列表
     *
     * @route POST /samc/create
     *
     * @param $userWorkId
     * @param $userRealName
     * @param $userPhone
     * @param $passwd
     */
    public function createUserFromSAMC($userWorkId, $userRealName, $userPhone, $passwd)
    {
        $userResult = $this->db->select('*')->from("users")->where(['workId' => $userWorkId])->getFirst();
        if($userResult['permission'] != 0 ){
            return (new ResponseUtils())->getResponse(false, 403, 'No Permission');
        }
        $accessToken = AuthenticationUtils::geneNewAccessToken();
        $clientToken = AuthenticationUtils::geneNewAccessToken();
        if($userResult){
            $sqlResult = $this->db->update('users')->set(array(
                "username" => $userRealName,
                "workId" => $userWorkId,
                "cellPhone" => $userPhone,
                "realName" => $userRealName,
                "password" => AuthenticationUtils::hashPasswordSHA256WithNewSALT($passwd),
                "accessToken" => $accessToken,
                "registerAt" => TimeUtils::getNormalTime(),
                "loginAt" => TimeUtils::getNormalTime(),
                "headBase64" => AuthenticationUtils::geneNewHead(),
                "lastIp" => HeaderUtils::getIp(),
                "permission" =>  1,
                "uniqueId" => UniqueIDUtils::build(),
                "clientToken" => $clientToken,

            ))->where(["id" => $userResult['id']])->exec();
            if ($sqlResult) {
                return (new ResponseUtils())->getResponse(true, 200, ['clientToken' => $clientToken,'accessToken' => $accessToken] );
            } else {
                return (new ResponseUtils())->getResponse(false, -6001, 'Server Error');
            }
        }
        $samcReturn = SamcUtils::sendRequest('home', 'User', 'user_info', $userWorkId);
        if (!$samcReturn) {
            return (new ResponseUtils())->getResponse('', -4003, '未查询到匹配数据');
        }
        if ($samcReturn['de_user_mobile'] != $userPhone || $samcReturn['de_user_realname'] != $userRealName) {
            return (new ResponseUtils())->getResponse('', -4004, '未查询到匹配数据');
        }

        $sqlResult = $this->db->insertInto('users')->values(array(
            "username" => $userRealName,
            "workId" => $userWorkId,
            "cellPhone" => $userPhone,
            "realName" => $userRealName,
            "password" => AuthenticationUtils::hashPasswordSHA256WithNewSALT($passwd),
            "accessToken" => $accessToken,
            "registerAt" => TimeUtils::getNormalTime(),
            "loginAt" => TimeUtils::getNormalTime(),
            "headBase64" => AuthenticationUtils::geneNewHead(),
            "lastIp" => HeaderUtils::getIp(),
            "permission" => 1,
            "uniqueId" => UniqueIDUtils::build(),
            "clientToken" => $clientToken,

        ))->exec();
        if ($sqlResult) {
            return (new ResponseUtils())->getResponse(true, 200, ['clientToken' => $clientToken, 'accessToken' => $accessToken]);
        } else {
            return (new ResponseUtils())->getResponse(false, -6001, 'Server Error');
        }

    }

    /**
     * 此方法实现了获取用户列表
     *
     * @route GET /admin/list
     *
     * @hook \App\Hooks\AuthenticationHooks\AdminAuth
     *
     * @param $page
     * @param $pageSize
     */
    public function getUserlist($page, $pageSize)
    {
        $userResult = $this->db->select('*')
            ->from('users')
            ->get();
        $allUsers = count($userResult);
        $userResult = array_slice($userResult, ($page - 1) * $pageSize, $pageSize);
        foreach ($userResult as $key => $value) {
            $userResult[$key]['headUrl'] = HOST . "user/head/" . $userResult[$key]['uniqueId'];
            unset($userResult[$key]['id']);
            unset($userResult[$key]['password']);
            unset($userResult[$key]['accessToken']);
            unset($userResult[$key]['tokenExpire']);
            unset($userResult[$key]['headBase64']);
        }
        return (new ResponseUtils())->getResponse([
            "page" => $page,
            "totalPage" => $allUsers % $pageSize + 1,
            "totalRow" => $allUsers,
            "list" => $userResult,
            "firstPage" => $page == 1 ? true : false,
            "lastPage" => $page == ($allUsers % $pageSize + 1) ? true : false
        ]);
    }

    /**
     * 此方法实现了更改用户信息
     *
     * @route GET /admin/modifie/{userId}
     *
     * @hook \App\Hooks\AuthenticationHooks\AdminAuth
     *
     * @param $param
     * @param $value
     */
    public function modifieUser($userId, $param, $value)
    {
        $userResult = $this->db->select('*')
            ->from('users')
            ->where(["uniqueId" => $userId])
            ->get();
        if (!$userResult) {
            return (new ResponseUtils())->getResponse(false, 404, "找不到用户");
        }
        $updResult = $this->db->update('users')
            ->set([$param => $value])
            ->where(["uniqueId" => $userId])
            ->exec();
        if ($updResult) {
            return (new ResponseUtils())->getResponse(true);
        } else {
            return (new ResponseUtils())->getResponse(false, 500, "Server error");
        }


    }

    /**
     * 此方法实现了删除用户
     *
     * @route GET /admin/delete/{userId}
     *
     * @hook \App\Hooks\AuthenticationHooks\AdminAuth
     *
     */
    public function deleteUser($userId)
    {
        $userResult = $this->db->select('*')
            ->from('users')
            ->where(["uniqueId" => $userId])
            ->get();
        if (!$userResult) {
            return (new ResponseUtils())->getResponse(false, 404, "找不到用户");
        }
        $updResult = $this->db->deleteFrom('users')
            ->where(["uniqueId" => $userId])
            ->exec();
        if ($updResult) {
            return (new ResponseUtils())->getResponse(true);
        } else {
            return (new ResponseUtils())->getResponse(false, 500, "Server error");
        }


    }
}