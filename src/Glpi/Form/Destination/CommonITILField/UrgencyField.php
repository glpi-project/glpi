<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

use CommonITILObject;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\AnswersSet;
use Glpi\Form\Destination\AbstractConfigField;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeUrgency;
use InvalidArgumentException;
use Override;

class UrgencyField extends AbstractConfigField
{
    #[Override]
    public function getLabel(): string
    {
        return __("Urgency");
    }

    #[Override]
    public function getWeight(): int
    {
        return 40;
    }

    #[Override]
    public function getConfigClass(): string
    {
        return UrgencyFieldConfig::class;
    }

    #[Override]
    public function renderConfigForm(
        Form $form,
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

            // Main config field
            'main_config_field' => [
                'label'           => $this->getLabel(),
                'value'           => $config->getStrategy()->value,
                'input_name'      => $input_name . "[" . UrgencyFieldConfig::STRATEGY . "]",
                'possible_values' => $this->getMainConfigurationValuesforDropdown(),
            ],

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

        // Compute value according to strategy
        $urgency = $config->getStrategy()->computeUrgency($config, $answers_set);

        // Do not edit input if invalid value was found
        $valid_values = array_keys($this->getUrgencyLevels());
        if (array_search($urgency, $valid_values) === false) {
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

    private function getUrgencyLevels(): array
    {
        return array_combine(
            range(1, 5),
            array_map(fn ($urgency) => CommonITILObject::getUrgencyName($urgency), range(1, 5))
        );
    }

    private function getMainConfigurationValuesforDropdown(): array
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
}
