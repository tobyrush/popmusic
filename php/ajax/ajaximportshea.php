<?php

include '../config.php';
include '../utility.php';

$mysqli = new mysqli($sqlserver, $adminuser, $adminpassword, $database);
if ($mysqli->connect_errno) {
	echo "Failed to connect to database: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}

if ( 0 < $_FILES['file']['error']) {
	echo 'Error: ' . $_FILES['file']['error'] . '<br>';
} else {
	$handle = fopen($_FILES['file']['tmp_name'], 'r');
	$filenameString = $_FILES['file']['name'];
	
	$songIDValues = $spanValues = $formSpanVars = $formSpanValues = [];
	while (($ln = fgetcsv($handle, 0, "\t")) !== FALSE) {
		if ($ln[9] && $ln[0]!="Annotator") {
			$songID = getSongID($mysqli, $ln[3], $ln[2]);
			$analyzerID = getAnalyzerID($mysqli, 'Shea, et al: ' . $ln[0]);
			$songIDValues[] = '("' . $ln[1] . '",null,' . $songID . ',"Guitar Pro")';
			if (substr($ln[7],0,5)=='https') {
				$songIDValues[] = '(null,"' . $ln[7] . '",' . $songID . ',"Spotify")';
			}
			$c=9;
			while ($ln[$c+3]) {
				$spanValues[] = '(' . $songID . ',' . $ln[$c+1] . ',' . $ln[$c+3] . ',"' . $ln[$c] . '",' . $analyzerID . ')';
				$formSpanVars[] = $ln[$c];
				$c += 2;
			}
			$spanValues[] = '(' . $songID . ',' . $ln[$c+1] . ',null,"' . $ln[$c] . '",' . $analyzerID . ')';
			$formSpanVars[] = $ln[$c];
		}
	}
	$res = $mysqli->query('INSERT INTO song_id (identifier,uri,song,database_name) VALUES ' . implode(",", $songIDValues));
	$res = $mysqli->query('INSERT INTO span (song,start_measure,end_measure,name,analyzer) VALUES ' . implode(",", $spanValues));
	$spanID = $mysqli->insert_id;
	foreach ($formSpanVars as $i) {
		$formSpanValues[] = '("' . $i . '",' . $spanID . ')';
		$spanID += 1;
	}
	$res = $mysqli->query('INSERT INTO form_span (label,span) VALUES ' . implode(",", $formSpanValues));
	$res = $mysqli->query('DELETE t1 FROM song_id t1 INNER JOIN song_id t2 WHERE t1.id > t2.id AND t1.uri=t2.uri AND t1.song=t2.song AND t1.database_name=t2.database_name');
	$res = $mysqli->query('DELETE t1 FROM song_id t1 INNER JOIN song_id t2 WHERE t1.id > t2.id AND t1.identifier=t2.identifier AND t1.song=t2.song AND t1.database_name=t2.database_name');
}

header('Content-Type: text/xml');
print("<?xml version=\"1.0\"  encoding=\"UTF-8\"?><root>");
print(convertToXMLTag("result", "0"));
print("<console>" . $console . "</console>");
print("</root>");

mysqli_close($mysqli);