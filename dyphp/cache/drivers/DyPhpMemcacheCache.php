<?php
/**
 * memcache缓存类
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright 2011 dyphp.com
 */
class DyPhpMemcacheCache extends DyPhpCache
{
    private $connection;

    //缓存配制中的键值
    public $cacheKey;

    //memcache的服务组key
    public $serversKey;

    //是否使用Memcached
    private $isMemd;

    public function __construct()
    {
    }

    public function run()
    {
        $cacheCfg = DyPhpConfig::item('cache');
        $cache = $cacheCfg[$this->cacheKey];

        $this->isMemd = isset($cache['isMemd']) ? $cache['isMemd'] : false;
        $this->connection = $this->isMemd ? new Memcached : new Memcache;

        if (is_array($cache[$this->serversKey])) {
            foreach ($cache[$this->serversKey] as $server) {
                $host = isset($server['0']) ? $server['0'] : '127.0.0.1';
                $port = isset($server['1']) ? $server['1'] : 11211;
                $weight = isset($server['2']) ? $server['2'] : 10;
                $this->addServer($host, $port, $weight);
            }
        }
    }

    /**
     * 获取memcache链接实例，为满足要使用memcache原生方法的场景
     *
     */
    public function getConnect()
    {
        return $this->connection;
    }

    /**
     * 添加服务器
     * @param 服务器
     * @param 端口
     * @param 权重
     **/
    private function addServer($host, $port=11211, $weight=10)
    {
        if ($this->isMemd) {
            $this->connection->addServer($host, (int)$port, (int)$weight);
        } else {
            $this->connection->addServer($host, (int)$port, false, (int)$weight, 10, 15, true);
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
    public function set($key, $data='', $expire=null)
    {
        if ($this->isMemd) {
            return $this->connection->set($key, $data, $expire);
        } else {
            return $this->connection->set($key, $data, 0, $expire);
        }
    }

    /**
     * 取得一个缓存结果
     *
     * @param string 缓存键名
     * @return mixed|string
     */
    public function get($key)
    {
        return $this->connection->get($key);
    }

    /**
     * 删除一个key值
     *
     * @param string 缓存键名
     * @return bool
     */
    public function delete($key)
    {
        return $this->connection->delete($key);
    }

    public function exists($key)
    {
        if ($this->connection->get($key) === false) {
            return $this->connection->getResultCode() == Memcached::RES_NOTFOUND ? false : true;
        }

        return true;
    }

    /**
     * 清除所有缓存的数据
     *
     * @return bool
     */
    public function flush()
    {
        return $this->connection->flush();
    }
}
