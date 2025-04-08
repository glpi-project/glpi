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
use Item_RemoteManagement;
use Override;
use Session;

class HasRemoteManagementCapacity extends AbstractCapacity
{
    public function getLabel(): string
    {
        return Item_RemoteManagement::getTypeName(Session::getPluralNumber());
    }

    public function getIcon(): string
    {
        return Item_RemoteManagement::getIcon();
    }

    #[Override]
    public function getDescription(): string
    {
        return __("Generate links for common remote access and control services");
    }

    public function getCloneRelations(): array
    {
        return [
            Item_RemoteManagement::class,
        ];
    }

    public function isUsed(string $classname): bool
    {
        return parent::isUsed($classname)
            && $this->countAssetsLinkedToPeerItem($classname, Item_RemoteManagement::class) > 0;
    }

    public function getCapacityUsageDescription(string $classname): string
    {
        return sprintf(
            __('%1$s remote management items attached to %2$s assets'),
            $this->countPeerItemsUsage($classname, Item_RemoteManagement::class),
            $this->countAssetsLinkedToPeerItem($classname, Item_RemoteManagement::class)
        );
    }

    public function onClassBootstrap(string $classname, CapacityConfig $config): void
    {
        $this->registerToTypeConfig('remote_management_types', $classname);

        CommonGLPI::registerStandardTab($classname, Item_RemoteManagement::class, 60);
    }

    public function getSearchOptions(string $classname): array
    {
        return Item_RemoteManagement::rawSearchOptionsToAdd($classname);
    }

    public function onCapacityDisabled(string $classname, CapacityConfig $config): void
    {
        $this->unregisterFromTypeConfig('remote_management_types', $classname);

        $remotemanagement_item = new Item_RemoteManagement();
        $remotemanagement_item->deleteByCriteria([
            'itemtype' => $classname,
        ], true, false);

        $this->deleteRelationLogs($classname, Item_RemoteManagement::class);
        $this->deleteDisplayPreferences($classname, Item_RemoteManagement::rawSearchOptionsToAdd($classname));
    }
}
