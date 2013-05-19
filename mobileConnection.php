<?php
include '/home/pi/UNICEF_VACCINE_MONITOR/hilink_smsGateway.php';

$Response = getStatus();

if($Response){
	$connectionStatus = $Response -> ConnectionStatus;
	if($connectionStatus == 901) {
		echo "Connected";
	} else {
		echo "Disconnected";
	}
	echo "Network type: ".$Respone -> CurrentNetworkType;
	
	echo "connection strength: ".$Response -> SignalStrength;
	
	echo "WanIPAddress: ".$Response -> WanIPAddress;
	
	echo "Primary DNS: ".$Respone -> PrimaryDns;
} else {
	echo "Could not connect to E3131";
}
