<?php

namespace App\Controllers\ReportsController;

use App\Utils\ResponseUtils;
use PhpBoot\DB\DB;
use PhpBoot\DI\Traits\EnableDIAnnotations;

/**
 * Class ReportsController
 * @package App\Controllers\ReportsController
 *
 * @path /reports/
 */
class ReportsController
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
     * 此方法实现了通过workId获取指定用户个人评估报告
     *
     * @route GET /personal/{workId}
     *
     * @return array
     */
    public function getPersonalReportData($workId)
    {
        $userStanderdData = $this->db->select("COU.*,CSB.NAME")
            ->from(DB::raw("co_user_subject as COU"))
            ->leftJoin(DB::raw("co_subject as CSB"))
            ->on("COU.subject_code = CSB.SUBJECT_ID")
            ->where("COU.user_code = " . $workId)
            ->getFirst();
        if (!$userStanderdData) {
            //TODO: 自动补全用户专业信息逻辑
            return (new ResponseUtils())->getResponse(false, 400, '该用户专业尚未补全');
        }

        $userScoreData = $this->db_samc->select("*")
            ->from("el_project_score")
            ->where("user_code = " . $workId)
            ->get();

        $scoreResult = [];

        $isPEPass = true;
        $isTEPass = true;

        $commentStr = "该学员";

        foreach ($userScoreData as $usI){
            $scoreDataList = $this->db_samc->select("*")
                ->from("el_project_score")
                ->where("project_id = " . $usI['project_id'])
                ->orderBy("score","DESC")
                ->get();
            $position = 1;
            if($usI['score'] < 80 && $usI['score'] != '合格'){
                if($usI['score2'] < 80 && $usI['score2'] != '合格'){
                    if(is_integer($usI['score2'])){
                        $isTEPass = false;
                    }
                    else{
                        $isPEPass = false;
                    }
                }
            }
            foreach ($scoreDataList as $sdI){

                if($usI['score'] < $sdI['score']){
                    $position ++;
                }else{
                    break;
                }
            }
            $positionPercentage = round($position / count($scoreDataList) * 100,2);

            $scoreResult[] = [
                "course_name" => $usI['date_course_name'],
                "score_F" => $usI['score'],
                "score_S" => $usI['score2'],
                "percent" => $positionPercentage
            ];
        }

        $commentStr .="理论考试成绩".($isTEPass ? '合格' : '不合格');
        $commentStr .="，实操考试成绩".($isPEPass ? '合格' : '不合格');
        $commentStr .="，".($isTEPass && $isPEPass? '通过' : '未通过')."本次培训。";

        $userActionData = $this->db_samc->select("*")
            ->from("el_project_member_action_log")
            ->where("user_code = " . $workId)
            ->get();

        $nineCon = [
            "安全意识" => 0,
            "按章作业意识" => 0,
            "产品保护意识" => 0,
            "多余物控制意识" => 0,
            "工具管控意识" => 0,
            "交接意识" => 0,
            "举手意识" => 0,
            "自检互检意识" => 0
        ];

        $nineConSecondary = [
            "安全意识" => [],
            "按章作业意识" => [],
            "产品保护意识" => [],
            "多余物控制意识" => [],
            "工具管控意识" => [],
            "交接意识" => [],
            "举手意识" => [],
            "自检互检意识" => []
        ];




        foreach ($userActionData as $nV){
            $nineCon[$nV['option1']] ++;
            if($nineConSecondary[$nV['option1']][$nV['option2']] == null){
                $nineConSecondary[$nV['option1']][$nV['option2']] = [
                    'times' => 1,
                    'descriptions' => [],
                ];
                $nV['description'] == '' or $nineConSecondary[$nV['option1']][$nV['option2']]['descriptions'][] = $nV['description'];
            }else{
                $nineConSecondary[$nV['option1']][$nV['option2']]['times'] ++ ;
                $nV['description']  == '' or $nineConSecondary[$nV['option1']][$nV['option2']]['descriptions'][] = $nV['description'];
            }

        }

        $nineConAllScore = 0;
        $actionCommentStr = [];
        foreach ($nineCon as $nK => $nI){
            $nineConAllScore += max((100 - $nI * 10), 0);
            $nineCon[$nK] = 100 - $nI * 10;

            if($nineConSecondary[$nK] != []){
                $actionCommentStrSub = $nK.'中，';
                foreach ($nineConSecondary[$nK] as $n2K => $n2I){
                    $actionCommentStrSub .= $n2K.'扣分'.$n2I['times'].'次';
                    if(count($n2I['descriptions']) > 0){
                        $actionCommentStrSub .= '具体表现为：' . implode('，', $n2I['descriptions']);
                    }
                }
                $actionCommentStr[] = $actionCommentStrSub;
            }
        }
        $nineConAllScore = round($nineConAllScore / 9 ,2);

        $commentStr .= "九大意识".($nineConAllScore > 90 ? '成绩良好，掌握情况全面' : ($nineConAllScore > 80 ? '掌握情况良好' : '掌握情况一般'));


        if(count($actionCommentStr) > 0){
            $commentStr .= '：'.implode("；",$actionCommentStr).'。';
        }

        $RESULT = [
            "score_data" => $scoreResult,
            "basic_info" => $userStanderdData,
            "action_log" => [
                "score" => $nineConAllScore,
                "actions" => $nineCon,
            ],
            "comment" => $commentStr,
        ];

        return (new ResponseUtils())->getResponse($RESULT, 200, '');

    }


    /**
     * 此方法实现了通过批次号获取指定批次评估报告信息
     *
     * @route GET /batch/{batchId}
     *
     * @return array
     */
    public function getBatchReportDataPart1($batchId)
    {
        $projectList = $this->db_samc->select("*")
            ->from("el_project")
            ->where("exim LIKE '%" . $batchId . "%'")
            ->get();

        // START RP_1_1 TABLE
        // START RP_1_2 TABLE
        $RP_1_1_TB = [
            "TT" => [
                "type" => "理论培训",
                "peopleNum" => 0,
                "courseNum" => 0
            ],
            "PT" => [
                "type" => "实操培训",
                "peopleNum" => 0,
                "courseNum" => 0
            ],
            "TE" => [
                "type" => "理论考核",
                "peopleNum" => 0,
                "courseNum" => 0
            ],
            "PE" => [
                "type" => "实操考核",
                "peopleNum" => 0,
                "courseNum" => 0
            ]
        ];
        $RP_1_2_TB = [
            "TT" => [
                "type" => "理论培训",
                "teacherNum" => [],
                "courseAvgNum" => 0,
                "peopleAvgNum" => 0,
            ],
            "PT" => [
                "type" => "实操培训",
                "teacherNum" => [],
                "courseAvgNum" => 0,
                "peopleAvgNum" => 0,
            ]
        ];

        $totalFormCount = 0;
        $totalPeopleCount = 0;

        foreach ($projectList as $project) {
            $RP_1_1_TB_TEMP = [
                "TT" => [
                    "courseList" => [],
                    "peopleList" => [],
                ],
                "PT" => [
                    "courseList" => [],
                    "peopleList" => [],
                ],
                "TE" => [
                    "courseList" => [],
                    "peopleList" => [],
                ],
                "PE" => [
                    "courseList" => [],
                    "peopleList" => [],
                ]
            ];
            $RP_1_2_TB_TEMP = [
                "TT" => [
                ],
                "PT" => [
                ]
            ];
            $dateList = $this->db_samc->select("*")
                ->from("el_project_date")
                ->where("project_id = " . $project['project_id'] . "")
                ->get();
            $memberList = $this->db_samc->select("user_code")
                ->from("el_project_member")
                ->where("project_id = " . $project['project_id'] . "")
                ->get();

            foreach ($dateList as $date) {

                // RP_1_1_TB
                foreach ($memberList as $mI) {
                    $uniqueStringMember = $date['date_id'] . $date["ex_7"] . $mI['user_code'];
                    if (!in_array($uniqueStringMember, $RP_1_1_TB_TEMP[$date["ex_7"]]["peopleList"])) $RP_1_1_TB_TEMP[$date["ex_7"]]["peopleList"][] = $uniqueStringMember;
                }
                $uniqueStringCourse = $date['ex_6'] . $date["ex_7"];
                if (!in_array($uniqueStringCourse, $RP_1_1_TB_TEMP[$date["ex_7"]]["courseList"])) $RP_1_1_TB_TEMP[$date["ex_7"]]["courseList"][] = $uniqueStringCourse;

                // RP_1_2_TB
                $date['project_teacher_code'] = explode("、", $date['project_teacher_code']);
                foreach ($date['project_teacher_code'] as $teacherCode) {
                    if ($date["ex_7"] == "PT" && !in_array($date['project_teacher_code'], $RP_1_2_TB_TEMP['PT'])) $RP_1_2_TB_TEMP['PT'][] = $teacherCode;
                    if ($date["ex_7"] == "TT" && !in_array($date['project_teacher_code'], $RP_1_2_TB_TEMP['TT'])) $RP_1_2_TB_TEMP['TT'][] = $teacherCode;
                }

                $totalFormCount += $this->db_samc->select("*")
                    ->from("el_forms_data")
                    ->where("date_id = " . $date['date_id'] . "")
                    ->count();
            }


            foreach ($RP_1_1_TB_TEMP as $TB_TYPE => $TB_TEMP_DATA) {
                $RP_1_1_TB[$TB_TYPE]["courseNum"] += count($TB_TEMP_DATA["courseList"]);
                $totalPeopleCount += count($TB_TEMP_DATA["peopleList"]);

                $RP_1_1_TB[$TB_TYPE]["peopleNum"] += count($TB_TEMP_DATA["peopleList"]);
            }

            foreach ($RP_1_2_TB_TEMP as $TB_TYPE => $TB_TEMP_DATA) {

                $RP_1_2_TB[$TB_TYPE]["teacherNum"] = array_merge($RP_1_2_TB[$TB_TYPE]["teacherNum"], $TB_TEMP_DATA);

            }
        }

        foreach ($RP_1_2_TB as $TB_TYPE => $TB_TEMP_DATA) {
            $RP_1_2_TB[$TB_TYPE]["teacherNum"] = count(array_unique($TB_TEMP_DATA["teacherNum"]));
            $RP_1_2_TB[$TB_TYPE]["courseAvgNum"] = $RP_1_2_TB[$TB_TYPE]["teacherNum"] == 0 ? 0.00 :round($RP_1_1_TB[$TB_TYPE]["courseNum"] / $RP_1_2_TB[$TB_TYPE]["teacherNum"], 2);
            $RP_1_2_TB[$TB_TYPE]["peopleAvgNum"] = $RP_1_2_TB[$TB_TYPE]["teacherNum"] == 0 ? 0.00 :round($RP_1_1_TB[$TB_TYPE]["peopleNum"] / $RP_1_2_TB[$TB_TYPE]["teacherNum"], 2);
        }
        // END RP_1_2 TABLE
        // END RP_1_1 TABLE

        // START RP_1_3_1 TABLE
        $RP_1_3_1_TB = $this->getLast6BatchComment($batchId);
        // END RP_1_3_1 TABLE

        // START RP_1_3_2 TABLE
        $RP_1_3_2_TB = $this->getLast6BatchScore($batchId);
        // END RP_1_3_2 TABLE

        $result = [
            "RP_1_1_TB" => $RP_1_1_TB,
            "RP_1_2_TB" => $RP_1_2_TB,
            "RP_1_3_1_TB" => $RP_1_3_1_TB,
            "RP_1_3_2_TB" => $RP_1_3_2_TB,
            "RP_WORDS_LIST" => [
                "RP_TITLE" => $projectList[0]['ex_2']."年第".$projectList[0]['ex_3']."批".$projectList[0]['project_cate']."评估报告",
                "RP_TITLE_DESCRIPTION" => "（概况介绍）"
            ]
        ];

        $result['RP_WORDS_LIST']["RP_2_1_1_DESCRIPTION"] = "问卷回收量为".$totalFormCount."份，回收率为".round($totalFormCount / $totalPeopleCount * 100 ,2)."%";


        $resultSet = [
            "RP_2_1_2" => [
                "RP_2_1_2_1" => [],
                "RP_2_1_2_2" => [],
            ],
            "RP_2_1_3" => [
                "RP_2_1_3_1" => [],
                "RP_2_1_3_2" => [],
            ],

        ];

        $courseKey = [0, 1, 6, 7];
        $teacherKey = [2, 3, 4, 5];

        // START RP_2_1_2_1 RP_2_1_2_2 TABLE
        $projectList = $this->db_samc->select("*")
            ->from("el_project")
            ->where("exim LIKE '%" . $batchId . "%'")
            ->get();

        $proposalList = [];
        $timeDifferList = [];

        $totalPTFormCount = 0;
        $totalTTFormCount = 0;

        $totalPTSatisfied = 0;
        $totalTTSatisfied = 0;
        foreach ($projectList as $pI) {
            $dateList = $this->db_samc->select("*")
                ->from("el_project_date")
                ->where("project_id = " . $pI['project_id'] . "")
                ->get();
            foreach ($dateList as $dI) {
                $formList = $this->db_samc->select("*")
                    ->from("el_forms_data")
                    ->where("date_id = " . $dI['date_id'] . "")
                    ->get();

                $courseInfo = [
                    "courseCode" => $dI['ex_6'],
                    "courseName" => $dI['date_course_name']
                ];

                $arrangeCourseHours = intval((strtotime($dI['project_date']." ".$dI['date_end_time']) - strtotime($dI['project_date']." ".$dI['date_start_time'])) / 3600);

                if (($dI['ex_7'] == 'TT')) {
                    $courseInfo['liveStatisfiedPercentage'] = ["num" => 0, "total" => 0];
                    $courseInfo['courseStatisfiedPercentage'] = ["num" => 0, "total" => 0];
                    $courseInfo['testNum'] = 0;
                    $totalTTFormCount ++;
                }
                if (($dI['ex_7'] == 'PT')) {

                    $courseInfo['bookStatisfiedPercentage'] = ["num" => 0, "total" => 0];
                    $courseInfo['hardcoreStatisfiedPercentage'] = ["num" => 0, "total" => 0];
                    $courseInfo['toolsStatisfiedPercentage'] = ["num" => 0, "total" => 0];
                    $courseInfo['courseStatisfiedPercentage'] = ["num" => 0, "total" => 0];
                    $courseInfo['testNum'] = 0;
                    $totalPTFormCount ++;
                }



                foreach ($formList as $fV) {
                    $answerArray = json_decode($fV['answer'], true);

                    if (($dI['ex_7'] == 'TT')) {
                        $courseInfo['bookStatisfiedPercentage']["num"]++;
                        $courseInfo['bookStatisfiedPercentage']["total"] += $answerArray[0];
                        $courseInfo['hardcoreStatisfiedPercentage']["num"]++;
                        $courseInfo['hardcoreStatisfiedPercentage']["total"] += $answerArray[1];
                        $courseInfo['toolsStatisfiedPercentage']["num"]++;
                        $courseInfo['toolsStatisfiedPercentage']["total"] += $answerArray[6];
                        $courseInfo['testNum']++;
                        /*
                        $courseInfo['liveStatisfiedPercentage']["num"]++;
                        $courseInfo['liveStatisfiedPercentage']["total"] += $answerArray[7];
                        $courseInfo['testNum']++;*/
                    }
                    if (($dI['ex_7'] == 'PT')) {
                        $courseInfo['bookStatisfiedPercentage']["num"]++;
                        $courseInfo['bookStatisfiedPercentage']["total"] += $answerArray[0];
                        $courseInfo['hardcoreStatisfiedPercentage']["num"]++;
                        $courseInfo['hardcoreStatisfiedPercentage']["total"] += $answerArray[1];
                        $courseInfo['toolsStatisfiedPercentage']["num"]++;
                        $courseInfo['toolsStatisfiedPercentage']["total"] += $answerArray[6];
                        $courseInfo['testNum']++;
                    }
                    if(mb_strlen($answerArray[8]) > 6 && $dI['date_course_name'] != ''){
                        if($proposalList[$dI['date_id']] != null) {
                            $proposalList[$dI['date_id']] = [
                                "courseName" => $dI['date_course_name'],
                                "courseCode" => $dI['ex_6'],
                                "courseDateId" => $dI['date_id'],
                                "proposals" => [
                                    $answerArray[8],
                                ]
                            ];
                        }else{
                            $proposalList[$dI['date_id']]['proposals'][] = $answerArray[8];
                        }
                    }
                    if(abs($answerArray[9] - $arrangeCourseHours) > 1 && abs($answerArray[9] - $arrangeCourseHours) < 24){

                        if($timeDifferList[$dI['date_id']] != null){
                            $timeDifferList[$dI['date_id']]["differInfo"]["average"] = ($timeDifferList[$dI['date_id']]["differInfo"]["average"]  + $answerArray[9] ) / 2;
                        }else{
                            $timeDifferList[$dI['date_id']] = [
                                "courseName" => $dI['date_course_name'],
                                "courseCode" => $dI['ex_6'],
                                "courseDateId" => $dI['date_id'],
                                "teacherCode" => $dI['project_teacher_code'],
                                "differInfo" => [
                                    "arrange" => $arrangeCourseHours,
                                    "average" => $answerArray[9]
                                ]
                            ];
                        }

                    }

                    foreach ($answerArray as $aK => $av) {

                        if (in_array((int)$aK, $courseKey)) {
                            $courseInfo['courseStatisfiedPercentage']['num']++;
                            $courseInfo['courseStatisfiedPercentage']['total'] += $av;
                        }
                    }
                }
                if (($dI['ex_7'] == 'TT')) {
                    $courseInfo['bookStatisfiedPercentage'] = $courseInfo['bookStatisfiedPercentage']['num'] == 0 ? 0 : round(($courseInfo['bookStatisfiedPercentage']['total'] / (5 * $courseInfo['bookStatisfiedPercentage']['num'])) * 100, 2);
                    $courseInfo['hardcoreStatisfiedPercentage'] = $courseInfo['hardcoreStatisfiedPercentage']['num'] == 0 ? 0 : round(($courseInfo['hardcoreStatisfiedPercentage']['total'] / (5 * $courseInfo['hardcoreStatisfiedPercentage']['num'])) * 100, 2);
                    $courseInfo['toolsStatisfiedPercentage'] = $courseInfo['toolsStatisfiedPercentage']['num'] == 0 ? 0 : round(($courseInfo['toolsStatisfiedPercentage']['total'] / (5 * $courseInfo['toolsStatisfiedPercentage']['num'])) * 100, 2);
                    $courseInfo['courseStatisfiedPercentage'] = $courseInfo['courseStatisfiedPercentage']['num'] == 0 ? 0 : round(($courseInfo['courseStatisfiedPercentage']['total'] / (5 * $courseInfo['courseStatisfiedPercentage']['num'])) * 100, 2);

                    if ($courseInfo['testNum'] >= 5) {
                        if (array_key_exists($courseInfo['courseCode'], $resultSet['RP_2_1_2']['RP_2_1_2_1'])) {
                            $resultSet['RP_2_1_2']['RP_2_1_2_2'][$courseInfo['courseCode']]['bookStatisfiedPercentage'] += $courseInfo['bookStatisfiedPercentage'];
                            $resultSet['RP_2_1_2']['RP_2_1_2_2'][$courseInfo['courseCode']]['bookStatisfiedPercentage'] = round($resultSet['RP_2_1_2']['RP_2_1_2_1'][$courseInfo['courseCode']]['bookStatisfiedPercentage'] / 2, 2);
                            $resultSet['RP_2_1_2']['RP_2_1_2_2'][$courseInfo['courseCode']]['hardcoreStatisfiedPercentage'] += $courseInfo['hardcoreStatisfiedPercentage'];
                            $resultSet['RP_2_1_2']['RP_2_1_2_2'][$courseInfo['courseCode']]['hardcoreStatisfiedPercentage'] = round($resultSet['RP_2_1_2']['RP_2_1_2_1'][$courseInfo['courseCode']]['hardcoreStatisfiedPercentage'] / 2, 2);
                            $resultSet['RP_2_1_2']['RP_2_1_2_2'][$courseInfo['courseCode']]['toolsStatisfiedPercentage'] += $courseInfo['toolsStatisfiedPercentage'];
                            $resultSet['RP_2_1_2']['RP_2_1_2_2'][$courseInfo['courseCode']]['toolsStatisfiedPercentage'] = round($resultSet['RP_2_1_2']['RP_2_1_2_1'][$courseInfo['courseCode']]['toolsStatisfiedPercentage'] / 2, 2);
                            $resultSet['RP_2_1_2']['RP_2_1_2_2'][$courseInfo['courseCode']]['courseStatisfiedPercentage'] += $courseInfo['courseStatisfiedPercentage'];
                            $resultSet['RP_2_1_2']['RP_2_1_2_2'][$courseInfo['courseCode']]['courseStatisfiedPercentage'] = round($resultSet['RP_2_1_2']['RP_2_1_2_1'][$courseInfo['courseCode']]['courseStatisfiedPercentage'] / 2, 2);
                            $resultSet['RP_2_1_2']['RP_2_1_2_2'][$courseInfo['courseCode']]['testNum'] += $courseInfo['testNum'];

                        } else {
                            $resultSet['RP_2_1_2']['RP_2_1_2_2'][$courseInfo['courseCode']] = $courseInfo;
                        }
                        $totalTTSatisfied += $courseInfo['courseStatisfiedPercentage'];
                        $totalTTSatisfied = round($totalTTSatisfied / 2,2);
                    }
                    /*
                    $courseInfo['liveStatisfiedPercentage'] = $courseInfo['liveStatisfiedPercentage']['num'] == 0 ? 0 : round(($courseInfo['liveStatisfiedPercentage']['total'] / (5 * $courseInfo['liveStatisfiedPercentage']['num'])) * 100, 2);
                    $courseInfo['courseStatisfiedPercentage'] = $courseInfo['courseStatisfiedPercentage']['num'] == 0 ? 0 : round(($courseInfo['courseStatisfiedPercentage']['total'] / (5 * $courseInfo['courseStatisfiedPercentage']['num'])) * 100, 2);

                    if ($courseInfo['testNum'] >= 5) {
                        if (array_key_exists($courseInfo['courseCode'], $resultSet['RP_2_1_2']['RP_2_1_2_2'])) {
                            $resultSet['RP_2_1_2']['RP_2_1_2_2'][$courseInfo['courseCode']]['liveStatisfiedPercentage'] += $courseInfo['liveStatisfiedPercentage'];
                            $resultSet['RP_2_1_2']['RP_2_1_2_2'][$courseInfo['courseCode']]['liveStatisfiedPercentage'] = round($resultSet['RP_2_1_2']['RP_2_1_2_2'][$courseInfo['courseCode']]['liveStatisfiedPercentage'] / 2, 2);
                            $resultSet['RP_2_1_2']['RP_2_1_2_2'][$courseInfo['courseCode']]['courseStatisfiedPercentage'] += $courseInfo['courseStatisfiedPercentage'];
                            $resultSet['RP_2_1_2']['RP_2_1_2_2'][$courseInfo['courseCode']]['courseStatisfiedPercentage'] = round($resultSet['RP_2_1_2']['RP_2_1_2_2'][$courseInfo['courseCode']]['courseStatisfiedPercentage'] / 2, 2);
                            $resultSet['RP_2_1_2']['RP_2_1_2_2'][$courseInfo['courseCode']]['testNum'] += $courseInfo['testNum'];

                        } else {
                            $resultSet['RP_2_1_2']['RP_2_1_2_2'][$courseInfo['courseCode']] = $courseInfo;
                        }
                        $totalTTSatisfied += $courseInfo['courseStatisfiedPercentage'];
                        $totalTTSatisfied = round($totalTTSatisfied / 2,2);

                    }*/
                }
                if (($dI['ex_7'] == 'PT')) {
                    $courseInfo['bookStatisfiedPercentage'] = $courseInfo['bookStatisfiedPercentage']['num'] == 0 ? 0 : round(($courseInfo['bookStatisfiedPercentage']['total'] / (5 * $courseInfo['bookStatisfiedPercentage']['num'])) * 100, 2);
                    $courseInfo['hardcoreStatisfiedPercentage'] = $courseInfo['hardcoreStatisfiedPercentage']['num'] == 0 ? 0 : round(($courseInfo['hardcoreStatisfiedPercentage']['total'] / (5 * $courseInfo['hardcoreStatisfiedPercentage']['num'])) * 100, 2);
                    $courseInfo['toolsStatisfiedPercentage'] = $courseInfo['toolsStatisfiedPercentage']['num'] == 0 ? 0 : round(($courseInfo['toolsStatisfiedPercentage']['total'] / (5 * $courseInfo['toolsStatisfiedPercentage']['num'])) * 100, 2);
                    $courseInfo['courseStatisfiedPercentage'] = $courseInfo['courseStatisfiedPercentage']['num'] == 0 ? 0 : round(($courseInfo['courseStatisfiedPercentage']['total'] / (5 * $courseInfo['courseStatisfiedPercentage']['num'])) * 100, 2);

                    if ($courseInfo['testNum'] >= 5) {
                        if (array_key_exists($courseInfo['courseCode'], $resultSet['RP_2_1_2']['RP_2_1_2_1'])) {
                            $resultSet['RP_2_1_2']['RP_2_1_2_1'][$courseInfo['courseCode']]['bookStatisfiedPercentage'] += $courseInfo['bookStatisfiedPercentage'];
                            $resultSet['RP_2_1_2']['RP_2_1_2_1'][$courseInfo['courseCode']]['bookStatisfiedPercentage'] = round($resultSet['RP_2_1_2']['RP_2_1_2_1'][$courseInfo['courseCode']]['bookStatisfiedPercentage'] / 2, 2);
                            $resultSet['RP_2_1_2']['RP_2_1_2_1'][$courseInfo['courseCode']]['hardcoreStatisfiedPercentage'] += $courseInfo['hardcoreStatisfiedPercentage'];
                            $resultSet['RP_2_1_2']['RP_2_1_2_1'][$courseInfo['courseCode']]['hardcoreStatisfiedPercentage'] = round($resultSet['RP_2_1_2']['RP_2_1_2_1'][$courseInfo['courseCode']]['hardcoreStatisfiedPercentage'] / 2, 2);
                            $resultSet['RP_2_1_2']['RP_2_1_2_1'][$courseInfo['courseCode']]['toolsStatisfiedPercentage'] += $courseInfo['toolsStatisfiedPercentage'];
                            $resultSet['RP_2_1_2']['RP_2_1_2_1'][$courseInfo['courseCode']]['toolsStatisfiedPercentage'] = round($resultSet['RP_2_1_2']['RP_2_1_2_1'][$courseInfo['courseCode']]['toolsStatisfiedPercentage'] / 2, 2);
                            $resultSet['RP_2_1_2']['RP_2_1_2_1'][$courseInfo['courseCode']]['courseStatisfiedPercentage'] += $courseInfo['courseStatisfiedPercentage'];
                            $resultSet['RP_2_1_2']['RP_2_1_2_1'][$courseInfo['courseCode']]['courseStatisfiedPercentage'] = round($resultSet['RP_2_1_2']['RP_2_1_2_1'][$courseInfo['courseCode']]['courseStatisfiedPercentage'] / 2, 2);
                            $resultSet['RP_2_1_2']['RP_2_1_2_1'][$courseInfo['courseCode']]['testNum'] += $courseInfo['testNum'];

                        } else {
                            $resultSet['RP_2_1_2']['RP_2_1_2_1'][$courseInfo['courseCode']] = $courseInfo;
                        }
                        $totalPTSatisfied += $courseInfo['courseStatisfiedPercentage'];
                        $totalPTSatisfied = round($totalPTSatisfied / 2,2);
                    }
                }

            }
        }

        $result['RP_WORDS_LIST']["RP_2_1_2_DESCRIPTION"] = "收集理论培训评估问卷".$totalTTFormCount."份，理论课程综合满意度为".$totalTTSatisfied."%；收集实操培训评估问卷".$totalPTFormCount."份，实操培训课程满意度".$totalPTSatisfied."%。";
        $result["RP_2_1_4"] = [];
        foreach ($timeDifferList as $diffItem){
            if($diffItem['differInfo']["arrange"] == 0) continue;
            if(($diffItem['differInfo']["average"] - $diffItem['differInfo']["arrange"])/$diffItem['differInfo']["arrange"] < -0.3){
                $teacherInfo = $this->db_samc->select("*")
                    ->from("el_member_detail")
                    ->where("de_user_name = " . $diffItem['teacherCode'])
                    ->getFirst();

                $courseInfo = [
                    "courseName" => $diffItem['courseName'],
                    "courseCode" => $diffItem['courseCode'],
                    "teacherName" => $teacherInfo['de_user_realname'],
                    "arrange" => $diffItem['differInfo']["arrange"],
                    "average" => $diffItem['differInfo']["average"]
                ];
                $result["RP_2_1_4"][] = $courseInfo;
            }



        }
        $result['RP_WORDS_LIST']["RP_2_1_5_DESCRIPTION"] = [];
        foreach ($proposalList as $proposal) {
            if($proposal['courseName'] == '') continue;
            $situationStr = "《".$proposal['courseCode']."[".$proposal['courseDateId']."]".$proposal['courseName']."》有评论：".implode(";",$proposal['proposals']);
            $result['RP_WORDS_LIST']["RP_2_1_5_DESCRIPTION"][] = $situationStr;
        }

            // END RP_2_1_2_1 RP_2_1_2_2 TABLE

        // START RP_2_1_3_1 RP_2_1_3_2 TABLE
        $projectList = $this->db_samc->select("*")
            ->from("el_project")
            ->where("exim LIKE '%" . $batchId . "%'")
            ->get();

        $resultSetSub = [
            "PT" => [],
            "TT" => []
        ];
        $totalPTTeacherSatisfied = 0;
        $totalTTTeacherSatisfied = 0;
        foreach ($projectList as $pI) {
            $teacherList = $this->db_samc->select(DB::raw("DISTINCT project_teacher_code"))
                ->from("el_project_date")
                ->where("project_id = " . $pI['project_id'] . "")
                ->get();
            $teachers = [];
            foreach ($teacherList as $tI) {
                if (strlen($tI['project_teacher_code']) <= 6) {
                    $teachers[] = $tI['project_teacher_code'];
                } else {
                    $strTest1 = explode("、", $tI['project_teacher_code']);
                    if ($strTest1[0] > 6) {
                        $strTest2 = explode(",", $tI['project_teacher_code']);
                        if ($strTest2[0] > 6) {
                            $strTest3 = explode("，", $tI['project_teacher_code']);
                            if ($strTest3[0] > 6) {
                                $strTest4 = explode(" ", $tI['project_teacher_code']);
                                if ($strTest4[0] > 6) {
                                    // ?????
                                    continue;
                                } else {
                                    $teachers = array_merge($teachers, $strTest4);
                                }
                            } else {
                                $teachers = array_merge($teachers, $strTest3);
                            }
                        } else {
                            $teachers = array_merge($teachers, $strTest2);
                        }
                    } else {
                        $teachers = array_merge($teachers, $strTest1);
                    }
                }
            }
            foreach ($teachers as $tI) {
                if($tI == '') continue;
                $dateList = $this->db_samc->select("*")
                    ->from("el_project_date")
                    ->where("project_id = " . $pI['project_id'] . " and project_teacher_code LIKE '%" . $tI . "%'")
                    ->get();

                $teacherInfo = $this->db_samc->select("*")
                    ->from("el_member_detail")
                    ->where("de_user_name = " . $tI)
                    ->getFirst();

                foreach ($dateList as $dI) {
                    $formList = $this->db_samc->select("*")
                        ->from("el_forms_data")
                        ->where("date_id = " . $dI['date_id'] . "")
                        ->get();

                    $courseInfo = [
                        "courseName" => [$dI['ex_6'] . $dI['date_course_name']],
                        "teacherName" => $teacherInfo['de_user_realname']
                    ];
                    $courseInfo['professionalLevel'] = ["num" => 0, "total" => 0];
                    $courseInfo['teachingAttitude'] = ["num" => 0, "total" => 0];
                    $courseInfo['teachingAbility'] = ["num" => 0, "total" => 0];
                    $courseInfo['qualityConsciousness'] = ["num" => 0, "total" => 0];
                    $courseInfo['teacherStatisfiedPercentage'] = ["num" => 0, "total" => 0];
                    $courseInfo['expressAbility'] = ["num" => 0, "total" => 0];
                    $courseInfo['interactAbility'] = ["num" => 0, "total" => 0];
                    $courseInfo['teachingAttitude'] = ["num" => 0, "total" => 0];
                    $courseInfo['qualitySafety'] = ["num" => 0, "total" => 0];
                    $courseInfo['teacherStatisfiedPercentage'] = ["num" => 0, "total" => 0];
                    $courseInfo['testNum'] = 0;
                    /*
                    if (($dI['ex_7'] == 'TT')) {
                        $courseInfo['expressAbility'] = ["num" => 0, "total" => 0];
                        $courseInfo['interactAbility'] = ["num" => 0, "total" => 0];
                        $courseInfo['teachingAttitude'] = ["num" => 0, "total" => 0];
                        $courseInfo['qualitySafety'] = ["num" => 0, "total" => 0];
                        $courseInfo['teacherStatisfiedPercentage'] = ["num" => 0, "total" => 0];
                        $courseInfo['testNum'] = 0;
                    }
                    if (($dI['ex_7'] == 'PT')) {

                        $courseInfo['professionalLevel'] = ["num" => 0, "total" => 0];
                        $courseInfo['teachingAttitude'] = ["num" => 0, "total" => 0];
                        $courseInfo['teachingAbility'] = ["num" => 0, "total" => 0];
                        $courseInfo['qualityConsciousness'] = ["num" => 0, "total" => 0];
                        $courseInfo['teacherStatisfiedPercentage'] = ["num" => 0, "total" => 0];
                        $courseInfo['testNum'] = 0;
                    }*/

                    foreach ($formList as $fV) {
                        $answerArray = json_decode($fV['answer'], true);
                        $courseInfo['expressAbility']["num"]++;
                        $courseInfo['expressAbility']["total"] += $answerArray[2];
                        $courseInfo['interactAbility']["num"]++;
                        $courseInfo['interactAbility']["total"] += $answerArray[4];
                        $courseInfo['teachingAttitude']["num"]++;
                        $courseInfo['teachingAttitude']["total"] += $answerArray[3];
                        $courseInfo['qualitySafety']["num"]++;
                        $courseInfo['qualitySafety']["total"] += $answerArray[7];
                        $courseInfo['qualityConsciousness']["num"]++;
                        $courseInfo['qualityConsciousness']["total"] += $answerArray[5];
                        $courseInfo['professionalLevel']["num"]++;
                        $courseInfo['professionalLevel']["total"] += $answerArray[4];
                        $courseInfo['teachingAbility']["num"]++;
                        $courseInfo['teachingAbility']["total"] += $answerArray[2];
                        $courseInfo['teachingAttitude']["num"]++;
                        $courseInfo['teachingAttitude']["total"] += $answerArray[3];
                        $courseInfo['testNum']++;
                        /*
                        if (($dI['ex_7'] == 'TT')) {
                            $courseInfo['expressAbility']["num"]++;
                            $courseInfo['expressAbility']["total"] += $answerArray[2];
                            $courseInfo['interactAbility']["num"]++;
                            $courseInfo['interactAbility']["total"] += $answerArray[4];
                            $courseInfo['teachingAttitude']["num"]++;
                            $courseInfo['teachingAttitude']["total"] += $answerArray[3];
                            $courseInfo['qualitySafety']["num"]++;
                            $courseInfo['qualitySafety']["total"] += $answerArray[7];
                            $courseInfo['testNum']++;
                        }
                        if (($dI['ex_7'] == 'PT')) {
                            $courseInfo['qualityConsciousness']["num"]++;
                            $courseInfo['qualityConsciousness']["total"] += $answerArray[5];
                            $courseInfo['professionalLevel']["num"]++;
                            $courseInfo['professionalLevel']["total"] += $answerArray[4];
                            $courseInfo['teachingAbility']["num"]++;
                            $courseInfo['teachingAbility']["total"] += $answerArray[2];
                            $courseInfo['teachingAttitude']["num"]++;
                            $courseInfo['teachingAttitude']["total"] += $answerArray[3];
                            $courseInfo['testNum']++;
                        }*/

                        foreach ($answerArray as $aK => $av) {

                            if (in_array((int)$aK, $teacherKey)) {
                                $courseInfo['teacherStatisfiedPercentage']['num']++;
                                $courseInfo['teacherStatisfiedPercentage']['total'] += $av;
                            }
                        }
                    }
                    $courseInfo['expressAbility'] = $courseInfo['expressAbility']['num'] == 0 ? 0 : round(($courseInfo['expressAbility']['total'] / (5 * $courseInfo['expressAbility']['num'])) * 100, 2);
                    $courseInfo['interactAbility'] = $courseInfo['interactAbility']['num'] == 0 ? 0 : round(($courseInfo['interactAbility']['total'] / (5 * $courseInfo['interactAbility']['num'])) * 100, 2);
                    $courseInfo['teachingAttitude'] = $courseInfo['teachingAttitude']['num'] == 0 ? 0 : round(($courseInfo['teachingAttitude']['total'] / (5 * $courseInfo['teachingAttitude']['num'])) * 100, 2);
                    $courseInfo['qualitySafety'] = $courseInfo['qualitySafety']['num'] == 0 ? 0 : round(($courseInfo['qualitySafety']['total'] / (5 * $courseInfo['qualitySafety']['num'])) * 100, 2);
                    $courseInfo['professionalLevel'] = $courseInfo['professionalLevel']['num'] == 0 ? 0 : round(($courseInfo['professionalLevel']['total'] / (5 * $courseInfo['professionalLevel']['num'])) * 100, 2);
                    $courseInfo['teachingAttitude'] = $courseInfo['teachingAttitude']['num'] == 0 ? 0 : round(($courseInfo['teachingAttitude']['total'] / (5 * $courseInfo['teachingAttitude']['num'])) * 100, 2);
                    $courseInfo['teachingAbility'] = $courseInfo['teachingAbility']['num'] == 0 ? 0 : round(($courseInfo['teachingAbility']['total'] / (5 * $courseInfo['teachingAbility']['num'])) * 100, 2);
                    $courseInfo['qualityConsciousness'] = $courseInfo['qualityConsciousness']['num'] == 0 ? 0 : round(($courseInfo['qualityConsciousness']['total'] / (5 * $courseInfo['qualityConsciousness']['num'])) * 100, 2);
                    $courseInfo['teacherStatisfiedPercentage'] = $courseInfo['teacherStatisfiedPercentage']['num'] == 0 ? 0 : round(($courseInfo['teacherStatisfiedPercentage']['total'] / (5 * $courseInfo['teacherStatisfiedPercentage']['num'])) * 100, 2);

                    if ($courseInfo['testNum'] != 0) {
                        if (array_key_exists($tI, $resultSetSub)) {
                            $resultSetSub[$tI]['expressAbility'] += $courseInfo['expressAbility'];
                            $resultSetSub[$tI]['expressAbility'] = round($resultSetSub[$tI]['expressAbility'] / 2, 2);
                            $resultSetSub[$tI]['interactAbility'] += $courseInfo['interactAbility'];
                            $resultSetSub[$tI]['interactAbility'] = round($resultSetSub[$tI]['interactAbility'] / 2, 2);
                            $resultSetSub[$tI]['teachingAttitude'] += $courseInfo['teachingAttitude'];
                            $resultSetSub[$tI]['teachingAttitude'] = round($resultSetSub[$tI]['teachingAttitude'] / 2, 2);
                            $resultSetSub[$tI]['qualitySafety'] += $courseInfo['qualitySafety'];
                            $resultSetSub[$tI]['qualitySafety'] = round($resultSetSub[$tI]['qualitySafety'] / 2, 2);
                            $resultSetSub[$tI]['testNum'] += $courseInfo['testNum'];
                            $resultSetSub[$tI]['professionalLevel'] += $courseInfo['professionalLevel'];
                            $resultSetSub[$tI]['professionalLevel'] = round($resultSetSub[$tI]['professionalLevel'] / 2, 2);
                            $resultSetSub[$tI]['teachingAttitude'] += $courseInfo['teachingAttitude'];
                            $resultSetSub[$tI]['teachingAttitude'] = round($resultSetSub[$tI]['teachingAttitude'] / 2, 2);
                            $resultSetSub[$tI]['teachingAbility'] += $courseInfo['teachingAbility'];
                            $resultSetSub[$tI]['teachingAbility'] = round($resultSetSub[$tI]['teachingAbility'] / 2, 2);
                            $resultSetSub[$tI]['qualityConsciousness'] += $courseInfo['qualityConsciousness'];
                            $resultSetSub[$tI]['qualityConsciousness'] = round($resultSetSub[$tI]['qualityConsciousness'] / 2, 2);
                            $resultSetSub[$tI]['teacherStatisfiedPercentage'] += $courseInfo['teacherStatisfiedPercentage'];
                            $resultSetSub[$tI]['teacherStatisfiedPercentage'] = round($resultSetSub[$tI]['teacherStatisfiedPercentage'] / 2, 2);
                            $resultSetSub[$tI]['courseName'] = array_unique(array_merge($resultSetSub[$tI]['courseName'], $courseInfo['courseName']));

                        } else {
                            $resultSetSub[$tI] = $courseInfo;
                        }

                        $totalTTTeacherSatisfied += $courseInfo['teacherStatisfiedPercentage'];
                        $totalTTTeacherSatisfied = round($totalTTTeacherSatisfied / 2,2);

                    }



                    /*
                    if (($dI['ex_7'] == 'TT')) {
                        $courseInfo['expressAbility'] = $courseInfo['expressAbility']['num'] == 0 ? 0 : round(($courseInfo['expressAbility']['total'] / (5 * $courseInfo['expressAbility']['num'])) * 100, 2);
                        $courseInfo['interactAbility'] = $courseInfo['interactAbility']['num'] == 0 ? 0 : round(($courseInfo['interactAbility']['total'] / (5 * $courseInfo['interactAbility']['num'])) * 100, 2);
                        $courseInfo['teachingAttitude'] = $courseInfo['teachingAttitude']['num'] == 0 ? 0 : round(($courseInfo['teachingAttitude']['total'] / (5 * $courseInfo['teachingAttitude']['num'])) * 100, 2);
                        $courseInfo['qualitySafety'] = $courseInfo['qualitySafety']['num'] == 0 ? 0 : round(($courseInfo['qualitySafety']['total'] / (5 * $courseInfo['qualitySafety']['num'])) * 100, 2);
                        $courseInfo['teacherStatisfiedPercentage'] = $courseInfo['teacherStatisfiedPercentage']['num'] == 0 ? 0 : round(($courseInfo['teacherStatisfiedPercentage']['total'] / (5 * $courseInfo['teacherStatisfiedPercentage']['num'])) * 100, 2);

                        if ($courseInfo['testNum'] != 0) {
                            if (array_key_exists($tI, $resultSetSub['TT'])) {
                                $resultSetSub['TT'][$tI]['expressAbility'] += $courseInfo['expressAbility'];
                                $resultSetSub['TT'][$tI]['expressAbility'] = round($resultSetSub['TT'][$tI]['expressAbility'] / 2, 2);
                                $resultSetSub['TT'][$tI]['interactAbility'] += $courseInfo['interactAbility'];
                                $resultSetSub['TT'][$tI]['interactAbility'] = round($resultSetSub['TT'][$tI]['interactAbility'] / 2, 2);
                                $resultSetSub['TT'][$tI]['teachingAttitude'] += $courseInfo['teachingAttitude'];
                                $resultSetSub['TT'][$tI]['teachingAttitude'] = round($resultSetSub['TT'][$tI]['teachingAttitude'] / 2, 2);
                                $resultSetSub['TT'][$tI]['qualitySafety'] += $courseInfo['qualitySafety'];
                                $resultSetSub['TT'][$tI]['qualitySafety'] = round($resultSetSub['TT'][$tI]['qualitySafety'] / 2, 2);
                                $resultSetSub['TT'][$tI]['teacherStatisfiedPercentage'] += $courseInfo['teacherStatisfiedPercentage'];
                                $resultSetSub['TT'][$tI]['teacherStatisfiedPercentage'] = round($resultSetSub['TT'][$tI]['teacherStatisfiedPercentage'] / 2, 2);
                                $resultSetSub['TT'][$tI]['testNum'] += $courseInfo['testNum'];
                                $resultSetSub['TT'][$tI]['courseName'] = array_unique(array_merge($resultSetSub['TT'][$tI]['courseName'], $courseInfo['courseName']));

                            } else {
                                $resultSetSub['TT'][$tI] = $courseInfo;
                            }

                            $totalTTTeacherSatisfied += $courseInfo['teacherStatisfiedPercentage'];
                            $totalTTTeacherSatisfied = round($totalTTTeacherSatisfied / 2,2);

                        }
                    }
                    if (($dI['ex_7'] == 'PT')) {
                        $courseInfo['professionalLevel'] = $courseInfo['professionalLevel']['num'] == 0 ? 0 : round(($courseInfo['professionalLevel']['total'] / (5 * $courseInfo['professionalLevel']['num'])) * 100, 2);
                        $courseInfo['teachingAttitude'] = $courseInfo['teachingAttitude']['num'] == 0 ? 0 : round(($courseInfo['teachingAttitude']['total'] / (5 * $courseInfo['teachingAttitude']['num'])) * 100, 2);
                        $courseInfo['teachingAbility'] = $courseInfo['teachingAbility']['num'] == 0 ? 0 : round(($courseInfo['teachingAbility']['total'] / (5 * $courseInfo['teachingAbility']['num'])) * 100, 2);
                        $courseInfo['qualityConsciousness'] = $courseInfo['qualityConsciousness']['num'] == 0 ? 0 : round(($courseInfo['qualityConsciousness']['total'] / (5 * $courseInfo['qualityConsciousness']['num'])) * 100, 2);
                        $courseInfo['teacherStatisfiedPercentage'] = $courseInfo['teacherStatisfiedPercentage']['num'] == 0 ? 0 : round(($courseInfo['teacherStatisfiedPercentage']['total'] / (5 * $courseInfo['teacherStatisfiedPercentage']['num'])) * 100, 2);

                        if ($courseInfo['testNum'] != 0) {
                            if (array_key_exists($tI, $resultSetSub['PT'])) {
                                $resultSetSub['PT'][$tI]['professionalLevel'] += $courseInfo['professionalLevel'];
                                $resultSetSub['PT'][$tI]['professionalLevel'] = round($resultSetSub['PT'][$tI]['professionalLevel'] / 2, 2);
                                $resultSetSub['PT'][$tI]['teachingAttitude'] += $courseInfo['teachingAttitude'];
                                $resultSetSub['PT'][$tI]['teachingAttitude'] = round($resultSetSub['PT'][$tI]['teachingAttitude'] / 2, 2);
                                $resultSetSub['PT'][$tI]['teachingAbility'] += $courseInfo['teachingAbility'];
                                $resultSetSub['PT'][$tI]['teachingAbility'] = round($resultSetSub['PT'][$tI]['teachingAbility'] / 2, 2);
                                $resultSetSub['PT'][$tI]['qualityConsciousness'] += $courseInfo['qualityConsciousness'];
                                $resultSetSub['PT'][$tI]['qualityConsciousness'] = round($resultSetSub['PT'][$tI]['qualityConsciousness'] / 2, 2);
                                $resultSetSub['PT'][$tI]['teacherStatisfiedPercentage'] += $courseInfo['teacherStatisfiedPercentage'];
                                $resultSetSub['PT'][$tI]['teacherStatisfiedPercentage'] = round($resultSetSub['PT'][$tI]['teacherStatisfiedPercentage'] / 2, 2);
                                $resultSetSub['PT'][$tI]['testNum'] += $courseInfo['testNum'];

                                $resultSetSub['PT'][$tI]['courseName'] = array_unique(array_merge($resultSetSub['PT'][$tI]['courseName'], $courseInfo['courseName']));


                            } else {

                                $resultSetSub['PT'][$tI] = $courseInfo;
                            }

                            $totalPTTeacherSatisfied += $courseInfo['teacherStatisfiedPercentage'];
                            $totalPTTeacherSatisfied = round($totalPTTeacherSatisfied / 2,2);
                        }
                    }
                    */
                }
            }
        }
        /*
        foreach ($resultSetSub['TT'] as $k => $v) {
            $resultSetSub['TT'][$k]['courseName'] = implode("、", $resultSetSub['TT'][$k]['courseName']);
        }

        foreach ($resultSetSub['PT'] as $k => $v) {
            $resultSetSub['PT'][$k]['courseName'] = implode("、", $resultSetSub['PT'][$k]['courseName']);
        }*/
        /*foreach ($resultSetSub as $k => $v) {
            $resultSetSub[$k]['courseName'] = implode("、", $resultSetSub[$k]['courseName']);
        }*/
        //$resultSet['RP_2_1_3']['RP_2_1_3_2'] = $resultSetSub['TT'];
        $resultSet['RP_2_1_3']['RP_2_1_3_1'] = $resultSetSub;
        $result['RP_WORDS_LIST']["RP_2_1_3_DESCRIPTION"] = "本次培训教员综合满意度为".round(($totalPTTeacherSatisfied + $totalTTTeacherSatisfied) / 2)."%";


        // END RP_2_1_3_1 RP_2_1_3_2 TABLE

        // START RP_2_2_1 RP_2_2_2 TABLE
        $projectList = $this->db_samc->select("*")
            ->from("el_project")
            ->where("exim LIKE '%" . $batchId . "%'")
            ->get();

        $scoreInfo = [
            "firstPass" => 0,
            "firstTotal" => 0,
            "secondPass" => 0,
            "secondPassResult" => [],
            "secondTotal" => 0,
        ];

        $totalCourseScoreInfo = [
            "PE" => [
                "firstTotal" => 0,
                "firstPass" => 0,
            ],
            "TE" => [
                "firstTotal" => 0,
                "firstPass" => 0,
            ],
        ];

        $courseScoreInfo = [
            "PE" => [],
            "TE" => [],
        ];
        foreach ($projectList as $pI) {

            $firstScoreInfo = $this->db_samc->select(["score","date_course_name"])
                ->from("el_project_score")
                ->where("project_id = " . $pI['project_id'] . " and (score >= 80 or score = '合格')")
                ->get();
            $scoreInfo["firstPass"] += count($firstScoreInfo);
            $firstTotalInfo = $this->db_samc->select(["score","date_course_name","exim_p1"])
                ->from("el_project_score")
                ->where("project_id = " . $pI['project_id'] . "")
                ->get();
            $scoreInfo["firstTotal"] += count($firstTotalInfo);

            $scoreInfo["secondPassResult"] = array_merge( $scoreInfo["secondPassResult"],$this->db_samc->select("score")
                ->from("el_project_score")
                ->where("project_id = " . $pI['project_id'] . " and (score2 >= 80 or score2 = '合格')")
                ->get());
            $scoreInfo["secondPass"] = count($scoreInfo["secondPassResult"]);
            $secondScoreL = $this->db_samc->select("score")
                ->from("el_project_score")
                ->where("project_id = " . $pI['project_id'] . " and (score2 != '' or score2 != null)")
                ->get();
            $scoreInfo["secondTotal"] += count($secondScoreL);

            $typeCode = substr($firstTotalInfo[0]['exim_p1'],-2,2);
            $totalCourseScoreInfo[$typeCode]['firstPass'] += count($firstScoreInfo);
            $totalCourseScoreInfo[$typeCode]['firstTotal'] += count($firstTotalInfo);

            if($firstTotalInfo[0]['date_course_name'] != null){

                $courseScoreInfo[$typeCode][] = [
                    "courseName" => $firstTotalInfo[0]['date_course_name'],
                    "totalPeople" => count($firstTotalInfo),
                    "passPercent" => count($firstTotalInfo) == 0 ? 0.00 : round(count($firstScoreInfo) / count($firstTotalInfo) * 100,2)
                ];
            }


        }


        $resultSet['RP_2_2_2'] = $courseScoreInfo;
        $resultSet['RP_2_2_1'] = [
            [
                "type" => "初考",
                "total" => $scoreInfo['firstTotal'],
                "passPercentage" => $scoreInfo["firstTotal"] == 0 ? 0.00 : round($scoreInfo["firstPass"] / $scoreInfo["firstTotal"] * 100, 2)
            ],
            [
                "type" => "补考",
                "total" => $scoreInfo['secondTotal'],
                //"debug" => $scoreInfo['secondPassResult'],
                "passPercentage" => $scoreInfo["secondPass"] == 0 ? 0.00 : round($scoreInfo["secondPass"] / $scoreInfo["secondTotal"] * 100, 2)
            ],
        ];

        $TEPassPercentage = $totalCourseScoreInfo['TE']['firstTotal'] == 0 ? 0.00 : round($totalCourseScoreInfo['TE']['firstPass'] / $totalCourseScoreInfo['TE']['firstTotal'] * 100 , 2);
        $TEDidntPassCount = $totalCourseScoreInfo['TE']['firstTotal'] - $totalCourseScoreInfo['TE']['firstPass'];
        $PEPassPercentage = $totalCourseScoreInfo['PE']['firstTotal'] == 0 ? 0.00 : round($totalCourseScoreInfo['PE']['firstPass'] / $totalCourseScoreInfo['PE']['firstTotal'] * 100 , 2);
        $PEDidntPassCount = $totalCourseScoreInfo['PE']['firstTotal'] - $totalCourseScoreInfo['PE']['firstPass'];

        $result['RP_WORDS_LIST']["RP_2_2_2_DESCRIPTION"] = "本批次参加理论考试".$totalCourseScoreInfo['TE']['firstTotal']."人次，初考通过率为".$TEPassPercentage."%，".$TEDidntPassCount."人次未通过；";
        $result['RP_WORDS_LIST']["RP_2_2_2_DESCRIPTION"] .="实操考试".$totalCourseScoreInfo['PE']['firstTotal']."人次，初考通过率为".$PEPassPercentage."%，".$PEDidntPassCount."人次未通过。";

        // END RP_2_2_1 RP_2_2_2 TABLE

        // START RP_2_3_1
        $projectList = $this->db_samc->select("*")
            ->from("el_project")
            ->where("exim LIKE '%" . $batchId . "%'")
            ->get();

        $nineCon = [
            "安全意识" => 0,
            "按章作业意识" => 0,
            "产品保护意识" => 0,
            "多余物控制意识" => 0,
            "工具管控意识" => 0,
            "交接意识" => 0,
            "举手意识" => 0,
            "自检互检意识" => 0
        ];

        $nineConSecondary = [
            "安全意识" => [],
            "按章作业意识" => [],
            "产品保护意识" => [],
            "多余物控制意识" => [],
            "工具管控意识" => [],
            "交接意识" => [],
            "举手意识" => [],
            "自检互检意识" => []
        ];

        foreach ($projectList as $pI) {

            $nineList = $this->db_samc->select("*")
                ->from("el_project_member_action_log")
                ->where("project_id = " . $pI['project_id'] . "")
                ->get();

            foreach ($nineList as $nV){
                $nineCon[$nV['option1']] ++;

                if($nineConSecondary[$nV['option1']][$nV['option2']] == null){
                    $nineConSecondary[$nV['option1']][$nV['option2']] = 1;
                }else{
                    $nineConSecondary[$nV['option1']][$nV['option2']] ++ ;
                }
            }
        }

        $resultSet['RP_2_3_1'] = $nineCon;

        $nineConSecondaryResult = [];
        foreach ($nineConSecondary as $nK => $nV){
            foreach ($nV as $k => $item){
                $nineConSecondaryResult[] = [
                    "typeF" => $nK,
                    "typeS" => $k,
                    "peopleNum" => $item
                ];
            }

        }

        $resultSet['RP_2_3_2'] = $nineConSecondaryResult;
        arsort($nineCon);
        $nineConSORT = array_flip($nineCon);
        $result['RP_WORDS_LIST']["RP_2_3_1_DESCRIPTION"] .="本次培训九大素养评估方面共扣分".array_sum($nineCon)."人次，其中".array_shift($nineConSORT)."扣分情况最多，其次是".array_shift($nineConSORT)."和".array_shift($nineConSORT)."。";




        return (new ResponseUtils())->getResponse(array_merge($result,$resultSet), 200, '');
    }


    private function getLast6BatchScore($currentBatch)
    {
        $batchCode = $this->db_samc->select(DB::raw("ex_2,ex_3"))
            ->from(DB::raw("el_project"))
            ->where(("ex_1 = '" . substr($currentBatch, 0, 1) . "' AND exim < '" . $currentBatch . "'  and ex_3 != 00 and ex_3 != 99"))
            ->orWhere(("exim LIKE '%" . $currentBatch . "%'"))
            ->groupBy(DB::raw("ex_2,ex_3"))
            ->orderBy(["ex_2" => "DESC", "ex_3" => "DESC"])
            ->limit(0, 6)
            ->get();
        $resultSet = [];
        foreach ($batchCode as $bI) {
            $currentBatchCode = substr($currentBatch, 0, 1) . $bI['ex_2'] . $bI['ex_3'];
            $projectList = $this->db_samc->select("*")
                ->from("el_project")
                ->where("exim LIKE '%" . $currentBatchCode . "%'")
                ->get();

            $scoreInfo = [
                "firstPass" => 0,
                "firstTotal" => 0,
                "secondPass" => 0,
                "secondPassResult" => [],
                "secondTotal" => 0,
            ];
            foreach ($projectList as $pI) {

                $scoreInfo["firstPass"] += count($this->db_samc->select("score")
                    ->from("el_project_score")
                    ->where("project_id = " . $pI['project_id'] . " and (score >= 80 or score = '合格')")
                    ->get());
                $scoreInfo["firstTotal"] += count($this->db_samc->select("score")
                    ->from("el_project_score")
                    ->where("project_id = " . $pI['project_id'] . "")
                    ->get());
                $scoreInfo["secondPassResult"] = $this->db_samc->select("score")
                    ->from("el_project_score")
                    ->where("project_id = " . $pI['project_id'] . " and (score2 >= 80 or score2 = '合格')")
                    ->get();
                $scoreInfo["secondPass"] += count($scoreInfo["secondPassResult"]);
                $scoreInfo["secondTotal"] += count($this->db_samc->select("score")
                    ->from("el_project_score")
                    ->where("project_id = " . $pI['project_id'] . " and (score2 != '' or score2 != null)")
                    ->get());
            }


            $resultSet[$currentBatchCode] = [
                "name" => $bI['ex_2'] . "年第" . $bI['ex_3'] . "批",
                "score" => [
                    "firstScore" => $scoreInfo["firstTotal"] == 0 ? 0.00 : round($scoreInfo["firstPass"] / $scoreInfo["firstTotal"] * 100, 2),
                    "secondScore" => $scoreInfo["secondPass"] == 0 ? 0.00 : round($scoreInfo["secondPass"] / $scoreInfo["secondTotal"] * 100, 2),
                ]
            ];

        }
        return array_reverse($resultSet);
    }

    private function getLast6BatchComment($currentBatch)
    {
        $batchCode = $this->db_samc->select(DB::raw("ex_2,ex_3"))
            ->from(DB::raw("el_project"))
            ->where(("ex_1 = '" . substr($currentBatch, 0, 1) . "' AND exim < '" . $currentBatch . "'  and ex_3 != 00 and ex_3 != 99"))
            ->orWhere(("exim LIKE '%" . $currentBatch . "%'"))
            ->groupBy(DB::raw("ex_2,ex_3"))
            ->orderBy(["ex_2" => "DESC", "ex_3" => "DESC"])
            ->limit(0, 6)
            ->get();
        $resultSet = [];
        foreach ($batchCode as $bI) {
            $currentBatchCode = substr($currentBatch, 0, 1) . $bI['ex_2'] . $bI['ex_3'];
            $projectList = $this->db_samc->select("*")
                ->from("el_project")
                ->where("exim LIKE '%" . $currentBatchCode . "%'")
                ->get();
            $courseKey = [0, 1, 6, 7];
            $teacherKey = [2, 3, 4, 5];
            $courseScoreInfo = [
                "total" => 0,
                "num" => 0,
            ];
            $teacherScoreInfo = [
                "total" => 0,
                "num" => 0,
            ];
            foreach ($projectList as $pI) {
                $dateList = $this->db_samc->select("*")
                    ->from("el_project_date")
                    ->where("project_id = " . $pI['project_id'] . "")
                    ->get();
                foreach ($dateList as $dI) {
                    $formList = $this->db_samc->select("*")
                        ->from("el_forms_data")
                        ->where("date_id = " . $dI['date_id'] . "")
                        ->get();
                    foreach ($formList as $fV) {
                        $answerArray = json_decode($fV['answer'], true);

                        foreach ($answerArray as $aK => $av) {
                            if (in_array((int)$aK, $courseKey)) {
                                $courseScoreInfo['num']++;
                                $courseScoreInfo['total'] += $av;
                            }
                            if (in_array((int)$aK, $teacherKey)) {
                                $teacherScoreInfo['num']++;
                                $teacherScoreInfo['total'] += $av;
                            }
                        }
                    }
                }
            }

            $teacherScoreInfo['avg'] = $teacherScoreInfo['num'] == 0 ? 0.00 :round(($teacherScoreInfo['total'] / (5 * $teacherScoreInfo['num'])) * 100, 2);
            $courseScoreInfo['avg'] = $courseScoreInfo['num'] == 0 ? 0.00 :round(($courseScoreInfo['total'] / (5 * $courseScoreInfo['num'])) * 100, 2);

            $resultSet[$currentBatchCode] = [
                "name" => $bI['ex_2'] . "年第" . $bI['ex_3'] . "批",
                "score" => [
                    "teacher" => $teacherScoreInfo,
                    "course" => $courseScoreInfo
                ]
            ];

        }
        return array_reverse($resultSet);
    }


}