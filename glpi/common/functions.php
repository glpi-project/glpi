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
return $value;
/*
       $value = is_array($value) ?
                   array_map('htmlentities_deep', $value) :
                   (is_null($value) ? NULL : htmlentities($value,ENT_QUOTES));
       return $value;
*/       
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
return $string;
/*	$trans_tbl = get_html_translation_table (HTML_ENTITIES,ENT_QUOTES);
	if( $trans_tbl["'"] != '&#039;' ) { # some versions of PHP match single quotes to &#39;
		$trans_tbl["'"] = '&#039;';
	}
	$trans_tbl = array_flip ($trans_tbl);
	return strtr ($string, $trans_tbl);
*/	
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
return $value;
/*	$value = is_array($value) ?
		array_map('unhtmlentities_deep', $value) :
			(is_null($value) ? NULL : unhtmlentities($value,ENT_QUOTES));
	return $value;
*/	
}

function utf8_decode_deep($value) {
	$value = is_array($value) ?
		array_map('utf8_decode_deep', $value) :
			(is_null($value) ? NULL : utf8_decode($value));
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
	global $_POST, $_GET,$_COOKIE,$tab,$cfg_features;
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
	// Override cfg_features by session value
	if (isset($_SESSION['list_limit'])) $cfg_features["list_limit"]=$_SESSION['list_limit'];

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

// Override list-limit if choosen
 if (isset($_POST['list_limit'])) {
       $_SESSION['list_limit']=$_POST['list_limit'];
       $cfg_features["list_limit"]=$_POST['list_limit'];
 }

	
	
$utils = array($lang["Menu"][17]=>array("/reservation/index.php","1"),
		$lang["Menu"][19]=>array("/knowbase/index.php"," "),
		$lang["Menu"][6]=>array("/reports/index.php"," "),
		);
	
$inventory = array($lang["Menu"][0]=>array("/computers/index.php","c"),
	              $lang["Menu"][1]=>array("/networking/index.php","n"),
	              $lang["Menu"][2]=>array("/printers/index.php","p"),
	              $lang["Menu"][3]=>array("/monitors/index.php","m"),
	              $lang["Menu"][4]=>array("/software/index.php","s"),
		      $lang["Menu"][16]=>array("/peripherals/index.php","r"),
		      $lang["Menu"][21]=>array("/cartridges/index.php","c"),
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
	echo "<td width='80'  valign='middle' align='center' >\n";
	echo "<a class='icon_logo' href=\"".$cfg_install["root"]."/central.php\" accesskey=\"0\"><img  src=\"".$HTMLRel."pics/logo-glpi.png\"  alt=\"".$cfg_layout["logotxt"]."\" title=\"".$lang["central"][5]."\"></a>";
	echo "<br><br><div style='width:80px; text-align:center;'><p class='nav_horl'><b>".$_SESSION["glpiname"]."</b></p></div>";
	echo "</td>\n";
	echo "<td valign='middle' style='padding-left:20px'>\n";
	
	// Get object-variables and build the navigation-elements
	echo "<table width='100%' cellspacing='0' cellpadding='0' border='0'><tr>";
	if ($navigation->inventory) {
		echo "<td align='center' valign='top' width='20%'>\n";
		echo "<img class='icon_nav' src=\"".$HTMLRel."pics/inventaire.png\" alt=\"\" title=\"".$lang["setup"][10]."\"><br>\n";
		echo "<span class='menu_title'>-&nbsp;".$lang["setup"][10]."&nbsp;-</span><br>\n";

		 echo "<table cellspacing='0' border='0' cellpadding='0'><tr><td>\n";
		$i=0;
		 foreach ($inventory as $key => $val) {
		 			if ($i%2==1) echo "</td><td style='border-left:1px groove #000000; border-right:1px groove #000000'>&nbsp;</td><td style='padding-left:5px; padding-right:5px;' align='center'>\n";
		 			else echo "</td></tr><tr><td style='padding-left:5px; padding-right:5px;' align='center'>\n";
                         
			 echo "<span class='menu'><a  href=\"".$cfg_install["root"].$val[0]."\" accesskey=\"".$val[1]."\">".$key."</a></span><br>\n";
                         $i++;
                   }
		echo "</td></tr></table>\n";
		echo "</td>\n";
	}
	if ($navigation->maintain) {
		echo "<td align='center' valign='top' width='20%'>\n";
		echo "<img class='icon_nav' src=\"".$HTMLRel."pics/maintenance.png\" alt=\"\" title=\"".$lang["title"][24]."\"><br>\n";

		echo "<span class='menu_title'>-&nbsp;".$lang["title"][24]."&nbsp;-</span><br>\n";
		foreach ($maintain as $key => $val) {
			echo "<span class='menu'><a  href=\"".$cfg_install["root"].$val[0]."\" accesskey=\"".$val[1]."\">".$key."</a></span><br>\n";
		}
		echo "</td>\n";
	}
	if ($navigation->financial) {
		echo "<td align='center' valign='top' width='20%'>\n";
		echo "<img class='icon_nav' src=\"".$HTMLRel."pics/gestion.png\" alt=\"\" title=\"".$lang["Menu"][26]."\"><br>\n";

		echo "<span class='menu_title'>-&nbsp;".$lang["Menu"][26]."&nbsp;-</span><br>\n";
		foreach ($financial as $key => $val) {
			echo "<span class='menu'><a  href=\"".$cfg_install["root"].$val[0]."\" accesskey=\"".$val[1]."\">".$key."</a></span><br>\n";
		}
		echo "</td>\n";
	}
	
	
	if ($navigation->utils) {
		echo "<td align='center' valign='top' width='20%'>\n";
		echo "<img class='icon_nav' src=\"".$HTMLRel."pics/outils.png\" alt=\"\" title=\"".$lang["Menu"][18]."\"><br>\n";
		echo "<span class='menu_title'>-&nbsp;".$lang["Menu"][18]."&nbsp;-</span><br>\n";
		foreach ($utils as $key => $val) {
			echo "<span class='menu'><a  href=\"".$cfg_install["root"].$val[0]."\" accesskey=\"".$val[1]."\">".$key."</a></span><br>\n";
		}
	echo "</td>\n";
	}

	// PLUGINS
	$dirplug=$phproot."/plugins";
	$dh  = opendir($dirplug);
	while (false !== ($filename = readdir($dh))) {
   	
   	if ($filename!="CVS"&&$filename!="."&&$filename!=".."&&is_dir($dirplug."/".$filename))
   	$plugins[]=$filename;
	}
	if (isset($plugins)&&count($plugins)>0){
		echo "<td align='center' valign='top'>\n";
		echo "<img class='icon_nav' src=\"".$HTMLRel."pics/plugins.png\" alt=\"\" title=\"".$lang["Menu"][15]."\"><br>\n";
		echo "<span class='menu_title'>-&nbsp;Plugins&nbsp;-</span><br>\n";
		foreach ($plugins as $key => $val) {
			echo "<span class='menu'><a  href=\"".$cfg_install["root"]."/plugins/".$val."/\" accesskey=\"".$val."\">".$val."</a></span><br>\n";
		}
		echo "</td>\n";
	}
	
	
	if ($navigation->settings) {
		echo "<td align='center' valign='top' width='20%'>\n";
		echo "<img class='icon_nav' src=\"".$HTMLRel."pics/config.png\" alt=\"\" title=\"".$lang["Menu"][15]."\"><br>\n";
		echo "<span class='menu_title'>-&nbsp;".$lang["Menu"][15]."&nbsp;-</span><br>\n";
		foreach ($config as $key => $val) {
			echo "<span class='menu'><a  href=\"".$cfg_install["root"].$val[0]."\" accesskey=\"".$val[1]."\">".$key."</a></span><br>\n";
		}
		echo "</td>\n";
	}
	
	
	
	
	// On the right side of the navigation bar, we have a clock with
	// date, help and a logout-link.

	echo "<td  align='center' valign='top' width='100'>\n";
	//help
	echo "<a class='icon_nav_move'  href='#' onClick=\"window.open('".$HTMLRel."help/".$cfg_install["languages"][$_SESSION["glpilanguage"]][2]."','helpdesk','width=750,height=600,scrollbars=yes')\"><img class='icon_nav' src=\"".$HTMLRel."pics/help.png\" alt=\"\" title=\"".$lang["central"][7]."\"></a>\n";
	echo "<p>".date("H").":".date("i")."<br><i>".date("j.")."&nbsp;".date("M")."&nbsp;".date("Y");
	echo "</i></p>\n";
	echo "<a  class='icon_nav_move' href=\"".$cfg_install["root"]."/logout.php\"><img  src=\"".$HTMLRel."pics/logout.png\" alt=\"".$lang["central"][6]."\" title=\"".$lang["central"][6]."\"></a>\n";
	
	echo "</td>\n";

	// End navigation bar

	echo "</tr></table>\n";

	// End headline

	
	echo "</td></tr>\n";	
	echo "</table>\n";
				echo "</div>\n";
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

	// Send UTF8 Headers
	header("Content-Type: text/html; charset=UTF-8");

	// Start the page
	echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">";
        echo "<html><head><title>GLPI Helpdesk - ".$title."</title>";
        echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8 \" >";
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

GLOBAL $cfg_install,$cfg_debug,$DEBUG_SQL_STRING,$TIMER_DEBUG,$SQL_TOTAL_TIMER,$SQL_TOTAL_REQUEST;

echo "<div id='footer' >";
echo "<table width='100%'><tr><td align='left'><span class='copyright'>";
echo $TIMER_DEBUG->Get_Time()."s</span>";
echo "</td><td align='right'>";
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
* Print a nice tab for last event from inventory section
*
* Print a great tab to present lasts events occured on glpi
*
*
* @param $target where to go when complete
* @param $order order by clause occurences (eg: ) 
* @param $sort order by clause occurences (eg: date) 
**/
function showAddEvents($target,$order,$sort,$user="") {
	// Show events from $result in table form

	GLOBAL $cfg_layout, $cfg_install, $cfg_features, $lang, $HTMLRel;

	// new database object
	$db = new DB;

	// define default sorting
	
	if (!$sort) {
		$sort = "date";
		$order = "DESC";
	}
	
	$usersearch="%";
	if (!empty($user))
	$usersearch=$user." ";
	
	// Query Database
	$query = "SELECT * FROM glpi_event_log WHERE message LIKE '".$usersearch."added%' ORDER BY $sort $order LIMIT 0,".$cfg_features["num_of_events"];

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

	echo "<div align='center'><br><table width='400' class='tab_cadre'>";
	echo "<tr><th colspan='5'>".$lang["central"][2]." ".$cfg_features["num_of_events"]." ".$lang["central"][8].":</th></tr>";
	echo "<tr>";

	echo "<th colspan='2'>";
	if ($sort=="item") {
		if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
		else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=item&amp;order=".($order=="ASC"?"DESC":"ASC")."\">".$lang["event"][0]."</a></th>";

	echo "<th>";
	if ($sort=="date") {
		if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
		else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=date&amp;order=".($order=="ASC"?"DESC":"ASC")."\">".$lang["event"][1]."</a></th>";

	echo "<th width='8%'>";
	if ($sort=="service") {
		if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
		else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=service&amp;order=".($order=="ASC"?"DESC":"ASC")."\">".$lang["event"][2]."</a></th>";

	echo "<th width='60%'>";
	if ($sort=="message") {
		if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
		else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=message&amp;order=".($order=="ASC"?"DESC":"ASC")."\">".$lang["event"][4]."</a></th></tr>";

	while ($i < $number) {
		$ID = $db->result($result, $i, "ID");
		$item = $db->result($result, $i, "item");
		$itemtype = $db->result($result, $i, "itemtype");
		$date = $db->result($result, $i, "date");
		$service = $db->result($result, $i, "service");
		//$level = $db->result($result, $i, "level");
		$message = $db->result($result, $i, "message");
		
		echo "<tr class='tab_bg_2'>";
		echo "<td>$itemtype:</td><td align='center'><b>";
		if ($item=="-1" || $item=="0") {
			echo $item;
		} else {
				if ($itemtype=="reservation"){
				echo "<a href=\"".$cfg_install["root"]."/$itemtype/index.php?show=resa&amp;ID=";
				} else {
				echo "<a href=\"".$cfg_install["root"]."/$itemtype/".$itemtype."-info-form.php?ID=";
				}
			echo $item;
			echo "\">$item</a>";
		}			
		echo "</b></td><td><span style='font-size:9px;'>$date</span></td><td align='center'>$service</td><td>$message</td>";
		echo "</tr>";

		$i++; 
	}

	echo "</table></div><br>";
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

	echo "<center><table width='90%' class='tab_cadre'>";
	echo "<tr><th colspan='6'>".$lang["central"][2]." ".$cfg_features["num_of_events"]." ".$lang["central"][3].":</th></tr>";
	echo "<tr>";

	echo "<th colspan='2'>";
	if ($sort=="item") {
		if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
		else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=item&amp;order=".($order=="ASC"?"DESC":"ASC")."\">".$lang["event"][0]."</a></th>";

	echo "<th>";
	if ($sort=="date") {
		if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
		else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=date&amp;order=".($order=="ASC"?"DESC":"ASC")."\">".$lang["event"][1]."</a></th>";

	echo "<th width='8%'>";
	if ($sort=="service") {
		if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
		else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=service&amp;order=".($order=="ASC"?"DESC":"ASC")."\">".$lang["event"][2]."</a></th>";

	echo "<th width='8%'>";
	if ($sort=="level") {
		if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
		else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=level&amp;order=".($order=="ASC"?"DESC":"ASC")."\">".$lang["event"][3]."</a></th>";

	echo "<th width='60%'>";
	if ($sort=="message") {
		if ($order=="DESC") echo "<img src=\"".$HTMLRel."pics/puce-down.png\" alt='' title=''>";
		else echo "<img src=\"".$HTMLRel."pics/puce-up.png\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=message&amp;order=".($order=="ASC"?"DESC":"ASC")."\">".$lang["event"][4]."</a></th></tr>";

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
				echo "<a href=\"".$cfg_install["root"]."/$itemtype/index.php?show=resa&amp;ID=";
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
	
	global $deleted_tables,$template_tables,$dropdowntree_tables;	
	
	// Make a select box
	$db = new DB;

	if($table == "glpi_dropdown_netpoint") {
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
		$where="WHERE '1'='1' ";
		if (in_array($table,$deleted_tables))
			$where.="AND deleted='N'";
		if (in_array($table,$template_tables))
			$where.="AND is_template='0'";			
		if (in_array($table,$dropdowntree_tables))
			$query = "SELECT ID, completename as name FROM $table $where ORDER BY completename";
		else $query = "SELECT * FROM $table $where ORDER BY name";
		$result = $db->query($query);
		echo "<select name=\"$myname\" size='1'>";
		echo "<option value=\"0\">-----</option>";
		$i = 0;
		$number = $db->numrows($result);
		if ($number > 0) {
			while ($i < $number) {
				$output = $db->result($result, $i, "name");
				if (empty($output)) $output="&nbsp;";
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
	
	global $deleted_tables,$template_tables,$dropdowntree_tables,$lang;
	
	// Make a select box with preselected values
	$db = new DB;

	if($table == "glpi_dropdown_netpoint") {
		$query = "select t1.ID as ID, t1.name as netpname, t2.ID as locID from glpi_dropdown_netpoint as t1";
		$query .= " left join glpi_dropdown_locations as t2 on t1.location = t2.ID";
		$query .= " order by t1.name,t2.name "; 
		$result = $db->query($query);
		// Get Location Array
		$query2="SELECT ID, completename FROM glpi_dropdown_locations";
		$result2 = $db->query($query2);
		$locat=array();
		if ($db->numrows($result2)>0)
		while ($a=$db->fetch_array($result2)){
			$locat[$a["ID"]]=$a["completename"];
		}

		echo "<select name=\"$myname\">";
		$i = 0;
		$number = $db->numrows($result);
		if ($number > 0) {
			while ($i < $number) {
				$output = $db->result($result, $i, "netpname");
				//$loc = getTreeValueCompleteName("glpi_dropdown_locations",$db->result($result, $i, "locID"));
				$loc=$locat[$db->result($result, $i, "locID")];
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
		

	if (in_array($table,$dropdowntree_tables))
		$query = "SELECT ID, completename as name FROM $table $where ORDER BY completename";
	else $query = "SELECT ID, name FROM $table $where ORDER BY name";
	
	$result = $db->query($query);
	
	echo "<select name=\"$myname\" size='1'>";
	if ($table=="glpi_dropdown_kbcategories")
	echo "<option value=\"0\">--".$lang["knowbase"][12]."--</option>";
	else echo "<option value=\"0\">-----</option>";
	
	$i = 0;
	$number = $db->numrows($result);
	if ($number > 0) {
		while ($i < $number) {
			$output = $db->result($result, $i, "name");
			if (empty($output)) $output="&nbsp;";
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

	global $deleted_tables,$template_tables,$dropdowntree_tables;

	$db = new DB;

	$where="";
	if (in_array($table,$deleted_tables))
		$where="WHERE deleted='N'";
	if (in_array($table,$template_tables))
		$where.="AND is_template='0'";
		
	if (in_array($table,$dropdowntree_tables))
		$query = "SELECT ID FROM $table $where ORDER BY completename";
	else $query = "SELECT ID FROM $table $where ORDER BY name";
	$result = $db->query($query);
	
	echo "<select name=\"$myname\" size='1'>";
	$i = 0;
	$number = $db->numrows($result);
	if ($number > 0) {
		while ($i < $number) {
			$ID = $db->result($result, $i, "ID");
			if ($ID === $value) {
			} else {
				echo "<option value=\"$ID\">".getDropdownName($table,$ID)."</option>";
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
			echo ">". $line["netpointname"]." (".getTreeValueCompleteName("glpi_dropdown_locations",$line["locID"]) .")</option>";
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
	global $deleted_tables,$template_tables;
	$db = new DB;

	$where="";
	if (in_array($table,$deleted_tables))
		$where.="AND deleted='N'";
	if (in_array($table,$template_tables))
		$where.="AND is_template='0'";	

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
function dropdownUsers($value, $myname,$all=0) {
	global $lang;
	// Make a select box with all glpi users

	$db = new DB;
	$query = "SELECT * FROM glpi_users WHERE (".searchUserbyType("normal").") ORDER BY name";
	$result = $db->query($query);

	echo "<select name=\"$myname\">";
	$i = 0;
	
	$number = $db->numrows($result);
	if ($all==0)
	echo "<option value=\"0\">[ Nobody ]</option>";
	else echo "<option value=\"0\">[ ".$lang["search"][7]." ]</option>";
	if ($number > 0) {
		while ($i < $number) {
			$output = unhtmlentities($db->result($result, $i, "name"));
			$ID = unhtmlentities($db->result($result, $i, "ID"));
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
function dropdownAssign($value, $value_type,$myname) {
	// Make a select box with all glpi users

	$db = new DB;
	$query = "SELECT * FROM glpi_users WHERE (".searchUserbyType("normal").") ORDER BY name";
	$result = $db->query($query);

	$query2 = "SELECT * FROM glpi_enterprises ORDER BY name";
	
	$result2 = $db->query($query2);
		
	
	echo "<select name=\"$myname\">";
	$i = 0;
	
	$number = $db->numrows($result);
	echo "<option value=\"15_0\">[ Nobody ]</option>";
	if ($number > 0) {
		while ($i < $number) {
			$output = unhtmlentities($db->result($result, $i, "name"));
			$ID = unhtmlentities($db->result($result, $i, "ID"));
			if ($value_type==USER_TYPE&&$ID == $value) {
				echo "<option value=\"".USER_TYPE."_$ID\" selected>".$output;
			} else {
				echo "<option value=\"".USER_TYPE."_$ID\">".$output;
			}
			$i++;
			echo "</option>";
   		}
	}
	echo "<option value=\"15_0\">-------</option>";
	$i=0;
	$number2 = $db->numrows($result2);
	if ($number2 > 0) {
		while ($i < $number2) {
			$output = unhtmlentities($db->result($result2, $i, "name"));
			$ID = unhtmlentities($db->result($result2, $i, "ID"));
			if ($value_type==ENTERPRISE_TYPE&&$ID == $value) {
				echo "<option value=\"".ENTERPRISE_TYPE."_$ID\" selected>".$output;
			} else {
				echo "<option value=\"".ENTERPRISE_TYPE."_$ID\">".$output;
			}
			$i++;
			echo "</option>";
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
function dropdownAllUsersSearch($value, $myname,$search) {
	// Make a select box with all glpi users

	$db = new DB;
	$query = "SELECT * FROM glpi_users WHERE name LIKE '%$search%' OR realname LIKE '%$search%' ORDER BY name";
	$result = $db->query($query);

	echo "<select name=\"$myname\">";
	$i = 0;
	
	$number = $db->numrows($result);
	echo "<option value=\"0\">[ Nobody ]</option>";
	if ($number > 0) {
		while ($i < $number) {
			$output = unhtmlentities($db->result($result, $i, "name"));
			$ID = unhtmlentities($db->result($result, $i, "ID"));
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

	dropdownUsers($value, $myname);
/*	$db = new DB;
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
*/	
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
	global $cfg_install,$dropdowntree_tables;
	$db = new DB;
	$name = "";
	$query = "select * from ". $table ." where ID = '". $id ."'";
	if ($result = $db->query($query))
	if($db->numrows($result) != 0) {
		if (in_array($table,$dropdowntree_tables)){
		$name=getTreeValueCompleteName($table,$id);
	
		} else {
		$name = $db->result($result,0,"name");
		if ($table=="glpi_dropdown_netpoint")
			$name .= " (".getDropdownName("glpi_dropdown_locations",$db->result($result,0,"location")).")";
		}
		if ($table=="glpi_enterprises"){
			$name.=getEnterpriseLinks($id);	
		}
	}
	if (empty($name)) return "&nbsp;";
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



function dropdownDeviceType($name,$device_type){
global $lang;
echo "<select name='$name'>";
	echo "<option value='0'>-----</option>";
    	echo "<option value='".COMPUTER_TYPE."' ".(($device_type==COMPUTER_TYPE)?" selected":"").">".$lang["help"][25]."</option>";
	echo "<option value='".NETWORKING_TYPE."' ".(($device_type==NETWORKING_TYPE)?" selected":"").">".$lang["help"][26]."</option>";
	echo "<option value='".PRINTER_TYPE."' ".(($device_type==PRINTER_TYPE)?" selected":"").">".$lang["help"][27]."</option>";
	echo "<option value='".MONITOR_TYPE."' ".(($device_type==MONITOR_TYPE)?" selected":"").">".$lang["help"][28]."</option>";
	echo "<option value='".PERIPHERAL_TYPE."' ".(($device_type==PERIPHERAL_TYPE)?" selected":"").">".$lang["help"][29]."</option>";
	echo "<option value='".SOFTWARE_TYPE."' ".(($device_type==SOFTWARE_TYPE)?" selected":"").">".$lang["help"][31]."</option>";
	echo "<option value='".CARTRIDGE_TYPE."' ".(($device_type==CARTRIDGE_TYPE)?" selected":"").">".$lang["Menu"][21]."</option>";
	echo "<option value='".CONTACT_TYPE."' ".(($device_type==CONTACT_TYPE)?" selected":"").">".$lang["Menu"][22]."</option>";
	echo "<option value='".ENTERPRISE_TYPE."' ".(($device_type==ENTERPRISE_TYPE)?" selected":"").">".$lang["Menu"][23]."</option>";
	echo "<option value='".CONTRACT_TYPE."' ".(($device_type==CONTRACT_TYPE)?" selected":"").">".$lang["Menu"][25]."</option>";
	//echo "<option value='".USER_TYPE."' ".(($device_type==USER_TYPE)?" selected":"").">".$lang["Menu"][14]."</option>";
	echo "</select>";


}

function getDeviceTypeName($ID){
global $lang;
switch ($ID){
	case COMPUTER_TYPE : return $lang["help"][25];break;
	case NETWORKING_TYPE : return $lang["help"][26];break;
	case PRINTER_TYPE : return $lang["help"][27];break;
	case MONITOR_TYPE : return $lang["help"][28];break;
	case PERIPHERAL_TYPE : return $lang["help"][29];break;
	case SOFTWARE_TYPE : return $lang["help"][31];break;
	case CARTRIDGE_TYPE : return $lang["Menu"][21];break;
	case CONTACT_TYPE : return $lang["Menu"][22];break;
	case ENTERPRISE_TYPE : return $lang["Menu"][23];break;
	case CONTRACT_TYPE : return $lang["Menu"][25];break;
	//case USER_TYPE : return $lang["Menu"][14];break;


}

}


/**
* 
*
*
*
* @param $name
* @param $withenterprise
* @param $search
* @param $value
* @return nothing (print out an HTML select box)
*/
function dropdownAllItems($name,$withenterprise=0,$search='',$value='') {
	global $deleted_tables, $template_tables;
	
	$db=new DB;
	
	$items=array(
	COMPUTER_TYPE=>"glpi_computers",
	NETWORKING_TYPE=>"glpi_networking",
	PRINTER_TYPE=>"glpi_printers",
	MONITOR_TYPE=>"glpi_monitors",
	PERIPHERAL_TYPE=>"glpi_peripherals",
	SOFTWARE_TYPE=>"glpi_software",
	);

	if ($withenterprise==1) $items[ENTERPRISE_TYPE]="glpi_enterprises";
	
	echo "<select name=\"$name\" size='1'>";
	echo "<option value='0'>-----</option>";
	$ci=new CommonItem;

	foreach ($items as $type => $table){
		$ci->setType($type);
		$where="WHERE '1' = '1' ";
		
		if (in_array($table,$deleted_tables))
			$where.= " AND deleted='N' ";
		
		if (in_array($table,$template_tables))
			$where.= " AND is_template='0' ";
		
		
		if (!empty($search))
		$where.="AND name LIKE '%$search%' ";
		
//	if ($table=="glpi_enterprises"||$table=="glpi_cartridge_type")
//		$where = "WHERE deleted='N' ";

		$query = "SELECT ID,name FROM $table $where ORDER BY name";
	//echo $query;
		$result = $db->query($query);
	
		$i = 0;
		$number = $db->numrows($result);
	
		if ($number > 0) {
			while ($i < $number) {
				$ID=$db->result($result, $i, "ID");
				$name=$db->result($result, $i, "name");
				$output=$ci->getType()." - ".$name;
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

	GLOBAL $lang,$cfg_install,$cfg_debug;

	if(empty($_SESSION["glpilanguage"])) {
		$file= "/glpi/dicts/".$cfg_install["languages"][$cfg_install["default_language"]][1];
	} else {
		$file = "/glpi/dicts/".$cfg_install["languages"][$_SESSION["glpilanguage"]][1];
	}
		include ("_relpos.php");
		include ($phproot . $file);
		
	// Debug display lang element with item
	if ($cfg_debug["active"]&&$cfg_debug["lang"]){
		foreach ($lang as $module => $tab)
		foreach ($tab as $num => $val){
			$lang[$module][$num].="<span style='font-size:4px; color:red;'>$module/$num</span>";
		
		}
	}

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

		// Is global connection ?
		$global=0;
		if ($type==PERIPHERAL_TYPE){
			$periph=new Peripheral;
			$periph->getFromDB($ID);
			$global=$periph->fields['is_global'];
		} else if ($type==MONITOR_TYPE){
			$mon=new Monitor;
			$mon->getFromDB($ID);
			$global=$mon->fields['is_global'];
		}
		
		$connect->type=$type;
		$computers = $connect->getComputerContact($ID);

		echo "<br><center><table width='50%' class='tab_cadre'><tr><th colspan='2'>";
		echo $lang["connect"][0].":";
		echo "</th></tr>";

		if ($computers&&count($computers)>0) {
			foreach ($computers as $key => $computer){
				$connect->getComputerData($computer);
				echo "<tr><td class='tab_bg_1".($connect->deleted=='Y'?"_2":"")."'><b>Computer: ";
				echo "<a href=\"".$cfg_install["root"]."/computers/computers-info-form.php?ID=".$connect->device_ID."\">";
				echo $connect->device_name." (".$connect->device_ID.")";
				echo "</a>";
				echo "</b></td>";
				echo "<td class='tab_bg_2".($connect->deleted=='Y'?"_2":"")."' align='center'><b>";
				echo "<a href=\"$target?disconnect=1&amp;ID=".$key."\">".$lang["connect"][3]."</a>";
			}
		} else {
			echo "<tr><td class='tab_bg_1'><b>Computer: </b>";
			echo "<i>".$lang["connect"][1]."</i>";
			echo "</td>";
			echo "<td class='tab_bg_2' align='center'><b>";
			echo "<a href=\"$target?connect=1&amp;ID=$ID\">".$lang["connect"][2]."</a>";
		}

		if ($global&&$computers&&count($computers)>0){
			echo "</b></td>";
			echo "</tr>";
			echo "<tr><td class='tab_bg_1'>&nbsp;";
			echo "</td>";
			echo "<td class='tab_bg_2' align='center'><b>";
			echo "<a href=\"$target?connect=1&amp;ID=$ID\">".$lang["connect"][2]."</a>";
		}

		echo "</b></td>";
		echo "</tr>";
		echo "</table></center><br>";
}

/**
* Disconnects a direct connection
* 
*
* @param $ID the connection ID to disconnect.
* @return nothing
*/
function Disconnect($ID) {
	// Disconnects a direct connection

	$connect = new Connection;
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

	if (!isset($dev->obj->fields["is_global"])||!$dev->obj->fields["is_global"]){
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
			$dev->obj->fields['contact']=unhtmlentities($comp->fields['contact']);
			$dev->obj->fields['contact_num']=unhtmlentities($comp->fields['contact_num']);
			$dev->obj->updateInDB($updates);
			$_SESSION["MESSAGE_AFTER_REDIRECT"]=$lang["computers"][49];
		}
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
	echo "<option value=serial>".$lang["connect"][23]."</option>";
	echo "<option value=otherserial>".$lang["connect"][24]."</option>";
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
	
		
	$query = "SELECT glpi_computers.ID as ID,glpi_computers.name as name, glpi_dropdown_locations.ID as location from glpi_computers left join glpi_dropdown_locations on glpi_computers.location = glpi_dropdown_locations.id WHERE glpi_computers.deleted = 'N' AND glpi_computers.is_template ='0' AND glpi_computers.".$input["type"]." LIKE '%".$input["search"]."%' order by name ASC";
	
	$result = $db->query($query);
	$number = $db->numrows($result);
	echo "<select name=\"cID\">";
	while ($i < $number) {
		$dID = $db->result($result, $i, "ID");
		$name = $db->result($result, $i, "name");
		$location = $db->result($result, $i, "location");
		echo "<option value=\"$dID\">".$name." (".getTreeValueCompleteName("glpi_dropdown_locations",$location).")</option>";
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

	$CONNECT_SEARCH="(glpi_connect_wire.ID IS NULL";	
	if ($device_type=="monitor"||$device_type=="peripheral")
		$CONNECT_SEARCH.=" OR $table.is_global='1' ";
	$CONNECT_SEARCH.=")";
	$query = "SELECT $table.ID as ID,$table.name as name, glpi_dropdown_locations.ID as location from $table left join glpi_dropdown_locations on $table.location = glpi_dropdown_locations.id left join glpi_connect_wire on ($table.ID = glpi_connect_wire.end1 AND glpi_connect_wire.type = $device_id) WHERE $table.deleted='N' AND $table.is_template='0' AND $table.".$input["type"]." LIKE '%".$input["search"]."%' AND $CONNECT_SEARCH order by name ASC";
	
	
	//echo $query;
	$result = $db->query($query);
	$number = $db->numrows($result);
	if ($number>0) {
	echo "<select name=\"ID\">";
	while ($i < $number) {
		$dID = $db->result($result, $i, "ID");
		$name = $db->result($result, $i, "name");
		$location = $db->result($result, $i, "location");
		echo "<option value=\"$dID\">".$name." (".getTreeValueCompleteName("glpi_dropdown_locations",$location).")</option>";
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

function dropdownTrackingDeviceType($name,$value){
	global $lang;
	echo "<select name='$name'>";
    //if (isAdmin($_SESSION["glpitype"]))
    echo "<option value='0' >".$lang["help"][30]."";
	echo "<option value='".COMPUTER_TYPE."' ".(($value==COMPUTER_TYPE)?" selected":"").">".$lang["help"][25]."";
	echo "<option value='".NETWORKING_TYPE."' ".(($value==NETWORKING_TYPE)?" selected":"").">".$lang["help"][26]."";
	echo "<option value='".PRINTER_TYPE."' ".(($value==PRINTER_TYPE)?" selected":"").">".$lang["help"][27]."";
	echo "<option value='".MONITOR_TYPE."' ".(($value==MONITOR_TYPE)?" selected":"").">".$lang["help"][28]."";
	echo "<option value='".PERIPHERAL_TYPE."' ".(($value==PERIPHERAL_TYPE)?" selected":"").">".$lang["help"][29]."";
	echo "<option value='".SOFTWARE_TYPE."' ".(($value==SOFTWARE_TYPE)?" selected":"").">".$lang["help"][31]."";
	echo "</select>";
		
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
        $device_type = COMPUTER_TYPE;
		$computer="";
		$contents="";
		
		if (isset($_SESSION["helpdeskSaved"]["emailupdates"]))
               $emailupdates = $_SESSION["helpdeskSaved"]["emailupdates"];
		if (isset($_SESSION["helpdeskSaved"]["email"]))
                $email = $_SESSION["helpdeskSaved"]["uemail"];
		if (isset($_SESSION["helpdeskSaved"]["computer"]))
                $computer = $_SESSION["helpdeskSaved"]["computer"];
		if (isset($_SESSION["helpdeskSaved"]["device_type"]))
                $device_type = $_SESSION["helpdeskSaved"]["device_type"];
		if (isset($_SESSION["helpdeskSaved"]["contents"]))
                $contents = $_SESSION["helpdeskSaved"]["contents"];


	echo "<form method='post' name=\"helpdeskform\" action=\"".$cfg_install["root"]."/tracking/tracking-injector.php\"  enctype=\"multipart/form-data\">";
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
	echo "<td>".$lang["help"][12]." <img src=\"".$cfg_install["root"]."/pics/aide.png\" style='cursor:pointer;' alt=\"help\"onClick=\"window.open('".$cfg_install["root"]."/find_num.php','Help','scrollbars=1,resizable=1,width=600,height=600')\"></td>";
	echo "<td><input name='computer' size='10' value='$computer'>";
	echo "</td>";
	echo "</tr>";

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
	
	echo "<tr class='tab_bg_1'><td>".$lang["document"][2]." (".$max_size."Mo max):	</td>";
	echo "<td colspan='2'><input type='file' name='filename' value=\"\" size='25'></td>";
	echo "</tr>";

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
		echo "<table width='100%' class='tab_cadre'>";
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
						$contract_type = getDropdownName("glpi_dropdown_contract_type",$ligne["contract_type"]);
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
		echo "<table width='100%' class='tab_cadre'>";
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
						$contract_type = getDropdownName("glpi_dropdown_contract_type",$ligne["contract_type"]);
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
		echo "<table width='100%' class='tab_cadre'>";
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
						$contract_type = getDropdownName("glpi_dropdown_contract_type",$ligne["contract_type"]);
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
		echo "<table width='100%' class='tab_cadre'>";
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
						$contract_type = getDropdownName("glpi_dropdown_contract_type",$ligne["contract_type"]);
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
		echo "<table width='100%' class='tab_cadre'>";
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
						$contract_type = getDropdownName("glpi_dropdown_contract_type",$ligne["contract_type"]);
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
		echo "<table width='100%' class='tab_cadre'>";
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
						$contract_type = getDropdownName("glpi_dropdown_contract_type",$ligne["contract_type"]);
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
		echo "<table width='100%' class='tab_cadre'>";
		echo "<tr> ";
		echo "<th><div align='center'><b>".$lang["reports"][20]."</b></div></th>";
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
					$lieu=getTreeValueCompleteName("glpi_dropdown_locations",$ligne["location"]);
					//echo $ligne['location'];
					//print_r($ligne);
					$prise=$ligne['prise'];
					$port=$ligne['port'];
					$nw=new NetWire();
					$end1=$nw->getOppositeContact($ligne['IDport']);
					$np=new Netport();

					$ordi="";
					$ip2="";
					$mac2="";
					$portordi="";

					if ($end1){
						$np->getFromDB($end1);
						$np->getDeviceData($np->fields["on_device"],$np->fields["device_type"]);
						$ordi=$np->device_name;
						$ip2=$np->fields['ifaddr'];
						$mac2=$np->fields['ifmac'];
						$portordi=$np->fields['name'];
					}

					$ip=$ligne['ip'];
					$mac=$ligne['mac'];

					$np=new Netport();
					$np->getFromDB($ligne['IDport']);

					$nd=new Netdevice();
					$nd->getFromDB($np->fields["on_device"]);
					$switch=$nd->fields["name"];
					

					//inserer ces valeures dans un tableau
					
					echo "<tr>";	
					if($lieu) echo "<td><div align='center'>$lieu</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
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
		echo "<table width='100%' class='tab_cadre'>";
		echo "<tr> ";
		echo "<th><div align='center'>&nbsp;</div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][46]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][38]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][53]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][47]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][38]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][53]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["reports"][36]."</b></div></th>";
		echo "</tr>";
		
		while( $ligne = $db->fetch_array($result))
					{
					$switch = $ligne['switch'];
					//echo $ligne['location'];
					//$prise=$ligne['prise'];
					$port = $ligne['port'];
					$nw=new NetWire();
					$end1=$nw->getOppositeContact($ligne['IDport']);
					$np=new Netport();

					$ip2="";
					$mac2="";
					$portordi="";
					$ordi="";

					if ($end1){
						$np->getFromDB($end1);
						$np->getDeviceData($np->fields["on_device"],$np->fields["device_type"]);
						$ordi=$np->device_name;
						$ip2=$np->fields['ifaddr'];
						$mac2=$np->fields['ifmac'];
						$portordi=$np->fields['name'];
					} 

					$ip=$ligne['ip'];
					$mac=$ligne['mac'];
					//inserer ces valeures dans un tableau
					
					echo "<tr>";	
					if($switch) echo "<td><div align='center'>$switch</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
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
		echo "<table width='100%' class='tab_cadre'>";
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
					$nw=new NetWire();
					$end1=$nw->getOppositeContact($ligne['IDport']);
					$np=new Netport();

					$ordi="";
					$ip2="";
					$mac2="";
					$portordi="";

					if ($end1){
						$np->getFromDB($end1);
						$np->getDeviceData($np->fields["on_device"],$np->fields["device_type"]);
						$ordi=$np->device_name;
						$ip2=$np->fields['ifaddr'];
						$mac2=$np->fields['ifmac'];
						$portordi=$np->fields['name'];
					}

					$ip=$ligne['ip'];
					$mac=$ligne['mac'];
					$port=$ligne['port'];
					$np=new Netport();
					$np->getFromDB($ligne['IDport']);

					$nd=new Netdevice();
					$nd->getFromDB($np->fields["on_device"]);
					$switch=$nd->fields["name"];
					
					
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
* To be commented
*
* @param $table
* @param $ID
* @return nothing 
*/
function getTreeValueCompleteName($table,$ID)
{
	$query = "select * from $table where (ID = $ID)";
	$db=new DB;
	$name="";
	if ($result=$db->query($query)){
		if ($db->numrows($result)==1){
			$name=$db->result($result,0,"completename");
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
// DO NOT DELETE THIS FUNCTION : USED IN THE UPDATE
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
			$name = $row["name"] . ">";
		}
		$name = getTreeValueName($table,$parentID, $name) . $name;
	}
	
	}
return (@$name);
}

/**
* Get the equivalent search query using ID that the search of the string argument
*
* @param $table
* @param $search the search string value
* @return string the query
*/
function getRealSearchForTreeItem($table,$search){

return " ( $table.completename LIKE '%$search%' ) ";

/*if (empty($search)) return " ( $table.name LIKE '%$search%' ) ";

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
*/
}



/**
* Get the equivalent search query using ID of soons that the search of the father's ID argument
*
* @param $table
* @param $IDf The ID of the father
* @return string the query
*/
function getRealQueryForTreeItem($table,$IDf){

if (empty($IDf)) return "";

$db=new DB();

// IDs to be present in the final query
$id_found=array();
// current ID found to be added
$found=array();

// First request init the  varriables
$query="SELECT ID from $table WHERE ID = '$IDf'";
if ( ($result=$db->query($query)) && ($db->numrows($result)>0) ){
	while ($row=$db->fetch_array($result)){
		array_push($id_found,$row['ID']);
		array_push($found,$row['ID']);
	}
} else return " ( $table.ID = '$IDf') ";

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
}else return " ( $table.ID = '$IDf') ";
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
* @param $table
* @return nothing
*/
function regenerateTreeCompleteName($table){
	$db=new DB;
	$query="SELECT ID from $table";
	$result=$db->query($query);
	if ($db->numrows($result)>0){
		while ($data=$db->fetch_array($result)){
		$query="UPDATE $table SET completename='".addslashes(unhtmlentities(getTreeValueName("$table",$data['ID'])))."' WHERE ID='".$data['ID']."'";
		$db->query($query);
		}
	}
}

/**
* To be commented
*
* @param $table
* @param $ID
* @return nothing
*/
function regenerateTreeCompleteNameUnderID($table,$ID){
	$db=new DB;
	$query="UPDATE $table SET completename='".addslashes(unhtmlentities(getTreeValueName("$table",$ID)))."' WHERE ID='".$ID."'";
	$db->query($query);
	$query="SELECT ID FROM $table WHERE parentID='$ID'";
	$result=$db->query($query);
	if ($db->numrows($result)>0){
		while ($data=$db->fetch_array($result)){
			regenerateTreeCompleteNameUnderID($table,$data["ID"]);
		}
	}
	
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
	logEvent($file, "sendFile", 1, "security", $_SESSION["glpiname"]." try to get a non standard file.");
	return;
	}
	if (!file_exists($file)){
	echo "Error file $file does not exist";
	return;
	} else {
		$db = new DB;
		$splitter=split("/",$file);
		$filedb=$splitter[count($splitter)-2]."/".$splitter[count($splitter)-1];
		$query="SELECT mime from glpi_docs WHERE filename LIKE '$filedb'";
		$result=$db->query($query);
		$mime="application/octetstream";
		if ($result&&$db->numrows($result)==1){
			$mime=$db->result($result,0,0);
			
		} else {
			// fichiers DUMP SQL et XML
			if ($splitter[count($splitter)-2]=="dump"){
				$splitter2=split("\.",$file);
				switch ($splitter2[count($splitter2)-1]) {
					case "sql" : 
						$mime="text/x-sql";
						break;
					case "xml" :
						$mime="text/xml";
						break;
				}
			} else {
				// Cas particulier
				switch ($splitter[count($splitter)-2]) {
					case "SQL" : 
						$mime="text/x-sql";
						break;
					case "XML" :
						$mime="text/xml";
						break;
				}
			}
			
		}
		
		header("Content-disposition: filename=$filename");
		
        	header("Content-type: ".$mime);
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
global $deleted_tables,$template_tables;

$query = "select ID from $table where ID > $ID ";

if (in_array($table,$deleted_tables))
	$query.="AND deleted='N'";
if (in_array($table,$template_tables))
	$query.="AND is_template='0'";	
		
$query.=" order by ID";

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
global $deleted_tables,$template_tables;

$query = "select ID from $table where ID < $ID ";

if (in_array($table,$deleted_tables))
	$query.="AND deleted='N'";
if (in_array($table,$template_tables))
	$query.="AND is_template='0'";	
		
$query.=" order by ID DESC";


$db=new DB;
$result=$db->query($query);
if ($db->numrows($result)>0)
	return $db->result($result,0,"ID");
else return -1;

}


function return_bytes_from_ini_vars($val) {
   $val = trim($val);
   $last = strtolower($val{strlen($val)-1});
   switch($last) {
       // Le modifieur 'G' est disponible depuis PHP 5.1.0
       case 'g':
           $val *= 1024;
       case 'm':
           $val *= 1024;
       case 'k':
           $val *= 1024;
   }

   return $val;
}

function glpi_header($dest){
echo "<script language=javascript>window.location=\"".$dest."\"</script>";
exit();
}

function getMultiSearchItemForLink($name,$array){
	
	$out="";
	if (is_array($array)&&count($array)>0)
	foreach($array as $key => $val){
		if ($name!="link"||$key!=0)
			$out.="&amp;".$name."[$key]=".$array[$key];
	}
	return $out;
	
}

function getUserName($ID,$link=0){
	global $cfg_install;
	$user=new User();
	$user->getFromDBbyID($ID);
	$before="";
	$after="";
	if ($link){
		$before="<a href=\"".$cfg_install["root"]."/users/users-info.php?ID=".$ID."\">";
		$after="</a>";
	}
	return $before.$user->getName().$after;
}

function get_hour_from_sql($time){
$t=explode(" ",$time);
$p=explode(":",$t[1]);
return $p[0].":".$p[1];
}

?>
