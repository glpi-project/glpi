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

	global $db;

	$date_mod=date("Y-m-d H:i:s");

	if(!empty($changes)){

		// cr�r un query avec l'insertion des ��ents fixes + changements
		$id_search_option=$changes[0];
		$old_value=$changes[1];
		$new_value=$changes[2];

		// Build query
		$query = "INSERT INTO glpi_history (FK_glpi_device,device_type,device_internal_type,linked_action,user_name,date_mod,id_search_option,old_value,new_value)  VALUES ('$id_device','$device_type','$device_internal_type','$linked_action','". addslashes(getUserName($_SESSION["glpiID"],$link=0))."','$date_mod','$id_search_option','$old_value','$new_value');";


		$db->query($query)  or die($db->error());

	}
}

/**
 * Construct  history for device
 *
 * 
 *
 * @param $id_device
 * @param $device_type
 * @param $key
 * @param $oldvalues
 * @param $newvalues
 **/
function constructHistory($id_device,$device_type,$key,$oldvalues,$newvalues) {

	global $SEARCH_OPTION, $LINK_ID_TABLE,$phproot, $lang ;

	// on ne log que les changements pas la d�inition d'un ��ent vide
	if (!empty($oldvalues)){
		$changes=array();
		// n�essaire pour avoir les $search_option
		include_once ($phproot . "/inc/search.class.php");

		// on parse le tableau $search_option, on v�ifie qu'il existe une entr� correspondante �$key
		foreach($SEARCH_OPTION[$device_type] as $key2 => $val2){

			if($val2["linkfield"]==$key){

				$id_search_option=$key2; // on r�upere dans $SEARCH_OPTION l'id_search_options

				if($val2["table"]==$LINK_ID_TABLE[$device_type]){
					// 1er cas $key est un champs normal -> on ne touche pas aux valeurs 
					$changes=array($id_search_option, addslashes($oldvalues),$newvalues);
				}else {
					//2�e cas $key est un champs li� il faut r�up�er les valeurs du dropdown
					$changes=array($id_search_option,  addslashes(getDropdownName( $val2["table"],$oldvalues)), addslashes(getDropdownName( $val2["table"],$newvalues)));
				}

			}
		} // fin foreach

		if (count($changes))
			historyLog ($id_device,$device_type,$changes);

	} // Fin if

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

	global $db,$SEARCH_OPTION, $LINK_ID_TABLE,$phproot,$lang;	

	// n�essaire pour avoir les $search_option
	include_once ($phproot . "/inc/search.class.php");

	$query="SELECT * FROM glpi_history WHERE FK_glpi_device='".$id_device."' AND device_type='".$device_type."' ORDER BY  ID DESC;";

	//echo $query;

	// Get results
	$result = $db->query($query);

	// Number of results
	$number = $db->numrows($result);

	// No Events in database
	if ($number < 1) {
		echo "<br><div align='center'>";
		echo "<table class='tab_cadre_fixe'>";
		echo "<tr><th>".$lang["event"][20]."</th></tr>";
		echo "</table>";
		echo "</div><br>";
		return;
	}

	// Output events



	echo "<div align='center'><br><table class='tab_cadre_fixe'>";
	echo "<tr><th colspan='5'>".$lang["title"][38]."</th></tr>";
	echo "<tr><th>".$lang["common"][2]."</th><th>".$lang["common"][27]."</th><th>".$lang["event"][17]."</th><th>".$lang["event"][18]."</th><th>".$lang["event"][19]."</th></tr>";
	while ($data =$db->fetch_array($result)){ 
		$ID = $data["ID"];
		$date_mod = $date_mod=convDateTime($data["date_mod"]);
		$user_name = $data["user_name"];

		// This is an internal device ?
		if($data["linked_action"]){
			// Yes it is an internal device

			switch ($data["linked_action"]){

				case HISTORY_ADD_DEVICE :
					$field=getDeviceTypeLabel($data["device_internal_type"]);
					$change = $lang["devices"][25]."&nbsp;<strong>:</strong>&nbsp;\"".$data[ "new_value"]."\"";	
					break;

				case HISTORY_UPDATE_DEVICE :
					$field=getDeviceTypeLabel($data["device_internal_type"]);
					$change = getDeviceSpecifityLabel($data["device_internal_type"])."&nbsp;:&nbsp;\"".$data[ "old_value"]."\"&nbsp;<strong>--></strong>&nbsp;\"".$data[ "new_value"]."\"";	
					break;

				case HISTORY_DELETE_DEVICE :
					$field=getDeviceTypeLabel($data["device_internal_type"]);
					$change = $lang["devices"][26]."&nbsp;<strong>:</strong>&nbsp;"."\"".$data["old_value"]."\"";	
					break;
				case HISTORY_INSTALL_SOFTWARE :
					$field=$lang["software"][10];
					$change = $lang["software"][44]."&nbsp;<strong>:</strong>&nbsp;"."\"".$data["new_value"]."\"";	
					break;				
				case HISTORY_UNINSTALL_SOFTWARE :
					$field=$lang["software"][10];
					$change = $lang["software"][45]."&nbsp;<strong>:</strong>&nbsp;"."\"".$data["old_value"]."\"";	
					break;				
			}


		}else{
			// It's not an internal device
			foreach($SEARCH_OPTION[$device_type] as $key2 => $val2){

				if($key2==$data["id_search_option"]){
					$field= $val2["name"];
				}
			}

			$change = "\"".$data[ "old_value"]."\"&nbsp;<strong>--></strong>&nbsp;\"".$data[ "new_value"]."\"";
		}// fin du else

		// show line 
		echo "<tr class='tab_bg_2'>";

		echo "<td>$ID</td><td>$date_mod</td><td>$user_name</td><td>$field</td><td width='60%'>$change</td>"; 
		echo "</tr>";

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

	global $db,$cfg_glpi, $lang;
	if ($level <= $cfg_glpi["event_loglevel"]) { 
		$query = "INSERT INTO glpi_event_log VALUES (NULL, '".addslashes($item)."', '".addslashes($itemtype)."', NOW(), '".addslashes($service)."', '".addslashes($level)."', '".addslashes($event)."')";

		$result = $db->query($query);    

	}
}

/**
 * Return arrays for function showEvent et lastEvent
 *
 **/
function logArray(){

	global $lang;

	$logItemtype=array("system"=>$lang["log"][1],
			"computers"=>$lang["log"][2],
			"monitors"=>$lang["log"][3],
			"printers"=>$lang["log"][4],
			"software"=>$lang["log"][5],
			"networking"=>$lang["log"][6],
			"cartridges"=>$lang["log"][7],
			"peripherals"=>$lang["log"][8],
			"consumables"=>$lang["log"][9],
			"tracking"=>$lang["log"][10],
			"contacts"=>$lang["log"][11],
			"enterprises"=>$lang["log"][12],
			"documents"=>$lang["log"][13],
			"knowbase"=>$lang["log"][14],
			"users"=>$lang["log"][15],
			"infocom"=>$lang["log"][19],
			"devices"=>$lang["log"][18],
			"links"=>$lang["log"][38],
			"typedocs"=>$lang["log"][39],
			"planning"=>$lang["log"][16],
			"reservation"=>$lang["log"][42],
			"contracts"=>$lang["log"][17],
			"phones"=>$lang["log"][43],
			"dropdown"=>$lang["log"][44],
			"groups"=>$lang["log"][47]);

	$logService=array("inventory"=>$lang["log"][50],
			"tracking"=>$lang["log"][51],
			"planning"=>$lang["log"][52],
			"tools"=>$lang["log"][53],
			"financial"=>$lang["log"][54],
			"login"=>$lang["log"][55],
			"setup"=>$lang["log"][57],
			"reservation"=>$lang["log"][58],
			"cron"=>$lang["log"][59],
			"document"=>$lang["log"][56]);

	return array($logItemtype,$logService);

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

	global $db,$cfg_glpi, $lang;

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
	$query = "SELECT * FROM glpi_event_log WHERE message LIKE '".$usersearch.addslashes($lang["log"][20])."%' ORDER BY $sort $order LIMIT 0,".$cfg_glpi["num_of_events"];

	// Get results
	$result = $db->query($query);


	// Number of results
	$number = $db->numrows($result);

	// No Events in database
	if ($number < 1) {
		echo "<br><div align='center'>";
		echo "<table class='tab_cadrehov'>";
		echo "<tr><th>".$lang["central"][4]."</th></tr>";
		echo "</table>";
		echo "</div><br>";
		return;
	}

	// Output events
	$i = 0;

	echo "<div align='center'><br><table  class='tab_cadrehov'>";
	echo "<tr><th colspan='5'><a href=\"".$cfg_glpi["root_doc"]."/front/log.php\">".$lang["central"][2]." ".$cfg_glpi["num_of_events"]." ".$lang["central"][8]."</a></th></tr>";
	echo "<tr>";

	echo "<th colspan='2'>";
	if ($sort=="item") {
		if ($order=="DESC") echo "<img src=\"".$cfg_glpi["root_doc"]."/pics/puce-down.png\" alt='' title=''>";
		else echo "<img src=\"".$cfg_glpi["root_doc"]."/pics/puce-up.png\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=item&amp;order=".($order=="ASC"?"DESC":"ASC")."\">".$lang["event"][0]."</a></th>";

	echo "<th>";
	if ($sort=="date") {
		if ($order=="DESC") echo "<img src=\"".$cfg_glpi["root_doc"]."/pics/puce-down.png\" alt='' title=''>";
		else echo "<img src=\"".$cfg_glpi["root_doc"]."/pics/puce-up.png\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=date&amp;order=".($order=="ASC"?"DESC":"ASC")."\">".$lang["common"][27]."</a></th>";

	echo "<th width='8%'>";
	if ($sort=="service") {
		if ($order=="DESC") echo "<img src=\"".$cfg_glpi["root_doc"]."/pics/puce-down.png\" alt='' title=''>";
		else echo "<img src=\"".$cfg_glpi["root_doc"]."/pics/puce-up.png\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=service&amp;order=".($order=="ASC"?"DESC":"ASC")."\">".$lang["event"][2]."</a></th>";

	echo "<th width='60%'>";
	if ($sort=="message") {
		if ($order=="DESC") echo "<img src=\"".$cfg_glpi["root_doc"]."/pics/puce-down.png\" alt='' title=''>";
		else echo "<img src=\"".$cfg_glpi["root_doc"]."/pics/puce-up.png\" alt='' title=''>";
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
		echo "<td>".$logItemtype[$itemtype].":</td><td align='center'><b>";
		if ($item=="-1" || $item=="0") {
			echo $item;
		} else {
			if ($itemtype=="reservation"){
				echo "<a href=\"".$cfg_glpi["root_doc"]."/front/reservation.php?show=resa&amp;ID=";
			} else {
				if ($itemtype[strlen($itemtype)-1]=='s')
					$show=substr($itemtype,0,strlen($itemtype)-1);
				else $show=$itemtype;

				echo "<a href=\"".$cfg_glpi["root_doc"]."/front/".$show.".form.php?ID=";
			}
			echo $item;
			echo "\">$item</a>";
		}			
		echo "</b></td><td><span style='font-size:9px;'>".convDateTime($date)."</span></td><td align='center'>".$logService[$service]."</td><td>$message</td>";
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
 * @param $start
 **/
function showEvents($target,$order,$sort,$start=0) {
	// Show events from $result in table form

	global $db,$cfg_glpi, $lang;

	list($logItemtype,$logService)=logArray();


	// define default sorting

	if (!$sort) {
		$sort = "date";
		$order = "DESC";
	}

	// Query Database
	$query = "SELECT * FROM glpi_event_log ORDER BY $sort $order";

	$query_limit = "SELECT * FROM glpi_event_log ORDER BY $sort $order LIMIT $start,".$cfg_glpi["list_limit"];
	// Get results
	$result = $db->query($query);


	// Number of results
	$numrows = $db->numrows($result);
	$result = $db->query($query_limit);
	$number = $db->numrows($result);

	// No Events in database
	if ($number < 1) {
		echo "<div align='center'><b>".$lang["central"][4]."</b></div>";
		return;
	}

	// Output events
	$i = 0;

	echo "<div align='center'>";
	$parameters="sort=$sort&amp;order=$order";
	printPager($start,$numrows,$target,$parameters);

	echo "<table class='tab_cadre_fixe'>";
	echo "<tr>";

	echo "<th colspan='2'>";
	if ($sort=="item") {
		if ($order=="DESC") echo "<img src=\"".$cfg_glpi["root_doc"]."/pics/puce-down.png\" alt='' title=''>";
		else echo "<img src=\"".$cfg_glpi["root_doc"]."/pics/puce-up.png\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=item&amp;order=".($order=="ASC"?"DESC":"ASC")."\">".$lang["event"][0]."</a></th>";

	echo "<th>";
	if ($sort=="date") {
		if ($order=="DESC") echo "<img src=\"".$cfg_glpi["root_doc"]."/pics/puce-down.png\" alt='' title=''>";
		else echo "<img src=\"".$cfg_glpi["root_doc"]."/pics/puce-up.png\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=date&amp;order=".($order=="ASC"?"DESC":"ASC")."\">".$lang["common"][27]."</a></th>";

	echo "<th width='8%'>";
	if ($sort=="service") {
		if ($order=="DESC") echo "<img src=\"".$cfg_glpi["root_doc"]."/pics/puce-down.png\" alt='' title=''>";
		else echo "<img src=\"".$cfg_glpi["root_doc"]."/pics/puce-up.png\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=service&amp;order=".($order=="ASC"?"DESC":"ASC")."\">".$lang["event"][2]."</a></th>";

	echo "<th width='8%'>";
	if ($sort=="level") {
		if ($order=="DESC") echo "<img src=\"".$cfg_glpi["root_doc"]."/pics/puce-down.png\" alt='' title=''>";
		else echo "<img src=\"".$cfg_glpi["root_doc"]."/pics/puce-up.png\" alt='' title=''>";
	}
	echo "<a href=\"$target?sort=level&amp;order=".($order=="ASC"?"DESC":"ASC")."\">".$lang["event"][3]."</a></th>";

	echo "<th width='60%'>";
	if ($sort=="message") {
		if ($order=="DESC") echo "<img src=\"".$cfg_glpi["root_doc"]."/pics/puce-down.png\" alt='' title=''>";
		else echo "<img src=\"".$cfg_glpi["root_doc"]."/pics/puce-up.png\" alt='' title=''>";
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

		echo "<td>".(isset($logItemtype[$itemtype])?$logItemtype[$itemtype]:"&nbsp;").":</td><td align='center'><b>"; 

		//echo "<td>$itemtype:</td><td align='center'><b>";
		if ($item=="-1" || $item=="0") {
			echo "&nbsp;";//$item;
		} else {
			if ($itemtype=="infocom"){
				echo "<a href='#' onClick=\"window.open('".$cfg_glpi["root_doc"]."/front/infocom.show.php?ID=$item','infocoms','location=infocoms,width=1000,height=600,scrollbars=no')\">$item</a>";					
			} else {
				if ($itemtype=="reservation"){
					echo "<a href=\"".$cfg_glpi["root_doc"]."/front/reservation.php?show=resa&amp;ID=";
				} else {
					if ($itemtype[strlen($itemtype)-1]=='s')
						$show=substr($itemtype,0,strlen($itemtype)-1);
					else $show=$itemtype;

					echo "<a href=\"".$cfg_glpi["root_doc"]."/front/".$show.".form.php?ID=";
				}
				echo $item;
				echo "\">$item</a>";
			}
		}			
		echo "</b></td><td>".convDateTime($date)."</td><td align='center'>".$logService[$service]."</td><td align='center'>$level</td><td>$message</td>";
		echo "</tr>";

		$i++; 
	}

	echo "</table></div><br>";
}

?>
