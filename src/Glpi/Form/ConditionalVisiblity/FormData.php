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

namespace Glpi\Form\ConditionalVisiblity;

use Glpi\Form\QuestionType\QuestionTypeInterface;
use ReflectionClass;

final class FormData
{
    /** @var QuestionData[] $questions_data */
    private array $questions_data = [];

    /** @var ConditionData[] $conditions_data */
    private array $conditions_data = [];

    private string $selected_item_uuid;
    private string $selected_item_type;

    public function __construct(
        array $raw_data
    ) {
        $this->parseRawQuestionsData($raw_data['questions'] ?? []);
        $this->parseRawConditionsData($raw_data['conditions'] ?? []);
        $this->selected_item_uuid = $raw_data['selected_item_uuid'] ?? '';
        $this->selected_item_type = $raw_data['selected_item_type'] ?? '';
    }

    /** @return QuestionData[] */
    public function getQuestionsData(): array
    {
        return $this->questions_data;
    }

    /** @return ConditionData[] */
    public function getConditionsData(): array
    {
        return $this->conditions_data;
    }

    public function getSelectedItemUuid(): string
    {
        return $this->selected_item_uuid;
    }

    public function getSelectedItemType(): string
    {
        return $this->selected_item_type;
    }

    private function parseRawQuestionsData(array $questions_data): void
    {
        foreach ($questions_data as $question_data) {
            $type = $question_data['type'] ?? null;
            if (
                !is_a($type, QuestionTypeInterface::class, true)
                || (new ReflectionClass($type))->isAbstract()
            ) {
                continue;
            }

            $this->questions_data[] = new QuestionData(
                uuid: $question_data['uuid'],
                name: $question_data['name'],
                type: new $type(),
            );
        }
    }

    private function parseRawConditionsData(array $conditions_data): void
    {
        foreach ($conditions_data as $condition_data) {
            $item_key = $condition_data['item'];

            if ($item_key == '') {
                // Item has not yet been selected.
                $type = '';
                $uuid = '';
            } else {
                // Item has been selected, extract type and uuid.
                $item_parts = explode('-', $item_key);
                $type = array_shift($item_parts);
                $uuid = implode('-', $item_parts);
            }

            $this->conditions_data[] = new ConditionData(
                item_uuid     : $uuid,
                item_type     : $type,
                value_operator: $condition_data['value_operator'] ?? null,
                value         : $condition_data['value'] ?? null,
                logic_operator: $condition_data['logic_operator'] ?? null,
            );
        }
    }
}
