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
	$startTime = $newMeter = $newTonic = null;
	$measureNumber = 1;
	while($cl = fgets($handle)) {
		if (preg_match('/^#\s(.+):\s(.+)/', $pl, $ma)) {
			switch ($ma[1]) {
				case 'title':
					$song->title = $ma[2];
					$newSong = true;
					break;
				case 'artist':
					$song->artist = $ma[2];
					$newSong = true;
					break;
				case 'metre':
				case 'meter':
					$newMeter = $ma[2];
					break;
				case 'tonic':
					$newTonic = $ma[2];
					break;
			}
		} elseif (preg_match('/^([\d.]+)\t(.+)/', $pl, $ma)){
			$endTime = $ma[1];
			if ($newSong) {
				$song->meters = [];
				$song->tonics = [];
				$newSong = false;
			}
			if ($newMeter) {
				if ($currentMeter->startTime) {
					$song->meters[] = [$currentMeter->meter, $currentMeter->startTime, $endTime, $currentMeter->startMeasure, $measureNumber, 1, 1];
				}
				$currentMeter->meter = $newMeter;
				$currentMeter->startTime = $endTime;
				$currentMeter->startMeasure = $measureNumber;
				$newMeter = null;
			}
			if ($newTonic) {
				if ($currentTonic->startTime) {
					$song->tonics[] = [$currentTonic->tonic, $currentTonic->startTime, $endTime, $currentTonic->startMeasure, $measureNumber, 1, 1];
				}
				$currentTonic->tonic = getPitchClassFromNoteName($newTonic);
				$currentTonic->tonicLetter = $newTonic;
				$currentTonic->startTime = $endTime;
				$currentTonic->startMeasure = $measureNumber;
				$newTonic = null;
			}
			
			$analyses = explode(", ",$analysisBlock);
			foreach ($analyses as $aItem) {
				if (preg_match('/\|/', $aItem)) { // chord annotations section
					$measures = explode('|', $aItem);
					array_shift($measures); // these blocks start with |, so we strip off $measures[0] since it's empty
					if (preg_match('/->/', $measures[count($measures)-1])) {
						$currentPhrase->elided = true;
					}
					if (preg_match('/x(\d+)/', $measures[count($measures)-1], $reps)) {
						array_pop($measures);
						$copy = $measures;
						for ($i=1; $i < $reps[1]; $i++) { // we use < instead of <= because we actually want to copy $reps[1]-1 times
							$measures = array_merge($measures, $copy);
						}
					} else {
						array_pop($measures);
					}
					$timeCursor = $startTime;
					$beatsInGlobalMeter = getNumberOfBeats($currentMeter->meter);
					$beatsInPhrase = $beatsInGlobalMeter * count($measures);
					if (preg_match_all('/\((\d*\/\d*)\)/', $aItem, $meterMatches)) {
						foreach ($meterMatches[1] as $m) {
							$beatsInPhrase -= $beatsInGlobalMeter;
							$beatsInPhrase += getNumberOfBeats($m);
						}
					}
					$beatLength = ($endTime-$startTime)/$beatsInPhrase;
					$currentChord = null;
					foreach ($measures as $measure) {
						$chords = explode(" ", trim($measure));
						$beatNumber = 1;
						$beats = $beatsInGlobalMeter;
						if (preg_match('/^\(/', trim($chords[0]))) {
							$beats = getNumberOfBeats(trim($chords[0], " ()"));
							array_shift($chords);
						}
						foreach ($chords as $chord) {
							if ($chord != $currentChord->chord && $chord != ".") {
								if ($currentChord->startTime) {
									$song->chords[] = [$currentChord->chord, $currentChord->startTime, $timeCursor, $currentChord->startMeasure, $measureNumber, $currentChord->startBeat, $beatNumber, $currentTonic->tonic, $currentTonic->tonicLetter];
									$currentChord = null;
								}
								if ($chord != "N") {
									$currentChord->chord = $chord;
									$currentChord->startTime = $timeCursor;
									$currentChord->startMeasure = $measureNumber;
									$currentChord->startBeat = $beatNumber;
								}
							}
							if (count($chords)==1) {
								$timeCursor += $beatLength * $beats;
							} elseif (count($chords)==2) {
								$beatNumber += $beats/2;
								$timeCursor += $beatLength * ($beats/2);
							} else {
								$beatNumber += 1;
								$timeCursor += $beatLength;
							}
						}
						$measureNumber += 1;
					}
				} elseif (preg_match('/[\(\)]/', $aItem)) { // lead instrument section
					preg_match('/(\(?)([^\)]+)(\)?)/', $aItem, $liPart);
					if ($liPart[1]) { // has open parenthesis
						$currentInstruments[$liPart[2]]->startTime = $startTime;
						$currentInstruments[$liPart[2]]->startMeasure = $measureNumber;
					}
					if ($liPart[3]) { // has close parenthesis
						$song->instruments[] = [$liPart[2],$currentInstruments[$liPart[2]]->startTime,$endTime, $currentInstruments[$liPart[2]]->startMeasure, $measureNumber, 1, 1];
					}
				} elseif (preg_match("/[A-Z']+/", $aItem)) { // letter form analysis
					if ($currentLetter->startTime) {
						$song->letters[] = [$currentLetter->letter, $currentLetter->startTime, $startTime, $currentLetter->startMeasure, $measureNumber, 1, 1];
						$newLetter = null;
					}
					$currentLetter->letter = $aItem;
					$currentLetter->startTime = $startTime;
					$currentLetter->startMeasure = $measureNumber;
				} elseif (trim($aItem)) { // structural form analysis
					if ($currentStructure->startTime) {
						$song->structures[] = [$currentStructure->structure, $currentStructure->startTime, $startTime, $currentStructure->startMeasure, $measureNumber, 1, 1];
						$newStructure = null;
					}
					$currentStructure->structure = $aItem;
					$currentStructure->startTime = $startTime;
					$currentStructure->startMeasure = $measureNumber;
				}
			}
		}
		if ($currentChord->startTime) { // commit current chord before move to next phrase
			$song->chords[] = [$currentChord->chord, $currentChord->startTime, $timeCursor, $currentChord->startMeasure, $measureNumber, $currentChord->startBeat, $beatNumber, $currentTonic->tonic, $currentTonic->tonicLetter];
			$currentChord = null;
		}
		$startTime = $endTime;
		$analysisBlock = $ma[2];
		$pl = $cl;
	}
	if ($currentMeter->startTime) {
		$song->meters[] = [$currentMeter->meter, $currentMeter->startTime, $timeCursor, $currentMeter->startMeasure, $measureNumber, 1, 1];
	}
	if ($currentTonic->startTime) {
		$song->tonics[] = [$currentTonic->tonic, $currentTonic->startTime, $timeCursor, $currentTonic->startMeasure, $measureNumber, 1, 1];
	}
	
	// var_dump($song);
	
	$analyzerID = getAnalyzerID($mysqli, "McGill DDMAL");
	// $res = $mysqli->query('SELECT id FROM analyzer WHERE name="McGill DDMAL"');
	// if ($res->num_rows) {
	// 	$res->data_seek(0);
	// 	$analyzerID = $res->fetch_object()->id;
	// } else {
	// 	$res = $mysqli->query('INSERT INTO analyzer (name) VALUES ("McGill DDMAL")');
	// 	$analyzerID = $mysqli->insert_id;
	// }
	$songID=getSongID($mysqli, $song->artist, $song->title);
	// $res = $mysqli->query('SELECT id FROM artist WHERE name="' . $song->artist . '"');
	// if ($res->num_rows) {
	// 	$res->data_seek(0);
	// 	$artistID = $res->fetch_object()->id;
	// } else {
	// 	$res = $mysqli->query('INSERT INTO artist (name) VALUES ("' . $song->artist . '")');
	// 	$artistID = $mysqli->insert_id;
	// }
	// $res = $mysqli->query('SELECT id FROM song WHERE title="' . $song->title . '" AND artist=' . $artistID);
	// if ($res->num_rows) {
	// 	$res->data_seek(0);
	// 	$songID = $res->fetch_object()->id;
	// } else {
	// 	$res = $mysqli->query('INSERT INTO song (title,artist) VALUES ("' . $song->title . '",' . $artistID . ')');
	// 	$songID = $mysqli->insert_id;
	// }
	
	$res = $mysqli->query("DELETE FROM span WHERE song=" . $songID . " AND analyzer=" . $analyzerID);
	// this should also delete associated meter_span, tonic_span, form_span entries
	
	$spanValues = $meterSpanVars = $meterSpanValues = [];
	foreach ($song->meters as $i) {
		$spanValues[] = '(' . $songID . ',' . $i[1] . ',' . $i[2] . ',' . $i[3] . ',' . $i[4] . ',' . $i[5] . ',' . $i[6] . ',"' . $i[0] . '",' . $analyzerID . ')';
		$meterSpanVars[] = getMeterID($mysqli, $i[0]);
	}
	if (count($spanValues)) {
		$res = $mysqli->query('INSERT INTO span (song,start_time,end_time,start_measure,end_measure,start_beat,end_beat,name,analyzer) VALUES ' . implode(",", $spanValues));
		$spanID = $mysqli->insert_id;
		foreach ($meterSpanVars as $i) {
			$meterSpanValues[] = '(' . $i . ',' . $spanID . ')';
			$spanID += 1;
		}
		$res = $mysqli->query('INSERT INTO meter_span (meter,span) VALUES ' . implode(",", $meterSpanValues));
	}
	
	$spanValues = $tonicSpanVars = $tonicSpanValues = [];
	foreach ($song->tonics as $i) {
		$spanValues[] = '(' . $songID . ',' . $i[1] . ',' . $i[2] . ',' . $i[3] . ',' . $i[4] . ',' . $i[5] . ',' . $i[6] . ',"' . getNoteName($i[0],"") . '",' . $analyzerID . ')';
		$tonicSpanVars[] = $i[0];
	}
	if (count($spanValues)) {
		$res = $mysqli->query('INSERT INTO span (song,start_time,end_time,start_measure,end_measure,start_beat,end_beat,name,analyzer) VALUES ' . implode(",", $spanValues));
		$spanID = $mysqli->insert_id;
		foreach ($tonicSpanVars as $i) {
			$tonicSpanValues[] = '(' . $i . ',' . $spanID . ')';
			$spanID += 1;
		}
		$res = $mysqli->query('INSERT INTO tonic_span (tonic,span) VALUES ' . implode(",", $tonicSpanValues));
	}
	
	$spanValues = $formSpanVars = $formSpanValues = [];
	foreach ($song->structures as $i) {
		$spanValues[] = '(' . $songID . ',' . $i[1] . ',' . $i[2] . ',' . $i[3] . ',' . $i[4] . ',' . $i[5] . ',' . $i[6] . ',"' . $i[0] . '",' . $analyzerID . ')';
		$formSpanVars[] = $i[0];
	}
	foreach ($song->letters as $i) {
		$spanValues[] = '(' . $songID . ',' . $i[1] . ',' . $i[2] . ',' . $i[3] . ',' . $i[4] . ',' . $i[5] . ',' . $i[6] . ',"' . $i[0] . '",' . $analyzerID . ')';
		$formSpanVars[] = $i[0];
	}
	if (count($spanValues)) {
		$res = $mysqli->query('INSERT INTO span (song,start_time,end_time,start_measure,end_measure,start_beat,end_beat,name,analyzer) VALUES ' . implode(",", $spanValues));
		$spanID = $mysqli->insert_id;
		foreach ($formSpanVars as $i) {
			$formSpanValues[] = '("' . $i . '",' . $spanID . ')';
			$spanID += 1;
		}
		$res = $mysqli->query('INSERT INTO form_span (label,span) VALUES ' . implode(",", $formSpanValues));
	}
	
	$spanValues = $chordSpanVars = $chordSpanValues = $chordFunctionDictionary = [];
	foreach ($song->chords as $i) {
		$spanValues[] = '(' . $songID . ',' . $i[1] . ',' . $i[2] . ',' . $i[3] . ',' . $i[4] . ',' . $i[5] . ',' . $i[6] . ',"' . $i[0] . '",' . $analyzerID . ')';
		$chordSpanVars[] = parseMcGillChord($mysqli, $i[0], $i[8]);
	}
	if (count($spanValues)) {
		$res = $mysqli->query('INSERT INTO span (song,start_time,end_time,start_measure,end_measure,start_beat,end_beat,name,analyzer) VALUES ' . implode(",", $spanValues));
		$spanID = $mysqli->insert_id;
		foreach ($chordSpanVars as $i) {
			$chordSpanValues[] = '(' . $spanID . ',' . $i[0] . ',' . $i[1] . ',' . $i[2] . ',' . $i[3] . ',' . $i[4] . ')';
			$spanID += 1;
		}
		$res = $mysqli->query('INSERT INTO chord_span (span,chord_function,chord_type,absolute_root,inversion,tonic) VALUES ' . implode(",", $chordSpanValues));
	}
}

header('Content-Type: text/xml');
print("<?xml version=\"1.0\"  encoding=\"UTF-8\"?><root>");
print(convertToXMLTag("result", "0"));
print("<console>" . $console . "</console>");
print("</root>");

mysqli_close($mysqli);