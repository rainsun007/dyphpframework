<?php
/**
 * 数据验证过滤器
 *
 * @author QingYu.Sun Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com
 * @copyright dyphp.com
 **/
class DyFilter
{
    /**
     * @brief    验证email
     *
     * @param   $email
     *
     * @return
     **/
    public static function isMail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * @brief    以浮点数验证值
     *
     * @param   $number
     *
     * @return
     **/
    public static function isFloat($number)
    {
        return filter_var($number, FILTER_VALIDATE_FLOAT);
    }

    /**
     * @brief    根据 regexp，兼容 Perl 的正则表达式来验证值。
     *
     * @param   $var
     * @param   $regexp
     *
     * @return
     **/
    public static function regexp($var, $regexp)
    {
        return filter_var($var, FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => $regexp)));
    }

    /**
     * @brief    把值作为 URL 来验证。
     *
     * @param   $url
     *
     * @return
     **/
    public static function isUrl($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL);
    }

    /**
     * @brief    把值作为 IP 地址来验证
     *
     * @param   $ip
     *
     * @return
     **/
    public static function isIp($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP);
    }

    /**
     * @brief    整数
     *
     * @param   $int
     * @param   $minRange
     * @param   $maxRange
     *
     * @return
     **/
    public static function isInt($int, $minRange = 0, $maxRange = PHP_INT_MAX)
    {
        if (!is_numeric($minRange) || !is_numeric($maxRange) || $maxRange < $minRange || $maxRange > PHP_INT_MAX) {
            return false;
        }
        $int_options = array('options' => array('min_range' => $minRange, 'max_range' => $maxRange));

        return filter_var($int, FILTER_VALIDATE_INT, $int_options);
    }

    /**
     * @brief    验证是否为英文字母
     *
     * @param   $var
     *
     * @return
     **/
    public static function isLetter($var)
    {
        return self::regexp($var, '/^[A-Za-z]+$/');
    }

    /**
     * @brief    验证是否为英文字母和数
     *
     * @param   $var
     *
     * @return
     **/
    public static function isLetterNum($var)
    {
        return self::regexp($var, '/^[A-Za-z0-9]+$/');
    }

    /**
     * @brief    验证中文
     *
     * @param   $var
     * @param   $addition
     *
     * @return
     **/
    public static function isZhCn($var, $addition = 'A-Za-z0-9_')
    {
        $addition = $addition ? $addition : '';

        return self::regexp($var, '/^[\x{4e00}-\x{9fa5}'.$addition.']+$/u');
    }

    /**
     * @brief    验证是否为账号(以字母开头 字母数据下划线的组合)
     *
     * @param   $var
     *
     * @return
     **/
    public static function isAccount($var, $minRange = 5, $maxRange = 16)
    {
        $minRange = $minRange - 1;
        $maxRange = $maxRange - 1;
        if ($minRange < 1 || $maxRange < $minRange) {
            return false;
        }

        return self::regexp($var, '/^[a-zA-Z][a-zA-Z0-9_]{'.$minRange.','.$maxRange.'}$/');
    }

    /**
     * html字符串过滤.
     *
     * @param string 需要处理的字符串
     * @param string
     **/
    public static function html($string, $fun = 'e')
    {
        $string = (string) $string;
        if ($fun == 'e') {
            $string = htmlentities($string, ENT_QUOTES, 'UTF-8');
        } elseif ($fun == 'ed') {
            $string = html_entity_decode($string, ENT_QUOTES, 'UTF-8');
        } elseif ($fun == 's') {
            $string = htmlspecialchars($string, ENT_QUOTES);
        } elseif ($fun == 'sd') {
            $string = htmlspecialchars_decode($string, ENT_QUOTES);
        }

        return $string;
    }

    /**
     * @brief    从数组中去除 HTML 和 PHP 标记
     *
     * @param   $data
     * @param   $tags
     *
     * @return
     **/
    public static function stripTagArr($data, $tags = null)
    {
        $stripped_data = array();
        foreach ($data as $value) {
            if (is_array($value)) {
                $stripped_data[] = self::stripTagArr($value, $tags);
            } else {
                $stripped_data[] = strip_tags($value, $tags);
            }
        }

        return $stripped_data;
    }
}
