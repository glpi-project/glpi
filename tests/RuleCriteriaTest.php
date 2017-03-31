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

/* Test for inc/rulecriteria.class.php */

class RuleCriteriaTest extends DbTestCase {


   /**
    * @covers RuleCriteria::getForbiddenStandardMassiveAction
    */
   public function testGetForbiddenStandardMassiveAction() {
      $criteria = new RuleCriteria();
      $this->assertEquals(1, count($criteria->getForbiddenStandardMassiveAction()));
   }

   /**
    * @covers RuleCriteria::__construct
    */
   public function testConstruct() {
      $criteria = new RuleCriteria('RuleDictionnarySoftware');
      $this->assertEquals('RuleDictionnarySoftware', $criteria::$itemtype);
   }

   /**
    * @covers RuleCriteria::post_getFromDB
    */
   public function testPost_getFromDB() {
      $rule     = new Rule();
      $criteria = new RuleCriteria();

      $rules_id = $rule->add(['name'        => 'Ignore import',
                              'is_active'   => 1,
                              'entities_id' => 0,
                              'sub_type'    => 'RuleDictionnarySoftware',
                              'match'       => Rule::AND_MATCHING,
                              'condition'   => 0,
                              'description' => ''
                             ]);
      $criteria_id = $criteria->add(['rules_id'  => $rules_id,
                                    'criteria'  => 'name',
                                    'condition' => Rule::PATTERN_IS,
                                    'pattern'   => 'Mozilla Firefox 52'
                                   ]);

      $criteria->getFromDB($criteria_id);
      $this->assertEquals('RuleDictionnarySoftware', $criteria::$itemtype);
      $this->assertNotEquals('Rule', $criteria::$itemtype);
   }

   /**
    * @covers RuleCriteria::getTypeName
    */
   public function testGetTypeName() {
      $this->assertEquals('Criterion', RuleCriteria::getTypeName(1));
      $this->assertEquals('Criteria', RuleCriteria::getTypeName(Session::getPluralNumber()));
   }

   /**
    * @covers RuleCriteria::getRawName
    */
   public function testRawTypeName() {
      $rule     = new Rule();
      $criteria = new RuleCriteria();

      $rules_id = $rule->add(['name'        => 'Ignore import',
                              'is_active'   => 1,
                              'entities_id' => 0,
                              'sub_type'    => 'RuleDictionnarySoftware',
                              'match'       => Rule::AND_MATCHING,
                              'condition'   => 0,
                              'description' => ''
                             ]);
      $criteria_id = $criteria->add(['rules_id'  => $rules_id,
                                     'criteria'  => 'name',
                                     'condition' => Rule::PATTERN_IS,
                                     'pattern'   => 'Mozilla Firefox 52'
                                    ]);

      $criteria->getFromDB($criteria_id);
      $this->assertEquals('SoftwareisMozilla Firefox 52', $criteria->getRawName());
   }

   /**
    * @covers RuleCriteria::post_addItem
    */
   public function testPost_addItem() {
      $this->Login();
      $rule     = new Rule();
      $criteria = new RuleCriteria();

      $rules_id = $rule->add(['name'        => 'Ignore import',
                              'is_active'   => 1,
                              'entities_id' => 0,
                              'sub_type'    => 'RuleDictionnarySoftware',
                              'match'       => Rule::AND_MATCHING,
                              'condition'   => 0,
                              'description' => '',
                             ]);

      $rule->getFromDB($rules_id);
      $this->updateDateMod($rules_id, '2017-03-31 00:00:00');

      $criteria_id = $criteria->add(['rules_id'  => $rules_id,
                                     'criteria'  => 'name',
                                     'condition' => Rule::PATTERN_IS,
                                     'pattern'   => 'Mozilla Firefox 52'
                                    ]);

      $rule->getFromDB($rules_id);

      //By adding a critera, rule's date_mod must have been updated
      $this->assertTrue($rule->fields['date_mod'] > '2017-03-31 00:00:00');

   }

   /**
    * @covers RuleCriteria::post_purgeItem
    */
   public function testPost_purgeItem() {
      $this->Login();
      $rule     = new Rule();
      $criteria = new RuleCriteria();

      $rules_id = $rule->add(['name'        => 'Ignore import',
                              'is_active'   => 1,
                              'entities_id' => 0,
                              'sub_type'    => 'RuleDictionnarySoftware',
                              'match'       => Rule::AND_MATCHING,
                              'condition'   => 0,
                              'description' => '',
                             ]);

      $rule->getFromDB($rules_id);

      $criteria_id = $criteria->add(['rules_id'  => $rules_id,
                                     'criteria'  => 'name',
                                     'condition' => Rule::PATTERN_IS,
                                     'pattern'   => 'Mozilla Firefox 52'
                                    ]);

      $this->updateDateMod($rules_id, '2017-03-31 00:00:00');

      $criteria->delete(['id' => $criteria_id], true);
      $rule->getFromDB($rules_id);

      //By adding a critera, rule's date_mod must have been updated
      $this->assertTrue($rule->fields['date_mod'] > '2017-03-31 00:00:00');

   }

   /**
    * @covers RuleCriteria::prepareInputForAdd
    */
   public function testPrepareInputForAdd() {
      $rule     = new Rule();
      $criteria = new RuleCriteria();

      $this->assertFalse($criteria->prepareInputForAdd('name'));

      $input    = ['rules_id' => 1, 'criteria' => 'name'];
      $this->assertFalse($criteria->prepareInputForAdd($input));

      $rules_id = $rule->add(['name'        => 'Ignore import',
                              'is_active'   => 1,
                              'entities_id' => 0,
                              'sub_type'    => 'RuleDictionnarySoftware',
                              'match'       => Rule::AND_MATCHING,
                              'condition'   => 0,
                              'description' => '',
                             ]);

      $rule->getFromDB($rules_id);

      $input    = ['rules_id' => $rules_id, 'criteria' => 'name'];
      $this->assertEquals($input, $criteria->prepareInputForAdd($input));

      $input = ['rules_id' => 1];
      $this->assertEquals(false, $criteria->prepareInputForAdd($input));
   }

   /**
   * @cover RuleCriteria::getSearchOptionsNew
   */
   public function testGetSearchOptionsNew() {
      $criteria = new RuleCriteria();
      $this->assertEquals(3, count($criteria->getSearchOptionsNew()));
   }

   /**
   * @cover RuleCriteria::getRuleCriterias
   */
   public function testGetRuleCriterias() {
      $rule     = new Rule();
      $criteria = new RuleCriteria();

      $rules_id = $rule->add(['name'        => 'Example rule',
                              'is_active'   => 1,
                              'entities_id' => 0,
                              'sub_type'    => 'RuleDictionnarySoftware',
                              'match'       => Rule::AND_MATCHING,
                              'condition'   => 0,
                              'description' => '',
                             ]);

      $criteria->add(['rules_id'  => $rules_id,
                       'criteria'  => 'name',
                       'condition' => Rule::PATTERN_IS,
                       'pattern'   => 'Mozilla Firefox 52'
                      ]);

      $criteria->add(['rules_id'  => $rules_id,
                      'criteria'  => 'version',
                      'condition' => Rule::REGEX_NOT_MATCH,
                      'pattern'   => '/(.*)/'
                     ]);

      //Get criteria for the newly created rule
      $result   = $criteria->getRuleCriterias($rules_id);
      $this->assertEquals(2, count($result));
      $this->assertEquals($result[0]->fields['criteria'], 'name');
      $this->assertEquals($result[1]->fields['criteria'], 'version');

      //Try to get criteria for a non existing rule
      $this->assertEquals(0, count($criteria->getRuleCriterias(100)));

   }

   /*
   * @cover RuleCriteria::match
   */
   public function testMatchConditionWildcardOfFind() {
      $criteria = new RuleCriteria();

      $criteria->fields = ['id' => 1,
                           'rules_id'  => 1,
                           'criteria'  => 'name',
                           'condition' => Rule::PATTERN_IS,
                           'pattern'   => Rule::RULE_WILDCARD
                          ];

      $results      = [];
      $regex_result = [];
      $this->assertTrue($criteria->match($criteria, 'Mozilla Firefox',
                                         $results, $regex_result));

      $criteria->fields = ['id' => 1,
                           'rules_id'  => 1,
                           'criteria'  => 'name',
                           'condition' => Rule::PATTERN_FIND,
                           'pattern'   => Rule::RULE_WILDCARD
                          ];
      $this->assertTrue($criteria->match($criteria, 'Mozilla Firefox',
                                         $results, $regex_result));

   }

   /*
   * @cover RuleCriteria::match
   */
   public function testMatchConditionIsOrNot() {
      $criteria = new RuleCriteria();

      $criteria->fields = ['id' => 1,
                           'rules_id'  => 1,
                           'criteria'  => 'name',
                           'condition' => Rule::PATTERN_IS,
                           'pattern'   => 'Mozilla Firefox'
                          ];

      $results      = [];
      $regex_result = [];
      $this->assertTrue($criteria->match($criteria, 'Mozilla Firefox',
                                         $results, $regex_result));
      $this->assertEquals($results['name'], 'mozilla firefox');

      $results = [];
      $this->assertTrue($criteria->match($criteria, ['Mozilla Firefox', 'foo'],
                                         $results, $regex_result));
      $this->assertEquals($results['name'], 'Mozilla Firefox');

      $results = [];
      $this->assertFalse($criteria->match($criteria, ['foo', 'bar'],
                                          $results, $regex_result));
      $this->assertEmpty($results);

      $results = [];
      $this->assertFalse($criteria->match($criteria, 'foo',
                                          $results, $regex_result));
      $this->assertEmpty($results);

      $criteria->fields = ['id' => 1,
                           'rules_id'  => 1,
                           'criteria'  => 'name',
                           'condition' => Rule::PATTERN_IS_NOT,
                           'pattern'   => 'Mozilla Firefox'
                          ];

      $results = [];
      $this->assertTrue($criteria->match($criteria, 'foo',
                                         $results, $regex_result));
      $this->assertEquals($results['name'], 'mozilla firefox');

   }

   /*
   * @cover RuleCriteria::match
   */
   public function testMatchConditionExistsOrNot() {
      $criteria = new RuleCriteria();

      $criteria->fields = ['id' => 1,
                           'rules_id'  => 1,
                           'criteria'  => 'name',
                           'condition' => Rule::PATTERN_EXISTS,
                           'pattern'   => ''
                          ];

      $results      = [];
      $regex_result = [];
      $this->assertTrue($criteria->match($criteria, 'Mozilla Firefox',
                                         $results, $regex_result));
      $this->assertEmpty($results);

      $results = [];
      $this->assertFalse($criteria->match($criteria, '',
                                          $results, $regex_result));
      $this->assertEmpty($results);

      $results = [];
      $this->assertFalse($criteria->match($criteria, null,
                                          $results, $regex_result));
      $this->assertEmpty($results);
   }

   /*
   * @cover RuleCriteria::match
   */
   public function testMatchContainsOrNot() {
      $criteria = new RuleCriteria();

      $criteria->fields = ['id' => 1,
                           'rules_id'  => 1,
                           'criteria'  => 'name',
                           'condition' => Rule::PATTERN_CONTAIN,
                           'pattern'   => 'Firefox'
                          ];

      $results      = [];
      $regex_result = [];
      $this->assertTrue($criteria->match($criteria, 'Mozilla Firefox',
                                         $results, $regex_result));
      $this->assertEquals($results, ['name' => 'Firefox']);

      $this->assertTrue($criteria->match($criteria, 'mozilla firefox',
                                         $results, $regex_result));
      $this->assertEquals($results, ['name' => 'Firefox']);

      $this->assertFalse($criteria->match($criteria, '',
                                          $results, $regex_result));
      $this->assertEquals($results, ['name' => 'Firefox']);

      $this->assertFalse($criteria->match($criteria, null,
                                          $results, $regex_result));
      $this->assertEquals($results, ['name' => 'Firefox']);

      $criteria->fields = ['id' => 1,
                           'rules_id'  => 1,
                           'criteria'  => 'name',
                           'condition' => Rule::PATTERN_NOT_CONTAIN,
                           'pattern'   => 'Firefox'
                          ];

      $results      = [];
      $regex_result = [];
      $this->assertTrue($criteria->match($criteria, 'Konqueror',
                                         $results, $regex_result));

      $this->assertEquals($results, ['name' => 'Firefox']);

   }

   /*
   * @cover RuleCriteria::match
   */
   public function testMatchBeginEnd() {
      $criteria = new RuleCriteria();

      $criteria->fields = ['id'        => 1,
                           'rules_id'  => 1,
                           'criteria'  => 'name',
                           'condition' => Rule::PATTERN_BEGIN,
                           'pattern'   => 'Mozilla'
                          ];

      $results      = [];
      $regex_result = [];
      $this->assertTrue($criteria->match($criteria, 'Mozilla Firefox',
                                         $results, $regex_result));
      $this->assertEquals($results, ['name' => 'Mozilla']);

      $results      = [];
      $regex_result = [];
      $this->assertTrue($criteria->match($criteria, 'mozilla firefox',
                                         $results, $regex_result));
      $this->assertEquals($results, ['name' => 'Mozilla']);

      $results      = [];
      $regex_result = [];
      $this->assertFalse($criteria->match($criteria, '',
                                          $results, $regex_result));
      $this->assertEquals($results, []);

      $results      = [];
      $regex_result = [];
      $this->assertFalse($criteria->match($criteria, null,
                                          $results, $regex_result));
      $this->assertEquals($results, []);

      $criteria->fields = ['id' => 1,
                           'rules_id'  => 1,
                           'criteria'  => 'name',
                           'condition' => Rule::PATTERN_END,
                           'pattern'   => 'Firefox'
                          ];

      $results      = [];
      $regex_result = [];
      $this->assertTrue($criteria->match($criteria, 'Mozilla Firefox',
                                         $results, $regex_result));

      $this->assertEquals($results, ['name' => 'Firefox']);

      $results      = [];
      $regex_result = [];
      $this->assertTrue($criteria->match($criteria, 'mozilla firefox',
                                         $results, $regex_result));

      $this->assertEquals($results, ['name' => 'Firefox']);

      $results      = [];
      $regex_result = [];
      $this->assertFalse($criteria->match($criteria, '',
                                         $results, $regex_result));

      $this->assertEquals($results, []);

   }

   /*
   * @cover RuleCriteria::match
   */
   public function testMatchRegex() {
      $criteria = new RuleCriteria();

      $criteria->fields = ['id'        => 1,
                           'rules_id'  => 1,
                           'criteria'  => 'name',
                           'condition' => Rule::REGEX_MATCH,
                           'pattern'   => '/Mozilla Firefox (.*)/'
                          ];

      $results      = [];
      $regex_result = [];
      $this->assertTrue($criteria->match($criteria, 'Mozilla Firefox 52',
                                         $results, $regex_result));
      $this->assertEquals($results, ['name' => '/Mozilla Firefox (.*)/']);
      $this->assertEquals($regex_result, [0 => ['52']]);

      $this->assertFalse($criteria->match($criteria, 'Mozilla Thunderbird 52',
                                          $results, $regex_result));

      $criteria->fields = ['id'        => 1,
                           'rules_id'  => 1,
                           'criteria'  => 'name',
                           'condition' => Rule::REGEX_NOT_MATCH,
                           'pattern'   => '/Mozilla Firefox (.*)/'
                          ];

      $results      = [];
      $regex_result = [];

      $this->assertFalse($criteria->match($criteria, 'Mozilla Firefox 52',
                                          $results, $regex_result));
      $this->assertEquals($results, []);
      $this->assertEquals($regex_result, []);

      $this->assertTrue($criteria->match($criteria, 'Mozilla Thunderbird 52',
                                         $results, $regex_result));
      $this->assertEquals($results, ['name' => '/Mozilla Firefox (.*)/']);
      $this->assertEquals($regex_result, []);
   }

   /*
   * @cover RuleCriteria::match
   */
   public function testMatchConditionUnderNotUnder() {
      $this->Login();

      $criteria = new RuleCriteria();
      $location = new Location();

      $loc_1 = $location->import(['completename' => 'loc1',
                                  'entities_id' => 0, 'is_recursive' => 1]);
      $loc_2 = $location->import(['completename' => 'loc1 > sloc1',
                                  'entities_id' => 0, 'is_recursive' => 1,
                                  'locations_id' => $loc_1]);
      $loc_3 = $location->import(['completename' => 'loc3',
                                  'entities_id' => 0, 'is_recursive' => 1]);

      $criteria->fields = ['id'        => 1,
                           'rules_id'  => 1,
                           'criteria'  => 'locations_id',
                           'condition' => Rule::PATTERN_UNDER,
                           'pattern'   => $loc_1
                          ];

      $results      = [];
      $regex_result = [];
      $this->assertTrue($criteria->match($criteria, $loc_1,
                                         $results, $regex_result));

      $this->assertEmpty($results);
      $this->assertTrue($criteria->match($criteria, $loc_2,
                                         $results, $regex_result));

      $this->assertFalse($criteria->match($criteria, $loc_3,
                                          $results, $regex_result));

      $criteria->fields = ['id'        => 1,
                           'rules_id'  => 1,
                           'criteria'  => 'locations_id',
                           'condition' => Rule::PATTERN_NOT_UNDER,
                           'pattern'   => $loc_1
                          ];

      $results      = [];
      $regex_result = [];
      $this->assertTrue($criteria->match($criteria, $loc_3,
                                         $results, $regex_result));
      $this->assertEmpty($results);

      $this->assertFalse($criteria->match($criteria, $loc_2,
                                         $results, $regex_result));
      $this->assertEmpty($results);

   }


   /**
   * @cover RuleCriteria::getConditions
   */
   function testGetConditions() {
      $conditions = RuleCriteria::getConditions('RuleDictionnarySoftware');
      $this->assertEquals(10, count($conditions));

      $conditions = RuleCriteria::getConditions('RuleTicket', 'locations_id');
      $this->assertEquals(12, count($conditions));
   }

   /**
   * @cover RuleCriteria::getConditionByID
   */
   function testGetConditionByID() {
      $condition = RuleCriteria::getConditionByID(Rule::PATTERN_BEGIN, 'RuleTicket', 'locations_id');
      $this->assertEquals($condition, "starting with");

      $condition = RuleCriteria::getConditionByID(Rule::PATTERN_NOT_UNDER, 'RuleTicket', 'locations_id');
      $this->assertEquals($condition, "not under");

   }

   function updateDateMod($rules_id, $time) {
      global $DB;

      $query = "UPDATE `glpi_rules` SET `date_mod`='$time' WHERE `id`='$rules_id'";
      $DB->query($query);
   }
}
