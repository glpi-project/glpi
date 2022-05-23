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

/**
 * Class that represents a theme/palette.
 */
final class Theme
{
    private string $key;
    private string $name;
    private bool $is_dark;
    private bool $is_custom;

    public const DEFAULT_THEME = 'auror';
    public const CORE_THEME_ROOT = GLPI_ROOT . '/css/palettes/';

    public function __construct(string $key, string $name, bool $is_dark, bool $is_custom)
    {
        $this->key = $key;
        $this->name = $name;
        $this->is_dark = $is_dark;
        $this->is_custom = $is_custom;
    }

    /**
     * Get all themes added by GLPI itself
     * @return Theme[]
     */
    public static function getCoreThemes(): array
    {
        static $result = null;
        if ($result === null) {
            $result = [
                new self('aerialgreen', _x('theme', 'Aerial Green'), false, false),
                new self('auror', _x('theme', 'Auror'), false, false),
                new self('auror_dark', _x('theme', 'Dark Auror'), true, false),
                new self('automn', _x('theme', 'Autumn'), false, false),
                new self('classic', _x('theme', 'Classic'), false, false),
                new self('clockworkorange', _x('theme', 'Clockwork Orange'), false, false),
                new self('dark', _x('theme', 'Dark'), false, false),
                new self('darker', _x('theme', 'Darker'), true, false),
                new self('flood', _x('theme', 'Flood'), false, false),
                new self('greenflat', _x('theme', 'Green Flat'), false, false),
                new self('hipster', _x('theme', 'Hipster'), false, false),
                new self('icecream', _x('theme', 'Ice Cream'), false, false),
                new self('lightblue', _x('theme', 'Light Blue'), false, false),
                new self('midnight', _x('theme', 'Midnight'), true, false),
                new self('premiumred', _x('theme', 'Premium Red'), false, false),
                new self('purplehaze', _x('theme', 'Purple Haze'), false, false),
                new self('teclib', _x('theme', 'Teclib'), false, false),
                new self('vintage', _x('theme', 'Vintage'), false, false),
            ];
        }

        return $result;
    }

    /**
     * Get all themes present in the "palettes" directory that aren't core themes
     * @return Theme[]
     */
    public static function getCustomThemes(): array
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

            $core_themes = self::getCoreThemes();
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
                    $custom_themes[] = new self($file_name, $theme_name, $is_dark, true);
                }
            }
        }
        return $custom_themes;
    }

    /**
     * Get all available themes
     * @return Theme[]
     */
    public static function getAllThemes(): array
    {
        $core_themes = self::getCoreThemes();
        $custom_themes = self::getCustomThemes();
        return array_merge($core_themes, $custom_themes);
    }

    /**
     * Get the Theme object for the current $_SESSION['glpipalette'] value.
     *
     * If the "glpipalette" value is not set, the defautl theme is used.
     * @return Theme|null
     */
    public static function getCurrentTheme(): ?Theme
    {
        $current = $_SESSION['glpipalette'] ?? self::DEFAULT_THEME;
        return self::getTheme($current);
    }

    /**
     * Get the Theme object for the given key.
     * @param string $key
     * @return Theme|null
     */
    public static function getTheme(string $key): ?Theme
    {
        $themes = self::getAllThemes();
        foreach ($themes as $theme) {
            if ($theme->getKey() === $key) {
                return $theme;
            }
        }
        return null;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return bool
     * @used-by "templates/layout/parts/head.html.twig"
     */
    public function isDarkTheme(): bool
    {
        return $this->is_dark;
    }

    public function isCustomTheme(): bool
    {
        return $this->is_custom;
    }

    public function getPreviewPath(): string
    {
        return self::CORE_THEME_ROOT . '/previews/' . $this->getKey() . '.png';
    }
}
