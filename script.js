window.onload=montre;
function montre(id) {
var d = document.getElementById(id);
	for (var i = 1; i<=10; i++) {
		if (document.getElementById('smenu'+i)) {document.getElementById('smenu'+i).style.display='none';displaySelectBoxes();
	}
	}
if (d) {d.style.display='block'; hideSelectBoxes();}
}

/**
* Hides all drop down form select boxes on the screen so they do not appear above the mask layer.
* IE has a problem with wanted select form tags to always be the topmost z-index or layer
*/
function hideSelectBoxes(){
for(var i = 0; i < document.forms.length; i++) {
for(var e = 0; e < document.forms[i].length; e++){
if(document.forms[i].elements[e].tagName == "SELECT") {
document.forms[i].elements[e].style.visibility="hidden";
}
}
}
}

/**
* Makes all drop down form select boxes on the screen visible so they do not reappear after the dialog is closed.
* IE has a problem with wanted select form tags to always be the topmost z-index or layer
*/

function displaySelectBoxes() {
for(var i = 0; i < document.forms.length; i++) {
for(var e = 0; e < document.forms[i].length; e++){
if(document.forms[i].elements[e].tagName == "SELECT") {
document.forms[i].elements[e].style.visibility="visible";
}
}
}
}

function jumpTo(URL_List){ var URL = URL_List.options[URL_List.selectedIndex].value;  window.location.href = URL; }


browserName=navigator.appName;
browserVer=parseInt(navigator.appVersion);
if ((browserName=="Netscape" && browserVer>=3) || (browserName=="Microsoft Internet Explorer" && browserVer>=4)) version="n3";
else version="n2"; 

function historyback() { history.back(); }
 
function historyforward() { history.forward(); }


function fillidfield(Type,Id){
window.opener.document.forms["helpdeskform"].elements["computer"].value = Id;
window.opener.document.forms["helpdeskform"].elements["device_type"].value = Type;
window.close();}


