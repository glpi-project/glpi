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

use Certificate;
use Certificate_Item;
use CommonGLPI;

class HasCertificatesCapacity extends AbstractCapacity
{
    public function getLabel(): string
    {
        return Certificate::getTypeName();
    }

    public function onClassBootstrap(string $classname): void
    {
        $this->registerToTypeConfig('certificate_types', $classname);

        CommonGLPI::registerStandardTab(
            $classname,
            Certificate_Item::class,
            PHP_INT_MAX
        );
    }

    public function onCapacityDisabled(string $classname): void
    {
        // Unregister from certificate types
        $this->unregisterFromTypeConfig('certificate_types', $classname);

        // Delete related certificate data
        $certificate = new Certificate_Item();
        $certificate->deleteByCriteria([
            'itemtype' => $classname,
        ]);

        // Clean history related to certificates
        $this->deleteRelationLogs($classname, Certificate::class);

        // Clean display preferences
        $certificate_search_options = Certificate::rawSearchOptionsToAdd($classname);
        $this->deleteDisplayPreferences($classname, $certificate_search_options);
    }
}
