<?php
/**
 * 用户认证类
 * 框架规则-此文件为特殊文件 此类必须存在 无此类将报错.
 *
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright dyphp.com
 */
class UserIdentity extends DyPhpUserIdentity
{
    //使用cookie保存登陆状态及setInfo
    protected $isCookieUserAuth = true;

    //用户信息
    public $userInfo = null;

    /**
     * 框架规则-必须实现此认证方法.
     *
     * @param string 用户身份索引值,如：id,email,电话,用户名,昵称等
     * @param string 加密后的密码
     * @param int    自动登陆过期时间
     *
     * @return bool
     **/
    public function authenticate($userIndexValue = '', $encryptPassword = '', $autoLoginExpire = 0)
    {
        $userInfo = Member::model()->getOne("id='{$userIndexValue}'");
        
        if (!$userInfo) {
            return false;
        }
        $this->userInfo = $userInfo;
        
        if ($userInfo->password != $encryptPassword) {
            return false;
        }

        //设置全局用户信息
        $this->setInfo('uid', $userInfo->id);
        
        //该方法必须调用 否则没有登陆状态
        return $this->setLoginStatus($userIndexValue, $encryptPassword, $autoLoginExpire);
    }

    /**
     * app中调用登陆使用.
     *
     * @param string 用户id
     * @param string 密码
     *
     * @return bool
     **/
    public function login($userId = '', $password = '', $loginExpire = 86400*7)
    {
        if (empty($userId) || empty($password)) {
            return false;
        }

        return $this->authenticate($userId, md5($password), (int)$loginExpire);
    }
}
