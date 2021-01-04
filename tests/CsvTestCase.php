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

// namespace tests\units;

use Glpi\Csv\ExportToCsvInterface;

/* Test for inc/log.class.php */
abstract class CsvTestCase extends DbTestCase {

   /**
    * Get data to test.
    *
    * Format: [
    *    'export'   => ExportToCsvInterface
    *    'expected' => array of parameters (see below)
    * ]
    *
    * Mandatory params for 'expected' :
    *    - filename (expected name of the csv file)
    *    - cols     (expected number of cols of the csv file)
    *    - rows     (expected number of rows of the csv file)
    *
    * Optionnals params for 'expected' :
    *    - header   (exact expected content of the header array)
    *    - content  (exact expected content of the content array)
    *
    * @return array
    */
   protected abstract function getTestData(): array;

   protected function csvTestProvider(): array {
       return $this->getTestData();
   }

   /**
    * @dataprovider csvTestProvider
    */
   public function testGetFileName(
      ExportToCsvInterface $export,
      array $expected
   ): void {
      $filename = $export->getFileName();
      $this->string($filename)->isEqualTo($expected['filename']);
   }

   /**
    * @dataprovider csvTestProvider
    */
   public function testGetFileHeader(
      ExportToCsvInterface $export,
      array $expected
   ): void {
      $header = $export->getFileHeader();
      $this->array($header)->hasSize($expected['cols']);

      if (isset($expected['header'])) {
         $this->array($header)->isEqualTo($expected['header']);
      }
   }

   /**
    * @dataprovider csvTestProvider
    */
   public function testGetFileContent(
      ExportToCsvInterface $export,
      array $expected
   ): void {
      $content = $export->getFileContent();
      $this->array($content)->hasSize($expected['rows']);

      foreach ($content as $content_row) {
         $this->array($content_row)->hasSize($expected['cols']);
      }

      if (isset($expected['content'])) {
         $this->array($content)->isEqualTo($expected['content']);
      }
   }
}
