<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

/* Test for inc/profile.class.php */

class Profile extends DbTestCase
{
    /**
     * @see self::testHaveUserRight()
     *
     * @return array
     */
    protected function haveUserRightProvider()
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
                    ['name' => \ITILFollowup::$rightname, 'value' => \ITILFollowup::ADDMYTICKET, 'expected' => true],
                    ['name' => \ITILFollowup::$rightname, 'value' => \ITILFollowup::ADDALLTICKET, 'expected' => false],
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
                    ['name' => \ITILFollowup::$rightname, 'value' => \ITILFollowup::ADDMYTICKET, 'expected' => true],
                    ['name' => \ITILFollowup::$rightname, 'value' => \ITILFollowup::ADDALLTICKET, 'expected' => true],
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
                    ['name' => \ITILFollowup::$rightname, 'value' => \ITILFollowup::ADDMYTICKET, 'expected' => true],
                    ['name' => \ITILFollowup::$rightname, 'value' => \ITILFollowup::ADDALLTICKET, 'expected' => true],
                ],
            ],
        ];
    }

    /**
     * Tests user rights checking.
     *
     * @param array   $user     Array containing 'login' and 'password' fields of tested user.
     * @param array   $rightset Array of arrays containing 'name', 'value' and 'expected' result of a right.
     *
     * @dataProvider haveUserRightProvider
     */
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
            $this->boolean($result)
             ->isEqualTo(
                 $rightdata['expected'],
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
            'ORDER'  => 'name'
        ]);

        foreach ($iterator as $profile_found) {
            $this->array($profile_found)->string['name']->isNotEqualTo('Super-Admin');
            $this->array($profile_found)->string['name']->isNotEqualTo('Admin');
        }
    }

    /**
     * Check we keep only necessary rights (at least for ticket)
     * when passing a profile from standard to self-service interface
     */
    public function testSwitchingInterface()
    {
        $ticket = new \Ticket();

       //create a temporay standard profile
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
            '_ticket' => $all_rights
        ]);

       // switch to self-service interface
        $profile->update([
            'id'        => $profiles_id,
            'interface' => 'helpdesk'
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
            $this->integer(($profile->fields['ticket'] & $right))->isEqualTo(0);
        }
       // check self-service rights is still here
        foreach ($ss_rights as $right => $value) {
            $this->integer(($profile->fields['ticket'] & $right))->isEqualTo($right);
        }
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
        $this->boolean($super_admin->isLastSuperAdminProfile())->isTrue();
        $this->boolean($super_admin->canPurgeItem())->isEqualTo(false);

        $super_admin_2 = $this->createItem("Profile", [
            "name" => "Super-Admin 2"
        ]);
        $this->boolean($super_admin->isLastSuperAdminProfile())->isTrue();
        $this->boolean($super_admin_2->isLastSuperAdminProfile())->isFalse();

        // Two super admin account, can't be deleted because only one has central interface
        $this->updateItem("Profile", $super_admin_2->getID(), [
            '_profile' => [UPDATE . "_0" => true]
        ]);
        $this->boolean($super_admin->isLastSuperAdminProfile())->isTrue();
        $this->boolean($super_admin->canPurgeItem())->isFalse();
        $this->boolean($super_admin_2->isLastSuperAdminProfile())->isFalse();
        $this->boolean($super_admin_2->canPurgeItem())->isTrue();

        // Two super admin account, both can be deleted
        $this->updateItem("Profile", $super_admin_2->getID(), [
            'interface' => 'central'
        ]);
        $this->boolean($super_admin->isLastSuperAdminProfile())->isFalse();
        $this->boolean($super_admin->canPurgeItem())->isTrue();
        $this->boolean($super_admin_2->isLastSuperAdminProfile())->isFalse();
        $this->boolean($super_admin_2->canPurgeItem())->isTrue();
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
        $this->boolean($super_admin->isLastSuperAdminProfile())->isTrue();
        $this->boolean($super_admin->update([
            'id' => $super_admin->getId(),
            '_profile' => [UPDATE . "_0" => false]
        ]))->isEqualTo(true);
        $this->hasSessionMessages(ERROR, [
            "Can't remove update right on this profile as it is the only remaining profile with this right."
        ]);

        // Try to change the interface of the lock profile
        $readonly = getItemByTypeName('Profile', 'Read-Only');
        $this->updateItem("Profile", $readonly->getID(), [
            'interface' => 'helpdesk'
        ], ['interface']); // Skip interface check as it should not be updated.
        $readonly->getFromDB($readonly->fields['id']); // Reload data
        $this->string($readonly->fields['interface'])->isEqualTo('central');
        $this->hasSessionMessages(ERROR, [
            "This profile can't be moved to the simplified interface as it is used for locking items."
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
                        if (empty($right['field'])) {
                            echo 'A right is missing a field name. Please check that the class has the rightname property set or the right is otherwise defined with the field property in the array';
                            if ($previous_right) {
                                echo 'The previous right was: ' . print_r($previous_right, true) . " in ${$interface}/${$form}/${$group}";
                            } else {
                                echo "The right was the first one in ${$interface}/${$form}/${$group}";
                            }
                            continue;
                        }
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
        if (count($failures)) {
            echo sprintf('The following rights do not have a search option: %s', implode(', ', $failures));
        }
        $this->array($failures)->isEmpty();
    }
}
