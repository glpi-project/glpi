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
/**
* Test if an user have the right to assign a job to another user 
*
* Return true if the user with name $name is allowed to assign a job.
* Else return false.
*
*@param $name (username).
*@return boolean
*/
function can_assign_job($name)
{
  $db = new DB;
  $query = "SELECT * FROM glpi_users WHERE (name = '".$name."')";
	$result = $db->query($query);
	if (!$result&&$db->numrows()==0) return false;
	$type = $db->result($result, 0, "can_assign_job");
	if ($type == 'yes')
	{
	 return true;
	 }
	 else
	 {
	 return false;
	 }
}
/**
* Test if an user has the postonly rights or higher.
*
* Return true if the user with authentication type $authtype has
* the postonly rights.
*
*
*@param $authtype authentication type
*
*@return boolean
*
**/
function isPostOnly($authtype) {
	switch ($authtype){
		case "post-only" :
		case "normal" :
		case "admin":
		case "super-admin":
			return true;
			break;
		default :
			return false;
		}
}
/**
* Test if an user has the normal rights or higher.
*
* Return true if the user with authentication type $authtype has
* the normal rights.
*
*
*@param $authtype authentication type
*
*@return boolean
*
**/
function isNormal($authtype) {
	switch ($authtype){
		case "normal" :
		case "admin":
		case "super-admin":
			return true;
			break;
		default :
			return false;
		}
}

/**
* Test if an user has the admin rights or higher.
*
* Return true if the user with authentication type $authtype has
* the admin rights.
*
*
*@param $authtype authentication type
*
*@return boolean
*
**/
function isAdmin($authtype) {
	switch ($authtype){
		case "admin":
		case "super-admin":
			return true;
			break;
		default :
			return false;
		}
}
/**
* Test if an user has the super-admin rights or higher.
*
* Return true if the user with authentication type $authtype has
* the super-admin rights.
*
*
*@param $authtype authentication type
*
*@return boolean
*
**/
function isSuperAdmin($authtype) {
	switch ($authtype){
			case "super-admin":
			return true;
			break;
		default :
			return false;
		}
}
/**
* Make a "where" clause for a mysql query on user table
*
*
* Return a string witch contain the where clause, for a query 
* under the glpi_users table, witch return users that have the right $authtype.
* 
*
*@param : $authtype auth type
*@returns : string (in order to construct a SQL where clause)
**/
function searchUserbyType($authtype) {
	switch ($authtype){
		case "post-only" :
			return " 1=1 ";
			break;
		case "normal" :
			return " type ='super-admin' || type ='admin' || type ='normal'";
			break;
		case "admin":
			return " type ='super-admin' || type ='admin' ";
			break;
		case "super-admin":
			return " type ='super-admin' ";
			break;
		default :
			return "";
		}
}
/**
* To be commented
*
*
*
* @param $s
* @return 
*
*/

function getDictEntryfromDB($s){
GLOBAL $lang;
$a=split("_",$s);
return $lang[$a[0]][$a[1]];
}

/**
* To be commented
*
*
*
* @param $s
* @return 
*
*/
function stripslashes_deep($value) {
       $value = is_array($value) ?
                   array_map('stripslashes_deep', $value) :
                   (is_null($value) ? NULL : stripslashes($value));
                   
       return $value;
}

/**
* To be commented
*
*
*
* @param $value
* @return 
*
*/
function addslashes_deep($value) {
       $value = is_array($value) ?
                   array_map('addslashes_deep', $value) :
                   (is_null($value) ? NULL : addslashes($value));
       return $value;
}

/**
* To be commented
*
*
*
* @param $value
* @return 
*
*/
function htmlentities_deep($value){
       $value = is_array($value) ?
                   array_map('htmlentities_deep', $value) :
                   (is_null($value) ? NULL : htmlentities($value,ENT_QUOTES));
       return $value;
}

/**
* To be commented
* Nécessaire pour PHP < 4.3
*
*
* @param $value
* @return 
*
*/
function unhtmlentities ($string) {
	$trans_tbl = get_html_translation_table (HTML_ENTITIES,ENT_QUOTES);
	if( $trans_tbl["'"] != '&#039;' ) { # some versions of PHP match single quotes to &#39;
		$trans_tbl["'"] = '&#039;';
	}
	$trans_tbl = array_flip ($trans_tbl);
	return strtr ($string, $trans_tbl);
}

/**
* To be commented
* Nécessaire pour PHP < 4.3
*
*
* @param $value
* @return 
*
*/
function unhtmlentities_deep($value) {
	$value = is_array($value) ?
		array_map('unhtmlentities_deep', $value) :
			(is_null($value) ? NULL : unhtmlentities($value,ENT_QUOTES));
	return $value;
}

/**
* Verify if the current user has some rights
*
* Do nothing if the current user (wich session call this func) has 
* rights egal or higher as $authtype.
* 
* @param $authtype min level right we wish to allow
* @Return Nothing (display function)
*
**/      
function checkAuthentication($authtype) {
	// Universal method to have a magic-quote-gpc system
	global $_POST, $_GET,$_COOKIE,$tab;
	// Clean array and addslashes
	
	if (get_magic_quotes_gpc()) {
		if (isset($_POST)){
			$_POST = array_map('stripslashes_deep', $_POST);
		}
		if (isset($_GET)){
			$_GET = array_map('stripslashes_deep', $_GET);
		}
		if (isset($tab)){
			$tab = array_map('stripslashes_deep', $tab);    
		}
	}    
	if (isset($_POST)){
		$_POST = array_map('addslashes_deep', $_POST);
	}
	if (isset($_GET)){
		$_GET = array_map('addslashes_deep', $_GET);
	}
	if (isset($tab)){
		$tab = array_map('addslashes_deep', $tab);    
	}

	// Checks a GLOBAL user and password against the database
	// If $authtype is "normal" or "admin", it checks if the user
	// has the privileges to do something. Should be used in every 
	// control-page to set a minium security level.
	
	
	
	//if(!isset($_SESSION)) session_start();
	if(!session_id()){@session_start();}
	
	
	GLOBAL $cfg_install, $lang, $HTMLRel;

	if(empty($_SESSION["authorisation"]))
	{
		nullHeader("Login",$_SERVER["PHP_SELF"]);
		echo "<div align='center'><b><a href=\"".$cfg_install["root"]."/logout.php\">Relogin</a></b></div>";
		nullFooter();
		die();	
	}

	
	// New database object
	loadLanguage();
	$type = $_SESSION["glpitype"];	
	// Check username and password
	if (!isset($_SESSION["glpiname"])) {
		header("Vary: User-Agent");
		nullHeader($lang["login"][3], $_SERVER["PHP_SELF"]);
		echo "<center><b>".$lang["login"][0]."</b><br><br>";
		echo "<b><a href=\"".$cfg_install["root"]."/logout.php\">".$lang["login"][1]."</a></b></center>";
		nullFooter();
		exit();
	} else {
		header("Vary: User-Agent");

		loadLanguage();

		switch ($authtype) {
			case "super-admin";
				if (!isSuperAdmin($type)) 
				{
					commonHeader($lang["login"][5],$_SERVER["PHP_SELF"]);
					echo "<center><br><br><img src=\"".$HTMLRel."pics/warning.png\" alt=\"warning\"><br><br>";
					echo "<b>".$lang["login"][5]."</b></center>";
					commonFooter();
					exit();
				}
			break;
				
			case "admin";
				if (!isAdmin($type)) 
				{
					commonHeader($lang["login"][5],$_SERVER["PHP_SELF"]);
					echo "<center><br><br><img src=\"".$HTMLRel."pics/warning.png\" alt=\"warning\"><br><br>";
					echo "<b>".$lang["login"][5]."</b></center>";
					commonFooter();
					exit();
				}
			break;
				
			case "normal";
				if (!isNormal($type))
				{
					commonHeader($lang["login"][5],$_SERVER["PHP_SELF"]);
					echo "<center><br><br><img src=\"".$HTMLRel."pics/warning.png\" alt=\"warning\"><br><br>";
					echo "<b>".$lang["login"][5]."</b></center>";
					commonFooter();
					exit();
				}
			break;
		
			case "post-only";
				if (!isPostOnly($type)) {
					commonHeader($lang["login"][5],$_SERVER["PHP_SELF"]);
					echo "<center><br><br><img src=\"".$HTMLRel."pics/warning.png\" alt=\"warning\"><br><br>";
					echo "<b>".$lang["login"][5]."</b></center>";
					commonFooter();
					exit();
				}
			break;
		}
	}
}
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
	
	
$utils = array($lang["Menu"][17]=>array("/reservation/index.php","1"),
		$lang["Menu"][19]=>array("/knowbase/index.php"," "),
		$lang["Menu"][6]=>array("/reports/index.php"," "),
		);
	
$inventory = array($lang["Menu"][0]=>array("/computers/index.php","1"),
	              $lang["Menu"][1]=>array("/networking/index.php","2"),
	              $lang["Menu"][2]=>array("/printers/index.php","3"),
	              $lang["Menu"][3]=>array("/monitors/index.php","4"),
	              $lang["Menu"][4]=>array("/software/index.php","5"),
		      $lang["Menu"][16]=>array("/peripherals/index.php","6"),
		      $lang["Menu"][21]=>array("/cartridges/index.php","7"),
		      $lang["Menu"][28]=>array("/repair/index.php","8"),
		      );

$financial = array($lang["Menu"][22]=>array("/contacts/index.php","1"),
		$lang["Menu"][23]=>array("/enterprises/index.php"," "),
		$lang["Menu"][25]=>array("/contracts/index.php"," "),
		$lang["Menu"][27]=>array("/documents/index.php"," "),
		);

$maintain =	array($lang["Menu"][5]=>array("/tracking/index.php","6"),
		"Helpdesk"=>array("/helpdesk/index.php"," "),
		$lang["Menu"][13]=>array("/stats/index.php"," "));

				
$config = array($lang["Menu"][14]=>array("/setup/setup-users.php"," "),
		$lang["Menu"][10]=>array("/setup/index.php"," "),
		$lang["Menu"][11]=>array("/preferences/index.php"," "),
		$lang["Menu"][12]=>array("/backups/index.php"," "));
	// Send extra expires header if configured
	if ($cfg_features["sendexpire"]) {
		header("Expires: Fri, Jun 12 1981 08:20:00 GMT\nPragma: no-cache");
	}
	// Start the page
	echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">";
	echo "<html><head><title>GLPI - ".$title."</title>";
        echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1 \" >";
	// Send extra expires header if configured
	if ($cfg_features["sendexpire"]) {
		echo "<meta http-equiv=\"Expires\" content=\"Fri, Jun 12 1981 08:20:00 GMT\">\n";
		echo "<meta http-equiv=\"Pragma\" content=\"no-cache\">\n";
		echo "<meta http-equiv=\"Cache-Control\" content=\"no-cache\">\n";
	}
	//  Appel  CSS
	
        echo "<link rel='stylesheet'  href='".$HTMLRel."styles.css' type='text/css' media='screen' >";
	echo "<link rel='stylesheet' type='text/css' media='print' href='".$HTMLRel."print.css' >";
	echo "<link rel='shortcut icon' type='images/x-icon' href='".$HTMLRel."pics/favicon.ico' >";

	// Some Javascript-Functions which we may need later
	
	echo "<script type=\"text/javascript\" src='".$HTMLRel."script.js'></script>";
	// End of Head
	echo "</head>\n";
	// Body with configured stuff
	echo "<body>";
	// Main Headline
	echo "<div id='navigation' style='background : url(\"".$HTMLRel."pics/fond.png\") repeat-x top right ;'>";
	echo "<table  cellspacing='0' border='0' width='100%' >";
	echo "<tr>";
	// New object from the configured base functions, we check some
	// object-variables in this object: inventory, maintain, admin
	// and settings. We build the navigation bar here.
	$navigation = new baseFunctions;
	
	// Logo with link to command center
	echo "<td width='80px'  valign='middle' align='center' >\n";
	echo "<a class='icon_logo' href=\"".$cfg_install["root"]."/central.php\" accesskey=\"0\"><img  src=\"".$HTMLRel."pics/logo-glpi.png\"  alt=\"".$cfg_layout["logotxt"]."\" title=\"".$lang["central"][5]."\"></a>";
	echo "<br><br><div style='width:80px; text-align:center;'><p class='nav_horl'><b>".$_SESSION["glpiname"]."</b></p></div>";
	echo "</td>";
	echo "<td valign='middle' style='padding-left:20px'>";
	
	// Get object-variables and build the navigation-elements
	echo "<table width='100%' cellspacing='0' cellpadding='0' border='0'><tr>";
	if ($navigation->inventory) {
		echo "<td align='center' valign='top' width='20%'>";
		echo "<img class='icon_nav' src=\"".$HTMLRel."pics/inventaire.png\" alt=\"\" title=\"".$lang["setup"][10]."\"><br>";
		echo "<span class='menu_title'>-&nbsp;".$lang["setup"][10]."&nbsp;-</span><br>";

		 echo "<table cellspacing='0' border='0' cellpadding='0'><tr><td>";
		$i=0;
		 foreach ($inventory as $key => $val) {
		 			if ($i%2==1) echo "</td><td style='border-left:1px groove #000000; border-right:1px groove #000000'>&nbsp;</td><td style='padding-left:5px; padding-right:5px;' align='center'>";
		 			else echo "</td></tr><tr><td style='padding-left:5px; padding-right:5px;' align='center'>";
                         
			 echo "<span class='menu'><a  href=\"".$cfg_install["root"].$val[0]."\" accesskey=\"".$val[1]."\">".$key."</a></span><br>";
                         $i++;
                   }
		echo "</td></tr></table>";
		echo "</td>";
	}
	if ($navigation->maintain) {
		echo "<td align='center' valign='top' width='20%'>";
		echo "<img class='icon_nav' src=\"".$HTMLRel."pics/maintenance.png\" alt=\"\" title=\"".$lang["title"][24]."\"><br>";

		echo "<span class='menu_title'>-&nbsp;".$lang["title"][24]."&nbsp;-</span><br>";
		foreach ($maintain as $key => $val) {
			echo "<span class='menu'><a  href=\"".$cfg_install["root"].$val[0]."\" accesskey=\"".$val[1]."\">".$key."</a></span><br>";
		}
		echo "</td>";
	}
	if ($navigation->financial) {
		echo "<td align='center' valign='top' width='20%'>";
		echo "<img class='icon_nav' src=\"".$HTMLRel."pics/gestion.png\" alt=\"\" title=\"".$lang["setup"][55]."\"><br>";

		echo "<span class='menu_title'>-&nbsp;".$lang["Menu"][26]."&nbsp;-</span><br>";
		foreach ($financial as $key => $val) {
			echo "<span class='menu'><a  href=\"".$cfg_install["root"].$val[0]."\" accesskey=\"".$val[1]."\">".$key."</a></span><br>";
		}
		echo "</td>";
	}
	
	
	if ($navigation->utils) {
		echo "<td align='center' valign='top' width='20%'>";
		echo "<img class='icon_nav' src=\"".$HTMLRel."pics/outils.png\" alt=\"\" title=\"".$lang["Menu"][15]."\"><br>";
		echo "<span class='menu_title'>-&nbsp;".$lang["Menu"][18]."&nbsp;-</span><br>";
		foreach ($utils as $key => $val) {
			echo "<span class='menu'><a  href=\"".$cfg_install["root"].$val[0]."\" accesskey=\"".$val[1]."\">".$key."</a></span><br>";
		}
	}
	
	
	if ($navigation->settings) {
		echo "<td align='center' valign='top' width='20%'>";
		echo "<img class='icon_nav' src=\"".$HTMLRel."pics/config.png\" alt=\"\" title=\"".$lang["Menu"][15]."\"><br>";
		echo "<span class='menu_title'>-&nbsp;".$lang["Menu"][15]."&nbsp;-</span><br>";
		foreach ($config as $key => $val) {
			echo "<span class='menu'><a  href=\"".$cfg_install["root"].$val[0]."\" accesskey=\"".$val[1]."\">".$key."</a></span><br>";
		}
		echo "</td>";
	}
	
	
	
	
	// On the right side of the navigation bar, we have a clock with
	// date, help and a logout-link.

	echo "<td  align='center' valign='top' width='100px'>";
	//help
	echo "<a class='icon_nav_move'  href='#' onClick=\"window.open('".$HTMLRel."help/".$cfg_install["languages"][$_SESSION["glpilanguage"]][2]."','helpdesk','width=700,height=600,scrollbars=yes')\"><img class='icon_nav' src=\"".$HTMLRel."pics/help.png\" alt=\"\" title=\"".$lang["central"][7]."\"></a>";
	echo "<p>".date("H").":".date("i")."<br><i>".date("j.")."&nbsp;".date("M")."&nbsp;".date("Y");
	echo "</i></p>";
	echo "<a  class='icon_nav_move' href=\"".$cfg_install["root"]."/logout.php\"><img  src=\"".$HTMLRel."pics/logout.png\" alt=\"".$lang["central"][6]."\" title=\"".$lang["central"][6]."\"></a>";
	
	echo "</td>";

	// End navigation bar

	echo "</tr></table>";

	// End headline

	
	echo "</td></tr>";	
	echo "</table>\n";
				echo "</div>";
	// Affichage du message apres redirection
	if (isset($_SESSION["MESSAGE_AFTER_REDIRECT"])&&!empty($_SESSION["MESSAGE_AFTER_REDIRECT"])){
		echo "<center><b>".$_SESSION["MESSAGE_AFTER_REDIRECT"]."</b></center>";
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

	// Start the page
	echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">";
        echo "<html><head><title>GLPI Helpdesk - ".$title."</title>";
        echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1 \" >";
	echo "<link rel='shortcut icon' type='images/x-icon' href='".$HTMLRel."pics/favicon.ico' />";
	// Send extra expires header if configured
	if ($cfg_features["sendexpire"]) {
	        echo "<meta http-equiv=\"Expires\" content=\"Fri, Jun 12 1981 08:20:00 GMT\">\n";
		echo "<meta http-equiv=\"Pragma\" content=\"no-cache\">\n";
		echo "<meta http-equiv=\"Cache-Control\" content=\"no-cache\">\n";
	}

	// Some Javascript-Functions which we may need later
	
	echo "<script type=\"text/javascript\" src='".$HTMLRel."script.js'></script>";
	
	// Appel CSS
	
        echo "<link rel='stylesheet'  href='".$HTMLRel."styles.css' type='text/css' media='screen' >";
	echo "<link rel='stylesheet' type='text/css' media='print' href='".$HTMLRel."print.css' >";
	
	// End of Head
	echo "</head>\n";
	
	// Body with configured stuff
	echo "<body>";

	// Main Headline
				echo "<div id='navigation' style='background : url(\"".$HTMLRel."pics/fond.png\") repeat-x top right ;'>";

	echo "<table cellspacing='0' border='0' width='98%'>";
	echo "<tr>";
	
	// Logo with link to command center
	echo "<td align='center' width='25%'>\n";
	
	echo "<img src=\"".$HTMLRel."pics/logo-glpi.png\"  alt=\"".$cfg_layout["logotxt"]."\" title=\"".$lang["central"][5]."\" >";
	echo "<div style='width:80px; text-align:center;'><p class='nav_horl'><b>".$_SESSION["glpiname"]."</b></p></div>";
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

	// Send extra expires header if configured
	if (!empty($cfg_features["sendexpire"])) {
		header("Expires: Fri, Jun 12 1981 08:20:00 GMT\nPragma: no-cache");
	}

	// Start the page
       	echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">";
        echo "<html><head><title>GLPI - ".$title."</title>";
        echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1 \" >";
	echo "<link rel='shortcut icon' type='images/x-icon' href='".$HTMLRel."pics/favicon.ico' />";
	// Send extra expires header if configured
	if (!empty($cft_features["sendexpire"])) {
		echo "<meta http-equiv=\"Expires\" content=\"Fri, Jun 12 1981 08:20:00 GMT\">\n";
		echo "<meta http-equiv=\"Pragma\" content=\"no-cache\">\n";
		echo "<meta http-equiv=\"Cache-Control\" content=\"no-cache\">\n";
	}

	// Some Javascript-Functions which we may need later
	
	echo "<script type=\"text/javascript\" src='".$HTMLRel."script.js'></script>";
	
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
	echo "<a href=\"".$cfg_install["root"]."/index.php\"><img src=\"".$HTMLRel."pics/logo-glpi.png\" alt=\"".$cfg_layout["logotxt"]."\" title=\"\" ></a>\n";
	echo "</td>";


	// End navigation bar
	
	echo "</tr></table>";
	
	// End headline

	echo "</td></tr></form>";	
	echo "</table>\n";
				echo "</div>";
}

/**
* Print footer for every page
*
*
**/
function commonFooter() {
	// Print foot for every page

GLOBAL $cfg_install;
echo "<div id='footer' ><div align='right'>";
	echo "<a href=\"http://GLPI.indepnet.org/\">";
	echo "<span class='copyright'>GLPI ".$cfg_install["version"]." Copyright (C) 2003-2005 by the INDEPNET Development Team.</span>";
	echo "</a></div>";
	echo "</div>";
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
	echo "<span class='copyright'>GLPI ".$cfg_install["version"]." Copyright (C) 2003-2005 by the INDEPNET Development Team.</span>";
	echo "</a>";
		echo "</div></div>";

	echo "</body></html>";
}

/**
* Log an event.
*
* Log the event $event on the glpi_event table with all the others args, if
* $level is above or equal to setting from configuration.
*
* @param $item 
* @param $itemtype
* @param $level
* @param $service
* @param $event
**/
function logEvent ($item, $itemtype, $level, $service, $event) {
	// Logs the event if level is above or equal to setting from configuration

	GLOBAL $cfg_features;
	if ($level <= $cfg_features["event_loglevel"]) { 
		$db = new DB;	
		$query = "INSERT INTO glpi_event_log VALUES (NULL, $item, '$itemtype', NOW(), '$service', $level, '$event')";
		$result = $db->query($query);    
	}
}

/**
* Print a nice tab for last event
*
* Print a great tab to present lasts events occured on glpi
*
*
* @param $target where to go when complete
* @param $order order by clause occurences (eg: ) 
* @param $sort order by clause occurences (eg: date) 
**/
function showEvents($target,$order,$sort) {
	// Show events from $result in table form

	GLOBAL $cfg_layout, $cfg_install, $cfg_features, $lang, $HTMLRel;

	// new database object
	$db = new DB;

	// define default sorting
	
	if (!$sort) {
		$sort = "date";
		$order = "DESC";
	}
	
	// Query Database
	$query = "SELECT * FROM glpi_event_log ORDER BY $sort $order LIMIT 0,".$cfg_features["num_of_events"];

	// Get results
	$result = $db->query($query);
	
	
	// Number of results
	$number = $db->numrows($result);

	// No Events in database
	if ($number < 1) {
		echo "<b>".$lang["central"][4]."</b>";
		return;
	}
	
	// Output events
	$i = 0;

	echo "<p><center><table width='90%' class='tab_cadre'>";
	echo "<tr><th colspan='6'>".$lang["central"][2]." ".$cfg_features["num_of_events"]." ".$lang["central"][3].":</th></tr>";
	echo "<tr>";

	echo "<th colspan='2'>";
	if ($sort=="item") {
		echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=item&order=ASC\">".$lang["event"][0]."</a></th>";

	echo "<th>";
	if ($sort=="date") {
		echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=date&order=DESC\">".$lang["event"][1]."</a></th>";

	echo "<th width='8%'>";
	if ($sort=="service") {
		echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=service&order=ASC\">".$lang["event"][2]."</a></th>";

	echo "<th width='8%'>";
	if ($sort=="level") {
		echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=level&order=DESC\">".$lang["event"][3]."</a></th>";

	echo "<th width='60%'>";
	if ($sort=="message") {
		echo "<img src=\"".$HTMLRel."pics/puce-down.gif\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=message&order=ASC\">".$lang["event"][4]."</a></th></tr>";

	while ($i < $number) {
		$ID = $db->result($result, $i, "ID");
		$item = $db->result($result, $i, "item");
		$itemtype = $db->result($result, $i, "itemtype");
		$date = $db->result($result, $i, "date");
		$service = $db->result($result, $i, "service");
		$level = $db->result($result, $i, "level");
		$message = $db->result($result, $i, "message");
		
		echo "<tr class='tab_bg_2'>";
		echo "<td>$itemtype:</td><td align='center'><b>";
		if ($item=="-1" || $item=="0") {
			echo $item;
		} else {
				if ($itemtype=="reservation"){
				echo "<a href=\"".$cfg_install["root"]."/$itemtype/index.php?show=resa&ID=";
				} else {
				echo "<a href=\"".$cfg_install["root"]."/$itemtype/".$itemtype."-info-form.php?ID=";
				}
			echo $item;
			echo "\">$item</a>";
		}			
		echo "</b></td><td>$date</td><td align='center'>$service</td><td align='center'>$level</td><td>$message</td>";
		echo "</tr>";

		$i++; 
	}

	echo "</table></center><br>";
}


/**
* Print out an HTML "<select>" for a dropdown
*
* 
* 
*
* @param $table the dropdown table from witch we want values on the select
* @param $myname the name of the HTML select
* @return nothing (display the select box)
**/
function dropdown($table,$myname) {
	
	global $deleted_tables;	
	
	// Make a select box
	$db = new DB;
	if($table == "glpi_dropdown_locations" || $table == "glpi_dropdown_kbcategories") {
	echo "<select name=\"$myname\">";
	showTreeListSelect($table,-1, 0);
	echo "</select>";
	}
	else if($table == "glpi_dropdown_netpoint") {
		$query = "select t1.ID as ID, t1.name as netpname, t2.name as locname from glpi_dropdown_netpoint as t1";
		$query .= " left join glpi_dropdown_locations as t2 on t1.location = t2.ID";
		$query .= " order by t2.name, t1.name"; 
		$result = $db->query($query);
		echo "<select name=\"$myname\">";
		$i = 0;
		$number = $db->numrows($result);
		if ($number > 0) {
			while ($i < $number) {
				$output = $db->result($result, $i, "netpname");
				$loc = $db->result($result, $i, "locname");
				$ID = $db->result($result, $i, "ID");
				echo "<option value=\"$ID\">$output ($loc)</option>";
				$i++;
			}
		}
		echo "</select>";
	}
 else {
		$where="";
		if (in_array($table,$deleted_tables))
			$where="WHERE deleted='N'";
			
		$query = "SELECT * FROM $table $where ORDER BY name";
		$result = $db->query($query);
		echo "<select name=\"$myname\" size='1'>";
		$i = 0;
		$number = $db->numrows($result);
		if ($number > 0) {
			while ($i < $number) {
				$output = $db->result($result, $i, "name");
				$ID = $db->result($result, $i, "ID");
				echo "<option value=\"$ID\">$output</option>";
				$i++;
			}
		}
		echo "</select>";
	}
}

/**
* Print out an HTML "<select>" for a dropdown with preselected value
*
*
*
*
*
* @param $table the dropdown table from witch we want values on the select
* @param $myname the name of the HTML select
* @param $value the preselected value we want
* @return nothing (display the select box)
*
*/
function dropdownValue($table,$myname,$value) {
	
	global $deleted_tables,$template_tables;
	
	// Make a select box with preselected values
	$db = new DB;
	if ($table == "glpi_dropdown_locations" || $table=="glpi_dropdown_kbcategories"){
	echo "<select name=\"$myname\">";
	echo "<option value=\"0\">-----</option>";
	showTreeListSelect($table,$value, 0);
	echo "</select>";
	}
	else if($table == "glpi_dropdown_netpoint") {
		$query = "select t1.ID as ID, t1.name as netpname, t2.ID as locID from glpi_dropdown_netpoint as t1";
		$query .= " left join glpi_dropdown_locations as t2 on t1.location = t2.ID";
		$query .= " order by t1.name,t2.name "; 
		$result = $db->query($query);
		echo "<select name=\"$myname\">";
		$i = 0;
		$number = $db->numrows($result);
		if ($number > 0) {
			while ($i < $number) {
				$output = $db->result($result, $i, "netpname");
				$loc = getTreeValueName("glpi_dropdown_locations",$db->result($result, $i, "locID"));
				$ID = $db->result($result, $i, "ID");
				echo "<option value=\"$ID\"";
				if ($ID==$value) echo " selected ";
				echo ">$output ($loc)</option>";
				$i++;
			}
		}
		echo "</select>";
	}	else {

	$where="WHERE '1'='1' ";
	if (in_array($table,$deleted_tables))
		$where.="AND deleted='N'";
	if (in_array($table,$template_tables))
		$where.="AND is_template='0'";
		
	$query = "SELECT * FROM $table $where ORDER BY name";
	
	$result = $db->query($query);
	
	echo "<select name=\"$myname\" size='1'>";
	echo "<option value=\"0\">-----</option>";
	$i = 0;
	$number = $db->numrows($result);
	if ($number > 0) {
		while ($i < $number) {
			$output = $db->result($result, $i, "name");
			$ID = $db->result($result, $i, "ID");
			if ($ID === $value) {
				echo "<option value=\"$ID\" selected>$output</option>";
				
			} else {
				echo "<option value=\"$ID\">$output</option>";
			}
			$i++;
		}
	}
	echo "</select>";
	
	if ($table=="glpi_enterprises")	{
	echo getEnterpriseLinks($value);
	}

	}
}



/**
* To be commented
*
*
*
*
*
* 
*/
function dropdownNoValue($table,$myname,$value) {
	// Make a select box without parameters value

	global $deleted_tables;

	$db = new DB;

	$where="";
	if (in_array($table,$deleted_tables))
		$where="WHERE deleted='N'";

	$query = "SELECT * FROM $table $where ORDER BY name";
	$result = $db->query($query);
	
	echo "<select name=\"$myname\" size='1'>";
	$i = 0;
	$number = $db->numrows($result);
	if ($number > 0) {
		while ($i < $number) {
			$output = $db->result($result, $i, "name");
			$ID = $db->result($result, $i, "ID");
			if ($ID === $value) {
			} else {
				echo "<option value=\"$ID\">$output</option>";
			}
			$i++;
		}
	}
	echo "</select>";
}

/**
* Make a select box with preselected values for table dropdown_netpoint
*
*
*
*
*
* @param $search
* @param $myname
* @param $location
* @param $value
* @return nothing (print out an HTML select box)
*/
function NetpointLocationSearch($search,$myname,$location,$value='') {
// Make a select box with preselected values for table dropdown_netpoint
	$db = new DB;
	
	$query = "SELECT t1.ID as ID, t1.name as netpointname, t2.name as locname, t2.ID as locID
	FROM glpi_dropdown_netpoint AS t1
	LEFT JOIN glpi_dropdown_locations AS t2
	ON t1.location = t2.ID
	WHERE (";
	if ($location!="")
		$query.= " t2.ID = '". $location ."' AND "; 
	$query.=" (t2.name LIKE '%". $search ."%'
	OR t1.name LIKE '%". $search ."%'))";
	if ($value!="")
		$query.=" OR t1.ID = '$value' ";
	$query.=" ORDER BY t1.name, t2.name";
	$result = $db->query($query);

	if ($db->numrows($result) == 0) {
		$query = "SELECT t1.ID as ID, t1.name as netpointname, t2.name as locname, t2.ID as locID
			FROM glpi_dropdown_netpoint AS t1
			LEFT JOIN glpi_dropdown_locations AS t2 ON t1.location = t2.ID
			ORDER BY t1.name, t2.name";
		$result = $db->query($query);
	}
	
	
	echo "<select name=\"$myname\" size='1'>";
	echo "<option value=\"0\">---</option>";
	
	if($db->numrows($result) > 0) {
		while($line = $db->fetch_array($result)) {
			echo "<option value=\"". $line["ID"] ."\" ";
			if ($value==$line["ID"]) echo " selected ";
			echo ">". $line["netpointname"]." (".getTreeValueName("glpi_dropdown_locations",$line["locID"]) .")</option>";
		}
	}
	echo "</select>";
}
/**
*  Make a select box with preselected values and search option
*
*
*
* @param $table
* @param $myname
* @param $value
* @param $search
* @return nothing (print out an HTML select box)
*
*
*/
function dropdownValueSearch($table,$myname,$value,$search) {
	// Make a select box with preselected values
	global $deleted_tables;
	$db = new DB;

	$where="";
	if (in_array($table,$deleted_tables))
		$where="AND deleted='N'";

	$query = "SELECT * FROM $table WHERE name LIKE '%$search%' $where ORDER BY name";
	$result = $db->query($query);

	
	$number = $db->numrows($result);
	if ($number == 0) {
		$query = "SELECT * FROM $table ORDER BY name";		
		$result = $db->query($query);
		$number = $db->numrows($result);
		}

	echo "<select name=\"$myname\" size='1'>";

	if ($number > 0) {
		$i = 0;		
		while ($i < $number) {
			if ($table=="glpi_software")
			$output = $db->result($result, $i, "name")." ".$db->result($result, $i, "version");
			else
			$output = $db->result($result, $i, "name");
			$ID = $db->result($result, $i, "ID");

			if ($ID == $value) {
				echo "<option value=\"$ID\" selected>$output</option>";
			} else {
				echo "<option value=\"$ID\">$output</option>";
			}
			$i++;
		}
	}
	echo "</select>";
}


/**
* Make a select box with all glpi users where select key = name
*
* Think it's unused now.
*
*
* @param $value
* @param $myname
* @return nothing (print out an HTML select box)
*
*
*/
function dropdownUsers($value, $myname) {
	// Make a select box with all glpi users

	$db = new DB;
	$query = "SELECT * FROM glpi_users WHERE (".searchUserbyType("normal").") ORDER BY name";
	$result = $db->query($query);

	echo "<select name=\"$myname\">";
	$i = 0;
	
	$number = $db->numrows($result);
	echo "<option value=\"\">[ Nobody ]</option>";
	if ($number > 0) {
		while ($i < $number) {
			$output = unhtmlentities($db->result($result, $i, "name"));
			if ($output == $value) {
				echo "<option value=\"$output\" selected>".$output;
			} else {
				echo "<option value=\"$output\">".$output;
			}
			$i++;
			echo "</option>";
   		}
	}
	echo "</select>";
}


/**
* Make a select box with all glpi users where select key = ID
*
*
*
* @param $value
* @param $myname
* @return nothing (print out an HTML select box)
*/
function dropdownUsersID($value, $myname) {
	// Make a select box with all glpi users

	$db = new DB;
	$query = "SELECT * FROM glpi_users WHERE (".searchUserbyType("normal").") ORDER BY name";
	$result = $db->query($query);

	echo "<select name=\"$myname\">";
	$i = 0;
	
	$number = $db->numrows($result);
	echo "<option value=\"\">[ Nobody ]</option>";
	if ($number > 0) {
		while ($i < $number) {
			$ID = $db->result($result, $i, "ID");
			$output = unhtmlentities($db->result($result, $i, "name"));
			if ($ID == $value) {
				echo "<option value=\"$ID\" selected>".$output;
			} else {
				echo "<option value=\"$ID\">".$output;
			}
			$i++;
			echo "</option>";
   		}
	}
	echo "</select>";
}

/**
* Get the value of a dropdown 
*
*
* Returns the value of the dropdown from $table with ID $id.
*
* @param $table
* @param $id
* @return string the value of the dropdown or "" (\0) if not exists
*/
function getDropdownName($table,$id) {
	global $cfg_install;
	$db = new DB;
	$name = "";
	$query = "select * from ". $table ." where ID = '". $id ."'";
	if ($result = $db->query($query))
	if($db->numrows($result) != 0) {
		if ($table=="glpi_dropdown_locations"||$table=="glpi_dropdown_kbcategories"){
		$name=getTreeValueName($table,$id);
	
		} else {
		$name = $db->result($result,0,"name");
		if ($table=="glpi_dropdown_netpoint")
			$name .= " (".getDropdownName("glpi_dropdown_locations",$db->result($result,0,"location")).")";
		}
		if ($table=="glpi_enterprises"){
			$name.=getEnterpriseLinks($id);
			
		}
	}
	return $name;
}

/**
* Make a select box with all glpi users in tracking table
*
*
*
* @param $value
* @param $myname
* @param $champ
* @return nothing (print out an HTML select box)
*/

function dropdownUsersTracking($value, $myname,$champ) {
	// Make a select box with all glpi users in tracking table
	global $lang;
	$db = new DB;
	$query = "SELECT DISTINCT glpi_tracking.$champ AS NAME FROM glpi_tracking WHERE glpi_tracking.$champ <> '' ORDER BY glpi_tracking.$champ";
	$result = $db->query($query);

	echo "<select name=\"$myname\">";
	$i = 0;
	$number = $db->numrows($result);
	if ($number > 0) {
		echo "<option value=\"all\">".$lang["reports"][16]."\n";
		while ($i < $number) {
			$name = $db->result($result, $i, "NAME");
			if ($name == $value) {
				echo "<option value=\"$name\" selected>".$name;
			} else {
				echo "<option value=\"$name\">".$name;
			}
			$i++;
			echo "</option>";
   		}
	}
	echo "</select>";
}

/**
* 
*
*
*
* @param $value
* @param $myname
* @param $store_path
* @return nothing (print out an HTML select box)
*/
function dropdownIcons($myname,$value,$store_path){
global $HTMLRel;
if (is_dir($store_path)){
if ($dh = opendir($store_path)) {
	echo "<select name=\"$myname\">";
       while (($file = readdir($dh)) !== false) {
           if (eregi(".png$",$file)){
	   if ($file == $value) {
				echo "<option value=\"$file\" selected>".$file;
			} else {
				echo "<option value=\"$file\">".$file;
			}
	echo "</option>";
	   }
	   
       
       }
       closedir($dh);
       echo "</select>";
   } else echo "Error reading directory $store_path";


} else echo "Error $store_path is not a directory";


}

/**
* 
*
*
*
* @param $name
* @param $search
* @param $value
* @return nothing (print out an HTML select box)
*/
function dropdownAllItems($name,$search='',$value='') {
	$db=new DB;
	
	$items=array(
	COMPUTER_TYPE=>"glpi_computers",
	NETWORKING_TYPE=>"glpi_networking",
	PRINTER_TYPE=>"glpi_printers",
	MONITOR_TYPE=>"glpi_monitors",
	PERIPHERAL_TYPE=>"glpi_peripherals",
	SOFTWARE_TYPE=>"glpi_software",
	);

	echo "<select name=\"$name\" size='1'>";

	foreach ($items as $type => $table){

		$where="WHERE '1' = '1' ";
		if ($table=="glpi_computers")
		$where.="AND is_template='0' ";
	
		if (!empty($search))
		$where.="AND name LIKE '%$search%' ";
		
//	if ($table=="glpi_enterprises"||$table=="glpi_cartridge_type")
//		$where = "WHERE deleted='N' ";

		$query = "SELECT ID FROM $table $where ORDER BY name";
	
		$result = $db->query($query);
	
		$i = 0;
		$number = $db->numrows($result);
	
		if ($number > 0) {
			while ($i < $number) {
				$ID=$db->result($result, $i, "ID");
				$ci=new CommonItem;
				$ci->getFromDB($type,$ID);
				$output=$ci->getType()." - ".$ci->getName();
				if (createAllItemsSelectValue($type,$ID) === $value) {
					echo "<option value=\"".$type."_".$ID."\" selected>$output</option>";
				} else {
					echo "<option value=\"".$type."_".$ID."\">$output</option>";
				}
				$i++;
			}
		}
	}
	echo "</select>";
	
}

/**
* Make a select box for a boolean choice (Yes/No)
*
*
*
* @param $name select name
* @param $value preselected value.
*
*/
function dropdownYesNo($name,$value){
	global $lang;
	echo "<select name='$name'>";
	echo "<option value='N' ".($value=='N'?" selected ":"").">".$lang["choice"][1]."</option>";
	echo "<option value='Y' ".($value=='Y'?" selected ":"").">".$lang["choice"][0]."</option>";
	echo "</select>";	
}	


function createAllItemsSelectValue($type,$ID){
	return $type."_".$ID;
}

function explodeAllItemsSelectResult($val){
	$splitter=split("_",$val);
	return array($splitter[0],$splitter[1]);
}

/**
* Include the good language dict.
*
* Get the default language from current user in $_SESSION["glpilanguage"].
* And load the dict that correspond.
*
* @return nothing (make an include)
*
*/
function loadLanguage() {

	GLOBAL $lang,$cfg_install;

	if(empty($_SESSION["glpilanguage"])) {
		$file= "/glpi/dicts/".$cfg_install["languages"][$cfg_install["default_language"]][1];
	} else {
		$file = "/glpi/dicts/".$cfg_install["languages"][$_SESSION["glpilanguage"]][1];
	}
		include ("_relpos.php");
		include ($phproot . $file);
}

/**
* Prints a direct connection to a computer
*
* @param $target the page where we'll print out this.
* @param $ID the connection ID
* @param $type the connection type
* @return nothing (print out a table)
*
*/
function showConnect($target,$ID,$type) {
		// Prints a direct connection to a computer

		GLOBAL $lang, $cfg_layout, $cfg_install;

		$connect = new Connection;
		$connect->type=$type;
		$computer = $connect->getComputerContact($ID);

		echo "<br><center><table width='50%' class='tab_cadre'><tr><th colspan='2'>";
		echo $lang["connect"][0].":";
		echo "</th></tr>";

		if ($computer) {
			$connect->getComputerData($computer);
			echo "<tr><td class='tab_bg_1".($connect->deleted=='Y'?"_2":"")."'><b>Computer: ";
			echo "<a href=\"".$cfg_install["root"]."/computers/computers-info-form.php?ID=".$connect->device_ID."\">";
			echo $connect->device_name." (".$connect->device_ID.")";
			echo "</a>";
			echo "</b></td>";
			echo "<td class='tab_bg_2".($connect->deleted=='Y'?"_2":"")."' align='center'><b>";
			echo "<a href=\"$target?disconnect=1&ID=$ID\">".$lang["connect"][3]."</a>";
		} else {
			echo "<tr><td class='tab_bg_1'><b>Computer: </b>";
			echo "<i>".$lang["connect"][1]."</i>";
			echo "</td>";
			echo "<td class='tab_bg_2' align='center'><b>";
			echo "<a href=\"$target?connect=1&ID=$ID\">".$lang["connect"][2]."</a>";
		}

		echo "</b></td>";
		echo "</tr>";
		echo "</table></center><br>";
}

/**
* Disconnects a direct connection
* 
*
* @param $ID the connection to disconnect ID.
* @param $type the connection to disconnect type.
* @return nothing
*/
function Disconnect($ID,$type) {
	// Disconnects a direct connection

	$connect = new Connection;
	$connect->type=$type;
	$connect->deletefromDB($ID);
}


/**
*
* Makes a direct connection
*
*
*
* @param $target
* @param $sID connection source ID.
* @param $cID computer ID (where the sID would be connected).
* @param $type connection type.
*/
function Connect($target,$sID,$cID,$type) {
	global $lang;
	// Makes a direct connection

	$connect = new Connection;
	$connect->end1=$sID;
	$connect->end2=$cID;
	$connect->type=$type;
	$connect->addtoDB();
	// Mise a jour lieu du periph si nécessaire
	$dev=new CommonItem();
	$dev->getFromDB($type,$sID);

	$comp=new Computer();
	$comp->getFromDB($cID);
	if ($comp->fields['location']!=$dev->obj->fields['location']){
	$updates[0]="location";
	$dev->obj->fields['location']=$comp->fields['location'];
	$dev->obj->updateInDB($updates);
	$_SESSION["MESSAGE_AFTER_REDIRECT"]=$lang["computers"][48];

	}
	if ($comp->fields['contact']!=$dev->obj->fields['contact']||$comp->fields['contact_num']!=$dev->obj->fields['contact_num']){
	$updates[0]="contact";
	$updates[1]="contact_num";
	$dev->obj->fields['contact']=$comp->fields['contact'];
	$dev->obj->fields['contact_num']=$comp->fields['contact_num'];
	$dev->obj->updateInDB($updates);
	$_SESSION["MESSAGE_AFTER_REDIRECT"]=$lang["computers"][49];
	}
	
}

/**
* Print a select box for an item to be connected
* 
* 
*
*
* @param $target where we go when done.
* @param $ID connection source ID.
* @param $type connection type.
*/
function showConnectSearch($target,$ID,$type="computer") {

	GLOBAL $cfg_layout,$cfg_install, $lang;

	echo "<center><table class='tab_cadre'>";
	echo "<tr><th colspan='2'>".$lang["connect"][4]." :</th></tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<form method='post' action=\"$target\">";
	echo "<td>";
	switch($type){
	case "computer" :
		echo $lang["connect"][5];		
		break;
	case "printer" :
		echo $lang["connect"][13];		
		break;
	case "peripheral" :
		echo $lang["connect"][14];		
		break;
	case "monitor" :
		echo $lang["connect"][15];		
		break;
		
	default : // computer
		echo "<tr><th colspan='2'>ERROR  :</th></tr>";
	}
	
	echo " <select name=type>";
	echo "<option value=name>".$lang["connect"][6]."</option>";
	echo "<option value=id>".$lang["connect"][7]."</option>";
	echo "</select> ";
	echo $lang["connect"][8]." <input type='text' size=10 name=search>";
	echo "<input type='hidden' name='pID1' value=$ID>";
	echo "<input type='hidden' name='device_type' value=$type>";
	echo "<input type='hidden' name='connect' value='2'>";
	echo "</td><td class='tab_bg_2'>";
	echo "<input type='submit' value=\"".$lang["buttons"][11]."\" class='submit'>";
	echo "</td></tr>";	

	echo "</form>";
	echo "</table>";	
	echo "</center>";
}

/**
* To be commented
* 
*
* @param $target where we go when done
* @param $input 
* @return nothing
*/
function listConnectComputers($target,$input) {

	GLOBAL $cfg_layout,$cfg_install, $lang;

	$pID1 = $input["pID1"];

	echo "<center><table  class='tab_cadre'>";
	echo "<tr><th colspan='2'>".$lang["connect"][9].":</th></tr>";
	echo "<form method='post' action=\"$target\"><tr><td>";

	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'>";

	$db = new DB;
	if ($input["type"] == "name") {
		$query = "SELECT glpi_computers.ID as ID,glpi_computers.name as name, glpi_dropdown_locations.ID as location  from glpi_computers left join glpi_dropdown_locations on glpi_computers.location = glpi_dropdown_locations.id WHERE glpi_computers.deleted = 'N' AND glpi_computers.name LIKE '%".$input["search"]."%' order by name ASC";
	} else {
		$query = "SELECT glpi_computers.ID as ID,glpi_computers.name as name, glpi_dropdown_locations.ID as location from glpi_computers left join glpi_dropdown_locations on glpi_computers.location = glpi_dropdown_locations.id WHERE glpi_computers.deleted = 'N' AND glpi_computers.ID LIKE '%".$input["search"]."%' order by name ASC";
	} 
	$result = $db->query($query);
	$number = $db->numrows($result);
	echo "<select name=\"cID\">";
	while ($i < $number) {
		$dID = $db->result($result, $i, "ID");
		$name = $db->result($result, $i, "name");
		$location = $db->result($result, $i, "location");
		echo "<option value=\"$dID\">".$name." (".getTreeValueName("glpi_dropdown_locations",$location).")</option>";
		$i++;
	}
	echo  "</select>";

	echo "</td>";
	echo "<td class='tab_bg_2' align='center'>";
	echo "<input type='hidden' name='sID' value=\"".$input["pID1"]."\">";
	echo "<input type='hidden' name='connect' value='3'>";
	echo "<input type='hidden' name='device_type' value='computer'>";
	echo "<input type='submit' value=\"".$lang["buttons"][9]."\" class='submit'>";
	echo "</td></form></tr></table>";	

}

/**
*
* To be commented
*
*
*
* @param $target where we go when done
* @param $input
*
* @return nothing
*/
function listConnectElement($target,$input) {

	GLOBAL $cfg_layout,$cfg_install, $lang;

	$pID1 = $input["pID1"];
	$device_type=$input["device_type"];
	$table="";
	switch($device_type){
	case "printer":
	$table="glpi_printers";$device_id=PRINTER_TYPE;break;
	case "monitor":
	$table="glpi_monitors";$device_id=MONITOR_TYPE;break;
	case "peripheral":
	$table="glpi_peripherals";$device_id=PERIPHERAL_TYPE;break;
	
	}
	
	echo "<center><table  class='tab_cadre'>";
	echo "<tr><th colspan='2'>";
	switch($device_type){
	case "printer":
	echo 	$lang["connect"][10];break;
	case "monitor":
	echo 	$lang["connect"][12];break;
	case "peripheral":
	echo 	$lang["connect"][11];break;
	}

	
	echo ":</th></tr>";
	echo "<form method='post' action=\"$target\"><tr><td>";

	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'>";

	$db = new DB;
	if ($input["type"] == "name") {
		$query = "SELECT $table.ID as ID,$table.name as name, glpi_dropdown_locations.ID as location from $table left join glpi_dropdown_locations on $table.location = glpi_dropdown_locations.id left join glpi_connect_wire on ($table.ID = glpi_connect_wire.end1 AND glpi_connect_wire.type = $device_id) WHERE $table.deleted='N' AND $table.is_template='0' AND $table.name LIKE '%".$input["search"]."%' AND glpi_connect_wire.ID IS NULL order by name ASC";
	} else {
		$query = "SELECT $table.ID as ID,$table.name as name, glpi_dropdown_locations.ID as location from $table left join glpi_dropdown_locations on $table.location = glpi_dropdown_locations.id left join glpi_connect_wire on ($table.ID = glpi_connect_wire.end1 AND glpi_connect_wire.type = $device_id) WHERE $table.deleted='N' AND $table.is_template='0' AND $table.ID LIKE '%".$input["search"]."%' AND glpi_connect_wire.ID IS NULL order by name ASC";
	} 
	
	
	//echo $query;
	$result = $db->query($query);
	$number = $db->numrows($result);
	if ($number>0) {
	echo "<select name=\"ID\">";
	while ($i < $number) {
		$dID = $db->result($result, $i, "ID");
		$name = $db->result($result, $i, "name");
		$location = $db->result($result, $i, "location");
		echo "<option value=\"$dID\">".$name." (".getTreeValueName("glpi_dropdown_locations",$location).")</option>";
		$i++;
	}
	echo  "</select>";

	echo "</td>";
	echo "<td class='tab_bg_2' align='center'>";
	echo "<input type='hidden' name='cID' value=\"".$input["pID1"]."\">";
	echo "<input type='hidden' name='connect' value='3'>";
	echo "<input type='hidden' name='device_type' value='$device_id'>";
	echo "<input type='submit' value=\"".$lang["buttons"][9]."\" class='submit'>";
	} else echo $lang["connect"][16]."<br><b><a href=\"".$_SERVER["PHP_SELF"]."?ID=".$input["pID1"]."\">".$lang["buttons"][13]."</a></b>";
	
	echo "</td></form></tr></table>";	

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

	echo "<form method='post' name=\"helpdeskform\" action=\"".$cfg_install["root"]."/tracking/tracking-injector.php\">";
	echo "<input type='hidden' name='from_helpdesk' value='$from_helpdesk'>";

	echo "<center><table  class='tab_cadre'>";

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
		echo "<option value='no' selected>".$lang["help"][9]."";
		echo "<option value='yes'>".$lang["help"][10]."";
		echo "</select>";
		echo "</td></tr>";
		echo "<tr class='tab_bg_1'>";
		echo "<td>".$lang["help"][11].":</td>";
		echo "<td>	<input name='uemail' value=\"$email\" size='20'>";
		echo "</td></tr>";
	}

	echo "<tr class='tab_bg_1'>";
	echo "<td>".$lang["help"][12]." <a href=\"#\" onClick=\"window.open('".$cfg_install["root"]."/find_num.php','Help','scrollbars=1,resizable=1,width=400,height=400')\"><img src=\"".$cfg_install["root"]."/pics/aide.png\"  alt=\"help\"></a></td>";
	echo "<td><input name='computer' size='10'>";
	echo "</td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'>";
	echo "<td>".$lang["help"][24].": </td>";
	echo "<td><select name=device_type>";
    //if (isAdmin($_SESSION["glpitype"]))
    echo "<option value='0' >".$lang["help"][30]."";
	echo "<option value='1' selected>".$lang["help"][25]."";
	echo "<option value='2'>".$lang["help"][26]."";
	echo "<option value='3'>".$lang["help"][27]."";
	echo "<option value='4'>".$lang["help"][28]."";
	echo "<option value='5'>".$lang["help"][29]."";
	echo "<option value='6'>".$lang["help"][31]."";
	echo "</select>";
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'>";
	echo "<td colspan='2' align='center'>".$lang["help"][13].":</td>";
	echo "</tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<td colspan='2' align='center'><textarea name='contents' cols='45' rows='14' ></textarea>";
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'>";
	echo "<td colspan='2' align='center'> <input type='submit' value=\"".$lang["help"][14]."\" class='submit'>";
		echo "<input type='hidden' name='IRMName' value=\"$name\">";
	echo "</td></tr>";

	echo "</table>";
	echo "</center>";
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

	GLOBAL $cfg_layout, $cfg_features, $lang;
	
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
	echo "<br><center><table class='tab_cadre2' width='750'>";
	echo "<tr>";
	
	// Back and fast backward button
	if (!$start==0) {
		echo "<th align='left'>";
		echo "<a href=\"$target?$parameters&start=$back\">";
		echo "&nbsp;<&nbsp;";
		echo "</a></th>";
		echo "<th align='left'>";
		echo "<a href=\"$target?$parameters&start=0\">";
		echo "&nbsp;<<&nbsp;";
		echo "</a></th>";
	}

	// Print the "where am I?" 
	echo "<td width='750' align='center' class='tab_bg_2'><b>";
	echo $lang["pager"][2]."&nbsp;".$current_start."&nbsp;".$lang["pager"][1]."&nbsp;".$current_end."&nbsp;".$lang["pager"][3]."&nbsp;".$numrows."&nbsp;";
	echo "</b></td>";

	// Forward and fast forward button
	if ($forward<$numrows) {
		echo "<th align='right'>";
		echo "<a href=\"$target?$parameters&start=$forward\">";
		echo "&nbsp;>&nbsp;";
		echo "</a></th>";
		echo "<th align='right'>";
		echo "<a href=\"$target?$parameters&start=$end\">";
		echo "&nbsp;>>&nbsp;";
		echo "</a></th>";
	}

	// End pager
	echo "</tr>";
	echo "</table></center>";
}

/**
* affiche un rapport personalisé a partir d'une requete $query
* pour un type de materiel ($item_type)
* 
* Print out a report from a query ($query) for an item type ($item_type).
*
*
* @param $query query for make the report
* @param $item_type item type.
* @return nothing (print out a report).
*/
function report_perso($item_type,$query)
//affiche un rapport personalisé a partir d'une requete $query
//pour un type de materiel ($item_type) 
{

GLOBAL $cfg_layout, $cfg_features, $lang;

$db = new DB;
$result = $db->query($query);
include ("_relpos.php");


switch($item_type)
	{   
		case 'glpi_computers' :
		
		
		echo " <strong>".$lang["reports"][6]."</strong>";
		echo "<table width='100%' height='60' border='0' bordercolor='black'>";
		echo "<tr>";
		echo "<th><div align='center'><b>".$lang["computers"][7]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["common"][3]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][10]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][41]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][42]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["financial"][6]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["financial"][7]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["search"][9]."</b></div></th>";
		echo "</tr>";
	 	while( $ligne = $db->fetch_array($result))
					{
						
						$name = $ligne['itemname'];
						$deleted = $ligne['itemdeleted'];
						$lieu = $ligne['location'];
						$achat_date = $ligne['buy_date'];
						$fin_garantie = getWarrantyExpir($ligne["buy_date"],$ligne["warranty_duration"]);
						$contract_type = getContractTypeName($ligne["contract_type"]);
						$contract_begin = $ligne['begin_date'];
						$contract_end = getWarrantyExpir($ligne["begin_date"],$ligne["duration"]);
		
						//inserer ces valeures dans un tableau

						echo "<tr>";
						if($name) echo "<td><div align='center'> $name </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($deleted) echo "<td><div align='center'> $deleted </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($lieu) echo "<td><div align='center'> ".getDropdownName("glpi_dropdown_locations",$lieu)." </div></td>"; else echo "<td><div align='center'> N/A </div></td>";	
						if($achat_date) echo "<td><div align='center'> $achat_date </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($fin_garantie) echo "<td><div align='center'> $fin_garantie </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contract_type) echo "<td><div align='center'> $contract_type </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contract_begin) echo "<td><div align='center'> $contract_begin </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contract_end) echo "<td><div align='center'> $contract_end </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						
						echo "</tr>";
					}
		echo "</table><br><hr><br> ";
		break;
		
		case 'glpi_printers' :
		
		echo "<b><strong>".$lang["reports"][7]."</strong></b>";
		echo "<table width='100%' height='60' border='0'>";
		echo "<tr> ";
		echo "<th><div align='center'><b>".$lang["computers"][7]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["common"][3]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][10]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][41]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][42]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["financial"][6]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["financial"][7]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["search"][9]."</b></div></th>";
		echo "</tr>";
		
		while( $ligne = $db->fetch_array($result))
					{
						$name = $ligne['itemname'];
						$deleted = $ligne['itemdeleted'];
						$lieu = $ligne['location'];
						$achat_date = $ligne['buy_date'];
						$fin_garantie = getWarrantyExpir($ligne["buy_date"],$ligne["warranty_duration"]);
						$contract_type = getContractTypeName($ligne["contract_type"]);
						$contract_begin = $ligne['begin_date'];
						$contract_end = getWarrantyExpir($ligne["begin_date"],$ligne["duration"]);
					
					//inserer ces valeures dans un tableau
					echo "<tr>";	
						if($name) echo "<td><div align='center'> $name </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($deleted) echo "<td><div align='center'> $deleted </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($lieu) echo "<td><div align='center'> ".getDropdownName("glpi_dropdown_locations",$lieu)." </div></td>"; else echo "<td><div align='center'> N/A </div></td>";	
						if($achat_date) echo "<td><div align='center'> $achat_date </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($fin_garantie) echo "<td><div align='center'> $fin_garantie </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contract_type) echo "<td><div align='center'> $contract_type </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contract_begin) echo "<td><div align='center'> $contract_begin </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contract_end) echo "<td><div align='center'> $contract_end </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					echo "</tr>";
					}	
		echo "</table><br><hr><br>";
		break;
		
		case 'glpi_monitors' :
		
		echo " <b><strong>".$lang["reports"][9]."</strong></b>";
		echo "<table width='100%' height='60' border='0'>";
		echo "<tr> ";
		echo "<th><div align='center'><b>".$lang["computers"][7]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["common"][3]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][10]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][41]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][42]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["financial"][6]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["financial"][7]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["search"][9]."</b></div></th>";
		echo "</tr>";
		
		while( $ligne = $db->fetch_array($result))
					{
						$name = $ligne['itemname'];
						$deleted = $ligne['itemdeleted'];
						$lieu = $ligne['location'];
						$achat_date = $ligne['buy_date'];
						$fin_garantie = getWarrantyExpir($ligne["buy_date"],$ligne["warranty_duration"]);
						$contract_type = getContractTypeName($ligne["contract_type"]);
						$contract_begin = $ligne['begin_date'];
						$contract_end = getWarrantyExpir($ligne["begin_date"],$ligne["duration"]);
					//inserer ces valeures dans un tableau
					
					echo "<tr>";	
						if($name) echo "<td><div align='center'> $name </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($deleted) echo "<td><div align='center'> $deleted </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($lieu) echo "<td><div align='center'> ".getDropdownName("glpi_dropdown_locations",$lieu)." </div></td>"; else echo "<td><div align='center'> N/A </div></td>";	
						if($achat_date) echo "<td><div align='center'> $achat_date </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($fin_garantie) echo "<td><div align='center'> $fin_garantie </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contract_type) echo "<td><div align='center'> $contract_type </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contract_begin) echo "<td><div align='center'> $contract_begin </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contract_end) echo "<td><div align='center'> $contract_end </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					echo "</tr>";
					}	
		echo "</table><br><hr><br>";
		break;
		
		case 'glpi_networking' :
		
		echo " <b><strong>".$lang["reports"][8]."</strong></b>";
		echo "<table width='100%' height='60' border='0'>";
		echo "<tr> ";
		echo "<th><div align='center'><b>".$lang["computers"][7]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["common"][3]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][10]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][41]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][42]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["financial"][6]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["financial"][7]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["search"][9]."</b></div></th>";
		echo "</tr>";
		
		while( $ligne = $db->fetch_array($result))
					{
						$name = $ligne['itemname'];
						$deleted = $ligne['itemdeleted'];
						$lieu = $ligne['location'];
						$achat_date = $ligne['buy_date'];
						$fin_garantie = getWarrantyExpir($ligne["buy_date"],$ligne["warranty_duration"]);
						$contract_type = getContractTypeName($ligne["contract_type"]);
						$contract_begin = $ligne['begin_date'];
						$contract_end = getWarrantyExpir($ligne["begin_date"],$ligne["duration"]);
					//inserer ces valeures dans un tableau
				
					echo "<tr> ";	
						if($name) echo "<td><div align='center'> $name </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($deleted) echo "<td><div align='center'> $deleted </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($lieu) echo "<td><div align='center'> ".getDropdownName("glpi_dropdown_locations",$lieu)." </div></td>"; else echo "<td><div align='center'> N/A </div></td>";	
						if($achat_date) echo "<td><div align='center'> $achat_date </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($fin_garantie) echo "<td><div align='center'> $fin_garantie </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contract_type) echo "<td><div align='center'> $contract_type </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contract_begin) echo "<td><div align='center'> $contract_begin </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contract_end) echo "<td><div align='center'> $contract_end </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					echo "</tr>";
					}	
		echo "</table><br><hr><br>";
		break;
		case 'glpi_peripherals' :
		
		echo " <b><strong>".$lang["reports"][29]."</strong></b>";
		echo "<table width='100%' height='60' border='0'>";
		echo "<tr> ";
		echo "<th><div align='center'><b>".$lang["computers"][7]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["common"][3]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][10]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][41]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][42]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["financial"][6]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["financial"][7]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["search"][9]."</b></div></th>";
		echo "</tr>";
		
		while( $ligne = $db->fetch_array($result))
					{
						$name = $ligne['itemname'];
						$deleted = $ligne['itemdeleted'];
						$lieu = $ligne['location'];
						$achat_date = $ligne['buy_date'];
						$fin_garantie = getWarrantyExpir($ligne["buy_date"],$ligne["warranty_duration"]);
						$contract_type = getContractTypeName($ligne["contract_type"]);
						$contract_begin = $ligne['begin_date'];
						$contract_end = getWarrantyExpir($ligne["begin_date"],$ligne["duration"]);
					//inserer ces valeures dans un tableau
					
					echo "<tr>";	
						if($name) echo "<td><div align='center'> $name </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($deleted) echo "<td><div align='center'> $deleted </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($lieu) echo "<td><div align='center'> ".getDropdownName("glpi_dropdown_locations",$lieu)." </div></td>"; else echo "<td><div align='center'> N/A </div></td>";	
						if($achat_date) echo "<td><div align='center'> $achat_date </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($fin_garantie) echo "<td><div align='center'> $fin_garantie </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contract_type) echo "<td><div align='center'> $contract_type </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contract_begin) echo "<td><div align='center'> $contract_begin </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contract_end) echo "<td><div align='center'> $contract_end </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					echo "</tr>";
					}	
		echo "</table><br><hr><br>";
		break;
		case 'glpi_software' :
		
		echo " <b><strong>".$lang["reports"][55]."</strong></b>";
		echo "<table width='100%' height='60' border='0'>";
		echo "<tr> ";
		echo "<th><div align='center'><b>".$lang["computers"][7]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["common"][3]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][10]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][41]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][42]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["financial"][6]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["financial"][7]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["search"][9]."</b></div></th>";
		echo "</tr>";
		
		while( $ligne = $db->fetch_array($result))
					{
						$name = $ligne['itemname'];
						$deleted = $ligne['itemdeleted'];
						$lieu = $ligne['location'];
						$achat_date = $ligne['buy_date'];
						$fin_garantie = getWarrantyExpir($ligne["buy_date"],$ligne["warranty_duration"]);
						$contract_type = getContractTypeName($ligne["contract_type"]);
						$contract_begin = $ligne['begin_date'];
						$contract_end = getWarrantyExpir($ligne["begin_date"],$ligne["duration"]);
					//inserer ces valeures dans un tableau
					
					echo "<tr>";	
						if($name) echo "<td><div align='center'> $name </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($deleted) echo "<td><div align='center'> $deleted </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($lieu) echo "<td><div align='center'> ".getDropdownName("glpi_dropdown_locations",$lieu)." </div></td>"; else echo "<td><div align='center'> N/A </div></td>";	
						if($achat_date) echo "<td><div align='center'> $achat_date </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($fin_garantie) echo "<td><div align='center'> $fin_garantie </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contract_type) echo "<td><div align='center'> $contract_type </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contract_begin) echo "<td><div align='center'> $contract_begin </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contract_end) echo "<td><div align='center'> $contract_end </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					echo "</tr>";
					}	
		echo "</table><br><hr><br>";
		break;
		// Rapport réseau par lieu
		case 'glpi_networking_lieu' :
		echo "<table width='100%' height='60' border='0'>";
		echo "<tr> ";
		echo "<th><div align='center'><b>".$lang["reports"][37]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][52]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][38]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][46]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][53]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][47]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][38]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][53]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][36]."</b></div></th>";
		echo "</tr>";
		
		while( $ligne = $db->fetch_array($result))
					{
					//echo $ligne['location'];
					//print_r($ligne);
					$prise=$ligne['prise'];
					$port=$ligne['port'];
					$switch=$ligne['switch'];
					$portordi=$ligne['portordi'];
					$ordi=$ligne['ordi'];
					$ip=$ligne['ip'];
					$mac=$ligne['mac'];
					$ip2=$ligne['ip2'];
					$mac2=$ligne['mac2'];					
					//inserer ces valeures dans un tableau
					
					echo "<tr>";	
					if($prise) echo "<td><div align='center'>$prise</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($switch) echo "<td><div align='center'>$switch</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($ip) echo "<td><div align='center'>$ip</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($port) echo "<td><div align='center'>$port</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($mac) echo "<td><div align='center'>$mac</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($portordi) echo "<td><div align='center'>$portordi</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($ip2) echo "<td><div align='center'>$ip2</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($mac2) echo "<td><div align='center'>$mac2</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($ordi) echo "<td><div align='center'>$ordi</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					}	
		echo "</table><br><hr><br>";
		break;
	//rapport reseau par switch	
	case 'glpi_networking_switch' :
		echo "<table width='100%' height='60' border='0'>";
		echo "<tr> ";
		echo "<th><div align='center'><b>".$lang["reports"][20]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][38]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][46]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][53]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][47]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][38]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][53]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][36]."</b></div></th>";
		echo "</tr>";
		
		while( $ligne = $db->fetch_array($result))
					{
					//$switch = $ligne['switch'];
					//echo $ligne['location'];
					$ID=$ligne['ID'];
					$lieu=getDropdownName("glpi_dropdown_locations",$ID);
					//$prise=$ligne['prise'];
					$port = $ligne['port'];
					$portordi=$ligne['portordi'];
					$ordi=$ligne['ordi'];
					$ip=$ligne['ip'];
					$mac=$ligne['mac'];
					$ip2=$ligne['ip2'];
					$mac2=$ligne['mac2'];
					//inserer ces valeures dans un tableau
					
					echo "<tr>";	
					//if($switch) echo "<td><div align='center'>$switch</div></td>"; else echo //"<td><div align='center'> N/A </div></td>";
					if($lieu) echo "<td><div align='center'>$lieu</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($port) echo "<td><div align='center'>$port</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($ip) echo "<td><div align='center'>$ip</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($mac) echo "<td><div align='center'>$mac</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($portordi) echo "<td><div align='center'>$portordi</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($ip2) echo "<td><div align='center'>$ip2</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($mac2) echo "<td><div align='center'>$mac2</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($ordi) echo "<td><div align='center'>$ordi</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					}	
		echo "</table><br><hr><br>";
		break;
		
		//rapport reseau par prise
		case 'glpi_networking_prise' :
		echo "<table width='100%' height='60' border='0'>";
		echo "<tr> ";
		echo "<th><div align='center'><b>".$lang["reports"][20]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][52]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][38]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][46]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][53]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][47]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][38]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][53]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][36]."</b></div></th>";
		echo "</tr>";
		
		while( $ligne = $db->fetch_array($result))
					{
					$prise=$ligne['prise'];
					$ID=$ligne['ID'];
					$lieu=getDropdownName("glpi_dropdown_locations",$ID);
					//$etage=$ligne['etage'];
					$switch=$ligne['switch'];
					$port=$ligne['port'];
					$portordi=$ligne['portordi'];
					$ordi=$ligne['ordi'];
					$ip=$ligne['ip'];
					$mac=$ligne['mac'];
					$ip2=$ligne['ip2'];
					$mac2=$ligne['mac2'];
					//echo $ligne['location'];
					//print_r($ligne);
					//$ports=$ligne['ports'];
					//$ifaddr = $ligne['ifaddr'];
					
					
					//inserer ces valeures dans un tableau
					
					echo "<tr>";
					if($lieu) echo "<td><div align='center'>$lieu</div></td>"; else echo "<td><div align='center'> N/A </div></td>";	
					if($switch) echo "<td><div align='center'>$switch</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($ip) echo "<td><div align='center'>$ip</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($port) echo "<td><div align='center'>$port</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($mac) echo "<td><div align='center'>$mac</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($portordi) echo "<td><div align='center'>$portordi</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($ip2) echo "<td><div align='center'>$ip2</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($mac2) echo "<td><div align='center'>$mac2</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($ordi) echo "<td><div align='center'>$ordi</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					}	
		echo "</table><br><hr><br>";
		break;	
		
	}	
}

/**
* Count the number of elements in a table.
*
*
* @param $table table name
*
* return int nb of elements in table
*/
function countElementsInTable($table){
$db=new DB;
$query="SELECT count(*) as cpt from $table";
$result=$db->query($query);
$ligne = $db->fetch_array($result);
return $ligne['cpt'];
}


//****************
// De jolies fonctions pour améliorer l'affichage du texte de la FAQ/knowledgbase
//***************

/**
*Met en "ordre" une chaine avant affichage
* Remplace trés AVANTAGEUSEMENT nl2br 
* 
* @param $pee
* 
* 
* @return $string
*/
function autop($pee, $br=1) {

// 

// Thanks  to Matthew Mullenweg

$pee = preg_replace("/(\r\n|\n|\r)/", "\n", $pee); // cross-platform newlines
$pee = preg_replace("/\n\n+/", "\n\n", $pee); // take care of duplicates
$pee = preg_replace('/\n?(.+?)(\n\n|\z)/s', "<p>$1</p>\n", $pee); // make paragraphs, including one at the end
if ($br) $pee = preg_replace('|(?<!</p>)\s*\n|', "<br>\n", $pee); // optionally make line breaks
return $pee;
}


/**
* Rend une url cliquable htp/https/ftp meme avec une variable Get
*
* @param $chaine
* 
* 
* 
* @return $string
*/
function clicurl($chaine){

// 

$text=preg_replace("`((?:https?|ftp)://\S+)(\s|\z)`", '<a href="$1">$1</a>$2', $chaine); 

return $text;
}

/**
* Split the message into tokens ($inside contains all text inside $start and $end, and $outside contains all text outside)
*
* @param $text
* @param $start
* @param $end
* 
* @return array 
*/
function split_text($text, $start, $end)
{
	
// Adapté de PunBB 
//Copyright (C)  Rickard Andersson (rickard@punbb.org)

	$tokens = explode($start, $text);

	$outside[] = $tokens[0];

	$num_tokens = count($tokens);
	for ($i = 1; $i < $num_tokens; ++$i)
	{
		$temp = explode($end, $tokens[$i]);
		$inside[] = $temp[0];
		$outside[] = $temp[1];
	}

	

	return array($inside, $outside);
}


/**
* Replace bbcode in text by html tag
*
* @param $string
* 
* 
* 
* @return $string 
*/
function rembo($string){

// Adapté de PunBB 
//Copyright (C)  Rickard Andersson (rickard@punbb.org)

  

// If the message contains a code tag we have to split it up (text within [code][/code] shouldn't be touched)
	if (strpos($string, '[code]') !== false && strpos($string, '[/code]') !== false)
	{
		list($inside, $outside) = split_text($string, '[code]', '[/code]');
		$outside = array_map('trim', $outside);
		$string = implode('<">', $outside);
	}




	$pattern = array('#\[b\](.*?)\[/b\]#s',
					 '#\[i\](.*?)\[/i\]#s',
					 '#\[u\](.*?)\[/u\]#s',
					  '#\[s\](.*?)\[/s\]#s',
					  '#\[c\](.*?)\[/c\]#s',
					 '#\[g\](.*?)\[/g\]#s',
					 //'#\[url\](.*?)\[/url\]#e',
					 //'#\[url=(.*?)\](.*?)\[/url\]#e',
					 '#\[email\](.*?)\[/email\]#',
					 '#\[email=(.*?)\](.*?)\[/email\]#',
					 '#\[color=([a-zA-Z]*|\#?[0-9a-fA-F]{6})](.*?)\[/color\]#s');

					 
	$replace = array('<strong>$1</strong>',
					 '<em>$1</em>',
					 '<span class="souligne">$1</span>',
					'<span class="barre">$1</span>',
					'<div align="center">$1</div>',
					'<big>$1</big>',
					// 'truncate_url(\'$1\')',
					 //'truncate_url(\'$1\', \'$2\')',
					 '<a href="mailto:$1">$1</a>',
					 '<a href="mailto:$1">$2</a>',
					 '<span style="color: $1">$2</span>');

	// This thing takes a while! :)
	$string = preg_replace($pattern, $replace, $string);

	
	
	$string=clicurl($string);
	
	$string=autop($string);
	
	
	// If we split up the message before we have to concatenate it together again (code tags)
	if (isset($inside))
	{
		$outside = explode('<">', $string);
		$string = '';

		$num_tokens = count($outside);

		for ($i = 0; $i < $num_tokens; ++$i)
		{
			$string .= $outside[$i];
			if (isset($inside[$i]))
				$string .= '<br><br><table  class="code" align="center" cellspacing="4" cellpadding="6"><tr><td class="punquote"><b>Code:</b><br><br><pre>'.trim($inside[$i]).'</pre></td></tr></table><br>';
		}
	}

	
	
	
	
	
	return $string;
}


/**
* To be commented
*
* @param $table
* @param $current
* @param $parentID
* @param $categoryname
* @return nothing 
*/
function showTreeListSelect($table,$current, $parentID=0, $categoryname="")
{

	$query = "select * from $table where (parentID = $parentID) order by name ";

	$db=new DB;
	
	if ($result=$db->query($query)){
		if ($db->numrows($result)>0){
	
		while ($row=$db->fetch_array($result)){
		
			$ID = $row["ID"];
			$name = $categoryname . $row["name"];
			echo "<option value='$ID'";
			if($current == $ID)	echo " selected";
			echo ">$name</option>\n";
			
			$name = $name . "\\";
			showTreeListSelect($table,$current, $ID, $name);
		}
	}	}


}


/**
* To be commented
*
* @param $table
* @param $ID
* @return nothing 
*/
function getTreeLeafValueName($table,$ID)
{
	$query = "select * from $table where (ID = $ID)";
	$db=new DB;
	$name="";
	if ($result=$db->query($query)){
		if ($db->numrows($result)==1){
			$name=$db->result($result,0,"name");
		}
		
	}
return $name;
}

/**
* show name catégory
*
* @param $table
* @param $ID
* @param $wholename
* @return string name
*/
function getTreeValueName($table,$ID, $wholename="")
{
	// show name catégory
	// ok ??
	
	global $lang;
	
	$query = "select * from $table where (ID = $ID)";
	$db=new DB;
	
	if ($result=$db->query($query)){
		if ($db->numrows($result)>0){
		
		$row=$db->fetch_array($result);
		
		$parentID = $row["parentID"];
		if($wholename == "")
		{
			$name = $row["name"];
		} else
		{
			$name = $row["name"] . "\\";
		}
		$name = getTreeValueName($table,$parentID, $name) . $name;
	}
	
	}
return (@$name);
}

/**
* Get the equivalent serach query using ID that the search of the string argument
*
* @param $table
* @param $search the search strin value
* @return string the query
*/
function getRealSearchForTreeItem($table,$search){

if (empty($search)) return " ( $table.name LIKE '%$search%') ";

$db=new DB();

// IDs to be present in the final query
$id_found=array();
// current ID found to be added
$found=array();

// First request init the  varriables
$query="SELECT ID from $table WHERE name LIKE '%$search%'";
if ( ($result=$db->query($query)) && ($db->numrows($result)>0) ){
	while ($row=$db->fetch_array($result)){
		array_push($id_found,$row['ID']);
		array_push($found,$row['ID']);
	}
}else return " ( $table.name LIKE '%$search%') ";

// Get the leafs of previous founded item
while (count($found)>0){
	// Get next elements
	$query="SELECT ID from $table WHERE '0'='1' ";
	foreach ($found as $key => $val)
		$query.= " OR parentID = '$val' ";
		
	// CLear the found array
	unset($found);
	$found=array();
	
	$result=$db->query($query);
	if ($db->numrows($result)>0){
		while ($row=$db->fetch_array($result)){
			if (!in_array($row['ID'],$id_found)){
				array_push($id_found,$row['ID']);
				array_push($found,$row['ID']);
			}
		}		
	}
}

// Construct the final request
if (count($id_found)>0){
	$ret=" ( '0' = '1' ";
	foreach ($id_found as $key => $val)
		$ret.=" OR $table.ID = '$val' ";
	$ret.=") ";
	
	return $ret;
}else return " ( $table.name LIKE '%$search%') ";
}


/**
* Get the level for an item in a tree structure
*
* @param $table
* @param $ID
* @return int level
*/
function getTreeItemLevel($table,$ID){

$level=0;

$db=new DB();
$query="select parentID from $table where ID='$ID'";
while (1)
{
	if (($result=$db->query($query))&&$db->numrows($result)==1){
		$parentID=$db->result($result,0,"parentID");
		if ($parentID==0) return $level;
		else {
			$level++;
			$query="select parentID from $table where ID='$parentID'";
		}
	}
}


return -1;

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
		echo "<input type='text' name='$element' readonly size='10' value='$value'>";
		
		if ($withtemplate!=2){
			echo "&nbsp;<img src='".$HTMLRel."pics/calendar.png' class='calendrier' alt='".$lang["buttons"][15]."' title='".$lang["buttons"][15]."'
			onclick=\"window.open('".$HTMLRel."mycalendar.php?form=$form&amp;elem=$element&amp;value=$value','".$lang["buttons"][15]."','width=200,height=220')\" >";
		
			echo "&nbsp;<img src='".$HTMLRel."pics/reset.png' class='calendrier' onClick=\"document.forms['$form'].$element.value='0000-00-00'\" alt='Reset' title='Reset'>";	
		}
}

/**
* To be commented
*
* @param $file
* @param $filename
* @return nothing
*/
function sendFile($file,$filename){
        // Test sécurité
	if (ereg("\.\.",$file)){
	session_start();
	echo "Security attack !!!";
	logEvent($file, "senFile", 1, "security", $_SESSION["glpiname"]." try to get a non standard file.");
	return;
	}
	if (!file_exists($file)){
	echo "Error file $file does not exist";
	} else {
		header("Content-disposition: filename=$filename");
        	header('Content-type: application/octetstream');
        	header('Pragma: no-cache');
        	header('Expires: 0');
		$f=fopen($file,"r");
		
		if (!$f){
		echo "Error opening file $file";
		} else {
			while (!feof($f)){
           		echo  fread($f, 1024);
       			}
		}
	
	}
}
/**
* Get the ID of the next Item
*
* @param $table table to search next item
* @param $ID current ID
* @return the next ID, -1 if not exist
*/
function getNextItem($table,$ID){

$query = "select ID from $table where ID > $ID AND deleted='N' AND is_template='0' order by ID";
$db=new DB;
$result=$db->query($query);
if ($db->numrows($result)>0)
	return $db->result($result,0,"ID");
else return -1;

}

/**
* Get the ID of the previous Item
*
* @param $table table to search next item
* @param $ID current ID
* @return the previous ID, -1 if not exist
*/
function getPreviousItem($table,$ID){

$query = "select ID from $table where ID < $ID AND deleted='N' AND is_template='0' order by ID DESC";
$db=new DB;
$result=$db->query($query);
if ($db->numrows($result)>0)
	return $db->result($result,0,"ID");
else return -1;

}


?>
