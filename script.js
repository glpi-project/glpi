 
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


