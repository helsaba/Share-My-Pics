<?php

/*******************************************************************************
    ShareMyPics, a free twitpic clone
    Copyright (C) 2012 Jimmy Rudolf

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*******************************************************************************/

require_once 'MDB2.php';

/**
 * Manages a database
 */
class Database {
    private $handle = null;
    private $dsn = "";
    private $options = null;
    
    public function __construct($dsn, $options = null) {
        $this->dsn = $dsn;
        $this->options = $options;
    }
    
    /**
     * Opens the connection
     */
    private function getCon() {
        $con = null; 
        
        $con =& MDB2::factory($this->dsn, $this->options);
        
        if(PEAR::isError($con)) {
            die(_("An error has occured while opening database !"));
        }
        
        return $con;
    }
    
    /**
     * Builds a query to select only one row
     */
    public function selectOne($table, $fields = "*", $where = "", $order = "") {
        $list = $this->selectList($table, $fields, $where, $order, 1);
        
        if(is_array($list) && count($list) >= 1) {
            return $list[0];
        }
        
        return null;
    } 
    
    /**
     * Builds a query to return an array of objects
     */
    public function selectList($table, $fields = "*", $where = "", $order = "", $limit = "", $group = "") {
        $con = $this->getCon();
        
        $query = "SELECT " . $fields . " FROM " . $table;
        
        if($where != "") {
            $query .= " WHERE " . $where;
        }
        
        if($group != "") {
            $query .= " GROUP BY " . $group;
        }
        
        if($order != "") {
            $query .= " ORDER BY " . $order;
        }
        
        if($limit != "") {
            $query .= " LIMIT " . $limit;
        }
        
        $results =& $con->query($query);
        
        if(PEAR::isError($con)) {
            die(_("An error has occured while selecting datas !"));
        }
        
        $list = Array();
        
        while($obj = $results->fetchRow(MDB2_FETCHMODE_OBJECT)) {
            $list[] = $obj;
        }
        
        $results->free();
        
        $con->disconnect();
        
        return $list;
    } 
    
    /**
     * Inserts specified datas in the specified table
     */
    public function insert($table, $values) {
        $con = $this->getCon();
        
        $query = "INSERT INTO " . $table . " (" . implode(",", array_keys($values)) . ") VALUES ('" . implode("','", array_values($values)) . "')";
        
        $con->exec($query); 
        
        $id = $con->lastInsertId();
        
        $con->disconnect();
        
        return $id;
    }
    
    /**
     * Updates the specified record with specified datas
     */
    public function update($table, $values, $where) {
        $con = $this->getCon();
        
        $values_copy = Array();
        
        foreach($values as $key => $val) {
            $values_copy[] = $key . "='" . $val . "'";
        }
        
        $query = "UPDATE " . $table . " SET " . implode(",", $values_copy) . " WHERE " . $where;
        
        $con->exec($query); 
        
        $con->disconnect();
    }
    
    /**
     * Removes records from database
     */
    public function delete($table, $where)  {
        $con = $this->getCon();
        
        $query = "DELETE FROM " . $table . " WHERE " . $where;
        
        $con->query($query);
        
        $con->disconnect();
    }
}

?>