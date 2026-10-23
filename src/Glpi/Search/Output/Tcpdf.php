<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace Glpi\Search\Output;

use GLPIPDF;

use function Safe\preg_replace;

class Tcpdf extends \PhpOffice\PhpSpreadsheet\Writer\Pdf\Tcpdf
{
    protected function createExternalWriterInstance($orientation, $unit, $paperSize): \TCPDF
    {
        $instance = new class (
            [
                'orientation' => $orientation,
                'unit' => $unit,
                'format' => $paperSize,
                'font_size' => 8,
                'font' => $_SESSION['glpipdffont'] ?? 'helvetica',
                'margin_bottom' => 30,
            ],
            $this->spreadsheet->getProperties()->getCustomPropertyValue('items count'),
            null,
            false
        ) extends GLPIPDF {
            /**
             * @param bool $val
             *
             * @return void
             */
            public function setPrintFooter($val = true)
            {
                //override because \PhpOffice\PhpSpreadsheet\Writer\Pdf\Tcpdf::save() explicitly calls setPrintFooter(false) -_-
                parent::setPrintFooter(true);
            }
        };

        //remove size considerations so TCPDF do its work.
        $callback = (function ($html) {
            $html = preg_replace(
                [
                    '|</style>|',
                    '|width:\d+pt"|',
                    '|padding-left:\dpx;|',
                ],
                [
                    'table { width: 100%; } th, td { padding: 1pt 2pt; }</style>',
                    '"',
                    '',
                ],
                $html
            );

            // TCPDF 7 sizes columns from their content, spread them evenly instead
            $columns_count = substr_count($html, '<col ');
            if ($columns_count > 0) {
                $html = preg_replace('|<td |', '<td width="' . round(100 / $columns_count, 2) . '%" ', $html);
            }

            return $html;
        });
        $this->setEditHtmlCallback($callback);

        return $instance;
    }
}
