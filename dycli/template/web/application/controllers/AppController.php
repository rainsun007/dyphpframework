<?php

/**
 * home controller
 * 框架规则-默认错误 信息 登陆处理对应此controller 如不使用默认需要在配制中定义.
 *
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright dyphp.com
 */
class AppController extends BaseController
{
    protected function init()
    {
        parent::init();
        $this->view->defaultLayout = 'main';
    }

    protected function beforeAction()
    {
        parent::init();
    }

    /**
     * 站点首页.
     **/
    public function actionIndex()
    {
        $this->view->render('index');
    }

    /**
     * 框架规则-登录action  此方法必须存在
     * 当游客访问accessRules方法中deny数组定义的action时 会自动跳转到此action.
     **/
    public function actionLogin()
    {
        $userId = DyRequest::getStr('userId');
        $password = DyRequest::getStr('password');
        DyPhpBase::app()->auth->login($userId,$password);
    }

    /**
     * 退出action.
     **/
    public function actionLogout()
    {
        DyPhpBase::app()->auth->logout();
        DyRequest::redirect('/');
    }

    /**
     * 框架规则-错误信息获取action  如config中不配制errorHandler此方法必须存在
     * 当访问出错时会自动调用此方法.
     **/
    public function actionError()
    {
        $error = $this->actionParam;
        var_dump($error);
    }

    /**
     * 框架规则-信息获取action  如config中不配制messageHandler此方法必须存在
     * 使用框架showMsg时会自动调用此方法.
     **/
    public function actionMessage()
    {
        $message = $this->actionParam;
        var_dump($message);
    }
}
