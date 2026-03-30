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

use DbTestCase;
use Glpi\Toolbox\Sanitizer;
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

        // advanced test for ##task.categorycomment## and ##task.categoryid## tags
        // test of the getDataForObject for default language en_GB
        $taskcat = getItemByTypeName('TaskCategory', '_subcat_1');
        $encoded_sep = Sanitizer::sanitize('>');
        $expected = [
            [
                '##task.id##'              => 1,
                '##task.isprivate##'       => 'No',
                '##task.author##'          => '_test_user',
                '##task.categoryid##'      => $taskcat->getID(),
                '##task.category##'        => '_cat_1 ' . $encoded_sep . ' _subcat_1',
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

        $basic_options = [
            'additionnaloption' => [
                'usertype' => '',
            ],
        ];
        $ret = $notiftargetticket->getDataForObject($tkt, $basic_options);

        $this->assertSame($expected, $ret['tasks']);

        // test of the getDataForObject for default language fr_FR
        $CFG_GLPI['translate_dropdowns'] = 1;
        // Force generation of completename that was not done on dataset bootstrap
        // because `translate_dropdowns` is false by default.
        (new \DropdownTranslation())->generateCompletename([
            'itemtype' => \TaskCategory::class,
            'items_id' => getItemByTypeName(\TaskCategory::class, '_cat_1', true),
            'language' => 'fr_FR',
        ]);
        $_SESSION["glpilanguage"] = \Session::loadLanguage('fr_FR');
        $_SESSION['glpi_dropdowntranslations'] = \DropdownTranslation::getAvailableTranslations($_SESSION["glpilanguage"]);

        $ret = $notiftargetticket->getDataForObject($tkt, $basic_options);

        $expected = [
            [
                '##task.id##'              => 1,
                '##task.isprivate##'       => 'Non',
                '##task.author##'          => '_test_user',
                '##task.categoryid##'      => $taskcat->getID(),
                '##task.category##'        => 'FR - _cat_1 ' . $encoded_sep . ' FR - _subcat_1',
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

    public function testDateFormatInNotifications()
    {
        $this->login('tech', 'tech');

        $entity = getItemByTypeName('Entity', '_test_root_entity');

        // Create a ticket with a known creation date
        $creation_date = '2026-03-19 06:46:00';
        $ticket = $this->createItem(
            \Ticket::class,
            [
                'name'                => 'Test date format in notifications',
                'content'             => 'Testing date format',
                'entities_id'         => $entity->getID(),
                'date'                => $creation_date,
                '_users_id_assign'    => getItemByTypeName(\User::class, 'tech', true),
                '_users_id_requester' => getItemByTypeName(\User::class, 'tech', true),
            ]
        );
        $ticket->getFromDB($ticket->getID());

        // Set session date format to default (YYYY-MM-DD = format 0)
        $_SESSION["glpidate_format"] = 0;

        $basic_options = [
            'additionnaloption' => [
                'usertype' => \NotificationTarget::GLPI_USER,
            ],
        ];

        $notiftargetticket = new \NotificationTargetTicket(
            $entity->getID(),
            'new',
            $ticket
        );

        // With default format (0 = YYYY-MM-DD) date should be formatted as 2026-03-19
        $ret = $notiftargetticket->getDataForObject($ticket, $basic_options);
        $this->assertSame('2026-03-19 06:46', $ret['##ticket.creationdate##']);

        // Now switch session to DD-MM-YYYY format (format 1)
        $_SESSION["glpidate_format"] = 1;

        // Need to recreate the notification target to avoid any internal caching
        $notiftargetticket = new \NotificationTargetTicket(
            $entity->getID(),
            'new',
            $ticket
        );
        $ret = $notiftargetticket->getDataForObject($ticket, $basic_options);
        $this->assertSame('19-03-2026 06:46', $ret['##ticket.creationdate##']);

        // Now test that the date format is correctly applied when coming from
        // getTemplateByLanguage (which simulates the real notification flow)
        // Reset to default format in session, simulating a cron or different user context
        $_SESSION["glpidate_format"] = 0;

        // Create a user with DD-MM-YYYY date format preference
        $user = $this->createItem(
            \User::class,
            [
                'name'        => 'test_dateformat_user_' . mt_rand(),
                'date_format' => 1, // DD-MM-YYYY
            ]
        );

        // Build a notification template
        $template = $this->createItem(
            \NotificationTemplate::class,
            [
                'name'      => 'Test date format template',
                'itemtype'  => 'Ticket',
            ]
        );

        $this->createItem(
            \NotificationTemplateTranslation::class,
            [
                'notificationtemplates_id' => $template->getID(),
                'language'                 => '',
                'subject'                  => '##ticket.title##',
                'content_text'             => '##ticket.creationdate##',
                'content_html'             => '&lt;p&gt;##ticket.creationdate##&lt;/p&gt;',
            ]
        );

        $notiftargetticket = new \NotificationTargetTicket(
            $entity->getID(),
            'new',
            $ticket
        );

        // Simulate user_infos as built by the notification system for a user with DD-MM-YYYY format
        $user_infos = [
            'language'          => 'en_GB',
            'users_id'          => $user->getID(),
            'additionnaloption' => [
                'usertype'    => \NotificationTarget::GLPI_USER,
                'date_format' => 1, // DD-MM-YYYY
            ],
        ];

        $tid = $template->getTemplateByLanguage(
            $notiftargetticket,
            $user_infos,
            'new',
            []
        );
        $this->assertNotFalse($tid);

        // The rendered template should contain the date in DD-MM-YYYY format
        $generated = $template->templates_by_languages[$tid];
        $this->assertStringContainsString(
            '19-03-2026 06:46',
            $generated['content_text'],
            'Notification date should use the recipient\'s date format (DD-MM-YYYY), got: ' . $generated['content_text']
        );

        // Verify session date_format was restored to original value
        $this->assertSame(0, $_SESSION["glpidate_format"]);

        // Now test with format 2 (MM-DD-YYYY) for a different user
        $user_infos2 = [
            'language'          => 'en_GB',
            'users_id'          => $user->getID(),
            'additionnaloption' => [
                'usertype'    => \NotificationTarget::GLPI_USER,
                'date_format' => 2, // MM-DD-YYYY
            ],
        ];

        $notiftargetticket2 = new \NotificationTargetTicket(
            $entity->getID(),
            'new',
            $ticket
        );

        // Use a fresh template to avoid cache
        $template2 = new \NotificationTemplate();
        $template2->getFromDB($template->getID());

        $tid2 = $template2->getTemplateByLanguage(
            $notiftargetticket2,
            $user_infos2,
            'new',
            []
        );
        $this->assertNotFalse($tid2);

        $generated2 = $template2->templates_by_languages[$tid2];
        $this->assertStringContainsString(
            '03-19-2026 06:46',
            $generated2['content_text'],
            'Notification date should use format MM-DD-YYYY, got: ' . $generated2['content_text']
        );

        // Verify session date_format was restored again
        $this->assertSame(0, $_SESSION["glpidate_format"]);

        // Clean up
        $_SESSION["glpidate_format"] = 0;
    }
}
