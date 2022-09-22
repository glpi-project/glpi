<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

namespace Glpi\UI;

final class ThemeManager
{

    public const DEFAULT_THEME = 'auror';
    public const CORE_THEME_ROOT = GLPI_ROOT . '/css/palettes/';

    public static function getInstance(): self
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        return $instance;
    }

    /**
     * Get all themes added by GLPI itself
     * @return Theme[]
     */
    public function getCoreThemes(): array
    {
        static $result = null;
        if ($result === null) {
            $result = [
                new Theme('aerialgreen', _x('theme', 'Aerial Green'), false, false),
                new Theme('auror', _x('theme', 'Auror'), false, false),
                new Theme('auror_dark', _x('theme', 'Dark Auror'), true, false),
                new Theme('automn', _x('theme', 'Autumn'), false, false),
                new Theme('classic', _x('theme', 'Classic'), false, false),
                new Theme('clockworkorange', _x('theme', 'Clockwork Orange'), false, false),
                new Theme('dark', _x('theme', 'Dark'), false, false),
                new Theme('darker', _x('theme', 'Darker'), true, false),
                new Theme('flood', _x('theme', 'Flood'), false, false),
                new Theme('greenflat', _x('theme', 'Green Flat'), false, false),
                new Theme('hipster', _x('theme', 'Hipster'), false, false),
                new Theme('icecream', _x('theme', 'Ice Cream'), false, false),
                new Theme('lightblue', _x('theme', 'Light Blue'), false, false),
                new Theme('midnight', _x('theme', 'Midnight'), true, false),
                new Theme('premiumred', _x('theme', 'Premium Red'), false, false),
                new Theme('purplehaze', _x('theme', 'Purple Haze'), false, false),
                new Theme('teclib', _x('theme', 'Teclib'), false, false),
                new Theme('vintage', _x('theme', 'Vintage'), false, false),
            ];
        }

        return $result;
    }

    /**
     * Get all themes present in the "palettes" directory that aren't core themes
     * @return Theme[]
     */
    public function getCustomThemes(): array
    {
        static $custom_themes = null;

        if ($custom_themes === null) {
            $custom_themes = [];

            $file_matches = [];
            // Cannot use GLOB_BRACE on some platforms (like the docker environment used for tests)
            $patterns = [
                '*.css',
                '*.scss'
            ];
            foreach ($patterns as $pattern) {
                foreach (glob(self::CORE_THEME_ROOT . $pattern) as $file) {
                    $file_name = pathinfo($file, PATHINFO_FILENAME);
                    if (str_starts_with($file_name, '_')) {
                        continue;
                    }
                    $file_matches[$file_name] = $file;
                }
                foreach (glob(GLPI_THEMES_DIR . '/' . $pattern) as $file) {
                    $file_name = pathinfo($file, PATHINFO_FILENAME);
                    if (str_starts_with($file_name, '_')) {
                        continue;
                    }
                    $file_matches[$file_name] = $file;
                }
            }

            $core_themes = $this->getCoreThemes();
            $core_keys = [];
            foreach ($core_themes as $core_theme) {
                $core_keys[] = $core_theme->getKey();
            }

            foreach ($file_matches as $file_name => $file) {
                if (!in_array($file_name, $core_keys, true)) {
                    if (str_contains($file, self::CORE_THEME_ROOT)) {
                        \Toolbox::deprecated('Custom theme file "' . $file_name . '" should be moved to ' . GLPI_THEMES_DIR);
                    }
                    // Guess dark mode based on if the file contains "$is-dark: true;"
                    $file_content = file_get_contents($file);
                    $is_dark = preg_match('/^\s*\$is-dark:\s*true;\s*$/im', $file_content);
                    $theme_name = ucfirst(str_replace('_', ' ', $file_name));
                    $custom_themes[] = new Theme($file_name, $theme_name, $is_dark, true);
                }
            }
        }
        return $custom_themes;
    }

    /**
     * Get all available themes
     * @return Theme[]
     */
    public function getAllThemes(): array
    {
        $core_themes = $this->getCoreThemes();
        $custom_themes = $this->getCustomThemes();
        return array_merge($core_themes, $custom_themes);
    }

    /**
     * Get the Theme object for the current $_SESSION['glpipalette'] value.
     *
     * If the "glpipalette" value is not set, the defautl theme is used.
     * @return Theme|null
     */
    public function getCurrentTheme(): ?Theme
    {
        $current = $_SESSION['glpipalette'] ?? self::DEFAULT_THEME;
        return $this->getTheme($current);
    }

    /**
     * Get the Theme object for the given key.
     * @param string $key
     * @return Theme|null
     */
    public function getTheme(string $key): ?Theme
    {
        $themes = $this->getAllThemes();
        foreach ($themes as $theme) {
            if ($theme->getKey() === $key) {
                return $theme;
            }
        }
        return null;
    }
}
