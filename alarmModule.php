<?php
include '/home/pi/UNICEF_VACCINE_MONITOR/E3131_smsGateway.php';

// Global variables
$fridgeMax = 7;
$fridgeLow = 3;
$freezerMax = -15;
$freezerLow = -25;
$maxTimeWithoutPower = 10 * 60; // 10 minutes


$db = new mysqli('localhost', 'root', 'raspberry', 'emoncms');

if($db->connect_errno > 0){
	die('Unable to connect to database [' . $db->connect_error . ']');
}


// Check Fridges
$input = $db->query("SELECT * FROM feeds WHERE tag = 'fridge'");

$alarms = "";

while($row = $input -> fetch_assoc()) {
	$name = $row['name'];
	$val = floatval($row['value']);
	$time = strtotime($row['time']);

	if(time()-$time < 10 * 60) { // if reading is less than 10 min old
		if($val > $fridgeMax OR $val < $fridgeLow) {
			$alarms = $alarms."[".$name."] ".$row['value'].", ";
		}
	}
}


// Check Power source
$input = $db->query("SELECT * FROM feeds WHERE tag = 'power-source'");

$alarms = "";

while($row = $input -> fetch_assoc()) {
	if($row['value'] == '0') {
		$time = strtotime($row['time']);
		
	if(time()-$time < 10 * 60) { // if reading is less than 10 min old
		if(strlen($alarms) > 0) {
			$alarms = $alarms." - ";
		}
		
		$alarms = $alarms."The power-grid seems to be down!";
	}
}


if(strlen($alarms) > 0) {
	$mutetime = time() - 5 * 60;
	$users = $db -> query("SELECT * FROM event WHERE lasttime < '$mutetime'");
	
	while($row = $users -> fetch_assoc()) {
		$phoneNumber = $row['setphonenumber'];
		$message = "ALARM: ".substr($alarms, 0, -2);
		echo $message;
		sendMessage($phoneNumber, $message);
		$id = $row['id'];
		$updateTime = time();
		$db -> query("UPDATE event SET lasttime = '$updateTime' WHERE id = '$id'");
	}
}