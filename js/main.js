function doSearch() {
	var searchString = document.getElementById("searchfield").value;
	sendHTTPRequest(displayData,"ajax/ajaxsearch.php","s="+escape(encodeURI(searchString)))
}

function displayData(request) {
	if (request) {
		result = new dbResult(request.responseText);
		if (result.success()) {
			var tableBlock = document.getElementById('results').appendChild(createTag({name: 'table', id: 'resulttable'}));
			var rowBlock, headerBlock = tableBlock.appendChild(createTag({name: 'tr'}));
			result.columnNames().forEach(function(column) {
				headerBlock.appendChild(createTag({name: 'th', value: column}));
			});
			result.getRows().forEach(function(row) {
				rowBlock = tableBlock.appendChild(createTag({name: 'tr'}));
				row.forEach(function(cell) {
					rowBlock.appendChild(createTag({name: 'td', value: cell}));
				});
			});
		}
	}
}

class dbResult {
	constructor(xmlString) {
		var d = new DOMParser();
		this.xml = d.parseFromString(xmlString, 'text/xml');
		this.numRows = this.xml.getElementsByTagName('row').length;
	}
	success() {
		return this.xml.getElementsByTagName('result').length && this.xml.getElementsByTagName('result')[0].textContent == "0";
	}
	columnNames() {
		var a = [];
		if (this.numRows > 0) {
			for (let item of this.xml.getElementsByTagName('row')[0].children) {
				a.push(item.getAttribute('key'));
			}
		}
		return a;
	}
	getRow(row) {
		var a = [];
		if (row < this.numRows) {
			for (let item of this.xml.getElementsByTagName('row')[row].children) {
				a.push(item.textContent);
			}
		}
		return a;
	}
	getRows() {
		var b, a = [];
		for (let i=0;i<this.numRows;i++) {
			b = [];
			for (let item of this.xml.getElementsByTagName('row')[i].children) {
				b.push(item.textContent);
			}
			a.push(b);
		}
		return a;
	}
}

function downloadCSV(csv, filename) {
	var csvFile, downloadLink;

	csvFile = new Blob([csv], {type: "text/csv"});
	downloadLink = document.createElement("a");
	downloadLink.download = filename;
	downloadLink.href = window.URL.createObjectURL(csvFile);
	downloadLink.style.display = "none";
	document.body.appendChild(downloadLink);
	downloadLink.click();
}

function exportTableToCSV() {
	var t,csv = [];
	var rows = document.querySelectorAll("table tr");
	
	for (var i = 0; i < rows.length; i++) {
		var row = [], cols = rows[i].querySelectorAll("td, th");
		for (var j = 0; j < cols.length; j++) {
			t = cols[j].innerText.replace(/"/g, '""');
			if (t.includes(',')) {
				t = '"' + t + '"';
			}
			row.push(t);
		}
		csv.push(row.join(","));        
	}

	// Download CSV file
	downloadCSV(csv.join("\n"), "results.csv");
}