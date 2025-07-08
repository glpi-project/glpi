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
use Item_Plug;
use Override;
use Plug;
use Session;

class HasPlugCapacity extends AbstractCapacity
{
    public function getLabel(): string
    {
        return Plug::getTypeName(Session::getPluralNumber());
    }

    public function getIcon(): string
    {
        return Plug::getIcon();
    }

    #[Override]
    public function getDescription(): string
    {
        return __("Has power plugs. Usually related to PDU or UPS");
    }

    public function getCloneRelations(): array
    {
        return [
            Item_Plug::class,
        ];
    }

    public function isUsed(string $classname): bool
    {
        return parent::isUsed($classname)
            && $this->countAssetsLinkedToPeerItem($classname, Item_Plug::class) > 0;
    }

    public function getCapacityUsageDescription(string $classname): string
    {
        return sprintf(
            __('%1$s plugs attached to %2$s assets'),
            $this->countPeerItemsUsage($classname, Item_Plug::class),
            $this->countAssetsLinkedToPeerItem($classname, Item_Plug::class)
        );
    }

    public function onClassBootstrap(string $classname, CapacityConfig $config): void
    {
        $this->registerToTypeConfig('plug_types', $classname);

        CommonGLPI::registerStandardTab($classname, Item_Plug::class, 55);
    }

    public function onCapacityDisabled(string $classname, CapacityConfig $config): void
    {
        // Unregister from types
        $this->unregisterFromTypeConfig('plug_types', $classname);

        //Delete related items
        $item_plug = new Item_Plug();
        $item_plug->deleteByCriteria(['itemtype' => $classname], force: true, history: false);

        // Clean history related items
        $this->deleteRelationLogs($classname, Item_Plug::class);
    }
}
