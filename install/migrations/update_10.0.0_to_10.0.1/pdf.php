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
$fonts_mapping = [
    // xbriyaz => Arabic fonts
    'aealarabiya' => ['xbriyaz'],
    // sun-exta => CJK fonts
    'cid0cs' => ['sun-exta'],
    // TTF font => corresponding Adobe embedded fonts
    'pdfacourier' => ['courier'],
    'pdfahelvetica' => ['helvetica'],
    'pdfasymbol' => ['symbol'],
    'pdfatimes' => ['times'],
    'pdfazapfdingbats' => ['zapfdingbats'],
    // Other unsupported fonts
    'dejavusansextralight' => ['dejavusans'],
];

foreach ($fonts_mapping as $new_value => $old_values) {
    $migration->addPostQuery(
        $DB->buildUpdate(
            'glpi_configs',
            [
                'value' => $new_value,
            ],
            [
                'name'  => 'pdffont',
                'value' => $old_values,
            ]
        )
    );
    $migration->addPostQuery(
        $DB->buildUpdate(
            'glpi_users',
            [
                'pdffont' => $new_value,
            ],
            [
                'pdffont' => $old_values,
            ]
        )
    );
}
