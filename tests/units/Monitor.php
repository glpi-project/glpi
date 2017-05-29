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

namespace tests\units;

use \DbTestCase;

/* Test for inc/monitor.class.php */

class Monitor extends DbTestCase {

   public function testBasicMonitor() {
      $this->Login();
      $this->setEntity('_test_root_entity', true);

      $date = date('Y-m-d H:i:s');
      $_SESSION['glpi_currenttime'] = $date;

      $data = [
         'name'         => '_test_monitor01',
         'entities_id'  => '0'
      ];

      $monitor = new \Monitor();
      $added = $monitor->add($data);
      $this->integer((int)$added)->isGreaterThan(0);

      $monitor = getItemByTypeName('Monitor', '_test_monitor01');

      $expected = [
         'id' => "$added",
         'entities_id' => '0',
         'name' => '_test_monitor01',
         'date_mod' => $date,
         'contact' => NULL,
         'contact_num' => NULL,
         'users_id_tech' => '0',
         'groups_id_tech' => '0',
         'comment' => NULL,
         'serial' => NULL,
         'otherserial' => NULL,
         'size' => '0.00',
         'have_micro' => '0',
         'have_speaker' => '0',
         'have_subd' => '0',
         'have_bnc' => '0',
         'have_dvi' => '0',
         'have_pivot' => '0',
         'have_hdmi' => '0',
         'have_displayport' => '0',
         'locations_id' => '0',
         'monitortypes_id' => '0',
         'monitormodels_id' => '0',
         'manufacturers_id' => '0',
         'is_global' => '0',
         'is_deleted' => '0',
         'is_template' => '0',
         'template_name' => NULL,
         'users_id' => '0',
         'groups_id' => '0',
         'states_id' => '0',
         'ticket_tco' => '0.0000',
         'is_dynamic' => '0',
         'date_creation' => $date,
         'is_recursive' => '0'
      ];

      $this->array($monitor->fields)->isIdenticalTo($expected);
   }
}
