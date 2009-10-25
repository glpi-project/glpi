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
 *  Common DataBase Table Manager Class
 */
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
   /// Black list fields for history log or date mod update
   var $history_blacklist	= array();
   /// set false to desactivate automatic message on action
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
   *@param $ID ID of the item
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
   *@param $ID ID of the item
   *
   *@return nothing
   *
   **/
   function cleanRelationData($ID) {
      global $DB, $CFG_GLPI;

      $RELATION=getDbRelations();
      if (isset($RELATION[$this->table])) {
         $newval = 0;
         $fkname = getForeignKeyFieldForTable($this->table);
         if (isset($this->fields[$fkname])) {
            // When delete a tree item, remplace by is parent
            $newval = $this->fields[$fkname];
         }
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
         $job=new Job;

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
         $infocom = new InfoCom();
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
         $ci = new ContractItem();
         $ci->cleanDBonItemDelete($this->type,$ID);
      }

      // If this type have DOCUMENT, clean one associated to purged item
      if (in_array($this->type,$CFG_GLPI["doc_types"])) {
         $di = new DocumentItem();
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

      if (isset($input['add'])) {
         $input['_add']=$input['add'];
         unset($input['add']);
      }
      $input=$this->prepareInputForAdd($input);

      if ($input&&is_array($input)) {
         $this->fields=array();
         $table_fields=$DB->list_fields($this->table);

         // fill array for add
         foreach ($input as $key => $val) {
            if ($key[0]!='_' && isset($table_fields[$key])) {
               $this->fields[$key] = $input[$key];
            }
         }
         // Auto set date_mod if exsist
         if (isset($table_fields['date_mod'])) {
            $this->fields['date_mod']=$_SESSION["glpi_currenttime"];
         }

         if ($newID= $this->addToDB()) {
            $this->addMessageOnAddAction($input);
            $this->post_addItem($newID,$input);
            doHook("item_add",array("type"=>$this->type, "id" => $newID, "input" => $input));
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
   function addMessageOnAddAction($input) {
      global $INFOFORM_PAGES, $CFG_GLPI, $LANG;

      if (!isset($INFOFORM_PAGES[$this->type])) {
         return;
      }

      $addMessAfterRedirect=false;
      if (isset($input['_add'])) {
         $addMessAfterRedirect=true;
      }
      if (isset($input['_no_message']) || !$this->auto_message_on_action) {
         $addMessAfterRedirect=false;
      }

      $name = '';
      if (isset($this->fields["name"])) {
         $name = $this->fields["name"];
      }
      if ($_SESSION['glpiis_ids_visible']
          || !isset($this->fields["name"])
          || empty($this->fields["name"])) {
         $name .= " (".$this->fields['id'].")";
      }

      if ($addMessAfterRedirect) {
         addMessageAfterRedirect($LANG['common'][70] . ": <a href='" . $CFG_GLPI["root_doc"].
                                 "/".$INFOFORM_PAGES[$this->type] . "?id=" . $this->fields['id'] .
                                 (isset($input['is_template']) ? "&amp;withtemplate=1" : "").
                                 "'>" .$name . "</a>");
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
   *@return Nothing (call to the class member)
   *
   **/
   function update($input,$history=1) {
      global $DB;

      if ($DB->isSlave()) {
         return false;
      }

      $input['_item_type_']=$this->type;
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

            // CLean old_values history not needed
            if (!$this->dohistory || !$history) {
               $oldvalues=array();
            }

            if ($this->updateInDB($updates,$oldvalues)) {
               $this->addMessageOnUpdateAction($input);
               doHook("item_update",array("type"=>$this->type, "id" => $input["id"],
                      "input"=> $input, "updates" => $updates, "oldvalues" => $oldvalues));
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
   function addMessageOnUpdateAction($input) {
      global $INFOFORM_PAGES, $CFG_GLPI, $LANG;

      if (!isset($INFOFORM_PAGES[$this->type])) {
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
         addMessageAfterRedirect($LANG['common'][71].": <a href='" . $CFG_GLPI["root_doc"]."/".
                                 $INFOFORM_PAGES[$this->type] . "?id=" . $this->fields['id'] . "'>" .
                                 (isset($this->fields["name"]) && !empty($this->fields["name"])
                                 ? stripslashes($this->fields["name"])
                                 : "(".$this->fields['id'].")") . "</a>");
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
   *@return Nothing ()
   *
   **/
   function delete($input,$force=0,$history=1) {
      global $DB;

      if ($DB->isSlave()) {
         return false;
      }

      $input['_item_type_']=$this->type;
      if ($force) {
         $input=doHookFunction("pre_item_purge",$input);
         if (isset($input['purge'])) {
            $input['_purge']=$input['purge'];
            unset($input['purge']);
         }
      } else {
         $input=doHookFunction("pre_item_delete",$input);
         if (isset($input['delete'])) {
            $input['_delete']=$input['delete'];
            unset($input['delete']);
         }
      }

      if ($this->getFromDB($input[$this->getIndexName()])) {
         if ($this->pre_deleteItem($this->fields["id"])) {
            if ($this->deleteFromDB($this->fields["id"],$force)) {
               if ($force) {
                  $this->addMessageOnPurgeAction($input);
                  doHook("item_purge",array("type"=>$this->type, "id" => $this->fields["id"],
                         "input" => $input));
               } else {
                  $this->addMessageOnDeleteAction($input);
                  if ($this->dohistory&&$history) {
                     $changes[0] = 0;
                     $changes[1] = $changes[2] = "";

                     historyLog ($this->fields["id"],$this->type,$changes,0,HISTORY_DELETE_ITEM);
                  }
                  doHook("item_delete",array("type"=>$this->type, "id" => $this->fields["id"],
                         "input" => $input));
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
   function addMessageOnDeleteAction($input) {
      global $INFOFORM_PAGES, $CFG_GLPI, $LANG;

      if (!isset($INFOFORM_PAGES[$this->type])) {
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
         addMessageAfterRedirect($LANG['common'][72] . ": <a href='" . $CFG_GLPI["root_doc"]."/".
                                 $INFOFORM_PAGES[$this->type] . "?id=" . $this->fields['id'] . "'>" .
                                 (isset($this->fields["name"]) && !empty($this->fields["name"])
                                 ? stripslashes($this->fields["name"])
                                 : "(".$this->fields['id'].")") . "</a>");
      }
   }

   /**
   * Add a message on purge action
   *
   *@param $input array : the _POST vars returned bye the item form when press add
   *
   **/
   function addMessageOnPurgeAction($input) {
      global $INFOFORM_PAGES, $CFG_GLPI, $LANG;

      if (!isset($INFOFORM_PAGES[$this->type])) {
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
         addMessageAfterRedirect($LANG['common'][73].": ".(isset($this->fields["name"]) &&
                                 !empty($this->fields["name"]) ? stripslashes($this->fields["name"])
                                 : "(".$this->fields['id'].")"));
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

      if (isset($input['restore'])) {
         $input['_restore']=$input['restore'];
         unset($input['restore']);
      }
      $input['_item_type_']=$this->type;
      $input=doHookFunction("pre_item_restore",$input);

      if ($this->getFromDB($input[$this->getIndexName()])) {
         if ($this->restoreInDB($input["id"])) {
            $this->addMessageOnRestoreAction($input);
            if ($this->dohistory && $history) {
               $changes[0] = 0;
               $changes[1] = $changes[2] = "";
               historyLog ($input["id"],$this->type,$changes,0,HISTORY_RESTORE_ITEM);
            }
            doHook("item_restore",array("type"=>$this->type, "id" => $input["id"],
                   "input" => $input));
         }
      }
   }

   /**
   * Add a message on restore action
   *
   *@param $input array : the _POST vars returned bye the item form when press add
   *
   **/
   function addMessageOnRestoreAction($input) {
      global $INFOFORM_PAGES, $CFG_GLPI, $LANG;

      if (!isset($INFOFORM_PAGES[$this->type])) {
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
         addMessageAfterRedirect($LANG['common'][74] . ": <a href='" . $CFG_GLPI["root_doc"]."/".
                                 $INFOFORM_PAGES[$this->type] . "?id=" . $this->fields['id'] . "'>" .
                                 (isset($this->fields["name"]) && !empty($this->fields["name"])
                                 ? stripslashes($this->fields["name"])
                                 : "(".$this->fields['id'].")") . "</a>");
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
   * Define tabs to display
   *
   *@param $withtemplate is a template view ?
   *
   *@return array containing the onglets
   *
   **/
   function defineTabs($ID,$withtemplate) {
      return array();
   }

   /**
   * Show onglets
   *
   *@param $ID ID of the item to display
   *@param $withtemplate is a template view ?
   *@param $actif active onglet
   *@param $addparams array of parameters to add to URLs and ajax
   *
   *@return Nothing ()
   *
   **/
   function showTabs($ID,$withtemplate,$actif,$addparams=array()) {
      global $LANG,$CFG_GLPI,$INFOFORM_PAGES;

      $target=$_SERVER['PHP_SELF'];
      $template="";
      $templatehtml="";
      if(!empty($withtemplate)) {
         $template="&withtemplate=$withtemplate";
         $templatehtml="&amp;withtemplate=$withtemplate";
      }
      $extraparamhtml="";
      $extraparam="";
      if (is_array($addparams) && count($addparams)) {
         foreach ($addparams as $key => $val) {
            $extraparamhtml.="&amp;$key=$val";
            $extraparam.="&$key=$val";
         }
      }
      if (empty($withtemplate) && $ID && $this->type>0) {
         echo "<div id='menu_navigate'>";
         if (isset($this->sub_type)) {
            $glpilistitems =& $_SESSION['glpilistitems'][$this->type][$this->sub_type];
            $glpilisttitle =& $_SESSION['glpilisttitle'][$this->type][$this->sub_type];
            $glpilisturl   =& $_SESSION['glpilisturl'][$this->type][$this->sub_type];
         } else {
            $glpilistitems =& $_SESSION['glpilistitems'][$this->type];
            $glpilisttitle =& $_SESSION['glpilisttitle'][$this->type];
            $glpilisturl   =& $_SESSION['glpilisturl'][$this->type];
         }
         $next=$prev=$first=$last=-1;
         $current=false;
         if (is_array($glpilistitems)) {
            $current=array_search($ID,$glpilistitems);
            if ($current!==false) {
               if (isset($glpilistitems[$current+1])) {
                  $next=$glpilistitems[$current+1];
               }
               if (isset($glpilistitems[$current-1])) {
                  $prev=$glpilistitems[$current-1];
               }
               $first=$glpilistitems[0];
               if ($first==$ID) {
                  $first= -1;
               }
               $last=$glpilistitems[count($glpilistitems)-1];
               if ($last==$ID) {
                  $last= -1;
               }
            }
         }
         $cleantarget=preg_replace("/\?id=([0-9]+)/","",$target);
         echo "<ul>";
         echo "<li><a href=\"javascript:showHideDiv('tabsbody','tabsbodyimg','".$CFG_GLPI["root_doc"].
                    "/pics/deplier_down.png','".$CFG_GLPI["root_doc"]."/pics/deplier_up.png')\">";
         echo "<img alt='' name='tabsbodyimg' src=\"".$CFG_GLPI["root_doc"]."/pics/deplier_up.png\">";
         echo "</a></li>";

         echo "<li><a href=\"".$glpilisturl."\">";
         if ($glpilisttitle) {
            if (utf8_strlen($glpilisttitle)>$_SESSION['glpidropdown_chars_limit']) {
               $glpilisttitle = utf8_substr($glpilisttitle, 0, $_SESSION['glpidropdown_chars_limit'])
                                            . "&hellip;";
            }
            echo $glpilisttitle;
         } else {
            echo $LANG['common'][53];
         }
         echo "</a>:&nbsp;</li>";

         if ($first>0) {
            echo "<li><a href='$cleantarget?id=$first$extraparamhtml'><img src=\"".
                       $CFG_GLPI["root_doc"]."/pics/first.png\" alt='".$LANG['buttons'][55].
                       "' title='".$LANG['buttons'][55]."'></a></li>";
         } else {
            echo "<li><img src=\"".$CFG_GLPI["root_doc"]."/pics/first_off.png\" alt='".
                       $LANG['buttons'][55]."' title='".$LANG['buttons'][55]."'></li>";
         }

         if ($prev>0) {
            echo "<li><a href='$cleantarget?id=$prev$extraparamhtml'><img src=\"".
                       $CFG_GLPI["root_doc"]."/pics/left.png\" alt='".$LANG['buttons'][12].
                       "' title='".$LANG['buttons'][12]."'></a></li>";
         } else {
            echo "<li><img src=\"".$CFG_GLPI["root_doc"]."/pics/left_off.png\" alt='".
                       $LANG['buttons'][12]."' title='".$LANG['buttons'][12]."'></li>";
         }

         if ($current!==false) {
            echo "<li>".($current+1) . "/" . count($glpilistitems)."</li>";
         }

         if ($next>0) {
            echo "<li><a href='$cleantarget?id=$next$extraparamhtml'><img src=\"".
                       $CFG_GLPI["root_doc"]."/pics/right.png\" alt='".$LANG['buttons'][11].
                       "' title='".$LANG['buttons'][11]."'></a></li>";
         } else {
            echo "<li><img src=\"".$CFG_GLPI["root_doc"]."/pics/right_off.png\" alt='".
                       $LANG['buttons'][11]."' title='".$LANG['buttons'][11]."'></li>";
         }

         if ($last>0) {
            echo "<li><a href='$cleantarget?id=$last$extraparamhtml'><img src=\"".
                       $CFG_GLPI["root_doc"]."/pics/last.png\" alt='".$LANG['buttons'][56].
                       "' title='".$LANG['buttons'][56]."'></a></li>";
         } else {
            echo "<li><img src=\"".$CFG_GLPI["root_doc"]."/pics/last_off.png\" alt='".
                       $LANG['buttons'][56]."' title='".$LANG['buttons'][56]."'></li>";
         }
         echo "</ul></div>";
         echo "<div class='sep'></div>";
      }
      echo "<div id='tabspanel' class='center-h'></div>";

      $active=0;
      $onglets=$this->defineTabs($ID,$withtemplate);
      $display_all=true;
      if (isset($onglets['no_all_tab'])) {
         $display_all=false;
         unset($onglets['no_all_tab']);
      }
      if (count($onglets)) {
         $patterns[0] = '/front/';
         $patterns[1] = '/form/';
         $replacements[0] = 'ajax';
         $replacements[1] = 'tabs';
         $tabpage=preg_replace($patterns, $replacements, $INFOFORM_PAGES[$this->type]);
         $tabs=array();
         foreach ($onglets as $key => $val ) {
            $tabs[$key]=array('title'=>$val,
                              'url'=>$CFG_GLPI['root_doc']."/$tabpage",
                              'params'=>"target=$target&itemtype=".$this->type.
                                        "&glpi_tab=$key&id=$ID$template$extraparam");
         }
         $plug_tabs=getPluginTabs($target,$this->type,$ID,$withtemplate);
         $tabs+=$plug_tabs;
         // Not all tab for templates and if only 1 tab
         if($display_all && empty($withtemplate)
            && count($tabs)>1) {
            $tabs[-1]=array('title'=>$LANG['common'][66],
                            'url'=>$CFG_GLPI['root_doc']."/$tabpage",
                            'params'=>"target=$target&itemtype=".$this->type.
                                      "&glpi_tab=-1&id=$ID$template$extraparam");
         }
         createAjaxTabs('tabspanel','tabcontent',$tabs,$actif);
      }
   }

   /**
   * Have I the right to "create" the Object
   *
   * May be overloaded if needed (ex kbitem)
   *
   * @return booleen
   **/
   function canCreate() {
      return haveTypeRight($this->type,"w");
   }

   /**
   * Have I the right to "view" the Object
   *
   * May be overloaded if needed
   *
   * @return booleen
   **/
   function canView() {
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
   function canUnrecurs() {
      global $DB, $LINK_ID_TABLE, $CFG_GLPI;

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
                           if (isset($LINK_ID_TABLE[$itemtype]) &&
                               in_array($device=$LINK_ID_TABLE[$itemtype],
                               $CFG_GLPI["specif_entities_tables"])) {

                              if (countElementsInTable("$tablename, $device",
                                  "`$tablename`.`$field`='$ID'
                                  AND `$tablename`.`$typefield`='$itemtype'
                                  AND `$tablename`.`$devfield`=`$device`.id
                                  AND `$device`.`entities_id` NOT IN $entities")>'0') {
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

      if ($withtemplate || $ID<=0) {
         echo "<td class='tab_bg_2 center' colspan='".($colspan*2)."'>";
         if ($ID<=0||$withtemplate==2){
            echo "<input type='submit' name='add' value=\"".$LANG['buttons'][8]."\" class='submit'>";
         } else {
            echo "<input type='submit' name='update' value=\"".$LANG['buttons'][7]."\" class='submit'>";
         }
      } else {
         // Can delete an object with Infocom only if can write Infocom
         if (in_array($this->type,$CFG_GLPI["infocom_types"]) & !haveRight('infocom','w')) {
            $infocom = new InfoCom();
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

      if (!empty($withtemplate) && $withtemplate == 2 && $ID>0) {
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
         echo "&nbsp;(".getDropdownName("glpi_entities",$this->fields["entities_id"]).")";
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
            dropdownYesNo("is_recursive",$this->fields["is_recursive"]);
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
   * Check right on an item
   *
   * @param $ID ID of the item (-1 if new item)
   * @param $right Right to check : r / w / recursive
   * @param $input array of input data (used for adding item)
   *
   * @return boolean
   **/
   function can($ID,$right,&$input=NULL) {

      $entity_to_check=-1;
      $recursive_state_to_check=0;
      // Get item if not already loaded
      if (empty($ID)||$ID<=0) {
         // No entity define : adding process : use active entity
         if (isset($input['entities_id'])) {
            $entity_to_check = $input['entities_id'];
         } else {
            $entity_to_check = $_SESSION["glpiactive_entity"];
         }
      } else {
         if (!isset($this->fields['id']) || $this->fields['id']!=$ID) {
            // Item not found : no right
            if (!$this->getFromDB($ID)) {
               return false;
            }
         }
         if ($this->isEntityAssign()) {
            $entity_to_check=$this->getEntityID();
            if ($this->maybeRecursive()) {
               $recursive_state_to_check=$this->isRecursive();
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
               // Check Global Right
               if ($this->canView()) {
                  // Is an item assign to an entity
                  if ($this->isEntityAssign()) {
                     // Can be recursive check
                     if ($this->maybeRecursive()) {
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
            if ($this->may_be_private && $this->fields['is_private']
                && $this->fields['users_id']==$_SESSION["glpiID"]){
               return true;
            } else {
               // Check Global Right
               if ($this->canCreate()) {
                  // Is an item assign to an entity
                  if ($this->isEntityAssign()) {
                     // Have access to entity
                     return haveAccessToEntity($entity_to_check);
                  } else { // Global item
                     return true;
                  }
               }
            }
            break;

         case 'recursive':
            if ($this->isEntityAssign() && $this->maybeRecursive()) {
               if ($this->canCreate() && haveAccessToEntity($entity_to_check)) {
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
}

/// Common DataBase Relation Table Manager Class
abstract class CommonDBRelation extends CommonDBTM {

   // Mapping between DB fields
   var $itemtype_1; // Type ref or field name
   var $items_id_1; // Field name
   var $itemtype_2; // Type ref or field name
   var $items_id_2; // Field name

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

      if ($ID>0) {
         if (!isset($this->fields['id']) || $this->fields['id']!=$ID) {
            // Item not found : no right
            if (!$this->getFromDB($ID)) {
               return false;
            }
         }
         $input = &$this->fields;
      }

      // Must can read first Item of the relation
      $ci1 = new CommonItem();
      $ci1->setType(is_numeric($this->itemtype_1) ? $this->itemtype_1 : $input[$this->itemtype_1],
                    true);
      if (!$ci1->obj->can($input[$this->items_id_1],'r')) {
         return false;
      }
      // Must can read second Item of the relation
      $ci2 = new CommonItem();
      $ci2->setType(is_numeric($this->itemtype_2) ? $this->itemtype_2 : $input[$this->itemtype_2],
                    true);
      if (!$ci2->obj->can($input[$this->items_id_2],'r')) {
         return false;
      }

      // Read right checked on both item
      if ($right=='r') {
         return true;
      }

      // Check entity compatibility
      if ($ci1->obj->isEntityAssign() && $ci2->obj->isEntityAssign()) {
         if ($ci1->obj->getEntityID() == $ci2->obj->getEntityID()) {
            $checkentity = true;
         } else if ($ci1->obj->isRecursive()
                    && in_array($ci1->obj->getEntityID(),
                                 getAncestorsOf("glpi_entities",$ci2->obj->getEntityID()))) {
            $checkentity = true;
         } else if ($ci2->obj->isRecursive()
                    && in_array($ci2->obj->getEntityID(),
                                getAncestorsOf("glpi_entities",$ci1->obj->getEntityID()))) {
            $checkentity = true;
         } else {
            // $checkentity is false => return
            return false;
         }
      } else {
         $checkentity = true;
      }
      // can write one item is enough
      if ($ci1->obj->can($input[$this->items_id_1],'w')
          || $ci2->obj->can($input[$this->items_id_2],'w')) {
         return true;
      }
      return false;
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
   function post_addItem($newID,$inpt) {

      $ci1 = new CommonItem();
      $ci1->setType(is_numeric($this->itemtype_1) ? $this->itemtype_1 :
                    $this->fields[$this->itemtype_1], true);
      if (!$ci1->obj->getFromDB($this->fields[$this->items_id_1])) {
         return false;
      }
      $ci2 = new CommonItem();
      $ci2->setType(is_numeric($this->itemtype_2) ? $this->itemtype_2 :
                    $this->fields[$this->itemtype_2], true);
      if (!$ci2->obj->getFromDB($this->fields[$this->items_id_2])) {
         return false;
      }

      if ($ci1->obj->dohistory) {
         $changes[0]='0';
         $changes[1]="";
         $changes[2]=addslashes($ci2->getNameID());
         historyLog ($ci1->obj->fields["id"],$ci1->obj->type,$changes,$ci2->obj->type,
                     HISTORY_ADD_RELATION);
      }
      if ($ci2->obj->dohistory) {
         $changes[0]='0';
         $changes[1]="";
         $changes[2]=addslashes($ci1->getNameID());
         historyLog ($ci2->obj->fields["id"],$ci2->obj->type,$changes,$ci1->obj->type,
                     HISTORY_ADD_RELATION);
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

      $ci1 = new CommonItem();
      $ci1->setType(is_numeric($this->itemtype_1) ? $this->itemtype_1 :
                    $this->fields[$this->itemtype_1], true);
      if (!$ci1->obj->getFromDB($this->fields[$this->items_id_1])) {
         return false;
      }
      $ci2 = new CommonItem();
      $ci2->setType(is_numeric($this->itemtype_2) ? $this->itemtype_2 :
                    $this->fields[$this->itemtype_2], true);
      if (!$ci2->obj->getFromDB($this->fields[$this->items_id_2])) {
         return false;
      }

      if ($ci1->obj->dohistory) {
         $changes[0]='0';
         $changes[1]=addslashes($ci2->getNameID());
         $changes[2]="";
         historyLog ($ci1->obj->fields["id"],$ci1->obj->type,$changes,$ci2->obj->type,
                     HISTORY_DEL_RELATION);
      }
      if ($ci2->obj->dohistory) {
         $changes[0]='0';
         $changes[1]=addslashes($ci1->getNameID());
         $changes[2]="";
         historyLog ($ci2->obj->fields["id"],$ci2->obj->type,$changes,$ci1->obj->type,
                     HISTORY_DEL_RELATION);
      }
   }

   /**
    * Clean the Relation Table when item of the relation is deleted
    * To be call from the cleanDBonPurge of each Item class
    *
    * @param $itemtype : type of the item
    * @param $item_id : id of the item
    */
   function cleanDBonItemDelete ($itemtype, $item_id) {
      global $DB;

      $query = "SELECT `id`
                FROM `".$this->table."`";

      if ($itemtype==$this->itemtype_1) {
         $where = " WHERE `".$this->items_id_1."`='$item_id'";
      } else if (!is_numeric($this->itemtype_1)) {
         $where = " WHERE (`".$this->itemtype_1."`='$itemtype'
                           AND `".$this->items_id_1."`='$item_id')";
      } else {
         $where = '';
      }

      if ($itemtype==$this->itemtype_2) {
         $where .= (empty($where) ? " WHERE " : " OR ");
         $where .= " `".$this->items_id_2."`='$item_id'";
      } else if (!is_numeric($this->itemtype_2)) {
         $where .= (empty($where) ? " WHERE " : " OR ");
         $where .= " (`".$this->itemtype_2."`='$itemtype'
                      AND `".$this->items_id_2."`='$item_id')";
      }

      if (empty($where)) {
         return false;
      }
      $result = $DB->query($query.$where);
      while ($data = $DB->fetch_assoc($result)) {
         $this->delete(array('id'=>$data['id']));
      }
   }
}

abstract class CommonTreeDropdown extends CommonDBTM{

   /**
    * Constructor
    **/
   function __construct($itemtype){
      global $LINK_ID_TABLE;

      $this->type=$itemtype;
      $this->table=$LINK_ID_TABLE[$itemtype];
      $this->keyid=getForeignKeyFieldForTable($this->table);
      $this->entity_assign=true;
      $this->may_be_recursive=true;
   }

   /**
    * Return Additional Fileds for this type
    */
   function getAdditionalFields() {
      return array();
   }

   /**
    * Get the localized display name of the type
    */
   abstract function getTypeName();

   function defineTabs($ID,$withtemplate) {
      global $LANG;

      $ong=array();
      $ong[1] = $this->getTypeName();
      return $ong;
   }

   function showForm ($target,$ID) {
      global $CFG_GLPI, $LANG;

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w');
         $this->getEmpty();
      }

      $this->showTabs($ID, '',getActiveTab($this->type));
      $this->showFormHeader($target,$ID,'',2);

      $fields = $this->getAdditionalFields();
      $nb=count($fields);

      echo "<tr class='tab_bg_1'><td>".$LANG['setup'][75]."&nbsp;:</td>";
      echo "<td>";
      dropdownValue($this->table, $this->keyid,
                    $this->fields["$this->keyid"], 1,
                    $this->fields["entities_id"], '',
                    ($ID>0 ? getSonsOf($this->table, $ID) : array()));
      echo "</td>";

      echo "<td rowspan='".($nb+2)."'>";
      echo $LANG['common'][25]."&nbsp;:</td>";
      echo "<td rowspan='".($nb+2)."'>
            <textarea cols='45' rows='".($nb+3)."' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'><td>".$LANG['common'][16]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("name",$this->table,"name",$this->fields["name"],40);
      echo "</td></tr>\n";

      foreach ($fields as $field) {
         echo "<tr class='tab_bg_1'><td>".$field['label']."&nbsp;:</td><td>";
         switch ($field['type']) {
            case 'dropdownUsersID' :
               dropdownUsersID($field['name'], $this->fields[$field['name']], "interface", 1,
                                $this->fields["entities_id"]);
               break;
            case 'dropdownValue' :
               dropdownValue(getTableNameForForeignKeyField($field['name']),
                              $field['name'], $this->fields[$field['name']],1,
                              $this->fields["entities_id"]);
               break;
         }
         echo "</td></tr>\n";
      }

      $this->showFormButtons($ID,'',2);

      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";

      return true;
   }

   function prepareInputForAdd($input) {

      $parent = clone $this;

      if (isset($input[$this->keyid])
          && $input[$this->keyid]>0
          && $parent->getFromDB($input[$this->keyid])) {
         $input['level'] = $parent->fields['level']+1;
         $input['completename'] = $parent->fields['completename'] . " > " . $input['name'];
      } else {
         $input[$this->keyid] = 0;
         $input['level'] = 1;
         $input['completename'] = $input['name'];
      }

      return $input;
   }

   function pre_deleteItem($ID) {
      global $DB;

      $parent = $this->fields[$this->keyid];

      CleanFields($this->table, 'sons_cache', 'ancestors_cache');
      $tmp = clone $this;
      $crit = array('FIELDS'=>'id',
                    $this->keyid=>$ID);
      foreach ($DB->request($this->table, $crit) as $data) {
         $data[$this->keyid] = $parent;
         $tmp->update($data);
      }
      return true;
   }

   function prepareInputForUpdate($input) {
      // Can't move a parent under a child
      if (isset($input[$this->keyid])
          && in_array($input[$this->keyid], getSonsOf($this->table, $input['id']))) {
         return false;
      }
      return $input;
   }

   function post_updateItem($input,$updates,$history=1) {
      if (in_array('name', $updates) || in_array($this->keyid, $updates)) {
         if (in_array($this->keyid, $updates)) {
            CleanFields($this->table, 'sons_cache', 'ancestors_cache');
         }
         regenerateTreeCompleteNameUnderID($this->table, $input['id']);
      }
   }
   /**
    * Print the HTML array children of a TreeDropdown
    *
    *@param $ID of the dropdown
    *
    *@return Nothing (display)
    *
    **/
    function showChildren($ID) {
      global $DB, $CFG_GLPI, $LANG, $INFOFORM_PAGES;

      $this->check($ID, 'r');
      $fields = $this->getAdditionalFields();
      $nb=count($fields);

      echo "<br><div class='center'><table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='".($nb+3)."'>".$LANG['setup'][78]."</th></tr>";
      echo "<tr><th>".$LANG['common'][16]."</th>"; // Name
      echo "<th>".$LANG['entity'][0]."</th>"; // Entity
      foreach ($fields as $field) {
         if ($field['list']) {
            echo "<th>".$field['label']."</th>";
         }
      }
      echo "<th>".$LANG['common'][25]."</th>";
      echo "</tr>\n";

      foreach ($DB->request($this->table, array($this->keyid=>$ID)) as $data) {
         echo "<tr class='tab_bg_1'>";
         echo "<td><a href='".$CFG_GLPI["root_doc"].'/front/dropdown.form.php?itemtype=';
         echo $this->type.'&amp;id='.$data['id']."'>".$data['name']."</a></td>";
         echo "<td>".getDropdownName("glpi_entities",$data["entities_id"])."</td>";
         foreach ($fields as $field) {
            if ($field['list']) {
               echo "<td>";
               switch ($field['type']) {
                  case 'dropdownUsersID' :
                     echo getUserName($data[$field['name']]);
                     break;
                  case 'dropdownValue' :
                     echo getDropdownName(getTableNameForForeignKeyField($field['name']),
                                     $data[$field['name']]);
                     break;
                  default:
                     echo $data[$field['name']];
               }
               echo "</td>";
            }
         }
         echo "<td>".$data['comment']."</td>";
         echo "</tr>\n";
      }
      echo "</table></div>\n";
   }

}

?>