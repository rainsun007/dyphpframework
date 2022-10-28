<?php

$appPath = realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..');
DyCfg::setPathOfAlias('vpn_app', $appPath);

return array(
    //app根地址
    'appPath' => $appPath,
    //app名 用于title显示
    'appName' => 'DY-vpn',
    //app错误框架提示语言 现只支持zh_cn
    'language' => 'zh_cn',
    //app密钥 cookie session string等加密  不同应用此密钥应唯一
    'secretKey' => 'dyphp1+x8K$8_y3rG-8#5Cz%Tw7/u^6nM~f0%Q2&R4*tc=i8\t@9!vpn',
    //运行环境dev,test,pro,pre
    'env' => 'dev',

    //预加载文件及包含路径
    'import' => array(
        'app.utils.*',
        'vpn_app.models.*',
    ),

    //类及命名空间别名映射
    'aliasMap' => array(
    ),

    //数据库配制
    'db' => array(
        'default' => array(
            'dbDriver' => 'pdo_mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'dbName' => 'vpn',
            'charset' => 'UTF8',
            'user' => 'proot',
            'pass' => 'root',
            //'pconn' => false,
            //'tablePrefix' => '',
        ),
    ),

    /*
     * URL管理
     * 注意：ca,page为框架保留的$_GET参数
     */
    'urlManager' => array(
        'urlStyle' => array('hideIndex' => 'yes', 'restCa' => 'yes'),
    ),

    //cookie配制
    'cookie' => array(
        'prefix' => 'vpn_',
    ),

    //缓存配制 'file','apc','memcache'
    'cache' => array(
        'default' => array('type' => 'file','cacheRootPath'=>dirname(__FILE__).DIRECTORY_SEPARATOR.'../../storage/cache'), 
    ),

    //建议按console web类型做不同处理
    //自定义错误处理句柄 默认为app/error
    'errorHandler' => 'app/error',
    //自定义message处理句柄 默认为app/message
    'messageHandler' => 'app/message',
    //自定义登陆处理句柄 默认为app/login
    'loginHandler' => 'app/login',
    //异常log保存的根目录
    'exceptionLogRootDir' => dirname(__FILE__).DIRECTORY_SEPARATOR.'../../storage/logs',

    //自定义参数配制
    'params' => array(
    ),
);
