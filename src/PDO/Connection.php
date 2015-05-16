<?php

namespace Reactor\Database\PDO;

use Reactor\Database\Interfaces\ConnectionInterface;
use Reactor\Database\Exceptions as Exceptions;

class Connection implements ConnectionInterface {
    protected 
        $connection = null,
        $connection_string, // connection string
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

    public function insert($table, $data, $flags = '') {
        $keys = array_keys($data);
        $this->sql('insert ' . $flags . ' into `'.$table.'`
            (`' . implode('`, `', $keys) . '`)
            values (:' . implode(', :', $keys) . ')', $data);
        return $this->lastId();
    }

    public function replace($table, $data, $flags = '') {   
        $keys = array_keys($data);
        $this->sql('replace ' . $flags . ' into `'.$table.'`
            (`' . implode('`, `', $keys) . '`)
            values (:' . implode(', :', $keys) . ')', $data);
        return $this->lastId();
    }

    public function update($table, $data, $where = '', $flags = '') {
        $query = '';
        $keys = array_keys($data);
        $pairs = array();
        foreach ($data as $k => $v) {
            $pairs[] = '`' . $k . '`= ' . $v;    
        }
        if ($where != '') {
            $where = ' where ' . $where;
        }
        $this->sql('update ' . $flags . ' `' . $table . '` set ' . implode(', ', $pairs) . $where);
        return $this->rowCount();
    }

    public function pages($query, $parameters, $page, $per_page) {
        if($p == 0) {
            $this->sql($query, $parameters);    
        } else {
            $from = ($page - 1)  * $per_page;
            $this->sql($query . ' limit ' . $from . ', ' . $per_page, $parameters);
        }

        $data = $this->matr();

        $t = stripos($query, 'from');
        $query = substr($query, $t);

        $t = strripos($query, 'order by');
        if($t !== false) {
            $query = substr($query, 0, $t);
        }

        $this->sql('SELECT count(*) as `count` ' . $query);
        $total_rows = $this->line('count');
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
