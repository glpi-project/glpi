<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
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

use Config;
use DBConnection;
use Glpi\Console\AbstractCommand;
use Glpi\Console\Command\ForceNoPluginsOptionCommandInterface;

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

abstract class AbstractConfigureCommand extends AbstractCommand implements ForceNoPluginsOptionCommandInterface {

   /**
    * Error code returned if DB configuration is aborted by user.
    *
    * @var integer
    */
   const ABORTED_BY_USER = -1;

   /**
    * Error code returned if DB configuration succeed.
    *
    * @var integer
    */
   const SUCCESS = 0;

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
    * Error code returned when trying to configure and having a DB config already set.
    *
    * @var integer
    */
   const ERROR_DB_CONFIG_ALREADY_SET = 3;

   /**
    * Error code returned when failing to save database configuration file.
    *
    * @var integer
    */
   const ERROR_DB_CONFIG_FILE_NOT_SAVED = 4;

   protected $requires_db_up_to_date = false;

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
         'reconfigure',
         'r',
         InputOption::VALUE_NONE,
         __('Reconfigure database, override configuration file if it already exists')
      );
   }

   protected function interact(InputInterface $input, OutputInterface $output) {

      $questions = [
         'db-name'     => new Question(__('Database name:'), ''), // Required
         'db-user'     => new Question(__('Database user:'), ''), // Required
         'db-password' => new Question(__('Database password:'), ''), // Prompt if null (passed without value)
      ];
      $questions['db-password']->setHidden(true); // Make password input hidden

      foreach ($questions as $name => $question) {
         if (null === $input->getOption($name)) {
            /** @var \Symfony\Component\Console\Helper\QuestionHelper $question_helper */
            $question_helper = $this->getHelper('question');
            $value = $question_helper->ask($input, $output, $question);
            $input->setOption($name, $value);
         }
      }
   }

   protected function initDbConnection() {

      return; // Prevent DB connection
   }

   /**
    * Save database configuration file.
    *
    * @param InputInterface $input
    * @param OutputInterface $output
    * @throws InvalidArgumentException
    * @return string
    */
   protected function configureDatabase(InputInterface $input, OutputInterface $output) {

      $db_pass     = $input->getOption('db-password');
      $db_host     = $input->getOption('db-host');
      $db_name     = $input->getOption('db-name');
      $db_port     = $input->getOption('db-port');
      $db_user     = $input->getOption('db-user');
      $db_hostport = $db_host . (!empty($db_port) ? ':' . $db_port : '');

      $reconfigure    = $input->getOption('reconfigure');

      if (file_exists(GLPI_CONFIG_DIR . '/config_db.php') && !$reconfigure) {
         // Prevent overriding of existing DB
         $output->writeln(
            '<error>' . __('Database configuration already exists. Use --reconfigure option to override existing configuration.') . '</error>'
         );
         return self::ERROR_DB_CONFIG_ALREADY_SET;
      }

      $this->validateConfigInput($input);

      $run = $this->askForDbConfigConfirmation(
         $input,
         $output,
         $db_hostport,
         $db_name,
         $db_user
      );
      if (!$run) {
         $output->writeln(
            '<comment>' . __('Configuration aborted.') . '</comment>',
            OutputInterface::VERBOSITY_VERBOSE
         );
         return self::ABORTED_BY_USER;
      }

      $mysqli = new \mysqli();
      if (intval($db_port) > 0) {
         // Network port
         @$mysqli->connect($db_host, $db_user, $db_pass, null, $db_port);
      } else {
         // Unix Domain Socket
         @$mysqli->connect($db_host, $db_user, $db_pass, null, 0, $db_port);
      }

      if (0 !== $mysqli->connect_errno) {
         $message = sprintf(
            __('Database connection failed with message "(%s) %s".'),
            $mysqli->connect_errno,
            $mysqli->connect_error
         );
         $output->writeln('<error>' . $message . '</error>', OutputInterface::VERBOSITY_QUIET);
         return self::ERROR_DB_CONNECTION_FAILED;
      }

      ob_start();
      $db_version_data = $mysqli->query('SELECT version()')->fetch_array();
      $checkdb = Config::displayCheckDbEngine(false, $db_version_data[0]);
      $message = ob_get_clean();
      if ($checkdb > 0) {
         $output->writeln('<error>' . $message . '</error>', OutputInterface::VERBOSITY_QUIET);
         return self::ERROR_DB_ENGINE_UNSUPPORTED;
      }

      $db_name = $mysqli->real_escape_string($db_name);

      $output->writeln(
         '<comment>' . __('Saving configuration file...') . '</comment>',
         OutputInterface::VERBOSITY_VERBOSE
      );
      if (!DBConnection::createMainConfig($db_hostport, $db_user, $db_pass, $db_name)) {
         $message = sprintf(
            __('Cannot write configuration file "%s".'),
            GLPI_CONFIG_DIR . DIRECTORY_SEPARATOR . 'config_db.php'
         );
         $output->writeln(
            '<error>' . $message . '</error>',
            OutputInterface::VERBOSITY_QUIET
         );
         return self::ERROR_DB_CONFIG_FILE_NOT_SAVED;
      }

      return self::SUCCESS;
   }

   public function getNoPluginsOptionValue() {

      return true;
   }

   /**
    * Check if DB is already configured.
    *
    * @return boolean
    */
   protected function isDbAlreadyConfigured() {

      return file_exists(GLPI_CONFIG_DIR . '/config_db.php');
   }

   /**
    * Validate configuration variables from input.
    *
    * @param InputInterface $input
    *
    * @throws InvalidArgumentException
    */
   protected function validateConfigInput(InputInterface $input) {

      $db_name = $input->getOption('db-name');
      $db_user = $input->getOption('db-user');
      $db_pass = $input->getOption('db-password');

      if (empty($db_name)) {
         throw new InvalidArgumentException(
            __('Database name defined by --db-name option cannot be empty.')
         );
      }

      if (empty($db_user)) {
         throw new InvalidArgumentException(
            __('Database user defined by --db-user option cannot be empty.')
         );
      }

      if (null === $db_pass) {
         // Will be null if option used without value and without interaction
         throw new InvalidArgumentException(
            __('--db-password option value cannot be null.')
         );
      }
   }

   /**
    * Ask user to confirm DB configuration.
    *
    * @param InputInterface $input
    * @param OutputInterface $output
    * @param string $db_hostport DB host and port
    * @param string $db_name DB name
    * @param string $db_user DB username
    *
    * @return boolean
    */
   protected function askForDbConfigConfirmation(
      InputInterface $input,
      OutputInterface $output,
      $db_hostport,
      $db_name,
      $db_user) {

      $informations = new Table($output);
      $informations->addRow([__('Database host'), $db_hostport]);
      $informations->addRow([__('Database name'), $db_name]);
      $informations->addRow([__('Database user'), $db_user]);
      $informations->render();

      if ($input->getOption('no-interaction')) {
         // Consider that config is validated if user require no interaction
         return true;
      }

      /** @var \Symfony\Component\Console\Helper\QuestionHelper $question_helper */
      $question_helper = $this->getHelper('question');
      return $question_helper->ask(
         $input,
         $output,
         new ConfirmationQuestion(__('Do you want to continue ?') . ' [Yes/no]', true)
      );
   }
}
