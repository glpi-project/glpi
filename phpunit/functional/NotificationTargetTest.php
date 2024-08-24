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

use DbTestCase;
use Entity;
use Generator;
use Monolog\Logger;
use Session;

/* Test for inc/notificationtarget.class.php */

class NotificationTargetTest extends DbTestCase
{
    public function testGetSubjectPrefix()
    {
        $this->login();

        $root    = getItemByTypeName('Entity', 'Root entity', true);
        $parent  = getItemByTypeName('Entity', '_test_root_entity', true);
        $child_1 = getItemByTypeName('Entity', '_test_child_1', true);
        $child_2 = getItemByTypeName('Entity', '_test_child_2', true);

        $ntarget_parent  = new \NotificationTarget($parent);
        $ntarget_child_1 = new \NotificationTarget($child_1);
        $ntarget_child_2 = new \NotificationTarget($child_2);

        $this->assertEquals("[GLPI] ", $ntarget_parent->getSubjectPrefix());
        $this->assertEquals("[GLPI] ", $ntarget_child_1->getSubjectPrefix());
        $this->assertEquals("[GLPI] ", $ntarget_child_2->getSubjectPrefix());

        $entity  = new \Entity();
        $this->assertTrue($entity->update([
            'id'                       => $root,
            'notification_subject_tag' => "prefix_root",
        ]));

        $this->assertEquals("[prefix_root] ", $ntarget_parent->getSubjectPrefix());
        $this->assertEquals("[prefix_root] ", $ntarget_child_1->getSubjectPrefix());
        $this->assertEquals("[prefix_root] ", $ntarget_child_2->getSubjectPrefix());

        $this->assertTrue($entity->update([
            'id'                       => $parent,
            'notification_subject_tag' => "prefix_parent",
        ]));

        $this->assertEquals("[prefix_parent] ", $ntarget_parent->getSubjectPrefix());
        $this->assertEquals("[prefix_parent] ", $ntarget_child_1->getSubjectPrefix());
        $this->assertEquals("[prefix_parent] ", $ntarget_child_2->getSubjectPrefix());

        $this->assertTrue($entity->update([
            'id'                       => $child_1,
            'notification_subject_tag' => "prefix_child_1",
        ]));

        $this->assertEquals("[prefix_parent] ", $ntarget_parent->getSubjectPrefix());
        $this->assertEquals("[prefix_child_1] ", $ntarget_child_1->getSubjectPrefix());
        $this->assertEquals("[prefix_parent] ", $ntarget_child_2->getSubjectPrefix());

        $this->assertTrue($entity->update([
            'id'                       => $child_2,
            'notification_subject_tag' => "prefix_child_2",
        ]));

        $this->assertEquals("[prefix_parent] ", $ntarget_parent->getSubjectPrefix());
        $this->assertEquals("[prefix_child_1] ", $ntarget_child_1->getSubjectPrefix());
        $this->assertEquals("[prefix_child_2] ", $ntarget_child_2->getSubjectPrefix());
    }

    public static function getReplyToProvider(): iterable
    {
        $root_entity_id    = 0;
        $parent_entity_id  = getItemByTypeName('Entity', '_test_root_entity', true);
        $child_1_entity_id = getItemByTypeName('Entity', '_test_child_1', true);
        $child_2_entity_id = getItemByTypeName('Entity', '_test_child_2', true);

        yield [
            'global_config'    => [],
            'entities_configs' => [],
            'allow_response'   => true,
            'expected_results' => [
                $root_entity_id    => ['email' => null, 'name'  => null],
                $parent_entity_id  => ['email' => null, 'name'  => null],
                $child_1_entity_id => ['email' => null, 'name'  => null],
                $child_2_entity_id => ['email' => null, 'name'  => null],
            ]
        ];

        // Global config is used if no entity configuration is defined
        yield [
            'global_config'    => [
                'replyto_email'      => 'test@global.tld',
                'replyto_email_name' => 'test global',
                'noreply_email'      => 'noreply@global.tld',
                'noreply_email_name' => 'noreply global',
            ],
            'entities_configs' => [],
            'allow_response'   => true,
            'expected_results' => [
                $root_entity_id    => ['email' => 'test@global.tld', 'name'  => 'test global'],
                $parent_entity_id  => ['email' => 'test@global.tld', 'name'  => 'test global'],
                $child_1_entity_id => ['email' => 'test@global.tld', 'name'  => 'test global'],
                $child_2_entity_id => ['email' => 'test@global.tld', 'name'  => 'test global'],
            ]
        ];

        yield [
            'global_config'    => [
                'replyto_email'      => 'test@global.tld',
                'replyto_email_name' => 'test global',
                'noreply_email'      => 'noreply@global.tld',
                'noreply_email_name' => 'noreply global',
            ],
            'entities_configs' => [],
            'allow_response'   => false,
            'expected_results' => [
                $root_entity_id    => ['email' => 'noreply@global.tld', 'name'  => 'noreply global'],
                $parent_entity_id  => ['email' => 'noreply@global.tld', 'name'  => 'noreply global'],
                $child_1_entity_id => ['email' => 'noreply@global.tld', 'name'  => 'noreply global'],
                $child_2_entity_id => ['email' => 'noreply@global.tld', 'name'  => 'noreply global'],
            ]
        ];

        // Closest entity config is used, fallback on global
        yield [
            'global_config'    => [
                'replyto_email'      => 'test@global.tld',
                'replyto_email_name' => 'test global',
                'noreply_email'      => 'noreply@global.tld',
                'noreply_email_name' => 'noreply global',
            ],
            'entities_configs' => [
                $parent_entity_id  => [
                    'replyto_email'      => 'test@parent.tld',
                    'replyto_email_name' => 'test parent',
                    'noreply_email'      => 'noreply@parent.tld',
                    'noreply_email_name' => 'noreply parent',
                ],
                $child_2_entity_id => [
                    'replyto_email'      => 'test@child2.tld',
                    'replyto_email_name' => 'test child2',
                    'noreply_email'      => 'noreply@child2.tld',
                    'noreply_email_name' => 'noreply child2',
                ],
            ],
            'allow_response'   => true,
            'expected_results' => [
                $root_entity_id    => ['email' => 'test@global.tld', 'name'  => 'test global'],
                $parent_entity_id  => ['email' => 'test@parent.tld', 'name'  => 'test parent'],
                $child_1_entity_id => ['email' => 'test@parent.tld', 'name'  => 'test parent'],
                $child_2_entity_id => ['email' => 'test@child2.tld', 'name'  => 'test child2'],
            ]
        ];

        yield [
            'global_config'    => [
                'replyto_email'      => 'test@global.tld',
                'replyto_email_name' => 'test global',
                'noreply_email'      => 'noreply@global.tld',
                'noreply_email_name' => 'noreply global',
            ],
            'entities_configs' => [
                $parent_entity_id  => [
                    'replyto_email'      => 'test@parent.tld',
                    'replyto_email_name' => 'test parent',
                    'noreply_email'      => 'noreply@parent.tld',
                    'noreply_email_name' => 'noreply parent',
                ],
                $child_2_entity_id => [
                    'replyto_email'      => 'test@child2.tld',
                    'replyto_email_name' => 'test child2',
                    'noreply_email'      => 'noreply@child2.tld',
                    'noreply_email_name' => 'noreply child2',
                ],
            ],
            'allow_response'   => false,
            'expected_results' => [
                $root_entity_id    => ['email' => 'noreply@global.tld', 'name'  => 'noreply global'],
                $parent_entity_id  => ['email' => 'noreply@parent.tld', 'name'  => 'noreply parent'],
                $child_1_entity_id => ['email' => 'noreply@parent.tld', 'name'  => 'noreply parent'],
                $child_2_entity_id => ['email' => 'noreply@child2.tld', 'name'  => 'noreply child2'],
            ]
        ];
    }

    /**
     * @dataProvider getReplyToProvider
     */
    public function testGetReplyTo(
        array $global_config,
        array $entities_configs,
        bool $allow_response,
        array $expected_results
    ): void {
        global $CFG_GLPI;

        $this->login(); // must be logged-in to update entities

        foreach ($global_config as $config_key => $config_value) {
            $CFG_GLPI[$config_key] = $config_value;
        }

        foreach ($entities_configs as $entity_id => $entity_config) {
            $this->updateItem(Entity::class, $entity_id, $entity_config);
        }

        foreach ($expected_results as $entity_id => $expected_result) {
            $target = new \NotificationTarget($entity_id);
            $target->setAllowResponse($allow_response);
            $this->assertEquals($expected_result, $target->getReplyTo());
        }
    }

    /**
     * Provider for testGetSender
     *
     * @return Generator
     */
    protected function getSenderProvider(): Generator
    {
        global $CFG_GLPI;

        $this->login();

        // Case 1: default post install values no reply
        yield [
            'allow_response' => false,
            'email'          => "admsys@localhost",
            'name'           => "",
            'warning'        => 'No-Reply address is not defined in configuration.'
        ];

        // Case 2: no reply with global config
        $CFG_GLPI['noreply_email'] = "noreply@localhost";
        $CFG_GLPI['noreply_email_name'] = "No reply";

        yield [
            'allow_response' => false,
            'email'          => "noreply@localhost",
            'name'           => "No reply",
        ];

        // Case 3: default post install values with admin
        yield [
            'allow_response' => true,
            'email'          => "admsys@localhost",
            'name'           => "",
        ];

        // Case 4: default post install values with global admin config
        $CFG_GLPI['admin_email'] = "globaladmin@localhost";
        $CFG_GLPI['admin_email_name'] = "Global admin";

        yield [
            'allow_response' => true,
            'email'          => "globaladmin@localhost",
            'name'           => "Global admin",
        ];

        // Case 5: default post install values with specific entity config
        $entity = new Entity();
        $this->assertTrue($entity->update([
            'id'               => Session::getActiveEntity(),
            'admin_email'      => "specificadmin@localhost",
            'admin_email_name' => "Specific admin",
        ]));
        $this->assertTrue(
            $entity->getFromDB(Session::getActiveEntity())
        );
        $this->assertEquals("specificadmin@localhost", $entity->fields['admin_email']);
        $this->assertEquals("Specific admin", $entity->fields['admin_email_name']);

        yield [
            'allow_response' => true,
            'email'          => "specificadmin@localhost",
            'name'           => "Specific admin",
        ];

        // Case 6: default post install values with global from config
        $CFG_GLPI['from_email'] = "globalfrom@localhost";
        $CFG_GLPI['from_email_name'] = "Global from";

        yield [
            'allow_response' => true,
            'email'          => "globalfrom@localhost",
            'name'           => "Global from",
        ];
    }

    /**
     * Functional tests for the getSender method
     *
     * @return void
     */
    public function testGetSender(): void
    {
        $provider = $this->getSenderProvider();
        foreach ($provider as $row) {
            $allow_response = $row['allow_response'];
            $email = $row['email'] ?? null;
            $name = $row['name'] ?? null;
            $warning = $row['warning'] ?? null;

            $target = $this->getMockBuilder(\NotificationTarget::class)
                ->onlyMethods(['allowResponse'])
                ->getMock();
            $target->method('allowResponse')->willReturn($allow_response);

            $this->assertEquals(
                [
                    'email' => $email,
                    'name' => $name,
                ],
                $target->getSender()
            );

            if (!is_null($warning)) {
                $this->hasPhpLogRecordThatContains(
                    $warning,
                    Logger::WARNING
                );
            }
        }
    }

    public static function testGetInstanceClassProvider(): Generator
    {
        yield [
            'itemtype' => 'Test',
            'class'    => 'NotificationTargetTest',
        ];

        yield [
            'itemtype' => 'PluginPluginameTest',
            'class'    => 'PluginPluginameNotificationTargetTest',
        ];

        yield [
            'itemtype' => 'GlpiPlugin\Namespace\Test',
            'class'    => 'GlpiPlugin\Namespace\NotificationTargetTest',
        ];
    }

    /**
     * Tests for NotificationTarget::getInstanceClass
     *
     * @dataProvider testGetInstanceClassProvider
     */
    public function testGetInstanceClass(string $itemtype, string $class): void
    {
        $output = \NotificationTarget::getInstanceClass($itemtype);
        $this->assertEquals($class, $output);
    }

    public static function testFormatUrlProvider(): iterable
    {
        global $CFG_GLPI;

        // No URL returned for anonymous user
        yield [
            'usertype' => \NotificationTarget::ANONYMOUS_USER,
            'redirect' => 'Ticket_24',
            'expected' => '',
        ];

        // GLPI user, no `noAUTO=1` parameter
        yield [
            'usertype' => \NotificationTarget::GLPI_USER,
            'redirect' => 'Ticket_24',
            'expected' => $CFG_GLPI['url_base'] . '/index.php?redirect=Ticket_24',
        ];
        yield [
            'usertype' => \NotificationTarget::GLPI_USER,
            'redirect' => '/front/test.php?param=test&value=foo bar',
            'expected' => $CFG_GLPI['url_base'] . '/index.php?redirect=%2Ffront%2Ftest.php%3Fparam%3Dtest%26value%3Dfoo%20bar',
        ];

        // External user, no `noAUTO` parameter
        yield [
            'usertype' => \NotificationTarget::EXTERNAL_USER,
            'redirect' => 'Ticket_24',
            'expected' => $CFG_GLPI['url_base'] . '/index.php?redirect=Ticket_24',
        ];
        yield [
            'usertype' => \NotificationTarget::EXTERNAL_USER,
            'redirect' => '/front/test.php?param=test&value=foo bar',
            'expected' => $CFG_GLPI['url_base'] . '/index.php?redirect=%2Ffront%2Ftest.php%3Fparam%3Dtest%26value%3Dfoo%20bar',
        ];
    }

    /**
     * @dataProvider testFormatUrlProvider
     */
    public function testFormatUrl(int $usertype, string $redirect, string $expected): void
    {
        $instance = new \NotificationTarget();
        $this->assertEquals($expected, $instance->formatUrl($usertype, $redirect));
    }

    public static function messageItemProvider(): iterable
    {
        return [
            [
                "itemtype" => "Ticket",
                "items_id" => 1,
                "event" => "new",
                "expected" => "/^GLPI_%UUID%-Ticket-1\/new@%UNAME%$/",
            ],
            [
                "itemtype" => "Ticket",
                "items_id" => 1,
                "event" => "update",
                "expected" => "/^GLPI_%UUID%-Ticket-1\/update\.\d+\.\d+@%UNAME%$/",
            ],
            [
                "itemtype" => "Certificate",
                "items_id" => 1,
                "event" => 'alert',
                "expected" => "/^GLPI_%UUID%-Certificate-1\/alert\.\d+\.\d+@%UNAME%$/",
            ],
            [
                "itemtype" => "User",
                "items_id" => 7,
                "event" => 'new',
                "expected" => "/^GLPI_%UUID%-User-7\/new@%UNAME%$/",
            ],
            [
                "itemtype" => "User",
                "items_id" => 7,
                "event" => 'passwordexpires',
                "expected" => "/^GLPI_%UUID%-User-7\/passwordexpires\.\d+\.\d+@%UNAME%$/",
            ],
            [
                // no item
                "itemtype" => null,
                "items_id" => null,
                "event" => "some_event",
                "expected" => "/^GLPI_%UUID%\/some_event\.\d+\.\d+@%UNAME%$/",
            ],
            [
                // invalid itemtype
                "itemtype" => "Other",
                "items_id" => 1,
                "event" => "update",
                "expected" => "/^GLPI_%UUID%\/update\.\d+\.\d+@%UNAME%$/",
            ],
            [
                // no event
                "itemtype" => null,
                "items_id" => null,
                "event" => null,
                "expected" => "/^GLPI_%UUID%\/none\.\d+\.\d+@%UNAME%$/",
            ],
        ];
    }

    /**
     * @dataProvider messageItemProvider
     */
    public function testGetMessageIdForEvent(?string $itemtype, ?int $items_id, ?string $event, string $expected)
    {
        //set UUID
        $uuid = \Toolbox::getRandomString(40);
        \Config::setConfigurationValues('core', ['notification_uuid' => $uuid]);

        $uuid = \Config::getConfigurationValue('core', 'notification_uuid');
        $uname = php_uname('n');

        $expected = str_replace(
            [
                '%UUID%',
                '%UNAME%',
            ],
            [
                $uuid,
                $uname,
            ],
            $expected
        );

        $instance = new \NotificationTarget();
        $messageid = $instance->getMessageIdForEvent($itemtype, $items_id, $event);
        $this->assertMatchesRegularExpression($expected, $messageid);
    }
}
