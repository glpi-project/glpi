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
use Config;
use Impact;
use ImpactItem;
use ImpactRelation;

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

    private function countImpactRelations(string $classname): int
    {
        return countElementsInTable(ImpactRelation::getTable(), [
            'OR' => [
                'itemtype_source' => $classname,
                'itemtype_impacted' => $classname
            ]
        ]);
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
        $asset_count = $this->countAssets($classname);

        return sprintf(__('%1$s impact relations involving %2$s assets'), $impact_count, $asset_count);
    }

    public function onClassBootstrap(string $classname): void
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $CFG_GLPI['impact_asset_types'][$classname] = $classname::getDefinition()->fields['picture'];

        $enabled_types = json_decode(Config::getConfigurationValue('core', Impact::CONF_ENABLED), true) ?? [];
        if (!in_array($classname, $enabled_types, true)) {
            $enabled_types[] = $classname;
            Config::setConfigurationValues('core', [Impact::CONF_ENABLED => json_encode($enabled_types)]);
        }

        CommonGLPI::registerStandardTab($classname, Impact::class, 2);
    }

    public function onCapacityDisabled(string $classname): void
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        unset($CFG_GLPI['impact_asset_types'][$classname]);

        $enabled_types = json_decode(Config::getConfigurationValue('core', Impact::CONF_ENABLED), true) ?? [];
        $enabled_types = array_diff($enabled_types, [$classname]);
        Config::setConfigurationValues('core', [Impact::CONF_ENABLED => json_encode($enabled_types)]);

        $impact_item = new ImpactItem();
        $impact_item->deleteByCriteria([
            'itemtype' => $classname,
        ], true, false);

        // Clean history related to links
        $this->deleteRelationLogs($classname, ImpactItem::class);
    }
}
