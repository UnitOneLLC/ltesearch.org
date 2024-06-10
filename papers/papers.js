// papers.js

var DATA_TABLE_OPTIONS = {
    "paging": false,
    "autoWidth": false,
/*    "scrollY": "80vh",*/
/*    "scrollCollapse": true, */
    "order": [ [0, "asc"]],
    "columns": [
        {type: "text", width: "25%"},
        {type: "html", width: "40%"},
        {type: "text", width: "40%"},
        {type: "text", width: "5%"}
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
        var d = jsonArr[i];
        var url =d.domain;
        if (url.indexOf("http") == -1) {
            url = "http://" + url;
        }

        var row = "<tr>";
        row += "<td>" + d.name + "</td>";
        row += "<td>" + "<a href='" + url + "' target=_blank>" + d.domain + "</a></td>";
        row += "<td class='url'>" + d.lteaddr + "</td>";
        row += "<td>" + d.max_words + "</td>";
        row += "</tr>";
        table.append(row);
    }
    table.DataTable(DATA_TABLE_OPTIONS);
}
