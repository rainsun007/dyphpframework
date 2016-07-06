<?php
/**
 * http请求处理工具类
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright 2011 dyphp.com 
 */
class DyRequest{
    //remote post请求结果
    private static $postResult;
    private static $userAgent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.57 Safari/537.36';

    //主url单例
    private static $url = '';

    /**
     * @brief    url重定向
     * @param    $url   站内重定向该参数为controller/action 站外或完整url重定向需要将$param设置为非array类型
     * @param    $param 为兼容url风格此参数设置为array  不使用createUrl可以将此参数设置为非array类型如boolean
     * @param    $code
     * @param    $method
     * @return   
     **/
    public static function redirect($url, $param = array(), $code = 302, $method = 'location'){
        $jumpUrl = is_array($param) ? self::createUrl($url,$param) : $url;
        header($method == 'refresh' ? "Refresh:0;url = {$jumpUrl}" : "Location: {$jumpUrl}", TRUE, $code);
        exit;
    }

    /**
     * @brief    获取站点根url
     * @return   
     **/
    public static function getSiteRootUrl(){
        return self::createUrl();
    }

    /**
     * 创建兼容性URL 
     * @param string controller或controller/action
     * @param array  get参数
     * @param 强制以url正常get参数格式处理传入的参数
     * @return string 完整的http访问地址
     **/
    public static function createUrl($ca = '', $param = array(),$forceGet = false){
        $isRest = DyPhpConfig::getRestCa();
        $hideIndex = DyPhpConfig::getHideIndex();
        $index = $hideIndex ? '' : 'index'.EXT . ($isRest ? '/' : '?');

        $ca = trim($ca, '/');
        $ca = $index . ($isRest ? $ca : ($hideIndex ? '?' : '') . 'ca=' . ($ca == '' ? DYPHP_DEFAULT_CONTROLLER.'.'.DYPHP_DEFAULT_ACTION : str_replace('/', '.', $ca)));
        $getParam = $isRest ? '/' : '&';
        foreach ($param as $key => $val){
            if(empty($key) || empty($val)){
                continue;
            }
            if($isRest && $forceGet){
                $getParam .= $key . '=' . $val . '&';
            }else{
                $getParam .= $isRest ? $key . '/' . $val . '/' : $key . '=' . $val . '&';
            }
        }
        $getParam = $isRest && $forceGet ? '?'.trim(substr($getParam, 0, -1),'/') : substr($getParam, 0, -1);

        if(self::$url){
            return self::$url . $ca . $getParam;
        }
        $appHttpPath = DyPhpConfig::item('appHttpPath') != '' ? DyPhpConfig::item('appHttpPath') .'/' : '';
        self::$url = self::serverName() . '/' . $appHttpPath; 
        return self::$url . $ca . $getParam;
    }

    /**
     * 创建URL访问路径
     * @param string 路径
     * @return string 完整url路径
     **/
    public static function path($path = ''){
        $path = trim($path, '/');

        if(self::$url){
            return self::$url.$path;
        }

        $appHttpPath = DyPhpConfig::item('appHttpPath') != '' ? DyPhpConfig::item('appHttpPath').'/' : '';
        self::$url = self::serverName() . '/' . $appHttpPath;
        return self::$url.$path;
    }

    /**
     * ajax请求判断 
     * @return bool 
     **/
    public static function isAjax(){
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    /**
     * server name 
     * @return string 
     **/
    public static function serverName(){
        $protocol = (isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) != 'off')) ? 'https://' : 'http://';

        if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])){
            $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
        }elseif (isset($_SERVER['HTTP_HOST'])){
            $host = $_SERVER['HTTP_HOST'];
        }else{
            $port = '';
            if (isset($_SERVER['SERVER_PORT'])){
                $port = ':' . $_SERVER['SERVER_PORT'];

                if ((':80' == $port && 'http://' == $protocol) || (':443' == $port && 'https://' == $protocol)){
                    $port = '';
                }
            }

            if (isset($_SERVER['SERVER_NAME'])){
                $host = $_SERVER['SERVER_NAME'] . $port;
            }elseif (isset($_SERVER['SERVER_ADDR'])){
                $host = $_SERVER['SERVER_ADDR'] . $port;
            }
        }
        return $protocol . $host;
    }

    /**
     * 获取get请求的字串
     * @param String  get请求的变量名
     * @param string  默认值
     * @return String get变量名对应的值
     */
    public static function getStr($key,$default=''){
        return isset($_GET[$key]) ? self::getFilterString($_GET[$key],$default) : $default;
    }

    /**
     * 获取get请求的整数
     * @param String get请求的变量名
     * @param int    默认值
     * @return Int   get变量名对应的值
     */
    public static function getInt($key,$default=0){
        return isset($_GET[$key]) ? self::getFilterInt($_GET[$key],$default) : $default;
    }

    /**
     * 获取get请求的浮点数
     * @param String get请求的变量名
     * @param int    默认值
     * @return Int   get变量名对应的值
     */
    public static function getFloat($key,$default=0.00){
        return isset($_GET[$key]) ? self::getFilterFloat($_GET[$key],$default) : $default;
    }

    /**
     * 获取POST请求的数组
     * @param String post请求的变量名
     * @param array  默认值
     * @return Int   post变量名对应的值
     **/
    public static function getArr($key,$default=array()){
        return isset($_GET[$key]) && is_array($_GET[$key]) ? self::getFilterString($_GET[$key],$default) : $default;
    }

    /**
     * 获取post请求的字串
     * @param String  post请求的变量名
     * @param string  默认值
     * @return String post变量名对应的值
     */
    public static function postStr($key,$default=''){
        return isset($_POST[$key]) ? self::getFilterString($_POST[$key],$default) : $default;
    }

    /**
     * 获取post请求的整数
     * @param String post请求的变量名
     * @param int    默认值
     * @return Int   post变量名对应的值
     */
    public static function postInt($key,$default=0){
        return isset($_POST[$key]) ? self::getFilterInt($_POST[$key],$default) : $default;
    }

    /**
     * 获取get请求的浮点数
     * @param String get请求的变量名
     * @param int    默认值
     * @return Int   get变量名对应的值
     */
    public static function postFloat($key,$default=0.00){
        return isset($_POST[$key]) ? self::getFilterFloat($_POST[$key],$default) : $default;
    }

    /**
     * 获取POST请求的数组
     * @param String post请求的变量名
     * @param array  默认值
     * @return Int   post变量名对应的值
     **/
    public static function postArr($key,$default=array()){
        return isset($_POST[$key]) && is_array($_POST[$key]) ? self::getFilterString($_POST[$key],$default) : $default;
    }

    /**
     * @brief    获取get提交原始数据
     * @param    $paramKey
     * @param    $default
     * @return   
     **/
    public static function getOriginal($paramKey='',$default=''){
        return self::getOriginalParam($paramKey,'get',$default);
    }

    /**
     * @brief    获取post提交原始数据
     * @param    $paramKey
     * @param    $default
     * @return   
     **/
    public static function postOriginal($paramKey='',$default=''){
        return self::getOriginalParam($paramKey,'post',$default);
    }


    /**
     * @brief    获取提交的原始数据
     * @param    $paramKey
     * @param    $method
     * @param    $default
     * @return   
     **/
    private static function getOriginalParam($paramKey='',$method='get',$default=''){
        $methodArr = $method == 'get' ? $_GET : $_POST;
        if(!isset($methodArr[$paramKey])){
            return $default;
        }

        $value = $methodArr[$paramKey];
        if(get_magic_quotes_gpc()){
            $value = stripcslashes($value);
        }
        return $value;
    }

    /**
     * 转义字串
     * @param String  需要被转义的字串
     * @return String 转义的字串
     */
    private static function getFilterString($requestValue,$default){
        $value = is_array($requestValue) ? array() : '';
        return isset($requestValue) ? self::strAddslashes($requestValue) : $value;
    }

    /**
     * 处理整型数据
     * @param String  需要被验证的值
     * @return String 返回验证过的值
     */
    private static function getFilterInt($requestValue,$default){
        return DyFilter::isInt($requestValue) ? $requestValue : $default;
    }

    /**
     * @brief   处理浮点型数据
     * @param   float 需要被验证的值
     * @return  返回验证过的值
     **/
    private static function getFilterFloat($requestValue,$default){
        return DyFilter::isFloat($requestValue) ? $requestValue : $default;
    }


    /**
     * 字符串转义
     * @param string  需要转义的字符串
     * @return string 转义后的字符串
     **/
    private static function strAddslashes($string){
        if (!get_magic_quotes_gpc()){
            if (is_array($string)){
                foreach ($string as $key => $val){
                    $string[$key] = self::strAddslashes($val);
                }
            } else{
                $string = trim($string);
                $string = function_exists('addslashes') ? addslashes($string) : mysql_real_escape_string($string);
            }
        }
        return $string;
    }





    /**
     * 发起 post 请求
     * @param String  接受请求的url
     * @param Array   提交的数据
     * @return String 返回请求的结果
     */
    public static function remotePost($url, $postArray=array(),$timeOut = 5){
        $postString = http_build_query($postArray, '', '&');
        if (function_exists('curl_init')){
            self::runCurlRequest($url,$postString,$timeOut);
        } else {
            self::runFileRequest($url,$postString,$timeOut);
        }
        return self::$postResult;
    }

    /**
     * @brief    使用curl发送请求
     * @param    $postUrl
     * @param    $postString
     * @param    $timeOut
     * @return   
     **/
    private static function runCurlRequest($postUrl,$postString,$timeOut){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeOut);
        curl_setopt($ch, CURLOPT_USERAGENT, self::$userAgent);
        self::$postResult = curl_exec($ch);
        curl_close($ch);  
    }

    /**
     * @brief    使用fopen发送请求
     * @param    $postUrl
     * @param    $postString
     * @param    $timeOut
     * @return   
     **/
    private static function runFileRequest($postUrl,$postString,$timeOut){
        $context = array(
            'http' => array(
                'method' => 'POST',
                'timeout'=>$timeOut,
                'user_agent' => self::$userAgent, 
                'header' => 'Content-Type: application/x-www-form-urlencoded' .  "\r\n" .
                'Content-Length: ' . strlen($postString). "\r\n", 
                'content' => $postString
            )
        );
        $contextID = stream_context_create($context);

        //第一获取方案
        $postResult = file_get_contents($postUrl, false, $contextID);
        if($postResult !== false){
            self::$postResult = $postResult;
            return;
        }

        //备用获取方案
        $sock = fopen($postUrl, 'r', false, $contextID);
        self::$postResult = '';
        if ($sock){
            while (!feof($sock)){
                self::$postResult .= fgets($sock, 4096);
            }
            fclose($sock);
        }
    }

}

