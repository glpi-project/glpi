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

namespace Glpi\Asset\Capacity;

use CommonGLPI;
use Glpi\Asset\CapacityConfig;
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
        return [];
    }

    public function isUsed(string $classname): bool
    {
        return parent::isUsed($classname)
            && $this->countAssetsLinkedToPlugs($classname, Plug::class) > 0;
    }

    private function countAssetsLinkedToPlugs(string $asset_classname, string $relation_classname): int
    {
        return countDistinctElementsInTable(
            $relation_classname::getTable(),
            'items_id_main',
            [
                'itemtype_main' => $asset_classname,
            ]
        );
    }

    public function getCapacityUsageDescription(string $classname): string
    {
        return sprintf(
            __('%1$s plugs attached to %2$s assets'),
            $this->countPlugItemsUsage($classname, Plug::class),
            $this->countAssetsLinkedToPlugs($classname, Plug::class)
        );
    }

    private function countPlugItemsUsage(string $asset_classname, string $relation_classname): int
    {
        return countElementsInTable(
            $relation_classname::getTable(),
            [
                'itemtype_main'      => $asset_classname,
            ]
        );
    }

    public function onClassBootstrap(string $classname, CapacityConfig $config): void
    {
        $this->registerToTypeConfig('plug_types', $classname);

        CommonGLPI::registerStandardTab($classname, Plug::class, 55);
    }

    public function onCapacityDisabled(string $classname, CapacityConfig $config): void
    {
        // Unregister from types
        $this->unregisterFromTypeConfig('plug_types', $classname);

        //Delete related items
        $plug = new Plug();
        $plug->deleteByCriteria(['itemtype_main' => $classname], force: true, history: false);

        // Clean history related items
        $this->deleteRelationLogs($classname, Plug::class);
    }
}
