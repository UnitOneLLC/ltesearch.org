

$(document).ready(function() {
	showSpin(false);
	$("#date").text(formatDate(new Date()));
	$("#copyAndGoBtn").click(createDraftAndGo);
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

function formatDate(d) {
	var months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
	var midx = d.getMonth();
	var day = d.getDate();
	var year = d.getFullYear();
	
	return months[midx] + " " + day + ", " + year;
}

function showSpin(on) {
	if (on) {
		$("#spinner").show();
		$("#spinner-prompt").show();		
	}
	else {
		$("#spinner").hide();		
		$("#spinner-prompt").hide();		
	}
}

function createDraftAndGo() {
	
	var author =  $("#author").val();
	document.cookie = "author=" + author;
	$("#auth_text").text(author);
	$("#author").remove();
	
	var paperName = document.getElementById("newspaper").innerText;
	var title = document.getElementById("hyper").innerText;
	title = "LTE " + encodeURIComponent(paperName + "- " + title.substring(0,31));

	var sanitized_link = $("#hyper").attr("href").replace("https", "PROTOCOL1").replace("http", "PROTOCOL2");
	var sanitized_lteaddr = $("#submit_addr").text().replace("https", "PROTOCOL1").replace("http", "PROTOCOL2");
	
	var params = {
		author: $("#auth_text").text().trim(),
		paper: $("#newspaper").text().trim(),
		responding_title: $("#hyper").text().trim(),
		responding_url: sanitized_link,
		lteaddr: sanitized_lteaddr,
		title: title.trim()
	}
	
	showSpin(true);
	$.ajax("./createdraft.php", {data: params})
		.done((resultString)=>{
			showSpin(false);
			try {
				var result = JSON.parse(resultString);
				var url = "https://docs.google.com/document/d/" + result.id + "/copy?title=" + title;
				location.replace(url);
			}
			catch (e) {
				alert("Unable to create the draft doc: " + (e.message ? e.message : e));
				showSpin(false);
			}
		})
		.fail((e)=>{
			alert("Unable to create the draft doc: " + e);
			showSpin(false);
		});
}
