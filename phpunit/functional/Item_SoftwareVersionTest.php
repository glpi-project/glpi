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
use Glpi\Asset\Capacity;
use Glpi\Asset\Capacity\HasSoftwaresCapacity;
use Glpi\Features\Clonable;
use Item_SoftwareVersion;
use Toolbox;

/**
 * @engine isolate
 */
class Item_SoftwareVersionTest extends DbTestCase
{
    public function testRelatedItemHasTab()
    {
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: HasSoftwaresCapacity::class)]);

        $this->login(); // tab will be available only if corresponding right is available in the current session

        foreach ($CFG_GLPI['software_types'] as $itemtype) {
            $item = $this->createItem(
                $itemtype,
                $this->getMinimalCreationInput($itemtype)
            );

            $tabs = $item->defineAllTabs();
            $this->assertArrayHasKey('Item_SoftwareVersion$1', $tabs, $itemtype);
        }
    }

    public function testRelatedItemCloneRelations()
    {
        global $CFG_GLPI;

        $this->initAssetDefinition(capacities: [new Capacity(name: HasSoftwaresCapacity::class)]);

        foreach ($CFG_GLPI['software_types'] as $itemtype) {
            if (!Toolbox::hasTrait($itemtype, Clonable::class)) {
                continue;
            }

            $item = \getItemForItemtype($itemtype);
            $this->assertContains(Item_SoftwareVersion::class, $item->getCloneRelations(), $itemtype);
        }
    }

    public function testTypeName()
    {
        $this->assertSame('Installation', Item_SoftwareVersion::getTypeName(1));
        $this->assertSame('Installations', Item_SoftwareVersion::getTypeName(0));
        $this->assertSame('Installations', Item_SoftwareVersion::getTypeName(10));
    }

    public function testPrepareInputForAdd()
    {
        $this->login();

        $computer1 = getItemByTypeName('Computer', '_test_pc01');
        $ver = getItemByTypeName('SoftwareVersion', '_test_softver_1', true);

        // Do some installations
        $ins = new Item_SoftwareVersion();
        $this->assertGreaterThan(
            0,
            $ins->add([
                'items_id'              => $computer1->getID(),
                'itemtype'              => 'Computer',
                'softwareversions_id'   => $ver,
            ])
        );

        $input = [
            'items_id'  => $computer1->getID(),
            'itemtype'  => 'Computer',
            'name'      => 'A name',
        ];

        $expected = [
            'items_id'              => $computer1->getID(),
            'itemtype'              => 'Computer',
            'name'                  => 'A name',
            'is_template_item'      => $computer1->getField('is_template'),
            'is_deleted_item'       => $computer1->getField('is_deleted'),
            'entities_id'           => getItemByTypeName('Entity', '_test_root_entity', true),
            'is_recursive'          => 0,
        ];

        $this->setEntity('_test_root_entity', true);
        $this->assertSame($expected, $ins->prepareInputForAdd($input));
    }

    public function testPrepareInputForUpdate()
    {
        $this->login();

        $computer1 = getItemByTypeName('Computer', '_test_pc01');
        $ver = getItemByTypeName('SoftwareVersion', '_test_softver_1', true);

        // Do some installations
        $ins = new Item_SoftwareVersion();
        $this->assertGreaterThan(
            0,
            $ins->add([
                'items_id'              => $computer1->getID(),
                'itemtype'              => 'Computer',
                'softwareversions_id'   => $ver,
            ])
        );

        $input = [
            'items_id'              => $computer1->getID(),
            'itemtype'              => 'Computer',
            'name'                  => 'Another name',
        ];

        $expected = [
            'items_id'              => $computer1->getID(),
            'itemtype'              => 'Computer',
            'name'                  => 'Another name',
            'is_template_item'      => $computer1->getField('is_template'),
            'is_deleted_item'       => $computer1->getField('is_deleted'),
        ];

        $this->assertSame($expected, $ins->prepareInputForUpdate($input));
    }


    public function testCountInstall()
    {
        $this->login();

        $computer1 = getItemByTypeName('Computer', '_test_pc01', true);
        $computer11 = getItemByTypeName('Computer', '_test_pc11', true);
        $computer12 = getItemByTypeName('Computer', '_test_pc12', true);
        $ver = getItemByTypeName('SoftwareVersion', '_test_softver_1', true);

        // Do some installations
        $ins = new Item_SoftwareVersion();
        $this->assertGreaterThan(
            0,
            $ins->add([
                'items_id'              => $computer1,
                'itemtype'              => 'Computer',
                'softwareversions_id'   => $ver,
            ])
        );
        $this->assertGreaterThan(
            0,
            $ins->add([
                'items_id'              => $computer11,
                'itemtype'              => 'Computer',
                'softwareversions_id'   => $ver,
            ])
        );
        $this->assertGreaterThan(
            0,
            $ins->add([
                'items_id'              => $computer12,
                'itemtype'              => 'Computer',
                'softwareversions_id'   => $ver,
            ])
        );

        // Count installations
        $this->setEntity('_test_root_entity', true);
        $this->assertSame(3, Item_SoftwareVersion::countForVersion($ver), 'count in all tree');

        $this->setEntity('_test_root_entity', false);
        $this->assertSame(1, Item_SoftwareVersion::countForVersion($ver), 'count in root');

        $this->setEntity('_test_child_1', false);
        $this->assertSame(2, Item_SoftwareVersion::countForVersion($ver), 'count in child');
    }

    public function testUpdateDatasFromComputer()
    {
        $c00 = 1566671;
        $computer1 = getItemByTypeName('Computer', '_test_pc01');
        $ver1 = getItemByTypeName('SoftwareVersion', '_test_softver_1', true);
        $ver2 = getItemByTypeName('SoftwareVersion', '_test_softver_2', true);

        // Do some installations
        $softver = new Item_SoftwareVersion();
        $softver01 = $softver->add([
            'items_id'              => $computer1->getID(),
            'itemtype'              => 'Computer',
            'softwareversions_id'   => $ver1,
        ]);
        $this->assertGreaterThan(0, (int) $softver01);
        $softver02 = $softver->add([
            'items_id'              => $computer1->getID(),
            'itemtype'              => 'Computer',
            'softwareversions_id'   => $ver2,
        ]);
        $this->assertGreaterThan(0, (int) $softver02);

        foreach ([$softver01, $softver02] as $tsoftver) {
            $o = new Item_SoftwareVersion();
            $this->assertTrue($o->getFromDb($tsoftver));
            $this->assertEquals(0, $o->getField('is_deleted_item'));
        }

        //computer that does not exist
        $this->assertFalse($softver->updateDatasForItem('Computer', $c00));

        //update existing computer
        $input = $computer1->fields;
        $input['is_deleted'] = '1';
        $this->assertTrue($computer1->update($input));

        $this->assertTrue($softver->updateDatasForItem('Computer', $computer1->getID()));

        //check if all has been updated
        foreach ([$softver01, $softver02] as $tsoftver) {
            $o = new Item_SoftwareVersion();
            $this->assertTrue($o->getFromDb($tsoftver));
            $this->assertEquals(1, $o->getField('is_deleted_item'));
        }

        //restore computer state
        $input['is_deleted'] = '0';
        $this->assertTrue($computer1->update($input));
    }

    public function testCountForSoftware()
    {
        $soft1 = getItemByTypeName('Software', '_test_soft');
        $computer1 = getItemByTypeName('Computer', '_test_pc01');

        $this->Login();

        $this->assertSame(
            0,
            Item_SoftwareVersion::countForSoftware($soft1->fields['id'])
        );

        $csoftver = new Item_SoftwareVersion();
        $this->assertGreaterThan(
            0,
            $csoftver->add([
                'items_id'              => $computer1->fields['id'],
                'itemtype'              => 'Computer',
                'softwareversions_id'   => $soft1->fields['id'],
            ])
        );

        $this->assertSame(
            1,
            Item_SoftwareVersion::countForSoftware($soft1->fields['id'])
        );
    }
}
