<?php
/**
 * 公用方法工具类.
 *
 * @author 大宇 Email:dyphp.com@gmail.com
 *
 * @link http://www.dyphp.com/
 *
 * @copyright Copyright 2011 dyphp.com
 **/
class DyTools
{
    //-------------------------格式化-----------------------
    /**
     * 格式化字节
     *
     * @param 字节数
     * @param
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
     * @param type $time
     * @param type $period
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
                return  ceil($time / 60).'分钟前';
            } elseif ($time < $hour) {
                return  ceil($time / 3600).'小时前';
            } else {
                return self::formatTime($time);
            }
        }

        return date('Y-m-d H:i:s', $time);
    }

    /**
     * @brief    分类格式化
     *
     * @param   $items
     *                 foreach($nav as $val){
     *                 $classArr[$val->id] = array(
     *                 'id'=>$val->id,
     *                 'name'=>$val->name,
     *                 'pid'=>$val->pid,
     *                 'link'=>$val->cate_link,
     *                 );
     *                 }
     *
     * @return
     **/
    public static function treeFormat($items)
    {
        foreach ($items as $item) {
            $items[$item['pid']]['child'][$item['id']] = &$items[$item['id']];
        }

        return isset($items[0]['child']) ? $items[0]['child'] : array();
    }

    //-------------------------文件，目录-----------------------
    /**
     * 创建目录.
     *
     * @param string
     * @param ctal
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
     * log记录  按年月创建目录 每天一个log文件.
     *
     * @param string  log信息
     * @param string  log类型   如info error warning debug，此参数不做限制
     * @param string  log保存目录   默认在 application/logs/app_log下
     * @param boolen  是否生成单独文件   如需不同$type生成单独文件设置为true
     * @param boolen  是否切割log，默认为按天切割log, 设置为false则只生成一个log文件
     **/
    public static function logs($message, $type = 'info', $logRootDir = '', $typeAlone = false, $logCut = true)
    {
        if (function_exists('ini_get') && ini_get('date.timezone') == '' && function_exists('date_default_timezone_set')) {
            date_default_timezone_set('PRC');
        }

        $formatTime = date('Y-m-d H:i:s', time());
        $data = $formatTime.' ['.$type.'] '.self::getClientIp().' '.$_SERVER['REQUEST_URI']."\n".$message."\n\n";

        $logRootDir = empty($logRootDir) ? rtrim(DyPhpConfig::item('appPath'), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR.'app_log' : $logRootDir;
        $logDir = rtrim($logRootDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.date('Y-m', time()).DIRECTORY_SEPARATOR;
        if (!is_dir($logDir)) {
            self::dirWrite($logDir, 0777);
        }

        //处理文件切分与是否按类型生成单独文件
        $filePrefix = $typeAlone ? $type.'_' : '';
        $fileCut = $logCut ? date('Y-m-d', time()) : '';
        $fileName = $filePrefix.$fileCut;
        if ($fileName == '') {
            $fileName = 'app';
        } elseif ($fileName == $type.'_') {
            $fileName = $type;
        }

        $file = $logDir.$fileName.'.log';
        $fp = fopen($file, 'a');
        if ($fp) {
            fwrite($fp, $data);
            fclose($fp);
        }
    }

    /**
     * @brief    配制文件修改 也可用于其它文件内容修改
     *
     * @param   $cfgArr
     * @param   $configTemplate
     * @param   $config
     *
     * @return
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

    //-------------------------其它-----------------------
    /**
     * 获得客户端IP地址
     **/
    public static function getClientIp()
    {
        if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $ip = getenv('REMOTE_ADDR');
        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = '0.0.0.0';
        }

        return $ip;
    }

    /**
     * 获取生肖.
     *
     * @param int 完整的年份
     *
     * @return 生肖
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
     * @return 单位：千米
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

    /**
     * @brief    简单json返回值格式化
     *
     * @param   $status  状态
     * @param   $code    代码
     * @param   $message 信息
     * @param   $data
     *
     * @return
     **/
    public static function apiJson($status = 1, $code = 200, $message = '', $data = '')
    {
        $dataArr = array('status' => $status, 'code' => $code, 'message' => $message, 'data' => $data);

        return json_encode($dataArr);
    }

    /**
     * @brief    获取6位数字的验证码
     *
     * @return
     **/
    public static function getVerifyCode()
    {
        $numArr = range(0, 9);
        $randArr = array();
        for ($i = 0; $i < 6; ++$i) {
            $randArr[] = array_rand($numArr);
        }
        shuffle($randArr);

        return implode('', $randArr);
    }
}
