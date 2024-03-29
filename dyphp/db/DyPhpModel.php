<?php

/**
 * @file DyPhpModel.php
 * 数据库操作
 *
 * @author QingYu.Sun Email:dyphp.com@gmail.com
 *
 * @version 1.0
 *
 * @copyright dyphp.com
 *
 * @link http://www.dyphp.com
 * 
 **/
class DyPhpModel
{
    //默认使用的数据库配制,切换配制需要在使用中自行显示重写该值
    protected $dbCnf = 'default';

    //是否强制使用master,设置为true之后所有请求将使用master，只有显示设置为false才会切换回主从分离
    public $forceMaster = false;

    // 是否开启mysql explain分析
    public $openExplain = true;

    //model对应的表, 支持在使用中动态修改该值
    protected $tableName = '';

    //是否强制以字符串方式处理所有的值
    protected $stringifyValues = true;

    //当前时间戳
    protected $time = 0;

    //当前完整日期和时间（Y-m-d H:i:s）
    protected $datetime = '1970-01-01 08:00:00';

    //当前完整日期（Y-m-d）
    protected $date = '1970-01-01';

    //配制中设置的数据库类型, dbDriver不设置时, 默认使用mysql
    private $dbType = 'mysql';

    //已支持的数据库类型
    private $supportType = array(
        'mysql',
    );

    //配置中是否设置为使用PDO, dbDriver不设置时, 默认使用PDO
    private $isPdo = true;

    //lbs权重处理类单例
    private $weightRound = false;

    //数据库连接实例
    private $instance = null;

    protected function __construct()
    {
        //时间初始化
        $this->time = time();
        $this->datetime = date('Y-m-d H:i:s', $this->time);
        $this->date = date('Y-m-d', $this->time);

        //数据库存连接初始化
        $this->instance = $this->getInstance();

        //执行用户自定义业务逻辑
        $this->init();
    }

    /**
     * 在实例化时自动执行此方法,可以重写此方法实现自己的业务逻辑
     * 注意: 每次实例化时都会被执行, 如整个周期内只允许执行一次, 需要自己实现业务逻辑
     **/
    protected function init()
    {
    }

    /**
     * Load Balancing Strategy
     * 重写此方法，实现自定义负载均衡
     * 按自定规则每次返回某个slave配制即可
     * 返回值必须是数组类型
     * 
     * 例如:
     * $dbConfig = DyPhpConfig::item('db');
     * return $dbConfig['default']['slaves'][0];
     * 
     * @return array
     **/
    protected function lbs()
    {
        return array();
    }

    /**
     * 获取指定属性值
     *
     * @param string $attr 属性
     * @return void
     */
    public function getItem($attr)
    {
        return isset($this->$attr) ? $this->$attr : null;
    }

    /**
     * 添加单条记录.
     *
     * @param array array('name'=>'test','nickname'=>'test nickname')
     * @param bool  true为replace操作
     *
     * @return int
     */
    public function insert($arrayArgs, $compatible = false)
    {
        $table = $this->tableName;
        if (count($arrayArgs) <= 0) {
            return false;
        }
        $mode = $compatible ? 'REPLACE' : 'INSERT';
        $sql = "{$mode} INTO `{$table}` (" . $this->sqlImplode(array_keys($arrayArgs), 'in') . ') VALUES (' . $this->sqlImplode(array_values($arrayArgs), 'in', 'values') . ')';

        return $this->dbExec($sql);
    }

    /**
     * 同时写入多条记录.
     *
     * @param array array('user_id','user_name')
     * @param array array(
     *                  array('12345','rain sun 2'),
     *                  array('1234','rain sun 1'),
     *              )
     * 
     * @return int
     **/
    public function insertBatch($column, $values)
    {
        $table = $this->tableName;
        if (count($column) == 0 || count($values) == 0) {
            return false;
        }

        $ret = '';
        foreach ($values as $vals) {
            $ret .= '(';
            foreach ($vals as $val) {
                $ret .= "'$val',";
            }
            $ret = substr($ret, 0, -1) . '),';
        }
        $valuesStr = substr($ret, 0, -1);
        $columnStr = '(' . implode(',', $column) . ')';

        $sql = "INSERT INTO `{$table}` {$columnStr} VALUES {$valuesStr}";

        return $this->dbExec($sql);
    }

    /**
     * 更新数据.
     *
     * @param array  array('classid'=>'classid+1')
     * @param string where条件
     * @param array  进行自加自减操作的字段 array('classid')
     *
     * @return int
     */
    public function update($setArr = array(), $criteria = '', $columnOperationArr = array())
    {
        $criteria = $criteria != '' ? $criteria : '1 = 1';
        if (count($setArr) <= 0) {
            return false;
        }
        $sql = 'UPDATE `' . $this->tableName . '` SET ' . $this->sqlImplode($setArr, 'up', $columnOperationArr) . ' WHERE ' . $criteria;

        return $this->dbExec($sql);
    }

    /**
     * 更新数据(字段自加操作).
     *
     * @param array  array('classid'=>1,'classid2'=>2)
     * @param string where条件
     *
     * @return int
     */
    public function incr($setArr = array(), $criteria = '')
    {
        $upSetArr = $columnOperationArr = array();
        foreach ($setArr as $key => $value) {
            $upSetArr[$key] = $key . '+' . $value;
            $columnOperationArr[] = $key;
        }
        return $this->update($upSetArr, $criteria, $columnOperationArr);
    }

    /**
     * 更新数据(字段自减操作).
     *
     * @param array  array('classid'=>1,'classid2'=>2)
     * @param string where条件
     *
     * @return int
     */
    public function decr($setArr = array(), $criteria = '')
    {
        $upSetArr = $columnOperationArr = array();
        foreach ($setArr as $key => $value) {
            $upSetArr[$key] = $key . '-' . $value;
            $columnOperationArr[] = $key;
        }
        return $this->update($upSetArr, $criteria, $columnOperationArr);
    }

    /**
     * 删除数据.
     *
     * @param string
     *
     * @return int
     */
    public function delete($criteria = '')
    {
        $criteria = $criteria != '' ? $criteria : '1 = 1';
        $sql = 'DELETE FROM `' . $this->tableName . '` WHERE ' . $criteria;

        return $this->dbExec($sql);
    }

    /**
     * 使用id获取记录
     *
     * @param int     $id
     * @param string  $select
     *
     * @return object
     **/
    public function getById($id = 0, $select = '*')
    {
        if (!is_numeric($id)) {
            return false;
        }
        $query = $this->querySql("id={$id}", $select);

        return $this->fetch($query);
    }

    /**
     * 获取一条记录.
     *
     * @param mixed  $criteria  DyDbCriteria类实例 或 完整sql语句 或 是where语句
     * @param string $select    查询字段  当$criteria为where条件时有效
     *
     * @return object
     **/
    public function getOne($criteria = '', $select = '*')
    {
        if (is_string($criteria)) {
            $criteria = trim($criteria);
            if (empty($criteria)) {
                $criteria = '1=1 LIMIT 1';
            }
        }
        $query = $this->querySql($criteria, $select);

        return $this->fetch($query);
    }

    /**
     * 获取多条记录.
     *
     * @param mixed  $criteria  DyDbCriteria类实例 或 完整sql语句 或 是where语句
     * @param string $select    查询字段  当$criteria为where条件时有效
     *
     * @return array
     **/
    public function getAll($criteria = '', $select = '*')
    {
        $query = $this->querySql($criteria, $select);

        return $this->fetchAll($query);
    }

    /**
     * 执行完整的sql语句
     *
     * @param  string  $query        sql语句
     * @param  bool    $isFetchAll   true为返回全部，false为只返回一条
     *
     * @return
     **/
    public function query($query = '', $isFetchAll = false)
    {
        if (DyPhpBase::$debug) {
            $start = $this->getTime();
        }

        $dbms = strpos(strtolower(ltrim($query)), 'select') === 0 ? 'slave' : 'master';

        //先获取查询句柄
        $result = $this->getInstance($dbms)->query($query);

        $fetchResult = false;
        if ($result) {
            if ($this->isPdo) {
                $fetchResult = $isFetchAll ? $result->fetchAll() : $result->fetch();
            } else {
                $fetchResult = $isFetchAll ? $this->getInstance($dbms)->fetchAll() : $this->getInstance($dbms)->fetch();
            }
        }

        if (DyPhpBase::$debug) {
            $this->logQuery($query, $start);
        }

        return $fetchResult;
    }

    /**
     * 执行完整的sql语句(兼容pdo exec)
     *
     * @param string $sql
     * @return void
     */
    public function exec($sql)
    {
        return $this->dbExec($sql);
    }

    /**
     * 分页查询获取记录
     *
     * @param mixed  $criteria DyDbCriteria类实例 或 完整sql语句 或 是where语句
     * @param int    $pageSize
     * @param string $page     此参数为int类型时直接做为页数使用  为字符串时做为$_GET的key使用(默认为page)
     *
     * @return
     **/
    public function getAllForPage($criteria, $pageSize = 20, $page = 'page')
    {
        $limit = $pageSize > 0 ? $pageSize : 20;
        $cpage = is_int($page) ? $page : DyRequest::getInt($page, 0);
        $offset = $cpage > 0 ? ($cpage - 1) * $limit : 0;

        $data = array();
        $counts = 0;
        if (is_object($criteria)) {
            $criteria->limit($limit);
            $criteria->offset($offset);
            $data = $this->getAll($criteria);
            $counts = $this->count($criteria);
        } else {
            $criteriaLimit = $criteria . " LIMIT {$offset},{$limit}";
            $data = $this->getAll($criteriaLimit);
            $counts = $this->count($criteria);
        }

        return array('data' => $data, 'count' => $counts);
    }

    /**
     * 获取最后写入记录的ID.
     *
     * @return: int
     */
    public function getInsertId()
    {
        return $this->instance->lastInsertId();
    }

    /**
     * 获取数据大小
     *
     * @return int
     **/
    public function getDataSize()
    {
        return $this->getInstance('slave')->getDataSize();
    }

    /**
     * 获取版本号
     *
     * @return string
     **/
    public function getVersion()
    {
        return $this->getInstance('slave')->getVersion();
    }

    /**
     * 获取查询总数.
     *
     * @param mixed $criteria DyDbCriteria类实例 或 完整sql语句 或 是where语句
     *
     * @return int
     */
    public function count($criteria = '')
    {
        $query = $this->querySql($criteria, '', true);
        if (DyPhpBase::$debug) {
            $start = $this->getTime();
        }

        $count = 0;
        try {
            //先获取查询句柄
            $result = $this->getInstance('slave')->query($query);

            if ($result) {
                if ($this->isPdo) {
                    $fetch = $result->fetch();
                    $count = isset($fetch->dycount) ? $fetch->dycount : $result->rowCount();
                } else {
                    $count = $this->getInstance('slave')->count();
                }
            }
        } catch (Exception $e) {
            DyPhpBase::throwException('sql criteria error', $query . '--' . $e->getMessage(), $e->getCode(), $e);
        }

        if (DyPhpBase::$debug) {
            $this->logQuery($query, $start);
        }

        return $count;
    }

    /**
     * 事务beginTransaction方法.
     *
     * @return bool
     */
    public function beginTransaction()
    {
        $this->instance->beginTransaction();

        return $this;
    }

    /**
     * 事务commit方法.
     *
     * @return bool
     */
    public function commitTransaction()
    {
        $this->instance->commit();
    }

    /**
     * 事务back方法.
     *
     * @return bool
     */
    public function rollBackTransaction()
    {
        $this->instance->rollBack();
    }

    /**
     * 单列化数据库
     *
     * @param string  $dbms 主/从数据库
     *
     * @return object db instance 数据库实例
     **/
    private function getInstance($dbms = 'master')
    {
        $dbConfigArr = $this->getDbConfigArr($dbms);

        //已实例化的数据库连接直接返回
        if($this->instance){
            $instanceInfo = $this->instance->dbConfigArr['dbName'].$this->instance->dbConfigArr['host'].$this->instance->dbConfigArr['port'];
            $dbmsInfo = $dbConfigArr['dbName'].$dbConfigArr['host'].$dbConfigArr['port'];
            if($instanceInfo == $dbmsInfo){
                return $this->instance;
            }
        }

        //数据库类型及PDO使用处理
        if (isset($dbConfigArr['dbDriver']) && !empty($dbConfigArr['dbDriver'])) {
            $this->isPdo = strpos(trim($dbConfigArr['dbDriver']), 'pdo_') === 0 ? true : false;
            $this->dbType = $this->isPdo ? substr($dbConfigArr['dbDriver'], 4) : $dbConfigArr['dbDriver'];
            if (!in_array($this->dbType, $this->supportType)) {
                DyPhpBase::throwException('support databases', $this->dbType, 0);
            }
        }

        //自动拼接表前缀
        $tablePrefix = isset($dbConfigArr['tablePrefix']) ? $dbConfigArr['tablePrefix'] : '';
        if (empty($this->tableName)) {
            $this->tableName = !function_exists('get_called_class') ? $tablePrefix . get_class($this) : $tablePrefix . get_called_class();
        } else {
            $this->tableName = $tablePrefix . $this->tableName;
        }

        return DyPhpModelManage::instance($dbConfigArr, $this);
    }

    /**
     * 获取数据库配制数组
     *
     * @param string  $dbms 主/从数据库
     *
     * @return array
     **/
    private function getDbConfigArr($dbms = 'master')
    {
        $dbConfig = DyPhpConfig::item('db');
        if (!isset($dbConfig[$this->dbCnf])) {
            DyPhpBase::throwException('database config undefined', $this->dbCnf, 0);
        }

        //不使用主从只使用一个数据库时直接返回
        if (!isset($dbConfig[$this->dbCnf]['master']) && !isset($dbConfig[$this->dbCnf]['slaves'])) {
            return $dbConfig[$this->dbCnf];
        }

        //使用主从时，主从必须都要配制，支持强制使用主库
        if ($dbms == 'master' || $this->forceMaster) {
            if (!isset($dbConfig[$this->dbCnf]['master'])) {
                DyPhpBase::throwException('database config undefined', $this->dbCnf . '[master]', 0);
            }
            return $dbConfig[$this->dbCnf]['master'];
        }

        //从库未配制抛出异常
        if (!isset($dbConfig[$this->dbCnf]['slaves'])) {
            DyPhpBase::throwException('database config undefined', $this->dbCnf . '[slaves]', 0);
        }

        //支持用户自定义从库重负载均衡
        $dbLbs = $this->lbs();
        if (!is_array($dbLbs)) {
            DyPhpBase::throwException('database lbs return error', 'getDbConfigArr error', 0);
        }

        //配制了自定义重负载均衡规则直接返回
        if ($dbLbs) {
            return $dbLbs;
        }

        //系统自动按权重负载均衡选择从库
        if (!$this->weightRound) {
            $this->weightRound = new DyphpWeightRound($dbConfig[$this->dbCnf]['slaves']);
        }
        return $this->weightRound->getDbConfig();
    }

    /**
     * 多条记录获取器.
     *
     * @param string  查询语句
     * @param bool    是否执行查询分析
     *
     * @return mixed
     **/
    private function fetchAll($query, $explain = true)
    {
        if (DyPhpBase::$debug) {
            $start = $this->getTime();
        }

        try {
            $fetchResult = false;
            if ($this->isPdo) {
                $result = $this->getInstance('slave')->query($query);
                if ($result) {
                    $fetchResult = $result->fetchAll();
                }
            } else {
                $this->getInstance('slave')->query($query);
                $fetchResult = $this->getInstance('slave')->fetchAll();
            }
        } catch (Exception $e) {
            DyPhpBase::throwException('sql criteria error', $query . '--' . $e->getMessage(), $e->getCode(), $e);
        }

        if (DyPhpBase::$debug) {
            $this->logQuery($query, $start, $explain);
        }

        return $fetchResult;
    }

    /**
     * 单条记录获取器.
     *
     * @param string  查询语句
     * @param bool    是否执行查询分析
     *
     * @return mixed
     **/
    private function fetch($query, $explain = true)
    {
        if (DyPhpBase::$debug) {
            $start = $this->getTime();
        }

        try {
            $fetchResult = false;
            if ($this->isPdo) {
                $result = $this->getInstance('slave')->query($query);
                if ($result) {
                    $fetchResult = $result->fetch();
                }
            } else {
                $this->getInstance('slave')->query($query);
                $fetchResult = $this->getInstance('slave')->fetch();
            }
        } catch (Exception $e) {
            DyPhpBase::throwException('sql criteria error', $query . '--' . $e->getMessage(), $e->getCode(), $e);
        }

        if (DyPhpBase::$debug) {
            $this->logQuery($query, $start, $explain);
        }

        return $fetchResult;
    }

    /**
     * exec管理器.
     *
     * @param string exec语句,完整sql
     *
     * @return mixed
     **/
    private function dbExec($sql)
    {
        if (DyPhpBase::$debug) {
            $start = $this->getTime();
        }

        try {
            $result = $this->instance->exec($sql);
        } catch (Exception $e) {
            DyPhpBase::throwException('sql criteria error', $sql . '--' . $e->getMessage(), $e->getCode(), $e);
        }

        if (DyPhpBase::$debug) {
            //写入与更新操作分析，现只针对mysql5.6及以上版本进行处理
            $explain = $this->dbType == 'mysql' && version_compare($this->getVersion(), '5.6', '>=') ? true : false;
            $this->logQuery($sql, $start, $explain);
        }

        return $result;
    }

    /**
     * 数组转sql格式化字符串
     *
     * @param  array  $args   update，insert字段与值对应的数组
     * @param  string $type   up为update, in为insert
     * @param  mixed  $ftype  $type为in时有两个值（column为字段类型，values为值类型）,$type为up时为数组（进行自加自减操作的字段不会加引号）
     *
     * @return string
     **/
    private function sqlImplode($args, $type = 'up', $ftype = 'column')
    {
        $ret = '';
        if ($type == 'up') {
            foreach ($args as $key => $val) {
                $val = in_array($key, $ftype) ? $val : "'{$val}'";
                $ret .= "`{$key}`={$val},";
            }
        } elseif ($type == 'in') {
            $chr = $ftype == 'column' ? '`' : "'";
            foreach ($args as $val) {
                $ret .= $chr . $val . $chr . ',';
            }
        }

        return substr($ret, 0, -1);
    }

    /**
     * 获取SQL查询语句.
     *
     * @param mixed   $criteria DyDbCriteria类实例 或 完整sql语句 或 是where语句
     * @param string  $select   查询字段  当$criteria为where条件时有效
     * @param bool    $isCount  是否为查询总数
     *
     * @return string
     **/
    private function querySql($criteria, $select = '*', $isCount = false)
    {
        if (!is_string($criteria) && !is_object($criteria)) {
            DyPhpBase::throwException('sql criteria error', 'querySql error', 0);
        }

        $table = $this->tableName;
        if (is_string($criteria)) {
            $criteria = trim($criteria);
            $select = $isCount ? 'count(1) as `dycount`' : $select;

            if ($criteria == '') {
                return "SELECT {$select} FROM  `{$table}`";
            } elseif (strpos(strtolower($criteria), 'select') === 0) {
                return $criteria;
            } else {
                return "SELECT {$select} FROM `{$table}` WHERE {$criteria}";
            }
        }

        if (is_object($criteria)) {
            $getDbSql = 'get' . ucfirst($this->dbType) . 'Query';
            if ($isCount) {
                $countCriteria = clone $criteria;
                $countCriteria->select('count(1) as `dycount`', false);
                $countCriteria->clearSqlItem('limit');
                $countCriteria->clearSqlItem('offset');

                return $countCriteria->{$getDbSql}($table);
            }

            return $criteria->{$getDbSql}($table);
        }
    }

    /**
     * sql开始时间.
     **/
    private function getTime()
    {
        list($usec, $sec) = explode(' ', microtime());
        $time = (float) $usec + (float) $sec;

        return $time;
    }

    /**
     * sql结束log及sql分析
     *
     * @param   $sql     sql语句
     * @param   $start   执行开始时间
     * @param   $explain 是否使用sql分析
     *
     * @return null
     **/
    private function logQuery($sql, $start, $explain = true)
    {
        $time = $this->getTime() - $start;

        //mysql查询分析处理
        $explainFetchResult = '';
        if ($this->dbType == 'mysql' && $explain && $this->openExplain) {
            $query = 'EXPLAIN ' . $sql;

            if ($this->isPdo) {
                $result = $this->getInstance('slave')->query($query);
                if ($result) {
                    $explainFetchResult = $result->fetch();
                }
            } else {
                $this->getInstance('slave')->query($query);
                $explainFetchResult = $this->getInstance('slave')->fetch();
            }
            $explainFetchResult = (array) $explainFetchResult;
        }

        // 查询及分析结果注册到debug处理逻辑
        $query = array(
            'sql' => $sql,
            'time' => $time,
            'explain' => $explainFetchResult,
        );
        array_push(DyDebug::$queries, $query);
    }

    private function __clone()
    {
    }
}

/**
 * model操作管理器
 *
 * @author QingYu.Sun Email:dyphp.com@gmail.com
 *
 * @version 1.0
 *
 * @copyright dyphp.com
 *
 * @link http://www.dyphp.com
 * 
 **/
final class DyPhpModelManage
{
    private static $instances = array();

    /**
     * 单列化数据库.
     *
     * @param array   $dbConfigArr   数据库配制
     * @param object  $DyPhpModel    DyPhpModel类实例
     *
     * @return object 数据库链接实例
     */
    public static function instance($dbConfigArr, $DyPhpModel)
    {
        $stringifyValues = $DyPhpModel->getItem('stringifyValues');
        $dbType = $DyPhpModel->getItem('dbType');
        $isPdo = $DyPhpModel->getItem('isPdo');

        $insKey = md5($dbConfigArr['dbName'] . '_' . $dbConfigArr['host'] . '_' . $dbConfigArr['port']);
        $mins = self::getInstance($insKey);
        if ($mins) {
            return $mins;
        }

        self::checkPdoExtension($isPdo, $dbType);

        $className = $isPdo ? 'DyPhpPdo' . ucfirst($dbType) : 'DyPhp' . ucfirst($dbType);
        $driver = new $className();

        $driver->dbConfigArr = $dbConfigArr;

        if($isPdo){
            $driver->stringifyValues = $stringifyValues;
        }

        $driver->run();

        self::setInstance($insKey, $driver);
        return $driver;
    }


    /**
     * 设置instance记录 单列存储器.
     *
     * @param string $key       单例key
     * @param object $instance  实例对象
     * 
     */
    private static function setInstance($key, $instance)
    {
        self::$instances[$key] = $instance;
    }

    /**
     * 获取instance记录.
     * 
     * @param string $key       单例key
     * 
     **/
    private static function getInstance($key = '')
    {
        $instance = self::$instances;
        if (array_key_exists($key, $instance)) {
            return $instance[$key];
        }

        return false;
    }

    /**
     * pdo扩展加载检查
     *
     * @param   $isPdo  是否为pdo
     * @param   $dbType 数据库类型
     *
     * @return null
     **/
    private static function checkPdoExtension($isPdo, $dbType)
    {
        if (!$isPdo) {
            return;
        }

        if (!extension_loaded('pdo') || !extension_loaded('pdo_' . $dbType)) {
            DyPhpBase::throwException('pdo extension loaded error', 'checkPdo error', 0);
        }
    }
}

/**
 * 通过权重获取数据库
 **/
class DyphpWeightRound
{
    //从库配制数组
    private $weightArray = array();
    //从库权重计算临时数组
    private $tempWeightArray = array();
    //权重计数器
    private $weightNum = 0;

    public function __construct($weightArray)
    {
        $this->weightArray = $weightArray;
    }

    /**
     * 随机返回一个配制
     *
     * @return array
     */
    public function getDbConfig()
    {
        //只有一个从配制时直接使用该配制
        if (count($this->weightArray) == 1) {
            return $this->weightArray[0];
        }

        if ($this->tempWeightArray) {
            shuffle($this->tempWeightArray);
            $waKey = $this->tempWeightArray[mt_rand(0, $this->weightNum - 1)];
            return $this->weightArray[$waKey];
        }

        foreach ($this->weightArray as $key => $val) {
            if (!isset($val['weight']) || $val['weight'] <= 0) {
                continue;
            }

            $this->weightNum += $val['weight'];
            for ($i = 0; $i < $val['weight']; $i++) {
                $this->tempWeightArray[] = $key;
            }
        }

        //若全都未设置weight，则随时返回一个配制
        if (!$this->tempWeightArray && $this->weightArray) {
            shuffle($this->weightArray);
            return $this->weightArray[array_rand($this->weightArray)];
        }

        shuffle($this->tempWeightArray);
        $waKey = $this->tempWeightArray[mt_rand(0, $this->weightNum - 1)];
        return $this->weightArray[$waKey];
    }
}
