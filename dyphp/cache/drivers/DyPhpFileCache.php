<?php
/**
 * 文件缓存类
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright 2011 dyphp.com
 */
class DyPhpFileCache extends DyPhpCache
{
    //缓存目录
    private $cachePath = '';

    //gc是否开启  缓存量大不建议开启
    public $gcOpen = false;

    public function __construct()
    {
        $this->cachePath = rtrim(DyPhpConfig::item('appPath'), '/').'/cache/data';
    }

    /**
     * 取得缓存路径
     *
     * @param string  缓存键名
     * @param string 如目录不存在是否需要创建
     *
     * @return string
     */
    private function file($key, $isMake=true)
    {
        $md5Key = md5($key);
        $folders = array();
        for ($i=1;$i<=3;$i++) {
            $folders[] = substr($md5Key, 0, $i);
        }
        $file = sprintf('%s/%s/%s.cache', $this->cachePath, implode('/', $folders), $md5Key);
        if ($isMake) {
            $floder = dirname($file);
            $this->setPath($floder);
        }
        return $file;
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
        $hashFile   = $this->file($key);
        $fp = fopen($hashFile, "wb");
        if ($fp) {
            $data = serialize($data);

            //延长过期时间防止高并发
            touch($hashFile, time()+60);

            flock($fp, LOCK_EX);
            fwrite($fp, $data);
            flock($fp, LOCK_UN);
            fclose($fp);

            $expire = $expire<=0 ? time() : time()+$expire;
            touch($hashFile, $expire);
            return true;
        }
        return false;
    }

    /**
     * 取得一个缓存结果
     *
     * @param string 缓存键名
     * @return mixed
     */
    public function get($key)
    {
        $hashFile = $this->file($key, false);
        $data = false;
        if (is_file($hashFile)) {
            if (filemtime($hashFile) < time()) {
                unlink($hashFile);
            } else {
                $fp = fopen($hashFile, "rb");
                flock($fp, LOCK_SH);
                if ($fp) {
                    clearstatcache();
                    $length = filesize($hashFile);
                    $data = $length>0 ? fread($fp, $length) : '';
                    flock($fp, LOCK_UN);
                    fclose($fp);
                    $data = $data != '' ? unserialize($data) : '';
                }
            }
        }
        return $data;
    }

    /**
     * 删除一个key值
     *
     * @param string 缓存键名
     * @return bool
     */
    public function delete($key)
    {
        $hashFile = $this->file($key);
        if (is_file($hashFile)) {
            unlink($hashFile);
            return true;
        }
        return false;
    }

    public function exists($key)
    {
        $hashFile = $this->file($key, false);
        if (is_file($hashFile)) {
            if (filemtime($hashFile) < time()) {
                //unlink($hashFile);
                return false;
            }else{
                return true;
            }
        }
        return false;
    }

    /**
     * 清除所有缓存的数据
     *
     * @return bool
     */
    public function flush()
    {
        return $this->rmPath($this->cachePath);
    }

    /**
     * 批量创建目录
     *
     * @param string 文件夹路径
     * @param int    权限
     * @return bool
     */
    private function setPath($path, $mode = 0777)
    {
        if (!is_dir($path)) {
            $result = mkdir($path, $mode, true);
            return $result;
        }
        return true;
    }

    /**
     * 删除文件夹
     *
     * @param string 要删除的文件夹路径
     * @return bool
     */
    private function rmPath($path)
    {
        if (!is_dir($path)) {
            return false;
        }

        if ($dh = opendir($path)) {
            while (false !== ($file=readdir($dh))) {
                if ($file != '.' && $file != '..') {
                    $filePath = $path.'/'.$file;
                    is_dir($filePath) ? $this->rmPath($filePath) : unlink($filePath);
                }
            }
            closedir($dh);
        }
        $result = rmdir($path);
        return $result;
    }

    /**
     * 执行过期文件清理
     *
     * @return bool
     */
    private function gc($path)
    {
        if (!is_dir($path)) {
            return true;
        }

        if ($dh = opendir($path)) {
            while (false !== ($file=readdir($dh))) {
                if ($file != '.' && $file != '..') {
                    $filePath = $path.'/'.$file;
                    if (is_dir($filePath)) {
                        $this->gc($filePath);
                    } elseif (is_file($filePath)) {
                        $lastTime = filemtime($filePath);
                        if ($lastTime < time()) {
                            unlink($filePath);
                        }
                    }
                }
            }
            closedir($dh);
        }
        return true;
    }

    public function __destruct()
    {
        if ($this->gcOpen) {
            if (mt_rand(1, 10)<=5) {
                return $this->gc($this->cachePath);
            }
        }
    }
}
