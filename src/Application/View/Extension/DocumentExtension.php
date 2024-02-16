<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

/**
 * @since 10.0.0
 */
class DocumentExtension extends AbstractExtension
{
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
        /** @var array $CFG_GLPI */
        /** @var \DBmysql $DB */
        global $CFG_GLPI, $DB;

        $icon = sprintf('/pics/icones/%s-dist.png', strtolower(pathinfo($filename, PATHINFO_EXTENSION)));

        if (file_exists(GLPI_ROOT . $icon)) {
            return $CFG_GLPI['root_doc'] . $icon;
        }

        $extention = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        $iterator = $DB->request([
            'SELECT' => 'icon',
            'FROM'   => 'glpi_documenttypes',
            'WHERE'  => [
                'ext'    => $extention,
                'icon'   => ['<>', '']
            ]
        ]);

        $icon = '/pics/timeline/file.png';

        foreach ($iterator as $result) {
            if (file_exists(GLPI_ROOT . '/pics/icones/' . $result['icon'])) {
                $icon = '/pics/icones/' . $result['icon'];
                break;
            }
        }

        return $CFG_GLPI['root_doc'] . $icon;
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
