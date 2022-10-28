<?php
/**
 * 控制器类.
 *
 * @author 大宇 Email:dyphp.com@gmail.com
 *
 * @link http://www.dyphp.com/
 *
 * @copyright Copyright dyphp.com
 **/
class DyPhpController
{
    //run方法 和 forward方法 所传递的参数，在init方法执行前就已设置生效
    protected $caParam = array();

    //未登陆跳转地址
    protected $loginHandler = '';

    //设置所有action未登陆禁止访问 为true时needLogin方法将无效（loginHandler属性不受限制）
    protected $allNeedLogin = false;

    //action是否使用请求方法与action名做强一致验证
    protected $actionHttpMethodMode = false;

    //当前运行的module,controller,module/controller,action名，首字母为小写
    protected $module = '';
    protected $cid = '';
    protected $pcid = '';
    protected $aid = '';

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
     * 未登陆禁止访问的action 首字母需小写.
     * @return array
     **/
    protected function needLogin()
    {
        return array();
    }

    /**
     * 运行controller入口
     * 支持直接调用本方法 但不建议直接使用如有重定向需求可使用forward方法.
     *
     * @param string controller类名,支持module_controller或module/controller的格式调用
     * @param string action的方法名
     * @param array  当前运行controller中可公用的参数（异常信息，提示信息等）
     **/
    final public static function run($controllerPathName, $action = '', $params = array())
    {
        //controller分析及实例化
        $controllerRun = self::parseController($controllerPathName);

        //时间属性只为开发使用方便
        $controllerRun->time = time();
        $controllerRun->datetime = date('Y-m-d H:i:s', $controllerRun->time);
        $controllerRun->date = date('Y-m-d', $controllerRun->time);

        //参数设置
        $controllerRun->caParam = $params;

        //view实例化
        $controllerRun->view = new DyPhpView();

        //action分析及登录验证
        $actionName = self::parseAction($controllerRun, $action);

        //init运行
        $controllerRun->init();

        //beforeAction运行
        $controllerRun->beforeAction();

        //action执行
        $controllerRun->$actionName();

        //hook调用: action执行完成之后
        DyPhpBase::app()->hook->invokeHook(DyPhpHooks::AFTER_ACTION);
    }

    /**
     * 请求转发 可以理解为run方法的别名 区别在于必须继承了DyPhpController才可使用.
     *
     * @param string controller类名,支持module_controller或module/controller的格式调用
     * @param string action的方法名
     * @param array  当前运行controller中可公用的参数
     **/
    final protected function forward($controllerPathName = '', $action = '', $params = array())
    {
        if (empty($controllerPathName)) {
            DyPhpBase::throwException('controller does not exist');
        }
        if (empty($action)) {
            DyPhpBase::throwException('action does not exist');
        }
        self::run($controllerPathName, $action, $params);
    }

    /**
     * 解析action
     *
     * @param   object $controllerRun controller实例
     * @param   string $action
     *
     * @return  string
     **/
    private static function parseAction($controllerRun, $action)
    {
        //action方法名组装与验证
        $actionNameStr = $action ? $action : DYPHP_DEFAULT_ACTION;
        $httpMethod = $controllerRun->actionHttpMethodMode ? ucfirst(strtolower(DyRequest::getMethod())) : '';
        $actionName = 'action'.$httpMethod.ucfirst($actionNameStr);
        if (!method_exists($controllerRun, $actionName)) {
            DyPhpBase::throwException('action does not exist', $actionName);
        }
        
        $controllerRun->aid = lcfirst($actionNameStr);
        DyPhpBase::app()->aid = $controllerRun->aid;

        //hook调用: controller实例化之后执行
        DyPhpBase::app()->hook->invokeHook(DyPhpHooks::AFTER_CONTROLLER_CONSTRUCTOR);

        //只有web项目需要进行访问认证验证
        if (DyPhpBase::$appType == 'web') {
            //need login处理 判断返回值类型  对必须登录才可以访问的方法进行验证与拦截
            if (!is_array($controllerRun->needLogin())) {
                DyPhpBase::throwException('needLogin method error');
            }

            //未登录不可访问的action,重定向到登录页
            $loginHandler = empty($controllerRun->loginHandler) ? DyPhpConfig::item('loginHandler') : $controllerRun->loginHandler;
            if ($controllerRun->pcid.'/'.$controllerRun->aid != trim($loginHandler, '/') && DyPhpBase::app()->auth->isGuest() && ($controllerRun->allNeedLogin || in_array($controllerRun->aid, $controllerRun->needLogin()))) {
                DyPhpBase::app()->auth->logout();
                DyRequest::redirect($loginHandler);
            }
        }

        return $actionName;
    }

    /**
     * 解析controller
     *
     * @param   string $controllerPathName
     *
     * @return  object
     **/
    private static function parseController($controllerPathName)
    {
        //controller 解析
        $controllerPathName = trim(str_replace('_', '/', $controllerPathName), '/');
        $pos = strrpos($controllerPathName, '/');
        $controllerName = $pos === false ? ucfirst($controllerPathName) : ucfirst(substr($controllerPathName, $pos + 1));
        $controllerPath = $pos === false ? '' : substr($controllerPathName, 0, $pos + 1);
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
        $controllerRun->cid = lcfirst($controllerName);
        $controllerRun->pcid = lcfirst($controllerPathName);
        $controllerRun->module = rtrim(lcfirst($controllerPath), '/');
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
