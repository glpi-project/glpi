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
use Glpi\Asset\AssetDefinition;
use Glpi\DBAL\QueryExpression;
use PHPUnit\Framework\Attributes\DataProvider;

/* Test for inc/profile.class.php */

class ProfileTest extends DbTestCase
{
    /**
     * @see self::testHaveUserRight()
     *
     * @return array
     */
    public static function haveUserRightProvider()
    {

        return [
            [
                'user'     => [
                    'login'    => 'post-only',
                    'password' => 'postonly',
                ],
                'rightset' => [
                    ['name' => \Computer::$rightname, 'value' => CREATE, 'expected' => false],
                    ['name' => \Computer::$rightname, 'value' => DELETE, 'expected' => false],
                    ['name' => \Ticket::$rightname, 'value' => CREATE, 'expected' => true],
                    ['name' => \Ticket::$rightname, 'value' => DELETE, 'expected' => false],
                    ['name' => \ITILFollowup::$rightname, 'value' => \ITILFollowup::ADDMY, 'expected' => true],
                    ['name' => \ITILFollowup::$rightname, 'value' => \ITILFollowup::ADDALLITEM, 'expected' => false],
                ],
            ],
            [
                'user'     => [
                    'login'    => 'glpi',
                    'password' => 'glpi',
                ],
                'rightset' => [
                    ['name' => \Computer::$rightname, 'value' => CREATE, 'expected' => true],
                    ['name' => \Computer::$rightname, 'value' => DELETE, 'expected' => true],
                    ['name' => \Ticket::$rightname, 'value' => CREATE, 'expected' => true],
                    ['name' => \Ticket::$rightname, 'value' => DELETE, 'expected' => true],
                    ['name' => \ITILFollowup::$rightname, 'value' => \ITILFollowup::ADDMY, 'expected' => true],
                    ['name' => \ITILFollowup::$rightname, 'value' => \ITILFollowup::ADDALLITEM, 'expected' => true],
                ],
            ],
            [
                'user'     => [
                    'login'    => 'tech',
                    'password' => 'tech',
                ],
                'rightset' => [
                    ['name' => \Computer::$rightname, 'value' => CREATE, 'expected' => true],
                    ['name' => \Computer::$rightname, 'value' => DELETE, 'expected' => true],
                    ['name' => \Ticket::$rightname, 'value' => CREATE, 'expected' => true],
                    ['name' => \Ticket::$rightname, 'value' => DELETE, 'expected' => false],
                    ['name' => \ITILFollowup::$rightname, 'value' => \ITILFollowup::ADDMY, 'expected' => true],
                    ['name' => \ITILFollowup::$rightname, 'value' => \ITILFollowup::ADDALLITEM, 'expected' => true],
                ],
            ],
        ];
    }

    /**
     * Tests user rights checking.
     *
     * @param array   $user     Array containing 'login' and 'password' fields of tested user.
     * @param array   $rightset Array of arrays containing 'name', 'value' and 'expected' result of a right.
     */
    #[DataProvider('haveUserRightProvider')]
    public function testHaveUserRight(array $user, array $rightset)
    {

        $this->login($user['login'], $user['password']);

        foreach ($rightset as $rightdata) {
            $result = \Profile::haveUserRight(
                \Session::getLoginUserID(),
                $rightdata['name'],
                $rightdata['value'],
                0
            );
            $this->assertEquals(
                $rightdata['expected'],
                $result,
                sprintf('Unexpected result for value "%d" of "%s" right.', $rightdata['value'], $rightdata['name'])
            );
        }
    }

    /**
     * We try to login with tech profile and check if we can get a super-admin profile
     */
    public function testGetUnderActiveProfileRestrictCriteria()
    {
        global $DB;

        $this->login('tech', 'tech');

        $iterator = $DB->request([
            'FROM'   => \Profile::getTable(),
            'WHERE'  => \Profile::getUnderActiveProfileRestrictCriteria(),
            'ORDER'  => 'name',
        ]);

        foreach ($iterator as $profile_found) {
            $this->assertNotEquals('Super-Admin', $profile_found['name']);
            $this->assertNotEquals('Admin', $profile_found['name']);
        }
    }

    /**
     * Check we keep only necessary rights (at least for ticket)
     * when passing a profile from standard to self-service interface
     */
    public function testSwitchingInterface()
    {
        $ticket = new \Ticket();

        //create a temporary standard profile
        $profile = new \Profile();
        $profiles_id = $profile->add([
            'name'      => "test switch profile",
            'interface' => "standard",
        ]);

        // retrieve all tickets rights
        $all_rights = $ticket->getRights();
        $all_rights = array_keys($all_rights);
        $all_rights = array_fill_keys($all_rights, 1);

        // add all ticket rights to this profile
        $profile->update([
            'id'      => $profiles_id,
            '_ticket' => $all_rights,
        ]);

        // switch to self-service interface
        $profile->update([
            'id'        => $profiles_id,
            'interface' => 'helpdesk',
        ]);

        // retrieve self-service tickets rights
        $ss_rights = $ticket->getRights("helpdesk");
        $ss_rights = array_keys($ss_rights);
        $ss_rights = array_fill_keys($ss_rights, 1);
        $exc_rights = array_diff_key($all_rights, $ss_rights);

        //reload profile
        $profile->getFromDB($profiles_id);

        // check removed rights is clearly removed
        foreach ($exc_rights as $right => $value) {
            $this->assertEquals(0, ($profile->fields['ticket'] & $right));
        }
        // check self-service rights is still here
        foreach ($ss_rights as $right => $value) {
            $this->assertEquals($right, ($profile->fields['ticket'] & $right));
        }
    }

    public function testClone()
    {
        global $DB;

        // Get default "Admin" profile
        $profile = new \Profile();
        $this->assertTrue($profile->getFromDB(3));

        $this->assertTrue($profile->update([
            'id' => $profile->fields['id'],
            '_asset_test01' => [
                READ . "_0" => 1,
                UPDATE . "_0" => 1,
                CREATE . "_0" => 1,
                DELETE . "_0" => 1,
            ],
        ]));
        $profile->getFromDB($profile->fields['id']);

        // Clone it
        $cloned_profile = new \Profile();
        $clone_profiles_id = $profile->clone([
            'name' => __FUNCTION__,
        ]);
        $this->assertGreaterThan(0, $clone_profiles_id);
        $this->assertTrue($cloned_profile->getFromDB($clone_profiles_id));

        // Verify the original profile still references the source profile
        $this->assertEquals(3, $profile->fields['id']);

        // Some fields in the Profile itself to check that they are cloned
        $core_fields = ['interface', 'helpdesk_hardware', 'helpdesk_item_type'];
        foreach ($core_fields as $field) {
            if ($field === 'helpdesk_item_type') {
                $this->assertEquals(
                    importArrayFromDB($profile->fields[$field]),
                    importArrayFromDB($cloned_profile->fields[$field])
                );
            } else {
                $this->assertEquals($profile->fields[$field], $cloned_profile->fields[$field]);
            }
        }

        $rights_iterator = $DB->request([
            'SELECT' => ['profiles_id', 'name', 'rights'],
            'FROM'   => \ProfileRight::getTable(),
            'WHERE'  => ['profiles_id' => [3, $clone_profiles_id]],
        ]);
        // Check that all rights with profiles_id 3 exist with the clone ID as well
        $rights = [
            3 => [],
            $clone_profiles_id => [],
        ];
        foreach ($rights_iterator as $right) {
            $rights[$right['profiles_id']][$right['name']] = $right['rights'];
        }
        $this->assertEquals(
            count($rights[3]),
            count($rights[$clone_profiles_id])
        );

        foreach ($rights[3] as $right => $value) {
            $this->assertEquals($value, $rights[$clone_profiles_id][$right]);
        }

        $definition = getItemByTypeName(AssetDefinition::class, 'Test01');
        $definition_rights = importArrayFromDB($definition->fields['profiles']);
        $this->assertEquals(15, $definition_rights[$profile->fields['id']]);
        $this->assertEquals(15, $definition_rights[$clone_profiles_id]);
    }

    /**
     * Tests for Profile->canPurgeItem()
     *
     * @return void
     */
    public function testCanPurgeItem(): void
    {
        // Default: only one super admin account, can't be deleted
        $super_admin = getItemByTypeName('Profile', 'Super-Admin');
        $this->assertTrue($super_admin->isLastSuperAdminProfile());
        $this->assertFalse($super_admin->canPurgeItem());

        $super_admin_2 = $this->createItem("Profile", [
            "name" => "Super-Admin 2",
        ]);
        $this->assertTrue($super_admin->isLastSuperAdminProfile());
        $this->assertFalse($super_admin_2->isLastSuperAdminProfile());

        // Two super admin account, can't be deleted because only one has central interface
        $this->updateItem("Profile", $super_admin_2->getID(), [
            '_profile' => [UPDATE . "_0" => true],
        ]);
        $this->assertTrue($super_admin->isLastSuperAdminProfile());
        $this->assertFalse($super_admin->canPurgeItem());
        $this->assertFalse($super_admin_2->isLastSuperAdminProfile());
        $this->assertTrue($super_admin_2->canPurgeItem());

        // Two super admin account, both can be deleted
        $this->updateItem("Profile", $super_admin_2->getID(), [
            'interface' => 'central',
        ]);
        $this->assertFalse($super_admin->isLastSuperAdminProfile());
        $this->assertTrue($super_admin->canPurgeItem());
        $this->assertFalse($super_admin_2->isLastSuperAdminProfile());
        $this->assertTrue($super_admin_2->canPurgeItem());
    }

    /**
     * Tests for Profile->prepareInputForUpdate()
     *
     * @return void
     */
    public function testprepareInputForUpdate(): void
    {
        // Default: only one super admin account, can't remove update rights
        $super_admin = getItemByTypeName('Profile', 'Super-Admin');
        $this->assertTrue($super_admin->isLastSuperAdminProfile());
        $this->assertTrue($super_admin->update([
            'id' => $super_admin->getId(),
            '_profile' => [UPDATE . "_0" => false],
        ]));
        $this->hasSessionMessages(ERROR, [
            // Session messages may contain HTML (allowed), but this message only contains text from translations and it should be santiiized
            "Can&#039;t remove update right on this profile as it is the only remaining profile with this right.",
        ]);

        // Try to change the interface of the lock profile
        $readonly = getItemByTypeName('Profile', 'Read-Only');
        $this->updateItem("Profile", $readonly->getID(), [
            'interface' => 'helpdesk',
        ], ['interface']); // Skip interface check as it should not be updated.
        $readonly->getFromDB($readonly->fields['id']); // Reload data
        $this->assertEquals('central', $readonly->fields['interface']);
        $this->hasSessionMessages(ERROR, [
            // Session messages may contain HTML (allowed), but this message only contains text from translations and it should be santiiized
            "This profile can&#039;t be moved to the simplified interface as it is used for locking items.",
        ]);
    }

    /**
     * Test that core profile rights have a search option in the Profile class to ensure that changes are recorded in the profile's history.
     */
    public function testProfileRightsHaveSearchOptions()
    {
        $search_opts = \Search::getOptions(\Profile::class);
        // We can keep only the options that have 'right' as the field
        $search_opts = array_filter($search_opts, static function ($opt) {
            return is_array($opt) && isset($opt['field']) && $opt['field'] === 'rights';
        });
        $failures = [];
        $all_rights = \Profile::getRightsForForm();

        foreach ($all_rights as $interface => $forms) {
            foreach ($forms as $form => $groups) {
                foreach ($groups as $group => $rights) {
                    $previous_right = null;
                    foreach ($rights as $right) {
                        $failure_message = 'A right is missing a field name. Please check that the class has the rightname property set or the right is otherwise defined with the field property in the array.';
                        $locator_message = $previous_right
                            ? "The previous right was: " . print_r($previous_right, true) . " in {$interface}/{$form}/{$group}"
                            : "The right was the first one in {$interface}/{$form}/{$group}";
                        $this->assertNotEmpty($right['field'], $failure_message . ' ' . $locator_message);
                        $search_opt_matches = array_filter($search_opts, static function ($opt) use ($right) {
                            return array_key_exists('rightname', $opt) && $opt['rightname'] === $right['field'];
                        });
                        if (!count($search_opt_matches)) {
                            $failures[] = $right['field'];
                        }
                        $previous_right = $right;
                    }
                }
            }
        }

        $failures = array_unique($failures);
        $this->assertEmpty($failures, sprintf('The following rights do not have a search option: %s', implode(', ', $failures)));
    }

    public function testDefaultProfileMoreThanRights()
    {
        global $DB;

        // Some profiles are not directly heirarchically comparable, so we list the rights that can be ignored for this test
        $profiles_by_permission_level = [
            'Super-Admin' => [],
            'Admin' => [],
            'Supervisor' => [],
            'Technician' => ['ticket'], // Hotliners can see new tickets, but not technicians
            //'Observer' => [], Not able to be compared well with other profiles
            'Hotliner' => ['reservation', 'reminder_public', 'rssfeed_public'], // Hotliner cannot reserver items, or see public reminders/rss feeds
            'Self-Service' => [],
        ];
        $this->login();
        for ($i = 0; $i < count($profiles_by_permission_level) - 1; $i++) {
            $profiles_id = getItemByTypeName('Profile', array_keys($profiles_by_permission_level)[$i], true);
            $rights_to_ignore = array_values($profiles_by_permission_level)[$i];
            \Session::changeProfile($profiles_id);
            $lower_profiles = array_slice(array_keys($profiles_by_permission_level), $i + 1);
            foreach ($lower_profiles as $lower_profile_name) {
                $lower_profile_id = getItemByTypeName('Profile', $lower_profile_name, true);
                // Not using `Profile::currentUserHaveMoreRightThan` as it has some conditions that are not suitable here
                $criteria = [
                    'FROM' => 'glpi_profilerights AS pa',
                    'LEFT JOIN' => [
                        'glpi_profilerights AS pb' => [
                            'ON' => [
                                'pa' => 'name',
                                'pb' => 'name',
                                ['AND' => ['pb.profiles_id' => $lower_profile_id]],
                            ],
                        ],
                    ],
                    'WHERE' => [
                        'pa.profiles_id' => $profiles_id,
                        new QueryExpression('(pb.rights | pa.rights) <> pa.rights'),
                    ],
                ];
                if (!empty($rights_to_ignore)) {
                    $criteria['WHERE'][] = [
                        'NOT' => ['pa.name' => $rights_to_ignore],
                    ];
                }
                $lower_rights = $DB->request($criteria);
                $this->assertCount(0, $lower_rights, sprintf(
                    'Profile %s should have more rights than %s but has less rights for: %s',
                    array_keys($profiles_by_permission_level)[$i],
                    $lower_profile_name,
                    implode(', ', array_column(iterator_to_array($lower_rights), 'name')),
                ));
            }
        }
    }
}
