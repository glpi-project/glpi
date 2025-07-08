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
use Item_OperatingSystem;
use OperatingSystem;
use Override;

class HasOperatingSystemCapacity extends AbstractCapacity
{
    // #Override
    public function getLabel(): string
    {
        return OperatingSystem::getTypeName();
    }

    public function getIcon(): string
    {
        return OperatingSystem::getIcon();
    }

    #[Override]
    public function getDescription(): string
    {
        return __("Display operating system information");
    }

    public function isUsed(string $classname): bool
    {
        return parent::isUsed($classname)
            && $this->countAssetsLinkedToPeerItem($classname, Item_OperatingSystem::class) > 0;
    }

    public function getCapacityUsageDescription(string $classname): string
    {
        return sprintf(
            __('Used by %1$s of %2$s assets'),
            $this->countAssetsLinkedToPeerItem($classname, Item_OperatingSystem::class),
            $this->countAssets($classname)
        );
    }

    // #Override
    public function onClassBootstrap(string $classname, CapacityConfig $config): void
    {
        $this->registerToTypeConfig('operatingsystem_types', $classname);

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

    public function getCloneRelations(): array
    {
        return [
            Item_OperatingSystem::class,
        ];
    }

    // #Override
    public function onCapacityDisabled(string $classname, CapacityConfig $config): void
    {
        // Unregister from operating system types
        $this->unregisterFromTypeConfig('operatingsystem_types', $classname);

        // Delete related operating system data
        $item_os = new Item_OperatingSystem();
        $item_os->deleteByCriteria(
            crit   : ['itemtype' => $classname],
            force  : true,
            history: false
        );

        // Clean history related to operating systems
        $this->deleteRelationLogs($classname, OperatingSystem::getType());
        $this->deleteRelationLogs($classname, Item_OperatingSystem::getType());

        // Clean display preferences
        $this->deleteDisplayPreferences(
            $classname,
            Item_OperatingSystem::rawSearchOptionsToAdd($classname)
        );
    }
}
