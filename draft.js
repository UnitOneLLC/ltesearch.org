

$(document).ready(function() {
	setupClipboardResult();
	$("#copyAndGoBtn").click(copyHeaderToClipboard);
});


function setupClipboardResult() {
	// set the date field
	document.getElementById("date").textContent = formatDate(new Date());
	
	// set the author field
//	var authSpan = document.getElementById("author");
//	authSpan.textContent = args.author;
	
}

function updateAuthor(argAuth) {
	if (argAuth != "" && argAuth != "YOUR NAME") {
		chrome.storage.sync.set({"author": argAuth}, function() {});
		_author = argAuth;
	}
}

function formatDate(d) {
	var months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
	var midx = d.getMonth();
	var day = d.getDate();
	var year = d.getFullYear();
	
	return months[midx] + " " + day + ", " + year;
}

function copyHeaderToClipboard() {
	
//	updateAuthor(args.author);
	
	// do the copy to clipboard
	var result = false;
	var range = document.createRange();
	var start = document.getElementById("startCopy");
	var end = document.getElementById("endCopy");
	range.setStart(start, 0);
	range.setEnd(end, 0);
	
	var selObj = window.getSelection()
	selObj.removeAllRanges();
	selObj.addRange(range);
	
	if (document.execCommand('copy')) {
		result = document.getElementById("header-markup").innerHTML;
	} else {
		console.error('failed to get clipboard content');
		result = false;
	}
	
	if (result !== false) {
		var paperName = document.getElementById("newspaper").innerText;
		var title = document.getElementById("hyper").innerText;
		var url = "https://docs.google.com/document/create?title=LTE ";
		title = encodeURIComponent(paperName + "- " + title.substring(0,31));
		url += title;

		location.replace(url);
	}
}
