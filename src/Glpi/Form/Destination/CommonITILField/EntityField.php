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

use Entity;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\AnswersSet;
use Glpi\Form\Destination\AbstractConfigField;
use Glpi\Form\Form;
use Glpi\Form\Migration\DestinationFieldConverterInterface;
use Glpi\Form\Migration\FormMigration;
use Glpi\Form\QuestionType\QuestionTypeItem;
use InvalidArgumentException;
use Override;

class EntityField extends AbstractConfigField implements DestinationFieldConverterInterface
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

        // Do not edit input if invalid value was found
        if (Entity::getById($entity_id) === false) {
            return $input;
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
         * Strategies that are supported:
         * - 3. First dynamic requester user's entity (alphabetical) -- Must be implemented
         * - 4. Last dynamic requester user's entity (alphabetical) -- Must be implemented
         * - 6. Default entity of the validator
         * - 8. Default entity of a user type question answer
         */

        switch ($rawData['destination_entity']) {
            case 1:
            case 2:
                return new EntityFieldConfig(
                    EntityFieldStrategy::FORM_FILLER,
                );
            case 5:
                return new EntityFieldConfig(
                    EntityFieldStrategy::FROM_FORM,
                );
            case 7:
                return new EntityFieldConfig(
                    strategy: EntityFieldStrategy::SPECIFIC_VALUE,
                    specific_entity_id: $rawData['destination_entity_value']
                );
            case 9:
                return new EntityFieldConfig(
                    strategy: EntityFieldStrategy::SPECIFIC_ANSWER,
                    specific_question_id: $migration->getMappedItemTarget(
                        'PluginFormcreatorQuestion',
                        $rawData['destination_entity_value']
                    )['items_id']
                );
        }

        return $this->getDefaultConfig($form);
    }
}
