<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Diplomacy State of Play</title>
</head>
<body>
<?php
include("connect.php");
$connection = create_connection();
create_tables_if_needed($connection);//Should also be in admin?
$not_set = FALSE;
if ($not_set)
{
    set_players($connection);//Should probably be in admin
}
else
{
    $turn_number = get_last_turn_number($connection);
    show_header($turn_number);
    show_turn_results($turn_number, $connection);
}
?>
</body>
</html>



<?php

function show_header($turn_number)
{
    if ($turn_number != 0)
    {
        echo "<a href=#turn" . $turn_number . ">Turn $turn_number</a> ";
        show_header($turn_number-1);
    }
}

function get_last_turn_number(PDO $con)
{
    try
    {

        $query = "SELECT turnNum FROM positions ORDER BY turnNum DESC LIMIT 1";
        $result = $con->query($query);
        $array = $result->fetchAll();
        return $array['turnNum'];
    }
    catch(PDOException $e)
    {
        log_error($e->getMessage(), __LINE__);
        echo "<p id=\"error\">Couldn't get last turn number</p>";
        return null;
    }
}

function show_turn_results($turn_number, $con)
{
    if ($turn_number == 0)
    {
        echo "<p>No more turns</p>";
        return true;
    }
    $query = "SELECT description FROM summary WHERE id = $turn_number";
    $result = mysql_query($query, $con);
    if (!$result)
    {
        die("Error on line 56: " . mysql_error($con));
    }
    if (mysql_num_rows($result) == 0)
    {
        $summary = "<p>No summary for this turn yet</p>";
    }
    else
    {
        $array = mysql_fetch_array($result);
        $summary = $array['description'];
    }
    echo "<a name=\"turn" . $turn_number ."\" /><h1>Turn $turn_number</h1>
	<p>" . str_ireplace("\n", "</p>\n<p>", strip_tags($summary)) . "</p>
	<p>The state of the board at the beginning of turn $turn_number is:<p>
	<img src=\"https://dl.dropbox.com/u/2827522/Diplomacy/Turn" . $turn_number . ".png\" alt=\"Turn $turn_number Map\" />
    <ul><strong>KEY:</strong>
	    <li>Pink: England</li>
	    <li>Blue: France</li>
	    <li>Black: Germany</li>
        <li>Green: Italy</li>
        <li>Brown: Hungary</li>
        <li>Yellow: Turkey</li>
        <li>Purple: Russia</li>
        <li>Square: Soldier</li>
        <li>Circle: Ship</li>
    </ul>\n";
    $countries = array(
        "England",
        "France",
        "Germany",
        "Hungary",
        "Italy",
        "Russia",
        "Turkey"
    );
    foreach($countries as $country)
    {
        $query = mysql_query("SELECT pipCount FROM pips WHERE turnNum = $turn_number AND country = '$country';", $con);
        $array = mysql_fetch_array($query);
        $pip_count = $array['pipCount'];
        $query = "SELECT * FROM positions WHERE turnNum = $turn_number AND country = '$country';";
        $result = mysql_query($query, $con);
        if (!$result)
        {
            die("Query failed: " . mysql_error($con));
        }
        $army_count = mysql_num_rows($result);
        echo "<ul><strong>" . strtoupper($country) . " - $army_count armies ($pip_count pips controlled)</strong>\n";
        while ($row = mysql_fetch_array($result))
        {
            echo "<li>" . $row['type'] . " - " . $row['position'] . "</li>\n";
        }
        echo "</ul>\n";
        $query = "SELECT orderText, succeeded FROM orders WHERE turnNum = " . ($turn_number - 1) . " AND country = '$country';";
        $result = mysql_query($query, $con);
        if (mysql_num_rows($result) != 0)
        {
            echo  "<ul><strong>Orders Sent Last Turn</strong>\n";
            while ($row = mysql_fetch_array($result))
            {
                echo "<li>" . $row['orderText'];
                if ($row['succeeded'] != 1)
                {
                    echo " - <strong>ORDER FAILED</strong>";
                }
                echo  "</li>\n";
            }
            echo "</ul>\n";
        }
        else
        {
            echo "<p>No orders given for this turn</p>\n";
        }
    }
    show_turn_results($turn_number - 1, $con);
    return TRUE;
}

function set_players(PDO $con)
{
    try
    {
        $con->beginTransaction();
        $assigning = 1;
        $assign_query = $con->prepare("UPDATE email SET assign=:assigning WHERE id=:id;");
        $assign_query->bindParam(":assigning", $assigning);
        $assign_query->bindParam(":id", $id);
        echo "<p>Setting email assign numbers</p>";
        foreach($con->query("SELECT id FROM email ORDER BY rand()") as $row)
        {
            $id = $row['id'];
            echo "<p>Setting assign number $assigning</p>";
            //$query = "UPDATE email SET assign=$assigning WHERE id=".$row['id'].";";
            $assign_query->execute();
            $assigning = $assigning + 1;
        }
        $assign_query = $con->prepare("SELECT * FROM email WHERE assign = :assigning;");
        $assign_query->bindParam(":assigning", $assigning);
        $assigning = 1;
        $update_query = $con->prepare("UPDATE players SET playing=:playing_email WHERE id=:id;");
        $update_query->bindParam(":playing_email", $playing_email);
        $update_query->bindParam(":id", $id);
        echo "<p>Setting players</p>";
        foreach($con->query("SELECT * FROM players ORDER BY RAND();") as $row)
        {
            $email = $row['email'];
            $player = $row['name'];
            $assign_query->execute();
            $row_email = $assign_query->fetchAll();
            $playing_email = $row_email['email'];
            $password = $row_email['password'];
            //echo "<p>$player, $email, $playing_email, $password</p>";
            if (do_mailing($email, $player, $playing_email, $password))
            {
                echo "<p>Emailed $player</p>";
            }
            else
            {
                die("<p>Failed to email $player</p>");
            }
            $id = $row['id'];
            $update_query->execute();
            $assigning = $assigning + 1;
        }
    }
    catch(PDOException $e)
    {
        log_error($e->getMessage(), __LINE__);
        echo "<p id=\"error\">Error attempting to set players</p>";
    }
}

function do_mailing($email, $player_name, $playing_email, $password)
{
    $message =
        "
           Dear $player_name,
       
           The diplomacy randomiser has now been run, and you have been given
           this fine set of log in details:
           
           Email: $playing_email
           Password: $password
           
           To log in, go to http://www.patrickrosemusic.co.uk:2095/ and put in those details.
           Those who'd like to download their emails to a phone or whatever, please email mepp
           so I can give you those details.
       
           Please wait for further details
           Paddy
       ";
    return mail($email, "[Diplomacy] FINAL PASSWORD", $message);
    //echo "<p>$message</p>";
    //return TRUE;
}

function create_tables_if_needed(PDO $con)
{
    try
    {
        $con->beginTransaction();
        $query = "CREATE TABLE IF NOT EXISTS email (id INT PRIMARY KEY AUTO_INCREMENT, email VARCHAR(50), password VARCHAR(50), assign INT)";
        $con->exec($query);
        $query = "CREATE TABLE IF NOT EXISTS players (id INT PRIMARY KEY AUTO_INCREMENT, email VARCHAR(50), name VARCHAR(50), playing VARCHAR(50))";
        $con->exec($query);
        $query = "CREATE TABLE IF NOT EXISTS orders (id INT PRIMARY KEY AUTO_INCREMENT, country VARCHAR(10), orderText VARCHAR(75), turnNum INT, succeeded tinyint(1) NOT NULL DEFAULT '1');";
        $con->exec($query);
        $query = "CREATE TABLE IF NOT EXISTS positions (id INT PRIMARY KEY AUTO_INCREMENT, country VARCHAR(10), type VARCHAR(7), position VARCHAR(20), turnNum INT);";
        $con->exec($query);
        $query = "CREATE TABLE IF NOT EXISTS summary (id INT PRIMARY KEY AUTO_INCREMENT, description VARCHAR(255), turnNum INT);";
        $con->exec($query);
        $query = "CREATE TABLE IF NOT EXISTS pips (id INT PRIMARY KEY AUTO_INCREMENT, country VARCHAR(10), pipCount INT, turnNum INT);";
        $con->exec($query);
        $con->commit();
    }
    catch(PDOException $e)
    {
        log_error($e->getMessage(), __LINE__);
        echo "<p id=\"error\">Error attempting to create the tables</p>";
        return null;
    }
}

?>
