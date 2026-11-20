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

// Changing a question type used to keep the extra data of the previous type.
// These types have no settings, so any extra data stored on them is stale and
// makes the question fail validation when its form is cloned.
$migration->addPostQuery(
    $DB->buildUpdate(
        'glpi_forms_questions',
        ['extra_data' => null],
        [
            'type' => [
                'Glpi\\Form\\QuestionType\\QuestionTypeEmail',
                'Glpi\\Form\\QuestionType\\QuestionTypeFile',
                'Glpi\\Form\\QuestionType\\QuestionTypeLongText',
                'Glpi\\Form\\QuestionType\\QuestionTypeNumber',
                'Glpi\\Form\\QuestionType\\QuestionTypeRequestType',
                'Glpi\\Form\\QuestionType\\QuestionTypeShortText',
                'Glpi\\Form\\QuestionType\\QuestionTypeUrgency',
            ],
            'extra_data' => ['<>', ''],
        ]
    )
);
