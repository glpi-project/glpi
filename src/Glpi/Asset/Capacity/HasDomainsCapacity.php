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
use Domain;
use Domain_Item;
use Session;

class HasDomainsCapacity extends AbstractCapacity
{
    public function getLabel(): string
    {
        return Domain::getTypeName(Session::getPluralNumber());
    }

    public function getIcon(): string
    {
        return Domain::getIcon();
    }

    public function isUsed(string $classname): bool
    {
        return parent::isUsed($classname)
            && $this->countAssetsLinkedToPeerItem($classname, Domain_Item::class) > 0;
    }

    public function getCapacityUsageDescription(string $classname): string
    {
        return sprintf(
            __('%1$s domains attached to %2$s assets'),
            $this->countPeerItemsUsage($classname, Domain_Item::class),
            $this->countAssetsLinkedToPeerItem($classname, Domain_Item::class)
        );
    }

    public function onClassBootstrap(string $classname): void
    {
        $this->registerToTypeConfig('domain_types', $classname);

        CommonGLPI::registerStandardTab(
            $classname,
            Domain_Item::class,
            65,
        );
    }

    public function onCapacityDisabled(string $classname): void
    {
        // Unregister from domain types
        $this->unregisterFromTypeConfig('domain_types', $classname);

        //Delete related domains
        $domains = new Domain_Item();
        $domains->deleteByCriteria(
            [
                'itemtype' => $classname
            ],
            force: true,
            history: false
        );

        // Clean history related to domains
        $this->deleteRelationLogs($classname, Domain::class);
        $this->deleteRelationLogs($classname, Domain_Item::class);

        // Clean display preferences
        $this->deleteDisplayPreferences($classname, $this->getSearchOptions($classname));
    }
}
