<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\Condition\ConditionValueTransformerInterface;
use Glpi\Form\Migration\FormQuestionDataConverterInterface;
use Glpi\Form\Question;
use Glpi\ItemTranslation\Context\TranslationHandler;
use LogicException;
use Override;
use Safe\Exceptions\JsonException;

use function Safe\json_decode;

/**
 * Short answers are single line inputs used to answer simple questions.
 */
abstract class AbstractQuestionTypeSelectable extends AbstractQuestionType implements FormQuestionDataConverterInterface, TranslationAwareQuestionType, ConditionValueTransformerInterface
{
    public const TRANSLATION_KEY_OPTION = 'option';

    public function __construct() {}

    #[Override]
    public function getFormEditorJsOptions(): string
    {
        return <<<JS
            {
                "extractDefaultValue": function (question) {
                    const options = question.find('[data-glpi-form-editor-selectable-question-options]')
                        .data('manager').getOptions();

                    return new EditorConvertedExtractedSelectableDefaultValue(options);
                },
                "convertDefaultValue": function (question, value) {
                    if (value == null) {
                        return '';
                    }

                    if (!(value instanceof EditorConvertedExtractedSelectableDefaultValue)) {
                        return '';
                    }

                    setTimeout(() => {
                        question.find('[data-glpi-form-editor-selectable-question-options]')
                            .data('manager').setOptions(value.getOptions());
                    });

                    return value.getOptions();
                }
            }
        JS;
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
            import("/js/modules/Forms/QuestionSelectable.js").then((m) => {
                {% if question is not null %}
                    const container = $('div[data-glpi-form-editor-selectable-question-options="{{ rand }}"]');
                    container.data(
                        'manager',
                        new m.GlpiFormQuestionTypeSelectable('{{ input_type|escape('js') }}', container)
                    );
                {% else %}
                    $(document).on('glpi-form-editor-question-type-changed', function(e, question, type) {
                        if (type === '{{ question_type|escape('js') }}') {
                            const container = question.find('div[data-glpi-form-editor-selectable-question-options]');
                            container.data(
                                'manager',
                                new m.GlpiFormQuestionTypeSelectable('{{ input_type|escape('js') }}', container, true)
                            );
                        }
                    });

                    $(document).on('glpi-form-editor-question-duplicated', function(e, question, new_question) {
                        const question_type = question.find('input[data-glpi-form-editor-original-name="type"]').val();
                        if (question_type === '{{ question_type|escape('js') }}') {
                            const container = new_question.find('div[data-glpi-form-editor-selectable-question-options]');
                            container.data(
                                'manager',
                                new m.GlpiFormQuestionTypeSelectable('{{ input_type|escape('js') }}', container, true)
                            );
                        }
                    });
                {% endif %}

                // The module above will trigger some input changes, we need
                // to reset the global unsaved form state after this.
                window.setHasUnsavedChanges(false);
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
        if ($input === [] || !isset($input['options'])) {
            return false;
        }

        return true;
    }

    #[Override]
    public function prepareExtraData(array $input): array
    {
        // Sort options by order
        if (isset($input['options_order']) && is_array($input['options_order'])) {
            uksort($input['options'], function ($a, $b) use ($input) {
                $orderA = $input['options_order'][$a] ?? 0;
                $orderB = $input['options_order'][$b] ?? 0;
                return $orderA <=> $orderB;
            });
            unset($input['options_order']);
        }

        // The last option can be empty, so we need to remove it
        if (isset($input['options']) && end($input['options']) === '') {
            array_pop($input['options']);
        }

        return $input;
    }

    #[Override]
    public function convertDefaultValue(array $rawData): array
    {
        if (
            empty($rawData['default_values'])
            || empty($rawData['values'])
        ) {
            return [];
        }

        $options = json_decode($rawData['values']);
        if (empty($options)) {
            return [];
        }

        /**
         * New default values format require an array of values.
         * The old system did not use an array if there was only one element.
         */
        $default_values = '';
        try {
            $default_values = json_decode($rawData['default_values']);
        } catch (JsonException $e) {
            //empty catch
        }
        if (!is_array($default_values)) {
            $default_values = [$rawData['default_values']];
        }

        // Return the indexes of the default values
        return array_map(fn($value) => array_search($value, $options) + 1, $default_values);
    }

    #[Override]
    public function convertExtraData(array $rawData): array
    {
        $values = json_decode($rawData['values'] ?? '[]', true) ?? [];

        // Convert array values to use index + 1 as keys
        $options = [];
        foreach ($values as $index => $value) {
            $options[$index + 1] = $value;
        }

        $config = new QuestionTypeSelectableExtraDataConfig(
            options: $options
        );
        return $config->jsonSerialize();
    }

    #[Override]
    public function listTranslationsHandlers(Question $question): array
    {
        $handlers = [];
        $options = $this->getOptions($question);
        if ($options !== []) {
            $handlers = array_map(
                fn($uuid, $option) => new TranslationHandler(
                    item: $question,
                    key: sprintf('%s-%s', self::TRANSLATION_KEY_OPTION, $uuid),
                    name: sprintf('%s %s', self::getName(), __('Option')),
                    value: $option,
                ),
                array_keys($options),
                $options
            );
        }

        return $handlers;
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

        /** @var ?QuestionTypeSelectableExtraDataConfig $config */
        $config = $this->getExtraDataConfig(json_decode($question->fields['extra_data'], true) ?? []);
        if ($config === null) {
            return [];
        }

        return $config->getOptions();
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

        {% macro addOption(input_type, checked, value, translations, uuid = null, order, extra_details = false, disabled = false, hide_default_value_input = false) %}
            {% if uuid is null %}
                {% set uuid = random() %}
            {% endif %}

            <div
                class="d-flex gap-1 align-items-center mb-2"
                data-glpi-form-selectable-question-option
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
                    class="flex-grow-1 w-full"
                    style="border: none transparent; outline: none; box-shadow: none;"
                    name="options[{{ uuid }}]"
                    value="{{ value }}"
                    placeholder="{{ translations.enter_option }}"
                    aria-label="{{ translations.selectable_option }}"
                >
                <input
                    type="hidden"
                    name="options_order[{{ uuid }}]"
                    value="{{ order }}"
                    data-glpi-form-editor-specific-question-extra-data
                    data-glpi-form-editor-question-option-order
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
            {{ _self.addOption(input_type, false, '', translations, null, null, true, true, hide_default_value_input) }}
        </template>

        <div class="{{ selectable_question_options_class|default('') }}">
            <div
                data-glpi-form-editor-selectable-question-options="{{ rand }}"
                {{ hide_container_when_unfocused ? 'data-glpi-form-editor-question-extra-details' : '' }}
            >
                {% for value in values %}
                    {{ _self.addOption(input_type, value.checked, value.value, translations, value.uuid, loop.index0, false, false, hide_default_value_input) }}
                {% endfor %}
            </div>

            {{ _self.addOption(input_type, false, '', translations, null, values|length, true, true, hide_default_value_input) }}
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
            ],
        ]);
    }

    #[Override]
    public function renderEndUserTemplate(
        Question $question,
    ): string {
        $template = <<<TWIG
            {% for value in values %}
                <label class="form-check {{ loop.last ? 'mb-0' : '' }}">
                    <input
                        type="{{ input_type }}"
                        name="{{ question.getEndUserInputName() }}[]"
                        value="{{ value.uuid }}"
                        class="form-check-input" {{ value.checked ? 'checked' : '' }}
                    >
                    <span class="form-check-label">
                        {{ translate_form_item_key(
                            question,
                            '%s-%s'|format(
                                constant('Glpi\\\\Form\\\\QuestionType\\\\AbstractQuestionTypeSelectable::TRANSLATION_KEY_OPTION'),
                                value.uuid
                            )
                        ) }}
                    </span>
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
    public function formatRawAnswer(mixed $answer, Question $question): string
    {
        if (is_string($answer)) {
            $answer = [$answer];
        }

        // Replace uuids by labels
        $options = $this->getOptions($question);
        $answer = array_map(
            fn($uuid) => $options[$uuid] ?? '',
            $answer
        );

        return implode(', ', $answer);
    }

    #[Override]
    public function isAllowedForUnauthenticatedAccess(): bool
    {
        return true;
    }

    #[Override]
    public function getExtraDataConfigClass(): ?string
    {
        return QuestionTypeSelectableExtraDataConfig::class;
    }

    #[Override]
    public function transformConditionValueForComparisons(mixed $value, ?JsonFieldInterface $question_config): array
    {
        // Handle empty cases first
        if (empty($value)) {
            return [];
        }

        if (!($question_config instanceof QuestionTypeSelectableExtraDataConfig)) {
            throw new LogicException('Invalid question config');
        }

        if (is_string($value)) {
            // If it's a string, try to split it into an array
            $value = explode(',', $value);
        }

        return array_filter(array_map(fn($item) => $question_config->getOptions()[$item] ?? null, $value));
    }

    #[Override]
    public function getTargetQuestionType(array $rawData): string
    {
        return static::class;
    }


    #[Override]
    public function beforeConversion(array $rawData): void {}
}
