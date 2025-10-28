<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 * @var       DBmysql $DB
 * @var       Migration $migration
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

use function Safe\json_encode;

$migration->log('Group managed olas', false);

add_groups_id_field_in_olas($migration);
create_items_olas_table($migration);
migrate_items_olas_data($migration);
remove_olas_fields_in_tickets($migration);
update_crontask($migration, $DB);

$migration->executeMigration();
return;

// --- functions

function add_groups_id_field_in_olas(Migration $migration): void
{
    $migration->addField(
        OLA::getTable(),
        Group::getForeignKeyField(),
        'fkey',
        [
            'value' => '0',
            'null' => false,
            'after' => 'slms_id',
        ]
    );

    // addKey requires the table to exist -> execute migration before
    $migration->executeMigration();
    $migration->addKey(OLA::getTable(), Group::getForeignKeyField());
}

function remove_olas_fields_in_tickets(Migration $migration): void
{
    $fields_to_remove = [
        'ola_waiting_duration',
        'olas_id_tto',
        'olas_id_ttr',
        'olalevels_id_ttr',
        'ola_tto_begin_date',
        'ola_ttr_begin_date',
        'internal_time_to_resolve',
        'internal_time_to_own',
    ];

    foreach ($fields_to_remove as $field) {
        $migration->dropField(Ticket::getTable(), $field);
    }
}
function create_items_olas_table(Migration $migration): void
{
    $charset = DBConnection::getDefaultCharset();
    $collation = DBConnection::getDefaultCollation();
    $pk_sign = DBConnection::getDefaultPrimaryKeySignOption();

    $query = "CREATE TABLE IF NOT EXISTS `glpi_items_olas` (
        `id`            int {$pk_sign} NOT NULL AUTO_INCREMENT,
        `itemtype`      varchar(255) NOT NULL,
        `items_id`      int unsigned NOT NULL,
        `olas_id`       int unsigned NOT NULL,
        `ola_type`      tinyint NOT NULL, -- 1: TTO, 2: TTR
        `start_time`    timestamp NULL DEFAULT NULL,
        `due_time`      timestamp NULL DEFAULT NULL,
        `end_time`      timestamp NULL DEFAULT NULL,
        `waiting_time`  int NOT NULL DEFAULT 0,
        `waiting_start` timestamp,
        `is_late`       tinyint NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`)
         ) ENGINE=InnoDB DEFAULT CHARSET=$charset COLLATE=$collation ROW_FORMAT=DYNAMIC;";
    $migration->addPreQuery($query);
    $migration->executeMigration();

    $migration->addKey('glpi_items_olas', 'olas_id');
    $migration->addKey('glpi_items_olas', ['itemtype', 'items_id'], 'item');
}

function migrate_items_olas_data(Migration $migration): void
{
    $_ticket = new Ticket();
    if (!$_ticket->isField('olas_id_tto')) {
        // olas_id_tto field is removed : considere migration as done
        return;
    }
    $tickets_with_ola = $_ticket->find(['OR'
        => [
            ['NOT' => ['olas_id_tto' => null]],
            ['NOT' => ['olas_id_ttr' => null]],
        ]]);

    foreach ($tickets_with_ola as $ticket) {
        if ($ticket['olas_id_tto'] !== 0) {
            $io = new Item_Ola();

            $_data = [
                'itemtype' => Ticket::class,
                'items_id' => $ticket['id'],
                'olas_id' => $ticket['olas_id_tto'],
                'start_time' => $ticket['ola_tto_begin_date'],
                'due_time' => $ticket['internal_time_to_own'],
                'waiting_time' => $ticket['ola_waiting_duration'],
                'waiting_start' => null,
            ];

            if (!$io->add($_data)) {
                throw new Exception('Failed to migrate OLA TTO data: ' . json_encode($_data));
            }
        }

        if ($ticket['olas_id_ttr'] !== 0) {
            $io = new Item_Ola();

            $_data = [
                'itemtype' => Ticket::class,
                'items_id' => $ticket['id'],
                'olas_id' => $ticket['olas_id_ttr'],
                'start_time' => $ticket['ola_ttr_begin_date'],
                'due_time' => $ticket['internal_time_to_resolve'],
                'waiting_time' => $ticket['ola_waiting_duration'],
            ];

            if (!$io->add($_data)) {
                throw new Exception('Failed to migrato OLA TTO data: ' . json_encode($_data));
            }
        }
    }
}

function update_crontask(Migration $migration, DBmysql $DB): void
{
    // find if cron task already exists to choose against adding it or updating it
    $crontask = $DB->request([
        'SELECT' => ['id'],
        'FROM' => CronTask::getTable(),
        'WHERE' => [
            'name' => 'olaticket',
        ],
    ]);
    $id = $crontask->current() ? $crontask->current()['id'] : null;

    if (is_null($id)) {
        // add new crontask
        $migration->insertInTable(
            'glpi_crontasks',
            [
                'itemtype' => 'Item_Ola',
                'name' => 'olaticket',
                'frequency' => 5 * MINUTE_TIMESTAMP,
                'param' => null,
                'state' => CronTask::STATE_WAITING,
                'mode' => CronTask::MODE_INTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
                'hourmin' => 0,
                'hourmax' => 24,
            ]
        );
    } else {
        // update existing crontask
        if (false === $DB->doQuery($DB->buildUpdate('glpi_crontasks', ['itemtype' => 'Item_Ola'], ['id' => $id]))) {
            throw new Exception('Failed to update crontask itemtype');
        }
    }
}
