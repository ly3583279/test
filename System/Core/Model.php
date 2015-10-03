<?php
namespace System\Core;

/**
 * Class Model
 * @package System\Core
 */
abstract class Model
{
    public $db;
    public $tableName = '';
    public $primaryKeyName = 'id';

    protected $modelData;
    protected $extraModel;
    protected $statusMessages;

    /**
     * __construct
     * @param null $primaryKeyValue
     * @param int $expires
     */
    public function __construct($primaryKeyValue = null, $expires = 0)
    {
        $this->db = App::db();

        $this->setPrimaryKey($primaryKeyValue);

        if (!empty($primaryKeyValue)) {
            $this->bind(null, null, $expires);
        }
    }

    /**
     * getTableName
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * setTableName
     * @param string $tableName
     */
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * getPrimaryKeyName
     * @return string
     */
    public function getPrimaryKeyName()
    {
        return $this->primaryKeyName;
    }

    /**
     * setPrimaryKeyName
     * @param string $primaryKeyName [id/aid,uid]
     */
    public function setPrimaryKeyName($primaryKeyName)
    {
        $this->primaryKeyName = $primaryKeyName;
    }

    /**
     * getPrimaryKey
     * @param bool $returnArr
     * @return array|string
     */
    public function getPrimaryKey($returnArr = false)
    {
        $primaryKeyName = trim($this->primaryKeyName);
        if (strpos($primaryKeyName, ',') === false) { // 单列PrimaryKey
            return $returnArr ? array($primaryKeyName => $this->$primaryKeyName) : $this->$primaryKeyName;
        } else { // 多列PrimaryKey
            $ns = explode(',', $primaryKeyName);
            $row = array();
            foreach ($ns as $k => $varName) {
                $varName = trim($varName);
                $row[$varName] = $this->$varName;
            }
            return $row;
        }
    }

    /**
     * setPrimaryKey
     * @param $params
     */
    public function setPrimaryKey($params)
    {
        if ($params === null) {
            return;
        }

        $primaryKeyName = trim($this->primaryKeyName);
        if (strpos($primaryKeyName, ',') === false) { // 单列PrimaryKey
            $this->$primaryKeyName = is_array($params) ? $params[0] : $params;
        } else { // 多列PrimaryKey
            $ns = explode(',', $primaryKeyName);
            $args = is_array($params) ? $params : func_get_args();
            foreach ($ns as $k => $varName) {
                $varName = trim($varName);
                $this->$varName = isset($args[$varName]) ? $args[$varName] : $args[$k];
            }
        }
    }

    /**
     * getData
     * @param null $name
     * @return null
     */
    public function getData($name = null)
    {
        if ($name === null) {
            return $this->modelData;
        } else {
            return is_array($this->modelData) && isset($this->modelData[$name]) ? $this->modelData[$name] : null;
        }
    }

    /**
     * bind
     * @param null $args
     * @param null $params
     * @param int $expires
     * @return $this
     */
    public function bind($args = null, $params = null, $expires = 0)
    {
        if (is_array($args)) {
            $this->modelData = $args;
        } else {
            $this->modelData = $this->getRow($args, $params, $expires);
        }

        return $this;
    }

    /**
     * flush
     */
    public function flush()
    {
        unset($this->modelData);
    }

    /**
     * formatRow
     * @param $row
     * @param null|string|array $params
     * @return mixed
     */
    public function formatRow($row, $params = null)
    {
        return $row;
    }

    /**
     * getFetchFields
     * @param string $fetchType
     * @return string
     */
    public function getFetchFields($fetchType = '*')
    {
        return '*';
    }

    /**
     * getStatusMessage
     * @param int|string $status
     * @return array|null
     */
    public function getStatusMessage($status = null)
    {
        return is_array($this->statusMessages) && array_key_exists($status, $this->statusMessages)
            ? $this->statusMessages[$status] : $this->statusMessages;
    }

    // CURD functions

    /**
     * create
     * @param array $dataSet
     * @param bool|false $multiRows
     * @return int
     */
    public function create($dataSet = null, $multiRows = false)
    {
        $dataSet = $this->getObjectVars($dataSet);
        return $this->db->insert($this->tableName, $dataSet, $multiRows);
    }

    /**
     * update
     * @param array $dataSet
     * @param array $where
     * @param bool|false $multiRows
     * @return mixed
     */
    public function update($dataSet = null, $where = null, $multiRows = false)
    {
        $dataSet = $this->getObjectVars($dataSet);
        $where = $this->getWhere($where);
        return $this->db->update($this->tableName, $dataSet, $where, $multiRows);
    }

    /**
     * replace
     * @param array $dataSet
     * @param bool|false $multiRows
     * @return int
     */
    public function replace($dataSet = null, $multiRows = false)
    {
        $dataSet = $this->getObjectVars($dataSet);
        return $this->db->replace($this->tableName, $dataSet, $multiRows);
    }

    /**
     * delete
     * @param string|array $where
     * @return mixed
     */
    public function delete($where = null)
    {
        $where = $this->getWhere($where);
        return $this->db->delete($this->tableName, $where);
    }

    // query functions

    /**
     * getRow
     * @param $where
     * @param string|array $params [fields/orderBy/groupBy/rowStart/rowCount/rowIndex]
     * @param int $expires [default: 0; if > 0, cache enabled]
     * @param callback $callback
     * @return mixed
     */
    public function getRow($where, $params = null, $expires = 0, $callback = null)
    {
        $where = $this->getWhere($where);
        if ($callback === null) {
            return $this->formatRow($this->db->getRow($this->tableName, $where, $params, $expires, $callback), $params);
        } else {
            return $this->db->getRow($this->tableName, $where, $params, $expires, $callback);
        }
    }

    /**
     * getRows
     * @param $where
     * @param string|array $params [fields/orderBy/groupBy/rowStart/rowCount/indexField]
     * @param int $expires [default: 0; if > 0, cache enabled]
     * @param callback|boolean $callback 当$callback=false时，返回原生row
     * @return array|null
     */
    public function getRows($where, $params = null, $expires = 0, $callback = null)
    {
        $where = $this->getWhere($where);
        if ($callback === null) {
            $callback = function ($row) use ($params) {
                return $this->formatRow($row, $params);
            };
        }
        return $this->db->getRows($this->tableName, $where, $params, $expires, $callback);
    }

    /**
     * getScalars
     * @param $where
     * @param string|array $params [indexField/valueField/orderBy/groupBy/rowStart/rowCount]
     * @param int $expires [default: 0; if > 0, cache enabled]
     * @param callback $callback
     * @return mixed
     */
    public function getScalars($where, $params = null, $expires = 0, $callback = null)
    {
        $where = $this->getWhere($where);
        return $this->db->getScalars($this->tableName, $where, $params, $expires, $callback);
    }

    /**
     * getScalar
     * @param $where
     * @param string|array $params [fields/orderBy/groupBy/rowStart/rowCount/columnIndex/rowIndex]
     * @param int $expires [default: 0; if > 0, cache enabled]
     * @param callback $callback
     * @return mixed
     */
    public function getScalar($where, $params = null, $expires = 0, $callback = null)
    {
        $where = $this->getWhere($where);
        return $this->db->getScalar($this->tableName, $where, $params, $expires, $callback);
    }

    /**
     * getCount
     * @param $where
     * @param int $expires
     * @return mixed|null
     */
    public function getCount($where, $expires = 0)
    {
        $where = $this->getWhere($where);
        return $this->db->getCount($this->tableName, $where, $expires);
    }

    // protected functions

    /**
     * getObjectVars
     * @param $vars
     * @return mixed
     */
    protected function getObjectVars($vars)
    {
        if ($vars === null) {
            $vars = $this->modelData;
        }

        return $vars;
    }

    /**
     * getWhere
     * @param $where
     * @return array|string
     */
    protected function getWhere($where)
    {
        if ($where === null) {
            $where = $this->getPrimaryKey(true);
        } else {
            if (empty($where)) {
                $where = '';
            } elseif (is_numeric($where)) {
                $where = array($this->primaryKeyName => $where);
            } elseif (is_string($where)) {
                if (preg_match("/\W/", $where)) { // 含有非字符类的字符串，理解为sql语句
                    $where = trim($where);
                } else {
                    $where = array($this->primaryKeyName => $where);
                }
            }
        }

        return $where;
    }

    // extra model methods

    /**
     * getExtraModel
     * @param null $tableName
     * @param string $primaryKeyName
     * @return stdModel
     */
    public function getExtraModel($tableName = null, $primaryKeyName = 'id')
    {
        if ($this->extraModel === null) {
            if (empty($tableName)) {
                $tableName = $this->tableName . '_extra';
            }
            $this->extraModel = new stdModel($tableName, $primaryKeyName);
        }
        return $this->extraModel;
    }

    // magic methods

    /**
     * __get
     * @param $name
     * @return null
     */
    public function __get($name)
    {
        if (is_array($this->modelData) && array_key_exists($name, $this->modelData)) {
            return $this->modelData[$name];
        }

        return null;
    }

    /**
     * __set
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->modelData[$name] = $value;
    }

    /**
     * __isset
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->modelData[$name]);
    }

    /**
     * __unset
     * @param string $name
     */
    public function __unset($name)
    {
        unset($this->modelData[$name]);
    }
}

/**
 * Class stdModel
 * @package System\Core
 */
class stdModel extends Model
{
    public function __construct($tableName, $primaryKeyName = 'id', $primaryKeyValue = null, $expires = 0)
    {
        $this->tableName = $tableName;
        $this->primaryKeyName = $primaryKeyName;
        parent::__construct($primaryKeyValue, $expires);
    }
}