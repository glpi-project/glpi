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
use ITILCategory;
use Override;

final class ITILCategoryField extends AbstractConfigField implements DestinationFieldConverterInterface
{
    #[Override]
    public function getLabel(): string
    {
        return _n('ITIL category', 'ITIL categories', 1);
    }

    #[Override]
    public function getConfigClass(): string
    {
        return ITILCategoryFieldConfig::class;
    }

    #[Override]
    public function renderConfigForm(
        Form $form,
        FormDestination $destination,
        JsonFieldInterface $config,
        string $input_name,
        array $display_options
    ): string {
        if (!$config instanceof ITILCategoryFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        $twig = TemplateRenderer::getInstance();
        return $twig->render('pages/admin/form/itil_config_fields/itilcategory.html.twig', [
            // Possible configuration constant that will be used to to hide/show additional fields
            'CONFIG_SPECIFIC_VALUE'  => ITILCategoryFieldStrategy::SPECIFIC_VALUE->value,
            'CONFIG_SPECIFIC_ANSWER' => ITILCategoryFieldStrategy::SPECIFIC_ANSWER->value,

            // General display options
            'options' => $display_options,

            // Specific additional config for SPECIFIC_ANSWER strategy
            'specific_value_extra_field' => [
                'empty_label'     => __("Select an ITIL category..."),
                'value'           => $config->getSpecificITILCategoryID() ?? 0,
                'input_name'      => $input_name . "[" . ITILCategoryFieldConfig::SPECIFIC_ITILCATEGORY_ID . "]",
            ],

            // Specific additional config for SPECIFIC_VALUE strategy
            'specific_answer_extra_field' => [
                'empty_label'     => __("Select a question..."),
                'value'           => $config->getSpecificQuestionId(),
                'input_name'      => $input_name . "[" . ITILCategoryFieldConfig::SPECIFIC_QUESTION_ID . "]",
                'possible_values' => $this->getITILCategoryQuestionsValuesForDropdown($form),
            ],
        ]);
    }

    #[Override]
    public function applyConfiguratedValueToInputUsingAnswers(
        JsonFieldInterface $config,
        array $input,
        AnswersSet $answers_set
    ): array {
        if (!$config instanceof ITILCategoryFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        // Only one strategy is allowed
        $strategy = current($config->getStrategies());

        // Compute value according to strategy
        $itilcategory_id = $strategy->getITILCategory($config, $answers_set);

        // Do not edit input if invalid value was found
        if (!ITILCategory::getById($itilcategory_id)) {
            return $input;
        }

        // Apply value
        $input['itilcategories_id'] = $itilcategory_id;
        return $input;
    }

    #[Override]
    public function getDefaultConfig(Form $form): ITILCategoryFieldConfig
    {
        return new ITILCategoryFieldConfig(
            ITILCategoryFieldStrategy::LAST_VALID_ANSWER
        );
    }

    #[Override]
    public function convertFieldConfig(FormMigration $migration, Form $form, array $rawData): JsonFieldInterface
    {
        switch ($rawData['category_rule']) {
            case 2: // PluginFormcreatorAbstractItilTarget::CATEGORY_RULE_SPECIFIC
                return new ITILCategoryFieldConfig(
                    strategy: ITILCategoryFieldStrategy::SPECIFIC_VALUE,
                    specific_itilcategory_id: $rawData['category_question']
                );
            case 3: // PluginFormcreatorAbstractItilTarget::CATEGORY_RULE_ANSWER
                $mapped_item = $migration->getMappedItemTarget(
                    'PluginFormcreatorQuestion',
                    $rawData['category_question']
                );

                if ($mapped_item === null) {
                    throw new InvalidArgumentException("Question '{$rawData['category_question']}' not found in a target form");
                }

                return new ITILCategoryFieldConfig(
                    strategy: ITILCategoryFieldStrategy::SPECIFIC_ANSWER,
                    specific_question_id: $mapped_item['items_id']
                );
            case 4: // PluginFormcreatorAbstractItilTarget::CATEGORY_RULE_LAST_ANSWER
                return new ITILCategoryFieldConfig(
                    ITILCategoryFieldStrategy::LAST_VALID_ANSWER
                );
        }

        return $this->getDefaultConfig($form);
    }

    public function getStrategiesForDropdown(): array
    {
        $values = [];
        foreach (ITILCategoryFieldStrategy::cases() as $strategies) {
            $values[$strategies->value] = $strategies->getLabel();
        }
        return $values;
    }

    private function getITILCategoryQuestionsValuesForDropdown(Form $form): array
    {
        $values = [];
        $questions = $form->getQuestionsByType(QuestionTypeItemDropdown::class);

        foreach ($questions as $question) {
            // Only keep questions that are ITIL categories
            if ((new QuestionTypeItemDropdown())->getDefaultValueItemtype($question) !== ITILCategory::getType()) {
                continue;
            }

            $values[$question->getId()] = $question->fields['name'];
        }

        return $values;
    }

    #[Override]
    public function getWeight(): int
    {
        return 40;
    }

    #[Override]
    public function getCategory(): Category
    {
        return Category::PROPERTIES;
    }

    #[Override]
    public function exportDynamicConfig(
        array $config,
        AbstractCommonITILFormDestination $destination,
    ): DynamicExportDataField {
        $fallback = parent::exportDynamicConfig($config, $destination);

        // Check if a category is defined
        $category_id = $config[ITILCategoryFieldConfig::SPECIFIC_ITILCATEGORY_ID] ?? null;
        if ($category_id === null) {
            return $fallback;
        }

        // Try to load category
        $category = ITILCategory::getById($category_id);
        if (!$category) {
            return $fallback;
        }

        // Insert category name and requirement
        $requirement = DataRequirementSpecification::fromItem($category);
        $config[ITILCategoryFieldConfig::SPECIFIC_ITILCATEGORY_ID] = $requirement->name;

        return new DynamicExportDataField($config, [$requirement]);
    }

    #[Override]
    public static function prepareDynamicConfigDataForImport(
        array $config,
        AbstractCommonITILFormDestination $destination,
        DatabaseMapper $mapper,
    ): array {
        // Check if a category is defined
        if (isset($config[ITILCategoryFieldConfig::SPECIFIC_ITILCATEGORY_ID])) {
            // Insert id
            $config[ITILCategoryFieldConfig::SPECIFIC_ITILCATEGORY_ID] = $mapper->getItemId(
                ITILCategory::class,
                $config[ITILCategoryFieldConfig::SPECIFIC_ITILCATEGORY_ID],
            );
        }

        // Check if a specific question is defined
        if (isset($config[ITILCategoryFieldConfig::SPECIFIC_QUESTION_ID])) {
            // Insert id
            $config[ITILCategoryFieldConfig::SPECIFIC_QUESTION_ID] = $mapper->getItemId(
                Question::class,
                $config[ITILCategoryFieldConfig::SPECIFIC_QUESTION_ID],
            );
        }

        return $config;
    }
}
