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
use Item_Project;
use Override;
use Project;

class IsProjectAssetCapacity extends AbstractCapacity
{
    public function getLabel(): string
    {
        return Project::getTypeName();
    }

    public function getIcon(): string
    {
        return Project::getIcon();
    }

    #[Override]
    public function getDescription(): string
    {
        return __("Can be associated to a project");
    }

    public function getCloneRelations(): array
    {
        return [
            Item_Project::class,
        ];
    }

    public function isUsed(string $classname): bool
    {
        return parent::isUsed($classname)
            && $this->countAssetsLinkedToPeerItem($classname, Item_Project::class) > 0;
    }

    public function getCapacityUsageDescription(string $classname): string
    {
        return sprintf(
            __('%1$s assets used in %2$s projects'),
            $this->countAssetsLinkedToPeerItem($classname, Item_Project::class),
            $this->countPeerItemsUsage($classname, Item_Project::class)
        );
    }

    public function onClassBootstrap(string $classname, CapacityConfig $config): void
    {
        // Allow our item to be linked to projects
        $this->registerToTypeConfig('project_asset_types', $classname);

        CommonGLPI::registerStandardTab($classname, Item_Project::class, 95);
    }

    public function onCapacityDisabled(string $classname, CapacityConfig $config): void
    {
        // Unregister from project assets types
        $this->unregisterFromTypeConfig('project_asset_types', $classname);

        // Delete related project data
        $project_item = new Item_Project();
        $project_item->deleteByCriteria(
            crit   : ['itemtype' => $classname],
            force  : true,
            history: false
        );

        // Clean history related to projects (both sides of the relation)
        $this->deleteRelationLogs($classname, Project::class);

        // Clean display preferences
        $this->deleteDisplayPreferences(
            $classname,
            Project::rawSearchOptionsToAdd($classname)
        );
    }
}
