<?php
/**
 * 项目父类
 *
 * @author QingYu.Sun Email:dyphp.com@gmail.com
 *
 * @version 1.0
 *
 * @copyright dyphp.com
 *
 * @link http://www.dyphp.com
 **/
class BaseController extends DyPhpController
{
    protected $allNeedLogin = false;

    //用户信息
    public $userInfo = null;
    public $userId = 0;

    //系统设置
    public $sysSetting = null;
    public $setting = null;

    protected function init()
    {
    }

    protected function beforeAction()
    {
    }
}
