<?php
/**
 * 分页widget.
 *
 * @author 大宇 Email:dyphp.com@gmail.com
 *
 * @link http://www.dyphp.com/
 *
 * @copyright Copyright dyphp.com
 **/
class DyPagerWidget extends DyPhpWidgets
{
    //每页显示数
    private $pageSize;
    //总页数
    private $pageCount;
    //总行数
    private $count;
    //当前翻页数
    private $thisPage;
    //分页参数的名称, 此参数非强制，默认值为page, 可在调用时自定义
    private $paramName;
    //分页偏移量
    private $offset;
    //url风格
    private $isRest = true;

    //分页带的参数 传入url参数生效
    private $params = array();
    //传入url参数该值为false
    private $autoUrl = true;

    //ajax分页回调js函数名
    private $ajaxCallback = '';

    private $url;
    private $first;
    private $last;
    private $previous;
    private $next;

    public function run($options = array())
    {
        $this->count = !empty($options['count']) && $options['count'] > 0 ? $options['count'] : 0;
        $this->pageSize = !empty($options['pageSize']) && $options['pageSize'] > 0 ? $options['pageSize'] : 20;
        $this->offset = !empty($options['offset']) && $options['offset'] > 0 ? $options['offset'] : 5;

        $this->paramName = !empty($options['pageName']) ? $options['pageName'] : 'page';

        $this->ajaxCallback = !empty($options['ajaxCallback']) ? $options['ajaxCallback'] : '';

        $this->first = !empty($options['first']) ? $options['first'] : 'First';
        $this->last = !empty($options['last']) ? $options['last'] : 'Last';
        $this->previous = !empty($options['pre']) ? $options['pre'] : 'Prev';
        $this->next = !empty($options['next']) ? $options['next'] : 'Next';

        $dyPhpPagerStyle = !empty($options['style']) ? $options['style'] : '/static/widgets/dypager/default.css';
        $dyPhpPagerStyle = (DyPhpConfig::item('appHttpPath') != '' ? '/'.DyPhpConfig::item('appHttpPath') : '').$dyPhpPagerStyle;
        $dyPhpPagerStyle = !empty($options['cdnStyle']) ? $options['cdnStyle'] : $dyPhpPagerStyle;

        //当前翻页数
        $this->pageCount = ceil($this->count / $this->pageSize);
        $this->thisPage = !empty($_GET[$this->paramName]) ? intval($_GET[$this->paramName]) : 1;
        $this->thisPage = $this->thisPage <= 0 ? 1 : $this->thisPage;
        if (!empty($this->pageCount) && $this->thisPage > $this->pageCount) {
            $this->thisPage = $this->pageCount;
        }

        if (!empty($options['url'])) {
            //指定分页url及参数处理
            $this->url = $options['url'];
            $this->params = isset($options['params']) && is_array($options['params']) ? $options['params'] : array();
            $this->autoUrl = false;
        } else {
            $this->isRest = DyPhpConfig::getRestCa();
            $this->url = DyRequest::createUrl(DyPhpBase::app()->pcid.'/'.DyPhpBase::app()->aid);
        }

        $dyPhpPagerShow = $this->show();
        if ($dyPhpPagerShow != '') {
            $dyPhpPagerDataCount = $this->count;
            $dyPhpPagerPageCount = $this->pageCount;
            $dyPhpPagerCurrentPage = $this->thisPage;
            $dyPhpPagerPageSize = $this->pageSize;
            $this->sysRender('dyPager', compact('dyPhpPagerShow', 'dyPhpPagerStyle', 'dyPhpPagerDataCount', 'dyPhpPagerPageCount', 'dyPhpPagerCurrentPage', 'dyPhpPagerPageSize'));
        }
    }

    /**
     * 分页输出.
     */
    private function show()
    {
        if ($this->count < 1 || $this->count <= $this->pageSize) {
            return '';
        }

        $offset = $this->offset;
        if ($offset + $this->thisPage > $this->pageCount) {
            $begin = $this->pageCount - $offset * 2;
        } else {
            $begin = $this->thisPage - $offset;
        }

        $begin = ($begin >= 1) ? $begin : 1;
        $return = '';
        $return .= $this->first();
        $return .= $this->previous();
        for ($i = $begin; $i <= $begin + $offset * 2; ++$i) {
            if ($i > $this->pageCount) {
                break;
            }
            if ($i == $this->thisPage) {
                $return .= "<a class='thisPage'>$i</a>";
            } else {
                $return .= $this->getLink($i, $i);
            }
        }
        $return .= $this->next();
        $return .= $this->last();

        return $return;
    }

    /**
     * 得到当前连接.
     *
     * @param $page
     * @param $text
     *
     * @return string
     */
    private function getLink($page, $text)
    {
        $getParam = '';
        if (!$this->autoUrl) {
            //指定分页url及参数处理
            foreach ($this->params as $key => $val) {
                $getParam .= $key.'='.$val.'&';
            }
            $getParam = substr($getParam, 0, -1);
            $getParam = $getParam ? '?'.$this->paramName.'='.$page.'&'.$getParam : '?'.$this->paramName.'='.$page;
        } else {
            //根据url风格配制自动转换url
            $getArr = array();
            foreach ($_GET as $key => $value) {
                $getArr[$key] = addslashes($value);
            }
            $getArr[$this->paramName] = $page;
            
            $getParam = $this->isRest ? '/' : '&';
            foreach ($getArr as $key => $val) {
                if ($key == 'ca' || empty($key) || trim($val) === '') {
                    continue;
                }
                $getParam .= $this->isRest ? $key.'/'.$val.'/' : $key.'='.$val.'&';
            }
            $getParam = substr($getParam, 0, -1);
        }

        $ajax = $this->ajaxCallback ? ' onclick="'.$this->ajaxCallback.'(\''.$this->url.$getParam.'\');return false;" ' : '';
        return '<a '.$ajax.' href="'.$this->url.$getParam.'">'.$text.'</a>';
    }

    /**
     * 第一页.
     *
     * @return string
     */
    private function first()
    {
        if ($this->thisPage > 5) {
            return $this->getLink('1', $this->first);
        }

        return '';
    }

    /**
     * 最后一页.
     *
     * @param $name
     *
     * @return string
     */
    private function last()
    {
        if ($this->thisPage < $this->pageCount - 5) {
            return $this->getLink($this->pageCount, $this->last);
        }

        return '';
    }

    /**
     * 上一页.
     *
     * @return string
     */
    private function previous()
    {
        if ($this->thisPage != 1) {
            return $this->getLink($this->thisPage - 1, $this->previous);
        }

        return '';
    }

    /**
     * 下一页.
     *
     * @return string
     */
    private function next()
    {
        if ($this->thisPage < $this->pageCount) {
            return $this->getLink($this->thisPage + 1, $this->next);
        }

        return '';
    }
}
