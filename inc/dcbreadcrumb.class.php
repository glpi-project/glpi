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
 * Datacenter breadcrumb
 **/
trait DCBreadcrumb {

   /**
    * Display datacenter element breadcrumb
    * @see CommonGLPI::showNavigationHeader()
    *
    * @return void
    */
   function showDcBreadcrumb() {
      global $CFG_GLPI;

      $item = $this;

      $types = $CFG_GLPI['rackable_types'];
      $enclosure_types = $types;
      unset($enclosure_types[array_search('Enclosure', $enclosure_types)]);

      $breadcrumb = [];
      if (in_array($item->getType(), $enclosure_types)) {
         //check if asset is part of an enclosure
         if ($enclosure = $this->isEnclosurePart($item->getType(), $item->getID(), true)) {
            $breadcrumb[] = $enclosure->getLink();
            $item = $enclosure;
         }
      }

      if (in_array($item->getType(), $types)) {
         //check if asset (or its enclosure) is part of a rack
         if ($rack = $this->isRackPart($item->getType(), $item->getID(), true)) {
            $breadcrumb[] = $rack->getLink();
            $item = $rack;
         }
      }

      if ($item->getType() == Rack::getType()) {
         if ($item->fields['dcrooms_id'] > 0) {
            $dcroom = new DCRoom();
            if ($dcroom->getFromDB($item->fields['dcrooms_id'])) {
               $breadcrumb[] = $dcroom->getLink();
               $item = $dcroom;
            }
         }
      }

      if ($item->getType() == DCRoom::getType()) {
         if ($item->fields['datacenters_id'] > 0) {
            $datacenter = new Datacenter();
            if ($datacenter->getFromDB($item->fields['datacenters_id'])) {
               $breadcrumb[] = $datacenter->getLink();
            }
         }
      }

      if (count($breadcrumb)) {
         echo "<tr class='tab_bg_1'>
                  <td>" . __('Datacenter position') . "</td>
                  <td colspan='3'>" . implode(' > ', array_reverse($breadcrumb)) . "</td>
               </tr>";
      }
   }

   /**
    * Check if an item is part of an Enclosure
    *
    * @param string  $itemtype Item type
    * @param integer $items_id Item ID
    * @param boolean $getobj   Whether to return enclosure object
    *
    * @return false|Enclosure
    */
   public function isEnclosurePart($itemtype, $items_id, $getobj = false) {
      $ien = new Item_Enclosure();
      $found = $ien->getFromDBByCrit([
         'itemtype'  => $itemtype,
         'items_id'  => $items_id
      ]);

      if ($found && $getobj) {
         $enclosure = new Enclosure();
         if ($enclosure->getFromDB($ien->fields['enclosures_id'])) {
            return $enclosure;
         }
      }

      return $found;
   }

   /**
    * Check if an item is part of a rack
    *
    * @param string  $itemtype Item type
    * @param integer $items_id Item ID
    * @param boolean $getobj   Whether to return rack object
    *
    * @return false|Rack
    */
   public function isRackPart($itemtype, $items_id, $getobj = false) {
      $ira = new Item_Rack();
      $found = $ira->getFromDBByCrit([
         'itemtype'  => $itemtype,
         'items_id'  => $items_id
      ]);
      if ($found && $getobj) {
         $rack = new \Rack();
         if ($rack->getFromDb($ira->fields['racks_id'])) {
            return $rack;
         }
      }

      return $found;
   }
}
