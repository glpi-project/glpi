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

/* Test for inc/search.class.php */

class Search extends DbTestCase {

   private function doSearch($itemtype, $params, array $forcedisplay = []) {
      global $DEBUG_SQL;

      // check param itemtype exists (to avoid search errors)
      $this->class($itemtype)->isSubClassof('CommonDBTM');

      // login to glpi if needed
      if (!isset($_SESSION['glpiname'])) {
         $this->login();
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
    * @param array   $excludes List of classes to exclude
    *
    * @return array
    */
   private function getClasses($function = false, array $excludes = []) {
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
                  $classname = $token[1];

                  foreach ($excludes as $exclude) {
                     if ($classname === $exclude || @preg_match($exclude, $classname) === 1) {
                        break 2; // Class is excluded from results, go to next file
                     }
                  }

                  if ($function) {
                     if (method_exists($classname, $function)) {
                        $classes[] = $classname;
                     }
                  } else {
                     $classes[] = $classname;
                  }

                  break; // Assume there is only one class by file
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

      $itemtypeslist = $this->getClasses(
         'searchOptions',
         [
            '/^Rule.*/',
            '/^Common.*/',
            '/^DB.*/',
            'SlaLevel',
            'OlaLevel',
            'Reservation',
            'Event',
            'Glpi\\Event',
            'KnowbaseItem',
            'NetworkPortMigration',
            'TicketFollowup',
         ]
      );
      foreach ($itemtypeslist as $itemtype) {
         $number = 0;
         if (!file_exists('front/'.strtolower($itemtype).'.php')) {
            // it's the case where not have search possible in this itemtype
            continue;
         }
         $item = getItemForItemtype($itemtype);

         //load all options; so rawSearchOptionsToAdd to be tested
         $options = \Search::getCleanedOptions($itemtype);
         //but reload only items one because of mysql join limit
         $options = $item->searchOptions();
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
         $this->integer(
            (int)countElementsInTable(
               $displaypref->getTable(),
               ['itemtype' => $itemtype, 'users_id' => 0]
            )
         )->isIdenticalTo($number);

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
            foreach ($item->searchOptions() as $key=>$data) {
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
    * Test that searchOptions throws an exception when it finds a duplicate
    *
    * @return void
    */
   public function testGetSearchOptionsWException() {
      $error = 'Duplicate key 12 (One search option/Any option) in tests\units\DupSearchOpt searchOptions! ';

      $this->exception(
         function () {
            $item = new DupSearchOpt();
            $item->searchOptions();
         }
      )
         ->isInstanceOf('\RuntimeException')
         ->message->endWith($error);
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
                    'as_map'       => 0
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
                    'as_map'           => 0
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
                    'as_map'       => 0
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
                    'as_map'           => 0
                   ]);

   }

   public function addLeftJoinProvider() {
      return [
         'itemtype_item_revert' => [[
            'itemtype'           => 'Project',
            'table'              => \Contact::getTable(),
            'field'              => 'name',
            'linkfield'          => 'id',
            'meta'               => false,
            'meta_type'          => null,
            'joinparams'         => [
               'jointype'          => 'itemtype_item_revert',
               'specific_itemtype' => 'Contact',
               'beforejoin'        => [
                  'table'      => \ProjectTeam::getTable(),
                  'joinparams' => [
                     'jointype' => 'child',
                  ]
               ]
            ],
            'sql' => "LEFT JOIN `glpi_projectteams`
                        ON (`glpi_projects`.`id` = `glpi_projectteams`.`projects_id`
                            )
                      LEFT JOIN `glpi_contacts`  AS `glpi_contacts_id_d36f89b191ea44cf6f7c8414b12e1e50`
                        ON (`glpi_contacts_id_d36f89b191ea44cf6f7c8414b12e1e50`.`id` = `glpi_projectteams`.`items_id`
                        AND `glpi_projectteams`.`itemtype` = 'Contact'
                         )"
         ]],
      ];
   }

   /**
    * @dataProvider addLeftJoinProvider
    */
   public function testAddLeftJoin($lj_provider) {
      $already_link_tables = [];

      $sql_join = \Search::addLeftJoin(
         $lj_provider['itemtype'],
         getTableForItemType($lj_provider['itemtype']),
         $already_link_tables,
         $lj_provider['table'],
         $lj_provider['linkfield'],
         $lj_provider['meta'],
         $lj_provider['meta_type'],
         $lj_provider['joinparams'],
         $lj_provider['field']
      );

      $this->string($this->cleanSQL($sql_join))
           ->isEqualTo($this->cleanSQL($lj_provider['sql']));
   }

   private function cleanSQL($sql) {
      $sql = str_replace("\r\n", ' ', $sql);
      $sql = str_replace("\n", ' ', $sql);
      while (strpos($sql, '  ') !== false) {
         $sql = str_replace('  ', ' ', $sql);
      }

      $sql = trim($sql);

      return $sql;
   }

   public function testAllAssetsFields() {
      global $CFG_GLPI, $DB;

      $needed_fields = [
         'id',
         'name',
         'states_id',
         'locations_id',
         'serial',
         'otherserial',
         'comment',
         'users_id',
         'contact',
         'contact_num',
         'groups_id',
         'date_mod',
         'manufacturers_id',
         'groups_id_tech',
         'entities_id',
      ];

      foreach ($CFG_GLPI["asset_types"] as $itemtype) {
         $table = getTableForItemtype($itemtype);

         foreach ($needed_fields as $field) {
            $this->boolean($DB->fieldExists($table, $field))
                 ->isTrue("$table.$field is missing");
         }
      }
   }

   public function testProblems() {
      $tech_users_id = getItemByTypeName('User', "tech", true);

      // reduce the right of tech profile
      // to have only the right of display their own problems (created, assign)
      \ProfileRight::updateProfileRights(getItemByTypeName('Profile', "Technician", true), [
         'Problem' => (\Problem::READMY + READNOTE + UPDATENOTE)
      ]);

      // add a group for tech user
      $group = new \Group;
      $groups_id = $group->add([
         'name' => "test group for tech user"
      ]);
      $this->integer((int)$groups_id)->isGreaterThan(0);
      $group_user = new \Group_User;
      $this->integer(
         (int)$group_user->add([
            'groups_id' => $groups_id,
            'users_id'  => $tech_users_id
         ])
      )->isGreaterThan(0);

      // create a problem and assign group with tech user
      $problem = new \Problem;
      $this->integer(
         (int)$problem->add([
            'name'              => "test problem visibility for tech",
            'content'           => "test problem visibility for tech",
            '_groups_id_assign' => $groups_id
         ])
      )->isGreaterThan(0);

      // let's use tech user
      $this->login('tech', 'tech');

      // do search and check presence of the created problem
      $data = \Search::prepareDatasForSearch('Problem', ['reset' => 'reset']);
      \Search::constructSQL($data);
      \Search::constructData($data);

      $this->array($data)->array['data']->integer['totalcount']->isEqualTo(1);
      $this->array($data)
         ->array['data']
         ->array['rows']
         ->array[0]
         ->array['raw']
         ->string['ITEM_0']->isEqualTo('test problem visibility for tech');

   }

   public function testChanges() {
      $tech_users_id = getItemByTypeName('User', "tech", true);

      // reduce the right of tech profile
      // to have only the right of display their own changes (created, assign)
      \ProfileRight::updateProfileRights(getItemByTypeName('Profile', "Technician", true), [
         'Change' => (\Change::READMY + READNOTE + UPDATENOTE)
      ]);

      // add a group for tech user
      $group = new \Group;
      $groups_id = $group->add([
         'name' => "test group for tech user"
      ]);
      $this->integer((int)$groups_id)->isGreaterThan(0);

      $group_user = new \Group_User;
      $this->integer(
         (int)$group_user->add([
            'groups_id' => $groups_id,
            'users_id'  => $tech_users_id
         ])
      )->isGreaterThan(0);

      // create a Change and assign group with tech user
      $change = new \Change;
      $this->integer(
         (int)$change->add([
            'name'              => "test Change visibility for tech",
            'content'           => "test Change visibility for tech",
            '_groups_id_assign' => $groups_id
         ])
      )->isGreaterThan(0);

      // let's use tech user
      $this->login('tech', 'tech');

      // do search and check presence of the created Change
      $data = \Search::prepareDatasForSearch('Change', ['reset' => 'reset']);
      \Search::constructSQL($data);
      \Search::constructData($data);

      $this->array($data)->array['data']->integer['totalcount']->isEqualTo(1);
      $this->array($data)
         ->array['data']
         ->array['rows']
         ->array[0]
         ->array['raw']
         ->string['ITEM_0']->isEqualTo('test Change visibility for tech');

   }

   public function testSearchDdTranslation() {
      global $CFG_GLPI;

      $this->login();
      $conf = new \Config();
      $conf->setConfigurationValues('core', ['translate_dropdowns' => 1]);
      $CFG_GLPI['translate_dropdowns'] = 1;

      $state = new \State();
      $this->boolean($state->maybeTranslated())->isTrue();

      $sid = $state->add([
         'name'         => 'A test state',
         'is_recursive' => 1
      ]);
      $this->integer($sid)->isGreaterThan(0);

      $ddtrans = new \DropdownTranslation();
      $this->integer(
         $ddtrans->add([
            'itemtype'  => $state->getType(),
            'items_id'  => $state->fields['id'],
            'language'  => 'fr_FR',
            'field'     => 'completename',
            'value'     => 'Un status de test'
         ])
      )->isGreaterThan(0);

      $_SESSION['glpi_dropdowntranslations'] = [$state->getType() => ['completename' => '']];

      $search_params = [
         'is_deleted'   => 0,
         'start'        => 0,
         'criteria'     => [
            0 => [
               'field'      => 'view',
               'searchtype' => 'contains',
               'value'      => 'test'
            ]
         ],
         'metacriteria' => []
      ];

      $data = $this->doSearch('State', $search_params);

      $this->array($data)
         ->hasKey('data')
            ->array['last_errors']->isIdenticalTo([])
            ->array['data']->isNotEmpty()
            ->integer['totalcount']->isIdenticalTo(1);

      $conf->setConfigurationValues('core', ['translate_dropdowns' => 0]);
      $CFG_GLPI['translate_dropdowns'] = 0;
      unset($_SESSION['glpi_dropdowntranslations']);
   }

   public function dataInfocomOptions() {
      return [
         [1, false],
         [2, false],
         [4, false],
         [40, false],
         [31, false],
         [80, false],
         [25, true],
         [26, true],
         [27, true],
         [28, true],
         [37, true],
         [38, true],
         [50, true],
         [51, true],
         [52, true],
         [53, true],
         [54, true],
         [55, true],
         [56, true],
         [57, true],
         [58, true],
         [59, true],
         [120, true],
         [122, true],
         [123, true],
         [124, true],
         [125, true],
         [142, true],
         [159, true],
         [173, true],
      ];
   }

   /**
    * @dataProvider dataInfocomOptions
    */
   public function testIsInfocomOption($index, $expected) {
      $this->boolean(\Search::isInfocomOption('Computer', $index))->isIdenticalTo($expected);
   }
}

class DupSearchOpt extends \CommonDBTM {
   public function rawSearchOptions() {
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
