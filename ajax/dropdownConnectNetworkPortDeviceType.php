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

/**
 * @since 0.84
 */

include ('../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkRight("networking", UPDATE);

// Make a select box
if (class_exists($_POST["itemtype"])) {
   $table    = getTableForItemType($_POST["itemtype"]);
   $rand     = mt_rand();

   $toupdate = ['value_fieldname' => 'item',
                'to_update'       => "results_item_$rand",
                'url'             => $CFG_GLPI["root_doc"]."/ajax/dropdownConnectNetworkPort.php",
                'moreparams'      => ['networkports_id'    => $_POST['networkports_id'],
                                      'itemtype'           => $_POST['itemtype'],
                                      'myname'             => $_POST['myname'],
                                      'instantiation_type' => $_POST['instantiation_type']]];
   $params   = ['rand'      => $rand,
                'name'      => "items",
                'entity'    => $_POST["entity_restrict"],
                // Beware: '\n' inside condition is transformed to 'n' in SQL request
                //         so don't cut this SQL request !
                'condition' => "(`id` in (SELECT `items_id`".
                                               "FROM `glpi_networkports`".
                                               "WHERE `itemtype` = '".$_POST["itemtype"]."'".
                                                     "AND `instantiation_type`".
                                                           "= '".$_POST['instantiation_type']."'))",
                'toupdate'  => $toupdate];

   Dropdown::show($_POST['itemtype'], $params);

   echo "<span id='results_item_$rand'>";
   echo "</span>\n";

}
