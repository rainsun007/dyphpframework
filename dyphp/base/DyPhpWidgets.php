<?php
/**
 * widget基类
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright 2011 dyphp.com 
 **/
abstract class DyPhpWidgets{

    abstract function run($args=array());

    /** 
     * app调用 widget view
     * @param string 调用的view
     * @param mixed  view需要的数据
     **/
    protected function render($view,$data=array()){
        $view = trim($view,'/');
        $viewFile = DyPhpConfig::item('appPath').DIRECTORY_SEPARATOR
            .'widgets'.DIRECTORY_SEPARATOR
            .'views'.DIRECTORY_SEPARATOR
            .$view.EXT;
        if(file_exists($viewFile)){
            if(is_array($data)){
                extract($data);
            }
            require $viewFile;
        }else{
            DyPhpBase::throwException('widget view does not exist', $view);
        }
    }

    /** 
     * 系统调用widget view
     * @param string 调用的view
     * @param mixed  view需要的数据
     **/
    protected function sysRender($view,$data=array()){
        $view = trim($view,'/');
        $viewFile = DYPHP_PATH.DIRECTORY_SEPARATOR
            .'dyphp'.DIRECTORY_SEPARATOR
            .'widgets'.DIRECTORY_SEPARATOR
            .'views'.DIRECTORY_SEPARATOR
            .$view.EXT;
        if(file_exists($viewFile)){
            if(is_array($data)){
                extract($data);
            }
            require $viewFile;
        }else{
            DyPhpBase::throwException('widget view does not exist', $view);
        }
    }

}
