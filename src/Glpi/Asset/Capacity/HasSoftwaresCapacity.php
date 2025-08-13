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
use Item_SoftwareLicense;
use Item_SoftwareVersion;
use Override;
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

    public function getIcon(): string
    {
        return Software::getIcon();
    }

    #[Override]
    public function getDescription(): string
    {
        return __("List installed software");
    }

    public function getCloneRelations(): array
    {
        return [
            Item_SoftwareVersion::class,
            Item_SoftwareLicense::class,
        ];
    }

    public function isUsed(string $classname): bool
    {
        return parent::isUsed($classname)
            && (
                $this->countAssetsLinkedToPeerItem($classname, Item_SoftwareVersion::class) > 0
                || $this->countAssetsLinkedToPeerItem($classname, Item_SoftwareLicense::class) > 0
            );
    }

    public function getCapacityUsageDescription(string $classname): string
    {
        global $DB;

        $assets_ids = [];
        $softwares_ids = [];

        $asset_table    = $classname::getTable();
        $software_table = Software::getTable();
        $versions_table = SoftwareVersion::getTable();
        $item_v_table   = Item_SoftwareVersion::getTable();
        $licences_table = SoftwareLicense::getTable();
        $item_l_table   = Item_SoftwareLicense::getTable();

        // Get relations between asset and software versions
        $versions_iterator = $DB->request([
            'SELECT'     => [
                $software_table . '.id AS softwares_id',
                $asset_table . '.id AS assets_id',
            ],
            'FROM'       => $item_v_table,
            'INNER JOIN' => [
                $versions_table => [
                    'ON' => [
                        $item_v_table   => 'softwareversions_id',
                        $versions_table => 'id',
                    ],
                ],
                $software_table => [
                    'ON' => [
                        $versions_table => 'softwares_id',
                        $software_table => 'id',
                    ],
                ],
                $asset_table => [
                    'ON' => [
                        $item_v_table   => 'items_id',
                        $asset_table    => 'id',
                    ],
                ],
            ],
            'WHERE'      => [
                'itemtype' => $classname,
            ],
        ]);
        foreach ($versions_iterator as $version_data) {
            if (!in_array($version_data['softwares_id'], $softwares_ids, true)) {
                $softwares_ids[] = $version_data['softwares_id'];
            }
            if (!in_array($version_data['assets_id'], $assets_ids, true)) {
                $assets_ids[] = $version_data['assets_id'];
            }
        }

        // Get relations between asset and software licences
        $versions_iterator = $DB->request([
            'SELECT' => [
                $software_table . '.id AS softwares_id',
                $asset_table . '.id AS assets_id',
            ],
            'FROM'       => $item_l_table,
            'INNER JOIN' => [
                $licences_table => [
                    'ON' => [
                        $item_l_table   => 'softwarelicenses_id',
                        $licences_table => 'id',
                    ],
                ],
                $software_table => [
                    'ON' => [
                        $licences_table => 'softwares_id',
                        $software_table => 'id',
                    ],
                ],
                $asset_table => [
                    'ON' => [
                        $item_l_table   => 'items_id',
                        $asset_table    => 'id',
                    ],
                ],
            ],
            'WHERE'  => [
                'itemtype' => $classname,
            ],
        ]);
        foreach ($versions_iterator as $version_data) {
            if (!in_array($version_data['softwares_id'], $softwares_ids, true)) {
                $softwares_ids[] = $version_data['softwares_id'];
            }
            if (!in_array($version_data['assets_id'], $assets_ids, true)) {
                $assets_ids[] = $version_data['assets_id'];
            }
        }

        return sprintf(
            __('%1$s software attached to %2$s assets'),
            count($softwares_ids),
            count($assets_ids)
        );
    }

    public function onClassBootstrap(string $classname, CapacityConfig $config): void
    {
        $this->registerToTypeConfig('software_types', $classname);

        CommonGLPI::registerStandardTab($classname, Item_SoftwareVersion::class, 85);
    }

    public function onCapacityDisabled(string $classname, CapacityConfig $config): void
    {
        // Unregister from software types
        $this->unregisterFromTypeConfig('software_types', $classname);

        //Delete related softwares
        $softwares_version = new Item_SoftwareVersion();
        $softwares_version->deleteByCriteria(
            [
                'itemtype' => $classname,
            ],
            force: true,
            history: false
        );

        $software_licenses = new Item_SoftwareLicense();
        $software_licenses->deleteByCriteria(
            [
                'itemtype' => $classname,
            ],
            force: true,
            history: false
        );

        // Clean history related to softwares
        $this->deleteRelationLogs($classname, Item_SoftwareVersion::class);
        $this->deleteRelationLogs($classname, Item_SoftwareLicense::class);
        $this->deleteRelationLogs($classname, Software::class);
        $this->deleteRelationLogs($classname, SoftwareVersion::class);
        $this->deleteRelationLogs($classname, SoftwareLicense::class);

        // Clean display preferences
        $softwares_search_options = Item_SoftwareVersion::getSearchOptionsToAdd($classname::getType());
        $this->deleteDisplayPreferences($classname, $softwares_search_options);
    }
}
