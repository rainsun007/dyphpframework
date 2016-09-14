<?php
/**
 * 控制器类.
 *
 * @author 大宇 Email:dyphp.com@gmail.com
 *
 * @link http://www.dyphp.com/
 *
 * @copyright Copyright 2011 dyphp.com
 **/
class DyPhpController
{
    //未登陆跳转地址
    protected $loginUri = '';
    //设置所有action未登陆禁止访问 为true时needLogin方法将无效（loginUri属性不受限制）
    protected $allNeedLogin = false;
    //handler跳过init beforeAction hooks的执行, 设置为false如应用中有handler循环调用逻辑必须自己处理跳过逻辑
    protected $handlerPass = true;

    //默认使用的action
    protected $defaultAction = DYPHP_DEFAULT_ACTION;
    //当前运行的module,controller,action
    protected $cid = '';
    protected $aid = '';
    protected $pcid = '';
    protected $module = '';

    //action调用自定义
    protected $actionParam = array();
    //当前时间戳
    protected $time = 0;
    //当前完整日期时间（Y-m-d H:i:s）
    protected $datetime = '1970-01-01 08:00:00';
    //当前完整日期（Y-m-d）
    protected $date = '1970-01-01';

    //controller单例控制
    private static $incController = array();
    //view实例
    public $view;

    /**
     * @brief   在beforeAction之前执行,可以重写此方法实现自己的业务逻辑
     *
     * @return
     **/
    protected function init()
    {
    }

    /**
     * @brief   在action之前执行,可以重写此方法实现自己的业务逻辑
     *
     * @return
     **/
    protected function beforeAction()
    {
    }

    /**
     * 未登陆禁止访问的action 必须全部小写.
     **/
    protected function needLogin()
    {
        return array();
    }

    /**
     * 运行controller入口
     * 支持系统内部直接调用本方法
     * 支持url以module_controller_action或module/controller/action的格式调用.
     *
     * @param string controller
     * @param string action
     * @param array  参数
     **/
    final public static function run($controllerPname, $action = '', $params = array())
    {
        $controllerRun = self::parseController($controllerPname);
        $actionName = self::parseAction($controllerRun, $action);

        //时间属性只为开发使用方便
        $controllerRun->time = time();
        $controllerRun->datetime = date('Y-m-d H:i:s', $controllerRun->time);
        $controllerRun->date = date('Y-m-d', $controllerRun->time);

        //参数设置
        $controllerRun->actionParam = $params;
        //view实例化
        $controllerRun->view = new DyPhpView();

        //handler不执行以下逻辑（防止统一拦截处理时进入重定向死循环）
        $handlers = array(trim(DyPhpConfig::item('errorHandler'), '/'), trim(DyPhpConfig::item('messageHandler'), '/'), trim(DyPhpConfig::item('loginHandler'), '/'));
        if (!$controllerRun->handlerPass || !in_array($controllerRun->cid.'/'.$controllerRun->aid, $handlers)) {
            //init运行
            $controllerRun->init();
            //hook调用
            DyPhpBase::app()->hook->invokeHook('before_action');
            //beforeAction运行
            $controllerRun->beforeAction();
        }

        //action执行
        $controllerRun->$actionName();
    }

    /**
     * @brief    首字母小写
     *
     * @param   $string
     *
     * @return
     **/
    protected static function lcfirst($string)
    {
        if (false === function_exists('lcfirst')) {
            $string = (string) $string;
            if (empty($string)) {
                return '';
            }
            $string{0} = strtolower($string{0});

            return $string;
        } else {
            return lcfirst($string);
        }
    }

    /**
     * @brief    解析action
     *
     * @param   $controllerRun controller实例
     * @param   $action
     *
     * @return
     **/
    private static function parseAction($controllerRun, $action)
    {
        //action 解析
        $actionNameStr = $action ? $action : $controllerRun->defaultAction;
        $actionName = 'action'.ucfirst($actionNameStr);
        if (!method_exists($controllerRun, $actionName)) {
            DyPhpBase::throwException('action does not exist', $actionName);
        }
        $controllerRun->aid = self::lcfirst($actionNameStr);
        DyPhpBase::app()->aid = $controllerRun->aid;

        //need login处理 判断返回值类型  对必须登录才可以访问的方法进行验证与拦截
        if (!is_array($controllerRun->needLogin())) {
            DyPhpBase::throwException('needLogin method error');
        }

        //重定向到登录页
        $loginUri = empty($controllerRun->loginUri) ? DyPhpConfig::item('loginHandler') : $controllerRun->loginUri;
        if ($controllerRun->cid.'/'.$controllerRun->aid != trim($loginUri, '/') && DyPhpBase::app()->auth->isGuest() && ($controllerRun->allNeedLogin || in_array(ucfirst($actionNameStr), $controllerRun->needLogin()))) {
            DyRequest::redirect($loginUri);
        }

        return $actionName;
    }

    /**
     * @brief    解析controller
     *
     * @param   $controllerPname
     *
     * @return
     **/
    private static function parseController($controllerPname)
    {
        //controller 解析
        $controllerPname = trim(str_replace('_', '/', $controllerPname), '/');
        $pos = strrpos($controllerPname, '/');
        $controllerName = $pos === false ? ucfirst($controllerPname) : ucfirst(substr($controllerPname, $pos + 1));
        $controllerPath = $pos === false ? '' : substr($controllerPname, 0, $pos + 1);
        $controller = $controllerName.'Controller';
        $controllerFile = DyPhpConfig::item('appPath').DIRECTORY_SEPARATOR.'controllers'.DIRECTORY_SEPARATOR.$controllerPath.$controller.EXT;
        if (!file_exists($controllerFile)) {
            DyPhpBase::throwException('controller does not exist', $controllerName);
        }

        //controller文件（引用）单例加载
        if (!in_array($controller, self::$incController)) {
            include $controllerFile;
            self::$incController[] = $controller;
        }
        $controllerRun = new $controller();

        //设置全局公共属性
        $controllerRun->cid = self::lcfirst($controllerName);
        $controllerRun->pcid = self::lcfirst($controllerPname);
        $controllerRun->module = rtrim(self::lcfirst($controllerPath), '/');
        DyPhpBase::app()->cid = $controllerRun->cid;
        DyPhpBase::app()->pcid = $controllerRun->pcid;
        DyPhpBase::app()->module = $controllerRun->module;
        DyPhpBase::app()->runingController = $controllerRun;

        return $controllerRun;
    }

    public function __call($key, $Args)
    {
        DyPhpBase::throwException('method does not exist', $key);
    }
}
