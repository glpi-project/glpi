<?php
/*
-------------------------------------------------------------------------
GLPI - Gestionnaire Libre de Parc Informatique
Copyright (C) 2015-2016 Teclib'.

http://glpi-project.org

based on GLPI - Gestionnaire Libre de Parc Informatique
Copyright (C) 2003-2014 by the INDEPNET Development Team.

-------------------------------------------------------------------------

LICENSE

This file is part of GLPI.

GLPI is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

GLPI is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with GLPI. If not, see <http://www.gnu.org/licenses/>.
--------------------------------------------------------------------------
*/

/* Test for inc/search.class.php */

class SearchTest extends DbTestCase {

   private function doSearch($itemtype, $params, array $forcedisplay = array()) {
      global $DEBUG_SQL;

      // check param itemtype exists (to avoid search errors)
      $this->assertTrue(is_subclass_of($itemtype, "CommonDBTM"));

      // login to glpi if needed
      if (!isset($_SESSION['glpiname'])) {
         $this->Login();
      }

      // force session in debug mode (to store & retrieve sql errors)
      $glpi_use_mode             = $_SESSION['glpi_use_mode'];
      $_SESSION['glpi_use_mode'] = Session::DEBUG_MODE;

      // don't compute last request from session
      $params['reset'] = 'reset';

      // do search
      $params = Search::manageParams($itemtype, $params);
      $data   = Search::getDatas($itemtype, $params, $forcedisplay);

      // append existing errors to returned data
      $data['last_errors'] = array();
      if (isset($DEBUG_SQL['errors'])) {
         $data['last_errors'] = implode(', ', $DEBUG_SQL['errors']);
         unset($DEBUG_SQL['errors']);
      }

      // restore glpi mode to previous
      $_SESSION['glpi_use_mode'] = $glpi_use_mode;

      // do not store this search from session
      Search::resetSaveSearch();

      return $data;
   }


   /**
    * Get all classes in folder inc/
    */
   private function getClasses($function=false) {
      $classes = array();
      foreach (new DirectoryIterator('inc/') as $fileInfo) {
         if($fileInfo->isDot()) continue;

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
      return $classes;
   }



   public function testMetaComputerSoftwareLicense() {
      $search_params = array('is_deleted'   => 0,
                             'start'        => 0,
                             'criteria'     => array(0 => array('field'      => 'view',
                                                                'searchtype' => 'contains',
                                                                'value'      => '')),
                             'metacriteria' => array(0 => array('link'       => 'AND',
                                                                'itemtype'   => 'Software',
                                                                'field'      => 163,
                                                                'searchtype' => 'contains',
                                                                'value'      => '>0'),
                                                     1 => array('link'       => 'AND',
                                                                'itemtype'   => 'Software',
                                                                'field'      => 160,
                                                                'searchtype' => 'contains',
                                                                'value'      => 'firefox')));

      $data = $this->doSearch('Computer', $search_params);

      // check for sql error (data key missing or empty)
      $this->assertArrayHasKey('data', $data, $data['last_errors']);
      $this->assertNotCount(0, $data['data'], $data['last_errors']);
   }



   public function testMetaComputerUser() {
      $search_params = array('is_deleted'   => 0,
                             'start'        => 0,
                             'search'       => 'Search',
                             'criteria'     => array(0 => array('field'      => 'view',
                                                                'searchtype' => 'contains',
                                                                'value'      => '')),
                                                     // user login
                             'metacriteria' => array(0 => array('link'       => 'AND',
                                                                'itemtype'   => 'User',
                                                                'field'      => 1,
                                                                'searchtype' => 'equals',
                                                                'value'      => 2),
                                                     // user profile
                                                     1 => array('link'       => 'AND',
                                                                'itemtype'   => 'User',
                                                                'field'      => 20,
                                                                'searchtype' => 'equals',
                                                                'value'      => 4),
                                                     // user entity
                                                     2 => array('link'       => 'AND',
                                                                'itemtype'   => 'User',
                                                                'field'      => 80,
                                                                'searchtype' => 'equals',
                                                                'value'      => 0),
                                                     // user profile
                                                     3 => array('link'       => 'AND',
                                                                'itemtype'   => 'User',
                                                                'field'      => 13,
                                                                'searchtype' => 'equals',
                                                                'value'      => 1)));

      $data = $this->doSearch('Computer', $search_params);

      // check for sql error (data key missing or empty)
      $this->assertArrayHasKey('data', $data, $data['last_errors']);
      $this->assertNotCount(0, $data['data'], $data['last_errors']);
   }

   public function testUser() {
      $search_params = array('is_deleted'   => 0,
                             'start'        => 0,
                             'search'       => 'Search',
                                                     // profile
                             'criteria'     => array(0 => array('field'      => '20',
                                                                'searchtype' => 'contains',
                                                                'value'      => 'super-admin'),
                                                     // login
                                                     1 => array('link'       => 'AND',
                                                                'field'      => '1',
                                                                'searchtype' => 'contains',
                                                                'value'      => 'glpi'),
                                                     // entity
                                                     2 => array('link'       => 'AND',
                                                                'field'      => '80',
                                                                'searchtype' => 'equals',
                                                                'value'      => 0),
                                                     // is not not active
                                                     3 => array('link'       => 'AND',
                                                                'field'      => '8',
                                                                'searchtype' => 'notequals',
                                                                'value'      => 0)));
   }



   /**
    * This test will add all serachoptions in each itemtype and check if the
    * search give a SQL error
    */
   public function testSearchOptions() {

      $displaypref = new DisplayPreference();
      // save table glpi_displaypreferences
      $dp = getAllDatasFromTable($displaypref->getTable());

      $itemtypeslist = $this->getClasses('getSearchOptions');
      foreach ($itemtypeslist as $itemtype) {
         $number = 0;
         if (!file_exists('front/'.strtolower($itemtype).'.php')
                 || substr($itemtype, 0, 4) === "Rule"
                 || substr($itemtype, 0, 6) === "Common"
                 || substr($itemtype, 0, 2) === "DB"
                 || $itemtype == 'SlaLevel'
                 || $itemtype == 'Reservation'
                 || $itemtype == 'Event'
                 || $itemtype == 'KnowbaseItem'
                 || $itemtype == 'NetworkPortMigration') {
            // it's the case where not have search possible in this itemtype
            continue;
         }
         $item = getItemForItemtype($itemtype);
         foreach ($item->getSearchOptions() as $key=>$data) {
            if (is_int($key)) {
               $input = array(
                   'itemtype' => $itemtype,
                   'users_id' => 0,
                   'num' => $key,
               );
               $displaypref->add($input);
               $number++;
            }
         }
         $this->assertEquals($number, countElementsInTable($displaypref->getTable(),
                 "`itemtype`='".$itemtype."' AND `users_id`=0"));

         // do a search query
         $search_params = array('is_deleted'   => 0,
                                'start'        => 0,
                                'criteria'     => array(),
                                'metacriteria' => array());
         $data = $this->doSearch($itemtype, $search_params);
         // check for sql error (data key missing or empty)
         $this->assertArrayHasKey('data', $data, $data['last_errors']);
         $this->assertNotCount(0, $data['data'], $data['last_errors']);
      }
      // restore displaypreference table
      foreach (getAllDatasFromTable($displaypref->getTable()) as $line) {
         $displaypref->delete($line, true);
      }
      $this->assertEquals(0, countElementsInTable($displaypref->getTable()));
      foreach ($dp as $input) {
         $displaypref->add($input);
      }
   }



   /**
    * Test search with all meta to not have SQL errors
    */
   public function test_search_all_meta() {
      $itemtypeslist = array('Computer', 'Problem', 'Ticket', 'Printer', 'Monitor',
          'Peripheral', 'Software', 'Phone');
      foreach ($itemtypeslist as $itemtype) {
         // do a search query
         $search_params = array('is_deleted'   => 0,
                                'start'        => 0,
                                'criteria'     => array(0 => array('field'      => 'view',
                                                                   'searchtype' => 'contains',
                                                                   'value'      => '')),
                                'metacriteria' => array());
         $metacriteria = array();
         $metaList = Search::getMetaItemtypeAvailable($itemtype);
         foreach ($metaList as $metaitemtype) {
            $item = getItemForItemtype($metaitemtype);
            foreach ($item->getSearchOptions() as $key=>$data) {
               if (is_int($key)) {
                  if (isset($data['datatype']) && $data['datatype'] == 'bool') {
                     $metacriteria[] = array(
                         'link'       => 'AND',
                         'field'      => $key,
                         'searchtype' => 'equals',
                         'value'      => 0,
                     );
                  } else {
                     $metacriteria[] = array(
                         'link'       => 'AND',
                         'field'      => $key,
                         'searchtype' => 'contains',
                         'value'      => 'f',
                     );
                  }
               }
            }
         }
         $search_params['metacriteria'] = $metacriteria;
         $data = $this->doSearch($itemtype, $search_params);
         // check for sql error (data key missing or empty)
         $this->assertArrayHasKey('data', $data, $data['last_errors']);
         $this->assertNotCount(0, $data['data'], $data['last_errors']);
      }
   }

   public function testIsNotifyComputerGroup() {
      $search_params = array('is_deleted'   => 0,
                             'start'        => 0,
                             'search'       => 'Search',
                             'criteria'     => array(0 => array('field'      => 'view',
                                                                'searchtype' => 'contains',
                                                                'value'      => '')),
                                                     // group is_notify
                             'metacriteria' => array(0 => array('link'       => 'AND',
                                                                'itemtype'   => 'Group',
                                                                'field'      => 20,
                                                                'searchtype' => 'equals',
                                                                'value'      => 1)));
      $this->setEntity('Root entity', true);

      $data = $this->doSearch('Computer', $search_params);

      // check for sql error (data key missing or empty)
      $this->assertArrayHasKey('data', $data, $data['last_errors']);
      $this->assertNotCount(0, $data['data'], $data['last_errors']);
      //expecting no result
      $this->assertEquals(0, $data['data']['totalcount']);

      $computer1 = getItemByTypeName('Computer', '_test_pc01');

      //create group that can be notified
      $group = new Group();
      $gid = $group->add(
         [
            'name'         => '_test_group01',
            'is_notify'    => '1',
            'entities_id'  => $computer1->fields['entities_id'],
            'is_recursive' => 1
         ]
      );
      $this->assertGreaterThan(0, $gid, 'Group has not been created!');

      //attach group to computer
      $updated = $computer1->update(
         [
            'id'        => $computer1->getID(),
            'groups_id' => $gid
         ]
      );
      $this->assertTrue($updated, 'Group has not been attached to computer!');

      $data = $this->doSearch('Computer', $search_params);

      //reset computer
      $updated = $computer1->update(
         [
            'id'        => $computer1->getID(),
            'groups_id' => 0
         ]
      );
      $this->assertTrue($updated, 'Group has not been detached from computer!');

      // check for sql error (data key missing or empty)
      $this->assertArrayHasKey('data', $data, $data['last_errors']);
      $this->assertNotCount(0, $data['data'], $data['last_errors']);
      //expecting one result
      $this->assertEquals(1, $data['data']['totalcount']);
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

      $this->assertArrayHasKey('data', $data, $data['last_errors']);
      $this->assertNotCount(0, $data['data'], $data['last_errors']);
      $this->assertGreaterThan(0, $data['data']['totalcount']);

      //negate previous search
      $search_params['criteria'][1]['link'] = 'AND NOT';
      $data = $this->doSearch('Ticket', $search_params);

      $this->assertArrayHasKey('data', $data, $data['last_errors']);
      $this->assertNotCount(0, $data['data'], $data['last_errors']);
      $this->assertEquals(0, $data['data']['totalcount']);
   }
}
