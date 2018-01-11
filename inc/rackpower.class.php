<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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

class RackPower extends CommonGLPI {

   static function getTypeName($nb = 0) {
      return __('Power management');
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate
          && ($item->getType() == 'Rack')
          && Rack::canView()) {
         $nb = 0;
         if ($_SESSION['glpishow_count_on_tabs']) {
            $nb = countElementsInTable(
               Item_Rack::getTable(), [
                  'racks_id'  => $item->getID(),
                  'itemtype'  => PDU::getType()
               ]);
         }
         return self::createTabEntry(self::getTypeName(), $nb);
      }
      return '';
   }

   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);

      return $ong;
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      self::showForRack($item);
      return true;
   }

   /**
    * Print power related components
    *
    * @param Rack $rack Rack instance
    *
    * @return void
   **/
   static function showForRack(Rack $rack) {
      global $DB;

      $ID = $rack->fields['id'];
      $ira = new Item_Rack();

      if (!$rack->getFromDB($ID) || !$rack->can($ID, READ)) {
         return false;
      }
      $canedit = $rack->canEdit($ID);

      if ($canedit) {
         echo "<div class='center firstbloc'>".
                "<a class='vsubmit' href='" . $ira->getFormURL() . "?racks_id=$ID&power=true'>";
         echo __('Attach a new power device');
         echo "</a></div>\n";
      }

      echo "<div class='center'>";

      $pdus = $DB->request([
         'FROM'   => Item_Rack::getTable(),
         'WHERE'  => [
            'racks_id'  => $ID,
            'itemtype'  => PDU::getType()
         ]
      ]);

      echo "<table class='tab_cadre_fixehov'>";

      if (!count($pdus)) {
         echo "<tr><th>".__('No power device attached')."</th></tr>";
      } else {
         echo "<tr class='noHover'><th colspan='10'>".__('List of power devices')."</th></tr>";

         $header = "<tr><th>".__('Name')."</th>";
         $header .= "<th>".__('Position')."</th>";
         $header .= "</tr>";
         echo $header;

         while ($row = $pdus->next()) {
            $pdu = new PDU();
            $pdu->getFromDB($row['items_id']);
            echo "<tr class='tab_bg_2'>";
            echo "<td>".$pdu->getLink()."</td>";

            $position = $row['position'];
            if ($position < 1) {
               switch ($position) {
                  case Rack::OUT_BOTTOM:
                     $position = __('Out bottom');
                     break;
                  case Rack::OUT_LEFT:
                     $position = __('Out left');
                     break;
                  case Rack::OUT_RIGHT:
                     $position = __('Out right');
                     break;
               }
            }
            echo "<td>$position</td>";

            echo "</tr>";
         }
         echo $header;
      }
      echo "</table>";
      echo "</div>";
   }
}
