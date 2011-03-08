/**
 * This array is used to remember mark status of rows in browse mode
 */
var marked_row = new Array;

var timeoutglobalvar;

//modifier la propri��display d'un �ement
function setdisplay (objet, statut) {
	var e = objet;
	if(e.style.display != statut){
		e.style.display = statut;
	}
	return true;
}

//tester le type de navigateur
function isIe(){
	var ie = false;	
	var appVer = navigator.appVersion.toLowerCase();
	var iePos = appVer.indexOf('msie');
	if (iePos != -1) {
		var is_minor = parseFloat(appVer.substring(iePos+5,appVer.indexOf(';',iePos)));
		var is_major = parseInt(is_minor);
	}
	if (navigator.appName.substring(0,9) == "Microsoft") { 
		// Check if IE version is 6 or older
		if (is_major <= 6) {
			ie = true;
		}
	}
	return ie;
}

function cleandisplay(id){
	var e = document.getElementById(id);
	if(e){
		setdisplay(e,'block');
		if (isIe()){
			doHideSelect(e);
		}
	}
}

function cleanhide(id){
	var e = document.getElementById(id);
	if(e){
		if(isIe()){
			doShowSelect(e);
		}
		setdisplay(e,'none');
	}
}


function completecleandisplay(id){
	var e = document.getElementById(id);
	if(e){
		setdisplay(e,'block');


/*	if(document.getElementById('show_entities')){
		var oneTime=0;
		var divHeight = document.getElementById('show_entities').offsetHeight;
		var divWidth = document.getElementById('show_entities').offsetWidth;
				
		if (divHeight>300){

			
			document.getElementById('show_entities').style.overflow = 'auto';
			document.getElementById('show_entities').style.height = '400px';
			// document.getElementById('show_entities').style.width =  divWidth + 'px';
			document.getElementById('show_entities').style.width =  '300px';

		}

	

	}	
*/


		if (isIe()) {
			e.onmouseleave = function(){ completecleanhide(id) };
			hideSelect(0,0,	document.documentElement.clientWidth,document.documentElement.clientHeight);
		} else {
			e.onmouseout = function(){ completecleanhide(id) };
		}
	}
}

function completecleanhide(id){
	var e = document.getElementById(id);
	if(e){
		setdisplay(e,'none');
		if(isIe()){
			showSelect(0,0,document.documentElement.clientWidth,document.documentElement.clientHeight);
		}
	}
}

//effacer tous les smenu du menu principal
//afficher les selects du document
function hidemenu(idMenu){
	var e = document.getElementById(idMenu);
	var e = e.getElementsByTagName('ul');
	for(var i = 0; i < e.length; i++) {
		if (e[i]) {
			if (isIe()){
				doShowSelect(e[i]);
			}
			setdisplay(e[i],'none');
		}
	}	
}

//masquer le smenu actif par timeout
function afterView(idMenu){
	setdisplay(idMenu,'none');
	if (isIe()) {
		doShowSelect(idMenu);
	}
}

//execute la fonction showSelect
function doShowSelect(objet){
	if (objet) {
		//correction du bugg sur IE
		if(isIe()){
			if(setdisplay(objet,'block')){
				var selx=0; var sely=0; var selp;
				selx=getLeft(objet);
				sely=getTop(objet);

				selw = objet.offsetWidth;
				selh = objet.offsetHeight;
				showSelect(selx,sely,selw,selh);
			}
			if(setdisplay(objet,'none')){
				return true;
			}		
		}
	}
}

//affiche les select du document
function showSelect(x,y,w,h){
	var selx,sely,selw,selh;
	var sel = document.getElementsByTagName("SELECT");
	for(var i=0; i<sel.length; i++){
		selx=0; sely=0; var selp;
		selx=getLeft(sel[i]);
		sely=getTop(sel[i]);
		selw=sel[i].offsetWidth;
		selh=sel[i].offsetHeight;
		// || Manage position error computation
		if((selx+selw>x && selx<x+w && sely+selh>y && sely<y+h ) || selx<0 || sely<0)
			sel[i].style.visibility="visible";
	}
	return true;
}

//execute la fonction hideMenu
function doHideSelect(object){
	var e = object;
	if(isIe()){
		var selx=0; var sely=0; var selp;

		selx=getLeft(e);
		sely=getTop(e);

		selw = e.offsetWidth;
		selh = e.offsetHeight;
		hideSelect(selx,sely,selw,selh);
	}
	return true;
}

//masque les select du document
function hideSelect(x,y,w,h){
	var selx,sely,selw,selh,i;
	var sel=document.getElementsByTagName("SELECT");
	for(i=0;i<sel.length;i++){
		selx=0; sely=0; var selp;
		selx=getLeft(sel[i]);
		sely=getTop(sel[i]);
		selw=sel[i].offsetWidth;
		selh=sel[i].offsetHeight;
		// || Manage position error computation
		if((selx+selw>x && selx<x+w && sely+selh>y && sely<y+h ) || selx<0 || sely<0 ){
			sel[i].style.visibility="hidden";
		} 	
	}
	return true;
}

function menuAff(id,idMenu){
	var m = document.getElementById(idMenu);
	var item = m.getElementsByTagName('li');
	for(var i=0; i<item.length; i++){
		if(item[i].id == id)
			var ssmenu = item[i];
	}	
	var m = m.getElementsByTagName('ul');
	
	if(isIe()){
		//masquage des �ements select du document
		if(m){
			for (var i=1; i<10 ;i++) { //probl�e dans le listage et le nomage des menus xhtml
				//listage des ��ents li nomm� du type smenu + i
				var e = document.getElementById('menu'+i);
				if(e){
					var smenu = e.getElementsByTagName('ul');
					doShowSelect(smenu[0]);
				}
			}
		}		
	}
	
	if (ssmenu) {
		var smenu = ssmenu.getElementsByTagName('ul');
		if (smenu) {
			//masquer tous les smenu ouverts
			for(var i = 0; i < m.length; i++){
				setdisplay(	m[i],'none');
			}
			setdisplay(smenu[0],'block');
			clearTimeout(timeoutglobalvar);
			//timeoutglobalvar = setTimeout(function(){afterView(smenu[0])},1000);
			if (isIe()) {
				ssmenu.onmouseleave = function(){ timeoutglobalvar = setTimeout(function(){afterView(smenu[0])},300); };
				doHideSelect(smenu[0]);
			} else {
				ssmenu.onmouseout = function(){ timeoutglobalvar = setTimeout(function(){afterView(smenu[0])},300); };
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
	window.opener.document.forms["helpdeskform"].elements["items_id"].value = Id;
	window.opener.document.forms["helpdeskform"].elements["itemtype"].value = Type;
	window.close();
}

/**
 * marks all checkboxes inside the given element
 * the given element is usaly a table or a div containing the table or tables
 *
 * @param    container_id    DOM element
 */
function markCheckboxes( container_id ) {
	var checkboxes = document.getElementById(container_id).getElementsByTagName('input');
	for ( var j = 0; j < checkboxes.length; j++ ) {
		checkbox=checkboxes[j];
		if ( checkbox && checkbox.type == 'checkbox' ) {
			if ( checkbox.disabled == false ) {
				checkbox.checked = true;
			}
		}
	}

	return true;
}


/**
 * marks all checkboxes inside the given element
 * the given element is usaly a table or a div containing the table or tables
 *
 * @param    container_id    DOM element
 */
function unMarkCheckboxes( container_id ) {
	var checkboxes = document.getElementById(container_id).getElementsByTagName('input');
	for ( var j = 0; j < checkboxes.length; j++ ) {
		checkbox=checkboxes[j];
		if ( checkbox && checkbox.type == 'checkbox' ) {
			checkbox.checked = false;
		}
	}

	return true;
}
/**
 * toggle all checkboxes inside the given element
 * the given element is usaly a table or a div containing the table or tables
 *
 * @param    container    DOM element
 */
function toggleCheckboxes( container_id ) {
	var checkboxes = document.getElementById(container_id).getElementsByTagName('input');
		for ( var j = 0; j < checkboxes.length; j++ ) {
			checkbox=checkboxes[j];
			if ( checkbox && checkbox.type == 'checkbox' ) {
				if ( checkbox.disabled == false) {
					if (checkbox.checked == false){
						checkbox.checked = true;
					} else {
						checkbox.checked = false;
					}
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

function getLeft(MyObject){
//Fonction permettant de connaître la position d'un objet
//par rapport au bord gauche de la page.
//Cet objet peut être à l'intérieur d'un autre objet.
    if (MyObject.offsetParent)
        return (MyObject.offsetLeft + getLeft(MyObject.offsetParent));
    else
        return (MyObject.offsetLeft);
} 

function getTop(MyObject){
//Fonction permettant de connaître la position d'un objet
//par rapport au bord haut de la page.
//Cet objet peut être à l'intérieur d'un autre objet.
    if (MyObject.offsetParent)
        return (MyObject.offsetTop + getTop(MyObject.offsetParent));
    else
        return (MyObject.offsetTop);
}


// id = id of the dive
// img_name = name attribut of the img item
// img_src_close = url of the close img
// img_src_open = url of the open img
function showHideDiv(id,img_name,img_src_close,img_src_open) {
	//safe function to hide an element with a specified id
	if (document.getElementById) { // DOM3 = IE5, NS6
		if (document.getElementById(id).style.display == 'none')
		{
			document.getElementById(id).style.display = 'block';
			if (img_name!=''){
				document[img_name].src=img_src_open;
			}
		}
		else
		{
			document.getElementById(id).style.display = 'none';
			if (img_name!=''){
				document[img_name].src=img_src_close;
			}
		}
			
	}
	else {
		if (document.layers) { // Netscape 4
			if (document.id.display == 'none')
			{
				document.id.display = 'block';
				if (img_name!=''){
					document[img_name].src=img_src_open;
				}
			}
			else	
			{
				document.id.display = 'none';
				if (img_name!=''){
					document[img_name].src=img_src_close;
				}
			}
		}
		else { // IE 4
			if (document.all.id.style.display == 'none')
			{
				document.all.id.style.display = 'block';
				if (img_name!=''){
					document[img_name].src=img_src_close;
				}
			}
			else
			{
				document.all.id.style.display = 'none';	
				if (img_name!=''){
					document[img_name].src=img_src_close;
				}
			}
		}
	}
}

function toogle(id,img_name,img_src_yes,img_src_no) {
	//safe function to hide an element with a specified id
	if (document.getElementById) { // DOM3 = IE5, NS6
		if (document.getElementById(id).value == '0')
		{
			document.getElementById(id).value = '1';
			if (img_name!=''){
				document[img_name].src=img_src_yes;
			}
		}
		else
		{
			document.getElementById(id).value = '0';
			if (img_name!=''){
				document[img_name].src=img_src_no;
			}
		}
			
	}
	
	}

