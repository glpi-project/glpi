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

if (!defined('GLPI_ROOT')) {
   define('GLPI_ROOT', realpath('..'));
}

include_once (GLPI_ROOT . "/inc/based_config.php");
include_once (GLPI_ROOT . "/inc/db.function.php");

/**
 * Creates all GLPI tables
 * @since 10.0.0
 */
function createTables() {
   $table_schema = new DBTableSchema();

   $table_schema->init('glpi_alerts')
      ->addField('itemtype', 'varchar(100)', ['value' => null, 'nodefault' => true])
      ->addField('items_id', 'int', ['value' => '0'])
      ->addIndexedField('type', 'int', ['value' => '0', 'nodefault' => false, 'comment' => 'see define.php ALERT_* constant'])
      ->addIndexedField('date', 'timestamp', ['value' => null, 'nodefault' => true])
      ->addUniqueKey('unicity', ['itemtype', 'items_id', 'type'])
      ->createOrDie(true);

   $table_schema->init('glpi_authldapreplicates')
      ->addIndexedField('authldaps_id', 'int', ['value' => '0'])
      ->addField('host', 'string')
      ->addField('port', 'int', ['value' => '389'])
      ->addField('name', 'string')
      ->createOrDie(true);

   $table_schema->init('glpi_authldaps')
      ->addField('name', 'string')
      ->addField('host', 'string')
      ->addField('basedn', 'string')
      ->addField('rootdn', 'string')
      ->addField('port', 'int', ['value' => '389'])
      ->addField('condition', 'text')
      ->addField('login_field', 'string', ['value' => 'uid'])
      ->addIndexedField('sync_field', 'string')
      ->addField('use_tls', 'boolean', ['value' => '0'])
      ->addField('group_field', 'string')
      ->addField('group_condition', 'text')
      ->addField('group_search_type', 'int', ['value' => '0'])
      ->addField('group_member_field', 'string')
      ->addField('email1_field', 'string')
      ->addField('realname_field', 'string')
      ->addField('firstname_field', 'string')
      ->addField('phone_field', 'string')
      ->addField('phone2_field', 'string')
      ->addField('mobile_field', 'string')
      ->addField('comment_field', 'string')
      ->addField('use_dn', 'boolean', ['value' => '1'])
      ->addField('time_offset', 'int', ['value' => '0', 'nodefault' => false, 'comment' => 'in seconds'])
      ->addField('deref_option', 'int', ['value' => '0'])
      ->addField('title_field', 'string')
      ->addField('category_field', 'string')
      ->addField('language_field', 'string')
      ->addField('entity_field', 'string')
      ->addField('entity_condition', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addField('comment', 'text')
      ->addIndexedField('is_default', 'boolean', ['value' => '0'])
      ->addIndexedField('is_active', 'boolean', ['value' => '0'])
      ->addField('rootdn_passwd', 'string')
      ->addField('registration_number_field', 'string')
      ->addField('email2_field', 'string')
      ->addField('email3_field', 'string')
      ->addField('email4_field', 'string')
      ->addField('location_field', 'string')
      ->addField('responsible_field', 'string')
      ->addField('pagesize', 'int', ['value' => '0'])
      ->addField('ldap_maxlimit', 'int', ['value' => '0'])
      ->addField('can_support_pagesize', 'boolean', ['value' => '0'])
      ->addField('picture_field', 'string')
      ->addIndexedField('date_creation', 'timestamp')
      ->addField('inventory_domain', 'string')
      ->createOrDie(true);

   $table_schema->init('glpi_authmails')
      ->addField('name', 'string')
      ->addField('connect_string', 'string')
      ->addField('host', 'string')
      ->addIndexedField('date_mod', 'timestamp')
      ->addField('comment', 'text')
      ->addIndexedField('is_active', 'boolean', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_apiclients')
      ->addField('entities_id', 'int', ['value' => '0'])
      ->addField('is_recursive', 'boolean', ['value' => '0'])
      ->addField('name', 'string')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('is_active', 'boolean', ['value' => '0'])
      ->addField('ipv4_range_start', 'bigint(20)')
      ->addField('ipv4_range_end', 'bigint(20)')
      ->addField('ipv6', 'string')
      ->addField('app_token', 'string')
      ->addField('app_token_date', 'timestamp')
      ->addField('dolog_method', 'byte', ['value' => '0'])
      ->addField('comment', 'text')
      ->createOrDie(true);

   $table_schema->init('glpi_autoupdatesystems')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->createOrDie(true);

   $table_schema->init('glpi_blacklistedmailcontents')
      ->addField('name', 'string')
      ->addField('content', 'text')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_blacklists')
      ->addIndexedField('type', 'int', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addField('value', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_budgets')
      ->addIndexedField('name', 'string')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addField('comment', 'text')
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('begin_date', 'date')
      ->addIndexedField('end_date', 'date')
      ->addField('value', 'decimal(20,4)', ['value' => '0.0000'])
      ->addIndexedField('is_template', 'boolean', ['value' => '0'])
      ->addField('template_name', 'string')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->addIndexedField('locations_id', 'int', ['value' => '0'])
      ->addIndexedField('budgettypes_id', 'int', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_budgettypes')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_businesscriticities')
      ->addIndexedField('name', 'string')
      ->addField('entities_id', 'int', ['value' => '0'])
      ->addField('is_recursive', 'boolean', ['value' => '0'])
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->addField('businesscriticities_id', 'int', ['value' => '0'])
      ->addField('completename', 'text')
      ->addField('level', 'int', ['value' => '0'])
      ->addField('ancestors_cache', 'longtext')
      ->addField('sons_cache', 'longtext')
      ->addUniqueKey('unicity', ['businesscriticities_id', 'name'])
      ->createOrDie(true);

   $table_schema->init('glpi_calendars')
      ->addIndexedField('name', 'string')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addField('cache_duration', 'text')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_calendarsegments')
      ->addIndexedField('calendars_id', 'int', ['value' => '0'])
      ->addField('entities_id', 'int', ['value' => '0'])
      ->addField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('day', 'boolean', ['value' => '1', 'nodefault' => false, 'comment' => 'numer of the day based on date(w)'])
      ->addField('begin', 'time')
      ->addField('end', 'time')
      ->createOrDie(true);

   $table_schema->init('glpi_calendars_holidays')
      ->addField('calendars_id', 'int', ['value' => '0'])
      ->addIndexedField('holidays_id', 'int', ['value' => '0'])
      ->addUniqueKey('unicity', ['calendars_id', 'holidays_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_cartridgeitems')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addField('ref', 'string')
      ->addIndexedField('locations_id', 'int', ['value' => '0'])
      ->addIndexedField('cartridgeitemtypes_id', 'int', ['value' => '0'])
      ->addIndexedField('manufacturers_id', 'int', ['value' => '0'])
      ->addIndexedField('users_id_tech', 'int', ['value' => '0'])
      ->addIndexedField('groups_id_tech', 'int', ['value' => '0'])
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addField('comment', 'text')
      ->addIndexedField('alarm_threshold', 'int', ['value' => '10'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_cartridgeitems_printermodels')
      ->addIndexedField('cartridgeitems_id', 'int', ['value' => '0'])
      ->addField('printermodels_id', 'int', ['value' => '0'])
      ->addUniqueKey('unicity', ['printermodels_id', 'cartridgeitems_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_cartridgeitemtypes')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_cartridges')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('cartridgeitems_id', 'int', ['value' => '0'])
      ->addIndexedField('printers_id', 'int', ['value' => '0'])
      ->addField('date_in', 'date')
      ->addField('date_use', 'date')
      ->addField('date_out', 'date')
      ->addField('pages', 'int', ['value' => '0'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_certificates')
      ->addIndexedField('name', 'string')
      ->addField('serial', 'string')
      ->addField('otherserial', 'string')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addField('is_recursive', 'boolean', ['value' => '0'])
      ->addField('comment', 'text')
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('is_template', 'boolean', ['value' => '0'])
      ->addField('template_name', 'string')
      ->addIndexedField('certificatetypes_id', 'int', ['value' => '0', 'nodefault' => false, 'comment' => 'RELATION to glpi_certificatetypes (id)'])
      ->addField('dns_name', 'string')
      ->addField('dns_suffix', 'string')
      ->addIndexedField('users_id_tech', 'int', ['value' => '0', 'nodefault' => false, 'comment' => 'RELATION to glpi_users (id)'])
      ->addIndexedField('groups_id_tech', 'int', ['value' => '0', 'nodefault' => false, 'comment' => 'RELATION to glpi_groups (id)'])
      ->addIndexedField('locations_id', 'int', ['value' => '0', 'nodefault' => false, 'comment' => 'RELATION to glpi_locations (id)'])
      ->addIndexedField('manufacturers_id', 'int', ['value' => '0', 'nodefault' => false, 'comment' => 'RELATION to glpi_manufacturers (id)'])
      ->addField('contact', 'string')
      ->addField('contact_num', 'string')
      ->addIndexedField('users_id', 'int', ['value' => '0'])
      ->addIndexedField('groups_id', 'int', ['value' => '0'])
      ->addField('is_autosign', 'boolean', ['value' => '0'])
      ->addField('date_expiration', 'date')
      ->addIndexedField('states_id', 'int', ['value' => '0', 'nodefault' => false, 'comment' => 'RELATION to states (id)'])
      ->addField('command', 'text')
      ->addField('certificate_request', 'text')
      ->addField('certificate_item', 'text')
      ->addIndexedField('date_creation', 'timestamp')
      ->addIndexedField('date_mod', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_certificates_items')
      ->addField('certificates_id', 'int', ['value' => '0'])
      ->addField('items_id', 'int', ['value' => '0', 'nodefault' => false, 'comment' => 'RELATION to various tables, according to itemtype (id)'])
      ->addField('itemtype', 'varchar(100)', ['value' => null, 'nodefault' => true, 'comment' => 'see .class.php file'])
      ->addIndexedField('date_creation', 'timestamp')
      ->addIndexedField('date_mod', 'timestamp')
      ->addUniqueKey('unicity', ['certificates_id', 'itemtype', 'items_id'])
      ->addKey('device', ['items_id', 'itemtype'])
      ->addKey('item', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_certificatetypes')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_creation', 'timestamp')
      ->addIndexedField('date_mod', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_changecosts')
      ->addIndexedField('changes_id', 'int', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('begin_date', 'date')
      ->addIndexedField('end_date', 'date')
      ->addField('actiontime', 'int', ['value' => '0'])
      ->addField('cost_time', 'decimal(20,4)', ['value' => '0.0000'])
      ->addField('cost_fixed', 'decimal(20,4)', ['value' => '0.0000'])
      ->addField('cost_material', 'decimal(20,4)', ['value' => '0.0000'])
      ->addIndexedField('budgets_id', 'int', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_changes')
      ->addIndexedField('name', 'string')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('status', 'int', ['value' => '1'])
      ->addField('content', 'longtext')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date', 'timestamp')
      ->addIndexedField('solvedate', 'timestamp')
      ->addIndexedField('closedate', 'timestamp')
      ->addIndexedField('time_to_resolve', 'timestamp')
      ->addIndexedField('users_id_recipient', 'int', ['value' => '0'])
      ->addIndexedField('users_id_lastupdater', 'int', ['value' => '0'])
      ->addIndexedField('urgency', 'int', ['value' => '1'])
      ->addIndexedField('impact', 'int', ['value' => '1'])
      ->addIndexedField('priority', 'int', ['value' => '1'])
      ->addIndexedField('itilcategories_id', 'int', ['value' => '0'])
      ->addField('impactcontent', 'longtext')
      ->addField('controlistcontent', 'longtext')
      ->addField('rolloutplancontent', 'longtext')
      ->addField('backoutplancontent', 'longtext')
      ->addField('checklistcontent', 'longtext')
      ->addIndexedField('global_validation', 'int', ['value' => '1'])
      ->addField('validation_percent', 'int', ['value' => '0'])
      ->addField('actiontime', 'int', ['value' => '0'])
      ->addField('begin_waiting_date', 'timestamp')
      ->addField('waiting_duration', 'int', ['value' => '0'])
      ->addField('close_delay_stat', 'int', ['value' => '0'])
      ->addField('solve_delay_stat', 'int', ['value' => '0'])
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_changes_groups')
      ->addField('changes_id', 'int', ['value' => '0'])
      ->addField('groups_id', 'int', ['value' => '0'])
      ->addField('type', 'int', ['value' => '1'])
      ->addUniqueKey('unicity', ['changes_id', 'type', 'groups_id'])
      ->addKey('group', ['groups_id', 'type'])
      ->createOrDie(true);

   $table_schema->init('glpi_changes_items')
      ->addField('changes_id', 'int', ['value' => '0'])
      ->addField('itemtype', 'varchar(100)')
      ->addField('items_id', 'int', ['value' => '0'])
      ->addUniqueKey('unicity', ['changes_id', 'itemtype', 'items_id'])
      ->addKey('item', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_changes_problems')
      ->addField('changes_id', 'int', ['value' => '0'])
      ->addIndexedField('problems_id', 'int', ['value' => '0'])
      ->addUniqueKey('unicity', ['changes_id', 'problems_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_changes_suppliers')
      ->addField('changes_id', 'int', ['value' => '0'])
      ->addField('suppliers_id', 'int', ['value' => '0'])
      ->addField('type', 'int', ['value' => '1'])
      ->addField('use_notification', 'boolean', ['value' => '0'])
      ->addField('alternative_email', 'string')
      ->addUniqueKey('unicity', ['changes_id', 'type', 'suppliers_id'])
      ->addKey('group', ['suppliers_id', 'type'])
      ->createOrDie(true);

   $table_schema->init('glpi_changes_tickets')
      ->addField('changes_id', 'int', ['value' => '0'])
      ->addIndexedField('tickets_id', 'int', ['value' => '0'])
      ->addUniqueKey('unicity', ['changes_id', 'tickets_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_changes_users')
      ->addField('changes_id', 'int', ['value' => '0'])
      ->addField('users_id', 'int', ['value' => '0'])
      ->addField('type', 'int', ['value' => '1'])
      ->addField('use_notification', 'boolean', ['value' => '0'])
      ->addField('alternative_email', 'string')
      ->addUniqueKey('unicity', ['changes_id', 'type', 'users_id', 'alternative_email'])
      ->addKey('user', ['users_id', 'type'])
      ->createOrDie(true);

   $table_schema->init('glpi_changetasks')
      ->addIndexedField('changes_id', 'int', ['value' => '0'])
      ->addIndexedField('taskcategories_id', 'int', ['value' => '0'])
      ->addIndexedField('state', 'int', ['value' => '0'])
      ->addIndexedField('date', 'timestamp')
      ->addIndexedField('begin', 'timestamp')
      ->addIndexedField('end', 'timestamp')
      ->addIndexedField('users_id', 'int', ['value' => '0'])
      ->addIndexedField('users_id_editor', 'int', ['value' => '0'])
      ->addIndexedField('users_id_tech', 'int', ['value' => '0'])
      ->addIndexedField('groups_id_tech', 'int', ['value' => '0'])
      ->addField('content', 'longtext')
      ->addField('actiontime', 'int', ['value' => '0'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->addIndexedField('tasktemplates_id', 'int', ['value' => '0'])
      ->addField('timeline_position', 'boolean', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_changevalidations')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('users_id', 'int', ['value' => '0'])
      ->addIndexedField('changes_id', 'int', ['value' => '0'])
      ->addIndexedField('users_id_validate', 'int', ['value' => '0'])
      ->addField('comment_submission', 'text')
      ->addField('comment_validation', 'text')
      ->addIndexedField('status', 'int', ['value' => '2'])
      ->addIndexedField('submission_date', 'timestamp')
      ->addIndexedField('validation_date', 'timestamp')
      ->addField('timeline_position', 'boolean', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_computerantiviruses')
      ->addIndexedField('computers_id', 'int', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addField('manufacturers_id', 'int', ['value' => '0'])
      ->addIndexedField('antivirus_version', 'string')
      ->addIndexedField('signature_version', 'string')
      ->addIndexedField('is_active', 'boolean', ['value' => '0'])
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('is_uptodate', 'boolean', ['value' => '0'])
      ->addIndexedField('is_dynamic', 'boolean', ['value' => '0'])
      ->addIndexedField('date_expiration', 'timestamp')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_computermodels')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('product_number', 'string')
      ->addField('weight', 'int', ['value' => '0'])
      ->addField('required_units', 'int', ['value' => '1'])
      ->addField('depth', 'float', ['value' => '1'])
      ->addField('power_connections', 'int', ['value' => '0'])
      ->addField('power_consumption', 'int', ['value' => '0'])
      ->addField('is_half_rack', 'boolean', ['value' => '0'])
      ->addField('picture_front', 'text')
      ->addField('picture_rear', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_computers')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addIndexedField('serial', 'string')
      ->addIndexedField('otherserial', 'string')
      ->addField('contact', 'string')
      ->addField('contact_num', 'string')
      ->addIndexedField('users_id_tech', 'int', ['value' => '0'])
      ->addIndexedField('groups_id_tech', 'int', ['value' => '0'])
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('autoupdatesystems_id', 'int', ['value' => '0'])
      ->addIndexedField('locations_id', 'int', ['value' => '0'])
      ->addIndexedField('domains_id', 'int', ['value' => '0'])
      ->addIndexedField('networks_id', 'int', ['value' => '0'])
      ->addIndexedField('computermodels_id', 'int', ['value' => '0'])
      ->addIndexedField('computertypes_id', 'int', ['value' => '0'])
      ->addIndexedField('is_template', 'boolean', ['value' => '0'])
      ->addField('template_name', 'string')
      ->addIndexedField('manufacturers_id', 'int', ['value' => '0'])
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('is_dynamic', 'boolean', ['value' => '0'])
      ->addIndexedField('users_id', 'int', ['value' => '0'])
      ->addIndexedField('groups_id', 'int', ['value' => '0'])
      ->addIndexedField('states_id', 'int', ['value' => '0'])
      ->addField('ticket_tco', 'decimal(20,4)', ['value' => '0.0000'])
      ->addIndexedField('uuid', 'string')
      ->addIndexedField('date_creation', 'timestamp')
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_computers_items')
      ->addField('items_id', 'int', ['value' => '0', 'nodefault' => false, 'comment' => 'RELATION to various table, according to itemtype (ID)'])
      ->addIndexedField('computers_id', 'int', ['value' => '0'])
      ->addField('itemtype', 'varchar(100)', ['value' => null, 'nodefault' => true])
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('is_dynamic', 'boolean', ['value' => '0'])
      ->addKey('item', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_computers_softwarelicenses')
      ->addIndexedField('computers_id', 'int', ['value' => '0'])
      ->addIndexedField('softwarelicenses_id', 'int', ['value' => '0'])
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('is_dynamic', 'boolean', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_computers_softwareversions')
      ->addField('computers_id', 'int', ['value' => '0'])
      ->addIndexedField('softwareversions_id', 'int', ['value' => '0'])
      ->addField('is_deleted_computer', 'boolean', ['value' => '0'])
      ->addField('is_template_computer', 'boolean', ['value' => '0'])
      ->addField('entities_id', 'int', ['value' => '0'])
      ->addField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('is_dynamic', 'boolean', ['value' => '0'])
      ->addIndexedField('date_install', 'date')
      ->addUniqueKey('unicity', ['computers_id', 'softwareversions_id'])
      ->addKey('computers_info', ['entities_id', 'is_template_computer', 'is_deleted_computer'])
      ->addKey('is_template', ['is_template_computer'])
      ->addKey('is_deleted', ['is_deleted_computer'])
      ->createOrDie(true);

   $table_schema->init('glpi_computertypes')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_computervirtualmachines')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('computers_id', 'int', ['value' => '0'])
      ->addIndexedField('name', 'string', ['value' => ''])
      ->addIndexedField('virtualmachinestates_id', 'int', ['value' => '0'])
      ->addIndexedField('virtualmachinesystems_id', 'int', ['value' => '0'])
      ->addField('virtualmachinetypes_id', 'int', ['value' => '0'])
      ->addIndexedField('uuid', 'string', ['value' => ''])
      ->addIndexedField('vcpu', 'int', ['value' => '0'])
      ->addIndexedField('ram', 'string', ['value' => ''])
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('is_dynamic', 'boolean', ['value' => '0'])
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_configs')
      ->addField('context', 'varchar(150)')
      ->addField('name', 'varchar(150)')
      ->addField('value', 'text')
      ->addUniqueKey('unicity', ['context', 'name'])
      ->createOrDie(true);

   $table_schema->init('glpi_consumableitems')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addField('ref', 'string')
      ->addIndexedField('locations_id', 'int', ['value' => '0'])
      ->addIndexedField('consumableitemtypes_id', 'int', ['value' => '0'])
      ->addIndexedField('manufacturers_id', 'int', ['value' => '0'])
      ->addIndexedField('users_id_tech', 'int', ['value' => '0'])
      ->addIndexedField('groups_id_tech', 'int', ['value' => '0'])
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addField('comment', 'text')
      ->addIndexedField('alarm_threshold', 'int', ['value' => '10'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->addIndexedField('otherserial', 'string')
      ->createOrDie(true);

   $table_schema->init('glpi_consumableitemtypes')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_consumables')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('consumableitems_id', 'int', ['value' => '0'])
      ->addIndexedField('date_in', 'date')
      ->addIndexedField('date_out', 'date')
      ->addField('itemtype', 'varchar(100)')
      ->addField('items_id', 'int', ['value' => '0'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->addKey('item', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_contacts')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addField('firstname', 'string')
      ->addField('phone', 'string')
      ->addField('phone2', 'string')
      ->addField('mobile', 'string')
      ->addField('fax', 'string')
      ->addField('email', 'string')
      ->addIndexedField('contacttypes_id', 'int', ['value' => '0'])
      ->addField('comment', 'text')
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('usertitles_id', 'int', ['value' => '0'])
      ->addField('address', 'text')
      ->addField('postcode', 'string')
      ->addField('town', 'string')
      ->addField('state', 'string')
      ->addField('country', 'string')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_contacts_suppliers')
      ->addField('suppliers_id', 'int', ['value' => '0'])
      ->addIndexedField('contacts_id', 'int', ['value' => '0'])
      ->addUniqueKey('unicity', ['suppliers_id', 'contacts_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_contacttypes')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_contractcosts')
      ->addIndexedField('contracts_id', 'int', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('begin_date', 'date')
      ->addIndexedField('end_date', 'date')
      ->addField('cost', 'decimal(20,4)', ['value' => '0.0000'])
      ->addIndexedField('budgets_id', 'int', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_contracts')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addField('num', 'string')
      ->addIndexedField('contracttypes_id', 'int', ['value' => '0'])
      ->addIndexedField('begin_date', 'date')
      ->addField('duration', 'int', ['value' => '0'])
      ->addField('notice', 'int', ['value' => '0'])
      ->addField('periodicity', 'int', ['value' => '0'])
      ->addField('billing', 'int', ['value' => '0'])
      ->addField('comment', 'text')
      ->addField('accounting_number', 'string')
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addField('week_begin_hour', 'time', ['value' => '00:00:00'])
      ->addField('week_end_hour', 'time', ['value' => '00:00:00'])
      ->addField('saturday_begin_hour', 'time', ['value' => '00:00:00'])
      ->addField('saturday_end_hour', 'time', ['value' => '00:00:00'])
      ->addIndexedField('use_saturday', 'boolean', ['value' => '0'])
      ->addField('monday_begin_hour', 'time', ['value' => '00:00:00'])
      ->addField('monday_end_hour', 'time', ['value' => '00:00:00'])
      ->addIndexedField('use_monday', 'boolean', ['value' => '0'])
      ->addField('max_links_allowed', 'int', ['value' => '0'])
      ->addIndexedField('alert', 'int', ['value' => '0'])
      ->addField('renewal', 'int', ['value' => '0'])
      ->addField('template_name', 'string')
      ->addField('is_template', 'boolean', ['value' => '0'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_contracts_items')
      ->addField('contracts_id', 'int', ['value' => '0'])
      ->addField('items_id', 'int', ['value' => '0'])
      ->addField('itemtype', 'varchar(100)', ['value' => null, 'nodefault' => true])
      ->addUniqueKey('unicity', ['contracts_id', 'itemtype', 'items_id'])
      ->addKey('FK_device', ['items_id', 'itemtype'])
      ->addKey('item', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_contracts_suppliers')
      ->addField('suppliers_id', 'int', ['value' => '0'])
      ->addIndexedField('contracts_id', 'int', ['value' => '0'])
      ->addUniqueKey('unicity', ['suppliers_id', 'contracts_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_contracttypes')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_crontasklogs')
      ->addIndexedField('crontasks_id', 'int', ['value' => null, 'nodefault' => true])
      ->addField('crontasklogs_id', 'int', ['value' => null, 'nodefault' => true, 'comment' => 'id of \'start\' event'])
      ->addIndexedField('date', 'timestamp', ['value' => null, 'nodefault' => true])
      ->addField('state', 'int', ['value' => null, 'nodefault' => true, 'comment' => '0:start, 1:run, 2:stop'])
      ->addField('elapsed', 'float', ['value' => null, 'nodefault' => true, 'comment' => 'time elapsed since start'])
      ->addField('volume', 'int', ['value' => null, 'nodefault' => true, 'comment' => 'for statistics'])
      ->addField('content', 'string', ['value' => null, 'nodefault' => false, 'comment' => 'message'])
      ->addKey('crontasklogs_id_state', ['crontasklogs_id', 'state'])
      ->createOrDie(true);

   $table_schema->init('glpi_crontasks')
      ->addField('itemtype', 'varchar(100)', ['value' => null, 'nodefault' => true])
      ->addField('name', 'varchar(150)', ['value' => null, 'nodefault' => true, 'comment' => 'task name'])
      ->addField('frequency', 'int', ['value' => null, 'nodefault' => true, 'comment' => 'second between launch'])
      ->addField('param', 'int', ['value' => null, 'nodefault' => false, 'comment' => 'task specify parameter'])
      ->addField('state', 'int', ['value' => '1', 'nodefault' => false, 'comment' => '0:disabled, 1:waiting, 2:running'])
      ->addIndexedField('mode', 'int', ['value' => '1', 'nodefault' => false, 'comment' => '1:internal, 2:external'])
      ->addField('allowmode', 'int', ['value' => '3', 'nodefault' => false, 'comment' => '1:internal, 2:external, 3:both'])
      ->addField('hourmin', 'int', ['value' => '0'])
      ->addField('hourmax', 'int', ['value' => '24'])
      ->addField('logs_lifetime', 'int', ['value' => '30', 'nodefault' => false, 'comment' => 'number of days'])
      ->addField('lastrun', 'timestamp', ['value' => null, 'nodefault' => false, 'comment' => 'last run date'])
      ->addField('lastcode', 'int', ['value' => null, 'nodefault' => false, 'comment' => 'last run return code'])
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->addUniqueKey('unicity', ['itemtype', 'name'])
      ->createOrDie(true);

   $table_schema->init('glpi_datacenters')
      ->addField('name', 'string')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('locations_id', 'int', ['value' => '0'])
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addField('date_mod', 'timestamp')
      ->addField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_dcrooms')
      ->addField('name', 'string')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('locations_id', 'int', ['value' => '0'])
      ->addField('vis_cols', 'int')
      ->addField('vis_rows', 'int')
      ->addField('blueprint', 'text')
      ->addIndexedField('datacenters_id', 'int', ['value' => '0'])
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addField('date_mod', 'timestamp')
      ->addField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_devicebatteries')
      ->addIndexedField('designation', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('manufacturers_id', 'int', ['value' => '0'])
      ->addField('voltage', 'int')
      ->addField('capacity', 'int')
      ->addIndexedField('devicebatterytypes_id', 'int', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('devicebatterymodels_id', 'int')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_devicebatterymodels')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('product_number', 'string')
      ->createOrDie(true);

   $table_schema->init('glpi_devicebatterytypes')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_devicecasemodels')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('product_number', 'string')
      ->createOrDie(true);

   $table_schema->init('glpi_devicecases')
      ->addIndexedField('designation', 'string')
      ->addIndexedField('devicecasetypes_id', 'int', ['value' => '0'])
      ->addField('comment', 'text')
      ->addIndexedField('manufacturers_id', 'int', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('devicecasemodels_id', 'int')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_devicecasetypes')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_devicecontrolmodels')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('product_number', 'string')
      ->createOrDie(true);

   $table_schema->init('glpi_devicecontrols')
      ->addIndexedField('designation', 'string')
      ->addField('is_raid', 'boolean', ['value' => '0'])
      ->addField('comment', 'text')
      ->addIndexedField('manufacturers_id', 'int', ['value' => '0'])
      ->addIndexedField('interfacetypes_id', 'int', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('devicecontrolmodels_id', 'int')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_devicedrivemodels')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('product_number', 'string')
      ->createOrDie(true);

   $table_schema->init('glpi_devicedrives')
      ->addIndexedField('designation', 'string')
      ->addField('is_writer', 'boolean', ['value' => '1'])
      ->addField('speed', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('manufacturers_id', 'int', ['value' => '0'])
      ->addIndexedField('interfacetypes_id', 'int', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('devicedrivemodels_id', 'int')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_devicefirmwaremodels')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('product_number', 'string')
      ->createOrDie(true);

   $table_schema->init('glpi_devicefirmwares')
      ->addIndexedField('designation', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('manufacturers_id', 'int', ['value' => '0'])
      ->addField('date', 'date')
      ->addField('version', 'string')
      ->addIndexedField('devicefirmwaretypes_id', 'int', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('devicefirmwaremodels_id', 'int')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_devicefirmwaretypes')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_devicegenericmodels')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('product_number', 'string')
      ->createOrDie(true);

   $table_schema->init('glpi_devicegenerics')
      ->addIndexedField('designation', 'string')
      ->addIndexedField('devicegenerictypes_id', 'int', ['value' => '0'])
      ->addField('comment', 'text')
      ->addIndexedField('manufacturers_id', 'int', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('locations_id', 'int', ['value' => '0'])
      ->addIndexedField('states_id', 'int', ['value' => '0'])
      ->addIndexedField('devicegenericmodels_id', 'int')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_devicegenerictypes')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->createOrDie(true);

   $table_schema->init('glpi_devicegraphiccardmodels')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('product_number', 'string')
      ->createOrDie(true);

   $table_schema->init('glpi_devicegraphiccards')
      ->addIndexedField('designation', 'string')
      ->addIndexedField('interfacetypes_id', 'int', ['value' => '0'])
      ->addField('comment', 'text')
      ->addIndexedField('manufacturers_id', 'int', ['value' => '0'])
      ->addField('memory_default', 'int', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('devicegraphiccardmodels_id', 'int')
      ->addIndexedField('chipset', 'string')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_deviceharddrivemodels')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('product_number', 'string')
      ->createOrDie(true);

   $table_schema->init('glpi_deviceharddrives')
      ->addIndexedField('designation', 'string')
      ->addField('rpm', 'string')
      ->addIndexedField('interfacetypes_id', 'int', ['value' => '0'])
      ->addField('cache', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('manufacturers_id', 'int', ['value' => '0'])
      ->addField('capacity_default', 'int', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('deviceharddrivemodels_id', 'int')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_devicememories')
      ->addIndexedField('designation', 'string')
      ->addField('frequence', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('manufacturers_id', 'int', ['value' => '0'])
      ->addField('size_default', 'int', ['value' => '0'])
      ->addIndexedField('devicememorytypes_id', 'int', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('devicememorymodels_id', 'int')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_devicememorymodels')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('product_number', 'string')
      ->createOrDie(true);

   $table_schema->init('glpi_devicememorytypes')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_devicemotherboardmodels')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('product_number', 'string')
      ->createOrDie(true);

   $table_schema->init('glpi_devicemotherboards')
      ->addIndexedField('designation', 'string')
      ->addField('chipset', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('manufacturers_id', 'int', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('devicemotherboardmodels_id', 'int')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_devicenetworkcardmodels')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('product_number', 'string')
      ->createOrDie(true);

   $table_schema->init('glpi_devicenetworkcards')
      ->addIndexedField('designation', 'string')
      ->addField('bandwidth', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('manufacturers_id', 'int', ['value' => '0'])
      ->addField('mac_default', 'string')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('devicenetworkcardmodels_id', 'int')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_devicepcimodels')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('product_number', 'string')
      ->createOrDie(true);

   $table_schema->init('glpi_devicepcis')
      ->addIndexedField('designation', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('manufacturers_id', 'int', ['value' => '0'])
      ->addIndexedField('devicenetworkcardmodels_id', 'int', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('devicepcimodels_id', 'int')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_devicepowersupplies')
      ->addIndexedField('designation', 'string')
      ->addField('power', 'string')
      ->addField('is_atx', 'boolean', ['value' => '1'])
      ->addField('comment', 'text')
      ->addIndexedField('manufacturers_id', 'int', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('devicepowersupplymodels_id', 'int')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_devicepowersupplymodels')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('product_number', 'string')
      ->createOrDie(true);

   $table_schema->init('glpi_deviceprocessormodels')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('product_number', 'string')
      ->createOrDie(true);

   $table_schema->init('glpi_deviceprocessors')
      ->addIndexedField('designation', 'string')
      ->addField('frequence', 'int', ['value' => '0'])
      ->addField('comment', 'text')
      ->addIndexedField('manufacturers_id', 'int', ['value' => '0'])
      ->addField('frequency_default', 'int', ['value' => '0'])
      ->addField('nbcores_default', 'int')
      ->addField('nbthreads_default', 'int')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('deviceprocessormodels_id', 'int')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_devicesensormodels')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('product_number', 'string')
      ->createOrDie(true);

   $table_schema->init('glpi_devicesensors')
      ->addIndexedField('designation', 'string')
      ->addIndexedField('devicesensortypes_id', 'int', ['value' => '0'])
      ->addField('devicesensormodels_id', 'int', ['value' => '0'])
      ->addField('comment', 'text')
      ->addIndexedField('manufacturers_id', 'int', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('locations_id', 'int', ['value' => '0'])
      ->addIndexedField('states_id', 'int', ['value' => '0'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_devicesensortypes')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->createOrDie(true);

   $table_schema->init('glpi_devicesimcards')
      ->addIndexedField('designation', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('manufacturers_id', 'int', ['value' => '0'])
      ->addField('voltage', 'int')
      ->addIndexedField('devicesimcardtypes_id', 'int', ['value' => '0'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->addField('allow_voip', 'boolean', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_devicesimcardtypes')
      ->addIndexedField('name', 'string', ['value' => ''])
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_devicesoundcardmodels')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('product_number', 'string')
      ->createOrDie(true);

   $table_schema->init('glpi_devicesoundcards')
      ->addIndexedField('designation', 'string')
      ->addField('type', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('manufacturers_id', 'int', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('devicesoundcardmodels_id', 'int')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_displaypreferences')
      ->addIndexedField('itemtype', 'varchar(100)', ['value' => null, 'nodefault' => true])
      ->addIndexedField('num', 'int', ['value' => '0'])
      ->addIndexedField('rank', 'int', ['value' => '0'])
      ->addField('users_id', 'int', ['value' => '0'])
      ->addIndexedField('is_main', 'boolean', ['value' => '1'])
      ->addUniqueKey('unicity', ['users_id', 'itemtype', 'num', 'is_main'])
      ->createOrDie(true);

   $table_schema->init('glpi_documentcategories')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addField('documentcategories_id', 'int', ['value' => '0'])
      ->addField('completename', 'text')
      ->addField('level', 'int', ['value' => '0'])
      ->addField('ancestors_cache', 'longtext')
      ->addField('sons_cache', 'longtext')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->addUniqueKey('unicity', ['documentcategories_id', 'name'])
      ->createOrDie(true);

   $table_schema->init('glpi_documents')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addField('filename', 'string', ['value' => null, 'nodefault' => false, 'comment' => 'for display and transfert'])
      ->addField('filepath', 'string', ['value' => null, 'nodefault' => false, 'comment' => 'file storage path'])
      ->addIndexedField('documentcategories_id', 'int', ['value' => '0'])
      ->addField('mime', 'string')
      ->addIndexedField('date_mod', 'timestamp')
      ->addField('comment', 'text')
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addField('link', 'string')
      ->addIndexedField('users_id', 'int', ['value' => '0'])
      ->addIndexedField('tickets_id', 'int', ['value' => '0'])
      ->addIndexedField('sha1sum', 'char(40)')
      ->addField('is_blacklisted', 'boolean', ['value' => '0'])
      ->addIndexedField('tag', 'string')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_documents_items')
      ->addField('documents_id', 'int', ['value' => '0'])
      ->addField('items_id', 'int', ['value' => '0'])
      ->addField('itemtype', 'varchar(100)', ['value' => null, 'nodefault' => true])
      ->addField('entities_id', 'int', ['value' => '0'])
      ->addField('is_recursive', 'boolean', ['value' => '0'])
      ->addField('date_mod', 'timestamp')
      ->addIndexedField('users_id', 'int', ['value' => '0'])
      ->addField('timeline_position', 'boolean', ['value' => '0'])
      ->addUniqueKey('unicity', ['documents_id', 'itemtype', 'items_id'])
      ->addKey('item', ['itemtype', 'items_id', 'entities_id', 'is_recursive'])
      ->createOrDie(true);

   $table_schema->init('glpi_documenttypes')
      ->addIndexedField('name', 'string')
      ->addField('ext', 'string')
      ->addField('icon', 'string')
      ->addField('mime', 'string')
      ->addIndexedField('is_uploadable', 'boolean', ['value' => '1'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addField('comment', 'text')
      ->addIndexedField('date_creation', 'timestamp')
      ->addUniqueKey('unicity', ['ext'])
      ->createOrDie(true);

   $table_schema->init('glpi_domains')
      ->addIndexedField('name', 'string')
      ->addField('entities_id', 'int', ['value' => '0'])
      ->addField('is_recursive', 'boolean', ['value' => '0'])
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_dropdowntranslations')
      ->addField('items_id', 'int', ['value' => '0'])
      ->addField('itemtype', 'varchar(100)')
      ->addIndexedField('language', 'varchar(5)')
      ->addIndexedField('field', 'varchar(100)')
      ->addField('value', 'text')
      ->addUniqueKey('unicity', ['itemtype', 'items_id', 'language', 'field'])
      ->addKey('typeid', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_enclosuremodels')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('product_number', 'string')
      ->addField('weight', 'int', ['value' => '0'])
      ->addField('required_units', 'int', ['value' => '1'])
      ->addField('depth', 'float', ['value' => '1'])
      ->addField('power_connections', 'int', ['value' => '0'])
      ->addField('power_consumption', 'int', ['value' => '0'])
      ->addField('is_half_rack', 'boolean', ['value' => '0'])
      ->addField('picture_front', 'text')
      ->addField('picture_rear', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_enclosures')
      ->addField('name', 'string')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('locations_id', 'int', ['value' => '0'])
      ->addField('serial', 'string')
      ->addField('otherserial', 'string')
      ->addIndexedField('enclosuremodels_id', 'int')
      ->addIndexedField('users_id_tech', 'int', ['value' => '0'])
      ->addField('groups_id_tech', 'int', ['value' => '0'])
      ->addIndexedField('is_template', 'boolean', ['value' => '0'])
      ->addField('template_name', 'string')
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addField('orientation', 'boolean')
      ->addField('power_supplies', 'boolean', ['value' => '0'])
      ->addIndexedField('states_id', 'int', ['value' => '0', 'nodefault' => false, 'comment' => 'RELATION to states (id)'])
      ->addField('comment', 'text')
      ->addIndexedField('manufacturers_id', 'int', ['value' => '0'])
      ->addField('date_mod', 'timestamp')
      ->addField('date_creation', 'timestamp')
      ->addKey('group_id_tech', ['groups_id_tech'])
      ->createOrDie(true);

   $table_schema->init('glpi_entities')
      ->addField('name', 'string')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addField('completename', 'text')
      ->addField('comment', 'text')
      ->addField('level', 'int', ['value' => '0'])
      ->addField('sons_cache', 'longtext')
      ->addField('ancestors_cache', 'longtext')
      ->addField('address', 'text')
      ->addField('postcode', 'string')
      ->addField('town', 'string')
      ->addField('state', 'string')
      ->addField('country', 'string')
      ->addField('website', 'string')
      ->addField('phonenumber', 'string')
      ->addField('fax', 'string')
      ->addField('email', 'string')
      ->addField('admin_email', 'string')
      ->addField('admin_email_name', 'string')
      ->addField('admin_reply', 'string')
      ->addField('admin_reply_name', 'string')
      ->addField('notification_subject_tag', 'string')
      ->addField('ldap_dn', 'string')
      ->addField('tag', 'string')
      ->addField('authldaps_id', 'int', ['value' => '0'])
      ->addField('mail_domain', 'string')
      ->addField('entity_ldapfilter', 'text')
      ->addField('mailing_signature', 'text')
      ->addField('cartridges_alert_repeat', 'int', ['value' => '-2'])
      ->addField('consumables_alert_repeat', 'int', ['value' => '-2'])
      ->addField('use_licenses_alert', 'int', ['value' => '-2'])
      ->addField('send_licenses_alert_before_delay', 'int', ['value' => '-2'])
      ->addField('use_certificates_alert', 'int', ['value' => '-2'])
      ->addField('send_certificates_alert_before_delay', 'int', ['value' => '-2'])
      ->addField('use_contracts_alert', 'int', ['value' => '-2'])
      ->addField('send_contracts_alert_before_delay', 'int', ['value' => '-2'])
      ->addField('use_infocoms_alert', 'int', ['value' => '-2'])
      ->addField('send_infocoms_alert_before_delay', 'int', ['value' => '-2'])
      ->addField('use_reservations_alert', 'int', ['value' => '-2'])
      ->addField('autoclose_delay', 'int', ['value' => '-2'])
      ->addField('notclosed_delay', 'int', ['value' => '-2'])
      ->addField('calendars_id', 'int', ['value' => '-2'])
      ->addField('auto_assign_mode', 'int', ['value' => '-2'])
      ->addField('tickettype', 'int', ['value' => '-2'])
      ->addField('max_closedate', 'timestamp')
      ->addField('inquest_config', 'int', ['value' => '-2'])
      ->addField('inquest_rate', 'int', ['value' => '0'])
      ->addField('inquest_delay', 'int', ['value' => '-10'])
      ->addField('inquest_URL', 'string')
      ->addField('autofill_warranty_date', 'string', ['value' => '-2'])
      ->addField('autofill_use_date', 'string', ['value' => '-2'])
      ->addField('autofill_buy_date', 'string', ['value' => '-2'])
      ->addField('autofill_delivery_date', 'string', ['value' => '-2'])
      ->addField('autofill_order_date', 'string', ['value' => '-2'])
      ->addField('tickettemplates_id', 'int', ['value' => '-2'])
      ->addField('entities_id_software', 'int', ['value' => '-2'])
      ->addField('default_contract_alert', 'int', ['value' => '-2'])
      ->addField('default_infocom_alert', 'int', ['value' => '-2'])
      ->addField('default_cartridges_alarm_threshold', 'int', ['value' => '-2'])
      ->addField('default_consumables_alarm_threshold', 'int', ['value' => '-2'])
      ->addField('delay_send_emails', 'int', ['value' => '-2'])
      ->addField('is_notif_enable_default', 'int', ['value' => '-2'])
      ->addField('inquest_duration', 'int', ['value' => '0'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->addField('autofill_decommission_date', 'string', ['value' => '-2'])
      ->addUniqueKey('unicity', ['entities_id', 'name'])
      ->createOrDie(true);

   $table_schema->init('glpi_entities_knowbaseitems')
      ->addIndexedField('knowbaseitems_id', 'int', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_entities_reminders')
      ->addIndexedField('reminders_id', 'int', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_entities_rssfeeds')
      ->addIndexedField('rssfeeds_id', 'int', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_events')
      ->addField('items_id', 'int', ['value' => '0'])
      ->addField('type', 'string')
      ->addIndexedField('date', 'timestamp')
      ->addField('service', 'string')
      ->addIndexedField('level', 'int', ['value' => '0'])
      ->addField('message', 'text')
      ->addKey('item', ['type', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_fieldblacklists')
      ->addIndexedField('name', 'string', ['value' => ''])
      ->addField('field', 'string', ['value' => ''])
      ->addField('value', 'string', ['value' => ''])
      ->addField('itemtype', 'string', ['value' => ''])
      ->addField('entities_id', 'int', ['value' => '0'])
      ->addField('is_recursive', 'boolean', ['value' => '0'])
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_fieldunicities')
      ->addField('name', 'string', ['value' => ''])
      ->addField('is_recursive', 'boolean', ['value' => '0'])
      ->addField('itemtype', 'string', ['value' => ''])
      ->addField('entities_id', 'int', ['value' => '-1'])
      ->addField('fields', 'text')
      ->addField('is_active', 'boolean', ['value' => '0'])
      ->addField('action_refuse', 'boolean', ['value' => '0'])
      ->addField('action_notify', 'boolean', ['value' => '0'])
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_filesystems')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_fqdns')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addIndexedField('fqdn', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_groups')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('ldap_field', 'string')
      ->addField('ldap_value', 'text')
      ->addField('ldap_group_dn', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('groups_id', 'int', ['value' => '0'])
      ->addField('completename', 'text')
      ->addField('level', 'int', ['value' => '0'])
      ->addField('ancestors_cache', 'longtext')
      ->addField('sons_cache', 'longtext')
      ->addIndexedField('is_requester', 'boolean', ['value' => '1'])
      ->addIndexedField('is_watcher', 'boolean', ['value' => '1'])
      ->addIndexedField('is_assign', 'boolean', ['value' => '1'])
      ->addField('is_task', 'boolean', ['value' => '1'])
      ->addIndexedField('is_notify', 'boolean', ['value' => '1'])
      ->addIndexedField('is_itemgroup', 'boolean', ['value' => '1'])
      ->addIndexedField('is_usergroup', 'boolean', ['value' => '1'])
      ->addIndexedField('is_manager', 'boolean', ['value' => '1'])
      ->addIndexedField('date_creation', 'timestamp')
      ->addKey('ldap_value', ['ldap_value' => 200])
      ->addKey('ldap_group_dn', ['ldap_group_dn' => 200])
      ->createOrDie(true);

   $table_schema->init('glpi_groups_knowbaseitems')
      ->addIndexedField('knowbaseitems_id', 'int', ['value' => '0'])
      ->addIndexedField('groups_id', 'int', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '-1'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_groups_problems')
      ->addField('problems_id', 'int', ['value' => '0'])
      ->addField('groups_id', 'int', ['value' => '0'])
      ->addField('type', 'int', ['value' => '1'])
      ->addUniqueKey('unicity', ['problems_id', 'type', 'groups_id'])
      ->addKey('group', ['groups_id', 'type'])
      ->createOrDie(true);

   $table_schema->init('glpi_groups_reminders')
      ->addIndexedField('reminders_id', 'int', ['value' => '0'])
      ->addIndexedField('groups_id', 'int', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '-1'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_groups_rssfeeds')
      ->addIndexedField('rssfeeds_id', 'int', ['value' => '0'])
      ->addIndexedField('groups_id', 'int', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '-1'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_groups_tickets')
      ->addField('tickets_id', 'int', ['value' => '0'])
      ->addField('groups_id', 'int', ['value' => '0'])
      ->addField('type', 'int', ['value' => '1'])
      ->addUniqueKey('unicity', ['tickets_id', 'type', 'groups_id'])
      ->addKey('group', ['groups_id', 'type'])
      ->createOrDie(true);

   $table_schema->init('glpi_groups_users')
      ->addField('users_id', 'int', ['value' => '0'])
      ->addIndexedField('groups_id', 'int', ['value' => '0'])
      ->addField('is_dynamic', 'boolean', ['value' => '0'])
      ->addIndexedField('is_manager', 'boolean', ['value' => '0'])
      ->addIndexedField('is_userdelegate', 'boolean', ['value' => '0'])
      ->addUniqueKey('unicity', ['users_id', 'groups_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_holidays')
      ->addIndexedField('name', 'string')
      ->addField('entities_id', 'int', ['value' => '0'])
      ->addField('is_recursive', 'boolean', ['value' => '0'])
      ->addField('comment', 'text')
      ->addIndexedField('begin_date', 'date')
      ->addIndexedField('end_date', 'date')
      ->addIndexedField('is_perpetual', 'boolean', ['value' => '0'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_infocoms')
      ->addField('items_id', 'int', ['value' => '0'])
      ->addField('itemtype', 'varchar(100)', ['value' => null, 'nodefault' => true])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('buy_date', 'date')
      ->addField('use_date', 'date')
      ->addField('warranty_duration', 'int', ['value' => '0'])
      ->addField('warranty_info', 'string')
      ->addIndexedField('suppliers_id', 'int', ['value' => '0'])
      ->addField('order_number', 'string')
      ->addField('delivery_number', 'string')
      ->addField('immo_number', 'string')
      ->addField('value', 'decimal(20,4)', ['value' => '0.0000'])
      ->addField('warranty_value', 'decimal(20,4)', ['value' => '0.0000'])
      ->addField('sink_time', 'int', ['value' => '0'])
      ->addField('sink_type', 'int', ['value' => '0'])
      ->addField('sink_coeff', 'float', ['value' => '0'])
      ->addField('comment', 'text')
      ->addField('bill', 'string')
      ->addIndexedField('budgets_id', 'int', ['value' => '0'])
      ->addIndexedField('alert', 'int', ['value' => '0'])
      ->addField('order_date', 'date')
      ->addField('delivery_date', 'date')
      ->addField('inventory_date', 'date')
      ->addField('warranty_date', 'date')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->addField('decommission_date', 'timestamp')
      ->addIndexedField('businesscriticities_id', 'int', ['value' => '0'])
      ->addUniqueKey('unicity', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_interfacetypes')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_ipaddresses')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addField('items_id', 'int', ['value' => '0'])
      ->addField('itemtype', 'varchar(100)', ['value' => null, 'nodefault' => true])
      ->addField('version', 'tinyint(3) unsigned', ['value' => '0'])
      ->addField('name', 'string')
      ->addField('binary_0', 'int(10) unsigned', ['value' => '0'])
      ->addField('binary_1', 'int(10) unsigned', ['value' => '0'])
      ->addField('binary_2', 'int(10) unsigned', ['value' => '0'])
      ->addField('binary_3', 'int(10) unsigned', ['value' => '0'])
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('is_dynamic', 'boolean', ['value' => '0'])
      ->addField('mainitems_id', 'int', ['value' => '0'])
      ->addField('mainitemtype', 'string')
      ->addKey('textual', ['name'])
      ->addKey('binary', ['binary_0', 'binary_1', 'binary_2', 'binary_3'])
      ->addKey('item', ['itemtype', 'items_id', 'is_deleted'])
      ->addKey('mainitem', ['mainitemtype', 'mainitems_id', 'is_deleted'])
      ->createOrDie(true);

   $table_schema->init('glpi_ipaddresses_ipnetworks')
      ->addIndexedField('ipaddresses_id', 'int', ['value' => '0'])
      ->addIndexedField('ipnetworks_id', 'int', ['value' => '0'])
      ->addUniqueKey('unicity', ['ipaddresses_id', 'ipnetworks_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_ipnetworks')
      ->addField('entities_id', 'int', ['value' => '0'])
      ->addField('is_recursive', 'boolean', ['value' => '0'])
      ->addField('ipnetworks_id', 'int', ['value' => '0'])
      ->addField('completename', 'text')
      ->addField('level', 'int', ['value' => '0'])
      ->addField('ancestors_cache', 'longtext')
      ->addField('sons_cache', 'longtext')
      ->addField('addressable', 'boolean', ['value' => '0'])
      ->addField('version', 'tinyint(3) unsigned', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addField('address', 'varchar(40)')
      ->addField('address_0', 'int(10) unsigned', ['value' => '0'])
      ->addField('address_1', 'int(10) unsigned', ['value' => '0'])
      ->addField('address_2', 'int(10) unsigned', ['value' => '0'])
      ->addField('address_3', 'int(10) unsigned', ['value' => '0'])
      ->addField('netmask', 'varchar(40)')
      ->addField('netmask_0', 'int(10) unsigned', ['value' => '0'])
      ->addField('netmask_1', 'int(10) unsigned', ['value' => '0'])
      ->addField('netmask_2', 'int(10) unsigned', ['value' => '0'])
      ->addField('netmask_3', 'int(10) unsigned', ['value' => '0'])
      ->addField('gateway', 'varchar(40)')
      ->addField('gateway_0', 'int(10) unsigned', ['value' => '0'])
      ->addField('gateway_1', 'int(10) unsigned', ['value' => '0'])
      ->addField('gateway_2', 'int(10) unsigned', ['value' => '0'])
      ->addField('gateway_3', 'int(10) unsigned', ['value' => '0'])
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->addKey('network_definition', ['entities_id', 'address', 'netmask'])
      ->addKey('address', ['address_0', 'address_1', 'address_2', 'address_3'])
      ->addKey('netmask', ['netmask_0', 'netmask_1', 'netmask_2', 'netmask_3'])
      ->addKey('gateway', ['gateway_0', 'gateway_1', 'gateway_2', 'gateway_3'])
      ->createOrDie(true);

   $table_schema->init('glpi_ipnetworks_vlans')
      ->addField('ipnetworks_id', 'int', ['value' => '0'])
      ->addField('vlans_id', 'int', ['value' => '0'])
      ->addUniqueKey('link', ['ipnetworks_id', 'vlans_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_items_devicebatteries')
      ->addField('items_id', 'int', ['value' => '0'])
      ->addField('itemtype', 'string')
      ->addIndexedField('devicebatteries_id', 'int', ['value' => '0'])
      ->addField('manufacturing_date', 'date')
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('is_dynamic', 'boolean', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('serial', 'string')
      ->addIndexedField('otherserial', 'string')
      ->addField('locations_id', 'int', ['value' => '0'])
      ->addField('states_id', 'int', ['value' => '0'])
      ->addKey('computers_id', ['items_id'])
      ->addKey('item', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_items_devicecases')
      ->addField('items_id', 'int', ['value' => '0'])
      ->addField('itemtype', 'string')
      ->addIndexedField('devicecases_id', 'int', ['value' => '0'])
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('is_dynamic', 'boolean', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('serial', 'string')
      ->addIndexedField('otherserial', 'string')
      ->addIndexedField('locations_id', 'int', ['value' => '0'])
      ->addIndexedField('states_id', 'int', ['value' => '0'])
      ->addKey('computers_id', ['items_id'])
      ->addKey('item', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_items_devicecontrols')
      ->addField('items_id', 'int', ['value' => '0'])
      ->addField('itemtype', 'string')
      ->addIndexedField('devicecontrols_id', 'int', ['value' => '0'])
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('is_dynamic', 'boolean', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('serial', 'string')
      ->addIndexedField('busID', 'string')
      ->addIndexedField('otherserial', 'string')
      ->addIndexedField('locations_id', 'int', ['value' => '0'])
      ->addIndexedField('states_id', 'int', ['value' => '0'])
      ->addKey('computers_id', ['items_id'])
      ->addKey('item', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_items_devicedrives')
      ->addField('items_id', 'int', ['value' => '0'])
      ->addField('itemtype', 'string')
      ->addIndexedField('devicedrives_id', 'int', ['value' => '0'])
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('is_dynamic', 'boolean', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('serial', 'string')
      ->addIndexedField('busID', 'string')
      ->addIndexedField('otherserial', 'string')
      ->addIndexedField('locations_id', 'int', ['value' => '0'])
      ->addIndexedField('states_id', 'int', ['value' => '0'])
      ->addKey('computers_id', ['items_id'])
      ->addKey('item', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_items_devicefirmwares')
      ->addField('items_id', 'int', ['value' => '0'])
      ->addField('itemtype', 'string')
      ->addIndexedField('devicefirmwares_id', 'int', ['value' => '0'])
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('is_dynamic', 'boolean', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('serial', 'string')
      ->addIndexedField('otherserial', 'string')
      ->addField('locations_id', 'int', ['value' => '0'])
      ->addField('states_id', 'int', ['value' => '0'])
      ->addKey('computers_id', ['items_id'])
      ->addKey('item', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_items_devicegenerics')
      ->addField('items_id', 'int', ['value' => '0'])
      ->addField('itemtype', 'string')
      ->addIndexedField('devicegenerics_id', 'int', ['value' => '0'])
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('is_dynamic', 'boolean', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('serial', 'string')
      ->addIndexedField('otherserial', 'string')
      ->addField('locations_id', 'int', ['value' => '0'])
      ->addField('states_id', 'int', ['value' => '0'])
      ->addKey('computers_id', ['items_id'])
      ->addKey('item', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_items_devicegraphiccards')
      ->addField('items_id', 'int', ['value' => '0'])
      ->addField('itemtype', 'string')
      ->addIndexedField('devicegraphiccards_id', 'int', ['value' => '0'])
      ->addField('memory', 'int', ['value' => '0'])
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('is_dynamic', 'boolean', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('serial', 'string')
      ->addIndexedField('busID', 'string')
      ->addIndexedField('otherserial', 'string')
      ->addIndexedField('locations_id', 'int', ['value' => '0'])
      ->addIndexedField('states_id', 'int', ['value' => '0'])
      ->addKey('computers_id', ['items_id'])
      ->addKey('specificity', ['memory'])
      ->addKey('item', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_items_deviceharddrives')
      ->addField('items_id', 'int', ['value' => '0'])
      ->addField('itemtype', 'string')
      ->addIndexedField('deviceharddrives_id', 'int', ['value' => '0'])
      ->addField('capacity', 'int', ['value' => '0'])
      ->addIndexedField('serial', 'string')
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('is_dynamic', 'boolean', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('busID', 'string')
      ->addIndexedField('otherserial', 'string')
      ->addIndexedField('locations_id', 'int', ['value' => '0'])
      ->addIndexedField('states_id', 'int', ['value' => '0'])
      ->addKey('computers_id', ['items_id'])
      ->addKey('specificity', ['capacity'])
      ->addKey('item', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_items_devicememories')
      ->addField('items_id', 'int', ['value' => '0'])
      ->addField('itemtype', 'string')
      ->addIndexedField('devicememories_id', 'int', ['value' => '0'])
      ->addField('size', 'int', ['value' => '0'])
      ->addIndexedField('serial', 'string')
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('is_dynamic', 'boolean', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('busID', 'string')
      ->addIndexedField('otherserial', 'string')
      ->addIndexedField('locations_id', 'int', ['value' => '0'])
      ->addIndexedField('states_id', 'int', ['value' => '0'])
      ->addKey('computers_id', ['items_id'])
      ->addKey('specificity', ['size'])
      ->addKey('item', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_items_devicemotherboards')
      ->addField('items_id', 'int', ['value' => '0'])
      ->addField('itemtype', 'string')
      ->addIndexedField('devicemotherboards_id', 'int', ['value' => '0'])
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('is_dynamic', 'boolean', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('serial', 'string')
      ->addIndexedField('otherserial', 'string')
      ->addIndexedField('locations_id', 'int', ['value' => '0'])
      ->addIndexedField('states_id', 'int', ['value' => '0'])
      ->addKey('computers_id', ['items_id'])
      ->addKey('item', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_items_devicenetworkcards')
      ->addField('items_id', 'int', ['value' => '0'])
      ->addField('itemtype', 'string')
      ->addIndexedField('devicenetworkcards_id', 'int', ['value' => '0'])
      ->addField('mac', 'string')
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('is_dynamic', 'boolean', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('serial', 'string')
      ->addIndexedField('busID', 'string')
      ->addIndexedField('otherserial', 'string')
      ->addIndexedField('locations_id', 'int', ['value' => '0'])
      ->addIndexedField('states_id', 'int', ['value' => '0'])
      ->addKey('computers_id', ['items_id'])
      ->addKey('specificity', ['mac'])
      ->addKey('item', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_items_devicepcis')
      ->addField('items_id', 'int', ['value' => '0'])
      ->addField('itemtype', 'string')
      ->addIndexedField('devicepcis_id', 'int', ['value' => '0'])
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('is_dynamic', 'boolean', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('serial', 'string')
      ->addIndexedField('busID', 'string')
      ->addIndexedField('otherserial', 'string')
      ->addIndexedField('locations_id', 'int', ['value' => '0'])
      ->addIndexedField('states_id', 'int', ['value' => '0'])
      ->addKey('computers_id', ['items_id'])
      ->addKey('item', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_items_devicepowersupplies')
      ->addField('items_id', 'int', ['value' => '0'])
      ->addField('itemtype', 'string')
      ->addIndexedField('devicepowersupplies_id', 'int', ['value' => '0'])
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('is_dynamic', 'boolean', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('serial', 'string')
      ->addIndexedField('otherserial', 'string')
      ->addIndexedField('locations_id', 'int', ['value' => '0'])
      ->addIndexedField('states_id', 'int', ['value' => '0'])
      ->addKey('computers_id', ['items_id'])
      ->addKey('item', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_items_deviceprocessors')
      ->addField('items_id', 'int', ['value' => '0'])
      ->addField('itemtype', 'string')
      ->addIndexedField('deviceprocessors_id', 'int', ['value' => '0'])
      ->addField('frequency', 'int', ['value' => '0'])
      ->addIndexedField('serial', 'string')
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('is_dynamic', 'boolean', ['value' => '0'])
      ->addIndexedField('nbcores', 'int')
      ->addIndexedField('nbthreads', 'int')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('busID', 'string')
      ->addIndexedField('otherserial', 'string')
      ->addIndexedField('locations_id', 'int', ['value' => '0'])
      ->addIndexedField('states_id', 'int', ['value' => '0'])
      ->addKey('computers_id', ['items_id'])
      ->addKey('specificity', ['frequency'])
      ->addKey('item', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_items_devicesensors')
      ->addField('items_id', 'int', ['value' => '0'])
      ->addField('itemtype', 'string')
      ->addIndexedField('devicesensors_id', 'int', ['value' => '0'])
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('is_dynamic', 'boolean', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('serial', 'string')
      ->addIndexedField('otherserial', 'string')
      ->addField('locations_id', 'int', ['value' => '0'])
      ->addField('states_id', 'int', ['value' => '0'])
      ->addKey('computers_id', ['items_id'])
      ->addKey('item', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_items_devicesimcards')
      ->addField('items_id', 'int', ['value' => '0', 'nodefault' => false, 'comment' => 'RELATION to various table, according to itemtype (id)'])
      ->addField('itemtype', 'varchar(100)', ['value' => null, 'nodefault' => true])
      ->addIndexedField('devicesimcards_id', 'int', ['value' => '0'])
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('is_dynamic', 'boolean', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('serial', 'string')
      ->addIndexedField('otherserial', 'string')
      ->addIndexedField('states_id', 'int', ['value' => '0'])
      ->addIndexedField('locations_id', 'int', ['value' => '0'])
      ->addIndexedField('lines_id', 'int', ['value' => '0'])
      ->addField('pin', 'string', ['value' => ''])
      ->addField('pin2', 'string', ['value' => ''])
      ->addField('puk', 'string', ['value' => ''])
      ->addField('puk2', 'string', ['value' => ''])
      ->addField('msin', 'string', ['value' => ''])
      ->addKey('item', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_items_devicesoundcards')
      ->addField('items_id', 'int', ['value' => '0'])
      ->addField('itemtype', 'string')
      ->addIndexedField('devicesoundcards_id', 'int', ['value' => '0'])
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('is_dynamic', 'boolean', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('serial', 'string')
      ->addIndexedField('busID', 'string')
      ->addIndexedField('otherserial', 'string')
      ->addIndexedField('locations_id', 'int', ['value' => '0'])
      ->addIndexedField('states_id', 'int', ['value' => '0'])
      ->addKey('computers_id', ['items_id'])
      ->addKey('item', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_items_disks')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('itemtype', 'string')
      ->addIndexedField('items_id', 'int', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addIndexedField('device', 'string')
      ->addIndexedField('mountpoint', 'string')
      ->addIndexedField('filesystems_id', 'int', ['value' => '0'])
      ->addIndexedField('totalsize', 'int', ['value' => '0'])
      ->addIndexedField('freesize', 'int', ['value' => '0'])
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('is_dynamic', 'boolean', ['value' => '0'])
      ->addField('encryption_status', 'int', ['value' => '0'])
      ->addField('encryption_tool', 'string')
      ->addField('encryption_algorithm', 'string')
      ->addField('encryption_type', 'string')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->addKey('item', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_items_enclosures')
      ->addField('enclosures_id', 'int', ['value' => null, 'nodefault' => true])
      ->addField('itemtype', 'string', ['value' => null, 'nodefault' => true])
      ->addField('items_id', 'int', ['value' => null, 'nodefault' => true])
      ->addField('position', 'int', ['value' => null, 'nodefault' => true])
      ->addUniqueKey('item', ['itemtype', 'items_id'])
      ->addKey('relation', ['enclosures_id', 'itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_items_operatingsystems')
      ->addIndexedField('items_id', 'int', ['value' => '0'])
      ->addField('itemtype', 'string')
      ->addIndexedField('operatingsystems_id', 'int', ['value' => '0'])
      ->addIndexedField('operatingsystemversions_id', 'int', ['value' => '0'])
      ->addIndexedField('operatingsystemservicepacks_id', 'int', ['value' => '0'])
      ->addIndexedField('operatingsystemarchitectures_id', 'int', ['value' => '0'])
      ->addIndexedField('operatingsystemkernelversions_id', 'int', ['value' => '0'])
      ->addField('license_number', 'string')
      ->addField('licenseid', 'string')
      ->addIndexedField('operatingsystemeditions_id', 'int', ['value' => '0'])
      ->addField('date_mod', 'timestamp')
      ->addField('date_creation', 'timestamp')
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('is_dynamic', 'boolean', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addUniqueKey('unicity', ['items_id', 'itemtype', 'operatingsystems_id', 'operatingsystemarchitectures_id'])
      ->addKey('item', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_items_problems')
      ->addField('problems_id', 'int', ['value' => '0'])
      ->addField('itemtype', 'varchar(100)')
      ->addField('items_id', 'int', ['value' => '0'])
      ->addUniqueKey('unicity', ['problems_id', 'itemtype', 'items_id'])
      ->addKey('item', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_items_projects')
      ->addField('projects_id', 'int', ['value' => '0'])
      ->addField('itemtype', 'varchar(100)')
      ->addField('items_id', 'int', ['value' => '0'])
      ->addUniqueKey('unicity', ['projects_id', 'itemtype', 'items_id'])
      ->addKey('item', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_items_racks')
      ->addField('racks_id', 'int', ['value' => null, 'nodefault' => true])
      ->addField('itemtype', 'string', ['value' => null, 'nodefault' => true])
      ->addField('items_id', 'int', ['value' => null, 'nodefault' => true])
      ->addField('position', 'int', ['value' => null, 'nodefault' => true])
      ->addField('orientation', 'boolean')
      ->addField('bgcolor', 'varchar(7)')
      ->addField('hpos', 'boolean', ['value' => '0'])
      ->addField('is_reserved', 'boolean', ['value' => '0'])
      ->addUniqueKey('item', ['itemtype', 'items_id', 'is_reserved'])
      ->addKey('relation', ['racks_id', 'itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_items_tickets')
      ->addField('itemtype', 'string')
      ->addField('items_id', 'int', ['value' => '0'])
      ->addIndexedField('tickets_id', 'int', ['value' => '0'])
      ->addUniqueKey('unicity', ['itemtype', 'items_id', 'tickets_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_itilcategories')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('itilcategories_id', 'int', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addField('completename', 'text')
      ->addField('comment', 'text')
      ->addField('level', 'int', ['value' => '0'])
      ->addIndexedField('knowbaseitemcategories_id', 'int', ['value' => '0'])
      ->addIndexedField('users_id', 'int', ['value' => '0'])
      ->addIndexedField('groups_id', 'int', ['value' => '0'])
      ->addField('ancestors_cache', 'longtext')
      ->addField('sons_cache', 'longtext')
      ->addIndexedField('is_helpdeskvisible', 'boolean', ['value' => '1'])
      ->addIndexedField('tickettemplates_id_incident', 'int', ['value' => '0'])
      ->addIndexedField('tickettemplates_id_demand', 'int', ['value' => '0'])
      ->addIndexedField('is_incident', 'int', ['value' => '1'])
      ->addIndexedField('is_request', 'int', ['value' => '1'])
      ->addIndexedField('is_problem', 'int', ['value' => '1'])
      ->addIndexedField('is_change', 'boolean', ['value' => '1'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_itilfollowups')
      ->addIndexedField('itemtype', 'varchar(100)', ['value' => null, 'nodefault' => true])
      ->addField('items_id', 'int', ['value' => '0'])
      ->addIndexedField('date', 'timestamp')
      ->addIndexedField('users_id', 'int', ['value' => '0'])
      ->addIndexedField('users_id_editor', 'int', ['value' => '0'])
      ->addField('content', 'longtext')
      ->addIndexedField('is_private', 'boolean', ['value' => '0'])
      ->addIndexedField('requesttypes_id', 'int', ['value' => '0'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->addField('timeline_position', 'boolean', ['value' => '0'])
      ->addIndexedField('sourceitems_id', 'int', ['value' => '0'])
      ->addIndexedField('sourceof_items_id', 'int', ['value' => '0'])
      ->addKey('item_id', ['items_id'])
      ->addKey('item', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_itilsolutions')
      ->addIndexedField('itemtype', 'varchar(100)', ['value' => null, 'nodefault' => true])
      ->addField('items_id', 'int', ['value' => '0'])
      ->addIndexedField('solutiontypes_id', 'int', ['value' => '0'])
      ->addField('solutiontype_name', 'string')
      ->addField('content', 'longtext')
      ->addField('date_creation', 'timestamp')
      ->addField('date_mod', 'timestamp')
      ->addField('date_approval', 'timestamp')
      ->addIndexedField('users_id', 'int', ['value' => '0'])
      ->addField('user_name', 'string')
      ->addIndexedField('users_id_editor', 'int', ['value' => '0'])
      ->addIndexedField('users_id_approval', 'int', ['value' => '0'])
      ->addField('user_name_approval', 'string')
      ->addIndexedField('status', 'int', ['value' => '1'])
      ->addIndexedField('itilfollowups_id', 'int', ['value' => null, 'nodefault' => false, 'comment' => 'Followup reference on reject or approve a solution'])
      ->addKey('item_id', ['items_id'])
      ->addKey('item', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_itils_projects')
      ->addField('itemtype', 'varchar(100)', ['value' => ''])
      ->addField('items_id', 'int', ['value' => '0'])
      ->addIndexedField('projects_id', 'int', ['value' => '0'])
      ->addUniqueKey('unicity', ['itemtype', 'items_id', 'projects_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_knowbaseitemcategories')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addField('knowbaseitemcategories_id', 'int', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addField('completename', 'text')
      ->addField('comment', 'text')
      ->addField('level', 'int', ['value' => '0'])
      ->addField('sons_cache', 'longtext')
      ->addField('ancestors_cache', 'longtext')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->addUniqueKey('unicity', ['entities_id', 'knowbaseitemcategories_id', 'name'])
      ->createOrDie(true);

   $table_schema->init('glpi_knowbaseitems')
      ->addIndexedField('knowbaseitemcategories_id', 'int', ['value' => '0'])
      ->addField('name', 'text')
      ->addField('answer', 'longtext')
      ->addIndexedField('is_faq', 'boolean', ['value' => '0'])
      ->addIndexedField('users_id', 'int', ['value' => '0'])
      ->addField('view', 'int', ['value' => '0'])
      ->addField('date', 'timestamp')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('begin_date', 'timestamp')
      ->addIndexedField('end_date', 'timestamp')
      ->addFulltextKey('fulltext', ['name', 'answer'])
      ->addFulltextKey('name')
      ->addFulltextKey('answer')
      ->createOrDie(true);

   $table_schema->init('glpi_knowbaseitems_comments')
      ->addField('knowbaseitems_id', 'int', ['value' => null, 'nodefault' => true])
      ->addField('users_id', 'int', ['value' => '0'])
      ->addField('language', 'varchar(5)')
      ->addField('comment', 'text', ['value' => null, 'nodefault' => true])
      ->addField('parent_comment_id', 'int')
      ->addField('date_creation', 'timestamp')
      ->addField('date_mod', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_knowbaseitems_items')
      ->addField('knowbaseitems_id', 'int', ['value' => null, 'nodefault' => true])
      ->addIndexedField('itemtype', 'varchar(100)', ['value' => null, 'nodefault' => true])
      ->addField('items_id', 'int', ['value' => '0'])
      ->addField('date_creation', 'timestamp')
      ->addField('date_mod', 'timestamp')
      ->addUniqueKey('unicity', ['itemtype', 'items_id', 'knowbaseitems_id'])
      ->addKey('item_id', ['items_id'])
      ->addKey('item', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_knowbaseitems_profiles')
      ->addIndexedField('knowbaseitems_id', 'int', ['value' => '0'])
      ->addIndexedField('profiles_id', 'int', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '-1'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_knowbaseitems_revisions')
      ->addField('knowbaseitems_id', 'int', ['value' => null, 'nodefault' => true])
      ->addIndexedField('revision', 'int', ['value' => null, 'nodefault' => true])
      ->addField('name', 'text')
      ->addField('answer', 'longtext')
      ->addField('language', 'varchar(5)')
      ->addField('users_id', 'int', ['value' => '0'])
      ->addField('date_creation', 'timestamp')
      ->addUniqueKey('unicity', ['knowbaseitems_id', 'revision', 'language'])
      ->createOrDie(true);

   $table_schema->init('glpi_knowbaseitems_users')
      ->addIndexedField('knowbaseitems_id', 'int', ['value' => '0'])
      ->addIndexedField('users_id', 'int', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_knowbaseitemtranslations')
      ->addField('knowbaseitems_id', 'int', ['value' => '0'])
      ->addField('language', 'varchar(5)')
      ->addField('name', 'text')
      ->addField('answer', 'longtext')
      ->addIndexedField('users_id', 'int', ['value' => '0'])
      ->addField('date_mod', 'timestamp')
      ->addField('date_creation', 'timestamp')
      ->addKey('item', ['knowbaseitems_id', 'language'])
      ->addFullTextKey('fulltext', ['name', 'answer'])
      ->addFulltextKey('name')
      ->addFulltextKey('answer')
      ->createOrDie(true);

   $table_schema->init('glpi_lineoperators')
      ->addIndexedField('name', 'string', ['value' => ''])
      ->addField('comment', 'text')
      ->addField('mcc', 'int')
      ->addField('mnc', 'int')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->addUniqueKey('unicity', ['mcc', 'mnc'])
      ->createOrDie(true);

   $table_schema->init('glpi_lines')
      ->addField('name', 'string', ['value' => ''])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addField('is_deleted', 'boolean', ['value' => '0'])
      ->addField('caller_num', 'string', ['value' => ''])
      ->addField('caller_name', 'string', ['value' => ''])
      ->addIndexedField('users_id', 'int', ['value' => '0'])
      ->addField('groups_id', 'int', ['value' => '0'])
      ->addIndexedField('lineoperators_id', 'int', ['value' => '0'])
      ->addField('locations_id', 'int', ['value' => '0'])
      ->addField('states_id', 'int', ['value' => '0'])
      ->addField('linetypes_id', 'int', ['value' => '0'])
      ->addField('date_creation', 'timestamp')
      ->addField('date_mod', 'timestamp')
      ->addField('comment', 'text')
      ->createOrDie(true);

   $table_schema->init('glpi_linetypes')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_links')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addField('is_recursive', 'boolean', ['value' => '1'])
      ->addField('name', 'string')
      ->addField('link', 'string')
      ->addField('data', 'text')
      ->addField('open_window', 'boolean', ['value' => '1'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_links_itemtypes')
      ->addIndexedField('links_id', 'int', ['value' => '0'])
      ->addField('itemtype', 'varchar(100)', ['value' => null, 'nodefault' => true])
      ->addUniqueKey('unicity', ['itemtype', 'links_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_locations')
      ->addField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addIndexedField('locations_id', 'int', ['value' => '0'])
      ->addField('completename', 'text')
      ->addField('comment', 'text')
      ->addField('level', 'int', ['value' => '0'])
      ->addField('ancestors_cache', 'longtext')
      ->addField('sons_cache', 'longtext')
      ->addField('address', 'text')
      ->addField('postcode', 'string')
      ->addField('town', 'string')
      ->addField('state', 'string')
      ->addField('country', 'string')
      ->addField('building', 'string')
      ->addField('room', 'string')
      ->addField('latitude', 'string')
      ->addField('longitude', 'string')
      ->addField('altitude', 'string')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->addUniqueKey('unicity', ['entities_id', 'locations_id', 'name'])
      ->createOrDie(true);

   $table_schema->init('glpi_logs')
      ->addField('itemtype', 'varchar(100)', ['value' => ''])
      ->addField('items_id', 'int', ['value' => '0'])
      ->addIndexedField('itemtype_link', 'varchar(100)', ['value' => ''])
      ->addField('linked_action', 'int', ['value' => '0', 'nodefault' => false, 'comment' => 'see define.php HISTORY_* constant'])
      ->addField('user_name', 'string')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('id_search_option', 'int', ['value' => '0', 'nodefault' => false, 'comment' => 'see search.constant.php for value'])
      ->addField('old_value', 'string')
      ->addField('new_value', 'string')
      ->addKey('item', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_mailcollectors')
      ->addField('name', 'string')
      ->addField('host', 'string')
      ->addField('login', 'string')
      ->addField('filesize_max', 'int', ['value' => '2097152'])
      ->addIndexedField('is_active', 'boolean', ['value' => '1'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addField('comment', 'text')
      ->addField('passwd', 'string')
      ->addField('accepted', 'string')
      ->addField('refused', 'string')
      ->addField('use_kerberos', 'boolean', ['value' => '0'])
      ->addField('errors', 'int', ['value' => '0'])
      ->addField('use_mail_date', 'boolean', ['value' => '0'])
      ->addIndexedField('date_creation', 'timestamp')
      ->addField('requester_field', 'int', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_manufacturers')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_monitormodels')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('product_number', 'string')
      ->addField('weight', 'int', ['value' => '0'])
      ->addField('required_units', 'int', ['value' => '1'])
      ->addField('depth', 'float', ['value' => '1'])
      ->addField('power_connections', 'int', ['value' => '0'])
      ->addField('power_consumption', 'int', ['value' => '0'])
      ->addField('is_half_rack', 'boolean', ['value' => '0'])
      ->addField('picture_front', 'text')
      ->addField('picture_rear', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_monitors')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addField('date_mod', 'timestamp')
      ->addField('contact', 'string')
      ->addField('contact_num', 'string')
      ->addIndexedField('users_id_tech', 'int', ['value' => '0'])
      ->addIndexedField('groups_id_tech', 'int', ['value' => '0'])
      ->addField('comment', 'text')
      ->addIndexedField('serial', 'string')
      ->addIndexedField('otherserial', 'string')
      ->addField('size', 'decimal(5,2)', ['value' => '0.00'])
      ->addField('have_micro', 'boolean', ['value' => '0'])
      ->addField('have_speaker', 'boolean', ['value' => '0'])
      ->addField('have_subd', 'boolean', ['value' => '0'])
      ->addField('have_bnc', 'boolean', ['value' => '0'])
      ->addField('have_dvi', 'boolean', ['value' => '0'])
      ->addField('have_pivot', 'boolean', ['value' => '0'])
      ->addField('have_hdmi', 'boolean', ['value' => '0'])
      ->addField('have_displayport', 'boolean', ['value' => '0'])
      ->addIndexedField('locations_id', 'int', ['value' => '0'])
      ->addIndexedField('monitortypes_id', 'int', ['value' => '0'])
      ->addIndexedField('monitormodels_id', 'int', ['value' => '0'])
      ->addIndexedField('manufacturers_id', 'int', ['value' => '0'])
      ->addIndexedField('is_global', 'boolean', ['value' => '0'])
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('is_template', 'boolean', ['value' => '0'])
      ->addField('template_name', 'string')
      ->addIndexedField('users_id', 'int', ['value' => '0'])
      ->addIndexedField('groups_id', 'int', ['value' => '0'])
      ->addIndexedField('states_id', 'int', ['value' => '0'])
      ->addField('ticket_tco', 'decimal(20,4)', ['value' => '0.0000'])
      ->addIndexedField('is_dynamic', 'boolean', ['value' => '0'])
      ->addIndexedField('date_creation', 'timestamp')
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_monitortypes')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_netpoints')
      ->addField('entities_id', 'int', ['value' => '0'])
      ->addField('locations_id', 'int', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->addKey('complete', ['entities_id', 'locations_id', 'name'])
      ->addKey('location_name', ['locations_id', 'name'])
      ->createOrDie(true);

   $table_schema->init('glpi_networkaliases')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('networknames_id', 'int', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addField('fqdns_id', 'int', ['value' => '0'])
      ->addField('comment', 'text')
      ->createOrDie(true);

   $table_schema->init('glpi_networkequipmentmodels')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('product_number', 'string')
      ->addField('weight', 'int', ['value' => '0'])
      ->addField('required_units', 'int', ['value' => '1'])
      ->addField('depth', 'float', ['value' => '1'])
      ->addField('power_connections', 'int', ['value' => '0'])
      ->addField('power_consumption', 'int', ['value' => '0'])
      ->addField('is_half_rack', 'boolean', ['value' => '0'])
      ->addField('picture_front', 'text')
      ->addField('picture_rear', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_networkequipments')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addField('ram', 'string')
      ->addIndexedField('serial', 'string')
      ->addIndexedField('otherserial', 'string')
      ->addField('contact', 'string')
      ->addField('contact_num', 'string')
      ->addIndexedField('users_id_tech', 'int', ['value' => '0'])
      ->addIndexedField('groups_id_tech', 'int', ['value' => '0'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addField('comment', 'text')
      ->addIndexedField('locations_id', 'int', ['value' => '0'])
      ->addIndexedField('domains_id', 'int', ['value' => '0'])
      ->addIndexedField('networks_id', 'int', ['value' => '0'])
      ->addIndexedField('networkequipmenttypes_id', 'int', ['value' => '0'])
      ->addIndexedField('networkequipmentmodels_id', 'int', ['value' => '0'])
      ->addIndexedField('manufacturers_id', 'int', ['value' => '0'])
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('is_template', 'boolean', ['value' => '0'])
      ->addField('template_name', 'string')
      ->addIndexedField('users_id', 'int', ['value' => '0'])
      ->addIndexedField('groups_id', 'int', ['value' => '0'])
      ->addIndexedField('states_id', 'int', ['value' => '0'])
      ->addField('ticket_tco', 'decimal(20,4)', ['value' => '0.0000'])
      ->addIndexedField('is_dynamic', 'boolean', ['value' => '0'])
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_networkequipmenttypes')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_networkinterfaces')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->createOrDie(true);

   $table_schema->init('glpi_networknames')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addField('items_id', 'int', ['value' => '0'])
      ->addField('itemtype', 'varchar(100)', ['value' => null, 'nodefault' => true])
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('fqdns_id', 'int', ['value' => '0'])
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('is_dynamic', 'boolean', ['value' => '0'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->addKey('FQDN', ['name', 'fqdns_id'])
      ->addKey('item', ['itemtype', 'items_id', 'is_deleted'])
      ->createOrDie(true);

   $table_schema->init('glpi_networkportaggregates')
      ->addField('networkports_id', 'int', ['value' => '0'])
      ->addField('networkports_id_list', 'text', ['value' => null, 'nodefault' => false, 'comment' => 'array of associated networkports_id'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->addUniqueKey('networkports_id', ['networkports_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_networkportaliases')
      ->addField('networkports_id', 'int', ['value' => '0'])
      ->addIndexedField('networkports_id_alias', 'int', ['value' => '0'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->addUniqueKey('networkports_id', ['networkports_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_networkportdialups')
      ->addField('networkports_id', 'int', ['value' => '0'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->addUniqueKey('networkports_id', ['networkports_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_networkportethernets')
      ->addField('networkports_id', 'int', ['value' => '0'])
      ->addField('items_devicenetworkcards_id', 'int', ['value' => '0'])
      ->addField('netpoints_id', 'int', ['value' => '0'])
      ->addIndexedField('type', 'varchar(10)', ['value' => '', 'nodefault' => false, 'comment' => 'T, LX, SX'])
      ->addIndexedField('speed', 'int', ['value' => '10', 'nodefault' => false, 'comment' => 'Mbit/s: 10, 100, 1000, 10000'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->addUniqueKey('networkports_id', ['networkports_id'])
      ->addKey('card', ['items_devicenetworkcards_id'])
      ->addKey('netpoint', ['netpoints_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_networkportfiberchannels')
      ->addField('networkports_id', 'int', ['value' => '0'])
      ->addField('items_devicenetworkcards_id', 'int', ['value' => '0'])
      ->addField('netpoints_id', 'int', ['value' => '0'])
      ->addIndexedField('wwn', 'varchar(16)', ['value' => ''])
      ->addIndexedField('speed', 'int', ['value' => '10', 'nodefault' => false, 'comment' => 'Mbit/s: 10, 100, 1000, 10000'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->addUniqueKey('networkports_id', ['networkports_id'])
      ->addKey('card', ['items_devicenetworkcards_id'])
      ->addKey('netpoint', ['netpoints_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_networkportlocals')
      ->addField('networkports_id', 'int', ['value' => '0'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->addUniqueKey('networkports_id', ['networkports_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_networkports')
      ->addField('items_id', 'int', ['value' => '0'])
      ->addField('itemtype', 'varchar(100)', ['value' => null, 'nodefault' => true])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addField('logical_number', 'int', ['value' => '0'])
      ->addField('name', 'string')
      ->addField('instantiation_type', 'string')
      ->addIndexedField('mac', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('is_dynamic', 'boolean', ['value' => '0'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->addKey('on_device', ['items_id', 'itemtype'])
      ->addKey('item', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_networkports_networkports')
      ->addField('networkports_id_1', 'int', ['value' => '0'])
      ->addIndexedField('networkports_id_2', 'int', ['value' => '0'])
      ->addUniqueKey('unicity', ['networkports_id_1', 'networkports_id_2'])
      ->createOrDie(true);

   $table_schema->init('glpi_networkports_vlans')
      ->addField('networkports_id', 'int', ['value' => '0'])
      ->addIndexedField('vlans_id', 'int', ['value' => '0'])
      ->addField('tagged', 'boolean', ['value' => '0'])
      ->addUniqueKey('unicity', ['networkports_id', 'vlans_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_networkportwifis')
      ->addField('networkports_id', 'int', ['value' => '0'])
      ->addField('items_devicenetworkcards_id', 'int', ['value' => '0'])
      ->addField('wifinetworks_id', 'int', ['value' => '0'])
      ->addField('networkportwifis_id', 'int', ['value' => '0', 'nodefault' => false, 'comment' => 'only useful in case of Managed node'])
      ->addIndexedField('version', 'varchar(20)', ['value' => null, 'nodefault' => false, 'comment' => 'a, a/b, a/b/g, a/b/g/n, a/b/g/n/y'])
      ->addIndexedField('mode', 'varchar(20)', ['value' => null, 'nodefault' => false, 'comment' => 'ad-hoc, managed, master, repeater, secondary, monitor, auto'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->addUniqueKey('networkports_id', ['networkports_id'])
      ->addKey('card', ['items_devicenetworkcards_id'])
      ->addKey('essid', ['wifinetworks_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_networks')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_notepads')
      ->addField('itemtype', 'varchar(100)')
      ->addField('items_id', 'int', ['value' => '0'])
      ->addIndexedField('date', 'timestamp')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('users_id', 'int', ['value' => '0'])
      ->addIndexedField('users_id_lastupdater', 'int', ['value' => '0'])
      ->addField('content', 'longtext')
      ->addKey('item', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_notifications')
      ->addIndexedField('name', 'string')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('itemtype', 'varchar(100)', ['value' => null, 'nodefault' => true])
      ->addField('event', 'string', ['value' => null, 'nodefault' => true])
      ->addField('comment', 'text')
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('is_active', 'boolean', ['value' => '0'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_notifications_notificationtemplates')
      ->addIndexedField('notifications_id', 'int', ['value' => '0'])
      ->addIndexedField('mode', 'varchar(20)', ['value' => null, 'nodefault' => true, 'comment' => 'See Notification_NotificationTemplate::MODE_* constants'])
      ->addIndexedField('notificationtemplates_id', 'int', ['value' => '0'])
      ->addUniqueKey('unicity', ['notifications_id', 'mode', 'notificationtemplates_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_notificationtargets')
      ->addField('items_id', 'int', ['value' => '0'])
      ->addField('type', 'int', ['value' => '0'])
      ->addIndexedField('notifications_id', 'int', ['value' => '0'])
      ->addKey('items', ['type', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_notificationtemplates')
      ->addIndexedField('name', 'string')
      ->addIndexedField('itemtype', 'varchar(100)', ['value' => null, 'nodefault' => true])
      ->addIndexedField('date_mod', 'timestamp')
      ->addField('comment', 'text')
      ->addField('css', 'text')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_notificationtemplatetranslations')
      ->addIndexedField('notificationtemplates_id', 'int', ['value' => '0'])
      ->addField('language', 'char(5)', ['value' => ''])
      ->addField('subject', 'string', ['value' => null, 'nodefault' => true])
      ->addField('content_text', 'text')
      ->addField('content_html', 'text')
      ->createOrDie(true);

   $table_schema->init('glpi_notimportedemails')
      ->addField('from', 'string', ['value' => null, 'nodefault' => true])
      ->addField('to', 'string', ['value' => null, 'nodefault' => true])
      ->addIndexedField('mailcollectors_id', 'int', ['value' => '0'])
      ->addField('date', 'timestamp', ['value' => null, 'nodefault' => true])
      ->addField('subject', 'text')
      ->addField('messageid', 'string', ['value' => null, 'nodefault' => true])
      ->addField('reason', 'int', ['value' => '0'])
      ->addIndexedField('users_id', 'int', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_objectlocks')
      ->addField('itemtype', 'varchar(100)', ['value' => null, 'nodefault' => true, 'comment' => 'Type of locked object'])
      ->addField('items_id', 'int', ['value' => null, 'nodefault' => true, 'comment' => 'RELATION to various tables, according to itemtype (ID)'])
      ->addField('users_id', 'int', ['value' => null, 'nodefault' => true, 'comment' => 'id of the locker'])
      ->addField('date_mod', 'timestamp', ['value' => null, 'nodefault' => true, 'comment' => 'Timestamp of the lock'])
      ->addUniqueKey('item', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_olalevelactions')
      ->addIndexedField('olalevels_id', 'int', ['value' => '0'])
      ->addField('action_type', 'string')
      ->addField('field', 'string')
      ->addField('value', 'string')
      ->createOrDie(true);

   $table_schema->init('glpi_olalevelcriterias')
      ->addIndexedField('olalevels_id', 'int', ['value' => '0'])
      ->addField('criteria', 'string')
      ->addIndexedField('condition', 'int', ['value' => '0', 'nodefault' => false, 'comment' => 'see define.php PATTERN_* and REGEX_* constant'])
      ->addField('pattern', 'string')
      ->createOrDie(true);

   $table_schema->init('glpi_olalevels')
      ->addIndexedField('name', 'string')
      ->addIndexedField('olas_id', 'int', ['value' => '0'])
      ->addField('execution_time', 'int', ['value' => null, 'nodefault' => true])
      ->addIndexedField('is_active', 'boolean', ['value' => '1'])
      ->addField('entities_id', 'int', ['value' => '0'])
      ->addField('is_recursive', 'boolean', ['value' => '0'])
      ->addField('match', 'char(10)', ['value' => null, 'nodefault' => false, 'comment' => 'see define.php *_MATCHING constant'])
      ->addField('uuid', 'string')
      ->createOrDie(true);

   $table_schema->init('glpi_olalevels_tickets')
      ->addIndexedField('tickets_id', 'int', ['value' => '0'])
      ->addIndexedField('olalevels_id', 'int', ['value' => '0'])
      ->addField('date', 'timestamp')
      ->addUniqueKey('unicity', ['tickets_id', 'olalevels_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_olas')
      ->addIndexedField('name', 'string')
      ->addField('entities_id', 'int', ['value' => '0'])
      ->addField('is_recursive', 'boolean', ['value' => '0'])
      ->addField('type', 'int', ['value' => '0'])
      ->addField('comment', 'text')
      ->addField('number_time', 'int', ['value' => null, 'nodefault' => true])
      ->addIndexedField('calendars_id', 'int', ['value' => '0'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addField('definition_time', 'string')
      ->addField('end_of_working_day', 'boolean', ['value' => '0'])
      ->addIndexedField('date_creation', 'timestamp')
      ->addIndexedField('slms_id', 'int', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_operatingsystemarchitectures')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_operatingsystemeditions')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addField('date_mod', 'timestamp')
      ->addField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_operatingsystemkernels')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addField('date_mod', 'timestamp')
      ->addField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_operatingsystemkernelversions')
      ->addIndexedField('operatingsystemkernels_id', 'int', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addField('date_mod', 'timestamp')
      ->addField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_operatingsystems')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_operatingsystemservicepacks')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_operatingsystemversions')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_pdumodels')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('product_number', 'string')
      ->addField('weight', 'int', ['value' => '0'])
      ->addField('required_units', 'int', ['value' => '1'])
      ->addField('depth', 'float', ['value' => '1'])
      ->addField('power_connections', 'int', ['value' => '0'])
      ->addField('max_power', 'int', ['value' => '0'])
      ->addField('is_half_rack', 'boolean', ['value' => '0'])
      ->addField('picture_front', 'text')
      ->addField('picture_rear', 'text')
      ->addIndexedField('is_rackable', 'boolean', ['value' => '0'])
      ->addField('date_mod', 'timestamp')
      ->addField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_pdus')
      ->addField('name', 'string')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('locations_id', 'int', ['value' => '0'])
      ->addField('serial', 'string')
      ->addField('otherserial', 'string')
      ->addIndexedField('pdumodels_id', 'int')
      ->addIndexedField('users_id_tech', 'int', ['value' => '0'])
      ->addField('groups_id_tech', 'int', ['value' => '0'])
      ->addIndexedField('is_template', 'boolean', ['value' => '0'])
      ->addField('template_name', 'string')
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('states_id', 'int', ['value' => '0', 'nodefault' => false, 'comment' => 'RELATION to states (id)'])
      ->addField('comment', 'text')
      ->addIndexedField('manufacturers_id', 'int', ['value' => '0'])
      ->addIndexedField('pdutypes_id', 'int', ['value' => '0'])
      ->addField('date_mod', 'timestamp')
      ->addField('date_creation', 'timestamp')
      ->addKey('group_id_tech', ['groups_id_tech'])
      ->createOrDie(true);

   $table_schema->init('glpi_pdus_plugs')
      ->addIndexedField('plugs_id', 'int', ['value' => '0'])
      ->addIndexedField('pdus_id', 'int', ['value' => '0'])
      ->addField('number_plugs', 'int', ['value' => '0'])
      ->addField('date_mod', 'timestamp')
      ->addField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_pdus_racks')
      ->addIndexedField('racks_id', 'int', ['value' => '0'])
      ->addIndexedField('pdus_id', 'int', ['value' => '0'])
      ->addField('side', 'int', ['value' => '0'])
      ->addField('position', 'int', ['value' => null, 'nodefault' => true])
      ->addField('bgcolor', 'varchar(7)')
      ->addField('date_mod', 'timestamp')
      ->addField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_pdutypes')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_creation', 'timestamp')
      ->addIndexedField('date_mod', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_peripheralmodels')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('product_number', 'string')
      ->addField('weight', 'int', ['value' => '0'])
      ->addField('required_units', 'int', ['value' => '1'])
      ->addField('depth', 'float', ['value' => '1'])
      ->addField('power_connections', 'int', ['value' => '0'])
      ->addField('power_consumption', 'int', ['value' => '0'])
      ->addField('is_half_rack', 'boolean', ['value' => '0'])
      ->addField('picture_front', 'text')
      ->addField('picture_rear', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_peripherals')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addIndexedField('date_mod', 'timestamp')
      ->addField('contact', 'string')
      ->addField('contact_num', 'string')
      ->addIndexedField('users_id_tech', 'int', ['value' => '0'])
      ->addIndexedField('groups_id_tech', 'int', ['value' => '0'])
      ->addField('comment', 'text')
      ->addIndexedField('serial', 'string')
      ->addIndexedField('otherserial', 'string')
      ->addIndexedField('locations_id', 'int', ['value' => '0'])
      ->addIndexedField('peripheraltypes_id', 'int', ['value' => '0'])
      ->addIndexedField('peripheralmodels_id', 'int', ['value' => '0'])
      ->addField('brand', 'string')
      ->addIndexedField('manufacturers_id', 'int', ['value' => '0'])
      ->addIndexedField('is_global', 'boolean', ['value' => '0'])
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('is_template', 'boolean', ['value' => '0'])
      ->addField('template_name', 'string')
      ->addIndexedField('users_id', 'int', ['value' => '0'])
      ->addIndexedField('groups_id', 'int', ['value' => '0'])
      ->addIndexedField('states_id', 'int', ['value' => '0'])
      ->addField('ticket_tco', 'decimal(20,4)', ['value' => '0.0000'])
      ->addIndexedField('is_dynamic', 'boolean', ['value' => '0'])
      ->addIndexedField('date_creation', 'timestamp')
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_peripheraltypes')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_phonemodels')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('product_number', 'string')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_phonepowersupplies')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_phones')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addIndexedField('date_mod', 'timestamp')
      ->addField('contact', 'string')
      ->addField('contact_num', 'string')
      ->addIndexedField('users_id_tech', 'int', ['value' => '0'])
      ->addIndexedField('groups_id_tech', 'int', ['value' => '0'])
      ->addField('comment', 'text')
      ->addIndexedField('serial', 'string')
      ->addIndexedField('otherserial', 'string')
      ->addIndexedField('locations_id', 'int', ['value' => '0'])
      ->addIndexedField('phonetypes_id', 'int', ['value' => '0'])
      ->addIndexedField('phonemodels_id', 'int', ['value' => '0'])
      ->addField('brand', 'string')
      ->addIndexedField('phonepowersupplies_id', 'int', ['value' => '0'])
      ->addField('number_line', 'string')
      ->addField('have_headset', 'boolean', ['value' => '0'])
      ->addField('have_hp', 'boolean', ['value' => '0'])
      ->addIndexedField('manufacturers_id', 'int', ['value' => '0'])
      ->addIndexedField('is_global', 'boolean', ['value' => '0'])
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('is_template', 'boolean', ['value' => '0'])
      ->addField('template_name', 'string')
      ->addIndexedField('users_id', 'int', ['value' => '0'])
      ->addIndexedField('groups_id', 'int', ['value' => '0'])
      ->addIndexedField('states_id', 'int', ['value' => '0'])
      ->addField('ticket_tco', 'decimal(20,4)', ['value' => '0.0000'])
      ->addIndexedField('is_dynamic', 'boolean', ['value' => '0'])
      ->addIndexedField('date_creation', 'timestamp')
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_phonetypes')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_planningrecalls')
      ->addField('items_id', 'int', ['value' => '0'])
      ->addField('itemtype', 'varchar(100)', ['value' => null, 'nodefault' => true])
      ->addIndexedField('users_id', 'int', ['value' => '0'])
      ->addIndexedField('before_time', 'int', ['value' => '-10'])
      ->addIndexedField('when', 'timestamp')
      ->addUniqueKey('unicity', ['itemtype', 'items_id', 'users_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_plugins')
      ->addField('directory', 'string', ['value' => null, 'nodefault' => true])
      ->addField('name', 'string', ['value' => null, 'nodefault' => true])
      ->addField('version', 'string', ['value' => null, 'nodefault' => true])
      ->addIndexedField('state', 'int', ['value' => '0', 'nodefault' => false, 'comment' => 'see define.php PLUGIN_* constant'])
      ->addField('author', 'string')
      ->addField('homepage', 'string')
      ->addField('license', 'string')
      ->addUniqueKey('unicity', ['directory'])
      ->createOrDie(true);

   $table_schema->init('glpi_plugs')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_printermodels')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('product_number', 'string')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_printers')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addIndexedField('date_mod', 'timestamp')
      ->addField('contact', 'string')
      ->addField('contact_num', 'string')
      ->addIndexedField('users_id_tech', 'int', ['value' => '0'])
      ->addIndexedField('groups_id_tech', 'int', ['value' => '0'])
      ->addIndexedField('serial', 'string')
      ->addIndexedField('otherserial', 'string')
      ->addField('have_serial', 'boolean', ['value' => '0'])
      ->addField('have_parallel', 'boolean', ['value' => '0'])
      ->addField('have_usb', 'boolean', ['value' => '0'])
      ->addField('have_wifi', 'boolean', ['value' => '0'])
      ->addField('have_ethernet', 'boolean', ['value' => '0'])
      ->addField('comment', 'text')
      ->addField('memory_size', 'string')
      ->addIndexedField('locations_id', 'int', ['value' => '0'])
      ->addIndexedField('domains_id', 'int', ['value' => '0'])
      ->addIndexedField('networks_id', 'int', ['value' => '0'])
      ->addIndexedField('printertypes_id', 'int', ['value' => '0'])
      ->addIndexedField('printermodels_id', 'int', ['value' => '0'])
      ->addIndexedField('manufacturers_id', 'int', ['value' => '0'])
      ->addIndexedField('is_global', 'boolean', ['value' => '0'])
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('is_template', 'boolean', ['value' => '0'])
      ->addField('template_name', 'string')
      ->addField('init_pages_counter', 'int', ['value' => '0'])
      ->addIndexedField('last_pages_counter', 'int', ['value' => '0'])
      ->addIndexedField('users_id', 'int', ['value' => '0'])
      ->addIndexedField('groups_id', 'int', ['value' => '0'])
      ->addIndexedField('states_id', 'int', ['value' => '0'])
      ->addField('ticket_tco', 'decimal(20,4)', ['value' => '0.0000'])
      ->addIndexedField('is_dynamic', 'boolean', ['value' => '0'])
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_printertypes')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_problemcosts')
      ->addIndexedField('problems_id', 'int', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('begin_date', 'date')
      ->addIndexedField('end_date', 'date')
      ->addField('actiontime', 'int', ['value' => '0'])
      ->addField('cost_time', 'decimal(20,4)', ['value' => '0.0000'])
      ->addField('cost_fixed', 'decimal(20,4)', ['value' => '0.0000'])
      ->addField('cost_material', 'decimal(20,4)', ['value' => '0.0000'])
      ->addIndexedField('budgets_id', 'int', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_problems')
      ->addIndexedField('name', 'string')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('status', 'int', ['value' => '1'])
      ->addField('content', 'longtext')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date', 'timestamp')
      ->addIndexedField('solvedate', 'timestamp')
      ->addIndexedField('closedate', 'timestamp')
      ->addIndexedField('time_to_resolve', 'timestamp')
      ->addIndexedField('users_id_recipient', 'int', ['value' => '0'])
      ->addIndexedField('users_id_lastupdater', 'int', ['value' => '0'])
      ->addIndexedField('urgency', 'int', ['value' => '1'])
      ->addIndexedField('impact', 'int', ['value' => '1'])
      ->addIndexedField('priority', 'int', ['value' => '1'])
      ->addIndexedField('itilcategories_id', 'int', ['value' => '0'])
      ->addField('impactcontent', 'longtext')
      ->addField('causecontent', 'longtext')
      ->addField('symptomcontent', 'longtext')
      ->addField('actiontime', 'int', ['value' => '0'])
      ->addField('begin_waiting_date', 'timestamp')
      ->addField('waiting_duration', 'int', ['value' => '0'])
      ->addField('close_delay_stat', 'int', ['value' => '0'])
      ->addField('solve_delay_stat', 'int', ['value' => '0'])
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_problems_suppliers')
      ->addField('problems_id', 'int', ['value' => '0'])
      ->addField('suppliers_id', 'int', ['value' => '0'])
      ->addField('type', 'int', ['value' => '1'])
      ->addField('use_notification', 'boolean', ['value' => '0'])
      ->addField('alternative_email', 'string')
      ->addUniqueKey('unicity', ['problems_id', 'type', 'suppliers_id'])
      ->addKey('group', ['suppliers_id', 'type'])
      ->createOrDie(true);

   $table_schema->init('glpi_problems_tickets')
      ->addField('problems_id', 'int', ['value' => '0'])
      ->addIndexedField('tickets_id', 'int', ['value' => '0'])
      ->addUniqueKey('unicity', ['problems_id', 'tickets_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_problems_users')
      ->addField('problems_id', 'int', ['value' => '0'])
      ->addField('users_id', 'int', ['value' => '0'])
      ->addField('type', 'int', ['value' => '1'])
      ->addField('use_notification', 'boolean', ['value' => '0'])
      ->addField('alternative_email', 'string')
      ->addUniqueKey('unicity', ['problems_id', 'type', 'users_id', 'alternative_email'])
      ->addKey('user', ['users_id', 'type'])
      ->createOrDie(true);

   $table_schema->init('glpi_problemtasks')
      ->addIndexedField('problems_id', 'int', ['value' => '0'])
      ->addIndexedField('taskcategories_id', 'int', ['value' => '0'])
      ->addIndexedField('date', 'timestamp')
      ->addIndexedField('begin', 'timestamp')
      ->addIndexedField('end', 'timestamp')
      ->addIndexedField('users_id', 'int', ['value' => '0'])
      ->addIndexedField('users_id_editor', 'int', ['value' => '0'])
      ->addIndexedField('users_id_tech', 'int', ['value' => '0'])
      ->addIndexedField('groups_id_tech', 'int', ['value' => '0'])
      ->addField('content', 'longtext')
      ->addField('actiontime', 'int', ['value' => '0'])
      ->addIndexedField('state', 'int', ['value' => '0'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->addIndexedField('tasktemplates_id', 'int', ['value' => '0'])
      ->addField('timeline_position', 'boolean', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_profilerights')
      ->addField('profiles_id', 'int', ['value' => '0'])
      ->addField('name', 'string')
      ->addField('rights', 'int', ['value' => '0'])
      ->addUniqueKey('unicity', ['profiles_id', 'name'])
      ->createOrDie(true);

   $table_schema->init('glpi_profiles')
      ->addField('name', 'string')
      ->addIndexedField('interface', 'string', ['value' => 'helpdesk'])
      ->addIndexedField('is_default', 'boolean', ['value' => '0'])
      ->addField('helpdesk_hardware', 'int', ['value' => '0'])
      ->addField('helpdesk_item_type', 'text')
      ->addField('ticket_status', 'text', ['value' => null, 'nodefault' => false, 'comment' => 'json encoded array of from/dest allowed status change'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addField('comment', 'text')
      ->addField('problem_status', 'text', ['value' => null, 'nodefault' => false, 'comment' => 'json encoded array of from/dest allowed status change'])
      ->addField('create_ticket_on_login', 'boolean', ['value' => '0'])
      ->addField('tickettemplates_id', 'int', ['value' => '0'])
      ->addField('change_status', 'text', ['value' => null, 'nodefault' => false, 'comment' => 'json encoded array of from/dest allowed status change'])
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_profiles_reminders')
      ->addIndexedField('reminders_id', 'int', ['value' => '0'])
      ->addIndexedField('profiles_id', 'int', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '-1'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_profiles_rssfeeds')
      ->addIndexedField('rssfeeds_id', 'int', ['value' => '0'])
      ->addIndexedField('profiles_id', 'int', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '-1'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_profiles_users')
      ->addIndexedField('users_id', 'int', ['value' => '0'])
      ->addIndexedField('profiles_id', 'int', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '1'])
      ->addIndexedField('is_dynamic', 'boolean', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_projectcosts')
      ->addIndexedField('projects_id', 'int', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('begin_date', 'date')
      ->addIndexedField('end_date', 'date')
      ->addField('cost', 'decimal(20,4)', ['value' => '0.0000'])
      ->addIndexedField('budgets_id', 'int', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_projects')
      ->addIndexedField('name', 'string')
      ->addIndexedField('code', 'string')
      ->addIndexedField('priority', 'int', ['value' => '1'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('projects_id', 'int', ['value' => '0'])
      ->addIndexedField('projectstates_id', 'int', ['value' => '0'])
      ->addIndexedField('projecttypes_id', 'int', ['value' => '0'])
      ->addIndexedField('date', 'timestamp')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('users_id', 'int', ['value' => '0'])
      ->addIndexedField('groups_id', 'int', ['value' => '0'])
      ->addIndexedField('plan_start_date', 'timestamp')
      ->addIndexedField('plan_end_date', 'timestamp')
      ->addIndexedField('real_start_date', 'timestamp')
      ->addIndexedField('real_end_date', 'timestamp')
      ->addIndexedField('percent_done', 'int', ['value' => '0'])
      ->addIndexedField('show_on_global_gantt', 'boolean', ['value' => '0'])
      ->addField('content', 'longtext')
      ->addField('comment', 'longtext')
      ->addField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('date_creation', 'timestamp')
      ->addIndexedField('projecttemplates_id', 'int', ['value' => '0'])
      ->addIndexedField('is_template', 'boolean', ['value' => '0'])
      ->addField('template_name', 'string')
      ->createOrDie(true);

   $table_schema->init('glpi_projectstates')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addField('color', 'string')
      ->addIndexedField('is_finished', 'boolean', ['value' => '0'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_projecttasks')
      ->addIndexedField('name', 'string')
      ->addField('content', 'longtext')
      ->addField('comment', 'longtext')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('projects_id', 'int', ['value' => '0'])
      ->addIndexedField('projecttasks_id', 'int', ['value' => '0'])
      ->addIndexedField('date', 'timestamp')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('plan_start_date', 'timestamp')
      ->addIndexedField('plan_end_date', 'timestamp')
      ->addIndexedField('real_start_date', 'timestamp')
      ->addIndexedField('real_end_date', 'timestamp')
      ->addField('planned_duration', 'int', ['value' => '0'])
      ->addField('effective_duration', 'int', ['value' => '0'])
      ->addIndexedField('projectstates_id', 'int', ['value' => '0'])
      ->addIndexedField('projecttasktypes_id', 'int', ['value' => '0'])
      ->addIndexedField('users_id', 'int', ['value' => '0'])
      ->addIndexedField('percent_done', 'int', ['value' => '0'])
      ->addIndexedField('is_milestone', 'boolean', ['value' => '0'])
      ->addIndexedField('projecttasktemplates_id', 'int', ['value' => '0'])
      ->addIndexedField('is_template', 'boolean', ['value' => '0'])
      ->addField('template_name', 'string')
      ->createOrDie(true);

   $table_schema->init('glpi_projecttasks_tickets')
      ->addField('tickets_id', 'int', ['value' => '0'])
      ->addField('projecttasks_id', 'int', ['value' => '0'])
      ->addUniqueKey('unicity', ['tickets_id', 'projecttasks_id'])
      ->addKey('projects_id', ['projecttasks_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_projecttaskteams')
      ->addField('projecttasks_id', 'int', ['value' => '0'])
      ->addField('itemtype', 'varchar(100)')
      ->addField('items_id', 'int', ['value' => '0'])
      ->addUniqueKey('unicity', ['projecttasks_id', 'itemtype', 'items_id'])
      ->addKey('item', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_projecttasktemplates')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addField('description', 'longtext')
      ->addField('comment', 'longtext')
      ->addIndexedField('projects_id', 'int', ['value' => '0'])
      ->addIndexedField('projecttasks_id', 'int', ['value' => '0'])
      ->addIndexedField('plan_start_date', 'timestamp')
      ->addIndexedField('plan_end_date', 'timestamp')
      ->addIndexedField('real_start_date', 'timestamp')
      ->addIndexedField('real_end_date', 'timestamp')
      ->addField('planned_duration', 'int', ['value' => '0'])
      ->addField('effective_duration', 'int', ['value' => '0'])
      ->addIndexedField('projectstates_id', 'int', ['value' => '0'])
      ->addIndexedField('projecttasktypes_id', 'int', ['value' => '0'])
      ->addIndexedField('users_id', 'int', ['value' => '0'])
      ->addIndexedField('percent_done', 'int', ['value' => '0'])
      ->addIndexedField('is_milestone', 'boolean', ['value' => '0'])
      ->addField('comments', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_projecttasktypes')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_projectteams')
      ->addField('projects_id', 'int', ['value' => '0'])
      ->addField('itemtype', 'varchar(100)')
      ->addField('items_id', 'int', ['value' => '0'])
      ->addUniqueKey('unicity', ['projects_id', 'itemtype', 'items_id'])
      ->addKey('item', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_projecttypes')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_queuednotifications')
      ->addField('itemtype', 'varchar(100)')
      ->addField('items_id', 'int', ['value' => '0'])
      ->addField('notificationtemplates_id', 'int', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('sent_try', 'int', ['value' => '0'])
      ->addIndexedField('create_time', 'timestamp')
      ->addIndexedField('send_time', 'timestamp')
      ->addIndexedField('sent_time', 'timestamp')
      ->addField('name', 'text')
      ->addField('sender', 'text')
      ->addField('sendername', 'text')
      ->addField('recipient', 'text')
      ->addField('recipientname', 'text')
      ->addField('replyto', 'text')
      ->addField('replytoname', 'text')
      ->addField('headers', 'text')
      ->addField('body_html', 'longtext')
      ->addField('body_text', 'longtext')
      ->addField('messageid', 'text')
      ->addField('documents', 'text')
      ->addIndexedField('mode', 'varchar(20)', ['value' => null, 'nodefault' => true, 'comment' => 'See Notification_NotificationTemplate::MODE_* constants'])
      ->addKey('item', ['itemtype', 'items_id', 'notificationtemplates_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_rackmodels')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('product_number', 'string')
      ->addField('date_mod', 'timestamp')
      ->addField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_racks')
      ->addField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('locations_id', 'int', ['value' => '0'])
      ->addField('serial', 'string')
      ->addField('otherserial', 'string')
      ->addIndexedField('rackmodels_id', 'int')
      ->addIndexedField('manufacturers_id', 'int', ['value' => '0'])
      ->addIndexedField('racktypes_id', 'int', ['value' => '0'])
      ->addIndexedField('states_id', 'int', ['value' => '0'])
      ->addIndexedField('users_id_tech', 'int', ['value' => '0'])
      ->addField('groups_id_tech', 'int', ['value' => '0'])
      ->addField('width', 'int')
      ->addField('height', 'int')
      ->addField('depth', 'int')
      ->addField('number_units', 'int', ['value' => '0'])
      ->addIndexedField('is_template', 'boolean', ['value' => '0'])
      ->addField('template_name', 'string')
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('dcrooms_id', 'int', ['value' => '0'])
      ->addField('room_orientation', 'int', ['value' => '0'])
      ->addField('position', 'varchar(50)')
      ->addField('bgcolor', 'varchar(7)')
      ->addField('max_power', 'int', ['value' => '0'])
      ->addField('mesured_power', 'int', ['value' => '0'])
      ->addField('max_weight', 'int', ['value' => '0'])
      ->addField('date_mod', 'timestamp')
      ->addField('date_creation', 'timestamp')
      ->addKey('group_id_tech', ['groups_id_tech'])
      ->createOrDie(true);

   $table_schema->init('glpi_racktypes')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_creation', 'timestamp')
      ->addIndexedField('date_mod', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_registeredids')
      ->addIndexedField('name', 'string')
      ->addField('items_id', 'int', ['value' => '0'])
      ->addField('itemtype', 'varchar(100)', ['value' => null, 'nodefault' => true])
      ->addIndexedField('device_type', 'varchar(100)', ['value' => null, 'nodefault' => true, 'comment' => 'USB, PCI ...'])
      ->addKey('item', ['items_id', 'itemtype'])
      ->createOrDie(true);

   $table_schema->init('glpi_reminders')
      ->addIndexedField('date', 'timestamp')
      ->addIndexedField('users_id', 'int', ['value' => '0'])
      ->addField('name', 'string')
      ->addField('text', 'text')
      ->addIndexedField('begin', 'timestamp')
      ->addIndexedField('end', 'timestamp')
      ->addIndexedField('is_planned', 'boolean', ['value' => '0'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('state', 'int', ['value' => '0'])
      ->addField('begin_view_date', 'timestamp')
      ->addField('end_view_date', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_reminders_users')
      ->addIndexedField('reminders_id', 'int', ['value' => '0'])
      ->addIndexedField('users_id', 'int', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_requesttypes')
      ->addIndexedField('name', 'string')
      ->addIndexedField('is_helpdesk_default', 'boolean', ['value' => '0'])
      ->addIndexedField('is_followup_default', 'boolean', ['value' => '0'])
      ->addIndexedField('is_mail_default', 'boolean', ['value' => '0'])
      ->addIndexedField('is_mailfollowup_default', 'boolean', ['value' => '0'])
      ->addIndexedField('is_active', 'boolean', ['value' => '1'])
      ->addIndexedField('is_ticketheader', 'boolean', ['value' => '1'])
      ->addIndexedField('is_itilfollowup', 'boolean', ['value' => '1'])
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_reservationitems')
      ->addField('itemtype', 'varchar(100)', ['value' => null, 'nodefault' => true])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addField('items_id', 'int', ['value' => '0'])
      ->addField('comment', 'text')
      ->addIndexedField('is_active', 'boolean', ['value' => '1'])
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addKey('item', ['itemtype', 'items_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_reservations')
      ->addIndexedField('reservationitems_id', 'int', ['value' => '0'])
      ->addIndexedField('begin', 'timestamp')
      ->addIndexedField('end', 'timestamp')
      ->addIndexedField('users_id', 'int', ['value' => '0'])
      ->addField('comment', 'text')
      ->addField('group', 'int', ['value' => '0'])
      ->addKey('resagroup', ['reservationitems_id', 'group'])
      ->createOrDie(true);

   $table_schema->init('glpi_rssfeeds')
      ->addIndexedField('name', 'string')
      ->addIndexedField('users_id', 'int', ['value' => '0'])
      ->addField('comment', 'text')
      ->addField('url', 'text')
      ->addField('refresh_rate', 'int', ['value' => '86400'])
      ->addField('max_items', 'int', ['value' => '20'])
      ->addIndexedField('have_error', 'boolean', ['value' => '0'])
      ->addIndexedField('is_active', 'boolean', ['value' => '0'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_rssfeeds_users')
      ->addIndexedField('rssfeeds_id', 'int', ['value' => '0'])
      ->addIndexedField('users_id', 'int', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_ruleactions')
      ->addIndexedField('rules_id', 'int', ['value' => '0'])
      ->addField('action_type', 'string', ['value' => null, 'nodefault' => false, 'comment' => 'VALUE IN (assign, regex_result, append_regex_result, affectbyip, affectbyfqdn, affectbymac)'])
      ->addField('field', 'string')
      ->addField('value', 'string')
      ->addKey('field_value', ['field' => 50, 'value' => 50])
      ->createOrDie(true);

   $table_schema->init('glpi_rulecriterias')
      ->addIndexedField('rules_id', 'int', ['value' => '0'])
      ->addField('criteria', 'string')
      ->addIndexedField('condition', 'int', ['value' => '0', 'nodefault' => false, 'comment' => 'see define.php PATTERN_* and REGEX_* constant'])
      ->addField('pattern', 'text')
      ->createOrDie(true);

   $table_schema->init('glpi_rulerightparameters')
      ->addField('name', 'string')
      ->addField('value', 'string')
      ->addField('comment', 'text', ['value' => null, 'nodefault' => true])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_rules')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('sub_type', 'string', ['value' => ''])
      ->addField('ranking', 'int', ['value' => '0'])
      ->addField('name', 'string')
      ->addField('description', 'text')
      ->addField('match', 'char(10)', ['value' => null, 'nodefault' => false, 'comment' => 'see define.php *_MATCHING constant'])
      ->addIndexedField('is_active', 'boolean', ['value' => '1'])
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addField('uuid', 'string')
      ->addIndexedField('condition', 'int', ['value' => '0'])
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_savedsearches')
      ->addField('name', 'string')
      ->addIndexedField('type', 'int', ['value' => '0', 'nodefault' => false, 'comment' => 'see SavedSearch:: constants'])
      ->addIndexedField('itemtype', 'varchar(100)', ['value' => null, 'nodefault' => true])
      ->addIndexedField('users_id', 'int', ['value' => '0'])
      ->addIndexedField('is_private', 'boolean', ['value' => '1'])
      ->addIndexedField('entities_id', 'int', ['value' => '-1'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addField('path', 'string')
      ->addField('query', 'text')
      ->addIndexedField('last_execution_time', 'int')
      ->addIndexedField('do_count', 'boolean', ['value' => '2', 'nodefault' => false, 'comment' => 'Do or do not count results on list display see SavedSearch::COUNT_* constants'])
      ->addIndexedField('last_execution_date', 'timestamp')
      ->addField('counter', 'int', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_savedsearches_alerts')
      ->addField('savedsearches_id', 'int', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addIndexedField('is_active', 'boolean', ['value' => '0'])
      ->addField('operator', 'boolean', ['value' => null, 'nodefault' => true])
      ->addField('value', 'int', ['value' => null, 'nodefault' => true])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->addUniqueKey('unicity', ['savedsearches_id', 'operator', 'value'])
      ->createOrDie(true);

   $table_schema->init('glpi_savedsearches_users')
      ->addField('users_id', 'int', ['value' => '0'])
      ->addField('itemtype', 'varchar(100)', ['value' => null, 'nodefault' => true])
      ->addIndexedField('savedsearches_id', 'int', ['value' => '0'])
      ->addUniqueKey('unicity', ['users_id', 'itemtype'])
      ->createOrDie(true);

   $table_schema->init('glpi_slalevelactions')
      ->addIndexedField('slalevels_id', 'int', ['value' => '0'])
      ->addField('action_type', 'string')
      ->addField('field', 'string')
      ->addField('value', 'string')
      ->createOrDie(true);

   $table_schema->init('glpi_slalevelcriterias')
      ->addIndexedField('slalevels_id', 'int', ['value' => '0'])
      ->addField('criteria', 'string')
      ->addIndexedField('condition', 'int', ['value' => '0', 'nodefault' => false, 'comment' => 'see define.php PATTERN_* and REGEX_* constant'])
      ->addField('pattern', 'string')
      ->createOrDie(true);

   $table_schema->init('glpi_slalevels')
      ->addIndexedField('name', 'string')
      ->addIndexedField('slas_id', 'int', ['value' => '0'])
      ->addField('execution_time', 'int', ['value' => null, 'nodefault' => true])
      ->addIndexedField('is_active', 'boolean', ['value' => '1'])
      ->addField('entities_id', 'int', ['value' => '0'])
      ->addField('is_recursive', 'boolean', ['value' => '0'])
      ->addField('match', 'char(10)', ['value' => null, 'nodefault' => false, 'comment' => 'see define.php *_MATCHING constant'])
      ->addField('uuid', 'string')
      ->createOrDie(true);

   $table_schema->init('glpi_slalevels_tickets')
      ->addIndexedField('tickets_id', 'int', ['value' => '0'])
      ->addIndexedField('slalevels_id', 'int', ['value' => '0'])
      ->addField('date', 'timestamp')
      ->addUniqueKey('unicity', ['tickets_id', 'slalevels_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_slas')
      ->addIndexedField('name', 'string')
      ->addField('entities_id', 'int', ['value' => '0'])
      ->addField('is_recursive', 'boolean', ['value' => '0'])
      ->addField('type', 'int', ['value' => '0'])
      ->addField('comment', 'text')
      ->addField('number_time', 'int', ['value' => null, 'nodefault' => true])
      ->addIndexedField('calendars_id', 'int', ['value' => '0'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addField('definition_time', 'string')
      ->addField('end_of_working_day', 'boolean', ['value' => '0'])
      ->addIndexedField('date_creation', 'timestamp')
      ->addIndexedField('slms_id', 'int', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_slms')
      ->addIndexedField('name', 'string')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addField('comment', 'text')
      ->addIndexedField('calendars_id', 'int', ['value' => '0'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_softwarecategories')
      ->addField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('softwarecategories_id', 'int', ['value' => '0'])
      ->addField('completename', 'text')
      ->addField('level', 'int', ['value' => '0'])
      ->addField('ancestors_cache', 'longtext')
      ->addField('sons_cache', 'longtext')
      ->createOrDie(true);

   $table_schema->init('glpi_softwarelicenses')
      ->addField('softwares_id', 'int', ['value' => '0'])
      ->addField('softwarelicenses_id', 'int', ['value' => '0'])
      ->addField('completename', 'text')
      ->addField('level', 'int', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addField('is_recursive', 'boolean', ['value' => '0'])
      ->addField('number', 'int', ['value' => '0'])
      ->addIndexedField('softwarelicensetypes_id', 'int', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addIndexedField('serial', 'string')
      ->addIndexedField('otherserial', 'string')
      ->addIndexedField('softwareversions_id_buy', 'int', ['value' => '0'])
      ->addIndexedField('softwareversions_id_use', 'int', ['value' => '0'])
      ->addIndexedField('expire', 'date')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addField('is_valid', 'boolean', ['value' => '1'])
      ->addIndexedField('date_creation', 'timestamp')
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('locations_id', 'int', ['value' => '0'])
      ->addIndexedField('users_id_tech', 'int', ['value' => '0'])
      ->addIndexedField('users_id', 'int', ['value' => '0'])
      ->addIndexedField('groups_id_tech', 'int', ['value' => '0'])
      ->addIndexedField('groups_id', 'int', ['value' => '0'])
      ->addIndexedField('is_helpdesk_visible', 'boolean', ['value' => '0'])
      ->addIndexedField('is_template', 'boolean', ['value' => '0'])
      ->addField('template_name', 'string')
      ->addIndexedField('states_id', 'int', ['value' => '0'])
      ->addIndexedField('manufacturers_id', 'int', ['value' => '0'])
      ->addField('contact', 'string')
      ->addField('contact_num', 'string')
      ->addIndexedField('allow_overquota', 'boolean', ['value' => '0'])
      ->addKey('softwares_id_expire', ['softwares_id', 'expire'])
      ->createOrDie(true);

   $table_schema->init('glpi_softwarelicensetypes')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->addIndexedField('softwarelicensetypes_id', 'int', ['value' => '0'])
      ->addField('level', 'int', ['value' => '0'])
      ->addField('ancestors_cache', 'longtext')
      ->addField('sons_cache', 'longtext')
      ->addField('entities_id', 'int', ['value' => '0'])
      ->addField('is_recursive', 'boolean', ['value' => '0'])
      ->addField('completename', 'text')
      ->createOrDie(true);

   $table_schema->init('glpi_softwares')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('locations_id', 'int', ['value' => '0'])
      ->addIndexedField('users_id_tech', 'int', ['value' => '0'])
      ->addIndexedField('groups_id_tech', 'int', ['value' => '0'])
      ->addIndexedField('is_update', 'boolean', ['value' => '0'])
      ->addIndexedField('softwares_id', 'int', ['value' => '0'])
      ->addIndexedField('manufacturers_id', 'int', ['value' => '0'])
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('is_template', 'boolean', ['value' => '0'])
      ->addField('template_name', 'string')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('users_id', 'int', ['value' => '0'])
      ->addIndexedField('groups_id', 'int', ['value' => '0'])
      ->addField('ticket_tco', 'decimal(20,4)', ['value' => '0.0000'])
      ->addIndexedField('is_helpdesk_visible', 'boolean', ['value' => '1'])
      ->addIndexedField('softwarecategories_id', 'int', ['value' => '0'])
      ->addField('is_valid', 'boolean', ['value' => '1'])
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_softwareversions')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('softwares_id', 'int', ['value' => '0'])
      ->addIndexedField('states_id', 'int', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('operatingsystems_id', 'int', ['value' => '0'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_solutiontemplates')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addField('content', 'text')
      ->addIndexedField('solutiontypes_id', 'int', ['value' => '0'])
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_solutiontypes')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '1'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_ssovariables')
      ->addField('name', 'string')
      ->addField('comment', 'text', ['value' => null, 'nodefault' => true])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_states')
      ->addIndexedField('name', 'string')
      ->addField('entities_id', 'int', ['value' => '0'])
      ->addField('is_recursive', 'boolean', ['value' => '0'])
      ->addField('comment', 'text')
      ->addField('states_id', 'int', ['value' => '0'])
      ->addField('completename', 'text')
      ->addField('level', 'int', ['value' => '0'])
      ->addField('ancestors_cache', 'longtext')
      ->addField('sons_cache', 'longtext')
      ->addIndexedField('is_visible_computer', 'boolean', ['value' => '1'])
      ->addIndexedField('is_visible_monitor', 'boolean', ['value' => '1'])
      ->addIndexedField('is_visible_networkequipment', 'boolean', ['value' => '1'])
      ->addIndexedField('is_visible_peripheral', 'boolean', ['value' => '1'])
      ->addIndexedField('is_visible_phone', 'boolean', ['value' => '1'])
      ->addIndexedField('is_visible_printer', 'boolean', ['value' => '1'])
      ->addIndexedField('is_visible_softwareversion', 'boolean', ['value' => '1'])
      ->addIndexedField('is_visible_softwarelicense', 'boolean', ['value' => '1'])
      ->addIndexedField('is_visible_line', 'boolean', ['value' => '1'])
      ->addIndexedField('is_visible_certificate', 'boolean', ['value' => '1'])
      ->addIndexedField('is_visible_rack', 'boolean', ['value' => '1'])
      ->addIndexedField('is_visible_enclosure', 'boolean', ['value' => '1'])
      ->addIndexedField('is_visible_pdu', 'boolean', ['value' => '1'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->addUniqueKey('unicity', ['states_id', 'name'])
      ->createOrDie(true);

   $table_schema->init('glpi_suppliers')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addIndexedField('suppliertypes_id', 'int', ['value' => '0'])
      ->addField('address', 'text')
      ->addField('postcode', 'string')
      ->addField('town', 'string')
      ->addField('state', 'string')
      ->addField('country', 'string')
      ->addField('website', 'string')
      ->addField('phonenumber', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addField('fax', 'string')
      ->addField('email', 'string')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->addIndexedField('is_active', 'boolean', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_suppliers_tickets')
      ->addField('tickets_id', 'int', ['value' => '0'])
      ->addField('suppliers_id', 'int', ['value' => '0'])
      ->addField('type', 'int', ['value' => '1'])
      ->addField('use_notification', 'boolean', ['value' => '1'])
      ->addField('alternative_email', 'string')
      ->addUniqueKey('unicity', ['tickets_id', 'type', 'suppliers_id'])
      ->addKey('group', ['suppliers_id', 'type'])
      ->createOrDie(true);

   $table_schema->init('glpi_suppliertypes')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_taskcategories')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('taskcategories_id', 'int', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addField('completename', 'text')
      ->addField('comment', 'text')
      ->addField('level', 'int', ['value' => '0'])
      ->addField('ancestors_cache', 'longtext')
      ->addField('sons_cache', 'longtext')
      ->addIndexedField('is_active', 'boolean', ['value' => '1'])
      ->addIndexedField('is_helpdeskvisible', 'boolean', ['value' => '1'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->addIndexedField('knowbaseitemcategories_id', 'int', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_tasktemplates')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addField('content', 'text')
      ->addIndexedField('taskcategories_id', 'int', ['value' => '0'])
      ->addField('actiontime', 'int', ['value' => '0'])
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->addField('state', 'int', ['value' => '0'])
      ->addIndexedField('is_private', 'boolean', ['value' => '0'])
      ->addIndexedField('users_id_tech', 'int', ['value' => '0'])
      ->addIndexedField('groups_id_tech', 'int', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_ticketcosts')
      ->addIndexedField('tickets_id', 'int', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('begin_date', 'date')
      ->addIndexedField('end_date', 'date')
      ->addField('actiontime', 'int', ['value' => '0'])
      ->addField('cost_time', 'decimal(20,4)', ['value' => '0.0000'])
      ->addField('cost_fixed', 'decimal(20,4)', ['value' => '0.0000'])
      ->addField('cost_material', 'decimal(20,4)', ['value' => '0.0000'])
      ->addIndexedField('budgets_id', 'int', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_ticketrecurrents')
      ->addField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('is_active', 'boolean', ['value' => '0'])
      ->addIndexedField('tickettemplates_id', 'int', ['value' => '0'])
      ->addField('begin_date', 'timestamp')
      ->addField('periodicity', 'string')
      ->addField('create_before', 'int', ['value' => '0'])
      ->addIndexedField('next_creation_date', 'timestamp')
      ->addField('calendars_id', 'int', ['value' => '0'])
      ->addField('end_date', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_tickets')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addIndexedField('date', 'timestamp')
      ->addIndexedField('closedate', 'timestamp')
      ->addIndexedField('solvedate', 'timestamp')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('users_id_lastupdater', 'int', ['value' => '0'])
      ->addIndexedField('status', 'int', ['value' => '1'])
      ->addIndexedField('users_id_recipient', 'int', ['value' => '0'])
      ->addField('requesttypes_id', 'int', ['value' => '0'])
      ->addField('content', 'longtext')
      ->addIndexedField('urgency', 'int', ['value' => '1'])
      ->addIndexedField('impact', 'int', ['value' => '1'])
      ->addIndexedField('priority', 'int', ['value' => '1'])
      ->addIndexedField('itilcategories_id', 'int', ['value' => '0'])
      ->addIndexedField('type', 'int', ['value' => '1'])
      ->addIndexedField('global_validation', 'int', ['value' => '1'])
      ->addIndexedField('slas_id_ttr', 'int', ['value' => '0'])
      ->addIndexedField('slas_id_tto', 'int', ['value' => '0'])
      ->addIndexedField('slalevels_id_ttr', 'int', ['value' => '0'])
      ->addIndexedField('time_to_resolve', 'timestamp')
      ->addIndexedField('time_to_own', 'timestamp')
      ->addField('begin_waiting_date', 'timestamp')
      ->addField('sla_waiting_duration', 'int', ['value' => '0'])
      ->addIndexedField('ola_waiting_duration', 'int', ['value' => '0'])
      ->addIndexedField('olas_id_tto', 'int', ['value' => '0'])
      ->addIndexedField('olas_id_ttr', 'int', ['value' => '0'])
      ->addIndexedField('olalevels_id_ttr', 'int', ['value' => '0'])
      ->addIndexedField('internal_time_to_resolve', 'timestamp')
      ->addIndexedField('internal_time_to_own', 'timestamp')
      ->addField('waiting_duration', 'int', ['value' => '0'])
      ->addField('close_delay_stat', 'int', ['value' => '0'])
      ->addField('solve_delay_stat', 'int', ['value' => '0'])
      ->addField('takeintoaccount_delay_stat', 'int', ['value' => '0'])
      ->addField('actiontime', 'int', ['value' => '0'])
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('locations_id', 'int', ['value' => '0'])
      ->addField('validation_percent', 'int', ['value' => '0'])
      ->addIndexedField('date_creation', 'timestamp')
      ->addKey('request_type', ['requesttypes_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_ticketsatisfactions')
      ->addField('tickets_id', 'int', ['value' => '0'])
      ->addField('type', 'int', ['value' => '1'])
      ->addField('date_begin', 'timestamp')
      ->addField('date_answered', 'timestamp')
      ->addField('satisfaction', 'int')
      ->addField('comment', 'text')
      ->addUniqueKey('tickets_id', ['tickets_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_tickets_tickets')
      ->addField('tickets_id_1', 'int', ['value' => '0'])
      ->addField('tickets_id_2', 'int', ['value' => '0'])
      ->addField('link', 'int', ['value' => '1'])
      ->addUniqueKey('unicity', ['tickets_id_1', 'tickets_id_2'])
      ->createOrDie(true);

   $table_schema->init('glpi_tickets_users')
      ->addField('tickets_id', 'int', ['value' => '0'])
      ->addField('users_id', 'int', ['value' => '0'])
      ->addField('type', 'int', ['value' => '1'])
      ->addField('use_notification', 'boolean', ['value' => '1'])
      ->addField('alternative_email', 'string')
      ->addUniqueKey('unicity', ['tickets_id', 'type', 'users_id', 'alternative_email'])
      ->addKey('user', ['users_id', 'type'])
      ->createOrDie(true);

   $table_schema->init('glpi_tickettasks')
      ->addIndexedField('tickets_id', 'int', ['value' => '0'])
      ->addIndexedField('taskcategories_id', 'int', ['value' => '0'])
      ->addIndexedField('date', 'timestamp')
      ->addIndexedField('users_id', 'int', ['value' => '0'])
      ->addIndexedField('users_id_editor', 'int', ['value' => '0'])
      ->addField('content', 'longtext')
      ->addIndexedField('is_private', 'boolean', ['value' => '0'])
      ->addField('actiontime', 'int', ['value' => '0'])
      ->addIndexedField('begin', 'timestamp')
      ->addIndexedField('end', 'timestamp')
      ->addIndexedField('state', 'int', ['value' => '1'])
      ->addIndexedField('users_id_tech', 'int', ['value' => '0'])
      ->addIndexedField('groups_id_tech', 'int', ['value' => '0'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->addIndexedField('tasktemplates_id', 'int', ['value' => '0'])
      ->addField('timeline_position', 'boolean', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_tickettemplatehiddenfields')
      ->addField('tickettemplates_id', 'int', ['value' => '0'])
      ->addField('num', 'int', ['value' => '0'])
      ->addUniqueKey('unicity', ['tickettemplates_id', 'num'])
      ->createOrDie(true);

   $table_schema->init('glpi_tickettemplatemandatoryfields')
      ->addField('tickettemplates_id', 'int', ['value' => '0'])
      ->addField('num', 'int', ['value' => '0'])
      ->addUniqueKey('unicity', ['tickettemplates_id', 'num'])
      ->createOrDie(true);

   $table_schema->init('glpi_tickettemplatepredefinedfields')
      ->addField('tickettemplates_id', 'int', ['value' => '0'])
      ->addField('num', 'int', ['value' => '0'])
      ->addField('value', 'text')
      ->addKey('tickettemplates_id_id_num', ['tickettemplates_id', 'num'])
      ->createOrDie(true);

   $table_schema->init('glpi_tickettemplates')
      ->addIndexedField('name', 'string')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('is_recursive', 'boolean', ['value' => '0'])
      ->addField('comment', 'text')
      ->createOrDie(true);

   $table_schema->init('glpi_ticketvalidations')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('users_id', 'int', ['value' => '0'])
      ->addIndexedField('tickets_id', 'int', ['value' => '0'])
      ->addIndexedField('users_id_validate', 'int', ['value' => '0'])
      ->addField('comment_submission', 'text')
      ->addField('comment_validation', 'text')
      ->addIndexedField('status', 'int', ['value' => '2'])
      ->addIndexedField('submission_date', 'timestamp')
      ->addIndexedField('validation_date', 'timestamp')
      ->addField('timeline_position', 'boolean', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_transfers')
      ->addField('name', 'string')
      ->addField('keep_ticket', 'int', ['value' => '0'])
      ->addField('keep_networklink', 'int', ['value' => '0'])
      ->addField('keep_reservation', 'int', ['value' => '0'])
      ->addField('keep_history', 'int', ['value' => '0'])
      ->addField('keep_device', 'int', ['value' => '0'])
      ->addField('keep_infocom', 'int', ['value' => '0'])
      ->addField('keep_dc_monitor', 'int', ['value' => '0'])
      ->addField('clean_dc_monitor', 'int', ['value' => '0'])
      ->addField('keep_dc_phone', 'int', ['value' => '0'])
      ->addField('clean_dc_phone', 'int', ['value' => '0'])
      ->addField('keep_dc_peripheral', 'int', ['value' => '0'])
      ->addField('clean_dc_peripheral', 'int', ['value' => '0'])
      ->addField('keep_dc_printer', 'int', ['value' => '0'])
      ->addField('clean_dc_printer', 'int', ['value' => '0'])
      ->addField('keep_supplier', 'int', ['value' => '0'])
      ->addField('clean_supplier', 'int', ['value' => '0'])
      ->addField('keep_contact', 'int', ['value' => '0'])
      ->addField('clean_contact', 'int', ['value' => '0'])
      ->addField('keep_contract', 'int', ['value' => '0'])
      ->addField('clean_contract', 'int', ['value' => '0'])
      ->addField('keep_software', 'int', ['value' => '0'])
      ->addField('clean_software', 'int', ['value' => '0'])
      ->addField('keep_document', 'int', ['value' => '0'])
      ->addField('clean_document', 'int', ['value' => '0'])
      ->addField('keep_cartridgeitem', 'int', ['value' => '0'])
      ->addField('clean_cartridgeitem', 'int', ['value' => '0'])
      ->addField('keep_cartridge', 'int', ['value' => '0'])
      ->addField('keep_consumable', 'int', ['value' => '0'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addField('comment', 'text')
      ->addField('keep_disk', 'int', ['value' => '0'])
      ->createOrDie(true);

   $table_schema->init('glpi_usercategories')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_useremails')
      ->addField('users_id', 'int', ['value' => '0'])
      ->addIndexedField('is_default', 'boolean', ['value' => '0'])
      ->addIndexedField('is_dynamic', 'boolean', ['value' => '0'])
      ->addIndexedField('email', 'string')
      ->addUniqueKey('unicity', ['users_id', 'email'])
      ->createOrDie(true);

   $table_schema->init('glpi_users')
      ->addField('name', 'string')
      ->addField('password', 'string')
      ->addField('phone', 'string')
      ->addField('phone2', 'string')
      ->addField('mobile', 'string')
      ->addIndexedField('realname', 'string')
      ->addIndexedField('firstname', 'string')
      ->addIndexedField('locations_id', 'int', ['value' => '0'])
      ->addField('language', 'char(10)', ['value' => null, 'nodefault' => false, 'comment' => 'see define.php CFG_GLPI[language] array'])
      ->addField('use_mode', 'int', ['value' => '0'])
      ->addField('list_limit', 'int')
      ->addIndexedField('is_active', 'boolean', ['value' => '1'])
      ->addField('comment', 'text')
      ->addField('auths_id', 'int', ['value' => '0'])
      ->addField('authtype', 'int', ['value' => '0'])
      ->addField('last_login', 'timestamp')
      ->addIndexedField('date_mod', 'timestamp')
      ->addField('date_sync', 'timestamp')
      ->addIndexedField('is_deleted', 'boolean', ['value' => '0'])
      ->addIndexedField('profiles_id', 'int', ['value' => '0'])
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addIndexedField('usertitles_id', 'int', ['value' => '0'])
      ->addIndexedField('usercategories_id', 'int', ['value' => '0'])
      ->addField('date_format', 'int')
      ->addField('number_format', 'int')
      ->addField('names_format', 'int')
      ->addField('csv_delimiter', 'char(1)')
      ->addField('is_ids_visible', 'boolean')
      ->addField('use_flat_dropdowntree', 'boolean')
      ->addField('show_jobs_at_login', 'boolean')
      ->addField('priority_1', 'char(20)')
      ->addField('priority_2', 'char(20)')
      ->addField('priority_3', 'char(20)')
      ->addField('priority_4', 'char(20)')
      ->addField('priority_5', 'char(20)')
      ->addField('priority_6', 'char(20)')
      ->addField('followup_private', 'boolean')
      ->addField('task_private', 'boolean')
      ->addField('default_requesttypes_id', 'int')
      ->addField('password_forget_token', 'char(40)')
      ->addField('password_forget_token_date', 'timestamp')
      ->addField('user_dn', 'text')
      ->addField('registration_number', 'string')
      ->addField('show_count_on_tabs', 'boolean')
      ->addField('refresh_ticket_list', 'int')
      ->addField('set_default_tech', 'boolean')
      ->addField('personal_token', 'string')
      ->addField('personal_token_date', 'timestamp')
      ->addField('api_token', 'string')
      ->addField('api_token_date', 'timestamp')
      ->addField('cookie_token', 'string')
      ->addField('cookie_token_date', 'timestamp')
      ->addField('display_count_on_home', 'int')
      ->addField('notification_to_myself', 'boolean')
      ->addField('duedateok_color', 'string')
      ->addField('duedatewarning_color', 'string')
      ->addField('duedatecritical_color', 'string')
      ->addField('duedatewarning_less', 'int')
      ->addField('duedatecritical_less', 'int')
      ->addField('duedatewarning_unit', 'string')
      ->addField('duedatecritical_unit', 'string')
      ->addField('display_options', 'text')
      ->addIndexedField('is_deleted_ldap', 'boolean', ['value' => '0'])
      ->addField('pdffont', 'string')
      ->addField('picture', 'string')
      ->addIndexedField('begin_date', 'timestamp')
      ->addIndexedField('end_date', 'timestamp')
      ->addField('keep_devices_when_purging_item', 'boolean')
      ->addField('privatebookmarkorder', 'longtext')
      ->addField('backcreated', 'boolean')
      ->addField('task_state', 'int')
      ->addField('layout', 'char(20)')
      ->addField('palette', 'char(20)')
      ->addField('set_default_requester', 'boolean')
      ->addField('lock_autolock_mode', 'boolean')
      ->addField('lock_directunlock_notification', 'boolean')
      ->addIndexedField('date_creation', 'timestamp')
      ->addField('highcontrast_css', 'boolean', ['value' => '0'])
      ->addField('plannings', 'text')
      ->addIndexedField('sync_field', 'string')
      ->addIndexedField('groups_id', 'int', ['value' => '0'])
      ->addIndexedField('users_id_supervisor', 'int', ['value' => '0'])
      ->addField('timezone', 'varchar(50)')
      ->addUniqueKey('unicityloginauth', ['name', 'authtype', 'auths_id'])
      ->addKey('authitem', ['authtype', 'auths_id'])
      ->createOrDie(true);

   $table_schema->init('glpi_usertitles')
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_virtualmachinestates')
      ->addField('name', 'string', ['value' => ''])
      ->addField('comment', 'text', ['value' => null, 'nodefault' => true])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_virtualmachinesystems')
      ->addField('name', 'string', ['value' => ''])
      ->addField('comment', 'text', ['value' => null, 'nodefault' => true])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_virtualmachinetypes')
      ->addField('name', 'string', ['value' => ''])
      ->addField('comment', 'text', ['value' => null, 'nodefault' => true])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_vlans')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addField('comment', 'text')
      ->addIndexedField('tag', 'int', ['value' => '0'])
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);

   $table_schema->init('glpi_wifinetworks')
      ->addIndexedField('entities_id', 'int', ['value' => '0'])
      ->addField('is_recursive', 'boolean', ['value' => '0'])
      ->addIndexedField('name', 'string')
      ->addIndexedField('essid', 'string')
      ->addField('mode', 'string', ['value' => null, 'nodefault' => false, 'comment' => 'ad-hoc, access_point'])
      ->addField('comment', 'text')
      ->addIndexedField('date_mod', 'timestamp')
      ->addIndexedField('date_creation', 'timestamp')
      ->createOrDie(true);
}

/**
 * Inserts all default data such as configs into the database
 * @since 10.0.0
 */
function insertDefaultData() {
   global $DB;

   $DB->insertOrDie('glpi_apiclients', [
      'id'              => 1,
      'entities_id'     => 0,
      'is_recursive'    => 1,
      'name'            => 'full access from localhost',
      'is_active'       => 1,
      'ipv4_range_start'   => new QueryExpression("INET_ATON('127.0.0.1')"),
      'ipv4_range_end'     => new QueryExpression("INET_ATON('127.0.0.1')"),
      'ipv6'               => '::1',
   ]);

   $DB->insertOrDie('glpi_blacklists', [
      'id'     => 1,
      'type'   => 1,
      'name'   => 'empty IP',
      'value'  => ''
   ]);
   $DB->insertOrDie('glpi_blacklists', [
      'id'     => 2,
      'type'   => 1,
      'name'   => 'localhost',
      'value'  => '127.0.0.1'
   ]);
   $DB->insertOrDie('glpi_blacklists', [
      'id'     => 3,
      'type'   => 1,
      'name'   => 'zero IP',
      'value'  => '0.0.0.0'
   ]);
   $DB->insertOrDie('glpi_blacklists', [
      'id'     => 4,
      'type'   => 2,
      'name'   => 'empty MAC',
      'value'  => ''
   ]);

   $DB->insertOrDie('glpi_calendars', [
      'id'           => 1,
      'name'         => 'Default',
      'is_recursive' => 1,
      'comment'      => 'Default calendar',
      'cache_duration'  => '[0,43200,43200,43200,43200,43200,0]'
   ]);

   for ($i = 1; $i < 6; $i++) {
      $DB->insertOrDie('glpi_calendarsegments', [
         'id'           => $i,
         'calendars_id' => 1,
         'day'          => $i,
         'begin'        => '08:00:00',
         'end'          => '20:00:00'
      ]);
   }

   $DB->insertBulkOrDie('glpi_configs', ['context', 'name', 'value'], [
      ['core', 'version', GLPI_VERSION],
      ['core', 'show_jobs_at_login', 0],
      ['core', 'cut', 250],
      ['core', 'list_limit', 15],
      ['core', 'list_limit_max', 50],
      ['core', 'url_maxlength', 30],
      ['core', 'event_loglevel', 5],
      ['core', 'notifications_mailing', 0],
      ['core', 'admin_email', 'admsys@localhost'],
      ['core', 'admin_email_name', ''],
      ['core', 'admin_reply', ''],
      ['core', 'admin_reply_name', ''],
      ['core', 'mailing_signature', 'SIGNATURE'],
      ['core', 'use_anonymous_helpdesk', 0],
      ['core', 'use_anonymous_followups', 0],
      ['core', 'language', isset($_SESSION['glpilanguage']) ? $_SESSION['glpilanguage'] : 'en_GB'],
      ['core', 'priority_1', '#fff2f2'],
      ['core', 'priority_2', '#ffe0e0'],
      ['core', 'priority_3', '#ffcece'],
      ['core', 'priority_4', '#ffbfbf'],
      ['core', 'priority_5', '#ffadad'],
      ['core', 'priority_6', '#ff5555'],
      ['core', 'date_tax', '2005-12-31'],
      ['core', 'cas_host', ''],
      ['core', 'cas_port', 443],
      ['core', 'cas_uri', ''],
      ['core', 'cas_logout', ''],
      ['core', 'existing_auth_server_field_clean_domain', 0],
      ['core', 'planning_begin', '08:00:00'],
      ['core', 'planning_end', '20:00:00'],
      ['core', 'utf8_conv', 1],
      ['core', 'use_public_faq', 0],
      ['core', 'url_base', 'http://localhost/glpi/'],
      ['core', 'show_link_in_mail', 0],
      ['core', 'text_login', ''],
      ['core', 'founded_new_version', ''],
      ['core', 'dropdown_max', 100],
      ['core', 'ajax_wildcard', '*'],
      ['core', 'ajax_limit_count', 10],
      ['core', 'use_ajax_autocompletion', 1],
      ['core', 'is_users_auto_add', 1],
      ['core', 'date_format', 0],
      ['core', 'number_format', 0],
      ['core', 'csv_delimiter', ';'],
      ['core', 'is_ids_visible', 0],
      ['core', 'smtp_mode', 0],
      ['core', 'smtp_host', ''],
      ['core', 'smtp_port', 25],
      ['core', 'smtp_username', ''],
      ['core', 'proxy_name', ''],
      ['core', 'proxy_port', 8080],
      ['core', 'proxy_user', ''],
      ['core', 'add_followup_on_update_ticket', 1],
      ['core', 'keep_tickets_on_delete', 0],
      ['core', 'time_step', 5],
      ['core', 'decimal_number', 2],
      ['core', 'helpdesk_doc_url', ''],
      ['core', 'central_doc_url', ''],
      ['core', 'documentcategories_id_forticket', 0],
      ['core', 'monitors_management_restrict', 2],
      ['core', 'phones_management_restrict', 2],
      ['core', 'peripherals_management_restrict', 2],
      ['core', 'printers_management_restrict', 2],
      ['core', 'use_log_in_files', 1],
      ['core', 'time_offset', 0],
      ['core', 'is_contact_autoupdate', 1],
      ['core', 'is_user_autoupdate', 1],
      ['core', 'is_group_autoupdate', 1],
      ['core', 'is_location_autoupdate', 1],
      ['core', 'state_autoupdate_mode', 0],
      ['core', 'is_contact_autoclean', 0],
      ['core', 'is_user_autoclean', 0],
      ['core', 'is_group_autoclean', 0],
      ['core', 'is_location_autoclean', 0],
      ['core', 'state_autoclean_mode', 0],
      ['core', 'use_flat_dropdowntree', 0],
      ['core', 'use_autoname_by_entity', 1],
      ['core', 'softwarecategories_id_ondelete', 1],
      ['core', 'x509_email_field', ''],
      ['core', 'x509_cn_restrict', ''],
      ['core', 'x509_o_restrict', ''],
      ['core', 'x509_ou_restrict', ''],
      ['core', 'default_mailcollector_filesize_max', 2097152],
      ['core', 'followup_private', 0],
      ['core', 'task_private', 0],
      ['core', 'default_software_helpdesk_visible', 1],
      ['core', 'names_format', 0],
      ['core', 'default_requesttypes_id', 1],
      ['core', 'use_noright_users_add', 1],
      ['core', 'cron_limit', 5],
      ['core', 'priority_matrix', '{\"1\":{\"1\":1,\"2\":1,\"3\":2,\"4\":2,\"5\":2},\"2\":{\"1\":1,\"2\":2,\"3\":2,\"4\":3,\"5\":3},\"3\":{\"1\":2,\"2\":2,\"3\":3,\"4\":4,\"5\":4},\"4\":{\"1\":2,\"2\":3,\"3\":4,\"4\":4,\"5\":5},\"5\":{\"1\":2,\"2\":3,\"3\":4,\"4\":5,\"5\":5}}'],
      ['core', 'urgency_mask', 62],
      ['core', 'impact_mask', 62],
      ['core', 'user_deleted_ldap', 0],
      ['core', 'auto_create_infocoms', 0],
      ['core', 'use_slave_for_search', 0],
      ['core', 'proxy_passwd', ''],
      ['core', 'smtp_passwd', ''],
      ['core', 'transfers_id_auto', 0],
      ['core', 'show_count_on_tabs', 1],
      ['core', 'refresh_ticket_list', 0],
      ['core', 'set_default_tech', 1],
      ['core', 'allow_search_view', 2],
      ['core', 'allow_search_all', 0],
      ['core', 'allow_search_global', 0],
      ['core', 'display_count_on_home', 5],
      ['core', 'use_password_security', 0],
      ['core', 'password_min_length', 8],
      ['core', 'password_need_number', 1],
      ['core', 'password_need_letter', 1],
      ['core', 'password_need_caps', 1],
      ['core', 'password_need_symbol', 1],
      ['core', 'use_check_pref', 0],
      ['core', 'notification_to_myself', 1],
      ['core', 'duedateok_color', '#06ff00'],
      ['core', 'duedatewarning_color', '#ffb800'],
      ['core', 'duedatecritical_color', '#ff0000'],
      ['core', 'duedatewarning_less', 20],
      ['core', 'duedatecritical_less', 5],
      ['core', 'duedatewarning_unit', '%'],
      ['core', 'duedatecritical_unit', '%'],
      ['core', 'realname_ssofield', ''],
      ['core', 'firstname_ssofield', ''],
      ['core', 'email1_ssofield', ''],
      ['core', 'email2_ssofield', ''],
      ['core', 'email3_ssofield', ''],
      ['core', 'email4_ssofield', ''],
      ['core', 'phone_ssofield', ''],
      ['core', 'phone2_ssofield', ''],
      ['core', 'mobile_ssofield', ''],
      ['core', 'comment_ssofield', ''],
      ['core', 'title_ssofield', ''],
      ['core', 'category_ssofield', ''],
      ['core', 'language_ssofield', ''],
      ['core', 'entity_ssofield', ''],
      ['core', 'registration_number_ssofield', ''],
      ['core', 'ssovariables_id', 0],
      ['core', 'translate_kb', 0],
      ['core', 'translate_dropdowns', 0],
      ['core', 'pdffont', 'helvetica'],
      ['core', 'keep_devices_when_purging_item', 0],
      ['core', 'maintenance_mode', 0],
      ['core', 'maintenance_text', ''],
      ['core', 'attach_ticket_documents_to_mail', 0],
      ['core', 'backcreated', 0],
      ['core', 'task_state', 1],
      ['core', 'layout', 'lefttab'],
      ['core', 'palette', 'auror'],
      ['core', 'lock_use_lock_item', 0],
      ['core', 'lock_autolock_mode', 1],
      ['core', 'lock_directunlock_notification', 0],
      ['core', 'lock_item_list', '[]'],
      ['core', 'lock_lockprofile_id', 8],
      ['core', 'set_default_requester', 1],
      ['core', 'highcontrast_css', 0],
      ['core', 'smtp_check_certificate', 1],
      ['core', 'enable_api', 0],
      ['core', 'enable_api_login_credentials', 0],
      ['core', 'enable_api_login_external_token', 1],
      ['core', 'url_base_api', 'http://localhost/glpi/api'],
      ['core', 'login_remember_time', 604800],
      ['core', 'login_remember_default', 1],
      ['core', 'use_notifications', 0],
      ['core', 'notifications_ajax', 0],
      ['core', 'notifications_ajax_check_interval', 5],
      ['core', 'notifications_ajax_sound', null],
      ['core', 'notifications_ajax_icon_url', '/pics/glpi.png'],
      ['core', 'dbversion', GLPI_SCHEMA_VERSION],
      ['core', 'smtp_max_retries', 5],
      ['core', 'smtp_sender', null],
      ['core', 'from_email', null],
      ['core', 'from_email_name', null],
      ['core', 'instance_uuid', null],
      ['core', 'registration_uuid', null],
      ['core', 'smtp_retry_time', 5],
      ['core', 'purge_addrelation', 0],
      ['core', 'purge_deleterelation', 0],
      ['core', 'purge_createitem', 0],
      ['core', 'purge_deleteitem', 0],
      ['core', 'purge_restoreitem', 0],
      ['core', 'purge_updateitem', 0],
      ['core', 'purge_computer_software_install', 0],
      ['core', 'purge_software_computer_install', 0],
      ['core', 'purge_software_version_install', 0],
      ['core', 'purge_infocom_creation', 0],
      ['core', 'purge_profile_user', 0],
      ['core', 'purge_group_user', 0],
      ['core', 'purge_adddevice', 0],
      ['core', 'purge_updatedevice', 0],
      ['core', 'purge_deletedevice', 0],
      ['core', 'purge_connectdevice', 0],
      ['core', 'purge_disconnectdevice', 0],
      ['core', 'purge_userdeletedfromldap', 0],
      ['core', 'purge_comments', 0],
      ['core', 'purge_datemod', 0],
      ['core', 'purge_all', 0],
      ['core', 'purge_user_auth_changes', 0],
      ['core', 'purge_plugins', 0],
      ['core', 'display_login_source', 1]
   ]);

   $DB->insertBulkOrDie('glpi_crontasks', ['itemtype', 'name', 'frequency', 'param', 'state', 'mode',
         'allowmode', 'hourmin', 'hourmax', 'logs_lifetime'], [
      ['CartridgeItem','cartridge','86400','10','0','1','3','0','24','30'],
      ['ConsumableItem','consumable','86400','10','0','1','3','0','24','30'],
      ['SoftwareLicense','software','86400',null,'0','1','3','0','24','30'],
      ['Contract','contract','86400',null,'1','1','3','0','24','30'],
      ['InfoCom','infocom','86400',null,'1','1','3','0','24','30'],
      ['CronTask','logs','86400','30','0','1','3','0','24','30'],
      ['MailCollector','mailgate','600','10','1','1','3','0','24','30'],
      ['DBconnection','checkdbreplicate','300',null,'0','1','3','0','24','30'],
      ['CronTask','checkupdate','604800',null,'0','1','3','0','24','30'],
      ['CronTask','session','86400',null,'1','1','3','0','24','30'],
      ['CronTask','graph','3600',null,'1','1','3','0','24','30'],
      ['ReservationItem','reservation','3600',null,'1','1','3','0','24','30'],
      ['Ticket','closeticket','43200',null,'1','1','3','0','24','30'],
      ['Ticket','alertnotclosed','43200',null,'1','1','3','0','24','30'],
      ['SlaLevel_Ticket','slaticket','300',null,'1','1','3','0','24','30'],
      ['Ticket','createinquest','86400',null,'1','1','3','0','24','30'],
      ['Crontask','watcher','86400',null,'1','1','3','0','24','30'],
      ['TicketRecurrent','ticketrecurrent','3600',null,'1','1','3','0','24','30'],
      ['PlanningRecall','planningrecall','300',null,'1','1','3','0','24','30'],
      ['QueuedNotification','queuednotification','60','50','1','1','3','0','24','30'],
      ['QueuedNotification','queuednotificationclean','86400','30','1','1','3','0','24','30'],
      ['Crontask','temp','3600',null,'1','1','3','0','24','30'],
      ['MailCollector','mailgateerror','86400',null,'1','1','3','0','24','30'],
      ['Crontask','circularlogs','86400','4','0','1','3','0','24','30'],
      ['ObjectLock','unlockobject','86400','4','0','1','3','0','24','30'],
      ['SavedSearch','countAll','604800',null,'0','1','3','0','24','10'],
      ['SavedSearch_Alert','savedsearchesalerts','86400',null,'0','1','3','0','24','10'],
      ['Certificate','certificate','86400',null,'0','1','3','0','24','10'],
      ['OlaLevel_Ticket','olaticket','300',null,'1','1','3','0','24','30']
   ]);
   

   $DB->insertOrDie('glpi_devicememorytypes', [
      'id'        => 1,
      'name'   => 'EDO'
   ]);
   $DB->insertOrDie('glpi_devicememorytypes', [
      'id'        => 2,
      'name'   => 'DDR'
   ]);
   $DB->insertOrDie('glpi_devicememorytypes', [
      'id'        => 3,
      'name'   => 'SDRAM'
   ]);
   $DB->insertOrDie('glpi_devicememorytypes', [
      'id'        => 4,
      'name'   => 'SDRAM-2'
   ]);

   $DB->insertOrDie('glpi_devicesimcardtypes', [
      'id'        => 1,
      'name'   => 'Full SIM'
   ]);
   $DB->insertOrDie('glpi_devicesimcardtypes', [
      'id'        => 2,
      'name'   => 'Mini SIM'
   ]);
   $DB->insertOrDie('glpi_devicesimcardtypes', [
      'id'        => 3,
      'name'   => 'Micro SIM'
   ]);
   $DB->insertOrDie('glpi_devicesimcardtypes', [
      'id'        => 4,
      'name'   => 'Nano SIM'
   ]);

   $DB->insertBulkOrDie('glpi_displaypreferences', ['itemtype', 'num', 'rank', 'users_id', 'is_main'], [
      ['Computer','4','4','0','1'],
      ['Computer','45','6','0','1'],
      ['Computer','40','5','0','1'],
      ['Computer','5','3','0','1'],
      ['Computer','23','2','0','1'],
      ['DocumentType','3','1','0','1'],
      ['Monitor','31','1','0','1'],
      ['Monitor','23','2','0','1'],
      ['Monitor','3','3','0','1'],
      ['Monitor','4','4','0','1'],
      ['Printer','31','1','0','1'],
      ['NetworkEquipment','31','1','0','1'],
      ['NetworkEquipment','23','2','0','1'],
      ['Printer','23','2','0','1'],
      ['Printer','3','3','0','1'],
      ['Software','4','3','0','1'],
      ['Software','5','2','0','1'],
      ['Software','23','1','0','1'],
      ['CartridgeItem','4','2','0','1'],
      ['CartridgeItem','34','1','0','1'],
      ['Peripheral','3','3','0','1'],
      ['Peripheral','23','2','0','1'],
      ['Peripheral','31','1','0','1'],
      ['Computer','31','1','0','1'],
      ['Computer','3','7','0','1'],
      ['Computer','19','8','0','1'],
      ['Computer','17','9','0','1'],
      ['NetworkEquipment','3','3','0','1'],
      ['NetworkEquipment','4','4','0','1'],
      ['NetworkEquipment','11','6','0','1'],
      ['NetworkEquipment','19','7','0','1'],
      ['Printer','4','4','0','1'],
      ['Printer','19','6','0','1'],
      ['Monitor','19','6','0','1'],
      ['Monitor','7','7','0','1'],
      ['Peripheral','4','4','0','1'],
      ['Peripheral','19','6','0','1'],
      ['Peripheral','7','7','0','1'],
      ['Contact','3','1','0','1'],
      ['Contact','4','2','0','1'],
      ['Contact','5','3','0','1'],
      ['Contact','6','4','0','1'],
      ['Contact','9','5','0','1'],
      ['Supplier','9','1','0','1'],
      ['Supplier','3','2','0','1'],
      ['Supplier','4','3','0','1'],
      ['Supplier','5','4','0','1'],
      ['Supplier','10','5','0','1'],
      ['Supplier','6','6','0','1'],
      ['Contract','4','1','0','1'],
      ['Contract','3','2','0','1'],
      ['Contract','5','3','0','1'],
      ['Contract','6','4','0','1'],
      ['Contract','7','5','0','1'],
      ['Contract','11','6','0','1'],
      ['CartridgeItem','23','3','0','1'],
      ['CartridgeItem','3','4','0','1'],
      ['DocumentType','6','2','0','1'],
      ['DocumentType','4','3','0','1'],
      ['DocumentType','5','4','0','1'],
      ['Document','3','1','0','1'],
      ['Document','4','2','0','1'],
      ['Document','7','3','0','1'],
      ['Document','5','4','0','1'],
      ['Document','16','5','0','1'],
      ['User','34','1','0','1'],
      ['User','5','3','0','1'],
      ['User','6','4','0','1'],
      ['User','3','5','0','1'],
      ['ConsumableItem','34','1','0','1'],
      ['ConsumableItem','4','2','0','1'],
      ['ConsumableItem','23','3','0','1'],
      ['ConsumableItem','3','4','0','1'],
      ['NetworkEquipment','40','5','0','1'],
      ['Printer','40','5','0','1'],
      ['Monitor','40','5','0','1'],
      ['Peripheral','40','5','0','1'],
      ['User','8','6','0','1'],
      ['Phone','31','1','0','1'],
      ['Phone','23','2','0','1'],
      ['Phone','3','3','0','1'],
      ['Phone','4','4','0','1'],
      ['Phone','40','5','0','1'],
      ['Phone','19','6','0','1'],
      ['Phone','7','7','0','1'],
      ['Group','16','1','0','1'],
      ['AllAssets','31','1','0','1'],
      ['ReservationItem','4','1','0','1'],
      ['ReservationItem','3','2','0','1'],
      ['Budget','3','2','0','1'],
      ['Software','72','4','0','1'],
      ['Software','163','5','0','1'],
      ['Budget','5','1','0','1'],
      ['Budget','4','3','0','1'],
      ['Budget','19','4','0','1'],
      ['Crontask','8','1','0','1'],
      ['Crontask','3','2','0','1'],
      ['Crontask','4','3','0','1'],
      ['Crontask','7','4','0','1'],
      ['RequestType','14','1','0','1'],
      ['RequestType','15','2','0','1'],
      ['NotificationTemplate','4','1','0','1'],
      ['NotificationTemplate','16','2','0','1'],
      ['Notification','5','1','0','1'],
      ['Notification','6','2','0','1'],
      ['Notification','2','3','0','1'],
      ['Notification','4','4','0','1'],
      ['Notification','80','5','0','1'],
      ['Notification','86','6','0','1'],
      ['MailCollector','2','1','0','1'],
      ['MailCollector','19','2','0','1'],
      ['AuthLDAP','3','1','0','1'],
      ['AuthLDAP','19','2','0','1'],
      ['AuthMail','3','1','0','1'],
      ['AuthMail','19','2','0','1'],
      ['IPNetwork','18','1','0','1'],
      ['WifiNetwork','10','1','0','1'],
      ['Profile','2','1','0','1'],
      ['Profile','3','2','0','1'],
      ['Profile','19','3','0','1'],
      ['Transfer','19','1','0','1'],
      ['TicketValidation','3','1','0','1'],
      ['TicketValidation','2','2','0','1'],
      ['TicketValidation','8','3','0','1'],
      ['TicketValidation','4','4','0','1'],
      ['TicketValidation','9','5','0','1'],
      ['TicketValidation','7','6','0','1'],
      ['NotImportedEmail','2','1','0','1'],
      ['NotImportedEmail','5','2','0','1'],
      ['NotImportedEmail','4','3','0','1'],
      ['NotImportedEmail','6','4','0','1'],
      ['NotImportedEmail','16','5','0','1'],
      ['NotImportedEmail','19','6','0','1'],
      ['RuleRightParameter','11','1','0','1'],
      ['Ticket','12','1','0','1'],
      ['Ticket','19','2','0','1'],
      ['Ticket','15','3','0','1'],
      ['Ticket','3','4','0','1'],
      ['Ticket','4','5','0','1'],
      ['Ticket','5','6','0','1'],
      ['Ticket','7','7','0','1'],
      ['Calendar','19','1','0','1'],
      ['Holiday','11','1','0','1'],
      ['Holiday','12','2','0','1'],
      ['Holiday','13','3','0','1'],
      ['SLA','4','1','0','1'],
      ['Ticket','18','8','0','1'],
      ['AuthLdap','30','3','0','1'],
      ['AuthMail','6','3','0','1'],
      ['FQDN','11','1','0','1'],
      ['FieldUnicity','1','1','0','1'],
      ['FieldUnicity','80','2','0','1'],
      ['FieldUnicity','4','3','0','1'],
      ['FieldUnicity','3','4','0','1'],
      ['FieldUnicity','86','5','0','1'],
      ['FieldUnicity','30','6','0','1'],
      ['Problem','21','1','0','1'],
      ['Problem','12','2','0','1'],
      ['Problem','19','3','0','1'],
      ['Problem','15','4','0','1'],
      ['Problem','3','5','0','1'],
      ['Problem','7','6','0','1'],
      ['Problem','18','7','0','1'],
      ['Vlan','11','1','0','1'],
      ['TicketRecurrent','11','1','0','1'],
      ['TicketRecurrent','12','2','0','1'],
      ['TicketRecurrent','13','3','0','1'],
      ['TicketRecurrent','15','4','0','1'],
      ['TicketRecurrent','14','5','0','1'],
      ['Reminder','2','1','0','1'],
      ['Reminder','3','2','0','1'],
      ['Reminder','4','3','0','1'],
      ['Reminder','5','4','0','1'],
      ['Reminder','6','5','0','1'],
      ['Reminder','7','6','0','1'],
      ['IPNetwork','10','2','0','1'],
      ['IPNetwork','11','3','0','1'],
      ['IPNetwork','12','4','0','1'],
      ['IPNetwork','17','5','0','1'],
      ['NetworkName','12','1','0','1'],
      ['NetworkName','13','2','0','1'],
      ['RSSFeed','2','1','0','1'],
      ['RSSFeed','4','2','0','1'],
      ['RSSFeed','5','3','0','1'],
      ['RSSFeed','19','4','0','1'],
      ['RSSFeed','6','5','0','1'],
      ['RSSFeed','7','6','0','1'],
      ['Blacklist','12','1','0','1'],
      ['Blacklist','11','2','0','1'],
      ['ReservationItem','5','3','0','1'],
      ['QueueMail','16','1','0','1'],
      ['QueueMail','7','2','0','1'],
      ['QueueMail','20','3','0','1'],
      ['QueueMail','21','4','0','1'],
      ['QueueMail','22','5','0','1'],
      ['QueueMail','15','6','0','1'],
      ['Change','12','1','0','1'],
      ['Change','19','2','0','1'],
      ['Change','15','3','0','1'],
      ['Change','7','4','0','1'],
      ['Change','18','5','0','1'],
      ['Project','3','1','0','1'],
      ['Project','4','2','0','1'],
      ['Project','12','3','0','1'],
      ['Project','5','4','0','1'],
      ['Project','15','5','0','1'],
      ['Project','21','6','0','1'],
      ['ProjectState','12','1','0','1'],
      ['ProjectState','11','2','0','1'],
      ['ProjectTask','2','1','0','1'],
      ['ProjectTask','12','2','0','1'],
      ['ProjectTask','14','3','0','1'],
      ['ProjectTask','5','4','0','1'],
      ['ProjectTask','7','5','0','1'],
      ['ProjectTask','8','6','0','1'],
      ['ProjectTask','13','7','0','1'],
      ['CartridgeItem','9','5','0','1'],
      ['ConsumableItem','9','5','0','1'],
      ['ReservationItem','9','4','0','1'],
      ['SoftwareLicense','1','1','0','1'],
      ['SoftwareLicense','3','2','0','1'],
      ['SoftwareLicense','10','3','0','1'],
      ['SoftwareLicense','162','4','0','1'],
      ['SoftwareLicense','5','5','0','1'],
      ['SavedSearch','8','1','0','1'],
      ['SavedSearch','9','1','0','1'],
      ['SavedSearch','3','1','0','1'],
      ['SavedSearch','10','1','0','1'],
      ['SavedSearch','11','1','0','1'],
      ['Plugin','2','1','0','1'],
      ['Plugin','3','2','0','1'],
      ['Plugin','4','3','0','1'],
      ['Plugin','5','4','0','1'],
      ['Plugin','6','5','0','1'],
      ['Plugin','7','6','0','1'],
      ['Plugin','8','7','0','1'],
      ['Contract','3','1','0','0'],
      ['Contract','4','2','0','0'],
      ['Contract','29','3','0','0'],
      ['Contract','5','4','0','0'],
      ['Item_Disk','2','1','0','0'],
      ['Item_Disk','3','2','0','0'],
      ['Item_Disk','4','3','0','0'],
      ['Item_Disk','5','4','0','0'],
      ['Item_Disk','6','5','0','0'],
      ['Item_Disk','7','6','0','0'],
      ['Certificate','7','1','0','0'],
      ['Certificate','4','2','0','0'],
      ['Certificate','8','3','0','0'],
      ['Certificate','121','4','0','0'],
      ['Certificate','10','5','0','0'],
      ['Certificate','31','6','0','0'],
      ['Notepad','200','1','0','0'],
      ['Notepad','201','2','0','0'],
      ['Notepad','202','3','0','0'],
      ['Notepad','203','4','0','0'],
      ['Notepad','204','5','0','0'],
      ['SoftwareVersion','3','1','0','0'],
      ['SoftwareVersion','31','1','0','0'],
      ['SoftwareVersion','2','1','0','0'],
      ['SoftwareVersion','122','1','0','0'],
      ['SoftwareVersion','123','1','0','0'],
      ['SoftwareVersion','124','1','0','0']
   ]);

   $DB->insertBulkOrDie('glpi_documenttypes', ['name', 'ext', 'icon'], [
      ['JPEG','jpg','jpg-dist.png'],
      ['PNG','png','png-dist.png'],
      ['GIF','gif','gif-dist.png'],
      ['BMP','bmp','bmp-dist.png'],
      ['Photoshop','psd','psd-dist.png'],
      ['TIFF','tif','tif-dist.png'],
      ['AIFF','aiff','aiff-dist.png'],
      ['Windows Media','asf','asf-dist.png'],
      ['Windows Media','avi','avi-dist.png'],
      ['C source','c','c-dist.png'],
      ['RealAudio','rm','rm-dist.png'],
      ['Midi','mid','mid-dist.png'],
      ['QuickTime','mov','mov-dist.png'],
      ['MP3','mp3','mp3-dist.png'],
      ['MPEG','mpg','mpg-dist.png'],
      ['Ogg Vorbis','ogg','ogg-dist.png'],
      ['QuickTime','qt','qt-dist.png'],
      ['BZip','bz2','bz2-dist.png'],
      ['RealAudio','ra','ra-dist.png'],
      ['RealAudio','ram','ram-dist.png'],
      ['Word','doc','doc-dist.png'],
      ['DjVu','djvu',''],
      ['MNG','mng',''],
      ['PostScript','eps','ps-dist.png'],
      ['GZ','gz','gz-dist.png'],
      ['WAV','wav','wav-dist.png'],
      ['HTML','html','html-dist.png'],
      ['Flash','swf','swf-dist.png'],
      ['PDF','pdf','pdf-dist.png'],
      ['PowerPoint','ppt','ppt-dist.png'],
      ['PostScript','ps','ps-dist.png'],
      ['Windows Media','wmv','wmv-dist.png'],
      ['RTF','rtf','rtf-dist.png'],
      ['StarOffice','sdd','sdd-dist.png'],
      ['StarOffice','sdw','sdw-dist.png'],
      ['Stuffit','sit','sit-dist.png'],
      ['Adobe Illustrator','ai','ai-dist.png'],
      ['OpenOffice Impress','sxi','sxi-dist.png'],
      ['OpenOffice','sxw','sxw-dist.png'],
      ['DVI','dvi','dvi-dist.png'],
      ['TGZ','tgz','tgz-dist.png'],
      ['texte','txt','txt-dist.png'],
      ['RedHat/Mandrake/SuSE','rpm','rpm-dist.png'],
      ['Excel','xls','xls-dist.png'],
      ['XML','xml','xml-dist.png'],
      ['Zip','zip','zip-dist.png'],
      ['Debian','deb','deb-dist.png'],
      ['C header','h','h-dist.png'],
      ['Pascal','pas','pas-dist.png'],
      ['OpenOffice Calc','sxc','sxc-dist.png'],
      ['LaTeX','tex','tex-dist.png'],
      ['GIMP multi-layer','xcf','xcf-dist.png'],
      ['JPEG','jpeg','jpg-dist.png'],
      ['Oasis Open Office Writer','odt','odt-dist.png'],
      ['Oasis Open Office Calc','ods','ods-dist.png'],
      ['Oasis Open Office Impress','odp','odp-dist.png'],
      ['Oasis Open Office Impress Template','otp','odp-dist.png'],
      ['Oasis Open Office Writer Template','ott','odt-dist.png'],
      ['Oasis Open Office Calc Template','ots','ods-dist.png'],
      ['Oasis Open Office Math','odf','odf-dist.png'],
      ['Oasis Open Office Draw','odg','odg-dist.png'],
      ['Oasis Open Office Draw Template','otg','odg-dist.png'],
      ['Oasis Open Office Base','odb','odb-dist.png'],
      ['Oasis Open Office HTML','oth','oth-dist.png'],
      ['Oasis Open Office Writer Master','odm','odm-dist.png'],
      ['Oasis Open Office Chart','odc',''],
      ['Oasis Open Office Image','odi',''],
      ['Word XML','docx','doc-dist.png'],
      ['Excel XML','xlsx','xls-dist.png'],
      ['PowerPoint XML','pptx','ppt-dist.png'],
      ['Comma-Separated Values','csv','csv-dist.png'],
      ['Scalable Vector Graphics','svg','svg-dist.png']
   ]);

   $DB->insertOrDie('glpi_entities', [
      'id'                       => 0,
      'name'                     => 'Root entity',
      'entities_id'              => '-1',
      'completename'             => 'Root entity',
      'level'                    => 1,
      'cartridges_alert_repeat'  => 0,
      'consumables_alert_repeat'  => 0,
      'use_licenses_alert'  => 0,
      'send_licenses_alert_before_delay'  => 0,
      'use_certificates_alert'  => 0,
      'send_certificates_alert_before_delay'  => 0,
      'use_contracts_alert'  => 0,
      'send_contracts_alert_before_delay'  => 0,
      'use_infocoms_alert'  => 0,
      'send_infocoms_alert_before_delay'  => 0,
      'use_reservations_alert'  => 0,
      'autoclose_delay'  => -10,
      'notclosed_delay'  => 0,
      'calendars_id'  => 0,
      'auto_assign_mode'  => -10,
      'tickettype'  => 1,
      'inquest_config'  => 1,
      'inquest_rate'  => 0,
      'inquest_delay'  => 0,
      'autofill_warranty_date'  => 0,
      'autofill_use_date'  => 0,
      'autofill_buy_date'  => 0,
      'autofill_delivery_date'  => 0,
      'autofill_order_date'  => 0,
      'tickettemplates_id'  => 1,
      'entities_id_software'  => -10,
      'default_contract_alert'  => 0,
      'default_infocom_alert'  => 0,
      'default_cartridges_alarm_threshold'  => 10,
      'default_consumables_alarm_threshold'  => 10,
      'delay_send_emails'  => 0,
      'is_notif_enable_default'  => 1,
      'autofill_decommission_date'  => 0
   ]);

   $DB->insertBulkOrDie('glpi_filesystems', ['name'], [
      ['ext'], ['ext2'], ['ext3'], ['ext4'], ['FAT'], ['FAT32'], ['VFAT'], ['HFS'], ['HPFS'],
      ['HTFS'], ['JFS2'], ['NFS'], ['NTFS'], ['ReiserFS'], ['SMBFS'], ['UDF'], ['UFS'],
      ['XFS'], ['ZFS']]);

   $DB->insertBulkOrDie('glpi_interfacetypes', ['name'], [
      ['IDE'], ['SATA'], ['SCSI'], ['USB'], ['AGP'], ['PCI'], ['PCIe'], ['PCI-X']]);

   $DB->insertBulkOrDie('glpi_notifications', ['name', 'itemtype', 'event', 'is_active'], [
      ['Alert Tickets not closed', 'Ticket', 'alertnotclosed', 1],
      ['New Ticket', 'Ticket', 'new', 1],
      ['Update Ticket', 'Ticket', 'update', 0],
      ['Close Ticket', 'Ticket', 'closed', 1],
      ['Add Followup', 'Ticket', 'add_followup', 1],
      ['Add Task', 'Ticket', 'add_task', 1],
      ['Update Followup', 'Ticket', 'update_followup', 1],
      ['Update Task', 'Ticket', 'update_task', 1],
      ['Delete Followup', 'Ticket', 'delete_followup', 1],
      ['Delete Task', 'Ticket', 'delete_task', 1],
      ['Resolve ticket', 'Ticket', 'solved', 1],
      ['Ticket Validation', 'Ticket', 'validation', 1],
      ['New Reservation', 'Reservation', 'new', 1],
      ['Update Reservation', 'Reservation', 'update', 1],
      ['Delete Reservation', 'Reservation', 'delete', 1],
      ['Alert Reservation', 'Reservation', 'alert', 1],
      ['Contract Notice', 'Contract', 'notice', 1],
      ['Contract End', 'Contract', 'end', 1],
      ['MySQL Synchronization', 'DBConnection', 'desynchronization', 1],
      ['Cartridges', 'CartridgeItem', 'alert', 1],
      ['Consumables', 'ConsumableItem', 'alert', 1],
      ['Infocoms', 'Infocom', 'alert', 1],
      ['Software Licenses', 'SoftwareLicense', 'alert', 1],
      ['Ticket Recall', 'Ticket', 'recall', 1],
      ['Password Forget', 'User', 'passwordforget', 1],
      ['Ticket Satisfaction', 'Ticket', 'satisfaction', 1],
      ['Item not unique', 'FieldUnicity', 'refuse', 1],
      ['Crontask Watcher', 'Crontask', 'alert', 1],
      ['New Problem', 'Problem', 'new', 1],
      ['Update Problem', 'Problem', 'update', 1],
      ['Resolve Problem', 'Problem', 'solved', 1],
      ['Add Task', 'Problem', 'add_task', 1],
      ['Update Task', 'Problem', 'update_task', 1],
      ['Delete Task', 'Problem', 'delete_task', 1],
      ['Close Problem', 'Problem', 'closed', 1],
      ['Delete Problem', 'Problem', 'delete', 1],
      ['Ticket Validation Answer', 'Ticket', 'validation_answer', 1],
      ['Contract End Periodicity', 'Contract', 'periodicity', 1],
      ['Contract Notice Periodicity', 'Contract', 'periodicitynotice', 1],
      ['Planning recall', 'PlanningRecall', 'planningrecall', 1],
      ['Delete Ticket', 'Ticket', 'delete', 1],
      ['New Change', 'Change', 'new', 1],
      ['Update Change', 'Change', 'update', 1],
      ['Resolve Change', 'Change', 'solved', 1],
      ['Add Task', 'Change', 'add_task', 1],
      ['Update Task', 'Change', 'update_task', 1],
      ['Delete Task', 'Change', 'delete_task', 1],
      ['Close Change', 'Change', 'closed', 1],
      ['Delete Change', 'Change', 'delete', 1],
      ['Ticket Satisfaction Answer', 'Ticket', 'replysatisfaction', 1],
      ['Receiver errors', 'MailCollector', 'error', 1],
      ['New Project', 'Project', 'new', 1],
      ['Update Project', 'Project', 'update', 1],
      ['Delete Project', 'Project', 'delete', 1],
      ['New Project Task', 'ProjectTask', 'new', 1],
      ['Update Project Task', 'ProjectTask', 'update', 1],
      ['Delete Project Task', 'ProjectTask', 'delete', 1],
      ['Request Unlock Items', 'ObjectLock', 'unlock', 1],
      ['New user in requesters', 'Ticket', 'requester_user', 1],
      ['New group in requesters', 'Ticket', 'requester_group', 1],
      ['New user in observers', 'Ticket', 'observer_user', 1],
      ['New group in observers', 'Ticket', 'observer_group', 1],
      ['New user in assignees', 'Ticket', 'assign_user', 1],
      ['New group in assignees', 'Ticket', 'assign_group', 1],
      ['New supplier in assignees', 'Ticket', 'assign_supplier', 1],
      ['Saved searches', 'SavedSearch_Alert', 'alert', 1],
      ['Certificates', 'Certificate', 'alert', 1]
   ]);

   $DB->insertBulkOrDie('glpi_notifications_notificationtemplates', ['notificationtemplates_id', 'mode', 'notifications_id'], [
      [1, 'mailing', 6],
      [2, 'mailing', 4],
      [3, 'mailing', 4],
      [4, 'mailing', 4],
      [5, 'mailing', 4],
      [6, 'mailing', 4],
      [7, 'mailing', 4],
      [8, 'mailing', 4],
      [9, 'mailing', 4],
      [10, 'mailing', 4],
      [11, 'mailing', 4],
      [12, 'mailing', 7],
      [13, 'mailing', 2],
      [14, 'mailing', 2],
      [15, 'mailing', 2],
      [16, 'mailing', 3],
      [17, 'mailing', 12],
      [18, 'mailing', 12],
      [19, 'mailing', 1],
      [20, 'mailing', 8],
      [21, 'mailing', 9],
      [22, 'mailing', 10],
      [23, 'mailing', 11],
      [24, 'mailing', 4],
      [25, 'mailing', 13],
      [26, 'mailing', 14],
      [27, 'mailing', 15],
      [28, 'mailing', 16],
      [29, 'mailing', 17],
      [30, 'mailing', 17],
      [31, 'mailing', 17],
      [32, 'mailing', 17],
      [33, 'mailing', 17],
      [34, 'mailing', 17],
      [35, 'mailing', 17],
      [36, 'mailing', 17],
      [37, 'mailing', 7],
      [38, 'mailing', 12],
      [39, 'mailing', 12],
      [40, 'mailing', 18],
      [41, 'mailing', 4],
      [42, 'mailing', 19],
      [43, 'mailing', 19],
      [44, 'mailing', 19],
      [45, 'mailing', 19],
      [46, 'mailing', 19],
      [47, 'mailing', 19],
      [48, 'mailing', 19],
      [49, 'mailing', 19],
      [50, 'mailing', 14],
      [51, 'mailing', 20],
      [52, 'mailing', 21],
      [53, 'mailing', 21],
      [54, 'mailing', 21],
      [55, 'mailing', 22],
      [56, 'mailing', 22],
      [57, 'mailing', 22],
      [58, 'mailing', 23],
      [59, 'mailing', 4],
      [60, 'mailing', 4],
      [61, 'mailing', 4],
      [62, 'mailing', 4],
      [63, 'mailing', 4],
      [64, 'mailing', 4],
      [65, 'mailing', 4],
      [66, 'mailing', 24],
      [67, 'mailing', 25]
   ]);

   $DB->insertBulkOrDie('glpi_notificationtargets', ['items_id', 'type', 'notifications_id'], [
      ['3','1','13'],
      ['1','1','13'],
      ['3','2','2'],
      ['1','1','2'],
      ['1','1','3'],
      ['1','1','5'],
      ['1','1','4'],
      ['2','1','3'],
      ['4','1','3'],
      ['3','1','2'],
      ['3','1','3'],
      ['3','1','5'],
      ['3','1','4'],
      ['1','1','19'],
      ['14','1','12'],
      ['3','1','14'],
      ['1','1','14'],
      ['3','1','15'],
      ['1','1','15'],
      ['1','1','6'],
      ['3','1','6'],
      ['1','1','7'],
      ['3','1','7'],
      ['1','1','8'],
      ['3','1','8'],
      ['1','1','9'],
      ['3','1','9'],
      ['1','1','10'],
      ['3','1','10'],
      ['1','1','11'],
      ['3','1','11'],
      ['19','1','25'],
      ['3','1','26'],
      ['21','1','2'],
      ['21','1','3'],
      ['21','1','5'],
      ['21','1','4'],
      ['21','1','6'],
      ['21','1','7'],
      ['21','1','8'],
      ['21','1','9'],
      ['21','1','10'],
      ['21','1','11'],
      ['1','1','41'],
      ['1','1','28'],
      ['3','1','29'],
      ['1','1','29'],
      ['21','1','29'],
      ['2','1','30'],
      ['4','1','30'],
      ['3','1','30'],
      ['1','1','30'],
      ['21','1','30'],
      ['3','1','31'],
      ['1','1','31'],
      ['21','1','31'],
      ['3','1','32'],
      ['1','1','32'],
      ['21','1','32'],
      ['3','1','33'],
      ['1','1','33'],
      ['21','1','33'],
      ['3','1','34'],
      ['1','1','34'],
      ['21','1','34'],
      ['3','1','35'],
      ['1','1','35'],
      ['21','1','35'],
      ['3','1','36'],
      ['1','1','36'],
      ['21','1','36'],
      ['14','1','37'],
      ['3','1','40'],
      ['3','1','42'],
      ['1','1','42'],
      ['21','1','42'],
      ['2','1','43'],
      ['4','1','43'],
      ['3','1','43'],
      ['1','1','43'],
      ['21','1','43'],
      ['3','1','44'],
      ['1','1','44'],
      ['21','1','44'],
      ['3','1','45'],
      ['1','1','45'],
      ['21','1','45'],
      ['3','1','46'],
      ['1','1','46'],
      ['21','1','46'],
      ['3','1','47'],
      ['1','1','47'],
      ['21','1','47'],
      ['3','1','48'],
      ['1','1','48'],
      ['21','1','48'],
      ['3','1','49'],
      ['1','1','49'],
      ['21','1','49'],
      ['3','1','50'],
      ['2','1','50'],
      ['1','1','51'],
      ['27','1','52'],
      ['1','1','52'],
      ['28','1','52'],
      ['27','1','53'],
      ['1','1','53'],
      ['28','1','53'],
      ['27','1','54'],
      ['1','1','54'],
      ['28','1','54'],
      ['31','1','55'],
      ['1','1','55'],
      ['32','1','55'],
      ['31','1','56'],
      ['1','1','56'],
      ['32','1','56'],
      ['31','1','57'],
      ['1','1','57'],
      ['32','1','57'],
      ['19','1','58'],
      ['3','1','59'],
      ['13','1','60'],
      ['21','1','61'],
      ['20','1','62'],
      ['2','1','63'],
      ['23','1','64'],
      ['8','1','65'],
      ['19','1','66']
   ]);

   $DB->insertBulkOrDie('glpi_notificationtemplates', ['name', 'itemtype'], [
      ['MySQL Synchronization','DBConnection'],
      ['Reservations','Reservation'],
      ['Alert Reservation','Reservation'],
      ['Tickets','Ticket'],
      ['Tickets (Simple)','Ticket'],
      ['Alert Tickets not closed','Ticket'],
      ['Tickets Validation','Ticket'],
      ['Cartridges','CartridgeItem'],
      ['Consumables','ConsumableItem'],
      ['Infocoms','Infocom'],
      ['Licenses','SoftwareLicense'],
      ['Contracts','Contract'],
      ['Password Forget','User'],
      ['Ticket Satisfaction','Ticket'],
      ['Item not unique','FieldUnicity'],
      ['Crontask','Crontask'],
      ['Problems','Problem'],
      ['Planning recall','PlanningRecall'],
      ['Changes','Change'],
      ['Receiver errors','MailCollector'],
      ['Projects','Project'],
      ['Project Tasks','ProjectTask'],
      ['Unlock Item request','ObjectLock'],
      ['Saved searches alerts','SavedSearch_Alert'],
      ['Certificates','Certificate']
   ]);

   $template_mysql_text = '##lang.dbconnection.delay## : ##dbconnection.delay##';
   $template_mysql_html = '&lt;p&gt;##lang.dbconnection.delay## : ##dbconnection.delay##&lt;/p&gt;';
   $template_reservation_text = '======================================================================
##lang.reservation.user##: ##reservation.user##
##lang.reservation.item.name##: ##reservation.itemtype## - ##reservation.item.name##
##IFreservation.tech## ##lang.reservation.tech## ##reservation.tech## ##ENDIFreservation.tech##
##lang.reservation.begin##: ##reservation.begin##
##lang.reservation.end##: ##reservation.end##
##lang.reservation.comment##: ##reservation.comment##
======================================================================
';
   $template_reservation_html = '&lt;!-- description{ color: inherit; background: #ebebeb;border-style: solid;border-color: #8d8d8d; border-width: 0px 1px 1px 0px; } --&gt;
&lt;p&gt;&lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;##lang.reservation.user##:&lt;/span&gt;##reservation.user##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;##lang.reservation.item.name##:&lt;/span&gt;##reservation.itemtype## - ##reservation.item.name##&lt;br /&gt;##IFreservation.tech## ##lang.reservation.tech## ##reservation.tech####ENDIFreservation.tech##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;##lang.reservation.begin##:&lt;/span&gt; ##reservation.begin##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;##lang.reservation.end##:&lt;/span&gt;##reservation.end##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;##lang.reservation.comment##:&lt;/span&gt; ##reservation.comment##&lt;/p&gt;';
   $template_alertreservation_text = '##lang.reservation.entity## : ##reservation.entity##


##FOREACHreservations##
##lang.reservation.itemtype## : ##reservation.itemtype##

 ##lang.reservation.item## : ##reservation.item##

 ##reservation.url##

 ##ENDFOREACHreservations##';
   $template_alertreservation_html = '&lt;p&gt;##lang.reservation.entity## : ##reservation.entity## &lt;br /&gt; &lt;br /&gt;
##FOREACHreservations## &lt;br /&gt;##lang.reservation.itemtype## :  ##reservation.itemtype##&lt;br /&gt;
 ##lang.reservation.item## :  ##reservation.item##&lt;br /&gt; &lt;br /&gt;
 &lt;a href=\"##reservation.url##\"&gt; ##reservation.url##&lt;/a&gt;&lt;br /&gt;
 ##ENDFOREACHreservations##&lt;/p&gt;';
   $template_ticketsimple_text = '##lang.ticket.url## : ##ticket.url##

##lang.ticket.description##


##lang.ticket.title##  :##ticket.title##

##lang.ticket.authors##  :##IFticket.authors##
##ticket.authors## ##ENDIFticket.authors##
##ELSEticket.authors##--##ENDELSEticket.authors##

##IFticket.category## ##lang.ticket.category##  :##ticket.category##
##ENDIFticket.category## ##ELSEticket.category##
##lang.ticket.nocategoryassigned## ##ENDELSEticket.category##

##lang.ticket.content##  : ##ticket.content##
##IFticket.itemtype##
##lang.ticket.item.name##  : ##ticket.itemtype## - ##ticket.item.name##
##ENDIFticket.itemtype##';
   $template_ticketsimple_html = '&lt;div&gt;##lang.ticket.url## : &lt;a href=\"##ticket.url##\"&gt;
##ticket.url##&lt;/a&gt;&lt;/div&gt;
&lt;div class=\"description b\"&gt;
##lang.ticket.description##&lt;/div&gt;
&lt;p&gt;&lt;span
style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;
##lang.ticket.title##&lt;/span&gt;&#160;:##ticket.title##
&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;
##lang.ticket.authors##&lt;/span&gt;
##IFticket.authors## ##ticket.authors##
##ENDIFticket.authors##
##ELSEticket.authors##--##ENDELSEticket.authors##
&lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;&#160
;&lt;/span&gt;&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; &lt;/span&gt;
##IFticket.category##&lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;
##lang.ticket.category## &lt;/span&gt;&#160;:##ticket.category##
##ENDIFticket.category## ##ELSEticket.category##
##lang.ticket.nocategoryassigned## ##ENDELSEticket.category##
&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;
##lang.ticket.content##&lt;/span&gt;&#160;:
##ticket.content##&lt;br /&gt;##IFticket.itemtype##
&lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;
##lang.ticket.item.name##&lt;/span&gt;&#160;:
##ticket.itemtype## - ##ticket.item.name##
##ENDIFticket.itemtype##&lt;/p&gt;';
   $template_ticket_text = ' ##IFticket.storestatus=5##
 ##lang.ticket.url## : ##ticket.urlapprove##
 ##lang.ticket.autoclosewarning##
 ##lang.ticket.solvedate## : ##ticket.solvedate##
 ##lang.ticket.solution.type## : ##ticket.solution.type##
 ##lang.ticket.solution.description## : ##ticket.solution.description## ##ENDIFticket.storestatus##
 ##ELSEticket.storestatus## ##lang.ticket.url## : ##ticket.url## ##ENDELSEticket.storestatus##

 ##lang.ticket.description##

 ##lang.ticket.title## : ##ticket.title##
 ##lang.ticket.authors## : ##IFticket.authors## ##ticket.authors## ##ENDIFticket.authors## ##ELSEticket.authors##--##ENDELSEticket.authors##
 ##lang.ticket.creationdate## : ##ticket.creationdate##
 ##lang.ticket.closedate## : ##ticket.closedate##
 ##lang.ticket.requesttype## : ##ticket.requesttype##
##lang.ticket.item.name## :

##FOREACHitems##

 ##IFticket.itemtype##
  ##ticket.itemtype## - ##ticket.item.name##
  ##IFticket.item.model## ##lang.ticket.item.model## : ##ticket.item.model## ##ENDIFticket.item.model##
  ##IFticket.item.serial## ##lang.ticket.item.serial## : ##ticket.item.serial## ##ENDIFticket.item.serial##
  ##IFticket.item.otherserial## ##lang.ticket.item.otherserial## : ##ticket.item.otherserial## ##ENDIFticket.item.otherserial##
 ##ENDIFticket.itemtype##

##ENDFOREACHitems##
##IFticket.assigntousers## ##lang.ticket.assigntousers## : ##ticket.assigntousers## ##ENDIFticket.assigntousers##
 ##lang.ticket.status## : ##ticket.status##
##IFticket.assigntogroups## ##lang.ticket.assigntogroups## : ##ticket.assigntogroups## ##ENDIFticket.assigntogroups##
 ##lang.ticket.urgency## : ##ticket.urgency##
 ##lang.ticket.impact## : ##ticket.impact##
 ##lang.ticket.priority## : ##ticket.priority##
##IFticket.user.email## ##lang.ticket.user.email## : ##ticket.user.email ##ENDIFticket.user.email##
##IFticket.category## ##lang.ticket.category## : ##ticket.category## ##ENDIFticket.category## ##ELSEticket.category## ##lang.ticket.nocategoryassigned## ##ENDELSEticket.category##
 ##lang.ticket.content## : ##ticket.content##
 ##IFticket.storestatus=6##

 ##lang.ticket.solvedate## : ##ticket.solvedate##
 ##lang.ticket.solution.type## : ##ticket.solution.type##
 ##lang.ticket.solution.description## : ##ticket.solution.description##
 ##ENDIFticket.storestatus##
 ##lang.ticket.numberoffollowups## : ##ticket.numberoffollowups##

##FOREACHfollowups##

 [##followup.date##] ##lang.followup.isprivate## : ##followup.isprivate##
 ##lang.followup.author## ##followup.author##
 ##lang.followup.description## ##followup.description##
 ##lang.followup.date## ##followup.date##
 ##lang.followup.requesttype## ##followup.requesttype##

##ENDFOREACHfollowups##
 ##lang.ticket.numberoftasks## : ##ticket.numberoftasks##

##FOREACHtasks##

 [##task.date##] ##lang.task.isprivate## : ##task.isprivate##
 ##lang.task.author## ##task.author##
 ##lang.task.description## ##task.description##
 ##lang.task.time## ##task.time##
 ##lang.task.category## ##task.category##

##ENDFOREACHtasks##';
   $template_ticket_html = '<!-- description{ color: inherit; background: #ebebeb; border-style: solid;border-color: #8d8d8d; border-width: 0px 1px 1px 0px; }    -->
<div>##IFticket.storestatus=5##</div>
<div>##lang.ticket.url## : <a href=\"##ticket.urlapprove##\">##ticket.urlapprove##</a> <strong>&#160;</strong></div>
<div><strong>##lang.ticket.autoclosewarning##</strong></div>
<div><span style=\"color: #888888;\"><strong><span style=\"text-decoration: underline;\">##lang.ticket.solvedate##</span></strong></span> : ##ticket.solvedate##<br /><span style=\"text-decoration: underline; color: #888888;\"><strong>##lang.ticket.solution.type##</strong></span> : ##ticket.solution.type##<br /><span style=\"text-decoration: underline; color: #888888;\"><strong>##lang.ticket.solution.description##</strong></span> : ##ticket.solution.description## ##ENDIFticket.storestatus##</div>
<div>##ELSEticket.storestatus## ##lang.ticket.url## : <a href=\"##ticket.url##\">##ticket.url##</a> ##ENDELSEticket.storestatus##</div>
<p class=\"description b\"><strong>##lang.ticket.description##</strong></p>
<p><span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.title##</span>&#160;:##ticket.title## <br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.authors##</span>&#160;:##IFticket.authors## ##ticket.authors## ##ENDIFticket.authors##    ##ELSEticket.authors##--##ENDELSEticket.authors## <br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.creationdate##</span>&#160;:##ticket.creationdate## <br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.closedate##</span>&#160;:##ticket.closedate## <br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.requesttype##</span>&#160;:##ticket.requesttype##<br />
<br /><span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.item.name##</span>&#160;:
<p>##FOREACHitems##</p>
<div class=\"description b\">##IFticket.itemtype## ##ticket.itemtype##&#160;- ##ticket.item.name## ##IFticket.item.model## ##lang.ticket.item.model## : ##ticket.item.model## ##ENDIFticket.item.model## ##IFticket.item.serial## ##lang.ticket.item.serial## : ##ticket.item.serial## ##ENDIFticket.item.serial## ##IFticket.item.otherserial## ##lang.ticket.item.otherserial## : ##ticket.item.otherserial## ##ENDIFticket.item.otherserial## ##ENDIFticket.itemtype## </div><br />
<p>##ENDFOREACHitems##</p>
##IFticket.assigntousers## <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.assigntousers##</span>&#160;: ##ticket.assigntousers## ##ENDIFticket.assigntousers##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\">##lang.ticket.status## </span>&#160;: ##ticket.status##<br /> ##IFticket.assigntogroups## <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.assigntogroups##</span>&#160;: ##ticket.assigntogroups## ##ENDIFticket.assigntogroups##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.urgency##</span>&#160;: ##ticket.urgency##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.impact##</span>&#160;: ##ticket.impact##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.priority##</span>&#160;: ##ticket.priority## <br /> ##IFticket.user.email##<span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.user.email##</span>&#160;: ##ticket.user.email ##ENDIFticket.user.email##    <br /> ##IFticket.category##<span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\">##lang.ticket.category## </span>&#160;:##ticket.category## ##ENDIFticket.category## ##ELSEticket.category## ##lang.ticket.nocategoryassigned## ##ENDELSEticket.category##    <br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.ticket.content##</span>&#160;: ##ticket.content##</p>
<br />##IFticket.storestatus=6##<br /><span style=\"text-decoration: underline;\"><strong><span style=\"color: #888888;\">##lang.ticket.solvedate##</span></strong></span> : ##ticket.solvedate##<br /><span style=\"color: #888888;\"><strong><span style=\"text-decoration: underline;\">##lang.ticket.solution.type##</span></strong></span> : ##ticket.solution.type##<br /><span style=\"text-decoration: underline; color: #888888;\"><strong>##lang.ticket.solution.description##</strong></span> : ##ticket.solution.description##<br />##ENDIFticket.storestatus##</p>
<div class=\"description b\">##lang.ticket.numberoffollowups##&#160;: ##ticket.numberoffollowups##</div>
<p>##FOREACHfollowups##</p>
<div class=\"description b\"><br /> <strong> [##followup.date##] <em>##lang.followup.isprivate## : ##followup.isprivate## </em></strong><br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.followup.author## </span> ##followup.author##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.followup.description## </span> ##followup.description##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.followup.date## </span> ##followup.date##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.followup.requesttype## </span> ##followup.requesttype##</div>
<p>##ENDFOREACHfollowups##</p>
<div class=\"description b\">##lang.ticket.numberoftasks##&#160;: ##ticket.numberoftasks##</div>
<p>##FOREACHtasks##</p>
<div class=\"description b\"><br /> <strong> [##task.date##] <em>##lang.task.isprivate## : ##task.isprivate## </em></strong><br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.task.author##</span> ##task.author##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.task.description##</span> ##task.description##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.task.time##</span> ##task.time##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.task.category##</span> ##task.category##</div>
<p>##ENDFOREACHtasks##</p>';
   $template_alertticket_text = '##lang.ticket.entity## : ##ticket.entity##

##FOREACHtickets##

##lang.ticket.title## : ##ticket.title##
 ##lang.ticket.status## : ##ticket.status##

 ##ticket.url##
 ##ENDFOREACHtickets##';
   $template_alertticket_html = '&lt;table class=\"tab_cadre\" border=\"1\" cellspacing=\"2\" cellpadding=\"3\"&gt;
&lt;tbody&gt;
&lt;tr&gt;
&lt;td style=\"text-align: left;\" width=\"auto\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##lang.ticket.authors##&lt;/span&gt;&lt;/td&gt;
&lt;td style=\"text-align: left;\" width=\"auto\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##lang.ticket.title##&lt;/span&gt;&lt;/td&gt;
&lt;td style=\"text-align: left;\" width=\"auto\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##lang.ticket.priority##&lt;/span&gt;&lt;/td&gt;
&lt;td style=\"text-align: left;\" width=\"auto\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##lang.ticket.status##&lt;/span&gt;&lt;/td&gt;
&lt;td style=\"text-align: left;\" width=\"auto\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##lang.ticket.attribution##&lt;/span&gt;&lt;/td&gt;
&lt;td style=\"text-align: left;\" width=\"auto\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##lang.ticket.creationdate##&lt;/span&gt;&lt;/td&gt;
&lt;td style=\"text-align: left;\" width=\"auto\" bgcolor=\"#cccccc\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##lang.ticket.content##&lt;/span&gt;&lt;/td&gt;
&lt;/tr&gt;
##FOREACHtickets##
&lt;tr&gt;
&lt;td width=\"auto\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##ticket.authors##&lt;/span&gt;&lt;/td&gt;
&lt;td width=\"auto\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;&lt;a href=\"##ticket.url##\"&gt;##ticket.title##&lt;/a&gt;&lt;/span&gt;&lt;/td&gt;
&lt;td width=\"auto\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##ticket.priority##&lt;/span&gt;&lt;/td&gt;
&lt;td width=\"auto\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##ticket.status##&lt;/span&gt;&lt;/td&gt;
&lt;td width=\"auto\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##IFticket.assigntousers####ticket.assigntousers##&lt;br /&gt;##ENDIFticket.assigntousers####IFticket.assigntogroups##&lt;br /&gt;##ticket.assigntogroups## ##ENDIFticket.assigntogroups####IFticket.assigntosupplier##&lt;br /&gt;##ticket.assigntosupplier## ##ENDIFticket.assigntosupplier##&lt;/span&gt;&lt;/td&gt;
&lt;td width=\"auto\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##ticket.creationdate##&lt;/span&gt;&lt;/td&gt;
&lt;td width=\"auto\"&gt;&lt;span style=\"font-size: 11px; text-align: left;\"&gt;##ticket.content##&lt;/span&gt;&lt;/td&gt;
&lt;/tr&gt;
##ENDFOREACHtickets##
&lt;/tbody&gt;
&lt;/table&gt;';
   $template_validation_text = '##FOREACHvalidations##

##IFvalidation.storestatus=2##
##validation.submission.title##
##lang.validation.commentsubmission## : ##validation.commentsubmission##
##ENDIFvalidation.storestatus##
##ELSEvalidation.storestatus## ##validation.answer.title## ##ENDELSEvalidation.storestatus##

##lang.ticket.url## : ##ticket.urlvalidation##

##IFvalidation.status## ##lang.validation.status## : ##validation.status## ##ENDIFvalidation.status##
##IFvalidation.commentvalidation##
##lang.validation.commentvalidation## : ##validation.commentvalidation##
##ENDIFvalidation.commentvalidation##
##ENDFOREACHvalidations##';
   $template_validation_html = '&lt;div&gt;##FOREACHvalidations##&lt;/div&gt;
&lt;p&gt;##IFvalidation.storestatus=2##&lt;/p&gt;
&lt;div&gt;##validation.submission.title##&lt;/div&gt;
&lt;div&gt;##lang.validation.commentsubmission## : ##validation.commentsubmission##&lt;/div&gt;
&lt;div&gt;##ENDIFvalidation.storestatus##&lt;/div&gt;
&lt;div&gt;##ELSEvalidation.storestatus## ##validation.answer.title## ##ENDELSEvalidation.storestatus##&lt;/div&gt;
&lt;div&gt;&lt;/div&gt;
&lt;div&gt;
&lt;div&gt;##lang.ticket.url## : &lt;a href=\"##ticket.urlvalidation##\"&gt; ##ticket.urlvalidation## &lt;/a&gt;&lt;/div&gt;
&lt;/div&gt;
&lt;p&gt;##IFvalidation.status## ##lang.validation.status## : ##validation.status## ##ENDIFvalidation.status##
&lt;br /&gt; ##IFvalidation.commentvalidation##&lt;br /&gt; ##lang.validation.commentvalidation## :
&#160; ##validation.commentvalidation##&lt;br /&gt; ##ENDIFvalidation.commentvalidation##
&lt;br /&gt;##ENDFOREACHvalidations##&lt;/p&gt;';
   $template_cartridge_text = '##lang.cartridge.entity## : ##cartridge.entity##


##FOREACHcartridges##
##lang.cartridge.item## : ##cartridge.item##


##lang.cartridge.reference## : ##cartridge.reference##

##lang.cartridge.remaining## : ##cartridge.remaining##

##cartridge.url##
 ##ENDFOREACHcartridges##';
   $template_cartridge_html = '&lt;p&gt;##lang.cartridge.entity## : ##cartridge.entity##
&lt;br /&gt; &lt;br /&gt;##FOREACHcartridges##
&lt;br /&gt;##lang.cartridge.item## :
##cartridge.item##&lt;br /&gt; &lt;br /&gt;
##lang.cartridge.reference## :
##cartridge.reference##&lt;br /&gt;
##lang.cartridge.remaining## :
##cartridge.remaining##&lt;br /&gt;
&lt;a href=\"##cartridge.url##\"&gt;
##cartridge.url##&lt;/a&gt;&lt;br /&gt;
##ENDFOREACHcartridges##&lt;/p&gt;';
   $template_consumable_text = '##lang.consumable.entity## : ##consumable.entity##


##FOREACHconsumables##
##lang.consumable.item## : ##consumable.item##


##lang.consumable.reference## : ##consumable.reference##

##lang.consumable.remaining## : ##consumable.remaining##

##consumable.url##

##ENDFOREACHconsumables##';
   $template_consumable_html = '&lt;p&gt;
##lang.consumable.entity## : ##consumable.entity##
&lt;br /&gt; &lt;br /&gt;##FOREACHconsumables##
&lt;br /&gt;##lang.consumable.item## : ##consumable.item##&lt;br /&gt;
&lt;br /&gt;##lang.consumable.reference## : ##consumable.reference##&lt;br /&gt;
##lang.consumable.remaining## : ##consumable.remaining##&lt;br /&gt;
&lt;a href=\"##consumable.url##\"&gt; ##consumable.url##&lt;/a&gt;&lt;br /&gt;
   ##ENDFOREACHconsumables##&lt;/p&gt;';
   $template_infocom_text = '##lang.infocom.entity## : ##infocom.entity##


##FOREACHinfocoms##

##lang.infocom.itemtype## : ##infocom.itemtype##

##lang.infocom.item## : ##infocom.item##


##lang.infocom.expirationdate## : ##infocom.expirationdate##

##infocom.url##
 ##ENDFOREACHinfocoms##';
   $template_infocom_html = '&lt;p&gt;##lang.infocom.entity## : ##infocom.entity##
&lt;br /&gt; &lt;br /&gt;##FOREACHinfocoms##
&lt;br /&gt;##lang.infocom.itemtype## : ##infocom.itemtype##&lt;br /&gt;
##lang.infocom.item## : ##infocom.item##&lt;br /&gt; &lt;br /&gt;
##lang.infocom.expirationdate## : ##infocom.expirationdate##
&lt;br /&gt; &lt;a href=\"##infocom.url##\"&gt;
##infocom.url##&lt;/a&gt;&lt;br /&gt;
##ENDFOREACHinfocoms##&lt;/p&gt;';
   $template_license_text = '##lang.license.entity## : ##license.entity##

##FOREACHlicenses##

##lang.license.item## : ##license.item##

##lang.license.serial## : ##license.serial##

##lang.license.expirationdate## : ##license.expirationdate##

##license.url##
 ##ENDFOREACHlicenses##';
   $template_license_html = '&lt;p&gt;
##lang.license.entity## : ##license.entity##&lt;br /&gt;
##FOREACHlicenses##
&lt;br /&gt;##lang.license.item## : ##license.item##&lt;br /&gt;
##lang.license.serial## : ##license.serial##&lt;br /&gt;
##lang.license.expirationdate## : ##license.expirationdate##
&lt;br /&gt; &lt;a href=\"##license.url##\"&gt; ##license.url##
&lt;/a&gt;&lt;br /&gt; ##ENDFOREACHlicenses##&lt;/p&gt;';
   $template_contract_text = '##lang.contract.entity## : ##contract.entity##

##FOREACHcontracts##
##lang.contract.name## : ##contract.name##
##lang.contract.number## : ##contract.number##
##lang.contract.time## : ##contract.time##
##IFcontract.type####lang.contract.type## : ##contract.type####ENDIFcontract.type##
##contract.url##
##ENDFOREACHcontracts##';
   $template_contract_html = '&lt;p&gt;##lang.contract.entity## : ##contract.entity##&lt;br /&gt;
&lt;br /&gt;##FOREACHcontracts##&lt;br /&gt;##lang.contract.name## :
##contract.name##&lt;br /&gt;
##lang.contract.number## : ##contract.number##&lt;br /&gt;
##lang.contract.time## : ##contract.time##&lt;br /&gt;
##IFcontract.type####lang.contract.type## : ##contract.type##
##ENDIFcontract.type##&lt;br /&gt;
&lt;a href=\"##contract.url##\"&gt;
##contract.url##&lt;/a&gt;&lt;br /&gt;
##ENDFOREACHcontracts##&lt;/p&gt;';
   $template_password_text = '##user.realname## ##user.firstname##

##lang.passwordforget.information##

##lang.passwordforget.link## ##user.passwordforgeturl##';
   $template_password_html = '&lt;p&gt;&lt;strong&gt;##user.realname## ##user.firstname##&lt;/strong&gt;&lt;/p&gt;
&lt;p&gt;##lang.passwordforget.information##&lt;/p&gt;
&lt;p&gt;##lang.passwordforget.link## &lt;a title=\"##user.passwordforgeturl##\" href=\"##user.passwordforgeturl##\"&gt;##user.passwordforgeturl##&lt;/a&gt;&lt;/p&gt;';
   $template_satisfaction_text = '##lang.ticket.title## : ##ticket.title##

##lang.ticket.closedate## : ##ticket.closedate##

##lang.satisfaction.text## ##ticket.urlsatisfaction##';
   $template_satisfaction_html = '&lt;p&gt;##lang.ticket.title## : ##ticket.title##&lt;/p&gt;
&lt;p&gt;##lang.ticket.closedate## : ##ticket.closedate##&lt;/p&gt;
&lt;p&gt;##lang.satisfaction.text## &lt;a href=\"##ticket.urlsatisfaction##\"&gt;##ticket.urlsatisfaction##&lt;/a&gt;&lt;/p&gt;';
   $template_unicity_text = '##lang.unicity.entity## : ##unicity.entity##

##lang.unicity.itemtype## : ##unicity.itemtype##

##lang.unicity.message## : ##unicity.message##

##lang.unicity.action_user## : ##unicity.action_user##

##lang.unicity.action_type## : ##unicity.action_type##

##lang.unicity.date## : ##unicity.date##';
   $template_unicity_html = '&lt;p&gt;##lang.unicity.entity## : ##unicity.entity##&lt;/p&gt;
&lt;p&gt;##lang.unicity.itemtype## : ##unicity.itemtype##&lt;/p&gt;
&lt;p&gt;##lang.unicity.message## : ##unicity.message##&lt;/p&gt;
&lt;p&gt;##lang.unicity.action_user## : ##unicity.action_user##&lt;/p&gt;
&lt;p&gt;##lang.unicity.action_type## : ##unicity.action_type##&lt;/p&gt;
&lt;p&gt;##lang.unicity.date## : ##unicity.date##&lt;/p&gt;';
   $template_crontask_text = '##lang.crontask.warning##

##FOREACHcrontasks##
 ##crontask.name## : ##crontask.description##

##ENDFOREACHcrontasks##';
   $template_crontask_html = '&lt;p&gt;##lang.crontask.warning##&lt;/p&gt;
&lt;p&gt;##FOREACHcrontasks## &lt;br /&gt;&lt;a href=\"##crontask.url##\"&gt;##crontask.name##&lt;/a&gt; : ##crontask.description##&lt;br /&gt; &lt;br /&gt;##ENDFOREACHcrontasks##&lt;/p&gt;';
   $template_problem_text = '##IFproblem.storestatus=5##
 ##lang.problem.url## : ##problem.urlapprove##
 ##lang.problem.solvedate## : ##problem.solvedate##
 ##lang.problem.solution.type## : ##problem.solution.type##
 ##lang.problem.solution.description## : ##problem.solution.description## ##ENDIFproblem.storestatus##
 ##ELSEproblem.storestatus## ##lang.problem.url## : ##problem.url## ##ENDELSEproblem.storestatus##

 ##lang.problem.description##

 ##lang.problem.title##  :##problem.title##
 ##lang.problem.authors##  :##IFproblem.authors## ##problem.authors## ##ENDIFproblem.authors## ##ELSEproblem.authors##--##ENDELSEproblem.authors##
 ##lang.problem.creationdate##  :##problem.creationdate##
 ##IFproblem.assigntousers## ##lang.problem.assigntousers##  : ##problem.assigntousers## ##ENDIFproblem.assigntousers##
 ##lang.problem.status##  : ##problem.status##
 ##IFproblem.assigntogroups## ##lang.problem.assigntogroups##  : ##problem.assigntogroups## ##ENDIFproblem.assigntogroups##
 ##lang.problem.urgency##  : ##problem.urgency##
 ##lang.problem.impact##  : ##problem.impact##
 ##lang.problem.priority## : ##problem.priority##
##IFproblem.category## ##lang.problem.category##  :##problem.category## ##ENDIFproblem.category## ##ELSEproblem.category## ##lang.problem.nocategoryassigned## ##ENDELSEproblem.category##
 ##lang.problem.content##  : ##problem.content##

##IFproblem.storestatus=6##
 ##lang.problem.solvedate## : ##problem.solvedate##
 ##lang.problem.solution.type## : ##problem.solution.type##
 ##lang.problem.solution.description## : ##problem.solution.description##
##ENDIFproblem.storestatus##
 ##lang.problem.numberoffollowups## : ##problem.numberoffollowups##

##FOREACHfollowups##

 [##followup.date##] ##lang.followup.isprivate## : ##followup.isprivate##
 ##lang.followup.author## ##followup.author##
 ##lang.followup.description## ##followup.description##
 ##lang.followup.date## ##followup.date##
 ##lang.followup.requesttype## ##followup.requesttype##

##ENDFOREACHfollowups##
 ##lang.problem.numberoftickets## : ##problem.numberoftickets##

##FOREACHtickets##
 [##ticket.date##] ##lang.problem.title## : ##ticket.title##
 ##lang.problem.content## ##ticket.content##

##ENDFOREACHtickets##
 ##lang.problem.numberoftasks## : ##problem.numberoftasks##

##FOREACHtasks##
 [##task.date##]
 ##lang.task.author## ##task.author##
 ##lang.task.description## ##task.description##
 ##lang.task.time## ##task.time##
 ##lang.task.category## ##task.category##

##ENDFOREACHtasks##
';
   $template_problem_html = '&lt;p&gt;##IFproblem.storestatus=5##&lt;/p&gt;
&lt;div&gt;##lang.problem.url## : &lt;a href=\"##problem.urlapprove##\"&gt;##problem.urlapprove##&lt;/a&gt;&lt;/div&gt;
&lt;div&gt;&lt;span style=\"color: #888888;\"&gt;&lt;strong&gt;&lt;span style=\"text-decoration: underline;\"&gt;##lang.problem.solvedate##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##problem.solvedate##&lt;br /&gt;&lt;span style=\"text-decoration: underline; color: #888888;\"&gt;&lt;strong&gt;##lang.problem.solution.type##&lt;/strong&gt;&lt;/span&gt; : ##problem.solution.type##&lt;br /&gt;&lt;span style=\"text-decoration: underline; color: #888888;\"&gt;&lt;strong&gt;##lang.problem.solution.description##&lt;/strong&gt;&lt;/span&gt; : ##problem.solution.description## ##ENDIFproblem.storestatus##&lt;/div&gt;
&lt;div&gt;##ELSEproblem.storestatus## ##lang.problem.url## : &lt;a href=\"##problem.url##\"&gt;##problem.url##&lt;/a&gt; ##ENDELSEproblem.storestatus##&lt;/div&gt;
&lt;p class=\"description b\"&gt;&lt;strong&gt;##lang.problem.description##&lt;/strong&gt;&lt;/p&gt;
&lt;p&gt;&lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.problem.title##&lt;/span&gt;&#160;:##problem.title## &lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.problem.authors##&lt;/span&gt;&#160;:##IFproblem.authors## ##problem.authors## ##ENDIFproblem.authors##    ##ELSEproblem.authors##--##ENDELSEproblem.authors## &lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.problem.creationdate##&lt;/span&gt;&#160;:##problem.creationdate## &lt;br /&gt; ##IFproblem.assigntousers## &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.problem.assigntousers##&lt;/span&gt;&#160;: ##problem.assigntousers## ##ENDIFproblem.assigntousers##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;##lang.problem.status## &lt;/span&gt;&#160;: ##problem.status##&lt;br /&gt; ##IFproblem.assigntogroups## &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.problem.assigntogroups##&lt;/span&gt;&#160;: ##problem.assigntogroups## ##ENDIFproblem.assigntogroups##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.problem.urgency##&lt;/span&gt;&#160;: ##problem.urgency##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.problem.impact##&lt;/span&gt;&#160;: ##problem.impact##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.problem.priority##&lt;/span&gt; : ##problem.priority## &lt;br /&gt;##IFproblem.category##&lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;##lang.problem.category## &lt;/span&gt;&#160;:##problem.category##  ##ENDIFproblem.category## ##ELSEproblem.category##  ##lang.problem.nocategoryassigned## ##ENDELSEproblem.category##    &lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.problem.content##&lt;/span&gt;&#160;: ##problem.content##&lt;/p&gt;
&lt;p&gt;##IFproblem.storestatus=6##&lt;br /&gt;&lt;span style=\"text-decoration: underline;\"&gt;&lt;strong&gt;&lt;span style=\"color: #888888;\"&gt;##lang.problem.solvedate##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##problem.solvedate##&lt;br /&gt;&lt;span style=\"color: #888888;\"&gt;&lt;strong&gt;&lt;span style=\"text-decoration: underline;\"&gt;##lang.problem.solution.type##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##problem.solution.type##&lt;br /&gt;&lt;span style=\"text-decoration: underline; color: #888888;\"&gt;&lt;strong&gt;##lang.problem.solution.description##&lt;/strong&gt;&lt;/span&gt; : ##problem.solution.description##&lt;br /&gt;##ENDIFproblem.storestatus##&lt;/p&gt;
<div class=\"description b\">##lang.problem.numberoffollowups##&#160;: ##problem.numberoffollowups##</div>
<p>##FOREACHfollowups##</p>
<div class=\"description b\"><br /> <strong> [##followup.date##] <em>##lang.followup.isprivate## : ##followup.isprivate## </em></strong><br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.followup.author## </span> ##followup.author##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.followup.description## </span> ##followup.description##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.followup.date## </span> ##followup.date##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.followup.requesttype## </span> ##followup.requesttype##</div>
<p>##ENDFOREACHfollowups##</p>
&lt;div class=\"description b\"&gt;##lang.problem.numberoftickets##&#160;: ##problem.numberoftickets##&lt;/div&gt;
&lt;p&gt;##FOREACHtickets##&lt;/p&gt;
&lt;div&gt;&lt;strong&gt; [##ticket.date##] &lt;em&gt;##lang.problem.title## : &lt;a href=\"##ticket.url##\"&gt;##ticket.title## &lt;/a&gt;&lt;/em&gt;&lt;/strong&gt;&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; &lt;/span&gt;&lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;##lang.problem.content## &lt;/span&gt; ##ticket.content##
&lt;p&gt;##ENDFOREACHtickets##&lt;/p&gt;
&lt;div class=\"description b\"&gt;##lang.problem.numberoftasks##&#160;: ##problem.numberoftasks##&lt;/div&gt;
&lt;p&gt;##FOREACHtasks##&lt;/p&gt;
&lt;div class=\"description b\"&gt;&lt;strong&gt;[##task.date##] &lt;/strong&gt;&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.task.author##&lt;/span&gt; ##task.author##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.task.description##&lt;/span&gt; ##task.description##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.task.time##&lt;/span&gt; ##task.time##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.task.category##&lt;/span&gt; ##task.category##&lt;/div&gt;
&lt;p&gt;##ENDFOREACHtasks##&lt;/p&gt;
&lt;/div&gt;';
   $template_planning_text = '##recall.action##: ##recall.item.name##

##recall.item.content##

##lang.recall.planning.begin##: ##recall.planning.begin##
##lang.recall.planning.end##: ##recall.planning.end##
##lang.recall.planning.state##: ##recall.planning.state##
##lang.recall.item.private##: ##recall.item.private##';
   $template_planning_html = '&lt;p&gt;##recall.action##: &lt;a href=\"##recall.item.url##\"&gt;##recall.item.name##&lt;/a&gt;&lt;/p&gt;
&lt;p&gt;##recall.item.content##&lt;/p&gt;
&lt;p&gt;##lang.recall.planning.begin##: ##recall.planning.begin##&lt;br /&gt;##lang.recall.planning.end##: ##recall.planning.end##&lt;br /&gt;##lang.recall.planning.state##: ##recall.planning.state##&lt;br /&gt;##lang.recall.item.private##: ##recall.item.private##&lt;br /&gt;&lt;br /&gt;&lt;/p&gt;
&lt;p&gt;&lt;br /&gt;&lt;br /&gt;&lt;/p&gt;';
   $template_change_text = '##IFchange.storestatus=5##
 ##lang.change.url## : ##change.urlapprove##
 ##lang.change.solvedate## : ##change.solvedate##
 ##lang.change.solution.type## : ##change.solution.type##
 ##lang.change.solution.description## : ##change.solution.description## ##ENDIFchange.storestatus##
 ##ELSEchange.storestatus## ##lang.change.url## : ##change.url## ##ENDELSEchange.storestatus##

 ##lang.change.description##

 ##lang.change.title##  :##change.title##
 ##lang.change.authors##  :##IFchange.authors## ##change.authors## ##ENDIFchange.authors## ##ELSEchange.authors##--##ENDELSEchange.authors##
 ##lang.change.creationdate##  :##change.creationdate##
 ##IFchange.assigntousers## ##lang.change.assigntousers##  : ##change.assigntousers## ##ENDIFchange.assigntousers##
 ##lang.change.status##  : ##change.status##
 ##IFchange.assigntogroups## ##lang.change.assigntogroups##  : ##change.assigntogroups## ##ENDIFchange.assigntogroups##
 ##lang.change.urgency##  : ##change.urgency##
 ##lang.change.impact##  : ##change.impact##
 ##lang.change.priority## : ##change.priority##
##IFchange.category## ##lang.change.category##  :##change.category## ##ENDIFchange.category## ##ELSEchange.category## ##lang.change.nocategoryassigned## ##ENDELSEchange.category##
 ##lang.change.content##  : ##change.content##

##IFchange.storestatus=6##
 ##lang.change.solvedate## : ##change.solvedate##
 ##lang.change.solution.type## : ##change.solution.type##
 ##lang.change.solution.description## : ##change.solution.description##
##ENDIFchange.storestatus##
 ##lang.change.numberoffollowups## : ##change.numberoffollowups##

##FOREACHfollowups##

 [##followup.date##] ##lang.followup.isprivate## : ##followup.isprivate##
 ##lang.followup.author## ##followup.author##
 ##lang.followup.description## ##followup.description##
 ##lang.followup.date## ##followup.date##
 ##lang.followup.requesttype## ##followup.requesttype##

##ENDFOREACHfollowups##
 ##lang.change.numberofproblems## : ##change.numberofproblems##

##FOREACHproblems##
 [##problem.date##] ##lang.change.title## : ##problem.title##
 ##lang.change.content## ##problem.content##

##ENDFOREACHproblems##
 ##lang.change.numberoftasks## : ##change.numberoftasks##

##FOREACHtasks##
 [##task.date##]
 ##lang.task.author## ##task.author##
 ##lang.task.description## ##task.description##
 ##lang.task.time## ##task.time##
 ##lang.task.category## ##task.category##

##ENDFOREACHtasks##
';
   $template_change_html = '&lt;p&gt;##IFchange.storestatus=5##&lt;/p&gt;
&lt;div&gt;##lang.change.url## : &lt;a href=\"##change.urlapprove##\"&gt;##change.urlapprove##&lt;/a&gt;&lt;/div&gt;
&lt;div&gt;&lt;span style=\"color: #888888;\"&gt;&lt;strong&gt;&lt;span style=\"text-decoration: underline;\"&gt;##lang.change.solvedate##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##change.solvedate##&lt;br /&gt;&lt;span style=\"text-decoration: underline; color: #888888;\"&gt;&lt;strong&gt;##lang.change.solution.type##&lt;/strong&gt;&lt;/span&gt; : ##change.solution.type##&lt;br /&gt;&lt;span style=\"text-decoration: underline; color: #888888;\"&gt;&lt;strong&gt;##lang.change.solution.description##&lt;/strong&gt;&lt;/span&gt; : ##change.solution.description## ##ENDIFchange.storestatus##&lt;/div&gt;
&lt;div&gt;##ELSEchange.storestatus## ##lang.change.url## : &lt;a href=\"##change.url##\"&gt;##change.url##&lt;/a&gt; ##ENDELSEchange.storestatus##&lt;/div&gt;
&lt;p class=\"description b\"&gt;&lt;strong&gt;##lang.change.description##&lt;/strong&gt;&lt;/p&gt;
&lt;p&gt;&lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.change.title##&lt;/span&gt;&#160;:##change.title## &lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.change.authors##&lt;/span&gt;&#160;:##IFchange.authors## ##change.authors## ##ENDIFchange.authors##    ##ELSEchange.authors##--##ENDELSEchange.authors## &lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.change.creationdate##&lt;/span&gt;&#160;:##change.creationdate## &lt;br /&gt; ##IFchange.assigntousers## &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.change.assigntousers##&lt;/span&gt;&#160;: ##change.assigntousers## ##ENDIFchange.assigntousers##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;##lang.change.status## &lt;/span&gt;&#160;: ##change.status##&lt;br /&gt; ##IFchange.assigntogroups## &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.change.assigntogroups##&lt;/span&gt;&#160;: ##change.assigntogroups## ##ENDIFchange.assigntogroups##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.change.urgency##&lt;/span&gt;&#160;: ##change.urgency##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.change.impact##&lt;/span&gt;&#160;: ##change.impact##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.change.priority##&lt;/span&gt; : ##change.priority## &lt;br /&gt;##IFchange.category##&lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;##lang.change.category## &lt;/span&gt;&#160;:##change.category##  ##ENDIFchange.category## ##ELSEchange.category##  ##lang.change.nocategoryassigned## ##ENDELSEchange.category##    &lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.change.content##&lt;/span&gt;&#160;: ##change.content##&lt;/p&gt;
&lt;p&gt;##IFchange.storestatus=6##&lt;br /&gt;&lt;span style=\"text-decoration: underline;\"&gt;&lt;strong&gt;&lt;span style=\"color: #888888;\"&gt;##lang.change.solvedate##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##change.solvedate##&lt;br /&gt;&lt;span style=\"color: #888888;\"&gt;&lt;strong&gt;&lt;span style=\"text-decoration: underline;\"&gt;##lang.change.solution.type##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##change.solution.type##&lt;br /&gt;&lt;span style=\"text-decoration: underline; color: #888888;\"&gt;&lt;strong&gt;##lang.change.solution.description##&lt;/strong&gt;&lt;/span&gt; : ##change.solution.description##&lt;br /&gt;##ENDIFchange.storestatus##&lt;/p&gt;
<div class=\"description b\">##lang.change.numberoffollowups##&#160;: ##change.numberoffollowups##</div>
<p>##FOREACHfollowups##</p>
<div class=\"description b\"><br /> <strong> [##followup.date##] <em>##lang.followup.isprivate## : ##followup.isprivate## </em></strong><br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.followup.author## </span> ##followup.author##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.followup.description## </span> ##followup.description##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.followup.date## </span> ##followup.date##<br /> <span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"> ##lang.followup.requesttype## </span> ##followup.requesttype##</div>
<p>##ENDFOREACHfollowups##</p>
&lt;div class=\"description b\"&gt;##lang.change.numberofproblems##&#160;: ##change.numberofproblems##&lt;/div&gt;
&lt;p&gt;##FOREACHproblems##&lt;/p&gt;
&lt;div&gt;&lt;strong&gt; [##problem.date##] &lt;em&gt;##lang.change.title## : &lt;a href=\"##problem.url##\"&gt;##problem.title## &lt;/a&gt;&lt;/em&gt;&lt;/strong&gt;&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; &lt;/span&gt;&lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt;##lang.change.content## &lt;/span&gt; ##problem.content##
&lt;p&gt;##ENDFOREACHproblems##&lt;/p&gt;
&lt;div class=\"description b\"&gt;##lang.change.numberoftasks##&#160;: ##change.numberoftasks##&lt;/div&gt;
&lt;p&gt;##FOREACHtasks##&lt;/p&gt;
&lt;div class=\"description b\"&gt;&lt;strong&gt;[##task.date##] &lt;/strong&gt;&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.task.author##&lt;/span&gt; ##task.author##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.task.description##&lt;/span&gt; ##task.description##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.task.time##&lt;/span&gt; ##task.time##&lt;br /&gt; &lt;span style=\"color: #8b8c8f; font-weight: bold; text-decoration: underline;\"&gt; ##lang.task.category##&lt;/span&gt; ##task.category##&lt;/div&gt;
&lt;p&gt;##ENDFOREACHtasks##&lt;/p&gt;
&lt;/div&gt;';
   $template_receiver_text = '##FOREACHmailcollectors##
##lang.mailcollector.name## : ##mailcollector.name##
##lang.mailcollector.errors## : ##mailcollector.errors##
##mailcollector.url##
##ENDFOREACHmailcollectors##';
   $template_receiver_html = '&lt;p&gt;##FOREACHmailcollectors##&lt;br /&gt;##lang.mailcollector.name## : ##mailcollector.name##&lt;br /&gt; ##lang.mailcollector.errors## : ##mailcollector.errors##&lt;br /&gt;&lt;a href=\"##mailcollector.url##\"&gt;##mailcollector.url##&lt;/a&gt;&lt;br /&gt; ##ENDFOREACHmailcollectors##&lt;/p&gt;
&lt;p&gt;&lt;/p&gt;';
   $template_project_text = '##lang.project.url## : ##project.url##

##lang.project.description##

##lang.project.name## : ##project.name##
##lang.project.code## : ##project.code##
##lang.project.manager## : ##project.manager##
##lang.project.managergroup## : ##project.managergroup##
##lang.project.creationdate## : ##project.creationdate##
##lang.project.priority## : ##project.priority##
##lang.project.state## : ##project.state##
##lang.project.type## : ##project.type##
##lang.project.description## : ##project.description##

##lang.project.numberoftasks## : ##project.numberoftasks##



##FOREACHtasks##

[##task.creationdate##]
##lang.task.name## : ##task.name##
##lang.task.state## : ##task.state##
##lang.task.type## : ##task.type##
##lang.task.percent## : ##task.percent##
##lang.task.description## : ##task.description##

##ENDFOREACHtasks##';
   $template_project_html = '&lt;p&gt;##lang.project.url## : &lt;a href=\"##project.url##\"&gt;##project.url##&lt;/a&gt;&lt;/p&gt;
&lt;p&gt;&lt;strong&gt;##lang.project.description##&lt;/strong&gt;&lt;/p&gt;
&lt;p&gt;##lang.project.name## : ##project.name##&lt;br /&gt;##lang.project.code## : ##project.code##&lt;br /&gt; ##lang.project.manager## : ##project.manager##&lt;br /&gt;##lang.project.managergroup## : ##project.managergroup##&lt;br /&gt; ##lang.project.creationdate## : ##project.creationdate##&lt;br /&gt;##lang.project.priority## : ##project.priority## &lt;br /&gt;##lang.project.state## : ##project.state##&lt;br /&gt;##lang.project.type## : ##project.type##&lt;br /&gt;##lang.project.description## : ##project.description##&lt;/p&gt;
&lt;p&gt;##lang.project.numberoftasks## : ##project.numberoftasks##&lt;/p&gt;
&lt;div&gt;
&lt;p&gt;##FOREACHtasks##&lt;/p&gt;
&lt;div&gt;&lt;strong&gt;[##task.creationdate##] &lt;/strong&gt;&lt;br /&gt; ##lang.task.name## : ##task.name##&lt;br /&gt;##lang.task.state## : ##task.state##&lt;br /&gt;##lang.task.type## : ##task.type##&lt;br /&gt;##lang.task.percent## : ##task.percent##&lt;br /&gt;##lang.task.description## : ##task.description##&lt;/div&gt;
&lt;p&gt;##ENDFOREACHtasks##&lt;/p&gt;
&lt;/div&gt;';
   $template_projecttask_text = '##lang.projecttask.url## : ##projecttask.url##

##lang.projecttask.description##

##lang.projecttask.name## : ##projecttask.name##
##lang.projecttask.project## : ##projecttask.project##
##lang.projecttask.creationdate## : ##projecttask.creationdate##
##lang.projecttask.state## : ##projecttask.state##
##lang.projecttask.type## : ##projecttask.type##
##lang.projecttask.description## : ##projecttask.description##

##lang.projecttask.numberoftasks## : ##projecttask.numberoftasks##



##FOREACHtasks##

[##task.creationdate##]
##lang.task.name## : ##task.name##
##lang.task.state## : ##task.state##
##lang.task.type## : ##task.type##
##lang.task.percent## : ##task.percent##
##lang.task.description## : ##task.description##

##ENDFOREACHtasks##';
   $template_projecttask_html = '&lt;p&gt;##lang.projecttask.url## : &lt;a href=\"##projecttask.url##\"&gt;##projecttask.url##&lt;/a&gt;&lt;/p&gt;
&lt;p&gt;&lt;strong&gt;##lang.projecttask.description##&lt;/strong&gt;&lt;/p&gt;
&lt;p&gt;##lang.projecttask.name## : ##projecttask.name##&lt;br /&gt;##lang.projecttask.project## : &lt;a href=\"##projecttask.projecturl##\"&gt;##projecttask.project##&lt;/a&gt;&lt;br /&gt;##lang.projecttask.creationdate## : ##projecttask.creationdate##&lt;br /&gt;##lang.projecttask.state## : ##projecttask.state##&lt;br /&gt;##lang.projecttask.type## : ##projecttask.type##&lt;br /&gt;##lang.projecttask.description## : ##projecttask.description##&lt;/p&gt;
&lt;p&gt;##lang.projecttask.numberoftasks## : ##projecttask.numberoftasks##&lt;/p&gt;
&lt;div&gt;
&lt;p&gt;##FOREACHtasks##&lt;/p&gt;
&lt;div&gt;&lt;strong&gt;[##task.creationdate##] &lt;/strong&gt;&lt;br /&gt;##lang.task.name## : ##task.name##&lt;br /&gt;##lang.task.state## : ##task.state##&lt;br /&gt;##lang.task.type## : ##task.type##&lt;br /&gt;##lang.task.percent## : ##task.percent##&lt;br /&gt;##lang.task.description## : ##task.description##&lt;/div&gt;
&lt;p&gt;##ENDFOREACHtasks##&lt;/p&gt;
&lt;/div&gt;';
   $template_unlockitem_text = '##objectlock.type## ###objectlock.id## - ##objectlock.name##

      ##lang.objectlock.url##
      ##objectlock.url##

      ##lang.objectlock.date_mod##
      ##objectlock.date_mod##

      Hello ##objectlock.lockedby.firstname##,
      Could go to this item and unlock it for me?
      Thank you,
      Regards,
      ##objectlock.requester.firstname##';
   $template_unlockitem_html = '&lt;table&gt;
      &lt;tbody&gt;
      &lt;tr&gt;&lt;th colspan=\"2\"&gt;&lt;a href=\"##objectlock.url##\"&gt;##objectlock.type## ###objectlock.id## - ##objectlock.name##&lt;/a&gt;&lt;/th&gt;&lt;/tr&gt;
      &lt;tr&gt;
      &lt;td&gt;##lang.objectlock.url##&lt;/td&gt;
      &lt;td&gt;##objectlock.url##&lt;/td&gt;
      &lt;/tr&gt;
      &lt;tr&gt;
      &lt;td&gt;##lang.objectlock.date_mod##&lt;/td&gt;
      &lt;td&gt;##objectlock.date_mod##&lt;/td&gt;
      &lt;/tr&gt;
      &lt;/tbody&gt;
      &lt;/table&gt;
      &lt;p&gt;&lt;span style=\"font-size: small;\"&gt;Hello ##objectlock.lockedby.firstname##,&lt;br /&gt;Could go to this item and unlock it for me?&lt;br /&gt;Thank you,&lt;br /&gt;Regards,&lt;br /&gt;##objectlock.requester.firstname## ##objectlock.requester.lastname##&lt;/span&gt;&lt;/p&gt;';
   $template_savedsearch_text = '##savedsearch.type## ###savedsearch.id## - ##savedsearch.name##

      ##savedsearch.message##

      ##lang.savedsearch.url##
      ##savedsearch.url##

      Regards,';
   $template_savedsearch_html = '&lt;table&gt;
      &lt;tbody&gt;
      &lt;tr&gt;&lt;th colspan=\"2\"&gt;&lt;a href=\"##savedsearch.url##\"&gt;##savedsearch.type## ###savedsearch.id## - ##savedsearch.name##&lt;/a&gt;&lt;/th&gt;&lt;/tr&gt;
      &lt;tr&gt;&lt;td colspan=\"2\"&gt;&lt;a href=\"##savedsearch.url##\"&gt;##savedsearch.message##&lt;/a&gt;&lt;/td&gt;&lt;/tr&gt;
      &lt;tr&gt;
      &lt;td&gt;##lang.savedsearch.url##&lt;/td&gt;
      &lt;td&gt;##savedsearch.url##&lt;/td&gt;
      &lt;/tr&gt;
      &lt;/tbody&gt;
      &lt;/table&gt;
      &lt;p&gt;&lt;span style=\"font-size: small;\"&gt;Hello &lt;br /&gt;Regards,&lt;/span&gt;&lt;/p&gt;';
   $template_certificate_text = '##lang.certificate.entity## : ##certificate.entity##

##FOREACHcertificates##

##lang.certificate.serial## : ##certificate.serial##

##lang.certificate.expirationdate## : ##certificate.expirationdate##

##certificate.url##
 ##ENDFOREACHcertificates##';
   $template_certificate_html = '&lt;p&gt;
##lang.certificate.entity## : ##certificate.entity##&lt;br /&gt;
##FOREACHcertificates##
&lt;br /&gt;##lang.certificate.name## : ##certificate.name##&lt;br /&gt;
##lang.certificate.serial## : ##certificate.serial##&lt;br /&gt;
##lang.certificate.expirationdate## : ##certificate.expirationdate##
&lt;br /&gt; &lt;a href=\"##certificate.url##\"&gt; ##certificate.url##
&lt;/a&gt;&lt;br /&gt; ##ENDFOREACHcertificates##&lt;/p&gt;';

   // Break into multiple calls to avoid reaching max_allowed_packet
   $DB->insertBulkOrDie('glpi_notificationtemplatetranslations', ['notificationtemplates_id', 'subject', 'content_text', 'content_html'], [
      [1, '##lang.dbconnection.title##', $template_mysql_text, $template_mysql_html],
      [2, '##reservation.action##', $template_reservation_text, $template_reservation_html],
      [3, '##reservation.action##  ##reservation.entity##', $template_alertreservation_text, $template_alertreservation_html],
      [4, '##ticket.action## ##ticket.title##', $template_ticket_text, $template_ticket_html],
      [5, '##lang.dbconnection.title##', $template_ticketsimple_text, $template_ticketsimple_html],
      [6, '##ticket.action## ##ticket.entity##', $template_alertticket_text, $template_alertticket_html],
      [7, '##ticket.action## ##ticket.title##', $template_validation_text, $template_validation_html],
      [8, '##cartridge.action##  ##cartridge.entity##', $template_cartridge_text, $template_cartridge_html],
      [9, '##consumable.action##  ##consumable.entity##', $template_consumable_text, $template_consumable_html],
      [10, '##infocom.action##  ##infocom.entity##', $template_infocom_text, $template_infocom_html],
   ]);
   $DB->insertBulkOrDie('glpi_notificationtemplatetranslations', ['notificationtemplates_id', 'subject', 'content_text', 'content_html'], [
      [11, '##license.action##  ##license.entity##', $template_license_text, $template_license_html],
      [12, '##contract.action##  ##contract.entity##', $template_contract_text, $template_contract_html],
      [13, '##user.action##', $template_password_text, $template_password_html],
      [14, '##ticket.action## ##ticket.title##', $template_satisfaction_text, $template_satisfaction_html],
      [15, '##lang.unicity.action##', $template_unicity_text, $template_unicity_html],
      [16, '##crontask.action##', $template_crontask_text, $template_crontask_html],
      [17, '##problem.action## ##problem.title##', $template_problem_text, $template_problem_html],
      [18, '##recall.action##: ##recall.item.name##', $template_planning_text, $template_planning_html],
      [19, '##change.action## ##change.title##', $template_change_text, $template_change_html],
      [20, '##mailcollector.action##', $template_receiver_text, $template_receiver_html],
   ]);
   $DB->insertBulkOrDie('glpi_notificationtemplatetranslations', ['notificationtemplates_id', 'subject', 'content_text', 'content_html'], [
      [21, '##project.action## ##project.name## ##project.code##', $template_project_text, $template_project_html],
      [22, '##projecttask.action## ##projecttask.name##', $template_projecttask_text, $template_projecttask_html],
      [23, '##objectlock.action##', $template_unlockitem_text, $template_unlockitem_html],
      [24, '##savedsearch.action## ##savedsearch.name##', $template_savedsearch_text, $template_savedsearch_html],
      [25, '##certificate.action##  ##certificate.entity##', $template_certificate_text, $template_certificate_html],
   ]);

   $DB->insertBulkOrDie('glpi_profilerights', ['profiles_id', 'name', 'rights'], [
      ['1','computer','0'],
      ['1','monitor','0'],
      ['1','software','0'],
      ['1','networking','0'],
      ['1','internet','0'],
      ['1','printer','0'],
      ['1','peripheral','0'],
      ['1','cartridge','0'],
      ['1','consumable','0'],
      ['1','phone','0'],
      ['6','queuednotification','0'],
      ['1','contact_enterprise','0'],
      ['1','document','0'],
      ['1','contract','0'],
      ['1','infocom','0'],
      ['1','knowbase','2048'],
      ['1','reservation','1024'],
      ['1','reports','0'],
      ['1','dropdown','0'],
      ['1','device','0'],
      ['1','typedoc','0'],
      ['1','link','0'],
      ['1','config','0'],
      ['1','rule_ticket','0'],
      ['1','rule_import','0'],
      ['1','rule_ldap','0'],
      ['1','rule_softwarecategories','0'],
      ['1','search_config','0'],
      ['5','location','0'],
      ['7','domain','23'],
      ['1','profile','0'],
      ['1','user','0'],
      ['1','group','0'],
      ['1','entity','0'],
      ['1','transfer','0'],
      ['1','logs','0'],
      ['1','reminder_public','1'],
      ['1','rssfeed_public','1'],
      ['1','bookmark_public','0'],
      ['1','backup','0'],
      ['1','ticket','5'],
      ['1','followup','5'],
      ['1','task','1'],
      ['1','planning','0'],
      ['2','state','0'],
      ['2','taskcategory','0'],
      ['1','statistic','0'],
      ['1','password_update','1'],
      ['1','show_group_hardware','0'],
      ['1','rule_dictionnary_software','0'],
      ['1','rule_dictionnary_dropdown','0'],
      ['1','budget','0'],
      ['1','notification','0'],
      ['1','rule_mailcollector','0'],
      ['7','solutiontemplate','23'],
      ['1','calendar','0'],
      ['1','slm','0'],
      ['1','rule_dictionnary_printer','0'],
      ['1','problem','0'],
      ['2','netpoint','0'],
      ['4','knowbasecategory','23'],
      ['5','itilcategory','0'],
      ['1','tickettemplate','0'],
      ['1','ticketrecurrent','0'],
      ['1','ticketcost','0'],
      ['6','changevalidation','20'],
      ['1','ticketvalidation','0'],
      ['2','computer','33'],
      ['2','monitor','33'],
      ['2','software','33'],
      ['2','networking','33'],
      ['2','internet','1'],
      ['2','printer','33'],
      ['2','peripheral','33'],
      ['2','cartridge','33'],
      ['2','consumable','33'],
      ['2','phone','33'],
      ['5','queuednotification','0'],
      ['2','contact_enterprise','33'],
      ['2','document','33'],
      ['2','contract','33'],
      ['2','infocom','1'],
      ['2','knowbase','10241'],
      ['2','reservation','1025'],
      ['2','reports','1'],
      ['2','dropdown','0'],
      ['2','device','0'],
      ['2','typedoc','1'],
      ['2','link','1'],
      ['2','config','0'],
      ['2','rule_ticket','0'],
      ['2','rule_import','0'],
      ['2','rule_ldap','0'],
      ['2','rule_softwarecategories','0'],
      ['2','search_config','1024'],
      ['4','location','23'],
      ['6','domain','0'],
      ['2','profile','0'],
      ['2','user','2049'],
      ['2','group','33'],
      ['2','entity','0'],
      ['2','transfer','0'],
      ['2','logs','0'],
      ['2','reminder_public','1'],
      ['2','rssfeed_public','1'],
      ['2','bookmark_public','0'],
      ['2','backup','0'],
      ['2','ticket','168989'],
      ['2','followup','5'],
      ['2','task','1'],
      ['6','projecttask','1025'],
      ['7','projecttask','1025'],
      ['2','planning','1'],
      ['1','state','0'],
      ['1','taskcategory','0'],
      ['2','statistic','1'],
      ['2','password_update','1'],
      ['2','show_group_hardware','0'],
      ['2','rule_dictionnary_software','0'],
      ['2','rule_dictionnary_dropdown','0'],
      ['2','budget','33'],
      ['2','notification','0'],
      ['2','rule_mailcollector','0'],
      ['5','solutiontemplate','0'],
      ['6','solutiontemplate','0'],
      ['2','calendar','0'],
      ['2','slm','0'],
      ['2','rule_dictionnary_printer','0'],
      ['2','problem','1057'],
      ['1','netpoint','0'],
      ['3','knowbasecategory','23'],
      ['4','itilcategory','23'],
      ['2','tickettemplate','0'],
      ['2','ticketrecurrent','0'],
      ['2','ticketcost','1'],
      ['4','changevalidation','1044'],
      ['5','changevalidation','20'],
      ['2','ticketvalidation','15376'],
      ['3','computer','127'],
      ['3','monitor','127'],
      ['3','software','127'],
      ['3','networking','127'],
      ['3','internet','31'],
      ['3','printer','127'],
      ['3','peripheral','127'],
      ['3','cartridge','127'],
      ['3','consumable','127'],
      ['3','phone','127'],
      ['4','queuednotification','31'],
      ['3','contact_enterprise','127'],
      ['3','document','127'],
      ['3','contract','127'],
      ['3','infocom','23'],
      ['3','knowbase','14359'],
      ['3','reservation','1055'],
      ['3','reports','1'],
      ['3','dropdown','23'],
      ['3','device','23'],
      ['3','typedoc','23'],
      ['3','link','23'],
      ['3','config','0'],
      ['3','rule_ticket','1047'],
      ['3','rule_import','0'],
      ['3','rule_ldap','0'],
      ['3','rule_softwarecategories','0'],
      ['3','search_config','3072'],
      ['3','location','23'],
      ['5','domain','0'],
      ['3','profile','1'],
      ['3','user','7199'],
      ['3','group','119'],
      ['3','entity','33'],
      ['3','transfer','1'],
      ['3','logs','1'],
      ['3','reminder_public','23'],
      ['3','rssfeed_public','23'],
      ['3','bookmark_public','23'],
      ['3','backup','1024'],
      ['3','ticket','261151'],
      ['3','followup','15383'],
      ['3','task','13329'],
      ['3','projecttask','1121'],
      ['4','projecttask','1121'],
      ['5','projecttask','0'],
      ['3','planning','3073'],
      ['7','taskcategory','23'],
      ['7','netpoint','23'],
      ['3','statistic','1'],
      ['3','password_update','1'],
      ['3','show_group_hardware','0'],
      ['3','rule_dictionnary_software','0'],
      ['3','rule_dictionnary_dropdown','0'],
      ['3','budget','127'],
      ['3','notification','0'],
      ['3','rule_mailcollector','23'],
      ['3','solutiontemplate','23'],
      ['4','solutiontemplate','23'],
      ['3','calendar','23'],
      ['3','slm','23'],
      ['3','rule_dictionnary_printer','0'],
      ['3','problem','1151'],
      ['2','knowbasecategory','0'],
      ['3','itilcategory','23'],
      ['3','tickettemplate','23'],
      ['3','ticketrecurrent','1'],
      ['3','ticketcost','23'],
      ['2','changevalidation','1044'],
      ['3','changevalidation','1044'],
      ['3','ticketvalidation','15376'],
      ['4','computer','255'],
      ['4','monitor','255'],
      ['4','software','255'],
      ['4','networking','255'],
      ['4','internet','159'],
      ['4','printer','255'],
      ['4','peripheral','255'],
      ['4','cartridge','255'],
      ['4','consumable','255'],
      ['4','phone','255'],
      ['4','contact_enterprise','255'],
      ['4','document','255'],
      ['4','contract','255'],
      ['4','infocom','23'],
      ['4','knowbase','15383'],
      ['4','reservation','1055'],
      ['4','reports','1'],
      ['4','dropdown','23'],
      ['4','device','23'],
      ['4','typedoc','23'],
      ['4','link','159'],
      ['4','config','3'],
      ['4','rule_ticket','1047'],
      ['4','rule_import','23'],
      ['4','rule_ldap','23'],
      ['4','rule_softwarecategories','23'],
      ['4','search_config','3072'],
      ['2','location','0'],
      ['4','domain','23'],
      ['4','profile','23'],
      ['4','user','7327'],
      ['4','group','119'],
      ['4','entity','3327'],
      ['4','transfer','23'],
      ['4','logs','1'],
      ['4','reminder_public','159'],
      ['4','rssfeed_public','159'],
      ['4','bookmark_public','23'],
      ['4','backup','1045'],
      ['4','ticket','261151'],
      ['4','followup','15383'],
      ['4','task','13329'],
      ['7','project','1151'],
      ['1','projecttask','0'],
      ['2','projecttask','1025'],
      ['4','planning','3073'],
      ['6','taskcategory','0'],
      ['6','netpoint','0'],
      ['4','statistic','1'],
      ['4','password_update','1'],
      ['4','show_group_hardware','1'],
      ['4','rule_dictionnary_software','23'],
      ['4','rule_dictionnary_dropdown','23'],
      ['4','budget','127'],
      ['4','notification','23'],
      ['4','rule_mailcollector','23'],
      ['1','solutiontemplate','0'],
      ['2','solutiontemplate','0'],
      ['4','calendar','23'],
      ['4','slm','23'],
      ['4','rule_dictionnary_printer','23'],
      ['4','problem','1151'],
      ['1','knowbasecategory','0'],
      ['2','itilcategory','0'],
      ['4','tickettemplate','23'],
      ['4','ticketrecurrent','23'],
      ['4','ticketcost','23'],
      ['7','change','1151'],
      ['1','changevalidation','0'],
      ['4','ticketvalidation','15376'],
      ['5','computer','0'],
      ['5','monitor','0'],
      ['5','software','0'],
      ['5','networking','0'],
      ['5','internet','0'],
      ['5','printer','0'],
      ['5','peripheral','0'],
      ['5','cartridge','0'],
      ['5','consumable','0'],
      ['5','phone','0'],
      ['3','queuednotification','0'],
      ['5','contact_enterprise','0'],
      ['5','document','0'],
      ['5','contract','0'],
      ['5','infocom','0'],
      ['5','knowbase','10240'],
      ['5','reservation','0'],
      ['5','reports','0'],
      ['5','dropdown','0'],
      ['5','device','0'],
      ['5','typedoc','0'],
      ['5','link','0'],
      ['5','config','0'],
      ['5','rule_ticket','0'],
      ['5','rule_import','0'],
      ['5','rule_ldap','0'],
      ['5','rule_softwarecategories','0'],
      ['5','search_config','0'],
      ['1','location','0'],
      ['3','domain','23'],
      ['5','profile','0'],
      ['5','user','1025'],
      ['5','group','0'],
      ['5','entity','0'],
      ['5','transfer','0'],
      ['5','logs','0'],
      ['5','reminder_public','0'],
      ['5','rssfeed_public','0'],
      ['5','bookmark_public','0'],
      ['5','backup','0'],
      ['5','ticket','140295'],
      ['5','followup','12295'],
      ['5','task','8193'],
      ['4','project','1151'],
      ['5','project','1151'],
      ['6','project','1151'],
      ['5','planning','1'],
      ['5','taskcategory','0'],
      ['5','netpoint','0'],
      ['5','statistic','1'],
      ['5','password_update','1'],
      ['5','show_group_hardware','0'],
      ['5','rule_dictionnary_software','0'],
      ['5','rule_dictionnary_dropdown','0'],
      ['5','budget','0'],
      ['5','notification','0'],
      ['5','rule_mailcollector','0'],
      ['6','state','0'],
      ['7','state','23'],
      ['5','calendar','0'],
      ['5','slm','0'],
      ['5','rule_dictionnary_printer','0'],
      ['5','problem','1024'],
      ['7','knowbasecategory','23'],
      ['1','itilcategory','0'],
      ['5','tickettemplate','0'],
      ['5','ticketrecurrent','0'],
      ['5','ticketcost','23'],
      ['5','change','1054'],
      ['6','change','1151'],
      ['5','ticketvalidation','3088'],
      ['6','computer','127'],
      ['6','monitor','127'],
      ['6','software','127'],
      ['6','networking','127'],
      ['6','internet','31'],
      ['6','printer','127'],
      ['6','peripheral','127'],
      ['6','cartridge','127'],
      ['6','consumable','127'],
      ['6','phone','127'],
      ['2','queuednotification','0'],
      ['6','contact_enterprise','96'],
      ['6','document','127'],
      ['6','contract','96'],
      ['6','infocom','0'],
      ['6','knowbase','14359'],
      ['6','reservation','1055'],
      ['6','reports','1'],
      ['6','dropdown','0'],
      ['6','device','0'],
      ['6','typedoc','0'],
      ['6','link','0'],
      ['6','config','0'],
      ['6','rule_ticket','0'],
      ['6','rule_import','0'],
      ['6','rule_ldap','0'],
      ['6','rule_softwarecategories','0'],
      ['6','search_config','0'],
      ['2','domain','0'],
      ['6','profile','0'],
      ['6','user','1055'],
      ['6','group','1'],
      ['6','entity','33'],
      ['6','transfer','1'],
      ['6','logs','0'],
      ['6','reminder_public','23'],
      ['6','rssfeed_public','23'],
      ['6','bookmark_public','0'],
      ['6','backup','0'],
      ['6','ticket','166919'],
      ['6','followup','13319'],
      ['6','task','13329'],
      ['1','project','0'],
      ['2','project','1025'],
      ['3','project','1151'],
      ['6','planning','1'],
      ['4','taskcategory','23'],
      ['4','netpoint','23'],
      ['6','statistic','1'],
      ['6','password_update','1'],
      ['6','show_group_hardware','0'],
      ['6','rule_dictionnary_software','0'],
      ['6','rule_dictionnary_dropdown','0'],
      ['6','budget','96'],
      ['6','notification','0'],
      ['6','rule_mailcollector','0'],
      ['4','state','23'],
      ['5','state','0'],
      ['6','calendar','0'],
      ['6','slm','1'],
      ['6','rule_dictionnary_printer','0'],
      ['6','problem','1121'],
      ['6','knowbasecategory','0'],
      ['7','itilcategory','23'],
      ['7','location','23'],
      ['6','tickettemplate','1'],
      ['6','ticketrecurrent','1'],
      ['6','ticketcost','23'],
      ['3','change','1151'],
      ['4','change','1151'],
      ['6','ticketvalidation','3088'],
      ['7','computer','127'],
      ['7','monitor','127'],
      ['7','software','127'],
      ['7','networking','127'],
      ['7','internet','31'],
      ['7','printer','127'],
      ['7','peripheral','127'],
      ['7','cartridge','127'],
      ['7','consumable','127'],
      ['7','phone','127'],
      ['1','queuednotification','0'],
      ['7','contact_enterprise','96'],
      ['7','document','127'],
      ['7','contract','96'],
      ['7','infocom','0'],
      ['7','knowbase','14359'],
      ['7','reservation','1055'],
      ['7','reports','1'],
      ['7','dropdown','0'],
      ['7','device','0'],
      ['7','typedoc','0'],
      ['7','link','0'],
      ['7','config','0'],
      ['7','rule_ticket','1047'],
      ['7','rule_import','0'],
      ['7','rule_ldap','0'],
      ['7','rule_softwarecategories','0'],
      ['7','search_config','0'],
      ['1','domain','0'],
      ['7','profile','0'],
      ['7','user','1055'],
      ['7','group','1'],
      ['7','entity','33'],
      ['7','transfer','1'],
      ['7','logs','1'],
      ['7','reminder_public','23'],
      ['7','rssfeed_public','23'],
      ['7','bookmark_public','0'],
      ['7','backup','0'],
      ['7','ticket','261151'],
      ['7','followup','15383'],
      ['7','task','13329'],
      ['7','queuednotification','0'],
      ['7','planning','3073'],
      ['3','taskcategory','23'],
      ['3','netpoint','23'],
      ['7','statistic','1'],
      ['7','password_update','1'],
      ['7','show_group_hardware','0'],
      ['7','rule_dictionnary_software','0'],
      ['7','rule_dictionnary_dropdown','0'],
      ['7','budget','96'],
      ['7','notification','0'],
      ['7','rule_mailcollector','23'],
      ['7','changevalidation','1044'],
      ['3','state','23'],
      ['7','calendar','23'],
      ['7','slm','23'],
      ['7','rule_dictionnary_printer','0'],
      ['7','problem','1151'],
      ['5','knowbasecategory','0'],
      ['6','itilcategory','0'],
      ['6','location','0'],
      ['7','tickettemplate','23'],
      ['7','ticketrecurrent','1'],
      ['7','ticketcost','23'],
      ['1','change','0'],
      ['2','change','1057'],
      ['7','ticketvalidation','15376'],
      ['8','backup','1'],
      ['8','bookmark_public','1'],
      ['8','budget','33'],
      ['8','calendar','1'],
      ['8','cartridge','33'],
      ['8','change','1057'],
      ['8','changevalidation','0'],
      ['8','computer','33'],
      ['8','config','1'],
      ['8','consumable','33'],
      ['8','contact_enterprise','33'],
      ['8','contract','33'],
      ['8','device','1'],
      ['8','document','33'],
      ['8','domain','1'],
      ['8','dropdown','1'],
      ['8','entity','33'],
      ['8','followup','8193'],
      ['8','global_validation','0'],
      ['8','group','33'],
      ['8','infocom','1'],
      ['8','internet','1'],
      ['8','itilcategory','1'],
      ['8','knowbase','10241'],
      ['8','knowbasecategory','1'],
      ['8','link','1'],
      ['8','location','1'],
      ['8','logs','1'],
      ['8','monitor','33'],
      ['8','netpoint','1'],
      ['8','networking','33'],
      ['8','notification','1'],
      ['8','password_update','0'],
      ['8','peripheral','33'],
      ['8','phone','33'],
      ['8','planning','3073'],
      ['8','printer','33'],
      ['8','problem','1057'],
      ['8','profile','1'],
      ['8','project','1057'],
      ['8','projecttask','33'],
      ['8','queuednotification','1'],
      ['8','reminder_public','1'],
      ['8','reports','1'],
      ['8','reservation','1'],
      ['8','rssfeed_public','1'],
      ['8','rule_dictionnary_dropdown','1'],
      ['8','rule_dictionnary_printer','1'],
      ['8','rule_dictionnary_software','1'],
      ['8','rule_import','1'],
      ['8','rule_ldap','1'],
      ['8','rule_mailcollector','1'],
      ['8','rule_softwarecategories','1'],
      ['8','rule_ticket','1'],
      ['8','search_config','0'],
      ['8','show_group_hardware','1'],
      ['8','slm','1'],
      ['8','software','33'],
      ['8','solutiontemplate','1'],
      ['8','state','1'],
      ['8','statistic','1'],
      ['8','task','8193'],
      ['8','taskcategory','1'],
      ['8','ticket','138241'],
      ['8','ticketcost','1'],
      ['8','ticketrecurrent','1'],
      ['8','tickettemplate','1'],
      ['8','ticketvalidation','0'],
      ['8','transfer','1'],
      ['8','typedoc','1'],
      ['8','user','1'],
      ['1','license','0'],
      ['2','license','33'],
      ['3','license','127'],
      ['4','license','255'],
      ['5','license','0'],
      ['6','license','127'],
      ['7','license','127'],
      ['8','license','33'],
      ['1','line','0'],
      ['2','line','33'],
      ['3','line','127'],
      ['4','line','255'],
      ['5','line','0'],
      ['6','line','127'],
      ['7','line','127'],
      ['8','line','33'],
      ['1','lineoperator','0'],
      ['2','lineoperator','33'],
      ['3','lineoperator','23'],
      ['4','lineoperator','23'],
      ['5','lineoperator','0'],
      ['6','lineoperator','0'],
      ['7','lineoperator','23'],
      ['8','lineoperator','1'],
      ['1','devicesimcard_pinpuk','0'],
      ['2','devicesimcard_pinpuk','1'],
      ['3','devicesimcard_pinpuk','3'],
      ['4','devicesimcard_pinpuk','3'],
      ['5','devicesimcard_pinpuk','0'],
      ['6','devicesimcard_pinpuk','3'],
      ['7','devicesimcard_pinpuk','3'],
      ['8','devicesimcard_pinpuk','1'],
      ['1','certificate','0'],
      ['2','certificate','33'],
      ['3','certificate','127'],
      ['4','certificate','255'],
      ['5','certificate','0'],
      ['6','certificate','127'],
      ['7','certificate','127'],
      ['8','certificate','33'],
      ['1','datacenter','0'],
      ['2','datacenter','1'],
      ['3','datacenter','31'],
      ['4','datacenter','31'],
      ['5','datacenter','0'],
      ['6','datacenter','31'],
      ['7','datacenter','31'],
      ['8','datacenter','1'],
      ['4','rule_asset','1047'],
      ['1','personalization','3'],
      ['2','personalization','3'],
      ['3','personalization','3'],
      ['4','personalization','3'],
      ['5','personalization','3'],
      ['6','personalization','3'],
      ['7','personalization','3'],
      ['8','personalization','3'],
      ['1','rule_asset','0'],
      ['2','rule_asset','0'],
      ['3','rule_asset','0'],
      ['5','rule_asset','0'],
      ['6','rule_asset','0'],
      ['7','rule_asset','0'],
      ['8','rule_asset','0'],
      ['1','global_validation','0'],
      ['2','global_validation','0'],
      ['3','global_validation','0'],
      ['4','global_validation','0'],
      ['5','global_validation','0'],
      ['6','global_validation','0'],
      ['7','global_validation','0'],
   ]);

   $helpdesk_item_types = "[\"Computer\",\"Monitor\",\"NetworkEquipment\",\"Peripheral\",\"Phone\",\"Printer\",\"Software\", \"DCRoom\", \"Rack\", \"Enclosure\"]";
   $DB->insertBulkOrDie('glpi_profiles', ['name', 'interface', 'is_default', 'helpdesk_hardware',
         'helpdesk_item_type', 'ticket_status', 'comment', 'problem_status', 'create_ticket_on_login',
         'tickettemplates_id', 'change_status'], [
      ['Self-Service','helpdesk','1','1',$helpdesk_item_types,'{\"1\":{\"2\":0,\"3\":0,\"4\":0,\"5\":0,\"6\":0},\"2\":{\"1\":0,\"3\":0,\"4\":0,\"5\":0,\"6\":0},\"3\":{\"1\":0,\"2\":0,\"4\":0,\"5\":0,\"6\":0},\"4\":{\"1\":0,\"2\":0,\"3\":0,\"5\":0,\"6\":0},\"5\":{\"1\":0,\"2\":0,\"3\":0,\"4\":0},\"6\":{\"1\":0,\"2\":0,\"3\":0,\"4\":0,\"5\":0}}',NULL,'[]','0','0',NULL],
      ['Observer','central','0','1',$helpdesk_item_types,'[]',null,'[]','0','0',null],
      ['Admin','central','0','3',$helpdesk_item_types,'[]',null,'[]','0','0',null],
      ['Super-Admin','central','0','3',$helpdesk_item_types,'[]',null,'[]','0','0',null],
      ['Hotliner','central','0','3',$helpdesk_item_types,'[]',null,'[]','1','0',NULL],
      ['Technician','central','0','3',$helpdesk_item_types,'[]',null,'[]','0','0',null],
      ['Supervisor','central','0','3',$helpdesk_item_types,'[]',null,'[]','0','0',null],
      ['Read-Only','central','0','0','[]','{\"1\":{\"2\":0,\"3\":0,\"4\":0,\"5\":0,\"6\":0},
                       \"2\":{\"1\":0,\"3\":0,\"4\":0,\"5\":0,\"6\":0},
                       \"3\":{\"1\":0,\"2\":0,\"4\":0,\"5\":0,\"6\":0},
                       \"4\":{\"1\":0,\"2\":0,\"3\":0,\"5\":0,\"6\":0},
                       \"5\":{\"1\":0,\"2\":0,\"3\":0,\"4\":0,\"6\":0},
                       \"6\":{\"1\":0,\"2\":0,\"3\":0,\"4\":0,\"5\":0}}','This profile defines read-only access. It is used when objects are locked. It can also be used to give to users rights to unlock objects.','{\"1\":{\"7\":0,\"2\":0,\"3\":0,\"4\":0,\"5\":0,\"8\":0,\"6\":0},
                      \"7\":{\"1\":0,\"2\":0,\"3\":0,\"4\":0,\"5\":0,\"8\":0,\"6\":0},
                      \"2\":{\"1\":0,\"7\":0,\"3\":0,\"4\":0,\"5\":0,\"8\":0,\"6\":0},
                      \"3\":{\"1\":0,\"7\":0,\"2\":0,\"4\":0,\"5\":0,\"8\":0,\"6\":0},
                      \"4\":{\"1\":0,\"7\":0,\"2\":0,\"3\":0,\"5\":0,\"8\":0,\"6\":0},
                      \"5\":{\"1\":0,\"7\":0,\"2\":0,\"3\":0,\"4\":0,\"8\":0,\"6\":0},
                      \"8\":{\"1\":0,\"7\":0,\"2\":0,\"3\":0,\"4\":0,\"5\":0,\"6\":0},
                      \"6\":{\"1\":0,\"7\":0,\"2\":0,\"3\":0,\"4\":0,\"5\":0,\"8\":0}}','0','0','{\"1\":{\"9\":0,\"10\":0,\"7\":0,\"4\":0,\"11\":0,\"12\":0,\"5\":0,\"8\":0,\"6\":0},
                       \"9\":{\"1\":0,\"10\":0,\"7\":0,\"4\":0,\"11\":0,\"12\":0,\"5\":0,\"8\":0,\"6\":0},
                       \"10\":{\"1\":0,\"9\":0,\"7\":0,\"4\":0,\"11\":0,\"12\":0,\"5\":0,\"8\":0,\"6\":0},
                       \"7\":{\"1\":0,\"9\":0,\"10\":0,\"4\":0,\"11\":0,\"12\":0,\"5\":0,\"8\":0,\"6\":0},
                       \"4\":{\"1\":0,\"9\":0,\"10\":0,\"7\":0,\"11\":0,\"12\":0,\"5\":0,\"8\":0,\"6\":0},
                       \"11\":{\"1\":0,\"9\":0,\"10\":0,\"7\":0,\"4\":0,\"12\":0,\"5\":0,\"8\":0,\"6\":0},
                       \"12\":{\"1\":0,\"9\":0,\"10\":0,\"7\":0,\"4\":0,\"11\":0,\"5\":0,\"8\":0,\"6\":0},
                       \"5\":{\"1\":0,\"9\":0,\"10\":0,\"7\":0,\"4\":0,\"11\":0,\"12\":0,\"8\":0,\"6\":0},
                       \"8\":{\"1\":0,\"9\":0,\"10\":0,\"7\":0,\"4\":0,\"11\":0,\"12\":0,\"5\":0,\"6\":0},
                       \"6\":{\"1\":0,\"9\":0,\"10\":0,\"7\":0,\"4\":0,\"11\":0,\"12\":0,\"5\":0,\"8\":0}}']
   ]);

   $DB->insertBulkOrDie('glpi_profiles_users', ['users_id', 'profiles_id'], [
      [2, 4],
      [3, 1],
      [4, 6],
      [5, 2]
   ]);

   $DB->insertBulkOrDie('glpi_projectstates', ['name', 'color', 'is_finished'], [
      ['New', '#06ff00', '0'],
      ['Processing', '#ffb800', '0'],
      ['Closed', '#ff0000', '1']
   ]);

   $DB->insertBulkOrDie('glpi_requesttypes', ['name', 'is_helpdesk_default', 'is_followup_default',
         'is_mail_default', 'is_mailfollowup_default'], [
      ['Helpdesk', '1', '1', '0', '0'],
      ['E-Mail', '0', '0', '1', '1'],
      ['Phone', '0', '0', '0', '0'],
      ['Direct', '0', '0', '0', '0'],
      ['Written', '0', '0', '0', '0'],
      ['Other', '0', '0', '0', '0']
   ]);

   $DB->insertBulkOrDie('glpi_ruleactions', ['rules_id', 'action_type', 'field', 'value'], [
      ['6','fromitem','locations_id','1'],
      ['2','assign','entities_id','0'],
      ['3','assign','entities_id','0'],
      ['4','assign','_refuse_email_no_response','1'],
      ['5','assign','_refuse_email_no_response','1'],
      ['7','fromuser','locations_id','1'],
      ['8','assign','_import_category','1'],
      ['9','regex_result','_affect_user_by_regex','#0'],
      ['10','regex_result','_affect_user_by_regex','#0'],
      ['11','regex_result','_affect_user_by_regex','#0']
   ]);

   $DB->insertBulkOrDie('glpi_rulecriterias', ['rules_id', 'criteria', 'condition', 'pattern'], [
      ['6','locations_id','9','1'],
      ['2','uid','0','*'],
      ['2','samaccountname','0','*'],
      ['2','MAIL_EMAIL','0','*'],
      ['3','subject','6','/.*/'],
      ['4','x-auto-response-suppress','6','/\\S+/'],
      ['5','auto-submitted','6','/^(?!.*no).+$/i'],
      ['6','items_locations','8','1'],
      ['7','locations_id','9','1'],
      ['7','users_locations','8','1'],
      ['8','name','0','*'],
      ['9','_itemtype','0','Computer'],
      ['9','_auto','0','1'],
      ['9','contact','6','/(.*)@/'],
      ['10','_itemtype','0','Computer'],
      ['10','_auto','0','1'],
      ['10','contact','6','/(.*),/'],
      ['11','_itemtype','0','Computer'],
      ['11','_auto','0','1'],
      ['11','contact','6','/(.*)/']
   ]);

   $DB->insertBulkOrDie('glpi_rulerightparameters', ['name', 'value', 'comment'], [
      ['(LDAP)Organization','o',''],
      ['(LDAP)Common Name','cn',''],
      ['(LDAP)Department Number','departmentnumber',''],
      ['(LDAP)Email','mail',''],
      ['Object Class','objectclass',''],
      ['(LDAP)User ID','uid',''],
      ['(LDAP)Telephone Number','phone',''],
      ['(LDAP)Employee Number','employeenumber',''],
      ['(LDAP)Manager','manager',''],
      ['(LDAP)DistinguishedName','dn',''],
      ['(AD)User ID','samaccountname',''],
      ['(LDAP) Title','title',''],
      ['(LDAP) MemberOf','memberof','']
   ]);

   $DB->insertBulkOrDie('glpi_rules', ['sub_type', 'ranking', 'name', 'description', 'match',
         'is_active', 'comment', 'is_recursive', 'uuid', 'condition'], [
      ['RuleRight','1','Root','','OR','1',null,'0','500717c8-2bd6e957-53a12b5fd35745.02608131','0'],
      ['RuleMailCollector','3','Root','','OR','1',null,'0','500717c8-2bd6e957-53a12b5fd36404.54713349','0'],
      ['RuleMailCollector','1','X-Auto-Response-Suppress','Exclude Auto-Reply emails using X-Auto-Response-Suppress header','AND','0',null,'1','500717c8-2bd6e957-53a12b5fd36d97.94503423','0'],
      ['RuleMailCollector','2','Auto-Reply Auto-Submitted','Exclude Auto-Reply emails using Auto-Submitted header','OR','1',null,'1','500717c8-2bd6e957-53a12b5fd376c2.87642651','0'],
      ['RuleTicket','1','Ticket location from item','','AND','0','Automatically generated by GLPI 0.84','1','500717c8-2bd6e957-53a12b5fd37f94.10365341','1'],
      ['RuleTicket','2','Ticket location from user','','AND','0','Automatically generated by GLPI 0.84','1','500717c8-2bd6e957-53a12b5fd38869.86002585','1'],
      ['RuleSoftwareCategory','1','Import category from inventory tool','','AND','0','Automatically generated by GLPI 9.2','1','500717c8-2bd6e957-53a12b5fd38869.86003425','1'],
      ['RuleAsset','1','Domain user assignation','','AND','1','Automatically generated by GLPI 9.3','1','fbeb1115-7a37b143-5a3a6fc1afdc17.92779763','3'],
      ['RuleAsset','2','Multiple users: assign to the first','','AND','1','Automatically generated by GLPI 9.3','1','fbeb1115-7a37b143-5a3a6fc1b03762.88595154','3'],
      ['RuleAsset','3','One user assignation','','AND','1','Automatically generated by GLPI 9.3','1','fbeb1115-7a37b143-5a3a6fc1b073e1.16257440','3']
   ]);

   $DB->insertOrDie('glpi_softwarecategories', [
      'name'                  => 'FUSION',
      'softwarecategories_id' => 0,
      'completename'          => 'FUSION',
      'level'                 => 1,
   ]);

   $DB->insertOrDie('glpi_softwarelicensetypes', [
      'name'                     => 'OEM',
      'comment'                  => '',
      'softwarelicensetypes_id'  => 0,
      'level'                    => 0,
      'is_recursive'             => 1,
      'completename'             => 'OEM',
   ]);

   $DB->insertBulkOrDie('glpi_ssovariables', ['name', 'comment'], [
      ['HTTP_AUTH_USER',''],
      ['REMOTE_USER',''],
      ['PHP_AUTH_USER',''],
      ['USERNAME',''],
      ['REDIRECT_REMOTE_USER',''],
      ['HTTP_REMOTE_USER','']
   ]);

   $DB->insertOrDie('glpi_tickettemplatemandatoryfields', [
      'tickettemplates_id' => 1,
      'num'                => 21,
   ]);

   $DB->insertOrDie('glpi_tickettemplates', [
      'name'         => 'Default',
      'is_recursive' => 1,
   ]);

   $DB->insertOrDie('glpi_transfers', [
      'name'                  => 'complete',
      'keep_ticket'           => 2,
      'keep_networklink'      => 2,
      'keep_reservation'      => 1,
      'keep_history'          => 1,
      'keep_device'           => 1,
      'keep_infocom'          => 1,
      'keep_dc_monitor'       => 1,
      'clean_dc_monitor'      => 1,
      'keep_dc_phone'         => 1,
      'clean_dc_phone'        => 1,
      'keep_dc_peripheral'    => 1,
      'clean_dc_peripheral'   => 1,
      'keep_dc_printer'       => 1,
      'clean_dc_printer'      => 1,
      'keep_supplier'         => 1,
      'clean_supplier'        => 1,
      'keep_contact'          => 1,
      'clean_contact'         => 1,
      'keep_contract'         => 1,
      'clean_contract'        => 1,
      'keep_software'         => 1,
      'clean_software'        => 1,
      'keep_document'         => 1,
      'clean_document'        => 1,
      'keep_cartridgeitem'    => 1,
      'clean_cartridgeitem'   => 1,
      'keep_cartridge'        => 1,
      'keep_consumable'       => 1,
      'keep_disk'             => 1
   ]);

   $user_lang = isset($_SESSION['glpilanguage']) ? $_SESSION['glpilanguage'] : 'en_GB';
   $DB->insertBulkOrDie('glpi_users', ['id', 'name', 'password', 'language', 'list_limit', 'authtype'], [
      [2, 'glpi', '$2y$10$rXXzbc2ShaiCldwkw4AZL.n.9QSH7c0c9XJAyyjrbL9BwmWditAYm',$user_lang, 20, 1],
      [3, 'post-only', '$2y$10$dTMar1F3ef5X/H1IjX9gYOjQWBR1K4bERGf4/oTPxFtJE/c3vXILm', $user_lang, 20, 1],
      [4, 'tech', '$2y$10$.xEgErizkp6Az0z.DHyoeOoenuh0RcsX4JapBk2JMD6VI17KtB1lO', $user_lang, 20, 1],
      [5, 'normal', '$2y$10$Z6doq4zVHkSPZFbPeXTCluN1Q/r0ryZ3ZsSJncJqkN3.8cRiN0NV.', $user_lang, 20, 1]
   ]);

   $DB->insertBulkOrDie('glpi_devicefirmwaretypes', ['name'], [['BIOS'], ['UEFI'], ['Firmware']]);
}

createTables();
insertDefaultData();
