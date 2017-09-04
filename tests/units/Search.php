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

/* Test for inc/search.class.php */

class Search extends DbTestCase {

   private function doSearch($itemtype, $params, array $forcedisplay = []) {
      global $DEBUG_SQL;

      // check param itemtype exists (to avoid search errors)
      $this->class($itemtype)->isSubClassof('CommonDBTM');

      // login to glpi if needed
      if (!isset($_SESSION['glpiname'])) {
         $this->Login();
      }

      // force session in debug mode (to store & retrieve sql errors)
      $glpi_use_mode             = $_SESSION['glpi_use_mode'];
      $_SESSION['glpi_use_mode'] = \Session::DEBUG_MODE;

      // don't compute last request from session
      $params['reset'] = 'reset';

      // do search
      $params = \Search::manageParams($itemtype, $params);
      $data   = \Search::getDatas($itemtype, $params, $forcedisplay);

      // append existing errors to returned data
      $data['last_errors'] = [];
      if (isset($DEBUG_SQL['errors'])) {
         $data['last_errors'] = implode(', ', $DEBUG_SQL['errors']);
         unset($DEBUG_SQL['errors']);
      }

      // restore glpi mode to previous
      $_SESSION['glpi_use_mode'] = $glpi_use_mode;

      // do not store this search from session
      \Search::resetSaveSearch();

      return $data;
   }


   /**
    * Get all classes in folder inc/
    *
    * @param boolean $function Whether to look for a function
    *
    * @return array
    */
   private function getClasses($function = false) {
      $classes = [];
      foreach (new \DirectoryIterator('inc/') as $fileInfo) {
         if ($fileInfo->isDot()) {
            continue;
         }

         $php_file = file_get_contents("inc/".$fileInfo->getFilename());
         $tokens = token_get_all($php_file);
         $class_token = false;
         foreach ($tokens as $token) {
            if (is_array($token)) {
               if ($token[0] == T_CLASS) {
                  $class_token = true;
               } else if ($class_token && $token[0] == T_STRING) {
                  if ($function) {
                     if (method_exists($token[1], $function)) {
                        $classes[] = $token[1];
                     }
                  } else {
                     $classes[] = $token[1];
                  }
                  $class_token = false;
               }
            }
         }
      }
      return array_unique($classes);
   }



   public function testMetaComputerSoftwareLicense() {
      $search_params = ['is_deleted'   => 0,
                             'start'        => 0,
                             'criteria'     => [0 => ['field'      => 'view',
                                                                'searchtype' => 'contains',
                                                                'value'      => '']],
                             'metacriteria' => [0 => ['link'       => 'AND',
                                                                'itemtype'   => 'Software',
                                                                'field'      => 163,
                                                                'searchtype' => 'contains',
                                                                'value'      => '>0'],
                                                     1 => ['link'       => 'AND',
                                                                'itemtype'   => 'Software',
                                                                'field'      => 160,
                                                                'searchtype' => 'contains',
                                                                'value'      => 'firefox']]];

      $data = $this->doSearch('Computer', $search_params);

      // check for sql error (data key missing or empty)
      $this->array($data)
         ->hasKey('data')
            ->array['last_errors']->isIdenticalTo([])
            ->array['data']->isNotEmpty();
   }

   public function testMetaComputerUser() {
      $search_params = ['is_deleted'   => 0,
                             'start'        => 0,
                             'search'       => 'Search',
                             'criteria'     => [0 => ['field'      => 'view',
                                                                'searchtype' => 'contains',
                                                                'value'      => '']],
                                                     // user login
                             'metacriteria' => [0 => ['link'       => 'AND',
                                                                'itemtype'   => 'User',
                                                                'field'      => 1,
                                                                'searchtype' => 'equals',
                                                                'value'      => 2],
                                                     // user profile
                                                     1 => ['link'       => 'AND',
                                                                'itemtype'   => 'User',
                                                                'field'      => 20,
                                                                'searchtype' => 'equals',
                                                                'value'      => 4],
                                                     // user entity
                                                     2 => ['link'       => 'AND',
                                                                'itemtype'   => 'User',
                                                                'field'      => 80,
                                                                'searchtype' => 'equals',
                                                                'value'      => 0],
                                                     // user profile
                                                     3 => ['link'       => 'AND',
                                                                'itemtype'   => 'User',
                                                                'field'      => 13,
                                                                'searchtype' => 'equals',
                                                                'value'      => 1]]];

      $data = $this->doSearch('Computer', $search_params);

      // check for sql error (data key missing or empty)
      $this->array($data)
         ->hasKey('data')
            ->array['last_errors']->isIdenticalTo([])
            ->array['data']->isNotEmpty();
   }

   public function testUser() {
      $search_params = ['is_deleted'   => 0,
                             'start'        => 0,
                             'search'       => 'Search',
                                                     // profile
                             'criteria'     => [0 => ['field'      => '20',
                                                                'searchtype' => 'contains',
                                                                'value'      => 'super-admin'],
                                                     // login
                                                     1 => ['link'       => 'AND',
                                                                'field'      => '1',
                                                                'searchtype' => 'contains',
                                                                'value'      => 'glpi'],
                                                     // entity
                                                     2 => ['link'       => 'AND',
                                                                'field'      => '80',
                                                                'searchtype' => 'equals',
                                                                'value'      => 0],
                                                     // is not not active
                                                     3 => ['link'       => 'AND',
                                                                'field'      => '8',
                                                                'searchtype' => 'notequals',
                                                                'value'      => 0]]];
      $data = $this->doSearch('User', $search_params);

      // check for sql error (data key missing or empty)
      $this->array($data)
         ->hasKey('data')
            ->array['last_errors']->isIdenticalTo([])
            ->array['data']
               ->isNotEmpty()
               //expecting one result
               ->integer['totalcount']->isIdenticalTo(1);
   }

   /**
    * This test will add all searchoptions in each itemtype and check if the
    * search give a SQL error
    *
    * @return void
    */
   public function testSearchOptions() {

      $displaypref = new \DisplayPreference();
      // save table glpi_displaypreferences
      $dp = getAllDatasFromTable($displaypref->getTable());
      foreach ($dp as $line) {
         $displaypref->delete($line, true);
      }

      $itemtypeslist = $this->getClasses('getSearchOptions');
      foreach ($itemtypeslist as $itemtype) {
         $number = 0;
         if (!file_exists('front/'.strtolower($itemtype).'.php')
                 || substr($itemtype, 0, 4) === "Rule"
                 || substr($itemtype, 0, 6) === "Common"
                 || substr($itemtype, 0, 2) === "DB"
                 || $itemtype == 'SlaLevel'
                 || $itemtype == 'OlaLevel'
                 || $itemtype == 'Reservation'
                 || $itemtype == 'Event'
                 || $itemtype == 'Glpi\\Event'
                 || $itemtype == 'KnowbaseItem'
                 || $itemtype == 'NetworkPortMigration') {
            // it's the case where not have search possible in this itemtype
            continue;
         }
         $item = getItemForItemtype($itemtype);

         $options = $item->getSearchOptions();
         $compare_options = [];
         foreach ($options as $key => $value) {
            if (is_array($value) && count($value) == 1) {
               $compare_options[$key] = $value['name'];
            } else {
               $compare_options[$key] = $value;
            }
         }

         foreach ($options as $key=>$data) {
            if (is_int($key)) {
               $input = [
                   'itemtype' => $itemtype,
                   'users_id' => 0,
                   'num' => $key,
               ];
                $displaypref->add($input);
               $number++;
            }
         }
         $this->integer((int)countElementsInTable($displaypref->getTable(),
                 "`itemtype`='".$itemtype."' AND `users_id`=0"))->isIdenticalTo($number);

         // do a search query
         $search_params = ['is_deleted'   => 0,
                                'start'        => 0,
                                'criteria'     => [],
                                'metacriteria' => []];
         $data = $this->doSearch($itemtype, $search_params);
         // check for sql error (data key missing or empty)
         $this->array($data)
            ->hasKey('data')
               ->array['last_errors']->isIdenticalTo([])
               ->array['data']->isNotEmpty();
      }
      // restore displaypreference table
      /// TODO: review, this can't work.
      foreach (getAllDatasFromTable($displaypref->getTable()) as $line) {
         $displaypref->delete($line, true);
      }
      $this->integer((int)countElementsInTable($displaypref->getTable()))->isIdenticalTo(0);
      foreach ($dp as $input) {
         $displaypref->add($input);
      }
   }

   /**
    * Test search with all meta to not have SQL errors
    *
    * @return void
    */
   public function test_search_all_meta() {
      $itemtypeslist = [
         'Computer',
         'Problem',
         'Ticket',
         'Printer',
         'Monitor',
         'Peripheral',
         'Software',
         'Phone'
      ];

      foreach ($itemtypeslist as $itemtype) {
         // do a search query
         $search_params = ['is_deleted'   => 0,
                                'start'        => 0,
                                'criteria'     => [0 => ['field'      => 'view',
                                                                   'searchtype' => 'contains',
                                                                   'value'      => '']],
                                'metacriteria' => []];
         $metacriteria = [];
         $metaList = \Search::getMetaItemtypeAvailable($itemtype);
         foreach ($metaList as $metaitemtype) {
            $item = getItemForItemtype($metaitemtype);
            foreach ($item->getSearchOptions() as $key=>$data) {
               if (is_int($key)) {
                  if (isset($data['datatype']) && $data['datatype'] == 'bool') {
                     $metacriteria[] = [
                         'link'       => 'AND',
                         'field'      => $key,
                         'searchtype' => 'equals',
                         'value'      => 0,
                     ];
                  } else {
                     $metacriteria[] = [
                         'link'       => 'AND',
                         'field'      => $key,
                         'searchtype' => 'contains',
                         'value'      => 'f',
                     ];
                  }
               }
            }
         }
         $search_params['metacriteria'] = $metacriteria;
         $data = $this->doSearch($itemtype, $search_params);
         // check for sql error (data key missing or empty)
         $this->array($data)
            ->hasKey('data')
               ->array['last_errors']->isIdenticalTo([])
               ->array['data']->isNotEmpty();
      }
   }

   public function testIsNotifyComputerGroup() {
      $search_params = ['is_deleted'   => 0,
                             'start'        => 0,
                             'search'       => 'Search',
                             'criteria'     => [0 => ['field'      => 'view',
                                                                'searchtype' => 'contains',
                                                                'value'      => '']],
                                                     // group is_notify
                             'metacriteria' => [0 => ['link'       => 'AND',
                                                                'itemtype'   => 'Group',
                                                                'field'      => 20,
                                                                'searchtype' => 'equals',
                                                                'value'      => 1]]];
      $this->login();
      $this->setEntity('_test_root_entity', true);

      $data = $this->doSearch('Computer', $search_params);

      // check for sql error (data key missing or empty)
      $this->array($data)
         ->hasKey('data')
            ->array['last_errors']->isIdenticalTo([])
            ->array['data']->isNotEmpty()
            //expecting no result
            ->integer['totalcount']->isIdenticalTo(0);

      $computer1 = getItemByTypeName('Computer', '_test_pc01');

      //create group that can be notified
      $group = new \Group();
      $gid = $group->add(
         [
            'name'         => '_test_group01',
            'is_notify'    => '1',
            'entities_id'  => $computer1->fields['entities_id'],
            'is_recursive' => 1
         ]
      );
      $this->integer($gid)->isGreaterThan(0);

      //attach group to computer
      $updated = $computer1->update(
         [
            'id'        => $computer1->getID(),
            'groups_id' => $gid
         ]
      );
      $this->boolean($updated)->isTrue();

      $data = $this->doSearch('Computer', $search_params);

      //reset computer
      $updated = $computer1->update(
         [
            'id'        => $computer1->getID(),
            'groups_id' => 0
         ]
      );
      $this->boolean($updated)->isTrue();

      // check for sql error (data key missing or empty)
      $this->array($data)
         ->hasKey('data')
            ->array['last_errors']->isIdenticalTo([])
            ->array['data']->isNotEmpty()
            //expecting one result
            ->integer['totalcount']->isIdenticalTo(1);
   }

   public function testDateBeforeOrNot() {
      //tickets created since one week
      $search_params = [
         'is_deleted'   => 0,
         'start'        => 0,
         'criteria'     => [
            0 => [
               'field'      => 'view',
               'searchtype' => 'contains',
               'value'      => ''
            ],
            // creation date
            1 => [
               'link'       => 'AND',
               'field'      => '15',
               'searchtype' => 'morethan',
               'value'      => '-1WEEK'
            ]
         ]
      ];

      $data = $this->doSearch('Ticket', $search_params);

      $this->array($data)
         ->hasKey('data')
            ->array['last_errors']->isIdenticalTo([])
            ->array['data']->isNotEmpty()
            ->integer['totalcount']->isGreaterThan(0);

      //negate previous search
      $search_params['criteria'][1]['link'] = 'AND NOT';
      $data = $this->doSearch('Ticket', $search_params);

      $this->array($data)
         ->hasKey('data')
            ->array['last_errors']->isIdenticalTo([])
            ->array['data']->isNotEmpty()
            ->integer['totalcount']->isIdenticalTo(0);
   }

   /**
    * Test that getSearchOptions throws an exception when it finds a duplicate
    *
    * @return void
    */
   public function testGetSearchOptionsWException() {
      $error = 'Duplicate key 12 (One search option/Any option) in tests\units\DupSearchOpt searchOptions!';

      $this->exception(
         function () {
            $item = new DupSearchOpt();
            $item->getSearchOptions();
         }
      )
         ->isInstanceOf('\RuntimeException')
         ->hasMessage($error);
   }

   function testManageParams() {
      // let's use TU_USER
      $this->login();
      $uid =  getItemByTypeName('User', TU_USER, true);

      $search = \Search::manageParams('Ticket', ['reset' => 1], false, false);
      $this->array(
         $search
      )->isEqualTo(['reset'        => 1,
                    'start'        => 0,
                    'order'        => 'DESC',
                    'sort'         => 19,
                    'is_deleted'   => 0,
                    'criteria'     => [0 => ['field' => 12,
                                             'searchtype' => 'equals',
                                             'value' => 'notold'
                                            ],
                                      ],
                    'metacriteria' => [],
                   ]);

      // now add a bookmark on Ticket view
      $bk = new \SavedSearch();
      $this->boolean(
         (boolean)$bk->add(['name'         => 'All my tickets',
                            'type'         => 1,
                            'itemtype'     => 'Ticket',
                            'users_id'     => $uid,
                            'is_private'   => 1,
                            'entities_id'  => 0,
                            'is_recursive' => 1,
                            'url'         => 'front/ticket.php?itemtype=Ticket&sort=2&order=DESC&start=0&criteria[0][field]=5&criteria[0][searchtype]=equals&criteria[0][value]='.$uid
                           ])
      )->isTrue();

      $bk_id = $bk->fields['id'];

      $bk_user = new \SavedSearch_User();
      $this->boolean(
         (boolean)$bk_user->add(['users_id' => $uid,
                                 'itemtype' => 'Ticket',
                                 'savedsearches_id' => $bk_id
                                ])
      )->isTrue();

      $search = \Search::manageParams('Ticket', ['reset' => 1], true, false);
      $this->array(
         $search
      )->isEqualTo(['reset'        => 1,
                    'start'        => 0,
                    'order'        => 'DESC',
                    'sort'         => 2,
                    'is_deleted'   => 0,
                    'criteria'     => [0 => ['field' => '5',
                                             'searchtype' => 'equals',
                                             'value' => $uid
                                            ],
                                      ],
                    'metacriteria' => [],
                    'itemtype' => 'Ticket',
                    'savedsearches_id' => $bk_id,
                   ]);

      // let's test for Computers
      $search = \Search::manageParams('Computer', ['reset' => 1], false, false);
      $this->array(
         $search
      )->isEqualTo(['reset'        => 1,
                    'start'        => 0,
                    'order'        => 'ASC',
                    'sort'         => 1,
                    'is_deleted'   => 0,
                    'criteria'     => [0 => ['field' => 'view',
                                             'link' => 'contains',
                                             'value' => '',
                                            ],
                                      ],
                    'metacriteria' => [],
                   ]);

      // now add a bookmark on Computer view
      $bk = new \SavedSearch();
      $this->boolean(
         (boolean)$bk->add(['name'         => 'Computer test',
                            'type'         => 1,
                            'itemtype'     => 'Computer',
                            'users_id'     => $uid,
                            'is_private'   => 1,
                            'entities_id'  => 0,
                            'is_recursive' => 1,
                            'url'         => 'front/computer.php?itemtype=Computer&sort=31&order=DESC&criteria%5B0%5D%5Bfield%5D=view&criteria%5B0%5D%5Bsearchtype%5D=contains&criteria%5B0%5D%5Bvalue%5D=test'
                           ])
      )->isTrue();

      $bk_id = $bk->fields['id'];

      $bk_user = new \SavedSearch_User();
      $this->boolean(
         (boolean)$bk_user->add(['users_id' => $uid,
                                 'itemtype' => 'Computer',
                                 'savedsearches_id' => $bk_id
                                ])
      )->isTrue();

      $search = \Search::manageParams('Computer', ['reset' => 1], true, false);
      $this->array(
         $search
      )->isEqualTo(['reset'        => 1,
                    'start'        => 0,
                    'order'        => 'DESC',
                    'sort'         => 31,
                    'is_deleted'   => 0,
                    'criteria'     => [0 => ['field' => 'view',
                                             'searchtype' => 'contains',
                                             'value' => 'test'
                                            ],
                                      ],
                    'metacriteria' => [],
                    'itemtype' => 'Computer',
                    'savedsearches_id' => $bk_id,
                   ]);

   }
}

class DupSearchOpt extends \CommonDBTM {
   public function getSearchOptionsNew() {
      $tab = [];

      $tab[] = [
         'id'     => '12',
         'name'   => 'One search option'
      ];

      $tab[] = [
         'id'     => '12',
         'name'   => 'Any option'
      ];

      return $tab;
   }

}
