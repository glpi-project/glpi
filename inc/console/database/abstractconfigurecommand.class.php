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

namespace Glpi\Console\Database;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use Config;
use DBConnection;
use DBmysql;
use Glpi\Console\AbstractCommand;
use Glpi\Console\Command\ForceNoPluginsOptionCommandInterface;
use Glpi\System\Requirement\DbTimezones;
use mysqli;
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

      $this->addOption(
         'log-deprecation-warnings',
         null,
         InputOption::VALUE_NONE,
         __('Indicated if deprecation warnings sent by database server should be logged')
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
    * @param bool $auto_config_flags
    * @param bool $use_utf8mb4
    * @param bool $allow_myisam
    * @param bool $allow_datetime
    *
    * @throws InvalidArgumentException
    *
    * @return string
    */
   protected function configureDatabase(
      InputInterface $input,
      OutputInterface $output,
      bool $auto_config_flags = true,
      bool $use_utf8mb4 = false,
      bool $allow_myisam = true,
      bool $allow_datetime = true
   ) {

      $db_pass     = $input->getOption('db-password');
      $db_host     = $input->getOption('db-host');
      $db_name     = $input->getOption('db-name');
      $db_port     = $input->getOption('db-port');
      $db_user     = $input->getOption('db-user');
      $db_hostport = $db_host . (!empty($db_port) ? ':' . $db_port : '');

      $reconfigure    = $input->getOption('reconfigure');
      $log_deprecation_warnings = $input->getOption('log-deprecation-warnings');

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

      $mysqli = new mysqli();
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

      if ($auto_config_flags) {
         // Instanciate DB to be able to compute boolean properties flags.
         $db = new class($db_hostport, $db_user, $db_pass, $db_name) extends DBmysql {
            public function __construct($dbhost, $dbuser, $dbpassword, $dbdefault) {
               $this->dbhost     = $dbhost;
               $this->dbuser     = $dbuser;
               $this->dbpassword = $dbpassword;
               $this->dbdefault  = $dbdefault;
               parent::__construct();
            }
         };
         $config_flags = $db->getComputedConfigBooleanFlags();
         $use_utf8mb4 = $config_flags[DBConnection::PROPERTY_USE_UTF8MB4] ?? $use_utf8mb4;
         $allow_myisam = $config_flags[DBConnection::PROPERTY_ALLOW_MYISAM] ?? $allow_myisam;
         $allow_datetime = $config_flags[DBConnection::PROPERTY_ALLOW_DATETIME] ?? $allow_datetime;
      }

      DBConnection::setConnectionCharset($mysqli, $use_utf8mb4);

      $are_timezones_available = $this->checkTimezonesAvailability($mysqli);
      $use_timezones = !$allow_datetime && $are_timezones_available;

      $db_name = $mysqli->real_escape_string($db_name);

      $output->writeln(
         '<comment>' . __('Saving configuration file...') . '</comment>',
         OutputInterface::VERBOSITY_VERBOSE
      );
      $result = DBConnection::createMainConfig(
         $db_hostport,
         $db_user,
         $db_pass,
         $db_name,
         $use_timezones,
         $log_deprecation_warnings,
         $use_utf8mb4,
         $allow_myisam,
         $allow_datetime
      );
      if (!$result) {
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

      // Set $db instance to use new connection properties
      $this->db = new class(
         $db_hostport,
         $db_user,
         $db_pass,
         $db_name,
         $use_timezones,
         $log_deprecation_warnings,
         $use_utf8mb4,
         $allow_myisam,
         $allow_datetime
      ) extends DBmysql {
         public function __construct(
            $dbhost,
            $dbuser,
            $dbpassword,
            $dbdefault,
            $use_timezones,
            $log_deprecation_warnings,
            $use_utf8mb4,
            $allow_myisam,
            $allow_datetime
         ) {
            $this->dbhost     = $dbhost;
            $this->dbuser     = $dbuser;
            $this->dbpassword = $dbpassword;
            $this->dbdefault  = $dbdefault;

            $this->use_timezones  = $use_timezones;
            $this->use_utf8mb4    = $use_utf8mb4;
            $this->allow_myisam   = $allow_myisam;
            $this->allow_datetime = $allow_datetime;

            $this->log_deprecation_warnings = $log_deprecation_warnings;

            $this->clearSchemaCache();

            parent::__construct();
         }
      };

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
         throw new \Symfony\Component\Console\Exception\InvalidArgumentException(
            __('Database name defined by --db-name option cannot be empty.')
         );
      }

      if (empty($db_user)) {
         throw new \Symfony\Component\Console\Exception\InvalidArgumentException(
            __('Database user defined by --db-user option cannot be empty.')
         );
      }

      if (null === $db_pass) {
         // Will be null if option used without value and without interaction
         throw new \Symfony\Component\Console\Exception\InvalidArgumentException(
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
         new ConfirmationQuestion(__('Do you want to continue?') . ' [Yes/no]', true)
      );
   }

   /**
    * Check timezones availability and return availability state.
    *
    * @param mysqli $mysqli
    *
    * @return bool
    */
   private function checkTimezonesAvailability(mysqli $mysqli): bool {

      $db = new class($mysqli) extends DBmysql {
         public function __construct($dbh) {
            $this->dbh = $dbh;
         }
      };
      $timezones_requirement = new DbTimezones($db);

      if (!$timezones_requirement->isValidated()) {
         $message = __('Timezones usage cannot be activated due to following errors:');
         foreach ($timezones_requirement->getValidationMessages() as $validation_message) {
            $message .= "\n - " . $validation_message;
         }
         $this->output->writeln(
            '<comment>' . $message . '</comment>',
            OutputInterface::VERBOSITY_QUIET
         );
         if ($this->input->getOption('no-interaction')) {
            $message = sprintf(
               __('Fix them and run the "php bin/console %1$s" command to enable timezones.'),
               'glpi:database:enable_timezones'
            );
            $this->output->writeln('<comment>' . $message . '</comment>', OutputInterface::VERBOSITY_QUIET);
         } else {
            /** @var \Symfony\Component\Console\Helper\QuestionHelper $question_helper */
            $question_helper = $this->getHelper('question');
            $continue = $question_helper->ask(
               $this->input,
               $this->output,
               new ConfirmationQuestion(__('Do you want to continue?') . ' [Yes/no]', true)
            );
            if (!$continue) {
               throw new \Glpi\Console\Exception\EarlyExitException(
                  '<comment>' . __('Configuration aborted.') . '</comment>',
                  self::ABORTED_BY_USER
               );
            }
         }
      }

      return $timezones_requirement->isValidated();
   }
}
