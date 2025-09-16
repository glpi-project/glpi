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

use CommonITILActor;
use Contract;
use DbTestCase;
use Entity;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Controller\ServiceCatalog\IndexController;
use Glpi\DBAL\QueryExpression;
use Glpi\Form\Category;
use Glpi\Form\Form;
use Glpi\Helpdesk\HomePageTabs;
use ITILFollowup;
use ITILSolution;
use NotificationTarget;
use NotificationTargetTicket;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Profile_User;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Ticket;
use Ticket_Contract;
use Ticket_User;

/* Test for inc/entity.class.php */

class EntityTest extends DbTestCase
{
    public function testSonsAncestors()
    {
        $ent0 = getItemByTypeName('Entity', '_test_root_entity');
        $this->assertSame(
            'Root entity > _test_root_entity',
            $ent0->getField('completename')
        );

        $ent1 = getItemByTypeName('Entity', '_test_child_1');
        $this->assertSame(
            'Root entity > _test_root_entity > _test_child_1',
            $ent1->getField('completename')
        );

        $ent2 = getItemByTypeName('Entity', '_test_child_2');
        $this->assertSame(
            'Root entity > _test_root_entity > _test_child_2',
            $ent2->getField('completename')
        );

        $ent3 = getItemByTypeName('Entity', '_test_child_3');

        $this->assertSame(
            [0],
            array_keys(getAncestorsOf('glpi_entities', $ent0->getID()))
        );
        $this->assertSame(
            [0],
            array_values(getAncestorsOf('glpi_entities', $ent0->getID()))
        );
        $this->assertEquals(
            [$ent0->getID(), $ent1->getID(), $ent2->getID(), $ent3->getID()],
            array_keys(getSonsOf('glpi_entities', $ent0->getID()))
        );
        $this->assertSame(
            [$ent0->getID(), $ent1->getID(), $ent2->getID(), $ent3->getID()],
            array_values(getSonsOf('glpi_entities', $ent0->getID()))
        );

        $this->assertEquals(
            [0, $ent0->getID()],
            array_keys(getAncestorsOf('glpi_entities', $ent1->getID()))
        );
        $this->assertEquals(
            [0, $ent0->getID()],
            array_values(getAncestorsOf('glpi_entities', $ent1->getID()))
        );
        $this->assertEquals(
            [$ent1->getID()],
            array_keys(getSonsOf('glpi_entities', $ent1->getID()))
        );
        $this->assertEquals(
            [$ent1->getID()],
            array_values(getSonsOf('glpi_entities', $ent1->getID()))
        );

        $this->assertEquals(
            [0, $ent0->getID()],
            array_keys(getAncestorsOf('glpi_entities', $ent2->getID()))
        );
        $this->assertEquals(
            [0, $ent0->getID()],
            array_values(getAncestorsOf('glpi_entities', $ent2->getID()))
        );
        $this->assertEquals(
            [$ent2->getID()],
            array_keys(getSonsOf('glpi_entities', $ent2->getID()))
        );
        $this->assertEquals(
            [$ent2->getID()],
            array_values(getSonsOf('glpi_entities', $ent2->getID()))
        );
    }

    public function testPrepareInputForAdd()
    {
        $this->login();
        $entity = new Entity();

        $this->assertFalse(
            $entity->prepareInputForAdd([
                'name' => '',
            ])
        );
        $this->hasSessionMessages(ERROR, ["You can&#039;t add an entity without name"]);

        $this->assertFalse(
            $entity->prepareInputForAdd([
                'anykey' => 'anyvalue',
            ])
        );
        $this->hasSessionMessages(ERROR, ["You can&#039;t add an entity without name"]);

        $prepared = $entity->prepareInputForAdd([
            'name' => 'entname',
        ]);
        $this->assertSame('entname', $prepared['name']);
        $this->assertSame('entname', $prepared['completename']);
        $this->assertSame(1, $prepared['level']);
        $this->assertSame(0, $prepared['entities_id']);
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

        $entity = new Entity();
        $new_id = (int) $entity->add([
            'name'         => 'Sub child entity',
            'entities_id'  => $ent1,
        ]);
        $this->assertGreaterThan(0, $new_id);
        $ackey_new_id = 'ancestors_cache_glpi_entities_' . $new_id;

        $expected = [0 => 0, $ent0 => $ent0, $ent1 => $ent1];
        if ($cache === true) {
            $this->assertSame($expected, $GLPI_CACHE->get($ackey_new_id));
        }

        $ancestors = getAncestorsOf('glpi_entities', $new_id);
        $this->assertSame($expected, $ancestors);

        if ($cache === true && $hit === false) {
            $this->assertSame($expected, $GLPI_CACHE->get($ackey_new_id));
        }

        $expected = [$ent1 => $ent1, $new_id => $new_id];

        $sons = getSonsOf('glpi_entities', $ent1);
        $this->assertSame($expected, $sons);

        if ($cache === true && $hit === false) {
            $this->assertSame($expected, $GLPI_CACHE->get($sckey_ent1));
        }

        //change parent entity
        $this->assertTrue(
            $entity->update([
                'id'           => $new_id,
                'entities_id'  => $ent2,
            ])
        );

        $expected = [0 => 0, $ent0 => $ent0, $ent2 => $ent2];
        if ($cache === true) {
            $this->assertSame($expected, $GLPI_CACHE->get($ackey_new_id));
        }

        $ancestors = getAncestorsOf('glpi_entities', $new_id);
        $this->assertSame($expected, $ancestors);

        if ($cache === true && $hit === false) {
            $this->assertSame($expected, $GLPI_CACHE->get($ackey_new_id));
        }

        $expected = [$ent1 => $ent1];
        $sons = getSonsOf('glpi_entities', $ent1);
        $this->assertSame($expected, $sons);

        if ($cache === true && $hit === false) {
            $this->assertSame($expected, $GLPI_CACHE->get($sckey_ent1));
        }

        $expected = [$ent2 => $ent2, $new_id => $new_id];
        $sons = getSonsOf('glpi_entities', $ent2);
        $this->assertSame($expected, $sons);

        if ($cache === true && $hit === false) {
            $this->assertSame($expected, $GLPI_CACHE->get($sckey_ent2));
        }

        //clean new entity
        $this->assertTrue(
            $entity->delete(['id' => $new_id], true)
        );
    }

    private function checkParentsSonsAreReset()
    {
        $ent0 = getItemByTypeName('Entity', '_test_root_entity', true);
        $ent1 = getItemByTypeName('Entity', '_test_child_1', true);
        $ent2 = getItemByTypeName('Entity', '_test_child_2', true);

        $expected = [0 => 0, $ent0 => $ent0];
        $ancestors = getAncestorsOf('glpi_entities', $ent1);
        $this->assertSame($expected, $ancestors);

        $ancestors = getAncestorsOf('glpi_entities', $ent2);
        $this->assertSame($expected, $ancestors);

        $expected = [$ent1 => $ent1];
        $sons = getSonsOf('glpi_entities', $ent1);
        $this->assertSame($expected, $sons);

        $expected = [$ent2 => $ent2];
        $sons = getSonsOf('glpi_entities', $ent2);
        $this->assertSame($expected, $sons);
    }

    public function testChangeEntityParent()
    {
        global $DB;
        //ensure db cache are unset
        $DB->update(
            'glpi_entities',
            [
                'ancestors_cache' => null,
                'sons_cache'      => null,
            ],
            [new QueryExpression('true')]
        );
        $this->runChangeEntityParent();
        //reset cache (checking for expected defaults) then run a second time: db cache must be set
        $this->checkParentsSonsAreReset();
        $this->runChangeEntityParent();
    }

    #[Group('cache')]
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

    public function testMoveParentEntity(): void
    {
        $this->doTestMoveParentEntity(false);
    }

    #[Group('cache')]
    public function testMoveParentEntityCached(): void
    {
        $this->doTestMoveParentEntity(true);
    }

    private function doTestMoveParentEntity(bool $cache): void
    {
        $this->login();

        $entities = [];

        // Create the test entities
        $parent_id = 0;
        for ($i = 0; $i < 5; $i++) {
            $entity = $this->createItem(
                Entity::class,
                [
                    'name'         => sprintf('Level %s entity', $i + 1),
                    'entities_id'  => $parent_id,
                ]
            );
            $entities[$i] = $entity;
            $parent_id = $entity->getID();
        }

        // Validate that sons/ancestors are correctly set
        $this->checkEntitiesTree($entities, $cache);

        // Move the "Level 3 entity"
        $entities[2]->update(['id' => $entities[2]->getID(), 'entities_id' => 0]);

        // Validate that sons/ancestors are correctly updated
        $this->checkEntitiesTree(\array_slice($entities, 2), $cache);
        $this->checkEntitiesTree(\array_slice($entities, 0, 2), $cache);
    }

    /**
     * Check that the entities tree is correctly returned by `getAncestorsOf`/`getSonsOf` methods.
     *
     * @param array $entities   The entities list. Each item is supposed to be the son of the previous item, and the
     *                          first item is supposed to be a son of the root entity.
     * @param bool $cache
     */
    private function checkEntitiesTree(array $entities, bool $cache): void
    {
        global $GLPI_CACHE;

        foreach ($entities as $key => $entity) {
            $expected_sons_ids = \array_map(
                static fn(Entity $ent) => $ent->getID(),
                \array_slice($entities, $key)
            );
            $expected_sons = \array_combine($expected_sons_ids, $expected_sons_ids);

            $expected_ancestors_ids = array_merge(
                [0], // root entity
                \array_map(
                    static fn(Entity $ent) => $ent->getID(),
                    \array_slice($entities, 0, $key)
                )
            );
            $expected_ancestors = \array_combine($expected_ancestors_ids, $expected_ancestors_ids);

            $this->assertSame($expected_ancestors, getAncestorsOf('glpi_entities', $entity->getID()));
            if ($cache === true) {
                $this->assertSame($expected_ancestors, $GLPI_CACHE->get('ancestors_cache_glpi_entities_' . $entity->getID()));
            }

            $this->assertSame($expected_sons, getSonsOf('glpi_entities', $entity->getID()));
            if ($cache === true) {
                $this->assertSame($expected_sons, $GLPI_CACHE->get('sons_cache_glpi_entities_' . $entity->getID()));
            }
        }
    }

    public function testInheritGeolocation()
    {
        $this->login();
        $ent0 = getItemByTypeName('Entity', '_test_root_entity', true);
        $ent1 = new Entity();
        $ent1_id = $ent1->add([
            'entities_id'  => $ent0,
            'name'         => 'inherit_geo_test_parent',
            'latitude'     => '48.8566',
            'longitude'    => '2.3522',
            'altitude'     => '115',
        ]);
        $this->assertGreaterThan(0, (int) $ent1_id);
        $ent2 = new Entity();
        $ent2_id = $ent2->add([
            'entities_id'  => $ent1_id,
            'name'         => 'inherit_geo_test_child',
        ]);
        $this->assertGreaterThan(0, (int) $ent2_id);
        $this->assertEquals($ent1->fields['latitude'], $ent2->fields['latitude']);
        $this->assertEquals($ent1->fields['longitude'], $ent2->fields['longitude']);
        $this->assertEquals($ent1->fields['altitude'], $ent2->fields['altitude']);

        // Make sure we don't overwrite data a user sets
        $ent3 = new Entity();
        $ent3_id = $ent3->add([
            'entities_id'  => $ent1_id,
            'name'         => 'inherit_geo_test_child2',
            'latitude'     => '41.3851',
            'longitude'    => '2.1734',
            'altitude'     => '39',
        ]);
        $this->assertGreaterThan(0, (int) $ent3_id);
        $this->assertEquals('41.3851', $ent3->fields['latitude']);
        $this->assertEquals('2.1734', $ent3->fields['longitude']);
        $this->assertEquals('39', $ent3->fields['altitude']);
    }

    public function testDeleteEntity()
    {
        $this->login();
        $root_id = getItemByTypeName('Entity', '_test_root_entity', true);

        $entity = new Entity();
        $entity_id = (int) $entity->add(
            [
                'name'         => 'Test entity',
                'entities_id'  => $root_id,
            ]
        );
        $this->assertGreaterThan(0, $entity_id);

        //make sure parent entity cannot be removed
        $this->assertFalse($entity->delete(['id' => $root_id]));
        $this->hasSessionMessages(ERROR, ["You cannot delete an entity which contains sub-entities."]);

        $user_id = getItemByTypeName('User', 'normal', true);
        $profile_id = getItemByTypeName('Profile', 'Admin', true);

        $profile_user = new Profile_User();
        $profile_user_id = (int) $profile_user->add(
            [
                'entities_id' => $entity_id,
                'profiles_id' => $profile_id,
                'users_id'    => $user_id,
            ]
        );
        $this->assertGreaterThan(0, $profile_user_id);

        // Profile_User exists
        $this->assertTrue($profile_user->getFromDB($profile_user_id));

        $this->assertTrue($entity->delete(['id' => $entity_id]));

        // Profile_User has been deleted when entity has been deleted
        $this->assertFalse($profile_user->getFromDB($profile_user_id));
    }

    public static function getUsedConfigProvider(): iterable
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

    #[DataProvider('getUsedConfigProvider')]
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

        $entity = new Entity();
        $this->assertTrue($entity->update(['id' => $root_id] + $root_values));
        $this->assertTrue($entity->update(['id' => $child_id] + $child_values));
        $this->assertTrue($entity->update(['id' => $grandchild_id] + $grandchild_values));

        $this->assertEquals($expected_result, call_user_func_array([Entity::class, 'getUsedConfig'], $params));
    }


    public static function customCssProvider()
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
                'child_enable_custom_css' => Entity::CONFIG_PARENT,
                'child_custom_css_code'   => '',
                'expected'                => '',
            ],
            [
                // Do not output custom CSS from parent if empty
                'entity_id'               => $child_id,
                'root_enable_custom_css'  => 1,
                'root_custom_css_code'    => '',
                'child_enable_custom_css' => Entity::CONFIG_PARENT,
                'child_custom_css_code'   => '',
                'expected'                => '',
            ],
            [
                // Output custom CSS from parent
                'entity_id'               => $child_id,
                'root_enable_custom_css'  => 1,
                'root_custom_css_code'    => '.link::before { content: "test"; }',
                'child_enable_custom_css' => Entity::CONFIG_PARENT,
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

    #[DataProvider('customCssProvider')]
    public function testGetCustomCssTag(
        int $entity_id,
        int $root_enable_custom_css,
        string $root_custom_css_code,
        int $child_enable_custom_css,
        string $child_custom_css_code,
        string $expected
    ): void {
        $this->login();

        $entity = new Entity();

        // Define configuration values
        $update = $entity->update(
            [
                'id'                => getItemByTypeName('Entity', 'Root entity', true),
                'enable_custom_css' => $root_enable_custom_css,
                'custom_css_code'   => $root_custom_css_code,
            ]
        );
        $this->assertTrue($update);
        $update = $entity->update(
            [
                'id'                => getItemByTypeName('Entity', '_test_child_1', true),
                'enable_custom_css' => $child_enable_custom_css,
                'custom_css_code'   => $child_custom_css_code,
            ]
        );
        $this->assertTrue($update);

        // Validate method result
        $this->assertTrue($entity->getFromDB($entity_id));
        $this->assertSame($expected, $entity->getCustomCssTag());
    }

    public static function anonymizeSettingProvider(): array
    {
        return [
            [
                'interface' => 'central',
                'setting'   => Entity::ANONYMIZE_DISABLED,
                'expected'  => 'test_anon_user',
            ],
            [
                'interface' => 'helpdesk',
                'setting'   => Entity::ANONYMIZE_DISABLED,
                'expected'  => 'test_anon_user',
            ],
            [
                'interface' => 'central',
                'setting'   => Entity::ANONYMIZE_USE_GENERIC,
                'expected'  => 'test_anon_user',
            ],
            [
                'interface' => 'helpdesk',
                'setting'   => Entity::ANONYMIZE_USE_GENERIC,
                'expected'  => "Helpdesk user",
            ],
            [
                'interface' => 'central',
                'setting'   => Entity::ANONYMIZE_USE_NICKNAME,
                'expected'  => 'test_anon_user',
                'user_nick' => 'user_nick_6436345654',
            ],
            [
                'interface' => 'helpdesk',
                'setting'   => Entity::ANONYMIZE_USE_NICKNAME,
                'expected'  => 'user_nick_6436345654',
                'user_nick' => 'user_nick_6436345654',
            ],
            [
                'interface' => 'central',
                'setting'   => Entity::ANONYMIZE_USE_GENERIC_USER,
                'expected'  => 'test_anon_user',
            ],
            [
                'interface' => 'helpdesk',
                'setting'   => Entity::ANONYMIZE_USE_GENERIC_USER,
                'expected'  => "Helpdesk user",
            ],
            [
                'interface' => 'central',
                'setting'   => Entity::ANONYMIZE_USE_NICKNAME_USER,
                'expected'  => 'test_anon_user',
                'user_nick' => 'user_nick_6436345654',
            ],
            [
                'interface' => 'helpdesk',
                'setting'   => Entity::ANONYMIZE_USE_NICKNAME_USER,
                'expected'  => 'user_nick_6436345654',
                'user_nick' => 'user_nick_6436345654',
            ],
            [
                'interface' => 'central',
                'setting'   => Entity::ANONYMIZE_USE_GENERIC_GROUP,
                'expected'  => 'test_anon_user',
            ],
            [
                'interface' => 'helpdesk',
                'setting'   => Entity::ANONYMIZE_USE_GENERIC_GROUP,
                'expected'  => 'test_anon_user',
            ],
        ];
    }

    #[DataProvider('anonymizeSettingProvider')]
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
        $this->assertTrue($update);

        // create a user for this test (avoid using current logged user as we don't anonymize him)
        $user_obj = new \User();
        $user_obj->add([
            'name'     => 'test_anon_user',
            'password' => 'test_anon_user',
        ]);

        // Set user nickname
        $user = getItemByTypeName('User', 'test_anon_user');

        if ($user_nick == "" && $user->fields['nickname'] == null) {
            // Special case, glpi wont update null to "" so we need to set
            // another value first
            $update = $user->update([
                'id'       => $user->getID(),
                'nickname' => 'TMP',
            ]);
            $this->assertTrue($update);
            $this->assertTrue($user->getFromDB($user->getID()));
            $this->assertEquals('TMP', $user->fields['nickname']);
        }

        $update = $user->update([
            'id'       => $user->getID(),
            'nickname' => $user_nick,
        ]);
        $this->assertTrue($update);
        $this->assertTrue($user->getFromDB($user->getID()));
        $this->assertEquals($user_nick, $user->fields['nickname']);

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
        $this->assertGreaterThan(0, $tickets_id);

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
        $this->assertEquals(
            [
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
            ],
            iterator_to_array($ticket_users)
        );

        // Add followup to test ticket
        $fup = new ITILFollowup();
        $fup_id = $fup->add([
            'content' => 'test',
            'users_id' => getItemByTypeName('User', 'test_anon_user', true),
            'users_id_editor' => getItemByTypeName('User', 'test_anon_user', true),
            'itemtype' => 'Ticket',
            'items_id' => $tickets_id,
        ]);
        $this->assertGreaterThan(0, $fup_id);

        // Add solution to test ticket
        $solution = new ITILSolution();
        $solutions_id = $solution->add([
            'content' => 'test',
            'users_id' => getItemByTypeName('User', 'test_anon_user', true),
            'users_id_editor' => getItemByTypeName('User', 'test_anon_user', true),
            'itemtype' => 'Ticket',
            'items_id' => $tickets_id,
        ]);
        $this->assertGreaterThan(0, $solutions_id);

        // Save and replace session data
        $old_interface = $_SESSION['glpiactiveprofile']['interface'];
        $_SESSION['glpiactiveprofile']['interface'] = $interface;

        // Case 1: removed (test values recovered from CommonITILObject::showUsersAssociated())

        // Case 2: test values recovered from CommonITILObject:::showShort()
        $entries = Ticket::getDatatableEntries([
            [
                'item_id' => $tickets_id,
                'id' => $tickets_id,
                'itemtype' => 'Ticket',
            ],
        ]);
        $entry = $entries[0];

        $entry_contents = array_reduce(array_keys($entry), static function ($carry, $key) use ($entry) {
            if (is_array($entry[$key]) && array_key_exists('content', $entry[$key])) {
                return $carry . $entry[$key]['content'];
            }
            return $carry . $entry[$key];
        }, '');
        foreach ($possible_values as $value) {
            if ($value === $expected) {
                $this->assertStringContainsString(
                    $value,
                    $entry_contents,
                    "Ticket getDatatableEntries must contains '$value' in interface '$interface' with settings '$setting'"
                );
            } else {
                $this->assertStringNotContainsString(
                    $value,
                    $entry_contents,
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
            ],
        ]);
        foreach ($notif_data['followups'] as $n_fup) {
            if ($n_fup['##followup.author##'] !== null) {
                foreach ($possible_values as $value) {
                    if ($value == $expected) {
                        $this->assertStringContainsString(
                            $value,
                            $n_fup['##followup.author##']
                        );
                    } else {
                        $this->assertStringNotContainsString(
                            $value,
                            $n_fup['##followup.author##']
                        );
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
                $this->assertStringContainsString(
                    $value,
                    $html,
                    "Ticket form must contains '$value' in interface '$interface' with settings '$setting'"
                );
            } else {
                $this->assertStringNotContainsString(
                    $value,
                    $html,
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

        $entity = new Entity();
        $ticket = new Ticket();
        $ticket_contract = new Ticket_Contract();
        $contract = new Contract();

        // Create test entity
        $entities_id = $entity->add([
            'name'        => 'Test',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]);
        $this->assertGreaterThan(0, $entities_id);

        // Create test contracts
        $contracts_id_1 = $contract->add([
            'name'        => 'test1',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
            'renewal'    => Contract::RENEWAL_TACIT,
        ]);
        $this->assertGreaterThan(0, $contracts_id_1);

        $contracts_id_2 = $contract->add([
            'name'        => 'test2',
            'entities_id' => $entities_id,
            'renewal'    => Contract::RENEWAL_TACIT,
        ]);
        $this->assertGreaterThan(0, $contracts_id_2);

        // Test 1: no config
        $tickets_id = $ticket->add([
            'name'        => 'Test ticket 1',
            'content'     => 'Test ticket 1',
            'entities_id' => $entities_id,
        ]);
        $this->assertGreaterThan(0, $tickets_id);

        // Case 1: no entity specified, no contract expected
        $this->assertTrue($ticket->getFromDB($tickets_id));

        $links = $ticket_contract::getListForItem($ticket);
        $this->assertEquals(0, count($links));

        // Test 2: Use specific contract
        $res = $entity->update([
            'id' => $entities_id,
            'contracts_id_default' => $contracts_id_1,
        ]);
        $this->assertTrue($res);

        // Case 1: no contract specified, specific default expected
        $tickets_id = $ticket->add([
            'name'        => 'Test ticket 1',
            'content'     => 'Test ticket 1',
            'entities_id' => $entities_id,
        ]);
        $this->assertGreaterThan(0, $tickets_id);
        $this->assertTrue($ticket->getFromDB($tickets_id));

        $links = $ticket_contract::getListForItem($ticket);
        $this->assertEquals(1, count($links));
        $link = $links->current();
        $this->assertEquals($contracts_id_1, $link['id']);

        // Case 2: contract specified, should not change
        $tickets_id = $ticket->add([
            'name'          => 'Test ticket 1',
            'content'       => 'Test ticket 1',
            'entities_id'   => $entities_id,
            '_contracts_id' => $contracts_id_2,
        ]);
        $this->assertGreaterThan(0, $tickets_id);
        $this->assertTrue($ticket->getFromDB($tickets_id));

        $links = $ticket_contract::getListForItem($ticket);
        $this->assertEquals(1, count($links));
        $link = $links->current();
        $this->assertEquals($contracts_id_2, $link['id']);

        // Test 3: Use contract in current entity
        $res = $entity->update([
            'id' => $entities_id,
            'contracts_id_default' => '-1',
        ]);
        $this->assertTrue($res);

        // Case 1: root entity, expect no contract (no config for this entity)
        $tickets_id_2 = $ticket->add([
            'name'        => 'Test ticket 1',
            'content'     => 'Test ticket 1',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]);
        $this->assertGreaterThan(0, $tickets_id_2);
        $this->assertTrue($ticket->getFromDB($tickets_id_2));

        $links = $ticket_contract::getListForItem($ticket);
        $this->assertEquals(0, count($links));

        // Case 2: sub entity, expect contract 2
        $tickets_id = $ticket->add([
            'name'        => 'Test ticket 1',
            'content'     => 'Test ticket 1',
            'entities_id' => $entities_id,
        ]);
        $this->assertGreaterThan(0, $tickets_id);
        $this->assertTrue($ticket->getFromDB($tickets_id));

        $links = $ticket_contract::getListForItem($ticket);
        $this->assertEquals(1, count($links));
        $link = $links->current();
        $this->assertEquals($contracts_id_2, $link['id']);

        // Case 3: contract specified, should not change
        $tickets_id = $ticket->add([
            'name'          => 'Test ticket 1',
            'content'       => 'Test ticket 1',
            'entities_id'   => $entities_id,
            '_contracts_id' => $contracts_id_1,
        ]);
        $this->assertGreaterThan(0, $tickets_id);
        $this->assertTrue($ticket->getFromDB($tickets_id));

        $links = $ticket_contract::getListForItem($ticket);
        $this->assertEquals(1, count($links));
        $link = $links->current();
        $this->assertEquals($contracts_id_1, $link['id']);
    }


    public function testMultipleClones()
    {
        $this->login();

        $this->createItems('Entity', [
            [
                'name'        => 'test clone entity',
                'entities_id' => 0,
            ],
        ]);

        // Check that no clones exists
        $entity = new Entity();
        $res = $entity->find(['name' => ['LIKE', 'test clone entity %']]);
        $this->assertCount(0, $res);

        // Clone multiple times
        $entity = getItemByTypeName('Entity', 'test clone entity', false);
        $this->assertTrue($entity->cloneMultiple(4));

        // Check that 4 clones were created
        $entity = new Entity();
        $res = $entity->find(['name' => ['LIKE', 'test clone entity %']]);
        $this->assertCount(4, $res);

        // Try to read each clones
        $this->assertGreaterThan(0, getItemByTypeName('Entity', 'test clone entity (copy)', true));
        $this->assertGreaterThan(0, getItemByTypeName('Entity', 'test clone entity (copy 2)', true));
        $this->assertGreaterThan(0, getItemByTypeName('Entity', 'test clone entity (copy 3)', true));
        $this->assertGreaterThan(0, getItemByTypeName('Entity', 'test clone entity (copy 4)', true));
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
        $this->assertGreaterThan(0, $entities_id);

        //try to rename on existing name
        $this->assertTrue(
            $new_entity->update([
                'id'   => $entities_id,
                'name' => 'Existing entity',
            ])
        );
        $this->hasSessionMessages(ERROR, ['An entity with that name already exists at the same level.']);

        $this->assertTrue($new_entity->getFromDB($entities_id));
        $this->assertEquals('New entity', $new_entity->fields['name']);
    }

    /**
     * Regression test to ensure that renaming an entity doesn't force it to become a child of the root entity (ID 0)
     */
    public function testRenameDoesntChangeParent(): void
    {
        $this->login();
        $entity = $this->createItem('Entity', [
            'name'        => __FUNCTION__,
            'entities_id' => $this->getTestRootEntity(true),
        ]);
        $this->assertTrue($entity->update([
            'id'   => $entity->getID(),
            'name' => __FUNCTION__ . ' renamed',
        ]));
        $this->assertTrue($entity->getFromDB($entity->getID()));
        $this->assertEquals($this->getTestRootEntity(true), $entity->fields['entities_id']);
        $this->assertEquals(__FUNCTION__ . ' renamed', $entity->fields['name']);
    }

    public static function entityTreeProvider(): iterable
    {
        $e2e_test_root = getItemByTypeName('Entity', 'E2ETestEntity', true);
        $e2e_test_child1 = getItemByTypeName('Entity', 'E2ETestSubEntity1', true);
        $e2e_test_child2 = getItemByTypeName('Entity', 'E2ETestSubEntity2', true);
        $entity_test_root    = getItemByTypeName('Entity', '_test_root_entity');
        $entity_test_child_1 = getItemByTypeName('Entity', '_test_child_1');
        $entity_test_child_2 = getItemByTypeName('Entity', '_test_child_2');
        $entity_test_child_3 = getItemByTypeName('Entity', '_test_child_3');

        yield [
            'entity_id' => 0,
            'result'    => [
                0 => [
                    'name' => 'Root entity',
                    'tree' => [
                        $e2e_test_root => [
                            'name' => 'E2ETestEntity',
                            'tree' => [
                                $e2e_test_child1 => [
                                    'name' => 'E2ETestSubEntity1',
                                    'tree' => [],
                                ],
                                $e2e_test_child2 => [
                                    'name' => 'E2ETestSubEntity2',
                                    'tree' => [],
                                ],
                            ],
                        ],
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
                                ],
                                $entity_test_child_3->getID() => [
                                    'name' => $entity_test_child_3->fields['name'],
                                    'tree' => [],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        yield [
            'entity_id' => $entity_test_root->getID(),
            'result'    => [
                $entity_test_root->getID() => [
                    'name' => $entity_test_root->fields['completename'],
                    'tree' => [
                        $entity_test_child_1->getID() => [
                            'name' => $entity_test_child_1->fields['name'],
                            'tree' => [],
                        ],
                        $entity_test_child_2->getID() => [
                            'name' => $entity_test_child_2->fields['name'],
                            'tree' => [],
                        ],
                        $entity_test_child_3->getID() => [
                            'name' => $entity_test_child_3->fields['name'],
                            'tree' => [],
                        ],
                    ],
                ],
            ],
        ];

        yield [
            'entity_id' => $entity_test_child_1->getID(),
            'result'    => [
                $entity_test_child_1->getID() => [
                    'name' => $entity_test_child_1->fields['completename'],
                    'tree' => [
                    ],
                ],
            ],
        ];

        yield [
            'entity_id' => $entity_test_child_2->getID(),
            'result'    => [
                $entity_test_child_2->getID() => [
                    'name' => $entity_test_child_2->fields['completename'],
                    'tree' => [
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('entityTreeProvider')]
    public function testGetEntityTree(int $entity_id, array $result): void
    {
        $this->login();

        $entity = new Entity();
        $this->assertEquals($result, $this->callPrivateMethod($entity, 'getEntityTree', $entity_id));
    }

    public function testGetEntitySelectorTree(): void
    {
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
                // extract entity name from the first <button> element inside the 'title' property
                $matches = [];
                preg_match('/<button.*?>(.*?)<\/button>/s', $item['title'], $matches);
                $entity_name = trim($matches[1]);
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
        $this->assertGreaterThan(0, count($entities));
        $selector = Entity::getEntitySelectorTree();
        $found = [];
        $fn_find_entities_in_selector($selector, $entities, null, $found);
        $this->assertCount(count($entities), $found);

        // Create a new entity
        $entity = new Entity();
        $this->assertGreaterThan(
            0,
            $entities_id = $entity_id = $entity->add([
                'name' => __FUNCTION__ . '1',
                'entities_id' => getItemByTypeName('Entity', '_test_child_2', true),
            ])
        );
        $found = [];
        $entities = $fn_get_current_entities();
        $fn_find_entities_in_selector(Entity::getEntitySelectorTree(), $entities, null, $found);
        $this->assertCount(count($entities), $found);

        // Delete the entity
        $this->assertTrue($entity->delete(['id' => $entity_id]));
        $found = [];
        $entities = $fn_get_current_entities();
        $fn_find_entities_in_selector(Entity::getEntitySelectorTree(), $entities, null, $found);
        $this->assertCount(count($entities), $found);
    }

    public function testGetHelpdeskSceneIdIsInheritedByDefault(): void
    {
        $this->login();
        $root_entity = $this->getTestRootEntity();

        // Act: create a child entity without values for the scene fields
        $entity = $this->createItem(Entity::class, [
            'name'        => "Test entity",
            'entities_id' => $root_entity->getID(),
        ]);

        // Assert: scenes should be inherited
        $this->assertEquals(
            Entity::CONFIG_PARENT,
            $entity->fields['custom_helpdesk_home_scene_left'],
        );
        $this->assertEquals(
            Entity::CONFIG_PARENT,
            $entity->fields['custom_helpdesk_home_scene_right'],
        );
    }

    public function testGetHelpdeskSceneIdInheritedDefaultValue(): void
    {
        $this->login();
        $root_entity = $this->getTestRootEntity();

        // Arrange: create a child entity that inherit its parent values
        $entity = $this->createItem(Entity::class, [
            'name'        => "Test entity",
            'entities_id' => $root_entity->getID(),
            'custom_helpdesk_home_scene_left'  => Entity::CONFIG_PARENT,
            'custom_helpdesk_home_scene_right' => Entity::CONFIG_PARENT,
        ]);

        // Act: get the scenes id
        $left_scene_id = $entity->getHelpdeskSceneId(
            'custom_helpdesk_home_scene_left'
        );
        $right_scene_id = $entity->getHelpdeskSceneId(
            'custom_helpdesk_home_scene_right'
        );

        // Assert: the default illustration must be found
        $this->assertEquals($left_scene_id, Entity::DEFAULT_LEFT_SCENE);
        $this->assertEquals($right_scene_id, Entity::DEFAULT_RIGHT_SCENE);
    }

    public function testGetHelpdeskSceneIdInheritedCustomValue(): void
    {
        $this->login();
        $root_entity = $this->getTestRootEntity();

        // Arrange: create a child entity that inherit its parent values
        $entity = $this->createItem(Entity::class, [
            'name'        => "Test entity",
            'entities_id' => $root_entity->getID(),
            'custom_helpdesk_home_scene_left'  => Entity::CONFIG_PARENT,
            'custom_helpdesk_home_scene_right' => Entity::CONFIG_PARENT,
        ]);

        // Act: set custom values for the parent and get the scenes ids
        $this->updateItem(Entity::class, $root_entity->getID(), [
            'custom_helpdesk_home_scene_left' => 'test-left.png',
            'custom_helpdesk_home_scene_right' => 'test-right.png',
        ]);
        $left_scene_id = $entity->getHelpdeskSceneId(
            'custom_helpdesk_home_scene_left'
        );
        $right_scene_id = $entity->getHelpdeskSceneId(
            'custom_helpdesk_home_scene_right'
        );

        // Assert: the specific file names should be found.
        $this->assertEquals($left_scene_id, 'custom:test-left.png');
        $this->assertEquals($right_scene_id, 'custom:test-right.png');
    }

    public function testGetHelpdeskSceneIdDefaultValue(): void
    {
        $this->login();
        $root_entity = $this->getTestRootEntity();

        // Arrange: create a child entity with default illustrations
        $entity = $this->createItem(Entity::class, [
            'name'                             => "Test entity",
            'entities_id'                      => $root_entity->getID(),
            'custom_helpdesk_home_scene_left'  => '',
            'custom_helpdesk_home_scene_right' => '',
        ]);

        // Act: get the scenes id
        $left_scene_id = $entity->getHelpdeskSceneId(
            'custom_helpdesk_home_scene_left'
        );
        $right_scene_id = $entity->getHelpdeskSceneId(
            'custom_helpdesk_home_scene_right'
        );

        // Assert: the default illustration must be found
        $this->assertEquals($left_scene_id, Entity::DEFAULT_LEFT_SCENE);
        $this->assertEquals($right_scene_id, Entity::DEFAULT_RIGHT_SCENE);
    }

    public function testGetHelpdeskSceneIdCustomValue(): void
    {
        $this->login();
        $root_entity = $this->getTestRootEntity();

        // Arrange: create a child entity with custom illustrations
        $entity = $this->createItem(Entity::class, [
            'name'                             => "Test entity",
            'entities_id'                      => $root_entity->getID(),
            'custom_helpdesk_home_scene_left'  => 'test-left.png',
            'custom_helpdesk_home_scene_right' => 'test-right.png',
        ]);

        // Act: Get the scenes ids
        $left_scene_id = $entity->getHelpdeskSceneId(
            'custom_helpdesk_home_scene_left'
        );
        $right_scene_id = $entity->getHelpdeskSceneId(
            'custom_helpdesk_home_scene_right'
        );

        // Assert: the specific file names should be found.
        $this->assertEquals($left_scene_id, 'custom:test-left.png');
        $this->assertEquals($right_scene_id, 'custom:test-right.png');
    }

    public static function customHelpdeskTitleIsAppliedProvider(): iterable
    {
        yield 'One entity without config' => [
            'entities' => [
                ['name' => 'entity_a', 'config' => ''],
            ],
            'entity' => 'entity_a',
            'expected' => 'How can we help you?',
        ];

        yield 'Two entities without config' => [
            'entities' => [
                ['name' => 'entity_a', 'config' => ''],
                ['name' => 'entity_aa', 'parent' => 'entity_a', 'config' => -2],
            ],
            'entity' => 'entity_aa',
            'expected' => 'How can we help you?',
        ];

        yield 'Two entities with specific config on parent' => [
            'entities' => [
                ['name' => 'entity_a', 'config' => 'My value'],
                ['name' => 'entity_aa', 'parent' => 'entity_a', 'config' => -2],
            ],
            'entity' => 'entity_aa',
            'expected' => 'My value',
        ];

        yield 'Two entities with specific config on child' => [
            'entities' => [
                ['name' => 'entity_a', 'config' => 'My value'],
                ['name' => 'entity_aa', 'parent' => 'entity_a', 'config' => 'My other value'],
            ],
            'entity' => 'entity_aa',
            'expected' => 'My other value',
        ];
    }

    #[DataProvider('customHelpdeskTitleIsAppliedProvider')]
    public function testCustomHelpdeskTitleIsApplied(
        array $entities,
        string $entity,
        string $expected,
    ): void {
        $this->login();

        // Arrange: create requested entities
        $root = $this->getTestRootEntity(only_id: true);
        foreach ($entities as $to_create) {
            if (isset($to_create['parent'])) {
                $parent = getItemByTypeName(
                    Entity::class,
                    $to_create['parent'],
                    onlyid: true,
                );
            } else {
                $parent = $root;
            }

            $this->createItem(Entity::class, [
                'name'                       => $to_create['name'],
                'entities_id'                => $parent,
                'custom_helpdesk_home_title' => $to_create['config'],
            ]);
        }

        // Act: render home page
        $_SERVER['REQUEST_URI'] = ""; // Needed to avoid warnings
        $renderer = TemplateRenderer::getInstance();
        $content = $renderer->render('pages/helpdesk/index.html.twig', [
            // Important, entity to test
            'entity' => getItemByTypeName(Entity::class, $entity),
            // We don't case about these, set minimal required values
            'title' => '',
            'tiles' => [],
            'tabs'  => new HomePageTabs(),
            'password_alert' => null,
        ]);

        // Assert: compare the rendered title with the expected value
        $title = (new Crawler($content))
            ->filter('[data-testid=home-title]')
            ->text()
        ;
        $this->assertEquals($expected, $title);
    }

    public static function helpdeskSearchBarConfigIsAppliedProvider(): iterable
    {
        yield 'One entity without config' => [
            'entities' => [
                ['name' => 'entity_a', 'config' => -2],
            ],
            'entity' => 'entity_a',
            'should_be_enabled' => true, // Inherit from root
        ];

        yield 'Inherit enabled from parent' => [
            'entities' => [
                ['name' => 'entity_a', 'config' => 1],
                ['name' => 'entity_aa', 'parent' => 'entity_a', 'config' => -2],
            ],
            'entity' => 'entity_aa',
            'should_be_enabled' => true,
        ];

        yield 'Inherit disabled from parent' => [
            'entities' => [
                ['name' => 'entity_a', 'config' => 0],
                ['name' => 'entity_aa', 'parent' => 'entity_a', 'config' => -2],
            ],
            'entity' => 'entity_aa',
            'should_be_enabled' => false,
        ];

        yield 'Enabled from child' => [
            'entities' => [
                ['name' => 'entity_a', 'config' => 0],
                ['name' => 'entity_aa', 'parent' => 'entity_a', 'config' => 1],
            ],
            'entity' => 'entity_aa',
            'should_be_enabled' => true,
        ];

        yield 'Disabled from child' => [
            'entities' => [
                ['name' => 'entity_a', 'config' => 1],
                ['name' => 'entity_aa', 'parent' => 'entity_a', 'config' => 0],
            ],
            'entity' => 'entity_aa',
            'should_be_enabled' => false,
        ];
    }

    #[DataProvider('helpdeskSearchBarConfigIsAppliedProvider')]
    public function testHelpdeskSearchBarConfigIsApplied(
        array $entities,
        string $entity,
        bool $should_be_enabled,
    ): void {
        $this->login();

        // Arrange: create requested entities
        $root = $this->getTestRootEntity(only_id: true);
        foreach ($entities as $to_create) {
            if (isset($to_create['parent'])) {
                $parent = getItemByTypeName(
                    Entity::class,
                    $to_create['parent'],
                    onlyid: true,
                );
            } else {
                $parent = $root;
            }

            $this->createItem(Entity::class, [
                'name'                            => $to_create['name'],
                'entities_id'                     => $parent,
                'enable_helpdesk_home_search_bar' => $to_create['config'],
            ]);
        }

        // Act: render home page
        $this->login('post-only');
        $_SERVER['REQUEST_URI'] = ""; // Needed to avoid warnings
        $renderer = TemplateRenderer::getInstance();
        $content = $renderer->render('pages/helpdesk/index.html.twig', [
            // Important, entity to test
            'entity' => getItemByTypeName(Entity::class, $entity),
            // We don't case about these, set minimal required values
            'title' => '',
            'tiles' => [],
            'tabs'  => new HomePageTabs(),
            'password_alert' => null,
        ]);

        // Assert: try to find the search bar on the page
        $search_bar = (new Crawler($content))
            ->filter('[data-testid=home-search]')
        ;
        $this->assertCount(
            $should_be_enabled ? 1 : 0,
            $search_bar
        );
    }

    public static function helpdeskServiceCatalogConfigIsAppliedProvider(): iterable
    {
        yield 'One entity without config' => [
            'entities' => [
                ['name' => 'entity_a', 'config' => -2],
            ],
            'entity' => 'entity_a',
            'should_be_enabled' => true, // Inherit from root
        ];

        yield 'Inherit enabled from parent' => [
            'entities' => [
                ['name' => 'entity_a', 'config' => 1],
                ['name' => 'entity_aa', 'parent' => 'entity_a', 'config' => -2],
            ],
            'entity' => 'entity_aa',
            'should_be_enabled' => true,
        ];

        yield 'Inherit disabled from parent' => [
            'entities' => [
                ['name' => 'entity_a', 'config' => 0],
                ['name' => 'entity_aa', 'parent' => 'entity_a', 'config' => -2],
            ],
            'entity' => 'entity_aa',
            'should_be_enabled' => false,
        ];

        yield 'Enabled from child' => [
            'entities' => [
                ['name' => 'entity_a', 'config' => 0],
                ['name' => 'entity_aa', 'parent' => 'entity_a', 'config' => 1],
            ],
            'entity' => 'entity_aa',
            'should_be_enabled' => true,
        ];

        yield 'Disabled from child' => [
            'entities' => [
                ['name' => 'entity_a', 'config' => 1],
                ['name' => 'entity_aa', 'parent' => 'entity_a', 'config' => 0],
            ],
            'entity' => 'entity_aa',
            'should_be_enabled' => false,
        ];
    }

    #[DataProvider('helpdeskServiceCatalogConfigIsAppliedProvider')]
    public function testHelpdeskServiceCatalogConfigIsApplied(
        array $entities,
        string $entity,
        bool $should_be_enabled,
    ): void {
        $this->login();

        // Arrange: create requested entities
        $root = $this->getTestRootEntity(only_id: true);
        foreach ($entities as $to_create) {
            if (isset($to_create['parent'])) {
                $parent = getItemByTypeName(
                    Entity::class,
                    $to_create['parent'],
                    onlyid: true,
                );
            } else {
                $parent = $root;
            }

            $this->createItem(Entity::class, [
                'name'                            => $to_create['name'],
                'entities_id'                     => $parent,
                'enable_helpdesk_service_catalog' => $to_create['config'],
            ]);
        }

        // Act: render home page
        $entity = getItemByTypeName(Entity::class, $entity);
        $this->login('post-only');
        $this->setEntity($entity->getId(), true); // Can't pass entity value for this one, it must be set in the session.
        $_SERVER['REQUEST_URI'] = ""; // Needed to avoid warnings
        $renderer = TemplateRenderer::getInstance();
        $content = $renderer->render('pages/helpdesk/index.html.twig', [
            // We don't case about these, set minimal required values
            'entity' => $entity,
            'title' => '',
            'tiles' => [],
            'tabs'  => new HomePageTabs(),
            'password_alert' => null,
        ]);

        // Assert: try to find the search bar on the page
        $search_bar = (new Crawler($content))
            ->filter('[href="/ServiceCatalog"]')
        ;
        $this->assertCount(
            $should_be_enabled ? 1 : 0,
            $search_bar
        );
    }

    public static function helpdeskExpandCategoriesConfigIsAppliedProvider(): iterable
    {
        yield 'One entity without config' => [
            'entities' => [
                ['name' => 'entity_a', 'config' => -2],
            ],
            'entity' => 'entity_a',
            'should_be_expanded' => false, // Inherit from root
        ];

        yield 'Inherit enabled from parent' => [
            'entities' => [
                ['name' => 'entity_a', 'config' => 1],
                ['name' => 'entity_aa', 'parent' => 'entity_a', 'config' => -2],
            ],
            'entity' => 'entity_aa',
            'should_be_expanded' => true,
        ];

        yield 'Inherit disabled from parent' => [
            'entities' => [
                ['name' => 'entity_a', 'config' => 0],
                ['name' => 'entity_aa', 'parent' => 'entity_a', 'config' => -2],
            ],
            'entity' => 'entity_aa',
            'should_be_expanded' => false,
        ];

        yield 'Enabled from child' => [
            'entities' => [
                ['name' => 'entity_a', 'config' => 0],
                ['name' => 'entity_aa', 'parent' => 'entity_a', 'config' => 1],
            ],
            'entity' => 'entity_aa',
            'should_be_expanded' => true,
        ];

        yield 'Disabled from child' => [
            'entities' => [
                ['name' => 'entity_a', 'config' => 1],
                ['name' => 'entity_aa', 'parent' => 'entity_a', 'config' => 0],
            ],
            'entity' => 'entity_aa',
            'should_be_expanded' => false,
        ];
    }

    #[DataProvider('helpdeskExpandCategoriesConfigIsAppliedProvider')]
    public function testHelpdeskExpandCategoriesConfigIsApplied(
        array $entities,
        string $entity,
        bool $should_be_expanded,
    ): void {
        // Arrange: create requested entities
        $this->login();
        $root = $this->getTestRootEntity(only_id: true);
        foreach ($entities as $to_create) {
            if (isset($to_create['parent'])) {
                $parent = getItemByTypeName(
                    Entity::class,
                    $to_create['parent'],
                    onlyid: true,
                );
            } else {
                $parent = $root;
            }

            $this->createItem(Entity::class, [
                'name'                   => $to_create['name'],
                'entities_id'            => $parent,
                'expand_service_catalog' => $to_create['config'],
            ]);
        }

        // Create a category and move the request form into it
        $category = $this->createItem(Category::class, [
            'name' => 'My category',
        ]);
        $request_form = getItemByTypeName(Form::class, "Request a service");
        $this->updateItem(Form::class, $request_form->getID(), [
            Category::getForeignKeyField() => $category->getID(),
        ]);

        // Act: render service catalog page
        $entity = getItemByTypeName(Entity::class, $entity);
        $this->login('post-only');
        $this->setEntity($entity->getId(), true);
        $controller = new IndexController();
        $response = $controller->__invoke(Request::create(""));
        $html = $response->getContent();

        // Assert: try to find the request form, it should only be displayed
        // if categories are expanded
        $request_form = (new Crawler($html))
            ->filter("a[href=\"/Form/Render/{$request_form->getID()}\"]")
        ;
        $this->assertCount(
            $should_be_expanded ? 1 : 0,
            $request_form
        );
    }
}
