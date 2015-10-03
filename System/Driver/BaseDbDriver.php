<?php
namespace System\Driver;

/**
 * Class BaseDbDriver
 * @package System\Driver
 */
abstract class BaseDbDriver
{
    public $insertId = 0;
    public $affectedRows = 0;
    public $numRows = 0;

    protected $dbCache;
    protected $dbDriver = 'BaseDbDriver';
    protected $dbServers;
    protected $dbMasterCount = 1;
    protected $dbConnections = array();
    protected $dbServerId = -1;
    protected $dbServerMasterId = -1;
    protected $dbServerSlaveId = -1;
    protected $dbServerCurrentId = -1;

    protected $tablePrefix = 'tb_';
    protected $isReplication = false;
    protected $connectRetries = 0;
    protected $numQueries = 0;
    protected $logEnabled = false;

    private $defaultParams = array(
        'fields' => '*',
        'orderBy' => '',
        'groupBy' => '',
        'rowStart' => '0',
        'rowCount' => '100',
        'rowIndex' => 0,
        'columnIndex' => 0,
        'indexField' => '',
        'valueField' => '',
        'sqlOrderBy' => '',
        'sqlGroupBy' => ''
    );

    /**
     * __construct
     * @param null $config
     * @param null $cache
     */
    public function __construct($config = null, &$cache = null)
    {
        $this->dbCache =& $cache;
        $this->dbDriver = $config['driver'];
        $this->dbServers = $config['servers'];
        $this->dbMasterCount = $config['master_count'];
        $this->tablePrefix = $config['servers'][0]['table_pre'];
        $this->isReplication = count($config['servers']) > 1;
        $this->logEnabled = $config['log_enabled'];
    }

    /**
     * open
     * @param string $connectType 数据库连接模式，RW：读写；R：读；W：写
     * @return mixed
     */
    public function open($connectType = 'RW')
    {
        $serverId = $this->getServerId($connectType);

        // 打开数据库链接
        if (!$this->dbConnections[$serverId]) {
            $this->log('OPEN', 'Open ' . $this->dbDriver . ': ' . $serverId);
            $this->dbConnections[$serverId] = $this->connect($this->dbServers[$serverId]);
        }

        $this->dbServerCurrentId = $serverId;

        return $this->dbConnections[$serverId];
    }

    /**
     * setServerId
     * @param string $serverId
     * @return int|string
     */
    public function setServerId($serverId = 'RW')
    {
        if (is_numeric($serverId)) {
            $this->dbServerId = $serverId;
        } else {
            $this->dbServerId = $this->getServerId($serverId);
        }
        return $this->dbServerId;
    }

    /**
     * getServerId
     * @param string $connectType
     * @return int
     */
    public function getServerId($connectType = 'RW')
    {
        $serverId = $this->dbServerId;
        if ($serverId < 0) {
            if ($this->isReplication && $connectType == 'R') { // 读服务器
                if ($this->dbServerSlaveId < 0) {
                    $this->dbServerSlaveId = rand($this->dbMasterCount, count($this->dbServers) - 1);
                }
                $serverId = $this->dbServerSlaveId;
            } else { // 读写服务器
                if ($this->dbServerMasterId < 0) {
                    $this->dbServerMasterId = $this->dbMasterCount > 1 ? rand(0, $this->dbMasterCount - 1) : 0;
                }
                $serverId = $this->dbServerMasterId;
            }
        }
        return $serverId;
    }

    /**
     * getConnection
     * @param int $serverId
     * @return null
     */
    protected function getConnection($serverId = -1)
    {
        if ($serverId < 0) {
            $serverId = $this->dbServerCurrentId;
        }
        return array_key_exists($serverId, $this->dbConnections) ? $this->dbConnections[$serverId] : null;
    }

    /**
     * resetConnection
     * @param int $serverId
     */
    protected function resetConnection($serverId = -1)
    {
        if ($serverId < 0) {
            $serverId = $this->dbServerCurrentId;
        }
        if (array_key_exists($serverId, $this->dbConnections)) {
            unset($this->dbConnections[$serverId]);
        }
    }

    /**
     * log
     * @param $tag
     * @param $msg
     * @return bool
     */
    protected function log($tag, $msg)
    {
        if ($this->logEnabled) {
            return error_log("[{$tag}]: {$msg}", 0);
        }
    }

    // public abstract functions

    public abstract function connect($server);

    public abstract function query($sql);

    public abstract function fetchRow($result, $fetchType = 'ASSOC');

    public abstract function escapeString($str);

    public abstract function close();

    // public functions

    /**
     * insert
     * @param $tableName
     * @param $dataSet
     * @param bool $multiRows
     * @return int
     */
    public function insert($tableName, $dataSet, $multiRows = false)
    {
        $tableName = $this->getSqlTable($tableName);

        if ($multiRows) {
            $fields = array_keys($dataSet[0]);
            //$fields = $this->escape($fields);

            $values = array();
            foreach ($dataSet as $arr) {
                $arr = $this->escape($arr);
                $values[] = "('" . implode("','", $arr) . "')";
            }
        } else {
            $fields = array_keys($dataSet);
            //$fields = $this->escape($fields);

            $dataSet = $this->escape($dataSet);
            $values = array("('" . implode("','", $dataSet) . "')");
        }

        $returnValue = $this->query("INSERT INTO {$tableName} (`" . implode('`,`', $fields) . "`) VALUES " . implode(',', $values));

        if ($returnValue && $this->insertId) {
            return $this->insertId;
        } else {
            return $returnValue;
        }
    }

    /**
     * update
     * @param $tableName
     * @param $dataSet
     * @param $where
     * @param bool $multiRows
     * @return mixed
     */
    public function update($tableName, $dataSet, $where, $multiRows = false)
    {
        $tableName = $this->getSqlTable($tableName);

        if ($multiRows) {
            $values = array();

            $caseKey = key($where);
            $keysArr = array_keys($dataSet[0]);

            foreach ($keysArr as $k) {
                $values[$k] = $k . '=CASE ' . $caseKey;
            }

            foreach ($dataSet as $i => $arr) {
                $arr = $this->escape($arr);
                foreach ($arr as $k => $v) {
                    $values[$k] .= ' WHEN \'' . $where[$caseKey][$i] . '\' THEN \'' . $v . '\'';
                }
            }

            foreach ($keysArr as $k) {
                $values[$k] .= ' END';
            }

            $strValues = implode(', ', $values);
        } else {
            if (is_array($dataSet)) {
                $values = array();
                foreach ($dataSet as $k => $v) {
                    if (is_numeric($k)) {
                        $values[] = $v;
                    } else {
                        //$k = $this->escape($k);
                        $values[] = "`{$k}`='" . $this->escape($v) . "'";
                    }
                }
                $strValues = implode(', ', $values);
                unset($values);
            } else {
                $strValues = $dataSet;
            }
        }

        $sqlWhere = $this->getSqlWhere($where);

        $returnValue = $this->query("UPDATE {$tableName} SET " . $strValues . $sqlWhere);

        if ($returnValue && $this->affectedRows) {
            return $this->affectedRows;
        } else {
            return $returnValue;
        }
    }

    /**
     * replace
     * @param $tableName
     * @param $dataSet
     * @param bool $multiRows
     * @return int
     */
    public function replace($tableName, $dataSet, $multiRows = false)
    {
        $tableName = $this->getSqlTable($tableName);

        if ($multiRows) {
            $fields = array_keys($dataSet[0]);
            //$fields = $this->escape($fields);

            $values = array();
            foreach ($dataSet as $arr) {
                $arr = $this->escape($arr);
                $values[] = "('" . implode("','", $arr) . "')";
            }
        } else {
            $fields = array_keys($dataSet);
            //$fields = $this->escape($fields);

            $dataSet = $this->escape($dataSet);
            $values = array("('" . implode("','", $dataSet) . "')");
        }

        $returnValue = $this->query("REPLACE INTO {$tableName} (`" . implode('`,`', $fields) . "`) VALUES " . implode(',', $values));

        if ($returnValue && $this->insertId) {
            return $this->insertId;
        } else {
            return $returnValue;
        }
    }

    /**
     * delete
     * @param $tableName
     * @param $where
     * @return mixed
     */
    public function delete($tableName, $where)
    {
        $tableName = $this->getSqlTable($tableName);
        $sqlWhere = $this->getSqlWhere($where);

        $returnValue = $this->query("DELETE FROM " . $tableName . $sqlWhere);

        if ($returnValue && $this->affectedRows) {
            return $this->affectedRows;
        } else {
            return $returnValue;
        }
    }

    /**
     * @param $tableName
     * @param null $where
     * @param null $params [fields/orderBy/groupBy/rowStart/rowCount/indexField]
     * @param int $expires [default: 0; if > 0, cache enabled]
     * @param null $callback
     * @return array|null
     */
    public function getRows($tableName, $where = null, $params = null, $expires = 0, $callback = null)
    {
        $cacheKey = $cacheData = null;
        if ($expires && $this->dbCache != null) {
            $cacheKey = is_array($params) && isset($params['cacheKey']) ? $params['cacheKey'] : null;
            if (empty($cacheKey)) {
                $cacheKey = $this->getSqlCacheKey('rows', $tableName, $where, $params);
            }

            if ($expires < 0) {
                $this->dbCache->del($cacheKey);
                if ($expires == -1) {
                    return null;
                }
                $expires = $expires * -1;
            }

            $cacheData = $this->dbCache->get($cacheKey);
        } else {
            $expires = 0;
        }

        if (empty($cacheData)) {
            $fields = $sqlOrderBy = $sqlGroupBy = $rowStart = $rowCount = $indexField = null;
            $tableName = $this->getSqlTable($tableName);
            $sqlWhere = $this->getSqlWhere($where);
            extract($this->getSqlParams($params));

            $cacheData = $this->queryRows("SELECT {$fields} FROM {$tableName} {$sqlWhere} {$sqlOrderBy} {$sqlGroupBy} LIMIT {$rowStart},{$rowCount};", $indexField, $callback);

            if ($expires && !empty($cacheData)) {
                $this->dbCache->set($cacheKey, $cacheData, $expires);
            }
        }

        return $cacheData;
    }

    /**
     * getRow
     * @param $tableName
     * @param null $where
     * @param null $params [fields/orderBy/groupBy/rowStart/rowCount/rowIndex]
     * @param int $expires [default: 0; if > 0, cache enabled]
     * @param null $callback
     * @return mixed
     */
    public function getRow($tableName, $where = null, $params = null, $expires = 0, $callback = null)
    {
        $cacheKey = $cacheData = null;
        if ($expires && $this->dbCache != null) {
            $cacheKey = is_array($params) && isset($params['cacheKey']) ? $params['cacheKey'] : null;
            if (empty($cacheKey)) {
                $cacheKey = $this->getSqlCacheKey('row', $tableName, $where, $params);
            }

            if ($expires < 0) {
                $this->dbCache->del($cacheKey);
                if ($expires == -1) {
                    return null;
                }
                $expires = $expires * -1;
            }

            $cacheData = $this->dbCache->get($cacheKey);
        } else {
            $expires = 0;
        }

        if (empty($cacheData)) {
            $fields = $sqlOrderBy = $sqlGroupBy = $rowStart = $rowCount = $rowIndex = null;
            $tableName = $this->getSqlTable($tableName);
            $sqlWhere = $this->getSqlWhere($where);
            extract($this->getSqlParams($params));

            $cacheData = $this->queryRow("SELECT {$fields} FROM {$tableName} {$sqlWhere} {$sqlOrderBy} {$sqlGroupBy} LIMIT {$rowStart},{$rowCount};", $rowIndex, $callback);

            if ($expires && !empty($cacheData)) {
                $this->dbCache->set($cacheKey, $cacheData, $expires);
            }
        }

        return $cacheData;
    }

    /**
     * getScalars
     * @param $tableName
     * @param null $where
     * @param null $params [indexField/valueField/orderBy/groupBy/rowStart/rowCount]
     * @param int $expires [default: 0; if > 0, cache enabled]
     * @param null $callback
     * @return mixed
     */
    public function getScalars($tableName, $where = null, $params = null, $expires = 0, $callback = null)
    {
        $cacheKey = $cacheData = null;
        if ($expires && $this->dbCache != null) {
            $cacheKey = is_array($params) && isset($params['cacheKey']) ? $params['cacheKey'] : null;
            if (empty($cacheKey)) {
                $cacheKey = $this->getSqlCacheKey('scalars', $tableName, $where, $params);
            }

            if ($expires < 0) {
                $this->dbCache->del($cacheKey);
                if ($expires == -1) {
                    return null;
                }
                $expires = $expires * -1;
            }

            $cacheData = $this->dbCache->get($cacheKey);
        } else {
            $expires = 0;
        }

        if (empty($cacheData)) {
            $fields = $indexField = $valueField = $sqlOrderBy = $sqlGroupBy = $rowStart = $rowCount = null;
            $tableName = $this->getSqlTable($tableName);
            $sqlWhere = $this->getSqlWhere($where);
            extract($this->getSqlParams($params));

            if (empty($indexField) && empty($valueField)) {
                $fieldArr = empty($fields) ? null : explode(',', $fields);
                if (count($fieldArr) > 1) {
                    $indexField = trim($fieldArr[0]);
                    $valueField = trim($fieldArr[1]);
                } else {
                    $valueField = trim($fieldArr[0]);
                }
            } elseif (empty($indexField) || $indexField == $valueField) {
                $fields = $valueField;
            } else {
                $fields = $indexField . ',' . $valueField;
            }

            $cacheData = $this->queryScalars("SELECT {$fields} FROM {$tableName} {$sqlWhere} {$sqlOrderBy} {$sqlGroupBy} LIMIT {$rowStart},{$rowCount};", $indexField, $valueField, $callback);

            if ($expires && !empty($cacheData)) {
                $this->dbCache->set($cacheKey, $cacheData, $expires);
            }
        }

        return $cacheData;
    }

    /**
     * getScalar
     * @param $tableName
     * @param null $where
     * @param null $params [fields/orderBy/groupBy/rowStart/rowCount/columnIndex/rowIndex]
     * @param int $expires [default: 0; if > 0, cache enabled]
     * @param null $callback
     * @return mixed
     */
    public function getScalar($tableName, $where = null, $params = null, $expires = 0, $callback = null)
    {
        $cacheKey = $cacheData = null;
        if ($expires && $this->dbCache != null) {
            $cacheKey = is_array($params) && isset($params['cacheKey']) ? $params['cacheKey'] : null;
            if (empty($cacheKey)) {
                $cacheKey = $this->getSqlCacheKey('scalar', $tableName, $where, $params);
            }

            if ($expires < 0) {
                $this->dbCache->del($cacheKey);
                if ($expires == -1) {
                    return null;
                }
                $expires = $expires * -1;
            }

            $cacheData = $this->dbCache->get($cacheKey);
        } else {
            $expires = 0;
        }

        if (!isset($cacheData)) {
            $fields = $sqlOrderBy = $sqlGroupBy = $rowStart = $rowCount = $columnIndex = $rowIndex = null;
            $tableName = $this->getSqlTable($tableName);
            $sqlWhere = $this->getSqlWhere($where);
            extract($this->getSqlParams($params));

            $cacheData = $this->queryScalar("SELECT {$fields} FROM {$tableName} {$sqlWhere} {$sqlOrderBy} {$sqlGroupBy} LIMIT {$rowStart},{$rowCount};", $columnIndex, $rowIndex, $callback);

            if ($expires && isset($cacheData)) {
                $this->dbCache->set($cacheKey, $cacheData, $expires);
            }
        }

        return $cacheData;
    }

    /**
     * getCount
     * @param $tableName
     * @param null $where
     * @param int $expires [default: 0; if > 0, cache enabled]
     * @param null $cacheKey
     * @return mixed
     */
    public function getCount($tableName, $where = null, $expires = 0, $cacheKey = null)
    {
        if ($expires > 0 && $cacheKey) {
            $params = array('fields' => 'count(*) as c', 'cacheKey' => $cacheKey);
        } else {
            $params = 'count(*) as c';
        }
        return $this->getScalar($tableName, $where, $params, $expires);
    }

    // common functions

    /**
     * getSqlTable
     * @param $tableName
     * @return string
     */
    public function getSqlTable($tableName)
    {
        return strpos($tableName, ' ') === FALSE ? ($this->tablePrefix . $tableName) : $tableName;
    }

    /**
     * getSqlWhere
     * @param $where
     * @param string $glue
     * @return string
     */
    public function getSqlWhere($where, $glue = 'AND')
    {
        if (empty($where)) {
            $strWhere = '';
        } else {
            if (is_array($where)) {
                $wheres = array();
                foreach ($where as $k => $v) {
                    if (is_numeric($k)) { // SQL语句
                        $wheres[] = $v;
                    } elseif ($k == 'AND' || $k == 'OR') { // AND/OR
                        $sqlWhere = $this->getSqlWhere($v, $k);
                        if (!empty($sqlWhere)) {
                            $wheres[] = '(' . substr($sqlWhere, 7) . ')';
                        }
                    } else { // ARRAY
                        if (is_array($v)) { // IN
                            $v = $this->escape($v);
                            $wheres[] = "`{$k}` IN ('" . implode("','", $v) . "')";
                        } else {
                            $wheres[] = "`{$k}`='" . $this->escape($v) . "'";
                        }
                    }
                }
                $strWhere = implode(' ' . $glue . ' ', $wheres);
            } else {
                $strWhere = $where;
            }
        }

        $strWhere = trim($strWhere);

        return empty($strWhere) ? '' : ' WHERE ' . $strWhere;
    }

    /**
     * getSqlParams
     * @param $params
     * @return array
     */
    public function getSqlParams($params)
    {
        $defaultParams = $this->defaultParams;

        if (empty($params)) {
            return $defaultParams;
        }

        if (is_string($params)) {
            $defaultParams['fields'] = $params;
            return $defaultParams;
        }

        if (isset($params['orderBy']) && !empty($params['orderBy'])) {
            $params['sqlOrderBy'] = 'ORDER BY ' . $params['orderBy'];
        }

        if (isset($params['groupBy']) && !empty($params['groupBy'])) {
            $params['sqlGroupBy'] = 'GROUP BY ' . $params['groupBy'];
        }

        return array_merge($defaultParams, $params);
    }

    /**
     * getSqlCacheKey
     * @param string $queryType
     * @param $tableName
     * @param $where
     * @param $params
     * @return string
     */
    public function getSqlCacheKey($queryType = 'rows', $tableName, $where, $params)
    {
        return $this->dbCache->generateCacheKey($this->dbDriver, $queryType, $tableName, $where, $params);
    }

    // util functions

    /**
     * flush
     */
    public function flush()
    {
        $this->insertId = 0;
        $this->affectedRows = 0;
        $this->numRows = 0;
    }

    /**
     * escape
     * @param $var
     * @param bool $escapeLikeWildcards
     * @return array|mixed|string
     */
    public function escape($var, $escapeLikeWildcards = false)
    {
        if (is_array($var)) {
            foreach ($var as $key => $val) {
                $var[$key] = $this->escape($val, $escapeLikeWildcards);
            }
            return $var;
        }

        if (is_numeric($var) || is_bool($var)) {
            return $var;
        }

        if (is_null($var)) {
            return '';
        }

        $var = $this->escapeString($var);

        // escape LIKE condition wildcards
        if ($escapeLikeWildcards) {
            $var = str_replace(array('%', '_'), array('\\%', '\\_'), $var);
        }

        return $var;
    }

    /**
     * execute
     * @param $sql
     * @return mixed
     */
    public function execute($sql)
    {
        // insert|delete|update|replace|truncate|drop|create|alter|begin|commit|rollback|set|lock|unlock|call
        return $this->query($sql);
    }

    /**
     * stat
     * @return string
     */
    public function stat()
    {
        return sprintf("Servers: %s Queries: %s Retries: %s ", count($this->dbServers), $this->numQueries, $this->connectRetries);
    }

    // private functions

    /**
     * queryRows
     * @param $sql
     * @param null $indexField
     * @param null $callback
     * @return array|null
     */
    private function queryRows($sql, $indexField = null, $callback = null)
    {
        $rows = null;
        if ($result = $this->query($sql)) {
            $rows = array();
            while ($row = $this->fetchRow($result, 'ASSOC')) {
                if ($callback) {
                    $row = call_user_func($callback, $row);
                    //$row = call_user_func_array($callback, array($row));
                }
                empty($indexField) ? ($rows[] = $row) : ($rows[$row[$indexField]] = $row);
            }
            $result->close();
        }

        return $rows;
    }

    /**
     * queryRow
     * @param $sql
     * @param int $y
     * @param null $callback
     * @return mixed|null
     */
    private function queryRow($sql, $y = 0, $callback = null)
    {
        $row = null;
        if ($result = $this->query($sql)) {
            if ($y > 0) {
                $y1 = 0;
                while ($row = $this->fetchRow($result, 'ASSOC')) {
                    if ($y1 === $y) {
                        break;
                    }
                    $y1++;
                }
            } else {
                $row = $this->fetchRow($result, 'ASSOC');
            }
            $result->close();
        }

        if ($callback) {
            return call_user_func($callback, $row);
        }

        return $row;
    }

    /**
     * queryScalars
     * @param $sql
     * @param null $indexField
     * @param null $valueField
     * @param null $callback
     * @return array|null
     */
    private function queryScalars($sql, $indexField = null, $valueField = null, $callback = null)
    {
        $rows = null;
        if ($result = $this->query($sql)) {
            $rows = array();
            while ($row = $this->fetchRow($result, 'ASSOC')) {
                if ($callback) {
                    $row = call_user_func($callback, $row);
                }

                if (empty($indexField)) {
                    $rows[] = $row[$valueField];
                } else {
                    $rows[$row[$indexField]] = $row[$valueField];
                }
            }
            $result->close();
        }

        return $rows;
    }

    /**
     * queryScalar
     * @param $sql
     * @param int $x
     * @param int $y
     * @param null $callback
     * @return mixed|null
     */
    private function queryScalar($sql, $x = 0, $y = 0, $callback = null)
    {
        $row = null;
        if ($result = $this->query($sql)) {
            if ($y > 0) {
                $y1 = 0;
                while ($row = $this->fetchRow($result, 'ARRAY')) {
                    if ($y1 === $y) {
                        break;
                    }
                    $y1++;
                }
            } else {
                $row = $this->fetchRow($result, 'ARRAY');
            }
            $result->close();
        }

        $returnVal = $row ? $row[$x] : null;

        if ($callback) {
            return call_user_func($callback, $returnVal);
        }

        return $returnVal;
    }
}