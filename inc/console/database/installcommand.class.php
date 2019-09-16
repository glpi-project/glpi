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
use Symfony\Component\Console\Question\ConfirmationQuestion;
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

      if ($this->isDbAlreadyConfigured()
          && $this->isInputContainingConfigValues($input, $output)
          && !$input->getOption('reconfigure')) {
         /** @var Symfony\Component\Console\Helper\QuestionHelper $question_helper */
         $question_helper = $this->getHelper('question');
         $reconfigure = $question_helper->ask(
            $input,
            $output,
            new ConfirmationQuestion(
               __('Command input contains configuration options that may override existing configuration.')
                  . PHP_EOL
                  . __('Do you want to reconfigure database ?') . ' [Yes/no]',
               true
            )
         );
         $input->setOption('reconfigure', $reconfigure);
      }

      if (!$this->isDbAlreadyConfigured() || $input->getOption('reconfigure')) {
         parent::interact($input, $output);
      }
   }

   protected function execute(InputInterface $input, OutputInterface $output) {

      $default_language = $input->getOption('default-language');
      $force            = $input->getOption('force');

      if ($this->isDbAlreadyConfigured()
          && $this->isInputContainingConfigValues($input, $output)
          && !$input->getOption('reconfigure')) {
         // Prevent overriding of existing DB when input contains configuration values and
         // --reconfigure option is not used.
         $output->writeln(
            '<error>' . __('Database configuration already exists. Use --reconfigure option to override existing configuration.') . '</error>'
         );
         return self::ERROR_DB_CONFIG_ALREADY_SET;
      }

      if (!$this->isDbAlreadyConfigured() || $input->getOption('reconfigure')) {
         $result = $this->configureDatabase($input, $output);

         if (self::ABORTED_BY_USER === $result) {
            return 0; // Considered as success
         } else if (self::SUCCESS !== $result) {
            return $result; // Fail with error code
         }
         $db_driver   = $input->getOption('db-driver');
         $db_host     = $input->getOption('db-host');
         $db_port     = $input->getOption('db-port');
         $db_hostport = $db_host . (!empty($db_port) ? ':' . $db_port : '');
         $db_name     = $input->getOption('db-name');
         $db_user     = $input->getOption('db-user');
         $db_pass     = $input->getOption('db-password');
      } else {
         // Ask to confirm installation based on existing configuration.
         $db_config = Yaml::parseFile(GLPI_CONFIG_DIR . '/db.yaml', Yaml::PARSE_CONSTANT);
         $db_driver   = $db_config['driver'];
         $db_hostport = $db_config['host'];
         $db_name     = $db_config['dbname'];
         $db_user     = $db_config['user'];
         $db_pass     = $db_config['pass'];
         $run = $this->askForDbConfigConfirmation(
            $input,
            $output,
            $db_driver,
            $db_hostport,
            $db_name,
            $db_user
         );
         if (!$run) {
            $output->writeln(
               '<comment>' . __('Installation aborted.') . '</comment>',
               OutputInterface::VERBOSITY_VERBOSE
            );
            return 0;
         }
      }

      $dbh = DatabaseFactory::create(
         [
            'driver'   => $db_driver,
            'host'     => $db_hostport,
            'dbname'   => $db_name,
            'user'     => $db_user,
            'pass'     => $db_pass,
         ]
      );

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

   /**
    * Check if input contains DB config options.
    *
    * @param InputInterface $input
    * @param OutputInterface $output
    *
    * @return boolean
    */
   private function isInputContainingConfigValues(InputInterface $input, OutputInterface $output) {

      $config_options = [
         'db-driver',
         'db-host',
         'db-port',
         'db-name',
         'db-user',
         'db-password',
      ];
      foreach ($config_options as $option) {
         $default_value = $this->getDefinition()->getOption($option)->getDefault();
         $input_value   = $input->getOption($option);

         if ($default_value !== $input_value) {
            return true;
         }
      }

      return false;
   }
}
