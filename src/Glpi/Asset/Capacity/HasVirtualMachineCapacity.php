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
use Glpi\Asset\CapacityConfig;
use ItemVirtualMachine;
use Override;
use Session;

class HasVirtualMachineCapacity extends AbstractCapacity
{
    public function getLabel(): string
    {
        return ItemVirtualMachine::getTypeName(Session::getPluralNumber());
    }

    public function getIcon(): string
    {
        return ItemVirtualMachine::getIcon();
    }

    #[Override]
    public function getDescription(): string
    {
        return __("List virtual machines attached to these assets");
    }

    public function getCloneRelations(): array
    {
        return [
            ItemVirtualMachine::class,
        ];
    }

    public function getSearchOptions(string $classname): array
    {
        return ItemVirtualMachine::rawSearchOptionsToAdd($classname);
    }

    public function isUsed(string $classname): bool
    {
        return parent::isUsed($classname)
            && $this->countAssetsLinkedToPeerItem($classname, ItemVirtualMachine::class) > 0;
    }

    public function getCapacityUsageDescription(string $classname): string
    {
        return sprintf(
            __('%1$s virtual machines attached to %2$s assets'),
            $this->countPeerItemsUsage($classname, ItemVirtualMachine::class),
            $this->countAssetsLinkedToPeerItem($classname, ItemVirtualMachine::class)
        );
    }

    public function onClassBootstrap(string $classname, CapacityConfig $config): void
    {
        $this->registerToTypeConfig('itemvirtualmachines_types', $classname);

        CommonGLPI::registerStandardTab($classname, ItemVirtualMachine::class, 55);
    }

    public function onCapacityDisabled(string $classname, CapacityConfig $config): void
    {
        // Unregister from types
        $this->unregisterFromTypeConfig('itemvirtualmachines_types', $classname);

        //Delete related items
        $avs = new ItemVirtualMachine();
        $avs->deleteByCriteria(['itemtype' => $classname], force: true, history: false);

        // Clean history related items
        $this->deleteRelationLogs($classname, ItemVirtualMachine::class);

        // Clean display preferences
        $avs_search_options = ItemVirtualMachine::rawSearchOptionsToAdd($classname);
        $this->deleteDisplayPreferences($classname, $avs_search_options);
    }
}
