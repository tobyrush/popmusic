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
	while (($data = fgetcsv($handle, 0, "\t")) !== FALSE) {
		$res = $mysqli->query('SELECT id FROM artist WHERE name="' . $data[2] . '"');
		if ($res->num_rows) {
			$res->data_seek(0);
			$artistID = $res->fetch_object()->id;
		} else {
			$res = $mysqli->query('INSERT INTO artist (name) VALUES ("' . $data[2] . '")');
			$artistID = $mysqli->insert_id;
		}
		$res = $mysqli->query('SELECT id FROM song WHERE title="' . $data[1] . '" AND artist=' .$artistID);
		if ($res->num_rows == 0) {
			$songValues[] = '("' . $data[1] . '","' . $artistID . '","' . $data[3] . '-00-00")';
		} else {
			$res->data_seek(0);
			$songID = $res->fetch_object()->id;
			$res = $mysqli->query('UPDATE song WHERE id=' . $songID . ' SET release_date="' . $data[3] . '-00-00');
		}
		print("\n");
	}
	$res = $mysqli->query('INSERT INTO song (title, artist, release_date) VALUES ' . implode(",", $songValues));
}

header('Content-Type: text/xml');
print("<?xml version=\"1.0\"  encoding=\"UTF-8\"?><root>");
print(convertToXMLTag("result", "0"));
print("<console>" . $console . "</console>");
print("</root>");

mysqli_close($mysqli);