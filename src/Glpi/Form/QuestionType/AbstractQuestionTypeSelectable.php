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
     * @param ?Question $question
     * @return string
     */
    abstract public function getInputType(?Question $question): string;

    /**
     * Get inline javascript to be added during form rendering.
     * Some twig variables are available:
     * - question: the question object
     * - input_type: the input type
     * - question_type: the question type class
     * - rand: a random number
     *
     * @return string
     */
    protected function getFormInlineScript(): string
    {
        // language=Twig
        $js = <<<TWIG
            import("{{ js_path('js/modules/Forms/QuestionSelectable.js') }}").then((m) => {
                {% if question is not null %}
                    const container = $('div[data-glpi-form-editor-selectable-question-options="{{ rand }}"]');
                    new m.GlpiFormQuestionTypeSelectable('{{ input_type|escape('js') }}', container);
                {% else %}
                    $(document).on('glpi-form-editor-question-type-changed', function(e, question, type) {
                        if (type === '{{ question_type|escape('js') }}') {
                            const container = question.find('div[data-glpi-form-editor-selectable-question-options]');
                            new m.GlpiFormQuestionTypeSelectable('{{ input_type|escape('js') }}', container);
                        }
                    });
                {% endif %}
            });
TWIG;

        return $js;
    }

    #[Override]
    public function formatDefaultValueForDB(mixed $value): ?string
    {
        if (is_array($value)) {
            return implode(',', $value);
        }

        return $value;
    }

    #[Override]
    public function validateExtraDataInput(array $input): bool
    {
        // The input can not be empty, always have at least one option : the last one can be empty
        if (empty($input) || !isset($input['options'])) {
            return false;
        }

        return true;
    }

    #[Override]
    public function prepareExtraData(array $input): array
    {
        // The last option can be empty, so we need to remove it
        if (isset($input['options']) && end($input['options']) === '') {
            array_pop($input['options']);
        }

        return $input;
    }

    public function hideOptionsContainerWhenUnfocused(): bool
    {
        return false;
    }

    public function hideOptionsDefaultValueInput(): bool
    {
        return false;
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
     * @return array
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
                'uuid' => $uuid,
                'value' => $option,
                'checked' => (int) in_array($uuid, $default_values),
            ];
        }

        return $values;
    }

    /**
     * Retrieve the selectable question options class
     *
     * @return string
     */
    protected function getSelectableQuestionOptionsClass(): string
    {
        return '';
    }

    #[Override]
    public function renderAdministrationTemplate(?Question $question): string
    {
        $template = <<<TWIG
        {% set rand = random() %}

        {% macro addOption(input_type, checked, value, translations, uuid = null, extra_details = false, disabled = false, hide_default_value_input = false) %}
            {% if uuid is null %}
                {% set uuid = random() %}
            {% endif %}

            <div
                class="d-flex gap-1 align-items-center mb-2"
                {{ extra_details ? 'data-glpi-form-editor-question-extra-details' : '' }}
            >
                <i
                    role="button"
                    aria-label="{{ translations.move_option }}"
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
                    class="form-check-input {{ hide_default_value_input ? 'd-none' : '' }}"
                    aria-label="{{ translations.default_option }}"
                    {{ checked ? 'checked' : '' }}
                    {{ disabled ? 'disabled' : '' }}
                >
                <input
                    data-glpi-form-editor-specific-question-extra-data
                    type="text"
                    class="w-full"
                    style="border: none transparent; outline: none; box-shadow: none;"
                    name="options[{{ uuid }}]"
                    value="{{ value }}"
                    placeholder="{{ translations.enter_option }}"
                    aria-label="{{ translations.selectable_option }}"
                >
                <button
                    class="btn btn-sm btn-icon btn-ghost-secondary {{ value ? '' : 'd-none' }}"
                    aria-label="{{ translations.remove_option }}"
                    data-glpi-form-editor-question-extra-details
                    data-glpi-form-editor-question-option-remove
                >
                    <i class="ti ti-x"></i>
                </button>
            </div>
        {% endmacro %}

        <template>
            {{ _self.addOption(input_type, false, '', translations, null, true, true, hide_default_value_input) }}
        </template>

        <div class="{{ selectable_question_options_class|default('') }}">
            <div
                data-glpi-form-editor-selectable-question-options="{{ rand }}"
                {{ hide_container_when_unfocused ? 'data-glpi-form-editor-question-extra-details' : '' }}
            >
                {% for value in values %}
                    {{ _self.addOption(input_type, value.checked, value.value, translations, value.uuid, false, false, hide_default_value_input) }}
                {% endfor %}
            </div>

            {{ _self.addOption(input_type, false, '', translations, null, true, true, hide_default_value_input) }}
        </div>

        <script>
            // TODO: avoid this, the script should probably run in a dedicated method that the framework can call at
            // the right time.
            $("[data-glpi-form-editor-container]").on('initialized', () => {
                {$this->getFormInlineScript()}
            });
        </script>
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'question'                          => $question,
            'question_type'                     => $this::class,
            'values'                            => $this->getValues($question),
            'input_type'                        => $this->getInputType($question),
            'hide_container_when_unfocused'     => $this->hideOptionsContainerWhenUnfocused(),
            'hide_default_value_input'          => $this->hideOptionsDefaultValueInput(),
            'selectable_question_options_class' => $this->getSelectableQuestionOptionsClass(),
            'translations'                      => [
                'move_option'       => __('Move option'),
                'default_option'    => __('Default option'),
                'remove_option'     => __('Remove option'),
                'selectable_option' => __('Selectable option'),
                'enter_option'      => __('Enter an option'),
            ]
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
                        name="{{ question.getEndUserInputName() }}[]"
                        value="{{ value.value }}"
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
            'input_type' => $this->getInputType($question),
        ]);
    }

    #[Override]
    public function formatRawAnswer($answer): string
    {
        if (is_string($answer)) {
            return $answer;
        }

        return implode(', ', $answer);
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

    #[Override]
    public function isAllowedForUnauthenticatedAccess(): bool
    {
        return true;
    }
}
