<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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
 * @var DB $DB
 * @var Migration $migration
 */

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

// Add missing keys (based on glpi:database:check_keys detection)
$missing_keys = [
   'glpi_apiclients' => [
      'entities_id',
      'is_recursive',
   ],
   'glpi_appliances' => [
      'date_mod',
      'is_recursive',
   ],
   'glpi_appliancetypes' => [
      'is_recursive',
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
   ],
   'glpi_computerantiviruses' => [
      'manufacturers_id',
   ],
   'glpi_computervirtualmachines' => [
      'virtualmachinetypes_id',
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
   'glpi_dashboards_rights' => [
      'item' => ['itemtype', 'items_id'],
   ],
   'glpi_datacenters' => [
      'date_creation',
      'date_mod',
   ],
   'glpi_dcrooms' => [
      'date_creation',
      'date_mod',
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
   ],
   'glpi_entities' => [
      'authldaps_id',
      'calendars_id',
      'entities_id_software',
   ],
   'glpi_fieldblacklists' => [
      'entities_id',
      'is_recursive',
   ],
   'glpi_fieldunicities' => [
      'entities_id',
      'is_active',
      'is_recursive',
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
      'date_creation',
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
   ],
   'glpi_links' => [
      'is_recursive',
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
   'glpi_networkportwifis' => [
      'networkportwifis_id',
   ],
   'glpi_objectlocks' => [
      'users_id',
      'date_mod',
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
   ],
   'glpi_pdumodels' => [
      'date_creation',
      'date_mod',
   ],
   'glpi_pdus' => [
      'date_creation',
      'date_mod',
   ],
   'glpi_pdus_plugs' => [
      'date_creation',
      'date_mod',
   ],
   'glpi_pdus_racks' => [
      'date_creation',
      'date_mod',
   ],
   'glpi_printers' => [
      'is_recursive',
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
   ],
   'glpi_recurrentchanges' => [
      'calendars_id',
   ],
   'glpi_registeredids' => [
      'item' => ['itemtype', 'items_id'],
   ],
   'glpi_remindertranslations' => [
      'date_creation',
      'date_mod',
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
   'glpi_states' => [
      'entities_id',
      'is_recursive',
   ],
   'glpi_suppliers' => [
      'is_recursive',
   ],
   'glpi_ticketrecurrents' => [
      'calendars_id',
   ],
   'glpi_tickets_tickets' => [
      'tickets_id_2',
   ],
   'glpi_users' => [
      'auths_id',
      'default_requesttypes_id',
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
