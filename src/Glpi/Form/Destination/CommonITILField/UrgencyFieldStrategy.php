<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

namespace Glpi\Form\Destination\CommonITILField;

use Glpi\Form\AnswersSet;
use Glpi\Form\QuestionType\QuestionTypeUrgency;

enum UrgencyFieldStrategy: string
{
    case FROM_TEMPLATE = 'from_template';
    case SPECIFIC_VALUE = 'specific_value';
    case SPECIFIC_ANSWER = 'specific_answer';
    case LAST_VALID_ANSWER = 'last_valid_answer';

    public function getLabel(): string
    {
        return match ($this) {
            self::FROM_TEMPLATE     => __("From template"),
            self::SPECIFIC_VALUE    => __("Specific urgency"),
            self::SPECIFIC_ANSWER   => __("Answer from a specific question"),
            self::LAST_VALID_ANSWER => __('Answer to last "Urgency" question'),
        };
    }

    public function computeUrgency(
        UrgencyFieldConfig $config,
        AnswersSet $answers_set,
    ): ?int {
        return match ($this) {
            self::FROM_TEMPLATE => null, // Let the template apply its default value
            self::SPECIFIC_VALUE => $config->getSpecificUrgency(),
            self::SPECIFIC_ANSWER => $this->getUrgencyFromSpecificAnswer(
                $config->getSpecificQuestionId(),
                $answers_set
            ),
            self::LAST_VALID_ANSWER => $this->getUrgencyFromLastValidAnswer($answers_set),
        };
    }

    private function getUrgencyFromSpecificAnswer(
        ?int $question_id,
        AnswersSet $answers_set,
    ): ?int {
        if ($question_id === null) {
            return null;
        }

        $answer = $answers_set->getAnswerByQuestionId($question_id);
        if ($answer === null) {
            return null;
        }

        $value = $answer->getRawAnswer();
        if (!is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    private function getUrgencyFromLastValidAnswer(
        AnswersSet $answers_set,
    ): ?int {
        $valid_answers = $answers_set->getAnswersByType(
            QuestionTypeUrgency::class
        );

        if (count($valid_answers) == 0) {
            return null;
        }

        $answer = end($valid_answers);
        $value = $answer->getRawAnswer();
        if (!is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }
}
