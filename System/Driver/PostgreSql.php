<?php
namespace System\Driver;

/**
 * Class PostgreSQL
 * @package System\Driver
 */
class PostgreSql extends BaseDbDriver
{

    /**
     * connect
     * @param $server
     * @return resource
     */
    public function connect($server)
    {
        $port = isset($server['port']) ? $server['port'] : 5432;
        $dbConnection = pg_pconnect("host={$server['host']} port={$port} dbname={$server['dbname']} user={$server['user']} password={$server['password']}");
        if ($dbConnection === false) {
            $this->log('ERROR', 'PostgreSQL Connect Error');

            header('HTTP/1.1 500 PostgreSQL Server Error', true, 500);
            header('Status: 500 PostgreSQL Server Error', true, 500);

            exit("PostgreSQL Server Error");
        } else {
            $charset = str_replace('-', '', $server['charset']);
            if (!empty($charset)) {
                pg_set_client_encoding($dbConnection, $charset);
            }
        }

        return $dbConnection;
    }

    /**
     * query
     * @param $sql
     * @return bool|resource
     */
    public function query($sql)
    {
        $startTime = microtime(1);

        $sql = trim($sql);
        $queryMethod = strtoupper(strstr($sql, ' ', true));
        $connectType = $queryMethod == 'SELECT' ? 'R' : 'RW';

        $dbConnection = $this->open($connectType);

        $this->log('QUERY', $sql);

        $this->flush();

        $query = pg_query($dbConnection, $sql);
        if ($query === FALSE) {
            if ($this->connectRetries < 3 && pg_connection_status($dbConnection) === PGSQL_CONNECTION_BAD) {
                // retry connect
                $this->connectRetries++;
                $this->resetConnection();
                $this->log('RETRY', 'Connect Retries ' . $this->connectRetries);

                return $this->query($sql);
            }

            $this->log('ERROR', pg_last_error($dbConnection));

            return false;
        }

        $this->numQueries++;
        $this->connectRetries = 0;

        // select
        if ($connectType == 'R') {
            $this->numRows = pg_num_rows($query);
        } else {
            $this->affectedRows = pg_affected_rows($dbConnection);
            if (strpos('/INSERT/UPDATE/REPLACE/', $queryMethod) > 0) {
                $insertQuery = pg_query($dbConnection, "SELECT lastval();");
                $insertRow = pg_fetch_row($insertQuery);
                $this->insertId = $insertRow[0];
            }
        }

        $this->log('TIME', (microtime(1) - $startTime));

        return $query;
    }

    /**
     * fetchRow
     * @param $result
     * @param string $fetchType
     * @return array|object
     */
    public function fetchRow($result, $fetchType = 'ASSOC')
    {
        if ($fetchType == 'ASSOC') {
            return pg_fetch_assoc($result);
        } elseif ($fetchType == 'ARRAY') {
            return pg_fetch_array($result);
        } elseif ($fetchType == 'OBJECT') {
            return pg_fetch_object($result);
        } elseif ($fetchType == 'BOTH') {
            return pg_fetch_array($result, null, PGSQL_BOTH);
        } else {
            return pg_fetch_assoc($result);
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
            $str = pg_escape_string($dbConnection, $str);
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
            pg_close($dbConnection);
            $this->log('CLOSE', 'Close connection: ' . $serverId);
        }
    }
}