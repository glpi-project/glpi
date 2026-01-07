<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace tests\units\Glpi\Search;

use Glpi\Search\SearchEngine;
use Glpi\Tests\DbTestCase;

class SearchEngineTest extends DbTestCase
{
    public function testGetMetaParentItemtypesForTypesConfig(): void
    {
        global $CFG_GLPI;

        $search_engine = new SearchEngine();
        $exclusions = [
            'unicity_types',
            'asset_types',
            'report_types',
            'peripheralhost_types',
            'linkuser_types',
            'linkgroup_types',
            'linkuser_tech_types',
            'linkgroup_tech_types',
            'dictionnary_types',
            'helpdesk_visible_types',
            'device_types',
            'itemdevices_types',
            'itemdevicememory_types',
            'itemdevicepowersupply_types',
            'itemdevicenetworkcard_types',
            'itemdeviceharddrive_types',
            'itemdevicebattery_types',
            'itemdevicefirmware_types',
            'itemdevicesimcard_types',
            'itemdevicegeneric_types',
            'itemdevicepci_types',
            'itemdevicecontrol_types',
            'itemdevicedrive_types',
            'itemdevicesensor_types',
            'itemdeviceprocessor_types',
            'itemdevicesoundcard_types',
            'itemdevicegraphiccard_types',
            'itemdevicemotherboard_types',
            'itemdevicecamera_types',
            'notificationtemplates_types',
            'systeminformations_types',
            'planning_types',
            'planning_add_types',
            'globalsearch_types',
            'inventory_types',
            'kb_types',
            'disk_types',
            'kanban_types',
            'appliance_relation_types',
            'remote_management_types',
            'default_impact_asset_types',
            'impact_asset_types',
            'itemvirtualmachines_types',
            'management_types',
            'tools_types',
            'admin_types',
            'environment_types',
        ];
        $fails = [];
        foreach ($CFG_GLPI as $key => $value) {
            if (!in_array($key, $exclusions) && !str_contains($key, 'rule') && \Safe\preg_match('/.+_types$/', $key) !== 0) {
                if (count($this->callPrivateMethod($search_engine, 'getMetaParentItemtypesForTypesConfig', $key)) === 0) {
                    $fails[] = $key;
                }
            }
        }

        $this->assertCount(
            0,
            $fails,
            sprintf(
                "Missing/wrong itemtypes mapping in SearchEngine::getMetaParentItemtypesForTypesConfig():\n'%s'",
                implode('\', \'', $fails)
            )
        );

        $fails = [];
        foreach ($exclusions as $exclusion) {
            if (count($this->callPrivateMethod($search_engine, 'getMetaParentItemtypesForTypesConfig', $exclusion)) !== 0) {
                $fails[] = $exclusion;
            }
        }

        $this->assertCount(
            0,
            $fails,
            sprintf(
                "Existing itemtypes mapping that should not exists in SearchEngine::getMetaParentItemtypesForTypesConfig():\n'%s'",
                implode('\', \'', $fails)
            )
        );
    }
}
