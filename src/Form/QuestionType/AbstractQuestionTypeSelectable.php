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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Form\Question;
use Html;
use Override;

/**
 * Short answers are single line inputs used to answer simple questions.
 */
abstract class AbstractQuestionTypeSelectable extends AbstractQuestionType
{
    #[Override]
    public function __construct()
    {
    }

    /**
     * Specific input type for child classes
     *
     * @return string
     */
    abstract public function getInputType(): string;

    #[Override]
    public static function loadJavascriptFiles(): array
    {
        return ['js/form_question_selectable.js'];
    }

    #[Override]
    public static function formatDefaultValueForDB(mixed $value): ?string
    {
        if (is_array($value)) {
            return implode(',', $value);
        }

        return $value;
    }

    #[Override]
    public static function validateExtraDataInput(array $input): bool
    {
        // The input can not be empty, always have at least one option : the last one can be empty
        if (empty($input) || !isset($input['options'])) {
            return false;
        }

        return true;
    }

    #[Override]
    public static function prepareExtraData(array $input): array
    {
        // The last option can be empty, so we need to remove it
        if (isset($input['options']) && end($input['options']) === '') {
            array_pop($input['options']);
        }

        return $input;
    }

    /**
     * Retrieve the options
     *
     * @param ?Question $question
     * @return array
     */
    public function getOptions(?Question $question): array
    {
        if ($question === null) {
            return [];
        }

        return $question->getExtraDatas()['options'] ?? [];
    }

    /**
     * Retrieve the values
     *
     * @param ?Question $question
     * @return int
     */
    public function getValues(?Question $question): array
    {
        // If the question is not set we return an empty array (no options per default)
        if ($question === null) {
            return [];
        }

        $values = [];
        $options = $this->getOptions($question);
        $default_values = explode(',', $question->fields['default_value'] ?? '');
        foreach ($options as $uuid => $option) {
            $values[] = [
                'value' => $option,
                'checked' => (int) in_array($uuid, $default_values),
            ];
        }

        return $values;
    }

    #[Override]
    public function renderAdministrationTemplate(
        ?Question $question = null,
        ?string $input_prefix = null
    ): string {
        $template = <<<TWIG
        {% set rand = random() %}

        {% macro addOption(input_type, rand, checked, value, placeholder, extra_details = false, disabled = false) %}
            {% set uuid = random() %}

            <div
                class="d-flex gap-1 align-items-center mb-2"
                {{ extra_details ? 'data-glpi-form-editor-question-extra-details' : '' }}
            >
                <i
                    data-glpi-form-editor-question-extra-details
                    data-glpi-form-editor-question-option-handle
                    class="ti ti-grip-horizontal cursor-grab ms-auto me-1"
                    style="{{ disabled ? 'visibility: hidden;' : '' }}"
                    draggable="true"
                ></i>
                <input
                    type="{{ input_type }}"
                    name="default_value[]"
                    value="{{ uuid }}"
                    class="form-check-input" {{ checked ? 'checked' : '' }}
                    {{ disabled ? 'disabled' : '' }}
                >
                <input
                    data-glpi-form-editor-specific-question-extra-data
                    type="text"
                    class="w-full"
                    style="border: none transparent; outline: none; box-shadow: none;"
                    name="options[{{ uuid }}]"
                    value="{{ value|e('html_attr') }}"
                    placeholder="{{ placeholder|e('html_attr') }}"
                >
                <i
                    data-glpi-form-editor-question-extra-details
                    data-glpi-form-editor-question-option-remove
                    class="ti ti-x fa-lg text-muted ml-2 {{ value ? '' : 'd-none' }}"
                    style="cursor: pointer;"
                ></i>
            </div>
        {% endmacro %}

        <template>
            {{ _self.addOption(input_type, rand, false, '', input_placeholder, true, true) }}
        </template>

        <div data-glpi-form-editor-selectable-question-options="{{ rand }}">
            {% for value in values %}
                {{ _self.addOption(input_type, rand, value.checked, value.value, input_placeholder) }}
            {% endfor %}
        </div>

        {{ _self.addOption(input_type, rand, false, '', input_placeholder, true, true) }}

        <script>
            $(document).ready(function() {
                {% if question is not null %}
                    const container = $('div[data-glpi-form-editor-selectable-question-options="{{ rand }}"]');
                    new GlpiFormQuestionTypeSelectable('{{ input_type }}', container);
                {% else %}
                    $(document).on('glpi-form-editor-question-type-changed', function(e, question, type) {
                        if (type === '{{ question_type|escape('js') }}') {
                            const container = question.find('div[data-glpi-form-editor-selectable-question-options]');
                            new GlpiFormQuestionTypeSelectable('{{ input_type }}', container);
                        }
                    });
                {% endif %}
            });
        </script>
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'question'          => $question,
            'question_type'     => $this::class,
            'values'            => $this->getValues($question),
            'input_type'        => $this->getInputType(),
            'input_placeholder' => __('Enter an option'),
        ]);
    }

    #[Override]
    public function renderEndUserTemplate(
        Question $question,
    ): string {
        $template = <<<TWIG
            {% for value in values %}
                <label class="form-check">
                    <input
                        type="{{ input_type }}"
                        name="answers[{{ question.fields.id }}][]"
                        value="{{ value.value|e('html_attr') }}"
                        class="form-check-input" {{ value.checked ? 'checked' : '' }}
                    >
                    <span class="form-check-label">{{ value.value }}</span>
                </label>
            {% endfor %}
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'question'   => $question,
            'values'     => $this->getValues($question),
            'input_type' => $this->getInputType(),
        ]);
    }

    #[Override]
    public function renderAnswerTemplate($answers): string
    {
        $template = <<<TWIG
            {% for answer in answers %}
                <div class="form-control-plaintext">{{ answer }}</div>
            {% endfor %}
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'answers' => $answers,
        ]);
    }
}
