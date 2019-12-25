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

//设置别名
class_alias('DyPhpConfig', 'DyCfg', false);

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
    //命名空间根目录
    private static $namespaceRoot = '';

    /**
     * 运行app配制入口
     *
     * @param string|array app配制
     **/
    public static function runConfig($appConfig)
    {
        $config = self::parseConfig($appConfig);
        if (!is_array($config)) {
            DyPhpBase::throwException('config is not an array');
        }

        //异常信息输出语言 现只支持zh_cn
        self::$language = isset($config['language']) ? $config['language'] : 'zh_cn';

        //检查app密钥，用于cookie,session,string等加密使用，不同应用此密钥应唯一
        if (!array_key_exists('secretKey', $config) || empty($config['secretKey'])) {
            DyPhpBase::throwException('secretKey Undefined');
        }

        //运行环境dev(开发)、test(测试)、pre(预发布)、pro(发布)，用于加载不同环境的constants文件;为secretKey加环境后缀（当前版本只有这两个用途）
        if (array_key_exists('env', $config) && !in_array($config['env'], array('dev','test','pre','pro'))) {
            DyPhpBase::throwException('run environment defined invalid');
        }

        //获取app根目录并设置常量
        if (!array_key_exists('appPath', $config)) {
            DyPhpBase::throwException('appPath Undefined');
        }
        self::$appPath = rtrim(realpath($config['appPath']), '/');
        define('APP_PATH', self::$appPath);
        define('APP_PARENT_PATH', dirname(self::$appPath));

        //解析并设置namespaceRoot，默认根目录在appPath下, autoload将会调用
        self::$namespaceRoot = array_key_exists('namespaceRoot', $config) ? rtrim(realpath($config['namespaceRoot']), '/') : self::$appPath;

        //获取url层级地址，以支持将应用部署到web根目录的子目录下，DyRequest::createUrl()、DyPhpRoute::urlCrop()方法中使用
        self::$appHttpPath = isset($_SERVER["SCRIPT_NAME"]) ? trim(str_replace(array('\\','\\\\','//'), '/', dirname($_SERVER["SCRIPT_NAME"])), '/') : trim(str_replace($_SERVER['DOCUMENT_ROOT'], "", dirname($_SERVER['SCRIPT_FILENAME'])), '/');

        //初始化handler，设置默认值
        //建议：按console，web类型使用不同的入口文件，引用不同的配制文件，在配制中分别配制
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
                $isType = $cnfSearch <= 7 ? is_array($value) : is_string($value);
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

        //秘钥长度不可小于32位
        if (strlen(self::$secretKey) < 32) {
            DyPhpBase::throwException('the secretKey length is no less than 32', 'secretKey');
        }
        //为secretKey加上环境后缀,自动按环境生成不同的secretKey
        self::$secretKey = self::$secretKey.'_'.self::$env;

        //生成app唯一id,在session key的前缀中使用到,解决多应用session冲突（当前版本只有这一个用途）
        self::$appID = md5(self::$secretKey);

        //包含类或路径加载
        self::import();

        //类别名配制解析
        self::aliasMap();

        //常量配制加载
        self::loadConstants();
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
        //重写排除项, 只能使用父级配制（父级未配制，子级配制了，配制项将不生效）
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
            if (isset($parentConfig[$rewriteExcludeKeys])) {
                $excludeConfig = (array)$parentConfig[$rewriteExcludeKeys];
                unset($parentConfig[$rewriteExcludeKeys]);
            }
            if (isset($childConfig[$rewriteExcludeKeys])) {
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
            if (isset($config[$rewriteExcludeKeys])) {
                unset($config[$rewriteExcludeKeys]);
            }
        }

        return $config;
    }

    /**
     * 解析app配制中包含的类及路径，autoload将调用该配制
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
    private static function import()
    {
        $appImport = array(
            'app.models.*',
            'app.components.*',
        );

        $pathArr = array_unique(array_merge($appImport, self::$import));
        self::$import = array();

        foreach ($pathArr as $importPath) {
            $path = self::getRealPath($importPath);

            //解析类文件
            if (substr($path, -1) === '*') {
                //解析包含路径
                $incPath = rtrim($path, '*');
                if (is_dir($incPath) && !in_array($incPath, self::$includePath)) {
                    self::$includePath[] = $incPath;
                }
            } else {
                $file = $path.EXT;
                if (!file_exists($file)) {
                    DyPhpBase::throwException('file does not exist', $file);
                }

                $className = substr($importPath,strrpos($importPath, '.')+1);
                if (!isset(self::$import[$className])) {
                    self::$import[$className] = $file;
                }
            }
        }

        if (self::$includePath) {
            set_include_path(implode(PATH_SEPARATOR, self::$includePath) . PATH_SEPARATOR . get_include_path());
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
    private static function aliasMap()
    {
        self::setPathOfAlias('dysys', DYPHP_PATH);
        $loadAlias = array(
            'captcha'=>'dysys.dyphp.lib.DyCaptcha',
            'dbc'=>'dysys.dyphp.db.DyDbCriteria',
            'hook'=>'dysys.dyphp.base.DyPhpHooks',
            //'hook'=>'dysys.dyphp.base.DyPhpHooks',
        );

        //仅web类型项目自动加载用户验证组件
        if (DyPhpBase::$appType == 'web') {
            $loadAlias['auth'] = 'app.components.UserIdentity';
        }

        $aliasArr = array_unique(array_merge($loadAlias, self::$aliasMap));
        self::$aliasMap = array();

        foreach ($aliasArr as $key=>$path) {
            $file = self::getRealPath($path).EXT;

            //将别名原类导入到包含类中，实现直接调用原类时也能自动加载
            $className = substr($path,strrpos($path, '.')+1);
            if (!isset(self::$import[$className])) {
                self::$import[$className] = $file;
            }

            if (!file_exists($file)) {
                DyPhpBase::throwException('file does not exist', $file);
            }
            self::$aliasMap[$key] = $file;
        }
    }

    /**
     * 加载常量配制, 必须在config路径下
     * constants 非必须文件 不存在就不加载 不会给出报错信息
     **/
    private static function loadConstants()
    {
        $constants = self::$appPath.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.self::$env.'_constants'.EXT;
        if (file_exists($constants)) {
            require $constants;
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
     * 设置自定义别名包, 需注意路径访问权限及安全
     * @param string 别名
     * @param string 路径,需要传入绝对路径
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
     * @param    string  类别名
     * @return   array
     **/
    public static function getAliasMap($aliasName)
    {
        if (!isset(self::$aliasMap[$aliasName])) {
            return false;
        }

        //返回类原名及类文件
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
        if (self::$exceptionLogRootDir == '') {
            $defaultDir = rtrim(self::$appPath, DIRECTORY_SEPARATOR);
            return $defaultDir == '' ? 'logs'.DIRECTORY_SEPARATOR : $defaultDir.DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR;
        } else {
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
     * 获取自定义包含路径，autoload将调用该方法，路径在import中设置
     * @return   string
     **/
    public static function getIncludePath()
    {
        return self::$includePath;
    }

    /**
     * 引入包含文件
     * @return   string
     **/
    public static function loadFile($path)
    {
        $path = self::getRealPath($path);

        //解析类文件
        $file = $path.EXT;
        if (!file_exists($file)) {
            DyPhpBase::throwException('file does not exist', $file);
        }
        
        require $file;
    }
}
