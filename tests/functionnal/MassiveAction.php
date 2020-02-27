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

use DbTestCase;

/* Test for inc/massiveaction.class.php */

class MassiveAction extends DbTestCase {

   protected function actionsProvider() {
      return [
         [
            'itemtype'     => 'Computer',
            'items_id'     => '_test_pc01',
            'allcount'     => 15,
            'singlecount'  => 8
         ], [
            'itemtype'     => 'Printer',
            'items_id'     => '_test_printer_all',
            'allcount'     => 13,
            'singlecount'  => 7
         ], [
            'itemtype'     => 'Ticket',
            'items_id'     => '_ticket01',
            'allcount'     => 16,
            'singlecount'  => 11
         ], [
            'itemtype'     => 'Profile',
            'items_id'     => 'Super-Admin',
            'allcount'     => 1,
            'singlecount'  => 0
         ]
      ];
   }

   /**
    * @dataProvider actionsProvider
    */
   public function testGetAllMassiveActions($itemtype, $items_id, $allcount, $singlecount) {
      $this->login();
      $items_id = getItemByTypeName($itemtype, $items_id, true);
      $mact = new \MassiveAction(
         [
            'item'            => [
               $itemtype   => [
                  $items_id => 1
               ]
            ]
         ],
         [],
         'initial'
      );
      $input  = $mact->getInput();
      $this->array($input)
         ->hasKey('action_filter')
         ->hasKey('actions');
      $this->array($input['action_filter'])->hasSize($allcount);
      $this->array($input['actions'])->hasSize($allcount);

      $mact = new \MassiveAction(
         [
            'item'   => [
               $itemtype   => [
                  $items_id => 1
               ]
            ]
         ],
         [],
         'initial',
         $items_id
      );
      $input  = $mact->getInput();
      $this->array($input)
         ->hasKey('action_filter')
         ->hasKey('actions');
      $this->array($input['action_filter'])->hasSize($singlecount);
      $this->array($input['actions'])->hasSize($singlecount);
   }
}
