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
        /* buttons */ {
            data: null,
            searchable: false,
            sortable: false,
            render: function (data, type, full, meta) {
                if (type === 'display') {
                    var $span = $('<span></span>');
                    $('<button class="dtMoveUp">  ↑  </button>').appendTo($span);
                    $('<button class="dtMoveDown">  ↓  </button>').appendTo($span);
                    
                    return $span.html();
                }
                return data;
            }
        },
        /* del btn */ {type: "html"},
        /* paper   */ {type: "text"},
        /* title   */ {type: "html"},
    ],
    'drawCallback': function (settings) {

        if (gDataTable) {
            var rows = $("#result_table")[0].rows;
            
            for (var i=1; i < rows.length; ++i) {
                var tRowElem = rows[i];
                var rowObj = gDataTable.row(tRowElem);
                if (rowObj.data()) {
                    var rowData = rowObj.data();
                    rowData[0] = getZeroPaddedStringForInt(i-1);
                    rowObj.data(rowData);
                }
            }
        }
        // Remove previous binding before adding it
        $('.dtMoveUp').unbind('click');
        $('.dtMoveDown').unbind('click');
        
        // Bind clicks to functions
        $('.dtMoveUp').click(moveUp);
        $('.dtMoveDown').click(moveDown);

    }
};


function isFirstRow(t, r) {
    // Get the first row of the table
    var firstRow = t.rows[1];
    
    // Check if the provided row is the first row
    return r === firstRow;
}

function isLastRow(t, r) {
    // Get the last row index in the table
    var lastRowIndex = t.rows.length - 1;
    
    // Get the last row of the table
    var lastRow = t.rows[lastRowIndex];
    
    // Check if the provided row is the last row
    return r === lastRow;
}

function moveUp() {
    var tr = $(this).parents('tr');
    
    if (isFirstRow($("#result_table")[0], tr[0]))
        return;
    
    moveRow(tr, 'up');
}

// Move the row down
function moveDown() {
    var tr = $(this).parents('tr');
    
    if (isLastRow($("#result_table")[0], tr[0]))
        return;
    
    moveRow(tr, 'down');
}

function getZeroPaddedStringForInt(n) {
    if (n < 10) 
        return "0" + n;
    else
        return "" + n;
}

// Move up or down (depending...)
function moveRow(row, direction) {
//    var index = gDataTable.row(row).index();
    var index = parseInt(row.children().first()[0].innerText);
    
    var order = -1;
    if (direction === 'down') {
        order = 1;
    }
//    var data1 = gDataTable.row(index).data();
    var data1 = gDataTable.rows().data()[index];
    data1[0] = getZeroPaddedStringForInt(parseInt(data1[0]) + order);
    
    var data2 = gDataTable.rows().data()[index + order];
    data2[0] = getZeroPaddedStringForInt(parseInt(data2[0]) - order);
    
    var r = rowFromSeq(index);
    if (r) {
        r.data(data2);
    }
    r = rowFromSeq(index + order)
    if (r) {
        r.data(data1);
    }
    
    gDataTable.page(0).draw(false);
}

function rowFromSeq(seq) {
    var rows = gDataTable.rows();
    for (var i=0; i < rows.data().length; ++i) {
        var row = rows.row(i);
        if (row.data()) {
            var rowSeq = parseInt(row.data()[0]);
            if (rowSeq == seq) {
                return row;
            }
        }
    }
    return null;
}

$(document).ready(function() {
    $("#copy-btn").click(doCopy);
    $("#add-url-btn").click(doAddUrl);
    gDataTable = buildResultTable(items);
});

function isFirefox() {
    return !(navigator.clipboard && navigator.clipboard.write);
}

function createRow(seq, paperName, text, data) {
    var row = document.createElement("tr");
    row.__lte = data;
    var arrowsCell = document.createElement("td");
    var seqCell = document.createElement("td");
    var delBtnCell = document.createElement("td");
    var paperCell = document.createElement("td");
    var linkCell = document.createElement("td");

    seq = getZeroPaddedStringForInt(seq);

    row.appendChild(seqCell);
    row.appendChild(arrowsCell);
    row.appendChild(delBtnCell);
    row.appendChild(paperCell);
    row.appendChild(linkCell);
    
    
    seqCell.appendChild(document.createTextNode(seq));
    delBtnCell.innerHTML = "<span onclick='deleteRow(this)' class=del-btn>x</span>";
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
    
    if (isFirefox()) {
        return legacyCopyToClipboard();
    }

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
        $("#__end__").remove();
        $("#copy-feedback").text("" + gDataTable.rows().count() + " articles copied to clipboard")
    }).catch((error) => {
        console.error('Unable to copy HTML to clipboard', error);
    });
}

function legacyCopyToClipboard() {
    var clipDiv = document.getElementById('copy_buffer');
    var range = document.createRange();
    range.setStart(clipDiv, 0);
    range.setEnd(document.getElementById("__end__"), 0);        
    
    var selObj = window.getSelection()
    selObj.removeAllRanges();
    selObj.addRange(range);
    
    if (document.execCommand('copy')) {
        $(clipDiv).remove();
        $("#__end__").remove();        
        $("#copy-feedback").text("" + gDataTable.rows().count() + " articles copied to clipboard")
    } else {
        console.error('Unable to copy HTML to clipboard (legacy)');    
    }
}

function doCopy() {
    const cssContainer = "";
    const cssTable = "background-color: #eee; width: 90%; max-width: 640px; font-family: arial; margin-left: 20px";
    const cssPaperCell = "width: 27%; font-style: italic; text-align: right; padding-right: 10px";
    const cssLinkCell = "width: 65";
    const cssBtnCell = " width: 45px;  text-align: center; font-size:0.8em";
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

        if (isFirefox())
            markUp += "<span style='padding:0 20px; display:inline; font-weight:default'>&nbsp;</span>";
        
        $(container).append(markUp);
    }
    $("#copy_buffer").append($(container));
    var endMark = document.createElement("div");
    $(endMark).prop("id", "__end__");
    $("#copy_buffer").append($(endMark));
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
                getZeroPaddedStringForInt(nRows-1),
                newRow.childNodes[1].innerHTML,
                newRow.childNodes[2].innerHTML,
                newRow.childNodes[3].innerHTML,
                newRow.childNodes[4].innerHTML,                
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
