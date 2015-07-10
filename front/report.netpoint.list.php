<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

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

include ('../inc/includes.php');

Session::checkRight("reports", READ);

if (isset($_POST["prise"]) && $_POST["prise"]) {
   Html::header(Report::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "tools", "report");

   Report::title();

   $name = Dropdown::getDropdownName("glpi_netpoints", $_POST["prise"]);

   // Titre
   echo "<div class='center spaced'><h2>".sprintf(__('Network report by outlet: %s'), $name).
        "</h2></div>";

   Report::reportForNetworkInformations("`glpi_netpoints`
                                         LEFT JOIN `glpi_locations`
                                             ON (`glpi_locations`.`id`
                                                   = `glpi_netpoints`.`locations_id`)
                                         INNER JOIN `glpi_networkportethernets`
                                             ON (`glpi_networkportethernets`.`netpoints_id`
                                                   = `glpi_netpoints`.`id`)",
                                        "PORT_1.`id` = `glpi_networkportethernets`.`networkports_id`",
                                        "`glpi_netpoints`.`id` = '".$_POST["prise"]."'",
                                        '',
                                        "`glpi_locations`.`completename` AS extra,",
                                        Location::getTypeName());

   Html::footer();

} else  {
   Html::redirect($CFG_GLPI['root_doc']."/front/report.networking.php");
}
?>