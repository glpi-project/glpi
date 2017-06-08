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

namespace tests\units;

use \DbTestCase;

/* Test for inc/rulecriteria.class.php */

class RuleCriteria extends DbTestCase {

   public function testGetForbiddenStandardMassiveAction() {
      $criteria = new \RuleCriteria();
      $this->array($criteria->getForbiddenStandardMassiveAction())->hasSize(1);
   }

   public function testConstruct() {
      $criteria = new \RuleCriteria('RuleDictionnarySoftware');
      $this->string($criteria::$itemtype)->isIdenticalTo('RuleDictionnarySoftware');
   }

   public function testPost_getFromDB() {
      $rule     = new \Rule();
      $criteria = new \RuleCriteria();

      $rules_id = $rule->add(['name'        => 'Ignore import',
                              'is_active'   => 1,
                              'entities_id' => 0,
                              'sub_type'    => 'RuleDictionnarySoftware',
                              'match'       => \Rule::AND_MATCHING,
                              'condition'   => 0,
                              'description' => ''
                             ]);
      $this->integer((int)$rules_id)->isGreaterThan(0);

      $criteria_id = $criteria->add(['rules_id'  => $rules_id,
                                    'criteria'  => 'name',
                                    'condition' => \Rule::PATTERN_IS,
                                    'pattern'   => 'Mozilla Firefox 52'
                                 ]);
      $this->integer((int)$criteria_id)->isGreaterThan(0);

      $this->boolean($criteria->getFromDB($criteria_id))->isTrue();
      $this->string($criteria::$itemtype)->isIdenticalTo('RuleDictionnarySoftware');
   }

   public function testGetTypeName() {
      $this->string(\RuleCriteria::getTypeName(1))->isIdenticalTo('Criterion');
      $this->string(\RuleCriteria::getTypeName(\Session::getPluralNumber()))->isIdenticalTo('Criteria');
   }

   public function testRawTypeName() {
      $rule     = new \Rule();
      $criteria = new \RuleCriteria();

      $rules_id = $rule->add(['name'        => 'Ignore import',
                              'is_active'   => 1,
                              'entities_id' => 0,
                              'sub_type'    => 'RuleDictionnarySoftware',
                              'match'       => \Rule::AND_MATCHING,
                              'condition'   => 0,
                              'description' => ''
                           ]);
      $this->integer((int)$rules_id)->isGreaterThan(0);

      $criteria_id = $criteria->add(['rules_id'  => $rules_id,
                                     'criteria'  => 'name',
                                     'condition' => \Rule::PATTERN_IS,
                                     'pattern'   => 'Mozilla Firefox 52'
                                    ]);
      $this->integer((int)$criteria_id)->isGreaterThan(0);

      $this->boolean($criteria->getFromDB($criteria_id))->isTrue();
      $this->string($criteria->getRawName())->isIdenticalTo('SoftwareisMozilla Firefox 52');
   }

   public function testPost_addItem() {
      $this->Login();
      $rule     = new \Rule();
      $criteria = new \RuleCriteria();

      $rules_id = $rule->add(['name'        => 'Ignore import',
                              'is_active'   => 1,
                              'entities_id' => 0,
                              'sub_type'    => 'RuleDictionnarySoftware',
                              'match'       => \Rule::AND_MATCHING,
                              'condition'   => 0,
                              'description' => '',
                             ]);
      $this->integer((int)$rules_id)->isGreaterThan(0);

      $this->boolean($rule->getFromDB($rules_id))->isTrue();
      $this->updateDateMod($rules_id, '2017-03-31 00:00:00');

      $criteria_id = $criteria->add(['rules_id'  => $rules_id,
                                     'criteria'  => 'name',
                                     'condition' => \Rule::PATTERN_IS,
                                     'pattern'   => 'Mozilla Firefox 52'
                                    ]);
      $this->integer((int)$criteria_id)->isGreaterThan(0);

      $this->boolean($rule->getFromDB($rules_id))->isTrue();

      //By adding a critera, rule's date_mod must have been updated
      $this->boolean($rule->fields['date_mod'] > '2017-03-31 00:00:00')->isTrue();

   }

   public function testPost_purgeItem() {
      $this->Login();
      $rule     = new \Rule();
      $criteria = new \RuleCriteria();

      $rules_id = $rule->add(['name'        => 'Ignore import',
                              'is_active'   => 1,
                              'entities_id' => 0,
                              'sub_type'    => 'RuleDictionnarySoftware',
                              'match'       => \Rule::AND_MATCHING,
                              'condition'   => 0,
                              'description' => '',
                             ]);
      $this->integer((int)$rules_id)->isGreaterThan(0);

      $this->boolean($rule->getFromDB($rules_id))->isTrue();

      $criteria_id = $criteria->add(['rules_id'  => $rules_id,
                                     'criteria'  => 'name',
                                     'condition' => \Rule::PATTERN_IS,
                                     'pattern'   => 'Mozilla Firefox 52'
                                    ]);
      $this->integer((int)$criteria_id)->isGreaterThan(0);
      $this->updateDateMod($rules_id, '2017-03-31 00:00:00');

      $this->boolean($criteria->delete(['id' => $criteria_id], true))->isTrue();
      $this->boolean($rule->getFromDB($rules_id))->isTrue();

      //By adding a critera, rule's date_mod must have been updated
      $this->boolean($rule->fields['date_mod'] > '2017-03-31 00:00:00')->isTrue();

   }

   public function testPrepareInputForAdd() {
      $rule     = new \Rule();
      $criteria = new \RuleCriteria();

      $this->boolean($criteria->prepareInputForAdd('name'))->isFalse();

      $input    = ['rules_id' => 1, 'criteria' => 'name'];
      $this->boolean($criteria->prepareInputForAdd($input))->isFalse();

      $rules_id = $rule->add(['name'        => 'Ignore import',
                              'is_active'   => 1,
                              'entities_id' => 0,
                              'sub_type'    => 'RuleDictionnarySoftware',
                              'match'       => \Rule::AND_MATCHING,
                              'condition'   => 0,
                              'description' => '',
                             ]);
      $this->integer((int)$rules_id)->isGreaterThan(0);

      $this->boolean($rule->getFromDB($rules_id))->isTrue();

      $input    = ['rules_id' => $rules_id, 'criteria' => 'name'];
      $this->array($criteria->prepareInputForAdd($input))->isIdenticalTo($input);

      $input = ['rules_id' => 1];
      $this->boolean($criteria->prepareInputForAdd($input))->isFalse();
   }

   public function testGetSearchOptionsNew() {
      $criteria = new \RuleCriteria();
      $this->array($criteria->getSearchOptionsNew())->hasSize(3);
   }

   public function testGetRuleCriterias() {
      $rule     = new \Rule();
      $criteria = new \RuleCriteria();

      $rules_id = $rule->add(['name'        => 'Example rule',
                              'is_active'   => 1,
                              'entities_id' => 0,
                              'sub_type'    => 'RuleDictionnarySoftware',
                              'match'       => \Rule::AND_MATCHING,
                              'condition'   => 0,
                              'description' => '',
                             ]);
      $this->integer((int)$rules_id)->isGreaterThan(0);

      $this->integer(
         (int)$criteria->add([
            'rules_id'  => $rules_id,
            'criteria'  => 'name',
            'condition' => \Rule::PATTERN_IS,
            'pattern'   => 'Mozilla Firefox 52'
         ])
      )->isGreaterThan(0);

      $this->integer(
         (int)$criteria->add([
            'rules_id'  => $rules_id,
            'criteria'  => 'version',
            'condition' => \Rule::REGEX_NOT_MATCH,
            'pattern'   => '/(.*)/'
         ])
      )->isGreaterThan(0);

      //Get criteria for the newly created rule
      $result   = $criteria->getRuleCriterias($rules_id);
      $this->array($result)->hasSize(2);
      $this->string($result[0]->fields['criteria'])->isIdenticalTo('name');
      $this->string($result[1]->fields['criteria'])->isIdenticalTo('version');

      //Try to get criteria for a non existing rule
      $this->array($criteria->getRuleCriterias(100))->isEmpty();
   }

   public function testMatchConditionWildcardOfFind() {
      $criteria = new \RuleCriteria();

      $criteria->fields = ['id' => 1,
                           'rules_id'  => 1,
                           'criteria'  => 'name',
                           'condition' => \Rule::PATTERN_IS,
                           'pattern'   => \Rule::RULE_WILDCARD
                          ];

      $results      = [];
      $regex_result = [];
      $this->boolean(
         $criteria->match(
            $criteria,
            'Mozilla Firefox',
            $results,
            $regex_result
         )
      )->isTrue();

      $criteria->fields = ['id' => 1,
                           'rules_id'  => 1,
                           'criteria'  => 'name',
                           'condition' => \Rule::PATTERN_FIND,
                           'pattern'   => \Rule::RULE_WILDCARD
                          ];
      $this->boolean(
         $criteria->match(
            $criteria,
            'Mozilla Firefox',
            $results,
            $regex_result
         )
      )->isTrue();
   }

   public function testMatchConditionIsOrNot() {
      $criteria = new \RuleCriteria();

      $criteria->fields = ['id' => 1,
                           'rules_id'  => 1,
                           'criteria'  => 'name',
                           'condition' => \Rule::PATTERN_IS,
                           'pattern'   => 'Mozilla Firefox'
                          ];

      $results      = [];
      $regex_result = [];
      $this->boolean(
         $criteria->match(
            $criteria,
            'Mozilla Firefox',
            $results,
            $regex_result
         )
      )->isTrue();
      $this->string($results['name'])->isIdenticalTo('mozilla firefox');

      $results = [];
      $this->boolean(
         $criteria->match(
            $criteria,
            ['Mozilla Firefox', 'foo'],
            $results,
            $regex_result
         )
      )->isTrue();
      $this->string($results['name'])->isIdenticalTo('Mozilla Firefox');

      $results = [];
      $this->boolean(
         $criteria->match(
            $criteria,
            ['foo', 'bar'],
            $results,
            $regex_result
         )
      )->isFalse();
      $this->array($results)->isEmpty();

      $results = [];
      $this->boolean(
         $criteria->match(
            $criteria,
            'foo',
            $results,
            $regex_result
         )
      )->isFalse();
      $this->array($results)->isEmpty();

      $criteria->fields = ['id' => 1,
                           'rules_id'  => 1,
                           'criteria'  => 'name',
                           'condition' => \Rule::PATTERN_IS_NOT,
                           'pattern'   => 'Mozilla Firefox'
                          ];

      $results = [];
      $this->boolean(
         $criteria->match(
            $criteria,
            'foo',
            $results,
            $regex_result
         )
      )->isTrue();
      $this->string($results['name'])->isIdenticalTo('mozilla firefox');
   }

   public function testMatchConditionExistsOrNot() {
      $criteria = new \RuleCriteria();

      $criteria->fields = ['id' => 1,
                           'rules_id'  => 1,
                           'criteria'  => 'name',
                           'condition' => \Rule::PATTERN_EXISTS,
                           'pattern'   => ''
                          ];

      $results      = [];
      $regex_result = [];
      $this->boolean(
         $criteria->match(
            $criteria,
            'Mozilla Firefox',
            $results,
            $regex_result
         )
      )->isTrue();

      $this->array($results)->isEmpty();

      $results = [];
      $this->boolean(
         $criteria->match(
            $criteria,
            '',
            $results,
            $regex_result
         )
      )->isFalse();
      $this->array($results)->isEmpty();

      $results = [];
      $this->boolean(
         $criteria->match(
            $criteria,
            null,
            $results,
            $regex_result
         )
      )->isFalse();
      $this->array($results)->isEmpty();
   }

   public function testMatchContainsOrNot() {
      $criteria = new \RuleCriteria();

      $criteria->fields = ['id' => 1,
                           'rules_id'  => 1,
                           'criteria'  => 'name',
                           'condition' => \Rule::PATTERN_CONTAIN,
                           'pattern'   => 'Firefox'
                          ];

      $results      = [];
      $regex_result = [];
      $this->boolean(
         $criteria->match(
            $criteria,
            'Mozilla Firefox',
            $results,
            $regex_result
         )
      )->isTrue();
      $this->array($results)->isIdenticalTo(['name' => 'Firefox']);

      $this->boolean(
         $criteria->match(
            $criteria,
            'mozilla firefox',
            $results,
            $regex_result
         )
      )->isTrue();
      $this->array($results)->isIdenticalTo(['name' => 'Firefox']);

      $this->boolean(
         $criteria->match(
            $criteria,
            '',
            $results,
            $regex_result
         )
      )->isFalse();
      $this->array($results)->isIdenticalTo(['name' => 'Firefox']);

      $this->boolean(
         $criteria->match(
            $criteria,
            null,
            $results,
            $regex_result
         )
      )->isFalse();
      $this->array($results)->isIdenticalTo(['name' => 'Firefox']);

      $criteria->fields = ['id' => 1,
                           'rules_id'  => 1,
                           'criteria'  => 'name',
                           'condition' => \Rule::PATTERN_NOT_CONTAIN,
                           'pattern'   => 'Firefox'
                          ];

      $results      = [];
      $regex_result = [];
      $this->boolean(
         $criteria->match(
            $criteria,
            'Konqueror',
            $results,
            $regex_result
         )
      )->isTrue();

      $this->array($results)->isIdenticalTo(['name' => 'Firefox']);

   }

   public function testMatchBeginEnd() {
      $criteria = new \RuleCriteria();

      $criteria->fields = ['id'        => 1,
                           'rules_id'  => 1,
                           'criteria'  => 'name',
                           'condition' => \Rule::PATTERN_BEGIN,
                           'pattern'   => 'Mozilla'
                          ];

      $results      = [];
      $regex_result = [];
      $this->boolean(
         $criteria->match(
            $criteria,
            'Mozilla Firefox',
            $results,
            $regex_result
         )
      )->isTrue();
      $this->array($results)->isIdenticalTo(['name' => 'Mozilla']);

      $results      = [];
      $regex_result = [];
      $this->boolean(
         $criteria->match(
            $criteria,
            'mozilla firefox',
            $results,
            $regex_result
         )
      )->isTrue();
      $this->array($results)->isIdenticalTo(['name' => 'Mozilla']);

      $results      = [];
      $regex_result = [];
      $this->boolean(
         $criteria->match(
            $criteria,
            '',
            $results,
            $regex_result
         )
      )->isFalse();
      $this->array($results)->isIdenticalTo([]);

      $results      = [];
      $regex_result = [];
      $this->boolean(
         $criteria->match(
            $criteria,
            null,
            $results,
            $regex_result
         )
      )->isFalse();
      $this->array($results)->isIdenticalTo([]);

      $criteria->fields = ['id' => 1,
                           'rules_id'  => 1,
                           'criteria'  => 'name',
                           'condition' => \Rule::PATTERN_END,
                           'pattern'   => 'Firefox'
                          ];

      $results      = [];
      $regex_result = [];
      $this->boolean(
         $criteria->match(
            $criteria,
            'Mozilla Firefox',
            $results,
            $regex_result
         )
      )->isTrue();

      $this->array($results)->isIdenticalTo(['name' => 'Firefox']);

      $results      = [];
      $regex_result = [];
      $this->boolean(
         $criteria->match(
            $criteria,
            'mozilla firefox',
            $results,
            $regex_result
         )
      )->isTrue();

      $this->array($results)->isIdenticalTo(['name' => 'Firefox']);

      $results      = [];
      $regex_result = [];
      $this->boolean(
         $criteria->match(
            $criteria,
            '',
            $results,
            $regex_result
         )
      )->isFalse();

      $this->array($results)->isIdenticalTo([]);
   }

   public function testMatchRegex() {
      $criteria = new \RuleCriteria();

      $criteria->fields = ['id'        => 1,
                           'rules_id'  => 1,
                           'criteria'  => 'name',
                           'condition' => \Rule::REGEX_MATCH,
                           'pattern'   => '/Mozilla Firefox (.*)/'
                          ];

      $results      = [];
      $regex_result = [];
      $this->boolean(
         $criteria->match(
            $criteria,
            'Mozilla Firefox 52',
            $results,
            $regex_result
         )
      )->isTrue();
      $this->array($results)->isIdenticalTo(['name' => '/Mozilla Firefox (.*)/']);
      $this->array($regex_result)->isIdenticalTo([0 => ['52']]);

      $this->boolean(
         $criteria->match(
            $criteria,
            'Mozilla Thunderbird 52',
            $results,
            $regex_result
         )
      )->isFalse();

      $criteria->fields = ['id'        => 1,
                           'rules_id'  => 1,
                           'criteria'  => 'name',
                           'condition' => \Rule::REGEX_NOT_MATCH,
                           'pattern'   => '/Mozilla Firefox (.*)/'
                          ];

      $results      = [];
      $regex_result = [];

      $this->boolean(
         $criteria->match(
            $criteria,
            'Mozilla Firefox 52',
            $results,
            $regex_result
         )
      )->isFalse();
      $this->array($results)->isIdenticalTo([]);
      $this->array($regex_result)->isIdenticalTo([]);

      $this->boolean(
         $criteria->match(
            $criteria,
            'Mozilla Thunderbird 52',
            $results,
            $regex_result
         )
      )->isTrue();
      $this->array($results)->isIdenticalTo(['name' => '/Mozilla Firefox (.*)/']);
      $this->array($regex_result)->isIdenticalTo([]);
   }

   public function testMatchConditionUnderNotUnder() {
      $this->Login();

      $criteria = new \RuleCriteria();
      $location = new \Location();

      $loc_1 = $location->import(['completename' => 'loc1',
                                  'entities_id' => 0, 'is_recursive' => 1]);
      $this->integer($loc_1)->isGreaterThan(0);

      $loc_2 = $location->import(['completename' => 'loc1 > sloc1',
                                  'entities_id' => 0, 'is_recursive' => 1,
                                  'locations_id' => $loc_1]);
      $this->integer($loc_2)->isGreaterThan(0);

      $loc_3 = $location->import(['completename' => 'loc3',
                                  'entities_id' => 0, 'is_recursive' => 1]);
      $this->integer($loc_3)->isGreaterThan(0);

      $criteria->fields = ['id'        => 1,
                           'rules_id'  => 1,
                           'criteria'  => 'locations_id',
                           'condition' => \Rule::PATTERN_UNDER,
                           'pattern'   => $loc_1
                          ];

      $results      = [];
      $regex_result = [];
      $this->boolean(
         $criteria->match(
            $criteria,
            $loc_1,
            $results,
            $regex_result
         )
      )->isTrue();

      $this->array($results)->isEmpty();
      $this->boolean(
         $criteria->match(
            $criteria,
            $loc_2,
            $results,
            $regex_result
         )
      )->isTrue();

      $this->boolean(
         $criteria->match(
            $criteria,
            $loc_3,
            $results,
            $regex_result
         )
      )->isFalse();

      $criteria->fields = ['id'        => 1,
                           'rules_id'  => 1,
                           'criteria'  => 'locations_id',
                           'condition' => \Rule::PATTERN_NOT_UNDER,
                           'pattern'   => $loc_1
                          ];

      $results      = [];
      $regex_result = [];
      $this->boolean(
         $criteria->match(
            $criteria,
            $loc_3,
            $results,
            $regex_result
         )
      )->isTrue();
      $this->array($results)->isEmpty();

      $this->boolean(
         $criteria->match(
            $criteria,
            $loc_2,
            $results,
            $regex_result
         )
      )->isFalse();
      $this->array($results)->isEmpty();
   }

   function testGetConditions() {
      $conditions = \RuleCriteria::getConditions('RuleDictionnarySoftware');
      $this->array($conditions)->hasSize(10);

      $conditions = \RuleCriteria::getConditions('RuleTicket', 'locations_id');
      $this->array($conditions)->hasSize(12);
   }

   function testGetConditionByID() {
      $condition = \RuleCriteria::getConditionByID(
         \Rule::PATTERN_BEGIN,
         'RuleTicket',
         'locations_id'
      );
      $this->string($condition)->isIdenticalTo("starting with");

      $condition = \RuleCriteria::getConditionByID(
         \Rule::PATTERN_NOT_UNDER,
         'RuleTicket',
         'locations_id'
      );
      $this->string($condition)->isIdenticalTo("not under");
   }

   /**
    * Update rule modification date
    *
    * @param integer $rules_id Rule ID
    * @param string  $time     Time to set modification date to
    *
    * @return void
    */
   private function updateDateMod($rules_id, $time) {
      global $DB;

      $query = "UPDATE `glpi_rules` SET `date_mod`='$time' WHERE `id`='$rules_id'";
      $DB->query($query);
   }
}
