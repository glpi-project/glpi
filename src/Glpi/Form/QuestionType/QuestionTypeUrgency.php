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

use CommonITILObject;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\Condition\ConditionHandler\UrgencyConditionHandler;
use Glpi\Form\Condition\ConditionValueTransformerInterface;
use Glpi\Form\Condition\UsedAsCriteriaInterface;
use Glpi\Form\Migration\FormQuestionDataConverterInterface;
use Glpi\Form\Question;
use Override;

final class QuestionTypeUrgency extends AbstractQuestionType implements UsedAsCriteriaInterface, FormQuestionDataConverterInterface, ConditionValueTransformerInterface
{
    /**
     * Retrieve the default value for the urgency question type
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

    /**
     * Retrieve available urgency levels
     *
     * @return array
     */
    private function getUrgencyLevels(): array
    {
        global $CFG_GLPI;

        // Get the urgency levels
        $urgency_levels = array_combine(
            range(1, 5),
            array_map(fn($urgency) => CommonITILObject::getUrgencyName($urgency), range(1, 5))
        );

        // Filter out the urgency levels that are not enabled
        $urgency_levels = array_filter(
            $urgency_levels,
            fn($key) => (($CFG_GLPI['urgency_mask'] & (1 << $key)) > 0),
            ARRAY_FILTER_USE_KEY
        );

        return $urgency_levels;
    }

    #[Override]
    public function renderAdministrationTemplate(?Question $question): string
    {
        $template = <<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}

            {{ fields.dropdownArrayField(
                'default_value',
                value,
                urgency_levels,
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
            'urgency_levels'     => $this->getUrgencyLevels(),
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
            urgency_levels,
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
            'urgency_levels'     => $this->getUrgencyLevels(),
            'label' => $question->fields['name'],
        ]);
    }

    #[Override]
    public function formatRawAnswer(mixed $answer, Question $question): string
    {
        return CommonITILObject::getUrgencyName($answer);
    }

    #[Override]
    public function getCategory(): QuestionTypeCategoryInterface
    {
        return QuestionTypeCategory::URGENCY;
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
        return array_merge(parent::getConditionHandlers($question_config), [new UrgencyConditionHandler()]);
    }

    #[Override]
    public function transformConditionValueForComparisons(mixed $value, ?JsonFieldInterface $question_config): string
    {
        return strval($value);
    }

    #[Override]
    public function convertDefaultValue(array $rawData): ?int
    {
        if (!isset($rawData['default_values'])) {
            return null;
        }

        return (int) $rawData['default_values'];
    }

    #[Override]
    public function convertExtraData(array $rawData): null
    {
        return null;
    }

    #[Override]
    public function getTargetQuestionType(array $rawData): string
    {
        return self::class;
    }


    #[Override]
    public function beforeConversion(array $rawData): void {}
}
