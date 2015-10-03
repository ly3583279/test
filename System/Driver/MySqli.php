<?php
namespace System\Driver;

/**
 * Class MySQLi
 * @package System\Driver
 */
class MySqli extends BaseDbDriver
{

    // abstract functions

    /**
     * connect: 建立数据连接
     * @param $server
     * @return \mysqli
     */
    public function connect($server)
    {
        $port = isset($server['port']) ? $server['port'] : 3306;
        $dbConnection = new \mysqli($server['host'], $server['user'], $server['password'], $server['dbname'], $port);
        if ($dbConnection->connect_error) {
            $this->log('ERROR', $dbConnection->connect_errno . '/' . $dbConnection->connect_error);

            header('HTTP/1.1 500 MySQL Server Error', true, 500);
            header('Status: 500 MySQL Server Error', true, 500);

            exit("MySQL Server Error");
        } else {
            $charset = str_replace('-', '', $server['charset']);
            if (!empty($charset)) {
                $dbConnection->set_charset($charset);
            }
        }

        return $dbConnection;
    }

    /**
     * query: 执行查询
     * @param $sql
     * @return bool
     */
    public function query($sql)
    {
        $startTime = microtime(1);

        $sql = trim($sql);
        $queryMethod = strtoupper(strstr($sql, ' ', true));
        //$connectType = strpos('/SELECT/SHOW/DESCRIBE/EXPLAIN/', $queryMethod) > 0 ? 'R' : 'RW';
        $connectType = $queryMethod == 'SELECT' ? 'R' : 'RW';

        $dbConnection = $this->open($connectType);

        $this->log('QUERY', $sql);

        $this->flush();

        $query = $dbConnection->query($sql);
        if ($query === FALSE) {
            if ($this->connectRetries < 3 && in_array($dbConnection->errno, array(2006, 2013))) {
                // retry connect
                $this->connectRetries++;
                $this->resetConnection();
                $this->log('RETRY', 'Connect Retries ' . $this->connectRetries);

                return $this->query($sql);
            }

            $this->log('ERROR', $dbConnection->errno . '/' . $dbConnection->error);

            return false;
        }

        $this->numQueries++;
        $this->connectRetries = 0;

        // select
        if ($connectType == 'R') {
            $this->numRows = $query->num_rows;
        } else {
            $this->affectedRows = $dbConnection->affected_rows;
            if (strpos('/INSERT/UPDATE/REPLACE/', $queryMethod) > 0) {
                $this->insertId = $dbConnection->insert_id;
            }
        }

        $this->log('TIME', (microtime(1) - $startTime));

        return $query;
    }

    /**
     * fetchRow
     * @param $result
     * @param string $fetchType
     * @return mixed
     */
    public function fetchRow($result, $fetchType = 'ASSOC')
    {
        if ($fetchType == 'ASSOC') {
            return $result->fetch_assoc();
        } elseif ($fetchType == 'ARRAY') {
            return $result->fetch_row();
        } elseif ($fetchType == 'OBJECT') {
            return $result->fetch_object();
        } elseif ($fetchType == 'BOTH') {
            return $result->fetch_array(MYSQLI_BOTH);
        } else {
            return $result->fetch_assoc();
        }
    }

    /**
     * escapeString
     * @param $str
     * @return string
     */
    public function escapeString($str)
    {
        $dbConnection = $this->getConnection();
        if ($dbConnection && is_resource($dbConnection)) {
            $str = $dbConnection->real_escape_string($str);
        } else {
            $str = addslashes($str);
        }
        return $str;
    }

    /**
     * close
     */
    public function close()
    {
        foreach ($this->dbConnections as $serverId => $dbConnection) {
            $dbConnection->close();
            $this->log('CLOSE', 'Close connection: ' . $serverId);
        }
    }

    // override functions

    /**
     * stat
     * @return string
     */
    public function stat()
    {
        $stat = '';
        foreach ($this->dbServers as $server) {
            $stat .= sprintf("[%s]: %s\r\n", $server['host'], $this->connect($server)->stat());
        }
        return $stat . parent::stat();
    }
}