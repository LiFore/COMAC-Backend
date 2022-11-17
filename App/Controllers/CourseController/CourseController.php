<?php


namespace App\Controllers\CourseController;

use App\Controllers\AuthenticationController\TokenUtils;
use App\Controllers\PxSamcController\SamcUtils;
use App\Utils\ResponseUtils;
use PhpBoot\DB\DB;
use PhpBoot\DI\Traits\EnableDIAnnotations;

/**
 * Class CourseController
 * @package App\Controllers\CourseController
 *
 * @path /course/
 */
class CourseController
{
    use EnableDIAnnotations;

    //启用通过@inject标记注入依赖

    /**
     * @inject
     * @var DB
     */
    private $db;

    private $weekArray = array("日","一","二","三","四","五","六");

    /**
     * 通过工号获取课程信息
     *
     * @hook \App\Hooks\AuthenticationHooks\UserAuth
     *
     * @route GET /teacher/course
     * @param string $keywords
     * @param string $startTime
     * @param string $endTime
     * @return array
     */
    public function getCourseByWorkId($keywords = '', $startTime = '', $endTime = '')
    {
        $userInfo = (new TokenUtils())->getUUIDByToken($this->db);


        $conditions = "teacherIds like '%" . $userInfo['workId'] . "%'";

        if($keywords != '')$conditions .= " AND projectName like '%".$keywords."%'";
        if($startTime != '')$conditions .= " AND startTime >= '".$startTime."'";
        if($endTime != '')$conditions .= " AND endTime <= '".$endTime."'";

        $courseResult = $this->db->select('*')->from("courses")->where($conditions)->get();

        $courseArray = [];

        foreach ($courseResult as $couKey => $couItem){
            $teacherIdArray = explode(",",$couItem['teacherIds']);
            $teacherInfoArray = [];

            foreach ($teacherIdArray as $tk => $ti){
                $teacherInfo = (new TokenUtils())->getInfoByWorkId($this->db,$ti);
                array_push($teacherInfoArray,$teacherInfo['username']);
            }
            $couItem['teacherInfo'] = implode(",",$teacherInfoArray);
            if($courseArray[$couItem['projectName']]){
                array_push($courseArray[$couItem['projectName']]['data'],$couItem);
            }else{
                $courseArray[$couItem['projectName']] = ['data'=>[$couItem]];
            }
        }

        foreach ($courseArray as $caKey => $caItem){
            $startTime = 0;
            $nearestTime = 0;
            $latestTime = 0;
            $allCount = count($caItem['data']);
            $endCount = 0;
            $couItem['teacherInfo'] = implode(",",$teacherInfoArray);
            foreach ($caItem['data'] as $tk => $ti){
                if($startTime > strtotime($ti['startTime']) || $startTime == 0){
                    $startTime = strtotime($ti['startTime']);
                }
                if($latestTime < strtotime($ti['startTime']) || $latestTime == 0){
                    $latestTime = strtotime($ti['startTime']);
                }
                if(($nearestTime < strtotime($ti['startTime']) && strtotime($ti['startTime']) > strtotime(date("Y-m-d H:i:s"))) || $nearestTime == 0){
                    $nearestTime = strtotime($ti['startTime']);
                }
                if( strtotime($ti['startTime']) <= strtotime(date("Y-m-d H:i:s")) ){
                    $endCount++;
                }
            }
            $courseArray[$caKey]['teacherInfo'] =  implode(",",$teacherInfoArray);
            $courseArray[$caKey]['startTime'] = date("Y-m-d H:i:s",$startTime);
            $courseArray[$caKey]['nearestTime'] = date("Y-m-d H:i:s",$nearestTime);
            $courseArray[$caKey]['status'] = $latestTime <= strtotime(date("Y-m-d H:i:s")) ? 2 : (strtotime(date("Y-m-d H:i:s")) <= $startTime ? 0 : 1);
            $courseArray[$caKey]['allCount'] = $allCount;
            $courseArray[$caKey]['endCount'] = $endCount;
        }

        return (new ResponseUtils())->getResponse($courseArray);

    }

    /**
     * 实现按月获取课程
     *
     * @hook \App\Hooks\AuthenticationHooks\UserAuth
     *
     * @route GET /mine/course/month
     *
     * @param $month
     * @return array
     */
    public function getMineCourseByMonth($month){
        $userInfo = (new TokenUtils())->getUUIDByToken($this->db);
        $samcReturn = SamcUtils::sendRequestCourseM('course', 'LineClass', 'getproject', $userInfo['workId'],$month);

        $courseArray = [];
        foreach($samcReturn['data'] as $srKey => $srItem){
            if($courseArray[$srItem['project_date']] == null){
                $courseArray[$srItem['project_date']] = [$srItem];
            }else{
                array_push($courseArray[$srItem['project_date']],$srItem);
            }
        }

        return (new ResponseUtils())->getResponse($courseArray);
    }

    /**
     * 实现按日获取课程
     *
     * @hook \App\Hooks\AuthenticationHooks\UserAuth
     *
     * @route GET /mine/course/day
     *
     * @param $day
     * @return array
     */
    public function getMineCourseByDay($day){
        $userInfo = (new TokenUtils())->getUUIDByToken($this->db);
        $samcReturn = SamcUtils::sendRequestCourseD('course', 'LineClass', 'getproject', $userInfo['workId'],$day);

        $courseArray = [];
        foreach($samcReturn['data'] as $srKey => $srItem){

            $teacherIdArray = explode(",",$srItem['teacher_str']);
            $teacherInfoArray = [];

            foreach ($teacherIdArray as $tk => $ti){
                $teacherInfo = (new TokenUtils())->getInfoByWorkId($this->db,$ti);
                array_push($teacherInfoArray,$teacherInfo['username']);
            }
            $srItem['teacherInfo'] = implode(",",$teacherInfoArray);
            if($courseArray[$srItem['project_date']] == null){
                $courseArray[$srItem['project_date']] = [$srItem];
            }else{
                array_push($courseArray[$srItem['project_date']],$srItem);
            }
        }

        return (new ResponseUtils())->getResponse($courseArray);
    }

    /**
     * 实现按月获取课程
     *
     * @hook \App\Hooks\AuthenticationHooks\UserAuth
     *
     * @route GET /course/{dateId}
     *
     * @param $dateId
     * @return array
     */
    public function getCourseByDateId($dateId){
        $userInfo = (new TokenUtils())->getUUIDByToken($this->db);

        $courseResult = $this->db->select('*')->from("courses")->where(['dateId' => $dateId])->getFirst();

        if(!$courseResult){
            return (new ResponseUtils())->getResponse('', -4003, '未查询到匹配数据');
        }

        $samcReturn = SamcUtils::sendRequestCourseUserList('course', 'LineClass', 'api_user_list', $courseResult['projectId']);

        $startTime = strtotime($courseResult['startTime']);
        $endTime = strtotime($courseResult['endTime']);

        $courseResult['classUsers'] = $samcReturn;
        $courseResult['startTime'] = date("H:i",$startTime);
        $courseResult['startDate'] = date("Y-m-d",$startTime)." 周". $this->weekArray[date("w",$startTime)];
        $courseResult['endTime'] = date("H:i",$endTime);
        $courseResult['endDate'] = date("Y-m-d",$endTime)." 周". $this->weekArray[date("w",$endTime)];

        $teacherIdArray = explode(",",$courseResult['teacherIds']);
        $teacherInfoArray = [];

        foreach ($teacherIdArray as $tk => $ti){
            $teacherInfo = (new TokenUtils())->getInfoByWorkId($this->db,$ti);
            array_push($teacherInfoArray,$teacherInfo['username']);
        }
        $courseResult['teacherInfo'] = implode(",",$teacherInfoArray);


        return (new ResponseUtils())->getResponse($courseResult);
    }
}