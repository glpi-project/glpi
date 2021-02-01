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

namespace Glpi\Console\Migration;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use Glpi\Console\AbstractCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DynamicRowFormatCommand extends AbstractCommand {

   /**
    * Error code returned if migration failed on, at least, one table.
    *
    * @var integer
    */
   const ERROR_MIGRATION_FAILED_FOR_SOME_TABLES = 1;

   /**
    * Error code returned if some tables are still using MyISAM engine.
    *
    * @var integer
    */
   const ERROR_INNODB_REQUIRED = 2;

   /**
    * Error code returned if DB configuration is not compatible with large indexes.
    *
    * @var integer
    */
   const ERROR_INCOMPATIBLE_DB_CONFIG = 3;

   protected $requires_db_up_to_date = false;

   protected function configure() {
      parent::configure();

      $this->setName('glpi:migration:dynamic_row_format');
      $this->setDescription(__('Convert database tables to "Dynamic" row format (required for "utf8mb4" character support).'));
   }

   protected function execute(InputInterface $input, OutputInterface $output) {
      $this->checkForPrerequisites();
      $this->upgradeRowFormat();

      return 0; // Success
   }

   /**
    * Check for migration prerequisites.
    *
    * @return void
    */
   private function checkForPrerequisites(): void {
      // Check that DB configuration is compatible
      $version = preg_replace('/^((\d+\.?)+).*$/', '$1', $this->db->getVersion());
      if (version_compare($version, '5.7', '<')) {
         // On MySQL 5.6, "ROW_FORMAT = Dynamic" fallbacks to "ROW_FORMAT = Compact"
         // if "innodb_file_format" is not set to "Barracuda".
         // This variable has been removed in MySQL 8.0 and in MariaDB 10.3.
         $query = 'SELECT @@GLOBAL.' . $this->db->quoteName('innodb_file_format as innodb_file_format');

         if (($db_config_res = $this->db->query($query)) === false) {
            $msg = '<error>' . __('Unable to validate database configuration variables.') . '</error>';
            throw new \Glpi\Console\Exception\EarlyExitException($msg, self::ERROR_INCOMPATIBLE_DB_CONFIG);
         }

         $db_config = $db_config_res->fetch_assoc();
         if ($db_config['innodb_file_format'] !== 'Barracuda') {
            $msg = '<error>' . __('Database configuration is not compatible with "Dynamic" row format usage.') . '</error>'
                 . "\n" . '<error> - ' . __('"innodb_file_format" must be set to "Barracuda".') . '</error>';
            throw new \Glpi\Console\Exception\EarlyExitException($msg, self::ERROR_INCOMPATIBLE_DB_CONFIG);
         }
      }

      // Check that all tables are using InnoDB engine
      if (($myisam_count = $this->db->getMyIsamTables()->count()) > 0) {
         $msg = sprintf(
            __('%d tables are still using MyISAM storage engine. Run "php bin/console glpi:migration:myisam_to_innodb" to fix this.'),
            $myisam_count
         );
         throw new \Glpi\Console\Exception\EarlyExitException('<error>' . $msg . '</error>', self::ERROR_INNODB_REQUIRED);
      }
   }

   /**
    * Upgrade row format from 'Compact'/'Redundant' to 'Dynamic'.
    * This is mandatory to support large indexes.
    *
    * @return void
    */
   private function upgradeRowFormat(): void {

      $table_iterator = $this->db->listTables(
         'glpi\_%',
         [
            'row_format'   => ['Compact', 'Redundant'],
         ]
      );

      if ($table_iterator->count() === 0) {
         return;
      }

      $this->output->writeln(
         sprintf(
            '<info>' . __('Found %s table(s) requiring a migration to "ROW_FORMAT=Dynamic".') . '</info>',
            $table_iterator->count()
         )
      );

      $this->askForConfirmation();

      $tables = [];
      foreach ($table_iterator as $table_data) {
         $tables[] = $table_data['TABLE_NAME'];
      }
      sort($tables);

      $progress_bar = new ProgressBar($this->output);
      $errors = 0;

      foreach ($progress_bar->iterate($tables) as $table) {
         $this->writelnOutputWithProgressBar(
            sprintf(__('Migrating table "%s"...'), $table),
            $progress_bar,
            OutputInterface::VERBOSITY_VERY_VERBOSE
         );

         $result = $this->db->query(
            sprintf('ALTER TABLE `%s` ROW_FORMAT = DYNAMIC', $table)
         );

         if (!$result) {
            $this->writelnOutputWithProgressBar(
               sprintf(__('<error>Error migrating table "%s".</error>'), $table),
               $progress_bar,
               OutputInterface::VERBOSITY_QUIET
            );
            $errors++;
         }
      }

      $this->output->write(PHP_EOL);

      if ($errors) {
         throw new \Glpi\Console\Exception\EarlyExitException(
            '<error>' . __('Errors occured during migration.') . '</error>',
            self::ERROR_MIGRATION_FAILED_FOR_SOME_TABLES
         );
      }
   }
}
