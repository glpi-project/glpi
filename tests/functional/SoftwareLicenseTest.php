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

/* Test for inc/softwarelicense.class.php */

/**
 * @engine isolate
 */
class SoftwareLicenseTest extends DbTestCase
{
    public function testTypeName()
    {
        $this->assertSame('License', \SoftwareLicense::getTypeName(1));
        $this->assertSame('Licenses', \SoftwareLicense::getTypeName(0));
        $this->assertSame('Licenses', \SoftwareLicense::getTypeName(10));
    }

    public function testPrepareInputForAdd()
    {
        $license = new \SoftwareLicense();

        //Without softwares_id, accepted (since GLPI 11.0.0)
        $input = [
            'name' => 'not_inserted_software_license',
            'entities_id' => 0,
        ];
        $expected = [
            'name' => 'not_inserted_software_license',
            'entities_id' => 0,
            'softwarelicenses_id' => 0,
            'level' => 1,
            'completename' => 'not_inserted_software_license',
            'number' => 1,
        ];
        $this->assertSame($expected, $license->prepareInputForAdd($input));

        //With a softwares_id
        $input = ['name' => 'inserted_sofwarelicense', 'softwares_id' => 1];
        $license->input['softwares_id'] = 1;
        $expected = [
            'name' => 'inserted_sofwarelicense',
            'softwares_id' => 1,
            'softwarelicenses_id' => 0,
            'level' => 1,
            'completename' => 'inserted_sofwarelicense',
            'number' => 1,
        ];
        $this->assertSame($expected, $license->prepareInputForAdd($input));

        //withtemplate, empty 'expire' should be ignored. id will be replaced in _oldID
        $input = [
            'name' => 'other_inserted_sofwarelicense',
            'softwares_id' => 1,
            'id' => 1,
            'withtemplate' => 0,
            'expire' => '',
            'softwarelicenses_id' => 0,
        ];
        $expected = [
            'name' => 'other_inserted_sofwarelicense',
            'softwares_id' => 1,
            'softwarelicenses_id' => 0,
            'level' => 1,
            'completename' => 'other_inserted_sofwarelicense',
            '_oldID' => 1,
            'number' => 1,
        ];
        $this->assertSame($expected, $license->prepareInputForAdd($input));
    }

    /**
     * Creates a new software
     *
     * @return \Software
     */
    private function createSoft()
    {
        $softwares_id = $this->createItem(\Software::class, [
            'name' => 'Software ' . $this->getUniqueString(),
            'is_template' => 0,
            'entities_id' => 0,
        ])->getID();
        $software = new \Software();
        $this->assertTrue($software->getFromDB($softwares_id));

        return $software;
    }

    public function testAdd()
    {
        $this->login();

        $license = new \SoftwareLicense();
        $license_id = $this->createItem(\SoftwareLicense::class, [
            'name' => 'not_inserted_software_license_child',
            'entities_id' => 0,
        ])->getID();
        $this->assertGreaterThan(0, $license_id);

        $software = $this->createSoft();

        $parentlicense = new \SoftwareLicense();
        $parentlicense_id = $this->createItem(\SoftwareLicense::class, [
            'name' => 'a_software_license',
            'softwares_id' => $software->getID(),
            'entities_id' => 0,
        ])->getID();
        $this->assertGreaterThan(0, (int) $parentlicense_id);

        $this->assertTrue($parentlicense->getFromDB($parentlicense_id));

        $this->assertSame("a_software_license", $parentlicense->fields['completename']);
        $this->assertSame('a_software_license', $parentlicense->fields['name']);
        $this->assertNull($parentlicense->fields['expire']);
        $this->assertEquals(1, $parentlicense->fields['level']);

        $lic_id = $this->createItem(\SoftwareLicense::class, [
            'softwares_id' => $software->getID(),
            'expire' => '2017-01-01',
            'name' => 'a_child_license',
            'softwarelicenses_id' => $parentlicense_id,
            'entities_id' => $parentlicense->fields['entities_id'],
        ])->getID();
        $this->assertGreaterThan($parentlicense_id, $lic_id);
        $this->assertTrue($license->getFromDB($lic_id));

        $this->assertSame("a_software_license > a_child_license", $license->fields['completename']);
        $this->assertSame('a_child_license', $license->fields['name']);
        $this->assertSame('2017-01-01', $license->fields['expire']);
        $this->assertEquals(2, $license->fields['level']);
    }

    public function testComputeValidityIndicator()
    {
        $this->login();

        $license = new \SoftwareLicense();
        $software = $this->createSoft();

        $lic_id = $this->createItem(\SoftwareLicense::class, [
            'softwares_id' => $software->getID(),
            'expire' => '2017-01-01',
            'name' => 'Test licence ' . $this->getUniqueString(),
            'number' => 3,
            'entities_id' => 0,
        ])->getID();
        $this->assertGreaterThan(0, (int) $lic_id);
        $this->assertTrue($license->getFromDB($lic_id));

        $comp1 = getItemByTypeName('Computer', '_test_pc01');
        $comp2 = getItemByTypeName('Computer', '_test_pc02');

        $item_license_id = $this->createItem(\Item_SoftwareLicense::class, [
            'softwarelicenses_id' => $lic_id,
            'items_id' => $comp1->getID(),
            'itemtype' => 'Computer',
            'is_deleted' => 0,
            'is_dynamic' => 0,
        ])->getID();
        $this->assertGreaterThan(0, (int) $item_license_id);

        $this->assertEquals(1, \SoftwareLicense::computeValidityIndicator($lic_id, -1));
        $this->assertEquals(0, \SoftwareLicense::computeValidityIndicator($lic_id, 0));

        $item_license_id2 = $this->createItem(\Item_SoftwareLicense::class, [
            'softwarelicenses_id' => $lic_id,
            'items_id' => $comp2->getID(),
            'itemtype' => 'Computer',
            'is_deleted' => 0,
            'is_dynamic' => 0,
        ])->getID();
        $this->assertGreaterThan(0, (int) $item_license_id2);

        $this->assertEquals(1, \SoftwareLicense::computeValidityIndicator($lic_id, 2));
        $this->assertEquals(0, \SoftwareLicense::computeValidityIndicator($lic_id, 1));
    }

    public function testPrepareInputForUpdate()
    {
        $this->login();

        $license = new \SoftwareLicense();
        $software = $this->createSoft();

        $lic_id = $this->createItem(\SoftwareLicense::class, [
            'softwares_id' => $software->getID(),
            'expire' => '2017-01-01',
            'name' => 'Test licence ' . $this->getUniqueString(),
            'number' => 3,
            'entities_id' => 0,
        ])->getID();
        $this->assertGreaterThan(0, (int) $lic_id);

        // Make sure to load the license
        $this->assertTrue($license->getFromDB($lic_id));

        $input = ['id' => $lic_id, 'number' => 3];
        $expected = ['id' => $lic_id, 'number' => 3, 'is_valid' => 1];
        $this->assertSame($expected, $license->prepareInputForUpdate($input));
    }

    public function testUpdateValidityIndicator()
    {
        $this->login();

        $license = new \SoftwareLicense();
        $comp1 = getItemByTypeName('Computer', '_test_pc01');

        $software = $this->createSoft();
        $lic_id = $this->createItem(\SoftwareLicense::class, [
            'softwares_id' => $software->getID(),
            'expire' => '2017-01-01',
            'name' => 'Test licence ' . $this->getUniqueString(),
            'number' => 3,
            'entities_id' => 0,
        ])->getID();
        $this->assertGreaterThan(0, (int) $lic_id);
        $this->assertTrue($license->getFromDB($lic_id));

        $this->createLicenseInstall(
            $license,
            ['_test_pc01', '_test_pc02', '_test_pc22']
        );

        // Make sure comp1 is defined before use
        $comp1 = getItemByTypeName('Computer', '_test_pc01');

        $license_computer = new \Item_SoftwareLicense();
        $input = [
            'softwarelicenses_id' => $license->getID(),
            'items_id' => $comp1->getID(),
            'itemtype' => 'Computer',
        ];
        $this->assertTrue($license_computer->deleteByCriteria($input, true));

        $orig_number = $license->getField('number');
        $this->updateItem(\SoftwareLicense::class, $license->getID(), [
            'number' => 1,
        ]);
        $this->assertTrue($license->getFromDB($license->getID()));

        $this->assertGreaterThan(0, (int) $license->getID());
        $this->assertEquals(1, $license->fields['number']);

        $license->updateValidityIndicator($license->getID());
        $this->assertEquals(0, $license->fields['is_valid']);

        $this->updateItem(\SoftwareLicense::class, $license->getID(), [
            'number' => $orig_number,
        ]);

        $license->updateValidityIndicator($license->fields['id']);
        $license->getFromDB($license->getID());
        $this->assertEquals(1, $license->fields['is_valid']);
    }

    private function createLicenseInstall(\SoftwareLicense $license, $computers)
    {
        foreach ($computers as $computer) {
            $comp = getItemByTypeName('Computer', $computer);
            $this->createInstall($license->getID(), $comp->getID());
        }
    }

    private function createInstall($licenses_id, $items_id)
    {
        $this->createItem(
            \Item_SoftwareLicense::class,
            [
                'softwarelicenses_id' => $licenses_id,
                'items_id' => $items_id,
                'itemtype' => 'Computer',
                'is_dynamic' => 0,
                'is_deleted' => 0,
            ]
        );
    }

    public function testRawSearchOptionsCountsUsers()
    {
        $this->login();

        $license = new \SoftwareLicense();
        $search_options = $license->rawSearchOptions();

        $found = false;
        foreach ($search_options as $option) {
            if (isset($option['id']) && $option['id'] == '163') {
                $found = true;

                $this->assertArrayHasKey('computation', $option);
                $computation = $option['computation'];

                $this->assertStringContainsString(\Item_SoftwareLicense::getTable(), $computation);
                $this->assertStringContainsString(\SoftwareLicense_User::getTable(), $computation);

                $this->assertStringContainsString('+', $computation);

                $this->assertEquals('count', $option['computationtype']);
                break;
            }
        }

        $this->assertTrue($found, 'Search option 163 (Number of installations) not found');
    }

    public function testConsistentInstallationCounting()
    {
        $this->login();

        // Create a software
        $software_id = $this->createItem(\Software::class, [
            'name' => 'Test software for counting consistency',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ])->getID();

        // Create a license
        $license_id = $this->createItem(\SoftwareLicense::class, [
            'name' => 'Test license for counting consistency',
            'softwares_id' => $software_id,
            'number' => 5,
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ])->getID();

        $item_license_table = new \Item_SoftwareLicense();
        $item_license_table->deleteByCriteria(['softwarelicenses_id' => $license_id], true);
        $user_license_table = new \SoftwareLicense_User();
        $user_license_table->deleteByCriteria(['softwarelicenses_id' => $license_id]);

        $license = new \SoftwareLicense();
        $this->assertTrue($license->getFromDB($license_id));

        $initial_items = \Item_SoftwareLicense::countForLicense($license_id);
        $initial_users = \SoftwareLicense_User::countForLicense($license_id);
        $initial_total = $initial_items + $initial_users;

        $computer = getItemByTypeName('Computer', '_test_pc01');
        $item_license_id = $this->createItem(\Item_SoftwareLicense::class, [
            'softwarelicenses_id' => $license_id,
            'items_id' => $computer->getID(),
            'itemtype' => 'Computer',
        ])->getID();

        $items_after_computer = \Item_SoftwareLicense::countForLicense($license_id);
        $users_after_computer = \SoftwareLicense_User::countForLicense($license_id);
        $total_after_computer = $items_after_computer + $users_after_computer;

        $this->assertEquals($initial_items + 1, $items_after_computer);
        $this->assertEquals($initial_users, $users_after_computer);
        $this->assertEquals($initial_total + 1, $total_after_computer);

        $user = getItemByTypeName('User', TU_USER);
        $user_license_id = $this->createItem(\SoftwareLicense_User::class, [
            'softwarelicenses_id' => $license_id,
            'users_id' => $user->getID(),
        ])->getID();

        $items_after_user = \Item_SoftwareLicense::countForLicense($license_id);
        $users_after_user = \SoftwareLicense_User::countForLicense($license_id);
        $total_after_user = $items_after_user + $users_after_user;

        $this->assertEquals($items_after_computer, $items_after_user);
        $this->assertEquals($users_after_computer + 1, $users_after_user);
        $this->assertEquals($total_after_computer + 1, $total_after_user);

        // Initialise the user_ids array before using it
        $user_ids = [];

        for ($i = 1; $i <= 3; $i++) {
            // Create a user and assign it to the license
            $user_id = $this->createItem(
                \User::class,
                [
                    'name' => 'test_license_user_' . $i,
                    'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
                ]
            )->getID();
            $user_ids[] = $user_id;

            $this->createItem(\SoftwareLicense_User::class, [
                'softwarelicenses_id' => $license_id,
                'users_id' => $user_id,
            ]);
        }

        $total_count = \Item_SoftwareLicense::countForLicense($license_id)
            + \SoftwareLicense_User::countForLicense($license_id);
        $this->assertEquals($initial_total + 5, $total_count);

        $this->assertTrue($license->getFromDB($license_id));
        $this->assertEquals(1, $license->fields['is_valid']);

        $user_id = $this->createItem(
            \User::class,
            [
                'name' => 'test_license_user_exceed',
                'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            ]
        )->getID();

        $user_ids[] = $user_id;

        $this->createItem(\SoftwareLicense_User::class, [
            'softwarelicenses_id' => $license_id,
            'users_id' => $user_id,
        ]);

        $total_count = \Item_SoftwareLicense::countForLicense($license_id)
            + \SoftwareLicense_User::countForLicense($license_id);
        $this->assertEquals($initial_total + 6, $total_count);

        // Define software for deletion at the end
        $software = new \Software();
        $this->assertTrue($software->getFromDB($software_id));

        $user_license_table->deleteByCriteria(['softwarelicenses_id' => $license_id]);
        $item_license_table->delete(['id' => $item_license_id]);
        $license->delete(['id' => $license_id]);
        $software->delete(['id' => $software_id]);

        foreach ($user_ids as $id) {
            $u = new \User();
            $u->delete(['id' => $id], true);
        }
    }

    /**
     * Test the updated getSpecificMassiveActions() method to verify
     * it correctly handles quota limits
     */
    public function testGetSpecificMassiveActionsWithQuotaLimits()
    {
        $this->login();

        // Create a software
        $software_id = $this->createItem(\Software::class, [
            'name' => 'Test software for quota limits',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ])->getID();

        // Create a license with limited number and NO overquota allowed
        $license_id = $this->createItem(\SoftwareLicense::class, [
            'name' => 'Test license with strict quota',
            'softwares_id' => $software_id,
            'number' => 1, // Limit to 1 installation
            'allow_overquota' => 0, // No overquota
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ])->getID();

        $license = new \SoftwareLicense();
        $this->assertTrue($license->getFromDB($license_id));

        // Check that action is initially available (added by getSpecificMassiveActions)
        $specific_actions = $license->getSpecificMassiveActions();
        $action_key = 'Item_SoftwareLicense' . \MassiveAction::CLASS_ACTION_SEPARATOR . 'add_item';
        $this->assertTrue(
            $this->actionExists($specific_actions, $action_key),
            "Add an item action should be added by getSpecificMassiveActions"
        );

        // Check that action is not forbidden when license is under quota
        $forbidden_actions = $license->getForbiddenSingleMassiveActions();
        $this->assertFalse(
            in_array($action_key, $forbidden_actions),
            "Add an item action should not be forbidden when under quota"
        );

        // Add a computer to this license to reach the limit
        $computer = getItemByTypeName('Computer', '_test_pc01');
        $item_license_id = $this->createItem(\Item_SoftwareLicense::class, [
            'softwarelicenses_id' => $license_id,
            'items_id' => $computer->getID(),
            'itemtype' => 'Computer',
        ])->getID();

        // Check that action is NOW forbidden when at quota limit
        $license->getFromDB($license_id); // Reload to get current state
        $forbidden_actions = $license->getForbiddenSingleMassiveActions();
        $this->assertTrue(
            in_array($action_key, $forbidden_actions),
            "Add an item action should be forbidden when at quota limit"
        );

        // Create a license WITH overquota allowed
        $license2_id = $this->createItem(\SoftwareLicense::class, [
            'name' => 'Test license with overquota',
            'softwares_id' => $software_id,
            'number' => 1, // Limit to 1 installation
            'allow_overquota' => 1, // Allow overquota
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ])->getID();

        $license2 = new \SoftwareLicense();
        $this->assertTrue($license2->getFromDB($license2_id));

        // Add a computer to this license to reach the limit
        $item_license2_id = $this->createItem(\Item_SoftwareLicense::class, [
            'softwarelicenses_id' => $license2_id,
            'items_id' => $computer->getID(),
            'itemtype' => 'Computer',
        ])->getID();

        // Check that action is NOT forbidden even when at quota limit but overquota is allowed
        $forbidden_actions = $license2->getForbiddenSingleMassiveActions();
        $this->assertFalse(
            in_array($action_key, $forbidden_actions),
            "Add an item action should not be forbidden when overquota is allowed"
        );

        // Create a license with unlimited installations
        $license3_id = $this->createItem(\SoftwareLicense::class, [
            'name' => 'Test license with unlimited installations',
            'softwares_id' => $software_id,
            'number' => -1, // Unlimited installations
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ])->getID();

        $license3 = new \SoftwareLicense();
        $this->assertTrue($license3->getFromDB($license3_id));

        // Add multiple computers to this license
        for ($i = 1; $i <= 3; $i++) {
            $computer = getItemByTypeName('Computer', '_test_pc0' . $i);
            $this->createItem(\Item_SoftwareLicense::class, [
                'softwarelicenses_id' => $license3_id,
                'items_id' => $computer->getID(),
                'itemtype' => 'Computer',
            ]);
        }

        // Check that action is NOT forbidden for unlimited licenses
        $forbidden_actions = $license3->getForbiddenSingleMassiveActions();
        $this->assertFalse(
            in_array($action_key, $forbidden_actions),
            "Add an item action should not be forbidden for unlimited licenses"
        );
    }

    /**
     * Helper method to check if an action exists in the actions array
     */
    private function actionExists($actions, $action_key)
    {
        return array_key_exists($action_key, $actions);
    }
}
