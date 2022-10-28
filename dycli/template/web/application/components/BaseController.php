<?php
/**
 * @file BaseController.php
 * @brief 项目父类
 *
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright dyphp.com
 **/
class BaseController extends DyPhpController
{
    protected $allNeedLogin = false;

    protected function init()
    {
        $this->view->defaultTheme = 'default';
    }

    protected function beforeAction()
    {
    }

    /**
     * 验证码生成器.
     **/
    public function actionCaptcha()
    {
        $captchaType = DyRequest::getStr('ct');
        $captchaArr = array('login');
        if (!in_array($captchaType, $captchaArr)) {
            exit('captcha error');
        }

        $cap = DyPhpBase::app()->captcha;
        $cap->saveName = 'rc_'.$captchaType;
        $cap->background = 'rand'; //'rand'  'bg4.png'  array(100,255,255)
        $cap->waveWord = false;
        $cap->saveType = 'cookie';
        $cap->expire = 300;
        $cap->model = 0;
        $cap->format = 'png';
        $cap->scale = 2;
        //$cap->maxRotation = 20;
        $cap->noiseLine = 0;
        $cap->noise = 0;
        $cap->fonts = array(array('spacing' => -2, 'minSize' => 24, 'maxSize' => 30, 'font' => 'AHGBold.ttf'));
        $cap->colors = array(array(27, 78, 181));
        $cap->createImage();
        exit;
    }
}
