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
	$data = json_decode(stream_get_contents($handle));
	$metadata = $data->file_metadata;
	$songID = getSongID($mysqli, $metadata->artist, $metadata->title)
	$res = $mysqli->query('UPDATE song SET duration=' . round($metadata->duration) . 'WHILE id=' . $songID);
	// $res = $mysqli->query('SELECT id FROM artist WHERE name="' . $metadata->artist . '"');
	// if ($res->num_rows) {
	// 	$res->data_seek(0);
	// 	$artistID = $res->fetch_object()->id;
	// } else {
	// 	$res = $mysqli->query('INSERT INTO artist (name) VALUES ("' . $metadata->artist . '")');
	// 	$artistID = $mysqli->insert_id;
	// }
	// $res = $mysqli->query('SELECT id FROM song WHERE title="' . $metadata->title . '" AND artist=' . $artistID);
	// if ($res->num_rows) {
	// 	$res->data_seek(0);
	// 	$songID = $res->fetch_object()->id;
	// 	$res = $mysqli->query('UPDATE song SET duration=' . round($metadata->duration));
	// } else {
	// 	$res = $mysqli->query('INSERT INTO song (title,artist,duration) VALUES ("' . $metadata->title . '",' . $artistID . ',' . round($metadata->duration) . ')');
	// 	$songID = $mysqli->insert_id;
	// }
	$res = $mysqli->query('DELETE FROM song_id WHERE song=' . $songID . ' AND database_name="YouTube"');
	$res = $mysqli->query('INSERT INTO song_id (uri,song,database_name) VALUES ("' . $metadata->identifiers->youtube_url . '",' . $songID . ',"YouTube")');
	
	$chordDictionary = [];
	foreach ($data->annotations as $annotation) {
		$analyzerID = getAnalyzerID($mysqli, $annotation->annotation_metadata->annotator->id);
		// $annotator = $annotation->annotation_metadata->annotator->id;
		// $res = $mysqli->query('SELECT id FROM analyzer WHERE name="CASD Annotator ' . $annotator . '"');
		// if ($res->num_rows) {
		// 	$res->data_seek(0);
		// 	$analyzerID = $res->fetch_object()->id;
		// } else {
		// 	$res = $mysqli->query('INSERT INTO analyzer (name) VALUES ("CASD Annotator ' . $annotator . '")');
		// 	$analyzerID = $res->insert_id;
		// }
		$spanValues = $chordSpanVars = [];
		foreach ($annotation->data as $chord) {
			if ($chord->value != "N") {
				$spanValues[] = '(' . $songID . ',' . $chord->time . ',' . ($chord->time + $chord->duration) . ',"' . $chord->value . '",' . $analyzerID . ')';
				if (array_key_exists($chord->value, $chordDictionary)) {
					$chordSpanVars[] = $chordDictionary[$chord->value];
				} else {
					$chordDictionary[$chord->value] = parseCASDChord($mysqli, $chord->value);
					$chordSpanVars[] = $chordDictionary[$chord->value];
				}
				// $chordSpanVars[] = parseCASDChord($mysqli, $chord->value); //returns $chordTypeID, $absoluteRoot, $inversion
			}
		}
		$res = $mysqli->query("DELETE FROM span WHERE song=" . $songID . " AND analyzer=" . $analyzerID);
		if (count($spanValues)) {
			$res = $mysqli->query('INSERT INTO span (song,start_time,end_time,name,analyzer) VALUES ' . implode(",", $spanValues));
			$spanID = $mysqli->insert_id;
			foreach ($chordSpanVars as $i) {
				$chordSpanValues[] = '(' . $spanID . ',' . $i[0] . ',' . $i[1] . ',' . $i[2] . ')';
				$spanID += 1;
			}
			$res = $mysqli->query('INSERT INTO chord_span (span,chord_type,absolute_root,inversion) VALUES ' . implode(",", $chordSpanValues));
		}
	}
}

header('Content-Type: text/xml');
print("<?xml version=\"1.0\"  encoding=\"UTF-8\"?><root>");
print(convertToXMLTag("result", "0"));
print("<console>" . $console . "</console>");
print("</root>");

mysqli_close($mysqli);