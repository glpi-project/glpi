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

use Glpi\Toolbox\Sanitizer;

/**
 * @var DB $DB
 * @var Migration $migration
 */

/* BEGIN: Fixes default notification targets */
$itil_types = ['Ticket', 'Change', 'Problem'];
$iterator = $DB->request([
    'SELECT' => ['id', 'event'],
    'FROM'   => 'glpi_notifications',
    'WHERE'  => [
        'itemtype' => $itil_types,
    ],
]);

foreach ($iterator as $notification) {
    $target_iterator = $DB->request([
        'SELECT' => ['id', 'items_id'],
        'FROM'   => 'glpi_notificationtargets',
        'WHERE'  => [
            'notifications_id' => $notification['id']
        ],
    ]);
    $targets = [];
    foreach ($target_iterator as $target) {
        $targets[$target['id']] = $target['items_id'];
    }
    $removed_item_group = false;
    $found_assigned_group = false;
    foreach ($targets as $target_id => $items_id) {
        if (
            $items_id === Notification::ITEM_TECH_GROUP_IN_CHARGE
            || $items_id === Notification::ITEM_TECH_IN_CHARGE
            || $items_id === Notification::ITEM_USER
        ) {
            $DB->deleteOrDie('glpi_notificationtargets', [
                'id' => $target_id,
            ]);
            if ($items_id === Notification::ITEM_TECH_GROUP_IN_CHARGE) {
                $removed_item_group = true;
            }
        }
        if ($items_id === Notification::ASSIGN_GROUP) {
            $found_assigned_group = true;
        }
    }
    if ($notification['event'] === 'assign_group' && $removed_item_group && !$found_assigned_group) {
        $DB->insertOrDie('glpi_notificationtargets', [
            'notifications_id'  => $notification['id'],
            'type'              => Notification::USER_TYPE,
            'items_id'          => Notification::ASSIGN_GROUP,
        ]);
    }
}
/* END: Fixes default notification targets */

/* BEGIN: Fixes notification templates encoding (see #10295) */
$template_iterator = $DB->request([
    'SELECT' => ['id', 'content_html'],
    'FROM'   => 'glpi_notificationtemplatetranslations',
]);
foreach ($template_iterator as $template_data) {
    $content_html = Sanitizer::decodeHtmlSpecialChars($template_data['content_html']);

    if (str_contains($content_html, '&lt;p&gt;') && str_contains($content_html, '&lt;/p&gt;')) {
        // HTML still contains encoded HTML. It can be result of 2 different initial states
        // 1. DB content may contains be partially encoded (contains both encoded and raw HTML).
        //    In this case, Sanitizer::decodeHtmlSpecialChars() will have no effect, as it is not considered as encoded,
        //    and so `&lt;p&gt;` and `&lt;/p&gt;` will still be present.
        // 2. A template partially encoded has been saved from UI, resulting in presence of `&#38;lt;p&#38;gt;` and `&#38;lt;/p&#38;gt;`.
        //    Sanitizer::decodeHtmlSpecialChars() will transform these to `&lt;p&gt;` and `&lt;/p&gt;`.
        //
        // In both cases, remaining encoded HTML has to be decoded, so it will be then possible to reencode the whole
        // content without having some characters that are double-encoded.
        $content_html = str_replace(['&lt;', '&gt;'], ['<', '>'], $content_html);

        $migration->addPostQuery(
            $DB->buildUpdate(
                'glpi_notificationtemplatetranslations',
                [
                    'content_html' => Sanitizer::sanitize($content_html),
                ],
                [
                    'id' => $template_data['id'],
                ]
            )
        );
    }
}
/* END: Fixes notification templates encoding */
