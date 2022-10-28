<?php
//环境配制
error_reporting(E_ALL);
$config = dirname(__FILE__).'/application/config/dev_config.php';
require dirname(__FILE__).'/../dyphpframework/dyphp.php';

//运行app
//Dy::supportCheck();
define('DYPHP_DEFAULT_CONTROLLER', 'app');
define('DYPHP_DEFAULT_ACTION', 'index');
Dy::runConsoleApp($config, true);
