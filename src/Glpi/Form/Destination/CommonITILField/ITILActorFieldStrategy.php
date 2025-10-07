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
use Glpi\Form\QuestionType\AbstractQuestionTypeActors;
use Glpi\Form\QuestionType\QuestionTypeEmail;
use Group;
use Session;
use Ticket;
use User;

enum ITILActorFieldStrategy: string
{
    case FORM_FILLER                   = 'form_filler';
    case FORM_FILLER_SUPERVISOR        = 'form_filler_supervisor';
    case FROM_TEMPLATE                 = 'from_template';
    case SPECIFIC_VALUES               = 'specific_values';
    case SPECIFIC_ANSWERS              = 'specific_answer';
    case LAST_VALID_ANSWER             = 'last_valid_answer';
    case USER_FROM_OBJECT_ANSWER       = 'user_from_object_answer';
    case TECH_USER_FROM_OBJECT_ANSWER  = 'tech_user_from_object_answer';
    case GROUP_FROM_OBJECT_ANSWER      = 'group_from_object_answer';
    case TECH_GROUP_FROM_OBJECT_ANSWER = 'tech_group_from_object_answer';

    public function getLabel(string $label): string
    {
        return match ($this) {
            self::FORM_FILLER                   => __('User who filled the form'),
            self::FORM_FILLER_SUPERVISOR        => __('Supervisor of the user who filled the form'),
            self::FROM_TEMPLATE                 => __('From template'),
            self::SPECIFIC_VALUES               => __('Specific actors'),
            self::SPECIFIC_ANSWERS              => __('Answer from specific questions'),
            self::LAST_VALID_ANSWER             => sprintf(__('Answer to last "%s" or "Email" question'), $label),
            self::USER_FROM_OBJECT_ANSWER       => __('User from GLPI object answer'),
            self::TECH_USER_FROM_OBJECT_ANSWER  => __('Tech user from GLPI object answer'),
            self::GROUP_FROM_OBJECT_ANSWER      => __('Group from GLPI object answer'),
            self::TECH_GROUP_FROM_OBJECT_ANSWER => __('Tech group from GLPI object answer'),
        };
    }

    public function getITILActors(
        ITILActorField $itil_actor_field,
        ITILActorFieldConfig $config,
        AnswersSet $answers_set
    ): ?array {
        return match ($this) {
            self::FROM_TEMPLATE          => null,
            self::FORM_FILLER            => $this->getActorsFromCurrentUser($answers_set),
            self::FORM_FILLER_SUPERVISOR => $this->getActorsFromSupervisorOfCurrentUser($answers_set),
            self::SPECIFIC_VALUES        => $this->getActorsFromSpecificValues(
                $config->getSpecificITILActorsIds(),
                $itil_actor_field
            ),
            self::SPECIFIC_ANSWERS       => $this->getActorsForSpecificAnswers(
                $config->getSpecificQuestionIds(),
                $itil_actor_field,
                $answers_set,
            ),
            self::LAST_VALID_ANSWER      => $this->getActorsForLastValidAnswer(
                $itil_actor_field,
                $answers_set,
            ),
            self::USER_FROM_OBJECT_ANSWER => $this->getActorsFromObjectAnswers(
                $config->getSpecificQuestionIds(),
                $answers_set,
                User::getForeignKeyField()
            ),
            self::TECH_USER_FROM_OBJECT_ANSWER => $this->getActorsFromObjectAnswers(
                $config->getSpecificQuestionIds(),
                $answers_set,
                User::getForeignKeyField() . '_tech'
            ),
            self::GROUP_FROM_OBJECT_ANSWER => $this->getActorsFromObjectAnswers(
                $config->getSpecificQuestionIds(),
                $answers_set,
                Group::getForeignKeyField()
            ),
            self::TECH_GROUP_FROM_OBJECT_ANSWER => $this->getActorsFromObjectAnswers(
                $config->getSpecificQuestionIds(),
                $answers_set,
                Group::getForeignKeyField() . '_tech'
            ),
        };
    }

    private function getActorsFromCurrentUser(AnswersSet $answers_set): ?array
    {
        $user_id = Session::getLoginUserID();
        if (!is_numeric($user_id)) {
            return null;
        }

        $delegation = $answers_set->getDelegation();

        if (
            $delegation->users_id !== null
            && Ticket::canDelegateeCreateTicket($delegation->users_id)
        ) {
            return [
                [
                    'itemtype' => User::class,
                    'items_id' => $delegation->users_id ?? $user_id,
                    'use_notification' => $delegation->use_notification ?? 0,
                    'alternative_email' => $delegation->alternative_email ?? '',
                ],
            ];
        } else {
            return [
                [
                    'itemtype' => User::class,
                    'items_id' => $user_id,
                ],
            ];
        }
    }

    private function getActorsFromSupervisorOfCurrentUser(AnswersSet $answers_set): ?array
    {
        $users_id = $answers_set->getDelegation()->users_id ?? Session::getLoginUserID();
        if (!is_numeric($users_id)) {
            return null;
        }

        $user = new User();
        $user->getFromDB($users_id);
        $supervisor_id = $user->fields['users_id_supervisor'];

        if (!is_numeric($supervisor_id)) {
            return null;
        }

        return [
            [
                'itemtype' => User::class,
                'items_id' => (int) $supervisor_id,
            ],
        ];
    }

    private function getActorsFromSpecificValues(
        ?array $itil_actors_ids,
        ITILActorField $itil_actor_field
    ): ?array {
        if (empty($itil_actors_ids)) {
            return null;
        }

        $actors = [];
        foreach ($itil_actors_ids as $itemtype => $ids) {
            foreach ($ids as $id) {
                if (
                    !in_array($itemtype, $itil_actor_field->getAllowedActorTypes())
                    || !is_numeric($id)
                ) {
                    continue;
                }

                $actors[] = [
                    'itemtype' => $itemtype,
                    'items_id' => (int) $id,
                ];
            }
        }

        return $actors;
    }

    private function getActorsForSpecificAnswers(
        ?array $question_ids,
        ITILActorField $itil_actor_field,
        AnswersSet $answers_set,
    ): ?array {
        if (empty($question_ids)) {
            return null;
        }

        return array_reduce($question_ids, function ($carry, $question_id) use ($itil_actor_field, $answers_set) {
            $actors_ids = $this->getActorsForSpecificAnswer(
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

    private function getActorsForSpecificAnswer(
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

        if ($answer->getType() instanceof AbstractQuestionTypeActors) {
            $values = $answer->getRawAnswer();
            return array_reduce($values, function ($carry, $value) use ($itil_actor_field) {
                if (
                    !in_array($value['itemtype'], $itil_actor_field->getAllowedActorTypes())
                    || !is_numeric($value['items_id'])
                ) {
                    return $carry;
                }

                $carry[] = [
                    'itemtype' => $value['itemtype'],
                    'items_id' => (int) $value['items_id'],
                ];
                return $carry;
            }, []);
        } elseif ($answer->getType() instanceof QuestionTypeEmail) {
            $value = $answer->getRawAnswer();
            if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return [
                    [
                        'itemtype' => User::class,
                        'items_id' => 0, // No specific user ID, just the email
                        'use_notification' => 1, // Use notification
                        'alternative_email' => $value,
                    ],
                ];
            }
        }

        return null;
    }

    private function getActorsForLastValidAnswer(
        ITILActorField $itil_actor_field,
        AnswersSet $answers_set,
    ): ?array {
        $allowed_types = $itil_actor_field->getAllowedQuestionType();
        $valid_answers = [];
        foreach ($allowed_types as $question_type) {
            $answers = $answers_set->getAnswersByType($question_type::class);
            $valid_answers = array_merge($valid_answers, $answers);
        }

        if (count($valid_answers) == 0) {
            return null;
        }

        $answer = end($valid_answers);
        return $this->getActorsForSpecificAnswer(
            $answer->getQuestionId(),
            $itil_actor_field,
            $answers_set
        );
    }

    private function getActorsFromObjectAnswers(
        ?array $question_ids,
        AnswersSet $answers_set,
        string $fk_field
    ): ?array {
        if (empty($question_ids)) {
            return null;
        }

        return array_reduce($question_ids, function ($carry, $question_id) use ($answers_set, $fk_field) {
            $actors_ids = $this->getActorsFromObjectAnswer(
                $question_id,
                $answers_set,
                $fk_field
            );

            if ($actors_ids === null) {
                return $carry;
            }

            return array_merge_recursive($carry, $actors_ids);
        }, []);
    }

    private function getActorsFromObjectAnswer(
        ?int $question_id,
        AnswersSet $answers_set,
        string $fk_field
    ): ?array {
        if ($question_id === null) {
            return null;
        }

        $answer = $answers_set->getAnswerByQuestionId($question_id);
        if ($answer === null) {
            return null;
        }

        $value = $answer->getRawAnswer();
        if (
            getItemForItemtype($value['itemtype']) === false
            || !is_numeric($value['items_id'])
        ) {
            return null;
        }

        $item = getItemForItemtype($value['itemtype']);
        if (!$item->getFromDB($value['items_id'])) {
            return null;
        }

        if (!isset($item->fields[$fk_field])) {
            return null;
        }

        $actors_ids = $item->fields[$fk_field];
        if (!is_array($actors_ids)) {
            $actors_ids = [$actors_ids];
        }

        $itemtype = getItemtypeForForeignKeyField(str_replace('_tech', '', $fk_field));
        return array_map(fn($actor_id) => [
            'itemtype' => $itemtype,
            'items_id' => (int) $actor_id,
        ], $actors_ids);
    }
}
