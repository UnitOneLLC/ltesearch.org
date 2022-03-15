// ltesearch.js

var USE_TEST_DATA = false;

const READER_URL = "https://ltesearch.org/read";
const DRAFT_URL = "https://ltesearch.org/draft";

var gDataTable = null;

var docTitle = window.docTitle;

var DATA_TABLE_OPTIONS = {  
    "pageLength": 500,
    "rowReorder": true,
    "columns": [
        {type: "num" },
        {type: "text"},
        {type: "html"},
        {type: "html"},
        {type: "html"}
        ]
}

$(document).ready(function() {
    $("#copy-btn").click(doCopy);
    buildResultTable(items);
});

function createRow(seq, paperName, text, url, readerUrl, draftUrl) {
    var row = document.createElement("tr");
    var handle = document.createElement("td");
    var paperCell = document.createElement("td");
    var linkCell = document.createElement("td");
    var textLinkCell = document.createElement("td");
    var draftLinkCell = document.createElement("td");
    row.appendChild(handle);
    row.appendChild(paperCell);
    row.appendChild(linkCell);
    row.appendChild(textLinkCell);
    row.appendChild(draftLinkCell);
    
    handle.appendChild(document.createTextNode(seq.toString()));
    paperCell.appendChild(document.createTextNode(paperName));
    paperCell.setAttribute("style", "padding-left:30px");
    var anch = document.createElement("a");
    anch.setAttribute("noreferrer","");
    linkCell.appendChild(anch);
    anch.href = url;
    var textNode = document.createTextNode(text);
    anch.appendChild(textNode);
    linkCell.setAttribute("style","font-size:14.5px");
    
    if (readerUrl) {
        var readerAnch = document.createElement("a");
        readerAnch.setAttribute("title", "view text");
        readerAnch.setAttribute("style", "background-color: #11a;color: #fff;font-family: sans-serif;font-variant: small-caps;padding: 0px 2px 0px 2px;cursor: pointer;text-decoration: none;font-size:0.9em;font-weight:800");;
        textLinkCell.appendChild(document.createTextNode("  "));
        textLinkCell.appendChild(readerAnch);
        readerAnch.href = readerUrl;
        readerAnch.innerHTML = " text";        
    }
    
    if (draftUrl) {
        var draftAnch = document.createElement("a");
        draftAnch.setAttribute("title", "create draft letter");
        draftAnch.setAttribute("style", "background-color: #11a;color: #fff;font-family: sans-serif;font-variant: small-caps;padding: 0px 2px 0px 2px;cursor: pointer;text-decoration: none;font-size:0.9em;font-weight:800");
        draftLinkCell.appendChild(draftAnch);
        draftAnch.href = draftUrl;
        draftAnch.innerHTML = " draft"; 
    }
    
    return row;
}


function makeReaderUrl(z) {
    return READER_URL + "?z=" + z;
}

function makeDraftUrl(z) {
    return DRAFT_URL + "?z=" + z;
}

function buildResultTable(jsonArr) {
	if (!Array.isArray(jsonArr)) {
		alert(jsonArr.error);
		return;
	}

    var table = $('#result_table');
    var tbody = $("#result_table tbody");
        
    for (i in jsonArr) {
        d = jsonArr[i];
        var readerUrl = d.zlink ? makeReaderUrl(d.zlink) : "#";
        var draftUrl = d.zlink ? makeDraftUrl(d.zlink) : "#";
        var row = createRow(i, d.paper, d.title, d.url, readerUrl, draftUrl);
        tbody[0].appendChild(row);
    }
    
    var nRows = $('#result_table tr').length;
    for (var k=0;  k < nRows; ++k) {
        var commentRow = createCommentRow(k + nRows);
        tbody[0].appendChild(commentRow);
    }
    
    gDataTable = table.DataTable(DATA_TABLE_OPTIONS);
}

function createCommentRow(seq) {
    var xrow = document.createElement("tr");
    var seqCell = document.createElement("td");
    seqCell.appendChild(document.createTextNode(seq.toString()))
    xrow.appendChild(seqCell);
    var bigcol = document.createElement("td");
    bigcol.setAttribute("contenteditable", "true");
    bigcol.setAttribute("colspan", 4);
    xrow.appendChild(bigcol);
    
    for (var i=0; i < 3; ++i) {
        var cell = document.createElement("td");
        cell.style.display = "none";
        xrow.appendChild(cell);
    }
    
    $(xrow).css("font-weight", "bold");
    return xrow;
}

function doCopy() {
    var rows = $("#result_table tbody tr");
    for (var i=0; i < rows.length; ++i) {
        var firstCell = rows[i].firstChild;
        $(firstCell).text(" ");
    }

    for (i = rows.length-1; i >= rows.length/2; --i) {
        firstCell = rows[i].firstChild.nextSibling;
        if ($(firstCell).text() == "") {
            rows[i].remove();
        }
    }

    var tab = $("#head")[0];
    var end = $("#_end_")[0];        

    var range = document.createRange();
    
    range.setStart(tab, 0);
    range.setEnd(end, 0);
    
    var selObj = window.getSelection()
    selObj.removeAllRanges();
    selObj.addRange(range);

    var ok;
    var nItems = items.length;
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

    for (i=0; i < rows.length; ++i) {
        var firstCell = rows[i].firstChild;
        $(firstCell).text(i.toString());
    }
    if (window.getSelection().empty)
        window.getSelection().empty();
}