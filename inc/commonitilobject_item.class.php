<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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
 * CommonItilObject_Item Class
 *
 * Relation between CommonItilObject_Item and Items
 */
abstract class CommonItilObject_Item extends CommonDBRelation
{
   static function getSpecificValueToDisplay($field, $values, array $options = []) {

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
                  $tmp = Dropdown::getDropdownName(getTableForItemType($values['itemtype']),
                                                   $values[$field], 1);
                  return sprintf(__('%1$s %2$s'), $tmp['name'],
                                 Html::showToolTip($tmp['comment'], ['display' => false]));

               }
               return Dropdown::getDropdownName(getTableForItemType($values['itemtype']),
                                                $values[$field]);
            }
            break;
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }

   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {
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
               static::dropdownAllDevices($name, 0, 0);
               return ' ';
            }
            break;
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }

   static function dropdownAllDevices($myname, $itemtype, $items_id = 0, $admin = 0, $users_id = 0,
                                      $entity_restrict = -1, $options = []) {
      global $CFG_GLPI;

      $params = [
         static::$items_id_1 => 0,
         'used'       => [],
         'multiple'   => 0,
         'rand'       => mt_rand(),
         'display'    => true,
      ];

      foreach ($options as $key => $val) {
         $params[$key] = $val;
      }

      $rand = $params['rand'];
      $out  = "";

      if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"] == 0) {
         $out.= "<input type='hidden' name='$myname' value=''>";
         $out.= "<input type='hidden' name='items_id' value='0'>";

      } else {
         $out.= "<div id='tracking_all_devices$rand' class='input-group mb-1'>";
         if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"]&pow(2, Ticket::HELPDESK_ALL_HARDWARE)) {
            // Display a message if view my hardware
            if ($users_id
                &&($_SESSION["glpiactiveprofile"]["helpdesk_hardware"]&pow(2, Ticket::HELPDESK_MY_HARDWARE))) {
               $out.= "<span class='input-group-text'>".__('Or complete search')."</span>";
            }

            $types = static::$itemtype_1::getAllTypesForHelpdesk();
            $emptylabel = __('General');
            if ($params[static::$items_id_1] > 0) {
               $emptylabel = Dropdown::EMPTY_VALUE;
            }
            $out.= Dropdown::showItemTypes($myname, array_keys($types), [
               'emptylabel' => $emptylabel,
               'value'      => $itemtype,
               'rand'       => $rand, 'display_emptychoice' => true,
               'display'    => $params['display'],
            ]);
            $p = [
               'itemtype'        => '__VALUE__',
               'entity_restrict' => $entity_restrict,
               'admin'           => $admin,
               'used'            => $params['used'],
               'multiple'        => $params['multiple'],
               'rand'            => $rand,
               'myname'          => "add_items_id"
            ];
            $out.= Ajax::updateItemOnSelectEvent(
               "dropdown_$myname$rand",
               "results_$myname$rand",
               $CFG_GLPI["root_doc"]."/ajax/dropdownTrackingDeviceType.php",
               $p,
               $params['display']
            );
            $out.= "<span id='results_$myname$rand'>";

            // Display default value if itemtype is displayed
            $found_type = isset($types[$itemtype]);
            if ($found_type
                && $itemtype) {
               if (($item = getItemForItemtype($itemtype))
                    && $items_id) {
                  if ($item->getFromDB($items_id)) {
                     $out.= Dropdown::showFromArray('items_id', [$items_id => $item->getName()], [
                        'value'   => $items_id,
                        'display' => $params['display']
                     ]);
                  }
               } else {
                  $p['itemtype'] = $itemtype;
                  $out.= "<script type='text/javascript' >";
                  $out.= "$(function() {";
                  $out.= Ajax::updateItemJsCode(
                     "results_$myname$rand",
                     $CFG_GLPI["root_doc"]."/ajax/dropdownTrackingDeviceType.php",
                     $p,
                     "",
                     $params['display']
                  );
                  $out.= '});</script>';
               }
            }
            $out.= "</span>";
         }
         $out.= "</div>";
      }

      if ($params['display']) {
         echo $out;
         return $rand;
      } else {
         return $out;
      }
   }

}
