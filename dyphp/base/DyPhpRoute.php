<?php
/**
 * 路由类.
 *
 * @author 大宇 Email:dyphp.com@gmail.com
 *
 * @link http://www.dyphp.com/
 *
 * @copyright Copyright 2011 dyphp.com
 **/
class DyPhpRoute
{
    //url重写后正则匹配到的Get参数
    private static $regularGetParams = array();

    /**
     * web路由入口
     * 优先处理urlManager配制
     * $_GET保留key : ca(controller和acton格式 如user.profile或admin.user.profile),ext_name(访问的后缀名 如php),page(用于分页widget)
     * 支持get请求以ca=module.controller.action的方式访问.
     **/
    public static function runWeb()
    {
        $matchArr = self::urlManager();
        if ($matchArr) {
            if (!isset($matchArr['controller'])) {
                DyPhpBase::throwException('urlManager error');
            }
            $action = isset($matchArr['action']) ? $matchArr['action'] : '';
            self::runToController(array('c' => $matchArr['controller'], 'a' => $action));
        } else {
            if (!isset($_GET['ca'])) {
                self::runToController();
                return;
            }

            $ca = $_GET['ca'];
            if (preg_match('|([.]){2,}|', $ca) || !preg_match('#^[a-zA-Z0-9.]{1,}[a-zA-Z0-9]{1,}$#', $ca)) {
                DyPhpBase::throwException('ca format error');
            }

            $caArr = explode('.', $ca);
            if (count($caArr) > 2) {
                $action = end($caArr);
                array_pop($caArr);
                $controller = implode('_', $caArr);
            } else {
                $controller = isset($caArr[0]) ? $caArr[0] : DYPHP_DEFAULT_CONTROLLER;
                $action = isset($caArr[1]) ? $caArr[1] : '';
            }
            self::runToController(array('c' => $controller, 'a' => $action));
        }
    }

    /**
     * @brief    console路由入口
     *
     * @return
     **/
    public static function runConsole()
    {
        array_shift($_SERVER['argv']);
        if (empty($_SERVER['argv'])) {
            $message = "Welcome to use \n";
            $message .= 'DyFramework Console The default execution : '.ucfirst(DYPHP_DEFAULT_CONTROLLER).'Controller->action'.ucfirst(DYPHP_DEFAULT_ACTION)."\n";
            $message .= "Invocation Method : <controller> [<action>] [<param1> <param2> ...<paramN>] \n";
            echo $message;
        }

        $controller = isset($_SERVER['argv'][0]) ? $_SERVER['argv'][0] : DYPHP_DEFAULT_CONTROLLER;
        $action = DYPHP_DEFAULT_ACTION;
        $params = array();
        if (isset($_SERVER['argv'][1])) {
            $action = $_SERVER['argv'][1];
            unset($_SERVER['argv'][0], $_SERVER['argv'][1]);
            sort($_SERVER['argv']);
            foreach ($_SERVER['argv'] as $key => $val) {
                $params[] = $val;
            }
        }
        DyPhpController::run($controller, $action, $params);
    }

    /**
     * @brief    获取uri中正则匹配到的参数
     *
     * @param  string  $paramKey
     *
     * @return string
     **/
    public static function getParam($paramKey = '')
    {
        if (!isset(self::$regularGetParams[$paramKey])) {
            return null;
        }

        $value = self::$regularGetParams[$paramKey];
        if (!get_magic_quotes_gpc()) {
            $value = function_exists('addslashes') ? addslashes($value) : mysql_real_escape_string($value);
        }

        return $value;
    }

    /**
     * url解析及运行controller和action.
     * 
     * @param array $ca 
     **/
    private static function runToController($ca = array())
    {
        if (!empty($ca)) {
            DyPhpController::run($ca['c'], $ca['a']);
            return;
        }

        $controllerArgs = self::urlCrop();
        if ($controllerArgs == '' || $controllerArgs == false) {
            DyPhpController::run(DYPHP_DEFAULT_CONTROLLER);
            return;
        }

        $controllerArgsArr = explode('/', $controllerArgs);
        $pathCutCount = count($controllerArgsArr);
        if ($pathCutCount <= 2) {
            if ($pathCutCount == 1) {
                DyPhpController::run($controllerArgsArr[0]);
                return;
            }
            $controller = $controllerArgsArr[0];
            $action = $controllerArgsArr[1];
        } else {
            //非模块路由处理
            if (count($controllerArgsArr) % 2 == 0) {
                foreach ($controllerArgsArr as $key => $val) {
                    if ($key > 1 && $key % 2 == 0 && $val != '') {
                        $_GET[$val] = isset($controllerArgsArr[($key + 1)]) ? $controllerArgsArr[($key + 1)] : '';
                    }
                }
                $controller = $controllerArgsArr[0];
                $action = $controllerArgsArr[1];
            } else {
                //模块路由处理
                foreach ($controllerArgsArr as $key => $val) {
                    if ($key > 2 && $key % 2 == 1 && $val != '') {
                        $_GET[$val] = isset($controllerArgsArr[($key + 1)]) ? $controllerArgsArr[($key + 1)] : '';
                    }
                }
                $controller = $controllerArgsArr[0].'/'.$controllerArgsArr[1];
                $action = $controllerArgsArr[2];
            }
        }
        DyPhpController::run($controller, $action);
    }

    /**
     * URL重写处理
     * 'urlManager'=>array(
     *   'urlStyle'=>array('hideIndex'=>'yes','restCa'=>'yes',),
     *
     *   '/error'=>array("controller"=>"home","action"=>"error",),
     *
     *   '/admin/globalBase/:action'=>array(
     *       "controller"=>"admin_base",
     *       "param"=>array(
     *           ":action"=>"[a-zA-Z0-9]{1,10}",
     *       ),
     *   ),
     *
     *   '/user/:class/:controller/:action'=>array(
     *       "param"=>array(
     *           ":controller"=>"[a-zA-Z0-9]{1,20}",
     *           ":action"=>"[a-zA-Z0-9]{1,10}",
     *           ":class"=>"[a-zA-Z0-9]{1,10}",
     *       ),
     *   ),
     *
     *   '/ping/aaa/:user/ccc/ddd/:id'=>array(
     *       "controller"=>"test",
     *       "action"=>"index",
     *       "param"=>array(
     *           ":user"=>"[a-zA-Z0-9]{4,10}",
     *           ":id"=>"\d{1,3}",
     *       ),
     *   ),
     * )
     **/
    private static function urlManager()
    {
        $urlManager = DyPhpConfig::item('urlManager');

        //去除url风格设置参数，该参数不参与匹配处理
        if (isset($urlManager['urlStyle'])) {
            unset($urlManager['urlStyle']);
        }

        if (!is_array($urlManager) || count($urlManager) == 0) {
            return array();
        }

        $cropPathUrl = self::urlCrop();
        //完全匹处理配
        if($cropPathUrl){
            $pathUrlArr = array($cropPathUrl, '/'.$cropPathUrl, '/'.$cropPathUrl.'/');
            foreach ($pathUrlArr as $key => $val) {
                if (isset($urlManager[$val])) {
                    return $urlManager[$val];
                }
            }
        }

        //正则处理
        $uriStrArr = explode('/', $cropPathUrl);
        foreach ($urlManager as $urlKey => $urlVal) {
            if (!isset($urlVal['param']) || !is_array($urlVal['param'])) {
                continue; //未设置param项 不做正则处理
            }
            $pmatch = str_replace('/', '\/', strtr(trim($urlKey, '/'), $urlVal['param']));
            if (preg_match('#^'.$pmatch.'$#i', $cropPathUrl)) {
                //pathController为true并设置controller需正则匹配 controller匹配值及其之前的所有项都将做为controller
                $isPathCtr = isset($urlVal['pathController']) && $urlVal['pathController'] == true ? true : false;
                $urlKeyArr = explode('/', trim($urlKey, '/'));
                foreach ($urlVal['param'] as $key => $val) {
                    $pkey = array_search($key, $urlKeyArr);
                    if ($pkey !== false && isset($uriStrArr[$pkey])) {
                        //controller,action需正则匹配 会对配制的controller,action重写或设置
                        if ($key == ':controller') {
                            $urlVal['controller'] = $isPathCtr ? implode('_', array_slice($uriStrArr, 0, $pkey)).'_'.$uriStrArr[$pkey] : $uriStrArr[$pkey];
                        } elseif ($key == ':action') {
                            $urlVal['action'] = $uriStrArr[$pkey];
                        } else {
                            self::$regularGetParams[substr($key, 1)] = $uriStrArr[$pkey];
                        }
                    }
                }

                return $urlVal;
            }
        }

        return array();
    }

    /**
     * url解析 获取cotroller，action及rest风格的get参数; 
     * 获取扩展名，同时将扩展名从ca中去掉，此方法使uri支持自定义扩展（如可用于实现伪静态）.
     *
     * @return string
     **/
    private static function urlCrop()
    {
        $pathStr = '';
        $parse = parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
        if($parse !== false && $parse !== NULL){
            $pathStr = trim(str_replace(array(DyPhpConfig::item('appHttpPath'),'index'.EXT,'//'), '', $parse), '/');
        }else{
            $requestUriStr = str_replace('index'.EXT, '', trim($_SERVER['REQUEST_URI'], '/'));
            $search = array(DyPhpConfig::item('appHttpPath'));
            isset($_SERVER['QUERY_STRING']) ? array_push($search,$_SERVER['QUERY_STRING']) : '';
            $uriPath = str_replace($search, '', $requestUriStr);
            $pathStr =  trim(trim($uriPath, '/'), '?');
        }

        $ext = pathinfo($pathStr,PATHINFO_EXTENSION);
        $_GET['ext_name'] = $ext;
        return $ext ? substr($pathStr, 0, -(strlen($ext)+1)) : $pathStr;
    }
}
