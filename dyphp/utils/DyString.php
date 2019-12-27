<?php
/**
 * 字符串加密，解密工具类
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright dyphp.com
 **/
class DyString
{
    /**
     * 加密字符串
     * @param string 要加密的字符串
     * @param string 加密密钥
     * @param int    加密过期时间,单位：秒， 0为不过期 -1为加密作废
     **/
    public static function encodeStr($string, $key='', $expiry=0)
    {
        if (empty($string) || $expiry<-1) {
            return '';
        }

        $key = !empty($key) ? $key : DyPhpConfig::item('secretKey');
        if (extension_loaded('openssl')) {
            $expiry = $expiry > 0 ? time() + $expiry : $expiry;
            $string = openssl_encrypt($expiry.'|'.$string.'_dysc_'.mt_rand(0, 999), "AES-256-CBC", substr(md5($key), 0, 20), 0, substr(md5($key), 8, -8));
            $string = base64_encode($string);
        } else {
            $string = self::authCode('ENCODE', $string, $key, $expiry);
        }

        return  str_replace(array('+','/'), array('*','_'), $string);
    }

    /**
     * 解密字符串
     * @param string 要解密的字符串
     * @param string 解密密钥
     **/
    public static function decodeStr($string, $key='')
    {
        if (empty($string)) {
            return '';
        }

        $key = !empty($key) ? $key : DyPhpConfig::item('secretKey');
        $string = str_replace(array('*','_'), array('+','/'), $string);

        if (extension_loaded('openssl')) {
            $decryptStr = openssl_decrypt(base64_decode($string), "AES-256-CBC", substr(md5($key), 0, 20), 0, substr(md5($key), 8, -8));
            return self::decodeCheck($decryptStr);
        } else {
            return self::authCode('DECODE', $string, $key);
        }
    }

    /**
     * 判断字符串是否为utf8
     * @param string 需要验证的字符串
     **/
    public static function isUtf8($word)
    {
        if (function_exists('mb_detect_encoding')) {
            return mb_detect_encoding($word, 'UTF-8, ISO-8859-1, GBK') == 'UTF-8' ? true : false;
        }

        if (preg_match("/^([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){1}/", $word) == true || preg_match("/([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){1}$/", $word) == true || preg_match("/([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){2,}/", $word) == true) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 中文编码转为utf8
     * @param  string|array   $str 需要转码的字符串
     * @param  string         $inCharset 输入的字符集
     * @return string
     **/
    public static function zhcnToUtf8($str='', $inCharset='gb2312')
    {
        if (is_array($str)) {
            foreach ($str as $key => $value) {
                $str[$key] = self::zhcnToUtf8($value, $inCharset);
            }
            return $str;
        }
        if (empty($str) || self::isUtf8($str)) {
            return $str;
        }
        return iconv($inCharset, "UTF-8", $str);
    }

    /**
     * 字符串切割
     * 
     * @param    string  $str       需要处理的字符串
     * @param    int     $start     切割的起始位置
     * @param    int     $length    切割长度
     * @param    string  $charset   字附编码
     * @param    bool    $suffix    长度超出部分时后缀
     * 
     * @return   mixed
     **/
    public static function cutStr($str, $start=0, $length, $charset="utf-8", $suffix=false)
    {
        if ($start == 0 && self::length($str) <= $length) {
            return $str;
        }

        if (function_exists("mb_substr")) {
            $slice = mb_substr($str, $start, $length, $charset);
            return $suffix ? $slice."…" : $slice;
        } elseif (function_exists('iconv_substr')) {
            $slice = iconv_substr($str, $start, $length, $charset);
            return $suffix ? $slice."…" : $slice;
        }

        $re = array();
        $re['utf-8']  = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        if ($match) {
            $slice = join("", array_slice($match[0], $start, $length));
            return $suffix ? $slice."…" : $slice;
        }
        return false;
    }

    /**
     * 字符串转义
     * @param string  需要转义的字符串
     * @param bool    是否做强行转义
     * @param bool    是否使用stripcslashes反转义
     * @return string 转义后的字符串
     **/
    public static function slashes($string, $force = false, $strip = false)
    {
        if (!get_magic_quotes_gpc() || $force) {
            if (is_array($string)) {
                foreach ($string as $key => $val) {
                    $string[$key] = self::slashes($val, $force);
                }
            } else {
                $string = trim($string);
                $string = $strip ? stripcslashes($string) : addslashes($string);
            }
        }
        return $string;
    }

    /**
     * 字符长度计算
     * 
     * @param  string  $str
     * @param  bool    true为两个英文等于一个中文长度
     * @param  string  $charset
     * 
     * @return int
     */
    public static function length($str, $en2=true, $charset='utf-8')
    {
        if (function_exists("mb_strlen")) {
            return mb_strlen($str, $charset);
        }

        if ($charset=='utf-8') {
            $str = iconv('utf-8', 'gbk//ignore', $str);
        }
        $num = strlen($str);
        $cnNum = 0;
        for ($i=0;$i<$num;$i++) {
            if (ord(substr($str, $i+1, 1))>127) {
                $cnNum++;
                $i++;
            }
        }
        $enNum = $num-($cnNum*2);
        $number = $en2 ? ($enNum/2)+$cnNum : $enNum+$cnNum;
        return ceil($number);
    }

    /**
     * 生成唯一的32位随机字符串(md5值)
     **/
    public static function randomStr()
    {
        return md5(str_shuffle(chr(mt_rand(32, 126)) . uniqid() . microtime(true)));
    }

    /**
     * @brief    全角半角互转
     * 
     * @param    string  $str
     * @param    bool    $flip  翻转
     * 
     * @return   string
     **/
    public static function transfer($str, $flip=false)
    {
        if (empty($str) || !is_string($str)) {
            return '';
        }
        $arr = array(
            '０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4',
            '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
            'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E',
            'Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J',
            'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O',
            'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T',
            'Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y',
            'Ｚ' => 'Z', 'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd',
            'ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i',
            'ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n',
            'ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's',
            'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x',
            'ｙ' => 'y', 'ｚ' => 'z',
            '（' => '(', '）' => ')', '［' => '[', '］' => ']', '【' => '[',
            '】' => ']', '〖' => '[', '〗' => ']', '「' => '[', '」' => ']',
            '『' => '[', '』' => ']', '｛' => '{', '｝' => '}', '《' => '<',
            '》' => '>',
            '％' => '%', '＋' => '+', '—' => '-', '－' => '-', '～' => '-',
            '：' => ':', '。' => '.', '、' => ',', '，' => ',',
            '；' => ';', '？' => '?', '！' => '!',  '“' => '"','”' => '"',
            '＂' => '"', '＇' => '`', '｀' => '`', '｜' => '|', '〃' => '"',
            '　' => ' '
        );
        if ($flip) {
            $arr = array_flip($arr);
        }

        return strtr($str, $arr);
    }

    /**
     * @brief  字符串加密、解密(经简单改造)
     * 
     * @author DZ
     * @param  string   操作类型，ENCODE：加密，DECODE：解密
     * @param  string   加密的对象
     * @param  string   加密附加密钥值
     * @param  int      过期时间，0为不过期
     * 
     * @return string
     * */
    private static function authCode($operation = 'DECODE', $string, $key = '', $expiry = 0)
    {
        $ckey_length = 4;
        $key = md5(!empty($key) ? $key : DyPhpConfig::item('secretKey'));
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';

        $cryptkey = $keya.md5($keya.$keyc);
        $key_length = strlen($cryptkey);

        $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
        $string_length = strlen($string);

        $result = '';
        $box = range(0, 255);

        $rndkey = array();
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }

        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }

        if ($operation == 'DECODE') {
            if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            return $keyc.str_replace('=', '', base64_encode($result));
        }
    }

    /**
     * 解密结果检证
     *
     * @param  string $decryptStr 解码后的原数据
     * 
     * @return string
     */
    private static function decodeCheck($decryptStr)
    {
        if ($decryptStr == '') {
            return '';
        }

        //解密字符串转为数组用以验证是否过期
        $re = explode('|', substr($decryptStr, 0, strrpos($decryptStr, '_dysc_')));
        if (count($re) != 2) {
            return '';
        }

        //判断过期时间的合法性，处理设置为过期的字符串,
        if (!is_numeric($re[0]) || $re[0] == -1) {
            return '';
        }

        if ($re[0] == 0) {
            return $re[1]; //永不过期直接返回数据
        } else {
            return $re[0] < time() ? '' : $re[1];
        }
    }
}
