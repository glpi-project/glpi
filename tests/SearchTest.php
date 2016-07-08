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
}