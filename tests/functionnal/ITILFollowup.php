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

   public function testUpdateAndDelete() {
      $this->login();

      $ticketId = $this->getNewITILObject('Ticket');
      $fup      = new \ITILFollowup();
      $tmp      = ['itemtype' => 'Ticket', 'items_id' => $ticketId];

      $fup_id = $fup->add([
         'content'      => "my followup",
         'itemtype'   => 'Ticket',
         'items_id'   => $ticketId
      ]);
      $this->integer((int)$fup_id)->isGreaterThan(0);

      $this->boolean(
         $fup->update([
            'id'         => $fup_id,
            'content'    => "my followup updated",
            'itemtype'   => 'Ticket',
            'items_id'   => $ticketId
         ])
      )->isTrue();

      $this->boolean(
         $fup->getFromDB($fup_id)
      )->isTrue();
      $this->string((string) $fup->fields['content'])->isEqualTo('my followup updated');

      $this->boolean(
         $fup->delete([
            'id'  => $fup_id
         ])
      )->isTrue();
      $this->boolean((boolean) $fup->getFromDB($fup_id))->isFalse();

      $changeId = $this->getNewITILObject('Change');
      $fup      = new \ITILFollowup();
      $tmp      = ['itemtype' => 'Change', 'items_id' => $changeId];

      $fup_id = $fup->add([
         'content'      => "my followup",
         'itemtype'   => 'Change',
         'items_id'   => $changeId
      ]);
      $this->integer((int)$fup_id)->isGreaterThan(0);

      $this->boolean(
         $fup->update([
            'id'         => $fup_id,
            'content'    => "my followup updated",
            'itemtype'   => 'Change',
            'items_id'   => $changeId
         ])
      )->isTrue();

      $this->boolean(
         $fup->getFromDB($fup_id)
      )->isTrue();
      $this->string((string) $fup->fields['content'])->isEqualTo('my followup updated');

      $this->boolean(
         $fup->delete([
            'id'  => $fup_id
         ])
      )->isTrue();
      $this->boolean((boolean) $fup->getFromDB($fup_id))->isFalse();

      $problemId = $this->getNewITILObject('Problem');
      $fup      = new \ITILFollowup();
      $tmp      = ['itemtype' => 'Problem', 'items_id' => $problemId];

      $fup_id = $fup->add([
         'content'      => "my followup",
         'itemtype'   => 'Problem',
         'items_id'   => $problemId
      ]);
      $this->integer((int)$fup_id)->isGreaterThan(0);

      $this->boolean(
         $fup->update([
            'id'         => $fup_id,
            'content'    => "my followup updated",
            'itemtype'   => 'Problem',
            'items_id'   => $problemId
         ])
      )->isTrue();

      $this->boolean(
         $fup->getFromDB($fup_id)
      )->isTrue();
      $this->string((string) $fup->fields['content'])->isEqualTo('my followup updated');

      $this->boolean(
         $fup->delete([
            'id'  => $fup_id
         ])
      )->isTrue();
      $this->boolean((boolean) $fup->getFromDB($fup_id))->isFalse();
   }

   /**
    * Test _do_not_compute_takeintoaccount flag
    */
   public function testDoNotComputeTakeintoaccount() {
      $this->login();

      $ticket = new \Ticket();
      $oldConf = [
        'glpiset_default_tech'      => $_SESSION['glpiset_default_tech'],
        'glpiset_default_requester' => $_SESSION['glpiset_default_requester'],
      ];

      $_SESSION['glpiset_default_tech'] = 0;
      $_SESSION['glpiset_default_requester'] = 0;

      // Normal behaviior, no flag specified
      $ticketID = $this->getNewITILObject('Ticket');
      $this->integer($ticketID);

      $ITILFollowUp = new \ITILFollowup();
      $this->integer($ITILFollowUp->add([
         'date'                            => $_SESSION['glpi_currenttime'],
         'users_id'                        => \Session::getLoginUserID(),
         'content'                         => "Functionnal test",
         'items_id'                        => $ticketID,
         'itemtype'                        => \Ticket::class,
      ]));

      $this->boolean($ticket->getFromDB($ticketID))
         ->isTrue();
      $this->integer((int) $ticket->fields['takeintoaccount_delay_stat'])
         ->isGreaterThan(0);

      // Now using the _do_not_compute_takeintoaccount flag
      $ticketID = $this->getNewITILObject('Ticket');
      $this->integer($ticketID);

      $ITILFollowUp = new \ITILFollowup();
      $this->integer($ITILFollowUp->add([
         'date'                            => $_SESSION['glpi_currenttime'],
         'users_id'                        => \Session::getLoginUserID(),
         'content'                         => "Functionnal test",
         '_do_not_compute_takeintoaccount' => true,
         'items_id'                        => $ticketID,
         'itemtype'                        => \Ticket::class,
      ]));

      $this->boolean($ticket->getFromDB($ticketID))
         ->isTrue();

      $this->integer((int) $ticket->fields['takeintoaccount_delay_stat'])
         ->isEqualTo(0);

      // Reset conf
      $_SESSION['glpiset_default_tech']      = $oldConf['glpiset_default_tech'];
      $_SESSION['glpiset_default_requester'] = $oldConf['glpiset_default_requester'];
   }
}
