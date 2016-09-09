<?php
/*
 * @version $Id$
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
* @since version 0.85 (change name before migration_cleaner)
*/

include ('../inc/includes.php');

Session::checkSeveralRightsOr(array("networking" => UPDATE,
                                    "internet"   => UPDATE));

if (!TableExists('glpi_networkportmigrations')) {
   Session::addMessageAfterRedirect(__('You don\'t need the "migration cleaner" tool anymore...'));
   Html::redirect($CFG_GLPI["root_doc"]."/front/central.php");
}

Html::header(__('Migration cleaner'), $_SERVER['PHP_SELF'], "tools","migration");

echo "<div class='spaced' id='tabsbody'>";
echo "<table class='tab_cadre_fixe'>";

echo "<tr><th>" . __('"Migration cleaner" tool') . "</td></tr>";

if (Session::haveRight('internet', UPDATE)
    // Check access to all entities
    && Session::isViewAllEntities()) {
   echo "<tr class='tab_bg_1'><td class='center'>";
   Html::showSimpleForm(IPNetwork::getFormURL(), 'reinit_network',
                        __('Reinit the network topology'));
   echo "</td></tr>";
}
if (Session::haveRight('networking', UPDATE)) {
   echo "<tr class='tab_bg_1'><td class='center'>";
   echo "<a href='".$CFG_GLPI['root_doc']."/front/networkportmigration.php'>".
         __('Clean the network port migration errors') . "</a>";
   echo "</td></tr>";
}
echo "</table>";
echo "</div>";


Html::footer();
?>