<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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


/**
 * Log  history 
 *
 * 
 *
 * @param $id_device
 * @param $device_type
 * @param $changes
 * @param $device_internal_type
 * @param $linked_action
 **/
function historyLog ($id_device,$device_type,$changes,$device_internal_type='0',$linked_action='0') {

	global $DB;

	$date_mod=$_SESSION["glpi_currenttime"];
	
	if(!empty($changes)){

		// créate a query to insert history
		$id_search_option=$changes[0];
		$old_value=$changes[1];
		$new_value=$changes[2];

		if (isset($_SESSION["glpiID"]))
			$username = getUserName($_SESSION["glpiID"],$link=0);
		else
			$username="";

		// Build query
		$query = "INSERT INTO glpi_history (FK_glpi_device, device_type, device_internal_type, linked_action, user_name, date_mod,
		id_search_option, old_value, new_value)  
		VALUES ('$id_device', '$device_type', '$device_internal_type', '$linked_action','". addslashes($username)."', '$date_mod',
		'$id_search_option', '".utf8_substr($old_value,0,250)."', '".utf8_substr($new_value,0,250)."');";
		$DB->query($query)  or die($DB->error());
	}

}

/**
 * Construct  history for device
 *
 * 
 *
 * @param $id_device ID of the device
 * @param $device_type ID of the device type
 * @param $oldvalues old values updated
 * @param $values all values of the item
 **/
function constructHistory($id_device,$device_type,&$oldvalues,&$values) {

	global $LINK_ID_TABLE, $LANG ;

	if (count($oldvalues)){
		// needed to have  $SEARCH_OPTION
		$SEARCH_OPTION=getSearchOptions();

		foreach ($oldvalues as $key => $oldval){
			$changes=array();
			// Parsing $SEARCH_OPTIONS to find infocom 
			if ($device_type==INFOCOM_TYPE) {
				$ic=new Infocom();
				if ($ic->getFromDB($values['ID'])){
					$real_device_type=$ic->fields['device_type'];
					$id_device=$ic->fields['FK_device'];
					if (isset($SEARCH_OPTION[$real_device_type])) foreach($SEARCH_OPTION[$real_device_type] as $key2 => $val2){
						if(($val2["field"]==$key&&strpos($val2['table'],'infocoms')) || 
							($key=='budget'&&$val2['table']=='glpi_dropdown_budget') ||
							($key=='FK_enterprise'&&$val2['table']=='glpi_enterprises_infocoms')) {
							$id_search_option=$key2; // Give ID of the $SEARCH_OPTION
							if ($val2["table"]=="glpi_infocoms"){
								// 1st case : text field -> keep datas
								$changes=array($id_search_option, addslashes($oldval),$values[$key]);
							} else if ($val2["table"]=="glpi_enterprises_infocoms") {
								// 2nd case ; link field -> get data from glpi_enterprises
								$changes=array($id_search_option,  addslashes(getDropdownName("glpi_enterprises",$oldval)), addslashes(getDropdownName("glpi_enterprises",$values[$key])));
							} else  {
								// 3rd case ; link field -> get data from dropdown (budget)
								$changes=array($id_search_option,  addslashes(getDropdownName( $val2["table"],$oldval)), addslashes(getDropdownName( $val2["table"],$values[$key])));
							}
						break; // foreach exit
						}
					}
				}
			} else {
				$real_device_type=$device_type;
				// Parsing $SEARCH_OPTION, check if an entry exists matching $key
				if (isset($SEARCH_OPTION[$device_type])){
					foreach($SEARCH_OPTION[$device_type] as $key2 => $val2){
				
						// Linkfield or standard field not massive action enable
						if($val2["linkfield"]==$key 
							|| ( empty($val2["linkfield"]) && $key == $val2["field"]) ){
							$id_search_option=$key2; // Give ID of the $SEARCH_OPTION
				
							if($val2["table"]==$LINK_ID_TABLE[$device_type]){
								// 1st case : text field -> keep datas
								$changes=array($id_search_option, addslashes($oldval),$values[$key]);
							}else {
								// 2nd case ; link field -> get data from dropdown
								$changes=array($id_search_option,  addslashes(getDropdownName( $val2["table"],$oldval)), addslashes(getDropdownName( $val2["table"],$values[$key])));
							}
							break;
						}
					} 
				}
			}
		
			if (count($changes)){
				historyLog ($id_device,$real_device_type,$changes);
			}

		}
	}
} // function construct_history



/**
 * Show History
 ** 
 * Show history for a device 
 *
 * @param $id_device
 * @param $device_type
 **/
function showHistory($device_type,$id_device){

	global $DB, $LINK_ID_TABLE,$LANG;	
	
	$SEARCH_OPTION=getSearchOptions();
	
	if (isset($_REQUEST["start"])) {
		$start = $_REQUEST["start"];
	} else {
		$start = 0;
	}

	// Total Number of events
	$number = countElementsInTable("glpi_history", "FK_glpi_device=$id_device AND device_type=$device_type");

	// No Events in database
	if ($number < 1) {
		echo "<div class='center'>";
		echo "<table class='tab_cadre_fixe'>";
		echo "<tr><th>".$LANG["event"][20]."</th></tr>";
		echo "</table>";
		echo "</div><br>";
		return;
	}

	// Display the pager
	printAjaxPager($LANG["title"][38],$start,$number);

	$query="SELECT * 
		FROM glpi_history 
		WHERE FK_glpi_device='".$id_device."' AND device_type='".$device_type."'
		ORDER BY  ID DESC LIMIT ".intval($start)."," . intval($_SESSION['glpilist_limit']);

	//echo $query;

	// Get results
	$result = $DB->query($query);

	// Output events
	echo "<div class='center'><table class='tab_cadre_fixe'>";
	//echo "<tr><th colspan='5'>".$LANG["title"][38]."</th></tr>";
	echo "<tr><th>".$LANG["common"][2]."</th><th>".$LANG["common"][27]."</th><th>".$LANG["common"][34]."</th><th>".$LANG["event"][18]."</th><th>".$LANG["event"][19]."</th></tr>";
	while ($data =$DB->fetch_array($result)){ 
		$display_history = true;
		$ID = $data["ID"];
		$date_mod=convDateTime($data["date_mod"]);
		$user_name = $data["user_name"];
		$field="";
		// This is an internal device ?
		if($data["linked_action"]){
			// Yes it is an internal device

			switch ($data["linked_action"]){

				case HISTORY_DELETE_ITEM :
					$change = $LANG["log"][22];	
					break;
				case HISTORY_RESTORE_ITEM :
					$change = $LANG["log"][23];	
					break;

				case HISTORY_ADD_DEVICE :
					$field=getDictDeviceLabel($data["device_internal_type"]);
					$change = $LANG["devices"][25]."&nbsp;<strong>:</strong>&nbsp;\"".$data[ "new_value"]."\"";	
					break;

				case HISTORY_UPDATE_DEVICE :
					$field=getDictDeviceLabel($data["device_internal_type"]);
					$change = getDeviceSpecifityLabel($data["device_internal_type"])."&nbsp;:&nbsp;\"".$data[ "old_value"]."\"&nbsp;<strong>--></strong>&nbsp;\"".$data[ "new_value"]."\"";	
					break;

				case HISTORY_DELETE_DEVICE :
					$field=getDictDeviceLabel($data["device_internal_type"]);
					$change = $LANG["devices"][26]."&nbsp;<strong>:</strong>&nbsp;"."\"".$data["old_value"]."\"";	
					break;
				case HISTORY_INSTALL_SOFTWARE :
					$field=$LANG["help"][31];
					$change = $LANG["software"][44]."&nbsp;<strong>:</strong>&nbsp;"."\"".$data["new_value"]."\"";	
					break;				
				case HISTORY_UNINSTALL_SOFTWARE :
					$field=$LANG["help"][31];
					$change = $LANG["software"][45]."&nbsp;<strong>:</strong>&nbsp;"."\"".$data["old_value"]."\"";	
					break;	
				case HISTORY_DISCONNECT_DEVICE:
					$ci=new CommonItem();
					$ci->setType($data["device_internal_type"]);
					$field=$ci->getType();
					$change = $LANG["central"][6]."&nbsp;<strong>:</strong>&nbsp;"."\"".$data["old_value"]."\"";	
					break;	
				case HISTORY_CONNECT_DEVICE:
					$ci=new CommonItem();
					$ci->setType($data["device_internal_type"]);
					$field=$ci->getType();
					$change = $LANG["log"][55]."&nbsp;<strong>:</strong>&nbsp;"."\"".$data["new_value"]."\"";	
					break;	
				case HISTORY_OCS_IMPORT:
					if (haveRight("view_ocsng","r")){
						$field="";
						$change = $LANG["ocsng"][7]." ".$LANG["ocsng"][45]."&nbsp;<strong>:</strong>&nbsp;"."\"".$data["new_value"]."\"";	
					} else {
						$display_history = false;
					}
						
					break;	
				case HISTORY_OCS_DELETE:
					if (haveRight("view_ocsng","r")){
						$field="";
						$change = $LANG["ocsng"][46]." ".$LANG["ocsng"][45]."&nbsp;<strong>:</strong>&nbsp;"."\"".$data["old_value"]."\"";	
					} else {
						$display_history = false;
					}

					break;	
				case HISTORY_OCS_LINK:
					if (haveRight("view_ocsng","r")){
						$ci=new CommonItem();
						$ci->setType($data["device_internal_type"]);
						$field=$ci->getType();
						$change = $LANG["ocsng"][47]." ".$LANG["ocsng"][45]."&nbsp;<strong>:</strong>&nbsp;"."\"".$data["new_value"]."\"";	
					} else {
						$display_history = false;
					}

					break;	
				case HISTORY_OCS_IDCHANGED:
					if (haveRight("view_ocsng","r")){
						$field="";
						$change = $LANG["ocsng"][48]." "."&nbsp;<strong>:</strong>&nbsp;"."\"".$data["old_value"]."\" --> &nbsp;<strong>:</strong>&nbsp;"."\"".$data["new_value"]."\"";	
					} else {
						$display_history = false;
					}

					break;	
					
				case HISTORY_LOG_SIMPLE_MESSAGE:
					$field="";
					$change = $data["new_value"];	
					break;			
			}
		}else{
			$fieldname="";
			// It's not an internal device
			foreach($SEARCH_OPTION[$device_type] as $key2 => $val2){

				if($key2==$data["id_search_option"]){
					$field= $val2["name"];
					$fieldname=$val2["field"];
				}
			}

			switch ($fieldname){
				case "comments" : 
					$change =$LANG["log"][64];
					break;
				case "notes" : 
					$change =$LANG["log"][67];
					break;
				default :
					$change = "\"".$data[ "old_value"]."\"&nbsp;<strong>--></strong>&nbsp;\"".$data[ "new_value"]."\"";
			}
		}// fin du else

		if ($display_history)
		{
			// show line 
			echo "<tr class='tab_bg_2'>";
	
			echo "<td>$ID</td><td>$date_mod</td><td>$user_name</td><td>$field</td><td width='60%'>$change</td>"; 
			echo "</tr>";
		}
	}

	echo "</table></div>";

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
	
	global $DB,$CFG_GLPI, $LANG;
	if ($level <= $CFG_GLPI["event_loglevel"] && !$DB->isSlave()) { 
		$query = "INSERT INTO glpi_event_log VALUES (NULL, '".addslashes($item)."', '".addslashes($itemtype)."', '".$_SESSION["glpi_currenttime"]."', '".addslashes($service)."', '".addslashes($level)."', '".addslashes($event)."')";
		$result = $DB->query($query);    

	}
}

/**
 * Return arrays for function showEvent et lastEvent
 *
 **/
function logArray(){

	global $LANG;

	$logItemtype=array("system"=>$LANG["log"][1],
			"computers"=>$LANG["log"][2],
			"monitors"=>$LANG["log"][3],
			"printers"=>$LANG["log"][4],
			"software"=>$LANG["log"][5],
			"networking"=>$LANG["log"][6],
			"cartridges"=>$LANG["log"][7],
			"peripherals"=>$LANG["log"][8],
			"consumables"=>$LANG["log"][9],
			"tracking"=>$LANG["log"][10],
			"contacts"=>$LANG["log"][11],
			"enterprises"=>$LANG["log"][12],
			"documents"=>$LANG["log"][13],
			"knowbase"=>$LANG["log"][14],
			"users"=>$LANG["log"][15],
			"infocom"=>$LANG["log"][19],
			"devices"=>$LANG["log"][18],
			"links"=>$LANG["log"][38],
			"typedocs"=>$LANG["log"][39],
			"planning"=>$LANG["log"][16],
			"reservation"=>$LANG["log"][42],
			"contracts"=>$LANG["log"][17],
			"phones"=>$LANG["log"][43],
			"dropdown"=>$LANG["log"][44],
			"groups"=>$LANG["log"][47],
			"entity"=>$LANG["log"][63],
			"rules"=>$LANG["log"][65],
			"reminder"=>$LANG["log"][81],
			"transfers"=>$LANG["transfer"][1]);


	$logService=array("inventory"=>$LANG["Menu"][38],
			"tracking"=>$LANG["Menu"][5],
			"planning"=>$LANG["Menu"][29],
			"tools"=>$LANG["Menu"][18],
			"financial"=>$LANG["Menu"][26],
			"login"=>$LANG["log"][55],
			"setup"=>$LANG["common"][12],
			"security"=>$LANG["log"][66],
			"reservation"=>$LANG["log"][58],
			"cron"=>$LANG["log"][59],
			"document"=>$LANG["Menu"][27],
			"plugin"=>$LANG["common"][29]);

	return array($logItemtype,$logService);

}

function displayItemLogID($itemtype,$item){
	global $CFG_GLPI;

	if ($item=="-1" || $item=="0") {
		echo "&nbsp;";//$item;
	} else {
		if ($itemtype=="infocom"){
			echo "<a href='#' onClick=\"window.open('".$CFG_GLPI["root_doc"]."/front/infocom.show.php?ID=$item','infocoms','location=infocoms,width=1000,height=400,scrollbars=no')\">$item</a>";					
		} else {
			if ($item=="-1" || $item=="0") {
				echo "&nbsp;";//$item;
			} else {
				switch ($itemtype){
					case "rules" :
						echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/rule.generic.form.php?ID=".$item."\">".$item."</a>";
						break;
					case "infocom" :
						echo "<a href='#' onClick=\"window.open('".$CFG_GLPI["root_doc"]."/front/infocom.show.php?ID=$item','infocoms','location=infocoms,width=1000,height=400,scrollbars=no')\">$item</a>";					
						break;
					case "devices":
						echo $item;
						break;
					case "reservation":
						echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/reservation.php?show=resa&amp;ID=".$item."\">$item</a>";
						break;
					default :
					if ($itemtype[strlen($itemtype)-1]=='s'){
						$show=substr($itemtype,0,strlen($itemtype)-1);
					}else $show=$itemtype;
						
					echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/".$show.".form.php?ID=";
					echo $item;
					echo "\">$item</a>";
					break;
				}
			}			
		}
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
 * @param $user
 **/
function showAddEvents($target,$order,$sort,$user="") {
	// Show events from $result in table form

	global $DB,$CFG_GLPI, $LANG;

	list($logItemtype,$logService)=logArray();

	// define default sorting

	if (!$sort) {
		$sort = "date";
		$order = "DESC";
	}

	$usersearch="%";
	if (!empty($user))
		$usersearch=$user." ";

	// Query Database
	$query = "SELECT * 
		FROM glpi_event_log 
		WHERE message LIKE '".$usersearch.addslashes($LANG["log"][20])."%' 
		ORDER BY $sort $order LIMIT 0,".intval($_SESSION["glpinum_of_events"]);

	// Get results
	$result = $DB->query($query);


	// Number of results
	$number = $DB->numrows($result);

	// No Events in database
	if ($number < 1) {
		echo "<br>";
		echo "<table class='tab_cadrehov'>";
		echo "<tr><th>".$LANG["central"][4]."</th></tr>";
		echo "</table>";
		echo "<br>";
		return;
	}

	// Output events
	$i = 0;

	echo "<br><table  class='tab_cadrehov'>";
	echo "<tr><th colspan='5'><a href=\"".$CFG_GLPI["root_doc"]."/front/log.php\">".$LANG["central"][2]." ".$_SESSION["glpinum_of_events"]." ".$LANG["central"][8]."</a></th></tr>";
	echo "<tr>";

	echo "<th colspan='2'>";
	if ($sort=="item") {
		if ($order=="DESC") echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/puce-down.png\" alt='' title=''>";
		else echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/puce-up.png\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=item&amp;order=".($order=="ASC"?"DESC":"ASC")."\">".$LANG["event"][0]."</a></th>";

	echo "<th>";
	if ($sort=="date") {
		if ($order=="DESC") echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/puce-down.png\" alt='' title=''>";
		else echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/puce-up.png\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=date&amp;order=".($order=="ASC"?"DESC":"ASC")."\">".$LANG["common"][27]."</a></th>";

	echo "<th width='8%'>";
	if ($sort=="service") {
		if ($order=="DESC") echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/puce-down.png\" alt='' title=''>";
		else echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/puce-up.png\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=service&amp;order=".($order=="ASC"?"DESC":"ASC")."\">".$LANG["event"][2]."</a></th>";

	echo "<th width='60%'>";
	if ($sort=="message") {
		if ($order=="DESC") echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/puce-down.png\" alt='' title=''>";
		else echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/puce-up.png\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=message&amp;order=".($order=="ASC"?"DESC":"ASC")."\">".$LANG["event"][4]."</a></th></tr>";

	while ($i < $number) {
		$ID = $DB->result($result, $i, "ID");
		$item = $DB->result($result, $i, "item");
		$itemtype = $DB->result($result, $i, "itemtype");
		$date = $DB->result($result, $i, "date");
		$service = $DB->result($result, $i, "service");
		//$level = $DB->result($result, $i, "level");
		$message = $DB->result($result, $i, "message");

		echo "<tr class='tab_bg_2'>";
		echo "<td>".$logItemtype[$itemtype].":</td><td class='center'>";

		displayItemLogID($itemtype,$item);
		echo "</td><td  class='center'>".convDateTime($date)."</td><td class='center'>".$logService[$service]."</td><td>$message</td>";
		echo "</tr>";

		$i++; 
	}

	echo "</table><br>";
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
 * @param $start
 **/
function showEvents($target,$order,$sort,$start=0) {
	// Show events from $result in table form

	global $DB,$CFG_GLPI, $LANG;

	list($logItemtype,$logService)=logArray();


	// define default sorting

	if (!$sort) {
		$sort = "date";
	}
	if ($order!="ASC"){
		$order = "DESC";
	}

	// Query Database
	$query = "SELECT * FROM glpi_event_log ORDER BY `$sort` $order";

	$query_limit = "SELECT * FROM glpi_event_log ORDER BY `$sort` $order LIMIT ".intval($start).",".intval($_SESSION['glpilist_limit']);
	// Get results
	$result = $DB->query($query);


	// Number of results
	$numrows = $DB->numrows($result);
	$result = $DB->query($query_limit);
	$number = $DB->numrows($result);

	// No Events in database
	if ($number < 1) {
		echo "<div class='center'><strong>".$LANG["central"][4]."</strong></div>";
		return;
	}

	// Output events
	$i = 0;

	echo "<div class='center'>";
	$parameters="sort=$sort&amp;order=$order";
	printPager($start,$numrows,$target,$parameters);

	echo "<table class='tab_cadre_fixe'>";
	echo "<tr>";

	echo "<th colspan='2'>";
	if ($sort=="item") {
		if ($order=="DESC") echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/puce-down.png\" alt='' title=''>";
		else echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/puce-up.png\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=item&amp;order=".($order=="ASC"?"DESC":"ASC")."\">".$LANG["event"][0]."</a></th>";

	echo "<th>";
	if ($sort=="date") {
		if ($order=="DESC") echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/puce-down.png\" alt='' title=''>";
		else echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/puce-up.png\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=date&amp;order=".($order=="ASC"?"DESC":"ASC")."\">".$LANG["common"][27]."</a></th>";

	echo "<th width='8%'>";
	if ($sort=="service") {
		if ($order=="DESC") echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/puce-down.png\" alt='' title=''>";
		else echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/puce-up.png\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=service&amp;order=".($order=="ASC"?"DESC":"ASC")."\">".$LANG["event"][2]."</a></th>";

	echo "<th width='8%'>";
	if ($sort=="level") {
		if ($order=="DESC") echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/puce-down.png\" alt='' title=''>";
		else echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/puce-up.png\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=level&amp;order=".($order=="ASC"?"DESC":"ASC")."\">".$LANG["event"][3]."</a></th>";

	echo "<th width='50%'>";
	if ($sort=="message") {
		if ($order=="DESC") echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/puce-down.png\" alt='' title=''>";
		else echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/puce-up.png\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=message&amp;order=".($order=="ASC"?"DESC":"ASC")."\">".$LANG["event"][4]."</a></th></tr>";

	while ($i < $number) {
		$ID = $DB->result($result, $i, "ID");
		$item = $DB->result($result, $i, "item");
		$itemtype = $DB->result($result, $i, "itemtype");
		$date = $DB->result($result, $i, "date");
		$service = $DB->result($result, $i, "service");
		$level = $DB->result($result, $i, "level");
		$message = $DB->result($result, $i, "message");
		
		echo "<tr class='tab_bg_2'>";
		echo "<td>".(isset($logItemtype[$itemtype])?$logItemtype[$itemtype]:"&nbsp;").":</td><td class='center'><strong>"; 
		displayItemLogID($itemtype,$item);	
		echo "</strong></td><td>".convDateTime($date)."</td><td class='center'>".(isset($logService[$service])?$logService[$service]:$service)."</td><td class='center'>$level</td><td>$message</td>";
		echo "</tr>";

		$i++; 
	}

	echo "</table></div><br>";
}

?>
