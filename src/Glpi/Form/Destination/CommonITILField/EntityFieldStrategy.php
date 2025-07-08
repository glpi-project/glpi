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

use Entity;
use Glpi\Form\AnswersSet;
use Glpi\Form\QuestionType\QuestionTypeItem;
use Session;

enum EntityFieldStrategy: string
{
    case FORM_FILLER          = 'form_filler';
    case FROM_FORM            = 'from_form';
    case SPECIFIC_VALUE       = 'specific_value';
    case SPECIFIC_ANSWER      = 'specific_answer';
    case LAST_VALID_ANSWER    = 'last_valid_answer';

    public function getLabel(): string
    {
        return match ($this) {
            self::FORM_FILLER          => __("Active entity of the form filler"),
            self::FROM_FORM            => __("From form"),
            self::SPECIFIC_VALUE       => __("Specific entity"),
            self::SPECIFIC_ANSWER      => __("Answer from a specific question"),
            self::LAST_VALID_ANSWER    => __('Answer to last "Entity" item question'),
        };
    }

    public function getEntityID(
        EntityFieldConfig $config,
        AnswersSet $answers_set,
    ): int {
        return match ($this) {
            self::FORM_FILLER          => $this->getFormFillerEntityID(),
            self::FROM_FORM            => $answers_set->getItem()->fields['entities_id'],
            self::SPECIFIC_VALUE       => $config->getSpecificEntityId() ?? $this->getFormFillerEntityID(),
            self::SPECIFIC_ANSWER      => $this->getEntityIDForSpecificAnswer(
                $config->getSpecificQuestionId(),
                $answers_set
            ),
            self::LAST_VALID_ANSWER => $this->getEntityIDForLastValidAnswer($answers_set),
        };
    }

    private function getFormFillerEntityID(): int
    {
        if (Session::isAuthenticated()) {
            return Session::getActiveEntity();
        } else {
            return 0;
        }
    }

    private function getEntityIDForSpecificAnswer(
        ?int $question_id,
        AnswersSet $answers_set,
    ): int {
        if ($question_id === null) {
            return $this->getFormFillerEntityID();
        }

        $answer = $answers_set->getAnswerByQuestionId($question_id);
        if ($answer === null) {
            return $this->getFormFillerEntityID();
        }

        $value = $answer->getRawAnswer();
        if ($value['itemtype'] !== Entity::getType() || !is_numeric($value['items_id'])) {
            return $this->getFormFillerEntityID();
        }

        return (int) $value['items_id'];
    }

    public function getEntityIDForLastValidAnswer(
        AnswersSet $answers_set,
    ): int {
        $valid_answers = array_filter(
            $answers_set->getAnswersByType(
                QuestionTypeItem::class
            ),
            fn($answer) => $answer->getRawAnswer()['itemtype'] === Entity::getType()
        );

        if (count($valid_answers) == 0) {
            return $this->getFormFillerEntityID();
        }

        $answer = end($valid_answers);
        $value = $answer->getRawAnswer();
        if (!is_numeric($value['items_id'])) {
            return $this->getFormFillerEntityID();
        }

        return (int) $value['items_id'];
    }
}
