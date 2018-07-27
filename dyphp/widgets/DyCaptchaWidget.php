<?php
/**
 * 验证码widget
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright dyphp.com 
 **/
class DyCaptchaWidget extends DyPhpWidgets{
    public function run($options=array()){
        $dycwRequest = isset($options['request']) ? $options['request'] : '';
        $dycwButtonLabel = isset($options['buttonLabel']) ? $options['buttonLabel'] : 'Get a new code';
        $this->sysRender('dyCaptcha',compact('dycwRequest','dycwButtonLabel'));
    }
    
}

