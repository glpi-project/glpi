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

namespace Glpi\Tests\Api\Deprecated;

interface DeprecatedInterface
{
    /**
     * Get deprecated type
     * @return string
     */
    public static function getDeprecatedType(): string;

    /**
     * Get current type
     * @return string
     */
    public static function getCurrentType(): string;

    /**
     * Get deprecated expected fields
     * @return array
     */
    public static function getDeprecatedFields(): array;

    /**
     * Get current add input
     * @return array
     */
    public static function getCurrentAddInput(): array;

    /**
     * Get deprecated add input
     * @return array
     */
    public static function getDeprecatedAddInput(): array;

    /**
     * Get deprecated update input
     * @return array
     */
    public static function getDeprecatedUpdateInput(): array;

    /**
     * Get expected data after insert
     * @return array
     */
    public static function getExpectedAfterInsert(): array;

    /**
     * Get expected data after update
     * @return array
     */
    public static function getExpectedAfterUpdate(): array;

    /**
     * Get deprecated search query
     * @return string
     */
    public static function getDeprecatedSearchQuery(): string;

    /**
     * Get current search query
     * @return string
     */
    public static function getCurrentSearchQuery(): string;
}
