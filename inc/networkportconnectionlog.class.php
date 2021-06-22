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
   die("Sorry. You can't access directly to this file");
}

/**
 * Store ports connections log
 */
class NetworkPortConnectionLog extends CommonDBChild {

   static public $itemtype        = 'NetworkPort';
   static public $items_id        = 'networkports_id';
   public $dohistory              = false;


   /**
    * Get name of this type by language of the user connected
    *
    * @param integer $nb number of elements
    *
    * @return string name of this type
    */
   static function getTypeName($nb = 0) {
      return __('Port connection history');
   }

   /**
    * Get the tab name used for item
    *
    * @param object $item the item object
    * @param integer $withtemplate 1 if is a template form
    * @return string|array name of the tab
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      $array_ret = [];

      if ($item->getType() == 'NetworkPort') {
         $cnt = countElementsInTable([static::getTable()], $this->getCriteria($item));
         $array_ret[] = self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $cnt);
      }
      return $array_ret;
   }

   public function getCriteria(NetworkPort $netport) {
      return [
         'OR' => [
            'networkports_id_source'      => $netport->fields['id'],
            'networkports_id_destination' => $netport->fields['id']
         ]
      ];
   }

   /**
    * Display the content of the tab
    *
    * @param object $item
    * @param integer $tabnum number of the tab to display
    * @param integer $withtemplate 1 if is a template form
    * @return boolean
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() == NetworkPort::getType() && $item->getID() > 0) {
         $connectionlog = new self();
         $connectionlog->showForItem($item);
         return true;
      }
      return false;
   }

   public function showForItem(NetworkPort $netport, $user_filters = []) {
      global $DB;

      $iterator = $DB->request([
         'FROM'   => $this->getTable(),
         'WHERE'  => $this->getCriteria($netport)
      ]);

      echo "<table class='tab_cadre_fixehov'>";
      echo "<thead><tr>";
      echo "<th>" . __('State')  . "</th>";
      echo "<th>" . _n('Date', 'Dates', 1)  . "</th>";
      echo "<th>" . __('Connected item')  . "</th>";
      echo "</tr></thead>";

      echo "<tbody>";

      if (!count($iterator)) {
         echo "<tr><td colspan='4' class='center'>" . __('No result found')  . "</td></tr>";
      }

      while ($row = $iterator->next()) {
         echo "<tr>";
         echo "<td>";

         if ($row['connected'] == 1) {
            $co_class = 'fa-link netport green';
            $title = __('Connected');
         } else {
            $co_class = 'fa-unlink netport red';
            $title = __('Not connected');
         }
         echo "<i class='fas $co_class' title='$title'></i> <span class='sr-only'>$title</span>";
         echo "</td>";
         echo "<td>" . $row['date']  . "</td>";
         echo "<td>";

         $is_source = $netport->fields['id'] == $row['networkports_id_source'];
         $netports_id = $row[($is_source ? 'networkports_id_destination' : 'networkports_id_source')];

         $cport = new NetworkPort();
         if ($cport->getFromDB($netports_id)) {
            $citem = new $cport->fields["itemtype"];
            $citem->getFromDB($cport->fields["items_id"]);

            $cport_link = sprintf(
               "<a href='%1\$s'>%2\$s</a>",
               $cport->getFormURLWithID($cport->fields['id']),
               (trim($cport->fields['name']) == '' ? __('Without name') : $cport->fields['name'])
            );

            echo sprintf(
               '%1$s on %2$s',
               $cport_link,
               $citem->getLink(1)
            );
         } else if ($row['connected'] == 1) {
            echo __('No longer exists in database');
         }

         echo "</td>";
         echo "</tr>";
      }
      echo "</tbody>";
   }
}
