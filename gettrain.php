<?php
require('./config.php');
$soap = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <GetTrainPosition xmlns="http://provoz.szdc.cz/grappws/">
      <_request>
        <MessageHeader>
          <MessageNumber>1</MessageNumber>
          <MessageDateTime>' . date(DateTime::RFC3339, time()). '</MessageDateTime>
        </MessageHeader>
        <User>
          <Login>' . $uid . '</Login>
          <Password>' . $password . '</Password>
        </User>
        <LastUpdate>' . date(DateTime::RFC3339, time()- 180). '</LastUpdate>
      </_request>
    </GetTrainPosition>
  </soap:Body>
</soap:Envelope>
';
$options = array('http' => array('method' => 'POST', 'header' => "Content-type: text/xml\r\n" . 'Content-Length: ' . strlen($soap). "\r\n", 'content' => $soap));
$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);
$result = explode('</MessageHeader>', $result);
$result = explode('<Status>OK</Status>', $result[1]);
$xml = simplexml_load_string('<document>' . $result[0] . '</document>');
$db = new SQLite3('szdc-temp.sqlite');
$maxage = time()- 3600;
//$db->query("DROP TABLE soap;");
//$db->query('CREATE TABLE soap (Type TEXT,Number INTEGER,TRID TEXT,FPoint INTEGER,LCPoint INTEGER,LCPointArrival TEXT,LCPointTime INTEGER,LPoint INTEGER);');
$db->query("DELETE FROM soap WHERE LCPointTime < $maxage;");
$db->query("VACUUM;");

foreach($xml->children()as $train) {
	if($train->Substitution == 'true') continue;
	$upd = $db->query("SELECT Type FROM soap WHERE TRID = '$train->TRID';");
	if($upd->FetchArray()) {
		$db->query("UPDATE soap SET LCPoint = '" . substr($train->LastConfirmedPoint->SR70, 2, 6). "', LCPointArrival='" . $train->LastConfirmedPoint->Arrival . "', LCPointTime = '" . strtotime($train->LastConfirmedPoint->Real). "' WHERE TRID = '$train->TRID';");
	} else {
		$db->query("INSERT INTO soap VALUES  ('" . $train->Type . "','" . $train->Number . "','" . $train->TRID . "','" . substr($train->FPoint->SR70, 2, 6). "','" . substr($train->LastConfirmedPoint->SR70, 2, 6). "','" . $train->LastConfirmedPoint->Arrival . "','" . strtotime($train->LastConfirmedPoint->Real). "','" . substr($train->LPoint->SR70, 2, 6). "');");
	}
}

?>
