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

use function Safe\json_decode;
use function Safe\json_encode;

/**
 * @var DBmysql $DB
 * @var Migration $migration
 */

// Migrate glpi_forms_questions:
//   - default_value: {"items_id": X} → {"items_ids": [X]}
//   - conditions / validation_conditions: condition value {"itemtype":X,"items_id":Y} → {"itemtype":X,"items_ids":[Y]}
// Applies to both QuestionTypeItem and its subclass QuestionTypeItemDropdown.
$item_question_types = [
    'Glpi\\Form\\QuestionType\\QuestionTypeItem',
    'Glpi\\Form\\QuestionType\\QuestionTypeItemDropdown',
];

/**
 * Migrate the legacy condition value format ({"itemtype":X,"items_id":Y}) to the
 * new one ({"itemtype":X,"items_ids":[Y]}) inside the given JSON conditions column.
 *
 * @return ?string The re-encoded conditions when an update is needed, null otherwise.
 */
$migrate_conditions_column = static function (?string $raw_conditions): ?string {
    if ($raw_conditions === null) {
        return null;
    }

    $conditions = json_decode($raw_conditions, true) ?? [];
    $updated    = false;
    foreach ($conditions as &$condition) {
        $value = $condition['value'] ?? null;
        if (
            is_array($value)
            && isset($value['items_id'])
            && !isset($value['items_ids'])
        ) {
            $value['items_ids'] = [(int) $value['items_id']];
            unset($value['items_id']);
            $condition['value'] = $value;
            $updated = true;
        }
    }
    unset($condition);

    return $updated ? json_encode($conditions) : null;
};

// Only JSON encoded values still holding the legacy `items_id` key need to be migrated.
// This marker is used to filter out, at the SQL level, rows that do not need any
// processing, to avoid loading the whole tables in memory.
$legacy_key_marker = ['LIKE', '%"items_id"%'];

$last_forms_questions_id = 0;

do {
    $iterator = $DB->request([
        'SELECT' => ['id', 'default_value', 'conditions', 'validation_conditions'],
        'FROM' => 'glpi_forms_questions',
        'WHERE' => [
            'id'   => ['>', $last_forms_questions_id],
            'type' => $item_question_types,
            'OR'   => [
                'default_value'         => $legacy_key_marker,
                'conditions'             => $legacy_key_marker,
                'validation_conditions'  => $legacy_key_marker,
            ],
        ],
        'ORDER' => 'id ASC',
        'LIMIT' => 250,
    ]);

    foreach ($iterator as $row) {
        $last_forms_questions_id = $row['id'];
        $updated = false;
        $data    = [];

        // Migrate default_value
        $default_value = $row['default_value'] ?? null;
        if ($default_value !== null) {
            $decoded = json_decode($default_value, true) ?? [];
            if (isset($decoded['items_id']) && !isset($decoded['items_ids'])) {
                $decoded['items_ids'] = [(int) $decoded['items_id']];
                unset($decoded['items_id']);
                $data['default_value'] = json_encode($decoded);
                $updated = true;
            }
        }

        // Migrate conditions columns
        foreach (['conditions', 'validation_conditions'] as $column) {
            if (!isset($row[$column])) {
                continue;
            }

            $migrated = $migrate_conditions_column($row[$column]);
            if ($migrated !== null) {
                $data[$column] = $migrated;
                $updated = true;
            }
        }

        if ($updated) {
            $DB->update('glpi_forms_questions', $data, ['id' => $row['id']]);
        }
    }
} while ($iterator->count() > 0);

// Migrate conditions that may reference an item question in the other form tables:
//   - glpi_forms_forms.submit_button_conditions
//   - glpi_forms_sections.conditions
//   - glpi_forms_comments.conditions
//   - glpi_forms_destinations_formdestinations.conditions
$conditions_tables = [
    'glpi_forms_forms'                          => 'submit_button_conditions',
    'glpi_forms_sections'                       => 'conditions',
    'glpi_forms_comments'                       => 'conditions',
    'glpi_forms_destinations_formdestinations'  => 'conditions',
];

foreach ($conditions_tables as $table => $column) {
    $last_id = 0;
    do {
        $iterator = $DB->request([
            'SELECT' => ['id', $column],
            'FROM' => $table,
            'WHERE' => [
                'id'     => ['>', $last_id],
                $column  => $legacy_key_marker,
            ],
            'ORDER' => 'id ASC',
            'LIMIT' => 250,
        ]);
        foreach ($iterator as $row) {
            $last_id = $row['id'];

            if (!isset($row[$column])) {
                continue;
            }

            $migrated = $migrate_conditions_column($row[$column]);
            if ($migrated !== null) {
                $DB->update($table, [$column => $migrated], ['id' => $row['id']]);
            }
        }
    } while ($iterator->count() > 0);
}

// Migrate glpi_forms_answerssets:
//   - answers: raw_answer {"itemtype":X,"items_id":Y} → {"itemtype":X,"items_ids":[Y]}
//     for answers whose raw_question_type is QuestionTypeItem or QuestionTypeItemDropdown

$last_answerssets_id = 0;
do {
    $iterator = $DB->request([
        'SELECT' => ['id', 'answers'],
        'FROM' => 'glpi_forms_answerssets',
        'WHERE' => [
            'id'      => ['>', $last_answerssets_id],
            'answers' => $legacy_key_marker,
        ],
        'ORDER' => 'id ASC',
        'LIMIT' => 250,
    ]);

    foreach ($iterator as $row) {
        $last_answerssets_id = $row['id'];
        $answers = json_decode($row['answers'], true) ?? [];
        $updated = false;

        foreach ($answers as &$answer) {
            if (!in_array($answer['raw_question_type'] ?? null, $item_question_types, true)) {
                continue;
            }

            $raw = $answer['raw_answer'] ?? null;
            if (
                is_array($raw)
                && isset($raw['items_id'])
                && !isset($raw['items_ids'])
            ) {
                $raw['items_ids'] = [(int) $raw['items_id']];
                unset($raw['items_id']);
                $answer['raw_answer'] = $raw;
                $updated = true;
            }
        }
        unset($answer);

        if ($updated) {
            $DB->update(
                'glpi_forms_answerssets',
                ['answers' => json_encode($answers)],
                ['id' => $row['id']]
            );
        }
    }
} while ($iterator->count() > 0);
