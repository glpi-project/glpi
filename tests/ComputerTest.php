<?php
/**
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

/* Test for inc/computer_softwareversion.class.php */

class ComputerTest extends DbTestCase {

   /**
    * @covers Computer::post_updateItem
    */
   public function testTypeName() {
      global $CFG_GLPI;

      $computer = getItemByTypeName('Computer', '_test_pc01');
      $savecomp = $computer->fields;
      $saveconf = $CFG_GLPI;
      $printer  = getItemByTypeName('Printer', '_test_printer_all');

      // Create the link
      $link = new Computer_Item();
      $in = ['computers_id' => $computer->getField('id'),
             'itemtype'     => $printer->getType(),
             'items_id'     => $printer->getID(),
      ];
      $this->assertGreaterThan(0, $link->add($in));

      // Change the computer
      $CFG_GLPI['is_contact_autoupdate']  = 1;
      $CFG_GLPI['is_user_autoupdate']     = 1;
      $CFG_GLPI['is_group_autoupdate']    = 1;
      $CFG_GLPI['state_autoupdate_mode']  = -1;
      $CFG_GLPI['is_location_autoupdate'] = 1;
      $in = ['id'           => $computer->getField('id'),
             'contact'      => $this->getUniqueString(),
             'contact_num'  => $this->getUniqueString(),
             'users_id'     => $this->getUniqueInteger(),
             'groups_id'    => $this->getUniqueInteger(),
             'states_id'    => $this->getUniqueInteger(),
             'locations_id' => $this->getUniqueInteger(),
      ];
      $this->assertTrue($computer->update($in));
      $this->assertTrue($computer->getFromDB($computer->getID()));
      $this->assertTrue($printer->getFromDB($printer->getID()));
      foreach ($in as $k => $v) {
         // Check the computer new values
         $this->assertEquals($v, $computer->getField($k), $k);
         // Check the printer and test propagation occurs
         $this->assertEquals($v, $printer->getField($k), $k);
      }

      // Change the computer again
      $CFG_GLPI['is_contact_autoupdate']  = 0;
      $CFG_GLPI['is_user_autoupdate']     = 0;
      $CFG_GLPI['is_group_autoupdate']    = 0;
      $CFG_GLPI['state_autoupdate_mode']  = 0;
      $CFG_GLPI['is_location_autoupdate'] = 0;
      $in2 = ['id'          => $computer->getField('id'),
             'contact'      => $this->getUniqueString(),
             'contact_num'  => $this->getUniqueString(),
             'users_id'     => $this->getUniqueInteger(),
             'groups_id'    => $this->getUniqueInteger(),
             'states_id'    => $this->getUniqueInteger(),
             'locations_id' => $this->getUniqueInteger(),
      ];
      $this->assertTrue($computer->update($in2));
      $this->assertTrue($computer->getFromDB($computer->getID()));
      $this->assertTrue($printer->getFromDB($printer->getID()));
      foreach ($in2 as $k => $v) {
         // Check the computer new values
         $this->assertEquals($v, $computer->getField($k), $k);
         // Check the printer and test propagation DOES NOT occurs
         $this->assertEquals($in[$k], $printer->getField($k), $k);
      }

      // Restore state
      $computer->update($savecomp);
      // Restore configuration
      $CFG_GLPI = $saveconf;
   }

}
