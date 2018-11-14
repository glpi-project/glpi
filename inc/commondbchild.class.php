<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/// Common DataBase Relation Table Manager Class
abstract class CommonDBChild extends CommonDBConnexity {

   // Mapping between DB fields
   // * definition
   static public $itemtype; // Class name or field name (start with itemtype) for link to Parent
   static public $items_id; // Field name
   // * rights
   static public $checkParentRights  = self::HAVE_SAME_RIGHT_ON_ITEM;
   static public $mustBeAttached     = true;
   // * log
   static public $logs_for_parent    = true;
   static public $log_history_add    = Log::HISTORY_ADD_SUBITEM;
   static public $log_history_update = Log::HISTORY_UPDATE_SUBITEM;
   static public $log_history_delete = Log::HISTORY_DELETE_SUBITEM;
   static public $log_history_lock   = Log::HISTORY_LOCK_SUBITEM;
   static public $log_history_unlock = Log::HISTORY_UNLOCK_SUBITEM;

   /**
    * @since 0.84
    *
    * @deprecated 9.4
    *
    * @param $itemtype
    * @param $items_id
    *
    * @return string
   **/
   static function getSQLRequestToSearchForItem($itemtype, $items_id) {
      Toolbox::deprecated('Use getSQLCriteriaToSearchForItem');

      $fields     = ['`'.static::getIndexName().'`'];

      // Check item 1 type
      $condition_id = "`".static::$items_id."` = '$items_id'";
      $fields[]     = "`".static::$items_id."` as items_id";
      if (preg_match('/^itemtype/', static::$itemtype)) {
         $fields[]  = "`".static::$itemtype."` AS itemtype";
         $condition = "($condition_id AND `".static::$itemtype."` = '$itemtype')";
      } else {
         $fields[] = "'".static::$itemtype."' AS itemtype";
         if (($itemtype ==  static::$itemtype)
             || is_subclass_of($itemtype, static::$itemtype)) {
            $condition = $condition_id;
         }
      }
      if (isset($condition)) {
         return "SELECT ".implode(', ', $fields)."
                 FROM `".static::getTable()."`
                 WHERE $condition";
      }
      return '';
   }


   /**
    * Get request cirteria to search for an item
    *
    * @since 9.4
    *
    * @param string  $itemtype Item type
    * @param integer $items_id Item ID
    *
    * @return array|null
   **/
   static function getSQLCriteriaToSearchForItem($itemtype, $items_id) {

      $criteria = [
         'SELECT' => [
            static::getIndexName(),
            static::$items_id . ' AS items_id',
            new \QueryExpression("'" . static::$itemtype . "' AS itemtype")
         ],
         'FROM'   => static::getTable(),
         'WHERE'  => [
            static::$items_id  => $items_id
         ]
      ];

      // Check item 1 type
      $request = false;
      if (preg_match('/^itemtype/', static::$itemtype)) {
         $criteria['WHERE'][static::$itemtype] = $itemtype;
         $request = true;
      } else {
         if (($itemtype ==  static::$itemtype)
             || is_subclass_of($itemtype, static::$itemtype)) {
            $request = true;
         }
      }
      if ($request === true) {
         return $criteria;
      }
      return null;
   }


   /**
    * @since 0.84
   **/
   static function canCreate() {

      if ((static::$rightname) && (!Session::haveRight(static::$rightname, CREATE))) {
         return false;
      }
      return static::canChild('canUpdate');
   }


   /**
    * @since 0.84
   **/
   static function canView() {
      if ((static::$rightname) && (!Session::haveRight(static::$rightname, READ))) {
         return false;
      }
      return static::canChild('canView');
   }


   /**
    * @since 0.84
   **/
   static function canUpdate() {
      if ((static::$rightname) && (!Session::haveRight(static::$rightname, UPDATE))) {
         return false;
      }
      return static::canChild('canUpdate');
   }


   /**
    * @since 0.84
   **/
   static function canDelete() {
      if ((static::$rightname) && (!Session::haveRight(static::$rightname, DELETE))) {
         return false;
      }
      return static::canChild('canUpdate');
   }


   /**
    * @since 0.85
    **/
   static function canPurge() {
      if ((static::$rightname) && (!Session::haveRight(static::$rightname, PURGE))) {
         return false;
      }
      return static::canChild('canUpdate');
   }


   /**
    * @since 0.84
   **/
   function canCreateItem() {
      return $this->canChildItem('canUpdateItem', 'canUpdate');
   }


   /**
    * @since 0.84
   **/
   function canViewItem() {
      return $this->canChildItem('canViewItem', 'canView');
   }


   /**
    * @since 0.84
   **/
   function canUpdateItem() {
      return $this->canChildItem('canUpdateItem', 'canUpdate');
   }


   /**
    * @since 0.84
   **/
   function canDeleteItem() {
      return $this->canChildItem('canUpdateItem', 'canUpdate');
   }


   /**
    * @since 0.84
    *
    * @param $method
   **/
   static function canChild($method) {

      return static::canConnexity($method, static::$checkParentRights, static::$itemtype,
                                  static::$items_id);
   }


   /**
    * @since 0.84
    *
    * @param $methodItem
    * @param $methodNotItem
    *
    * @return boolean
   **/
   function canChildItem($methodItem, $methodNotItem) {

      try {
         return static::canConnexityItem($methodItem, $methodNotItem, static::$checkParentRights,
                                         static::$itemtype, static::$items_id);
      } catch (CommonDBConnexityItemNotFound $e) {
         return !static::$mustBeAttached;
      }
   }


   /**
    * Get the item associated with the current object. Rely on CommonDBConnexity::getItemFromArray()
    *
    * @since 0.84
    *
    * @param $getFromDB   (true by default)
    * @param $getEmpty    (true by default)
    *
    * @return object of the concerned item or false on error
   **/
   function getItem($getFromDB = true, $getEmpty = true) {

      return $this->getConnexityItem(static::$itemtype, static::$items_id,
                                     $getFromDB, $getEmpty);
   }


   /**
    * \brief recursively display the items of this
    *
    * @param array  $recursiveItems    items of the current elements (see recursivelyGetItems())
    * @param string $elementToDisplay  what to display : 'Type', 'Name', 'Link'
   **/
   static function displayRecursiveItems(array $recursiveItems, $elementToDisplay) {

      if ((!is_array($recursiveItems)) || (count($recursiveItems) == 0)) {
         echo __('Item not linked to an object');
         return;
      }

      switch ($elementToDisplay) {
         case 'Type' :
            $masterItem = $recursiveItems[count($recursiveItems) - 1];
            echo $masterItem->getTypeName(1);
            break;

         case 'Name' :
         case 'Link' :
            $items_elements  = [];
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
    * @since 0.84
    *
    * @return array
   **/
   function recursivelyGetItems() {

      $item = $this->getItem();
      if ($item !== false) {
         if ($item instanceof CommonDBChild) {
            return array_merge([$item], $item->recursivelyGetItems());
         }
         return [$item];
      }
      return [];
   }


   /**
    * Get the ID of entity assigned to the object
    *
    * @return integer ID of the entity
   **/
   function getEntityID () {

      // Case of Duplicate Entity info to child
      if (parent::isEntityAssign()) {
         return parent::getEntityID();
      }

      $item = $this->getItem();
      if (($item !== false) && ($item->isEntityAssign())) {

         return $item->getEntityID();

      }
      return -1;
   }


   function isEntityAssign() {

      // Case of Duplicate Entity info to child
      if (parent::isEntityAssign()) {
         return true;
      }

      $item = $this->getItem(false);

      if ($item !== false) {
         return $item->isEntityAssign();
      }

      return false;
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

      $item = $this->getItem(false);

      if ($item !== false) {
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

      $item = $this->getItem();

      if ($item !== false) {
         return $item->isRecursive();
      }

      return false;
   }


   /**
    * @since 0.84
   **/
   function addNeededInfoToInput($input) {

      // is entity missing and forwarding on ?
      if ($this->tryEntityForwarding() && !isset($input['entities_id'])) {
         // Merge both arrays to ensure all the fields are defined for the following checks
         $completeinput = array_merge($this->fields, $input);
         // Set the item to allow parent::prepareinputforadd to get the right item ...
         if ($itemToGetEntity = static::getItemFromArray(static::$itemtype, static::$items_id,
                                                         $completeinput)) {
            if (($itemToGetEntity instanceof CommonDBTM)
                && $itemToGetEntity->isEntityForwardTo(get_called_class())) {

               $input['entities_id']  = $itemToGetEntity->getEntityID();
               $input['is_recursive'] = intval($itemToGetEntity->isRecursive());

            } else {
               // No entity link : set default values
               $input['entities_id']  = 0;
               $input['is_recursive'] = 0;
            }
         }
      }
      return $input;
   }


   /**
    * @since 0.84
   **/
   function prepareInputForAdd($input) {

      if (!is_array($input)) {
         return false;
      }

      // Check item exists
      if (static::$mustBeAttached
          && !$this->getItemFromArray(static::$itemtype, static::$items_id, $input)) {
         return false;
      }

      return $this->addNeededInfoToInput($input);
   }


   /**
    * @since 0.84
   **/
   function prepareInputForUpdate($input) {

      if (!is_array($input)) {
         return false;
      }

      // True if item changed
      if (!parent::checkAttachedItemChangesAllowed($input, [static::$itemtype,
                                                                 static::$items_id])) {
         return false;
      }

      return parent::addNeededInfoToInput($input);
   }


   /**
    * Get the history name of item
    *
    * @param CommonDBTM $item the other item
    * @param string     $case : can be overwrite by object
    *    - 'add' when this CommonDBChild is added (to and item)
    *    - 'update item previous' transfert : this is removed from the old item
    *    - 'update item next' transfert : this is added to the new item
    *    - 'delete' when this CommonDBChild is remove (from an item)
    *
    * @return string the name of the entry for the database (ie. : correctly slashed)
   **/
   function getHistoryNameForItem(CommonDBTM $item, $case) {

      return $this->getNameID(['forceid'    => true,
                                    'additional' => true]);
   }


   /**
    * Actions done after the ADD of the item in the database
    *
    * @return void
   **/
   function post_addItem() {

      if ((isset($this->input['_no_history']) && $this->input['_no_history'])
          || !static::$logs_for_parent) {
         return;
      }

      $item = $this->getItem();

      if (($item !== false)
          && $item->dohistory) {
         $changes = [
            '0',
            '',
            $this->getHistoryNameForItem($item, 'add'),
         ];
         Log::history($item->getID(), $item->getType(), $changes, $this->getType(),
                      static::$log_history_add);
      }
   }


   /**
    * Actions done after the UPDATE of the item in the database
    *
    * @since 0.84
    *
    * @param integer|boolean $history store changes history ?
    *
    * @return void
   **/
   function post_updateItem($history = 1) {

      if ((isset($this->input['_no_history']) && $this->input['_no_history'])
          || !static::$logs_for_parent) {
         return;
      }

      $items_for_log = $this->getItemsForLog(static::$itemtype, static::$items_id);

      // Whatever case : we log the changes
      $oldvalues = $this->oldvalues;
      unset($oldvalues[static::$itemtype]);
      unset($oldvalues[static::$items_id]);
      $item      = $items_for_log['new'];
      if (($item !== false)
          && $item->dohistory) {
         foreach (array_keys($oldvalues) as $field) {
            $changes = $this->getHistoryChangeWhenUpdateField($field);
            if ((!is_array($changes)) || (count($changes) != 3)) {
               continue;
            }
            Log::history($item->getID(), $item->getType(), $changes, $this->getType(),
                         static::$log_history_update);
         }
      }

      if (isset($items_for_log['previous'])) {
         // Have updated the connexity relation

         $prevItem = $items_for_log['previous'];
         $newItem  = $items_for_log['new'];

         if (($prevItem !== false)
             && $prevItem->dohistory) {
            $changes[0] = '0';
            $changes[1] = addslashes($this->getHistoryNameForItem($prevItem, 'update item previous'));
            $changes[2] = '';
            Log::history($prevItem->getID(), $prevItem->getType(), $changes, $this->getType(),
                         static::$log_history_delete);
         }

         if (($newItem !== false)
             && $newItem->dohistory) {
            $changes[0] = '0';
            $changes[1] = '';
            $changes[2] = addslashes($this->getHistoryNameForItem($newItem, 'update item next'));
            Log::history($newItem->getID(), $newItem->getType(), $changes, $this->getType(),
                         static::$log_history_add);
         }
      }
   }

   /**
    * Actions done after the DELETE of the item in the database
    *
    * @return void
   **/
   function post_deleteFromDB() {

      if ((isset($this->input['_no_history']) && $this->input['_no_history'])
          || !static::$logs_for_parent) {
         return;
      }

      $item = $this->getItem();

      if (($item !== false)
          && $item->dohistory) {
         $changes = [
            '0',
         ];

         if (static::$log_history_delete == Log::HISTORY_LOG_SIMPLE_MESSAGE) {
            $changes[1] = '';
            $changes[2] = addslashes($this->getHistoryNameForItem($item, 'delete'));
         } else {
            $changes[1] = addslashes($this->getHistoryNameForItem($item, 'delete'));
            $changes[2] = '';
         }
         Log::history($item->getID(), $item->getType(), $changes, $this->getType(),
                      static::$log_history_delete);
      }
   }


   /**
    *  Actions done when item flag deleted is set to an item
    *
    * @since 0.84
    *
    * @return void
   **/
   function cleanDBonMarkDeleted() {

      if ((isset($this->input['_no_history']) && $this->input['_no_history'])
          || !static::$logs_for_parent) {
         return;
      }

      if ($this->useDeletedToLockIfDynamic()
          && $this->isDynamic()) {
         $item = $this->getItem();

         if (($item !== false)
             && $item->dohistory) {
            $changes = [
               '0',
               addslashes($this->getHistoryNameForItem($item, 'lock')),
               '',
            ];
            Log::history($item->getID(), $item->getType(), $changes, $this->getType(),
                         static::$log_history_lock);
         }
      }
   }


   /**
    * Actions done after the restore of the item
    *
    * @since 0.84
    *
    * @return void
   **/

   function post_restoreItem() {
      if ((isset($this->input['_no_history']) && $this->input['_no_history'])
          || !static::$logs_for_parent) {
         return;
      }

      if ($this->useDeletedToLockIfDynamic()
          && $this->isDynamic()) {
         $item = $this->getItem();

         if (($item !== false)
             && $item->dohistory) {
            $changes = [
               '0',
               '',
               addslashes($this->getHistoryNameForItem($item, 'unlock')),
            ];
            Log::history($item->getID(), $item->getType(), $changes, $this->getType(),
                         static::$log_history_unlock);
         }
      }
   }


   /**
    * get the Javascript "code" to add to the form when clicking on "+"
    *
    * @since 0.84
    *
    * @see showAddChildButtonForItemForm()
    *
    * @param string $field_name         the name of the HTML field inside Item's form
    * @param string $child_count_js_var the name of the javascript variable containing current child
    *                                   number of items
    *
    * @return string
   **/
   static function getJSCodeToAddForItemChild($field_name, $child_count_js_var) {
      return "<input type=\'text\' size=\'40\' ". "name=\'" . $field_name .
             "[-'+$child_count_js_var+']\'>";
   }


   /**
    * display the field of a given child
    *
    * @since 0.84
    *
    * @see showChildsForItemForm()
    *
    * @param boolean $canedit     true if we can edit the child
    * @param string  $field_name  the name of the HTML field inside Item's form
    * @param integer $id          id of the child
    *
    * @return void
   **/
   function showChildForItemForm($canedit, $field_name, $id) {

      if ($this->isNewID($this->getID())) {
         $value = '';
      } else {
         $value = $this->getName();
      }
      $field_name = $field_name."[$id]";
      if ($canedit) {
         echo "<input type='text' size='40' name='$field_name' value='$value'>";
      } else {
         echo "<input type='hidden' name='$field_name' value='$value'>$value";
      }
   }


   /**
    * We can add several single CommonDBChild to a given Item. In such case, we display a "+"
    * button and the fields already entered
    * This method display the "+" button
    *
    * @since 0.84
    *
    * @todo study if we cannot use these methods for the user emails
    * @see showChildsForItemForm(CommonDBTM $item, $field_name)
    *
    * @param CommonDBTM   $item        the item on which to add the current CommenDBChild
    * @param string       $field_name  the name of the HTML field inside Item's form
    * @param boolean|null $canedit     boolean to force rights, NULL to use default behaviour
    * @param boolean      $display     true display or false to return the button HTML code
    *
    *
    * @return void|string the button HTML code if $display is true, void otherwise
   **/
   static function showAddChildButtonForItemForm(CommonDBTM $item, $field_name, $canedit = null,
                                                 $display = true) {

      $items_id = $item->getID();

      if (is_null($canedit)) {
         if ($item->isNewItem()) {
            if (!$item->canCreate()) {
               return false;
            }
            $canedit = $item->canUpdate();
         } else {
            if (!$item->can($items_id, READ)) {
               return false;
            }

            $canedit = $item->can($items_id, UPDATE);
         }
      }

      $result = '';

      if ($canedit) {
         $lower_name         = strtolower(get_called_class());
         $child_count_js_var = 'nb'.$lower_name.'s';
         $div_id             = "add_".$lower_name."_to_".$item->getType()."_".$items_id;

         // Beware : -1 is for the first element added ...
         $result = "&nbsp;<script type='text/javascript'>var $child_count_js_var=2; </script>";
         $result .= "<span id='add".$lower_name."button' class='fa fa-plus pointer'".
              " title=\"".__s('Add')."\"" .
                "\" onClick=\"var row = ".Html::jsGetElementByID($div_id).";
                             row.append('<br>" .
               static::getJSCodeToAddForItemChild($field_name, $child_count_js_var)."');
                            $child_count_js_var++;\"
               ><span class='sr-only'>" . __s('Add')  . "</span></span>";
      }
      if ($display) {
         echo $result;
      } else {
         return $result;
      }
   }


   /**
    * We can add several single CommonDBChild to a given Item. In such case, we display a "+"
    * button and the fields already entered.
    * This method display the fields
    *
    * @since 0.84
    *
    * @todo study if we cannot use these methods for the user emails
    * @see showAddChildButtonForItemForm()
    *
    * @param CommonDBTM   $item        the item on which to add the current CommenDBChild
    * @param string       $field_name  the name of the HTML field inside Item's form
    * @param boolean|null $canedit     boolean to force rights, NULL to use default behaviour
    *
    * @return void
   **/
   static function showChildsForItemForm(CommonDBTM $item, $field_name, $canedit = null) {
      global $DB;

      $items_id = $item->getID();

      if (is_null($canedit)) {
         if ($item->isNewItem()) {
            if (!$item->canCreate()) {
               return false;
            }
            $canedit = $item->canUpdate();
         } else {
            if (!$item->can($items_id, READ)) {
               return false;
            }

            $canedit = $item->can($items_id, UPDATE);
         }
      }

      $lower_name = strtolower(get_called_class());
      $div_id     = "add_".$lower_name."_to_".$item->getType()."_".$items_id;

      // To be sure not to load bad datas from this table
      if ($items_id == 0) {
         $items_id = -99;
      }

      $query = [
         'FROM'   => static::getTable(),
         'WHERE'  => [
            static::$items_id => $item->getID()
         ]
      ];

      if (preg_match('/^itemtype/', static::$itemtype)) {
         $query['WHERE']['itemtype'] = $item->getType();
      }

      $current_item = new static();

      if ($current_item->maybeDeleted()) {
         $query['WHERE']['is_deleted'] = 0;
      }

      $iterator = $DB->request($query);
      $count = 0;
      while ($data = $iterator->next()) {

         $current_item->fields = $data;

         if ($count) {
            echo '<br>';
         }
         $count++;

         $current_item->showChildForItemForm($canedit, $field_name, $current_item->getID());

      }

      if ($canedit) {
         echo "<div id='$div_id'>";
         // No Child display field
         if ($count == 0) {
            $current_item->getEmpty();
            $current_item->showChildForItemForm($canedit, $field_name, -1);
         }
         echo "</div>";
      }
   }


   /**
    * Affect a CommonDBChild to a given item. By default, unaffect it
    *
    * @param $id          integer   the id of the CommonDBChild to affect
    * @param $items_id    integer   the id of the new item (default 0)
    * @param $itemtype    string    the type of the new item (default '')
    *
    * @return boolean : true on success
   **/
   function affectChild($id, $items_id = 0, $itemtype = '') {

      $input = [static::getIndexName() => $id,
                     static::$items_id      => $items_id];

      if (preg_match('/^itemtype/', static::$itemtype)) {
         $input[static::$itemtype] = $itemtype;
      }

      return $this->update($input);
   }
}
