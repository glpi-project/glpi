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

namespace Glpi\Asset\CustomFieldOption;

use InvalidArgumentException;

interface OptionInterface
{
    /**
     * Get the option name.
     */
    public function getName(): string;

    /**
     * Get the option key.
     */
    public function getKey(): string;

    /**
     * Defines configured value.
     */
    public function setValue(mixed $value): void;

    /**
     * Get the configured value.
     */
    public function getValue(): mixed;

    /**
     * Get the HTML code to use to display the option input in the custom field form.
     */
    public function getFormInput(): string;

    /**
     * Normalize the value submitted using the input generated by `self::getFormInput()`.
     *
     * @param mixed $value
     * @throws InvalidArgumentException Thrown if the submitted value does not correspond to a valid value.
     */
    public function normalizeValue(mixed $value): mixed;

    /**
     * @return bool True if this option should be applied to the default value field
     */
    public function getApplyToDefault(): bool;
}
