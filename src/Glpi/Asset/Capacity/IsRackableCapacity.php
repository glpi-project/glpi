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

use Item_Rack;
use Rack;
use Session;

class IsRackableCapacity extends AbstractCapacity
{
    public function getLabel(): string
    {
        return Rack::getTypeName(Session::getPluralNumber());
    }

    public function getIcon(): string
    {
        return Rack::getIcon();
    }

    public function getSearchOptions(string $classname): array
    {
        return Rack::rawSearchOptionsToAdd($classname::getType());
    }

    public function isUsed(string $classname): bool
    {
        return parent::isUsed($classname)
            && $this->countAssetsLinkedToPeerItem($classname, Item_Rack::class) > 0;
    }

    public function getCapacityUsageDescription(string $classname): string
    {
        return sprintf(
            __('Used by %1$s of %2$s assets'),
            $this->countAssetsLinkedToPeerItem($classname, Item_Rack::class),
            $this->countAssets($classname)
        );
    }

    public function onClassBootstrap(string $classname): void
    {
        $this->registerToTypeConfig('rackable_types', $classname);
    }

    public function onCapacityDisabled(string $classname): void
    {
        $this->unregisterFromTypeConfig('rackable_types', $classname);

        $item_rack = new Item_Rack();
        $item_rack->deleteByCriteria(['itemtype' => $classname], force: true, history: false);

        $this->deleteRelationLogs($classname, Item_Rack::class);
        $this->deleteRelationLogs($classname, Rack::class);

        $search_opts = Rack::rawSearchOptionsToAdd($classname);
        $this->deleteDisplayPreferences($classname, $search_opts);
    }
}
