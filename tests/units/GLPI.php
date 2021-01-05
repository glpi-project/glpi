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

namespace tests\units;

/* Test for inc/glpi.class.php */

class GLPI extends \GLPITestCase {
   public function testGetLogLevel() {
      $glpi = new \GLPI();
      $glpi->initLogger();
      $this->integer($glpi->getLogLevel())->isIdenticalTo(\Monolog\Logger::DEBUG);
   }

   public function testMissingLanguages() {
      global $CFG_GLPI;

      $know_languages = $CFG_GLPI['languages'];
      $list_languages = [];

      $diterator = new \DirectoryIterator(__DIR__ . '/../../locales');
      foreach ($diterator as $file) {
         if (!$file->isDot() && $file->getExtension() == 'po') {
            $lang = $file->getBasename('.' . $file->getExtension());
            $list_languages[$lang] = $lang;
         }
      }

      $po_missing = array_diff_key($know_languages, $list_languages);
      $this->array($po_missing)->isEmpty(
         "Referenced languages in configuration are missing in locales directory:\n" . print_r($po_missing, true)
      );

      $cfg_missing = array_diff_key($list_languages, $know_languages);
      $this->array($cfg_missing)->isEmpty(
         "Locales files present in directory are missing from configuration:\n" . print_r($cfg_missing, true)
      );
   }
}
