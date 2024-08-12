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

namespace Glpi\Form\Export\Context;

use Glpi\DBAL\JsonFieldInterface;
use Glpi\Form\Export\Specification\DataRequirementSpecification;

/**
 * Must be implemented by all JsonFieldInterface objects that contains references
 * to database ids.
 *
 * The method of this interface will be used by the form serializer to ensure
 * that form exports can be done correctly as an export can't contains hardcoded
 * database ids.
 */
interface JsonFieldReferencingDatabaseIdsInterface
{
    /**
     * Must return the same values as `JsonFieldInterface::jsonSeserialize` but
     * with all database ids removed and replaced by the items names instead.
     *
     * @return array
     */
    public function jsonSerializeWithoutDatabaseIds(): array;

    /**
     * Must return the same value as `JsonFieldInterface::jsonDeserialize` but
     * the supplied $data parameter will contains items names instead of ids.
     *
     * The correct ids must be inserted using the ReadonlyDatabaseMapper instance.
     *
     * @param ReadonlyDatabaseMapper $mapper
     * @param array $data
     * @return JsonFieldInterface
     */
    public static function jsonDeserializeWithoutDatabaseIds(
        ReadonlyDatabaseMapper $mapper,
        array $data,
    ): JsonFieldInterface;

    /**
     * All ids replaced by the `jsonSerializeWithoutDatabaseIds` method must be
     * referenced here by creating a DataRequirementSpecification object per id.
     *
     *  @return DataRequirementSpecification[]
     */
    public function getJsonDeserializeWithoutDatabaseIdsRequirements(): array;
}
