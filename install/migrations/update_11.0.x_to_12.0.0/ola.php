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



add_groups_id_field_in_olas($migration);
create_items_olas_table($migration, $DB);
migrate_items_olas_data($migration, $DB);
remove_olas_fields_in_tickets($migration);
update_crontask($migration, $DB);

// --- functions

function add_groups_id_field_in_olas(Migration $migration): void
{
    $migration->addField(
        'glpi_olas',
        'groups_id',
        'fkey',
        [
            'value' => '0',
            'null' => false,
            'after' => 'slms_id',
        ]
    );
    $migration->addKey('glpi_olas', 'groups_id');
}

function create_items_olas_table(Migration $migration, DBmysql $DB): void
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
        PRIMARY KEY (`id`),
        KEY `olas_id` (`olas_id`),
        KEY `item` (`itemtype`, `items_id`)
         ) ENGINE=InnoDB DEFAULT CHARSET=$charset COLLATE=$collation ROW_FORMAT=DYNAMIC;";
    $DB->doQuery($query);
}

function migrate_items_olas_data(Migration $migration, DBmysql $DB): void
{
    if (!$DB->fieldExists('glpi_tickets', 'olas_id_tto')) {
        // olas_id_tto field is removed : considere migration as done
        return;
    }

    $tickets_with_ola = $DB->request(
        [
            'FROM'  => 'glpi_tickets',
            'WHERE' => [
                'OR' => [
                    ['NOT' => ['olas_id_tto' => null]],
                    ['NOT' => ['olas_id_ttr' => null]],
                ],
            ],
        ]
    );

    foreach ($tickets_with_ola as $ticket) {
        if ($ticket['olas_id_tto'] !== 0) {
            $migration->addPostQuery(
                $DB->buildInsert(
                    'glpi_items_olas',
                    [
                        'itemtype'      => 'Ticket',
                        'items_id'      => $ticket['id'],
                        'olas_id'       => $ticket['olas_id_tto'],
                        'ola_type'      => 1,
                        'start_time'    => $ticket['ola_tto_begin_date'],
                        'due_time'      => $ticket['internal_time_to_own'],
                        'end_time'      => null,
                        'waiting_time'  => $ticket['ola_waiting_duration'],
                        'waiting_start' => null,
                        'is_late'       => false,
                    ]
                )
            );
        }

        if ($ticket['olas_id_ttr'] !== 0) {
            $migration->addPostQuery(
                $DB->buildInsert(
                    'glpi_items_olas',
                    [
                        'itemtype'      => 'Ticket',
                        'items_id'      => $ticket['id'],
                        'olas_id'       => $ticket['olas_id_ttr'],
                        'ola_type'      => 2,
                        'start_time'    => $ticket['ola_ttr_begin_date'],
                        'due_time'      => $ticket['internal_time_to_resolve'],
                        'end_time'      => null,
                        'waiting_time'  => $ticket['ola_waiting_duration'],
                        'waiting_start' => null,
                        'is_late'       => false,
                    ]
                )
            );
        }
    }
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
        $migration->dropField('glpi_tickets', $field);
    }
}

function update_crontask(Migration $migration, DBmysql $DB): void
{
    // find if cron task already exists to choose against adding it or updating it
    $crontask = $DB->request([
        'SELECT' => ['id'],
        'FROM' => 'glpi_crontasks',
        'WHERE' => [
            'name' => 'olaticket',
        ],
    ]);
    $id = $crontask->current() ? $crontask->current()['id'] : null;

    if (is_null($id)) {
        // add new crontask
        $migration->addPostQuery(
            $DB->buildInsert(
                'glpi_crontasks',
                [
                    'itemtype'      => 'Item_Ola',
                    'name'          => 'olaticket',
                    'frequency'     => 300,
                    'param'         => null,
                    'state'         => 1, // waiting
                    'mode'          => 1, // internal
                    'lastrun'       => null,
                    'logs_lifetime' => 30,
                    'hourmin'       => 0,
                    'hourmax'       => 24,
                ]
            )
        );
    } else {
        // update existing crontask
        $migration->addPostQuery(
            $DB->buildUpdate(
                'glpi_crontasks',
                ['itemtype' => 'Item_Ola'],
                ['id' => $id]
            )
        );
    }
}
