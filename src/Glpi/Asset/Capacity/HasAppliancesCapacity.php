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

use Appliance;
use Appliance_Item;
use CommonGLPI;
use Session;

class HasAppliancesCapacity extends AbstractCapacity
{
    public function getLabel(): string
    {
        return Appliance::getTypeName(Session::getPluralNumber());
    }

    public function getIcon(): string
    {
        return Appliance::getIcon();
    }

    public function isUsed(string $classname): bool
    {
        return parent::isUsed($classname)
            && $this->countAssetsLinkedToPeerItem($classname, Appliance_Item::class) > 0;
    }

    public function getCapacityUsageDescription(string $classname): string
    {
        return sprintf(
            __('%1$s appliances attached to %2$s assets'),
            $this->countPeerItemsUsage($classname, Appliance_Item::class),
            $this->countAssetsLinkedToPeerItem($classname, Appliance_Item::class)
        );
    }

    public function onClassBootstrap(string $classname): void
    {
        $this->registerToTypeConfig('appliance_types', $classname);
        CommonGLPI::registerStandardTab($classname, Appliance_Item::class, 85);
    }

    public function onCapacityDisabled(string $classname): void
    {
        $this->unregisterFromTypeConfig('appliance_types', $classname);

        $appliance_item = new Appliance_Item();
        $appliance_item->deleteByCriteria([
            'itemtype' => $classname,
        ], true, false);

        $this->deleteRelationLogs($classname, Appliance_Item::class);
        $this->deleteRelationLogs($classname, Appliance::class);
        $this->deleteDisplayPreferences($classname, Appliance::rawSearchOptionsToAdd($classname));
    }
}
