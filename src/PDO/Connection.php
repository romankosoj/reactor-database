<?php

namespace Reactor\Database\PDO;

use Reactor\Database\Interfaces\ConnectionInterface;
use Reactor\Database\Exceptions as Exceptions;

class Connection implements ConnectionInterface {
    protected 
        $connection = null,
        $connection_string,
        $user,
        $pass;

    public function __construct($connection_string, $user = null, $pass = null) {
        $this->connection_string = $connection_string;
        $this->user = $user;
        $this->pass = $pass;
    }

    protected function getConnection() {
        if ($this->connection === null) {
            try {
                $this->connection = new \PDO($this->connection_string, $this->user, $this->pass);
                $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            } catch (\PDOException $exception) {
                throw new Exceptions\DatabaseException($exception->getMessage(), $this);
            }
        }
        return $this->connection;
    }

    public function sql($query, $arguments = array()) {
        // echo "$query ".json_encode($arguments)."<br>";
        $statement = $this->getConnection()->prepare($query);
        if (!$statement) {
            throw new Exceptions\DatabaseException($this->getConnection()->errorInfo()[2], $this);
        }
        $query = new Query($statement);
        if ($arguments === null) {
            return $query;
        }
        return $query->exec($arguments);
    }

    public function lastId($name = null) {
        return $this->getConnection()->lastInsertId($name);
    }

    protected function wrapWrere($where) {
        if (trim($where) == '') {
            return ' ';
        }
        return ' where '.$where;
    }

    public function select($table, $where_data = array(), $where = '') {
        if ($where === '') {
            $where = $this->buildPairs(array_keys($where_data), 'and');
        }
        return $this->sql('select * from `' . $table . '`'
            . $this->wrapWrere($where), $where_data);
    }

    public function insert($table, $data) {
        $keys = array_keys($data);
        $this->sql('insert into `'.$table.'`
            (`' . implode('`, `', $keys) . '`)
            values (:' . implode(', :', $keys) . ')', $data);
        return $this->lastId();
    }

    public function replace($table, $data) {   
        $keys = array_keys($data);
        $this->sql('replace into `'.$table.'`
            (`' . implode('`, `', $keys) . '`)
            values (:' . implode(', :', $keys) . ')', $data);
        return $this->lastId();
    }

    public function buildPairs($keys, $delimeter = ',') {
        $pairs = array();
        foreach ($keys as $k) {
            $pairs[] = '`' . $k . '`= :' . $k;    
        }
        return implode(' ' . $delimeter . ' ', $pairs);
    }

    public function update($table, $data, $where_data = array(), $where = '') {
        if ($where === '') {
            $where = $this->buildPairs(array_keys($where_data), 'and');
        }
        $query = $this->sql('update ' . $flags . ' `' . $table . '` set '
            . $this->buildPairs(array_keys($data)) 
            . $this->wrapWrere($where), array_merge($data, $where_data));
        return $query->rowCount();
    }

    public function delete($table, $where_data = array(), $where = '') {
        if ($where === '') {
            $where = $this->buildPairs(array_keys($where_data), 'and');
        }
        $query = $this->sql('delete from `' . $table . '` '
            . $this->wrapWrere($where), $where_data);
        return $query->rowCount();
    }

    public function pages($query, $parameters, $page, $per_page, $total_rows = null) {
        $per_page = (int)$per_page;
        $page = (int)$page;

        if($p == 0) {
            $this->sql($query, $parameters);    
        } else {

            $from = ($page - 1)  * $per_page;
            $data = $this->sql($query . ' limit ' . $from . ', ' . $per_page, $parameters);
        }

        if ($total_rows === null) {
            $cnt_query = stristr('from', $query);

            $t = strripos($cnt_query, 'order by');
            if($t !== false) {
                $cnt_query = substr($cnt_query, 0, $t);
            }

            $total_rows = $this->sql('SELECT count(*) as `count` ' . $cnt_query)->line('count');
        }

        $total_pages = ceil($total_rows / $by);
        return array(
            'data' => $data,
            'total_rows' => $total_rows,
            'total_pages' => $total_pages,
            'page' => $page,
            'per_page' => $per_page,
        );
    }

}
