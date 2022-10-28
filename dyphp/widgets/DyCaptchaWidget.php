<?php
/**
 * 验证码widget
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright dyphp.com
 **/
class DyCaptchaWidget extends DyPhpWidgets
{
    /**
     * 验证码渲染
     * 
     * @example 在view中调用：$this->widget('DyCaptchaWidget', array('request'=>DyRequest::createUrl('/app/captcha', array('ct'=>'login')),'buttonLabel'=>'换一个','id'=>'free'));
     *
     * @param array  id:验证码显示元素id，可不设置
     *               request：验证码生成地址
     *               buttonLabel：获取新验证码提示文字，可不设置
     * 
     * @return void
     */
    public function run($options=array())
    {
        //图片元素id
        $dyCWElementId = isset($options['id']) ? 'dyCaptchaShowImg_'.$options['id'] : 'dyCaptchaShowImg_'.mt_rand(1000,99999);
        //js函数名
        $dyCWRefreshCaptcha = isset($options['id']) ? 'dyRefreshCaptchaImg_'.$options['id'].'()' : 'dyRefreshCaptchaImg_'.mt_rand(1000,99999).'()';
        //验证码生成地址
        $dyCWRequest = isset($options['request']) ? $options['request'] : '/';
        //获取新验证码提示文字
        $dyCWButtonLabel = isset($options['buttonLabel']) ? $options['buttonLabel'] : 'Get a new code';

        $this->sysRender('dyCaptcha', compact('dyCWRequest', 'dyCWButtonLabel','dyCWElementId','dyCWRefreshCaptcha'));
    }
}
