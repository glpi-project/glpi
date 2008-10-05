<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

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


if (!defined('GLPI_ROOT'))
	define('GLPI_ROOT', '..');

include_once (GLPI_ROOT . "/config/define.php");

include_once (GLPI_ROOT . "/inc/timer.class.php");
include_once (GLPI_ROOT . "/inc/dbmysql.class.php");
include_once (GLPI_ROOT . "/inc/common.function.php");
include_once (GLPI_ROOT . "/inc/db.function.php");
include_once (GLPI_ROOT . "/inc/display.function.php");
include_once (GLPI_ROOT . "/inc/commondbtm.class.php");
include_once (GLPI_ROOT . "/inc/plugin.class.php");
include_once (GLPI_ROOT . "/config/based_config.php");
include_once (GLPI_CONFIG_DIR . "/config_db.php");

setGlpiSessionPath();

cleanCache();

// Init debug variable
$_SESSION['glpi_use_mode']=DEBUG_MODE;
$CFG_GLPI["use_errorlog"]=1;
$DB=new DB();


//Load language
if(!function_exists('loadLang')) {
	function loadLang($LANGuage) {
		if (isset($LANG)){ 
			unset($LANG);
		}
		global $LANG;

		$file = GLPI_ROOT ."/locales/".$LANGuage.".php";
		if (file_exists($file)){
			include($file);
		} else {
			include(GLPI_ROOT ."/locales/en_GB.php");
		}
	}
}

/* ----------------------------------------------------------------- */

/*---------------------------------------------------------------------*/
/**
 * Display the form of content update (addslashes compatibility (V0.4))
 *
 *
 * @returns nothing (displays)
 */
function showContentUpdateForm() {

	global $LANG;
	echo "<div align='center'>";
	echo "<h3>".$LANG["update"][94]."</h3>";
	echo "<p>".$LANG["update"][107]."</p></div>";
	echo "<p class='submit'> <a href=\"update_content.php\"><span class='button'>".$LANG["install"][25]."</span></a>";
}


///// FONCTION POUR UPDATE LOCATION

function validate_new_location(){
	global $DB;
	$query=" DROP TABLE `glpi_dropdown_locations`";	
	$DB->query($query);
	$query=" ALTER TABLE `glpi_dropdown_locations_new` RENAME `glpi_dropdown_locations`";	
	$DB->query($query);
}

function display_new_locations(){
	global $DB;

	$MAX_LEVEL=10;

	$SELECT_ALL="";
	$FROM_ALL="";
	$ORDER_ALL="";
	$WHERE_ALL="";
	for ($i=1;$i<=$MAX_LEVEL;$i++){
		$SELECT_ALL.=" , location$i.name AS NAME$i , location$i.parentID AS PARENT$i ";
		$FROM_ALL.=" LEFT JOIN glpi_dropdown_locations_new AS location$i ON location".($i-1).".ID = location$i.parentID ";
		//$WHERE_ALL.=" AND location$i.level='$i' ";
		$ORDER_ALL.=" , NAME$i";

	}

	$query="select location0.name AS NAME0, location0.parentID AS PARENT0 $SELECT_ALL FROM glpi_dropdown_locations_new AS location0 $FROM_ALL  WHERE location0.parentID='0' $WHERE_ALL  ORDER BY NAME0 $ORDER_ALL";
	//echo $query;
	//echo "<hr>";
	$result=$DB->query($query);
	$data_old=array();
	echo "<table><tr>";
	for ($i=0;$i<=$MAX_LEVEL;$i++){
		echo "<th>$i</th><th>&nbsp;</th>";
	}
	echo "</tr>";

	while ($data =  $DB->fetch_array($result)){

		echo "<tr class=tab_bg_1>";
		for ($i=0;$i<=$MAX_LEVEL;$i++){
			if (!isset($data_old["NAME$i"])||($data_old["PARENT$i"]!=$data["PARENT$i"])||($data_old["NAME$i"]!=$data["NAME$i"])){
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
	$DB->free_result($result);
	echo "</table>";
}

function display_old_locations(){
	global $DB;
	$query="SELECT * from glpi_dropdown_locations order by name;";
	$result=$DB->query($query);

	while ($data =  $DB->fetch_array($result))
		echo "<b>".$data['name']."</b> - ";

	$DB->free_result($result);
}

function location_create_new($split_char,$add_first){

	global $DB;

	$query_auto_inc= "ALTER TABLE `glpi_dropdown_locations_new` CHANGE `ID` `ID` INT(11) NOT NULL";
	$result_auto_inc=$DB->query($query_auto_inc);

	$query="SELECT MAX(ID) AS MAX from glpi_dropdown_locations;";
	//echo $query."<br>";
	$result=$DB->query($query);
	$new_ID=$DB->result($result,0,"MAX");
	$new_ID++;



	$query="SELECT * from glpi_dropdown_locations;";
	$result=$DB->query($query);

	$query_clear_new="TRUNCATE TABLE `glpi_dropdown_locations_new`";
	//echo $query_clear_new."<br>";

	$result_clear_new=$DB->query($query_clear_new); 

	if (!empty($add_first)){
		$root_ID=$new_ID;
		$new_ID++;
		$query_insert="INSERT INTO glpi_dropdown_locations_new VALUES ('$root_ID','".addslashes($add_first)."',0,'')";

		$result_insert=$DB->query($query_insert);

	} else {
		$root_ID=0;
	}

	while ($data =  $DB->fetch_array($result)){

		if (!empty($split_char))
			$splitter=split($split_char,$data['name']);
		else $splitter=array($data['name']);

		$up_ID=$root_ID;

		for ($i=0;$i<count($splitter)-1;$i++){
			// Entr� existe deja ??
			$query_search="select ID from glpi_dropdown_locations_new WHERE name='".addslashes($splitter[$i])."'  AND parentID='".$up_ID."'";
			//				echo $query_search."<br>";
			$result_search=$DB->query($query_search);
			if ($DB->numrows($result_search)==1){	// Found
				$up_ID=$DB->result($result_search,0,"ID");
			} else { // Not FOUND -> INSERT
				$query_insert="INSERT INTO glpi_dropdown_locations_new VALUES ('$new_ID','".addslashes($splitter[$i])."','$up_ID','')";
				//					echo $query_insert."<br>";
				$result_insert=$DB->query($query_insert);
				$up_ID=$new_ID++;

			}
		}

		// Ajout du dernier
		$query_insert="INSERT INTO glpi_dropdown_locations_new VALUES ('".$data["ID"]."','".addslashes($splitter[count($splitter)-1])."','$up_ID','')";
		//			echo $query_insert."<br>";

		$result_insert=$DB->query($query_insert);

	}
	$DB->free_result($result);
	$query_auto_inc= "ALTER TABLE `glpi_dropdown_locations_new` CHANGE `ID` `ID` INT(11) NOT NULL AUTO_INCREMENT";
	$result_auto_inc=$DB->query($query_auto_inc);

}

///// FIN FONCTIONS POUR UPDATE LOCATION

function showLocationUpdateForm(){
	global $DB,$LANG;


	if (FieldExists("glpi_dropdown_locations", "parentID")) {
		updateTreeDropdown();
		return true;
	}

	if (!isset($_POST['root'])) $_POST['root']='';
	if (!isset($_POST['car_sep'])) $_POST['car_sep']='';

	if(!TableExists("glpi_dropdown_locations_new")) {
		$query = " CREATE TABLE `glpi_dropdown_locations_new` (
			`ID` INT NOT NULL auto_increment,
			`name` VARCHAR(255) NOT NULL ,
			`parentID` INT NOT NULL ,
			`comments` TEXT NULL ,
			PRIMARY KEY (`ID`),
			UNIQUE KEY (`name`,`parentID`), 
			KEY(`parentID`)) TYPE=MyISAM;";
		$DB->query($query) or die("LOCATION ".$DB->error());
	}

	if (!isset($_POST["validate_location"])){
		echo "<div align='center'>";
		echo "<h4>".$LANG["update"][130]."</h4>";
		echo "<p>".$LANG["update"][131]."</p>";
		echo "<p>".$LANG["update"][132]."<br>".$LANG["update"][133]."</p>";
		echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">";
		echo "<p>".$LANG["update"][134].": <input type=\"text\" name=\"car_sep\" value=\"".$_POST['car_sep']."\"></p>";
		echo "<p>".$LANG["update"][135].": <input type=\"text\" name=\"root\" value=\"".$_POST['root']."\"></p>";
		echo "<input type=\"submit\" class='submit' name=\"new_location\" value=\"".$LANG["buttons"][2]."\">";
		echo "<input type=\"hidden\" name=\"from_update\" value=\"from_update\">";
		echo "</form>";
		echo "</div>";
	}



	if (isset($_POST["new_location"])){
		location_create_new($_POST['car_sep'],$_POST['root']);	
		echo "<h4>".$LANG["update"][138].": </h4>";
		display_old_locations();	
		echo "<h4>".$LANG["update"][137].": </h4>";
		display_new_locations();	
		echo "<p>".$LANG["update"][136]."</p>";
		echo "<form action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">";
		echo "<input type=\"submit\" class='submit' name=\"validate_location\" value=\"".$LANG["buttons"][2]."\">";
		echo "<input type=\"hidden\" name=\"from_update\" value=\"from_update\">";
		echo "</form>";
	}
	else if (isset($_POST["validate_location"])){
		validate_new_location();
		updateTreeDropdown();
		return true;
	} else {
		display_old_locations();	
	}
}


//test la connection a la base de donn�.
function test_connect() {
	global $DB;
	if($DB->error == 0) return true;
	else return false;
}

//Change table2 from varchar to ID+varchar and update table1.chps with depends
function changeVarcharToID($table1, $table2, $chps)
{

	global $DB,$LANG;

	if(!FieldExists($table2, "ID")) {
		$query = " ALTER TABLE `". $table2 ."` ADD `ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST";
		$DB->query($query) or die("".$LANG["update"][90].$DB->error());
	}
	$query = "ALTER TABLE $table1 ADD `temp` INT";
	$DB->query($query) or die($LANG["update"][90].$DB->error());

	$query = "select ". $table1 .".ID as row1, ". $table2 .".ID as row2 from ". $table1 .",". $table2 ." where ". $table2 .".name = ". $table1 .".". $chps." ";
	$result = $DB->query($query) or die($LANG["update"][90].$DB->error());
	while($line = $DB->fetch_array($result)) {
		$query = "update ". $table1 ." set temp = ". $line["row2"] ." where ID = '". $line["row1"] ."'";
		$DB->query($query) or die($LANG["update"][90].$DB->error());
	}
	$DB->free_result($result);

	$query = "ALTER TABLE ". $table1 ." DROP ". $chps."";
	$DB->query($query) or die($LANG["update"][90].$DB->error());
	$query = "ALTER TABLE ". $table1 ." CHANGE `temp` `". $chps ."` INT";
	$DB->query($query) or die($LANG["update"][90].$DB->error());
}



//update database up to 0.31
function updatedbUpTo031()
{

	global $DB,$LANG;
	$ret = array();


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
		$DB->query($query) or die($LANG["update"][90].$DB->error());

		$query = "INSERT INTO `glpi_config` VALUES (1, '10', '1', '1', '80', '30', '15', ' 0.31', 'GLPI powered by indepnet', '/glpi', '5', '0', '', '', '', '', '', '', 'admsys@xxxxx.fr', 'SIGNATURE', '1', '1', '1', '1', '0', '0', '0', '0', '0', '0', '0', '0','1', '1', '1', 'uid', 'mail', 'physicaldeliveryofficename', 'cn', 'telephonenumber')";
		$DB->query($query) or die($LANG["update"][90].$DB->error());

		echo "<p class='center'>Version > 0.31  </p>";
	}

	// Get current version
	$query="SELECT version FROM glpi_config";
	$result=$DB->query($query) or die("get current version".$DB->error());
	$current_version=trim($DB->result($result,0,0));


	switch ($current_version){
		case "0.31": 
			include("update_031_04.php");
			update031to04();
		case "0.4": 
		case "0.41": 
			include("update_04_042.php");
			update04to042();
		case "0.42": 
			include("update_042_05.php");
			update042to05();
		case "0.5": 
			include("update_05_051.php");
			update05to051();
		case "0.51": 
		case "0.51a": 
			include("update_051_06.php");
			update051to06();
		case "0.6": 
			include("update_06_065.php");
			update06to065();
		case "0.65": 
			include("update_065_068.php");
			update065to068();
		case "0.68":
			include("update_068_0681.php");
			update068to0681();
		case "0.68.1":
		case "0.68.2":
		case "0.68.3":
			include("update_0681_07.php");
			update0681to07();
		case "0.7":
		case "0.70.1":
		case "0.70.2":
			include("update_07_071.php");
			update07to071();
		case "0.71":
		case "0.71.1":
			include("update_071_0712.php");
			update071to0712();
		case "0.71.2":
			include("update_0712_072.php");
			update0712to072();
		case "0.72":
			break;
		default:
			include("update_031_04.php");
			update031to04();
			include("update_04_042.php");
			update04to042();
			include("update_042_05.php");
			update042to05();
			include("update_05_051.php");
			update05to051();
			include("update_051_06.php");
			update051to06();
			include("update_06_065.php");
			update06to065();
			include("update_065_068.php");
			update065to068();
			include("update_068_0681.php");
			update068to0681();
			include("update_0681_07.php");
			update0681to07();
			include("update_07_071.php");
			update07to071();
			include("update_071_0712.php");
			update071to0712();
			include("update_0712_072.php");
			update0712to072();

			break;
	}

	// Update version number and default langage and new version_founded ---- LEAVE AT THE END
	$query = "UPDATE `glpi_config` SET `version` = ' 0.72', language='".$_SESSION["glpilanguage"]."',founded_new_version='' ;";
	$DB->query($query) or die("0.6 ".$LANG["update"][90].$DB->error());

	// Update process desactivate all plugins
	$plugin=new Plugin();
	$plugin->unactivateAll();

	optimize_tables();

	return $ret;
}









function updateTreeDropdown(){
	global $DB,$LANG;

	// Update Tree dropdown
	if(!FieldExists("glpi_dropdown_locations","completename")) {
		$query= "ALTER TABLE `glpi_dropdown_locations` ADD `completename` TEXT NOT NULL ;";
		$DB->query($query) or die("0.6 add completename in dropdown_locations ".$LANG["update"][90].$DB->error());	
		regenerateTreeCompleteName("glpi_dropdown_locations");
	}
	if(!FieldExists("glpi_dropdown_kbcategories","completename")) {
		$query= "ALTER TABLE `glpi_dropdown_kbcategories` ADD `completename` TEXT NOT NULL ;";
		$DB->query($query) or die("0.6 add completename in dropdown_kbcategories ".$LANG["update"][90].$DB->error());	
		regenerateTreeCompleteName("glpi_dropdown_kbcategories");
	}
}

//Debut du script
$HEADER_LOADED=true;

startGlpiSession();

if(!isset($_SESSION["glpilanguage"])||empty($_SESSION["glpilanguage"])) $_SESSION["glpilanguage"] = "en_GB";


loadLang($_SESSION["glpilanguage"]);

// Send UTF8 Headers
header("Content-Type: text/html; charset=UTF-8");

echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">";
echo "<html>";
echo "<head>";
echo " <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">";
echo "<meta http-equiv=\"Content-Script-Type\" content=\"text/javascript\"> ";
echo "<meta http-equiv=\"Content-Style-Type\" content=\"text/css\"> ";
echo "<meta http-equiv=\"Content-Language\" content=\"fr\"> ";
echo "<meta name=\"generator\" content=\"\">";
echo "<meta name=\"DC.Language\" content=\"fr\" scheme=\"RFC1766\">";
echo "<title>Setup GLPI</title>";
// CSS
echo "<link rel='stylesheet'  href='../css/style_install.css' type='text/css' media='screen' >";

echo "</head>";
echo "<body>";
echo "<div id='principal'>";
echo "<div id='bloc'>";
echo "<div class='haut'></div>";
echo "<h2>GLPI SETUP</h2>";
echo "<br><h3>Update</h3>";

// step 1    avec bouton de confirmation

if(empty($_POST["continuer"]) && empty($_POST["from_update"])) {

	if(empty($from_install)&&!isset($_POST["from_update"])) {
		echo "<div align='center'>";
		echo "<h3><span class='red'>".$LANG["update"][105]."</span>";
		echo "<p class='submit'> <a href=\"../index.php\"><span class='button'>".$LANG["update"][106]."</span></a></p>";
		echo "</div>";
	}
	else {
		echo "<div align='center'>";
		echo "<h3><span class='red'>".$LANG["update"][91]."</span>".$LANG["update"][92]. $DB->dbdefault ."</h3>";

		echo "<form action=\"update.php\" method=\"post\">";
		echo "<input type=\"submit\" class='submit' name=\"continuer\" value=\"".$LANG["install"][25] ."\">";
		echo "</form></div>";
	}
}
// Step 2  
else {
	if(test_connect()) {
		echo "<h3>".$LANG["update"][93]."</h3>";
		if (!isset($_POST["update_location"])){
			$current_verison="0.31";
			if(!TableExists("glpi_config")) {
				include("update_to_031.php");
				updateDbTo031();
				$tab = updateDbUpTo031();
			} else {
				// Get current version
				$query="SELECT version FROM glpi_config";
				$result=$DB->query($query) or die("get current version".$DB->error());
				$current_version=trim($DB->result($result,0,0));

				$tab = updateDbUpTo031();
			}

			echo "<div align='center'>";
			if(!empty($tab) && $tab["adminchange"]) {
				echo "<div align='center'> <h2>". $LANG["update"][96] ."<h2></div>";
			}

			if (showLocationUpdateForm()){
				switch ($current_version){
					case "0.31": 
					case "0.4": 
					case "0.41": 
					case "0.42": 
					case "0.5": 
					case "0.51": 
					case "0.51a": 
					case "0.6": 
					case "0.65": 
					case "0.68":
					case "0.68.1":
					case "0.68.2":
					case "0.68.3":
						showContentUpdateForm();
					break;
					default:
					echo "<a href=\"../index.php\"><span class='button'>".$LANG["install"][64]."</span></a>";
					break;
				}
			}
			echo "</div>";
		}
	}
	else {
		echo "<h3> ";
		echo $LANG["update"][95] ."</h3>";
	}

}

echo "<div class='bas'></div></div></div></body></html>";

?>
