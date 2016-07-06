<?php 
/*
 * 服务器配置，详情请参考@link http://mp.weixin.qq.com/wiki/index.php?title=接入指南
 */
//define("WECHAT_URL", '');
//define('WECHAT_TOKEN', '');
//define('ENCODING_AES_KEY', "");

/*
 * 开发者配置
 */
//define("WECHAT_APPID", '');
//define("WECHAT_APPSECRET", '');

class LaneWeChatAutoloader{
    const NAMESPACE_PREFIX = 'LaneWeChat\\';
    /**
     * 向PHP注册在自动载入函数
     */
    public static function register(){
        spl_autoload_register(array(new self, 'autoload'));
    }

    /**
     * 根据类名载入所在文件
     */
    public static function autoload($className){
        $namespacePrefixStrlen = strlen(self::NAMESPACE_PREFIX);
        if(strncmp(self::NAMESPACE_PREFIX, $className, $namespacePrefixStrlen) === 0){
            $className = strtolower($className);
            $filePath = str_replace('\\', DIRECTORY_SEPARATOR, substr($className, $namespacePrefixStrlen));
            $filePath = __DIR__.DIRECTORY_SEPARATOR.$filePath.'.lib.php';
            //$filePath = __DIR__.DIRECTORY_SEPARATOR.'core'.DIRECTORY_SEPARATOR.$filePath.'.lib.php';
            require $filePath;
        }
    }
}

Dy::autoloadRegister(array('LaneWeChatAutoloader','autoload'));


