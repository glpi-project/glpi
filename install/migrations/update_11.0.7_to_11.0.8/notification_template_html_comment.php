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

/**
 * @var DBmysql $DB
 * @var Migration $migration
 */

// Remove legacy HTML comments containing CSS rules from the beginning of notification
// template HTML content. These comments were never rendered as CSS (they were inside
// HTML comment tags) but some email clients display their content as visible text.
$iterator = $DB->request([
    'SELECT' => ['id', 'content_html'],
    'FROM'   => 'glpi_notificationtemplatetranslations',
    'WHERE'  => ['content_html' => ['LIKE', '&lt;!--%--&gt;%']],
]);

foreach ($iterator as $row) {
    $content = $row['content_html'];
    // Strip a leading HTML comment (stored as HTML-encoded entities)
    $cleaned = preg_replace('/^&lt;!--.*?--&gt;\s*/s', '', $content);
    if ($cleaned !== $content) {
        $DB->update(
            'glpi_notificationtemplatetranslations',
            ['content_html' => $cleaned],
            ['id' => $row['id']]
        );
    }
}
