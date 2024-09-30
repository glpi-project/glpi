<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

final class IllustrationManager
{
    public const DEFAULT_ICON = "request-service.svg";

    /**
     * @param int $size Height and width (px)
     */
    public function render(string $filename, int $size = 100): string
    {
        $svg_content = $this->getSvgContent($filename);
        $svg_content = $this->replaceColorsByVariables($svg_content);
        $svg_content = $this->setSize($svg_content, $size);

        return $svg_content;
    }

    private function getSvgContent(string $filename): string
    {
        $svg_content = file_get_contents(GLPI_ROOT . "/pics/illustration/$filename");
        if (!$svg_content) {
            trigger_error("Unknown illustration: $filename", E_USER_WARNING);

            // Can't fallback to default icon if it is already the one being
            // requeted.
            if ($filename == self::DEFAULT_ICON) {
                return "";
            }

            return $this->getSvgContent(self::DEFAULT_ICON);
        }

        return $svg_content;
    }

    private function replaceColorsByVariables(string $svg_content): string
    {
        $mapping = [
            'rgb(71,71,71)'    => "--glpi-mainmenu-bg",
            'rgb(186,186,186)' => "--glpi-helpdesk-header",
            'rgb(235,235,235)' => "--tblr-primary",
        ];

        foreach ($mapping as $color => $variable) {
            $svg_content = str_replace($color, "var($variable)", $svg_content);
        }

        return $svg_content;
    }

    private function setSize(string $svg_content, int $size): string
    {
        $svg_content = str_replace(
            'width="100%"',
            'width="' . $size . 'px"',
            $svg_content
        );
        $svg_content = str_replace(
            'height="100%"',
            'height="' . $size . 'px"',
            $svg_content
        );

        return $svg_content;
    }
}
