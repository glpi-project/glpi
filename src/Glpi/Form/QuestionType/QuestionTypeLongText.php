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

/**
 * Long answers are multiple lines inputs used to answer questions with as much details as needed.
 */
final class QuestionTypeLongText extends AbstractQuestionType
{
    #[Override]
    public function getFormEditorJsOptions(): string
    {
        return <<<JS
            {
                "extractDefaultValue": function (question) {
                    const GlpiFormEditorConvertedExtractedDefaultValue = $("[data-glpi-form-editor-container]")
                        .data('EditorConvertedExtractedDefaultValue')
                    ;

                    const textarea = question.find('[data-glpi-form-editor-question-type-specific]')
                        .find('[name="default_value"], [data-glpi-form-editor-original-name="default_value"]');
                    const inst = tinyMCE.get(textarea.attr('id'));

                    if (inst) {
                        let content = inst.getContent();
                        let tmp = document.createElement("DIV");
                        tmp.innerHTML = content;
                        content = tmp.textContent || tmp.innerText || "";

                        return new GlpiFormEditorConvertedExtractedDefaultValue(
                            GlpiFormEditorConvertedExtractedDefaultValue.DATATYPE.STRING,
                            content
                        );
                    }

                    return '';
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

                    const textarea = question.find('[data-glpi-form-editor-question-type-specific]')
                        .find('[name="default_value"], [data-glpi-form-editor-original-name="default_value"]');
                    textarea.val(value.getDefaultValue());

                    return textarea.val();
                }
            }
        JS;
    }

    #[Override]
    public function renderAdministrationTemplate(?Question $question): string
    {
        $template = <<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}

            {{ fields.textareaField(
                'default_value',
                question is not null ? question.fields.default_value : '',
                "",
                {
                    'placeholder': placeholder,
                    'enable_richtext': true,
                    'editor_height': "0",
                    'rows' : 1,
                    'init': question is not null ? true : false,
                    'is_horizontal': false,
                    'full_width'   : true,
                    'no_label'     : true,
                    'aria_label': aria_label,
                }
            ) }}
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'question'    => $question,
            'placeholder' => __('Long text'),
            'aria_label'  => __('Default value')
        ]);
    }

    #[Override]
    public function renderEndUserTemplate(Question $question): string
    {
        // TODO: handle required
        $template = <<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}

            {{ fields.textareaField(
                question.getEndUserInputName(),
                question.fields.default_value,
                "",
                {
                    'enable_richtext': true,
                    'editor_height': "0",
                    'rows' : 1,
                    'init': question is not null ? true : false,
                    'is_horizontal': false,
                    'full_width'   : true,
                    'no_label'     : true,
                }
            ) }}
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'question' => $question,
        ]);
    }

    #[Override]
    public function renderAnswerTemplate(mixed $answer): string
    {
        $template = <<<TWIG
            <div class="form-control-plaintext">{{ answer|safe_html }}</div>
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'answer' => $answer,
        ]);
    }

    #[Override]
    public function getCategory(): QuestionTypeCategory
    {
        return QuestionTypeCategory::LONG_ANSWER;
    }

    #[Override]
    public function isAllowedForUnauthenticatedAccess(): bool
    {
        return true;
    }
}
