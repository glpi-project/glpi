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

    return $tables;
}

$migration = new Migration(GLPI_VERSION);
return getTables($migration);
