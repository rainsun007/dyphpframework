<?php
/**
 * hook类
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright 2011 dyphp.com
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

    /**
     * hook调用
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
        
        //判断是否关闭所有hook
        $enable = isset($hook['enable']) && is_bool($hook['enable']) ? $hook['enable'] : false;
        if (!$enable) {
            return;
        }

        //判断是否只关闭当前 $hookType 的hook
        $itemEnable = isset($hook[$hookType]['enable']) && is_bool($hook[$hookType]['enable']) ? $hook[$hookType]['enable'] : false;
        if (!$itemEnable) {
            return;
        }

        ob_start();

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

        if (ob_get_length()) {
            ob_end_clean();
            //ob_end_flush();
        }
    }

    /**
     * 加载hook文件
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
