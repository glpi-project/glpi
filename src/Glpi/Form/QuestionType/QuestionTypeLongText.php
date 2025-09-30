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

use Config;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\Condition\ConditionHandler\RichTextConditionHandler;
use Glpi\Form\Condition\ConditionValueTransformerInterface;
use Glpi\Form\Condition\UsedAsCriteriaInterface;
use Glpi\Form\FormTranslation;
use Glpi\Form\Migration\FormQuestionDataConverterInterface;
use Glpi\Form\Question;
use Glpi\ItemTranslation\Context\TranslationHandler;
use Override;
use Session;

/**
 * Long answers are multiple lines inputs used to answer questions with as much details as needed.
 */
final class QuestionTypeLongText extends AbstractQuestionType implements
    FormQuestionDataConverterInterface,
    UsedAsCriteriaInterface,
    TranslationAwareQuestionType,
    ConditionValueTransformerInterface
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
                    'placeholder'    : placeholder,
                    'enable_richtext': true,
                    'editor_height'  : "100",
                    'rows'           : 1,
                    'init'           : false,
                    'init_on_demand' : true,
                    'is_horizontal'  : false,
                    'full_width'     : true,
                    'no_label'       : true,
                    'aria_label'     : aria_label,
                    'mb'             : '',
                }
            ) }}
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'question'    => $question,
            'placeholder' => __('Long text'),
            'aria_label'  => __('Default value'),
        ]);
    }

    #[Override]
    public function renderEndUserTemplate(Question $question): string
    {
        // TODO: handle required
        $translated_default_value = FormTranslation::translate(
            $question,
            Question::TRANSLATION_KEY_DEFAULT_VALUE,
            1
        );
        $template = <<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}

            {{ fields.textareaField(
                question.getEndUserInputName(),
                default_value,
                "",
                {
                    'enable_richtext': true,
                    'enable_images'  : enable_images,
                    'init'           : question is not null ? true   : false,
                    'is_horizontal'  : false,
                    'full_width'     : true,
                    'no_label'       : true,
                    'mb'             : '',
                }
            ) }}
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'question'      => $question,
            'default_value' => $translated_default_value,
            'enable_images' => Session::isAuthenticated() || Config::allowUnauthenticatedUploads(),
        ]);
    }

    #[Override]
    public function getCategory(): QuestionTypeCategoryInterface
    {
        return QuestionTypeCategory::LONG_ANSWER;
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
    public function getConditionHandlers(
        ?JsonFieldInterface $question_config
    ): array {
        return array_merge(parent::getConditionHandlers($question_config), [new RichTextConditionHandler()]);
    }

    #[Override]
    public function transformConditionValueForComparisons(mixed $value, ?JsonFieldInterface $question_config): string
    {
        return strip_tags(strval($value));
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
    public function listTranslationsHandlers(Question $question): array
    {
        $handlers = [];

        if (!empty($question->fields['default_value'])) {
            $handlers[] = new TranslationHandler(
                item: $question,
                key: Question::TRANSLATION_KEY_DEFAULT_VALUE,
                name: __('Default value'),
                value: $question->fields['default_value'],
                is_rich_text: true
            );
        }

        return $handlers;
    }

    #[Override]
    public function getTargetQuestionType(array $rawData): string
    {
        return self::class;
    }


    #[Override]
    public function beforeConversion(array $rawData): void {}
}
