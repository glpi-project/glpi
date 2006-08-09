
/**
 * This array is used to remember mark status of rows in browse mode
 */
var marked_row = new Array;

var timeoutglobalvar;
function setdisplay (objet, statut) {
	if (objet.style.display != statut) objet.style.display = statut;
}

function cleandisplay(id){
	var e=document.getElementById(id);
	if (e){
		var ie=false;

		var appVer = navigator.appVersion.toLowerCase();
		var iePos = appVer.indexOf('msie');
		if (iePos !=-1) {
			var is_minor = parseFloat(appVer.substring(iePos+5,appVer.indexOf(';',iePos)));
			var is_major = parseInt(is_minor);
 		}
	 	if (navigator.appName.substring(0,9) == "Microsoft")
	 	{ // Check if IE version is 6 or older
 			if (is_major <= 6) {
				ie=true;
			}
		}

		setdisplay(e,'block');

		if (ie){
			 var selx=0; var sely=0; var selp;
			 if(e.offsetParent){
				 selp=e;
				 while(selp.offsetParent){
				 	selp=selp.offsetParent;
					 selx+=selp.offsetLeft;
					 sely+=selp.offsetTop;
				 }
			 }
			 selx+=e.offsetLeft;
	 		sely+=e.offsetTop;
			 selw=e.offsetWidth;
			 selh=e.offsetHeight;
			hideSelect(selx,sely,selw,selh);
		}
	}
}

function cleanhide(id){
	var e=document.getElementById(id);
	if (e){
		var ie=false;

		var appVer = navigator.appVersion.toLowerCase();
		var iePos = appVer.indexOf('msie');
		if (iePos !=-1) {
			var is_minor = parseFloat(appVer.substring(iePos+5,appVer.indexOf(';',iePos)));
			var is_major = parseInt(is_minor);
 		}
	 	if (navigator.appName.substring(0,9) == "Microsoft")
	 	{ // Check if IE version is 6 or older
 			if (is_major <= 6) {
				ie=true;
			}
		}

		if (ie){
			 var selx=0; var sely=0; var selp;
			 if(e.offsetParent){
				 selp=e;
				 while(selp.offsetParent){
				 	selp=selp.offsetParent;
					 selx+=selp.offsetLeft;
					 sely+=selp.offsetTop;
				 }
			 }
			 selx+=e.offsetLeft;
	 		sely+=e.offsetTop;
			 selw=e.offsetWidth;
			 selh=e.offsetHeight;
			showSelect(selx,sely,selw,selh);
		}
		setdisplay(e,'none');

	}
}


function hidemenu(){
	var ie=false;

	var appVer = navigator.appVersion.toLowerCase();
	var iePos = appVer.indexOf('msie');
	 if (iePos !=-1) {
		 var is_minor = parseFloat(appVer.substring(iePos+5,appVer.indexOf(';',iePos)));
		 var is_major = parseInt(is_minor);
 	}
	 if (navigator.appName.substring(0,9) == "Microsoft")
	 { // Check if IE version is 6 or older
 		if (is_major <= 6) {
			ie=true;
		}
	}

	for (var i = 1; i<=10; i++) {
		var e=document.getElementById('smenu'+i);
		if (e) {
			if (ie){
				 var selx=0; var sely=0; var selp;
				 if(e.offsetParent){
					 selp=e;
					 while(selp.offsetParent){
					 	selp=selp.offsetParent;
						 selx+=selp.offsetLeft;
						 sely+=selp.offsetTop;
					 }
				 }
				 selx+=e.offsetLeft;
		 		sely+=e.offsetTop;
				 selw=e.offsetWidth;
				 selh=e.offsetHeight;
				showSelect(selx,sely,selw,selh);
			}
			setdisplay(e,'none');
		}
	}	
}

function montre(id) {
var d = document.getElementById(id);

var ie=false;

	var appVer = navigator.appVersion.toLowerCase();
	var iePos = appVer.indexOf('msie');
	 if (iePos !=-1) {
		 var is_minor = parseFloat(appVer.substring(iePos+5,appVer.indexOf(';',iePos)));
		 var is_major = parseInt(is_minor);
 	}
	 if (navigator.appName.substring(0,9) == "Microsoft")
	 { // Check if IE version is 6 or older
 		if (is_major <= 6) {
			ie=true;
		}
	}

	for (var i = 1; i<=10; i++) {
		var e=document.getElementById('smenu'+i);
		if (e) {
			setdisplay(e,'block');
			if (ie){
				 var selx=0; var sely=0; var selp;
				 if(e.offsetParent){
					 selp=e;
					 while(selp.offsetParent){
					 	selp=selp.offsetParent;
						 selx+=selp.offsetLeft;
						 sely+=selp.offsetTop;
					 }
				 }
				 selx+=e.offsetLeft;
		 		sely+=e.offsetTop;
				 selw=e.offsetWidth;
				 selh=e.offsetHeight;
				showSelect(selx,sely,selw,selh);
			}
			setdisplay(e,'none');
		}
	}
if (d) {
	setdisplay(d,'block'); 
	clearTimeout(timeoutglobalvar);
	timeoutglobalvar=setTimeout(function(){setdisplay(d, 'none')},5000);
	
	if (ie){
		 var selx=0; var sely=0; var selp;
		 if(d.offsetParent){
			 selp=d;
			 while(selp.offsetParent){
				 selp=selp.offsetParent;
				 selx+=selp.offsetLeft;
				 sely+=selp.offsetTop;
			 }
		 }
		 selx+=d.offsetLeft;
		 sely+=d.offsetTop;
		 selw=d.offsetWidth;
		 selh=d.offsetHeight;
		hideSelect(selx,sely,selw,selh);
	}
}
}

function showSelect(x,y,w,h){
	 var selx,sely,selw,selh,i;
	 var sel=document.getElementsByTagName("SELECT");
	 for(i=0;i<sel.length;i++){
	 selx=0; sely=0; var selp;
	 if(sel[i].offsetParent){
		 selp=sel[i];
		 while(selp.offsetParent){
			 selp=selp.offsetParent;
			 selx+=selp.offsetLeft;
			 sely+=selp.offsetTop;
		 }
		}
		selx+=sel[i].offsetLeft;
		sely+=sel[i].offsetTop;
		selw=sel[i].offsetWidth;
		selh=sel[i].offsetHeight;
		if(selx+selw>x && selx<x+w && sely+selh>y && sely<y+h)
		sel[i].style.visibility="visible";
	 }
 }

function hideSelect(x,y,w,h){
	var selx,sely,selw,selh,i;
	var sel=document.getElementsByTagName("SELECT");
	for(i=0;i<sel.length;i++){
		 selx=0; sely=0; var selp;
		 if(sel[i].offsetParent){
			 selp=sel[i];
			 while(selp.offsetParent){
				 selp=selp.offsetParent;
				 selx+=selp.offsetLeft;
				 sely+=selp.offsetTop;
			 }
		 }
		 selx+=sel[i].offsetLeft;
		 sely+=sel[i].offsetTop;
		 selw=sel[i].offsetWidth;
		 selh=sel[i].offsetHeight;
		 if(selx+selw>x && selx<x+w && sely+selh>y && sely<y+h)
		 sel[i].style.visibility="hidden";
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
window.close();
}

/**
 * marks all rows and selects its first checkbox inside the given element
 * the given element is usaly a table or a div containing the table or tables
 * From phpMyAdmin
 *
 * @param    container    DOM element
 */
function markAllRows( container_id ) {
    var rows = document.getElementById(container_id).getElementsByTagName('tr');
    var unique_id;
    var checkbox;

        for ( var i = 0; i < rows.length; i++ ) {

		checkboxes = rows[i].getElementsByTagName( 'input' );
	
		for ( var j = 0; j < checkboxes.length; j++ ) {
			checkbox=checkboxes[j];
			if ( checkbox && checkbox.type == 'checkbox' ) {
				unique_id = checkbox.name + checkbox.value;
				if ( checkbox.disabled == false ) {
					checkbox.checked = true;
					if ( typeof(marked_row[unique_id]) == 'undefined' || !marked_row[unique_id] ) {
						rows[i].className += ' marked';
						marked_row[unique_id] = true;
					}
				}
			}
		}
	}

        return true;
}


/**
 * marks all rows and selects its first checkbox inside the given element
 * the given element is usaly a table or a div containing the table or tables
 * From phpMyAdmin 
 *
 * @param    container    DOM element
 */
function unMarkAllRows( container_id ) {
        var rows = document.getElementById(container_id).getElementsByTagName('tr');
    var unique_id;
    var checkbox;

        for ( var i = 0; i < rows.length; i++ ) {
		checkboxes = rows[i].getElementsByTagName( 'input' );

		for ( var j = 0; j < checkboxes.length; j++ ) {
			checkbox=checkboxes[j];
			if ( checkbox && checkbox.type == 'checkbox' ) {
				unique_id = checkbox.name + checkbox.value;
				checkbox.checked = false;
				rows[i].className = rows[i].className.replace(' marked', '');
				marked_row[unique_id] = false;
			}
		}
	}
        return true;
}

function confirmAction(text,where){
	if (confirm(text)) {
		window.location = where;
	}
}
