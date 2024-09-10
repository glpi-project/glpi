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

namespace Glpi\Form\QuestionType;

use DateTime;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Form\Question;
use Override;

/**
 * Short answers are single line inputs used to answer simple questions.
 */
class QuestionTypeDateTime extends AbstractQuestionType
{
    #[Override]
    public function getCategory(): QuestionTypeCategory
    {
        return QuestionTypeCategory::DATE_AND_TIME;
    }

    public function getInputType(?Question $question, bool $ignoreDefaultValueIsCurrentTime = false): string
    {
        if (
            $question !== null
            && $this->isDefaultValueCurrentTime($question)
            && !$ignoreDefaultValueIsCurrentTime
        ) {
            return 'text';
        }

        if (
            $question === null
            || !$this->isTimeEnabled($question)
        ) {
            return 'date';
        }

        if ($this->isDateEnabled($question)) {
            return 'datetime-local';
        }

        return 'time';
    }

    public function getDefaultValue(?Question $question): ?string
    {
        $value = '';
        if ($question !== null) {
            if ($this->isDefaultValueCurrentTime($question)) {
                switch ($this->getInputType($question, true)) {
                    case 'date':
                        $value = (new DateTime())->format('Y-m-d');
                        break;
                    case 'time':
                        $value = (new DateTime())->format('H:i');
                        break;
                    case 'datetime-local':
                        $value = (new DateTime())->format('Y-m-d\TH:i');
                        break;
                }
            } else {
                $value = $question->fields['default_value'];
            }
        }

        return $value;
    }

    public function getPlaceholders(): array
    {
        return [
            'input'         => [
                'date'            => _n('Date', 'Dates', 1),
                'time'            => _n('Time', 'Times', 1),
                'datetime-local'  => __('Date and time'),
            ],
            'default_value' => [
                'date'            => __('Current date'),
                'time'            => __('Current time'),
                'datetime-local'  => __('Current date and time'),
            ]
        ];
    }

    public function formatAnswer(string $answer): string
    {
        if (str_contains($answer, 'T')) {
            return (new DateTime($answer))->format('Y-m-d H:i');
        }

        return $answer;
    }

    public function isDefaultValueCurrentTime(?Question $question): bool
    {
        if ($question === null) {
            return false;
        }

        return $question->getExtraDatas()['is_default_value_current_time'] ?? false;
    }

    public function isDateEnabled(?Question $question): bool
    {
        if ($question === null) {
            return true;
        }

        return $question->getExtraDatas()['is_date_enabled'] ?? true;
    }

    public function isTimeEnabled(?Question $question): bool
    {
        if ($question === null) {
            return false;
        }

        return $question->getExtraDatas()['is_time_enabled'] ?? false;
    }

    #[Override]
    public function validateExtraDataInput(array $input): bool
    {
        $allowed_keys = [
            'is_default_value_current_time',
            'is_date_enabled',
            'is_time_enabled'
        ];

        return empty(array_diff(array_keys($input), $allowed_keys))
            && array_reduce($input, fn($carry, $value) => $carry && preg_match('/^[01]$/', $value), true);
    }

    #[Override]
    public function renderAdministrationTemplate(?Question $question): string
    {
        $template = <<<TWIG
            {% set rand = random() %}

            <div class="row g-2">
                <div class="col-5">
                    <input
                        class="form-control mb-2"
                        type="{{ input_type }}"
                        id="date_input_{{ rand }}"
                        name="default_value"
                        placeholder="{{ placeholders.input[input_type_ignore_text] }}"
                        value="{{ default_value }}"
                        {{ is_default_value_current_time ? 'disabled' : '' }}
                    />
                </div>
                <div data-glpi-form-editor-question-extra-details class="col-auto d-flex align-items-center ms-1 mt-0">
                    <label class="form-check form-switch m-0 d-flex align-items-center gap-2">
                        <input type="hidden" name="is_default_value_current_time" value="0"
                            data-glpi-form-editor-specific-question-extra-data>
                        <input id="is_default_value_current_time_{{ rand }}" name="is_default_value_current_time" class="form-check-input"
                            onchange="handleDefaultValueCurrentTimeCheckbox_{{ rand }}(this)"
                            type="checkbox" value="1" {{ is_default_value_current_time ? 'checked' : '' }}
                            data-glpi-form-editor-specific-question-extra-data>
                        <span>{{ placeholders.default_value[input_type_ignore_text] }}</span>
                    </label>
                </div>
            </div>

            <script>
                function handleDefaultValueCurrentTimeCheckbox_{{ rand }}(input) {
                    const isChecked = $(input).is(':checked');
                    const dateInput = $('#date_input_{{ rand }}').prop('disabled', isChecked);
                    updateDateAndTimeInputType($(input).closest('div[data-glpi-form-editor-question]'));
                }
            </script>
TWIG;

        if ($question === null) {
            $template .= <<<TWIG
                <script>
                    window.updateDateAndTimeInputType = function updateDateAndTimeInputType(questionSection) {
                        const dateInput = questionSection.find('input[id^="date_input_"]');
                        const isDefaultValueCurrentTime = questionSection
                            .find('input[id^="is_default_value_current_time_"]');
                        const isDateEnabled = questionSection
                            .find('input[id^="is_date_enabled_"]')
                            .is(':checked');
                        const isTimeEnabled = questionSection
                            .find('input[id^="is_time_enabled_"]')
                            .is(':checked');

                        let inputType = 'date';
                        let inputPlaceholder = {{ placeholders.input.date|json_encode|raw }};
                        let defaultValuePlaceholder = {{ placeholders.default_value.date|json_encode|raw }};
                        if (isDateEnabled && isTimeEnabled) {
                            inputType = 'datetime-local';
                            inputPlaceholder = {{ placeholders.input['datetime-local']|json_encode|raw }};
                            defaultValuePlaceholder = {{ placeholders.default_value['datetime-local']|json_encode|raw }};
                        } else if (isTimeEnabled) {
                            inputType = 'time';
                            inputPlaceholder = {{ placeholders.input.time|json_encode|raw }};
                            defaultValuePlaceholder = {{ placeholders.default_value.time|json_encode|raw }};
                        }

                        if (isDefaultValueCurrentTime.is(':checked')) {
                            inputType = 'text';

                            // Clear the value
                            dateInput.val('');
                        }

                        dateInput.prop('type', inputType);
                        dateInput.prop('placeholder', inputPlaceholder);
                        isDefaultValueCurrentTime.siblings('span').text(defaultValuePlaceholder);
                    }
                </script>
TWIG;
        }

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'question'          => $question,
            'default_value'     => $this->isDefaultValueCurrentTime($question)
                ? '' : $question->fields['default_value'] ?? '',
            'input_type'        => $this->getInputType($question),
            'input_type_ignore_text'        => $this->getInputType($question, true),
            'is_default_value_current_time' => $this->isDefaultValueCurrentTime($question),
            'placeholders'      => $this->getPlaceholders()
        ]);
    }

    #[Override]
    public function renderAdministrationOptionsTemplate(?Question $question): string
    {
        $template = <<<TWIG
            {% set rand = random() %}

            <div class="d-flex gap-2">
                <label class="form-check form-switch mb-0">
                    <input type="hidden" name="is_date_enabled" value="0"
                    data-glpi-form-editor-specific-question-extra-data>
                    <input class="form-check-input" type="checkbox" name="is_date_enabled"
                        id="is_date_enabled_{{ rand }}"
                        value="1" {{ is_date_enabled ? 'checked' : '' }}
                        onchange="handleDateAndTimeCheckbox_{{ rand }}(this)"
                        data-glpi-form-editor-specific-question-extra-data>
                    <span class="form-check-label">{{ labels.date }}</span>
                </label>
                <label class="form-check form-switch mb-0">
                    <input type="hidden" name="is_time_enabled" value="0"
                    data-glpi-form-editor-specific-question-extra-data>
                    <input class="form-check-input" type="checkbox" name="is_time_enabled"
                        id="is_time_enabled_{{ rand }}"
                        value="1" {{ is_time_enabled ? 'checked' : '' }}
                        onchange="handleDateAndTimeCheckbox_{{ rand }}(this)"
                        data-glpi-form-editor-specific-question-extra-data>
                    <span class="form-check-label">{{ labels.time }}</span>
                </label>
            </div>

            <script>
                {# Both date and time can be checked at the same time, but one of them must be checked #}
                function handleDateAndTimeCheckbox_{{ rand }}(input) {
                    const isChecked = $(input).is(':checked');
                    const otherInput = $('input[onchange^="handleDateAndTimeCheckbox_{{ rand }}"]:not([name="' + input.name + '"])');

                    if (!isChecked && otherInput.not(':checked')) {
                        otherInput.prop('checked', true);
                    }

                    updateDateAndTimeInputType($(input).closest('div[data-glpi-form-editor-question]'));
                }
            </script>
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'question' => $question,
            'is_date_enabled' => $this->isDateEnabled($question),
            'is_time_enabled' => $this->isTimeEnabled($question),
            'labels' => [
                'date' => _n('Date', 'Dates', 1),
                'time' => _n('Time', 'Times', 1)
            ]
        ]);
    }

    #[Override]
    public function renderEndUserTemplate(
        Question $question,
    ): string {
        $template = <<<TWIG
            <input
                type="{{ input_type }}"
                class="form-control"
                name="{{ question.getEndUserInputName() }}"
                value="{{ default_value }}"
                {{ question.fields.is_mandatory ? 'required' : '' }}
            >
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'question'      => $question,
            'input_type'    => $this->getInputType($question, true),
            'default_value' => $this->getDefaultValue($question) ?? ''
        ]);
    }

    #[Override]
    public function formatRawAnswer(mixed $answer): string
    {
        return $this->formatAnswer($answer);
    }

    #[Override]
    public function isAllowedForUnauthenticatedAccess(): bool
    {
        return true;
    }
}
