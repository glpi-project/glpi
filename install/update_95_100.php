<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

use Glpi\Application\LocalConfigurationManager;
use Glpi\ConfigParams;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Yaml\Yaml;

/**
 * Update from 9.5.x to 10.0.0
 *
 * @return bool for success (will die for most error)
**/
function update95to100() {
   global $DB, $migration, $CFG_GLPI;
   $dbutils = new DbUtils();

   $current_config   = Config::getConfigurationValues('core');
   $updateresult     = true;
   $ADDTODISPLAYPREF = [];
   $config_to_drop = [];

   //TRANS: %s is the number of new version
   $migration->displayTitle(sprintf(__('Update to %s'), '10.0.0'));
   $migration->setVersion('10.0.0');

   /** Add main column on displaypreferences */
   if ($migration->addField(
         'glpi_displaypreferences',
         'is_main',
         'bool',
         ['value' => 1]
      )) {
      $migration->addKey('glpi_displaypreferences', 'is_main');
      $migration->dropKey('glpi_displaypreferences', 'unicity');
      $migration->migrationOneTable('glpi_displaypreferences');
      $migration->addKey(
         'glpi_displaypreferences',
         ['users_id', 'itemtype', 'num', 'is_main'],
         'unicity',
         'UNIQUE'
      );
   }
   /** /Add main column on displaypreferences */

   /** add display preferences for sub items */
   $ADDTODISPLAYPREF['Contract'] = [3, 4, 29, 5];
   $ADDTODISPLAYPREF['Item_Disk'] = [2, 3, 4, 5, 6, 7, 8];
   $ADDTODISPLAYPREF['Certificate'] = [7, 4, 8, 121, 10, 31];
   $ADDTODISPLAYPREF['Notepad'] = [200, 201, 202, 203, 204];
   $ADDTODISPLAYPREF['SoftwareVersion'] = [3, 31, 2, 122, 123, 124];
   foreach ($ADDTODISPLAYPREF as $type => $tab) {
      $rank = 1;
      foreach ($tab as $newval) {
         $query = "REPLACE INTO `glpi_displaypreferences`
                           (`itemtype` ,`num` ,`rank` ,`users_id`, `is_main`)
                     VALUES ('$type', '$newval', '".$rank++."', '0', '0')";
         $DB->query($query);
      }
   }
   /** /add display preferences for sub items */

   //Add over-quota option to software licenses to allow assignment after all alloted licenses are used
   if (!$DB->fieldExists('glpi_softwarelicenses', 'allow_overquota')) {
      if ($migration->addField('glpi_softwarelicenses', 'allow_overquota', 'bool')) {
         $migration->addKey('glpi_softwarelicenses', 'allow_overquota');
      }
   }

   /** move cache configuration into local configuration file */
   try {
      $localConfigManager = new LocalConfigurationManager(
         GLPI_CONFIG_DIR,
         new PropertyAccessor(),
         new Yaml()
      );
      $localConfigManager->setCacheValuesFromLegacyConfig(new ConfigParams($CFG_GLPI), GLPI_CACHE_DIR);
      $localConfigManager->setParameterValue('[cache_uniq_id]', uniqid(), false);
      $config_to_drop[] = 'cache_db';
      $config_to_drop[] = 'cache_trans';
   } catch (\Exception $exception) {
      $migration->displayWarning(
          sprintf(
              __('Unable to write cache configuration into local configuration file. Message was: "%s".'),
              $exception->getMessage()
          )
      );
   }
   /** /move cache configuration into local configuration file */

   /** Timezones */
   //User timezone
   if (!$DB->fieldExists('glpi_users', 'timezone')) {
      $migration->addField("glpi_users", "timezone", "varchar(50) DEFAULT NULL");
   }
   $migration->displayWarning("DATETIME fields must be converted to TIMESTAMP for timezones to work. Run bin/console db:migration:timestamps");

   // Add a config entry for app timezone setting
   $migration->addConfig(['timezone' => null]);

   /** /Timezones */

   // ************ Keep it at the end **************
   Config::deleteConfigurationValues('core', $config_to_drop);

   $migration->executeMigration();

   return $updateresult;
}
