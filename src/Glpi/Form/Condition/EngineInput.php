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

use Glpi\Form\Form;
use Glpi\Form\Question;

final class EngineInput
{
    public function __construct(
        private array $answers,
    ) {}

    /**
     * Construct an input using default values from the database.
     * Useful when computing data that was not yet modified by the user.
     */
    public static function fromForm(Form $form): self
    {
        $answers = [];

        // Get questions that can be used as a criteria
        $questions = array_filter(
            $form->getQuestions(),
            fn(Question $q): bool => $q->getQuestionType() instanceof UsedAsCriteriaInterface,
        );

        foreach ($questions as $question) {
            $answers[$question->getID()] = $question->fields['default_value'];
        }

        return new self(answers: $answers);
    }

    public function getAnswers(): array
    {
        return $this->answers;
    }
}
