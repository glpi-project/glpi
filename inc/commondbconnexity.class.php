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


/**
 * @since 0.84
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
    * @since 9.4
    *
    * @param string  $itemtype the type of the item to look for
    * @param integer $items_id the id of the item to look for
    *
    * @return array|null
    */
   static function getSQLCriteriaToSearchForItem($itemtype, $items_id) {
      return null;
   }


   /**
    * Clean the Connecity Table when item of the relation is deleted
    * To be call from the cleanDBonPurge of each Item class
    *
    * @param string  $itemtype  type of the item
    * @param integer $items_id  id of the item
   **/
   function cleanDBonItemDelete ($itemtype, $items_id) {
      global $DB;

      $criteria = static::getSQLCriteriaToSearchForItem($itemtype, $items_id);
      if ($criteria !== null) {
         $input = [
            '_no_history'     => true,
            '_no_notif'       => true
         ];

         $iterator = $DB->request($criteria);
         while ($data = $iterator->next()) {
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
    * @param string  $itemtype          the name of the field of the type of the item to get
    * @param string  $items_id          the name of the field of the id of the item to get
    * @param boolean $getFromDB         do we have to load the item from the DB ?
    * @param boolean $getEmpty          else : do we have to load an empty item ?
    * @param boolean $getFromDBOrEmpty  get from DB if possible, else, getEmpty
    *
    * @return CommonDBTM|boolean the item or false if we cannot load the item
   **/
   function getConnexityItem($itemtype, $items_id, $getFromDB = true, $getEmpty = true,
                             $getFromDBOrEmpty = false) {

      return static::getItemFromArray($itemtype, $items_id, $this->fields, $getFromDB,
                                      $getEmpty, $getFromDBOrEmpty);
   }


   /**
    * get associated item (defined by $itemtype and $items_id)
    *
    * @param string  $itemtype          the name of the field of the type of the item to get
    * @param string  $items_id          the name of the field of the id of the item to get
    * @param array   $array             the array in we have to search ($input, $this->fields ...)
    * @param boolean $getFromDB         do we have to load the item from the DB ?
    * @param boolean $getEmpty          else : do we have to load an empty item ?
    * @param boolean $getFromDBOrEmpty  get from DB if possible, else, getEmpty
    *
    * @return CommonDBTM|boolean the item or false if we cannot load the item
   **/
   static function getItemFromArray($itemtype, $items_id, array $array, $getFromDB = true,
                                    $getEmpty = true, $getFromDBOrEmpty = false) {

      if (preg_match('/^itemtype/', $itemtype)) {
         if (isset($array[$itemtype])) {
            $type = $array[$itemtype];
         } else {
            $type = '';
         }
      } else {
         $type = $itemtype;
      }
      $item = ($type ? getItemForItemtype($type) : false);

      if ($item !== false) {
         if ($getFromDB
             || $getFromDBOrEmpty) {
            if (isset($array[$items_id])
                && $item->getFromDB($array[$items_id])) {
               return $item;
            }
            if ($getFromDBOrEmpty) {
               if ($item->getEmpty()) {
                  return $item;
               }
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
         if (!$new_item->can(-1, CREATE, $input)
              || !$this->can($this->getID(), DELETE)
              || !$this->can($this->getID(), PURGE)) {
            Session::addMessageAfterRedirect(__('Cannot update item: not enough right on the parent(s) item(s)'),
                                             INFO, true);
            return false;
         }

         // Solution 2 : simple check ! Can we update the item with new values ?
         // if (!$new_item->can($input['id'], 'w')) {
         //    Session::addMessageAfterRedirect(__('Cannot update item: not enough right on the parent(s) item(s)'),
         //                                     INFO, true);
         //    return false;
         // }
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
    * @param string  $method     the method to check (canCreate, canView, canUpdate of canDelete)
    * @param integer $item_right the right to check (DONT_CHECK_ITEM_RIGHTS, HAVE_VIEW_RIGHT_ON_ITEM ...)
    * @param string  $itemtype   the name of the field of the type of the item to get
    * @param string  $items_id   the name of the field of the id of the item to get
    *
    * @return boolean true if we have absolute right to create the current connexity
   **/
   static function canConnexity($method, $item_right, $itemtype, $items_id) {

      if (($item_right != self::DONT_CHECK_ITEM_RIGHTS)
          && (!preg_match('/^itemtype/', $itemtype))) {
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
    * @param string          $methodItem    the method to check (canCreateItem, canViewItem,
                                            canUpdateItem or canDeleteItem)
    * @param string          $methodNotItem the method to check (canCreate, canView, canUpdate of canDelete)
    * @param integer         $item_right    the right to check (DONT_CHECK_ITEM_RIGHTS, HAVE_VIEW_RIGHT_ON_ITEM ...)
    * @param string          $itemtype      the name of the field of the type of the item to get
    * @param string          $items_id      the name of the field of the id of the item to get
    * @param CommonDBTM|null &$item         the item concerned by the item
    *
    * @return true if we have absolute right to create the current connexity
   **/
   function canConnexityItem($methodItem, $methodNotItem, $item_right, $itemtype, $items_id,
                             CommonDBTM &$item = null) {

      // Do not get it twice
      $connexityItem = $item;
      if (is_null($connexityItem)) {
         $connexityItem = $this->getConnexityItem($itemtype, $items_id);

         // Set value in $item to reuse it on future calls
         if ($connexityItem instanceof CommonDBTM) {
            $item = $this->getConnexityItem($itemtype, $items_id);
         }
      }
      if ($item_right != self::DONT_CHECK_ITEM_RIGHTS) {
         if ($connexityItem !== false) {
            if ($item_right == self::HAVE_VIEW_RIGHT_ON_ITEM) {
               $methodNotItem = 'canView';
               $methodItem    = 'canViewItem';
            }
            // here, we can check item's global rights
            if (preg_match('/^itemtype/', $itemtype)) {
               if (!$connexityItem->$methodNotItem()) {
                  return false;
               }
            }
            return $connexityItem->$methodItem();
         } else {
            // if we cannot get the parent, then we throw an exception
            throw new CommonDBConnexityItemNotFound();
         }
      }
      return true;
   }


   /**
    * @since 0.84
    *
    * Get the change values for history when only the fields of the CommonDBChild are updated
    * @warning can be call as many time as fields are updated
    *
    * @param string $field the name of the field that has changed
    *
    * @return array as the third parameter of Log::history() method or false if we don't want to
    *         log for the given field
   **/
   function getHistoryChangeWhenUpdateField($field) {

      return ['0', addslashes($this->oldvalues[$field]), addslashes($this->fields[$field])];
   }


   /**
    * Factorized method to search difference when updating a connexity : return both previous
    * item and new item if both are different. Otherwise returns new items
    *
    * @param string $itemtype  the name of the field of the type of the item to get
    * @param string $items_id  the name of the field of the id of the item to get
    *
    * @return array containing "previous" (if exists) and "new". Beware that both can be equal
    *         to false
   **/
   function getItemsForLog($itemtype, $items_id) {

      $newItemArray = [
         $items_id => $this->fields[$items_id],
      ];
      $previousItemArray = [];

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

      $result = ['new' => self::getItemFromArray($itemtype, $items_id, $newItemArray)];
      if ($previousItemArray !== $newItemArray) {
         $result['previous'] = self::getItemFromArray($itemtype, $items_id, $previousItemArray);
      }

      return $result;
   }

   /**
    * Get all specificities of the current itemtype concerning the massive actions
    *
    * @since 0.85
    *
    * @return array of the specificities:
    *        'reaffect'   is it possible to reaffect the connexity (1 or 2 for CommonDBRelation)
    *        'itemtypes'  the types of the item in cas of reaffectation
    *        'normalized' array('affect', 'unaffect') of arrays containing each action
    *        'button_labels'          array of the labels of the button indexed by the action name
   **/
   static function getConnexityMassiveActionsSpecificities() {

      return ['reaffect'      => false,
                   'itemtypes'     => [],
                   'normalized'    => ['affect'   => ['affect'],
                                            'unaffect' => ['unaffect']],
                   'action_name'   => ['affect'   => _x('button', 'Associate'),
                                            'unaffect' => _x('button', 'Dissociate')]];
   }


   /**
    * @since 0.85
    *
    * @see CommonDBTM::getMassiveActionsForItemtype()
   **/
   static function getMassiveActionsForItemtype(array &$actions, $itemtype, $is_deleted = 0,
                                                CommonDBTM $checkitem = null) {

      $unaffect = false;
      $affect   = false;
      if (is_a($itemtype, 'CommonDBChild', true)) {
         $specificities = $itemtype::getConnexityMassiveActionsSpecificities();
         if (!$itemtype::$mustBeAttached) {
            $unaffect = true;
            $affect   = true;
         } else if ($specificities['reaffect']) {
            $affect = true;
         }
      } else if (is_a($itemtype, 'CommonDBRelation', true)) {
         $specificities = $itemtype::getConnexityMassiveActionsSpecificities();
         if ((!$itemtype::$mustBeAttached_1) || (!$itemtype::$mustBeAttached_2)) {
            $unaffect = true;
            $affect   = true;
         } else if ($specificities['reaffect']) {
            $affect = true;
         }
      } else {
         return;
      }

      $prefix = __CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR;

      if ($unaffect) {
         $actions[$prefix.'unaffect'] = $specificities['action_name']['unaffect'];
      }

      if ($affect) {
         $actions[$prefix.'affect'] = $specificities['action_name']['affect'];
      }

      parent::getMassiveActionsForItemtype($actions, $itemtype, $is_deleted, $checkitem);
   }


   /**
    * @since 0.85
    *
    * @see CommonDBTM::showMassiveActionsSubForm()
   **/
   static function showMassiveActionsSubForm(MassiveAction $ma) {

      $action = $ma->getAction();
      $items  = $ma->getItems();

      $itemtypes_affect   = [];
      $itemtypes_unaffect = [];
      foreach (array_keys($items) as $itemtype) {
         if (!is_a($itemtype, __CLASS__, true)) {
            continue;
         }
         $specificities = $itemtype::getConnexityMassiveActionsSpecificities();
         if (in_array($action, $specificities['normalized']['affect'])) {
            $itemtypes_affect[$itemtype] = $specificities;
            continue;
         }
         if (in_array($action, $specificities['normalized']['unaffect'])) {
            $itemtypes_unaffect[$itemtype] = $specificities;
            continue;
         }
      }

      if (count($itemtypes_affect) > count($itemtypes_unaffect)) {
         $normalized_action = 'affect';
         $itemtypes         = $itemtypes_affect;
      } else if (count($itemtypes_affect) < count($itemtypes_unaffect)) {
         $normalized_action = 'unaffect';
         $itemtypes         = $itemtypes_unaffect;
      } else {
         return parent::showMassiveActionsSubForm($ma);
      }

      switch ($normalized_action) {
         case 'unaffect' :
            foreach ($itemtypes as $itemtype => $specificities) {
               if (is_a($itemtype, 'CommonDBRelation', true)) {
                  $peer_field = "peer[$itemtype]";
                  if ((!$itemtype::$mustBeAttached_1) && (!$itemtype::$mustBeAttached_2)) {
                     // Should never occur ... But we must care !
                     $values = [];
                     if ((empty($itemtype::$itemtype_1))
                      || (preg_match('/^itemtype/', $itemtype::$itemtype_1))) {
                        $values[0] = __('First Item');
                     } else {
                        $itemtype_1 = $itemtype::$itemtype_1;
                        $values[0]  = $itemtype_1::getTypeName(Session::getPluralNumber());
                     }
                     if ((empty($itemtype::$itemtype_2))
                         || (preg_match('/^itemtype/', $itemtype::$itemtype_2))) {
                        $values[1] = __('Second Item');
                     } else {
                        $itemtype_2 = $itemtype::$itemtype_2;
                        $values[1]  = $itemtype_2::getTypeName(Session::getPluralNumber());
                     }
                     echo sprintf(__('Select a peer for %s:'), $itemtype::getTypeName());
                     Dropdown::showFromArray($peer_field, $values);
                     echo "<br>\n";
                  } else if (!$itemtype::$mustBeAttached_1) {
                     echo "<input type='hidden' name='$peer_field' value='0'>";
                  } else if (!$itemtype::$mustBeAttached_2) {
                     echo "<input type='hidden' name='$peer_field' value='1'>";
                  }
               }
            }
            echo "<br><br>".Html::submit(_x('button', 'Dissociate'), ['name' => 'massiveaction']);
            return true;

         case 'affect' :
            $peertypes = [];
            foreach ($itemtypes as $itemtype => $specificities) {
               if (!$specificities['reaffect']) {
                  continue;
               }
               if (is_a($itemtype, 'CommonDBRelation', true)) {
                  if ($specificities['reaffect'] == 1) {
                     $peertype = $itemtype::$itemtype_1;
                  } else {
                     $peertype = $itemtype::$itemtype_2;
                  }
               } else {
                  $peertype = $itemtype::$itemtype;
               }
               if (preg_match('/^itemtype/', $peertype)) {
                  $peertypes = array_merge($peertypes, $specificities['itemtypes']);
               } else {
                  $peertypes[] = $peertype;
               }
            }
            $peertypes = array_unique($peertypes);
            if (count($peertypes) == 0) {
               echo __('Unable to reaffect given elements !');
               exit();
            }
            $options = [];
            if (count($peertypes) == 1) {
               $options['name']   = 'peers_id';
               $type_for_dropdown = $peertypes[0];
               if (preg_match('/^itemtype/', $peertype)) {
                  echo Html::hidden('peertype', ['value' => $type_for_dropdown]);
               }
               $type_for_dropdown::dropdown($options);
            } else {
               $options['itemtype_name'] = 'peertype';
               $options['items_id_name'] = 'peers_id';
               $options['itemtypes']     = $peertypes;
               Dropdown::showSelectItemFromItemtypes($options);
            }

            echo "<br><br>".Html::submit(_x('button', 'Associate'), ['name' => 'massiveaction']);
            return true;
      }

      return parent::showMassiveActionsSubForm($ma);
   }


   /**
    * @since 0.85
    *
    * Set based array for static::add or static::update in case of massive actions are doing
    * something.
    *
    * @param string     $action  the name of the action
    * @param CommonDBTM $item    the item on which apply the massive action
    * @param integer[]  $ids     the ids of the item on which apply the action
    * @param array      $input   the input provided by the form ($_POST, $_GET ...)
    *
    * @return array containing the elements
   **/
   static function getConnexityInputForProcessingOfMassiveActions($action, CommonDBTM $item,
                                                                  array $ids, array $input) {
      return [];
   }


   /**
    * @since 0.85
    *
    * @see CommonDBTM::processMassiveActionsForOneItemtype()
   **/
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {

      if (!is_a($item, __CLASS__, true)) {
         parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
         return;
      }

      $itemtype      = $item->getType();
      $specificities = $itemtype::getConnexityMassiveActionsSpecificities();

      $action        = $ma->getAction();
      $input         = $ma->getInput();

      // First, get normalized action : affect or unaffect
      if (in_array($action, $specificities['normalized']['affect'])) {
         $normalized_action = 'affect';
      } else if (in_array($action, $specificities['normalized']['unaffect'])) {
         $normalized_action = 'unaffect';
      } else {
         // If we cannot get normalized action, then, its not for this method !
         parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
         return;
      }

      switch ($normalized_action) {
         case 'unaffect' :
            foreach ($ids as $key) {
               if ($item->can($key, UPDATE)) {
                  if ($item instanceof CommonDBRelation) {
                     if (isset($input['peer'][$item->getType()])) {
                        if ($item->affectRelation($key, $input['peer'][$item->getType()])) {
                           $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                        } else {
                           $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                           $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                        }
                     } else {
                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                        $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                     }
                  } else if ($item instanceof CommonDBChild) {
                     if ($item->affectChild($key)) {
                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                     } else {
                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                        $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                     }
                  }
               } else {
                  $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_NORIGHT);
                  $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
               }
            }
            return;

         case 'affect' :
            if (!$specificities['reaffect']) {
               $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
               $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
               return;
            }
            if (is_a($item, 'CommonDBRelation', true)) {
               if ($specificities['reaffect'] == 1) {
                  $peertype = $itemtype::$itemtype_1;
                  $peers_id = $itemtype::$items_id_1;
               } else {
                  $peertype = $itemtype::$itemtype_2;
                  $peers_id = $itemtype::$items_id_2;
               }
            } else {
               $peertype = $itemtype::$itemtype;
               $peers_id = $itemtype::$items_id;
            }
            $input2 = $itemtype::getConnexityInputForProcessingOfMassiveActions($action, $item,
                                                                                $ids, $input);
            $input2[$peers_id] = $input['peers_id'];
            if (preg_match('/^itemtype/', $peertype)) {
               if (!in_array($input['peertype'], $specificities['itemtypes'])) {
                  $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
                  $ma->addMessage($item->getErrorMessage(ERROR_NOT_FOUND));
                  return;
               }
               $input2[$peertype] = $input['peertype'];
            } else {
               if ($peertype != $input['peertype']) {
                  $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
                  $ma->addMessage($item->getErrorMessage(ERROR_NOT_FOUND));
                  return;
               }
            }
            foreach ($ids as $key) {
               if (!$item->getFromDB($key)) {
                  $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                  $ma->addMessage($item->getErrorMessage(ERROR_NOT_FOUND));
                  continue;
               }
               if (preg_match('/^itemtype/', $peertype)) {
                  if (($input2[$peertype] == $item->fields[$peertype])
                      && ($input2[$peers_id] == $item->fields[$peers_id])) {
                     $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                     $ma->addMessage($item->getErrorMessage(ERROR_ALREADY_DEFINED));
                     continue;
                  }
               } else {
                  if ($input2[$peers_id] == $item->fields[$peers_id]) {
                     $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                     $ma->addMessage($item->getErrorMessage(ERROR_ALREADY_DEFINED));
                     continue;
                  }
               }
               $input2[$item->getIndexName()] = $item->getID();
               if ($item->can($item->getID(), UPDATE, $input2)) {
                  if ($item->update($input2)) {
                     $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                  } else {
                     $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                     $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                  }
               } else {
                  $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_NORIGHT);
                  $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
               }
            }
            return;
      }

      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }

}
