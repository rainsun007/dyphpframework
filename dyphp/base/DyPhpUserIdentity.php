<?php
/**
 * 用户身份验证类.
 *
 * @author 大宇 Email:dyphp.com@gmail.com
 *
 * @link http://www.dyphp.com/
 *
 * @copyright Copyright 2011 dyphp.com
 **/
abstract class DyPhpUserIdentity
{
    //登陆用户信息前缀
    protected $infoPre = 'au_';
    //使用cookie保存登陆状态
    protected $isCookieUserAuth = false;
    //用户身份索引值
    private $userIndexValue = '';

    /**
     * 必须实现认证方法.
     *
     * @param int    自动登陆有效期 单位：秒
     * @param string 用户验证加密串，如加密后的密码（如md5('password')等,保持加密一至即可）
     *               虽不强制但基于安全考虑此处最好不要使用明文（相关校验逻辑在实现方法中完成），
     *               需要注意的是框架调用此方法时此参数为空字
     **/
    abstract public function authenticate($expire = 0, $encryptPassword = '');

    /**
     * 登陆状态设置.
     *
     * @param string 用户身份索引值
     * @param 过期时间 单位：秒
     **/
    final protected function setStatus($userIndexValue = '', $expire = 0)
    {
        $expire = intval($expire);

        //login status setting
        if ($this->getIsCookieUserAuth()) {
            DyCookie::set($this->getAutoLoginKey(), 'cookie_logined');
        } else {
            DySession::set($this->loginStateKey(), true);
        }

        //记住登陆处理 cookie设置 未传入有效数据则视为不设置自动登陆
        if (!empty($userIndexValue) && $expire > 0) {
            $delayExpire = time() + $expire + 1;
            $rememberArr = array(
                'token' => $this->getAutoLoginToken($userIndexValue, $delayExpire),
                'userIndexValue' => $userIndexValue,
                'expire' => $delayExpire,
            );
            DyCookie::set($this->loginStateKey('cookie'), json_encode($rememberArr), $delayExpire);
        }
    }

    /**
     * 自动登陆处理器(框架会自动调用此方法).
     *
     * @return bool
     **/
    final public function autoLoginStatus()
    {
        $cookieAuth = $this->getIsCookieUserAuth();

        //session记录登陆状态
        if (!$cookieAuth) {
            if (DySession::get($this->loginStateKey()) === true) {
                return true;
            }

            return $this->checkAutoLogin();
        }

        //cookie记录登陆状态
        if (DyCookie::get($this->getAutoLoginKey())) {
            return true;
        }

        return $this->checkAutoLogin();
    }

    /**
     * 判断用户是否为访客.
     **/
    final public function isGuest()
    {
        if ($this->getIsCookieUserAuth()) {
            return DyCookie::get($this->getAutoLoginKey()) ? false : true;
        } else {
            return DySession::get($this->loginStateKey()) ? false : true;
        }
    }

    /**
     * 退出登陆.
     **/
    final public function logout()
    {
        DyCookie::clear();
        DySession::destroy();
    }

    /**
     * @brief    设置登陆状态下信息
     *
     * @param   $name
     * @param   $value
     **/
    final public function setInfo($name, $value)
    {
        $this->getIsCookieUserAuth() ? DyCookie::set($this->infoPre.$name, $value) : DySession::set($this->infoPre.$name, $value);
    }

    /**
     * 获取登陆状态下信息.
     **/
    final public function __get($name)
    {
        return $this->getIsCookieUserAuth() ? DyCookie::get($this->infoPre.$name) : DySession::get($this->infoPre.$name);
    }

    /**
     * 加密登陆状态key.
     *
     * @param  类型
     *
     * @return string
     **/
    private function loginStateKey($type = 'session')
    {
        $loginStateKey = $this->cookieCryptStr().'@Dyphp~User_login-status&key#String%+^!=*/';
        if ($type == 'session') {
            return substr(md5($loginStateKey), 0, 8);
        } elseif ($type == 'cookie') {
            return substr(md5($loginStateKey), -8);
        }
    }

    /**
     * 获取cookie加密key.
     *
     * @return string
     **/
    private function cookieCryptStr()
    {
        return DyPhpConfig::item('secretKey') ? md5(DyPhpConfig::item('secretKey')) : '';
    }

    /**
     * 获取cookie登陆状态key(通过此key获取到value不为空则为已登录状态).
     **/
    private function getAutoLoginKey()
    {
        return $this->loginStateKey('cookie').'_al';
    }

    /**
     * 获取是否为Cookie验证
     **/
    private function getIsCookieUserAuth()
    {
        return is_bool($this->isCookieUserAuth) ? $this->isCookieUserAuth : false;
    }

    /**
     * @brief    获取自动登陆token
     *
     * @param   $userIndexValue
     * @param   $expire
     *
     * @return
     **/
    private function getAutoLoginToken($userIndexValue = '', $expire = 0)
    {
        $value = $userIndexValue.$expire.DyPhpConfig::item('secretKey');

        return md5(DyString::encodeStr($value, $this->cookieCryptStr(), $expire)).md5($value);
    }

    /**
     * @brief    获取用户身份索引值
     *
     * @return
     **/
    protected function getUserIndexValue()
    {
        return $this->userIndexValue;
    }

    /**
     * @brief    自动登陆验证
     *
     * @return
     **/
    private function checkAutoLogin()
    {
        $remember = DyCookie::get($this->loginStateKey('cookie'));
        if ($remember) {
            $rememberMe = json_decode($remember);
            if ($rememberMe['expire'] - time() <= 0) {
                return false;
            }
            $token = $this->getAutoLoginToken($rememberMe['userIndexValue'], $rememberMe['expire']);
            if ($token != $rememberMe['token']) {
                return false;
            }
            $this->userIndexValue = $rememberMe['userIndexValue'];

            return DyPhpBase::app()->auth->authenticate($rememberMe['expire'], '');
        }

        return false;
    }
}
