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
/**
 *  Database class for Mysql
 */
class DBmysql {

	//! Database Host
	var $dbhost	= ""; 
	//! Database User
	var $dbuser = "";
	//! Database Password 
	var $dbpassword	= "";
	//! Default Database
	var $dbdefault	= "";
	//! Database Handler
	var $dbh;
	//! Database Error
	var $error = 0;

	/**
	* Constructor / Connect to the MySQL Database
	*
	* Use dbhost, dbuser, dbpassword and dbdefault
	* Die if connection or database selection failed
	*
	* @return nothing 
	*/
	function DBmysql()
	{  // Constructor
		$this->dbh = @mysql_connect($this->dbhost, $this->dbuser, $this->dbpassword) or $this->error = 1;
		if ($this->dbh)
		mysql_select_db($this->dbdefault) or $this->error = 1;
		else {
			nullHeader("Mysql Error",$_SERVER['PHP_SELF']);
			echo "<div align='center'><p><strong>A link to the Mysql server could not be established. Please Check your configuration.</strong></p><p><strong>Le serveur Mysql est inacessible. V&eacute;rifiez votre configuration</strong></p></div>";
			nullFooter("Mysql Error",$_SERVER['PHP_SELF']);
			die();
		}
	}
	/**
	* Execute a MySQL query
	* @param $query Query to execute
	* @return Query result handler
	*/
	function query($query) {
		global $cfg_glpi,$DEBUG_SQL_STRING,$SQL_TOTAL_TIMER, $SQL_TOTAL_REQUEST;
		
		if ($cfg_glpi["debug"]) {
			if ($cfg_glpi["debug_sql"]){		
				$SQL_TOTAL_REQUEST++;
				$DEBUG_SQL_STRING.="N&#176; ".$SQL_TOTAL_REQUEST." : <br>".$query;
				
				if ($cfg_glpi["debug_profile"]){		
					$TIMER=new Script_Timer;
					$TIMER->Start_Timer();
				}
			}
		}

		$res=mysql_query($query,$this->dbh);
		if (!$res) {
			$this->DBmysql();
			$res=mysql_query($query,$this->dbh);
		}

		if ($cfg_glpi["debug"]) {
			if ($cfg_glpi["debug_profile"]&&$cfg_glpi["debug_sql"]){		
				$TIME=$TIMER->Get_Time();
				$DEBUG_SQL_STRING.="<br><b>Time: </b>".$TIME."s";
				$SQL_TOTAL_TIMER+=$TIME;
			}
			if ($cfg_glpi["debug_sql"]){
				$DEBUG_SQL_STRING.="<hr>";
			}
		}
		
		return $res;
	}
	/**
	* Give result from a mysql result
	* @param $result MySQL result handler
	* @param $i Row to give
	* @param $field Field to give
	* @return Value of the Row $i and the Field $field of the Mysql $result
	*/
	function result($result, $i, $field) {
		$value=get_magic_quotes_runtime()?stripslashes_deep(mysql_result($result, $i, $field)):mysql_result($result, $i, $field);
		return $value;
	}
	/**
	* Give number of rows of a Mysql result
	* @param $result MySQL result handler
	* @return number of rows
	*/
	function numrows($result) {
		return mysql_num_rows($result);
	}
	/**
	* Fetch array of the next row of a Mysql query
	* @param $result MySQL result handler
	* @return result array
	*/
	function fetch_array($result) {
		$value=get_magic_quotes_runtime()?stripslashes_deep(mysql_fetch_array($result)):mysql_fetch_array($result);
		return $value;
	}
	/**
	* Fetch row of the next row of a Mysql query
	* @param $result MySQL result handler
	* @return result row
	*/
	function fetch_row($result) {
		$value=get_magic_quotes_runtime()?stripslashes_deep(mysql_fetch_row($result)):mysql_fetch_row($result);
		return $value;
	}
	/**
	* Fetch assoc of the next row of a Mysql query
	* @param $result MySQL result handler
	* @return result associative array
	*/
	function fetch_assoc($result) {
		$value=get_magic_quotes_runtime()?stripslashes_deep(mysql_fetch_assoc($result)):mysql_fetch_assoc($result);
		return $value;
	}
	/**
	* Move current pointer of a Mysql result to the specific row
	* @param $result MySQL result handler
	* @param $seek row to move current pointer
	* @return boolean
	*/
	function data_seek($result,$num){
		return mysql_data_seek ($result,$num);
	}
	/**
	* Give ID of the last insert item by Mysql
	* @return item ID
	*/
	function insert_id() {
 		return mysql_insert_id($this->dbh);
 	}
	/**
	* Give number of fields of a Mysql result
	* @param $result MySQL result handler
	* @return number of fields
	*/
	function num_fields($result) {
		return mysql_num_fields($result);
	}
	/**
	* Give name of a field of a Mysql result
	* @param $result MySQL result handler
	* @param $nb number of column of the field
	* @return name of the field
	*/
	function field_name($result,$nb)
	{
		return mysql_field_name($result,$nb);
	}
	function field_flags($result,$field)
	{
		return mysql_field_flags($result,$field);
	}
	function list_tables() {
		return mysql_list_tables($this->dbdefault,$this->dbh);
	}
	function table_name($result,$nb) {
		return mysql_tablename($result,$nb);
	}
	function list_fields($table) {
		return mysql_list_fields($this->dbdefault,$table,$this->dbh);
	}
	function affected_rows() {
		return mysql_affected_rows($this->dbh);
	}
	function errno()
	{
		return mysql_errno($this->dbh);
	}

	function error()
	{
		return mysql_error($this->dbh);
	}
	function close()
	{
		return mysql_close($this->dbh);
	}
	
}


// Common DataBase Table Manager Class
class CommonDBTM {

	var $fields	= array();
	var $updates	= array();
	var $table="";
	var $type=-1;
	var $dohistory=false;
	
	function CommonDBTM () {

	}

	// Specific ones : StateItem / Reservation Item
	function getFromDB ($ID) {

		// Make new database object and fill variables
		global $db;
		$query = "SELECT * FROM ".$this->table." WHERE (ID = '$ID')";
		if ($result = $db->query($query)) {
			if ($db->numrows($result)==1){
			$data = $db->fetch_assoc($result);
			foreach ($data as $key => $val) {
				$this->fields[$key] = $val;
			}
			return true;
		} else return false;
		} else {
			return false;
		}
	}
		
	function getEmpty () {
		//make an empty database object
		global $db;
		$fields = $db->list_fields($this->table);
		$columns = $db->num_fields($fields);
		for ($i = 0; $i < $columns; $i++) {
			$name = $db->field_name($fields, $i);
			$this->fields[$name] = "";
		
		}
		$this->post_getEmpty();
		return true;
	}
	function post_getEmpty () {
	}

	// Specific Ones : Netdevice / License / User
	function updateInDB($updates)  {

		global $db;

		for ($i=0; $i < count($updates); $i++) {
			$query  = "UPDATE `".$this->table."` SET `";
			$query .= $updates[$i];
			$query .= "`='";
			$query .= $this->fields[$updates[$i]];
			$query .= "' WHERE ID='";
			$query .= $this->fields["ID"];	
			$query .= "'";
			
			$result=$db->query($query);
		}
		$this->post_updateInDB($updates);
	}
	
	function post_updateInDB($updates)  {

	}

	// Specific ones : User
	function addToDB() {
		
		global $db;

		// Build query
		$query = "INSERT INTO ".$this->table." (";
		$i=0;
		
		foreach ($this->fields as $key => $val) {
			$fields[$i] = $key;
			$values[$i] = $val;
			$i++;
		}		
		for ($i=0; $i < count($fields); $i++) {
			$query .= $fields[$i];
			if ($i!=count($fields)-1) {
				$query .= ",";
			}
		}
		$query .= ") VALUES (";
		for ($i=0; $i < count($values); $i++) {
			$query .= "'".$values[$i]."'";
			if ($i!=count($values)-1) {
				$query .= ",";
			}
		}
		$query .= ")";

		if ($result=$db->query($query)) {
			$this->post_addToDB();
			return $db->insert_id();
		} else {
			return false;
		}
	}
	
	function post_addToDB(){

	}

	function restoreInDB($ID) {
		global $db,$cfg_glpi;
		if (in_array($this->table,$cfg_glpi["deleted_tables"])){
			$query = "UPDATE ".$this->table." SET deleted='N' WHERE (ID = '$ID')";
			if ($result = $db->query($query)) {
				return true;
			} else {
				return false;
			}
		} else return false;
	}
	function deleteFromDB($ID,$force=0) {

		global $db,$cfg_glpi;

		if ($force==1||!in_array($this->table,$cfg_glpi["deleted_tables"])){
			
			$this->cleanDBonPurge($ID);

			$query = "DELETE from ".$this->table." WHERE ID = '$ID'";
		
			if ($result = $db->query($query)) {
				$this->post_deleteFromDB($ID);
				return true;
			} else {
				return false;
			}
		}else {
		$query = "UPDATE ".$this->table." SET deleted='Y' WHERE ID = '$ID'";		
		return ($result = $db->query($query));
		}
	}

	function post_deleteFromDB($ID){
	}

	function cleanDBonPurge($ID) {
	}

	// Common functions

	/**
	* Add an item in the database.
	*
	* Add an item in the database with all it's items.
	*
	*@param $input array : the _POST vars returned bye the item form when press add
	*
	*
	*@return integer the new ID of the added item
	*
	**/
	// specific ones : document, reservationresa , planningtracking, followup
	function add($input) {

		// dump status
		unset($input['add']);
		$input=$this->prepareInputForAdd($input);

		// fill array for udpate
		foreach ($input as $key => $val) {
			if ($key[0]!='_'&& (!isset($this->fields[$key]) || $this->fields[$key] != $input[$key])) {
				$this->fields[$key] = $input[$key];
			}
		}

		$newID= $this->addToDB();

		$this->postAddItem($newID,$input);

		do_hook_function("item_add",array("type"=>$this->type, "ID" => $newID));

		return $newID;
	}

	function prepareInputForAdd($input) {
		return $input;
	}
	
	function postAddItem($newID,$input) {
	}


	/**
	* Update some elements of an item in the database
	*
	* Update some elements of an item in the database.
	*
	*@param $input array : the _POST vars returned bye the item form when press update
	*
	*
	*@return Nothing (call to the class member)
	*
	**/
	// specific ones : document, reservationresa, planningtracking
	function update($input,$history=1) {
		
		$input=$this->prepareInputForUpdate($input);
		unset($input['update']);
		if ($this->getFromDB($input["ID"])){

			// Fill the update-array with changes
			$x=0;
			$updates=array();
			foreach ($input as $key => $val) {
				if (array_key_exists($key,$this->fields) && $this->fields[$key] != $input[$key]) {
					// Debut logs
					if ($this->dohistory&&$history)
						constructHistory($input["ID"],$this->type,$key,$this->fields[$key],$input[$key]);
					// Fin des logs

					$this->fields[$key] = $input[$key];
					$updates[$x] = $key;
					$x++;
				}
			}

			if(count($updates)){
				$this->updateInDB($updates);
			} 

			$this->post_updateItem($input,$updates,$history);

			do_hook_function("item_update",array("type"=>$this->type, "ID" => $input["ID"]));
		}
	}

	function prepareInputForUpdate($input) {
		return $input;
	}
	
	function post_updateItem($input,$updates,$history=1) {
	}

	/**
	* Delete an item in the database.
	*
	* Delete an item in the database.
	*
	*@param $input array : the _POST vars returned bye the item form when press delete
	*@param $force boolean : force deletion
	*
	*
	*@return Nothing ()
	*
	**/
	function delete($input,$force=0) {
		if ($this->getFromDB($input["ID"])){
			$this->pre_deleteItem($input["ID"]);
			$this->deleteFromDB($input["ID"],$force);
			if ($force)
				do_hook_function("item_purge",array("type"=>$this->type, "ID" => $input["ID"]));
			else 
				do_hook_function("item_delete",array("type"=>$this->type, "ID" => $input["ID"]));
		return true;
		} else return false;

	}
	function pre_deleteItem($ID) {

	}
	/**
	* Restore an item trashed in the database.
	*
	* Restore an item trashed in the database.
	*
	*@param $input array : the _POST vars returned bye the item form when press restore
	*
	*@return Nothing ()
	*
	**/
	// specific ones : cartridges / consumables
	function restore($input) {
	
		$this->restoreInDB($input["ID"]);
		do_hook_function("item_restore",array("type"=>$this->type, "ID" => $input["ID"]));
	}

	function defineOnglets($withtemplate){
		return array();
	}
	
	function showOnglets($target,$withtemplate,$actif){
		global $lang, $HTMLRel;

		$template="";
		if(!empty($withtemplate)){
			$template="&amp;withtemplate=$withtemplate";
		}
	
		echo "<div id='barre_onglets'><ul id='onglet'>";
		
		if (count($onglets=$this->defineOnglets($withtemplate))){
			foreach ($onglets as $key => $val ) {
				echo "<li "; if ($actif==$key){ echo "class='actif'";} echo  "><a href='$target&amp;onglet=$key$template'>".$val."</a></li>";
				}
		}


		if(empty($withtemplate)){
			echo "<li class='invisible'>&nbsp;</li>";
			echo "<li "; if ($actif=="-1") {echo "class='actif'";} echo "><a href='$target&amp;onglet=-1$template'>".$lang["title"][29]."</a></li>";
		}

		display_plugin_headings($target,$this->type,$withtemplate,$actif);

		echo "<li class='invisible'>&nbsp;</li>";
	
		if (empty($withtemplate)&&preg_match("/\?ID=([0-9]+)/",$target,$ereg)){
			$ID=$ereg[1];
			$next=getNextItem($this->table,$ID);
			$prev=getPreviousItem($this->table,$ID);
			$cleantarget=preg_replace("/\?ID=([0-9]+)/","",$target);
			if ($prev>0) echo "<li><a href='$cleantarget?ID=$prev'><img src=\"".$HTMLRel."pics/left.png\" alt='".$lang["buttons"][12]."' title='".$lang["buttons"][12]."'></a></li>";
			if ($next>0) echo "<li><a href='$cleantarget?ID=$next'><img src=\"".$HTMLRel."pics/right.png\" alt='".$lang["buttons"][11]."' title='".$lang["buttons"][11]."'></a></li>";

			if (function_exists("isReservable")&&isReservable(COMPUTER_TYPE,$ID)){
				echo "<li class='invisible'>&nbsp;</li>";
				echo "<li".(($actif==11)?" class='actif'":"")."><a href='$target&amp;onglet=11$template'>".$lang["title"][35]."</a></li>";
			}

		}

		echo "</ul></div>";
	
	}

}



/**
 *  Common Item of GLPI : Global simple interface to items - abstraction usage
 */
class CommonItem{
	//! Object Type depending of the device_type
	var $obj = NULL;	
	//! Device Type ID of the object
	var $device_type=0;
	//! Device ID of the object
	var $id_type=0;
	
	
	/**
	* Get an Object / General Function
	*
	* Create a new Object depending of $device_type and Get the item with the ID $id_device
	*
	* @param $device_type Device Type ID of the object
	* @param $id_device Device ID of the object
	*
	* @return boolean : object founded and loaded
	*/
	function getfromDB ($device_type,$id_device) {
		$this->id_device=$id_device;
		$this->device_type=$device_type;
		// Make new database object and fill variables

			switch ($device_type){
			case COMPUTER_TYPE :
				$this->obj=new Computer;
				break;
			case NETWORKING_TYPE :
				$this->obj=new Netdevice;
				break;
			case PRINTER_TYPE :
				$this->obj=new Printer;
				break;
			case MONITOR_TYPE : 
				$this->obj= new Monitor;	
				break;
			case PERIPHERAL_TYPE : 
				$this->obj= new Peripheral;	
				break;				
			case PHONE_TYPE : 
				$this->obj= new Phone;	
				break;		
			case SOFTWARE_TYPE : 
				$this->obj= new Software;	
				break;				
			case CONTRACT_TYPE : 
				$this->obj= new Contract;	
				break;				
			case ENTERPRISE_TYPE : 
				$this->obj= new Enterprise;	
				break;	
			case CONTACT_TYPE : 
				$this->obj= new Contact;	
				break;	
			case KNOWBASE_TYPE : 
				$this->obj= new kbitem;	
				break;					
			case USER_TYPE : 
				$this->obj= new User;	
				break;					
			case TRACKING_TYPE : 
				$this->obj= new Job;	
				break;
			case CARTRIDGE_TYPE : 
				$this->obj= new CartridgeType;	
				break;					
			case CONSUMABLE_TYPE : 
				$this->obj= new ConsumableType;	
				break;					
			case CARTRIDGE_ITEM_TYPE : 
				$this->obj= new Cartridge;	
				break;					
			case CONSUMABLE_ITEM_TYPE : 
				$this->obj= new Consumable;	
				break;					
			case LICENSE_TYPE : 
				$this->obj= new License;	
				break;					
			case DOCUMENT_TYPE : 
				$this->obj= new Document;	
				break;					
			}

			if ($this->obj!=NULL){
				// Do not load devices
					return $this->obj->getfromDB($id_device);
			}
			else return false;
			
	}
	
	/**
	* Set the device type
	*
	* @param $device_type Device Type ID of the object
	*
	*/
	function setType ($device_type){
		$this->device_type=$device_type;
	}

	/**
	* Get The Type Name of the Object
	*
	* @return String: name of the object type in the current language
	*/
	function getType (){
		global $lang;
		
		switch ($this->device_type){
			case GENERAL_TYPE :
				return $lang["help"][30];
				break;
			case COMPUTER_TYPE :
				return $lang["computers"][44];
				break;
			case NETWORKING_TYPE :
				return $lang["networking"][12];
				break;
			case PRINTER_TYPE :
				return $lang["printers"][4];
				break;
			case MONITOR_TYPE : 
				return $lang["monitors"][4];
				break;
			case PERIPHERAL_TYPE : 
				return $lang["peripherals"][4];
				break;				
			case PHONE_TYPE : 
				return $lang["phones"][4];
				break;				
			case SOFTWARE_TYPE : 
				return $lang["software"][10];
				break;				
			case CONTRACT_TYPE : 
				return $lang["financial"][1];
				break;				
			case ENTERPRISE_TYPE : 
				return $lang["financial"][26];
				break;
			case CONTACT_TYPE : 
				return $lang["common"][18];
				break;
			case KNOWBASE_TYPE : 
				return $lang["knowbase"][0];
				break;	
			case USER_TYPE : 
				return $lang["setup"][57];
				break;	
			case TRACKING_TYPE : 
				return $lang["job"][38];
				break;	
			case CARTRIDGE_TYPE : 
				return $lang["cartridges"][16];
				break;
			case CONSUMABLE_TYPE : 
				return $lang["consumables"][16];
				break;					
			case LICENSE_TYPE : 
				return $lang["software"][11];
				break;					
			case CARTRIDGE_ITEM_TYPE : 
				return $lang["cartridges"][0];
				break;
			case CONSUMABLE_ITEM_TYPE : 
				return $lang["consumables"][0];
				break;					
			case DOCUMENT_TYPE : 
				return $lang["document"][0];
				break;					
			}
	
	}

	/**
	* Get The Name of the Object
	*
	* @return String: name of the object in the current language
	*/
	function getName(){
		global $lang;
		
		if ($this->device_type==0) return "";
		
		if ($this->device_type==KNOWBASE_TYPE&&$this->obj!=NULL&&isset($this->obj->fields["question"])&&$this->obj->fields["question"]!="")
			return $this->obj->fields["question"];
		else if ($this->device_type==LICENSE_TYPE&&$this->obj!=NULL&&isset($this->obj->fields["serial"])&&$this->obj->fields["serial"]!="")
			return $this->obj->fields["serial"];
		else if (($this->device_type==CARTRIDGE_TYPE||$this->device_type==CONSUMABLE_TYPE)&&$this->obj!=NULL&&$this->obj->fields["name"]!=""){
			$name=$this->obj->fields["name"];
			if (isset($this->obj->fields["ref"])&&!empty($this->obj->fields["ref"]))			
				$name.=" - ".$this->obj->fields["ref"];
			return $name;
			}
		else if ($this->obj!=NULL&&isset($this->obj->fields["name"])&&$this->obj->fields["name"]!="")
			return $this->obj->fields["name"];
		else 
			return "N/A";
	}
	function getNameID(){
		global $cfg_glpi;
		if ($cfg_glpi["view_ID"]){
			if ($this->device_type==0)
				return $this->getName();
			else return $this->getName()." (".$this->id_device.")";
		} else return $this->getName();
	}
	/**
	* Get The link to the Object
	*
	* @return String: link to the object type in the current language
	*/
	function getLink(){
	
		global $cfg_glpi,$INFOFORM_PAGES;
		$ID="";
		switch ($this->device_type){
			case GENERAL_TYPE :
				return $this->getName();
				break;
			case COMPUTER_TYPE :
			case NETWORKING_TYPE :
			case PRINTER_TYPE :
			case MONITOR_TYPE : 
			case PERIPHERAL_TYPE : 
			case PHONE_TYPE : 
			case SOFTWARE_TYPE : 
			case CONTRACT_TYPE : 
			case ENTERPRISE_TYPE : 
			case CONTACT_TYPE : 
			case KNOWBASE_TYPE : 
			case USER_TYPE : 
			case TRACKING_TYPE : 			
			case CARTRIDGE_TYPE : 
			case CONSUMABLE_TYPE : 
			case DOCUMENT_TYPE : 
				if($cfg_glpi["view_ID"]) $ID= " (".$this->id_device.")";
				return "<a href=\"".$cfg_glpi["root_doc"]."/".$INFOFORM_PAGES[$this->device_type]."?ID=".$this->id_device."\">".$this->getName()."$ID</a>";
				break;
			case LICENSE_TYPE : 
				return $this->getName();
				break;						
			case CARTRIDGE_ITEM_TYPE : 
				return $this->getName();
				break;						
			case CONSUMABLE_ITEM_TYPE : 
				return $this->getName();
				break;						
			}

	
	}
	
}




?>
