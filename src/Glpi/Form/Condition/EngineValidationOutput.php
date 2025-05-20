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

use JsonSerializable;
use Override;

final class EngineValidationOutput implements JsonSerializable
{
    /** @var array<int, ConditionData[]> */
    private array $questions_validation = [];

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'questions_validation' => $this->questions_validation,
        ];
    }

    public function setQuestionValidation(int $question_id, array $not_met_conditions): void
    {
        $this->questions_validation[$question_id] = $not_met_conditions;
    }

    /**
     * @param int $question_id
     * @return ConditionData[]
     */
    public function getQuestionValidation(int $question_id): array
    {
        if (!isset($this->questions_validation[$question_id])) {
            return [];
        }

        return $this->questions_validation[$question_id];
    }

    public function isQuestionValid(int $question_id): bool
    {
        if (!isset($this->questions_validation[$question_id])) {
            return false;
        }

        return empty($this->questions_validation[$question_id]);
    }
}
