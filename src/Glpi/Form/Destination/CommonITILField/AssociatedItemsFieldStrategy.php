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

use CommonITILObject;
use Glpi\Form\AnswersSet;
use Glpi\Form\QuestionType\QuestionTypeItem;

enum AssociatedItemsFieldStrategy: string
{
    case SPECIFIC_VALUES   = 'specific_values';
    case SPECIFIC_ANSWERS  = 'specific_answers';
    case LAST_VALID_ANSWER = 'last_valid_answer';
    case ALL_VALID_ANSWERS = 'all_valid_answers';

    public function getLabel(): string
    {
        return match ($this) {
            self::SPECIFIC_VALUES   => __("Specific items"),
            self::SPECIFIC_ANSWERS  => __("Answer from specific questions"),
            self::LAST_VALID_ANSWER => __('Answer to last assets item question'),
            self::ALL_VALID_ANSWERS => __('All "Item" answers'),
        };
    }

    public function getAssociatedItems(
        AssociatedItemsFieldConfig $config,
        AnswersSet $answers_set,
    ): ?array {
        return match ($this) {
            self::SPECIFIC_VALUES => self::getSpecificAssociatedItems(
                $config->getSpecificAssociatedItems()
            ),
            self::SPECIFIC_ANSWERS => self::getAssociatedItemsForSpecificAnswers(
                $config->getSpecificQuestionIds(),
                $answers_set
            ),
            self::LAST_VALID_ANSWER => self::getAssociatedItemsForLastValidAnswer($answers_set),
            self::ALL_VALID_ANSWERS => self::getAssociatedItemsFromAllValidAnswers($answers_set),
        };
    }

    private function isValidAnswer(array $value): bool
    {
        return isset($value['itemtype']) && is_string($value['itemtype']) &&
            isset($value['items_id']) && is_numeric($value['items_id']);
    }

    private function getSpecificAssociatedItems(
        ?array $specific_associated_items,
    ): ?array {
        if (empty($specific_associated_items)) {
            return null;
        }

        $associated_items = [];
        foreach ($specific_associated_items as $itemtype => $items_ids) {
            if (!is_array($items_ids)) {
                continue;
            }

            foreach ($items_ids as $item_id) {
                $associated_items[] = [
                    'itemtype' => $itemtype,
                    'items_id' => $item_id,
                ];
            }
        }

        return array_filter($associated_items, fn($answer) => $this->isValidAnswer($answer));
    }

    private function getAssociatedItemsForSpecificAnswers(
        ?array $question_ids,
        AnswersSet $answers_set,
    ): ?array {
        if (empty($question_ids)) {
            return null;
        }

        $associted_items = array_map(
            fn($question_id) => $this->getAssociatedItemsForSpecificAnswer($question_id, $answers_set),
            $question_ids
        );

        return array_filter($associted_items);
    }

    private function getAssociatedItemsForSpecificAnswer(
        ?int $question_id,
        AnswersSet $answers_set,
    ): ?array {
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

        return $value;
    }

    private function getAssociatedItemsForLastValidAnswer(
        AnswersSet $answers_set,
    ): ?array {
        $valid_answers = array_filter(
            $answers_set->getAnswersByType(
                QuestionTypeItem::class
            ),
            fn($answer) => in_array(
                $answer->getRawAnswer()['itemtype'],
                CommonITILObject::getAllTypesForHelpdesk()
            )
        );

        if (count($valid_answers) == 0) {
            return null;
        }

        $answer = end($valid_answers);
        $value = $answer->getRawAnswer();
        if (!$this->isValidAnswer($value)) {
            return null;
        }

        return [$value];
    }

    private function getAssociatedItemsFromAllValidAnswers(
        AnswersSet $answers_set,
    ): ?array {
        $valid_answers = array_filter(
            $answers_set->getAnswersByType(
                QuestionTypeItem::class
            ),
            fn($answer) => in_array(
                $answer->getRawAnswer()['itemtype'],
                CommonITILObject::getAllTypesForHelpdesk()
            )
        );

        if (count($valid_answers) == 0) {
            return null;
        }

        $associted_items = array_map(
            fn($answer) => $answer->getRawAnswer(),
            $valid_answers
        );

        return array_filter($associted_items, fn($answer) => $this->isValidAnswer($answer));
    }
}
