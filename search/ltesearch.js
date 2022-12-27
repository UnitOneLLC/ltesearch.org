// ltesearch.js

const READER_URL = "https://ltesearch.org/read";
const DRAFT_URL = "https://ltesearch.org/draft";

var USE_TEST_DATA = false;
var DO_FILTER = true;

var bFirstQuery = true;
var gDataTable = null;

var docTitle;

var DATA_TABLE_OPTIONS = {
    "paging": false,
    "scrollY": "75vh",
    "scrollCollapse": true,
    "order": [ [1, "desc"], [2, "asc"]],
    "columns": [
        {type: "html", width: "25px"},  /* 0 check box */
        {type: "date", width: "100px"}, /* 1 date */
        {type: "text", width: "150px"}, /* 2 newspaper */
        {type: "html"},                 /* 3 title/url */
        {type: "text"},                 /* 4 snippet */
        {type: "text", visible: false}  /* 5 zlink */
        ]
}
var ZLINK_COL_IDX = 5;

var gResultData = null;

$(document).ready(function() {
    docTitle = document.title;
    
    $("#digest").hide();
    $("#loading").hide();
    $("#fetch").click(getDigest);
    $("#copy-selected").click(copySelected);
    $("#sign-out").click(signOut);
    $("#clear-selection").click(onClearSelection);
    $("#incl-text-only").prop("checked", true);
    $("#incl-create-draft").prop("checked", true);
    $("#radio_all").prop("checked", true);
    $("input[name=disp_sel]").on("change", onDisplaySelectChange);
    $("#param-summary").text(getParamsSummary());
    $("#region").on("change", resetParamsSummary);
    $("#topic").on("change", resetParamsSummary);    
    $("#table-builder").click(onBuildTable);
    
    var sel = document.querySelector("select[name=paper-lookup]");
    sel.addEventListener("change", doFilterPaper)

    var urlVars = getUrlVars();

    var reg = urlVars["region"];
    if (reg) {
        $("#region").val(reg);
        $.cookie("region", reg);
    }
    else {
        reg = $.cookie("region");
        if (reg) {
            $("#region").val(reg); 
        }
        else {
            $("#region").val("Massachusetts");
        }
    }

    var topic = urlVars["topic"];
    if (topic) {
        $("#topic").val(topic);
        $.cookie("topic", topic);
    }
    else {
        topic = $.cookie("topic");
        if (topic) {
            $("#topic").val(topic); 
        }
        else {
            $("#topic").val("climate");
        }
    }
    
    $("#param-summary").text(getParamsSummary());

    prepareAuth();
});

function createLink(paperName, text, url, readerUrl, draftUrl) {
    var root = document.createElement("div");
    var anch = document.createElement("a");
    anch.setAttribute("noreferrer","");
    root.appendChild(document.createTextNode(paperName + ": "));
    root.appendChild(anch);
    anch.href = url;
    var textNode = document.createTextNode(text);
    anch.appendChild(textNode);
    root.setAttribute("style","font-size:14.5px");
    
    if (readerUrl) {
        var readerAnch = document.createElement("a");
        readerAnch.setAttribute("title", "view text");
        readerAnch.setAttribute("style", "background-color: #11a;color: #fff;font-family: sans-serif;font-variant: small-caps;padding: 0px 2px 0px 2px;cursor: pointer;text-decoration: none;font-size:0.9em;font-weight:800");;
        root.appendChild(document.createTextNode("  "));
        root.appendChild(readerAnch);
        readerAnch.href = readerUrl;
        readerAnch.innerHTML = " text";        
    }

    if (draftUrl) {
        var draftAnch = document.createElement("a");
        draftAnch.setAttribute("title", "create draft letter");
        draftAnch.setAttribute("style", "background-color: #11a;color: #fff;font-family: sans-serif;font-variant: small-caps;padding: 0px 2px 0px 2px;cursor: pointer;text-decoration: none;font-size:0.9em;font-weight:800");
        root.appendChild(document.createTextNode("  "));
        root.appendChild(draftAnch);
        draftAnch.href = draftUrl;
        draftAnch.innerHTML = " draft"; 
    }
    
    return root;
}


function theDataTable() {
    return $("#digest").DataTable();
}

function getZlink(domrow) {
    return theDataTable().row(domrow).data()[ZLINK_COL_IDX];
}

function getParamsSummary() {
    return $("#region").val() + "/" + $("#topic").val();
}

function resetParamsSummary() {
    $.cookie("region",$("#region").val());
    $.cookie("topic",$("#topic").val());    
    $("#param-summary").text(getParamsSummary());
}

function trimTitle(s) {
    if (s.indexOf("The Recorder") == 0) {
        return s.substring("The Recorder".length + 3);
    }
    var ix;
    ix = s.indexOf(" |");
    if (ix === -1)
        ix = s.indexOf(" — "); // mdash
    if (ix === -1) 
        ix = s.indexOf(" – "); // ndash
    if (ix === -1) 
        ix = s.indexOf(" - "); // dash		
    if (ix > 0) {
        return s.substring(0, ix);
    }
    return s;
}

function makeReaderUrl(z) {
    return READER_URL + "?z=" + z;
}

function makeDraftUrl(z) {
    return DRAFT_URL + "?z=" + z;
}

function onClearSelection() {
    $("#digest tr").removeClass("selected-row");
    $("#digest tr input[type=checkbox]").prop("checked", false);
    $("#radio_all").prop("checked", true);
    $("#sel-sum").text("Selection");

    clearSearch();
}

function clearSearch() {
    $.fn.dataTable.ext.search = [];        
    theDataTable().draw();
}

function onDisplaySelectChange() {
    var allSelected = theDataTable().rows(".selected-row");
    const isSelected = (index) => {
        for (var i=0; i < allSelected[0].length; ++i) {
            if (allSelected[0][i] == index)
                return true;
        }
        return false;
    }
    if($(this).val() == 'selected') {
        $.fn.dataTable.ext.search.push(
            function( settings, searchData, index, rowData, counter ) {
                if (isSelected(index)) {
                    return true;
                }
                else {
                    return false;
                }
            })
        
        theDataTable().draw();
        
    } else {
        clearSearch();
    }
}

function setClipboardMulti(items) {
    var outer = document.createElement("div");
    var last = document.createElement("div");
    var c = items.length;
    if (c == 0)
        return false;
    else {
        $(outer).css("background-color", "white");
        $(outer).css("color", "black");
        
        var bInclReader = $("#incl-text-only").is(":checked");
        var bIncDraft = $("#incl-create-draft").is(":checked");
        
        items.sort(function(a,b) {return a.title.localeCompare(b.title)});
        
        for (var i=0; i < items.length; ++i) {
            var t = items[i];
            var readerUrl = bInclReader && (t.zlink.indexOf(".pdf")==-1) ? makeReaderUrl(t.zlink) : null;
            var draftUrl = bIncDraft ? makeDraftUrl(t.zlink) : null;
            var link = createLink(t.paper, trimTitle(t.title), t.url, readerUrl, draftUrl);
            outer.appendChild(link);
        }
        document.body.appendChild(outer);
        document.body.appendChild(last);
        var range = document.createRange();
        
        range.setStart(outer, 0);
        range.setEnd(last, 0);
        
        var selObj = window.getSelection()
        selObj.removeAllRanges();
        selObj.addRange(range);

        var nItems = $(".selected-row").length;

        var ok;
        if (document.execCommand('copy')) {
            if (nItems === 1) {
                $("#copy-feedback").text("1 article copied");
            } else {
                $("#copy-feedback").text("" + nItems + " articles copied");
            }
            ok = true;
        } else {
            console.error('failed to get multi clipboard content');
            ok = false;
        }
        outer.remove();
        last.remove();
        return ok;
    }
    
}

/*** not used
function setClipboardMultiTable(items)
{
    var outer = document.createElement("div");
    var last = document.createElement("div");
    var table = document.createElement("table");
    var tbody = document.createElement("tbody");
    table.appendChild(tbody);
    outer.appendChild(table);

    var c = items.length;
    if (c == 0)
        return false;
    else {
        $(outer).css("background-color", "white");
        $(outer).css("color", "black");
        
        var bInclReader = $("#incl-text-only").is(":checked");
        var bIncDraft = $("#incl-create-draft").is(":checked");
        
        items.sort(function(a,b) {return a.title.localeCompare(b.title)});
        
        for (var i=0; i < items.length; ++i) {
            var t = items[i];
            var readerUrl = bInclReader && (t.zlink.indexOf(".pdf")==-1) ? makeReaderUrl(t.zlink) : null;
            var draftUrl = bIncDraft ? makeDraftUrl(t.zlink) : null;
            var row = createRow(t.paper, trimTitle(t.title), t.url, readerUrl, draftUrl);
            tbody.appendChild(row);
        }
        document.body.appendChild(outer);
        document.body.appendChild(last);
        var range = document.createRange();
        
        range.setStart(outer, 0);
        range.setEnd(last, 0);
        
        var selObj = window.getSelection()
        selObj.removeAllRanges();
        selObj.addRange(range);
        
        var nItems = $(".selected-row").length;
        
        var ok;
        if (document.execCommand('copy')) {
            if (nItems === 1) {
                $("#copy-feedback").text("1 article copied");
            } else {
                $("#copy-feedback").text("" + nItems + " articles copied");
            }
            ok = true;
        } else {
            console.error('failed to get multi clipboard content');
            ok = false;
        }
        outer.remove();
        last.remove();
        return ok;
    }
}
****/

function copySelected(obj, fnDomBuilder) {
    if (!fnDomBuilder) {
        fnDomBuilder = setClipboardMulti;
    }
    var items = [];
    var rowChecks = $("td>input[type=checkbox]");
    for (var i=0; i < rowChecks.length; ++i) {
        if ($(rowChecks[i]).is(":checked")) {
            var selected = {};
            var td = $(rowChecks[i]).parent().next("td");
            selected["date"] = td.text(); td = td.next("td");
            selected["paper"] = td.text(); td = td.next("td");
            selected["url"] = $(td.find("a")[0]).attr("href");
            selected["title"] = $(td.find("a")[0]).text();
            selected["zlink"] = getZlink(td.parent());
            items.push(selected);
        }
    }
    fnDomBuilder(items);
}

function onBuildTable() {
    copySelected(null, postSelectedToEditor);
}

function postSelectedToEditor(items) {
    $("#items_json").val(JSON.stringify(items));
    $("#edit-form").submit();
}

function isLoggedIn() {
    var tkn = localStorage.getItem("token");
    return (tkn && (tkn != "null"));
}

function prepareAuth() {
console.debug("enter prepareAuth");
    var controls = document.getElementById("controls");
    var authDiv = document.getElementById("auth");

    if (!isLoggedIn()) {
console.debug("NOT logged in");
        controls.style.display = "none";
        auth.style.display = "block";
    }
    else {
console.debug("Login OK already");
        controls.style.display = "block";
        auth.style.display = "none";
    }
}


function doFilterPaper() {
    var sel = document.querySelector("select[name=paper-lookup]");
    var paper = sel.value.split(",")[0];
    
    
    var search = document.querySelector("input[type=search]");
    search.value = paper;
    $(search).trigger("paste");
}


function handleGoogleAuth(credResponse) {
    try {
        console.log("auth: clientId=" + credResponse.clientId);
        console.log("auth: credential=" + credResponse.credential);
        
        var jwt = credResponse.credential.split(".");
        var decoded = atob(jwt[1]); // decode payload
        payload = JSON.parse(decoded);
        localStorage.setItem("token", btoa(payload.email));
        prepareAuth();
    } catch (e) {
        console.log("auth exception: " + e.message);
        $("body")[0].append(" [" + e.message + "]");
        window.alert("auth fail: " + e.message);
    }
}

// this is the old Google auth code —– not used now
function onSignIn(googleUser) {
    try {
        console.log("onSignIn: call getBasicProfile");
        var profile = googleUser.getBasicProfile();
        console.log('ID: ' + profile.getId()); // Do not send to your backend! Use an ID token instead.
        console.log('Name: ' + profile.getName());
        console.log('Image URL: ' + profile.getImageUrl());
        console.log('Email: ' + profile.getEmail()); // This is null if the 'email' scope is not present.
        
        localStorage.setItem("token", btoa(profile.getEmail()));
        prepareAuth();
        getDigest();
    } catch (e) {
        console.log("auth exception: " + e.message);
        $("body")[0].append(" [" + e.message + "]");
        window.alert("auth fail: " + e.message);
    }
}

function onAuthFail(e) {
    window.alert("auth fail: " + e.error);
    window.alert(e.error);
    $("body")[0].append(" [" + e.error + "]");
}

function gapi_init() {
    gapi.load('auth2', function() {
        /* Ready. Make a call to gapi.auth2.init or some other API */
    });
}

function signOut() {
    localStorage.setItem("token", null);
    var auth2 = gapi.auth2.getAuthInstance();
    if (auth2) {
        auth2.signOut().then(function () {
            console.log('User signed out.');
            
            prepareAuth();
        });
    }
    else prepareAuth();
}

function getUrlVars() {
    var vars = [], hash;
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    for(var i = 0; i < hashes.length; i++)
    {
        hash = hashes[i].split('=');
        vars.push(hash[0]);
        vars[hash[0]] = hash[1];
    }
    return vars;
}

function dedupItems(items) {
    items.sort(function(d1, d2) {
        if (d1.description < d2.description) return -1;
        if (d1.description > d2.description) return 1;
		if (d1.paper < d2.paper) return -1;
		if (d1.paper > d2.paper) return 1;
		return 0;        
    });
    return items.filter(function(value, index, self) {
        if (index == 0) return true;
        return value.description != self[index-1].description;
    });
}

function _preview(url) {
    window.open("https://ltesearch.org/read?z=" + url);
}

function previewButton(zlink) {
    
    var action = "javascript:_preview(\"" + zlink + "\")";
    
    return "<span title='view article text' onclick='" + action +  "' class='circle'>&#9417;</span>";
}

function _summarize(url) {
    window.open("https://ltesearch.org/summary?z=" + url);
}


function summarizeButton(zlink) {
    
    var action = "javascript:_summarize(\"" + zlink + "\")";
    
    return "<span title='view a summary of the article' onclick='" + action +  "' class='circle'>&#9416;</span>";
}

function checkClick(checkbox) {
    var row = $(checkbox).parent().parent();
    if ($(checkbox).is(":checked")) {
        row.addClass("selected-row");
    }
    else {
        row.removeClass("selected-row");
    }
    var nSelected = $(".selected-row").length;
    $("#sel-sum").text("Selection(" + nSelected + ")");
    $("#copy-feedback").text("");
}

function buildResultTable(jsonArr) {
	if (!Array.isArray(jsonArr)) {
		alert(jsonArr.error);
		return;
	}
	jsonArr = dedupItems(jsonArr);

    var table = $('#digest');
    
    if (!bFirstQuery) {
    	var parent = $("#table-parent");
    	$(parent.children()[0]).remove();
    	var tblMarkup = "<table id='digest' class='hover stripe'><thead><tr><th>Select</th><th>Date</th><th>Source</th><th>Item</th><th>Summary</th></tr></thead><tbody></tbody></table>";
    	parent.append(tblMarkup);
    	table = $('#digest');
    	if (table.DataTable())
    		table.DataTable().destroy();
    }
    else {
    	bFirstQuery = false;
    }
    
    var paperCounts = {};
    
    for (i in jsonArr) {
        d = jsonArr[i];

		date = (new Date(d.pubDate.substr(0,4),parseInt(d.pubDate.substr(5,2))-1,d.pubDate.substr(8,2)));
//        date = date.toLocaleDateString("en-US",{month: "short", day: "numeric"});
        
        date = Intl.DateTimeFormat('en-US').format(date);
        
        if (d.highlight == "true") {
            row = "<tr class=row-highlight>";
        }
        else {
            row = "<tr>";
        }
        row += "<td>" + "<input type='checkbox' onchange='checkClick(this)'/>" + "</td>";
        row += "<td>" + date + "</td>";
        row += "<td>" + d.paper + "</td>";
        row += "<td>" + "<a target='_blank' noreferrer href='" + d.url + "'>" + d.title + "</a>" + previewButton(d.zlink) +  
            summarizeButton(d.zlink) + "</td>";
        row += "<td>" + d.pubDate + " " + d.description + "</td>";
        row += "<td>" + d.zlink + "</td>";        
        table.append(row);
        
        if (paperCounts[d.paper]) {
            paperCounts[d.paper][1]++
        }
        else {
            paperCounts[d.paper] = [d.paper, 1];
        }
    }
    
    var sel = document.querySelector("select[name=paper-lookup]");
    for (var i=sel.children.length-1; i > 0; i--) {
        sel.children[i].remove();
    }
    
    var sortedPapers = [];
    for (p in paperCounts)
        sortedPapers[sortedPapers.length] = p;
    sortedPapers.sort();
    
    for (var i=0; i < sortedPapers.length; ++i) {
        var p = sortedPapers[i];
        var opt = document.createElement("option");
        opt.value = paperCounts[p];
        opt.text = p + " (" + paperCounts[p][1] + ")";
        sel.appendChild(opt);
    }
    
    document.title = docTitle + " (" + jsonArr.length + ")";

    table.DataTable(DATA_TABLE_OPTIONS);
}

function walkUpToRow(e) {
    var elem = e;
    while (elem && (elem.tagName != 'TR')) {
        elem = elem.parentElement;
    }
    return elem ? elem : e;
}

function getDigest() {
    document.title = docTitle;
//    onClearSelection();
    $("#digest tbody tr").remove();
    $("#loading").show();
    var url = 'ltesearch.php';
    var area = $("#region").val();
    url += "?region=" + area;
    url += "&action=search";
    url += "&topic=" + $("#topic").val();
    
    $("#sel-sum").text("Selection");

    var specificSearch = getQueryVariable("s");
    if (specificSearch) {
        url += "&s=" + encodeURIComponent(specificSearch);
    }
    var filterStrength = getQueryVariable("filter");
    if (filterStrength) {
    	url += "&filter=" + filterStrength;
    }

    $.ajax({
        url: url,
        type: 'POST',
        data: {"tkn":localStorage.getItem("token")},
        success: function(data) { gResultData = JSON.parse(data); $("#digest").show(); $("#loading").hide(); buildResultTable(gResultData) },
        cache: false,
        contentType: false,
        processData: true
    });
}

function getQueryVariable(variable) { // https://stackoverflow.com/questions/2090551/parse-query-string-in-javascript
    var query = window.location.search.substring(1);
    var vars = query.split('&');
    for (var i = 0; i < vars.length; i++) {
        var pair = vars[i].split('=');
        if (decodeURIComponent(pair[0]) == variable) {
            return decodeURIComponent(pair[1]);
        }
    }
    return "";
}

