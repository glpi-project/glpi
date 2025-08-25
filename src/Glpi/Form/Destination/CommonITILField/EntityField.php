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

use Entity;
use Exception;
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
use Glpi\Form\QuestionType\QuestionTypeItem;
use InvalidArgumentException;
use Override;

final class EntityField extends AbstractConfigField implements DestinationFieldConverterInterface
{
    #[Override]
    public function getLabel(): string
    {
        return _n("Entity", "Entities", 1);
    }

    #[Override]
    public function getConfigClass(): string
    {
        return EntityFieldConfig::class;
    }

    #[Override]
    public function renderConfigForm(
        Form $form,
        FormDestination $destination,
        JsonFieldInterface $config,
        string $input_name,
        array $display_options
    ): string {
        if (!$config instanceof EntityFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        $twig = TemplateRenderer::getInstance();
        return $twig->render('pages/admin/form/itil_config_fields/entity.html.twig', [
            // Possible configuration constant that will be used to to hide/show additional fields
            'CONFIG_SPECIFIC_VALUE'  => EntityFieldStrategy::SPECIFIC_VALUE->value,
            'CONFIG_SPECIFIC_ANSWER' => EntityFieldStrategy::SPECIFIC_ANSWER->value,

            // General display options
            'options' => $display_options,

            // Specific additional config for SPECIFIC_VALUE strategy
            'specific_value_extra_field' => [
                'aria_label'      => __("Select an entity..."),
                'value'           => $config->getSpecificEntityId() ?? 0,
                'input_name'      => $input_name . "[" . EntityFieldConfig::SPECIFIC_ENTITY_ID . "]",
            ],

            // Specific additional config for SPECIFIC_ANSWER strategy
            'specific_answer_extra_field' => [
                'empty_label'     => __("Select a question..."),
                'value'           => $config->getSpecificQuestionId(),
                'input_name'      => $input_name . "[" . EntityFieldConfig::SPECIFIC_QUESTION_ID . "]",
                'possible_values' => $this->getEntityQuestionsValuesForDropdown($form),
            ],
        ]);
    }

    #[Override]
    public function applyConfiguratedValueToInputUsingAnswers(
        JsonFieldInterface $config,
        array $input,
        AnswersSet $answers_set
    ): array {
        if (!$config instanceof EntityFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        // Only one strategy is allowed
        $strategy = current($config->getStrategies());

        // Compute value according to strategy
        $entity_id = $strategy->getEntityID($config, $answers_set);

        // We always need a valid value for entities
        if (Entity::getById($entity_id) === false) {
            throw new Exception("Invalid entity: $entity_id");
        }

        // Apply value
        $input['entities_id'] = $entity_id;
        return $input;
    }

    #[Override]
    public function getDefaultConfig(Form $form): EntityFieldConfig
    {
        // Return last valid answer by default and fallback
        // to form entity if no valid answer was found
        $valid_answers = array_filter(
            $form->getQuestionsByType(
                QuestionTypeItem::class
            ),
            fn($question) => (new QuestionTypeItem())->getDefaultValueItemtype($question) === Entity::getType()
        );

        if (count($valid_answers) == 0) {
            return new EntityFieldConfig(
                EntityFieldStrategy::FORM_FILLER
            );
        }

        return new EntityFieldConfig(
            EntityFieldStrategy::LAST_VALID_ANSWER
        );
    }

    public function getStrategiesForDropdown(): array
    {
        $values = [];
        foreach (EntityFieldStrategy::cases() as $strategies) {
            $values[$strategies->value] = $strategies->getLabel();
        }
        return $values;
    }

    private function getEntityQuestionsValuesForDropdown(Form $form): array
    {
        $values = [];
        $questions = $form->getQuestionsByType(QuestionTypeItem::class);

        foreach ($questions as $question) {
            // Only keep questions that are Entity
            if ((new QuestionTypeItem())->getDefaultValueItemtype($question) !== Entity::getType()) {
                continue;
            }

            $values[$question->getId()] = $question->fields['name'];
        }

        return $values;
    }

    #[Override]
    public function getWeight(): int
    {
        return 20;
    }

    #[Override]
    public function getCategory(): Category
    {
        return Category::PROPERTIES;
    }

    #[Override]
    public function convertFieldConfig(FormMigration $migration, Form $form, array $rawData): JsonFieldInterface
    {
        /**
         * FormCreator implements many strategies to determine the entity.
         * Some strategies are not 100% supported by the new form system:
         * - 2. Default requester user's entity
         *  This strategy take the entity of the first requester user and fallback to the form filler entity.
         *
         * Strategies that are not supported:
         * - 3. First dynamic requester user's entity (alphabetical) -- Must be implemented
         * - 4. Last dynamic requester user's entity (alphabetical) -- Must be implemented
         * - 6. Default entity of the validator
         * - 8. Default entity of a user type question answer
         */

        switch ($rawData['destination_entity']) {
            case 1: // PluginFormcreatorAbstractTarget::DESTINATION_ENTITY_CURRENT
            case 2: // PluginFormcreatorAbstractTarget::DESTINATION_ENTITY_REQUESTER
                return new EntityFieldConfig(
                    EntityFieldStrategy::FORM_FILLER,
                );
            case 5: // PluginFormcreatorAbstractTarget::DESTINATION_ENTITY_FORM
                return new EntityFieldConfig(
                    EntityFieldStrategy::FROM_FORM,
                );
            case 7: // PluginFormcreatorAbstractTarget::DESTINATION_ENTITY_SPECIFIC
                return new EntityFieldConfig(
                    strategy: EntityFieldStrategy::SPECIFIC_VALUE,
                    specific_entity_id: $rawData['destination_entity_value']
                );
            case 9: // PluginFormcreatorAbstractTarget::DESTINATION_ENTITY_ENTITY_FROM_OBJECT
                $mapped_item = $migration->getMappedItemTarget(
                    'PluginFormcreatorQuestion',
                    $rawData['destination_entity_value']
                );

                if ($mapped_item === null) {
                    throw new InvalidArgumentException("Question '{$rawData['destination_entity_value']}' not found in a target form");
                }

                return new EntityFieldConfig(
                    strategy: EntityFieldStrategy::SPECIFIC_ANSWER,
                    specific_question_id: $mapped_item['items_id']
                );
        }

        return $this->getDefaultConfig($form);
    }

    #[Override]
    public function exportDynamicConfig(
        array $config,
        AbstractCommonITILFormDestination $destination,
    ): DynamicExportDataField {
        $fallback = parent::exportDynamicConfig($config, $destination);

        // Check if an entity is defined
        $entity_id = $config[EntityFieldConfig::SPECIFIC_ENTITY_ID] ?? null;
        if ($entity_id === null) {
            return $fallback;
        }

        // Try to load entity
        $entity = Entity::getById($entity_id);
        if (!$entity) {
            return $fallback;
        }

        // Insert entity name and requirement
        $requirement = DataRequirementSpecification::fromItem($entity);
        $config[EntityFieldConfig::SPECIFIC_ENTITY_ID] = $requirement->name;

        return new DynamicExportDataField($config, [$requirement]);
    }

    #[Override]
    public static function prepareDynamicConfigDataForImport(
        array $config,
        AbstractCommonITILFormDestination $destination,
        DatabaseMapper $mapper,
    ): array {
        // Check if an entity is defined
        if (isset($config[EntityFieldConfig::SPECIFIC_ENTITY_ID])) {
            // Insert id
            $config[EntityFieldConfig::SPECIFIC_ENTITY_ID] = $mapper->getItemId(
                Entity::class,
                $config[EntityFieldConfig::SPECIFIC_ENTITY_ID],
            );
        }

        // Check if a specific question is defined
        if (isset($config[EntityFieldConfig::SPECIFIC_QUESTION_ID])) {
            // Insert id
            $config[EntityFieldConfig::SPECIFIC_QUESTION_ID] = $mapper->getItemId(
                Question::class,
                $config[EntityFieldConfig::SPECIFIC_QUESTION_ID],
            );
        }

        return $config;
    }
}
