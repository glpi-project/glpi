<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace Glpi\Console\Database;

use DBConnection;
use DBmysql;
use Glpi\Cache\CacheManager;
use Glpi\Console\Traits\TelemetryActivationTrait;
use Glpi\System\Requirement\DbConfiguration;
use GLPIKey;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Toolbox;

class InstallCommand extends AbstractConfigureCommand
{
    use TelemetryActivationTrait;

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
     * Error code returned when failing to create encryption key file.
     *
     * @var integer
     */
    const ERROR_CANNOT_CREATE_ENCRYPTION_KEY_FILE = 8;

    /**
     * Error code returned if DB configuration is not compatible with large indexes.
     *
     * @var integer
     */
    const ERROR_INCOMPATIBLE_DB_CONFIG = 9;

    protected function configure()
    {

        parent::configure();

        $this->setName('database:install');
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

        $this->registerTelemetryActivationOptions($this->getDefinition());
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {

        global $GLPI_CACHE;
        $GLPI_CACHE = (new CacheManager())->getInstallerCacheInstance(); // Use dedicated "installer" cache

        parent::initialize($input, $output);

        $this->outputWarningOnMissingOptionnalRequirements();
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {

        if (
            $this->isDbAlreadyConfigured()
            && $this->isInputContainingConfigValues($input, $output)
            && !$input->getOption('reconfigure')
        ) {
            /** @var \Symfony\Component\Console\Helper\QuestionHelper $question_helper */
            $question_helper = $this->getHelper('question');
            $reconfigure = $question_helper->ask(
                $input,
                $output,
                new ConfirmationQuestion(
                    __('Command input contains configuration options that may override existing configuration.')
                    . PHP_EOL
                    . __('Do you want to reconfigure database?') . ' [Yes/no]',
                    true
                )
            );
            $input->setOption('reconfigure', $reconfigure);
        }

        if (!$this->isDbAlreadyConfigured() || $input->getOption('reconfigure')) {
            parent::interact($input, $output);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $default_language = $input->getOption('default-language');
        $force            = $input->getOption('force');

        if (
            $this->isDbAlreadyConfigured()
            && $this->isInputContainingConfigValues($input, $output)
            && !$input->getOption('reconfigure')
        ) {
           // Prevent overriding of existing DB when input contains configuration values and
           // --reconfigure option is not used.
            $output->writeln(
                '<error>' . __('Database configuration already exists. Use --reconfigure option to override existing configuration.') . '</error>'
            );
            return self::ERROR_DB_CONFIG_ALREADY_SET;
        }

        if (!$this->isDbAlreadyConfigured() || $input->getOption('reconfigure')) {
            $this->configureDatabase($input, $output, false);

            // Ensure global $DB is updated (used by GLPIKey)
            global $DB;
            $DB = $this->db;

            $db_host     = $input->getOption('db-host');
            $db_port     = $input->getOption('db-port');
            $db_hostport = $db_host . (!empty($db_port) ? ':' . $db_port : '');
            $db_name     = $input->getOption('db-name');
            $db_user     = $input->getOption('db-user');
            $db_pass     = $input->getOption('db-password');
        } else {
           // Ask to confirm installation based on existing configuration.
            global $DB;

           // $DB->dbhost can be array when using round robin feature
            $db_hostport = is_array($DB->dbhost) ? $DB->dbhost[0] : $DB->dbhost;

            $hostport = explode(':', $db_hostport);
            $db_host = $hostport[0];
            if (count($hostport) < 2) {
               // Host only case
                $db_port = null;
            } else {
               // Host:port case or :Socket case
                $db_port = $hostport[1];
            }

            $db_name = $DB->dbdefault;
            $db_user = $DB->dbuser;
            $db_pass = rawurldecode($DB->dbpassword); //rawurldecode as in DBmysql::connect()

            $this->askForDbConfigConfirmation(
                $input,
                $output,
                $db_hostport,
                $db_name,
                $db_user
            );

            $this->db = $DB;
        }

       // Create security key
        $glpikey = new GLPIKey();
        if (!$glpikey->generate()) {
            $message = __('Security key cannot be generated!');
            $output->writeln('<error>' . $message . '</error>', OutputInterface::VERBOSITY_QUIET);
            return self::ERROR_CANNOT_CREATE_ENCRYPTION_KEY_FILE;
        }

        mysqli_report(MYSQLI_REPORT_OFF);
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

       // Check for compatibility with utf8mb4 usage.
        $db = new class ($mysqli) extends DBmysql {
            public function __construct($dbh)
            {
                  $this->dbh = $dbh;
            }
        };
        $config_requirement = new DbConfiguration($db);
        if (!$config_requirement->isValidated()) {
            $msg = '<error>' . __('Database configuration is not compatible with "utf8mb4" usage.') . '</error>';
            foreach ($config_requirement->getValidationMessages() as $validation_message) {
                $msg .= "\n" . '<error> - ' . $validation_message . '</error>';
            }
            throw new \Glpi\Console\Exception\EarlyExitException($msg, self::ERROR_INCOMPATIBLE_DB_CONFIG);
        }

        DBConnection::setConnectionCharset($mysqli, true);

       // Create database or select existing one
        $output->writeln(
            '<comment>' . __('Creating the database...') . '</comment>',
            OutputInterface::VERBOSITY_VERBOSE
        );
        if (
            !$mysqli->query('CREATE DATABASE IF NOT EXISTS `' . $db_name . '`')
            || !$mysqli->select_db($db_name)
        ) {
            $message = sprintf(
                __('Database creation failed with message "(%s) %s".'),
                $mysqli->errno,
                $mysqli->error
            );
            $output->writeln('<error>' . $message . '</error>', OutputInterface::VERBOSITY_QUIET);
            return self::ERROR_DB_CREATION_FAILED;
        }

       // Prevent overriding of existing DB
        $tables_result = $mysqli->query(
            "SELECT COUNT(table_name)
          FROM information_schema.tables
          WHERE table_schema = '{$db_name}'
             AND table_type = 'BASE TABLE'
             AND table_name LIKE 'glpi\_%'"
        );
        if (!$tables_result) {
            throw new \Symfony\Component\Console\Exception\RuntimeException('Unable to check GLPI tables existence.');
        }
        if ($tables_result->fetch_array()[0] > 0 && !$force) {
            $output->writeln(
                '<error>' . __('Database already contains "glpi_*" tables. Use --force option to override existing database.') . '</error>'
            );
            return self::ERROR_DB_ALREADY_CONTAINS_TABLES;
        }

        $output->writeln(
            '<comment>' . __('Loading default schema...') . '</comment>',
            OutputInterface::VERBOSITY_VERBOSE
        );
       // TODO Get rid of output buffering
        ob_start();
        $this->db->connect(); // Reconnect DB to ensure it uses update configuration (see `self::configureDatabase()`)
        Toolbox::createSchema($default_language, $this->db);
        $message = ob_get_clean();
        if (!empty($message)) {
            $output->writeln('<error>' . $message . '</error>', OutputInterface::VERBOSITY_QUIET);
            return self::ERROR_SCHEMA_CREATION_FAILED;
        }

        $output->writeln('<info>' . __('Installation done.') . '</info>');

        (new CacheManager())->resetAllCaches(); // Ensure cache will not use obsolete data

        $this->handTelemetryActivation($input, $output);

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
    private function shouldSetDBConfig(InputInterface $input, OutputInterface $output)
    {

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
    private function isInputContainingConfigValues(InputInterface $input, OutputInterface $output)
    {

        $config_options = [
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
