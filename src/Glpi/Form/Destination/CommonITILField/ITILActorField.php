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

namespace Glpi\Form\Destination\CommonITILField;

use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Features\AssignableItem;
use Glpi\Form\AnswersSet;
use Glpi\Form\Destination\AbstractCommonITILFormDestination;
use Glpi\Form\Destination\AbstractConfigField;
use Glpi\Form\Destination\FormDestination;
use Glpi\Form\Export\Context\DatabaseMapper;
use Glpi\Form\Export\Serializer\DynamicExportDataField;
use Glpi\Form\Export\Specification\DataRequirementSpecification;
use Glpi\Form\Form;
use Glpi\Form\Migration\DestinationFieldConverterInterface;
use Glpi\Form\Migration\FormMigration;
use Glpi\Form\Question;
use Glpi\Form\QuestionType\AbstractQuestionType;
use Glpi\Form\QuestionType\AbstractQuestionTypeActors;
use Glpi\Form\QuestionType\QuestionTypeItem;
use Group;
use InvalidArgumentException;
use Override;
use Supplier;
use User;

use function Safe\class_uses;

abstract class ITILActorField extends AbstractConfigField implements DestinationFieldConverterInterface
{
    /** @return AbstractQuestionType[] */
    abstract public function getAllowedQuestionType(): array;
    abstract public function getActorType(): string;

    public function getAllowedActorTypes(): array
    {
        $question_type = array_filter(
            $this->getAllowedQuestionType(),
            fn(AbstractQuestionType $type) => $type instanceof AbstractQuestionTypeActors
        );

        return array_merge(...array_map(
            fn(AbstractQuestionTypeActors $type) => $type->getAllowedActorTypes(),
            $question_type
        ));
    }

    #[Override]
    public function renderConfigForm(
        Form $form,
        FormDestination $destination,
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

        // Apply each configured strategy to get ITIL actors
        foreach ($config->getStrategies() as $strategy) {
            $itilactors = $strategy->getITILActors($this, $config, $answers_set);

            if (empty($itilactors)) {
                continue;
            }

            // Process each actor found by the strategy
            foreach ($itilactors as $itilactor) {
                $this->addActorToInput($input, $itilactor);
            }
        }

        return $input;
    }

    /**
     * Add a single ITIL actor to the input array
     *
     * @param array $input The input array to modify
     * @param array $itilactor The actor data containing itemtype, items_id, use_notification, alternative_email
     */
    private function addActorToInput(array &$input, array $itilactor): void
    {
        // Generate the foreign key field name for this actor type
        $itemtype_fk = getForeignKeyFieldForItemType($itilactor['itemtype']);
        $actor_type = $this->getActorType();

        // Build the key names for actor and notification data
        $actor_key = "_{$itemtype_fk}_{$actor_type}";
        $notif_key = "_{$itemtype_fk}_{$actor_type}_notif";

        // Initialize actor array if not exists
        $input[$actor_key] ??= [];

        // Convert single string value to array if needed
        if (is_string($input[$actor_key])) {
            $input[$actor_key] = [$input[$actor_key]];
        }

        // Add the actor ID
        $input[$actor_key][] = $itilactor['items_id'];

        // Initialize notification arrays if not exists
        $input[$notif_key]['use_notification'] ??= [];
        $input[$notif_key]['alternative_email'] ??= [];

        // Add notification settings for this actor
        $input[$notif_key]['use_notification'][] = $itilactor['use_notification'] ?? '1';
        $input[$notif_key]['alternative_email'][] = $itilactor['alternative_email'] ?? '';
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
                1 => ITILActorFieldStrategy::FORM_FILLER, // PluginFormcreatorTarget_Actor::ACTOR_TYPE_CREATOR
                // 2 => Form Validator // PluginFormcreatorTarget_Actor::ACTOR_TYPE_VALIDATOR
                3 => ITILActorFieldStrategy::SPECIFIC_VALUES, // PluginFormcreatorTarget_Actor::ACTOR_TYPE_PERSON
                4 => ITILActorFieldStrategy::SPECIFIC_ANSWERS, // PluginFormcreatorTarget_Actor::ACTOR_TYPE_QUESTION_PERSON
                5 => ITILActorFieldStrategy::SPECIFIC_VALUES, // PluginFormcreatorTarget_Actor::ACTOR_TYPE_GROUP
                6 => ITILActorFieldStrategy::SPECIFIC_ANSWERS, // PluginFormcreatorTarget_Actor::ACTOR_TYPE_QUESTION_GROUP
                7 => ITILActorFieldStrategy::SPECIFIC_VALUES, // PluginFormcreatorTarget_Actor::ACTOR_TYPE_SUPPLIER
                8 => ITILActorFieldStrategy::SPECIFIC_ANSWERS, // PluginFormcreatorTarget_Actor::ACTOR_TYPE_QUESTION_SUPPLIER
                9 => ITILActorFieldStrategy::SPECIFIC_ANSWERS, // PluginFormcreatorTarget_Actor::ACTOR_TYPE_QUESTION_ACTORS
                // 10 => Group from an object // PluginFormcreatorTarget_Actor::ACTOR_TYPE_GROUP_FROM_OBJECT
                // 11 => Tech group from an object // PluginFormcreatorTarget_Actor::ACTOR_TYPE_TECH_GROUP_FROM_OBJECT
                // 12 => Form author supervisor // PluginFormcreatorTarget_Actor::ACTOR_TYPE_SUPERVISOR
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
                            $mapped_item = $migration->getMappedItemTarget(
                                'PluginFormcreatorQuestion',
                                $id
                            );

                            if ($mapped_item === null) {
                                throw new InvalidArgumentException("Question '$id' not found in a target form");
                            }

                            $specific_question_ids[] = $mapped_item['items_id'];
                        }
                    }
                }
            }

            return $this->getConfig($form, [$this->getKey() => [
                ITILActorFieldConfig::STRATEGIES => array_map(
                    fn(ITILActorFieldStrategy $strategy) => $strategy->value,
                    $strategies
                ),
                ITILActorFieldConfig::SPECIFIC_ITILACTORS_IDS => $specific_itilactors_ids,
                ITILActorFieldConfig::SPECIFIC_QUESTION_IDS   => $specific_question_ids,
            ]]);
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
            $form->getQuestionsByTypes(array_map('get_class', $this->getAllowedQuestionType())),
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

                return class_uses($question_itemtype)[AssignableItem::class] ?? false;
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

    #[Override]
    public function exportDynamicConfig(
        array $config,
        AbstractCommonITILFormDestination $destination,
    ): DynamicExportDataField {
        $requirements = [];

        if (!isset($config[ITILActorFieldConfig::SPECIFIC_ITILACTORS_IDS])) {
            return parent::exportDynamicConfig($config, $destination);
        }

        $items = $config[ITILActorFieldConfig::SPECIFIC_ITILACTORS_IDS];
        foreach ($items as $itemtype => $items_ids) {
            foreach ($items_ids as $i => $item_id) {
                $item = getItemForItemtype($itemtype);
                if ($item->getFromDB($item_id)) {
                    // Insert name instead of id and register requirement
                    $requirement = DataRequirementSpecification::fromItem($item);
                    $requirements[] = $requirement;
                    $items[$itemtype][$i] = $requirement->name;
                }
            }
        }

        $config[ITILActorFieldConfig::SPECIFIC_ITILACTORS_IDS] = $items;
        return new DynamicExportDataField($config, $requirements);
    }

    #[Override]
    public static function prepareDynamicConfigDataForImport(
        array $config,
        AbstractCommonITILFormDestination $destination,
        DatabaseMapper $mapper,
    ): array {
        if (isset($config[ITILActorFieldConfig::SPECIFIC_ITILACTORS_IDS])) {
            $items = $config[ITILActorFieldConfig::SPECIFIC_ITILACTORS_IDS];
            foreach ($items as $itemtype => $items_names) {
                foreach ($items_names as $i => $item_name) {
                    $id = $mapper->getItemId($itemtype, $item_name);
                    $items[$itemtype][$i] = $id;
                }
            }
            $config[ITILActorFieldConfig::SPECIFIC_ITILACTORS_IDS] = $items;
        }

        // Check if specific questions are defined
        if (isset($config[ITILActorFieldConfig::SPECIFIC_QUESTION_IDS])) {
            $questions = $config[ITILActorFieldConfig::SPECIFIC_QUESTION_IDS];
            foreach ($questions as $i => $question) {
                $id = $mapper->getItemId(Question::class, $question);
                $questions[$i] = $id;
            }
            $config[ITILActorFieldConfig::SPECIFIC_QUESTION_IDS] = $questions;
        }

        return $config;
    }
}
