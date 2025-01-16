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
use Session;
use User;

enum ITILActorFieldStrategy: string
{
    case FORM_FILLER               = 'form_filler';
    case FROM_TEMPLATE             = 'from_template';
    case SPECIFIC_VALUES           = 'specific_values';
    case SPECIFIC_ANSWERS          = 'specific_answer';
    case LAST_VALID_ANSWER         = 'last_valid_answer';

    public function getLabel(string $label): string
    {
        return match ($this) {
            self::FORM_FILLER       => __('User who filled the form'),
            self::FROM_TEMPLATE     => __('From template'),
            self::SPECIFIC_VALUES   => __('Specific actors'),
            self::SPECIFIC_ANSWERS  => __('Answer from specific questions'),
            self::LAST_VALID_ANSWER => sprintf(__('Answer to last "%s" question'), $label),
        };
    }

    public function getITILActorsIDs(
        ITILActorField $itil_actor_field,
        ITILActorFieldConfig $config,
        AnswersSet $answers_set
    ): ?array {
        return match ($this) {
            self::FROM_TEMPLATE    => null,
            self::FORM_FILLER      => $this->getActorsIdsFromCurrentUser(),
            self::SPECIFIC_VALUES  => $config->getSpecificITILActorsIds(),
            self::SPECIFIC_ANSWERS => $this->getActorsIdsForSpecificAnswers(
                $config->getSpecificQuestionIds(),
                $itil_actor_field,
                $answers_set,
            ),
            self::LAST_VALID_ANSWER => $this->getActorsIdsForLastValidAnswer(
                $itil_actor_field,
                $answers_set,
            ),
        };
    }

    private function getActorsIdsFromCurrentUser(): ?array
    {
        $users_id = Session::getLoginUserID();
        if (!is_numeric($users_id)) {
            return null;
        }

        return [
            User::class => [(int) $users_id],
        ];
    }

    private function getActorsIdsForSpecificAnswers(
        ?array $question_ids,
        ITILActorField $itil_actor_field,
        AnswersSet $answers_set,
    ): ?array {
        if (empty($question_ids)) {
            return null;
        }

        return array_reduce($question_ids, function ($carry, $question_id) use ($itil_actor_field, $answers_set) {
            $actors_ids = $this->getActorsIdsForSpecificAnswer(
                $question_id,
                $itil_actor_field,
                $answers_set
            );

            if ($actors_ids === null) {
                return $carry;
            }

            return array_merge_recursive($carry, $actors_ids);
        }, []);
    }

    private function getActorsIdsForSpecificAnswer(
        ?int $question_id,
        ITILActorField $itil_actor_field,
        AnswersSet $answers_set,
    ): ?array {
        if ($question_id === null) {
            return null;
        }

        $answer = $answers_set->getAnswerByQuestionId($question_id);
        if ($answer === null) {
            return null;
        }

        $values = $answer->getRawAnswer();

        return array_reduce($values, function ($carry, $value) use ($itil_actor_field) {
            if (
                !in_array($value['itemtype'], $itil_actor_field->getAllowedActorTypes())
                || !is_numeric($value['items_id'])
            ) {
                return $carry;
            }

            $carry[$value['itemtype']][] = (int) $value['items_id'];
            return $carry;
        }, []);
    }

    private function getActorsIdsForLastValidAnswer(
        ITILActorField $itil_actor_field,
        AnswersSet $answers_set,
    ): ?array {
        $valid_answers = $answers_set->getAnswersByType(
            $itil_actor_field->getAllowedQuestionType()
        );

        if (count($valid_answers) == 0) {
            return null;
        }

        $answer = end($valid_answers);
        return $this->getActorsIdsForSpecificAnswer(
            $answer->getQuestionId(),
            $itil_actor_field,
            $answers_set
        );
    }
}
