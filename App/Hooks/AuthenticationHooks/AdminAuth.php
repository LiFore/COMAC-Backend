<?php


namespace App\Hooks\AuthenticationHooks;

use App\Utils\HeaderUtils;
use App\Utils\TimeUtils;
use PhpBoot\Controller\HookInterface;
use PhpBoot\DB\DB;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class AdminAuth implements HookInterface
{

    use \PhpBoot\DI\Traits\EnableDIAnnotations; //启用通过@inject标记注入依赖

    /**
     * @inject
     * @var DB
     */
    private $db;

    /**
     * @param Request $request
     * @param callable $next
     * @return Response
     */
    public function handle(Request $request, callable $next)
    {
        $auth = HeaderUtils::getTokens($_SERVER['HTTP_AUTHORIZATION']);
        $this->clientToken = $auth['clientToken'];
        $this->accessToken = $auth['accessToken'];
        if(!$this->accessToken || !$this->clientToken){
            $messages = [
                "Auth Info   >> Auth:          ".$auth."\n",
                "            >> AccessToken:   ".$this->accessToken."\n",
                "            >> ClientToken:   ".$this->clientToken."\n",
                "            >> Type:          AdminAuth\n",
                "            >> UserName:      NONE\n",
                "            >> Status :       NO ACCESSTOKEN OR CLIENTTOKEN!"
            ];
            $this->LogAuth($messages);
            \PhpBoot\abort(new UnauthorizedHttpException('Basic realm="LiAPI Authencation"', "Invalid accessToken or clientToken!"));
        }
        $userResult = $this->db->select('*')
            ->from('users')
            ->where("clientToken = '".$this->clientToken."'")->get();
        $userResult = $userResult[0];
        if($userResult['accessToken'] != $this->accessToken){
            $messages = [
                "Auth Info   >> Auth:          ".$auth."\n",
                "            >> AccessToken:   ".$this->accessToken."\n",
                "            >> ClientToken:   ".$this->clientToken."\n",
                "            >> Type:          AdminAuth\n",
                "            >> UserName:      ".$userResult['username']."\n",
                "            >> Status :       ERROR! Invalid accessToken!"
            ];
            $this->LogAuth($messages);
            \PhpBoot\abort(new UnauthorizedHttpException('Basic realm="LiAPI Authencation"', "Invalid accessToken!"));
        }
        if($userResult['permission'] <= 50){
            $messages = [
                "Auth Info   >> Auth:          ".$auth."\n",
                "            >> AccessToken:   ".$this->accessToken."\n",
                "            >> ClientToken:   ".$this->clientToken."\n",
                "            >> Type:          AdminAuth\n",
                "            >> UserName:      ".$userResult['username']."\n",
                "            >> Status :       ERROR! No permission!"
            ];
            $this->LogAuth($messages);
            \PhpBoot\abort(new UnauthorizedHttpException('Basic realm="LiAPI Authencation"', "No permission!"));
        }
        if($userResult['tokenExpire'] < TimeUtils::getUNIXTime()){
            $this->db->update('users')
                ->set([
                    'accessToken'=> '',
                    'loginAt'=> TimeUtils::getNormalTime(),
                    'clientToken' => ''
                ])
                ->where(['uniqueId'=>$userResult['uniqueId']])
                ->exec();
            \PhpBoot\abort(new UnauthorizedHttpException('Basic realm="LiAPI Authencation"', "AccessToken expired!"));
        }
        $messages = [
            "Auth Info   >> Auth:          ".$auth."\n",
            "            >> AccessToken:   ".$this->accessToken."\n",
            "            >> ClientToken:   ".$this->clientToken."\n",
            "            >> Type:          AdminAuth\n",
            "            >> UserName:      ".$userResult['username']."\n",
            "            >> Status :       Success"
        ];
        $this->LogAuth($messages);

        return $next($request);
    }

    public function LogAuth( $message, $force = false ) {
        $time = date( 'Y-m-d H:i:s' );
        $message[0] = "\r\n[".$time . ']'. $message[0] ;
        for ($i = 1; $i < count($message);$i ++){
            $message[$i] = "\r\n                     ". $message[$i];
        }
        $server_date = date( 'Y_m_d' );
        $filename = '[AdminAuth Log]'.$server_date . '.log';
        $file_path = dirname(getcwd()).'\\logFiles\\Log_'.$server_date.'\\' . $filename;
        $error_content = $message;
        //print_r($error_content);
        //$error_content = '错误的数据库，不可以链接';
        $file = dirname(getcwd()) .'\\logFiles\\Log_'.$server_date.'';
        //设置文件保存目录

        //建立文件夹
        if ( !file_exists( $file ) ) {
            if ( !mkdir( $file, 0777 ) ) {
                //默认的 mode 是 0777，意味着最大可能的访问权
                die( 'upload files directory does not exist and creation failed'.$file );
            }
        }

        //建立txt日期文件
        if ( !file_exists( $file_path ) ) {

            //echo '建立日期文件';
            fopen( $file_path, 'w+' );

            //首先要确定文件存在并且可写
            if ( is_writable( $file_path ) ) {
                //使用添加模式打开$filename，文件指针将会在文件的开头
                if ( !$handle = fopen( $file_path, 'a' ) ) {
                    echo "不能打开文件 $filename";
                    exit;
                }

                //将$somecontent写入到我们打开的文件中。
                foreach ($message as $key => $val){
                    if ( !fwrite( $handle, $val) ) {
                        echo "不能写入到文件 $filename";
                        exit;
                    }
                }


                //echo "文件 $filename 写入成功";

                //echo '——错误记录被保存!';

                //关闭文件
                fclose( $handle );
            } else {
                echo "文件 $filename 不可写";
            }

        } else {
            //首先要确定文件存在并且可写
            if ( is_writable( $file_path ) ) {
                //使用添加模式打开$filename，文件指针将会在文件的开头
                if ( !$handle = fopen( $file_path, 'a' ) ) {
                    echo "不能打开文件 $filename";
                    exit;
                }

                //将$somecontent写入到我们打开的文件中。
                foreach ($message as $key => $val){
                    if ( !fwrite( $handle, $val) ) {
                        echo "不能写入到文件 $filename";
                        exit;
                    }
                }


                //echo "文件 $filename 写入成功";
                //echo '——错误记录被保存!';

                //关闭文件
                fclose( $handle );
            } else {
                echo "文件 $filename 不可写";
            }
        }
    }

    /**
     * @var string
     */
    public $clientToken;
    /**
     * @var string
     */
    public $accessToken;
}