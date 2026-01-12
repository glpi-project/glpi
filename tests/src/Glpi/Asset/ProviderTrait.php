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

namespace Glpi\Tests\Glpi\Asset;

trait ProviderTrait
{
    /**
     * @return \Generator Item type that are both assets and assignable
     */
    public static function assignableAssetsItemtypeProvider(): \Generator
    {
        global $CFG_GLPI;
        $assignable_assets_itemtypes = array_intersect($CFG_GLPI['assignable_types'], $CFG_GLPI['asset_types']);

        dump($assignable_assets_itemtypes);
        foreach ($assignable_assets_itemtypes as $itemtype) {
            yield $itemtype => [
                'class' => $itemtype,
            ];
        }
    }
}
