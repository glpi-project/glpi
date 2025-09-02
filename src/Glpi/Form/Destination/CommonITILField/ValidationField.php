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

use CommonITILValidation;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\JsonFieldInterface;
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
use Glpi\Form\QuestionType\AbstractQuestionTypeActors;
use Glpi\Form\QuestionType\QuestionTypeAssignee;
use Glpi\Form\QuestionType\QuestionTypeItem;
use Glpi\Form\QuestionType\QuestionTypeObserver;
use Group;
use InvalidArgumentException;
use Override;
use User;

use function Safe\json_decode;

final class ValidationField extends AbstractConfigField implements DestinationFieldConverterInterface
{
    #[Override]
    public function getLabel(): string
    {
        return CommonITILValidation::getTypeName(1);
    }

    #[Override]
    public function getConfigClass(): string
    {
        return ValidationFieldConfig::class;
    }

    #[Override]
    public function renderConfigForm(
        Form $form,
        FormDestination $destination,
        JsonFieldInterface $config,
        string $input_name,
        array $display_options,
        string|int $strategy_index = 0
    ): string {
        if (!$config instanceof ValidationFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        // Get strategy config index
        if ($strategy_index === '__INDEX__') {
            $strategy_config = new ValidationFieldStrategyConfig(ValidationFieldStrategy::NO_VALIDATION);
            $specific_actors = [];
        } else {
            $strategy_config = $config->getStrategyConfigByIndex($strategy_index);
            if ($strategy_config === null) {
                return '';
            }

            // Specific actors are stored as an array of itemtype => items_ids to be generic.
            // We need to convert keys to foreign keys to be able to use them with the actors component.
            $specific_actors = [];
            foreach ($strategy_config->getSpecificActors() as $itemtype => $items_ids) {
                $specific_actors[getForeignKeyFieldForItemType($itemtype)] = $items_ids;
            }
        }

        $twig = TemplateRenderer::getInstance();
        return $twig->render('pages/admin/form/itil_config_fields/validation.html.twig', [
            // Possible configuration constant that will be used to to hide/show additional fields
            'CONFIG_SPECIFIC_VALUES'  => ValidationFieldStrategy::SPECIFIC_VALUES->value,
            'CONFIG_SPECIFIC_ACTORS'  => ValidationFieldStrategy::SPECIFIC_ACTORS->value,
            'CONFIG_SPECIFIC_ANSWERS' => ValidationFieldStrategy::SPECIFIC_ANSWERS->value,

            // General display options
            'options' => $display_options,
            'strategy_index' => $strategy_index,

            // Specific additional config for SPECIFIC_VALUES strategy
            'specific_value_extra_field' => [
                'aria_label'     => __("Select approval templates..."),
                'value'           => $strategy_config->getSpecificValidationTemplateIds(),
                'input_name'      => $input_name . "[" . $strategy_index . "][" . ValidationFieldStrategyConfig::SPECIFIC_VALIDATION_TEMPLATE_IDS . "]",
            ],

            // Specific additional config for SPECIFIC_ACTORS strategy
            'specific_values_extra_field' => [
                'values'        => $specific_actors,
                'input_name'    => $input_name . "[" . $strategy_index . "][" . ValidationFieldStrategyConfig::SPECIFIC_ACTORS . "]",
                'allowed_types' => [User::class, Group::class],
                'aria_label'    => __("Select actors..."),
            ],

            // Specific additional config for SPECIFIC_ANSWERS strategy
            'specific_answers_extra_field' => [
                'values'          => $strategy_config->getSpecificQuestionIds(),
                'input_name'      => $input_name . "[" . $strategy_index . "][" . ValidationFieldStrategyConfig::SPECIFIC_QUESTION_IDS . "]",
                'possible_values' => $this->getActorsQuestionsValuesForDropdown($form),
                'aria_label'      => __("Select questions..."),
            ],

            // Specific additional config for SPECIFIC_ACTORS and SPECIFIC_ANSWERS strategies
            'specific_validation_step_extra_field' => [
                'empty_label' => __('No validation step selected (Default step will be used)'),
                'aria_label' => __('Select validation step...'),
                'value'      => $strategy_config->getSpecificValidationStepId(),
                'input_name' => $input_name . "[" . $strategy_index . "][" . ValidationFieldStrategyConfig::SPECIFIC_VALIDATION_STEP_ID . "]",
            ],
        ]);
    }

    #[Override]
    public function applyConfiguratedValueToInputUsingAnswers(
        JsonFieldInterface $config,
        array $input,
        AnswersSet $answers_set
    ): array {
        if (!$config instanceof ValidationFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        // Compute value according to each strategy configuration
        foreach ($config->getStrategyConfigs() as $strategy_config) {
            $validations = $strategy_config->getStrategy()->getValidation($strategy_config, $answers_set);

            if (!empty($validations)) {
                foreach ($validations as $validation) {
                    $input['_add_validation'] = 0;
                    $validation_target = [
                        'validatortype'   => $validation['itemtype'],
                        'itemtype_target' => $validation['itemtype'],
                        'items_id_target' => $validation['items_id'],
                    ];

                    if ($strategy_config->getSpecificValidationStepId() > 0) {
                        $validation_target['validationsteps_id'] = $strategy_config->getSpecificValidationStepId();
                    }

                    $input['_validation_targets'][] = $validation_target;
                }
            }
        }

        return $input;
    }

    #[Override]
    public function getDefaultConfig(Form $form): ValidationFieldConfig
    {
        return new ValidationFieldConfig([
            new ValidationFieldStrategyConfig(ValidationFieldStrategy::NO_VALIDATION),
        ]);
    }

    public function getStrategiesForDropdown(): array
    {
        $values = [];
        foreach (ValidationFieldStrategy::cases() as $strategies) {
            $values[$strategies->value] = $strategies->getLabel();
        }
        return $values;
    }

    private function getActorsQuestionsValuesForDropdown(Form $form): array
    {
        $values = [];
        $questions = array_merge(
            $form->getQuestionsByType(QuestionTypeItem::class),
            $form->getQuestionsByType(QuestionTypeObserver::class),
            $form->getQuestionsByType(QuestionTypeAssignee::class),
        );
        $allowed_itemtypes = [
            User::class,
            Group::class,
        ];

        foreach ($questions as $question) {
            // Only keep questions that are allowed
            if (
                !($question->getQuestionType() instanceof AbstractQuestionTypeActors)
                && !in_array((new QuestionTypeItem())->getDefaultValueItemtype($question), $allowed_itemtypes)
            ) {
                continue;
            }

            $values[$question->getId()] = $question->fields['name'];
        }

        return $values;
    }

    #[Override]
    public function getWeight(): int
    {
        return 30;
    }

    #[Override]
    public function prepareInput(array $input): array
    {
        $input = parent::prepareInput($input);

        if (!isset($input[$this->getKey()][ValidationFieldConfig::STRATEGIES])) {
            return $input;
        }

        $strategies = $input[$this->getKey()][ValidationFieldConfig::STRATEGIES] ?? [];
        $strategy_configs = [];

        foreach ($strategies as $index => $strategy_value) {
            if (empty($strategy_value)) {
                continue;
            }

            $strategy = ValidationFieldStrategy::tryFrom($strategy_value);
            if ($strategy === null) {
                continue;
            }

            $config_data = $input[$this->getKey()][$index] ?? [];

            // Handle specific validation templates
            $specific_validation_templates = [];
            if (isset($config_data[ValidationFieldStrategyConfig::SPECIFIC_VALIDATION_TEMPLATE_IDS])) {
                $templates = $config_data[ValidationFieldStrategyConfig::SPECIFIC_VALIDATION_TEMPLATE_IDS];
                if (is_array($templates)) {
                    $specific_validation_templates = array_filter($templates, 'is_numeric');
                }
            }

            // Handle specific question IDs
            $specific_question_ids = [];
            if (isset($config_data[ValidationFieldStrategyConfig::SPECIFIC_QUESTION_IDS])) {
                $questions = $config_data[ValidationFieldStrategyConfig::SPECIFIC_QUESTION_IDS];
                if (is_array($questions)) {
                    $specific_question_ids = array_filter($questions, 'is_numeric');
                }
            }

            // Handle specific actors
            $specific_actors = [];
            if (isset($config_data[ValidationFieldStrategyConfig::SPECIFIC_ACTORS])) {
                $actors_data = $config_data[ValidationFieldStrategyConfig::SPECIFIC_ACTORS];
                if (is_array($actors_data)) {
                    $available_actor_types = [
                        User::class => User::getForeignKeyField(),
                        Group::class => Group::getForeignKeyField(),
                    ];

                    foreach ($actors_data as $actor) {
                        $actor_parts = explode('-', $actor);
                        if (
                            count($actor_parts) !== 2
                            || !in_array($actor_parts[0], $available_actor_types)
                            || !is_numeric($actor_parts[1])
                        ) {
                            continue;
                        }

                        $itemtype = array_search($actor_parts[0], $available_actor_types);
                        if ($itemtype === false) {
                            continue;
                        }

                        $specific_actors[$itemtype][] = (int) $actor_parts[1];
                    }
                }
            }

            // Handle validation step
            $validation_step_id = null;
            if (isset($config_data[ValidationFieldStrategyConfig::SPECIFIC_VALIDATION_STEP_ID])) {
                $step_id = $config_data[ValidationFieldStrategyConfig::SPECIFIC_VALIDATION_STEP_ID];
                if (is_numeric($step_id)) {
                    $validation_step_id = (int) $step_id;
                }
            }

            $strategy_configs[] = new ValidationFieldStrategyConfig(
                strategy: $strategy,
                specific_validationtemplate_ids: $specific_validation_templates,
                specific_question_ids: $specific_question_ids,
                specific_actors: $specific_actors,
                specific_validation_step_id: $validation_step_id
            );
        }

        // Replace the input with the new config structure
        $input[$this->getKey()] = new ValidationFieldConfig($strategy_configs);

        return $input;
    }

    #[Override]
    public function canHaveMultipleStrategies(): bool
    {
        return true;
    }

    public function getReusableStrategies(): array
    {
        return [
            ValidationFieldStrategy::SPECIFIC_ACTORS->value,
            ValidationFieldStrategy::SPECIFIC_ANSWERS->value,
        ];
    }

    #[Override]
    public function getCategory(): Category
    {
        return Category::TIMELINE;
    }

    #[Override]
    public function convertFieldConfig(FormMigration $migration, Form $form, array $rawData): JsonFieldInterface
    {
        if (isset($rawData['commonitil_validation_rule'])) {
            switch ($rawData['commonitil_validation_rule']) {
                case 1: // PluginFormcreatorAbstractItilTarget::VALIDATION_NONE
                    return new ValidationFieldConfig([
                        new ValidationFieldStrategyConfig(ValidationFieldStrategy::NO_VALIDATION),
                    ]);
                case 2: // PluginFormcreatorAbstractItilTarget::VALIDATION_SPECIFIC_USER_OR_GROUP
                    $validation_actors = json_decode($rawData['commonitil_validation_question'], true);
                    $actors = [];
                    if (is_array($validation_actors)) {
                        $itemtype = $validation_actors['type'] == 'user' ? User::class : Group::class;
                        $actors[$itemtype] = array_map('intval', $validation_actors['values'] ?? []);
                    }

                    return new ValidationFieldConfig([
                        new ValidationFieldStrategyConfig(
                            strategy: ValidationFieldStrategy::SPECIFIC_ACTORS,
                            specific_actors: $actors
                        ),
                    ]);
                case 3: // PluginFormcreatorAbstractItilTarget::VALIDATION_ANSWER_USER
                case 4: // PluginFormcreatorAbstractItilTarget::VALIDATION_ANSWER_GROUP
                    $question_ids = [];
                    if (is_numeric($rawData['commonitil_validation_question'] ?? null)) {
                        $mapped_item = $migration->getMappedItemTarget(
                            'PluginFormcreatorQuestion',
                            $rawData['commonitil_validation_question']
                        );

                        if ($mapped_item === null) {
                            throw new InvalidArgumentException("Question '{$rawData['commonitil_validation_question']}' not found in a target form");
                        }

                        $question_ids[] = $mapped_item['items_id'];
                    }

                    return new ValidationFieldConfig([
                        new ValidationFieldStrategyConfig(
                            strategy: ValidationFieldStrategy::SPECIFIC_ANSWERS,
                            specific_question_ids: $question_ids
                        ),
                    ]);
            }
        }

        return $this->getDefaultConfig($form);
    }

    #[Override]
    public function exportDynamicConfig(
        array $config,
        AbstractCommonITILFormDestination $destination,
    ): DynamicExportDataField {
        $requirements = [];

        foreach ($config[ValidationFieldConfig::STRATEGY_CONFIGS] as $index => $strategy_config) {
            // Register requirements for specific actors
            if ($strategy_config[ValidationFieldStrategyConfig::STRATEGY] === ValidationFieldStrategy::SPECIFIC_ACTORS->value) {
                foreach ($strategy_config[ValidationFieldStrategyConfig::SPECIFIC_ACTORS] as $itemtype => $items_ids) {
                    foreach ($items_ids as $id_index => $item_id) {
                        $item = getItemForItemtype($itemtype);
                        if ($item->getFromDB($item_id)) {
                            // Register requirement with item name
                            $requirement = DataRequirementSpecification::fromItem($item);
                            $requirements[] = $requirement;

                            // Replace item ID with name in config
                            $config[ValidationFieldConfig::STRATEGY_CONFIGS][$index][ValidationFieldStrategyConfig::SPECIFIC_ACTORS][$itemtype][$id_index] = $requirement->name;
                        }
                    }
                }
            }
        }

        return new DynamicExportDataField($config, $requirements);
    }

    #[Override]
    public static function prepareDynamicConfigDataForImport(
        array $config,
        AbstractCommonITILFormDestination $destination,
        DatabaseMapper $mapper,
    ): array {
        foreach ($config[ValidationFieldConfig::STRATEGY_CONFIGS] as $index => $strategy_config) {
            $strategy_configs = &$config[ValidationFieldConfig::STRATEGY_CONFIGS][$index];

            // Convert specific actors from names to IDs
            if ($strategy_config[ValidationFieldStrategyConfig::STRATEGY] === ValidationFieldStrategy::SPECIFIC_ACTORS->value) {
                foreach ($strategy_config[ValidationFieldStrategyConfig::SPECIFIC_ACTORS] as $itemtype => $items_names) {
                    foreach ($items_names as $i => $item_name) {
                        $id = $mapper->getItemId($itemtype, $item_name);
                        $strategy_configs[ValidationFieldStrategyConfig::SPECIFIC_ACTORS][$itemtype][$i] = $id;
                    }
                }
            }

            // Convert old question IDs to new IDs
            if ($strategy_config[ValidationFieldStrategyConfig::STRATEGY] === ValidationFieldStrategy::SPECIFIC_ANSWERS->value) {
                // Convert specific question names to IDs
                $questions = $strategy_config[ValidationFieldStrategyConfig::SPECIFIC_QUESTION_IDS] ?? [];
                foreach ($questions as $i => $question_name) {
                    $id = $mapper->getItemId(Question::class, $question_name);
                    $strategy_configs[ValidationFieldStrategyConfig::SPECIFIC_QUESTION_IDS][$i] = $id;
                }
            }
        }

        return $config;
    }
}
