<?php

class HaloDb extends ezSQL_mysql
{
    private $slavedb = null;

    public function __construct($dbconfig, $slavedbconfig = null)
    {
        parent::ezSQL_mysql($dbconfig['user'], $dbconfig['pass'],
            $dbconfig['dbname'], $dbconfig['host']);
        $this->query('SET NAMES utf8');
        if ($slavedbconfig != null) {
            $slavedb = new HaloDb($slavedbconfig);
            $slavedb->query('SET NAMES utf8');
        }
    }

    public function getVarByCondition($table, $condition, $varName)
    {//Fucname='%s'$this->db->escape($username)
        $sql = sprintf('SELECT %s FROM %s', $varName, $table);
        if (!empty($condition))
            $sql .= ' WHERE ' . $condition;
// 		echo $sql.'<hr/>';
        return $this->get_var($sql);
    }

    public function getCountByCondition($table, $condition, $distinct = null)
    {
        $field = $distinct == null ? '*' : 'DISTINCT ' . $distinct;
        if (empty($condition))
            $sql = sprintf('SELECT COUNT(%s) FROM %s', $field, $table);
        else
            $sql = sprintf('SELECT COUNT(%s) FROM %s WHERE %s', $field, $table, $condition);
// 				echo '<hr/>';
// 				echo $sql;
        return intval($this->get_var($sql));
    }

    public function getDistinctByCondition($table, $condition, $distinct)
    {
        $sql = sprintf('SELECT DISTINCT %s FROM %s', $distinct, $table);
        if (!empty($condition))
            $sql .= ' WHERE ' . $condition;
// 		echo $sql;
        return $this->get_col($sql);
    }

    public function getRowByCondition($table, $condition, $fields = '')
    {
        if (empty($fields))
            $sql = sprintf('SELECT * FROM %s WHERE %s LIMIT 1', $table, $condition);
        else
            $sql = sprintf('SELECT %s FROM %s WHERE %s LIMIT 1', $fields, $table, $condition);
// 		echo $sql.'<hr/>';
        return $this->get_row($sql, ARRAY_A);
    }

    public function getColByCondition($table, $condition, $colName)
    {
        if (empty($condition))
            $sql = sprintf('SELECT %s FROM %s', $colName, $table);
        else
            $sql = sprintf('SELECT %s FROM %s WHERE %s', $colName, $table, $condition);
// 		echo $sql;	
        return $this->get_col($sql);
    }

    public function getResultsByCondition($table, $condition = '', $fields = '')
    {
        if (empty($fields)) {
            if (empty($condition))
                $sql = sprintf('SELECT * FROM %s', $table);
            else
                $sql = sprintf('SELECT * FROM %s WHERE %s', $table, $condition);
        } else {
            if (empty($condition))
                $sql = sprintf('SELECT %s FROM %s', $fields, $table);
            else
                $sql = sprintf('SELECT %s FROM %s WHERE %s', $fields, $table, $condition);
        }
        $result = $this->get_results($sql, ARRAY_A);
        if ($result)
            return $result;
        else
            return array();
    }

    public function insertTable($table, $data, $appendCondition = array())
    {
        if (is_array($data)) {
            $list = $this->getConditionArray($data, $appendCondition);
            if (count($list) > 0) {
                $sql = sprintf('INSERT INTO %s SET %s', $table, implode(',', $list));
                $result = $this->query($sql);
                if ($result > 0) {
                    if ($this->insert_id === 0)
                        return true;
                    else
                        return $this->insert_id;
                }
            }
        }
        return false;
    }

    public function updateTable($table, $data, $condition, $appendCondition = array())
    {
        if (is_array($data)) {
            $list = $this->getConditionArray($data, $appendCondition);
            if (count($list) > 0) {
                $sql = sprintf('UPDATE %s SET %s WHERE %s', $table, implode(',', $list), $condition);
                return $this->query($sql);
            }
        }
        return false;
    }

    public function insertOrUpdateTable($table, $data, $condition)
    {
        $count = $this->getCountByCondition($table, $condition);
        if ($count > 0) {
            return $this->updateTable($table, $data, $condition);
        } else {
            return $this->insertTable($table, $data);
        }
    }

    public function replaceTable($table, $data, $appendCondition = array())
    {
        if (is_array($data)) {
            $list = $this->getConditionArray($data, $appendCondition);
            if (count($list) > 0) {
                $sql = sprintf('REPLACE INTO %s SET %s', $table, implode(',', $list));
                return $this->query($sql);
            }
        }
        return false;
    }

    public function delRowByCondition($table, $condition)
    {
        $list = $this->getConditionArray($condition);
        $sql = sprintf('DELETE FROM %s WHERE %s', $table, implode(' AND ', $list));
        return $this->query($sql);
    }

    public function delRowByCondition2($table, $condition)
    {
        $sql = sprintf('DELETE FROM %s WHERE %s', $table, $condition);
        return $this->query($sql);
    }

    public function getConditionArray($data, $appendCondition = array())
    {
        $list = array();
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                if (is_int($v))
                    $list[] = sprintf('%s=%d', $k, $v);
                else
                    $list[] = sprintf('%s=\'%s\'', $k, $this->escape($v));//dbencode
            }
        }
        $appendCondition = (array)$appendCondition;
        foreach ($appendCondition as $v)
            $list[] = $v;
        return $list;
    }

    public function truncateTable($table)
    {
        $sql = sprintf('TRUNCATE TABLE %s', $table);
        $this->query($sql);
    }

    private function dbencode($str, $size = 0)
    {
        if ($size > 0) {
            $str = mb_substr($str, 0, $size);
        }
        // 	checkDenyWords($str);
        return mysql_real_escape_string($str);//直接处理了
        /*$str = addslashes($str);
         //$str = addslashes(trim(str_replace(array_keys($GLOBALS['emoji_maps']),$GLOBALS['emoji_maps'],$str)));
        return $_ENV['db']->escape($str);*/
    }

    /**
     * 批量插入数据库
     * @param string $table
     * @param string $fieldData
     * @param string $valueData
     */
    public function batchInsertData($table, $fieldData, $valueData)
    {
        if (empty($fieldData) || empty($valueData)) {
            return;
        }
        $value_str = "";
        $count = count($valueData);
        for ($index = 0; $index < $count; $index++) {
            $data = $valueData[$index];
            $list = array();
            foreach ($data as $k => $v) {
                if (is_int($v)) {
                    $list[] = sprintf('%d', $v);
                } else {
                    $list[] = sprintf("'%s'", $this->escape($v));
                }
            }
            $value_str .= "(" . implode(",", $list) . ")";
            if ($index < $count - 1) {
                $value_str .= ",";
            }
        }
        $field_str = implode(",", $fieldData);
        $sql = "INSERT INTO %s (%s) VALUES %s";
        $query = sprintf($sql, $table, $field_str, $value_str);
        $this->query($query);
    }

    function query($query)
    {
        if ($this->slavedb == null || preg_match("/^(set|insert|deleteJob|update|replace)\s+/i", $query)) {
            return parent::query($query);
        } else {
            return $this->slavedb->query($query);
        }
    }
}