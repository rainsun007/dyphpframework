<?php
/**
 * mysql驱动类
 * @author 大宇 Email:dyphp.com@gmail.com
 * @link http://www.dyphp.com/
 * @copyright Copyright 2011 dyphp.com
 **/
final class DyPhpMysql{
    private $host = '';
    private $user = '';
    private $pass = '';
    private $dbName = '';
    private $port = '3306';
    private $charset = 'UTF8';
    public $tableName = '';
    public $dbConfigArr = array();

    private $conn = "pconn";
    private $pconn = false;
    private $result = '';

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

        try{
            if ($this->pconn) {
                $this->conn = mysql_pconnect($this->host.':'.$this->port, $this->user, $this->pass);
            } else {
                $this->conn = mysql_connect($this->host.':'.$this->port, $this->user, $this->pass,true);
            }

            if (!mysql_select_db($this->dbName, $this->conn)) {
                throw new Exception("mysql connect error");
            }

            mysql_query("SET NAMES {$this->charset}");
            return $this;
        }catch(Exception $e){
            DyPhpBase::throwException("can't connect to Database", '('.$e->getMessage().')'.'--mysql run error',$e->getCode(),$e);
        }
    }

    /**
     * @brief    事务开启
     * @return
     **/
    public function beginTransaction(){
        mysql_query('SET autocommit=0', $this->conn);
        mysql_query('START TRANSACTION', $this->conn);
    }

    /**
     * @brief    事务提交
     * @return
     **/
    public function commit(){
        mysql_query('COMMIT', $this->conn);
        mysql_query('SET autocommit=1', $this->conn);
    }

    /**
     * @brief    事务回滚
     * @return
     **/
    public function rollBack(){
        mysql_query('ROLLBACK', $this->conn);
        mysql_query('SET autocommit=1', $this->conn);
    }

    /**
     * @brief    写入执行
     * @param    $query
     * @return
     **/
    public function exec($query){
        $this->result = mysql_query($query, $this->conn);
        if(!$this->result){
            throw new Exception("mysql exec error: \n<br \> {$query}");
        }
        return $this->result;
    }


    /**
     * @brief    读取执行
     * @param    $query
     * @return
     **/
    public function query($query){
        $this->result = mysql_query($query, $this->conn);
        if(!$this->result){
            throw new Exception("mysql query error: \n<br \> {$query}");
        }
        return $this->result;
    }

    /**
     * @brief    获取插入ID
     * @return
     **/
    public function lastInsertId(){
        return mysql_insert_id($this->conn);
    }

    /**
     * @brief    获取单条数据
     * @return
     **/
    public function fetch(){
        return mysql_fetch_object($this->result);
    }

    /**
     * @brief    获取多条记录
     * @return
     **/
    public function fetchAll(){
        $data = array();
        while ($row = mysql_fetch_assoc($this->result)) {
            $data[] = (object)$row;
        }
        return $data;
    }

    /**
     * @brief    获取总数
     * @return
     **/
    public function count(){
        $fetch = mysql_fetch_array($this->result);
        return isset($fetch['dycount']) ? $fetch['dycount'] : mysql_num_rows($this->result);
    }


    /**
     * @brief    获取数据大小
     * @return
     **/
    public function getDataSize(){
        $this->query("SHOW TABLE STATUS");
        $size = $this->fetchAll();
        $dbCount = 0;
        if($size){
            foreach($size as $val){
                $data = $val->Data_length;
                $index = $val->Index_length;
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
        $this->query("SELECT VERSION() as dbversion");
        $version = $this->fetch();
        return $version ? $version->dbversion : 'unknown';
    }


    public function __destruct() {
        if (!$this->pconn) {
            if(is_resource($this->result)){
                mysql_free_result($this->result);
            }
            if($this->conn){
                mysql_close($this->conn);
            }
        }
    }

}
