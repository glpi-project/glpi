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

namespace Glpi\Search\Output;

/**
 *
 * @internal Not for use outside {@link Search} class and the "Glpi\Search" namespace.
 */
final class PDFLandscapeSearchOutput extends PDFSearchOutput
{
    public static function showFooter($title = "", $count = null): string
    {
        /** @var string $PDF_TABLE */
        global $PDF_TABLE;

        $font       = 'helvetica';
        $fontsize   = 8;
        if (isset($_SESSION['glpipdffont']) && $_SESSION['glpipdffont']) {
            $font       = $_SESSION['glpipdffont'];
        }

        $pdf = new \GLPIPDF(
            [
                'font_size'  => $fontsize,
                'font'       => $font,
                'orientation'        => 'L',
            ],
            $count,
            $title,
        );

        $PDF_TABLE .= '</table>';
        $pdf->writeHTML($PDF_TABLE, true, false, true);
        $pdf->Output('glpi.pdf', 'I');
        return '';
    }
}
