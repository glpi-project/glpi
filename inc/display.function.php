<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------



//******************************************************************************************************
//******************************************************************************************************
//***********  Fonctions d'affichage header footer helpdesk pager ***********************
//******************************************************************************************************
//******************************************************************************************************
/**
 * Print a nice HTML head for every page
 *
 *
 * @param $title title of the page
 * @param $url not used anymore.
 *
 **/
function commonHeader($title,$url)
{
	// Print a nice HTML-head for every page

	global $cfg_glpi,$lang,$HTMLRel,$phproot,$plugin_hooks,$HEADER_LOADED ;
	if ($HEADER_LOADED) return;
	$HEADER_LOADED=true;
	// Override list-limit if choosen
	if (isset($_POST['list_limit'])) {
		$_SESSION['glpilist_limit']=$_POST['list_limit'];
		$cfg_glpi["list_limit"]=$_POST['list_limit'];
	}


	//  menu list 	
	//////// UTILS
	$utils=array();
	if (haveRight("reservation_helpdesk","1")||haveRight("reservation_central","r")) 
		$utils[$lang["Menu"][17]]=array("reservation.php","1");
	if (haveRight("knowbase","r")||haveRight("faq","r")) 
		$utils[$lang["Menu"][19]]=array("knowbase.php"," ");
	if (haveRight("reports","r"))
		$utils[$lang["Menu"][6]]=array("report.php"," ");
	if ($cfg_glpi["ocs_mode"]&&haveRight("ocsng","w")) 
		$utils[$lang["Menu"][33]]=array("ocsng.php"," ");

	//////// INVENTORY
	$inventory=array();
	$showstate=false;
	if (haveRight("computer","r")){
		$inventory[$lang["Menu"][0]]=array("computer.php","c");
		$showstate=true;
	}
	if (haveRight("monitor","r")){
		$inventory[$lang["Menu"][3]]=array("monitor.php","m");
		$showstate=true;
	}
	if (haveRight("software","r")){
		$inventory[$lang["Menu"][4]]=array("software.php","s");  
		$showstate=true;
	}
	if (haveRight("networking","r")){
		$inventory[$lang["Menu"][1]]=array("networking.php","n");
		$showstate=true;
	}
	if (haveRight("peripheral","r")){
		$inventory[$lang["Menu"][16]]=array("peripheral.php","r");
		$showstate=true;
	}
	if (haveRight("printer","r")){
		$inventory[$lang["Menu"][2]]=array("printer.php","p");
		$showstate=true;
	}
	if (haveRight("cartridge","r")){
		$inventory[$lang["Menu"][21]]=array("cartridge.php","c");
	}
	if (haveRight("consumable","r")){
		$inventory[$lang["Menu"][32]]=array("consumable.php","g");
	}
	if (haveRight("phone","r")){
		$inventory[$lang["Menu"][34]]=array("phone.php","n");
		$showstate=true;
	}
	if ($showstate){
		$inventory[$lang["Menu"][28]]=array("state.php","s");
	}

	//////// FINANCIAL
	$financial=array();
	if (haveRight("contact_enterprise","r")){
		$financial[$lang["Menu"][22]]=array("contact.php","t");
		$financial[$lang["Menu"][23]]=array("enterprise.php","e");
	}
	if (haveRight("contract_infocom","r"))
		$financial[$lang["Menu"][25]]=array("contract.php","n");
	if (haveRight("document","r"))
		$financial[$lang["Menu"][27]]=array("document.php","d");

	$maintain=array();
	//////// ASSISTANCE
	if (haveRight("observe_ticket","1")||haveRight("show_ticket","1")||haveRight("create_ticket","1"))
		$maintain[$lang["Menu"][5]]=array("tracking.php","t");
	if (haveRight("create_ticket","1"))
		$maintain[$lang["Menu"][31]]=array("helpdesk.php","h");
	if (haveRight("show_planning","1")||haveRight("show_all_planning","1"))
		$maintain[$lang["Menu"][29]]=array("planning.php","l");
	if (haveRight("statistic","1"))
		$maintain[$lang["Menu"][13]]=array("stat.php","1");

	//////// ADMINISTRATION
	if (haveRight("user","r"))
		$config[$lang["Menu"][14]]=array("user.php","u");
	if (haveRight("group","r"))
		$config[$lang["Menu"][36]]=array("group.php","g");
	if (haveRight("profile","r"))
		$config[$lang["Menu"][35]]=array("profile.php","p");
	$config[$lang["Menu"][10]]=array("setup.php","2");
	$config[$lang["Menu"][11]]=array("preference.php","p");
	if (haveRight("backup","w"))
		$config[$lang["Menu"][12]]=array("backup.php","b");
	if (haveRight("logs","r"))
		$config[$lang["Menu"][30]]=array("log.php","l");

	// Send UTF8 Headers
	header("Content-Type: text/html; charset=UTF-8");
	// Send extra expires header if configured
	if ($cfg_glpi["sendexpire"]) {
		header_nocache();
	}
	// Start the page
	echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">";
	echo "\n<html><head><title>GLPI - ".$title."</title>";
	echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8 \" >";
	// Send extra expires header if configured
	if ($cfg_glpi["sendexpire"]) {
		echo "<meta http-equiv=\"Expires\" content=\"Fri, Jun 12 1981 08:20:00 GMT\">\n";
		echo "<meta http-equiv=\"Pragma\" content=\"no-cache\">\n";
		echo "<meta http-equiv=\"Cache-Control\" content=\"no-cache\">\n";
	}
	//  CSS link
	echo "<link rel='stylesheet'  href='".$HTMLRel."css/styles.css' type='text/css' media='screen' >";
	echo "<link rel='stylesheet' type='text/css' media='print' href='".$HTMLRel."css/print.css' >";
	echo "<link rel='shortcut icon' type='images/x-icon' href='".$HTMLRel."pics/favicon.ico' >";
	// AJAX library
	echo "<script type=\"text/javascript\" src='".$HTMLRel."lib/prototype.js'></script>";
	echo "<script type=\"text/javascript\" src='".$HTMLRel."lib/scriptaculous/scriptaculous.js'></script>";
	// Some Javascript-Functions which we may need later
	echo "<script type=\"text/javascript\" src='".$HTMLRel."script.js'></script>";

	// Calendar scripts 
	echo "<style type=\"text/css\">@import url(".$HTMLRel."lib/calendar/aqua/theme.css);</style>";
	echo "<script type=\"text/javascript\" src=\"".$HTMLRel."lib/calendar/calendar.js\"></script>";
	echo "<script type=\"text/javascript\" src=\"".$HTMLRel."lib/calendar/lang/calendar-".$cfg_glpi["languages"][$_SESSION["glpilanguage"]][4].".js\"></script>";
	echo "<script type=\"text/javascript\" src=\"".$HTMLRel."lib/calendar/calendar-setup.js\"></script>";


	// End of Head
	echo "</head>\n";
	// Body 
	echo "<body>";



	// Main Headline
	echo "<div id='navigation'>";

	//menu
	echo "<div id='menu'>";
	// Logo with link to command center

	echo "<dl><dt onmouseover=\"javascript:hidemenu();\"><a class='icon_logo' style='background: transparent' href=\"".$cfg_glpi["root_doc"]."/front/central.php\" accesskey=\"0\"><img  src=\"".$HTMLRel."pics/logo-glpi.png\"  alt=\"".$cfg_glpi["logotxt"]."\" title=\"".$lang["central"][5]."\"></a></dt></dl>";

	// Get object-variables and build the navigation-elements

	// Inventory
	if (count($inventory)) {
		echo "<dl><dt onmouseover=\"javascript:montre('smenu1');\"><img class='icon_nav' src=\"".$HTMLRel."pics/inventaire.png\" alt=\"\" title=\"".$lang["setup"][10]."\"><br>\n";
		echo "<span class='menu_title'>-&nbsp;".$lang["setup"][10]."&nbsp;-</span></dt>\n";
		echo "<dd id=\"smenu1\"><ul>";
		$i=0;
		// list menu item 
		foreach ($inventory as $key => $val) {
			echo "<li><span class='menu'><a  href=\"".$cfg_glpi["root_doc"]."/front/".$val[0]."\" accesskey=\"".$val[1]."\">".$key."</a></span></li>\n";
			$i++;
		}

		echo "</ul></dd>\n";
		echo "</dl>\n";
	}

	// Maintain / Tracking / ticket
	if (count($maintain)) {

		echo "<dl><dt onmouseover=\"javascript:montre('smenu2');\"><img class='icon_nav' src=\"".$HTMLRel."pics/maintenance.png\" alt=\"\" title=\"".$lang["title"][24]."\"><br>\n";
		echo "<span class='menu_title'>-&nbsp;".$lang["title"][24]."&nbsp;-</span></dt>\n";
		echo "<dd id=\"smenu2\"><ul>";
		// list menu item 
		foreach ($maintain as $key => $val) {
			echo "<li><span class='menu'><a  href=\"".$cfg_glpi["root_doc"]."/front/".$val[0]."\" accesskey=\"".$val[1]."\">".$key."</a></span></li>\n";
		}
		echo "</ul></dd>\n";
		echo "</dl>\n";
	}
	// Financial
	if (count($financial)) {
		echo "<dl><dt onmouseover=\"javascript:montre('smenu3');\">";
		echo "<img class='icon_nav' src=\"".$HTMLRel."pics/gestion.png\" alt=\"\" title=\"".$lang["Menu"][26]."\"><br>\n";
		echo "<span class='menu_title'>-&nbsp;".$lang["Menu"][26]."&nbsp;-</span></dt>\n";
		echo "<dd id=\"smenu3\"><ul>";
		// list menu item 
		foreach ($financial as $key => $val) {
			echo "<li><span class='menu'><a  href=\"".$cfg_glpi["root_doc"]."/front/".$val[0]."\" accesskey=\"".$val[1]."\">".$key."</a></span></li>\n";
		}
		echo "</ul></dd>\n";
		echo "</dl>\n";
	}

	// Tools
	if (count($utils)) {
		echo "<dl><dt onmouseover=\"javascript:montre('smenu4');\">";
		echo "<img class='icon_nav' src=\"".$HTMLRel."pics/outils.png\" alt=\"\" title=\"".$lang["Menu"][18]."\"><br>\n";
		echo "<span class='menu_title'>-&nbsp;".$lang["Menu"][18]."&nbsp;-</span></dt>\n";
		echo "<dd id=\"smenu4\"><ul>";
		// list menu item 
		foreach ($utils as $key => $val) {
			echo "<li><span class='menu'><a  href=\"".$cfg_glpi["root_doc"]."/front/".$val[0]."\" accesskey=\"".$val[1]."\">".$key."</a></span></li>\n";
		}
		echo "</ul></dd>\n";
		echo "</dl>\n";
	}

	// PLUGINS
	$plugins=array();
	if (isset($plugin_hooks["menu_entry"])&&count($plugin_hooks["menu_entry"]))
		foreach  ($plugin_hooks["menu_entry"] as $plugin => $active) {
			if ($active){
				$function="plugin_version_$plugin";

				if (function_exists($function))
					$plugins[$plugin]=$function();
			}
		}

	if (isset($plugins)&&count($plugins)>0){
		$list=array();
		foreach ($plugins as $key => $val) {
			$list[$key]=$val["name"];
		}
		asort($list);
		echo "<dl><dt onmouseover=\"javascript:montre('smenu5');\">";
		echo "<img class='icon_nav' src=\"".$HTMLRel."pics/plugins.png\" alt=\"\" title=\"".$lang["Menu"][15]."\"><br>\n";
		echo "<span class='menu_title'>-&nbsp;".$lang["common"][29]."&nbsp;-</span></dt>\n";
		echo "<dd id=\"smenu5\"><ul>";
		// list menu item 
		foreach ($list as $key => $val) {
			echo "<li><span class='menu'><a  href=\"".$cfg_glpi["root_doc"]."/plugins/".$key."/\">".$plugins[$key]["name"]."</a></span></li>\n";
		}
		echo "</ul></dd>\n";
		echo "</dl>\n";
	}

	// Administration 
	if (count($config)) {
		echo "<dl><dt onmouseover=\"javascript:montre('smenu6');\">";
		echo "<img class='icon_nav' src=\"".$HTMLRel."pics/config.png\" alt=\"\" title=\"".$lang["Menu"][15]."\"><br>\n";
		echo "<span class='menu_title'>-&nbsp;".$lang["Menu"][15]."&nbsp;-</span></dt>\n";
		echo "<dd id=\"smenu6\"><ul>";
		// list menu item 
		foreach ($config as $key => $val) {
			echo "<li><span class='menu'><a  href=\"".$cfg_glpi["root_doc"]."/front/".$val[0]."\" accesskey=\"".$val[1]."\">".$key."</a></span></li>\n";
		}
		echo "</ul></dd>\n";
		echo "</dl>\n";
	}


	// Display  clock with date, help and a logout-link.
	//logout
	echo "<div  onmouseover=\"javascript:hidemenu();\" style='float:right; width:5%; margin-right:10px;'><a  class='icon_nav_move'  style='background: transparent'  href=\"".$cfg_glpi["root_doc"]."/logout.php\"><img  class='icon_nav'  src=\"".$HTMLRel."pics/logout.png\" alt=\"".$lang["central"][6]."\" title=\"".$lang["central"][6]."\"></a></div>\n";

	//help
	echo "<div  onmouseover=\"javascript:hidemenu();\" style='float:right; width:5%;'><a class='icon_nav_move'  style='background: transparent'   href='#' onClick=\"window.open('".$HTMLRel."help/".$cfg_glpi["languages"][$_SESSION["glpilanguage"]][2]."','helpdesk','width=750,height=600,scrollbars=yes')\"><img class='icon_nav' src=\"".$HTMLRel."pics/help.png\" alt=\"\" title=\"".$lang["central"][7]."\"></a></div>\n";




	// End navigation bar


	// End headline
	//	echo "<hr class='separ'>";
	echo "</div>\n";

	//clock
	echo "<div class='nav_horl' style='font-size:9px; position:absolute; top:60px; right: 15px; text-align:center; z-index:99;'>";
	echo "<a href='".$HTMLRel."front/user.form.my.php'>";
	if (!empty($_SESSION["glpirealname"])) {
		echo $_SESSION["glpirealname"];
		if (strlen($_SESSION["glpirealname"]." ".$_SESSION["glpifirstname"])<20) echo " ".$_SESSION["glpifirstname"];
	}
	else echo $_SESSION["glpiname"];
	echo "</a></div>\n";

	echo "</div>";

	echo "<div onmouseover=\"javascript:hidemenu();\">";

	// call function callcron() every 5min
	if (isset($_SESSION["glpicrontimer"])){
		if (abs(time()-$_SESSION["glpicrontimer"])>300){
			callCron();
			$_SESSION["glpicrontimer"]=time();
		} 
	} else $_SESSION["glpicrontimer"]=time();


	displayMessageAfterRedirect();
}

function displayMessageAfterRedirect(){
	// Affichage du message apres redirection
	if (isset($_SESSION["MESSAGE_AFTER_REDIRECT"])&&!empty($_SESSION["MESSAGE_AFTER_REDIRECT"])){
		echo "<div align='center'><b>".$_SESSION["MESSAGE_AFTER_REDIRECT"]."</b></div>";
		$_SESSION["MESSAGE_AFTER_REDIRECT"]="";
		unset($_SESSION["MESSAGE_AFTER_REDIRECT"]);
	} else $_SESSION["MESSAGE_AFTER_REDIRECT"]="";
}

/**
 * Print a nice HTML head for help page
 *
 *
 * @param $title title of the page
 * @param $url not used anymore.
 **/
function helpHeader($title,$url) {
	// Print a nice HTML-head for help page

	global $cfg_glpi,$lang,$HTMLRel,$phproot, $cfg_glpi,$HEADER_LOADED ;

	if ($HEADER_LOADED) return;
	$HEADER_LOADED=true;

	// Override list-limit if choosen
	if (isset($_POST['list_limit'])) {
		$_SESSION['glpilist_limit']=$_POST['list_limit'];
		$cfg_glpi["list_limit"]=$_POST['list_limit'];
	}

	// Send extra expires header if configured
	if ($cfg_glpi["sendexpire"]) {
		header_nocache(); 
	}

	// Send UTF8 Headers
	header("Content-Type: text/html; charset=UTF-8");

	// Start the page
	echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">";
	echo "<html><head><title>GLPI Helpdesk - ".$title."</title>";
	echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8 \" >";
	echo "<link rel='shortcut icon' type='images/x-icon' href='".$HTMLRel."pics/favicon.ico' >";
	// Send extra expires header if configured

	if ($cfg_glpi["sendexpire"]) {
		echo "<meta http-equiv=\"Expires\" content=\"Fri, Jun 12 1981 08:20:00 GMT\">\n";
		echo "<meta http-equiv=\"Pragma\" content=\"no-cache\">\n";
		echo "<meta http-equiv=\"Cache-Control\" content=\"no-cache\">\n";
	}

	// Some Javascript-Functions which we may need later

	echo "<script type=\"text/javascript\" src='".$HTMLRel."script.js'></script>";

	// AJAX library
	echo "<script type=\"text/javascript\" src='".$HTMLRel."lib/prototype.js'></script>";
	echo "<script type=\"text/javascript\" src='".$HTMLRel."lib/scriptaculous/scriptaculous.js'></script>";


	// Appel CSS

	echo "<link rel='stylesheet'  href='".$HTMLRel."css/styles.css' type='text/css' media='screen' >";
	echo "<link rel='stylesheet' type='text/css' media='print' href='".$HTMLRel."css/print.css' >";

	// Calendar scripts 
	echo "<style type=\"text/css\">@import url(".$HTMLRel."lib/calendar/aqua/theme.css);</style>";
	echo "<script type=\"text/javascript\" src=\"".$HTMLRel."lib/calendar/calendar.js\"></script>";
	echo "<script type=\"text/javascript\" src=\"".$HTMLRel."lib/calendar/lang/calendar-".$cfg_glpi["languages"][$_SESSION["glpilanguage"]][4].".js\"></script>";
	echo "<script type=\"text/javascript\" src=\"".$HTMLRel."lib/calendar/calendar-setup.js\"></script>";

	// End of Head
	echo "</head>\n";

	// Body 
	echo "<body>";

	// Main Headline
	echo "<div id='navigation-helpdesk' style='background : url(\"".$HTMLRel."pics/fond.png\") repeat-x top right ;'>";

	echo "<table cellspacing='0' border='0' width='98%'>";
	echo "<tr>";

	// Logo with link to command center
	echo "<td align='center' width='20%'>\n";
	echo "<a class='icon_logo' style='background: transparent' href=\"".$cfg_glpi["root_doc"]."/front/helpdesk.public.php\" accesskey=\"0\">";
	echo "<img src=\"".$HTMLRel."pics/logo-glpi.png\"  alt=\"".$cfg_glpi["logotxt"]."\" title=\"".$lang["central"][5]."\" >";
	echo "</a>";
	echo "<div style='text-align:center;'><p class='nav_horl'><b>";
	echo "<a href='".$HTMLRel."front/user.form.my.php'>";
	if (!empty($_SESSION["glpirealname"])) {
		echo $_SESSION["glpirealname"];
		if (strlen($_SESSION["glpirealname"]." ".$_SESSION["glpifirstname"])<30) echo " ".$_SESSION["glpifirstname"];
	}
	else echo $_SESSION["glpiname"];
	echo "</a></b></p></div>";
	echo "</td>";

	echo "<td valign='middle'>";

	echo "<table width='100%' cellspacing='0' cellpadding='0' border='0'><tr>";

	//if (ereg("tracking-injector",$_SERVER["PHP_SELF"]))
	//		echo "<td width='100%'>&nbsp;</td>";
	// Just give him a language selector
	echo "<td width='40%' align='center'>";
	if ($cfg_glpi["debug"]!=DEMO_MODE&&!ereg("tracking-injector",$_SERVER["PHP_SELF"]))
		showLangSelect($cfg_glpi["root_doc"]."/front/preference.php");
	else echo "&nbsp;";
	echo "</td>";

	// And he can change his password, thats it
	echo "<td width='40%' align='center'>";
	if (haveRight("password_update","1")&&$cfg_glpi["debug"]!=DEMO_MODE&&$_SESSION["glpiextauth"]!=1&&!ereg("tracking-injector",$_SERVER["PHP_SELF"]))
		showPasswordForm($cfg_glpi["root_doc"]."/front/preference.php",$_SESSION["glpiname"]);
	else echo "&nbsp;";
	echo "</td>";
	// We tracking or post a new one
	echo "<td width='100' align='center'>";
	if (haveRight("create_ticket","1"))
		echo "<a class='icon_nav_move' href=\"".$cfg_glpi["root_doc"]."/front/helpdesk.public.php\"><img  src=\"".$HTMLRel."pics/ajoutinterv.png\" alt=\"".$lang["job"][13]."\" title=\"".$lang["job"][13]."\"></a>";
	echo "<br><br>";
	if (haveRight("observe_ticket","1"))
		echo "<a class='icon_nav_move' href=\"".$cfg_glpi["root_doc"]."/front/helpdesk.public.php?show=user\"><img  src=\"".$HTMLRel."pics/suivi.png\" alt=\"".$lang["tracking"][0]."\" title=\"".$lang["tracking"][0]."\"></a>";
	echo "</td>";
	//reservation

	echo "<td width='100' align='center'>";
	if (haveRight("reservation_helpdesk","1"))
		echo "<a  class='icon_nav_move' href=\"".$cfg_glpi["root_doc"]."/front/helpdesk.public.php?show=resa\"><img  src=\"".$HTMLRel."pics/reservation-2.png\" alt=\"".$lang["Menu"][17]."\" title=\"".$lang["Menu"][17]."\"></a>";
	echo "<br><br>";
	if (haveRight("faq","r"))
		echo "<a class='icon_nav_move' href=\"".$cfg_glpi["root_doc"]."/front/helpdesk.public.php?show=faq\"><img  src=\"".$HTMLRel."pics/faq-24.png\" alt=\"".$lang["knowbase"][1]."\" title=\"".$lang["knowbase"][1]."\"></a>";

	echo "</td>";
	// On the right side of the navigation bar, we have a clock with
	// date, help and a logout-link.
	echo "<td align='right' width='100'><div align='right'>";
	// HELP	
	echo "<a class='icon_nav_move'  href='#'
		onClick=\"window.open('".$HTMLRel."help/".$cfg_glpi["languages"][$_SESSION["glpilanguage"]][3]."','helpdesk','width=400,height=600,scrollbars=yes')\"><img class='icon_nav' src=\"".$HTMLRel."pics/help.png\" alt=\"\" title=\"".$lang["central"][7]."\"></a>";

	echo "<p>".date("H").":".date("i")."<br><i>".date("j.")."&nbsp;".date("M")."&nbsp;".date("Y");
	echo "</i></p><a class='icon_nav_move' href=\"".$cfg_glpi["root_doc"]."/logout.php\"><img class='icon_nav' src=\"".$HTMLRel."pics/logout.png\" alt=\"".$lang["central"][6]."\" title=\"".$lang["central"][6]."\"></a></div></td>";

	// End navigation bar

	echo "</tr></table>";

	// End headline

	echo "</td></tr>";	
	echo "</table>\n";
	echo "</div>";

	// call function callcron() every 5min
	if (isset($_SESSION["glpicrontimer"])){
		if (($_SESSION["glpicrontimer"]-time())>300){
			callCron();
			$_SESSION["glpicrontimer"]=time();
		}
	} else $_SESSION["glpicrontimer"]=time();

	displayMessageAfterRedirect();
}

/**
 * Print a nice HTML head with no controls
 *
 *
 * @param $title title of the page
 * @param $url not used anymore.
 **/
function nullHeader($title,$url) {
	global $cfg_glpi,$HEADER_LOADED;
	if ($HEADER_LOADED) return;
	$HEADER_LOADED=true;
	// Print a nice HTML-head with no controls

	global $cfg_glpi,$lang,$HTMLRel,$phproot ;
	// Send UTF8 Headers
	header("Content-Type: text/html; charset=UTF-8");

	// Override list-limit if choosen
	if (isset($_POST['list_limit'])) {
		$_SESSION['glpilist_limit']=$_POST['list_limit'];
		$cfg_glpi["list_limit"]=$_POST['list_limit'];
	}

	// Send extra expires header if configured
	if (!empty($cfg_glpi["sendexpire"])) {
		header_nocache();
	}

	// Start the page
	echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">";
	echo "<html><head><title>GLPI - ".$title."</title>";
	echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8 \" >";
	echo "<link rel='shortcut icon' type='images/x-icon' href='".$HTMLRel."pics/favicon.ico' >";
	// Send extra expires header if configured
	if (!empty($cft_features["sendexpire"])) {
		echo "<meta http-equiv=\"Expires\" content=\"Fri, Jun 12 1981 08:20:00 GMT\">\n";
		echo "<meta http-equiv=\"Pragma\" content=\"no-cache\">\n";
		echo "<meta http-equiv=\"Cache-Control\" content=\"no-cache\">\n";
	}

	// Some Javascript-Functions which we may need later

	echo "<script type=\"text/javascript\" src='".$HTMLRel."script.js'></script>";

	// AJAX library
	echo "<script type=\"text/javascript\" src='".$HTMLRel."lib/prototype.js'></script>";
	echo "<script type=\"text/javascript\" src='".$HTMLRel."lib/scriptaculous/scriptaculous.js'></script>";

	// Appel CSS

	echo "<link rel='stylesheet'  href='".$HTMLRel."css/styles.css' type='text/css' media='screen' >";

	// Calendar scripts 
	if (isset($_SESSION["glpilanguage"])){
		echo "<style type=\"text/css\">@import url(".$HTMLRel."lib/calendar/aqua/theme.css);</style>";
		echo "<script type=\"text/javascript\" src=\"".$HTMLRel."lib/calendar/calendar.js\"></script>";
		echo "<script type=\"text/javascript\" src=\"".$HTMLRel."lib/calendar/lang/calendar-".$cfg_glpi["languages"][$_SESSION["glpilanguage"]][4].".js\"></script>";
		echo "<script type=\"text/javascript\" src=\"".$HTMLRel."lib/calendar/calendar-setup.js\"></script>";
	}


	// End of Head
	echo "</head>\n";

	// Body with configured stuff
	echo "<body>";

	// Main Headline
	echo "<div id='navigation' style='background : url(\"".$HTMLRel."pics/fond.png\") repeat-x top right ;'>";

	echo "<table cellspacing='0' border='0' width='98%'>";
	echo "<tr>";

	// Logo with link to index
	echo "<td align='center' width='100%'>\n";
	echo "<a href=\"".$HTMLRel."index.php\"><img src=\"".$HTMLRel."pics/logo-glpi.png\" alt=\"".$cfg_glpi["logotxt"]."\" title=\"\" ></a>\n";
	echo "</td>";


	// End navigation bar

	echo "</tr></table>";

	echo "</div>";
}


function popHeader($title,$url)
{
	// Print a nice HTML-head for every page

	global $cfg_glpi,$lang,$HTMLRel,$phproot,$plugin_hooks,$HEADER_LOADED ;

	if ($HEADER_LOADED) return;
	$HEADER_LOADED=true;


	// Override list-limit if choosen
	if (isset($_POST['list_limit'])) {
		$_SESSION['glpilist_limit']=$_POST['list_limit'];
		$cfg_glpi["list_limit"]=$_POST['list_limit'];
	}



	// Send UTF8 Headers
	header("Content-Type: text/html; charset=UTF-8");
	// Send extra expires header if configured
	if ($cfg_glpi["sendexpire"]) {
		header_nocache();
	}
	// Start the page
	echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">";
	echo "\n<html><head><title>GLPI - ".$title."</title>";
	echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8 \" >";
	// Send extra expires header if configured
	if ($cfg_glpi["sendexpire"]) {
		echo "<meta http-equiv=\"Expires\" content=\"Fri, Jun 12 1981 08:20:00 GMT\">\n";
		echo "<meta http-equiv=\"Pragma\" content=\"no-cache\">\n";
		echo "<meta http-equiv=\"Cache-Control\" content=\"no-cache\">\n";
	}
	//  CSS link
	echo "<link rel='stylesheet'  href='".$HTMLRel."css/styles.css' type='text/css' media='screen' >";
	echo "<link rel='stylesheet' type='text/css' media='print' href='".$HTMLRel."css/print.css' >";
	echo "<link rel='shortcut icon' type='images/x-icon' href='".$HTMLRel."pics/favicon.ico' >";
	// AJAX library
	echo "<script type=\"text/javascript\" src='".$HTMLRel."lib/prototype.js'></script>";
	echo "<script type=\"text/javascript\" src='".$HTMLRel."lib/scriptaculous/scriptaculous.js'></script>";
	// Some Javascript-Functions which we may need later
	echo "<script type=\"text/javascript\" src='".$HTMLRel."script.js'></script>";

	// Calendar scripts 
	echo "<style type=\"text/css\">@import url(".$HTMLRel."lib/calendar/aqua/theme.css);</style>";
	echo "<script type=\"text/javascript\" src=\"".$HTMLRel."lib/calendar/calendar.js\"></script>";
	echo "<script type=\"text/javascript\" src=\"".$HTMLRel."lib/calendar/lang/calendar-".$cfg_glpi["languages"][$_SESSION["glpilanguage"]][4].".js\"></script>";
	echo "<script type=\"text/javascript\" src=\"".$HTMLRel."lib/calendar/calendar-setup.js\"></script>";


	// End of Head
	echo "</head>\n";
	// Body 
	echo "<body>";

	displayMessageAfterRedirect();
}


function popFooter() {
	global $FOOTER_LOADED;

	if ($FOOTER_LOADED) return;
	$FOOTER_LOADED=true;

	// Print foot 

	echo "</body></html>";
}











/**
 * Print footer for every page
 *
 *
 **/
function commonFooter() {
	// Print foot for every page

	global $lang,$cfg_glpi,$DEBUG_SQL_STRING,$TIMER_DEBUG,$SQL_TOTAL_TIMER,$SQL_TOTAL_REQUEST,$FOOTER_LOADED;

	if ($FOOTER_LOADED) return;
	$FOOTER_LOADED=true;

	echo "</div>";
	echo "<div id='footer' >";
	echo "<table width='100%'><tr><td align='left'><span class='copyright'>";
	echo $TIMER_DEBUG->Get_Time()."s</span>";
	echo "</td>";

	if (!empty($cfg_glpi["founded_new_version"]))
		echo "<td align='center' class='copyright'>".$lang["setup"][301]." ".$cfg_glpi["founded_new_version"]."<br>".$lang["setup"][302]."</td>";
	echo "<td class='copyright'>";
	echo date("H").":".date("i")."&nbsp;<i>".date("j.")."&nbsp;".date("M")."&nbsp;".date("Y")."</i>";
	echo "</td>";

	echo "<td align='right'>";
	echo "<a href=\"http://glpi-project.org/\">";
	echo "<span class='copyright'>GLPI ".$cfg_glpi["version"]." Copyright (C) 2003-".date("Y")." by the INDEPNET Development Team.</span>";
	echo "</a>";

	echo "</td></tr>";
	echo "</table></div>";



	if ($cfg_glpi["debug"]==1){ // debug mode traduction

		echo "<div id='debug-float'>";		
		echo "<a href='#debug'>GLPI MODE TRANSLATION</a>";
		echo "</div>";
	}

	if ($cfg_glpi["debug"]==2){ // mode debug 

		echo "<div id='debug-float'>";		
		echo "<a href='#debug'>GLPI MODE DEBUG</a>";
		echo "</div>";



		echo "<div id='debug'>";
		echo "<h1><a name='#debug'>GLPI MODE DEBUG</a></h1>";
		if ($cfg_glpi["debug_profile"]){
			echo "<h2>TIME</h2>";
			echo $TIMER_DEBUG->Get_Time()."s";
			if (function_exists("memory_get_usage")){
				echo "<h2>MEMORY</h2>";
				echo memory_get_usage();
			}
		}
		if ($cfg_glpi["debug_vars"]){
			echo "<h2>POST VARIABLE</h2>";
			printCleanArray($_POST);
			echo "<h2>GET VARIABLE</h2>";
			printCleanArray($_GET);
			echo "<h2>SESSION VARIABLE</h2>";
			printCleanArray($_SESSION);
		}

		if ($cfg_glpi["debug_sql"]){	
			echo "<h2>SQL REQUEST</h2>";
			echo "<p><strong> Number of request:</strong> ".$SQL_TOTAL_REQUEST."</p>";
			if ($cfg_glpi["debug_profile"]){
				echo "<p><strong>Total Time:</strong> ".$SQL_TOTAL_TIMER."s</p><hr>";
			}

			echo eregi_replace("ORDER BY","<br>ORDER BY",eregi_replace("SORT","<br>SORT",eregi_replace("LEFT JOIN","<br>LEFT JOIN",eregi_replace("WHERE","<br>WHERE",eregi_replace("FROM","<br>FROM",$DEBUG_SQL_STRING)))));
		}
		echo "</div>";
	}
	echo "</body></html>";
}

/**
 * Print footer for help page
 *
 *
 **/
function helpFooter() {
	// Print foot for help page
	global $cfg_glpi,$FOOTER_LOADED;

	if ($FOOTER_LOADED) return;
	$FOOTER_LOADED=true;

	echo "<div id='footer'><div align='right'>";
	echo "<a href=\"http://glpi-project.org/\">";
	echo "<span class='copyright'>GLPI ".$cfg_glpi["version"]." Copyright (C) 2003-".date("Y")." by the INDEPNET Development Team.</span>";
	echo "</a></div>";
	echo "</div>";

	echo "</body></html>";
}

/**
 * Print footer for null page
 *
 *
 **/
function nullFooter() {
	// Print foot for null page
	global $cfg_glpi,$FOOTER_LOADED;

	if ($FOOTER_LOADED) return;
	$FOOTER_LOADED=true;

	echo "<div id='footer'><div align='right'>";
	echo "<a href=\"http://glpi-project.org/\">";
	echo "<span class='copyright'>GLPI ".(isset($cfg_glpi["version"])?$cfg_glpi["version"]:"")." Copyright (C) 2003-".date("Y")." by the INDEPNET Development Team.</span>";
	echo "</a>";
	echo "</div></div>";

	echo "</body></html>";
}


/**
 * Print the helpdesk 
 *
 * @param $ID int : ID of the user who want to display the Helpdesk
 * @param $from_helpdesk int : is display from the helpdesk.php ?
 * @return nothing (print the helpdesk)
 */
function printHelpDesk ($ID,$from_helpdesk) {

	global $db,$cfg_glpi,$lang;

	if (!haveRight("create_ticket","1")) return false;

	$query = "SELECT email,realname,firstname,name FROM glpi_users WHERE (ID = '$ID')";
	$result=$db->query($query);
	$email = $db->result($result,0,"email");
	$realname = $db->result($result,0,"realname");
	$firstname = $db->result($result,0,"firstname");
	$name = $db->result($result,0,"name");

	// Get saved data from a back system
	$emailupdates = 'yes';
	$device_type = 0;
	$computer="";
	$contents="";
	$category = 0;


	if (isset($_SESSION["helpdeskSaved"]["emailupdates"]))
		$emailupdates = stripslashes($_SESSION["helpdeskSaved"]["emailupdates"]);
	if (isset($_SESSION["helpdeskSaved"]["email"]))
		$email = stripslashes($_SESSION["helpdeskSaved"]["uemail"]);
	if (isset($_SESSION["helpdeskSaved"]["device_type"]))
		$device_type = stripslashes($_SESSION["helpdeskSaved"]["device_type"]);
	if (isset($_SESSION["helpdeskSaved"]["category"]))
		$device_type = stripslashes($_SESSION["helpdeskSaved"]["category"]);
	if (isset($_SESSION["helpdeskSaved"]["contents"]))
		$contents = stripslashes($_SESSION["helpdeskSaved"]["contents"]);
	if (isset($_SESSION["helpdeskSaved"]["category"]))
		$category = stripslashes($_SESSION["helpdeskSaved"]["category"]);

	echo "<form method='post' name=\"helpdeskform\" action=\"".$cfg_glpi["root_doc"]."/front/tracking.injector.php\"  enctype=\"multipart/form-data\">";
	echo "<input type='hidden' name='_from_helpdesk' value='$from_helpdesk'>";
	echo "<input type='hidden' name='request_type' value='1'>";

	echo "<div align='center'><table  class='tab_cadre'>";

	if ($realname!='') $name=$realname." ".$firstname;

	echo "<tr><th colspan='2'>".$lang["help"][0]." $name, ".$lang["help"][1].":</th></tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<td>".$lang["help"][2].": </td>";
	echo "<td>";
	dropdownPriority("priority",3);
	echo "</td></tr>";
	if($cfg_glpi["mailing"] != 0)
	{
		echo "<tr class='tab_bg_1'>";
		echo "<td>".$lang["help"][8].":</td>";
		echo "<td>	<select name='emailupdates'>";
		echo "<option value='no' ".(($emailupdates=="no")?" selected":"").">".$lang["choice"][0]."";
		echo "<option value='yes' ".(($emailupdates=="yes")?" selected":"").">".$lang["choice"][1]."";
		echo "</select>";
		echo "</td></tr>";
		echo "<tr class='tab_bg_1'>";
		echo "<td>".$lang["help"][11].":</td>";
		echo "<td>	<input name='uemail' value=\"$email\" size='20'>";
		echo "</td></tr>";
	}

	if ($_SESSION["glpiprofile"]["helpdesk_hardware"]!=0){
		echo "<tr class='tab_bg_1'>";
		echo "<td>".$lang["help"][24].": </td>";
		echo "<td>";
		dropdownTrackingDeviceType("device_type",$device_type);
		echo "</td></tr>";
	}

	echo "<tr class='tab_bg_1'>";
	echo "<td>".$lang["common"][36].":</td><td>";

	dropdownValue("glpi_dropdown_tracking_category","category",$category);
	echo "</td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'>";
	echo "<td colspan='2' align='center'>".$lang["help"][13].":</td>";
	echo "</tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<td colspan='2' align='center'><textarea name='contents' cols='80' rows='14' >$contents</textarea>";
	echo "</td></tr>";

	$max_size=return_bytes_from_ini_vars(ini_get("upload_max_filesize"));
	$max_size/=1024*1024;
	$max_size=round($max_size,1);

	echo "<tr class='tab_bg_1'><td>".$lang["document"][2]." (".$max_size." Mb max):	";
	echo "<img src=\"".$cfg_glpi["root_doc"]."/pics/aide.png\" style='cursor:pointer;' alt=\"aide\"onClick=\"window.open('".$cfg_glpi["root_doc"]."/front/typedoc.list.php','Help','scrollbars=1,resizable=1,width=1000,height=800')\">";
	echo "</td>";
	echo "<td><input type='file' name='filename' value=\"\" size='25'></td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'>";
	echo "<td colspan='2' align='center'> <input type='submit' value=\"".$lang["help"][14]."\" class='submit'>";
	echo "</td></tr>";

	echo "</table>";
	echo "</div>";
	echo "</form>";

}


/**
 * Print pager for search option (first/previous/next/last)
 *
 *
 *
 * @param $start from witch item we start
 * @param $numrows total items
 * @param $target page would be open when click on the option (last,previous etc)
 * @param $parameters parameters would be passed on the URL.
 * @param $item_type_output item type display - if >0 display export PDF et Sylk form
 * @param $item_type_output item type display - if >0 display export PDF et Sylk form
 * @param $item_type_output_param item type parameter for export
 * @return nothing (print a pager)
 *
 */
function printPager($start,$numrows,$target,$parameters,$item_type_output=0,$item_type_output_param=0) {

	global $cfg_glpi, $lang, $HTMLRel,$cfg_glpi;

	// Forward is the next step forward
	$forward = $start+$cfg_glpi["list_limit"];

	// This is the end, my friend	
	$end = $numrows-$cfg_glpi["list_limit"];

	// Human readable count starts here
	$current_start=$start+1;

	// And the human is viewing from start to end
	$current_end = $current_start+$cfg_glpi["list_limit"]-1;
	if ($current_end>$numrows) {
		$current_end = $numrows;
	}

	// Backward browsing 
	if ($current_start-$cfg_glpi["list_limit"]<=0) {
		$back=0;
	} else {
		$back=$start-$cfg_glpi["list_limit"];
	}

	// Print it

	echo "<div align='center' style='font-size:6px;'><table class='tab_cadre_pager'>\n";
	echo "<tr>\n";

	// Back and fast backward button
	if (!$start==0) {
		echo "<th align='left'>";
		echo "<a href=\"$target?$parameters&amp;start=0\">";
		echo "<img src=\"".$HTMLRel."pics/first.png\" alt='".$lang["buttons"][33]."' title='".$lang["buttons"][33]."'>";


		echo "</a></th>\n";
		echo "<th align='left'>";
		echo "<a href=\"$target?$parameters&amp;start=$back\">";
		echo "<img src=\"".$HTMLRel."pics/left.png\" alt='".$lang["buttons"][12]."' title='".$lang["buttons"][12]."'>";
		echo "</a></th>\n";
	}

	// Print the "where am I?" 
	echo "<td width='50%' align='center' class='tab_bg_2'>";
	echo "<form method='POST' action=\"$target?$parameters&amp;start=$start\">\n";
	echo "<span>".$lang["pager"][4]."&nbsp;</span>";
	echo "<select name='list_limit' onChange='submit()'>";
	for ($i=5;$i<=200;$i+=5) echo "<option value='$i' ".((isset($_SESSION["glpilist_limit"])&&$_SESSION["glpilist_limit"]==$i)?" selected ":"").">$i</option>\n";
	echo "<option value='9999999' ".((isset($_SESSION["glpilist_limit"])&&$_SESSION["glpilist_limit"]==9999999)?" selected ":"").">9999999</option>\n";	
	echo "</select><span>&nbsp;";
	echo $lang["pager"][5];
	echo "</span>";
	echo "</form>\n";
	echo "</td>\n";

	if ($item_type_output>0&&$_SESSION["glpiprofile"]["interface"]=="central"){
		echo "<td class='tab_bg_2' width='30%'>" ;
		echo "<form method='GET' action=\"".$cfg_glpi["root_doc"]."/front/report.dynamic.php\" target='_blank'>\n";
		echo "<input type='hidden' name='item_type' value='$item_type_output'>";
		if ($item_type_output_param!=0)
			echo "<input type='hidden' name='item_type_param' value='".serialize($item_type_output_param)."'>";
		$split=split("&amp;",$parameters);
		for ($i=0;$i<count($split);$i++){
			$pos=strpos($split[$i],'=');
			echo "<input type='hidden' name=\"".substr($split[$i],0,$pos)."\" value=\"".substr($split[$i],$pos+1)."\">";
		}
		echo "<select name='display_type'>";
		echo "<option value='2'>".$lang["buttons"][27]."</option>";
		echo "<option value='1'>".$lang["buttons"][28]."</option>";
		echo "<option value='-2'>".$lang["buttons"][29]."</option>";
		echo "<option value='-1'>".$lang["buttons"][30]."</option>";
		echo "</select>";
		echo "&nbsp;<input type='image' name='export'  src='".$HTMLRel."pics/export.png' title='".$lang["buttons"][31]."' value='".$lang["buttons"][31]."'>";
		echo "</form>";
		echo "</td>" ;
	}

	echo "<td  width='50%' align='center' class='tab_bg_2'><b>";

	echo $lang["pager"][2]."&nbsp;".$current_start."&nbsp;".$lang["pager"][1]."&nbsp;".$current_end."&nbsp;".$lang["pager"][3]."&nbsp;".$numrows."&nbsp;";
	echo "</b></td>\n";

	// Forward and fast forward button
	if ($forward<$numrows) {
		echo "<th align='right'>";
		echo "<a href=\"$target?$parameters&amp;start=$forward\">";
		echo "<img src=\"".$HTMLRel."pics/right.png\" alt='".$lang["buttons"][11]."' title='".$lang["buttons"][11]."'>";
		echo "</a></th>\n";
		echo "<th align='right'>";
		echo "<a href=\"$target?$parameters&amp;start=$end\">";
		echo "<img src=\"".$HTMLRel."pics/last.png\" alt='".$lang["buttons"][32]."' title='".$lang["buttons"][32]."'>";
		echo "</a></th>\n";
	}

	// End pager
	echo "</tr>\n";
	echo "</table><br></div>\n";

}


/**
 * Display calendar form
 *
 * @param $form form in which the calendar is display
 * @param $element name of the element
 * @param $value default value to display
 * @param $withtemplate if = 2 only display (add from template) : could not modify element
 * @param $with_time use datetime format instead of date format ?
 * @return nothing
 */
function showCalendarForm($form,$element,$value='',$withtemplate='',$with_time=0){
	global $HTMLRel,$lang,$cfg_glpi;
	$rand=mt_rand();
	if (empty($value)) {
		if ($with_time) $value=date("Y-m-d H:i");
		else 	$value=date("Y-m-d");
	}

	$size=10;
	if ($with_time) $size=17;
	echo "<input id='show$rand' type='text' name='____".$element."_show' readonly size='$size' value=\"".convDate($value)."\">";
	echo "<input id='data$rand' type='hidden' name='$element' size='$size' value=\"".$value."\">";

	if ($withtemplate!=2){
		echo "&nbsp;<img id='button$rand' src='".$HTMLRel."pics/calendar.png' class='calendrier' alt='".$lang["buttons"][15]."' title='".$lang["buttons"][15]."'>";

		echo "&nbsp;<img src='".$HTMLRel."pics/reset.png' class='calendrier' onClick=\"document.getElementById('data$rand').value='0000-00-00';document.getElementById('show$rand').value='".convDate("0000-00-00")."'\" alt='Reset' title='Reset'>";	

		echo "<script type='text/javascript'>";
		echo "Calendar.setup(";
		echo "{";
		echo "inputField : 'data$rand',"; // ID of the input field
		if ($with_time){
			echo "ifFormat : '%Y-%m-%d %H:%M',"; // the date format
			echo "showsTime : true,"; 
		}
		else echo "ifFormat : '%Y-%m-%d',"; // the datetime format
		echo "button : 'button$rand'"; // ID of the button
		echo "});";
		echo "</script>";

		echo "<script type='text/javascript' >\n";
		echo "   new Form.Element.Observer('data$rand', 1, \n";
		echo "      function(element, value) {\n";
		if (!$cfg_glpi["dateformat"]){
			echo "document.getElementById('show$rand').value=value;";
		} else {
			echo "var d=Date.parseDate(value,'%Y-%m-%d');";
			echo "document.getElementById('show$rand').value=d.print('%d-%m-%Y');";
		}
		echo "})\n";
		echo "</script>\n";


	}
}

/**
 *  show notes for item
 *
 * @param $target target page to update item
 * @param $type item type of the device to display notes
 * @param $id id of the device to display notes
 * @return nothing
 */
function showNotesForm($target,$type,$id){
	global $HTMLRel,$lang;

	if (!haveRight("notes","r")) return false;
	//new objet
	$ci =new CommonItem;
	//getfromdb
	$ci->getfromDB ($type,$id);


	echo "<form name='form' method='post' action=\"".$target."\">";
	echo "<div align='center'>";
	echo "<table class='tab_cadre_fixe' >";
	echo "<tr><th align='center' >";
	echo $lang["title"][37];
	echo "</th></tr>";
	echo "<tr><td valign='middle' align='center' class='tab_bg_1' ><textarea class='textarea_notes' cols='100' rows='35' name='notes' >".$ci->obj->fields["notes"]."</textarea></td></tr>";
	echo "<tr><td class='tab_bg_2' align='center' >\n";
	echo "<input type='hidden' name='ID' value=$id>";
	if (haveRight("notes","w"))
		echo "<input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'>";
	echo "</td></tr>\n";
	echo "</table></div></form>";
}

function header_nocache(){
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date du passe
}

function glpi_flush(){
	flush();
	if (function_exists("ob_flush") && ob_get_length () !== FALSE) ob_flush();
}

function displayProgressBar($width,$percent){
	global $lang;
	$percentwidth=floor($percent*$width/100);
	echo str_pad("<div align='center'><table class='tab_cadre' width='$width'><tr><td width='$width' align='center'> ".$lang["common"][47]."&nbsp;".$percent."%</td></tr><tr><td><table><tr><td bgcolor='red'  width='$percentwidth' height='20'>&nbsp;</td></tr></table></td></tr></table></div>\n",4096);
	glpi_flush();
}

function printCleanArray($tab,$pad=0){
	foreach($tab as $key => $val){
		for ($i=0;$i<$pad*20;$i++)
			echo "&nbsp;";
		echo $key." => ";
		if (is_array($val)){
			echo "Array<br>";
			printCleanArray($val,$pad+1);
		}
		else echo $val."<br>";
	}
}

/**
 *  show onglet for central
 *
 * @param $target 
 * @param $actif
 * @return nothing
 */
function showCentralOnglets($target,$actif) {
	global $lang, $HTMLRel,$plugin_hooks;
	echo "<div id='barre_onglets'><ul id='onglet'>";
	echo "<li ".($actif=="my"?"class='actif'":"")."><a href='$target?onglet=my'>".$lang["central"][12]."</a></li>";
	if (haveRight("show_ticket","1")||haveRight("logs","r")||haveRight("contract_infocom","r"))
		echo "<li ".($actif=="global"?"class='actif'":"")."><a href='$target?onglet=global'>".$lang["central"][13]."</a></li>";
	if (isset($plugin_hooks['central_action'])&&count($plugin_hooks['central_action'])){
		echo "<li ".($actif=="plugins"?"class='actif'":"")."><a href='$target?onglet=plugins'>".$lang["common"][29]."</a></li>";
	}
	echo "</ul></div>";
}





?>
