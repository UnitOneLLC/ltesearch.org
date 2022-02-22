

$(document).ready(function() {
	showSpin(false);
	$("#copyAndGoBtn").click(createDraftAndGo);
	$("#author").on("keyup", onAuthChange);
	$("#author").on("blur", onAuthBlur);	
	$("#btn-chg-auth").click(onClickChangeAuthor);
	setupAuthor();
});

function enableButton(enabled) {
	if (enabled) {
		$("#copyAndGoBtn").prop("disabled", false);		
		$("#copyAndGoBtn").css("background-color", "rgba(30,30,120,1)");
	}
	else {
		$("#copyAndGoBtn").prop("disabled", true);		
		$("#copyAndGoBtn").css("background-color", "#999");		
	}
}


function onAuthChange() {
	if ($("#author").val())
		enableButton(true);
	else
		enableButton(false);
}

function onAuthBlur() {
	$.cookie("author", $("#author").val());
}

function onClickChangeAuthor() {
	$.removeCookie("author");
	setupAuthor();
}


function setupAuthor() {
	var authorText = $.cookie("author");
	if (authorText === "undefined" || authorText === null)
		authorText = "";
	if (authorText) {
		$("#no-cookie").hide();
		$("#have-cookie").show();
		$("#btn-chg-auth").show();
		$("#author").val(authorText);
		$("#auth-text").text(authorText);
		$("#author").hide();
		enableButton(true);		
	}
	else {
		$("#no-cookie").show();
		$("#have-cookie").hide();
		$("#btn-chg-auth").hide();
		$("#author").show();
		$("#author").val("");
		$("#author").focus();
		enableButton(false);		
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
	$("#auth-text").text(author);
	$("#author").remove();
	
	var paperName = g_newspaper;
	var title = g_title;
	title = "LTE " + encodeURIComponent(paperName + "- " + title.substring(0,31));

	var sanitized_link = g_url.replace("https", "PROTOCOL1").replace("http", "PROTOCOL2");
	var sanitized_lteaddr = g_lteaddr.replace("https", "PROTOCOL1").replace("http", "PROTOCOL2");
	
	var params = {
		author: $("#auth-text").text().trim(),
		paper: g_newspaper.trim(),
		responding_title: g_title.trim(),
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
				location.assign(url);
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
