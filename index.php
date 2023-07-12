<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<title>HZS - SŽDC 1.1</title>
</head>
<body>
<h1>SŽDC Sledování vlaků 1.1</h1>
Server poskytuje data v následujících formátech
<a href='/trains.csv'><h2>CSV</h2></a> (text oddělený čárkami - lze načíst ve webové službě)
<a href='/trains.json'><h2>GeoJSON</h2></a>  (GeoJSON standard - lze načíst v IZS Operátoru)
<a href='/trains.kml'><h2>KML</h2></a> (Keyhole Markup Language - lze načíst například v Google aplikacích)
<br/><br/>
<h2>Status služby</h2>
<?php echo "Poslední import ze SOAP služby SŽDC: <b>" . date ("d.m.Y H:i:s", filemtime('./szdc-temp.sqlite')); ?></b><br/>
<?php echo "Export generován: <b>" . date ("d.m.Y H:i:s", filemtime('./trains.csv')); ?></b>
<h2>Changelog</h2>
12.07.2023 - Upgrade OS na Rocky Linux 8 a libSpatiaLite z https://www.gaia-gis.it/gaia-sins/libspatialite-sources/<br/>
31.05.2023 - Expirace vlaků je nyní 1 hodina a záznamy jsou řazeny dle času události<br/>
21.06.2019 - Opraven export KML a GeoJSON (souřadnice ve správném pořadí XY)<br/>
01.06.2019 - SŽDC preferuje TLS spojení, načítání dat tedy probíhá po https<br/>
31.01.2019 - bez limitu na dopravce ČD - zobrazujeme vše co SŽDC pošle<br/>
25.01.2019 - pokud vlak jede po úseku příliš dlouho, zobrazí se fixně na 85% úseku, kde byl naposledy potvrzen. neaktivní záznamy nad 30 minut jsou mazány<br/>
21.01.2019 - poloha vlaku je odhadována s ohledem na uběhlý čas od opuštění bodu SR70 a rozložena na průběh železniční sítě do následujícího bodu SR70<br/>
14.01.2019 - první verze, s připojeným a aktivním routovaním nad sítí železnice (tuto část je třeba dopsat) - nicmene zatím snapnuto na body SR70
</body>
</html>
