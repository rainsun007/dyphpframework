<?php
/**
 * dyphp-framework 缓存抽象类文件
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright dyphp.com
 *
 *   配制格式
 *   'cache'=>array(
 *       'c1'=>array('type'=>'file','gcOpen'=>false,'cacheRootPath'=>'/var/log/dyphpFramework/'),
 *       'c2'=>array('type'=>'apc'),
 *       'c3'=>array(
 *           'type'=>'memcache',
 *           'isMemd'=>false,
 *           'servers_one'=>array(
 *               array('host','port','weight'),
 *               array('host','port','weight'),
 *           ),
 *       ),
 *   ),
 */
abstract class DyPhpCache
{
    //获取缓存
    abstract public function get($key);
    
    //设置缓存
    abstract public function set($key, $value='', $expire=null);
    
    //删除缓存
    abstract public function delete($key);

    //判断缓存是否存在
    abstract public function exists($key);
    
    //删除全部缓存
    abstract public function flush();
}
