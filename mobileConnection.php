<?php
include '/home/pi/UNICEF_VACCINE_MONITOR/E3131_smsGateway.php';

$Response = getStatus();

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
