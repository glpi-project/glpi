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

use CommonITILObject;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\AnswersSet;
use Glpi\Form\Destination\AbstractCommonITILFormDestination;
use Glpi\Form\Destination\AbstractConfigField;
use Glpi\Form\Destination\FormDestination;
use Glpi\Form\Export\Context\DatabaseMapper;
use Glpi\Form\Form;
use Glpi\Form\Migration\DestinationFieldConverterInterface;
use Glpi\Form\Migration\FormMigration;
use Glpi\Form\Question;
use Glpi\Form\QuestionType\QuestionTypeUrgency;
use InvalidArgumentException;
use Override;

final class UrgencyField extends AbstractConfigField implements DestinationFieldConverterInterface
{
    #[Override]
    public function getLabel(): string
    {
        return __("Urgency");
    }

    #[Override]
    public function getWeight(): int
    {
        return 70;
    }

    #[Override]
    public function getConfigClass(): string
    {
        return UrgencyFieldConfig::class;
    }

    #[Override]
    public function renderConfigForm(
        Form $form,
        FormDestination $destination,
        JsonFieldInterface $config,
        string $input_name,
        array $display_options
    ): string {
        if (!$config instanceof UrgencyFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        $twig = TemplateRenderer::getInstance();
        return $twig->render('pages/admin/form/itil_config_fields/urgency.html.twig', [
            // Possible configuration constant that will be used to to hide/show additional fields
            'CONFIG_SPECIFIC_VALUE'  => UrgencyFieldStrategy::SPECIFIC_VALUE->value,
            'CONFIG_SPECIFIC_ANSWER' => UrgencyFieldStrategy::SPECIFIC_ANSWER->value,

            // General display options
            'options' => $display_options,

            // Specific additional config for SPECIFIC_VALUE strategy
            'specific_value_extra_field' => [
                'empty_label'     => __("Select an urgency level..."),
                'value'           => $config->getSpecificUrgency(),
                'input_name'      => $input_name . "[" . UrgencyFieldConfig::SPECIFIC_URGENCY_VALUE . "]",
                'possible_values' => $this->getUrgencyLevels(),
            ],

            // Specific additional config for SPECIFIC_ANSWER strategy
            'specific_answer_extra_field' => [
                'empty_label'     => __("Select a question..."),
                'value'           => $config->getSpecificQuestionId(),
                'input_name'      => $input_name . "[" . UrgencyFieldConfig::SPECIFIC_QUESTION_ID . "]",
                'possible_values' => $this->getUrgencyQuestionsValuesForDropdown($form),
            ],
        ]);
    }

    #[Override]
    public function applyConfiguratedValueToInputUsingAnswers(
        JsonFieldInterface $config,
        array $input,
        AnswersSet $answers_set
    ): array {
        if (!$config instanceof UrgencyFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        // Only one strategy is allowed
        $strategy = current($config->getStrategies());

        // Compute value according to strategy
        $urgency = $strategy->computeUrgency($config, $answers_set);

        // Do not edit input if invalid value was found
        $valid_values = array_keys($this->getUrgencyLevels());
        if (!in_array($urgency, $valid_values)) {
            return $input;
        }

        // Apply value
        $input['urgency'] = $urgency;
        return $input;
    }

    #[Override]
    public function getDefaultConfig(Form $form): UrgencyFieldConfig
    {
        return new UrgencyFieldConfig(UrgencyFieldStrategy::LAST_VALID_ANSWER);
    }

    #[Override]
    public function convertFieldConfig(FormMigration $migration, Form $form, array $rawData): JsonFieldInterface
    {
        switch ($rawData['urgency_rule']) {
            case 1: // PluginFormcreatorAbstractItilTarget::URGENCY_RULE_NONE
                return new UrgencyFieldConfig(
                    strategy: UrgencyFieldStrategy::FROM_TEMPLATE
                );
            case 2: // PluginFormcreatorAbstractItilTarget::URGENCY_RULE_SPECIFIC
                return new UrgencyFieldConfig(
                    strategy: UrgencyFieldStrategy::SPECIFIC_VALUE,
                    specific_urgency_value: $rawData['urgency_question']
                );
            case 3: // PluginFormcreatorAbstractItilTarget::URGENCY_RULE_ANSWER
                $mapped_item = $migration->getMappedItemTarget(
                    'PluginFormcreatorQuestion',
                    $rawData['urgency_question']
                );

                if ($mapped_item === null) {
                    throw new InvalidArgumentException("Question '{$rawData['urgency_question']}' not found in a target form");
                }

                return new UrgencyFieldConfig(
                    strategy: UrgencyFieldStrategy::SPECIFIC_ANSWER,
                    specific_question_id: $mapped_item['items_id']
                );
        }

        return $this->getDefaultConfig($form);
    }

    /**
     * Retrieve available urgency levels
     *
     * @return array
     */
    private function getUrgencyLevels(): array
    {
        global $CFG_GLPI;

        // Get the urgency levels
        $urgency_levels = array_combine(
            range(1, 5),
            array_map(fn($urgency) => CommonITILObject::getUrgencyName($urgency), range(1, 5))
        );

        // Filter out the urgency levels that are not enabled
        $urgency_levels = array_filter(
            $urgency_levels,
            fn($key) => (($CFG_GLPI['urgency_mask'] & (1 << $key)) > 0),
            ARRAY_FILTER_USE_KEY
        );

        return $urgency_levels;
    }

    public function getStrategiesForDropdown(): array
    {
        $values = [];
        foreach (UrgencyFieldStrategy::cases() as $strategies) {
            $values[$strategies->value] = $strategies->getLabel();
        }
        return $values;
    }

    private function getUrgencyQuestionsValuesForDropdown(Form $form): array
    {
        $values = [];
        $questions = $form->getQuestionsByType(QuestionTypeUrgency::class);

        foreach ($questions as $question) {
            $values[$question->getId()] = $question->fields['name'];
        }

        return $values;
    }

    #[Override]
    public function getCategory(): Category
    {
        return Category::PROPERTIES;
    }

    #[Override]
    public static function prepareDynamicConfigDataForImport(
        array $config,
        AbstractCommonITILFormDestination $destination,
        DatabaseMapper $mapper,
    ): array {
        // Check if a specific question is defined
        if (isset($config[UrgencyFieldConfig::SPECIFIC_QUESTION_ID])) {
            // Insert id
            $config[UrgencyFieldConfig::SPECIFIC_QUESTION_ID] = $mapper->getItemId(
                Question::class,
                $config[UrgencyFieldConfig::SPECIFIC_QUESTION_ID],
            );
        }

        return $config;
    }
}
