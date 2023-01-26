// editor.js

var USE_TEST_DATA = false;

const READER_URL = "https://ltesearch.org/read";
const DRAFT_URL = "https://ltesearch.org/draft";
const TWITTER_URL = "https://twitter.com/intent/tweet";
const FACEBOOK_URL = "https://www.facebook.com/dialog/share?app_id=80401312489";

var gDataTable = null;

var docTitle = window.docTitle;

var DATA_TABLE_OPTIONS = {  
    "pageLength": 500,
    "rowReorder": true,
    "columns": [
        {type: "num" },
        {type: "html"},
        {type: "text"},
        {type: "html"},
        {type: "html"},
        {type: "html"},
        {type: "html"}
        ]
}

var helpString = "You can reorder the rows of the table by dragging lines up and down. Click and hold on " + 
    "the sequence number in the leftmost column to drag. You can write comments in the blank " +
    "areas and drag them to the appropriate position in the table.";

$(document).ready(function() {
    $("#copy-btn").click(doCopy);
    $("#add-url-btn").click(doAddUrl);
    $("#toggle-help").click(doToggleHelp);
    $("#help-pane").hide();
    $("#help-pane").text(helpString);
    gDataTable = buildResultTable(items);
});

function createRow(seq, paperName, text, url, readerUrl, draftUrl, twitterUrl, fbUrl) {
    var row = document.createElement("tr");
    var handle = document.createElement("td");
    var delBtnCell = document.createElement("td");
    var paperCell = document.createElement("td");
    var linkCell = document.createElement("td");
    var textLinkCell = document.createElement("td");
    var draftLinkCell = document.createElement("td");
    var socialCell = document.createElement("td");
    row.appendChild(handle);
    row.appendChild(delBtnCell);
    row.appendChild(paperCell);
    row.appendChild(linkCell);
    row.appendChild(textLinkCell);
    row.appendChild(draftLinkCell);
    row.appendChild(socialCell);
    
    handle.appendChild(document.createTextNode(seq.toString()));
    delBtnCell.innerHTML = "<span onclick='deleteRow(this)' class=del-btn contenteditable=false>x</span>";
    $(delBtnCell).addClass("del-btn");
    paperCell.appendChild(document.createTextNode(paperName));
    paperCell.setAttribute("style", "padding-left:30px");
    if (bEnableSocial) {
        socialCell.setAttribute("style", "width:40px");
    }
    else {
        socialCell.setAttribute("style", "width:1px");
    }
        
    var anch = document.createElement("a");
    anch.setAttribute("noreferrer","");
    linkCell.appendChild(anch);
    anch.href = url;
    var textNode = document.createTextNode(text);
    anch.appendChild(textNode);
//    linkCell.setAttribute("style","font-size:14.5px");
    
    if (readerUrl) {
        var readerAnch = document.createElement("a");
        readerAnch.setAttribute("title", "view text");
        readerAnch.setAttribute("style", "background-color: #11a;color: #fff;font-family: sans-serif;font-variant: small-caps;padding: 0px 2px 0px 2px;cursor: pointer;text-decoration: none;font-size:0.85em;font-weight:600");;
        textLinkCell.appendChild(document.createTextNode("  "));
        textLinkCell.setAttribute("style", "width:40px");        
        textLinkCell.appendChild(readerAnch);
        readerAnch.href = readerUrl;
        readerAnch.innerHTML = "&nbsp;text&nbsp;";        
    }
    
    if (draftUrl) {
        var draftAnch = document.createElement("a");
        draftAnch.setAttribute("title", "create draft letter");
        draftAnch.setAttribute("style", "background-color: #11a;color: #fff;font-family: sans-serif;font-variant: small-caps;padding: 0px 2px 0px 2px;cursor: pointer;text-decoration: none;font-size:0.85em;font-weight:600");
        draftLinkCell.setAttribute("style", "width:50px");
        draftLinkCell.appendChild(draftAnch);
        draftAnch.href = draftUrl;
        draftAnch.innerHTML = "&nbsp;draft&nbsp;"; 
    }

    if (bEnableSocial) {
        if (twitterUrl) {
            var twitterAnch = document.createElement("a");
            twitterAnch.setAttribute("title", "tweet this article");
            twitterAnch.setAttribute("style", "width:16px");
            socialCell.appendChild(document.createTextNode("  "));
            socialCell.appendChild(twitterAnch);
            twitterAnch.href = twitterUrl;
            twitterAnch.innerHTML = '<img style="height:16px; vertical-align: middle" src="https://www.gstatic.com/alerts/images/tw-24.png">';
        }
        
        if (fbUrl) {
            socialCell.appendChild(document.createTextNode("  "));
            var fbAnch = document.createElement("a");
            fbAnch.setAttribute("title", "share on Facebook");
            fbAnch.setAttribute("style", "width:16px");
            socialCell.appendChild(document.createTextNode("  "));
            socialCell.appendChild(fbAnch);
            fbAnch.href = fbUrl;
            fbAnch.innerHTML = '<img style="height:16px; vertical-align: middle" src="https://www.gstatic.com/alerts/images/fb-24.png">';
        }
    }
    
    return row;
}


function makeReaderUrl(z) {
    return READER_URL + "?z=" + z;
}

function makeDraftUrl(z) {
    return DRAFT_URL + "?z=" + z;
}

function makeTwitterUrl(u, title) {
    return TWITTER_URL + "?url=" + encodeURIComponent(u) + "&text=" + encodeURIComponent(title);
}

function makeFacebookUrl(u, title) {
    return FACEBOOK_URL + "&href=" + encodeURIComponent(u);
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
        var twitterUrl = d.url ? makeTwitterUrl(d.url, d.title) : "#";
        var facebookUrl = d.url ? makeFacebookUrl(d.url, d.title) : "#";
        var row = createRow(i, d.paper, d.title, d.url, readerUrl, draftUrl, twitterUrl, facebookUrl);
        tbody[0].appendChild(row);
    }
    
    var nRows = $('#result_table tr').length;
    for (var k=0;  k < nRows; ++k) {
        var commentRow = createCommentRow(k + nRows);
        tbody[0].appendChild(commentRow);
    }
    
    return table.DataTable(DATA_TABLE_OPTIONS);
}


function getArticleCount() {
    return $("#result_table tr").length - $(".comment-row").length;
}

function createCommentRow(seq) {
    var xrow = document.createElement("tr");
    xrow.classList.add("comment-row");
    var seqCell = document.createElement("td");
    seqCell.appendChild(document.createTextNode(seq.toString()))
    xrow.appendChild(seqCell);
    var delCell = document.createElement("td");
    $(delCell).addClass("del-btn");
    $(delCell).css("background-color", "#d0d0d0");
    var bigcol = document.createElement("td");
    bigcol.setAttribute("contenteditable", "true");
    bigcol.setAttribute("colspan", 5);
    xrow.appendChild(delCell);
    xrow.appendChild(bigcol);
    
    for (var i=0; i < 4; ++i) {
        var cell = document.createElement("td");
        cell.style.display = "none";
        xrow.appendChild(cell);
    }
    
    $(xrow).css("font-weight", "bold");
    return xrow;
}

function deleteRow(cell) {
    gDataTable
    .row( $(cell).parents('tr') )
    .remove()
    .draw();    
}

function doCopy() {
    $("#result_table").css("width", "640px").css("margin", "0 10px");
    
    var btns = $(".del-btn");
    
    btns.each(
        b=>{
            if ($(btns[b]).text() == "x") {
                $(btns[b]).text("");
                $(btns[b]).css("user-select", "auto");
                $(btns[b]).css("background-color", "transparent");
            }
        }
    )
    var rows = $("#result_table tbody tr");
    for (var i=0; i < rows.length; ++i) {
        var firstCell = rows[i].firstChild;
        $(firstCell).text(" ");
    }

    for (i = rows.length-1; i >= 0; --i) {
        $(rows[i].childNodes[0]).css("width", "1px");
        $(rows[i].childNodes[0]).css("padding", "0 0 0 0"); 
        $(rows[i].childNodes[1]).css("width", "1px");
        $(rows[i].childNodes[1]).css("padding", "0 0 0 0");        
        
        if ($(rows[i]).hasClass("comment-row")) {
            if (rows[i].textContent.trim() == "") {
                rows[i].remove();
            }
            else {
                $(rows[i].childNodes[1]).css("background-color", "transparent");
            }
        }
    }
    
    $("#result_table thead tr")[0].remove();

    var tab = $("#head")[0];
    var end = $("#_end_")[0];        

    var range = document.createRange();
    
    range.setStart(tab, 0);
    range.setEnd(end, 0);
    
    var selObj = window.getSelection()
    selObj.removeAllRanges();
    selObj.addRange(range);


    var nItems = getArticleCount();
    if (document.execCommand('copy')) {
        if (nItems === 1) {
            $("#copy-feedback").text("1 article copied");
        } else {
            $("#copy-feedback").text("" + nItems + " articles copied");
        }
    } else {
        console.error('failed to get clipboard content');
    }

    for (i=0; i < rows.length; ++i) {
        var firstCell = rows[i].firstChild;
        $(firstCell).text(i.toString());
    }
    if (window.getSelection().empty)
        window.getSelection().empty();

    $("#result_table").css("width", "").css("margin", "");
}

function doAddUrl() {

    var url = $("#url-to-add-input").val();
    if (url.length == 0)
        return;

    var params = {
        action: "lookup",
        url: url
    }
    
    $.ajax("./editor_ajax.php", {data: params})
    .done((resultString)=>{
        try {
            console.log(resultString);
            var rowData = JSON.parse(resultString);
            
            var readerUrl = rowData.zlink ? makeReaderUrl(rowData.zlink) : "#";
            var draftUrl = rowData.zlink ? makeDraftUrl(rowData.zlink) : "#";
            var twitterUrl = rowData.url ? makeTwitterUrl(rowData.url, rowData.title) : "#";
            var facebookUrl = rowData.url ? makeFacebookUrl(rowData.url, rowData.title) : "#";
            var nRows = $('#result_table tr').length;

            var newRow = createRow(nRows, rowData.paper, rowData.title, url, readerUrl, draftUrl, twitterUrl, facebookUrl);

            gDataTable.row.add([
                nRows-1,
                newRow.childNodes[1].innerHTML,
                newRow.childNodes[2].innerHTML,
                newRow.childNodes[3].innerHTML,
                newRow.childNodes[4].innerHTML,
                newRow.childNodes[5].innerHTML,
                newRow.childNodes[6].innerHTML                
            ]);
            
            var commentRow = createCommentRow(nRows);
            gDataTable.row.add([
                nRows,
                commentRow.childNodes[1].innerHTML,
                commentRow.childNodes[2].innerHTML,
                commentRow.childNodes[3].innerHTML,
                commentRow.childNodes[4].innerHTML,
                commentRow.childNodes[5].innerHTML,
                commentRow.childNodes[6].innerHTML
            ]);
            
            gDataTable.draw();
            
            var allRows = $("#result_table tr");
            allRows[allRows.length-3].childNodes[2].style = "padding-left:30px";
            
            var delCell = allRows[allRows.length-3].childNodes[1];
            delCell.classList.add("del-btn");

            var commentRow = allRows[allRows.length-2];
            
            delCell = allRows[allRows.length-2].childNodes[1];
            $(delCell).css("background-color", "#d0d0d0");
            
            var commentCell = commentRow.childNodes[2];
            commentCell.style = "font-weight:bold";
            commentCell.setAttribute("contenteditable", "true");
            commentCell.setAttribute("colspan", 4);
        }
        catch (e) {
            alert("Unable to get info for URL: " + (e.message ? e.message : e));
        }
    })
    .fail((e)=>{
        alert("Unable to add the URL" + e);
        showSpin(false);
    });

}

function doToggleHelp() {
    $("#help-pane").toggle();
}