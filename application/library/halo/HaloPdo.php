<?php

class HaloPdo
{
    /**
     * @var PDO
     */
    public $dbh;
    protected $transLevel = 0;
    public $error;

    public static function condition($sql, $_ = null)
    {
        $args = func_get_args();

        if (count($args) > 1) {
            array_shift($args);
        } else {
            $args = null;
        }

        return array($sql, $args);
    }

    public function __construct($config)
    {
        $port = isset($config['port']) ? $config['port'] : 3306;
        $dsn = sprintf('mysql:host=%s;dbname=%s;port=%d', $config['host'], $config['dbname'], $port);
        YafDebug::log($dsn);
        try {
            $this->dbh = new PDO($dsn, $config['user'], $config['pass'],
                array(
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'utf8\'',
                    PDO::ATTR_PERSISTENT => false,
                    PDO::ATTR_EMULATE_PREPARES => true,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING,
                ));
        } catch (Exception $e) {
            if ($this) $this->error = $e->getMessage();
        }
    }

    protected function transactionNestable()
    {
        return true;
    }

    function transaction($call)
    {
        $this->beginTransaction();
        $ret = false;
        try {
            $ret = call_user_func($call);
        } catch (Exception $e) {
            $this->rollBack();
        }

        if ($ret) {
            $this->commit();
        } else {
            $this->rollBack();
        }

        return $ret;
    }

    public function beginTransaction()
    {
        if (!$this->transactionNestable() || $this->transLevel == 0) {
            $this->dbh->beginTransaction();
        } else {
            $this->dbh->exec(sprintf('SAVEPOINT LEVEL%d', $this->transLevel));
        }

        $this->transLevel++;
    }

    public function commit()
    {
        $this->transLevel--;
        if (!$this->transactionNestable() || $this->transLevel == 0) {
            $this->dbh->commit();
        } else {
            $this->dbh->exec(sprintf("RELEASE SAVEPOINT LEVEL%d", $this->transLevel));
        }
    }

    public function rollBack()
    {
        $this->transLevel--;

        if (!$this->transactionNestable() || $this->transLevel == 0) {
            $this->dbh->rollBack();
        } else {
            $this->dbh->exec(sprintf("ROLLBACK TO SAVEPOINT LEVEL%d", $this->transLevel));
        }
    }

    public function getVarByCondition($table, $condition, $varName)
    {
        list($condition, $values) = $this->getConditionPair($condition);
        $sql = sprintf('SELECT %s FROM %s', $varName, $table);
        if (!empty($condition))
            $sql .= ' WHERE ' . $condition;

        return $this->get_var($sql, $values);
    }

    public function getDistinctCountByCondition($table, $condition = '', $countPara = '')
    {
        list($condition, $values) = $this->getConditionPair($condition);
        if (empty($countPara))
            return $this->getCountByCondition($table, $condition, $values);
        else {
            if (empty($condition))
                $sql = sprintf('SELECT COUNT(DISTINCT %s) FROM %s', $countPara, $table);
            else
                $sql = sprintf('SELECT COUNT(DISTINCT %s) FROM %s WHERE %s', $countPara, $table, $condition);
        }

        return intval($this->get_var($sql, $values));
    }

    public function getExplainCountByCondition($table, $condition)
    {
        if (empty($condition)) {
            return 0;
        }
        list($condition, $values) = $this->getConditionPair($condition);
        $sql = sprintf('EXPLAIN SELECT COUNT(*) FROM %s WHERE %s', $table, $condition);
        $explain = $this->get_row($sql, $values);
        return $explain['rows'];
    }

    public function getCountByCondition($table, $condition = '')
    {
        list($condition, $values) = $this->getConditionPair($condition);
        if (empty($condition))
            $sql = sprintf('SELECT COUNT(*) FROM %s', $table);
        else
            $sql = sprintf('SELECT COUNT(*) FROM %s WHERE %s', $table, $condition);

        return intval($this->get_var($sql, $values));
    }

    public function getDistinctByCondition($table, $condition, $distinct)
    {
        list($condition, $values) = $this->getConditionPair($condition);
        $sql = sprintf('SELECT DISTINCT %s FROM %s', $distinct, $table);
        if (!empty($condition))
            $sql .= ' WHERE ' . $condition;

        return $this->get_col($sql, $values);
    }

    public function getRowByCondition($table, $condition, $fields = '')
    {
        list($condition, $values) = $this->getConditionPair($condition);
        if (empty($fields))
            $sql = sprintf('SELECT * FROM %s WHERE %s LIMIT 1', $table, $condition);
        else
            $sql = sprintf('SELECT %s FROM %s WHERE %s LIMIT 1', $fields, $table, $condition);

// 		echo $sql.'<hr/>';
        return $this->get_row($sql, $values);
    }

    public function getColByCondition($table, $condition, $colName)
    {
        list($condition, $values) = $this->getConditionPair($condition);
        if (empty($condition))
            $sql = sprintf('SELECT %s FROM %s', $colName, $table);
        else
            $sql = sprintf('SELECT %s FROM %s WHERE %s', $colName, $table, $condition);
        return $this->get_col($sql, $values);
    }

    public function getResultsByCondition($table, $condition = '', $fields = '')
    {
        list($condition, $values) = $this->getConditionPair($condition);
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
        return $this->get_results($sql, $values);
    }

    public function insertTable($table, $data)
    {
        if (!is_array($data))
            return false;

        list($fields, $values) = $this->getConditionArray($data);

        if (count($values)) {
            $sql = sprintf('INSERT INTO %s SET %s', $table, $fields);
            $this->query($sql, $values);
            return intval($this->dbh->lastInsertId());
        }
        return false;
    }

    public function updateFieldByIncrease($table, $field, $condition, $diff = 1)
    {
        list($where, $values) = $this->getConditionPair($condition);

        if ($where) {
            $where = 'WHERE ' . $where;
        }

        $sql = sprintf('UPDATE %s SET %s=%s+%d %s', $table, $field, $field, $diff, $where);


        $this->query($sql, $values);
    }


    public function updateFieldByIncrease2($table, $field, $data, $condition, $diff = 1)
    {
        list($condition, $conditionValues) = $this->getConditionPair($condition);
        if ($condition) {
            $condition = 'WHERE ' . $condition;
        }
        if (is_array($data)) {
            list ($fields, $values) = $this->getConditionArray($data);
            $fields .= ',' . sprintf('%s=%s+%d', $field, $field, $diff);
            if (count($values) > 0) {
                $sql = sprintf('UPDATE %s SET %s  %s', $table, $fields, $condition);
                if (count($conditionValues))
                    $values = array_merge($values, $conditionValues);
                return $this->query($sql, $values);
            }

        }
        return false;

    }

    public function batchInsertData($table, $fields, $valueData)
    {
        if (empty($fields) || empty($valueData)) {
            return null;
        }

        $rows = array();
        $values = array();
        $count = count($valueData);
        for ($index = 0; $index < $count; $index++) {
            $padArray = array_pad(array(), count($valueData[$index]), '?');
            $rows[] = '(' . implode(',', $padArray) . ')';
            foreach ($valueData[$index] as $v)
                $values[] = $v;
        }

        $sql = "INSERT IGNORE INTO %s (%s) VALUES %s";
        $query = sprintf($sql, $table, implode(',', $fields), implode(',', $rows));

        return $this->query($query, $values);
    }

    public function updateTable($table, $data, $condition)
    {
        list($condition, $conditionValues) = $this->getConditionPair($condition);
        if (is_array($data)) {
            list ($fields, $values) = $this->getConditionArray($data);
            if (count($values) > 0) {
                $sql = sprintf('UPDATE %s SET %s WHERE %s', $table, $fields, $condition);
                if (count($conditionValues))
                    $values = array_merge($values, $conditionValues);
                return $this->query($sql, $values)->rowCount();
            }

        }
        return false;
    }


    public function getConditionArray2($data)
    {
        if (count($data) == 0)
            return array(null, null);

        $fields = array();
        $values = array();
        foreach ($data as $k => $v) {
            $fields[] = sprintf('%s=%s+?', $k, $k);
            $values[] = $v;
        }

        return array(implode(',', $fields), $values);
    }

    public function updateFieldsByIncrease($table, $data, $condition)
    {
        list($condition, $conditionValues) = $this->getConditionPair($condition);
        if (is_array($data)) {
            list ($fields, $values) = $this->getConditionArray2($data);
            if (count($values) > 0) {
                $sql = sprintf('UPDATE %s SET %s WHERE %s', $table, $fields, $condition);
                if (count($conditionValues))
                    $values = array_merge($values, $conditionValues);
                return $this->query($sql, $values);
            }
        }
        return false;
    }

    public function insertOrUpdateTable($table, $data, $condition, $idField = 'Fid')
    {
        $row = $this->getRowByCondition($table, $condition, $idField);
        if ($row) {
            $this->updateTable($table, $data, $condition);
            return $row[$idField];
        } else {
            return $this->insertTable($table, $data);
        }
    }

    public function insertIfNotExist($table, $data, $condition, $keyField = 'Fid')
    {
        $rowId = 0;
        $row = $this->getRowByCondition($table, $condition, $keyField);
        if (!$row) {
            $rowId = $this->insertTable($table, $data);
        } else if (!empty($keyField)) {
            $rowId = $row[$keyField];
        }

        return $rowId;
    }

    public function replaceTable($table, $data)
    {
        if (is_array($data)) {
            list($fields, $values) = $this->getConditionArray($data);
            if (count($values) > 0) {
                $sql = sprintf('REPLACE INTO %s SET %s', $table, $fields);
                return $this->query($sql, $values);
            }

        }
        return false;
    }

    public function delRowByCondition($table, $map)
    {
        list($condition, $values) = $this->getConditionPairFromMap($map);
        $sql = sprintf('DELETE FROM %s WHERE %s', $table, $condition);
        $stmt = $this->query($sql, $values);
        return $stmt->errorCode() == PDO::ERR_NONE;
    }

    //Modify by Jet 传ids，可以清memcache
    public function delRowByCondition2($table, $condition)
    {
        list($condition, $values) = $this->getConditionPair($condition);
        $sql = sprintf('DELETE FROM %s WHERE %s', $table, $condition);
        return $this->query($sql, $values);
    }

    public function getConditionArray($data)
    {
        if (count($data) == 0)
            return array(null, null);

        $fields = array();
        $values = array();
        foreach ($data as $k => $v) {
            $fields[] = sprintf('%s=?', $k);
            $values[] = $v;
        }

        return array(implode(',', $fields), $values);
    }

    public function getPlaceHolders($cnt)
    {
        return implode(',', array_pad(array(), $cnt, '?'));
    }

    public function truncateTable($table)
    {
        $sql = sprintf('TRUNCATE TABLE %s', $table);
        $this->query($sql);
    }

    public function query($sql, $values = null)
    {
        $stmt = $this->dbh->prepare($sql);
        $stmt->execute($values);
        if ($stmt->errorCode() != PDO::ERR_NONE) {
            if (count($values))
                $msg = sprintf('%s | (%s)', $sql, implode(',', $values));
            else
                $msg = $sql;
            Logger::ERROR($msg, __FILE__, __LINE__, 'ERROR-SQL');
            Logger::ERROR($stmt->errorInfo());
        }
        return $stmt;
    }

    public function get_var($sql, $values = null)
    {
        return $this->query($sql, $values)->fetchColumn(0);
    }

//
    public function get_row($sql, $values = null)
    {
        return $this->query($sql, $values)->fetch(PDO::FETCH_ASSOC);
    }

    public function get_col($sql, $values = null, $offset = 0)
    {
        return $this->query($sql, $values)->fetchAll(PDO::FETCH_COLUMN, $offset);
    }

    public function get_results($sql, $values = null)
    {
        return $this->query($sql, $values)->fetchAll(PDO::FETCH_ASSOC);
    }

    function escape($str)
    {
        return $this->dbh->quote($str);
    }

    public function getConditionPair($condition)
    {
        if (is_array($condition)) {
            return $condition;
        }

        if (empty($condition) || is_string($condition)) {
            return array($condition, null);
        }
    }

    protected function getConditionPairFromMap($map)
    {
        $placeHolders = array();
        $values = array();
        foreach ($map as $k => $v) {
            array_push($placeHolders, sprintf('%s=?', $k));
            array_push($values, $v);
        }

        $sql = implode(' AND ', $placeHolders);
        return array($sql, $values);
    }
}