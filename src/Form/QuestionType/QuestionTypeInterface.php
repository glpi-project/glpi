<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

use Glpi\Form\Question;

/**
 * Interface that must be implemented by all available questions types
 */
interface QuestionTypeInterface
{
    /**
     * Render the administration template for the given question.
     * This template is used on the form editor page.
     *
     * @param Question|null $question     Given question's data. May be null for a new question.
     * @param string|null   $input_prefix Prefix to use for input names. May be null for a new question.
     *                                    For a given prefix, the input's names must be "_question[<prefix>][<name>]".
     *
     * @return string
     */
    public function renderAdminstrationTemplate(
        ?Question $question,
        ?string $input_prefix = null
    ): string;

    /**
     * Render the end up user template for a given question.
     * This template is used when rendered forms are displayed to users.
     *
     * @param Question $question Given question's data.
     *
     * @return string
     */
    public function renderEndUserTemplate(
        Question $question,
    ): string;

    /**
     * Render the given answer.
     * This template is used when rendering answers for a form.
     *
     * @param mixed $answer Given raw answer data.
     *
     * @return string
     */
    public function renderAnswerTemplate($answer): string;
}
