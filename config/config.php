<?php

use PhpBoot\DB\DB;

return [
    //App
    'host' => 'https://api.comac.mlstudio.cc/',

    //DB
    'DB.connection'=> 'mysql:dbname=comac;host=127.0.0.1',
    'DB.username'=> 'root',
    'DB.password'=> '73c2b6650162880d',
    'DB.options' => [],

    'samc_db' => \DI\factory([DB::class, 'connect'])
        ->parameter('dsn', 'mysql:dbname=samc_sync;host=127.0.0.1')
        ->parameter('username', 'root')
        ->parameter('password', '73c2b6650162880d')
        ->parameter('options', []),

    \PhpBoot\Controller\ExceptionRenderer::class =>
        \DI\object(\App\Utils\ExceptionRenderer::class)
];