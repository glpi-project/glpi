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

namespace Glpi\Toolbox;

final class URL
{
    /**
     * Sanitize URL to prevent XSS.
     * /!\ This method only ensure that links are corresponding to a valid URL
     * (i.e. an absolute URL with a scheme or something that correspond to a path).
     * To be sure that no XSS is possible, value have to be HTML encoded when it is printed in a HTML page.
     *
     * @param null|string $url
     *
     * @return string|null
     */
    final public static function sanitizeURL(?string $url): string
    {
        if ($url === null) {
            return '';
        }

        $url = trim($url);

        $url_begin_patterns = [
            // scheme followed by `//` and a hostname (absolute URL)
            '[a-z]+:\/\/.+',
            // `/` that corresponds to either start of a network path (e.g. `//host/path/to/file`)
            // or a relative URL (e.g. `/`, `/path/to/page`, or `//anothersite.org/`)
            '\/',
        ];
        $url_pattern = '/^(' . implode('|', $url_begin_patterns) . ')/i';
        if (preg_match($url_pattern, $url) !== 1) {
            return '';
        }

        $js_pattern = '/^javascript:/i';
        if (preg_match($js_pattern, $url)) {
            return '';
        }

        return $url;
    }
}
