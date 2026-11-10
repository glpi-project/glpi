<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

use Glpi\Tests\DbTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Ticket;

use function Safe\ob_get_clean;
use function Safe\ob_start;

/* Test for inc/itilsolution.class.php */

class ITILSolutionTest extends DbTestCase
{
    /**
     * Create a new ITILObject and return its id or the object
     *
     * @param string $itemtype ITILObject parent to test followups on
     * @param bool   $as_object
     * @return int|\CommonDBTM
     */
    private function getNewITILObject(string $itemtype, bool $as_object = false)
    {
        //create reference ITILObject
        $itilobject = new $itemtype();
        $this->assertGreaterThan(
            0,
            (int) $itilobject->add([
                'name'         => "$itemtype title",
                'description'  => 'a description',
                'content'      => 'content',
                'entities_id'  => getItemByTypeName('Entity', '_test_root_entity', true),
            ])
        );

        $this->assertFalse($itilobject->isNewItem());
        $this->assertTrue($itilobject->can($itilobject->getID(), \READ));
        return $as_object ? $itilobject : (int) $itilobject->getID();
    }

    public function testTicketSolution()
    {
        $this->login();

        $uid = getItemByTypeName('User', TU_USER, true);
        $ticket = new Ticket();
        $this->assertGreaterThan(
            0,
            (int) $ticket->add([
                'name'               => 'ticket title',
                'description'        => 'a description',
                'content'            => 'content',
                '_users_id_assign'   => $uid,
            ])
        );

        $this->assertFalse($ticket->isNewItem());
        $this->assertSame($ticket::ASSIGNED, $ticket->getField('status'));

        $solution = new \ITILSolution();
        $this->assertGreaterThan(
            0,
            (int) $solution->add([
                'itemtype'  => $ticket::getType(),
                'items_id'  => $ticket->getID(),
                'content'   => "Current friendly ticket\r\nis solved!",
            ])
        );
        //reload from DB
        $this->assertTrue($ticket->getFromDB($ticket->getID()));

        $this->assertEquals($ticket::SOLVED, $ticket->getField('status'));
        $this->assertSame("Current friendly ticket\r\nis solved!", $solution->getField('content'));

        $this->assertTrue($solution->getFromDB($solution->getID()));
        $this->assertSame(\CommonITILValidation::WAITING, (int) $solution->fields['status']);

        //approve solution
        $follow = new \ITILFollowup();
        $this->assertGreaterThan(
            0,
            (int) $follow->add([
                'itemtype'  => $ticket::getType(),
                'items_id'   => $ticket->getID(),
                'add_close'    => '1',
            ])
        );
        $this->assertTrue($follow->getFromDB($follow->getID()));
        $this->assertTrue($solution->getFromDB($solution->getID()));
        $this->assertTrue($ticket->getFromDB($ticket->getID()));
        $this->assertSame(\CommonITILValidation::ACCEPTED, (int) $solution->fields['status']);
        $this->assertSame($ticket::CLOSED, (int) $ticket->fields['status']);

        //reopen ticket
        $this->assertGreaterThan(
            0,
            (int) $follow->add([
                'itemtype'  => $ticket::getType(),
                'items_id'  => $ticket->getID(),
                'add_reopen'   => '1',
                'content'      => 'This is required',
            ])
        );
        $this->assertTrue($ticket->getFromDB($ticket->getID()));
        $this->assertTrue($solution->getFromDB($solution->getID()));

        $this->assertSame($ticket::ASSIGNED, (int) $ticket->fields['status']);
        $this->assertSame(\CommonITILValidation::REFUSED, (int) $solution->fields['status']);

        $this->assertGreaterThan(
            0,
            (int) $solution->add([
                'itemtype'  => $ticket::getType(),
                'items_id'  => $ticket->getID(),
                'content'   => 'Another solution proposed!',
            ])
        );
        //reload from DB
        $this->assertTrue($ticket->getFromDB($ticket->getID()));
        $this->assertTrue($solution->getFromDB($solution->getID()));

        $this->assertEquals($ticket::SOLVED, $ticket->getField('status'));
        $this->assertSame(\CommonITILValidation::WAITING, (int) $solution->fields['status']);

        //refuse
        $follow = new \ITILFollowup();
        $this->assertGreaterThan(
            0,
            (int) $follow->add([
                'itemtype'   => 'Ticket',
                'items_id'   => $ticket->getID(),
                'add_reopen'   => '1',
                'content'      => 'This is required',
            ])
        );

        //reload from DB
        $this->assertTrue($ticket->getFromDB($ticket->getID()));
        $this->assertTrue($solution->getFromDB($solution->getID()));

        $this->assertSame($ticket::ASSIGNED, (int) $ticket->fields['status']);
        $this->assertSame(\CommonITILValidation::REFUSED, (int) $solution->fields['status']);

        $this->assertSame(
            2,
            $solution::countFor(
                'Ticket',
                $ticket->getID()
            )
        );
    }

    public function testProblemSolution()
    {
        $this->login();
        $uid = getItemByTypeName('User', TU_USER, true);

        $problem = new \Problem();
        $this->assertGreaterThan(
            0,
            (int) $problem->add([
                'name'               => 'problem title',
                'description'        => 'a description',
                'content'            => 'a content',
                '_users_id_assign'   => $uid,
            ])
        );

        $this->assertFalse($problem->isNewItem());
        $this->assertSame($problem::ASSIGNED, $problem->getField('status'));

        $solution = new \ITILSolution();
        $this->assertGreaterThan(
            0,
            (int) $solution->add([
                'itemtype'  => $problem::getType(),
                'items_id'  => $problem->getID(),
                'content'   => "Current friendly problem\r\nis solved!",
            ])
        );
        //reload from DB
        $this->assertTrue($problem->getFromDB($problem->getID()));

        $this->assertEquals($problem::SOLVED, $problem->getField('status'));
        $this->assertSame("Current friendly problem\r\nis solved!", $solution->getField('content'));

        $this->assertTrue($solution->getFromDB($solution->getID()));
        $this->assertSame(\CommonITILValidation::ACCEPTED, (int) $solution->fields['status']);
    }

    public function testChangeSolution()
    {
        $this->login();
        $uid = getItemByTypeName('User', TU_USER, true);

        $change = new \Change();
        $this->assertGreaterThan(
            0,
            (int) $change->add([
                'name'               => 'change title',
                'description'        => 'a description',
                'content'            => 'a content',
                '_users_id_assign'   => $uid,
            ])
        );

        $this->assertFalse($change->isNewItem());
        $this->assertSame($change::INCOMING, $change->getField('status'));

        $solution = new \ITILSolution();
        $this->assertGreaterThan(
            0,
            (int) $solution->add([
                'itemtype'  => $change::getType(),
                'items_id'  => $change->getID(),
                'content'   => "Current friendly change\r\nis solved!",
            ])
        );
        //reload from DB
        $this->assertTrue($change->getFromDB($change->getID()));

        $this->assertEquals($change::SOLVED, $change->getField('status'));
        $this->assertSame("Current friendly change\r\nis solved!", $solution->getField('content'));

        $this->assertTrue($solution->getFromDB($solution->getID()));
        $this->assertSame(\CommonITILValidation::ACCEPTED, (int) $solution->fields['status']);
    }


    public function testSolutionOnDuplicate()
    {
        $this->login();
        $this->setEntity('Root entity', true);

        $uid = getItemByTypeName('User', TU_USER, true);
        $ticket = new Ticket();
        $duplicated = (int) $ticket->add([
            'name'               => 'Duplicated ticket',
            'description'        => 'A ticket that will be duplicated',
            'content'            => 'a content',
            '_users_id_assign'   => $uid,
        ]);
        $this->assertGreaterThan(0, $duplicated);

        $duplicate = (int) $ticket->add([
            'name'               => 'Duplicate ticket',
            'description'        => 'A ticket that is a duplicate',
            'content'            => 'a content',
            '_users_id_assign'   => $uid,
        ]);
        $this->assertGreaterThan(0, $duplicate);

        $link = new \Ticket_Ticket();
        $this->assertGreaterThan(
            0,
            (int) $link->add([
                'tickets_id_1' => $duplicated,
                'tickets_id_2' => $duplicate,
                'link'         => \CommonITILObject_CommonITILObject::DUPLICATE_WITH,
            ])
        );

        //we got one ticket, and another that duplicates it.
        //let's manage solutions on them
        $solution = new \ITILSolution();
        $this->assertGreaterThan(
            0,
            (int) $solution->add([
                'itemtype'  => $ticket::getType(),
                'items_id'  => $duplicate,
                'content'   => 'Solve from main ticket',
            ])
        );
        //reload from DB
        $this->assertTrue($ticket->getFromDB($duplicate));
        $this->assertEquals($ticket::SOLVED, $ticket->getField('status'));

        $this->assertTrue($ticket->getFromDB($duplicated));
        $this->assertEquals($ticket::SOLVED, $ticket->getField('status'));
    }

    public function testMultipleSolution()
    {
        $this->login();

        $uid = getItemByTypeName('User', TU_USER, true);
        $ticket = new Ticket();
        $this->assertGreaterThan(
            0,
            (int) $ticket->add([
                'name'               => 'ticket title',
                'description'        => 'a description',
                'content'            => 'a content',
                '_users_id_assign'   => $uid,
            ])
        );

        $this->assertFalse($ticket->isNewItem());
        $this->assertSame($ticket::ASSIGNED, $ticket->getField('status'));

        $solution = new \ITILSolution();

        // 1st solution, it should be accepted
        $this->assertGreaterThan(
            0,
            (int) $solution->add([
                'itemtype'  => $ticket::getType(),
                'items_id'  => $ticket->getID(),
                'content'   => '1st solution, should be accepted!',
            ])
        );

        $this->assertTrue($solution->getFromDB($solution->getID()));
        $this->assertSame(\CommonITILValidation::WAITING, (int) $solution->fields['status']);

        // try to add directly another solution, it should be refused
        $this->assertFalse(
            $solution->add([
                'itemtype'  => $ticket::getType(),
                'items_id'  => $ticket->getID(),
                'content'   => '2nd solution, should be refused!',
            ])
        );
        $this->hasSessionMessages(ERROR, ['The item is already solved, did anyone pushed a solution before you?']);
    }

    public function testScreenshotConvertedIntoDocument()
    {
        $this->login(); // must be logged as ITILSolution uses Session::getLoginUserID()

        // Test uploads for item creation
        $ticket = new Ticket();
        $ticket->add([
            'name' => $this->getUniqueString(),
            'content' => 'test',
        ]);
        $this->assertFalse($ticket->isNewItem());

        $base64Image = base64_encode(file_get_contents(FIXTURE_DIR . '/uploads/foo.png'));
        $user = getItemByTypeName('User', TU_USER, true);
        $filename = '5e5e92ffd9bd91.11111111image_paste22222222.png';
        $instance = new \ITILSolution();
        $input = [
            'users_id' => $user,
            'items_id' => $ticket->getID(),
            'itemtype' => 'Ticket',
            'name'    => 'a solution',
            'content' => <<<HTML
<p>Test with a ' (add)</p>
<p><img id="3e29dffe-0237ea21-5e5e7034b1d1a1.00000000" src="data:image/png;base64,{$base64Image}" width="12" height="12"></p>
HTML,
            '_filename' => [
                $filename,
            ],
            '_tag_filename' => [
                '3e29dffe-0237ea21-5e5e7034b1d1a1.00000000',
            ],
            '_prefix_filename' => [
                '5e5e92ffd9bd91.11111111',
            ],
        ];
        copy(FIXTURE_DIR . '/uploads/foo.png', GLPI_TMP_DIR . '/' . $filename);

        $instance->add($input);
        $this->assertFalse($instance->isNewItem());
        $this->assertTrue($instance->getFromDB($instance->getId()));
        $expected = 'a href="/front/document.send.php?docid=';
        $this->assertStringContainsString($expected, $instance->fields['content']);

        // Test uploads for item update
        $base64Image = base64_encode(file_get_contents(FIXTURE_DIR . '/uploads/bar.png'));
        $filename = '5e5e92ffd9bd91.44444444image_paste55555555.png';
        copy(FIXTURE_DIR . '/uploads/bar.png', GLPI_TMP_DIR . '/' . $filename);
        $success = $instance->update([
            'id' => $instance->getID(),
            'content' => <<<HTML
<p>Test with a ' (update)</p>
<p><img id="3e29dffe-0237ea21-5e5e7034b1d1a1.33333333" src="data:image/png;base64,{$base64Image}" width="12" height="12"></p>
HTML,
            '_filename' => [
                $filename,
            ],
            '_tag_filename' => [
                '3e29dffe-0237ea21-5e5e7034b1d1a1.33333333',
            ],
            '_prefix_filename' => [
                '5e5e92ffd9bd91.44444444',
            ],
        ]);
        $this->assertTrue($success);
        $this->assertTrue($instance->getFromDB($instance->getId()));
        $expected = 'a href="/front/document.send.php?docid=';
        $this->assertStringContainsString($expected, $instance->fields['content']);
    }

    public function testAddMultipleSolution()
    {

        $this->login(); // must be logged as ITILSolution uses Session::getLoginUserID()

        $em_ticket = new Ticket();
        $em_solution = new \ITILSolution();

        $tickets = [];

        // Create 10 tickets
        for ($i = 0; $i < 10; $i++) {
            $id = $em_ticket->add([
                'name'    => "test",
                'content' => "test",
            ]);
            $this->assertGreaterThan(0, $id);

            $ticket = new Ticket();
            $this->assertTrue($ticket->getFromDB($id));
            $tickets[] = $ticket;
        }

        // Solve all created tickets
        foreach ($tickets as $ticket) {
            $id = $em_solution->add([
                'itemtype' => $ticket::getType(),
                'items_id' => $ticket->fields['id'],
                'content'  => 'test',
            ]);
            $this->assertGreaterThan(0, $id);

            $this->assertTrue($ticket->getFromDB($ticket->fields['id']));
            $this->assertEquals(Ticket::SOLVED, $ticket->fields['status']);
        }
    }

    public function testAddFromTemplate()
    {
        $this->login();

        $ticket = $this->getNewITILObject('Ticket', true);
        $template = new \SolutionTemplate();
        $templates_id = $template->add([
            'name'               => 'test template',
            'content'            => 'test template',
        ]);
        $this->assertGreaterThan(0, $templates_id);
        $solution = new \ITILSolution();
        $solutions_id = $solution->add([
            '_templates_id'      => $templates_id,
            'itemtype'           => 'Ticket',
            'items_id'           => $ticket->fields['id'],
        ]);
        $this->assertGreaterThan(0, $solutions_id);

        $this->assertEquals('<p>test template</p>', $solution->fields['content']);

        //Reset ticket status
        $this->assertTrue(
            $ticket->update([
                'id'    => $ticket->fields['id'],
                'status' => \CommonITILObject::INCOMING,
            ])
        );
        $solutions_id = $solution->add([
            '_templates_id'      => $templates_id,
            'itemtype'           => 'Ticket',
            'items_id'           => $ticket->fields['id'],
            'content'            => 'test template2',
        ]);
        $this->assertGreaterThan(0, $solutions_id);

        $this->assertEquals('test template2', $solution->fields['content']);
    }

    public function testAddOnClosedTicket()
    {
        $this->login();
        // Create new ticket
        $ticket = $this->getNewITILObject('Ticket', true);
        // Close ticket
        $this->assertTrue(
            $ticket->update([
                'id'    => $ticket->fields['id'],
                'status' => \CommonITILObject::CLOSED,
            ])
        );
        // Create solution
        $solution = new \ITILSolution();
        $solutions_id = $solution->add([
            'itemtype'           => 'Ticket',
            'items_id'           => $ticket->fields['id'],
            'content'            => 'test solution',
        ]);
        $this->assertGreaterThan(0, $solutions_id);
        // Verify solution is not waiting for approval. Should default to being approved.
        $this->assertEquals(\CommonITILValidation::ACCEPTED, $solution->fields['status']);
        // Verify the ticket status is still closed.
        $this->assertTrue($ticket->getFromDB($ticket->fields['id']));
        $this->assertEquals(\CommonITILObject::CLOSED, $ticket->fields['status']);
    }

    public function testTicketSolutionValidation()
    {
        $tech_id     = getItemByTypeName('User', 'tech', true);
        $postonly_id = getItemByTypeName('User', 'post-only', true);

        // create ticket
        $this->login('post-only', 'postonly');
        $ticket = new Ticket();
        $ticket_id = (int) $ticket->add([
            'name'               => 'ticket title',
            'description'        => 'a description',
            'content'            => 'content',
            '_users_id_requester' => $postonly_id,
            '_users_id_assign'    => $tech_id,
        ]);
        $this->assertGreaterThan(0, $ticket_id);

        $this->assertFalse($ticket->isNewItem());
        $this->assertSame($ticket::ASSIGNED, $ticket->getField('status'));

        // add solution
        $this->login('tech', 'tech');
        $solution = new \ITILSolution();
        $solution_id = (int) $solution->add([
            'itemtype'           => $ticket::getType(),
            'items_id'           => $ticket->getID(),
            'content'            => 'a solution',
        ]);
        $this->assertGreaterThan(0, $solution_id);

        $this->assertTrue($ticket->getFromDB($ticket_id));
        $this->assertSame(\CommonITILValidation::WAITING, (int) $solution->getField('status'));
        $this->assertSame($ticket::SOLVED, (int) $ticket->getField('status'));

        //refuse solution
        $this->login('post-only', 'postonly');
        $follow = new \ITILFollowup();
        $follow_id = (int) $follow->add([
            'itemtype'  => $ticket::getType(),
            'items_id'   => $ticket->getID(),
            'add_reopen'   => '1',
            'content'      => 'This is required',
        ]);
        $this->assertGreaterThan(0, $follow_id);

        $this->assertTrue($follow->getFromDB($follow_id));
        $this->assertTrue($solution->getFromDB($solution_id));
        $this->assertTrue($ticket->getFromDB($ticket_id));
        $this->assertSame(\CommonITILValidation::REFUSED, (int) $solution->fields['status']);
        $this->assertSame($ticket::ASSIGNED, (int) $ticket->fields['status']);

        // add solution
        $this->login('tech', 'tech');
        $solution = new \ITILSolution();
        $solution_id = (int) $solution->add([
            'itemtype'           => $ticket::getType(),
            'items_id'           => $ticket->getID(),
            'content'            => 'a solution',
        ]);
        $this->assertGreaterThan(0, $solution_id);

        $this->assertTrue($ticket->getFromDB($ticket_id));
        $this->assertSame(\CommonITILValidation::WAITING, (int) $solution->getField('status'));
        $this->assertSame($ticket::SOLVED, (int) $ticket->getField('status'));

        //approve solution
        $this->login('post-only', 'postonly');
        $this->assertTrue($ticket->getFromDB($ticket_id));
        $this->assertTrue($ticket->needReopen());
        $follow = new \ITILFollowup();
        $follow_id = (int) $follow->add([
            'itemtype'  => $ticket::getType(),
            'items_id'   => $ticket->getID(),
            'add_close'    => '1',
        ]);
        $this->assertGreaterThan(0, $follow_id);

        $this->assertTrue($follow->getFromDB($follow_id));
        $this->assertTrue($solution->getFromDB($solution_id));
        $this->assertTrue($ticket->getFromDB($ticket_id));
        $this->assertSame(\CommonITILValidation::ACCEPTED, (int) $solution->fields['status']);
        $this->assertSame($ticket::CLOSED, (int) $ticket->fields['status']);
    }

    /**
     * Ticket of "post-only", assigned to and solved by "tech", who stays logged in.
     *
     * @return array{Ticket, \ITILSolution}
     */
    private function createSolvedTicket(): array
    {
        $this->login('post-only', 'postonly');
        $ticket = $this->createItem(Ticket::class, [
            'name'                => 'ticket title',
            'content'             => 'content',
            '_users_id_requester' => getItemByTypeName('User', 'post-only', true),
            '_users_id_assign'    => getItemByTypeName('User', 'tech', true),
        ]);

        $this->login('tech', 'tech');
        return [$ticket, $this->addSolution($ticket)];
    }

    /**
     * Change of the current user, with its solution, accepted at once.
     *
     * @return array{\Change, \ITILSolution}
     */
    private function createSolvedChange(): array
    {
        $this->login();
        $change = $this->createItem(\Change::class, [
            'name'                => 'change title',
            'content'             => 'content',
            'entities_id'         => $this->getTestRootEntity(only_id: true),
            '_users_id_requester' => \Session::getLoginUserID(),
        ]);
        $solution = $this->addSolution($change);
        $this->assertSame(\CommonITILValidation::ACCEPTED, (int) $solution->fields['status']);
        $this->assertTrue($change->getFromDB($change->getID()));
        $this->assertTrue($change->isSolved());

        return [$change, $solution];
    }

    private function addSolution(\CommonITILObject $item): \ITILSolution
    {
        return $this->createItem(\ITILSolution::class, [
            'itemtype' => $item::class,
            'items_id' => $item->getID(),
            'content'  => 'a solution',
        ]);
    }

    private function addFollowup(\CommonITILObject $item, array $input): \ITILFollowup
    {
        return $this->createItem(\ITILFollowup::class, [
            'itemtype' => $item::class,
            'items_id' => $item->getID(),
        ] + $input, ['add_reopen', 'add_close']);
    }

    private function assertSolutionAnswer(\ITILSolution $solution, int $status, int $itilfollowups_id): void
    {
        $this->assertTrue($solution->getFromDB($solution->getID()));
        $this->assertSame($status, (int) $solution->fields['status']);
        $this->assertSame($itilfollowups_id, (int) $solution->fields['itilfollowups_id']);
    }

    public function testSolutionIsLinkedToItsAnswerFollowup()
    {
        [$ticket, $refused] = $this->createSolvedTicket();

        $this->login('post-only', 'postonly');
        $reason = $this->addFollowup($ticket, ['add_reopen' => '1', 'content' => 'The reason of the refusal']);
        $this->assertSolutionAnswer($refused, \CommonITILValidation::REFUSED, $reason->getID());

        // Another followup does not replace the reason of the refusal
        $this->addFollowup($ticket, ['content' => 'Another followup']);
        $this->assertSolutionAnswer($refused, \CommonITILValidation::REFUSED, $reason->getID());

        $this->login('tech', 'tech');
        $approved = $this->addSolution($ticket);
        $this->login('post-only', 'postonly');
        $approval = $this->addFollowup($ticket, ['add_close' => '1']);
        $this->assertSolutionAnswer($approved, \CommonITILValidation::ACCEPTED, $approval->getID());
    }

    public function testSolutionIsNotLinkedToAFollowupThatDidNotAnswerIt()
    {
        [$ticket, $solution] = $this->createSolvedTicket();

        // Refused by a status change: no reason of the refusal
        $this->updateItem(Ticket::class, $ticket->getID(), ['status' => Ticket::ASSIGNED]);
        $this->assertSolutionAnswer($solution, \CommonITILValidation::REFUSED, 0);

        // Neither a later answer of the requester (e.g. a collected mail),
        // nor an approval sent from an outdated page
        $this->login('post-only', 'postonly');
        $this->addFollowup($ticket, ['add_reopen' => '1', 'content' => 'An answer']);
        $this->addFollowup($ticket, ['add_close' => '1']);
        $this->assertSolutionAnswer($solution, \CommonITILValidation::REFUSED, 0);

        // Nor a reopening answer of someone who cannot approve the solution (e.g. a mail of the technician)
        $this->login('tech', 'tech');
        $solution = $this->addSolution($ticket);
        $this->addFollowup($ticket, ['add_reopen' => '1', 'content' => 'Thanks']);
        $this->assertSolutionAnswer($solution, \CommonITILValidation::REFUSED, 0);
    }

    public function testSolutionIsLinkedToAFollowupReopeningTheItem()
    {
        [$ticket, $solution] = $this->createSolvedTicket();

        // Reopened without "add_reopen" (e.g. from the API)
        $this->login('post-only', 'postonly');
        $followup = $this->addFollowup($ticket, ['content' => 'It still does not work']);

        $this->assertTrue($ticket->getFromDB($ticket->getID()));
        $this->assertFalse($ticket->isSolved());
        $this->assertSolutionAnswer($solution, \CommonITILValidation::REFUSED, $followup->getID());
    }

    public function testSolutionApprovers()
    {
        $this->login();

        $postonly_id = getItemByTypeName('User', 'post-only', true);
        $normal_id   = getItemByTypeName('User', 'normal', true);
        $tech_id     = getItemByTypeName('User', 'tech', true);
        $group = $this->createItem(\Group::class, ['name' => 'requester group']);
        $this->createItem(\Group_User::class, ['groups_id' => $group->getID(), 'users_id' => $normal_id]);

        $ticket = $this->createItem(Ticket::class, [
            'name'                 => 'ticket title',
            'content'              => 'content',
            '_users_id_requester'  => $postonly_id,
            '_groups_id_requester' => $group->getID(),
        ]);

        $approvers = $ticket->getSolutionApprovers();
        $this->assertContains($postonly_id, $approvers['users']);
        $this->assertSame([$group->getID()], $approvers['groups']);

        $this->assertTrue($ticket->canUserApproveSolution($postonly_id));
        $this->assertTrue($ticket->canUserApproveSolution($normal_id));
        $this->assertFalse($ticket->canUserApproveSolution($tech_id));
        $this->assertFalse($ticket->canUserApproveSolution(0));

        // Members of a parent group with recursive membership
        $parent = $this->createItem(\Group::class, ['name' => 'parent group', 'recursive_membership' => 1]);
        $this->createItem(\Group_User::class, ['groups_id' => $parent->getID(), 'users_id' => $tech_id]);
        $this->assertFalse($ticket->canUserApproveSolution($tech_id));
        $this->updateItem(\Group::class, $group->getID(), ['groups_id' => $parent->getID()]);
        $this->assertTrue($ticket->canUserApproveSolution($tech_id));

        $this->updateItem(\Group::class, $parent->getID(), ['recursive_membership' => 0]);
        $this->assertFalse($ticket->canUserApproveSolution($tech_id));
    }

    public function testRefusalOfAnAcceptedSolutionIsLinked()
    {
        [$change, $solution] = $this->createSolvedChange();

        $reason = $this->addFollowup($change, ['add_reopen' => '1', 'content' => 'The reason of the refusal']);
        $this->assertSolutionAnswer($solution, \CommonITILValidation::REFUSED, $reason->getID());

        // Unlinked once the answer is purged
        $this->deleteItem(\ITILFollowup::class, $reason->getID(), true);
        $this->assertTrue($solution->getFromDB($solution->getID()));
        $this->assertNull($solution->fields['itilfollowups_id']);
    }

    public function testReopeningOfAClosedItemIsNotLinked()
    {
        [$ticket, $solution] = $this->createSolvedTicket();

        // Closed without approval (e.g. automatic closure): the solution stays waiting
        $this->updateItem(Ticket::class, $ticket->getID(), ['status' => Ticket::CLOSED]);
        $this->assertSolutionAnswer($solution, \CommonITILValidation::WAITING, 0);

        $this->login('post-only', 'postonly');
        $this->addFollowup($ticket, ['add_reopen' => '1', 'content' => 'Another problem']);

        $this->assertTrue($ticket->getFromDB($ticket->getID()));
        $this->assertFalse($ticket->isClosed());
        $this->assertSolutionAnswer($solution, \CommonITILValidation::REFUSED, 0);
    }

    public function testTriggerAloneDoesNotUpdateTheItem()
    {
        $this->login();
        $ticket = $this->createItem(Ticket::class, [
            'name'    => 'ticket title',
            'content' => 'content',
        ]);
        $followup = $this->addFollowup($ticket, ['content' => 'a followup']);

        // Status not allowed for this user: nothing is left to update
        $this->login('post-only', 'postonly');
        $this->assertTrue($ticket->getFromDB($ticket->getID()));
        $this->assertFalse($ticket->update([
            'id'       => $ticket->getID(),
            'status'   => Ticket::WAITING,
            '_trigger' => $followup,
        ]));
    }

    private function renderForm(\CommonITILObject $item): Crawler
    {
        ob_start();
        $item->showForm($item->getID());
        return new Crawler(ob_get_clean());
    }

    public function testApprovalFormIsDisplayedForAnAcceptedSolution()
    {
        [$change, $solution] = $this->createSolvedChange();

        $form = $this->renderForm($change)->filter('#ITILSolution_' . $solution->getID() . ' [data-testid="solution-approval-form"]');
        $this->assertCount(1, $form);
        $this->assertCount(1, $form->filter('button[name="add_close"]'));
        $this->assertCount(1, $form->filter('button[name="add_reopen"]'));
    }

    public function testApprovalFormIsDisplayedOnTheSolutionItApproves()
    {
        global $DB;

        [$ticket, $first] = $this->createSolvedTicket();
        $this->login('post-only', 'postonly');
        $this->addFollowup($ticket, ['add_reopen' => '1', 'content' => 'It still does not work']);
        $this->login('tech', 'tech');
        $second = $this->addSolution($ticket);

        // The approval answers the most recent solution, which is not always the last one added
        $DB->update(\ITILSolution::getTable(), ['date_creation' => date('Y-m-d H:i:s', strtotime('+1 hour'))], ['id' => $first->getID()]);

        $this->login('post-only', 'postonly');
        $this->assertTrue($ticket->getFromDB($ticket->getID()));
        $crawler = $this->renderForm($ticket);
        $this->assertCount(1, $crawler->filter('[data-testid="solution-approval-form"]'));
        $this->assertCount(1, $crawler->filter('#ITILSolution_' . $first->getID() . ' [data-testid="solution-approval-form"]'));
    }

    public function testApprovalFormIsDisplayedForAnItemSolvedWithoutSolution()
    {
        $this->login('post-only', 'postonly');
        $ticket = $this->createItem(Ticket::class, [
            'name'                => 'ticket title',
            'content'             => 'content',
            '_users_id_requester' => getItemByTypeName('User', 'post-only', true),
        ]);
        $this->login('tech', 'tech');
        $this->updateItem(Ticket::class, $ticket->getID(), ['status' => Ticket::SOLVED]);

        $this->login('post-only', 'postonly');
        $this->assertTrue($ticket->getFromDB($ticket->getID()));
        $form = $this->renderForm($ticket)->filter('[data-testid="solution-approval-form"]');
        $this->assertCount(1, $form);
        $this->assertCount(1, $form->filter('button[name="add_close"]'));
        $this->assertCount(1, $form->filter('button[name="add_reopen"]'));
    }

    public function testSolutionApproversAreAnonymizedInTheHelpdesk()
    {
        // The configuration of the active entity of the requester applies
        $this->login('post-only', 'postonly');
        $entities_id = \Session::getActiveEntity();
        $this->login();
        $this->updateItem(\Entity::class, $entities_id, [
            'anonymize_support_agents' => \Entity::ANONYMIZE_USE_GENERIC,
        ]);

        // Written by the technician, who can approve the solution
        $this->login('tech', 'tech');
        $ticket = $this->createItem(Ticket::class, [
            'name'                => 'ticket title',
            'content'             => 'content',
            '_users_id_requester' => getItemByTypeName('User', 'post-only', true),
        ]);
        $this->addSolution($ticket);
        $this->assertContains(getItemByTypeName('User', 'tech', true), $ticket->getSolutionApprovers()['users']);

        $this->login('post-only', 'postonly');
        $this->assertTrue($ticket->getFromDB($ticket->getID()));
        $approval = $this->renderForm($ticket)->filter('[data-testid="solution-approval"]')->text();
        $this->assertStringContainsString('Helpdesk user', $approval);
        $this->assertStringNotContainsString('tech', $approval);
    }

    public function testAddEmptyContent()
    {
        $this->login();

        $ticket = new Ticket();
        $ticket_id = $ticket->add([
            'name'               => 'ticket title',
            'description'        => 'a description',
            'content'            => 'test',
        ]);
        $this->assertGreaterThan(0, $ticket_id);

        $solution = new \ITILSolution();
        $solution_id = $solution->add([
            'itemtype'           => $ticket::getType(),
            'items_id'           => $ticket->getID(),
            'content'            => '',
        ]);
        $this->assertGreaterThan(0, $solution_id);
    }
}
