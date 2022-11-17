<?php


namespace App\Hooks\LoggerHooks;

use PhpBoot\Controller\HookInterface;
use PhpBoot\DB\DB;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class systemLog implements HookInterface
{

    /**
     * @param Request $request
     * @param callable $next
     * @return Response
     */
    public function handle(Request $request, callable $next)
    {
        $auth = $request->headers->get('Authorization');
        $messages = [
            "New request >> Uri:      ".$request->getRequestUri()."\n",
            "            >> Auth:     ".$auth."\n",
            "            >> Method:   ".$request->getMethod()."\n",
            "            >> Host:     ".$request->getHttpHost()."\n",
            "            >> Client:   ".$request->getClientIp()."\n",
            "            >> Query:    ".$request->getQueryString()."\n",
            "            >> PathInfo: ".$request->getPathInfo()."\n"
        ];
        $this->LogRequest($messages);

        return $next($request);
    }


    public function LogRequest( $message, $force = false ) {
        $time = date( 'Y-m-d H:i:s' );
        $message[0] = "\r\n[".$time . ']'. $message[0] ;
        for ($i = 1; $i < count($message);$i ++){
            $message[$i] = "\r\n                     ". $message[$i];
        }
        $server_date = date( 'Y_m_d' );
        $filename = '[Request Log]'.$server_date . '.log';
        $file_path = dirname(getcwd()).'/logFiles/Log_'.$server_date.'/' . $filename;
        $error_content = $message;
        //print_r($error_content);
        //$error_content = '错误的数据库，不可以链接';
        $file = dirname(getcwd()) .'/logFiles/Log_'.$server_date.'';
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

}