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

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}

// Common DataBase Table Manager Class
class CommonDBTM {

	// Data of the Item
	var $fields	= array();
	// Table name
	var $table="";
	// GLPI Item type
	var $type=-1;
	// Make an history of the changes
	var $dohistory=false;

	/**
	 * Constructor
	 *
	 *@return nothing
	 *
	 **/
	function CommonDBTM () {

	}

	/**
	 * Clean cache used by the item $ID
	 *
	 *@param $ID ID of the item
	 *@return nothing
	 *
	 **/
	function cleanCache($ID){
		global $CFG_GLPI;
		cleanAllItemCache($ID,"GLPI_".$this->type);
		cleanAllItemCache("comments_".$ID,"GLPI_".$this->type);
		$CFG_GLPI["cache"]->remove("data_".$ID,"GLPI_".$this->table);
		cleanRelationCache($this->table);
	}

	/**
	 * Retrieve an item from the database
	 *
	 *@param $ID ID of the item to get
	 *@return true if succeed else false
	 *
	 **/
	// Specific ones : Reservation Item
	function getFromDB ($ID) {

		// Make new database object and fill variables
		global $DB,$CFG_GLPI;
		if (empty($ID)&&$ID!=0) return false;
		$query = "SELECT * FROM ".$this->table." WHERE (".$this->getIndexName()." = $ID)";
		if ($result = $DB->query($query)) {
			if ($DB->numrows($result)==1){
				$this->fields = $DB->fetch_assoc($result);
				return true;
			} 
		} 
		return false;;

	}
	/**
	 * Get the name of the index field
	 *
	 *@return name of the index field
	 *
	 **/
	function getIndexName(){
		return "ID";
	}
	/**
	 * Get an empty item
	 *
	 *@return true if succeed else false
	 *
	 **/
	function getEmpty () {
		//make an empty database object
		global $DB;
		if ($fields = $DB->list_fields($this->table)){
			foreach ($fields as $key => $val){
				$this->fields[$key] = "";
			}
		} else return false;
		if (isset($this->fields['FK_entities'])&&isset($_SESSION["glpiactive_entity"])){
			$this->fields['FK_entities']=$_SESSION["glpiactive_entity"];
		}
		$this->post_getEmpty();
		return true;
	}
	/**
	 * Actions done at the end of the getEmpty function
	 *
	 *@return nothing
	 *
	 **/
	function post_getEmpty () {
	}
	/**
	 * Update the item in the database
	 * 
	 *@return nothing
	 *
	 **/
	function updateInDB($updates)  {

		global $DB,$CFG_GLPI;

		for ($i=0; $i < count($updates); $i++) {
			$query  = "UPDATE `".$this->table."` SET `";
			$query .= $updates[$i]."`";

			if ($this->fields[$updates[$i]]=="NULL"){
				$query .= " = ";
				$query .= $this->fields[$updates[$i]];
			} else {
				$query .= " = '";
				$query .= $this->fields[$updates[$i]]."'";
			}
			$query .= " WHERE ".$this->getIndexName()." ='";
			$query .= $this->fields["ID"];	
			$query .= "'";
			$result=$DB->query($query);
	
		}
		$this->cleanCache($this->fields["ID"]);
		return true;
	}

	/**
	 * Add an item to the database
	 *
	 *@return new ID of the item is insert successfull else false
	 *
	 **/
	function addToDB() {

		global $DB;
		//unset($this->fields["ID"]);
		$nb_fields=count($this->fields);
		if ($nb_fields>0){		

			// Build query
			$query = "INSERT INTO ".$this->table." (";
			$i=0;
			foreach ($this->fields as $key => $val) {
				$fields[$i] = $key;
				$values[$i] = $val;
				$i++;
			}		

			for ($i=0; $i < $nb_fields; $i++) {
				$query .= "`".$fields[$i]."`";
				if ($i!=$nb_fields-1) {
					$query .= ",";
				}
			}
			$query .= ") VALUES (";
			for ($i=0; $i < $nb_fields; $i++) {
				$query .= "'".$values[$i]."'";
				if ($i!=$nb_fields-1) {
					$query .= ",";
				}
			}
			$query .= ")";
			if ($result=$DB->query($query)) {
				$this->fields["ID"]=$DB->insert_id();
				cleanRelationCache($this->table);
				return $this->fields["ID"];
			} else {
				return false;
			}
		} else return false;
	}

	/**
	 * Restore item = set deleted flag to 0
	 *
	 *@param $ID ID of the item
	 *
	 *
	 *@return true if succeed else false
	 *
	 **/
	function restoreInDB($ID) {
		global $DB,$CFG_GLPI;
		if (in_array($this->table,$CFG_GLPI["deleted_tables"])){
			$query = "UPDATE ".$this->table." SET deleted='0' WHERE (".$this->getIndexName()." = '$ID')";
			if ($result = $DB->query($query)) {
				return true;
			} else {
				return false;
			}
		} else return false;
	}
	/**
	 * Mark deleted or purge an item in the database
	 *
	 *@param $ID ID of the item
	 *@param $force force the purge of the item (not used if the table do not have a deleted field)
	 *
	 *@return true if succeed else false
	 *
	 **/

	function deleteFromDB($ID,$force=0) {

		global $DB,$CFG_GLPI;

		if ($force==1||!in_array($this->table,$CFG_GLPI["deleted_tables"])){

			$this->cleanDBonPurge($ID);

			$this->cleanHistory($ID);

			$this->cleanRelationData($ID);

			$query = "DELETE from ".$this->table." WHERE ".$this->getIndexName()." = '$ID'";

			if ($result = $DB->query($query)) {
				$this->post_deleteFromDB($ID);
				$this->cleanCache($ID);
				return true;
			} else {
				return false;
			}
		}else {
			$query = "UPDATE ".$this->table." SET deleted='1' WHERE ".$this->getIndexName()." = '$ID'";		
			$this->cleanDBonMarkDeleted($ID);

			if ($result = $DB->query($query)){
				$this->cleanCache($ID);
				return true;
			} else return false;
		}
	}

	/**
	 * Clean data in the tables which have linked the deleted item
	 *
	 *@param $ID ID of the item
	 *
	 *
	 *@return nothing
	 *
	 **/
	function cleanHistory($ID){
		global $DB;
		if ($this->dohistory){
			$query = "DELETE FROM glpi_history WHERE ( device_type = '".$this->type."' AND FK_glpi_device = '$ID')";
			$DB->query($query);
		}
	}

	/**
	 * Clean data in the tables which have linked the deleted item
	 *
	 *@param $ID ID of the item
	 *
	 *
	 *@return nothing
	 *
	 **/
	function cleanRelationData($ID){
		global $DB;
		$RELATION=getDbRelations();
		if (isset($RELATION[$this->table])){
			foreach ($RELATION[$this->table] as $tablename => $field){
				if ($tablename[0]!='_'){
					if (!is_array($field)){
						$query="UPDATE `$tablename` SET `$field` = NULL WHERE `$field`='$ID' ";
						$DB->query($query);
					} else {
						foreach ($field as $f){
							$query="UPDATE `$tablename` SET `$f` = NULL WHERE `$f`='$ID' ";
							$DB->query($query);
						}
					}
				}
			}
		}
	}
	/**
	 * Actions done after the DELETE of the item in the database
	 *
	 *@param $ID ID of the item
	 *
	 *@return nothing
	 *
	 **/
	function post_deleteFromDB($ID){
	}

	/**
	 * Actions done when item is deleted from the database
	 *
	 *@param $ID ID of the item
	 *
	 *
	 *@return nothing
	 *
	 **/
	function cleanDBonPurge($ID) {
	}
	/**
	 * Actions done when item flag deleted is set to an item
	 *
	 *@param $ID ID of the item
	 *
	 *@return nothing
	 *
	 **/
	function cleanDBonMarkDeleted($ID) {
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
	// specific ones : reservationresa , planningtracking
	function add($input) {
		global $DB;
		$input['_item_type_']=$this->type;
		$input=doHookFunction("pre_item_add",$input);

		unset($input['add']);
		$input=$this->prepareInputForAdd($input);

		if ($input&&is_array($input)){
			$table_fields=$DB->list_fields($this->table);
			// fill array for udpate
			foreach ($input as $key => $val) {
				if ($key[0]!='_'&& isset($table_fields[$key])&&(!isset($this->fields[$key]) || $this->fields[$key] != $input[$key])) {
					$this->fields[$key] = $input[$key];
				}
			}

			if ($newID= $this->addToDB()){
				$this->post_addItem($newID,$input);
				doHook("item_add",array("type"=>$this->type, "ID" => $newID));
				return $newID;
			} else return false;

		} else return false;
	}

	/**
	 * Prepare input datas for adding the item
	 *
	 *@param $input datas used to add the item
	 *
	 *@return the modified $input array
	 *
	 **/
	function prepareInputForAdd($input) {
		return $input;
	}

	function post_addItem($newID,$input) {
	}


	/**
	 * Update some elements of an item in the database
	 *
	 * Update some elements of an item in the database.
	 *
	 *@param $input array : the _POST vars returned bye the item form when press update
	 *@param $history boolean : do history log ?
	 *
	 *
	 *@return Nothing (call to the class member)
	 *
	 **/
	// specific ones : reservationresa, planningtracking
	function update($input,$history=1) {

		$input['_item_type_']=$this->type;
		$input=doHookFunction("pre_item_update",$input);

		$input=$this->prepareInputForUpdate($input);
		unset($input['update']);
		if ($this->getFromDB($input["ID"])){
			// Fill the update-array with changes
			$x=0;
			$updates=array();
			foreach ($input as $key => $val) {
				if (array_key_exists($key,$this->fields)){
					// Secu for null values on history
					// TODO : Int with NULL default value in DB -> default value 0
/*					if (is_null($this->fields[$key])){
						if (is_int($input[$key])||$input[$key]=='0') 	$this->fields[$key]=0;
					}
*/
					if ($this->fields[$key] != stripslashes($input[$key])) {
						if ($key!="ID"){
							// Do logs
							if ($this->dohistory&&$history){
								constructHistory($input["ID"],$this->type,$key,$this->fields[$key],$input[$key]);
							}
							$this->fields[$key] = $input[$key];
							$updates[$x] = $key;
							$x++;
						}
					}
				}
			}	
			if(count($updates)){
				list($input,$updates)=$this->pre_updateInDB($input,$updates);

				if ($this->updateInDB($updates)){
					doHook("item_update",array("type"=>$this->type, "ID" => $input["ID"]));
				}
			} 
			$this->post_updateItem($input,$updates,$history);
		}
	}

	function prepareInputForUpdate($input) {
		return $input;
	}

	function post_updateItem($input,$updates,$history=1) {
	}

	function pre_updateInDB($input,$updates) {
		return array($input,$updates);
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
		$input['_item_type_']=$this->type;
		if ($force){
			$input=doHookFunction("pre_item_purge",$input);
		} else {
			$input=doHookFunction("pre_item_delete",$input);
		}

		if ($this->getFromDB($input["ID"])){
			if ($this->pre_deleteItem($input["ID"])){
				if ($this->deleteFromDB($input["ID"],$force)){
					if ($force){
						doHook("item_purge",array("type"=>$this->type, "ID" => $input["ID"]));
					} else {
						doHook("item_delete",array("type"=>$this->type, "ID" => $input["ID"]));
					}
				}
				return true;
			} else {
				return false;
			}
		} else return false;

	}
	function pre_deleteItem($ID) {
		return true;
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
		$input['_item_type_']=$this->type;
		$input=doHookFunction("pre_item_restore",$input);

		if ($this->restoreInDB($input["ID"])){
			doHook("item_restore",array("type"=>$this->type, "ID" => $input["ID"]));
		}
	}

	function reset(){
		$this->fields=array();

	}

	function defineOnglets($withtemplate){
		return array();
	}

	function showOnglets($ID,$withtemplate,$actif,$nextprevcondition="",$nextprev_item="",$addurlparam=""){
		global $LANG,$CFG_GLPI;

		$target=$_SERVER['PHP_SELF']."?ID=".$ID;
	
		$template="";
		if(!empty($withtemplate)){
			$template="&amp;withtemplate=$withtemplate";
		}
	
		echo "<div id='barre_onglets'><ul id='onglet'>";
	
		if (count($onglets=$this->defineOnglets($withtemplate))){
			//if (empty($withtemplate)&&haveRight("reservation_central","r")&&function_exists("isReservable")){
			//	$onglets[11]=$LANG["title"][35];
			//	ksort($onglets);
			//}
			foreach ($onglets as $key => $val ) {
				echo "<li "; if ($actif==$key){ echo "class='actif'";} echo  "><a href='$target&amp;onglet=$key$template$addurlparam'>".$val."</a></li>";
			}
			if(empty($withtemplate)){
				echo "<li class='invisible'>&nbsp;</li>";
				echo "<li "; if ($actif=="-1") {echo "class='actif'";} echo "><a href='$target&amp;onglet=-1$template$addurlparam'>".$LANG["title"][29]."</a></li>";
			}
		}
	
	
	
		displayPluginHeadings($target,$this->type,$withtemplate,$actif);
	
		echo "<li class='invisible'>&nbsp;</li>";
	
		if (empty($withtemplate)&&preg_match("/\?ID=([0-9]+)/",$target,$ereg)){
			$ID=$ereg[1];
			$next=getNextItem($this->table,$ID,$nextprevcondition,$nextprev_item);
			$prev=getPreviousItem($this->table,$ID,$nextprevcondition,$nextprev_item);
			$cleantarget=preg_replace("/\?ID=([0-9]+)/","",$target);
			if ($prev>0) echo "<li><a href='$cleantarget?ID=$prev$addurlparam'><img src=\"".$CFG_GLPI["root_doc"]."/pics/left.png\" alt='".$LANG["buttons"][12]."' title='".$LANG["buttons"][12]."'></a></li>";
			if ($next>0) echo "<li><a href='$cleantarget?ID=$next$addurlparam'><img src=\"".$CFG_GLPI["root_doc"]."/pics/right.png\" alt='".$LANG["buttons"][11]."' title='".$LANG["buttons"][11]."'></a></li>";
		}
	
		echo "</ul></div>";
	} 

}




?>
