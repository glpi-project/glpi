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

/* Test for inc/transfer.class.php */

class Transfer extends DbTestCase {

   public function testTransfer() {
      $obj = new \Printer();

      //Original entity
      $fentity = (int)getItemByTypeName('Entity', '_test_root_entity', true);
      //Destination entity
      $dentity = (int)getItemByTypeName('Entity', '_test_child_2', true);

      // Add
      $id = $obj->add([
         'name'         => 'Printer to transfer',
         'entities_id'  => $fentity
      ]);
      $this->integer((int)$id)->isGreaterThan(0);
      $this->boolean($obj->getFromDB($id))->isTrue();

      //transer to another entity
      $transfer = new \Transfer();

      $controller = new \atoum\mock\controller();
      $controller->__construct = function() {
         // void
      };

      $ma = new \mock\MassiveAction([], [], 'process', $controller);

      \MassiveAction::processMassiveActionsForOneItemtype(
         $ma,
         $obj,
         [$id]
      );
      $transfer->moveItems(['Printer' => [$id]], $dentity, [$id]);
      unset($_SESSION['glpitransfer_list']);

      $this->boolean($obj->getFromDB($id))->isTrue();
      $this->integer((int)$obj->fields['entities_id'])->isidenticalTo($dentity);
   }
}
