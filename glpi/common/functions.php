<?php
/*
 
 ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
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
function can_assign_job($IRMName)
{
  $db = new DB;
  $query = "SELECT * FROM users WHERE (name = '$IRMName')";
	$result = $db->query($query);
	$type = $db->result($result, 0, "can_assign_job");
	if ($type == 1)
	{
	 return true;
	 }
	 else
	 {
	 return false;
	 }
}

function checkAuthentication($authtype) {
	// Checks a GLOBAL user and password against the database
	// If $authtype is "normal" or "admin", it checks if the user
	// has the privileges to do something. Should be used in every 
	// control-page to set a minium security level.

	GLOBAL $IRMName, $IRMPass, $cfg_install, $lang;

	// New database object
	$db = new DB;

  loadLanguage('Helpdesk');
	// Get user from database
	$query = "SELECT * FROM users WHERE (name = '$IRMName')";
	$result = $db->query($query);
	$password = $db->result($result, 0, "password");
	$type = $db->result($result, 0, "type");	

	// Check username and password
	if (!IsSet($IRMName)) {
		header("Vary: User-Agent");
		nullHeader($lang["login"][3], $PHP_SELF);
		echo "<center><b>".$lang["login"][0]."</b><br><br>";
		echo "<b><a href=\"".$cfg_install["root"]."/logout.php\">".$lang["login"][1]."</a></b></center>";
		nullFooter();
		exit();
	} else if ($IRMPass != md5($password)) {
		nullHeader($lang["login"][4],$PHP_SELF);
		echo "<center><b>".$lang["login"][2]."</b><br><br>";
		echo "<b><a href=\"".$cfg_install["root"]."/logout.php\">".$lang["login"][1]."</a></b></center>";
		nullFooter();
		exit();
	} else {
		header("Vary: User-Agent");

		loadLanguage($IRMName);

		switch ($authtype) {

			case "admin";
				if ($type!="admin") {
					commonHeader($lang["login"][5],$PHP_SELF);
						echo "<center><br><br><img src=\"".$cfg_install["root"]."/pics/warning.png\" alt=\"warning\"><br><br>";

					echo "<b>".$lang["login"][5]."</b></center>";
					commonFooter();
					exit();
				}
			break;
			
			case "half-admin";
				if ($type!="normal" && $type!="admin" && $type!="half-admin") {
					commonHeader($lang["login"][5],$PHP_SELF);
											echo "<center><br><br><img src=\"".$cfg_install["root"]."/pics/warning.png\" alt=\"warning\"><br><br>";

					echo "<b>".$lang["login"][5]."</b></center>";
					commonFooter();
					exit();
				}
			break;
					
					
			case "normal";
				if ($type!="normal" && $type!="admin") {
					commonHeader($lang["login"][5],$PHP_SELF);
											echo "<center><br><br><img src=\"".$cfg_install["root"]."/pics/warning.png\" alt=\"warning\"><br><br>";

					echo "<b>".$lang["login"][5]."</b></center>";
					commonFooter();
					exit();
				}
			break;
		
			case "post-only";
				if ($type!="post-only" && $type!="normal" && $type!="admin") {
					commonHeader($lang["login"][5],$PHP_SELF);
											echo "<center><br><br><img src=\"".$cfg_install["root"]."/pics/warning.png\" alt=\"warning\"><br><br>";

					echo "<b>".$lang["login"][5]."</b></center>";
					commonFooter();
					exit();
				}				
			break;
		}
	}
}

function commonHeader($title,$url) {
	// Print a nice HTML-head for every page

	GLOBAL $cfg_layout,$cfg_install,$lang;
	
	
$inventory = 	array($lang["Menu"][0]=>"/computers/index.php",
	              $lang["Menu"][1]=>"/networking/index.php",
	              $lang["Menu"][2]=>"/printers/index.php",
	              $lang["Menu"][3]=>"/monitors/index.php",
	              $lang["Menu"][4]=>"/software/index.php");
				
				
				
$maintain =	array($lang["Menu"][5]=>"/tracking/index.php",
	              $lang["Menu"][6]=>"/reports/index.php");
				
				

$LDAP =	        array($lang["Menu"][7]=>"/ldap/index.php?type=posixGroup",
	              $lang["Menu"][8]=>"/ldap/index.php?type=posixAccount",
	              $lang["Menu"][9]=>"/ldap/index.php?type=qmailUser");
				
$config =	array($lang["Menu"][10]=>"/setup/index.php",
	              $lang["Menu"][11]=>"/preferences/index.php",
	              $lang["Menu"][12]=>"/backups/index.php");			

	// Send extra expires header if configured
	if ($cfg_features["sendexpire"]) {
		header("Expires: Fri, Jun 12 1981 08:20:00 GMT\nPragma: no-cache");
	}

	// Start the page
	echo "<html><head><title>glpi: ".$title."</title>";

	// Send extra expires header if configured
	if ($cfg_features["sendexpire"]) {
		echo "<META HTTP-EQUIV=\"Expires\" CONTENT=\"Fri, Jun 12 1981 08:20:00 GMT\">\n";
		echo "<META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">\n";
		echo "<META HTTP-EQUIV=\"Cache-Control\" CONTENT=\"no-cache\">\n";
	}

	// Include CSS
	echo "<style type=\"text/css\">\n";
		include ("_relpos.php");

		include ($phproot . "/glpi/config/styles.css");
	echo "</style>\n";

	// Some Javascript-Functions which we may need later
	echo "<script language=\"JavaScript\">";
	echo "function jumpTo(URL_List){ var URL = URL_List.options[URL_List.selectedIndex].value;  window.location.href = URL; }\n\n";
	echo "browserName=navigator.appName;";
  	echo "browserVer=parseInt(navigator.appVersion);";
	echo "if ((browserName==\"Netscape\" && browserVer>=3) || (browserName==\"Microsoft Internet Explorer\" && browserVer>=4)) version=\"n3\";";
	echo "else version=\"n2\"; function historyback() { history.back(); } function historyforward() { history.forward(); }";
	echo "</script>";

	// End of Head
	echo "</head>\n";
	
	// Body with configured stuff
	echo "<body bgcolor=".$cfg_layout["body_bg"]." text=".$cfg_layout["body_text"]." link=".$cfg_layout["body_link"]." vlink=".$cfg_layout["body_vlink"]." alink=".$cfg_layout["body_alink"].">\n";

	// Main Headline
			echo "<div id=navigation>";
echo "<table  cellspacing=0 border=0 width=98%>";
echo "<tr>";
	
	// Logo with link to command center
	echo "<td align=center width=25% >\n";
	echo "<a href=\"".$cfg_install["root"]."/central.php\"><IMG src=\"".$cfg_layout["logogfx"]."\" border=0 alt=\"".$cfg_layout["logotxt"]."\" ></a>\n";
	echo "</td>";

	echo "<td valign=middle>";
	
	// New object from the configured base functions, we check some
	// object-variables in this object: inventory, maintain, admin
	// and settings. We build the navigation bar here.

	$navigation = new baseFunctions;

	// Get object-variables and build the navigation-elements
	echo "<table width=100% cellspacing=0 cellpadding=0 border=0><tr>";
	if ($navigation->inventory) {
		echo "<td align=center valign=top><small>";
		echo "<img src=\"".$cfg_install["root"]."/pics/inventaire.png\" alt=\"\"><br>";
		echo "-&nbsp;".$lang["setup"][10]."&nbsp;-</small><br>";
		for ($i=0; $i < count($inventory); $i++) {
			list($key,$val) = each($inventory);
			echo "<a href=\"".$cfg_install["root"].$val."\">".$key."</a><br>";
		}	
		echo "</td>";
	}
	 if ($navigation->maintain) {
		echo "<td align=center valign=top><small>";
				echo "<img src=\"".$cfg_install["root"]."/pics/maintenance.png\" alt=\"\"><br>";

		echo "-&nbsp;".$lang["setup"][55]."&nbsp;-</small><br>";
		for ($i=0; $i < count($maintain); $i++) {
			list($key,$val) = each($maintain);
			echo "<a href=\"".$cfg_install["root"].$val."\">".$key."</a><br>";
		}
		echo "</td>";
	}
	 if ($navigation->admin) {
		echo "<td align=center valign=top><small>";
		echo "<img src=\"".$cfg_install["root"]."/pics/ldap.png\" alt=\"\"><br>";

		echo "-&nbsp;".$lang["ldap"][7]."-</small><br>";

		for ($i=0; $i < count($LDAP); $i++) {
			list($key,$val) = each($LDAP);
			echo "<a href=\"".$cfg_install["root"].$val."\">".$key."</a><br>";
		}	
		echo "</td>";
	}	
	if ($navigation->settings) {
		echo "<td align=center valign=top><small>";
				echo "<img src=\"".$cfg_install["root"]."/pics/config.png\" alt=\"\"><br>";

		echo "-&nbsp;".$lang["setup"][56]."&nbsp;-</small><br>";
		for ($i=0; $i < count($config); $i++) {
			list($key,$val) = each($config);
			echo "<a href=\"".$cfg_install["root"].$val."\">".$key."</a><br>";
		}	
		echo "</td>";
	}
	
	// On the right side of the navigation bar, we have a clock with
	// date and a logout-link.

	echo "<td align=right width=100><b><div align=right>";
	echo date("H")."<blink>:</blink>".date("i")."<br><i>".date("j.")."&nbsp;".date("M")."&nbsp;".date("Y");
	echo "</i><br><a href=\"".$cfg_install["root"]."/logout.php\"><img src=\"".$cfg_install["root"]."/pics/logout.png\" alt=\"Logout\"></a></div></b></td>";

	// End navigation bar

	echo "</tr></table>";

	// End headline

	echo "</td></tr></form>";	
echo "</table>\n";
				echo "</div>";
}


function helpHeader($title,$url,$IRMName) {
	// Print a nice HTML-head for help page

	GLOBAL $cfg_layout,$cfg_install,$lang;

	// Send extra expires header if configured
	if ($cfg_features["sendexpire"]) {
		header("Expires: Fri, Jun 12 1981 08:20:00 GMT\nPragma: no-cache");
	}

	// Start the page
	echo "<html><head><title>GLPI Internal Helpdesk : ".$title."</title>";

	// Send extra expires header if configured
	if ($cft_features["sendexpire"]) {
		echo "<META HTTP-EQUIV=\"Expires\" CONTENT=\"Fri, Jun 12 1981 08:20:00 GMT\">\n";
		echo "<META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">\n";
		echo "<META HTTP-EQUIV=\"Cache-Control\" CONTENT=\"no-cache\">\n";
	}

	// Some Javascript-Functions which we may need later
	echo "<script language=\"JavaScript\">";
	echo "function jumpTo(URL_List){ var URL = URL_List.options[URL_List.selectedIndex].value;  window.location.href = URL; }\n\n";
	echo "browserName=navigator.appName;";
  	echo "browserVer=parseInt(navigator.appVersion);";
	echo "if ((browserName==\"Netscape\" && browserVer>=3) || (browserName==\"Microsoft Internet Explorer\" && browserVer>=4)) version=\"n3\";";
	echo "else version=\"n2\"; function historyback() { history.back(); } function historyforward() { history.forward(); }";
	echo "</script>";
	
	// Include CSS
	echo "<style type=\"text/css\">\n";
				include ("_relpos.php");

		include ($phproot . "/glpi/config/styles.css");
	echo "</style>\n";

	// End of Head
	echo "</head>\n";
	
	// Body with configured stuff
	echo "<body bgcolor=".$cfg_layout["body_bg"]." text=".$cfg_layout["body_text"]." link=".$cfg_layout["body_link"]." vlink=".$cfg_layout["body_vlink"]." alink=".$cfg_layout["body_alink"].">\n";

	// Main Headline
				echo "<div id=navigation>";

	echo "<table cellspacing=0 border=0 width=98%>";
	echo "<tr>";
	
	// Logo with link to command center
	echo "<td align=center width=25%>\n";
	echo "<a href=\"".$cfg_install["root"]."/central.php\"><IMG src=\"".$cfg_layout["logogfx"]."\" border=0 alt=\"".$cfg_layout["logotxt"]."\" vspace=10></a>\n";
	echo "</td>";

	echo "<td valign=middle>";

	echo "<table width=100% cellspacing=0 cellpadding=0 border=0><tr>";

	// Just give him a language selector
	echo "<td>";
		showLangSelect($cfg_install["root"]."/preferences/index.php",$IRMName);
	echo "</td>";

	// And he can change his password, thats it
	echo "<td>";
		showPasswordForm($cfg_install["root"]."/preferences/index.php",$IRMName);
	echo "</td>";
	
	// On the right side of the navigation bar, we have a clock with
	// date and a logout-link.
	echo "<td align=right width=100><b><div align=right>";
	echo date("H")."<blink>:</blink>".date("i")."<br><i>".date("j.")."&nbsp;".date("M")."&nbsp;".date("Y");
	echo "</i><br><a href=\"".$cfg_install["root"]."/logout.php\"><img src=\"".$cfg_install["root"]."/pics/logout.png\" alt=\"Logout\"></a></div></b></td>";

	// End navigation bar
	
	echo "</tr></table>";
	
	// End headline

	echo "</td></tr></form>";	
	echo "</table>\n";
				echo "</div>";
}

function nullHeader($title,$url) {
	// Print a nice HTML-head with no controls

	GLOBAL $cfg_layout,$cfg_install,$lang;

	// Send extra expires header if configured
	if ($cfg_features["sendexpire"]) {
		header("Expires: Fri, Jun 12 1981 08:20:00 GMT\nPragma: no-cache");
	}

	// Start the page
	echo "<html><head><title>glpi: ".$title."</title>";

	// Send extra expires header if configured
	if ($cft_features["sendexpire"]) {
		echo "<META HTTP-EQUIV=\"Expires\" CONTENT=\"Fri, Jun 12 1981 08:20:00 GMT\">\n";
		echo "<META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">\n";
		echo "<META HTTP-EQUIV=\"Cache-Control\" CONTENT=\"no-cache\">\n";
	}

	// Some Javascript-Functions which we may need later
	echo "<script language=\"JavaScript\">";
	echo "function jumpTo(URL_List){ var URL = URL_List.options[URL_List.selectedIndex].value;  window.location.href = URL; }\n\n";
	echo "browserName=navigator.appName;";
  	echo "browserVer=parseInt(navigator.appVersion);";
	echo "if ((browserName==\"Netscape\" && browserVer>=3) || (browserName==\"Microsoft Internet Explorer\" && browserVer>=4)) version=\"n3\";";
	echo "else version=\"n2\"; function historyback() { history.back(); } function historyforward() { history.forward(); }";
	echo "</script>";
	
	// Include CSS
	echo "<style type=\"text/css\">\n";
				include ("_relpos.php");

		include ($phproot . "/glpi/config/styles.css");
	echo "</style>\n";

	// End of Head
	echo "</head>\n";
	
	// Body with configured stuff
	echo "<body bgcolor=".$cfg_layout["body_bg"]." text=".$cfg_layout["body_text"]." link=".$cfg_layout["body_link"]." vlink=".$cfg_layout["body_vlink"]." alink=".$cfg_layout["body_alink"].">\n";

	// Main Headline
				echo "<div id=navigation>";

	echo "<table cellspacing=0 border=0 width=98%>";
	echo "<tr>";
	
	// Logo with link to command center
	echo "<td align=center width=25%>\n";
	echo "<a href=\"".$cfg_install["root"]."/central.php\"><IMG src=\"".$cfg_layout["logogfx"]."\" border=0 alt=\"".$cfg_layout["logotxt"]."\" vspace=10></a>\n";
	echo "</td>";

	echo "<td valign=middle>";

	echo "<table width=100% cellspacing=0 cellpadding=0 border=0><tr>";

	// Just give him nothing

	// On the right side of the navigation bar, we have a clock with
	// date and a logout-link.
	echo "<td align=right width=100><b><div align=right>";
	echo date("H")."<blink>:</blink>".date("i")."<br><i>".date("j.")."&nbsp;".date("M")."&nbsp;".date("Y");
	echo "</i><br><a href=\"".$cfg_install["root"]."/logout.php\"><img src=\"".$cfg_install["root"]."/pics/logout.png\" alt=\"Logout\"></a></div></b></td>";

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
echo "<div id=footer>";
	echo "<a href=\"http://GLPI.indepnet.org/\">";
	echo "<small><b><div align=right>GLPI ".$cfg_install["version"]."</div></b></small>";
	echo "</a>";
	echo "</div>";
	echo "</body></html>";
}

function helpFooter() {
	// Print foot for help page
GLOBAL $cfg_install;
echo "<div id=footer>";
	echo "<a href=\"http://GLPI.indepnet.org/\">";
	echo "<small><b><div align=right>GLPI ".$cfg_install["version"]."</div></b></small>";
	echo "</a>";
		echo "</div>";

	echo "</body></html>";
}

function nullFooter() {
	// Print foot for help page
GLOBAL $cfg_install;
echo "<div id=footer>";
	echo "<a href=\"http://GLPI.indepnet.org/\">";
	echo "<small><b><div align=right>GLPI ".$cfg_install["version"]."</div></b></small>";
	echo "</a>";
		echo "</div>";

	echo "</body></html>";
}

function logEvent ($item, $itemtype, $level, $service, $event) {
	// Logs the event if level is above or equal to setting from configuration

	GLOBAL $cfg_features;
	if ($level <= $cfg_features["event_loglevel"]) { 
		$db = new DB;	
		$query = "INSERT INTO event_log VALUES (NULL, $item, '$itemtype', NOW(), '$service', $level, '$event')";
		$result = $db->query($query);    
	}
}


function showEvents($target,$result,$sort) {
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
	$query = "SELECT * FROM event_log ORDER BY $sort $order LIMIT 0,".$cfg_features["num_of_events"];

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

	echo "<p><center><table border=0 width=90%>";
	echo "<tr><th colspan=6>".$lang["central"][2]." ".$cfg_features["num_of_events"]." ".$lang["central"][3].":</th></tr>";
	echo "<tr>";

	echo "<th colspan=2>";
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

	echo "<th width=5%>";
	if ($sort=="level") {
		echo "&middot;&nbsp;";	
	}
	echo "<a href=\"$target?sort=level&order=DESC\">".$lang["event"][3]."</a></th>";

	echo "<th width=70%>";
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
		
		echo "<tr bgcolor=".$cfg_layout["tab_bg_2"].">";
		echo "<td>$itemtype:</td><td align=center><b><nobr>";
		if ($item=="-1" || $item=="0") {
			echo $item;
		} else {
			echo "<a href=\"".$cfg_install["root"]."/$itemtype/".$itemtype."-info-form.php?ID=";
			echo $item;
			echo "\">$item</a>";
		}			
		echo "</nobr></b></td><td><nobr>$date</nobr></td><td align=center>$service</td><td align=center>$level</td><td>$message</td>";
		echo "</tr>";

		$i++; 
	}

	echo "</table></center><br>";
}



function dropdown($table,$myname) {
	// Make a select box
	$db = new DB;
	$query = "SELECT * FROM $table ORDER BY name";
	$result = $db->query($query);

	echo "<SELECT NAME=\"$myname\" SIZE=1>";
	$i = 0;
	$number = $db->numrows($result);
	if ($number > 0) {
		while ($i < $number) {
			$output = $db->result($result, $i, "name");
			echo "<OPTION VALUE=\"$output\">$output</OPTION>";
			$i++;
		}
	}
	echo "</SELECT>";
}


function dropdownValue($table,$myname,$value) {
	// Make a select box with preselected values

	$db = new DB;

	$query = "SELECT * FROM $table ORDER BY name";
	$result = $db->query($query);

	echo "<SELECT NAME=\"$myname\" SIZE=1>";
	$i = 0;
	$number = $db->numrows($result);
	if ($number > 0) {
		while ($i < $number) {
			$output = $db->result($result, $i, "name");
			if ($output == $value) {
				echo "<OPTION VALUE=\"$output\" selected>$output</OPTION>";
			} else {
				echo "<OPTION VALUE=\"$output\">$output</OPTION>";
			}
			$i++;
		}
	}
	echo "</select>";
}

function dropdownUsers($value, $myname) {
	// Make a select box with all glpi users

	$db = new DB;
	$query = "SELECT * FROM users WHERE (type = 'admin' || type = 'normal') ORDER BY name";
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
   		}
	}
	echo "</select>";
}

function loadLanguage($user) {

	GLOBAL $lang;
	
	$db = new DB;
	$query = "SELECT language FROM prefs WHERE (user = '$user')";
	$result=$db->query($query);
	
	$language = $db->result($result,0,"language");
	$file = "/glpi/dicts/".$language.".php";
include ("_relpos.php");
	include ($phproot . $file);
}


function showConnect($target,$ID,$type) {
		// Prints a direct connection to a computer

		GLOBAL $lang, $cfg_layout, $cfg_install;

		$connect = new Connection;
		$connect->type=$type;
		$computer = $connect->getComputerContact($ID);

		echo "<br><center><table width=50%><tr><th colspan=2>";
		echo $lang["connect"][0].":";
		echo "</th></tr>";

		if ($computer) {
			$connect->getComputerData($computer);
			echo "<tr><td bgcolor=\"".$cfg_layout["tab_bg_1"]."\"><b>Computer: ";
			echo "<a href=\"".$cfg_install["root"]."/computers/computers-info-form.php?ID=".$connect->device_ID."\">";
			echo $connect->device_name." (".$connect->device_ID.")";
			echo "</a>";
			echo "</b></td>";
			echo "<td bgcolor=\"".$cfg_layout["tab_bg_2"]."\" align=center><b>";
			echo "<a href=\"$target?disconnect=1&ID=$ID\">".$lang["connect"][3]."</a>";
		} else {
			echo "<tr><td bgcolor=\"".$cfg_layout["tab_bg_1"]."\"><b>Computer: </b>";
			echo "<i>".$lang["connect"][1]."</i>";
			echo "</td>";
			echo "<td bgcolor=\"".$cfg_layout["tab_bg_2"]."\" align=center><b>";
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


function showConnectSearch($target,$ID) {

	GLOBAL $cfg_layout,$cfg_install, $lang;

	echo "<center><table border=0>";
	echo "<tr><th colspan=2>".$lang["connect"][4].":</th></tr>";

	echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\">";
	echo "<form method=post action=\"$target\">";
	echo "<td>".$lang["connect"][5]." <select name=type>";
	echo "<option value=name>".$lang["connect"][6]."</option>";
	echo "<option value=id>".$lang["connect"][7]."</option>";
	echo "</select> ";
	echo $lang["connect"][8]." <input type=text size=10 name=comp>";
	echo "<input type=hidden name=pID1 value=$ID>";
	echo "<input type=hidden name=connect value=2>";
	echo "</td><td bgcolor=\"".$cfg_layout["tab_bg_2"]."\">";
	echo "<input type=submit value=\"".$lang["buttons"][11]."\">";
	echo "</td></tr>";	

	echo "</form>";
	echo "</table>";	
	echo "</center>";
}


function listConnectComputers($target,$input) {

	GLOBAL $cfg_layout,$cfg_install, $lang;

	$pID1 = $input["pID1"];

	echo "<center><table border=0>";
	echo "<tr><th colspan=2>".$lang["connect"][9].":</th></tr>";
	echo "<form method=post action=\"$target\"><tr><td>";

	echo "<tr bgcolor=\"".$cfg_layout["tab_bg_1"]."\">";
	echo "<td align=center>";

	$db = new DB;
	if ($input["type"] == "name") {
		$query = "SELECT ID,name,location from computers WHERE (name LIKE '%".$input["comp"]."%')";
	} else {
		$query = "SELECT ID,name,location from computers WHERE ID = ".$input["comp"];
	} 
	$result = $db->query($query);
	$number = $db->numrows($result);
	echo "<select name=cID>";
	while ($i < $number) {
		$dID = $db->result($result, $i, "ID");
		$name = $db->result($result, $i, "name");
		$location = $db->result($result, $i, "location");
		echo "<option value=$dID>$name ($location)</option>";
		$i++;
	}
	echo  "</select>";

	echo "</td>";
	echo "<td bgcolor=\"".$cfg_layout["tab_bg_2"]."\" align=center>";
	echo "<input type=hidden name=sID value=\"".$input["pID1"]."\">";
	echo "<input type=hidden name=connect value=3>";
	echo "<input type=submit value=\"".$lang["buttons"][9]."\">";
	echo "</td></form></tr></table>";	

}

function printHelpDesk ($name) {

	GLOBAL $cfg_layout,$cfg_install,$lang;

	$db = new DB;

	$query = "SELECT email,realname FROM users WHERE (name = '$name')";
	$result=$db->query($query);
	$email = $db->result($result,0,"email");
	$realname = $db->result($result,0,"realname");

	echo "<form method=post action=\"".$cfg_install["root"]."/tracking/tracking-injector.php\">";
	echo "<center><table border=0>";

	echo "<tr><th colspan=2>".$lang["help"][0]." $realname, ".$lang["help"][1].":</th></tr>";
	echo "<tr bgcolor=".$cfg_layout["tab_bg_1"].">";
	echo "<td>".$lang["help"][2].": </td>";
	echo "<td><select name=priority>";
	echo "<option value=5>".$lang["help"][3]."";
	echo "<option value=4>".$lang["help"][4]."";
	echo "<option value=3 selected>".$lang["help"][5]."";
	echo"<option value=2>".$lang["help"][6]."";
	echo "<option value=1>".$lang["help"][7]."";
	echo "</select>";
	echo "</td></tr>";

	echo "<tr bgcolor=".$cfg_layout["tab_bg_1"].">";
	echo "<td>".$lang["help"][8].":</td>";
	echo "<td>	<select name=emailupdates>";
	echo "<option value=no selected>".$lang["help"][9]."";
	echo "<option value=yes>".$lang["help"][10]."";
	echo "</select>";
	echo "</td></tr>";


	echo "<tr bgcolor=".$cfg_layout["tab_bg_1"].">";
	echo "<td>".$lang["help"][11].":</td>";
	echo "<td>	<input name=uemail value=\"$email\" size=20>";
	echo "</td></tr>";

	echo "<tr bgcolor=".$cfg_layout["tab_bg_1"].">";
	echo "<td>".$lang["help"][12]." <a href=\"#\" onClick=\"window.open('".$cfg_install["root"]."/find_num.php','Help','scrollbars=1,resizable=1,width=400,height=400')\"><img src=\"".$cfg_install["root"]."/pics/aide.png\" border=0 alt=\"help\"></a></td>";
	echo "<td><input name=computer size=10>";
	echo "</td>";
	echo "</tr>";

	echo "<tr bgcolor=".$cfg_layout["tab_bg_1"].">";
	echo "<td colspan=2 align=center>".$lang["help"][13].":</td>";
	echo "</tr>";
	echo "<tr bgcolor=".$cfg_layout["tab_bg_1"].">";
	echo "<td colspan=2 align=center><textarea name=contents cols=40 rows=20 wrap=soft></textarea>";
	echo "</td></tr>";

	echo "<tr bgcolor=".$cfg_layout["tab_bg_1"].">";
	echo "<td colspan=2 align=center> <input type=submit value=\"".$lang["help"][14]."\">";
	echo "</td></tr>";

	echo "<input type=hidden name=IRMName value=\"$name\">";
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
	echo "<br><center><table border=0 width=90%>";
	echo "<tr>";
	
	// Back and fast backward button
	if (!$start==0) {
		echo "<th align=left>";
		echo "<a href=\"$target?$parameters&start=$back\">";
		echo "&nbsp;<&nbsp;";
		echo "</a></th>";
		echo "<th align=left>";
		echo "<a href=\"$target?$parameters&start=0\">";
		echo "&nbsp;<<&nbsp;";
		echo "</a></th>";
	}

	// Print the "where am I?" 
	echo "<td width=100% align=center bgcolor=\"".$cfg_layout["tab_bg_1"]."\"><b>";
	echo $current_start."&nbsp;".$lang["pager"][1]."&nbsp;".$current_end."&nbsp;".$lang["pager"][2]."&nbsp;".$numrows."&nbsp;";
	echo "</b></td>";

	// Forward and fast forward button
	if ($forward<$numrows) {
		echo "<th align=right>";
		echo "<a href=\"$target?$parameters&start=$forward\">";
		echo "&nbsp;>&nbsp;";
		echo "</a></th>";
		echo "<th align=right>";
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
		case 'computers' :
		
		
		echo " <b></strong>".$lang["reports"][5]."</strong></b>";
		echo "<table width='100%' height='60' border='0'bordercolor='black'>";
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
						$ramType = $ligne['ramType'];
						$ramSize = $ligne['ram'];
						$contact = $ligne['contact'];
						$achat_date = $ligne['achat_date'];
						$fin_garantie = $ligne['date_fin_garantie'];
		
						//inserer ces valeures dans un tableau

						echo "<tr>";
						if($name) echo "<td><div align='center'> $name </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($contact) echo "<td><div align='center'> $contact </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($os) echo "<td><div align='center'> $os </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($processor) echo "<td><div align='center'> $processor </div></td>"; else echo "<td><div align='center'> N/A </div></td>";	
						if ($processor_speed) echo "<td><div align='center'> $processor_speed </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if ($lieu) echo "<td><div align='center'> $lieu </div></td>"; else echo "<td><div align='center'> N/A </div></td>";	
						if($serial) echo "<td><div align='center'> $serial </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($ramType) echo "<td><div align='center'> $ramType </div></td>"; else echo "<td><div align='center'> N/A </div></td>";	
						if($ramSize) echo "<td><div align='center'> $ramSize </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($achat_date) echo "<td><div align='center'> $achat_date </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						if($fin_garantie) echo "<td><div align='center'> $fin_garantie </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
						echo "</tr>";
					}
		echo "</table><br><hr><br> ";
		break;
		
		case 'printers' :
		
		echo "<b><strong>".$lang["reports"][1]."</strong></b>";
		echo "<table width='100%' height='60' border='0'>";
		echo "<tr> ";
		echo "<th><div align='center'><b>".$lang["printers"][5]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["printers"][9]."</b></div></th>";
		echo "<th><div align='center'><b>".$lang["printers"][8]."</b></div></th>";	
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
					$name = $ligne['serial'];
					$contact = $ligne['contact'];
					$achat_date = $ligne['achat_date'];
					$fin_garantie = $ligne['date_fin_garantie'];
					$ramSize = $ligne['ramSize'];
					
					//inserer ces valeures dans un tableau
					echo "<tr>";	
					if($name) echo "<td><div align='center'>$name</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($contact) echo "<td><div align='center'>$contact</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($type) echo "<td><div align='center'>$type</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($lieu) echo "<td><div align='center'>$lieu</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($serial) echo "<td><div align='center'>$serial</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($ramSize) echo "<td><div align='center'>$ramSize</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($achat_date) echo "<td><div align='center'>$achat_date</div></td>"; else echo "<td><div align='center'> N/A</div> </td>";
					if($fin_garantie) echo "<td><div align='center'> $fin_garantie </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					echo "</tr>";
					}	
		echo "</table><br><hr><br>";
		break;
		
		case 'monitors' :
		
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
					if($type) echo "<td><div align='center'>$type</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($size) echo "<td><div align='center'>$size</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($contact) echo "<td><div align='center'>$contact</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($lieu) echo "<td><div align='center'>$lieu</div></td>"; else echo "<td><div align='center'>N/A </div></td>";
					if($serial) echo "<td><div align='center'>$serial</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($achat_date) echo "<td><div align='center'>$achat_date</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($fin_garantie) echo "<td><div align='center'> $fin_garantie </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					echo "</tr>";
					}	
		echo "</table><br><hr><br>";
		break;
		
		case 'networking' :
		
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
					if($type) echo "<td><div align='center'></div>$type</td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($contact) echo "<td><div align='center'>$contact</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($lieu) echo "<td><div align='center'>$lieu</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($serial) echo "<td><div align='center'>$serial</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($achat_date) echo "<td><div align='center'>$achat_date</div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					if($fin_garantie) echo "<td><div align='center'> $fin_garantie </div></td>"; else echo "<td><div align='center'> N/A </div></td>";
					echo "</tr>";
					}	
		echo "</table><br><hr><br>";
		break;
		
	}	
}


?>
