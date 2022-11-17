<?php


namespace App\Controllers\AuthenticationController;

/**
 * Class AuthenticationUtils
 * @package App\Controllers\AuthenticationController
 *
 * Authentication附件
 */
class AuthenticationUtils
{

    /**
     * 此方法实现了对于用户头像的生成
     *
     * @return string Base64格式
     */
    public static function geneNewHead(){
        $im = imagecreatetruecolor(600,600); //生成真彩图片
        $black = imagecolorallocate($im,0,230,250);//设置颜色
        $B = imagecolorallocate($im,230,230,250);//设置颜色
        imagefill($im,10,1,$black) ;
        $Iv = 49; //px 位移像素定义50

        for ($i=0; $i <300 ; $i++) {
            for ($y=0; $y < 600; $y++) {
                //渲染
                $ad = rand(10,50); //随机
                if ($ad%3==0) {
                    // $array[] //6 个  300
                    for ($xx=$i; $xx <$i+50; $xx++) {
                        for ($yy=$y; $yy < $y+100; $yy++) { //$i 机器人像
                            imagesetpixel($im,$xx,$yy, $B);//绘制图案
                        }
                    }
                    $is = ((300-$i)+300)-50; //计算偏移
                    for ($xx=$is; $xx <$is+50; $xx++) {
                        for ($yy=$y; $yy < $y+100; $yy++) {
                            imagesetpixel($im,$xx,$yy, $B);//绘制图案
                        }
                    }
                }
                $y+=$Iv;
            }
            $i+=$Iv;
        }

        $filename = dirname(getcwd())."/temp".rand(12409781,109238924870).".png";
        imagepng($im, $filename, 9);

        $base64 = chunk_split(base64_encode(file_get_contents($filename)));
        // 输出
        $encode = $base64 ;

        unlink($filename);

        imagedestroy($im);//释放内存

        return $encode;
    }

    /**
     * 此方法实现了对于用户密码的加密
     *
     * @param $password
     *
     * @return string 加密后的SHA256密码
     */
    public static function hashPasswordSHA256WithNewSALT($password){
        $intermediateSalt = md5(uniqid(rand(), true));
        $salt = substr($intermediateSalt, 0, 6);
        return hash("sha256", $password ).$salt;
    }

    /**
     * 此方法实现了对于用户密码的校验
     *
     * @param $inPassword string 传入的 Password
     * @param $storedPassword string 数据库的 Password
     * @return boolean 是否校验成功
     */
    public static function verfiyPassword($inPassword,$storedPassword){
        $salt = substr($storedPassword, -6);
        $inPassword = hash("sha256", $inPassword ). $salt;
        if($inPassword == $storedPassword){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 此方法实现了生成新的Accesstoken
     *
     * @return string
     */
    public static function geneNewAccessToken(){
        $newAccessToken = md5(uniqid(microtime(true),true));
        return $newAccessToken;
    }

}