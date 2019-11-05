<?php
/**
 * hook类
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright dyphp.com
 **/
class DyPhpHooks
{
    //controller实例化之后执行，如需要区分是否登录，在hook中自行处理
    const AFTER_CONTROLLER_CONSTRUCTOR = 'after_controller_constructor';
    //action执行完成之后执行
    const AFTER_ACTION = 'after_action';
    //view的render方法调用执行之前执行
    const BEFORE_VIEW_RENDER = 'before_view_render';

    //hook文件加载单例
    private $incOnce = array();

    //after_action,hook的开启状态
    private $afterActionEnable = false;
    //before_view_render,hook是否已被调用
    private $beforeViewRenderInvoke = false;


    public function __construct()
    {
        $hook = DyPhpConfig::item('hooks');
        $this->afterActionEnable = isset($hook[self::AFTER_ACTION]['enable']) && is_bool($hook[self::AFTER_ACTION]['enable']) ? $hook[self::AFTER_ACTION]['enable'] : false;
    }


    /**
     * hook调用执行
     * @param    $hookType
     *
     * 配制格式
     * 'hooks'=>array(
     *   'enable'=>true,
     *   'after_controller_constructor'=>array(
     *       'enable'=>true,
     *       'HookClassNameOne'=> array('methodName'),
     *       'HookClassNameTwo'=> array('methodOne'=>'param mixed : String , Int, Array','methodTwo'),
     *   ),
     * ),
     **/
    final public function invokeHook($hookType)
    {
        $hook = DyPhpConfig::item('hooks');
        
        //所有hook都关闭直接返回
        $enable = isset($hook['enable']) && is_bool($hook['enable']) ? $hook['enable'] : false;
        if (!$enable) {
            return;
        }

        //ob_start调用
        //after_action中可能会操作cookie,session,重定向等,所以要在"headers already sent"之前先开启ob_start
        //如action中不调用render,ob_start不必开启
        if ($hookType == self::BEFORE_VIEW_RENDER && $this->afterActionEnable) {
            $this->beforeViewRenderInvoke = true;
            ob_start();
        }

        //执行开启的hook
        $itemEnable = isset($hook[$hookType]['enable']) && is_bool($hook[$hookType]['enable']) ? $hook[$hookType]['enable'] : false;
        if ($itemEnable) {
            //hook配制验证
            if (!isset($hook[$hookType]) || !is_array($hook[$hookType])) {
                DyPhpBase::throwException('hook Undefined or data type error', $hookType);
            }

            //hook执行
            unset($hook[$hookType]['enable']);
            foreach ($hook[$hookType] as $key=>$val) {
                $this->incOnce($key);
                $userHook = new $key;
                foreach ($val as $k=>$v) {
                    is_int($k) ? $userHook->$v() : $userHook->$k($v);
                }
            }
        }
        
        //after_action开启状态下，before_view_render调用完成后需要执行ob_end_flush(),否则后导致view层输出报错
        if ($hookType == self::AFTER_ACTION && $this->afterActionEnable && $this->beforeViewRenderInvoke) {
            ob_end_flush();
        }
    }

    /**
     * hook文件自动加载单例
     * @param    $hookName
     * @return
     **/
    private function incOnce($hookName)
    {
        if (in_array($hookName, $this->incOnce)) {
            return;
        }

        $file  = DyPhpConfig::item('appPath').'/hooks/'.$hookName.EXT;
        if (!file_exists($file)) {
            DyPhpBase::throwException('hook does not exist', $hookName);
        }
        require $file;
        
        $this->incOnce[] = $hookName;
    }
}
