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
use Glpi\Form\Question;
use Glpi\Form\QuestionType\AbstractQuestionType;
use Glpi\Form\QuestionType\QuestionTypeCategory;
use Glpi\Form\QuestionType\QuestionTypeCategoryInterface;
use Override;

final class QuestionTypeColor extends AbstractQuestionType
{
    #[Override]
    public function getCategory(): QuestionTypeCategoryInterface
    {
        return new TesterCategory();
    }

    #[Override]
    public function getName(): string
    {
        return __('Color');
    }

    #[Override]
    public function getIcon(): string
    {
        return 'ti ti-palette';
    }

    #[Override]
    public function getWeight(): int
    {
        return 20;
    }

    #[Override]
    public function renderAdministrationTemplate(Question|null $question): string
    {
        $template = <<<TWIG
            <input
                class="form-control"
                type="color"
                name="default_value"
                placeholder="{{ input_placeholder }}"
                value="{{ question is not null ? question.fields.default_value : '' }}"
            />
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'question'          => $question,
            'input_placeholder' => $this->getName(),
        ]);
    }

    #[Override]
    public function renderEndUserTemplate(Question|null $question): string
    {
        $template = <<<TWIG
            <input
                type="color"
                class="form-control"
                name="{{ question.getEndUserInputName() }}"
                value="{{ question.fields.default_value }}"
                aria-label="{{ label }}"
                {{ question.fields.is_mandatory ? 'required' : '' }}
            >
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'question'   => $question,
            'label'      => $question->fields['name'],
        ]);
    }
}
