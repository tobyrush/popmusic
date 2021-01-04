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
	if (preg_match('/(.+)\.str/', $filenameString, $result)) {
		//is there a song that has this filename as it's identifier?
		$res = $mysqli->query('SELECT song FROM song_id WHERE (database_name="rs500" OR database_name="rs5x20" OR database_name="rs200") AND identifier="' . $result[1] . '"');
		if ($res->num_rows) { // if so,
			$res->data_seek(0);
			$songID = $res->fetch_object()->song; // capture the song ID
			$res = $mysqli->query('SELECT id FROM instrument WHERE name="lead vocals"');
			if ($res->num_rows) {
				$instrumentID = $res->fetch_object()->id;
				$syllableValues = [];
				$total = 0;
				$matched = 0;
				while (($currentLine = fgetcsv($handle, 0, " ")) !== FALSE) {
					$sm = floor($currentLine[1]);
					$smf = round($currentLine[1]-$sm,2);
					$res = $mysqli->query('SELECT note_span.id AS note_id FROM note_span,span WHERE note_span.instrument=' . $instrumentID . ' AND note_span.span=span.id AND span.song=' . $songID . ' AND span.start_measure=' . $sm . ' AND ABS(span.start_measure_fraction-' . $smf . ')<0.1');
					$total += 1;
					if ($res->num_rows) {
						$matched += 1;
						$noteSpanID = $res->fetch_object()->note_id;
						if (preg_match('/(.+)\[([0-9]+)\]/', $currentLine[5], $result)) {
							// var_dump($result);
							$word = $result[1];
							$syllableIndex = $result[2];
							if ($currentLine[5]==$previousWord) {
								$isMelisma += 1;
							} else {
								$isMelisma = 1;
								$previousWord = $currentLine[5];
							}
							$syllableValues[] = '("' . $word . '",' . $syllableIndex . ',' . $noteSpanID . ',' . $isMelisma . ')';
						}
					} else {
						$res = $mysqli->query('INSERT INTO errors (error) VALUES ("sm ' . $sm . ', smf ' . $smf . '")');
					}
				}
				if ($matched/$total > .9) { // if we have at least 90% success rate aligning syllables with notes
					// remove any existing chord_spans for this song (which should remove associated spans)
					$res = $mysqli->query('DELETE FROM syllable USING note_span,span WHERE syllable.note=note_span.id AND note_span.span=span.id AND span.song=' . $songID);
					$res = $mysqli->query('INSERT INTO syllable (word,syllable_index,note,is_melisma) VALUES ' . implode(', ',$syllableValues));
				} else {
					$res = $mysqli->query('INSERT INTO errors (error) VALUES ("song ID ' . $songID . ' had ' . $matched . ' out of ' . $total . ' matches and was skipped")');
				}
			}
		}
	}
}

header('Content-Type: text/xml');
print("<?xml version=\"1.0\"  encoding=\"UTF-8\"?><root>");
print(convertToXMLTag("result", "0"));
print("<console>" . $console . "</console>");
print("</root>");

mysqli_close($mysqli);