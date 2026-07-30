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
            if ($this->isSelectedItem(Type::SECTION, $section_data->getUuid())) {
                continue;
            }

            // Format itemtype + uuid into a single key to allow selected both
            // with a simple dropdown.
            $key = Type::SECTION->value . '-' . $section_data->getUuid();
            $dropdown_values[Section::getTypeName(Session::getPluralNumber())][$key] = $this->getSectionLabel($section_data);
        }

        $questions_data = $this->form_data->getQuestionsData();
        $comments_data = $this->form_data->getCommentsData();

        // Questions and comments are grouped under their parent section so that
        // items sharing the same name (a common case across sections) can be
        // told apart, just like in GLPI 10.
        //
        // This is only useful when the form has more than one section: with a
        // single section, its header is hidden in the editor, so grouping by
        // section would only add visual noise. In that case (or when no section
        // information is available), fall back to a flat grouping by type.
        $group_by_section = count($sections_data) > 1;

        // Index of known section uuids, used both to label the groups and to
        // detect items whose parent section can't be resolved.
        $section_names = [];
        foreach ($sections_data as $section_data) {
            $section_names[$section_data->getUuid()] = $section_data->getName();
        }

        // Build the per-section groups in section order so that questions and
        // comments appear under their section, in the form's order.
        if ($group_by_section) {
            foreach ($sections_data as $section_data) {
                $group = $this->getSectionLabel($section_data);

                foreach ($questions_data as $question_data) {
                    if (
                        $question_data->getSectionUuid() === $section_data->getUuid()
                        && !$this->isSelectedItem(Type::QUESTION, $question_data->getUuid())
                    ) {
                        $key = Type::QUESTION->value . '-' . $question_data->getUuid();
                        $dropdown_values[$group][$key] = $question_data->getName();
                    }
                }

                foreach ($comments_data as $comment_data) {
                    if (
                        $comment_data->getSectionUuid() === $section_data->getUuid()
                        && !$this->isSelectedItem(Type::COMMENT, $comment_data->getUuid())
                    ) {
                        $key = Type::COMMENT->value . '-' . $comment_data->getUuid();
                        $dropdown_values[$group][$key] = $comment_data->getName();
                    }
                }
            }
        }

        // Items that are not grouped by section (single-section form, or items
        // whose parent section is unknown) keep their former generic grouping
        // by type.
        foreach ($questions_data as $question_data) {
            if ($group_by_section && !empty($question_data->getSectionUuid()) && isset($section_names[$question_data->getSectionUuid()])) {
                continue;
            }
            if ($this->isSelectedItem(Type::QUESTION, $question_data->getUuid())) {
                continue;
            }

            $key = Type::QUESTION->value . '-' . $question_data->getUuid();
            $dropdown_values[Question::getTypeName(Session::getPluralNumber())][$key] = $question_data->getName();
        }

        foreach ($comments_data as $comment_data) {
            if ($group_by_section && !empty($comment_data->getSectionUuid()) && isset($section_names[$comment_data->getSectionUuid()])) {
                continue;
            }
            if ($this->isSelectedItem(Type::COMMENT, $comment_data->getUuid())) {
                continue;
            }

            $key = Type::COMMENT->value . '-' . $comment_data->getUuid();
            $dropdown_values[Comment::getTypeName(Session::getPluralNumber())][$key] = $comment_data->getName();
        }

        return $dropdown_values;
    }

    /**
     * Check whether the given item is the one currently selected, as a condition
     * can't be used as a criteria for its own visibility.
     */
    private function isSelectedItem(Type $type, string $uuid): bool
    {
        return $this->form_data->getSelectedItemType() == $type->value
            && $uuid == $this->form_data->getSelectedItemUuid();
    }

    /**
     * Get the label to display for a section, falling back to the same
     * placeholder as the editor when the section has no name.
     */
    private function getSectionLabel(SectionData $section): string
    {
        return $section->getName() !== '' ? $section->getName() : __('New section');
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
