<?php
include '/home/pi/UNICEF_VACCINE_MONITOR/hilink_smsGateway.php';

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
		$code = explode(" ", strtolower($content));
		echo $code[0];
		$sender = $mes -> Phone;
		$index = $mes -> Index;
		switch ($code[0]) {
			case "api":
				switch ($code[1]) {
					case "get":
						$result = $db->query("SELECT * FROM raspberrypi");
						$row = $result->fetch_array(MYSQLI_ASSOC);
						echo $row['remoteapikey']."\n";
						sendMessage($sender, "Current remote API-key: ".$row['remoteapikey']);
					break;
					
					case "set":
						$apikey = $code[2];
						if(strlen($apikey) == 32){
							$db->query("UPDATE raspberrypi SET remoteapikey = '$apikey'");
							sendMessage($sender, "The remote API-key has been changed to: ".$apikey);
						} else {
							sendMessage($sender, "Error, the API-key should be 32 characters long");
						}
					break;
				}
			break;
			
			case "domain":
				switch ($code[1]) {
					case "get":
						$result = $db->query("SELECT * FROM raspberrypi");
						$row = $result->fetch_array(MYSQLI_ASSOC);
						echo $row['remotedomain']."\n";
						sendMessage($sender, "Current remote domain: ".$row['remotedomain']);
					break;
					
					case "set":
						$domain = $code[2];
						$db->query("UPDATE raspberrypi SET remotedomain = '$domain'");
						sendMessage($sender, "The remote domain has been changed to: ".$domain);
					break;
				}
			break;

			case "path":
				switch ($code[1]) {
					case "get":
						$result = $db->query("SELECT * FROM raspberrypi");
						$row = $result->fetch_array(MYSQLI_ASSOC);
						echo $row['remotepath']."\n";
						sendMessage($sender, "Current remote path: ".$row['remotepath']);
					break;
					
					case "set":
						$path = $code[2];
						$db->query("UPDATE raspberrypi SET remotepath = '$path'");
						sendMessage($sender, "The remote path has been changed to: ".$path);
					break;
				}
			break;
			
			case "protocol":
				switch ($code[1]) {
					case "get":
						$result = $db->query("SELECT * FROM raspberrypi");
						$row = $result->fetch_array(MYSQLI_ASSOC);
						echo $row['remoteprotocol']."\n";
						sendMessage($sender, "Current remote protocol: ".$row['remoteprotocol']);
					break;
					
					case "set":
						$protocol = $code[2];
						$db->query("UPDATE raspberrypi SET remoteprotocol = '$protocol'");
						sendMessage($sender, "The remote domain has been changed to: ".$protocol);
					break;
				}
			break;
			
			case "alarm": // Alarm
				$check = $db->query("SELECT * FROM event WHERE setphonenumber = '$sender'");
				if($check->num_rows > 0) {
					$db->query("DELETE FROM event WHERE setphonenumber = '$sender'");
					sendMessage($sender, "You have been taken off the alarm list");
				} else {
					$db->query("INSERT INTO event (userid, eventfeed, eventtype, action, setphonenumber, lasttime, mutetime) VALUES ('1','0','7', '5', '$sender','0','300')");
					sendMessage($sender, "You have been signed up for alarms");
				}
			break;

			case "reboot":
				sendMessage($sender, "System will now reboot");
				deleteMessage($index);
				exec('sudo reboot');
			break;
			
			case "git": // git pull repository
				exec('cd /home/pi/UNICEF_VACCINE_MONITOR && sudo git pull && cd -');
				sendMessage($sender, "Newest repository has been been pulled, system will now reboot");
				deleteMessage($index);
				exec('sudo reboot');
			break;
			
			case "sensor":
				$sensor = $code[2];
				echo $sensor;
				
				$result = $db->query("SELECT * FROM feeds WHERE name = '$sensor'");
				if($result->num_rows < 1) {
					sendMessage($sender, "could not find sensor: ".$sensor);
					break;
				}
				switch ($code[1]) {
					case "get":
						$row = $result -> fetch_assoc();
						sendMessage($sender, "Sensor: ".$sensor.", [".$row['tag'].", ".$row['value']."]");
					
					break;
					
					case "set":
						switch ($code[3]) {
							case "0":
								// Disabled sensor, ignored in the alarm module
								$tag = "none";
								break;
							case "1":
								// fridge
								$tag = "fridge";
								break;
							
							case "2":
								// freezer
								$tag = "freezer";
								break;
							
							case "3":
								// outdoor
								$tag = "outdoor";
								break;
							
							case "4":
								// power-source
								$tag = "power-source";
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

