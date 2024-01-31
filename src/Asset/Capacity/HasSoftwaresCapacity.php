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
use Item_SoftwareLicense;
use Item_SoftwareVersion;
use Session;
use Software;
use SoftwareLicense;
use SoftwareVersion;

class HasSoftwaresCapacity extends AbstractCapacity
{
    public function getLabel(): string
    {
        return Software::getTypeName(Session::getPluralNumber());
    }

    public function onClassBootstrap(string $classname): void
    {
        $this->registerToTypeConfig('software_types', $classname);

        CommonGLPI::registerStandardTab($classname, Item_SoftwareVersion::class, 85);
    }

    public function onCapacityDisabled(string $classname): void
    {
        // Unregister from software types
        $this->unregisterFromTypeConfig('software_types', $classname);

        //Delete related softwares
        $softwares = new Item_SoftwareVersion();
        $softwares->deleteByCriteria(['itemtype' => $classname], force: true, history: false);

        // Clean history related to softwares
        $this->deleteRelationLogs($classname, Item_SoftwareVersion::class);
        $this->deleteRelationLogs($classname, Software::class);
        $this->deleteRelationLogs($classname, SoftwareLicense::class);
        $this->deleteRelationLogs($classname, SoftwareVersion::class);

        // Clean display preferences
        $softwares_search_options = Item_SoftwareVersion::getSearchOptionsToAdd($classname::getType());
        $this->deleteDisplayPreferences($classname, $softwares_search_options);
    }
}
