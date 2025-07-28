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

use DbTestCase;
use NotificationTarget;

/* Test for inc/notificationtargetticket.class.php */

class NotificationTargetTicketTest extends DbTestCase
{
    public function testgetDataForObject()
    {
        global $CFG_GLPI;

        $tkt = getItemByTypeName('Ticket', '_ticket01');
        $notiftargetticket = new \NotificationTargetTicket(getItemByTypeName('Entity', '_test_root_entity', true), 'new', $tkt);
        $notiftargetticket->getTags();

        // basic test for ##ticket.externalid## tag
        $expected = [
            'tag'             => 'ticket.externalid',
            'value'           => true,
            'label'           => 'External ID',
            'events'          => 0,
            'foreach'         => false,
            'lang'            => true,
            'allowed_values'  => [],
        ];
        $this->assertSame(
            $expected,
            $notiftargetticket->tag_descriptions['lang']['##lang.ticket.externalid##']
        );

        // basic test for ##task.categorycomment## tag
        $expected = [
            'tag'             => 'task.categorycomment',
            'value'           => true,
            'label'           => 'Category comment',
            'events'          => 0,
            'foreach'         => false,
            'lang'            => true,
            'allowed_values'  => [],
        ];

        $this->assertSame($expected, $notiftargetticket->tag_descriptions['lang']['##lang.task.categorycomment##']);
        $this->assertSame($expected, $notiftargetticket->tag_descriptions['tag']['##task.categorycomment##']);

        // basic test for ##task.categorid## tag
        $expected = [
            'tag'             => 'task.categoryid',
            'value'           => true,
            'label'           => 'Category id',
            'events'          => 0,
            'foreach'         => false,
            'lang'            => true,
            'allowed_values'  => [],
        ];
        $this->assertSame($expected, $notiftargetticket->tag_descriptions['lang']['##lang.task.categoryid##']);
        $this->assertSame($expected, $notiftargetticket->tag_descriptions['tag']['##task.categoryid##']);

        // advanced test for ##ticket.externalid## tag
        $basic_options = [
            'additionnaloption' => [
                'usertype' => NotificationTarget::GLPI_USER,
            ],
        ];
        $ret = $notiftargetticket->getDataForObject($tkt, $basic_options);
        $this->assertSame('external_id', $ret['##ticket.externalid##']);

        // advanced test for ##task.categorycomment## and ##task.categoryid## tags
        // test of the getDataForObject for default language en_GB
        $taskcat = getItemByTypeName('TaskCategory', '_subcat_1');
        $expected = [
            [
                '##task.id##'              => 1,
                '##task.isprivate##'       => 'No',
                '##task.author##'          => '_test_user',
                '##task.categoryid##'      => $taskcat->getID(),
                '##task.category##'        => '_cat_1 > _subcat_1',
                '##task.categorycomment##' => 'Comment for sub-category _subcat_1',
                '##task.date##'            => '2016-10-19 11:50',
                '##task.description##'     => 'Task to be done',
                '##task.time##'            => '0 seconds',
                '##task.status##'          => 'To do',
                '##task.user##'            => '_test_user',
                '##task.group##'           => '',
                '##task.begin##'           => '',
                '##task.end##'             => '',
            ],
        ];

        $ret = $notiftargetticket->getDataForObject($tkt, $basic_options);

        $this->assertSame($expected, $ret['tasks']);

        //add validation for TU_USER
        $ticket_validation = new \TicketValidation();
        $ticket_validation_id = $ticket_validation->add([
            "tickets_id" => $tkt->getID(),
            "status" => \CommonITILValidation::WAITING,
            "itemtype_target" => \User::class,
            "items_id_target" => getItemByTypeName(\User::class, TU_USER, true),
        ]);
        $this->assertGreaterThan(0, $ticket_validation_id);

        $basic_options['validation_id'] = $ticket_validation_id;
        $ret = $notiftargetticket->getDataForObject($tkt, $basic_options);

        $this->assertEquals(
            sprintf(
                '%s/index.php?redirect=ticket_%d_Ticket%%24main%%23TicketValidation_%d',
                $CFG_GLPI['url_base'],
                $tkt->getID(),
                $ticket_validation_id
            ),
            $ret['##ticket.urlvalidation##']
        );

        //add another validation for jsmith123
        $ticket_validation = new \TicketValidation();
        $ticket_validation_id = $ticket_validation->add([
            "tickets_id" => $tkt->getID(),
            "status" => \CommonITILValidation::WAITING,
            "itemtype_target" => \User::class,
            "items_id_target" => getItemByTypeName(\User::class, 'jsmith123', true),
        ]);
        $this->assertGreaterThan(0, $ticket_validation_id);

        $basic_options['validation_id'] = $ticket_validation_id;
        $ret = $notiftargetticket->getDataForObject($tkt, $basic_options);

        $this->assertEquals(
            sprintf(
                '%s/index.php?redirect=ticket_%d_Ticket%%24main%%23TicketValidation_%d',
                $CFG_GLPI['url_base'],
                $tkt->getID(),
                $ticket_validation_id
            ),
            $ret['##ticket.urlvalidation##']
        );

        // test of the getDataForObject for default language fr_FR
        $_SESSION["glpilanguage"] = \Session::loadLanguage('fr_FR');
        $_SESSION['glpi_dropdowntranslations'] = \DropdownTranslation::getAvailableTranslations($_SESSION["glpilanguage"]);

        $ret = $notiftargetticket->getDataForObject($tkt, $basic_options);

        $expected = [
            [
                '##task.id##'              => 1,
                '##task.isprivate##'       => 'Non',
                '##task.author##'          => '_test_user',
                '##task.categoryid##'      => $taskcat->getID(),
                '##task.category##'        => 'FR - _cat_1 > FR - _subcat_1',
                '##task.categorycomment##' => 'FR - Commentaire pour sous-catégorie _subcat_1',
                '##task.date##'            => '2016-10-19 11:50',
                '##task.description##'     => 'Task to be done',
                '##task.time##'            => '0 seconde',
                '##task.status##'          => 'A faire',
                '##task.user##'            => '_test_user',
                '##task.group##'           => '',
                '##task.begin##'           => '',
                '##task.end##'             => '',
            ],
        ];

        $this->assertSame($expected, $ret['tasks']);

        // switch back to default language
        $_SESSION["glpilanguage"] = \Session::loadLanguage('en_GB');
    }


    public function testTimelineTag()
    {
        $entity = getItemByTypeName("Entity", "_test_root_entity");
        global $DB;
        // Build test ticket
        $this->login('tech', 'tech');
        $ticket = new \Ticket();
        $tickets_id = $ticket->add($input = [
            'name'             => 'test',
            'content'          => 'test',
            '_users_id_assign' => getItemByTypeName('User', 'tech', true),
            '_users_id_requester' => getItemByTypeName('User', 'post-only', true),
            'entities_id'      => $entity->getID(),
            'users_id_recipient' => getItemByTypeName('User', 'tech', true),
            'users_id_lastupdater' => getItemByTypeName('User', 'tech', true),
            'requesttypes_id'  => 4,
        ]);
        $this->assertGreaterThan(0, $tickets_id);

        // Unset temporary fields that will not be found in tickets table
        unset($input['_users_id_assign']);
        unset($input['_users_id_requester']);

        // Check expected fields and reload object from DB
        $this->checkInput($ticket, $tickets_id, $input);

        // Add followup from tech
        $fup_tech = new \ITILFollowup();
        $fup1_id = $fup_tech->add([
            'content' => 'test followup',
            'users_id' => getItemByTypeName('User', 'tech', true),
            'users_id_editor' => getItemByTypeName('User', 'tech', true),
            'itemtype' => 'Ticket',
            'is_private' => 0,
            'items_id' => $tickets_id,
            'date_creation' => date('Y-m-d H:i:s', strtotime($_SESSION['glpi_currenttime']) + 1),
        ]);
        $this->assertGreaterThan(0, $fup1_id);

        // Add followup from post_only
        $fup_post_only = new \ITILFollowup();
        $fup2_id = $fup_post_only->add([
            'content' => 'test post_only',
            'users_id' => getItemByTypeName('User', 'post-only', true),
            'users_id_editor' => getItemByTypeName('User', 'post-only', true),
            'itemtype' => 'Ticket',
            'is_private' => 0,
            'items_id' => $tickets_id,
            'date_creation' => date('Y-m-d H:i:s', strtotime($_SESSION['glpi_currenttime']) + 2),
        ]);
        $this->assertGreaterThan(0, $fup2_id);

        // Add private followup to tech
        $fup_private_tech = new \ITILFollowup();
        $fup3_id = $fup_private_tech->add([
            'content' => 'test private followup',
            'users_id' => getItemByTypeName('User', 'tech', true),
            'users_id_editor' => getItemByTypeName('User', 'tech', true),
            'itemtype' => 'Ticket',
            'is_private' => 1,
            'items_id' => $tickets_id,
            'date_creation' => date('Y-m-d H:i:s', strtotime($_SESSION['glpi_currenttime']) + 3),
        ]);
        $this->assertGreaterThan(0, $fup3_id);

        //add private task from tech
        $task_private = new \TicketTask();
        $task1_id = $task_private->add([
            'state'             => \Planning::TODO,
            'tickets_id'        => $tickets_id,
            'tasktemplates_id'  => '0',
            'is_private'        => 1,
            'taskcategories_id' => '0',
            'actiontime'        => "172800",                                  //1hours
            'content'           => "Private Task",
            'users_id_tech'     => getItemByTypeName('User', 'tech', true),
            'date_creation'     => date('Y-m-d H:i:s', strtotime($_SESSION['glpi_currenttime']) + 4),
        ]);
        $this->assertGreaterThan(0, $task1_id);

        //add task from tech
        $task_tech = new \TicketTask();
        $task2_id = $task_tech->add([
            'state'             => \Planning::TODO,
            'tickets_id'        => $tickets_id,
            'tasktemplates_id'  => '0',
            'taskcategories_id' => '0',
            'is_private'        => 0,
            'actiontime'        => "172800", //1hour
            'content'           => "Task",
            'users_id_tech'     => getItemByTypeName('User', 'tech', true),
            'date_creation'     => date('Y-m-d H:i:s', strtotime($_SESSION['glpi_currenttime']) + 5),
        ]);
        $this->assertGreaterThan(0, $task2_id);

        // Add solution to test ticket
        $solution = new \ITILSolution();
        $solutions_id = $solution->add([
            'content' => 'test',
            'users_id' => getItemByTypeName('User', 'tech', true),
            'users_id_editor' => getItemByTypeName('User', 'tech', true),
            'itemtype' => 'Ticket',
            'items_id' => $tickets_id,
            'date_creation' => date('Y-m-d H:i:s', strtotime($_SESSION['glpi_currenttime']) + 6),
        ]);
        $this->assertGreaterThan(0, $solutions_id);

        // Must be logged out to ensure session rights are not checked.
        $this->resetSession();

        $basic_options = [
            'additionnaloption' => [
                'usertype' => NotificationTarget::GLPI_USER,
                'is_self_service' => false,
                'show_private'    => true,
            ],
        ];

        $notiftargetticket = new \NotificationTargetTicket(getItemByTypeName('Entity', '_test_root_entity', true), 'new', $ticket);
        $ret = $notiftargetticket->getDataForObject($ticket, $basic_options);

        //get all task / solution / followup (because is tech)
        $expected = [
            [
                "##timelineitems.type##"        => "ITILSolution",
                "##timelineitems.typename##"    => "Solutions",
                "##timelineitems.date##"        => \Html::convDateTime($solution->fields['date_creation']),
                "##timelineitems.description##" => $solution->fields['content'],
                "##timelineitems.position##"    => "right",
                "##timelineitems.author##"      => "tech", //empty
            ],[
                "##timelineitems.type##"        => "TicketTask",
                "##timelineitems.typename##"    => "Ticket tasks",
                "##timelineitems.date##"        => \Html::convDateTime($task_tech->fields['date']),
                "##timelineitems.description##" => $task_tech->fields['content'],
                "##timelineitems.position##"    => "right",
                "##timelineitems.author##"      => "tech",
            ],[
                "##timelineitems.type##"        => "TicketTask",
                "##timelineitems.typename##"    => "Ticket tasks",
                "##timelineitems.date##"        => \Html::convDateTime($task_private->fields['date']),
                "##timelineitems.description##" => $task_private->fields['content'],
                "##timelineitems.position##"    => "right",
                "##timelineitems.author##"      => "tech",
            ],[
                "##timelineitems.type##" => "ITILFollowup",
                "##timelineitems.typename##" => "Followups",
                "##timelineitems.date##"        => \Html::convDateTime($fup_private_tech->fields['date']),
                "##timelineitems.description##" => $fup_private_tech->fields['content'],
                "##timelineitems.position##" => "right",
                "##timelineitems.author##" => "tech",
            ],[
                "##timelineitems.type##"        => "ITILFollowup",
                "##timelineitems.typename##"    => "Followups",
                "##timelineitems.date##"        => \Html::convDateTime($fup_post_only->fields['date']),
                "##timelineitems.description##" => $fup_post_only->fields['content'],
                "##timelineitems.position##"    => "left",
                "##timelineitems.author##"      => "post-only",
            ],[
                "##timelineitems.type##" => "ITILFollowup",
                "##timelineitems.typename##" => "Followups",
                "##timelineitems.date##"        => \Html::convDateTime($fup_tech->fields['date']),
                "##timelineitems.description##" => $fup_tech->fields['content'],
                "##timelineitems.position##" => "right",
                "##timelineitems.author##" => "tech",
            ],
        ];

        $this->assertSame($expected, $ret['timelineitems']);

        $this->assertTrue((bool) $this->login('post-only', 'postonly', true));

        $basic_options = [
            'additionnaloption' => [
                'usertype' => NotificationTarget::GLPI_USER,
                'is_self_service' => true,
                'show_private'    => false,
            ],
        ];

        $ret = $notiftargetticket->getDataForObject($ticket, $basic_options);

        //get only public task / followup / Solution (because is post_only)
        $expected = [
            [
                "##timelineitems.type##"        => "ITILSolution",
                "##timelineitems.typename##"    => "Solutions",
                "##timelineitems.date##"        =>  \Html::convDateTime($solution->fields['date_creation']),
                "##timelineitems.description##" => $solution->fields['content'],
                "##timelineitems.position##"    => "right",
                "##timelineitems.author##"      => "tech", //empty
            ],[
                "##timelineitems.type##"        => "TicketTask",
                "##timelineitems.typename##"    => "Ticket tasks",
                "##timelineitems.date##"        =>  \Html::convDateTime($task_tech->fields['date']),
                "##timelineitems.description##" => $task_tech->fields['content'],
                "##timelineitems.position##"    => "right",
                "##timelineitems.author##"      => "tech",
            ],[
                "##timelineitems.type##"        => "ITILFollowup",
                "##timelineitems.typename##"    => "Followups",
                "##timelineitems.date##"        =>  \Html::convDateTime($fup_post_only->fields['date']),
                "##timelineitems.description##" => $fup_post_only->fields['content'],
                "##timelineitems.position##"    => "left",
                "##timelineitems.author##"      => "post-only",
            ],[
                "##timelineitems.type##" => "ITILFollowup",
                "##timelineitems.typename##" => "Followups",
                "##timelineitems.date##"        =>  \Html::convDateTime($fup_tech->fields['date']),
                "##timelineitems.description##" => $fup_tech->fields['content'],
                "##timelineitems.position##" => "right",
                "##timelineitems.author##" => "tech",
            ],
        ];

        //add a test for tech, but force the `show_private` option to false to ensure that presence of this option will
        //hide private items
        $basic_options = [
            'additionnaloption' => [
                'usertype' => NotificationTarget::GLPI_USER,
                'is_self_service' => false,
                'show_private'    => false,
            ],
        ];

        $ret = $notiftargetticket->getDataForObject($ticket, $basic_options);

        //get only public task / followup / Solution (because is post_only)
        $expected = [
            [
                "##timelineitems.type##"        => "ITILSolution",
                "##timelineitems.typename##"    => "Solutions",
                "##timelineitems.date##"        =>  \Html::convDateTime($solution->fields['date_creation']),
                "##timelineitems.description##" => $solution->fields['content'],
                "##timelineitems.position##"    => "right",
                "##timelineitems.author##"      => "tech", //empty
            ],[
                "##timelineitems.type##"        => "TicketTask",
                "##timelineitems.typename##"    => "Ticket tasks",
                "##timelineitems.date##"        =>  \Html::convDateTime($task_tech->fields['date']),
                "##timelineitems.description##" => $task_tech->fields['content'],
                "##timelineitems.position##"    => "right",
                "##timelineitems.author##"      => "tech",
            ],[
                "##timelineitems.type##"        => "ITILFollowup",
                "##timelineitems.typename##"    => "Followups",
                "##timelineitems.date##"        =>  \Html::convDateTime($fup_post_only->fields['date']),
                "##timelineitems.description##" => $fup_post_only->fields['content'],
                "##timelineitems.position##"    => "left",
                "##timelineitems.author##"      => "post-only",
            ],[
                "##timelineitems.type##" => "ITILFollowup",
                "##timelineitems.typename##" => "Followups",
                "##timelineitems.date##"        =>  \Html::convDateTime($fup_tech->fields['date']),
                "##timelineitems.description##" => $fup_tech->fields['content'],
                "##timelineitems.position##" => "right",
                "##timelineitems.author##" => "tech",
            ],
        ];

        $this->assertSame($expected, $ret['timelineitems']);
    }


    /**
     * Test url tags are correctly replaced in function of entity url_base setup
     */
    public function testUrlTag()
    {
        global $CFG_GLPI;

        $this->login();

        $CFG_GLPI['url_base'] = 'root.tld';

        $root    = getItemByTypeName("Entity", "Root entity", true);
        $parent  = getItemByTypeName('Entity', '_test_root_entity', true);

        // test entity url (with default url)
        $ticket = new \Ticket();
        $root_tickets_id = $ticket->add([
            'name'        => 'test',
            'content'     => 'test',
            'entities_id' => $root,
        ]);

        $expected_raw_url = "%base_url%/index.php?redirect=ticket_%ticket_id%";

        $basic_options = [
            'additionnaloption' => [
                'usertype' => NotificationTarget::GLPI_USER,
                'is_self_service' => false,
                'show_private'    => true,
            ],
        ];
        $notiftargetticket = new \NotificationTargetTicket($root, 'new', $ticket);
        $ret = $notiftargetticket->getDataForObject($ticket, $basic_options);


        $root_expected_url = str_replace([
            '%base_url%',
            '%ticket_id%',
        ], [
            "root.tld",
            $root_tickets_id,
        ], $expected_raw_url);
        $this->assertEquals($root_expected_url, $ret['##ticket.url##']);

        // test sub entity with changed url
        $entity  = new \Entity();
        $this->assertTrue($entity->update([
            'id'       => $parent,
            'url_base' => "parent.tld",
            'mailing_signature' => 'test',
        ]));
        $entity->getFromDB($parent);

        $parent_tickets_id = $ticket->add([
            'name'        => 'test',
            'content'     => 'test',
            'entities_id' => $parent,
        ]);

        $notiftargetticket = new \NotificationTargetTicket($parent, 'new', $ticket);
        $ret = $notiftargetticket->getDataForObject($ticket, $basic_options);

        $parent_expected_url = str_replace([
            '%base_url%',
            '%ticket_id%',
        ], [
            "parent.tld",
            $parent_tickets_id,
        ], $expected_raw_url);
        $this->assertEquals($parent_expected_url, $ret['##ticket.url##']);
    }
}
