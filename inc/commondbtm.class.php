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

if (!defined('GLPI_ROOT')){
	die("Sorry. You can't access directly to this file");
	}

/// Common DataBase Table Manager Class
class CommonDBTM {

	/// Data of the Item
	var $fields	= array();
	/// Table name
	var $table="";
	/// GLPI Item type
	var $type=-1;
	/// Make an history of the changes
	var $dohistory=false;
	/// Is an item specific to entity
	var $entity_assign=false;
	/// Is an item that can be recursivly assign to an entity
	var $may_be_recursive=false;
	/// Is an item that can be private or assign to an entity
	var $may_be_private=false;
	/// Black list fields for date_mod updates
	var $date_mod_blacklist	= array();

	/// set false to desactivate automatic message on action
	var $auto_message_on_action=true;

	/**
	 * Constructor
	 **/
	function __construct () {

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
		$CFG_GLPI["cache"]->remove("data_".$ID,"GLPI_".$this->table,true);
		cleanRelationCache($this->table);
	}

	/**
	 * Retrieve an item from the database
	 *
	 *@param $ID ID of the item to get
	 *@return true if succeed else false
	**/	
	function getFromDB ($ID) {

		// Make new database object and fill variables
		global $DB;
		// != 0 because 0 is consider as empty
		if (strlen($ID)==0) return false;
		
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
	 * Retrieve all items from the database
	 *
	 *@param $condition condition used to search if needed (empty get all)
	 *@param $order order field if needed
	 *@param $limit limit retrieved datas if needed
	 *@return true if succeed else false
	**/	
	function find ($condition="", $order="", $limit="") {

		// Make new database object and fill variables
		global $DB;
		
		$query = "SELECT * FROM ".$this->table;
		if (!empty($condition)){
			$query.=" WHERE $condition";
		}

		if (!empty($order)){
			$query.=" ORDER BY $order";
		}
		if (!empty($limit)){
			$query.=" LIMIT $limit";
		}
	
		$data=array();
		if ($result = $DB->query($query)) {
			if ($DB->numrows($result)){
				while ($line = $DB->fetch_assoc($result)){
					$data[$line['ID']]=$line;
				}
			} 
		} 
		return $data;

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
		} else {
			return false;
		}
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
	 *  @param $updates fields to update
 	 *  @param $oldvalues old values of the updated fields
	 *@return nothing
	 *
	 **/
	function updateInDB($updates,$oldvalues=array())  {

		global $DB,$CFG_GLPI;
		foreach ($updates as $field) {
			if (isset($this->fields[$field])){
				$query  = "UPDATE `".$this->table."` SET `";
				$query .= $field."`";
	
				if ($this->fields[$field]=="NULL"){
					$query .= " = ";
					$query .= $this->fields[$field];
				} else {
					$query .= " = '";
					$query .= $this->fields[$field]."'";
				}
				$query .= " WHERE ID ='";
				$query .= $this->fields["ID"];	
				$query .= "'";

				if (!$DB->query($query)){
					if (isset($oldvalues[$field])){
						unset($oldvalues[$field]);
					}
				}
			} else {
				// Clean oldvalues
				if (isset($oldvalues[$field])){
					unset($oldvalues[$field]);
				}
			}
		}

		if(count($oldvalues)){
			constructHistory($this->fields["ID"],$this->type,$oldvalues,$this->fields);
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
				if ($values[$i]=='NULL'){
					$query .= $values[$i];
				} else {
					$query .= "'".$values[$i]."'";
				}
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
			$query = "UPDATE ".$this->table." SET deleted='0' WHERE (ID = '$ID')";
			if ($result = $DB->query($query)) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
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

			$query = "DELETE from ".$this->table." WHERE ID = '$ID'";

			if ($result = $DB->query($query)) {
				$this->post_deleteFromDB($ID);
				$this->cleanCache($ID);
				return true;
			} else {
				return false;
			}
		}else {
			$query = "UPDATE ".$this->table." SET deleted='1' WHERE ID = '$ID'";		
			$this->cleanDBonMarkDeleted($ID);

			if ($result = $DB->query($query)){
				$this->cleanCache($ID);
				return true;
			} else {
				return false;
			}
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
						$query="UPDATE `$tablename` SET `$field` = 0 WHERE `$field`='$ID' ";
						$DB->query($query);
					} else {
						foreach ($field as $f){
							$query="UPDATE `$tablename` SET `$f` = 0 WHERE `$f`='$ID' ";
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
	 *@return nothing
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
	 * Add an item in the database with all it's items.
	 *
	 *@param $input array : the _POST vars returned bye the item form when press add
	 *
	 *@return integer the new ID of the added item
	**/
	function add($input) {
		global $DB;
		
		$addMessAfterRedirect = false;

		if ($DB->isSlave()) {
			return false;
		}
			
		$input['_item_type_']=$this->type;
		$input=doHookFunction("pre_item_add",$input);

		if (isset($input['add'])){
			$input['_add']=$input['add'];
			unset($input['add']);
		}
		$input=$this->prepareInputForAdd($input);

		if ($input&&is_array($input)){
			$this->fields=array();
			$table_fields=$DB->list_fields($this->table);

			// fill array for add
			foreach ($input as $key => $val) {
				if ($key[0]!='_'&& isset($table_fields[$key])
					// TEST -> TO DELETE Not needed for add process : always copy data && (!isset($this->fields[$key]) || $this->fields[$key] != $input[$key])
				) {
					$this->fields[$key] = $input[$key];
				}
			}
			// Auto set date_mod if exsist
			if (isset($table_fields['date_mod'])){
				$this->fields['date_mod']=$_SESSION["glpi_currenttime"];
			}

			if ($newID= $this->addToDB()){
				$this->addMessageOnAddAction($input);
				$this->post_addItem($newID,$input);
				doHook("item_add",array("type"=>$this->type, "ID" => $newID));
				return $newID;
			} else {
				return false;
			}

		} else {
			return false;
		}	
	}

	/**
	 * Add a message on add action
	 *
	 *@param $input array : the _POST vars returned bye the item form when press add
	 *
	**/
	function addMessageOnAddAction($input){
		global $INFOFORM_PAGES, $CFG_GLPI, $LANG;

		if (!isset($INFOFORM_PAGES[$this->type])){
			return;
		}

		$addMessAfterRedirect=false;
		if (isset($input['_add'])){
			$addMessAfterRedirect=true;
		}
		if (isset($input['_no_message']) || !$this->auto_message_on_action){
			$addMessAfterRedirect=false;
		}

		if ($addMessAfterRedirect) {
			addMessageAfterRedirect($LANG["common"][70] . 
			": <a href='" . $CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$this->type] . "?ID=" . $this->fields['ID'] . (isset($input['is_template'])?"&amp;withtemplate=1":"")."'>" .
			(isset($this->fields["name"]) && !empty($this->fields["name"]) ? stripslashes($this->fields["name"]) : "(".$this->fields['ID'].")") . "</a>");
		} 
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
	/**
	 * Actions done after the ADD of the item in the database
	 * 
	 *@param $newID ID of the new item 
	 *@param $input datas used to add the item
	 *
	 * @return nothing 
	 * 
	**/
	function post_addItem($newID,$input) {
	}


	/**
	 * Update some elements of an item in the database.
	 *
	 *@param $input array : the _POST vars returned bye the item form when press update
	 *@param $history boolean : do history log ?
	 *
	 *
	 *@return Nothing (call to the class member)
	 *
	**/
	function update($input,$history=1) {
		global $DB;
		if ($DB->isSlave())
			return false;

		$input['_item_type_']=$this->type;
		$input=doHookFunction("pre_item_update",$input);

		$input=$this->prepareInputForUpdate($input);

		if (isset($input['update'])){
			$input['_update']=$input['update'];
			unset($input['update']);
		}
		// Valid input
		if ($input&&is_array($input)
		&&$this->getFromDB($input[$this->getIndexName()])){

			// Fill the update-array with changes
			$x=0;
			$updates=array();
			$oldvalues=array();
			foreach ($input as $key => $val) {
				if (array_key_exists($key,$this->fields)){
					// Prevent history for date statement (for date for example)
					if ( is_null($this->fields[$key]) && $input[$key]=='NULL'){
						$this->fields[$key]='NULL';
					}
											
					if ( $this->fields[$key] != stripslashes($input[$key])) {
						if ($key!="ID"){
							// Store old values
							$oldvalues[$key]=$this->fields[$key];
							$this->fields[$key] = $input[$key];
							$updates[$x] = $key;
							$x++;
						}
					}
				}
			}

			if(count($updates)){
				if (isset($this->fields['date_mod'])){
					// is a non blacklist field exists
					if (count(array_diff($updates,$this->date_mod_blacklist)) > 0){
						$this->fields['date_mod']=$_SESSION["glpi_currenttime"];
						$updates[$x++] = 'date_mod';
					}
				}

				list($input,$updates)=$this->pre_updateInDB($input,$updates,$oldvalues);

				// CLean old_values history not needed
				if (!$this->dohistory || !$history){
					$oldvalues=array();
				}

				if ($this->updateInDB($updates,$oldvalues)){
					$this->addMessageOnUpdateAction($input);
					doHook("item_update",array("type"=>$this->type, "ID" => $input["ID"]));
				}
			} 
			$this->post_updateItem($input,$updates,$history);
		} else {
			return false;
		}
		return true;
	}

	/**
	 * Add a message on update action
	 *
	 *@param $input array : the _POST vars returned bye the item form when press add
	 *
	**/
	function addMessageOnUpdateAction($input){
		global $INFOFORM_PAGES, $CFG_GLPI, $LANG;

		if (!isset($INFOFORM_PAGES[$this->type])){
			return;
		}

		$addMessAfterRedirect=false;
		if (isset($input['_update'])){
			$addMessAfterRedirect=true;
		}
		if (isset($input['_no_message']) || !$this->auto_message_on_action){
			$addMessAfterRedirect=false;
		}
		

		if ($addMessAfterRedirect) {

			addMessageAfterRedirect($LANG["common"][71].": <a href='" . $CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$this->type] . "?ID=" . $this->fields['ID'] . "'>" .
			(isset($this->fields["name"]) && !empty($this->fields["name"]) ? stripslashes($this->fields["name"]) : "(".$this->fields['ID'].")") . "</a>");
		} 
	}

	/**
	 * Prepare input datas for updating the item
	 *
	 *@param $input datas used to update the item
	 * 
	 *@return the modified $input array 
	 * 
	**/
	function prepareInputForUpdate($input) {
		return $input;
	}

	/**
	 * Actions done after the UPDATE of the item in the database
	 *
	 *@param $input datas used to update the item
	 *@param $updates array of the updated fields 
	 *@param $history store changes history ? 
	 * 
	 *@return nothing 
	 * 
	**/
	function post_updateItem($input,$updates,$history=1) {
	}

	/**
	 * Actions done before the UPDATE of the item in the database
	 *
	 *@param $input datas used to update the item
	 *@param $updates array of the updated fields
	 *@param $oldvalues old values of updated fields
	 * 
	 *@return nothing
	 * 
	**/
	function pre_updateInDB($input,$updates,$oldvalues=array()) {
		return array($input,$updates);
	}

	/**
	 * Delete an item in the database.
	 *
	 *@param $input array : the _POST vars returned bye the item form when press delete
	 *@param $force boolean : force deletion
	 *@param $history boolean : do history log ?
	 *
	 *
	 *@return Nothing ()
	 *
	 **/
	function delete($input,$force=0,$history=1) {
		global $DB;
		
		if ($DB->isSlave())
			return false;

		$input['_item_type_']=$this->type;
		if ($force){
			$input=doHookFunction("pre_item_purge",$input);
			if (isset($input['purge'])){
				$input['_purge']=$input['purge'];
				unset($input['purge']);
			}
		} else {
			$input=doHookFunction("pre_item_delete",$input);
			if (isset($input['delete'])){
				$input['_delete']=$input['delete'];
				unset($input['delete']);
			}
		}

		if ($this->getFromDB($input[$this->getIndexName()])){
			if ($this->pre_deleteItem($this->fields["ID"])){
				if ($this->deleteFromDB($this->fields["ID"],$force)){
					if ($force){
						$this->addMessageOnPurgeAction($input);
						doHook("item_purge",array("type"=>$this->type, "ID" => $this->fields["ID"]));
					} else {
						$this->addMessageOnDeleteAction($input);

						if ($this->dohistory&&$history){
							$changes[0] = 0;
							$changes[1] = $changes[2] = "";
				
							historyLog ($this->fields["ID"],$this->type,$changes,0,HISTORY_DELETE_ITEM);
						}

						doHook("item_delete",array("type"=>$this->type, "ID" => $this->fields["ID"]));
					}
				}
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}

	}

	/**
	 * Add a message on delete action
	 *
	 *@param $input array : the _POST vars returned bye the item form when press add
	 *
	**/
	function addMessageOnDeleteAction($input){
		global $INFOFORM_PAGES, $CFG_GLPI, $LANG;

		if (!isset($INFOFORM_PAGES[$this->type])){
			return;
		}

		if (!in_array($this->table,$CFG_GLPI["deleted_tables"])){
			return;
		}

		$addMessAfterRedirect=false;
		if (isset($input['_delete'])){
			$addMessAfterRedirect=true;
		}
		if (isset($input['_no_message']) || !$this->auto_message_on_action){
			$addMessAfterRedirect=false;
		}

		if ($addMessAfterRedirect) {
			addMessageAfterRedirect($LANG["common"][72] . 
			": <a href='" . $CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$this->type] . "?ID=" . $this->fields['ID'] . "'>" .
			(isset($this->fields["name"]) && !empty($this->fields["name"]) ? stripslashes($this->fields["name"]) : "(".$this->fields['ID'].")") . "</a>");
		} 
	}

	/**
	 * Add a message on purge action
	 *
	 *@param $input array : the _POST vars returned bye the item form when press add
	 *
	**/
	function addMessageOnPurgeAction($input){
		global $INFOFORM_PAGES, $CFG_GLPI, $LANG;

		if (!isset($INFOFORM_PAGES[$this->type])){
			return;
		}

		$addMessAfterRedirect=false;
		if (isset($input['_purge'])){
			$addMessAfterRedirect=true;
		}
		if (isset($input['_no_message']) || !$this->auto_message_on_action){
			$addMessAfterRedirect=false;
		}

		if ($addMessAfterRedirect) {
			addMessageAfterRedirect($LANG["common"][73].": ".(isset($this->fields["name"]) && !empty($this->fields["name"]) ? stripslashes($this->fields["name"]) : "(".$this->fields['ID'].")"));
		} 
	}


	
	/**
	 * Actions done before the DELETE of the item in the database / Maybe used to add another check for deletion 
	 *
	 *@param $ID ID of the item to delete
	 * 
	 *@return bool : true if item need to be deleted else false
	 * 
	**/
	function pre_deleteItem($ID) {
		return true;
	}
	/** 
	 * Restore an item trashed in the database. 
	 * 
	 *@param $input array : the _POST vars returned bye the item form when press restore 
	 *@param $history boolean : do history log ?
	 * 
	 *@return Nothing () 
	 *@todo specific ones : cartridges / consumables : more reuse than restore
	 * 
	**/ 
	// specific ones : cartridges / consumables
	function restore($input,$history=1) {

		if (isset($input['restore'])){
			$input['_restore']=$input['restore'];
			unset($input['restore']);
		}

		$input['_item_type_']=$this->type;
		$input=doHookFunction("pre_item_restore",$input);

		if ($this->getFromDB($input[$this->getIndexName()])){
			if ($this->restoreInDB($input["ID"])){
				$this->addMessageOnRestoreAction($input);

				if ($this->dohistory&&$history){
					$changes[0] = 0;
					$changes[1] = $changes[2] = "";
	
					historyLog ($input["ID"],$this->type,$changes,0,HISTORY_RESTORE_ITEM);
				}
	
				doHook("item_restore",array("type"=>$this->type, "ID" => $input["ID"]));
			}
		}
	}

	/**
	 * Add a message on restore action
	 *
	 *@param $input array : the _POST vars returned bye the item form when press add
	 *
	**/
	function addMessageOnRestoreAction($input){
		global $INFOFORM_PAGES, $CFG_GLPI, $LANG;

		if (!isset($INFOFORM_PAGES[$this->type])){
			return;
		}

		$addMessAfterRedirect=false;
		if (isset($input['_restore'])){
			$addMessAfterRedirect=true;
		}
		if (isset($input['_no_message']) || !$this->auto_message_on_action){
			$addMessAfterRedirect=false;
		}

		if ($addMessAfterRedirect) {
			addMessageAfterRedirect($LANG["common"][74] . 
			": <a href='" . $CFG_GLPI["root_doc"]."/".$INFOFORM_PAGES[$this->type] . "?ID=" . $this->fields['ID'] . "'>" .
			(isset($this->fields["name"]) && !empty($this->fields["name"]) ? stripslashes($this->fields["name"]) : "(".$this->fields['ID'].")") . "</a>");
		} 
	}

	/**
	 * Reset fields of the item 
	 *
	**/
	function reset(){
		$this->fields=array();

	}

	/**
	 * Define onglets to display / WILL BE DELETED : use defineTabs instead
	 *
	 *@param $withtemplate is a template view ?
	 * 
	 *@return array containing the onglets
	 * 
	**/
	function defineOnglets($withtemplate){
		return array();
	}

	/**
	 * Define tabs to display
	 *
	 *@param $withtemplate is a template view ?
	 * 
	 *@return array containing the onglets
	 * 
	**/
	function defineTabs($ID,$withtemplate){
		return array();
	}

	/**
	 * Show onglets / WILL BE DELETED : use showTabs instead
	 *
	 *@param $ID ID of the item to display
	 *@param $withtemplate is a template view ?
	 *@param $actif active onglet
	 *@param $nextprevcondition condition used to find next/previous items
	 *@param $nextprev_item field used to define next/previous items
	 *@param $addurlparam parameters to add to the URLs 
	 * 
	 *@return Nothing () 
	 *  
	**/
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
			//	$onglets[11]=$LANG["Menu"][17];
			//	ksort($onglets);
			//}
			foreach ($onglets as $key => $val ) {
				echo "<li "; if ($actif==$key){ echo "class='actif'";} echo  "><a href='$target&amp;onglet=$key$template$addurlparam'>".$val."</a></li>";
			}
			if(empty($withtemplate)){
				echo "<li class='invisible'>&nbsp;</li>";
				echo "<li "; if ($actif=="-1") {echo "class='actif'";} echo "><a href='$target&amp;onglet=-1$template$addurlparam'>".$LANG["common"][66]."</a></li>";
			}
		}
	
	
	
		displayPluginHeadings($target,$this->type,$withtemplate,$actif);
	
		echo "<li class='invisible'>&nbsp;</li>";
	
		if (empty($withtemplate)&&preg_match("/\?ID=([0-9]+)/",$target,$ereg)){
			$ID=$ereg[1];
			$next=getNextItem($this->table,$ID,$nextprevcondition,$nextprev_item);
			$prev=getPreviousItem($this->table,$ID,$nextprevcondition,$nextprev_item);
			$cleantarget=preg_replace("/\?ID=([0-9]+)/","",$target);
			if ($prev>0) {
				echo "<li><a href='$cleantarget?ID=$prev$addurlparam'><img src=\"".$CFG_GLPI["root_doc"]."/pics/left.png\" alt='".$LANG["buttons"][12]."' title='".$LANG["buttons"][12]."'></a></li>";
			}
			if ($next>0) {
				echo "<li><a href='$cleantarget?ID=$next$addurlparam'><img src=\"".$CFG_GLPI["root_doc"]."/pics/right.png\" alt='".$LANG["buttons"][11]."' title='".$LANG["buttons"][11]."'></a></li>";
			}
		}
	
		echo "</ul></div>";
	} 


	/**
	 * Show onglets 
	 *
	 *@param $ID ID of the item to display
	 *@param $withtemplate is a template view ?
	 *@param $actif active onglet
	 *@param $addparams array of parameters to add to URLs and ajax
	 *@param $nextprevcondition condition used to find next/previous items
	 *@param $nextprev_item field used to define next/previous items
	 * 
	 *@return Nothing () 
	 *  
	**/
	function showTabs($ID,$withtemplate,$actif,$addparams=array(),$nextprevcondition="",$nextprev_item=""){
		global $LANG,$CFG_GLPI,$INFOFORM_PAGES;

		$target=$_SERVER['PHP_SELF'];
	
		$template="";
		$templatehtml="";
		if(!empty($withtemplate)){
			$template="&withtemplate=$withtemplate";
			$templatehtml="&amp;withtemplate=$withtemplate";
		}
		$extraparamhtml="";
		$extraparam="";
		if (is_array($addparams)&&count($addparams)){
			foreach ($addparams as $key => $val){
				$extraparamhtml.="&amp;$key=$val";
				$extraparam.="&$key=$val";
			}
		}

		if (empty($withtemplate)&&$ID){
			echo "<div>";
			$next=getNextItem($this->table,$ID,$nextprevcondition,$nextprev_item);
			$prev=getPreviousItem($this->table,$ID,$nextprevcondition,$nextprev_item);
			$cleantarget=preg_replace("/\?ID=([0-9]+)/","",$target);
			if ($prev>0) {
				echo "<a href='$cleantarget?ID=$prev$extraparamhtml'><img src=\"".$CFG_GLPI["root_doc"]."/pics/left.png\" alt='".$LANG["buttons"][12]."' title='".$LANG["buttons"][12]."'></a>";
			}

			echo "&nbsp;&nbsp;<a href=\"javascript:showHideDiv('tabsbody','tabsbodyimg', '".GLPI_ROOT."/pics/deplier_down.png','".GLPI_ROOT."/pics/deplier_up.png');\">";
			echo "<img alt='' name='tabsbodyimg' src=\"".GLPI_ROOT."/pics/deplier_up.png\">";
			echo "</a>";
			
			if ($next>0) {
				echo "&nbsp;&nbsp;<a href='$cleantarget?ID=$next$extraparamhtml'><img src=\"".$CFG_GLPI["root_doc"]."/pics/right.png\" alt='".$LANG["buttons"][11]."' title='".$LANG["buttons"][11]."'></a>";
			}
			echo "</div>";
		}
	
		
		echo "<div id='tabspanel' class='center-h'></div>";
		
		$active=0;
		if (count($onglets=$this->defineTabs($ID,$withtemplate))){
			$patterns[0] = '/front/';
			$patterns[1] = '/form/';
			$replacements[0] = 'ajax';
			$replacements[1] = 'tabs';
			$tabpage=preg_replace($patterns, $replacements, $INFOFORM_PAGES[$this->type]);
			$tabs=array();
			foreach ($onglets as $key => $val ) {
				$tabs[$key]=array('title'=>$val,
						'url'=>$CFG_GLPI['root_doc']."/$tabpage",
						'params'=>"target=$target&type=".$this->type."&glpi_tab=$key&ID=$ID$template$extraparam");
			}			
			$plug_tabs=getPluginTabs($target,$this->type,$ID,$withtemplate);
			$tabs+=$plug_tabs;
			// Not all tab for templates
			if(empty($withtemplate)){
				$tabs[-1]=array('title'=>$LANG["common"][66],
						'url'=>$CFG_GLPI['root_doc']."/$tabpage",
						'params'=>"target=$target&type=".$this->type."&glpi_tab=-1&ID=$ID$template$extraparam");
			}

			createAjaxTabs('tabspanel','tabcontent',$tabs,$actif);

		}
	} 

	/**
	 * Have I the right to "write" the Object
	 * 
	 * @return Array of can_edit (can write) + can_recu (can make recursive)
	**/
/*	function canEditAndRecurs () {
		global $CFG_GLPI;
		
		$can_edit = $this->canCreate();

		if (!isset($CFG_GLPI["recursive_type"][$this->type])) {
			$can_recu = false;
			
		} else if (!isset($this->fields["ID"])) {
			$can_recu = haveRecursiveAccessToEntity($_SESSION["glpiactive_entity"]);
				
		} else {
			if ($this->fields["recursive"]) {
				$can_edit = $can_edit && haveRecursiveAccessToEntity($this->fields["FK_entities"]);
				$can_recu = $can_edit;
			}	
			else {
				$can_recu = $can_edit && haveRecursiveAccessToEntity($this->fields["FK_entities"]);	
			}
		}
	
		return array($can_edit, $can_recu);		
	}
*/	
	/**
	 * Have I the right to "write" the Object
	 *
	 * @return bitmask : 0:no, 1:can_edit (can write), 2:can_recu (can make recursive)
	**/
/*	function canEdit () {
		list($can_edit,$can_recu)=$this->canEditAndRecurs();
		return ($can_edit?1:0)+($can_recu?2:0);
	}
*/


	/**
	 * Have I the right to "create" the Object
	 * 
	 * May be overloaded if needed (ex kbitem)
	 *
	 * @return booleen
	 **/
	function canCreate () {
		return haveTypeRight($this->type,"w");
	}

	/**
	 * Have I the right to "view" the Object
	 * 
	 * May be overloaded if needed
	 *
	 * @return booleen
	 **/
	function canView () {
		return haveTypeRight($this->type,"r");
	}

	/**
	 * Can I change recusvive flag to false
	 * check if there is "linked" object in another entity
	 * 
	 * May be overloaded if needed
	 *
	 * @return booleen
	 **/
	function canUnrecurs () {

		global $DB, $LINK_ID_TABLE, $CFG_GLPI;
			
		$ID  = $this->fields['ID'];	
		
		if ($ID<0 || !$this->fields['recursive']) {
			return true;
		}
		$entities = "(".$this->fields['FK_entities'];
		foreach (getEntityAncestors($this->fields['FK_entities']) as $papa) {
			$entities .= ",$papa";
		}
		$entities .= ")";

		$RELATION=getDbRelations();
	
		if (isset($RELATION[$this->table])){
			foreach ($RELATION[$this->table] as $tablename => $field){
				if (in_array($tablename,$CFG_GLPI["specif_entities_tables"])) {
					// 1->N Relation
					if (is_array($field)) {
						foreach ($field as $f) {
							if (countElementsInTable($tablename, "$f=$ID AND FK_entities NOT IN $entities")>0) {
								return false;
							}
						}
					} else {
						if (countElementsInTable($tablename, "$field=$ID AND FK_entities NOT IN $entities")>0) {
							return false;
						}
					}
				} else {
					foreach ($RELATION as $othertable => $rel) {	

						// Search for a N->N Relation with devices
						if ($othertable == "_virtual_device"					
							&& isset($rel[$tablename])) {

							$devfield  = $rel[$tablename][0]; // FK_device, on_device, end1...
							$typefield = $rel[$tablename][1]; // device_type, type, ...
							
							$sql = "SELECT DISTINCT $typefield AS type FROM $tablename WHERE $field=$ID";
							$res = $DB->query($sql);
							
							// Search linked device of each type
							if ($res) while ($data = $DB->fetch_assoc($res)) {
								$type=$data["type"];
								if (isset($LINK_ID_TABLE[$type]) && 
									in_array($device=$LINK_ID_TABLE[$type], $CFG_GLPI["specif_entities_tables"])) {

									if (countElementsInTable("$tablename, $device", 
										"$tablename.$field=$ID AND $tablename.$typefield=$type AND $tablename.$devfield=$device.ID AND $device.FK_entities NOT IN $entities")>0) {
											return false;											
									}
								}			
							}
							
						// Search for another N->N Relation 			
						} else if ($othertable != $this->table 
								&& isset($rel[$tablename]) 
								&& in_array($othertable,$CFG_GLPI["specif_entities_tables"])) {

							if (is_array($rel[$tablename])) {
								foreach ($rel[$tablename] as $otherfield){
									if (countElementsInTable("$tablename, $othertable", "$tablename.$field=$ID AND $tablename.$otherfield=$othertable.ID AND $othertable.FK_entities NOT IN $entities")>0) {
										return false;
									}
								}
							} else {
								$otherfield = $rel[$tablename];							
								if (countElementsInTable("$tablename, $othertable", "$tablename.$field=$ID AND $tablename.$otherfield=$othertable.ID AND $othertable.FK_entities NOT IN $entities")>0) {
									return false;
								}
							}						
						}
					}
				}
			}
		}
		
		// Doc links to this item
		if ($this->type > 0
			&& countElementsInTable("glpi_doc_device, glpi_docs",
				"glpi_doc_device.FK_device=$ID AND glpi_doc_device.device_type=".$this->type." AND glpi_doc_device.FK_doc=glpi_docs.ID AND glpi_docs.FK_entities NOT IN $entities")>0) {
					return false;                       
		} 
		// TODO : do we need to check all relations in $RELATION["_virtual_device"] for this item

		return true;
	}

	/**
	 * 
	 * Display a 2 columns Header 1 for ID, 1 for recursivity menu
	 * 
	 * @param $ID ID of the item (-1 if new item)
	 * @param $withtemplate empty or 1 for newtemplate, 2 for newobject from template
	 * @param $colspan for each column
	 * 
	 */
	 function showFormHeader ($ID, $withtemplate='', $colspan=1) {
	 	
	 	global $LANG, $CFG_GLPI;
	 	
		echo "<tr><th colspan='$colspan'>";

		if (!empty($withtemplate) && $withtemplate == 2 && $ID>0) {
			
			echo "<input type='hidden' name='tplname' value='".$this->fields["tplname"]."'>";
			echo $LANG["buttons"][8] . " - " . $LANG["common"][13] . ": " . $this->fields["tplname"];
			
		} else if (!empty($withtemplate) && $withtemplate == 1) {
			
			echo "<input type='hidden' name='is_template' value='1' />\n";
			echo $LANG["common"][6].": "; 
			autocompletionTextField("tplname",$this->table,"tplname",$this->fields["tplname"],25,$this->fields["FK_entities"]); 			
		
		} else if (empty($ID)||$ID<0){

			echo $LANG["common"][87];

		} else {

			echo $LANG["common"][2]." $ID";
		}
		
		if (isMultiEntitiesMode()){
			echo "&nbsp;(".getDropdownName("glpi_entities",$this->fields["FK_entities"]).")";
		}
		
		echo "</th><th colspan='$colspan'>";

		if ($this->may_be_recursive && isMultiEntitiesMode()){
			echo $LANG["entity"][9].":&nbsp;";
		
			if (!$this->can($ID,'recursive')) {
				echo getYesNo($this->fields["recursive"]);
				$comment=$LANG["common"][86];
				$image="/pics/lock.png";
			} else if (!$this->canUnrecurs()) {
				echo getYesNo($this->fields["recursive"]);
				$comment=$LANG["common"][84];
				$image="/pics/lock.png";
			} else {
				dropdownYesNo("recursive",$this->fields["recursive"]);
				$comment=$LANG["common"][85];
				$image="/pics/aide.png";
			}
			$rand=mt_rand();
			echo "&nbsp;<img alt='' src='".$CFG_GLPI["root_doc"].$image."' onmouseout=\"cleanhide('comments_recursive$rand')\" onmouseover=\"cleandisplay('comments_recursive$rand')\">";
			echo "<span class='over_link' id='comments_recursive$rand'>$comment</span>";
			
		} else {
			echo "&nbsp;";
		}
		echo "</th></tr>\n";
	 }
	 
	/**
	 * Check right on an item
	 *
	 * @param $ID ID of the item (-1 if new item)
	 * @param $right Right to check : r / w / recursive
	 * @param $entity entity to check right (used for adding item)
	 *
	 * @return boolean
	**/
	function can($ID,$right,$entity=-1){

		$entity_to_check=-1;
		$recursive_state_to_check=0;
		// Get item if not already loaded
		if (empty($ID)||$ID<=0){
//			if (!isset($this->fields["ID"]) || strlen($this->fields["ID"])) {
//				$this->getEmpty();
//			}
			// No entity define : adding process : use active entity
			if ($entity==-1){
				$entity_to_check=$_SESSION["glpiactive_entity"];
			} else { 
				$entity_to_check=$entity;
			}
		} else {
			if (!isset($this->fields['ID'])||$this->fields['ID']!=$ID){
				// Item not found : no right
				if (!$this->getFromDB($ID)){
					return false;
				}
			}
			if ($this->isEntityAssign()){
				$entity_to_check=$this->getEntityID();
				if ($this->maybeRecursive()){
					$recursive_state_to_check=$this->isRecursive();
				}
			}

		} 

//		echo $ID."_".$entity_to_check."_".$recursive_state_to_check.'<br>';
		switch ($right){
			case 'r':
				// Personnal item
				if ($this->may_be_private && $this->fields['private'] && $this->fields['FK_users']==$_SESSION["glpiID"]){
					return true;
				} else {
					// Check Global Right
					if ($this->canView()){
						// Is an item assign to an entity
						if ($this->isEntityAssign()){
							// Can be recursive check 
							if ($this->maybeRecursive()){
								return haveAccessToEntity($entity_to_check,$recursive_state_to_check);
							} else { // Non recursive item
								return haveAccessToEntity($entity_to_check);
							}
						} else { // Global item
							return true;
						}
					}
				}
				break;
			case 'w':
				// Personnal item
				if ($this->may_be_private && $this->fields['private'] && $this->fields['FK_users']==$_SESSION["glpiID"]){
					return true;
				} else {
					// Check Global Right
					if ($this->canCreate()){
						// Is an item assign to an entity
						if ($this->isEntityAssign()){
							// Have access to entity
							return haveAccessToEntity($entity_to_check);
						} else { // Global item
							return true;
						}
					}
				}
				break;
			case 'recursive':
				if ($this->isEntityAssign() && $this->maybeRecursive()){
					if ($this->canCreate() && haveAccessToEntity($entity_to_check)){
						// Can make recursive if recursive access to entity
						return haveRecursiveAccessToEntity($entity_to_check);
					}
				}
				break;
		}
		return false;

	}
	/**
	 * Check right on an item with block
	 *
	 * @param $ID ID of the item (-1 if new item)
	 * @param $right Right to check : r / w / recursive
	 * @param $entity entity to check right (used for adding item)
	 * @return nothing
	**/
	function check($ID,$right,$entity=-1) {
		global $CFG_GLPI;
	
		// Check item exists
		if ($ID>0 && !$this->getFromDB($ID)){
			// Gestion timeout session
			if (!isset ($_SESSION["glpiID"])) {
				glpi_header($CFG_GLPI["root_doc"] . "/index.php");
				exit ();
			}

			displayNotFoundError();			
	
		} else {
			if (!$this->can($ID,$right,$entity)) {
				// Gestion timeout session
				if (!isset ($_SESSION["glpiID"])) {
					glpi_header($CFG_GLPI["root_doc"] . "/index.php");
					exit ();
				}
				displayRightError();
			}
		}
	}
	/**
	 * Is the object assigned to an entity
	 * 
	 * @return boolean
	**/
	function isEntityAssign () {
		return $this->entity_assign;
	}
	/**
	 * Get the ID of entity assigned to the object
	 * 
	 * Can be overloaded (ex : infocom)
	 * 
	 * @return ID of the entity 
	**/
	function getEntityID () {
		if ($this->entity_assign && isset($this->fields["FK_entities"])) {
			return $this->fields["FK_entities"];		
		} 
		return  -1;
	}	
	/**
	 * Is the object may be recursive
	 * 
	 * @return boolean
	**/
	function maybeRecursive () {
		return $this->may_be_recursive;
	}
	/**
	 * Is the object recursive
	 * 
	 * Can be overloaded (ex : infocom)
	 * 
	 * @return integer (0/1) 
	**/
	function isRecursive () {
		if ($this->may_be_recursive) {
			return $this->fields["recursive"];		
		} 
		return 0;
	}	
}

?>
