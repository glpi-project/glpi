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


/**
 * @since version 0.84
**/
class CommonDBConnexityItemNotFound extends Exception {

}





/**
 * Common DataBase Connexity Table Manager Class
 * This class factorize code for CommonDBChild and CommonDBRelation. Both classes themselves
 * factorize and normalize the behaviour of all Child and Relations.
 * As such, several elements are directly managed by these two classes since 0.84 :
 * - Check:  all can* methods (canCreate, canUpdate, canViewItem, canDeleteItem ...) are
 *           defined.
 * - Update: when we try to update an attached element, we check if we change its parent item(s).
 *           If we change its parent(s), then we check if we can delete the item with previous
 *           parent(s) (cf. "check" before) AND we can create the item with the new parent(s).
 * - Entity: Entity is automatically setted or updated when setting or changing an attached item.
 *           Thus, you don't have any more to worry about entities.
 *            (May be disable using $disableAutoEntityForwarding)
 * - Log:    when we create, update or delete an item, we update its parent(s)'s histories to
 *           notify them of the creation, update or deletion
 * - Flying items : some items can be on the stock. For instance, before beeing plugged inside a
 *                  computer, an Item_DeviceProcessor can be without any parent. It is now possible
 *                  to define such items and transfer them from parent to parent.
 *
 * The aim of the new check is that the rights on a Child or a Relation are driven by the
 * parent(s): you can create, delete or update the item if and only if you can update its parent(s);
 * you can view the item if and only if you can view its parent(s). Beware that it differs from the
 * default behaviour of CommonDBTM: if you don't define canUpdate or canDelete, then it checks
 * canCreate and by default canCreate returns false (thus, if you don't do anything, you don't have
 * any right). A side effect is that if you define specific rights (see NetworkName::canCreate())
 * for your classes you must define all rights (canCreate, canView, canUpdate and canDelete).
 *
 * @warning You have to care of calling CommonDBChild or CommonDBRelation methods if you override
 * their methods (for instance: call parent::prepareInputForAdd($input) if you define
 * prepareInputForAdd). You can find an example with UserEmail::prepareInputForAdd($input).
 *
 * @since 0.84
**/
abstract class CommonDBConnexity extends CommonDBTM {

   const DONT_CHECK_ITEM_RIGHTS  = 1; // Don't check the parent => always can*Child
   const HAVE_VIEW_RIGHT_ON_ITEM = 2; // canXXXChild = true if parent::canView == true
   const HAVE_SAME_RIGHT_ON_ITEM = 3; // canXXXChild = true if parent::canXXX == true

   static public $canDeleteOnItemClean          = true;
   /// Disable auto forwarding information about entities ?
   static public $disableAutoEntityForwarding   = false;

   /**
    * Return the SQL request to get all the connexities corresponding to $itemtype[$items_id]
    * That is used by cleanDBOnItem : the only interesting field is static::getIndexName()
    * But CommonDBRelation also use it to get more complex result
    *
    * @param $itemtype the type of the item to look for
    * @param $items_id the id of the item to look for
    *
    * @return the SQL request of '' if it is not possible
    **/
   static function getSQLRequestToSearchForItem($itemtype, $items_id) {
      return '';
   }


   /**
    * Clean the Connecity Table when item of the relation is deleted
    * To be call from the cleanDBonPurge of each Item class
    *
    * @param $itemtype  type of the item
    * @param $items_id   id of the item
   **/
   function cleanDBonItemDelete ($itemtype, $items_id) {
      global $DB;

      $query = static::getSQLRequestToSearchForItem($itemtype, $items_id);
      if (!empty($query)) {
         $input = array('_no_history'     => true,
                        '_no_notif'       => true);

         foreach ($DB->request($query) as $data) {
            $input[$this->getIndexName()] = $data[$this->getIndexName()];
            $this->delete($input, 1);
         }
      }
   }


   /**
    * get associated item (defined by $itemtype and $items_id)
    *
    * @see CommonDBConnexity::getItemFromArray()
    *
    * @param $itemtype              the name of the field of the type of the item to get
    * @param $items_id              the name of the field of the id of the item to get
    * @param $getFromDB   boolean   do we have to load the item from the DB ? (true by default)
    * @param $getEmpty    boolean   else : do we have to load an empty item ? (true by default)
    *
    * @return the item or false if we cannot load the item
   **/
   function getConnexityItem($itemtype, $items_id, $getFromDB=true, $getEmpty=true) {
      return static::getItemFromArray($itemtype, $items_id, $this->fields, $getFromDB, $getEmpty);
   }


   /**
    * get associated item (defined by $itemtype and $items_id)
    *
    * @param $itemtype           the name of the field of the type of the item to get
    * @param $items_id           the name of the field of the id of the item to get
    * @param $array      array   the array in we have to search ($input, $this->fields ...)
    * @param $getFromDB  boolean do we have to load the item from the DB ? (true by default)
    * @param $getEmpty   boolean else : do we have to load an empty item ? (true by default)
    *
    * @return the item or false if we cannot load the item
   **/
   static function getItemFromArray($itemtype, $items_id, array $array,
                                    $getFromDB=true, $getEmpty=true) {

      if (preg_match('/^itemtype/', $itemtype)) {
         if (isset($array[$itemtype])) {
            $type = $array[$itemtype];
         } else {
            $type = '';
         }
      } else {
         $type = $itemtype;
      }
      $item = getItemForItemtype($type);
      if ($item !== false) {
         if ($getFromDB) {
            if ((isset($array[$items_id]))
                && ($item->getFromDB($array[$items_id]))) {
               return $item;
            }
         } else if ($getEmpty) {
            if ($item->getEmpty()) {
               return $item;
            }
         } else {
            return $item;
         }
         unset($item);
      }

      return false;
   }


   /**
    * Factorization of prepareInputForUpdate for CommonDBRelation and CommonDBChild. Just check if
    * we can change the item
    *
    * @warning if the update is not possible (right problem), then $input become false
    *
    * @param $input   array   the new values for the current item
    * @param $fields  array   list of fields that define the attached items
    *
    * @return true if the attached item has changed, false if the attached items has not changed
    **/
   function checkAttachedItemChangesAllowed(array $input, array $fields) {

      // Merge both arrays to ensure all the fields are defined for the following checks
      $input = array_merge($this->fields, $input);

      $have_to_check = false;
      foreach ($fields as $field_name) {
         if ((isset($this->fields[$field_name]))
             && ($input[$field_name] != $this->fields[$field_name])) {

            $have_to_check = true;
            break;
         }
      }

      if ($have_to_check) {

         $new_item = clone $this;

         // Solution 1 : If we cannot create the new item or delete the old item,
         // then we cannot update the item
         unset($new_item->fields);
         if ((!$new_item->can(-1, 'w', $input)) || (!$this->can($this->getID(), 'd'))) {
            Session::addMessageAfterRedirect(__('Cannot update item: not enough right on the parent(s) item(s)'),
                                             INFO, true);
            return false;
         }

         // Solution 2 : simple check ! Can we update the item with new values ?
//       if (!$new_item->can($input['id'], 'w')) {
//          Session::addMessageAfterRedirect(__('Cannot update item: not enough right on the parent(s) item(s)'),
//                                           INFO, true);
//          return false;
//       }
      }

      return true;
   }

   /**
    * Is auto entityForwarding needed ?
    *
    * @return boolean
    **/
   function tryEntityForwarding() {
      return (!static::$disableAutoEntityForwarding && $this->isEntityAssign());
   }


   /**
    * Factorization of canCreate, canView, canUpate and canDelete. It checks the ability to
    * create, view, update or delete the item if it is possible (ie : $itemtype != 'itemtype')
    *
    * The aim is that the rights are driven by the attached item : if we can do the action on the
    * item, then we can do the action of the CommonDBChild or the CommonDBRelation. Thus, it is the
    * inverse of CommonDBTM's behaviour, that, by default forbid any action (cf.
    * CommonDBTM::canCreate and CommonDBTM::canView)
    *
    * @warning By default, if the action is possible regarding the attaching item, then it is
    * possible on the CommonDBChild and the CommonDBRelation.
    *
    * @param $method     the method to check (canCreate, canView, canUpdate of canDelete)
    * @param $item_right the right to check (DONT_CHECK_ITEM_RIGHTS, HAVE_VIEW_RIGHT_ON_ITEM ...)
    * @param $itemtype   the name of the field of the type of the item to get
    * @param $items_id   the name of the field of the id of the item to get
    *
    * @return true if we have absolute right to create the current connexity
   **/
   static function canConnexity($method, $item_right, $itemtype, $items_id) {

      if (($item_right != self::DONT_CHECK_ITEM_RIGHTS)
          &&(!preg_match('/^itemtype/', $itemtype))) {
         if ($item_right == self::HAVE_VIEW_RIGHT_ON_ITEM) {
            $method = 'canView';
         }
         if (!$itemtype::$method()) {
            return false;
         }
      }
      return true;
   }


   /**
    * Factorization of canCreateItem, canViewItem, canUpateItem and canDeleteItem. It checks the
    * ability to create, view, update or delete the item. If we cannot check the item (none is
    * existing), then we can do the action of the current connexity
    *
    * @param $methodItem    the method to check (canCreateItem, canViewItem, canUpdateItem of
                            canDeleteItem)
    * @param $methodNotItem the method to check (canCreate, canView, canUpdate of canDelete)
    * @param $item_right    the right to check (DONT_CHECK_ITEM_RIGHTS, HAVE_VIEW_RIGHT_ON_ITEM ...)
    * @param $itemtype      the name of the field of the type of the item to get
    * @param $items_id      the name of the field of the id of the item to get
    * @param &$item         the item concerned by the item (default NULL)
    *
    * @return true if we have absolute right to create the current connexity
   **/
   function canConnexityItem($methodItem, $methodNotItem, $item_right, $itemtype, $items_id,
                             &$item=NULL) {

      // Do not get it twice
      if ($item == NULL) {
         $item = $this->getConnexityItem($itemtype, $items_id);
      }
      if ($item_right != self::DONT_CHECK_ITEM_RIGHTS) {
         if ($item !== false) {
            if ($item_right == self::HAVE_VIEW_RIGHT_ON_ITEM) {
               $methodNotItem = 'canView';
               $methodItem    = 'canViewItem';
            }
            // here, we can check item's global rights
            if (preg_match('/^itemtype/', $itemtype)) {
               if (!$item->$methodNotItem()) {
                  return false;
               }
            }
            return $item->$methodItem();
         } else {
            // if we cannot get the parent, then we throw an exception
            throw new CommonDBConnexityItemNotFound();
         }
      }
      return true;
   }


   /**
    * @since version 0.84
    *
    * Get the change values for history when only the fields of the CommonDBChild are updated
    * @warning can be call as many time as fields are updated
    *
    * @param $field the name of the field that has changed
    *
    * @return array as the third parameter of Log::history() method or false if we don't want to
    *         log for the given field
   **/
   function getHistoryChangeWhenUpdateField($field) {

      return array('0', addslashes($this->oldvalues[$field]), addslashes($this->fields[$field]));
   }


   /**
    * Factorized method to search difference when updating a connexity : return both previous
    * item and new item if both are different. Otherwise returns new items
    *
    * @param $itemtype      the name of the field of the type of the item to get
    * @param $items_id      the name of the field of the id of the item to get
    *
    * @return array containing "previous" (if exists) and "new". Beware that both can be equal
    *         to false
   **/
   function getItemsForLog($itemtype, $items_id) {

      $newItemArray[$items_id] = $this->fields[$items_id];
      if (isset($this->oldvalues[$items_id])) {
         $previousItemArray[$items_id] = $this->oldvalues[$items_id];
      } else {
         $previousItemArray[$items_id] = $this->fields[$items_id];
      }

      if (preg_match('/^itemtype/', $itemtype)) {
         $newItemArray[$itemtype] = $this->fields[$itemtype];
         if (isset($this->oldvalues[$itemtype])) {
            $previousItemArray[$itemtype] = $this->oldvalues[$itemtype];
         } else {
            $previousItemArray[$itemtype] = $this->fields[$itemtype];
         }
      }

      $result = array('new' => self::getItemFromArray($itemtype, $items_id, $newItemArray));
      if ($previousItemArray !== $newItemArray) {
         $result['previous'] = self::getItemFromArray($itemtype, $items_id, $previousItemArray);
      }

      return $result;
   }

}
?>
