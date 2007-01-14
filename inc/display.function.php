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

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}


//******************************************************************************************************
//******************************************************************************************************
//***********  Fonctions d'affichage header footer helpdesk pager ***********************
//******************************************************************************************************
//******************************************************************************************************

/**
 * Common Title Function
 *
 * @param $ref_pic_link Path to the image to display
 * @param $ref_pic_text Alt text of the icon
 * @param $ref_title Title to display
 * @param $ref_btts Extra items to display array(link=>text...)
 * @return nothing
 **/
function displayTitle($ref_pic_link="",$ref_pic_text="",$ref_title="",$ref_btts="") {
        echo "<div align='center'><table border='0'><tr>";
        if ($ref_pic_link!="")
                echo "<td><img src=\"".$ref_pic_link."\" alt=\"".$ref_pic_text."\"
title=\"".$ref_pic_text."\" ></td>"; 
        if ($ref_title!="")
                echo "<td><span class='icon_sous_nav'><b>".$ref_title."</b></span></td>"; 
	if (is_array($ref_btts)&&count($ref_btts))
        foreach ($ref_btts as $key => $val) { 
                echo "<td><a class='icon_consol' href=\"".$key."\">".$val."</a></td>"; 
        }        
        echo "</tr></table></div>";
}

/**
 * Print a nice HTML head for every page
 *
 *
 * @param $title title of the page
 * @param $url not used anymore.
 *
 **/
function commonHeader($title,$url,$sector="none")
{
	// Print a nice HTML-head for every page

	global $CFG_GLPI,$LANG,$PLUGIN_HOOKS,$HEADER_LOADED ;
	if ($HEADER_LOADED) return;
	$HEADER_LOADED=true;
	// Override list-limit if choosen
	if (isset($_POST['list_limit'])) {
		$_SESSION['glpilist_limit']=$_POST['list_limit'];
		$CFG_GLPI["list_limit"]=$_POST['list_limit'];
	}


	// Send UTF8 Headers
	header("Content-Type: text/html; charset=UTF-8");
	// Send extra expires header if configured
	if ($CFG_GLPI["sendexpire"]) {
		header_nocache();
	}

	
		// Start the page
		echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">";
		echo "\n<html><head><title>GLPI - ".$title."</title>";
		echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8 \" >";
		// Send extra expires header if configured
		if ($CFG_GLPI["sendexpire"]) {
			echo "<meta http-equiv=\"Expires\" content=\"Fri, Jun 12 1981 08:20:00 GMT\">\n";
			echo "<meta http-equiv=\"Pragma\" content=\"no-cache\">\n";
			echo "<meta http-equiv=\"Cache-Control\" content=\"no-cache\">\n";
		}
		//  CSS link
		echo "<link rel='stylesheet'  href='".$CFG_GLPI["root_doc"]."/css/styles.css' type='text/css' media='screen' >";
		//Surcharge de la feuille de style principale
		//echo "<link rel=\"stylesheet\" href=\"".$CFG_GLPI["root_doc"]."/css/header_style.css\" type=\"text/css\" media=\"screen\" />\n";
		echo "<link rel='stylesheet' type='text/css' media='print' href='".$CFG_GLPI["root_doc"]."/css/print.css' >";
		echo "<link rel='shortcut icon' type='images/x-icon' href='".$CFG_GLPI["root_doc"]."/pics/favicon.ico' >";
		// AJAX library
		echo "<script type=\"text/javascript\" src='".$CFG_GLPI["root_doc"]."/lib/scriptaculous/prototype.js'></script>";
		echo "<script type=\"text/javascript\" src='".$CFG_GLPI["root_doc"]."/lib/scriptaculous/scriptaculous.js'></script>";
		// Some Javascript-Functions which we may need later
		echo "<script type=\"text/javascript\" src='".$CFG_GLPI["root_doc"]."/script.js'></script>";
	
		// Calendar scripts 
		echo "<style type=\"text/css\">@import url(".$CFG_GLPI["root_doc"]."/lib/calendar/aqua/theme.css);</style>";
		echo "<script type=\"text/javascript\" src=\"".$CFG_GLPI["root_doc"]."/lib/calendar/calendar.js\"></script>";
		echo "<script type=\"text/javascript\" src=\"".$CFG_GLPI["root_doc"]."/lib/calendar/lang/calendar-".$CFG_GLPI["languages"][$_SESSION["glpilanguage"]][4].".js\"></script>";
		echo "<script type=\"text/javascript\" src=\"".$CFG_GLPI["root_doc"]."/lib/calendar/calendar-setup.js\"></script>";
	
	
		// End of Head
		echo "</head>\n";

	if (!($CFG_GLPI["cache"]->start($sector,"GLPI_HEADER_".$_SESSION["glpiID"]))) {
		
	// Body 
		echo "<body>";
	

		//  Generatiion des array pour les menus avec check des droits 	
		//////// UTILS
		$utils=array();
		if (haveRight("knowbase","r")||haveRight("faq","r")) 
			$utils[$LANG["Menu"][19]]=array("knowbase.php"," ");
		if (haveRight("reservation_helpdesk","1")||haveRight("reservation_central","r")) 
			$utils[$LANG["Menu"][17]]=array("reservation.php","1");
		if (haveRight("reports","r"))
			$utils[$LANG["Menu"][6]]=array("report.php"," ");
		if ($CFG_GLPI["ocs_mode"]&&haveRight("ocsng","w")) 
			$utils[$LANG["Menu"][33]]=array("ocsng.php"," ");
	
		//////// INVENTORY
		$inventory=array();
		$showstate=false;
		if (haveRight("computer","r")){
			$inventory[$LANG["Menu"][0]]=array("computer.php","c");
			$showstate=true;
		}
		if (haveRight("monitor","r")){
			$inventory[$LANG["Menu"][3]]=array("monitor.php","m");
			$showstate=true;
		}
		if (haveRight("software","r")){
			$inventory[$LANG["Menu"][4]]=array("software.php","s");  
			$showstate=true;
		}
		if (haveRight("networking","r")){
			$inventory[$LANG["Menu"][1]]=array("networking.php","n");
			$showstate=true;
		}
		if (haveRight("peripheral","r")){
			$inventory[$LANG["Menu"][16]]=array("peripheral.php","r");
			$showstate=true;
		}
		if (haveRight("printer","r")){
			$inventory[$LANG["Menu"][2]]=array("printer.php","p");
			$showstate=true;
		}
		if (haveRight("cartridge","r")){
			$inventory[$LANG["Menu"][21]]=array("cartridge.php","c");
		}
		if (haveRight("consumable","r")){
			$inventory[$LANG["Menu"][32]]=array("consumable.php","g");
		}
		if (haveRight("phone","r")){
			$inventory[$LANG["Menu"][34]]=array("phone.php","n");
			$showstate=true;
		}
		if ($showstate){
			$inventory[$LANG["Menu"][28]]=array("state.php","s");
		}
	
		//////// FINANCIAL
		$financial=array();
		if (haveRight("contact_enterprise","r")){
			$financial[$LANG["Menu"][22]]=array("contact.php","t");
			$financial[$LANG["Menu"][23]]=array("enterprise.php","e");
		}
		if (haveRight("contract_infocom","r"))
			$financial[$LANG["Menu"][25]]=array("contract.php","n");
		if (haveRight("document","r"))
			$financial[$LANG["Menu"][27]]=array("document.php","d");
	
		$maintain=array();
		//////// ASSISTANCE
		if (haveRight("observe_ticket","1")||haveRight("show_ticket","1")||haveRight("create_ticket","1"))
			$maintain[$LANG["Menu"][5]]=array("tracking.php","t");
		if (haveRight("create_ticket","1"))
			$maintain[$LANG["Menu"][31]]=array("helpdesk.php","h");
		if (haveRight("show_planning","1")||haveRight("show_all_planning","1"))
			$maintain[$LANG["Menu"][29]]=array("planning.php","l");
		if (haveRight("statistic","1"))
			$maintain[$LANG["Menu"][13]]=array("stat.php","1");
	
		//////// ADMINISTRATION
		if (haveRight("user","r"))
			$config[$LANG["Menu"][14]]=array("user.php","u");
		// TODO SPECIFIC RIGHT TO ENTITY
		if (haveRight("config","r"))
			$config["ENTITE"]=array("entity.php","z");
		if (haveRight("group","r"))
			$config[$LANG["Menu"][36]]=array("group.php","g");
		if (haveRight("profile","r"))
			$config[$LANG["Menu"][35]]=array("profile.php","p");
		$config[$LANG["Menu"][10]]=array("setup.php","2");
		if (haveRight("backup","w"))
			$config[$LANG["Menu"][12]]=array("backup.php","b");
		if (haveRight("logs","r"))
			$config[$LANG["Menu"][30]]=array("log.php","l");

	
	
		echo "<div id='header'>";
		
		// Les préférences + lien déconnexion 
		echo "<div id='c_preference' >";
		echo" <ul><li id='deconnexion'><a href=\"".$CFG_GLPI["root_doc"]."/logout.php\"  title=\"".$LANG["central"][6]."\">".$LANG["central"][6]."  </a>";
		echo "(";
		if (!empty($_SESSION["glpirealname"])) {
			echo $_SESSION["glpirealname"];
			if (strlen($_SESSION["glpirealname"]." ".$_SESSION["glpifirstname"])<20) echo " ".$_SESSION["glpifirstname"];
		}
		else echo $_SESSION["glpiname"];
		echo ")</li>\n"; 

		echo "	<li><a href='#' onClick=\"window.open('".$CFG_GLPI["root_doc"]."/help/".$CFG_GLPI["languages"][$_SESSION["glpilanguage"]][2]."','helpdesk','width=750')\" title=\"".$LANG["central"][7]."\" >    ".$LANG["central"][7]."</a></li>\n"; 
		echo "	<li> <a href=\"".$CFG_GLPI["root_doc"]."/front/user.form.my.php\" title=\"".$LANG["Menu"][11]."\" >".$LANG["Menu"][11]."   </a></li>\n"; 
		echo "</ul>\n"; 
		echo "<div class='sep'></div>\n"; 
		echo "</div>\n"; 
		
		//-- Le moteur de recherche -->
		echo "<div id='c_recherche' >\n"; 
		echo "<form method='get' action='".$CFG_GLPI["root_doc"]."/front/search.php'>\n"; 
		echo "	<div id='boutonRecherche'><input type='image' src='".$CFG_GLPI["root_doc"]."/pics/ok2.png'  value='OK'   title=\"".$LANG["buttons"][2]."\"  alt=\"".$LANG["buttons"][2]."\"  /></div>\n"; 
		echo "	<div id='champRecherche'><input type='text' name='globalsearch' value='".$LANG["buttons"][0]."' onfocus=\"this.value='';\" /></div>	\n"; 		
		echo "</form>\n"; 
		echo "<div class='sep'></div>\n"; 
		echo "</div>";
	
		//<!-- Le menu principal -->
		echo "<div id='c_menu'>";
		echo "<div id='c_logo' ><a href='".$CFG_GLPI["root_doc"]."/front/central.php'  title=\"".$LANG["central"][5]."\"><span class='invisible'>Logo</span></a></div>";
		echo "	<ul id='menu'>";
		
	
		// Get object-variables and build the navigation-elements
	
		// Inventory
		if (count($inventory)) {
			echo "	<li id='menu1' onmouseover=\"javascript:menuAff('menu1','menu');\" >";
			echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/computer.php\" title=\"".$LANG["setup"][10]."\" class='itemP'>".$LANG["setup"][10]."</a>"; // default computer
			echo "<ul class='ssmenu'>"; 
			$i=0;
			// list menu item 
			foreach ($inventory as $key => $val) {
				echo "<li><a href=\"".$CFG_GLPI["root_doc"]."/front/".$val[0]."\" accesskey=\"".$val[1]."\">".$key."</a></li>\n";
				$i++;
			}
	
			echo "</ul>";
			echo "</li>";		
		}
	
		// Maintain / Tracking / ticket
		if (count($maintain)) {
			echo "	<li id='menu2' onmouseover=\"javascript:menuAff('menu2','menu');\" >";
			echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/tracking.php\" title=\"".$LANG["title"][24]."\"   class='itemP'>".$LANG["title"][24]."</a>"; // default tracking
			echo "<ul class='ssmenu'>";	
			// list menu item 
			foreach ($maintain as $key => $val) {
				echo "<li><a href=\"".$CFG_GLPI["root_doc"]."/front/".$val[0]."\" accesskey=\"".$val[1]."\">".$key."</a></li>\n";
			}
			echo "</ul>";
			echo "</li>";
		}
		// Financial
		if (count($financial)) {
			echo "	<li id='menu3' onmouseover=\"javascript:menuAff('menu3','menu');\" >";
			echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/contact.php\" title=\"".$LANG["Menu"][26]."\" class='itemP'>".$LANG["Menu"][26]."</a>"; // default knowbase
			echo "<ul class='ssmenu'>"; 
			// list menu item 
			foreach ($financial as $key => $val) {
				echo "<li><a href=\"".$CFG_GLPI["root_doc"]."/front/".$val[0]."\" accesskey=\"".$val[1]."\">".$key."</a></li>\n";
			}
			echo "</ul>";
			echo "</li>";
		}
	
		// Tools
		if (count($utils)) {
			echo "	<li id='menu4' onmouseover=\"javascript:menuAff('menu4','menu');\" >";
			echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/knowbase.php\" title=\"".$LANG["Menu"][18]."\" class='itemP'>".$LANG["Menu"][18]."</a>"; // default knowbase
			echo "<ul class='ssmenu'>"; 
			// list menu item 
			foreach ($utils as $key => $val) {
				echo "<li><a href=\"".$CFG_GLPI["root_doc"]."/front/".$val[0]."\" accesskey=\"".$val[1]."\">".$key."</a></li>\n";
			}
			echo "</ul>";
			echo "</li>";
		}
	
		// PLUGINS
		$plugins=array();
		if (isset($PLUGIN_HOOKS["menu_entry"])&&count($PLUGIN_HOOKS["menu_entry"]))
			foreach  ($PLUGIN_HOOKS["menu_entry"] as $plugin => $active) {
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
			echo "	<li id='menu5' onmouseover=\"javascript:menuAff('menu5','menu');\" >";
			echo "<a href='#' title=\"".$LANG["common"][29]."\"  class='itemP'>".$LANG["common"][29]."</a>";  // default none
			echo "<ul class='ssmenu'>"; 
			// list menu item 
			foreach ($list as $key => $val) {
				echo "<li><a href=\"".$CFG_GLPI["root_doc"]."/plugins/".$key."/\">".$plugins[$key]["name"]."</a></li>\n";
			}
			echo "</ul>";
			echo "</li>";
		}
	
		// Administration 
		if (count($config)) {
			echo "	<li id='menu6' onmouseover=\"javascript:menuAff('menu6','menu');\" >";
			echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/user.php\" title=\"".$LANG["Menu"][15]."\"  class='itemP1'>".$LANG["Menu"][15]."</a>"; // default user
			echo "<ul class='ssmenu'>"; 
			// list menu item 
			foreach ($config as $key => $val) {
				echo "<li><a href=\"".$CFG_GLPI["root_doc"]."/front/".$val[0]."\" accesskey=\"".$val[1]."\">".$key."</a></li>\n";
			}
			echo "</ul>";
			echo "</li>";
		}
	
		echo "</ul>";		
		echo "<div class='sep'></div>";
		echo "</div>";
	
		// End navigation bar
	
		// End headline
		
		///Le sous menu contextuel 1
		echo "<div id='c_ssmenu1' >";
		echo "<ul>";
		switch ($sector){
			case "inventory":
			$sous_menu=$inventory;
			break;
			case "utils":
			$sous_menu=$utils;	
			break;
			case "financial":
			$sous_menu=$financial;
			break;
			case "admin":
			$sous_menu=$config;	
			break;
			case "maintain":
			$sous_menu=$maintain;	
			break;
			case "plugins":
			$sous_menu=$list;
			break;
		}
		// list sous-menu item 
			if ($sector!="none"){
				if (isset($sous_menu)&&is_array($sous_menu)){
					foreach ($sous_menu as $key => $val) {
						if ($sector=="plugins"){
							echo "<li><a href=\"".$CFG_GLPI["root_doc"]."/plugins/".$key."/\">".$plugins[$key]["name"]."</a></li>\n";
						}else{
							echo "<li><a href=\"".$CFG_GLPI["root_doc"]."/front/".$val[0]."\" accesskey=\"".$val[1]."\">".$key."</a></li>\n";
						}
					}
				}
			}
		
		echo "</ul>";
		echo "</div>";
		
		//  Le fil d arianne 
		echo "<div id='c_ssmenu2' >";
		echo "<ul>";
		echo "	<li><a href='' title='' >Central > </a></li>";
		//echo "	<li>Helpdesk</li>";
		echo "</ul>";	
		echo showProfileSelecter();	
		echo "	</div>";
			
		echo "</div>\n"; // fin header

		
		
		echo "<div  id='page' >";
		
		$CFG_GLPI["cache"]->end();
	}
	
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

	global $CFG_GLPI,$LANG, $CFG_GLPI,$HEADER_LOADED ;

	if ($HEADER_LOADED) return;
	$HEADER_LOADED=true;

	// Override list-limit if choosen
	if (isset($_POST['list_limit'])) {
		$_SESSION['glpilist_limit']=$_POST['list_limit'];
		$CFG_GLPI["list_limit"]=$_POST['list_limit'];
	}

	// Send extra expires header if configured
	if ($CFG_GLPI["sendexpire"]) {
		header_nocache(); 
	}

	// Send UTF8 Headers
	header("Content-Type: text/html; charset=UTF-8");

	// Start the page
	echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">";
	echo "<html><head><title>GLPI Helpdesk - ".$title."</title>";
	echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8 \" >";
	echo "<link rel='shortcut icon' type='images/x-icon' href='".$CFG_GLPI["root_doc"]."/pics/favicon.ico' >";
	// Send extra expires header if configured

	if ($CFG_GLPI["sendexpire"]) {
		echo "<meta http-equiv=\"Expires\" content=\"Fri, Jun 12 1981 08:20:00 GMT\">\n";
		echo "<meta http-equiv=\"Pragma\" content=\"no-cache\">\n";
		echo "<meta http-equiv=\"Cache-Control\" content=\"no-cache\">\n";
	}

	// Some Javascript-Functions which we may need later

	echo "<script type=\"text/javascript\" src='".$CFG_GLPI["root_doc"]."/script.js'></script>";

	// AJAX library
	echo "<script type=\"text/javascript\" src='".$CFG_GLPI["root_doc"]."/lib/scriptaculous/prototype.js'></script>";
	echo "<script type=\"text/javascript\" src='".$CFG_GLPI["root_doc"]."/lib/scriptaculous/scriptaculous.js'></script>";


	// Appel CSS

	echo "<link rel='stylesheet'  href='".$CFG_GLPI["root_doc"]."/css/styles.css' type='text/css' media='screen' >";
	echo "<link rel='stylesheet' type='text/css' media='print' href='".$CFG_GLPI["root_doc"]."/css/print.css' >";

	// Calendar scripts 
	echo "<style type=\"text/css\">@import url(".$CFG_GLPI["root_doc"]."/lib/calendar/aqua/theme.css);</style>";
	echo "<script type=\"text/javascript\" src=\"".$CFG_GLPI["root_doc"]."/lib/calendar/calendar.js\"></script>";
	echo "<script type=\"text/javascript\" src=\"".$CFG_GLPI["root_doc"]."/lib/calendar/lang/calendar-".$CFG_GLPI["languages"][$_SESSION["glpilanguage"]][4].".js\"></script>";
	echo "<script type=\"text/javascript\" src=\"".$CFG_GLPI["root_doc"]."/lib/calendar/calendar-setup.js\"></script>";

	// End of Head
	echo "</head>\n";

	// Body 
	echo "<body>";

	// Main Headline
	echo "<div id='header'>";
		// Les préférences + lien déconnexion 
		echo "<div id='c_preference' >";
		echo" <ul><li id='deconnexion'><a href=\"".$CFG_GLPI["root_doc"]."/logout.php\"  title=\"".$LANG["central"][6]."\">".$LANG["central"][6]."  </a>";
		echo "(";
		if (!empty($_SESSION["glpirealname"])) {
			echo $_SESSION["glpirealname"];
			if (strlen($_SESSION["glpirealname"]." ".$_SESSION["glpifirstname"])<20) echo " ".$_SESSION["glpifirstname"];
		}
		else echo $_SESSION["glpiname"];
		echo ")</li>\n"; 

		echo "	<li><a href='#' onClick=\"window.open('".$CFG_GLPI["root_doc"]."/help/".$CFG_GLPI["languages"][$_SESSION["glpilanguage"]][3]."','helpdesk','width=400,height=600,scrollbars=yes')\">    ".$LANG["central"][7]."</a></li>\n"; 
		echo "	<li> <a href=\"".$CFG_GLPI["root_doc"]."/front/user.form.my.php\" title=\"".$LANG["Menu"][11]."\" >".$LANG["Menu"][11]."   </a></li>\n"; 
					
		echo "</ul>\n"; 
		echo "<div class='sep'></div>\n"; 
		echo "</div>\n"; 
		//-- Le moteur de recherche -->
		echo "<div id='c_recherche' >\n"; 
		/*
		echo "<form id='recherche' action=''>\n"; 
		echo "	<div id='boutonRecherche'><input type='submit' value='OK' /></div>\n"; 
		echo "	<div id='champRecherche'><input type='text' value='Recherche' /></div>	\n"; 		
		echo "</form>\n"; 
		*/
		echo "<div class='sep'></div>\n"; 
		echo "</div>";
	
		//<!-- Le menu principal -->
		echo "<div id='c_menu'>";
		echo "<div id='c_logo' ><a href=\"".$CFG_GLPI["root_doc"]."/front/helpdesk.public.php\" accesskey=\"0\"  title=\"".$LANG["central"][5]."\"><span class='invisible'>Logo</span></a></div>";
		echo "	<ul id='menu'>";
		
	
		// Build the navigation-elements
	
		// Ticket
		if (haveRight("create_ticket","1")){
			echo "	<li id='menu1' >";
			echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/helpdesk.public.php\"  title=\"".$LANG["job"][13]."\" class='itemP'>".$LANG["Menu"][31]."</a>";
			
			echo "</li>";		
		}
	
		//  Suivi  ticket
		if (haveRight("observe_ticket","1")){
			echo "	<li id='menu2' >";
			echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/helpdesk.public.php?show=user\" title=\"".$LANG["tracking"][0]."\"   class='itemP'>".$LANG["title"][28]."</a>";
			
			echo "</li>";
		}
		// Reservation
		if (haveRight("reservation_helpdesk","1")){
			echo "	<li id='menu3' >";
			echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/helpdesk.resa.php\"  title=\"".$LANG["Menu"][17]."\" class='itemP'>".$LANG["Menu"][17]."</a>";
			
			echo "</li>";
		}
	
		// FAQ
		if (haveRight("faq","r")){
			echo "	<li id='menu4' >";
			echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/helpdesk.faq.php\" title=\"".$LANG["knowbase"][1]."\" class='itemP'>".$LANG["Menu"][20]."</a>";
			
			echo "</li>";
		}
	
			
		echo "</ul>";		
		echo "<div class='sep'></div>";
		echo "</div>";
	
		// End navigation bar
	
		// End headline
		
		///Le sous menu contextuel 1
		echo "<div id='c_ssmenu1' >";
		//echo "<ul>";
		//echo "	<li><a href='' title='' >Suivi</a></li>";
		//echo "	<li>Planning</li>";
		//echo "	<li>Statistique</li>";
		//echo "	<li>Helpdesk</li>";
		//echo "</ul>";
		echo "</div>";

		//  Le fil d arianne 
		echo "<div id='c_ssmenu2' >";
		echo "<ul>";
		echo "	<li><a href='#' title='' >Helpdesk > </a></li>";
		//echo "	<li>Helpdesk</li>";
		echo "</ul>";	
		echo showProfileSelecter();	
		echo "	</div>";
			
		echo "</div>\n"; // fin header

		echo "<div  id='page' >";
		
	
	
	

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
	global $CFG_GLPI,$HEADER_LOADED,$LANG ;
	if ($HEADER_LOADED) return;
	$HEADER_LOADED=true;
	// Print a nice HTML-head with no controls

	// Detect root_doc in case of error
	if (!isset($CFG_GLPI["root_doc"])){
		if ( !isset($_SERVER['REQUEST_URI']) ) {
			$_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'];
		}
		$currentdir=getcwd();
		chdir(GLPI_ROOT);
		$glpidir=str_replace(str_replace('\\', '/',getcwd()),"",str_replace('\\', '/',$currentdir));
		chdir($currentdir);
			
		$globaldir=preg_replace("/\/[0-9a-zA-Z\.\-\_]+\.php/","",$_SERVER['REQUEST_URI']);
		$globaldir=preg_replace("/\?.*/","",$globaldir);
		$CFG_GLPI["root_doc"]=str_replace($glpidir,"",$globaldir);
		$CFG_GLPI["root_doc"]=preg_replace("/\/$/","",$CFG_GLPI["root_doc"]);
		$CFG_GLPI["logotxt"]="";
	}

	// Send UTF8 Headers
	header("Content-Type: text/html; charset=UTF-8");

	// Override list-limit if choosen
	if (isset($_POST['list_limit'])) {
		$_SESSION['glpilist_limit']=$_POST['list_limit'];
		$CFG_GLPI["list_limit"]=$_POST['list_limit'];
	}

	// Send extra expires header if configured
	if (!empty($CFG_GLPI["sendexpire"])) {
		header_nocache();
	}

	// Start the page
	echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">";
	echo "<html><head><title>GLPI - ".$title."</title>";
	echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8 \" >";
	echo "<link rel='shortcut icon' type='images/x-icon' href='".$CFG_GLPI["root_doc"]."/pics/favicon.ico' >";
	// Send extra expires header if configured
	if (!empty($cft_features["sendexpire"])) {
		echo "<meta http-equiv=\"Expires\" content=\"Fri, Jun 12 1981 08:20:00 GMT\">\n";
		echo "<meta http-equiv=\"Pragma\" content=\"no-cache\">\n";
		echo "<meta http-equiv=\"Cache-Control\" content=\"no-cache\">\n";
	}

	// Some Javascript-Functions which we may need later

	echo "<script type=\"text/javascript\" src='".$CFG_GLPI["root_doc"]."/script.js'></script>";

	// AJAX library
	echo "<script type=\"text/javascript\" src='".$CFG_GLPI["root_doc"]."/lib/scriptaculous/prototype.js'></script>";
	echo "<script type=\"text/javascript\" src='".$CFG_GLPI["root_doc"]."/lib/scriptaculous/scriptaculous.js'></script>";

	// Appel CSS

	echo "<link rel='stylesheet'  href='".$CFG_GLPI["root_doc"]."/css/styles.css' type='text/css' media='screen' >";

	// Calendar scripts 
	if (isset($_SESSION["glpilanguage"])){
		echo "<style type=\"text/css\">@import url(".$CFG_GLPI["root_doc"]."/lib/calendar/aqua/theme.css);</style>";
		echo "<script type=\"text/javascript\" src=\"".$CFG_GLPI["root_doc"]."/lib/calendar/calendar.js\"></script>";
		echo "<script type=\"text/javascript\" src=\"".$CFG_GLPI["root_doc"]."/lib/calendar/lang/calendar-".$CFG_GLPI["languages"][$_SESSION["glpilanguage"]][4].".js\"></script>";
		echo "<script type=\"text/javascript\" src=\"".$CFG_GLPI["root_doc"]."/lib/calendar/calendar-setup.js\"></script>";
	}


	// End of Head
	echo "</head>\n";

	// Body with configured stuff
	echo "<body>";

	echo "<div id='contenu-nullHeader'>";

	echo "<div id='text-nullHeader'>";
		
			
		
	
	
}


function popHeader($title,$url)
{
	// Print a nice HTML-head for every page

	global $CFG_GLPI,$LANG,$PLUGIN_HOOKS,$HEADER_LOADED ;

	if ($HEADER_LOADED) return;
	$HEADER_LOADED=true;


	// Override list-limit if choosen
	if (isset($_POST['list_limit'])) {
		$_SESSION['glpilist_limit']=$_POST['list_limit'];
		$CFG_GLPI["list_limit"]=$_POST['list_limit'];
	}



	// Send UTF8 Headers
	header("Content-Type: text/html; charset=UTF-8");
	// Send extra expires header if configured
	if ($CFG_GLPI["sendexpire"]) {
		header_nocache();
	}
	// Start the page
	echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">";
	echo "\n<html><head><title>GLPI - ".$title."</title>";
	echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8 \" >";
	// Send extra expires header if configured
	if ($CFG_GLPI["sendexpire"]) {
		echo "<meta http-equiv=\"Expires\" content=\"Fri, Jun 12 1981 08:20:00 GMT\">\n";
		echo "<meta http-equiv=\"Pragma\" content=\"no-cache\">\n";
		echo "<meta http-equiv=\"Cache-Control\" content=\"no-cache\">\n";
	}
	//  CSS link
	echo "<link rel='stylesheet'  href='".$CFG_GLPI["root_doc"]."/css/styles.css' type='text/css' media='screen' >";
	echo "<link rel='stylesheet' type='text/css' media='print' href='".$CFG_GLPI["root_doc"]."/css/print.css' >";
	echo "<link rel='shortcut icon' type='images/x-icon' href='".$CFG_GLPI["root_doc"]."/pics/favicon.ico' >";
	// AJAX library
	echo "<script type=\"text/javascript\" src='".$CFG_GLPI["root_doc"]."/lib/scriptaculous/prototype.js'></script>";
	echo "<script type=\"text/javascript\" src='".$CFG_GLPI["root_doc"]."/lib/scriptaculous/scriptaculous.js'></script>";
	// Some Javascript-Functions which we may need later
	echo "<script type=\"text/javascript\" src='".$CFG_GLPI["root_doc"]."/script.js'></script>";

	// Calendar scripts 
	echo "<style type=\"text/css\">@import url(".$CFG_GLPI["root_doc"]."/lib/calendar/aqua/theme.css);</style>";
	echo "<script type=\"text/javascript\" src=\"".$CFG_GLPI["root_doc"]."/lib/calendar/calendar.js\"></script>";
	echo "<script type=\"text/javascript\" src=\"".$CFG_GLPI["root_doc"]."/lib/calendar/lang/calendar-".$CFG_GLPI["languages"][$_SESSION["glpilanguage"]][4].".js\"></script>";
	echo "<script type=\"text/javascript\" src=\"".$CFG_GLPI["root_doc"]."/lib/calendar/calendar-setup.js\"></script>";


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

	global $LANG,$CFG_GLPI,$DEBUG_SQL_STRING,$TIMER_DEBUG,$SQL_TOTAL_TIMER,$SQL_TOTAL_REQUEST,$FOOTER_LOADED;

	if ($FOOTER_LOADED) return;
	$FOOTER_LOADED=true;

	echo "</div>"; // fin de la div id ='page' initiée dans la fonction header
	
	echo "<div id='footer' >";
	echo "<table width='100%'><tr><td align='left'><span class='copyright'>";
	echo $TIMER_DEBUG->Get_Time()."s - ";
	if (function_exists("memory_get_usage")){
		echo memory_get_usage();
		echo " ";
	}
	echo "</span>";

	if (!empty($CFG_GLPI["founded_new_version"]))
		echo "<td align='center' class='copyright'>".$LANG["setup"][301]." ".$CFG_GLPI["founded_new_version"]."<br>".$LANG["setup"][302]."</td>";
	echo "<td class='copyright'>";
	echo date("H:i")."&nbsp;-&nbsp;<i>".date("j. M Y")."</i>";
	echo "</td>";

	echo "<td align='right'>";
	echo "<a href=\"http://glpi-project.org/\">";
	echo "<span class='copyright'>GLPI ".$CFG_GLPI["version"]." Copyright (C) 2003-".date("Y")." by the INDEPNET Development Team.</span>";
	echo "</a>";

	echo "</td></tr>";
	echo "</table></div>";



	if ($CFG_GLPI["debug"]==1){ // debug mode traduction

		echo "<div id='debug-float'>";		
		echo "<a href='#debug'>GLPI MODE TRANSLATION</a>";
		echo "</div>";
	}

	if ($CFG_GLPI["debug"]==2){ // mode debug 

		echo "<div id='debug-float'>";		
		echo "<a href='#debug'>GLPI MODE DEBUG</a>";
		echo "</div>";



		echo "<div id='debug'>";
		echo "<h1><a name='#debug'>GLPI MODE DEBUG</a></h1>";
		/*
		// déjà dans le footer
		if ($CFG_GLPI["debug_profile"]){
			echo "TIME : ";
			echo $TIMER_DEBUG->Get_Time()."s";
			if (function_exists("memory_get_usage")){
				echo "MEMORY : ";
				echo memory_get_usage();
				echo "";
			}
		}*/
		
		if ($CFG_GLPI["debug_sql"]){	
			echo "<h2>SQL REQUEST : ";
			
			echo $SQL_TOTAL_REQUEST." Queries ";
			if ($CFG_GLPI["debug_profile"]){
				echo "took  ".$SQL_TOTAL_TIMER."s  </h2>";
			}

			echo "<table class='tab_cadre' style='width:100%'><tr><th>N&#176; </th><th>Queries</th><th>Time</th></tr>";
			echo eregi_replace("ORDER BY","<br>ORDER BY",eregi_replace("SORT","<br>SORT",eregi_replace("LEFT JOIN","<br>LEFT JOIN",eregi_replace("WHERE","<br>WHERE",eregi_replace("FROM","<br>FROM",$DEBUG_SQL_STRING)))));
		}
		
		
		echo "</table>";
		
		if ($CFG_GLPI["debug_vars"]){
			echo "<h2>POST VARIABLE</h2>";
			printCleanArray($_POST);
			echo "<h2>GET VARIABLE</h2>";
			printCleanArray($_GET);
			echo "<h2>SESSION VARIABLE</h2>";
			printCleanArray($_SESSION);
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
	global $CFG_GLPI,$FOOTER_LOADED;

	if ($FOOTER_LOADED) return;
	$FOOTER_LOADED=true;
	
	echo "</div>"; // fin de la div id ='page' initiée dans la fonction header

	echo "<div id='footer'>";
	echo "<table width='100%'><tr><td align='left'><span class='copyright'>";
	echo date("H:i")."&nbsp;-&nbsp;<i>".date("j. M Y")."</i></span>";
	echo "</td><td align='right'>";
	echo "<a href=\"http://glpi-project.org/\">";
	echo "<span class='copyright'>GLPI ".$CFG_GLPI["version"]." Copyright (C) 2003-".date("Y")." by the INDEPNET Development Team.</span>";
	echo "</a></tr></table>";
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
	global $CFG_GLPI,$FOOTER_LOADED;

	if ($FOOTER_LOADED) return;
	$FOOTER_LOADED=true;

	echo "</div>";  // fin box text-nullHeader ouvert dans le null header
	echo "</div>"; // fin contenu-nullHeader ouvert dans le null header
	

	echo "<div id='footer-login'>";
	echo "<a href=\"http://glpi-project.org/\" title=\"Powered By Indepnet\"  >";
	echo 'GLPI version '.(isset($CFG_GLPI["version"])?$CFG_GLPI["version"]:"").' Copyright (C) 2003-'.date("Y").' INDEPNET Development Team.';
	echo "</a>";
	echo "</div>";
	
	
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

	global $DB,$CFG_GLPI,$LANG;

	if (!haveRight("create_ticket","1")) return false;

	$query = "SELECT email,realname,firstname,name FROM glpi_users WHERE (ID = '$ID')";
	$result=$DB->query($query);
	$email = $DB->result($result,0,"email");

	// Get saved data from a back system
	$emailupdates = 'yes';
	if ($email=="") $emailupdates='no';
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

	echo "<form method='post' name=\"helpdeskform\" action=\"".$CFG_GLPI["root_doc"]."/front/tracking.injector.php\"  enctype=\"multipart/form-data\">";
	echo "<input type='hidden' name='_from_helpdesk' value='$from_helpdesk'>";
	echo "<input type='hidden' name='request_type' value='1'>";

	echo "<div align='center'><table  class='tab_cadre'>";

	echo "<tr><th colspan='2'>".$LANG["help"][1].":</th></tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<td>".$LANG["help"][2].": </td>";
	echo "<td>";
	dropdownPriority("priority",3);
	echo "</td></tr>";
	if($CFG_GLPI["mailing"] != 0)
	{
		echo "<tr class='tab_bg_1'>";
		echo "<td>".$LANG["help"][8].":</td>";
		echo "<td>	<select name='emailupdates'>";
		echo "<option value='no' ".(($emailupdates=="no")?" selected":"").">".$LANG["choice"][0]."";
		echo "<option value='yes' ".(($emailupdates=="yes")?" selected":"").">".$LANG["choice"][1]."";
		echo "</select>";
		echo "</td></tr>";
		echo "<tr class='tab_bg_1'>";
		echo "<td>".$LANG["help"][11].":</td>";
		echo "<td>	<input name='uemail' value=\"$email\" size='50' onchange=\"emailupdates.value='yes'\">";
		echo "</td></tr>";
	}

	if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"]!=0){
		echo "<tr class='tab_bg_1'>";
		echo "<td>".$LANG["help"][24].": </td>";
		echo "<td align='center'>";
		dropdownMyDevices($_SESSION["glpiID"]);
		dropdownTrackingAllDevices("device_type",$device_type);
		echo "</td></tr>";
	}

	echo "<tr class='tab_bg_1'>";
	echo "<td>".$LANG["common"][36].":</td><td>";

	dropdownValue("glpi_dropdown_tracking_category","category",$category);
	echo "</td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'>";
	echo "<td colspan='2' align='center'>".$LANG["help"][13].":</td>";
	echo "</tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<td colspan='2' align='center'><textarea name='contents' cols='80' rows='14' >$contents</textarea>";
	echo "</td></tr>";

	$max_size=return_bytes_from_ini_vars(ini_get("upload_max_filesize"));
	$max_size/=1024*1024;
	$max_size=round($max_size,1);

	echo "<tr class='tab_bg_1'><td>".$LANG["document"][2]." (".$max_size." Mb max):	";
	echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/aide.png\" style='cursor:pointer;' alt=\"aide\"onClick=\"window.open('".$CFG_GLPI["root_doc"]."/front/typedoc.list.php','Help','scrollbars=1,resizable=1,width=1000,height=800')\">";
	echo "</td>";
	echo "<td><input type='file' name='filename' value=\"\" size='25'></td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'>";
	echo "<td colspan='2' align='center'> <input type='submit' value=\"".$LANG["help"][14]."\" class='submit'>";
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

	global $CFG_GLPI, $LANG,$CFG_GLPI;

	// Forward is the next step forward
	$forward = $start+$CFG_GLPI["list_limit"];

	// This is the end, my friend	
	$end = $numrows-$CFG_GLPI["list_limit"];

	// Human readable count starts here
	$current_start=$start+1;

	// And the human is viewing from start to end
	$current_end = $current_start+$CFG_GLPI["list_limit"]-1;
	if ($current_end>$numrows) {
		$current_end = $numrows;
	}

	// Backward browsing 
	if ($current_start-$CFG_GLPI["list_limit"]<=0) {
		$back=0;
	} else {
		$back=$start-$CFG_GLPI["list_limit"];
	}

	// Print it

	echo "<div align='center' style='font-size:6px;'><table class='tab_cadre_pager'>\n";
	echo "<tr>\n";

	// Back and fast backward button
	if (!$start==0) {
		echo "<th align='left'>";
		echo "<a href=\"$target?$parameters&amp;start=0\">";
		echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/first.png\" alt='".$LANG["buttons"][33]."' title='".$LANG["buttons"][33]."'>";


		echo "</a></th>\n";
		echo "<th align='left'>";
		echo "<a href=\"$target?$parameters&amp;start=$back\">";
		echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/left.png\" alt='".$LANG["buttons"][12]."' title='".$LANG["buttons"][12]."'>";
		echo "</a></th>\n";
	}

	// Print the "where am I?" 
	echo "<td width='50%' align='center' class='tab_bg_2'>";
	echo "<form method='POST' action=\"$target?$parameters&amp;start=$start\">\n";
	echo "<span>".$LANG["pager"][4]."&nbsp;</span>";
	echo "<select name='list_limit' onChange='submit()'>";
	for ($i=5;$i<=200;$i+=5) echo "<option value='$i' ".((isset($_SESSION["glpilist_limit"])&&$_SESSION["glpilist_limit"]==$i)?" selected ":"").">$i</option>\n";
	echo "<option value='9999999' ".((isset($_SESSION["glpilist_limit"])&&$_SESSION["glpilist_limit"]==9999999)?" selected ":"").">9999999</option>\n";	
	echo "</select><span>&nbsp;";
	echo $LANG["pager"][5];
	echo "</span>";
	echo "</form>\n";
	echo "</td>\n";

	if ($item_type_output>0&&isset($_SESSION["glpiactiveprofile"])&&$_SESSION["glpiactiveprofile"]["interface"]=="central"){
		echo "<td class='tab_bg_2' width='30%'>" ;
		echo "<form method='GET' action=\"".$CFG_GLPI["root_doc"]."/front/report.dynamic.php\" target='_blank'>\n";
		echo "<input type='hidden' name='item_type' value='$item_type_output'>";
		if ($item_type_output_param!=0)
			echo "<input type='hidden' name='item_type_param' value='".serialize($item_type_output_param)."'>";
		$split=split("&amp;",$parameters);
		for ($i=0;$i<count($split);$i++){
			$pos=strpos($split[$i],'=');
			echo "<input type='hidden' name=\"".substr($split[$i],0,$pos)."\" value=\"".substr($split[$i],$pos+1)."\">";
		}
		echo "<select name='display_type'>";
		echo "<option value='2'>".$LANG["buttons"][27]."</option>";
		echo "<option value='1'>".$LANG["buttons"][28]."</option>";
		echo "<option value='-2'>".$LANG["buttons"][29]."</option>";
		echo "<option value='-1'>".$LANG["buttons"][30]."</option>";
		echo "</select>";
		echo "&nbsp;<input type='image' name='export'  src='".$CFG_GLPI["root_doc"]."/pics/export.png' title='".$LANG["buttons"][31]."' value='".$LANG["buttons"][31]."'>";
		echo "</form>";
		echo "</td>" ;
	}

	echo "<td  width='50%' align='center' class='tab_bg_2'><b>";

	echo $LANG["pager"][2]."&nbsp;".$current_start."&nbsp;".$LANG["pager"][1]."&nbsp;".$current_end."&nbsp;".$LANG["pager"][3]."&nbsp;".$numrows."&nbsp;";
	echo "</b></td>\n";

	// Forward and fast forward button
	if ($forward<$numrows) {
		echo "<th align='right'>";
		echo "<a href=\"$target?$parameters&amp;start=$forward\">";
		echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/right.png\" alt='".$LANG["buttons"][11]."' title='".$LANG["buttons"][11]."'>";
		echo "</a></th>\n";
		echo "<th align='right'>";
		echo "<a href=\"$target?$parameters&amp;start=$end\">";
		echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/last.png\" alt='".$LANG["buttons"][32]."' title='".$LANG["buttons"][32]."'>";
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
	global $LANG,$CFG_GLPI;
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
		echo "&nbsp;<img id='button$rand' src='".$CFG_GLPI["root_doc"]."/pics/calendar.png' class='calendrier' alt='".$LANG["buttons"][15]."' title='".$LANG["buttons"][15]."'>";

		echo "&nbsp;<img src='".$CFG_GLPI["root_doc"]."/pics/reset.png' class='calendrier' onClick=\"document.getElementById('data$rand').value='0000-00-00';document.getElementById('show$rand').value='".convDate("0000-00-00")."'\" alt='Reset' title='Reset'>";	

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
		if (!$CFG_GLPI["dateformat"]){
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
	global $LANG;

	if (!haveRight("notes","r")) return false;
	//new objet
	$ci =new CommonItem;
	//getfromdb
	$ci->getfromDB ($type,$id);


	echo "<form name='form' method='post' action=\"".$target."\">";
	echo "<div align='center'>";
	echo "<table class='tab_cadre_fixe' >";
	echo "<tr><th align='center' >";
	echo $LANG["title"][37];
	echo "</th></tr>";
	echo "<tr><td valign='middle' align='center' class='tab_bg_1' ><textarea class='textarea_notes' cols='100' rows='35' name='notes' >".$ci->getField('notes')."</textarea></td></tr>";
	echo "<tr><td class='tab_bg_2' align='center' >\n";
	echo "<input type='hidden' name='ID' value=$id>";
	if (haveRight("notes","w"))
		echo "<input type='submit' name='update' value=\"".$LANG["buttons"][7]."\" class='submit'>";
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
	global $LANG;
	$percentwidth=floor($percent*$width/100);
	echo str_pad("<div align='center'><table class='tab_cadre' width='$width'><tr><td width='$width' align='center'> ".$LANG["common"][47]."&nbsp;".$percent."%</td></tr><tr><td><table><tr><td bgcolor='red'  width='$percentwidth' height='20'>&nbsp;</td></tr></table></td></tr></table></div>\n",4096);
	glpi_flush();
}

function printCleanArray($tab,$pad=0){
	if (count($tab)){
		echo "<table class='tab_cadre'>";
		echo "<tr><th>KEY</th><th>=></th><th>VALUE</th></tr>";
		foreach($tab as $key => $val){
			echo "<tr class='tab_bg_1'><td valign='top' align='right'>";
			echo $key;
			echo "</td><td valign='top'>=></td><td valign='top'  class='tab_bg_1'>";
			if (is_array($val)){
				printCleanArray($val,$pad+1);
			}
			else echo $val;
			echo "</td></tr>";
		}
		echo "</table>";
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
	global $LANG,$PLUGIN_HOOKS;
	echo "<div id='barre_onglets'><ul id='onglet'>";
	echo "<li ".($actif=="my"?"class='actif'":"")."><a href='$target?onglet=my'>".$LANG["central"][12]."</a></li>";
	if (haveRight("show_ticket","1")||haveRight("logs","r")||haveRight("contract_infocom","r"))
		echo "<li ".($actif=="global"?"class='actif'":"")."><a href='$target?onglet=global'>".$LANG["central"][13]."</a></li>";
	if (isset($PLUGIN_HOOKS['central_action'])&&count($PLUGIN_HOOKS['central_action'])){
		echo "<li ".($actif=="plugins"?"class='actif'":"")."><a href='$target?onglet=plugins'>".$LANG["common"][29]."</a></li>";
	}
	echo "<li class='invisible'>&nbsp;</li>";
	echo "<li ".($actif=="all"?"class='actif'":"")."><a href='$target?onglet=all'>".$LANG["title"][29]."</a></li>";

	echo "</ul></div>";
}


function showCentralGlobalView(){

	global $CFG_GLPI,$LANG;

	$showticket=haveRight("show_ticket","1");

	echo "<div align='center'>";
	echo "<table  class='tab_cadre_central' ><tr>";

	echo "<td valign='top'>";
	echo "<table border='0'>";
	if ($showticket){
		echo "<tr><td align='center' valign='top'  width='450px'>";
		showCentralJobCount();
		echo "</td></tr>";
	}
	if (haveRight("contract_infocom","r")){
		echo "<tr>";
		echo "<td align='center' valign='top'  width='450px'>";
		showCentralContract();
		echo "</td>";	
		echo "</tr>";
	}
	echo "</table>";
	echo "</td>";

	if (haveRight("logs","r")){
		echo "<td align='left' valign='top'>";
		echo "<table border='0' width='450px'><tr>";
		echo "<td align='center'>";
		if ($CFG_GLPI["num_of_events"]>0){

			//Show last add events
			showAddEvents($_SERVER['PHP_SELF'],"","",$_SESSION["glpiname"]);

		} else {
			echo "&nbsp;";
		}
		echo "</td></tr>";
		echo "</table>";
		echo "</td>";
	}
	echo "</tr>";

	echo "</table>";
	echo "</div>";


	if ($CFG_GLPI["jobs_at_login"]){
		echo "<br>";

		echo "<div align='center'><b>";
		echo $LANG["central"][10];
		echo "</b></div>";

		showTrackingList($_SERVER['PHP_SELF'],$_GET["start"],"","","new");
	}

}

function showCentralMyView(){

		$showticket=haveRight("show_ticket","1");

		echo "<div align='center'>";
		echo "<table class='tab_cadre_central' >";
		echo "<tr><td valign='top'>";
		echo "<table border='0'>";
	
		if ($showticket){
			echo "<tr><td align='center' valign='top'  width='450px'>";
			showCentralJobList($_SERVER['PHP_SELF'],$_GET['start']);
			echo "</td></tr>";
			echo "<tr><td  align='center' valign='top' width='450px'>";
			showCentralJobList($_SERVER['PHP_SELF'],$_GET['start'],"waiting");
			echo "</td></tr>";
		}
	
		echo "</table></td><td valign='top'><table border='0'><tr>";
	
		echo "<td align='center' valign='top'  width='450px'><br>";
		ShowPlanningCentral($_SESSION["glpiID"]);
		echo "</td></tr>";
		echo "<tr>";
	
	
		echo "<td  align='center' valign='top' width='450'>";
		showCentralReminder();
		echo "</td>";
		echo "</tr>";
	
		if (haveRight("reminder_public","r")){
			echo "<tr><td align='center' valign='top'  width='450px'>";
			showCentralReminder("public");
			echo "</td></tr>";
		}
	
	
		echo "</table></td></tr></table>";
		echo "</div>";


}

function showProfileSelecter(){
	global $CFG_GLPI;

	if (count($_SESSION["glpiprofiles"])>1){
		echo '<form name="form" method="post" action="'.$CFG_GLPI['root_doc'].'/login.php">';
		echo '<select name="newprofile" onChange="submit()">';
		foreach ($_SESSION["glpiprofiles"] as $key => $val){
			echo '<option value="'.$key.'" '.($_SESSION["glpiactiveprofile"]["ID"]==$key?'selected':'').'>'.$val['name'].'</option>';
		}
		echo '</select>';
		echo '</form>';


	} //else echo "only one profile -> no select to print";
	
	if (count($_SESSION['glpiactiveentities'])>1){
		echo "<div style='float:right;'>";
			dropdownActiveEntities("activeentity");
		echo "</div>";
/*	foreach ($_SESSION["glpiactiveentities"] as $key => $val) {
		echo $val."-";
	}
*/
	} //else echo "only one entity -> no select to print";


} 

?>
