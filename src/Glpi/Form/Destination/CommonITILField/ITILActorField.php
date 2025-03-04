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

use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\AnswersSet;
use Glpi\Form\Destination\AbstractConfigField;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeItem;
use Glpi\Form\Migration\DestinationFieldConverterInterface;
use Glpi\Form\Migration\FormMigration;
use Group;
use InvalidArgumentException;
use Override;
use Supplier;
use User;

abstract class ITILActorField extends AbstractConfigField implements DestinationFieldConverterInterface
{
    abstract public function getAllowedQuestionType(): string;
    abstract public function getActorType(): string;

    public function getAllowedActorTypes(): array
    {
        return (new ($this->getAllowedQuestionType())())->getAllowedActorTypes();
    }

    #[Override]
    public function renderConfigForm(
        Form $form,
        JsonFieldInterface $config,
        string $input_name,
        array $display_options
    ): string {
        if (!$config instanceof ITILActorFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        $specific_actors = [];
        foreach ($config->getSpecificITILActorsIDs() ?? [] as $itemtype => $ids) {
            $specific_actors[getForeignKeyFieldForItemType($itemtype)] = $ids;
        }

        $twig = TemplateRenderer::getInstance();
        return $twig->render('pages/admin/form/itil_config_fields/itilactor.html.twig', [
            // Possible configuration constant that will be used to to hide/show additional fields
            'CONFIG_SPECIFIC_VALUE'                    => ITILActorFieldStrategy::SPECIFIC_VALUES->value,
            'CONFIG_SPECIFIC_ANSWER'                   => ITILActorFieldStrategy::SPECIFIC_ANSWERS->value,
            'CONFIG_SPECIFIC_USER_OBJECT_ANSWER'       => ITILActorFieldStrategy::USER_FROM_OBJECT_ANSWER->value,
            'CONFIG_SPECIFIC_TECH_USER_OBJECT_ANSWER'  => ITILActorFieldStrategy::TECH_USER_FROM_OBJECT_ANSWER->value,
            'CONFIG_SPECIFIC_GROUP_OBJECT_ANSWER'      => ITILActorFieldStrategy::GROUP_FROM_OBJECT_ANSWER->value,
            'CONFIG_SPECIFIC_TECH_GROUP_OBJECT_ANSWER' => ITILActorFieldStrategy::TECH_GROUP_FROM_OBJECT_ANSWER->value,

            // General display options
            'options' => $display_options,

            // Specific additional config for SPECIFIC_VALUES strategy
            'specific_value_extra_field' => [
                'aria_label'      => __("Select actors..."),
                'values'          => $specific_actors,
                'input_name'      => $input_name . "[" . ITILActorFieldConfig::SPECIFIC_ITILACTORS_IDS . "]",
                'allowed_types'   => $this->getAllowedActorTypes(),
            ],

            // Specific additional config for SPECIFIC_ANSWERS strategy
            'specific_answer_extra_field' => [
                'aria_label'      => __("Select questions..."),
                'values'          => $config->getSpecificQuestionIds() ?? [],
                'input_name'      => $input_name . "[" . ITILActorFieldConfig::SPECIFIC_QUESTION_IDS . "]",
                'possible_values' => $this->getITILActorQuestionsValuesForDropdown($form),
            ],

            // Specific additional config for the following strategies:
            // - USER_FROM_OBJECT_ANSWER
            // - TECH_USER_OBJECT_ANSWER
            // - GROUP_FROM_OBJECT_ANSWER
            // - TECH_GROUP_OBJECT_ANSWER
            'object_answer_extra_field' => [
                'aria_label'      => __("Select questions..."),
                'values'          => $config->getSpecificQuestionIds() ?? [],
                'input_name'      => $input_name . "[" . ITILActorFieldConfig::SPECIFIC_QUESTION_IDS . "]",
                'possible_values' => $this->getItemWithAssignableItemtypeQuestionsValuesForDropdown($form),
            ],
        ]);
    }

    #[Override]
    public function applyConfiguratedValueToInputUsingAnswers(
        JsonFieldInterface $config,
        array $input,
        AnswersSet $answers_set
    ): array {
        if (!$config instanceof ITILActorFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        // Compute value according to strategies
        foreach ($config->getStrategies() as $strategy) {
            $itilactors_ids = $strategy->getITILActorsIDs($this, $config, $answers_set);

            if (!empty($itilactors_ids)) {
                foreach ($itilactors_ids as $itemtype => $ids) {
                    foreach ($ids as $id) {
                        $input['_actors'][$this->getActorType()][] = [
                            'itemtype' => $itemtype,
                            'items_id' => $id,
                        ];
                    }
                }
            }
        }

        return $input;
    }

    #[Override]
    public function prepareInput(array $input): array
    {
        $input = parent::prepareInput($input);

        if (!isset($input[$this->getKey()][ITILActorFieldConfig::STRATEGIES])) {
            return $input;
        }

        // Ensure that itilactors_ids is an array
        if (!is_array($input[$this->getKey()][ITILActorFieldConfig::SPECIFIC_ITILACTORS_IDS] ?? null)) {
            $input[$this->getKey()][ITILActorFieldConfig::SPECIFIC_ITILACTORS_IDS] = null;
        } else {
            $input[$this->getKey()][ITILActorFieldConfig::SPECIFIC_ITILACTORS_IDS] = array_reduce(
                $input[$this->getKey()][ITILActorFieldConfig::SPECIFIC_ITILACTORS_IDS],
                function ($carry, $value) {
                    $parts = explode("-", $value);
                    $carry[getItemtypeForForeignKeyField($parts[0])][] = (int) $parts[1];
                    return $carry;
                },
                []
            );
        }

        // Ensure that question_ids is an array
        if (!is_array($input[$this->getKey()][ITILActorFieldConfig::SPECIFIC_QUESTION_IDS] ?? null)) {
            $input[$this->getKey()][ITILActorFieldConfig::SPECIFIC_QUESTION_IDS] = null;
        }

        return $input;
    }

    #[Override]
    public function convertFieldConfig(FormMigration $migration, Form $form, array $rawData): JsonFieldInterface
    {
        $actor_role = match ($this->getActorType()) {
            'requester' => 1,
            'observer'  => 2,
            'assign'    => 3,
            default => throw new InvalidArgumentException("Unexpected actor type"),
        };

        if (isset($rawData[$actor_role])) {
            $strategies = [];
            $specific_itilactors_ids = [];
            $specific_question_ids = [];

            $strategy_map = [
                1 => ITILActorFieldStrategy::FORM_FILLER,
                // 2 => Form Validator
                3 => ITILActorFieldStrategy::SPECIFIC_VALUES,
                4 => ITILActorFieldStrategy::SPECIFIC_ANSWERS,
                5 => ITILActorFieldStrategy::SPECIFIC_VALUES,
                6 => ITILActorFieldStrategy::SPECIFIC_ANSWERS,
                7 => ITILActorFieldStrategy::SPECIFIC_VALUES,
                8 => ITILActorFieldStrategy::SPECIFIC_ANSWERS,
                9 => ITILActorFieldStrategy::SPECIFIC_ANSWERS,
                // 10 => Group from an object
                // 11 => Tech group from an object
                // 12 => Form author supervisor
            ];

            $itemtype_map = [
                3 => User::class,
                5 => Group::class,
                7 => Supplier::class,
            ];

            foreach ($rawData[$actor_role] as $raw_strategy => $ids) {
                if (isset($strategy_map[$raw_strategy])) {
                    $strategy = $strategy_map[$raw_strategy];
                    $strategies[] = $strategy;

                    if ($strategy === ITILActorFieldStrategy::SPECIFIC_VALUES && isset($itemtype_map[$raw_strategy])) {
                        foreach ($ids as $id) {
                            $specific_itilactors_ids[] = sprintf(
                                "%s-%s",
                                getForeignKeyFieldForItemType($itemtype_map[$raw_strategy]),
                                $id
                            );
                        }
                    }

                    if ($strategy === ITILActorFieldStrategy::SPECIFIC_ANSWERS) {
                        foreach ($ids as $id) {
                            $specific_question_ids[] = $migration->getMappedItemTarget(
                                'PluginFormcreatorQuestion',
                                $id
                            )['items_id'];
                        }
                    }
                }
            }

            return new ($this->getConfigClass())(
                strategies: $strategies,
                specific_itilactors_ids: $specific_itilactors_ids,
                specific_question_ids: $specific_question_ids
            );
        }

        return $this->getDefaultConfig($form);
    }

    public function getStrategiesForDropdown(): array
    {
        $values = [];
        foreach (ITILActorFieldStrategy::cases() as $strategies) {
            $values[$strategies->value] = $strategies->getLabel($this->getLabel());
        }
        return $values;
    }

    private function getITILActorQuestionsValuesForDropdown(Form $form): array
    {
        return array_reduce(
            $form->getQuestionsByType($this->getAllowedQuestionType()),
            function ($carry, $question) {
                $carry[$question->getId()] = $question->fields['name'];
                return $carry;
            },
            []
        );
    }

    private function getItemWithAssignableItemtypeQuestionsValuesForDropdown(Form $form): array
    {
        $allowed_item_questions = array_filter(
            $form->getQuestionsByType(QuestionTypeItem::class),
            function ($question) {
                $question_itemtype = (new QuestionTypeItem())->getDefaultValueItemtype($question);
                if ($question_itemtype === null) {
                    return false;
                }

                return class_uses($question_itemtype)['Glpi\Features\AssignableItem'] ?? false;
            }
        );

        return array_reduce(
            $allowed_item_questions,
            function ($carry, $question) {
                $carry[$question->getId()] = $question->fields['name'];
                return $carry;
            },
            []
        );
    }

    #[Override]
    public function canHaveMultipleStrategies(): bool
    {
        return true;
    }

    #[Override]
    public function getCategory(): Category
    {
        return Category::ACTORS;
    }
}
