<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
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
        global $CFG_GLPI;

        $icon = sprintf('/pics/icones/%s-dist.png', strtolower(pathinfo($filename, PATHINFO_EXTENSION)));

        return $CFG_GLPI['root_doc'] . (file_exists(GLPI_ROOT . $icon) ? $icon : '/pics/timeline/file.png');
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
