<?php
/**
 * 验证码widget
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright 2011 dyphp.com 
 **/
class DyCaptchaWidget extends DyPhpWidgets{
    public function run($options=array()){
        $options['request'] = isset($options['request']) ? $options['request'] : '';
        $options['buttonLabel'] = isset($options['buttonLabel']) ? $options['buttonLabel'] : 'Get a new code';
        $this->sysRender('dyCaptcha',compact('options'));
    }
    
}

