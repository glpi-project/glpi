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

/**
 * @var DBmysql $DB
 * @var Migration $migration
 */

/** KnowbaseItem notifications */
if (countElementsInTable('glpi_notifications', ['itemtype' => 'KnowbaseItem']) === 0) {
    $DB->insert(
        'glpi_notificationtemplates',
        [
            'name'            => 'Knowledge base item',
            'itemtype'        => 'KnowbaseItem',
        ]
    );
    $notificationtemplate_id = $DB->insertId();

    $DB->insert(
        'glpi_notificationtemplatetranslations',
        [
            'notificationtemplates_id' => $notificationtemplate_id,
            'language'                 => '',
            'subject'                  => '##knowbaseitem.action## - ##knowbaseitem.subject##',
            'content_text'             => <<<PLAINTEXT
            ##lang.knowbaseitem.url## : ##knowbaseitem.url##

            ##lang.knowbaseitem.subject## : ##knowbaseitem.subject##

            ##lang.knowbaseitem.content## : ##knowbaseitem.content##

            ##lang.knowbaseitem.categories## : ##knowbaseitem.categories##
            ##lang.knowbaseitem.is_faq## ##knowbaseitem.is_faq##
            ##lang.knowbaseitem.begin_date## : ##knowbaseitem.begin_date##
            ##lang.knowbaseitem.end_date## : ##knowbaseitem.end_date##

            ##lang.knowbaseitem.numberofdocuments## : ##knowbaseitem.numberofdocuments##

            ##FOREACHdocuments##
                ##lang.document.downloadurl## : ##document.downloadurl##
                ##lang.document.filename## : ##document.filename##
                ##lang.document.heading## : ##document.heading##
                ##lang.document.id## : ##document.id##
                ##lang.document.name## : ##document.name##
                ##lang.document.url## : ##document.url##
                ##lang.document.weblink## : ##document.weblink##
            ##ENDFOREACHdocuments##

            ##FOREACHtargets##
                ##lang.target.itemtype## : ##target.type##
                ##lang.target.name## : ##target.name##
                ##lang.target.url## : ##target.url##
            ##ENDFOREACHtargets##
            PLAINTEXT,
            'content_html'            => <<<HTML
            &lt;p&gt;##lang.knowbaseitem.subject## : ##knowbaseitem.subject##
            &lt;br&gt;##lang.knowbaseitem.categories## : ##knowbaseitem.categories##
            &lt;br&gt;##lang.knowbaseitem.is_faq## ##knowbaseitem.is_faq##
            &lt;br&gt;##lang.knowbaseitem.begin_date## : ##knowbaseitem.begin_date##
            &lt;br&gt;##lang.knowbaseitem.end_date## : ##knowbaseitem.end_date##
            &lt;br&gt;##lang.knowbaseitem.numberofdocuments## : ##knowbaseitem.numberofdocuments##&lt;/p&gt;
            ##FOREACHdocuments## &lt;p&gt;##lang.document.downloadurl## : ##document.downloadurl##
            &lt;br&gt;##lang.document.filename## : ##document.filename##
            &lt;br&gt;##lang.document.heading## : ##document.heading##
            &lt;br&gt;##lang.document.id## : ##document.id##
            &lt;br&gt;##lang.document.name## : ##document.name##
            &lt;br&gt;##lang.document.url## : ##document.url##
            &lt;br&gt;##lang.document.weblink## : ##document.weblink##&lt;/p&gt; ##ENDFOREACHdocuments## &lt;br&gt;
            ##FOREACHtargets## &lt;p&gt;##lang.target.itemtype## : ##target.type##
            &lt;br&gt;##lang.target.name## : ##target.name##
            &lt;br&gt;##lang.target.url## : ##target.url## &lt;/p&gt;##ENDFOREACHtargets##
            HTML,
        ]
    );

    $notifications_data = [
        [
            'event' => 'new',
            'name'  => 'New knowledge base item',
        ],
        [
            'event' => 'update',
            'name'  => 'Updating knowledge base item',
        ],
        [
            'event' => 'delete',
            'name'  => 'Delete knowledge base item',
        ],
    ];

    foreach ($notifications_data as $notification_data) {
        $DB->insert(
            'glpi_notifications',
            [
                'name'            => $notification_data['name'],
                'entities_id'     => 0,
                'itemtype'        => 'KnowbaseItem',
                'event'           => $notification_data['event'],
                'comment'         => null,
                'is_recursive'    => 1,
                'is_active'       => 0,
                'date_creation'   => null,
                'date_mod'        => null,
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
    }
}
