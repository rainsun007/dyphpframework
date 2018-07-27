<?php
/**
 * http请求处理库.
 *
 * @author 大宇 Email:dyphp.com@gmail.com
 *
 * @link http://www.dyphp.com/
 *
 * @copyright Copyright dyphp.com
 */
class DyRequest
{
    /**
     * url重定向
     *
     * @param $url    站内重定向该参数为controller/action 完整url重定向需要将$param参数设置为bool类型
     * @param $param  默认为array类型  为bool类型且值为false时将不进行url重组，直接执行重定向
     * @param $code
     * @param $method
     **/
    public static function redirect($url, $param = array(), $code = 302, $method = 'location')
    {
        $jumpUrl = $param === false ? $url : self::createUrl($url, $param);
        header($method == 'refresh' ? "Refresh:0;url = {$jumpUrl}" : "Location: {$jumpUrl}", true, $code);
        exit;
    }

    /**
     * 获取站点根url
     *
     * @return string
     **/
    public static function getSiteRootUrl()
    {
        return self::createUrl();
    }

    /**
     * 框架支持restful风格的url使用此方法可创建兼容性URL,使得url风格配制修改对生成的url无影响
     * 注意：此方法对urlManager配制的url重写不兼容
     *
     * @param string controller或controller/action
     * @param array  get参数
     * @param bool 强制以url正常get参数格式处理传入的参数
     *
     * @return string 完整的http访问地址
     **/
    public static function createUrl($ca = '', $param = array(), $forceGet = false)
    {
        $isRest = DyPhpConfig::getRestCa();
        $hideIndex = DyPhpConfig::getHideIndex();
        $index = $hideIndex ? '' : 'index'.EXT.($isRest ? '/' : '?');

        $ca = trim($ca, '/');
        $ca = $index.($isRest ? $ca : ($hideIndex ? '?' : '').'ca='.($ca == '' ? DYPHP_DEFAULT_CONTROLLER.'.'.DYPHP_DEFAULT_ACTION : str_replace('/', '.', $ca)));
        $getParam = $isRest ? '/' : '&';
        if (is_array($param)) {
            foreach ($param as $key => $val) {
                if (empty($key) || empty($val)) {
                    continue;
                }
                if ($isRest && $forceGet) {
                    $getParam .= $key.'='.$val.'&';
                } else {
                    $getParam .= $isRest ? $key.'/'.$val.'/' : $key.'='.$val.'&';
                }
            }
        }
        $getParam = $isRest && $forceGet ? '?'.trim(substr($getParam, 0, -1), '/') : substr($getParam, 0, -1);

        $appHttpPath = DyPhpConfig::item('appHttpPath') != '' ? DyPhpConfig::item('appHttpPath').'/' : '';
        return self::getServerName().'/'.$appHttpPath.$ca.$getParam;
    }

    /**
     * server name.
     *
     * @return string
     **/
    public static function getServerName()
    {
        $protocol = (isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) != 'off')) ? 'https://' : 'http://';

        if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
        } elseif (isset($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
        } else {
            $port = '';
            if (isset($_SERVER['SERVER_PORT'])) {
                $port = ':'.$_SERVER['SERVER_PORT'];
                if ((':80' == $port && 'http://' == $protocol) || (':443' == $port && 'https://' == $protocol)) {
                    $port = '';
                }
            }

            if (isset($_SERVER['SERVER_NAME'])) {
                $host = $_SERVER['SERVER_NAME'].$port;
            } elseif (isset($_SERVER['SERVER_ADDR'])) {
                $host = $_SERVER['SERVER_ADDR'].$port;
            }
        }

        return $protocol.$host;
    }

    /**
     * 获得客户端IP地址
     **/
    public static function getClientIp()
    {
        $ip = '0.0.0.0';
        if (getenv('HTTP_X_REAL_IP') && strcasecmp(getenv('HTTP_X_REAL_IP'), 'unknown')) {
            //nginx 代理模式下，获取客户端真实IP
            $ip = getenv('HTTP_X_REAL_IP'); 
        }elseif (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            //客户端的ip
            $ip = getenv('HTTP_CLIENT_IP'); 
        } elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            //浏览当前页面的用户计算机的网关
            $ip = getenv('HTTP_X_FORWARDED_FOR'); 
        } elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            //浏览当前页面的用户计算机的ip地址
            $ip = getenv('REMOTE_ADDR');
        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            //浏览当前页面的用户计算机的ip地址
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return ip2long($ip) > 0 ? $ip : '0.0.0.0';
    }

    /**
     * ajax请求判断.
     *
     * @return bool
     **/
    public static function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    /**
     * get请求判断.
     *
     * @return bool
     **/
    public static function isGet()
    {
        return self::getMethod() === 'GET';
    }

    /**
     * post请求判断.
     *
     * @return bool
     **/
    public static function isPost()
    {
        return self::getMethod() === 'POST';
    }

    /**
     * head请求判断.
     *
     * @return bool
     **/
    public static function isHead()
    {
        return self::getMethod() === 'HEAD';
    }

    /**
     * options请求判断.
     *
     * @return bool
     **/
    public static function isOptions()
    {
        return self::getMethod() === 'OPTIONS';
    }

    /**
     * put请求判断.
     *
     * @return bool
     **/
    public static function isPut()
    {
        return self::getMethod() === 'PUT';
    }

    /**
     * delete请求判断.
     *
     * @return bool
     **/
    public static function isDelete()
    {
        return self::getMethod() === 'DELETE';
    }

    /**
     * tarce请求判断.
     *
     * @return bool
     **/
    public static function isTarce()
    {
        return self::getMethod() === 'TARCE';
    }

    /**
     * 返回当前请求的方法 返回值为大写 默认返回GET.
     *
     * @return string
     */
    public static function getMethod()
    {
        $method = '';
        if (isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        } elseif (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
            $method = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
        } elseif (isset($_SERVER['REQUEST_METHOD'])) {
            $method = strtoupper($_SERVER['REQUEST_METHOD']);
        }

        $methodArr = array('GET', 'POST', 'HEAD', 'OPTIONS', 'PUT', 'DELETE', 'TARCE');
        return in_array($method, $methodArr) ? $method : 'GET';
    }

    /**
     * 获取get请求的字串(任何类型都可使用此方法，为了数据类型的严禁建议使用已提供的对应的方法).
     *
     * @param string  get请求的变量名
     * @param string  默认值
     *
     * @return string get变量名对应的值
     */
    public static function getStr($key, $default = '')
    {
        $result = isset($_GET[$key]) ? self::getFilterString($_GET[$key], $default) : $default;
        return $result == '' && $default != '' ? $default : $result;
    }

    /**
     * 获取get请求的整数.
     *
     * @param string get请求的变量名
     * @param int    默认值
     * @param int    最小值范围，小于此值将返回0
     *
     * @return int get变量名对应的值
     */
    public static function getInt($key, $default = 0, $minRange = 0)
    {
        return isset($_GET[$key]) ? self::getFilterInt($_GET[$key], $default, $minRange) : $default;
    }

    /**
     * 获取get请求的浮点数.
     *
     * @param string get请求的变量名
     * @param int    默认值
     *
     * @return int get变量名对应的值
     */
    public static function getFloat($key, $default = 0.00)
    {
        return isset($_GET[$key]) ? self::getFilterFloat($_GET[$key], $default) : $default;
    }

    /**
     * 获取POST请求的数组.
     *
     * @param string post请求的变量名
     * @param array  默认值
     *
     * @return int post变量名对应的值
     **/
    public static function getArr($key, $default = array())
    {
        return isset($_GET[$key]) && is_array($_GET[$key]) ? self::getFilterString($_GET[$key], $default) : $default;
    }

    /**
     * 获取post请求的字串(任何类型都可使用此方法，为了数据类型的严禁建议使用已提供的对应的方法).
     *
     * @param string  post请求的变量名
     * @param string  默认值
     *
     * @return string post变量名对应的值
     */
    public static function postStr($key, $default = '')
    {
        $result = isset($_POST[$key]) ? self::getFilterString($_POST[$key], $default) : $default;
        return $result == '' && $default != '' ? $default : $result;
    }

    /**
     * 获取post请求的整数.
     *
     * @param string post请求的变量名
     * @param int    默认值
     * @param int    最小值范围，小于此值将返回0
     *
     * @return int post变量名对应的值
     */
    public static function postInt($key, $default = 0, $minRange = 0)
    {
        return isset($_POST[$key]) ? self::getFilterInt($_POST[$key], $default, $minRange) : $default;
    }

    /**
     * 获取get请求的浮点数.
     *
     * @param string get请求的变量名
     * @param int    默认值
     *
     * @return int get变量名对应的值
     */
    public static function postFloat($key, $default = 0.00)
    {
        return isset($_POST[$key]) ? self::getFilterFloat($_POST[$key], $default) : $default;
    }

    /**
     * 获取POST请求的数组.
     *
     * @param string post请求的变量名
     * @param array  默认值
     *
     * @return int post变量名对应的值
     **/
    public static function postArr($key, $default = array())
    {
        return isset($_POST[$key]) && is_array($_POST[$key]) ? self::getFilterString($_POST[$key], $default) : $default;
    }

    /**
     * @brief    获取get提交原始数据 如系统开启了get_magic_quotes_gpc会数据进行stripcslashes操作 此方法要注册数据安全性  不建议使用
     *
     * @param   $paramKey
     * @param   $default
     *
     * @return  mix
     **/
    public static function getOriginal($paramKey = '', $default = '')
    {
        return self::getOriginalParam($paramKey, 'get', $default);
    }

    /**
     * @brief    获取post提交原始数据 如系统开启了get_magic_quotes_gpc会数据进行stripcslashes操作 此方法要注册数据安全性  不建议使用
     *
     * @param   $paramKey
     * @param   $default
     *
     * @return  mix
     **/
    public static function postOriginal($paramKey = '', $default = '')
    {
        return self::getOriginalParam($paramKey, 'post', $default);
    }

    /**
     * 通过输入流获取json数据并转为array返回
     * enctype="multipart/form-data" 的时候 php://input 是无效的
     * @return array
     **/
    public static function getJosnInput()
    {
        $val = file_get_contents('php://input');

        return json_decode($val, true);
    }

    /**
     * 简单的post请求
     *
     * @param string  接受请求的url
     * @param array   提交的数据
     * @param int     超时时间(单位：秒)
     * @param string  浏览器user agent
     *
     * @return string 返回请求的结果
     */
    public static function remotePost($url, $postArray = array(), $timeOut = 5, $userAgent = '')
    {
        $userAgent = $userAgent ? $userAgent : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.96 Safari/537.36';

        $postString = http_build_query($postArray, '', '&');

        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postString);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeOut);
            curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
            $postResult = curl_exec($ch);
            curl_close($ch);
            return $postResult;
        } else {
            $context = array(
                'http' => array(
                    'method' => 'POST',
                    'timeout' => $timeOut,
                    'user_agent' => $userAgent,
                    'header' => 'Content-Type: application/x-www-form-urlencoded'."\r\n".
                    'Content-Length: '.strlen($postString)."\r\n",
                    'content' => $postString,
                ),
            );
            $contextID = stream_context_create($context);
    
            $postResult = file_get_contents($postUrl, false, $contextID);
            if ($postResult !== false) {
                return $postResult;
            }
    
            $sock = fopen($postUrl, 'r', false, $contextID);
            $postResult = '';
            if ($sock) {
                while (!feof($sock)) {
                    $postResult .= fgets($sock, 4096);
                }
                fclose($sock);
            }
            return $postResult;
        }
    }

    /**
     * 获取提交的原始数据
     *
     * @param $paramKey
     * @param $method
     * @param $default
     *
     * @return  mixed
     **/
    private static function getOriginalParam($paramKey = '', $method = 'get', $default = '')
    {
        $methodArr = $method == 'get' ? $_GET : $_POST;
        if (!isset($methodArr[$paramKey])) {
            return $default;
        }

        $value = $methodArr[$paramKey];
        if (get_magic_quotes_gpc()) {
            $value = stripcslashes($value);
        }

        return $value;
    }

    /**
     * 转义字串.
     *
     * @param string  需要被转义的字串
     *
     * @return string 转义的字串
     */
    private static function getFilterString($requestValue, $default)
    {
        $value = is_array($requestValue) ? array() : '';

        return isset($requestValue) ? self::strAddslashes($requestValue) : $value;
    }

    /**
     * 处理整型数据.
     *
     * @param int  需要被验证的值
     *
     * @return int 返回验证过的值
     */
    private static function getFilterInt($requestValue, $default,$minRange = 0)
    {
        return DyFilter::isInt($requestValue,$minRange) !== false ? $requestValue : $default;
    }

    /**
     * 处理浮点型数据
     *
     * @param float 需要被验证的值
     *
     * @return float 返回验证过的值
     **/
    private static function getFilterFloat($requestValue, $default)
    {
        return DyFilter::isFloat($requestValue) !== false ? $requestValue : $default;
    }

    /**
     * 字符串转义.
     *
     * @param string|array  需要转义的字符串或数组
     *
     * @return mixed 转义后的字符串
     **/
    private static function strAddslashes($requestValue)
    {
        if (!get_magic_quotes_gpc()) {
            if (is_array($requestValue)) {
                foreach ($string as $key => $val) {
                    $requestValue[$key] = self::strAddslashes($val);
                }
            } else {
                $requestValue = addslashes(trim($requestValue));
            }
        }

        return $requestValue;
    }
}
