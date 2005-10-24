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
//********************************  Fonctions authentification / droits / language ***********
//******************************************************************************************************
//******************************************************************************************************



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
			return " type ='super-admin' OR type ='admin' OR type ='normal'";
			break;
		case "admin":
			return " type ='super-admin' OR type ='admin' ";
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

	if(empty($_SESSION["authorisation"])&& $authtype != "anonymous")
	{
		nullHeader("Login",$_SERVER["PHP_SELF"]);
		echo "<div align='center'><b><a href=\"".$cfg_install["root"]."/logout.php\">Relogin</a></b></div>";
		nullFooter();
		die();	
	}

	
	// New database object
	loadLanguage();
	$type="anonymous";
	if (isset($_SESSION["glpitype"]))
		$type = $_SESSION["glpitype"];	
		
	// Check username and password
	if (!isset($_SESSION["glpiname"])&& $authtype != "anonymous") {
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
			case "anonymous";
    				if ($cfg_features['public_faq'] == 0){
      					nullHeader("Login",$_SERVER["PHP_SELF"]);
      					echo "<div align='center'><b><a href=\"".$cfg_install["root"]."/logout.php\">No anonymous authorisation</a></b></div>";
      					nullFooter();
      					exit();
    				}
			break;
				
			break;
		}
	}
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
			$lang[$module][$num].="<span style='font-size:12px; color:red;'>$module/$num</span>";
		
		}
	}

}


//******************************************************************************************************
//******************************************************************************************************
//********************************  Fonctions  logs                ************************************
//******************************************************************************************************
//******************************************************************************************************
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
		echo "<br><div align='center'>";
		echo "<table class='tab_cadre' width='90%'>";
		echo "<tr><th>".$lang["central"][4]."</th></tr>";
		echo "</table>";
		echo "</div><br>";
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
function showEvents($target,$order,$sort,$start=0) {
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
	$query = "SELECT * FROM glpi_event_log ORDER BY $sort $order";

	$query_limit = "SELECT * FROM glpi_event_log ORDER BY $sort $order LIMIT $start,".$cfg_features["list_limit"];

	// Get results
	$result = $db->query($query);
	
	
	// Number of results
	$numrows = $db->numrows($result);
	$result = $db->query($query_limit);
	$number = $db->numrows($result);

	// No Events in database
	if ($number < 1) {
		echo "<b>".$lang["central"][4]."</b>";
		return;
	}
	
	// Output events
	$i = 0;

	echo "<center>";
	$parameters="sort=$sort&amp;order=$order";
	printPager($start,$numrows,$target,$parameters);

	echo "<table width='90%' class='tab_cadre'>";
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


//******************************************************************************************************
//******************************************************************************************************
//********************************  Fonctions de   ????? ************************************
//******************************************************************************************************
//******************************************************************************************************




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
	case CONSUMABLE_TYPE : return $lang["Menu"][32];break;
	//case USER_TYPE : return $lang["Menu"][14];break;


}

}

function createAllItemsSelectValue($type,$ID){
	return $type."_".$ID;
}

function explodeAllItemsSelectResult($val){
	$splitter=split("_",$val);
	return array($splitter[0],$splitter[1]);
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

	echo "<div align='center'>";
	echo "<form method='post' action=\"$target\">";

	echo "<table class='tab_cadre'>";
	echo "<tr><th colspan='2'>".$lang["connect"][4]." :</th></tr>";
	echo "<tr class='tab_bg_1'>";
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

	echo "</table>";	
	echo "</form>";
	echo "</div>";
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

	echo "<div align='center'>";
	echo "<form method='post' action=\"$target\">";

	echo "<table  class='tab_cadre'>";
	echo "<tr><th colspan='2'>".$lang["connect"][9].":</th></tr>";

	echo "<tr class='tab_bg_1'>";
	echo "<td align='center'>";

	$db = new DB;
		
	$query = "SELECT glpi_computers.ID as ID,glpi_computers.name as name, glpi_dropdown_locations.ID as location from glpi_computers left join glpi_dropdown_locations on glpi_computers.location = glpi_dropdown_locations.id WHERE glpi_computers.deleted = 'N' AND glpi_computers.is_template ='0' AND glpi_computers.".$input["type"]." LIKE '%".$input["search"]."%' order by name ASC";
	
	$result = $db->query($query);
	$number = $db->numrows($result);
	echo "<select name=\"cID\">";
	$i=0;
	while ($i < $number) {
		$dID = $db->result($result, $i, "ID");
		$name = $db->result($result, $i, "name");
		$location = $db->result($result, $i, "location");
		echo "<option value=\"$dID\">".$name." (".getTreeValueCompleteName("glpi_dropdown_locations",$location).")</option>\n";
		$i++;
	}
	echo  "</select>\n";

	echo "</td>";
	echo "<td class='tab_bg_2' align='center'>";
	echo "<input type='hidden' name='sID' value=\"".$input["pID1"]."\">";
	echo "<input type='hidden' name='connect' value='3'>";
	echo "<input type='hidden' name='device_type' value='computer'>";
	echo "<input type='submit' value=\"".$lang["buttons"][9]."\" class='submit'>";
	echo "</td></tr></table></form></div>";	

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
	
	echo "<div align='center'>";
	echo "<form method='post' action=\"$target\">";
	echo "<table  class='tab_cadre'>";
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
	$i=0;
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
	
	echo "</td></tr></table></form></div>";	

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

//******************************************************************************************************
//******************************************************************************************************
//********************************  Fonctions de  ************************************
//******************************************************************************************************
//******************************************************************************************************

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
			onclick=\"window.open('".$HTMLRel."mycalendar.php?form=$form&amp;elem=$element&amp;value=$value','".$lang["buttons"][15]."','width=300,height=300')\" >";
		
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
		
		header("Content-disposition: filename=\"$filename\"");
		
     	header("Content-type: ".$mime);
     	header('Pragma: no-cache');
     	header('Expires: 0');
		$f=fopen($file,"r");
		
		if (!$f){
		echo "Error opening file $file";
		} else {
			// Pour que les \x00 ne devienne pas \0
			$mc=get_magic_quotes_runtime();
			if ($mc) @set_magic_quotes_runtime(0); 
			
			echo fread($f, filesize($file));

			if ($mc) @set_magic_quotes_runtime($mc); 
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
	$db=new DB;
	$query="SELECT * from glpi_users WHERE ID='$ID'";
	$result=$db->query($query);
//	echo $query;
	if ($db->numrows($result)==1){
		$before="";
		$after="";
		if ($link){
			$before="<a href=\"".$cfg_install["root"]."/users/users-info.php?ID=".$ID."\">";
			$after="</a>";
		}
		if (strlen($db->result($result,0,"realname"))>0) return $before.$db->result($result,0,"realname").$after;
		else return $before.$db->result($result,0,"name").$after;
	}
	else return "";		
}

function get_hour_from_sql($time){
$t=explode(" ",$time);
$p=explode(":",$t[1]);
return $p[0].":".$p[1];
}

?>
