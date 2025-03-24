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

use DbTestCase;
use Glpi\Asset\Capacity\HasSoftwaresCapacity;
use Glpi\Features\Clonable;
use Item_SoftwareLicense;
use Toolbox;

class Item_SoftwareLicenseTest extends DbTestCase
{
    public function testRelatedItemCloneRelations()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [HasSoftwaresCapacity::class]);

        foreach ($CFG_GLPI['software_types'] as $itemtype) {
            if (!Toolbox::hasTrait($itemtype, Clonable::class)) {
                continue;
            }

            $item = \getItemForItemtype($itemtype);
            $this->assertContains(Item_SoftwareLicense::class, $item->getCloneRelations(), $itemtype);
        }
    }

    public function testCountForLicense()
    {
        $this->login();

        // Check new functionality
        $lic = getItemByTypeName('SoftwareLicense', '_test_softlic_1');
        $this->assertSame(3, \Item_SoftwareLicense::countForLicense($lic->fields['id']));

        $lic = getItemByTypeName('SoftwareLicense', '_test_softlic_2');
        $this->assertSame(2, \Item_SoftwareLicense::countForLicense($lic->fields['id']));

        $lic = getItemByTypeName('SoftwareLicense', '_test_softlic_3');
        $this->assertSame(2, \Item_SoftwareLicense::countForLicense($lic->fields['id']));

        $lic = getItemByTypeName('SoftwareLicense', '_test_softlic_4');
        $this->assertSame(0, \Item_SoftwareLicense::countForLicense($lic->fields['id']));
    }

    public function testCountForSoftware()
    {
        $this->login();

        //Check new functionality
        $soft = getItemByTypeName('Software', '_test_soft');
        $this->assertSame(7, \Item_SoftwareLicense::countForSoftware($soft->fields['id']));

        $soft = getItemByTypeName('Software', '_test_soft2');
        $this->assertSame(0, \Item_SoftwareLicense::countForSoftware($soft->fields['id']));
    }

    public function testGetLicenseForInstallation()
    {
        $computer1 = getItemByTypeName('Computer', '_test_pc01');
        $version1 = getItemByTypeName('SoftwareVersion', '_test_softver_1');

        $this->Login();

        $this->assertEmpty(
            \Item_SoftwareLicense::getLicenseForInstallation(
                'Computer',
                $computer1->fields['id'],
                $version1->fields['id']
            )
        );

        //simulate license install
        $lic = getItemByTypeName('SoftwareLicense', '_test_softlic_1');
        $this->assertTrue(
            $lic->update([
                'id'                       => $lic->fields['id'],
                'softwareversions_id_use'  => $version1->fields['id']
            ])
        );

        $this->assertCount(
            1,
            \Item_SoftwareLicense::getLicenseForInstallation(
                'Computer',
                $computer1->fields['id'],
                $version1->fields['id']
            )
        );

        //reset license
        $this->assertTrue(
            $lic->update([
                'id'                       => $lic->fields['id'],
                'softwareversions_id_use'  => 0
            ])
        );
    }

    public function testAddUpdateDelete()
    {
        $computer1 = getItemByTypeName('Computer', '_test_pc01');
        $computer2 = getItemByTypeName('Computer', '_test_pc02');
        $computer3 = getItemByTypeName('Computer', '_test_pc11');
        $lic       = getItemByTypeName('SoftwareLicense', '_test_softlic_4');

       // Do some installations
        $lic_computer = new \Item_SoftwareLicense();

        $input = [
            'items_id'              => $computer1->fields['id'],
            'itemtype'              => 'Computer',
            'softwarelicenses_id'   => $lic->fields['id'],
        ];
        $this->assertGreaterThan(0, (int)$lic_computer->add($input));

        $input = [
            'items_id'              => $computer2->fields['id'],
            'itemtype'              => 'Computer',
            'softwarelicenses_id'   => $lic->fields['id'],
        ];
        $this->assertGreaterThan(0, (int)$lic_computer->add($input));

        $lic = getItemByTypeName('SoftwareLicense', '_test_softlic_4');
        //License is valid: the number of affectations doesn't exceed declared number
        $this->assertEquals(1, $lic->fields['is_valid']);

        $input = [
            'items_id'              => $computer3->fields['id'],
            'itemtype'              => 'Computer',
            'softwarelicenses_id'   => $lic->fields['id']
        ];
        $this->assertGreaterThan(0, (int)$lic_computer->add($input));

        $lic = getItemByTypeName('SoftwareLicense', '_test_softlic_4');
        //Number of affectations exceed the number declared in the license
        $this->assertEquals(0, $lic->fields['is_valid']);

        //test upgrade
        $old_lic      = getItemByTypeName('SoftwareLicense', '_test_softlic_4');
        $new_lic      = getItemByTypeName('SoftwareLicense', '_test_softlic_3');

        $lic_computer = new \Item_SoftwareLicense();
        $computer     = getItemByTypeName('Computer', '_test_pc01');
        $result = $lic_computer->find([
            'items_id'              => $computer->fields['id'],
            'itemtype'              => 'Computer',
            'softwarelicenses_id'   => $old_lic->fields['id']
        ]);
        $this->assertTrue($lic_computer->getFromDB(array_keys($result)[0]));

        $lic_computer->upgrade($lic_computer->getID(), $new_lic->fields['id']);

        $this->assertNotEquals($old_lic->getID(), $lic_computer->fields['softwarelicenses_id']);
        $this->assertEquals($new_lic->getID(), $lic_computer->fields['softwarelicenses_id']);

        //test delete
        $lic_computer = new \Item_SoftwareLicense();
        $this->assertTrue($lic_computer->deleteByCriteria(['softwarelicenses_id' => $lic->fields['id']], true));

        $lic = getItemByTypeName('SoftwareLicense', '_test_softlic_4');
        //Number of installations shouldn't now exceed the number declared in the license
        $this->assertEquals(1, $lic->fields['is_valid']);
    }


    public function testCloneItem()
    {
        $this->login();

        $source_computer = getItemByTypeName('Computer', '_test_pc21');
        $target_computer = getItemByTypeName('Computer', '_test_pc22');

        $item_softwareLicenses = \Item_SoftwareLicense::getItemsAssociatedTo($source_computer->getType(), $source_computer->getID());
        $override_input['items_id'] = $target_computer->getID();
        foreach ($item_softwareLicenses as $item_softwareLicense) {
            $item_softwareLicense->clone($override_input);
        }

        $input = [
            'items_id'  => $source_computer->fields['id'],
            'itemtype'  => 'Computer'
        ];
        $this->assertSame(3, countElementsInTable('glpi_items_softwarelicenses', $input));

        $input = [
            'items_id' => $target_computer->fields['id'],
            'itemtype'  => 'Computer'
        ];
        $this->assertSame(3, countElementsInTable('glpi_items_softwarelicenses', $input));

        //cleanup
        $lic_computer = new \Item_SoftwareLicense();
        $lic_computer->deleteByCriteria([
            'items_id' => $target_computer->fields['id'],
            'itemtype'  => 'Computer'
        ], true);
    }

    public function testGetTabNameForItem()
    {
        $this->login();

        $license      = getItemByTypeName('SoftwareLicense', '_test_softlic_2');
        $cSoftwareLicense = new \Item_SoftwareLicense();
        $this->assertEmpty($cSoftwareLicense->getTabNameForItem(new \Computer(), 0));
        $this->assertEmpty($cSoftwareLicense->getTabNameForItem($license, 1));

        $_SESSION['glpishow_count_on_tabs'] = 0;
        $expected = [
            1 => "Summary",
            2 => "Affected items",
        ];
        $tabs = array_map(
            'strip_tags',
            $cSoftwareLicense->getTabNameForItem($license, 0)
        );
        $this->assertSame($expected, $tabs);

        $_SESSION['glpishow_count_on_tabs'] = 1;
        $expected = [
            1 => "Summary",
            2 => "Affected items 2"
        ];
        $tabs = array_map(
            'strip_tags',
            $cSoftwareLicense->getTabNameForItem($license, 0)
        );
        $this->assertSame($expected, $tabs);
    }

    public function testCountLicenses()
    {
        $this->login();

        $software = getItemByTypeName('Software', '_test_soft');
        $this->assertSame(5, \Item_SoftwareLicense::countLicenses($software->getID()));

        $software = getItemByTypeName('Software', '_test_soft2');
        $this->assertSame(0, \Item_SoftwareLicense::countLicenses($software->getID()));
    }

    public function testGetSearchOptionsNew()
    {
        $this->login();

        $cSoftwareLicense = new \Item_SoftwareLicense();
        $this->assertCount(5, $cSoftwareLicense->rawSearchOptions());
    }
}
