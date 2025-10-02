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

namespace GlpiPlugin\Tester\Form;

use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\AnswersSet;
use Glpi\Form\Destination\AbstractConfigField;
use Glpi\Form\Destination\CommonITILField\Category;
use Glpi\Form\Destination\FormDestination;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use InvalidArgumentException;
use Override;

final class ExternalIDField extends AbstractConfigField
{
    #[Override]
    public function getLabel(): string
    {
        return __('External ID');
    }

    #[Override]
    public function getConfigClass(): string
    {
        return ExternalIDFieldConfig::class;
    }

    #[Override]
    public function getWeight(): int
    {
        return 1000;
    }

    #[Override]
    public function getCategory(): Category
    {
        return Category::PROPERTIES;
    }

    #[Override]
    public function getDefaultConfig(Form $form): ExternalIDFieldConfig
    {
        return new ExternalIDFieldConfig(
            ExternalIDFieldStrategy::NO_EXTERNAL_ID
        );
    }

    #[Override]
    public function renderConfigForm(
        Form $form,
        FormDestination $destination,
        JsonFieldInterface $config,
        string $input_name,
        array $display_options
    ): string {
        if (!$config instanceof ExternalIDFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        $twig = TemplateRenderer::getInstance();
        return $twig->render('@tester/external_id_config_field.html.twig', [
            // Possible configuration constant that will be used to to hide/show additional fields
            'CONFIG_SPECIFIC_VALUE'  => ExternalIDFieldStrategy::SPECIFIC_VALUE->value,
            'CONFIG_SPECIFIC_ANSWER' => ExternalIDFieldStrategy::SPECIFIC_ANSWER->value,

            // General display options
            'options' => $display_options,

            // Specific additional config for SPECIFIC_VALUE strategy
            'specific_value_extra_field' => [
                'value'      => $config->getSpecificExternalID() ?? '',
                'input_name' => $input_name . "[" . ExternalIDFieldConfig::SPECIFIC_EXTERNAL_ID . "]",
                'aria_label' => __("Specific external ID"),
            ],

            // Specific additional config for SPECIFIC_ANSWER strategy
            'specific_answer_extra_field' => [
                'empty_label'     => __("Select a question..."),
                'value'           => $config->getSpecificQuestionId(),
                'input_name'      => $input_name . "[" . ExternalIDFieldConfig::SPECIFIC_QUESTION_ID . "]",
                'possible_values' => $this->getShortTextQuestionsValuesForDropdown($form),
            ],
        ]);
    }

    #[Override]
    public function applyConfiguratedValueToInputUsingAnswers(
        JsonFieldInterface $config,
        array $input,
        AnswersSet $answers_set
    ): array {
        if (!$config instanceof ExternalIDFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        // Only one strategy is allowed
        $strategy = current($config->getStrategies());

        // Compute value according to strategy
        $external_id = $strategy->getExternalID($config, $answers_set);

        // Apply value
        $input['externalid'] = $external_id;
        return $input;
    }

    public function getStrategiesForDropdown(): array
    {
        $values = [];
        foreach (ExternalIDFieldStrategy::cases() as $strategies) {
            $values[$strategies->value] = $strategies->getLabel();
        }
        return $values;
    }

    private function getShortTextQuestionsValuesForDropdown(Form $form): array
    {
        $values = [];
        $questions = $form->getQuestionsByType(QuestionTypeShortText::class);

        foreach ($questions as $question) {
            $values[$question->getId()] = $question->fields['name'];
        }

        return $values;
    }
}
