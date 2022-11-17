<?php

namespace App\Controllers\CourseController;

use App\Controllers\AuthenticationController\TokenUtils;
use App\Utils\ResponseUtils;
use PhpBoot\DB\DB;
use PhpBoot\DI\Traits\EnableDIAnnotations;

/**
 * Class VideoRecordsController
 * @package App\Controllers\CourseController
 *
 * @path /video/records/
 *
 */
class VideoRecordsController
{
    use EnableDIAnnotations;

    //启用通过@inject标记注入依赖

    /**
     * @inject
     * @var DB
     */
    private $db;

    /**
     *
     * @route GET /like/{videoHash}
     * @return array
     */
    public function likeVideo($videoHash){
        $userInfo = (new TokenUtils())->getUUIDByToken($this->db);
        $video = $this->db->select('videos.*')
            ->from('videos')
            ->where("videoHash = '".$videoHash."'")
            //->limit(($page - 1) * 30,30)
            ->getFirst();

        if(!$video)  return (new ResponseUtils())->getResponse(false,-4001,"找不到视频");

        if(json_decode($video['likedUser']) == null){
            $this->db->update('videos')
                ->set(["likedUser" => "[".$userInfo['workId']."]"])
                ->where("videoHash = '".$videoHash."'")
                //->limit(($page - 1) * 30,30)
                ->exec();
        }else{
            $newLiked = json_decode($video['likedUser']);
            $newLiked[] = $userInfo['workId'];
            $this->db->update('videos')
                ->set(['likedUser' => json_encode($newLiked)])
                ->where("videoHash = '".$videoHash."'")
                //->limit(($page - 1) * 30,30)
                ->exec();
        }

        return (new ResponseUtils())->getResponse('success');

    }

    /**
     *
     * @route GET /dislike/{videoHash}
     * @return array
     */
    public function dislikeVideo($videoHash){
        $userInfo = (new TokenUtils())->getUUIDByToken($this->db);
        $video = $this->db->select('videos.*')
            ->from('videos')
            ->where("videoHash = '".$videoHash."'")
            //->limit(($page - 1) * 30,30)
            ->getFirst();

        if(!$video)  return (new ResponseUtils())->getResponse(false,-4001,"找不到视频");

        if(json_decode($video['likedUser']) == null){
            return (new ResponseUtils())->getResponse('success');
        }else{
            $newLiked = json_decode($video['likedUser']);
            if(in_array($userInfo['workId'],$newLiked)){
                $key = array_search($userInfo['workId'],$newLiked);
                unset($newLiked[$key]);
                $this->db->update('videos')
                    ->set(['likedUser' => json_encode($newLiked)])
                    ->where("videoHash = '".$videoHash."'")
                    //->limit(($page - 1) * 30,30)
                    ->exec();

            }else{
                return (new ResponseUtils())->getResponse('success');
            }

        }

        return (new ResponseUtils())->getResponse('success');

    }

    /**
     *
     * @route GET /list
     * @return array
     */
    public function getAllVideoList($keyword){
        $userInfo = (new TokenUtils())->getUUIDByToken($this->db);
        $videoList = $this->db->select('v.*')
            ->from(DB::raw('videos as v'))
            ->leftJoin(DB::raw("courses as c"))
            ->on("c.courseId = v.linkedCourseId")
            ->where("c.projectName LIKE '%".$keyword."%' or v.linkedTeacherId = '".$keyword."'")
            //->limit(($page - 1) * 30,30)
            ->get();

        foreach ($videoList as $key => $video){
            if($likeArray = json_decode($video['likedUser'],true)){
                if(in_array($userInfo['workId'],$likeArray)){
                    $videoList[$key]['isLiked'] = true;
                }else{
                    $videoList[$key]['isLiked'] = false;
                }
            }else{
                $videoList[$key]['isLiked'] = false;
            }
        }

        return (new ResponseUtils())->getResponse($videoList);

    }

    /**
     *
     * @route GET /info
     * @return array
     */
    public function getVideoInfo($videoHash){

        $video = $this->db->select('videos.*')
            ->from('videos')
            ->where("videoHash = '".$videoHash."'")
            //->limit(($page - 1) * 30,30)
            ->getFirst();

        $videoTeacherIds = explode(",",$video['linkedTeacherId']);
        $teacherInfo = [];

        foreach($videoTeacherIds as $tV){
            $teacher = $this->db->select('*')
                ->from('users')
                ->where("workId = '".$tV."'")
                //->limit(($page - 1) * 30,30)
                ->getFirst();
            array_push($teacherInfo,$teacher['realName']);
        }

        if($video['linkedCourseId']){
            $course = $this->db->select('*')
                ->from('courses')
                ->where("courseId = '".$video['linkedCourseId']."'")
                //->limit(($page - 1) * 30,30)
                ->getFirst();
        }

        return (new ResponseUtils())->getResponse(["videoInfo" => $video,"courseInfo" => $course,"teacherInfo" => implode(",",$teacherInfo)]);

    }
}