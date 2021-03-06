// ltesearch.js

var USE_TEST_DATA = false;
var DO_FILTER = true;

var bFirstQuery = true;
var gDataTable = null;

var docTitle = window.docTitle;

var DATA_TABLE_OPTIONS = {
    "paging": false,
    "scrollY": "80vh",
    "scrollCollapse": true,
    "order": [ [0, "desc"], [1, "asc"]],
    "columns": [
        {type: "date", width: "150px"},
        {type: "text"},
        {type: "text"},
        {type: "text"},
        {type: "text"},
        {type: "text"}
        ]
        
}

$(document).ready(function() {
    buildActivityTable(activity_json);
});

// date region ipaddr topic nresults

function buildActivityTable(jsonArr) {
	if (!Array.isArray(jsonArr)) {
		alert(jsonArr.error);
		return;
	}

    var table = $('#digest');
        
    for (i in jsonArr) {
        d = jsonArr[i];

//		date = (new Date(d.pubDate.substr(0,4),parseInt(d.pubDate.substr(5,2))-1,d.pubDate.substr(8,2)));
//        date = date.toLocaleDateString("en-US",{month: "short", day: "numeric"});
        var row = "<tr>";
        d.timestamp = d.timestamp.substr(0,10) + 'T' + d.timestamp.substr(11) + "-00:00";
        var dt = (new Date(d.timestamp)).toLocaleString('en-US',{"hour12":false});
        var email = "";
        if (d.usertoken != null)
            email = atob(d.usertoken);

        row += "<td>" + dt + "</td>";
        row += "<td>" + d.region + "</td>";
        row += "<td>" + d.ipaddr + "</td>";
        row += "<td>" + d.topic + "</td>";
        row += "<td>" + d.nresults + "</td>";
        row += "<td>" + email + "</td>";
        table.append(row);
    }
    gDataTable = table.DataTable(DATA_TABLE_OPTIONS);
}
