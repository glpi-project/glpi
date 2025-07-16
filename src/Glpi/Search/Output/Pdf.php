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

namespace Glpi\Search\Output;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageMargins;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

final class Pdf extends Spreadsheet
{
    public const PORTRAIT = PageSetup::ORIENTATION_PORTRAIT;
    public const LANDSCAPE = PageSetup::ORIENTATION_LANDSCAPE;

    public function __construct(string $orientation = self::PORTRAIT)
    {
        parent::__construct();

        $style = $this->spread->getDefaultStyle();

        $borders = $style->getBorders();
        $borders->getBottom()->setBorderStyle(Border::BORDER_DOTTED);

        $pagesetup = $this->spread->getActiveSheet()->getPageSetup();
        $pagesetup->setPaperSize(PageSetup::PAPERSIZE_A4);
        $pagesetup->setRowsToRepeatAtTop([1, 1]); //OK, but align gap on 2nd page between header and body*/

        $margin = PageMargins::fromCentimeters(1);
        $this->spread->getActiveSheet()->getPageMargins()
            ->setTop($margin)
            ->setRight($margin)
            ->setLeft($margin);

        IOFactory::registerWriter('GLPIPdf', Tcpdf::class);
        /** @var \PhpOffice\PhpSpreadsheet\Writer\Pdf $writer */
        $writer = IOFactory::createWriter($this->spread, 'GLPIPdf');
        $writer->setOrientation($orientation);
        $this->writer = $writer;
    }

    public function getMime(): string
    {
        return 'appplication/pdf';
    }

    public function getFileName(): string
    {
        return "glpi.pdf";
    }
}
