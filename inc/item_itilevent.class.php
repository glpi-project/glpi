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
   die("Sorry. You can't access directly to this file");
}

/**
 * Item_ITILEvent Class
 *
 * Relation between ITILEvents and Items
 * @since 10.0.0
**/
class Item_ITILEvent extends CommonDBRelation
{
   
   // From CommonDBRelation
   static public $itemtype_1          = 'ITILEvent';
   static public $items_id_1          = 'itilevents_id';

   static public $itemtype_2          = 'itemtype';
   static public $items_id_2          = 'items_id';
   static public $checkItem_2_Rights  = self::HAVE_VIEW_RIGHT_ON_ITEM;

   /**
    * Used for linking items to ITILEvents. The linked item is the source of the event.
    */
   const LINK_SOURCE = 0;

   /**
    * Used for linking items to ITILEvents. The linked item is affected by the event but not the source.
    * For example: a VM host is restarted, all guests are also down unless moved to another host.
    */
   const LINK_AFFECTED = 1;


   function getForbiddenStandardMassiveAction()
   {
      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }

   function canCreateItem()
   {
      $event = new ITILEvent();

      if ($event->canUpdateItem()) {
         return true;
      }

      return parent::canCreateItem();
   }

   function post_addItem()
   {
      $event = new ITILEvent();
      $input  = [
         'id'              => $this->fields['itilevents_id'],
         'date_creation'   => $_SESSION["glpi_currenttime"],
         'date_mod'        => $_SESSION["glpi_currenttime"],
      ];

      $event->update($input);
      parent::post_addItem();
   }

   function post_purgeItem()
   {

      $event = new ITILEvent();
      $input  = [
         'id'              => $this->fields['itilevents_id'],
         'date_creation'   => $_SESSION["glpi_currenttime"],
         'date_mod'        => $_SESSION["glpi_currenttime"],
      ];

      $event->update($input);

      parent::post_purgeItem();
   }

   function prepareInputForAdd($input)
   {

      // Avoid duplicate entry
      if (countElementsInTable($this->getTable(), ['itilevents_id' => $input['itilevents_id'],
                                                   'itemtype'   => $input['itemtype'],
                                                   'items_id'   => $input['items_id']]) > 0) {
         return false;
      }

      return parent::prepareInputForAdd($input);
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
   {

      if (!$withtemplate) {
         $nb = 0;
         switch ($item->getType()) {
            case 'ITILEvent' :
               if (($_SESSION["glpiactiveprofile"]["helpdesk_hardware"] != 0)
                   && (count($_SESSION["glpiactiveprofile"]["helpdesk_item_type"]) > 0)) {
                  if ($_SESSION['glpishow_count_on_tabs']) {
                     $nb = countElementsInTable('glpi_items_itilevents',
                                                ['AND' => ['itilevents_id' => $item->getID() ],
                                                   ['itemtype' => $_SESSION["glpiactiveprofile"]["helpdesk_item_type"]]
                                                ]);
                  }
                  return self::createTabEntry(_n('Item', 'Items', Session::getPluralNumber()), $nb);
               }
            default:
               //TODO Count active only? Or show both?
               $nb = countElementsInTable('glpi_items_itilevents',
                                                ['AND' => ['items_id' => $item->getID() ],
                                                   ['itemtype' => $item->getType()]
                                                ]);
               return self::createTabEntry(_n('Event', 'Events', Session::getPluralNumber()), $nb);
         }
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
   {

      switch ($item->getType()) {
         case 'ITILEvent' :
            self::showForITILEvent($item);
            break;
         default:
            self::showForItem($item);
            break;
      }
      return true;
   }

   /**
    * Return used items for an ITILEvent
    *
    * @param type $itilevents_id
    * @return type
    */
   static function getUsedItems($itilevents_id)
   {

      $data = getAllDatasFromTable('glpi_items_itilevents', ['itilevents_id' => $itilevents_id]);
      $used = [];
      if (!empty($data)) {
         foreach ($data as $val) {
            $used[$val['itemtype']][] = $val['items_id'];
         }
      }

      return $used;
   }

   static function getSpecificValueToDisplay($field, $values, array $options = [])
   {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      switch ($field) {
         case 'items_id':
            if (strpos($values[$field], "_") !== false) {
               $item_itemtype      = explode("_", $values[$field]);
               $values['itemtype'] = $item_itemtype[0];
               $values[$field]     = $item_itemtype[1];
            }

            if (isset($values['itemtype'])) {
               if (isset($options['comments']) && $options['comments']) {
                  $tmp = Dropdown::getDropdownName(getTableForItemtype($values['itemtype']),
                                                   $values[$field], 1);
                  return sprintf(__('%1$s %2$s'), $tmp['name'],
                                 Html::showToolTip($tmp['comment'], ['display' => false]));

               }
               return Dropdown::getDropdownName(getTableForItemtype($values['itemtype']),
                                                $values[$field]);
            }
            break;
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }

   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
   {
      if (!is_array($values)) {
         $values = [$field => $values];
      }
      $options['display'] = false;
      switch ($field) {
         case 'items_id' :
            if (isset($values['itemtype']) && !empty($values['itemtype'])) {
               $options['name']  = $name;
               $options['value'] = $values[$field];
               return Dropdown::show($values['itemtype'], $options);
            } else {
               self::dropdownAllDevices($name, 0, 0);
               return ' ';
            }
            break;
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }

   /**
    * Display events for an item
    *
    * @param $item            CommonDBTM object for which the event tab need to be displayed
    * @param $withtemplate    withtemplate param (default 0)
   **/
   static function showForItem(CommonDBTM $item, $withtemplate = 0)
   {
      ITILEvent::showListForItem($item);
   }
}