<?php
/**
 * 公共类.
 *
 * @author 大宇 Email:dyphp.com@gmail.com
 *
 * @link http://www.dyphp.com/
 *
 * @copyright Copyright dyphp.com
 **/
class Common
{
    /**
     * @brief    提示信息
     *
     * @param   $module 'app','admin'
     * @param   $msg
     * @param   $status 'success','error','warning','info',1,0等，可自定义
     * @param   $link
     **/
    public static function msg($msg = '', $status = 'success', $code = 200, $data = array())
    {
        if (DyRequest::isAjax()) {
            $status = $status != 'success' || $status === 1 || $code != 200 ? 1 : 0;
            echo DyTools::apiJson($status, $code, $msg, $data);
            exit;
        }

        $callouts = array('success'=>'success','error'=>'danger','warning'=>'warning','info'=>'info',1=>'success',0=>'warning');
        $callout = isset($callouts[$status]) ? $callouts[$status] : 'info';
        Dy::showMsg(array('message' => $msg, 'status' => $status, 'code' => $code, 'data' => $data, 'callout' => $callout), true);
    }
}
