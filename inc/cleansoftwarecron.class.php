<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class CleanSoftwareCron extends CommonDBTM
{
   const task_name = 'cleansoftware';

   const MAX_BATCH_SIZE = 2000;

   public static function cronInfo($name) {
      return [
         'description' => __("Remove software versions with no installation and software with no version"),
         'parameter'   => __('Max items to handle in one execution')
      ];
   }

   /**
    * Clean unused software and software versions
    *
    * @param CronTask $task
    */
   public static function cronCleanSoftware(CronTask $task) {
      // Init max/batch_size settings
      $max = $task->fields['param'];
      $batch_size = max($max, self::MAX_BATCH_SIZE);

      $soft_em = new Software();
      $sv_em = new SoftwareVersion();

      // Delete software versions with no installation by batch
      $total = 0;
      do {
         $versions = self::getVersionsWithNoInstallation($batch_size);
         $count = count($versions);
         $task->addVolume($count);

         foreach ($versions as $version) {
            $sv_em->delete($version);
            $total++;

            if ($total >= $max) {
               // Stop if we reached the max number of items to handle
               break;
            }
         }

         // Stop if no items found
      } while ($count > 0);

      // Move software with no versions in the thrashbin
      $total = 0;
      do {
         $softwares = self::getSoftwareWithNoVersions($batch_size);
         $count = count($softwares);
         $task->addVolume($count);
         $total += $count;

         foreach ($softwares as $software) {
            $soft_em->delete($software);
            $total++;

            if ($total >= $max) {
               // Stop if we reached the max number of items to handle
               break;
            }
         }

         // Stop if no items found
      } while ($count > 0);

      return 1;
   }

   /**
    * Get all software versions which are not installed
    *
    * @return DBmysqlIterator
    */
   public static function getVersionsWithNoInstallation(
      int $batch_size
   ): DBmysqlIterator {
      global $DB;

      return $DB->request([
         'SELECT' => 'id',
         'FROM'   => SoftwareVersion::getTable(),
         'WHERE'  => [
            'NOT' => [
               'OR' => [
                  [
                     'id' => new QuerySubQuery([
                        'SELECT' => 'softwareversions_id',
                        'FROM'   => Item_SoftwareVersion::getTable(),
                     ])
                  ],
                  [
                     'id' => new QuerySubQuery([
                        'SELECT' => 'softwareversions_id_buy',
                        'FROM'   => SoftwareLicense::getTable(),
                     ]),
                  ],
                  [
                     'id' => new QuerySubQuery([
                        'SELECT' => 'softwareversions_id_use',
                        'FROM'   => SoftwareLicense::getTable(),
                     ]),
                  ],
               ],
            ],
         ],
         'LIMIT' => $batch_size
      ]);
   }

   /**
    * Get all software with no versions
    *
    * @return DBmysqlIterator
    */
   public static function getSoftwareWithNoVersions(
      int $batch_size
   ): DBmysqlIterator {
      global $DB;

      return $DB->request([
         'SELECT' => 'id',
         'FROM'   => Software::getTable(),
         'WHERE'  => [
            'is_deleted' => 0,
            'NOT' => [
               'id' => new QuerySubQuery([
                  'SELECT' => 'softwares_id',
                  'FROM'   => SoftwareVersion::getTable(),
               ]),
            ]
         ],
         'LIMIT' => $batch_size
      ]);
   }

   public function isEntityAssign() {
      return false;
   }
}