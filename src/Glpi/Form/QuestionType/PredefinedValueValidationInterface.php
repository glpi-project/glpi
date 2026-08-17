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

use Glpi\Form\Question;

/**
 * Question types whose predefined values (set through GET parameters) depend on
 * the question configuration must implement this interface.
 *
 * An invalid predefined value is discarded, leaving the default value that was
 * configured in the form editor untouched.
 */
interface PredefinedValueValidationInterface
{
    /**
     * Check whether a formatted predefined value can be applied to a question.
     *
     * @param string   $value    The value returned by `formatPredefinedValue()`
     * @param Question $question The question the value would be applied to
     */
    public function isValidPredefinedValue(string $value, Question $question): bool;
}
