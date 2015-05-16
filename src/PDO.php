<?php

namespace Reactor\Database;

class PDO {
    protected 
        $connection = null,
        $statement = null,
        $query = null,
        $link,
        $user,
        $pass;

    public function __construct($link, $user = null, $pass = null) {
        $this->link = $link;
        $this->user = $user;
        $this->pass = $pass;
    }

    protected function getConnection() {
        if ($this->connection !== null) {
            return $this->connection;
        }
        $this->connection = new \PDO($this->link, $this->user, $this->pass);
        return $this->connection;
    }

    public function sql($query, $parameters = array()) {
        $this->query = array(
            'sql' => $query
        );
        $this->free();
        $this->statement = $this->getConnection()->prepare($query);
        if (!$this->statement) {
            $this->query['error'] = $this->getConnection()->errorInfo()[2];
            return false;
        }
        $this->exec($parameters);
        return $this;
    }

    public function exec($parameters = array()) {
        $this->query['parameters'] = $parameters;
        $execution_time = microtime(true);
        $is_good = $this->statement->execute($parameters);
        $this->query['execution_time'] = microtime(true) - $execution_time;
        if (!$is_good) {
            $this->query['error'] = $this->statement->errorInfo()[2];
            return false;
        }
        return $this;
    }

    public function line($row = '*') {
        if (!$this->statement) {
            return null;
        }

        $line = $this->statement->fetch(PDO::FETCH_ASSOC);
        if ($line) {
            if ($row == '*') {
                return $line;
            }
            return $line[$row];
        }

        return null;
    }

    public function free() {
        if ($this->statement !== null) {
            $this->statement->closeCursor();
            unset($this->statement);
            $this->statement = null;
        }
    }

    public function matr($key = null, $row = '*') {
        $data = array();
        if ($key === null) {
            if ($row == '*') {
                $data = $this->statement->fetchAll();
            } else {
                while ($line = $this->statement->fetch(PDO::FETCH_ASSOC)) {
                    $data[] = $line[$row];
                }
            }
        } else {
            if ($row == '*') {
                while ($line = $this->statement->fetch(PDO::FETCH_ASSOC)) {
                    $data[$line[$key]] = $line;
                }
            } else {
                while ($line = $this->statement->fetch(PDO::FETCH_ASSOC)) {
                    $data[$line[$key]] = $line[$row];
                }
            }
        }

        return $data;
    }

    public function lastId($name = null) {
        return $this->getConnection()->lastInsertId($name);
    }

    public function rowCount() {
        return $this->statement->rowCount();
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
