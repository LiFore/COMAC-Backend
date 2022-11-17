<?php
use PhpBoot\Docgen\Swagger\Swagger;
use PhpBoot\Docgen\Swagger\SwaggerProvider;
use PhpBoot\Application;

error_reporting(0);

ini_set('date.timezone','Asia/Shanghai');

require __DIR__.'/../vendor/autoload.php';

header("Access-Control-Allow-Origin:".$_SERVER['HTTP_ORIGIN']); // 允许任意域名发起的跨域请求
header('Access-Control-Allow-Headers: X-Requested-With,X_Requested_With,Content-Type,authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT,DELETE,OPTIONS,PATCH');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

if( $_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
    exit;
}

// 加载配置
$app = Application::createByDefault(
    __DIR__.'/../config/config.php'
);
define("HOST",$app->get('host'));

define("BUFFER_SCORE_LIST_NAME","BUFFER_SCORE_LIST");
define("BUFFER_SCORE_LIST_LIVE_TIME",604800); //一周

define("BUFFER_COURSE_ATTEND_NAME","BUFFER_COURSE_ATTEND");
define("BUFFER_COURSE_ATTEND_TIME",86400); //一天

define("BUFFER_COURSE_ATTEND_TEACHER_NAME","BUFFER_COURSE_ATTEND_TEACHER");
define("BUFFER_COURSE_ATTEND_TEACHER_TIME",86400); //一天

define("BUFFER_SCORE_UNQUALIFIED_RANK_NAME","BUFFER_SCORE_UNQUALIFIED_RANK");
define("BUFFER_SCORE_UNQUALIFIED_RANK_TIME",86400); //一天

define("BUFFER_AGE_DATA_NAME","BUFFER_AGE_DATA");
define("BUFFER_AGE_DATA_TIME",43200); //半天

define("BUFFER_ROOM_USE_DATA_NAME","BUFFER_ROOM_USE_DATA");
define("BUFFER_ROOM_USE_DATA_TIME",43200); //半天

//接口文档自动导出功能, 如果要关闭此功能, 只需注释掉这块代码
//{{
/*
SwaggerProvider::register($app, function(Swagger $swagger)use($app){
    $swagger->schemes = ['https'];
    $swagger->host = $app->get('host');
    $swagger->info->title = 'GMSH+ API V2 操作文档';
    $swagger->info->description = "此文档由 GMSH+ 生成 swagger 格式的 json, 再由Swagger UI 渲染成 web。";
});*/

//}}

//$app->setGlobalHooks([\App\Hooks\LoggerHooks\systemLog::class]);

$app->loadRoutesFromPath( dirname(__DIR__) . '/App/Controllers/AuthenticationController','App\\Controllers\\AuthenticationController');
$app->loadRoutesFromPath( dirname(__DIR__) . '/App/Controllers/PxSamcController','App\\Controllers\\PxSamcController');
$app->loadRoutesFromPath( dirname(__DIR__) . '/App/Controllers/UserController','App\\Controllers\\UserController');
$app->loadRoutesFromPath( dirname(__DIR__) . '/App/Controllers/CourseController','App\\Controllers\\CourseController');
$app->loadRoutesFromPath( dirname(__DIR__) . '/App/Controllers/ProjectController','App\\Controllers\\ProjectController');
$app->loadRoutesFromPath( dirname(__DIR__) . '/App/Controllers/ProfileController','App\\Controllers\\ProfileController');
$app->loadRoutesFromPath( dirname(__DIR__) . '/App/Controllers/DataController','App\\Controllers\\DataController');
$app->loadRoutesFromPath( dirname(__DIR__) . '/App/Controllers/ReportsController','App\\Controllers\\ReportsController');

//执行请求
$app->dispatch();
