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

use Glpi\Toolbox\SanitizedStringsDecoder;

/**
 * @var DBmysql $DB
 * @var Migration $migration
 */

/* BEGIN: Fix HTML-escaped default notification templates (see #23464) */

// This instance is only affected if unsanitization-on-read is disabled,
// i.e. it was installed fresh from GLPI >= 11.0.5 (see #22658). Instances
// upgraded from an older version keep having their data unsanitized on
// read, so their (still HTML-encoded) templates already render correctly
// and do not need to be touched here.
$must_unsanitize_db_data = $DB->request([
    'FROM'  => 'glpi_configs',
    'WHERE' => ['context' => 'core', 'name' => 'must_unsanitize_db_data'],
])->current()['value'] ?? '1';

if ((int) $must_unsanitize_db_data === 0) {
    $decoder = new SanitizedStringsDecoder();

    $translations_iterator = $DB->request(['FROM' => 'glpi_notificationtemplatetranslations']);
    foreach ($translations_iterator as $translation) {
        $updated_fields = [];
        foreach (['content_html', 'content_text'] as $field) {
            if (empty($translation[$field])) {
                continue;
            }
            $decoded = $decoder->decodeHtmlSpecialChars($translation[$field]);
            // `decodeHtmlSpecialChars()` only alters values that look fully
            // HTML-encoded; a template customized through the rich text
            // editor (i.e. containing actual HTML markup) is left untouched.
            if ($decoded !== $translation[$field]) {
                $updated_fields[$field] = $decoded;
            }
        }
        if ($updated_fields !== []) {
            $migration->addPostQuery(
                $DB->buildUpdate(
                    'glpi_notificationtemplatetranslations',
                    $updated_fields,
                    ['id' => $translation['id']]
                )
            );
        }
    }
}
/* END: Fix HTML-escaped default notification templates */
