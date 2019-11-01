<?php
/**
 * 缓存工具类.
 *
 * @author 大宇 Email:dyphp.com@gmail.com
 *
 * @link http://www.dyphp.com/
 *
 * @copyright Copyright dyphp.com
 **/
class DyPhpDebug
{
    // sql请求分析数组
    public static $queries = array();

    // debug表格样式
    private static $styles = array(
        'table' => 'clear:both;margin:5px 0px;border:1px solid #CCC;font-weight:bold;font-size:14px;font:800 14px/25px simsun;color:#FFF;width:100%;background:#333;',
        'titleTr' => 'background:#666666;',
        'titleTd' => 'padding-left:5px;border:1px solid #666666;',
        'tdKey' => 'padding-left:5px;padding-right:5px;width:45px;border:1px solid #3D4F52;text-align:center;',
        'tdVal' => 'padding:0px 2px;word-break:break-all;word-wrap:break-word;', //主体内容td
        'item' => 'border:1px solid #3D4F52;margin-top:1px;margin-bottom:1px;padding:2px 5px;height:auto;',  //tdval样式的子样式
        'c1' => '#BDB76B;', //行字体颜色1
        'c2' => '#87CEEB;', //行字体颜色2
        'obj1' => 'padding-left:10px;color:#18c334', //file
        'obj2' => 'padding-left:10px;color:#0080FF', //sql
        'obj3' => 'padding-left:10px;color:#CD5C5C', //param
        'total' => 'padding-right:10px;text-align:right;border:1px solid #3D4F52;', //表格脚
    );

    /**
     * 获取加载的文件(web).
     *
     * @param bool 只显示出app加载文件, false为所有加载文件包括框架文件
     **/
    public static function getLoadFiles($onlyApp = true)
    {
        if (!DyPhpBase::$debug) {
            return '';
        }

        echo '<table cellspacing="1" style="'.self::$styles['table'].'">';
        echo '<tr><td colspan="3" style="'.self::$styles['obj1'].'"><b>INCLUDE FILES</b></td></tr>';
        echo '<tr style="'.self::$styles['titleTr'].'"><td style="'.self::$styles['titleTd'].'width:60px;">Index</td><td style="'.self::$styles['titleTd'].'">File</td><td style="'.self::$styles['titleTd'].'">Size</td></tr>';
        $allSize = 0;
        $num = 0;
        $files = get_included_files();
        foreach ($files as $key => $file) {
            $indexNum = $key++;
            if ($onlyApp) {
                if (strpos($file, DYPHP_PATH) !== false) {
                    continue;
                }
                $indexNum = $num++;
            }
            $size = filesize($file);

            echo '<tr style="color:'.($indexNum % 2 == 1 ? self::$styles['c1'] : self::$styles['c2']).'">';
            echo '<td style="'.self::$styles['tdKey'].'">'.$indexNum.
                '</td><td style="'.self::$styles['tdVal'].'">'.'<div style="'.self::$styles['item'].'">'.$file.'</div>'.
                '</td><td style="'.self::$styles['tdKey'].'width:100px;">'.DyTools::formatSize($size).'</td>';
            echo '</tr>';
            $allSize += $size;
        }
        echo '<tr><td colspan="3" style="'.self::$styles['total'].'">Total：'.DyTools::formatSize($allSize).'</td></tr>';
        echo '</table>';
    }

    /**
     * 获取加载的文件(console).
     *
     * @param bool 只显示出app加载文件, false为所有加载文件包括框架文件
     **/
    public static function getLoadFilesConsole($onlyApp = true)
    {
        if (!DyPhpBase::$debug) {
            return '';
        }

        $line = str_repeat('-', 120).PHP_EOL;
        echo PHP_EOL.'[INCLUDE FILES]'.PHP_EOL;
        echo  $line;
        echo '|Index | Size    | File '.PHP_EOL;
        echo  $line;
        $allSize = $num = 0;
        $files = get_included_files();
        foreach ($files as $key => $file) {
            $indexNum = $key++;
            if ($onlyApp) {
                if (strpos($file, DYPHP_PATH) !== false) {
                    continue;
                }
                $indexNum = $num++;
            }
            $size = filesize($file);

            echo '|'.$indexNum.'     | '.DyTools::formatSize($size).' | '.$file.PHP_EOL;
            echo $line;
            $allSize += $size;
        }
        echo '|Total | '.DyTools::formatSize($allSize).' |'.PHP_EOL;
        echo  $line.PHP_EOL;
    }

    /**
     *  获取加载的sql(web).
     **/
    public static function getLoadQuery()
    {
        if (!DyPhpBase::$debug) {
            return '';
        }
        $time = 0;
        echo '<table cellspacing="1" style="'.self::$styles['table'].'">';
        echo '<tr><td colspan="3" style="'.self::$styles['obj2'].'"><b>EXPLAIN SQL</b></td></tr>';
        echo '<tr style="'.self::$styles['titleTr'].'"><td style="'.self::$styles['titleTd'].'width:60px;">Index</td><td style="'.self::$styles['titleTd'].'">SQL</td><td style="'.self::$styles['titleTd'].'">Time</td></tr>';
        foreach (self::$queries as $key => $query) {
            echo '<tr style="color:'.($key % 2 == 1 ? self::$styles['c1'] : self::$styles['c2']).'">';
            echo '<td style="'.self::$styles['tdKey'].'">'.$key.'</td>
                <td style="'.self::$styles['tdVal'].'">'.'<div style="'.self::$styles['item'].'">'.DyFilter::html($query['sql']).self::mysqlExplain($query['explain']).'</div></td>
                <td style="'.self::$styles['tdKey'].'width:100px;">'.self::getReadableTime($query['time']).'<br /></td>';
            echo '</tr>';
            $time += $query['time'];
        }
        echo '<tr><td colspan="3" style="'.self::$styles['total'].'">Total：'.self::getReadableTime($time).'</td></tr>';
        echo '</table>';
    }

    /**
     *  获取加载的sql(console).
     **/
    public static function getLoadQueryConsole()
    {
        if (!DyPhpBase::$debug) {
            return '';
        }

        $line = str_repeat('-', 120).PHP_EOL;
        $time = 0;
        echo PHP_EOL.'[EXPLAIN SQL]'.PHP_EOL;
        echo  $line;
        echo '|Index | Time    | SQL '.PHP_EOL;
        echo  $line;
        foreach (self::$queries as $key => $query) {
            //echo $key.DyFilter::html($query['sql']).self::mysqlExplain($query['explain']).self::getReadableTime($query['time']).PHP_EOL;
            echo '|'.$key.'     | '.self::getReadableTime($query['time']).' | '.DyFilter::html($query['sql']).PHP_EOL;
            echo  $line;
            $time += $query['time'];
        }
        echo '|Total | '.self::getReadableTime($time).' |'.PHP_EOL;
        echo  $line.PHP_EOL;
    }

    /**
     * 获取全局变量(web)
     **/
    public static function getGlobalParams()
    {
        if (!DyPhpBase::$debug) {
            return '';
        }

        $usageMemory = (!function_exists('memory_get_usage')) ? '0' : round(memory_get_usage() / 1024 / 1024, 2).'MB';

        $paramsArr = array(
            'RUNTIME' => '<font color="#18c334">'.DyPhpBase::execTime().'Seconds</font>; '.' <font color="#0080FF">Memory:'.$usageMemory.'</font>; '.PHP_OS.', '.$_SERVER['SERVER_SOFTWARE'].', php:'.PHP_VERSION,
            'EXECUTE' => 'Module:<font color="#0080FF">'.DyPhpBase::app()->module.'</font>; Controller:<font color="#0080FF">'.DyPhpBase::app()->cid.'</font>; Action:<font color="#0080FF">'.DyPhpBase::app()->aid.'</font>',
            'SESSION' => isset($_SESSION) ? $_SESSION : array(),
            'COOKIE' => isset($_COOKIE) ? $_COOKIE : array(),
            'POST' => isset($_POST) ? $_POST : array(),
            'GET' => isset($_GET) ? $_GET : array(),
            'INCLUDE PATH' => get_include_path(),
        );

        echo '<table cellspacing="1" style="'.self::$styles['table'].'">';
        echo '<tr><td colspan="2" style="'.self::$styles['obj3'].'">PARAMS</td></tr>';
        echo '<tr style="'.self::$styles['titleTr'].'"><td style="'.self::$styles['titleTd'].'">Type</td><td style="'.self::$styles['titleTd'].'">Content</td></tr>';

        $i = 0;  //用于处理行字体颜色
        foreach ($paramsArr as $key => $val) {
            echo '<tr style="color:'.($i % 2 == 1 ? self::$styles['c1'] : self::$styles['c2']).'">';
            echo '<td style="'.self::$styles['tdKey'].'">'.$key.'</td>';
            echo '<td style="'.self::$styles['tdVal'].'">';

            //RUNTIME,EXECUTE
            if (!is_array($val)) {
                echo '<div style="'.self::$styles['item'].'">'.$val.'</div>';
                ++$i;
                continue;
            }

            //SESSION,COOKIE,POST,GET
            if (empty($val)) {
                echo '<div style="'.self::$styles['item'].'">NULL</div>';
                ++$i;
                continue;
            }

            foreach ($val as $k => $v) {
                $cookieArr = DyPhpConfig::item('cookie');
                if ($key == 'COOKIE' && isset($cookieArr['prefix']) && $cookieArr['prefix'] == substr($k, 0, strlen($cookieArr['prefix']))) {
                    $v = DyCookie::get(substr($k, strlen($cookieArr['prefix'])));
                } elseif ($key == 'SESSION') {
                    $k = DyPhpConfig::item('appID') && strpos($k, DyPhpConfig::item('appID')) === 0 ? substr($k, strlen(DyPhpConfig::item('appID')) + 1) : $k;
                }
                echo '<div style="'.self::$styles['item'].'">'.$k.'=>';
                var_export($v);
                echo '</div>';
            }

            echo '</td></tr>';
            ++$i;
        }

        echo '<tr style="height:0;"><td></td><td></td></tr>';
        echo '</table>';
    }

    /**
     * 获取全局变量(console)
     **/
    public static function getGlobalParamsConsole()
    {
        if (!DyPhpBase::$debug) {
            return '';
        }

        $usageMemory = (!function_exists('memory_get_usage')) ? '0' : round(memory_get_usage() / 1024 / 1024, 2).'MB';

        $paramsArr = array(
            'RUNTIME' => DyPhpBase::execTime().'Seconds ; '.' Memory:'.$usageMemory.' ; '.PHP_OS.' ; php:'.PHP_VERSION,
            'EXECUTE' => 'Module:'.DyPhpBase::app()->module.' Controller:'.DyPhpBase::app()->cid.' Action:'.DyPhpBase::app()->aid,
            'INCLUDE PATH' => get_include_path(),
        );

        $line = str_repeat('-', 120).PHP_EOL;
        echo PHP_EOL.'[PARAMS]'.PHP_EOL;
        echo  $line;
        echo '|Type    | Content '.PHP_EOL;
        echo  $line;
        foreach ($paramsArr as $key => $val) {
            $val = is_array($val) ? var_export($val, true) : $val;
            echo '|'.$key.' | '.$val.PHP_EOL;
            echo  $line;
        }
        echo PHP_EOL;
    }

    /**
     * 开始时间.
     **/
    public static function startTime()
    {
        list($usec, $sec) = explode(' ', microtime());
        $time = (float) $usec + (float) $sec;

        return $time;
    }

    /**
     * 结束时间.
     **/
    public static function endTime($beginTime)
    {
        list($usec, $sec) = explode(' ', microtime());
        $endTime = (float) $usec + (float) $sec;

        return  number_format($endTime - $beginTime, 6, '.', '');
    }

    /**
     * 格式化sql时间.
     *
     * @param float 时间戳
     **/
    private static function getReadableTime($time)
    {
        return number_format($time, 4, '.', '').'s';
    }

    /**
     * mysql explain格式化.
     *
     * @param array explain返回的数组类型数据
     **/
    private static function mysqlExplain($explain = '')
    {
        if (empty($explain)) {
            return '';
        }

        $keyStr = $valStr = '';
        foreach ($explain as $key => $val) {
            $keyStr .= '<td style="border:1px solid #3D4F52;padding:0px 5px;">'.$key.'</td>';
            $val = $val === null ? 'NULL' : $val;
            $valStr .= '<td style="border:1px solid #3D4F52;padding:0px 5px;">'.$val.'</td>';
        }
        $table = '<br /><table cellspacing="1" style="border:0px;color:#000033;"><tr>';

        return $table.$keyStr.'</tr><tr>'.$valStr.'</tr></table>';
    }
}
