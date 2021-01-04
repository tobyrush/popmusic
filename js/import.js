function importRS500() {
	Array.from(document.querySelector('#rs500').querySelector('.importFileSelector').files).forEach( currentFile => {
		if (currentFile.name == "rs500.txt") {
			document.querySelector('#rs500').querySelector('.importStatus').innerHTML = "Parsing rs500.txt...";
			uploadFile(document.querySelector('#rs500'),'php/ajax/ajaximportrs500.php',currentFile);
		}
	});
}

function importRS5x20() {
	Array.from(document.querySelector('#rs5x20').querySelector('.importFileSelector').files).forEach( currentFile => {
		if (currentFile.name == "rs5x20.txt") {
			document.querySelector('#rs5x20').querySelector('.importStatus').innerHTML = "Parsing rs5x20.txt...";
			uploadFile(document.querySelector('#rs5x20'),'php/ajax/ajaximportrs5x20.php',currentFile);
		}
	});
}

function importRS200() {
	var i = new importer(document.querySelector('#rs200'));
	i.addRule(/rs200\.txt/i, 'php/ajax/ajaximportrs200.php');
	i.addRule(/\.clt/i, 'php/ajax/ajaximportrs200clt.php');
	i.addRule(/\.nlt/i, 'php/ajax/ajaximportrs200nlt.php');
	i.addRule(/\.str/i, 'php/ajax/ajaximportrs200str.php');
	i.uploadFiles();
}

function importMcGill() {
	var i = new importer(document.querySelector('#mcgill'));
	i.addRule(/billboard-2\.0-index\.csv/i, 'php/ajax/ajaximportmcgill.php');
	i.addRule(/.txt/i, 'php/ajax/ajaximportmcgilltxt.php');
	i.uploadFiles();
}

function importCASD() {
	var i = new importer(document.querySelector('#casd'));
	i.addRule(/.jams/i, 'php/ajax/ajaximportcasd.php');
	i.uploadFiles();
}

function importShea() {
	Array.from(document.querySelector('#shea').querySelector('.importFileSelector').files).forEach( currentFile => {
		if (currentFile.name == "Function Corpus Encoding - Final_Cleaned.tsv") {
			document.querySelector('#shea').querySelector('.importStatus').innerHTML = "Parsing Function Corpus Encoding - Final_Cleaned.tsv...";
			uploadFile(document.querySelector('#shea'),'php/ajax/ajaximportshea.php',currentFile);
		}
	});
}

class importer {
	constructor(importBlock) {
		this.files = [];
		Array.from(importBlock.querySelector('.importFileSelector').files).forEach( currentFile => {
			this.files.push(currentFile);
		});
		this.progressBar = importBlock.querySelector('progress');
		this.statusText = importBlock.querySelector('.importStatus');
		this.progressBar.max = this.files.length;
		this.progressBar.value = 0;
		this.matches = [];
	}
	addRule(matchExpr,ajaxFile) {
		this.matches.push([matchExpr,ajaxFile]);
	}
	uploadFiles() {
		this.progressBar.style.display = "inline";
		if (this.files.length) {
			var currentFile = this.files.pop();
			this.progressBar.value = this.progressBar.max - this.files.length;
			var ajaxFile,statusMessage;
			this.matches.some( match => {
				statusMessage = "Parsing " + currentFile.name + "...";
				ajaxFile = match[1]
				return currentFile.name.match(match[0]);
			});
			this.statusText.innerHTML = statusMessage;
			var xhr = new XMLHttpRequest();
			var whenDone = function() {
				if (xhr.readyState === XMLHttpRequest.DONE) {
					this.uploadFiles();
				}
			};
			xhr.onreadystatechange = whenDone.bind(this);
			xhr.open('POST', ajaxFile, true);
			var formData = new FormData();
			formData.append("file", currentFile);
			xhr.send(formData);
		} else {
			this.progressBar.style.display = "none";
			this.statusText.innerHTML = "";
		}
	}
}

function uploadFile(importBlock,ajaxFile,whichFile) {
	importBlock.querySelector('progress').style.display = "inline";
	var xhr = new XMLHttpRequest();
	xhr.onreadystatechange = function() {
		if (xhr.readyState === XMLHttpRequest.DONE) {
			//if (xhr.status === 200) {
				//console.log('Done with status ' + xhr.status);
				importBlock.querySelector('progress').style.display = "none";
				importBlock.querySelector('.importStatus').innerHTML = "";
			//}
		}
	};
	xhr.open('POST', ajaxFile, true);
	var formData = new FormData();
	formData.append("file", whichFile);
	//formData.append("file", importBlock.querySelector('.importFileSelector').files[0]);
	xhr.send(formData);
	//console.log("Uploading to server...");
}