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

use CommonITILObject;
use DbTestCase;
use Glpi\Toolbox\Sanitizer;
use Ticket;

/* Test for inc/itilsolution.class.php */

class ITILSolutionTest extends DbTestCase
{
    /**
     * Create a new ITILObject and return its id or the object
     *
     * @param string $itemtype ITILObject parent to test followups on
     * @param bool   $as_object
     * @return integer|\CommonDBTM
     */
    private function getNewITILObject(string $itemtype, bool $as_object = false)
    {
        //create reference ITILObject
        $itilobject = new $itemtype();
        $this->assertGreaterThan(
            0,
            (int)$itilobject->add([
                'name'         => "$itemtype title",
                'description'  => 'a description',
                'content'      => '',
                'entities_id'  => getItemByTypeName('Entity', '_test_root_entity', true),
            ])
        );

        $this->assertFalse($itilobject->isNewItem());
        $this->assertTrue($itilobject->can($itilobject->getID(), \READ));
        return $as_object ? $itilobject : (int)$itilobject->getID();
    }

    public function testTicketSolution()
    {
        $this->login();

        $uid = getItemByTypeName('User', TU_USER, true);
        $ticket = new Ticket();
        $this->assertGreaterThan(
            0,
            (int)$ticket->add([
                'name'               => 'ticket title',
                'description'        => 'a description',
                'content'            => '',
                '_users_id_assign'   => $uid
            ])
        );

        $this->assertFalse($ticket->isNewItem());
        $this->assertSame($ticket::ASSIGNED, $ticket->getField('status'));

        $solution = new \ITILSolution();
        $this->assertGreaterThan(
            0,
            (int)$solution->add([
                'itemtype'  => $ticket::getType(),
                'items_id'  => $ticket->getID(),
                'content'   => 'Current friendly ticket\r\nis solved!'
            ])
        );
        //reload from DB
        $this->assertTrue($ticket->getFromDB($ticket->getID()));

        $this->assertEquals($ticket::SOLVED, $ticket->getField('status'));
        $this->assertSame("Current friendly ticket\r\nis solved!", $solution->getField('content'));

        $this->assertTrue($solution->getFromDB($solution->getID()));
        $this->assertSame(\CommonITILValidation::WAITING, (int)$solution->fields['status']);

        //approve solution
        $follow = new \ITILFollowup();
        $this->assertGreaterThan(
            0,
            (int)$follow->add([
                'itemtype'  => $ticket::getType(),
                'items_id'   => $ticket->getID(),
                'add_close'    => '1'
            ])
        );
        $this->assertTrue($follow->getFromDB($follow->getID()));
        $this->assertTrue($solution->getFromDB($solution->getID()));
        $this->assertTrue($ticket->getFromDB($ticket->getID()));
        $this->assertSame(\CommonITILValidation::ACCEPTED, (int)$solution->fields['status']);
        $this->assertSame($ticket::CLOSED, (int)$ticket->fields['status']);

        //reopen ticket
        $this->assertGreaterThan(
            0,
            (int)$follow->add([
                'itemtype'  => $ticket::getType(),
                'items_id'  => $ticket->getID(),
                'add_reopen'   => '1',
                'content'      => 'This is required'
            ])
        );
        $this->assertTrue($ticket->getFromDB($ticket->getID()));
        $this->assertTrue($solution->getFromDB($solution->getID()));

        $this->assertSame($ticket::ASSIGNED, (int)$ticket->fields['status']);
        $this->assertSame(\CommonITILValidation::REFUSED, (int)$solution->fields['status']);

        $this->assertGreaterThan(
            0,
            (int)$solution->add([
                'itemtype'  => $ticket::getType(),
                'items_id'  => $ticket->getID(),
                'content'   => 'Another solution proposed!'
            ])
        );
        //reload from DB
        $this->assertTrue($ticket->getFromDB($ticket->getID()));
        $this->assertTrue($solution->getFromDB($solution->getID()));

        $this->assertEquals($ticket::SOLVED, $ticket->getField('status'));
        $this->assertSame(\CommonITILValidation::WAITING, (int)$solution->fields['status']);

        //refuse
        $follow = new \ITILFollowup();
        $this->assertGreaterThan(
            0,
            (int)$follow->add([
                'itemtype'   => 'Ticket',
                'items_id'   => $ticket->getID(),
                'add_reopen'   => '1',
                'content'      => 'This is required'
            ])
        );

        //reload from DB
        $this->assertTrue($ticket->getFromDB($ticket->getID()));
        $this->assertTrue($solution->getFromDB($solution->getID()));

        $this->assertSame($ticket::ASSIGNED, (int)$ticket->fields['status']);
        $this->assertSame(\CommonITILValidation::REFUSED, (int)$solution->fields['status']);

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
            (int)$problem->add([
                'name'               => 'problem title',
                'description'        => 'a description',
                'content'            => 'a content',
                '_users_id_assign'   => $uid
            ])
        );

        $this->assertFalse($problem->isNewItem());
        $this->assertSame($problem::ASSIGNED, $problem->getField('status'));

        $solution = new \ITILSolution();
        $this->assertGreaterThan(
            0,
            (int)$solution->add([
                'itemtype'  => $problem::getType(),
                'items_id'  => $problem->getID(),
                'content'   => 'Current friendly problem\r\nis solved!'
            ])
        );
        //reload from DB
        $this->assertTrue($problem->getFromDB($problem->getID()));

        $this->assertEquals($problem::SOLVED, $problem->getField('status'));
        $this->assertSame("Current friendly problem\r\nis solved!", $solution->getField('content'));

        $this->assertTrue($solution->getFromDB($solution->getID()));
        $this->assertSame(\CommonITILValidation::ACCEPTED, (int)$solution->fields['status']);
    }

    public function testChangeSolution()
    {
        $this->login();
        $uid = getItemByTypeName('User', TU_USER, true);

        $change = new \Change();
        $this->assertGreaterThan(
            0,
            (int)$change->add([
                'name'               => 'change title',
                'description'        => 'a description',
                'content'            => 'a content',
                '_users_id_assign'   => $uid
            ])
        );

        $this->assertFalse($change->isNewItem());
        $this->assertSame($change::INCOMING, $change->getField('status'));

        $solution = new \ITILSolution();
        $this->assertGreaterThan(
            0,
            (int)$solution->add([
                'itemtype'  => $change::getType(),
                'items_id'  => $change->getID(),
                'content'   => 'Current friendly change\r\nis solved!'
            ])
        );
        //reload from DB
        $this->assertTrue($change->getFromDB($change->getID()));

        $this->assertEquals($change::SOLVED, $change->getField('status'));
        $this->assertSame("Current friendly change\r\nis solved!", $solution->getField('content'));

        $this->assertTrue($solution->getFromDB($solution->getID()));
        $this->assertSame(\CommonITILValidation::ACCEPTED, (int)$solution->fields['status']);
    }


    public function testSolutionOnDuplicate()
    {
        $this->login();
        $this->setEntity('Root entity', true);

        $uid = getItemByTypeName('User', TU_USER, true);
        $ticket = new Ticket();
        $duplicated = (int)$ticket->add([
            'name'               => 'Duplicated ticket',
            'description'        => 'A ticket that will be duplicated',
            'content'            => 'a content',
            '_users_id_assign'   => $uid
        ]);
        $this->assertGreaterThan(0, $duplicated);

        $duplicate = (int)$ticket->add([
            'name'               => 'Duplicate ticket',
            'description'        => 'A ticket that is a duplicate',
            'content'            => 'a content',
            '_users_id_assign'   => $uid
        ]);
        $this->assertGreaterThan(0, $duplicate);

        $link = new \Ticket_Ticket();
        $this->assertGreaterThan(
            0,
            (int)$link->add([
                'tickets_id_1' => $duplicated,
                'tickets_id_2' => $duplicate,
                'link'         => \Ticket_Ticket::DUPLICATE_WITH
            ])
        );

        //we got one ticket, and another that duplicates it.
        //let's manage solutions on them
        $solution = new \ITILSolution();
        $this->assertGreaterThan(
            0,
            (int)$solution->add([
                'itemtype'  => $ticket::getType(),
                'items_id'  => $duplicate,
                'content'   => 'Solve from main ticket'
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
            (int)$ticket->add([
                'name'               => 'ticket title',
                'description'        => 'a description',
                'content'            => 'a content',
                '_users_id_assign'   => $uid
            ])
        );

        $this->assertFalse($ticket->isNewItem());
        $this->assertSame($ticket::ASSIGNED, $ticket->getField('status'));

        $solution = new \ITILSolution();

        // 1st solution, it should be accepted
        $this->assertGreaterThan(
            0,
            (int)$solution->add([
                'itemtype'  => $ticket::getType(),
                'items_id'  => $ticket->getID(),
                'content'   => '1st solution, should be accepted!'
            ])
        );

        $this->assertTrue($solution->getFromDB($solution->getID()));
        $this->assertSame(\CommonITILValidation::WAITING, (int)$solution->fields['status']);

        // try to add directly another solution, it should be refused
        $this->assertFalse(
            $solution->add([
                'itemtype'  => $ticket::getType(),
                'items_id'  => $ticket->getID(),
                'content'   => '2nd solution, should be refused!'
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
            'content' => Sanitizer::sanitize(<<<HTML
<p>Test with a ' (add)</p>
<p><img id="3e29dffe-0237ea21-5e5e7034b1d1a1.00000000" src="data:image/png;base64,{$base64Image}" width="12" height="12"></p>
HTML
            ),
            '_filename' => [
                $filename,
            ],
            '_tag_filename' => [
                '3e29dffe-0237ea21-5e5e7034b1d1a1.00000000',
            ],
            '_prefix_filename' => [
                '5e5e92ffd9bd91.11111111',
            ]
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
            'content' => Sanitizer::sanitize(<<<HTML
<p>Test with a ' (update)</p>
<p><img id="3e29dffe-0237ea21-5e5e7034b1d1a1.33333333" src="data:image/png;base64,{$base64Image}" width="12" height="12"></p>
HTML
            ),
            '_filename' => [
                $filename,
            ],
            '_tag_filename' => [
                '3e29dffe-0237ea21-5e5e7034b1d1a1.33333333',
            ],
            '_prefix_filename' => [
                '5e5e92ffd9bd91.44444444',
            ]
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
                'content'  => 'test'
            ]);
            $this->assertGreaterThan(0, $id);

            $this->assertTrue($ticket->getFromDB($ticket->fields['id']));
            $this->assertEquals(\Ticket::SOLVED, $ticket->fields['status']);
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

        $this->assertEquals('&#60;p&#62;test template&#60;/p&#62;', $solution->fields['content']);

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
        $ticket_id = (int)$ticket->add([
            'name'               => 'ticket title',
            'description'        => 'a description',
            'content'            => '',
            '_users_id_requester' => $postonly_id,
            '_users_id_assign'    => $tech_id,
        ]);
        $this->assertGreaterThan(0, $ticket_id);

        $this->assertFalse($ticket->isNewItem());
        $this->assertSame($ticket::ASSIGNED, $ticket->getField('status'));

        // add solution
        $this->login('tech', 'tech');
        $solution = new \ITILSolution();
        $solution_id = (int)$solution->add([
            'itemtype'           => $ticket::getType(),
            'items_id'           => $ticket->getID(),
            'content'            => 'a solution',
        ]);
        $this->assertGreaterThan(0, $solution_id);

        $this->assertTrue($ticket->getFromDB($ticket_id));
        $this->assertSame(\CommonITILValidation::WAITING, (int)$solution->getField('status'));
        $this->assertSame($ticket::SOLVED, (int)$ticket->getField('status'));

        //refuse solution
        $this->login('post-only', 'postonly');
        $follow = new \ITILFollowup();
        $follow_id = (int)$follow->add([
            'itemtype'  => $ticket::getType(),
            'items_id'   => $ticket->getID(),
            'add_reopen'   => '1',
            'content'      => 'This is required'
        ]);
        $this->assertGreaterThan(0, $follow_id);

        $this->assertTrue($follow->getFromDB($follow_id));
        $this->assertTrue($solution->getFromDB($solution_id));
        $this->assertTrue($ticket->getFromDB($ticket_id));
        $this->assertSame(\CommonITILValidation::REFUSED, (int)$solution->fields['status']);
        $this->assertSame($ticket::ASSIGNED, (int)$ticket->fields['status']);

        // add solution
        $this->login('tech', 'tech');
        $solution = new \ITILSolution();
        $solution_id = (int)$solution->add([
            'itemtype'           => $ticket::getType(),
            'items_id'           => $ticket->getID(),
            'content'            => 'a solution',
        ]);
        $this->assertGreaterThan(0, $solution_id);

        $this->assertTrue($ticket->getFromDB($ticket_id));
        $this->assertSame(\CommonITILValidation::WAITING, (int)$solution->getField('status'));
        $this->assertSame($ticket::SOLVED, (int)$ticket->getField('status'));

        //approve solution
        $this->login('post-only', 'postonly');
        $this->assertTrue($ticket->getFromDB($ticket_id));
        $this->assertTrue($ticket->needReopen());
        $follow = new \ITILFollowup();
        $follow_id = (int)$follow->add([
            'itemtype'  => $ticket::getType(),
            'items_id'   => $ticket->getID(),
            'add_close'    => '1'
        ]);
        $this->assertGreaterThan(0, $follow_id);

        $this->assertTrue($follow->getFromDB($follow_id));
        $this->assertTrue($solution->getFromDB($solution_id));
        $this->assertTrue($ticket->getFromDB($ticket_id));
        $this->assertSame(\CommonITILValidation::ACCEPTED, (int)$solution->fields['status']);
        $this->assertSame($ticket::CLOSED, (int)$ticket->fields['status']);
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
