<?php
/**
 * dyphp-framework 缓存抽象类文件
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright 2011 dyphp.com
 **/
abstract class DyPhpCache{
    //获取缓存
    abstract function get($key);
    
    //设置缓存
    abstract function set($key,$value='',$expire=null);
    
    //删除缓存
    abstract function delete($key);
    
    //删除全部缓存
    abstract function flush();
}
