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
use Glpi\Console\Command\ForceNoPluginsOptionCommandInterface;
use Plugin;
use Session;
use Toolbox;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Exception\CommandNotFoundException;
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
      $this->initSession();
      $this->initCache();
      $this->initConfig();

      $this->computeAndLoadOutputLang();

      // Load core commands only to check if called command prevent or not usage of plugins
      // Plugin commands will be loaded later
      $loader = new CommandLoader(false);
      $this->setCommandLoader($loader);

      $use_plugins = $this->usePlugins();
      if ($use_plugins) {
         $this->loadActivePlugins();
         $loader->registerPluginsCommands();
      }
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
               '--config-dir',
               null,
               InputOption::VALUE_OPTIONAL,
               __('Configuration directory to use')
            ),
            new InputOption(
               '--no-plugins',
               null,
               InputOption::VALUE_NONE,
               __('Disable GLPI plugins (unless commands forces plugins loading)')
            ),
            new InputOption(
               '--lang',
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

   protected function doRunCommand(Command $command, InputInterface $input, OutputInterface $output) {

      $begin_time = microtime(true);

      parent::doRunCommand($command, $input, $output);

      if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
         $output->writeln(
            sprintf(
               __('Time elapsed: %s.'),
               Helper::formatTime(microtime(true) - $begin_time)
            )
         );
         $output->writeln(
            sprintf(
               __('Memory usage: %s.'),
               Helper::formatMemory(memory_get_peak_usage(true))
            )
         );
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
    * Initialize GLPI session.
    * This is mandatory to init cache and load languages.
    *
    * @TODO Do not use session for console.
    *
    * @return void
    */
   private function initSession() {

      if (!is_writable(GLPI_SESSION_DIR)) {
         throw new RuntimeException(
            sprintf(__('Cannot write in "%s" directory.'), GLPI_SESSION_DIR)
         );
      }

      Session::setPath();
      Session::start();

      // Default value for use mode
      $_SESSION['glpi_use_mode'] = Session::NORMAL_MODE;
   }

   /**
    * Initialize GLPI cache.
    *
    * @global Zend\Cache\Storage\StorageInterface $GLPI_CACHE
    *
    * @return void
    */
   private function initCache() {

      global $GLPI_CACHE;
      $GLPI_CACHE = Config::getCache('cache_db');
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

      Config::loadLegacyConfiguration(false);
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

      $_SESSION['glpilanguage'] = $lang;

      Session::loadLanguage('', $this->usePlugins());
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

   /**
    * Whether or not plugins have to be used.
    *
    * @return boolean
    */
   private function usePlugins() {

      $input = new ArgvInput();

      try {
         $command = $this->get($this->getCommandName($input));
         if ($command instanceof ForceNoPluginsOptionCommandInterface) {
            return !$command->getNoPluginsOptionValue();
         }
      } catch (CommandNotFoundException $e) {
         // Command will not be found at this point if it is a plugin command
         $command = null; // Say hello to CS checker
      }

      return !$input->hasParameterOption('--no-plugins', true);
   }
}
