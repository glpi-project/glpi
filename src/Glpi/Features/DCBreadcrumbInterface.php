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

namespace Glpi\Features;

use Enclosure;
use Rack;

interface DCBreadcrumbInterface
{
    /**
     * Specific value for "Data center position".
     *
     * @param int $items_id
     *
     * @return string
     */
    public static function renderDcBreadcrumb(int $items_id): string;

    /**
     * Get parent Enclosure.
     *
     * @return Enclosure|null
     */
    public function getParentEnclosure(): ?Enclosure;

    /**
     * Get position in Enclosure.
     *
     * @return int|null
     */
    public function getPositionInEnclosure(): ?int;

    /**
     * Get parent Rack.
     *
     * @return Rack|null
     */
    public function getParentRack(): ?Rack;

    /**
     * Get position in Rack.
     *
     * @return int|null
     */
    public function getPositionInRack(): ?int;
}
