<?php
/*
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

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

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 *  Class KnowbaseItem_Item
 *
 *  @author Johan Cwiklinski <jcwiklinski@teclib.com>
 *
 *  @since 9.2
 */
class KnowbaseItem_Item extends CommonDBRelation {

   // From CommonDBRelation
   static public $itemtype_1          = 'KnowbaseItem';
   static public $items_id_1          = 'knowbaseitems_id';
   static public $itemtype_2          = 'Ticket';
   static public $items_id_2          = 'ticket_id';

   static public $checkItem_2_Rights  = self::DONT_CHECK_ITEM_RIGHTS;
   static public $logs_for_item_2     = false;

   // From CommonDBTM
   public $dohistory          = true;

   static $rightname          = 'knowbaseitem';

   static function getTypeName($nb=0) {
      return _n('Knowledge base item', 'Knowledge base items', $nb);
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      if (!$withtemplate) {
         $nb = 0;
         if ($_SESSION['glpishow_count_on_tabs']) {
            if ($item->getType() == KnowbaseItem::getType()) {
               $nb = countElementsInTable(
                  'glpi_knowbaseitems_items',
                  ['knowbaseitems_id' => $item->getID()]
               );
            } else {
               $nb = countElementsInTable(
                  'glpi_knowbaseitems_items',
                  [
                     'itemtype' => $item::getType(),
                     'items_id' => $item->getId()
                  ]
               );
            }
         }

         $type_name = null;
         if ($item->getType() == KnowbaseItem::getType()) {
            $type_name = _n('Linked item', 'Linked items', $nb);
         } else {
            $type_name = self::getTypeName($nb);
         }

         return self::createTabEntry($type_name, $nb);
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      self::showForItem($item);
      return true;
   }

   /**
    * Show linked items of a knowbase item
    *
    * @param $item                     CommonDBTM object
    * @param $withtemplate    integer  withtemplate param (default '')

   **/
   static function showForItem(CommonDBTM $item, $withtemplate='') {
      global $DB;

      $item_id = $item->getID();
      $item_type = $item::getType();

      if (isset($_GET["start"])) {
         $start = intval($_GET["start"]);
      } else {
         $start = 0;
      }

      // Total Number of events
      if ($item_type == KnowbaseItem::getType()) {
         $number = countElementsInTable("glpi_knowbaseitems_items", ['knowbaseitems_id' => $item_id]);
      } else {
         $number = countElementsInTable(
            'glpi_knowbaseitems_items',
            [
               'itemtype' => $item::getType(),
               'items_id' => $item_id
            ]
         );
      }

      // No Events in database
      if ($number < 1) {
         $no_txt = ($item_type == KnowbaseItem::getType()) ?
            __('No linked items') :
            __('No knowledge base entries linked');
         echo "<div class='center'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>$no_txt</th></tr>";
         echo "</table>";
         echo "</div><br>";
         return;
      }

      // Display the pager
      $type_name = null;
      if ($item->getType() == KnowbaseItem::getType()) {
         $type_name = _n('Linked item', 'Linked items', 1);
      } else {
         $type_name = self::getTypeName(1);
      }
      Html::printAjaxPager($type_name, $start, $number);

      // Output events
      echo "<div class='center'><table class='tab_cadre_fixehov'>";

      $header = "<tr><th>" . __('Type') . "</th>";
      $header .= "<th>".__('Item')."</th>";
      $header .= "<th>".__('Creation date')."</th>";
      $header .= "<th>".__('Update date')."</th>";
      $header .= "</tr>";
      echo $header;

      foreach (self::getItems($item, $start, $_SESSION['glpilist_limit']) as $data) {
         $linked_item = getItemForItemtype($data['itemtype']);
         $linked_item->getFromDB($data['items_id']);

         $name = $linked_item->fields['name'];
         if ($_SESSION["glpiis_ids_visible"]
            || empty($data["name"])) {
            $name = sprintf(__('%1$s (%2$s)'), $name, $data["items_id"]);
         }

         $link = $linked_item::getFormURLWithID($data['items_id']);

         // show line
         echo "<tr class='tab_bg_2'>";
         echo "<td>" . $linked_item->getTypeName(1) . "</td>" .
                 "<td><a href=\"" . $link . "\">" . $name . "</a></td>".
                 "<td class='tab_date'>".$data['date_creation']."</td>".
                 "<td class='tab_date'>".$data['date_mod']."</td>";
         echo "</tr>";
      }
      echo $header;
      echo "</table></div>";
      Html::printAjaxPager($type_name, $start, $number);
   }

   /**
    * Retrieve items for a knowbase item
    *
    * @param $item                     CommonDBTM object
    * @param $start        integer     first line to retrieve (default 0)
    * @param $limit        integer     max number of line to retrive (0 for all) (default 0)
    * @param $sqlfilter    string      to add an SQL filter (default '')
    *
    * @return array of linked items
   **/
   static function getItems(CommonDBTM $item, $start=0, $limit=0, $sqlfilter='') {
      global $DB;

      $itemtype  = $item->getType();
      $items_id  = $item->getField('id');
      $itemtable = $item->getTable();

      if ($item::getType() == KnowbaseItem::getType()) {
         $id_field = 'knowbaseitems_id';
      } else {
        $id_field = 'items_id';
      }

      $query = "SELECT *
                FROM `glpi_knowbaseitems_items`
                WHERE `$id_field` = '$items_id' ";

      if ($sqlfilter) {
         $query .= "AND ($sqlfilter) ";
      }
      $query .= "ORDER BY `itemtype`, `items_id` DESC";

      if ($limit) {
         $query .= " LIMIT ".intval($start)."," . intval($limit);
      }

      $linked_items = array();
      foreach ($DB->request($query) as $data) {
         $linked_items[] = $data;
      }
      return $linked_items;
   }
}
