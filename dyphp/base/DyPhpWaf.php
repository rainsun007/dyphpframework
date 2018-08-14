<?php
/**
 * web防火墙，防护XSS,SQL,代码执行等攻击
 * 基于“云体检通用漏洞防护补丁v1.1”
 * 注意：技术社区类，cms类等可以提交富文本的网站，可能被误杀; 另外此功能会对所有的访问都进行检查对性能有所影响（可忽略不计）
 *
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright dyphp.com
 **/
class DyPhpWaf
{
    private $urlMatch=array(
        'url_xss'=>"\\=\\+\\/v(?:8|9|\\+|\\/)|\\%0acontent\\-(?:id|location|type|transfer\\-encoding)",
        'url_csrf'=>"</*(applet|link|style|script|iframe|frame|frameset|img|form)"
    );

    private $argsMatch=array(
        'args_xss'=>"[\\'\\\"\\;\\*\\<\\>].*\\bon[a-zA-Z]{3,15}[\\s\\r\\n\\v\\f]*\\=|\\b(?:expression)\\(|\\<script[\\s\\\\\\/]|\\<\\!\\[cdata\\[|\\b(?:eval|alert|prompt|msgbox)\\s*\\(|url\\((?:\\#|data|javascript)",
        'args_sql'=>"[^\\{\\s]{1}(\\s|\\b)+(?:select\\b|update\\b|insert(?:(\\/\\*.*?\\*\\/)|(\\s)|(\\+))+into\\b).+?(?:from\\b|set\\b)|[^\\{\\s]{1}(\\s|\\b)+(?:create|delete|drop|truncate|rename|desc)(?:(\\/\\*.*?\\*\\/)|(\\s)|(\\+))+(?:table\\b|from\\b|database\\b)|into(?:(\\/\\*.*?\\*\\/)|\\s|\\+)+(?:dump|out)file\\b|\\bsleep\\([\\s]*[\\d]+[\\s]*\\)|benchmark\\(([^\\,]*)\\,([^\\,]*)\\)|(?:declare|set|select)\\b.*@|union\\b.*(?:select|all)\\b|(?:select|update|insert|create|delete|drop|grant|truncate|rename|exec|desc|from|table|database|set|where)\\b.*(charset|ascii|bin|char|uncompress|concat|concat_ws|conv|export_set|hex|instr|left|load_file|locate|mid|sub|substring|oct|reverse|right|unhex)\\(|(?:master\\.\\.sysdatabases|msysaccessobjects|msysqueries|sysmodules|mysql\\.db|sys\\.database_name|information_schema\\.|sysobjects|sp_makewebtask|xp_cmdshell|sp_oamethod|sp_addextendedproc|sp_oacreate|xp_regread|sys\\.dbms_export_extension)",
        'args_behaviour'=>"(<[^>]+)style=([\`\'\"]*).*behaviour\([^>]*>",
        'args_vbscript'=>"([a-z]*)=([\'\"]*)vbscript:",
        'args_disable_functions'=>"<\?.*(php_|base64_|passthru|exec|system|chroot|scandir|chgrp|chown|shell_|proc_|ini_|dl|openlog|syslog|readlink|symlink|popepassthru|stream_socket_server|escapeshellcmd|dll|popen|disk_free_space|checkdnsrr|checkdnsrr|getservbyname|getservbyport|disk_total_space|posix_)",
        'args_other'=>"\\.\\.[\\\\\\/].*\\%00([^0-9a-fA-F]|$)|%00[\\'\\\"\\.]",
    );

    public function __construct()
    {
        $referer=empty($_SERVER['HTTP_REFERER']) ? array() : array($_SERVER['HTTP_REFERER']);
        $queryString=empty($_SERVER["QUERY_STRING"]) ? array() : array($_SERVER["QUERY_STRING"]);
        $this->check($queryString, $this->urlMatch);
        $this->check($_GET, $this->argsMatch);
        $this->check($_POST, $this->argsMatch);
        $this->check($_COOKIE, $this->argsMatch);
        $this->check($referer, $this->argsMatch);
    }

    protected function check($subject, $v)
    {
        if (empty($subject)) {
            return ;
        }
        foreach ($subject as $key => $value) {
            if (!is_numeric($key)) {
                !is_array($key) ? $this->checkMatch($key, $v) : $this->check($key, $v);
            }

            !is_array($value) && !empty($value) ? $this->checkMatch($value, $v) : $this->check($value, $v);
        }
    }
    
    protected function checkMatch($str, $v)
    {
        foreach ($v as $key => $value) {
            if (preg_match("#".$value."#isu", $str) == 1 || preg_match("#".$value."#isu", urlencode($str)) == 1) {
                $this->logs('hit:['.$key.'] '.$str);

                $errorHandlerArr = explode('/', trim(DyPhpConfig::item('errorHandler'), '/'));
                $exceptionMessage = array('dyExcType' => 'Aborted', 'errType' => 'ERROR', 'msg' => 'Suspend the access');
                DyPhpController::run($errorHandlerArr[0], $errorHandlerArr[1], $exceptionMessage);
                exit();
            }
        }
    }

    /**
     * 命中log记录
     *
     * @param string     错误信息
     **/
    private function logs($message)
    {
        $logDir = rtrim(DyPhpConfig::item('appPath'), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR;
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        //按app类型写log信息
        $data = date('Y-m-d H:i:s', time()).' '.DyRequest::getClientIp().' '.DyRequest::getMethod().' '.$_SERVER['REQUEST_URI'].' '.$message.PHP_EOL;
        $fp = fopen($logDir.'waf.log', 'a');
        if ($fp) {
            fwrite($fp, $data);
            fclose($fp);
        }
    }
}
