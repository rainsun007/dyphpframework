<?php
//环境配制
$appEnv = getenv('PHP_RUNTIME_ENVIROMENT');

if ($appEnv == 'DEV') {
    //error_reporting(0) && DyPhpBase::$debug == false时页面空白（即不调用config中自定义的errorHandler）
    error_reporting(E_ALL);
    $config = dirname(__FILE__).'/application/config/dev_config.php';
    $debug = true;
    require getenv('DYPHP_FRAMEWORK');
} else {
    error_reporting(E_ALL ^ (E_NOTICE | E_WARNING)); 
    $config = dirname(__FILE__).'/application/config/config.php';
    $debug = false;
    require dirname(__FILE__,3).'/DyphpFramework/dyphp.php';
}

//运行app
DyPhpBase::runWebApp($config, $debug);
