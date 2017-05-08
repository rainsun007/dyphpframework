<?php
/**
 * debug工具类
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright 2011 dyphp.com 
 **/
class DyDebug extends DyPhpDebug{
    public static function show($showType='sql,param,file'){
        if(!DyPhpBase::$debug || empty($showType)){
            return;
        }

        $showTypeArr = array_unique(explode(',',$showType));
        foreach ($showTypeArr as $key=>$val) {
            if($val == 'sql'){
                PHP_SAPI == 'cli' ? self::getLoadQueryConsole() : self::getLoadQuery();
            }elseif($val == 'param'){
                PHP_SAPI == 'cli' ? self::getGlobalParamsConsole() : self::getGlobalParams();
            }elseif($val == 'file' || $val == 'allfile'){
                $onlyApp = $val == 'allfile' ? false : true;
                PHP_SAPI == 'cli' ? self::getLoadFilesConsole() : self::getLoadFiles($onlyApp);
            }
        }
    }
}
