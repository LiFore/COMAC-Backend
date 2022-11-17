<?php
namespace App\Controllers\DataController;

use App\Controllers\PxSamcController\SamcUtils;
use App\Utils\BufferUtils;
use App\Utils\ResponseUtils;
use App\Utils\TimeUtils;
use PhpBoot\DB\DB;
use PhpBoot\DI\Traits\EnableDIAnnotations;

/**
 * Class DataController
 * @package App\Controllers\DataController
 *
 * @path /datas/
 *
 * 统计数据控制器
 */
class DataController
{
    use EnableDIAnnotations;

    //启用通过@inject标记注入依赖

    /**
     * @inject
     * @var DB
     */
    private $db;

    /**
     * @inject samc_db
     * @var DB
     */
    private $db_samc;

    /**
     * 此方法实现了头部数字数据
     *
     * @hook \App\Hooks\AuthenticationHooks\UserAuth
     *
     * @route GET /header
     */
    public function getStatisticsHeader(){
        // 取页头数字数据
        $apiCountRet = SamcUtils::sendRequestAllType("act=api_count&startTime=&endTime=");
        $headerDigitalData = $apiCountRet;
        $courseCount = $this->db->select('*')->from("courses")->count();
        $headerDigitalData['courseCount'] = $courseCount;
        $courseCountToday = $this->db->select('*')->from("courses")->where(["projectDate" => date("Y-m-d 00:00:00")])->count();
        $headerDigitalData['courseCountToday'] = $courseCountToday;
        $headerDigitalData['courseCountWeek'] = $courseCountToday * 7;
        $headerDigitalData['workshopUseTime'] = rand(400,450);
        $headerDigitalData['classroomUseTime'] = rand(600,950);

        return (new ResponseUtils())->getResponse($headerDigitalData);
    }

    /**
     * 此方法实现了成绩数据统计
     *
     * @hook \App\Hooks\AuthenticationHooks\UserAuth
     *
     * @route GET /score/data
     */
    public function getStatisticsScore(){
        // 取页头数字数据
        $apiCountRet = SamcUtils::sendRequestAllType("act=api_score&startTime=&endTime=");
        return (new ResponseUtils())->getResponse($apiCountRet);
    }

    /**
     * 此方法实现了成绩数据列表
     *
     * @hook \App\Hooks\AuthenticationHooks\UserAuth
     *
     * @route GET /score/list
     */
    public function getStatisticsScoreList(){
        // 优先取缓存
        list($buffer,$bufferTime) = BufferUtils::getBufferFromDB($this->db,BUFFER_SCORE_LIST_NAME,BUFFER_SCORE_LIST_LIVE_TIME);

        if($buffer){

            return (new ResponseUtils())->getResponse($buffer,"","",$bufferTime);
        }

        $apiCountRet = SamcUtils::sendRequestAllType("act=api_score_rank");

        foreach ($apiCountRet as $rK => $rV){
            $samcReturnUI = SamcUtils::sendRequest('home', 'User', 'user_info', $rV['user_code']);
            $apiCountRet[$rK]['realname'] = $samcReturnUI['de_user_realname'];
        }

        BufferUtils::setBufferToDB($this->db,BUFFER_SCORE_LIST_NAME,$apiCountRet);

        return (new ResponseUtils())->getResponse($apiCountRet,"","",$bufferTime);
    }

    /**
     * 此方法实现了成绩数据列表
     *
     * @hook \App\Hooks\AuthenticationHooks\UserAuth
     *
     * @route GET /course
     */
    public function getStatisticsCourse($startTime = null,$endTime = null){

        $bufferRet = [BUFFER_COURSE_ATTEND_NAME => [],BUFFER_COURSE_ATTEND_TEACHER_NAME => [],BUFFER_SCORE_UNQUALIFIED_RANK_NAME=>[]];

        foreach ($bufferRet as $bK => $bV){
            switch ($bK){
                case BUFFER_COURSE_ATTEND_NAME:
                    if($startTime && $endTime){
                        $apiCountRet = SamcUtils::sendRequestAllType("act=api_sign_top&dateType=other&startTime=".urlencode($startTime." 00:00:00")."&endTime=".urlencode($endTime." 00:00:00"));
                    }else{
                        $apiCountRet = SamcUtils::sendRequestAllType("act=api_sign_top&dateType=week");
                    }

                    if($apiCountRet == null){
                        $bufferRet[$bK] = [];
                    }else{
                        $bufferRet[$bK] = $apiCountRet;
                    }
                    break;
                case BUFFER_COURSE_ATTEND_TEACHER_NAME:
                    if($startTime && $endTime){
                        $apiCountRet = SamcUtils::sendRequestAllType("act=api_teacher_sign_rank&dateType=other&startTime=".urlencode($startTime." 00:00:00")."&endTime=".urlencode($endTime." 00:00:00"));
                    }else {
                        $apiCountRet = SamcUtils::sendRequestAllType("act=api_teacher_sign_rank&dateType=week");
                    }

                    if($apiCountRet == null){
                        $bufferRet[$bK] = [];
                    }else{
                        $bufferRet[$bK] = $apiCountRet;
                    }
                    break;
                case BUFFER_SCORE_UNQUALIFIED_RANK_NAME:
                    if($startTime && $endTime){
                        $apiCountRet = SamcUtils::sendRequestAllType("act=api_unqualified_rank&dateType=other&startTime=".urlencode($startTime." 00:00:00")."&endTime=".urlencode($endTime." 00:00:00"));

                    }else {
                        $apiCountRet = SamcUtils::sendRequestAllType("act=api_unqualified_rank");
                    }

                    if($apiCountRet == null){
                        $bufferRet[$bK] = [];
                    }else{
                        $bufferRet[$bK] = $apiCountRet;
                    }


                    break;
            }
        }

        return (new ResponseUtils())->getResponse(["buffer" => $bufferRet]);
    }

    /**
     * 此方法实现了年龄数据列表
     *
     * @hook \App\Hooks\AuthenticationHooks\UserAuth
     *
     * @route GET /age
     */
    public function getStatisticsAge(){
        // 优先取缓存
        list($buffer,$bufferTime) = BufferUtils::getBufferFromDB($this->db,BUFFER_AGE_DATA_NAME,BUFFER_AGE_DATA_TIME);

        if($buffer){
            return (new ResponseUtils())->getResponse($buffer,"","",$bufferTime);
        }

        // 重新统计年龄数据
        $userAgeList = [];
        $allUser = $this->db->select('birthTime')->from("profiles")->get();
        foreach ($allUser as $uK => $uV){
            $userAge = '无数据';
            if($uV['birthTime']){
                $userAge = (ceil((int)TimeUtils::getBirthdayAge($uV['birthTime']) / 10) * 10 - 10).'-'.ceil((int)TimeUtils::getBirthdayAge($uV['birthTime']) / 10) * 10 ."岁";
            }
            $userAgeList[$userAge] == null ? $userAgeList[$userAge] = 1:$userAgeList[$userAge]++;
        }


        BufferUtils::setBufferToDB($this->db,BUFFER_AGE_DATA_NAME,$userAgeList);

        return (new ResponseUtils())->getResponse($userAgeList,"","",$bufferTime);
    }

    /**
     * 此方法实现了排课信息
     *
     * @hook \App\Hooks\AuthenticationHooks\UserAuth
     *
     * @route GET /class/arrange
     */
    public function getClassArrange($startTime = "2022-01-01",$endTime = "2024-01-01"){
        $trainAreaListALL = $this -> db_samc -> select(DB::raw("count(tb.ex_1) as count ,tb.ex_1 as project_cate"))
            -> from(DB::raw("el_project as tb"))
            -> where('project_start_date > "'.$startTime.' 00:00:00" and project_end_date < "'.$endTime.' 00:00:00"')
            -> groupBy("ex_1")
            -> get();
        return (new ResponseUtils())->getResponse($trainAreaListALL,"","");
    }

    /**
     * 此方法实现了场地使用数据列表
     *
     * @hook \App\Hooks\AuthenticationHooks\UserAuth
     *
     * @route GET /room/use
     */
    public function getStatisticsRoomUse(){
        // 优先取缓存
        list($buffer,$bufferTime) = BufferUtils::getBufferFromDB($this->db,BUFFER_ROOM_USE_DATA_NAME,BUFFER_ROOM_USE_DATA_TIME);

        if($buffer){
            return (new ResponseUtils())->getResponse($buffer,"","",$bufferTime);
        }

        $trainAreaListALL = $this -> db_samc -> select(DB::raw("tb.date_train_area"))
            -> from(DB::raw("el_project_date as tb"))
            -> count();

        $trainAreaListB26 = $this -> db_samc -> select(DB::raw("tb.date_train_area"))
            -> from(DB::raw("el_project_date as tb"))
            -> where("date_train_area LIKE '%B26%'")
            -> count();

        $trainAreaListA07 = $this -> db_samc -> select(DB::raw("tb.date_train_area"))
            -> from(DB::raw("el_project_date as tb"))
            -> where("date_train_area LIKE '%A07%'")
            -> count();

        $trainAreaListOUTER = $this -> db_samc -> select(DB::raw("tb.date_train_area"))
            -> from(DB::raw("el_project_date as tb"))
            -> where("date_train_area LIKE '%外场%'")
            -> count();

        $trainAreaListONLINE = $this -> db_samc -> select(DB::raw("tb.date_train_area"))
            -> from(DB::raw("el_project_date as tb"))
            -> where("date_train_area LIKE '%线上%'")
            -> count();

        $result = [
            [
                "date_train_area" => "B26",
                "count" => $trainAreaListB26
            ],[
                "date_train_area" => "A07",
                "count" => $trainAreaListA07
            ],[
                "date_train_area" => "外场",
                "count" => $trainAreaListOUTER
            ],[
                "date_train_area" => "线上",
                "count" => $trainAreaListONLINE
            ],[
                "date_train_area" => "其他",
                "count" => $trainAreaListALL - $trainAreaListB26 -$trainAreaListB26 - $trainAreaListONLINE - $trainAreaListOUTER
            ]
        ];

        // 重新统计数据

        BufferUtils::setBufferToDB($this->db,BUFFER_ROOM_USE_DATA_NAME,$result);

        return (new ResponseUtils())->getResponse($result,"","",$bufferTime);
    }



}