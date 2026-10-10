<?php

/*
    Thin wrapper around mysqli.

    Originally based on the "PHP5 Mysql-klasse" by Web Communication World
    (www.wccw.in, http://www.php-einfach.de/codeschnipsel_6619.php), which may be
    used, modified and redistributed freely with this notice.

    Values must never be concatenated into SQL. Pass them as parameters instead:
        $sql->query("SELECT * FROM `ipamv6` WHERE `id` = ?", array($id));
*/

class mysql
{
    private $last_result = null;
    private $conn_id = null;

    public function __construct()
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try
        {
            $this->conn_id = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int)DB_PORT);
            $this->conn_id->set_charset('utf8mb4');
        }
        catch (mysqli_sql_exception $e)
        {
            error_log('IPAMv6: database connection failed: ' . $e->getMessage());
            http_response_code(500);
            exit('Database connection failed.');
        }
    }

    /**
     * Runs a query. With $params the query is executed as prepared statement,
     * every "?" placeholder is bound to the next value of $params.
     *
     * Returns a mysqli_result for queries with a result set, true for other
     * successful queries and false on error (the error is written to the log).
     */
    public function query($sqlcode, array $params = array())
    {
        try
        {
            if (!$params)
            {
                $this->last_result = $this->conn_id->query($sqlcode);
            }
            else
            {
                $stmt = $this->conn_id->prepare($sqlcode);
                $stmt->execute(array_values($params));
                $result = $stmt->get_result();
                $this->last_result = ($result === false) ? true : $result;
                $stmt->close();
            }
        }
        catch (mysqli_sql_exception $e)
        {
            error_log('IPAMv6: query failed: ' . $e->getMessage() . ' -- SQL: ' . $sqlcode);
            $this->last_result = false;
        }

        return $this->last_result;
    }

    private function result_set($res)
    {
        if ($res === null)
            $res = $this->last_result;
        return ($res instanceof mysqli_result) ? $res : null;
    }

    public function array_result($res = null)
    {
        $res = $this->result_set($res);
        return $res ? $res->fetch_array() : null;
    }

    public function assoc_result($res = null)
    {
        $res = $this->result_set($res);
        return $res ? $res->fetch_assoc() : null;
    }

    public function num_result($res = null)
    {
        $res = $this->result_set($res);
        return $res ? $res->num_rows : 0;
    }

    public function free_result($res = null)
    {
        $res = $this->result_set($res);
        if ($res)
            $res->free();
    }

    public function insert_id()
    {
        return $this->conn_id->insert_id;
    }

    public function affected_rows()
    {
        return $this->conn_id->affected_rows;
    }

    public function close_connect()
    {
        $this->conn_id->close();
    }
}
