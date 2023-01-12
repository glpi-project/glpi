<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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
 * @var DB $DB
 * @var Migration $migration
 */

$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

// Remove the `NOT NULL` flag of comment fields and fix collation
$tables = [
    'glpi_apiclients',
    'glpi_applianceenvironments',
    'glpi_appliances',
    'glpi_appliancetypes',
    'glpi_devicesimcards',
    'glpi_knowbaseitems_comments',
    'glpi_lines',
    'glpi_rulerightparameters',
    'glpi_ssovariables',
    'glpi_virtualmachinestates',
    'glpi_virtualmachinesystems',
    'glpi_virtualmachinetypes',
];
foreach ($tables as $table) {
    $migration->changeField($table, 'comment', 'comment', 'text');
}

// Add `DEFAULT CURRENT_TIMESTAMP` to some date fields
$tables = [
    'glpi_alerts',
    'glpi_crontasklogs',
    'glpi_notimportedemails',
];
foreach ($tables as $table) {
    $migration->changeField($table, 'date', 'date', 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP');
}

// Fix charset for glpi_notimportedemails table
$migration->addPreQuery(
    sprintf(
        'ALTER TABLE %s CONVERT TO CHARACTER SET %s COLLATE %s',
        $DB->quoteName('glpi_notimportedemails'),
        DBConnection::getDefaultCharset(),
        DBConnection::getDefaultCollation()
    )
);
// Put back `subject` type to text (charset convertion changed it from text to mediumtext)
$migration->changeField('glpi_notimportedemails', 'subject', 'subject', 'text', ['nodefault' => true]);

// Drop malformed keys
$malformed_keys = [
    'glpi_ipaddresses' => [
        'textual',
    ],
    'glpi_items_softwareversions' => [
        'is_deleted',
        'is_template',
    ],
    'glpi_registeredids' => [
        'item',
    ],
];
foreach ($malformed_keys as $table => $keys) {
    foreach ($keys as $key) {
        $migration->dropKey($table, $key);
        $migration->migrationOneTable($table);
    }
}

// Drop useless keys
$useless_keys = [
    'glpi_appliances_items' => [
        'appliances_id',
    ],
    'glpi_appliances_items_relations' => [
        'itemtype',
        'items_id',
    ],
    'glpi_certificates_items' => [
        'device',
    ],
    'glpi_changetemplatehiddenfields' => [
        'changetemplates_id',
    ],
    'glpi_changetemplatemandatoryfields' => [
        'changetemplates_id',
    ],
    'glpi_contracts_items' => [
        'FK_device',
    ],
    'glpi_dashboards_rights' => [
        'dashboards_dashboards_id',
    ],
    'glpi_domains_items' => [
        'domains_id',
        'FK_device',
    ],
    'glpi_dropdowntranslations' => [
        'typeid'
    ],
    'glpi_entities' => [
        'entities_id',
    ],
    'glpi_impactrelations' => [
        'source_asset',
    ],
    'glpi_ipaddresses_ipnetworks' => [
        'ipaddresses_id',
    ],
    'glpi_items_devicebatteries' => [
        'computers_id',
    ],
    'glpi_items_devicecases' => [
        'computers_id',
    ],
    'glpi_items_devicecontrols' => [
        'computers_id',
    ],
    'glpi_items_devicedrives' => [
        'computers_id',
    ],
    'glpi_items_devicefirmwares' => [
        'computers_id',
    ],
    'glpi_items_devicegenerics' => [
        'computers_id',
    ],
    'glpi_items_devicegraphiccards' => [
        'computers_id',
    ],
    'glpi_items_deviceharddrives' => [
        'computers_id',
    ],
    'glpi_items_devicememories' => [
        'computers_id',
    ],
    'glpi_items_devicemotherboards' => [
        'computers_id',
    ],
    'glpi_items_devicenetworkcards' => [
        'computers_id',
    ],
    'glpi_items_devicepcis' => [
        'computers_id',
    ],
    'glpi_items_devicepowersupplies' => [
        'computers_id',
    ],
    'glpi_items_deviceprocessors' => [
        'computers_id',
    ],
    'glpi_items_devicesensors' => [
        'computers_id',
    ],
    'glpi_items_devicesoundcards' => [
        'computers_id',
    ],
    'glpi_items_disks' => [
        'itemtype',
        'items_id',
    ],
    'glpi_items_operatingsystems' => [
        'items_id',
    ],
    'glpi_items_softwarelicenses' => [
        'itemtype',
        'items_id',
    ],
    'glpi_items_softwareversions' => [
        'item',
        'itemtype',
        'items_id',
    ],
    'glpi_itilfollowups' => [
        'itemtype',
        'item_id',
    ],
    'glpi_itilsolutions' => [
        'itemtype',
        'item_id',
    ],
    'glpi_knowbaseitemcategories' => [
        'entities_id',
    ],
    'glpi_knowbaseitems_items' => [
        'item',
        'itemtype',
        'item_id',
    ],
    'glpi_networknames' => [
        'name',
    ],
    'glpi_networkports' => [
        'on_device',
    ],
    'glpi_notifications_notificationtemplates' => [
        'notifications_id',
    ],
    'glpi_olalevels_tickets' => [
        'tickets_id',
    ],
    'glpi_problemtemplatehiddenfields' => [
        'problemtemplates_id',
    ],
    'glpi_problemtemplatemandatoryfields' => [
        'problemtemplates_id',
    ],
    'glpi_reservations' => [
        'reservationitems_id',
    ],
    'glpi_slalevels_tickets' => [
        'tickets_id',
    ],
    'glpi_tickettemplatehiddenfields' => [
        'tickettemplates_id',
    ],
    'glpi_tickettemplatemandatoryfields' => [
        'tickettemplates_id',
    ],
];
foreach ($useless_keys as $table => $keys) {
    foreach ($keys as $key) {
        $migration->dropKey($table, $key);
        $migration->migrationOneTable($table);
    }
}

// Add missing keys (based on tools:check_database_keys detection)
$missing_keys = [
    'glpi_apiclients' => [
        'entities_id',
        'is_recursive',
        'name',
    ],
    'glpi_appliances' => [
        'date_mod',
        'is_recursive',
    ],
    'glpi_appliancetypes' => [
        'is_recursive',
    ],
    'glpi_authldapreplicates' => [
        'name',
    ],
    'glpi_authldaps' => [
        'name',
    ],
    'glpi_authmails' => [
        'name',
    ],
    'glpi_blacklistedmailcontents' => [
        'name',
    ],
    'glpi_businesscriticities' => [
        'entities_id',
        'is_recursive',
    ],
    'glpi_calendarsegments' => [
        'entities_id',
        'is_recursive',
    ],
    'glpi_cartridgeitems' => [
        'is_recursive',
    ],
    'glpi_certificates' => [
        'is_recursive',
    ],
    'glpi_clusters' => [
        'date_creation',
        'date_mod',
        'name',
    ],
    'glpi_computerantiviruses' => [
        'manufacturers_id',
    ],
    'glpi_computervirtualmachines' => [
        'virtualmachinetypes_id',
    ],
    'glpi_configs' => [
        'name',
    ],
    'glpi_consumableitems' => [
        'is_recursive',
    ],
    'glpi_contacts' => [
        'is_recursive',
    ],
    'glpi_contracts' => [
        'is_recursive',
        'is_template',
    ],
    'glpi_crontasks' => [
        'name',
    ],
    'glpi_dashboards_dashboards' => [
        'name',
    ],
    'glpi_dashboards_rights' => [
        'item' => ['itemtype', 'items_id'],
    ],
    'glpi_datacenters' => [
        'date_creation',
        'date_mod',
        'name',
    ],
    'glpi_dcrooms' => [
        'date_creation',
        'date_mod',
        'name',
    ],
    'glpi_devicesensors' => [
        'devicesensormodels_id',
    ],
    'glpi_documents' => [
        'is_recursive',
    ],
    'glpi_documents_items' => [
        'entities_id',
        'is_recursive',
        'date_mod',
    ],
    'glpi_domainrecords' => [
        'is_recursive',
    ],
    'glpi_domainrecordtypes' => [
        'entities_id',
        'is_recursive',
    ],
    'glpi_domainrelations' => [
        'entities_id',
        'is_recursive',
    ],
    'glpi_domains' => [
        'is_recursive',
    ],
    'glpi_domaintypes' => [
        'entities_id',
        'is_recursive',
    ],
    'glpi_enclosures' => [
        'date_creation',
        'date_mod',
        'name',
    ],
    'glpi_entities' => [
        'authldaps_id',
        'calendars_id',
        'entities_id_software',
        'name',
    ],
    'glpi_fieldblacklists' => [
        'entities_id',
        'is_recursive',
    ],
    'glpi_fieldunicities' => [
        'entities_id',
        'is_active',
        'is_recursive',
        'name',
    ],
    'glpi_groups' => [
        'is_recursive',
    ],
    'glpi_groups_users' => [
        'is_dynamic',
    ],
    'glpi_holidays' => [
        'entities_id',
        'is_recursive',
    ],
    'glpi_impactcompounds' => [
        'name',
    ],
    'glpi_ipaddresses' => [
        'name',
    ],
    'glpi_ipnetworks' => [
        'ipnetworks_id',
        'is_recursive',
    ],
    'glpi_ipnetworks_vlans' => [
        'vlans_id',
    ],
    'glpi_items_devicebatteries' => [
        'locations_id',
        'states_id',
    ],
    'glpi_items_devicefirmwares' => [
        'locations_id',
        'states_id',
    ],
    'glpi_items_devicegenerics' => [
        'locations_id',
        'states_id',
    ],
    'glpi_items_devicesensors' => [
        'locations_id',
        'states_id',
    ],
    'glpi_items_kanbans' => [
        'users_id',
        'date_creation',
        'date_mod',
    ],
    'glpi_items_operatingsystems' => [
        'date_creation',
        'date_mod',
    ],
    'glpi_items_softwareversions' => [
        'is_deleted',
        'is_deleted_item',
        'is_template_item',
    ],
    'glpi_itilsolutions' => [
        'date_creation',
        'date_mod',
    ],
    'glpi_knowbaseitemcategories' => [
        'knowbaseitemcategories_id',
    ],
    'glpi_knowbaseitems_comments' => [
        'knowbaseitems_id',
        'parent_comment_id',
        'users_id',
        'date_creation',
        'date_mod',
    ],
    'glpi_knowbaseitems_items' => [
        'knowbaseitems_id',
        'date_creation',
        'date_mod',
    ],
    'glpi_knowbaseitems_revisions' => [
        'users_id',
    ],
    'glpi_knowbaseitemtranslations' => [
        'date_creation',
        'date_mod',
    ],
    'glpi_lines' => [
        'is_deleted',
        'groups_id',
        'linetypes_id',
        'locations_id',
        'states_id',
        'date_creation',
        'date_mod',
        'name',
    ],
    'glpi_links' => [
        'is_recursive',
        'name',
    ],
    'glpi_mailcollectors' => [
        'name',
    ],
    'glpi_manuallinks' => [
        'name',
    ],
    'glpi_monitors' => [
        'date_mod',
    ],
    'glpi_networkaliases' => [
        'fqdns_id',
    ],
    'glpi_networkequipments' => [
        'is_recursive',
    ],
    'glpi_networkports' => [
        'name',
    ],
    'glpi_networkportwifis' => [
        'networkportwifis_id',
    ],
    'glpi_objectlocks' => [
        'users_id',
    ],
    'glpi_olalevels' => [
        'entities_id',
        'is_recursive',
    ],
    'glpi_olas' => [
        'entities_id',
        'is_recursive',
    ],
    'glpi_operatingsystemeditions' => [
        'date_creation',
        'date_mod',
    ],
    'glpi_operatingsystemkernels' => [
        'date_creation',
        'date_mod',
    ],
    'glpi_operatingsystemkernelversions' => [
        'date_creation',
        'date_mod',
    ],
    'glpi_passivedcequipments' => [
        'date_creation',
        'date_mod',
        'name',
    ],
    'glpi_pdumodels' => [
        'date_creation',
        'date_mod',
    ],
    'glpi_pdus' => [
        'date_creation',
        'date_mod',
        'name',
    ],
    'glpi_pdus_plugs' => [
        'date_creation',
        'date_mod',
    ],
    'glpi_pdus_racks' => [
        'date_creation',
        'date_mod',
    ],
    'glpi_planningexternalevents' => [
        'name',
    ],
    'glpi_planningexternaleventtemplates' => [
        'name',
    ],
    'glpi_plugins' => [
        'name',
    ],
    'glpi_printers' => [
        'is_recursive',
    ],
    'glpi_profilerights' => [
        'name',
    ],
    'glpi_profiles' => [
        'name',
    ],
    'glpi_projects' => [
        'is_deleted',
    ],
    'glpi_queuednotifications' => [
        'notificationtemplates_id',
    ],
    'glpi_rackmodels' => [
        'date_creation',
        'date_mod',
    ],
    'glpi_racks' => [
        'date_creation',
        'date_mod',
        'name',
    ],
    'glpi_recurrentchanges' => [
        'calendars_id',
        'name',
    ],
    'glpi_refusedequipments' => [
        'name',
    ],
    'glpi_registeredids' => [
        'item' => ['itemtype', 'items_id'],
    ],
    'glpi_remindertranslations' => [
        'date_creation',
        'date_mod',
    ],
    'glpi_reminders' => [
        'name',
    ],
    'glpi_rulerightparameters' => [
        'name',
    ],
    'glpi_rules' => [
        'name',
    ],
    'glpi_savedsearches' => [
        'name',
    ],
    'glpi_softwarecategories' => [
        'name',
    ],
    'glpi_softwarelicenses' => [
        'is_recursive',
        'softwarelicenses_id',
    ],
    'glpi_softwarelicensetypes' => [
        'entities_id',
        'is_recursive',
    ],
    'glpi_softwares' => [
        'is_recursive',
    ],
    'glpi_slalevels' => [
        'entities_id',
        'is_recursive',
    ],
    'glpi_slas' => [
        'entities_id',
        'is_recursive',
    ],
    'glpi_ssovariables' => [
        'name',
    ],
    'glpi_states' => [
        'entities_id',
        'is_recursive',
    ],
    'glpi_suppliers' => [
        'is_recursive',
    ],
    'glpi_ticketrecurrents' => [
        'calendars_id',
        'name',
    ],
    'glpi_tickets_tickets' => [
        'tickets_id_2',
    ],
    'glpi_transfers' => [
        'name',
    ],
    'glpi_users' => [
        'auths_id',
        'default_requesttypes_id',
    ],
    'glpi_virtualmachinestates' => [
        'name',
    ],
    'glpi_virtualmachinesystems' => [
        'name',
    ],
    'glpi_virtualmachinetypes' => [
        'name',
    ],
    'glpi_vlans' => [
        'is_recursive',
    ],
    'glpi_wifinetworks' => [
        'is_recursive',
    ],
];
foreach ($missing_keys as $table => $fields) {
    foreach ($fields as $key => $field) {
        $migration->addKey($table, $field, is_numeric($key) ? '' : $key);
    }
}

// Add missing `date_creation` field on tables that already have `date_mod` field
$tables = [
    'glpi_apiclients',
    'glpi_appliances',
    'glpi_authmails',
    'glpi_transfers',
];
foreach ($tables as $table) {
    $migration->addField($table, 'date_creation', 'timestamp');
    $migration->addKey($table, 'date_creation');
}

// Add missing `date_mod` field on tables that already have `date_creation` field
$tables = [
    'glpi_lockedfields',
];
foreach ($tables as $table) {
    $migration->addField($table, 'date_mod', 'timestamp');
    $migration->addKey($table, 'date_mod');
}

// Rename `date` fields to `date_creation` when value is just a DB insert timestamp
$tables = [
    'glpi_knowbaseitems',
    'glpi_notepads',
    'glpi_projecttasks',
];
foreach ($tables as $table) {
    if ($DB->fieldExists($table, 'date', false)) {
        $migration->dropKey($table, 'date');
        $migration->migrationOneTable($table);
        $migration->changeField($table, 'date', 'date_creation', 'timestamp');
        $migration->addKey($table, 'date_creation');
    }
}
$migration->changeSearchOption(KnowbaseItem::class, 5, 121);
$migration->changeSearchOption(ProjectTask::class, 15, 121);

// Rename `glpi_objectlocks` `date_mod` to `date`
if ($DB->fieldExists('glpi_objectlocks', 'date_mod', false)) {
    $migration->dropKey('glpi_objectlocks', 'date_mod');
    $migration->migrationOneTable('glpi_objectlocks');
    $migration->changeField('glpi_objectlocks', 'date_mod', 'date', 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP');
    $migration->addKey('glpi_objectlocks', 'date');
}

// Rename `date_creation` to `date` when field refers to a valuable date and not just to db insert timestamps
$tables = [
    'glpi_knowbaseitems_revisions',
    'glpi_networkportconnectionlogs',
];
foreach ($tables as $table) {
    if ($DB->fieldExists($table, 'date_creation', false)) {
        $migration->dropKey($table, 'date_creation');
        $migration->migrationOneTable($table);
        $migration->changeField($table, 'date_creation', 'date', 'timestamp');
        $migration->addKey($table, 'date');
    }
}

// Replace -1 default values on entities_id foreign keys (visibility tables)
$tables = [
    'glpi_groups_knowbaseitems',
    'glpi_groups_reminders',
    'glpi_groups_rssfeeds',
    'glpi_knowbaseitems_profiles',
    'glpi_profiles_reminders',
    'glpi_profiles_rssfeeds',
];
foreach ($tables as $table) {
    $migration->addField($table, 'no_entity_restriction', 'boolean', ['update' => 0]);
    $migration->migrationOneTable($table); // Ensure 'no_entity_restriction' is created
    $DB->updateOrDie(
        $table,
        ['entities_id' => 0, 'no_entity_restriction' => 1],
        ['entities_id' => -1]
    );
    $migration->changeField($table, 'entities_id', 'entities_id', "int {$default_key_sign} DEFAULT NULL");
    $migration->migrationOneTable($table); // Ensure 'entities_id' is nullable
    $DB->updateOrDie(
        $table,
        ['entities_id' => 'NULL'],
        ['no_entity_restriction' => 1]
    );
}

// Replace -1 default values on glpi_rules.entities_id
$DB->updateOrDie(
    'glpi_rules',
    ['entities_id' => 0],
    ['entities_id' => -1]
);

// Replace unused -1 default values on entities_id foreign keys
$tables = [
    'glpi_fieldunicities',
    'glpi_savedsearches',
];
foreach ($tables as $table) {
    $migration->changeField($table, 'entities_id', 'entities_id', "int {$default_key_sign} NOT NULL DEFAULT 0");
}

// Replace -1 default values on glpi_queuednotifications.items_id
$DB->updateOrDie(
    'glpi_queuednotifications',
    ['items_id' => 0],
    ['items_id' => -1]
);
