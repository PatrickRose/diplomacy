<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>Diplomacy Admin Page</title>
    </head>
    <body>
        <?php
          include("connect.php");
          do_php_stuff();
        ?>
    </body>
</html>

<?php

function do_php_stuff()
{
    if(logged_in())
    {
        admin_page();
    }
    else
    {
        log_in_page();
    }
}

function logged_in()
{
    //do cookies!
    if ($_COOKIE['diplo_logged_in'] == 'TRUE')
    {
        return true;
    }
    if (isset($_POST['log_in']))
    {
        $password = "Backstabbing";        ;
        if ($_POST['log_in'] == $password)
        {
            setcookie('diplo_logged_in', 'TRUE');
            return true;
        }
        else
        {
            echo "<p id=\"error\">Incorrect password, try again</p>";
        }
        return false;
    }
}

function admin_page()
{
    //echo admin page
}

function log_in_page()
{
    //echo log in page
}
?>