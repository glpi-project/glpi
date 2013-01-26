<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2012 by the INDEPNET Development Team.

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

define('GLPI_ROOT', realpath('..'));
include (GLPI_ROOT . "/inc/includes.php");

Session::checkRight("reports", "r");

Html::header(Report::getTypeName(2), $_SERVER['PHP_SELF'], "utils", "report");

Report::title();

# Title

echo "<span class='big b'>GLPI ".Report::getTypeName(2)."</span><br><br>";

# 1. Get some number data

$where = "WHERE `is_deleted` = '0'
                AND `is_template` = '0' ";

$query = "SELECT COUNT(*)
          FROM `glpi_computers`
          $where ".
            getEntitiesRestrictRequest("AND","glpi_computers");
$result              = $DB->query($query);
$number_of_computers = $DB->result($result,0,0);


$query = "SELECT COUNT(*)
          FROM `glpi_softwares`
          $where ".
            getEntitiesRestrictRequest("AND","glpi_softwares");
$result             = $DB->query($query);
$number_of_software = $DB->result($result,0,0);


$query = "SELECT COUNT(*)
          FROM `glpi_printers`
          LEFT JOIN `glpi_computers_items`
             ON (`glpi_computers_items`.`itemtype` = 'Printer'
                 AND `glpi_computers_items`.`items_id` = `glpi_printers`.`id`)
          $where ".
            getEntitiesRestrictRequest("AND","glpi_printers");
$result             = $DB->query($query);
$number_of_printers = $DB->result($result,0,0);


$query = "SELECT COUNT(*)
          FROM `glpi_networkequipments`
          $where ".
            getEntitiesRestrictRequest("AND","glpi_networkequipments");
$result               = $DB->query($query);
$number_of_networking = $DB->result($result,0,0);


$query = "SELECT COUNT(*)
          FROM `glpi_monitors`
          LEFT JOIN `glpi_computers_items`
             ON (`glpi_computers_items`.`itemtype` = 'Monitor'
                 AND `glpi_computers_items`.`items_id` = `glpi_monitors`.`id`)
          $where ".
            getEntitiesRestrictRequest("AND","glpi_monitors");
$result             = $DB->query($query);
$number_of_monitors = $DB->result($result,0,0);


$query = "SELECT COUNT(*)
          FROM `glpi_peripherals`
          LEFT JOIN `glpi_computers_items`
             ON (`glpi_computers_items`.`itemtype` = 'Peripheral'
                 AND `glpi_computers_items`.`items_id` = `glpi_peripherals`.`id`)
          $where '0' ".
            getEntitiesRestrictRequest("AND","glpi_peripherals");
$result                = $DB->query($query);
$number_of_peripherals = $DB->result($result,0,0);


$query = "SELECT COUNT(*)
          FROM `glpi_phones`
          LEFT JOIN `glpi_computers_items`
             ON (`glpi_computers_items`.`itemtype` = 'Phone'
                 AND `glpi_computers_items`.`items_id` = `glpi_phones`.`id`)
          $where ".
            getEntitiesRestrictRequest("AND","glpi_phones");
$result           = $DB->query($query);
$number_of_phones = $DB->result($result,0,0);


# 2. Spew out the data in a table

echo "<table class='tab_cadre' width='80%'>";
echo "<tr class='tab_bg_2'><td>"._n('Computer', 'Computers', 2)."</td>";
echo "<td class='numeric'>$number_of_computers</td></tr>";
echo "<tr class='tab_bg_2'><td>"._n('Printer', 'Printers', 2)."</td>";
echo "<td class='numeric'>$number_of_printers</td></tr>";
echo "<tr class='tab_bg_2'><td>"._n('Network', 'Networks', 2)."</td>";
echo "<td class='numeric'>$number_of_networking</td></tr>";
echo "<tr class='tab_bg_2'><td>"._n('Software', 'Software', 2)."</td>";
echo "<td class='numeric'>$number_of_software</td></tr>";
echo "<tr class='tab_bg_2'><td>"._n('Monitor', 'Monitors', 2)."</td>";
echo "<td class='numeric'>$number_of_monitors </td></tr>";
echo "<tr class='tab_bg_2'><td>"._n('Device', 'Devices', 2)."</td>";
echo "<td class='numeric'>$number_of_peripherals</td></tr>";
echo "<tr class='tab_bg_2'><td>"._n('Phone', 'Phones', 2)."</td>";
echo "<td class='numeric'>$number_of_phones</td></tr>";

echo "<tr><td colspan='2' height=10></td></tr>";
echo "<tr class='tab_bg_1'><td colspan='2' class='b'>".__('Operating system')."</td></tr>";


# 3. Get some more number data (operating systems per computer)

$query = "SELECT COUNT(*) AS count, `glpi_operatingsystems`.`name` AS name
          FROM `glpi_computers`
          LEFT JOIN `glpi_operatingsystems`
             ON (`glpi_computers`.`operatingsystems_id` = `glpi_operatingsystems`.`id`)
          $where ".
            getEntitiesRestrictRequest("AND","glpi_computers")."
          GROUP BY `glpi_operatingsystems`.`name`";
$result = $DB->query($query);

while ($data=$DB->fetch_assoc($result)) {
   if (empty($data['name'])) {
      $data['name'] = Dropdown::EMPTY_VALUE;
   }
   echo "<tr class='tab_bg_2'><td>".$data['name']."</td>";
   echo "<td class='numeric'>".$data['count']."</td></tr>";
}

echo "<tr><td colspan='2' height=10></td></tr>";
echo "<tr class='tab_bg_1'><td colspan='2' class='b'>"._n('Network', 'Networks', 2)."</td></tr>";

# 4. Get some more number data (Networking)

$query = "SELECT COUNT(*) AS count, `glpi_networkequipmenttypes`.`name` AS name
          FROM `glpi_networkequipments`
          LEFT JOIN `glpi_networkequipmenttypes`
             ON (`glpi_networkequipments`.`networkequipmenttypes_id`
                  = `glpi_networkequipmenttypes`.`id`)
          $where ".
              getEntitiesRestrictRequest("AND","glpi_networkequipments")."
          GROUP BY `glpi_networkequipmenttypes`.`name`";
$result = $DB->query($query);

while ($data=$DB->fetch_assoc($result)) {
   if (empty($data['name'])) {
      $data['name'] = Dropdown:: EMPTY_VALUE;
   }
   echo "<tr class='tab_bg_2'><td>".$data['name']."</td>";
   echo "<td class='numeric'>".$data['count']."</td></tr>";
}

echo "<tr><td colspan='2' height=10></td></tr>";
echo "<tr class='tab_bg_1'><td colspan='2' class='b'>"._n('Monitor', 'Monitors', 2)."</td></tr>";

# 4. Get some more number data (Monitor)

$query = "SELECT COUNT(*) AS count, `glpi_monitortypes`.`name` AS name
          FROM `glpi_monitors`
          LEFT JOIN `glpi_monitortypes`
             ON (`glpi_monitors`.`monitortypes_id` = `glpi_monitortypes`.`id`)
          LEFT JOIN `glpi_computers_items`
             ON (`glpi_computers_items`.`itemtype` = 'Monitor'
                 AND `glpi_computers_items`.`items_id` = `glpi_monitors`.`id`)
          $where ".
            getEntitiesRestrictRequest("AND","glpi_monitors")."
          GROUP BY `glpi_monitortypes`.`name`";
$result = $DB->query($query);

while ($data=$DB->fetch_assoc($result)) {
   if (empty($data['name'])) {
      $data['name'] = Dropdown::EMPTY_VALUE;
   }
   echo "<tr class='tab_bg_2'><td>".$data['name']."</td>";
   echo "<td class='numeric'>".$data['count']."</td></tr>";
}

echo "<tr><td colspan='2' height=10></td></tr>";
echo "<tr class='tab_bg_1'><td colspan='2' class='b'>"._n('Printer', 'Printers', 2)."</td></tr>";

# 4. Get some more number data (Printers)

$query = "SELECT COUNT(*) AS count, `glpi_printertypes`.`name` AS name
          FROM `glpi_printers`
          LEFT JOIN `glpi_printertypes`
             ON (`glpi_printers`.`printertypes_id` = `glpi_printertypes`.`id`)
          LEFT JOIN `glpi_computers_items`
             ON (`glpi_computers_items`.`itemtype` = 'Printer'
                 AND `glpi_computers_items`.`items_id` = `glpi_printers`.`id`)
          $where ".
               getEntitiesRestrictRequest("AND","glpi_printers")."
          GROUP BY `glpi_printertypes`.`name`";
$result = $DB->query($query);

while ($data=$DB->fetch_assoc($result)) {
   if (empty($data['name'])) {
      $data['name'] = Dropdown::EMPTY_VALUE;
   }
   echo "<tr class='tab_bg_2'><td>".$data['name']."</td>";
   echo "<td class='numeric'>".$data['count']."</td></tr>";
}

echo "<tr><td colspan='2' height=10></td></tr>";
echo "<tr class='tab_bg_1'><td colspan='2' class='b'>"._n('Device', 'Devices', 2)."</td></tr>";

# 4. Get some more number data (Peripherals)

$query = "SELECT COUNT(*) AS count, `glpi_peripheraltypes`.`name` AS name
          FROM `glpi_peripherals`
          LEFT JOIN `glpi_peripheraltypes`
             ON (`glpi_peripherals`.`peripheraltypes_id` = `glpi_peripheraltypes`.`id`)
          LEFT JOIN `glpi_computers_items`
             ON (`glpi_computers_items`.`itemtype` = 'Peripheral'
                 AND `glpi_computers_items`.`items_id` = `glpi_peripherals`.`id`)
          $where ".
               getEntitiesRestrictRequest("AND","glpi_peripherals")."
          GROUP BY `glpi_peripheraltypes`.`name`";
$result = $DB->query($query);

while ($data=$DB->fetch_assoc($result)) {
   if (empty($data['name'])) {
      $data['name' ]= Dropdown::EMPTY_VALUE;
   }
   echo "<tr class='tab_bg_2'><td>".$data['name']."</td>";
   echo "<td class='numeric'>".$data['count']."</td></tr>";
}

echo "<tr><td colspan='2' height=10></td></tr>";
echo "<tr class='tab_bg_1'><td colspan='2' class='b'>"._n('Phone', 'Phones', 2)."</td></tr>";

# 4. Get some more number data (Peripherals)

$query = "SELECT COUNT(*) AS count, `glpi_phonetypes`.`name` AS name
          FROM `glpi_phones`
          LEFT JOIN `glpi_phonetypes`
             ON (`glpi_phones`.`phonetypes_id` = `glpi_phonetypes`.`id`)
          LEFT JOIN `glpi_computers_items`
             ON (`glpi_computers_items`.`itemtype` = 'Phone'
                 AND `glpi_computers_items`.`items_id` = `glpi_phones`.`id`)
          $where ".
              getEntitiesRestrictRequest("AND","glpi_phones")."
          GROUP BY `glpi_phonetypes`.`name`";
$result = $DB->query($query);

while ($data=$DB->fetch_assoc($result)) {
   if (empty($data['name'])) {
      $data['name'] = Dropdown::EMPTY_VALUE;
   }
   echo "<tr class='tab_bg_2'><td>".$data['name']."</td>";
   echo "<td class='numeric'>".$data['count']."</td></tr>";
}

echo "</table>";

Html::footer();
?>