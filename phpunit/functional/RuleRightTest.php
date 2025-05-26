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

/* Test for inc/RuleRight.class.php */

class RuleRightTest extends DbTestCase
{
    public function testGetCriteria()
    {
        $rule = new \RuleRight();
        $criteria = $rule->getCriterias();
        $this->assertGreaterThan(5, count($criteria));
    }

    public function testGetActions()
    {
        $rule = new \RuleRight();
        $actions  = $rule->getActions();
        $this->assertEquals(17, count($actions));
    }

    public function testDefaultRuleExists()
    {
        $this->assertSame(
            1,
            countElementsInTable(
                'glpi_rules',
                [
                    'name'      => 'Root',
                    'is_active' => 1,
                    'sub_type'  => 'RuleRight',
                ]
            )
        );
    }

    public function testLocalAccount()
    {
        $test_root_entity = $this->getTestRootEntity(true);

        //prepare rules
        $rules = new \RuleRight();
        $rules_id = $this->createItem(\RuleRight::class, [
            'sub_type'     => 'RuleRight',
            'name'         => 'test local account ruleright',
            'match'        => 'AND',
            'is_active'    => 1,
            'entities_id'  => 0,
            'is_recursive' => 1,
        ])->getID();

        $this->createItem(\RuleCriteria::class, [
            'rules_id'  => $rules_id,
            'criteria'  => 'LOGIN',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => TU_USER,
        ]);
        $this->createItem(\RuleCriteria::class, [
            'rules_id'  => $rules_id,
            'criteria'  => 'MAIL_EMAIL',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => TU_USER . '@glpi.com',
        ]);

        $this->createItem(\RuleAction::class, [
            'rules_id'    => $rules_id,
            'action_type' => 'assign',
            'field'       => 'profiles_id',
            'value'       => 5, // 'normal' profile
        ]);
        $this->createItem(\RuleAction::class, [
            'rules_id'    => $rules_id,
            'action_type' => 'assign',
            'field'       => 'entities_id',
            'value'       => $test_root_entity,
        ]);

        // login the user to force a real synchronisation and get it's glpi id
        $this->realLogin(TU_USER, TU_PASS, false);
        $users_id = \User::getIdByName(TU_USER);
        $this->assertGreaterThan(0, $users_id);

        $user = new \User();
        $user->getFromDB($users_id);
        $this->assertEquals($test_root_entity, $user->fields['entities_id']);

        $this->createItem(\RuleAction::class, [
            'rules_id'    => $rules_id,
            'action_type' => 'assign',
            'field'       => '_entities_id_default',
            'value'       => -1, // Full structure
        ]);

        $this->realLogin(TU_USER, TU_PASS, false);
        $user->getFromDB($users_id);
        $this->assertEquals(null, $user->fields['entities_id']);

        // check the user got the entity/profiles assigned
        $pu = \Profile_User::getForUser($users_id, true);

        // check the assigned right exists in the collection
        $found = false;
        foreach ($pu as $right) {
            if (
                isset($right['entities_id']) && $right['entities_id'] == $test_root_entity
                && isset($right['profiles_id']) && $right['profiles_id'] == 5
                && isset($right['is_dynamic']) && $right['is_dynamic'] == 1
            ) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);

        // cleanup
        $rules->delete([
            'id' => $rules_id,
        ], true);
        $exist = $rules->getFromDB($rules_id);
        $this->assertFalse($exist);

        // clean right singleton
        \SingletonRuleList::getInstance("RuleRight", 0)->load = 0;
        \SingletonRuleList::getInstance("RuleRight", 0)->list = [];

        // login again
        $this->realLogin(TU_USER, TU_PASS, false);

        // check the user got the entity/profiles assigned
        $pu = \Profile_User::getForUser($users_id, true);

        // check the assigned right exists in the collection
        $found = false;
        foreach ($pu as $right) {
            if (
                isset($right['entities_id']) && $right['entities_id'] == $test_root_entity
                && isset($right['profiles_id']) && $right['profiles_id'] == 5
                && isset($right['is_dynamic']) && $right['is_dynamic'] == 1
            ) {
                $found = true;
                break;
            }
        }
        $this->assertFalse($found);
    }

    public function testLocalAccountNoRules()
    {
        $testuser = [
            'name'      => 'testuser',
            'password'  => 'test',
            'password2' => 'test',
        ];

        $user = new \User();
        $this->realLogin();
        $users_id = $user->add($testuser);
        $this->assertGreaterThan(0, $users_id);

        // Get rights
        $pu = \Profile_User::getForUser($users_id, true);

        // User should have a single dynamic right
        $this->assertCount(1, $pu);
        $right = array_pop($pu);
        $this->assertEquals(1, $right['is_dynamic']);
        $this->assertEquals(1, $right['is_default_profile']);

        // Log in to force rules right processing
        $this->realLogin($testuser['name'], $testuser['password'], false);

        // Get rights again, should not have changed
        $pu = \Profile_User::getForUser($users_id, true);
        $this->assertCount(1, $pu);
        $right2 = array_pop($pu);
        $this->assertEquals($right, $right2);

        $this->realLogin();
        // Change the $right profile, since this is the default profile it should
        // be fixed on next login
        $pu = new \Profile_User();
        $res = $pu->update([
            'id' => $right2['id'],
            'profiles_id' => 2,
        ]);
        $this->assertTrue($res);
        $this->assertTrue($pu->getFromDB($right2['id']));
        $this->assertEquals(1, $pu->fields['is_default_profile']);
        $this->assertEquals(1, $pu->fields['is_dynamic']);

        $this->realLogin($testuser['name'], $testuser['password'], false);
        $pu = \Profile_User::getForUser($users_id, true);
        $this->assertCount(1, $pu);
        $right3 = array_pop($pu);

        // Compare without id which changed
        unset($right['id']);
        unset($right3['id']);
        $this->assertEquals($right, $right3);
    }

    public function testDenyLogin()
    {
        $rule = new \RuleRight();
        $this->assertGreaterThan(
            0,
            $rules_id = $rule->add([
                'sub_type'     => 'RuleRight',
                'name'         => 'deny login',
                'match'        => 'AND',
                'is_active'    => 1,
                'entities_id'  => 0,
                'is_recursive' => 1,
            ])
        );
        $criteria = new \RuleCriteria();
        $this->assertGreaterThan(
            0,
            $criteria->add([
                'rules_id'  => $rules_id,
                'criteria'  => 'LOGIN',
                'condition' => \Rule::PATTERN_IS,
                'pattern'   => TU_USER,
            ])
        );

        $actions = new \RuleAction();
        $this->assertGreaterThan(
            0,
            $actions->add([
                'rules_id'    => $rules_id,
                'action_type' => 'assign',
                'field'       => '_deny_login',
                'value'       => 1,
            ])
        );

        $this->realLogin(TU_USER, TU_PASS, true, false);
        $events = getAllDataFromTable('glpi_events', [
            'service' => 'login',
            'type' => 'system',
            'items_id' => 0,
        ]);
        $username = TU_USER;
        $this->assertCount(
            1,
            array_filter(
                $events,
                static function ($event) use ($username) {
                    return str_starts_with($event['message'], "Login for {$username} denied by authorization rules from IP ");
                }
            )
        );
    }

    public function testLanguage()
    {
        $rule = $this->createItem('RuleRight', [
            'sub_type' => 'RuleRight',
            'name' => __METHOD__,
            'match' => 'AND',
            'is_active' => 1,
            'entities_id' => 0,
            'is_recursive' => 1,
        ]);
        $this->createItem('RuleCriteria', [
            'rules_id' => $rule->getID(),
            'criteria' => 'LOGIN',
            'condition' => \Rule::PATTERN_IS,
            'pattern' => TU_USER,
        ]);
        $this->createItem('RuleAction', [
            'rules_id' => $rule->getID(),
            'action_type' => 'assign',
            'field' => 'language',
            'value' => 'fr_FR',
        ]);

        $user = new \User();

        // language is not set
        $user->getFromDBByName(TU_USER);
        $this->assertEquals(null, $user->getField('language'));

        // login
        $this->realLogin(TU_USER, TU_PASS);

        // language from rule
        $user->getFromDBByName(TU_USER);
        $this->assertEquals('fr_FR', $user->getField('language'));
    }
}
