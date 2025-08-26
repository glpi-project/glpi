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
use Glpi\Form\QuestionType\QuestionTypeItemDropdown;
use InvalidArgumentException;
use Location;
use Override;

final class LocationField extends AbstractConfigField implements DestinationFieldConverterInterface
{
    #[Override]
    public function getLabel(): string
    {
        return _n('Location', 'Locations', 1);
    }

    #[Override]
    public function getConfigClass(): string
    {
        return LocationFieldConfig::class;
    }

    #[Override]
    public function renderConfigForm(
        Form $form,
        FormDestination $destination,
        JsonFieldInterface $config,
        string $input_name,
        array $display_options
    ): string {
        if (!$config instanceof LocationFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        $twig = TemplateRenderer::getInstance();
        return $twig->render('pages/admin/form/itil_config_fields/location.html.twig', [
            // Possible configuration constant that will be used to to hide/show additional fields
            'CONFIG_SPECIFIC_VALUE'  => LocationFieldStrategy::SPECIFIC_VALUE->value,
            'CONFIG_SPECIFIC_ANSWER' => LocationFieldStrategy::SPECIFIC_ANSWER->value,

            // General display options
            'options' => $display_options,

            // Specific additional config for SPECIFIC_ANSWER strategy
            'specific_value_extra_field' => [
                'empty_label'     => __("Select a location..."),
                'value'           => $config->getSpecificLocationID(),
                'input_name'      => $input_name . "[" . LocationFieldConfig::SPECIFIC_LOCATION_ID . "]",
            ],

            // Specific additional config for SPECIFIC_VALUE strategy
            'specific_answer_extra_field' => [
                'empty_label'     => __("Select a question..."),
                'value'           => $config->getSpecificQuestionId(),
                'input_name'      => $input_name . "[" . LocationFieldConfig::SPECIFIC_QUESTION_ID . "]",
                'possible_values' => $this->getLocationQuestionsValuesForDropdown($form),
            ],
        ]);
    }

    #[Override]
    public function applyConfiguratedValueToInputUsingAnswers(
        JsonFieldInterface $config,
        array $input,
        AnswersSet $answers_set
    ): array {
        if (!$config instanceof LocationFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        // Only one strategy is allowed
        $strategy = current($config->getStrategies());

        // Compute value according to strategy
        $location_id = $strategy->getLocationID($config, $answers_set);

        // Do not edit input if invalid value was found
        if (Location::getById($location_id) === false) {
            return $input;
        }

        // Apply value
        $input['locations_id'] = $location_id;
        return $input;
    }

    #[Override]
    public function getDefaultConfig(Form $form): LocationFieldConfig
    {
        return new LocationFieldConfig(
            LocationFieldStrategy::LAST_VALID_ANSWER
        );
    }

    public function getStrategiesForDropdown(): array
    {
        $values = [];
        foreach (LocationFieldStrategy::cases() as $strategies) {
            $values[$strategies->value] = $strategies->getLabel();
        }
        return $values;
    }

    private function getLocationQuestionsValuesForDropdown(Form $form): array
    {
        $values = [];
        $questions = $form->getQuestionsByType(QuestionTypeItemDropdown::class);

        foreach ($questions as $question) {
            // Only keep questions that are Location
            if ((new QuestionTypeItemDropdown())->getDefaultValueItemtype($question) !== Location::getType()) {
                continue;
            }

            $values[$question->getId()] = $question->fields['name'];
        }

        return $values;
    }

    #[Override]
    public function getWeight(): int
    {
        return 80;
    }

    #[Override]
    public function getCategory(): Category
    {
        return Category::PROPERTIES;
    }

    #[Override]
    public function convertFieldConfig(FormMigration $migration, Form $form, array $rawData): JsonFieldInterface
    {
        if (isset($rawData['location_rule'])) {
            switch ($rawData['location_rule']) {
                case 1: // PluginFormcreatorAbstractItilTarget::LOCATION_RULE_NONE
                    return new LocationFieldConfig(
                        LocationFieldStrategy::FROM_TEMPLATE
                    );
                case 2: // PluginFormcreatorAbstractItilTarget::LOCATION_RULE_SPECIFIC
                    return new LocationFieldConfig(
                        strategy: LocationFieldStrategy::SPECIFIC_VALUE,
                        specific_location_id: $rawData['location_question']
                    );
                case 3: // PluginFormcreatorAbstractItilTarget::LOCATION_RULE_ANSWER
                    $mapped_item = $migration->getMappedItemTarget(
                        'PluginFormcreatorQuestion',
                        $rawData['location_question']
                    );

                    if ($mapped_item === null) {
                        throw new InvalidArgumentException("Question '{$rawData['location_question']}' not found in a target form");
                    }

                    return new LocationFieldConfig(
                        strategy: LocationFieldStrategy::SPECIFIC_ANSWER,
                        specific_question_id: $mapped_item['items_id']
                    );
                case 4: // PluginFormcreatorAbstractItilTarget::LOCATION_RULE_LAST_ANSWER
                    return new LocationFieldConfig(
                        LocationFieldStrategy::LAST_VALID_ANSWER
                    );
            }
        }

        return $this->getDefaultConfig($form);
    }

    #[Override]
    public function exportDynamicConfig(
        array $config,
        AbstractCommonITILFormDestination $destination,
    ): DynamicExportDataField {
        $fallback = parent::exportDynamicConfig($config, $destination);

        // Check if a location is defined
        $location_id = $config[LocationFieldConfig::SPECIFIC_LOCATION_ID] ?? null;
        if ($location_id === null) {
            return $fallback;
        }

        // Try to load location
        $location = Location::getById($location_id);
        if (!$location) {
            return $fallback;
        }

        // Insert location name and requirement
        $requirement = DataRequirementSpecification::fromItem($location);
        $config[LocationFieldConfig::SPECIFIC_LOCATION_ID] = $requirement->name;

        return new DynamicExportDataField($config, [$requirement]);
    }

    #[Override]
    public static function prepareDynamicConfigDataForImport(
        array $config,
        AbstractCommonITILFormDestination $destination,
        DatabaseMapper $mapper,
    ): array {
        // Check if a location is defined
        if (isset($config[LocationFieldConfig::SPECIFIC_LOCATION_ID])) {
            // Insert id
            $config[LocationFieldConfig::SPECIFIC_LOCATION_ID] = $mapper->getItemId(
                Location::class,
                $config[LocationFieldConfig::SPECIFIC_LOCATION_ID],
            );
        }

        // Check if a specific question is defined
        if (isset($config[LocationFieldConfig::SPECIFIC_QUESTION_ID])) {
            // Insert id
            $config[LocationFieldConfig::SPECIFIC_QUESTION_ID] = $mapper->getItemId(
                Question::class,
                $config[LocationFieldConfig::SPECIFIC_QUESTION_ID],
            );
        }

        return $config;
    }
}
