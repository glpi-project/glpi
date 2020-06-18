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

   public static function cronInfo($name) {
      return ['description' => __("Remove software versions with no installation and software with no version")];
   }

   /**
    * Clean unused software and software versions
    *
    * @param CronTask $task
    */
   public static function cronCleanSoftware(CronTask $task) {
      $soft_em = new Software();
      $sv_em = new SoftwareVersion();

      // Delete software versions with no installation by batch
      do {
         $versions = self::getVersionsWithNoInstallation();
         $count = count($versions);
         $task->addVolume($count);

         foreach ($versions as $version) {
            $sv_em->delete($version);
         }

      } while ($count > 0);

      // Move software with no versions in the thrashbin
      do {
         $softwares = self::getSoftwareWithNoVersions();
         $count = count($softwares);
         $task->addVolume($count);

         foreach ($softwares as $software) {
            $soft_em->delete($software);
         }

      } while ($count > 0);

      return 1;
   }

   /**
    * Get all software versions which are not installed
    *
    * @return DBmysqlIterator
    */
   public static function getVersionsWithNoInstallation(): DBmysqlIterator {
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
         'LIMIT' => 2000
      ]);
   }

   /**
    * Get all software with no versions
    *
    * @return DBmysqlIterator
    */
   public static function getSoftwareWithNoVersions(): DBmysqlIterator {
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
         'LIMIT' => 2000
      ]);
   }

   public function isEntityAssign() {
      return false;
   }
}