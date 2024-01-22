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

namespace Glpi\Asset\Capacity;

use CommonGLPI;
use Item_OperatingSystem;
use OperatingSystem;

class HasOperatingSystemCapacity extends AbstractCapacity
{
    // #Override
    public function getLabel(): string
    {
        return OperatingSystem::getTypeName();
    }

    // #Override
    public function onClassBootstrap(string $classname): void
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        // Allow our item to be linked to an operating system
        $CFG_GLPI['operatingsystem_types'][] = $classname;

        // Register the operating system tab into our item
        CommonGLPI::registerStandardTab(
            $classname,
            Item_OperatingSystem::class,
            10 // Tab should be somewhere near the top
        );
    }

    // #Override
    public function getSearchOptions(string $classname): array
    {
        return Item_OperatingSystem::rawSearchOptionsToAdd($classname);
    }

    // #Override
    public function onCapacityDisabled(string $classname): void
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        // Unregister from operating system types
        $CFG_GLPI['operatingsystem_types'] = array_diff(
            $CFG_GLPI['operatingsystem_types'],
            [$classname]
        );

        // Delete related operating system data
        $item_os = new Item_OperatingSystem();
        $item_os->deleteByCriteria(
            crit   : ['itemtype' => $classname],
            force  : true,
            history: false
        );

        // Clean history related to operating systems
        $this->deleteFieldsLogsByItemtypeLink($classname, [
            OperatingSystem::getType(),
            Item_OperatingSystem::getType(),
        ]);

        // Clean display preferences
        $this->deleteDisplayPreferences(
            $classname,
            Item_OperatingSystem::rawSearchOptionsToAdd($classname)
        );
    }
}
