<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace GlpiPlugin\Tester\Form;

use Glpi\Application\View\TemplateRenderer;
use Glpi\Form\AnswersSet;
use Glpi\Form\Destination\CommonITILField\SLMField;
use Glpi\Form\Destination\CommonITILField\SLMFieldConfig;
use Glpi\Form\Destination\CommonITILField\SLMFieldStrategyInterface;
use Glpi\Form\Form;
use Glpi\Form\QuestionType\QuestionTypeShortText;
use Override;

/**
 * Example SLM field strategy for plugin testing.
 *
 * This strategy allows setting the SLA/OLA based on a form answer.
 */
final class SpecificAnswerSLMStrategy implements SLMFieldStrategyInterface
{
    public const KEY = 'tester_specific_answer';
    public const EXTRA_KEY_QUESTION_ID = 'question_id';

    #[Override]
    public function getKey(): string
    {
        return self::KEY;
    }

    #[Override]
    public function getLabel(SLMField $field): string
    {
        return sprintf(__("Answer from a specific question (%s)"), $field->getSLM()->getTypeName(1));
    }

    #[Override]
    public function applyStrategyToInput(
        SLMField $field,
        SLMFieldConfig $config,
        array $input,
        AnswersSet $answers_set
    ): array {
        $question_id = $config->getExtraDataValue(self::EXTRA_KEY_QUESTION_ID);
        if ($question_id === null) {
            return $input;
        }

        $answer = $answers_set->getAnswerByQuestionId($question_id);
        if ($answer === null) {
            return $input;
        }

        $slm_id = $answer->getRawAnswer();
        if (!is_numeric($slm_id)) {
            return $input;
        }

        return $field->applySlmIdToInput((int) $slm_id, $input);
    }

    #[Override]
    public function renderExtraConfigFields(
        Form $form,
        SLMField $field,
        SLMFieldConfig $config,
        string $input_name,
        array $display_options
    ): string {
        $twig = TemplateRenderer::getInstance();

        return $twig->render('@tester/specific_answer_slm_strategy.html.twig', [
            'strategy_key'   => self::KEY,
            'options'        => $display_options,
            'extra_field'    => [
                'empty_label'     => __("Select a question..."),
                'value'           => $config->getExtraDataValue(self::EXTRA_KEY_QUESTION_ID),
                'input_name'      => $input_name . "[" . SLMFieldConfig::EXTRA_DATA . "][" . self::EXTRA_KEY_QUESTION_ID . "]",
                'possible_values' => $this->getShortTextQuestionsForDropdown($form),
            ],
        ]);
    }

    #[Override]
    public function getExtraConfigKeys(): array
    {
        return [self::EXTRA_KEY_QUESTION_ID];
    }

    #[Override]
    public function getWeight(): int
    {
        return 100;
    }

    /**
     * Get short text questions available in the form for the dropdown.
     *
     * @param Form $form
     * @return array<int, string>
     */
    private function getShortTextQuestionsForDropdown(Form $form): array
    {
        $values = [];
        $questions = $form->getQuestionsByType(QuestionTypeShortText::class);

        foreach ($questions as $question) {
            $values[$question->getId()] = $question->fields['name'];
        }

        return $values;
    }
}
