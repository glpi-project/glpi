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
use Infocom;
use Override;

class HasInfocomCapacity extends AbstractCapacity
{
    public function getLabel(): string
    {
        return Infocom::getTypeName();
    }

    public function getIcon(): string
    {
        return Infocom::getIcon();
    }

    #[Override]
    public function getDescription(): string
    {
        return __("Manage and track assets lifecycle, financial, administrative and warranty information");
    }

    public function getCloneRelations(): array
    {
        return [
            Infocom::class,
        ];
    }

    public function isUsed(string $classname): bool
    {
        return parent::isUsed($classname)
            && $this->countAssetsLinkedToPeerItem($classname, Infocom::class) > 0;
    }

    public function getCapacityUsageDescription(string $classname): string
    {
        return sprintf(
            __('Used by %1$s of %2$s assets'),
            $this->countAssetsLinkedToPeerItem($classname, Infocom::class),
            $this->countAssets($classname)
        );
    }

    public function onClassBootstrap(string $classname, CapacityConfig $config): void
    {
        $this->registerToTypeConfig('infocom_types', $classname);

        CommonGLPI::registerStandardTab($classname, Infocom::class, 50);
    }

    public function onCapacityDisabled(string $classname, CapacityConfig $config): void
    {
        // Unregister from infocom types
        $this->unregisterFromTypeConfig('infocom_types', $classname);

        // Delete related infocom data
        $infocom = new Infocom();
        $infocom->deleteByCriteria(['itemtype' => $classname], force: true, history: false);

        $infocom_search_options = Infocom::rawSearchOptionsToAdd($classname);

        // Clean history related to infocoms
        $this->deleteFieldsLogs($classname, $infocom_search_options);

        // Clean display preferences
        $this->deleteDisplayPreferences($classname, $infocom_search_options);
    }
}
