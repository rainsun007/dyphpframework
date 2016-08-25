<?php
/**
 * cookie工具类
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright 2011 dyphp.com
 **/
class DyCookie{
    /**
     * cookie配制处理器
     **/
    private static function cookieConfig($key){
        $cookieArr = DyPhpConfig::item('cookie');
        if($key == 'prefix'){
            $value = isset($cookieArr['prefix']) ? $cookieArr['prefix'] : '';
        }elseif($key == 'path'){
            $value = isset($cookieArr['path']) ? $cookieArr['path'] : '/';
        }elseif($key == 'domain'){
            $value = isset($cookieArr['domain']) ? $cookieArr['domain'] : $_SERVER['SERVER_NAME'];
        }elseif($key == 'cryptStr'){
            $cookieSecretKey = isset($cookieArr['secretKey']) ? $cookieArr['secretKey'] : '';
            $secretKey = DyPhpConfig::item('secretKey') ? DyPhpConfig::item('secretKey') : '';
            $value = $cookieSecretKey ? $cookieSecretKey : $secretKey;
            $value = md5($value);
        }
        return $value;
    }

    /**
     * 判断Cookie是否存在
     * @param string cookie键名
     **/
    public static function is_set($name) {
        $prefix = self::cookieConfig('prefix');
        return isset($_COOKIE[$prefix.$name]);
    }

    /**
     * 获取Cookie
     * @param string cookie键名
     **/
    public static function get($name='') {
        if($name === ''){
            return '';
        }

        $prefix = self::cookieConfig('prefix');
        if(!isset($_COOKIE[$prefix.$name])){
            return '';
        }

        return DyString::decodeStr($_COOKIE[$prefix.$name],self::cookieConfig('cryptStr'));
        //$value = unserialize(base64_decode($value));
        //return $value === false ? '' : $value;
    }

    /**
     * 设置Cookie
     * @param string cookie键名
     * @param mixed  cookie值
     * @param int    过期时间 单位：秒
     * @param string 存储路径
     * @param string 存储域
     **/
    public static function set($name='',$value='',$expire=0,$path='',$domain='',$secure=false,$httponly=false) {
        if($name === ''){
            return false;
        }
        $prefix = self::cookieConfig('prefix');
        if(empty($path)) {
            $path = self::cookieConfig('path');
        }
        if(empty($domain)) {
            $domain = self::cookieConfig('domain');
            $domain = ($domain != 'localhost') ? $domain : false;
        }

        //$value = base64_encode(serialize($value));
        $value = DyString::encodeStr($value,self::cookieConfig('cryptStr'),$expire);
        $expire = (int)$expire>0 ? time()+(int)$expire : 0;
        $_COOKIE[$prefix.$name] = $value;
        return setcookie($prefix.$name, $value,$expire,$path,$domain,$secure,$httponly);
    }

    /**
     * 获取oset方法设置的非加密Cookie
     * @param string cookie键名
     **/
    public static function oget($name='') {
        if($name === ''){
            return '';
        }

        $prefix = self::cookieConfig('prefix');
        if(!isset($_COOKIE[$prefix.$name])){
            return '';
        }
        return $_COOKIE[$prefix.$name];
    }

    /**
     * 设置非加密Cookie
     * @param string cookie键名
     * @param mixed  cookie值
     * @param int    过期时间 单位：秒
     * @param string 存储路径
     * @param string 存储域
     **/
    public static function oset($name='',$value='',$expire=0,$path='',$domain='',$secure=false,$httponly=false) {
        if($name === ''){
            return false;
        }
        $prefix = self::cookieConfig('prefix');
        if(empty($path)) {
            $path = self::cookieConfig('path');
        }
        if(empty($domain)) {
            $domain = self::cookieConfig('domain');
        }

        $expire = (int)$expire>0 ? time()+(int)$expire : 0;

        $_COOKIE[$prefix.$name] = $value;
        return setcookie($prefix.$name, $value,$expire,$path,$domain,$secure,$httponly);
    }

    /**
     * 删除cookie
     * @param  cookie键名
     **/
    public static function delete($name) {
        $expire = time()-86400;
        $prefix = self::cookieConfig('prefix');
        $path = self::cookieConfig('path');
        $domain = self::cookieConfig('domain');

        setcookie($prefix.$name, '',$expire,$path,$domain);
        unset($_COOKIE[$prefix.$name]);
    }

    /**
     * 清空Cookie
     **/
    public static function clear() {
        $prefix = self::cookieConfig('prefix');
        foreach($_COOKIE as $key=>$val){
            self::delete(str_replace($prefix,'',$key));
        }
        unset($_COOKIE);
    }

}
