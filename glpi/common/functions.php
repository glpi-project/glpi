<?php
/*
 
  ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
 Bazile Lebeau, baaz@indepnet.net - Jean-Mathieu Doléans, jmd@indepnet.net
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------
 Based on:
IRMA, Information Resource-Management and Administration
Christian Bauer, turin@incubus.de 

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
 ----------------------------------------------------------------------
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
*/
function can_assign_job($name)
{
  $db = new DB;
  $query = "SELECT * FROM glpi_users WHERE (name = '".$name."')";
	$result = $db->query($query);
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

function isSuperAdmin($authtype) {
	switch ($authtype){
			case "super-admin":
			return true;
			break;
		default :
			return false;
		}
}
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

function checkAuthentication($authtype) {
	// Checks a GLOBAL user and password against the database
	// If $authtype is "normal" or "admin", it checks if the user
	// has the privileges to do something. Should be used in every 
	// control-page to set a minium security level.
	if(!isset($_SESSION)) session_start();
	
	GLOBAL $cfg_install, $lang, $HTMLRel;

	if(empty($_SESSION["authorisation"]))
	{
		nullHeader("Login",$_SERVER["PHP_SELF"]);
		echo "<div align='center'><b><a href=\"".$cfg_install["root"]."/logout.php\">Relogin</a></b></div>";
		nullFooter();
		die();	
	}

	
	// New database object
//	print_r($_SESSION);
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
				if (!isPostOnly($type))
				{
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

function commonHeader($title,$url)
{
	// Print a nice HTML-head for every page

	GLOBAL $cfg_install,$lang, $cfg_layout,$cfg_features,$HTMLRel,$phproot ;
	
	
	
	
$inventory = 	array($lang["Menu"][0]=>array("/computers/index.php","1"),
	              $lang["Menu"][1]=>array("/networking/index.php","2"),
	              $lang["Menu"][2]=>array("/printers/index.php","3"),
	              $lang["Menu"][3]=>array("/monitors/index.php","4"),
	              $lang["Menu"][4]=>array("/software/index.php","5"),
		      $lang["Menu"][16]=>array("/peripherals/index.php","6"));

$maintain =	array($lang["Menu"][5]=>array("/tracking/index.php","6"),
	              $lang["Menu"][6]=>array("/reports/index.php"," "),
		      $lang["Menu"][13]=>array("/stats/index.php"," "));

				
$config =	array($lang["Menu"][14]=>array("/setup/setup-users.php"," "),
		       $lang["Menu"][10]=>array("/setup/index.php"," "),
		      $lang["Menu"][11]=>array("/preferences/index.php"," "),
	              $lang["Menu"][12]=>array("/backups/index.php"," "));

	// Send extra expires header if configured
	if ($cfg_features["sendexpire"]) {
		header("Expires: Fri, Jun 12 1981 08:20:00 GMT\nPragma: no-cache");
	}

	// Start the page
	echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">";
	echo "<html><head><title>glpi: ".$title."</title>";
        echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1 \" >";
       
	// Send extra expires header if configured
	if ($cfg_features["sendexpire"]) {
		echo "<meta http-equiv=\"Expires\" content=\"Fri, Jun 12 1981 08:20:00 GMT\">\n";
		echo "<meta http-equiv=\"Pragma\" content=\"no-cache\">\n";
		echo "<meta http-equiv=\"Cache-Control\" content=\"no-cache\">\n";
	}

	//  Appel  CSS
	
         echo "<link rel='stylesheet'  href='".$HTMLRel."styles.css' type='text/css' media='screen' >";

	

	// Some Javascript-Functions which we may need later
	echo "<script type=\"text/javascript\" language=\"JavaScript\">";
	echo "function jumpTo(URL_List){ var URL = URL_List.options[URL_List.selectedIndex].value;  window.location.href = URL; }\n\n";
	echo "browserName=navigator.appName;";
  	echo "browserVer=parseInt(navigator.appVersion);";
	echo "if ((browserName==\"Netscape\" && browserVer>=3) || (browserName==\"Microsoft Internet Explorer\" && browserVer>=4)) version=\"n3\";";
	echo "else version=\"n2\"; function historyback() { history.back(); } function historyforward() { history.forward(); }";
	echo "</script>";
	// End of Head
	echo "</head>\n";
	
	// Body with configured stuff
	echo "<body>";

	// Main Headline
	echo "<div id='navigation'>";
	echo "<table  cellspacing='0' border='0' width='98%'>";
	echo "<tr>";
	
	// Logo with link to command center
	echo "<td align='center' width='25%' >\n";
	echo "<a href=\"".$cfg_install["root"]."/central.php\" accesskey=\"0\"><img src=\"".$HTMLRel."pics/logo-glpi.png\"  alt=\"".$cfg_layout["logotxt"]."\" title=\"".$lang["central"][5]."\"></a>";
	echo "</td>";

	echo "<td valign='middle'>";
	
	// New object from the configured base functions, we check some
	// object-variables in this object: inventory, maintain, admin
	// and settings. We build the navigation bar here.

	$navigation = new baseFunctions;

	// Get object-variables and build the navigation-elements
	echo "<table width='100%' cellspacing='0' cellpadding='0' border='0'><tr>";
	if ($navigation->inventory) {
		echo "<td align='center' valign='top'>";
		echo "<img class='icon_nav' src=\"".$HTMLRel."pics/inventaire.png\" alt=\"\" title=\"".$lang["setup"][10]."\"><br>";
		echo "<small>-&nbsp;".$lang["setup"][10]."&nbsp;-</small><br>";

		 foreach ($inventory as $key => $val) {
                         echo "<span class='menu'><a  href=\"".$cfg_install["root"].$val[0]."\" accesskey=\"".$val[1]."\">".$key."</a></span><br>";
                   }

		echo "</td>";
	}
	 if ($navigation->maintain) {
		echo "<td align='center' valign='top'>";
				echo "<img class='icon_nav' src=\"".$HTMLRel."pics/maintenance.png\" alt=\"\" title=\"".$lang["setup"][55]."\"><br>";

		echo "<small>-&nbsp;".$lang["setup"][55]."&nbsp;-</small><br>";
		foreach ($maintain as $key => $val) {
			echo "<span class='menu'><a  href=\"".$cfg_install["root"].$val[0]."\" accesskey=\"".$val[1]."\">".$key."</a></span><br>";
		}
		echo "</td>";
	}
	if ($navigation->settings) {
		echo "<td align='center' valign='top'>";
				echo "<img class='icon_nav' src=\"".$HTMLRel."pics/config.png\" alt=\"\" title=\"".$lang["Menu"][15]."\"><br>";

		echo "<small>-&nbsp;".$lang["Menu"][15]."&nbsp;-</small><br>";
		foreach ($config as $key => $val) {
			echo "<span class='menu'><a  href=\"".$cfg_install["root"].$val[0]."\" accesskey=\"".$val[1]."\">".$key."</a></span><br>";
		}	
		echo "</td>";
	}
	
	/* HELP
	
	echo "<td align='center' valign='top'>";
				echo "<a class='icon_nav_move'  target=_blank href=\"".$HTMLRel."help/".$_SESSION["glpilanguage"].".html\"><img class='icon_nav' src=\"".$HTMLRel."pics/help.png\" alt=\"\" title=\"".$lang["central"][7]."\"></a><br>";

	echo "</td>";
	
	*/
	
	// On the right side of the navigation bar, we have a clock with
	// date and a logout-link.

	echo "<td  align='right' valign='top' width='100'>";
	echo "<a class='icon_nav_move'  target=_blank href=\"".$HTMLRel."help/".$_SESSION["glpilanguage"].".html\"><img class='icon_nav' src=\"".$HTMLRel."pics/help.png\" alt=\"\" title=\"".$lang["central"][7]."\"></a><br><br>";
	echo date("H").":".date("i")."<p><i>".date("j.")."&nbsp;".date("M")."&nbsp;".date("Y");
	echo "</i></p>";
	echo "<a  class='icon_nav_move' href=\"".$cfg_install["root"]."/logout.php\"><img  src=\"".$HTMLRel."pics/logout.png\" alt=\"".$lang["central"][6]."\" title=\"".$lang["central"][6]."\"></a></td>";

	// End navigation bar

	echo "</tr></table>";

	// End headline

	
	echo "</td></tr>";	
echo "</table>\n";
				echo "</div>";
}


function helpHeader($title,$url,$name) {
	// Print a nice HTML-head for help page

	GLOBAL $cfg_layout,$cfg_install,$lang,$cfg_features,$HTMLRel,$phproot ;

	// Send extra expires header if configured
	if ($cfg_features["sendexpire"]) {
		header("Expires: Fri, Jun 12 1981 08:20:00 GMT\nPragma: no-cache");
	}

	// Start the page
	echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">";
        echo "<html><head><title>GLPI Internal Helpdesk : ".$title."</title>";
        echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1 \" >";

	// Send extra expires header if configured
	if ($cfg_features["sendexpire"]) {
	        echo "<meta http-equiv=\"Expires\" content=\"Fri, Jun 12 1981 08:20:00 GMT\">\n";
		echo "<meta http-equiv=\"Pragma\" content=\"no-cache\">\n";
		echo "<meta http-equiv=\"Cache-Control\" content=\"no-cache\">\n";
	}

	// Some Javascript-Functions which we may need later
	echo "<script type=\"text/javascript\" language=\"JavaScript\">";
	echo "function jumpTo(URL_List){ var URL = URL_List.options[URL_List.selectedIndex].value;  window.location.href = URL; }\n\n";
	echo "browserName=navigator.appName;";
  	echo "browserVer=parseInt(navigator.appVersion);";
	echo "if ((browserName==\"Netscape\" && browserVer>=3) || (browserName==\"Microsoft Internet Explorer\" && browserVer>=4)) version=\"n3\";";
	echo "else version=\"n2\"; function historyback() { history.back(); } function historyforward() { history.forward(); }";
	echo "</script>";
	
	// Appel CSS
	
        echo "<link rel='stylesheet'  href='".$HTMLRel."styles.css' type='text/css' media='screen' >";

	
	// End of Head
	echo "</head>\n";
	
	// Body with configured stuff
	echo "<body>";

	// Main Headline
				echo "<div id='navigation'>";

	echo "<table cellspacing='0' border='0' width='98%'>";
	echo "<tr>";
	
	// Logo with link to command center
	echo "<td align='center' width='25%'>\n";
	
	echo "<img src=\"".$HTMLRel."pics/logo-glpi.png\"  alt=\"".$cfg_layout["logotxt"]."\" title=\"".$lang["central"][5]."\" >";

        echo "</td>";

	echo "<td valign='middle'>";

	echo "<table width='100%' cellspacing='0' cellpadding='0' border='0'><tr>";

	// Just give him a language selector
	echo "<td>";
		showLangSelect($cfg_install["root"]."/preferences/index.php",$name);
	echo "</td>";

	// And he can change his password, thats it
	echo "<td>";
		showPasswordForm($cfg_install["root"]."/preferences/index.php",$name);
	echo "</td>";
	// We tracking or post a new one
	echo "<td>";
        echo "<a class='icon_nav_move' href=\"".$cfg_install["root"]."/helpdesk.php\"><img  src=\"".$HTMLRel."pics/ajoutinterv.png\" alt=\"".$lang["job"][13]."\" title=\"".$lang["job"][13]."\"></a><br><br>";
        echo "<a class='icon_nav_move' href=\"".$cfg_install["root"]."/helpdesk.php?show=user\"><img  src=\"".$HTMLRel."pics/suivi.png\" alt=\"".$lang["tracking"][0]."\" title=\"".$lang["tracking"][0]."\"></a>";
	echo "</td>";
	// On the right side of the navigation bar, we have a clock with
	// date and a logout-link.
	echo "<td align='right' width='100'><div align='right'>";
	echo date("H").":".date("i")."<p><i>".date("j.")."&nbsp;".date("M")."&nbsp;".date("Y");
	echo "</i></p><a class='icon_nav_move' href=\"".$cfg_install["root"]."/logout.php\"><img class='icon_nav' src=\"".$HTMLRel."pics/logout.png\" alt=\"".$lang["central"][6]."\" title=\"".$lang["central"][6]."\"></a></div></td>";

	// End navigation bar
	
	echo "</tr></table>";
	
	// End headline

	echo "</td></tr>";	
	echo "</table>\n";
				echo "</div>";
}

function nullHeader($title,$url) {
	// Print a nice HTML-head with no controls

	GLOBAL $cfg_layout,$cfg_install,$lang,$HTMLRel,$phproot ;

	// Send extra expires header if configured
	if (!empty($cfg_features["sendexpire"])) {
		header("Expires: Fri, Jun 12 1981 08:20:00 GMT\nPragma: no-cache");
	}

	// Start the page
       	echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">";
        echo "<html><head><title>glpi: ".$title."</title>";
        echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1 \" >";

	// Send extra expires header if configured
	if (!empty($cft_features["sendexpire"])) {
		echo "<meta http-equiv=\"Expires\" content=\"Fri, Jun 12 1981 08:20:00 GMT\">\n";
		echo "<meta http-equiv=\"Pragma\" content=\"no-cache\">\n";
		echo "<meta http-equiv=\"Cache-Control\" content=\"no-cache\">\n";
	}

	// Some Javascript-Functions which we may need later
	echo "<script type=\"text/javascript\" language=\"JavaScript\">";
	echo "function jumpTo(URL_List){ var URL = URL_List.options[URL_List.selectedIndex].value;  window.location.href = URL; }\n\n";
	echo "browserName=navigator.appName;";
  	echo "browserVer=parseInt(navigator.appVersion);";
	echo "if ((browserName==\"Netscape\" && browserVer>=3) || (browserName==\"Microsoft Internet Explorer\" && browserVer>=4)) version=\"n3\";";
	echo "else version=\"n2\"; function historyback() { history.back(); } function historyforward() { history.forward(); }";
	echo "</script>";
	
	// Appel CSS
	
        echo "<link rel='stylesheet'  href='".$HTMLRel."styles.css' type='text/css' media='screen' >";

	
	// End of Head
	echo "</head>\n";
	
	// Body with configured stuff
	echo "<body>";

	// Main Headline
	echo "<div id='navigation'>";

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


function commonFooter() {
	// Print foot for every page

GLOBAL $cfg_install;
echo "<div id='footer'><div align='right'>";
	echo "<a href=\"http://GLPI.indepnet.org/\">";
	echo "<small>GLPI ".$cfg_install["version"]."</small>";
	echo "</a></div>";
	echo "</div>";
	echo "</body></html>";
}

function helpFooter() {
	// Print foot for help page
GLOBAL $cfg_install;
echo "<div id='footer'><div align='right'>";
	echo "<a href=\"http://GLPI.indepnet.org/\">";
	echo "<small>GLPI ".$cfg_install["version"]."</small>";
	echo "</a></div>";
		echo "</div>";

	echo "</body></html>";
}

function nullFooter() {
	// Print foot for help page
GLOBAL $cfg_install;
echo "<div id='footer'><div align='right'>";
	echo "<a href=\"http://GLPI.indepnet.org/\">";
	echo "<small>GLPI ".$cfg_install["version"]."</small>";
	echo "</a>";
		echo "</div></div>";

	echo "</body></html>";
}

function logEvent ($item, $itemtype, $level, $service, $event) {
	// Logs the event if level is above or equal to setting from configuration

	GLOBAL $cfg_features;
	if ($level <= $cfg_features["event_loglevel"]) { 
		$db = new DB;	
		$query = "INSERT INTO glpi_event_log VALUES (NULL, $item, '$itemtype', NOW(), '$service', $level, '$event')";
		$result = $db->query($query);    
	}
}


function showEvents($target,$order,$sort) {
	// Show events from $result in table form

	GLOBAL $cfg_layout, $cfg_install, $cfg_features, $lang;

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
		echo "&middot;&nbsp;";
	}
	echo "<a href=\"$target?sort=item&order=ASC\">".$lang["event"][0]."</a></th>";

	echo "<th>";
	if ($sort=="date") {
		echo "&middot;&nbsp;";	
	}
	echo "<a href=\"$target?sort=date&order=DESC\">".$lang["event"][1]."</a></th>";

	echo "<th>";
	if ($sort=="service") {
		echo "&middot;&nbsp;";	
	}
	echo "<a href=\"$target?sort=service&order=ASC\">".$lang["event"][2]."</a></th>";

	echo "<th width='5%'>";
	if ($sort=="level") {
		echo "&middot;&nbsp;";	
	}
	echo "<a href=\"$target?sort=level&order=DESC\">".$lang["event"][3]."</a></th>";

	echo "<th width='70%'>";
	if ($sort=="message") {
		echo "&middot;&nbsp;";	
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
			echo "<a href=\"".$cfg_install["root"]."/$itemtype/".$itemtype."-info-form.php?ID=";
			echo $item;
			echo "\">$item</a>";
		}			
		echo "</b></td><td>$date</td><td align='center'>$service</td><td align='center'>$level</td><td>$message</td>";
		echo "</tr>";

		$i++; 
	}

	echo "</table></center><br>";
}



function dropdown($table,$myname) {
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
		$query = "SELECT * FROM $table ORDER BY name";
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


function dropdownValue($table,$myname,$value) {
	// Make a select box with preselected values

	$db = new DB;

	$query = "SELECT * FROM $table ORDER BY name";
	$result = $db->query($query);
	
	echo "<select name=\"$myname\" size='1'>";
	echo "<option value=\"NULL\">-----</option>";
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
}

function dropdownNoValue($table,$myname,$value) {
	// Make a select box without parameters value

	$db = new DB;

	$query = "SELECT * FROM $table ORDER BY name";
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

function NetpointLocationSearch($search,$myname,$location,$value='') {
// Make a select box with preselected values for table dropdown_netpoint
	$db = new DB;
	
	$query = "SELECT t1.ID as ID, t1.name as netpointname, t2.name as locname
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
		$query = "SELECT t1.ID as ID, t1.name as netpointname, t2.name as locname
			FROM glpi_dropdown_netpoint AS t1
			LEFT JOIN glpi_dropdown_locations AS t2 ON t1.location = t2.ID
			ORDER BY t1.name, t2.name";
		$result = $db->query($query);
	}
	
	
	echo "<select name=\"$myname\" size='1'>";
	echo "<option value=\"NULL\">---</option>";
	
	if($db->numrows($result) > 0) {
		while($line = $db->fetch_array($result)) {
			echo "<option value=\"". $line["ID"] ."\" ";
			if ($value==$line["ID"]) echo " selected ";
			echo ">". $line["netpointname"]." (".$line["locname"] .")</option>";
		}
	}
	echo "</select>";
}

function dropdownValueSearch($table,$myname,$value,$search) {
	// Make a select box with preselected values

	$db = new DB;

	$query = "SELECT * FROM $table WHERE name LIKE '%$search%' ORDER BY name";
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
			$output = $db->result($result, $i, "name");

			if ($output == $value) {
				echo "<option value=\"$output\" selected>$output</option>";
			} else {
				echo "<option value=\"$output\">$output</option>";
			}
			$i++;
		}
	}
	echo "</select>";
}

function dropdownUsers($value, $myname) {
	// Make a select box with all glpi users

	$db = new DB;
	$query = "SELECT * FROM glpi_users WHERE (".searchUserbyType("normal").") ORDER BY name";
	$result = $db->query($query);

	echo "<select name=\"$myname\">";
	$i = 0;
	$number = $db->numrows($result);
	if ($number > 0) {
		echo "<option value=\"\">[ Nobody ]\n";
		while ($i < $number) {
			$output = $db->result($result, $i, "name");
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

function getDropdownName($table,$id) {
	
	$db = new DB;
	$name = "";
	$query = "select * from ". $table ." where ID = '". $id ."'";
	$result = $db->query($query);
	if($db->numrows($result) != 0) {
		$name = $db->result($result,0,"name");
		if ($table=="glpi_dropdown_netpoint")
			$name .= " (".getDropdownName("glpi_dropdown_locations",$db->result($result,0,"location")).")";
	}
	return $name;
}

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


function loadLanguage() {

	GLOBAL $lang;

	if(empty($_SESSION["glpilanguage"]))
	{	
		$file= "/glpi/dicts/french.php";
	}
	else {
		$file = "/glpi/dicts/".$_SESSION["glpilanguage"].".php";
	}
		include ("_relpos.php");
		include ($phproot . $file);
}


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
			echo "<tr><td class='tab_bg_1'><b>Computer: ";
			echo "<a href=\"".$cfg_install["root"]."/computers/computers-info-form.php?ID=".$connect->device_ID."\">";
			echo $connect->device_name." (".$connect->device_ID.")";
			echo "</a>";
			echo "</b></td>";
			echo "<td class='tab_bg_2' align='center'><b>";
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

function Disconnect($ID,$type) {
	// Disconnects a direct connection

	$connect = new Connection;
	$connect->type=$type;
	$connect->deletefromDB($ID);
}

function Connect($target,$sID,$cID,$type) {
	// Makes a direct connection

	$connect = new Connection;
	$connect->end1=$sID;
	$connect->end2=$cID;
	$connect->type=$type;
	$connect->addtoDB();
}


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
		$query = "SELECT glpi_computers.ID as ID,glpi_computers.name as name, glpi_dropdown_locations.name as location  from glpi_computers left join glpi_dropdown_locations on glpi_computers.location = glpi_dropdown_locations.id WHERE glpi_computers.name LIKE '%".$input["search"]."%' order by name ASC";
	} else {
		$query = "SELECT glpi_computers.ID as ID,glpi_computers.name as name, glpi_dropdown_locations.name as location from glpi_computers left join glpi_dropdown_locations on glpi_computers.location = glpi_dropdown_locations.id WHERE glpi_computers.ID LIKE '%".$input["search"]."%' order by name ASC";
	} 
	$result = $db->query($query);
	$number = $db->numrows($result);
	echo "<select name=\"cID\">";
	while ($i < $number) {
		$dID = $db->result($result, $i, "ID");
		$name = $db->result($result, $i, "name");
		$location = $db->result($result, $i, "location");
		echo "<option value=\"$dID\">".$name." (".$location.")</option>";
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

function listConnectElement($target,$input) {

	GLOBAL $cfg_layout,$cfg_install, $lang;

	$pID1 = $input["pID1"];
	$device_type=$input["device_type"];
	$table="";
	switch($device_type){
	case "printer":
	$table="glpi_printers";$device_id=3;break;
	case "monitor":
	$table="glpi_monitors";$device_id=4;break;
	case "peripheral":
	$table="glpi_peripherals";$device_id=5;break;
	
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
		$query = "SELECT $table.ID as ID,$table.name as name, glpi_dropdown_locations.name as location from $table left join glpi_dropdown_locations on $table.location = glpi_dropdown_locations.id left join glpi_connect_wire on ($table.ID = glpi_connect_wire.end1 AND glpi_connect_wire.type = $device_id) WHERE $table.name LIKE '%".$input["search"]."%' AND glpi_connect_wire.ID IS NULL order by name ASC";
	} else {
		$query = "SELECT $table.ID as ID,$table.name as name, glpi_dropdown_locations.name as location from $table left join glpi_dropdown_locations on $table.location = glpi_dropdown_locations.id left join glpi_connect_wire on ($table.ID = glpi_connect_wire.end1 AND glpi_connect_wire.type = $device_id) WHERE $table.ID LIKE '%".$input["search"]."%' AND glpi_connect_wire.ID IS NULL order by name ASC";
	} 
//	echo $query;
	$result = $db->query($query);
	$number = $db->numrows($result);
	if ($number>0) {
	echo "<select name=\"ID\">";
	while ($i < $number) {
		$dID = $db->result($result, $i, "ID");
		$name = $db->result($result, $i, "name");
		$location = $db->result($result, $i, "location");
		echo "<option value=\"$dID\">".$name." (".$location.")</option>";
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

function printHelpDesk ($name) {

	GLOBAL $cfg_layout,$cfg_install,$lang,$cfg_features;

	$db = new DB;

	$query = "SELECT email,realname,name FROM glpi_users WHERE (name = '$name')";
	$result=$db->query($query);
	$email = $db->result($result,0,"email");
	$realname = $db->result($result,0,"realname");
	$name = $db->result($result,0,"name");

	echo "<form method='post' name=\"helpdeskform\" action=\"".$cfg_install["root"]."/tracking/tracking-injector.php\">";
	echo "<center><table  class='tab_cadre'>";

	if ($realname!='') $name=$realname;

	echo "<tr><th colspan='2'>".$lang["help"][0]." $name, ".$lang["help"][1].":</th></tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<td>".$lang["help"][2].": </td>";
	echo "<td><select name=priority>";
	echo "<option value='5'>".$lang["help"][3]."";
	echo "<option value='4'>".$lang["help"][4]."";
	echo "<option value='3 selected'>".$lang["help"][5]."";
	echo"<option value='2'>".$lang["help"][6]."";
	echo "<option value='1'>".$lang["help"][7]."";
	echo "</select>";
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
	echo "<td colspan='2' align='center'>".$lang["help"][13].":</td>";
	echo "</tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<td colspan='2' align='center'><textarea name='contents' cols='40' rows='20' ></textarea>";
	echo "</td></tr>";

	echo "<tr class='tab_bg_1'>";
	echo "<td colspan='2' align='center'> <input type='submit' value=\"".$lang["help"][14]."\" class='submit'>";
		echo "<input type='hidden' name='IRMName' value=\"$name\">";
	echo "</td></tr>";

	echo "</table>";
	echo "</center>";
	echo "</form>";

}

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
		
		
		echo " <strong>".$lang["reports"][5]."</strong>";
		echo "<table width='100%' height='60' border='0' bordercolor='black'>";
		echo "<tr>";
		echo "<th><div align='center'><b>".$lang["computers"][7]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][16]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][9]."</b></div></th>";	
		echo "<th><div align='center'><b>".$lang["computers"][21]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][22]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][10]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][17]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][23]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][24]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][41]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["computers"][42]."</b></div></th>";
		echo "</tr>";
	 	while( $ligne = $db->fetch_array($result))
					{
						
						$name = $ligne['name'];
						$os = $ligne['os'];
						$processor = $ligne['processor'];
						$processor_speed = $ligne['processor_speed'];
						$lieu = $ligne['location'];
						$serial = $ligne['serial'];
						$ramType = $ligne['ramtype'];
						$ramSize = $ligne['ram'];
						$contact = $ligne['contact'];
						$achat_date = $ligne['achat_date'];
						$fin_garantie = $ligne['date_fin_garantie'];
		
						//inserer ces valeures dans un tableau

						echo "<tr>";
						if($name) echo "<td><div align='center'> $name </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contact) echo "<td><div align='center'> $contact </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($os) echo "<td><div align='center'> ".getDropdownName("glpi_dropdown_os",$os)." </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($processor) echo "<td><div align='center'> ".getDropdownName("glpi_dropdown_processor",$processor)." </div></td>"; else echo "<td><div align='center'> N/A </div></td>";	
						if($processor_speed) echo "<td><div align='center'> $processor_speed </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($lieu) echo "<td><div align='center'> ".getDropdownName("glpi_dropdown_locations",$lieu)." </div></td>"; else echo "<td><div align='center'> N/A </div></td>";	
						if($serial) echo "<td><div align='center'> $serial </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($ramType) echo "<td><div align='center'> ".getDropdownName("glpi_dropdown_ram",$ramType)." </div></td>"; else echo "<td><div align='center'> N/A </div></td>";	
						if($ramSize) echo "<td><div align='center'> $ramSize </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($achat_date) echo "<td><div align='center'> $achat_date </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($fin_garantie) echo "<td><div align='center'> $fin_garantie </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						echo "</tr>";
					}
		echo "</table><br><hr><br> ";
		break;
		
		case 'glpi_printers' :
		
		echo "<b><strong>".$lang["reports"][1]."</strong></b>";
		echo "<table width='100%' height='60' border='0'>";
		echo "<tr> ";
		echo "<th><div align='center'><b>".$lang["printers"][5]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["printers"][8]."</b></div></th>";	
		echo "<th><div align='center'><b>".$lang["printers"][9]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["printers"][6]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["printers"][10]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["printers"][23]."</b></div></th>";	
		echo "<th><div align='center'><b>".$lang["printers"][20]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["printers"][21]."</b></div></th>";
		echo "</tr>";
		
		while( $ligne = $db->fetch_array($result))
					{
					$type = $ligne['type']; 
					$name = $ligne['name'];
					$lieu = $ligne['location'];
					$serial = $ligne['serial'];
					$contact = $ligne['contact'];
					$achat_date = $ligne['achat_date'];
					$fin_garantie = $ligne['date_fin_garantie'];
					$ramSize = $ligne['ramSize'];
					
					//inserer ces valeures dans un tableau
					echo "<tr>";	
					if($name) echo "<td><div align='center'>$name</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($contact) echo "<td><div align='center'>$contact</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($type) echo "<td><div align='center'>".getDropdownName("glpi_type_printers",$type)."</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($lieu) echo "<td><div align='center'>".getDropdownName("glpi_dropdown_locations",$lieu)."</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($serial) echo "<td><div align='center'>$serial</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($ramSize) echo "<td><div align='center'>$ramSize</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($achat_date) echo "<td><div align='center'>$achat_date</div></td>"; else echo "<td><div align='center'> N/A</div> </td>";
					if($fin_garantie) echo "<td><div align='center'> $fin_garantie </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					echo "</tr>";
					}	
		echo "</table><br><hr><br>";
		break;
		
		case 'glpi_monitors' :
		
		echo " <b><strong>".$lang["reports"][2]."</strong></b>";
		echo "<table width='100%' height='60' border='0'>";
		echo "<tr> ";
		echo "<th><div align='center'><b>".$lang["monitors"][5]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["monitors"][9]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["monitors"][21]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["monitors"][8]."</b></div></th>";	
		echo "<th><div align='center'><b>".$lang["monitors"][6]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["monitors"][10]."</b></div></th>";	
		echo "<th><div align='center'><b>".$lang["monitors"][24]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["monitors"][25]."</b></div></th>";
		echo "</tr>";
		
		while( $ligne = $db->fetch_array($result))
					{
					$name = $ligne['name'];
					$lieu = $ligne['location'];
					$type = $ligne['type'];
					$contact = $ligne['contact'];
					$serial = $ligne['serial'];
					$achat_date = $ligne['achat_date'];
					$size = $ligne['size'];
					$fin_garantie = $ligne['date_fin_garantie'];
					//inserer ces valeures dans un tableau
					
					echo "<tr>";	
					if($name) echo "<td><div align='center'>$name</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($type) echo "<td><div align='center'>".getDropdownName("glpi_type_monitors",$type)."</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($size) echo "<td><div align='center'>$size</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($contact) echo "<td><div align='center'>$contact</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($lieu) echo "<td><div align='center'>".getDropdownName("glpi_type_monitors",$lieu)."</div></td>"; else echo "<td><div align='center'>N/A </div></td>";
					if($serial) echo "<td><div align='center'>$serial</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($achat_date) echo "<td><div align='center'>$achat_date</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($fin_garantie) echo "<td><div align='center'> $fin_garantie </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					echo "</tr>";
					}	
		echo "</table><br><hr><br>";
		break;
		
		case 'glpi_networking' :
		
		echo " <b><strong>".$lang["reports"][3]."</strong></b>";
		echo "<table width='100%' height='60' border='0'>";
		echo "<tr> ";
		echo "<th><div align='center'><b>".$lang["networking"][0]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["networking"][2]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["networking"][3]."</b></div></th>";	
		echo "<th><div align='center'><b>".$lang["networking"][1]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["networking"][6]."</b></div></th>";	
		echo "<th><div align='center'><b>".$lang["networking"][39]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["networking"][40]."</b></div></th>";
		echo "</tr>";
		
		while( $ligne = $db->fetch_array($result))
					{
					$name = $ligne['name'];
					$lieu = $ligne['location'];
					$type = $ligne['type'];
					$contact = $ligne['contact'];
					$serial = $ligne['serial'];
					$achat_date = $ligne['achat_date'];
					$fin_garantie = $ligne['date_fin_garantie'];
					//inserer ces valeures dans un tableau
				
					echo "<tr> ";	
					if($name) echo "<td><div align='center'>$name</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($type) echo "<td><div align='center'></div>".getDropdownName("glpi_type_networking",$type)."</td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($contact) echo "<td><div align='center'>$contact</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($lieu) echo "<td><div align='center'>".getDropdownName("glpi_dropdown_locations",$lieu)."</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($serial) echo "<td><div align='center'>$serial</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($achat_date) echo "<td><div align='center'>$achat_date</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($fin_garantie) echo "<td><div align='center'> $fin_garantie </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					echo "</tr>";
					}	
		echo "</table><br><hr><br>";
		break;
		
	}	
}

function countElementsInTable($table){
$db=new DB;
$query="SELECT count(*) as cpt from $table";
$result=$db->query($query);
$ligne = $db->fetch_array($result);
return $ligne['cpt'];
}
?>
