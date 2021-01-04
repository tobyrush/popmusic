<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<title>Popular Music Metacorpus</title>
		<link href='css/main.css' rel='stylesheet' type='text/css' />
		<script type="text/javascript" src="js/import.js"></script>
		<script type="text/javascript" src="js/utility.js"></script>
	</head>

	<body>

		<div id="header">
			<div id="title">Popular Music Metacorpus Administration</div>
		</div>
		<div id="import">
			<div class="importblock" id="rs500">
				<div class="blocktitle">Rolling Stone 500</div>
				<div class="blockdescription">Rolling Stone magazine's list of the &quot;500 Greatest Songs of All Time&quot; as listed on December 9, 2004, compiled into a tab-delimited list by Trevor de Clercq and David Temperley</div>
				<div class="importFileSelectorLabel">Import 'rs500.txt':</div><input type="file" id="fileInput" class="importFileSelector">
				<button class="importButton" onclick="importRS500();">Import</button>
				<progress class="importProgress" max="100"></progress>
				<span class="importStatus"></span>
			</div>
			<div class="importblock" id="rs5x20">
				<div class="blocktitle">Rolling Stone 5x20</div>
				<div class="blockdescription">Subset of Trevor de Clercq and David Temperley's Rolling Stone 500 above which is evenly spread across decades</div>
				<div class="importFileSelectorLabel">Import 'rs5x20.txt':</div><input type="file" id="fileInput" class="importFileSelector">
				<button class="importButton" onclick="importRS5x20();">Import</button>
				<progress class="importProgress" max="100"></progress>
				<span class="importStatus"></span>
			</div>
			<div class="importblock" id="rs200">
				<div class="blocktitle">Rolling Stone 200</div>
				<div class="blockdescription">Subset of Trevor de Clercq and David Temperley's Rolling Stone 500 that builds upon the Rolling Stone 5x20</div>
				<div class="importFileSelectorLabel">Import 'rs200.txt', *.clt, *.nlt or *.str files:</div><input type="file" id="fileInput" class="importFileSelector" multiple="">
				<button class="importButton" onclick="importRS200();">Import</button>
				<progress class="importProgress" max="100"></progress>
				<span class="importStatus"></span>
			</div>
			<div class="importblock" id="mcgill">
				<div class="blocktitle">McGill Billboard Project</div>
				<div class="blockdescription">A random sample of songs from Billboard's Hot 100 singles chart from 1958 to 1991.</div>
				<div class="importFileSelectorLabel">Import 'billboard-2.0-index.csv':</div><input type="file" id="fileInput" class="importFileSelector" multiple="">
				<button class="importButton" onclick="importMcGill();">Import</button>
				<progress class="importProgress" max="100"></progress>
				<span class="importStatus"></span>
			</div>
			<div class="importblock" id="casd">
				<div class="blocktitle">Chordify Annotator Subjectivity Dataset</div>
				<div class="blockdescription">Fifty songs from the McGill Billboard Project, each with chord analyses from four different analyzers</div>
				<div class="importFileSelectorLabel">Import *.jams files:</div><input type="file" id="fileInput" class="importFileSelector" multiple="">
				<button class="importButton" onclick="importCASD();">Import</button>
				<progress class="importProgress" max="100"></progress>
				<span class="importStatus"></span>
			</div>
			<div class="importblock" id="shea">
				<div class="blocktitle">Pop-Rock Function Corpus</div>
				<div class="blockdescription">A collection of songs from 1959-2019, annotated by Nicholas Shea, et al for form only</div>
				<div class="importFileSelectorLabel">Import 'Function Corpus Encoding - Final_Cleaned.tsv':</div><input type="file" id="fileInput" class="importFileSelector">
				<button class="importButton" onclick="importShea();">Import</button>
				<progress class="importProgress" max="100"></progress>
				<span class="importStatus"></span>
			</div>
		</div>
	</body>

</html>