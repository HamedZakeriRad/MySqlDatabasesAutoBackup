<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once 'config.php';
/**
 * Create MySQL Backups off all Databases
 *
 * @author Hamed
 */
class backupMySQL {

    //---------------------------
    /**
     * MySQLi Connection Tunnel to MySQL. It should be set to access the Databases.
     * @param MySQLiConnection MySQLi Connection Tunnel to MySQL.
     */
    public $SqlConnection;

    /**
     * Create Zip File from SQL File. Default value is FALSE. 
     * @param Bool Set this to TRUE, to create Zip Archive from the SQL File.
     */
    public $CreateZipFile = FALSE;

    /**
     * Optimize Database before creating SQL File backup. Default value is TRUE. 
     * @param Bool Set this it to FALSE, to avoid Optimizing the Database.
     */
    public $OptimizeDatabaseBeforeBackup = TRUE;

    /**
     * Set Backup SQL File OS Mode. Default mode is Linux. Default mode doesn't work on Windows OS.
     * @param String Set this to "Linux" if the OS is Linux/UNIX Type, To use shell command for Creating Backups.
     * @param String Set this to "WindowsMode1" if the OS is Windows, To use it on Windows OS. WindowsMode1 Uses Shell(CMD) Command to Create Backup SQL Files, This mode is Highly Reliable.
     * @param String Set this to "WindowsMode2"if the OS is Windows, To use it on Windows OS. WindowsMode2 Uses Loop Through Tables to Create Backup SQL Files, This mode is not Reliable. Works best for light Weighted Database. 
     */
    public $SetModeOS = "linux";

    /**
     * Specify the directory path to save the backup files. 
     * @param String Directory Path.
     */
    public $WorkingDirectory;
    //---------------------------
    private $SqlContent;
    private $CreateZipArchive;
    private $DatabaseName;

    private $username;
    private $password;
    //---------------------------
    function __construct() {
        $this->CreateZipArchive = new ZipArchive();
        
        $Config = new config();
        $this->username = $Config->username;
        $this->password = $Config->password;
    }

    /**
     * Create SQL File
     * @return void 
     * @param String $DatabaseName Name of the database.
     * @param String $WorkingDirectory Path for saving file.
     */
    public function CreateSqlFile($DatabaseName) {
        $this->SetUpOptions($DatabaseName);

        if ($this->OptimizeDatabaseBeforeBackup) {
            $this->OptimizeDatabase();
        }
        if ($this->SetModeOS == "windowsmode1") {
            $this->WindowsShellDumpDatabases();
        }else if ($this->SetModeOS == "windowsmode2") {
            $this->SetUpContetntFile();
            $Tables = $this->GetAllTables();
            $this->CycleThroughTables($Tables);
            $this->WriteToFile($this->WorkingDirectory, $this->DatabaseName . ".sql", $this->SqlContent, "a+"); // Add the Content to File 
            $this->SqlContent = ""; // Empty the Content File
             
        } else if ($this->SetModeOS == "linux") {
            $this->LinuxShellDumpDatabases();
        } else {
            $SetOSError = "Please set the SetModeOS variable to 'WindowsMode1', 'WindowsMode2' or 'Linux' OS.";
            echo $SetOSError . "<br/>";
            $this->WriteToFile($this->WorkingDirectory, "backups.log", $SetOSError . "\n", "a+");
            
        }
        if ($this->CreateZipFile) {
            $this->CreateZipFile(); //Create Zip Archive
        }
    }
    /**
     * Initialize Variables and Select The Database. 
     * @return void 
     * @param String $DatabaseName Database Name.
     */
    private function SetUpOptions($DatabaseName) {
        $this->SetModeOS = strtolower($this->SetModeOS);
        $this->SqlContent = "";
        $this->SetDatabaseName($DatabaseName);
        $this->SqlConnection->query("use " . $this->DatabaseName . ";");  // Select Database
        $DatabaseHeader = "\n"
        . "#######\n"
        . "Database => $this->DatabaseName \n"
        . "#######\n\n";
        $this->WriteToFile($this->WorkingDirectory, "backups.log", $DatabaseHeader, "a+");
    }
    /**
     * Write Headers to SQL Backup File 
     * @return void 
     */
    private function SetUpContetntFile() {
        $this->SqlConnection->query("SET NAMES 'utf8'");
        $this->SqlContent .= "-- ------------------------------------------------------\n"
                . "-- Host: localhost    Database: $this->DatabaseName\n"
                . "-- ------------------------------------------------------\n"
                . "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n"
                . "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n"
                . "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n"
                . "/*!40101 SET NAMES utf8 */;\n"
                . "/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;\n"
                . "/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;\n"
                . "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;\n"
                . "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;\n"
                . "/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;\n"
                . "-- ------------------------------------------------------\n\n\n";
        $this->WriteToFile($this->WorkingDirectory, $this->DatabaseName . ".sql", $this->SqlContent, "w+"); // Add the Content to File 
        $this->SqlContent = ""; // Empty the Content File
    }
    /**
     * Get all Available tables in The Database. 
     * @return Array Name of the Tables 
     */
    private function GetAllTables() {
        $Tables = '*';
        if ($Tables == '*') {
            $Tables = array();
            $qShowTablesQueryRow = $this->SqlConnection->query('SHOW TABLES');
            while ($ShowTablesQueryRow = $qShowTablesQueryRow->fetch_row()) {
                $Tables[] = $ShowTablesQueryRow[0];
            }
        } else {
            $Tables = is_array($Tables) ? $Tables : explode(',', $Tables);
        }
        return $Tables;
    }
    /**
     * Cycle Through all Tables and get Create Table statement. 
     * @return void 
     * @param Array $Tables Name of the Tables.
     */
    private function CycleThroughTables($Tables) {
        foreach ($Tables as $Table) {
            $this->SqlContent .= "-- \n"
                    . "-- Table structure for table `$Table` \n"
                    . "--\n\n";
            $SelectAllQuery = $this->SqlConnection->query('SELECT * FROM ' . $Table);
            $NumFields = $SelectAllQuery->field_count;

            $this->SqlContent .= "DROP TABLE $Table; \n";
            $CreateStatementRow = $this->SqlConnection->query('SHOW CREATE TABLE ' . $Table)->fetch_row();
            $this->SqlContent .= "\n\n" . $CreateStatementRow[1] . ";\n\n";

            $this->GetTablesValue($NumFields, $SelectAllQuery, $Table);
        }
    }
    /**
     * Get all the values in the Table and create Insert Query. 
     * @return void 
     * @param Integer $NumFields Number of columns in the Table.
     * @param QueryObject $SelectAllQuery Result of Select Query to get all the Columns.
     * @param Array $Table Name of the Tables.
     */
    private function GetTablesValue($NumFields, $SelectAllQuery, $Table) {
        for ($i = 0; $i < $NumFields; $i++) {
            while ($row = $SelectAllQuery->fetch_row()) {
                $this->SqlContent .= "INSERT INTO $Table VALUES(";
                for ($j = 0; $j < $NumFields; $j++) {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = str_replace("\n", "\\n", $row[$j]);
                    if (isset($row[$j])) {
                        $this->SqlContent .= '"' . $row[$j] . '"';
                    } else {
                        $this->SqlContent .= '""';
                    }
                    if ($j < ($NumFields - 1)) {
                        $this->SqlContent .= ",";
                    }
                }
                $this->SqlContent .= ");\n";
                $this->WriteToFile($this->WorkingDirectory, $this->DatabaseName . ".sql", $this->SqlContent, "a+"); // Add the Content to File 
                $this->SqlContent = ""; // Empty the Content File
            }
        }
        $this->SqlContent .= "\n\n\n";
    }
    /**
     * Create Zip Archive. 
     * @return void 
     */
    private function CreateZipFile() {
        $ZipFileName = $this->WorkingDirectory . $this->DatabaseName . ".zip";
        if ($this->CreateZipArchive->open($ZipFileName, ZipArchive::CREATE) !== TRUE) {
            exit();
        }
        $this->CreateZipArchive->addFile($this->WorkingDirectory . $this->DatabaseName . ".sql", $this->DatabaseName . ".sql");
        $this->CreateZipArchive->close();
    }
    /**
     * Optimize Database, which is include Analyze, RRepair and Optimize. 
     * @return void 
     * @param Array $Tables Name of the Tables.
     */
    private function OptimizeDatabase() {
        $TableStatusResult = $this->SqlConnection->query("SHOW TABLE STATUS FROM " . $this->DatabaseName); // Query mySQL for the results
        // ----------Analaysis And Repair----------------------------
        $qShowTables = $this->SqlConnection->query("show tables");
        while ($rShowTables = $qShowTables->fetch_array()) {
            $qAnalizeTables = $this->SqlConnection->query("analyze table $rShowTables[0]");
            $this->OptimizeError("Analyze", $rShowTables[0]);
            $rAnalizeTables = $qAnalizeTables->fetch_array();

            $qRepareTables = $this->SqlConnection->query("repair table $rShowTables[0]");
            $this->OptimizeError("Repair", $rShowTables[0]);
            $rRepareTables = $qRepareTables->fetch_array();
        }
        // ----------Optimize-----------------------------------------
        if ($TableStatusResult->num_rows) {  // Check to see if any tables exist within database
            while ($rTableRow = $TableStatusResult->fetch_array()) { // Loop through all the tables
                $this->SqlConnection->query("OPTIMIZE TABLE " . $rTableRow[0]); // Statement to optimize table. Optimize currently looped table
                $this->OptimizeError("Optimize", $rTableRow[0]);
            }
        }
    }
    /**
     * Linux OS Only. Use Linux Shell to Execute MySqlDump Command. 
     * @return void 
     */
    private function LinuxShellDumpDatabases() {
        $ErrorOutput = "";
        $FileNameAndPath = $this->WorkingDirectory . $this->DatabaseName . ".sql";
        exec('mysqldump -u' . $this->username . ' -p' . $this->password . ' ' . $this->DatabaseName . ' > ' . $FileNameAndPath . ';', $ErrorOutput);
        $this->WriteToFile($this->WorkingDirectory, "backups.log", $ErrorOutput[0], "a+");
    }

    /**
     * Write To File
     * @return void 
     * @param String $WorkingDirectory Path for saving file.
     * @param String $FileName Name of the file to be saved.
     * @param String $Text Content of the file.
     * @param String $Mod Set file mod; Example "a+" or "w+".
     */
    public function WriteToFile($WorkingDirectory, $FileName, $Text, $Mod) {
        $File = fopen($WorkingDirectory . $FileName, $Mod);
        fputs($File, $Text);
        fclose($File);
    }
    /**
     * Set Database Name. 
     * @return void 
     * @param String $DatabaseName Name of the Database.
     */
    private function SetDatabaseName($DatabaseName) {
        $this->DatabaseName = $DatabaseName;
    }
    /**
     * Write Optimization Error to the Log File. 
     * @return void 
     * @param String $Type Can be Analyze, Repair or Optimize.
     */
    private function OptimizeError($Type, $TableName) {
        $Content = "";
        if ($this->SqlConnection->error == "") {
            //UnComment Below Line For Logging Healthy Tables.
            //$Content = "[$Type] $TableName => Every Thing Seems To Be OK. \n"; 
        } else {
            $Content = "[$Type] $TableName => ".$this->SqlConnection->error . " \n";
        }
        $this->WriteToFile($this->WorkingDirectory, "backups.log", $Content, "a+");
    }
    /**
     * Windows OS Only. Use Windows Shell(CMD) to Execute MySqlDump.exe Command. 
     * @return void 
     */
    private function WindowsShellDumpDatabases() {
        $MySqlPath = $this->SqlConnection->query("SHOW VARIABLES LIKE 'basedir'")->fetch_assoc();
        $MySqlPath = $MySqlPath["Value"]."bin\\";
        $ErrorOutput = "";
        $FileNameAndPath = $this->WorkingDirectory . $this->DatabaseName . ".sql";
        exec($MySqlPath.'mysqldump.exe --user=' . $this->username . ' --password=' . $this->password . ' ' . $this->DatabaseName . ' > ' . $FileNameAndPath, $ErrorOutput);
        $this->WriteToFile($this->WorkingDirectory, "backups.log", $ErrorOutput[0], "a+");
    }

}
