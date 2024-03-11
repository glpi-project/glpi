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

/**
 * List of valid question types categories
 */
enum QuestionTypeCategory: string
{
    /**
     * Questions that expect short single line answers (text, number, ...)
     */
    case SHORT_ANSWER = "short_answer";

    /**
     * Question that expect long detailled answers (textarea)
     */
    case LONG_ANSWER = "long_answer";

    /**
     * Question that expect a date and time
     */
    case DATE_AND_TIME = "date_and_time";

    /**
     * Get category label
     * @return string
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::SHORT_ANSWER => __("Short answer"),
            self::LONG_ANSWER  => __("Long answer"),
            self::DATE_AND_TIME => __("Date and time"),
        };
    }
}
