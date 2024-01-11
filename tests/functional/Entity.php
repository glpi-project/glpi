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

use CommonITILActor;
use Contract;
use DbTestCase;
use ITILFollowup;
use ITILSolution;
use NotificationTarget;
use NotificationTargetTicket;
use Profile_User;
use Ticket;
use Ticket_Contract;
use Ticket_User;

/* Test for inc/entity.class.php */

class Entity extends DbTestCase
{
    public function testSonsAncestors()
    {
        $ent0 = getItemByTypeName('Entity', '_test_root_entity');
        $this->string($ent0->getField('completename'))
         ->isIdenticalTo('Root entity > _test_root_entity');

        $ent1 = getItemByTypeName('Entity', '_test_child_1');
        $this->string($ent1->getField('completename'))
         ->isIdenticalTo('Root entity > _test_root_entity > _test_child_1');

        $ent2 = getItemByTypeName('Entity', '_test_child_2');
        $this->string($ent2->getField('completename'))
         ->isIdenticalTo('Root entity > _test_root_entity > _test_child_2');

        $this->array(array_keys(getAncestorsOf('glpi_entities', $ent0->getID())))
         ->isIdenticalTo([0]);
        $this->array(array_values(getAncestorsOf('glpi_entities', $ent0->getID())))
         ->isIdenticalTo([0]);
        $this->array(array_keys(getSonsOf('glpi_entities', $ent0->getID())))
         ->isEqualTo([$ent0->getID(), $ent1->getID(), $ent2->getID()]);
        $this->array(array_values(getSonsOf('glpi_entities', $ent0->getID())))
         ->isIdenticalTo([$ent0->getID(), $ent1->getID(), $ent2->getID()]);

        $this->array(array_keys(getAncestorsOf('glpi_entities', $ent1->getID())))
         ->isEqualTo([0, $ent0->getID()]);
        $this->array(array_values(getAncestorsOf('glpi_entities', $ent1->getID())))
         ->isEqualTo([0, $ent0->getID()]);
        $this->array(array_keys(getSonsOf('glpi_entities', $ent1->getID())))
         ->isEqualTo([$ent1->getID()]);
        $this->array(array_values(getSonsOf('glpi_entities', $ent1->getID())))
         ->isEqualTo([$ent1->getID()]);

        $this->array(array_keys(getAncestorsOf('glpi_entities', $ent2->getID())))
         ->isEqualTo([0, $ent0->getID()]);
        $this->array(array_values(getAncestorsOf('glpi_entities', $ent2->getID())))
         ->isEqualTo([0, $ent0->getID()]);
        $this->array(array_keys(getSonsOf('glpi_entities', $ent2->getID())))
         ->isEqualTo([$ent2->getID()]);
        $this->array(array_values(getSonsOf('glpi_entities', $ent2->getID())))
         ->isEqualTo([$ent2->getID()]);
    }

    public function testPrepareInputForAdd()
    {
        $this->login();
        $entity = new \Entity();

        $this->boolean(
            $entity->prepareInputForAdd([
                'name' => ''
            ])
        )->isFalse();
        $this->hasSessionMessages(ERROR, ["You can't add an entity without name"]);

        $this->boolean(
            $entity->prepareInputForAdd([
                'anykey' => 'anyvalue'
            ])
        )->isFalse();
        $this->hasSessionMessages(ERROR, ["You can't add an entity without name"]);

        $this->array(
            $entity->prepareInputForAdd([
                'name' => 'entname'
            ])
        )
         ->string['name']->isIdenticalTo('entname')
         ->string['completename']->isIdenticalTo('entname')
         ->integer['level']->isIdenticalTo(1)
         ->integer['entities_id']->isIdenticalTo(0);
    }

    /**
     * Run getSonsOf tests
     *
     * @param boolean $cache Is cache enabled?
     * @param boolean $hit   Do we expect a cache hit? (ie. data already exists)
     *
     * @return void
     */
    private function runChangeEntityParent($cache = false, $hit = false)
    {
        global $GLPI_CACHE;

        $this->login();
        $ent0 = getItemByTypeName('Entity', '_test_root_entity', true);
        $ent1 = getItemByTypeName('Entity', '_test_child_1', true);
        $ent2 = getItemByTypeName('Entity', '_test_child_2', true);

        $sckey_ent1 = 'sons_cache_glpi_entities_' . $ent1;
        $sckey_ent2 = 'sons_cache_glpi_entities_' . $ent2;

        $entity = new \Entity();
        $new_id = (int)$entity->add([
            'name'         => 'Sub child entity',
            'entities_id'  => $ent1
        ]);
        $this->integer($new_id)->isGreaterThan(0);
        $ackey_new_id = 'ancestors_cache_glpi_entities_' . $new_id;

        $expected = [0 => 0, $ent0 => $ent0, $ent1 => $ent1];
        if ($cache === true) {
            $this->array($GLPI_CACHE->get($ackey_new_id))->isIdenticalTo($expected);
        }

        $ancestors = getAncestorsOf('glpi_entities', $new_id);
        $this->array($ancestors)->isIdenticalTo($expected);

        if ($cache === true && $hit === false) {
            $this->array($GLPI_CACHE->get($ackey_new_id))->isIdenticalTo($expected);
        }

        $expected = [$ent1 => $ent1, $new_id => $new_id];

        $sons = getSonsOf('glpi_entities', $ent1);
        $this->array($sons)->isIdenticalTo($expected);

        if ($cache === true && $hit === false) {
            $this->array($GLPI_CACHE->get($sckey_ent1))->isIdenticalTo($expected);
        }

       //change parent entity
        $this->boolean(
            $entity->update([
                'id'           => $new_id,
                'entities_id'  => $ent2
            ])
        )->isTrue();

        $expected = [0 => 0, $ent0 => $ent0, $ent2 => $ent2];
        if ($cache === true) {
            $this->array($GLPI_CACHE->get($ackey_new_id))->isIdenticalTo($expected);
        }

        $ancestors = getAncestorsOf('glpi_entities', $new_id);
        $this->array($ancestors)->isIdenticalTo($expected);

        if ($cache === true && $hit === false) {
            $this->array($GLPI_CACHE->get($ackey_new_id))->isIdenticalTo($expected);
        }

        $expected = [$ent1 => $ent1];
        $sons = getSonsOf('glpi_entities', $ent1);
        $this->array($sons)->isIdenticalTo($expected);

        if ($cache === true && $hit === false) {
            $this->array($GLPI_CACHE->get($sckey_ent1))->isIdenticalTo($expected);
        }

        $expected = [$ent2 => $ent2, $new_id => $new_id];
        $sons = getSonsOf('glpi_entities', $ent2);
        $this->array($sons)->isIdenticalTo($expected);

        if ($cache === true && $hit === false) {
            $this->array($GLPI_CACHE->get($sckey_ent2))->isIdenticalTo($expected);
        }

       //clean new entity
        $this->boolean(
            $entity->delete(['id' => $new_id], true)
        )->isTrue();
    }

    private function checkParentsSonsAreReset()
    {
        $ent0 = getItemByTypeName('Entity', '_test_root_entity', true);
        $ent1 = getItemByTypeName('Entity', '_test_child_1', true);
        $ent2 = getItemByTypeName('Entity', '_test_child_2', true);

        $expected = [0 => 0, 1 => $ent0];
        $ancestors = getAncestorsOf('glpi_entities', $ent1);
        $this->array($ancestors)->isIdenticalTo($expected);

        $ancestors = getAncestorsOf('glpi_entities', $ent2);
        $this->array($ancestors)->isIdenticalTo($expected);

        $expected = [$ent1 => $ent1];
        $sons = getSonsOf('glpi_entities', $ent1);
        $this->array($sons)->isIdenticalTo($expected);

        $expected = [$ent2 => $ent2];
        $sons = getSonsOf('glpi_entities', $ent2);
        $this->array($sons)->isIdenticalTo($expected);
    }

    public function testChangeEntityParent()
    {
        global $DB;
       //ensure db cache are unset
        $DB->update(
            'glpi_entities',
            [
                'ancestors_cache' => null,
                'sons_cache'      => null
            ],
            [true]
        );
        $this->runChangeEntityParent();
       //reset cache (checking for expected defaults) then run a second time: db cache must be set
        $this->checkParentsSonsAreReset();
        $this->runChangeEntityParent();
    }

    /**
     * @tags cache
     */
    public function testChangeEntityParentCached()
    {
       //run with cache
       //first run: no cache hit expected
        $this->runChangeEntityParent(true);
       //reset cache (checking for expected defaults) then run a second time: cache hit expected
       //second run: cache hit expected
        $this->checkParentsSonsAreReset();
        $this->runChangeEntityParent(true);
    }

    public function testInheritGeolocation()
    {
        $this->login();
        $ent0 = getItemByTypeName('Entity', '_test_root_entity', true);
        $ent1 = new \Entity();
        $ent1_id = $ent1->add([
            'entities_id'  => $ent0,
            'name'         => 'inherit_geo_test_parent',
            'latitude'     => '48.8566',
            'longitude'    => '2.3522',
            'altitude'     => '115'
        ]);
        $this->integer((int) $ent1_id)->isGreaterThan(0);
        $ent2 = new \Entity();
        $ent2_id = $ent2->add([
            'entities_id'  => $ent1_id,
            'name'         => 'inherit_geo_test_child',
        ]);
        $this->integer((int) $ent2_id)->isGreaterThan(0);
        $this->string($ent2->fields['latitude'])->isEqualTo($ent1->fields['latitude']);
        $this->string($ent2->fields['longitude'])->isEqualTo($ent1->fields['longitude']);
        $this->string($ent2->fields['altitude'])->isEqualTo($ent1->fields['altitude']);

       // Make sure we don't overwrite data a user sets
        $ent3 = new \Entity();
        $ent3_id = $ent3->add([
            'entities_id'  => $ent1_id,
            'name'         => 'inherit_geo_test_child2',
            'latitude'     => '41.3851',
            'longitude'    => '2.1734',
            'altitude'     => '39'
        ]);
        $this->integer((int) $ent3_id)->isGreaterThan(0);
        $this->string($ent3->fields['latitude'])->isEqualTo('41.3851');
        $this->string($ent3->fields['longitude'])->isEqualTo('2.1734');
        $this->string($ent3->fields['altitude'])->isEqualTo('39');
    }

    public function testDeleteEntity()
    {
        $this->login();
        $root_id = getItemByTypeName('Entity', '_test_root_entity', true);

        $entity = new \Entity();
        $entity_id = (int)$entity->add(
            [
                'name'         => 'Test entity',
                'entities_id'  => $root_id,
            ]
        );
        $this->integer($entity_id)->isGreaterThan(0);

        //make sure parent entity cannot be removed
        $this->boolean($entity->delete(['id' => $root_id]))->isFalse();
        $this->hasSessionMessages(ERROR, ["You cannot delete an entity which contains sub-entities."]);

        $user_id = getItemByTypeName('User', 'normal', true);
        $profile_id = getItemByTypeName('Profile', 'Admin', true);

        $profile_user = new Profile_User();
        $profile_user_id = (int)$profile_user->add(
            [
                'entities_id' => $entity_id,
                'profiles_id' => $profile_id,
                'users_id'    => $user_id,
            ]
        );
        $this->integer($profile_user_id)->isGreaterThan(0);

       // Profile_User exists
        $this->boolean($profile_user->getFromDB($profile_user_id))->isTrue();

        $this->boolean($entity->delete(['id' => $entity_id]))->isTrue();

       // Profile_User has been deleted when entity has been deleted
        $this->boolean($profile_user->getFromDB($profile_user_id))->isFalse();
    }

    protected function getUsedConfigProvider(): iterable
    {
        $root_id       = getItemByTypeName('Entity', 'Root entity', true);
        $child_id      = getItemByTypeName('Entity', '_test_root_entity', true);
        $grandchild_id = getItemByTypeName('Entity', '_test_child_1', true);

        // String value case
        yield [
            'root_values'       => ['admin_email' => 'admin+root@domain.tld'],
            'child_values'      => ['admin_email' => ''],
            'grandchild_values' => ['admin_email' => ''],
            'params'            => ['admin_email', $root_id, '', ''],
            'expected_result'   => 'admin+root@domain.tld', // self value
        ];
        yield [
            'root_values'       => ['admin_email' => 'admin+root@domain.tld'],
            'child_values'      => ['admin_email' => ''],
            'grandchild_values' => ['admin_email' => ''],
            'params'            => ['admin_email', $child_id, '', ''],
            'expected_result'   => 'admin+root@domain.tld', // inherit from root
        ];
        yield [
            'root_values'       => ['admin_email' => 'admin+root@domain.tld'],
            'child_values'      => ['admin_email' => 'admin+child@domain.tld'],
            'grandchild_values' => ['admin_email' => ''],
            'params'            => ['admin_email', $child_id, '', ''],
            'expected_result'   => 'admin+child@domain.tld', // self value
        ];
        yield [
            'root_values'       => ['admin_email' => 'admin+root@domain.tld'],
            'child_values'      => ['admin_email' => ''],
            'grandchild_values' => ['admin_email' => ''],
            'params'            => ['admin_email', $grandchild_id, '', ''],
            'expected_result'   => 'admin+root@domain.tld', // inherit from root
        ];
        yield [
            'root_values'       => ['admin_email' => 'admin+root@domain.tld'],
            'child_values'      => ['admin_email' => 'admin+child@domain.tld'],
            'grandchild_values' => ['admin_email' => ''],
            'params'            => ['admin_email', $grandchild_id, '', ''],
            'expected_result'   => 'admin+child@domain.tld', // inherit from parent
        ];
        yield [
            'root_values'       => ['admin_email' => 'admin+root@domain.tld'],
            'child_values'      => ['admin_email' => ''],
            'grandchild_values' => ['admin_email' => 'admin+grandchild@domain.tld'],
            'params'            => ['admin_email', $grandchild_id, '', ''],
            'expected_result'   => 'admin+grandchild@domain.tld', // self value
        ];

        // Inheritable value case
        yield [
            'root_values'       => ['use_domains_alert' => 1],
            'child_values'      => ['use_domains_alert' => -2],
            'grandchild_values' => ['use_domains_alert' => -2],
            'params'            => ['use_domains_alert', $root_id],
            'expected_result'   => 1, // self value
        ];
        yield [
            'root_values'       => ['use_domains_alert' => 1],
            'child_values'      => ['use_domains_alert' => -2],
            'grandchild_values' => ['use_domains_alert' => -2],
            'params'            => ['use_domains_alert', $child_id],
            'expected_result'   => 1, // inherit from root
        ];
        yield [
            'root_values'       => ['use_domains_alert' => 1],
            'child_values'      => ['use_domains_alert' => 0],
            'grandchild_values' => ['use_domains_alert' => -2],
            'params'            => ['use_domains_alert', $child_id],
            'expected_result'   => 0, // self value
        ];
        yield [
            'root_values'       => ['use_domains_alert' => 1],
            'child_values'      => ['use_domains_alert' => -2],
            'grandchild_values' => ['use_domains_alert' => -2],
            'params'            => ['use_domains_alert', $grandchild_id],
            'expected_result'   => 1, // inherit from root
        ];
        yield [
            'root_values'       => ['use_domains_alert' => 1],
            'child_values'      => ['use_domains_alert' => 0],
            'grandchild_values' => ['use_domains_alert' => -2],
            'params'            => ['use_domains_alert', $grandchild_id],
            'expected_result'   => 0, // inherit from parent
        ];
        yield [
            'root_values'       => ['use_domains_alert' => 1],
            'child_values'      => ['use_domains_alert' => -2],
            'grandchild_values' => ['use_domains_alert' => 0],
            'params'            => ['use_domains_alert', $grandchild_id],
            'expected_result'   => 0, // self value
        ];

        // Using strategy from another field case
        yield [
            'root_values'       => ['contracts_strategy_default' => -1, 'contracts_id_default' => 0],
            'child_values'      => ['contracts_strategy_default' => -2, 'contracts_id_default' => 0],
            'grandchild_values' => ['contracts_strategy_default' => -2, 'contracts_id_default' => 0],
            'params'            => ['contracts_strategy_default', $root_id],
            'expected_result'   => -1, // self strategy
        ];
        yield [
            'root_values'       => ['contracts_strategy_default' => 0, 'contracts_id_default' => 10],
            'child_values'      => ['contracts_strategy_default' => -2, 'contracts_id_default' => 0],
            'grandchild_values' => ['contracts_strategy_default' => -2, 'contracts_id_default' => 0],
            'params'            => ['contracts_strategy_default', $root_id, 'contracts_id_default', 0],
            'expected_result'   => 10, // self value
        ];
        yield [
            'root_values'       => ['contracts_strategy_default' => -1, 'contracts_id_default' => 0],
            'child_values'      => ['contracts_strategy_default' => -2, 'contracts_id_default' => 0],
            'grandchild_values' => ['contracts_strategy_default' => -2, 'contracts_id_default' => 0],
            'params'            => ['contracts_strategy_default', $child_id],
            'expected_result'   => -1, // inherit strategy from root
        ];
        yield [
            'root_values'       => ['contracts_strategy_default' => 0, 'contracts_id_default' => 10],
            'child_values'      => ['contracts_strategy_default' => -2, 'contracts_id_default' => 0],
            'grandchild_values' => ['contracts_strategy_default' => -2, 'contracts_id_default' => 0],
            'params'            => ['contracts_strategy_default', $child_id, 'contracts_id_default', 0],
            'expected_result'   => 10, // inherit value from root
        ];
        yield [
            'root_values'       => ['contracts_strategy_default' => 0, 'contracts_id_default' => 0],
            'child_values'      => ['contracts_strategy_default' => -1, 'contracts_id_default' => 0],
            'grandchild_values' => ['contracts_strategy_default' => -2, 'contracts_id_default' => 0],
            'params'            => ['contracts_strategy_default', $child_id],
            'expected_result'   => -1, // self strategy
        ];
        yield [
            'root_values'       => ['contracts_strategy_default' => 0, 'contracts_id_default' => 10],
            'child_values'      => ['contracts_strategy_default' => 0, 'contracts_id_default' => 12],
            'grandchild_values' => ['contracts_strategy_default' => -2, 'contracts_id_default' => 0],
            'params'            => ['contracts_strategy_default', $child_id, 'contracts_id_default', 0],
            'expected_result'   => 12, // self value
        ];
        yield [
            'root_values'       => ['contracts_strategy_default' => -1, 'contracts_id_default' => 0],
            'child_values'      => ['contracts_strategy_default' => -2, 'contracts_id_default' => 0],
            'grandchild_values' => ['contracts_strategy_default' => -2, 'contracts_id_default' => 0],
            'params'            => ['contracts_strategy_default', $grandchild_id],
            'expected_result'   => -1, // inherit strategy from root
        ];
        yield [
            'root_values'       => ['contracts_strategy_default' => 0, 'contracts_id_default' => 10],
            'child_values'      => ['contracts_strategy_default' => -2, 'contracts_id_default' => 0],
            'grandchild_values' => ['contracts_strategy_default' => -2, 'contracts_id_default' => 0],
            'params'            => ['contracts_strategy_default', $grandchild_id, 'contracts_id_default', 0],
            'expected_result'   => 10, // inherit value from root
        ];
        yield [
            'root_values'       => ['contracts_strategy_default' => 0, 'contracts_id_default' => 10],
            'child_values'      => ['contracts_strategy_default' => -1, 'contracts_id_default' => 0],
            'grandchild_values' => ['contracts_strategy_default' => -2, 'contracts_id_default' => 0],
            'params'            => ['contracts_strategy_default', $grandchild_id],
            'expected_result'   => -1, // inherit strategy from parent
        ];
        yield [
            'root_values'       => ['contracts_strategy_default' => 0, 'contracts_id_default' => 10],
            'child_values'      => ['contracts_strategy_default' => 0, 'contracts_id_default' => 15],
            'grandchild_values' => ['contracts_strategy_default' => -2, 'contracts_id_default' => 0],
            'params'            => ['contracts_strategy_default', $grandchild_id, 'contracts_id_default', 0],
            'expected_result'   => 15, // inherit value from parent
        ];
        yield [
            'root_values'       => ['contracts_strategy_default' => 0, 'contracts_id_default' => 10],
            'child_values'      => ['contracts_strategy_default' => -2, 'contracts_id_default' => 0],
            'grandchild_values' => ['contracts_strategy_default' => -1, 'contracts_id_default' => 0],
            'params'            => ['contracts_strategy_default', $grandchild_id],
            'expected_result'   => -1, // self strategy
        ];
        yield [
            'root_values'       => ['contracts_strategy_default' => 0, 'contracts_id_default' => 10],
            'child_values'      => ['contracts_strategy_default' => -2, 'contracts_id_default' => 0],
            'grandchild_values' => ['contracts_strategy_default' => 0, 'contracts_id_default' => 23],
            'params'            => ['contracts_strategy_default', $grandchild_id, 'contracts_id_default', 0],
            'expected_result'   => 23, // self value
        ];
    }

    /**
     * @dataProvider getUsedConfigProvider
     */
    public function testGetUsedConfig(
        array $root_values,
        array $child_values,
        array $grandchild_values,
        array $params,
        $expected_result
    ) {
        $this->login();

        $root_id       = getItemByTypeName('Entity', 'Root entity', true);
        $child_id      = getItemByTypeName('Entity', '_test_root_entity', true);
        $grandchild_id = getItemByTypeName('Entity', '_test_child_1', true);

        $entity = new \Entity();
        $this->boolean($entity->update(['id' => $root_id] + $root_values))->isTrue();
        $this->boolean($entity->update(['id' => $child_id] + $child_values))->isTrue();
        $this->boolean($entity->update(['id' => $grandchild_id] + $grandchild_values))->isTrue();

        $this->variable(call_user_func_array([\Entity::class, 'getUsedConfig'], $params))->isEqualTo($expected_result);
    }


    protected function customCssProvider()
    {

        $root_id  = getItemByTypeName('Entity', 'Root entity', true);
        $child_id = getItemByTypeName('Entity', '_test_child_1', true);

        return [
            [
            // Do not output custom CSS if not enabled
                'entity_id'               => $root_id,
                'root_enable_custom_css'  => 0,
                'root_custom_css_code'    => 'body { color:blue; }',
                'child_enable_custom_css' => 0,
                'child_custom_css_code'   => '',
                'expected'                => '',
            ],
            [
            // Output custom CSS if enabled
                'entity_id'               => $root_id,
                'root_enable_custom_css'  => 1,
                'root_custom_css_code'    => 'body { color:blue; }',
                'child_enable_custom_css' => 0,
                'child_custom_css_code'   => '',
                'expected'                => '<style>body { color:blue; }</style>',
            ],
            [
            // Do not output custom CSS if empty
                'entity_id'               => $root_id,
                'root_enable_custom_css'  => 1,
                'root_custom_css_code'    => '',
                'child_enable_custom_css' => 0,
                'child_custom_css_code'   => '',
                'expected'                => '',
            ],
            [
            // Do not output custom CSS from parent if disabled in parent
                'entity_id'               => $child_id,
                'root_enable_custom_css'  => 0,
                'root_custom_css_code'    => 'body { color:blue; }',
                'child_enable_custom_css' => \Entity::CONFIG_PARENT,
                'child_custom_css_code'   => '',
                'expected'                => '',
            ],
            [
            // Do not output custom CSS from parent if empty
                'entity_id'               => $child_id,
                'root_enable_custom_css'  => 1,
                'root_custom_css_code'    => '',
                'child_enable_custom_css' => \Entity::CONFIG_PARENT,
                'child_custom_css_code'   => '',
                'expected'                => '',
            ],
            [
            // Output custom CSS from parent
                'entity_id'               => $child_id,
                'root_enable_custom_css'  => 1,
                'root_custom_css_code'    => '.link::before { content: "test"; }',
                'child_enable_custom_css' => \Entity::CONFIG_PARENT,
                'child_custom_css_code'   => '',
                'expected'                => '<style>.link::before { content: "test"; }</style>',
            ],
            [
            // Do not output custom CSS from entity itself if disabled
                'entity_id'               => $child_id,
                'root_enable_custom_css'  => 0,
                'root_custom_css_code'    => '',
                'child_enable_custom_css' => 0,
                'child_custom_css_code'   => 'body { color:blue; }',
                'expected'                => '',
            ],
            [
            // Do not output custom CSS from entity itself if empty
                'entity_id'               => $child_id,
                'root_enable_custom_css'  => 1,
                'root_custom_css_code'    => '',
                'child_enable_custom_css' => 1,
                'child_custom_css_code'   => '',
                'expected'                => '',
            ],
            [
            // Output custom CSS from entity itself
                'entity_id'               => $child_id,
                'root_enable_custom_css'  => 0,
                'root_custom_css_code'    => '',
                'child_enable_custom_css' => 1,
                'child_custom_css_code'   => 'body > a { color:blue; }',
                'expected'                => '<style>body > a { color:blue; }</style>',
            ],
            [
            // Output cleaned custom CSS
                'entity_id'               => $child_id,
                'root_enable_custom_css'  => 0,
                'root_custom_css_code'    => '',
                'child_enable_custom_css' => 1,
                'child_custom_css_code'   => '</style><script>alert(1);</script>',
                'expected'                => '<style>alert(1);</style>',
            ],
        ];
    }

    /**
     * @dataProvider customCssProvider
     */
    public function testGetCustomCssTag(
        int $entity_id,
        int $root_enable_custom_css,
        string $root_custom_css_code,
        int $child_enable_custom_css,
        string $child_custom_css_code,
        string $expected
    ): void {
        $this->login();

        $entity = new \Entity();

       // Define configuration values
        $update = $entity->update(
            [
                'id'                => getItemByTypeName('Entity', 'Root entity', true),
                'enable_custom_css' => $root_enable_custom_css,
                'custom_css_code'   => $root_custom_css_code
            ]
        );
        $this->boolean($update)->isTrue();
        $update = $entity->update(
            [
                'id'                => getItemByTypeName('Entity', '_test_child_1', true),
                'enable_custom_css' => $child_enable_custom_css,
                'custom_css_code'   => $child_custom_css_code
            ]
        );
        $this->boolean($update)->isTrue();

       // Validate method result
        $this->boolean($entity->getFromDB($entity_id))->isTrue();
        $this->string($entity->getCustomCssTag())->isEqualTo($expected);
    }

    protected function testAnonymizeSettingProvider(): array
    {
        return [
            [
                'interface' => 'central',
                'setting'   => \Entity::ANONYMIZE_DISABLED,
                'expected'  => 'test_anon_user',
            ],
            [
                'interface' => 'helpdesk',
                'setting'   => \Entity::ANONYMIZE_DISABLED,
                'expected'  => 'test_anon_user',
            ],
            [
                'interface' => 'central',
                'setting'   => \Entity::ANONYMIZE_USE_GENERIC,
                'expected'  => 'test_anon_user',
            ],
            [
                'interface' => 'helpdesk',
                'setting'   => \Entity::ANONYMIZE_USE_GENERIC,
                'expected'  => "Helpdesk user",
            ],
            [
                'interface' => 'central',
                'setting'   => \Entity::ANONYMIZE_USE_NICKNAME,
                'expected'  => 'test_anon_user',
                'user_nick' => 'user_nick_6436345654'
            ],
            [
                'interface' => 'helpdesk',
                'setting'   => \Entity::ANONYMIZE_USE_NICKNAME,
                'expected'  => 'user_nick_6436345654',
                'user_nick' => 'user_nick_6436345654'
            ],
            [
                'interface' => 'central',
                'setting'   => \Entity::ANONYMIZE_USE_GENERIC_USER,
                'expected'  => 'test_anon_user',
            ],
            [
                'interface' => 'helpdesk',
                'setting'   => \Entity::ANONYMIZE_USE_GENERIC_USER,
                'expected'  => "Helpdesk user",
            ],
            [
                'interface' => 'central',
                'setting'   => \Entity::ANONYMIZE_USE_NICKNAME_USER,
                'expected'  => 'test_anon_user',
                'user_nick' => 'user_nick_6436345654'
            ],
            [
                'interface' => 'helpdesk',
                'setting'   => \Entity::ANONYMIZE_USE_NICKNAME_USER,
                'expected'  => 'user_nick_6436345654',
                'user_nick' => 'user_nick_6436345654'
            ],
            [
                'interface' => 'central',
                'setting'   => \Entity::ANONYMIZE_USE_GENERIC_GROUP,
                'expected'  => 'test_anon_user',
            ],
            [
                'interface' => 'helpdesk',
                'setting'   => \Entity::ANONYMIZE_USE_GENERIC_GROUP,
                'expected'  => 'test_anon_user',
            ],
        ];
    }

    /**
     * @dataProvider testAnonymizeSettingProvider
     */
    public function testAnonymizeSetting(
        string $interface,
        int $setting,
        string $expected,
        string $user_nick = ""
    ) {
        global $DB;

        $this->login();
        $possible_values = ['test_anon_user', 'user_nick_6436345654', "Helpdesk user"];

       // Set entity setting
        $entity = getItemByTypeName("Entity", "_test_root_entity");
        $update = $entity->update([
            'id'                       => $entity->getID(),
            'anonymize_support_agents' => $setting,
        ]);
        $this->boolean($update)->isTrue();

       // create a user for this test (avoid using current logged user as we don't anonymize him)
        $user_obj = new \User();
        $user_obj->add([
            'name'     => 'test_anon_user',
            'password' => 'test_anon_user'
        ]);

       // // Set user nickname
        $user = getItemByTypeName('User', 'test_anon_user');

        if ($user_nick == "" && $user->fields['nickname'] == null) {
           // Special case, glpi wont update null to "" so we need to set
           // another value first
            $update = $user->update([
                'id'       => $user->getID(),
                'nickname' => 'TMP',
            ]);
            $this->boolean($update)->isTrue();
            $this->boolean($user->getFromDB($user->getID()))->isTrue();
            $this->string($user->fields['nickname'])->isEqualTo('TMP');
        }

        $update = $user->update([
            'id'       => $user->getID(),
            'nickname' => $user_nick,
        ]);
        $this->boolean($update)->isTrue();
        $this->boolean($user->getFromDB($user->getID()))->isTrue();
        $this->string($user->fields['nickname'])->isEqualTo($user_nick);

       // Build test ticket
        $this->login('tech', 'tech');

       //force set entity because $_SESSION['glpiactive_entity'] contains 0 without
       //and break test from NotificationTargetCommonITILObject::getDataForObject()
       //and fails to recover the configuration of the anonymization
        $this->setEntity($entity->getID(), true);

        $ticket = new Ticket();
        $tickets_id = $ticket->add($input = [
            'name'                 => 'test',
            'content'              => 'test',
            '_users_id_assign'     => getItemByTypeName('User', 'test_anon_user', true),
            '_users_id_requester'  => getItemByTypeName('User', 'post-only', true),
            'entities_id'          => $entity->getID(),
            'users_id_recipient'   => getItemByTypeName('User', 'tech', true),
            'users_id_lastupdater' => getItemByTypeName('User', 'tech', true),
         // The default requesttype is "Helpdesk" and will mess up our tests,
         // we need another one to be sure the "Helpdesk" string will only be
         // printed by the anonymization code
            'requesttypes_id'      => 4,
        ]);
        $this->integer($tickets_id)->isGreaterThan(0);

       // Unset temporary fields that will not be found in tickets table
        unset($input['_users_id_assign']);
        unset($input['_users_id_requester']);

       // Check expected fields and reload object from DB
        $this->checkInput($ticket, $tickets_id, $input);

       // Check linked users
        $ticket_users = $DB->request([
            'SELECT' => ['tickets_id', 'users_id', 'type'],
            'FROM'   => Ticket_User::getTable(),
            'WHERE'  => ['tickets_id' => $tickets_id],
        ]);
        $this->array(iterator_to_array($ticket_users))->isEqualTo([
            0 => [
                'tickets_id' => $tickets_id,
                'users_id'   => getItemByTypeName('User', 'post-only', true),
                'type'       => CommonITILActor::REQUESTER,
            ],
            1 => [
                'tickets_id' => $tickets_id,
                'users_id'   => getItemByTypeName('User', 'test_anon_user', true),
                'type'       => CommonITILActor::ASSIGN,
            ],
        ]);

       // Add followup to test ticket
        $fup = new ITILFollowup();
        $fup_id = $fup->add([
            'content' => 'test',
            'users_id' => getItemByTypeName('User', 'test_anon_user', true),
            'users_id_editor' => getItemByTypeName('User', 'test_anon_user', true),
            'itemtype' => 'Ticket',
            'items_id' => $tickets_id,
        ]);
        $this->integer($fup_id)->isGreaterThan(0);

       // Add solution to test ticket
        $solution = new ITILSolution();
        $solutions_id = $solution->add([
            'content' => 'test',
            'users_id' => getItemByTypeName('User', 'test_anon_user', true),
            'users_id_editor' => getItemByTypeName('User', 'test_anon_user', true),
            'itemtype' => 'Ticket',
            'items_id' => $tickets_id,
        ]);
        $this->integer($solutions_id)->isGreaterThan(0);

       // Save and replace session data
        $old_interface = $_SESSION['glpiactiveprofile']['interface'];
        $_SESSION['glpiactiveprofile']['interface'] = $interface;

       // Case 1: removed (test values recovered from CommonITILObject::showUsersAssociated())

       // Case 2: test values recovered from CommonITILObject:::showShort()
        ob_start();
        Ticket::showShort($tickets_id);
        $html = ob_get_clean();

        foreach ($possible_values as $value) {
            if ($value == $expected) {
                $this->string($html)->contains(
                    $value,
                    "Ticket showShort must contains '$value' in interface '$interface' with settings '$setting'"
                );
            } else {
                $this->string($html)->notContains(
                    $value,
                    "Ticket form must not contains '$value' (expected '$expected') in interface '$interface' with settings '$setting'"
                );
            }
        }

       // Case 3: removed (timeline merged with main form)

       // Case 4: test values recovered from NotificationTargetCommonITILObject::getDataForObject()
        $notification = new NotificationTargetTicket();
        $notif_data = $notification->getDataForObject($ticket, [
            'additionnaloption' => [
                'usertype' => NotificationTarget::GLPI_USER,
            // Workaround to "simulate" different notification target and test
            // this part more easily
                'is_self_service' => $interface == 'helpdesk',
            ]
        ]);
        foreach ($notif_data['followups'] as $n_fup) {
            if ($n_fup['##followup.author##'] !== null) {
                foreach ($possible_values as $value) {
                    if ($value == $expected) {
                        $this->string($n_fup['##followup.author##'])->contains($value);
                    } else {
                        $this->string($n_fup['##followup.author##'])->notContains($value);
                    }
                }
            }
        }

       // Case 5: test values recovered from Ticket::showForm()
        ob_start();
        $ticket->showForm($tickets_id);
        $html = ob_get_clean();
       // Drop answers form, as new validation form contains current user name
        $html = preg_replace('/<div id="new-itilobject-form".*$/s', '', $html);

        foreach ($possible_values as $value) {
            if ($value == $expected) {
                $this->string($html)->contains(
                    $value,
                    "Ticket form must contains '$value' in interface '$interface' with settings '$setting'"
                );
            } else {
                $this->string($html)->notContains(
                    $value,
                    "Ticket form must not contains '$value' (expected '$expected') in interface '$interface' with settings '$setting'"
                );
            }
        }

       // Reset session
        $_SESSION['glpiactiveprofile']['interface'] = $old_interface;
    }

    public function testDefaultContractConfig()
    {
        $this->login();

        $entity = new \Entity();
        $ticket = new Ticket();
        $ticket_contract = new Ticket_Contract();
        $contract = new Contract();

       // Create test entity
        $entities_id = $entity->add([
            'name'        => 'Test',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]);
        $this->integer($entities_id)->isGreaterThan(0);

       // Create test contracts
        $contracts_id_1 = $contract->add([
            'name'        => 'test1',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'renewal'    => Contract::RENEWAL_TACIT,
        ]);
        $this->integer($contracts_id_1)->isGreaterThan(0);

        $contracts_id_2 = $contract->add([
            'name'        => 'test2',
            'entities_id' => $entities_id,
            'renewal'    => Contract::RENEWAL_TACIT,
        ]);
        $this->integer($contracts_id_2)->isGreaterThan(0);

       // Test 1: no config
        $tickets_id = $ticket->add([
            'name'        => 'Test ticket 1',
            'content'     => 'Test ticket 1',
            'entities_id' => $entities_id,
        ]);
        $this->integer($tickets_id)->isGreaterThan(0);

       // Case 1: no entity specified, no contract expected
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();

        $links = $ticket_contract::getListForItem($ticket);
        $this->integer(count($links))->isEqualTo(0);

       // Test 2: Use specific contract
        $res = $entity->update([
            'id' => $entities_id,
            'contracts_id_default' => $contracts_id_1,
        ]);
        $this->boolean($res)->isTrue();

       // Case 1: no contract specified, specific default expected
        $tickets_id = $ticket->add([
            'name'        => 'Test ticket 1',
            'content'     => 'Test ticket 1',
            'entities_id' => $entities_id,
        ]);
        $this->integer($tickets_id)->isGreaterThan(0);
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();

        $links = $ticket_contract::getListForItem($ticket);
        $this->integer(count($links))->isEqualTo(1);
        $link = $links->current();
        $this->integer($link['id'])->isEqualTo($contracts_id_1);

       // Case 2: contract specified, should not change
        $tickets_id = $ticket->add([
            'name'          => 'Test ticket 1',
            'content'       => 'Test ticket 1',
            'entities_id'   => $entities_id,
            '_contracts_id' => $contracts_id_2,
        ]);
        $this->integer($tickets_id)->isGreaterThan(0);
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();

        $links = $ticket_contract::getListForItem($ticket);
        $this->integer(count($links))->isEqualTo(1);
        $link = $links->current();
        $this->integer($link['id'])->isEqualTo($contracts_id_2);

       // Test 3: Use contract in current entity
        $res = $entity->update([
            'id' => $entities_id,
            'contracts_id_default' => '-1',
        ]);
        $this->boolean($res)->isTrue();

       // Case 1: root entity, expect no contract (no config for this entity)
        $tickets_id_2 = $ticket->add([
            'name'        => 'Test ticket 1',
            'content'     => 'Test ticket 1',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]);
        $this->integer($tickets_id_2)->isGreaterThan(0);
        $this->boolean($ticket->getFromDB($tickets_id_2))->isTrue();

        $links = $ticket_contract::getListForItem($ticket);
        $this->integer(count($links))->isEqualTo(0);

       // Case 2: sub entity, expect contract 2
        $tickets_id = $ticket->add([
            'name'        => 'Test ticket 1',
            'content'     => 'Test ticket 1',
            'entities_id' => $entities_id,
        ]);
        $this->integer($tickets_id)->isGreaterThan(0);
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();

        $links = $ticket_contract::getListForItem($ticket);
        $this->integer(count($links))->isEqualTo(1);
        $link = $links->current();
        $this->integer($link['id'])->isEqualTo($contracts_id_2);

       // Case 3: contract specified, should not change
        $tickets_id = $ticket->add([
            'name'          => 'Test ticket 1',
            'content'       => 'Test ticket 1',
            'entities_id'   => $entities_id,
            '_contracts_id' => $contracts_id_1,
        ]);
        $this->integer($tickets_id)->isGreaterThan(0);
        $this->boolean($ticket->getFromDB($tickets_id))->isTrue();

        $links = $ticket_contract::getListForItem($ticket);
        $this->integer(count($links))->isEqualTo(1);
        $link = $links->current();
        $this->integer($link['id'])->isEqualTo($contracts_id_1);
    }


    public function testMultipleClones()
    {
        $this->login();

        $this->createItems('Entity', [
            [
                'name'        => 'test clone entity',
                'entities_id' => 0,
            ]
        ]);

        // Check that no clones exists
        $entity = new \Entity();
        $res = $entity->find(['name' => ['LIKE', 'test clone entity %']]);
        $this->array($res)->hasSize(0);

        // Clone multiple times
        $entity = getItemByTypeName('Entity', 'test clone entity', false);
        $this->boolean($entity->cloneMultiple(4))->isTrue();

        // Check that 4 clones were created
        $entity = new \Entity();
        $res = $entity->find(['name' => ['LIKE', 'test clone entity %']]);
        $this->array($res)->hasSize(4);

        // Try to read each clones
        $this->integer(getItemByTypeName('Entity', 'test clone entity (copy)', true))->isGreaterThan(0);
        $this->integer(getItemByTypeName('Entity', 'test clone entity (copy 2)', true))->isGreaterThan(0);
        $this->integer(getItemByTypeName('Entity', 'test clone entity (copy 3)', true))->isGreaterThan(0);
        $this->integer(getItemByTypeName('Entity', 'test clone entity (copy 4)', true))->isGreaterThan(0);
    }

    public function testRename()
    {
        $this->login();

        $old_entity = $this->createItem(
            'Entity',
            [
                'name'        => 'Existing entity',
                'entities_id' => 0,
            ]
        );

        $new_entity = $this->createItem(
            'Entity',
            [
                'name'        => 'New entity',
                'entities_id' => 0,
            ]
        );

        $entities_id = $new_entity->fields['id'];
        $this->integer($entities_id)->isGreaterThan(0);

        //try to rename on existing name
        $this->boolean(
            $new_entity->update([
                'id'   => $entities_id,
                'name' => 'Existing entity',
            ])
        )->isTrue();
        $this->hasSessionMessages(ERROR, ['An entity with that name already exists at the same level.']);

        $this->boolean($new_entity->getFromDB($entities_id))->isTrue();
        $this->string($new_entity->fields['name'])->isEqualTo('New entity');
    }

    protected function entityTreeProvider(): iterable
    {
        $entity_test_root    = getItemByTypeName('Entity', '_test_root_entity');
        $entity_test_child_1 = getItemByTypeName('Entity', '_test_child_1');
        $entity_test_child_2 = getItemByTypeName('Entity', '_test_child_2');

        yield [
            'entity_id' => 0,
            'result'    => [
                0 => [
                    'name' => 'Root entity',
                    'tree' => [
                        $entity_test_root->getID() => [
                            'name' => $entity_test_root->fields['name'],
                            'tree' => [
                                $entity_test_child_1->getID() => [
                                    'name' => $entity_test_child_1->fields['name'],
                                    'tree' => [],
                                ],
                                $entity_test_child_2->getID() => [
                                    'name' => $entity_test_child_2->fields['name'],
                                    'tree' => [],
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        yield [
            'entity_id' => $entity_test_root->getID(),
            'result'    => [
                $entity_test_root->getID() => [
                    'name' => \Entity::sanitizeSeparatorInCompletename($entity_test_root->fields['completename']),
                    'tree' => [
                        $entity_test_child_1->getID() => [
                            'name' => $entity_test_child_1->fields['name'],
                            'tree' => [],
                        ],
                        $entity_test_child_2->getID() => [
                            'name' => $entity_test_child_2->fields['name'],
                            'tree' => [],
                        ]
                    ]
                ]
            ]
        ];

        yield [
            'entity_id' => $entity_test_child_1->getID(),
            'result'    => [
                $entity_test_child_1->getID() => [
                    'name' => \Entity::sanitizeSeparatorInCompletename($entity_test_child_1->fields['completename']),
                    'tree' => [
                    ]
                ]
            ]
        ];

        yield [
            'entity_id' => $entity_test_child_2->getID(),
            'result'    => [
                $entity_test_child_2->getID() => [
                    'name' => \Entity::sanitizeSeparatorInCompletename($entity_test_child_2->fields['completename']),
                    'tree' => [
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider entityTreeProvider
     */
    public function testGetEntityTree(int $entity_id, array $result): void
    {
        $this->login();

        $entity = $this->newTestedInstance();
        $this->array($this->callPrivateMethod($entity, 'getEntityTree', $entity_id))->isEqualTo($result);
    }

    public function testGetEntitySelectorTree(): void
    {
        /** @var \DBmysql $DB */
        global $DB;

        $this->login();

        $fn_get_current_entities = static function () use ($DB) {
            return iterator_to_array($DB->request([
                'SELECT' => ['id', 'name', 'entities_id'],
                'FROM' => 'glpi_entities',
            ]));
        };

        $fn_find_entities_in_selector = static function ($selector, $entities, $parent_id = 0, &$found = []) use (&$fn_find_entities_in_selector) {
            foreach ($selector as $item) {
                // extract entity name from the first <a> element inside the 'title' property
                $matches = [];
                preg_match('/>(.+)<\/a>/', $item['title'], $matches);
                $entity_name = $matches[1];
                foreach ($entities as $child) {
                    if ($child['name'] === $entity_name && $child['entities_id'] === $parent_id) {
                        $found[] = $child['id'];
                    }
                }
                if ($item['folder'] ?? false) {
                    // Extract item id from the number at the end of the first <a> element's href inside the 'title' property
                    $fn_find_entities_in_selector($item['children'], $entities, (int) $item['key'], $found);
                }
            }
        };

        $entities = $fn_get_current_entities();
        $this->array($entities)->size->isGreaterThan(0);
        $selector = \Entity::getEntitySelectorTree();
        $found = [];
        $fn_find_entities_in_selector($selector, $entities, null, $found);
        $this->array($found)->size->isEqualTo(count($entities));

        // Create a new entity
        $entity = new \Entity();
        $this->integer($entities_id = $entity_id = $entity->add([
            'name' => __FUNCTION__ . '1',
            'entities_id' => getItemByTypeName('Entity', '_test_child_2', true)
        ]))->isGreaterThan(0);
        $found = [];
        $entities = $fn_get_current_entities();
        $fn_find_entities_in_selector(\Entity::getEntitySelectorTree(), $entities, null, $found);
        $this->array($found)->size->isEqualTo(count($entities));

        // Delete the entity
        $this->boolean($entity->delete(['id' => $entity_id]))->isTrue();
        $found = [];
        $entities = $fn_get_current_entities();
        $fn_find_entities_in_selector(\Entity::getEntitySelectorTree(), $entities, null, $found);
        $this->array($found)->size->isEqualTo(count($entities));
    }
}
