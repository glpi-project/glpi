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

namespace Glpi\Form\Tag;

interface CompositeTagValueInterface extends TagWithIdValueInterface
{
    /**
     * Extracts the ID to be remapped from the composite value.
     * Ex : "5:1" → "5"
     *
     * @param string $value The composite value
     * @return string The ID to be remapped
     */
    public function extractItemIdFromValue(string $value): string;

    /**
     * Rebuilds the composite value with a new ID.
     * Ex : ("5:1", "10") → "10:1"
     *
     * @param string $value The composite value
     * @param string $new_id The new ID to replace the old one
     * @return string The rebuilt composite value
     */
    public function rebuildValueWithMappedId(string $value, string $new_id): string;
}
