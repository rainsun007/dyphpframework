<?php
/**
 * 静态文件管理器
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright dyphp.com
 **/
class DyStatic
{
    //重组计算锁
    private static $recombinationLock = false;

    //js配制
    //所有的js存储数组
    private static $jsArr = array('head'=>array(),'body'=>array(),'foot'=>array());
    //view加载的js临时存储数组（这些js在layout加载的js之后加载）
    private static $viewCssTempArr = array();
    //移除加载的js
    private static $viewJsUnregArr = array('head'=>array(),'body'=>array(),'foot'=>array());

    //css配制
    //所有的css存储数组
    private static $cssArr = array();
    //view加载的css临时存储数组（这些css在layout加载的css之后加载）
    private static $viewJsTempArr = array();
    //移除加载的css
    private static $viewCssUnregArr = array();
    
    /**
     * 注册js文件
     * @param js地址
     * @param string 加载位置  'head','body','foot'
     **/
    public static function regScript($jsDir, $position='head')
    {
        $position = strtolower($position);
        if ($jsDir != '' && isset(self::$jsArr[$position]) && !in_array($jsDir, self::$jsArr[$position])) {
            self::$jsArr[$position][] = $jsDir;
        }
    }

    /**
     * 移除注册js文件
     * @param js地址
     * @param string 加载位置  'head','body','foot'
     **/
    public static function unregScript($jsDir, $position='head')
    {
        $position = strtolower($position);
        if ($jsDir != '' && isset(self::$jsArr[$position]) && !in_array($jsDir, self::$viewJsUnregArr[$position])) {
            self::$viewJsUnregArr[$position][] = $jsDir;
        }
    }

    /**
     * 注册css文件
     * @param css地址
     **/
    public static function regCss($cssDir)
    {
        if ($cssDir != '' && !in_array($cssDir, self::$cssArr)) {
            self::$cssArr[] = $cssDir;
        }
    }

    /**
     * 移除注册css文件
     * @param css地址
     **/
    public static function unregCss($cssDir)
    {
        if (!in_array($cssDir, self::$viewCssUnregArr)) {
            self::$viewCssUnregArr[] = $cssDir;
        }
    }

    /**
     * 装载css
     *
     * @return string
     **/
    public static function viewCssLoad()
    {
        self::cssJsRecombination();
        $css = '';
        foreach (self::$cssArr as $val) {
            $css .= '    <link href="'.$val.'" type="text/css" rel="stylesheet" />'."\n";
        }
        return $css;
    }

    /**
     * 装载head script
     *
     * @return string
     **/
    public static function viewHeadScriptLoad()
    {
        self::cssJsRecombination();
        $script = '';
        foreach (self::$jsArr['head'] as $val) {
            $script .= '    <script type="text/javascript" src="'.$val.'"></script>'."\n";
        }
        return $script ? "\n".$script : '';
    }

    /**
     * 装载body script
     *
     * @return string
     **/
    public static function viewBodyScriptLoad()
    {
        self::cssJsRecombination();
        $script = "";
        foreach (self::$jsArr['body'] as $val) {
            $script .= '    <script type="text/javascript" src="'.$val.'"></script>'."\n";
        }
        return $script ? "\n".$script : '';
    }

    /**
     * 装载foot script
     *
     * @return string
     **/
    public static function viewFootScriptLoad()
    {
        self::cssJsRecombination();
        $script = "";
        foreach (self::$jsArr['foot'] as $val) {
            $script .= '    <script type="text/javascript" src="'.$val.'"></script>'."\n";
        }
        return $script ? "\n".$script : '';
    }


    /**
     * 静态文件加载项临时转移 在DyPhpView的render方法渲染时调用(view加载后，layout加载前)
     **/
    public static function cssJsMove()
    {
        self::$viewCssTempArr = self::$cssArr;
        self::$cssArr = array();

        self::$viewJsTempArr  = self::$jsArr;
        self::$jsArr  = array('head'=>array(),'body'=>array(),'foot'=>array());
    }

    /**
     * 重组静态文件加载项  对css和js做合并，去重，加载顺序处理，移除项处理
     **/
    private static function cssJsRecombination()
    {
        if (self::$recombinationLock) {
            return;
        }

        self::$cssArr = array_unique(array_merge(self::$cssArr, self::$viewCssTempArr));
        self::$cssArr = array_diff(self::$cssArr, self::$viewCssUnregArr);

        self::$jsArr  = array_merge_recursive(self::$jsArr, self::$viewJsTempArr);
        self::$jsArr['head']  = array_diff(array_unique(self::$jsArr['head']), self::$viewJsUnregArr['head']);
        self::$jsArr['body']  = array_diff(array_unique(self::$jsArr['body']), self::$viewJsUnregArr['body']);
        self::$jsArr['foot']  = array_diff(array_unique(self::$jsArr['foot']), self::$viewJsUnregArr['foot']);

        self::$recombinationLock = true;
    }
}
