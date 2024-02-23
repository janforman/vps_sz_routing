require('./config.php');
$soap = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:pov="https://wcf.grapp.spravazeleznic.cz/POVLData">
  <soap:Header xmlns:wsa="http://www.w3.org/2005/08/addressing">
  <wsa:To>' . $url . '</wsa:To>
  <wsa:Action>https://wcf.grapp.spravazeleznic.cz/POVLData/IPOVLData/Train</wsa:Action>
  </soap:Header>
<soap:Body>
      <pov:Train>
        <pov:request>
            <pov:MessageHeader>
               <pov:MessageReference>
                  <pov:MessageType>8012</pov:MessageType>
                  <pov:MessageTypeVersion>2.4</pov:MessageTypeVersion>
                  <pov:MessageIdentifier>1</pov:MessageIdentifier>
                  <pov:MessageDateTime>' . date(DateTime::RFC3339, time()). '</pov:MessageDateTime>
               </pov:MessageReference>
            </pov:MessageHeader>
            <pov:User>
               <pov:Login>' . $uid . '</pov:Login>
               <pov:Password>' . $password . '</pov:Password>
            </pov:User>
            <pov:LastUpdate>' . date(DateTime::RFC3339, time()- 180). '</pov:LastUpdate>
         </pov:request>
      </pov:Train>
   </soap:Body>
</soap:Envelope>
';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,$url);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/soap+xml'));
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS,$soap);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
$result = curl_exec($ch);
curl_close($ch);

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
	if(!$train->LastConfirmedPoint->Real) continue;
	
	$upd = $db->query("SELECT Type FROM soap WHERE TRID = '$train->TRID';");
	if($upd->FetchArray()) {
		$db->query("UPDATE soap SET LCPoint = '" . substr($train->LastConfirmedPoint->SR70, 2, 6). "', LCPointArrival='" . $train->LastConfirmedPoint->Arrival . "', LCPointTime = '" . strtotime($train->LastConfirmedPoint->Real). "' WHERE TRID = '$train->TRID';");
	} else {
		$db->query("INSERT INTO soap VALUES  ('" . $train->Type . "','" . $train->Number . "','" . $train->TRID . "','" . substr($train->FPoint->SR70, 2, 6). "','" . substr($train->LastConfirmedPoint->SR70, 2, 6). "','" . $train->LastConfirmedPoint->Arrival . "','" . strtotime($train->LastConfirmedPoint->Real). "','" . substr($train->LPoint->SR70, 2, 6). "');");
	}
}

?>
