<?php


namespace App\Controllers\PxSamcController;


class SamcUtils
{

    private static $generalCookie = "PHPSESSID=ufan2ipmvvn5bhvhflvnqtjon2; EDUV4_ONLY_LOGIN_KEY=aEorHGWCioyEGzVEsqWVVOAAIDyE-Lv2LHrPcjypxuCmtAOlKf_lzv6JINURWoofr-Z2VqCKiEnslld7lp4LdnfXwAWXegzZ9m5qAdlsGsUAHJROy4bm5gMq3Xl5EN1s4XSt47dz_MUyVXQZoorH3MliI_p3jUQmwlRHUBoq4crvPU6VOnryBayYgk2iUue5UeEVnT-Cnl1gQ-VCeNYhHA%3D%3D";
    /*
     * 内部方法
     * 实现了对于SAMC平台的请求
     */
    public static function sendRequest($app,$mod,$act,$user_code,$additional = ''){
        $samcGatewayUrl = 'http://px.samc.cc/index.php?app='.$app.'&mod='.$mod.'&act='.$act.'&user_code='.$user_code.'&testuser=6308&pass=1qazCDE@5tgb';

        $samcGatewayCh = curl_init();
        curl_setopt($samcGatewayCh, CURLOPT_URL, $samcGatewayUrl);
        curl_setopt($samcGatewayCh, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($samcGatewayCh, CURLOPT_HEADER, 0);
        curl_setopt($samcGatewayCh, CURLOPT_COOKIE , SamcUtils::$generalCookie );
        curl_setopt($samcGatewayCh, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
        curl_setopt($samcGatewayCh, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
        $samcGatewayRet = curl_exec($samcGatewayCh);
        curl_close($samcGatewayCh);
        $samcGatewayRet = json_decode($samcGatewayRet,true);
        return $samcGatewayRet;
    }

    /*
    * 内部方法
    * 实现了对于SAMC平台的请求
    */
    public static function sendRequestCourseM($app,$mod,$act,$user_code,$month){
        $samcGatewayUrl = 'http://px.samc.cc/index.php?app='.$app.'&mod='.$mod.'&act='.$act.'&user_code='.$user_code.'&yearmonth='.$month.'&testuser=6308&pass=1qazCDE@5tgb';

        $samcGatewayCh = curl_init();
        curl_setopt($samcGatewayCh, CURLOPT_URL, $samcGatewayUrl);
        curl_setopt($samcGatewayCh, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($samcGatewayCh, CURLOPT_HEADER, 0);
        curl_setopt ($samcGatewayCh, CURLOPT_COOKIE , SamcUtils::$generalCookie );
        curl_setopt($samcGatewayCh, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
        curl_setopt($samcGatewayCh, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
        $samcGatewayRet = curl_exec($samcGatewayCh);
        curl_close($samcGatewayCh);
        $samcGatewayRet = json_decode($samcGatewayRet,true);
        return $samcGatewayRet;
    }

    /*
    * 内部方法
    * 实现了对于SAMC平台的请求
    */
    public static function sendRequestCourseD($app,$mod,$act,$user_code,$day){
        $samcGatewayUrl = 'http://px.samc.cc/index.php?app='.$app.'&mod='.$mod.'&act='.$act.'&user_code='.$user_code.'&oneday='.$day.'&testuser=6308&pass=1qazCDE@5tgb';

        $samcGatewayCh = curl_init();
        curl_setopt($samcGatewayCh, CURLOPT_URL, $samcGatewayUrl);
        curl_setopt($samcGatewayCh, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($samcGatewayCh, CURLOPT_HEADER, 0);
        curl_setopt ($samcGatewayCh, CURLOPT_COOKIE , SamcUtils::$generalCookie );
        curl_setopt($samcGatewayCh, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
        curl_setopt($samcGatewayCh, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
        $samcGatewayRet = curl_exec($samcGatewayCh);
        curl_close($samcGatewayCh);
        $samcGatewayRet = json_decode($samcGatewayRet,true);
        return $samcGatewayRet;
    }

    /*
    * 内部方法
    * 实现了对于SAMC平台的请求
    */
    public static function sendRequestCourseUserList($app,$mod,$act,$project_id){
        $samcGatewayUrl = 'http://px.samc.cc/index.php?app='.$app.'&mod='.$mod.'&act='.$act.'&project_id='.$project_id.'&testuser=6308&pass=1qazCDE@5tgb';

        $samcGatewayCh = curl_init();
        curl_setopt($samcGatewayCh, CURLOPT_URL, $samcGatewayUrl);
        curl_setopt($samcGatewayCh, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($samcGatewayCh, CURLOPT_HEADER, 0);
        curl_setopt ($samcGatewayCh, CURLOPT_COOKIE , SamcUtils::$generalCookie );
        curl_setopt($samcGatewayCh, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
        curl_setopt($samcGatewayCh, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
        $samcGatewayRet = curl_exec($samcGatewayCh);
        curl_close($samcGatewayCh);
        $samcGatewayRet = json_decode($samcGatewayRet,true);
        return $samcGatewayRet;
    }

    /*
    * 内部方法
    * 实现了对于SAMC平台的请求
    */
    public static function sendRequestAllType($app){
        $samcGatewayUrl = 'http://px.samc.cc/api.php?'.$app;

        $samcGatewayCh = curl_init();

        curl_setopt($samcGatewayCh, CURLOPT_URL, $samcGatewayUrl);
        curl_setopt($samcGatewayCh, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($samcGatewayCh, CURLOPT_HEADER, 0);
        curl_setopt ($samcGatewayCh, CURLOPT_COOKIE , SamcUtils::$generalCookie );
        curl_setopt($samcGatewayCh, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
        curl_setopt($samcGatewayCh, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法是否存在
        $samcGatewayRet = curl_exec($samcGatewayCh);

        curl_close($samcGatewayCh);
        $samcGatewayRet = json_decode($samcGatewayRet,true);
        return $samcGatewayRet;
    }
}