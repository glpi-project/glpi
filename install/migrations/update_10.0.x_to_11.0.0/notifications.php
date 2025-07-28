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

use Glpi\DBAL\QueryExpression;

/**
 * @var DBmysql $DB
 * @var Migration $migration
 */

/** Password initialization notification */
$notification_exists = countElementsInTable('glpi_notifications', ['itemtype' => 'User', 'event' => 'passwordinit']) > 0;
if (!$notification_exists) {
    $DB->insert(
        'glpi_notifications',
        [
            'id'              => null,
            'name'            => 'Password Initialization',
            'entities_id'     => 0,
            'itemtype'        => 'User',
            'event'           => 'passwordinit',
            'comment'         => '',
            'is_recursive'    => 1,
            'is_active'       => 1,
            'date_creation'   => new QueryExpression('NOW()'),
            'date_mod'        => new QueryExpression('NOW()'),
        ]
    );
    $notification_id = $DB->insertId();

    $DB->insert(
        'glpi_notificationtemplates',
        [
            'name' => 'Password Initialization',
            'itemtype' => 'User',
        ]
    );

    $notificationtemplate_id = $DB->insertId();

    $DB->insert(
        'glpi_notificationtemplatetranslations',
        [
            'notificationtemplates_id' => $notificationtemplate_id,
            'language' => '',
            'subject' => '##user.action##',
            'content_text' => <<<PLAINTEXT
    ##user.realname## ##user.firstname##

    ##lang.passwordinit.information##

    ##lang.passwordinit.link## ##user.passwordiniturl##
    PLAINTEXT,
            'content_html' => <<<HTML
    &lt;p&gt;&lt;strong&gt;##user.realname## ##user.firstname##&lt;/strong&gt;&lt;/p&gt;
    &lt;p&gt;##lang.passwordinit.information##&lt;/p&gt;
    &lt;p&gt;##lang.passwordinit.link## &lt;a title="##user.passwordiniturl##" href="##user.passwordiniturl##"&gt;##user.passwordiniturl##&lt;/a&gt;&lt;/p&gt;
    HTML,
        ]
    );

    $DB->insert(
        'glpi_notifications_notificationtemplates',
        [
            'notifications_id'         => $notification_id,
            'mode'                     => Notification_NotificationTemplate::MODE_MAIL,
            'notificationtemplates_id' => $notificationtemplate_id,
        ]
    );

    $DB->insert(
        'glpi_notificationtargets',
        [
            'items_id'         => '19',
            'type'             => '1',
            'notifications_id' => $notification_id,
        ]
    );
}
/** /Password initialization notification */


/** Change Satisfaction notification */
if (countElementsInTable('glpi_notifications', ['itemtype' => 'Change', 'event' => 'satisfaction']) === 0) {
    $DB->insert(
        'glpi_notificationtemplates',
        [
            'name'            => 'Change Satisfaction',
            'itemtype'        => 'Change',
            'date_mod'        => new QueryExpression('NOW()'),
        ]
    );
    $notificationtemplate_id = $DB->insertId();

    $DB->insert(
        'glpi_notificationtemplatetranslations',
        [
            'notificationtemplates_id' => $notificationtemplate_id,
            'language'                 => '',
            'subject'                  => '##change.action## ##change.title##',
            'content_text'             => <<<PLAINTEXT
##lang.change.title## : ##change.title##

##lang.change.closedate## : ##change.closedate##

##lang.satisfaction.text## ##change.urlsatisfaction##
PLAINTEXT,
            'content_html'             => <<<HTML
&lt;p&gt;##lang.change.title## : ##change.title##&lt;/p&gt;
&lt;p&gt;##lang.change.closedate## : ##change.closedate##&lt;/p&gt;
&lt;p&gt;##lang.satisfaction.text## &lt;a href="##change.urlsatisfaction##"&gt;##change.urlsatisfaction##&lt;/a&gt;&lt;/p&gt;
HTML,
        ]
    );

    $notifications_data = [
        [
            'event' => 'satisfaction',
            'name'  => 'Change Satisfaction',
        ],
        [
            'event' => 'replysatisfaction',
            'name'  => 'Change Satisfaction Answer',
        ],
    ];
    foreach ($notifications_data as $notification_data) {
        $DB->insert(
            'glpi_notifications',
            [
                'name'            => $notification_data['name'],
                'entities_id'     => 0,
                'itemtype'        => 'Change',
                'event'           => $notification_data['event'],
                'comment'         => null,
                'is_recursive'    => 1,
                'is_active'       => 1,
                'date_creation'   => new QueryExpression('NOW()'),
                'date_mod'        => new QueryExpression('NOW()'),
            ]
        );
        $notification_id = $DB->insertId();

        $DB->insert(
            'glpi_notifications_notificationtemplates',
            [
                'notifications_id'         => $notification_id,
                'mode'                     => Notification_NotificationTemplate::MODE_MAIL,
                'notificationtemplates_id' => $notificationtemplate_id,
            ]
        );

        $DB->insert(
            'glpi_notificationtargets',
            [
                'items_id'         => 3,
                'type'             => 1,
                'notifications_id' => $notification_id,
            ]
        );

        if ($notification_data['event'] === 'replysatisfaction') {
            $DB->insert(
                'glpi_notificationtargets',
                [
                    'items_id'         => 2,
                    'type'             => 1,
                    'notifications_id' => $notification_id,
                ]
            );
        }
    }
}
/** /Change Satisfaction notification */

/** Add new notification for AutoBump */
if (countElementsInTable('glpi_notifications', ['itemtype' => 'Ticket', 'event' => 'auto_reminder']) === 0) {
    $DB->insert('glpi_notificationtemplates', [
        'name' => 'Automatic reminder',
        'itemtype' => 'Ticket',
    ]);

    $notificationtemplate_id = $DB->insertId();

    $DB->insert('glpi_notificationtemplatetranslations', [
        'notificationtemplates_id' => $notificationtemplate_id,
        'language' => '',
        'subject' => '##ticket.action## ##ticket.name##',
        'content_text' => '##lang.ticket.title##: ##ticket.title##

##lang.ticket.reminder.bumpcounter##: ##ticket.reminder.bumpcounter##
##lang.ticket.reminder.bumpremaining##: ##ticket.reminder.bumpremaining##
##lang.ticket.reminder.bumptotal##: ##ticket.reminder.bumptotal##
##lang.ticket.reminder.deadline##: ##ticket.reminder.deadline##

##lang.ticket.reminder.text##: ##ticket.reminder.text##',
        'content_html' => '&lt;p&gt;##lang.ticket.title##: ##ticket.title##&lt;/p&gt;
            &lt;p&gt;##lang.ticket.reminder.bumpcounter##: ##ticket.reminder.bumpcounter##&lt;/a&gt;&lt;br /&gt;
            ##lang.ticket.reminder.bumpremaining##: ##ticket.reminder.bumpremaining##&lt;/a&gt;&lt;br /&gt;
            ##lang.ticket.reminder.bumptotal##: ##ticket.reminder.bumptotal##&lt;/a&gt;&lt;br /&gt;
            ##lang.ticket.reminder.deadline##: ##ticket.reminder.deadline##&lt;/p&gt;
            &lt;p&gt;##lang.ticket.reminder.text##: ##ticket.reminder.text##&lt;/p&gt;',
    ]);

    $DB->insert(
        'glpi_notifications',
        [
            'name'            => 'Automatic reminder',
            'itemtype'        => 'Ticket',
            'event'           => 'auto_reminder',
            'comment'         => null,
            'is_recursive'    => 1,
            'is_active'       => 0,
        ]
    );
    $notification_id = $DB->insertId();

    $targets = [
        [
            'items_id' => 3,
            'type' => 1,
        ],
        [
            'items_id' => 1,
            'type' => 1,
        ],
        [
            'items_id' => 21,
            'type' => 1,
        ],
    ];

    foreach ($targets as $target) {
        $DB->insert('glpi_notificationtargets', [
            'items_id'         => $target['items_id'],
            'type'             => $target['type'],
            'notifications_id' => $notification_id,
        ]);
    }

    $DB->insert('glpi_notifications_notificationtemplates', [
        'notifications_id'         => $notification_id,
        'mode'                     => Notification_NotificationTemplate::MODE_MAIL,
        'notificationtemplates_id' => $notificationtemplate_id,
    ]);
}
/** /Add new notification for AutoBump */

/** Add handling of source item of attached documents in notification */
$migration->addField("glpi_notifications", "attach_documents", "tinyint NOT NULL DEFAULT '-2'");

$attach_documents_value = $DB->request([
    'SELECT' => ['value'],
    'FROM'   => 'glpi_configs',
    'WHERE'  => [
        'context' => 'core',
        'name'    => 'attach_ticket_documents_to_mail',
    ],
])->current()['value'] ?? '0';
$migration->addField(
    "glpi_queuednotifications",
    "attach_documents",
    "tinyint NOT NULL DEFAULT '0'",
    [
        'update' => $attach_documents_value,
    ]
);

$migration->addField("glpi_queuednotifications", "itemtype_trigger", "varchar(255) DEFAULT NULL");
$migration->addField("glpi_queuednotifications", "items_id_trigger", "fkey");
$migration->addKey(
    "glpi_queuednotifications",
    ["itemtype_trigger", "items_id_trigger"],
    "item_trigger"
);
/** /Add handling of source item of attached documents in notification */

$migration->addField('glpi_notificationtargets', 'is_exclusion', 'boolean');
$migration->addKey('glpi_notificationtargets', ['is_exclusion'], 'is_exclusion');
