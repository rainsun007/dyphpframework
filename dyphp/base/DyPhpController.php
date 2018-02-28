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
    //默认使用的action
    protected $defaultAction = DYPHP_DEFAULT_ACTION;
    //action调用自定义
    protected $actionParam = array();

    //未登陆跳转地址
    protected $loginHandler = '';
    //设置所有action未登陆禁止访问 为true时needLogin方法将无效（loginHandler属性不受限制）
    protected $allNeedLogin = false;
    //设置为true时handler调用将跳过init，beforeAction，hooks的执行, 设置为false应用中如有handler的循环调用，必须自己处理跳过逻辑
    protected $handlerPass = false;

    //当前运行的module,controller,action 首字母为小写
    protected $cid = '';
    protected $aid = '';
    protected $pcid = '';
    protected $module = '';

    //当前时间戳
    protected $time = 0;
    //当前完整日期时间（Y-m-d H:i:s）
    protected $datetime = '1970-01-01 08:00:00';
    //当前完整日期（Y-m-d）
    protected $date = '1970-01-01';

    //controller单例控制
    private static $incController = array();
    //DyPhpView实例
    public $view;

    /**
     * 在beforeAction之前执行,可以重写此方法实现自己的业务逻辑
     **/
    protected function init()
    {
    }

    /**
     * 在action之前执行,可以重写此方法实现自己的业务逻辑
     **/
    protected function beforeAction()
    {
    }

    /**
     * 未登陆禁止访问的action 首字母为小写.
     * @return array
     **/
    protected function needLogin()
    {
        return array();
    }

    /**
     * 运行controller入口
     * 支持直接调用本方法 但不建议直接使用如有转发需求可使用forward方法.
     *
     * @param string controller  以module_controller或module/controller的格式调用
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

        //hook调用: controller实例化之后执行，必须登录的action如未登录，此hook不执行
        DyPhpBase::app()->hook->invokeHook('after_controller_constructor');

        //handler不执行以下逻辑（防止统一拦截处理时进入重定向死循环）
        $handlers = array(trim(DyPhpConfig::item('errorHandler'), '/'), trim(DyPhpConfig::item('messageHandler'), '/'), trim(DyPhpConfig::item('loginHandler'), '/'), trim($controllerRun->loginHandler, '/'));
        if (!$controllerRun->handlerPass || !in_array($controllerRun->pcid.'/'.$controllerRun->aid, $handlers)) {
            //init运行
            $controllerRun->init();
            //beforeAction运行
            $controllerRun->beforeAction();
        }

        //action执行
        $controllerRun->$actionName();

        //hook调用: action执行完成之后
        DyPhpBase::app()->hook->invokeHook('after_action');
    }

    /**
     * 请求转发 可以理解为run方法的别名 区别在于必须继承了本类才可使用.
     *
     * @param string controller 以module_controller或module/controller的格式调用
     * @param string action
     * @param array  参数
     **/
    final protected function forward($controllerPname = '', $action = '', $params = array())
    {
        if (empty($controllerPname)) {
            DyPhpBase::throwException('controller does not exist');
        }
        if (empty($action)) {
            DyPhpBase::throwException('action does not exist');
        }
        self::run($controllerPname, $action, $params);
    }

    /**
     * @brief    解析action
     *
     * @param   string $controllerRun controller实例
     * @param   string $action
     *
     * @return  string
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
        $loginHandler = empty($controllerRun->loginHandler) ? DyPhpConfig::item('loginHandler') : $controllerRun->loginHandler;
        if ($controllerRun->pcid.'/'.$controllerRun->aid != trim($loginHandler, '/') && DyPhpBase::app()->auth->isGuest() && ($controllerRun->allNeedLogin || in_array($controllerRun->aid, $controllerRun->needLogin()))) {
            DyRequest::redirect($loginHandler);
        }

        return $actionName;
    }

    /**
     * @brief    解析controller
     *
     * @param   string $controllerPname
     *
     * @return  string
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

    /**
     * @brief    首字母小写
     *
     * @param  string $string
     *
     * @return string
     **/
    private static function lcfirst($string)
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

    public function __call($key, $Args)
    {
        DyPhpBase::throwException('method does not exist', $key);
    }
}
