<?php

/**
 * 公用方法工具类.
 *
 * @author 大宇 Email:dyphp.com@gmail.com
 *
 * @link http://www.dyphp.com/
 *
 * @copyright Copyright dyphp.com
 **/
class DyTools
{
    //-------------------------格式化-----------------------
    /**
     * 格式化字节
     *
     * @param int 字节数（一般为文件的大小）
     *
     * @return string
     **/
    public static function formatSize($size)
    {
        $sizes = array('Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $retstring = '%01.2f %s';
        $lastsizestring = end($sizes);
        foreach ($sizes as $sizestring) {
            if ($size < 1024) {
                break;
            }
            if ($sizestring != $lastsizestring) {
                $size /= 1024;
            }
        }
        if ($sizestring == $sizes[0]) {
            $retstring = '%01d %s';
        }

        return sprintf($retstring, $size, $sizestring);
    }

    /**
     * 格式化时间.
     *
     * @param int     $time     时间戳
     * @param boolean $period   时间周期格式
     * @param integer $just     $period=true时“刚刚”的时间周期
     * @param integer $minute   $period=true时“分钟”的时间周期
     * @param integer $hour     $period=true时“小时”的时间周期
     *
     * @return string
     */
    public static function formatTime($time, $period = false, $just = 60, $minute = 3600, $hour = 86400)
    {
        if ($period) {
            $time = time() - $time;
            if ($time < $just) {
                return '刚刚';
            } elseif ($time < $minute) {
                return  ceil($time / 60) . '分钟前';
            } elseif ($time < $hour) {
                return  ceil($time / 3600) . '小时前';
            } else {
                return self::formatTime($time);
            }
        }

        return date('Y-m-d H:i:s', $time);
    }

    /**
     * @abstract  树型分类格式化
     *
     * @param   $items  以id为key的二维数组
     *
     * @example
     * $classArr = array();
     * foreach($nav as $val){
     *      $classArr[$val->id] = array(
     *          'id'=>$val->id,    //必须字段
     *          'pid'=>$val->pid,  //必须字段
     *          'name'=>$val->name,
     *          'link'=>$val->cate_link,
     *      );
     * }
     *
     * @return  array
     **/
    public static function treeFormat($items, $childKeyName = 'child')
    {
        foreach ($items as $item) {
            $items[$item['pid']][$childKeyName][$item['id']] = &$items[$item['id']];
        }

        return isset($items[0][$childKeyName]) ? $items[0][$childKeyName] : array();
    }

    //-------------------------文件，目录-----------------------
    /**
     * 创建目录.
     *
     * @param string   目录路径
     * @param int      目录权限
     *
     * @return bool
     */
    public static function dirWrite($dir, $chmod = 0755)
    {
        if (!is_dir($dir) && !mkdir($dir, $chmod, true)) {
            return false;
        }

        if (!is_writable($dir) && !chmod($dir, $chmod)) {
            return false;
        }

        return true;
    }

    /**
     * log记录  此方法将按月创建目录.
     *
     * @param string  log信息
     * @param string  log类型   如info error warning debug，此参数不做限制
     * @param string  log保存目录   默认在 application/logs/app_log下
     * @param boolen  是否生成单独文件,默认不生成单独文件，如需不同$type生成单独文件设置为true
     * @param boolen  是否切割log，默认为按天切割log, 设置为false则只生成一个log文件
     **/
    public static function logs($message, $type = 'info', $logRootDir = '', $typeAlone = false, $logCut = true)
    {
        $formatTime = date('Y-m-d H:i:s', time());
        $source = DyPhpBase::$appType == 'web' ? DyRequest::getClientIp() . ' ' . DyRequest::getMethod() . ' ' . $_SERVER['REQUEST_URI'] : php_uname('n');
        $data = $formatTime . ' [' . $type . '] ' . $source . ' ' . $message . PHP_EOL;

        $logRootDir = empty($logRootDir) ? rtrim(DyPhpConfig::item('appPath'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'app_log' : $logRootDir;
        $logDir = rtrim($logRootDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . date('Y-m', time()) . DIRECTORY_SEPARATOR;
        if (!is_dir($logDir)) {
            self::dirWrite($logDir, 0777);
        }

        //处理文件切分与是否按类型生成单独文件
        $filePrefix = $typeAlone ? $type . '_' : '';
        $fileCut = $logCut ? date('Y-m-d', time()) : '';
        $fileName = $filePrefix . $fileCut;
        if ($fileName == '') {
            $fileName = 'app';
        } elseif ($fileName == $type . '_') {
            $fileName = $type;
        }

        $file = $logDir . $fileName . '.log';
        $fp = fopen($file, 'a');
        if ($fp) {
            fwrite($fp, $data);
            fclose($fp);
        }
    }

    /**
     * @brief    配制文件修改 也可用于其它文件内容修改
     *
     * @param   array   $cfgArr          替换映射数组
     * @param   string  $configTemplate  模版文件
     * @param   string  $config          生成后写入的文件
     *
     * @return  string
     **/
    public function setConfig($cfgArr, $configTemplate, $config)
    {
        if (!file_exists($configTemplate) || !file_exists($config)) {
            return false;
        }
        $search = array_keys($cfgArr);
        $replace = array_values($cfgArr);
        $contents = file_get_contents($configTemplate);
        $contents = str_replace($search, $replace, $contents);

        return file_put_contents($config, $contents);
    }

    //-------------------------api开发-----------------------
    /**
     * 简单json返回值格式化
     *
     * @param   int     $status             状态, 服务状态
     * @param   int     $code               服务代码,
     * @param   string  $message            服务说明信息
     * @param   mixed   $data               主体数据, 默认为空数组
     * @param   bool    $printAndExit       是否直接输出并执行exit(), 默认为不执行
     * @param   bool    $httpResponseCode   是否设置$code同时为http状态码,$printAndExit为true时生效
     *
     * @return  string
     **/
    public static function apiJson($status = 1, $code = 200, $message = '', $data = array(), $printAndExit = false, $httpResponseCode = false)
    {
        $dataArr = array('status' => $status, 'code' => $code, 'message' => $message, 'data' => $data);

        //以字面编码多字节 Unicode 字符(中文不被转码),自 PHP 5.4.0 起生效
        $result =  version_compare(PHP_VERSION, '5.4.0', '>=') ? json_encode($dataArr, JSON_UNESCAPED_UNICODE) : json_encode($dataArr);

        if (!$printAndExit) {
            return $result;
        }

        if ($httpResponseCode) {
            http_response_code($code);
        }

        echo $result;
        exit();
    }

    /**
     * 签名获取与验证
     * @param array   验签数组，建议自行在该数组中加入时间戳或随机数字段
     * @param string  对等加密秘钥
     * @param string  需要验证的签名，此参数不为空时为验证签名是否合法
     * 
     * @return mixed  $sign为空时返回string, 非空时返回bool 
     */
    public static function apiSign($params, $secret, $sign = '')
    {
        ksort($params);
        $paramsStr = '';
        foreach ($params as $key => $val) {
            $paramsStr .= $key . $val;
        }

        $signStr =  md5($paramsStr . $secret);

        return $sign == '' ?  $signStr : $signStr == $sign;
    }


    //-------------------------其它-----------------------
    /**
     * 获取生肖.
     *
     * @param int 完整的年份
     *
     * @return string 生肖
     **/
    public static function getAnimal($year)
    {
        $animal = array('猴', '鸡', '狗', '猪', '鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊');

        return $animal[$year % 12];
    }

    /**
     * 返回两个经纬度之间的直线距离.
     *
     * @param float 经度1
     * @param float 纬度1
     * @param float 经度2
     * @param float 纬度2
     *
     * @return float 单位：千米
     */
    public static function getDistance($lng1, $lat1, $lng2, $lat2)
    {
        $radLat1 = $lat1 * pi() / 180.0;
        $radLat2 = $lat2 * pi() / 180.0;
        $a = $radLat1 - $radLat2;
        $b = $lng1 * pi() / 180.0 - $lng2 * pi() / 180.0;
        $s = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
        $s = $s * 6378.137;
        $s = round($s * 10000) / 10000;

        return round($s, 1);
    }
}
