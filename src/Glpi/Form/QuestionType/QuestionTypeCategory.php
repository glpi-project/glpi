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

use Override;

/**
 * List of valid question types categories
 */
enum QuestionTypeCategory: string implements QuestionTypeCategoryInterface
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
     * Question that expect actors (users, groups, suppliers or anonymous users)
     */
    case ACTORS = "actors";

    /**
     * Question that expect a urgency level
     */
    case URGENCY = "urgency";

    /**
     * Question that expect a request type
     */
    case REQUEST_TYPE = "request_type";

    /**
     * Question that expect a file upload
     */
    case FILE = "file";

    /**
     * Question that expect a single choice among a list of options
     */
    case RADIO = "radio";

    /**
     * Question that expect multiple choices among a list of options
     */
    case CHECKBOX = "checkbox";

    /**
     * Question that expect a single or muliple choice among a list of options with a dropdown
     */
    case DROPDOWN = "dropdown";

    /**
     * Question that expect an item selection
     */
    case ITEM = "item";

    #[Override]
    public function getLabel(): string
    {
        return match ($this) {
            self::SHORT_ANSWER => __("Short answer"),
            self::LONG_ANSWER  => __("Long answer"),
            self::DATE_AND_TIME => __("Date and time"),
            self::ACTORS => __("Actors"),
            self::URGENCY => __("Urgency"),
            self::REQUEST_TYPE => __("Request type"),
            self::FILE => __("File"),
            self::RADIO => __("Radio"),
            self::CHECKBOX => __("Checkbox"),
            self::DROPDOWN => _nx('form_editor', 'Dropdown', 'Dropdowns', 1),
            self::ITEM => _n('Item', 'Items', 1)
        };
    }

    #[Override]
    public function getIcon(): string
    {
        return match ($this) {
            self::SHORT_ANSWER => "ti ti-letter-case",
            self::LONG_ANSWER  => "ti ti-message",
            self::DATE_AND_TIME => "ti ti-calendar",
            self::ACTORS => "ti ti-user",
            self::URGENCY => "ti ti-hourglass",
            self::REQUEST_TYPE => "ti ti-tag",
            self::FILE => "ti ti-file",
            self::RADIO => "ti ti-circle-dot",
            self::CHECKBOX => "ti ti-select",
            self::DROPDOWN => "ti ti-list",
            self::ITEM => "ti ti-link",
        };
    }

    #[Override]
    public function getWeight(): int
    {
        return match ($this) {
            self::SHORT_ANSWER  => 10,
            self::LONG_ANSWER   => 20,
            self::DATE_AND_TIME => 30,
            self::ACTORS        => 40,
            self::URGENCY       => 50,
            self::REQUEST_TYPE  => 60,
            self::FILE          => 70,
            self::RADIO         => 80,
            self::CHECKBOX      => 90,
            self::DROPDOWN      => 100,
            self::ITEM          => 110,
        };
    }
}
