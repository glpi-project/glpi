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
use function Safe\preg_replace;

$links = $DB->request([
    'SELECT' => ['id', 'link', 'data'],
    'FROM'   => 'glpi_links',
]);

// Replace custom tags format with twig variable format
$simple_tag_pattern = '/\[([A-Z_]+)\]/';
$field_tag_pattern = '/\[FIELD:([a-z_]+)\]/';

foreach ($links as $link) {
    $new_link = preg_replace($simple_tag_pattern, '{{ $1 }}', $link['link']);
    $new_link = preg_replace($field_tag_pattern, '{{ item.$1 }}', $new_link);
    $new_data = preg_replace($simple_tag_pattern, '{{ $1 }}', $link['data']);
    $new_data = preg_replace($field_tag_pattern, '{{ item.$1 }}', $new_data);
    $DB->update('glpi_links', [
        'id'   => $link['id'],
        'link' => $new_link,
        'data' => $new_data,
    ], [
        'id' => $link['id'],
    ]);
}
