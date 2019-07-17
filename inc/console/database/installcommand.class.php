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

namespace Glpi\Console\Database;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use Glpi\DatabaseFactory;
use Glpi\Application\LocalConfigurationManager;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Yaml\Yaml;
use Toolbox;

class InstallCommand extends AbstractConfigureCommand {

   /**
    * Error code returned when failing to create database.
    *
    * @var integer
    */
   const ERROR_DB_CREATION_FAILED = 5;

   /**
    * Error code returned when trying to install and having a DB already containing glpi_* tables.
    *
    * @var integer
    */
   const ERROR_DB_ALREADY_CONTAINS_TABLES = 6;

   /**
    * Error code returned when failing to create database schema.
    *
    * @var integer
    */
   const ERROR_SCHEMA_CREATION_FAILED = 7;

   /**
    * Error code returned when failing to select database.
    *
    * @var integer
    */
   const ERROR_DB_SELECT_FAILED = 7;

   /**
    * Error code returned when failing to save local configuration file.
    *
    * @var integer
    */
   const ERROR_LOCAL_CONFIG_FILE_NOT_SAVED = 8;

   protected function configure() {

      parent::configure();

      $this->setName('glpi:database:install');
      $this->setAliases(['db:install']);
      $this->setDescription('Install database schema');

      $this->addOption(
         'default-language',
         'L',
         InputOption::VALUE_OPTIONAL,
         __('Default language of GLPI'),
         'en_GB'
      );

      $this->addOption(
         'force',
         'f',
         InputOption::VALUE_NONE,
         __('Force execution of installation, overriding existing database')
      );
   }

   protected function interact(InputInterface $input, OutputInterface $output) {

      if ($this->shouldSetDBConfig($input, $output)) {
         parent::interact($input, $output);
      }
   }

   protected function execute(InputInterface $input, OutputInterface $output) {

      $db_name          = $input->getOption('db-name');
      $default_language = $input->getOption('default-language');
      $force            = $input->getOption('force');

      if ($this->shouldSetDBConfig($input, $output)) {
         $result = $this->configureDatabase($input, $output);

         if (self::ABORTED_BY_USER === $result) {
            return 0; // Considered as success
         } else if (self::SUCCESS !== $result) {
            return $result; // Fail with error code
         }
      }

      $dbh = DatabaseFactory::create();

      // Create database or select existing one
      $output->writeln(
         '<comment>' . __('Creating the database...') . '</comment>',
         OutputInterface::VERBOSITY_VERBOSE
      );
      $qchar = $dbh->getQuoteNameChar();
      $db_name = str_replace($qchar, $qchar.$qchar, $db_name); // Escape backquotes
      if (!$dbh->rawQuery('CREATE DATABASE IF NOT EXISTS ' . $dbh->quoteName($db_name))) {
         $error = $dbh->errorInfo();
         $message = sprintf(
            __("Database creation failed with message \"(%s)\n%s\"."),
            $error[0],
            $error[2]
         );
         $output->writeln('<error>' . $message . '</error>', OutputInterface::VERBOSITY_QUIET);
         return self::ERROR_DB_CREATION_FAILED;
      }
      if (false === $dbh->rawQuery('USE ' . $dbh->quoteName($db_name))) {
         $error = $dbh->errorInfo();
         $message = sprintf(
            __("Database selection failed with message \"(%s)\n%s\"."),
            $error[0],
            $error[2]
         );
         $output->writeln('<error>' . $message . '</error>', OutputInterface::VERBOSITY_QUIET);
         return self::ERROR_DB_CREATION_FAILED;
      }

      // Prevent overriding of existing DB
      $tables_iterator = $dbh->request(
          [
             'COUNT'  => 'cpt',
             'FROM'   => 'information_schema.tables',
             'WHERE'  => [
                'table_schema' => $db_name,
                'table_type'   => 'BASE TABLE',
                'table_name'   => ['LIKE', 'glpi_%'],
             ],
          ]
      );
      if ($tables_result = $tables_iterator->next()) {
         if ($tables_result['cpt'] > 0 && !$force) {
            $output->writeln(
               '<error>' . __('Database already contains "glpi_*" tables. Use --force option to override existing database.') . '</error>'
            );
            return self::ERROR_DB_ALREADY_CONTAINS_TABLES;
         }
      } else {
         throw new RuntimeException('Unable to check GLPI tables existence.');
      }

      // Install schema
      $output->writeln(
         '<comment>' . __('Loading default schema...') . '</comment>',
         OutputInterface::VERBOSITY_VERBOSE
      );
      // TODO Get rid of output buffering
      ob_start();
      Toolbox::createSchema($default_language);
      $message = ob_get_clean();
      if (!empty($message)) {
         $output->writeln('<error>' . $message . '</error>', OutputInterface::VERBOSITY_QUIET);
         return self::ERROR_SCHEMA_CREATION_FAILED;
      }

      try {
         $localConfigManager = new LocalConfigurationManager(
            GLPI_CONFIG_DIR,
            new PropertyAccessor(),
            new Yaml()
         );
         $localConfigManager->setParameterValue('[cache_uniq_id]', uniqid());
      } catch (\Exception $e) {
         $message = sprintf(
            __('Local configuration file saving failed with message "(%s)\n%s".'),
            $e->getMessage(),
            $e->getTraceAsString()
         );
         $output->writeln('<error>' . $message . '</error>', OutputInterface::VERBOSITY_QUIET);
         return self::ERROR_LOCAL_CONFIG_FILE_NOT_SAVED;
      }

      $output->writeln('<info>' . __('Installation done.') . '</info>');

      return 0; // Success
   }

   /**
    * Check if DB config should be set by current command run.
    *
    * @param InputInterface $input
    * @param OutputInterface $output
    *
    * @return boolean
    */
   private function shouldSetDBConfig(InputInterface $input, OutputInterface $output) {

      return $input->getOption('reconfigure') || !file_exists(GLPI_CONFIG_DIR . '/config_db.php');
   }
}
