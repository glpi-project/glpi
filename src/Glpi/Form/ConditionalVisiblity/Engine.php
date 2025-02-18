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

namespace Glpi\Form\ConditionalVisiblity;

use Glpi\Form\Form;
use Glpi\Form\Question;
use Glpi\Form\ConditionalVisiblity\VisibilityStrategy;
use RuntimeException;

final class Engine
{
    public function __construct(
        private Form $form,
        private EngineInput $input,
    ) {
    }

    public function computeVisibility(): EngineOutput
    {
        $output = new EngineOutput();

        // Compute questions visibility
        foreach ($this->form->getQuestions() as $question) {
            $output->setQuestionVisibility(
                $question->getID(),
                $this->computeItemVisibility($question),
            );
        }

        // Compute comments visibility
        foreach ($this->form->getFormComments() as $comment) {
            $output->setCommentVisibility(
                $comment->getID(),
                $this->computeItemVisibility($comment),
            );
        }

        // Compute section visiblity
        $first = true;
        foreach ($this->form->getSections() as $section) {
            $output->setSectionVisibility(
                $section->getID(),
                $first ? true : $this->computeItemVisibility($section),
            );
            $first = false;
        }

        return $output;
    }

    private function computeItemVisibility(ConditionnableInterface $item): bool
    {
        // Stop immediatly if the strategy result is forced.
        $strategy = $item->getConfiguredVisibilityStrategy();
        if ($strategy == VisibilityStrategy::ALWAYS_VISIBLE) {
            return true;
        }

        // Compute the conditions
        $conditions = $item->getConfiguredConditionsData();
        $conditions_result = null;
        foreach ($conditions as $condition) {
            // Apply condition (item + value operator + value)
            $condition_result = $this->computeCondition($condition);

            // Apply logic operator
            if ($conditions_result === null) {
                // This was the first condition, ignore operator.
                $conditions_result = $condition_result;
            } else {
                // Merge result with previous result using the defined operator.
                $operator = $condition->getLogicOperator();
                $conditions_result = $operator->apply(
                    $conditions_result,
                    $condition_result,
                );
            }
        }

        // No conditions are defined, we consider the result to be false
        if ($conditions_result === null) {
            $conditions_result = false;
        }

        return $strategy->mustBeVisible($conditions_result);
    }

    private function computeCondition(ConditionData $condition): bool
    {
        // Find relevant answer using the question's id
        $type = $condition->getItemType();
        if ($type !== Type::QUESTION) {
            // Only questions can be used as criteria at this time, this should
            // never happen.
            throw new RuntimeException("Not supported");
        }
        $question = Question::getByUuid($condition->getItemUuid());
        $answer = $this->input->getAnswers()[$question->getID()] ?? null;

        // Fail for questions without an answer.
        if ($answer === null) {
            return false;
        }

        // Get UsedForConditionInstance
        $question_type = $question->getQuestionType();
        if (!($question_type instanceof UsedAsCriteriaInterface)) {
            // Invalid condition
            return false;
        }

        return $question_type->applyValueOperator(
            $answer,
            $condition->getValueOperator(),
            $condition->getValue(),
        );
    }
}
