<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace tests\units;

use CommonDBTM;
use Contract;
use DbTestCase;
use Notepad;
use Problem;
use Session;
use Ticket;

/* Test for inc/massiveaction.class.php */

class MassiveAction extends DbTestCase
{
    protected function actionsProvider()
    {
        return [
            [
                'itemtype'     => 'Computer',
                'items_id'     => '_test_pc01',
                'allcount'     => 21,
                'singlecount'  => 13
            ], [
                'itemtype'     => 'Monitor',
                'items_id'     => '_test_monitor_1',
                'allcount'     => 20,
                'singlecount'  => 12
            ], [
                'itemtype'     => 'SoftwareLicense',
                'items_id'     => '_test_softlic_1',
                'allcount'     => 15,
                'singlecount'  => 9
            ], [
                'itemtype'     => 'NetworkEquipment',
                'items_id'     => '_test_networkequipment_1',
                'allcount'     => 16,
                'singlecount'  => 11
            ], [
                'itemtype'     => 'Peripheral',
                'items_id'     => '_test_peripheral_1',
                'allcount'     => 18,
                'singlecount'  => 12
            ], [
                'itemtype'     => 'Printer',
                'items_id'     => '_test_printer_all',
                'allcount'     => 19,
                'singlecount'  => 11
            ], [
                'itemtype'     => 'Phone',
                'items_id'     => '_test_phone_1',
                'allcount'     => 19,
                'singlecount'  => 11
            ], [
                'itemtype'     => 'Ticket',
                'items_id'     => '_ticket01',
                'allcount'     => 20,
                'singlecount'  => 10
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
    public function testGetAllMassiveActions($itemtype, $items_id, $allcount, $singlecount)
    {
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

    protected function processMassiveActionsForOneItemtype(
        string $action_code,
        CommonDBTM $item,
        array $ids,
        array $input,
        int $ok,
        int $ko,
        string $action_class = \MassiveAction::class
    ) {

        $ma_ok = 0;
        $ma_ko = 0;

       // Create mock
        $this->mockGenerator->orphanize('__construct');
        $ma = new \mock\MassiveAction([], [], '');

       // Mock needed methods
        $ma->getMockController()->getAction = $action_code;
        $ma->getMockController()->addMessage = function () {
        };
        $ma->getMockController()->getInput = $input;
        $ma->getMockController()->itemDone =
            function ($item, $id, $res) use (&$ma_ok, &$ma_ko) {
                if ($res == \MassiveAction::ACTION_OK) {
                    $ma_ok++;
                } else {
                    $ma_ko++;
                }
            };

       // Execute method
        $action_class::processMassiveActionsForOneItemtype($ma, $item, $ids);

       // Check expected number of success and failures
        $this->integer($ma_ok)->isIdenticalTo($ok);
        $this->integer($ma_ko)->isIdenticalTo($ko);
    }

    protected function amendCommentProvider()
    {
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
    public function testProcessMassiveActionsForOneItemtype_AmendComment(
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

    protected function addNoteProvider()
    {
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
    public function testProcessMassiveActionsForOneItemtype_AddNote(
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

    protected function linkToProblemProvider()
    {
        return [
            [
            // Expected failure: wrong itemtype
                'item'      => getItemByTypeName("Computer", "_test_pc01"),
                'input'     => [],
                'has_right' => false,
            ],
            [
            // Expected failure: missing rights
                'item'      => getItemByTypeName("Ticket", "_ticket01"),
                'input'     => [],
                'has_right' => false,
            ],
            [
            // Expected failure: input is empty
                'item'      => getItemByTypeName("Ticket", "_ticket01"),
                'input'     => [],
                'has_right' => true,
            ],
            [
            // Expected failure: input is invalid
                'item'      => getItemByTypeName("Ticket", "_ticket01"),
                'input'     => ['problems_id' => -1],
                'has_right' => true,
            ],
            [
            // Should work
                'item'      => getItemByTypeName("Ticket", "_ticket01"),
                'input'     => ['problems_id' => 1],
                'has_right' => true,
            ],
        ];
    }

    /**
     * @dataProvider linkToProblemProvider
     */
    public function testProcessMassiveActionsForOneItemtype_linkToProblem(
        CommonDBTM $item,
        array $input,
        bool $has_right
    ) {
       // Set up session rights
        $old_session = $_SESSION['glpiactiveprofile'][Problem::$rightname] ?? 0;
        if ($has_right) {
            $_SESSION['glpiactiveprofile'][Problem::$rightname] = UPDATE;
        }

       // Default expectation: can't run
        $expected_ok = 0;
        $expected_ko = 0;

       // Check rights set up was successful
        $this
         ->boolean(boolval(Session::haveRight(Problem::$rightname, UPDATE)))
         ->isIdenticalTo($has_right);

       // If input is valid, make sure we have a matching problem
        $problems_id = $input['problems_id'] ?? -1;
        if ($problems_id > 0) {
            $problem = new Problem();
            $input['problems_id'] = $problem->add([
                'name'    => "tmp",
                'content' => "tmp",
            ]);
            $this->integer($input['problems_id']);

           // Update expectation: this item should be OK
            $expected_ok = 1;
        }

       // Execute action
        $this->processMassiveActionsForOneItemtype(
            "link_to_problem",
            $item,
            [$item->fields['id']],
            $input,
            $expected_ok,
            $expected_ko,
            Ticket::class
        );

       // Reset rights
        $_SESSION['glpiactiveprofile'][Problem::$rightname] = $old_session;
    }

    protected function resolveTicketsProvider()
    {
        $ticket = new Ticket();
        $id = $ticket->add([
            'name'    => 'test',
            'content' => 'test'
        ]);
        $ticket->getFromDB($id);

        return [
            [
            // Expected failure: wrong itemtype
                'item'        => getItemByTypeName("Computer", "_test_pc01"),
                'input'       => [],
                'has_right'   => false,
                'should_work' => false,
            ],
            [
            // Expected failure: missing rights
                'item'        => $ticket,
                'input'       => [],
                'has_right'   => false,
                'should_work' => false,
            ],
            [
            // Expected failure: input is empty
                'item'        => $ticket,
                'input'       => [],
                'has_right'   => true,
                'should_work' => false,
            ],
            [
            // Should work
                'item'        => $ticket,
                'input'       => [
                    'solutiontypes_id' => 0,
                    'content'          => "Solution"
                ],
                'has_right'   => true,
                'should_work' => true,
            ],
        ];
    }

    /**
     * @dataProvider resolveTicketsProvider
     */
    public function testProcessMassiveActionsForOneItemtype_resolveTickets(
        CommonDBTM $item,
        array $input,
        bool $has_right,
        bool $should_work
    ) {

        $this->login(); // must be logged as ITILSolution uses Session::getLoginUserID()

       // Set up session rights
        $old_session = $_SESSION['glpiactiveprofile'][Ticket::$rightname] ?? 0;
        if ($has_right) {
            $_SESSION['glpiactiveprofile'][Ticket::$rightname] = UPDATE;
        } else {
            $_SESSION['glpiactiveprofile'][Ticket::$rightname] = 0;
        }

       // Default expectation: can't run
        $expected_ok = 0;
        $expected_ko = 0;

       // Check rights set up was successful
        $this
         ->boolean(boolval(Session::haveRight(Ticket::$rightname, UPDATE)))
         ->isIdenticalTo($has_right);

       // Update expectation: this item should be OK
        if ($should_work) {
            $expected_ok = 1;
        }

       // Execute action
        $this->processMassiveActionsForOneItemtype(
            "resolve_tickets",
            $item,
            [$item->fields['id']],
            $input,
            $expected_ok,
            $expected_ko,
            Ticket::class
        );

       // Reset rights
        $_SESSION['glpiactiveprofile'][Ticket::$rightname] = $old_session;
    }

    protected function addContractProvider()
    {
        $ticket = new Ticket();
        $id = $ticket->add([
            'name'    => 'test',
            'content' => 'test',
        ]);
        $ticket->getFromDB($id);
        $this->integer($id)->isGreaterThan(0);

        $contract = new Contract();
        $contract_id = $contract->add([
            'name'        => 'test',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]);
        $this->integer($contract_id)->isGreaterThan(0);

        return [
            [
            // Expected failure: wrong itemtype
                'item'        => getItemByTypeName("Computer", "_test_pc01"),
                'input'       => [],
                'has_right'   => false,
                'should_work' => false,
            ],
            [
            // Expected failure: missing rights
                'item'        => $ticket,
                'input'       => [],
                'has_right'   => false,
                'should_work' => false,
            ],
            [
            // Expected failure: input is empty
                'item'        => $ticket,
                'input'       => [],
                'has_right'   => true,
                'should_work' => false,
            ],
            [
            // Should work
                'item'        => $ticket,
                'input'       => [
                    'contracts_id' => $contract_id,
                ],
                'has_right'   => true,
                'should_work' => true,
            ],
        ];
    }

    /**
     * @dataProvider addContractProvider
     */
    public function testProcessMassiveActionsForOneItemtype_addContract(
        CommonDBTM $item,
        array $input,
        bool $has_right,
        bool $should_work
    ) {
        $this->login();

       // Set up session rights
        if ($has_right) {
            $this->login('tech', 'tech');
        } else {
            $this->login('post-only', 'postonly');
        }

       // Default expectation: can't run
        $expected_ok = 0;
        $expected_ko = 0;

       // Check rights set up was successful
        $this
         ->boolean(boolval(Session::haveRight(Ticket::$rightname, UPDATE)))
         ->isIdenticalTo($has_right);

       // Update expectation: this item should be OK
        if ($should_work) {
            $expected_ok = 1;
        }

       // Execute action
        $this->processMassiveActionsForOneItemtype(
            "add_contract",
            $item,
            [$item->fields['id']],
            $input,
            $expected_ok,
            $expected_ko,
            Ticket::class
        );
    }
}
