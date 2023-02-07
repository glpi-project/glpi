<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

class NotificationEvent extends DbTestCase
{
    protected function raiseEventProvider(): iterable
    {
        global $DB, $CFG_GLPI;

        $this->login();

        $entity_id_root       = getItemByTypeName(\Entity::class, '_test_root_entity', true);
        $entity_id_child1     = getItemByTypeName(\Entity::class, '_test_child_1', true);
        $entity_id_subchild11 = $this->createItem(\Entity::class, ['name' => '_test_subchild_1.1', 'entities_id' => $entity_id_child1])->getID();
        $entity_id_child2     = getItemByTypeName(\Entity::class, '_test_child_2', true);
        $tech_user_id         = getItemByTypeName(\User::class, 'tech', true);

        // Ensure notifications are sent
        $CFG_GLPI['use_notifications']     = true;
        $CFG_GLPI['notifications_mailing'] = true;

        // Disable base notification
        $DB->update(\Notification::getTable(), ['is_active' => 0], ['is_active' => 1]);

        // Create notifications templates
        $new_ticket_template = $this->createItem(
            \NotificationTemplate::class,
            [
                'name'     => $this->getUniqueString(),
                'itemtype' => \Ticket::class,
            ]
        );
        $this->createItem(
            \NotificationTemplateTranslation::class,
            [
                'notificationtemplates_id' => $new_ticket_template->getID(),
                'language'                 => '',
                'subject'                  => 'Ticket created',
                'content_text'             => '...',
                'content_html'             => '...',
            ]
        );
        $assigned_template = $this->createItem(
            \NotificationTemplate::class,
            [
                'name'     => $this->getUniqueString(),
                'itemtype' => \Ticket::class,
            ]
        );
        $this->createItem(
            \NotificationTemplateTranslation::class,
            [
                'notificationtemplates_id' => $assigned_template->getID(),
                'language'                 => '',
                'subject'                  => 'You are assigned!',
                'content_text'             => '...',
                'content_html'             => '...',
            ]
        );
        $new_project_template = $this->createItem(
            \NotificationTemplate::class,
            [
                'name'     => $this->getUniqueString(),
                'itemtype' => \Project::class,
            ]
        );
        $this->createItem(
            \NotificationTemplateTranslation::class,
            [
                'notificationtemplates_id' => $new_project_template->getID(),
                'language'                 => '',
                'subject'                  => 'Project created',
                'content_text'             => '...',
                'content_html'             => '...',
            ]
        );

        // Create notifications for assigned user at different entity level
        $notifications_specs = [
            // Notifications on '_test_root_entity'
            [
                // Keep this one to check that notification is sent only on matching itemtype
                'name'         => 'Project created in _test_root_entity + child entities',
                'entities_id'  => $entity_id_root,
                'is_recursive' => 1,
                'is_active'    => 1,
                'itemtype'     => \Project::class,
                'event'        => 'new',
                '_notificationtemplates_id' => $new_project_template->getID(),
            ],
            [
                'name'         => 'Ticket created in _test_root_entity + child entities',
                'entities_id'  => $entity_id_root,
                'is_recursive' => 1,
                'is_active'    => 1,
                'itemtype'     => \Ticket::class,
                'event'        => 'new',
                '_notificationtemplates_id' => $new_ticket_template->getID(),
            ],
            [
                'name'         => 'Ticket created in _test_root_entity + child entities (duplicate that should be ignored)',
                'entities_id'  => $entity_id_root,
                'is_recursive' => 1,
                'is_active'    => 1,
                'itemtype'     => \Ticket::class,
                'event'        => 'new',
                '_notificationtemplates_id' => $new_ticket_template->getID(),
            ],
            [
                'name'         => 'You are assigned in _test_root_entity',
                'entities_id'  => $entity_id_root,
                'is_recursive' => 0,
                'is_active'    => 1,
                'itemtype'     => \Ticket::class,
                'event'        => 'new',
                '_notificationtemplates_id' => $assigned_template->getID(),
            ],

            // Notifications on '_test_child_1'
            [
                // Active, so should override parent entities config
                'name'         => 'You are assigned in _test_child_1',
                'entities_id'  => $entity_id_child1,
                'is_recursive' => 0,
                'is_active'    => 1,
                'itemtype'     => \Ticket::class,
                'event'        => 'new',
                '_notificationtemplates_id' => $assigned_template->getID(),
            ],

            // Notifications on '_test_child_2'
            // Not active, so should not override parent entities config
            [
                'name'         => 'You are assigned in _test_child_2',
                'entities_id'  => $entity_id_child2,
                'is_recursive' => 0,
                'is_active'    => 0,
                'itemtype'     => \Ticket::class,
                'event'        => 'new',
                '_notificationtemplates_id' => $assigned_template->getID(),
            ],
        ];
        foreach ($notifications_specs as $notification_specs) {
            $notification = $this->createItem(
                \Notification::class,
                $notification_specs
            );
            $this->createItem(
                \Notification_NotificationTemplate::class,
                [
                    'notifications_id'         => $notification->getID(),
                    'mode'                     => \Notification_NotificationTemplate::MODE_MAIL,
                    'notificationtemplates_id' => $notification_specs['_notificationtemplates_id'],
                ]
            );
            $this->createItem(
                \NotificationTarget::class,
                [
                    'notifications_id' => $notification->getID(),
                    'items_id'         => \Notification::ASSIGN_TECH,
                    'type'             => 1,
                ]
            );
        }

        $common_input = [
            'name'          => $this->getUniqueString(),
            'content'       => $this->getUniqueString(),
            '_disablenotif' => true, // disable notifications, will be raised manually
        ];

        // When ticket is created on '_test_root_entity' entity, assignee will receive 2 notifications
        // -> 'Ticket created in _test_root_entity + child entities'
        // -> 'You are assigned in _test_root_entity'
        yield [
            'event'    => 'new',
            'item'     => $this->createItem(
                \Ticket::class,
                $common_input + [
                    'entities_id'            => $entity_id_root,
                    '_users_id_assign'       => ["$tech_user_id"],
                    '_users_id_assign_notif' => [
                        'use_notification'   => ['1'],
                        'alternative_email'  => ['tech@domain.tld'],
                    ],
                ]
            ),
            'expected' => [
                [
                    'notificationtemplates_id' => $new_ticket_template->getID(),
                    'recipientname'            => 'tech',
                ],
                [
                    'notificationtemplates_id' => $assigned_template->getID(),
                    'recipientname'            => 'tech',
                ],
            ],
        ];

        // When ticket is created on '_test_child_1' entity, assignee will only receive notifications defined
        // in '_test_child_1' entity, but not those defined in '_test_root_entity' entity + child entities
        // as we consider that defining an active notification in one entity overrides the whole configuration of parent entities
        // -> 'You are assigned in _test_child_1'
        yield [
            'event'    => 'new',
            'item'     => $this->createItem(
                \Ticket::class,
                $common_input + [
                    'entities_id'            => $entity_id_child1,
                    '_users_id_assign'       => ["$tech_user_id"],
                    '_users_id_assign_notif' => [
                        'use_notification'   => ['1'],
                        'alternative_email'  => ['tech@domain.tld'],
                    ],
                ]
            ),
            'expected' => [
                [
                    'notificationtemplates_id' => $assigned_template->getID(),
                    'recipientname'            => 'tech',
                ],
            ],
        ];

        // When ticket is created on '_test_subchild_1.1' entity, assignee will receive notifications defined in
        // '_test_root_entity' entity + child entities because notifications defined on closest level are not visible
        // from '_test_subchild_1.1' entity
        // -> 'Ticket created in _test_root_entity + child entities'
        yield [
            'event'    => 'new',
            'item'     => $this->createItem(
                \Ticket::class,
                $common_input + [
                    'entities_id'            => $entity_id_subchild11,
                    '_users_id_assign'       => ["$tech_user_id"],
                    '_users_id_assign_notif' => [
                        'use_notification'   => ['1'],
                        'alternative_email'  => ['tech@domain.tld'],
                    ],
                ]
            ),
            'expected' => [
                [
                    'notificationtemplates_id' => $new_ticket_template->getID(),
                    'recipientname'            => 'tech',
                ],
            ],
        ];

        // When ticket is created on '_test_child_2' entity, assignee will receive notifications defined
        // in '_test_root_entity' entity + child entities, because notification defined in '_test_child_2' is inactive
        // and therefore is ignored
        // -> 'You are assigned in _test_root_entity'
        yield [
            'event'    => 'new',
            'item'     => $this->createItem(
                \Ticket::class,
                $common_input + [
                    'entities_id'            => $entity_id_child2,
                    '_users_id_assign'       => ["$tech_user_id"],
                    '_users_id_assign_notif' => [
                        'use_notification'   => ['1'],
                        'alternative_email'  => ['tech@domain.tld'],
                    ],
                ]
            ),
            'expected' => [
                [
                    'notificationtemplates_id' => $new_ticket_template->getID(),
                    'recipientname'            => 'tech',
                ],
            ],
        ];
    }

    /**
     * @dataprovider raiseEventProvider
     */
    public function testRaiseEvent(string $event, \CommonDBTM $item, array $expected): void
    {
        global $DB;

        // Clean queued notifications
        $DB->update(\QueuedNotification::getTable(), ['is_deleted' => 1], ['is_deleted' => 0]);

        // Raise event
        $this->boolean(\NotificationEvent::raiseEvent('new', $item))->isTrue();

        // Check created notifications
        $notifications = getAllDataFromTable(\QueuedNotification::getTable(), ['is_deleted' => 0]);
        $this->array($notifications)->hasSize(count($expected));

        foreach ($expected as $criteria) {
            $found = getAllDataFromTable(\QueuedNotification::getTable(), $criteria + ['is_deleted' => 0]);
            $this->array($found)->hasSize(1);
        }
    }
}
