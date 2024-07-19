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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Form\AnswersSet;
use Glpi\Form\Destination\AbstractConfigField;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeRequestType;
use Override;
use Ticket;

class RequestTypeField extends AbstractConfigField
{
    // Main configuration main
    public const CONFIG_FROM_TEMPLATE = 'from_template_or_default';
    public const CONFIG_SPECIFIC_VALUE = 'specific_value';
    public const CONFIG_SPECIFIC_ANSWER = 'specific_answer';
    public const CONFIG_LAST_VALID_ANSWER = 'last_valid_answer';

    // Secondary config value that is used when the main value is CONFIG_SPECIFIC_VALUE
    public const EXTRA_CONFIG_REQUEST_TYPE = 'specific_request_type';

    // Secondary config value that is used when the main value is CONFIG_SPECIFIC_ANSWER
    public const EXTRA_CONFIG_QUESTION_ID = 'specific_question_id';

    #[Override]
    public function getKey(): string
    {
        return 'request_type';
    }

    #[Override]
    public function getLabel(): string
    {
        return __("Request type");
    }

    #[Override]
    public function renderConfigForm(
        Form $form,
        mixed $configurated_value,
        string $input_name,
        array $display_options
    ): string {
        if (!is_array($configurated_value)) {
            $configurated_value = $this->getDefaultValue($form);
        }

        $parameters = [
            // Possible configuration constant that will be used to to hide/show additional fields
            'CONFIG_SPECIFIC_VALUE'  => self::CONFIG_SPECIFIC_VALUE,
            'CONFIG_SPECIFIC_ANSWER' => self::CONFIG_SPECIFIC_ANSWER,

            // General display options
            'options' => $display_options,

            // Main config field
            'main_config_field' => [
                'label'           => $this->getLabel(),
                'value'           => $configurated_value['value'] ?? $this->getDefaultValue($form)['value'],
                'input_name'      => $input_name . "[value]",
                'possible_values' => $this->getMainConfigurationValuesforDropdown(),
            ],

            // Specific additional confog for CONFIG_SPECIFIC_VALUE
            'specific_value_extra_field' => [
                'empty_label'     => __("Select a request type..."),
                'value'           => $this->getConfiguratedSpecificValue($configurated_value),
                'input_name'      => $input_name . "[" . self::EXTRA_CONFIG_REQUEST_TYPE . "]",
                'possible_values' => Ticket::getTypes(),
            ],

            // Specific additional confog for CONFIG_SPECIFIC_ANSWER
            'specific_answer_extra_field' => [
                'empty_label'     => __("Select a question..."),
                'value'           => $this->getConfiguratedQuestionId($configurated_value),
                'input_name'      => $input_name . "[" . self::EXTRA_CONFIG_QUESTION_ID . "]",
                'possible_values' => $this->getRequestTypeQuestionsValuesForDropdown($form),
            ],
        ];

        $template = <<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}

            {{ fields.dropdownArrayField(
                main_config_field.input_name,
                main_config_field.value,
                main_config_field.possible_values,
                main_config_field.label,
                options
            ) }}

            <div
                {% if main_config_field.value != CONFIG_SPECIFIC_VALUE %}
                    class="d-none"
                {% endif %}
                data-glpi-parent-dropdown="{{ main_config_field.input_name }}"
                data-glpi-parent-dropdown-condition="{{ CONFIG_SPECIFIC_VALUE }}"
            >
                {{ fields.dropdownArrayField(
                    specific_value_extra_field.input_name,
                    specific_value_extra_field.value,
                    specific_value_extra_field.possible_values,
                    "",
                    options|merge({
                        no_label: true,
                        display_emptychoice: true,
                        emptylabel: specific_value_extra_field.empty_label,
                        aria_label: specific_value_extra_field.empty_label,
                    })
                ) }}
            </div>

            <div
                {% if main_config_field.value != CONFIG_SPECIFIC_ANSWER %}
                    class="d-none"
                {% endif %}
                data-glpi-parent-dropdown="{{ main_config_field.input_name }}"
                data-glpi-parent-dropdown-condition="{{ CONFIG_SPECIFIC_ANSWER }}"
            >
                {{ fields.dropdownArrayField(
                    specific_answer_extra_field.input_name,
                    specific_answer_extra_field.value,
                    specific_answer_extra_field.possible_values,
                    "",
                    options|merge({
                        no_label: true,
                        display_emptychoice: true,
                        emptylabel: specific_answer_extra_field.empty_label,
                        aria_label: specific_answer_extra_field.empty_label,
                    })
                ) }}
            </div>
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, $parameters);
    }

    #[Override]
    public function applyConfiguratedValueToInputUsingAnswers(
        mixed $configurated_value,
        array $input,
        AnswersSet $answers_set
    ): array {
        if (is_null($configurated_value)) {
            return $input;
        }

        switch ($configurated_value['value']) {
            default:
            case self::CONFIG_FROM_TEMPLATE:
                // Let the template apply its default value by itself.
                $value = null;
                break;

            case self::CONFIG_SPECIFIC_VALUE:
                $value = $this->getConfiguratedSpecificValue($configurated_value);
                break;

            case self::CONFIG_SPECIFIC_ANSWER:
                $question_id = $this->getConfiguratedQuestionId($configurated_value);
                if ($question_id === null) {
                    $value = null;
                    break;
                }

                $answer = $answers_set->getAnswerByQuestionId($question_id);
                if ($answer === null) {
                    $value = null;
                    break;
                }

                $value = $answer->getRawAnswer();
                break;

            case self::CONFIG_LAST_VALID_ANSWER:
                $valid_answers = $answers_set->getAnswersByType(
                    QuestionTypeRequestType::class
                );

                if (count($valid_answers) == 0) {
                    $value = null;
                    break;
                }

                $answer = end($valid_answers);
                $value = $answer->getRawAnswer();
                break;
        }

        // Do not edit input if invalid value was found
        $valid_values = [Ticket::INCIDENT_TYPE, Ticket::DEMAND_TYPE];
        if (!array_search($value, $valid_values)) {
            return $input;
        }

        // Apply value
        $input['type'] = $value;
        return $input;
    }

    #[Override]
    public function getDefaultValue(Form $form): mixed
    {
        return ['value' => self::CONFIG_LAST_VALID_ANSWER];
    }

    private function getConfiguratedSpecificValue(array $config): ?int
    {
        $value = $config[self::EXTRA_CONFIG_REQUEST_TYPE] ?? null;

        $valid_values = [Ticket::INCIDENT_TYPE, Ticket::DEMAND_TYPE];
        if (!array_search($value, $valid_values)) {
            return null;
        }

        return $value;
    }

    private function getConfiguratedQuestionId(array $config): ?int
    {
        $value = $config[self::EXTRA_CONFIG_QUESTION_ID] ?? null;

        if (!is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    private function getMainConfigurationValuesforDropdown(): array
    {
        return [
            self::CONFIG_FROM_TEMPLATE     => __("From template"),
            self::CONFIG_SPECIFIC_VALUE    => __("Specific request type"),
            self::CONFIG_SPECIFIC_ANSWER   => __("Answer from a specific question"),
            self::CONFIG_LAST_VALID_ANSWER => __('Answer to last "Request type" question'),
        ];
    }

    private function getRequestTypeQuestionsValuesForDropdown(Form $form): array
    {
        $values = [];
        $questions = $form->getQuestionsByType(QuestionTypeRequestType::class);

        foreach ($questions as $question) {
            $values[$question->getId()] = $question->fields['name'];
        }

        return $values;
    }
}
