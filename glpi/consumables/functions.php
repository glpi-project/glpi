<?php
/*
 * @version $Id$
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
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
 
// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

include ("_relpos.php");

/**
* Print a good title for Consumable pages
*
*
*
*
*@return nothing (diplays)
*
**/
function titleConsumable(){

         GLOBAL  $lang,$HTMLRel;
         
         echo "<div align='center'><table border='0'><tr><td>";
         echo "<a href='index.php'><img src=\"".$HTMLRel."pics/consommables.png\" alt='".$lang["consumables"][6]."' title='".$lang["consumables"][6]."'></a></td><td><a  class='icon_consol' href=\"consumables-info-form.php\"><b>".$lang["consumables"][6]."</b></a></td>";
	echo "<td><a class='icon_consol' href='index.php?synthese=yes'>".$lang["state"][11]."</a></td>";
         echo "</tr></table></div>";
}

function showConsumableOnglets($target,$withtemplate,$actif){
	global $lang,$HTMLRel;
	
	$template="";
	if(!empty($withtemplate)){
		$template="&amp;withtemplate=$withtemplate";
	}

	echo "<div id='barre_onglets'><ul id='onglet'>";
	echo "<li "; if ($actif=="1"){ echo "class='actif'";} echo  "><a href='$target&amp;onglet=1$template'>".$lang["title"][26]."</a></li>";
	echo "<li "; if ($actif=="4") {echo "class='actif'";} echo "><a href='$target&amp;onglet=4$template'>".$lang["Menu"][26]."</a></li>";
	echo "<li "; if ($actif=="5") {echo "class='actif'";} echo "><a href='$target&amp;onglet=5$template'>".$lang["title"][25]."</a></li>";
	
	if(empty($withtemplate)){
	echo "<li "; if ($actif=="7") {echo "class='actif'";} echo "><a href='$target&amp;onglet=7$template'>".$lang["title"][34]."</a></li>";
	echo "<li "; if ($actif=="10") {echo "class='actif'";} echo "><a href='$target&amp;onglet=10$template'>".$lang["title"][37]."</a></li>";
	echo "<li class='invisible'>&nbsp;</li>";
	echo "<li "; if ($actif=="-1") {echo "class='actif'";} echo "><a href='$target&amp;onglet=-1$template'>".$lang["title"][29]."</a></li>";
	
	}	
	echo "<li class='invisible'>&nbsp;</li>";
	
	if (empty($withtemplate)&&preg_match("/\?ID=([0-9]+)/",$target,$ereg)){
	$ID=$ereg[1];
	$next=getNextItem("glpi_consumables_type",$ID);
	$prev=getPreviousItem("glpi_consumables_type",$ID);
	$cleantarget=preg_replace("/\?ID=([0-9]+)/","",$target);
		if ($prev>0) echo "<li><a href='$cleantarget?ID=$prev'><img src=\"".$HTMLRel."pics/left.png\" alt='".$lang["buttons"][12]."' title='".$lang["buttons"][12]."'></a></li>";
	if ($next>0) echo "<li><a href='$cleantarget?ID=$next'><img src=\"".$HTMLRel."pics/right.png\" alt='".$lang["buttons"][11]."' title='".$lang["buttons"][11]."'></a></li>";
	}
	echo "</ul></div>";
	
}


/**
* Print the consumable type form
*
*
* Print g��al consumable type form
*
*@param $target filename : where to go when done.
*@param $ID Integer : Id of the consumable type
*
*
*@return Nothing (display)
*
**/
function showConsumableTypeForm ($target,$ID) {
	// Show ConsumableType or blank form
	
	GLOBAL $cfg_glpi,$lang;

	$ct = new ConsumableType;
	$ct_spotted=false;
	
	if (!$ID) {
		
		if($ct->getEmpty()) $ct_spotted = true;
	} else {
		if($ct->getfromDB($ID)) $ct_spotted = true;
	}
	
	if ($ct_spotted){
	
	echo "<form method='post' action=\"$target\"><div align='center'>\n";
	echo "<table class='tab_cadre_fixe'>\n";
	echo "<tr><th colspan='3'><b>\n";
	if (!$ID) {
		echo $lang["consumables"][6].":";
	} else {
		echo $lang["consumables"][12]." ID $ID:";
	}		
	echo "</b></th></tr>\n";

	echo "<tr class='tab_bg_1'><td>".$lang["common"][16].":		</td>\n";
	echo "<td colspan='2'>";
	autocompletionTextField("name","glpi_consumables_type","name",$ct->fields["name"],25);	
	echo "</td></tr>\n";

	echo "<tr class='tab_bg_1'><td>".$lang["consumables"][2].":		</td>\n";
	echo "<td colspan='2'>";
	autocompletionTextField("ref","glpi_consumables_type","ref",$ct->fields["ref"],25);	
	echo "</td></tr>\n";

	echo "<tr class='tab_bg_1'><td>".$lang["common"][17].": 	</td><td colspan='2'>\n";
		dropdownValue("glpi_dropdown_consumable_type","type",$ct->fields["type"]);
	echo "</td></tr>\n";

	echo "<tr class='tab_bg_1'><td>".$lang["common"][5].": 	</td><td colspan='2'>\n";
		dropdownValue("glpi_enterprises","FK_glpi_enterprise",$ct->fields["FK_glpi_enterprise"]);
	echo "</td></tr>\n";

	echo "<tr class='tab_bg_1'><td>".$lang["common"][10].": 	</td><td colspan='2'>\n";
		dropdownUsersID("tech_num", $ct->fields["tech_num"]);
	echo "</td></tr>\n";

	echo "<tr class='tab_bg_1'><td>".$lang["consumables"][36].": 	</td><td colspan='2'>\n";
		dropdownValue("glpi_dropdown_locations","location",$ct->fields["location"]);
	echo "</td></tr>\n";

	echo "<tr class='tab_bg_1'><td>".$lang["consumables"][38].":</td><td colspan='2'><select name='alarm'>\n";
	for ($i=0;$i<=100;$i++)
		echo "<option value='$i' ".($i==$ct->fields["alarm"]?" selected ":"").">$i</option>";
	echo "</select></td></tr>\n";
	
	
	echo "<tr class='tab_bg_1'><td valign='top'>\n";
	echo $lang["common"][25].":	</td>";
	echo "<td align='center' colspan='2'><textarea cols='35' rows='4' name='comments' >".$ct->fields["comments"]."</textarea>";
	echo "</td></tr>\n";
	
	if (!$ID) {

		echo "<tr>\n";
		echo "<td class='tab_bg_2' valign='top' colspan='3'>\n";
		echo "<div align='center'><input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'></div>";
		echo "</td>";
		echo "</tr>\n";

		echo "</table></div></form>";

	} else {

		echo "<tr>\n";
                echo "<td class='tab_bg_2'></td>";
                echo "<td class='tab_bg_2' valign='top'>";
		echo "<input type='hidden' name='ID' value=\"$ID\">\n";
		echo "<div align='center'><input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'></div>";
		echo "</td>";
		echo "<td class='tab_bg_2' valign='top'>\n";
		echo "<div align='center'>";
		if ($ct->fields["deleted"]=='N')
		echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
		else {
		echo "<input type='submit' name='restore' value=\"".$lang["buttons"][21]."\" class='submit'>";
		
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".$lang["buttons"][22]."\" class='submit'>\n";
		}
		echo "</div>";
		echo "</td>";
		echo "</tr>\n";

		echo "</table></div></form>";
		
	}
	
	} else {
	
	echo "<div align='center'><b>".$lang["consumables"][7]."</b></div>";
	return false;
	}
	return true;
}
/**
* Update some elements of a consumable type in the database.
*
* Update some elements of a consumable type in the database.
*
*@param $input array : the _POST vars returned bye the cartrdge form when press update (see showconsumabletype())
*
*
*@return Nothing (call to the class member)
*TODO : error reporting.
**/
function updateConsumableType($input) {
	// Update ConsumableType in the database

	$sw = new ConsumableType;
	$sw->getFromDB($input["ID"]);
 
	// Fill the update-array with changes
	$x=0;
	foreach ($input as $key => $val) {
		if (array_key_exists($key,$sw->fields) && $sw->fields[$key] != $input[$key]) {
			$sw->fields[$key] = $input[$key];
			$updates[$x] = $key;
			$x++;
		}
	}
	if(!empty($updates)) {
	
		$sw->updateInDB($updates);
	}
}
/**
* Add  a consumable type in the database.
*
* Add from $input, a consumable type in the database. return true if it has been added correcly, false elsewhere.
*
*@param $input array : the _POST vars returned bye the cartrdge form when press add (see showconsumabletype())
*
*
*@return boolean true or false.
*
**/
function addConsumableType($input) {
	
	$sw = new ConsumableType;
	
	// dump status
	unset($input['add']);
	
	// fill array for update
	foreach ($input as $key => $val) {
		if ($key[0]!='_'&&(!isset($sw->fields[$key]) || $sw->fields[$key] != $input[$key])) {
			$sw->fields[$key] = $input[$key];
		}
	}
	return $sw->addToDB();
}

/**
* Delete a consumable type in the database.
*
* Delete a consumable type in the database.
*
*@param $input array : the _POST vars returned bye the consumable form when press delete (see showconsumabletype())
*@param $force int : how far the consumable is deleted (moved to trash or purged from db).
*
*@return Nothing (call to the class member)
*TODO : error reporting.
**/
function deleteConsumableType($input,$force=0) {
	// Delete ConsumableType
	
	$ct = new ConsumableType;
	$ct->deleteFromDB($input["ID"],$force);
} 

/**
* restore some elements of a consumable type in the database.
*
* restore some elements of a consumable type witch has been deleted but not purged in the database.
*
*@param $input array : the _POST vars returned bye the consumable form when press restore (see showconsumabletype())
*
*
*@return Nothing (call to the class member)
*TODO : error reporting.
**/
function restoreConsumableType($input) {
	// Restore ConsumableType
	
	$ct = new ConsumableType;
	$ct->restoreInDB($input["ID"]);
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
	
	GLOBAL $cfg_glpi,$lang,$HTMLRel;
	
	
	echo "<form method='post'  action=\"".$HTMLRel."consumables/consumables-edit.php\">";
	echo "<div align='center'>&nbsp;<table class='tab_cadre' width='90%' cellpadding='2'>";
	echo "<tr><td align='center' class='tab_bg_2'><b>";
	echo "<a href=\"".$cfg_glpi["root_doc"]."/consumables/consumables-edit.php?add=add&amp;tID=$ID\">";
	echo $lang["consumables"][17];
	echo "</a></b></td>";
	echo "<td align='center' class='tab_bg_2'>";
	echo "<input type='submit' name='add_several' value=\"".$lang["buttons"][8]."\" class='submit'>";
	echo "<input type='hidden' name='tID' value=\"$ID\">\n";

	echo "&nbsp;&nbsp;<select name='to_add'>";
	for ($i=1;$i<100;$i++)
	echo "<option value='$i'>$i</option>";
	echo "</select>&nbsp;&nbsp;";
	echo $lang["consumables"][16];
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

	GLOBAL $db,$cfg_glpi,$lang,$HTMLRel;
	
	

	$query = "SELECT count(ID) AS COUNT  FROM glpi_consumables WHERE (FK_glpi_consumables_type = '$tID')";

	if ($result = $db->query($query)) {
		if ($db->result($result,0,0)!=0) { 
			$total=$db->result($result, 0, "COUNT");
			$unused=getUnusedConsumablesNumber($tID);
			$old=getOldConsumablesNumber($tID);
			if (!$show_old){
				echo "<form method='post' action='".$cfg_glpi["root_doc"]."/consumables/consumables-edit.php'>";
				//echo "<input type='hidden' name='tID' value='$tID'>";
			}
			echo "<br><div align='center'><table cellpadding='2' class='tab_cadre_fixe'>";
			if ($show_old==0){
				echo "<tr><th colspan='7'>";
				echo $total;
				echo "&nbsp;".$lang["consumables"][16]."&nbsp;-&nbsp;$unused&nbsp;".$lang["consumables"][13]."&nbsp;-&nbsp;$old&nbsp;".$lang["consumables"][15]."</th></tr>";
			}
			else { // Old
				echo "<tr><th colspan='8'>";
				echo $lang["consumables"][35];
				echo "</th></tr>";
				
			}
			$i=0;
			echo "<tr><th>".$lang["common"][2]."</th><th>".$lang["consumables"][23]."</th><th>".$lang["cartridges"][24]."</th><th>".$lang["consumables"][26]."</th>";


			if ($show_old)
				echo "<th>".$lang["setup"][57]."</th>";

			echo "<th>".$lang["financial"][3]."</th>";
			
				if (!$show_old){
				echo "<th>";
				dropdownAllUsers("id_user",0);
				echo "<input type='submit' name='give' value='".$lang["consumables"][32]."'>";
				echo "</th>";
				} else {echo "<th>&nbsp;</th>";}
				echo "<th>&nbsp;</th></tr>";
			} else {

				echo "<br><div align='center'><table border='0' width='50%' cellpadding='2'>";
				echo "<tr><th>".$lang["consumables"][7]."</th></tr>";
				echo "</table></div>";
		}
	}

$where="";
$leftjoin="";
$addselect="";
if ($show_old==0){ // NEW
$where= " AND date_out IS NULL ORDER BY date_in";
} else { //OLD
$where= " AND date_out IS NOT NULL ORDER BY date_out DESC, date_in";
$leftjoin=" LEFT JOIN glpi_users ON (glpi_users.ID = glpi_consumables.id_user) ";
$addselect= ", glpi_users.realname AS REALNAME, glpi_users.name AS USERNAME ";
}

$query = "SELECT glpi_consumables.* $addselect FROM glpi_consumables $leftjoin WHERE (FK_glpi_consumables_type = '$tID') $where ";

	if ($result = $db->query($query)) {			
	$number=$db->numrows($result);
	while ($data=$db->fetch_array($result)) {
		$date_in=convDate($data["date_in"]);
		$date_out=convDate($data["date_out"]);
						
		echo "<tr  class='tab_bg_1'><td align='center'>";
		echo $data["ID"]; 
		echo "</td><td align='center'>";
		echo getConsumableStatus($data["ID"]);
		echo "</td><td align='center'>";
		echo $date_in;
		echo "</td><td align='center'>";
		echo $date_out;		
		echo "</td>";

		if ($show_old){
			echo "<td align='center'>";
			if (!empty($data["REALNAME"])) echo $data["REALNAME"];
			else echo $data["USERNAME"];
			echo "</td>";
		}

		echo "<td align='center'>";
		showDisplayInfocomLink(CONSUMABLE_ITEM_TYPE,$data["ID"],1);
		echo "</td>";

				
		if ($show_old==0){
			echo "<td align='center'>";
			echo "<input type='checkbox' name='out[".$data["ID"]."]'>";
			echo "</td>";
		}

		if ($show_old!=0){
			echo "<td align='center'>";
			echo "<a href='".$cfg_glpi["root_doc"]."/consumables/consumables-edit.php?restore=restore&amp;ID=".$data["ID"]."&amp;tID=$tID'>".$lang["consumables"][37]."</a>";
			echo "</td>";
		}						
		
		echo "<td align='center'>";
		
		echo "<a href='".$cfg_glpi["root_doc"]."/consumables/consumables-edit.php?delete=delete&amp;ID=".$data["ID"]."&amp;tID=$tID'>".$lang["buttons"][6]."</a>";
		echo "</td></tr>";
		
	}	
	}	
echo "</table></div>\n\n";
if (!$show_old)
	echo "</form>";
}

/**
* Add  a consumable in the database.
*
* Add a new consumable that type is identified by $ID
*
*@param $tID : consumable type identifier
*
*
*@return boolean true or false.
*
**/
function addConsumable($tID) {
	$c = new Consumable;
	
	$input["FK_glpi_consumables_type"]=$tID;
	$input["date_in"]=date("Y-m-d");
	
	// fill array for update
	foreach ($input as $key => $val) {
		if (empty($c->fields[$key])) {
			$c->fields[$key] = $input[$key];
		}
	}
	$newID=$c->addToDB();
	
	// Add infocoms if exists for the licence
	$ic=new Infocom();
	if ($ic->getFromDB(CONSUMABLE_TYPE,$c->fields["FK_glpi_consumables_type"])){
		unset($ic->fields["ID"]);
		$ic->fields["FK_device"]=$newID;
		$ic->fields["device_type"]=CONSUMABLE_ITEM_TYPE;
		$ic->addToDB();
	}
	return $newID;
}
/**
* delete a consumable in the database.
*
* delete a consumable that is identified by $ID
*
*@param $ID : consumable identifier
*
*
*@return nothing
*TODO error reporting
*
**/
function deleteConsumable($ID) {
	// Delete consumable
	
	$lic = new Consumable;
	$lic->deleteFromDB($ID);
	
} 

/**
* UnLink a consumable linked to a printer
*
* UnLink the consumable identified by $ID
*
*@param $ID : consumable identifier
*
*@return boolean
*
**/
function outConsumable($ID,$id_user=0) {

	global $db;
	$query = "UPDATE glpi_consumables SET date_out = '".date("Y-m-d")."', id_user='$id_user' WHERE ID='$ID'";

	if ($result = $db->query($query)) {
		return true;
	} else {
		return false;
	}
}

function restoreConsumable($ID){
	global $db;
	$query = "UPDATE glpi_consumables SET date_out = NULL WHERE ID='$ID'";

	if ($result = $db->query($query)) {
		return true;
	} else {
		return false;
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
	
	GLOBAL $db,$cfg_glpi, $lang;
	
	
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
		$out.= "<div $highlight><b>".$lang["consumables"][30].":&nbsp;$total</b>&nbsp;&nbsp;&nbsp;".$lang["consumables"][13].": $unused&nbsp;&nbsp;&nbsp;".$lang["consumables"][15].": $old</div>";			
	else $out.= $lang["consumables"][30].": $total   ".$lang["consumables"][13].": $unused   ".$lang["consumables"][15].": $old";			

	} else {
		if (!$nohtml)
			$out.= "<div class='tab_bg_1_2'><i>".$lang["consumables"][9]."</i></div>";
		else $out.= $lang["consumables"][9];
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
	global $db;
	$query = "SELECT ID FROM glpi_consumables WHERE ( FK_glpi_consumables_type = '$tID')";
	$result = $db->query($query);
	return $db->numrows($result);
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
	global $db;
	$query = "SELECT ID FROM glpi_consumables WHERE ( FK_glpi_consumables_type = '$tID'  AND date_out IS NOT NULL)";
	$result = $db->query($query);
	return $db->numrows($result);
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
	global $db;
	$query = "SELECT ID FROM glpi_consumables WHERE ( FK_glpi_consumables_type = '$tID'  AND date_out IS NULL)";
	$result = $db->query($query);
	return $db->numrows($result);
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
global $db;
$query = "SELECT ID FROM glpi_consumables WHERE ( ID= '$cID' AND date_out IS NULL)";
$result = $db->query($query);
return ($db->numrows($result)==1);
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
global $db;
$query = "SELECT ID FROM glpi_consumables WHERE ( ID= '$cID' AND date_out IS NOT NULL)";
$result = $db->query($query);
return ($db->numrows($result)==1);
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
global $lang;
if (isNewConsumable($cID)) return $lang["consumables"][20];
else if (isOldConsumable($cID)) return $lang["consumables"][22];
}


function showConsumableSummary($target){
global $db,$lang;

	$query = "SELECT COUNT(ID) AS COUNT, FK_glpi_consumables_type, id_user FROM glpi_consumables WHERE date_out IS NOT NULL GROUP BY FK_glpi_consumables_type,id_user";
	$used=array();

	if ($result=$db->query($query)){
		if ($db->numrows($result))
		while ($data=$db->fetch_array($result))
			$used[$data["id_user"]][$data["FK_glpi_consumables_type"]]=$data["COUNT"];
	}

	$query = "SELECT COUNT(ID) AS COUNT, FK_glpi_consumables_type FROM glpi_consumables WHERE date_out IS NULL GROUP BY FK_glpi_consumables_type";
	$new=array();

	if ($result=$db->query($query)){
		if ($db->numrows($result))
		while ($data=$db->fetch_array($result))
			$new[$data["FK_glpi_consumables_type"]]=$data["COUNT"];
	}

	$types=array();
	$query="SELECT * from glpi_consumables_type";
	if ($result=$db->query($query)){
		if ($db->numrows($result))
		while ($data=$db->fetch_array($result))
			$types[$data["ID"]]=$data["name"];
	}
	sort($types);
	$total=array();
	if (count($types)>0){

		// Produce headline
		echo "<div align='center'><table  class='tab_cadrehov'><tr>";

		// Type			
		echo "<th>";;
		echo $lang["setup"][57]."</th>";

		foreach ($types as $key => $type){
			echo "<th>$type</th>";
			$total[$key]=0;
		}
		echo "<th>".$lang["state"][10]."</th>";
		echo "</tr>";
	
		// new
		echo "<tr class='tab_bg_2'><td><strong>".$lang["consumables"][1]."</strong></td>";
		$tot=0;
		foreach ($types as $id_type => $type){
			if (!isset($new[$id_type])) $new[$id_type]=0;
			echo "<td align='center'>".$new[$id_type]."</td>";
			$total[$id_type]+=$new[$id_type];
			$tot+=$new[$id_type];
		}
		echo "<td align='center'>".$tot."</td>";
		echo "</tr>";

		foreach ($used as $id_user => $val){
			echo "<tr class='tab_bg_2'><td>".getUserName($id_user)."</td>";
			$tot=0;
			foreach ($types as $id_type => $type){
				if (!isset($val[$id_type])) $val[$id_type]=0;
				echo "<td align='center'>".$val[$id_type]."</td>";
				$total[$id_type]+=$val[$id_type];
				$tot+=$val[$id_type];
			}
			echo "<td align='center'>".$tot."</td>";
			echo "</tr>";
		}
		echo "<tr class='tab_bg_1'><td><strong>".$lang["state"][10]."</strong></td>";
		$tot=0;
		foreach ($types as $id_type => $type){
			$tot+=$total[$id_type];
			echo "<td align='center'>".$total[$id_type]."</td>";
		}
		echo "<td align='center'>".$tot."</td>";
		echo "</tr>";
		echo "</table></div>";

	} else {
			echo "<div align='center'><b>".$lang["consumables"][7]."</b></div>";
	}

}

?>
