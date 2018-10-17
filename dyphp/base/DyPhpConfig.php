<?php
/**
 * 配制处理
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright dyphp.com
 **/

//默认controller配制
defined('DYPHP_DEFAULT_CONTROLLER') or define('DYPHP_DEFAULT_CONTROLLER', 'app');
//默认action配制
defined('DYPHP_DEFAULT_ACTION') or define('DYPHP_DEFAULT_ACTION', 'index');

//简单别名
class DyCfg extends DyPhpConfig
{
}

class DyPhpConfig
{
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
    //异常log保存的根目录(必须保证要有写入权限)
    private static $exceptionLogRootDir = '';
    //url重写管理
    private static $urlManager = array();
    //数据库配制
    private static $db = array();
    //缓存配制
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
    //用户自定义配制
    private static $userConfig = array();

    /**
     * 运行app配制入口
     *
     * @param string|array app配制
     **/
    public static function runConfig($appConfig)
    {
        $config = self::parseConfig($appConfig);

        //language load  异常信息输出语言 现只支持zh_cn
        self::$language = isset($config['language']) ? $config['language'] : 'zh_cn';

        //check app config
        if (!is_array($config)) {
            DyPhpBase::throwException('config is not an array');
        }

        //check secretKey
        //app密钥 cookie,session,string等加密使用  不同应用此密钥应唯一
        if (!array_key_exists('secretKey', $config) || empty($config['secretKey'])) {
            DyPhpBase::throwException('secretKey Undefined');
        }

        //check environment
        //运行环境，用于加载不同环境的constants文件;为secretKey加环境后缀（当前版本只有这两个用途）
        if (array_key_exists('env', $config) && !in_array($config['env'], array('dev','test','pro','pre',''))) {
            DyPhpBase::throwException('run environment defined invalid');
        }

        //check appPath and setting define
        if (!array_key_exists('appPath', $config)) {
            DyPhpBase::throwException('appPath Undefined');
        }
        self::$appPath = rtrim(realpath($config['appPath']), '/');
        define('APP_PATH', self::$appPath);
        define('APP_PARENT_PATH', dirname(APP_PATH));

        //获取url层级地址，以支持将应用部署到web根目录下的子目录下，DyRequest::createUrl()、DyPhpRoute::urlCrop()方法中使用
        self::$appHttpPath = isset($_SERVER["SCRIPT_NAME"]) ? trim(str_replace(array('\\','\\\\','//'), '/', dirname($_SERVER["SCRIPT_NAME"])), '/') : trim(str_replace($_SERVER['DOCUMENT_ROOT'], "", dirname($_SERVER['SCRIPT_FILENAME'])), '/');

        //初始化handler
        //建议方案一: 按console，web类型，配合DYPHP_DEFAULT_CONTROLLER定义 在action中做不同处理
        //建议方案二: 按console，web类型在不同的入口文件中引用不同的配制文件，在配制中分别配制
        self::$errorHandler = DYPHP_DEFAULT_CONTROLLER.'/error';
        self::$loginHandler = DYPHP_DEFAULT_CONTROLLER.'/login';
        self::$messageHandler = DYPHP_DEFAULT_CONTROLLER.'/message';

        //校验及设置配制属性
        $configCheckArr = array(
            'db','cache','cookie','urlManager','params','import','aliasMap','hooks',
            'errorHandler','messageHandler','loginHandler','exceptionLogRootDir','appName','secretKey','env'
        );
        foreach ($config as $key => $value) {
            $cnfSearch = array_search($key, $configCheckArr);
            if ($cnfSearch !== false) {
                //系统规定的配制数据类型只能是array或string
                $isType = $cnfSearch<=7 ? is_array($value) : is_string($value);
                if (!$isType) {
                    DyPhpBase::throwException('data type error', $key);
                }
                //系统属性设置
                self::${$key} = $value;
            } else {
                //用户自定义配制设置，可自由定义key，对value数据类型无限制
                self::$userConfig[$key] = $value;
            }
        }
        unset($config);

        //为secretKey加上环境后缀,自动按环境生成不同的secretKey
        if (strlen(self::$secretKey) < 32) {
            DyPhpBase::throwException('the secretKey length is no less than 32', 'secretKey');
        }
        self::$secretKey = empty(self::$env) ? self::$secretKey.'_pro' : self::$secretKey.'_'.self::$env;

        //生成app唯一id,在session key的前缀中使用到,解决多应用session冲突（当前版本只有这一个用途）
        self::$appID = md5(self::$secretKey);

        //import加载
        self::import(self::$import);

        //aliasMap加载
        self::aliasMap(self::$aliasMap);

        //加载配制及工具
        self::loadCommon();
    }

    /**
     * 配制文件解释
     *
     * @param string|array app配制
     * 
     * @return array
     **/
    private static function parseConfig($appConfig)
    {
        $rewriteExcludeKeys = 'rewrite_exclude_keys';
        if (is_array($appConfig)) {
            if (!isset($appConfig['p']) || !isset($appConfig['c'])) {
                DyPhpBase::throwException('config key is not exists', '"p" or "c"');
            } elseif (!file_exists($appConfig['p']) || !file_exists($appConfig['c'])) {
                DyPhpBase::throwException('config file is not exists', '"p" or "c"');
            }
            
            $parentConfig = require $appConfig['p'];
            $childConfig = require $appConfig['c'];

            $excludeConfig = array();
            if(isset($parentConfig[$rewriteExcludeKeys])){
                $excludeConfig = (array)$parentConfig[$rewriteExcludeKeys];
                unset($parentConfig[$rewriteExcludeKeys]);
            }
            if(isset($childConfig[$rewriteExcludeKeys])){
                unset($childConfig[$rewriteExcludeKeys]);
            }

            //如果配制了重写排除项, 那么只能使用父级配制，即使父级未配制而子级配制了也不会生效
            $config = array_merge((array)$parentConfig, (array)$childConfig);
            foreach ($excludeConfig as $key => $value) {
                if (isset($parentConfig[$value])) {
                    //使父配制中的设置生效
                    $config[$value] = $parentConfig[$value];
                } elseif (isset($config[$value])) {
                    //设置的排除项如父配制中未配制，子配制中配制了也无效
                    unset($config[$value]);
                }
            }
        } else {
            if (!file_exists($appConfig)) {
                DyPhpBase::throwException('config file is not exists', $appConfig);
            }

            $config = require $appConfig;
            if(isset($config[$rewriteExcludeKeys])){
                unset($config[$rewriteExcludeKeys]);
            }
        }

        return $config;
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
    private static function import($pathArr)
    {
        $appImport = array(
            'app.models.*',
            'app.components.*',
        );
        $pathArr = array_unique(array_merge($appImport, $pathArr));

        foreach ($pathArr as $path) {
            $path = self::getRealPath($path);

            if (substr($path, -1) !== '*') {
                $file = $path.EXT;
                if (!file_exists($file)) {
                    DyPhpBase::throwException('file does not exist', $file);
                }

                $className = substr($path, strrpos($path, DIRECTORY_SEPARATOR));
                if(!isset(self::$import[$className])){
                    self::$import[$className] = $file;
                }

                continue;
            }

            $incPath = rtrim($path, '*');
            if (is_dir($incPath) && !in_array($incPath,self::$includePath)) {
                self::$includePath[] = $incPath;
            }
        }

        if (self::$includePath) {
            set_include_path(get_include_path() . PATH_SEPARATOR . implode(PATH_SEPARATOR, self::$includePath));
        }
    }

    /**
     * 别名映射处理，autoload将调用该配制
     *
     * @param  array
     * 实例
     * 'aliasMap' => array(
     *       'captcha'=>'dysys.dyphp.lib.DyCaptcha',
     *       'dbc'=>'dysys.dyphp.db.DyDbCriteria',
     *       'hook'=>'dysys.dyphp.base.DyPhpHooks',
     *       'auth'=>'app.components.UserIdentity',
     *  );
     **/
    private static function aliasMap($aliasArr)
    {
        self::setPathOfAlias('dysys', DYPHP_PATH);
        $loadAlias = array(
            'captcha'=>'dysys.dyphp.lib.DyCaptcha',
            'dbc'=>'dysys.dyphp.db.DyDbCriteria',
            'hook'=>'dysys.dyphp.base.DyPhpHooks',
        );

        //仅web类型项目自动加载用户验证组件
        if (DyPhpBase::$appType == 'web') {
            $loadAlias['auth'] = 'app.components.UserIdentity';
        }

        $aliasArr = array_unique(array_merge($loadAlias, $aliasArr));
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
    private static function getRealPath($path)
    {
        if (strpos($path, 'app') === 0) {
            $path = strtr($path, array('app' => self::$appPath, '.' =>DIRECTORY_SEPARATOR));
        } else {
            $alias = substr($path, 0, strpos($path, '.'));
            if (!isset(self::$pathOfAlias[$alias])) {
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
    public static function setPathOfAlias($alias, $path)
    {
        self::$pathOfAlias[$alias] = $path;
    }

    /**
     * 获取import文件路径
     * @param  string 类名
     * @return array
     **/
    public static function getImport($className)
    {
        return isset(self::$import[$className]) ? self::$import[$className] : false;
    }

    /**
     * 获取映射，autoload将调用该方法
     * @param    string  别名键值
     * @return   array
     **/
    public static function getAliasMap($aliasName)
    {
        if (!isset(self::$aliasMap[$aliasName])) {
            return false;
        }
        return array('name'=>basename(self::$aliasMap[$aliasName], EXT), 'file'=>self::$aliasMap[$aliasName]);
    }

    /**
     * 获取配制项
     * @param    string  配制键值
     * @return   mixed
     **/
    public static function item($itemName)
    {
        if (isset(self::${$itemName})) {
            return self::${$itemName};
        } elseif (isset(self::$userConfig[$itemName])) {
            return self::$userConfig[$itemName];
        }
        DyPhpBase::throwException('config does not exist', $itemName);
    }

    /**
     * 获取异常log存储根目录
     *
     * @return string
     */
    public static function getExceptionLogRootDir()
    {
        if(self::$exceptionLogRootDir == ''){
            return rtrim(APP_PATH, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR;
        }else{
            return rtrim(self::$exceptionLogRootDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        }
    }

    /**
     * 获取是否隐藏index.php设置
     * @return bool
     **/
    public static function getHideIndex()
    {
        $urlmanager = self::$urlManager;
        return isset($urlmanager['urlStyle']['hideIndex']) && $urlmanager['urlStyle']['hideIndex'] == 'yes' ? true : false;
    }

    /**
     * 获取url是否以rest风格访问
     * @return bool
     **/
    public static function getRestCa()
    {
        $urlmanager = self::$urlManager;
        return isset($urlmanager['urlStyle']['restCa']) && $urlmanager['urlStyle']['restCa'] == 'yes' ? true : false;
    }

    /**
     * 获取自定义参数
     * @param string
     * @return mixed
     **/
    public static function getParams($param)
    {
        return !empty(self::$params[$param]) ? self::$params[$param] : null;
    }

    /**
     * 获取自定义包含路径，autoload将调用该方法
     * @return   string
     **/
    public static function getIncludePath()
    {
        return self::$includePath;
    }

    /**
     * 加载配制及工具
     **/
    private static function loadCommon()
    {
        //constants 非必须文件 不存在就不加载 不会给出报错信息
        $constants = empty(self::$env) ? 'constants' : self::$env.'_constants';
        $constants = self::$appPath.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.$constants.EXT;
        if (file_exists($constants)) {
            require $constants;
        }

        //functions 非必须文件 不存在就不加载 不会给出报错信息
        $funs = self::$appPath.DIRECTORY_SEPARATOR.'utils'.DIRECTORY_SEPARATOR.'functions'.EXT;
        if (file_exists($funs)) {
            require $funs;
        }
    }
}
