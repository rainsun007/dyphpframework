<?php
/**
 * view类.
 *
 * @author 大宇 Email:dyphp.com@gmail.com
 *
 * @link http://www.dyphp.com/
 *
 * @copyright Copyright 2011 dyphp.com
 **/
class DyPhpView
{
    //layout file
    private $layoutFile = '';
    //view file
    private $viewFile = '';
    //模板数据
    private $viewData = array();
    //view渲染完成后的html，用于getRenderHtml方法
    private $renderHtml = '';
    //是否直接输出到客户端
    private $flush = false;

    //默认使用的主题
    public $defaultTheme = 'default';
    //默认使用的layout文件, 支持跨模块调用layout(eg:/admin/Layout/main)
    public $defaultLayout = 'main';
    //自定义页面title
    public $pageTitle = '';

    /**
     * 完整view渲染.
     *
     * @param string 调用的view
     * @param array  view层数据
     **/
    public function render($view, $data = array())
    {
        //hook调用
        DyPhpBase::app()->hook->invokeHook(DyPhpHooks::BEFORE_VIEW_RENDER);

        $this->flush = true;
        $this->viewLayoutRender($view, $data);
    }

    /**
     * 获取完整view渲染后的html.
     *
     * @param string 调用的view
     * @param array  view层数据
     * 
     * @return string
     **/
    public function getRenderHtml($view, $data = array())
    {
        $this->flush = false;
        $this->viewLayoutRender($view, $data);
        return $this->renderHtml;
    }

    /**
     * 局部view渲染.
     *
     * @param string 调用的view
     * @param array  view层数据
     * @param string 主题（优先级高于defaultTheme属性），跨模块调用layout时将使用到此参数
     **/
    public function renderPartial($view, $data = array(), $moduleTheme = '')
    {
        $this->attrSet($view, $data, $moduleTheme);

        if (!file_exists($this->viewFile)) {
            DyPhpBase::throwException('view does not exist', $view);
        }

        if (is_array($this->viewData)) {
            extract($this->viewData);
        }
        require $this->viewFile;
    }

    /**
     * 获取title信息.
     **/
    public function pageTitle()
    {
        return $this->pageTitle == '' ? DyPhpConfig::item('appName') : $this->pageTitle;
    }

    /**
     * @brief    设置模板变量
     *
     * @param   mix $key
     * @param   mix $value
     **/
    public function setData($key = '', $value = '')
    {
        if (!empty($key) && !isset($this->viewData[$key])) {
            $this->viewData[$key] = $value;
        }
    }

    /**
     * @brief    获取模板变量
     * 主要使用场景为在renderPartial中调用了setData方法 在layout或其它view中后续执行代码中要使用设置的模板变量
     *
     * @param  string $key
     *
     * @return mix
     **/
    public function getData($key = '')
    {
        return !empty($key) && isset($this->viewData[$key]) ? $this->viewData[$key] : '';
    }

    /**
     * widget调用.
     *
     * @param string 调用的widget名
     * @param array 传递给widget的数据
     **/
    protected function widget($widget, $args = array())
    {
        $args = is_array($args) ? $args : (array) $args;
        $rumWidget = new $widget();
        $rumWidget->run($args);
    }

    /**
     * @brief    加载css
     *
     * @param  string $css
     * @param  bool   $return
     *
     * @return string
     **/
    protected function loadCss($css, $return = false)
    {
        $cssStr = '';
        if (is_array($css)) {
            foreach ($css as $val) {
                $cssStr .= '    <link href="'.$val.'" type="text/css" rel="stylesheet" />'."\n";
            }
        } else {
            $cssStr = '    <link href="'.$css.'" type="text/css" rel="stylesheet" />'."\n";
        }

        if ($return) {
            return $cssStr;
        }
        echo $cssStr;
    }

    /**
     * @brief  加载js
     *
     * @param  string  $script
     * @param  bool    $return
     *
     * @return  string 
     **/
    protected function loadJs($script, $return = false)
    {
        $scriptStr = '';
        if (is_array($script)) {
            foreach ($script as $val) {
                $scriptStr .= '    <script type="text/javascript" src="'.$val.'"></script>'."\n";
            }
        } else {
            $scriptStr = '    <script type="text/javascript" src="'.$script.'"></script>'."\n";
        }

        if ($return) {
            return $scriptStr;
        }
        echo $scriptStr;
    }

    /**
     * 完整view渲染处理器.
     *
     * @param string 调用的view
     * @param array  view层数据
     **/
     private function viewLayoutRender($view, $data = array())
     {
         $this->attrSet($view, $data);
 
         if (!file_exists($this->viewFile)) {
             DyPhpBase::throwException('view does not exist', $view.':'.$this->viewFile);
         }
 
         if (is_array($this->viewData)) {
             extract($this->viewData);
         }
 
         $contentObStart = ob_start();
         include $this->viewFile;
         $content = ob_get_contents();
         if ($contentObStart) {
             ob_end_clean();
         }
         DyStatic::cssJsMove();
 
         $layoutObStart = ob_start(array($this, 'formatViewLayoutRender'));
         include $this->layoutFile;
         if ($layoutObStart) {
            $this->flush ? ob_end_flush() : ob_end_clean();
         }
     }

    /**
     * 属性设置.
     *
     * @param string 调用的view
     * @param array  view层数据
     * @param string 主题（优先级高于defaultTheme属性）
     **/
     private function attrSet($view = '', $data = array(), $moduleTheme = '')
     {
        $moduleTheme = $moduleTheme == '' ? $this->defaultTheme : $moduleTheme;

        $viewRoot = DyPhpConfig::item('appPath').DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR;
        $themeViewRoot = $viewRoot.$moduleTheme.DIRECTORY_SEPARATOR;

        $this->layoutFile = strpos($this->defaultLayout, '/') === false ? $themeViewRoot.'Layout'.DIRECTORY_SEPARATOR.$this->defaultLayout.EXT : $viewRoot.$this->defaultLayout.EXT;

        $view = trim($view, '/');
        $view = strpos($view, '/') === false ? ucfirst(DyPhpBase::app()->cid).DIRECTORY_SEPARATOR.$view : $view;
        $this->viewFile = $themeViewRoot.$view.EXT;

        $this->viewData = array_merge($this->viewData, $data);
     }
 
     /**
      * 格式化完整view渲染,静态文件加载.
      *
      * @param string  缓冲输出文件内容
      * 
      * @return string 返回html及js,css加载处理结果
      **/
     private function formatViewLayoutRender($buffer)
     {
         if (empty($buffer)) {
             return '';
         }
 
         $headCssScript = DyStatic::viewCssLoad().DyStatic::viewHeadScriptLoad();
         if ($headCssScript != '') {
             $buffer = str_replace('</head>', $headCssScript.'</head>', $buffer);
         }
 
         $bodyScript = DyStatic::viewBodyScriptLoad();
         if ($bodyScript != '') {
             if (preg_match('/<body.*?>/i', $buffer, $regs)) {
                 $buffer = str_replace($regs[0], $regs[0].$bodyScript, $buffer);
             }
         }
 
         $footScript = DyStatic::viewFootScriptLoad();
         if ($footScript != '') {
             $buffer = str_replace('</body>', $footScript.'</body>', $buffer);
         }

         $this->renderHtml = !$this->flush ? $buffer : '';
         return $buffer;
     }
}
