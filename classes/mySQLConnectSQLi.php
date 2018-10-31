<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once 'config.php';

/**
 * Class to Create MySQLi Connection Tunnel to MySQL Database.
 *
 * @author Hamed
 */
class mySQLConnectSQLi {

    private $hostname;
    private $username;
    private $password;

    /**
     * MySQLi Connection Tunnel to MySQL
     * @example Connection->exec(SQL_Query) Use Connection to Execute SQL Query (Doesn't Return Value)
     * @example Connection->query(SQL_Query) Use Connection to Execute SQL Query (Returns Value)
     */
    public $Connection;

    function __construct() {
        $Config = new config();
        $this->hostname = $Config->hostname;
        $this->username = $Config->username;
        $this->password = $Config->password;
    }

    /**
     * Connect To MySQL using MySQLi
     * @return void 
     */
    public function MySQLiConnect() {
        $this->Connection = new mysqli($this->hostname, $this->username, $this->password); // connect to MySQL No Specific Database


        if ($this->Connection->connect_error) {
            die("Connection failed: " . $this->Connection->connect_error);
        }
    }

    /**
     * Close The Open MySQLi Connection to MySQL
     * @return void
     */
    public function MySQLiConnectionClose() {
        $this->Connection->close();
    }

    /**
     * Get Today's Date
     * @return date Returns Date in (Y-m-d) Format
     */
    public function getTodayDate() {
        return date('Y_m_d');
    }

}
