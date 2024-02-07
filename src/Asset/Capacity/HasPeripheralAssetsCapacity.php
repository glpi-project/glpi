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
use Glpi\Asset\Asset_PeripheralAsset;
use Session;

class HasPeripheralAssetsCapacity extends AbstractCapacity
{
    public function getLabel(): string
    {
        return Asset_PeripheralAsset::getTypeName(Session::getPluralNumber());
    }

    public function getIcon(): string
    {
        return Asset_PeripheralAsset::getIcon();
    }

    public function getCloneRelations(): array
    {
        return [
            Asset_PeripheralAsset::class,
        ];
    }

    public function getSearchOptions(string $classname): array
    {
        return Asset_PeripheralAsset::rawSearchOptionsToAdd();
    }

    protected function countAssetsLinkedToPeerItem(string $asset_classname, string $relation_classname): int
    {
        return countDistinctElementsInTable(
            $relation_classname::getTable(),
            'items_id_asset',
            [
                'itemtype_asset' => $asset_classname,
            ]
        );
    }

    protected function countPeerItemsUsage(string $asset_classname, string $relation_classname): int
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $count = 0;
        foreach ($CFG_GLPI['directconnect_types'] as $peripheral_itemtype) {
            $count += countDistinctElementsInTable(
                $relation_classname::getTable(),
                'items_id_peripheral',
                [
                    'itemtype_asset'      => $asset_classname,
                    'itemtype_peripheral' => $peripheral_itemtype,
                ]
            );
        }

        return $count;
    }

    public function isUsed(string $classname): bool
    {
        return parent::isUsed($classname)
            && $this->countAssetsLinkedToPeerItem($classname, Asset_PeripheralAsset::class) > 0;
    }

    public function getCapacityUsageDescription(string $classname): string
    {
        return sprintf(
            __('%1$s peripheral assets attached to %2$s assets'),
            $this->countPeerItemsUsage($classname, Asset_PeripheralAsset::class),
            $this->countAssetsLinkedToPeerItem($classname, Asset_PeripheralAsset::class)
        );
    }

    public function onClassBootstrap(string $classname): void
    {
        // Allow the asset to be linked to peripheral asset
        $this->registerToTypeConfig('peripheralhost_types', $classname);

        CommonGLPI::registerStandardTab($classname, Asset_PeripheralAsset::class, 55);
    }

    public function onCapacityDisabled(string $classname): void
    {
        // Unregister from peripheral hosts types
        $this->unregisterFromTypeConfig('peripheralhost_types', $classname);

        // Delete related items
        $relation = new Asset_PeripheralAsset();
        $relation->deleteByCriteria(['itemtype_asset' => $classname], force: true, history: false);

        // Clean history related items
        $this->deleteRelationLogs($classname, Asset_PeripheralAsset::class);

        // Clean display preferences
        $relation_search_options = Asset_PeripheralAsset::rawSearchOptionsToAdd();
        $this->deleteDisplayPreferences($classname, $relation_search_options);
    }
}
