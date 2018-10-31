# MySqlDatabasesAutoBackup
Create backup from all availlable databases in MySQL. and tore them in the daily specified path. 

Example: sql_backups\

Daily Create Backup files will be placed in the day specified folder like: sql_backups\backups_2018_10_31

# How to Use
Create Object of backupMySQL() class and set the Options:

# Example:
    require_once 'classes/backupMySQL.php';
    $BackupMySQL = new backupMySQL();

    $BackupMySQL->CreateZipFile = TRUE;
Creates Zip File containing the SQL Backup File.

    $BackupMySQL->OptimizeDatabaseBeforeBackup = TRUE;
Optimize the Database before Creating Backup, Which is include, Analyze, Repair and Optimize. The error will be Logged.

    $BackupMySQL->SetModeOS = "Linux";
Set the OS Mode. Default mode is Linux. Default mode does not work on Windows OS. the Options are include "Linux", "WindowsMode1" and "WindowsMode2".

Set the SetModeOS to "WindowsMode1" or "WindowsMode2" if the OS is Windows. "WindowsMode1" Uses Shell(CMD) Command to Create Backup SQL Files, This mode is Highly Reliable. "WindowsMode2" Uses Loop Through Tables to Create Backup SQL Files, This mode is not Reliable. Works best for light Weighted Database. 

    $BackupMySQL->WorkingDirectory = $WorkingDirectory;
Specify the directory path to save the backup files.

     $BackupMySQL->SqlConnection = $MySQLConnection->Connection;
MySQLi Connection Tunnel to MySQL. It should be set to access the Databases. $MySQLConnection->Connection is an object oriented MySQLi Connection.

#  Get The Auto Daily Backup Files:
You can use Linux "Cron Jobs" or on Windows use "Task Scheduler" to Automate the daily backups.

# Linux Cron Jobs Example:
Access the crontab: crontab -e

Set the PHP path and the Script Path in the below line and add it to the crontab. this will run the script every day at 10:00 AM

0 10 * * * [/path/to/php] [/Path/To/Script/]DailyMySqlBackps.php > /dev/null 2>&1

# Windows "Task Scheduler" Example:
Open the windows Run dialog box: Windows key + R

Type taskschd.msc and execute. in Action Menu click on Create Task.

1: Set the Name and location in General Tab. 

2: in Triggers Tab click on the New Button, and under setting panel select Daily.

3: in the Action Tab, Click on the New Button, set the Action Drop Box to "Start a program" and in the Program/scrip section add the following line (Set the PHP path and the Script Path):

C:\Path\To\php.exe -f C:\Path\To\Script\DailyMySqlBackps.php



