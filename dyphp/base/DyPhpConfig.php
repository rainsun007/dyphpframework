<?php
/**
 * 配制处理 
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright 2011 dyphp.com 
 **/

//默认controller配制
defined('DYPHP_DEFAULT_CONTROLLER') or define('DYPHP_DEFAULT_CONTROLLER', 'app');
//默认action配制
defined('DYPHP_DEFAULT_ACTION') or define('DYPHP_DEFAULT_ACTION', 'index');

//简单别名
class DyCfg extends DyPhpConfig{
}

class DyPhpConfig{
    //app唯一id
    private static $appID = '';
    //系统提示信息
    private static $language = '';
    //app的名称
    private static $appName = '';
    //app的根目录
    private static $appPath = '';
    //app的浏览URL根层级
    private static $appHttpPath = '';
    //密钥
    private static $secretKey = '';
    //运行环境
    private static $env = '';
    //错误显示页面
    private static $errorHandler = '';
    //登陆页面
    private static $loginHandler = '';
    //信息处理页面
    private static $messageHandler = '';
    //url重写管理
    private static $urlManager = array();
    //数据库配制
    private static $db = array();
    //缓存配制
    /*
    配制格式
    'cache'=>array( 
        'c1'=>array('type'=>'file','gcOpen'=>true),  //文件缓存多时 不建议打开gc 会导致性能低下  可以使用shell处理
        'c2'=>array('type'=>'apc'),
        'c3'=>array(
            'type'=>'memcache',
            'isMemd'=>false,
            'servers_one'=>array(
                array('host','port','weight'),
                array('host','port','weight'),
            ),
        ),
    ),
    */
    private static $cache = array();
    //cookie配制
    private static $cookie = array();
    //组件配制
    private static $components = array();
    //自定义参数配制
    private static $params = array();
    //import数组
    private static $import = array();
    //别名映射
    private static $aliasMap = array();
    //自定义别名包
    private static $pathOfAlias = array();
    //勾子配制
    private static $hooks = array();
    //autoload包含路径
    private static $includePath = array();

    /**
     * 运行app配制入口
     * @param array app配制
     **/ 
    public static function runConfig($config = null){
        $config = require $config;

        //language load
        //异常信息输出语言 现只支持zh_cn
        self::$language = isset($config['language']) ? $config['language'] : 'zh_cn';

        //app config
        if (!is_array($config)) {
            DyPhpBase::throwException('config is not an array');
        }

        //check secretKey
        //app密钥 cookie session string等加密  不同应用此密钥应唯一
        if (!array_key_exists('secretKey', $config) || empty($config['secretKey'])) {
            DyPhpBase::throwException('secretKey Undefined');
        }
        //生成app唯一id session key的前缀中使用到 解决多应用session冲突（当前版本只有这一个用途）
        self::$appID = md5($config['secretKey']);

        //check appPath and setting define
        if (!array_key_exists('appPath', $config)) {
            DyPhpBase::throwException('appPath Undefined');
        }
        self::$appPath = rtrim(realpath($config['appPath']),'/');
        define('APP_PATH', self::$appPath);
        define('APP_PARENT_PATH', dirname(APP_PATH));

        //check environment
        //运行环境，用于加载不同环境的constants文件，不设置或为空时加载constants.php（当前版本只有这一个用途）
        if(array_key_exists('env', $config) && !in_array($config['env'],array('dev','test','pro','pre',''))){
            DyPhpBase::throwException('run environment defined invalid');
        }

        //get appHttpPath 获取url层级地址，以支持将应用部署到web根目录下的子目录下，DyRequest::createUrl()方法中使用
        self::$appHttpPath = isset($_SERVER["SCRIPT_NAME"]) ? trim(str_replace(array('\\','\\\\','//'),'/',dirname($_SERVER["SCRIPT_NAME"])),'/') : trim(str_replace($_SERVER['DOCUMENT_ROOT'],"",dirname($_SERVER['SCRIPT_FILENAME'])),'/');

        //初始化handler
        //建议：按console web类型，配合DYPHP_DEFAULT_CONTROLLER定义 在action中做不同处理
        self::$errorHandler = DYPHP_DEFAULT_CONTROLLER.'/error';
        self::$loginHandler = DYPHP_DEFAULT_CONTROLLER.'/login';
        self::$messageHandler = DYPHP_DEFAULT_CONTROLLER.'/message';

        //设置配制属性
        $configArr = array(
            'db','cache','cookie','urlManager','params','aliasMap','hooks',
            'errorHandler','messageHandler','loginHandler','appName','secretKey','env'
        );
        foreach($configArr as $key=>$val){
            if (array_key_exists($val, $config)) {
                $isType = $key<=6 ? is_array($config[$val]) : is_string($config[$val]);
                if(!$isType){
                    DyPhpBase::throwException('data type error',$val);
                }
                self::${$val} = $config[$val];
            }
        }

        //import加载
        $import = array_key_exists('import', $config) && is_array($config['import']) ? $config['import'] : array();
        self::import($import);

        //aliasMap加载
        $aliasMap = array_key_exists('aliasMap', $config) && is_array($config['aliasMap']) ? $config['aliasMap'] : array();
        self::aliasMap($aliasMap);

        unset($config);
        self::loadCommon();
    }

    /**
     * 解析app配制中的包含文件，autoload将调用该配制
     * 
     * @param array
     * 实例
     * 'import' => array(
     *       'app.components.UserIdentity',
     *       'app.widgets.*',
     *       'app.utils.*',
     *       'com.utils.*',
     *   ), 
     **/
    private static function import($pathArr){
        $appImport = array(
            'app.models.*',
            'app.components.*',
        );
        $pathArr = array_unique(array_merge($appImport,$pathArr));

        foreach ($pathArr as $path) {
            $path = self::getRealPath($path);

            if (substr($path, -1) !== '*') {
                $file = $path.EXT;
                if (!file_exists($file)) {
                    DyPhpBase::throwException('file does not exist', $file);
                }
                $className = substr($path,strrpos($path,DIRECTORY_SEPARATOR));
                self::$import[$className] = $file;
                continue;
            }

            $incPath = rtrim($path, '*');
            if(is_dir($incPath)){
                self::$includePath[] = $incPath;
            }
        }

        if (self::$includePath) {
            set_include_path(get_include_path() . PATH_SEPARATOR . implode(PATH_SEPARATOR,self::$includePath));
        }
    }

    /**
     * @brief  别名映射,  调用方式如：Dy::app()->dbc
     * 
     * @param  array  $aliasArr
     * 实例
     * 'aliasMap' => array(
     *       'captcha'=>'dysys.dyphp.lib.DyCaptcha',
     *       'dbc'=>'dysys.dyphp.db.DyDbCriteria',
     *       'hook'=>'dysys.dyphp.base.DyPhpHooks',
     *       'auth'=>'app.components.UserIdentity',
     *  );
     **/
    private static function aliasMap($aliasArr){
        self::setPathOfAlias('dysys',DYPHP_PATH);
        $loadAlias = array(
            'captcha'=>'dysys.dyphp.lib.DyCaptcha',
            'dbc'=>'dysys.dyphp.db.DyDbCriteria',
            'hook'=>'dysys.dyphp.base.DyPhpHooks',
            'auth'=>'app.components.UserIdentity',
        );
        $aliasArr = array_unique(array_merge($loadAlias,$aliasArr));

        foreach ($aliasArr as $key=>$path) {
            $file = self::getRealPath($path).EXT;
            if (!file_exists($file)) {
                DyPhpBase::throwException('file does not exist', $file);
            }
            self::$aliasMap[$key] = $file;
        }
    }

    /**
     * 获取import与aliasMap的文件或目录真实路径
     *
     * @param string  $path
     * @return string 
     */
    private static function getRealPath($path){
        if (strpos($path, 'app') === 0) {
            $path = strtr($path, array('app' => self::$appPath, '.' =>DIRECTORY_SEPARATOR));
        }else{
            $alias = substr($path,0,strpos($path, '.'));
            if(!isset(self::$pathOfAlias[$alias])){
                DyPhpBase::throwException('path alias error', $alias);
            }
            $path = strtr($path, array($alias=>self::$pathOfAlias[$alias], '.' =>DIRECTORY_SEPARATOR));
        }
        return $path;
    }

    /**
     * 设置自定义别名包
     * @param string 别名
     * @param string 路径(相对、绝对路径均可)
     **/
     public static function setPathOfAlias($alias,$path){
        self::$pathOfAlias[$alias] = $path;
    }

    /**
     * 获取import文件路径
     * @param 类名
     * @return array
     **/
    public static  function getImport($className){
        return isset(self::$import[$className]) ? self::$import[$className] : false;
    }

    /**
     * @brief    获取映射，autoload将调用该方法
     * @param    $aliasName
     * @return   array
     **/
    public static  function getAliasMap($aliasName){
        if(!isset(self::$aliasMap[$aliasName])){
            return false;
        }
        return array('name'=>basename(self::$aliasMap[$aliasName],EXT), 'file'=>self::$aliasMap[$aliasName]);
    }

    /**
     * @brief    获取配制项
     * @param    $itemName
     * @return   mixed
     **/
    public static function item($itemName){
        if(isset(self::${$itemName})){
            return self::${$itemName};
        }
        DyPhpBase::throwException('config does not exist',$itemName);
    }

    /**
     * 获取是否隐藏index.php设置
     * @return bool
     **/
    public static function getHideIndex(){
        $urlmanager = self::$urlManager;
        return isset($urlmanager['urlStyle']['hideIndex']) && $urlmanager['urlStyle']['hideIndex'] == 'yes' ? true : false;
    }

    /**
     * 获取url是否以rest风格访问
     * @return bool
     **/
    public static function getRestCa(){
        $urlmanager = self::$urlManager;
        return isset($urlmanager['urlStyle']['restCa']) && $urlmanager['urlStyle']['restCa'] == 'yes' ? true : false;
    }

    /**
     * 获取自定义参数
     * @param string
     * @return mixed
     **/
    public static function getParams($param){
        return !empty(self::$params[$param]) ? self::$params[$param] : null;
    }

    /**
     * @brief    获取自定义包含路径，autoload将调用该方法
     * @return   string
     **/
    public static function getIncludePath(){
        return self::$includePath;
    }

    /**
     * @brief    加载配制及工具
     **/
    private static function loadCommon(){
        //constants 非必须文件 不存在就不加载 不会给出报错信息
        $constants = empty(self::$env) ? 'constants' : self::$env.'_constants';
        $constants = self::$appPath.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.$constants.EXT;
        if(file_exists($constants)){
            require $constants;
        }

        //functions 非必须文件 不存在就不加载 不会给出报错信息
        $funs = self::$appPath.DIRECTORY_SEPARATOR.'utils'.DIRECTORY_SEPARATOR.'functions'.EXT;
        if(file_exists($funs)){
            require $funs;
        }
    }
}


