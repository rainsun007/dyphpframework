<?php
/**
 * 用户身份验证类.
 *
 * @author 大宇 Email:dyphp.com@gmail.com
 *
 * @link http://www.dyphp.com/
 *
 * @copyright Copyright dyphp.com
 **/
abstract class DyPhpUserIdentity
{
    //登陆用户信息前缀
    protected $infoPre = 'au_';

    //使用cookie保存登陆状态及setInfo数据, 为false时使用session保存登录状态及setInfo数据
    protected $isCookieUserAuth = false;

    /**
     * [必须]身份认证.
     * 如不使用框架的认证规则，可实现此方法后return false;
     *
     * @param string [必须]用户身份索引值,如：id,email,电话,用户名,昵称等
     * @param string [必须]用户验证加密串，如加密后的密码（如md5('password')等,保持加密一至即可）
     *               基于安全考虑此处不要使用明文（相关校验逻辑需在实现中完成）
     * @param int    自动登陆有效期，单位：秒，0为不使用自动登陆
     *
     * @return bool
     **/
    abstract public function authenticate($userIndexValue = '', $encryptPassword = '', $autoLoginExpire = 0);

    /**
     * [必须]登陆状态设置. 此方法必须在authenticate实现中显示调用
     *
     * @param string [必须]用户身份索引值,如：id,email,电话,用户名,昵称等
     * @param string [必须]用户验证加密串，如加密后的密码（如md5('password')等,保持加密一至即可）
     *               基于安全考虑此处不要使用明文（相关校验逻辑需在实现中完成）
     * @param int    自动登陆有效期，单位：秒，0为不使用自动登陆
     *
     * @return bool
     **/
    final public function setLoginStatus($userIndexValue = '', $encryptPassword = '', $expire = 0)
    {
        if (!$userIndexValue || !$encryptPassword) {
            return false;
        }

        if ($this->isCookieUserAuth) {
            DyCookie::set($this->getLoginStateKey('cookie').'_cl', 'cookie_logined');
        } else {
            DySession::set($this->getLoginStateKey(), true);
        }

        if ($expire == 0) {
            return true;
        }

        //自动登陆cookie设置
        $time = time();
        $rememberArr = array(
            'token' => $this->getAutoLoginToken($userIndexValue, $encryptPassword, $time, $expire),
            'iv' => $userIndexValue,
            'ep' => $encryptPassword,
            'time' => $time,
            'expire' => $expire
        );
        return DyCookie::set($this->getLoginStateKey('cookie'), json_encode($rememberArr), $expire);
    }

    /**
     * 判断用户是否为访客.
     **/
    final public function isGuest()
    {
        if ($this->isCookieUserAuth) {
            return DyCookie::get($this->getLoginStateKey('cookie').'_cl') === 'cookie_logined' ? false : true;
        } else {
            return DySession::get($this->getLoginStateKey()) ? false : true;
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
     * 设置登陆状态下信息
     *
     * @param string
     * @param mixed
     **/
    final public function setInfo($name, $value)
    {
        $this->isCookieUserAuth ? DyCookie::set($this->infoPre.$name, $value) : DySession::set($this->infoPre.$name, $value);
    }

    /**
     * 获取登陆状态下信息.
     **/
    final public function __get($name)
    {
        return $this->isCookieUserAuth ? DyCookie::get($this->infoPre.$name) : DySession::get($this->infoPre.$name);
    }

    /**
     * 自动登陆处理器(框架会自动调用此方法).
     *
     * @return bool
     **/
    final public function runAutoLogin()
    {
        if (!$this->isGuest()) {
            return true;
        }

        $remember = DyCookie::get($this->getLoginStateKey('cookie'));
        if (!$remember) {
            return false;
        }

        $rememberMe = json_decode($remember, true);
        if ($rememberMe['time'] + $rememberMe['expire'] - time() <= 0) {
            return false;
        }
        
        $checkToken = $this->getAutoLoginToken($rememberMe['iv'], $rememberMe['ep'], $rememberMe['time'], $rememberMe['expire'], $rememberMe['token']);
        if (!$checkToken) {
            return false;
        }

        //调用项目自定义认证逻辑
        return $this->authenticate($rememberMe['iv'], $rememberMe['ep'], $rememberMe['expire']);
    }

    /**
     * 防止多应用在同一服务器运行时冲突，生成登陆状态key.
     *
     * @param  string 类型
     *
     * @return string
     **/
    private function getLoginStateKey($type = 'session')
    {
        $loginStateKey = $this->getCookieCryptKey();
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
    private function getCookieCryptKey()
    {
        $cookieArr = DyPhpConfig::item('cookie');
        $cookieSecretKey = isset($cookieArr['secretKey']) ? $cookieArr['secretKey'] : '@Dyphp0~User9_login1-8status2&key7#3String6%+^4!=*/5';
        
        return DyPhpConfig::item('secretKey').$cookieSecretKey;
    }

    /**
     * 获取自动登陆token
     *
     * @param  string  用户身份索引值
     * @param  int     token生成时间戳
     * @param  int     token过期时间
     * @param  string  token值，验证token是否有效时使用
     *
     * @return  mixed
     **/
    private function getAutoLoginToken($userIndexValue = '', $encryptPassword = '', $time = 0, $expire = 0, $token = '')
    {
        $value = md5($userIndexValue.$encryptPassword.$time.$expire).md5($this->getCookieCryptKey());

        if ($token) {
            return DyString::decodeStr($token, $this->getCookieCryptKey()) == $value;
        }

        return DyString::encodeStr($value, $this->getCookieCryptKey(), $expire);
    }
}
