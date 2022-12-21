<?php

/**
 * mysql pdo驱动类
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright dyphp.com
 **/
final class DyPhpPdoMysql extends PDO
{
    private $host = '';
    private $user = '';
    private $pass = '';
    private $dbName = '';
    private $port = '3306';
    private $charset = 'UTF8';
    private $pconn = false;
    public $stringifyValues = true;
    public $dbConfigArr = array();

    public function __construct()
    {
    }

    private function __clone()
    {
    }

    /**
     * 运行入口
     **/
    public function run()
    {
        $dbConfigArr = $this->dbConfigArr;
        $this->pconn = isset($dbConfigArr['pconn']) ? $dbConfigArr['pconn'] : false;
        $this->host = $dbConfigArr['host'];
        $this->port = $dbConfigArr['port'];
        $this->charset = $dbConfigArr['charset'];
        $this->user = $dbConfigArr['user'];
        $this->pass = $dbConfigArr['pass'];
        $this->dbName = $dbConfigArr['dbName'];

        //连接模式
        $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbName};charset={$this->charset}";
        $arrOptions = array(PDO::ATTR_PERSISTENT => $this->pconn, PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true);

        try {
            parent::__construct($dsn, $this->user, $this->pass, $arrOptions);

            //错误提示：只显示错误码(ERRMODE_SILENT)，显示警告错误(ERRMODE_WARNING)，抛出异常(ERRMODE_EXCEPTION)
            parent::setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            //设置默认返回数组模式：关联数组(FETCH_ASSOC)，数字索引数组(FETCH_NUM)，默认两者(FETCH_BOTH)，对象形式(FETCH_OBJ)
            parent::setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

            //设置列名变成一种格式：强制大写(CASE_UPPER)，强制小写(CASE_LOWER)，原始方式(CASE_NATURAL)
            parent::setAttribute(PDO::ATTR_CASE, PDO::CASE_NATURAL);

            //设置是否强制以字符串方式对待所有的值
            parent::setAttribute(PDO::ATTR_STRINGIFY_FETCHES, $this->stringifyValues);
            //设置是否本地仿真准备(高并发情景下频繁prepare会有性能问题)
            parent::setAttribute(PDO::ATTR_EMULATE_PREPARES, $this->stringifyValues);

            $this->query("SET NAMES {$this->charset}");
            return $this;

        } catch (PDOException $e) {
            DyPhpBase::throwException("can't connect to Database", '(' . $e->getMessage() . ')' . '--pdoMysql error', $e->getCode(), $e);
        }
    }


    /**
     * 获取数据大小
     * @return
     **/
    public function getDataSize()
    {
        $size = $this->query("SHOW TABLE STATUS");
        $dbCount = 0;
        if ($size) {
            foreach ($size as $val) {
                $data = $val->Data_length;
                $index = $val->Index_length;
                $dbCount += $data + $index;
            }
        }
        return DyTools::formatSize($dbCount);
    }

    /**
     * 获取版本号
     * @return
     **/
    public function getVersion()
    {
        $result = $this->query("SELECT VERSION() as dbversion");
        $version = $result->fetch();
        return $version ? $version->dbversion : 'unknown';
    }
}
