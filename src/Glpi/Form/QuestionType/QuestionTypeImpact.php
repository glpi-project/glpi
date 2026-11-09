<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

use CommonITILObject;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\Condition\ConditionHandler\ImpactConditionHandler;
use Glpi\Form\Condition\ConditionValueTransformerInterface;
use Glpi\Form\Condition\UsedAsCriteriaInterface;
use Glpi\Form\Question;
use Glpi\Form\ValidationResult;
use Glpi\ImpactLevel;
use Override;

final class QuestionTypeImpact extends AbstractQuestionType implements UsedAsCriteriaInterface, ConditionValueTransformerInterface, QuestionTypeValidationInterface
{
    /**
     * Retrieve the default value for the impact question type
     *
     * @param Question|null $question The question to retrieve the default value from
     * @return int
     */
    public function getDefaultValue(?Question $question): int
    {
        if (
            $question !== null
            && isset($question->fields['default_value'])
        ) {
            if (is_int($question->fields['default_value'])) {
                return $question->fields['default_value'];
            } elseif (is_numeric($question->fields['default_value'])) {
                return (int) $question->fields['default_value'];
            }
        }

        return 0;
    }

    #[Override]
    public function renderAdministrationTemplate(?Question $question): string
    {
        $template = <<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}

            {{ fields.dropdownArrayField(
                'default_value',
                value,
                impact_levels,
                '',
                {
                    'init'                : init,
                    'no_label'            : true,
                    'display_emptychoice' : true,
                    'mb'                  : '',
                }
            ) }}
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'init'               => $question != null,
            'value'              => $this->getDefaultValue($question),
            'impact_levels'      => ImpactLevel::getEnabledImpactValuesForDropdown(),
        ]);
    }

    #[Override]
    public function renderEndUserTemplate(Question $question): string
    {
        $template = <<<TWIG
        {% import 'components/form/fields_macros.html.twig' as fields %}

        {{ fields.dropdownArrayField(
            question.getEndUserInputName(),
            value,
            impact_levels,
            '',
            {
                'no_label'            : true,
                'display_emptychoice' : true,
                'aria_label'          : label,
                'mb'                  : '',
            }
        ) }}
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'value'              => $this->getDefaultValue($question),
            'question'           => $question,
            'impact_levels'      => ImpactLevel::getEnabledImpactValuesForDropdown(),
            'label' => $question->fields['name'],
        ]);
    }

    #[Override]
    public function formatRawAnswer(mixed $answer, Question $question): string
    {
        // An empty answer is stored as "0", which getImpactName() renders as "All"
        $level = filter_var($answer, FILTER_VALIDATE_INT);
        if ($level === false || ImpactLevel::tryFrom($level) === null) {
            return '';
        }

        return CommonITILObject::getImpactName($level);
    }

    #[Override]
    public function validateAnswer(Question $question, mixed $answer): ValidationResult
    {
        $result = new ValidationResult(true);

        // Only accept the exact levels offered by the dropdown
        $level = filter_var($answer, FILTER_VALIDATE_INT);
        if ($level === false || !array_key_exists($level, ImpactLevel::getEnabledImpactValuesForDropdown())) {
            $result->addError($question, __("Unexpected value"));
        }

        return $result;
    }

    #[Override]
    public function getCategory(): QuestionTypeCategoryInterface
    {
        return QuestionTypeCategory::IMPACT;
    }

    #[Override]
    public function isAllowedForUnauthenticatedAccess(): bool
    {
        return true;
    }

    #[Override]
    public function formatPredefinedValue(string $value): ?string
    {
        $value = strtolower($value);

        return match ($value) {
            'very low', 'verylow' => "1",
            'low' => "2",
            'medium' => "3",
            'high' => "4",
            'very high', 'veryhigh' => "5",
            default => null,
        };
    }

    #[Override]
    public function getConditionHandlers(
        ?JsonFieldInterface $question_config
    ): array {
        return array_merge(parent::getConditionHandlers($question_config), [new ImpactConditionHandler()]);
    }

    #[Override]
    public function transformConditionValueForComparisons(mixed $value, ?JsonFieldInterface $question_config): string
    {
        if (empty($value)) {
            return '';
        }

        return strval($value);
    }
}
