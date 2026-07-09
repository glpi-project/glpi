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

/* BEGIN: Fix HTML-escaped default notification templates (see #23464) */

// This instance is only affected if unsanitization-on-read is disabled,
// i.e. it was installed fresh from GLPI >= 11.0.5 (see #22658). Instances
// upgraded from an older version keep having their data unsanitized on
// read, so their (still HTML-encoded) default templates already render
// correctly and must not be touched here.
$must_unsanitize_db_data = $DB->request([
    'FROM'  => 'glpi_configs',
    'WHERE' => ['context' => 'core', 'name' => 'must_unsanitize_db_data'],
])->current()['value'] ?? '1';

if ((int) $must_unsanitize_db_data === 0) {
    $fixes = require __DIR__ . '/notification_templates_encoding.data.php';

    foreach ($fixes as $fix) {
        // Exact match only: a template whose content was customized by the
        // administrator no longer matches the broken default value, and is
        // left untouched.
        $migration->addPostQuery(
            $DB->buildUpdate(
                'glpi_notificationtemplatetranslations',
                [
                    $fix['field'] => $fix['new'],
                ],
                [
                    'notificationtemplates_id' => $fix['notificationtemplates_id'],
                    'language'                 => $fix['language'],
                    $fix['field']              => $fix['old'],
                ]
            )
        );
    }
}
/* END: Fix HTML-escaped default notification templates */
