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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

function ocsShowNewComputer($check,$start,$tolinked=0){
global $lang,$HTMLRel,$cfg_features;

$dbocs = new DBocs();
$query_ocs = "select * from hardware order by lastdate";
$result_ocs = $dbocs->query($query_ocs) or die($dbocs->error());

// Existing OCS - GLPI link
$dbglpi = new DB();
$query_glpi = "select * from glpi_ocs_link";
$result_glpi = $dbglpi->query($query_glpi) or die($dbglpi->error());

// Computers existing in GLPI
$query_glpi_comp = "select ID,name from glpi_computers where deleted = 'N' AND is_template='0'";
$result_glpi_comp = $dbglpi->query($query_glpi_comp) or die($dbglpi->error());

if ($dbocs->numrows($result_ocs)>0){
	
	// Get all hardware from OCS DB
	$hardware=array();
	while($data=$dbocs->fetch_array($result_ocs)){
		$data=addslashes_deep($data);
		$hardware[$data["DEVICEID"]]["date"]=$data["LASTDATE"];
		$hardware[$data["DEVICEID"]]["name"]=$data["NAME"];
	}
	// Get all links between glpi and OCS
	$already_linked=array();
	if ($dbglpi->numrows($result_glpi)>0){
		while($data=$dbocs->fetch_array($result_glpi)){
		$already_linked[$data["ocs_id"]]=$data["last_update"];
		}
	}

	// Get all existing computers name in GLPI
	$computer_names=array();
	if ($dbglpi->numrows($result_glpi_comp)>0){
		while($data=$dbocs->fetch_array($result_glpi_comp)){
		$computer_names[$data["name"]]=$data["ID"];
		}
	}
	
	// Clean $hardware from already linked element
	if (count($already_linked)>0){
		foreach ($already_linked as $ID => $date){
			if (isset($hardware[$ID])&&isset($already_linked[$ID]))
			unset($hardware[$ID]);
		}
	}
	
	echo "<div align='center'>";
	if (($numrows=count($hardware))>0){
	
		$parameters="check=$check";
   	 	printPager($start,$numrows,$_SERVER["PHP_SELF"],$parameters);

		// delete end 
		array_splice($hardware,$start+$cfg_features["list_limit"]);
		// delete begin
		if ($start>0)
		array_splice($hardware,0,$start);
		
		echo "<form method='post' action='".$_SERVER["PHP_SELF"]."'>";
		if ($tolinked==0)
			echo "<a href='".$_SERVER["PHP_SELF"]."?check=all&amp;start=$start'>".$lang["buttons"][18]."</a>&nbsp;/&nbsp;<a href='".$_SERVER["PHP_SELF"]."?check=none&amp;start=$start'>".$lang["buttons"][19]."</a>";

		
		echo "<table class='tab_cadre'>";
		echo "<tr><th>".$lang["ocsng"][5]."</th><th>".$lang["ocsng"][6]."</th><th>&nbsp;</th></tr>";
		
		echo "<tr class='tab_bg_1'><td colspan='3' align='center'>";
		echo "<input type='submit' name='import_ok' value='".$lang["buttons"][37]."'>";
		echo "</td></tr>";

		
		foreach ($hardware as $ID => $tab){
			echo "<tr class='tab_bg_2'><td>".$tab["name"]."</td><td>".$tab["date"]."</td><td>";
			
			if ($tolinked==0)
			echo "<input type='checkbox' name='toimport[$ID]' ".($check=="all"?"checked":"").">";
			else {
				if (isset($computer_names[$tab["name"]]))
					dropdownValue("glpi_computers","tolink[$ID]",$computer_names[$tab["name"]]);
				else
					dropdown("glpi_computers","tolink[$ID]");
			}
			echo "</td></tr>";
		
		}
		echo "<tr class='tab_bg_1'><td colspan='3' align='center'>";
		echo "<input type='submit' name='import_ok' value='".$lang["buttons"][37]."'>";
		echo "</td></tr>";
		echo "</table>";
		echo "</form>";
   	 	
		printPager($start,$numrows,$_SERVER["PHP_SELF"],$parameters);

	} else echo "<strong>".$lang["ocsng"][9]."</strong>";

	echo "</div>";

} else echo "<div align='center'><strong>".$lang["ocsng"][9]."</strong></div>";
}

/**
* Make the item link between glpi and ocs.
*
* This make the database link between ocs and glpi databases
*
*@param $ocs_item_id integer : ocs item unique id.
*@param $glpi_computer_id integer : glpi computer id
*
*@return integer : link id.
*
**/
function ocs_link($ocs_item_id, $glpi_computer_id) {
	$db = new DB();
	$query = "insert into glpi_ocs_link (glpi_id,ocs_id,last_update) VALUES ('".$glpi_computer_id."','".$ocs_item_id."',NOW())";
	
	$result=$db->query($query);
	if ($result)
		return ($db->insert_id());
	else return false;
}


function ocsImportComputer($DEVICEID){
	$dbocs = new DBocs();

	// Set OCS checksum to max value
	$query = "UPDATE hardware SET CHECKSUM='".MAX_OCS_CHECKSUM."' WHERE DEVICEID='$DEVICEID'";
	$result = $dbocs->query($query) or die($dbocs->error().$query);


	$query = "SELECT * FROM hardware WHERE DEVICEID='$DEVICEID'";
	$result = $dbocs->query($query) or die($dbocs->error().$query);
	if ($dbocs->numrows($result)==1){
		$line=$dbocs->fetch_array($result);
		$dbocs->close();
		$glpi_id=ocsImportNewComputer($line["NAME"]);
		if ($idlink = ocs_link($line['DEVICEID'], $glpi_id)){
			ocsUpdateComputer($idlink,0);
		}
	}
}


function ocsUpdateComputer($ID,$dohistory){

    $dbglpi = new DB();

     $cfg_ocs=getOcsConf(1);

    $query="SELECT * FROM glpi_ocs_link WHERE ID='$ID'";

    $result=$dbglpi->query($query) or die($dbglpi->error().$query);;
    if ($dbglpi->numrows($result)==1){
        $line=$dbglpi->fetch_assoc($result);
	$dbocs = new DBocs();
	$query_ocs = "SELECT CHECKSUM FROM hardware WHERE DEVICEID='".$line['ocs_id']."'";
	$result_ocs = $dbocs->query($query_ocs) or die($dbocs->error().$query_ocs);
	if ($dbocs->numrows($result_ocs)==1){
		$ocs_checksum=$dbocs->result($result_ocs,0,0);
	

		$mixed_checksum=intval($ocs_checksum) &  intval($cfg_ocs["checksum"]);
//		echo "OCS CS=".decbin($ocs_checksum)." - $ocs_checksum<br>";
//		echo "GLPI CS=".decbin($cfg_ocs["checksum"])." - ".$cfg_ocs["checksum"]."<br>";
//		echo "MIXED CS=".decbin($mixed_checksum)." - $mixed_checksum <br>";

		// Is an update to do ?
		if ($mixed_checksum){

			// Get updates on computers :
			$computer_updates=importArrayFromDB($line["computer_update"]);
			
			if ($mixed_checksum&pow(2,HARDWARE_FL))
				ocsUpdateHardware($line['glpi_id'],$line['ocs_id'],$cfg_ocs,$computer_updates,$dohistory);
			if ($mixed_checksum&pow(2,BIOS_FL))
				ocsUpdateBios($line['glpi_id'],$line['ocs_id'],$cfg_ocs,$computer_updates,$dohistory);


		// Update OCS Cheksum
		$dbocs = new DBocs();
		$query_ocs="UPDATE hardware SET CHECKSUM= (CHECKSUM - $mixed_checksum) WHERE DEVICEID='".$line['ocs_id']."'";
		$dbocs->query($query_ocs) or die($dbocs->error().$query_ocs);
		}
	}
/*
	if(getOcsConfVar("import_device_processor") == 1) 
		ocsResetDevices($line['glpi_id'],PROCESSOR_DEVICE);
	if(getOcsConfVar("import_device_iface") == 1) 
		ocsResetDevices($line['glpi_id'],NETWORK_DEVICE);
	if(getOcsConfVar("import_device_memory") == 1) 
		ocsResetDevices($line['glpi_id'],RAM_DEVICE);
	if(getOcsConfVar("import_device_hdd") == 1) 
		ocsResetDevices($line['glpi_id'],HDD_DEVICE);
	if(getOcsConfVar("import_device_sound") == 1) 
		ocsResetDevices($line['glpi_id'],SND_DEVICE);
	if(getOcsConfVar("import_device_gfxcard") == 1) 
		ocsResetDevices($line['glpi_id'],GFX_DEVICE);
	if(getOcsConfVar("import_device_drives") == 1) 
		ocsResetDevices($line['glpi_id'],DRIVE_DEVICE);
	if(getOcsConfVar("import_device_modems") == 1 || getOcsConfVar("import_device_ports") == 1) 
		ocsResetDevices($line['glpi_id'],PCI_DEVICE);
	ocsResetLicenses($line['glpi_id']);
	ocsResetPeriphs($line['glpi_id']);
	ocsResetMonitors($line['glpi_id']);
	ocsResetPrinters($line['glpi_id']);

	ocsUpdateGeneral($line['glpi_id'],$line['ocs_id']);
	ocsAddComputerDevices($line['glpi_id'],$line['ocs_id']);
	ocsImportPeripherals($line['glpi_id'],$line['ocs_id']);
	ocsImportSoftware($line['glpi_id'],$line['ocs_id']);

        $query="UPDATE glpi_ocs_link SET last_update=NOW() WHERE ID='$ID'";
        $dbglpi->query($query);
*/
    }
}

/**
* Import general config of a new computer
*
* This function create a new computer in GLPI with some general datas.
*
*@param $name : name of the computer.
*@param $ssn : serial number of the computer.
*@param $model : id for a computer model.
*@param $manuf : id for a enterprise.
*
*@return integer : inserted computer id.
*
**/
function ocsImportNewComputer($name) {
	$comp = new Computer;
	$comp->fields["name"] = $name;
	$comp->fields["ocs_import"] = 1;
	return($comp->addToDB());
}

/**
* Get OCSNG mode configuration
*
* Get all config of the OCSNG mode
*
*
*@return Value of $confVar fields or false if unfound.
*
**/
function getOcsConf($id) {
	$db = new DB();
	$query = "SELECT * FROM glpi_ocs_config WHERE ID='$id'";
	$result = $db->query($query)  or die($db->error().$query);
	if($result) return $db->fetch_assoc($result);
	else return 0;
}


/**
* Update the computer hardware configuration
*
* Update the computer hardware configuration
*
*@param $ocs_id integer : glpi computer id
*@param $glpi_id integer : ocs computer id.
*
*@return nothing.
*
**/
function ocsUpdateHardware($glpi_id,$ocs_id,$cfg_ocs,$computer_updates,$dohistory=1) {
 	global $lang;
	$dbocs = new DBocs();
	$query = "select * from hardware WHERE DEVICEID='".$ocs_id."'";
//	echo $query;
	$result = $dbocs->query($query) or die($dbocs->error());
	if ($dbocs->numrows($result)==1) {
		$line=$dbocs->fetch_assoc($result);
		$line=addslashes_deep($line);
		$compudate=array();
		
		if($cfg_ocs["import_general_os"]&&!in_array("os",$computer_updates)) {
			$compupdate["os"] = ocsImportDropdown('glpi_dropdown_os','name',$line["OSNAME"]." ".$dbocs->result($result,0,"OSVERSION"));
		}
		
		if($cfg_ocs["import_general_domain"]&&!in_array("domain",$computer_updates)) {
			$compupdate["domain"] = ocsImportDropdown('glpi_dropdown_domain','name',$line["WORKGROUP"]);
		}
		
		if($cfg_ocs["import_general_contact"]&&!in_array("contact",$computer_updates)) {
			$compupdate["contact"] = $line["USERID"];
		}
			
		if($cfg_ocs["import_general_comments"]&&!in_array("comments",$computer_updates)) {
			$compupdate["comments"] = $line["OSCOMMENTS"]."\r\n"."Swap: ".$line["SWAP"]."\r\n".addslashes($lang["ocsng"][7]);
		}

		if (count($compupdate)){
			$compupdate["ID"] = $glpi_id;
			updateComputer($compupdate,$dohistory);
		}
		
	}
}


/**
* Update the computer bios configuration
*
* Update the computer bios configuration
*
*@param $ocs_id integer : glpi computer id
*@param $glpi_id integer : ocs computer id.
*
*@return nothing.
*
**/
function ocsUpdateBios($glpi_id,$ocs_id,$cfg_ocs,$computer_updates,$dohistory=1) {
	$dbocs = new DBocs();
	$query = "select * from bios WHERE DEVICEID='".$ocs_id."'";
//	echo $query;
	$result = $dbocs->query($query) or die($dbocs->error().$query);
	if ($dbocs->numrows($result)==1) {
		$line=$dbocs->fetch_assoc($result);
		$line=addslashes_deep($line);
		$compudate=array();

		if($cfg_ocs["import_general_serial"]&&!in_array("serial",$computer_updates)) {
			$compupdate["serial"] = $line["SSN"];
		}
		
		if($cfg_ocs["import_general_model"]&&!in_array("model",$computer_updates)) {
			$compupdate["model"] = ocsImportDropdown('glpi_dropdown_model','name',$line["SMODEL"]);
		}	
		
		if($cfg_ocs["import_general_enterprise"]&&!in_array("FK_glpi_enterprise",$computer_updates)) {
			$compupdate["FK_glpi_enterprise"] = ocsImportEnterprise($line["SMANUFACTURER"]);
		}
		
		if($cfg_ocs["import_general_type"]&&!empty($line["TYPE"])&&!in_array("type",$computer_updates)) {
			$compupdate["type"] = ocsImportDropdown('glpi_type_computers','name',$line["TYPE"]);
		}
		
		if (count($compupdate)){
			$compupdate["ID"] = $glpi_id;
			updateComputer($compupdate,$dohistory);
		}
		
	}
}


/**
* Import a dropdown from OCS table.
*
* This import a new dropdown if it doesn't exist.
*
*@param $dpdTable string : Name of the glpi dropdown table.
*@param $dpdRow string : Name of the glinclude ($phproot . "/glpi/includes_devices.php");pi dropdown row.
*@param $value string : Value of the new dropdown.
*
*@return integer : dropdown id.
*
**/

function ocsImportDropdown($dpdTable,$dpdRow,$value) {
	$db = new DB();
	$query2 = "select * from ".$dpdTable." where $dpdRow='".$value."'";
	$result2 = $db->query($query2);
	if($db->numrows($result2) == 0) {
		$query3 = "insert into ".$dpdTable." (ID,".$dpdRow.") values ('','".$value."')";
		$db->query($query3) or die("echec de l'importation".$db->error());
		return $db->insert_id();
	} else {
	$line2 = $db->fetch_array($result2);
	return $line2["ID"];
	}
	
}


/**
* Import g��al config of a new enterprise
*
* This function create a new enterprise in GLPI with some general datas.
*
*@param $name : name of the enterprise.
*
*@return integer : inserted enterprise id.
*
**/
function ocsImportEnterprise($name) {
    $db = new DB();
    $query = "SELECT ID FROM glpi_enterprises WHERE name = '".$name."'";
    $result = $db->query($query) or die("Verification existence entreprise :".$name." - ".$db->error());
    if ($db->numrows($result)>0){
        $enterprise_id  = $db->result($result,0,"ID");
    } else {
        $entpr = new Enterprise;
        $entpr->fields["name"] = $name;
        $enterprise_id = $entpr->addToDB();
    }
    return($enterprise_id);
}

function ocsCleanLinks(){
	$db = new DB;

	$query="SELECT glpi_ocs_link.ID AS ID FROM glpi_ocs_link LEFT JOIN glpi_computers ON glpi_computers.ID=glpi_ocs_link.glpi_id WHERE glpi_computers.ID IS NULL";
	
	$result=$db->query($query);
	if ($db->numrows($result)>0){
		while ($data=$db->fetch_array($result)){
			$query2="DELETE FROM glpi_ocs_link WHERE ID='".$data['ID']."'";
			$db->query($query2);
		}
	}
}


function ocsShowUpdateComputer($check,$start){
global $lang,$HTMLRel,$cfg_features;

$cfg_ocs=getOcsConf(1);

$dbocs = new DBocs();
$query_ocs = "select * from hardware WHERE (CHECKSUM & ".$cfg_ocs["checksum"].") > 0 order by lastdate";
$result_ocs = $dbocs->query($query_ocs) or die($dbocs->error());

$dbglpi = new DB();
$query_glpi = "select glpi_ocs_link.last_update as last_update,  glpi_ocs_link.glpi_id as glpi_id, glpi_ocs_link.ocs_id as ocs_id, glpi_computers.name as name, glpi_ocs_link.auto_update as auto_update, glpi_ocs_link.ID as ID";
$query_glpi.= " from glpi_ocs_link LEFT JOIN glpi_computers ON (glpi_computers.ID = glpi_ocs_link.glpi_id) ";
$query_glpi.= " ORDER by glpi_ocs_link.last_update, glpi_computers.name";

$result_glpi = $dbglpi->query($query_glpi) or die($dbglpi->error());
if ($dbocs->numrows($result_ocs)>0){
	
	// Get all hardware from OCS DB
	$hardware=array();
	while($data=$dbocs->fetch_array($result_ocs)){
	$hardware[$data["DEVICEID"]]["date"]=$data["LASTDATE"];
	$hardware[$data["DEVICEID"]]["name"]=addslashes($data["NAME"]);
	}

	// Get all links between glpi and OCS
	$already_linked=array();
	if ($dbglpi->numrows($result_glpi)>0){
		while($data=$dbocs->fetch_assoc($result_glpi)){
			$data=addslashes_deep($data);
			if (isset($hardware[$data["ocs_id"]])){ 
				$already_linked[$data["ocs_id"]]["date"]=$data["last_update"];
				$already_linked[$data["ocs_id"]]["name"]=$data["name"];
				$already_linked[$data["ocs_id"]]["ID"]=$data["ID"];
				$already_linked[$data["ocs_id"]]["glpi_id"]=$data["glpi_id"];
			}
		}
	}
	echo "<div align='center'>";
	echo "<h2>".$lang["ocsng"][10]."</h2>";
	
	if (($numrows=count($already_linked))>0){

		$parameters="check=$check";
   		printPager($start,$numrows,$_SERVER["PHP_SELF"],$parameters);

		// delete end 
		array_splice($already_linked,$start+$cfg_features["list_limit"]);
		// delete begin
		if ($start>0)
		array_splice($already_linked,0,$start);

		echo "<form method='post' action='".$_SERVER["PHP_SELF"]."'>";
		
		echo "<a href='".$_SERVER["PHP_SELF"]."?check=all'>".$lang["buttons"][18]."</a>&nbsp;/&nbsp;<a href='".$_SERVER["PHP_SELF"]."?check=none'>".$lang["buttons"][19]."</a>";
		echo "<table class='tab_cadre'>";
		echo "<tr><th>".$lang["ocsng"][11]."</th><th>".$lang["ocsng"][13]."</th><th>".$lang["ocsng"][14]."</th><th>&nbsp;</th></tr>";
		
		echo "<tr class='tab_bg_1'><td colspan='4' align='center'>";
		echo "<input type='submit' name='update_ok' value='".$lang["buttons"][7]."'>";
		echo "</td></tr>";

		foreach ($already_linked as $ID => $tab){

			echo "<tr align='center' class='tab_bg_2'><td><a href='".$HTMLRel."computers/computers-info-form.php?ID=".$tab["glpi_id"]."'>".$tab["name"]."</a></td><td>".$tab["date"]."</td><td>".$hardware[$ID]["date"]."</td><td>";
			
			echo "<input type='checkbox' name='toupdate[".$tab["ID"]."]' ".($check=="all"?"checked":"").">";
			echo "</td></tr>";
		}
		echo "<tr class='tab_bg_1'><td colspan='4' align='center'>";
		echo "<input type='submit' name='update_ok' value='".$lang["buttons"][7]."'>";
		echo "</td></tr>";
		echo "</table>";
		echo "</form>";
   		printPager($start,$numrows,$_SERVER["PHP_SELF"],$parameters);

	} else echo "<br><strong>".$lang["ocsng"][11]."</strong>";

	echo "</div>";

} else echo "<div align='center'><strong>".$lang["ocsng"][12]."</strong></div>";
}


function mergeOcsArray($glpi_id,$tomerge,$field){
	$db=new DB();
	$query="SELECT $field FROM glpi_ocs_link WHERE glpi_id='$glpi_id'";
	if ($result=$db->query($query)){
		$tab=importArrayFromDB($db->result($result,0,0));
		$newtab=array_merge($tomerge,$tab);
		$query="UPDATE glpi_ocs_link SET $field='".exportArrayToDB($newtab)."' WHERE glpi_id='$glpi_id'";
		$db->query($query);
	}

}

function deleteInOcsArray($glpi_id,$todel,$field){
	$db=new DB();
	$query="SELECT $field FROM glpi_ocs_link WHERE glpi_id='$glpi_id'";
	if ($result=$db->query($query)){
		$tab=importArrayFromDB($db->result($result,0,0));
		unset($tab[$todel]);
		$query="UPDATE glpi_ocs_link SET $field='".exportArrayToDB($tab)."' WHERE glpi_id='$glpi_id'";
		$db->query($query);
	}

}


function ocsEditLock($target,$ID){
	global $lang,$SEARCH_OPTION;

	$db=new DB();
	$query="SELECT * FROM glpi_ocs_link WHERE glpi_id='$ID'";
	$result=$db->query($query);
	if ($db->numrows($result)==1){
		$data=$db->fetch_assoc($result);
		echo "<div align='center'>";
		// Print lock fields for OCSNG
		$lockable_fields=array("type","FK_glpi_enterprise","model","serial","comments","contact","domain","os");
		$locked=array_intersect(importArrayFromDB($data["computer_update"]),$lockable_fields);
		if (count($locked)){
			echo "<form method='post' action=\"$target\">";
			echo "<input type='hidden' name='ID' value='$ID'>";
			echo "<table class='tab_cadre'>";
			echo "<tr><th colspan='2'>".$lang["ocsng"][16]."</th></tr>";
			foreach ($locked as $key => $val){
				foreach ($SEARCH_OPTION[COMPUTER_TYPE] as $key2 => $val2)
				if ($val2["linkfield"]==$val)
				echo "<tr class='tab_bg_1'><td>".$val2["name"]."</td><td><input type='checkbox' name='lockfield[".$key."]'></td></tr>";
			}
			echo "<tr class='tab_bg_2'><td align='center' colspan='2'><input type='submit' name='unlock_field' value='".$lang["buttons"][38]."'></td></tr>";
			echo "</table>";
			echo "</form>";
		} else echo "<strong>".$lang["ocsng"][15]."</strong>";
		echo "</div>";
	}

}

function unlockOcsFields($ID,$fields){


}
?>
