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

use Glpi\Form\BlockInterface;
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
    private const MAX_FIXED_POINT_ITERATIONS = 100;

    /**
     * @var array<string, bool> Cache for item visibility results indexed by UUID
     */
    private array $visibility_cache = [];

    /**
     * @var bool Whether the fixed-point algorithm has been executed
     */
    private bool $visibility_computed = false;

    public function __construct(
        private Form $form,
        private EngineInput $input,
    ) {}

    public function computeVisibility(): EngineVisibilityOutput
    {
        // Phase 1: Compute all visibilities using fixed-point algorithm
        $this->computeVisibilitiesWithFixedPoint();

        // Phase 2: Build output from cache
        $output = new EngineVisibilityOutput();

        // Set form visibility
        $output->setFormVisibility(
            $this->visibility_cache[$this->form->getUUID()] ?? true,
        );

        // Set questions visibility
        foreach ($this->form->getQuestions() as $question) {
            $output->setQuestionVisibility(
                $question->getID(),
                $this->visibility_cache[$question->getUUID()] ?? true,
            );
        }

        // Set comments visibility
        foreach ($this->form->getFormComments() as $comment) {
            $output->setCommentVisibility(
                $comment->getID(),
                $this->visibility_cache[$comment->getUUID()] ?? true,
            );
        }

        // Set sections visibility
        $first = true;
        foreach ($this->form->getSections() as $section) {
            if ($first) {
                // First section must always be visible
                $output->setSectionVisibility($section->getID(), true);
                $first = false;
                continue;
            }

            $is_visible = $this->visibility_cache[$section->getUUID()] ?? true;

            // Set visibility to false if the section does not have at least one visible child
            if ($is_visible) {
                $is_visible = $this->sectionHasVisibleChildren($output, $section);
            }
            $output->setSectionVisibility($section->getID(), $is_visible);
        }

        return $output;
    }

    /**
     * Compute all item visibilities using a fixed-point iteration algorithm.
     *
     * This algorithm handles circular dependencies between conditions by iterating
     * until the visibility state stabilizes (no changes between iterations).
     */
    private function computeVisibilitiesWithFixedPoint(): void
    {
        if ($this->visibility_computed) {
            return;
        }

        // Get all items that can have visibility conditions
        $items = $this->getAllConditionableItems();

        // Initialize visibility cache with default values based on strategy
        foreach ($items as $item) {
            $strategy = $item->getConfiguredVisibilityStrategy();
            // Default: visible if strategy is ALWAYS_VISIBLE, otherwise assume not visible initially
            $this->visibility_cache[$item->getUUID()] = ($strategy === VisibilityStrategy::ALWAYS_VISIBLE);
        }

        // Fixed-point iteration
        for ($iteration = 0; $iteration < self::MAX_FIXED_POINT_ITERATIONS; $iteration++) {
            $changed = false;

            foreach ($items as $item) {
                $new_visibility = $this->evaluateItemVisibility($item);
                $uuid = $item->getUUID();

                if ($this->visibility_cache[$uuid] !== $new_visibility) {
                    $this->visibility_cache[$uuid] = $new_visibility;
                    $changed = true;
                }
            }

            if (!$changed) {
                // Fixed point reached, all visibilities are stable
                break;
            }
        }

        $this->visibility_computed = true;
    }

    /**
     * Get all items that implement ConditionableVisibilityInterface.
     *
     * @return ConditionableVisibilityInterface[]
     */
    private function getAllConditionableItems(): array
    {
        $items = [];

        // Add the form itself
        $items[] = $this->form;

        // Add all questions
        foreach ($this->form->getQuestions() as $question) {
            $items[] = $question;
        }

        // Add all comments
        foreach ($this->form->getFormComments() as $comment) {
            $items[] = $comment;
        }

        // Add all sections
        foreach ($this->form->getSections() as $section) {
            $items[] = $section;
        }

        return $items;
    }

    /**
     * Evaluate the visibility of an item using the current state of the visibility cache.
     */
    private function evaluateItemVisibility(ConditionableVisibilityInterface $item): bool
    {
        $strategy = $item->getConfiguredVisibilityStrategy();

        // Before evaluating conditions, check parent visibility for BlockInterface items
        if ($item instanceof BlockInterface) {
            $parent_visible = $this->visibility_cache[$item->getSection()->getUUID()] ?? true;
            if (!$parent_visible) {
                return false;
            }
        }

        // Strategy ALWAYS_VISIBLE means always visible
        if ($strategy === VisibilityStrategy::ALWAYS_VISIBLE) {
            $result = true;
        } else {
            // Compute the conditions using current visibility state
            $conditions = $item->getConfiguredConditionsData();
            $conditions_result = $this->computeConditions($conditions);
            $result = $strategy->mustBeVisible($conditions_result);
        }

        // Special handling for questions: check if question type allows unauthenticated access
        if ($result === true && $item instanceof Question) {
            if (
                !$item->getQuestionType()->isAllowedForUnauthenticatedAccess()
                && !Session::isAuthenticated()
            ) {
                $result = false;
            }
        }

        return $result;
    }

    public function computeValidation(): EngineValidationOutput
    {
        // Ensure visibility is computed first (needed for condition evaluation)
        $this->computeVisibilitiesWithFixedPoint();

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
        // Ensure visibility is computed first (needed for condition evaluation)
        $this->computeVisibilitiesWithFixedPoint();

        $output = new EngineCreationOutput();

        // Compute destinations creation
        foreach ($this->form->getDestinations() as $destination) {
            $visibility = $this->computeDestinationCreation($destination);
            if ($visibility) {
                $output->addItemThatMustBeCreated($destination);
            }
        }

        return $output;
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
        // Evaluate each condition individually and collect its logic operator.
        $evaluated = [];
        $operators = [];
        foreach ($conditions as $condition) {
            if (empty($condition->getItemUuid())) {
                continue;
            }
            $condition_result = $this->computeCondition($condition);
            $result_per_condition[spl_object_id($condition)] = $condition_result;
            $evaluated[] = $condition_result;
            $operators[] = $condition->getLogicOperator();
        }

        if ($evaluated === []) {
            return false;
        }

        // Apply AND-over-OR operator precedence (matching legacy formcreator behavior):
        // operators[0] is the first condition's operator and is always ignored.
        // operators[i] (i > 0) is the operator connecting evaluated[i-1] to evaluated[i].
        // We split on OR boundaries into AND-groups, then OR-reduce across groups.
        $or_groups = [];
        $current_group = [$evaluated[0]];
        for ($i = 1, $count = count($evaluated); $i < $count; $i++) {
            if ($operators[$i] === LogicOperator::OR) {
                $or_groups[] = $current_group;
                $current_group = [$evaluated[$i]];
            } else {
                $current_group[] = $evaluated[$i];
            }
        }
        $or_groups[] = $current_group;

        foreach ($or_groups as $group) {
            if (!in_array(false, $group, true)) {
                return true; // all conditions in this AND-group are true
            }
        }
        return false;
    }

    private function computeCondition(ConditionData $condition): bool
    {
        $type = $condition->getItemType();
        $operator = $condition->getValueOperator();
        $answer = null;
        $config = null;

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

                // Get source visibility from cache
                $source_visible = $this->visibility_cache[$question->getUUID()] ?? true;
                $answer = $this->input->getAnswers()[$question->getID()] ?? null;

                // Handle operators based on their dependency on source visibility
                if ($operator === ValueOperator::VISIBLE || $operator === ValueOperator::NOT_VISIBLE) {
                    // VISIBLE/NOT_VISIBLE operators use the visibility state directly
                    $answer = $source_visible;
                } elseif ($operator === ValueOperator::EMPTY) {
                    // EMPTY: if source is not visible, consider it as empty (return true)
                    if (!$source_visible) {
                        return true;
                    }
                } else {
                    // Other operators: if source is not visible, condition is false
                    // (user cannot see/modify the value)
                    if (!$source_visible) {
                        return false;
                    }
                }
                break;

            case Type::SECTION:
                $item = Section::getByUuid($condition->getItemUuid());
                $source_visible = $this->visibility_cache[$item->getUUID()] ?? true;

                if ($operator === ValueOperator::VISIBLE || $operator === ValueOperator::NOT_VISIBLE) {
                    $answer = $source_visible;
                }
                break;

            case Type::COMMENT:
                $item = Comment::getByUuid($condition->getItemUuid());
                $source_visible = $this->visibility_cache[$item->getUUID()] ?? true;

                if ($operator === ValueOperator::VISIBLE || $operator === ValueOperator::NOT_VISIBLE) {
                    $answer = $source_visible;
                }
                break;

            default:
                throw new LogicException(sprintf('Unknown type "%s" for condition', $type));
        }

        $condition_handler = array_filter(
            $item->getConditionHandlers($config),
            fn(ConditionHandlerInterface $handler): bool => in_array(
                $operator,
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
            $config,
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
