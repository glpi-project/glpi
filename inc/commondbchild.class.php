<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

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

/// Common DataBase Relation Table Manager Class
abstract class CommonDBChild extends CommonDBTM {

   // Mapping between DB fields
   public $itemtype; // Class name or field name (start with itemtype) for link to Parent
   public $items_id; // Field name

   // Make an history of the changes -
   // if true, will write a event in the history of parent for add/delete
   public $dohistory = false;

   /// Drop the element if it is not attached to an item
   /// since version 0.84
   public $mustBeAttached = false;
   /// If it is attached, inherit entity from the item
   /// since version 0.84
   public $inheritEntityFromItem = false;
   // TODO : thinking of factorizing the CommonDBTM::can* methods as checking the item ability
   // may be use for most CommonDBChild


   /**
    * Get the item associated with the current object. Rely on getItemFromArray()
    *
    * @since version 0.84
    *
    * @return object of the concerned item or false on error
   **/
   function getItem() {
      return $this->getItemFromArray($this->fields);
   }


   /**
    * Get the item associated with the elements inside the array (for instance : add method)
    *
    * @since version 0.84
    *
    * @param $array the array containing the item information (type and id) may be $this->field
    *
    * @return object of the concerned item or false on error
   **/
   function getItemFromArray(array $array) {

      if (preg_match('/^itemtype/', $this->itemtype)) {
         if (isset($array[$this->itemtype])) {
            $type = $array[$this->itemtype];
         } else {
            $type = '';
         }
      } else {
         $type = $this->itemtype;
      }

      if (class_exists($type) && isset($array[$this->items_id])) {
         $item = new $type();
         if ($item->getFromDB($array[$this->items_id])) {
            return $item;
         }
         unset($item);
      }

      return false;
   }


   /**
    * \brief recursively display the items of this
    *
    * @param $recursiveItems     array of the items of the current elements (see recursivelyGetItems())
    * @param $elementToDisplay   what to display : 'Type', 'Name', 'Link'
   **/
   static function displayRecursiveItems(array $recursiveItems, $elementToDisplay) {

      if ((!is_array($recursiveItems)) || (count($recursiveItems) == 0)) {
         _e('Item not linked to an object');
         return;
      }

      switch ($elementToDisplay) {
      case 'Type' :
         $masterItem = $recursiveItems[count($recursiveItems) - 1];
         echo $masterItem->getTypeName(1);
         break;

      case 'Name':
      case 'Link':
         $items_elements  = array();
         foreach ($recursiveItems as $item) {
            if ($elementToDisplay == 'Name') {
               $items_elements[] = $item->getName();
            } else {
               $items_elements[] = $item->getLink();
            }
         }
         echo implode(' &lt; ', $items_elements);
         break;
      }

   }


   /**
    * Get all the items associated with the current object by recursive requests
    *
    * @since version 0.84
    *
    * @return an array containing all the items
   **/
   function recursivelyGetItems() {

      $item = $this->getItem();
      if ($item !== false) {
         if ($item instanceof CommonDBChild) {
            return array_merge(array($item), $item->recursivelyGetItems());
         }
         return array($item);
      }
      return array();
   }


   /**
    * Get the ID of entity assigned to the object
    *
    * @return ID of the entity
   **/
   function getEntityID () {

      // Case of Duplicate Entity info to child
      if (parent::isEntityAssign()) {
         return parent::getEntityID();
      }

      // TODO : may be usefull to use $this->getItem

      if (preg_match('/^itemtype/', $this->itemtype)) {
         $type = $this->fields[$this->itemtype];
      } else {
         $type = $this->itemtype;
      }

      if ($item = getItemForItemtype($type)) {
         if ($item->getFromDB($this->fields[$this->items_id]) && $item->isEntityAssign()) {
            return $item->getEntityID();
         }

      }
      return -1;
   }


   function isEntityAssign() {

      // Case of Duplicate Entity info to child
      if (parent::isEntityAssign()) {
         return true;
      }

      // TODO : may be usefull to use $this->getItem

      if (preg_match('/^itemtype/', $this->itemtype)) {
         $type = $this->fields[$this->itemtype];
      } else {
         $type = $this->itemtype;
      }

      if ($item = getItemForItemtype($type)) {
         return $item->isEntityAssign();
      }

      return -1;
   }


   /**
    * Is the object may be recursive
    *
    * @return boolean
   **/
   function maybeRecursive() {

      // Case of Duplicate Entity info to child
      if (parent::maybeRecursive()) {
         return true;
      }

      // TODO : may be usefull to use $this->getItem

      if (preg_match('/^itemtype/', $this->itemtype)) {
         $type = $this->fields[$this->itemtype];
      } else {
         $type = $this->itemtype;
      }

      if ($item = getItemForItemtype($type)) {
         return $item->maybeRecursive();
      }

      return false;
   }


   /**
    * Is the object recursive
    *
    * @return boolean
   **/
   function isRecursive () {

      // Case of Duplicate Entity info to child
      if (parent::maybeRecursive()) {
          return parent::isRecursive();
      }

      // TODO : may be usefull to use $this->getItem

      if (preg_match('/^itemtype/', $this->itemtype)) {
         $type = $this->fields[$this->itemtype];
      } else {
         $type = $this->itemtype;
      }

      if ($item = getItemForItemtype($type)) {
         if ($item->getFromDB($this->fields[$this->items_id])) {
            return $item->isRecursive();
         }

      }
      return false;
   }


   /**
    * @since version 0.84
   **/
   function prepareInputForAdd($input) {

      $item = self::getItemFromArray($input);

      // Invalidate the element if it is not attached to an item although it must
      if ($this->mustBeAttached && ($item == false)) {
         Session::addMessageAfterRedirect(__('Operation performed partially successful'), INFO,
                                          true);
         return false;
      }

      // Set its entity according to the item, if it should
      if ($this->inheritEntityFromItem && ($item == true)) {
         $input['entities_id']  = $item->getEntityID();
         $input['is_recursive'] = intval($item->isRecursive());
      }

      return $input;
   }


   /**
    * @since version 0.84
   **/
   function prepareInputForUpdate($input) {

      $item = self::getItemFromArray($input);

      // TODO : must we apply this filter for the update ?
      // Return invalidate the element if it must be attached but it won't
      if ($this->mustBeAttached && ($item === false)) {
         Session::addMessageAfterRedirect(__('Operation performed partially successful'), INFO,
                                          true);
         return false;
      }

      // TODO : must we apply this filter for the update ?
      // If the entity is inherited from the item, then set it
      if ($this->inheritEntityFromItem && ($item === true)) {
         $input['entities_id']  = $item->getEntityID();
         $input['is_recursive'] = intval($item->isRecursive());
      }

      return $input;
   }


   /**
    * Actions done after the ADD of the item in the database
    *
    * @return nothing
   **/
   function post_addItem() {

      if (isset($this->input['_no_history']) || !$this->dohistory) {
         return false;
      }

      // TODO : may be usefull to use $this->getItem

      if (preg_match('/^itemtype/', $this->itemtype)) {
         $type = $this->fields[$this->itemtype];
      } else {
         $type = $this->itemtype;
      }

      if (!($item = getItemForItemtype($type))) {
         return false;
      }

      if (!$item->dohistory) {
         return false;
      }

      $changes[0] = '0';
      $changes[1] = "";
      $changes[2] = addslashes($this->getNameID(false, true));
      Log::history($this->fields[$this->items_id], $type, $changes, get_class($this),
                   Log::HISTORY_ADD_SUBITEM);
   }


   /**
    * Actions done after the DELETE of the item in the database
    *
    *@return nothing
   **/
   function post_deleteFromDB() {

      if (isset($this->input['_no_history']) || !$this->dohistory) {
         return false;
      }

      // TODO : may be usefull to use $this->getItem

      if (preg_match('/^itemtype/', $this->itemtype)) {
         $type = $this->fields[$this->itemtype];
      } else {
         $type = $this->itemtype;
      }

      if (!($item = getItemForItemtype($type))) {
         return false;
      }

      if (!$item->dohistory) {
         return false;
      }

      $changes[0] = '0';
      $changes[1] = addslashes($this->getNameID(false, true));
      $changes[2] = "";
      Log::history($this->fields[$this->items_id], $type, $changes, get_class($this),
                   Log::HISTORY_DELETE_SUBITEM);
   }


   /**
    * Clean the Relation Table when item of the relation is deleted
    * To be call from the cleanDBonPurge of each Item class
    *
    * @param $itemtype  type of the item
    * @param $item_id   id of the item
   **/
   function cleanDBonItemDelete ($itemtype, $item_id) {
      global $DB;

      $query = "SELECT `".$this->getIndexName()."`
                FROM `".$this->getTable()."`";

      if ($itemtype == $this->itemtype) {
         $where = " WHERE `".$this->items_id."` = '$item_id'";

      } else if (preg_match('/^itemtype/',$this->itemtype)) {
         $where = " WHERE (`".$this->itemtype."` = '$itemtype'
                           AND `".$this->items_id."` = '$item_id')";

      } else {
         return false;
      }

      $result = $DB->query($query.$where);
      while ($data = $DB->fetch_assoc($result)) {
         $data['_no_history'] = true; // Parent is deleted
         $data['_no_notif']   = true; // Parent is deleted
         $this->delete($data);
      }
   }


   /**
    * We can add several CommonDBChild to a given Item. In such case, we display a "+" button and
    * the fields already entered
    * This method display the "+" button
    *
    * @since version 0.84
    * @TODO study if we cannot use these methods for the user emails
    * @see showFieldsForItemForm(CommonDBTM $item, $html_field, $db_field)
    *
    * @param $item the item on which to add the current CommenDBChild
    * @param $html_field the name of the HTML field inside the Item form
    *
    * @result nothing (display only)
   **/
   function showAddButtonForChildItem(CommonDBTM $item, $html_field) {
      global $CFG_GLPI;

      $items_id = $item->getID();
      if (!$item->can($items_id,'r')) {
         return false;
      }
      $canedit = $item->can($items_id,"w");

      $lower_name = strtolower($this->getType());
      $nb_item_var = 'nb'.$lower_name.'s';
      $div_id = $lower_name."add$items_id";

      if ($canedit) {

         echo "&nbsp;<script type='text/javascript'>var $nb_item_var=1; </script>";
         echo "<span id='add".$lower_name."button'><img title=\"".__s('Add')."\" alt=\"".
               __s('Add').
            "\" onClick=\"var row = Ext.get('$div_id');
                             row.createChild('<input type=\'text\' size=\'40\' ".
            "name=\'".$html_field."[-'+$nb_item_var+']\'><br>');
                            $nb_item_var++;\"
               class='pointer' src='".$CFG_GLPI["root_doc"]."/pics/add_dropdown.png'></span>";
      }
   }


   /**
    * We can add several CommonDBChild to a given Item. In such case, we display a "+" button and
    * the fields already entered.
    * This method display the fields
    *
    * @since version 0.84
    * @TODO study if we cannot use these methods for the user emails
    * @see showAddButtonForChildItem(CommonDBTM $item, $html_field)
    *
    * @param $item the item on which to add the current CommenDBChild
    * @param $html_field the name of the HTML field inside the Item form
    * @param $db_field the name of the field inside the CommonDBChild table to display
    *
    * @result nothing (display only)
   **/
   function showFieldsForItemForm(CommonDBTM $item, $html_field, $db_field) {
      global $DB, $CFG_GLPI;

      $items_id = $item->getID();

      if (!$item->can($items_id,'r')) {
         return false;
      }
      $canedit = $item->can($items_id,"w");

      $lower_name = strtolower($this->getType());
      $div_id = $lower_name."add$items_id";

     // To be sure not to load bad datas from glpi_itememails table
      if ($items_id == 0) {
         $items_id = -99;
      }

      $query = "SELECT `$db_field`, `".$this->getIndexName()."`
                FROM `" . $this->getTable() . "`
                WHERE `".$this->items_id."`='".$item->getID()."'";

      if (preg_match('/^itemtype/', $this->itemtype)) {
         $query .= " AND `itemtype` = '".$item->getType()."'";
      }

      $setDefault = $this->isField('is_default');

      $count = 0;
      foreach ($DB->request($query) as $data) {

         $data['is_default'] = 0;
         $data['is_dynamic'] = 0;

         if ($count) {
            echo '<br>';
         }
         $count++;

         if ($setDefault) {
            echo "<input title='" . sprintf(__s('Default %s'), $this->getTypeName(1)) .
                 "' type='radio' name='_default_email' value='".$data[$this->getIndexName()]."'".
                 ($canedit?' ':' disabled').($data['is_default'] ? ' checked' : ' ').">&nbsp;";
         }

         $input_name = $html_field . "[" . $data[$this->getIndexName()] . "]";
         $input_value = $data[$db_field];

         if (!$canedit || ((isset($data['is_dynamic'])) && ($data['is_dynamic']))) {
            echo "<input type='hidden' name='$input_name' value='$input_value'>" .
               $input_value."<span class='b'>&nbsp;(D)</span>";
         } else {
            echo "<input type='text' size='40' name='$input_name' value='$input_value'>";
         }

         /*
         if (!NotificationMail::isItemAddressValid($data['email'])) {
            echo "<span class='red'>&nbsp;".__('Invalid email address')."</span>";
         }
         */
      }

      if ($canedit) {
         echo "<div id='$div_id'>";
         // No email display field
         if ($count == 0) {
            echo "<input type='text' size='40' name='".$html_field."[-100]' value=''>";
         }
         echo "</div>";
      }
   }


}
?>
