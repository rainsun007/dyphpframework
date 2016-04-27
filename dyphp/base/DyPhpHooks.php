<?php
/**
 * hook类
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright 2011 dyphp.com 
 **/
class DyPhpHooks{
    //在beforeAction之前执行
    const BEFORE_ACTION = 'before_action';
    //唯一包含
    private $incOnce = array();

    /**
     * @brief    hook调用
     * @param    $hookType
     * @return   
     **/
    final public function invokeHook($hookType,&$args=null){
        $hook = DyPhpConfig::item('hooks');
        $enable = isset($hook['enable']) && is_bool($hook['enable']) ? $hook['enable'] : false;
        if(!$enable){
            return;
        }

        if(!in_array($hookType,array(self::BEFORE_ACTION))){
            DyPhpBase::throwException('hook type error',$hookType);
        }

        //hook配制验证
        if(!isset($hook[$hookType]) || !is_array($hook[$hookType])){
            DyPhpBase::throwException('hook Undefined or data type error',$hookType);
        }

        foreach($hook[$hookType] as $key=>$val){
            $this->incOnce($key);

            //hook调用
            $userHook = new $key;
            foreach($val as $k=>$v){
                is_int($k) ? $userHook->$v() : $userHook->$k($v);
            }
        }
    }

    /**
     * @brief    加载hook文件
     * @param    $hookName
     * @return   
     **/
    private function incOnce($hookName){
        if(in_array($hookName,$this->incOnce)){
            return;
        }
        $file  = DyPhpConfig::item('appPath').'/hooks/'.$hookName.EXT;
        if(!file_exists($file)){
            DyPhpBase::throwException('hook does not exist',$hookName);
        }
        require $file;
        $this->incOnce[] = $hookName;
    }

}

