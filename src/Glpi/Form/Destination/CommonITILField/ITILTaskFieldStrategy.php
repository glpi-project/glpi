<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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
use Glpi\Form\QuestionType\QuestionTypeItemDropdown;
use TaskTemplate;

enum ITILTaskFieldStrategy: string
{
    case NO_TASK           = 'no_task';
    case SPECIFIC_VALUES   = 'specific_values';
    case SPECIFIC_ANSWERS  = 'specific_answers';
    case LAST_VALID_ANSWER = 'last_valid_answer';
    case ALL_VALID_ANSWERS = 'all_valid_answers';

    public function getLabel(): string
    {
        return match ($this) {
            self::NO_TASK           => __("No Task"),
            self::SPECIFIC_VALUES   => __("Specific Task templates"),
            self::SPECIFIC_ANSWERS  => __("Answer from specific questions"),
            self::LAST_VALID_ANSWER => __('Answer to last "Task templates" question'),
            self::ALL_VALID_ANSWERS => __('All answers to "Task templates" questions'),
        };
    }

    public function getTaskTemplatesIDs(
        ITILTaskFieldConfig $config,
        AnswersSet $answers_set,
    ): ?array {
        return match ($this) {
            self::NO_TASK          => null,
            self::SPECIFIC_VALUES  => $config->getSpecificTaskTemplatesIds(),
            self::SPECIFIC_ANSWERS => $this->getTaskTemplatesForSpecificAnswers(
                $config->getSpecificQuestionIds(),
                $answers_set
            ),
            self::LAST_VALID_ANSWER => $this->getTaskTemplatesForLastValidAnswer($answers_set),
            self::ALL_VALID_ANSWERS => $this->getTaskTemplatesForAllValidAnswers($answers_set),
        };
    }

    private function isValidAnswer(array $value): bool
    {
        if (
            !isset($value['itemtype']) || !is_string($value['itemtype'])
            || !isset($value['items_id']) || !is_numeric($value['items_id'])
        ) {
            return false;
        }

        if ($value['itemtype'] !== TaskTemplate::class) {
            return true;
        }

        if (!(new TaskTemplate())->getFromDB($value['items_id'])) {
            return false;
        }

        return true;
    }

    private function getTaskTemplatesForSpecificAnswers(
        ?array $question_ids,
        AnswersSet $answers_set,
    ): ?array {
        if (empty($question_ids)) {
            return null;
        }

        $itilfollowuptemplates_ids = array_map(
            fn($question_id) => $this->getTaskTemplatesForSpecificAnswer(
                $question_id,
                $answers_set
            ),
            $question_ids
        );

        return array_filter($itilfollowuptemplates_ids);
    }

    private function getTaskTemplatesForSpecificAnswer(
        ?int $question_id,
        AnswersSet $answers_set,
    ) {
        if ($question_id === null) {
            return null;
        }

        $answer = $answers_set->getAnswerByQuestionId($question_id);
        if ($answer === null) {
            return null;
        }

        $value = $answer->getRawAnswer();
        if (!$this->isValidAnswer($value)) {
            return null;
        }

        return $value['items_id'];
    }

    private function getTaskTemplatesForLastValidAnswer(
        AnswersSet $answers_set,
    ): ?array {
        $valid_answers = $this->getTaskTemplatesForAllValidAnswers($answers_set);

        if (empty($valid_answers)) {
            return null;
        }

        return [end($valid_answers)];
    }

    private function getTaskTemplatesForAllValidAnswers(
        AnswersSet $answers_set,
    ): ?array {
        $valid_answers = array_filter(
            $answers_set->getAnswersByType(
                QuestionTypeItemDropdown::class
            ),
            fn($answer) => $this->isValidAnswer($answer->getRawAnswer())
        );

        if (empty($valid_answers)) {
            return null;
        }

        return array_map(
            fn($answer) => $answer->getRawAnswer()['items_id'],
            $valid_answers
        );
    }
}
