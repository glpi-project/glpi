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

abstract class AbstractQuestionType implements QuestionTypeInterface
{
    #[Override]
    public function __construct()
    {
    }

    #[Override]
    public function formatDefaultValueForDB(mixed $value): ?string
    {
        return $value; // Default value is already formatted
    }

    #[Override]
    public function prepareEndUserAnswer(Question $question, mixed $answer): mixed
    {
        return $answer;
    }

    #[Override]
    public function validateExtraDataInput(array $input): bool
    {
        return empty($input); // No extra data by default
    }

    #[Override]
    public function prepareExtraData(array $input): array
    {
        return $input; // No need to prepare the extra data
    }

    #[Override]
    public function getFormEditorJsOptions(): string
    {
        return <<<JS
            {
                "extractDefaultValue": function (question) { return null; },
                "convertDefaultValue": function (question, value) {
                    return value;
                }
            }
        JS;
    }

    #[Override]
    public function renderAdministrationOptionsTemplate(?Question $question): string
    {
        return ''; // No options by default
    }

    #[Override]
    public function renderAnswerTemplate($answer): string
    {
        return TemplateRenderer::getInstance()->renderFromStringTemplate(
            '<div class="form-control-plaintext">{{ answer }}</div>',
            ['answer' => $this->formatRawAnswer($answer)]
        );
    }

    #[Override]
    public function formatRawAnswer($answer): string
    {
        // By default only return the string ansswer
        if (!is_string($answer) && !is_numeric($answer)) {
            throw new \InvalidArgumentException(
                'Raw answer must be a string or a method must be implemented to format the answer'
            );
        }

        return (string) $answer;
    }

    #[Override]
    public function getName(): string
    {
        return $this->getCategory()->getLabel();
    }

    #[Override]
    public function getIcon(): string
    {
        return 'ti ti-icons-off';
    }

    #[Override]
    public function getWeight(): int
    {
        return 10;
    }

    #[Override]
    public function isAllowedForUnauthenticatedAccess(): bool
    {
        return false;
    }
}
