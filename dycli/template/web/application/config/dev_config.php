<?php

$appPath = realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..');
DyCfg::setPathOfAlias('app', $appPath);

return array(
    //app根地址
    'appPath' => $appPath, 
    //app名 用于title显示
    'appName' => 'DYPHP_APP',
    //app错误框架提示语言 现只支持zh_cn
    'language' => 'zh_cn',
    //app密钥 cookie session string等加密  不同应用此密钥应唯一
    'secretKey' => 'dyphp1+x8K$8_y3rG-8#5Cz%new/u^6nM~f0%Q2&R4*tc=i8\t@9!',
    //运行环境dev,test,pro,pre
    'env' => 'dev',

    //预加载文件及包含路径
    'import' => array(
        'app.utils.*',
        'app.models.*',
    ),

    //类及命名空间别名映射
    'aliasMap' => array(
        'VHelper' => 'app.utils.ViewHelper',
    ),

    //数据库配制
    'db' => array(
        'default' => array(
            'dbDriver' => 'pdo_mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'dbName' => 'db_name',
            'charset' => 'utf8mb4',
            'user' => 'root',
            'pass' => '123456',
            'pconn' => false,
            'tablePrefix' => '',
        ),
    ),

    /*
     * URL管理
     * 注意：ca,page为框架保留的$_GET参数
     */
    'urlManager' => array(
        'urlStyle' => array('hideIndex' => 'yes', 'restCa' => 'yes'),
        '/home' => array('controller' => 'home', 'action' => 'index'),
    ),

    //cookie配制
    'cookie' => array(
        'prefix' => 'dy_',
    ),

    //缓存配制 'file','apc','memcache'
    'cache' => array(
        'default' => array('type' => 'file'),
    ),

    //建议按console web类型做不同处理
    //自定义错误处理句柄 默认为app/error
    'errorHandler' => 'app/error',
    //自定义message处理句柄 默认为app/message
    'messageHandler' => 'app/message',
    //自定义登陆处理句柄 默认为app/login
    'loginHandler' => 'app/login',
    //异常log保存的根目录
    'exceptionLogRootDir' => '',

    //自定义参数配制
    'params' => array(
        'powerBy' => 'Powered By <a href="https://github.com/rainsun007/dyphpframework" target="_blank">DYPHP-Framework</a>',
    ),

);
