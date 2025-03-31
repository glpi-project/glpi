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
 * @var       \DBmysql $DB
 * @var       \Migration $migration
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

$migration->log('Preparing ValidationSteps migration', false);
$validation_tables = ['glpi_ticketvalidations', 'glpi_changevalidations'];
$itil_tables = ['glpi_tickets', 'glpi_changes'];

// new object : ValidationStep
create_validation_steps_table($migration);
insert_validation_steps_defaults($migration, $DB);

// new object : ITIL_ValidationStep - Association between Validations and ValidationSteps + minimal_required_validation_percent
create_itils_validationsteps_table($migration);
add_validation_steps_in_validations_tables($migration, $validation_tables);
$migration->executeMigration();
add_itils_validationstep_to_existings_itils($migration, $validation_tables);

// templates
add_approval_status_to_ticket_templates($migration);
add_validation_steps_in_itilvalidationtemplates($migration);

remove_validation_percent_on_itils($migration, $itil_tables);

$migration->executeMigration();
$migration->log('ValidationSteps migration done', false);

return;
function create_validation_steps_table(Migration $migration): void
{
    $charset = DBConnection::getDefaultCharset();
    $collation = DBConnection::getDefaultCollation();
    $pk_sign = DBConnection::getDefaultPrimaryKeySignOption();

    $query = "CREATE TABLE IF NOT EXISTS `glpi_validationsteps` (
        `id`                                  int {$pk_sign} NOT NULL AUTO_INCREMENT,
        `name`                                varchar(255)          DEFAULT NULL,
        `minimal_required_validation_percent` smallint     NOT NULL DEFAULT '100',
        `is_default`                          tinyint      NOT NULL DEFAULT '0',
        `date_mod`                            timestamp    NULL     DEFAULT NULL,
        `date_creation`                       timestamp    NULL     DEFAULT NULL,
        `comment`                             text,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=$charset COLLATE=$collation ROW_FORMAT=DYNAMIC;";

    $migration->addPreQuery($query);
    // $migration needs to be executed before adding keys
    // because addKey() ckecks if key exists, so the table must exists
    $migration->executeMigration();

    $migration->addKey('glpi_validationsteps', 'name');
    $migration->addKey('glpi_validationsteps', 'date_mod');
    $migration->addKey('glpi_validationsteps', 'date_creation');
}

function insert_validation_steps_defaults(Migration $migration, \DBmysql $DB): void
{
    if (!$DB->tableExists(ValidationStep::getTable())) {
        $migration->log('ValidationSteps table does not exist, skipping defaults insertion', true);

        return;
    }

    $table_empty = (new DbUtils())->countElementsInTable(ValidationStep::getTable()) === 0;
    if (!$table_empty) {
        $migration->log('ValidationSteps table already filled, skipping defaults insertion', true);

        return;
    }

    $defaults = ValidationStep::getDefaults();
    foreach ($defaults as $values) {
        $values = array_map([$DB, 'escape'], $values);
        $migration->addPostQuery(
            sprintf(
                'INSERT INTO `glpi_validationsteps` (`name`, `minimal_required_validation_percent`, `is_default`, `date_mod`, `date_creation`) VALUES ("%s", "%s", "%s", "%s", "%s")',
                $values['name'],
                $values['minimal_required_validation_percent'],
                $values['is_default'],
                $values['date_mod'],
                $values['date_creation']
            )
        );
    }
}

function create_itils_validationsteps_table(Migration $migration): void
{
    $charset = DBConnection::getDefaultCharset();
    $collation = DBConnection::getDefaultCollation();
    $pk_sign = DBConnection::getDefaultPrimaryKeySignOption();

    $query = "CREATE TABLE  IF NOT EXISTS `glpi_itils_validationsteps` (
        `id` int {$pk_sign} NOT NULL AUTO_INCREMENT,
        `minimal_required_validation_percent` smallint NOT NULL,
        `validationsteps_id` int unsigned NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`),
        KEY `validationsteps_id` (`validationsteps_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=$charset COLLATE=$collation ROW_FORMAT=DYNAMIC;";

    $migration->addPreQuery($query);
}


/**
 * Add `itils_validationsteps_id` column in validation tables (ticketvalidations, changesvalidations), after `id` column
 */
function add_validation_steps_in_validations_tables(Migration $migration, array $validation_tables): void
{
    $itils_validationsteps_foreign_key = ITIL_ValidationStep::getForeignKeyField();
    foreach ($validation_tables as $table) {
        $migration->addField(
            $table,
            $itils_validationsteps_foreign_key,
            'fkey',
            [
                'value' => '0',
                'null' => false,
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
    $validationsteps_foreign_key = ValidationStep::getForeignKeyField();
    $migration->addField(
        'glpi_itilvalidationtemplates',
        $validationsteps_foreign_key,
        'fkey',
        [
            'value' => '0',
            'null' => false,
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
function add_itils_validationstep_to_existings_itils(Migration $migration, array $validation_tables): void
{
    foreach ($validation_tables as $validation_table) {
        /** @var \CommonITILValidation $change_class */
        $change_class = getItemTypeForTable($validation_table);
        $itil_class = $change_class::$itemtype;
        $itil_fk = getForeignKeyFieldForItemType($itil_class);
        $default_validation_step = ValidationStep::getDefault(); // previous sql needs to be processed before
        $validations = getAllDataFromTable($validation_table, ['GROUPBY' => $itil_fk]); // TicketValidation or ChangeValidation data
        foreach ($validations as $validation) {
            // get current required percent on itil
            $itil = new $itil_class();
            $itil->getFromDB($validation[$itil_fk]);
            $required_percent = $itil->fields['validation_percent'];

            // create itils_validationsteps
            $itils_validationstep_id = $migration->insertInTable(
                ITIL_ValidationStep::getTable(),
                [
                    ValidationStep::getForeignKeyField() => $default_validation_step->getID(),
                    'minimal_required_validation_percent' => $required_percent,
                ]
            );
            // update itils validations (ticket, change) with the created itils_validationsteps
            $update_validation_query = 'UPDATE ' . $validation_table . ' SET ' . ITIL_ValidationStep::getForeignKeyField() . ' = ' . $itils_validationstep_id . ' WHERE ' . $itil_fk . ' = ' . $validation[$itil_fk];
            $migration->addPostQuery($update_validation_query);
        }
    }
}

function remove_validation_percent_on_itils(Migration $migration, array $itil_tables): void
{
    foreach ($itil_tables as $table) {
        $migration->dropField($table, 'validation_percent');
    }
}
