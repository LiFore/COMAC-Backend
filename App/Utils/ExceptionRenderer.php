<?php
namespace App\Utils;

use Exception;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ExceptionRenderer
{
    /**
     * @param Exception $e
     * @return Response
     */
    protected $isDebug = false;

    public function render(Exception $e)
    {
        $message = json_encode(
            ['code' => -4001 , 'error' => get_class($e), 'message' => $e->getMessage() , 'data' => null,"location"=>$e->getTrace()],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
        $this->isDebug or $message = json_encode(
            ['code' => -4001 , 'error' => get_class($e), 'message' => $e->getMessage() , 'data' => null],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
        $messages = [
            "Error.      >> Error:    ".get_class($e)."\n",
            "            >> Message:  ".$e->getMessage()."\n",
            "            >> Trace:    ".json_encode($e->getTrace())."\n"
        ];
        $this->LogRequest($messages);
        if($e instanceof HttpException){
            return new Response(
                $message,
                $e->getStatusCode(),
                ['Content-Type'=>'application/json']
            );
        } if($e instanceof InvalidArgumentException){
        return new Response($message, Response::HTTP_BAD_REQUEST, ['Content-Type'=>'application/json']);
    }else{
        return new Response($message, Response::HTTP_INTERNAL_SERVER_ERROR, ['Content-Type'=>'application/json']);
    }
    }

    public function LogRequest( $message, $force = false ) {
        $time = date( 'Y-m-d H:i:s' );
        $message[0] = "\r\n[".$time . ']'. $message[0] ;
        for ($i = 1; $i < count($message);$i ++){
            $message[$i] = "\r\n                     ". $message[$i];
        }
        $server_date = date( 'Y_m_d' );
        $filename = '[Error Log]'.$server_date . '.log';
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