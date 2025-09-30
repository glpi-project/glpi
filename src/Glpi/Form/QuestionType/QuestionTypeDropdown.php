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
use Glpi\Form\Condition\ConditionHandler\MultipleChoiceFromValuesConditionHandler;
use Glpi\Form\Condition\ConditionHandler\SingleChoiceFromValuesConditionHandler;
use Glpi\Form\Condition\ConditionValueTransformerInterface;
use Glpi\Form\Condition\UsedAsCriteriaInterface;
use Glpi\Form\Question;
use InvalidArgumentException;
use Override;

use function Safe\json_decode;

final class QuestionTypeDropdown extends AbstractQuestionTypeSelectable implements
    UsedAsCriteriaInterface,
    ConditionValueTransformerInterface
{
    #[Override]
    public function getInputType(?Question $question): string
    {
        return $this->isMultipleDropdown($question) ? 'checkbox' : 'radio';
    }

    #[Override]
    public function getCategory(): QuestionTypeCategoryInterface
    {
        return QuestionTypeCategory::DROPDOWN;
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

        $config = new QuestionTypeDropdownExtraDataConfig(
            options: $options,
            is_multiple_dropdown: $rawData['fieldtype'] === 'multiselect'
        );
        return $config->jsonSerialize();
    }

    /**
     * Check if the question allows multiple options to be selected
     *
     * @param ?Question $question
     * @return bool
     */
    public function isMultipleDropdown(?Question $question): bool
    {
        if ($question === null) {
            return false;
        }

        /** @var ?QuestionTypeDropdownExtraDataConfig $config */
        $config = $this->getExtraDataConfig(json_decode($question->fields['extra_data'], true) ?? []);
        if ($config === null) {
            return false;
        }

        return $config->isMultipleDropdown();
    }

    #[Override]
    public function hideOptionsContainerWhenUnfocused(): bool
    {
        return true;
    }

    public function hideOptionsDefaultValueInput(): bool
    {
        return true;
    }

    #[Override]
    protected function getFormInlineScript(): string
    {
        // language=Twig
        $js = <<<TWIG
            import("/js/modules/Forms/QuestionDropdown.js").then((m) => {
                {% if question is not null %}
                    const container = $('div[data-glpi-form-editor-selectable-question-options="{{ rand }}"]');
                    container.data(
                        'manager',
                        new m.GlpiFormQuestionTypeDropdown('{{ input_type|escape('js') }}', container)
                    );
                {% else %}
                    $(document).on('glpi-form-editor-question-type-changed', function(e, question, type) {
                        if (type === '{{ question_type|escape('js') }}') {
                            const container = question.find('div[data-glpi-form-editor-selectable-question-options]');
                            container.data(
                                'manager',
                                new m.GlpiFormQuestionTypeDropdown('{{ input_type|escape('js') }}', container, true)
                            );
                        }
                    });

                    $(document).on('glpi-form-editor-question-duplicated', function(e, question, new_question) {
                        const question_type = question.find('input[data-glpi-form-editor-original-name="type"]').val();
                        if (question_type === '{{ question_type|escape('js') }}') {
                            const container = new_question.find('div[data-glpi-form-editor-selectable-question-options]');
                            container.data(
                                'manager',
                                new m.GlpiFormQuestionTypeDropdown('{{ input_type|escape('js') }}', container, true)
                            );
                        }
                    });
                {% endif %}
            });
TWIG;

        return $js;
    }

    #[Override]
    protected function getSelectableQuestionOptionsClass(): string
    {
        return 'dropdown-border';
    }

    #[Override]
    public function renderAdministrationTemplate(?Question $question): string
    {
        $template = <<<TWIG
        {% import 'components/form/fields_macros.html.twig' as fields %}

        <div data-glpi-form-editor-preview-dropdown>
            {{ fields.dropdownArrayField(
                'default_value',
                checked_values|first,
                values,
                '',
                {
                    'init': init,
                    'no_label': true,
                    'multiple': false,
                    'disabled': is_multiple_dropdown,
                    'display_emptychoice': true,
                    'field_class': 'single-preview-dropdown col-12' ~ (is_multiple_dropdown ? ' d-none' : ''),
                    'mb': '',
                    'aria_label': default_option_label
                }
            ) }}
            {{ fields.dropdownArrayField(
                'default_value',
                '',
                values,
                '',
                {
                    'init': init,
                    'no_label': true,
                    'multiple': true,
                    'disabled': not is_multiple_dropdown,
                    'values': checked_values,
                    'field_class': 'multiple-preview-dropdown col-12' ~ (not is_multiple_dropdown ? ' d-none' : ''),
                    'mb': '',
                    'aria_label': default_options_label
                }
            ) }}
        </div>
TWIG;

        $template .= parent::renderAdministrationTemplate($question);

        $twig = TemplateRenderer::getInstance();
        $values = array_combine(
            array_map(fn($option) => $option['uuid'], $this->getValues($question)),
            array_map(fn($option) => $option['value'], $this->getValues($question))
        );
        $checked_values = array_map(
            fn($option) => $option['uuid'],
            array_filter($this->getValues($question), fn($option) => $option['checked'])
        );
        return $twig->renderFromStringTemplate($template, [
            'question'              => $question,
            'init'                  => $question != null,
            'values'                => $values,
            'checked_values'        => $checked_values,
            'is_multiple_dropdown'  => $this->isMultipleDropdown($question),
            'default_option_label'  => __('Default option'),
            'default_options_label' => __('Default options'),
        ]);
    }

    #[Override]
    public function renderAdministrationOptionsTemplate(?Question $question): string
    {
        $template = <<<TWIG
            {% set rand = random() %}

            <div class="d-flex gap-2">
                <label class="form-check form-switch mb-0">
                    <input type="hidden" name="is_multiple_dropdown" value="0"
                    data-glpi-form-editor-specific-question-extra-data>
                    <input class="form-check-input" type="checkbox" name="is_multiple_dropdown"
                        value="1" {{ is_multiple_dropdown ? 'checked' : '' }}
                        data-glpi-form-editor-specific-question-extra-data>
                    <span class="form-check-label">{{ is_multiple_dropdown_label }}</span>
                </label>
            </div>
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'is_multiple_dropdown'       => $this->isMultipleDropdown($question),
            'is_multiple_dropdown_label' => __('Allow multiple options'),
        ]);
    }

    #[Override]
    public function renderEndUserTemplate(
        Question $question,
    ): string {
        $template = <<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}

            {{ fields.dropdownArrayField(
                question.getEndUserInputName(),
                not is_multiple ? checked_values|first : '',
                values,
                '',
                {
                    'no_label'           : true,
                    'values'             : checked_values,
                    'multiple'           : is_multiple,
                    'mb'                 : '',
                    'aria_label'         : label,
                    'display_emptychoice': checked_values|length == 0,
                }
            ) }}
TWIG;

        $twig = TemplateRenderer::getInstance();
        $checked_values = array_map(
            fn($option) => $option['uuid'],
            array_filter($this->getValues($question), fn($option) => $option['checked'])
        );
        return $twig->renderFromStringTemplate($template, [
            'question'       => $question,
            'label'          => $question->fields['name'],
            'values'         => $this->getOptions($question),
            'checked_values' => $checked_values,
            'is_multiple'    => $this->isMultipleDropdown($question),
        ]);
    }

    #[Override]
    public function getExtraDataConfigClass(): string
    {
        return QuestionTypeDropdownExtraDataConfig::class;
    }

    #[Override]
    public function getConditionHandlers(
        ?JsonFieldInterface $question_config
    ): array {
        if (!$question_config instanceof QuestionTypeDropdownExtraDataConfig) {
            throw new InvalidArgumentException();
        }

        $options = $question_config->getOptions();
        if ($question_config->isMultipleDropdown()) {
            return array_merge(
                parent::getConditionHandlers($question_config),
                [new MultipleChoiceFromValuesConditionHandler($options)]
            );
        } else {
            return array_merge(
                parent::getConditionHandlers($question_config),
                [new SingleChoiceFromValuesConditionHandler($options)]
            );
        }
    }
}
