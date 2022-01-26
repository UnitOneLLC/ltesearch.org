// papers.js

var DATA_TABLE_OPTIONS = {
    "paging": false,
    "scrollY": "80vh",
    "scrollCollapse": true,
    "order": [ [0, "asc"]],
    "columns": [
        {type: "text"},
        {type: "html"},
        {type: "text"},
        {type: "text"}
        ]
}

$(document).ready(function() {
    buildPapersTable(papers_json);
});

// name domain lteaddr max_words

function buildPapersTable(jsonArr) {
	if (!Array.isArray(jsonArr)) {
		alert(jsonArr.error);
		return;
	}

    var table = $('#digest');
        
    for (i in jsonArr) {
        d = jsonArr[i];

        var row = "<tr>";
        row += "<td>" + d.name + "</td>";
        row += "<td>" + "<a href='http://" + d.domain + "' target=_blank>" + d.domain + "</a></td>";
        row += "<td>" + d.lteaddr + "</td>";
        row += "<td>" + d.max_words + "</td>";
        row += "</tr>";
        table.append(row);
    }
    table.DataTable(DATA_TABLE_OPTIONS);
}
