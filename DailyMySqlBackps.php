<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE);

ob_start();
//------------------------Configure Classes-------------------------------------
require_once 'classes/mySQLConnectSQLi.php';
$MySQLConnection = new mySQLConnectSQLi();
$MySQLConnection->MySQLiConnect();
//--------
require_once 'classes/backupMySQL.php';
$BackupMySQL = new backupMySQL();
//------------------------Initialise Variables----------------------------------
/*
 * Set BaseDirectory
 * For Linux use something like /var/
 * For Windows use something like c:/
 */

$BaseDirectory = "c:/";
$WorkingDirectory = "";
$Date = $MySQLConnection->getTodayDate();
//------------------------Create Folders----------------------------------------
if (!file_exists($BaseDirectory . 'sql_backups')) {
    mkdir($BaseDirectory . 'sql_backups', 0777, true);
    mkdir($BaseDirectory . 'sql_backups/backups_' . $Date, 0777, true);
    $WorkingDirectory = $BaseDirectory . "sql_backups/backups_" . $Date . '/';
} else {
    if (!file_exists($BaseDirectory . 'sql_backups/backups_' . $Date)) {
        mkdir($BaseDirectory . 'sql_backups/backups_' . $Date, 0777, true);
        $WorkingDirectory = $BaseDirectory . "sql_backups/backups_" . $Date . '/';
    } else {
        $WorkingDirectory = $BaseDirectory . "sql_backups/backups_" . $Date . '/';
    }
}
//------------------------SetUp Backup Class------------------------------------
$BackupMySQL->CreateZipFile = TRUE;
$BackupMySQL->OptimizeDatabaseBeforeBackup = TRUE;
$BackupMySQL->SetModeOS = "WindowsMode1";
$BackupMySQL->WorkingDirectory = $WorkingDirectory;
$BackupMySQL->SqlConnection = $MySQLConnection->Connection;
//------------------------------------------------------------------------------
$LogHeaderDate = "\n\n"
        . "#################################################################\n"
        . "MySQL Backup Log For $Date \n"
        . "#################################################################\n";
$BackupMySQL->WriteToFile($WorkingDirectory, "backups.log", $LogHeaderDate, "a+");

$qDatabasesList = $MySQLConnection->Connection->query("show databases;");
while ($rDatabasesList = $qDatabasesList->fetch_assoc()) {
    $DatabaseName = $rDatabasesList['Database'];
    if ($DatabaseName == "information_schema" || $DatabaseName == "mysql" || $DatabaseName == "performance_schema") {
        // Skip Them
    } else {
        echo "Database => $DatabaseName <br/>";
        
        $BackupMySQL->CreateSqlFile($DatabaseName, $WorkingDirectory);

        echo str_pad(' ', 2048);
        flush();
        ob_flush();
    }
}


//------------------------Close MySQLi Connection-------------------------------
$MySQLConnection->MySQLiConnectionClose();
ob_flush();
?>
