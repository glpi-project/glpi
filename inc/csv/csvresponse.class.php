<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

namespace Glpi\Csv;

use League\Csv\Writer;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class CsvResponse
{
   /**
    * Output a CSV file using League\Csv
    *
    * @param ExportToCsvInterface $export
    */
   public static function output(ExportToCsvInterface $export): void {
      $csv = Writer::createFromString('');
      $csv->setDelimiter($_SESSION["glpicsv_delimiter"] ?? ";");
      $csv->insertOne($export->getFileHeader());
      $csv->insertAll($export->getFileContent());
      $csv->output($export->getFileName());
   }
}
