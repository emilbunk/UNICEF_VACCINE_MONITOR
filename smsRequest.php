<?php
include '/home/pi/UNICEF/E3131_smsGateway.php';

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
		$sender = substr($sender, 3);
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
				$stmt = $db->query("INSERT INTO event (userid, eventfeed, eventtype, action, setphonenumber, lasttime, mutetime) VALUES ('1','0','7', '5', '$sender','0','300')");
				sendMessage($sender, "You have been signed up for an alarm");
			break;

			case "reb": // Reboot
				deleteMessage($index);
				exec('sudo reboot');
			break;
			
			default:
				sendMessage($sender, "\"" + $content + "\" does not match any known request, try again.")
			break;
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

