<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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


/** @file
* @brief
*/

/**
*  Common DataBase Table Manager Class - Persistent Object
**/
class CommonDBTM extends CommonGLPI {

   /// Data of the Item
   var $fields                              = array();
   /// Make an history of the changes
   var $dohistory                           = false;
   /// Black list fields for history log or date mod update
   var $history_blacklist                   = array();
   /// Set false to desactivate automatic message on action
   var $auto_message_on_action              = true;

   /// Set true to desactivate link generation because form page do not permit show/edit item
   var $no_form_page                        = false;

   /// Set true to desactivate auto compute table name
   static protected $notable                = false;

   ///Additional fiedls for dictionnary processing
   var $additional_fields_for_dictionnary   = array();

   /// Forward entity datas to linked items
   static protected $forward_entity_to      = array();
   /// Foreign key field cache : set dynamically calling getForeignKeyField
   protected $fkfield                       = "";

   /// Search options of the item : to avoid multiple load
   protected $searchopt                     = false;

   /// Tab orientation : horizontal or vertical
   public $taborientation                   = 'vertical';
   /// Need to get item to show tab
   public $get_item_to_display_tab          = true;

   /// Forward entity to plugins itemtypes
   static protected $plugins_forward_entity = array();

   /// Profile name
   static $rightname                        = '';

   /// Is this item use notepad ?
   protected $usenotepad                    = false;

   /// FLush mail queue for
   public $mailqueueonaction = false;

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
   static function getTable() {
      if (static::$notable) {
         return '';
      }

      if (empty($_SESSION['glpi_table_of'][get_called_class()])) {
         $_SESSION['glpi_table_of'][get_called_class()] = getTableForItemType(get_called_class());
      }

      return $_SESSION['glpi_table_of'][get_called_class()];
   }


   /**
    * force table value (used for config management for old versions)
    *
    * @param $table name of the table to be forced
    *
    * @return nothing
   **/
   static function forceTable($table) {
      $_SESSION['glpi_table_of'][get_called_class()] = $table;
   }


   static function getForeignKeyField() {

      if (empty($_SESSION['glpi_foreign_key_field_of'][get_called_class()])) {
         $_SESSION['glpi_foreign_key_field_of'][get_called_class()]
            = getForeignKeyFieldForTable(static::getTable());
      }

      return $_SESSION['glpi_foreign_key_field_of'][get_called_class()];
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
      if (strlen($ID) == 0) {
         return false;
      }

      return $this->getFromDBByQuery("WHERE `" . $this->getTable() . "`.`" . $this->getIndexName() .
                                     "` = '" . Toolbox::cleanInteger($ID) . "' LIMIT 1");
   }

   /**
    * Retrieve an item from the database by query. The query must include the WHERE keyword. Thus,
    * we can replace "WHERE" to make complex SQL JOINED queries (for instance, see
    * User::getFromDBbyEmail()).
    *
    * @since version 0.84
    *
    * @param $query the "WHERE" or "JOIN" part of the SQL query
    *
    * @return true if succeed else false
   **/
   function getFromDBByQuery($query) {
      global $DB;

      // Make new database object and fill variables

      if (empty($query)) {
         return false;
      }

      $query = "SELECT `".$this->getTable()."`.*
                FROM `".$this->getTable()."`
                $query";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) == 1) {
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

      if (isset($this->fields[static::getIndexName()])) {
         return $this->fields[static::getIndexName()];
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
    * @param $condition    condition used to search if needed (empty get all) (default '')
    * @param $order        order field if needed (default '')
    * @param $limit        limit retrieved datas if needed (default '')
    *
    * @return all retrieved data in a associative array by id
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
   static function getIndexName() {
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

      if (!empty($table) &&
          ($fields = $DB->list_fields($table))) {

         foreach ($fields as $key => $val) {
            $this->fields[$key] = "";
         }
      } else {
         return false;
      }

      if (array_key_exists('entities_id',$this->fields)
          && isset($_SESSION["glpiactive_entity"])) {
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
    * @param $updates      fields to update
    * @param $oldvalues    array of old values of the updated fields
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

      if (count($oldvalues) && isset($_SESSION['glpiactiveentities_string'])) {
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
      if ($nb_fields > 0) {
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
            if ($i != ($nb_fields-1)) {
               $query .= ",";
            }
         }

         $query .= ") VALUES (";
         for ($i=0 ; $i<$nb_fields ; $i++) {

            if ($values[$i] == 'NULL') {
               $query .= $values[$i];
            } else {
               $query .= "'".$values[$i]."'";
            }

            if ($i != ($nb_fields-1)) {
               $query .= ",";
            }

         }
         $query .= ")";

         if ($result=$DB->query($query)) {
            // Already define for entity / insert_id does not work
            if (!isset($this->fields['id'])
                || is_null($this->fields['id'])
                || ($this->fields['id'] == 0)) {
               $this->fields['id'] = $DB->insert_id();
            }
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
    *               (default 0)
    *
    * @return true if succeed else false
   **/
   function deleteFromDB($force=0) {
      global $DB, $CFG_GLPI;

      if (($force == 1)
          || !$this->maybeDeleted()
          || ($this->useDeletedToLockIfDynamic()
              && !$this->isDynamic())) {
         $this->cleanDBonPurge();
         if ($this instanceof CommonDropdown) {
            $this->cleanTranslations();
         }
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
            if ($tablename[0] != '_') {

               $itemtype = getItemTypeForTable($tablename);

               // Code factorization : we transform the singleton to an array
               if (!is_array($field)) {
                  $field = array($field);
               }

               foreach ($field as $f) {
                  foreach ($DB->request($tablename, array($f => $this->getID())) as $data) {
                     // Be carefull : we must use getIndexName because self::update rely on that !
                     if ($object = getItemForItemtype($itemtype)) {
                        $idName = $object->getIndexName();
                        // And we must ensure that the index name is not the same as the field
                        // we try to modify. Otherwise we will loose this element because all
                        // will be set to $newval ...
                        if ($idName != $f) {
                           $object->update(array($idName          => $data[$idName],
                                                 $f               => $newval,
                                                 '_disablenotif'  => true)); // Disable notifs
                        }
                     }
                  }
               }

            }
         }

      }

      // Clean ticket open against the item
      if (in_array($this->getType(),$CFG_GLPI["ticket_types"])) {
         $job         = new Ticket();
         $itemsticket = new Item_Ticket();

         $query = "SELECT *
                   FROM `glpi_items_tickets`
                   WHERE `items_id` = '".$this->fields['id']."'
                         AND `itemtype`='".$this->getType()."'";
         $result = $DB->query($query);

         if ($DB->numrows($result)) {
            while ($data=$DB->fetch_assoc($result)) {
               $cnt = countElementsInTable('glpi_items_tickets', "`tickets_id`='".$data['tickets_id']."'");
               $job->getFromDB($data['tickets_id']);
               if ($cnt == 1) {
                  if ($CFG_GLPI["keep_tickets_on_delete"] == 1) {
                     $itemsticket->delete(array("id" => $data["id"]));
                  } else {
                     $job->delete(array("id" => $data["tickets_id"]));
                  }
               } else {
                  $itemsticket->delete(array("id" => $data["id"]));
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
    * Clean translations associated to a dropdown
    *
    * @since version 0.85
   **/
   function cleanTranslations() {

      //Do not try to clean is dropdown translation is globally off
      if (DropdownTranslation::isDropdownTranslationActive()) {
         $translation = new DropdownTranslation();
         $translation->deleteByCriteria(array('itemtype' => get_class($this),
                                              'items_id' => $this->getID()));
      }
   }


   /**
    * Clean the date in the relation tables for the deleted item
    * Clear N/N Relation
   **/
   function cleanRelationTable() {
      global $CFG_GLPI, $DB;

      // If this type have INFOCOM, clean one associated to purged item
      if (Infocom::canApplyOn($this)) {
         $infocom = new Infocom();

         if ($infocom->getFromDBforDevice($this->getType(), $this->fields['id'])) {
             $infocom->delete(array('id' => $infocom->fields['id']));
         }
      }

      // If this type have NETPORT, clean one associated to purged item
      if (in_array($this->getType(),$CFG_GLPI['networkport_types'])) {
         // If we don't use delete, then cleanDBonPurge() is not call and the NetworkPorts are not
         // clean properly
         $networkPortObject = new NetworkPort();
         $networkPortObject->cleanDBonItemDelete($this->getType(), $this->getID());
         // Manage networkportmigration if exists
         if (TableExists('glpi_networkportmigrations')) {
            $networkPortMigObject = new NetworkPortMigration();
            $networkPortMigObject->cleanDBonItemDelete($this->getType(), $this->getID());
         }
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
      if (Document::canApplyOn($this)) {
         $di = new Document_Item();
         $di->cleanDBonItemDelete($this->getType(), $this->fields['id']);
      }

      // If this type have NOTEPAD, clean one associated to purged item
      if ($this->usenotepad) {
         $note = new Notepad();
         $note->cleanDBonItemDelete($this->getType(), $this->fields['id']);
      }

   }


   /**
    * Actions done when item flag deleted is set to an item
    *
    * @return nothing
   **/
   function cleanDBonMarkDeleted() {
   }


   /**
    * Save the input data in the Session
    *
    * @since version 0.84
   **/
   protected function saveInput() {
      $_SESSION['saveInput'][$this->getType()] = $this->input;
   }


   /**
    * Clear the saved data stored in the session
    *
    * @since version 0.84
   **/
   protected function clearSavedInput() {
      unset($_SESSION['saveInput'][$this->getType()]);
   }


   /**
    * Get the data saved in the session
    *
    * @since version 0.84
    *
    * @param $default   Array of value used if session is empty
    *
    * @return Array of value
   **/
   protected function restoreInput(Array $default=array()) {

      if (isset($_SESSION['saveInput'][$this->getType()])) {
         $saved = Html::cleanPostForTextArea($_SESSION['saveInput'][$this->getType()]);

         // clear saved data when restored (only need once)
         unset($_SESSION['saveInput'][$this->getType()]);

         return $saved;
      }

      return $default;
   }


   // Common functions
   /**
    * Add an item in the database with all it's items.
    *
    * @param $input     array    the _POST vars returned by the item form when press add
    * @param options    array    with the insert options
    *   - unicity_message : do not display message if item it a duplicate (default is yes)
    * @param $history   boolean  do history log ? (true by default)
    *
    * @return integer the new ID of the added item (or false if fail)
   **/
   function add(array $input, $options=array(), $history=true) {
      global $DB, $CFG_GLPI;

      if ($DB->isSlave()) {
         return false;
      }

      // Store input in the object to be available in all sub-method / hook
      $this->input = $input;

      if (isset($this->input['add'])) {
         // Input from the interface
         // Save this data to be available if add fail
         $this->saveInput();
      }

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
            if (($key[0] != '_')
                && isset($table_fields[$key])) {
               $this->fields[$key] = $this->input[$key];
            }
         }

         // Auto set date_mod if exsist
         if (isset($table_fields['date_mod'])) {
            $this->fields['date_mod'] = $_SESSION["glpi_currenttime"];
         }

         if ($this->checkUnicity(true,$options)) {
            if ($this->addToDB()) {
               $this->post_addItem();
               $this->addMessageOnAddAction();

               if ($this->dohistory && $history) {
                  $changes[0] = 0;
                  $changes[1] = $changes[2] = "";

                  Log::history($this->fields["id"], $this->getType(), $changes, 0,
                               Log::HISTORY_CREATE_ITEM);
               }

                // Auto create infocoms
               if (isset($CFG_GLPI["auto_create_infocoms"]) && $CFG_GLPI["auto_create_infocoms"]
                   && Infocom::canApplyOn($this)) {

                  $ic = new Infocom();
                  if (!$ic->getFromDBforDevice($this->getType(), $this->fields['id'])) {
                     $ic->add(array('itemtype' => $this->getType(),
                                    'items_id' => $this->fields['id']));
                  }
               }

               // If itemtype is in infocomtype and if states_id field is filled
               // and item is not a template
               if (InfoCom::canApplyOn($this)
                   && isset($this->input['states_id'])
                            && (!isset($this->input['is_template'])
                                || !$this->input['is_template'])) {

                  //Check if we have to automatical fill dates
                  Infocom::manageDateOnStatusChange($this);
               }
               Plugin::doHook("item_add", $this);

               // As add have suceed, clean the old input value
               if (isset($this->input['_add'])) {
                  $this->clearSavedInput();
               }
               if ($this->mailqueueonaction) {
                  QueuedMail::forceSendFor($this->getType(), $this->fields['id']);
               }
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
    * @param $options array of options
    *    - comments     : boolean / display comments
    *    - complete     : boolean / display completename instead of name
    *    - additional   : boolean / display additionals information
    *    - linkoption   : string  / additional options to add to <a>
    *
    * @return string : HTML link
   **/
   function getLink($options=array()) {

      $p['linkoption'] = '';

      if (isset($options['linkoption'])) {
         $p['linkoption'] = $options['linkoption'];
      }

      if (!isset($this->fields['id'])) {
         return '';
      }

      if ($this->no_form_page
          || !$this->can($this->fields['id'], READ)) {
         return $this->getNameID($options);
      }

      $link = $this->getLinkURL();

      return "<a ".$p['linkoption']." href='$link'>".$this->getNameID($options)."</a>";
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

      $link  = $this->getFormURLWithID($this->getID());
      $link .= ($this->isTemplate() ? "&amp;withtemplate=1" : "");

      return $link;
   }


   /**
    * Add a message on add action
   **/
   function addMessageOnAddAction() {
      global $CFG_GLPI;

      $addMessAfterRedirect = false;
      if (isset($this->input['_add'])) {
         $addMessAfterRedirect = true;
      }

      if (isset($this->input['_no_message'])
          || !$this->auto_message_on_action) {
         $addMessAfterRedirect = false;
      }

      if ($addMessAfterRedirect) {
         $link = $this->getFormURL();
         if (!isset($link)) {
            return;
         }
         if (($name = $this->getName()) == NOT_AVAILABLE) {
            //TRANS: %1$s is the itemtype, %2$d is the id of the item
            $this->fields['name'] = sprintf(__('%1$s - ID %2$d'),
                                            $this->getTypeName(1), $this->fields['id']);
         }
         $display = (isset($this->input['_no_message_link'])?$this->getNameID()
                                                            :$this->getLink());

         // Do not display quotes
         //TRANS : %s is the description of the added item
         Session::addMessageAfterRedirect(sprintf(__('%1$s: %2$s'), __('Item successfully added'),
                                                  stripslashes($display)));

      }
   }


   /**
    * Add needed information to $input (example entities_id)
    *
    * @param $input datas used to add the item
    *
    * @since version 0.84
    *
    * @return the modified $input array
   **/
   function addNeededInfoToInput($input) {
      return $input;
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
    * @param $input     array    the _POST vars returned by the item form when press update
    * @param $history   boolean  do history log ? (default 1)
    * @param options    array    with the insert options
    *
    * @return boolean : true on success
   **/
   function update(array $input, $history=1, $options=array()) {
      global $DB, $CFG_GLPI;

      if ($DB->isSlave()) {
         return false;
      }

      if (!$this->getFromDB($input[static::getIndexName()])) {
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
      if ($this->checkUnicity(false,$options)) {
         if ($this->input && is_array($this->input)) {
            // Fill the update-array with changes
            $x               = 0;
            $this->updates   = array();
            $this->oldvalues = array();

            foreach ($this->input as $key => $val) {
               if (array_key_exists($key,$this->fields)) {

                  // Prevent history for date statement (for date for example)
                  if (is_null($this->fields[$key])
                      && ($this->input[$key] == 'NULL')) {
                     $this->fields[$key] = 'NULL';
                  }
                  // Compare item
                  $ischanged = true;
                  $searchopt = $this->getSearchOptionByField('field', $key, $this->getTable());
                  if (isset($searchopt['datatype'])) {
                     switch ($searchopt['datatype']) {
                        case 'string' :
                        case 'text' :
                           $ischanged = (strcmp($DB->escape($this->fields[$key]),
                                                $this->input[$key]) != 0);
                           break;

                        case 'itemlink' :
                           if ($key == 'name') {
                              $ischanged = (strcmp($DB->escape($this->fields[$key]),
                                                               $this->input[$key]) != 0);
                              break;
                           } // else default

                        default :
                           $ischanged = ($DB->escape($this->fields[$key]) != $this->input[$key]);
                           break;
                     }
                  } else {
                     // No searchoption case
                     $ischanged = ($DB->escape($this->fields[$key]) != $this->input[$key]);

                  }
                  if ($ischanged) {
                     if ($key != "id") {

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
                           static::$forward_entity_to[] = $itemtype;
                        }
                     }
                     // forward entity information if needed
                     if (count(static::$forward_entity_to)
                         && (in_array("entities_id",$this->updates)
                             || in_array("is_recursive",$this->updates)) ) {
                        $this->forwardEntityInformations();
                     }

                     // If itemtype is in infocomtype and if states_id field is filled
                     // and item not a template
                     if (InfoCom::canApplyOn($this)
                         && in_array('states_id',$this->updates)
                         && ($this->getField('is_template') != NOT_AVAILABLE)) {
                        //Check if we have to automatical fill dates
                        Infocom::manageDateOnStatusChange($this, false);
                     }
                  }
               }
            }
            $this->post_updateItem($history);

            if ($this->mailqueueonaction) {
               QueuedMail::forceSendFor($this->getType(), $this->fields['id']);
            }

            return true;
         }
      }

      return false;
   }


   /**
    * Forward entity information to linked items
   **/
   protected function forwardEntityInformations() {
      global $DB;

      if (!isset($this->fields['id']) || !($this->fields['id'] >= 0)) {
         return false;
      }

      if (count(static::$forward_entity_to)) {
         foreach (static::$forward_entity_to as $type) {
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
               // No history for such update
               $item->update($input, 0);
            }
         }
      }
   }


   /**
    * Add a message on update action
   **/
   function addMessageOnUpdateAction() {

      $addMessAfterRedirect = false;

      if (isset($this->input['_update'])) {
         $addMessAfterRedirect = true;
      }

      if (isset($this->input['_no_message'])
          || !$this->auto_message_on_action) {
         $addMessAfterRedirect = false;
      }

      if ($addMessAfterRedirect) {
         $link = $this->getFormURL();
         if (!isset($link)) {
            return;
         }
         // Do not display quotes
         if (isset($this->fields['name'])) {
            $this->fields['name'] = stripslashes($this->fields['name']);
         } else {
            //TRANS: %1$s is the itemtype, %2$d is the id of the item
            $this->fields['name'] = sprintf(__('%1$s - ID %2$d'),
                                            $this->getTypeName(1), $this->fields['id']);
         }


         if (isset($this->input['_no_message_link'])) {
            $display = $this->getNameID();
         } else {
            $display = $this->getLink();
         }
         //TRANS : %s is the description of the updated item
         Session::addMessageAfterRedirect(sprintf(__('%1$s: %2$s'), __('Item successfully updated'), $display));

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
    * @param $history store changes history ? (default 1)
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
    * @param $input     array    the _POST vars returned by the item form when press delete
    * @param $force     boolean  force deletion (default 0)
    * @param $history   boolean  do history log ? (default 1)
    *
    * @return boolean : true on success
   **/
   function delete(array $input, $force=0, $history=1) {
      global $DB;

      if ($DB->isSlave()) {
         return false;
      }

      if (!$this->getFromDB($input[static::getIndexName()])) {
         return false;
      }

      // Force purge for templates / may not to be deleted / not dynamic lockable items
      if ($this->isTemplate()
          || !$this->maybeDeleted()
          // Do not take into account deleted field if maybe dynamic but not dynamic
          || ($this->useDeletedToLockIfDynamic()
              && !$this->isDynamic())) {
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
                  $logaction  = Log::HISTORY_DELETE_ITEM;
                  if ($this->useDeletedToLockIfDynamic()
                      && $this->isDynamic()) {
                     $logaction = Log::HISTORY_LOCK_ITEM;
                  }

                  Log::history($this->fields["id"], $this->getType(), $changes, 0,
                               $logaction);
               }
               $this->post_deleteItem();

               Plugin::doHook("item_delete",$this);
            }
            if ($this->mailqueueonaction) {
               QueuedMail::forceSendFor($this->getType(), $this->fields['id']);
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

      if (!$this->maybeDeleted()) {
         return;
      }

      $addMessAfterRedirect = false;
      if (isset($this->input['_delete'])) {
         $addMessAfterRedirect = true;
      }

      if (isset($this->input['_no_message'])
          || !$this->auto_message_on_action) {
         $addMessAfterRedirect = false;
      }

      if ($addMessAfterRedirect) {
         $link = $this->getFormURL();
         if (!isset($link)) {
            return;
         }
         if (isset($this->input['_no_message_link'])) {
            $display = $this->getNameID();
         } else {
            $display = $this->getLink();
         }
         //TRANS : %s is the description of the updated item
         Session::addMessageAfterRedirect(sprintf(__('%1$s: %2$s'), __('Item successfully deleted'), $display));

      }
   }


   /**
    * Add a message on purge action
   **/
   function addMessageOnPurgeAction() {

      $addMessAfterRedirect = false;

      if (isset($this->input['_purge'])
          || isset($this->input['_delete'])) {
         $addMessAfterRedirect = true;
      }

      if (isset($this->input['_purge'])) {
         $this->input['_no_message_link'] = true;
      }

      if (isset($this->input['_no_message'])
          || !$this->auto_message_on_action) {
         $addMessAfterRedirect = false;
      }

      if ($addMessAfterRedirect) {
         $link = $this->getFormURL();
         if (!isset($link)) {
            return;
         }
         if (isset($this->input['_no_message_link'])) {
            $display = $this->getNameID();
         } else {
            $display = $this->getLink();
         }
          //TRANS : %s is the description of the updated item
         Session::addMessageAfterRedirect(sprintf(__('%1$s: %2$s'), __('Item successfully purged'),
                                                  $display));
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
    * Restore an item put in the dustbin in the database.
    *
    * @param $input     array    the _POST vars returned by the item form when press restore
    * @param $history   boolean  do history log ? (default 1)
    *
    * @return boolean : true on success
   **/
   function restore(array $input, $history=1) {

      if (!$this->getFromDB($input[static::getIndexName()])) {
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
            $logaction  = Log::HISTORY_RESTORE_ITEM;
            if ($this->useDeletedToLockIfDynamic()
                && $this->isDynamic()) {
               $logaction = Log::HISTORY_UNLOCK_ITEM;
            }
            Log::history($this->input["id"], $this->getType(), $changes, 0, $logaction);
         }

         $this->post_restoreItem();
         Plugin::doHook("item_restore", $this);
         if ($this->mailqueueonaction) {
            QueuedMail::forceSendFor($this->getType(), $this->fields['id']);
         }
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

      $addMessAfterRedirect = false;
      if (isset($this->input['_restore'])) {
         $addMessAfterRedirect = true;
      }

      if (isset($this->input['_no_message'])
          || !$this->auto_message_on_action) {
         $addMessAfterRedirect = false;
      }

      if ($addMessAfterRedirect) {
         $link = $this->getFormURL();
         if (!isset($link)) {
            return;
         }
         if (isset($this->input['_no_message_link'])) {
            $display = $this->getNameID();
         } else {
            $display = $this->getLink();
         }
         //TRANS : %s is the description of the updated item
         Session::addMessageAfterRedirect(sprintf(__('%1$s: %2$s'), __('Item successfully restored'), $display));
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
    * May be overloaded if needed (ex Ticket)
    *
    * @since version 0.83
    *
    * @param $type itemtype of object to add
    *
    * @return rights
   **/
   function canAddItem($type) {
      return $this->can($this->getID(), UPDATE);
   }


   /**
    * Have I the global right to "create" the Object
    * May be overloaded if needed (ex KnowbaseItem)
    *
    * @return booleen
   **/
   static function canCreate() {

      if (static::$rightname) {
         return Session::haveRight(static::$rightname, CREATE);
      }
      return false;
   }


   /**
    * Have I the global right to "delete" the Object
    *
    * May be overloaded if needed
    *
    * @return booleen
   **/
   static function canDelete() {

      if (static::$rightname) {
         return Session::haveRight(static::$rightname, DELETE);
      }
      return false;
   }


   /**
    * Have I the global right to "purge" the Object
    *
    * May be overloaded if needed
    *
    * @return booleen
    **/
   static function canPurge() {

      if (static::$rightname) {
         return Session::haveRight(static::$rightname, PURGE);
      }
      return false;
   }


   /**
    * Have I the global right to "update" the Object
    *
    * Default is calling canCreate
    * May be overloaded if needed
    *
    * @return booleen
   **/
   static function canUpdate() {

      if (static::$rightname) {
         return Session::haveRight(static::$rightname, UPDATE);
      }
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

      if (!$this->checkEntity()) {
         return false;
      }
      return true;
   }


   /**
    * Have I the right to "update" the Object
    *
    * Default is true and check entity if the objet is entity assign
    *
    * May be overloaded if needed
    *
    * @return booleen
   **/
   function canUpdateItem() {

      if (!$this->checkEntity()) {
         return false;
      }
      return true;
   }


   /**
    * Have I the right to "delete" the Object
    *
    * Default is true and check entity if the objet is entity assign
    *
    * May be overloaded if needed
    *
    * @return booleen
   **/
   function canDeleteItem() {

      if (!$this->checkEntity()) {
         return false;
      }
      return true;
   }


   /**
    * Have I the right to "purge" the Object
    *
    * Default is true and check entity if the objet is entity assign
    *
    * @since version 0.85
    *
    * @return booleen
   **/
   function canPurgeItem() {

      if (!$this->checkEntity()) {
         return false;
      }

      // Can purge an object with Infocom only if can purge Infocom
      if (InfoCom::canApplyOn($this)) {
         $infocom = new Infocom();

         if ($infocom->getFromDBforDevice($this->getType(), $this->fields['id'])) {
            return $infocom->canPurge();
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
   static function canView() {

      if (static::$rightname) {
         return Session::haveRight(static::$rightname, READ);
      }
      return false;
   }


   /**
    * Have I the right to "view" the Object
    * May be overloaded if needed
    *
    * @return booleen
   **/
   function canViewItem() {

      if (!$this->checkEntity(true)) {
         return false;
      }

      // else : Global item
      return true;
   }


   /**
    * Have i right to see action button
    *
    * @param $ID   integer   ID to check
    *
    * @since version 0.85
    *
    * @return booleen
   **/
   function canEdit($ID) {

      if ($this->maybeDeleted()) {
         return ($this->can($ID, CREATE)
                 || $this->can($ID, UPDATE)
                 || $this->can($ID, DELETE)
                 || $this->can($ID, PURGE));
      }
      return ($this->can($ID, CREATE)
              || $this->can($ID, UPDATE)
              || $this->can($ID, PURGE));
   }


   /**
    * Can I change recursive flag to false
    * check if there is "linked" object in another entity
    *
    * May be overloaded if needed
    *
    * @return booleen
   **/
   function canUnrecurs() {
      global $DB, $CFG_GLPI;

      $ID  = $this->fields['id'];
      if (($ID < 0)
          || !$this->fields['is_recursive']) {
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
                                  "`$f`='$ID' AND entities_id NOT IN $entities") > 0) {
            return false;
         }
      }

      if (isset($RELATION[$this->getTable()])) {
         foreach ($RELATION[$this->getTable()] as $tablename => $field) {
            if ($tablename[0] != '_') {

               $itemtype = getItemTypeForTable($tablename);
               $item     = new $itemtype();

               if ($item->isEntityAssign()) {

                  // 1->N Relation
                  if (is_array($field)) {
                     foreach ($field as $f) {
                        if (countElementsInTable($tablename,
                                                 "`$f`='$ID'
                                                   AND entities_id NOT IN $entities") > 0) {
                           return false;
                        }
                     }

                  } else {
                     if (countElementsInTable($tablename,
                                              "`$field`='$ID'
                                                AND entities_id NOT IN $entities") > 0) {
                        return false;
                     }
                  }

               } else {
                  foreach ($RELATION as $othertable => $rel) {
                     // Search for a N->N Relation with devices
                     if (($othertable == "_virtual_device")
                         && isset($rel[$tablename])) {
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
                                                           AND `$tablename`.`$typefield`
                                                                  ='$itemtype'
                                                           AND `$tablename`.`$devfield`
                                                                  =`$itemtable`.id
                                                           AND `$itemtable`.`entities_id`
                                                                  NOT IN $entities") > '0') {
                                    return false;
                                 }
                              }

                           }
                        }

                     // Search for another N->N Relation
                     } else if (($othertable != $this->getTable())
                              && isset($rel[$tablename])) {
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
                                                                  NOT IN $entities") > '0') {
                                    return false;
                                 }
                              }

                           } else {
                              $otherfield = $rel[$tablename];
                              if (countElementsInTable(array($tablename, $othertable),
                                                       "`$tablename`.`$field`=$ID
                                                        AND `$tablename`.`$otherfield`
                                                               =`$othertable`.id
                                                        AND `$othertable`.`entities_id`
                                                               NOT IN $entities") > '0') {
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
      if (($this->getType() > 0)
          && countElementsInTable(array('glpi_documents_items', 'glpi_documents'),
                                  "`glpi_documents_items`.`items_id`='$ID'
                                   AND `glpi_documents_items`.`itemtype`=".$this->getType()."
                                   AND `glpi_documents_items`.`documents_id`=`glpi_documents`.`id`
                                   AND `glpi_documents`.`entities_id` NOT IN $entities") > '0') {
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
   function canMassiveAction($action, $field, $value) {
      return true;
   }


   /**
    * Display a 2 columns Footer for Form buttons
    * Close the form is user can edit
    *
    * @param $options   array of possible options:
    *     - withtemplate : 1 for newtemplate, 2 for newobject from template
    *     - colspan for each column (default 2)
    *     - candel : set to false to hide "delete" button
    *     - canedit : set to false to hide all buttons
    *     - addbuttons : array of buttons to add
   **/
   function showFormButtons($options=array()) {
      global $CFG_GLPI;

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
          || !$this->canEdit($ID)) {
         echo "</table></div>";
         // Form Header always open form
         if (!$params['canedit']) {
            Html::closeForm();
         }
         return false;
      }
      echo "<tr class='tab_bg_2'>";

      if ($params['withtemplate']
          ||$this->isNewID($ID)) {

         echo "<td class='center' colspan='".($params['colspan']*2)."'>";

         if (($ID <= 0) || ($params['withtemplate'] == 2)) {
            echo Html::submit(_x('button','Add'), array('name' => 'add'));
         } else {
            //TRANS : means update / actualize
            echo Html::submit(_x('button','Save'), array('name' => 'update'));
         }

      } else {
         if ($params['candel']
             && !$this->can($ID, DELETE)
             && !$this->can($ID, PURGE)) {
            $params['candel'] = false;
         }

         if ($params['canedit'] && $this->can($ID, UPDATE)) {
            echo "<td class='center' colspan='".($params['colspan']*2)."'>\n";
            echo Html::submit(_x('button','Save'), array('name' => 'update'));
         }

         if ($params['candel']) {
            if ($params['canedit'] && $this->can($ID, UPDATE)) {
               echo "</td></tr><tr class='tab_bg_2'>\n";
            }
            if ($this->isDeleted()) {
               if ($this->can($ID, DELETE)) {
                  echo "<td class='right' colspan='".($params['colspan']*2)."' >\n";
                  echo Html::submit(_x('button','Restore'), array('name' => 'restore'));
               }

               if ($this->can($ID, PURGE)) {
                  echo "<span class='very_small_space'>";
                  if (in_array($this->getType(), Item_Devices::getConcernedItems())) {
                     Html::showToolTip(__('Check to keep the devices while deleting this item'));
                     echo "&nbsp;";
                     echo "<input type='checkbox' name='keep_devices' value='1'";
                     if (!empty($_SESSION['glpikeep_devices_when_purging_item'])) {
                        echo " checked";
                     }
                     echo ">&nbsp;";
                  }
                  echo Html::submit(_x('button','Delete permanently'), array('name' => 'purge'));
                  echo "</span>";
               }

            } else {
               echo "<td class='right' colspan='".($params['colspan']*2)."' >\n";
               // If maybe dynamic : do not take into account  is_deleted  field
               if (!$this->maybeDeleted()
                   || $this->useDeletedToLockIfDynamic()) {
                  if ($this->can($ID, PURGE)) {
                     echo Html::submit(_x('button','Delete permanently'),
                                       array('name'    => 'purge',
                                             'confirm' => __('Confirm the final deletion?')));
                  }
               } else if (!$this->isDeleted()
                          && $this->can($ID, DELETE)) {
                  echo Html::submit(_x('button','Put in dustbin'), array('name' => 'delete'));
               }
            }

         }
         if ($this->isField('date_mod')) {
            echo "<input type='hidden' name='_read_date_mod' value='".$this->getField('date_mod')."'>";
         }
      }

      if (!$this->isNewID($ID)) {
         echo "<input type='hidden' name='id' value='$ID'>";
      }
      echo "</td>";
      echo "</tr>\n";

      if ($params['canedit']
          && count($params['addbuttons'])) {
         echo "<tr class='tab_bg_2'>";
         if ((($params['colspan']*2) - count($params['addbuttons'])) > 0) {
            echo "<td colspan='".($params['colspan']*2 - count($params['addbuttons']))."'>&nbsp;".
                 "</td>";
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
    * Initialize item and check right before managing the edit form
    *
    * @since version 0.84
    *
    * @param $ID       Integer  ID of the item/template
    * @param $options  Array    of possible options:
    *     - withtemplate : 1 for newtemplate, 2 for newobject from template
    *
    * @return value of withtemplate option (exit of no right)
   **/
   function initForm($ID, Array $options=array()) {

      if (isset($options['withtemplate'])
          && ($options['withtemplate'] == 2)
          && !$this->isNewID($ID)) {
         // Create item from template
         // Check read right on the template
         $this->check($ID, READ);

         // Restore saved input or template data
         $input = $this->restoreInput($this->fields);

         // If entity assign force current entity to manage recursive templates
         if ($this->isEntityAssign()) {
            $input['entities_id'] = $_SESSION['glpiactive_entity'];
         }

         // Check create right
         $this->check(-1, CREATE, $input);

      } else if ($this->isNewID($ID)) {
         // Restore saved input if available
         $input = $this->restoreInput($options);
         // Create item
         $this->check(-1, CREATE, $input);
      } else {
         // Existing item
         $this->check($ID, READ);
      }

      return (isset($options['withtemplate']) ? $options['withtemplate'] : '');
   }


   /**
    *
    * Display a 2 columns Header 1 for ID, 1 for recursivity menu
    * Open the form is user can edit
    *
    * @param $options  array of possible options:
    *     - target for the Form
    *     - withtemplate : 1 for newtemplate, 2 for newobject from template
    *     - colspan for each column (default 2)
    *     - formoptions string (javascript p.e.)
    *     - canedit boolean edit mode of form ?
   **/
   function showFormHeader($options=array()) {
      global $CFG_GLPI;

      $ID                     = $this->fields['id'];
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
      if (($params['withtemplate'] == 2)
          && $this->isEntityAssign()) {
         $this->fields['entities_id']  = $_SESSION['glpiactive_entity'];
         if ($this->maybeRecursive()) {
            $this->fields["is_recursive"] = 0;
         }
      }

      if ($this->canEdit($ID)) {
         echo "<form name='form' method='post' action='".$params['target']."' ".
                $params['formoptions']." enctype=\"multipart/form-data\">";

         //Should add an hidden entities_id field ?
         //If the table has an entities_id field
         if ($this->isField("entities_id")) {
            //The object type can be assigned to an entity
            if ($this->isEntityAssign()) {
               if (isset($params['entities_id'])) {
                  $entity = $this->fields['entities_id'] = $params['entities_id'];

               } else if ($this->isNewID($ID)
                          || ($params['withtemplate'] == 2)) {
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
      }

      echo "<div class='spaced' id='tabsbody'>";
      echo "<table class='tab_cadre_fixe' id='mainformtable'>";

      echo "<tr class='headerRow'><th colspan='".$params['colspan']."'>";

      $name = '';
      if (!empty($params['withtemplate']) && ($params['withtemplate'] == 2)
          && !$this->isNewID($ID)) {

         echo "<input type='hidden' name='template_name' value='".$this->fields["template_name"]."'>";

         //TRANS: %s is the template name
         printf(__('Created from the template %s'), $this->fields["template_name"]);

      } else if (!empty($params['withtemplate']) && ($params['withtemplate'] == 1)) {
         echo "<input type='hidden' name='is_template' value='1'>\n";
         _e('Template name');
         Html::autocompletionTextField($this, "template_name", array('size' => 25));
      } else if ($this->isNewID($ID)) {
         printf(__('%1$s - %2$s'), __('New item'), $this->getTypeName(1));
      } else {
         //TRANS: %1$s is the Itemtype name and $2$d the ID of the item
         printf(__('%1$s - ID %2$d'), $this->getTypeName(1), $ID);
      }
      $entityname = '';
      if (isset($this->fields["entities_id"])
          && Session::isMultiEntitiesMode()
          && $this->isEntityAssign()) {
         $entityname = Dropdown::getDropdownName("glpi_entities", $this->fields["entities_id"]);
      }

      echo "</th><th colspan='".$params['colspan']."'>";
      if (get_class($this) == 'Entity') {
         // is recursive but cannot be change

      } else {
         if ($this->maybeRecursive()) {
            if (Session::isMultiEntitiesMode()) {
               echo "<table class='tab_format'><tr class='headerRow responsive_hidden'><th>".$entityname."</th>";
               echo "<th class='right'>".__('Child entities')."</th><th>";
               if ($params['canedit']) {
                  if ( $this instanceof CommonDBChild) {
                     echo Dropdown::getYesNo($this->isRecursive());
                     $comment = __("Can't change this attribute. It's inherited from its parent.");
                     // CommonDBChild : entity data is get or copy from parent

                  } else if (!$this->can($ID,'recursive')) {
                     echo Dropdown::getYesNo($this->fields["is_recursive"]);
                     $comment = __('You are not allowed to change the visibility flag for child entities.');

                  } else if ( !$this->canUnrecurs()) {
                     echo Dropdown::getYesNo($this->fields["is_recursive"]);
                     $comment = __('Flag change forbidden. Linked items found.');

                  } else {
                     Dropdown::showYesNo("is_recursive", $this->fields["is_recursive"]);
                     $comment = __('Change visibility in child entities');
                  }
                  echo " ";
                  Html::showToolTip($comment);
               } else {
                  echo Dropdown::getYesNo($this->fields["is_recursive"]);
               }
               echo "</th></tr></table>";
            } else {
               echo $entityname;
               echo "<input type='hidden' name='is_recursive' value='0'>";
            }
         } else {
            echo $entityname;
         }
      }

      // If in modal : do not display link on message after redirect
      if (isset($_REQUEST['_in_modal']) && $_REQUEST['_in_modal']) {
         echo "<input type='hidden' name='_no_message_link' value='1'>";
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
   static function isNewID($ID) {
      return (empty($ID) || ($ID <= 0));
   }


   /**
    * is the current object a new  one
    *
    * @since version 0.83
    *
    * @return boolean
   **/
   function isNewItem() {

      if (isset($this->fields['id'])) {
         return $this->isNewID($this->fields['id']);
      }
      return true;
   }


   /**
    * Check right on an item
    *
    * @param $ID            ID of the item (-1 if new item)
    * @param $right         Right to check : r / w / recursive / READ / UPDATE / DELETE
    * @param &$input  array of input data (used for adding item) (default NULL)
    *
    * @return boolean
   **/
   function can($ID, $right, array &$input=NULL) {
      // Clean ID :
      $ID = Toolbox::cleanInteger($ID);

      // Create process
      if ($this->isNewID($ID)) {
         if (!isset($this->fields['id'])) {
            // Only once
            $this->getEmpty();
         }

         if (is_array($input)) {
            $input = $this->addNeededInfoToInput($input);
            // Copy input field to allow getEntityID() to work
            // from entites_id field or from parent item ref
            foreach ($input as $key => $val) {
               if (isset($this->fields[$key])) {
                  $this->fields[$key] = $val;
               }
            }
            // Store to be available for others functions
            $this->input = $input;
         }

         if ($this->isPrivate()
             && ($this->fields['users_id'] === Session::getLoginUserID())) {
            return true;
         }
  //       if (!static::canCreate()) echo 'ii';
  //       if (!$this->canCreateItem()) echo 'jj';
         return (static::canCreate() && $this->canCreateItem());

      }
      // else : Get item if not already loaded
      if (!isset($this->fields['id']) || ($this->fields['id'] != $ID)) {
         // Item not found : no right
         if (!$this->getFromDB($ID)) {
            return false;
         }
      }
      switch ($right) {
         case READ :
            // Personnal item
            if ($this->isPrivate()
                && ($this->fields['users_id'] === Session::getLoginUserID())) {
               return true;
            }
            return (static::canView() && $this->canViewItem());

         case UPDATE :
            // Personnal item
            if ($this->isPrivate()
                && ($this->fields['users_id'] === Session::getLoginUserID())) {
               return true;
            }
            return (static::canUpdate() && $this->canUpdateItem());

         case DELETE :
            // Personnal item
            if ($this->isPrivate()
                && ($this->fields['users_id'] === Session::getLoginUserID())) {
               return true;
            }
            return (static::canDelete() && $this->canDeleteItem());

         case PURGE :
            // Personnal item
            if ($this->isPrivate()
                && ($this->fields['users_id'] === Session::getLoginUserID())) {
               return true;
            }
            return (static::canPurge() && $this->canPurgeItem());

        case CREATE :
            // Personnal item
            if ($this->isPrivate()
                && ($this->fields['users_id'] === Session::getLoginUserID())) {
               return true;
            }
            return (static::canCreate() && $this->canCreateItem());

         case 'recursive' :
            if ($this->isEntityAssign()
                && $this->maybeRecursive()) {
               if (static::canCreate()
                   && Session::haveAccessToEntity($this->getEntityID())) {
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
    * @param $ID            ID of the item (-1 if new item)
    * @param $right         Right to check : r / w / recursive
    * @param &$input  array of input data (used for adding item) (default NULL)
    *
    * @return nothing
   **/
   function check($ID, $right, array &$input=NULL) {
      global $CFG_GLPI;

      // Check item exists
      if (!$this->isNewID($ID)
          && !$this->getFromDB($ID)) {
         // Gestion timeout session
         Session::redirectIfNotLoggedIn();
         Html::displayNotFoundError();

      } else {
         if (!$this->can($ID,$right,$input)) {
            // Gestion timeout session
            Session::redirectIfNotLoggedIn();
            Html::displayRightError();
         }
      }
   }


   /**
    * Check if have right on this entity
    *
    * @param $recursive   boolean    set true to accept recursive items of ancestors
    *                                of active entities (View case for example) (default false)
    * @since version 0.85
    *
    * @return booleen
   **/
   function checkEntity($recursive=false) {

      // Is an item assign to an entity
      if ($this->isEntityAssign()) {
         // Can be recursive check
         if ($recursive && $this->maybeRecursive()) {
            return Session::haveAccessToEntity($this->getEntityID(), $this->isRecursive());
         }
         //  else : No recursive item         // Have access to entity
         return Session::haveAccessToEntity($this->getEntityID());
      }
      // else : Global item
      return true;
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
         Session::redirectIfNotLoggedIn();
         Html::displayRightError();
      }
   }


   /**
    * Get global right on an object
    *
    * @param $right Right to check : c / r / w / d / READ / UPDATE / CREATE / DELETE
    *
    * @return nothing
   **/
   function canGlobal($right) {

      switch ($right) {
         case READ :
            return static::canView();

         case UPDATE :
            return static::canUpdate();

         case CREATE :
            return static::canCreate();

         case DELETE :
            return static::canDelete();

         case PURGE :
            return static::canPurge();

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

      if (!array_key_exists('id', $this->fields)) {
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
    * Can the object be dynamic
    *
    * @since 0.84
    *
    * @return boolean
   **/
   function maybeDynamic() {

      if (!isset($this->fields['id'])) {
         $this->getEmpty();
      }
      return array_key_exists('is_dynamic', $this->fields);
   }


   /**
    * Use deleted field in case of dynamic management to lock ?
    *
    * need to be overriden if object need to use standard deleted management (Computer...)
    * @since 0.84
    *
    * @return boolean
   **/
   function useDeletedToLockIfDynamic() {
      return $this->maybeDynamic();
   }


   /**
    * Is an object dynamic or not
    *
    * @since 0.84
    *
    * @return boolean
   **/
   function isDynamic() {

      if ($this->maybeDynamic()) {
         return $this->fields['is_dynamic'];
      }
      return 0;
   }


   /**
    * Is the object may be private
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
    * Return the linked items (in computers_items)
    *
    * @return an array of linked items  like array('Computer' => array(1,2), 'Printer' => array(5,6))
    * @since version 0.84.4
   **/
   function getLinkedItems() {
      return array();
   }


   /**
    * Return the count of linked items (in computers_items)
    *
    * @return number of linked items
    * @since version 0.84.4
   **/
   function getLinkedItemsCount() {

      $linkeditems = $this->getLinkedItems();
      $nb          = 0;
      if (count($linkeditems)) {
         foreach ($linkeditems as $tab) {
            $nb += count($tab);
         }
      }
      return $nb;
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

      $comment = "";
      $toadd   = array();
      if ($this->isField('completename')) {
         $toadd[] = array('name'  => __('Complete name'),
                          'value' => nl2br($this->getField('completename')));
      }

      if ($this->isField('serial')) {
         $toadd[] = array('name'  => __('Serial number'),
                          'value' => nl2br($this->getField('serial')));
      }

      if ($this->isField('otherserial')) {
         $toadd[] = array('name'  => __('Inventory number'),
                          'value' => nl2br($this->getField('otherserial')));
      }

      if ($this->isField('states_id') && $this->getType()!='State') {
         $tmp = Dropdown::getDropdownName('glpi_states', $this->getField('states_id'));
         if ((strlen($tmp) != 0) && ($tmp != '&nbsp;')) {
            $toadd[] = array('name'  => __('Status'),
                             'value' => $tmp);
         }
      }

      if ($this->isField('locations_id') && $this->getType()!='Location') {
         $tmp = Dropdown::getDropdownName("glpi_locations", $this->getField('locations_id'));
         if ((strlen($tmp) != 0) && ($tmp != '&nbsp;')) {
            $toadd[] = array('name'  => __('Location'),
                             'value' => $tmp);
         }
      }

      if ($this->isField('users_id')) {
         $tmp = getUserName($this->getField('users_id'));
         if ((strlen($tmp) != 0) && ($tmp != '&nbsp;')) {
            $toadd[] = array('name'  => __('User'),
                             'value' => $tmp);
         }
      }

      if ($this->isField('groups_id')
          && ($this->getType() != 'Group')) {
         $tmp = Dropdown::getDropdownName("glpi_groups",$this->getField('groups_id'));
         if ((strlen($tmp) != 0) && ($tmp != '&nbsp;')) {
            $toadd[] = array('name'  => __('Group'),
                             'value' => $tmp);
         }
      }

      if ($this->isField('users_id_tech')) {
         $tmp = getUserName($this->getField('users_id_tech'));
         if ((strlen($tmp) != 0) && ($tmp != '&nbsp;')) {
            $toadd[] = array('name'  => __('Technician in charge of the hardware'),
                             'value' => $tmp);
         }
      }

      if ($this->isField('contact')) {
         $toadd[] = array('name'  => __('Alternate username'),
                          'value' => nl2br($this->getField('contact')));
      }

      if ($this->isField('contact_num')) {
         $toadd[] = array('name'  => __('Alternate username number'),
                          'value' => nl2br($this->getField('contact_num')));
      }

      if (InfoCom::canApplyOn($this)) {
         $infocom = new Infocom();
         if ($infocom->getFromDBforDevice($this->getType(), $this->fields['id'])) {
            $toadd[] = array('name'  => __('Warranty expiration date'),
                             'value' => Infocom::getWarrantyExpir($infocom->fields["warranty_date"],
                                                                  $infocom->fields["warranty_duration"],
                                                                  0, true));
         }
      }

      if (($this instanceof CommonDropdown)
          && $this->isField('comment')) {
         $toadd[] = array('name'  => __('Comments'),
                          'value' => nl2br($this->getField('comment')));
      }

      if (count($toadd)) {
         foreach ($toadd as $data) {
            // Do not use SPAN here
            $comment .= sprintf(__('%1$s: %2$s')."<br>",
                                "<strong>".$data['name'], "</strong>".$data['value']);
         }
      }

      if (!empty($comment)) {
         return Html::showToolTip($comment, array('display' => false));
      }

      return $comment;
   }


   /**
    * @since version 0.84
    *
    * Get field used for name
   **/
   static function getNameField() {
      return 'name';
   }


   /**
    * @since version 0.84
    *
    * Get field used for completename
   **/
   static function getCompleteNameField() {
      return 'completename';
   }


   /** Get raw name of the object
    * Maybe overloaded
    *
    * @see CommonDBTM::getNameField
    *
    * @since version 0.85
   **/
   function getRawName() {

      if (isset($this->fields[static::getNameField()])) {
         return $this->fields[static::getNameField()];
      }
      return '';
   }


   /** Get raw completename of the object
    * Maybe overloaded
    *
    * @see CommonDBTM::getCompleteNameField
    *
    * @since version 0.85
   **/
   function getRawCompleteName() {

      if (isset($this->fields[static::getCompleteNameField()])) {
         return $this->fields[static::getCompleteNameField()];
      }
      return '';
   }


   /**
    * Get the name of the object
    *
    * @param $options array of options
    *    - comments     : boolean / display comments
    *    - complete     : boolean / display completename instead of name
    *    - additional   : boolean / display aditionals information
    *
    * @return String: name of the object in the current language
    *
    * @see CommonDBTM::getRawCompleteName
    * @see CommonDBTM::getRawName
   **/
   function getName($options = array()) {

      $p['comments']   = false;
      $p['complete']   = false;
      $p['additional'] = false;

      if (is_array($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $name = '';
      if ($p['complete']) {
         $name = $this->getRawCompleteName();
      }
      if (empty($name)) {
         $name = $this->getRawName();
      }

      if (strlen($name) != 0) {
         if ($p['additional']) {
            $pre = $this->getPreAdditionalInfosForName();
            if (!empty($pre)) {
               $name = sprintf(__('%1$s - %2$s'), $pre, $name);
            }
            $post = $this->getPostAdditionalInfosForName();
            if (!empty($post)) {
               $name = sprintf(__('%1$s - %2$s'), $name, $post);
            }
         }
         if ($p['comments']) {
            $comment = $this->getComments();
            if (!empty($comment)) {
               $name = sprintf(__('%1$s - %2$s'), $name, $comment);
            }
         }
         return $name;
      }
      return NOT_AVAILABLE;
   }


   /**
    * Get additionals information to add before name
    *
    * @since version 0.84
    *
    * @return String: string to add
   **/
   function getPreAdditionalInfosForName() {
      return '';
   }

   /**
    * Get additionals information to add after name
    *
    * @since version 0.84
    *
    * @return String: string to add
   **/
   function getPostAdditionalInfosForName() {
      return '';
   }


   /**
    * Get the name of the object with the ID if the config is set
    * Should Not be overloaded (overload getName() instead)
    *
    * @see CommonDBTM::getName
    *
    * @param $options array of options
    *    - comments     : boolean / display comments
    *    - complete     : boolean / display completename instead of name
    *    - additional   : boolean / display aditionals information
    *    - forceid      : boolean  override config and display item's ID (false by default)
    *
    * @return String: name of the object in the current language
   **/
   function getNameID($options=array()) {
      global $CFG_GLPI;

      $p['forceid'] = false;
      $p['comments'] = false;

      if (is_array($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      if ($p['forceid']
          || $_SESSION['glpiis_ids_visible']) {
         $addcomment = $p['comments'];

         // unset comment
         $p['comments'] = false;
         $name = $this->getName($p);

         //TRANS: %1$s is a name, %2$s is ID
         $name = sprintf(__('%1$s (%2$s)'), $name, $this->getField('id'));

         if ($addcomment) {
            $comment = $this->getComments();
            if (!empty($comment)) {
               $name = sprintf(__('%1$s - %2$s'), $name, $comment);
            }
         }
         return $name;
      }
      return $this->getName($options);
   }


   /**
    * Get the Search options for the given Type
    *
    * This should be overloaded in Class
    *
    * @return an array of search options
    * More information on https://forge.indepnet.net/wiki/glpi/SearchEngine
   **/
   function getSearchOptions() {

      $tab                     = array();
      $tab['common']           =__('Characteristics');

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['name']          = __('Name');
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['massiveaction'] = false;

      return $tab;
   }


   /**
    * Get all the massive actions available for the current class regarding given itemtype
    *
    * @since version 0.85
    *
    * @param $actions    array   of the actions to update
    * @param $itemtype           the type of the item for which we want the actions
    * @param $is_deleted         (default 0)
    * @param $checkitem          (default NULL)
    *
    * @return nothing (update is set inside $actions)
   **/
   static function getMassiveActionsForItemtype(array &$actions, $itemtype, $is_deleted=0,
                                                CommonDBTM $checkitem=NULL) {
   }


   /**
    * Class-specific method used to show the fields to specify the massive action
    *
    * @since version 0.85
    *
    * @param $ma the current massive action object
    *
    * @return false if parameters displayed ?
   **/
   static function showMassiveActionsSubForm(MassiveAction $ma) {
      return false;
   }


   /**
    * Class specific execution of the massive action (new system) by itemtypes
    *
    * @since version 0.85
    *
    * @param $ma the current massive action object
    * @param $item the item on which apply the massive action
    * @param $ids an array of the ids of the item on which apply the action
    *
    * @return nothing (direct submit to $ma object)
   **/
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {
   }


   /**
    * Get the standard massive actions which are forbidden
    *
    * @since version 0.84
    *
    * This should be overloaded in Class
    *
    * @return an array of massive actions
   **/
   function getForbiddenStandardMassiveAction() {
      return array();
   }


   /**
    * Get the specific massive actions
    *
    * @since version 0.84
    *
    * This should be overloaded in Class
    *
    * @param $checkitem link item to check right   (default NULL)
    *
    * @return an array of massive actions
   **/
   function getSpecificMassiveActions($checkitem=NULL) {
      return array();
   }


   /**
    * Print out an HTML "<select>" for a dropdown
    *
    * This should be overloaded in Class
    *
    * @param $options   array of possible options:
    * Parameters which could be used in options array :
    *    - name : string / name of the select (default is depending itemtype)
    *    - value : integer / preselected value (default 0)
    *    - comments : boolean / is the comments displayed near the dropdown (default true)
    *    - entity : integer or array / restrict to a defined entity or array of entities
    *                   (default -1 : no restriction)
    *    - toupdate : array / Update a specific item on select change on dropdown
    *                   (need value_fieldname, to_update, url (see Ajax::updateItemOnSelectEvent for information)
    *                   and may have moreparams)
    *    - used : array / Already used items ID: not to display in dropdown (default empty)
    *
    * @return nothing display the dropdown
   **/
   static function dropdown($options=array()) {
      /// TODO try to revert usage : Dropdown::show calling this function
      /// TODO use this function instead of Dropdown::show
      return Dropdown::show(get_called_class(), $options);
   }


   /**
    * Return a search option by looking for a value of a specific field and maybe a specific table
    *
    * @param $field   the field in which looking for the value (for example : table, name, etc)
    * @param $value   the value to look for in the field
    * @param $table   the table (default '')
    *
    * @return then search option array, or an empty array if not found
   **/
   function getSearchOptionByField($field, $value, $table='') {

      foreach ($this->getSearchOptions() as $id => $searchOption) {
         if ((isset($searchOption['linkfield']) && ($searchOption['linkfield'] == $value))
             || (isset($searchOption[$field]) && ($searchOption[$field] == $value))) {
            if (($table == '')
                || (($table != '') && ($searchOption['table'] == $table))) {
               // Set ID ;
               $searchOption['id'] = $id;
               return $searchOption;
            }
         }
      }
      return array();
   }


   /**
    * Get search options
    *
    * @since version 0.85
    *
    * @return then search option array
   **/
   function getOptions() {

      if (!$this->searchopt) {
         $this->searchopt = Search::getOptions($this->getType());
      }

      return $this->searchopt;
   }


   /**
    * Return a search option ID by looking for a value of a specific field and maybe a specific table
    *
    * @since version 0.83
    *
    * @param $field   the field in which looking for the value (for example : table, name, etc)
    * @param $value   the value to look for in the field
    * @param $table   the table (default '')
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
    * @param $display   display or not messages in and addAfterRedirect (true by default)
    *
    * @return input the data checked
   **/
   function filterValues($display=true) {
   // MoYo : comment it because do not understand why filtering is disable
//       if (in_array('CommonDBRelation', class_parents($this))) {
//          return true;
//       }
      //Type mismatched fields
      $fails = array();
      if (isset($this->input) && is_array($this->input) && count($this->input)) {

         foreach ($this->input as $key => $value) {
            $unset        = false;
            $regs         = array();
            $searchOption = $this->getSearchOptionByField('field', $key);

            if (isset($searchOption['datatype'])
                && (is_null($value) || ($value == '') || ($value == 'NULL'))) {

               switch ($searchOption['datatype']) {
                  case 'date' :
                  case 'datetime' :
                     // don't use $unset', because this is not a failure
                     $this->input[$key] = 'NULL';
                     break;
               }
            } else if (isset($searchOption['datatype'])
                       && !is_null($value)
                       && ($value != '')
                       && ($value != 'NULL')) {

               switch ($searchOption['datatype']) {
                  case 'integer' :
                  case 'count' :
                  case 'number' :
                  case 'decimal' :
                     $value = str_replace(',','.',$value);
                     if ($searchOption['datatype'] == 'decimal') {
                        $this->input[$key] = floatval(Toolbox::cleanDecimal($value));
                     } else {
                        $this->input[$key] = intval(Toolbox::cleanInteger($value));
                     }
                     if (!is_numeric($this->input[$key])) {
                        $unset = true;
                     }
                     break;

                  case 'bool' :
                     if (!in_array($value,array(0,1))) {
                        $unset = true;
                     }
                     break;

                  case 'ip' :
                     $address = new IPAddress();
                     if (!$address->setAddressFromString($value))
                        $unset = true;
                     else if (!$address->is_ipv4())
                        $unset = true;
                     break;

                  case 'mac' :
                     preg_match("/([0-9a-fA-F]{1,2}([:-]|$)){6}$/",$value,$regs);
                     if (empty($regs)) {
                        $unset = true;
                     }
                     // Define the MAC address to lower to reduce complexity of SQL queries
                     $this->input[$key] = strtolower ($value);
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
                     if (strlen($value) > 255) {
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
         //TRANS: %s is the list of the failed fields
         $message = sprintf(__('%1$s: %2$s'), __('At least one field has an incorrect value'),
                            implode(',',$fails));
         Session::addMessageAfterRedirect($message, INFO, true);
      }
   }


   /**
    * Add more check for values
    *
    * @param $datatype   datatype of the value
    * @param &$value     value to check (pass by reference)
    *
    * @return true if value is ok, false if not
   **/
   function checkSpecificValues($datatype, &$value) {
      return true;
   }


   /**
    * Get fields to display in the unicity error message
    *
    * @return an array which contains field => label
   **/
   function getUnicityFieldsToDisplayInErrorMessage() {

      return array('id'          => __('ID'),
                   'serial'      => __('Serial number'),
                   'entities_id' => __('Entity'));
   }


   function getUnallowedFieldsForUnicity() {
      return array('alert', 'comment', 'date_mod', 'id', 'is_recursive', 'items_id');
   }


   /**
    * Build an unicity error message
    *
    * @param $msgs      the string not transleted to be display on the screen, or to be sent in a notification
    * @param $unicity   the unicity criterion that failed to match
    * @param $doubles   the items that are already present in DB
   **/
   function getUnicityErrorMessage($msgs, $unicity, $doubles) {

      foreach($msgs as $field => $value) {
         $table = getTableNameForForeignKeyField($field);
         if ($table != '') {
            $searchOption = $this->getSearchOptionByField('field', 'name', $table);
         } else {
            $searchOption = $this->getSearchOptionByField('field', $field);
         }
         $message[] = sprintf(__('%1$s = %2$s'), $searchOption['name'], $value);
      }

      if ($unicity['action_refuse']) {
         $message_text = sprintf(__('Impossible record for %s'),
                                 implode('&nbsp;&amp;&nbsp;', $message));
      } else {
         $message_text = sprintf(__('Item successfully added but duplicate record on %s'),
                                 implode('&nbsp;&amp;&nbsp;',$message));
      }
      $message_text .= '<br>'.__('Other item exist');

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
            $item = clone $this;
            $item->getFromDB($double['id']);
         }

         $double_text = '';
         if ($item->canView() && $item->canViewItem()) {
            $double_text = $item->getLink(array('linkoption' => "target='_blank'"));
         }

         foreach ($this->getUnicityFieldsToDisplayInErrorMessage() as $key => $value) {
            $field_value = $item->getField($key);
            if ($field_value != NOT_AVAILABLE) {
               if (getTableNameForForeignKeyField($key) != '') {
                  $field_value = Dropdown::getDropdownName(getTableNameForForeignKeyField($key),
                                                           $field_value);
               }
               $new_text = sprintf(__('%1$s: %2$s'), $value, $field_value);
               if (empty($double_text)) {
                  $double_text = $new_text;
               } else {
                  $double_text = sprintf(__('%1$s - %2$s'), $double_text, $new_text);
               }
            }
         }
         // Add information on item in dustbin
         if ($item->isField('is_deleted') && $item->getField('is_deleted')) {
            $double_text = sprintf(__('%1$s - %2$s'), $double_text, __('Item in the dustbin'));
         }

         $message_text .= "<br>[$double_text]";
      }
      return $message_text;
   }


   /**
    * Check field unicity before insert or update
    *
    * @param add                 true for insert, false for update (false by default)
    * @param $options   array
    *
    * @return true if item can be written in DB, false if not
   **/
   function checkUnicity($add=false, $options=array()) {
      global $DB, $CFG_GLPI;

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
         foreach ($all_fields as $key => $fields) {

            //If there's fields to check
            if (!empty($fields) && !empty($fields['fields'])) {
               $where    = "";
               $continue = true;
               foreach (explode(',',$fields['fields']) as $field) {
                  if (isset($this->input[$field]) //Field is set
                      //Standard field not null
                      && (((getTableNameForForeignKeyField($field) == '')
                           && ($this->input[$field] != ''))
                          //Foreign key and value is not 0
                          || ((getTableNameForForeignKeyField($field) != '')
                              && ($this->input[$field] > 0)))
                      && !Fieldblacklist::isFieldBlacklisted(get_class($this), $entities_id, $field,
                                                             $this->input[$field])) {
                     $where .= " AND `".$this->getTable()."`.`$field` = '".$this->input[$field]."'";
                  } else {
                     $continue = false;
                  }
               }

               if ($continue
                   && ($where != '')) {
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

                  if (countElementsInTable($this->getTable(),"1 $where $where_global") > 0) {
                     if ($p['unicity_error_message']
                         || $p['add_event_on_duplicate']) {
                        $message = array();
                        foreach (explode(',',$fields['fields']) as $field) {
                           $message[$fiels] = $this->input[$field];
                        }

                        $doubles      = getAllDatasFromTable($this->gettable(),
                                                             "1 $where $where_global");
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
                                       //TRANS: %1$s is the user login, %2$s the message
                                       sprintf(__('%1$s trying to add an item that already exists: %2$s'),
                                               $_SESSION["glpiname"], $message_text));
                        }
                     }
                     if ($fields['action_refuse']) {
                        $result = false;
                     }
                     if ($fields['action_notify']) {
                        $params = array('action_type' => $add,
                                        'action_user' => getUserName(Session::getLoginUserID()),
                                        'entities_id' => $entities_id,
                                        'itemtype'    => get_class($this),
                                        'date'        => $_SESSION['glpi_currenttime'],
                                        'refuse'      => $fields['action_refuse'],
                                        'label'       => $message,
                                        'field'       => $fields,
                                        'double'      => $doubles);
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
    * @param $crit   array    of criteria (ex array('is_active'=>'1'))
    * @param $force  boolean  force purge not on put in dustbin (default 0)
   **/
   function deleteByCriteria($crit=array(), $force=0) {
      global $DB;

      $ok = false;
      if (is_array($crit) && (count($crit) > 0)) {
         $crit['FIELDS'] = 'id';
         $ok = true;
         foreach ($DB->request($this->getTable(), $crit) as $row) {
            if (!$this->delete($row, $force)) {
               $ok = false;
            }
         }

      }
      return $ok;
   }


   /**
    * get the Entity of an Item
    *
    * @param $itemtype  string   item type
    * @param $items_id  integer  id of the item
    *
    * @return integer ID of the entity or -1
   **/
   static function getItemEntity($itemtype, $items_id) {

      if ($itemtype
          && ($item = getItemForItemtype($itemtype))) {

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
    * @param $field     String         name of the field
    * @param $values    String/Array   with the value to display or a Single value
    * @param $options   Array          of options
    *
    * @return return the string to display
   **/
   static function getSpecificValueToDisplay($field, $values, array $options=array()) {
      return '';
   }


   /**
    * display a field using standard system
    *
    * @since version 0.83
    *
    * @param $field_id_or_search_options  integer/string/array id of the search option field
    *                                                             or field name
    *                                                             or search option array
    * @param $values                                           mixed value to display
    * @param $options                     array                of possible options:
    * Parameters which could be used in options array :
    *    - comments : boolean / is the comments displayed near the value (default false)
    *    - any others options passed to specific display method
    *
    * @return return the string to display
   **/
   function getValueToDisplay($field_id_or_search_options, $values, $options=array()) {
      global $CFG_GLPI;

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
         $searchopt = $this->getSearchOptions();

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
            $value  = $values;
            $values = array($field => $value);
         }

         if (isset($searchoptions['datatype'])) {
            $unit = '';
            if (isset($searchoptions['unit'])) {
               $unit = $searchoptions['unit'];
            }

            switch ($searchoptions['datatype']) {
               case "count" :
               case "number" :
                  if (isset($searchoptions['toadd']) && isset($searchoptions['toadd'][$value])) {
                     return $searchoptions['toadd'][$value];
                  }
                  if ($options['html']) {
                     return Dropdown::getValueWithUnit(Html::formatNumber($value, false, 0), $unit);
                  }
                  return $value;

               case "decimal" :
                  if ($options['html']) {
                     return Dropdown::getValueWithUnit(Html::formatNumber($value), $unit);
                  }
                  return $value;

               case "string" :
               case "mac" :
               case "ip" :
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
                  if (($value == 0)
                      && isset($searchoptions['emptylabel'])) {
                     return $searchoptions['emptylabel'];
                  }
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
                     if (Toolbox::strlen($link) > $CFG_GLPI["url_maxlength"]) {
                        $link = Toolbox::substr($link, 0, $CFG_GLPI["url_maxlength"])."...";
                     }
                     return "<a href=\"".formatOutputWebLink($orig_link)."\" target='_blank'>$link".
                            "</a>";
                  }
                  return "&nbsp;";

               case "itemlink" :
                  if ($searchoptions['table'] == $this->getTable()) {
                     break;
                  }

               case "dropdown" :
                  if (isset($searchoptions['toadd']) && isset($searchoptions['toadd'][$value])) {
                     return $searchoptions['toadd'][$value];
                  }
                  if (!is_numeric($value)) {
                     return $value;
                  }

                  if (($value == 0)
                      && isset($searchoptions['emptylabel'])) {
                     return $searchoptions['emptylabel'];
                  }

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

               case "itemtypename" :
                  if ($obj = getItemForItemtype($value)) {
                     return $obj->getTypeName(1);
                  }
                  break;

               case "language" :
                  if (isset($CFG_GLPI['languages'][$value])) {
                     return $CFG_GLPI['languages'][$value][0];
                  }
                  return __('Default value');

            }
         }
         // Get specific display if available
         $itemtype = getItemTypeForTable($searchoptions['table']);
         if ($item = getItemForItemtype($itemtype)) {
            $options['searchopt'] = $searchoptions;
            $specific = $item->getSpecificValueToDisplay($field, $values, $options);
            if (!empty($specific)) {
               return $specific;
            }
         }

      }
      return $value;
   }

   /**
    * display a specific field selection system
    *
    * @since version 0.83
    *
    * @param $field     String         name of the field
    * @param $name      string         name of the select (if empty use linkfield) (default '')
    * @param $values    String/Array   with the value to select or a Single value (default '')
    * @param $options   Array          of options
    *
    * @return return the string to display
   **/
   static function getSpecificValueToSelect($field, $name='', $values='', array $options=array()) {
      return '';
   }


   /**
    * Select a field using standard system
    *
    * @since version 0.83
    *
    * @param $field_id_or_search_options  integer/string/array id of the search option field
    *                                                             or field name
    *                                                             or search option array
    * @param $name                        string               name of the select (if empty use linkfield)
    *                                                          (default '')
    * @param $values                                           mixed default value to display
    *                                                          (default '')
    * @param $options                     array                of possible options:
    * Parameters which could be used in options array :
    *    - comments : boolean / is the comments displayed near the value (default false)
    *    - any others options passed to specific display method
    *
    * @return return the string to display
   **/
   function getValueToSelect($field_id_or_search_options, $name='', $values='', $options=array()) {
      global $CFG_GLPI;

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
         $searchopt = $this->getSearchOptions();

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
            $value  = $values;
            $values = array($field => $value);
         }

         if (empty($name)) {
            $name = $searchoptions['linkfield'];
         }
         // If not set : set to specific
         if (!isset($searchoptions['datatype'])) {
            $searchoptions['datatype'] = 'specific';
         }

         $options['display'] = false;
         $unit               = '';
         if (isset($searchoptions['unit'])) {
            $unit = $searchoptions['unit'];
         }

         if (isset($options[$searchoptions['table'].'.'.$searchoptions['field']])) {
            $options = array_merge($options,
                                   $options[$searchoptions['table'].'.'.$searchoptions['field']]);
         }

         switch ($searchoptions['datatype']) {
            case "count" :
            case "number" :
            case "integer" :
               $copytooption = array('min', 'max', 'step', 'toadd', 'unit');
               foreach ($copytooption as $key) {
                  if (isset($searchoptions[$key]) && !isset($options[$key])) {
                     $options[$key] = $searchoptions[$key];
                  }
               }
               $options['value'] = $value;
               return Dropdown::showNumber($name, $options);

            case "decimal" :
            case "mac" :
            case "ip" :
            case "string" :
            case "email" :
            case "weblink" :
               $this->fields[$name] = $value;
               return Html::autocompletionTextField($this, $name, $options);

            case "text" :
               $out = '';
               if (isset($searchoptions['htmltext']) && $searchoptions['htmltext']) {
                  $out = Html::initEditorSystem($name, '', false);
               }
               return $out."<textarea cols='45' rows='5' name='$name'>$value</textarea>";

            case "bool" :
               return Dropdown::showYesNo($name, $value, -1, $options);

            case "color" :
               return Html::showColorField($name, $options);

            case "date" :
            case "date_delay" :
               if (isset($options['relative_dates']) && $options['relative_dates']) {
                  if (isset($searchoptions['maybefuture']) && $searchoptions['maybefuture']) {
                     $options['with_future'] = true;
                  }
                  return Html::showGenericDateTimeSearch($name, $value, $options);
               }
               $copytooption = array('min', 'max', 'maybeempty', 'showyear');
               foreach ($copytooption as $key) {
                  if (isset($searchoptions[$key]) && !isset($options[$key])) {
                     $options[$key] = $searchoptions[$key];
                  }
               }
               $options['value'] = $value;
               return Html::showDateField($name, $options);

            case "datetime" :
               if (isset($options['relative_dates']) && $options['relative_dates']) {
                  if (isset($searchoptions['maybefuture']) && $searchoptions['maybefuture']) {
                     $options['with_future'] = true;
                  }
                  $options['with_time'] = true;
                  return Html::showGenericDateTimeSearch($name, $value, $options);
               }
               $copytooption = array('mindate', 'maxdate', 'mintime', 'maxtime',
                                     'maybeempty', 'timestep');
               foreach ($copytooption as $key) {
                  if (isset($searchoptions[$key]) && !isset($options[$key])) {
                     $options[$key] = $searchoptions[$key];
                  }
               }
               $options['value'] = $value;
               return Html::showDateTimeField($name, $options);

            case "timestamp" :
               $copytooption = array('addfirstminutes', 'emptylabel', 'inhours',  'max', 'min',
                                     'step', 'toadd', 'display_emptychoice');
               foreach ($copytooption as $key) {
                  if (isset($searchoptions[$key]) && !isset($options[$key])) {
                     $options[$key] = $searchoptions[$key];
                  }
               }
               $options['value'] = $value;
               return Dropdown::showTimeStamp($name, $options);

            case "itemlink" :
               // Do not use dropdown if wanted to select string value instead of ID
               if (isset($options['itemlink_as_string']) && $options['itemlink_as_string']) {
                  break;
               }

            case "dropdown" :
               $copytooption     = array('condition', 'displaywith', 'emptylabel',
                                         'right', 'toadd');
               $options['name']  = $name;
               $options['value'] = $value;
               foreach ($copytooption as $key) {
                  if (isset($searchoptions[$key]) && !isset($options[$key])) {
                     $options[$key] = $searchoptions[$key];
                  }
               }
               if (!isset($options['entity'])) {
                  $options['entity'] = $_SESSION['glpiactiveentities'];
               }
               $itemtype = getItemTypeForTable($searchoptions['table']);
               return $itemtype::dropdown($options);

            case "right" :
                return Profile::dropdownRights(Profile::getRightsFor($searchoptions['rightclass']),
                                               $name, $value, array('multiple' => false,
                                                                    'display'  => false));

            case "itemtypename" :
               if (isset($searchoptions['itemtype_list'])) {
                  $options['types'] = $CFG_GLPI[$searchoptions['itemtype_list']];
               }
               $copytooption     = array('types');
               $options['value'] = $value;
               foreach ($copytooption as $key) {
                  if (isset($searchoptions[$key]) && !isset($options[$key])) {
                     $options[$key] = $searchoptions[$key];
                  }
               }
               if (isset($options['types'])) {
                  return Dropdown::showItemTypes($name, $options['types'],
                                                   $options);
               }
               return false;

            case "language" :
               $copytooption = array('emptylabel', 'display_emptychoice');
               foreach ($copytooption as $key) {
                  if (isset($searchoptions[$key]) && !isset($options[$key])) {
                     $options[$key] = $searchoptions[$key];
                  }
               }
               $options['value'] = $value;
               return Dropdown::showLanguages($name, $options);

         }
         // Get specific display if available
         $itemtype = getItemTypeForTable($searchoptions['table']);
         if ($item = getItemForItemtype($itemtype)) {
            $specific = $item->getSpecificValueToSelect($searchoptions['field'], $name,
                                                        $values, $options);
            if (strlen($specific)) {
               return $specific;
            }
         }
      }
      // default case field text
      $this->fields[$name] = $value;
      return Html::autocompletionTextField($this, $name, $options);
   }


   /**
    * @param $itemtype
    * @param $target
    * @param $add       (default 0)
    */
   static function listTemplates($itemtype, $target, $add=0) {
      global $DB;

      if (!($item = getItemForItemtype($itemtype))) {
         return false;
      }

      if (!$item->maybeTemplate()) {
         return false;
      }

      // Avoid to get old data
      $item->clearSavedInput();

      //Check is user have minimum right r
      if (!$item->canView()
          && !$item->canCreate()) {
         return false;
      }

      $query = "SELECT *
                FROM `".$item->getTable()."`
                WHERE `is_template` = '1' ";

      if ($item->isEntityAssign()) {
         $query .= getEntitiesRestrictRequest('AND', $item->getTable(), 'entities_id',
                                              $_SESSION['glpiactive_entity'],
                                              $item->maybeRecursive());
      }
      $query .= " ORDER by `template_name`";

      if ($result = $DB->query($query)) {
         echo "<div class='center'><table class='tab_cadre'>";
         if ($add) {
            $blank_params =
               (strpos($target, '?') ? '&amp;' : '?')
               . "id=-1&amp;withtemplate=2";
            $target_blank = $target . $blank_params;
            echo "<tr><th>" . $item->getTypeName(1)."</th>";
            echo "<th>".__('Choose a template')."</th></tr>";
            echo "<tr><td class='tab_bg_1 center' colspan='2'>";
            echo "<a href=\"$target_blank\">".__('Blank Template')."</a></td>";
            echo "</tr>";
         } else {
            echo "<tr><th>".$item->getTypeName(1)."</th><th>".__('Templates')."</th></tr>";
         }

         while ($data = $DB->fetch_assoc($result)) {
            $templname = $data["template_name"];
            if ($_SESSION["glpiis_ids_visible"] || empty($data["template_name"])) {
               $templname = sprintf(__('%1$s (%2$s)'), $templname, $data["id"]);
            }
            if ($item->canCreate() && !$add) {
               $modify_params =
                  (strpos($target, '?') ? '&amp;' : '?')
                  . "id=".$data['id']
                  . "&amp;withtemplate=1";
               $target_modify = $target . $modify_params;

               echo "<tr><td class='tab_bg_1 center'>";
               echo "<a href=\"$target_modify\">";
               echo "&nbsp;&nbsp;&nbsp;$templname&nbsp;&nbsp;&nbsp;</a></td>";
               echo "<td class='tab_bg_2 center b'>";
               Html::showSimpleForm($target, 'purge', _x('button', 'Delete permanently'),
                                    array('withtemplate' => 1,
                                          'id'           => $data['id']));
               echo "</td>";
            } else {
               $add_params =
                  (strpos($target, '?') ? '&amp;' : '?')
                  . "id=".$data['id']
                  . "&amp;withtemplate=2";
               $target_add = $target . $add_params;

               echo "<tr><td class='tab_bg_1 center' colspan='2'>";
               echo "<a href=\"$target_add\">";
               echo "&nbsp;&nbsp;&nbsp;$templname&nbsp;&nbsp;&nbsp;</a></td>";
            }
            echo "</tr>";
         }

         if ($item->canCreate() && !$add) {
            $create_params =
               (strpos($target, '?') ? '&amp;' : '?')
               . "withtemplate=1";
            $target_create = $target . $create_params;
            echo "<tr><td class='tab_bg_2 center b' colspan='2'>";
            echo "<a href=\"$target_create\">" . __('Add a template...') . "</a>";
            echo "</td></tr>";
         }
         echo "</table></div>\n";
      }
   }


   /**
    * Specificy a plugin itemtype for which entities_id and is_recursive should be forwarded
    *
    * @since 0.83
    *
    * @param $for_itemtype    change of entity for this itemtype will be forwarder
    * @param $to_itemtype     change of entity will affect this itemtype
    *
    * @return nothing
   **/
   static function addForwardEntity($for_itemtype, $to_itemtype) {
      self::$plugins_forward_entity[$for_itemtype][] = $to_itemtype;
   }


   /**
    * Is entity informations forward To ?
    *
    * @since 0.84
    *
    * @param $itemtype    itemtype to check
    *
    * @return boolean
   **/
   static function isEntityForwardTo($itemtype) {

      if (in_array($itemtype, static::$forward_entity_to)) {
         return true;
      }
      //Fill forward_entity_to array with itemtypes coming from plugins
      if (isset(static::$plugins_forward_entity[static::getType()])
          && in_array($itemtype, static::$plugins_forward_entity[static::getType()])) {
         return true;
      }
      return false;
   }


   /**
    * Get rights for an item _ may be overload by object
    *
    * @since version 0.85
    *
    * @param $interface   string   (defalt 'central')
    *
    * @return array of rights to display
   **/
   function getRights($interface='central') {

      $values = array(CREATE  => __('Create'),
                      READ    => __('Read'),
                      UPDATE  => __('Update'),
                      PURGE   => array('short' => __('Purge'),
                                       'long'  => _x('button', 'Delete permanently')));

      if ($this->maybeDeleted()) {
         $values[DELETE] = array('short' => __('Delete'),
                                 'long'  => _x('button', 'Put in dustbin'));
      }
      if ($this->usenotepad) {
         $values[READNOTE] = array('short' => __('Read notes'),
                                   'long' => __("Read the item's notes"));
         $values[UPDATENOTE] = array('short' => __('Update notes'),
                                     'long' => __("Update the item's notes"));
      }

      return $values;
   }

}
?>
