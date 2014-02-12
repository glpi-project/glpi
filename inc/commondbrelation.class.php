<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Common DataBase Relation Table Manager Class
abstract class CommonDBRelation extends CommonDBConnexity {

   // Item 1 information
   // * definition
   static public $itemtype_1; // Type ref or field name (must start with itemtype)
   static public $items_id_1; // Field name
   // * entity inheritance
   static public $take_entity_1          = true ;
   // * rights
   static public $checkItem_1_Rights     = self::HAVE_SAME_RIGHT_ON_ITEM ;
   static public $mustBeAttached_1       = true;
   // * log
   static public $logs_for_item_1        = true;
   static public $log_history_1_add      = Log::HISTORY_ADD_RELATION;
   static public $log_history_1_update   = Log::HISTORY_UPDATE_RELATION;
   static public $log_history_1_delete   = Log::HISTORY_DEL_RELATION;
   static public $log_history_1_lock     = Log::HISTORY_LOCK_RELATION;
   static public $log_history_1_unlock   = Log::HISTORY_UNLOCK_RELATION;

   // Item 2 information
   // * definition
   static public $itemtype_2; // Type ref or field name (must start with itemtype)
   static public $items_id_2; // Field name
   // * entity inheritance
   static public $take_entity_2          = false ;
   // * rights
   static public $checkItem_2_Rights     = self::HAVE_SAME_RIGHT_ON_ITEM;
   static public $mustBeAttached_2       = true;
   // * log
   static public $logs_for_item_2        = true;
   static public $log_history_2_add      = Log::HISTORY_ADD_RELATION;
   static public $log_history_2_update   = Log::HISTORY_UPDATE_RELATION;
   static public $log_history_2_delete   = Log::HISTORY_DEL_RELATION;
   static public $log_history_2_lock     = Log::HISTORY_LOCK_RELATION;
   static public $log_history_2_unlock   = Log::HISTORY_UNLOCK_RELATION;

   // Relation between items to check
   /// If both items must be checked for rights (default is only one)
   static public $checkAlwaysBothItems   = false;
   /// If both items must be in viewable each other entities
   static public $check_entity_coherency = true;

   var $no_form_page = true;


   /**
    * @since version 0.84
    *
    * @param $itemtype
    * @param $items_id
    *
    * @return string
   **/
   static function getSQLRequestToSearchForItem($itemtype, $items_id) {

      $conditions = array();
      $fields     = array('`'.static::getIndexName().'`');

      // Check item 1 type
      $condition_id_1 = "`".static::$items_id_1."` = '$items_id'";
      $fields[]       = "`".static::$items_id_1."` as items_id_1";
      if (preg_match('/^itemtype/', static::$itemtype_1)) {
         $fields[]    = "`".static::$itemtype_1."` AS itemtype_1";
         $condition_1 = "($condition_id_1 AND `".static::$itemtype_1."` = '$itemtype')";
      } else {
         $fields[] = "'".static::$itemtype_1."' AS itemtype_1";
         if (($itemtype ==  static::$itemtype_1)
             || is_subclass_of($itemtype,  static::$itemtype_1)) {
            $condition_1 = $condition_id_1;
         }
      }
      if (isset($condition_1)) {
         $conditions[] = $condition_1;
         $fields[]     = "IF($condition_1, 1, 0) AS is_1";
      } else {
         $fields[] = "0 AS is_1";
      }


      // Check item 2 type
      $condition_id_2 = "`".static::$items_id_2."` = '$items_id'";
      $fields[]       = "`".static::$items_id_2."` as items_id_2";
      if (preg_match('/^itemtype/', static::$itemtype_2)) {
         $fields[]    = "`".static::$itemtype_2."` AS itemtype_2";
         $condition_2 = "($condition_id_2 AND `".static::$itemtype_2."` = '$itemtype')";
      } else {
         $fields[] = "'".static::$itemtype_2."' AS itemtype_2";
         if (($itemtype ==  static::$itemtype_2)
             || is_subclass_of($itemtype,  static::$itemtype_2)) {
            $condition_2 = $condition_id_2;
         }
      }
      if (isset($condition_2)) {
         $conditions[] = $condition_2;
         $fields[]     = "IF($condition_2, 2, 0) AS is_2";
      } else {
         $fields[] = "0 AS is_2";
      }

      if (count($conditions) != 0) {
         return "SELECT ".implode(', ', $fields)."
                 FROM `".static::getTable()."`
                 WHERE ".implode(' OR ', $conditions)."";
      }
      return '';
   }


   /**
    * @since version 0.84
    *
    * @param $item            CommonDBTM object
    * @param $relations_id    (default NULL)
   **/
   static function getOpposite(CommonDBTM $item, &$relations_id=NULL) {
      return static::getOppositeByTypeAndID($item->getType(), $item->getID(), $relations_id);
   }

   /**
    * @since version 0.84
    *
    * @param $itemtype        Type of the item to search for its opposite
    * @param $items_id        ID of the item to search for its opposite
    * @param $relations_id    (default NULL)
    **/
   static function getOppositeByTypeAndID($itemtype, $items_id, &$relations_id=NULL) {
      global $DB;

      if ($items_id < 0) {
         return false;
      }

      $query = self::getSQLRequestToSearchForItem($itemtype, $items_id);

      if (!empty($query)) {
         $result = $DB->query($query);
         if ($DB->numrows($result) == 1) {
            $line = $DB->fetch_assoc($result);
            if ($line['is_1'] == $line['is_2']) {
               return false;
            }
            if ($line['is_1'] == 0) {
               $opposites_id = $line['items_id_1'];
               $oppositetype = $line['itemtype_1'];
            }
            if ($line['is_2'] == 0) {
               $opposites_id = $line['items_id_2'];
               $oppositetype = $line['itemtype_2'];
            }
            if ((isset($oppositetype)) && (isset($opposites_id))) {
               $opposite = getItemForItemtype($oppositetype);
               if ($opposite !== false) {
                  if ($opposite->getFromDB($opposites_id)) {
                     if (!is_null($relations_id)) {
                        $relations_id = $line[static::getIndexName()];
                     }
                     return $opposite;
                  }
                  unset($opposite);
               }
            }
         }
      }
      return false;
   }


   /**
    * @since version 0.84
    *
    * @param $number
    *
    * @return boolean
   **/
   function getOnePeer($number) {

      if ($number == 0) {
         $itemtype = static::$itemtype_1;
         $items_id = static::$items_id_1;
      } else if ($number == 1) {
         $itemtype = static::$itemtype_2;
         $items_id = static::$items_id_2;
      } else {
         return false;
      }
      return $this->getConnexityItem($itemtype, $items_id);
   }


  /**
    * Get link object between 2 items
    *
    * @since version 0.84
    *
    * @param $item1 object 1
    * @param $item2 object 2
    *
    * @return boolean founded ?
   **/
   function getFromDBForItems(CommonDBTM $item1, CommonDBTM $item2) {

      // Check items ID
      if (($item1->getID() < 0) || ($item2->getID() < 0)) {
         return false;
      }

      $wheres = array();
      $wheres[] = "`".static::$items_id_1."` = '".$item1->getID()."'";
      $wheres[] = "`".static::$items_id_2."` = '".$item2->getID()."'";

      // Check item 1 type
      if (preg_match('/^itemtype/', static::$itemtype_1)) {
         $wheres[] = "`".static::$itemtype_1."` = '".$item1->getType()."'";
      } else if (!is_a($item1, static::$itemtype_1)) {
         return false;
      }

      // Check item 1 type
      if (preg_match('/^itemtype/', static::$itemtype_2)) {
         $wheres[] = "`".static::$itemtype_2."` = '".$item2->getType()."'";
      } else if (!is_a($item2, static::$itemtype_2)) {
         return false;
      }
      return $this->getFromDBByQuery("WHERE ".implode(' AND ', $wheres));
   }


   /**
    * Get search function for the class
    *
    * @return array of search option
   **/
   function getSearchOptions() {

      $tab = array();
      $tab['common']           = __('Characteristics');

      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'id';
      $tab[2]['name']          = __('ID');
      $tab[2]['massiveaction'] = false;
      $tab[2]['datatype']      = 'number';

      if (!preg_match('/^itemtype/', static::$itemtype_1)) {
         $tab[3]['table']         = getTableForItemType(static::$itemtype_1);
         $tab[3]['field']         = static::$items_id_1;
         $tab[3]['name']          = call_user_func(array(static::$itemtype_1, 'getTypeName'));
         $tab[3]['datatype']      = 'text';
         $tab[3]['massiveaction'] = false;
      }

      if (!preg_match('/^itemtype/', static::$itemtype_2)) {
         $tab[4]['table']         = getTableForItemType(static::$itemtype_2);
         $tab[4]['field']         = static::$items_id_2;
         $tab[4]['name']          = call_user_func(array(static::$itemtype_2, 'getTypeName'));
         $tab[4]['datatype']      = 'text';
         $tab[4]['massiveaction'] = false;
      }

      return $tab;
   }


   /**
    * Specific check for check attach for relation 2
    *
    * @since version 0.84
    *
    * @param $input Array of data to be added
    *
    * @return boolean
   **/
   function isAttach2Valid(Array &$input) {
      return false;
   }


   /**
    * Specific check for check attach for relation 1
    *
    * @since version 0.84
    *
    * @param $input Array of data to be added
    *
    * @return boolean
   **/
   function isAttach1Valid(Array &$input) {
      return false;
   }


   /**
    * @since version 0.84
    *
    * @param $method
    * @param $forceCheckBoth boolean force check both items(false by default)
    *
    * @return boolean
   **/
   static function canRelation($method, $forceCheckBoth=false) {

      $can1 = static::canConnexity($method, static::$checkItem_1_Rights, static::$itemtype_1,
                                   static::$items_id_1);
      $can2 = static::canConnexity($method, static::$checkItem_2_Rights, static::$itemtype_2,
                                   static::$items_id_2);

      /// Check only one if SAME RIGHT for both items and not force checkBoth
      if (((static::HAVE_SAME_RIGHT_ON_ITEM == static::$checkItem_1_Rights)
           && (static::HAVE_SAME_RIGHT_ON_ITEM == static::$checkItem_2_Rights))
          && !$forceCheckBoth) {
         if ($can1) {
            // Can view the second one ?
            if (!static::canConnexity($method, static::HAVE_VIEW_RIGHT_ON_ITEM, static::$itemtype_2,
                                      static::$items_id_2)) {
               return false;
            }
            return true;
         } else if ($can2) {
            // Can view the first one ?
            if (!static::canConnexity($method, static::HAVE_VIEW_RIGHT_ON_ITEM, static::$itemtype_1,
                                      static::$items_id_1)) {
               return false;
            }
            return true;
         } else {
            // No item have right
            return false;
         }
      }

      return ($can1 && $can2);

   }


   /**
    * @since version 0.84
    *
    * @param $method
    * @param $methodNotItem
    * @param $check_entity            (true by default)
    * @param $forceCheckBoth boolean  force check both items (false by default)
    *
    * @return boolean
   **/
   function canRelationItem($method, $methodNotItem, $check_entity=true, $forceCheckBoth=false) {

      $OneWriteIsEnough = (!$forceCheckBoth
                           && ((static::HAVE_SAME_RIGHT_ON_ITEM == static::$checkItem_1_Rights)
                               || (static::HAVE_SAME_RIGHT_ON_ITEM == static::$checkItem_2_Rights)));

      try {
         $item1 = NULL;
         $can1  = $this->canConnexityItem($method, $methodNotItem, static::$checkItem_1_Rights,
                                          static::$itemtype_1, static::$items_id_1, $item1);
         if ($OneWriteIsEnough) {
            $view1 = $this->canConnexityItem($method, $methodNotItem,
                                             static::HAVE_VIEW_RIGHT_ON_ITEM, static::$itemtype_1,
                                             static::$items_id_1, $item1);
         }
      } catch (CommonDBConnexityItemNotFound $e) {
         if (static::$mustBeAttached_1 && !static::isAttach1Valid($this->fields)) {
            return false;
         }
         $can1         = true;
         $view1        = true;
         $check_entity = false; // If no item, then, we cannot check entities
      }

      try {
         $item2 = NULL;
         $can2  = $this->canConnexityItem($method, $methodNotItem, static::$checkItem_2_Rights,
                                          static::$itemtype_2, static::$items_id_2, $item2);
         if ($OneWriteIsEnough) {
            $view2 = $this->canConnexityItem($method, $methodNotItem,
                                             static::HAVE_VIEW_RIGHT_ON_ITEM, static::$itemtype_2,
                                             static::$items_id_2, $item2);
         }
      } catch (CommonDBConnexityItemNotFound $e) {
         if (static::$mustBeAttached_2 && !static::isAttach2Valid($this->fields)) {
            return false;
         }
         $can2         = true;
         $view2        = true;
         $check_entity = false; // If no item, then, we cannot check entities
      }


      if ($OneWriteIsEnough) {
         if ((!$can1 && !$can2)
             || ($can1 && !$view2)
             || ($can2 && !$view1)) {
            return false;
         }
      } else {
         if (!$can1 || !$can2) {
            return false;
         }
      }


      // Check coherency of entities
      if ($check_entity && static::$check_entity_coherency) {

         // If one of both extremity is not valid => not allowed !
         // (default is only to check on create and update not for view and delete)
         if ((!$item1 instanceof CommonDBTM)
             || (!$item2 instanceof CommonDBTM)) {
            return false;
         }
         if ($item1->isEntityAssign() && $item2->isEntityAssign()) {
            $entity1 = $item1->getEntityID();
            $entity2 = $item2->getEntityID();

            if ($entity1 == $entity2) {
               return true;
            }
            if (($item1->isRecursive())
                && in_array($entity1, getAncestorsOf("glpi_entities", $entity2))) {
               return true;
            }
            if (($item2->isRecursive())
                && in_array($entity2, getAncestorsOf("glpi_entities", $entity1))) {
               return true;
            }
            return false;
         }
      }

      return true;
   }


   /**
    * @since version 0.84
   **/
   static function canCreate() {
      return static::canRelation('canUpdate', static::$checkAlwaysBothItems);
   }

   /**
    * @since version 0.84
   **/
   static function canView() {
      // Always both checks for view
      return static::canRelation('canView', true);
   }


   /**
    * @since version 0.84
   **/
   static function canUpdate() {
      return static::canRelation('canUpdate', static::$checkAlwaysBothItems);
   }


   /**
    * @since version 0.84
   **/
   static function canDelete() {
      return static::canRelation('canUpdate', static::$checkAlwaysBothItems);
   }


   /**
    * @since version 0.84
   **/
   function canCreateItem() {

      return $this->canRelationItem('canUpdateItem', 'canUpdate', true,
                                    static::$checkAlwaysBothItems);
   }


   /**
    * @since version 0.84
   **/
   function canViewItem() {
      return $this->canRelationItem('canViewItem', 'canView', false, true);
   }


   /**
    * @since version 0.84
   **/
   function canUpdateItem() {

      return $this->canRelationItem('canUpdateItem', 'canUpdate', true,
                                    static::$checkAlwaysBothItems);
   }


   /**
    * @since version 0.84
   **/
   function canDeleteItem() {

      return $this->canRelationItem('canUpdateItem', 'canUpdate', false,
                                    static::$checkAlwaysBothItems);
   }


   /**
    * @since version 0.84
   **/
   function addNeededInfoToInput($input) {

      // is entity missing and forwarding on ?
      if ($this->tryEntityForwarding() && !isset($input['entities_id'])) {
         // Merge both arrays to ensure all the fields are defined for the following checks
         $completeinput = array_merge($this->fields, $input);

         $itemToGetEntity = false;
         // Set the item to allow parent::prepareinputforadd to get the right item ...
         if (static::$take_entity_1) {
            $itemToGetEntity = static::getItemFromArray(static::$itemtype_1, static::$items_id_1,
                                                        $completeinput);
         } else if (static::$take_entity_2) {
            $itemToGetEntity = static::getItemFromArray(static::$itemtype_2, static::$items_id_2,
                                                        $completeinput);
         }

         // Set the item to allow parent::prepareinputforadd to get the right item ...
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
      return $input;
   }


   /**
    * @since version 0.84
    *
    * @param $input
   **/
   function prepareInputForAdd($input) {

      if (!is_array($input)) {
         return false;
      }

      return $this->addNeededInfoToInput($input);
   }


   /**
    * @since version 0.84
   **/
   function prepareInputForUpdate($input) {

      if (!is_array($input)) {
         return false;
      }

      // True if item changed
      if (!parent::checkAttachedItemChangesAllowed($input, array(static::$itemtype_1,
                                                                 static::$items_id_1,
                                                                 static::$itemtype_2,
                                                                 static::$items_id_2))) {
         return false;
      }

      return parent::addNeededInfoToInput($input);
   }


   /**
    * Get the history name of first item
    *
    * @since version 0.84
    *
    * @param $item    CommonDBTM object   the other item (ie. : $item2)
    * @param $case : can be overwrite by object
    *              - 'add' when this CommonDBRelation is added (to and item)
    *              - 'update item previous' transfert : this is removed from the old item
    *              - 'update item next' transfert : this is added to the new item
    *              - 'delete' when this CommonDBRelation is remove (from an item)
    *
    * @return (string) the name of the entry for the database (ie. : correctly slashed)
   **/
   function getHistoryNameForItem1(CommonDBTM $item, $case) {

      return $item->getNameID(array('forceid'    => true,
                                    'additional' => true));
   }


   /**
    * Get the history name of second item
    *
    * @since version 0.84
    *
    * @param $item the other item (ie. : $item1)
    * @param $case : can be overwrite by object
    *              - 'add' when this CommonDBRelation is added (to and item)
    *              - 'update item previous' transfert : this is removed from the old item
    *              - 'update item next' transfert : this is added to the new item
    *              - 'delete' when this CommonDBRelation is remove (from an item)
    *
    * @return (string) the name of the entry for the database (ie. : correctly slashed)
   **/
   function getHistoryNameForItem2(CommonDBTM $item, $case) {

      return $item->getNameID(array('forceid'    => true,
                                    'additional' => true));
   }


   /**
    * Actions done after the ADD of the item in the database
    *
    * @return nothing
   **/
   function post_addItem() {

      if ((isset($this->input['_no_history']) && $this->input['_no_history'])
          || (!static::$logs_for_item_1
              && !static::$logs_for_item_2)) {
         return;
      }

      $item1 = $this->getConnexityItem(static::$itemtype_1, static::$items_id_1);
      $item2 = $this->getConnexityItem(static::$itemtype_2, static::$items_id_2);

      if (($item1 !== false)
          && ($item2 !== false)) {

         if ($item1->dohistory
             && static::$logs_for_item_1) {
            $changes[0] = '0';
            $changes[1] = "";
            $changes[2] = addslashes($this->getHistoryNameForItem1($item2, 'add'));
            Log::history($item1->getID(), $item1->getType(), $changes, $item2->getType(),
                         static::$log_history_1_add);
         }

         if ($item2->dohistory && static::$logs_for_item_2) {
            $changes[0] = '0';
            $changes[1] = "";
            $changes[2] = addslashes($this->getHistoryNameForItem2($item1, 'add'));
            Log::history($item2->getID(), $item2->getType(), $changes, $item1->getType(),
                         static::$log_history_2_add);
         }
      }
   }


    /**
    * Actions done after the UPDATE of the item in the database
    *
    * @since version 0.84
    *
    * @param $history store changes history ? (default 1)
    *
    * @return nothing
   **/
   function post_updateItem($history=1) {

      if ((isset($this->input['_no_history']) && $this->input['_no_history'])
          || (!static::$logs_for_item_1
              && !static::$logs_for_item_2)) {
         return;
      }

      $items_1 = $this->getItemsForLog(static::$itemtype_1, static::$items_id_1);
      $items_2 = $this->getItemsForLog(static::$itemtype_2, static::$items_id_2);

      $new1 = $items_1['new'];
      if (isset($items_1['previous'])) {
         $previous1 = $items_1['previous'];
      } else {
         $previous1 = $items_1['new'];
      }

      $new2 = $items_2['new'];
      if (isset($items_2['previous'])) {
         $previous2 = $items_2['previous'];
      } else {
         $previous2 = $items_2['new'];
      }

      $oldvalues = $this->oldvalues;
      unset($oldvalues[static::$itemtype_1]);
      unset($oldvalues[static::$items_id_1]);
      unset($oldvalues[static::$itemtype_2]);
      unset($oldvalues[static::$items_id_2]);

      foreach ($oldvalues as $field => $value) {
         $changes = $this->getHistoryChangeWhenUpdateField($field);
         if ((!is_array($changes)) || (count($changes) != 3)) {
            continue;
         }
         /// TODO clean management of it
         if ($new1 && $new1->dohistory
             && static::$logs_for_item_1) {
            Log::history($new1->getID(), $new1->getType(), $changes,
                         get_called_class().'#'.$field, static::$log_history_1_update);
         }
         if ($new2 && $new2->dohistory
             && static::$logs_for_item_2) {
            Log::history($new2->getID(), $new2->getType(), $changes,
                         get_called_class().'#'.$field, static::$log_history_2_update);
         }
      }

      if (isset($items_1['previous']) || isset($items_2['previous'])) {

         if ($previous2
             && $previous1 && $previous1->dohistory
             && static::$logs_for_item_1) {
            $changes[0] = '0';
            $changes[1] = addslashes($this->getHistoryNameForItem1($previous2,
                                     'update item previous'));
            $changes[2] = "";
            Log::history($previous1->getID(), $previous1->getType(), $changes,
                         $previous2->getType(), static::$log_history_1_delete);
         }

         if ($previous1
             && $previous2 && $previous2->dohistory
             && static::$logs_for_item_2) {
            $changes[0] = '0';
            $changes[1] = addslashes($this->getHistoryNameForItem2($previous1,
                                     'update item previous'));
            $changes[2] = "";
            Log::history($previous2->getID(), $previous2->getType(), $changes,
                         $previous1->getType(), static::$log_history_2_delete);
         }

         if ($new2
             && $new1 && $new1->dohistory
             && static::$logs_for_item_1) {
            $changes[0] = '0';
            $changes[1] = "";
            $changes[2] = addslashes($this->getHistoryNameForItem1($new2, 'update item next'));
            Log::history($new1->getID(), $new1->getType(), $changes, $new2->getType(),
                         static::$log_history_1_add);
         }

         if ($new1
             && $new2 && $new2->dohistory
             && static::$logs_for_item_2) {
            $changes[0] = '0';
            $changes[1] = "";
            $changes[2] = addslashes($this->getHistoryNameForItem2($new1, 'update item next'));
            Log::history($new2->getID(), $new2->getType(), $changes, $new1->getType(),
                         static::$log_history_2_add);
         }
      }
   }


   /**
    *  Actions done when item flag deleted is set to an item
    *
    * @since version 0.84
    *
    * @return nothing
   **/
   function cleanDBonMarkDeleted() {

      if ((isset($this->input['_no_history']) && $this->input['_no_history'])
          || (!static::$logs_for_item_1
              && !static::$logs_for_item_2)) {
         return;
      }

      if ($this->useDeletedToLockIfDynamic()
          && $this->isDynamic()) {
         $item1 = $this->getConnexityItem(static::$itemtype_1, static::$items_id_1);
         $item2 = $this->getConnexityItem(static::$itemtype_2, static::$items_id_2);

         if (($item1 !== false)
             && ($item2 !== false)) {
            if ($item1->dohistory
                && static::$logs_for_item_1) {
               $changes[0] = '0';
               $changes[1] = addslashes($this->getHistoryNameForItem1($item2, 'lock'));
               $changes[2] = "";

               Log::history($item1->getID(), $item1->getType(), $changes, $item2->getType(),
                            static::$log_history_1_lock);
            }

            if ($item2->dohistory
               && static::$logs_for_item_2) {
               $changes[0] = '0';
               $changes[1] = addslashes($this->getHistoryNameForItem2($item1, 'lock'));
               $changes[2] = "";
               Log::history($item2->getID(), $item2->getType(), $changes, $item1->getType(),
                            static::$log_history_2_lock);
            }
         }

      }
   }


   /**
    * Actions done after the restore of the item
    *
    * @since version 0.84
    *
    * @return nothing
   **/
   function post_restoreItem() {

      if ((isset($this->input['_no_history']) && $this->input['_no_history'])
          || (!static::$logs_for_item_1
              && !static::$logs_for_item_2)) {
         return;
      }

      if ($this->useDeletedToLockIfDynamic()
          && $this->isDynamic()) {
         $item1 = $this->getConnexityItem(static::$itemtype_1, static::$items_id_1);
         $item2 = $this->getConnexityItem(static::$itemtype_2, static::$items_id_2);

         if (($item1 !== false)
             && ($item2 !== false)) {
            if ($item1->dohistory
                && static::$logs_for_item_1) {
               $changes[0] = '0';
               $changes[1] = '';
               $changes[2] = addslashes($this->getHistoryNameForItem1($item2, 'unlock'));

               Log::history($item1->getID(), $item1->getType(), $changes, $item2->getType(),
                            static::$log_history_1_unlock);
            }

            if ($item2->dohistory
                && static::$logs_for_item_2) {
               $changes[0] = '0';
               $changes[1] = '';
               $changes[2] = addslashes($this->getHistoryNameForItem2($item1, 'unlock'));
               Log::history($item2->getID(), $item2->getType(), $changes, $item1->getType(),
                            static::$log_history_2_unlock);
            }
         }

      }
   }


  /**
    * Actions done after the DELETE of the item in the database
    *
    * @since version 0.84
    *
    *@return nothing
   **/
   function post_deleteFromDB() {

      if ((isset($this->input['_no_history']) && $this->input['_no_history'])
          || (!static::$logs_for_item_1
              && !static::$logs_for_item_2)) {
         return;
      }

      $item1 = $this->getConnexityItem(static::$itemtype_1, static::$items_id_1);
      $item2 = $this->getConnexityItem(static::$itemtype_2, static::$items_id_2);

      if (($item1 !== false)
          && ($item2 !== false)) {

         if ($item1->dohistory
             && static::$logs_for_item_1) {
            $changes[0] = '0';
            $changes[1] = addslashes($this->getHistoryNameForItem1($item2, 'delete'));
            $changes[2] = "";

            Log::history($item1->getID(), $item1->getType(), $changes, $item2->getType(),
                         static::$log_history_1_delete);
         }

         if ($item2->dohistory
             && static::$logs_for_item_2) {
            $changes[0] = '0';
            $changes[1] = addslashes($this->getHistoryNameForItem2($item1, 'delete'));
            $changes[2] = "";
            Log::history($item2->getID(), $item2->getType(), $changes, $item1->getType(),
                         static::$log_history_2_delete);
         }
      }

   }


  /**
    * @since version 0.84
    *
    * @param $itemtype
    * @param $base                  HTMLTableBase object
    * @param $super                 HTMLTableSuperHeader object (default NULL)
    * @param $father                HTMLTableHeader object (default NULL)
    * @param $options      array
   **/
   static function getHTMLTableHeader($itemtype, HTMLTableBase $base,
                                      HTMLTableSuperHeader $super=NULL,
                                      HTMLTableHeader $father=NULL, array $options=array()) {

      if (isset($options[get_called_class().'_side'])) {
         $side = $options[get_called_class().'_side'];
      } else {
         $side = 0;
      }
      $oppositetype = '';
      if (($side == 1)
          || ($itemtype == static::$itemtype_1)) {
         $oppositetype = static::$itemtype_2;
      }
      if (($side == 2)
          || ($itemtype == static::$itemtype_2)) {
         $oppositetype = static::$itemtype_1;
      }
      if (class_exists($oppositetype)
          && method_exists($oppositetype, 'getHTMLTableHeader')) {
         $oppositetype::getHTMLTableHeader(get_called_class(), $base, $super, $father, $options);
      }
   }


   /**
    * @since version 0.84
    *
    * @param $row                HTMLTableRow object (default NULL)
    * @param $item               CommonDBTM object (default NULL)
    * @param $father             HTMLTableCell object (default NULL)
    * @param $options   array
   **/
   static function getHTMLTableCellsForItem(HTMLTableRow $row=NULL, CommonDBTM $item=NULL,
                                            HTMLTableCell $father=NULL, array $options=array()) {
      global $DB, $CFG_GLPI;

      if (empty($item)) {
         if (empty($father)) {
            return;
         }
         $item = $father->getItem();
      }

      $query = self::getSQLRequestToSearchForItem($item->getType(), $item->getID());
      if (!empty($query)) {

         $relation = new static();
         foreach ($DB->request($query) as $line) {

            if ($line['is_1'] != $line['is_2']) {
               if ($line['is_1'] == 0) {
                  $options['items_id'] = $line['items_id_1'];
                  $oppositetype        = $line['itemtype_1'];
               } else {
                  $options['items_id'] = $line['items_id_2'];
                  $oppositetype        = $line['itemtype_2'];
               }
               if (class_exists($oppositetype)
                   && method_exists($oppositetype, 'getHTMLTableCellsForItem')
                   && $relation->getFromDB($line[static::getIndexName()])) {
                  $oppositetype::getHTMLTableCellsForItem($row, $relation, $father, $options);
               }
            }
         }
      }
   }

}
?>
