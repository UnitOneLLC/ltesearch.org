// editor.js

var USE_TEST_DATA = false;

const READER_URL = "https://ltesearch.org/read";
const DRAFT_URL = "https://ltesearch.org/draft";
const HAMBURGER = "\u2630";

var gDataTable = null;

var docTitle = window.docTitle;

var DATA_TABLE_OPTIONS = {  
    "pageLength": 500,
    "rowReorder": true,
    "paging": false,
    "bFilter": false,
    "columns": [
        /* seq #   */ {type: "text", className: 'reorder'},
        /* del btn */ {type: "html"},
        /* paper   */ {type: "text"},
        /* title   */ {type: "html"}
    ]
};

$(document).ready(function() {
    $("#copy-btn").click(doCopy);
    $("#add-url-btn").click(doAddUrl);
    gDataTable = buildResultTable(items);
});

function createRow(seq, paperName, text, data) {
    var row = document.createElement("tr");
    row.__lte = data;
    var seqCell = document.createElement("td");
    var delBtnCell = document.createElement("td");
    var paperCell = document.createElement("td");
    var linkCell = document.createElement("td");

    row.appendChild(seqCell);
    row.appendChild(delBtnCell);
    row.appendChild(paperCell);
    row.appendChild(linkCell);
    
    seqCell.appendChild(document.createTextNode(seq));
    delBtnCell.innerHTML = "<span onclick='deleteRow(this)' class=del-btn contenteditable=false>x</span>";
    $(delBtnCell).addClass("del-btn");
    paperCell.appendChild(document.createTextNode(paperName));
    linkCell.appendChild(document.createTextNode(text));
    
    return row;
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
        var row = createRow(i, d.paper, d.title, d);
        tbody[0].appendChild(row);
    }
    
    return table.DataTable(DATA_TABLE_OPTIONS);
}

function deleteRow(cell) {
    gDataTable
    .row( $(cell).parents('tr') )
    .remove()
    .draw();    
}

function getLteObjectsFromTable(tableNode) {
    const lteObjects = [];
    
    $(tableNode).find('tr').each(function() {
        const lteObject = $(this).prop('__lte');
        
        if (lteObject) {
            lteObjects.push(lteObject);
        }
    });
    
    return lteObjects;
}

function copyDivToClipboard() {
    // Get the element by ID
    var clipDiv = document.getElementById('clipDiv');
    
    // Get the HTML content of the element
    var contentToCopy = clipDiv.innerHTML;
    
    // Use the Clipboard API to copy the HTML content
    navigator.clipboard.write([
        new ClipboardItem({
            "text/html": new Blob([contentToCopy], { type: "text/html" })
        })
    ]).then(() => {
        $(clipDiv).remove();
        $("#copy-feedback").text("" + gDataTable.rows().count() + " articles copied to clipboard")
    }).catch((error) => {
        console.error('Unable to copy HTML to clipboard', error);
    });
}

function doCopy() {
    const cssContainer = "";
    const cssTable = "background-color: #eee; width: 80%; max-width: 640px; font-family: arial; margin-left: 28px";
    const cssPaperCell = "width: 25%; font-style: italic; text-align: right; padding-right: 10px";
    const cssLinkCell = "width: 67";
    const cssBtnCell = " width: 50px;  text-align: center";
    const cssBtnLink = "background-color: rgb(17,17,170); color: #FFF; font-weight: bold; text-decoration: none; font-variant: small-caps";
    const textLinkStem = "https://ltesearch.org/read?z=";
    const draftLinkStem = "https://ltesearch.org/draft?z=";    

    var lteObjs = getLteObjectsFromTable($("#result_table")[0]);
    var container = document.createElement("div");
    $(container).prop("id", "clipDiv");
    $(container).append("<br>");
    
    for (var i=0; i < lteObjs.length; ++i) {
        
        var textLink = textLinkStem + lteObjs[i].zlink;
        var draftLink = draftLinkStem + lteObjs[i].zlink;
        var markUp ="<table style='" + cssTable + "'>" +
                        "<tr>" +
                            "<td style='"+ cssPaperCell + "'>" + // paper
                                lteObjs[i].paper +
                            "</td>" +
                            "<td style='" + cssLinkCell + "'>" + // link
                                "<a href='" + lteObjs[i].url + "'>" + lteObjs[i].title + "</a>" +
                            "</td>" +
                            "<td style='" + cssBtnCell + "'>"+  // TEXT
                                "<a style='" + cssBtnLink + "' + href='" + textLink + "'>&nbsp;&nbsp;text&nbsp;&nbsp;</a>" +
                            "</td>" +
                            "<td style='" + cssBtnCell + "'>"+  // DRAFT
                                "<a style='" + cssBtnLink + "' + href='" + draftLink + "'>&nbsp;&nbsp;draft&nbsp;&nbsp;</a>" +
                            "</td>" +
                        "</tr>"+
                    "</table>";
        
        $(container).append(markUp);
    }
    $("body").append($(container));
    copyDivToClipboard();
}

function doAddUrl() {

    var url = $("#url-to-add-input").val();
    $("#url-to-add-input").val("");
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
            
            var nRows = $('#result_table tr').length;

            var newRow = createRow(nRows, rowData.paper, rowData.title, rowData);
            gDataTable.row.add([
                '' + nRows-1,
                newRow.childNodes[1].innerHTML,
                newRow.childNodes[2].innerHTML,
                newRow.childNodes[3].innerHTML
            ]);
            
            gDataTable.draw();
            var rowCount = gDataTable.rows().count();
            var newTr = gDataTable.row(rowCount-1).node();
            newTr.__lte = rowData;
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
