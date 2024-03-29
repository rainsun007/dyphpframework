<?php

/**
 * 路由类.
 *
 * @author 大宇 Email:dyphp.com@gmail.com
 *
 * @link http://www.dyphp.com/
 *
 * @copyright Copyright dyphp.com
 **/
class DyPhpRoute
{
    //url重写后正则匹配到的Get参数数组及扩展名（键值ext_name）,过滤后传递给controller的caParam属性
    private static $regularGetParams = array();

    /**
     * web路由入口
     * 
     * 优先处理urlManager配制
     * 
     * $_GET保留key: ca(controller和acton格式，如：user.profile或admin.user.profile), 此参数不可被占用
     **/
    public static function runWeb()
    {
        $matchArr = self::urlManager();

        //路由规则命中判断
        if ($matchArr) {
            if (!isset($matchArr['controller'])) {
                DyPhpBase::throwException('urlManager error');
            }
            $action = isset($matchArr['action']) ? $matchArr['action'] : '';
            self::runToController(array('c' => $matchArr['controller'], 'a' => $action));
            return true;
        }

        //URL参数中显性使用ca参数请求判断，未设置时调用默认controller和action
        if (!isset($_GET['ca'])) {
            self::runToController();
            return true;
        }

        //controller,action格式合法性判断
        $ca = $_GET['ca'];
        if (preg_match('|([.]){2,}|', $ca) || !preg_match('#^[a-zA-Z0-9.]{1,}[a-zA-Z0-9]{1,}$#', $ca)) {
            DyPhpBase::throwException('ca format error');
        }

        //支持多级路由处理(controller.action; module.controller.action; module.controller.action;)
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
            $message .= 'DyFramework Console The default execution : ' . ucfirst(DYPHP_DEFAULT_CONTROLLER) . 'Controller->action' . ucfirst(DYPHP_DEFAULT_ACTION) . "\n";
            $message .= "Invocation Method : [controller] [<action>] [<param1> <param2> ...<paramN>] \n";
            echo $message;
        }

        $controller = isset($_SERVER['argv'][0]) ? $_SERVER['argv'][0] : DYPHP_DEFAULT_CONTROLLER;
        $action = DYPHP_DEFAULT_ACTION;
        $params = array();
        if (isset($_SERVER['argv'][1])) {
            $action = $_SERVER['argv'][1];
            unset($_SERVER['argv'][0], $_SERVER['argv'][1]);
            array_values($_SERVER['argv']);
            foreach ($_SERVER['argv'] as $key => $val) {
                $params[] = $val;
            }
        }
        DyPhpController::run($controller, $action, $params);
    }

    /**
     * @brief  获取uri中正则匹配到的参数,进行字符串转换
     *         转入到DyPhpController的$caParam属性
     *
     * @return array
     **/
    private static function getRegParam()
    {
        if (!self::$regularGetParams) {
            return array();
        }

        foreach (self::$regularGetParams as $key => $value) {
            self::$regularGetParams[$key] = addslashes($value);
        }

        return self::$regularGetParams;
    }

    /**
     * url解析及运行controller和action.
     *
     * @param array $ca url设置了ca参数或urlManager规则命中，分析所得的ctroller和action
     *
     **/
    private static function runToController($ca = array())
    {
        //$regularGetParams属性中ext_name参数设置在urlCrop()中添加，所以需要在DyPhpController::run之前调用
        $controllerArgs = self::urlCrop();

        if (!empty($ca)) {
            DyPhpController::run($ca['c'], $ca['a'], self::getRegParam());
            return;
        }

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
                $controller = $controllerArgsArr[0] . '/' . $controllerArgsArr[1];
                $action = $controllerArgsArr[2];
            }
        }
        DyPhpController::run($controller, $action);
    }

    /**
     * URL路由规则管理
     *
     * @example 
     * 'urlManager'=>array(
     *   //'hideIndex'=>'yes'为不需要显示调用index.php; 'restCa'=>'yes'为参数使用restfull风格;
     *   'urlStyle'=>array('hideIndex'=>'yes','restCa'=>'yes'),
     *
     *   //重定向到固定controller,固定action
     *   '/error'=>array("controller"=>"home","action"=>"error"),
     *      
     *   //按组匹配重定向到固定controller,固定action, 不支持正则匹配
     *   'groupForward' => array(
     *       array(
     *           'group' => array(
     *               'admin/aaa/a',
     *               'bbb/b',
     *           ),
     *           'forward' => array('controller' => 'admin/home', 'action' => 'index'),
     *       )
     *    ),
     *
     *   //重定向到固定controller,动态action
     *   '/admin/global/:action'=>array(
     *       "controller"=>"admin_base",  //支持指定到module下的controller,使用下"_"或"/"指明
     *       "param"=>array(
     *           ":action"=>"[a-zA-Z0-9]{1,10}",
     *       ),
     *   ),
     * 
     *   //将动态action重定向到固定controller,固定action
     *   '/admin/global/:action'=>array(
     *       "controller"=>"admin_base",  //支持指定到module下的controller,使用下"_"或"/"指明
     *       "action"=>"index",           //已设置固定action时,使用固定action,正则匹配生效但不会被使用
     *       "param"=>array(
     *           ":action"=>"[a-zA-Z0-9]{1,10}",
     *       ),
     *   ),
     *
     *   //重定向到动态controller,动态action
     *   '/user/:controller/:action'=>array(
     *       "param"=>array(
     *           ":controller"=>"[a-zA-Z0-9]{1,20}",
     *           ":action"=>"[a-zA-Z0-9]{1,10}",
     *       ),
     *   ),
     *
     *   //解析特定url参数,并重定向到固定controller,固定action
     *   '/ping/aaa/:user/ccc/ddd/:id'=>array(
     *       "controller"=>"test",
     *       "action"=>"index",
     *       "param"=>array(
     *           ":user"=>"[a-zA-Z0-9]{4,10}",
     *           ":id"=>"\d{1,3}",
     *       ),
     *   ),
     *
     *   //为指定module下的指定controller设置默认的action
     *   '/blog/:controller'=>array(
     *       "pathController"=>true, //此参数设置为true,否则不生效
     *       "action"=>"index",
     *       "param"=>array(":controller"=>"[a-zA-Z0-9]{1,20}",),
     *   ),
     * )
     * 
     * @return array
     **/
    private static function urlManager()
    {
        $urlManager = DyPhpConfig::item('urlManager');

        //去除url风格设置参数，该参数不参与正则匹配处理
        if (isset($urlManager['urlStyle'])) {
            unset($urlManager['urlStyle']);
        }

        //未设置规则不进行匹配
        if (!is_array($urlManager) || count($urlManager) == 0) {
            return array();
        }

        //获取URL访问路径
        $cropPathUrl = self::urlCrop();

        //URL访问路径为空（只访问了主域名）不进度匹配
        if ($cropPathUrl === '') {
            return array();
        }

        //完全匹配URL访问路径判断
        $hitFull = self::hitFullPath($cropPathUrl, $urlManager);
        if ($hitFull) {
            return $hitFull;
        }

        //正则匹配规则判断
        $uriStrArr = explode('/', $cropPathUrl);
        foreach ($urlManager as $urlKey => $urlVal) {
            //未设置param项不进行正则匹配
            if (!isset($urlVal['param']) || !is_array($urlVal['param'])) {
                continue;
            }

            $pmatch = str_replace('/', '\/', strtr(trim($urlKey, '/'), $urlVal['param']));
            if (preg_match('#^' . $pmatch . '$#i', $cropPathUrl)) {
                //pathController为true并设置controller需正则匹配,则controller匹配值及其之前的所有项都将做为controller
                $isPathCtr = isset($urlVal['pathController']) && $urlVal['pathController'] == true ? true : false;

                $urlKeyArr = explode('/', trim($urlKey, '/'));
                foreach ($urlVal['param'] as $key => $val) {
                    $pkey = array_search($key, $urlKeyArr);
                    if ($pkey !== false && isset($uriStrArr[$pkey])) {
                        if ($key == ':controller') {
                            //命中的controller会依据pathController设置进行处理
                            $urlVal['controller'] = $isPathCtr ? implode('_', array_slice($uriStrArr, 0, $pkey)) . '_' . $uriStrArr[$pkey] : $uriStrArr[$pkey];
                        } elseif ($key == ':action') {
                            //命中的action处理,已设置固定action时，使用固定action
                            $urlVal['action'] = isset($urlVal['action']) && !empty($urlVal['action']) ? $urlVal['action'] : $uriStrArr[$pkey];
                        } else {
                            //命中的其它匹配项做为
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
     * 获取扩展名，同时将扩展名从ca中去掉，此方法使uri支持自由的自定义扩展名（如：可用于实现伪静态或伪装）.
     *
     * @return string
     **/
    private static function urlCrop()
    {
        $pathStr = '';
        $parse = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if ($parse !== false && $parse !== null) {
            $pathStr = trim(str_replace(array(DyPhpConfig::item('appHttpPath'), 'index' . EXT, '//'), '', $parse), '/');
        } else {
            $requestUriStr = str_replace('index' . EXT, '', trim($_SERVER['REQUEST_URI'], '/'));
            $search = array(DyPhpConfig::item('appHttpPath'));
            isset($_SERVER['QUERY_STRING']) ? array_push($search, $_SERVER['QUERY_STRING']) : '';
            $uriPath = str_replace($search, '', $requestUriStr);
            $pathStr =  trim(trim($uriPath, '/'), '?');
        }

        $ext = pathinfo($pathStr, PATHINFO_EXTENSION);
        self::$regularGetParams['ext_name'] = $ext;

        return $ext ? substr($pathStr, 0, - (strlen($ext) + 1)) : $pathStr;
    }

    /**
     * 完全匹配URL访问路径判断，多个命中时返回第一个规则
     * 
     * @param sring $cropPathUrl
     * @param array $urlManager
     * @return void
     */
    private static function hitFullPath($cropPathUrl = '', &$urlManager = array())
    {

        $groupForward = array();
        if (isset($urlManager['groupForward'])) {
            $groupForward = $urlManager['groupForward'];

            //去除groupForward设置参数，该参数不参与正则匹配处理
            unset($urlManager['groupForward']);
        }

        //兼容配制中路径格式，支持"/path"、"path/"、"/path/"
        $pathUrlArr = array($cropPathUrl, '/' . $cropPathUrl, '/' . $cropPathUrl . '/');

        //优先匹配groupForward外部规则
        $hitFull = array_values(array_intersect(array_keys($urlManager), $pathUrlArr));
        if ($hitFull) {
            return $urlManager[$hitFull[0]];
        }

        //匹配groupForward内部规则
        foreach ($groupForward as $key => $value) {
            $hitFull = array_values(array_intersect($value['group'], $pathUrlArr));
            if ($hitFull) {
                return $value['forward'];
            }
        }

        return array();
    }
}
