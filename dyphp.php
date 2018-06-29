<?php
/**
 * dyphp-framework base file
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright 2011 dyphp.com
 **/

//系统运行开始时间
define('DYPHP_BEGIN_TIME', microtime());
//框架根目录地址
define('DYPHP_PATH', dirname(__FILE__));
//系统路径分割符简写
define('DS', DIRECTORY_SEPARATOR);
//文件扩展名
defined('EXT') or define('EXT', '.php');

/**
 * 框架版本
 * 版本号规则：
 * 主版本号(较大的变动).子版本号(功能变化或新特性增加).构建版本号(Bug修复或优化)-版本阶段(base、alpha、beta、RC、release)
 * 上一级版本号变动时下级版本号归零
 **/
define('DYPHP_VERSION', '2.5.1-release');

//简单别名
class Dy extends DyPhpBase
{
}

/**
 * base class
 **/
class DyPhpBase
{
    //debug开关
    public static $debug = false;

    //app类型
    public static $appType = 'web';

    //app公用类实例器
    private static $dyApp;

    //框架类
    private static $coreClasses = array();

    /**
     * 运行web app入口
     *
     * @param string 配制文件
     * @param boolean 是否开启debug
     * @param boolean 是否开启web防火墙
     * @return void
     */
    public static function runWebApp($config = null, $debug = false, $waf = true)
    {
        self::runAppCommon($config, $debug, 'web');

        if ($waf) {
            require DYPHP_PATH.self::$coreClasses['DyPhpWaf'];
            new DyPhpWaf();
        }

        //运行自动登录逻辑
        self::app()->auth->autoLoginStatus();
        DyPhpRoute::runWeb();
        exit;
    }

    /**
     * 运行console app入口
     * @param array app配制
     **/
    public static function runConsoleApp($config = null, $debug = false)
    {
        if (PHP_SAPI !== 'cli' || !isset($_SERVER['argv'])) {
            die('This script must be run from the command line.');
        }
        self::runAppCommon($config, $debug, 'console');
        DyPhpRoute::runConsole();
        exit;
    }

    /**
     * 公共app运行入口
     * @param array app配制
     **/
    private static function runAppCommon($config = null, $debug = false, $appType = 'web')
    {
        if (function_exists('ini_get') && ini_get('date.timezone') == "" && function_exists('date_default_timezone_set')) {
            date_default_timezone_set('PRC');
        }
        self::$appType = $appType;

        self::loadCoreClass();
        require DYPHP_PATH.self::$coreClasses['DyPhpException'];
        require DYPHP_PATH.self::$coreClasses['DyPhpConfig'];
        require DYPHP_PATH.self::$coreClasses['DyPhpRoute'];
        if ($appType == 'web') {
            require DYPHP_PATH.self::$coreClasses['DyPhpUserIdentity'];
        }
        require DYPHP_PATH.self::$coreClasses['DyPhpController'];
        require DYPHP_PATH.self::$coreClasses['DyPhpHooks'];

        self::debug($debug);
        self::$dyApp = new DyPhpApp(self::$coreClasses);
        DyPhpConfig::runConfig($config);
    }

    /**
     * 使用自定义错误处理句柄 显示信息
     * @param array  按提示需要随意传值
     **/
    public static function showMsg($params = array())
    {
        Dy::app()->setPreInsAttr();

        $params = is_array($params) ? $params : (array)$params;
        $msgHandler = explode('/', trim(DyPhpConfig::item('messageHandler'), '/'));
        DyPhpController::run($msgHandler[0], $msgHandler[1], $params);
    }

    /**
     * 自动加载
     **/
    public static function autoload($className)
    {
        if (isset(self::$coreClasses[$className])) {
            require DYPHP_PATH.self::$coreClasses[$className];
        } elseif (DyPhpConfig::getImport($className)) {
            require DyPhpConfig::getImport($className);
        } else {
            //5.3 namespace自动加载
            if (($pos = strrpos($className, '\\')) !== false) {
                if ($alias = DyPhpConfig::getAliasMap(substr($className, 0, $pos))) {
                    require $alias['file'];
                } else {
                    $classFile = APP_PATH.DIRECTORY_SEPARATOR.str_replace('\\', '/', $className).EXT;
                    if (file_exists($classFile)) {
                        require $classFile;
                    } else {
                        foreach (DyPhpConfig::getIncludePath() as $key => $val) {
                            $autoClassFile = $val.$className . EXT;
                            if (is_file($autoClassFile)) {
                                require $autoClassFile;
                                break;
                            }
                        }
                    }
                }
            } else {
                if ($alias = DyPhpConfig::getAliasMap($className)) {
                    if (!class_exists($alias['name'], false)) {
                        require $alias['file'];
                    }
                    class_alias($alias['name'], $className, false);
                } else {
                    foreach (DyPhpConfig::getIncludePath() as $key => $val) {
                        $classFile = $val.$className . EXT;
                        if (is_file($classFile)) {
                            require $classFile;
                            break;
                        }
                    }
                }
            }
        }

        if (!class_exists($className, false) && !interface_exists($className, false)) {
            self::throwException('Class does not exist', $className);
        }
    }


    /**
     * 注册自动加载
     * @param  mixed  $autoload 自动加载函数
     * @param  bool   $prepend  如果是 true，spl_autoload_register() 会添加函数到队列之首，而不是队列尾部。
     * @param  bool   $replace  是否替换框架的自动加载方法, 替换后要实现框架相关的自动加载逻辑，不建议替换
     **/
    public static function autoloadRegister($callback, $prepend = false, $replace = false)
    {
        if ($replace) {
            spl_autoload_unregister(array('DyPhpBase', 'autoload'));
            spl_autoload_register($callback);
        } else {
            spl_autoload_register($callback, true, $prepend);
        }
    }


    /**
     * 运行时间
     * @return float   seconds
     **/
    public static function execTime()
    {
        list($usec, $sec) = explode(" ", DYPHP_BEGIN_TIME);
        $beginTime = (float)$usec + (float)$sec;

        list($usec, $sec) = explode(" ", microtime());
        $endTime = (float)$usec + (float)$sec;

        $time = $endTime-$beginTime;
        return number_format($time, 6, '.', '');
    }

    /**
     * app自定义类实例器
     * @public 类名
     **/
    public static function app()
    {
        return self::$dyApp;
    }

    /**
     * 获取Powered by
     * @param bool false时将返回不带连接的powered by
     * @return string
     **/
    public static function powerBy($link = true)
    {
        return $link ? 'Powered by <a href="http://www.dyphp.com" target="_blank">DYPHP-Framework '.DYPHP_VERSION.'</a>' : 'Powered by DYPHP-Framework '.DYPHP_VERSION;
    }

    /**
     * 异常,错误捕获
     * self::$debug为真时给出错误运行跟踪 为假时运行self::$errorHandler指向只给出错误提示信息
     * 详见DyPhpException类
     **/
    private static function debug($debug = false)
    {
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
    public static function throwException($errorMessage, $prefix = '', $code = 0, $previous = null)
    {
        if ($prefix != '') {
            $isUtf8 = DyString::isUtf8($prefix);
            if ($isUtf8 === false && function_exists('iconv')) {
                $prefix = iconv("gbk", "UTF-8", $prefix);
            }
        }
        $message = DyPhpMessage::getLanguagePackage(DyPhpConfig::item('language'));
        $excMessage = isset($message[$errorMessage]) ? $message[$errorMessage] : $errorMessage;
        if ($previous) {
            //现行版本只对数据库异常给应用一次可catch的机会
            throw new Exception($prefix.' '.$excMessage, (int)$code, $previous);
        } else {
            $dyExce = new DyPhpException($prefix.' '.$excMessage, $code, $previous);
            $dyExce->appTrace();
        }
        exit;
    }

    /**
     * @brief    加载框架类
     * @return array
     **/
    private static function loadCoreClass()
    {
        self::$coreClasses = array(
            //base
            'DyPhpController'=>'/dyphp/base/DyPhpController.php',
            'DyPhpView'=>'/dyphp/base/DyPhpView.php',
            'DyPhpConfig'=>'/dyphp/base/DyPhpConfig.php',
            'DyPhpRoute'=>'/dyphp/base/DyPhpRoute.php',
            'DyPhpException'=>'/dyphp/base/DyPhpException.php',
            'DyPhpWaf'=>'/dyphp/base/DyPhpWaf.php',
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
     * @return  null
     **/
    public static function supportCheck()
    {
        //'$_SERVER $_FILES $_COOKIE $_SESSION  | GD pdo_mysql PDO mbstring iconv  mcrypt openssl'
        $br = PHP_SAPI == 'cli' ? PHP_EOL : '</br >';
        $splitLine = $br.str_repeat('-', 60).$br;

        echo $br.'[Framework limit]';
        echo $splitLine;
        echo 'php current version:'.PHP_VERSION.' status: '.(version_compare(PHP_VERSION, '5.3.0', '>=') ? '√ OK' : '× minimum version of 5.2.2');
        echo $br.'Current running SAPI : '.PHP_SAPI.$br;
        echo PHP_SAPI !== 'cli' ? 'Framework retain key for $_GET : ca,ext_name,page' : '';
        
        echo $br.$br.'[Extension check]';
        echo $splitLine;

        echo extension_loaded('pdo') ? "√ PDO support" : "× PDO unsupport";
        echo $splitLine;

        echo extension_loaded('pdo_mysql') ? "√ PDO_MYSQL support" : "× PDO_MYSQL unsupport";
        echo $splitLine;

        echo extension_loaded('mbstring') ? "√ mbstring support" : "× mbstring unsupport";
        echo $splitLine;

        echo extension_loaded('iconv') ? "√ iconv support" : "× iconv unsupport";
        echo $splitLine;

        echo extension_loaded('gd') || extension_loaded('gd2') ? "√ GD support" : "× GD unsupport";
        echo $splitLine;

        echo extension_loaded('openssl') ? "√ openssl support" : "× openssl unsupport";
        echo $splitLine;

        echo extension_loaded('mcrypt') ? "√ mcrypt support" : "× mcrypt unsupport";
        echo $splitLine;

        echo extension_loaded('openssl') ? "√ openssl support" : "× openssl unsupport";
        echo $splitLine;

        if (version_compare(PHP_VERSION, '5.4.0', '<')) {
            //5.4之后magic_quotes_gpc移除, 总是返回false, 5.4以上版本不做此检查
            echo get_magic_quotes_gpc() ? '√ magic_quotes_gpc open' : '× magic_quotes_gpc close';
            echo $splitLine;
        }

        echo $br.$br;
        exit;
    }
}

/**
 * app
 **/
final class DyPhpApp
{
    /* controller实例全局属性 */
    //调用的module名
    public $module = '';
    //调用的module/controller名
    public $pcid = '';
    //调用的controller名
    public $cid = '';
    //调用的action名
    public $aid = '';

    /* 此针对errorHandler，messageHandler信息接管 */
    //记录前一次运行的module
    public $preModule = '';
    //记录前一次运行的module/controller
    public $prePcid = '';
    //记录前一次运行的controller
    public $preCid = '';
    //记录前一次运行的action
    public $preAid = '';

    //当前运行的controller实例
    public $runingController = null;

    //注册实例及别名实例单例存储器
    private $instanceArr = array();
    //加载vendors单例存储器
    private $incOnce = array();

    public function __construct()
    {
    }

    public function __get($name)
    {
        return  $this->instance($name);
    }

    public function __set($name, $value)
    {
        $this->reg($name, $value);
    }

    /**
     * @brief    实例注册
     * @param    $name   注册名
     * @param    $value  注册实例
     * @return   null
     **/
    public function reg($name, $value = '')
    {
        if ($value) {
            if (!isset($this->instanceArr[$name])) {
                $this->instanceArr[$name] = $value;
            }
        } else {
            $this->instance($name);
        }
    }

    /**
     * @brief    实例处理
     * param     注册名
     * @return   object
     **/
    private function instance($name)
    {
        if (isset($this->instanceArr[$name])) {
            return $this->instanceArr[$name];
        }

        $alias = DyPhpConfig::getAliasMap($name);
        $className = $alias ? $alias['name'] : $name;
        $classFile = $alias ? $alias['file'] : DyPhpConfig::getImport($name);
        if ($classFile && !class_exists($className, false)) {
            require $classFile;
        }

        //不做单例处理
        if ($name == 'dbc') {
            return new $alias['name'];
        }

        $this->instanceArr[$name] = new $className;
        return $this->instanceArr[$name];
    }

    /**
     * 加载vendors
     * @param string vendors 路径及文件名
     * @param bool dyphp为加载框架自带vendor app为加载app中的vendor
     * @return null
     */
    public function vendors($filePathName, $isSys = false)
    {
        $type = $isSys === true ? 'dyphp' : 'app';

        if (in_array($type.'_'.$filePathName, $this->incOnce)) {
            return;
        }
        $vendor = $type == 'app' ? DyPhpConfig::item('appPath').'/vendors/'.$filePathName.EXT : DYPHP_PATH.'/dyphp/vendors/'.$filePathName.'.php';
        if (!file_exists($vendor)) {
            DyPhpBase::throwException('vendor does not exist', $filePathName);
        }
        require $vendor;
        $this->incOnce[] = $type.'_'.$filePathName;
    }

    /**
     * 设置controller实例全局属性的前一次运行属性, 记录来源(前一次运行)module,controller,action
     */
    public function setPreInsAttr()
    {
        $this->preModule = $this->module;
        $this->prePcid = $this->pcid;
        $this->preCid = $this->cid;
        $this->preAid = $this->aid;
    }
}

spl_autoload_register(array('DyPhpBase', 'autoload'));
