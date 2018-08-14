<?php
/**
 * 异常类.
 *
 * @author 大宇 Email:dyphp.com@gmail.com
 *
 * @link http://www.dyphp.com/
 *
 * @copyright Copyright dyphp.com
 **/
class DyException extends DyPhpException
{
}

class DyPhpException extends Exception
{
    //错误类型，用于错误类型的自己定义归类
    private static $eReport = '';

    //错误句柄调用标志（锁） 防止多次调用句柄进入死循环
    private static $errorHandlerInvoked = false;

    //此值为true时框架将会把异常向外层抛出,应用中必须自己catch处理器,不建议使用此方法
    private static $appCatch = false;

    //前一个异常
    private static $dyPrevious = null;

    // 重定义构造器使 message 变为必须被指定的属性
    public function __construct($message, $code = 0, $previous = null)
    {
        self::$dyPrevious = $previous;
        parent::__construct($message, $code, $previous);
    }

    /**
     * dyphp提示信息.
     **/
    public function appTrace()
    {
        self::$eReport = E_USER_ERROR;
        self::centralizeHandler('DyException', $this->message, $this->getTraceAsString());
    }

    /**
     * 错误处理器.
     *
     * @param int    出错编号
     * @param string 出错信息
     * @param string 出错文件
     * @param int    出错行号
     **/
    public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        self::$eReport = $errno;
        self::centralizeHandler('Error', $errstr, $errfile.'('.$errline.')');
    }

    /**
     * 错误处理器.
     *
     * @param   该参数是一个抛出的异常对象
     **/
    public static function exceptionHandler($exception)
    {
        self::$eReport = E_ERROR;
        self::centralizeHandler('Exception', $exception->getMessage(), $exception->getFile().'('.$exception->getLine().")\n".$exception->getTraceAsString());
    }

    /**
     * shutdown处理器.
     **/
    public static function shutdownHandler()
    {
        if ($error = error_get_last()) {
            self::$eReport = $error['type'];
            self::centralizeHandler('Shutdown', $error['message'], $error['file'].'('.$error['line'].')');
        }
    }

    /**
     * 设置是否把异常向外层抛出
     * 此方法可在运行中动态开启和关闭，若开启在处理逻辑结束后关闭
     *
     * @param  bool 设置为true框架将会把异常向外层抛出,应用中可自行catch,如未处理异常最终还会被框架接管
     **/
    public static function setAppCatch($isCatch = false)
    {
        self::$appCatch = $isCatch;
    }

    /**
     * 获取异常向外层抛出设置
     *
     * @return bool
     **/
    public static function getAppCatch()
    {
        return self::$appCatch;
    }

    /**
     * 错误处理器.
     *
     * @param string 错误信息title
     * @param string 错误信息说明
     * @param string 错误运行跟踪
     **/
    private static function centralizeHandler($title, $message, $traceString)
    {
        //log记录所有异常报告
        self::logs($title, $message, $traceString);

        //设置了异常向外层抛出，将异常抛出给app
        if (self::$appCatch) {
            throw new Exception($message, 0, self::$dyPrevious);
        }

        //进入此逻辑如设置满足error_reporting(0) && DyPhpBase::$debug == false时，页面空白（不会调用errorHandler）
        if (!(error_reporting() & self::$eReport) && DyPhpBase::$debug == false) {
            return;
        }

        //获取归类后的错误类型
        $errType = self::getErrType();

        //清除所有输出
        if (ob_get_length()) {
            ob_end_clean();
        }

        //debug关闭时将报错信息转到自定义错误处理句柄
        if (!DyPhpBase::$debug) {
            if (self::$errorHandlerInvoked) {
                return;
            }
            self::$errorHandlerInvoked = true;

            Dy::app()->setPreInsAttr();

            $errorHandlerArr = explode('/', trim(DyPhpConfig::item('errorHandler'), '/'));
            $exceptionMessage = array('dyExcType' => $title, 'errType' => $errType, 'msg' => $message);
            DyPhpController::run($errorHandlerArr[0], $errorHandlerArr[1], $exceptionMessage);
            exit();
        }

        //按app类型输出
        if (DyPhpBase::$appType == 'web') {
            if (!headers_sent()) {
                header('Content-Type:text/html;charset=utf-8');
            }
            echo '<div style="font-weight:bold;font-size:14px;border:1px solid #ccc;background:#333;padding:10px 15px;">';
            echo '<div style="color:#BDB76B;margin:10px 0;">'.$title.'['.$errType.']</div>';
            echo '<div style="color:#CD5C5C;word-break:break-all;word-wrap:break-word;width:100%">'.$message.'</div>';
            echo '<div style="color:#FFF;word-break:break-all;word-wrap:break-word;margin:0px 0;"><pre>'.$traceString.'</pre></div>';
            echo '</div>';
        } else {
            echo $title.'['.$errType.']'.PHP_EOL.$message.PHP_EOL.$traceString.PHP_EOL.PHP_EOL;
        }
    }

    /**
     * 错误log记录, 每月一个独立log.
     *
    * @param string 错误信息title
    * @param string 错误信息说明
    * @param string 错误运行跟踪
     **/
    private static function logs($title, $message, $traceString)
    {
        $logDir = rtrim(DyPhpConfig::item('appPath'), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR;
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        $file = $logDir.'error_'.date('Ym', time()).'.log';

        //按app类型写log信息
        $request = DyPhpBase::$appType == 'web' ? $_SERVER['REQUEST_URI'] : 'controller:'.DyPhpBase::app()->cid.' action:'.DyPhpBase::app()->aid;
        $source = DyPhpBase::$appType == 'web' ? DyRequest::getClientIp().' '.DyRequest::getMethod() : php_uname('n');
        $data = date('Y-m-d H:i:s', time()).' '.$title.'['.self::getErrType().'] '.$source.' '.$request.PHP_EOL.$message.PHP_EOL.$traceString.PHP_EOL.PHP_EOL;
        $fp = fopen($file, 'a');
        if ($fp) {
            fwrite($fp, $data);
            fclose($fp);
        }
    }

    /**
     * 获取错误类型.
     *
     * @return string 错误类型
     **/
    private static function getErrType()
    {
        switch (self::$eReport) {
          case E_PARSE:
          case E_CORE_ERROR:
          case E_COMPILE_ERROR:
          case E_RECOVERABLE_ERROR:
          case E_ERROR:
          case E_USER_ERROR:
              $errType = 'ERROR';
              break;
          case E_CORE_WARNING:
          case E_COMPILE_WARNING:
          case E_WARNING:
          case E_USER_WARNING:
          case E_DEPRECATED:
          case E_USER_DEPRECATED:
              $errType = 'WARNING';
              break;
          case E_NOTICE:
          case E_USER_NOTICE:
              $errType = 'NOTICE';
              break;
          case E_STRICT:
              $errType = 'STRICT';
              break;
          default:
              $errType = 'Unknown';
              break;
        }

        return $errType;
    }
}
