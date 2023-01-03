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

namespace Glpi\Api\Deprecated;

/**
 * @since 9.5
 */

interface DeprecatedInterface
{
    /**
     * Get the deprecated itemtype
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Convert current hateoas to deprecated hateoas
     *
     * @param array $hateoas
     * @return array
     */
    public function mapCurrentToDeprecatedHateoas(array $hateoas): array;

    /**
     * Convert current fields to deprecated fields
     *
     * @param array $fields
     * @return array
     */
    public function mapCurrentToDeprecatedFields(array $fields): array;

    /**
     * Convert current searchoptions to deprecated searchoptions
     *
     * @param array $soptions
     * @return array
     */
    public function mapCurrentToDeprecatedSearchOptions(array $soptions): array;

    /**
     * Convert deprecated fields to current fields
     *
     * @param object $fields
     * @return object
     */
    public function mapDeprecatedToCurrentFields(object $fields): object;

    /**
     * Convert deprecated search criteria to current search criteria
     *
     * @param array $criteria
     * @return array
     */
    public function mapDeprecatedToCurrentCriteria(array $criteria): array;
}
