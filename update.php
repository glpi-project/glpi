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

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------


include ("_relpos.php");
include ($phproot . "/glpi/common/classes.php");
include ($phproot . "/glpi/common/functions.php");
include ($phproot . "/glpi/config/config_db.php");

// ITEMS TYPE
define("GENERAL_TYPE","0");
define("COMPUTER_TYPE","1");
define("NETWORKING_TYPE","2");
define("PRINTER_TYPE","3");
define("MONITOR_TYPE","4");
define("PERIPHERAL_TYPE","5");
define("SOFTWARE_TYPE","6");
define("CONTACT_TYPE","7");
define("ENTERPRISE_TYPE","8");
define("INFOCOM_TYPE","9");
define("CONTRACT_TYPE","10");
define("CARTRIDGE_TYPE","11");

// DEVICE TYPE
define("MOBOARD_DEVICE","1");
define("PROCESSOR_DEVICE","2");
define("RAM_DEVICE","3");
define("HDD_DEVICE","4");
define("NETWORK_DEVICE","5");
define("DRIVE_DEVICE","6"); 
define("CONTROL_DEVICE","7");
define("GFX_DEVICE","8");
define("SND_DEVICE","9");
define("PCI_DEVICE","10");
define("CASE_DEVICE","11");
define("POWER_DEVICE","12");

//Load language
if(!function_exists('loadLang')) {
	function loadLang($language) {
		
			unset($lang);
			global $lang;
			include ("_relpos.php");
			$file = $phproot ."/glpi/dicts/".$language.".php";
			include($file);
	}
}

function optimize_tables (){
	
$db = new DB;
$result=$db->list_tables();
	while ($line = $db->fetch_array($result))
   	{
   		if (ereg("glpi_",$line[0])){
			$table = $line[0];
   		$query = "OPTIMIZE TABLE ".$table.";";
//   		echo $query;
   		$db->query($query);
		}
  	 }
}

/* ----------------------------------------------------------------- */
/**
* Get data from old dropdowns to new devices
*
* This function assure to keep clean data and integrity, during the change from 
* computers-dropdown to computers devices. Then delete the unused old elements.
*
* @param integer devtype the devtype number 
* @param string devname the device table name (end of the name (glpi_device_thisparam))
* @param string dpdname the dropdown table name (end of the name (glpi_dropdown_thisparam))
* @param string compdpdname the name of the dropdown foreign key on glpi_computers (eg : hdtype, processor) 
* @param string specif='' the name of the dropdown value entry on glpi_computer (eg : hdspace, processor_speed) optionnal argument.
* @returns nothing if everything is good, else display mysql query and error.
*/
function compDpd2Device($devtype,$devname,$dpdname,$compDpdName,$specif='') {
	global $lang;
	$query = "select * from glpi_dropdown_".$dpdname."";
	$db = new DB;
	$result = $db->query($query);
	while($lndropd = $db->fetch_array($result)) {
		$query2 = "insert into glpi_device_".$devname." (designation) values ('".$lndropd["name"]."')";
		$db->query($query2) or die("unable to transfer ".$dpdname." to ".$devname."  ".$lang["update"][90].$db->error());
		$devid = mysql_insert_id();
		$query3 = "select * from glpi_computers where ".$compDpdName." = '".$lndropd["ID"]."'";
		$result3 = $db->query($query3);
		while($lncomp = $db->fetch_array($result3)) {
			$query4 = "insert into glpi_computer_device (device_type, FK_device, FK_computers) values ('$devtype','".$devid."','".$lncomp["ID"]."')";
			if(!empty($specif)) {
				$queryspecif = "SELECT ".$specif." FROM glpi_computers WHERE ID = '".$lncomp["ID"]."'";
				if($resultspecif = $db->query($queryspecif)) {
					$query4 = "insert into glpi_computer_device (specificity, device_type, FK_device, FK_computers) values ('".$db->result($resultspecif,0,$specif)."','$devtype','".$devid."','".$lncomp["ID"]."')";
				}
				
			}
			$db->query($query4) or die("unable to migrate from ".$dpdname." to ".$devname." for item computer:".$lncomp["ID"]."  ".$lang["update"][90].$db->error());
		}
	}
	//Delete unused elements (dropdown on the computer table, dropdown table and specif)
	$query = "ALTER TABLE glpi_computers drop `".$compDpdName."`";
	$db->query($query) or die("Error : ".$query." ".mysql_error());
	$query = "DROP TABLE `glpi_dropdown_".$dpdname."`";
	$db->query($query) or die("Error : ".$query." ".mysql_error());
	if(!empty($specif)) {
		$query = "ALTER TABLE glpi_computers drop `".$specif."`";
		$db->query($query) or die("Error : ".$query." ".mysql_error());
	}
}

/*---------------------------------------------------------------------*/
/**
* Test if there is an user with superadmin rights
*
*
* @returns boolean true if its ok, elsewhere false.
*/
function superAdminExists() {
	$db = new DB;
	$query = "select type, password from glpi_users";
	$result = $db->query($query);
	$var1 = false;
	while($line = $db->fetch_array($result)) {
		if($line["type"] == "super-admin" && !empty($line["password"])) $var1 = true;
	}
	return $var1;
}

/*---------------------------------------------------------------------*/
/**
* Put the correct root_doc value on glpi_config table.
*
*
* @returns nothing if everything is right, display query and mysql error if bad.
*/
function updaterootdoc() {
	
	// hack pour IIS qui ne connait pas $_SERVER['REQUEST_URI']  grrrr
	if ( !isset($_SERVER['REQUEST_URI']) ) {
	    $_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'];
	}
	
	$root_doc = ereg_replace("/update.php","",$_SERVER['REQUEST_URI']);
	$db = new DB;
	$query = "update glpi_config set root_doc = '".$root_doc."' where ID = '1'";
	$db->query($query) or die(" root_doc ".$lang["update"][90].$db->error());
}

/*---------------------------------------------------------------------*/
/**
* Display the form of content update (addslashes compatibility (V0.4))
*
*
* @returns nothing (displays)
*/
function showContentUpdateForm() {
	
	global $lang;
	echo "<div align='center'>";
	echo "<h3>".$lang["update"][94]."</h3>";
	echo "<p>".$lang["install"][63]."</p>";
	echo "<p>".$lang["update"][107]."</p></div>";
	echo "<p class='submit'> <a href=\"update_content.php\"><span class='button'>".$lang["install"][25]."</span></a>";
}


///// FONCTION POUR UPDATE LOCATION

function validate_new_location(){
	$db=new DB;
	$query=" DROP TABLE `glpi_dropdown_locations`";	
	//echo $query;
	$result=$db->query($query);
	$query=" ALTER TABLE `glpi_dropdown_locations_new` RENAME `glpi_dropdown_locations`";	
	//echo $query;
	$result=$db->query($query);
}

function display_new_locations(){
	
	$db=new DB;

	$MAX_LEVEL=10;

	$SELECT_ALL="";
	$FROM_ALL="";
	$ORDER_ALL="";
	$WHERE_ALL="";
	for ($i=1;$i<=$MAX_LEVEL;$i++){
		$SELECT_ALL.=" , location$i.name AS NAME$i ";
		$FROM_ALL.=" LEFT JOIN glpi_dropdown_locations_new AS location$i ON location".($i-1).".ID = location$i.parentID ";
		//$WHERE_ALL.=" AND location$i.level='$i' ";
		$ORDER_ALL.=" , NAME$i";

	}

	$query="select location0.name AS NAME0 $SELECT_ALL FROM glpi_dropdown_locations_new AS location0 $FROM_ALL  WHERE location0.parentID='0' $WHERE_ALL  ORDER BY NAME0 $ORDER_ALL";
	//echo $query;
	//echo "<hr>";
	$result=$db->query($query);
	$data_old=array();
	echo "<table class='tab_cadre'><tr>";
	for ($i=0;$i<=$MAX_LEVEL;$i++){
		echo "<th>$i</th><th>&nbsp;</th>";
	}
	echo "</tr>";

	while ($data =  $db->fetch_array($result)){
	
		echo "<tr class=tab_bg_1>";
		for ($i=0;$i<=$MAX_LEVEL;$i++){
			if (!isset($data_old["NAME$i"])||$data_old["NAME$i"]!=$data["NAME$i"]){
				$name=$data["NAME$i"];
				if (isset($data["NAME".($i+1)])&&!empty($data["NAME".($i+1)]))
				$arrow="--->";
			else $arrow="";
			} else {
				$name="";
				$arrow="";
			}
	
			echo "<td>".$name."</td>";
			echo "<td>$arrow</td>";
		}
	
		echo "</tr>";
	$data_old=$data;
	}
	
	echo "</table>";
}

function display_old_locations(){
	$db=new DB;
	$query="SELECT * from glpi_dropdown_locations;";
	$result=$db->query($query);

	while ($data =  $db->fetch_array($result))
	echo "<b>".$data['name']."</b> - ";
}

function location_create_new($split_char,$add_first){

	$db=new DB;
	$query="SELECT MAX(ID) AS MAX from glpi_dropdown_locations;";
	//echo $query."<br>";
	$result=$db->query($query);
	$new_ID=$db->result($result,0,"MAX");
	$new_ID++;

	$query="SELECT * from glpi_dropdown_locations;";
	$result=$db->query($query);

	$query_clear_new="TRUNCATE TABLE `glpi_dropdown_locations_new`";
	//echo $query_clear_new."<br>";
	$result_clear_new=$db->query($query_clear_new); 

	if (!empty($add_first)){
		$root_ID=$new_ID;
		$new_ID++;
		$query_insert="INSERT INTO glpi_dropdown_locations_new VALUES ('$root_ID','$add_first',0)";
		//echo $query_insert."<br>";
		$result_insert=$db->query($query_insert);
	} else {
		$root_ID=0;
	}

	while ($data =  $db->fetch_array($result)){
		if (!empty($split_char))
			$splitter=split($split_char,$data['name']);
		else $splitter=array($data['name']);
	
		$up_ID=$root_ID;
	
		for ($i=0;$i<count($splitter)-1;$i++){
			// Entrée existe deja ??
			$query_search="select ID from glpi_dropdown_locations_new WHERE name='".$splitter[$i]."'  AND parentID='".$up_ID."'";
			//	echo $query_search."<br>";
			$result_search=$db->query($query_search);
			if ($db->numrows($result_search)==1){	// Found
				$up_ID=$db->result($result_search,0,"ID");
			} else { // Not FOUND -> INSERT
				$query_insert="INSERT INTO glpi_dropdown_locations_new VALUES ('$new_ID','".$splitter[$i]."','$up_ID')";
				$up_ID=$new_ID++;
				//	echo $query_insert."<br>";
				$result_insert=$db->query($query_insert);
			}
		}

		// Ajout du dernier
		$query_insert="INSERT INTO glpi_dropdown_locations_new VALUES ('".$data["ID"]."','".$splitter[count($splitter)-1]."','$up_ID')";
		//	echo $query_insert."<br>";

		$result_insert=$db->query($query_insert);

	}

	$query_auto_inc= "ALTER TABLE `glpi_dropdown_locations_new` CHANGE `ID` `ID` INT( 11 ) DEFAULT '0' NOT NULL AUTO_INCREMENT";
	$result_auto_inc=$db->query($query_auto_inc);

}

///// FIN FONCTIONS POUR UPDATE LOCATION

function showLocationUpdateForm(){
	global $lang;
	$db=new DB;
	
	if (FieldExists("glpi_dropdown_locations", "parentID")) return true;

	if (!isset($_POST['root'])) $_POST['root']='';
	if (!isset($_POST['car_sep'])) $_POST['car_sep']='';

	if(!TableExists("glpi_dropdown_locations_new")) {
		$query = " CREATE TABLE `glpi_dropdown_locations_new` (`ID` INT NOT NULL ,`name` VARCHAR( 255 ) NOT NULL ,`parentID` INT NOT NULL ,PRIMARY KEY ( `ID` ),UNIQUE KEY (`name`,`parentID`), KEY(`parentID`));";
		$db->query($query) or die("LOCATION ".$db->error());
	}


	if (!isset($_POST["validate_location"])){
		echo "<div align='center'>";
		echo "<h4>".$lang["update"][130]."</h4>";
		echo "<p>".$lang["update"][131]."</p>";
		echo "<p>".$lang["update"][132]."<br>".$lang["update"][133]."</p>";
		echo "<form action=\"".$_SERVER["PHP_SELF"]."\" method=\"post\">";
		echo "<p>".$lang["update"][134].": <input type=\"text\" name=\"car_sep\" value=\"".$_POST['car_sep']."\"/></p>";
		echo "<p>".$lang["update"][135].": <input type=\"text\" name=\"root\" value=\"".$_POST['root']."\"/></p>";
		echo "<input type=\"submit\" class='submit' name=\"new_location\" value=\"".$lang["buttons"][2]."\" />";
		echo "<input type=\"hidden\" name=\"from_update\" value=\"from_update\" />";
		echo "</form>";
		echo "</div>";
	}



	if (isset($_POST["new_location"])){
		location_create_new($_POST['car_sep'],$_POST['root']);	
		echo "<h4>".$lang["update"][138].": </h4>";
		display_old_locations();	
		echo "<h4>".$lang["update"][137].": </h4>";
		display_new_locations();	
		echo "<p>".$lang["update"][136]."</p>";
		echo "<form action=\"".$_SERVER["PHP_SELF"]."\" method=\"post\">";
		echo "<input type=\"submit\" class='submit' name=\"validate_location\" value=\"".$lang["buttons"][2]."\" />";
		echo "<input type=\"hidden\" name=\"from_update\" value=\"from_update\" />";
		echo "</form>";
	}
	else if (isset($_POST["validate_location"])){
		validate_new_location();
		return true;
	} else {
	display_old_locations();	
	}
}

//Verifie si la table $tablename existe
function TableExists($tablename) {
  
   $db = new DB;
   // Get a list of tables contained within the database.
   $result = $db->list_tables($db);
   $rcount = $db->numrows($result);

   // Check each in list for a match.
   for ($i=0;$i<$rcount;$i++) {
       if (mysql_tablename($result, $i)==$tablename) return true;
   }
   return false;
}

//Verifie que le champs $field existe bien dans la table $table
function FieldExists($table, $field) {
	$db = new DB;
	$result = $db->query("SELECT * FROM ". $table ."");
	$fields = mysql_num_fields($result);
	$var1 = false;
	for ($i=0; $i < $fields; $i++) {
		$name  = mysql_field_name($result, $i);
		if($name == $field) {
			$var1 = true;
		}
	}
	return $var1;
}
// return true if the field $field of the table $table is a mysql index
// else return false
function isIndex($table, $field) {
	
		$db = new DB;
		$result = $db->query("select ". $field ." from ". $table);
		if ($result){
			$flags = mysql_field_flags($result,$field);
			if(eregi("multiple_key",$flags) || eregi("primary_key",$flags)) {
				return true;
			}
			else return false;
		} else return false;
		
}

//test la connection a la base de donnée.
function test_connect() {
$db = new DB;
if($db->error == 0) return true;
else return false;
}

//Change table2 from varchar to ID+varchar and update table1.chps with depends
function changeVarcharToID($table1, $table2, $chps)
{

global $lang;

$db = new DB;

if(!FieldExists($table2, "ID")) {
	$query = " ALTER TABLE `". $table2 ."` ADD `ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST";
	$db->query($query) or die("".$lang["update"][90].$db->error());
}
$query = "ALTER TABLE $table1 ADD `temp` INT";
$db->query($query) or die($lang["update"][90].$db->error());

$query = "select ". $table1 .".ID as row1, ". $table2 .".ID as row2 from ". $table1 .",". $table2 ." where ". $table2 .".name = ". $table1 .".". $chps." ";
$result = $db->query($query) or die($lang["update"][90].$db->error());
while($line = $db->fetch_array($result)) {
	$query = "update ". $table1 ." set temp = ". $line["row2"] ." where ID = '". $line["row1"] ."'";
	$db->query($query) or die($lang["update"][90].$db->error());
}

$query = "Alter table ". $table1 ." drop ". $chps."";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE ". $table1 ." CHANGE `temp` `". $chps ."` INT";
$db->query($query) or die($lang["update"][90].$db->error());
}

//update the database to the 0.31 version
function updateDbTo031()
{

global $lang;

$db = new DB;


//amSize ramSize
 $query = "Alter table users drop can_assign_job";
 $db->query($query) or die($lang["update"][90].$db->error());
 $query = "Alter table users add can_assign_job enum('yes','no') NOT NULL default 'no'";
 $db->query($query) or die($lang["update"][90].$db->error());
 $query = "Update users set can_assign_job = 'yes' where type = 'admin'";
 $db->query($query) or die($lang["update"][90].$db->error());
 
 echo "<br>Version 0.2 & < <br />";

//Version 0.21 ajout du champ ramSize a la table printers si non existant.


if(!FieldExists("printers", "ramSize")) {
	$query = "alter table printers add ramSize varchar(6) NOT NULL default ''";
	$db->query($query) or die($lang["update"][90].$db->error());
}

 echo "Version 0.21  <br/>";

//Version 0.3
//Ajout de NOT NULL et des valeurs par defaut.

$query = "ALTER TABLE computers MODIFY name VARCHAR(200) NOT NULL default ''";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE computers MODIFY type VARCHAR(100) NOT NULL default ''";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE computers MODIFY os VARCHAR(100) NOT NULL default ''";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE computers MODIFY osver VARCHAR(20) NOT NULL default ''";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE computers MODIFY processor VARCHAR(30) NOT NULL default ''";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE computers MODIFY processor_speed VARCHAR(30) NOT NULL default ''";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE computers MODIFY hdspace VARCHAR(6) NOT NULL default ''";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE computers MODIFY contact VARCHAR(90) NOT NULL default ''";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE computers MODIFY contact_num VARCHAR(90) NOT NULL default ''";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE computers MODIFY achat_date date NOT NULL default '0000-00-00'";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE computers MODIFY date_fin_garantie date NOT NULL default '0000-00-00'";


$query = "ALTER TABLE monitors MODIFY achat_date date NOT NULL default '0000-00-00'";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE monitors MODIFY date_fin_garantie date NOT NULL default '0000-00-00'";

$query = "ALTER TABLE networking MODIFY ram varchar(10) NOT NULL default ''";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE networking MODIFY serial varchar(50) NOT NULL default ''";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE networking MODIFY otherserial varchar(50) NOT NULL default ''";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE networking MODIFY achat_date date NOT NULL default '0000-00-00'";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE networking MODIFY date_fin_garantie date NOT NULL default '0000-00-00'";


$query = "ALTER TABLE printers MODIFY achat_date date NOT NULL default '0000-00-00'";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE printers MODIFY date_fin_garantie date NOT NULL default '0000-00-00'";

$query = "ALTER TABLE software MODIFY name varchar(200) NOT NULL default ''";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE software MODIFY platform varchar(200) NOT NULL default ''";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE software MODIFY version varchar(20) NOT NULL default ''";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE software MODIFY location varchar(200) NOT NULL default ''";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE software MODIFY comments text NOT NULL";


$query = "ALTER TABLE templates MODIFY templname varchar(200) NOT NULL default ''";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE templates MODIFY name varchar(200) NOT NULL default ''";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE templates MODIFY os varchar(200) NOT NULL default ''";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE templates MODIFY osver varchar(20) NOT NULL default ''";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE templates MODIFY processor varchar(200) NOT NULL default ''";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE templates MODIFY processor_speed varchar(100) NOT NULL default ''";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE templates MODIFY location varchar(200) NOT NULL default ''";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE templates MODIFY serial varchar(200) NOT NULL default ''";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE templates MODIFY otherserial varchar(200) NOT NULL default ''";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE templates MODIFY ramtype varchar(200) NOT NULL default ''";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE templates MODIFY ram varchar(20) NOT NULL default ''";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE templates MODIFY network varchar(200) NOT NULL default ''";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE templates MODIFY hdspace varchar(10) NOT NULL default ''";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE templates MODIFY contact varchar(200) NOT NULL default ''";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE templates MODIFY contact_num varchar(200) NOT NULL default ''";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE templates MODIFY comments text NOT NULL";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE templates MODIFY achat_date date NOT NULL default '0000-00-00'";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE templates MODIFY date_fin_garantie date NOT NULL default '0000-00-00'";

$query = "ALTER TABLE users MODIFY password varchar(80) NOT NULL default ''";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE users MODIFY email varchar(80) NOT NULL default ''";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE users MODIFY location varchar(100) NOT NULL default ''";
$db->query($query) or die($lang["update"][90].$db->error());
$query = "ALTER TABLE users MODIFY phone varchar(100) NOT NULL default ''";

 echo "Version 0.3  <br />";

 
}
 

//update database up to 0.31
function updatedbUpTo031()
{

global $lang;
$ret = array();

$db = new DB;
if(!TableExists("glpi_config"))
{
$query = "CREATE TABLE `glpi_config` (
  `ID` int(11) NOT NULL auto_increment,
  `num_of_events` varchar(200) NOT NULL default '',
  `jobs_at_login` varchar(200) NOT NULL default '',
  `sendexpire` varchar(200) NOT NULL default '',
  `cut` varchar(200) NOT NULL default '',
  `expire_events` varchar(200) NOT NULL default '',
  `list_limit` varchar(200) NOT NULL default '',
  `version` varchar(200) NOT NULL default '',
  `logotxt` varchar(200) NOT NULL default '',
  `root_doc` varchar(200) NOT NULL default '',
  `event_loglevel` varchar(200) NOT NULL default '',
  `mailing` varchar(200) NOT NULL default '',
  `imap_auth_server` varchar(200) NOT NULL default '',
  `imap_host` varchar(200) NOT NULL default '',
  `ldap_host` varchar(200) NOT NULL default '',
  `ldap_basedn` varchar(200) NOT NULL default '',
  `ldap_rootdn` varchar(200) NOT NULL default '',
  `ldap_pass` varchar(200) NOT NULL default '',
  `admin_email` varchar(200) NOT NULL default '',
  `mailing_signature` varchar(200) NOT NULL default '',
  `mailing_new_admin` varchar(200) NOT NULL default '',
  `mailing_followup_admin` varchar(200) NOT NULL default '',
  `mailing_finish_admin` varchar(200) NOT NULL default '',
  `mailing_new_all_admin` varchar(200) NOT NULL default '',
  `mailing_followup_all_admin` varchar(200) NOT NULL default '',
  `mailing_finish_all_admin` varchar(200) NOT NULL default '',
  `mailing_new_all_normal` varchar(200) NOT NULL default '',
  `mailing_followup_all_normal` varchar(200) NOT NULL default '',
  `mailing_finish_all_normal` varchar(200) NOT NULL default '',
  `mailing_new_attrib` varchar(200) NOT NULL default '',
  `mailing_followup_attrib` varchar(200) NOT NULL default '',
  `mailing_finish_attrib` varchar(200) NOT NULL default '',
  `mailing_new_user` varchar(200) NOT NULL default '',
  `mailing_followup_user` varchar(200) NOT NULL default '',
  `mailing_finish_user` varchar(200) NOT NULL default '',
  `ldap_field_name` varchar(200) NOT NULL default '',
  `ldap_field_email` varchar(200) NOT NULL default '',
  `ldap_field_location` varchar(200) NOT NULL default '',
  `ldap_field_realname` varchar(200) NOT NULL default '',
  `ldap_field_phone` varchar(200) NOT NULL default '',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM AUTO_INCREMENT=2 ";
$db->query($query) or die($lang["update"][90].$db->error());

$query = "INSERT INTO `glpi_config` VALUES (1, '10', '1', '1', '80', '30', '15', ' 0.4', 'GLPI powered by indepnet', '/glpi', '5', '0', '', '', '', '', '', '', 'admsys@xxxxx.fr', 'SIGNATURE', '1', '1', '1', '1', '0', '0', '0', '0', '0', '0', '0', '0','1', '1', '1', 'uid', 'mail', 'physicaldeliveryofficename', 'cn', 'telephonenumber')";
$db->query($query) or die($lang["update"][90].$db->error());

  echo "Version > 0.31  <br />";
}



//0.4 Prefixage des tables : 

if(!TableExists("glpi_computers")) {

	$query = "ALTER TABLE computers RENAME glpi_computers";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE connect_wire RENAME glpi_connect_wire";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE dropdown_gfxcard RENAME glpi_dropdown_gfxcard";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE dropdown_hdtype RENAME glpi_dropdown_hdtype";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE dropdown_iface RENAME glpi_dropdown_iface";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE dropdown_locations RENAME glpi_dropdown_locations";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE dropdown_moboard RENAME glpi_dropdown_moboard";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE dropdown_network RENAME glpi_dropdown_network";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE dropdown_os RENAME glpi_dropdown_os";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE dropdown_processor RENAME glpi_dropdown_processor";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE dropdown_ram RENAME glpi_dropdown_ram";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE dropdown_sndcard RENAME glpi_dropdown_sndcard";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE event_log RENAME glpi_event_log";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE followups RENAME glpi_followups";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE inst_software RENAME glpi_inst_software";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE licenses RENAME glpi_licenses";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE monitors RENAME glpi_monitors";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE networking RENAME glpi_networking";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE networking_ports RENAME glpi_networking_ports";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE networking_wire RENAME glpi_networking_wire";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE prefs RENAME glpi_prefs";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE printers RENAME glpi_printers";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE software RENAME glpi_software";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE templates RENAME glpi_templates";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE tracking RENAME glpi_tracking";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE type_computers RENAME glpi_type_computers";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE type_monitors RENAME glpi_type_monitors";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE type_networking RENAME glpi_type_networking";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE type_printers RENAME glpi_type_printers";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE users RENAME glpi_users";
	$db->query($query) or die($lang["update"][90].$db->error()); 

}	

//Ajout d'un champs ID dans la table users
if(!FieldExists("glpi_users", "ID")) {
	$query = "ALTER TABLE `glpi_users` DROP PRIMARY KEY";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE `glpi_users` ADD UNIQUE (`name`)";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = "ALTER TABLE `glpi_users` ADD INDEX (`name`)";
	$db->query($query) or die($lang["update"][90].$db->error());
	$query = " ALTER TABLE `glpi_users` ADD `ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST";
	$db->query($query) or die($lang["update"][90].$db->error());

}
//Mise a jour des ID pour les tables dropdown et type. clés primaires sur les tables dropdown et type, et mise a jour des champs liés
if(!FieldExists("glpi_dropdown_os", "ID")) {
	changeVarcharToID("glpi_computers", "glpi_dropdown_os", "os");
	changeVarcharToID("glpi_computers", "glpi_dropdown_hdtype", "hdtype");
	changeVarcharToID("glpi_computers", "glpi_dropdown_sndcard", "sndcard");
	changeVarcharToID("glpi_computers", "glpi_dropdown_moboard", "moboard");
	changeVarcharToID("glpi_computers", "glpi_dropdown_gfxcard", "gfxcard");
	changeVarcharToID("glpi_computers", "glpi_dropdown_network", "network");
	changeVarcharToID("glpi_computers", "glpi_dropdown_ram", "ramtype");
	changeVarcharToID("glpi_computers", "glpi_dropdown_locations", "location");
	changeVarcharToID("glpi_computers", "glpi_dropdown_processor", "processor");
	changeVarcharToID("glpi_monitors", "glpi_dropdown_locations", "location");
	changeVarcharToID("glpi_networking", "glpi_dropdown_locations", "location");
	changeVarcharToID("glpi_networking_ports", "glpi_dropdown_iface", "iface");
	changeVarcharToID("glpi_printers", "glpi_dropdown_locations", "location");
	changeVarcharToID("glpi_software", "glpi_dropdown_locations", "location");
	changeVarcharToID("glpi_software", "glpi_dropdown_os", "platform");
	changeVarcharToID("glpi_templates", "glpi_dropdown_os", "os");
	changeVarcharToID("glpi_templates", "glpi_dropdown_hdtype", "hdtype");
	changeVarcharToID("glpi_templates", "glpi_dropdown_sndcard", "sndcard");
	changeVarcharToID("glpi_templates", "glpi_dropdown_moboard", "moboard");
	changeVarcharToID("glpi_templates", "glpi_dropdown_gfxcard", "gfxcard");
	changeVarcharToID("glpi_templates", "glpi_dropdown_network", "network");
	changeVarcharToID("glpi_templates", "glpi_dropdown_ram", "ramtype");
	changeVarcharToID("glpi_templates", "glpi_dropdown_locations", "location");
	changeVarcharToID("glpi_templates", "glpi_dropdown_processor", "processor");
	changeVarcharToID("glpi_users", "glpi_dropdown_locations", "location");
	
	changeVarcharToID("glpi_monitors", "glpi_type_monitors", "type");
	changeVarcharToID("glpi_printers", "glpi_type_printers", "type");
	changeVarcharToID("glpi_networking", "glpi_type_networking", "type");
	changeVarcharToID("glpi_computers", "glpi_type_computers", "type");
	changeVarcharToID("glpi_templates", "glpi_type_computers", "type");
	
echo "Version 0.4 <br />";
}

if(!TableExists("glpi_type_peripherals")) {

$query = "CREATE TABLE `glpi_type_peripherals` (
	`ID` int(11) NOT NULL auto_increment,
	`name` varchar(255),
	 PRIMARY KEY  (`ID`)
	) TYPE=MyISAM;";
$db->query($query)or die("0A ".$lang["update"][90].$db->error());
}

if(!TableExists("glpi_peripherals")) {

	$query = "CREATE TABLE `glpi_peripherals` (
	`ID` int(11) NOT NULL auto_increment,
	`name` varchar(255) NOT NULL default '',
	`date_mod` datetime NOT NULL default '0000-00-00 00:00:00',
	 `contact` varchar(255) NOT NULL default '',
	 `contact_num` varchar(255) NOT NULL default '',
	`comments` text NOT NULL,
	`serial` varchar(255) NOT NULL default '',
	 `otherserial` varchar(255) NOT NULL default '',
	 `date_fin_garantie` date default NULL,
	  `achat_date` date NOT NULL default '0000-00-00',
	 `maintenance` int(2) default '0',
	  `location` int(11) NOT NULL default '0',
	 `type` int(11) NOT NULL default '0',
	 `brand` varchar(255) NOT NULL default '',
	  PRIMARY KEY  (`ID`)
	) TYPE=MyISAM;";

$db->query($query) or die("0 ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_prefs", "ID")) {
	$query = "Alter table glpi_prefs drop primary key";
	$db->query($query) or die("1 ".$lang["update"][90].$db->error());
	$query = "ALTER TABLE `glpi_prefs` ADD UNIQUE (`user`)";
	$db->query($query) or die("2 ".$lang["update"][90].$db->error());
	$query = "Alter table glpi_prefs add ID INT(11) not null auto_increment primary key";
	$db->query($query) or die("3 ".$lang["update"][90].$db->error());
}
if(!FieldExists("glpi_config", "ID")) {

	$query = "ALTER TABLE `glpi_config` CHANGE `config_id` `ID` INT( 11 ) NOT NULL AUTO_INCREMENT ";
	$db->query($query) or die("4 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_computers", "location")) {
	$query = "ALTER TABLE `glpi_computers` ADD INDEX ( `location` ) ";
	$db->query($query) or die("5 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_computers", "os")) {
	$query = "ALTER TABLE `glpi_computers` ADD INDEX ( `os` ) ";
	$db->query($query) or die("6 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_computers", "type")) {
	$query = "ALTER TABLE `glpi_computers` ADD INDEX ( `type` ) ";
	$db->query($query) or die("7 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_followups", "tracking")) {
	$query = "ALTER TABLE `glpi_followups` ADD INDEX ( `tracking` ) ";
	$db->query($query) or die("12 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_networking", "location")) {
	$query = "ALTER TABLE `glpi_networking` ADD INDEX ( `location` ) ";
	$db->query($query) or die("13 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_networking_ports", "on_device")) {
	$query = "ALTER TABLE `glpi_networking_ports` ADD INDEX ( `on_device` , `device_type` )";
	$db->query($query) or die("14 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_peripherals", "type")) {
	$query = "ALTER TABLE `glpi_peripherals` ADD INDEX ( `type` ) ";
	$db->query($query) or die("14 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_peripherals", "location")) {
	$query = "ALTER TABLE `glpi_peripherals` ADD INDEX ( `location` ) ";
	$db->query($query) or die("15 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_printers", "location")) {
	$query = "ALTER TABLE `glpi_printers` ADD INDEX ( `location` ) ";
	$db->query($query) or die("16 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_tracking", "computer")) {
	$query = "ALTER TABLE `glpi_tracking` ADD INDEX ( `computer` ) ";
	$db->query($query) or die("17 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_tracking", "author")) {
	$query = "ALTER TABLE `glpi_tracking` ADD INDEX ( `author` ) ";
	$db->query($query) or die("18 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_tracking", "assign")) {
	$query = "ALTER TABLE `glpi_tracking` ADD INDEX ( `assign` ) ";
	$db->query($query) or die("19 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_tracking", "date")) {
	$query = "ALTER TABLE `glpi_tracking` ADD INDEX ( `date` ) ";
	$db->query($query) or die("20 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_tracking", "closedate")) {
	$query = "ALTER TABLE `glpi_tracking` ADD INDEX ( `closedate` ) ";
	$db->query($query) or die("21 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_tracking", "status")) {
	$query = "ALTER TABLE `glpi_tracking` ADD INDEX ( `status` ) ";
	$db->query($query) or die("22 ".$lang["update"][90].$db->error());
}


if(!TableExists("glpi_dropdown_firmware")) {
	$query = " CREATE TABLE `glpi_dropdown_firmware` (`ID` INT NOT NULL AUTO_INCREMENT ,`name` VARCHAR( 255 ) NOT NULL ,PRIMARY KEY ( `ID` ))";
	$db->query($query) or die("23 ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_networking","firmware")) {
	$query = "ALTER TABLE `glpi_networking` ADD `firmware` INT(11);";
	$db->query($query) or die("24 ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_tracking","realtime")) {
	$query = "ALTER TABLE `glpi_tracking` ADD `realtime` FLOAT NOT NULL;";
	$db->query($query) or die("25 ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_printers","flags_usb")) {
	$query = "ALTER TABLE `glpi_printers` ADD `flags_usb` TINYINT DEFAULT '0' NOT NULL AFTER `flags_par`";
	$db->query($query) or die("26 ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_licenses","expire")) {
	$query = "ALTER TABLE `glpi_licenses` ADD `expire` date default NULL";
	$db->query($query) or die("27 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_licenses", "sID")) {
$query = "ALTER TABLE `glpi_licenses` ADD INDEX ( `sID` ) ";
$db->query($query) or die("32 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_followups", "author")) {
$query = "ALTER TABLE `glpi_followups` ADD INDEX ( `author` ) ";
$db->query($query) or die("33 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_monitors", "type")) {
$query = "ALTER TABLE `glpi_monitors` ADD INDEX ( `type` ) ";
$db->query($query) or die("34 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_monitors", "location")) {
$query = "ALTER TABLE `glpi_monitors` ADD INDEX ( `location` ) ";
$db->query($query) or die("35 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_monitors", "type")) {
$query = "ALTER TABLE `glpi_monitors` ADD INDEX ( `type` ) ";
$db->query($query) or die("37 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_networking", "type")) {
$query = "ALTER TABLE `glpi_networking` ADD INDEX ( `type` ) ";
$db->query($query) or die("38 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_networking", "firmware")) {
$query = "ALTER TABLE `glpi_networking` ADD INDEX ( `firmware` ) ";
$db->query($query) or die("39 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_printers", "type")) {
$query = "ALTER TABLE `glpi_printers` ADD INDEX ( `type` ) ";
$db->query($query) or die("42 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_software", "platform")) {
$query = "ALTER TABLE `glpi_software` ADD INDEX ( `platform` ) ";
$db->query($query) or die("44 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_software", "location")) {
$query = "ALTER TABLE `glpi_software` ADD INDEX ( `location` ) ";
$db->query($query) or die("45 ".$lang["update"][90].$db->error());
}

if(!TableExists("glpi_dropdown_netpoint")) {
	$query = " CREATE TABLE `glpi_dropdown_netpoint` (`ID` INT NOT NULL AUTO_INCREMENT ,`location` INT NOT NULL ,`name` VARCHAR( 255 ) NOT NULL ,PRIMARY KEY ( `ID` ))";
	$db->query($query) or die("46 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_dropdown_netpoint", "location")) {
$query = "ALTER TABLE `glpi_dropdown_netpoint` ADD INDEX ( `location` ) ";
$db->query($query) or die("47 ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_networking_ports","netpoint")) {
	$query = "ALTER TABLE `glpi_networking_ports` ADD `netpoint` INT default NULL";
	$db->query($query) or die("27 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_networking_ports", "netpoint")) {
$query = "ALTER TABLE `glpi_networking_ports` ADD INDEX ( `netpoint` ) ";
$db->query($query) or die("47 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_networking_wire", "end1")) {
$query = "ALTER TABLE `glpi_networking_wire` ADD INDEX ( `end1` ) ";
$db->query($query) or die("40 ".$lang["update"][90].$db->error());


// Clean Table
$query = "SELECT * FROM  `glpi_networking_wire` ORDER BY end1, end2 ";
$result=$db->query($query);
$curend1=-1;
$curend2=-1;
while($line = $db->fetch_array($result)) {
	if ($curend1==$line['end1']&&$curend2==$line['end2']){
		$q2="DELETE FROM `glpi_networking_wire` WHERE `ID`='".$line['ID']."' LIMIT 1";
		$db->query($q2);
		}
	else {$curend1=$line['end1'];$curend2=$line['end2'];}
	}	
		
$query = "ALTER TABLE `glpi_networking_wire` ADD UNIQUE end1_1 ( `end1`,`end2` ) ";
$db->query($query) or die("477 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_networking_wire", "end2")) {
$query = "ALTER TABLE `glpi_networking_wire` ADD INDEX ( `end2` ) ";
$db->query($query) or die("41 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_connect_wire", "end1")) {
$query = "ALTER TABLE `glpi_connect_wire` ADD INDEX ( `end1` ) ";
$db->query($query) or die("40 ".$lang["update"][90].$db->error());

// Clean Table
$query = "SELECT * FROM  `glpi_connect_wire` ORDER BY type, end1, end2 ";
$result=$db->query($query);
$curend1=-1;
$curend2=-1;
$curtype=-1;
while($line = $db->fetch_array($result)) {
	if ($curend1==$line['end1']&&$curend2==$line['end2']&&$curtype==$line['type']){
		$q2="DELETE FROM `glpi_connect_wire` WHERE `ID`='".$line['ID']."' LIMIT 1";
		$db->query($q2);
		}
	else{ $curend1=$line['end1'];$curend2=$line['end2'];$curtype=$line['type'];}
	}	
$query = "ALTER TABLE `glpi_connect_wire` ADD UNIQUE end1_1 ( `end1`,`end2`,`type` ) ";
$db->query($query) or die("478 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_connect_wire", "end2")) {
$query = "ALTER TABLE `glpi_connect_wire` ADD INDEX ( `end2` ) ";
$db->query($query) or die("40 ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_connect_wire", "type")) {
$query = "ALTER TABLE `glpi_connect_wire` ADD INDEX ( `type` ) ";
$db->query($query) or die("40 ".$lang["update"][90].$db->error());
}



if(!FieldExists("glpi_config","ldap_condition")) {
	$query = "ALTER TABLE `glpi_config` ADD `ldap_condition` varchar(255) NOT NULL default ''";
	$db->query($query) or die("48 ".$lang["update"][90].$db->error());
}

$query = "ALTER TABLE `glpi_users` CHANGE `type` `type` ENUM( 'normal', 'admin', 'post-only', 'super-admin' ) DEFAULT 'normal' NOT NULL";
$db->query($query) or die("49 ".$lang["update"][90].$db->error());

$ret["adminchange"] = false;
//All "admin" users have to be set as "super-admin"
if(!superAdminExists()) {
	$query = "update glpi_users set type = 'super-admin' where type = 'admin'";
	$db->query($query) or die("49 ".$lang["update"][90].$db->error());
	if($db->affected_rows() != 0) {
		$ret["adminchange"] = true;
	}
}

if(!FieldExists("glpi_users","password_md5")) {
	$query = "ALTER TABLE `glpi_users` ADD `password_md5` VARCHAR(80) NOT NULL AFTER `password` ";
	$db->query($query) or die("glpi_users.Password_md5".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_config","permit_helpdesk")) {
	$query = "ALTER TABLE `glpi_config` ADD `permit_helpdesk` varchar(200) NOT NULL";
	$db->query($query) or die("glpi_config_permit_helpdesk ".$lang["update"][90].$db->error());
}

if(!TableExists("glpi_reservation_item")) {

 echo "<br>Version 0.42 <br />";

	$query = "CREATE TABLE glpi_reservation_item (ID int(11) NOT NULL auto_increment,device_type tinyint(4) NOT NULL default '0', id_device int(11) NOT NULL default '0', comments text NOT NULL, PRIMARY KEY  (ID), KEY device_type (device_type));";

	$db->query($query) or die("4201 ".$lang["update"][90].$db->error());
}

if(!TableExists("glpi_reservation_resa")) {
	$query = "CREATE TABLE glpi_reservation_resa (  ID bigint(20) NOT NULL auto_increment,  id_item int(11) NOT NULL default '0',  begin datetime NOT NULL default '0000-00-00 00:00:00',  end datetime NOT NULL default '0000-00-00 00:00:00',  id_user int(11) NOT NULL default '0',  PRIMARY KEY  (ID),  KEY id_item (id_item),  KEY id_user (id_user),  KEY begin (begin),  KEY end (end));";

	$db->query($query) or die("4202 ".$lang["update"][90].$db->error());
}

//Mise a jour 0.42 verification des prefs pour chaque user.
$query = "select ID, name from glpi_users";
$query2 = "select ID, user from glpi_prefs";
$result = $db->query($query);
$result2 = $db->query($query2);
if($db->numrows($result) != $db->numrows($result2)) { 
	$users = array();
	$i = 0;
	while ($line = $db->fetch_array($result2)) {
		$prefs[$i] = $line["user"];
		$i++;
	}
	while($line = $db->fetch_array($result)) {
		if(!in_array($line["name"],$prefs)) {
			$query_insert =  "INSERT INTO `glpi_prefs` ( `user` , `tracking_order` , `language`) VALUES ( '".$line["name"]."', 'no', 'french')";
			$db->query($query_insert) or die("glpi maj prefs ".$lang["update"][90].$db->error()); 
		}
	}
}


if(!FieldExists("glpi_tracking","device_type")) {
	$query = "ALTER TABLE `glpi_tracking` ADD `device_type` INT DEFAULT '1' NOT NULL AFTER `assign` ;";
	$db->query($query) or die("4203 ".$lang["update"][90].$db->error());
}

// Ajout language par defaut
if(!FieldExists("glpi_config","default_language")) {

	$query = "ALTER TABLE `glpi_config` ADD `default_language` VARCHAR( 255 ) DEFAULT 'french' NOT NULL ;";
	$db->query($query) or die("4204 ".$lang["update"][90].$db->error());

}

// Augmentation taille itemtype
	$query = "ALTER TABLE `glpi_event_log` CHANGE `itemtype` `itemtype` VARCHAR(20) NOT NULL ;";
	$db->query($query) or die("4204 ".$lang["update"][90].$db->error());

	// Correction des itemtype tronqués
	$query = "UPDATE `glpi_event_log` SET `itemtype` = 'reservation' WHERE `itemtype` = 'reservatio' ;";
	$db->query($query) or die("4204 ".$lang["update"][90].$db->error());


// Mise a jour du numéro de version et de la langue par defaut---- A LAISSER 0 LA FIN
	$query = "UPDATE `glpi_config` SET `version` = ' 0.42', default_language='".$_SESSION["dict"]."' ;";
	$db->query($query) or die("4203 ".$lang["update"][90].$db->error());

/*******************************GLPI 0.5***********************************************/
//pass all templates to computers
if(!FieldExists("glpi_computers","is_template")) {
	$query = "ALTER TABLE `glpi_computers` ADD `is_template` ENUM('0','1') DEFAULT '0' NOT NULL ";
	$db->query($query) or die("0.5 alter computers add is_template ".$lang["update"][90].$db->error());
	$query = "ALTER TABLE `glpi_computers` ADD `tplname` VARCHAR(200) DEFAULT NULL ";
	$db->query($query) or die("0.5 alter computers add tplname ".$lang["update"][90].$db->error());
	
	$query = "Select * from glpi_templates";
	$result = $db->query($query);
	
	
	while($line = $db->fetch_array($result)) {
		$query2 = "INSERT INTO glpi_computers (`ID`,`name`, `osver`, `processor_speed`, `serial`, `otherserial`, `ram`, `hdspace`, `contact`, `contact_num`, `comments`, `achat_date`, `date_fin_garantie`, `maintenance`, `os`, `hdtype`, `sndcard`, `moboard`, `gfxcard`, `network`, `ramtype`, `location`, `processor`, `type`, `is_template`, `tplname`)";
		
		$query2 .= " VALUES ('','".$line["name"]."', '".$line["osver"]."', '".$line["processor_speed"]."', '".$line["serial"]."', '".$line["otherserial"]."', '".$line["ram"]."', '".$line ["hdspace"]."', '".$line["contact"]."', '".$line["contact_num"]."', '".$line["comments"]."', '".$line["achat_date"]."', '".$line["date_fin_garantie"]."', '".$line["maintenance"]."', '".$line["os"]."', '".$line["hdtype"]."', '".$line["sndcard"]."', '".$line["moboard"]."', '".$line["gfxcard"]."', '".$line["network"]."', '".$line["ramtype"]."', '".$line["location"]."', '".$line["processor"]."', '".$line["type"]."','1','".$line["templname"]."')";	
		#echo $query2;
		$db->query($query2) or die("0.5-convert template 2 computers ".$db->error());
	}
	$query = "DROP TABLE glpi_templates";
	$db->query($query) or die("0.5 drop table templates ".$db->error());
	
	$query="INSERT INTO glpi_computers (is_template,tplname) VALUES ('1','Blank Template')";
	$db->query($query) or die("0.5 add blank template ".$lang["update"][90].$db->error());	

	 echo "<br>Version 0.5 <br />";
}




//New internal peripherals ( devices ) config

if(!TableExists("glpi_computer_device")) {
	$query = "CREATE TABLE `glpi_computer_device` (
  `ID` int(11) NOT NULL auto_increment,
  `specificity` varchar(250) NOT NULL default '',
  `device_type` tinyint(4) NOT NULL default '0',
  `FK_device` int(11) NOT NULL default '0',
  `FK_computers` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY (`device_type`),
  KEY (`device_type`,`FK_device`),
  KEY (`FK_computers`)
) TYPE=MyISAM;";
	$db->query($query) or die("0.5 CREATE TABLE `glpi_computer_device` ".$lang["update"][90].$db->error());
}

if(!TableExists("glpi_device_gfxcard")) {
	$query = "CREATE TABLE `glpi_device_gfxcard` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(120) NOT NULL default '',
  `ram` varchar(10) NOT NULL default '',
  `interface` enum('AGP','PCI','PCI-X','Other') NOT NULL default 'AGP',
  `comment` text NOT NULL,
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
   `specif_default` VARCHAR( 250 ) NOT NULL,
  PRIMARY KEY  (`ID`),
  KEY(`FK_glpi_enterprise`)
) TYPE=MyISAM;";
	$db->query($query) or die("0.5 create table `glpi_device_gfxcard` ".$lang["update"][90].$db->error());
	compDpd2Device(GFX_DEVICE,"gfxcard","gfxcard","gfxcard");
}
if(!TableExists("glpi_device_hdd")) {
	$query = "CREATE TABLE `glpi_device_hdd` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(100) NOT NULL default '',
  `rpm` varchar(20) NOT NULL default '',
  `interface` enum('IDE','SATA','SCSI') NOT NULL default 'IDE',
  `cache` varchar(20) NOT NULL default '',
  `comment` text NOT NULL,
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `specif_default` VARCHAR( 250 ) NOT NULL,
  PRIMARY KEY  (`ID`),
  KEY(`FK_glpi_enterprise`)
) TYPE=MyISAM;";
	$db->query($query) or die("0.5 CREATE TABLE `glpi_device_hdtype` ".$lang["update"][90].$db->error());
	compDpd2Device(HDD_DEVICE,"hdd","hdtype","hdtype","hdspace");
}
if(!TableExists("glpi_device_iface")) {
	$query = "CREATE TABLE `glpi_device_iface` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(120) NOT NULL default '',
  `bandwidth` varchar(20) NOT NULL default '',
  `comment` text NOT NULL,
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `specif_default` VARCHAR( 250 ) NOT NULL,
  PRIMARY KEY  (`ID`),
  KEY(`FK_glpi_enterprise`)
) TYPE=MyISAM;";
	$db->query($query) or die("0.5- CREATE TABLE `glpi_device_iface` ".$lang["update"][90].$db->error());
	compDpd2Device(NETWORK_DEVICE,"iface","network","network");
}
if(!TableExists("glpi_device_moboard")) {
	$query = "CREATE TABLE `glpi_device_moboard` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(100) NOT NULL default '',
  `chipset` varchar(120) NOT NULL default '',
  `comment` text NOT NULL,
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `specif_default` VARCHAR( 250 ) NOT NULL,
  PRIMARY KEY  (`ID`),
  KEY(`FK_glpi_enterprise`)
) TYPE=MyISAM;";
	$db->query($query) or die("0.5 CREATE TABLE `glpi_device_moboard` ".$lang["update"][90].$db->error());
	compDpd2Device(MOBOARD_DEVICE,"moboard","moboard","moboard");
}
if(!TableExists("glpi_device_processor")) {
	$query = "CREATE TABLE `glpi_device_processor` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(120) NOT NULL default '',
  `frequence` int(11) NOT NULL default '0',
  `comment` text NOT NULL,
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `specif_default` VARCHAR( 250 ) NOT NULL,
  PRIMARY KEY  (`ID`),
  KEY(`FK_glpi_enterprise`)
) TYPE=MyISAM;";
	$db->query($query) or die("0.5 CREATE TABLE `glpi_device_processor` ".$lang["update"][90].$db->error());
	compDpd2Device(PROCESSOR_DEVICE,"processor","processor","processor","processor_speed");
}
if(!TableExists("glpi_device_ram")) {
	$query = "CREATE TABLE `glpi_device_ram` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(100) NOT NULL default '',
  `type` enum('EDO','DDR','SDRAM','SDRAM-2') NOT NULL default 'EDO',
  `frequence` varchar(8) NOT NULL default '',
  `comment` text NOT NULL,
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `specif_default` VARCHAR( 250 ) NOT NULL,
  PRIMARY KEY  (`ID`),
  KEY(`FK_glpi_enterprise`)
) TYPE=MyISAM;";
	$db->query($query) or die("0.5 CREATE TABLE `glpi_device_ram` ".$lang["update"][90].$db->error());
	compDpd2Device(RAM_DEVICE,"ram","ram","ramtype","ram");
}
if(!TableExists("glpi_device_sndcard")) {
	$query = "CREATE TABLE `glpi_device_sndcard` (
  `ID` int(11) NOT NULL auto_increment,
  `designation` varchar(120) NOT NULL default '',
  `type` varchar(100) NOT NULL default '',
  `comment` text NOT NULL,
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `specif_default` VARCHAR( 250 ) NOT NULL,
  PRIMARY KEY  (`ID`),
  KEY(`FK_glpi_enterprise`)
) TYPE=MyISAM;";
	$db->query($query) or die("0.5 CREATE TABLE `glpi_device_sndcard ".$lang["update"][90].$db->error());
	compDpd2Device(SND_DEVICE,"sndcard","sndcard","sndcard");
}

if(!TableExists("glpi_device_power")) {
	$query = "CREATE TABLE glpi_device_power (
	ID int(11) NOT NULL auto_increment,
	designation varchar(255) NOT NULL default '',
	power varchar(20) NOT NULL default '',
	atx enum('Y','N') NOT NULL default 'Y',
	`comment` text NOT NULL,
	FK_glpi_enterprise int(11) NOT NULL default '0',
	`specif_default` VARCHAR( 250 ) NOT NULL,
	PRIMARY KEY (ID),
	KEY FK_glpi_enterprise (FK_glpi_enterprise)
	) TYPE=MyISAM;";
	$db->query($query) or die("Error : ".$query." ".mysql_error());
}

if(!TableExists("glpi_device_case")) {
	$query = "CREATE TABLE glpi_device_case(
	ID int( 11 ) NOT NULL AUTO_INCREMENT ,
	designation varchar( 255 ) NOT NULL default '',
	format enum( 'Grand', 'Moyen', 'Micro' ) NOT NULL default 'Moyen',
	`comment` text NOT NULL ,
	FK_glpi_enterprise int( 11 ) NOT NULL default '0',
	`specif_default` VARCHAR( 250 ) NOT NULL,
	PRIMARY KEY ( ID ) ,
	KEY FK_glpi_enterprise( FK_glpi_enterprise )
	)TYPE = MyISAM;";
	$db->query($query) or die("Error : ".$query." ".mysql_error());
}

if(!TableExists("glpi_device_drive")) {
	$query = "CREATE TABLE `glpi_device_drive` (
	`ID` INT NOT NULL AUTO_INCREMENT ,
	`designation` VARCHAR( 255 ) NOT NULL ,
	`is_writer` ENUM( 'Y', 'N' ) DEFAULT 'Y' NOT NULL ,
	`speed` VARCHAR( 30 ) NOT NULL ,
	`interface` ENUM( 'IDE', 'SATA', 'SCSI' ) NOT NULL ,
	`comment` TEXT NOT NULL ,
	`FK_glpi_enterprise` INT NOT NULL ,
	`specif_default` VARCHAR( 250 ) NOT NULL,
	KEY FK_glpi_enterprise( FK_glpi_enterprise ),
	PRIMARY KEY ( `ID` )
	)TYPE=MyISAM;";
	$db->query($query) or die("Error : ".$query." ".mysql_error());
}

if(!TableExists("glpi_device_pci")) {
	$query = "CREATE TABLE glpi_device_pci (
	ID int(11) NOT NULL auto_increment,
	designation varchar(255) NOT NULL default '',
	`comment` text NOT NULL,
	FK_glpi_enterprise int(11) NOT NULL default '0',
	`specif_default` VARCHAR( 250 ) NOT NULL,
	PRIMARY KEY (ID),
	KEY FK_glpi_enterprise (FK_glpi_enterprise)
	) TYPE=MyISAM;";
	$db->query($query) or die("Error : ".$query." ".mysql_error());
} 

if(!TableExists("glpi_device_control")) {
	$query = "CREATE TABLE glpi_device_control (
	ID int(11) NOT NULL auto_increment,
	designation varchar(255) NOT NULL default '',
	interface enum('IDE','SATA','SCSI','USB') NOT NULL default 'IDE',
	raid enum('Y','N') NOT NULL default 'Y',
	`comment` text NOT NULL,
	FK_glpi_enterprise int(11) NOT NULL default '0',
	`specif_default` VARCHAR( 250 ) NOT NULL,
	PRIMARY KEY (ID),
	KEY FK_glpi_enterprise (FK_glpi_enterprise)
	) TYPE=MyISAM;";
	$db->query($query) or die("Error : ".$query." ".mysql_error());
}


// END new internal devices.

if(!TableExists("glpi_enterprises")) {
	$query = "CREATE TABLE `glpi_enterprises` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  `type` int(11) NOT NULL default '0',
  `address` text NOT NULL default '',
  `website` varchar(100) NOT NULL default '',
  `phonenumber` varchar(20) NOT NULL default '',
  `comments` text NOT NULL,
  `deleted` enum('Y','N') NOT NULL default 'N',
  PRIMARY KEY  (`ID`),
  KEY `deleted` (`deleted`),
  KEY `type` (`type`)
) TYPE=MyISAM;";
	$db->query($query) or die("0.5 CREATE TABLE `glpi_enterprise ".$lang["update"][90].$db->error());
}

/// Base connaissance
if(!TableExists("glpi_dropdown_kbcategories")) {
$query="CREATE TABLE `glpi_dropdown_kbcategories` (
  `ID` int(11) NOT NULL auto_increment,
  `parentID` int(11) NOT NULL default '0',
  `name` varchar(255) NOT NULL,
  PRIMARY KEY  (`ID`),
  KEY(`parentID`),
  UNIQUE KEY(`parentID`,`name`)
)  TYPE=MyISAM;";
	$db->query($query) or die("0.5 CREATE TABLE `glpi_dropdown_kbcategories ".$lang["update"][90].$db->error());

$query="CREATE TABLE `glpi_kbitems` (
  `ID` int(11) NOT NULL auto_increment,
  `categoryID` int(11) NOT NULL default '0',
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `faq` enum('yes','no') NOT NULL default 'no',
  PRIMARY KEY  (`ID`),
  KEY(`categoryID`)
) TYPE=MyISAM;";
	$db->query($query) or die("0.5 CREATE TABLE `glpi_kbitems ".$lang["update"][90].$db->error());

}

// Comment reservation
if(!FieldExists("glpi_reservation_resa","comment")) {
	$query = "ALTER TABLE `glpi_reservation_resa` ADD `comment` VARCHAR( 255 ) NOT NULL ;";
	$db->query($query) or die("0.5 alter reservation add comment ".$lang["update"][90].$db->error());
}	

// Tracking categorie
if(!TableExists("glpi_dropdown_tracking_category")) {

$query= "CREATE TABLE glpi_dropdown_tracking_category (
  ID int(11) NOT NULL auto_increment,
  name varchar(255) default NULL,
  PRIMARY KEY  (ID)
);";
	$db->query($query) or die("0.5 CREATE TABLE `glpi_dropdown_tracking_category ".$lang["update"][90].$db->error());

	$query= "ALTER TABLE `glpi_tracking` ADD `category` INT( 11 ) ;";
	$db->query($query) or die("0.5 alter tracking add categorie ".$lang["update"][90].$db->error());
}

// Nouvelle gestion des software et licenses
if(!FieldExists("glpi_licenses","oem")) {
$query = "ALTER TABLE `glpi_licenses` ADD `oem` ENUM( 'N', 'Y' ) DEFAULT 'N' NOT NULL , ADD `oem_computer` INT( 11 ) NOT NULL, ADD `buy` ENUM( 'Y', 'N' ) DEFAULT 'Y' NOT NULL;";
	$db->query($query) or die("0.5 alter licenses add oem + buy ".$lang["update"][90].$db->error());

$query = "ALTER TABLE `glpi_software` ADD `is_update` ENUM( 'N', 'Y' ) DEFAULT 'N' NOT NULL , ADD `update_software` INT( 11 ) NOT NULL DEFAULT '-1';";
	$db->query($query) or die("0.5 alter software add update ".$lang["update"][90].$db->error());
}

// Couleur pour les priorités
if(!FieldExists("glpi_config","priority_1")) {
$query= "ALTER TABLE `glpi_config` ADD `priority_1` VARCHAR( 200 ) DEFAULT '#fff2f2' NOT NULL, ADD `priority_2` VARCHAR( 200 ) DEFAULT '#ffe0e0' NOT NULL, ADD `priority_3` VARCHAR( 200 ) DEFAULT '#ffcece' NOT NULL, ADD `priority_4` VARCHAR( 200 ) DEFAULT '#ffbfbf' NOT NULL, ADD `priority_5` VARCHAR( 200 ) DEFAULT '#ffadad' NOT NULL ;";
	$db->query($query) or die("0.5 alter config add priority_X ".$lang["update"][90].$db->error());

}

// Gestion des cartouches
if(!TableExists("glpi_cartridges")) {
$query= "CREATE TABLE `glpi_cartridges` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_glpi_cartridges_type` int(11) default NULL,
  `FK_glpi_printers` int(11) default NULL,
  `date_in` date default NULL,
  `date_use` date default NULL,
  `date_out` date default NULL,
  `pages` varchar(30) default NULL,
  PRIMARY KEY  (`ID`),
  KEY(`FK_glpi_cartridges_type`),
  KEY(`FK_glpi_printers`),
  KEY(`date_in`),
  KEY(`date_use`),
  KEY(`date_out`)
);";
	$db->query($query) or die("0.5 CREATE TABLE glpi_cartridges ".$lang["update"][90].$db->error());

$query= "CREATE TABLE `glpi_cartridges_type` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `ref` varchar(255) NOT NULL default '',
  `location` int(11) NOT NULL default '0',
  `type` tinyint(4) NOT NULL default '0',
  `FK_glpi_enterprise` int(11) NOT NULL default '0',
  `tech_num` int(11) default '0',
  `deleted` enum('Y','N') NOT NULL default 'N',
  `comments` text NOT NULL,
  PRIMARY KEY  (`ID`),
  KEY(`FK_glpi_enterprise`),
  KEY(`tech_num`),
  KEY(`deleted`)
);";
	$db->query($query) or die("0.5 CREATE TABLE glpi_cartridges_type ".$lang["update"][90].$db->error());
	
$query= "CREATE TABLE `glpi_cartridges_assoc` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_glpi_cartridges_type` int(11) NOT NULL default '0',
  `FK_glpi_type_printer` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `FK_glpi_type_printer` (`FK_glpi_type_printer`,`FK_glpi_cartridges_type`),
 KEY(`FK_glpi_cartridges_type`),
 KEY(`FK_glpi_type_printer`) 
);";
	$db->query($query) or die("0.5 CREATE TABLE glpi_cartridges_assoc ".$lang["update"][90].$db->error());
}

// La gestion des contrats
if(!TableExists("glpi_contracts")) {
$query= "CREATE TABLE `glpi_contacts` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `phone` varchar(30) NOT NULL default '',
  `phone2` varchar(30) NOT NULL default '',
  `fax` varchar(30) NOT NULL default '',
  `email` varchar(255) NOT NULL default '',
  `type` tinyint(4) NOT NULL default '1',
  `comments` text NOT NULL,
  `deleted` enum('Y','N') NOT NULL default 'N',
  PRIMARY KEY  (`ID`)
) TYPE=MyISAM;";
	$db->query($query) or die("0.5 CREATE TABLE glpi_contact ".$lang["update"][90].$db->error());

$query = " CREATE TABLE `glpi_dropdown_enttype` (`ID` INT NOT NULL AUTO_INCREMENT ,`name` VARCHAR( 255 ) NOT NULL ,PRIMARY KEY ( `ID` ))";
$db->query($query) or die("23 ".$lang["update"][90].$db->error());

	
$query= "CREATE TABLE `glpi_contact_enterprise` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_enterprise` int(11) NOT NULL default '0',
  `FK_contact` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `FK_enterprise` (`FK_enterprise`,`FK_contact`),
  KEY(`FK_enterprise`),
  KEY(`FK_contact`) 
) TYPE=MyISAM;";
	$db->query($query) or die("0.5 CREATE TABLE glpi_contact_enterprise ".$lang["update"][90].$db->error());

$query= "CREATE TABLE `glpi_contracts` (
  `ID` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `num` varchar(255) NOT NULL default '',
  `cost` float NOT NULL default '0',
  `contract_type` int(11) NOT NULL default '0',
  `begin_date` date default NULL,
  `duration` tinyint(4) NOT NULL default '0',
  `notice` tinyint(4) NOT NULL default '0',
  `periodicity` tinyint(4) NOT NULL default '0',
  `facturation` tinyint(4) NOT NULL default '0',
  `bill_type` int(11) NOT NULL default '0',
  `comments` text NOT NULL,
  `compta_num` varchar(255) NOT NULL default '',
  `deleted` enum('Y','N') NOT NULL default 'N',
  `week_begin_hour` time NOT NULL default '00:00:00',
  `week_end_hour` time NOT NULL default '00:00:00',
  `saturday_begin_hour` time NOT NULL default '00:00:00',
  `saturday_end_hour` time NOT NULL default '00:00:00',
  `saturday` enum('Y','N') NOT NULL default 'N',
  `monday_begin_hour` time NOT NULL default '00:00:00',
  `monday_end_hour` time NOT NULL default '00:00:00',
  `monday` enum('Y','N') NOT NULL default 'N',
  PRIMARY KEY  (`ID`),
  KEY `contract_type` (`contract_type`),
  KEY `begin_date` (`begin_date`),
  KEY `bill_type` (`bill_type`)
) TYPE=MyISAM;";
	$db->query($query) or die("0.5 CREATE TABLE glpi_contract ".$lang["update"][90].$db->error());

$query= "CREATE TABLE `glpi_contract_device` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_contract` int(11) NOT NULL default '0',
  `FK_device` int(11) NOT NULL default '0',
  `device_type` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `FK_contract` (`FK_contract`,`FK_device`,`device_type` ),
  KEY (`FK_contract`),
  KEY (`FK_device`,`device_type`)
) TYPE=MyISAM;";
	$db->query($query) or die("0.5 CREATE TABLE glpi_contract_device ".$lang["update"][90].$db->error());

$query= "CREATE TABLE `glpi_contract_enterprise` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_enterprise` int(11) NOT NULL default '0',
  `FK_contract` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `FK_enterprise` (`FK_enterprise`,`FK_contract`),
  KEY  (`FK_enterprise`),
  KEY (`FK_contract`)
) TYPE=MyISAM;";
	$db->query($query) or die("0.5 CREATE TABLE glpi_contrat_enterprise ".$lang["update"][90].$db->error());

$query= "CREATE TABLE `glpi_infocoms` (
  `ID` int(11) NOT NULL auto_increment,
  `FK_device` int(11) NOT NULL default '0',
  `device_type` tinyint(4) NOT NULL default '0',
  `buy_date` date NOT NULL default '0000-00-00',
  `use_date` date NOT NULL default '0000-00-00',
  `warranty_duration` tinyint(4) NOT NULL default '0',
  `warranty_info` varchar(255) NOT NULL default '',
  `FK_enterprise` int(11) default NULL,
  `num_commande` varchar(50) NOT NULL default '',
  `bon_livraison` varchar(50) NOT NULL default '',
  `num_immo` varchar(50) NOT NULL default '',
  `value` float NOT NULL default '0',
  `warranty_value` float default NULL,
  `amort_time` tinyint(4) NOT NULL default '0',
  `amort_type` varchar(20) NOT NULL default '',
  `amort_coeff` float NOT NULL default '0',
  `comments` text NOT NULL,
  PRIMARY KEY  (`ID`),
  UNIQUE KEY `FK_device` (`FK_device`,`device_type`),
  KEY `FK_enterprise` (`FK_enterprise`),
  KEY `buy_date` (`buy_date`)
) TYPE=MyISAM;";
	$db->query($query) or die("0.5 CREATE TABLE glpi_infocom ".$lang["update"][90].$db->error());

///// Move warranty infos from item to infocoms.

function date_diff($from, $to) {
	$from=strtotime($from);
	$to=strtotime($to);
	if ($from > $to) {
		$t = $to;
		$to = $from;
		$from = $t;
	}

	$year1 = date("Y", $from);
	$year2 = date("Y", $to);
	$month1 = date("n", $from);
	$month2 = date("n", $to);

	if ($month2 < $month1) {
		$month2 += 12;
		$year2 --;
	}
	$months = $month2 - $month1;
	$years = $year2 - $year1;
	return (12*$years+$months);
}

function updateMaintenanceInfos($table,$type,$ID){
	$db=new DB;
	$elements=array();
	$query="SELECT ID from $table WHERE maintenance='1'";
	$result=$db->query($query);
	while ($data=$db->fetch_array($result)){
	$query_insert="INSERT INTO glpi_contract_device (FK_contract,FK_device,device_type) VALUES ('$ID','".$data["ID"]."','$type')";	
	$result_insert=$db->query($query_insert) or die("0.5 insert for update maintenance ".$lang["update"][90].$db->error());
	}
// TOADD BEFORE RELEASE
	$query_drop =  "ALTER TABLE `$table` DROP `maintenance`";
	$result_drop=$db->query($query_drop) or die("0.5 drop for update maintenance ".$lang["update"][90].$db->error());

}

function updateWarrantyInfos($table,$type){
	$db=new DB;
	$elements=array();
	$query="SELECT ID,achat_date,date_fin_garantie from $table ORDER BY achat_date,date_fin_garantie";
	$result=$db->query($query) or die("0.5 select for update warranty ".$lang["update"][90].$db->error());
	while ($data=$db->fetch_array($result)){
		if (($data['achat_date']!="0000-00-00"&&!empty($data['achat_date']))||($data['date_fin_garantie']!="0000-00-00"&&!empty($data['date_fin_garantie']))){
			$IDitem=$data['ID'];
			if ($data['achat_date']=="0000-00-00"&&!empty($data['achat_date'])) $achat_date=date("Y-m-d");
			else $achat_date=$data['achat_date'];
			$duration=0;
			if ($data['date_fin_garantie']!="0000-00-00"&&!empty($data['date_fin_garantie']))
				$duration=round(date_diff($achat_date,$data['date_fin_garantie']),2);
			$query_insert="INSERT INTO glpi_infocoms (device_type,FK_device,buy_date,warranty_duration) VALUES ('$type','$IDitem','".$achat_date."','$duration')";
			$result_insert=$db->query($query_insert) or die("0.5 insert for update warranty ".$lang["update"][90].$db->error());
		}
	}
// TOADD BEFORE RELEASE
	$query_drop =  "ALTER TABLE `$table` DROP `achat_date`";
	$result_drop=$db->query($query_drop) or die("0.5 drop1 for update warranty ".$lang["update"][90].$db->error());
	$query_drop =  "ALTER TABLE `$table` DROP `date_fin_garantie`";
	$result_drop=$db->query($query_drop) or die("0.5 drop2 for update warranty ".$lang["update"][90].$db->error());

}
function isMaintenanceUsed(){
	$db = new DB;
	$tables=array("glpi_computers","glpi_printers","glpi_monitors","glpi_peripherals","glpi_networking");
	foreach ($tables as $key => $table){
		$query="SELECT ID from $table WHERE maintenance='1';";
		$result=$db->query($query) or die("0.5 find for update maintenance ".$lang["update"][90].$db->error());
		if ($db->numrows($result)>0) return true;
	}
	return false;

}

function dropMaintenanceField(){
	$db = new DB;
	$tables=array("glpi_computers","glpi_printers","glpi_monitors","glpi_peripherals","glpi_networking");
	foreach ($tables as $key => $table){
		$query="ALTER TABLE `$table` DROP `maintenance`";
		$result=$db->query($query) or die("0.5 alter for update maintenance ".$lang["update"][90].$db->error());
	}
}


// Update Warranty Infos
updateWarrantyInfos("glpi_computers",COMPUTER_TYPE);
updateWarrantyInfos("glpi_printers",PRINTER_TYPE);
updateWarrantyInfos("glpi_networking",NETWORKING_TYPE);
updateWarrantyInfos("glpi_monitors",MONITOR_TYPE);
updateWarrantyInfos("glpi_peripherals",PERIPHERAL_TYPE);

// Update Maintenance Infos
if (isMaintenanceUsed()){

	$query="INSERT INTO `glpi_contracts` VALUES (1, 'Maintenance', '', '0', 5, '2005-01-01', 120, 0, 0, 0, 0, '', '', 'N', '00:00:00', '00:00:00', '00:00:00', '00:00:00', 'N', '00:00:00', '00:00:00', 'N');";
	$result=$db->query($query) or die("0.5 insert_init for update maintenace ".$lang["update"][90].$db->error());

	if ($result){
		$query="SELECT ID FROM glpi_contracts;";
		$result=$db->query($query) or die("0.5 select_init for update maintenace ".$lang["update"][90].$db->error());
		if ($result){
			$data=$db->fetch_array($result);
			$IDcontract=$data["ID"];
			updateMaintenanceInfos("glpi_computers",COMPUTER_TYPE,$IDcontract);
			updateMaintenanceInfos("glpi_printers",PRINTER_TYPE,$IDcontract);
			updateMaintenanceInfos("glpi_networking",NETWORKING_TYPE,$IDcontract);
			updateMaintenanceInfos("glpi_monitors",MONITOR_TYPE,$IDcontract);
			updateMaintenanceInfos("glpi_peripherals",PERIPHERAL_TYPE,$IDcontract);
		}
	}
} else dropMaintenanceField();

}

// Merge de l'OS et de la version
if(FieldExists("glpi_computers","osver")) {
	// Récupération des couples existants
	$query="SELECT DISTINCT glpi_computers.os AS ID , glpi_computers.osver AS VERS, glpi_dropdown_os.name as NAME FROM glpi_computers 
		LEFT JOIN glpi_dropdown_os ON glpi_dropdown_os.ID=glpi_computers.os ORDER BY glpi_computers.os, glpi_computers.osver";
	$result=$db->query($query) or die("0.5 select for update OS ".$lang["update"][90].$db->error());
	$valeur=array();
	$curros=-1;
	$currvers="-------------------------";
	while ($data=$db->fetch_array($result)){
		// Nouvel OS -> update de l'element de dropdown
		if ($data["ID"]!=$curros){
			$curros=$data["ID"];
			
			if (!empty($data["VERS"])){
				$query_update="UPDATE glpi_dropdown_os SET name='".$data["NAME"]." - ".$data["VERS"]."' WHERE ID='".$data["ID"]."'";
				$db->query($query_update) or die("0.5 update for update OS ".$lang["update"][90].$db->error());
			}
		
		} else { // OS deja mis a jour -> creation d'un nouvel OS et mise a jour des elements
		$newname=$data["NAME"]." - ".$data["VERS"];
		$query_insert="INSERT INTO glpi_dropdown_os (name) VALUES ('$newname');";
		$db->query($query_insert) or die("0.5 insert for update OS ".$lang["update"][90].$db->error());
		$query_select="SELECT ID from  glpi_dropdown_os WHERE name = '$newname';";
		$res=$db->query($query_select) or die("0.5 select for update OS ".$lang["update"][90].$db->error());
		if ($db->numrows($res)==1){
			$query_update="UPDATE glpi_computers SET os='".$db->result($res,0,"ID")."' WHERE os='".$data["ID"]."' AND osver='".$data["VERS"]."'";
			$db->query($query_update) or die("0.5 update2 for update OS ".$lang["update"][90].$db->error());
		}
		
		}
	}
	$query_alter= "ALTER TABLE `glpi_computers` DROP `osver` ";
	$db->query($query_alter) or die("0.5 alter for update OS ".$lang["update"][90].$db->error());
}

// Ajout Fabriquant computer
if(!FieldExists("glpi_computers","FK_glpi_enterprise")) {

	$query = "ALTER TABLE `glpi_computers` ADD `FK_glpi_enterprise` INT( 11 ) DEFAULT '0' NOT NULL ;";
	$db->query($query) or die("0.5 add field manufacturer ".$lang["update"][90].$db->error());
	
	
	$query="ALTER TABLE `glpi_computers` ADD INDEX ( `FK_glpi_enterprise` )" ;
	$db->query($query) or die("0.5 alter field manufacturer ".$lang["update"][90].$db->error());

}

// Ajout Fabriquant printer
if(!FieldExists("glpi_printers","FK_glpi_enterprise")) {

	$query = "ALTER TABLE `glpi_printers` ADD `FK_glpi_enterprise` INT( 11 ) DEFAULT '0' NOT NULL ;";
	$db->query($query) or die("0.5 add field manufacturer ".$lang["update"][90].$db->error());
	
	$query="ALTER TABLE `glpi_printers` ADD INDEX ( `FK_glpi_enterprise` )" ;
	$db->query($query) or die("0.5 alter field manufacturer ".$lang["update"][90].$db->error());


}

// Ajout Fabriquant networking
if(!FieldExists("glpi_networking","FK_glpi_enterprise")) {

	$query = "ALTER TABLE `glpi_networking` ADD `FK_glpi_enterprise` INT( 11 ) DEFAULT '0' NOT NULL ;";
	$db->query($query) or die("0.5 add field manufacturer ".$lang["update"][90].$db->error());
	
	$query="ALTER TABLE `glpi_networking` ADD INDEX ( `FK_glpi_enterprise` )" ;
	$db->query($query) or die("0.5 alter field manufacturer ".$lang["update"][90].$db->error());


}

// Ajout Fabriquant monitor
if(!FieldExists("glpi_monitors","FK_glpi_enterprise")) {

	$query = "ALTER TABLE `glpi_monitors` ADD `FK_glpi_enterprise` INT( 11 ) DEFAULT '0' NOT NULL ;";
	$db->query($query) or die("0.5 add field manufacturer ".$lang["update"][90].$db->error());
	
	$query="ALTER TABLE `glpi_monitors` ADD INDEX ( `FK_glpi_enterprise` )" ;
	$db->query($query) or die("0.5 alter field manufacturer ".$lang["update"][90].$db->error());


}

// Ajout Fabriquant software
if(!FieldExists("glpi_software","FK_glpi_enterprise")) {

	$query = "ALTER TABLE `glpi_software` ADD `FK_glpi_enterprise` INT( 11 ) DEFAULT '0' NOT NULL ;";
	$db->query($query) or die("0.5 add field manufacturer ".$lang["update"][90].$db->error());
	
	$query="ALTER TABLE `glpi_software` ADD INDEX ( `FK_glpi_enterprise` )" ;
	$db->query($query) or die("0.5 alter field manufacturer ".$lang["update"][90].$db->error());


}

// Ajout Fabriquant peripheral
if(!FieldExists("glpi_peripherals","FK_glpi_enterprise")) {

	$query = "ALTER TABLE `glpi_peripherals` ADD `FK_glpi_enterprise` INT( 11 ) DEFAULT '0' NOT NULL ;";
	$db->query($query) or die("0.5 add field manufacturer ".$lang["update"][90].$db->error());
	
	$query="ALTER TABLE `glpi_peripherals` ADD INDEX ( `FK_glpi_enterprise` )" ;
	$db->query($query) or die("0.5 alter field manufacturer ".$lang["update"][90].$db->error());
	

}

// Ajout deleted peripheral
if(!FieldExists("glpi_peripherals","deleted")) {

	$query = "ALTER TABLE `glpi_peripherals` ADD `deleted` ENUM( 'Y', 'N' ) DEFAULT 'N' NOT NULL ;";
	$db->query($query) or die("0.5 add field deleted ".$lang["update"][90].$db->error());
	
	$query="ALTER TABLE `glpi_peripherals` ADD INDEX ( `deleted` )" ;
	$db->query($query) or die("0.5 alter field deleted ".$lang["update"][90].$db->error());
	

}

// Ajout deleted software
if(!FieldExists("glpi_software","deleted")) {

	$query = "ALTER TABLE `glpi_software` ADD `deleted` ENUM( 'Y', 'N' ) DEFAULT 'N' NOT NULL ;";
	$db->query($query) or die("0.5 add field deleted ".$lang["update"][90].$db->error());

	$query="ALTER TABLE `glpi_software` ADD INDEX ( `deleted` )" ;
	$db->query($query) or die("0.5 alter field deleted ".$lang["update"][90].$db->error());
	

}

// Ajout deleted monitor
if(!FieldExists("glpi_monitors","deleted")) {

	$query = "ALTER TABLE `glpi_monitors` ADD `deleted` ENUM( 'Y', 'N' ) DEFAULT 'N' NOT NULL ;";
	$db->query($query) or die("0.5 add field deleted ".$lang["update"][90].$db->error());
	
	$query="ALTER TABLE `glpi_monitors` ADD INDEX ( `deleted` )" ;
	$db->query($query) or die("0.5 alter field deleted ".$lang["update"][90].$db->error());
	

}

// Ajout deleted networking
if(!FieldExists("glpi_networking","deleted")) {

	$query = "ALTER TABLE `glpi_networking` ADD `deleted` ENUM( 'Y', 'N' ) DEFAULT 'N' NOT NULL ;";
	$db->query($query) or die("0.5 add field deleted ".$lang["update"][90].$db->error());

	$query="ALTER TABLE `glpi_networking` ADD INDEX ( `deleted` )" ;
	$db->query($query) or die("0.5 alter field deleted ".$lang["update"][90].$db->error());
	

}
// Ajout deleted printer
if(!FieldExists("glpi_printers","deleted")) {

	$query = "ALTER TABLE `glpi_printers` ADD `deleted` ENUM( 'Y', 'N' ) DEFAULT 'N' NOT NULL ;";
	$db->query($query) or die("0.5 add field deleted ".$lang["update"][90].$db->error());
	
	$query="ALTER TABLE `glpi_printers` ADD INDEX ( `deleted` )" ;
	$db->query($query) or die("0.5 alter field deleted ".$lang["update"][90].$db->error());
	

}
// Ajout deleted computer
if(!FieldExists("glpi_computers","deleted")) {

	$query = "ALTER TABLE `glpi_computers` ADD `deleted` ENUM( 'Y', 'N' ) DEFAULT 'N' NOT NULL ;";
	$db->query($query) or die("0.5 add field deleted ".$lang["update"][90].$db->error());
	
	$query="ALTER TABLE `glpi_computers` ADD INDEX ( `deleted` )" ;
	$db->query($query) or die("0.5 alter field deleted ".$lang["update"][90].$db->error());
	

}

// Ajout template peripheral
if(!FieldExists("glpi_peripherals","is_template")) {

	$query = "ALTER TABLE `glpi_peripherals` ADD `is_template` ENUM( '0', '1' ) DEFAULT '0' NOT NULL , ADD `tplname` VARCHAR( 255 ) ;";
	$db->query($query) or die("0.5 add field deleted ".$lang["update"][90].$db->error());

	$query="INSERT INTO glpi_peripherals (is_template,tplname) VALUES ('1','Blank Template')";
	$db->query($query) or die("0.5 add blank template ".$lang["update"][90].$db->error());	

	$query="ALTER TABLE `glpi_peripherals` ADD INDEX ( `is_template` )" ;
	$db->query($query) or die("0.5 alter field is_template ".$lang["update"][90].$db->error());
	
	
}

// Ajout template software
if(!FieldExists("glpi_software","is_template")) {

	$query = "ALTER TABLE `glpi_software` ADD `is_template` ENUM( '0', '1' ) DEFAULT '0' NOT NULL , ADD `tplname` VARCHAR( 255 ) ;";
	$db->query($query) or die("0.5 add field deleted ".$lang["update"][90].$db->error());

	$query="INSERT INTO glpi_software (is_template,tplname) VALUES ('1','Blank Template')";
	$db->query($query) or die("0.5 add blank template ".$lang["update"][90].$db->error());	
	
	$query="ALTER TABLE `glpi_software` ADD INDEX ( `is_template` )" ;
	$db->query($query) or die("0.5 alter field is_template ".$lang["update"][90].$db->error());

}

// Ajout template monitor
if(!FieldExists("glpi_monitors","is_template")) {

	$query = "ALTER TABLE `glpi_monitors` ADD `is_template` ENUM( '0', '1' ) DEFAULT '0' NOT NULL , ADD `tplname` VARCHAR( 255 ) ;";
	$db->query($query) or die("0.5 add field deleted ".$lang["update"][90].$db->error());

	$query="INSERT INTO glpi_monitors (is_template,tplname) VALUES ('1','Blank Template')";
	$db->query($query) or die("0.5 add blank template ".$lang["update"][90].$db->error());	
	
	$query="ALTER TABLE `glpi_monitors` ADD INDEX ( `is_template` )" ;
	$db->query($query) or die("0.5 alter field is_template ".$lang["update"][90].$db->error());
	

}

if(!isIndex("glpi_computers", "is_template")) {
	$query = "ALTER TABLE `glpi_computers` ADD INDEX ( `is_template` ) ";
	$db->query($query) or die("5 ".$lang["update"][90].$db->error());
}

// Ajout template networking
if(!FieldExists("glpi_networking","is_template")) {

	$query = "ALTER TABLE `glpi_networking` ADD `is_template` ENUM( '0', '1' ) DEFAULT '0' NOT NULL , ADD `tplname` VARCHAR( 255 ) ;";
	$db->query($query) or die("0.5 add field deleted ".$lang["update"][90].$db->error());

	$query="INSERT INTO glpi_networking (is_template,tplname) VALUES ('1','Blank Template')";
	$db->query($query) or die("0.5 add blank template ".$lang["update"][90].$db->error());	

	$query="ALTER TABLE `glpi_networking` ADD INDEX ( `is_template` )" ;
	$db->query($query) or die("0.5 alter field is_template ".$lang["update"][90].$db->error());
	

}
// Ajout template printer
if(!FieldExists("glpi_printers","is_template")) {

	$query = "ALTER TABLE `glpi_printers` ADD `is_template` ENUM( '0', '1' ) DEFAULT '0' NOT NULL , ADD `tplname` VARCHAR( 255 ) ;";
	$db->query($query) or die("0.5 add field deleted ".$lang["update"][90].$db->error());
	
		$query="INSERT INTO glpi_printers (is_template,tplname) VALUES ('1','Blank Template')";
	$db->query($query) or die("0.5 add blank template ".$lang["update"][90].$db->error());	
	
	$query="ALTER TABLE `glpi_printers` ADD INDEX ( `is_template` )" ;
	$db->query($query) or die("0.5 alter field is_template ".$lang["update"][90].$db->error());
	


}
// Ajout date_mod
if(!FieldExists("glpi_printers","date_mod")) {
	$query = "ALTER TABLE `glpi_printers` ADD `date_mod` DATETIME DEFAULT NULL";
	$db->query($query) or die("Error : ".$query." ".mysql_error());

	$query="ALTER TABLE `glpi_printers` ADD INDEX ( `date_mod` )" ;
	$db->query($query) or die("0.5 alter field date_mod ".$lang["update"][90].$db->error());
		
}

if(!isIndex("glpi_computers", "date_mod")) {
	$query = "ALTER TABLE `glpi_computers` ADD INDEX ( `date_mod` ) ";
	$db->query($query) or die("5 ".$lang["update"][90].$db->error());
}

// Ajout date_mod
if(!FieldExists("glpi_monitors","date_mod")) {
	$query = "ALTER TABLE `glpi_monitors` ADD `date_mod` DATETIME DEFAULT NULL";
	$db->query($query) or die("Error : ".$query." ".mysql_error());
	
	$query="ALTER TABLE `glpi_monitors` ADD INDEX ( `date_mod` )" ;
	$db->query($query) or die("0.5 alter field date_mod ".$lang["update"][90].$db->error());
}

// Ajout date_mod
if(!FieldExists("glpi_software","date_mod")) {
	$query = "ALTER TABLE `glpi_software` ADD `date_mod` DATETIME DEFAULT NULL";
	$db->query($query) or die("Error : ".$query." ".mysql_error());
	
	$query="ALTER TABLE `glpi_software` ADD INDEX ( `date_mod` )" ;
	$db->query($query) or die("0.5 alter field date_mod ".$lang["update"][90].$db->error());
}

// Ajout date_mod
if(!FieldExists("glpi_networking","date_mod")) {
	$query = "ALTER TABLE `glpi_networking` ADD `date_mod` DATETIME DEFAULT NULL";
	$db->query($query) or die("Error : ".$query." ".mysql_error());
	
	$query="ALTER TABLE `glpi_networking` ADD INDEX ( `date_mod` )" ;
	$db->query($query) or die("0.5 alter field date_mod ".$lang["update"][90].$db->error());
}

// Ajout tech_num
if(!FieldExists("glpi_computers","tech_num")) {
	$query = "ALTER TABLE `glpi_computers` ADD `tech_num` int(11) NOT NULL default '0' AFTER `contact_num`";
	$db->query($query) or die("Error : ".$query." ".mysql_error());
	
	$query="ALTER TABLE `glpi_computers` ADD INDEX ( `tech_num` )" ;
	$db->query($query) or die("0.5 alter field tech_num ".$lang["update"][90].$db->error());
}
// Ajout tech_num
if(!FieldExists("glpi_networking","tech_num")) {
	$query = "ALTER TABLE `glpi_networking` ADD `tech_num` int(11) NOT NULL default '0' AFTER `contact_num`";
	$db->query($query) or die("Error : ".$query." ".mysql_error());
	
	$query="ALTER TABLE `glpi_networking` ADD INDEX ( `tech_num` )" ;
	$db->query($query) or die("0.5 alter field tech_num ".$lang["update"][90].$db->error());
}
// Ajout tech_num
if(!FieldExists("glpi_printers","tech_num")) {
	$query = "ALTER TABLE `glpi_printers` ADD `tech_num` int(11) NOT NULL default '0' AFTER `contact_num`";
	$db->query($query) or die("Error : ".$query." ".mysql_error());
	
	$query="ALTER TABLE `glpi_printers` ADD INDEX ( `tech_num` )" ;
	$db->query($query) or die("0.5 alter field tech_num ".$lang["update"][90].$db->error());
}

// Ajout tech_num
if(!FieldExists("glpi_monitors","tech_num")) {
	$query = "ALTER TABLE `glpi_monitors` ADD `tech_num` int(11) NOT NULL default '0' AFTER `contact_num`";
	$db->query($query) or die("Error : ".$query." ".mysql_error());
	
	$query="ALTER TABLE `glpi_monitors` ADD INDEX ( `tech_num` )" ;
	$db->query($query) or die("0.5 alter field tech_num ".$lang["update"][90].$db->error());
}

// Ajout tech_num
if(!FieldExists("glpi_software","tech_num")) {
	$query = "ALTER TABLE `glpi_software` ADD `tech_num` int(11) NOT NULL default '0' AFTER `location`";
	$db->query($query) or die("Error : ".$query." ".mysql_error());
	
	$query="ALTER TABLE `glpi_software` ADD INDEX ( `tech_num` )" ;
	$db->query($query) or die("0.5 alter field tech_num ".$lang["update"][90].$db->error());
}

// Ajout tech_num
if(!FieldExists("glpi_peripherals","tech_num")) {
	$query = "ALTER TABLE `glpi_peripherals` ADD `tech_num` int(11) NOT NULL default '0' AFTER `contact_num`";
	$db->query($query) or die("Error : ".$query." ".mysql_error());
	
	$query="ALTER TABLE `glpi_peripherals` ADD INDEX ( `tech_num` )" ;
	$db->query($query) or die("0.5 alter field tech_num ".$lang["update"][90].$db->error());
}

// Ajout tech_num
if(!FieldExists("glpi_software","tech_num")) {
	$query = "ALTER TABLE `glpi_software` ADD `tech_num` int(11) NOT NULL default '0'";
	$db->query($query) or die("Error : ".$query." ".mysql_error());
	
	$query="ALTER TABLE `glpi_software` ADD INDEX ( `tech_num` )" ;
	$db->query($query) or die("0.5 alter field tech_num ".$lang["update"][90].$db->error());
}

// Ajout tech_num
if(!TableExists("glpi_type_docs")) {
	
$query = "CREATE TABLE glpi_type_docs (
		  ID int(11) NOT NULL auto_increment,
		  name varchar(255) NOT NULL default '',
		  ext varchar(10) NOT NULL default '',
		  icon varchar(255) NOT NULL default '',
		  mime varchar(100) NOT NULL default '',
		  upload enum('Y','N') NOT NULL default 'Y',
		  date_mod datetime default NULL,
		  PRIMARY KEY  (ID),
		  UNIQUE KEY extension (ext),
		  KEY (upload)
		) TYPE=MyISAM;";
		
$db->query($query) or die("Error creating table typedoc ".$query." ".mysql_error());



$query = "INSERT INTO glpi_type_docs (ID, name, ext, icon, mime, upload, date_mod) VALUES  (1, 'JPEG', 'jpg', 'jpg-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (2, 'PNG', 'png', 'png-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (3, 'GIF', 'gif', 'gif-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (4, 'BMP', 'bmp', 'bmp-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (5, 'Photoshop', 'psd', 'psd-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (6, 'TIFF', 'tif', 'tif-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (7, 'AIFF', 'aiff', 'aiff-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (8, 'Windows Media', 'asf', 'asf-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (9, 'Windows Media', 'avi', 'avi-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (44, 'C source', 'c', '', '', 'Y', '2004-12-13 19:47:22'),
 (27, 'RealAudio', 'rm', 'rm-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (16, 'Midi', 'mid', 'mid-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (17, 'QuickTime', 'mov', 'mov-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (18, 'MP3', 'mp3', 'mp3-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (19, 'MPEG', 'mpg', 'mpg-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (20, 'Ogg Vorbis', 'ogg', 'ogg-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (24, 'QuickTime', 'qt', 'qt-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (10, 'BZip', 'bz2', 'bz2-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (25, 'RealAudio', 'ra', 'ra-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (26, 'RealAudio', 'ram', 'ram-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (11, 'Word', 'doc', 'doc-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (12, 'DjVu', 'djvu', '', '', 'Y', '2004-12-13 19:47:21'),
 (42, 'MNG', 'mng', '', '', 'Y', '2004-12-13 19:47:22'),
 (13, 'PostScript', 'eps', 'ps-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (14, 'GZ', 'gz', 'gz-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (37, 'WAV', 'wav', 'wav-dist.png', '', 'Y', '2004-12-13 19:47:22'),
 (15, 'HTML', 'html', 'html-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (34, 'Flash', 'swf', '', '', 'Y', '2004-12-13 19:47:22'),
 (21, 'PDF', 'pdf', 'pdf-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (22, 'PowerPoint', 'ppt', 'ppt-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (23, 'PostScript', 'ps', 'ps-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (40, 'Windows Media', 'wmv', '', '', 'Y', '2004-12-13 19:47:22'),
 (28, 'RTF', 'rtf', 'rtf-dist.png', '', 'Y', '2004-12-13 19:47:21'),
 (29, 'StarOffice', 'sdd', 'sdd-dist.png', '', 'Y', '2004-12-13 19:47:22'),
 (30, 'StarOffice', 'sdw', 'sdw-dist.png', '', 'Y', '2004-12-13 19:47:22'),
 (31, 'Stuffit', 'sit', 'sit-dist.png', '', 'Y', '2004-12-13 19:47:22'),
 (43, 'Adobe Illustrator', 'ai', 'ai-dist.png', '', 'Y', '2004-12-13 19:47:22'),
 (32, 'OpenOffice Impress', 'sxi', 'sxi-dist.png', '', 'Y', '2004-12-13 19:47:22'),
 (33, 'OpenOffice', 'sxw', 'sxw-dist.png', '', 'Y', '2004-12-13 19:47:22'),
 (46, 'DVI', 'dvi', 'dvi-dist.png', '', 'Y', '2004-12-13 19:47:22'),
 (35, 'TGZ', 'tgz', 'tgz-dist.png', '', 'Y', '2004-12-13 19:47:22'),
 (36, 'texte', 'txt', 'txt-dist.png', '', 'Y', '2004-12-13 19:47:22'),
 (49, 'RedHat/Mandrake/SuSE', 'rpm', 'rpm-dist.png', '', 'Y', '2004-12-13 19:47:22'),
 (38, 'Excel', 'xls', 'xls-dist.png', '', 'Y', '2004-12-13 19:47:22'),
 (39, 'XML', 'xml', 'xml-dist.png', '', 'Y', '2004-12-13 19:47:22'),
 (41, 'Zip', 'zip', 'zip-dist.png', '', 'Y', '2004-12-13 19:47:22'),
 (45, 'Debian', 'deb', 'deb-dist.png', '', 'Y', '2004-12-13 19:47:22'),
 (47, 'C header', 'h', '', '', 'Y', '2004-12-13 19:47:22'),
 (48, 'Pascal', 'pas', '', '', 'Y', '2004-12-13 19:47:22'),
 (50, 'OpenOffice Calc', 'sxc', 'sxc-dist.png', '', 'Y', '2004-12-13 19:47:22'),
 (51, 'LaTeX', 'tex', 'tex-dist.png', '', 'Y', '2004-12-13 19:47:22'),
 (52, 'GIMP multi-layer', 'xcf', 'xcf-dist.png', '', 'Y', '2004-12-13 19:47:22'),
 (53, 'JPEG', 'jpeg', 'jpg-dist.png', '', 'Y', '2005-03-07 22:23:17');";






$db->query($query) or die("Error inserting elements in table typedoc ".$query." ".mysql_error());
	
}

if(!TableExists("glpi_docs")) {
	
$query = "CREATE TABLE glpi_docs (
  ID int(11) NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  filename varchar(255) NOT NULL default '',
  rubrique int(11) NOT NULL default '0',
  mime varchar(30) NOT NULL default '',
  date_mod datetime NOT NULL default '0000-00-00 00:00:00',
  comment text NOT NULL,
  deleted enum('Y','N') NOT NULL default 'N',
  PRIMARY KEY  (ID),
  KEY rubrique (rubrique),
  KEY deleted (deleted),
  KEY date_mod (date_mod)
) TYPE=MyISAM;";

$db->query($query) or die("Error creating table docs ".$query." ".mysql_error());
}

if(!TableExists("glpi_doc_device")) {
	
$query = "CREATE TABLE glpi_doc_device (
  ID int(11) NOT NULL auto_increment,
  FK_doc int(11) NOT NULL default '0',
  FK_device int(11) NOT NULL default '0',
  device_type tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (ID),
  UNIQUE KEY FK_doc (FK_doc,FK_device,device_type),
  KEY FK_doc_2 (FK_doc),
  KEY FK_device (FK_device,device_type)
) TYPE=MyISAM;";

$db->query($query) or die("Error creating table docs ".$query." ".mysql_error());
}

if(!TableExists("glpi_dropdown_rubdocs")) {
	
$query = "CREATE TABLE glpi_dropdown_rubdocs (
  ID int(11) NOT NULL auto_increment,
  name varchar(255) default NULL,
  PRIMARY KEY  (ID)
) TYPE=MyISAM;";
$db->query($query) or die("Error creating table docs ".$query." ".mysql_error());
}

if(!isIndex("glpi_contacts", "deleted")) {
$query = "ALTER TABLE `glpi_contacts` ADD INDEX `deleted` ( `deleted` ) ";
$db->query($query) or die("0.5 alter field deleted".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_contacts", "type")) {
$query = "ALTER TABLE `glpi_contacts` ADD INDEX `type` ( `type` ) ";
$db->query($query) or die("0.5 alter field type ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_event_log", "itemtype")) {
$query = "ALTER TABLE `glpi_event_log` ADD INDEX ( `itemtype` ) ";
$db->query($query) or die("0.5 alter field itemtype ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_followups", "date")) {
$query = "ALTER TABLE `glpi_followups` ADD INDEX ( `date` ) ";
$db->query($query) or die("0.5 alter field date ".$lang["update"][90].$db->error());
}

if(!isIndex("glpi_tracking", "category")) {
$query = "ALTER TABLE `glpi_tracking` ADD INDEX ( `category` ) ";
$db->query($query) or die("0.5 alter field date ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_config","date_fiscale")) {
	$query = "ALTER TABLE `glpi_config` ADD `date_fiscale` date NOT NULL default '2005-12-31'";
	$db->query($query) or die("48 ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_networking","ifmac")) {
	$query = "ALTER TABLE `glpi_networking` ADD `ifmac` char(30) NOT NULL default ''";
	$db->query($query) or die("48 ".$lang["update"][90].$db->error());
}

if(!FieldExists("glpi_networking","ifaddr")) {
	$query = "ALTER TABLE `glpi_networking` ADD `ifaddr` char(30) NOT NULL default ''";
	$db->query($query) or die("48 ".$lang["update"][90].$db->error());
}

if(!TableExists("glpi_repair_item")) {

	$query = "CREATE TABLE glpi_repair_item (ID int(11) NOT NULL auto_increment,device_type tinyint(4) NOT NULL default '0', id_device int(11) NOT NULL default '0', PRIMARY KEY  (ID), KEY device_type (device_type), KEY device_type_2 (device_type,id_device));";

	$db->query($query) or die("4201 ".$lang["update"][90].$db->error());
}


// Update version number
$query="UPDATE glpi_config set version='0.5' WHERE ID='1'";
	$db->query($query) or die("0.5 update config version ".$lang["update"][90].$db->error());

optimize_tables();

return $ret;
}



function showFormSu() {
	include ("_relpos.php");
	global $lang;
	echo "<div align='center'>";
	echo "<h3>".$lang["update"][97]."</h3>";
	echo "<p>".$lang["update"][98]."</p>";
	echo "<p>".$lang["update"][99]."</p>";
	echo "<form action=\"".$_SERVER["PHP_SELF"]."\" method=\"post\">";
	echo "<p>".$lang["update"][100]." <input type=\"text\" name=\"login_su\" /></p>";
	echo "<p>".$lang["update"][101]." <input type=\"password\" name=\"pass_su1\" /></p>";
	echo "<p>".$lang["update"][102]." <input type=\"password\" name=\"pass_su2\" /></p>";
	echo "<input type=\"submit\" class='submit' name=\"ajout_su\" value=\"".$lang["install"][25] ."\" />";
	echo "</div>";
}

//Debut du script
	
	if(!isset($_SESSION)) session_start();
	if(empty($_SESSION["dict"])) $_SESSION["dict"] = "french";
	global $lang;
	loadLang($_SESSION["dict"]);
	include ("_relpos.php");
        echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">";
        echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"fr\" lang=\"fr\">";
        echo "<head>";
        echo " <meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\" />";
        echo "<meta http-equiv=\"Content-Script-Type\" content=\"text/javascript\" /> ";
        echo "<meta http-equiv=\"Content-Style-Type\" content=\"text/css\" /> ";
        echo "<meta http-equiv=\"Content-Language\" content=\"fr\" /> ";
        echo "<meta name=\"generator\" content=\"\" />";
        echo "<meta name=\"DC.Language\" content=\"fr\" scheme=\"RFC1766\" />";
        echo "<title>Setup GLPI</title>";
       
        echo "<style type=\"text/css\">";
        echo "<!--

        /*  ... Definition des styles ... */

        body {
        background-color:#C5DAC8;
        color:#000000; }
        
       .principal {
        background-color: #ffffff;
        font-family: Verdana;font-size:12px;
        text-align: justify ; 
        -moz-border-radius: 4px;
	border: 1px solid #FFC65D;
         margin: 40px; 
         padding: 40px 40px 10px 40px;
       }

       table {
       text-align:center;
       border: 0;
       margin: 20px;
       margin-left: auto;
       margin-right: auto;
       width: 90%;}

       .red { color:red;}
       .green {color:green;}

  th
  {  
    font-size: 12px;
    font-weight: bold;
   /* background-color: #FFC65D;*/
    background-color: #fccc6f;
	vertical-align:bottom;
}

.tab_cadre{
 -moz-border-radius: 4px;
  border: 1px solid #cccccc;
}

.tab_bg_1 {
background-color: #ccccc7;

}
       
       h2 {
        color:#FFC65D;
        text-align:center;}

       h3 {
        text-align:center;}

       h4 {
        text-align:center;
        text-decoration: underline;
        font-size: 16px;
        }

        input {border: 1px solid #ccc;}

        fieldset {
        padding: 20px;
          border: 1px dotted #ccc;
        font-size: 12px;
        font-weight:200;}

        .submit { text-align:center;}
       
        input.submit {
        border:1px solid #000000;
        background-color:#eeeeee;
        }
        
        input.submit:hover {
        border:1px solid #cccccc;
       background-color:#ffffff;
        }

	.button {
        font-weight:200;
	color:#000000;
	padding:5px;
	text-decoration:none;
	border:1px solid #009966;
        background-color:#eeeeee;
        }

        .button:hover{
          font-weight:200;
	  color:#000000;
	 padding:5px;
	text-decoration:none;
	border:1px solid #009966;
       background-color:#ffffff;
        }
	
        -->  ";
        echo "</style>";
         echo "</head>";
        echo "<body>";
	echo "<div class=\"principal\">";
        echo "<h2>GLPI SETUP</h2>";
	echo "<br/><h3>Update</h3>";

// step 1    avec bouton de confirmation

if(empty($_POST["continuer"]) && empty($_POST["ajout_su"]) && empty($_POST["from_update"])) {
	$db = new DB;
	if(empty($from_install)&&!isset($_POST["from_update"])) {
		echo "<div align='center'>";
		echo "<h3><span class='red'>".$lang["update"][105]."</span>";
		echo "<p class='submit'> <a href=\"index.php\"><span class='button'>".$lang["update"][106]."</span></a></p>";
		echo "</div>";
	}
	else {
		echo "<div align='center'>";
		echo "<h3><span class='red'>".$lang["update"][91]."</span>".$lang["update"][92]. $db->dbdefault ."</h3>";
	
		echo "<form action=\"update.php\" method=\"post\">";
		echo "<input type=\"submit\" class='submit' name=\"continuer\" value=\"".$lang["install"][25] ."\" />";
		echo "</div></form>";
	}
}
// Step 2  
elseif(empty($_POST["ajout_su"])) {
	if(test_connect()) {
		echo "<h3>".$lang["update"][93]."</h3>";
		if (!isset($_POST["update_location"]))
			if(!TableExists("glpi_config")) {
				updateDbTo031();
				$tab = updateDbUpTo031();
				updaterootdoc();
			} else {
				$tab = updateDbUpTo031();
				updaterootdoc();
			}
		
		if(!superAdminExists()) {
			showFormSu();
		}
		else {
			echo "<div align='center'>";
			if(!empty($tab) && $tab["adminchange"]) {
				echo "<div align='center'> <h2>". $lang["update"][96] ."<h2></div>";
			}
			if (showLocationUpdateForm())
				showContentUpdateForm();
		}
	}
	else {
		echo "<h3> ";
		echo $lang["update"][95] ."</h3>";
        }
	echo "</div></body></html>";
}
elseif(!empty($_POST["ajout_su"])) {
	if(!empty($_POST["pass_su1"]) && !empty($_POST["login_su"]) && $_POST["pass_su1"] == $_POST["pass_su2"]) {
		$db = new DB;
		$query = "insert into glpi_users ( `ID` , `name` , `password` , `email` , `phone` , `type` , `realname` , `can_assign_job` , `location` ) VALUES ('', '".$login_su."', PASSWORD( '".$pass_su1."' ) , '', NULL , 'super-admin', '', 'yes', NULL)";
		$db->query($query) or die(" No SU ".$lang["update"][90].$db->error());
		echo "<div align='center'>";
		echo "<h3>".$lang["update"][104]."</h3>";
		echo "</div>";
		if (showLocationUpdateForm())
			showContentUpdateForm();
		echo "<p class='submit'> <a href=\"index.php\"><span class='button'>".$lang["install"][64]."</span></a></p>";
	}
	else {
		echo "<div align='center' color='red'>";
		echo "<h3>".$lang["update"][103]."</h3>";
		echo "</div>";
		showFormSu();
	}
}



?>
