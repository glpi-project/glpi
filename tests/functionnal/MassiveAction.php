<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
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

use CommonDBTM;
use Computer;
use DbTestCase;
use Notepad;
use Session;

/* Test for inc/massiveaction.class.php */

class MassiveAction extends DbTestCase {

   protected function actionsProvider() {
      return [
         [
            'itemtype'     => 'Computer',
            'items_id'     => '_test_pc01',
            'allcount'     => 18,
            'singlecount'  => 11
         ], [
            'itemtype'     => 'Printer',
            'items_id'     => '_test_printer_all',
            'allcount'     => 16,
            'singlecount'  => 10
         ], [
            'itemtype'     => 'Ticket',
            'items_id'     => '_ticket01',
            'allcount'     => 16,
            'singlecount'  => 11
         ], [
            'itemtype'     => 'Profile',
            'items_id'     => 'Super-Admin',
            'allcount'     => 2,
            'singlecount'  => 1
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

   protected function processMassiveActionsForOneItemtype (
      string $action_code,
      CommonDBTM $item,
      array $ids,
      array $input,
      int $ok,
      int $ko
   ) {
      $ma_ok = 0;
      $ma_ko = 0;

      // Shunt constructor
      $controller = new \atoum\atoum\mock\controller();
      $controller->__construct = function($args){};

      // Create mock
      $ma = new \mock\MassiveAction([], [], '', false, $controller);

      // Mock needed methods
      $ma->getMockController()->getAction = $action_code;
      $ma->getMockController()->addMessage = function() {};
      $ma->getMockController()->getInput = $input;
      $ma->getMockController()->itemDone =
         function($item, $id, $res) use (&$ma_ok, &$ma_ko) {
            if ($res == \MassiveAction::ACTION_OK) {
               $ma_ok++;
            } else {
               $ma_ko++;
            }
         };

      // Execute method
      \MassiveAction::processMassiveActionsForOneItemtype($ma, $item, $ids);

      // Check expected number of success and failures
      $this->integer($ma_ok)->isIdenticalTo($ok);
      $this->integer($ma_ko)->isIdenticalTo($ko);
   }

   protected function amendCommentProvider() {
      return [
         [
            'item'                   => getItemByTypeName("Computer", "_test_pc01"),
            'itemtype_is_compatible' => true,
            'has_right'              => true,
         ],
         [
            'item'                   => getItemByTypeName("Ticket", "_ticket01"),
            'itemtype_is_compatible' => false,
            'has_right'              => false,
         ],
         [
            'item'                   => getItemByTypeName("Computer", "_test_pc01"),
            'itemtype_is_compatible' => true,
            'has_right'              => false,
         ],
      ];
   }

   /**
    * @dataProvider amendCommentProvider
    */
   public function testProcessMassiveActionsForOneItemtype_AmendComment (
      CommonDBTM $item,
      bool $itemtype_is_compatible,
      bool $has_right
   ) {
      $base_comment = "test comment";
      $amendment = "test amendment";
      $old_session = $_SESSION['glpiactiveentities'] ?? [];

      // Set rights if needed
      if ($has_right) {
         $_SESSION['glpiactiveentities'] = [
            $item->getEntityID()
         ];
      }

      // Check supplied params match the data
      $comment_exist = array_key_exists('comment', $item->fields);
      $this->boolean($comment_exist)->isIdenticalTo($itemtype_is_compatible);
      $this->boolean($item->canUpdateItem())->isIdenticalTo($has_right);

      if ($itemtype_is_compatible && $has_right) {
         $expected_ok = 1;
         $expected_ko = 0;

         // If we expect the test to work, set the comment to $base_comment
         $update = $item->update([
            'id'      => $item->fields['id'],
            'comment' => $base_comment,
         ]);
         $this->boolean($update)->isTrue();
      } else if (!$itemtype_is_compatible) {
         // Itemtype incompatible, the action wont run on any items
         $expected_ok = 0;
         $expected_ko = 0;
      } else {
         // No update right, the action will run and fail
         $expected_ok = 0;
         $expected_ko = 1;
      }

      // Execute action
      $this->processMassiveActionsForOneItemtype(
         "amend_comment",
         $item,
         [$item->fields['id']],
         ['amendment' => $amendment],
         $expected_ok,
         $expected_ko
      );

      // If the item was modified, check the new comment value
      if ($itemtype_is_compatible && $has_right) {
         // Refresh data
         $this->boolean($item->getFromDB($item->fields['id']))->isTrue();
         $this
            ->string($item->fields['comment'])
            ->isIdenticalTo("$base_comment\n\n$amendment");
      }

      $_SESSION['glpiactiveentities'] = $old_session;
   }

   protected function addNoteProvider() {
      return [
         [
            'item'      => getItemByTypeName("Computer", "_test_pc01"),
            'has_right' => true,
         ],
         [
            'item'      => getItemByTypeName("Ticket", "_ticket01"),
            'has_right' => false,
         ],
      ];
   }

   /**
    * @dataProvider addNoteProvider
    */
   public function testProcessMassiveActionsForOneItemtype_AddNote (
      CommonDBTM $item,
      bool $has_right
   ) {

      $this->login(); // must be logged as MassiveAction uses Session::getLoginUserID()

      // Init vars
      $new_note_content = "Test add note";
      $old_session = $_SESSION['glpiactiveprofile'][$item::$rightname] ?? 0;
      $note_search = [
         'items_id' => $item->fields['id'],
         'itemtype' => $item->getType(),
         'content'  => $new_note_content
      ];

      if ($has_right) {
         $_SESSION['glpiactiveprofile'][$item::$rightname] = UPDATENOTE;
      }

      // Check expected rights
      $this
         ->boolean(boolval(Session::haveRight($item::$rightname, UPDATENOTE)))
         ->isIdenticalTo($has_right);

      if ($has_right) {
         $expected_ok = 1;
         $expected_ko = 0;

         // Keep track of the number of existing notes for this item
         $count_notes = countElementsInTable(Notepad::getTable(), $note_search);
      } else {
         // No rights, the action wont run on any items
         $expected_ok = 0;
         $expected_ko = 0;
      }

      // Execute action
      $this->processMassiveActionsForOneItemtype(
         "add_note",
         $item,
         [$item->fields['id']],
         ['add_note' => $new_note_content],
         $expected_ok,
         $expected_ko
      );

      // If the note was added, check it's value in the DB
      if ($has_right) {
         $new_count = countElementsInTable(Notepad::getTable(), $note_search);
         $this->integer($new_count)->isIdenticalTo($count_notes + 1);
      }

      $_SESSION['glpiactiveprofile'][$item::$rightname] = $old_session;
   }
}
