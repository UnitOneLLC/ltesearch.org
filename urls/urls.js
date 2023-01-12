function copyToClipboard() {
    var outer = document.getElementById("start-copy");
    var last = document.getElementById("end-copy");
    outer.style.backgroundColor = "white";
    outer.style.color = "black";

    var range = document.createRange();
    
    range.setStart(outer, 0);
    range.setEnd(last, 0);
    
    var selObj = window.getSelection()
    selObj.removeAllRanges();
    selObj.addRange(range);
    var ok;
    if (document.execCommand('copy')) {
        document.getElementById("status").innerText = " copied";
    } else {
        document.getElementById("status").innerText = " copy failed";
    }
}
