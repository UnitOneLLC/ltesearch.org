

$(document).ready(function() {
	setupClipboardResult();
	$("#copyAndGoBtn").click(copyHeaderToClipboard);
	setupAuthor();
});


function setupAuthor() {
	var cookieLookup = parseCookie(document.cookie);
	if (cookieLookup["author"]) {
		$("#author").val(cookieLookup["author"]);
	}
	else {
		$("#author").val("Enter your name");
	}
}

function parseCookie(str) {
	var retVal;
	str
	.split(';')
	.map(v => {return v.split('=')})
	.reduce((acc, v) => {
		acc[decodeURIComponent(v[0].trim())] = decodeURIComponent(v[1].trim());
		retVal = acc;
		return acc;
	}, {});
	
	return retVal;
}


function setupClipboardResult() {
	// set the date field
	document.getElementById("date").textContent = formatDate(new Date());	
}

function formatDate(d) {
	var months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
	var midx = d.getMonth();
	var day = d.getDate();
	var year = d.getFullYear();
	
	return months[midx] + " " + day + ", " + year;
}

function copyHeaderToClipboard() {
	
	var author =  $("#author").val();
	document.cookie = "author=" + author;
	$("#auth_text").text(author);
	$("#author").remove();
	
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
