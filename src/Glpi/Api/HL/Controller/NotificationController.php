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

namespace Glpi\Api\HL\Controller;

use Entity;
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Middleware\ResultFormatterMiddleware;
use Glpi\Api\HL\ResourceAccessor;
use Glpi\Api\HL\Route;
use Glpi\Api\HL\RouteVersion;
use Glpi\Http\Request;
use Glpi\Http\Response;
use Notification;
use Notification_NotificationTemplate;
use NotificationSetting;
use NotificationTarget;
use NotificationTemplate;
use NotificationTemplateTranslation;
use QueuedNotification;

#[Route(path: '/Notifications', requirements: [
    'id' => '\d+',
    'notification_id' => '\d+',
    'template_id' => '\d+',
], tags: ['Notifications'])]
class NotificationController extends AbstractController
{
    protected static function getRawKnownSchemas(): array
    {
        return [
            'Notification' => [
                'x-version-introduced' => '2.3.0',
                'x-itemtype' => Notification::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                    'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                    'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                    'is_active' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                    'itemtype' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 100],
                    'event' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'maxLength' => 255,
                        'description' => 'The event that triggers the notification. The possible values depend on the type of item the notification is linked to.',
                    ],
                    'comment' => ['type' => Doc\Schema::TYPE_STRING],
                    'allow_reply' => [
                        'type' => Doc\Schema::TYPE_BOOLEAN,
                        'default' => true,
                        'x-field' => 'allow_response',
                    ],
                    'attach_documents' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'enum' => [
                            NotificationSetting::ATTACH_INHERIT,
                            NotificationSetting::ATTACH_NO_DOCUMENT,
                            NotificationSetting::ATTACH_ALL_DOCUMENTS,
                            NotificationSetting::ATTACH_FROM_TRIGGER_ONLY,
                        ],
                        'description' => <<<EOT
                        The way documents are attached to the notification. Possible values are:
                        - -2: Use the global setting (inherit from global config)
                        - 0 :No document
                        - 1: All documents
                        - 2: Only documents related to the item that triggers the event
                        EOT,
                    ],
                    'date_creation' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                        'readOnly' => true,
                    ],
                    'date_mod' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                        'readOnly' => true,
                    ],
                    'recipients' => [
                        'x-version-introduced' => '2.3.0',
                        'type' => Doc\Schema::TYPE_ARRAY,
                        'items' => [
                            'type' => Doc\Schema::TYPE_OBJECT,
                            'x-full-schema' => 'NotificationRecipient',
                            'x-join' => [
                                'table' => NotificationTarget::getTable(),
                                'fkey' => 'id',
                                'field' => 'notifications_id',
                                'primary-property' => 'id',
                            ],
                            'properties' => [
                                'id' => [
                                    'type' => Doc\Schema::TYPE_INTEGER,
                                    'readOnly' => true,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'NotificationRecipient' => [
                'x-version-introduced' => '2.3.0',
                'x-itemtype' => NotificationTarget::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'notification' => self::getDropdownTypeSchema(class: Notification::class, full_schema: 'Notification'),
                    'type' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => <<<EOT
                            The type of recipient. Possible values may depend on the type of item the notification is linked to.
                            - 1: User
                            - 2: Profile
                            - 3: Group
                            - 4: Seems unused but internally it is referenced as "people in charge of the database synchronization". This is probably a legacy type, or it was never truly implemented.
                            - 5: Manager of a group
                            - 6: Users of a group excluding managers
EOT,
                        'enum' => [
                            Notification::USER_TYPE,
                            Notification::PROFILE_TYPE,
                            Notification::GROUP_TYPE,
                            Notification::MAILING_TYPE,
                            Notification::SUPERVISOR_GROUP_TYPE,
                            Notification::GROUP_WITHOUT_SUPERVISOR_TYPE,
                        ],
                    ],
                    'items_id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'description' => <<<EOT
                            The IDs or values of the recipient items. The type of items depends on the type of recipient.
                            Note that for users, the items_id does NOT refer to a specific user, but rather a type of user.
                            For profilers, groups, manager of groups, and users of groups, the items_id refers to the actual IDs of the profiles, groups, etc. that are recipient of the notification.
                            Known values for users are:
                            - 1: Global administrator
                            - 2: Assigned technicians
                            - 3: Author/Owner of the item
                            - 4: Previously assigned technician
                            - 5: Technician in charge of the item
                            - 6: User of the item
                            - 7: Recipient (writer of the ticket)
                            - 8: Assigned suppliers
                            - 9: Assigned group members
                            - 10: Manager of the assigned group
                            - 11: Entity administrator
                            - 12: Manager of the requester group
                            - 13: Requester group members
                            - 14: Approval answerer (The user that responded to the approval request)
                            - 15: Approval requester (The user that made the approval request)
                            - 16: Task assigned user
                            - 17: Task author
                            - 18: Followup author
                            - 19: User (used in cases where there is only one related user such as object locks, saved search alerts, and password notifications)
                            - 20: Observer groups (groups directly added as an observer, not the groups of observer users)
                            - 21: Observer users
                            - 22: Manager of the observer group
                            - 23: Technician group members in charge of the item
                            - 24: Assigned group members, excluding managers
                            - 25: Requester group members, excluding managers
                            - 26: Observer group members, excluding managers
                            - 27: Project manager users
                            - 28: Project manager group members
                            - 29: Project manager group managers
                            - 30: Project manager group members, excluding managers
                            - 31: Team member users (used in projects and project tasks)
                            - 32: Team member group members (used in projects and project tasks)
                            - 33: Team member group managers (used in projects and project tasks)
                            - 34: Team member group members, excluding managers (used in projects and project tasks)
                            - 35: Team member contacts (used in projects and project tasks)
                            - 36: Team member suppliers (used in projects and project tasks)
                            - 37: Task assigned group members
                            - 38: Planning event guests
                            - 39: Mentioned users
                            - 40: Approval target
                            - 41: Approval target's approved substitutes (users that can approve on behalf of the approval target when they are not available).
EOT,
                    ],
                    'is_exclusion' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                ],
            ],
            'NotificationTemplate' => [
                'x-version-introduced' => '2.3.0',
                'x-itemtype' => NotificationTemplate::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                    'itemtype' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 100],
                    'comment' => ['type' => Doc\Schema::TYPE_STRING],
                    'css' => ['type' => Doc\Schema::TYPE_STRING],
                    'date_creation' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                        'readOnly' => true,
                    ],
                    'date_mod' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                        'readOnly' => true,
                    ],
                    'translations' => [
                        'x-version-introduced' => '2.3.0',
                        'type' => Doc\Schema::TYPE_ARRAY,
                        'items' => [
                            'type' => Doc\Schema::TYPE_OBJECT,
                            'x-full-schema' => 'NotificationTemplateTranslation',
                            'x-join' => [
                                'table' => NotificationTemplateTranslation::getTable(),
                                'fkey' => 'id',
                                'field' => 'notificationtemplates_id',
                                'primary-property' => 'id',
                            ],
                            'properties' => [
                                'id' => [
                                    'type' => Doc\Schema::TYPE_INTEGER,
                                    'readOnly' => true,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'NotificationTemplateTranslation' => [
                'x-version-introduced' => '2.3.0',
                'x-itemtype' => NotificationTemplateTranslation::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'notification_template' => self::getDropdownTypeSchema(class: NotificationTemplate::class, full_schema: 'NotificationTemplate'),
                    'language' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 10, 'default' => ''],
                    'subject' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                    'content_text' => ['type' => Doc\Schema::TYPE_STRING],
                    'content_html' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_HTML],
                ],
            ],
            'Notification_NotificationTemplate' => [
                'x-version-introduced' => '2.3.0',
                'x-itemtype' => Notification_NotificationTemplate::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'notification' => self::getDropdownTypeSchema(class: Notification::class, full_schema: 'Notification'),
                    'notification_template' => self::getDropdownTypeSchema(class: NotificationTemplate::class, full_schema: 'NotificationTemplate'),
                    'mode' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => <<<EOT
                            The mode of the notification template for the notification.
                            Typically only "mailing" or "ajax", but there are a few other types known to GLPI but not directly used, or which may be added by plugins.
                            - mailing: Email notification
                            - ajax: Browser notification (client-initiated pull notifications, not real-time push notifications)
                            - websocket: Not used by GLPI core
                            - sms: Not used by GLPI core
                            - xmpp: Not used by GLPI core
                            - irc: Not used by GLPI core
EOT,
                        'maxLength' => 20,
                        'enum' => ['mailing', 'ajax', 'websocket', 'sms', 'xmpp', 'irc'],
                    ],
                ],
            ],
            'QueuedNotification' => [
                'x-version-introduced' => '2.3.0',
                'x-itemtype' => QueuedNotification::class,
                'type' => Doc\Schema::TYPE_OBJECT,
                'properties' => [
                    'id' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                        'readOnly' => true,
                    ],
                    'itemtype' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 100],
                    'items_id' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
                    'notification_template' => self::getDropdownTypeSchema(class: NotificationTemplate::class, full_schema: 'NotificationTemplate'),
                    'entity' => self::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                    'is_deleted' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'default' => false],
                    'sent_try' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'default' => 0,
                        'description' => 'The number of times sending this notification has been attempted.',
                    ],
                    'create_time' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                        'readOnly' => true,
                        'description' => 'The time when the notification was created and queued for sending.',
                    ],
                    'expected_send_date' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                        'readOnly' => true,
                        'x-field' => 'send_time',
                        'description' => 'The time when the notification is expected to be sent. This may be in the past for notifications that have already been sent, or in the future for notifications that are scheduled to be (re-)sent later.',
                    ],
                    'send_date' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                        'readOnly' => true,
                        'x-field' => 'sent_time',
                        'description' => 'The time when the notification was actually sent.',
                    ],
                    'name' => ['type' => Doc\Schema::TYPE_STRING],
                    'sender' => ['type' => Doc\Schema::TYPE_STRING],
                    'sender_name' => ['type' => Doc\Schema::TYPE_STRING, 'x-field' => 'sendername'],
                    'recipient' => ['type' => Doc\Schema::TYPE_STRING],
                    'recipient_name' => ['type' => Doc\Schema::TYPE_STRING, 'x-field' => 'recipientname'],
                    'replyto' => ['type' => Doc\Schema::TYPE_STRING],
                    'replyto_name' => ['type' => Doc\Schema::TYPE_STRING, 'x-field' => 'replytoname'],
                    'headers' => ['type' => Doc\Schema::TYPE_STRING],
                    'body_text' => ['type' => Doc\Schema::TYPE_STRING],
                    'body_html' => ['type' => Doc\Schema::TYPE_STRING, 'format' => Doc\Schema::FORMAT_STRING_HTML],
                    'message_id' => ['type' => Doc\Schema::TYPE_STRING, 'x-field' => 'messageid'],
                    'documents' => ['type' => Doc\Schema::TYPE_STRING],
                    'mode' => [
                        'type' => Doc\Schema::TYPE_STRING,
                        'description' => <<<EOT
                            The mode of the notification template for the notification.
                            Typically only "mailing" or "ajax", but there are a few other types known to GLPI but not directly used, or which may be added by plugins.
                            - mailing: Email notification
                            - ajax: Browser notification (client-initiated pull notifications, not real-time push notifications)
                            - websocket: Not used by GLPI core
                            - sms: Not used by GLPI core
                            - xmpp: Not used by GLPI core
                            - irc: Not used by GLPI core
EOT,
                        'maxLength' => 20,
                        'enum' => ['mailing', 'ajax', 'websocket', 'sms', 'xmpp', 'irc'],
                    ],
                    'event' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                    'attach_documents' => [
                        'type' => Doc\Schema::TYPE_INTEGER,
                        'enum' => [
                            NotificationSetting::ATTACH_NO_DOCUMENT,
                            NotificationSetting::ATTACH_ALL_DOCUMENTS,
                            NotificationSetting::ATTACH_FROM_TRIGGER_ONLY,
                        ],
                        'description' => <<<EOT
                        The way documents are attached to the notification. Possible values are:
                        - 0 :No document
                        - 1: All documents
                        - 2: Only documents related to the item that triggers the event
                        EOT,
                    ],
                    //TODO how are these 2 properties different from itemtype and items_id?
                    'itemtype_trigger' => ['type' => Doc\Schema::TYPE_STRING, 'maxLength' => 255],
                    'items_id_trigger' => ['type' => Doc\Schema::TYPE_INTEGER, 'format' => Doc\Schema::FORMAT_INTEGER_INT64],
                ],
            ],
        ];
    }

    #[Route(path: '/QueuedNotification', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\SearchRoute(schema_name: 'QueuedNotification')]
    public function searchQueuedNotifications(Request $request): Response
    {
        return ResourceAccessor::searchBySchema($this->getKnownSchema('QueuedNotification', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/QueuedNotification/{id}', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\GetRoute(schema_name: 'QueuedNotification')]
    public function getQueuedNotification(Request $request): Response
    {
        return ResourceAccessor::getOneBySchema(
            $this->getKnownSchema('QueuedNotification', $this->getAPIVersion($request)),
            $request->getAttributes(),
            $request->getParameters()
        );
    }

    #[Route(path: '/QueuedNotification/{id}/SendRequest', methods: ['POST'])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\Route(description: 'Request to send a queued notification')]
    public function requestQueuedNotificationSend(Request $request): Response
    {
        $queued_notification = new QueuedNotification();
        if (!$queued_notification->getFromDB($request->getAttributes()['id'])) {
            return self::getNotFoundErrorResponse();
        }
        if (!$queued_notification->can($queued_notification->getID(), UPDATE)) {
            return self::getAccessDeniedErrorResponse();
        }
        $queued_notification->sendById($queued_notification->getID());
        return new Response(202);
    }

    #[Route(path: '/Notification', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\SearchRoute(schema_name: 'Notification')]
    public function searchNotifications(Request $request): Response
    {
        return ResourceAccessor::searchBySchema($this->getKnownSchema('Notification', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/Notification/{id}', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\GetRoute(schema_name: 'Notification')]
    public function getNotification(Request $request): Response
    {
        return ResourceAccessor::getOneBySchema(
            $this->getKnownSchema('Notification', $this->getAPIVersion($request)),
            $request->getAttributes(),
            $request->getParameters()
        );
    }

    #[Route(path: '/Notification', methods: ['POST'])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\CreateRoute(schema_name: 'Notification')]
    public function createNotification(Request $request): Response
    {
        return ResourceAccessor::createBySchema(
            $this->getKnownSchema('Notification', $this->getAPIVersion($request)),
            $request->getParameters(),
            [self::class, 'getNotification']
        );
    }

    #[Route(path: '/Notification/{id}', methods: ['PATCH'])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\UpdateRoute(schema_name: 'Notification')]
    public function updateNotification(Request $request): Response
    {
        return ResourceAccessor::updateBySchema(
            $this->getKnownSchema('Notification', $this->getAPIVersion($request)),
            $request->getAttributes(),
            $request->getParameters(),
        );
    }

    #[Route(path: '/Notification/{id}', methods: ['DELETE'])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\DeleteRoute(schema_name: 'Notification')]
    public function deleteNotification(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema(
            $this->getKnownSchema('Notification', $this->getAPIVersion($request)),
            $request->getAttributes(),
            $request->getParameters(),
        );
    }

    #[Route(path: '/Notification/{notification_id}/Recipient', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\SearchRoute(schema_name: 'NotificationRecipient')]
    public function searchNotificationRecipients(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';notification.id==' . $request->getAttributes()['notification_id'];
        $request->setParameter('filter', $filters);
        return ResourceAccessor::searchBySchema($this->getKnownSchema('NotificationRecipient', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/Notification/{notification_id}/Recipient/{id}', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\GetRoute(schema_name: 'NotificationRecipient')]
    public function getNotificationRecipient(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';notification.id==' . $request->getAttributes()['notification_id'];
        $request->setParameter('filter', $filters);
        return ResourceAccessor::getOneBySchema(
            $this->getKnownSchema('NotificationRecipient', $this->getAPIVersion($request)),
            $request->getAttributes(),
            $request->getParameters()
        );
    }

    #[Route(path: '/Notification/{notification_id}/Recipient', methods: ['POST'])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\CreateRoute(schema_name: 'NotificationRecipient')]
    public function createNotificationRecipient(Request $request): Response
    {
        $request->setParameter('notification', $request->getAttributes()['notification_id']);
        return ResourceAccessor::createBySchema(
            $this->getKnownSchema('NotificationRecipient', $this->getAPIVersion($request)),
            $request->getParameters(),
            [self::class, 'getNotificationRecipient']
        );
    }

    #[Route(path: '/Notification/{notification_id}/Recipient/{id}', methods: ['DELETE'])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\DeleteRoute(schema_name: 'NotificationRecipient')]
    public function deleteNotificationRecipient(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema(
            $this->getKnownSchema('NotificationRecipient', $this->getAPIVersion($request)),
            $request->getAttributes(),
            $request->getParameters(),
        );
    }

    #[Route(path: '/NotificationTemplate', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\SearchRoute(schema_name: 'NotificationTemplate')]
    public function searchNotificationTemplates(Request $request): Response
    {
        return ResourceAccessor::searchBySchema($this->getKnownSchema('NotificationTemplate', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/NotificationTemplate/{id}', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\GetRoute(schema_name: 'NotificationTemplate')]
    public function getNotificationTemplate(Request $request): Response
    {
        return ResourceAccessor::getOneBySchema(
            $this->getKnownSchema('NotificationTemplate', $this->getAPIVersion($request)),
            $request->getAttributes(),
            $request->getParameters()
        );
    }

    #[Route(path: '/NotificationTemplate', methods: ['POST'])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\CreateRoute(schema_name: 'NotificationTemplate')]
    public function createNotificationTemplate(Request $request): Response
    {
        return ResourceAccessor::createBySchema(
            $this->getKnownSchema('NotificationTemplate', $this->getAPIVersion($request)),
            $request->getParameters(),
            [self::class, 'getNotificationTemplate']
        );
    }

    #[Route(path: '/NotificationTemplate/{id}', methods: ['PATCH'])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\UpdateRoute(schema_name: 'NotificationTemplate')]
    public function updateNotificationTemplate(Request $request): Response
    {
        return ResourceAccessor::updateBySchema(
            $this->getKnownSchema('NotificationTemplate', $this->getAPIVersion($request)),
            $request->getAttributes(),
            $request->getParameters(),
        );
    }

    #[Route(path: '/NotificationTemplate/{id}', methods: ['DELETE'])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\DeleteRoute(schema_name: 'NotificationTemplate')]
    public function deleteNotificationTemplate(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema(
            $this->getKnownSchema('NotificationTemplate', $this->getAPIVersion($request)),
            $request->getAttributes(),
            $request->getParameters(),
        );
    }

    #[Route(path: '/NotificationTemplate/{template_id}/Translation', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\SearchRoute(schema_name: 'NotificationTemplateTranslation')]
    public function searchNotificationTemplateTranslations(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';notification_template.id==' . $request->getAttributes()['notification_template_id'];
        $request->setParameter('filter', $filters);
        return ResourceAccessor::searchBySchema($this->getKnownSchema('NotificationTemplateTranslation', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/NotificationTemplate/{template_id}/Translation/{id}', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\GetRoute(schema_name: 'NotificationTemplateTranslation')]
    public function getNotificationTemplateTranslation(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';notification_template.id==' . $request->getAttributes()['notification_template_id'];
        $request->setParameter('filter', $filters);
        return ResourceAccessor::getOneBySchema(
            $this->getKnownSchema('NotificationTemplateTranslation', $this->getAPIVersion($request)),
            $request->getAttributes(),
            $request->getParameters()
        );
    }

    #[Route(path: '/NotificationTemplate/{template_id}/Translation/{language}', methods: ['GET'], requirements: [
        'language' => '\w+',
    ], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\GetRoute(schema_name: 'NotificationTemplateTranslation')]
    public function getNotificationTemplateTranslationByLanguage(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';notification_template.id==' . $request->getAttributes()['notification_template_id'];
        $request->setParameter('filter', $filters);
        return ResourceAccessor::getOneBySchema(
            $this->getKnownSchema('NotificationTemplateTranslation', $this->getAPIVersion($request)),
            $request->getAttributes(),
            $request->getParameters(),
            'language'
        );
    }

    #[Route(path: '/NotificationTemplate/{template_id}/Translation/Default', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\GetRoute(schema_name: 'NotificationTemplateTranslation')]
    public function getNotificationTemplateTranslationDefaultLanguage(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';notification_template.id==' . $request->getAttributes()['notification_template_id'];
        $request->setParameter('filter', $filters);
        $request->setAttribute('language', '');
        return ResourceAccessor::getOneBySchema(
            $this->getKnownSchema('NotificationTemplateTranslation', $this->getAPIVersion($request)),
            $request->getAttributes(),
            $request->getParameters(),
            'language'
        );
    }

    #[Route(path: '/NotificationTemplate/{template_id}/Translation', methods: ['POST'])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\CreateRoute(schema_name: 'NotificationTemplateTranslation')]
    public function createNotificationTemplateTranslation(Request $request): Response
    {
        $request->setParameter('notification_template', $request->getAttributes()['notification_template_id']);
        return ResourceAccessor::createBySchema(
            $this->getKnownSchema('NotificationTemplateTranslation', $this->getAPIVersion($request)),
            $request->getParameters(),
            [self::class, 'getNotificationTemplateTranslationByLanguage']
        );
    }

    #[Route(path: '/NotificationTemplate/{template_id}/Translation/{id}', methods: ['PATCH'])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\UpdateRoute(schema_name: 'NotificationTemplateTranslation')]
    public function updateNotificationTemplateTranslation(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';notification_template.id==' . $request->getAttributes()['notification_template_id'];
        $filters .= ';language==' . $request->getAttributes()['language'];
        $request->setParameter('filter', $filters);
        return ResourceAccessor::updateBySchema(
            $this->getKnownSchema('NotificationTemplateTranslation', $this->getAPIVersion($request)),
            $request->getAttributes(),
            $request->getParameters(),
            'language'
        );
    }

    #[Route(path: '/NotificationTemplate/{template_id}/Translation/{id}', methods: ['DELETE'])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\DeleteRoute(schema_name: 'NotificationTemplateTranslation')]
    public function deleteNotificationTemplateTranslation(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema(
            $this->getKnownSchema('NotificationTemplateTranslation', $this->getAPIVersion($request)),
            $request->getAttributes(),
            $request->getParameters(),
        );
    }

    #[Route(path: '/Notification/{notification_id}/NotificationTemplate', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\SearchRoute(schema_name: 'Notification_NotificationTemplate')]
    public function searchNotification_NotificationTemplates(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';notification.id==' . $request->getAttributes()['notification_id'];
        $request->setParameter('filter', $filters);
        return ResourceAccessor::searchBySchema($this->getKnownSchema('Notification_NotificationTemplate', $this->getAPIVersion($request)), $request->getParameters());
    }

    #[Route(path: '/Notification/{notification_id}/NotificationTemplate/{id}', methods: ['GET'], middlewares: [ResultFormatterMiddleware::class])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\GetRoute(schema_name: 'Notification_NotificationTemplate')]
    public function getNotification_NotificationTemplate(Request $request): Response
    {
        $filters = $request->hasParameter('filter') ? $request->getParameter('filter') : '';
        $filters .= ';notification.id==' . $request->getAttributes()['notification_id'];
        $request->setParameter('filter', $filters);
        return ResourceAccessor::getOneBySchema(
            $this->getKnownSchema('Notification_NotificationTemplate', $this->getAPIVersion($request)),
            $request->getAttributes(),
            $request->getParameters()
        );
    }

    #[Route(path: '/Notification/{notification_id}/NotificationTemplate', methods: ['DELETE'])]
    #[RouteVersion(introduced: '2.3')]
    #[Doc\DeleteRoute(schema_name: 'Notification_NotificationTemplate')]
    public function deleteNotification_NotificationTemplate(Request $request): Response
    {
        return ResourceAccessor::deleteBySchema(
            $this->getKnownSchema('Notification_NotificationTemplate', $this->getAPIVersion($request)),
            $request->getAttributes(),
            $request->getParameters(),
        );
    }
}
