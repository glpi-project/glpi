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

namespace Glpi\Form\Condition;

use JsonException;

trait ConditionableTrait
{
    /**
     * Get the field name used for conditions
     * Can be overridden in the class using this trait
     *
     * @return string
     */
    protected function getConditionsFieldName(): string
    {
        return 'conditions';
    }

    /** @return ConditionData[] */
    public function getConfiguredConditionsData(): array
    {
        return $this->getConditionsData($this->getConditionsFieldName());
    }

    /** @return ConditionData[] */
    private function getConditionsData(string $field_name): array
    {
        parent::post_getFromDB();

        try {
            $raw_data = json_decode(
                json       : $this->fields[$field_name] ?? '{}',
                associative: true,
                flags      : JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $e) {
            $raw_data = [];
        }

        $form_data = new FormData([
            'conditions' => $raw_data,
        ]);

        // Filter out invalid conditions
        $conditions = array_filter(
            $form_data->getConditionsData(),
            fn(ConditionData $condition) => $condition->isValid()
        );

        return $conditions;
    }
}
