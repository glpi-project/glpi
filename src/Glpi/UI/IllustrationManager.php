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

use DirectoryIterator;

final class IllustrationManager
{
    public function __construct(
        private string $illustration_dir = GLPI_ROOT . "/resources/illustration"
    ) {
    }

    /**
     * @param int $size Height and width (px)
     */
    public function render(string $filename, int $size = 100): string
    {
        if (!$this->isValidIllustrationName($filename)) {
            return "";
        }

        $svg_content = $this->getSvgContent($filename);
        $svg_content = $this->replaceColorsByVariables($svg_content);
        $svg_content = $this->adjustSize($svg_content, $size);

        return $svg_content;
    }

    /** @return string[] */
    public function getAllIllustrationsNames(): array
    {
        $illustrations = [];
        $illustrations_files = new DirectoryIterator($this->getIllustrationDir());
        foreach ($illustrations_files as $file) {
            /** @var \SplFileInfo $file */
            if ($file->isDir()) {
                continue;
            }

            if ($file->getExtension() !== 'svg') {
                continue;
            }

            $illustrations[] = $file->getFilename();
        }

        return $illustrations;
    }

    private function isValidIllustrationName(string $filename): bool
    {
        $full_path = $this->getIllustrationDir() . "/$filename";

        return
            file_exists($full_path)
            && is_file($full_path)
            && is_readable($full_path)
            && str_ends_with($full_path, '.svg')
            // Make sure malicious users are not able to read files outside the illustration directory
            && realpath($full_path) == $full_path
        ;
    }

    private function getIllustrationDir(): string
    {
        return $this->illustration_dir;
    }

    private function getSvgContent(string $filename): string
    {
        $svg_content = file_get_contents($this->getIllustrationDir() . "/$filename");
        if (!$svg_content) {
            return "";
        }

        return $svg_content;
    }

    private function replaceColorsByVariables(string $svg_content): string
    {
        $mapping = [
            'rgb(71,71,71)'    => "--glpi-mainmenu-bg",
            '#474747'          => "--glpi-mainmenu-bg",
            'rgb(186,186,186)' => "--glpi-helpdesk-header",
            '#BABABA'          => "--glpi-helpdesk-header",
            'rgb(235,235,235)' => "--tblr-primary",
            '#EBEBEB'          => "--tblr-primary",
        ];

        foreach ($mapping as $color => $variable) {
            $svg_content = str_replace($color, "var($variable)", $svg_content);
        }

        return $svg_content;
    }

    private function adjustSize(string $svg_content, int $size): string
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
