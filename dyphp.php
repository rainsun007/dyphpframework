<?php
/**
 * dyphp-framework base file
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright dyphp.com
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
 * 主版本号(较大的变动).子版本号(功能变化或特性增减).构建版本号(Bug修复或优化)-版本阶段(base、alpha、beta、RC、release)
 * 上一级版本号变动时下级版本号归零
 **/
define('DYPHP_VERSION', '2.12.0-release');

/**
 * 框架入口
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

        //加载web防火墙
        if ($waf) {
            require DYPHP_PATH.self::$coreClasses['DyPhpWaf'];
            new DyPhpWaf();
        }

        //运行自动登录逻辑
        self::app()->auth->runAutoLogin();
        DyPhpRoute::runWeb();
        exit;
    }

    /**
     * 运行console app入口
     * 
     * @param array app配制
     * @param boolean 是否开启debug
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
     * 
     * @param array app配制
     * @param boolean 是否开启debug
     * @param string  app类型
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
        require DYPHP_PATH.self::$coreClasses['DyPhpController'];
        require DYPHP_PATH.self::$coreClasses['DyPhpView'];
        require DYPHP_PATH.self::$coreClasses['DyPhpHooks'];

        //web应用加载用户身份认证
        if ($appType == 'web') {
            require DYPHP_PATH.self::$coreClasses['DyPhpUserIdentity'];
        }

        //异常拦截注册
        self::debug($debug);
        
        //配制解析
        DyPhpConfig::runConfig($config);
    }

    /**
     * 调用自定义信息提示句柄
     * 
     * @param array  按提示需要随意传值
     **/
    public static function showMsg($params = array())
    {
        $params = is_array($params) ? $params : (array)$params;
        self::app()->setPreInsAttr($params);

        $msgHandler = explode('/', trim(DyPhpConfig::item('messageHandler'), '/'));
        DyPhpController::run($msgHandler[0], $msgHandler[1], $params);
    }

    /**
     * 自动加载
     * 
     * @param string 类名
     **/
    public static function autoload($className)
    {
        if (isset(self::$coreClasses[$className])) {
            //自动加载框架文件
            require DYPHP_PATH.self::$coreClasses[$className];
        } elseif ($importClass = DyPhpConfig::getImport($className)) {
            //自动加载配制的包含文件
            require $importClass;
        }elseif ($alias = DyPhpConfig::getAliasMap($className)) {
            //别名加载及设置
            if (!class_exists($alias['name'], false)) {
                require $alias['file'];
            }
            class_alias($alias['name'], $className, false);
        } elseif (($lastNsPos = strrpos($className, '\\')) !== false) {
            //namespace自动加载, 允许自定义根目录(在配制文件中设置namespaceRoot，默认在DyPhpConfig::item('appPath'))
            //规则：命名空间名及文件名，必须与路径及类名相同，否则无法加载，抛出“类不存在”异常
            //注意：如不符合规则，则需要实现自己的autoload,并调用autoloadRegister注册到框架,常见于集成第三方组件
            $classPathFile = DyPhpConfig::item('namespaceRoot').DIRECTORY_SEPARATOR.str_replace('\\',DIRECTORY_SEPARATOR, $className).EXT;
            if (file_exists($classPathFile)) {
                require $classPathFile;
            }
        } else {
            //在项目包含目录中遍历查找文件
            foreach (DyPhpConfig::getIncludePath() as $key => $val) {
                $autoClassFile = $val.$className.EXT;
                if (is_file($autoClassFile)) {
                    require $autoClassFile;
                    break;
                }
            }
        }

        if (!class_exists($className, false) && !interface_exists($className, false) && !trait_exists($className, false)) {
            self::throwException('Class does not exist', $className);
        }
    }

    /**
     * 注册自动加载
     * 主要用于引入需要自实现自动加载的第三方组件
     * 
     * @param  mixed  $autoload 自动加载函数
     * @param  bool   $prepend  如果是 true，spl_autoload_register() 会添加函数到队列之首，而不是队列尾部。php5.3后支持
     **/
    public static function autoloadRegister($callback, $prepend = true)
    {
        spl_autoload_register($callback, true, $prepend);
    }

    /**
     * 运行时间
     * 
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
     * 全局实例器
     * 
     * @return object DyPhpApp实例
     **/
    public static function app()
    {
        if (self::$dyApp) {
            return self::$dyApp;
        } else {
            self::$dyApp = new DyPhpApp();
            return self::$dyApp;
        }
    }

    /**
     * 获取Powered by
     * 
     * @param bool false时将返回不带连接的powered by
     * @return string
     **/
    public static function powerBy($link = true)
    {
        return $link ? 'Powered By <a href="http://www.dyphp.com" target="_blank">DYPHP-Framework '.DYPHP_VERSION.'</a>' : 'Powered By DYPHP-Framework '.DYPHP_VERSION;
    }

    /**
     * 异常,错误捕获
     * 详见DyPhpException类
     * 
     * @param bool  $debug为true时直接输出异常运行跟踪 , 为false时自动调用elf::$errorHandler返回错误信息
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
     * 
     * @param string  自定义出错信息
     * @param string  系统异常，自定义异常信息
     * @param string  异常类型
     * @param object  前一个异常，现行版本只有数据库操作抛出异常，给应用一次catch的机会，方便进行业务处理
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
            throw new Exception($prefix.' '.$excMessage, (int)$code, $previous);
        } else {
            $dyExce = new DyPhpException($prefix.' '.$excMessage, $code, $previous);
            $dyExce->appErrorHandler();
        }

        exit;
    }

    /**
     * 加载框架类
     * 
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
            'DyDebug'=>'/dyphp/lib/DyDebug.php',

            //utils
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
     * 框架支持检查
     * 
     * @param bool 是否执行exit(),默认执行
     * @return  null
     **/
    public static function supportCheck($exit = true)
    {
        //'$_SERVER $_FILES $_COOKIE $_SESSION  | GD pdo_mysql PDO mbstring iconv openssl'
        $br = PHP_SAPI == 'cli' ? PHP_EOL : '</br>';
        $splitLine = $br.str_repeat('-', 60).$br;

        echo $br.'[Framework limit]';
        echo $splitLine;
        echo 'php current version:'.PHP_VERSION.' status: '.(version_compare(PHP_VERSION, '5.3.0', '>=') ? '√ OK' : '× minimum version of 5.3.0');
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

        echo $br.$br;

        if($exit){
            exit;
        }
    }
}

/**
 * app模式
 * 
 * @example  DyPhpBase::app()->attribute , DyPhpBase::app()->method(params)
 **/
final class DyPhpApp
{
    /* controller实例全局属性 */
    //调用的module
    public $module = '';
    //调用的module/controller
    public $pcid = '';
    //调用的controller
    public $cid = '';
    //调用的action
    public $aid = '';

    //当前运行的controller实例
    public $runingController = null;

    //注册实例及别名实例单例存储器
    private $instanceArr = array();

    //加载vendors单例存储器
    private $vendorsIncOnce = array();

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
     * 实例注册
     * 
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
     * 实例单例处理
     * 
     * @param     注册名
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

        //不做单例处理, DyDbCriteria不可强制单例，单例会出现数据叠加错误
        if ($name == 'dbc') {
            return new $alias['name'];
        }

        $this->instanceArr[$name] = new $className;
        return $this->instanceArr[$name];
    }

    /**
     * 加载vendors
     * 
     * @param string  vendors 相对路径及文件名(无后缀，注意有些vendor加载时引入的是autoload文件),不支持"xx.yy.zz"格式(组件文件名中可能有".")
     * @param bool    true为加载框架已集成的vendor,false为加载app中引入的的vendor
     * 
     * @example DyPhpBase::app()->vendors('PHPMailer/PHPMailerAutoload', true);
     */
    public function vendors($filePathName, $isSys = false)
    {
        $type = $isSys === true ? 'dyphp' : 'app';

        $onceName = $type.'_'.$filePathName;
        if (in_array($onceName, $this->vendorsIncOnce)) {
            return;
        }

        $vendor = $type == 'app' ? DyPhpConfig::item('appPath').'/vendors/'.$filePathName.EXT : DYPHP_PATH.'/dyphp/vendors/'.$filePathName.'.php';
        if (!file_exists($vendor)) {
            DyPhpBase::throwException('vendor does not exist', $vendor);
        }

        $this->vendorsIncOnce[] = $onceName;
        require $vendor;
    }
    
    /**
     * 引入包含文件
     * 
     * @param string $path 要引入文件的路径(与配制文件中的import设置相同格式)
     * 
     * @example Dy::app()->import('app.utils.functions');
     **/
    public static function import($path)
    {
        DyPhpConfig::loadFile($path);
    }

    /**
     * 对调用方参数追加preInstance
     * 用于DyPhpException重定向到errorHandler之前追加调用堆栈上一级来源
     *
     * @param array $paramArr
     * 
     * @return bool
     */
    public function setPreInsAttr(&$paramArr)
    {
        $preInsAttr = array(
            'pre_module'=>$this->module,
            'pre_pcid'=>$this->pcid,
            'pre_cid'=>$this->cid,
            'pre_aid'=>$this->aid,
        );

        $paramArr['preInstance'] = $preInsAttr;
        return true;
    }

}


/**
 * 初始化
 */

//注册自动加载
spl_autoload_register(array('DyPhpBase', 'autoload'));

//设置别名
class_alias('DyPhpBase', 'Dy', false);
