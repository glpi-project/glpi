<?php
/*
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2005 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------

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
 ------------------------------------------------------------------------
*/

// Based on:
// IRMA, Information Resource-Management and Administration
// Christian Bauer 
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

	GLOBAL $cfg_install,$lang, $cfg_layout,$cfg_features,$HTMLRel,$phproot ;

	// Override list-limit if choosen
 	if (isset($_POST['list_limit'])) {
 		$_SESSION['list_limit']=$_POST['list_limit'];
     		 $cfg_features["list_limit"]=$_POST['list_limit'];
	 }

	
	//  menu list 	
	$utils = array($lang["Menu"][17]=>array("/reservation/index.php","1"),
	$lang["Menu"][19]=>array("/knowbase/index.php"," "),
	$lang["Menu"][6]=>array("/reports/index.php"," "),
	);
	$inventory = array($lang["Menu"][0]=>array("/computers/index.php","c"),
		$lang["Menu"][3]=>array("/monitors/index.php","m"),
		$lang["Menu"][4]=>array("/software/index.php","s"),  
		$lang["Menu"][1]=>array("/networking/index.php","n"),
		$lang["Menu"][16]=>array("/peripherals/index.php","r"),
		$lang["Menu"][2]=>array("/printers/index.php","p"),
		$lang["Menu"][21]=>array("/cartridges/index.php","c"),
		$lang["Menu"][32]=>array("/consumables/index.php","g"),     
		$lang["Menu"][28]=>array("/state/index.php","s"),
		);
	$financial = array($lang["Menu"][22]=>array("/contacts/index.php","t"),
		$lang["Menu"][23]=>array("/enterprises/index.php","e"),
		$lang["Menu"][25]=>array("/contracts/index.php","n"),
		$lang["Menu"][27]=>array("/documents/index.php","d"),
	);
	$maintain =	array($lang["Menu"][5]=>array("/tracking/index.php","t"),
		$lang["Menu"][31]=>array("/helpdesk/index.php","h"),
		$lang["Menu"][29]=>array("/planning/index.php","l"),
		$lang["Menu"][13]=>array("/stats/index.php","1"));
			
	$config = array($lang["Menu"][14]=>array("/users/index.php","u"),
		$lang["Menu"][10]=>array("/setup/index.php","2"),
		$lang["Menu"][11]=>array("/preferences/index.php","p"),
		$lang["Menu"][12]=>array("/backups/index.php","b"),
		$lang["Menu"][30]=>array("/logs.php","l"),);

	// Send UTF8 Headers
	header("Content-Type: text/html; charset=UTF-8");
	// Send extra expires header if configured
	if ($cfg_features["sendexpire"]) {
		header("Expires: Fri, Jun 12 1981 08:20:00 GMT\nPragma: no-cache");
	}
	// Start the page
	echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">";
	echo "<html><head><title>GLPI - ".$title."</title>";
        echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8 \" >";
	// Send extra expires header if configured
	if ($cfg_features["sendexpire"]) {
		echo "<meta http-equiv=\"Expires\" content=\"Fri, Jun 12 1981 08:20:00 GMT\">\n";
		echo "<meta http-equiv=\"Pragma\" content=\"no-cache\">\n";
		echo "<meta http-equiv=\"Cache-Control\" content=\"no-cache\">\n";
	}
	//  CSS link
        echo "<link rel='stylesheet'  href='".$HTMLRel."styles.css' type='text/css' media='screen' >";
	echo "<link rel='stylesheet' type='text/css' media='print' href='".$HTMLRel."print.css' >";
	echo "<link rel='shortcut icon' type='images/x-icon' href='".$HTMLRel."pics/favicon.ico' >";
	// AJAX library
	echo "<script type=\"text/javascript\" src='".$HTMLRel."prototype.js'></script>";
	echo "<script type=\"text/javascript\" src='".$HTMLRel."scriptaculous/scriptaculous.js'></script>";
	// Some Javascript-Functions which we may need later
	echo "<script type=\"text/javascript\" src='".$HTMLRel."script.js'></script>";
	// End of Head
	echo "</head>\n";
	// Body 
	echo "<body>";
	
	// Main Headline
	echo "<div id='navigation' style='background : url(\"".$HTMLRel."pics/fond.png\") repeat-x top right ;'>";

	// New object from the configured base functions, we check some
	// object-variables in this object: inventory, maintain, admin
	// and settings. We build the navigation bar here.
	$navigation = new baseFunctions;
	
	//menu
	echo "<div id='menu'>";
	// Logo with link to command center
	
	echo "<dl><dt onmouseover=\"javascript:hidemenu();\"><a class='icon_logo' style='background: transparent' href=\"".$cfg_install["root"]."/central.php\" accesskey=\"0\"><img  src=\"".$HTMLRel."pics/logo-glpi.png\"  alt=\"".$cfg_layout["logotxt"]."\" title=\"".$lang["central"][5]."\"></a></dt></dl>";
		
	// Get object-variables and build the navigation-elements
	
	if ($navigation->inventory) {
		echo "<dl><dt onmouseover=\"javascript:montre('smenu1');\"><img class='icon_nav' src=\"".$HTMLRel."pics/inventaire.png\" alt=\"\" title=\"".$lang["setup"][10]."\"><br>\n";
		echo "<span class='menu_title'>-&nbsp;".$lang["setup"][10]."&nbsp;-</span><dt>\n";
		echo "<dd id=\"smenu1\"><ul>";
		$i=0;
		// list menu item 
		 foreach ($inventory as $key => $val) {
		 	echo "<li><span class='menu'><a  href=\"".$cfg_install["root"].$val[0]."\" accesskey=\"".$val[1]."\">".$key."</a></span></li>\n";
                         	$i++;
	        }
			
		echo "</ul></dd>\n";
		echo "</dl>\n";
	}

	if ($navigation->maintain) {
		
		echo "<dl><dt onmouseover=\"javascript:montre('smenu2');\"><img class='icon_nav' src=\"".$HTMLRel."pics/maintenance.png\" alt=\"\" title=\"".$lang["title"][24]."\"><br>\n";
		echo "<span class='menu_title'>-&nbsp;".$lang["title"][24]."&nbsp;-</span></dt>\n";
		echo "<dd id=\"smenu2\"><ul>";
		// list menu item 
		foreach ($maintain as $key => $val) {
			echo "<li><span class='menu'><a  href=\"".$cfg_install["root"].$val[0]."\" accesskey=\"".$val[1]."\">".$key."</a></span></li>\n";
		}
		echo "</ul></dd>\n";
		echo "</dl>\n";
	}
	if ($navigation->financial) {
		echo "<dl><dt onmouseover=\"javascript:montre('smenu3');\">";
		echo "<img class='icon_nav' src=\"".$HTMLRel."pics/gestion.png\" alt=\"\" title=\"".$lang["Menu"][26]."\"><br>\n";
		echo "<span class='menu_title'>-&nbsp;".$lang["Menu"][26]."&nbsp;-</span></dt>\n";
		echo "<dd id=\"smenu3\"><ul>";
		// list menu item 
		foreach ($financial as $key => $val) {
			echo "<li><span class='menu'><a  href=\"".$cfg_install["root"].$val[0]."\" accesskey=\"".$val[1]."\">".$key."</a></span></li>\n";
		}
			echo "</ul></dd>\n";
		echo "</dl>\n";
	}
	
	
	if ($navigation->utils) {
		echo "<dl><dt onmouseover=\"javascript:montre('smenu4');\">";
		echo "<img class='icon_nav' src=\"".$HTMLRel."pics/outils.png\" alt=\"\" title=\"".$lang["Menu"][18]."\"><br>\n";
		echo "<span class='menu_title'>-&nbsp;".$lang["Menu"][18]."&nbsp;-</span></dt>\n";
		echo "<dd id=\"smenu4\"><ul>";
		// list menu item 
		foreach ($utils as $key => $val) {
			echo "<li><span class='menu'><a  href=\"".$cfg_install["root"].$val[0]."\" accesskey=\"".$val[1]."\">".$key."</a></span></li>\n";
		}
		echo "</ul></dd>\n";
		echo "</dl>\n";
	}

	// PLUGINS
	$dirplug=$phproot."/plugins";
	$dh  = opendir($dirplug);
	while (false !== ($filename = readdir($dh))) {
   		if ($filename!="CVS"&&$filename!="."&&$filename!=".."&&is_dir($dirplug."/".$filename))
   		$plugins[]=$filename;
	}
	if (isset($plugins)&&count($plugins)>0){
		echo "<dl><dt onmouseover=\"javascript:montre('smenu5');\">";
		echo "<img class='icon_nav' src=\"".$HTMLRel."pics/plugins.png\" alt=\"\" title=\"".$lang["Menu"][15]."\"><br>\n";
		echo "<span class='menu_title'>-&nbsp;Plugins&nbsp;-</span></dt>\n";
		echo "<dd id=\"smenu5\"><ul>";
		// list menu item 
		foreach ($plugins as $key => $val) {
			echo "<li><span class='menu'><a  href=\"".$cfg_install["root"]."/plugins/".$val."/\" accesskey=\"".$val."\">".$val."</a></span></li>\n";
		}
			echo "</ul></dd>\n";
		echo "</dl>\n";
	}
	
	
	if ($navigation->settings) {
			echo "<dl><dt onmouseover=\"javascript:montre('smenu6');\">";
		echo "<img class='icon_nav' src=\"".$HTMLRel."pics/config.png\" alt=\"\" title=\"".$lang["Menu"][15]."\"><br>\n";
		echo "<span class='menu_title'>-&nbsp;".$lang["Menu"][15]."&nbsp;-</span></dt>\n";
		echo "<dd id=\"smenu6\"><ul>";
		// list menu item 
		foreach ($config as $key => $val) {
			echo "<li><span class='menu'><a  href=\"".$cfg_install["root"].$val[0]."\" accesskey=\"".$val[1]."\">".$key."</a></span></li>\n";
		}
		echo "</ul></dd>\n";
		echo "</dl>\n";
	}
	
		
	// Display  clock with date, help and a logout-link.
	//logout
	echo "<div  onmouseover=\"javascript:hidemenu();\" style='float:right; width:5%; margin-right:10px;'><a  class='icon_nav_move'  style='background: transparent'  href=\"".$cfg_install["root"]."/logout.php\"><img  class='icon_nav'  src=\"".$HTMLRel."pics/logout.png\" alt=\"".$lang["central"][6]."\" title=\"".$lang["central"][6]."\"></a></div>\n";

	//help
	echo "<div  onmouseover=\"javascript:hidemenu();\" style='float:right; width:5%;'><a class='icon_nav_move'  style='background: transparent'   href='#' onClick=\"window.open('".$HTMLRel."help/".$cfg_install["languages"][$_SESSION["glpilanguage"]][2]."','helpdesk','width=750,height=600,scrollbars=yes')\"><img class='icon_nav' src=\"".$HTMLRel."pics/help.png\" alt=\"\" title=\"".$lang["central"][7]."\"></a></div>\n";

	
	

	// End navigation bar


	// End headline
	echo "<hr class='separ'>";
	echo "</div>\n";

	//clock
	echo "<div style='font-size:9px; position:absolute; top:70px; right: 15px; text-align:center;'>";
	echo date("H").":".date("i")."&nbsp;<i>".date("j.")."&nbsp;".date("M")."&nbsp;".date("Y");
	echo "</i><span class='nav_horl'><b>";
	if (!empty($_SESSION["glpirealname"])) echo $_SESSION["glpirealname"];
	else echo $_SESSION["glpiname"];
	echo "</b></span></div>\n";

	echo "</div>";

	echo "<div onmouseover=\"javascript:hidemenu();\">";

	// Affichage du message apres redirection
	if (isset($_SESSION["MESSAGE_AFTER_REDIRECT"])&&!empty($_SESSION["MESSAGE_AFTER_REDIRECT"])){
		echo "<div align='center'><b>".$_SESSION["MESSAGE_AFTER_REDIRECT"]."</b></div>";
		$_SESSION["MESSAGE_AFTER_REDIRECT"]="";
		unset($_SESSION["MESSAGE_AFTER_REDIRECT"]);
	}
}

/**
* Print a nice HTML head for help page
*
*
* @param $title title of the page
* @param $url not used anymore.
* @param $name 
**/
function helpHeader($title,$url,$name) {
	// Print a nice HTML-head for help page

	GLOBAL $cfg_layout,$cfg_install,$lang,$cfg_features,$HTMLRel,$phproot ;

	// Send extra expires header if configured
	if ($cfg_features["sendexpire"]) {
		header("Expires: Fri, Jun 12 1981 08:20:00 GMT\nPragma: no-cache");
	}

	// Send UTF8 Headers
	header("Content-Type: text/html; charset=UTF-8");

	// Start the page
	echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">";
        echo "<html><head><title>GLPI Helpdesk - ".$title."</title>";
        echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8 \" >";
	echo "<link rel='shortcut icon' type='images/x-icon' href='".$HTMLRel."pics/favicon.ico' >";
	// Send extra expires header if configured

	if ($cfg_features["sendexpire"]) {
	        echo "<meta http-equiv=\"Expires\" content=\"Fri, Jun 12 1981 08:20:00 GMT\">\n";
		echo "<meta http-equiv=\"Pragma\" content=\"no-cache\">\n";
		echo "<meta http-equiv=\"Cache-Control\" content=\"no-cache\">\n";
	}

	// Some Javascript-Functions which we may need later
	
	echo "<script type=\"text/javascript\" src='".$HTMLRel."script.js'></script>";

	// AJAX library
	echo "<script type=\"text/javascript\" src='".$HTMLRel."prototype.js'></script>";
	echo "<script type=\"text/javascript\" src='".$HTMLRel."scriptaculous/scriptaculous.js'></script>";
	
		
	// Appel CSS
	
        echo "<link rel='stylesheet'  href='".$HTMLRel."styles.css' type='text/css' media='screen' >";
	echo "<link rel='stylesheet' type='text/css' media='print' href='".$HTMLRel."print.css' >";
	
	// End of Head
	echo "</head>\n";
	
	// Body 
	echo "<body>";

	// Main Headline
	echo "<div id='navigation-helpdesk' style='background : url(\"".$HTMLRel."pics/fond.png\") repeat-x top right ;'>";

	echo "<table cellspacing='0' border='0' width='98%'>";
	echo "<tr>";
	
	// Logo with link to command center
	echo "<td align='center' width='25%'>\n";
	
	echo "<img src=\"".$HTMLRel."pics/logo-glpi.png\"  alt=\"".$cfg_layout["logotxt"]."\" title=\"".$lang["central"][5]."\" >";
	echo "<div style='width:80px; text-align:center;'><p class='nav_horl'><b>";
	if (!empty($_SESSION["glpirealname"])) echo $_SESSION["glpirealname"];
	else echo $_SESSION["glpiname"];
	echo "</b></p></div>";
        echo "</td>";

	echo "<td valign='middle'>";

	echo "<table width='100%' cellspacing='0' cellpadding='0' border='0'><tr>";

	if (ereg("tracking-injector",$_SERVER["PHP_SELF"]))
	echo "<td width='100%'>&nbsp;</td>";
	// Just give him a language selector
	echo "<td>";
		if (!ereg("tracking-injector",$_SERVER["PHP_SELF"]))
		showLangSelect($cfg_install["root"]."/preferences/index.php",$name);
		else echo "&nbsp;";
	echo "</td>";

	// And he can change his password, thats it
	echo "<td>";
	if ($_SESSION["extauth"]!=1&&!ereg("tracking-injector",$_SERVER["PHP_SELF"]))
		showPasswordForm($cfg_install["root"]."/preferences/index.php",$name);
		else echo "&nbsp;";
	echo "</td>";
	// We tracking or post a new one
	echo "<td>";
        echo "<a class='icon_nav_move' href=\"".$cfg_install["root"]."/helpdesk.php\"><img  src=\"".$HTMLRel."pics/ajoutinterv.png\" alt=\"".$lang["job"][13]."\" title=\"".$lang["job"][13]."\"></a><br><br>";
        echo "<a class='icon_nav_move' href=\"".$cfg_install["root"]."/helpdesk.php?show=user\"><img  src=\"".$HTMLRel."pics/suivi.png\" alt=\"".$lang["tracking"][0]."\" title=\"".$lang["tracking"][0]."\"></a>";
	echo "</td>";
	//reservation
	
	echo "<td>";
        echo "<a  class='icon_nav_move' href=\"".$cfg_install["root"]."/helpdesk.php?show=resa\"><img  src=\"".$HTMLRel."pics/reservation-2.png\" alt=\"".$lang["Menu"][17]."\" title=\"".$lang["Menu"][17]."\"></a><br><br>";
        echo "<a class='icon_nav_move' href=\"".$cfg_install["root"]."/helpdesk.php?show=faq\"><img  src=\"".$HTMLRel."pics/faq-24.png\" alt=\"".$lang["knowbase"][1]."\" title=\"".$lang["knowbase"][1]."\"></a>";
	
	echo "</td>";
	// On the right side of the navigation bar, we have a clock with
	// date, help and a logout-link.
	echo "<td align='right' width='100'><div align='right'>";
	// HELP	
	echo "<a class='icon_nav_move'  href='#'
	 onClick=\"window.open('".$HTMLRel."help/".$cfg_install["languages"][$_SESSION["glpilanguage"]][3]."','helpdesk','width=400,height=600,scrollbars=yes')\"><img class='icon_nav' src=\"".$HTMLRel."pics/help.png\" alt=\"\" title=\"".$lang["central"][7]."\"></a>";
				
	echo "<p>".date("H").":".date("i")."<br><i>".date("j.")."&nbsp;".date("M")."&nbsp;".date("Y");
	echo "</i></p><a class='icon_nav_move' href=\"".$cfg_install["root"]."/logout.php\"><img class='icon_nav' src=\"".$HTMLRel."pics/logout.png\" alt=\"".$lang["central"][6]."\" title=\"".$lang["central"][6]."\"></a></div></td>";

	// End navigation bar
	
	echo "</tr></table>";
	
	// End headline

	echo "</td></tr>";	
	echo "</table>\n";
	echo "</div>";

	// Affichage du message apres redirection
	if (isset($_SESSION["MESSAGE_AFTER_REDIRECT"])&&!empty($_SESSION["MESSAGE_AFTER_REDIRECT"])){
		echo "<div align='center'><b>".$_SESSION["MESSAGE_AFTER_REDIRECT"]."</b></div>";
		$_SESSION["MESSAGE_AFTER_REDIRECT"]="";
		unset($_SESSION["MESSAGE_AFTER_REDIRECT"]);
	}

}

/**
* Print a nice HTML head with no controls
*
*
* @param $title title of the page
* @param $url not used anymore.
* @param $name 
**/
function nullHeader($title,$url) {
	// Print a nice HTML-head with no controls

	GLOBAL $cfg_layout,$cfg_install,$lang,$HTMLRel,$phproot ;
	// Send UTF8 Headers
	header("Content-Type: text/html; charset=UTF-8");

	// Send extra expires header if configured
	if (!empty($cfg_features["sendexpire"])) {
		header("Expires: Fri, Jun 12 1981 08:20:00 GMT\nPragma: no-cache");
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
	echo "<script type=\"text/javascript\" src='".$HTMLRel."prototype.js'></script>";
	echo "<script type=\"text/javascript\" src='".$HTMLRel."scriptaculous/scriptaculous.js'></script>";
	
	// Appel CSS
	
        echo "<link rel='stylesheet'  href='".$HTMLRel."styles.css' type='text/css' media='screen' >";

	
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
	echo "<a href=\"".$HTMLRel."index.php\"><img src=\"".$HTMLRel."pics/logo-glpi.png\" alt=\"".$cfg_layout["logotxt"]."\" title=\"\" ></a>\n";
	echo "</td>";


	// End navigation bar
	
	echo "</tr></table>";
	
	echo "</div>";
}

/**
* Print footer for every page
*
*
**/
function commonFooter() {
	// Print foot for every page

GLOBAL $lang,$cfg_features,$cfg_install,$cfg_debug,$DEBUG_SQL_STRING,$TIMER_DEBUG,$SQL_TOTAL_TIMER,$SQL_TOTAL_REQUEST;
echo "</div>";
echo "<div id='footer' >";
echo "<table width='100%'><tr><td align='left'><span class='copyright'>";
echo $TIMER_DEBUG->Get_Time()."s</span>";
echo "</td>";

if (!empty($cfg_features["founded_new_version"]))
	echo "<td alilgn='center'>".$lang["setup"][301]." ".$cfg_features["founded_new_version"]."<br>".$lang["setup"][302]."</td>";


echo "<td align='right'>";
echo "<a href=\"http://GLPI.indepnet.org/\">";
echo "<span class='copyright'>GLPI ".$cfg_install["version"]." Copyright (C) 2003-2005 by the INDEPNET Development Team.</span>";
echo "</a>";

echo "</td></tr>";
echo "</table></div>";

	if ($cfg_debug["active"]){
		
		echo "<div id='debug'>";
		echo "<h1 >GLPI MODE DEBUG</h1>";
		if ($cfg_debug["profile"]){
			echo "<h2 >TIME</h2>";
			echo $TIMER_DEBUG->Get_Time()."s";
		}
		if ($cfg_debug["vars"]){
			echo "<h2 >POST VARIABLE</h2>";
			foreach($_POST as $key => $val)
				echo $key." => ".$val."<br>";
			echo "<h2 >GET VARIABLE</h2>";
			foreach($_GET as $key => $val)
				echo $key." => ".$val."<br>";
			echo "<h2 >SESSION VARIABLE</h2>";
			foreach($_SESSION as $key => $val)
				echo $key." => ".$val."<br>";
		}
	
		if ($cfg_debug["sql"]){	
			echo "<h2>SQL REQUEST</h2>";
			echo "<p><b> Number of request:</b> ".$SQL_TOTAL_REQUEST."</p>";
			if ($cfg_debug["profile"]){
				echo "<p><b>Total Time:</b> ".$SQL_TOTAL_TIMER."s</p><hr>";
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
GLOBAL $cfg_install;
echo "<div id='footer'><div align='right'>";
	echo "<a href=\"http://GLPI.indepnet.org/\">";
	echo "<span class='copyright'>GLPI ".$cfg_install["version"]." Copyright (C) 2003-2005 by the INDEPNET Development Team.</span>";
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
GLOBAL $cfg_install;
echo "<div id='footer'><div align='right'>";
	echo "<a href=\"http://GLPI.indepnet.org/\">";
	echo "<span class='copyright'>GLPI ".(isset($cfg_install["version"])?$cfg_install["version"]:"")." Copyright (C) 2003-2005 by the INDEPNET Development Team.</span>";
	echo "</a>";
		echo "</div></div>";

	echo "</body></html>";
}


/**
* Print the helpdesk 
*
* @param $name
* @param $from_helpdesk
* @return nothing (print the helpdesk)
*/
function printHelpDesk ($name,$from_helpdesk) {

	GLOBAL $cfg_layout,$cfg_install,$lang,$cfg_features;

	$db = new DB;

	$query = "SELECT email,realname,name FROM glpi_users WHERE (name = '$name')";
	$result=$db->query($query);
	$email = $db->result($result,0,"email");
	$realname = $db->result($result,0,"realname");
	$name = $db->result($result,0,"name");

	// Get saved data from a back system
        $emailupdates = 'yes';
        $device_type = 0;
	$computer="";
	$contents="";
		
		if (isset($_SESSION["helpdeskSaved"]["emailupdates"]))
                $emailupdates = stripslashes($_SESSION["helpdeskSaved"]["emailupdates"]);
		if (isset($_SESSION["helpdeskSaved"]["email"]))
                $email = stripslashes($_SESSION["helpdeskSaved"]["uemail"]);
		if (isset($_SESSION["helpdeskSaved"]["device_type"]))
                $device_type = stripslashes($_SESSION["helpdeskSaved"]["device_type"]);
		if (isset($_SESSION["helpdeskSaved"]["contents"]))
                $contents = stripslashes($_SESSION["helpdeskSaved"]["contents"]);

	echo "<form method='post' name=\"helpdeskform\" action=\"".$cfg_install["root"]."/tracking/tracking-injector.php\"  enctype=\"multipart/form-data\">";
	echo "<input type='hidden' name='from_helpdesk' value='$from_helpdesk'>";

	echo "<div align='center'><table  class='tab_cadre'>";

	if ($realname!='') $name=$realname;

	echo "<tr><th colspan='2'>".$lang["help"][0]." $name, ".$lang["help"][1].":</th></tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<td>".$lang["help"][2].": </td>";
	echo "<td>";
	dropdownPriority("priority",3);
	echo "</td></tr>";
	if($cfg_features["mailing"] != 0)
	{
		echo "<tr class='tab_bg_1'>";
		echo "<td>".$lang["help"][8].":</td>";
		echo "<td>	<select name='emailupdates'>";
		echo "<option value='no' ".(($emailupdates=="no")?" selected":"").">".$lang["help"][9]."";
		echo "<option value='yes' ".(($emailupdates=="yes")?" selected":"").">".$lang["help"][10]."";
		echo "</select>";
		echo "</td></tr>";
		echo "<tr class='tab_bg_1'>";
		echo "<td>".$lang["help"][11].":</td>";
		echo "<td>	<input name='uemail' value=\"$email\" size='20'>";
		echo "</td></tr>";
	}

	echo "<tr class='tab_bg_1'>";
	echo "<td>".$lang["help"][24].": </td>";
	echo "<td>";
	dropdownTrackingDeviceType("device_type",$device_type);
	
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'>";
	echo "<td colspan='2' align='center'>".$lang["help"][13].":</td>";
	echo "</tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<td colspan='2' align='center'><textarea name='contents' cols='45' rows='14' >$contents</textarea>";
	echo "</td></tr>";

	$max_size=return_bytes_from_ini_vars(ini_get("upload_max_filesize"));
	$max_size/=1024*1024;
	$max_size=round($max_size,1);
	
	echo "<tr class='tab_bg_1'><td>".$lang["document"][2]." (".$max_size." Mb max):	</td>";
	echo "<td colspan='2'><input type='file' name='filename' value=\"\" size='25'></td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'>";
	echo "<td colspan='2' align='center'> <input type='submit' value=\"".$lang["help"][14]."\" class='submit'>";
	echo "<input type='hidden' name='IRMName' value=\"$name\">";
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
* @param $parameters paramters would be passed on the URL.S 
* @return nothing (print a pager)
*
*/
function printPager($start,$numrows,$target,$parameters) {

	GLOBAL $cfg_layout, $cfg_features, $lang, $HTMLRel;
	
	// Forward is the next step forward
	$forward = $start+$cfg_features["list_limit"];
	
	// This is the end, my friend	
	$end = $numrows-$cfg_features["list_limit"];

	// Human readable count starts here
	$current_start=$start+1;
			
	// And the human is viewing from start to end
	$current_end = $current_start+$cfg_features["list_limit"]-1;
	if ($current_end>$numrows) {
		$current_end = $numrows;
	}

	// Backward browsing 
	if ($current_start-$cfg_features["list_limit"]<=0) {
		$back=0;
	} else {
		$back=$start-$cfg_features["list_limit"];
	}

	// Print it
	echo "<form method='POST' action=\"$target?$parameters&amp;start=$start\">\n";

	echo "<div align='center'><table class='tab_cadre2' width='800'>\n";
	echo "<tr>\n";
	
	// Back and fast backward button
	if (!$start==0) {
		echo "<th align='left'>";
		echo "<a href=\"$target?$parameters&amp;start=0\">";
		echo "<img src=\"".$HTMLRel."pics/first.png\" alt='".$lang["buttons"][12]."' title='".$lang["buttons"][12]."'>";
		
		
		echo "</a></th>\n";
		echo "<th align='left'>";
		echo "<a href=\"$target?$parameters&amp;start=$back\">";
		echo "<img src=\"".$HTMLRel."pics/left.png\" alt='".$lang["buttons"][12]."' title='".$lang["buttons"][12]."'>";
		echo "</a></th>\n";
	}

	// Print the "where am I?" 
	echo "<td width='50%' align='center' class='tab_bg_2'><b>";
	echo $lang["pager"][4]."&nbsp;</b>";
	echo "<select name='list_limit' onChange='submit()'>";
	for ($i=5;$i<=200;$i+=5) echo "<option value='$i' ".((isset($_SESSION["list_limit"])&&$_SESSION["list_limit"]==$i)?" selected ":"").">$i</option>\n";
	echo "<option value='9999999' ".((isset($_SESSION["list_limit"])&&$_SESSION["list_limit"]==9999999)?" selected ":"").">9999999</option>\n";	
	echo "</select><b>&nbsp;";
	echo $lang["pager"][5];
	echo "</b></td>\n";

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
		echo "<img src=\"".$HTMLRel."pics/last.png\" alt='".$lang["buttons"][11]."' title='".$lang["buttons"][11]."'>";
		echo "</a></th>\n";
	}

	// End pager
	echo "</tr>\n";
	echo "</table><br></div>\n";
	echo "</form>\n";

}


/**
* To be commented
*
* @param $form
* @param $element
* @param $value
* @param $withtemplate
* @return nothing
*/
function showCalendarForm($form,$element,$value='',$withtemplate=''){
		global $HTMLRel,$lang;
		echo "<input type='text' name='____".$element."_show' readonly size='10' value=\"".convDate($value)."\">";
		echo "<input type='hidden' name='$element' size='10' value=\"".$value."\">";
		
		if ($withtemplate!=2){
			echo "&nbsp;<img src='".$HTMLRel."pics/calendar.png' class='calendrier' alt='".$lang["buttons"][15]."' title='".$lang["buttons"][15]."'
			onclick=\"window.open('".$HTMLRel."mycalendar.php?form=$form&amp;elem=$element&amp;value=$value','".$lang["buttons"][15]."','width=300,height=300')\" >";
		
			echo "&nbsp;<img src='".$HTMLRel."pics/reset.png' class='calendrier' onClick=\"document.forms['$form'].$element.value='0000-00-00';document.forms['$form'].____".$element."_show.value='".convDate("0000-00-00")."'\" alt='Reset' title='Reset'>";	
		}
}

/**
*  show notes for item
*
* @param $target
* @param $type
* @param $id
* @return nothing
*/
function showNotesForm($target,$type,$id){
global $HTMLRel,$lang;

//new objet
 $ci =new CommonItem;
//getfromdb
$ci->getfromDB ($type,$id);


echo "<form name='form' method='post' action=\"".$target."\">";
echo "<div align='center'>";
echo "<table width='800' class='tab_cadre' >";
echo "<tr><th align='center' >";
echo "Notes";
echo "</th></tr>";
echo "<tr><td valign='middle' align='center' ><textarea  cols='100' rows='35' name='notes' >".$ci->obj->fields["notes"]."</textarea></td></tr>";
echo "<tr><td class='tab_bg_2' align='center' >\n";
echo "<input type='hidden' name='ID' value=$id>";
echo "<input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'>";
echo "</td></tr>\n";
echo "</table></div></form>";
}

?>