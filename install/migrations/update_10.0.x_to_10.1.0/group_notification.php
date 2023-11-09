<?php

/**
 * @var DB $DB
 * @var Migration $migration
 */

$table = 'glpi_groups_tickets';

$field_to_add = 'use_notification';
if (!$DB->fieldExists($table, $field_to_add)) {
    $migration->addField(
        $table,
        $field_to_add,
        'tinyint',
        [
            'value' => 0,
        ]
    );
    $migration->addKey($table, $field_to_add);
}
$table = 'glpi_changes_groups';

$field_to_add = 'use_notification';
if (!$DB->fieldExists($table, $field_to_add)) {
    $migration->addField(
        $table,
        $field_to_add,
        'tinyint',
        [
            'value' => 0,
        ]
    );
    $migration->addKey($table, $field_to_add);
}
$table = 'glpi_groups_problems';

$field_to_add = 'use_notification';
if (!$DB->fieldExists($table, $field_to_add)) {
    $migration->addField(
        $table,
        $field_to_add,
        'tinyint',
        [
            'value' => 0,
        ]
    );
    $migration->addKey($table, $field_to_add);
}
