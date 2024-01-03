<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

/**
 * Filename was previously migration_cleaner.php
 * @since 0.85
 */

/**
 * @var array $CFG_GLPI
 * @var \DBmysql $DB
 */
global $CFG_GLPI, $DB;

include('../inc/includes.php');

Session::checkSeveralRightsOr(["networking" => UPDATE,
    "internet"   => UPDATE
]);

if (!$DB->tableExists('glpi_networkportmigrations')) {
    Session::addMessageAfterRedirect(__('You don\'t need the "migration cleaner" tool anymore...'));
    Html::redirect($CFG_GLPI["root_doc"] . "/front/central.php");
}

Html::header(__('Migration cleaner'), $_SERVER['PHP_SELF'], "tools", "migration");

echo "<div class='spaced' id='tabsbody'>";
echo "<table class='tab_cadre_fixe'>";

echo "<tr><th>" . __('"Migration cleaner" tool') . "</td></tr>";

if (
    Session::haveRight('internet', UPDATE)
    // Check access to all entities
    && Session::canViewAllEntities()
) {
    echo "<tr class='tab_bg_1'><td class='center'>";
    Html::showSimpleForm(
        IPNetwork::getFormURL(),
        'reinit_network',
        __('Reinit the network topology')
    );
    echo "</td></tr>";
}
if (Session::haveRight('networking', UPDATE)) {
    echo "<tr class='tab_bg_1'><td class='center'>";
    echo "<a href='" . $CFG_GLPI['root_doc'] . "/front/networkportmigration.php'>" .
         __('Clean the network port migration errors') . "</a>";
    echo "</td></tr>";
}
echo "</table>";
echo "</div>";


Html::footer();
