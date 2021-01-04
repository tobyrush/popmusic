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
	if (preg_match('/(.+)_([a-z]{2,3})\.nlt/', $filenameString, $result)) {
		//is there a song that has this filename as it's identifier?
		$res = $mysqli->query('SELECT song FROM song_id WHERE (database_name="rs500" OR database_name="rs5x20" OR database_name="rs200") AND identifier="' . $result[1] . '"');
		if ($res->num_rows) { // if so,
			$res->data_seek(0);
			$songID = $res->fetch_object()->song; // capture the song ID
			// then capture existing analyzer ID, creating a new record if necessary
			if ($result[2]=='tdc') {
				$analyzerString = "Trevor de Clercq";
			} elseif ($result[2]=='dt') {
				$analyzerString = "David Temperley";
			} else {
				$analyzerString = $result[2];
			}
			$analyzerID = getAnalyzerID($mysqli, $analyzerString);
			// $res = $mysqli->query('SELECT id FROM analyzer WHERE name="' . $analyzerString . '"');
			// if ($res->num_rows) {
			// 	$res->data_seek(0);
			// 	$analyzerID = $res->fetch_object()->id;
			// } else {
			// 	$res = $mysqli->query('INSERT INTO analyzer (name) VALUES ("' . $analyzerString . '")');
			// 	$analyzerID = $mysqli->insert_id;
			// }
			// remove any existing chord_spans for this song (which should remove associated spans)
			$res = $mysqli->query('DELETE FROM note_span USING note_span,span WHERE note_span.span=span.id AND span.song=' . $songID . ' AND span.analyzer=' . $analyzerID);
			$res = $mysqli->query('SELECT id FROM instrument WHERE name="lead vocals"');
			if ($res->num_rows) {
				$instrumentID = $res->fetch_object()->id;
			} else {
				$res = $mysqli->query('INSERT INTO instrument (name) VALUES ("lead vocals")');
				$instrumentID = $res->insert_id;
			}
			$spanValues = [];
			$noteSpanVars = [];
			$noteSpanValues = [];
			while (($currentLine = fgetcsv($handle, 0, "\t")) !== FALSE) {
				$st = $currentLine[0];
				$sm = floor($currentLine[1]);
				$smf = $currentLine[1]-$sm;
				$pc = getPitchClassFromMIDIPitch($currentLine[2]);
				$oc = getOctaveFromMIDIPitch($currentLine[2]);
				$noteName = getNoteName($pc,$oc);
				$spanValues[] = '(' . $songID . ',' . $st . ',' . $sm . ',' . $smf . ',"' . $noteName . '",' . $analyzerID . ')';
				$noteSpanVars[] = [$pc,$oc];
			}
			$res = $mysqli->query('INSERT INTO span (song,start_time,start_measure,start_measure_fraction,name,analyzer) VALUES ' . implode(', ',$spanValues));
			$spanID = $mysqli->insert_id;
			foreach ($noteSpanVars as $v) {
				$noteSpanValues[] = '(' . $v[0] . ',' . $v[1] . ',' . $instrumentID . ',' . $spanID . ')';
				$spanID += 1;
			}
			$res = $mysqli->query('INSERT INTO note_span (pitch_class,octave,instrument,span) VALUES ' . implode(', ',$noteSpanValues));
		}
	}
}

header('Content-Type: text/xml');
print("<?xml version=\"1.0\"  encoding=\"UTF-8\"?><root>");
print(convertToXMLTag("result", "0"));
print("<console>" . $console . "</console>");
print("</root>");

mysqli_close($mysqli);