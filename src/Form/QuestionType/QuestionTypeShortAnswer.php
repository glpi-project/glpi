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

/**
 * Short answers are single line inputs used to answer simple questions.
 */
class QuestionTypeShortAnswer implements QuestionTypeInterface
{
    public function renderAdminstrationTemplate(?Question $question): string
    {
        $template = <<<TWIG
            <input
                class="form-control mb-2"
                type="text"
                name="default_value"
                placeholder="{{ placehoder|e('html_attr') }}"
                value="{{ question is not null ? question.fields.default_value|e('html_attr') : '' }}"
            />
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'question'   => $question,
            'placehoder' => __('No default value'),
        ]);
    }

    public function renderEndUserTemplate(
        Question $question,
    ): string {
        $template = <<<TWIG
            <input
                type="text"
                class="form-control"
                name="answers[{{ question.fields.id|e('html_attr') }}]"
                value="{{ question.fields.default_value|e('html_attr') }}"
                {{ question.fields.is_mandatory ? 'required' : '' }}
            >
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'question' => $question,
        ]);
    }

    public function renderAnswerTemplate($answer): string
    {
        $template = <<<TWIG
            <div class="form-control-plaintext">{{ answer }}</div>
TWIG;

        $twig = TemplateRenderer::getInstance();
        return $twig->renderFromStringTemplate($template, [
            'answer' => $answer,
        ]);
    }
}
