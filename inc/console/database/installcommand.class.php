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

use Glpi\Application\LocalConfigurationManager;
use Glpi\Console\Command\ForceNoPluginsOptionCommandInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Yaml\Yaml;
use Config;
use DBConnection;
use PDO;
use Toolbox;

class InstallCommand extends Command implements ForceNoPluginsOptionCommandInterface {

   /**
    * Error code returned if DB connection initialization fails.
    *
    * @var integer
    */
   const ERROR_DB_CONNECTION_FAILED = 1;

   /**
    * Error code returned if DB engine is unsupported.
    *
    * @var integer
    */
   const ERROR_DB_ENGINE_UNSUPPORTED = 2;

   /**
    * Error code returned when trying to install and having a DB config already set.
    *
    * @var integer
    */
   const ERROR_DB_CONFIG_ALREADY_SET = 3;

   /**
    * Error code returned when failing to create database.
    *
    * @var integer
    */
   const ERROR_DB_CREATION_FAILED = 4;

   /**
    * Error code returned when failing to save database configuration file.
    *
    * @var integer
    */
   const ERROR_DB_CONFIG_FILE_NOT_SAVED = 5;

   /**
    * Error code returned when failing to create database schema.
    *
    * @var integer
    */
   const ERROR_SCHEMA_CREATION_FAILED = 6;

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
         'db-host',
         'H',
         InputOption::VALUE_OPTIONAL,
         __('Database host'),
         'localhost'
      );

      $this->addOption(
         'db-name',
         'd',
         InputOption::VALUE_REQUIRED,
         __('Database name')
      );

      $this->addOption(
         'db-password',
         'p',
         InputOption::VALUE_OPTIONAL,
         __('Database password (will be prompted for value if option passed without value)'),
         '' // Empty string by default (enable detection of null if passed without value)
      );

      $this->addOption(
         'db-port',
         'P',
         InputOption::VALUE_OPTIONAL,
         __('Database port')
      );

      $this->addOption(
         'db-user',
         'u',
         InputOption::VALUE_REQUIRED,
         __('Database user')
      );

      $this->addOption(
         'default-language',
         'L',
         InputOption::VALUE_REQUIRED,
         __('Default language of GLPI')
      );

      $this->addOption(
         'force',
         'f',
         InputOption::VALUE_NONE,
         __('Force execution of installation, overriding existing database and configuration')
      );
   }

   protected function interact(InputInterface $input, OutputInterface $output) {

      $options = [
         'db-name'     => __('Database name:'), // Required
         'db-user'     => __('Database user:'), // Required
         'db-password' => __('Database password:'), // Prompt if null (passed without value)
      ];
      foreach ($options as $name => $label) {
         if (null === $input->getOption($name)) {
            /** @var \Symfony\Component\Console\Helper\QuestionHelper $question_helper */
            $question_helper = $this->getHelper('question');
            $value = $question_helper->ask(
               $input,
               $output,
               new Question($label, '')
            );
            $input->setOption($name, $value);
         }
      }
   }

   protected function execute(InputInterface $input, OutputInterface $output) {

      $db_pass     = $input->getOption('db-password');
      $db_host     = $input->getOption('db-host');
      $db_name     = $input->getOption('db-name');
      $db_port     = $input->getOption('db-port');
      $db_user     = $input->getOption('db-user');
      $db_hostport = $db_host . (!empty($db_port) ? ':' . $db_port : '');

      $default_language = $input->getOption('default-language');

      $force          = $input->getOption('force');
      $no_interaction = $input->getOption('no-interaction'); // Base symfony/console option

      if (file_exists(GLPI_CONFIG_DIR . '/db.yaml') && !$force) {
         // Prevent overriding of existing DB
         $output->writeln(
            '<error>' . __('Database configuration already exists. Use --force option to override existing database and configuration.') . '</error>'
         );
         return self::ERROR_DB_CONFIG_ALREADY_SET;
      }

      if (empty($db_name)) {
         throw new InvalidArgumentException(
            __('Database name defined by --db-name option cannot be empty.')
         );
      }

      if (null === $db_pass) {
         // Will be null if option used without value and without interaction
         throw new InvalidArgumentException(
            __('--db-password option value cannot be null.')
         );
      }

      if (!$no_interaction) {
         // Ask for confirmation (unless --no-interaction)

         $informations = new Table($output);
         $informations->addRow([__('Database host'), $db_hostport]);
         $informations->addRow([__('Database name'), $db_name]);
         $informations->addRow([__('Database user'), $db_user]);
         $informations->render();

         /** @var QuestionHelper $question_helper */
         $question_helper = $this->getHelper('question');
         $run = $question_helper->ask(
            $input,
            $output,
            new ConfirmationQuestion(__('Do you want to continue ?') . ' [Yes/no]', true)
         );
         if (!$run) {
            $output->writeln(
               '<comment>' . __('Installation aborted.') . '</comment>',
               OutputInterface::VERBOSITY_VERBOSE
            );
            return 0;
         }
      }

      $hostport = explode(":", $db_host);
      if (count($hostport) < 2 || intval($hostport[1]) > 0) {
         // "host" or "host:port"
         $dsn = "mysql:host=$db_host";
      } else {
         // ":socket"
         $dsn = "mysql:unix_socket={$hostport[1]}";
      }

      try {
         $dbh = new PDO(
            $dsn,
            $db_user,
            $db_pass
         );
         $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
         if (GLPI_FORCE_EMPTY_SQL_MODE) {
            $dbh->query("SET SESSION sql_mode = ''");
         }
      } catch (\PDOException $e) {
         $message = sprintf(
            __('Database connection failed with message "(%s)\n%s".'),
            $e->getMessage(),
            $e->getTraceAsString()
         );
         $output->writeln('<error>' . $message . '</error>', OutputInterface::VERBOSITY_QUIET);
         return self::ERROR_DB_CONNECTION_FAILED;

      }

      ob_start();
      $db_version = $dbh->query('SELECT version()')->fetchColumn();
      $checkdb = Config::displayCheckDbEngine(false, $db_version);
      $message = ob_get_clean();
      if ($checkdb > 0) {
         $output->writeln('<error>' . $message . '</error>', OutputInterface::VERBOSITY_QUIET);
         return self::ERROR_DB_ENGINE_UNSUPPORTED;
      }

      $db_name = str_replace('`', '``', $db_name); // Escape backquotes

      $output->writeln(
         '<comment>' . __('Creating the database...') . '</comment>',
         OutputInterface::VERBOSITY_VERBOSE
      );
      if (!$dbh->query('CREATE DATABASE IF NOT EXISTS `' . $db_name .'`')) {
         $error = $dbh->errorInfo();
         $message = sprintf(
            __("Database creation failed with message \"(%s)\n%s\"."),
            $error[0],
            $error[2]
         );
         $output->writeln('<error>' . $message . '</error>', OutputInterface::VERBOSITY_QUIET);
         return self::ERROR_DB_CREATION_FAILED;
      }
      if (false === $dbh->exec('USE `' . $db_name .'`')) {
         $error = $dbh->errorInfo();
         $message = sprintf(
            __("Database selection failed with message \"(%s)\n%s\"."),
            $error[0],
            $error[2]
         );
         $output->writeln('<error>' . $message . '</error>', OutputInterface::VERBOSITY_QUIET);
         return self::ERROR_DB_CREATION_FAILED;
      }

      $output->writeln(
         '<comment>' . __('Saving configuration file...') . '</comment>',
         OutputInterface::VERBOSITY_VERBOSE
      );
      if (!DBConnection::createMainConfig('mysql', $db_hostport, $db_user, $db_pass, $db_name)) {
         $message = sprintf(
            __('Cannot write configuration file "%s".'),
            GLPI_CONFIG_DIR . DIRECTORY_SEPARATOR . 'db.yaml'
         );
         $output->writeln(
            '<error>' . $message . '</error>',
            OutputInterface::VERBOSITY_QUIET
         );
         return self::ERROR_DB_CONFIG_FILE_NOT_SAVED;
      }

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

   public function getNoPluginsOptionValue() {

      return true;
   }
}
