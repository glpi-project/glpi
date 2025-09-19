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

namespace tests\units;

use InventoryTestCase;

class RuleImportAssetCollectionTest extends InventoryTestCase
{
    public function testPrepareInputDataForProcess()
    {
        global $DB;
        $json = json_decode(file_get_contents(self::INV_FIXTURES . 'computer_1.json'));
        unset($json->content->bios);
        unset($json->content->hardware->name);
        $this->doInventory($json);

        // check for refused equipment
        $iterator = $DB->request([
            'FROM'   => \RefusedEquipment::getTable(),
        ]);
        $this->assertCount(1, $iterator);
        $result = $iterator->current();

        $rule = new \RuleDefineItemtypeCollection();
        $input = ['itemtype' => \Computer::class, 'name' => 'name', 'test' => 'test'];
        $result = $rule->prepareInputDataForProcess($input, ['refusedequipments_id' => $result['id']]);

        unset($result['last_inventory_update']);

        $expected = array_merge($input, [
            '_auto'                  => 1,
            'tag'                    => '000005',
            'deviceid'               => 'glpixps-2018-07-09-09-07-13',
            'autoupdatesystems_id'   => 'GLPI Native Inventory',
            'last_boot'              => '2020-06-09 07:58:08',
            'chassis_type'           => 'Laptop',
            'datelastloggeduser'     => 'Fri Jun 12 14:15',
            'defaultgateway'         => '192.168.1.1',
            'dns'                    => '192.168.1.1',
            'lastloggeduser'         => 'root',
            'memory'                 => 7800,
            'swap'                   => 7951,
            'uuid'                   => '4c4c4544-0034-3010-8048-b6c04f503732',
            'vmsystem'               => 'Physical',
            'computertypes_id'       => 'Laptop',
            'contact'                => 'trasher/root',
            '_inventory_users'       => ['trasher', 'root'],
            'entities_id'            => 0,
        ]);

        // Check that refused equipment data have been returned by prepareInputDataForProcess
        $this->assertSame($expected, $result);
    }
}
