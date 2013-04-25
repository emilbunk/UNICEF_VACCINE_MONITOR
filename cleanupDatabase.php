<?php

$freeDiskSpace = disk_free_space("/");

if(!$freeDiskSpace) {
	echo "diskspace could not be read";
} elseif ($freeDiskSpace > 314572800) { //300 mb limit
	echo "Remaining disk space is surfficiant"
} else {
	$db = new mysqli('localhost', 'root', 'raspberry', 'emoncms');
	
	// 30 day limit on every feed
	$timeLimit = time() - (30 * 24 * 60 * 60);
	
	$feeds = $db->query("SELECT id FROM feeds");
	
	while( $id = $feeds -> fetch_array()) {
		$feedname = "feed_".$id;
		$db->query("DELETE FROM '$feedname' WHERE time < '$timelimit'");
	}
}





?>

