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
    /**
     * 获取缓存
     * 取得一个缓存结果
     *
     * @param string 缓存键名
     * @return mixed
     */
    abstract public function get($key);

    /**
     * 设置缓存
     * 添加一个值，如果已经存在，则覆盖
     *
     * @param string 缓存键名
     * @param mixed  缓存数据
     * @param int    过期时间，单位：秒
     * @return bool
     */
    abstract public function set($key, $value = '', $expire = null);

    /**
     * 删除缓存
     * 删除一个key值
     *
     * @param string 缓存键名
     * @return bool
     */
    abstract public function delete($key);

    /**
     * 判断缓存是否存在
     * 验证key是否存在
     *
     * @param string $key
     *
     * @return bool
     */
    abstract public function exists($key);

    /**
     * 清除所有缓存的数据
     *
     * @return bool
     */
    abstract public function flush();
}
