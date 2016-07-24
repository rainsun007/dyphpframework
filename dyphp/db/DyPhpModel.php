<?php
/**
 * @file DyPhpModel.php
 * @brief  数据库操作  
 * @author QingYu.Sun Email:dyphp.com@gmail.com
 * @version 1.0
 * @copyright dyphp.com
 * @link http://www.dyphp.com
 * @date 2013-04-16
 **/

class DyPhpModel{
    //要使用的数据库配制
    protected $dbCnf = 'default';

    //是否强制使用master 设置为true之后所有请求将使用master只有显示设置为false才会切换回主从分离
    public $forceMaster = false;

    //表名
    protected $tableName = '';

    //当前时间戳
    protected $time = 0;
    //当前完整日期时间（Y-m-d H:i:s）
    protected $datetime = '1970-01-01 08:00:00';
    //当前完整日期（Y-m-d）
    protected $date = '1970-01-01';

    //支持类型
    private $dbType = 'mysql';
    private $supportType = array(
        'mysql', 
    );

    //是否使用pdo
    private $isPdo = false;

    //db版本
    private $version = '';

    protected function __construct(){
        $this->time = time();
        $this->datetime = date("Y-m-d H:i:s",$this->time);
        $this->date = date("Y-m-d",$this->time);
        $this->init();
    }

    /**
     * @brief   在实例化时执行,可以重写此方法实现自己的业务逻辑
     * @return   
     **/
    protected function init(){
    }

    /**
     * @brief  Load Balancing Strategy重写此方法在该方法中实现返回config中db配制项中某个slave,例如:db[default][slaves][0]
     * @return   
     **/
    protected function lbs(){
        return array(); 
    }

    /**
     * 添加单条记录
     * @param array array('name'=>'test','nickname'=>'test nickname')
     * @param bool  true为replace操作
     * @return int
     */
    public function insert($arrayArgs,$compatible=false){
        $table = $this->getInstance()->tableName;
        if(count($arrayArgs) <= 0){
            return false;
        }
        $mode = $compatible ? 'REPLACE' : 'INSERT';
        $sql = "{$mode} INTO `{$table}` (".$this->sqlImplode(array_keys($arrayArgs),'in').') VALUES ('.$this->sqlImplode(array_values($arrayArgs),'in','values').')';
        return $this->dbExec($sql);
    }

    /**
     * 同时写入多条记录 
     * 
     * @param array('user_id','user_name')
     * @param array(
     *             array('12345','rain sun 2'),
     *             array('1234','rain sun 1'),
     *        )
     **/
    public function insertBatch($column,$values){
        $table = $this->getInstance()->tableName;
        if(count($column) == 0 || count($values) == 0){
            return false;
        }

        $ret = '';
        foreach($values as $vals){
            $ret .= '(';
            foreach($vals as $val){
                $ret .= "'$val',";
            }
            $ret = substr($ret,0,-1).'),';
        }
        $valuesStr = substr($ret,0,-1);
        $columnStr = '('.implode(',',$column).')';

        $sql = "INSERT INTO `{$table}` {$columnStr} VALUES {$valuesStr}";
        return $this->dbExec($sql);
    }

    /**
     * 更新数据
     * @param array  array('classid'=>classid+1)
     * @param string
     * @param array  array('classid')
     * @return int
     */
    public function update($setArr=array(),$criteria='',$columnOperationArr=array()){
        $criteria = $criteria != '' ? $criteria : '1 = 1';
        if(count($setArr) <= 0){
            return false;
        }
        $sql = "UPDATE `".$this->getInstance()->tableName."` SET ".$this->sqlImplode($setArr,'up',$columnOperationArr)." WHERE ". $criteria;
        return $this->dbExec($sql);
    }

    /**
     * 删除数据
     * @param string
     * @return int
     */
    public function delete($criteria=''){
        $criteria = $criteria != '' ? $criteria : '1 = 1';
        $sql = 'DELETE FROM `'.$this->getInstance()->tableName.'` WHERE '.$criteria;
        return $this->dbExec($sql);
    }

    /**
     * @brief    使用id获取记录
     * @param    $id
     * @param    $select
     * @return   
     **/
    public function getById($id=0,$select='*'){
        if(!is_numeric($id)){
            return false;
        }
        $query = $this->querySql("id={$id}",$select);
        return $this->fetch($query);
    }

    /**
     * 获取一条记录 
     * @param obj|string DyDbCriteria类实例|完整sql语句或是where语句
     * @param 查询字段  当$criteria为where条件时有效
     **/
    public function getOne($criteria='',$select='*'){
        if(is_string($criteria)){
            $criteria = trim($criteria);
            if(empty($criteria)){
                $criteria = '1=1 LIMIT 1';
            }
        }
        $query = $this->querySql($criteria,$select);
        return $this->fetch($query);
    }

    /**
     * 获取多条记录 
     * @param obj|string DyDbCriteria类实例|完整sql语句或是where语句
     * @param 查询字段  当$criteria为where条件时有效
     **/
    public function getAll($criteria='',$select='*'){
        $query = $this->querySql($criteria,$select);
        return $this->fetchAll($query);
    }

    /**
     * @brief    执行完整的sql语句
     * @param    $query
     * @param    $fetch
     * @return   
     **/
    public function query($query='',$isFetchAll=false){
        $dbms = strpos(strtolower(ltrim($query)), 'select') === 0 ? 'slave' : 'master';
        $result = $this->getInstance($dbms)->query($query);
        $fetchResult = false;
        if($result){
            if($this->isPdo){
                $fetchResult = $isFetchAll ? $result->fetchAll() : $result->fetch();
            }else{
                $fetchResult = $isFetchAll ? $this->getInstance($dbCnf)->fetchAll() : $this->getInstance($dbCnf)->fetch();
            }
        }
        return $fetchResult;
    }

    /**
     * @brief    分页查询获取记录
     * @param    $criteria
     * @param    $pageSize
     * @param    $pageName
     * @return   
     **/
    public function getAllForPage($criteria,$pageSize=15,$pageName='page'){
        if (!is_object($criteria)) {
            DyPhpBase::throwException('page sql criteria error','getAllForPage error','dbException');
        }
        $limit = $pageSize>0 ? $pageSize : 15;
        $pageName = $pageName ? $pageName : 'page';
        $page = DyRequest::getInt($pageName,0);
        $offset = $page>0 ? ($page-1)*$limit : 0;

        $criteria->limit($limit);
        $criteria->offset($offset);
        $data = $this->getAll($criteria);

        $counts = $this->count($criteria);
        return array('data'=>$data,'count'=>$counts);
    }

    /**
     * 获取最后写入记录的ID
     * @return: int
     */
    public function getInsertId(){
        return $this->getInstance()->lastInsertId();
    }

    /**
     * @brief    获取数据大小
     * @return   
     **/
    public function getDataSize(){
        return $this->getInstance('slave')->getDataSize();
    }

    /**
     * @brief    获取版本号
     * @return   
     **/
    public function getVersion(){
        return $this->getInstance('slave')->getVersion();
    }

    /**
     * 获取查询总数
     * @param obj|string DyDbCriteria类实例|完整sql语句或是where语句
     * @return int
     */
    public function count($criteria=''){
        $query = $this->querySql($criteria,'',true);
        if(DyPhpBase::$debug){
            $start = $this->getTime();
        }

        try{
            $count = 0;
            $result = $this->getInstance('slave')->query($query);
            if($result){
                if($this->isPdo){
                    $fetch = $result->fetch();
                    $count = isset($fetch->dycount) ? $fetch->dycount : $result->rowCount(); 
                }else{
                    $count =  $this->getInstance('slave')->count();
                }
            }
        }catch(Exception $e){
            DyPhpBase::throwException('sql criteria error',$query.'--'.$e->getMessage(),'dbException');
        }

        if(DyPhpBase::$debug){
            $this->logQuery($query, $start);
        }
        return $count;
    }

    /**
     * 事务beginTransaction方法
     * @return bool
     */
    public function beginTransaction(){
        $this->getInstance()->beginTransaction();
        return $this;
    }

    /**
     * 事务commit方法
     * @return bool
     */
    public function commitTransaction(){
        $this->getInstance()->commit();
    }

    /**
     * 事务back方法
     * @return bool
     */
    public function rollBackTransaction(){
        $this->getInstance()->rollBack();
    }

    /**
     * @brief    单列化数据库
     * @param    $dbms 主/从数据库
     * @return   db instance 
     **/
    private function getInstance($dbms='master'){
        $dbConfigArr = $this->getDbConfigArr($dbms);
        if(isset($dbConfigArr['dbDriver'])){
            $this->isPdo = strpos($dbConfigArr['dbDriver'], 'pdo_') === 0 ? true : false;
            $this->dbType = $this->isPdo ? substr($dbConfigArr['dbDriver'],4) : $dbConfigArr['dbDriver'];
            if(!in_array($this->dbType,$this->supportType)){
                DyPhpBase::throwException('support databases',$this->dbType,'dbException');
            }
        }

        if(!isset($dbConfigArr['tablePrefix'])){
            $dbConfigArr['tablePrefix'] = '';
        }

        if(empty($this->tableName)){
            $this->tableName = !function_exists('get_called_class') ? $dbConfigArr['tablePrefix'].get_class($this) : $dbConfigArr['tablePrefix'].get_called_class();
        }else{
            $this->tableName = $dbConfigArr['tablePrefix'].$this->tableName;
        }
        return DyPhpModelManage::instance($dbConfigArr,$this->tableName,$this->dbType,$this->isPdo);
    }

    /**
     * @brief  获取数据库配制数组
     * @return array 
     **/
    private function getDbConfigArr($dbms='master'){
        $dbConfig = DyPhpConfig::item('db');
        if(!isset($dbConfig[$this->dbCnf])){
            DyPhpBase::throwException('database config undefined',$this->dbCnf,'dbException');
        }

        //不使用主从只使用一个数据库
        if(!isset($dbConfig[$this->dbCnf]['master']) && !isset($dbConfig[$this->dbCnf]['slaves'])){
            return $dbConfig[$this->dbCnf];
        }

        if($dbms == 'master' || $this->forceMaster){
            if(!isset($dbConfig[$this->dbCnf]['master'])){
                DyPhpBase::throwException('database config undefined',$this->dbCnf.'[master]','dbException');
            }
            return $dbConfig[$this->dbCnf]['master'];
        }else{
            if(!isset($dbConfig[$this->dbCnf]['slaves'])){
                DyPhpBase::throwException('database config undefined',$this->dbCnf.'[slaves]','dbException');
            }

            $dbLbs = $this->lbs();
            if(!is_array($dbLbs)){
                //用户自定义lbs返回值判断
                DyPhpBase::throwException('database lbs return error','getDbConfigArr error','dbException');
            }
            if(empty($dbLbs)){
                $weight = new WeightedRoundRobin($dbConfig[$this->dbCnf]['slaves']);
                return $weight->getWeight();
            }else{
                return $dbLbs;
            }
        }
    }

    /**
     * 多条记录获取器
     * @param 查询语句
     * @param 是否执行查询分析
     **/
    private function fetchAll($query,$explain=true){
        if(DyPhpBase::$debug){
            $start = $this->getTime();
        }

        try{
            $fetchResult = false;
            if($this->isPdo){
                $result=$this->getInstance('slave')->query($query);
                if($result){
                    $fetchResult = $result->fetchAll();
                }
            }else{
                $this->getInstance('slave')->query($query);
                $fetchResult = $this->getInstance('slave')->fetchAll();
            }
        }catch(Exception $e){
            DyPhpBase::throwException('sql criteria error',$query.'--'.$e->getMessage(),'dbException');
        }

        if(DyPhpBase::$debug){
            $this->logQuery($query, $start,$explain);
        }
        return $fetchResult;
    }

    /**
     * 单条记录获取器
     * @param 查询语句
     * @param 是否执行查询分析
     **/
    private function fetch($query,$explain=true){
        if(DyPhpBase::$debug){
            $start = $this->getTime();
        }

        try{
            $fetchResult = false;
            if($this->isPdo){
                $result = $this->getInstance('slave')->query($query);
                if($result){
                    $fetchResult = $result->fetch();
                }
            }else{
                $this->getInstance('slave')->query($query);
                $fetchResult = $this->getInstance('slave')->fetch();
            }
        }catch(Exception $e){
            DyPhpBase::throwException('sql criteria error',$query.'--'.$e->getMessage(),'dbException');
        }

        if(DyPhpBase::$debug){
            $this->logQuery($query, $start,$explain);
        }
        return $fetchResult;
    }

    /**
     * exec管理器
     * @param exec语句
     * @return 执行结果
     **/
    private function dbExec($sql){
        if(DyPhpBase::$debug){
            $start = $this->getTime();
        }

        try{
            $result = $this->getInstance()->exec($sql);
        }catch(Exception $e){
            DyPhpBase::throwException('sql criteria error',$sql.'--'.$e->getMessage(),'dbException');
        }

        if(DyPhpBase::$debug){
            $version = $this->version ? $this->version : $this->getInstance()->getVersion();
            $explain = version_compare($version, '5.6', '>=');
            $this->logQuery($sql, $start,$explain);
        }
        return $result;
    }

    /**
     * @brief    数组转为用sql格式的字符串
     * @param    $args
     * @param    $type
     * @param    $ftype
     * @return   
     **/
    private function sqlImplode($args,$type='up',$ftype='column'){
        $ret = "";
        if($type == 'up'){
            while(list($key,$val)=each($args)){
                //$val = $ftype == 'column' && preg_match('/^[+-][[:space:]]{0,2}\d$/i',trim($val)) ? $key.$val : "'{$val}'";
                $val = in_array($key,$ftype) ? $val : "'{$val}'"; 
                $ret .= "`{$key}`={$val},";
            }
        }elseif($type == 'in'){
            $chr = $ftype == 'column' ? '`' : "'";
            foreach($args as $val){
                $ret .= $chr.$val.$chr.',';
            }
        }
        return substr($ret,0,-1); 
    }

    /**
     * 获取SQL查询语句
     * @param object/string 
     * @param 查询字段  当$criteria为where条件时有效
     * @param bool 是否为查询总数
     **/
    private function querySql($criteria,$select='*',$isCount=false){
        if(!is_string($criteria) && !is_object($criteria)){
            DyPhpBase::throwException('sql criteria error','querySql error','dbException');
        }

        $table = $this->getInstance('slave')->tableName;
        if(is_string($criteria)){
            $criteria = trim($criteria);
            $select = $isCount ? 'count(1) as `dycount`' : $select;

            if($criteria == ''){
                return "SELECT {$select} FROM  `{$table}`";
            }elseif(strpos(strtolower($criteria), 'select') === 0){
                return $criteria;
            }else{
                return "SELECT {$select} FROM `{$table}` WHERE {$criteria}";
            }
        }

        if(is_object($criteria)){
            $getDbSql =  'get'.ucfirst($this->dbType).'Sql';
            if($isCount){
                $countCriteria = clone $criteria;
                $countCriteria->select('count(1) as `dycount`',false);
                $countCriteria->clearSqlItem('limit');
                $countCriteria->clearSqlItem('offset');
                return $countCriteria->{$getDbSql}($table);
            }
            return $criteria->{$getDbSql}($table);
        }
    }

    /**
     * sql开始时间 
     **/
    private function getTime() {
        list($usec, $sec) = explode(" ", microtime());
        $time = (float)$usec + (float)$sec;
        return $time;
    }   


    /**
     * @brief    sql结束log及sql分析
     * @param    $sql      sql语句
     * @param    $start    执行开始时间
     * @param    $explain  是否使用sql分析
     * @return   
     **/
    private function logQuery($sql, $start, $explain=true) {
        $time = $this->getTime() - $start;

        $fetch = '';
        if($this->dbType == 'mysql' && $explain){
            $query = 'EXPLAIN '.$sql;

            if($this->isPdo){
                $result=$this->getInstance('slave')->query($query);
                if($result){
                    $fetch = $result->fetch();
                }
            }else{
                $this->getInstance('slave')->query($query);
                $fetch = $this->getInstance('slave')->fetch();
            }
            $fetch = (array)$fetch;
        }

        $query = array(
            'sql' => $sql,
            'time' => $time,
            'explain'=>$fetch,
        );
        array_push(DyPhpDebug::$queries, $query);
    }

    private function __clone(){
    }
}



/**
 * @brief  model操作管理器  
 * @author QingYu.Sun Email:dyphp.com@gmail.com
 * @version 1.0
 * @copyright dyphp.com
 * @link http://www.dyphp.com
 * @date 2013-04-16
 **/

final class DyPhpModelManage {
    private static $instances = array();

    /**
     * 单列化数据库 
     **/
    public static function instance($dbConfigArr,$prefixTableName,$dbType,$isPdo){
        $insKey = $dbConfigArr['host'].'_'.$dbConfigArr['dbName'];
        $mins = self::getInstance('model_'.$insKey);
        if($mins){
            $mins->tableName = $prefixTableName;
            return $mins;
        }

        self::checkPdo($isPdo,$dbType);
        $className = $isPdo ? 'DyPhpPdo'.ucfirst($dbType) : 'DyPhp'.ucfirst($dbType);
        $driver = new $className;
        $driver->tableName = $prefixTableName;
        $driver->dbConfigArr = $dbConfigArr;
        $driver->run();

        self::setInstance('model_'.$insKey,$driver);
        return $driver;
    }

    /**
     * 设置instance记录 单列存储器
     * 
     **/
    private static function setInstance($key,$instance){
        self::$instances[$key] = $instance;
    }

    /**
     * 获取instance记录 
     **/
    private static function getInstance($key=''){
        $instance = self::$instances;
        if(array_key_exists($key,$instance)){
            return $instance[$key];
        }
        return false;
    }

    /**
     * @brief    pdo扩展加载检查 
     * @param    $dbType
     * @return   
     **/
    private static function checkPdo($isPdo,$dbType){
        if(!$isPdo){
            return;
        }
        if(!extension_loaded('pdo') || !extension_loaded('pdo_'.$dbType)){
            DyPhpBase::throwException('pdo extension loaded error','checkPdo error','dbException');
        }
    }

}


/**
 * @brief    以权重实现lbs(经简单改造)
 * @author   此算法来自网络作者不详
 **/
class WeightedRoundRobin{
    private static $_weightArray = array();
    private static $_i = -1;//代表上一次选择的服务器
    private static $_gcd;//表示集合S中所有服务器权值的最大公约数
    private static $_cw = 0;//当前调度的权值
    private static $_max;
    private static $_n;//agent个数

    public function __construct(array $weightArray){
        //配制及权重处理
        $weightArrayTmp = array();
        if(count($weightArray) == 1){
            //只有一个从配制时强制使用该配制
            $weightArray[0]['id'] = 0;
            $weightArray[0]['weight'] = 1;
            $weightArrayTmp = $weightArray;
        }else{
            //权重为0或未设置不进入计算，即视为不使用
            foreach ($weightArray as $key=>$val) {
                if(!isset($val['weight']) || $val['weight'] > 0){
                    $weightArray[$key]['id'] = $key;
                    $weightArrayTmp[] = $weightArray[$key];
                }
            }
            //权重都未设置将权重全部设置为1
            if(count($weightArrayTmp) == 0){
                foreach ($weightArray as $key=>$val) {
                    $weightArray[$key]['id'] = $key;
                    $weightArray[$key]['weight'] = 1;
                    $weightArrayTmp[] = $weightArray[$key];
                }
            }
        }

        self::$_weightArray = $weightArrayTmp;
        self::$_gcd = self::getGcd(self::$_weightArray);
        self::$_max = self::getMaxWeight(self::$_weightArray);
        self::$_n = count($weightArray);
    }

    private static function getGcd(array $weightArray){
        $temp = array_shift($weightArray);
        $min = $temp['weight'];
        $status = false;
        foreach ($weightArray as $val) {
            $min = min($val['weight'],$min);
        }

        if($min == 1){
            return 1;
        }else{
            for ($i = $min; $i>1; $i--) {
                foreach ($weightArray as $val) {
                    if (is_int($val['weight']/$i)) {
                        $status = true;
                    }else{
                        $status = false;
                        break;
                    }
                }
                if ($status) {
                    return $i;
                }else {
                    return 1;
                }

            }
        }
    }

    private static  function getMaxWeight(array $weightArray){
        if(empty($weightArray)){
            return false;
        }
        $temp = array_shift($weightArray);
        $max = $temp['weight'];
        foreach ($weightArray as $val) {
            $max = max($val['weight'],$max);                           
        }          
        return $max;
    }

    public function getWeight(){
        while (true){
            self::$_i = ((int)self::$_i+1) % (int)self::$_n;
            if (self::$_i == 0) {
                self::$_cw = (int)self::$_cw - (int)self::$_gcd;
                if (self::$_cw <= 0) {
                    self::$_cw = (int)self::$_max;
                    if (self::$_cw == 0) {
                        return null;
                    }
                }
            }
            if ((int)(self::$_weightArray[self::$_i]['weight']) >= self::$_cw) {
                return self::$_weightArray[self::$_i];
            }
        }
    }
}


