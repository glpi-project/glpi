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

namespace Glpi\Console;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use Config;
use DB;
use GLPI;
use Plugin;
use Session;
use Toolbox;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;

class Application extends BaseApplication {

   /**
    * Pointer to $CFG_GLPI.
    * @var array
    */
   private $config;

   /**
    * @var DB
    */
   private $db;

   public function __construct() {

      parent::__construct('GLPI CLI', GLPI_VERSION);

      $this->initApplication();
      $this->initDb();
      $this->initConfig();

      $this->computeAndLoadOutputLang();

      $this->loadActivePlugins();

      $this->setCommandLoader(new CommandLoader());
   }

   protected function getDefaultInputDefinition() {

      $definition = new InputDefinition(
         [
            new InputArgument(
               'command',
               InputArgument::REQUIRED,
               __('The command to execute')
            ),

            new InputOption(
               '--help',
               '-h',
               InputOption::VALUE_NONE,
               __('Display this help message')
            ),
            new InputOption(
               '--quiet',
               '-q',
               InputOption::VALUE_NONE,
               __('Do not output any message')
            ),
            new InputOption(
               '--verbose',
               '-v|vv|vvv',
               InputOption::VALUE_NONE,
               __('Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug')
            ),
            new InputOption(
               '--version',
               '-V',
               InputOption::VALUE_NONE,
               __('Display this application version')
            ),
            new InputOption(
               '--ansi',
               null,
               InputOption::VALUE_NONE,
               __('Force ANSI output')
            ),
            new InputOption(
               '--no-ansi',
               null,
               InputOption::VALUE_NONE,
               __('Disable ANSI output')
            ),
            new InputOption(
               '--no-interaction',
               '-n',
               InputOption::VALUE_NONE,
               __('Do not ask any interactive question')
            ),
            new InputOption(
               'config-dir',
               null,
               InputOption::VALUE_OPTIONAL,
               __('Configuration directory to use')
            ),
            new InputOption(
               'lang',
               null,
               InputOption::VALUE_OPTIONAL,
               __('Output language (default value is existing GLPI "language" configuration or "en_GB")')
            )
         ]
      );

      return $definition;
   }

   protected function configureIO(InputInterface $input, OutputInterface $output) {

      global $CFG_GLPI;

      parent::configureIO($input, $output);

      // Trigger error on invalid lang. This is not done before as error handler would not be set.
      $lang = $input->getParameterOption('--lang', null, true);
      if (null !== $lang && !array_key_exists($lang, $CFG_GLPI['languages'])) {
         throw new RuntimeException(
            sprintf(__('Invalid "--lang" option value "%s".'), $lang)
         );
      }

      if ($output->getVerbosity() === OutputInterface::VERBOSITY_DEBUG) {
         // TODO Find a way to route errors to console output in a clean format.
         Toolbox::setDebugMode(Session::DEBUG_MODE, 1, 1, 1);
      }
   }

   /**
    * Initalize GLPI.
    *
    * @global array $CFG_GLPI
    * @global GLPI  $GLPI
    *
    * @return void
    */
   private function initApplication() {

      // Disable debug at bootstrap (will be re-enabled later if requested by verbosity level).
      global $CFG_GLPI;
      $CFG_GLPI = array_merge(
         $CFG_GLPI,
         [
            'debug_sql'  => 0,
            'debug_vars' => 0,
         ]
      );

      global $GLPI;
      $GLPI = new GLPI();
      $GLPI->initLogger();

      Config::detectRootDoc();
   }

   /**
    * Initialize database connection.
    *
    * @global DB $DB
    *
    * @return void
    *
    * @throws RuntimeException
    */
   private function initDb() {

      if (!class_exists('DB', false)) {
         return;
      }

      global $DB;
      $DB = new DB();
      $this->db = $DB;

      if (!$this->db->connected) {
         return;
      }

      ob_start();
      $checkdb = Config::displayCheckDbEngine();
      $message = ob_get_clean();
      if ($checkdb > 0) {
         throw new RuntimeException($message);
      }
   }

   /**
    * Initialize GLPI configuration.
    *
    * @global array $CFG_GLPI
    *
    * @return void
    */
   private function initConfig() {

      global $CFG_GLPI;
      $this->config = &$CFG_GLPI;

      if (!($this->db instanceof DB) || !$this->db->connected) {
         return;
      }

      $config_values = null;

      $config = new Config();
      if ($this->db->tableExists('glpi_configs')
          && $this->db->fieldExists('glpi_configs', 'value')) {
         // GLPI >= 0.85
         $config_values = Config::getConfigurationValues('core');
      } else {
         if (!$this->db->tableExists('glpi_configs')) {
            // GLPI < 0.78
            $config->forceTable('glpi_config');
         }

         if ($config->getFromDB(1)) {
            throw new RuntimeException(__('Unable to load GLPI configuration.'));
         }
         $config_values = $config->fields;
      }

      global $CFG_GLPI;
      $CFG_GLPI = array_merge($CFG_GLPI, $config_values);
      $this->config = &$CFG_GLPI;
   }

   /**
    * Compute and load output language.
    *
    * @return void
    *
    * @throws RuntimeException
    */
   private function computeAndLoadOutputLang() {

      // 1. Check in command line arguments
      $input = new ArgvInput();
      $lang = $input->getParameterOption('--lang', null, true);

      if (null !== $lang && !$this->isLanguageValid($lang)) {
         // Unset requested lang if invalid
         $lang = null;
      }

      // 2. Check in GLPI configuration
      if (null === $lang && array_key_exists('language', $this->config)
          && $this->isLanguageValid($this->config['language'])) {
         $lang = $this->config['language'];
      }

      // 3. Use default value
      if (null === $lang) {
         $lang = 'en_GB';
      }

      // Initialize session. This is mandatory to load languages.
      // TODO Do not use session for console.
      if (!is_writable(GLPI_SESSION_DIR)) {
         throw new RuntimeException(
            sprintf(__('Cannot write in "%s" directory.'), GLPI_SESSION_DIR)
         );
      }

      Session::setPath();
      Session::start();

      $_SESSION['glpilanguage'] = $lang;

      Session::loadLanguage();
   }

   /**
    * Check if a language is valid.
    *
    * @param string $language
    *
    * @return boolean
    */
   private function isLanguageValid($language) {
      return is_array($this->config)
         && array_key_exists('languages', $this->config)
         && array_key_exists($language, $this->config['languages']);
   }

   /**
    * Load active plugins.
    *
    * @return void
    */
   private function loadActivePlugins() {

      if (!($this->db instanceof DB) || !$this->db->connected) {
         return;
      }

      $plugin = new Plugin();
      $plugin->init();

      $plugins_list = $plugin->getPlugins();
      if (count($plugins_list) > 0) {
         foreach ($plugins_list as $name) {
            Plugin::load($name);
         }
         // For plugins which require action after all plugin init
         Plugin::doHook("post_init");
      }
   }
}
