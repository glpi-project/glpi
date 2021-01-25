<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace tests\units;

use DbTestCase;
use Profile_User;

/* Test for inc/entity.class.php */

class Entity extends DbTestCase {
   protected $cached_methods = [
      'testChangeEntityParentCached'
   ];

   public function testSonsAncestors() {
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

   public function testPrepareInputForAdd() {
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
   public function runChangeEntityParent($cache = false, $hit = false) {
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

   private function checkParentsSonsAreReset() {
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

   public function testChangeEntityParent() {
      global $DB;
      //ensure db cache are unset
      $DB->update(
         'glpi_entities', [
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
    * @extensions apcu
    */
   public function testChangeEntityParentCached() {
      //run with cache
      //first run: no cache hit expected
      $this->runChangeEntityParent(true);
      //reset cache (checking for expected defaults) then run a second time: cache hit expected
      //second run: cache hit expected
      $this->checkParentsSonsAreReset();
      $this->runChangeEntityParent(true);
   }

   public function testInheritGeolocation() {
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

   public function testDeleteEntity() {
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

   protected function inheritanceProvider() {
      return [
         ['admin_email', "username+admin@domain.tld"],
         ['admin_email_name', "Username admin"],
         ['admin_reply', "username+admin+reply@domain.tld"],
         ['admin_reply_name', "Username admin reply"],
      ];
   }

   /**
    * @dataProvider inheritanceProvider
    */
   public function testGetUsedConfig(string $field, $value) {
      $this->login();

      $root    = getItemByTypeName('Entity', 'Root entity', true);
      $parent  = getItemByTypeName('Entity', '_test_root_entity', true);
      $child_1 = getItemByTypeName('Entity', '_test_child_1', true);
      $child_2 = getItemByTypeName('Entity', '_test_child_2', true);

      $entity = new \Entity;
      $this->boolean($entity->update([
         'id'   => $root,
         $field => $value."_root",
      ]));

      $this->string(\Entity::getUsedConfig($field, $parent))->isEqualTo($value."_root");
      $this->string(\Entity::getUsedConfig($field, $child_1))->isEqualTo($value."_root");
      $this->string(\Entity::getUsedConfig($field, $child_2))->isEqualTo($value."_root");

      $this->boolean($entity->update([
         'id'   => $parent,
         $field => $value."_parent",
      ]));

      $this->string(\Entity::getUsedConfig($field, $parent))->isEqualTo($value."_parent");
      $this->string(\Entity::getUsedConfig($field, $child_1))->isEqualTo($value."_parent");
      $this->string(\Entity::getUsedConfig($field, $child_2))->isEqualTo($value."_parent");

      $this->boolean($entity->update([
         'id'   => $child_1,
         $field => $value."_child_1",
      ]));

      $this->string(\Entity::getUsedConfig($field, $parent))->isEqualTo($value."_parent");
      $this->string(\Entity::getUsedConfig($field, $child_1))->isEqualTo($value."_child_1");
      $this->string(\Entity::getUsedConfig($field, $child_2))->isEqualTo($value."_parent");

      $this->boolean($entity->update([
         'id'   => $child_2,
         $field => $value."_child_2",
      ]));

      $this->string(\Entity::getUsedConfig($field, $parent))->isEqualTo($value."_parent");
      $this->string(\Entity::getUsedConfig($field, $child_1))->isEqualTo($value."_child_1");
      $this->string(\Entity::getUsedConfig($field, $child_2))->isEqualTo($value."_child_2");

   }


   protected function customCssProvider() {

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
            'root_custom_css_code'    => 'body { color:blue; }',
            'child_enable_custom_css' => \Entity::CONFIG_PARENT,
            'child_custom_css_code'   => '',
            'expected'                => '<style>body { color:blue; }</style>',
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
            'child_custom_css_code'   => 'body { color:blue; }',
            'expected'                => '<style>body { color:blue; }</style>',
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
}
