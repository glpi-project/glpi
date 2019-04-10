<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

use \DbTestCase;

/* Test for inc/ruledictionnarysoftwarecollection.class.php */

class RuleDictionnarySoftwareCollection extends DbTestCase {

   public function testCleanTestOutputCriterias() {
      $collection = new \RuleDictionnarySoftwareCollection();
      $params     = ['manufacturers_id' => 1,
                     '_bad'             => '_value2',
                     '_ignore_import'   => '1'];
      $result     = $collection->cleanTestOutputCriterias($params);
      $expected   = ['manufacturers_id' => 1,
                   '_ignore_import'   => '1'];
      $this->array($result)->isIdenticalTo($expected);
   }

   public function testVersionExists() {
      $soft    = getItemByTypeName('Software', '_test_soft');
      $version = getItemByTypeName('SoftwareVersion', '_test_softver_1');

      $collection = new \RuleDictionnarySoftwareCollection();
      $result     = $collection->versionExists($soft->getID(), '_test_softver_1');

      $this->variable($result)->isIdenticalTo($version->getID());

      $collection = new \RuleDictionnarySoftwareCollection();
      $result     = $collection->versionExists($soft->getID(), '_test_softver_111');

      $this->variable($result)->isIdenticalTo(-1);
   }

   public function testMoveLicense() {
      $old_software = new \Software();
      $softwares_id = $old_software->add([
         'name'         => 'Software ' .$this->getUniqueString(),
         'is_template'  => 0,
         'entities_id'  => 0
      ]);
      $this->integer((int)$softwares_id)->isGreaterThan(0);
      $this->boolean($old_software->getFromDB($softwares_id))->isTrue();

      //ad and link 5 licenses to new software
      for ($i = 0; $i < 5; ++$i) {
         $license = new \SoftwareLicense();
         $license_id = $license->add([
            'name'         => 'Software license ' . $this->getUniqueString(),
            'softwares_id' => $old_software->getID(),
            'entities_id'  => 0
         ]);
         $this->integer((int)$license_id)->isGreaterThan(0);
         $this->boolean($license->getFromDB($license_id))->isTrue();
      }

      $new_software = new \Software();
      $softwares_id = $new_software->add([
         'name'         => 'Software ' .$this->getUniqueString(),
         'is_template'  => 0,
         'entities_id'  => 0
      ]);
      $this->integer((int)$softwares_id)->isGreaterThan(0);
      $this->boolean($new_software->getFromDB($softwares_id))->isTrue();

      $collection = new \RuleDictionnarySoftwareCollection();
      $this->boolean(
         $collection->moveLicenses(
            $old_software->getID(),
            $new_software->getID()
         )
      )->isTrue();

      $this->integer(
         (int)countElementsInTable(
            'glpi_softwarelicenses',
            ['softwares_id' => $old_software->getID()]
         )
      )->isIdenticalTo(0);
      $this->integer(
         (int)countElementsInTable(
            'glpi_softwarelicenses',
            ['softwares_id' => $new_software->getID()]
         )
      )->isIdenticalTo(5);

      $this->boolean($collection->moveLicenses('100', $new_software->getID()))->isFalse();
      $this->boolean($collection->moveLicenses($old_software->getID(), '100'))->isFalse();
   }

   public function testPutOldSoftsInTrash() {
      $this->login();

      $collection = new \RuleDictionnarySoftwareCollection();
      $software   = new \Software();

      //Softwares with no version
      $soft_id_1  = $software->add(['name' => 'Soft1', 'entities_id' => 0]);
      $this->integer($soft_id_1)->isGreaterThan(0);
      $soft_id_2  = $software->add(['name' => 'Soft2', 'entities_id' => 0]);
      $this->integer($soft_id_2)->isGreaterThan(0);

      //Software with at least one version (from bootstrap)
      $soft3      = getItemByTypeName('Software', '_test_soft');

      //Software already deleted
      $soft_id_4  = $software->add(['name' => 'Soft4', 'is_deleted' => 1, 'entities_id' => 0]);
      $this->integer($soft_id_4)->isGreaterThan(0);

      //Template of software
      $soft_id_5  = $software->add(['name' => 'Soft5', 'is_template' => 1, 'entities_id' => 0]);
      $this->integer($soft_id_5)->isGreaterThan(0);

      $collection->putOldSoftsInTrash([
         $soft_id_1,
         $soft_id_2,
         $soft3->getID(),
         $soft_id_4,
         $soft_id_5
      ]);

      //Softwares newly put in trash
      $this->integer(
         (int)countElementsInTable('glpi_softwares', ['name' => 'Soft1', 'is_deleted' => 1])
      )->isIdenticalTo(1);
      $this->integer(
         (int)countElementsInTable('glpi_softwares', ['name' => 'Soft2', 'is_deleted' => 1])
      )->isIdenticalTo(1);
      $this->integer(
         (int)countElementsInTable('glpi_softwares', ['name' => 'Soft4', 'is_deleted' => 1])
      )->isIdenticalTo(1);

      //Softwares not affected
      $this->integer(
         (int)countElementsInTable('glpi_softwares', ['name' => '_test_soft', 'is_deleted' => 1])
      )->isIdenticalTo(0);
      $this->integer(
         (int)countElementsInTable('glpi_softwares', ['name' => 'Soft5', 'is_deleted' => 0])
      )->isIdenticalTo(0);
   }

   public function testIgnoreImport() {
      $rule       = new \Rule();
      $criteria   = new \RuleCriteria();
      $action     = new \RuleAction();
      $collection = new \RuleDictionnarySoftwareCollection();

      $rules_id = $rule->add(['name'        => 'Ignore import',
                              'is_active'   => 1,
                              'entities_id' => 0,
                              'sub_type'    => 'RuleDictionnarySoftware',
                              'match'       => \Rule::AND_MATCHING,
                              'condition'   => 0,
                              'description' => ''
                             ]);
      $this->integer($rules_id)->isGreaterThan(0);

      $this->integer(
         (int)$criteria->add([
            'rules_id'  => $rules_id,
            'criteria'  => 'name',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => 'Mozilla Firefox 52'
         ])
      )->isGreaterThan(0);

      $this->integer(
         (int)$action->add([
            'rules_id'    => $rules_id,
            'action_type' => 'assign',
            'field'       => '_ignore_import',
            'value'       => '1'
         ])
      )->isGreaterThan(0);

      $input = ['name'             => 'Mozilla Firefox 52',
                'version'          => '52',
                'manufacturer'     => 'Mozilla',
                '_system_category' => 'web'
               ];
      $result = $collection->processAllRules($input);
      $expected = ['_ignore_import' => '1', '_ruleid' => "$rules_id"];
      $this->array($result)->isIdenticalTo($expected);

      $input = ['name'             => 'Mozilla Firefox 53',
                'version'          => '52',
                'manufacturer'     => 'Mozilla',
                '_system_category' => 'web'
               ];
      $result = $collection->processAllRules($input);
      $expected = ['_no_rule_matches' => true, '_rule_process' => false];
      $this->array($result)->isIdenticalTo($expected);

   }

   public function testSetSoftwareVersion() {
      $rule       = new \Rule();
      $criteria   = new \RuleCriteria();
      $action     = new \RuleAction();
      $collection = new \RuleDictionnarySoftwareCollection();

      $rules_id = $rule->add(['name'        => 'Set version',
                              'is_active'   => 1,
                              'entities_id' => 0,
                              'sub_type'    => 'RuleDictionnarySoftware',
                              'match'       => \Rule::AND_MATCHING,
                              'condition'   => 0,
                              'description' => ''
                           ]);
      $this->integer($rules_id)->isGreaterThan(0);

      $this->integer(
         $criteria->add([
            'rules_id'  => $rules_id,
            'criteria'  => 'name',
            'condition' => \Rule::REGEX_MATCH,
            'pattern'   => '/Mozilla Firefox (.*)/'
         ])
      )->isGreaterThan(0);

      $this->integer(
         $action->add([
            'rules_id'    => $rules_id,
            'action_type' => 'regex_result',
            'field'       => 'version',
            'value'       => '#0'
         ])
      )->isGreaterThan(0);

      $input = ['name'             => 'Mozilla Firefox 52',
                'manufacturer'     => 'Mozilla',
                '_system_category' => 'web'
               ];

      $collection->RuleList = new \stdClass();
      $collection->RuleList->load = true;
      $result   = $collection->processAllRules($input);
      $expected = ['version' => '52', '_ruleid' => "$rules_id"];
      $this->array($result)->isIdenticalTo($expected);
   }

   public function testSetSoftwareNameAndVersion() {
      $rule       = new \Rule();
      $criteria   = new \RuleCriteria();
      $action     = new \RuleAction();
      $collection = new \RuleDictionnarySoftwareCollection();

      $rules_id = $rule->add(['name'        => 'Set version',
                              'is_active'   => 1,
                              'entities_id' => 0,
                              'sub_type'    => 'RuleDictionnarySoftware',
                              'match'       => \Rule::AND_MATCHING,
                              'condition'   => 0,
                              'description' => ''
                           ]);
      $this->integer($rules_id)->isGreaterThan(0);

      $this->integer(
         $criteria->add([
            'rules_id'  => $rules_id,
            'criteria'  => 'name',
            'condition' => \Rule::REGEX_MATCH,
            'pattern'   => '/Mozilla Firefox (.*)/'
         ])
      )->isGreaterThan(0);

      $this->integer(
         $action->add([
            'rules_id'    => $rules_id,
            'action_type' => 'regex_result',
            'field'       => 'version',
            'value'       => '#0'
         ])
      )->isGreaterThan(0);

      $this->integer(
         $action->add([
            'rules_id'    => $rules_id,
            'action_type' => 'assign',
            'field'       => 'name',
            'value'       => 'Mozilla Firefox'
         ])
      )->isGreaterThan(0);

      $input = ['name'             => 'Mozilla Firefox 52',
                'manufacturer'     => 'Mozilla',
                '_system_category' => 'web'
               ];

      $collection->RuleList = new \stdClass();
      $collection->RuleList->load = true;
      $result   = $collection->processAllRules($input);
      $expected = [
         'version' => '52',
         'name'    => 'Mozilla Firefox',
         '_ruleid' => "$rules_id",
      ];
      $this->array($result)->isIdenticalTo($expected);
   }

   public function testSetSoftwareNameAndCategory() {
      $rule       = new \Rule();
      $criteria   = new \RuleCriteria();
      $action     = new \RuleAction();
      $collection = new \RuleDictionnarySoftwareCollection();
      $category   = new \SoftwareCategory();
      $categories_id = $category->importExternal('web');

      $rules_id = $rule->add(['name'        => 'Set version',
                              'is_active'   => 1,
                              'entities_id' => 0,
                              'sub_type'    => 'RuleDictionnarySoftware',
                              'match'       => \Rule::AND_MATCHING,
                              'condition'   => 0,
                              'description' => ''
                           ]);
      $this->integer($rules_id)->isGreaterThan(0);

      $this->integer(
         $criteria->add([
            'rules_id'  => $rules_id,
            'criteria'  => 'name',
            'condition' => \Rule::REGEX_MATCH,
            'pattern'   => '/Mozilla Firefox (.*)/'
         ])
      )->isGreaterThan(0);

      $this->integer(
         $action->add([
            'rules_id'    => $rules_id,
            'action_type' => 'assign',
            'field'       => 'softwarecategories_id',
            'value'       => $categories_id
         ])
      )->isGreaterThan(0);

      $this->integer(
         $action->add([
            'rules_id'    => $rules_id,
            'action_type' => 'assign',
            'field'       => 'name',
            'value'       => 'Mozilla Firefox'
         ])
      )->isGreaterThan(0);

      $input = ['name'             => 'Mozilla Firefox 52',
                'manufacturer'     => 'Mozilla',
                '_system_category' => 'web'
               ];

      $collection->RuleList = new \stdClass();
      $collection->RuleList->load = true;
      $result   = $collection->processAllRules($input);
      $expected = [
         'softwarecategories_id' => "$categories_id",
         'name'                  => 'Mozilla Firefox',
         '_ruleid'               => "$rules_id"
      ];
      $this->array($result)->isIdenticalTo($expected);
   }

   public function testSetManufacturer() {
      $rule             = new \Rule();
      $criteria         = new \RuleCriteria();
      $action           = new \RuleAction();
      $collection       = new \RuleDictionnarySoftwareCollection();
      $manufacturer     = new \Manufacturer();
      $manufacturers_id = $manufacturer->importExternal('Mozilla');

      $rules_id = $rule->add(['name'        => 'Set manufacturer',
                              'is_active'   => 1,
                              'entities_id' => 0,
                              'sub_type'    => 'RuleDictionnarySoftware',
                              'match'       => \Rule::AND_MATCHING,
                              'condition'   => 0,
                              'description' => ''
                             ]);
      $this->integer($rules_id)->isGreaterThan(0);

      $this->integer(
         $criteria->add([
            'rules_id'  => $rules_id,
            'criteria'  => 'name',
            'condition' => \Rule::REGEX_MATCH,
            'pattern'   => '/Mozilla Firefox (.*)/'
         ])
      )->isGreaterThan(0);

      $this->integer(
         $action->add([
            'rules_id'    => $rules_id,
            'action_type' => 'assign',
            'field'       => 'manufacturers_id',
            'value'       => $manufacturers_id
         ])
      )->isGreaterThan(0);

      $input = ['name'             => 'Mozilla Firefox 52',
                'manufacturer'     => 'Mozilla',
                '_system_category' => 'web'
               ];

      $collection->RuleList = new \stdClass();
      $collection->RuleList->load = true;
      $result   = $collection->processAllRules($input);
      $expected = ['manufacturers_id' => "$manufacturers_id",
                   '_ruleid'          => "$rules_id"
                  ];
      $this->array($result)->isIdenticalTo($expected);
   }

   public function testSetSoftwareVersionAppend() {
      $rule       = new \Rule();
      $criteria   = new \RuleCriteria();
      $action     = new \RuleAction();
      $collection = new \RuleDictionnarySoftwareCollection();

      $rules_id = $rule->add(['name'        => 'Test append',
                              'is_active'   => 1,
                              'entities_id' => 0,
                              'sub_type'    => 'RuleDictionnarySoftware',
                              'match'       => \Rule::AND_MATCHING,
                              'condition'   => 0,
                              'description' => ''
                           ]);
      $this->integer($rules_id)->isGreaterThan(0);

      $this->integer(
         $criteria->add([
            'rules_id'  => $rules_id,
            'criteria'  => 'name',
            'condition' => \Rule::REGEX_MATCH,
            'pattern'   => '/^Soft (something|else)/'
         ])
      )->isGreaterThan(0);

      $this->integer(
         $action->add([
            'rules_id'    => $rules_id,
            'action_type' => 'append_regex_result',
            'field'       => 'version',
            'value'       => '#0'
         ])
      )->isGreaterThan(0);

      $input = ['name'             => 'Soft something'];
      $collection->RuleList = new \stdClass();
      $collection->RuleList->load = true;
      $result   = $collection->processAllRules($input);
      $expected = ['version_append' => 'something', 'version' => 'something', '_ruleid' => "$rules_id"];
      $this->array($result)->isIdenticalTo($expected);

      $input = ['name'             => 'Soft else'];
      $collection->RuleList = new \stdClass();
      $collection->RuleList->load = true;
      $result   = $collection->processAllRules($input);
      $expected = ['version_append' => 'else', 'version' => 'else', '_ruleid' => "$rules_id"];
      $this->array($result)->isIdenticalTo($expected);

   }
}
