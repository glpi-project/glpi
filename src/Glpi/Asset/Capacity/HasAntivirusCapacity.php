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
use ItemAntivirus;
use Override;
use Session;

class HasAntivirusCapacity extends AbstractCapacity
{
    public function getLabel(): string
    {
        return ItemAntivirus::getTypeName(Session::getPluralNumber());
    }

    public function getIcon(): string
    {
        return ItemAntivirus::getIcon();
    }

    #[Override]
    public function getDescription(): string
    {
        return __("List antivirus software");
    }

    public function getCloneRelations(): array
    {
        return [
            ItemAntivirus::class,
        ];
    }

    public function getSearchOptions(string $classname): array
    {
        return ItemAntivirus::rawSearchOptionsToAdd();
    }

    public function isUsed(string $classname): bool
    {
        return parent::isUsed($classname)
            && $this->countAssetsLinkedToPeerItem($classname, ItemAntivirus::class) > 0;
    }

    public function getCapacityUsageDescription(string $classname): string
    {
        return sprintf(
            __('%1$s antiviruses attached to %2$s assets'),
            $this->countPeerItemsUsage($classname, ItemAntivirus::class),
            $this->countAssetsLinkedToPeerItem($classname, ItemAntivirus::class)
        );
    }

    public function onClassBootstrap(string $classname, CapacityConfig $config): void
    {
        $this->registerToTypeConfig('itemantivirus_types', $classname);

        CommonGLPI::registerStandardTab($classname, ItemAntivirus::class, 55);
    }

    public function onCapacityDisabled(string $classname, CapacityConfig $config): void
    {
        // Unregister from types
        $this->unregisterFromTypeConfig('itemantivirus_types', $classname);

        //Delete related items
        $avs = new ItemAntivirus();
        $avs->deleteByCriteria(['itemtype' => $classname], force: true, history: false);

        // Clean history related items
        $this->deleteRelationLogs($classname, ItemAntivirus::class);

        // Clean display preferences
        $avs_search_options = ItemAntivirus::rawSearchOptionsToAdd();
        $this->deleteDisplayPreferences($classname, $avs_search_options);
    }
}
