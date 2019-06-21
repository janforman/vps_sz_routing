<?php
$dbdata = new SQLite3('szdc-temp.sqlite', SQLITE3_OPEN_READONLY);
$rs = $dbdata->query("SELECT * FROM soap;");
$db = new SQLite3('szdc-routing.sqlite', SQLITE3_OPEN_READONLY);
$db->loadExtension('mod_spatialite.so');

while($row = $rs->fetchArray()) {
	$rs2 = $db->query("SELECT AsText(Geometry) FROM nodes WHERE bod_sr70 = '$row[4]';");
	$geometry = $rs2->fetchArray();
	$geometry = str_replace('POINT(', '', str_replace(')', '', $geometry[0]));
	$geometry = explode(' ', $geometry);
	if($geometry[1]) {
		//if($geometry[1]) {
		if($row[5] == 'true') {
			$content[] = array($geometry[1], $geometry[0], $row[0] . $row[1], "Stojí ve stanici v " . date("H:i", $row[6]). " <a href='https://www.cd.cz/vlak/" . $row[1] . "' target='_blank'>info</a>");
		} else {
			$noders = $db->query("SELECT ID FROM nodes WHERE bod_sr70 =  " . $row[4] . " LIMIT 1;");
			$node = $noders->fetchArray();
			$NodeFrom = $node[0];
			$noders = $db->query("SELECT ID FROM nodes WHERE bod_sr70 =  " . $row[7] . " LIMIT 1;");
			$node = $noders->fetchArray();
			$NodeTo = $node[0];
			if($noders = $db->query("SELECT * FROM railway_network_net WHERE NodeFrom = " . $NodeFrom . " AND NodeTo = " . $NodeTo . " LIMIT 1,5;")) {

				while($node = $noders->fetchArray()) {
					$rs3 = $db->query("SELECT bod_sr70,bod_sr70_n FROM nodes WHERE ID = " . $node[3] . " LIMIT 1;");
					$temp = $rs3->fetchArray();
					if($temp[0] != "")
						break;
				}
				$dalsistanice = str_replace(',', '.', $temp[1]);
				$timeontrack = abs(time()- $row[6]);
				$rsgeom = $db->query("SELECT GeodesicLength(Geometry),NumPoints(Geometry),AsWKT(Geometry) FROM railway_network_net WHERE NodeFrom = " . $NodeFrom . " AND NodeTo = " . $node[3] . " LIMIT 1;");
				if($rsgeom)
					$temp = $rsgeom->fetchArray();
				else 
					continue;
				$meters = round($temp[0]);
				$points = $temp[1];
				$wkt = explode(',', substr($temp[2], 11, - 1));
				// approx. speed
				$speed = 8;
				if($row[0] == 'Ex' OR $row[0] == 'R' OR $row[0] == 'IC' OR $row[0] == 'Ec')
					$speed = 16;
				// approx. speed
				$position = $speed * $timeontrack;
				if($position > $meters)
					$apoint = round($points * 0.85);
				else {
					$apoint = round($points /($meters / $position));
				}
				$wkt_e = explode(' ', $wkt[$apoint]);
				$lat = round($wkt_e[1], 6);
				$lon = round($wkt_e[0], 6);
				if($lat != 0 AND $lon != 0)
					$content[] = array($lat, $lon, $row[0] . $row[1], "Jede do " . $dalsistanice . " (úsek " . round($meters / 1024, 2). "km) v " . date("H:i", $row[6]). " <a href='https://www.cd.cz/vlak/" . $row[1] . "' target='_blank'>info</a>");
			}
		}
	}
}
$dbdata->close();
$db->close();
generateCSV($content);
generateJSON($content);
generateKML($content);

function generateKML($list) {
	$content = '<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
<Document>
<Style id="train">
 <IconStyle><Icon><href>https://portal.hzspk.cz/icons/szdc.png</href></Icon><scale>2.0</scale></IconStyle>
</Style>';
	$a = 0;

	while($list[$a]) {
		$lon = $list[$a][1];
		$lat = $list[$a][0];
		$name = $list[$a][2];
		$description = str_replace("\r\n", '', $list[$a][3]);
		$content .= "<Placemark id=\"m$a\">
<name><![CDATA[$name]]></name>
<description>$description</description>
<styleUrl>#train</styleUrl> 
<Point>
<coordinates>$lon,$lat</coordinates>
</Point>
</Placemark>\n\r";
		$a ++;
	}
	$content .= '</Document></kml>';
	file_put_contents('trains.kml', $content);
}

function generateCSV($list) {
	$a = 0;
	$content = "lat,lon,type,description\r\n";

	while($list[$a]) {
		$content .= implode(',', $list[$a]). "\r\n";
		$a ++;
	}
	file_put_contents('trains.csv', $content);
}

function generateJSON($list) {
	$content = '{ "type": "FeatureCollection", "features": [';
	$a = 0;

	while($list[$a]) {
		$lon = $list[$a][1];
		$lat = $list[$a][0];
		$name = $list[$a][2];
		$description = str_replace("\r\n", '', explode(' <a href', $list[$a][3]));
		$jsonarray[] = '{ "type": "Feature","geometry": {"type": "Point", "coordinates": [' . $lon . ',' . $lat . ']}, "label":"' . $name . '", "text":"' . $description[0] . '","properties":{}}';
		$a ++;
	}
	$content .= implode(',', $jsonarray). '],"crs":{"type":"name","properties":{"name":"urn:ogc:def:crs:EPSG::4326"}}}';
	file_put_contents('trains.json', $content);
}
