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
	$res = $mysqli->query('SELECT id FROM chart WHERE name="Billboard Hot 100"');
	if ($res->num_rows) {
		$res->data_seek(0);
		$chartID = $res->fetch_object()->id;
	} else {
		$res = $mysqli->query('INSERT INTO chart (name) VALUES ("Billboard Hot 100")');
		$chartID = $mysqli->insert_id;
	}
	$songIDValues = [];
	$chartEntryValues = [];
	$chartHistoryValues = [];
	$res = $mysqli->query('DELETE FROM song_id WHERE database_name="McGill"');
	while (($ln = fgetcsv($handle, 0, ",")) !== FALSE) {
		if ($ln[5] && $ln[0]!="id") {
			$songID = getSongID($mysqli, $ln[5], $ln[4]);
			// $res = $mysqli->query('SELECT id FROM artist WHERE name="' . $ln[5] . '"');
			// if ($res->num_rows) {
			// 	$res->data_seek(0);
			// 	$artistID = $res->fetch_object()->id;
			// } else {
			// 	$res = $mysqli->query('INSERT INTO artist (name) VALUES ("' . $ln[5] . '")');
			// 	$artistID = $mysqli->insert_id;
			// }
			// $res = $mysqli->query('SELECT id FROM song WHERE title="' . $ln[4] . '" AND artist=' . $artistID);
			// if ($res->num_rows) {
			// 	$res->data_seek(0);
			// 	$songID = $res->fetch_object()->id;
			// } else {
			// 	$res = $mysqli->query('INSERT INTO song (title,artist) VALUES ("' . $ln[4] . '",' . $artistID . ')');
			// 	$songID = $mysqli->insert_id;
			// }
			$songIDValues[] = '("' . $ln[0] . '",' . $songID . ',"McGill")';
			$res = $mysqli->query('DELETE FROM chart_entry WHERE song=' . $songID . ' AND date_of_entry="' . $ln[1] . '" AND chart=' . $chartID);
			$chartEntryValues[] = '("' . $ln[1] . '",' . $ln[2] . ',' . $ln[3] . ',' . $songID . ',' . $chartID . ')';
			$res = $mysqli->query('DELETE FROM chart_history WHERE song=' . $songID . ' AND chart=' . $chartID);
			$chartHistoryValues[] = '(' . $ln[6] . ',' . $ln[7] . ',' . $songID . ',' . $chartID . ')';
		}
	}
	$res = $mysqli->query('INSERT INTO song_id (identifier,song,database_name) VALUES ' . implode(",", $songIDValues));
	$res = $mysqli->query('INSERT INTO chart_entry (date_of_entry,target_rank,actual_rank,song,chart) VALUES ' . implode(",", $chartEntryValues));
	$res = $mysqli->query('INSERT INTO chart_history (peak_rank,weeks_on_chart,song,chart) VALUES ' . implode(",", array_unique($chartHistoryValues)));
}

header('Content-Type: text/xml');
print("<?xml version=\"1.0\"  encoding=\"UTF-8\"?><root>");
print(convertToXMLTag("result", "0"));
print("<console>" . $console . "</console>");
print("</root>");

mysqli_close($mysqli);