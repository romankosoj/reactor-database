<?php

namespace Reactor\Database\Interfaces;

interface ConnectionInterface {

    public function sql($query);
    public function lastId($name = null);
    public function insert($table, $data, $flags = '');
    public function replace($table, $data, $flags = '');
    public function update($table, $data, $where = '', $flags = '');
    public function pages($query, $parameters, $page, $per_page);

}
