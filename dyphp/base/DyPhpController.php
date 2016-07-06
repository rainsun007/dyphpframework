<?php
/**
 * 控制器类
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright 2011 dyphp.com 
 **/
class DyPhpController{
    //默认使用的action
    protected $defaultAction = DYPHP_DEFAULT_ACTION;

    //未登陆跳转地址
    protected $loginUri = '';

    //action调用自定义
    protected $actionParam = array();

    //当前时间戳
    protected $time = 0;
    //当前完整日期时间（Y-m-d H:i:s）
    protected $datetime = '1970-01-01 08:00:00';
    //当前完整日期（Y-m-d）
    protected $date = '1970-01-01';

    //view实例
    public $view;

    //controller单例控制
    private static $incController = array();

    //设置所有action未登陆禁止访问 为true时needLogin方法将无效（loginUri属性不受限制）
    protected $allNeedLogin = false;

    //当前运行的controller及action
    protected $cid = '';
    protected $pcid = '';
    protected $aid = '';

    /**
     * @brief   在beforeAction之前执行,可以重写此方法实现自己的业务逻辑
     * @return   
     **/
    protected function init(){
    }

    /**
     * @brief   在action之前执行,可以重写此方法实现自己的业务逻辑
     * @return   
     **/
    protected function beforeAction(){
    }

    /**
     * 未登陆禁止访问的action 必须全部小写
     **/
    protected function needLogin(){
        return array();
    }

    /**
     * 运行controller入口
     * 支持系统内部直接调用本方法
     * 支持url以module_controller_action或module/controller/action的格式调用
     * @param string controller 
     * @param string action
     * @param array  参数
     **/
    final public static function run($controllerPname,$action='',$params=array()){
        //controller 解析 
        $controllerPname = trim(str_replace('_','/',$controllerPname),'/'); 
        $pos = strrpos($controllerPname,'/');
        $controllerName = $pos === false ? ucfirst($controllerPname) : ucfirst(substr($controllerPname,$pos+1));
        $controllerPath = $pos === false ? '' : substr($controllerPname,0,$pos+1);
        $controller = $controllerName.'Controller';
        $controllerFile = DyPhpConfig::item('appPath').DIRECTORY_SEPARATOR.'controllers'.DIRECTORY_SEPARATOR.$controllerPath.$controller.EXT;
        if(!file_exists($controllerFile)){
            DyPhpBase::throwException('controller does not exist', $controllerName);
        }

        //controller单例加载
        if(!in_array($controller,self::$incController)){ 
            include $controllerFile;
            self::$incController[] = $controller;
        }
        $controllerRun = new $controller;
        $controllerRun->cid = self::lcfirst($controllerName);
        $controllerRun->pcid = self::lcfirst($controllerPname);
        DyPhpBase::app()->cid = $controllerRun->cid;
        DyPhpBase::app()->pcid = $controllerRun->pcid;
        DyPhpBase::app()->runingController = $controllerRun;
        

        //action 解析
        $actionNameStr = $action ? $action : $controllerRun->defaultAction;
        $actionName = 'action'.ucfirst($actionNameStr);
        if(!method_exists($controllerRun,$actionName)){
            DyPhpBase::throwException('action does not exist', $actionName);
        }
        $controllerRun->aid = self::lcfirst($actionNameStr);
        DyPhpBase::app()->aid = $controllerRun->aid;

        //need login处理
        $denyAccess = $controllerRun->allNeedLogin ? array(strtolower($actionNameStr)) : $controllerRun->needLogin();
        if(!is_array($denyAccess)){
            DyPhpBase::throwException('needLogin method error');
        }

        if(!in_array(strtolower($actionNameStr),$denyAccess) || (!DyPhpBase::app()->auth->isGuest() && in_array(strtolower($actionNameStr),$denyAccess))){
            $controllerRun->time = isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time();
            $controllerRun->datetime = date("Y-m-d H:i:s",$controllerRun->time);
            $controllerRun->date = date("Y-m-d",$controllerRun->time);

            //参数设置
            $controllerRun->actionParam = $params;
            //view实例化
            $controllerRun->view = new DyPhpView;
            //init运行
            $controllerRun->init();
            //hook调用
            DyPhpBase::app()->hook->invokeHook('before_action');
            //beforeAction运行
            $controllerRun->beforeAction();
            //action执行
            $controllerRun->$actionName();
        }else{
            $loginUri = empty($controllerRun->loginUri) ? DyPhpConfig::item('loginHandler') : $controllerRun->loginUri;
            DyRequest::redirect($loginUri);
        }
    }

    /**
     * @brief    首字母小写
     * @param    $string
     * @return   
     **/
    protected static function lcfirst($string){
        if ( false === function_exists('lcfirst') ){
            $string = (string)$string;
            if(empty($string)){
                return '';
            }
            $string{0} = strtolower($string{0});
            return $string; 
        }else{
            return lcfirst($string);
        }
    }

    public function __call($key, $Args){
        DyPhpBase::throwException('method does not exist', $key);
    }
}

