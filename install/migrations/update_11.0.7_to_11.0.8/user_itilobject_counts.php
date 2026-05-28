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
$default_charset = DBConnection::getDefaultCharset();
$default_collation = DBConnection::getDefaultCollation();
$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

if (!$DB->tableExists('glpi_users_itilobject_counts')) {
    $query = "CREATE TABLE `glpi_users_itilobject_counts` (
        `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
        `users_id` int {$default_key_sign} NOT NULL DEFAULT '0',
        `itemtype` varchar(100) NOT NULL,
        `actor_type` tinyint NOT NULL DEFAULT '0',
        `count` int NOT NULL DEFAULT '0',
        `date_creation` timestamp NULL DEFAULT NULL,
        `date_mod` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unicity` (`users_id`, `itemtype`, `actor_type`),
        KEY `itemtype_actor_type_count` (`itemtype`, `actor_type`, `count`),
        KEY `date_creation` (`date_creation`),
        KEY `date_mod` (`date_mod`)
      ) ENGINE=InnoDB DEFAULT CHARSET = {$default_charset} COLLATE = {$default_collation} ROW_FORMAT=DYNAMIC";
    $DB->doQuery($query);
}

// Keep migration idempotent for instances where table was created
// by a previous revision without date_creation.
$has_schema_updates = false;
if (!$DB->fieldExists('glpi_users_itilobject_counts', 'date_creation')) {
    $has_schema_updates = $migration->addField(
        'glpi_users_itilobject_counts',
        'date_creation',
        "timestamp NULL DEFAULT NULL",
        ['after' => 'count']
    ) || $has_schema_updates;
}
if (!isIndex('glpi_users_itilobject_counts', 'date_creation')) {
    $migration->addKey('glpi_users_itilobject_counts', 'date_creation');
    $has_schema_updates = true;
}
if ($has_schema_updates) {
    $migration->migrationOneTable('glpi_users_itilobject_counts');
}

$DB->delete('glpi_users_itilobject_counts', ['id' => ['>', 0]]);

$counts_queries = [
    [
        'itemtype'      => Ticket::class,
        'relation'      => Ticket_User::getTable(),
        'relation_fk'   => Ticket_User::getItilObjectForeignKey(),
        'itil_table'    => Ticket::getTable(),
    ],
    [
        'itemtype'      => Problem::class,
        'relation'      => Problem_User::getTable(),
        'relation_fk'   => Problem_User::getItilObjectForeignKey(),
        'itil_table'    => Problem::getTable(),
    ],
    [
        'itemtype'      => Change::class,
        'relation'      => Change_User::getTable(),
        'relation_fk'   => Change_User::getItilObjectForeignKey(),
        'itil_table'    => Change::getTable(),
    ],
];

foreach ($counts_queries as $query_data) {
    $itemtype = $DB->escape($query_data['itemtype']);
    $relation = DBmysql::quoteName($query_data['relation']);
    if ($query_data['relation_fk'] === null) {
        continue;
    }
    $relation_fk = DBmysql::quoteName($query_data['relation_fk']);
    $itil_table = DBmysql::quoteName($query_data['itil_table']);

    $query = "INSERT INTO `glpi_users_itilobject_counts`
        (`users_id`, `itemtype`, `actor_type`, `count`, `date_creation`, `date_mod`)
        SELECT
            {$relation}.`users_id`,
            '{$itemtype}',
            {$relation}.`type`,
            COUNT(DISTINCT {$relation}.{$relation_fk}) AS `count`,
            NOW(),
            NOW()
        FROM {$relation}
        INNER JOIN {$itil_table}
            ON {$itil_table}.`id` = {$relation}.{$relation_fk}
        WHERE {$relation}.`users_id` > 0
            AND {$itil_table}.`is_deleted` = 0
        GROUP BY {$relation}.`users_id`, {$relation}.`type`";
    $DB->doQuery($query);
}
