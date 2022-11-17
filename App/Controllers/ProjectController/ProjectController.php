<?php


namespace App\Controllers\ProjectController;

use App\Controllers\AuthenticationController\TokenUtils;
use App\Controllers\PxSamcController\SamcUtils;
use App\Utils\ResponseUtils;
use PhpBoot\DB\DB;
use PhpBoot\DI\Traits\EnableDIAnnotations;

/**
 * Class ProjectController
 * @package App\Controllers\ProjectController
 *
 * @path /project/
 */
class ProjectController
{
    use EnableDIAnnotations;

    //启用通过@inject标记注入依赖

    /**
     * @inject
     * @var DB
     */
    private $db;

    /**
     * 此方法实现了首页获取班级信息
     * @hook \App\Hooks\AuthenticationHooks\UserAuth
     *
     * @route GET /search
     * @param $projectId
     * @param $className
     * @return array
     */
    public function getProjectList($projectId = '', $className = '')
    {
        $userInfo = (new TokenUtils())->getUUIDByToken($this->db);


        $conditions = "teacherIds like '%" . $userInfo['workId'] . "%'";

        if ($className != '') {
            $conditions .= " AND projectName like '%" . $className . "%'";
        }
        if ($projectId != '') {
            $conditions .= " AND projectId = '" . $projectId . "'";
        }

        $courseResult = $this->db->select('*')->from("courses")->where($conditions)->get();

        $courseArray = [];

        foreach ($courseResult as $couKey => $couItem) {
            $teacherIdArray = explode(",", $couItem['teacherIds']);
            $teacherInfoArray = [];

            foreach ($teacherIdArray as $tk => $ti) {
                $teacherInfo = (new TokenUtils())->getInfoByWorkId($this->db, $ti);
                array_push($teacherInfoArray, $teacherInfo['username']);
            }
            $couItem['teacherInfo'] = implode(",", $teacherInfoArray);
            if ($courseArray[$couItem['projectId']]) {
                array_push($courseArray[$couItem['projectId']]['data'], $couItem);
            } else {
                $courseArray[$couItem['projectId']] = ['data' => [$couItem]];
            }
        }

        foreach ($courseArray as $caKey => $caItem) {
            $startTime = 0;
            $nearestTime = 0;
            $latestTime = 0;
            $allCount = count($caItem['data']);
            $endCount = 0;
            $couItem['teacherInfo'] = implode(",", $teacherInfoArray);
            foreach ($caItem['data'] as $tk => $ti) {
                if ($startTime > strtotime($ti['startTime']) || $startTime == 0) {
                    $startTime = strtotime($ti['startTime']);
                }
                if ($latestTime < strtotime($ti['startTime']) || $latestTime == 0) {
                    $latestTime = strtotime($ti['startTime']);
                }
                if (($nearestTime < strtotime($ti['startTime']) && strtotime($ti['startTime']) > strtotime(date("Y-m-d H:i:s"))) || $nearestTime == 0) {
                    $nearestTime = strtotime($ti['startTime']);
                }
                if (strtotime($ti['startTime']) <= strtotime(date("Y-m-d H:i:s"))) {
                    $endCount++;
                }
            }

            $courseArray[$caKey]['projectName'] = $caItem['data'][0]['projectName'];
            $courseArray[$caKey]['teacherInfo'] = implode(",", $teacherInfoArray);
            $courseArray[$caKey]['startTime'] = date("Y-m-d H:i:s", $startTime);
            $courseArray[$caKey]['nearestTime'] = date("Y-m-d H:i:s", $nearestTime);
            $courseArray[$caKey]['status'] = $latestTime <= strtotime(date("Y-m-d H:i:s")) ? 2 : (strtotime(date("Y-m-d H:i:s")) <= $startTime ? 0 : 1);
            $courseArray[$caKey]['allCount'] = $allCount;
            $courseArray[$caKey]['endCount'] = $endCount;

            unset($courseArray[$caKey]['data']);
        }

        return (new ResponseUtils())->getResponse($courseArray);
    }

    /**
     * 此方法实现了获取班级信息
     * @hook \App\Hooks\AuthenticationHooks\UserAuth
     *
     * @route GET /info/{projectId}
     * @param $projectId
     * @return array
     */
    public function getProjectInfo($projectId)
    {
        $userInfo = (new TokenUtils())->getUUIDByToken($this->db);

        $conditions = "teacherIds like '%" . $userInfo['workId'] . "%'";
        $conditions .= " AND projectId = '" . $projectId . "'";

        $courseResult = $this->db->select('*')->from("courses")->where($conditions)->get();

        $courseArray = [];

        $teacherArray = [];
        $teacherInfoArray = [];

        foreach ($courseResult as $couKey => $couItem) {
            $teacherIdArray = explode(",", $couItem['teacherIds']);


            foreach ($teacherIdArray as $tk => $ti) {
                $teacherInfo = (new TokenUtils())->getInfoByWorkId($this->db, $ti);
                array_push($teacherInfoArray, $teacherInfo['username']);
                array_push($teacherArray, $teacherInfo);
            }
            $couItem['teacherInfo'] = implode(",", $teacherInfoArray);
            if ($courseArray[$couItem['projectId']]) {
                array_push($courseArray[$couItem['projectId']]['data'], $couItem);
            } else {
                $courseArray[$couItem['projectId']] = ['data' => [$couItem]];
            }
        }

        foreach ($courseArray as $caKey => $caItem) {
            $startTime = 0;
            $nearestTime = 0;
            $latestTime = 0;
            $allCount = count($caItem['data']);
            $endCount = 0;
            $couItem['teacherInfo'] = implode(",", $teacherInfoArray);
            foreach ($caItem['data'] as $tk => $ti) {
                if ($startTime > strtotime($ti['startTime']) || $startTime == 0) {
                    $startTime = strtotime($ti['startTime']);
                }
                if ($latestTime < strtotime($ti['startTime']) || $latestTime == 0) {
                    $latestTime = strtotime($ti['startTime']);
                }
                if (($nearestTime < strtotime($ti['startTime']) && strtotime($ti['startTime']) > strtotime(date("Y-m-d H:i:s"))) || $nearestTime == 0) {
                    $nearestTime = strtotime($ti['startTime']);
                }
                if (strtotime($ti['startTime']) <= strtotime(date("Y-m-d H:i:s"))) {
                    $endCount++;
                }
            }

            $samcReturn = SamcUtils::sendRequestCourseUserList('course', 'LineClass', 'api_user_list', $caItem['data'][0]['projectId']);

            $courseArray['projectId'] = $caItem['data'][0]['projectId'];
            $courseArray['classUsers'] = $samcReturn;
            $courseArray['projectName'] = $caItem['data'][0]['projectName'];
            $courseArray['teacherInfo'] = implode(",", $teacherInfoArray);
            $courseArray['teacherArray'] = $teacherArray;
            $courseArray['startTime'] = date("Y-m-d H:i:s", $startTime);
            $courseArray['nearestTime'] = date("Y-m-d H:i:s", $nearestTime);
            $courseArray['status'] = $latestTime <= strtotime(date("Y-m-d H:i:s")) ? 2 : (strtotime(date("Y-m-d H:i:s")) <= $startTime ? 0 : 1);
            $courseArray['allCount'] = $allCount;
            $courseArray['endCount'] = $endCount;

            unset($courseArray[$caKey]['data']);
        }

        return (new ResponseUtils())->getResponse($courseArray);
    }
}