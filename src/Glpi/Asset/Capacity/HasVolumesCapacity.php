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
use Item_Disk;
use Override;
use Session;

class HasVolumesCapacity extends AbstractCapacity
{
    public function getLabel(): string
    {
        return Item_Disk::getTypeName(Session::getPluralNumber());
    }

    public function getIcon(): string
    {
        return Item_Disk::getIcon();
    }

    #[Override]
    public function getDescription(): string
    {
        return __("List storage volumes");
    }

    public function getSearchOptions(string $classname): array
    {
        return Item_Disk::rawSearchOptionsToAdd($classname::getType());
    }

    public function getCloneRelations(): array
    {
        return [
            Item_Disk::class,
        ];
    }

    public function isUsed(string $classname): bool
    {
        return parent::isUsed($classname)
            && $this->countAssetsLinkedToPeerItem($classname, Item_Disk::class) > 0;
    }

    public function getCapacityUsageDescription(string $classname): string
    {
        return sprintf(
            __('%1$s volumes attached to %2$s assets'),
            $this->countPeerItemsUsage($classname, Item_Disk::class),
            $this->countAssetsLinkedToPeerItem($classname, Item_Disk::class)
        );
    }

    public function onClassBootstrap(string $classname, CapacityConfig $config): void
    {
        $this->registerToTypeConfig('disk_types', $classname);

        CommonGLPI::registerStandardTab($classname, Item_Disk::class, 55);
    }

    public function onCapacityDisabled(string $classname, CapacityConfig $config): void
    {
        // Unregister from disk types
        $this->unregisterFromTypeConfig('disk_types', $classname);

        //Delete related disks
        $disks = new Item_Disk();
        $disks->deleteByCriteria(['itemtype' => $classname], force: true, history: false);

        // Clean history related to disks
        $this->deleteRelationLogs($classname, Item_Disk::class);

        // Clean display preferences
        $disks_search_options = Item_Disk::rawSearchOptionsToAdd($classname);
        $this->deleteDisplayPreferences($classname, $disks_search_options);
    }
}
