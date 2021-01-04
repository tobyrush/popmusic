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
	if (preg_match('/(.+)_([a-z]{2,3})\.clt/', $filenameString, $result)) {
		//is there a song that has this filename as it's identifier?
		$res = $mysqli->query('SELECT song FROM song_id WHERE (database_name="rs500" OR database_name="rs5x20" OR database_name="rs200") AND identifier="' . $result[1] . '"');
		if ($res->num_rows) { // if so,
			$res->data_seek(0);
			$songID = $res->fetch_object()->song; // capture the song ID
		// 	// then capture existing analyzer ID, creating a new record if necessary
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
			// remove any existing chord_spans and tonic_spans for this song (which should remove associated spans)
			$res = $mysqli->query('DELETE FROM chord_span USING chord_span,span WHERE chord_span.span=span.id AND span.song=' . $songID . ' AND span.analyzer=' . $analyzerID);
			$res = $mysqli->query('DELETE FROM tonic_span USING tonic_span,span WHERE tonic_span.span=span.id AND span.song=' . $songID . ' AND span.analyzer=' . $analyzerID);
			$lineNumber = 1;
			$spanValues = $chordSpanVars = $chordSpanValues = $tonicSpanValues = $tonicSpanVars = [];
			while (($currentLine = fgetcsv($handle, 0, "\t")) !== FALSE) {
				if ($lineNumber>1) { // we need ending time values, so we read line n and line n-1
					$chordAnalysis = $previousLine[2];
					$st = $previousLine[0];
					$et = $currentLine[0];
					$sm = floor($previousLine[1]);
					$em = floor($currentLine[1]);
					$smf = $previousLine[1]-$sm;
					$emf = $currentLine[1]-$em;
					$spanValues[] = '(' . $songID . ',' . $st . ',' . $et . ',' . $sm . ',' . $em . ',' . $smf . ',' . $emf. ',"' . $chordAnalysis . '",' . $analyzerID . ')';
					$chordInfo = parseRSChord($mysqli, $chordAnalysis,$previousLine[3],$previousLine[4]); // returns $chordFunctionID, $chordTypeID, $inversion
					$chordSpanVars[] = [$chordInfo[0],$chordInfo[1],$previousLine[6],$chordInfo[2],$previousLine[5]];
					
					if ($currentLine[2] == "End" || $currentLine[5] != $previousLine[5]) {
						$tonicSpanValues[] = '(' . $songID . ',' . $currentTonic->startTime . ',' . $et . ',' . $currentTonic->startMeasure . ',' . $em . ',' . $currentTonic->startMeasureFraction . ',' . $emf. ',"' . getNoteName($currentTonic->tonic,"") . '",' . $analyzerID . ')';
						$tonicSpanVars[] = $currentTonic->tonic;
						$currentTonic->tonic = $currentLine[5];
						$currentTonic->startTime = $currentLine[0];
						$currentTonic->startMeasure = floor($currentLine[1]);
						$currentTonic->startMeasureFraction = $currentLine[1]-floor($currentLine[1]);
					}
				} else {
					$currentTonic->tonic = $currentLine[5];
					$currentTonic->startTime = $currentLine[0];
					$currentTonic->startMeasure = floor($currentLine[1]);
					$currentTonic->startMeasureFraction = $currentLine[1]-floor($currentLine[1]);
				}
				$previousLine = $currentLine;
				$lineNumber += 1;
			}
			$res = $mysqli->query('INSERT INTO span (song,start_time,end_time,start_measure,end_measure,start_measure_fraction,end_measure_fraction,name,analyzer) VALUES ' . implode(', ',$spanValues));
			$spanID = $mysqli->insert_id;
			foreach ($chordSpanVars as $v) {
				$chordSpanValues[] = '(' . $spanID . ',' . $v[0] . ',' . $v[1] . ',' . $v[2] . ',' . $v[3] . ',' . $v[4] . ')';
				$spanID += 1;
			}
			$res = $mysqli->query('INSERT INTO chord_span (span,chord_function,chord_type,absolute_root,inversion,tonic) VALUES ' . implode(', ',$chordSpanValues));
			
			$res = $mysqli->query('INSERT INTO span (song,start_time,end_time,start_measure,end_measure,start_measure_fraction,end_measure_fraction,name,analyzer) VALUES ' . implode(', ',$tonicSpanValues));
			$spanID = $mysqli->insert_id;
			foreach ($tonicSpanVars as $v) {
				$tonicSpanVarValues[] = '(' . $v . ',' . $spanID . ')';
				$spanID += 1;
			}
			$res = $mysqli->query('INSERT INTO tonic_span (tonic,span) VALUES ' . implode(', ',$tonicSpanVarValues));
		}
	}
}

header('Content-Type: text/xml');
print("<?xml version=\"1.0\"  encoding=\"UTF-8\"?><root>");
print(convertToXMLTag("result", "0"));
print("<console>" . $console . "</console>");
print("</root>");

mysqli_close($mysqli);