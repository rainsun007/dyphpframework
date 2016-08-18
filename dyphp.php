<?php
/**
 * dyphp-framework base file
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright 2011 dyphp.com
 **/
define('DYPHP_BEGIN_TIME', microtime());
define('DYPHP_PATH', dirname(__FILE__));
define('DS', DIRECTORY_SEPARATOR);
defined('EXT') or define('EXT', '.php');

//简单别名
class Dy extends DyPhpBase{}

/**
 * base class
 **/
class DyPhpBase{
    //debug开关
    public static $debug = false;
    //app类型
    public static $appType = 'web';
    //app公用类实例器
    private static $dyApp;
    //app自定的提示信息参数
    private static $showMsgParam = '';
    //框架类
    private static $coreClasses = array();

    /**
     * 运行web app入口
     * @param array app配制
     **/
    public static function runWebApp($config=null,$debug=false){
        self::runAppCommon($config,$debug,'web');
        self::app()->auth->autoLoginStatus();
        DyPhpRoute::runWeb();
        exit;
    }

    /**
     * 运行console app入口
     * @param array app配制
     **/
    public static function runConsoleApp($config=null,$debug=false){
        if(PHP_SAPI !== 'cli' || !isset($_SERVER['argv'])){
            die('This script must be run from the command line.');
        }
        self::runAppCommon($config,$debug,'console');
        DyPhpRoute::runConsole();
        exit;
    }


    /**
     * 公共app运行入口
     * @param array app配制
     **/
    private static function runAppCommon($config=null,$debug=false,$appType='web'){
        if(function_exists('ini_get') && ini_get('date.timezone') == "" && function_exists('date_default_timezone_set')){
            date_default_timezone_set('PRC');
        }
        self::$appType = $appType;

        self::loadCoreClass();
        require DYPHP_PATH.self::$coreClasses['DyPhpException'];
        require DYPHP_PATH.self::$coreClasses['DyPhpConfig'];
        require DYPHP_PATH.self::$coreClasses['DyPhpRoute'];
        require DYPHP_PATH.self::$coreClasses['DyPhpUserIdentity'];
        require DYPHP_PATH.self::$coreClasses['DyPhpController'];

        self::debug($debug);
        self::$dyApp = new DyPhpApp(self::$coreClasses);
        DyPhpConfig::runConfig($config);
    }

    /**
     * 使用自定义错误处理句柄 显示信息
     * @param array  按提示需要随意传值
     **/
    public static function showMsg($params=array(),$exit = true){
        $params = is_array($params) ? $params : (array)$params;
        $msgHandler = explode('/',trim(DyPhpConfig::item('messageHandler'),'/'));
        DyPhpController::run($msgHandler[0],$msgHandler[1],$params);
        if ($exit){exit;}
    }

    /**
     * 自动加载
     **/
    public static function autoload($className){
        if(isset(self::$coreClasses[$className])){
            require DYPHP_PATH.self::$coreClasses[$className];
        }elseif(DyPhpConfig::getImport($className)){
            require DyPhpConfig::getImport($className);
        }else{
            //5.3 namespace
            if(($pos=strrpos($className,'\\'))!==false){
                if($alias = DyPhpConfig::getAliasMap(substr($className,0,$pos))){
                    require $alias['file'];
                }else{
                    $classFile = APP_PATH.DIRECTORY_SEPARATOR.str_replace('\\','/',$className).EXT;
                    if(file_exists($classFile)){
                        require $classFile;
                    }
                    foreach(DyPhpConfig::getIncludePath() as $key=>$val){
                        $autoClassFile = $val.$className . EXT;
                        if(is_file($autoClassFile)){
                            require $autoClassFile;
                            break;
                        }
                    }
                }
            }else{
                foreach(DyPhpConfig::getIncludePath() as $key=>$val){
                    $classFile = $val.$className . EXT;
                    if(is_file($classFile)){
                        require $classFile;
                        break;
                    }
                }
            }
        }

        if (!class_exists($className, false) && !interface_exists($className, false)) {
            self::throwException('Class does not exist',$className);
        }
    }


    /**
     * @brief    注册自动加载
     * @param    $autoload 自动加载函数
     * @param    $replace  是否替换框架和自动加载方法
     * @return
     **/
    public static function autoloadRegister($callback,$replace=false){
        spl_autoload_unregister(array('DyPhpBase', 'autoload'));
        if($replace){
            spl_autoload_register($callback);
        }else{
            spl_autoload_register($callback);
            spl_autoload_register(array('DyPhpBase', 'autoload'));
        }
    }


    /**
     * 运行时间
     * @return float   seconds
     **/
    public static function execTime(){
        list($usec, $sec) = explode(" ", DYPHP_BEGIN_TIME);
        $beginTime = (float)$usec + (float)$sec;

        list($usec, $sec) = explode(" ", microtime());
        $endTime = (float)$usec + (float)$sec;

        $time = $endTime-$beginTime;
        return number_format($time,6,'.','');
    }

    /**
     * app自定义类实例器
     * @public 类名
     **/
    public static function app(){
        return self::$dyApp;
    }

    /**
     * 获取框架版本
     **/
    public static function getVersion(){
        return 'Beta 1.3';
    }

    /**
     * 获取Powered by
     * @param false时将返回不带连接的powered by
     **/
    public static function powerBy($link=true){
        return $link ? 'Powered by <a href="http://www.dyphp.com" target="_blank">DYPHP-Framework</a>' : 'Powered by DYPHP-Framework';
    }

    /**
     * 异常,错误捕获
     * self::$debug为真时给出错误运行跟踪 为假时运行self::$errorHandler指向只给出错误提示信息
     * 详见DyPhpException类
     **/
    private static function debug($debug=false){
        self::$debug = $debug ? true : false;
        set_error_handler(array('DyPhpException', 'errorHandler'));
        set_exception_handler(array('DyPhpException', 'exceptionHandler'));
        register_shutdown_function(array('DyPhpException', 'shutdownHandler'));
    }

    /**
     * 异常抛出
     * @param string  自定义出错信息
     * @param string  系统异常，自定义异常自信等
     * @param string  异常类型
     * @param bool    是否退出程序
     **/
    public static function throwException($errorMessage, $prefix='', $code=0, $previous = NULL){
        if($prefix != ''){
            $isUtf8 = DyString::isUtf8($prefix);
            if($isUtf8 === false && function_exists('iconv')){
                $prefix = iconv("gbk", "UTF-8", $prefix);
            }
        }
        $message = DyPhpMessage::getLanguagePackage(DyPhpConfig::item('language'));
        $excMessage = isset($message[$errorMessage]) ? $message[$errorMessage] : $errorMessage;
        if($previous){
            //现行版本只对数据库异常给应用一次可catch的机会
            throw new Exception($prefix.' '.$excMessage, $code, $previous);
        }else{
            $dyExce = new DyPhpException($prefix.' '.$excMessage, $code, $previous);
            $dyExce->appTrace();
        }
        exit;
    }

    /**
     * @brief    加载框架类
     * @return
     **/
    private static function loadCoreClass(){
        self::$coreClasses = array(
            //base
            'DyPhpController'=>'/dyphp/base/DyPhpController.php',
            'DyPhpView'=>'/dyphp/base/DyPhpView.php',
            'DyPhpConfig'=>'/dyphp/base/DyPhpConfig.php',
            'DyPhpRoute'=>'/dyphp/base/DyPhpRoute.php',
            'DyPhpException'=>'/dyphp/base/DyPhpException.php',
            'DyPhpUserIdentity'=>'/dyphp/base/DyPhpUserIdentity.php',
            'DyPhpWidgets'=>'/dyphp/base/DyPhpWidgets.php',
            'DyPhpDebug'=>'/dyphp/base/DyPhpDebug.php',
            'DyPhpHooks'=>'/dyphp/base/DyPhpHooks.php',
            'DyPhpMessage'=>'/dyphp/i18n/DyPhpMessage.php',

            //cache
            'DyPhpCache'=>'/dyphp/cache/DyPhpCache.php',
            'DyPhpFileCache'=>'/dyphp/cache/drivers/DyPhpFileCache.php',
            'DyPhpApcCache'=>'/dyphp/cache/drivers/DyPhpApcCache.php',
            'DyPhpMemcacheCache'=>'/dyphp/cache/drivers/DyPhpMemcacheCache.php',

            //db
            'DyPhpModel'=>'/dyphp/db/DyPhpModel.php',
            'DyDbCriteria'=>'/dyphp/db/DyDbCriteria.php',
            'DyPhpPdoMysql'=>'/dyphp/db/drivers/DyPhpPdoMysql.php',
            'DyPhpMysql'=>'/dyphp/db/drivers/DyPhpMysql.php',

            //lib
            'DyCookie'=>'/dyphp/lib/DyCookie.php',
            'DyRequest'=>'/dyphp/lib/DyRequest.php',
            'DySession'=>'/dyphp/lib/DySession.php',
            'DyCache'=>'/dyphp/lib/DyCache.php',
            'DyStatic'=>'/dyphp/lib/DyStatic.php',
            'DyCaptcha'=>'/dyphp/lib/DyCaptcha.php',

            //utils
            'DyDebug'=>'/dyphp/utils/DyDebug.php',
            'DyTools'=>'/dyphp/utils/DyTools.php',
            'DyGDImg'=>'/dyphp/utils/DyGDImg.php',
            'DyString'=>'/dyphp/utils/DyString.php',
            'DyUpload'=>'/dyphp/utils/DyUpload.php',
            'DyDownload'=>'/dyphp/utils/DyDownload.php',
            'DyFilter'=>'/dyphp/utils/DyFilter.php',

            //widget
            'DyPagerWidget'=>'/dyphp/widgets/DyPagerWidget.php',
            'DyCaptchaWidget'=>'/dyphp/widgets/DyCaptchaWidget.php',
        );
    }

    /**
     * @brief   框架支持检查
     * @return
     **/
    public static function supportCheck(){
        //'$_SERVER $_FILES $_COOKIE $_SESSION GD PDO mb_substr iconv_substr iconv  mcrypt';
        $result = apache_get_modules();
        if(in_array('mod_rewrite', $result)) {
            echo 'apache rewrite support';
        } else {
            echo 'apache rewrite unsupport';
        }
        echo 'php current version:'.PHP_VERSION.' status:'.(version_compare(PHP_VERSION, '5.2.2', '>=') ? 'OK' : 'minimum version of 5.2.2');
        exit;
    }
}

/**
 * app
 **/
final class DyPhpApp{
    //调用的controller名
    public $cid = '';

    //调用的module/controller名
    public $pcid = '';

    //调用的action名
    public $aid = '';

    //调用的module名
    public $module = '';

    //当前运行的controller实例
    public $runingController = null;

    //实例存储
    private $instanceArr = array();

    //唯一包含
    private $incOnce = array();

    public function __construct(){
    }

    public function __get($name){
        return  $this->instance($name);
    }

    public function __set($name,$value){
        $this->reg($name,$value);
    }

    /**
     * @brief    实例注册
     * @param    $name   注册名
     * @param    $value  注册实例
     * @return
     **/
    public function reg($name,$value=''){
        if($value){
            if(!isset($this->instanceArr[$name])){
                $this->instanceArr[$name] = $value;
            }
        }else{
            $this->instance($name);
        }
    }

    /**
     * @brief    实例处理
     * param     注册名
     * @return
     **/
    private function instance($name){
        if(isset($this->instanceArr[$name])){
            return $this->instanceArr[$name];
        }

        $alias = DyPhpConfig::getAliasMap($name);
        $className = $alias ? $alias['name'] : $name;
        $classFile = $alias ? $alias['file'] : DyPhpConfig::getImport($name);
        if($classFile && !class_exists($className, false)){
            require $classFile;
        }

        //不做单例处理
        if($name == 'dbc'){
            return new $alias['name'];
        }

        $this->instanceArr[$name] = new $className;
        return $this->instanceArr[$name];
    }

    /**
     * 加载vendors
     * @param vendors 路径及文件名
     * @param dyphp为加载框架自带vendor app为加载app中的vendor
     */
    public function vendors($filePathName,$isSys=false){
        $type = $isSys === true ? 'dyphp' : 'app';

        if(in_array($type.'_'.$filePathName,$this->incOnce)){
            return;
        }
        $vendor = $type == 'app' ? DyPhpConfig::item('appPath').'/vendors/'.$filePathName.EXT : DYPHP_PATH.'/dyphp/vendors/'.$filePathName.'.php';
        if(!file_exists($vendor)){
            DyPhpBase::throwException('vendor does not exist',$filePathName);
        }
        require $vendor;
        $this->incOnce[] = $type.'_'.$filePathName;
    }

}

spl_autoload_register(array('DyPhpBase', 'autoload'));
