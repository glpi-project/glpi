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
use Contract;
use Contract_Item;

class HasContractsCapacity extends AbstractCapacity
{
    // #Override
    public function getLabel(): string
    {
        return Contract::getTypeName();
    }

    // #Override
    public function onClassBootstrap(string $classname): void
    {
        // Allow our item to be linked to contracts
        $this->registerToTypeConfig('contract_types', $classname);

        // Register the contracts tab into our item
        CommonGLPI::registerStandardTab(
            $classname,
            Contract_Item::class,
            20 // Tab shouldn't be too far from the top
        );
    }

    // #Override
    public function onCapacityDisabled(string $classname): void
    {
        // Unregister from contracts types
        $this->unregisterFromTypeConfig('contract_types', $classname);

        // Delete related contract data
        $contract_item = new Contract_Item();
        $contract_item->deleteByCriteria(
            crit   : ['itemtype' => $classname],
            force  : true,
            history: false
        );

        // Clean history related to contracts (both sides of the relation)
        $this->deleteRelationLogs($classname, Contract::getType());

        // Clean display preferences
        $this->deleteDisplayPreferences(
            $classname,
            Contract::rawSearchOptionsToAdd()
        );
    }
}
