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
use Glpi\Form\Form;
use Glpi\Form\Question;
use Glpi\Form\Section;
use LogicException;
use Safe\Exceptions\JsonException;
use Session;

use function Safe\json_decode;

final class Engine
{
    /**
     * @var array Track items currently being processed to avoid circular dependencies
     */
    private array $processing_stack = [];

    /**
     * @var array Cache for item visibility results
     */
    private array $visibility_cache = [];

    public function __construct(
        private Form $form,
        private EngineInput $input,
    ) {}

    public function computeVisibility(): EngineVisibilityOutput
    {
        $output = new EngineVisibilityOutput();

        // Compute form visibility
        $output->setFormVisibility(
            $this->computeItemVisibility($this->form),
        );

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
            if ($first) {
                // First section must always be visible
                $output->setSectionVisibility($section->getID(), true);
                $first = false;
                continue;
            }

            $is_visible = $this->computeItemVisibility($section);

            // Set visiblity to false if the section does not have at least
            // one visible child
            if ($is_visible) {
                $is_visible = $this->sectionHasVisibleChildren($output, $section);
            }
            $output->setSectionVisibility($section->getID(), $is_visible);
        }

        return $output;
    }

    public function computeValidation(): EngineValidationOutput
    {
        $validation = new EngineValidationOutput();

        // Compute questions validation
        foreach ($this->form->getQuestions() as $question) {
            $validation->setQuestionValidation(
                $question->getID(),
                $this->computeItemValidation($question),
            );
        }

        return $validation;
    }

    public function computeItemsThatMustBeCreated(): EngineCreationOutput
    {
        $output = new EngineCreationOutput();

        // Compute questions visibility
        foreach ($this->form->getDestinations() as $destination) {
            $visibility = $this->computeDestinationCreation($destination);
            if ($visibility) {
                $output->addItemThatMustBeCreated($destination);
            }
        }

        return $output;
    }

    private function computeItemVisibility(ConditionableVisibilityInterface $item): bool
    {
        $item_uuid = $item->getUUID();

        // Return cached result if available
        if (isset($this->visibility_cache[$item_uuid])) {
            return $this->visibility_cache[$item_uuid];
        }

        // Detect circular dependencies
        if (in_array($item_uuid, $this->processing_stack)) {
            // Circular dependency detected, stop processing and return false to avoid infinite loop.
            return false;
        }

        // Add current item to the processing stack
        $this->processing_stack[] = $item_uuid;

        try {
            // Stop immediatly if the strategy result is forced.
            $strategy = $item->getConfiguredVisibilityStrategy();
            if ($strategy == VisibilityStrategy::ALWAYS_VISIBLE) {
                $result = true;
            } else {
                // Compute the conditions
                $conditions = $item->getConfiguredConditionsData();
                $conditions_result = $this->computeConditions($conditions);
                $result = $strategy->mustBeVisible($conditions_result);
            }

            if ($result === true && $item instanceof Question) {
                // Questions can have a question type that does not allow its visibility to unauthenticated users.
                // In this case we must consider that the question is not visible.
                if (
                    !$item->getQuestionType()->isAllowedForUnauthenticatedAccess()
                    && !Session::isAuthenticated()
                ) {
                    $result = false;
                }
            }

            // Cache the result
            $this->visibility_cache[$item_uuid] = $result;

            return $result;
        } finally {
            // Remove the item from the processing stack when done
            array_pop($this->processing_stack);
        }
    }

    /**
     * @param Question $question
     * @return ConditionData[]
     */
    private function computeItemValidation(Question $question): array
    {
        // Stop immediatly if the strategy result is forced.
        $strategy = $question->getConfiguredValidationStrategy();
        if ($strategy == ValidationStrategy::NO_VALIDATION) {
            return [];
        }

        // Compute the conditions
        $conditions = $question->getConfiguredValidationConditionsData();
        $result_per_condition = [];
        $conditions_result = $this->computeConditions($conditions, $result_per_condition);
        if ($strategy->mustBeValidated($conditions_result)) {
            return [];
        }

        return array_filter(array_map(
            fn(ConditionData $condition): ?ConditionData => $strategy->mustBeValidated($result_per_condition[spl_object_id($condition)]) ? null : $condition,
            $conditions,
        ));
    }

    private function computeDestinationCreation(ConditionableCreationInterface $item): bool
    {
        // Stop immediatly if the strategy result is forced.
        $strategy = $item->getConfiguredCreationStrategy();
        if ($strategy == CreationStrategy::ALWAYS_CREATED) {
            return true;
        }

        // Compute the conditions
        $conditions = $item->getConfiguredConditionsData();
        $conditions_result = $this->computeConditions($conditions);

        return $strategy->mustBeCreated($conditions_result);
    }

    /**
     * @param ConditionData[] $conditions
     * @param array<int, bool> &$result_per_condition
     * @return bool
     */
    private function computeConditions(array $conditions, array &$result_per_condition = []): bool
    {
        $conditions_result = null;
        foreach ($conditions as $condition) {
            if (empty($condition->getItemUuid())) {
                continue;
            }

            // Apply condition (item + value operator + value)
            $condition_result = $this->computeCondition($condition);
            $result_per_condition[spl_object_id($condition)] = $condition_result;

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

        return $conditions_result;
    }

    private function computeCondition(ConditionData $condition): bool
    {
        // Find relevant answer using the question's id
        $type = $condition->getItemType();
        switch ($type) {
            case Type::QUESTION:
                $question = Question::getByUuid($condition->getItemUuid());
                $item = $question->getQuestionType();
                try {
                    $raw_config = json_decode($question->fields['extra_data'] ?? '', true);
                    $config = $item->getExtraDataConfig($raw_config);
                } catch (JsonException $e) {
                    $config = null;
                }
                $answer = $this->input->getAnswers()[$question->getID()] ?? null;

                // If the condition is not about visibility operators and the source question is not visible,
                // we cannot evaluate the condition based on its answer
                if (
                    $condition->getValueOperator() !== ValueOperator::VISIBLE
                    && $condition->getValueOperator() !== ValueOperator::NOT_VISIBLE
                    && !$this->computeItemVisibility($question)
                ) {
                    return false;
                }
                break;
            case Type::SECTION:
                $item = Section::getByUuid($condition->getItemUuid());
                break;
            case Type::COMMENT:
                $item = Comment::getByUuid($condition->getItemUuid());
                break;
            default:
                throw new LogicException(sprintf('Unknown type "%s" for condition', $type));
        }

        if (
            $condition->getValueOperator() === ValueOperator::VISIBLE
            || $condition->getValueOperator() === ValueOperator::NOT_VISIBLE
        ) {
            $answer = $this->computeItemVisibility($question ?? $item);
        }

        if (($answer ?? null) === null) {
            return false;
        }

        $condition_handler = array_filter(
            $item->getConditionHandlers($config ?? null),
            fn(ConditionHandlerInterface $handler): bool => in_array(
                $condition->getValueOperator(),
                $handler->getSupportedValueOperators(),
            ),
        );

        if (count($condition_handler) === 1) {
            $condition_handler = current($condition_handler);
        } else {
            throw new LogicException(
                sprintf(
                    'Condition handler not found for item "%s" and operator "%s"',
                    $item->getName(),
                    $condition->getValueOperator()->value,
                ),
            );
        }

        return $condition_handler->applyValueOperator(
            $answer,
            $condition->getValueOperator(),
            $condition->getValue(),
        );
    }

    private function sectionHasVisibleChildren(
        EngineVisibilityOutput $output,
        Section $section,
    ): bool {
        foreach ($section->getFormComments() as $comment) {
            if ($output->isCommentVisible($comment->getId())) {
                return true;
            }
        }

        foreach ($section->getQuestions() as $question) {
            if ($output->isQuestionVisible($question->getId())) {
                return true;
            }
        }

        return false;
    }
}
