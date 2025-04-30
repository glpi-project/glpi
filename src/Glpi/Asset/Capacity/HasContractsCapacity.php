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

namespace Glpi\Asset\Capacity;

use CommonGLPI;
use Contract;
use Contract_Item;
use Glpi\Asset\CapacityConfig;
use Override;

class HasContractsCapacity extends AbstractCapacity
{
    // #Override
    public function getLabel(): string
    {
        return Contract::getTypeName();
    }

    public function getIcon(): string
    {
        return Contract::getIcon();
    }

    #[Override]
    public function getDescription(): string
    {
        return __("Link contracts to the assets for costs, renewal and supplier tracking");
    }

    public function getCloneRelations(): array
    {
        return [
            Contract_Item::class,
        ];
    }

    public function isUsed(string $classname): bool
    {
        return parent::isUsed($classname)
            && $this->countAssetsLinkedToPeerItem($classname, Contract_Item::class) > 0;
    }

    public function getCapacityUsageDescription(string $classname): string
    {
        return sprintf(
            __('%1$s contracts attached to %2$s assets'),
            $this->countPeerItemsUsage($classname, Contract_Item::class),
            $this->countAssetsLinkedToPeerItem($classname, Contract_Item::class)
        );
    }

    // #Override
    public function onClassBootstrap(string $classname, CapacityConfig $config): void
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
    public function onCapacityDisabled(string $classname, CapacityConfig $config): void
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
