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
use Glpi\Form\FormTranslation;
use Glpi\Form\Migration\FormQuestionDataConverterInterface;
use Glpi\Form\Question;
use Override;

/**
 * Short answers are single line inputs used to answer simple questions.
 */
abstract class AbstractQuestionTypeShortAnswer extends AbstractQuestionType implements FormQuestionDataConverterInterface
{
    /**
     * Specific input type for child classes
     *
     * @return string
     */
    abstract public function getInputType(): string;

    #[Override]
    public function getFormEditorJsOptions(): string
    {
        return <<<JS
            {
                "extractDefaultValue": function (question) {
                    const GlpiFormEditorConvertedExtractedDefaultValue = $("[data-glpi-form-editor-container]")
                        .data('EditorConvertedExtractedDefaultValue')
                    ;

                    const input = question.find('[data-glpi-form-editor-question-type-specific]')
                        .find('[name="default_value"], [data-glpi-form-editor-original-name="default_value"]');

                    return new GlpiFormEditorConvertedExtractedDefaultValue(
                        GlpiFormEditorConvertedExtractedDefaultValue.DATATYPE.STRING,
                        input.val()
                    );
                },
                "convertDefaultValue": function (question, value) {
                    const GlpiFormEditorConvertedExtractedDefaultValue = $("[data-glpi-form-editor-container]")
                        .data('EditorConvertedExtractedDefaultValue')
                    ;

                    if (value == null) {
                        return '';
                    }

                    // Only accept string values
                    if (value.getDatatype() !== GlpiFormEditorConvertedExtractedDefaultValue.DATATYPE.STRING) {
                        return '';
                    }

                    const input = question.find('[data-glpi-form-editor-question-type-specific]')
                        .find('[name="default_value"], [data-glpi-form-editor-original-name="default_value"]');

                    return input.val(value.getDefaultValue()).val();
                }
            }
        JS;
    }

    /**
     * Provide additional attributes for the input field
     *
     * @return array
     */
    public function getInputAttributes(): array
    {
        return [];
    }

    #[Override]
    public function renderAdministrationTemplate(?Question $question): string
    {
        $template = <<<TWIG
            <input
                class="form-control"
                type="{{ input_type }}"
                name="default_value"
                placeholder="{{ input_placeholder }}"
                value="{{ question is not null ? question.fields.default_value : '' }}"
                aria-label="{{ aria_label }}"
                {% for key, value in attributes %}
                    {{ key }}="{{ value }}"
                {% endfor %}
            />
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'question'          => $question,
            'input_type'        => $this->getInputType(),
            'input_placeholder' => $this->getName(),
            'attributes'        => $this->getInputAttributes(),
            'aria_label'        => __('Default value'),
        ]);
    }

    #[Override]
    public function renderEndUserTemplate(
        Question $question,
    ): string {
        $default_value = $question->fields['default_value'] ?? '';
        if ($this instanceof TranslationAwareQuestionType) {
            $default_value = FormTranslation::translate(
                $question,
                Question::TRANSLATION_KEY_DEFAULT_VALUE,
                1
            );
        }

        $template = <<<TWIG
            <input
                type="{{ input_type }}"
                class="form-control"
                name="{{ question.getEndUserInputName() }}"
                value="{{ default_value }}"
                aria-label="{{ label }}"
                {{ question.fields.is_mandatory ? 'required' : '' }}
                {% for key, value in attributes %}
                    {{ key }}="{{ value }}"
                {% endfor %}
            >
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'question'      => $question,
            'default_value' => $default_value,
            'input_type'    => $this->getInputType(),
            'label'         => $question->fields['name'],
            'attributes'    => $this->getInputAttributes(),
        ]);
    }

    #[Override]
    public function getCategory(): QuestionTypeCategoryInterface
    {
        return QuestionTypeCategory::SHORT_ANSWER;
    }

    #[Override]
    public function isAllowedForUnauthenticatedAccess(): bool
    {
        return true;
    }

    #[Override]
    public function formatPredefinedValue(string $value): string
    {
        return $value;
    }

    #[Override]
    public function convertDefaultValue(array $rawData): ?string
    {
        return $rawData['default_values'] ?? null;
    }

    #[Override]
    public function convertExtraData(array $rawData): null
    {
        return null;
    }

    #[Override]
    public function getTargetQuestionType(array $rawData): string
    {
        return static::class;
    }


    #[Override]
    public function beforeConversion(array $rawData): void {}
}
