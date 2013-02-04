<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

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
* @brief show network port by network equipment
*/


include ('../inc/includes.php');

Session::checkRight("reports", "r");

// Titre
if (isset($_POST["switch"]) && $_POST["switch"]) {
   Html::header(Report::getTypeName(2), $_SERVER['PHP_SELF'], "utils", "report");

   Report::title();

   $name = Dropdown::getDropdownName("glpi_networkequipments",$_POST["switch"]);
   echo "<div class='center spaced'><h2>".sprintf(__('Network report by hardware: %s'),$name).
        "</h2></div>";

   // TODO : must be review at the end of Damien's work
   $query = "SELECT `glpi_networkports`.`name` AS port, `glpi_networkports`.`ip`AS ip,
                    `glpi_networkports`.`mac` AS mac, `glpi_networkports`.`id` AS IDport,
                    `glpi_networkequipments`.`name` AS switch
             FROM `glpi_networkequipments`
             INNER JOIN `glpi_networkports`
                  ON (`glpi_networkports`.`itemtype` = 'NetworkEquipment'
                      AND `glpi_networkports`.`items_id` = `glpi_networkequipments`.`id`)
             WHERE `glpi_networkequipments`.`id` = '".$_POST["switch"]."'";

   $result = $DB->query($query);
   if ($result && $DB->numrows($result)) {
      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr><th>".__('Hardware ports')."</th>";
      echo "<th>".__('IP')."</th>";
      echo "<th>".__('MAC address')."</th>";
      echo "<th>".__('Device ports')."</th>";
      echo "<th>".__('IP')."</th>";
      echo "<th>".__('MAC address')."</th>";
      echo "<th>".__('Connected devices')."</th></tr>\n";

      while ($ligne = $DB->fetch_assoc($result)) {
         $switch            = $ligne['switch'];
         $port              = $ligne['port'];
         $nw                = new NetworkPort_NetworkPort();
         $networkports_id_1 = $nw->getOppositeContact($ligne['IDport']);
         $np                = new NetworkPort();
         $ip2               = "";
         $mac2              = "";
         $portordi          = "";
         $ordi              = "";

         if ($networkports_id_1) {
            $np->getFromDB($networkports_id_1);
            $ordi = '';
            if ($item = getItemForItemtype($np->fields["itemtype"])) {
               if ($item->getFromDB($np->fields["items_id"])) {
                  $ordi = $item->getName();
               }
            }

            $ip2      = $np->fields['ip'];
            $mac2     = $np->fields['mac'];
            $portordi = $np->fields['name'];
         }
         $ip  = $ligne['ip'];
         $mac = $ligne['mac'];

         //inserer ces valeures dans un tableau
         echo "<tr class='tab_bg_1'>";

         if ($port) {
            echo "<td class='center'>$port</td>";
         } else {
            echo "<td> ".NOT_AVAILABLE." </td>";
         }

         if ($ip) {
            echo "<td>$ip</td>";
         } else {
            echo "<td> ".NOT_AVAILABLE." </td>";
         }

         if ($mac) {
            echo "<td>$mac</td>";
         } else {
            echo "<td> ".NOT_AVAILABLE." </td>";
         }

         if ($portordi) {
            echo "<td>$portordi</td>";
         } else {
            echo "<td> ".NOT_AVAILABLE." </td>";
         }

         if ($ip2) {
            echo "<td>$ip2</td>";
         } else {
            echo "<td> ".NOT_AVAILABLE." </td>";
         }

         if ($mac2) {
            echo "<td>$mac2</td>";
         } else {
            echo "<td> ".NOT_AVAILABLE." </td>";
         }

         if ($ordi) {
            echo "<td>$ordi</td>";
         } else {
            echo "<td> ".NOT_AVAILABLE." </td>";
         }
         echo "</tr>\n";
      }
      echo "</table><br><hr><br>";
   }
   Html::footer();

} else  {
   Html::redirect($CFG_GLPI['root_doc']."/front/report.networking.php");
}
?>