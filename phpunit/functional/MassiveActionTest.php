<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
use Location;
use MassiveAction;
use Notepad;
use PHPUnit\Framework\Attributes\DataProvider;
use Problem;
use Session;
use Ticket;
use User;
use UserEmail;

/* Test for inc/massiveaction.class.php */

class MassiveActionTest extends DbTestCase
{
    public static function actionsProvider()
    {
        return [
            [
                'itemtype'     => 'Computer',
                'items_id'     => '_test_pc01',
                'allcount'     => 29,
                'singlecount'  => 20,
            ], [
                'itemtype'     => 'Monitor',
                'items_id'     => '_test_monitor_1',
                'allcount'     => 24,
                'singlecount'  => 17,
            ], [
                'itemtype'     => 'SoftwareLicense',
                'items_id'     => '_test_softlic_1',
                'allcount'     => 15,
                'singlecount'  => 9,
            ], [
                'itemtype'     => 'NetworkEquipment',
                'items_id'     => '_test_networkequipment_1',
                'allcount'     => 24,
                'singlecount'  => 17,
            ], [
                'itemtype'     => 'Peripheral',
                'items_id'     => '_test_peripheral_1',
                'allcount'     => 26,
                'singlecount'  => 18,
            ], [
                'itemtype'     => 'Printer',
                'items_id'     => '_test_printer_all',
                'allcount'     => 25,
                'singlecount'  => 17,
            ], [
                'itemtype'     => 'Phone',
                'items_id'     => '_test_phone_1',
                'allcount'     => 25,
                'singlecount'  => 17,
            ], [
                'itemtype'     => 'Ticket',
                'items_id'     => '_ticket01',
                'allcount'     => 20,
                'singlecount'  => 9,
            ], [
                'itemtype'     => 'Profile',
                'items_id'     => 'Super-Admin',
                'allcount'     => 3,
                'singlecount'  => 2,
            ],
        ];
    }

    #[DataProvider('actionsProvider')]
    public function testGetAllMassiveActions($itemtype, $items_id, $allcount, $singlecount)
    {
        $this->login();
        $items_id = getItemByTypeName($itemtype, $items_id, true);
        $mact = new MassiveAction(
            [
                'item'            => [
                    $itemtype   => [
                        $items_id => 1,
                    ],
                ],
            ],
            [],
            'initial'
        );
        $input  = $mact->getInput();
        $this->assertArrayHasKey('action_filter', $input);
        $this->assertArrayHasKey('actions', $input);
        $this->assertCount($allcount, $input['action_filter']);
        $this->assertCount($allcount, $input['actions']);

        $mact = new MassiveAction(
            [
                'item'   => [
                    $itemtype   => [
                        $items_id => 1,
                    ],
                ],
            ],
            [],
            'initial',
            $items_id
        );
        $input  = $mact->getInput();
        $this->assertArrayHasKey('action_filter', $input);
        $this->assertArrayHasKey('actions', $input);
        $this->assertCount($singlecount, $input['action_filter']);
        $this->assertCount($singlecount, $input['actions']);
    }

    protected function processMassiveActionsForOneItemtype(
        string $action_code,
        CommonDBTM $item,
        array $ids,
        array $input,
        int $ok,
        int $ko,
        string $action_class = MassiveAction::class
    ) {
        $ma_ok = 0;
        $ma_ko = 0;

        // Create mock
        $ma = $this->getMockBuilder(MassiveAction::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAction', 'addMessage', 'getInput', 'itemDone'])
            ->getMock();

        // Mock needed methods
        $ma->POST = $input;
        $ma->method('getAction')->willReturn($action_code);
        $ma->method('addMessage')->willReturn(null);
        $ma->method('getInput')->willReturn($input);
        $ma->method('itemDone')->willReturnCallback(
            function ($item, $ids, $res) use (&$ma_ok, &$ma_ko) {
                if (is_array($ids)) {
                    $increment = count($ids);
                } else {
                    $increment = 1;
                }

                if ($res == MassiveAction::ACTION_OK) {
                    $ma_ok += $increment;
                } else {
                    $ma_ko += $increment;
                }
            }
        );

        // Execute method
        $action_class::processMassiveActionsForOneItemtype($ma, $item, $ids);

        // Check expected number of success and failures
        $this->assertSame($ok, $ma_ok);
        $this->assertSame($ko, $ma_ko);
    }

    public static function amendCommentProvider()
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

    #[DataProvider('amendCommentProvider')]
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
            $this->login();
            $_SESSION['glpiactiveentities'] = [
                $item->getEntityID(),
            ];
        }

        // Check supplied params match the data
        $comment_exist = array_key_exists('comment', $item->fields);
        $this->assertSame($itemtype_is_compatible, $comment_exist);
        $this->assertSame($has_right, $item->canUpdateItem());

        if ($itemtype_is_compatible && $has_right) {
            $expected_ok = 1;
            $expected_ko = 0;

            // If we expect the test to work, set the comment to $base_comment
            $update = $item->update([
                'id'      => $item->fields['id'],
                'comment' => $base_comment,
            ]);
            $this->assertTrue($update);
        } elseif (!$itemtype_is_compatible) {
            // Itemtype incompatible, the action won't run on any items
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
            $this->assertTrue($item->getFromDB($item->fields['id']));
            $this->assertSame(
                "$base_comment\n\n$amendment",
                $item->fields['comment']
            );
        }

        $_SESSION['glpiactiveentities'] = $old_session;
    }

    public static function addNoteProvider()
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

    #[DataProvider('addNoteProvider')]
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
            'content'  => $new_note_content,
        ];

        if ($has_right) {
            $_SESSION['glpiactiveprofile'][$item::$rightname] = UPDATENOTE;
        }

        // Check expected rights
        $this->assertSame(
            $has_right,
            (bool) Session::haveRight($item::$rightname, UPDATENOTE)
        );

        if ($has_right) {
            $expected_ok = 1;
            $expected_ko = 0;

            // Keep track of the number of existing notes for this item
            $count_notes = countElementsInTable(Notepad::getTable(), $note_search);
        } else {
            // No rights, the action won't run on any items
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
            $this->assertSame($count_notes + 1, $new_count);
        }

        $_SESSION['glpiactiveprofile'][$item::$rightname] = $old_session;
    }

    public static function linkToProblemProvider()
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

    #[DataProvider('linkToProblemProvider')]
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
        $this->assertSame(
            $has_right,
            (bool) Session::haveRight(Problem::$rightname, UPDATE)
        );

        // If input is valid, make sure we have a matching problem
        $problems_id = $input['problems_id'] ?? -1;
        if ($problems_id > 0) {
            $problem = new Problem();
            $input['problems_id'] = $problem->add([
                'name'    => "tmp",
                'content' => "tmp",
            ]);
            $this->assertGreaterThan(0, $input['problems_id']);

            // Update expectation: this item should be OK
            $expected_ok = 1;
        }

        // Execute action
        @$this->processMassiveActionsForOneItemtype(
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
            'content' => 'test',
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
                    'content'          => "Solution",
                ],
                'has_right'   => true,
                'should_work' => true,
            ],
        ];
    }

    public function testProcessMassiveActionsForOneItemtype_resolveTickets()
    {
        $this->login(); // must be logged as ITILSolution uses Session::getLoginUserID()

        $provider = $this->resolveTicketsProvider();
        foreach ($provider as $row) {
            $item = $row['item'];
            $input = $row['input'];
            $has_right = $row['has_right'];
            $should_work = $row['should_work'];


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
            $this->assertSame(
                $has_right,
                (bool) Session::haveRight(Ticket::$rightname, UPDATE)
            );

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
    }

    protected function addContractProvider()
    {
        $ticket = new Ticket();
        $id = $ticket->add([
            'name'    => 'test',
            'content' => 'test',
        ]);
        $ticket->getFromDB($id);
        $this->assertGreaterThan(0, $id);

        $contract = new Contract();
        $contract_id = $contract->add([
            'name'        => 'test',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]);
        $this->assertGreaterThan(0, $contract_id);

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

    public function testProcessMassiveActionsForOneItemtype_addContract()
    {
        $this->login();

        $provider = $this->addContractProvider();
        foreach ($provider as $row) {
            $item = $row['item'];
            $input = $row['input'];
            $has_right = $row['has_right'];
            $should_work = $row['should_work'];

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
            $this->assertSame(
                $has_right,
                (bool) Session::haveRight(Ticket::$rightname, UPDATE)
            );

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

    /**
     * Data provider for testDeleteEmails
     *
     * @return iterable
     */
    protected function deleteEmailsProvider(): iterable
    {
        // Users used for our tests
        $user1 = getItemByTypeName("User", "post-only", true);
        $user2 = getItemByTypeName("User", "glpi", true);
        $user3 = getItemByTypeName("User", "tech", true);
        $users_ids = [$user1, $user2, $user3];

        // Check that no email exist for our tests users
        $this->assertCount(
            0,
            (new UserEmail())->find([
                'users_id' => $users_ids,
            ])
        );

        // Create set of emails to be deleted
        $this->createItems('UserEmail', [
            // 1 email for user1
            ['users_id' => $user1, 'email' => 'test1@mail.com'],
            // 2 emails for user2
            ['users_id' => $user2, 'email' => 'test2@mail.com'],
            ['users_id' => $user2, 'email' => 'test3@mail.com'],
            // 0 emails for user3
        ]);

        // Check that emails have been created as expected
        $this->assertCount(
            3,
            (new UserEmail())->find([
                'users_id' => $users_ids,
            ])
        );

        // First, login as someone who shouldn't be able to run this action
        $this->login('post-only', 'postonly');
        $current_right = Session::haveRight(User::$rightname, UPDATE);
        $this->assertFalse(boolval($current_right));
        yield [$users_ids, 0, 3]; // All failed

        // Now login to someone that can run this action
        $this->login('glpi', 'glpi');
        $current_right = Session::haveRight(User::$rightname, UPDATE);
        $this->assertTrue(boolval($current_right));
        yield [$users_ids, 3, 0]; // Success

        // Verify that emails have been cleaned
        $this->assertCount(
            0,
            (new UserEmail())->find([
                'users_id' => $users_ids,
            ])
        );
    }

    /**
     * Test the "delete_email" massive action for User
     */
    public function testProcessMassiveActionsForOneItemtype_deleteEmail()
    {
        $provider = $this->deleteEmailsProvider();
        foreach ($provider as $row) {
            $items = $row[0];
            $ok = $row[1];
            $ko = $row[2];

            // Execute action
            $this->processMassiveActionsForOneItemtype(
                "delete_emails",
                new User(),
                $items,
                [],
                $ok,
                $ko,
                User::class
            );
        }
    }



    public function testProcessMassiveActionsForOneItemtype_AddDomain()
    {
        $this->login('glpi', 'glpi');

        $domain_item = new \Domain_Item();

        //create computer
        $computer = new \Computer();
        $this->assertGreaterThan(
            0,
            $computer->add([
                'name' => 'test',
                'entities_id' => 1,
            ])
        );

        //create manual domain
        $manual_domain = new \Domain();
        $this->assertGreaterThan(
            0,
            $manual_domain->add([
                'name' => 'manual_domain',
                'entities_id' => 1,
            ])
        );


        $this->assertTrue(boolval(Session::haveRight(\Domain::$rightname, UPDATE)));

        // Execute action to link Computer and Manual Domain
        $this->processMassiveActionsForOneItemtype(
            "add_item",
            $computer,
            [$computer->fields['id']],
            ['domains_id' => $manual_domain->fields['id'], 'domainrelations_id' => \DomainRelation::BELONGS],
            1,
            0,
            \Domain::class
        );

        //check if exist
        $rows = $domain_item->find([
            'domains_id'            => $manual_domain->fields['id'],
            'items_id'              => $computer->fields['id'],
            'itemtype'              => $computer->getType(),
            'domainrelations_id'    => \DomainRelation::BELONGS,
            'is_deleted'            => false,
        ]);
        $this->assertCount(1, $rows);


        //create new domain (for is_dynamic test)
        $dynamic_domain = new \Domain();
        $this->assertGreaterThan(
            0,
            $dynamic_domain->add([
                'name' => 'dynamic_domain',
            ])
        );

        //add relation (with is_dynamic = 1)
        $this->assertGreaterThan(
            0,
            $domain_item->add([
                'domains_id'            => $dynamic_domain->fields['id'],
                'is_dynamic'            => 1,
                'items_id'              => $computer->fields['id'],
                'itemtype'              => $computer->getType(),
                'domainrelations_id'    => \DomainRelation::BELONGS,
                'is_deleted'            => false,
            ])
        );


        // Execute action to remove link between Computer and Manual Domain
        $this->processMassiveActionsForOneItemtype(
            "remove_domain",
            $computer,
            [$computer->fields['id']],
            ['domains_id' => $manual_domain->fields['id']],
            1,
            0,
            \Domain::class
        );

        //manual Domain link should not exist
        $rows = $domain_item->find([
            'domains_id'            => $manual_domain->fields['id'],
            'items_id'              => $computer->fields['id'],
            'itemtype'              => $computer->getType(),
            'domainrelations_id'    => \DomainRelation::BELONGS,
        ]);
        $this->assertCount(0, $rows);

        //dynamic domain still exist
        $rows = $domain_item->find([
            'domains_id'            => $dynamic_domain->fields['id'],
            'is_dynamic'            => 1,
            'items_id'              => $computer->fields['id'],
            'itemtype'              => $computer->getType(),
            'domainrelations_id'    => \DomainRelation::BELONGS,
            'is_deleted'            => false,
        ]);
        $this->assertCount(1, $rows);

        // Execute action to remove link between Computer and Dynamic Domain
        $this->processMassiveActionsForOneItemtype(
            "remove_domain",
            $computer,
            [$computer->fields['id']],
            ['domains_id' => $dynamic_domain->fields['id']],
            1,
            0,
            \Domain::class
        );

        //dynamic domain still exist but in trashbin and locked
        $rows = $domain_item->find([
            'domains_id'            => $dynamic_domain->fields['id'],
            'is_dynamic'            => 1,
            'items_id'              => $computer->fields['id'],
            'itemtype'              => $computer->getType(),
            'domainrelations_id'    => \DomainRelation::BELONGS,
            'is_deleted'            => true,
        ]);
        $this->assertCount(1, $rows);
    }

    public function testSaveSearchSpecificMassiveActionProvider()
    {
        $this->login();

        $entity = getItemByTypeName('Entity', '_test_root_entity', true);
        $uid = getItemByTypeName('User', TU_USER, true);
        $bk = new \SavedSearch();
        $bku = new \SavedSearch_User();
        $this->assertTrue(
            (bool) $bk->add([
                'name'         => 'public root recursive',
                'type'         => 1,
                'itemtype'     => 'Ticket',
                'users_id'     => $uid,
                'is_private'   => 0,
                'entities_id'  => $entity,
                'is_recursive' => 1,
                'url'          => 'front/ticket.php?itemtype=Ticket&sort=2&order=DESC&start=0&criteria[0][field]=5&criteria[0][searchtype]=equals&criteria[0][value]=' . $uid,
            ])
        );

        $this->assertTrue(
            (bool) $bku->add([
                'users_id'          => $uid,
                'itemtype'          => 'Ticket',
                'savedsearches_id'  => $bk->getID(),
            ])
        );

        yield [
            "action"       => "unset_default",
            "item"         => $bk,
            "ids"          => [$bk->getID()],
            "input"        => $bk->input,
            "ok"           => 1,
            "ko"           => 0,
            "action_class" => \SavedSearch::class,
        ];

        yield [
            "action"       => "change_entity",
            "item"         => $bk,
            "ids"          => [$bk->getID()],
            "input"        => $bk->input,
            "ok"           => 1,
            "ko"           => 0,
            "action_class" => \SavedSearch::class,
        ];

        yield [
            "action"       => "change_visibility",
            "item"         => $bk,
            "ids"          => [$bk->getID()],
            "input"        => $bk->input,
            "ok"           => 1,
            "ko"           => 0,
            "action_class" => \SavedSearch::class,
        ];
    }

    public function testaveSearchSpecificmassiveAction()
    {
        $provider = $this->testSaveSearchSpecificMassiveActionProvider();
        foreach ($provider as $row) {
            $this->processMassiveActionsForOneItemtype(
                $row["action"],
                $row["item"],
                $row["ids"],
                $row["input"],
                $row["ok"],
                $row["ko"],
                $row["action_class"],
            );
        }
    }

    public function testProcessMassiveActionsForOneItemtype_updateDropdownOrCommonDBConnexity()
    {
        $this->login();

        $provider = $this->updateDropdownOrCommonDBConnexityProvider();
        foreach ($provider as $row) {
            $item = $row['item'];
            $input = $row['input'];
            $has_right = $row['has_right'];
            $should_work = $row['should_work'];


            $old_session = $_SESSION['glpiactiveprofile'][Ticket::$rightname] ?? 0;
            if ($has_right) {
                $_SESSION['glpiactiveprofile'][Ticket::$rightname] = UPDATE;
            } else {
                $_SESSION['glpiactiveprofile'][Ticket::$rightname] = 0;
            }



            // Check rights set up was successful
            $this->assertSame(
                $has_right,
                (bool) Session::haveRight(Ticket::$rightname, UPDATE)
            );

            // Default expectation: can't run
            $expected_ok = 0;
            $expected_ko = 0;

            // Update expectation: this item should be OK
            if ($should_work) {
                $expected_ok = 1;
            } else {
                $expected_ko = 1;
            }

            // Execute action
            $this->processMassiveActionsForOneItemtype(
                "update",
                $item,
                [$item->fields['id']],
                $input,
                $expected_ok,
                $expected_ko,
                MassiveAction::class
            );

            // Reset rights
            $_SESSION['glpiactiveprofile'][Ticket::$rightname] = $old_session;
        }
    }

    protected function updateDropdownOrCommonDBConnexityProvider()
    {
        $ticket = new Ticket();
        $location = new Location();
        $id = $ticket->add([
            'name'          => 'test',
            'content'       => 'test',
            'entities_id'   => getItemByTypeName('Entity', '_test_root_entity', true),
        ]);
        $ticket->getFromDB($id);
        $this->assertGreaterThan(0, $id);
        $this->assertSame($ticket->fields['entities_id'], getItemByTypeName('Entity', '_test_root_entity', true));

        $this->createItem(
            \Entity::class,
            [
                'name'          => '_test_root_subentity',
                'entities_id'   => getItemByTypeName('Entity', '_test_root_entity', true),
            ]
        );

        $location = new Location();
        $location_id_1 = $location->add([
            'name'        => 'test',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]);
        $this->assertGreaterThan(0, $location_id_1);

        $location_id_2 = $location->add([
            'name'        => 'test_sub',
            'entities_id' => getItemByTypeName('Entity', '_test_root_subentity', true),
        ]);
        $this->assertGreaterThan(0, $location_id_2);

        return [
            [
                // Should work
                'item'        => $ticket,
                'input'       => [
                    'locations_id' => $location_id_1,
                    'search_options'
                    => [
                        $ticket->getType() => 83,
                    ],
                    'field' => 'locations_id',
                ],
                'has_right'   => true,
                'should_work' => true,
            ],
            [
                // Should not work
                'item'        => $ticket,
                'input'       => [
                    'locations_id' => $location_id_2,
                    'search_options'
                    => [
                        $ticket->getType() => 83,
                    ],
                    'field' => 'locations_id',
                ],
                'has_right'   => true,
                'should_work' => false, //not same entity
            ],
        ];
    }
}
