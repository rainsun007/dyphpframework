<?php
/**
 * apc缓存类
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright 2011 dyphp.com
 *
 */
class DyPhpApcCache extends DyPhpCache
{
    private $apcu = false;

    public function __construct()
    {
        $this->apcu = extension_loaded('apcu');

        if (!extension_loaded('apc') && !$this->apcu) {
            DyPhpBase::throwException('apc extension does not open');
        }
    }
    
    /**
     * 添加一个值，如果已经存在，则覆盖
     *
     * @param string 缓存键名
     * @param mixed  缓存数据
     * @param int    过期时间，单位：秒
     * @return bool
     */
    public function set($key, $data='', $expire=0)
    {
        return $this->apcu ? apcu_store($key, $data, $expire) : apc_store($key, $data, $expire);
    }
    
    /**
     * 取得一个缓存结果
     *
     * @param string 缓存键名
     * @return string
     */
    public function get($key)
    {
        return $this->apcu ? apcu_fetch($key) : apc_fetch($key);
    }
    
    /**
     * 删除一个key值
     *
     * @param string 缓存键名
     * @return bool
     */
    public function delete($key)
    {
        return $this->apcu ? apcu_fetch($key) : apc_delete($key);
    }

    public function exists($key)
    {
        return $this->apcu ? apcu_exists($key) : apc_exists($key);
    }
    
    /**
     * 清除所有缓存的数据
     *
     * @return bool
     */
    public function flush()
    {
        return $this->apcu ? apcu_clear_cache() : apc_clear_cache('user');
    }
}
