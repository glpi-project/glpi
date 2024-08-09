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

namespace tests\units\Glpi\RichText;

use CommonITILActor;
use CommonITILObject;
use DbTestCase;
use Glpi\Toolbox\Sanitizer;
use Notification;
use Notification_NotificationTemplate;
use NotificationTarget;
use NotificationTemplate;
use NotificationTemplateTranslation;
use Session;
use Ticket;
use Ticket_User;
use TicketValidation;
use User;

class UserMentionTest extends DbTestCase
{
    public static function getTypesMapping(): array
    {
        return [
            'Change' => [
                'ITILFollowup',
                'ChangeTask',
                'ITILSolution',
            ],
            'Problem' => [
                'ITILFollowup',
                'ProblemTask',
                'ITILSolution',
            ],
            'Ticket' => [
                'ITILFollowup',
                'TicketTask',
                'ITILSolution',
            ],
        ];
    }

    public static function itilProvider()
    {
        $tech_id = getItemByTypeName('User', 'tech', true);
        $normal_id = getItemByTypeName('User', 'normal', true);

        foreach (self::getTypesMapping() as $main_type => $sub_types) {
            foreach (array_merge([$main_type], $sub_types) as $itemtype) {
                yield [
                    'itemtype'      => $itemtype,
                    'main_itemtype' => $main_type,

                    // No user mention on creation => no observer
                    'add_content'            => <<<HTML
                  <p>ping @tec</p>
HTML
               ,
                    'add_expected_observers' => [],
                    'add_expected_notified'  => [],

                     // Added mentions on update => new observers
                    'update_content'            => <<<HTML
                  <p>ping <span data-user-mention="true" data-user-id="{$tech_id}">@tech</span></p>
HTML
               ,
                    'update_expected_observers' => [$tech_id],
                    'update_expected_notified'  => [$tech_id],
                ];

                yield [
                    'itemtype'      => $itemtype,
                    'main_itemtype' => $main_type,

                    // 1 user mention => 1 observer
                    'add_content'            => <<<HTML
                  <p>ping <span data-user-mention="true" data-user-id="{$tech_id}">@tech</span></p>
HTML
               ,
                    'add_expected_observers' => [$tech_id],
                    'add_expected_notified'  => [$tech_id],

                    // Same mentions on update => mentionned users are not notified
                    'update_content'            => <<<HTML
                  <p>ping <span data-user-mention="true" data-user-id="{$tech_id}">@tech</span></p>
HTML
               ,
                    'update_expected_observers' => [],
                    'update_expected_notified'  => [],
                ];
                yield [
                    'itemtype'      => $itemtype,
                    'main_itemtype' => $main_type,

                    // multiple user mentions => multiple observer
                    // validate that data-* attributes order has no impact
                    'add_content'            => <<<HTML
                  <p>Hi <span data-user-mention="true" data-user-id="{$tech_id}">@tech</span>,</p>
                  <p>I discussed with <span data-user-id="{$normal_id}" data-user-mention="true">@normal</span> about ...</p>
HTML
               ,
                    'add_expected_observers' => [$tech_id, $normal_id],
                    'add_expected_notified'  => [$tech_id, $normal_id],

                    // Deleted mentions on update => no change on observers
                    'update_content'            => <<<HTML
                  <p>Hi <span data-user-mention="true" data-user-id="{$tech_id}">@tech</span>,</p>
                  <p> ... </p>
HTML
               ,
                    'update_expected_observers' => [],
                    'update_expected_notified'  => [],
                ];

                $item = getItemForItemtype($itemtype);
                if ($item->maybePrivate()) {
                    yield [
                        'itemtype'      => $itemtype,
                        'main_itemtype' => $main_type,

                        // Created content => no notification to private users
                        'add_content'            => <<<HTML
                     <p>Hi <span data-user-mention="true" data-user-id="{$tech_id}">@tech</span>,</p>
                     <br>
                     <p>I discussed with <span data-user-id="{$normal_id}" data-user-mention="true">@normal</span> about ...</p>
HTML
                  ,
                        'add_expected_observers' => [$tech_id, $normal_id],
                        'add_expected_notified'  => [$tech_id],

                        // Updated content => no notification to private users
                        'update_content'            => <<<HTML
                     <p>Hi <span data-user-mention="true" data-user-id="{$tech_id}">@tech</span>,</p>
                     <p>I discussed with <span data-user-id="{$normal_id}" data-user-mention="true">@normal</span> about ...</p>
                     <p> ... </p>
HTML
                  ,
                        'update_expected_observers' => [],
                        'update_expected_notified'  => [],
                        'is_private'                => true,
                    ];
                }
                yield [
                    'itemtype'      => $itemtype,
                    'main_itemtype' => $main_type,

                    // bad HTML no users are notified
                    'add_content'            => <<<HTML
                  </span></p></div></body></html>
HTML
               ,
                    'add_expected_observers' => [],
                    'add_expected_notified'  => [],

                    // update bad HTML => no users are notified
                    'update_content'            => <<<HTML
                  </span></p></div></body></html>
HTML
               ,
                    'update_expected_observers' => [],
                    'update_expected_notified'  => [],
                ];
            }
        }
    }

    /**
     * @dataProvider itilProvider
     */
    public function testHandleUserMentions(
        string $itemtype,
        string $main_itemtype,
        string $add_content,
        array $add_expected_observers,
        array $add_expected_notified,
        string $update_content,
        array $update_expected_observers,
        array $update_expected_notified,
        ?bool $is_private = null
    ) {
        global $CFG_GLPI;
        $CFG_GLPI['use_notifications'] = 1;
        $CFG_GLPI['notifications_mailing'] = 1;

        $tech_id = getItemByTypeName('User', 'tech', true);
        $normal_id = getItemByTypeName('User', 'normal', true);

        // Delete existing notifications targets (to prevent sending of notifications not related to user_mention)
        $notification_targets = new NotificationTarget();
        $notification_targets->deleteByCriteria(['NOT' => ['items_id' => Notification::MENTIONNED_USER]]);

        // Add email to users for notifications
        $this->login(); // must be authenticated to update emails
        $user = new User();
        $update = $user->update(['id' => $tech_id, '_useremails' => ['tech@glpi-project.org']]);
        $this->assertTrue($update);
        $update = $user->update(['id' => $normal_id, '_useremails' => ['normal@glpi-project.org']]);
        $this->assertTrue($update);

        foreach (self::getTypesMapping() as $main_type => $sub_types) {
            $this->createNotification($main_type);
        }

        $item = getItemForItemtype($itemtype);

        $input = [
            'content' => $add_content,
        ];

        if (is_a($itemtype, CommonITILObject::class, true)) {
            $main_item = $item;
            $input['name'] = $this->getUniqueString(); // code does not handle absence of name in input
        } else {
            // Create main item to be able to attach it the sub item
            $main_item = getItemForItemtype($main_itemtype);

            $main_item_id = $main_item->add(
                [
                    'name'    => $this->getUniqueString(),
                    'content' => $this->getUniqueString(),
                ]
            );
            $this->assertGreaterThan(0, $main_item_id);

            if ($item->isField($main_item->getForeignKeyField())) {
                $input[$main_item->getForeignKeyField()] = $main_item_id;
            } else {
                $input['itemtype'] = $main_itemtype;
                $input['items_id'] = $main_item_id;
            }
        }

        if ($is_private !== null) {
            $input['is_private'] = $is_private ? 1 : 0;
        }

        // Create item
        $item_id = $item->add(Sanitizer::sanitize($input));
        $this->assertGreaterThan(0, $item_id);

        // Check observers on creation
        $observers = getAllDataFromTable(
            $main_item->userlinkclass::getTable(),
            [
                'type' => CommonITILActor::OBSERVER,
                $main_item->getForeignKeyField() => $main_item->getID(),
            ]
        );
        $this->assertCount(count($add_expected_observers), $observers);
        $this->assertEquals($add_expected_observers, array_column($observers, 'users_id'));

        // Check notifications sent on creation
        $notifications = getAllDataFromTable(
            'glpi_queuednotifications',
            [
                'itemtype' => $main_itemtype,
                'items_id' => $main_item->getID(),
            ]
        );
        $this->assertCount(count($add_expected_notified), $notifications);

        // Update item
        $update = $item->update(Sanitizer::sanitize(['id' => $item->getID(), 'content' => $update_content]));
        $this->assertTrue($update);

        // Check observers on update
        $observers = getAllDataFromTable(
            $main_item->userlinkclass::getTable(),
            [
                'type' => CommonITILActor::OBSERVER,
                $main_item->getForeignKeyField() => $main_item->getID(),
            ]
        );
        $expected_observers = array_merge($add_expected_observers, $update_expected_observers);
        $this->assertCount(count($expected_observers), $observers);
        $this->assertEquals($expected_observers, array_column($observers, 'users_id'));

        // Check notifications sent on update
        $notifications = getAllDataFromTable(
            'glpi_queuednotifications',
            [
                'itemtype' => $main_itemtype,
                'items_id' => $main_item->getID(),
            ]
        );
        $this->assertCount(count($add_expected_notified) + count($update_expected_notified), $notifications);
    }

    public static function ticketValidationProvider()
    {
        $tech_id = getItemByTypeName('User', 'tech', true);
        $normal_id = getItemByTypeName('User', 'normal', true);

        yield [
            // No user mention on creation => no observer
            'submission_add'            => <<<HTML
            <p>ping @tec</p>
HTML
         ,
            'validation_add'            => null,

            'add_expected_observers'    => [],
            'add_expected_notified'     => [],

            // Added mentions on update (submission) => new observers
            'submission_update'         => <<<HTML
            <p>ping <span data-user-mention="true" data-user-id="{$tech_id}">@tech</span></p>
HTML
         ,
            'validation_update'         => null,

            'update_expected_observers' => [$tech_id],
            'update_expected_notified'  => [$tech_id],
        ];

        yield [
            // No user mention on creation => no observer
            'submission_add'            => <<<HTML
            <p>ping @tec</p>
HTML
         ,
            'validation_add'            => null,

            'add_expected_observers'    => [],
            'add_expected_notified'     => [],

            // Added mentions on update (validation) => new observers
            'submission_update'         => null,
            'validation_update'         => <<<HTML
            <p>ping <span data-user-mention="true" data-user-id="{$tech_id}">@tech</span></p>
HTML
,

            'update_expected_observers' => [$tech_id],
            'update_expected_notified'  => [$tech_id],
        ];

        yield [
            // 1 user mention => 1 observer
            'submission_add'            => <<<HTML
            <p>ping <span data-user-mention="true" data-user-id="{$tech_id}">@tech</span></p>
HTML
         ,
            'validation_add'            => null,
            'add_expected_observers'    => [$tech_id],
            'add_expected_notified'     => [$tech_id],

            // Same mentions on update (submission) => mentionned users are not notified
            'submission_update'         => <<<HTML
            <p>ping <span data-user-mention="true" data-user-id="{$tech_id}">@tech</span></p>
HTML
         ,
            'validation_update'         => null,
            'update_expected_observers' => [],
            'update_expected_notified'  => [],
        ];

        yield [
            // 1 user mention => 1 observer
            'submission_add'            => <<<HTML
            <p>ping <span data-user-mention="true" data-user-id="{$tech_id}">@tech</span></p>
HTML
         ,
            'validation_add'            => null,
            'add_expected_observers'    => [$tech_id],
            'add_expected_notified'     => [$tech_id],

            // Same mentions on update (validation) => mentioned users are not notified
            'submission_update'         => null,
            'validation_update'         => <<<HTML
            <p>ping <span data-user-mention="true" data-user-id="{$tech_id}">@tech</span></p>
HTML
         ,
            'update_expected_observers' => [],
            'update_expected_notified'  => [],
        ];

        yield [
            // No user mention on creation => no observer
            'submission_add'            => <<<HTML
            <p>ping @tec</p>
HTML
         ,
            'validation_add'            => null,

            'add_expected_observers'    => [],
            'add_expected_notified'     => [],

            // Added mentions on update (submission and validation) => new observers
            'submission_update'         => <<<HTML
            <p>ping <span data-user-mention="true" data-user-id="{$tech_id}">@tech</span></p>
HTML
         ,
            'validation_update'         => <<<HTML
            <p>I discussed with <span data-user-id="{$normal_id}" data-user-mention="true">@normal</span> about ...</p>
HTML
         ,

            'update_expected_observers' => [$tech_id, $normal_id],
            'update_expected_notified'  => [$tech_id, $normal_id],
        ];

        yield [
            // multiple user mentions => multiple observer
            // validate that data-* attributes order has no impact
            'submission_add'            => <<<HTML
            <p>Hi <span data-user-mention="true" data-user-id="{$tech_id}">@tech</span>,</p>
            <p>I discussed with <span data-user-id="{$normal_id}" data-user-mention="true">@normal</span> about ...</p>
HTML
         ,
            'validation_add'            => null,
            'add_expected_observers'    => [$tech_id, $normal_id],
            'add_expected_notified'     => [$tech_id, $normal_id],

            // Deleted mentions on update => no change on observers
            'submission_update'            => <<<HTML
            <p>Hi <span data-user-mention="true" data-user-id="{$tech_id}">@tech</span>,</p>
            <p> ... </p>
HTML
         ,
            'validation_update'            => null,
            'update_expected_observers'    => [],
            'update_expected_notified'     => [],
        ];
    }

    /**
     * Specific tests on TicketValidation that contains 2 content fields.
     *
     * @dataProvider ticketValidationProvider
     */
    public function testHandleUserMentionsOnTicketValidation(
        ?string $submission_add,
        ?string $validation_add,
        array $add_expected_observers,
        array $add_expected_notified,
        ?string $submission_update,
        ?string $validation_update,
        array $update_expected_observers,
        array $update_expected_notified
    ) {
        global $CFG_GLPI;
        $CFG_GLPI['use_notifications'] = 1;
        $CFG_GLPI['notifications_mailing'] = 1;

        $tech_id = getItemByTypeName('User', 'tech', true);
        $normal_id = getItemByTypeName('User', 'normal', true);

        // Delete existing notifications targets (to prevent sending of notifications not related to user_mention)
        $notification_targets = new NotificationTarget();
        $notification_targets->deleteByCriteria(['NOT' => ['items_id' => Notification::MENTIONNED_USER]]);

        // Add email to users for notifications
        $this->login(); // must be authenticated to update emails
        $user = new User();
        $update = $user->update(['id' => $tech_id, '_useremails' => ['tech@glpi-project.org']]);
        $this->assertTrue($update);
        $update = $user->update(['id' => $normal_id, '_useremails' => ['normal@glpi-project.org']]);
        $this->assertTrue($update);

        // Create ticket
        $ticket = new Ticket();
        $ticket_id = $ticket->add(
            [
                'name'    => $this->getUniqueString(),
                'content' => $this->getUniqueString(),
            ]
        );
        $this->assertGreaterThan(0, $ticket_id);

        // Create TicketValidation
        $input = [
            'tickets_id'        => $ticket_id,
            'users_id_validate' => Session::getLoginUserID(),
        ];
        if ($submission_add !== null) {
            $input['comment_submission'] = $submission_add;
        }
        if ($validation_add !== null) {
            $input['comment_validation'] = $validation_add;
        }
        $ticket_validation = new TicketValidation();
        $ticket_validation_id = $ticket_validation->add(Sanitizer::sanitize($input));
        $this->assertGreaterThan(0, $ticket_validation_id);

        // Check observers on creation
        $observers = getAllDataFromTable(
            Ticket_User::getTable(),
            [
                'type'       => CommonITILActor::OBSERVER,
                'tickets_id' => $ticket->getID(),
            ]
        );
        $this->assertCount(count($add_expected_observers), $observers);
        $this->assertEquals($add_expected_observers, array_column($observers, 'users_id'));

        // Check notifications sent on creation
        $notifications = getAllDataFromTable(
            'glpi_queuednotifications',
            [
                'itemtype' => Ticket::getType(),
                'items_id' => $ticket_id,
            ]
        );
        $this->assertCount(count($add_expected_notified), $notifications);

        // Update TicketValidation
        $input = [
            'id' => $ticket_validation_id
        ];
        if ($submission_update !== null) {
            $input['comment_submission'] = $submission_update;
        }
        if ($validation_update !== null) {
            $input['comment_validation'] = $validation_update;
        }
        $update = $ticket_validation->update(Sanitizer::sanitize($input));
        $this->assertTrue($update);

        // Check observers on update
        $observers = getAllDataFromTable(
            Ticket_User::getTable(),
            [
                'type'       => CommonITILActor::OBSERVER,
                'tickets_id' => $ticket->getID(),
            ]
        );
        $expected_observers = array_merge($add_expected_observers, $update_expected_observers);
        $this->assertCount(count($expected_observers), $observers);
        $this->assertEquals($expected_observers, array_column($observers, 'users_id'));

        // Check notifications sent on update
        $notifications = getAllDataFromTable(
            'glpi_queuednotifications',
            [
                'itemtype' => Ticket::getType(),
                'items_id' => $ticket_id,
            ]
        );
        $this->assertCount(count($add_expected_notified) + count($update_expected_notified), $notifications);
    }

    /**
     * Create user_mention notification / template / targets for given itemtype.
     *
     * @param string $itemtype
     *
     * @return void
     */
    private function createNotification(string $itemtype): void
    {
        $notification = new Notification();
        $id = $notification->add(
            [
                'name'        => 'New user mentioned',
                'entities_id' => 0,
                'itemtype'    => $itemtype,
                'event'       => 'user_mention',
                'is_active'   => 1,
            ]
        );
        $this->assertGreaterThan(0, $id);

        $template = new NotificationTemplate();
        $template_id = $template->add(
            [
                'name'     => 'New user mentioned',
                'itemtype' => $itemtype,
            ]
        );
        $this->assertGreaterThan(0, $template_id);

        $template_translation = new NotificationTemplateTranslation();
        $template_translation_id = $template_translation->add(
            [
                'notificationtemplates_id' => $template_id,
                'language'                 => '',
                'subject'                  => 'You have been mentioned',
                'content_text'             => '...',
                'content_html'             => '...',
            ]
        );
        $this->assertGreaterThan(0, $template_translation_id);

        $notification_notificationtemplate = new Notification_NotificationTemplate();
        $notification_notificationtemplate_id = $notification_notificationtemplate->add(
            [
                'notifications_id'         => $id,
                'mode'                     => Notification_NotificationTemplate::MODE_MAIL,
                'notificationtemplates_id' => $template_id,
            ]
        );
        $this->assertGreaterThan(0, $notification_notificationtemplate_id);

        $target = new NotificationTarget();
        $target_id = $target->add(
            [
                'items_id'         => Notification::MENTIONNED_USER,
                'type'             => 1,
                'notifications_id' => $id,
            ]
        );
        $this->assertGreaterThan(0, $target_id);
    }
}
