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

use Doctrine\DBAL\Schema\SchemaException;

\Glpi\DBAL\DB::establishConnection();

/**
 * @param Migration $migration
 * @return array
 * @throws SchemaException
 */
function getTables(Migration $migration)
{
    $tables = [];

    $tables[] = $migration->buildTable('glpi_alerts')
        ->withID()
        ->addString('itemtype', 100)
        ->addIDReference('items_id')
        ->addInteger('type', ['notnull' => true, 'default' => 0, 'comment' => 'see define.php ALERT_* constant'])
        ->addDateTime('date', ['notnull' => true, 'default' => 'CURRENT_TIMESTAMP'])
        ->addUniqueIndex('unicity', ['itemtype', 'items_id', 'type'])
        ->addIndex('type', ['type'])
        ->addIndex('date', ['date']);

    $tables[] = $migration->buildTable('glpi_authldapreplicates')
        ->withID()
        ->addIDReference('authldaps_id')
        ->addNullableString('host')
        ->addInteger('port', ['default' => 389])
        ->addNullableString('name')
        ->addInteger('timeout', ['default' => 10])
        ->addIndex('name', ['name'])
        ->addIndex('authldaps_id', ['authldaps_id']);

    $tables[] = $migration->buildTable('glpi_authldaps')
        ->withID()
        ->addNullableString('name')
        ->addNullableString('host')
        ->addNullableString('basedn')
        ->addNullableString('rootdn')
        ->addInteger('port', ['default' => 389])
        ->addText('condition')
        ->addNullableString('login_field', 255, ['default' => 'uid'])
        ->addNullableString('sync_field')
        ->addBoolean('use_tls', ['default' => 0])
        ->addNullableString('group_field')
        ->addText('group_condition')
        ->addInteger('group_search_type', ['default' => 0])
        ->addNullableString('group_member_field')
        ->addNullableString('email1_field')
        ->addNullableString('realname_field')
        ->addNullableString('firstname_field')
        ->addNullableString('phone_field')
        ->addNullableString('phone2_field')
        ->addNullableString('mobile_field')
        ->addNullableString('comment_field')
        ->addBoolean('use_dn', ['default' => 1])
        ->addInteger('time_offset', ['default' => 0, 'comment' => 'in seconds'])
        ->addInteger('deref_option', ['default' => 0])
        ->addNullableString('title_field')
        ->addNullableString('category_field')
        ->addNullableString('language_field')
        ->addDateTime('date_mod', ['notnull' => false, 'default' => null])
        ->addText('comment')
        ->addBoolean('is_default', ['default' => 0])
        ->addBoolean('is_active', ['default' => 0])
        ->addNullableString('rootdn_passwd')
        ->addNullableString('registration_number_field')
        ->addNullableString('email2_field')
        ->addNullableString('email3_field')
        ->addNullableString('email4_field')
        ->addNullableString('location_field')
        ->addNullableString('responsible_field')
        ->addInteger('pagesize', ['default' => 0])
        ->addInteger('ldap_maxlimit', ['default' => 0])
        ->addBoolean('can_support_pagesize', ['default' => 0])
        ->addNullableString('picture_field')
        ->addNullableString('begin_date_field')
        ->addNullableString('end_date_field')
        ->addDateTime('date_creation')
        ->addNullableString('inventory_domain')
        ->addText('tls_certfile')
        ->addText('tls_keyfile')
        ->addBoolean('use_bind', ['default' => 1])
        ->addInteger('timeout', ['default' => 10])
        ->addNullableString('tls_version', 10)
        ->addIndex('name', ['name'])
        ->addIndex('date_mod', ['date_mod'])
        ->addIndex('is_default', ['is_default'])
        ->addIndex('is_active', ['is_active'])
        ->addIndex('date_creation', ['date_creation'])
        ->addIndex('sync_field', ['sync_field']);

    $tables[] = $migration->buildTable('glpi_authmails')
        ->withID()
        ->withCreateAndModDates()
        ->addNullableString('name')
        ->addNullableString('connect_string')
        ->addNullableString('host')
        ->addNullableText('comment')
        ->addBoolean('is_active', ['default' => 0])
        ->addIndex('name', ['name'])
        ->addIndex('is_active', ['is_active']);

    $tables[] = $migration->buildTable('glpi_apiclients')
        ->withID()
        ->withEntity()
        ->withCreateAndModDates()
        ->addNullableString('name')
        ->addBoolean('is_active')
        ->addBigInteger('ipv4_range_start', ['notnull' => false, 'default' => null])
        ->addBigInteger('ipv4_range_end', ['notnull' => false, 'default' => null])
        ->addNullableString('ipv6')
        ->addNullableString('app_token')
        ->addDateTime('app_token_date',  ['notnull' => false, 'default' => null])
        ->addTinyInteger('dolog_method', ['default' => 0])
        ->addNullableText('comment')
        ->addIndex('name', ['name'])
        ->addIndex('is_active', ['is_active']);

    $tables[] = $migration->buildTable('glpi_autoupdatesystems')
        ->withID()
        ->addNullableString('name')
        ->addNullableText('comment')
        ->addIndex('name', ['name']);

    $tables[] = $migration->buildTable('glpi_blacklistedmailcontents')
        ->withID()
        ->withCreateAndModDates()
        ->addNullableString('name')
        ->addNullableText('content')
        ->addNullableText('comment')
        ->addIndex('name', ['name']);

    $tables[] = $migration->buildTable('glpi_blacklists')
        ->withID()
        ->withCreateAndModDates()
        ->addInteger('type', ['default' => 0])
        ->addNullableString('name')
        ->addNullableString('value')
        ->addNullableText('comment')
        ->addIndex('type', ['type'])
        ->addIndex('name', ['name']);

    $tables[] = $migration->buildTable('glpi_savedsearches')
        ->withID()
        ->withEntity()
        ->addNullableString('name')
        ->addInteger('type', ['default' => 0, 'comment' => 'see SavedSearch:: constants'])
        ->addString('itemtype', 100)
        ->addIDReference('users_id')
        ->addBoolean('is_private', ['default' => 1])
        ->addNullableText('query')
        ->addInteger('last_execution_time', ['notnull' => false, 'default' => null])
        ->addTinyInteger('do_count', ['default' => 2, 'comment' => 'Do or do not count results on list display see SavedSearch::COUNT_* constants'])
        ->addDateTime('last_execution_date', ['notnull' => false, 'default' => null])
        ->addInteger('counter', ['default' => 0])
        ->addIndex('name', ['name'])
        ->addIndex('type', ['type'])
        ->addIndex('itemtype', ['itemtype'])
        ->addIndex('users_id', ['users_id'])
        ->addIndex('is_private', ['is_private'])
        ->addIndex('last_execution_time', ['last_execution_time'])
        ->addIndex('last_execution_date', ['last_execution_date'])
        ->addIndex('do_count', ['do_count']);

    $tables[] = $migration->buildTable('glpi_savedsearches_users')
        ->withID()
        ->addIDReference('users_id')
        ->addString('itemtype')
        ->addIDReference('savedsearches_id')
        ->addUniqueIndex('unicity', ['users_id', 'itemtype'])
        ->addIndex('savedsearches_id', ['savedsearches_id']);

    $tables[] = $migration->buildTable('glpi_savedsearches_alerts')
        ->withID()
        ->withCreateAndModDates()
        ->addIDReference('savedsearches_id')
        ->addNullableString('name')
        ->addBoolean('is_active', ['default' => 0])
        ->addTinyInteger('operator')
        ->addInteger('value')
        ->addInteger('frequency', ['default' => 0])
        ->addUniqueIndex('unicity', ['savedsearches_id', 'operator', 'value'])
        ->addIndex('name', ['name'])
        ->addIndex('is_active', ['is_active']);

//    $tables[] = $migration->buildTable('glpi_budgets')
//        ->withID();
//
//    $tables[] = $migration->buildTable('glpi_budgettypes')
//        ->withID();
//
//    $tables[] = $migration->buildTable('glpi_businesscriticities')
//        ->withID();
//
//    $tables[] = $migration->buildTable('glpi_calendars')
//        ->withID();
//
//    $tables[] = $migration->buildTable('glpi_calendars_holidays');
//
//    $tables[] = $migration->buildTable('glpi_calendarsegments');
//
//    $tables[] = $migration->buildTable('glpi_cartridgeitems');
//
//    $tables[] = $migration->buildTable('glpi_printers_cartridgeinfos');
//
//    $tables[] = $migration->buildTable('glpi_cartridgeitems_printermodels');
//
//    $tables[] = $migration->buildTable('glpi_cartridgeitemtypes');
//
//    $tables[] = $migration->buildTable('glpi_cartridges');
//
//    $tables[] = $migration->buildTable('glpi_certificates');
//
//    $tables[] = $migration->buildTable('glpi_certificates_items');
//
//    $tables[] = $migration->buildTable('glpi_certificatetypes');
//
//    $tables[] = $migration->buildTable('glpi_changecosts');
//
//    $tables[] = $migration->buildTable('glpi_changes');
//
//    $tables[] = $migration->buildTable('glpi_changes_groups');
//
//    $tables[] = $migration->buildTable('glpi_changes_items');
//
//    $tables[] = $migration->buildTable('glpi_changes_problems');
//
//    $tables[] = $migration->buildTable('glpi_changes_suppliers');
//
//    $tables[] = $migration->buildTable('glpi_changes_tickets');
//
//    $tables[] = $migration->buildTable('glpi_changes_users');
//
//    $tables[] = $migration->buildTable('glpi_changetasks');
//
//    $tables[] = $migration->buildTable('glpi_changevalidations');
//
//    $tables[] = $migration->buildTable('glpi_computerantiviruses');
//
//    $tables[] = $migration->buildTable('glpi_items_disks');
//
//    $tables[] = $migration->buildTable('glpi_computermodels');
//
//    $tables[] = $migration->buildTable('glpi_computers');
//
//    $tables[] = $migration->buildTable('glpi_computers_items');
//
//    $tables[] = $migration->buildTable('glpi_items_softwarelicenses');
//
//    $tables[] = $migration->buildTable('glpi_items_softwareversions');
//
//    $tables[] = $migration->buildTable('glpi_computertypes');
//
//    $tables[] = $migration->buildTable('glpi_computervirtualmachines');
//
//    $tables[] = $migration->buildTable('glpi_items_operatingsystems');
//
//    $tables[] = $migration->buildTable('glpi_operatingsystemkernels');
//
//    $tables[] = $migration->buildTable('glpi_operatingsystemkernelversions');
//
//    $tables[] = $migration->buildTable('glpi_operatingsystemeditions');
//
//    $tables[] = $migration->buildTable('glpi_configs');
//
//    $tables[] = $migration->buildTable('glpi_impactrelations');
//
//    $tables[] = $migration->buildTable('glpi_impactcompounds');
//
//    $tables[] = $migration->buildTable('glpi_impactitems');
//
//    $tables[] = $migration->buildTable('glpi_impactcontexts');
//
//    $tables[] = $migration->buildTable('glpi_consumableitems');
//
//    $tables[] = $migration->buildTable('glpi_consumableitemtypes');
//
//    $tables[] = $migration->buildTable('glpi_consumables');
//
//    $tables[] = $migration->buildTable('glpi_contacts');
//
//    $tables[] = $migration->buildTable('glpi_contacts_suppliers');
//
//    $tables[] = $migration->buildTable('glpi_contacttypes');
//
//    $tables[] = $migration->buildTable('glpi_contractcosts');
//
//    $tables[] = $migration->buildTable('glpi_contracts');
//
//    $tables[] = $migration->buildTable('glpi_contracts_items');
//
//    $tables[] = $migration->buildTable('glpi_contracts_suppliers');
//
//    $tables[] = $migration->buildTable('glpi_contracttypes');
//
//    $tables[] = $migration->buildTable('glpi_crontasklogs');
//
//    $tables[] = $migration->buildTable('glpi_crontasks');
//
//    $tables[] = $migration->buildTable('glpi_dashboards_dashboards');
//
//    $tables[] = $migration->buildTable('glpi_dashboards_filters');
//
//    $tables[] = $migration->buildTable('glpi_dashboards_items');
//
//    $tables[] = $migration->buildTable('glpi_dashboards_rights');
//
//    $tables[] = $migration->buildTable('glpi_devicecasemodels');
//
//    $tables[] = $migration->buildTable('glpi_devicecases');
//
//    $tables[] = $migration->buildTable('glpi_devicecasetypes');
//
//    $tables[] = $migration->buildTable('glpi_devicecontrolmodels');
//
//    $tables[] = $migration->buildTable('glpi_devicecontrols');
//
//    $tables[] = $migration->buildTable('glpi_devicedrivemodels');
//
//    $tables[] = $migration->buildTable('glpi_devicedrives');
//
//    $tables[] = $migration->buildTable('glpi_devicegenericmodels');
//
//    $tables[] = $migration->buildTable('glpi_devicegenerics');
//
//    $tables[] = $migration->buildTable('glpi_devicegenerictypes');
//
//    $tables[] = $migration->buildTable('glpi_devicegraphiccardmodels');
//
//    $tables[] = $migration->buildTable('glpi_devicegraphiccards');
//
//    $tables[] = $migration->buildTable('glpi_deviceharddrivemodels');
//
//    $tables[] = $migration->buildTable('glpi_deviceharddrives');
//
//    $tables[] = $migration->buildTable('glpi_devicecameras');
//
//    $tables[] = $migration->buildTable('glpi_items_devicecameras');
//
//    $tables[] = $migration->buildTable('glpi_devicecameramodels');
//
//    $tables[] = $migration->buildTable('glpi_imageformats');
//
//    $tables[] = $migration->buildTable('glpi_imageresolutions');
//
//    $tables[] = $migration->buildTable('glpi_items_devicecameras_imageformats');
//
//    $tables[] = $migration->buildTable('glpi_items_devicecameras_imageresolutions');
//
//    $tables[] = $migration->buildTable('glpi_devicememorymodels');
//
//    $tables[] = $migration->buildTable('glpi_devicememories');
//
//    $tables[] = $migration->buildTable('glpi_devicememorytypes');
//
//    $tables[] = $migration->buildTable('glpi_devicemotherboardmodels');
//
//    $tables[] = $migration->buildTable('glpi_devicemotherboards');
//
//    $tables[] = $migration->buildTable('glpi_devicenetworkcardmodels');
//
//    $tables[] = $migration->buildTable('glpi_devicenetworkcards');
//
//    $tables[] = $migration->buildTable('glpi_devicepcimodels');
//
//    $tables[] = $migration->buildTable('glpi_devicepcis');
//
//    $tables[] = $migration->buildTable('glpi_devicepowersupplymodels');
//
//    $tables[] = $migration->buildTable('glpi_devicepowersupplies');
//
//    $tables[] = $migration->buildTable('glpi_deviceprocessormodels');
//
//    $tables[] = $migration->buildTable('glpi_deviceprocessors');
//
//    $tables[] = $migration->buildTable('glpi_devicesensors');
//
//    $tables[] = $migration->buildTable('glpi_devicesensormodels');
//
//    $tables[] = $migration->buildTable('glpi_devicesensortypes');
//
//    $tables[] = $migration->buildTable('glpi_devicesimcards');
//
//    $tables[] = $migration->buildTable('glpi_items_devicesimcards');
//
//    $tables[] = $migration->buildTable('glpi_devicesimcardtypes');
//
//    $tables[] = $migration->buildTable('glpi_devicesoundcardmodels');
//
//    $tables[] = $migration->buildTable('glpi_devicesoundcards');
//
//    $tables[] = $migration->buildTable('glpi_displaypreferences');
//
//    $tables[] = $migration->buildTable('glpi_documentcategories');
//
//    $tables[] = $migration->buildTable('glpi_documents');
//
//    $tables[] = $migration->buildTable('glpi_documents_items');
//
//    $tables[] = $migration->buildTable('glpi_documenttypes');
//
//    $tables[] = $migration->buildTable('glpi_domains');
//
//    $tables[] = $migration->buildTable('glpi_dropdowntranslations');
//
//    $tables[] = $migration->buildTable('glpi_entities');
//
//    $tables[] = $migration->buildTable('glpi_entities_knowbaseitems');
//
//    $tables[] = $migration->buildTable('glpi_entities_reminders');
//
//    $tables[] = $migration->buildTable('glpi_entities_rssfeeds');
//
//    $tables[] = $migration->buildTable('glpi_events');
//
//    $tables[] = $migration->buildTable('glpi_fieldblacklists');
//
//    $tables[] = $migration->buildTable('glpi_fieldunicities');
//
//    $tables[] = $migration->buildTable('glpi_filesystems');
//
//    $tables[] = $migration->buildTable('glpi_fqdns');
//
//    $tables[] = $migration->buildTable('glpi_groups');
//
//    $tables[] = $migration->buildTable('glpi_groups_knowbaseitems');
//
//    $tables[] = $migration->buildTable('glpi_groups_problems');
//
//    $tables[] = $migration->buildTable('glpi_groups_reminders');
//
//    $tables[] = $migration->buildTable('glpi_groups_rssfeeds');
//
//    $tables[] = $migration->buildTable('glpi_groups_tickets');
//
//    $tables[] = $migration->buildTable('glpi_groups_users');
//
//    $tables[] = $migration->buildTable('glpi_holidays');
//
//    $tables[] = $migration->buildTable('glpi_infocoms');
//
//    $tables[] = $migration->buildTable('glpi_interfacetypes');
//
//    $tables[] = $migration->buildTable('glpi_ipaddresses');
//
//    $tables[] = $migration->buildTable('glpi_ipaddresses_ipnetworks');
//
//    $tables[] = $migration->buildTable('glpi_ipnetworks');
//
//    $tables[] = $migration->buildTable('glpi_ipnetworks_vlans');
//
//    $tables[] = $migration->buildTable('glpi_items_devicecases');
//
//    $tables[] = $migration->buildTable('glpi_items_devicecontrols');
//
//    $tables[] = $migration->buildTable('glpi_items_devicedrives');
//
//    $tables[] = $migration->buildTable('glpi_items_devicegenerics');
//
//    $tables[] = $migration->buildTable('glpi_items_devicegraphiccards');
//
//    $tables[] = $migration->buildTable('glpi_items_deviceharddrives');
//
//    $tables[] = $migration->buildTable('glpi_items_devicememories');
//
//    $tables[] = $migration->buildTable('glpi_items_devicemotherboards');
//
//    $tables[] = $migration->buildTable('glpi_items_devicenetworkcards');
//
//    $tables[] = $migration->buildTable('glpi_items_devicepcis');
//
//    $tables[] = $migration->buildTable('glpi_items_devicepowersupplies');
//
//    $tables[] = $migration->buildTable('glpi_items_deviceprocessors');
//
//    $tables[] = $migration->buildTable('glpi_items_devicesensors');
//
//    $tables[] = $migration->buildTable('glpi_items_devicesoundcards');
//
//    $tables[] = $migration->buildTable('glpi_items_problems');
//
//    $tables[] = $migration->buildTable('glpi_items_processes');
//
//    $tables[] = $migration->buildTable('glpi_items_environments');
//
//    $tables[] = $migration->buildTable('glpi_items_projects');
//
//    $tables[] = $migration->buildTable('glpi_items_tickets');
//
//    $tables[] = $migration->buildTable('glpi_itilcategories');
//
//    $tables[] = $migration->buildTable('glpi_itils_projects');
//
//    $tables[] = $migration->buildTable('glpi_knowbaseitemcategories');
//
//    $tables[] = $migration->buildTable('glpi_knowbaseitems');
//
//    $tables[] = $migration->buildTable('glpi_knowbaseitems_knowbaseitemcategories');
//
//    $tables[] = $migration->buildTable('glpi_knowbaseitems_profiles');
//
//    $tables[] = $migration->buildTable('glpi_knowbaseitems_users');
//
//    $tables[] = $migration->buildTable('glpi_knowbaseitemtranslations');
//
//    $tables[] = $migration->buildTable('glpi_lines');
//
//    $tables[] = $migration->buildTable('glpi_lineoperators');
//
//    $tables[] = $migration->buildTable('glpi_linetypes');
//
//    $tables[] = $migration->buildTable('glpi_links');
//
//    $tables[] = $migration->buildTable('glpi_links_itemtypes');
//
//    $tables[] = $migration->buildTable('glpi_locations');
//
//    $tables[] = $migration->buildTable('glpi_logs');
//
//    $tables[] = $migration->buildTable('glpi_mailcollectors');
//
//    $tables[] = $migration->buildTable('glpi_manufacturers');
//
//    $tables[] = $migration->buildTable('glpi_monitormodels');
//
//    $tables[] = $migration->buildTable('glpi_monitors');
//
//    $tables[] = $migration->buildTable('glpi_monitortypes');
//
//    $tables[] = $migration->buildTable('glpi_sockets');
//
//    $tables[] = $migration->buildTable('glpi_cables');
//
//    $tables[] = $migration->buildTable('glpi_cabletypes');
//
//    $tables[] = $migration->buildTable('glpi_cablestrands');
//
//    $tables[] = $migration->buildTable('glpi_socketmodels');
//
//    $tables[] = $migration->buildTable('glpi_networkaliases');
//
//    $tables[] = $migration->buildTable('glpi_networkequipmentmodels');
//
//    $tables[] = $migration->buildTable('glpi_networkequipments');
//
//    $tables[] = $migration->buildTable('glpi_networkequipmenttypes');
//
//    $tables[] = $migration->buildTable('glpi_networkinterfaces');
//
//    $tables[] = $migration->buildTable('glpi_networknames');
//
//    $tables[] = $migration->buildTable('glpi_networkportaggregates');
//
//    $tables[] = $migration->buildTable('glpi_networkportaliases');
//
//    $tables[] = $migration->buildTable('glpi_networkportdialups');
//
//    $tables[] = $migration->buildTable('glpi_networkportethernets');
//
//    $tables[] = $migration->buildTable('glpi_networkportfiberchanneltypes');
//
//    $tables[] = $migration->buildTable('glpi_networkportfiberchannels');
//
//    $tables[] = $migration->buildTable('glpi_networkportlocals');
//
//    $tables[] = $migration->buildTable('glpi_networkports');
//
//    $tables[] = $migration->buildTable('glpi_networkports_networkports');
//
//    $tables[] = $migration->buildTable('glpi_networkports_vlans');
//
//    $tables[] = $migration->buildTable('glpi_networkportwifis');
//
//    $tables[] = $migration->buildTable('glpi_networks');
//
//    $tables[] = $migration->buildTable('glpi_notepads');
//
//    $tables[] = $migration->buildTable('glpi_notifications');
//
//    $tables[] = $migration->buildTable('glpi_notifications_notificationtemplates');
//
//    $tables[] = $migration->buildTable('glpi_notificationtargets');
//
//    $tables[] = $migration->buildTable('glpi_notificationtemplates');
//
//    $tables[] = $migration->buildTable('glpi_notificationtemplatetranslations');
//
//    $tables[] = $migration->buildTable('glpi_notimportedemails');
//
//    $tables[] = $migration->buildTable('glpi_objectlocks');
//
//    $tables[] = $migration->buildTable('glpi_operatingsystemarchitectures');
//
//    $tables[] = $migration->buildTable('glpi_operatingsystems');
//
//    $tables[] = $migration->buildTable('glpi_operatingsystemservicepacks');
//
//    $tables[] = $migration->buildTable('glpi_operatingsystemversions');
//
//    $tables[] = $migration->buildTable('glpi_passivedcequipments');
//
//    $tables[] = $migration->buildTable('glpi_passivedcequipmentmodels');
//
//    $tables[] = $migration->buildTable('glpi_passivedcequipmenttypes');
//
//    $tables[] = $migration->buildTable('glpi_peripheralmodels');
//
//    $tables[] = $migration->buildTable('glpi_peripherals');
//
//    $tables[] = $migration->buildTable('glpi_peripheraltypes');
//
//    $tables[] = $migration->buildTable('glpi_phonemodels');
//
//    $tables[] = $migration->buildTable('glpi_phonepowersupplies');
//
//    $tables[] = $migration->buildTable('glpi_phones');
//
//    $tables[] = $migration->buildTable('glpi_phonetypes');
//
//    $tables[] = $migration->buildTable('glpi_planningrecalls');
//
//    $tables[] = $migration->buildTable('glpi_plugins');
//
//    $tables[] = $migration->buildTable('glpi_printermodels');
//
//    $tables[] = $migration->buildTable('glpi_printers');
//
//    $tables[] = $migration->buildTable('glpi_printertypes');
//
//    $tables[] = $migration->buildTable('glpi_problemcosts');
//
//    $tables[] = $migration->buildTable('glpi_problems');
//
//    $tables[] = $migration->buildTable('glpi_problems_suppliers');
//
//    $tables[] = $migration->buildTable('glpi_problems_tickets');
//
//    $tables[] = $migration->buildTable('glpi_problems_users');
//
//    $tables[] = $migration->buildTable('glpi_problemtasks');
//
//    $tables[] = $migration->buildTable('glpi_profilerights');
//
//    $tables[] = $migration->buildTable('glpi_profiles');
//
//    $tables[] = $migration->buildTable('glpi_profiles_reminders');
//
//    $tables[] = $migration->buildTable('glpi_profiles_rssfeeds');
//
//    $tables[] = $migration->buildTable('glpi_profiles_users');
//
//    $tables[] = $migration->buildTable('glpi_projectcosts');
//
//    $tables[] = $migration->buildTable('glpi_projects');
//
//    $tables[] = $migration->buildTable('glpi_projectstates');
//
//    $tables[] = $migration->buildTable('glpi_projecttasks');
//
//    $tables[] = $migration->buildTable('glpi_projecttasklinks');
//
//    $tables[] = $migration->buildTable('glpi_projecttasktemplates');
//
//    $tables[] = $migration->buildTable('glpi_projecttasks_tickets');
//
//    $tables[] = $migration->buildTable('glpi_projecttaskteams');
//
//    $tables[] = $migration->buildTable('glpi_projecttasktypes');
//
//    $tables[] = $migration->buildTable('glpi_projectteams');
//
//    $tables[] = $migration->buildTable('glpi_projecttypes');
//
//    $tables[] = $migration->buildTable('glpi_queuednotifications');
//
//    $tables[] = $migration->buildTable('glpi_registeredids');
//
//    $tables[] = $migration->buildTable('glpi_reminders');
//
//    $tables[] = $migration->buildTable('glpi_remindertranslations');
//
//    $tables[] = $migration->buildTable('glpi_reminders_users');
//
//    $tables[] = $migration->buildTable('glpi_requesttypes');
//
//    $tables[] = $migration->buildTable('glpi_reservationitems');
//
//    $tables[] = $migration->buildTable('glpi_reservations');
//
//    $tables[] = $migration->buildTable('glpi_rssfeeds');
//
//    $tables[] = $migration->buildTable('glpi_rssfeeds_users');
//
//    $tables[] = $migration->buildTable('glpi_ruleactions');
//
//    $tables[] = $migration->buildTable('glpi_rulecriterias');
//
//    $tables[] = $migration->buildTable('glpi_rulerightparameters');
//
//    $tables[] = $migration->buildTable('glpi_rules');
//
//    $tables[] = $migration->buildTable('glpi_slalevelactions');
//
//    $tables[] = $migration->buildTable('glpi_slalevelcriterias');
//
//    $tables[] = $migration->buildTable('glpi_slalevels');
//
//    $tables[] = $migration->buildTable('glpi_slalevels_tickets');
//
//    $tables[] = $migration->buildTable('glpi_olalevelactions');
//
//    $tables[] = $migration->buildTable('glpi_olalevelcriterias');
//
//    $tables[] = $migration->buildTable('glpi_olalevels');
//
//    $tables[] = $migration->buildTable('glpi_olalevels_tickets');
//
//    $tables[] = $migration->buildTable('glpi_slms');
//
//    $tables[] = $migration->buildTable('glpi_slas');
//
//    $tables[] = $migration->buildTable('glpi_olas');
//
//    $tables[] = $migration->buildTable('glpi_softwarecategories');
//
//    $tables[] = $migration->buildTable('glpi_softwarelicenses');
//
//    $tables[] = $migration->buildTable('glpi_softwarelicensetypes');
//
//    $tables[] = $migration->buildTable('glpi_softwares');
//
//    $tables[] = $migration->buildTable('glpi_softwareversions');
//
//    $tables[] = $migration->buildTable('glpi_solutiontemplates');
//
//    $tables[] = $migration->buildTable('glpi_solutiontypes');
//
//    $tables[] = $migration->buildTable('glpi_itilsolutions');
//
//    $tables[] = $migration->buildTable('glpi_ssovariables');
//
//    $tables[] = $migration->buildTable('glpi_states');
//
//    $tables[] = $migration->buildTable('glpi_suppliers');
//
//    $tables[] = $migration->buildTable('glpi_suppliers_tickets');
//
//    $tables[] = $migration->buildTable('glpi_suppliertypes');
//
//    $tables[] = $migration->buildTable('glpi_taskcategories');
//
//    $tables[] = $migration->buildTable('glpi_tasktemplates');
//
//    $tables[] = $migration->buildTable('glpi_ticketcosts');
//
//    $tables[] = $migration->buildTable('glpi_ticketrecurrents');
//
//    $tables[] = $migration->buildTable('glpi_recurrentchanges');
//
//    $tables[] = $migration->buildTable('glpi_tickets');
//
//    $tables[] = $migration->buildTable('glpi_tickets_tickets');
//
//    $tables[] = $migration->buildTable('glpi_tickets_users');
//
//    $tables[] = $migration->buildTable('glpi_ticketsatisfactions');
//
//    $tables[] = $migration->buildTable('glpi_tickettasks');
//
//    $tables[] = $migration->buildTable('glpi_tickettemplatehiddenfields');
//
//    $tables[] = $migration->buildTable('glpi_changetemplatehiddenfields');
//
//    $tables[] = $migration->buildTable('glpi_problemtemplatehiddenfields');
//
//    $tables[] = $migration->buildTable('glpi_tickettemplatereadonlyfields');
//
//    $tables[] = $migration->buildTable('glpi_changetemplatereadonlyfields');
//
//    $tables[] = $migration->buildTable('glpi_problemtemplatereadonlyfields');
//
//    $tables[] = $migration->buildTable('glpi_tickettemplatemandatoryfields');
//
//    $tables[] = $migration->buildTable('glpi_changetemplatemandatoryfields');
//
//    $tables[] = $migration->buildTable('glpi_problemtemplatemandatoryfields');
//
//    $tables[] = $migration->buildTable('glpi_tickettemplatepredefinedfields');
//
//    $tables[] = $migration->buildTable('glpi_changetemplatepredefinedfields');
//
//    $tables[] = $migration->buildTable('glpi_problemtemplatepredefinedfields');
//
//    $tables[] = $migration->buildTable('glpi_tickettemplates');
//
//    $tables[] = $migration->buildTable('glpi_changetemplates');
//
//    $tables[] = $migration->buildTable('glpi_problemtemplates');
//
//    $tables[] = $migration->buildTable('glpi_ticketvalidations');
//
//    $tables[] = $migration->buildTable('glpi_transfers');
//
//    $tables[] = $migration->buildTable('glpi_usercategories');
//
//    $tables[] = $migration->buildTable('glpi_useremails');
//
//    $tables[] = $migration->buildTable('glpi_users');
//
//    $tables[] = $migration->buildTable('glpi_usertitles');
//
//    $tables[] = $migration->buildTable('glpi_virtualmachinestates');
//
//    $tables[] = $migration->buildTable('glpi_virtualmachinesystems');
//
//    $tables[] = $migration->buildTable('glpi_virtualmachinetypes');
//
//    $tables[] = $migration->buildTable('glpi_vlans');
//
//    $tables[] = $migration->buildTable('glpi_wifinetworks');
//
//    $tables[] = $migration->buildTable('glpi_knowbaseitems_items');
//
//    $tables[] = $migration->buildTable('glpi_knowbaseitems_revisions');
//
//    $tables[] = $migration->buildTable('glpi_knowbaseitems_comments');
//
//    $tables[] = $migration->buildTable('glpi_devicebatterymodels');
//
//    $tables[] = $migration->buildTable('glpi_devicebatteries');
//
//    $tables[] = $migration->buildTable('glpi_items_devicebatteries');
//
//    $tables[] = $migration->buildTable('glpi_devicebatterytypes');
//
//    $tables[] = $migration->buildTable('glpi_devicefirmwaremodels');
//
//    $tables[] = $migration->buildTable('glpi_devicefirmwares');
//
//    $tables[] = $migration->buildTable('glpi_items_devicefirmwares');
//
//    $tables[] = $migration->buildTable('glpi_devicefirmwaretypes');
//
//    $tables[] = $migration->buildTable('glpi_datacenters');
//
//    $tables[] = $migration->buildTable('glpi_dcrooms');
//
//    $tables[] = $migration->buildTable('glpi_rackmodels');
//
//    $tables[] = $migration->buildTable('glpi_racktypes');
//
//    $tables[] = $migration->buildTable('glpi_racks');
//
//    $tables[] = $migration->buildTable('glpi_items_racks');
//
//    $tables[] = $migration->buildTable('glpi_enclosuremodels');
//
//    $tables[] = $migration->buildTable('glpi_enclosures');
//
//    $tables[] = $migration->buildTable('glpi_items_enclosures');
//
//    $tables[] = $migration->buildTable('glpi_pdumodels');
//
//    $tables[] = $migration->buildTable('glpi_pdutypes');
//
//    $tables[] = $migration->buildTable('glpi_pdus');
//
//    $tables[] = $migration->buildTable('glpi_plugs');
//
//    $tables[] = $migration->buildTable('glpi_pdus_plugs');
//
//    $tables[] = $migration->buildTable('glpi_pdus_racks');
//
//    $tables[] = $migration->buildTable('glpi_itilfollowuptemplates');
//
//    $tables[] = $migration->buildTable('glpi_itilfollowups');
//
//    $tables[] = $migration->buildTable('glpi_clustertypes');
//
//    $tables[] = $migration->buildTable('glpi_clusters');
//
//    $tables[] = $migration->buildTable('glpi_items_clusters');
//
//    $tables[] = $migration->buildTable('glpi_planningexternalevents');
//
//    $tables[] = $migration->buildTable('glpi_planningexternaleventtemplates');
//
//    $tables[] = $migration->buildTable('glpi_planningeventcategories');
//
//    $tables[] = $migration->buildTable('glpi_items_kanbans');
//
//    $tables[] = $migration->buildTable('glpi_vobjects');
//
//    $tables[] = $migration->buildTable('glpi_domaintypes');
//
//    $tables[] = $migration->buildTable('glpi_domainrelations');
//
//    $tables[] = $migration->buildTable('glpi_domains_items');
//
//    $tables[] = $migration->buildTable('glpi_domainrecordtypes');
//
//    $tables[] = $migration->buildTable('glpi_domainrecords');
//
//    $tables[] = $migration->buildTable('glpi_appliances');
//
//    $tables[] = $migration->buildTable('glpi_appliances_items');
//
//    $tables[] = $migration->buildTable('glpi_appliancetypes');
//
//    $tables[] = $migration->buildTable('glpi_applianceenvironments');
//
//    $tables[] = $migration->buildTable('glpi_appliances_items_relations');
//
//    $tables[] = $migration->buildTable('glpi_agenttypes');
//
//    $tables[] = $migration->buildTable('glpi_agents');
//
//    $tables[] = $migration->buildTable('glpi_rulematchedlogs');
//
//    $tables[] = $migration->buildTable('glpi_lockedfields');
//
//    $tables[] = $migration->buildTable('glpi_unmanageds');
//
//    $tables[] = $migration->buildTable('glpi_networkporttypes');
//
//    $tables[] = $migration->buildTable('glpi_printerlogs');
//
//    $tables[] = $migration->buildTable('glpi_networkportconnectionlogs');
//
//    $tables[] = $migration->buildTable('glpi_networkportmetrics');
//
//    $tables[] = $migration->buildTable('glpi_refusedequipments');
//
//    $tables[] = $migration->buildTable('glpi_usbvendors');
//
//    $tables[] = $migration->buildTable('glpi_pcivendors');
//
//    $tables[] = $migration->buildTable('glpi_items_remotemanagements');
//
//    $tables[] = $migration->buildTable('glpi_pendingreasons');
//
//    $tables[] = $migration->buildTable('glpi_pendingreasons_items');
//
//    $tables[] = $migration->buildTable('glpi_manuallinks');
//
//    $tables[] = $migration->buildTable('glpi_tickets_contracts');
//
//    $tables[] = $migration->buildTable('glpi_databaseinstancetypes');
//
//    $tables[] = $migration->buildTable('glpi_databaseinstancecategories');
//
//    $tables[] = $migration->buildTable('glpi_databaseinstances');
//
//    $tables[] = $migration->buildTable('glpi_databases');
//
//    $tables[] = $migration->buildTable('glpi_snmpcredentials');
//
//    $tables[] = $migration->buildTable('glpi_items_ticketrecurrents');
//
//    $tables[] = $migration->buildTable('glpi_items_lines');
//
//    $tables[] = $migration->buildTable('glpi_changes_changes');
//
//    $tables[] = $migration->buildTable('glpi_problems_problems');
//
//    $tables[] = $migration->buildTable('glpi_changesatisfactions');
//
//    $tables[] = $migration->buildTable('glpi_oauth_access_tokens');
//
//    $tables[] = $migration->buildTable('glpi_oauth_refresh_tokens');
//
//    $tables[] = $migration->buildTable('glpi_oauth_auth_codes');
//
//    $tables[] = $migration->buildTable('glpi_oauthclients');
//
//    $tables[] = $migration->buildTable('glpi_validatorsubstitutes');
//
//    $tables[] = $migration->buildTable('glpi_searches_criteriafilters');
//
//    $tables[] = $migration->buildTable('glpi_itilvalidationtemplates');
//
//    $tables[] = $migration->buildTable('glpi_itilvalidationtemplates_targets');
//
//    $tables[] = $migration->buildTable('glpi_itilreminders');

    return $tables;
}

$migration = new Migration(GLPI_VERSION);
return getTables($migration);
