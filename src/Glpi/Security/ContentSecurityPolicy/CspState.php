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
use Toolbox;

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
            self::$cached_state = self::getIncompatiblePlugins() === [];
            self::$cached_plugins_key = $plugins_key;
        }

        return self::$cached_state;
    }

    /**
     * Get the keys of the active plugins that are not compatible with the CSP.
     *
     * @return array<string>
     */
    public static function getIncompatiblePlugins(): array
    {
        return \array_values(\array_filter(
            Plugin::getPlugins(),
            static fn(string $plugin_key): bool => !self::isPluginCompatible($plugin_key)
        ));
    }

    /**
     * Get the keys of the given plugins whose activation would disable the CSP,
     * i.e. the plugins that are not compatible with it, if the CSP is currently enabled.
     *
     * @param array<string> $plugin_keys
     *
     * @return array<string>
     */
    public static function getPluginsThatWouldDisableCsp(array $plugin_keys): array
    {
        if (!self::isEnabled()) {
            return [];
        }

        return \array_values(\array_filter(
            $plugin_keys,
            static fn(string $plugin_key): bool => !self::isPluginCompatible($plugin_key)
        ));
    }

    /**
     * Get the message used to ask for a confirmation before activating the given plugins,
     * or null if their activation would not disable the CSP.
     * The returned message is plain text, it must be escaped before being used in HTML.
     *
     * @param array<string> $plugin_keys
     */
    public static function getActivationConfirmationMessage(array $plugin_keys): ?string
    {
        $plugins = self::getPluginsThatWouldDisableCsp($plugin_keys);
        if ($plugins === []) {
            return null;
        }

        return \sprintf(
            _n(
                '%s is not compatible with the Content Security Policy (CSP), which protects GLPI against cross-site scripting (XSS) attacks. Enabling it will disable the CSP. Do you want to continue?',
                'The following plugins are not compatible with the Content Security Policy (CSP), which protects GLPI against cross-site scripting (XSS) attacks: %s. Enabling them will disable the CSP. Do you want to continue?',
                \count($plugins)
            ),
            \implode(', ', \array_map(self::getPluginName(...), $plugins))
        );
    }

    private static function isPluginCompatible(string $plugin_key): bool
    {
        return (self::getPluginInformation($plugin_key)['csp_compliant'] ?? null) === true;
    }

    /**
     * Get the plugin name, without HTML tags.
     */
    private static function getPluginName(string $plugin_key): string
    {
        $name = self::getPluginInformation($plugin_key)['name'] ?? null;

        return (\is_string($name) ? Toolbox::stripTags($name) : '') ?: $plugin_key;
    }

    /**
     * Get the plugin information, loading its setup file if the plugin is not loaded yet.
     *
     * @return array<string, mixed>
     */
    private static function getPluginInformation(string $plugin_key): array
    {
        if (\function_exists('plugin_version_' . \strtolower($plugin_key))) {
            $information = Plugin::getInfo($plugin_key);
        } else {
            $information = (new Plugin())->getInformationsFromDirectory($plugin_key, false);
        }

        return \is_array($information) ? $information : [];
    }
}
