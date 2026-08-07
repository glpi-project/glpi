<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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
use Glpi\Form\FormTranslation;
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
        return "{% include 'pages/admin/form/question_type/dropdown/form_inline_script.html.twig' %}";
    }

    #[Override]
    protected function getSelectableQuestionOptionsClass(): string
    {
        return 'dropdown-border';
    }

    #[Override]
    public function renderAdministrationTemplate(?Question $question): string
    {
        $parent = parent::renderAdministrationTemplate($question);

        $values = array_combine(
            array_map(fn($option) => $option['uuid'], $this->getValues($question)),
            array_map(fn($option) => $option['value'], $this->getValues($question))
        );
        $checked_values = array_map(
            fn($option) => $option['uuid'],
            array_filter($this->getValues($question), fn($option) => $option['checked'])
        );

        return TemplateRenderer::getInstance()->render(
            'pages/admin/form/question_type/dropdown/administration_template.html.twig',
            [
                'question'              => $question,
                'init'                  => $question != null,
                'values'                => $values,
                'checked_values'        => $checked_values,
                'is_multiple_dropdown'  => $this->isMultipleDropdown($question),
                'default_option_label'  => __('Default option'),
                'default_options_label' => __('Default options'),
            ]
        ) . $parent;
    }

    #[Override]
    public function renderAdministrationOptionsTemplate(?Question $question): string
    {
        return TemplateRenderer::getInstance()->render(
            'pages/admin/form/question_type/dropdown/administration_options.html.twig',
            [
                'is_multiple_dropdown'       => $this->isMultipleDropdown($question),
                'is_multiple_dropdown_label' => __('Allow multiple options'),
            ]
        );
    }

    #[Override]
    public function renderEndUserTemplate(
        Question $question,
    ): string {
        $checked_values = array_map(
            fn($option) => $option['uuid'],
            array_filter($this->getValues($question), fn($option) => $option['checked'])
        );

        $options = $this->getOptions($question);
        if (count($options) > 50) {
            return $this->renderEndUserTemplateAjax($question, $options, $checked_values);
        }

        $translated_options = [];
        foreach ($options as $uuid => $option) {
            $key = sprintf('%s-%s', self::TRANSLATION_KEY_OPTION, $uuid);
            $translated_options[$uuid] = FormTranslation::translate($question, $key) ?? $option;
        }

        return TemplateRenderer::getInstance()->render(
            'pages/admin/form/question_type/dropdown/end_user_template.html.twig',
            [
                'question'       => $question,
                'label'          => $question->fields['name'],
                'values'         => $translated_options,
                'checked_values' => $checked_values,
                'is_multiple'    => $this->isMultipleDropdown($question),
            ]
        );
    }

    /**
     * Render end-user template for large option lists (> 50) using AJAX-backed Select2.
     *
     * @param array<string, string> $options        All available options (uuid -> label)
     * @param array<string>         $checked_values UUIDs of pre-selected options
     */
    private function renderEndUserTemplateAjax(
        Question $question,
        array $options,
        array $checked_values,
    ): string {
        global $CFG_GLPI;

        $initial_values = [];
        foreach ($checked_values as $uuid) {
            if (isset($options[$uuid])) {
                $key = sprintf('%s-%s', self::TRANSLATION_KEY_OPTION, $uuid);
                $initial_values[$uuid] = FormTranslation::translate($question, $key) ?? $options[$uuid];
            }
        }

        return TemplateRenderer::getInstance()->render(
            'pages/admin/form/question_type/dropdown/end_user_template_ajax.html.twig',
            [
                'label'          => $question->fields['name'],
                'input_name'     => $question->getEndUserInputName(),
                'question_id'    => $question->fields['id'],
                'ajax_url'       => $CFG_GLPI['root_doc'] . '/Form/Question/DropdownValues',
                'initial_values' => $initial_values,
                'is_multiple'    => $this->isMultipleDropdown($question),
            ]
        );
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
