<?php

function convertToXMLTag($tagname, $data, $parameters = null)
{
	$p = "";
	foreach ($parameters as $key => $value) {
		$p = $p . ' ' . $key . '="' . $value . '"';
	}
	return "<$tagname$p>$data</$tagname>\n";
}

function convertToXMLTagWithAttributes($tagname, $attributes, $data) // $attributes: ['attr1'=>'data1','attr2'=>'data2']
{
	$result = "<$tagname";
	foreach ($attributes as $key => $value) {
		$result = $result . " $key=\"$value\"";
	}
	$result = $result . ">$data</$tagname>\n";
	return $result;
}

function parseRSChord($mysqli, $chordAnalysis, $chromaticRoot, $diatonicRoot)
{
	if (preg_match('/(?<!s)([0-9]+)(\/|$|s)/', $chordAnalysis, $result)) {
		$inversionString = $result[1];
		$inversion = getInversion($inversionString);
		$romanNumeral = str_replace($inversionString,getRootPosition($inversionString),$chordAnalysis);
	} else {
		// $inversionString = "";
		$inversion = 0;
		$romanNumeral = $chordAnalysis;
	}
	preg_match('/^([b#]?[IiVv]{1,3})([^\/\s]*)($|\/[IiVv]+)/', $romanNumeral, $result);
	$rootRomanNumeral = strtoupper($result[1]) . $result[3];
	$suffix = $result[2];
	if (preg_match('/[IV]/', $result[1])) {
		$roman_numeral_case = "1";
	} else {
		$roman_numeral_case = "0";
	}
	$res = $mysqli->query('SELECT id FROM chord_function WHERE chromatic_root=' . $chromaticRoot . ' AND diatonic_root=' . $diatonicRoot . ' AND roman_numeral="' . $rootRomanNumeral . '"');
	if ($res->num_rows) {
		$res->data_seek(0);
		$chordFunctionID = $res->fetch_object()->id;
	} else {
		$res = $mysqli->query('INSERT INTO chord_function (roman_numeral, chromatic_root, diatonic_root) VALUES ("' . $rootRomanNumeral . '",' . $chromaticRoot . ',' . $diatonicRoot . ')');
		$chordFunctionID = $res->insert_id;
	}
	
	$res = $mysqli->query('SELECT chord_type FROM chord_symbol WHERE symbol="' . $suffix . '"');
	if ($res->num_rows == 2) {
		$chordTypeID = $res->fetch_object()->chordTypeID;
		$res = $mysqli->query('SELECT chord_type FROM chord_symbol WHERE symbol="' . $suffix . '" AND roman_numeral_case=' . $roman_numeral_case);
	if ($res->num_rows) {
			$chordTypeID = $res->fetch_object()->chordTypeID;
		}
	} elseif ($res->num_rows == 1) {
		$res->data_seek(0);
		$chordTypeID = $res->fetch_object()->chordTypeID;
	} else {
		$res = $mysqli->query('INSERT INTO chord_type (roman_numeral_case,default_symbol) VALUES (' . $roman_numeral_case . ',"' . $suffix . ')' . "\n");
		$chordTypeID = $res->insert_id;
		$res = $mysqli->query('INSERT INTO chord_symbol (symbol,roman_numeral_case,chord_type) VALUES ("' . $suffix . '",' . $roman_numeral_case . ',' . $chordTypeID . ')');
	}
	return [$chordFunctionID,$chordTypeID,$inversion];
}

function getInversion($s)
{
	if ($s == "2" || $s == "42" || $s == "642") {
		return 3;
	} elseif ($s == "43" || $s == "64" || $s == "643") {
		return 2;
	} elseif ($s == "65" || $s == "6" || $s == "653") {
		return 1;
	} else {
		return 0;
	}
}

function getRootPosition($s)
{
	if ($s == "7" || $s == "653" || $s == "65" || $s == "643" || $s == "43" || $s == "642" || $s == "42" || $s == "2") {
		return "7";
	} else if ($s == "64" || $s == "6") {
		return "";
	} else {
		return $s;
	}
}

function getPitchClassFromMIDIPitch($pitch) {
	return $pitch % 12;
}

function getPitchClassFromNoteName($noteName) {
	if (preg_match('/([A-Ha-h#sx]+)/', $noteName, $result)) {
		return [
			'c' => 0, 'b#' => 0, 'dbb' => 0,
			'c#' => 1, 'db' => 1,
			'd' => 2, 'cx' => 2, 'c##' => 2, 'dbb' => 2,
			'd#' => 3, 'eb' => 3, 'fbb' => 3,
			'e' => 4, 'dx' => 4, 'd##' => 4, 'fb' => 4,
			'f' => 5, 'e#' => 5, 'gbb' => 5,
			'f#' => 6, 'gb' => 6, 'ex' => 6, 'e##' => 6, 
			'g' => 7, 'fx' => 7, 'f##' => 7, 'abb' => 7,
			'g#' => 8, 'ab' => 8,
			'a' => 9, 'gx' => 9, 'g##' => 9, 'bbb' => 9, 
			'a#' => 10, 'bb' => 10, 'cbb' => 10,
			'b' => 11, 'ax' => 11, 'a##' => 11, 'cb' => 11, 'h' => 11
		][strtolower($result[1])];
	} else {
		return null;
	}
}

function getOctaveFromMIDIPitch($pitch) {
	return floor($pitch/12)-1;
}

function getNoteName($pitch_class, $octave) {
	return ['C','C#','D','D#','E','F','F#','G','G#','A','A#','B'][$pitch_class] . $octave;
}

function getNumberOfBeats($meterString) {
	$parts = explode("/", $meterString);
	if ($parts[0]==6 || $parts[0]==9 || $parts[0]==12) {
		return $parts[0]/3;
	} else {
		return $parts[0];
	}
}

function getMeterID($mysqli, $meterString) {
	$n = explode('/', $meterString);
	$res = $mysqli->query('SELECT id FROM meter WHERE top_number=' . $n[0] . ' AND bottom_number=' . $n[1]);
	if ($res->num_rows) {
		$res->data_seek(0);
		return $res->fetch_object()->id;
	} else {
		$res = $mysqli->query('INSERT INTO meter (top_number,bottom_number) VALUES (' . $n[0] . ',' . $n[1] . ')');
		return $mysqli->insert_id;
	}
}

function parseMcGillChord($mysqli, $chordString, $tonic) {
	preg_match('/^([^:\/]+):([^:\/]+)\/?[b#]?([0-9]*)$/', $chordString, $result);
	$res = $mysqli->query('SELECT chord_type FROM chord_symbol WHERE symbol="' . $result[2] . '"');
	if ($res->num_rows) {
		$res->data_seek(0);
		$chordTypeID = $res->fetch_object()->chord_type;
	} else {
		$res = $mysqli->query('INSERT INTO chord_type (roman_numeral_case,default_symbol) VALUES (1,"' . $result[2] . '")');
		$chordTypeID = $mysqli->insert_id;
		$res = $mysqli->query('INSERT INTO chord_symbol (symbol,chord_type) VALUES ("' . $result[2] . '",' . $chordTypeID . ')');
	}
	$dRoot = getDiatonicRoot($tonic, $result[1]);
	$cRoot = getChromaticRoot($tonic, $result[1]);
	$res = $mysqli->query('SELECT id FROM chord_function WHERE chromatic_root=' . $cRoot . ' AND diatonic_root=' . $dRoot . ' ORDER BY CHAR_LENGTH(roman_numeral) ASC');
	if ($res->num_rows) {
		$res->data_seek(0);
		$chordFunctionID = $res->fetch_object()->id;
	} else {
		$res = $mysqli->query('INSERT INTO chord_function (chromatic_root,diatonic_root) VALUES (' . $cRoot . ',' . $dRoot . ')');
		$chordFunctionID = $mysqli->insert_id;
	}
	if ($result[3]) {
		$inversion = ($result[3]-1)/2;
	} else {
		$inversion = 0;
	}
	$absoluteRoot = getPitchClassFromNoteName($result[1]);
	$tonicPitchClass = getPitchClassFromNoteName($tonic);
	
	return [$chordFunctionID, $chordTypeID, $absoluteRoot, $inversion, $tonicPitchClass];
}

function parseCASDChord($mysqli, $chordString) {
	preg_match('/^([^:\/]+):([^:\/]+)\/?[b#]?([0-9]*)$/', $chordString, $result);
	$res = $mysqli->query('SELECT chord_type FROM chord_symbol WHERE symbol="' . $result[2] . '"');
	if ($res->num_rows) {
		$res->data_seek(0);
		$chordTypeID = $res->fetch_object()->chord_type;
	} else {
		$res = $mysqli->query('INSERT INTO chord_type (roman_numeral_case,default_symbol) VALUES (1,"' . $result[2] . '")');
		$chordTypeID = $mysqli->insert_id;
		$res = $mysqli->query('INSERT INTO chord_symbol (symbol,chord_type) VALUES ("' . $result[2] . '",' . $chordTypeID . ')');
	}
	if ($result[3]) {
		$inversion = ($result[3]-1)/2;
	} else {
		$inversion = 0;
	}
	$absoluteRoot = getPitchClassFromNoteName($result[1]);
	
	return [$chordTypeID, $absoluteRoot, $inversion];
}

function getSongID($mysqli, $artist, $title) {
	$res = $mysqli->query('SELECT id FROM artist WHERE name="' . $artist . '"');
	if ($res->num_rows) {
		$res->data_seek(0);
		$artistID = $res->fetch_object()->id;
	} else {
		$res = $mysqli->query('INSERT INTO artist (name) VALUES ("' . $artist . '")');
		$artistID = $mysqli->insert_id;
	}
	$res = $mysqli->query('SELECT id FROM song WHERE title="' . $title . '" AND artist=' . $artistID);
	if ($res->num_rows) {
		$res->data_seek(0);
		return $res->fetch_object()->id;
	} else {
		$res = $mysqli->query('INSERT INTO song (title,artist) VALUES ("' . $title . '",' . $artistID . ')');
		return $mysqli->insert_id;
	}
}

function getAnalyzerID($mysqli, $analyzer) {
	$res = $mysqli->query('SELECT id FROM analyzer WHERE name="' . $analyzer . '"');
	if ($res->num_rows) {
		$res->data_seek(0);
		return $res->fetch_object()->id;
	} else {
		$res = $mysqli->query('INSERT INTO analyzer (name) VALUES ("' . $analyzer . '")');
		return $mysqli->insert_id;
	}
}

function getDiatonicRoot($tonic, $note) {
	$d = ['c' => 0, 'd' => 1, 'e' => 2, 'f' => 3, 'g' => 4, 'a' => 5, 'b' => 6];
	$r = $d[strtolower(substr($note, 0, 1))] - $d[strtolower(substr($tonic, 0, 1))];
	while ($r < 0) {
		$r += 7;
	}
	return $r + 1; // diatonic root is 1-based to correlate with roman numerals
}

function getChromaticRoot($tonic, $note) {
	$r = getPitchClassFromNoteName($note) - getPitchClassFromNoteName($tonic);
	while ($r < 0) {
		$r += 12;
	}
	return $r;
}
