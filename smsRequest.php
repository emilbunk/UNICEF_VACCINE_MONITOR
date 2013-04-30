<?php
include '/home/pi/UNICEF_VACCINE_MONITOR/E3131_smsGateway.php';

// Retrive inbox
$Messages = getList(1);

if(!empty($Messages)){
	
	$db = new mysqli('localhost', 'root', 'raspberry', 'emoncms');

	if($db->connect_errno > 0){
    	die('Unable to connect to database [' . $db->connect_error . ']');
	}
	
	foreach ($Messages->Message as $mes) {
		$content = $mes -> Content;
		echo $content."\n";
		$sender = $mes -> Phone;
		$index = $mes -> Index;
		switch (strtolower(substr($content, 0, 3))) {
			case "api":
				switch (strtolower(substr($content, 4, 3))) {
					case "get":
						$result = $db->query("SELECT * FROM raspberrypi");
						$row = $result->fetch_array(MYSQLI_ASSOC);
						echo $row['remoteapikey']."\n";
						sendMessage($sender, "Current remote API-key: ".$row['remoteapikey']);
					break;
					
					case "set":
						$newkey = strtolower(substr($content, 8, 32));
						$db->query("UPDATE raspberrypi SET remoteapikey = '$newkey'");
						sendMessage($sender, "The remote API-key has been change to: ".$newkey);
					break;
				}
			break;

			case "ala": // Alarm
				$check = $db->query("SELECT * FROM event WHERE setphonenumber = '$sender'");
				if($check->num_rows > 0) {
					$db->query("DELETE FROM event WHERE setphonenumber = '$sender'");
					sendMessage($sender, "You have been taken off the alarm list");
				} else {
					$db->query("INSERT INTO event (userid, eventfeed, eventtype, action, setphonenumber, lasttime, mutetime) VALUES ('1','0','7', '5', '$sender','0','300')");
					sendMessage($sender, "You have been signed up for alarms");
				}
			break;

			case "reb": // Reboot
				deleteMessage($index);
				exec('sudo reboot');
			break;
			
			case "sen": // Sensor
				$sensor = strtolower(substr($content, 11, 15));
				echo $sensor;
				echo $content[27];
				$result = $db->query("SELECT * FROM feeds WHERE name = '$sensor'");
				if($result->num_rows < 1) {
					sendMessage($sender, "could not find sensor: ".$sensor);
					break;
				}
				switch (strtolower(substr($content, 7, 3))) {
					case "get":
						$row = $result -> fetch_assoc();
						sendMessage($sender, "Sensor: ".$sensor.", [".$row['tag'].", ".$row['value']."]");
					
					break;
					
					case "set":
						if($content{27} == "1"){
							// fridge
							$tag = "fridge";
							
						} elseif($content{27} == "2") {
							// freezer
							$tag = "freezer";
							
						} elseif($content{27} == "3") {
							// outdoor
							$tag = "outdoor";
							
						} else {
						sendMessage($sender, "not a known tag code");
						break;
						}
						$db -> query("UPDATE feeds SET tag = '$tag' WHERE name = '$sensor'");
						sendMessage($sender, "Sensor: ".$sensor.", has changed tag to: ".$tag);
					break;
				}
			break;
			
			default:
				sendMessage($sender, "\"".$content."\" does not match any known request, try again.");
		}
		deleteMessage($index);
	}
}
// Empty Outbox
$Messages = getList(2);
if(!empty($Messages)){
	foreach ($Messages->Message as $mes) {
		$index = $mes -> Index;
		deleteMessage($index);
	}
}

?>

