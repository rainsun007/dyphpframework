<?php
/**
 * view类
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright 2011 dyphp.com 
 **/
class DyPhpView{
    private static $aId = '';
    private static $cId = '';

    //layout file
    private $layoutFile = '';
    //view file
    private $viewFile = '';
    //模板数据
    private $viewData = array();

    //主题 使用该参数后DyPhpUserIdentity中的defaultTheme设置将无效
    public $defaultTheme = 'default';
    //默认使用的layout文件
    public $defaultLayout = 'main';
    //自定义页面title
    public $pageTitle = '';

    /** 
     * 完整view调用
     * @param string 调用的view
     * @param mixed  view需要的数据
     **/
    public function render($view,$data=array()){
        $this->attrSet($view,$data);

        if(!file_exists($this->viewFile)){
            DyPhpBase::throwException('view does not exist', $view.':'.$this->viewFile);
        }

        if(is_array($this->viewData)){
            extract($this->viewData);
        }
        
        //debug时对于非view内输出可见
        /*if(!DyPhpBase::$debug && ob_get_length()){*/
            //ob_clean();
        /*}*/

        ob_start();
        include $this->viewFile;
        $content = ob_get_contents();
        ob_end_clean();
        DyStatic::cssJsMove();

        ob_start(array($this,"formatLayout"));
        include $this->layoutFile;
        ob_end_flush();
        exit;
    }

    /** 
     * 局部view调用
     * @param string 调用的view
     * @param mixed  view需要的数据
     **/
    public function renderPartial($view,$data=array()){
        $this->attrSet($view,$data);

        if(!file_exists($this->viewFile)){
            DyPhpBase::throwException('view does not exist',$view);
        }

        if(is_array($this->viewData)){ 
            extract($this->viewData);
        }
        require $this->viewFile;
    }

    /**
     * 属性设置 
     * @param string 调用的view
     **/
    private function attrSet($view='',$data=array()){
        if(empty(self::$aId) || empty(self::$cId)){
            self::$aId = ucfirst(DyPhpBase::app()->aid);
            self::$cId = ucfirst(DyPhpBase::app()->cid);
        }
        
        $view = trim($view,'/');
        if(strpos($view,'/') === false){
            $view = self::$cId.DIRECTORY_SEPARATOR.$view;
        }

        $viewRoot = DyPhpConfig::item('appPath').DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.$this->defaultTheme.DIRECTORY_SEPARATOR;
        $this->viewFile = $viewRoot.$view.EXT;
        $this->layoutFile = $viewRoot.'Layout'.DIRECTORY_SEPARATOR.$this->defaultLayout.EXT;
        $this->viewData = array_merge($this->viewData,$data);
    }

    /**
     * 格式化layout
     * @param string  缓冲输出文件内容
     * @param bool    是否将content输出替换为include
     * @return string 返回处理后结果
     **/
    public function formatLayout(&$buffer=''){
        if(empty($buffer)){
            return '';
        }


        $headCssScript = DyStatic::viewCssLoad().DyStatic::viewHeadScriptLoad(); 
        if($headCssScript != ''){
            $buffer = str_replace('</head>',$headCssScript.'</head>',$buffer);
        }

        $bodyScript = DyStatic::viewBodyScriptLoad();
        if($bodyScript != ''){
            if (preg_match('/<body.*?>/i', $buffer, $regs)) {
                $buffer = str_replace($regs[0],$regs[0].$bodyScript,$buffer);
            }         
        }

        $footScript = DyStatic::viewFootScriptLoad();
        if($footScript != ''){
            $buffer = str_replace('</body>',$footScript.'</body>',$buffer);
        }
        return $buffer;
    }

    /**
     * 获取title信息
     **/
    public function pageTitle(){
        return $this->pageTitle == '' ?  DyPhpConfig::item('appName') : $this->pageTitle;
    }

    /**
     * @brief    设置模板数据
     * @param    $key
     * @param    $value
     * @return   
     **/
    public function setData($key='',$value=''){
        if(!empty($key) && !isset($this->viewData[$key])){
            $this->viewData[$key] = $value;
        }
    }

    /**
     * widget调用
     * @param string 调用的widget名
     * @param array 传递给widget的数据 
     **/
    protected function widget($widget,$args=array()){
        $args = is_array($args) ? $args : (array)$args;
        $rumWidget = new $widget;
        $rumWidget->run($args);
    }

    /**
     * @brief    加载css
     * @param    $css
     * @param    $return
     * @return   
     **/
    protected function loadCss($css,$return=false){
        $cssStr = '';
        if(is_array($css)){
            foreach($css as $val){
                $cssStr .=  '    <link href="'.$val.'" type="text/css" rel="stylesheet" />'."\n";
            }
        }else{
            $cssStr .=  '    <link href="'.$css.'" type="text/css" rel="stylesheet" />'."\n";
        }

        if($return){
            return $cssStr;
        }
        echo $cssStr;
    } 

    /**
     * @brief  加载js  
     * @param    $script
     * @param    $return
     * @return   
     **/
    protected function loadJs($script,$return=false){
        $scriptStr = '';
        if(is_array($script)){
            foreach($script as $val){
                $scriptStr .=  '    <script type="text/javascript" src="'.$val.'"></script>'."\n";
            }
        }else{
            $scriptStr .=  '    <script type="text/javascript" src="'.$script.'"></script>'."\n";
        }

        if($return){
            return $scriptStr;
        }
        echo $scriptStr;
    } 

}


