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

class ITILSolution extends DbTestCase
{
    /**
     * Create a new ITILObject and return its id or the object
     *
     * @param string $itemtype ITILObject parent to test followups on
     * @param bool   $as_object
     * @return integer|\CommonDBTM
     */
    private function getNewITILObject($itemtype, bool $as_object = false)
    {
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
        return $as_object ? $itilobject : (int)$itilobject->getID();
    }

    public function testTicketSolution()
    {
        $this->login();

        $uid = getItemByTypeName('User', TU_USER, true);
        $ticket = new Ticket();
        $this->integer((int)$ticket->add([
            'name'               => 'ticket title',
            'description'        => 'a description',
            'content'            => '',
            '_users_id_assign'   => $uid
        ]))->isGreaterThan(0);

        $this->boolean($ticket->isNewItem())->isFalse();
        $this->variable($ticket->getField('status'))->isIdenticalTo($ticket::ASSIGNED);

        $solution = new \ITILSolution();
        $this->integer(
            (int)$solution->add([
                'itemtype'  => $ticket::getType(),
                'items_id'  => $ticket->getID(),
                'content'   => 'Current friendly ticket\r\nis solved!'
            ])
        );
       //reload from DB
        $this->boolean($ticket->getFromDB($ticket->getID()))->isTrue();

        $this->variable($ticket->getField('status'))->isEqualTo($ticket::SOLVED);
        $this->string($solution->getField('content'))->isIdenticalTo("Current friendly ticket\r\nis solved!");

        $this->boolean($solution->getFromDB($solution->getID()))->isTrue();
        $this->integer((int)$solution->fields['status'])->isIdenticalTo(\CommonITILValidation::WAITING);

       //approve solution
        $follow = new \ITILFollowup();
        $this->integer(
            (int)$follow->add([
                'itemtype'  => $ticket::getType(),
                'items_id'   => $ticket->getID(),
                'add_close'    => '1'
            ])
        )->isGreaterThan(0);
        $this->boolean($follow->getFromDB($follow->getID()))->isTrue();
        $this->boolean($solution->getFromDB($solution->getID()))->isTrue();
        $this->boolean($ticket->getFromDB($ticket->getID()))->isTrue();
        $this->integer((int)$solution->fields['status'])->isIdenticalTo(\CommonITILValidation::ACCEPTED);
        $this->integer((int)$ticket->fields['status'])->isIdenticalTo($ticket::CLOSED);

       //reopen ticket
        $this->integer(
            (int)$follow->add([
                'itemtype'  => $ticket::getType(),
                'items_id'  => $ticket->getID(),
                'add_reopen'   => '1',
                'content'      => 'This is required'
            ])
        )->isGreaterThan(0);
        $this->boolean($ticket->getFromDB($ticket->getID()))->isTrue();
        $this->boolean($solution->getFromDB($solution->getID()))->isTrue();

        $this->integer((int)$ticket->fields['status'])->isIdenticalTo($ticket::ASSIGNED);
        $this->integer((int)$solution->fields['status'])->isIdenticalTo(\CommonITILValidation::REFUSED);

        $this->integer(
            (int)$solution->add([
                'itemtype'  => $ticket::getType(),
                'items_id'  => $ticket->getID(),
                'content'   => 'Another solution proposed!'
            ])
        );
       //reload from DB
        $this->boolean($ticket->getFromDB($ticket->getID()))->isTrue();
        $this->boolean($solution->getFromDB($solution->getID()))->isTrue();

        $this->variable($ticket->getField('status'))->isEqualTo($ticket::SOLVED);
        $this->integer((int)$solution->fields['status'])->isIdenticalTo(\CommonITILValidation::WAITING);

       //refuse
        $follow = new \ITILFollowup();
        $this->integer(
            (int)$follow->add([
                'itemtype'   => 'Ticket',
                'items_id'   => $ticket->getID(),
                'add_reopen'   => '1',
                'content'      => 'This is required'
            ])
        )->isGreaterThan(0);

       //reload from DB
        $this->boolean($ticket->getFromDB($ticket->getID()))->isTrue();
        $this->boolean($solution->getFromDB($solution->getID()))->isTrue();

        $this->integer((int)$ticket->fields['status'])->isIdenticalTo($ticket::ASSIGNED);
        $this->integer((int)$solution->fields['status'])->isIdenticalTo(\CommonITILValidation::REFUSED);

        $this->integer(
            $solution::countFor(
                'Ticket',
                $ticket->getID()
            )
        )->isIdenticalTo(2);
    }

    public function testProblemSolution()
    {
        $this->login();
        $uid = getItemByTypeName('User', TU_USER, true);

        $problem = new \Problem();
        $this->integer((int)$problem->add([
            'name'               => 'problem title',
            'description'        => 'a description',
            'content'            => 'a content',
            '_users_id_assign'   => $uid
        ]))->isGreaterThan(0);

        $this->boolean($problem->isNewItem())->isFalse();
        $this->variable($problem->getField('status'))->isIdenticalTo($problem::ASSIGNED);

        $solution = new \ITILSolution();
        $this->integer(
            (int)$solution->add([
                'itemtype'  => $problem::getType(),
                'items_id'  => $problem->getID(),
                'content'   => 'Current friendly problem\r\nis solved!'
            ])
        );
       //reload from DB
        $this->boolean($problem->getFromDB($problem->getID()))->isTrue();

        $this->variable($problem->getField('status'))->isEqualTo($problem::SOLVED);
        $this->string($solution->getField('content'))->isIdenticalTo("Current friendly problem\r\nis solved!");

        $this->boolean($solution->getFromDB($solution->getID()))->isTrue();
        $this->integer((int)$solution->fields['status'])->isIdenticalTo(\CommonITILValidation::ACCEPTED);
    }

    public function testChangeSolution()
    {
        $this->login();
        $uid = getItemByTypeName('User', TU_USER, true);

        $change = new \Change();
        $this->integer((int)$change->add([
            'name'               => 'change title',
            'description'        => 'a description',
            'content'            => 'a content',
            '_users_id_assign'   => $uid
        ]))->isGreaterThan(0);

        $this->boolean($change->isNewItem())->isFalse();
        $this->variable($change->getField('status'))->isIdenticalTo($change::INCOMING);

        $solution = new \ITILSolution();
        $this->integer(
            (int)$solution->add([
                'itemtype'  => $change::getType(),
                'items_id'  => $change->getID(),
                'content'   => 'Current friendly change\r\nis solved!'
            ])
        );
       //reload from DB
        $this->boolean($change->getFromDB($change->getID()))->isTrue();

        $this->variable($change->getField('status'))->isEqualTo($change::SOLVED);
        $this->string($solution->getField('content'))->isIdenticalTo("Current friendly change\r\nis solved!");

        $this->boolean($solution->getFromDB($solution->getID()))->isTrue();
        $this->integer((int)$solution->fields['status'])->isIdenticalTo(\CommonITILValidation::ACCEPTED);
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
        $this->integer($duplicated)->isGreaterThan(0);

        $duplicate = (int)$ticket->add([
            'name'               => 'Duplicate ticket',
            'description'        => 'A ticket that is a duplicate',
            'content'            => 'a content',
            '_users_id_assign'   => $uid
        ]);
        $this->integer($duplicate)->isGreaterThan(0);

        $link = new \Ticket_Ticket();
        $this->integer(
            (int)$link->add([
                'tickets_id_1' => $duplicated,
                'tickets_id_2' => $duplicate,
                'link'         => \Ticket_Ticket::DUPLICATE_WITH
            ])
        )->isGreaterThan(0);

       //we got one ticketg, and another that duplicates it.
       //let's manage solutions on them
        $solution = new \ITILSolution();
        $this->integer(
            (int)$solution->add([
                'itemtype'  => $ticket::getType(),
                'items_id'  => $duplicate,
                'content'   => 'Solve from main ticket'
            ])
        );
       //reload from DB
        $this->boolean($ticket->getFromDB($duplicate))->isTrue();
        $this->variable($ticket->getField('status'))->isEqualTo($ticket::SOLVED);

        $this->boolean($ticket->getFromDB($duplicated))->isTrue();
        $this->variable($ticket->getField('status'))->isEqualTo($ticket::SOLVED);
    }

    public function testMultipleSolution()
    {
        $this->login();

        $uid = getItemByTypeName('User', TU_USER, true);
        $ticket = new Ticket();
        $this->integer((int)$ticket->add([
            'name'               => 'ticket title',
            'description'        => 'a description',
            'content'            => 'a content',
            '_users_id_assign'   => $uid
        ]))->isGreaterThan(0);

        $this->boolean($ticket->isNewItem())->isFalse();
        $this->variable($ticket->getField('status'))->isIdenticalTo($ticket::ASSIGNED);

        $solution = new \ITILSolution();

       // 1st solution, it should be accepted
        $this->integer(
            (int)$solution->add([
                'itemtype'  => $ticket::getType(),
                'items_id'  => $ticket->getID(),
                'content'   => '1st solution, should be accepted!'
            ])
        );

        $this->boolean($solution->getFromDB($solution->getID()))->isTrue();
        $this->integer((int)$solution->fields['status'])->isIdenticalTo(\CommonITILValidation::WAITING);

       // try to add directly another solution, it should be refused
        $this->boolean(
            $solution->add([
                'itemtype'  => $ticket::getType(),
                'items_id'  => $ticket->getID(),
                'content'   => '2nd solution, should be refused!'
            ])
        )->isFalse();
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
        $this->boolean($ticket->isNewItem())->isFalse();

        $base64Image = base64_encode(file_get_contents(__DIR__ . '/../fixtures/uploads/foo.png'));
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
        copy(__DIR__ . '/../fixtures/uploads/foo.png', GLPI_TMP_DIR . '/' . $filename);

        $instance->add($input);
        $this->boolean($instance->isNewItem())->isFalse();
        $this->boolean($instance->getFromDB($instance->getId()))->isTrue();
        $expected = 'a href="/front/document.send.php?docid=';
        $this->string($instance->fields['content'])->contains($expected);

       // Test uploads for item update
        $base64Image = base64_encode(file_get_contents(__DIR__ . '/../fixtures/uploads/bar.png'));
        $filename = '5e5e92ffd9bd91.44444444image_paste55555555.png';
        copy(__DIR__ . '/../fixtures/uploads/bar.png', GLPI_TMP_DIR . '/' . $filename);
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
        $this->boolean($success)->isTrue();
        $this->boolean($instance->getFromDB($instance->getId()))->isTrue();
        $expected = 'a href="/front/document.send.php?docid=';
        $this->string($instance->fields['content'])->contains($expected);
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
            $this->integer($id);

            $ticket = new Ticket();
            $ticket->getFromDB($id);
            $tickets[] = $ticket;
        }

       // Solve all created tickets
        foreach ($tickets as $ticket) {
            $id = $em_solution->add([
                'itemtype' => $ticket::getType(),
                'items_id' => $ticket->fields['id'],
                'content'  => 'test'
            ]);
            $this->integer($id);

            $ticket->getFromDB($ticket->fields['id']);
            $this->integer($ticket->fields['status'])->isEqualTo(Ticket::SOLVED);
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
        $this->integer($templates_id)->isGreaterThan(0);
        $solution = new \ITILSolution();
        $solutions_id = $solution->add([
            '_templates_id'      => $templates_id,
            'itemtype'           => 'Ticket',
            'items_id'           => $ticket->fields['id'],
        ]);
        $this->integer($solutions_id)->isGreaterThan(0);

        $this->string($solution->fields['content'])->isEqualTo('&#60;p&#62;test template&#60;/p&#62;');

        //Reset ticket status
        $ticket->update([
            'id'    => $ticket->fields['id'],
            'status' => \CommonITILObject::INCOMING,
        ]);
        $solutions_id = $solution->add([
            '_templates_id'      => $templates_id,
            'itemtype'           => 'Ticket',
            'items_id'           => $ticket->fields['id'],
            'content'            => 'test template2',
        ]);
        $this->integer($solutions_id)->isGreaterThan(0);

        $this->string($solution->fields['content'])->isEqualTo('test template2');
    }

    public function testAddOnClosedTicket()
    {
        $this->login();
        // Create new ticket
        $ticket = $this->getNewITILObject('Ticket', true);
        // Close ticket
        $this->boolean($ticket->update([
            'id'    => $ticket->fields['id'],
            'status' => \CommonITILObject::CLOSED,
        ]))->isTrue();
        // Create solution
        $solution = new \ITILSolution();
        $solutions_id = $solution->add([
            'itemtype'           => 'Ticket',
            'items_id'           => $ticket->fields['id'],
            'content'            => 'test solution',
        ]);
        $this->integer($solutions_id)->isGreaterThan(0);
        // Verify solution is not waiting for approval. Should default to being approved.
        $this->integer($solution->fields['status'])->isEqualTo(\CommonITILValidation::ACCEPTED);
        // Verify the ticket status is still closed.
        $this->boolean($ticket->getFromDB($ticket->fields['id']))->isTrue();
        $this->integer($ticket->fields['status'])->isEqualTo(\CommonITILObject::CLOSED);
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
        $this->integer($ticket_id)->isGreaterThan(0);

        $this->boolean($ticket->isNewItem())->isFalse();
        $this->variable($ticket->getField('status'))->isIdenticalTo($ticket::ASSIGNED);

        // add solution
        $this->login('tech', 'tech');
        $solution = new \ITILSolution();
        $solution_id = (int)$solution->add([
            'itemtype'           => $ticket::getType(),
            'items_id'           => $ticket->getID(),
            'content'            => 'a solution',
        ]);
        $this->integer($solution_id)->isGreaterThan(0);

        $this->boolean($ticket->getFromDB($ticket_id))->isTrue();
        $this->variable((int)$solution->getField('status'))->isIdenticalTo(\CommonITILValidation::WAITING);
        $this->variable((int)$ticket->getField('status'))->isIdenticalTo($ticket::SOLVED);

        //refuse solution
        $this->login('post-only', 'postonly');
        $follow = new \ITILFollowup();
        $follow_id = (int)$follow->add([
            'itemtype'  => $ticket::getType(),
            'items_id'   => $ticket->getID(),
            'add_reopen'   => '1',
            'content'      => 'This is required'
        ]);
        $this->integer($follow_id)->isGreaterThan(0);

        $this->boolean($follow->getFromDB($follow_id))->isTrue();
        $this->boolean($solution->getFromDB($solution_id))->isTrue();
        $this->boolean($ticket->getFromDB($ticket_id))->isTrue();
        $this->integer((int)$solution->fields['status'])->isIdenticalTo(\CommonITILValidation::REFUSED);
        $this->integer((int)$ticket->fields['status'])->isIdenticalTo($ticket::ASSIGNED);

        // add solution
        $this->login('tech', 'tech');
        $solution = new \ITILSolution();
        $solution_id = (int)$solution->add([
            'itemtype'           => $ticket::getType(),
            'items_id'           => $ticket->getID(),
            'content'            => 'a solution',
        ]);
        $this->integer($solution_id)->isGreaterThan(0);

        $this->boolean($ticket->getFromDB($ticket_id))->isTrue();
        $this->variable((int)$solution->getField('status'))->isIdenticalTo(\CommonITILValidation::WAITING);
        $this->variable((int)$ticket->getField('status'))->isIdenticalTo($ticket::SOLVED);

        //approve solution
        $this->login('post-only', 'postonly');
        $this->boolean($ticket->getFromDB($ticket_id))->isTrue();
        $this->boolean((bool)$ticket->needReopen())->isTrue();
        $follow = new \ITILFollowup();
        $follow_id = (int)$follow->add([
            'itemtype'  => $ticket::getType(),
            'items_id'   => $ticket->getID(),
            'add_close'    => '1'
        ]);
        $this->integer($follow_id)->isGreaterThan(0);

        $this->boolean($follow->getFromDB($follow_id))->isTrue();
        $this->boolean($solution->getFromDB($solution_id))->isTrue();
        $this->boolean($ticket->getFromDB($ticket_id))->isTrue();
        $this->integer((int)$solution->fields['status'])->isIdenticalTo(\CommonITILValidation::ACCEPTED);
        $this->integer((int)$ticket->fields['status'])->isIdenticalTo($ticket::CLOSED);
    }
}
