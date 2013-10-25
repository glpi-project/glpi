<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 *  Common DataBase Table Manager Class - Persistent Object
 */
class CommonDBTM extends CommonGLPI {

   /// Data of the Item
   var $fields = array();
   /// Make an history of the changes
   var $dohistory = false;
   /// Black list fields for history log or date mod update
   var $history_blacklist = array();
   /// Set false to desactivate automatic message on action
   var $auto_message_on_action = true;

   /// Set true to desactivate link generation because form page do not permit show/edit item
   var $no_form_page = false;

   /// Set true to desactivate auto compute table name
   var $notable = false;

   //Additional foeidls for dictionnary processing
   var $additional_fields_for_dictionnary = array();

   /// Forward entity datas to linked items
   protected $forward_entity_to = array();
   /// Table name cache : set dynamically calling getTable
   protected $table = "";
   /// Foreign key field cache : set dynamically calling getForeignKeyField
   protected $fkfield = "";

   //Forward entity to plugins itemtypes
   static protected $plugins_forward_entity = array();

   const SUCCESS                    = 0; //Process is OK
   const TYPE_MISMATCH              = 1; //Type is not good, value cannot be inserted
   const ERROR_FIELDSIZE_EXCEEDED   = 2; //Value is bigger than the field's size
   const HAS_DUPLICATE              = 3; //Can insert or update because it's duplicating another item
   const NOTHING_TO_DO              = 4; //Nothing to insert or update


   /**
    * Constructor
   **/
   function __construct () {
   }


   /**
    * Return the table used to stor this object
    *
    * @return string
   **/
   function getTable() {

      if (empty($this->table) && !$this->notable) {
         $this->table = getTableForItemType($this->getType());
      }

      return $this->table;
   }


   /**
    * force table value (used for config management for old versions)
    *
    * @return nothing
   **/
   function forceTable($table) {
      $this->table = $table;
   }


   function getForeignKeyField() {

      if (empty($this->fkfield)) {
         $this->fkfield = getForeignKeyFieldForTable($this->getTable());
      }

      return $this->fkfield;
   }


   /**
    * Retrieve an item from the database
    *
    * @param $ID ID of the item to get
    *
    * @return true if succeed else false
   **/
   function getFromDB($ID) {
      global $DB;
      // Make new database object and fill variables

      // != 0 because 0 is consider as empty
      if (strlen($ID)==0) {
         return false;
      }

      $query = "SELECT *
                FROM `".$this->getTable()."`
                WHERE `".$this->getIndexName()."` = '".Toolbox::cleanInteger($ID)."'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)==1) {
            $this->fields = $DB->fetch_assoc($result);
            $this->post_getFromDB();

            return true;
         }
      }

      return false;
   }


   /**
    * Get the identifier of the current item
    *
    * @return ID
   **/
   function getID() {

      if (isset($this->fields[$this->getIndexName()])) {
         return $this->fields[$this->getIndexName()];
      }
      return -1;
   }


   /**
    * Actions done at the end of the getFromDB function
    *
    * @return nothing
   **/
   function post_getFromDB() {
   }


   /**
    * Retrieve all items from the database
    *
    * @param $condition condition used to search if needed (empty get all)
    * @param $order order field if needed
    * @param $limit limit retrieved datas if needed
    *
    * @return true if succeed else false
   **/
   function find($condition="", $order="", $limit="") {
      global $DB;
      // Make new database object and fill variables

      $query = "SELECT *
                FROM `".$this->getTable()."`";

      if (!empty($condition)) {
         $query .= " WHERE $condition";
      }

      if (!empty($order)) {
         $query .= " ORDER BY $order";
      }

      if (!empty($limit)) {
         $query .= " LIMIT ".intval($limit);
      }

      $data = array();
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            while ($line = $DB->fetch_assoc($result)) {
               $data[$line['id']] = $line;
            }
         }
      }

      return $data;
   }


   /**
    * Get the name of the index field
    *
    * @return name of the index field
   **/
   function getIndexName() {
      return "id";
   }


   /**
    * Get an empty item
    *
    *@return true if succeed else false
   **/
   function getEmpty() {
      global $DB;

      //make an empty database object
      $table = $this->getTable();

      if (!empty($table) && $fields = $DB->list_fields($table)) {
         foreach ($fields as $key => $val) {
            $this->fields[$key] = "";
         }
      } else {
         return false;
      }

      if (array_key_exists('entities_id',$this->fields) && isset($_SESSION["glpiactive_entity"])) {
         $this->fields['entities_id'] = $_SESSION["glpiactive_entity"];
      }

      $this->post_getEmpty();

      // Call the plugin hook - $this->fields can be altered
      Plugin::doHook("item_empty", $this);
      return true;
   }


   /**
    * Actions done at the end of the getEmpty function
    *
    * @return nothing
   **/
   function post_getEmpty() {
   }


   /**
    * Get type to register log on
    *
    * @since version 0.83
    *
    * @return array of type + ID
   **/
   function getLogTypeID() {
      return array($this->getType(), $this->fields['id']);
   }


   /**
    * Update the item in the database
    *
    * @param $updates fields to update
    * @param $oldvalues old values of the updated fields
    *
    * @return nothing
   **/
   function updateInDB($updates, $oldvalues=array()) {
      global $DB, $CFG_GLPI;

      foreach ($updates as $field) {
         if (isset($this->fields[$field])) {
            $query  = "UPDATE `".$this->getTable()."`
                       SET `".$field."`";

            if ($this->fields[$field]=="NULL") {
               $query .= " = ".$this->fields[$field];

            } else {
               $query .= " = '".$this->fields[$field]."'";
            }

            $query .= " WHERE `id` ='".$this->fields["id"]."'";

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

      if (count($oldvalues)) {
         Log::constructHistory($this, $oldvalues, $this->fields);
      }

      return true;
   }


   /**
    * Add an item to the database
    *
    * @return new ID of the item is insert successfull else false
   **/
   function addToDB() {
      global $DB;

      //unset($this->fields["id"]);
      $nb_fields = count($this->fields);
      if ($nb_fields>0) {
         // Build query
         $query = "INSERT
                   INTO `".$this->getTable()."` (";

         $i = 0;
         foreach ($this->fields as $key => $val) {
            $fields[$i] = $key;
            $values[$i] = $val;
            $i++;
         }

         for ($i=0 ; $i<$nb_fields; $i++) {
            $query .= "`".$fields[$i]."`";
            if ($i!=$nb_fields-1) {
               $query .= ",";
            }
         }

         $query .= ") VALUES (";
         for ($i=0 ; $i<$nb_fields ; $i++) {

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
            $this->fields['id'] = $DB->insert_id();
            return $this->fields['id'];
         }

      }
      return false;
   }


   /**
    * Restore item = set deleted flag to 0
    *
    * @return true if succeed else false
   **/
   function restoreInDB() {
      global $DB,$CFG_GLPI;

      if ($this->maybeDeleted()) {
         // Auto set date_mod if exsist
         $toadd = '';
         if (isset($this->fields['date_mod'])) {
            $toadd = ", `date_mod` ='".$_SESSION["glpi_currenttime"]."' ";
         }

         $query = "UPDATE `".$this->getTable()."`
                   SET `is_deleted`='0' $toadd
                   WHERE `id` = '".$this->fields['id']."'";

         if ($result = $DB->query($query)) {
            return true;
         }

      }
      return false;
   }


   /**
    * Mark deleted or purge an item in the database
    *
    * @param $force force the purge of the item (not used if the table do not have a deleted field)
    *
    * @return true if succeed else false
   **/
   function deleteFromDB($force=0) {
      global $DB, $CFG_GLPI;

      if ($force==1 || !$this->maybeDeleted()) {
         $this->cleanDBonPurge();
         $this->cleanHistory();
         $this->cleanRelationData();
         $this->cleanRelationTable();

         $query = "DELETE
                   FROM `".$this->getTable()."`
                   WHERE `id` = '".$this->fields['id']."'";

         if ($result = $DB->query($query)) {
            $this->post_deleteFromDB();
            return true;
         }

      } else {
         // Auto set date_mod if exsist
         $toadd = '';
         if (isset($this->fields['date_mod'])) {
            $toadd = ", `date_mod` ='".$_SESSION["glpi_currenttime"]."' ";
         }

         $query = "UPDATE `".$this->getTable()."`
                   SET `is_deleted`='1' $toadd
                   WHERE `id` = '".$this->fields['id']."'";
         $this->cleanDBonMarkDeleted();

         if ($result = $DB->query($query)) {
            return true;
         }

      }

      return false;
   }


   /**
    * Clean data in the tables which have linked the deleted item
    *
    * @return nothing
   **/
   function cleanHistory() {
      global $DB;

      if ($this->dohistory) {
         $query = "DELETE
                   FROM `glpi_logs`
                   WHERE (`itemtype` = '".$this->getType()."'
                          AND `items_id` = '".$this->fields['id']."')";
         $DB->query($query);
      }
   }


   /**
    * Clean data in the tables which have linked the deleted item
    * Clear 1/N Relation
    *
    * @return nothing
   **/
   function cleanRelationData() {
      global $DB, $CFG_GLPI;

      $RELATION = getDbRelations();
      if (isset($RELATION[$this->getTable()])) {
         $newval = (isset($this->input['_replace_by']) ? $this->input['_replace_by'] : 0);

         foreach ($RELATION[$this->getTable()] as $tablename => $field) {
            if ($tablename[0]!='_') {

               $itemtype = getItemTypeForTable($tablename);

               if (!is_array($field)) {
                  foreach ($DB->request($tablename, array($field => $this->fields['id'])) as $data) {
                     if ($object = getItemForItemtype($itemtype)) {
                        $object->update(array('id'            => $data['id'],
                                              $field          => $newval,
                                              '_disablenotif' => true)); // Disable notifs
                     }
                  }
               } else {
                  foreach ($field as $f) {
                     foreach ($DB->request($tablename, array($f => $this->fields['id'])) as $data) {
                        if ($object = getItemForItemtype($itemtype)) {
                           $object->update(array('id'             => $data['id'],
                                                  $f              => $newval,
                                                  '_disablenotif' => true)); // Disable notifs
                        }
                     }
                  }
               }

            }
         }

      }

      // Clean ticket open against the item
      if (in_array($this->getType(),$CFG_GLPI["ticket_types"])) {
         $job = new Ticket();

         $query = "SELECT *
                   FROM `glpi_tickets`
                   WHERE `items_id` = '".$this->fields['id']."'
                         AND `itemtype`='".$this->getType()."'";
         $result = $DB->query($query);

         if ($DB->numrows($result)) {
            while ($data=$DB->fetch_array($result)) {

               if ($CFG_GLPI["keep_tickets_on_delete"]==1) {
                  $input = array();
                  $input['id']       = $data["id"];
                  $input['items_id'] = 0;
                  $input['itemtype'] = '';
                  if ($data['status'] == 'closed') {
                     $input['_disablenotif']= true;
                  }
                  $job->update($input);
               } else {
                  $job->delete(array("id" => $data["id"]));
               }

            }
         }

      }
   }


   /**
    * Actions done after the DELETE of the item in the database
    *
    * @return nothing
   **/
   function post_deleteFromDB() {
   }


   /**
    * Actions done when item is deleted from the database
    *
    * @return nothing
   **/
   function cleanDBonPurge() {
   }


   /**
    * Clean the date in the relation tables for the deleted item
    * Clear N/N Relation
   **/
   function cleanRelationTable() {
      global $CFG_GLPI, $DB;

      // If this type have INFOCOM, clean one associated to purged item
      if (in_array($this->getType(),$CFG_GLPI['infocom_types'])) {
         $infocom = new Infocom();

         if ($infocom->getFromDBforDevice($this->getType(), $this->fields['id'])) {
             $infocom->delete(array('id' => $infocom->fields['id']));
         }
      }

      // If this type have NETPORT, clean one associated to purged item
      if (in_array($this->getType(),$CFG_GLPI['networkport_types'])) {
         $query = "SELECT `id`
                   FROM `glpi_networkports`
                   WHERE (`items_id` = '".$this->fields['id']."'
                          AND `itemtype` = '".$this->getType()."')";
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
                   WHERE (`items_id` = '".$this->fields['id']."'
                          AND `itemtype` = '".$this->getType()."')";
         $result = $DB->query($query);
      }

      // If this type is RESERVABLE clean one associated to purged item
      if (in_array($this->getType(),$CFG_GLPI['reservation_types'])) {
         $rr = new ReservationItem();

         if ($rr->getFromDBbyItem($this->getType(), $this->fields['id'])) {
             $rr->delete(array('id' => $infocom->fields['id']));
         }
      }

      // If this type have CONTRACT, clean one associated to purged item
      if (in_array($this->getType(),$CFG_GLPI['contract_types'])) {
         $ci = new Contract_Item();
         $ci->cleanDBonItemDelete($this->getType(), $this->fields['id']);
      }

      // If this type have DOCUMENT, clean one associated to purged item
      if (in_array($this->getType(),$CFG_GLPI["document_types"])) {
         $di = new Document_Item();
         $di->cleanDBonItemDelete($this->getType(), $this->fields['id']);
      }
   }


   /**
    * Actions done when item flag deleted is set to an item
    *
    * @return nothing
   **/
   function cleanDBonMarkDeleted() {
   }


   // Common functions
   /**
    * Add an item in the database with all it's items.
    *
    * @param $input array : the _POST vars returned by the item form when press add
    * @param options an array with the insert options
    *   - unicity_message : do not display message if item it a duplicate (default is yes)
    * @param $history boolean : do history log ?
    *
    * @return integer the new ID of the added item (or false if fail)
   **/
   function add($input, $options=array(), $history=true) {
      global $DB, $CFG_GLPI;

      if ($DB->isSlave()) {
         return false;
      }

      // Store input in the object to be available in all sub-method / hook
      $this->input = $input;

      // Call the plugin hook - $this->input can be altered
      // This hook get the data from the form, not yet altered
      Plugin::doHook("pre_item_add", $this);

      if ($this->input && is_array($this->input)) {

         if (isset($this->input['add'])) {
            $this->input['_add'] = $this->input['add'];
            unset($this->input['add']);
         }

         $this->input = $this->prepareInputForAdd($this->input);
      }

      if ($this->input && is_array($this->input)) {
         // Call the plugin hook - $this->input can be altered
         // This hook get the data altered by the object method
         Plugin::doHook("post_prepareadd", $this);
      }

      if ($this->input && is_array($this->input)) {
         //Check values to inject
         $this->filterValues(!isCommandLine());
      }

      if ($this->input && is_array($this->input)) {
         $this->fields = array();
         $table_fields = $DB->list_fields($this->getTable());

         // fill array for add
         foreach ($this->input as $key => $val) {
            if ($key[0]!='_' && isset($table_fields[$key])) {
               $this->fields[$key] = $this->input[$key];
            }
         }

         // Auto set date_mod if exsist
         if (isset($table_fields['date_mod'])) {
            $this->fields['date_mod'] = $_SESSION["glpi_currenttime"];
         }

         if ($this->checkUnicity(true, $options)) {

            if ($this->addToDB()) {
               $this->addMessageOnAddAction();
               $this->post_addItem();

               if ($this->dohistory && $history) {
                  $changes[0] = 0;
                  $changes[1] = $changes[2] = "";

                  Log::history($this->fields["id"], $this->getType(), $changes, 0,
                               Log::HISTORY_CREATE_ITEM);
               }

                // Auto create infocoms
               if ($CFG_GLPI["auto_create_infocoms"]
                   && in_array($this->getType(), $CFG_GLPI["infocom_types"])) {

                  $ic = new Infocom();
                  if (!$ic->getFromDBforDevice($this->getType(), $this->fields['id'])) {
                     $ic->add(array('itemtype' => $this->getType(),
                                    'items_id' => $this->fields['id']));
                  }
               }

               // If itemtype is in infocomtype and if states_id field is filled
               // and item is not a template
               if (in_array($this->getType(),$CFG_GLPI["infocom_types"])
                   && isset($this->input['states_id'])
                            && (!isset($this->input['is_template'])
                                || !$this->input['is_template'])) {

                  //Check if we have to automatical fill dates
                  Infocom::manageDateOnStatusChange($this);
               }
               Plugin::doHook("item_add", $this);
               return $this->fields['id'];
            }
         }

      }
      $this->last_status = self::NOTHING_TO_DO;
      return false;
   }


   /**
    * Get the link to an item
    *
    * @param $with_comment Display comments
    *
    * @return string : HTML link
   **/
   function getLink($with_comment=0) {
      global $CFG_GLPI;

      if (!isset($this->fields['id'])) {
         return '';
      }

      if ($this->no_form_page) {
         return $this->getNameID($with_comment);

      } else {
         $link_item = $this->getFormURL();

         if (!$this->can($this->fields['id'],'r')) {
            return $this->getNameID($with_comment);
         }

         $link  = $link_item;
         $link .= (strpos($link,'?') ? '&amp;':'?').'id=' . $this->fields['id'];
         $link .= ($this->isTemplate() ? "&amp;withtemplate=1" : "");

         return "<a href='$link'>".$this->getNameID($with_comment)."</a>";
      }

   }


   /**
    * Get the link url to an item
    *
    * @return string : HTML link
   **/
   function getLinkURL() {
      global $CFG_GLPI;

      if (!isset($this->fields['id'])) {
         return '';
      }

      $link_item = $this->getFormURL();

      $link  = $link_item;
      $link .= (strpos($link,'?') ? '&amp;':'?').'id=' . $this->fields['id'];
      $link .= ($this->isTemplate() ? "&amp;withtemplate=1" : "");

      return $link;
   }


   /**
    * Add a message on add action
   **/
   function addMessageOnAddAction() {
      global $CFG_GLPI, $LANG;

      $link = $this->getFormURL();
      if (!isset($link)) {
         return;
      }

      $addMessAfterRedirect = false;
      if (isset($this->input['_add'])) {
         $addMessAfterRedirect = true;
      }

      if (isset($this->input['_no_message']) || !$this->auto_message_on_action) {
         $addMessAfterRedirect = false;
      }

      if ($addMessAfterRedirect) {
         if (($name=$this->getName()) == NOT_AVAILABLE) {
            $this->fields['name'] = $this->getTypeName()." : ".$LANG['common'][2]
                                    ." ".$this->fields['id'];
         }
         $display = (isset($this->input['_no_message_link'])?$this->getNameID()
                                                            :$this->getLink());

         // Do not display quotes
         Session::addMessageAfterRedirect($LANG['common'][70]."&nbsp;: ".stripslashes($display));

      }
   }


   /**
    * Prepare input datas for adding the item
    *
    * @param $input datas used to add the item
    *
    * @return the modified $input array
   **/
   function prepareInputForAdd($input) {
      return $input;
   }


   /**
    * Actions done after the ADD of the item in the database
    *
    * @return nothing
   **/
   function post_addItem() {
   }


   /**
    * Update some elements of an item in the database.
    *
    * @param $input array : the _POST vars returned by the item form when press update
    * @param $history boolean : do history log ?
    * @param options an array with the insert options
    *
    * @return boolean : true on success
   **/
   function update($input, $history=1, $options=array()) {
      global $DB, $CFG_GLPI;

      if ($DB->isSlave()) {
         return false;
      }

      if (!$this->getFromDB($input[$this->getIndexName()])) {
         return false;
      }

      // Store input in the object to be available in all sub-method / hook
      $this->input = $input;

      // Plugin hook - $this->input can be altered
      Plugin::doHook("pre_item_update", $this);

      if ($this->input && is_array($this->input)) {
         $this->input = $this->prepareInputForUpdate($this->input);

         if (isset($this->input['update'])) {
            $this->input['_update'] = $this->input['update'];
            unset($this->input['update']);
         }

         $this->filterValues(!isCommandLine());
      }

      // Valid input for update
      if ($this->checkUnicity(false, $options)) {
         if ($this->input && is_array($this->input)) {
            // Fill the update-array with changes
            $x = 0;
            $this->updates   = array();
            $this->oldvalues = array();

            foreach ($this->input as $key => $val) {
               if (array_key_exists($key,$this->fields)) {

                  // Prevent history for date statement (for date for example)
                  if (is_null($this->fields[$key]) && $this->input[$key]=='NULL') {
                     $this->fields[$key] = 'NULL';
                  }

                  if (mysql_real_escape_string($this->fields[$key]) != $this->input[$key]) {
                     if ($key!="id") {

                        // Store old values
                        if (!in_array($key,$this->history_blacklist)) {
                           $this->oldvalues[$key] = $this->fields[$key];
                        }

                        $this->fields[$key] = $this->input[$key];
                        $this->updates[$x]  = $key;
                        $x++;
                     }
                  }

               }
            }

            if (count($this->updates)) {
               if (array_key_exists('date_mod',$this->fields)) {
                  // is a non blacklist field exists
                  if (count(array_diff($this->updates, $this->history_blacklist)) > 0) {
                     $this->fields['date_mod'] = $_SESSION["glpi_currenttime"];
                     $this->updates[$x++]      = 'date_mod';
                  }
               }
               $this->pre_updateInDB();

               if (count($this->updates)) {
                  if ($this->updateInDB($this->updates,
                                        ($this->dohistory && $history ? $this->oldvalues
                                                                      : array()))) {
                     $this->addMessageOnUpdateAction();
                     Plugin::doHook("item_update", $this);

                     //Fill forward_entity_to array with itemtypes coming from plugins
                     if (isset(self::$plugins_forward_entity[$this->getType()])) {
                        foreach (self::$plugins_forward_entity[$this->getType()] as $itemtype) {
                           $this->forward_entity_to[] = $itemtype;
                        }
                     }
                     // forward entity information if needed
                     if (count($this->forward_entity_to)
                         && (in_array("entities_id",$this->updates)
                             || in_array("is_recursive",$this->updates)) ) {
                        $this->forwardEntityInformations();
                     }

                     // If itemtype is in infocomtype and if states_id field is filled
                     // and item not a template
                     if (in_array($this->getType(),$CFG_GLPI["infocom_types"])
                         && in_array('states_id',$this->updates)
                         && ($this->getField('is_template') != NOT_AVAILABLE)) {
                        //Check if we have to automatical fill dates
                        Infocom::manageDateOnStatusChange($this, false);
                     }
                  }
               }
            }
            $this->post_updateItem($history);
            return true;
         }
      }

      return false;
   }


   /**
    * Forward entity informations to linked items
   **/
   protected function forwardEntityInformations() {
      global $DB;

      if (!isset($this->fields['id']) || !($this->fields['id']>=0)) {
         return false;
      }

      if (count($this->forward_entity_to)) {
         foreach ($this->forward_entity_to as $type) {
            $item  = new $type();
            $query = "SELECT `id`
                      FROM `".$item->getTable()."`
                      WHERE ";

            if ($item->isField('itemtype')) {
               $query .= " `itemtype` = '".$this->getType()."'
                          AND `items_id` = '".$this->fields['id']."'";
            } else {
               $query .= " `".$this->getForeignKeyField()."` = '".$this->fields['id']."'";
            }

            $input = array('entities_id' => $this->getEntityID());

            if ($this->maybeRecursive()) {
               $input['is_recursive'] = $this->isRecursive();
            }

            foreach ($DB->request($query) as $data) {
               $input['id'] = $data['id'];
               $item->update($input);
            }
         }
      }
   }


   /**
    * Add a message on update action
   **/
   function addMessageOnUpdateAction() {
      global $CFG_GLPI, $LANG;

      $link = $this->getFormURL();
      if (!isset($link)) {
         return;
      }

      $addMessAfterRedirect = false;

      if (isset($this->input['_update'])) {
         $addMessAfterRedirect = true;
      }

      if (isset($this->input['_no_message']) || !$this->auto_message_on_action) {
         $addMessAfterRedirect = false;
      }

      if ($addMessAfterRedirect) {

         // Do not display quotes
         if (isset($this->fields['name'])) {
            $this->fields['name'] = stripslashes($this->fields['name']);
         } else {
            $this->fields['name'] = $this->getTypeName()." : ".$LANG['common'][2]." ".
                                    $this->fields['id'];
         }

         Session::addMessageAfterRedirect($LANG['common'][71] . "&nbsp;: " .
                                          (isset($this->input['_no_message_link'])?$this->getNameID()
                                                                                  :$this->getLink()));
      }

   }


   /**
    * Prepare input datas for updating the item
    *
    * @param $input datas used to update the item
    *
    * @return the modified $input array
   **/
   function prepareInputForUpdate($input) {
      return $input;
   }


   /**
    * Actions done after the UPDATE of the item in the database
    *
    * @param $history store changes history ?
    *
    * @return nothing
   **/
   function post_updateItem($history=1) {
   }


   /**
    * Actions done before the UPDATE of the item in the database
    *
    * @return nothing
   **/
   function pre_updateInDB() {
   }


   /**
    * Delete an item in the database.
    *
    * @param $input array : the _POST vars returned by the item form when press delete
    * @param $force boolean : force deletion
    * @param $history boolean : do history log ?
    *
    * @return boolean : true on success
   **/
   function delete($input, $force=0, $history=1) {
      global $DB;

      if ($DB->isSlave()) {
         return false;
      }

      if (!$this->getFromDB($input[$this->getIndexName()])) {
         return false;
      }

      if ($this->isTemplate() || !$this->maybeDeleted()) {
         $force = 1;
      }
      // Store input in the object to be available in all sub-method / hook
      $this->input = $input;

      if (isset($this->input['purge'])) {
         $this->input['_purge'] = $this->input['purge'];
         unset($this->input['purge']);
      }

      if (isset($this->input['delete'])) {
         $this->input['_delete'] = $this->input['delete'];
         unset($this->input['delete']);
      }

      // Purge
      if ($force) {
         Plugin::doHook("pre_item_purge", $this);
      } else {
         Plugin::doHook("pre_item_delete", $this);
      }


      if (!is_array($this->input)) {
         // $input clear by a hook to cancel delete
         return false;
      }

      if ($this->pre_deleteItem()) {
         if ($this->deleteFromDB($force)) {

            if ($force) {
               $this->addMessageOnPurgeAction();
               $this->post_purgeItem();
               Plugin::doHook("item_purge", $this);

            } else {
               $this->addMessageOnDeleteAction();

               if ($this->dohistory && $history) {
                  $changes[0] = 0;
                  $changes[1] = $changes[2] = "";

                  Log::history($this->fields["id"], $this->getType(), $changes, 0,
                               Log::HISTORY_DELETE_ITEM);
               }

               $this->post_deleteItem();
               Plugin::doHook("item_delete",$this);
            }
            return true;
         }

      }
      return false;
   }


   /**
    * Actions done after the DELETE (mark as deleted) of the item in the database
    *
    * @return nothing
   **/
   function post_deleteItem() {
   }


   /**
    * Actions done after the PURGE of the item in the database
    *
    * @return nothing
   **/
   function post_purgeItem() {
   }


   /**
    * Add a message on delete action
   **/
   function addMessageOnDeleteAction() {
      global $CFG_GLPI, $LANG;

      $link = $this->getFormURL();
      if (!isset($link)) {
         return;
      }

      if (!$this->maybeDeleted()) {
         return;
      }

      $addMessAfterRedirect=false;
      if (isset($this->input['_delete'])) {
         $addMessAfterRedirect = true;
      }

      if (isset($this->input['_no_message']) || !$this->auto_message_on_action) {
         $addMessAfterRedirect = false;
      }

      if ($addMessAfterRedirect) {
         Session::addMessageAfterRedirect($LANG['common'][72] . "&nbsp;: " .
                                          (isset($this->input['_no_message_link'])?$this->getNameID()
                                                                                  :$this->getLink()));
      }
   }


   /**
    * Add a message on purge action
   **/
   function addMessageOnPurgeAction() {
      global $CFG_GLPI, $LANG;

      $link = $this->getFormURL();
      if (!isset($link)) {
         return;
      }

      $addMessAfterRedirect = false;

      if (isset($this->input['_purge']) || isset($this->input['_delete'])) {
         $addMessAfterRedirect = true;
      }

      if (isset($this->input['_no_message']) || !$this->auto_message_on_action) {
         $addMessAfterRedirect = false;
      }

      if ($addMessAfterRedirect) {
         Session::addMessageAfterRedirect($LANG['common'][73]);
      }
   }


   /**
    * Actions done before the DELETE of the item in the database /
    * Maybe used to add another check for deletion
    *
    * @return bool : true if item need to be deleted else false
   **/
   function pre_deleteItem() {
      return true;
   }


   /**
    * Restore an item trashed in the database.
    *
    * @param $input array : the _POST vars returned by the item form when press restore
    * @param $history boolean : do history log ?
    *
    * @return boolean : true on success
   **/
   function restore($input, $history=1) {

      if (!$this->getFromDB($input[$this->getIndexName()])) {
         return false;
      }

      if (isset($input['restore'])) {
         $input['_restore'] = $input['restore'];
         unset($input['restore']);
      }

      // Store input in the object to be available in all sub-method / hook
      $this->input = $input;
      Plugin::doHook("pre_item_restore", $this);

      if ($this->restoreInDB()) {
         $this->addMessageOnRestoreAction();

         if ($this->dohistory && $history) {
            $changes[0] = 0;
            $changes[1] = $changes[2] = "";
            Log::history($this->input["id"], $this->getType(), $changes, 0,
                         Log::HISTORY_RESTORE_ITEM);
         }

         $this->post_restoreItem();
         Plugin::doHook("item_restore", $this);
         return true;
      }

      return false;
   }


   /**
    * Actions done after the restore of the item
    *
    * @return nothing
   **/
   function post_restoreItem() {
   }


   /**
    * Add a message on restore action
   **/
   function addMessageOnRestoreAction() {
      global $CFG_GLPI, $LANG;

      $link = $this->getFormURL();
      if (!isset($link)) {
         return;
      }

      $addMessAfterRedirect = false;
      if (isset($this->input['_restore'])) {
         $addMessAfterRedirect = true;
      }

      if (isset($this->input['_no_message']) || !$this->auto_message_on_action) {
         $addMessAfterRedirect = false;
      }

      if ($addMessAfterRedirect) {
         Session::addMessageAfterRedirect($LANG['common'][74] . "&nbsp;: " .
                                          (isset($this->input['_no_message_link'])?$this->getNameID()
                                                                                  :$this->getLink()));
      }
   }


   /**
    * Reset fields of the item
   **/
   function reset() {
      $this->fields = array();
   }


   /**
    * Have I the global right to add an item for the Object
    *
    * May be overloaded if needed (ex KnowbaseItem)
    *
    * @since version 0.83
    *
    * @param $type itemtype of object to add
    *
    * @return rights
   **/
   function canAddItem($type) {
      return $this->can($this->getID(), 'w');
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
         return Session::haveAccessToEntity($this->getEntityID());
      }
      // else : Global item
      return true;
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
      global $CFG_GLPI;

      if (!$this->canCreateItem()) {
         return false;
      }

      // Can delete an object with Infocom only if can delete Infocom
      if (in_array($this->getType(), $CFG_GLPI['infocom_types'])) {
         $infocom = new Infocom();

         if ($infocom->getFromDBforDevice($this->getType(), $this->fields['id'])) {
            return $infocom->canDelete();
         }

      }

      return true;
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
            return Session::haveAccessToEntity($this->getEntityID(), $this->isRecursive());
         }
         //  else : No recursive item
         return Session::haveAccessToEntity($this->getEntityID());
      }
      //  else : Global item
      return true;
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
      $RELATION  = getDbRelations();

      if ($this instanceof CommonTreeDropdown) {
         $f = getForeignKeyFieldForTable($this->getTable());

         if (countElementsInTable($this->getTable(),
                                  "`$f`='$ID' AND entities_id NOT IN $entities")>0) {
            return false;
         }
      }

      if (isset($RELATION[$this->getTable()])) {
         foreach ($RELATION[$this->getTable()] as $tablename => $field) {
            if ($tablename[0]!='_') {

               $itemtype = getItemTypeForTable($tablename);
               $item     = new $itemtype();

               if ($item->isEntityAssign()) {

                  // 1->N Relation
                  if (is_array($field)) {
                     foreach ($field as $f) {
                        if (countElementsInTable($tablename,
                                                "`$f`='$ID' AND entities_id NOT IN $entities")>0) {
                           return false;
                        }
                     }

                  } else {
                     if (countElementsInTable($tablename,
                                             "`$field`='$ID' AND entities_id NOT IN $entities")>0) {
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
                              $itemtype  = $data["itemtype"];
                              $itemtable = getTableForItemType($itemtype);
                              $item      = new $itemtype();

                              if ($item->isEntityAssign()) {
                                 if (countElementsInTable(array($tablename, $itemtable),
                                                         "`$tablename`.`$field`='$ID'
                                                         AND `$tablename`.`$typefield`='$itemtype'
                                                         AND `$tablename`.`$devfield`=`$itemtable`.id
                                                         AND `$itemtable`.`entities_id`
                                                               NOT IN $entities")>'0') {
                                    return false;
                                 }
                              }

                           }
                        }

                     // Search for another N->N Relation
                     } else if ($othertable != $this->getTable() && isset($rel[$tablename])) {
                        $itemtype = getItemTypeForTable($othertable);
                        $item     = new $itemtype();

                        if ($item->isEntityAssign()) {
                           if (is_array($rel[$tablename])) {
                              foreach ($rel[$tablename] as $otherfield) {
                                 if (countElementsInTable(array($tablename, $othertable),
                                                         "`$tablename`.`$field`='$ID'
                                                         AND `$tablename`.`$otherfield`
                                                                     =`$othertable`.id
                                                         AND `$othertable`.`entities_id`
                                                                     NOT IN $entities")>'0') {
                                    return false;
                                 }
                              }

                           } else {
                              $otherfield = $rel[$tablename];
                              if (countElementsInTable(array($tablename, $othertable),
                                                      "`$tablename`.`$field`=$ID
                                                      AND `$tablename`.`$otherfield`=`$othertable`.id
                                                      AND `$othertable`.`entities_id`
                                                                  NOT IN $entities")>'0') {
                                 return false;
                              }
                           }

                        }
                     }
                  }
               }
            }
         }
      }

      // Doc links to this item
      if ($this->getType() > 0
          && countElementsInTable(array('glpi_documents_items', 'glpi_documents'),
                                  "`glpi_documents_items`.`items_id`='$ID'
                                   AND `glpi_documents_items`.`itemtype`=".$this->getType()."
                                   AND `glpi_documents_items`.`documents_id`=`glpi_documents`.`id`
                                   AND `glpi_documents`.`entities_id` NOT IN $entities")>'0') {
         return false;
      }
      // TODO : do we need to check all relations in $RELATION["_virtual_device"] for this item

      return true;
   }


   /**
    * check if this action can be done on this field of this item by massive actions
    *
    * @since 0.83
    *
    * @param $action    string   name of the action
    * @param $field     integer  id of the field
    * @param $value     string   value of the field
    *
   **/
   function canMassiveAction($action, $field, $value){
      return true;
   }


   /**
    * Display a 2 columns Footer for Form buttons
    * Close the form is user can edit
    *
    * @param $options array
    *     - withtemplate : 1 for newtemplate, 2 for newobject from template
    *     - colspan for each column (default 2)
    *     - candel : set to false to hide "delete" button
    *     - canedit : set to false to hide all buttons
    *     - addbuttons : array of buttons to add
    *
   **/
   function showFormButtons($options=array()) {
      global $LANG, $CFG_GLPI;

      // for single object like config
      if (isset($this->fields['id'])) {
         $ID = $this->fields['id'];
      } else {
        $ID = 1;
      }

      $params['colspan']      = 2;
      $params['withtemplate'] = '';
      $params['candel']       = true;
      $params['canedit']      = true;
      $params['addbuttons']   = array();

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      if (!$params['canedit']
          || (!$this->can($ID,'w') && !$this->can($ID,'d'))) {
         echo "</table></div>";
         // Form Header always open form
         if (!$params['canedit']) {
            Html::closeForm();
         }
         return false;
      }
      echo "<tr>";

      if ($params['withtemplate'] ||$this->isNewID($ID)) {
         echo "<td class='tab_bg_2 center' colspan='".($params['colspan']*2)."'>";

         if ($ID<=0 || $params['withtemplate']==2) {
            echo "<input type='submit' name='add' value=\"".$LANG['buttons'][8]."\" class='submit'>";
         } else {
            echo "<input type='submit' name='update' value=\"".$LANG['buttons'][7]."\" class='submit'>";
         }

      } else {
         if ($params['candel'] && !$this->can($ID,'d')) {
            $params['candel'] = false;
         }

         if ($params['candel']) {
            echo "<td class='tab_bg_2 center' colspan='".$params['colspan']."'>\n";
            echo "<input type='submit' name='update' value=\"".$LANG['buttons'][7]."\" class='submit'>";
            echo "</td>\n";
            echo "<td class='tab_bg_2 center' colspan='".$params['colspan']."' >\n";

            if ($this->isDeleted()) {
               echo "<input type='submit' name='restore' value=\"".$LANG['buttons'][21]."\"
                      class='submit'>";
               echo "<span class='small_space'>
                     <input type='submit' name='purge' value=\"".$LANG['buttons'][22]."\"
                      class='submit'>
                     </span>";

            } else {
               if (!$this->maybeDeleted()) {
                  echo "<input type='submit' name='delete' value=\"".$LANG['buttons'][22]."\"
                         class='submit' ".Html::addConfirmationOnAction($LANG['common'][50]).">";
               } else {
                  echo "<input type='submit' name='delete' value='" . $LANG['buttons'][6] ."'
                         class='submit'>";
               }
            }

         } else {
            echo "<td class='tab_bg_2 center' colspan='".($params['colspan']*2)."'>\n";
            echo "<input type='submit' name='update' value=\"".$LANG['buttons'][7]."\"
                   class='submit'>";
         }
         if ($this->isField('date_mod')) {
            echo "<input type='hidden' name='_read_date_mod' value='".$this->getField('date_mod')."'>";
         }
      }

      if ($ID>0) {
         echo "<input type='hidden' name='id' value='$ID'>";
      }
      echo "</td>";
      echo "</tr>\n";

      if ($params['canedit'] && count($params['addbuttons'])) {
         echo "<tr>";
         if (($params['colspan']*2 - count($params['addbuttons'])) >0) {
            echo "<td colspan='".($params['colspan']*2 - count($params['addbuttons']))."'>&nbsp;</td>";
         }
         foreach ($params['addbuttons'] as $key => $val) {
            echo "<td><input class='submit' type='submit' name='$key' value=\"".
                Html::entities_deep($val)."\"></td>";
         }
         echo "</tr>";
      }

      // Close for Form
      echo "</table></div>";
      Html::closeForm();
   }


   /**
    *
    * Display a 2 columns Header 1 for ID, 1 for recursivity menu
    * Open the form is user can edit
    *
    * @param $options array
    *     - target for the Form
    *     - withtemplate : 1 for newtemplate, 2 for newobject from template
    *     - colspan for each column (default 2)
    *     - formoptions string (javascript p.e.)
    *     - canedit boolean edit mode of form ?
    *
   **/
   function showFormHeader($options=array()) {
      global $LANG, $CFG_GLPI;

      $ID = $this->fields['id'];
      $params['target']       = $this->getFormURL();
      $params['colspan']      = 2;
      $params['withtemplate'] = '';
      $params['formoptions']  = '';
      $params['canedit']      = true;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      // Template case : clean entities data
      if ($params['withtemplate'] == 2 && $this->isEntityAssign()) {
         $this->fields['entities_id']  = $_SESSION['glpiactive_entity'];
         if ($this->maybeRecursive()) {
            $this->fields["is_recursive"] = 0;
         }
      }

      if ($this->can($ID,'w')) {
         echo "<form name='form' method='post' action='".$params['target']."' ".
                $params['formoptions'].">";

         //Should add an hidden entities_id field ?
         //If the table has an entities_id field
         if ($this->isField("entities_id")) {
            //The object type can be assigned to an entity
            if ($this->isEntityAssign()) {
               // TODO CommonDBChild must not use current entity, but parent entity

               if (isset($params['entities_id'])) {
                  $entity = $this->fields['entities_id'] = $params['entities_id'];

               } else if ($this->isNewID($ID) || $params['withtemplate'] == 2) {
                  //It's a new object to be added
                  $entity = $_SESSION['glpiactive_entity'];

               } else {
                  //It's an existing object to be displayed
                  $entity = $this->fields['entities_id'];
               }

               echo "<input type='hidden' name='entities_id' value='$entity'>";

            // For Rules except ruleticket and slalevel
            } else if ($this->getType() != 'User') {
               echo "<input type='hidden' name='entities_id' value='0'>";

            }
         }

         // No link on popup window
         if (isset($_GET['popup']) && $_GET['popup']) {
            echo "<input type='hidden' name='_no_message_link' value='1'>";
         }
      }

      echo "<div class='spaced' id='tabsbody'>";
      echo "<table class='tab_cadre_fixe'>";

      echo "<tr><th colspan='".$params['colspan']."'>";

      if (!empty($params['withtemplate'])
          && $params['withtemplate'] == 2
          && !$this->isNewID($ID)) {

         echo "<input type='hidden' name='template_name' value='".$this->fields["template_name"]."'>";
         echo $LANG['buttons'][8]." - ".$LANG['common'][13]."&nbsp;: ".$this->fields["template_name"];

      } else if (!empty($params['withtemplate']) && $params['withtemplate'] == 1) {
         echo "<input type='hidden' name='is_template' value='1'>\n";
         echo $LANG['common'][6]."&nbsp;: ";
         Html::autocompletionTextField($this, "template_name", array('size' => 25));

      } else if ($this->isNewID($ID)) {
         echo $LANG['common'][87];

      } else {
         echo $this->getTypeName(1)." - ".$LANG['common'][2]." $ID";
      }

      if (isset($this->fields["entities_id"])
          && Session::isMultiEntitiesMode()
          && $this->isEntityAssign()) {

         echo "&nbsp;(".Dropdown::getDropdownName("glpi_entities", $this->fields["entities_id"]).")";
      }

      echo "</th><th colspan='".$params['colspan']."'>";

      if (get_class($this)=='Entity') {
         // is recursive but cannot be change

      } else {
         if ($this->maybeRecursive()){
            if (Session::isMultiEntitiesMode()) {
                echo $LANG['entity'][9]."&nbsp;:&nbsp;";

                if ($params['canedit']) {
                   if (!$this->can($ID,'recursive')) {
                      echo Dropdown::getYesNo($this->fields["is_recursive"]);
                      $comment = $LANG['common'][86];
                      // CommonDBChild : entity data is get or copy from parent

                   } else if ( $this instanceof CommonDBChild) {
                      echo Dropdown::getYesNo($this->isRecursive());
                      $comment = $LANG['common'][91];

                   } else if ( !$this->canUnrecurs()) {
                      echo Dropdown::getYesNo($this->fields["is_recursive"]);
                      $comment = $LANG['common'][84];

                   } else {
                      Dropdown::showYesNo("is_recursive", $this->fields["is_recursive"]);
                      $comment = $LANG['common'][85];
                   }
                   echo "&nbsp;";
                   Html::showToolTip($comment);
                } else {
                   echo Dropdown::getYesNo($this->fields["is_recursive"]);
                }
            } else {
               echo "<input type='hidden' name='is_recursive' value='0'>";
            }
         } else {
            echo "&nbsp;";
         }
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
      return (empty($ID) || $ID<=0);
   }

   /**
    * is the current object a new  one
    *
    * @since version 0.83
    *
    * @return boolean
    */
   function isNewItem() {

      if (isset($this->fields['id'])) {
         return $this->isNewID($this->fields['id']);
      }
      return true;
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
   function can($ID, $right, &$input=NULL) {
      // Clean ID value
      $ID = Toolbox::cleanInteger($ID);
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

         if ($this->isPrivate() && $this->fields['users_id']===Session::getLoginUserID()) {
            return true;
         }
         return ($this->canCreate() && $this->canCreateItem());

      }
      // else : Get item if not already loaded
      if (!isset($this->fields['id']) || $this->fields['id']!=$ID) {
         // Item not found : no right
         if (!$this->getFromDB($ID)) {
            return false;
         }
      }

      switch ($right) {
         case 'r' :
            // Personnal item
            if ($this->isPrivate() && $this->fields['users_id']===Session::getLoginUserID()) {
               return true;
            }
            return ($this->canView() && $this->canViewItem());

         case 'w' :
            // Personnal item
            if ($this->isPrivate() && $this->fields['users_id']===Session::getLoginUserID()) {
               return true;
            }
            return ($this->canUpdate() && $this->canUpdateItem());

         case 'd' :
            // Personnal item
            if ($this->isPrivate() && $this->fields['users_id']===Session::getLoginUserID()) {
               return true;
            }
            return ($this->canDelete() && $this->canDeleteItem());

         case 'recursive' :
            if ($this->isEntityAssign() && $this->maybeRecursive()) {
               if ($this->canCreate() && Session::haveAccessToEntity($this->getEntityID())) {
                  // Can make recursive if recursive access to entity
                  return Session::haveRecursiveAccessToEntity($this->getEntityID());
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
    *
    * @return nothing
   **/
   function check($ID,$right,&$input=NULL) {
      global $CFG_GLPI;

      // Check item exists
      if (!$this->isNewID($ID) && !$this->getFromDB($ID)) {
         // Gestion timeout session
         if (!Session::getLoginUserID()) {
            Html::redirect($CFG_GLPI["root_doc"] . "/index.php");
            exit ();
         }
         Html::displayNotFoundError();

      } else {
         if (!$this->can($ID,$right,$input)) {
            // Gestion timeout session
            if (!Session::getLoginUserID()) {
               Html::redirect($CFG_GLPI["root_doc"] . "/index.php");
               exit ();
            }
            Html::displayRightError();
         }
      }
   }


   /**
    * Check global right on an object
    *
    * @param $right Right to check : c / r / w / d
    *
    * @return nothing
   **/
   function checkGlobal($right) {
      global $CFG_GLPI;

      if (!$this->canGlobal($right)) {
         // Gestion timeout session
         if (!Session::getLoginUserID()) {
            Html::redirect($CFG_GLPI["root_doc"] . "/index.php");
            exit ();
         }
         Html::displayRightError();
      }
   }


   /**
    * Get global right on an object
    *
    * @param $right Right to check : c / r / w / d
    *
    * @return nothing
   **/
   function canGlobal($right) {

      switch ($right) {
         case 'r' :
            return $this->canView();

         case 'w' :
            return $this->canUpdate();

         case 'c' :
            return $this->canCreate();

         case 'd' :
            return $this->canDelete();
      }

      return false;
   }


   /**
    * Get the ID of entity assigned to the object
    *
    * Can be overloaded (ex : infocom)
    *
    * @return ID of the entity
   **/
   function getEntityID() {

      if ($this->isEntityAssign()) {
         return $this->fields["entities_id"];
      }
      return  -1;
   }


   /**
    * Is the object assigned to an entity
    *
    * Can be overloaded (ex : infocom)
    *
    * @return boolean
   **/
   function isEntityAssign() {

      if (!array_key_exists('id',$this->fields)) {
         $this->getEmpty();
      }
      return array_key_exists('entities_id', $this->fields);
   }


   /**
    * Is the object may be recursive
    *
    * Can be overloaded (ex : infocom)
    *
    * @return boolean
   **/
   function maybeRecursive() {

      if (!array_key_exists('id',$this->fields)) {
         $this->getEmpty();
      }
      return array_key_exists('is_recursive', $this->fields);
   }


   /**
    * Is the object recursive
    *
    * Can be overloaded (ex : infocom)
    *
    * @return boolean
   **/
   function isRecursive() {

      if ($this->maybeRecursive()) {
         return $this->fields["is_recursive"];
      }
      // Return integer value to be used to fill is_recursive field
      return 0;
   }


   /**
    * Is the object may be deleted
    *
    * @return boolean
   **/
   function maybeDeleted() {

      if (!isset($this->fields['id'])) {
         $this->getEmpty();
      }
      return array_key_exists('is_deleted', $this->fields);
   }


   /**
    * Is the object deleted
    *
    * @return boolean
   **/
   function isDeleted() {

      if ($this->maybeDeleted()) {
         return $this->fields["is_deleted"];
      }
      // Return integer value to be used to fill is_deleted field
      return 0;

   }


   /**
    * Is the object may be a template
    *
    * @return boolean
   **/
   function maybeTemplate() {

      if (!isset($this->fields['id'])) {
         $this->getEmpty();
      }
      return isset($this->fields['is_template']);
   }


   /**
    * Is the object a template
    *
    * @return boolean
   **/
   function isTemplate() {

      if ($this->maybeTemplate()) {
         return $this->fields["is_template"];
      }
      // Return integer value to be used to fill is_template field
      return 0;
   }


   /**
    * Is the object may be recursive
    *
    * @return boolean
   **/
   function maybePrivate() {

      if (!isset($this->fields['id'])) {
         $this->getEmpty();
      }
      return (array_key_exists('is_private', $this->fields)
              && array_key_exists('users_id', $this->fields));
   }


   /**
    * Is the object private
    *
    * @return boolean
   **/
   function isPrivate() {

      if ($this->maybePrivate()) {
         return $this->fields["is_private"];
      }
      return false;
   }


   /**
    * Return the SQL command to retrieve linked object
    *
    * @return a SQL command which return a set of (itemtype, items_id)
   **/
   function getSelectLinkedItem() {
      return '';
   }


   /**
    * Return a field Value if exists
    *
    * @param $field field name
    *
    * @return value of the field / false if not exists
   **/
   function getField($field) {

      if (array_key_exists($field,$this->fields)) {
         return $this->fields[$field];
      }
      return NOT_AVAILABLE;
   }


   /**
    * Determine if a field exists
    *
    * @param $field field name
    *
    * @return boolean
   **/
   function isField($field) {

      if (!isset($this->fields['id'])) {
         $this->getEmpty();
      }
       return array_key_exists($field, $this->fields);
   }


   /**
    * Get comments of the Object
    *
    * @return String: comments of the object in the current language (HTML)
   **/
   function getComments() {
      global $LANG,$CFG_GLPI;

      $comment = "";
      if ($this->isField('completename')) {
         $comment .= "<span class='b'>".$LANG['common'][51]."&nbsp;: </span>".
                      $this->getField('completename')."<br>";
      }

      if ($this->isField('serial')) {
         $comment .= "<span class='b'>".$LANG['common'][19]."&nbsp;: </span>".
                     $this->getField('serial')."<br>";
      }

      if ($this->isField('otherserial')) {
         $comment .= "<span class='b'>".$LANG['common'][20]."&nbsp;: </span>".
                     $this->getField('otherserial')."<br>";
      }

      if ($this->isField('states_id') && $this->getType()!='State') {
         $tmp = Dropdown::getDropdownName('glpi_states', $this->getField('states_id'));
         if (strlen($tmp)!=0 && $tmp!='&nbsp;') {
            $comment .= "<span class='b'>".$LANG['state'][0]."&nbsp;: </span>$tmp<br>";
         }
      }

      if ($this->isField('locations_id') && $this->getType()!='Location') {
         $tmp = Dropdown::getDropdownName("glpi_locations", $this->getField('locations_id'));
         if (strlen($tmp)!=0 && $tmp!='&nbsp;') {
            $comment .= "<span class='b'>".$LANG['common'][15]."&nbsp;: "."</span>".$tmp."<br>";
         }
      }

      if ($this->isField('users_id')) {
         $tmp = getUserName($this->getField('users_id'));
         if (strlen($tmp)!=0 && $tmp!='&nbsp;') {
            $comment .= "<span class='b'>".$LANG['common'][34]."&nbsp;: "."</span>".$tmp."<br>";
         }
      }

      if ($this->isField('groups_id') && $this->getType()!='Group') {
         $tmp = Dropdown::getDropdownName("glpi_groups",$this->getField('groups_id'));
         if (strlen($tmp)!=0 && $tmp!='&nbsp;') {
            $comment .= "<span class='b'>".$LANG['common'][35]."&nbsp;: "."</span>".$tmp."<br>";
         }
      }

      if ($this->isField('users_id_tech')) {
         $tmp = getUserName($this->getField('users_id_tech'));
         if (strlen($tmp)!=0 && $tmp!='&nbsp;') {
            $comment .= "<span class='b'>".$LANG['common'][10]."&nbsp;: "."</span>".$tmp."<br>";
         }
      }

      if ($this->isField('contact')) {
         $comment .= "<span class='b'>".$LANG['common'][18]."&nbsp;: </span>".
                      $this->getField('contact')."<br>";
      }

      if ($this->isField('contact_num')) {
         $comment .= "<span class='b'>".$LANG['common'][21]."&nbsp;: </span>".
                     $this->getField('contact_num')."<br>";
      }
      if (($this instanceof CommonDropdown) && $this->isField('comment')) {
         $comment .= "<span class='b'>".$LANG['common'][25]."&nbsp;: </span>".
                      nl2br($this->getField('comment'))."<br>";
      }

      if (!empty($comment)) {
         return Html::showToolTip($comment, array('display' => false));
      }

      return $comment;
   }


   /**
    * Get The Name of the Object
    *
    * @param $with_comment add comments to name
    *
    * @return String: name of the object in the current language
   **/
   function getName($with_comment=0) {

      $toadd = "";
      if ($with_comment) {
         $toadd = "&nbsp;".$this->getComments();
      }

      if (isset($this->fields["name"]) && strlen($this->fields["name"])!=0) {
         return $this->fields["name"].$toadd;
      }
      return NOT_AVAILABLE;
   }


   /**
    * Get The Name of the Object with the ID if the config is set
    * Should Not be overloaded (overload getName() instead)
    *
    * @param $with_comment add comments to name
    * @param $forceid boolean override config and display item's ID
    *
    * @return String: name of the object in the current language
   **/
   function getNameID($with_comment=0, $forceid=false) {
      global $CFG_GLPI;

      $toadd = "";
      if ($with_comment) {
         $toadd = "&nbsp;".$this->getComments();
      }

      if ($forceid || $_SESSION['glpiis_ids_visible']) {
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
   **/
   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_link'] = $this->getType();
      $tab[1]['massiveaction'] = false;

      return $tab;
   }


   /**
    * Print out an HTML "<select>" for a dropdown
    *
    * This should be overloaded in Class
    * Parameters which could be used in options array :
    *    - name : string / name of the select (default is depending itemtype)
    *    - value : integer / preselected value (default 0)
    *    - comments : boolean / is the comments displayed near the dropdown (default true)
    *    - entity : integer or array / restrict to a defined entity or array of entities
    *                   (default -1 : no restriction)
    *    - toupdate : array / Update a specific item on select change on dropdown
    *                   (need value_fieldname, to_update, url (see Ajax::updateItemOnSelectEvent for informations)
    *                   and may have moreparams)
    *    - used : array / Already used items ID: not to display in dropdown (default empty)
    *
    * @param $options possible options
    * @return nothing display the dropdown
   **/
   static function dropdown($options=array()) {
      echo "This function cannot be used for the moment. Use Dropdown::show instead.";
      /*
      Dropdown::show(get_called_class(),$options);
      */
   }


   /**
    * Return a search option by looking for a value of a specific field and maybe a specific table
    *
    * @param field the field in which looking for the value (for example : table, name, etc)
    * @param value the value to look for in the field
    * @param table the table
    *
    * @return then search option array, or an empty array if not found
   **/
   function getSearchOptionByField($field, $value, $table='') {

      foreach (Search::getOptions(get_class($this)) as $id => $searchOption) {
         if ((isset($searchOption['linkfield']) && $searchOption['linkfield'] == $value)
             || (isset($searchOption[$field]) && $searchOption[$field] == $value)) {
            if (($table == '') || ($table != '' && $searchOption['table'] == $table)) {
               // Set ID ;
               $searchOption['id'] = $id;
               return $searchOption;
            }
         }
      }
      return array();
   }

   /**
    * Return a search option ID by looking for a value of a specific field and maybe a specific table
    *
    * @since version 0.83
    *
    * @param field the field in which looking for the value (for example : table, name, etc)
    * @param value the value to look for in the field
    * @param table the table
    *
    * @return then search option id, or -1 if not found
   **/
   function getSearchOptionIDByField($field, $value, $table='') {

      $tab = $this->getSearchOptionByField($field, $value, $table);
      if (isset($tab['id'])) {
         return $tab['id'];
      }
      return -1;
   }


   /**
    * Check float and decimal values
    *
    * @param display or not messages in and addAfterRedirect
    *
    * @return input the data checked
   **/
   function filterValues($display=true) {
      global $LANG;

      if (in_array('CommonDBRelation',class_parents($this))) {
         return true;
      }
      //Type mismatched fields
      $fails = array();
      if (isset($this->input) && is_array($this->input) && count($this->input)) {

         foreach ($this->input as $key => $value) {
            $unset        = false;
            $regs         = array();
            $searchOption = $this->getSearchOptionByField('field', $key);

            if (isset($searchOption['datatype'])
                && (is_null($value) || $value == '' ||  $value == 'NULL')) {

               switch ($searchOption['datatype']) {
                  case 'date' :
                  case 'datetime' :
                     // don't use $unset', because this is not a failure
                     $this->input[$key] = 'NULL';
                     break;
               }
            } else if (isset($searchOption['datatype'])
                && !is_null($value)
                && $value != ''
                && $value != 'NULL') {

               switch ($searchOption['datatype']) {
                  case 'integer' :
                  case 'number' :
                  case 'decimal' :
                     $value = str_replace(',','.',$value);
                     if ($searchOption['datatype'] == 'decimal') {
                        $this->input[$key] = floatval($value);
                     } else {
                        $this->input[$key] = intval($value);
                     }
                     if (!is_numeric($value)) {
                        $unset = true;
                     }
                     break;

                  case 'bool' :
                     if (!in_array($value,array(0,1))) {
                        $unset = true;
                     }
                     break;

                  case 'ip' :
                     $pattern  = "\b(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.";
                     $pattern .= "(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.";
                     $pattern .= "(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.";
                     $pattern .= "(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b";
                     if (!preg_match("/$pattern/", $value, $regs)) {
                        $unset = true;
                     }
                     break;

                  case 'mac' :
                     preg_match("/([0-9a-fA-F]{1,2}([:-]|$)){6}$/",$value,$regs);
                     if (empty($regs)) {
                        $unset = true;
                     }
                     break;

                  case 'date' :
                  case 'datetime' :
                     // Date is already "reformat" according to getDateFormat()
                     $pattern  = "/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})";
                     $pattern .= "([_][01][0-9]|2[0-3]:[0-5][0-9]:[0-5]?[0-9])?/";
                     preg_match($pattern, $value, $regs);
                     if (empty($regs)) {
                        $unset = true;
                     }
                     break;

                  case 'itemtype' :
                     //Want to insert an itemtype, but the associated class doesn't exists
                     if (!class_exists($value)) {
                        $unset = true;
                     }

                  case 'email' :
                  case 'string' :
                     if (strlen($value)>255) {
                        $this->input[$key] = substr($value, 0, 254);
                     }
                     break;

                  default :
                     //Plugins can implement their own checks
                     if (!$this->checkSpecificValues($searchOption['datatype'],$value)) {
                        $unset = true;
                     }
                     // Copy value if check have update it
                     $this->input[$key] = $value;
                     break;
                }
            }

            if ($unset) {
               $fails[] = $searchOption['name'];
               unset($this->input[$key]);
            }
         }
      }
      if ($display && count($fails)) {
         //Display a message to indicate that one or more value where filtered
         $message = $LANG['common'][106].' : '.implode(',',$fails);
         Session::addMessageAfterRedirect($message, INFO, true);
      }
   }


   /**
    * Add more check for values
    *
    * @param datatype datatype of the value
    * @param value value to check (pass by reference)
    *
    * @return true if value is ok, false if not
   **/
   function checkSpecificValues($datatype, &$value) {
      return true;
   }


   /**
    * Get fields to display in the unicity error message
    *
    * @return an aray which contains field => label
   **/
   function getUnicityFieldsToDisplayInErrorMessage() {
      global $LANG;

      return array('id'          => $LANG['common'][2],
                   'serial'      => $LANG['common'][19],
                   'entities_id' => $LANG['entity'][0]);
   }


   function getUnallowedFieldsForUnicity() {
      return array('alert', 'comment', 'date_mod', 'id', 'is_recursive', 'items_id', 'notepad');
   }

   /**
    * Build an unicity error message
    *
    * @param $message the string to be display on the screen, or to be sent in a notification
    * @param $unicity the unicity criterion that failed to match
    * @param $doubles the items that are already present in DB
   **/
   function getUnicityErrorMessage($message, $unicity, $doubles) {
      global $LANG;

      if ($unicity['action_refuse']) {
         $message_text = $LANG['setup'][813];
      } else {
         $message_text = $LANG['setup'][823];
      }
      $message_text .= " ".implode('&nbsp;&&nbsp;',$message);
      $message_text .= $LANG['setup'][818];

      foreach ($doubles as $double) {
         $doubles_text = array();

         if (in_array('CommonDBChild',class_parents($this))) {
            if ($this->getField($this->itemtype)) {
               $item = new $double['itemtype']();
            } else {
               $item = new $this->itemtype();
            }

            $item->getFromDB($double['items_id']);
         } else {
            $item = new CommonDBTM();
            $item->fields = $double;
         }

         foreach ($this->getUnicityFieldsToDisplayInErrorMessage() as $key => $value) {
            $field_value = $item->getField($key);
            if ($field_value != NOT_AVAILABLE) {
               if (getTableNameForForeignKeyField($key) != '') {
                  $field_value = Dropdown::getDropdownName(getTableNameForForeignKeyField($key),
                                                           $field_value);
               }
               $doubles_text[] =  $value.": ".$field_value;
            }
         }
         // Add information on item in trash
         if ($item->isField('is_deleted') && $item->getField('is_deleted')) {
            $doubles_text[] = $LANG['setup'][820];
         }

         $message_text .= "<br>[".implode(', ',$doubles_text)."]";
      }
      return $message_text;
   }


   /**
    * Check field unicity before insert or update
    *
    * @param add true for insert, false for update
    * @param $options array
    *
    * @return true if item can be written in DB, false if not
   **/
   function checkUnicity($add=false, $options=array()) {
      global $LANG, $DB, $CFG_GLPI;

      $p['unicity_error_message']  = true;
      $p['add_event_on_duplicate'] = true;
      $p['disable_unicity_check']  = false;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $value) {
            $p[$key] = $value;
         }
      }

      // Do not check for template
      if (isset($this->input['is_template']) && $this->input['is_template']) {
         return true;
      }

      $result = true;

      //Do not check unicity when creating infocoms or if checking is expliclty disabled
      if ($p['disable_unicity_check']) {
         return $result;
      }

      //Get all checks for this itemtype and this entity
      if (in_array(get_class($this), $CFG_GLPI["unicity_types"])) {
         // Get input entities if set / else get object one
         if (isset($this->input['entities_id'])) {
            $entities_id = $this->input['entities_id'];
         } else {
            $entities_id = $this->fields['entities_id'];
         }

         $all_fields =  FieldUnicity::getUnicityFieldsConfig(get_class($this), $entities_id);
         foreach ($all_fields  as $key => $fields) {

            //If there's fields to check
            if (!empty($fields) && !empty($fields['fields'])) {
               $where    = "";
               $continue = true;
               foreach (explode(',',$fields['fields']) as $field) {
                  if (isset($this->input[$field]) //Field is set
                      //Standard field not null
                      && ((getTableNameForForeignKeyField($field) == '' && $this->input[$field] != '')
                          //Foreign key and value is not 0
                          || (getTableNameForForeignKeyField($field) != ''
                              && $this->input[$field] > 0))
                      && !Fieldblacklist::isFieldBlacklisted(get_class($this), $entities_id, $field,
                                                             $this->input[$field])) {
                     $where .= " AND `".$this->getTable()."`.`$field` = '".$this->input[$field]."'";
                  } else {
                     $continue = false;
                  }
               }

               if ($continue && $where != '') {
                  $entities = $fields['entities_id'];
                  if ($fields['is_recursive']) {
                     $entities = getSonsOf('glpi_entities', $fields['entities_id']);
                  }
                  $where_global = getEntitiesRestrictRequest(" AND", $this->getTable(), '',
                                                             $entities);
                  $tmp = clone $this;
                  if ($tmp->maybeTemplate()) {
                     $where_global .= " AND NOT `is_template`";
                  }

                  //If update, exclude ID of the current object
                  if (!$add) {
                     $where .= " AND `".$this->getTable()."`.`id` NOT IN (".$this->input['id'].") ";
                  }

                  if (countElementsInTable($this->table,"1 $where $where_global") > 0) {
                     if ($p['unicity_error_message'] || $p['add_event_on_duplicate']) {
                        $message = array();
                        foreach (explode(',',$fields['fields']) as $field) {
                           $table = getTableNameForForeignKeyField($field);
                           if ($table != '') {
                              $searchOption = $this->getSearchOptionByField('field', 'name',
                                                                            $table);
                           } else {
                              $searchOption = $this->getSearchOptionByField('field', $field);
                           }
                           $message[] = $searchOption['name'].'='.$this->input[$field];
                        }

                        $doubles      = getAllDatasFromTable($this->table, "1 $where $where_global");
                        $message_text = $this->getUnicityErrorMessage($message, $fields, $doubles);
                        if ($p['unicity_error_message']) {
                           if (!$fields['action_refuse']) {
                           $show_other_messages = ($fields['action_refuse']?true:false);
                           } else {
                              $show_other_messages = true;
                           }
                           Session::addMessageAfterRedirect($message_text, true,
                                                            $show_other_messages,
                                                            $show_other_messages);
                        }
                        if ($p['add_event_on_duplicate']) {
                           Event::log ((!$add?$this->fields['id']:0), get_class($this), 4,
                                       'inventory',
                                       $_SESSION["glpiname"]." ".$LANG['log'][123].' : '.
                                          $message_text);
                        }
                     }
                     if($fields['action_refuse']) {
                        $result = false;
                     }
                     if($fields['action_notify']) {
                        $params = array('message'     => Html::clean($message_text),
                                        'action_type' => $add,
                                        'action_user' => getUserName(Session::getLoginUserID()),
                                        'entities_id' => $entities_id,
                                        'itemtype'    => get_class($this),
                                        'date'        => $_SESSION['glpi_currenttime'],
                                        'refuse'      => $fields['action_refuse']);
                        NotificationEvent::raiseEvent('refuse', new FieldUnicity(), $params);
                     }
                  }
               }
            }
         }

      }

      return $result;
   }


   /**
    * Clean all infos which match some criteria
    *
    * @param $crit array of criteria (ex array('is_active'=>'1'))
    * @param $force boolean force purge not on put in trash
   **/
   function deleteByCriteria($crit=array(), $force=0) {
      global $DB;

      if (is_array($crit) && count($crit)>0) {
         $crit['FIELDS'] = 'id';

         foreach ($DB->request($this->getTable(), $crit) as $row) {
            $this->delete($row, $force);
         }

      }
   }


   /**
    *  show notes for item
    *
    * @return nothing
   **/
   function showNotesForm() {
      global $LANG;

      if (!Session::haveRight("notes","r")) {
         return false;
      }

      if (!$this->isField('notepad') || !isset($this->fields[$this->getIndexName()])) {
         return false;
      }

      //getFromDB
      $canedit = (Session::haveRight("notes", "w")
                  && (!$this->isEntityAssign()
                      || Session::haveAccessToEntity($this->getEntityID())));
      $target = $this->getFormURL();

      if ($canedit) {
         echo "<form name='form' method='post' action='".$target."'>";
      }

      echo "<div class='center'>";
      echo "<table class='tab_cadre_fixe' >";
      echo "<tr><th>".$LANG['title'][37]."</th></tr>";

      echo "<tr><td class='tab_bg_1 center middle'>";
      echo "<textarea class='textarea_notes' cols='100' rows='35' name='notepad'>".
            $this->getField('notepad')."</textarea></td></tr>";

      echo "<tr><td class='tab_bg_2 center'>";
      echo "<input type='hidden' name='id' value='".$this->fields['id']."'>";
      // for all objects without id as primary key (like entitydata)
      if ($this->getIndexName() != 'id') {
         echo "<input type='hidden' name='".$this->getIndexName()."' value='".
                $this->fields[$this->getIndexName()]."'>";
      }

      if ($canedit) {
         echo "<input type='submit' name='update' value=\"".$LANG['buttons'][7]."\" class='submit'>";
      }
      echo "</td></tr>";
      echo "</table></div>";

      if ($canedit) {
         Html::closeForm();
      }
   }


   /**
    * get the Entity of an Item
    *
    * @param $itemtype string item type
    * @param $items_id integer id of the item
    *
    * @return integer ID of the entity or -1
   **/
   static function getItemEntity($itemtype, $items_id) {

      if ($itemtype && ($item = getItemForItemtype($itemtype))) {

         if ($item->getFromDB($items_id)) {
            return $item->getEntityID();
         }

      }
      return -1;
   }


   /**
    * display a specific field value
    *
    * @since version 0.83
    *
    * @param $field     String name of the field
    * @param $values    Array with the value to display or a Single value
    * @param $options   Array of option
    *
    * @return return the string to display
   **/
   static function getSpecificValueToDisplay($field, $values, $options=array()) {
      return '';
   }


   /**
    * display a field using standard system
    *
    * @since version 0.83
    *
    * @param $field_id_or_search_options integer/string/array id of the search option field
    *                                                            or field name
    *                                                            or search option array
    * @param $values mixed value to display
    * @param $options array options array
    * Parameters which could be used in options array :
    *    - comments : boolean / is the comments displayed near the value (default false)
    *    - any others options passed to specific display method
    *
    * @return return the string to display
   **/
   function getValueToDisplay($field_id_or_search_options, $values, $options=array()) {
      global $LANG, $CFG_GLPI;

      $param['comments'] = false;
      $param['html']     = false;
      foreach ($param as $key => $val) {
         if (!isset($options[$key])) {
            $options[$key] = $val;
         }
      }

      $searchoptions = array();
      if (is_array($field_id_or_search_options)) {
         $searchoptions = $field_id_or_search_options;
      } else {
         $searchopt = Search::getOptions($this->getType());

         // Get if id of search option is passed
         if (is_numeric($field_id_or_search_options)) {
            if (isset($searchopt[$field_id_or_search_options])) {
               $searchoptions = $searchopt[$field_id_or_search_options];
            }
         } else { // Get if field name is passed
            $searchoptions = $this->getSearchOptionByField('field', $field_id_or_search_options,
                                                           $this->getTable());
         }
      }

      if (count($searchoptions)) {
         $field = $searchoptions['field'];

         // Normalize option
         if (is_array($values)) {
            $value = $values[$field];
         } else {
            $value = $values;
            $values = array($field => $value);
         }

         if (isset($searchoptions['datatype'])) {
            $unit = '';
            if (isset($searchoptions['unit'])) {
               $unit = $searchoptions['unit'];
            }

            switch ($searchoptions['datatype']) {
               case "number" :
                  if ($options['html']) {
                     return Html::formatNumber($value, false, 0). $unit;
                  }
                  return $value;

               case "decimal" :
                  if ($options['html']) {
                     return Html::formatNumber($value).$unit;
                  }
                  return $value;

               case "string" :
                  return $value;

               case "text" :
                  if ($options['html']) {
                     $text = nl2br($value);
                  } else {
                     $text = $value;
                  }
                  if (isset($searchoptions['htmltext']) && $searchoptions['htmltext']) {
                     $text = Html::clean(Toolbox::unclean_cross_side_scripting_deep($text));
                  }
                  return $text;

               case "bool" :
                  return Dropdown::getYesNo($value);

               case "date" :
               case "date_delay" :
                  if (isset($options['relative_dates']) && $options['relative_dates']) {
                     $dates = Html::getGenericDateTimeSearchItems(array('with_time'   => true,
                                                                        'with_future' => true));
                     return $dates[$value];
                  }
                  return Html::convDate(Html::computeGenericDateTimeSearch($value, true));

               case "datetime" :
                  if (isset($options['relative_dates']) && $options['relative_dates']) {
                     $dates = Html::getGenericDateTimeSearchItems(array('with_time'   => true,
                                                                        'with_future' => true));
                     return $dates[$value];
                  }
                  return Html::convDateTime(Html::computeGenericDateTimeSearch($value,false));

               case "timestamp" :
                  $withseconds = false;
                  if (isset($searchoptions['withseconds'])) {
                     $withseconds = $searchoptions['withseconds'];
                  }
                  return Html::timestampToString($value,$withseconds);

               case "email" :
                  if ($options['html']) {
                     return "<a href='mailto:$value'>$value</a>";
                  }
                  return $value;

            case "weblink" :
               $orig_link = trim($value);
               if (!empty($orig_link)) {
                  // strip begin of link
                  $link = preg_replace('/https?:\/\/(www[^\.]*\.)?/','',$orig_link);
                  $link = preg_replace('/\/$/', '', $link);
                  if (Toolbox::strlen($link)>$CFG_GLPI["url_maxlength"]) {
                     $link = Toolbox::substr($link, 0, $CFG_GLPI["url_maxlength"])."...";
                  }
                  return "<a href=\"".formatOutputWebLink($orig_link)."\" target='_blank'>$link</a>";
               }
               return "&nbsp;";

               case "dropdown" :
                  if ($searchoptions['table'] == 'glpi_users') {
                     if ($param['comments']) {
                        $tmp = getUserName($value,2);
                        return $tmp['name'].'&nbsp;'.Html::showToolTip($tmp['comment'],
                                                                       array('display' => false));
                     }
                     return getUserName($value);
                  }
                  if ($param['comments']) {
                     $tmp = Dropdown::getDropdownName($searchoptions['table'],$value,1);
                     return $tmp['name'].'&nbsp;'.Html::showToolTip($tmp['comment'],
                                                                    array('display' => false));
                  }
                  return Dropdown::getDropdownName($searchoptions['table'], $value);

               case "right" :
                  return Profile::getRightValue($value);

               case "itemtypename" :
                  if ($obj = getItemForItemtype($value)) {
                     return $obj->getTypeName();
                  }
                  break;

               case "language":
                  if (isset($CFG_GLPI['languages'][$value])) {
                     return $CFG_GLPI['languages'][$value][0];
                  }
                  return $LANG['setup'][46];

            }
         }
         // Get specific display if available
         $itemtype = getItemTypeForTable($searchoptions['table']);
         $item     = new $itemtype();
         $specific = $item->getSpecificValueToDisplay($field, $values, $options);
         if (!empty($specific)) {
            return $specific;
         }

      }
      return $value;
   }


   static function listTemplates($itemtype, $target, $add = 0) {
      global $DB, $CFG_GLPI, $LANG;

      if (!($item = getItemForItemtype($itemtype))) {
         return false;
      }

      if (!$item->maybeTemplate()) {
         return false;
      }

      //Check is user have minimum right r
      if (!$item->canView() && !$item->canCreate()) {
         return false;
      }

      $query = "SELECT * FROM `".$item->getTable()."`
                WHERE `is_template` = '1' ";
      if ($item->isEntityAssign()) {
         $query .= getEntitiesRestrictRequest('AND', $item->getTable(), 'entities_id',
                  $_SESSION['glpiactive_entity'], $item->maybeRecursive());
      }
      $query .= " ORDER by `template_name`";

      if ($result = $DB->query($query)) {
         echo "<div class='center'><table class='tab_cadre' width='50%'>";
         if ($add) {
            echo "<tr><th>" . $LANG['common'][7] . " - ".$item->getTypeName()." :</th></tr>";
            echo "<tr><td class='tab_bg_1 center'>";
            echo "<a href=\"$target?id=-1&amp;withtemplate=2\">&nbsp;&nbsp;&nbsp;" .
                   $LANG['common'][31] . "&nbsp;&nbsp;&nbsp;</a></td>";
            echo "</tr>";
         } else {
            echo "<tr><th colspan='2'>" . $LANG['common'][14] . " - ".$item->getTypeName()." : ".
                 "</th></tr>";
         }

         while ($data = $DB->fetch_array($result)) {
            $templname = $data["template_name"];
            if ($_SESSION["glpiis_ids_visible"] || empty($data["template_name"])) {
               $templname.= "(".$data["id"].")";
            }
            echo "<tr><td class='tab_bg_1 center'>";
            if ($item->canCreate() && !$add) {
               echo "<a href=\"$target?id=" . $data["id"] . "&amp;withtemplate=1\">";
               echo "&nbsp;&nbsp;&nbsp;$templname&nbsp;&nbsp;&nbsp;</a></td>";
               echo "<td class='tab_bg_2 center b'>";
               echo "<a href=\"$target?id=" . $data["id"]."&amp;purge=purge&amp;withtemplate=1\">".
                      $LANG['buttons'][6] . "</a></td>";
            } else {
               echo "<a href=\"$target?id=" . $data["id"] . "&amp;withtemplate=2\">";
               echo "&nbsp;&nbsp;&nbsp;$templname&nbsp;&nbsp;&nbsp;</a></td>";
            }
            echo "</tr>";
         }

         if ($item->canCreate() && !$add) {
            echo "<tr><td colspan='2' class='tab_bg_2 center b'>";
            echo "<a href=\"$target?withtemplate=1\">" . $LANG['common'][9] . "</a>";
            echo "</td></tr>";
         }
         echo "</table></div>\n";
      }
   }

   /**
    * Specificy a plugin itemtype for which entities_id and is_recursive should be forwarded
    *
    * @param $for_itemtype change of entity for this itemtype will be forwarder
    * @param $to_itemtype change of entity will affect this itemtype
    *
    * @return nothing
    * @since 0.83
   **/
   static function addForwardEntity($for_itemtype, $to_itemtype) {
      self::$plugins_forward_entity[$for_itemtype][] = $to_itemtype;
   }

}
?>