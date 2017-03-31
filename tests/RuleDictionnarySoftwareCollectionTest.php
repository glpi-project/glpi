<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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

/* Test for inc/ruledictionnarysoftwarecollection.class.php */

class RuleDictionnarySoftwareCollectionTest extends DbTestCase {

   /**
    * @covers RuleDictionnarySoftwareCollection::cleanTestOutputCriterias
    */
   public function testCleanTestOutputCriterias() {
      $collection = new RuleDictionnarySoftwareCollection();
      $params     = ['manufacturers_id' => 1,
                     '_bad'             => '_value2',
                     '_ignore_import'   => '1'];
      $result     = $collection->cleanTestOutputCriterias($params);
      $expected   = ['manufacturers_id' => 1,
                   '_ignore_import'   => '1'];
      $this->assertEquals($result, $expected);
   }

   /**
    * @covers RuleDictionnarySoftwareCollection::versionExists
    */
   public function testVersionExists() {
      $soft    = getItemByTypeName('Software', '_test_soft');
      $version = getItemByTypeName('SoftwareVersion', '_test_softver_1');

      $collection = new RuleDictionnarySoftwareCollection();
      $result     = $collection->versionExists($soft->getID(), '_test_softver_1');

      $this->assertEquals($result, $version->getID());

      $collection = new RuleDictionnarySoftwareCollection();
      $result     = $collection->versionExists($soft->getID(), '_test_softver_111');

      $this->assertEquals($result, -1);

   }

   /**
    * @covers RuleDictionnarySoftwareCollection::moveLicenses
    */
   public function testMoveLicense() {
      $old_soft = getItemByTypeName('Software', '_test_soft');
      $new_soft = getItemByTypeName('Software', '_test_soft_3');

      $collection = new RuleDictionnarySoftwareCollection();
      $this->assertTrue($collection->moveLicenses($old_soft->getID(), $new_soft->getID()));

      $this->assertEquals(0, countElementsInTable('glpi_softwarelicenses',
                                                  ['softwares_id' => $old_soft->getID()]));
      $this->assertEquals(5, countElementsInTable('glpi_softwarelicenses',
                                                  ['softwares_id' => $new_soft->getID()]));

      $this->assertFalse($collection->moveLicenses('100', $new_soft->getID()));
      $this->assertFalse($collection->moveLicenses($old_soft->getID(), '100'));
   }

   /**
    * @covers RuleDictionnarySoftwareCollection::putOldSoftsInTrash
    */
   public function testPutOldSoftsInTrash() {
      $this->Login();

      $collection = new RuleDictionnarySoftwareCollection();
      $software   = new Software();

      //Softwares with no version
      $soft_id_1  = $software->add(['name' => 'Soft1']);
      $soft_id_2  = $software->add(['name' => 'Soft2']);

      //Software with at least one version (from bootstrap)
      $soft3      = getItemByTypeName('Software', '_test_soft');

      //Software already deleted
      $soft_id_4  = $software->add(['name' => 'Soft4', 'is_deleted' => 1]);

      //Template of software
      $soft_id_5  = $software->add(['name' => 'Soft5', 'is_template' => 1]);

      $result = $collection->putOldSoftsInTrash([$soft_id_1, $soft_id_2,
                                                 $soft3->getID(), $soft_id_4,
                                                 $soft_id_5]);

      //Softwares newly put in trash
      $this->assertEquals(1, countElementsInTable('glpi_softwares', ['name' => 'Soft1', 'is_deleted' => 1]));
      $this->assertEquals(1, countElementsInTable('glpi_softwares', ['name' => 'Soft2', 'is_deleted' => 1]));
      $this->assertEquals(1, countElementsInTable('glpi_softwares', ['name' => 'Soft4', 'is_deleted' => 1]));

      //Softwares not affected
      $this->assertEquals(0, countElementsInTable('glpi_softwares', ['name' => '_test_soft', 'is_deleted' => 1]));
      $this->assertEquals(0, countElementsInTable('glpi_softwares', ['name' => 'Soft5', 'is_deleted' => 0]));
   }

   /**
   * @test Test ignore software import
   */
   public function testIgnoreImport() {
      $rule       = new Rule();
      $criteria   = new RuleCriteria();
      $action     = new RuleAction();
      $collection = new RuleDictionnarySoftwareCollection();

      $rules_id = $rule->add(['name'        => 'Ignore import',
                              'is_active'   => 1,
                              'entities_id' => 0,
                              'sub_type'    => 'RuleDictionnarySoftware',
                              'match'       => Rule::AND_MATCHING,
                              'condition'   => 0,
                              'description' => ''
                             ]);

      $criteria->add(['rules_id'  => $rules_id,
                      'criteria'  => 'name',
                      'condition' => Rule::PATTERN_IS,
                      'pattern'   => 'Mozilla Firefox 52'
                     ]);

      $action->add(['rules_id'    => $rules_id,
                    'action_type' => 'assign',
                    'field'       => '_ignore_import',
                    'value'       => '1'
                   ]);

      $input = ['name'             => 'Mozilla Firefox 52',
                'version'          => '52',
                'manufacturer'     => 'Mozilla',
                '_system_category' => 'web'
               ];
      $result = $collection->processAllRules($input);
      $expected = ['_ignore_import' => 1, '_ruleid' => $rules_id];
      $this->assertEquals($result, $expected);

      $input = ['name'             => 'Mozilla Firefox 53',
                'version'          => '52',
                'manufacturer'     => 'Mozilla',
                '_system_category' => 'web'
               ];
      $result = $collection->processAllRules($input);
      $expected = ['_no_rule_matches' => 1, '_rule_process' => ''];
      $this->assertEquals($result, $expected);

   }

   /**
   * @test Test set version during import
   */
   public function testSetSoftwareVersion() {
      $rule       = new Rule();
      $criteria   = new RuleCriteria();
      $action     = new RuleAction();
      $collection = new RuleDictionnarySoftwareCollection();

      $rules_id = $rule->add(['name'        => 'Set version',
                              'is_active'   => 1,
                              'entities_id' => 0,
                              'sub_type'    => 'RuleDictionnarySoftware',
                              'match'       => Rule::AND_MATCHING,
                              'condition'   => 0,
                              'description' => ''
                             ]);

      $criteria->add(['rules_id'  => $rules_id,
                      'criteria'  => 'name',
                      'condition' => Rule::REGEX_MATCH,
                      'pattern'   => '/Mozilla Firefox (.*)/'
                     ]);

      $action->add(['rules_id'    => $rules_id,
                    'action_type' => 'regex_result',
                    'field'       => 'version',
                    'value'       => '#0'
                   ]);

      $input = ['name'             => 'Mozilla Firefox 52',
                'manufacturer'     => 'Mozilla',
                '_system_category' => 'web'
               ];

      $collection->RuleList->load = true;
      $result   = $collection->processAllRules($input);
      $expected = ['version' => '52', '_ruleid' => $rules_id];
      $this->assertEquals($result, $expected);
   }

   /**
   * @test Test set name and version
   */
   public function testSetSoftwareNameAndVersion() {
      $rule       = new Rule();
      $criteria   = new RuleCriteria();
      $action     = new RuleAction();
      $collection = new RuleDictionnarySoftwareCollection();

      $rules_id = $rule->add(['name'        => 'Set version',
                              'is_active'   => 1,
                              'entities_id' => 0,
                              'sub_type'    => 'RuleDictionnarySoftware',
                              'match'       => Rule::AND_MATCHING,
                              'condition'   => 0,
                              'description' => ''
                             ]);

      $criteria->add(['rules_id'  => $rules_id,
                      'criteria'  => 'name',
                      'condition' => Rule::REGEX_MATCH,
                      'pattern'   => '/Mozilla Firefox (.*)/'
                     ]);

      $action->add(['rules_id'    => $rules_id,
                    'action_type' => 'regex_result',
                    'field'       => 'version',
                    'value'       => '#0'
                   ]);
       $action->add(['rules_id'    => $rules_id,
                     'action_type' => 'assign',
                     'field'       => 'name',
                     'value'       => 'Mozilla Firefox'
                    ]);

      $input = ['name'             => 'Mozilla Firefox 52',
                'manufacturer'     => 'Mozilla',
                '_system_category' => 'web'
               ];

      $collection->RuleList->load = true;
      $result   = $collection->processAllRules($input);
      $expected = ['name'    => 'Mozilla Firefox',
                   'version' => '52',
                   '_ruleid' => $rules_id
                  ];
      $this->assertEquals($result, $expected);
   }

   /**
   * @test Test set name and category
   */
   public function testSetSoftwareNameAndCategory() {
      $rule       = new Rule();
      $criteria   = new RuleCriteria();
      $action     = new RuleAction();
      $collection = new RuleDictionnarySoftwareCollection();
      $category   = new SoftwareCategory();
      $categories_id = $category->importExternal('web');

      $rules_id = $rule->add(['name'        => 'Set version',
                              'is_active'   => 1,
                              'entities_id' => 0,
                              'sub_type'    => 'RuleDictionnarySoftware',
                              'match'       => Rule::AND_MATCHING,
                              'condition'   => 0,
                              'description' => ''
                             ]);

      $criteria->add(['rules_id'  => $rules_id,
                      'criteria'  => 'name',
                      'condition' => Rule::REGEX_MATCH,
                      'pattern'   => '/Mozilla Firefox (.*)/'
                     ]);

      $action->add(['rules_id'    => $rules_id,
                    'action_type' => 'assign',
                    'field'       => 'softwarecategories_id',
                    'value'       => $categories_id
                   ]);
       $action->add(['rules_id'    => $rules_id,
                     'action_type' => 'assign',
                     'field'       => 'name',
                     'value'       => 'Mozilla Firefox'
                    ]);

      $input = ['name'             => 'Mozilla Firefox 52',
                'manufacturer'     => 'Mozilla',
                '_system_category' => 'web'
               ];

      $collection->RuleList->load = true;
      $result   = $collection->processAllRules($input);
      $expected = ['name'                  => 'Mozilla Firefox',
                   'softwarecategories_id' => $categories_id,
                   '_ruleid'               => $rules_id
                  ];
      $this->assertEquals($result, $expected);
   }

   /**
   * @test Test set manufacturer
   */
   public function testSetManufacturer() {
      $rule             = new Rule();
      $criteria         = new RuleCriteria();
      $action           = new RuleAction();
      $collection       = new RuleDictionnarySoftwareCollection();
      $manufacturer     = new Manufacturer();
      $manufacturers_id = $manufacturer->importExternal('Mozilla');

      $rules_id = $rule->add(['name'        => 'Set manufacturer',
                              'is_active'   => 1,
                              'entities_id' => 0,
                              'sub_type'    => 'RuleDictionnarySoftware',
                              'match'       => Rule::AND_MATCHING,
                              'condition'   => 0,
                              'description' => ''
                             ]);

      $criteria->add(['rules_id'  => $rules_id,
                      'criteria'  => 'name',
                      'condition' => Rule::REGEX_MATCH,
                      'pattern'   => '/Mozilla Firefox (.*)/'
                     ]);

      $action->add(['rules_id'    => $rules_id,
                    'action_type' => 'assign',
                    'field'       => 'manufacturers_id',
                    'value'       => $manufacturers_id
                   ]);

      $input = ['name'             => 'Mozilla Firefox 52',
                'manufacturer'     => 'Mozilla',
                '_system_category' => 'web'
               ];

      $collection->RuleList->load = true;
      $result   = $collection->processAllRules($input);
      $expected = ['manufacturers_id' => $manufacturers_id,
                   '_ruleid'          => $rules_id
                  ];
      $this->assertEquals($result, $expected);
   }
}
