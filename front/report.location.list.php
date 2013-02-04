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
* @brief
*/

include ('../inc/includes.php');

Session::checkRight("reports", "r");

if (isset($_POST["locations_id"]) && $_POST["locations_id"]) {
   Html::header(Report::getTypeName(2), $_SERVER['PHP_SELF'], "utils", "report");

   Report::title();

   // Titre
   $name = Dropdown::getDropdownName("glpi_locations",$_POST["locations_id"]);
   echo "<div class='center spaced'><h2>".sprintf(__('Network report by location: %s'),$name).
        "</h2></div>";

   // TODO : must be review at the end of Damien's work
   $query = "SELECT `glpi_netpoints`.`name` AS prise, `glpi_networkports`.`name` AS port,
                    `glpi_ipaddresses`.`name` as ip,
                    `glpi_networkports`.`mac`,
                    `glpi_networkports`.`id` AS IDport, `glpi_locations`.`id`,
                    `glpi_locations`.`completename`
             FROM `glpi_locations`
             INNER JOIN `glpi_netpoints`
                  ON (`glpi_netpoints`.`locations_id` = `glpi_locations`.`id`)
             INNER JOIN `glpi_networkportethernets`
                  ON (`glpi_networkportethernets`.`netpoints_id` = `glpi_netpoints`.`id`)
             INNER JOIN `glpi_networkports`
                  ON (`glpi_networkports`.`id` = `glpi_networkportethernets`.`networkports_id`)
             LEFT JOIN `glpi_networknames`
                  ON (`glpi_networknames`.`itemtype` = 'NetworkPort'
                      AND `glpi_networkports`.`id` = `glpi_networknames`.`items_id`)
             LEFT JOIN `glpi_ipaddresses`
                  ON (`glpi_ipaddresses`.`itemtype` = 'NetworkName'
                      AND `glpi_networknames`.`id` = `glpi_ipaddresses`.`items_id`)
             WHERE ".getRealQueryForTreeItem("glpi_locations",$_POST["locations_id"])."
                   AND `glpi_networkports`.`itemtype` = 'NetworkEquipment'
             ORDER BY `glpi_locations`.`completename`, `glpi_networkports`.`name`";

   $result = $DB->query($query);
   if ($result && $DB->numrows($result)) {
      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr><th>".__('Location')."</th>";
      echo "<th>"._n('Network outlet', 'Network outlets', 2)."</th>";
      echo "<th>".__('Switch')."</th>";
      echo "<th>".__('IP')."</th>";
      echo "<th>".__('Hardware ports')."</th>";
      echo "<th>".__('MAC address')."</th>";
      echo "<th>".__('Device ports')."</th>";
      echo "<th>".__('IP')."</th>";
      echo "<th>".__('MAC address')."</th>";
      echo "<th>".__('Connected devices')."</th></tr>";

      while ($ligne = $DB->fetch_assoc($result)) {
         $lieu              = $ligne["completename"];
         $prise             = $ligne['prise'];
         $port              = $ligne['port'];
         $nw                = new NetworkPort_NetworkPort();
         $networkports_id_1 = $nw->getOppositeContact($ligne['IDport']);
         $np                = new NetworkPort();
         $ordi              = "";
         $ip2               = "";
         $mac2              = "";
         $portordi          = "";

         if ($networkports_id_1 && $np->getFromDB($networkports_id_1)) {
            $ordi = '';
            if ($item = getItemForItemtype($np->fields["itemtype"])) {
               if ($item->getFromDB($np->fields["items_id"])) {
                  $ordi = $item->getName();
               }
            }

            $mac2     = $np->fields['mac'];
            $portordi = $np->fields['name'];
            // TODO get IPs from opposite network port
            $ip2      = '';

         }

         $ip  = $ligne['ip'];
         $mac = $ligne['mac'];

         $np->getFromDB($ligne['IDport']);

         $nd     = new NetworkEquipment();
         $nd->getFromDB($np->fields["items_id"]);
         $switch = $nd->fields["name"];

         //inserer ces valeures dans un tableau
         echo "<tr class='tab_bg_1'>";
         if ($lieu) {
            echo "<td>$lieu</td>";
         } else {
            echo "<td> ".NOT_AVAILABLE." </td>";
         }

         if ($prise) {
            echo "<td>$prise</td>";
         } else {
            echo "<td> ".NOT_AVAILABLE." </td>";
         }

         if ($switch) {
            echo "<td>$switch</td>";
         } else {
            echo "<td> ".NOT_AVAILABLE." </td>";
         }

         if ($ip) {
            echo "<td>$ip</td>";
         } else {
            echo "<td> ".NOT_AVAILABLE." </td>";
         }

         if ($port) {
            echo "<td>$port</td>";
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
      echo "</table></div><br><hr><br>";
   }
   Html::footer();

} else  {
   Html::redirect($CFG_GLPI['root_doc']."/front/report.networking.php");
}
?>