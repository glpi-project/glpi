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

namespace Glpi\Application\View\Extension;

use Toolbox;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

use function Safe\filesize;

/**
 * @since 10.0.0
 */
class DocumentExtension extends AbstractExtension
{
    /**
     * Static cache for user defined files extensions icons.
     */
    private static $extensionIcon = null;

    public function getFilters(): array
    {
        return [
            new TwigFilter('document_icon', [$this, 'getDocumentIcon']),
            new TwigFilter('document_size', [$this, 'getDocumentSize']),
        ];
    }

    /**
     * Returns icon URL for given document filename.
     *
     * @param string $filename
     *
     * @return string
     */
    public function getDocumentIcon(string $filename): string
    {
        global $CFG_GLPI, $DB;

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (self::$extensionIcon === null) {
            $iterator = $DB->request([
                'SELECT' => [
                    'ext',
                    'icon',
                ],
                'FROM' => 'glpi_documenttypes',
                'WHERE' => [
                    'icon' => ['<>', ''],
                ],
            ]);
            foreach ($iterator as $result) {
                self::$extensionIcon[$result['ext']] = $result['icon'];
            }
        }

        $defaultIcon = '/pics/timeline/file.png';
        $icon = $defaultIcon;

        if (isset(self::$extensionIcon[$extension])) {
            $icon = '/pics/icones/' . self::$extensionIcon[$extension];
        }

        return $CFG_GLPI['root_doc'] . (file_exists(GLPI_ROOT . $icon) ? $icon : $defaultIcon);
    }

    /**
     * Returns human readable size of file matching given path (relative to GLPI_DOC_DIR).
     *
     * @param string $filepath
     *
     * @return null|string
     */
    public function getDocumentSize(string $filepath): ?string
    {
        $fullpath = GLPI_DOC_DIR . '/' . $filepath;

        return is_readable($fullpath) ? Toolbox::getSize(filesize($fullpath)) : null;
    }
}
