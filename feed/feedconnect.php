<?php

define("ADMIN", "");
define("DB_HOST", "");
define("DB_NAME", "");
define("DB_USER", "");
define("DB_PASS", "");

function create_connection()
{
    try
    {
        $con = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        return $con;
    }
    catch(PDOException $e)
    {
        log_error($e->getMessage(), __LINE__);
        die("Error hit, please tell the admin");
    }
}

function log_error($error_msg, $line_of_error)
{
    $file = fopen("error.log", 'a');
    $date = date("d/m/y");
    $msg = $date . ": Error at line $line_of_error. Error was $error_msg";
    mail(ADMIN, "Error hit on Diplomacy", "There was an error with diplomacy, please look at the error log");
    fwrite($file, $msg);
    fclose($file);
    error_log($msg);
}

?>