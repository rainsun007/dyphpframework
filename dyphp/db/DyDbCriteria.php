<?php
/**
 * sql组装器.
 *
 * @author 大宇 Email:dyphp.com@gmail.com
 *
 * @link http://www.dyphp.com/
 *
 * @copyright Copyright dyphp.com
 **/
class DyDbCriteria
{
    private $select = '*';
    private $distinct = false;
    private $from = '';
    private $join = '';
    private $where = '';
    private $group = '';
    private $having = '';
    private $order = '';
    private $offset = '';
    private $limit = '';

    public function __construct()
    {
        return $this;
    }

    /**
     * @brief    查询字段
     *
     * @param  string  $select       查询字段
     * @param  bool    $isDistinct   是否使用distinct
     *
     * @return  object
     **/
    public function select($select = '*', $isDistinct = false)
    {
        $this->distinct = is_bool($isDistinct) ? $isDistinct : false;

        $select = trim($select);
        $select = empty($select) ? '*' : $select;
        if ($select != '*') {
            $selectArr = explode(',', $select);
            $selectNewArr = array();
            foreach ($selectArr as $key => $val) {
                $val = trim($val);
                if ($val) {
                    $selectNewArr[] = strpos($val, '.') !== false && stripos($val, ' as ') !== false ? '`'.$val.'`' : $val;
                }
            }
            $select = implode(',', $selectNewArr);
            $select = empty($select) ? '*' : $select;
        }
        $this->select = $select;

        return $this;
    }

    /**
     * @brief    from条件
     *
     * @param  mixed  $from 表名，单表进行查询时传入表名即可(string) ; 多表查询时专入表名数组(array)
     * @param  mixed  $alias 表别名，与$from数据类型相对应, 不需要表名的情况下可以不传此参数
     *
     * @example
     *          DyPhpBase::app()->dbc->from('user')
     *          DyPhpBase::app()->dbc->from('user','u')
     *          DyPhpBase::app()->dbc->from(array('user','order'),array('u','o'))
     *
     * @return  object
     **/
    public function from($from, $alias = null)
    {
        if (is_array($from)) {
            $froms = '';
            foreach ($from as $key => $val) {
                //$froms .= is_int($key) ? $val.',' : $key.' AS '.$val.',';
                $froms .= isset($alias[$key]) && !empty($alias[$key]) ? $val.' AS '.$alias[$key].',' : $val.',';
            }
            $this->from = substr($froms, 0, -1);
        } elseif (is_string($from)) {
            $this->from = !empty($alias) ? $from.' AS '.$alias : $from;
        }

        return $this;
    }

    /**
     * @brief    查询条件
     *
     * @param  string  $key        字段
     * @param  mixed   $val        值
     * @param  string  $condition  条件(= > < >= <= <> in notin like llike rlike notlike notllike notrlike null notnull)
     * @param  string  $xor        条件关系(AND,OR)
     *
     * @return  object
     **/
    public function where($key, $val, $condition = '=', $xor = 'AND')
    {
        $compatible = $this->join == '' ? true : false;

        $xor = strtoupper(trim($xor));
        $xor = $xor == 'AND' ? 'AND' : 'OR';
        $xor = $this->where != '' ? $xor : '';

        $this->where .= $this->_where($key, $val, $condition, $xor, $compatible);

        return $this;
    }

    /**
     * @brief    group by
     *
     * @param  mixed  $by 字段名或字段数组
     *
     * @return  object
     **/
    public function group($by)
    {
        if (is_string($by)) {
            $this->group .= $this->group == '' ? " {$by} " : " ,{$by} ";
        } elseif (is_array($by)) {
            $by = implode(',', $by);
            $this->group .= $this->group == '' ? " {$by} " : " ,{$by} ";
        }

        return $this;
    }

    /**
     * @brief    having
     *
     * @param  string  $key        字段
     * @param  mixed   $val        值
     * @param  string  $condition  条件(= > < >= <= <> in notin like llike rlike notlike notllike notrlike null notnull)
     * @param  string  $xor        条件关系(AND,OR)
     *
     * @return  object
     **/
    public function having($key, $val, $condition = '=', $xor = 'AND')
    {
        $compatible = $this->join == '' ? true : false;

        $xor = strtoupper(trim($xor));
        $xor = $xor == 'AND' ? 'AND' : 'OR';
        $xor = $this->having != '' ? $xor : '';

        $this->having .= $this->_where($key, $val, $condition, $xor, $compatible);

        return $this;
    }

    /**
     * @brief    order by
     *
     * @param  string  $by  字段名
     * @param  string  $order  asc或desc
     *
     * @return  object
     **/
    public function order($by, $order = 'ASC')
    {
        $order = in_array(strtoupper(trim($order)), array('ASC', 'DESC')) ? $order : ' ASC';
        $this->order .= $this->order == '' ? " {$by} {$order} " : ", {$by} {$order} ";

        return $this;
    }

    /**
     * @brief    offset
     *
     * @param  int $offset  偏移量
     *
     * @return  object
     **/
    public function offset($offset = 0)
    {
        $this->offset = is_numeric($offset) && $offset > 0 ? $offset : 0;

        return $this;
    }

    /**
     * @brief    limit
     *
     * @param  int $limit
     *
     * @return  object
     **/
    public function limit($limit = 1)
    {
        $this->limit = is_numeric($limit) && $limit > 0 ? $limit : 1;

        return $this;
    }

    /**
     * @brief    join条件
     *
     * @param  string $join  join语句
     * @param  string $on    on语句
     * @param  string $type  联接方式, 默认为left
     *
     * @return  object
     **/
    public function join($join = '', $on = '', $type = 'LEFT')
    {
        if (empty($join) || empty($on) || empty($type)) {
            return false;
        }
        $type = strtoupper(trim($type));
        if (in_array($type, array('LEFT', 'RIGHT', 'OUTER', 'INNER', 'LEFT OUTER', 'RIGHT OUTER', 'FULL'))) {
            $this->join .= "{$type} JOIN {$join} ON {$on}";
        }

        return $this;
    }

    /**
     * @brief    获取sql语句项信息
     *
     * @param   $item
     *
     * @return  string
     **/
    public function getSqlItem($item = '')
    {
        return isset($this->$item) ? $this->$item : null;
    }

    /**
     * @brief    清除已设置的sql项 即 设置为默认值
     *
     * @param   $item
     *
     * @return  object
     **/
    public function clearSqlItem($item = '')
    {
        if (isset($this->$item)) {
            if ($item == 'distinct') {
                $this->distinct = false;
            } elseif ($item == 'select') {
                $this->distinct = '*';
            } else {
                $this->$item = '';
            }
        }

        return $this;
    }

    /**
     * @brief    条件处理
     *
     * @param  string  $key        字段
     * @param  mixed   $val        值
     * @param  string  $condition  条件(= > < >= <= <> in notin like llike rlike notlike notllike notrlike null notnull)
     * @param  string  $xor        条件关系(AND,OR)
     * @param  bool    $compatible 兼容性处理(对字段加`, 对数字不加引号), 默认开启, 当联表时为false,需要在join中自行处理兼容性问题
     *
     * @return  string
     **/
    private function _where($key, $val, $condition = '=', $xor = 'AND', $compatible = true)
    {
        $key = $compatible && trim($key) != '' ? "`{$key}`" : $key;

        switch ($condition) {
            case 'in':
                $val = "'".join("','", explode(',', $val))."'";
                $where = "{$key} IN({$val}) ";
                break;
            case 'notin':
                $val = "'".join("','", explode(',', $val))."'";
                $where = "{$key} NOT IN({$val}) ";
                break;
            case 'like':
                $where = "{$key} LIKE '%{$val}%' ";
                break;
            case 'llike':
                $where = "{$key} LIKE '%{$val}' ";
                break;
            case 'rlike':
                $where = "{$key} LIKE '{$val}%' ";
                break;
            case 'notlike':
                $where = "{$key} NOT LIKE '%{$val}%' ";
                break;
            case 'notllike':
                $where = "{$key} NOT LIKE '%{$val}' ";
                break;
            case 'notrlike':
                $where = "{$key} NOT LIKE '{$val}%' ";
                break;
            case 'null':
                $where = "{$key} IS NULL ";
                break;
            case 'notnull':
                $where = "{$key} IS NOT NULL ";
                break;
            default:
                $val = ($compatible && is_numeric($val)) ?  $val : "'{$val}'";
                $where = "{$key} {$condition} {$val} ";
                break;
        }

        return ' '.$xor.' '.$where;
    }

    /**
     * @brief    获取mysql查询语句
     *
     * @param   string  $from  完整表名
     *
     * @return  string
     **/
    public function getMysqlQuery($from = '')
    {
        $select = $this->distinct === true ? 'DISTINCT '.$this->select : $this->select;
        $from = $this->from != '' ? $this->from : '`'.$from.'`';
        $join = $this->join != '' ? $this->join : '';
        $where = 'WHERE '.($this->where != '' ? $this->where : '1=1');
        $group = $this->group != '' ? 'GROUP BY '.$this->group : '';
        $having = $this->having != '' ? 'HAVING '.$this->having : '';
        $order = $this->order != '' ? 'ORDER BY '.$this->order : '';
        $offset = intval($this->offset) > 0 ? $this->offset.',' : '0,';
        $limit = intval($this->limit) > 0 ? 'LIMIT '.$offset.$this->limit : '';
        $query = "SELECT {$select} FROM {$from} {$join} {$where} {$group} {$having} {$order} {$limit}";

        return $query;
    }
}
