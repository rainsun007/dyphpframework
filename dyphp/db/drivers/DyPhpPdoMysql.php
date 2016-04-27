<?php
/**
 * mysql pdo驱动类
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright 2011 dyphp.com 
 **/
final class DyPhpPdoMysql extends PDO{
    private $host = '';
    private $user = '';
    private $pass = '';
    private $dbName = '';
    private $port = '3306';
    private $charset = 'UTF8';
    private $pconn = false;
    public  $tableName = '';
    public  $dbConfigArr = array();

    public function __construct(){}

    private function __clone(){}

    /**
     * 运行入口
     * @param 数据库配制键值
     **/
    public function run(){
        $dbConfigArr = $this->dbConfigArr;
        $this->host = $dbConfigArr['host'];
        $this->port = $dbConfigArr['port'];
        $this->charset = $dbConfigArr['charset'];
        $this->user = $dbConfigArr['user'];
        $this->pass = $dbConfigArr['pass'];
        $this->dbName = $dbConfigArr['dbName'];
        $this->pconn = $dbConfigArr['pconn'];

        $dsn = "mysql:host={$this->host} ; port={$this->port} ; dbname={$this->dbName}";

        //连接模式
        $arrOptions = array(PDO::ATTR_PERSISTENT=>$this->pconn,PDO::MYSQL_ATTR_USE_BUFFERED_QUERY=>true);

        try{
            parent::__construct($dsn,$this->user, $this->pass,$arrOptions);
            //设置列名变成一种格式：强制大写(CASE_UPPER)，强制小写(CASE_LOWER)，原始方式(CASE_NATURAL)
            parent::setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
            //错误提示：只显示错误码(ERRMODE_SILENT)，显示警告错误(ERRMODE_WARNING)，抛出异常(ERRMODE_EXCEPTION)
            parent::setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
            //设置默认返回数组模式：关联数组(FETCH_ASSOC)，数字索引数组(FETCH_NUM)，默认两者(FETCH_BOTH)，对象形式(FETCH_OBJ)
            parent::setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_OBJ);
            $this->query("SET NAMES {$this->charset}");
            return $this;
        }catch(PDOException $e){
            DyPhpBase::throwException("can't connect to Database", '('.$e->getMessage().')','pdoMysql error','dbException');
        }   
    }


    /**
     * @brief    获取数据大小
     * @return   
     **/
    public function getDataSize(){
        $size = $this->query("SHOW TABLE STATUS");
        $dbCount = 0;
        if($size){
            foreach($size as $val){
                $data = $val->data_length;
                $index = $val->index_length;
                $dbCount += $data+$index;
            }
        }
        return DyTools::formatSize($dbCount);
    }

    /**
     * @brief    获取版本号
     * @return   
     **/
    public function getVersion(){
        $result = $this->query("SELECT VERSION() as dbversion");
        $version = $result->fetch();
        return $version ? $version->dbversion : 'unknown'; 
    }
}

