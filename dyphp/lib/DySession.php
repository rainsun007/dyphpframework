<?php
 /**
 * session工具类
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright dyphp.com
 **/
class DySession
{
    private static $appid = '';
    
    /**
     * 开启session
     **/
    private static function start()
    {
        if (!session_id()) {
            session_start();
        }
        if (!self::$appid) {
            self::$appid = DyPhpConfig::item('appID').'_';
        }
    }
    
    /**
     * 设置session
     * @param string session键名
     * @param string session值
     **/
    public static function set($name, $value)
    {
        self::start();
        $_SESSION[self::$appid.$name] = $value;
    }
    
    /**
     * 获取session
     * @param string session键名
     * @return string session值
     **/
    public static function get($name)
    {
        self::start();
        return isset($_SESSION[self::$appid.$name]) ? $_SESSION[self::$appid.$name] : null;
    }
    
    /**
     * 删除session
     * @param string session键名
     **/
    public static function delete($name)
    {
        self::start();
        if (isset($_SESSION[self::$appid.$name])) {
            unset($_SESSION[self::$appid.$name]);
        }
    }
    
    /**
     * 销毁session
     **/
    public static function destroy()
    {
        self::start();
        $_SESSION = array();
        session_unset();
        session_destroy();
    }
}
