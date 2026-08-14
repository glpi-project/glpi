<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace Glpi\Form\Tag;

use CommonDBTM;
use Glpi\Form\AnswersSet;
use Glpi\Form\Form;
use Glpi\Form\Question;
use Glpi\Form\QuestionType\QuestionTypeItem;
use Glpi\Search\SearchOption;
use Override;

final class ItemPropertyTagProvider implements TagProviderInterface, CompositeTagValueInterface
{
    public const SEPARATOR = ':';
    public const USER_EMAILS_PSEUDO_ID = -1;

    #[Override]
    public function getTagColor(): string
    {
        return "cyan";
    }

    #[Override]
    public function getItemtype(): string
    {
        return Question::class;
    }

    #[Override]
    public function getTags(Form $form): array
    {
        $tags = [];
        foreach ($form->getQuestions() as $question) {
            if (!$this->isItemQuestion($question)) {
                continue;
            }
            foreach ($this->getPropertiesForQuestion($question) as $option_id => $option_label) {
                $tags[] = new Tag(
                    label: sprintf(__('Answer: %1$s › %2$s'), $question->fields['name'], $option_label),
                    value: $question->getId() . self::SEPARATOR . $option_id,
                    provider: $this,
                );
            }
        }
        return $tags;
    }

    #[Override]
    public function getTagFromRawValue(string $value): ?Tag
    {
        [$question_id, $option_id] = $this->parseValue($value);
        $question = Question::getById($question_id);
        if (!$question || !$this->isItemQuestion($question)) {
            return null;
        }
        $properties = $this->getPropertiesForQuestion($question);
        if (!isset($properties[$option_id])) {
            return null;
        }
        return new Tag(
            label: sprintf(__('Answer: %1$s › %2$s'), $question->fields['name'], $properties[$option_id]),
            value: $value,
            provider: $this,
        );
    }

    #[Override]
    public function getTagContentForValue(string $value, AnswersSet $answers_set): string
    {
        [$question_id, $option_id] = $this->parseValue($value);

        $answer = $answers_set->getAnswerByQuestionId($question_id);
        if ($answer === null) {
            return '';
        }

        $raw = $answer->getRawAnswer();
        if (!is_array($raw) || empty($raw['itemtype']) || empty($raw['items_ids'])) {
            return '';
        }

        if (!is_array($raw['items_ids'])) {
            $raw['items_ids'] = [$raw['items_ids']];
        }

        $itemtype = $raw['itemtype'];
        $field_vlaues = [];
        foreach ($raw['items_ids'] as $item_id) {
            $item = $itemtype::getById((int) $item_id);
            if (!$item) {
                continue;
            }
            $field_vlaues[] = $this->resolveProperty($item, $itemtype, $option_id);
        }

        return implode(', ', $field_vlaues);
    }

    #[Override]
    public function extractItemIdFromValue(string $value): string
    {
        return (string) $this->parseValue($value)[0];
    }

    #[Override]
    public function rebuildValueWithMappedId(string $value, string $new_id): string
    {
        $parts = $this->parseValue($value);
        return $new_id . self::SEPARATOR . $parts[1];
    }

    // --- private helpers ---

    private function isItemQuestion(Question $question): bool
    {
        $type = $question->fields['type'];
        return is_a($type, QuestionTypeItem::class, true);
    }

    /**
     * Return available properties for a question.
     * Only simple properties are returned,(main table, scalar field) and documented special cases (ex: User emails).
     *
     * @return array<int, string>  [search_option_id => translated label]
     */
    private function getPropertiesForQuestion(Question $question): array
    {
        if (!is_a($question->fields['type'], QuestionTypeItem::class, true)) {
            return [];
        }

        $itemtype = (new ($question->fields['type'])())->getDefaultValueItemtype($question);
        if ($itemtype === null || !class_exists($itemtype) || !is_a($itemtype, CommonDBTM::class, true)) {
            return [];
        }

        $options = SearchOption::getOptionsForItemtype($itemtype);
        $properties = [];

        foreach ($options as $id => $option) {
            if (!is_int($id) || $id === 0) {
                continue;
            }
            // keep only options of the main table, with a scalar field.
            if (
                !isset($option['table'], $option['field'], $option['name'])
                || $option['table'] !== $itemtype::getTable()
                || isset($option['nosearch'])
                || isset($option['nodisplay'])
                || str_contains($option['field'], '.')
            ) {
                continue;
            }

            $allowed_types = [
                'string', 'text', 'longtext',
                'integer', 'number', 'decimal', 'float',
                'bool', 'itemlink',
                'date', 'datetime',
                'email', 'ip', 'mac', 'weblink',
                'right',
            ];
            if (isset($option['datatype']) && !in_array($option['datatype'], $allowed_types, true)) {
                continue;
            }
            $properties[$id] = $option['name'];
        }

        if (is_a($itemtype, \User::class, true)) {
            $properties[self::USER_EMAILS_PSEUDO_ID] = __('Email addresses');
        }

        return $properties;
    }

    private function resolveProperty(CommonDBTM $item, string $itemtype, int $option_id): string
    {
        // special case for user emails, as they are not a real property.
        if ($item instanceof \User && $option_id === self::USER_EMAILS_PSEUDO_ID) {
            return implode(', ', \UserEmail::getAllForUser($item->getID()));
        }

        if (!is_a($itemtype, CommonDBTM::class, true)) {
            return '';
        }

        $options = SearchOption::getOptionsForItemtype($itemtype);
        $option = $options[$option_id] ?? null;
        if ($option === null) {
            return '';
        }

        $field = $option['field'];
        $value = $item->fields[$field] ?? null;

        if ($value === null || $value === '') {
            return '';
        }

        // Convert the value to a readable string based on its datatype
        $datatype = $option['datatype'] ?? 'string';
        return match ($datatype) {
            'bool'     => $value ? __('Yes') : __('No'),
            'datetime' => \Html::convDateTime($value) ?? (string) $value,
            'date'     => \Html::convDate($value) ?? (string) $value,
            default    => (string) $value,
        };
    }

    /** @return array{0: int, 1: int} */
    private function parseValue(string $value): array
    {
        $parts = explode(self::SEPARATOR, $value, 2);
        return [(int) ($parts[0] ?? 0), (int) ($parts[1] ?? 0)];
    }
}
