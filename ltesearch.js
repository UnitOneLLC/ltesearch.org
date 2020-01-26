// ltesearch.js

var USE_TEST_DATA = false;
var DO_FILTER = true;

var bFirstQuery = true;
var gDataTable = null;

var docTitle;

var DATA_TABLE_OPTIONS = {
    "lengthMenu": [10],
    "paging": false,
    "scrollY": 400,
    "order": [ [0, "desc"], [1, "asc"]],
    "columns": [
        {type: "date", width: "100px"},
        {type: "text"},
        {type: "html"},
        {type: "text"}
        ]
}

$(document).ready(function() {
    docTitle = document.title;
    
    $("#digest").hide();
    $("#loading").hide();
    $("#fetch").click(getDigest);

    var urlVars = getUrlVars();

    DATA_TABLE_OPTIONS.scrollY = Math.round($(window).height()-180);

    var reg = urlVars["region"];
    var bRegionSet = false;    
    if (reg) {
	  $("#region").val(reg);
	  bRegionSet = true;
    }
    else {
    	var area = document.cookie.indexOf("region=");
    	if (area != -1) {
    		area = document.cookie.substr(area + "region=".length);
    		if (area.indexOf(";") != -1)
    			area = area.substr(0, area.indexOf(";"));
    		if ((area.length > 0) && (area != "null")) {
    		  $("#region").val(area);
    		  bRegionSet = true;
    		}
    	}
    }
	if (!bRegionSet) {
	  $("#region").val("Massachusetts");
	}

    hideShowBookMark();

    getDigest();
    
    $("#region").change(hideShowBookMark);
});

function hideShowBookMark() {
    if ($("#region").val() === "Massachusetts")
        $("#bookmark").show();
    else 
        $("#bookmark").hide();
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
    	var tblMarkup = "<table id='digest' class='hover stripe'><thead><tr><th>Date</th><th>Source</th><th>Item</th><th>Summary</th></tr></thead><tbody></tbody></table>";
    	parent.append(tblMarkup);
    	table = $('#digest');
    	if (gDataTable)
    		gDataTable.destroy();
    }
    else {
    	bFirstQuery = false;
    }
    
    for (i in jsonArr) {
        d = jsonArr[i];

		date = (new Date(d.pubDate.substr(0,4),parseInt(d.pubDate.substr(5,2))-1,d.pubDate.substr(8,2)));
        date = date.toLocaleDateString("en-US",{month: "short", day: "numeric"});
        if (d.highlight == "true") {
            row = "<tr class=row-highlight>";
        }
        else {
            row = "<tr>";
        }
        row += "<td>" + date + "</td>";
        row += "<td>" + d.paper + "</td>";
        row += "<td>" + "<a target='_blank' href='" + d.url + "'>" + d.title + "</a></td>";
        row += "<td>" + d.pubDate + " " + d.description + "</td>";
        table.append(row);
    }
    document.title = docTitle + " (" + jsonArr.length + ")";

    gDataTable = table.DataTable(DATA_TABLE_OPTIONS);
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
    
    $("#digest tbody tr").remove();
    $("#loading").show();
    var url = 'ltesearch.php';
    var area = $("#region").val();
    url += "?region=" + area;
    url += "&action=search";
    
    var topic = getQueryVariable("topic");
    if (topic) {
    	url += "&topic=" + topic;
    }
    var filterStrength = getQueryVariable("filter");
    if (filterStrength) {
    	url += "&filter=" + filterStrength;
    }
    
    document.cookie = "region=" + area + "; Max-Age=9999999";

    $.ajax({
        url: url,
        type: 'GET',
        success: function(data) { $("#digest").show(); $("#loading").hide(); buildResultTable(JSON.parse(data)) },
        cache: false,
        contentType: false,
        processData: false
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
