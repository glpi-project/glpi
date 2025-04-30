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

use Certificate;
use Certificate_Item;
use CommonGLPI;
use Glpi\Asset\CapacityConfig;
use Override;
use Session;

class HasCertificatesCapacity extends AbstractCapacity
{
    public function getLabel(): string
    {
        return Certificate::getTypeName(Session::getPluralNumber());
    }

    public function getIcon(): string
    {
        return Certificate::getIcon();
    }

    #[Override]
    public function getDescription(): string
    {
        return __("Track certificates used by the assets");
    }

    public function getCloneRelations(): array
    {
        return [
            Certificate_Item::class,
        ];
    }

    public function getSearchOptions(string $classname): array
    {
        return Certificate::rawSearchOptionsToAdd($classname);
    }

    public function isUsed(string $classname): bool
    {
        return parent::isUsed($classname)
            && $this->countAssetsLinkedToPeerItem($classname, Certificate_Item::class) > 0;
    }

    public function getCapacityUsageDescription(string $classname): string
    {
        return sprintf(
            __('%1$s certificates attached to %2$s assets'),
            $this->countPeerItemsUsage($classname, Certificate_Item::class),
            $this->countAssetsLinkedToPeerItem($classname, Certificate_Item::class)
        );
    }

    public function onClassBootstrap(string $classname, CapacityConfig $config): void
    {
        $this->registerToTypeConfig('certificate_types', $classname);

        CommonGLPI::registerStandardTab(
            $classname,
            Certificate_Item::class,
            60
        );
    }

    public function onCapacityDisabled(string $classname, CapacityConfig $config): void
    {
        // Unregister from document types
        $this->unregisterFromTypeConfig('certificate_types', $classname);

        // Delete relations to certificates
        $certificate_item = new Certificate_Item();
        $certificate_item->deleteByCriteria(
            [
                'itemtype' => $classname,
            ],
            force: true,
            history: false
        );

        // Clean history related to certificates
        $this->deleteRelationLogs($classname, Certificate::class);

        // Clean display preferences
        $this->deleteDisplayPreferences($classname, Certificate::rawSearchOptionsToAdd($classname));
    }
}
