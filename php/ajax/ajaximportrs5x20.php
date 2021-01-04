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
	$songValues = [];
	$songIDVars = [];
	$songIDValues = [];
	$res = $mysqli->query('DELETE FROM song_id WHERE database_name="rs5x20"');
	while (($data = fgetcsv($handle, 0, "\t")) !== FALSE) {
		$res = $mysqli->query('SELECT id FROM artist WHERE name="' . $data[3] . '"');
		if ($res->num_rows) {
			$res->data_seek(0);
			$artistID = $res->fetch_object()->id;
		} else {
			$res = $mysqli->query('INSERT INTO artist (name) VALUES ("' . $data[3] . '")');
			$artistID = $mysqli->insert_id;
		}
		$res = $mysqli->query('SELECT id FROM song WHERE title="' . $data[2] . '" AND artist=' .$artistID);
		if ($res->num_rows) {
			$res->data_seek(0);
			$songID = $res->fetch_object()->id;
			$res = $mysqli->query('UPDATE song SET release_date="' . $data[4] . '-00-00');
			$res = $mysqli->query('INSERT INTO song_id (identifier, song, database_name) VALUES ("' . $data[0] . '",' . $songID . ',"rs5x20")');
		} else {
			$songValues[] = '("' . $data[2] . '","' . $artistID . '","' . $data[4] . '-00-00")';
			$songIDVars[] = $data[0];
		}
	}
	$res = $mysqli->query('INSERT INTO song (title, artist, release_date) VALUES ' . implode(",", $songValues));
	$songID = $mysqli->insert_id;
	foreach ($songIDVars as $v) {
		$songIDValues[] = '(' . $v[0] . ',' . $songID . ',"rs5x20")';
		$songID += 1;
	}
	$res = $mysqli->query('INSERT INTO song_id (identifier, song, database_name) VALUES ' . implode(', ',$songIDValues));
}

header('Content-Type: text/xml');
print("<?xml version=\"1.0\"  encoding=\"UTF-8\"?><root>");
print(convertToXMLTag("result", "0"));
print("<console>" . $console . "</console>");
print("</root>");

mysqli_close($mysqli);