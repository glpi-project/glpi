<?php
/**
* Update the configuration
*
* Update this plugin config from the form, do the query and go back to the form.
*
*@param $input array : The _POST values from the config form
*@param $id int : template or basic computers
*
*@return nothing (displays or error)
*
**/
function ocsUpdateConfig($input, $id) {
	
	global $phproot, $langOcs;
	if(!empty($input["ocs_db_user"]) && !empty($input["ocs_db_host"])) {
		$db = new DB;
		if(empty($input["ocs_db_passwd"])) $input["ocs_db_passwd"] = "";
		$query = "update glpi_ocs_config set ocs_db_user = '".$input["ocs_db_user"]."', ocs_db_host = '".$input["ocs_db_host"]."', ocs_db_passwd = '".$input["ocs_db_passwd"]."', ocs_db_name = '".$input["ocs_db_name"]."'  where ID = '".$id."'";
		if($db->query($query)) {
			glpi_header($_SERVER["HTTP_REFERER"]); 
		} else {
			glpi_header($_SERVER["HTTP_REFERER"]);	
		}
		
	} else {
		echo $langOcs["error"][0];
	}
	
}

/**
* Verify if a table exists
*
* 
*
*@param $tablename string : Name of the table we want to verify.
*
*@return bool : true if exists, false elseway.
*
**/
function TableExists($tablename) {
  
   $db = new DB;
   // Get a list of tables contained within the database.
   $result = $db->list_tables($db);
   $rcount = $db->numrows($result);

   // Check each in list for a match.
   for ($i=0;$i<$rcount;$i++) {
       if ($db->table_name($result, $i)==$tablename) return true;
   }
   return false;
}


/**
* Install the plugin tables on the GLPI database
*
*
*@return nothing.
*
**/
function ocsInstall() {
	$db = new DB;
	$query1 = "CREATE TABLE IF NOT EXISTS `glpi_ocs_link` (`ID` int(11) NOT NULL auto_increment,`glpi_id` int(11) NOT NULL default '0',`ocs_id` varchar(11) NOT NULL default '', human_checked int(2) NOT NULL default '0', PRIMARY KEY  (`ID`))";
	$db->query($query1) or die ($db->error());
	$query2 = "CREATE TABLE IF NOT EXISTS `glpi_ocs_config` (`ID` int(11) NOT NULL auto_increment,`ocs_db_user` varchar(255) NOT NULL default '',`ocs_db_passwd` varchar(255) NOT NULL default '',`ocs_db_host` varchar(255) NOT NULL default '',`ocs_db_name` varchar(255) NOT NULL default '',PRIMARY KEY  (`ID`))";
	$db->query($query2) or die($db->error());
	$query3 = "INSERT INTO `glpi_ocs_config` ( `ID` , `ocs_db_user` , `ocs_db_passwd` , `ocs_db_host` , `ocs_db_name` )VALUES ('1', '', '', '', '')";
	$db->query($query3) or die($db->error());
	
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
	$db = new DB;
	$query2 = "select * from ".$dpdTable."";
	$result2 = $db->query($query2);
	if($db->numrows($result2) == 0) {
		$query3 = "insert into ".$dpdTable." (ID,".$dpdRow.") values ('','".$value."')";
		$db->query($query3) or die("echec de l'importation".$db->error());
		$return = $db->insert_id();
	} else {
		while($line2 = $db->fetch_array($result2)) {
			$tabDpd[$line2["ID"]] = $line2[$dpdRow];
		} 
		$inDb = false;
		foreach($tabDpd as $key => $val) {
		//Pour plus tard : faire des comparaisons en regexp entre l'entrée ocs et le plugin
		//Pour éviter les doublons type : Pentium IV avec intel Pentium IV
			if(strcmp($value,$val) == 0) {
				$inDb = true;
				return($key);
			}
		}
		if(!$inDb) {
			#echo strcmp($value,$line2[$dpdRow]);die();#$value.":".$line2[$dpdRow]; die();
			$query3 = "insert into ".$dpdTable." (ID,".$dpdRow.") values ('','".$value."')";
			$db->query($query3) or die("echec de l'importation".$db->error());
			$return = $db->insert_id();

		}
	}
}


/**
* Import général config of a new computer
*
* This function create a new computer in GLPI with some general datas.
*
*@param $name : name of the computer.
*@param $dpdos : dropdown id for an os.
*
*@return integer : inserted computer id.
*
**/
function ocsImportNewComputer($name,$dpdOs) {
	global $langOcs;
	//best way to do.
	$comp = new Computer;
	$comp->fields["name"] = $name;
	$comp->fields["os"] = $dpdOs;
	$comp->fields["date_mod"] = date("Y-m-d H:i:s");
	$comp->fields["comments"] = $langOcs["import"][3];
	return($comp->addToDB());
	/*//simpliest way to do.
	$db = new DB;
	$query = "insert into glpi_computers (name,os) VALUES ('".$name."','".$dpdOs."')";
	$db->query($query) or die("unable to import computer".$db->error());
	return $db->insert_id();*/
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
	$db = new DB;
	$query = "insert into glpi_ocs_link (glpi_id,ocs_id,human_checked) VALUES ('".$glpi_computer_id."','".$ocs_item_id."','0')";
	$db->query($query) or die("Lien impossible : ".$db->error());
	return($db->insert_id());
}

/**
* Add a new device.
*
* Add a new device if doesn't exist.
*
*@param $device_type integer : device type identifier.
*@param $dev_array array : device fields.
*
*@return integer : device id.
*
**/
function ocsAddDevice($device_type,$dev_array) {
	$db = new DB;
	$query = "select * from ".getDeviceTable($device_type)."";
	$result = $db->query($query);
	if($db->numrows($result) == 0) {
		$dev = new Device($device_type);
		foreach($dev_array as $key => $val) {
			$dev->fields[$key] = $val;
		}
		return($dev->addToDB());
	} else {
		$tabDevice = array();
		while($line = $db->fetch_array($result)) {
			$tabDevice[$line["ID"]] = $line["designation"];
		}
		foreach($tabDevice as $key => $val) {
		//Pour plus tard : faire des comparaisons en regexp entre l'entrée ocs et le plugin
		//Pour éviter les doublons type : Pentium IV avec intel Pentium IV
			if(strcmp($dev_array["designation"],$val) === 0) {
				return($key);
			} 
		}
		$dev = new device($device_type);
		foreach($dev_array as $key => $val) {
			$dev->fields[$key] = $val;
		}
		return($dev->addToDB());
	}
}


/**
* Delete old devices settings
*
* Delete Old device settings.
*
*@param $device_type integer : device type identifier.
*@param $glpi_computer_id integer : glpi computer id.
*@param $device_id integer : device identifier.
*
*@return nothing.
*
**/
function ocsResetDevices($glpi_computer_id, $device_type, $device_id) {
	$db = new DB;
	$query = "delete from glpi_computer_device where device_type = '".$device_type."', FK_device = '".$device_id."', FK_computers = '".$glpi_computer_id."'";
	$db->query($query) or die("unable to delete old devices settings ".$db->error());
}




?>