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
        $this->assertGreaterThan(11, count($actions));
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
                    'sub_type'  => 'RuleRight'
                ]
            )
        );
    }

    public function testLocalAccount()
    {
        //prepare rules
        $rules = new \RuleRight();
        $rules_id = $rules->add([
            'sub_type'     => 'RuleRight',
            'name'         => 'test local account ruleright',
            'match'        => 'AND',
            'is_active'    => 1,
            'entities_id'  => 0,
            'is_recursive' => 1,
        ]);

        $criteria = new \RuleCriteria();
        $criteria->add([
            'rules_id'  => $rules_id,
            'criteria'  => 'LOGIN',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => TU_USER,
        ]);
        $criteria->add([
            'rules_id'  => $rules_id,
            'criteria'  => 'MAIL_EMAIL',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => TU_USER . '@glpi.com',
        ]);

        $actions = new \RuleAction();
        $actions->add([
            'rules_id'    => $rules_id,
            'action_type' => 'assign',
            'field'       => 'profiles_id',
            'value'       => 5, // 'normal' profile
        ]);
        $actions->add([
            'rules_id'    => $rules_id,
            'action_type' => 'assign',
            'field'       => 'entities_id',
            'value'       => 1, // '_test_child_1' entity
        ]);

        // login the user to force a real synchronisation and get it's glpi id
        $this->login(TU_USER, TU_PASS, false);
        $users_id = \User::getIdByName(TU_USER);
        $this->assertGreaterThan(0, $users_id);

        // check the user got the entity/profiles assigned
        $pu = \Profile_User::getForUser($users_id, true);

        // check the assigned right exists in the collection
        $found = false;
        foreach ($pu as $right) {
            if (
                isset($right['entities_id']) && $right['entities_id'] == 1
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
        $this->login(TU_USER, TU_PASS, false);

        // check the user got the entity/profiles assigned
        $pu = \Profile_User::getForUser($users_id, true);

        // check the assigned right exists in the collection
        $found = false;
        foreach ($pu as $right) {
            if (
                isset($right['entities_id']) && $right['entities_id'] == 1
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
        $this->login();
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
        $this->login($testuser['name'], $testuser['password'], false);

        // Get rights again, should not have changed
        $pu = \Profile_User::getForUser($users_id, true);
        $this->assertCount(1, $pu);
        $right2 = array_pop($pu);
        $this->assertEquals($right, $right2);

        $this->login();
        // Change the $right profile, since this is the default profile it should
        // be fixed on next login
        $pu = new \Profile_User();
        $res = $pu->update([
            'id' => $right2['id'],
            'profiles_id' => 2
        ]);
        $this->assertTrue($res);
        $this->assertTrue($pu->getFromDB($right2['id']));
        $this->assertEquals(1, $pu->fields['is_default_profile']);
        $this->assertEquals(1, $pu->fields['is_dynamic']);

        $this->login($testuser['name'], $testuser['password'], false);
        $pu = \Profile_User::getForUser($users_id, true);
        $this->assertCount(1, $pu);
        $right3 = array_pop($pu);

        // Compare without id which changed
        unset($right['id']);
        unset($right3['id']);
        $this->assertEquals($right, $right3);

        // Clean session
        $this->login();
    }
}
