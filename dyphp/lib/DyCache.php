<?php
/**
 * 缓存工具类
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright dyphp.com
 **/
class DyCache
{
    private static $cacheTypes = array('file','memcache','apc');
    private static $instances = array();
    
    /**
     * 缓存调用
     * @param string 缓存配制键值
     * @param string 只用在使用memcache时有效  缓存配制中memcache的服务组key
     * @return obj   调用的缓存类实例
     **/
    public static function invoke($cacheKey, $memCacheServersKey='servers_one')
    {
        $cache = DyPhpConfig::item('cache');
        if (array_key_exists($cacheKey, $cache)) {
            if (!is_array($cache[$cacheKey]) || !array_key_exists('type', $cache[$cacheKey])) {
                DyPhpBase::throwException('cache config format error', $cache[$cacheKey]['type']);
            }
            
            if (!in_array($cache[$cacheKey]['type'], self::$cacheTypes)) {
                DyPhpBase::throwException('cache type does not exist', $cache[$cacheKey]['type']);
            }
            
            $cacheCalss = 'DyPhp'.ucfirst($cache[$cacheKey]['type']).'Cache';
            if (isset(self::$instances[$cacheCalss])) {
                return self::$instances[$cacheCalss];
            }

            $driver = new $cacheCalss;
            if ($cache[$cacheKey]['type'] == 'memcache') {
                $driver->cacheKey = $cacheKey;
                $driver->serversKey = $memCacheServersKey;
                $driver->run();
            } elseif ($cache[$cacheKey]['type'] == 'file') {
                $driver->gcOpen = isset($cache[$cacheKey]['gcOpen']) ? $cache[$cacheKey]['gcOpen'] : false;
                $driver->cachePath = isset($cache[$cacheKey]['cacheRootPath']) && !empty($cache[$cacheKey]['cacheRootPath']) ? rtrim($cache[$cacheKey]['cacheRootPath'],'/').'/data' : rtrim(DyPhpConfig::item('appPath'), '/').'/cache/data';
            }
            
            self::$instances[$cacheCalss] = $driver;
            return $driver;
        } else {
            DyPhpBase::throwException('cache method does not exist', $cacheKey);
        }
    }
}
