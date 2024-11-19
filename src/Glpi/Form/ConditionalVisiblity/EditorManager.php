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

namespace Glpi\Form\ConditionalVisiblity;

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
        if (empty($conditions)) {
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
     *  @return string[]
     */
    public function getItemsDropdownValues(): array
    {
        $questions_data = $this->form_data->getQuestionsData();

        $dropdown_values = [];
        foreach ($questions_data as $question_data) {
            if (!($question_data->getType() instanceof UsedForConditionInterface)) {
                continue;
            }

            // Ignore selected question
            if (
                $this->form_data->getSelectedItemType() == Type::QUESTION->value
                && $question_data->getUuid() == $this->form_data->getSelectedItemUuid()
            ) {
                continue;
            }

            $key = Type::QUESTION->value . '-' . $question_data->getUuid();
            $dropdown_values[$key] = $question_data->getName();
        }

        return $dropdown_values;
    }

    /**
     * Get the allowed values operators for the given question using its id.
     * Despite an ID being submitted, the question data will be loaded from the
     * current known form data (not from the database).
     */
    public function getValueOperatorDropdownValues(string $question_uuid): array
    {
        $question = $this->findQuestionDataByUuid($question_uuid);
        if ($question === null) {
            return [];
        }

        $type = $question->getType();
        if (!$type instanceof UsedForConditionInterface) {
            return [];
        }

        $dropdown_values = [];
        foreach ($type->getSupportedValueOperators() as $operator) {
            $dropdown_values[$operator->value] = $operator->getLabel();
        }

        return $dropdown_values;
    }

    public function getLogicOperatorDropdownValues(): array
    {
        return LogicOperator::getDropdownValues();
    }

    private function findQuestionDataByUuid(string $question_uuid): ?QuestionData
    {
        $questions = $this->form_data->getQuestionsData();
        $questions = array_filter(
            $questions,
            fn (QuestionData $q): bool => $question_uuid == $q->getUuid(),
        );

        if (count($questions) !== 1) {
            return null;
        }

        $question = array_pop($questions);
        return $question;
    }
}
