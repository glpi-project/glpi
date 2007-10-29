<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2007 by the INDEPNET Development Team.

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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}


/**
 * Print out a link to add directly a new consumable from a consumable type.
 *
 * Print out the link witch make a new consumable from consumable type idetified by $ID
 *
 *@param $ID Consumable type identifier.
 *
 *
 *@return Nothing (displays)
 **/
function showConsumableAdd($ID) {

	global $CFG_GLPI,$LANG;

	if (!haveRight("consumable","w")) return false;

	echo "<form method='post'  action=\"".$CFG_GLPI["root_doc"]."/front/consumable.edit.php\">";
	echo "<div class='center'>&nbsp;<table class='tab_cadre_fixe'>";
	echo "<tr><td align='center' class='tab_bg_2'><strong>";
	echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/consumable.edit.php?add=add&amp;tID=$ID\">";
	echo $LANG["consumables"][17];
	echo "</a></strong></td>";
	echo "<td align='center' class='tab_bg_2'>";
	echo "<input type='submit' name='add_several' value=\"".$LANG["buttons"][8]."\" class='submit'>";
	echo "<input type='hidden' name='tID' value=\"$ID\">\n";

	echo "&nbsp;&nbsp;";
	dropdownInteger('to_add',1,1,100);
	echo "&nbsp;&nbsp;";
	echo $LANG["consumables"][16];
	echo "</td></tr>";
	echo "</table></div>";
	echo "</form><br>";
}
/**
 * Print out the consumables of a defined type
 *
 * Print out all the consumables that are issued from the consumable type identified by $ID
 *
 *@param $tID integer : Consumable type identifier.
 *@param $show_old boolean : show old consumables or not. 
 *
 *@return Nothing (displays)
 **/
function showConsumables ($tID,$show_old=0) {

	global $DB,$CFG_GLPI,$LANG;

	if (!haveRight("consumable","r")) return false;
	$canedit=haveRight("consumable","w");

	$cartype=new ConsumableType();
	
	
	if ($cartype->getFromDB($tID)){

		$query = "SELECT count(*) AS COUNT  FROM glpi_consumables WHERE (FK_glpi_consumables_type = '$tID')";
	
		if ($result = $DB->query($query)) {
			if ($DB->result($result,0,0)!=0) { 
				$total=$DB->result($result, 0, "COUNT");
				$unused=getUnusedConsumablesNumber($tID);
				$old=getOldConsumablesNumber($tID);
				if (!$show_old&&$canedit){
					echo "<form method='post' action='".$CFG_GLPI["root_doc"]."/front/consumable.edit.php'>";
					echo "<input type='hidden' name='tID' value=\"$tID\">\n";
				}
				echo "<br><div class='center'><table cellpadding='2' class='tab_cadre_fixe'>";
				if ($show_old==0){
					echo "<tr><th colspan='7'>";
					echo $total;
					echo "&nbsp;".$LANG["consumables"][16]."&nbsp;-&nbsp;$unused&nbsp;".$LANG["consumables"][13]."&nbsp;-&nbsp;$old&nbsp;".$LANG["consumables"][15]."</th></tr>";
				}
				else { // Old
					echo "<tr><th colspan='8'>";
					echo $LANG["consumables"][35];
					echo "</th></tr>";
	
				}
				$i=0;
				echo "<tr><th>".$LANG["common"][2]."</th><th>".$LANG["consumables"][23]."</th><th>".$LANG["cartridges"][24]."</th><th>".$LANG["consumables"][26]."</th>";
	
	
				if ($show_old){
					echo "<th>".$LANG["setup"][57]."</th>";
				}
	
				echo "<th>".$LANG["financial"][3]."</th>";
	
				if (!$show_old&&$canedit){
					echo "<th>";
					dropdownAllUsers("id_user",0,1,$cartype->fields["FK_entities"]);
					echo "&nbsp;<input type='submit' class='submit' name='give' value='".$LANG["consumables"][32]."'>";
					echo "</th>";
				} else {echo "<th>&nbsp;</th>";}

				if ($canedit){
					echo "<th>&nbsp;</th>";
				}
				echo "</tr>";
			} else {
	
				echo "<br>";
				echo "<div class='center'><strong>".$LANG["consumables"][7]."</strong></div>";
				return;
			}
		}
	
		$where="";
		$leftjoin="";
		$addselect="";
		if ($show_old==0){ // NEW
			$where= " AND date_out IS NULL ORDER BY date_in, ID";
		} else { //OLD
			$where= " AND date_out IS NOT NULL ORDER BY date_out DESC, date_in, ID";
			$leftjoin=" LEFT JOIN glpi_users ON (glpi_users.ID = glpi_consumables.id_user) ";
			$addselect= ", glpi_users.realname AS REALNAME, glpi_users.firstname AS FIRSTNAME, glpi_users.name AS USERNAME ";
		}
	
		$query = "SELECT glpi_consumables.* $addselect FROM glpi_consumables $leftjoin WHERE (FK_glpi_consumables_type = '$tID') $where";
	
		if ($result = $DB->query($query)) {			
			$number=$DB->numrows($result);
			while ($data=$DB->fetch_array($result)) {
				$date_in=convDate($data["date_in"]);
				$date_out=convDate($data["date_out"]);
	
				echo "<tr  class='tab_bg_1'><td class='center'>";
				echo $data["ID"]; 
				echo "</td><td class='center'>";
				echo getConsumableStatus($data["ID"]);
				echo "</td><td class='center'>";
				echo $date_in;
				echo "</td><td class='center'>";
				echo $date_out;		
				echo "</td>";
	
				if ($show_old){
					echo "<td class='center'>";
					if (!empty($data["REALNAME"])) {
						echo $data["REALNAME"];
						if (!empty($data["FIRSTNAME"]))
							echo " ".$data["FIRSTNAME"];
					}
					else echo $data["USERNAME"];
					echo "</td>";
				}
	
				echo "<td class='center'>";
				showDisplayInfocomLink(CONSUMABLE_ITEM_TYPE,$data["ID"],1);
				echo "</td>";
	
	
				if ($show_old==0&&$canedit){
					echo "<td class='center'>";
					echo "<input type='checkbox' name='out[".$data["ID"]."]'>";
					echo "</td>";
				}
	
				if ($show_old!=0&&$canedit){
					echo "<td class='center'>";
					echo "<a href='".$CFG_GLPI["root_doc"]."/front/consumable.edit.php?restore=restore&amp;ID=".$data["ID"]."&amp;tID=$tID'>".$LANG["consumables"][37]."</a>";
					echo "</td>";
				}						
	
				echo "<td class='center'>";
	
				echo "<a href='".$CFG_GLPI["root_doc"]."/front/consumable.edit.php?delete=delete&amp;ID=".$data["ID"]."&amp;tID=$tID'>".$LANG["buttons"][6]."</a>";
				echo "</td></tr>";
	
			}	
		}	
		echo "</table></div>\n\n";
		if (!$show_old&&$canedit){
			echo "</form>";
		}
	}
}



/**
 * Print the consumable count HTML array for a defined consumable type
 *
 * Print the consumable count HTML array for the consumable type $tID
 *
 *@param $tID integer: consumable type identifier.
 *@param $alarm integer: threshold alarm value.
 *@param $nohtml integer: Return value without HTML tags.
 *
 *@return string to display
 *
 **/
function countConsumables($tID,$alarm,$nohtml=0) {

	global $DB,$CFG_GLPI, $LANG;


	$out="";
	// Get total
	$total = getConsumablesNumber($tID);

	if ($total!=0) {
		$unused=getUnusedConsumablesNumber($tID);
		$old=getOldConsumablesNumber($tID);

		$highlight="";
		if ($unused<=$alarm)
			$highlight="class='tab_bg_1_2'";
		if (!$nohtml)
			$out.= "<div $highlight>".$LANG["common"][33].":&nbsp;$total&nbsp;&nbsp;&nbsp;<strong>".$LANG["consumables"][13].": $unused</strong>&nbsp;&nbsp;&nbsp;".$LANG["consumables"][15].": $old</div>";			
		else $out.= $LANG["common"][33].": $total   ".$LANG["consumables"][13].": $unused   ".$LANG["consumables"][15].": $old";			

	} else {
		if (!$nohtml)
			$out.= "<div class='tab_bg_1_2'><i>".$LANG["consumables"][9]."</i></div>";
		else $out.= $LANG["consumables"][9];
	}
	return $out;
}	

/**
 * count how many consumable for a consumable type
 *
 * count how many consumable for the consumable type $tID
 *
 *@param $tID integer: consumable type identifier.
 *
 *@return integer : number of consumable counted.
 *
 **/
function getConsumablesNumber($tID){
	global $DB;
	$query = "SELECT ID FROM glpi_consumables WHERE ( FK_glpi_consumables_type = '$tID')";
	$result = $DB->query($query);
	return $DB->numrows($result);
}

/**
 * count how many old consumable for a consumable type
 *
 * count how many old consumable for the consumable type $tID
 *
 *@param $tID integer: consumable type identifier.
 *
 *@return integer : number of old consumable counted.
 *
 **/
function getOldConsumablesNumber($tID){
	global $DB;
	$query = "SELECT ID FROM glpi_consumables WHERE ( FK_glpi_consumables_type = '$tID'  AND date_out IS NOT NULL)";
	$result = $DB->query($query);
	return $DB->numrows($result);
}
/**
 * count how many consumable unused for a consumable type
 *
 * count how many consumable unused for the consumable type $tID
 *
 *@param $tID integer: consumable type identifier.
 *
 *@return integer : number of consumable unused counted.
 *
 **/
function getUnusedConsumablesNumber($tID){
	global $DB;
	$query = "SELECT ID FROM glpi_consumables WHERE ( FK_glpi_consumables_type = '$tID'  AND date_out IS NULL)";
	$result = $DB->query($query);
	return $DB->numrows($result);
}


/**
 * To be commented
 *
 * 
 *
 *@param $cID integer : consumable type.
 *
 *@return 
 *
 **/
function isNewConsumable($cID){
	global $DB;
	$query = "SELECT ID FROM glpi_consumables WHERE ( ID= '$cID' AND date_out IS NULL)";
	$result = $DB->query($query);
	return ($DB->numrows($result)==1);
}

/**
 * To be commented
 *
 * 
 *
 *@param $cID integer : consumable type.
 *
 *@return 
 *
 **/
function isOldConsumable($cID){
	global $DB;
	$query = "SELECT ID FROM glpi_consumables WHERE ( ID= '$cID' AND date_out IS NOT NULL)";
	$result = $DB->query($query);
	return ($DB->numrows($result)==1);
}

/**
 * Get the dict value for the status of a consumable
 *
 * 
 *
 *@param $cID integer : consumable ID.
 *
 *@return string : dict value for the consumable status.
 *
 **/
function getConsumableStatus($cID){
	global $LANG;
	if (isNewConsumable($cID)) return $LANG["consumables"][20];
	else if (isOldConsumable($cID)) return $LANG["consumables"][22];
}


function showConsumableSummary($target){
	global $DB,$LANG;

	if (!haveRight("consumable","r")) return false;

	$query = "SELECT COUNT(*) AS COUNT, FK_glpi_consumables_type, id_user FROM glpi_consumables WHERE date_out IS NOT NULL AND FK_glpi_consumables_type IN (SELECT ID FROM glpi_consumables_type ".getEntitiesRestrictRequest("WHERE","glpi_consumables_type").") GROUP BY id_user,FK_glpi_consumables_type";
	$used=array();

	if ($result=$DB->query($query)){
		if ($DB->numrows($result))
			while ($data=$DB->fetch_array($result))
				$used[$data["id_user"]][$data["FK_glpi_consumables_type"]]=$data["COUNT"];
	}

	$query = "SELECT COUNT(*) AS COUNT, FK_glpi_consumables_type FROM glpi_consumables WHERE date_out IS NULL AND FK_glpi_consumables_type IN (SELECT ID FROM glpi_consumables_type ".getEntitiesRestrictRequest("WHERE","glpi_consumables_type").") GROUP BY FK_glpi_consumables_type";
	$new=array();

	if ($result=$DB->query($query)){
		if ($DB->numrows($result))
			while ($data=$DB->fetch_array($result))
				$new[$data["FK_glpi_consumables_type"]]=$data["COUNT"];
	}

	$types=array();
	$query="SELECT * FROM glpi_consumables_type ".getEntitiesRestrictRequest("WHERE","glpi_consumables_type");
	if ($result=$DB->query($query)){
		if ($DB->numrows($result)){
			while ($data=$DB->fetch_array($result)){
				$types[$data["ID"]]=$data["name"];
			}
		}
	}
	asort($types);
	$total=array();
	if (count($types)>0){

		// Produce headline
		echo "<div class='center'><table  class='tab_cadrehov'><tr>";

		// Type			
		echo "<th>";;
		echo $LANG["setup"][57]."</th>";

		foreach ($types as $key => $type){
			echo "<th>$type</th>";
			$total[$key]=0;
		}
		echo "<th>".$LANG["common"][33]."</th>";
		echo "</tr>";

		// new
		echo "<tr class='tab_bg_2'><td><strong>".$LANG["consumables"][1]."</strong></td>";
		$tot=0;
		foreach ($types as $id_type => $type){
			if (!isset($new[$id_type])) $new[$id_type]=0;
			echo "<td class='center'>".$new[$id_type]."</td>";
			$total[$id_type]+=$new[$id_type];
			$tot+=$new[$id_type];
		}
		echo "<td class='center'>".$tot."</td>";
		echo "</tr>";

		foreach ($used as $id_user => $val){
			echo "<tr class='tab_bg_2'><td>".getUserName($id_user)."</td>";
			$tot=0;
			foreach ($types as $id_type => $type){
				if (!isset($val[$id_type])) $val[$id_type]=0;
				echo "<td class='center'>".$val[$id_type]."</td>";
				$total[$id_type]+=$val[$id_type];
				$tot+=$val[$id_type];
			}
			echo "<td class='center'>".$tot."</td>";
			echo "</tr>";
		}
		echo "<tr class='tab_bg_1'><td><strong>".$LANG["common"][33]."</strong></td>";
		$tot=0;
		foreach ($types as $id_type => $type){
			$tot+=$total[$id_type];
			echo "<td class='center'>".$total[$id_type]."</td>";
		}
		echo "<td class='center'>".$tot."</td>";
		echo "</tr>";
		echo "</table></div>";

	} else {
		echo "<div class='center'><strong>".$LANG["consumables"][7]."</strong></div>";
	}

}

function cron_consumable(){
	global $DB,$CFG_GLPI,$LANG;

	// Get cartridges type with alarm activated and last warning > config
	$query="SELECT glpi_consumables_type.ID AS consID, glpi_consumables_type.FK_entities as entity, glpi_consumables_type.ref as consref, glpi_consumables_type.name AS consname, glpi_consumables_type.alarm AS threshold, glpi_alerts.ID AS alertID, glpi_alerts.date FROM glpi_consumables_type LEFT JOIN glpi_alerts ON (glpi_consumables_type.ID = glpi_alerts.FK_device AND glpi_alerts.device_type='".CONSUMABLE_TYPE."') WHERE glpi_consumables_type.deleted='0' AND glpi_consumables_type.alarm>='0' AND (glpi_alerts.date IS NULL OR (glpi_alerts.date+".$CFG_GLPI["consumables_alert"].") < CURRENT_TIMESTAMP());";

	$result=$DB->query($query);
	$message=array();
	if ($DB->numrows($result)>0){
		while ($data=$DB->fetch_array($result)){
			if (($unused=getUnusedConsumablesNumber($data["consID"]))<=$data["threshold"]){
				if (!isset($message[$data["entity"]])){
					$message[$data["entity"]]="";
				}
				// define message alert
				$message[$data["entity"]].=$LANG["mailing"][35]." ".$data["consname"]." - ".$LANG["consumables"][2].": ".$data["consref"]." - ".$LANG["software"][20].": ".$unused."<br>\n";

				// Mark alert as done
				$alert=new Alert();
				//// if alert exists -> delete 
				if (!empty($data["alertID"])){
					$alert->delete(array("ID"=>$data["alertID"]));
				}

				$alert=new Alert();
				//// add alert
				$input["type"]=ALERT_THRESHOLD;
				$input["device_type"]=CONSUMABLE_TYPE;
				$input["FK_device"]=$data["consID"];

				$alert->add($input);
			}
		}

		if (count($message)>0){
			foreach ($message as $entity => $msg){
				$mail=new MailingAlert("alertconsumable",$msg,$entity);
				$mail->send();
				if ($CFG_GLPI["use_errorlog"]){
					logInFile("cron","Entity $entity :  $msg\n");
				}
			}
			return 1;
		}

	}
	return 0;
}
?>
