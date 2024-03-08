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

use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class Tcpdf extends \PhpOffice\PhpSpreadsheet\Writer\Pdf\Tcpdf
{
    protected function createExternalWriterInstance($orientation, $unit, $paperSize): \TCPDF
    {
        $instance = new \GLPIPDF(
            [
                'orientation' => $orientation,
                'unit' => $unit,
                'format' => $paperSize,
                'font_size' => 8,
                'font' => $_SESSION['glpipdffont'] ?? 'helvetica',
                'margin_bottom' => 30
            ],
            $this->spreadsheet->getProperties()->getCustomPropertyValue('items count'),
            null,
            false
        );

        //remove size considerations so TCPDF do its work.
        $callback = function ($html) {
            return preg_replace(
                [
                    '|</style>|',
                    '|width:\d+pt"|',
                    '|padding-left:\dpx;|'
                ],
                [
                    'table { width: 100%; };</style>',
                    '"',
                    ''
                ],
                $html
            );
        };
        $this->setEditHtmlCallback($callback);

        return $instance;
    }

    /**
     * Fully copy-pasted from \PhpOffice\PhpSpreadsheet\Writer\Pdf\Tcpdf to comment setPrintFooter(false) -_-
     */
    public function save($filename, int $flags = 0): void
    {
        $fileHandle = parent::prepareForSave($filename);

        //  Default PDF paper size
        $paperSize = 'LETTER'; //    Letter    (8.5 in. by 11 in.)

        //  Check for paper size and page orientation
        $setup = $this->spreadsheet->getSheet($this->getSheetIndex() ?? 0)->getPageSetup();
        $orientation = $this->getOrientation() ?? $setup->getOrientation();
        $orientation = ($orientation === PageSetup::ORIENTATION_LANDSCAPE) ? 'L' : 'P';
        $printPaperSize = $this->getPaperSize() ?? $setup->getPaperSize();
        $paperSize = self::$paperSizes[$printPaperSize] ?? PageSetup::getPaperSizeDefault();
        $printMargins = $this->spreadsheet->getSheet($this->getSheetIndex() ?? 0)->getPageMargins();

        //  Create PDF
        $pdf = $this->createExternalWriterInstance($orientation, 'pt', $paperSize);
        $pdf->setFontSubsetting(false);
        //    Set margins, converting inches to points (using 72 dpi)
        $pdf->SetMargins($printMargins->getLeft() * 72, $printMargins->getTop() * 72, $printMargins->getRight() * 72);
        $pdf->SetAutoPageBreak(true, $printMargins->getBottom() * 72);

        $pdf->setPrintHeader(false);
        //$pdf->setPrintFooter(false);

        $pdf->AddPage();

        //  Set the appropriate font
        $pdf->SetFont($this->getFont());
        $pdf->writeHTML($this->generateHTMLAll());

        //  Document info
        $pdf->SetTitle($this->spreadsheet->getProperties()->getTitle());
        $pdf->SetAuthor($this->spreadsheet->getProperties()->getCreator());
        $pdf->SetSubject($this->spreadsheet->getProperties()->getSubject());
        $pdf->SetKeywords($this->spreadsheet->getProperties()->getKeywords());
        $pdf->SetCreator($this->spreadsheet->getProperties()->getCreator());

        //  Write to file
        fwrite($fileHandle, $pdf->output('', 'S'));

        parent::restoreStateAfterSave();
    }
}
