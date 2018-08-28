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

/* Test for inc/itilfollowup.class.php */

class ITILFollowup extends DbTestCase {

   /**
    * Create a new ITILObject and return its id
    *
    * @param string $itemtype ITILObject parent to test followups on
    * @return integer
    */
   private function getNewITILObject($itemtype) {
      //create reference ITILObject
      $itilobject = new $itemtype();
      $this->integer((int)$itilobject->add([
            'name'         => "$itemtype title",
            'description'  => 'a description',
            'content'      => '',
            'entities_id'  => getItemByTypeName('Entity', '_test_root_entity', true),
      ]))->isGreaterThan(0);

      $this->boolean($itilobject->isNewItem())->isFalse();
      $this->boolean($itilobject->can($itilobject->getID(), \READ))->isTrue();
      return (int)$itilobject->getID();
   }

   public function testACL() {
      $this->login();

      $ticketId = $this->getNewITILObject('Ticket');
      $fup      = new \ITILFollowup();
      $tmp      = ['itemtype' => 'Ticket', 'items_id' => $ticketId];
      $this->boolean((boolean) $fup->can(-1, \CREATE, $tmp))->isTrue();

      $fup_id = $fup->add([
         'content'      => "my followup",
         'itemtype'   => 'Ticket',
         'items_id'   => $ticketId
      ]);
      $this->integer($fup_id)->isGreaterThan(0);
      $this->boolean((boolean) $fup->canViewItem())->isTrue();
      $this->boolean((boolean) $fup->canUpdateItem())->isTrue();
      $this->boolean((boolean) $fup->canPurgeItem())->isTrue();

      $changeId = $this->getNewITILObject('Change');
      $fup      = new \ITILFollowup();
      $tmp      = ['itemtype' => 'Change', 'items_id' => $changeId];
      $this->boolean((boolean) $fup->can(-1, \CREATE, $tmp))->isTrue();

      $fup_id = $fup->add([
         'content'      => "my followup",
         'itemtype'   => 'Change',
         'items_id'   => $changeId
      ]);
      $this->integer($fup_id)->isGreaterThan(0);
      $this->boolean((boolean) $fup->canViewItem())->isTrue();
      $this->boolean((boolean) $fup->canUpdateItem())->isTrue();
      $this->boolean((boolean) $fup->canPurgeItem())->isTrue();

      $problemId = $this->getNewITILObject('Problem');
      $fup      = new \ITILFollowup();
      $tmp      = ['itemtype' => 'Problem', 'items_id' => $problemId];
      $this->boolean((boolean) $fup->can(-1, \CREATE, $tmp))->isTrue();

      $fup_id = $fup->add([
         'content'      => "my followup",
         'itemtype'   => 'Problem',
         'items_id'   => $problemId
      ]);
      $this->integer($fup_id)->isGreaterThan(0);
      $this->boolean((boolean) $fup->canViewItem())->isTrue();
      $this->boolean((boolean) $fup->canUpdateItem())->isTrue();
      $this->boolean((boolean) $fup->canPurgeItem())->isTrue();
   }
}
