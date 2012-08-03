<?php
    header("Content-Type: application/rss+xml; charset=ISO-8859-1"); 
    include ('feedconnect.php');
    $rssfeed = '<?xml version="1.0" encoding="ISO-8859-1"?>';
    $rssfeed .= '<rss version="2.0">';
    $rssfeed .= '<channel>';
    $rssfeed .= '<title>Diplomacy State of Play</title>';
    $rssfeed .= '<link>http://diplomacy.patrickrosemusic.co.uk</link>';
    $rssfeed .= '<description>This is the RSS feed for the game of anon diplomacy that I\'m </description>';
    $rssfeed .= '<language>en-uk</language>';
    $rssfeed .= '<copyright>Copyright (C) 2012 Patrick Rose</copyright>';
    $connection = create_connection();
 
    $query = "SELECT * FROM summary ORDER BY turnNum DESC";
    $result = mysql_query($query, $connection) or die ("Could not execute query");
    while($row = mysql_fetch_array($result)) {
        extract($row);
 
        $rssfeed .= '<item>';
        $rssfeed .= '<title>Diplomacy Turn ' . $turnNum . '</title>';
        $rssfeed .= '<description>' . $description . '</description>';
        $rssfeed .= '<link>http://diplomacy.patrickrosemusic.co.uk/#turn' . $turnNum . '</link>';
        $rssfeed .= '<pubDate>' . date("D, d M Y H:i:s O", strtotime($date)) . '</pubDate>';
        $rssfeed .= '</item>';
    }
 
    $rssfeed .= '</channel>';
    $rssfeed .= '</rss>';
 
    echo $rssfeed;

?>
