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

use CommonITILObject;
use Glpi\Form\AnswersSet;
use Glpi\Form\QuestionType\QuestionTypeItem;
use Glpi\Form\QuestionType\QuestionTypeUserDevice;

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
            self::ALL_VALID_ANSWERS => __('All valid "Item" answers'),
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
        if (!isset($value['itemtype']) || !is_string($value['itemtype'])) {
            return false;
        }

        if (!isset($value['items_id']) || !is_numeric($value['items_id'])) {
            return false;
        }

        $valid_itemtypes = CommonITILObject::getAllTypesForHelpdesk();
        if (!isset($valid_itemtypes[$value['itemtype']])) {
            return false;
        }

        return true;
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

        return array_reduce(
            $question_ids,
            fn($carry, $question_id) => array_merge(
                $carry ?? [],
                $this->getAssociatedItemsForSpecificAnswer($question_id, $answers_set) ?? []
            )
        );
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

        $values = $answer->getRawAnswer();

        // $values must be a list of items
        // We can have a single item, so we need to wrap it in a list
        if (!array_is_list($values)) {
            $values = [$values];
        }

        $values = array_filter($values, fn($value) => $this->isValidAnswer($value));
        if ($values === []) {
            return null;
        }

        return $values;
    }

    private function getAssociatedItemsForLastValidAnswer(
        AnswersSet $answers_set,
    ): ?array {
        $valid_answers = $this->getValidAnswers($answers_set);

        if (count($valid_answers) == 0) {
            return null;
        }

        $answer = end($valid_answers);
        $values = $answer->getRawAnswer();

        // $values must be a list of items
        // We can have a single item, so we need to wrap it in a list
        if (!array_is_list($values)) {
            $values = [$values];
        }

        if ($values === []) {
            return null;
        }

        return $values;
    }

    private function getAssociatedItemsFromAllValidAnswers(
        AnswersSet $answers_set,
    ): ?array {
        $valid_answers = $this->getValidAnswers($answers_set);

        if (count($valid_answers) == 0) {
            return null;
        }

        $all_items = array_merge(...array_map(
            function ($answer) {
                $raw_answer = $answer->getRawAnswer();

                // $raw_answer must be a list of items
                // We can have a single item, so we need to wrap it in a list
                if (!array_is_list($raw_answer)) {
                    $raw_answer = [$raw_answer];
                }

                return $raw_answer;
            },
            $valid_answers
        ));

        // Remove duplicate items
        $unique_items = array_unique($all_items, SORT_REGULAR);

        return $unique_items;
    }

    private function getValidAnswers(AnswersSet $answers_set): array
    {
        return array_filter(
            $answers_set->getAnswersByTypes([
                QuestionTypeItem::class,
                QuestionTypeUserDevice::class,
            ]),
            function ($answer) {
                $raw_answer = $answer->getRawAnswer();

                // $raw_answer must be a list of items
                // We can have a single item, so we need to wrap it in a list
                if (!array_is_list($raw_answer)) {
                    $raw_answer = [$raw_answer];
                }

                return array_reduce(
                    $raw_answer,
                    fn($carry, $value) => $carry || $this->isValidAnswer($value),
                    false
                );
            }
        );
    }
}
