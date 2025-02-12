<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
     * @return string
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

    /**
     * Checks whether an URL can be considered as a valid GLPI relative URL.
     *
     * @param string $url
     *
     * @return bool
     */
    public static function isGLPIRelativeUrl(string $url): bool
    {
        if ($url === '') {
            return false;
        }

        if (self::sanitizeURL($url) !== $url) {
            return false;
        }

        $parsed_url = parse_url($url);

        if (
            // URL is not parsable, it is invalid.
            $parsed_url === false
            // A relative URL should not contain a `scheme` or a `host` token
            || array_key_exists('scheme', $parsed_url)
            || array_key_exists('host', $parsed_url)
            // A relative URL should contain a `path` token.
            || !array_key_exists('path', $parsed_url)
            // GLPI URLs are not supposed to contain special chars.
            || preg_match('#[^a-z0-9_/\.-]#i', $parsed_url['path']) === 1
            // // The path refers to an hidden resource (name starts with `/.`), or contains a `/..` that may lead outside the GLPI tree.
            || preg_match('#/\.#', $parsed_url['path']) === 1
        ) {
            return false;
        }

        return true;
    }
}
