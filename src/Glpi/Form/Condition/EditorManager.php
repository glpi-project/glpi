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

use Glpi\Form\Comment;
use Glpi\Form\Condition\ConditionHandler\ConditionHandlerInterface;
use Glpi\Form\Question;
use Glpi\Form\Section;
use Session;

final class EditorManager
{
    private FormData $form_data;

    /**
     * Current data; must be set before calling others methods.
     */
    public function setFormData(FormData $form_data): void
    {
        $this->form_data = $form_data;
    }

    /**
     * Get the defined conditions of the current form data.
     * Will add a default empty condition if none are defined.
     *
     * @return ConditionData[]
     */
    public function getDefinedConditions(): array
    {
        $conditions = $this->form_data->getConditionsData();
        if ($conditions === []) {
            $conditions[] = new ConditionData(
                item_uuid: '',
                item_type: '',
                value_operator: null,
                value: null,
            );
        }

        return $conditions;
    }

    /**
     * Get a formatted array that is ready to be displayed in a dropdown, with
     * each options being a question of the current form data that support
     * conditions.
     *
     * In the future, this will also contains sections as they can be used as
     * conditions too (show question X if section xxx is visible...)
     *
     *  @return array<string, array<string, string>>
     */
    public function getItemsDropdownValues(): array
    {
        $dropdown_values = [];

        $sections_data = $this->form_data->getSectionsData();
        foreach ($sections_data as $section_data) {
            // Ignore the section that is currently selected as a condition can't
            // be used as a criteria for its own visiblity.
            if (
                $this->form_data->getSelectedItemType() == Type::SECTION->value
                && $section_data->getUuid() == $this->form_data->getSelectedItemUuid()
            ) {
                continue;
            }

            // Format itemtype + uuid into a single key to allow selected both
            // with a simple dropdown.
            $key = Type::SECTION->value . '-' . $section_data->getUuid();
            $dropdown_values[Section::getTypeName(Session::getPluralNumber())][$key] = $section_data->getName();
        }

        $questions_data = $this->form_data->getQuestionsData();
        foreach ($questions_data as $question_data) {
            // Ignore the question that is currently selected as a condition can't
            // be used as a criteria for its own visiblity.
            if (
                $this->form_data->getSelectedItemType() == Type::QUESTION->value
                && $question_data->getUuid() == $this->form_data->getSelectedItemUuid()
            ) {
                continue;
            }

            // Format itemtype + uuid into a single key to allow selected both
            // with a simple dropdown.
            $key = Type::QUESTION->value . '-' . $question_data->getUuid();
            $dropdown_values[Question::getTypeName(Session::getPluralNumber())][$key] = $question_data->getName();
        }

        $comments_data = $this->form_data->getCommentsData();
        foreach ($comments_data as $comment_data) {
            // Ignore the comment that is currently selected as a condition can't
            // be used as a criteria for its own visiblity.
            if (
                $this->form_data->getSelectedItemType() == Type::COMMENT->value
                && $comment_data->getUuid() == $this->form_data->getSelectedItemUuid()
            ) {
                continue;
            }

            // Format itemtype + uuid into a single key to allow selected both
            // with a simple dropdown.
            $key = Type::COMMENT->value . '-' . $comment_data->getUuid();
            $dropdown_values[Comment::getTypeName(Session::getPluralNumber())][$key] = $comment_data->getName();
        }

        return $dropdown_values;
    }

    /**
     * Get the allowed values operators for the given question using its uuid.
     */
    public function getValueOperatorDropdownValues(string $uuid): array
    {
        $itemData = $this->findItemDataByUuid($uuid);
        if ($itemData === null) {
            return [];
        }

        switch ($itemData::class) {
            case QuestionData::class:
                // Load question type config
                $raw_config = $itemData->getExtraData();

                // Replace the question data with the question type
                $itemData = $itemData->getType();

                // Load question type config
                $config = $raw_config ? $itemData->getExtraDataConfig($raw_config) : null;
                break;
            case SectionData::class:
                $itemData = new Section();
                break;
            case CommentData::class:
                $itemData = new Comment();
                break;
        }

        // Load possible value operators
        $dropdown_values = [];
        foreach ($itemData->getConditionHandlers($config ?? null) as $condition_handler) {
            foreach ($condition_handler->getSupportedValueOperators() as $operator) {
                $dropdown_values[$operator->value] = $operator->getLabel();
            }
        }

        return $dropdown_values;
    }

    public function getValueOperatorForValidationDropdownValues(string $uuid): array
    {
        // Filter the value operators to only keep the ones that can be used for validation
        return array_filter(
            $this->getValueOperatorDropdownValues($uuid),
            fn(string $key): bool => ValueOperator::from($key)->canBeUsedForValidation(),
            ARRAY_FILTER_USE_KEY,
        );
    }

    public function getLogicOperatorDropdownValues(): array
    {
        return LogicOperator::getDropdownValues();
    }

    public function getHandlerForCondition(
        ConditionData $condition
    ): ConditionHandlerInterface {
        $itemData = $this->findItemDataByUuid($condition->getItemUuid());

        switch ($itemData::class) {
            case QuestionData::class:
                // Load question type config
                $raw_config = $itemData->getExtraData();

                // Replace the question data with the question type
                $itemData = $itemData->getType();

                // Load question type config
                $config = $raw_config ? $itemData->getExtraDataConfig($raw_config) : null;
                break;
            case SectionData::class:
                $itemData = new Section();
                break;
            case CommentData::class:
                $itemData = new Comment();
                break;
        }

        $condition_handlers = array_filter(
            $itemData->getConditionHandlers($config ?? null),
            fn(ConditionHandlerInterface $handler): bool => in_array(
                $condition->getValueOperator(),
                $handler->getSupportedValueOperators()
            ),
        );

        if (count($condition_handlers) !== 1) {
            // Fallback to the first one if there is no match
            return current($itemData->getConditionHandlers($config ?? null));
        }

        return array_pop($condition_handlers);
    }

    /**
     * Find an item (question, section, or comment) by its UUID
     *
     * @param string $uuid UUID to search for
     * @return QuestionData|SectionData|CommentData|null The found item or null if not found
     */
    private function findItemDataByUuid(string $uuid): QuestionData|SectionData|CommentData|null
    {
        // First, search in questions
        $questions = $this->form_data->getQuestionsData();
        $filtered_questions = array_filter(
            $questions,
            fn(QuestionData $q): bool => $uuid == $q->getUuid(),
        );

        if (count($filtered_questions) === 1) {
            return array_pop($filtered_questions);
        }

        // If not found in questions, search in sections
        $sections = $this->form_data->getSectionsData();
        $filtered_sections = array_filter(
            $sections,
            fn(SectionData $s): bool => $uuid == $s->getUuid(),
        );

        if (count($filtered_sections) === 1) {
            return array_pop($filtered_sections);
        }

        // If not found in sections, search in comments
        $comments = $this->form_data->getCommentsData();
        $filtered_comments = array_filter(
            $comments,
            fn(CommentData $c): bool => $uuid == $c->getUuid(),
        );

        if (count($filtered_comments) === 1) {
            return array_pop($filtered_comments);
        }

        // Not found in any collection
        return null;
    }
}
