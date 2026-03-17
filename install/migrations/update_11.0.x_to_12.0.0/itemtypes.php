<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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
 * @var DBmysql $DB
 * @var Migration $migration
 */

$itemtype_fields_to_expand_notnull = [
    'glpi_alerts' => ['itemtype'],
    'glpi_savedsearches' => ['itemtype'],
    'glpi_savedsearches_users' => ['itemtype'],
    'glpi_certificates_items' => ['itemtype'],
    'glpi_items_softwarelicenses' => ['itemtype'],
    'glpi_items_softwareversions' => ['itemtype'],
    'glpi_contracts_items' => ['itemtype'],
    'glpi_crontasks' => ['itemtype'],
    'glpi_dashboards_rights' => ['itemtype'],
    'glpi_items_devicesimcards' => ['itemtype'],
    'glpi_displaypreferences' => ['itemtype'],
    'glpi_documents_items' => ['itemtype'],
    'glpi_infocoms' => ['itemtype'],
    'glpi_ipaddresses' => ['itemtype'],
    'glpi_links_itemtypes' => ['itemtype'],
    'glpi_networknames' => ['itemtype'],
    'glpi_networkports' => ['itemtype'],
    'glpi_notifications' => ['itemtype'],
    'glpi_notificationtemplates' => ['itemtype'],
    'glpi_objectlocks' => ['itemtype'],
    'glpi_planningrecalls' => ['itemtype'],
    'glpi_registeredids' => ['itemtype', 'device_type'],
    'glpi_reservationitems' => ['itemtype'],
    'glpi_itilsolutions' => ['itemtype'],
    'glpi_knowbaseitems_items' => ['itemtype'],
    'glpi_itilfollowups' => ['itemtype'],
    'glpi_items_kanbans' => ['itemtype'],
    'glpi_domains_items' => ['itemtype'],
    'glpi_appliances_items_relations' => ['itemtype'],
    'glpi_agents' => ['itemtype'],
    'glpi_printerlogs' => ['itemtype'],
    'glpi_itilreminders' => ['itemtype'],
    'glpi_stencils' => ['itemtype'],
    'glpi_itemtranslations_itemtranslations' => ['itemtype'],
];
$itemtype_fields_to_expand_nullable = [
    'glpi_changes_items' => ['itemtype'],
    'glpi_consumables' => ['itemtype'],
    'glpi_dropdowntranslations' => ['itemtype'],
    'glpi_items_problems' => ['itemtype'],
    'glpi_items_processes' => ['itemtype'],
    'glpi_items_environments' => ['itemtype'],
    'glpi_items_projects' => ['itemtype'],
    'glpi_notepads' => ['itemtype'],
    'glpi_projecttaskteams' => ['itemtype'],
    'glpi_projectteams' => ['itemtype'],
    'glpi_queuednotifications' => ['itemtype'],
    'glpi_items_clusters' => ['itemtype'],
    'glpi_vobjects' => ['itemtype'],
    'glpi_rulematchedlogs' => ['itemtype'],
    'glpi_lockedfields' => ['itemtype'],
    'glpi_unmanageds' => ['itemtype'],
    'glpi_refusedequipments' => ['itemtype'],
    'glpi_items_remotemanagements' => ['itemtype'],
    'glpi_items_lines' => ['itemtype'],
    'glpi_searches_criteriafilters' => ['itemtype'],
    'glpi_itilvalidationtemplates_targets' => ['itemtype'],
    'glpi_defaultfilters' => ['itemtype'],
    'glpi_queuedwebhooks' => ['itemtype'],
];
$itemtype_fields_to_expand_empty = [
    'glpi_itils_projects' => ['itemtype'],
    'glpi_logs' => ['itemtype', 'itemtype_link'],
    'glpi_dropdownvisibilities' => ['itemtype', 'visible_itemtype'],
    'glpi_appliances_items' => ['itemtype'],
    'glpi_pendingreasons_items' => ['itemtype'],
    'glpi_databaseinstances' => ['itemtype'],
];

foreach ($itemtype_fields_to_expand_notnull as $table => $fields) {
    foreach ($fields as $field) {
        $migration->changeField($table, $field, $field, 'varchar(255) NOT NULL');
    }
}
foreach ($itemtype_fields_to_expand_nullable as $table => $fields) {
    foreach ($fields as $field) {
        $migration->changeField($table, $field, $field, 'varchar(255) DEFAULT NULL');
    }
}
foreach ($itemtype_fields_to_expand_empty as $table => $fields) {
    foreach ($fields as $field) {
        $migration->changeField($table, $field, $field, "varchar(255) NOT NULL DEFAULT ''");
    }
}
