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

use function Safe\preg_match;
use function Safe\preg_replace;

class VersionParser
{
    /**
     * Pattern used to detect/extract unstable flag.
     * @var string
     */
    private const UNSTABLE_FLAG_PATTERN = '(dev|alpha\d*|beta\d*|rc\d*)';

    /**
     * Normalize version number.
     *
     * @param string $version
     * @param bool $keep_stability_flag
     *
     * @return string
     */
    public static function getNormalizedVersion(string $version, bool $keep_stability_flag = true): string
    {
        $version_pattern = implode(
            '',
            [
                '/^',
                '(?<major>\d+)', // Major release number, always present
                '\.(?<minor>\d+)', // Minor release number, always present
                '(\.(?<bugfix>\d+))?', // Bugfix number, not always present (e.g. GLPI 9.2)
                '(\.(?<tag_fail>\d+))?', // Redo tag operation number, rarely present (e.g. GLPI 9.4.1.1)
                '(?<stability_flag>-' . self::UNSTABLE_FLAG_PATTERN . ')?', // Stability flag, optional
                '$/',
            ]
        );
        $version_matches = [];
        if (preg_match($version_pattern, $version, $version_matches) === 1) {
            $version = $version_matches['major']
            . '.' . $version_matches['minor']
            . '.' . ($version_matches['bugfix'] ?? 0)
            . ($keep_stability_flag && array_key_exists('stability_flag', $version_matches) ? $version_matches['stability_flag'] : '');
        }

        return $version;
    }

    /**
     * Get major version number (e.g. '9').
     */
    public static function getMajorVersion(string $version): string
    {
        $normalized = self::getNormalizedVersion($version, false);

        return preg_replace('/^(\d+)[^d].+$/', '$1', $normalized);
    }

    /**
     * Get intermediate version number (e.g. '9.5').
     */
    public static function getIntermediateVersion(string $version): string
    {
        $normalized = self::getNormalizedVersion($version, false);

        return preg_replace('/^(\d+\.\d+)[^d].+$/', '$1', $normalized);
    }

    /**
     * Check if given version is a stable release (i.e. does not contain a stability flag referring to unstable state).
     *
     * @param string $version
     *
     * @return bool
     */
    public static function isStableRelease(string $version): bool
    {
        return preg_match('/-' . self::UNSTABLE_FLAG_PATTERN . '$/', $version) !== 1;
    }

    /**
     * Check if given version is a dev version (i.e. ends with `-dev`).
     *
     * @param string $version
     *
     * @return bool
     */
    public static function isDevVersion(string $version): bool
    {
        return preg_match('/-dev$/', $version) === 1;
    }
}
