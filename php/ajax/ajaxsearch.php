<?php

include '../config.php';
include '../utility.php';

$s = urldecode($_REQUEST['s']);

$mysqli = new mysqli($sqlserver, $readonlyuser, $readonlypassword, $database);
if ($mysqli->connect_errno) {
	echo "Failed to connect to database: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}
$mysqli->set_charset("utf8");

header('Content-Type: text/xml');
print("<?xml version=\"1.0\"  encoding=\"UTF-8\"?><root>");
print(convertToXMLTag("result", "0"));
print("<resultlist>");

if ($s != "") {
	$res = $mysqli->query($s);

	$res->data_seek(0);
	while ($row = $res->fetch_assoc()) {
		print("<row>");
		foreach ($row as $key => $value) {
			print(convertToXMLTagWithAttributes("value",['key'=>$key],htmlspecialchars($value)));
		}
		print("</row>");
	}
}

print("</resultlist>");

print("<console>" . $console . "</console>");
print("</root>");

mysqli_close($mysqli);