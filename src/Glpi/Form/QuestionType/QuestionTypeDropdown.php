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
use Override;

final class QuestionTypeDropdown extends AbstractQuestionTypeSelectable
{
    #[Override]
    public function getInputType(?Question $question): string
    {
        return $this->isMultipleDropdown($question) ? 'checkbox' : 'radio';
    }

    #[Override]
    public function getCategory(): QuestionTypeCategory
    {
        return QuestionTypeCategory::DROPDOWN;
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

        return $question->getExtraDatas()['is_multiple_dropdown'] ?? false;
    }

    #[Override]
    public function hideOptionsContainerWhenUnfocused(): bool
    {
        return true;
    }

    #[Override]
    public function loadJavascriptFiles(): array
    {
        return array_merge(
            parent::loadJavascriptFiles(),
            ['js/form_question_dropdown.js']
        );
    }

    #[Override]
    protected function getFooterScript(): string
    {
        $js = <<<TWIG
            $(document).ready(function() {
                {% if question is not null %}
                    const container = $('div[data-glpi-form-editor-selectable-question-options="{{ rand }}"]');
                    new GlpiFormQuestionTypeDropdown('{{ input_type }}', container);
                {% else %}
                    $(document).on('glpi-form-editor-question-type-changed', function(e, question, type) {
                        if (type === '{{ question_type|escape('js') }}') {
                            const container = question.find('div[data-glpi-form-editor-selectable-question-options]');
                            new GlpiFormQuestionTypeDropdown('{{ input_type }}', container);
                        }
                    });
                {% endif %}
            });
TWIG;

        return $js;
    }

    #[Override]
    public function renderAdministrationTemplate(?Question $question = null): string
    {
        $template = <<<TWIG
        {% import 'components/form/fields_macros.html.twig' as fields %}

        <div data-glpi-form-editor-preview-dropdown>
            {{ fields.dropdownArrayField(
                '',
                checked_values|first,
                values,
                '',
                {
                    'init': init,
                    'no_label': true,
                    'multiple': false,
                    'display_emptychoice': true,
                    'field_class': 'single-preview-dropdown col-12 col-sm-6' ~ (is_multiple_dropdown ? ' d-none' : '')
                }
            ) }}
            {{ fields.dropdownArrayField(
                '',
                '',
                values,
                '',
                {
                    'init': init,
                    'no_label': true,
                    'multiple': true,
                    'values': checked_values,
                    'field_class': 'multiple-preview-dropdown col-12 col-sm-6' ~ (not is_multiple_dropdown ? ' d-none' : '')
                }
            ) }}
        </div>
TWIG;

        $template .= parent::renderAdministrationTemplate($question);

        $twig = TemplateRenderer::getInstance();
        $values = array_combine(
            array_map(fn ($option) => $option['uuid'], $this->getValues($question)),
            array_map(fn ($option) => $option['value'], $this->getValues($question))
        );
        $checked_values = array_map(
            fn ($option) => $option['uuid'],
            array_filter($this->getValues($question), fn ($option) => $option['checked'])
        );
        return $twig->renderFromStringTemplate($template, [
            'question'             => $question,
            'init'                 => $question != null,
            'values'               => $values,
            'checked_values'       => $checked_values,
            'is_multiple_dropdown' => $this->isMultipleDropdown($question),
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
            'is_multiple_dropdown_label' => __('Allow multiple options')
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
                    'no_label': true,
                    'values': checked_values,
                    'multiple': is_multiple,
                }
            ) }}
TWIG;

        $twig = TemplateRenderer::getInstance();
        $values = array_map(fn ($option) => $option['value'], $this->getValues($question));
        $checked_values = array_map(
            fn ($option) => $option['value'],
            array_filter($this->getValues($question), fn ($option) => $option['checked'])
        );
        return $twig->renderFromStringTemplate($template, [
            'question'       => $question,
            'values'         => array_combine($values, $values), // Make keys and values the same to easily store the selected values
            'checked_values' => $checked_values,
            'is_multiple'    => $this->isMultipleDropdown($question),
        ]);
    }
}
