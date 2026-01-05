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

namespace tests\units;

use DbTestCase;

/* Test for inc/item_softwareversion.class.php */

/**
 * @engine isolate
 */
class Item_SoftwareVersionTest extends DbTestCase
{
    public function testTypeName()
    {
        $this->assertSame('Installation', \Item_SoftwareVersion::getTypeName(1));
        $this->assertSame('Installations', \Item_SoftwareVersion::getTypeName(0));
        $this->assertSame('Installations', \Item_SoftwareVersion::getTypeName(10));
    }

    public function testPrepareInputForAdd()
    {
        $this->login();

        $computer1 = getItemByTypeName('Computer', '_test_pc01');
        $ver = getItemByTypeName('SoftwareVersion', '_test_softver_1', true);

        // Do some installations
        $ins = new \Item_SoftwareVersion();
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
            'entities_id'           => 1,
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
        $ins = new \Item_SoftwareVersion();
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
        $ins = new \Item_SoftwareVersion();
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
        $this->assertSame(3, \Item_SoftwareVersion::countForVersion($ver), 'count in all tree');

        $this->setEntity('_test_root_entity', false);
        $this->assertSame(1, \Item_SoftwareVersion::countForVersion($ver), 'count in root');

        $this->setEntity('_test_child_1', false);
        $this->assertSame(2, \Item_SoftwareVersion::countForVersion($ver), 'count in child');
    }

    public function testUpdateDatasFromComputer()
    {
        $c00 = 1566671;
        $computer1 = getItemByTypeName('Computer', '_test_pc01');
        $ver1 = getItemByTypeName('SoftwareVersion', '_test_softver_1', true);
        $ver2 = getItemByTypeName('SoftwareVersion', '_test_softver_2', true);

        // Do some installations
        $softver = new \Item_SoftwareVersion();
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
            $o = new \Item_SoftwareVersion();
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
            $o = new \Item_SoftwareVersion();
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
            \Item_SoftwareVersion::countForSoftware($soft1->fields['id'])
        );

        $csoftver = new \Item_SoftwareVersion();
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
            \Item_SoftwareVersion::countForSoftware($soft1->fields['id'])
        );
    }

    public function testCanCreateRightsConsistency()
    {
        // Test that rights check requires proper rights on both linked items
        // User needs UPDATE on software OR computer, AND VIEW on the other
        $this->login();

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $ver = getItemByTypeName('SoftwareVersion', '_test_softver_1', true);

        $inst = new \Item_SoftwareVersion();
        $input = [
            'items_id'            => $computer->getID(),
            'itemtype'            => 'Computer',
            'softwareversions_id' => $ver,
        ];

        // With full rights (glpi user), can() should return true
        $this->assertTrue($inst->can(-1, CREATE, $input));

        // Save current profile rights
        $original_software = $_SESSION['glpiactiveprofile']['software'] ?? 0;
        $original_computer = $_SESSION['glpiactiveprofile']['computer'] ?? 0;

        // Test case 1: Software UPDATE + Computer READ = should work
        $_SESSION['glpiactiveprofile']['software'] = READ | UPDATE;
        $_SESSION['glpiactiveprofile']['computer'] = READ;
        $inst1 = new \Item_SoftwareVersion();
        $this->assertTrue($inst1->can(-1, CREATE, $input), 'Software UPDATE + Computer READ should allow creation');

        // Test case 2: Computer UPDATE + Software READ = should work
        $_SESSION['glpiactiveprofile']['software'] = READ;
        $_SESSION['glpiactiveprofile']['computer'] = READ | UPDATE;
        $inst2 = new \Item_SoftwareVersion();
        $this->assertTrue($inst2->can(-1, CREATE, $input), 'Computer UPDATE + Software READ should allow creation');

        // Test case 3: Software UPDATE only (no computer rights) = should fail
        $_SESSION['glpiactiveprofile']['software'] = READ | UPDATE;
        $_SESSION['glpiactiveprofile']['computer'] = 0;
        $inst3 = new \Item_SoftwareVersion();
        $this->assertFalse($inst3->can(-1, CREATE, $input), 'Software UPDATE without Computer READ should deny creation');

        // Test case 4: Computer UPDATE only (no software rights) = should fail
        $_SESSION['glpiactiveprofile']['software'] = 0;
        $_SESSION['glpiactiveprofile']['computer'] = READ | UPDATE;
        $inst4 = new \Item_SoftwareVersion();
        $this->assertFalse($inst4->can(-1, CREATE, $input), 'Computer UPDATE without Software READ should deny creation');

        // Restore original rights
        $_SESSION['glpiactiveprofile']['software'] = $original_software;
        $_SESSION['glpiactiveprofile']['computer'] = $original_computer;
    }
}
