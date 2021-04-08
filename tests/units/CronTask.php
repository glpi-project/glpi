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

use \RecursiveDirectoryIterator;
use \RecursiveIteratorIterator;

/* Test for inc/crontask.class.php */

class Crontask extends \GLPITestCase {

   public function testCronTemp() {

      //create some files
      $Data = [
         [
            'name'    => GLPI_TMP_DIR.'/recent_file.txt',
            'content' => 'content1',
         ],
         [
            'name'    => GLPI_TMP_DIR.'/file1.txt',
            'content' => 'content1',
         ],
         [
            'name'    => GLPI_TMP_DIR.'/file2.txt',
            'content' => 'content2',
         ],
         [
            'name'    => GLPI_TMP_DIR.'/auto_orient/file3.txt',
            'content' => 'content3',
         ],
         [
            'name'    => GLPI_TMP_DIR.'/auto_orient/file4.txt',
            'content' => 'content4',
         ]
      ];

      //create auto_orient directory
      if (!file_exists(GLPI_TMP_DIR.'/auto_orient/')) {
         mkdir(GLPI_TMP_DIR.'/auto_orient/', 0755, true);
      }

      foreach ($Data as $Row) {
         $file = fopen($Row['name'], 'c');
         fwrite($file, $Row['content']);
         fclose($file);

         //change filemtime (except recent_file.txt)
         if ($Row['name'] != GLPI_TMP_DIR.'/recent_file.txt') {
            touch($Row['name'], time() - (HOUR_TIMESTAMP * 2));
         }

      }

      // launch Cron for cleaning _tmp directory
      $mode = - \CronTask::MODE_EXTERNAL; // force
      \CronTask::launch($mode, 5, 'temp');

      $nb_file = $this->getFileCountRecursively(GLPI_TMP_DIR);
      $this->variable($nb_file)->isEqualTo(1); //recent_file.txt
   }


   public function getFileCountRecursively($path) {

      $dir = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
      $files = new RecursiveIteratorIterator($dir,
                     RecursiveIteratorIterator::CHILD_FIRST);
      return iterator_count($files);
   }
}