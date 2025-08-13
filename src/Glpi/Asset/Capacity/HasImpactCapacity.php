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
use Config;
use Glpi\Asset\CapacityConfig;
use Impact;
use ImpactItem;
use ImpactRelation;
use Override;
use Toolbox;

use function Safe\json_decode;
use function Safe\json_encode;

class HasImpactCapacity extends AbstractCapacity
{
    public function getLabel(): string
    {
        return Impact::getTypeName(1);
    }

    public function getIcon(): string
    {
        return Impact::getIcon();
    }

    #[Override]
    public function getDescription(): string
    {
        return __("Enable \"Impact analysis\" tab and the assets can also be displayed in the impact tab of other asset types");
    }

    private function countImpactRelations(string $classname): int
    {
        return countElementsInTable(ImpactRelation::getTable(), [
            'OR' => [
                'itemtype_source' => $classname,
                'itemtype_impacted' => $classname,
            ],
        ]);
    }

    private function countAssetsUsingImpact(string $classname): int
    {
        $source_assets_count = \countDistinctElementsInTable(
            ImpactRelation::getTable(),
            'items_id_source',
            [
                'itemtype_source' => $classname,
            ]
        );
        $impacted_assets_count = \countDistinctElementsInTable(
            ImpactRelation::getTable(),
            'items_id_impacted',
            [
                'itemtype_impacted' => $classname,
            ]
        );
        return $source_assets_count + $impacted_assets_count;
    }

    public function isUsed(string $classname): bool
    {
        //NOTE: This doesn't consider cases where assets exist inside graphs on other asset types. Maybe there is no good solution since it involves scanning every graph.
        return parent::isUsed($classname)
            && $this->countImpactRelations($classname) > 0;
    }

    public function getCapacityUsageDescription(string $classname): string
    {
        $impact_count = $this->countImpactRelations($classname);
        $asset_count = $this->countAssetsUsingImpact($classname);

        return sprintf(__('%1$s impact relations involving %2$s assets'), $impact_count, $asset_count);
    }

    public function onClassBootstrap(string $classname, CapacityConfig $config): void
    {
        global $CFG_GLPI;

        $CFG_GLPI['impact_asset_types'][$classname] = Toolbox::getPictureUrl(
            $classname::getDefinition()->fields['picture'],
            false
        );

        CommonGLPI::registerStandardTab($classname, Impact::class, 2);
    }

    public function onCapacityEnabled(string $classname, CapacityConfig $config): void
    {
        $enabled_types = json_decode(Config::getConfigurationValue('core', Impact::CONF_ENABLED)) ?? [];
        if (!in_array($classname, $enabled_types, true)) {
            $enabled_types[] = $classname;
            Config::setConfigurationValues('core', [Impact::CONF_ENABLED => json_encode($enabled_types)]);
        }
    }

    public function onCapacityDisabled(string $classname, CapacityConfig $config): void
    {
        global $CFG_GLPI;

        unset($CFG_GLPI['impact_asset_types'][$classname]);

        $enabled_types = json_decode(Config::getConfigurationValue('core', Impact::CONF_ENABLED)) ?? [];
        if (in_array($classname, $enabled_types, true)) {
            $enabled_types = \array_values(\array_diff($enabled_types, [$classname]));
            Config::setConfigurationValues('core', [Impact::CONF_ENABLED => json_encode($enabled_types)]);
        }

        $relation = new ImpactRelation();
        $relation->deleteByCriteria(
            [
                'OR' => [
                    'itemtype_source' => $classname,
                    'itemtype_impacted' => $classname,
                ],
            ],
            force: true,
            history: false
        );

        $impact_item = new ImpactItem();
        $impact_item->deleteByCriteria(
            [
                'itemtype' => $classname,
            ],
            force: true,
            history: false
        );

        // Clean history related to links
        $this->deleteRelationLogs($classname, ImpactItem::class);
    }
}
