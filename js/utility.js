function sendHTTPRequest(callback,filename,data) {
	var httpRequest;
	var phpRootAddress = window.location.href.substr(0, window.location.href.lastIndexOf('/'))+'/php/';
	if (window.XMLHttpRequest) {
		httpRequest = new XMLHttpRequest();
	} else if (window.ActiveXObject) {
		httpRequest = new ActiveXObject("Microsoft.XMLHTTP");
	}
	httpRequest.onreadystatechange = function() {
		if (httpRequest.readyState === XMLHttpRequest.DONE) {
			if (httpRequest.status === 200) {
				callback(httpRequest);
			} else {
				callback(null);
			}
		}
	};
	httpRequest.open('POST',phpRootAddress+filename, true);
	httpRequest.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	httpRequest.send(data);
}

function createTag(params) {
	var d = document.createElement(params.name);
	if (params.id) {
		d.id = params.id;
	}
	if (params.className) {
		d.className = params.className;
	}
	if (params.value) {
		d.innerHTML = params.value;
	}
	return d;
}

function createDiv(params) {
	params.name = 'div';
	return createTag(params);
}

