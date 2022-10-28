<?php
/**
 * 助手类.
 *
 * @author 大宇 Email:dyphp.com@gmail.com
 *
 * @link http://www.dyphp.com/
 *
 * @copyright Copyright dyphp.com
 **/
class ViewHelper
{
    //静态文件路径
    private static $staticServerPath = '';

    /**
     * 获取静态文件地址
     *
     * @param string 静态文件
     *
     * @return string
     **/
    public static function getStaticPath($path = '')
    {
        $path = ltrim($path, '/');
        if (self::$staticServerPath) {
            return self::$staticServerPath.$path;
        }

        $appHttpPath = DyCfg::item('appHttpPath') != '' ? DyCfg::item('appHttpPath').'/' : '';
        self::$staticServerPath = rtrim(STATIC_SERVER, '/').'/'.$appHttpPath.'static/';

        return self::$staticServerPath.$path;
    }

    /**
     * @brief    加载css
     *
     * @param   $css
     *
     * @return
     **/
    public static function regCss($css)
    {
        DyStatic::regCss(self::getStaticPath($css));
    }

    /**
     * @brief   移除css
     *
     * @param   $css
     *
     * @return
     **/
    public static function unregCss($css)
    {
        DyStatic::unregCss(self::getStaticPath($css));
    }

    /**
     * @brief    加载js
     *
     * @param   $script
     * @param   $position
     *
     * @return
     **/
    public static function regJs($script, $position = 'head')
    {
        DyStatic::regScript(self::getStaticPath($script), $position);
    }

    /**
     * @brief    移除js
     *
     * @param   $script
     * @param   $position
     *
     * @return
     **/
    public static function unregJs($script, $position = 'head')
    {
        DyStatic::unregScript(self::getStaticPath($script), $position);
    }

}