<?php
// Usage of Huwaei E3131 as SMS Gateway
// Request responses can be seen on: http://chaddyhv.wordpress.com/2012/08/13/programming-and-installing-huawei-hilink-e3131-under-linux/
// Setting up E3131 for Raspberry pi (Wheezy), see: http://www.raspberrypi.org/phpBB3/viewtopic.php?f=36&t=18996
	function sendRequest($URL, $xml_data) {
		$ch = curl_init($URL);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
		curl_setopt($ch, CURLOPT_POSTFIELDS, "$xml_data");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, false);

		$output = curl_exec($ch);
		curl_close($ch);
		$xml = simplexml_load_string($output);
		return $xml;
	}

	function getList($boxNo, $pageNo = 1) {
		// 1 for inbox, 2 for outbox
		$URL = "http://192.168.1.1/api/sms/sms-list";
		$xml_data =	"<request>".
    				"<PageIndex>".$pageNo."</PageIndex>".
        			"<ReadCount>20</ReadCount>".
        			"<BoxType>".$boxNo."</BoxType>".
					"<SortType>0</SortType>".
        			"<Ascending>0</Ascending>".
					"<UnreadPreferred>0</UnreadPreferred>".
					"</request>";
		return sendRequest($URL, $xml_data)-> Messages;
	}


	function deleteMessage($index) {
		$URL = "http://192.168.1.1/api/sms/delete-sms";
		$xml_data =	"<request>".
    				"<Index>".$index."</Index>".
        			"</request>";
		
		sendRequest($URL, $xml_data);
	}
	
	function sendMessage($phoneNumber, $content) {
		$URL = "http://192.168.1.1/api/sms/send-sms";
		$xml_data = "<request>".
					"<Index>1</Index>".
					"<Phones><Phone>".$phoneNumber."</Phone></Phones>".
					"<Sca></Sca>".
					"<Content>".$content."</Content>".
					"<Length>".strlen($content)."</Length>".
					"<Reserved>1</Reserved>".
					"<Date>1</Date>".
					"</request>";

		sendRequest($URL, $xml_data);
	}
?>
