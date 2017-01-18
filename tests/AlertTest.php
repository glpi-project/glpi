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

/* Test for inc/alert.class.php */

class AlertTest extends DbTestCase {

   public function testAddDelete() {

      $alert = new Alert();
      $nb    = countElementsInTable($alert->getTable());
      $comp  = getItemByTypeName('Computer', '_test_pc01');
      $date  = '2016-09-01 12:34:56';

      // Add
      $id = $alert->add([
         'itemtype' => $comp->getType(),
         'items_id' => $comp->getID(),
         'type'     => Alert::END,
         'date'     => $date,
      ]);
      $this->assertGreaterThan(0, $id);
      $this->assertGreaterThan($nb, countElementsInTable($alert->getTable()));

      // Getters
      $this->assertFalse(Alert::alertExists($comp->getType(), $comp->getID(), Alert::NOTICE));
      $this->assertEquals($id, Alert::alertExists($comp->getType(), $comp->getID(), Alert::END));
      $this->assertEquals($date, Alert::getAlertDate($comp->getType(), $comp->getID(), Alert::END));

      // Display
      $this->expectOutputString(sprintf('Alert sent on %s', Html::convDateTime($date)));
      Alert::displayLastAlert($comp->getType(), $comp->getID());

      // Delete
      $this->assertTrue($alert->clear($comp->getType(), $comp->getID(), Alert::END));
      $this->assertEquals($nb, countElementsInTable($alert->getTable()));

      // Still true, nothing to delete but no error
      $this->assertTrue($alert->clear($comp->getType(), $comp->getID(), Alert::END));
   }
}
