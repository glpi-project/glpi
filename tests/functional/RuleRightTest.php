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

use Glpi\Tests\DbTestCase;
use Glpi\Tests\RuleBuilder;
use PHPUnit\Framework\Attributes\DataProvider;

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
        $rule_builder = new RuleBuilder(__FUNCTION__, \RuleRight::class);
        $rule_builder->setEntity(0)
            ->setIsRecursive(1)
            ->addCriteria('LOGIN', \Rule::PATTERN_IS, TU_USER)
            ->addCriteria('MAIL_EMAIL', \Rule::PATTERN_IS, TU_USER . '@glpi.com')
            ->addAction('assign', 'profiles_id', 5) // 'normal' profile
            ->addAction('assign', 'entities_id', $test_root_entity);
        $rule = $this->createRule($rule_builder);
        $rules_id = $rule->getID();
        $rules = new \RuleRight();

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

    public static function ruleAccountAssignAndDefaultProfileAndEntityProvider()
    {
        yield 'Assign an existing profile without specifying a default' => [
            'user_data' => [
                'profiles_id' => 'Profile1',
                '_profiles_id_default' => 0,
                'entities_id' => 'Entity1',
                '_entities_id_default' => 0,
            ],
            'actions' => [
                'profiles_id' => 'Profile1',
            ],
            'expected' => [
                'profiles_id' => 'Profile1',
                '_profiles_id_default' => 0,
                'entities_id' => 'Entity1',
                '_entities_id_default' => 0,
            ],
        ];

        yield 'Set an already associated profile as the default' => [
            'user_data' => [
                'profiles_id' => 'Profile1',
                '_profiles_id_default' => 0,
                'entities_id' => 'Entity1',
                '_entities_id_default' => 0,
            ],
            'actions' => [
                '_profiles_id_default' => 'Profile1',
            ],
            'expected' => [
                'profiles_id' => 'Profile1',
                '_profiles_id_default' => 'Profile1',
                'entities_id' => 0,
                '_entities_id_default' => 0,
            ],
        ];

        yield 'Assign an existing entity without specifying a default' => [
            'user_data' => [
                'profiles_id' => 'Profile1',
                '_profiles_id_default' => 0,
                'entities_id' => 'Entity1',
                '_entities_id_default' => 0,
            ],
            'actions' => [
                'entities_id' => 'Entity2',
            ],
            'expected' => [
                'profiles_id' => 'Profile1',
                '_profiles_id_default' => 0,
                'entities_id' => 'Entity2',
                '_entities_id_default' => 0,
            ],
        ];

        yield 'Set an already associated entity as the default' => [
            'user_data' => [
                'profiles_id' => 'Profile1',
                '_profiles_id_default' => 0,
                'entities_id' => 'Entity1',
                '_entities_id_default' => 0,
            ],
            'actions' => [
                '_entities_id_default' => 'Entity1',
            ],
            'expected' => [
                'profiles_id' => 'Profile1',
                '_profiles_id_default' => 0,
                'entities_id' => 0,
                '_entities_id_default' => 'Entity1',
            ],
        ];

        yield 'Assign profile and entity and set both as defaults' => [
            'user_data' => [
                'profiles_id' => 'Profile1',
                '_profiles_id_default' => 0,
                'entities_id' => 'Entity1',
                '_entities_id_default' => 0,
            ],
            'actions' => [
                'profiles_id' => 'Profile1',
                '_profiles_id_default' => 'Profile1',
                'entities_id' => 'Entity1',
                '_entities_id_default' => 'Entity1',
            ],
            'expected' => [
                'profiles_id' => 'Profile1',
                '_profiles_id_default' => 'Profile1',
                'entities_id' => 'Entity1',
                '_entities_id_default' => 'Entity1',
            ],
        ];

        yield 'Attempt to set unassigned profiles and entities as default' => [
            'user_data' => [
                'profiles_id' => 'Profile1',
                '_profiles_id_default' => 0,
                'entities_id' => 'Entity1',
                '_entities_id_default' => 0,
            ],
            'actions' => [
                'profiles_id' => 'Profile1',
                '_profiles_id_default' => 'Profile2',
                'entities_id' => 'Entity1',
                '_entities_id_default' => 'Entity2',
            ],
            'expected' => [
                'profiles_id' => 'Profile1',
                '_profiles_id_default' => 0,
                'entities_id' => 'Entity1',
                '_entities_id_default' => 0,
            ],
        ];

        yield 'Re-assign existing default profile and entity' => [
            'user_data' => [
                'profiles_id' => 'Profile1',
                '_profiles_id_default' => 'Profile1',
                'entities_id' => 'Entity1',
                '_entities_id_default' => 'Entity1',
            ],
            'actions' => [
                'profiles_id' => 'Profile1',
                '_profiles_id_default' => 'Profile1',
                'entities_id' => 'Entity1',
                '_entities_id_default' => 'Entity1',
            ],
            'expected' => [
                'profiles_id' => 'Profile1',
                '_profiles_id_default' => 'Profile1',
                'entities_id' => 'Entity1',
                '_entities_id_default' => 'Entity1',
            ],
        ];

        yield 'Update defaults by assigning a new profile and a new entity' => [
            'user_data' => [
                'profiles_id' => 'Profile1',
                '_profiles_id_default' => 'Profile1',
                'entities_id' => 'Entity1',
                '_entities_id_default' => 'Entity1',
            ],
            'actions' => [
                'profiles_id' => 'Profile2',
                '_profiles_id_default' => 'Profile2',
                'entities_id' => 'Entity2',
                '_entities_id_default' => 'Entity2',
            ],
            'expected' => [
                'profiles_id' => 'Profile2',
                '_profiles_id_default' => 'Profile2',
                'entities_id' => 'Entity2',
                '_entities_id_default' => 'Entity2',
            ],
        ];

        yield 'Assign default profile without giving the profile right' => [
            'user_data' => [
                'profiles_id' => 'Profile1',
                '_profiles_id_default' => 0,
                'entities_id' => 'Entity1',
                '_entities_id_default' => 0,
            ],
            'actions' => [
                '_profiles_id_default' => 'Profile2',
            ],
            'expected' => [
                'profiles_id' => 'Profile1',
                '_profiles_id_default' => 0,
                'entities_id' => 0,
                '_entities_id_default' => 0,
            ],
        ];
    }

    #[DataProvider('ruleAccountAssignAndDefaultProfileAndEntityProvider')]
    public function testRuleAccountAssignAndDefaultProfileAndEntity(array $user_data, array $actions, array $expected)
    {
        $this->login();

        $profile_user = new \Profile_User();
        $rules = new \RuleRight();

        // Create an entity and a profile to be assigned by the rule.
        [$entity1, $entity2] = $this->createItems(\Entity::class, [
            [
                'name'        => 'Entity1',
                'entities_id' => 0,
            ],
            [
                'name'        => 'Entity2',
                'entities_id' => 0,
            ],
        ]);
        [$profile1, $profile2] = $this->createItems(\Profile::class, [
            ['name' => 'Profile1'],
            ['name' => 'Profile2'],
        ]);

        // Replace search keys by created items ids in actions, expected and user data.
        $search_keys = ['Profile1', 'Profile2', 'Entity1', 'Entity2'];
        $replace_ids = [$profile1->getID(), $profile2->getID(), $entity1->getID(), $entity2->getID()];

        foreach ($actions as $key => $value) {
            $actions[$key] = intval(str_replace($search_keys, $replace_ids, $value));
        }
        foreach ($expected as $key => $value) {
            $expected[$key] = intval(str_replace($search_keys, $replace_ids, $value));
        }
        foreach ($user_data as $key => $value) {
            $user_data[$key] = intval(str_replace($search_keys, $replace_ids, $value));
        }

        $user = $this->createItem(\User::class, [
            'name'      => __FUNCTION__ . '_user',
            'password'  => 'test',
            'password2' => 'test',
            'profiles_id' => $user_data['_profiles_id_default'],
            'entities_id' => $user_data['_entities_id_default'],
        ], ['password', 'password2']);

        $this->createItem(\Profile_User::class, [
            'users_id' => $user->getID(),
            'profiles_id' => $user_data['profiles_id'],
            'entities_id' => $user_data['entities_id'],
        ]);

        // Prepare rule to assign the default profile and default entity.
        $rule_builder = new RuleBuilder(__FUNCTION__, \RuleRight::class);
        $rule_builder->setEntity(0)
            ->addCriteria('LOGIN', \Rule::PATTERN_IS, $user->fields['name'])
            ->setIsRecursive(1);
        foreach ($actions as $key => $value) {
            $rule_builder->addAction('assign', $key, $value);
        }
        $rule = $this->createRule($rule_builder);

        // Login to trigger RuleRight processing.
        $this->realLogin($user->fields['name'], 'test', false);

        // Check the user got the entity/profiles assigned.
        $this->assertTrue($user->getFromDB($user->getID()));
        $this->assertEquals($expected['_profiles_id_default'], $user->fields['profiles_id']);
        $this->assertEquals($expected['_entities_id_default'], $user->fields['entities_id']);

        // Check the profile is in the user profiles collection.
        $profiles = $profile_user->getUserProfiles($user->getID());
        $this->assertContains($expected['profiles_id'], $profiles);

        // Cleanup
        $rules->delete([
            'id' => $rule->getID(),
        ], true);

        // Clean singleton to avoid polluting next tests.
        \SingletonRuleList::getInstance(\RuleRight::class, 0)->load = 0;
        \SingletonRuleList::getInstance(\RuleRight::class, 0)->list = [];
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
        $rule_builder = new RuleBuilder(__FUNCTION__, \RuleRight::class);
        $rule_builder->setEntity(0)
            ->setIsRecursive(1)
            ->addCriteria('LOGIN', \Rule::PATTERN_IS, TU_USER)
            ->addAction('assign', '_deny_login', 1);
        $this->createRule($rule_builder);

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
        $rule_builder = new RuleBuilder(__FUNCTION__, \RuleRight::class);
        $rule_builder->setEntity(0)
            ->setIsRecursive(1)
            ->addCriteria('LOGIN', \Rule::PATTERN_IS, TU_USER)
            ->addAction('assign', 'language', 'fr_FR');
        $this->createRule($rule_builder);

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

    public function testAssignGroupByRegexResult()
    {
        $group = $this->createItem(\Group::class, [
            'name' => '_test_user_group_rule',
            'entities_id' => 0,
        ]);

        $rule = $this->createItem(\RuleRight::class, [
            'sub_type'     => 'RuleRight',
            'name'         => __METHOD__,
            'match'        => 'AND',
            'is_active'    => 1,
            'entities_id'  => 0,
            'is_recursive' => 1,
        ]);

        $this->createItem(\RuleCriteria::class, [
            'rules_id'  => $rule->getID(),
            'criteria'  => 'LOGIN',
            'condition' => \Rule::REGEX_MATCH,
            'pattern'   => '/(.*)/',
        ]);

        $this->createItem(\RuleAction::class, [
            'rules_id'    => $rule->getID(),
            'action_type' => 'regex_result',
            'field'       => 'specific_groups_id',
            'value'       => '#0_group_rule',
        ]);

        $user = new \User();
        $user->getFromDBByName(TU_USER);
        $users_id = $user->getID();

        $group_user = new \Group_User();
        $this->assertFalse(
            $group_user->getFromDBByCrit([
                'users_id'  => $users_id,
                'groups_id' => $group->getID(),
            ])
        );

        $this->realLogin(TU_USER, TU_PASS, false);

        $this->assertTrue(
            $group_user->getFromDBByCrit([
                'users_id'  => $users_id,
                'groups_id' => $group->getID(),
                'is_dynamic' => 1,
            ])
        );
    }

    public function testAssignMultipleGroupsByRegexResult()
    {
        $group1 = $this->createItem(\Group::class, [
            'name' => '_test_user_group_rule_alpha',
            'entities_id' => 0,
        ]);
        $group2 = $this->createItem(\Group::class, [
            'name' => '_test_user_group_rule_beta',
            'entities_id' => 0,
        ]);

        $rule1 = $this->createItem(\RuleRight::class, [
            'sub_type'     => 'RuleRight',
            'name'         => __METHOD__ . '_alpha',
            'match'        => 'AND',
            'is_active'    => 1,
            'entities_id'  => 0,
            'is_recursive' => 1,
        ]);

        $this->createItem(\RuleCriteria::class, [
            'rules_id'  => $rule1->getID(),
            'criteria'  => 'LOGIN',
            'condition' => \Rule::REGEX_MATCH,
            'pattern'   => '/^(.+)$/',
        ]);

        $this->createItem(\RuleAction::class, [
            'rules_id'    => $rule1->getID(),
            'action_type' => 'regex_result',
            'field'       => 'specific_groups_id',
            'value'       => '#0_group_rule_alpha',
        ]);

        $rule2 = $this->createItem(\RuleRight::class, [
            'sub_type'     => 'RuleRight',
            'name'         => __METHOD__ . '_beta',
            'match'        => 'AND',
            'is_active'    => 1,
            'entities_id'  => 0,
            'is_recursive' => 1,
        ]);

        $this->createItem(\RuleCriteria::class, [
            'rules_id'  => $rule2->getID(),
            'criteria'  => 'LOGIN',
            'condition' => \Rule::REGEX_MATCH,
            'pattern'   => '/^(.+)$/',
        ]);

        $this->createItem(\RuleAction::class, [
            'rules_id'    => $rule2->getID(),
            'action_type' => 'regex_result',
            'field'       => 'specific_groups_id',
            'value'       => '#0_group_rule_beta',
        ]);

        $user = new \User();
        $user->getFromDBByName(TU_USER);
        $users_id = $user->getID();

        $this->realLogin(TU_USER, TU_PASS, false);

        $group_user = new \Group_User();
        $this->assertTrue(
            $group_user->getFromDBByCrit([
                'users_id'  => $users_id,
                'groups_id' => $group1->getID(),
                'is_dynamic' => 1,
            ])
        );
        $this->assertTrue(
            $group_user->getFromDBByCrit([
                'users_id'  => $users_id,
                'groups_id' => $group2->getID(),
                'is_dynamic' => 1,
            ])
        );
    }

    public function testAssignGroupByRegexResultNoMatch()
    {
        $rule = $this->createItem(\RuleRight::class, [
            'sub_type'     => 'RuleRight',
            'name'         => __METHOD__,
            'match'        => 'AND',
            'is_active'    => 1,
            'entities_id'  => 0,
            'is_recursive' => 1,
        ]);

        $this->createItem(\RuleCriteria::class, [
            'rules_id'  => $rule->getID(),
            'criteria'  => 'LOGIN',
            'condition' => \Rule::REGEX_MATCH,
            'pattern'   => '/^(.+)$/',
        ]);

        $this->createItem(\RuleAction::class, [
            'rules_id'    => $rule->getID(),
            'action_type' => 'regex_result',
            'field'       => 'specific_groups_id',
            'value'       => 'nonexistent_group_#0',
        ]);

        $user = new \User();
        $user->getFromDBByName(TU_USER);
        $users_id = $user->getID();

        $groups_before = countElementsInTable(\Group_User::getTable(), [
            'users_id'   => $users_id,
            'is_dynamic' => 1,
        ]);

        \SingletonRuleList::getInstance("RuleRight", 0)->load = 0;
        \SingletonRuleList::getInstance("RuleRight", 0)->list = [];

        $this->realLogin(TU_USER, TU_PASS, false);

        $groups_after = countElementsInTable(\Group_User::getTable(), [
            'users_id'   => $users_id,
            'is_dynamic' => 1,
        ]);

        $this->assertEquals($groups_before, $groups_after);
    }

    public function testBypassLdapRules()
    {
        $this->login();

        $profile_user = new \Profile_User();

        // Create an user and profile and entity to be used in the test
        $user = $this->createItem(\User::class, [
            'name'      => __FUNCTION__ . '_user',
            'password'  => 'test',
            'password2' => 'test',
        ], ['password', 'password2']);

        $profile = $this->createItem(\Profile::class, [
            'name' => 'ProfileBypass',
        ]);

        $entity = $this->createItem(\Entity::class, [
            'name' => 'EntityBypass',
        ]);

        $input = [
            'profiles_id' => $profile->getID(),
            'entities_id' => $entity->getID(),
            '_ldap_rules' => [
                'rules_entities' => [
                    [$entity->getID(), 1]
                ],
                'rules_profiles' => [
                    [$profile->getID()]
                ]
            ]
        ];

        // Update the user with the LDAP rules bypass input
        $user = $this->updateItem(\User::class, $user->getID(), $input, ['profiles_id', 'entities_id']);

        $user->getFromDB($user->getID());
        $profiles = $profile_user->getUserProfiles($user->getID());

        // Verif if the profile and entity have not been updated by the input because of the LDAP rules bypass in prepareInputForUpdate
        $this->assertNotEquals($profile->getID(), $user->fields['profiles_id']);
        $this->assertNotEquals($entity->getID(), $user->fields['entities_id']);
        $this->assertNotContains($profile->getID(), $profiles);
    }
}
