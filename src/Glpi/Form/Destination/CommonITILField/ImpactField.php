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

namespace Glpi\Form\Destination\CommonITILField;

use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\AnswersSet;
use Glpi\Form\Destination\AbstractCommonITILFormDestination;
use Glpi\Form\Destination\AbstractConfigField;
use Glpi\Form\Destination\FormDestination;
use Glpi\Form\Export\Context\DatabaseMapper;
use Glpi\Form\Form;
use Glpi\Form\Question;
use Glpi\Form\QuestionType\QuestionTypeImpact;
use Glpi\ImpactLevel;
use InvalidArgumentException;
use Override;

final class ImpactField extends AbstractConfigField
{
    #[Override]
    public function getLabel(): string
    {
        return __("Impact");
    }

    #[Override]
    public function getWeight(): int
    {
        return 75;
    }

    #[Override]
    public function getConfigClass(): string
    {
        return ImpactFieldConfig::class;
    }

    /**
     * @param array<string, mixed> $display_options
     */
    #[Override]
    public function renderConfigForm(
        Form $form,
        FormDestination $destination,
        JsonFieldInterface $config,
        string $input_name,
        array $display_options
    ): string {
        if (!$config instanceof ImpactFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        $twig = TemplateRenderer::getInstance();
        return $twig->render('pages/admin/form/itil_config_fields/impact.html.twig', [
            // Possible configuration constant that will be used to to hide/show additional fields
            'CONFIG_SPECIFIC_VALUE'  => ImpactFieldStrategy::SPECIFIC_VALUE->value,
            'CONFIG_SPECIFIC_ANSWER' => ImpactFieldStrategy::SPECIFIC_ANSWER->value,

            // General display options
            'options' => $display_options,

            // Specific additional config for SPECIFIC_VALUE strategy
            'specific_value_extra_field' => [
                'empty_label'     => __("Select an impact level..."),
                'value'           => $config->getSpecificImpact(),
                'input_name'      => $input_name . "[" . ImpactFieldConfig::SPECIFIC_IMPACT_VALUE . "]",
                'possible_values' => ImpactLevel::getEnabledImpactValuesForDropdown(),
            ],

            // Specific additional config for SPECIFIC_ANSWER strategy
            'specific_answer_extra_field' => [
                'empty_label'     => __("Select a question..."),
                'value'           => $config->getSpecificQuestionId(),
                'input_name'      => $input_name . "[" . ImpactFieldConfig::SPECIFIC_QUESTION_ID . "]",
                'possible_values' => $this->getImpactQuestionsValuesForDropdown($form),
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    #[Override]
    public function applyConfiguratedValueToInputUsingAnswers(
        JsonFieldInterface $config,
        array $input,
        AnswersSet $answers_set
    ): array {
        if (!$config instanceof ImpactFieldConfig) {
            throw new InvalidArgumentException("Unexpected config class");
        }

        // Only one strategy is allowed
        $strategy = current($config->getStrategies());

        // Compute value according to strategy
        $impact = $strategy->computeImpact($config, $answers_set);

        // Do not edit input if invalid value was found
        $valid_values = array_keys(ImpactLevel::getEnabledImpactValuesForDropdown());
        if (!in_array($impact, $valid_values)) {
            return $input;
        }

        // Apply value
        $input['impact'] = $impact;
        return $input;
    }

    #[Override]
    public function getDefaultConfig(Form $form): ImpactFieldConfig
    {
        return new ImpactFieldConfig(ImpactFieldStrategy::LAST_VALID_ANSWER);
    }

    /**
     * @return array<string, string>
     */
    public function getStrategiesForDropdown(): array
    {
        $values = [];
        foreach (ImpactFieldStrategy::cases() as $strategies) {
            $values[$strategies->value] = $strategies->getLabel();
        }
        return $values;
    }

    /**
     * @return array<int, string>
     */
    private function getImpactQuestionsValuesForDropdown(Form $form): array
    {
        $values = [];
        $questions = $form->getQuestionsByType(QuestionTypeImpact::class);

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

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    #[Override]
    public static function prepareDynamicConfigDataForImport(
        array $config,
        AbstractCommonITILFormDestination $destination,
        DatabaseMapper $mapper,
    ): array {
        // Check if a specific question is defined
        if (isset($config[ImpactFieldConfig::SPECIFIC_QUESTION_ID])) {
            // Insert id
            $config[ImpactFieldConfig::SPECIFIC_QUESTION_ID] = $mapper->getItemId(
                Question::class,
                $config[ImpactFieldConfig::SPECIFIC_QUESTION_ID],
            );
        }

        return $config;
    }
}
