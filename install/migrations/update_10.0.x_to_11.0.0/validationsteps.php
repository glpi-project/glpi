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

use Glpi\DBAL\QuerySubQuery;

/**
 * @var DBmysql $DB
 * @var Migration $migration
 **/
$validation_tables = ['glpi_ticketvalidations', 'glpi_changevalidations'];
$itil_tables = ['glpi_tickets', 'glpi_changes'];

// new object : ValidationStep
create_validation_steps_table($migration, $DB);
insert_validation_steps_defaults($migration, $DB);

// new object : ITIL_ValidationStep - Association between Validations and ValidationSteps + minimal_required_validation_percent
create_itils_validationsteps_table($migration, $DB);
add_validation_steps_in_validations_tables($migration, $validation_tables);
add_itils_validationstep_to_existings_itils($migration, $DB, $validation_tables);

// templates
add_approval_status_to_ticket_templates($migration);
add_validation_steps_in_itilvalidationtemplates($migration);

remove_validation_percent_on_itils($migration, $itil_tables);

migrate_validation_rules($DB, $migration);

return;

function create_validation_steps_table(Migration $migration, DBmysql $DB): void
{
    if ($DB->tableExists('glpi_validationsteps')) {
        return;
    }

    $charset = DBConnection::getDefaultCharset();
    $collation = DBConnection::getDefaultCollation();
    $pk_sign = DBConnection::getDefaultPrimaryKeySignOption();

    $DB->doQuery("CREATE TABLE IF NOT EXISTS `glpi_validationsteps` (
        `id`                                  int {$pk_sign}     NOT NULL AUTO_INCREMENT,
        `name`                                varchar(255)       DEFAULT NULL,
        `minimal_required_validation_percent` tinyint unsigned   NOT NULL DEFAULT '100',
        `is_default`                          tinyint            NOT NULL DEFAULT '0',
        `date_mod`                            timestamp          NULL DEFAULT NULL,
        `date_creation`                       timestamp          NULL DEFAULT NULL,
        `comment`                             text,
        PRIMARY KEY (`id`),
        KEY `name` (`name`),
        KEY `date_mod` (`date_mod`),
        KEY `date_creation` (`date_creation`)
    ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation} ROW_FORMAT=DYNAMIC;");
}

function insert_validation_steps_defaults(Migration $migration, DBmysql $DB): void
{
    if (countElementsInTable('glpi_validationsteps') > 0) {
        return;
    }

    $migration->insertInTable(
        'glpi_validationsteps',
        [
            'id' => 1,
            'name' => _n('Approval', 'Approvals', 1),
            'minimal_required_validation_percent' => 100,
            'is_default' => 1,
            'date_creation' => date('Y-m-d H:i:s'),
            'date_mod' => date('Y-m-d H:i:s'),
            'comment' => '',
        ]
    );
}

function create_itils_validationsteps_table(Migration $migration, DBmysql $DB): void
{
    if ($DB->tableExists('glpi_itils_validationsteps')) {
        return;
    }

    $charset = DBConnection::getDefaultCharset();
    $collation = DBConnection::getDefaultCollation();
    $pk_sign = DBConnection::getDefaultPrimaryKeySignOption();

    $DB->doQuery("CREATE TABLE `glpi_itils_validationsteps` (
        `id`                                    int {$pk_sign}        NOT NULL AUTO_INCREMENT,
        `minimal_required_validation_percent`   tinyint unsigned      NOT NULL,
        `validationsteps_id`                    int {$pk_sign}        NOT NULL DEFAULT '0',
        `itemtype`                              varchar(255)          NOT NULL,
        `items_id`                              int {$pk_sign}        NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`),
        UNIQUE KEY `unicity` (`itemtype`,`items_id`,`validationsteps_id`),
        KEY `validationsteps_id` (`validationsteps_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=$charset COLLATE=$collation ROW_FORMAT=DYNAMIC;");
}


/**
 * Add `itils_validationsteps_id` column in validation tables (ticketvalidations, changesvalidations), after `id` column
 */
function add_validation_steps_in_validations_tables(Migration $migration, array $validation_tables): void
{
    $itils_validationsteps_foreign_key = 'itils_validationsteps_id';
    foreach ($validation_tables as $table) {
        $migration->addField(
            $table,
            $itils_validationsteps_foreign_key,
            'fkey',
            [
                'after' => 'id',
            ]
        );
        $migration->addKey($table, $itils_validationsteps_foreign_key);
    }
}


/**
 * Add "Approval' state to tickets status
 * Not needed for changes templates, already have that value
 */
function add_approval_status_to_ticket_templates(Migration $migration): void
{
    $migration->changeField(
        'glpi_tickettemplates',
        'allowed_statuses',
        'allowed_statuses',
        'string',
        [
            'value' => '[1,10,2,3,4,5,6]',
            'null' => false,
            'after' => 'comment',
        ]
    );
}

/**
 * Validation templates have a validation step field (Changes & Tickets)
 */
function add_validation_steps_in_itilvalidationtemplates(Migration $migration): void
{
    $validationsteps_foreign_key = 'validationsteps_id';
    $migration->addField(
        'glpi_itilvalidationtemplates',
        $validationsteps_foreign_key,
        'fkey',
        [
            'after' => 'is_recursive',
        ]
    );
    $migration->addKey('glpi_itilvalidationtemplates', $validationsteps_foreign_key);
}

/**
 * Add validation step to existing validations
 *
 * Create an ITIL_ValidationStep for each validation (but only one per itil)
 */
function add_itils_validationstep_to_existings_itils(Migration $migration, DBmysql $DB, array $validation_tables): void
{
    foreach ($validation_tables as $validation_table) {
        $itil_class = match ($validation_table) {
            'glpi_ticketvalidations' => 'Ticket',
            'glpi_changevalidations' => 'Change',
            default => throw new RuntimeException('Unexpected validation table: ' . $validation_table),
        };
        $itil_table = match ($itil_class) {
            'Ticket' => 'glpi_tickets',
            'Change' => 'glpi_changes',
        };
        $itil_fk = match ($itil_class) {
            'Ticket' => 'tickets_id',
            'Change' => 'changes_id',
        };

        if (!$DB->fieldExists($itil_table, 'validation_percent')) {
            // The migration has already been done.
            continue;
        }

        $default_validation_step_id = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => 'glpi_validationsteps',
            'WHERE'  => ['is_default' => 1],
        ])->current()['id'];

        // Get itil objects having at least one validation
        $itils_iterator = $DB->request([
            'SELECT' => ['id', 'validation_percent'],
            'FROM'   => $itil_table,
            'WHERE'  => [
                'id' => new QuerySubQuery([
                    'SELECT' => $itil_fk,
                    'FROM'   => $validation_table,
                ]),
            ],
        ]);
        foreach ($itils_iterator as $itil) {
            // create itils_validationsteps
            $itils_validationstep_id = $migration->insertInTable(
                'glpi_itils_validationsteps',
                [
                    'itemtype' => $itil_class,
                    'items_id' => $itil['id'],
                    'validationsteps_id' => $default_validation_step_id,
                    'minimal_required_validation_percent' => $itil['validation_percent'],
                ]
            );

            // update itils validations (ticket, change) with the created itils_validationsteps
            $update_validation_query = $DB->buildUpdate(
                $validation_table,
                [
                    'itils_validationsteps_id' => $itils_validationstep_id,
                ],
                [
                    $itil_fk => $itil['id'],
                ]
            );
            $migration->addPostQuery($update_validation_query);
        }
    }
}

function remove_validation_percent_on_itils(Migration $migration, array $itil_tables): void
{
    foreach ($itil_tables as $table) {
        $migration->dropField($table, 'validation_percent');

        $itil_class = match ($table) {
            'glpi_tickets' => 'Ticket',
            'glpi_changes' => 'Change',
            default => throw new RuntimeException('Unexpected ITIL table: ' . $table),
        };
        $migration->removeSearchOption($itil_class, 51); // 51 = validation_percent
    }
}

/**
 * Migrate the validation rules.
 */
function migrate_validation_rules(DBmysql $DB, Migration $migration): void
{
    // Validation threshold now applies to a specific step
    $migration->addPostQuery(
        $DB->buildUpdate(
            'glpi_ruleactions',
            ['action_type' => 'validationsteps_threshold'],
            ['action_type' => 'validation_percent']
        )
    );

    // Drop `global_validation` assignment action, not supported anymore
    $migration->addPostQuery(
        $DB->buildDelete(
            'glpi_ruleactions',
            ['action_type' => 'global_validation']
        )
    );
}
