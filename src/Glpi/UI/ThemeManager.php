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

namespace Glpi\UI;

use RuntimeException;
use Toolbox;

use function Safe\file_get_contents;
use function Safe\glob;
use function Safe\preg_match;
use function Safe\scandir;

/**
 * Class that manages the core and custom themes (palettes).
 */
class ThemeManager
{
    public const DEFAULT_THEME = 'auror';
    public const CORE_THEME_ROOT = GLPI_ROOT . '/css/palettes/';

    private array $core_themes = [];
    private array $custom_themes = [];

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
        if ($this->core_themes === []) {
            $this->core_themes = [
                new Theme('aerialgreen', 'Aerial Green', false, false),
                new Theme('auror', 'Auror', false, false),
                new Theme('auror_dark', 'Dark Auror', true, false),
                new Theme('automn', 'Autumn', false, false),
                new Theme('classic', 'Classic', false, false),
                new Theme('clockworkorange', 'Clockwork Orange', false, false),
                new Theme('dark', 'Dark', false, false),
                new Theme('darker', 'Darker', true, false),
                new Theme('flood', 'Flood', false, false),
                new Theme('greenflat', 'Green Flat', false, false),
                new Theme('hipster', 'Hipster', false, false),
                new Theme('icecream', 'Ice Cream', false, false),
                new Theme('lightblue', 'Light Blue', false, false),
                new Theme('midnight', 'Midnight', true, false),
                new Theme('premiumred', 'Premium Red', false, false),
                new Theme('purplehaze', 'Purple Haze', false, false),
                new Theme('teclib', 'Teclib', false, false),
                new Theme('vintage', 'Vintage', false, false),
            ];
        }

        return $this->core_themes;
    }

    /**
     * Get the path to the custom themes directory
     * @return string
     */
    public function getCustomThemesDirectory(): string
    {
        return GLPI_THEMES_DIR;
    }

    /**
     * Get all themes present in the "palettes" directory that aren't core themes
     * @return Theme[]
     */
    public function getCustomThemes(): array
    {
        if ($this->custom_themes === []) {
            $custom_themes_dir = $this->getCustomThemesDirectory();
            $file_matches = [];
            // Cannot use GLOB_BRACE on some platforms (like the docker environment used for tests)
            $patterns = [
                '*.css',
                '*.scss',
            ];
            /**
             * PHP glob function calls libc glob which won't be aware of streams like vfsStream
             *
             * This workaround is needed for getting custom themes as the directory is mocked in tests
             * @param $directory
             * @param $filePattern
             * @return array
             */
            $streamSafeGlob = static function ($directory, $filePattern) {
                $files = scandir($directory);
                $found = [];

                foreach ($files as $filename) {
                    if (fnmatch($filePattern, $filename)) {
                        $found[] = $directory . '/' . $filename;
                    }
                }

                return $found;
            };
            foreach ($patterns as $pattern) {
                foreach (glob(self::CORE_THEME_ROOT . $pattern) as $file) {
                    $file_name = pathinfo($file, PATHINFO_FILENAME);
                    if (str_starts_with($file_name, '_')) {
                        continue;
                    }
                    $file_matches[$file_name] = $file;
                }
                foreach ($streamSafeGlob($custom_themes_dir, $pattern) as $file) {
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
                        Toolbox::deprecated('Custom theme file "' . $file_name . '" should be moved to ' . $custom_themes_dir);
                    }
                    // Guess dark mode based on if the file contains "$is-dark: true;"
                    $file_content = file_get_contents($file);
                    $is_dark = preg_match('/^\s*\$is-dark:\s*true;\s*$/im', $file_content) === 1;
                    $theme_name = ucfirst(str_replace('_', ' ', $file_name));
                    $this->custom_themes[] = new Theme($file_name, $theme_name, $is_dark, true);
                }
            }
        }
        return $this->custom_themes;
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
     * If the "glpipalette" value is not set, the default theme is used.
     * @return Theme
     */
    public function getCurrentTheme(): Theme
    {
        $current = $_SESSION['glpipalette'] ?? self::DEFAULT_THEME;
        $theme = $this->getTheme($current);
        if ($theme === null) {
            // Force trying to get the default theme
            $theme = $this->getTheme(self::DEFAULT_THEME);
        }
        // If the theme is still null, trigger an error
        if ($theme === null) {
            throw new RuntimeException('Theme "' . $current . '" not found');
        }
        return $theme;
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
