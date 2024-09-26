

$(document).ready(function() {
	showSpin(false);
	showSpin(false, "tp");	
	$("#copyAndGoBtn").click(createDraftAndGo);
	$("#author").on("keyup", onAuthChange);
	$("#author").on("blur", onAuthBlur);	
	$("#btn-chg-auth").click(onClickChangeAuthor);
	$("#btn-get-tp").click(onGetTalkingPoints);
	$("#btn-get-angles").click(onSuggestAngles);
	$("#btn-copy-ai").click(onCopyAi);
	$("#btn-copy-ai").attr("disabled", true);
	$("#post-ai-notice").hide();
	setupAuthor();
});

function onGetTalkingPoints() {
	var pro = true;
	$("#tp-cell").empty();
	showSpin(true, "tp");
	var qStringParams = new URLSearchParams(window.location.search);
	var url = "./aitp.php?z=" + qStringParams.get('z') + "&pro=" + pro + "&req=tp" + "&topic=" + theTopic;
	$.ajax(url)
	.done((resultString)=>{
		showSpin(false, "tp");
		var cellDiv = $("#tp-cell");
		try {
			var results = JSON.parse(resultString);
			cellDiv.empty();
			cellDiv.append($("<ul id='tp-list'></ul>"));
			var i, list=$("#tp-list"), keys = Object.keys(results);
			for (i=0; i < keys.length; ++i) {
				list.append($("<li>" + results[keys[i]].trim() + "</li>"));
			}
			$("#btn-copy-ai").attr("disabled", false);
		}
		catch (e) {
			cellDiv.empty();
			alert("Unable to get talking points. Try again in a few moments. " + " (System response was: " + resultString + ")");
			showSpin(false, "tp");
		}
	})
	.fail((e)=>{
		alert("Unable to get talking points: " + e);
		showSpin(false, "tp");
	});
}


function onSuggestAngles() {
	var pro = true;
	$("#tp-cell").empty();
	showSpin(true, "tp");
	var qStringParams = new URLSearchParams(window.location.search);
	var url = "./aitp.php?z=" + qStringParams.get('z') + "&pro=" + pro + "&req=angles" + "&topic=" + theTopic;
	var extra = $('#extra-prompt').val();
	if (extra && extra.length > 0) {
		url += "&extra=" + encodeURIComponent(extra);
	}
	
	$.ajax(url)
	.done((resultString)=>{
		showSpin(false, "tp");
		var cellDiv = $("#tp-cell");
		try {
			var results = JSON.parse(resultString.trim());
			cellDiv.empty();
			cellDiv.append($("<div>" + results["text"] + "</div>"));
			$("#btn-copy-ai").attr("disabled", false);
			$("#post-ai-notice").show();
		}
		catch (e) {
			cellDiv.empty();
			alert("Unable to get angle suggestions. Try again in a few moments." + " (System response: " + resultString + ")");
			showSpin(false, "tp");
		}
	})
	.fail((e)=>{
		alert("Unable to get angle suggestions: " + e);
		showSpin(false, "tp");
	});
}

function onCopyAi() {
	var range = document.createRange();
	range.selectNodeContents($("#tp-cell")[0]);	
	
	var selObj = window.getSelection()
	selObj.removeAllRanges();
	selObj.addRange(range);
	
	if (!document.execCommand('copy')) {
		console.error('failed to copy to clipboard');
		alert("oops . . . failed to copy!");
	}
	window.getSelection().removeAllRanges();	
}

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
		$("#lte-no-cookie").hide();
		$("#lte-have-cookie").show();
		$("#btn-chg-auth").show();
		$("#author").val(authorText);
		$("#auth-text").text(authorText);
		$("#author").hide();
		enableButton(true);		
	}
	else {
		$("#lte-no-cookie").show();
		$("#lte-have-cookie").hide();
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

function showSpin(on, prefix) {
	if (prefix == null)
		prefix = "";
	else
		prefix = prefix + "-";
	
	if (on) {
		$("#" + prefix + "spinner").show();
		$("#" + prefix + "spinner-prompt").show();		
	}
	else {
		$("#" + prefix + "spinner").hide();		
		$("#" + prefix + "spinner-prompt").hide();		
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
		title: title.trim(),
		maxWords: g_max_words
	}
	
	showSpin(true);
	$.ajax("./createdraft.php", {data: params})
		.done((resultString)=>{
			showSpin(false);
			try {
				var result = JSON.parse(resultString);
				var url = "https://docs.google.com/document/d/" + result.id + "/copy?title=" + title;
				
				var cbox = document.getElementById("cbox-new-window");
				if (cbox.checked) {
					window.open(url, "_blank");
				}
				else {
					location.assign(url);
				}
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
