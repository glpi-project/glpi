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

/* Test for inc/dropdown.class.php */

class Dropdown extends DbTestCase {

   public function testShowLanguages() {

      $opt = [ 'display_emptychoice' => true, 'display' => false ];
      $out = \Dropdown::showLanguages('dropfoo', $opt);
      $this->string($out)
         ->contains("name='dropfoo'")
         ->contains("value='' selected")
         ->notContains("value='0'")
         ->contains("value='fr_FR'");

      $opt = ['display' => false, 'value' => 'cs_CZ', 'rand' => '1234'];
      $out = \Dropdown::showLanguages('language', $opt);
      $this->string($out)
         ->notContains("value=''")
         ->notContains("value='0'")
         ->contains("name='language' id='dropdown_language1234")
         ->contains("value='cs_CZ' selected")
         ->contains("value='fr_FR'");
   }

   public function dataTestImport() {
      return [
            // input,             name,  message
            [ [ ],                '',    'missing name'],
            [ [ 'name' => ''],    '',    'empty name'],
            [ [ 'name' => ' '],   '',    'space name'],
            [ [ 'name' => ' a '], 'a',   'simple name'],
            [ [ 'name' => 'foo'], 'foo', 'simple name'],
      ];
   }

   /**
    * @dataProvider dataTestImport
    */
   public function testImport($input, $result, $msg) {
      $id = \Dropdown::import('UserTitle', $input);
      if ($result) {
         $this->integer((int)$id)->isGreaterThan(0);
         $ut = new \UserTitle();
         $this->boolean($ut->getFromDB($id))->isTrue();
         $this->string($ut->getField('name'))->isIdenticalTo($result);
      } else {
         $this->integer((int)$id)->isLessThan(0);
      }
   }

   public function dataTestTreeImport() {
      return [
            // input,                                  name,    completename, message
            [ [ ],                                     '',      '',           'missing name'],
            [ [ 'name' => ''],                          '',     '',           'empty name'],
            [ [ 'name' => ' '],                         '',     '',           'space name'],
            [ [ 'name' => ' a '],                       'a',    'a',          'simple name'],
            [ [ 'name' => 'foo'],                       'foo',  'foo',        'simple name'],
            [ [ 'completename' => 'foo > bar'],         'bar',  'foo > bar',  'two names'],
            [ [ 'completename' => ' '],                 '',     '',           'only space'],
            [ [ 'completename' => '>'],                 '',     '',           'only >'],
            [ [ 'completename' => ' > '],               '',     '',           'only > and spaces'],
            [ [ 'completename' => 'foo>bar'],           'bar',  'foo > bar',  'two names with no space'],
            [ [ 'completename' => '>foo>>bar>'],        'bar',  'foo > bar',  'two names with additional >'],
            [ [ 'completename' => ' foo >   > bar > '], 'bar',  'foo > bar',  'two names with garbage'],
      ];
   }

   /**
    * @dataProvider dataTestTreeImport
    */
   public function testTreeImport($input, $result, $complete, $msg) {
      $input['entities_id'] = getItemByTypeName('Entity', '_test_root_entity', true);
      $id = \Dropdown::import('Location', $input);
      if ($result) {
         $this->integer((int)$id, $msg)->isGreaterThan(0);
         $ut = new \Location();
         $this->boolean($ut->getFromDB($id))->isTrue();
         $this->string($ut->getField('name'))->isIdenticalTo($result);
         $this->string($ut->getField('completename'))->isIdenticalTo($complete);
      } else {
         $this->integer((int)$id)->isLessThanOrEqualTo(0);
      }
   }

   public function testGetDropdownName() {
      global $CFG_GLPI;

      $ret = \Dropdown::getDropdownName('not_a_known_table', 1);
      $this->string($ret)->isIdenticalTo('&nbsp;');

      $cat = getItemByTypeName('TaskCategory', '_cat_1');

      $subCat = getItemByTypeName('TaskCategory', '_subcat_1');

      // basic test returns string only
      $expected = $cat->fields['name']." > ".$subCat->fields['name'];
      $ret = \Dropdown::getDropdownName('glpi_taskcategories', $subCat->getID());
      $this->string($ret)->isIdenticalTo($expected);

      // test of return with comments
      $expected = ['name'    => $cat->fields['name']." > ".$subCat->fields['name'],
                        'comment' => "<span class='b'>Complete name</span>: ".$cat->fields['name']." > "
                                    .$subCat->fields['name']."<br><span class='b'>&nbsp;Comments&nbsp;</span>"
                                    .$subCat->fields['comment']];
      $ret = \Dropdown::getDropdownName( 'glpi_taskcategories', $subCat->getID(), true );
      $this->array($ret)->isIdenticalTo($expected);

      // test of return without $tooltip
      $expected = ['name'    => $cat->fields['name']." > ".$subCat->fields['name'],
                        'comment' => $subCat->fields['comment']];
      $ret = \Dropdown::getDropdownName( 'glpi_taskcategories', $subCat->getID(), true, true, false );
      $this->array($ret)->isIdenticalTo($expected);

      // test of return with translations
      $CFG_GLPI['translate_dropdowns'] = 1;
      $_SESSION["glpilanguage"] = \Session::loadLanguage( 'fr_FR' );
      $_SESSION['glpi_dropdowntranslations'] = \DropdownTranslation::getAvailableTranslations($_SESSION["glpilanguage"]);
      $expected = ['name'    => 'FR - _cat_1 > FR - _subcat_1',
                        'comment' => 'FR - Commentaire pour sous-catégorie _subcat_1'];
      $ret = \Dropdown::getDropdownName( 'glpi_taskcategories', $subCat->getID(), true, true, false );
      // switch back to default language
      $_SESSION["glpilanguage"] = \Session::loadLanguage('en_GB');
      $this->array($ret)->isIdenticalTo($expected);

      ////////////////////////////////
      // test for other dropdown types
      ////////////////////////////////

      ///////////
      // Computer
      $computer = getItemByTypeName( 'Computer', '_test_pc01' );
      $ret = \Dropdown::getDropdownName( 'glpi_computers', $computer->getID());
      $this->string($ret)->isIdenticalTo($computer->getName());

      $expected = ['name'    => $computer->getName(),
                        'comment' => $computer->fields['comment']];
      $ret = \Dropdown::getDropdownName( 'glpi_computers', $computer->getID(), true);
      $this->array($ret)->isIdenticalTo($expected);

      //////////
      // Contact
      $contact = getItemByTypeName( 'Contact', '_contact01_name' );
      $expected = $contact->getName();
      $ret = \Dropdown::getDropdownName( 'glpi_contacts', $contact->getID());
      $this->string($ret)->isIdenticalTo($expected);

      // test of return with comments
      $expected = ['name'    => $contact->getName(),
                        'comment' => "Comment for contact _contact01_name<br><span class='b'>".
                                    "Phone: </span>0123456789<br><span class='b'>Phone 2: </span>0123456788<br><span class='b'>".
                                    "Mobile phone: </span>0623456789<br><span class='b'>Fax: </span>0123456787<br>".
                                    "<span class='b'>Email: </span>_contact01_firstname._contact01_name@glpi.com"];
      $ret = \Dropdown::getDropdownName( 'glpi_contacts', $contact->getID(), true );
      $this->array($ret)->isIdenticalTo($expected);

      // test of return without $tooltip
      $expected = ['name'    => $contact->getName(),
                        'comment' => $contact->fields['comment']];
      $ret = \Dropdown::getDropdownName( 'glpi_contacts', $contact->getID(), true, true, false );
      $this->array($ret)->isIdenticalTo($expected);

      ///////////
      // Supplier
      $supplier = getItemByTypeName( 'Supplier', '_suplier01_name' );
      $expected = $supplier->getName();
      $ret = \Dropdown::getDropdownName( 'glpi_suppliers', $supplier->getID());
      $this->string($ret)->isIdenticalTo($expected);

      // test of return with comments
      $expected = ['name'    => $supplier->getName(),
                        'comment' => "Comment for supplier _suplier01_name<br><span class='b'>Phone: </span>0123456789<br>".
                                     "<span class='b'>Fax: </span>0123456787<br><span class='b'>Email: </span>info@_supplier01_name.com"];
      $ret = \Dropdown::getDropdownName( 'glpi_suppliers', $supplier->getID(), true );
      $this->array($ret)->isIdenticalTo($expected);

      // test of return without $tooltip
      $expected = ['name'    => $supplier->getName(),
                        'comment' => $supplier->fields['comment']];
      $ret = \Dropdown::getDropdownName( 'glpi_suppliers', $supplier->getID(), true, true, false );
      $this->array($ret)->isIdenticalTo($expected);

      ///////////
      // Netpoint
      $netpoint = getItemByTypeName( 'Netpoint', '_netpoint01' );
      $location = getItemByTypeName( 'Location', '_location01' );
      $expected = $netpoint->getName()." (".$location->getName().")";
      $ret = \Dropdown::getDropdownName( 'glpi_netpoints', $netpoint->getID());
      $this->string($ret)->isIdenticalTo($expected);

      // test of return with comments
      $expected = ['name'    => $expected,
                        'comment' => "Comment for netpoint _netpoint01"];
      $ret = \Dropdown::getDropdownName( 'glpi_netpoints', $netpoint->getID(), true );
      $this->array($ret)->isIdenticalTo($expected);

      // test of return without $tooltip
      $ret = \Dropdown::getDropdownName( 'glpi_netpoints', $netpoint->getID(), true, true, false );
      $this->array($ret)->isIdenticalTo($expected);

      ///////////
      // Budget
      $budget = getItemByTypeName( 'Budget', '_budget01' );
      $expected = $budget->getName();
      $ret = \Dropdown::getDropdownName( 'glpi_budgets', $budget->getID());
      $this->string($ret)->isIdenticalTo($expected);

      // test of return with comments
      $expected = ['name'    =>  $budget->getName(),
                        'comment' => "Comment for budget _budget01<br><span class='b'>Location</span>: ".
                                       "_location01<br><span class='b'>Type</span>: _budgettype01<br><span class='b'>".
                                       "Start date</span>: 2016-10-18 <br><span class='b'>End date</span>: 2016-12-31 "];
      $ret = \Dropdown::getDropdownName( 'glpi_budgets', $budget->getID(), true );
      $this->array($ret)->isIdenticalTo($expected);

      // test of return without $tooltip
      $expected = ['name'    => $budget->getName(),
                        'comment' => $budget->fields['comment']];
      $ret = \Dropdown::getDropdownName( 'glpi_budgets', $budget->getID(), true, true, false );
      $this->array($ret)->isIdenticalTo($expected);
   }

   public function testGetDropdownNetpoint() {
      $netpoint = getItemByTypeName( 'Netpoint', '_netpoint01' );
      $location = getItemByTypeName( 'Location', '_location01' );
      $ret = \Dropdown::getDropdownNetpoint([], false);
      $this->array($ret)->hasKeys(['count', 'results'])->integer['count']->isIdenticalTo(1);
      $this->array($ret['results'])->isIdenticalTo([
         [
            'id'     => 0,
            'text'   => '-----'
         ], [
            'id'     => $netpoint->fields['id'],
            'text'   => $netpoint->getName() . ' (' . $location->getName() . ')',
            'title'  =>  $netpoint->getName() . ' - ' . $location->getName() . ' - ' . $netpoint->fields['comment']
         ]
      ]);
   }

   public function dataGetValueWithUnit() {
      return [
            [1,         'auto',        '1024 Kio'],
            [1025,      'auto',        '1 Gio'],
            ['1 025',   'auto',        '1 Gio'],
            [1,         'year',        '1 year'],
            [2,         'year',        '2 years'],
            [3,         '%',           '3%'],
            ['foo',     'bar',         'foo bar'],
            [1,         'month',       '1 month'],
            [2,         'month',       '2 months'],
            ['any',     '',            'any'],
            [1,         'day',         '1 day'],
            [2,         'day',         '2 days'],
            [1,         'hour',        '1 hour'],
            [2,         'hour',        '2 hours'],
            [1,         'minute',      '1 minute'],
            [2,         'minute',      '2 minutes'],
            [1,         'second',      '1 second'],
            [2,         'second',      '2 seconds'],
            [1,         'millisecond', '1 millisecond'],
            [2,         'millisecond', '2 milliseconds'],
      ];
   }

   /**
    * @dataProvider dataGetValueWithUnit
    */
   public function testGetValueWithUnit($input, $unit, $expected) {
      $this->string(\Dropdown::getValueWithUnit($input, $unit))->isIdenticalTo($expected);
   }

   protected function getDropdownValueProvider() {
      return [
         [
            'params' => [
               'display_emptychoice'   => 0,
               'itemtype'              => 'TaskCategory'
            ],
            'expected'  => [
               'results' => [
                  0 => [
                     'text'      => 'Root entity',
                     'children'  => [
                        0 => [
                           'id'             => getItemByTypeName('TaskCategory', '_cat_1', true),
                           'text'           => '_cat_1',
                           'level'          => 1,
                           'title'          => '_cat_1 - Comment for category _cat_1',
                           'selection_text' => '_cat_1',
                        ],
                        1 => [
                           'id'             => getItemByTypeName('TaskCategory', '_subcat_1', true),
                           'text'           => '_subcat_1',
                           'level'          => 2,
                           'title'          => '_cat_1 > _subcat_1 - Comment for sub-category _subcat_1',
                           'selection_text' => '_cat_1 > _subcat_1',
                        ]
                     ]
                  ]
               ],
               'count' => 2
            ]
         ], [
            'params' => [
               'display_emptychoice'   => 0,
               'itemtype'              => 'TaskCategory',
               'searchText'            => 'subcat'
            ],
            'expected'  => [
               'results' => [
                  0 => [
                     'text'      => 'Root entity',
                     'children'  => [
                        0 => [
                           'id'     => getItemByTypeName('TaskCategory', '_cat_1', true),
                           'text'   => '_cat_1',
                           'level'  => 1,
                           'disabled' => true
                        ],
                        1 => [
                           'id'             => getItemByTypeName('TaskCategory', '_subcat_1', true),
                           'text'           => '_subcat_1',
                           'level'          => 2,
                           'title'          => '_cat_1 > _subcat_1 - Comment for sub-category _subcat_1',
                           'selection_text' => '_cat_1 > _subcat_1',
                        ]
                     ]
                  ]
               ],
               'count' => 1
            ]
         ], [
            'params' => [
               'display_emptychoice'   => 1,
               'emptylabel'            => 'EEEEEE',
               'itemtype'              => 'TaskCategory'
            ],
            'expected'  => [
               'results' => [
                  0 => [
                     'id'        => 0,
                     'text'      => 'EEEEEE'
                  ],
                  1 => [
                     'text'      => 'Root entity',
                     'children'  => [
                        0 => [
                           'id'             => getItemByTypeName('TaskCategory', '_cat_1', true),
                           'text'           => '_cat_1',
                           'level'          => 1,
                           'title'          => '_cat_1 - Comment for category _cat_1',
                           'selection_text' => '_cat_1',
                        ],
                        1 => [
                           'id'             => getItemByTypeName('TaskCategory', '_subcat_1', true),
                           'text'           => '_subcat_1',
                           'level'          => 2,
                           'title'          => '_cat_1 > _subcat_1 - Comment for sub-category _subcat_1',
                           'selection_text' => '_cat_1 > _subcat_1',
                        ]
                     ]
                  ]
               ],
               'count' => 2
            ]
         ], [
            'params' => [
               'display_emptychoice'   => 0,
               'itemtype'              => 'TaskCategory',
               'used'                  => [getItemByTypeName('TaskCategory', '_cat_1', true)]
            ],
            'expected'  => [
               'results' => [
                  0 => [
                     'text'      => 'Root entity',
                     'children'  => [
                        0 => [
                           'id'     => getItemByTypeName('TaskCategory', '_cat_1', true),
                           'text'   => '_cat_1',
                           'level'  => 1,
                           'disabled' => true
                        ],
                        1 => [
                           'id'             => getItemByTypeName('TaskCategory', '_subcat_1', true),
                           'text'           => '_subcat_1',
                           'level'          => 2,
                           'title'          => '_cat_1 > _subcat_1 - Comment for sub-category _subcat_1',
                           'selection_text' => '_cat_1 > _subcat_1',
                        ]
                     ]
                  ]
               ],
               'count' => 1
            ]
         ], [
            'params' => [
               'display_emptychoice'   => 0,
               'itemtype'              => 'Computer',
               'entity_restrict'       => getItemByTypeName('Entity', '_test_child_2', true)
            ],
            'expected'  => [
               'results'   => [
                  0 => [
                     'text'      => 'Root entity > _test_root_entity > _test_child_2',
                     'children'  => [
                        0 => [
                           'id'     => getItemByTypeName('Computer', '_test_pc21', true),
                           'text'   => '_test_pc21',
                           'title'  => '_test_pc21',
                        ],
                        1 => [
                           'id'     => getItemByTypeName('Computer', '_test_pc22', true),
                           'text'   => '_test_pc22',
                           'title'  => '_test_pc22',
                        ]
                     ]
                  ]
               ],
               'count'     => 2
            ]
         ], [
            'params' => [
               'display_emptychoice'   => 0,
               'itemtype'              => 'Computer',
               'entity_restrict'       => '[' . getItemByTypeName('Entity', '_test_child_2', true) .']'
            ],
            'expected'  => [
               'results'   => [
                  0 => [
                     'text'      => 'Root entity > _test_root_entity > _test_child_2',
                     'children'  => [
                        0 => [
                           'id'     => getItemByTypeName('Computer', '_test_pc21', true),
                           'text'   => '_test_pc21',
                           'title'  => '_test_pc21',
                        ],
                        1 => [
                           'id'     => getItemByTypeName('Computer', '_test_pc22', true),
                           'text'   => '_test_pc22',
                           'title'  => '_test_pc22',
                        ]
                     ]
                  ]
               ],
               'count'     => 2
            ]
         ], [
            'params' => [
               'display_emptychoice'   => 0,
               'itemtype'              => 'Computer',
               'entity_restrict'       => getItemByTypeName('Entity', '_test_child_2', true),
               'searchText'            => '22'
            ],
            'expected'  => [
               'results'   => [
                  0 => [
                     'text'      => 'Root entity > _test_root_entity > _test_child_2',
                     'children'  => [
                        0 => [
                           'id'     => getItemByTypeName('Computer', '_test_pc22', true),
                           'text'   => '_test_pc22',
                           'title'  => '_test_pc22',
                        ]
                     ]
                  ]
               ],
               'count'     => 1
            ]
         ], [
            'params' => [
               'display_emptychoice'   => 0,
               'itemtype'              => 'TaskCategory',
               'searchText'            => 'subcat',
               'toadd'                 => ['key' => 'value']
            ],
            'expected'  => [
               'results' => [
                  0 => [
                     'id'     => 'key',
                     'text'   => 'value'
                  ],
                  1 => [
                     'text'      => 'Root entity',
                     'children'  => [
                        0 => [
                           'id'     => getItemByTypeName('TaskCategory', '_cat_1', true),
                           'text'   => '_cat_1',
                           'level'  => 1,
                           'disabled' => true
                        ],
                        1 => [
                           'id'             => getItemByTypeName('TaskCategory', '_subcat_1', true),
                           'text'           => '_subcat_1',
                           'level'          => 2,
                           'title'          => '_cat_1 > _subcat_1 - Comment for sub-category _subcat_1',
                           'selection_text' => '_cat_1 > _subcat_1',
                        ]
                     ]
                  ]
               ],
               'count' => 1
            ]
         ], [
            'params' => [
               'display_emptychoice'   => 0,
               'itemtype'              => 'TaskCategory',
               'searchText'            => 'subcat'
            ],
            'expected'  => [
               'results' => [
                  0 => [
                     'text'      => 'Root entity',
                     'children'  => [
                        0 => [
                           'id'             => getItemByTypeName('TaskCategory', '_subcat_1', true),
                           'text'           => '_cat_1 > _subcat_1',
                           'level'          => 0,
                           'title'          => '_cat_1 > _subcat_1 - Comment for sub-category _subcat_1',
                           'selection_text' => '_cat_1 > _subcat_1',
                        ]
                     ]
                  ]
               ],
               'count' => 1
            ],
            'session_params' => [
               'glpiuse_flat_dropdowntree' => true
            ]
         ], [
            'params' => [
               'display_emptychoice'   => 0,
               'itemtype'              => 'TaskCategory'
            ],
            'expected'  => [
               'results' => [
                  0 => [
                     'text'      => 'Root entity',
                     'children'  => [
                        0 => [
                           'id'             => getItemByTypeName('TaskCategory', '_cat_1', true),
                           'text'           => '_cat_1',
                           'level'          => 0,
                           'title'          => '_cat_1 - Comment for category _cat_1',
                           'selection_text' => '_cat_1',
                        ],
                        1 => [
                           'id'             => getItemByTypeName('TaskCategory', '_subcat_1', true),
                           'text'           => '_cat_1 > _subcat_1',
                           'level'          => 0,
                           'title'          => '_cat_1 > _subcat_1 - Comment for sub-category _subcat_1',
                           'selection_text' => '_cat_1 > _subcat_1',
                        ]
                     ]
                  ]
               ],
               'count' => 2
            ],
            'session_params' => [
               'glpiuse_flat_dropdowntree' => true
            ]
         ], [
            'params' => [
               'display_emptychoice'   => 0,
               'itemtype'              => 'TaskCategory',
               'searchText'            => 'subcat',
               'permit_select_parent'  => true
            ],
            'expected'  => [
               'results' => [
                  0 => [
                     'text'      => 'Root entity',
                     'children'  => [
                        0 => [
                           'id'             => getItemByTypeName('TaskCategory', '_cat_1', true),
                           'text'           => '_cat_1',
                           'level'          => 1,
                           'title'          => '_cat_1 - Comment for category _cat_1',
                           'selection_text' => '_cat_1',
                        ],
                        1 => [
                           'id'             => getItemByTypeName('TaskCategory', '_subcat_1', true),
                           'text'           => '_subcat_1',
                           'level'          => 2,
                           'title'          => '_cat_1 > _subcat_1 - Comment for sub-category _subcat_1',
                           'selection_text' => '_cat_1 > _subcat_1',
                        ]
                     ]
                  ]
               ],
               'count' => 1
            ]
         ]
      ];
   }

   /**
    * @dataProvider getDropdownValueProvider
    */
   public function testGetDropdownValue($params, $expected, $session_params = []) {
      $bkp_params = [];
      //set session params if any
      if (count($session_params)) {
         foreach ($session_params as $param => $value) {
            if (isset($_SESSION[$param])) {
               $bkp_params[$param] = $_SESSION[$param];
            }
            $_SESSION[$param] = $value;
         }
      }

      $result = \Dropdown::getDropdownValue($params, false);

      //reset session params before executing test
      if (count($session_params)) {
         foreach ($session_params as $param => $value) {
            if (isset($bkp_params[$param])) {
               $_SESSION[$param] = $bkp_params[$param];
            } else {
               unset($_SESSION[$param]);
            }
         }
      }

      $this->array($result)->isIdenticalTo($expected);
   }

   protected function getDropdownConnectProvider() {
      return [
         [
            'params'    => [
               'fromtype'  => 'Computer',
               'itemtype'  => 'Printer'
            ],
            'expected'  => [
               'results' => [
                  0 => [
                     'id' => 0,
                     'text' => '-----',
                  ],
                  1 => [
                     'text' => 'Root entity > _test_root_entity',
                     'children' => [
                        0 => [
                           'id'     => getItemByTypeName('Printer', '_test_printer_all', true),
                           'text'   => '_test_printer_all',
                        ],
                        1 => [
                           'id'     => getItemByTypeName('Printer', '_test_printer_ent0', true),
                           'text'   => '_test_printer_ent0',
                        ]
                     ]
                  ],
                  2 => [
                     'text' => 'Root entity > _test_root_entity > _test_child_1',
                     'children' => [
                        0 => [
                           'id'     => getItemByTypeName('Printer', '_test_printer_ent1', true),
                           'text'   => '_test_printer_ent1',
                        ]
                     ]
                  ],
                  3 => [
                     'text' => 'Root entity > _test_root_entity > _test_child_2',
                     'children' => [
                        0 => [
                           'id'     => getItemByTypeName('Printer', '_test_printer_ent2', true),
                           'text'   => '_test_printer_ent2',
                        ]
                     ]
                  ]
               ]
            ]
         ], [
            'params'    => [
               'fromtype'  => 'Computer',
               'itemtype'  => 'Printer',
               'used'      => [
                  'Printer' => [
                     getItemByTypeName('Printer', '_test_printer_ent0', true),
                     getItemByTypeName('Printer', '_test_printer_ent2', true)
                  ]
               ]
            ],
            'expected'  => [
               'results' => [
                  0 => [
                     'id' => 0,
                     'text' => '-----',
                  ],
                  1 => [
                     'text' => 'Root entity > _test_root_entity',
                     'children' => [
                        0 => [
                           'id'     => getItemByTypeName('Printer', '_test_printer_all', true),
                           'text'   => '_test_printer_all',
                        ]
                     ]
                  ],
                  2 => [
                     'text' => 'Root entity > _test_root_entity > _test_child_1',
                     'children' => [
                        0 => [
                           'id'     => getItemByTypeName('Printer', '_test_printer_ent1', true),
                           'text'   => '_test_printer_ent1',
                        ]
                     ]
                  ]
               ]
            ]
         ], [
            'params'    => [
               'fromtype'     => 'Computer',
               'itemtype'     => 'Printer',
               'searchText'   => 'ent0'
            ],
            'expected'  => [
               'results' => [
                  0 => [
                     'text' => 'Root entity > _test_root_entity',
                     'children' => [
                        0 => [
                           'id'     => getItemByTypeName('Printer', '_test_printer_ent0', true),
                           'text'   => '_test_printer_ent0',
                        ]
                     ]
                  ]
               ]
            ]
         ], [
            'params'    => [
               'fromtype'     => 'Computer',
               'itemtype'     => 'Printer',
               'searchText'   => 'ent0'
            ],
            'expected'  => [
               'results' => [
                  0 => [
                     'text' => 'Root entity > _test_root_entity',
                     'children' => [
                        0 => [
                           'id'     => getItemByTypeName('Printer', '_test_printer_ent0', true),
                           'text'   => '_test_printer_ent0 (' .getItemByTypeName('Printer', '_test_printer_ent0', true) . ')',
                        ]
                     ]
                  ]
               ]
            ],
            'session_params' => [
               'glpiis_ids_visible' => true
            ]
         ]
      ];
   }

   /**
    * @dataProvider getDropdownConnectProvider
    */
   public function testGetDropdownConnect($params, $expected, $session_params = []) {
      $this->login();

      $bkp_params = [];
      //set session params if any
      if (count($session_params)) {
         foreach ($session_params as $param => $value) {
            if (isset($_SESSION[$param])) {
               $bkp_params[$param] = $_SESSION[$param];
            }
            $_SESSION[$param] = $value;
         }
      }

      $result = \Dropdown::getDropdownConnect($params, false);

      //reset session params before executing test
      if (count($session_params)) {
         foreach ($session_params as $param => $value) {
            if (isset($bkp_params[$param])) {
               $_SESSION[$param] = $bkp_params[$param];
            } else {
               unset($_SESSION[$param]);
            }
         }
      }

      $this->array($result)->isIdenticalTo($expected);
   }

   protected function getDropdownNumberProvider() {
      return [
         [
            'params'    => [],
            'expected'  => [
               'results'   => [
                  0 => [
                     'id'     => 1,
                     'text'   => '1'
                  ],
                  1 => [
                     'id'     => 2,
                     'text'   => '2'
                  ],
                  2 => [
                     'id'     => 3,
                     'text'   => '3'
                  ],
                  3 => [
                     'id'     => 4,
                     'text'   => '4'
                  ],
                  4 => [
                     'id'     => 5,
                     'text'   => '5'
                  ],
                  5 => [
                     'id'     => 6,
                     'text'   => '6'
                  ],
                  6 => [
                     'id'     => 7,
                     'text'   => '7'
                  ],
                  7 => [
                     'id'     => 8,
                     'text'   => '8'
                  ],
                  8 => [
                     'id'     => 9,
                     'text'   => '9'
                  ],
                  9 => [
                     'id'     => 10,
                     'text'   => '10'
                  ]
               ],
               'count'     => 10
            ]
         ], [
            'params'    => [
               'min'    => 10,
               'max'    => 30,
               'step'   => 10
            ],
            'expected'  => [
               'results'   => [
                  0 => [
                     'id'     => 10,
                     'text'   => '10'
                  ],
                  1 => [
                     'id'     => 20,
                     'text'   => '20'
                  ],
                  2 => [
                     'id'     => 30,
                     'text'   => '30'
                  ]
               ],
               'count'     => 3
            ]
         ], [
            'params'    => [
               'min'    => 10,
               'max'    => 30,
               'step'   => 10,
               'used'   => [20]
            ],
            'expected'  => [
               'results'   => [
                  0 => [
                     'id'     => 10,
                     'text'   => '10'
                  ],
                  1 => [
                     'id'     => 30,
                     'text'   => '30'
                  ]
               ],
               'count'     => 2
            ]
         ], [
            'params'    => [
               'min'    => 10,
               'max'    => 30,
               'step'   => 10,
               'used'   => [20],
               'toadd'  => [5 => 'five']
            ],
            'expected'  => [
               'results'   => [
                  0 => [
                     'id'     => 5,
                     'text'   =>'five'
                  ],
                  1 => [
                     'id'     => 10,
                     'text'   => '10'
                  ],
                  2 => [
                     'id'     => 30,
                     'text'   => '30'
                  ]
               ],
               'count'     => 2
            ]
         ], [
            'params'    => [
               'min'    => 10,
               'max'    => 30,
               'step'   => 10,
               'used'   => [20],
               'unit'   => 'second'
            ],
            'expected'  => [
               'results'   => [
                  0 => [
                     'id'     => 10,
                     'text'   => '10 seconds'
                  ],
                  1 => [
                     'id'     => 30,
                     'text'   => '30 seconds'
                  ]
               ],
               'count'     => 2
            ]
         ]
      ];
   }

   /**
    * @dataProvider getDropdownNumberProvider
    */
   public function testGetDropdownNumber($params, $expected) {
      global $CFG_GLPI;
      $orig_max = $CFG_GLPI['dropdown_max'];
      $CFG_GLPI['dropdown_max'] = 10;
      $result = \Dropdown::getDropdownNumber($params, false);
      $CFG_GLPI['dropdown_max'] = $orig_max;
      $this->array($result)->isIdenticalTo($expected);
   }

   protected function getDropdownUsersProvider() {
      return [
         [
            'params'    => [],
            'expected'  => [
               'results' => [
                  0 => [
                     'id'     => 0,
                     'text'   => '-----',
                  ],
                  1 => [
                     'id'     => (int)getItemByTypeName('user', '_test_user', true),
                     'text'   => '_test_user',
                     'title'  => '_test_user - _test_user',
                  ],
                  2 => [
                     'id'     => (int)getItemByTypeName('User', 'glpi', true),
                     'text'   => 'glpi',
                     'title'  => 'glpi - glpi',
                  ],
                  3 => [
                     'id'     => (int)getItemByTypeName('User', 'normal', true),
                     'text'   => 'normal',
                     'title'  => 'normal - normal',
                  ],
                  4 => [
                     'id'     => (int)getItemByTypeName('User', 'post-only', true),
                     'text'   => 'post-only',
                     'title'  => 'post-only - post-only',
                  ],
                  5 => [
                     'id'     => (int)getItemByTypeName('User', 'tech', true),
                     'text'   => 'tech',
                     'title'  => 'tech - tech',
                  ]
               ],
               'count' => 5
            ]
         ], [
            'params'    => [
               'used'   => [
                  getItemByTypeName('User', 'glpi', true),
                  getItemByTypeName('User', 'tech', true)
               ]
            ],
            'expected'  => [
               'results' => [
                  0 => [
                     'id'     => 0,
                     'text'   => '-----',
                  ],
                  1 => [
                     'id'     => (int)getItemByTypeName('User', '_test_user', true),
                     'text'   => '_test_user',
                     'title'  => '_test_user - _test_user',
                  ],
                  2 => [
                     'id'     => (int)getItemByTypeName('User', 'normal', true),
                     'text'   => 'normal',
                     'title'  => 'normal - normal',
                  ],
                  3 => [
                     'id'     => (int)getItemByTypeName('User', 'post-only', true),
                     'text'   => 'post-only',
                     'title'  => 'post-only - post-only',
                  ]
               ],
               'count' => 3
            ]
         ], [
            'params'    => [
               'all'    => true,
               'used'   => [
                  getItemByTypeName('User', 'glpi', true),
                  getItemByTypeName('User', 'tech', true),
                  getItemByTypeName('User', 'normal', true),
                  getItemByTypeName('User', 'post-only', true)
               ]
            ],
            'expected'  => [
               'results' => [
                  0 => [
                     'id'     => 0,
                     'text'   => 'All',
                  ],
                  1 => [
                     'id'     => (int)getItemByTypeName('User', '_test_user', true),
                     'text'   => '_test_user',
                     'title'  => '_test_user - _test_user',
                  ]
               ],
               'count' => 1
            ]
         ]
      ];
   }

   /**
    * @dataProvider getDropdownUsersProvider
    */
   public function testGetDropdownUsers($params, $expected) {
      $result = \Dropdown::getDropdownUsers($params, false);
      $this->array($result)->isIdenticalTo($expected);
   }

   /**
    * Test getDropdownValue with paginated results on
    * an CommonTreeDropdown
    *
    * @return void
    */
   public function testGetDropdownValuePaginate() {
      //let's add some content in Locations
      $location = new \Location();
      for ($i = 0; $i <= 20; ++$i) {
         $this->integer(
            (int)$location->add([
               'name'   => "Test location $i"
            ])
         )->isGreaterThan(0);
      }

      $post = [
         'itemtype'              => $location::getType(),
         'display_emptychoice'   => true,
         'entity_restrict'       => 0,
         'page'                  => 1,
         'page_limit'            => 10
      ];
      $values = \Dropdown::getDropdownValue($post);
      $values = (array)json_decode($values);

      $this->array($values)
         ->integer['count']->isEqualTo(10)
         ->array['results']
            ->hasSize(2);

      $results = (array)$values['results'];
      $this->array((array)$results[0])
         ->isIdenticalTo([
            'id'     => 0,
            'text'   => '-----'
         ]);

      $list_results = (array)$results[1];
      $this->array($list_results)
         ->hasSize(2)
         ->string['text']->isIdenticalTo('Root entity');

      $children = (array)$list_results['children'];
      $this->array($children)->hasSize(10);
      $this->array((array)$children[0])
         ->hasKeys([
            'id',
            'text',
            'level',
            'title',
            'selection_text'
         ]);

      $post['page'] = 2;
      $values = \Dropdown::getDropdownValue($post);
      $values = (array)json_decode($values);

      $this->array($values)
         ->integer['count']->isEqualTo(10);

      $this->array($values['results'])->hasSize(10);
      $this->array((array)$values['results'][0])
         ->hasKeys([
            'id',
            'text',
            'level',
            'title',
            'selection_text'
         ]);

      //use a condition
      $post = [
         'itemtype'              => $location::getType(),
         'condition'             => '`name` LIKE "%3%"',
         'display_emptychoice'   => true,
         'entity_restrict'       => 0,
         'page'                  => 1,
         'page_limit'            => 10
      ];
      $_SESSION['glpicondition'][$post['condition']] = $post['condition'];
      $values = \Dropdown::getDropdownValue($post);
      $values = (array)json_decode($values);

      $this->array($values)
         ->integer['count']->isEqualTo(2)
         ->array['results']
            ->hasSize(2);

      //use a condition that does not exists in session
      $post = [
         'itemtype'              => $location::getType(),
         'condition'             => '`name` LIKE "%4%"',
         'display_emptychoice'   => true,
         'entity_restrict'       => 0,
         'page'                  => 1,
         'page_limit'            => 10
      ];
      $values = \Dropdown::getDropdownValue($post);
      $values = (array)json_decode($values);

      $this->array($values)
         ->integer['count']->isEqualTo(10)
         ->array['results']
            ->hasSize(2);

   }
}
