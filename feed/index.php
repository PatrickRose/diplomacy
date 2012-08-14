<?php
header("Content-Type: application/rss+xml; charset=ISO-8859-1");
include ('feedconnect.php');
$rssfeed = '<?xml version="1.0" encoding="ISO-8859-1"?>
        <rss version="2.0">
            <channel>
                <title>Diplomacy State of Play</title>
                <link>http://diplomacy.patrickrosemusic.co.uk</link>
                <description>This is the RSS feed for the game of anon diplomacy that I\'m </description>
                <language>en-uk</language>
                <copyright>Copyright (C) 2012 Patrick Rose</copyright>';
$connection = create_connection();
$query = "SELECT * FROM summary ORDER BY turnNum DESC";
foreach($connection->query($query) as $row)
{
    extract($row);
    $link = 'http://diplomacy.patrickrosemusic.co.uk/#turn' . $turnNum;
    $pubDate = date("D, d M Y H:i:s O", strtotime($date));
    $rssfeed .=
        '<item>
                <title>Diplomacy Turn $turnNum</title>
                <description>$description</description>
                <link>$link</link>
                <pubDate>$pubDate</pubDate>
            </item>';
}

$rssfeed .=
    '
            </channel>
        </rss>';

echo $rssfeed;

?>
