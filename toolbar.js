
// Barre de raccourcis
// derive du:
// bbCode control by subBlue design : www.subBlue.com

// Check for Browser & Platform for PC & IE specific bits
// More details from: http://www.mozilla.org/docs/web-developer/sniffer/browser_type.html
var clientPC = navigator.userAgent.toLowerCase(); // Get client info
var clientVer = parseInt(navigator.appVersion); // Get browser version

var is_ie = ((clientPC.indexOf("msie") != -1) && (clientPC.indexOf("opera") == -1));
var is_nav = ((clientPC.indexOf('mozilla') != -1) && (clientPC.indexOf('spoofer') == -1)
                && (clientPC.indexOf('compatible') == -1) && (clientPC.indexOf('opera') == -1)
                && (clientPC.indexOf('webtv') == -1) && (clientPC.indexOf('hotjava') == -1));
var is_moz = 0;

var is_win = ((clientPC.indexOf("win") != -1) || (clientPC.indexOf("16bit") != -1));
var is_mac = (clientPC.indexOf("mac") != -1);

// From http://www.massless.org/mozedit/
function mozWrap(txtarea, open, close)
{
	var selLength = txtarea.textLength;
	var selStart = txtarea.selectionStart;
	var selEnd = txtarea.selectionEnd;
	if (selEnd == 1 || selEnd == 2) {
		selEnd = selLength;
	}
	var s1 = (txtarea.value).substring(0,selStart);
	var s2 = (txtarea.value).substring(selStart, selEnd)
	var s3 = (txtarea.value).substring(selEnd, selLength);
	txtarea.value = s1 + open + s2 + close + s3;
	return;
}

function raccourciTypo(toolbarfield, begin, end)
{
	var txtarea = toolbarfield;
	txtarea.focus();
	if ((clientVer >= 4) && is_ie && is_win) {
		var str = document.selection.createRange().text;
		var sel = document.selection.createRange();
		sel.text = begin + str + end;
	} else if (txtarea.selectionEnd && (txtarea.selectionEnd - txtarea.selectionStart > 0)) {
		mozWrap(txtarea, begin, end);
	}
	return;
}





function drawToolbar(toolbarfield)
{
	document.write('<link rel="stylesheet" type="text/css" href="toolbar.css" />\
	<p class="toolbar">\
	<a href="javascript:raccourciTypo(document.' + toolbarfield + ', \'[b]\', \'[/b]\');"><b>Gras</b></a> \
	<a href="javascript:raccourciTypo(document.' + toolbarfield + ', \'[i]\', \'[/i]\');"><i>Italique</i></a> \
	<a href="javascript:raccourciTypo(document.' + toolbarfield + ', \'[u]\', \'[/u]\');">Souligné</a> \
	<a href="javascript:raccourciTypo(document.' + toolbarfield + ', \'[code]\', \'[/code]\');">Code</a> \
	<a href="javascript:raccourciTypo(document.' + toolbarfield + ', \'[email]\', \'[/email]\');">Email</a> \
	</p>');
}
