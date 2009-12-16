<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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

/**
 *  Common DataBase Table Manager Class - Persistent Object
 */
class CommonDBTM extends CommonGLPI {

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
   /// Black list fields for history log or date mod update
   var $history_blacklist	= array();
   /// Set false to desactivate automatic message on action
   var $auto_message_on_action=true;

   /**
   * Constructor
   **/
   function __construct () {
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
      if (strlen($ID)==0) {
         return false;
      }

      $query = "SELECT *
                FROM `".$this->table."`
                WHERE `".$this->getIndexName()."` = '$ID'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)==1) {
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

      $query = "SELECT *
                FROM `".$this->table."`";
      if (!empty($condition)) {
         $query.=" WHERE $condition";
      }

      if (!empty($order)){
         $query.=" ORDER BY $order";
      }
      if (!empty($limit)){
         $query.=" LIMIT ".intval($limit);
      }

      $data=array();
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            while ($line = $DB->fetch_assoc($result)) {
               $data[$line['id']]=$line;
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
   function getIndexName() {
      return "id";
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

      if ($fields = $DB->list_fields($this->table)) {
         foreach ($fields as $key => $val) {
            $this->fields[$key] = "";
         }
      } else {
         return false;
      }
      if (isset($this->fields['entities_id']) && isset($_SESSION["glpiactive_entity"])) {
         $this->fields['entities_id']=$_SESSION["glpiactive_entity"];
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
   * @param $updates fields to update
   * @param $oldvalues old values of the updated fields
   *@return nothing
   *
   **/
   function updateInDB($updates,$oldvalues=array()) {
      global $DB,$CFG_GLPI;

      foreach ($updates as $field) {
         if (isset($this->fields[$field])) {
            $query  = "UPDATE `".
                       $this->table."`
                       SET `";
            $query .= $field."`";

            if ($this->fields[$field]=="NULL") {
               $query .= " = ";
               $query .= $this->fields[$field];
            } else {
               $query .= " = '";
               $query .= $this->fields[$field]."'";
            }
            $query .= " WHERE `id` ='";
            $query .= $this->fields["id"];
            $query .= "'";

            if (!$DB->query($query)) {
               if (isset($oldvalues[$field])) {
                  unset($oldvalues[$field]);
               }
            }
         } else {
            // Clean oldvalues
            if (isset($oldvalues[$field])) {
               unset($oldvalues[$field]);
            }
         }
      }

      if(count($oldvalues)) {
         constructHistory($this->fields["id"],$this->type,$oldvalues,$this->fields);
      }

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

      //unset($this->fields["id"]);
      $nb_fields=count($this->fields);
      if ($nb_fields>0) {
         // Build query
         $query = "INSERT INTO `".
                   $this->table."` (";
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
            if ($values[$i]=='NULL') {
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
            $this->fields["id"]=$DB->insert_id();
            return $this->fields["id"];
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
   *@return true if succeed else false
   *
   **/
   function restoreInDB($ID) {
      global $DB,$CFG_GLPI;

      if (in_array($this->table,$CFG_GLPI["deleted_tables"])) {
         $query = "UPDATE `".
                   $this->table."`
                   SET `is_deleted`='0'
                   WHERE `id` = '$ID'";
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
   *@param $ID integer ID of the current item
   *@param $force force the purge of the item (not used if the table do not have a deleted field)
   *
   *@return true if succeed else false
   *
   **/
   function deleteFromDB($ID,$force=0) {
      global $DB,$CFG_GLPI;

      if ($force==1 || !in_array($this->table,$CFG_GLPI["deleted_tables"])) {
         $this->cleanDBonPurge($ID);
         $this->cleanHistory($ID);
         $this->cleanRelationData($ID);
         $this->cleanRelationTable($ID);

         $query = "DELETE
                   FROM `".$this->table."`
                   WHERE `id` = '$ID'";

         if ($result = $DB->query($query)) {
            $this->post_deleteFromDB($ID);
            return true;
         } else {
            return false;
         }
      }else {
         $query = "UPDATE `".
                   $this->table."`
                   SET `is_deleted`='1'
                   WHERE `id` = '$ID'";
         $this->cleanDBonMarkDeleted($ID);

         if ($result = $DB->query($query)) {
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

      if ($this->dohistory) {
         $query = "DELETE
                   FROM `glpi_logs`
                   WHERE (`itemtype` = '".$this->type."'
                          AND `items_id` = '$ID')";
         $DB->query($query);
      }
   }

   /**
   * Clean data in the tables which have linked the deleted item
   * Clear 1/N Relation
   *
   * @param $ID integer : ID of the item
   *
   *@return nothing
   *
   **/
   function cleanRelationData($ID) {
      global $DB, $CFG_GLPI;

      $RELATION=getDbRelations();
      if (isset($RELATION[$this->table])) {
         $newval = (isset($this->input['_replace_by']) ? $this->input['_replace_by'] : 0);
         foreach ($RELATION[$this->table] as $tablename => $field) {
            if ($tablename[0]!='_') {
               if (!is_array($field)) {
                  $query="UPDATE
                          `$tablename`
                          SET `$field` = '$newval'
                          WHERE `$field`='$ID' ";
                  $DB->query($query);
               } else {
                  foreach ($field as $f) {
                     $query="UPDATE
                             `$tablename`
                             SET `$f` = '$newval'
                             WHERE `$f`='$ID' ";
                     $DB->query($query);
                  }
               }
            }
         }
      }

      // Clean ticket open against the item
      if (in_array($this->type,$CFG_GLPI["helpdesk_types"])) {
         $job=new Ticket;

         $query = "SELECT *
                   FROM `glpi_tickets`
                   WHERE `items_id` = '$ID'
                     AND `itemtype`='".$this->type."'";
         $result = $DB->query($query);

         if ($DB->numrows($result)) {
            while ($data=$DB->fetch_array($result)) {
               if ($CFG_GLPI["keep_tickets_on_delete"]==1) {
                  // TODO : use update method for history/notify ? check state != old ?
                  $query = "UPDATE
                            `glpi_tickets`
                            SET `items_id` = '0', `itemtype` = '0'
                            WHERE `id`='".$data["id"]."';";
                  $DB->query($query);
               } else {
                   $job->delete(array("id"=>$data["id"]));
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
   function post_deleteFromDB($ID) {
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
    * Clean the date in the relation tables for the deleted item
    * Clear N/N Relation
    *
    */
   function cleanRelationTable ($ID) {
      global $CFG_GLPI, $DB;

      // If this type have INFOCOM, clean one associated to purged item
      if (in_array($this->type,$CFG_GLPI['infocom_types'])) {
         $infocom = new Infocom();
         if ($infocom->getFromDBforDevice($this->type, $ID)) {
             $infocom->delete(array('id'=>$infocom->fields['id']));
         }
      }

      // If this type have NETPORT, clean one associated to purged item
      if (in_array($this->type,$CFG_GLPI['netport_types'])) {
         $query = "SELECT `id`
                   FROM `glpi_networkports`
                   WHERE (`items_id` = '$ID'
                          AND `itemtype` = '".$this->type."')";
         $result = $DB->query($query);

         while ($data = $DB->fetch_array($result)) {
            $q = "DELETE
                  FROM `glpi_networkports_networkports`
                  WHERE (`networkports_id_1` = '".$data["id"]."'
                         OR `networkports_id_2` = '".$data["id"]."')";
            $result2 = $DB->query($q);
         }

         $query = "DELETE
                   FROM `glpi_networkports`
                   WHERE (`items_id` = '$ID'
                          AND `itemtype` = '".$this->type."')";
         $result = $DB->query($query);
      }

      // If this type is RESERVABLE clean one associated to purged item
      if (in_array($this->type,$CFG_GLPI['reservation_types'])) {
         $rr=new ReservationItem();
         if ($rr->getFromDBbyItem($this->type, $ID)) {
             $rr->delete(array('id'=>$infocom->fields['id']));
         }
      }

      // If this type have CONTRACT, clean one associated to purged item
      if (in_array($this->type,$CFG_GLPI['contract_types'])) {
         $ci = new Contract_Item();
         $ci->cleanDBonItemDelete($this->type,$ID);
      }

      // If this type have DOCUMENT, clean one associated to purged item
      if (in_array($this->type,$CFG_GLPI["doc_types"])) {
         $di = new Document_Item();
         $di->cleanDBonItemDelete($this->type,$ID);
      }
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
   *@param $input array : the _POST vars returned by the item form when press add
   *
   *@return integer the new ID of the added item (or false if fail)
   **/
   function add($input) {
      global $DB;

      $addMessAfterRedirect = false;

      if ($DB->isSlave()) {
         return false;
      }

      // Store input in the object to be available in all sub-method / hook
      $this->input = $input;

      // Call the plugin hook - $this->input can be altered
      doHook("pre_item_add", $this);

      if ($this->input && is_array($this->input)) {
         if (isset($this->input['add'])) {
            $this->input['_add']=$this->input['add'];
            unset($this->input['add']);
         }
         $this->input=$this->prepareInputForAdd($this->input);
      }

      if ($this->input && is_array($this->input)) {
         $this->fields=array();
         $table_fields=$DB->list_fields($this->table);

         // fill array for add
         foreach ($this->input as $key => $val) {
            if ($key[0]!='_' && isset($table_fields[$key])) {
               $this->fields[$key] = $this->input[$key];
            }
         }
         // Auto set date_mod if exsist
         if (isset($table_fields['date_mod'])) {
            $this->fields['date_mod']=$_SESSION["glpi_currenttime"];
         }

         if ($newID= $this->addToDB()) {
            $this->addMessageOnAddAction($this->input);
            $this->post_addItem($newID, $this->input);
            doHook("item_add", $this);
            return $newID;
         }
      }
      return false;
   }

   /**
    * Get the link to an item
    *
    * @param $with_comment Display comments
    *
    * @return string : HTML link
    */
   function getLink($with_comment=0) {
      global $CFG_GLPI;

      if (!isset($this->fields['id'])) {
         return '';
      }
      $link_item = getItemTypeFormURL($this->type);
      if (!$this->can($this->fields['id'],'r')) {
         return $this->getNameID($with_comment);
      }

      $link  = $link_item;
      $link .= (strpos($link,'?') ? '&amp;':'?').'id=' . $this->fields['id'];
      $link .= ($this->getField('is_template') ? "&amp;withtemplate=1" : "");

      return "<a href='$link'>".$this->getNameID($with_comment)."</a>";
   }

   /**
   * Add a message on add action
   *
   *@param $input array : the _POST vars returned by the item form when press add
   *
   **/
   function addMessageOnAddAction($input) {
      global
      $CFG_GLPI, $LANG;

      $link=getItemTypeFormURL($this->type);
      if (!isset($link)) {
         return;
      }

      $addMessAfterRedirect=false;
      if (isset($input['_add'])) {
         $addMessAfterRedirect=true;
      }
      if (isset($input['_no_message']) || !$this->auto_message_on_action) {
         $addMessAfterRedirect=false;
      }

      if ($addMessAfterRedirect) {
         addMessageAfterRedirect($LANG['common'][70] . "&nbsp;: " . $this->getLink());
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
   *@param $input array : the _POST vars returned by the item form when press update
   *@param $history boolean : do history log ?
   *
   *@return boolean : true on success
   *
   **/
   function update($input,$history=1) {
      global $DB;

      if ($DB->isSlave()) {
         return false;
      }

      $input=doHookFunction("pre_item_update",$input);
      $input=$this->prepareInputForUpdate($input);

      if (isset($input['update'])) {
         $input['_update']=$input['update'];
         unset($input['update']);
      }
      // Valid input
      if ($input && is_array($input)
          && $this->getFromDB($input[$this->getIndexName()])) {
         // Fill the update-array with changes
         $x=0;
         $updates=array();
         $oldvalues=array();
         foreach ($input as $key => $val) {
            if (array_key_exists($key,$this->fields)) {
               // Prevent history for date statement (for date for example)
               if ( is_null($this->fields[$key]) && $input[$key]=='NULL') {
                  $this->fields[$key]='NULL';
               }
               if ( $this->fields[$key] != stripslashes($input[$key])) {
                  if ($key!="id") {
                     // Store old values
                     if (!in_array($key,$this->history_blacklist)) {
                        $oldvalues[$key]=$this->fields[$key];
                     }
                     $this->fields[$key] = $input[$key];
                     $updates[$x] = $key;
                     $x++;
                  }
               }
            }
         }
         if(count($updates)) {
            if (array_key_exists('date_mod',$this->fields)) {
               // is a non blacklist field exists
               if (count(array_diff($updates,$this->history_blacklist)) > 0) {
                  $this->fields['date_mod']=$_SESSION["glpi_currenttime"];
                  $updates[$x++] = 'date_mod';
               }
            }
            list($input,$updates)=$this->pre_updateInDB($input,$updates,$oldvalues);

            // CLean old_values history not needed  => Keep old value for plugin hook
            //if (!$this->dohistory || !$history) {
            //   $oldvalues=array();
            //}

            if ($this->updateInDB($updates, ($this->dohistory && $history ? $oldvalues : array()))) {
               $this->addMessageOnUpdateAction($input);
               doHook("item_update",array("type"=>$this->type, "id" => $input["id"],
                      "input"=> $input, "updates" => $updates, "oldvalues" => $oldvalues));
            }
         }
         $this->post_updateItem($input,$updates,$history);
         return true;
      }
      return false;
   }

   /**
   * Add a message on update action
   *
   *@param $input array : the _POST vars returned by the item form when press add
   *
   **/
   function addMessageOnUpdateAction($input) {
      global $CFG_GLPI, $LANG;

      $link=getItemTypeFormURL($this->type);
      if (!isset($link)) {
         return;
      }

      $addMessAfterRedirect=false;
      if (isset($input['_update'])) {
         $addMessAfterRedirect=true;
      }
      if (isset($input['_no_message']) || !$this->auto_message_on_action) {
         $addMessAfterRedirect=false;
      }

      if ($addMessAfterRedirect) {
         addMessageAfterRedirect($LANG['common'][71] . "&nbsp;: " . $this->getLink());
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
   *@param $input array : the _POST vars returned by the item form when press delete
   *@param $force boolean : force deletion
   *@param $history boolean : do history log ?
   *
   *@return boolean : true on success
   *
   **/
   function delete($input,$force=0,$history=1) {
      global $DB;

      if ($DB->isSlave()) {
         return false;
      }

      if (!$this->getFromDB($input[$this->getIndexName()])) {
         return false;
      }

      if ($this->getField('is_template')
            || !isset($this->fields['is_deleted'])) {
         $force = 1;
      }
      // Store input in the object to be available in all sub-method / hook
      $this->input = $input;

      if ($force) {
         doHook("pre_item_purge",$this);
         if (isset($this->input['purge'])) {
            $this->input['_purge']=$this->input['purge'];
            unset($this->input['purge']);
         }
      } else {
         doHook("pre_item_delete",$this);
         if (isset($this->input['delete'])) {
            $this->input['_delete']=$this->input['delete'];
            unset($input['delete']);
         }
      }

      if (!is_array($this->input)) {
         // $input clear by a hook to cancel delete
         return false;
      }
      if ($this->pre_deleteItem($this->fields["id"])) {
         if ($this->deleteFromDB($this->fields["id"], $force)) {
            if ($force) {
               $this->addMessageOnPurgeAction($this->input);
               doHook("item_purge",$this);
            } else {
               $this->addMessageOnDeleteAction($this->input);
               if ($this->dohistory&&$history) {
                  $changes[0] = 0;
                  $changes[1] = $changes[2] = "";

                  historyLog ($this->fields["id"],$this->type,$changes,0,HISTORY_DELETE_ITEM);
               }
               doHook("item_delete",$this);
            }
            return true;
         }
      }
      return false;
   }

   /**
   * Add a message on delete action
   *
   *@param $input array : the _POST vars returned by the item form when press add
   *
   **/
   function addMessageOnDeleteAction($input) {
      global $CFG_GLPI, $LANG;

      $link=getItemTypeFormURL($this->type);
      if (!isset($link)) {
         return;
      }
      if (!in_array($this->table,$CFG_GLPI["deleted_tables"])) {
         return;
      }

      $addMessAfterRedirect=false;
      if (isset($input['_delete'])) {
         $addMessAfterRedirect=true;
      }
      if (isset($input['_no_message']) || !$this->auto_message_on_action) {
         $addMessAfterRedirect=false;
      }
      if ($addMessAfterRedirect) {
         addMessageAfterRedirect($LANG['common'][72] . "&nbsp;: " . $this->getLink());
      }
   }

   /**
   * Add a message on purge action
   *
   *@param $input array : the _POST vars returned by the item form when press add
   *
   **/
   function addMessageOnPurgeAction($input) {
      global $CFG_GLPI, $LANG;

      $link=getItemTypeFormURL($this->type);
      if (!isset($link)) {
         return;
      }

      $addMessAfterRedirect=false;
      if (isset($input['_purge'])) {
         $addMessAfterRedirect=true;
      }
      if (isset($input['_no_message']) || !$this->auto_message_on_action) {
         $addMessAfterRedirect=false;
      }
      if ($addMessAfterRedirect) {
         addMessageAfterRedirect($LANG['common'][73] . "&nbsp;: " . $this->getLink());
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
   *@param $input array : the _POST vars returned by the item form when press restore
   *@param $history boolean : do history log ?
   *
   *@return boolean : true on success
   *@todo specific ones : cartridges / consumables : more reuse than restore
   *
   **/
   // specific ones : cartridges / consumables
   function restore($input,$history=1) {

      if (!$this->getFromDB($input[$this->getIndexName()])) {
         return false;
      }
      if (isset($input['restore'])) {
         $input['_restore']=$input['restore'];
         unset($input['restore']);
      }
      // Store input in the object to be available in all sub-method / hook
      $this->input = $input;
      doHook("pre_item_restore",$this);

      if ($this->restoreInDB($this->input["id"])) {
         $this->addMessageOnRestoreAction($this->input);
         if ($this->dohistory && $history) {
            $changes[0] = 0;
            $changes[1] = $changes[2] = "";
            historyLog ($this->input["id"],$this->type,$changes,0,HISTORY_RESTORE_ITEM);
         }
         doHook("item_restore", $this);

         return true;
      }
      return false;
   }

   /**
   * Add a message on restore action
   *
   *@param $input array : the _POST vars returned by the item form when press add
   *
   **/
   function addMessageOnRestoreAction($input) {
      global $CFG_GLPI, $LANG;

      $link=getItemTypeFormURL($this->type);
      if (!isset($link)) {
         return;
      }

      $addMessAfterRedirect=false;
      if (isset($input['_restore'])) {
         $addMessAfterRedirect=true;
      }
      if (isset($input['_no_message']) || !$this->auto_message_on_action) {
         $addMessAfterRedirect=false;
      }
      if ($addMessAfterRedirect) {
         addMessageAfterRedirect($LANG['common'][74] . "&nbsp;: " . $this->getLink());
      }
   }

   /**
   * Reset fields of the item
   *
   **/
   function reset() {
      $this->fields=array();
   }

   /**
   * Have I the global right to "create" the Object
   *
   * May be overloaded if needed (ex KnowbaseItem)
   *
   * @return booleen
   **/
   function canCreate() {
      return false;
   }

   /**
   * Have I the global right to "delete" the Object
   *
   * Default is calling canCreate
   * May be overloaded if needed
   *
   * @return booleen
   * @see canCreate
   **/
   function canDelete() {
      return $this->canCreate();
   }

   /**
   * Have I the global right to "update" the Object
   *
   * Default is calling canCreate
   * May be overloaded if needed
   *
   * @return booleen
   * @see canCreate
   **/
   function canUpdate() {
      return $this->canCreate();
   }

   /**
   * Have I the right to "create" the Object
   *
   * Default is true and check entity if the objet is entity assign
   *
   * May be overloaded if needed
   *
   * @return booleen
   **/
   function canCreateItem() {
      // Is an item assign to an entity
      if ($this->isEntityAssign()) {
         // Have access to entity
         return haveAccessToEntity($this->getEntityID());
      } else { // Global item
         return true;
      }
   }

   /**
   * Have I the right to "update" the Object
   *
   * Default is calling canCreateItem
   * May be overloaded if needed
   *
   * @return booleen
   * @see canCreate
   **/
   function canUpdateItem() {
      return $this->canCreateItem();
   }


   /**
   * Have I the right to "delete" the Object
   *
   * Default is calling canCreateItem
   * May be overloaded if needed
   *
   * @return booleen
   * @see canCreate
   **/
   function canDeleteItem() {
      return $this->canCreateItem();
   }

   /**
   * Have I the global right to "view" the Object
   *
   * Default is true and check entity if the objet is entity assign
   *
   * May be overloaded if needed
   *
   * @return booleen
   **/
   function canView() {

      return false;
   }

   /**
   * Have I the right to "view" the Object
   *
   * May be overloaded if needed
   *
   * @return booleen
   **/
   function canViewItem() {
      // Is an item assign to an entity
      if ($this->isEntityAssign()) {
         // Can be recursive check
         if ($this->maybeRecursive()) {
            return haveAccessToEntity($this->getEntityID(),$this->isRecursive());
         } else { // Non recursive item
            return haveAccessToEntity($this->getEntityID());
         }
      } else { // Global item
         return true;
      }
   }

   /**
   * Can I change recusvive flag to false
   * check if there is "linked" object in another entity
   *
   * May be overloaded if needed
   *
   * @return booleen
   **/
   function canUnrecurs() {
      global $DB, $CFG_GLPI;

      $ID  = $this->fields['id'];
      if ($ID<0 || !$this->fields['is_recursive']) {
         return true;
      }
      $entities = "('".$this->fields['entities_id']."'";
      foreach (getAncestorsOf("glpi_entities",$this->fields['entities_id']) as $papa) {
         $entities .= ",'$papa'";
      }
      $entities .= ")";

      $RELATION=getDbRelations();

      if (in_array($this->table, $CFG_GLPI["dropdowntree_tables"])) {
         $f = getForeignKeyFieldForTable($this->table);
         if (countElementsInTable($this->table, "`$f`='$ID'
                                                 AND entities_id NOT IN $entities")>0) {
            return false;
         }
      }
      if (isset($RELATION[$this->table])) {
         foreach ($RELATION[$this->table] as $tablename => $field) {
            if (in_array($tablename,$CFG_GLPI["specif_entities_tables"])) {
               // 1->N Relation
               if (is_array($field)) {
                  foreach ($field as $f) {
                     if (countElementsInTable($tablename, "`$f`='$ID' AND entities_id
                         NOT IN $entities")>0) {
                        return false;
                     }
                  }
               } else {
                  if (countElementsInTable($tablename, "`$field`='$ID' AND entities_id
                      NOT IN $entities")>0) {
                     return false;
                  }
               }
            } else {
               foreach ($RELATION as $othertable => $rel) {
                  // Search for a N->N Relation with devices
                  if ($othertable == "_virtual_device" && isset($rel[$tablename])) {
                     $devfield  = $rel[$tablename][0]; // items_id...
                     $typefield = $rel[$tablename][1]; // itemtype...

                     $sql = "SELECT DISTINCT `$typefield` AS itemtype
                             FROM `$tablename`
                             WHERE `$field`='$ID'";
                     $res = $DB->query($sql);

                     // Search linked device of each type
                     if ($res) {
                        while ($data = $DB->fetch_assoc($res)) {
                           $itemtype=$data["itemtype"];
                           $itemtable=getTableForItemType($itemtype);
                           if (in_array($itemtable, $CFG_GLPI["specif_entities_tables"])) {

                              if (countElementsInTable("$tablename, $itemtable",
                                  "`$tablename`.`$field`='$ID'
                                  AND `$tablename`.`$typefield`='$itemtype'
                                  AND `$tablename`.`$devfield`=`$itemtable`.id
                                  AND `$itemtable`.`entities_id` NOT IN $entities")>'0') {
                                 return false;
                              }
                           }
                        }
                     }
                  // Search for another N->N Relation
                  } else if ($othertable != $this->table && isset($rel[$tablename])
                             && in_array($othertable,$CFG_GLPI["specif_entities_tables"])) {

                     if (is_array($rel[$tablename])) {
                        foreach ($rel[$tablename] as $otherfield) {
                           if (countElementsInTable("$tablename, $othertable",
                               "`$tablename`.`$field`='$ID'
                               AND `$tablename`.`$otherfield`=`$othertable`.id
                               AND `$othertable`.`entities_id` NOT IN $entities")>'0') {
                              return false;
                           }
                        }
                     } else {
                        $otherfield = $rel[$tablename];
                        if (countElementsInTable("$tablename, $othertable",
                            "`$tablename`.`$field`=$ID
                            AND `$tablename`.`$otherfield`=`$othertable`.id
                            AND `$othertable`.`entities_id` NOT IN $entities")>'0') {
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
         && countElementsInTable("`glpi_documents_items`, `glpi_documents`",
                                 "`glpi_documents_items`.`items_id`='$ID'
                                  AND `glpi_documents_items`.`itemtype`=".$this->type."
                                  AND `glpi_documents_items`.`documents_id`=`glpi_documents`.`id`
                                  AND `glpi_documents`.`entities_id` NOT IN $entities")>'0') {
         return false;
      }
      // TODO : do we need to check all relations in $RELATION["_virtual_device"] for this item

      return true;
   }

   /*
    * Display a 2 columns Footer for Form buttons
    * Close the form is user can edit
    *
    * @param $ID ID of the item (-1 if new item)
    * @param $withtemplate empty or 1 for newtemplate, 2 for newobject from template
    * @param $colspan for each column
    * @param $candel : set to false to hide "delete" button
    *
    */
   function showFormButtons ($ID, $withtemplate='', $colspan=1, $candel=true) {
      global $LANG, $CFG_GLPI;

      if (!$this->can($ID,'w')) {
         echo "</table></div>";
         return false;
      }
      echo "<tr>";

      if (get_class($this)=='Entity' && !$ID) {
         // Very special case ;)
      } else if ($withtemplate || $ID<=0) {
         echo "<td class='tab_bg_2 center' colspan='".($colspan*2)."'>";
         if ($ID<=0||$withtemplate==2){
            echo "<input type='submit' name='add' value=\"".$LANG['buttons'][8]."\" class='submit'>";
         } else {
            echo "<input type='submit' name='update' value=\"".$LANG['buttons'][7]."\" class='submit'>";
         }
      } else {
         // Can delete an object with Infocom only if can write Infocom
         if (in_array($this->type,$CFG_GLPI["infocom_types"]) & !haveRight('infocom','w')) {
            $infocom = new Infocom();
            $candel = !$infocom->getFromDBforDevice($this->type,$ID);
         }

         if ($candel) {
            echo "<td class='tab_bg_2 center' colspan='".$colspan."'>\n";
            echo "<input type='submit' name='update' value=\"".$LANG['buttons'][7]."\" class='submit'>";
            echo "</td>\n";
            echo "<td class='tab_bg_2 center' colspan='".$colspan."' >\n";
            if (isset($this->fields['is_deleted']) && $this->fields['is_deleted']){
               echo "<input type='submit' name='restore' value=\"".$LANG['buttons'][21].
                      "\" class='submit'>";
               echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='submit' name='purge' value=\"".
                                                    $LANG['buttons'][22]."\" class='submit'>";
            }else {
               // TODO : change message for "destroy" / "send in trash" ?
               if (!in_array($this->table,$CFG_GLPI["deleted_tables"])) {
                  echo "<input type='submit' name='delete' value=\"" . $LANG['buttons'][6] .
                         "\" class='submit' OnClick='return window.confirm(\"" .
                         $LANG['common'][50]. "\");'>";
               } else {
                  echo "<input type='submit' name='delete' value=\"" . $LANG['buttons'][6] .
                         "\" class='submit'>";
               }
            }
         } else {
            echo "<td class='tab_bg_2 center' colspan='".($colspan*2)."'>\n";
            echo "<input type='submit' name='update' value=\"".$LANG['buttons'][7]."\" class='submit'>";
            // TODO : add a "no right to delete" message ?
         }
      }
      if ($ID>0) {
         echo "<input type='hidden' name='id' value='$ID'>";
      }
      echo "</td>";
      echo "</tr>\n";

      // Close for Form
      echo "</table></div></form>";
   }

   /**
   *
   * Display a 2 columns Header 1 for ID, 1 for recursivity menu
   * Open the form is user can edit
   *
   * @param $target for the Form
   * @param $ID ID of the item (-1 if new item)
   * @param $withtemplate empty or 1 for newtemplate, 2 for newobject from template
   * @param $colspan for each column
   * @param $formoptions string (javascript p.e.)
   *
   */
   function showFormHeader ($target, $ID, $withtemplate='', $colspan=1, $formoptions='') {
      global $LANG, $CFG_GLPI;

      if ($this->can($ID,'w')) {
         echo "<form name='form' method='post' action=\"$target\" $formoptions>";
         if (isset($this->fields["entities_id"])) {
            echo "<input type='hidden' name='entities_id' value='".$this->fields["entities_id"]."'>";
         }
      }
      echo "<div class='center' id='tabsbody'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='$colspan'>";

      if (get_class($this)=='Entity' && !$ID) {
         // Very special case ;)
      } else if (!empty($withtemplate) && $withtemplate == 2 && $ID>0) {
         echo "<input type='hidden' name='template_name' value='".$this->fields["template_name"]."'>";
         echo $LANG['buttons'][8] . " - " . $LANG['common'][13] . "&nbsp;: " . $this->fields["template_name"];
      } else if (!empty($withtemplate) && $withtemplate == 1) {
         echo "<input type='hidden' name='is_template' value='1' />\n";
         echo $LANG['common'][6]."&nbsp;: ";
         autocompletionTextField("template_name",$this->table,"template_name",
                                 $this->fields["template_name"],25,$this->fields["entities_id"]);
      } else if (empty($ID)||$ID<0) {
         echo $LANG['common'][87];
      } else {
         echo $LANG['common'][2]." $ID";
      }

      if (isset($this->fields["entities_id"]) && isMultiEntitiesMode()) {
         echo "&nbsp;(".Dropdown::getDropdownName("glpi_entities",$this->fields["entities_id"]).")";
      }
      echo "</th><th colspan='$colspan'>";

      if (isset($this->fields["is_recursive"]) && isMultiEntitiesMode()) {
         echo $LANG['entity'][9]."&nbsp;:&nbsp;";

         if (!$this->can($ID,'recursive')) {
            echo getYesNo($this->fields["is_recursive"]);
            $comment=$LANG['common'][86];
            $image="/pics/lock.png";
         } else if (!$this->canUnrecurs()) {
            echo getYesNo($this->fields["is_recursive"]);
            $comment=$LANG['common'][84];
            $image="/pics/lock.png";
         } else {
            Dropdown::showYesNo("is_recursive",$this->fields["is_recursive"]);
            $comment=$LANG['common'][85];
            $image="/pics/aide.png";
         }
         $rand=mt_rand();
         echo "&nbsp;<img alt='' src='".$CFG_GLPI["root_doc"].$image."'
                      onmouseout=\"cleanhide('comment_recursive$rand')\"
                      onmouseover=\"cleandisplay('comment_recursive$rand')\">";
         echo "<span class='over_link' id='comment_recursive$rand'>$comment</span>";
      } else {
         echo "&nbsp;";
      }
      echo "</th></tr>\n";
   }

   /**
   * is the parameter ID must be considered as new one ?
   * Default is empty of <0 may be overriden (for entity for example)
   *
   * @param $ID ID of the item (-1 if new item)
   *
   * @return boolean
   **/
   function isNewID($ID) {
      return (empty($ID)||$ID<=0);
   }

   /**
   * Check right on an item
   *
   * @param $ID ID of the item (-1 if new item)
   * @param $right Right to check : r / w / recursive
   * @param $input array of input data (used for adding item)
   *
   * @return boolean
   **/
   function can($ID,$right,&$input=NULL) {

      // Create process
      if ($this->isNewID($ID)) {
         if (!isset($this->fields['id'])) {
            // Only once
            $this->getEmpty();
         }
         if (is_array($input)) {
            // Copy input field to allow getEntityID() to work
            // from entites_id field or from parent item ref
            foreach ($input as $key => $val) {
               if (isset($this->fields[$key])) {
                  $this->fields[$key] = $val;
               }
            }
         }
         return ($this->canCreate() && $this->canCreateItem());
      } else {
         // Get item if not already loaded
         if (!isset($this->fields['id']) || $this->fields['id']!=$ID) {
            // Item not found : no right
            if (!$this->getFromDB($ID)) {
               return false;
            }
         }
      }

      switch ($right) {
         case 'r':
            // Personnal item
            if ($this->may_be_private && $this->fields['is_private']
                && $this->fields['users_id']==$_SESSION["glpiID"]) {
               return true;
            } else {
               return ($this->canView() && $this->canViewItem());
            }
            break;

         case 'w':
            // Personnal item
            if ($this->may_be_private && $this->fields['is_private']
                && $this->fields['users_id']==$_SESSION["glpiID"]){
               return true;
            } else {
               return ($this->canUpdate() && $this->canUpdateItem());
            }
            break;
         case 'd':
            // Personnal item
            if ($this->may_be_private && $this->fields['is_private']
                && $this->fields['users_id']==$_SESSION["glpiID"]){
               return true;
            } else {
               return ($this->canDelete() && $this->canDeleteItem());
            }
            break;
         case 'recursive':
            if ($this->isEntityAssign() && $this->maybeRecursive()) {
               if ($this->canCreate() && haveAccessToEntity($this->getEntityID())) {
                  // Can make recursive if recursive access to entity
                  return haveRecursiveAccessToEntity($this->getEntityID());
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
   * @param $input array of input data (used for adding item)
   * @return nothing
   **/
   function check($ID,$right,&$input=NULL) {
      global $CFG_GLPI;

      // Check item exists
      if ($ID>0 && !$this->getFromDB($ID)) {
         // Gestion timeout session
         if (!isset ($_SESSION["glpiID"])) {
            glpi_header($CFG_GLPI["root_doc"] . "/index.php");
            exit ();
         }
         displayNotFoundError();
      } else {
         if (!$this->can($ID,$right,$input)) {
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
   function isEntityAssign() {
      return $this->entity_assign;
   }

   /**
   * Get the ID of entity assigned to the object
   *
   * Can be overloaded (ex : infocom)
   *
   * @return ID of the entity
   **/
   function getEntityID() {
      if ($this->entity_assign && isset($this->fields["entities_id"])) {
         return $this->fields["entities_id"];
      }
      return  -1;
   }

   /**
   * Is the object may be recursive
   *
   * @return boolean
   **/
   function maybeRecursive() {
      return $this->may_be_recursive;
   }

   /**
    * Is the object recursive
    *
    * Can be overloaded (ex : infocom)
    *
    * @return integer (0/1)
    **/
   function isRecursive() {
      if ($this->may_be_recursive && isset($this->fields["is_recursive"])) {
         return $this->fields["is_recursive"];
      }
      return 0;
   }

   /**
   * Return the SQL command to retrieve linked object
   *
   * @return a SQL command which return a set of (itemtype, items_id)
   */
   function getSelectLinkedItem() {
      return '';
   }


   /**
    * Return a field Value if exists
    * @param $field field name
    * @return value of the field / false if not exists
    */
   function getField ($field) {
      if (isset($this->fields[$field])) {
         return $this->fields[$field];
      }
      /// TODO find a new value : can be valid for boolean value
      return false;
   }

   /**
    * Get comments of the Object
    *
    * @return String: comments of the object in the current language (HTML)
    */
   function getComments() {
      global $LANG,$CFG_GLPI;

      $comment="";
      if ($tmp=$this->getField('serial')) {
         $comment.="<strong>".$LANG['common'][19]."&nbsp;: "."</strong>".$tmp."<br>";
      }
      if ($tmp=$this->getField('otherserial')) {
         $comment.="<strong>".$LANG['common'][20]."&nbsp;: "."</strong>".$tmp."<br>";
      }
      if ($tmp=$this->getField('locations_id')) {
         $tmp=Dropdown::getDropdownName("glpi_locations",$tmp);
         if (!empty($tmp)&&$tmp!='&nbsp;'){
            $comment.="<strong>".$LANG['common'][15]."&nbsp;: "."</strong>".$tmp."<br>";
         }
      }
      if ($tmp=$this->getField('users_id')) {
         $tmp=getUserName($tmp);
         if (!empty($tmp)&&$tmp!='&nbsp;') {
            $comment.="<strong>".$LANG['common'][34]."&nbsp;: "."</strong>".$tmp."<br>";
         }
      }
      if ($tmp=$this->getField('groups_id')) {
         $tmp=Dropdown::getDropdownName("glpi_groups",$tmp);
         if (!empty($tmp)&&$tmp!='&nbsp;') {
            $comment.="<strong>".$LANG['common'][35]."&nbsp;: "."</strong>".$tmp."<br>";
         }
      }
      if ($tmp=$this->getField('users_id_tech')) {
         $tmp=getUserName($tmp);
         if (!empty($tmp)&&$tmp!='&nbsp;') {
            $comment.="<strong>".$LANG['common'][10]."&nbsp;: "."</strong>".$tmp."<br>";
         }
      }
      if ($tmp=$this->getField('contact')) {
         $comment.="<strong>".$LANG['common'][18]."&nbsp;: "."</strong>".$tmp."<br>";
      }
      if ($tmp=$this->getField('contact_num')) {
         $comment.="<strong>".$LANG['common'][21]."&nbsp;: "."</strong>".$tmp."<br>";
      }

      if (!empty($comment)) {
         $rand=mt_rand();
         $comment_display=" onmouseout=\"cleanhide('comment_commonitem$rand')\"
                            onmouseover=\"cleandisplay('comment_commonitem$rand')\" ";
         $comment_display2="<span class='over_link' id='comment_commonitem$rand'>".nl2br($comment).
                           "</span>";

         $comment="<img alt='' src='".$CFG_GLPI["root_doc"]."/pics/aide.png' $comment_display> ";
         $comment.=$comment_display2;
      }
      return $comment;
   }

   /**
    * Get The Name of the Object
    *
    * @param $with_comment add comments to name
    * @return String: name of the object in the current language
    */
   function getName($with_comment=0) {
      $toadd="";
      if ($with_comment) {
         $toadd="&nbsp;".$this->getComments();
      }

      if (isset($this->fields["name"]) && !empty($this->fields["name"])) {
         return $this->fields["name"].$toadd;
      }
      return NOT_AVAILABLE;
   }

   /**
    * Get The Name of the Object with the ID if the config is set
    * @param $with_comment add comments to name
    * @return String: name of the object in the current language
    */
   function getNameID($with_comment=0) {
      global $CFG_GLPI;

      $toadd="";
      if ($with_comment) {
         $toadd="&nbsp;".$this->getComments();
      }
      if ($_SESSION['glpiis_ids_visible']) {
         return $this->getName()." (".$this->getField('id').")".$toadd;
      }
      return $this->getName().$toadd;
   }

   /**
    * Get the Search options for the givem Type
    *
    * This should be overloaded in Class
    *
    * @return an array of seach options
    * More information on https://forge.indepnet.net/wiki/glpi/SearchEngine
    */
   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common']           = $LANG['common'][32];;

      $tab[1]['table']         = $this->table;
      $tab[1]['field']         = 'name';
      $tab[1]['linkfield']     = '';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_link'] = $this->type;

      return $tab;
   }

   /**
    * Print out an HTML "<select>" for a dropdown
    *
    * This should be overloaded in Class
    * Parameters which could be used in options array :
    *    - value : integer / preselected value (default 0)
    *    - comments : boolean / is the comments displayed near the dropdown (default true)
    *    - entity : integer or array / restrict to a defined entity or array of entities
    *                   (default -1 : no restriction)
    *    - toupdate : array / Update a specific item on select change on dropdown
    *                   (need value_fieldname, to_update, url (see ajaxUpdateItemOnSelectEvent for informations)
    *                   and may have moreparams)
    *    - used : array / Already used items ID: not to display in dropdown (default empty)
    *    - auto_submit : boolean / preselected value (default 0)
    *
    * @param $name the name of the HTML select
    * @param $options possible options
    * @return nothing display the dropdown
    */
   static function dropdown($name,$options=array()) {
      $default_values['value']='';
      $default_values['comments']=1;
      $default_values['entity']=-1;
      $default_values['toupdate']='';
      $default_values['used']=array();
      $default_values['auto_submit']=0;

      foreach ($default_values as $key => $val) {
         if (isset($options[$key])) {
            $$key=$options[$key];
         } else {
            $$key=$default_values[$key];
         }
      }

      Dropdown::dropdownValue(
            getTableForItemType(get_class($this)),
            $name,
            $value,
            $comments,
            $entity,
            $toupdate,
            $used,
            $auto_submit
         );
   }

}

?>