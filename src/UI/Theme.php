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

namespace Glpi\UI;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Class that represents a theme/palette.
 */
final class Theme
{
    private string $key;
    private string $name;
    private bool $is_dark;
    private bool $is_custom;

    public function __construct(string $key, string $name, bool $is_dark, bool $is_custom)
    {
        $this->key = $key;
        $this->name = $name;
        $this->is_dark = $is_dark;
        $this->is_custom = $is_custom;
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

    private function getBaseDir(bool $relative = true): string
    {
        $path = $this->is_custom ? ThemeManager::getInstance()->getCustomThemesDirectory() : ThemeManager::CORE_THEME_ROOT;
        if ($relative) {
            $filesystem = new Filesystem();
            $path = $filesystem->makePathRelative($path, GLPI_ROOT);
        }
        return $path;
    }

    /**
     * Return preview file path, if any.
     *
     * @param bool $relative
     *
     * @return string|null
     */
    public function getPreviewPath(bool $relative = true): ?string
    {
        if (!file_exists($this->getBaseDir(false) . '/previews/' . $this->getKey() . '.png')) {
            return null;
        }

        return $this->getBaseDir($relative) . '/previews/' . $this->getKey() . '.png';
    }

    public function getPath(bool $relative = true): string
    {
        return $this->getBaseDir($relative) . '/' . $this->getKey() . '.scss';
    }
}
