// ltesearch.js

var USE_TEST_DATA = false;
var DO_FILTER = true;

var bFirstQuery = true;
var gDataTable = null;

var docTitle;

var DATA_TABLE_OPTIONS = {
    "paging": false,
    "scrollY": "75vh",
    "scrollCollapse": true,
    "order": [ [0, "desc"], [1, "asc"]],
    "columns": [
        {type: "date", width: "100px"},
        {type: "text", width: "150px"},
        {type: "html"},
        {type: "text"}
        ]
}

$(document).ready(function() {
    docTitle = document.title;
    
    $("#digest").hide();
    $("#loading").hide();
    $("#fetch").click(getDigest);
    $("#sign-out").click(signOut);

    var urlVars = getUrlVars();

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

    prepareAuth();

    hideShowBookMark();

    $("#region").change(hideShowBookMark);
});

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

function handleGoogleAuth(credResponse) {
    try {
        console.log("auth: clientId=" + credResponse.clientId);
        console.log("auth: credential=" + credResponse.credential);
        
        var jwt = credResponse.credential.split(".");
        var decoded = atob(jwt[1]); // decode payload
        payload = JSON.parse(decoded);
        localStorage.setItem("token", btoa(payload.email));
        prepareAuth();
        getDigest();
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

function signOut() {
    var auth2 = gapi.auth2.getAuthInstance();
    auth2.signOut().then(function () {
        console.log('User signed out.');
        localStorage.setItem("token", null);
        prepareAuth();
    });
}

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
//        date = date.toLocaleDateString("en-US",{month: "short", day: "numeric"});
        
        date = Intl.DateTimeFormat('en-US').format(date);
        
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
    var specificSearch = getQueryVariable("s");
    if (specificSearch) {
        url += "&s=" + encodeURIComponent(specificSearch);
    }
    var filterStrength = getQueryVariable("filter");
    if (filterStrength) {
    	url += "&filter=" + filterStrength;
    }
    
    document.cookie = "region=" + area + "; Max-Age=9999999";

    $.ajax({
        url: url,
        type: 'POST',
        data: {"tkn":localStorage.getItem("token")},
        success: function(data) { $("#digest").show(); $("#loading").hide(); buildResultTable(JSON.parse(data)) },
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

