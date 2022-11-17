<?php


namespace App\Utils;

/**
 * Class ResponseUtils
 * @package App\Utils
 *
 * Response控制器
 */
class ResponseUtils
{
    /**
     * 获取请求返回值函数
     * 此方法实现了对于返回值的统一格式
     *
     * @param $data
     * @param int $code
     * @param string $error
     * @param string $message
     * @param bool $custom
     * @return array
     */
    public function getResponse($data, $code = 200, $error = '', $message = '',$custom = false){
        if($custom == false){
            return array(
                'data'  => $data,
                'code'  => $code,
                'error' => $error,
                'message' => $message
            ) ;
        }else{
            return $data;
        }
    }
}