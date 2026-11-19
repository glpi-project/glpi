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

namespace Glpi\Security\ContentSecurityPolicy;

use Plugin;

/**
 * Indicates whether the Content-Security-Policy is enforced.
 *
 * The CSP is enabled unless an active plugin does not declare itself as compatible
 * with it, using the `'csp_compliant' => true` entry of its `plugin_version_*()` function.
 */
final class CspState
{
    /**
     * Active plugins list used to compute the cached state.
     */
    private static ?string $cached_plugins_key = null;

    /**
     * Cached state, valid as long as the active plugins list does not change.
     */
    private static bool $cached_state = true;

    public static function isEnabled(): bool
    {
        $plugins = Plugin::getPlugins();
        $plugins_key = \implode(',', $plugins);

        if (self::$cached_plugins_key !== $plugins_key) {
            self::$cached_state = self::computeState($plugins);
            self::$cached_plugins_key = $plugins_key;
        }

        return self::$cached_state;
    }

    /**
     * @param array<string> $plugins Active plugins keys
     */
    private static function computeState(array $plugins): bool
    {
        foreach ($plugins as $plugin_key) {
            if (Plugin::getInfo($plugin_key, 'csp_compliant') !== true) {
                return false;
            }
        }

        return true;
    }
}
