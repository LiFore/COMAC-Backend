<?php


namespace App\Controllers\ProfileController;

use App\Controllers\AuthenticationController\AuthenticationUtils;
use App\Controllers\AuthenticationController\TokenUtils;
use App\Controllers\PxSamcController\SamcUtils;
use App\Utils\HeaderUtils;
use App\Utils\ResponseUtils;
use App\Utils\TimeUtils;
use App\Utils\UniqueIDUtils;
use PhpBoot\DB\DB;
use PhpBoot\DI\Traits\EnableDIAnnotations;

/**
 * Class ProfileController
 * @package App\Controllers\ProfileController
 *
 * @path /profile/
 *
 * 档案控制器
 */
class ProfileController
{
    use EnableDIAnnotations;

    //启用通过@inject标记注入依赖

    /**
     * @inject
     * @var DB
     */
    private $db;


    /**
     * 此方法实现了获取指定学院的个人档案
     * @route GET /student/get/{workId}
     *
     * @param $workId
     *
     */
    public function getStudentProfileByWorkId($workId)
    {
        $userInfo = (new TokenUtils())->getInfoByWorkId($this->db, $workId);
        $samcReturnUI = SamcUtils::sendRequest('home', 'User', 'user_info', $workId);
        $samcReturnCI = SamcUtils::sendRequest('course', 'LineClass', 'api_user_learn_progress', $workId);
        $samcReturnPI = SamcUtils::sendRequest('course', 'LineClass', 'getmyallproject', $workId);
        if (!$userInfo) {

            if (!$samcReturnUI) {
                return (new ResponseUtils())->getResponse('', -4003, '未查询到匹配数据 请检查工号');
            }
            if ($samcReturnUI['de_user_mobile'] == '' || $samcReturnUI['de_user_realname'] == '') {
                return (new ResponseUtils())->getResponse('', -4004, '未查询到匹配数据 请检查工号');
            }

            $sqlResult = $this->db->insertInto('users')->values(array(
                "username" => $samcReturnUI['de_user_realname'],
                "workId" => $workId,
                "cellPhone" => $samcReturnUI['de_user_mobile'],
                "realName" => $samcReturnUI['de_user_realname'],
                "password" => 'empty',
                "accessToken" => '',
                "registerAt" => TimeUtils::getNormalTime(),
                "loginAt" => TimeUtils::getNormalTime(),
                "headBase64" => AuthenticationUtils::geneNewHead(),
                "lastIp" => HeaderUtils::getIp(),
                "permission" => 0,
                "uniqueId" => UniqueIDUtils::build(),
                "clientToken" => '',
                "userWorkType" => 0
            ))->exec();
        }

        $userInfo = (new TokenUtils())->getInfoByWorkId($this->db, $workId);

        if ($userInfo['userWorkType'] == 1) {
            return (new ResponseUtils())->getResponse('', -4005, '教员无学生档案数据 请检查工号');
        }

        $profileResult = $this->db->select('*')->from("profiles")->where(['workId' => $workId])->getFirst();

        $projectArray = [];

        foreach ($samcReturnPI['data'] as $kPI => $vPI){
            if($projectArray[$vPI['project_id']] == null){
                $projectArray[$vPI['project_id']] = ["projectInfo" => [
                    "course_name" => "班级".$vPI['project_id']."",
                ],"project_ended"=>0,"project_start"=>0];
            }
            if( strtotime($vPI['project_start_date']) > time()){
                $samcReturnPI['data'][$kPI]['isNoStarted'] = true;
            }
            if($vPI['sign_log'] != null){
                $projectArray[$vPI['project_id']]['project_ended'] += 1;
            }
            $projectArray[$vPI['project_id']]['project_start'] += 1;

        }

        if ($profileResult) {
            return (new ResponseUtils())->getResponse(["profile" => $profileResult, "samc" => ['project' => $samcReturnPI,'project_progress'=>$projectArray, 'user' => $samcReturnUI, 'course' => $samcReturnCI]], 200);
        } else {
            $insResult = $this->db->insertInto('profiles')->values(array(
                "workId" => $workId,
                "phoneNumber" => $samcReturnUI['de_user_mobile'],
            ))->exec();

            if ($insResult) {
                $profileResult = $this->db->select('*')->from("profiles")->where(['workId' => $workId])->getFirst();
                return (new ResponseUtils())->getResponse(["profile" => $profileResult, "samc" => ['project' => $samcReturnPI,'project_progress'=>$projectArray, 'user' => $samcReturnUI, 'course' => $samcReturnCI]], 200);
            } else {
                return (new ResponseUtils())->getResponse(false, -6001, 'Server Error');
            }
        }


    }

    /**
     * 此方法实现了获取指定学院的个人档案
     * @route GET /student/signin/{workId}
     *
     * @param $workId
     *
     */
    public function signInStudentProfileByWorkId($workId)
    {
        $userInfo = (new TokenUtils())->getInfoByWorkId($this->db, $workId);
        $samcReturnUI = SamcUtils::sendRequest('home', 'User', 'user_info', $workId);
        if (!$userInfo) {

            if (!$samcReturnUI) {
                return (new ResponseUtils())->getResponse('', -4003, '未查询到匹配数据 请检查工号');
            }
            if ($samcReturnUI['de_user_mobile'] == '' || $samcReturnUI['de_user_realname'] == '') {
                return (new ResponseUtils())->getResponse('', -4004, '未查询到匹配数据 请检查工号');
            }

            $sqlResult = $this->db->insertInto('users')->values(array(
                "username" => $samcReturnUI['de_user_realname'],
                "workId" => $workId,
                "cellPhone" => $samcReturnUI['de_user_mobile'],
                "realName" => $samcReturnUI['de_user_realname'],
                "password" => 'empty',
                "accessToken" => '',
                "registerAt" => TimeUtils::getNormalTime(),
                "loginAt" => TimeUtils::getNormalTime(),
                "headBase64" => AuthenticationUtils::geneNewHead(),
                "lastIp" => HeaderUtils::getIp(),
                "permission" => 0,
                "uniqueId" => UniqueIDUtils::build(),
                "clientToken" => '',
                "userWorkType" => 0
            ))->exec();
        }

        $userInfo = (new TokenUtils())->getInfoByWorkId($this->db, $workId);

        if ($userInfo['userWorkType'] == 1) {
            return (new ResponseUtils())->getResponse('', -4005, '教员无学生档案数据 请检查工号');
        }

        $profileResult = $this->db->select('*')->from("profiles")->where(['workId' => $workId])->getFirst();

        if ($profileResult) {
            $res=array_filter($profileResult);
            if(count($res) == count($profileResult)){
                return (new ResponseUtils())->getResponse(["profile" => $profileResult], 200);
            }
            return (new ResponseUtils())->getResponse(["profile" => $profileResult], 202);
        } else {
            $insResult = $this->db->insertInto('profiles')->values(array(
                "workId" => $workId,
                "phoneNumber" => $samcReturnUI['de_user_mobile'],
            ))->exec();

            if ($insResult) {
                $profileResult = $this->db->select('*')->from("profiles")->where(['workId' => $workId])->getFirst();
                return (new ResponseUtils())->getResponse(["profile" => $profileResult], 204);
            } else {
                return (new ResponseUtils())->getResponse(false, -6001, 'Server Error');
            }
        }


    }

    /**
     * 此方法实现了获取指定学院的个人档案
     * @route GET /student/signup/{workId}
     *
     * @param $workId
     *
     */
    public function signUpStudentProfileByWorkId($workId,$gender,$attendTime,$birthTime,$nativePlace,$major,$school)
    {
        $userInfo = (new TokenUtils())->getInfoByWorkId($this->db, $workId);
        if (!$userInfo) {
            return (new ResponseUtils())->getResponse('', -4004, '未查询到匹配数据 请检查工号');
        }

        if ($userInfo['userWorkType'] == 1) {
            return (new ResponseUtils())->getResponse('', -4005, '教员无学生档案数据 请检查工号');
        }

        if($attendTime == "null" || $birthTime == "null" || $nativePlace == "null" || $major == "null" || $school == "null"){
            return (new ResponseUtils())->getResponse('', -4002, '请完整填写档案信息');
        }

        $updResult = $this->db->update("profiles")->set(["gender" => $gender,"attendTime"=>$attendTime,"birthTime"=>$birthTime,"nativePlace" => $nativePlace, "major" => $major , "school" => $school])->where(["workId" => $workId])->exec();

        if ($updResult) {
            return (new ResponseUtils())->getResponse("", 200,"success");
        } else {
            return (new ResponseUtils())->getResponse(false, -6001, 'Server Error');
        }


    }
}