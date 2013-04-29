<?php

$db = new mysqli('localhost', 'root', 'raspberry', 'emoncms');

if($db->connect_errno > 0){
    	die('Unable to connect to database [' . $db->connect_error . ']');
}

$input = $db->query("SELECT * FROM input");

while( $row = $input -> fetch_assoc()) {
	echo "INPUT";
	$name = $row['name'];
	$val = intval($row['value']);
	$temp = $db->query("SELECT * FROM feeds WHERE name = '$name'");
	$numRows = $temp->num_rows;
	if($numRows == 0) {
		//insert new feed into feeds table
		$db->query("INSERT INTO feeds (name, userid, tag, value, datatype, public) VALUES ('$name', '1', 'fridge', '$val', '1', '0')");
		
		// Retrieve new feed's id
		$temp = $db->query("SELECT * FROM feeds WHERE name = '$name'");
		$temp = $temp -> fetch_assoc();
		$feedname = "feed_".$temp['id'];
		
		// Create table for new feed
		$db->query("CREATE TABLE $feedname (time INT UNSIGNED, data float, INDEX ( `time` ))");
		
		// Add process to input
		$process = "2:0.01,1:".$temp['id']; // multiply by 0.01 and push to feed_#id.
		$db->query("UPDATE input SET processList='$process' WHERE name = '$name'");
	}
}

?>
