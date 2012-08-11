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
    //testing();
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

function get_last_turn_number($con)
{
    $query = "SELECT turnNum FROM positions ORDER BY turnNum DESC LIMIT 1";
    $result = mysql_query($query, $con);
    if (!$result)
    {
        die("Error on line 39. Error was: " . mysql_query($con));
    }
    $array = mysql_fetch_array($result);
    return $array['turnNum'];
}

function show_turn_results($turn_number, $con)
{
    if ($turn_number == 0)
    {
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

function set_players($con)
{
    $randomiser = mysql_query("SELECT id FROM email ORDER BY rand()", $con);
    $assigning = 1;
    if (!$randomiser)
    {
        die("<p>There was a problem with the query, error was</p>" . mysql_error($con));
    }
    echo "<p>Setting email assign numbers</p>";
    while($row = mysql_fetch_array($randomiser))
    {
        echo "<p>Setting assign number $assigning</p>";
        $query = "UPDATE email SET assign=$assigning WHERE id=".$row['id'].";";
        $result = mysql_query($query);
        if (!$result)
        {
            die("<p>There was a problem with the query, query was:</p>" . $query);
        }
        $assigning = $assigning + 1;
    }
    $query_players =
        "
       SELECT *
       FROM players
       ORDER BY RAND();
     ";
    $result_players = mysql_query($query_players, $con);
    if (!$result_players)
    {
        die("<p>There was a problem with the query, query was:</p>" . $query_players);
    }
    $assigning = 1;
    echo "<p>Setting players</p>";
    while($row = mysql_fetch_array($result_players))
    {
        $email = $row['email'];
        $player = $row['name'];
        $query = "SELECT * FROM email WHERE assign = $assigning;";
        if (!$result = mysql_query($query, $con))
        {
            die("<p>There was a problem with the query (line 74), query was:</p>" . $query);
        }
        $row_email = mysql_fetch_array($result);
        $playing_email = $row_email['email'];
        $password = $row_email['password'];
//       echo "<p>$player, $email, $playing_email, $password</p>";
        if (do_mailing($email, $player, $playing_email, $password))
        {
            echo "<p>Emailed $player</p>";
        }
        else
        {
            die("<p>Failed to email $player</p>");
        }
        $query = "UPDATE players SET playing='$playing_email' WHERE id=".$row['id'].";";
        $result = mysql_query($query);
        if (!$result)
        {
            die("<p>There was a problem with the query, query was:</p>" . $query);
        }
        $assigning = $assigning + 1;
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
//       echo "<p>$message</p>";
//       return TRUE;
}

function create_tables_if_needed($con)
{
    $query = "CREATE TABLE IF NOT EXISTS email (id INT PRIMARY KEY AUTO_INCREMENT, email VARCHAR(50), password VARCHAR(50), assign INT)";
    $result = mysql_query($query, $con);
    if (!$result)
    {
        die("<p>There was a problem with the query, query was:</p>" . $query . "<p>Error was: </p>" . mysql_error($con));
    }
    $query = "CREATE TABLE IF NOT EXISTS players (id INT PRIMARY KEY AUTO_INCREMENT, email VARCHAR(50), name VARCHAR(50), playing VARCHAR(50))";
    $result = mysql_query($query, $con);
    if (!$result)
    {
        die("<p>There was a problem with the query, query was:</p>" . $query . "<p>Error was: </p>" . mysql_error($con));
    }
    $query = "CREATE TABLE IF NOT EXISTS orders (id INT PRIMARY KEY AUTO_INCREMENT, country VARCHAR(10), orderText VARCHAR(75), turnNum INT, succeeded tinyint(1) NOT NULL DEFAULT '1');";
    $result = mysql_query($query, $con);
    if (!$result)
    {
        die("<p>There was a problem with the query, query was:</p>" . $query . "<p>Error was: </p>" . mysql_error($con));
    }
    $query = "CREATE TABLE IF NOT EXISTS positions (id INT PRIMARY KEY AUTO_INCREMENT, country VARCHAR(10), type VARCHAR(7), position VARCHAR(20), turnNum INT);";
    $result = mysql_query($query, $con);
    if (!$result)
    {
        die("<p>There was a problem with the query, query was:</p>" . $query . "<p>Error was: </p>" . mysql_error($con));
    }
    $query = "CREATE TABLE IF NOT EXISTS summary (id INT PRIMARY KEY AUTO_INCREMENT, description VARCHAR(255), turnNum INT);";
    $result = mysql_query($query, $con);
    if (!$result)
    {
        die("<p>There was a problem with the query, query was:</p>" . $query . "<p>Error was: </p>" . mysql_error($con));
    }
    $query = "CREATE TABLE IF NOT EXISTS pips (id INT PRIMARY KEY AUTO_INCREMENT, country VARCHAR(10), pipCount INT, turnNum INT);";
    $result = mysql_query($query, $con);
    if (!$result)
    {
        die("<p>There was a problem with the query, query was:</p>" . $query . "<p>Error was: </p>" . mysql_error($con));
    }
}

?>
