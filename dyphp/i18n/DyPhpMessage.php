<?php
/**
 * 提示信息类
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright dyphp.com
 **/
class DyPhpMessage
{
    /**
     * 获取语言包
     * @param 现支持语言类型 zh_ch,en
     * @return array
     **/
    public static function getLanguagePackage($language)
    {
        $languageArr = array('zh_cn');
        $language = in_array($language, $languageArr) ? $language : 'zh_cn';
        return include DYPHP_PATH.'/dyphp/i18n/'.$language.'.php';
    }
}
