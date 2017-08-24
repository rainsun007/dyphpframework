<?php
/**
 * 静态文件管理器
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright 2011 dyphp.com 
 **/
class DyStatic{
    private static $jsArr = array();
    private static $cssArr = array();
    private static $viewCssTempArr = array();
    private static $viewJsTempArr = array();
    private static $isRecombination = false;

    /**
     * 注册js文件
     * @param js地址
     * @param string 加载位置  'head','body','foot'
     **/
    public static function regScript($jsDir,$position='head'){
        $position = strtolower($position);
        $allow = array('head','body','foot');
        if($jsDir != '' && in_array($position,$allow)){
            if(!isset(self::$jsArr[$position])){
                self::$jsArr[$position] = array();
            }
            if(!in_array($jsDir,self::$jsArr[$position])){
                self::$jsArr[$position][] = $jsDir;
            }
        }
    }

    /**
     * 注册css文件
     * @param css地址
     **/
    public static function regCss($cssDir){
        if($cssDir != '' && !in_array($cssDir,self::$cssArr)){
            self::$cssArr[] = $cssDir;
        }
    }

    /**
     * 装载css 
     **/
    public static function viewCssLoad(){
        self::cssJsRecombination();
        if(count(self::$cssArr)>0){
            $css = '';
            foreach(self::$cssArr as $val){
                $css .= '    <link href="'.$val.'" type="text/css" rel="stylesheet" />'."\n";
            }
            return $css;
        }
    }

    /**
     * 装载head script
     **/
    public static function viewHeadScriptLoad(){
        self::cssJsRecombination();
        if(isset(self::$jsArr['head'])){
            $script = '';
            foreach(self::$jsArr['head'] as $val){
                $script .= '    <script type="text/javascript" src="'.$val.'"></script>'."\n";
            }
            return $script;
        }
    }

    /**
     * 装载body script
     **/
    public static function viewBodyScriptLoad(){
        self::cssJsRecombination();
        if(isset(self::$jsArr['body'])){
            $script = "\n";
            foreach(self::$jsArr['body'] as $val){
                $script .= '    <script type="text/javascript" src="'.$val.'"></script>'."\n";
            }
            return $script;
        }
    }

    /**
     * 装载foot script
     **/
    public static function viewFootScriptLoad(){
        self::cssJsRecombination();
        if(isset(self::$jsArr['foot'])){
            $script = "\n";
            foreach(self::$jsArr['foot'] as $val){
                $script .= '    <script type="text/javascript" src="'.$val.'"></script>'."\n";
            }
            return $script;
        }
    }


    /**
     * @brief    静态文件加载项临时转移
     * @return   
     **/
    public static function cssJsMove(){
        if(!empty(self::$cssArr)){
            self::$viewCssTempArr = self::$cssArr;
            self::$cssArr = array();
        }

        if(!empty(self::$jsArr)){
            self::$viewJsTempArr  = self::$jsArr;
            self::$jsArr  = array();
        }
    }

    /**
     * @brief    重组静态文件加载项
     * @return   
     **/
    private static function cssJsRecombination(){
        if(self::$isRecombination){
            return;
        }
        self::$cssArr = array_merge (self::$cssArr,self::$viewCssTempArr);
        self::$jsArr  = array_merge_recursive(self::$jsArr,self::$viewJsTempArr);
        self::$isRecombination = true;
    }
}
