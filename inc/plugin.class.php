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

/**
 * Based on cacti plugin system
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use Psr\SimpleCache\CacheInterface;
use Glpi\Marketplace\View as MarketplaceView;
use Glpi\Marketplace\Controller as MarketplaceController;

class Plugin extends CommonDBTM {

   // Class constant : Plugin state
   /**
    * @var int Unknown plugin state
    */
   const UNKNOWN        = -1;

   /**
    * @var int Plugin was discovered but not installed
    *
    * @note Plugins are never actually set to this status?
    */
   const ANEW           = 0;

   /**
    * @var int Plugin is installed and enabled
    */
   const ACTIVATED      = 1;

   /**
    * @var int Plugin is not installed
    */
   const NOTINSTALLED   = 2;

   /**
    * @var int Plugin is installed but needs configured before it can be enabled
    */
   const TOBECONFIGURED = 3;

   /**
    * @var int Plugin is installed but not enabled
    */
   const NOTACTIVATED   = 4;

   /**
    * @var int Plugin was previously discovered, but the plugin directory is missing now. The DB needs cleaned.
    */
   const TOBECLEANED    = 5;

   /**
    * @var int The plugin's files are for a newer version than installed. An update is needed.
    */
   const NOTUPDATED     = 6;

   static $rightname = 'config';

   /**
    * Plugin init state.
    *
    * @var boolean
    */
   private static $plugins_init = false;

   /**
    * Activated plugin list
    *
    * @var string[]
    */
   private static $activated_plugins = [];

   /**
    * Loaded plugin list
    *
    * @var string[]
    */
   private static $loaded_plugins = [];

   static function getTypeName($nb = 0) {
      return _n('Plugin', 'Plugins', $nb);
   }


   static function getMenuName() {
      return static::getTypeName(Session::getPluralNumber());
   }


   static function getMenuContent() {
      $menu = parent::getMenuContent() ?: [];

      if (static::canView()) {
         $redirect_mp = MarketplaceController::getPluginPageConfig();

         $menu['title'] = self::getMenuName();
         $menu['page']  = $redirect_mp == MarketplaceController::MP_REPLACE_YES
                           ? '/front/marketplace.php'
                           : '/front/plugin.php';
         $menu['icon']  = self::getIcon();
      }
      if (count($menu)) {
         return $menu;
      }
      return false;
   }


   static function getAdditionalMenuLinks() {
      if (!static::canView()) {
         return false;
      }
      $mp_icon     = MarketplaceView::getIcon();
      $mp_title    = MarketplaceView::getTypeName();
      $marketplace = "<i class='$mp_icon pointer' title='$mp_title'></i><span class='sr-only'>$mp_title</span>";

      $cl_icon     = Plugin::getIcon();
      $cl_title    = Plugin::getTypeName();
      $classic     = "<i class='$cl_icon pointer' title='$cl_title'></i><span class='sr-only'>$cl_title</span>";

      return [
         $marketplace => MarketplaceView::getSearchURL(false),
         $classic     => Plugin::getSearchURL(false),
      ];

   }


   static function getAdditionalMenuOptions() {
      if (static::canView()) {
         return [
            'marketplace' => [
               'icon'  => MarketplaceView::geticon(),
               'title' => MarketplaceView::getTypeName(),
               'page'  => MarketplaceView::getSearchURL(false),
            ]
         ];
      }
   }


   /**
    * Retrieve an item from the database using its directory
    *
    * @param string $dir directory of the plugin
    *
    * @return boolean
   **/
   function getFromDBbyDir($dir) {
      return $this->getFromDBByCrit([$this->getTable() . '.directory' => $dir]);
   }


   /**
    * Init plugins list.
    *
    * @param boolean $load_plugins     Whether to load active/configurable plugins or not.
    * @param array $excluded_plugins   List of plugins to exclude
    *
    * @return void
   **/
   function init(bool $load_plugins = false, array $excluded_plugins = []) {
      global $DB;

      self::$plugins_init   = false;
      self::$activated_plugins = [];
      self::$loaded_plugins = [];

      if (!isset($DB) || !$DB->connected) {
         // Cannot init plugins list if DB is not connected
         self::$plugins_init = true;
         return;
      }

      $this->checkStates(false, $excluded_plugins);

      $plugins = $this->find(['state' => [self::ACTIVATED, self::TOBECONFIGURED]]);

      self::$plugins_init = true;

      if ($load_plugins && count($plugins)) {
         foreach ($plugins as $plugin) {
            if (in_array($plugin['directory'], $excluded_plugins)) {
               continue;
            }

            if (!$this->isLoadable($plugin['directory'])) {
               continue;
            }

            Plugin::load($plugin['directory']);

            if ((int)$plugin['state'] === self::ACTIVATED) {
               self::$activated_plugins[] = $plugin['directory'];
            }
         }
         // For plugins which require action after all plugin init
         Plugin::doHook("post_init");
      }
   }


   /**
    * Init a plugin including setup.php file
    * launching plugin_init_NAME function  after checking compatibility
    *
    * @param string  $plugin_key        System name (Plugin directory)
    * @param boolean $withhook   Load hook functions (false by default)
    *
    * @return void
   **/
   static function load($plugin_key, $withhook = false) {
      global $LOADED_PLUGINS;

      $loaded = false;
      foreach (PLUGINS_DIRECTORIES as $base_dir) {
         if (!is_dir($base_dir)) {
            continue;
         }

         if (file_exists("$base_dir/$plugin_key/setup.php")) {
            $loaded = true;
            $plugin_directory = "$base_dir/$plugin_key";
            include_once("$plugin_directory/setup.php");
            if (!in_array($plugin_key, self::$loaded_plugins)) {
               self::$loaded_plugins[] = $plugin_key;
               $init_function = "plugin_init_$plugin_key";
               if (function_exists($init_function)) {
                  $init_function();
                  $LOADED_PLUGINS[$plugin_key] = $plugin_directory;
                  self::loadLang($plugin_key);
               }
            }
         }
         if ($withhook) {
            self::includeHook($plugin_key);
         }

         if ($loaded) {
            break;
         }
      }
   }

   /**
    * Unload a plugin.
    *
    * @param string  $plugin_key  System name (Plugin directory)
    *
    * @return void
    */
   private function unload($plugin_key) {
      global $LOADED_PLUGINS;

      if (($key = array_search($plugin_key, self::$activated_plugins)) !== false) {
         unset(self::$activated_plugins[$key]);
      }

      if (($key = array_search($plugin_key, self::$loaded_plugins)) !== false) {
         unset(self::$loaded_plugins[$key]);
      }

      if (isset($LOADED_PLUGINS[$plugin_key])) {
         unset($LOADED_PLUGINS[$plugin_key]);
      }
   }


   /**
    * Load lang file for a plugin
    *
    * @param string $plugin_key    System name (Plugin directory)
    * @param string $forcelang     Force a specific lang (default '')
    * @param string $coretrytoload Lang trying to be loaded from core (default '')
    *
    * @return void
   **/
   static function loadLang($plugin_key, $forcelang = '', $coretrytoload = '') {
      global $CFG_GLPI, $TRANSLATE;

      // For compatibility for plugins using $LANG
      $trytoload = 'en_GB';
      if (isset($_SESSION['glpilanguage'])) {
         $trytoload = $_SESSION["glpilanguage"];
      }
      // Force to load a specific lang
      if (!empty($forcelang)) {
         $trytoload = $forcelang;
      }

      // If not set try default lang file
      if (empty($trytoload)) {
         $trytoload = $CFG_GLPI["language"];
      }

      if (empty($coretrytoload)) {
            $coretrytoload = $trytoload;
      }

      // New localisation system
      $mofile = false;
      foreach (PLUGINS_DIRECTORIES as $base_dir) {
         if (!is_dir($base_dir)) {
            continue;
         }
         $locales_dir = "$base_dir/$plugin_key/locales/";
         if (file_exists($locales_dir.$CFG_GLPI["languages"][$trytoload][1])) {
            $mofile = $locales_dir.$CFG_GLPI["languages"][$trytoload][1];
         } else if (!empty($CFG_GLPI["language"])
                    && file_exists($locales_dir.$CFG_GLPI["languages"][$CFG_GLPI["language"]][1])) {
            $mofile = $locales_dir.$CFG_GLPI["languages"][$CFG_GLPI["language"]][1];
         } else if (file_exists($locales_dir."en_GB.mo")) {
            $mofile = $locales_dir."en_GB.mo";
         }

         if ($mofile !== false) {
            break;
         }
      }

      if ($mofile !== false) {
         $TRANSLATE->addTranslationFile(
            'gettext',
            $mofile,
            $plugin_key,
            $coretrytoload
         );
      }

      $plugin_folders = scandir(GLPI_LOCAL_I18N_DIR);
      $plugin_folders = array_filter($plugin_folders, function($dir) use ($plugin_key) {
         if (!is_dir(GLPI_LOCAL_I18N_DIR . "/$dir")) {
            return false;
         }

         if ($dir == $plugin_key) {
            return true;
         }

         return Toolbox::startsWith($dir, $plugin_key . '_');
      });

      foreach ($plugin_folders as $plugin_folder) {
         $mofile = str_replace($locales_dir, GLPI_LOCAL_I18N_DIR . '/'. $plugin_folder . '/', $mofile);
         $phpfile = str_replace('.mo', '.php', $mofile);

         // Load local PHP file if it exists
         if (file_exists($phpfile)) {
            $TRANSLATE->addTranslationFile('phparray', $phpfile, $plugin_key, $coretrytoload);
         }

         // Load local MO file if it exists -- keep last so it gets precedence
         if (file_exists($mofile)) {
            $TRANSLATE->addTranslationFile('gettext', $mofile, $plugin_key, $coretrytoload);
         }
      }
   }


   /**
    * Check plugins states and detect new plugins.
    *
    * @param boolean $scan_inactive_and_new_plugins
    * @param array $excluded_plugins   List of plugins to exclude
    *
    * @return void
    */
   public function checkStates($scan_inactive_and_new_plugins = false, array $excluded_plugins = []) {

      $directories = [];

      // Add known plugins to the check list
      $condition = $scan_inactive_and_new_plugins ? [] : ['state' => self::ACTIVATED];
      $known_plugins = $this->find($condition);
      foreach ($known_plugins as $plugin) {
         $directories[] = $plugin['directory'];
      }

      if ($scan_inactive_and_new_plugins) {
         // Add found directories to the check list
         foreach (PLUGINS_DIRECTORIES as $plugins_directory) {
            if (!is_dir($plugins_directory)) {
               continue;
            }
            $directory_handle  = opendir($plugins_directory);
            while (false !== ($filename = readdir($directory_handle))) {
               if (!in_array($filename, ['.svn', '.', '..'])
                   && is_dir($plugins_directory . DIRECTORY_SEPARATOR . $filename)) {
                   $directories[] = $filename;
               }
            }
         }
      }

      // Prevent duplicated checks
      $directories = array_unique($directories);

      // Check all directories from the checklist
      foreach ($directories as $directory) {
         if (in_array($directory, $excluded_plugins)) {
            continue;
         }
         $this->checkPluginState($directory);
      }
   }


   /**
    * Check plugin state.
    *
    * @param string $plugin_key System name (Plugin directory)
    *
    * return void
    */
   public function checkPluginState($plugin_key) {

      $plugin = new self();
      $is_already_known = $plugin->getFromDBByCrit(['directory' => $plugin_key]);

      $informations = $this->getInformationsFromDirectory($plugin_key);

      if (empty($informations)) {
         if (!$is_already_known) {
            // Plugin is not known and we are unable to load information, we ignore it
            return;
         }

         // Try to get information from a plugin that lists current name as its old name
         // If something found, and not already registerd in DB,, base plugin information on it
         // If nothing found, mark plugin as "To be cleaned"
         $new_specs = $this->getNewInfoAndDirBasedOnOldName($plugin_key);
         if (null !== $new_specs
             && countElementsInTable(self::getTable(), ['directory' => $new_specs['directory']]) === 0) {
            $plugin_key    = $new_specs['directory'];
            $informations = $new_specs['informations'];
         } else {
            trigger_error(
               sprintf(
                  'Unable to load plugin "%s" information.',
                  $plugin_key
               ),
               E_USER_WARNING
            );
            // Plugin is known but we are unable to load information, we ignore it
            return;
         }
      }

      if (!$is_already_known && array_key_exists('oldname', $informations)) {
         // Plugin not known but was named differently before, we try to load state using old name
         $is_already_known = $plugin->getFromDBByCrit(['directory' => $informations['oldname']]);
      }

      if (!$is_already_known) {
         // Plugin not known, add it in DB
         $this->add(
            array_merge(
               $informations,
               [
                  'state'     => self::NOTINSTALLED,
                  'directory' => $plugin_key,
               ]
            )
         );
         return;
      }

      if ($informations['version'] != $plugin->fields['version']
          || $plugin_key != $plugin->fields['directory']) {
         // Plugin known version differs from information or plugin has been renamed,
         // update information in database
         $input              = $informations;
         $input['id']        = $plugin->fields['id'];
         $input['directory'] = $plugin_key;
         if (!in_array($plugin->fields['state'], [self::ANEW, self::NOTINSTALLED, self::NOTUPDATED])) {
            // mark it as 'updatable' unless it was not installed
            trigger_error(
               sprintf(
                  'Plugin "%s" version changed. It has been deactivated as its update process has to be launched.',
                  $plugin_key
               ),
               E_USER_WARNING
            );

            $input['state']     = self::NOTUPDATED;
         }

         $this->update($input);

         $this->unload($plugin_key);
         // reset menu
         if (isset($_SESSION['glpimenu'])) {
            unset($_SESSION['glpimenu']);
         }

         return;
      }

      // Check if configuration state changed
      if (in_array((int)$plugin->fields['state'], [self::ACTIVATED, self::TOBECONFIGURED, self::NOTACTIVATED], true)) {
         $function = 'plugin_' . $plugin_key . '_check_config';
         $is_config_ok = !function_exists($function) || $function();

         if ((int)$plugin->fields['state'] === self::TOBECONFIGURED && $is_config_ok) {
            // Remove TOBECONFIGURED state if configuration is OK now
            $this->update(
               [
                  'id'    => $plugin->fields['id'],
                  'state' => self::NOTACTIVATED
               ]
            );
            return;
         } else if ((int)$plugin->fields['state'] !== self::TOBECONFIGURED && !$is_config_ok) {
            // Add TOBECONFIGURED state if configuration is required
            trigger_error(
               sprintf(
                  'Plugin "%s" must be configured.',
                  $plugin_key
               ),
               E_USER_WARNING
            );
            $this->update(
               [
                  'id'    => $plugin->fields['id'],
                  'state' => self::TOBECONFIGURED
               ]
            );
            return;
         }
      }

      if (self::ACTIVATED !== (int)$plugin->fields['state']) {
         // Plugin is not activated, nothing to do
         return;
      }

      // Check that active state of plugin can be kept
      $usage_ok = true;

      // Check compatibility
      ob_start();
      if (!$this->checkVersions($plugin_key)) {
         $usage_ok = false;
      }
      ob_end_clean();

      // Check prerequisites
      if ($usage_ok) {
         $function = 'plugin_' . $plugin_key . '_check_prerequisites';
         if (function_exists($function)) {
            ob_start();
            if (!$function()) {
               $usage_ok = false;
            }
            ob_end_clean();
         }
      }

      if (!$usage_ok) {
         // Deactivate if not usable
         trigger_error(
            sprintf(
               'Plugin "%s" prerequisites are not matched. It has been deactivated.',
               $plugin_key
            ),
            E_USER_WARNING
         );
         $this->unactivate($plugin->fields['id']);
      }
   }


   /**
    * Get plugin information based on its old name.
    *
    * @param string $oldname
    *
    * @return null|array If a new directory is found, returns an array containing 'directory' and 'informations' keys.
    */
   private function getNewInfoAndDirBasedOnOldName($oldname) {

      $plugins_directories = new DirectoryIterator(GLPI_ROOT . '/plugins');
      /** @var SplFileInfo $plugin_directory */
      foreach ($plugins_directories as $plugin_directory) {
         if (in_array($plugin_directory->getFilename(), ['.svn', '.', '..'])
             || !is_dir($plugin_directory->getRealPath())) {
            continue;
         }

         $informations = $this->getInformationsFromDirectory($plugin_directory->getFilename());
         if (array_key_exists('oldname', $informations) && $informations['oldname'] === $oldname) {
            // Return informations if oldname specified in parsed directory matches passed value
            return [
               'directory'    => $plugin_directory->getFilename(),
               'informations' => $informations,
            ];
         }
      }

      return null;
   }

   /**
    * Get list of all plugins
    *
    * @param array $fields Fields to retrieve
    * @param array $order  Query ORDER clause
    *
    * @return array
    */
   public function getList(array $fields = [], array $order = ['name', 'directory']) {
      global $DB;

      $query = [
         'FROM'   => $this->getTable()
      ];

      if (count($fields) > 0) {
         $query['FIELDS'] = $fields;
      }

      if (count($order) > 0) {
         $query['ORDER'] = $order;
      }

      $iterator = $DB->request($query);
      return iterator_to_array($iterator, false);
   }


   /**
    * Uninstall a plugin
    *
    * @param integer $ID ID of the plugin (The `id` field, not directory)
   **/
   function uninstall($ID) {
      $message = '';
      $type = ERROR;

      if ($this->getFromDB($ID)) {
         CronTask::Unregister($this->fields['directory']);
         self::load($this->fields['directory'], true); // Force load in case plugin is not active
         FieldUnicity::deleteForItemtype($this->fields['directory']);
         Link_Itemtype::deleteForItemtype($this->fields['directory']);

         // Run the Plugin's Uninstall Function first
         $function = 'plugin_' . $this->fields['directory'] . '_uninstall';
         if (function_exists($function)) {
            $function();
         } else {
            Session::addMessageAfterRedirect(
               sprintf(__('Plugin %1$s has no uninstall function!'), $this->fields['name']),
               true,
               WARNING
            );
         }

         $this->update([
            'id'      => $ID,
            'state'   => self::NOTINSTALLED,
         ]);
         $this->unload($this->fields['directory']);
         self::doHook('post_plugin_uninstall', $this->fields['directory']);

         $type = INFO;
         $message = sprintf(__('Plugin %1$s has been uninstalled!'), $this->fields['name']);
      } else {
         $message = sprintf(__('Plugin %1$s not found!'), $ID);
      }

      Session::addMessageAfterRedirect(
         $message,
         true,
         $type
      );
   }


   /**
    * Install a plugin
    *
    * @param integer $ID      ID of the plugin (The `id` field, not directory)
    * @param array   $params  Additional params to pass to install hook.
    *
    * @return void
    *
    * @since 9.5.0 Added $param parameter
   **/
   function install($ID, array $params = []) {

      global $DB;

      $message = '';
      $type = ERROR;

      if ($this->getFromDB($ID)) {
         // Clear locale cache to prevent errors while reloading plugin locales
         $translation_cache = Config::getCache('cache_trans', 'core', true);
         if ($translation_cache instanceof CacheInterface) {
            $translation_cache->clear();
         }

         self::load($this->fields['directory'], true); // Load plugin hooks

         $install_function = 'plugin_' . $this->fields['directory'] . '_install';
         if (function_exists($install_function)) {
            $DB->disableTableCaching(); //prevents issues on table/fieldExists upgrading from old versions
            if ($install_function($params)) {
               $type = INFO;
               $check_function = 'plugin_' . $this->fields['directory'] . '_check_config';
               $is_config_ok = !function_exists($check_function) || $check_function();
               if ($is_config_ok) {
                  $this->update(['id'    => $ID,
                                      'state' => self::NOTACTIVATED]);
                  $message = sprintf(__('Plugin %1$s has been installed!'), $this->fields['name']);
                  $message .= '<br/><br/>' . str_replace(
                     '%activate_link',
                     Html::getSimpleForm(static::getFormURL(), ['action' => 'activate'],
                                       mb_strtolower(_x('button', 'Enable')), ['id' => $ID], '', 'class="pointer"'),
                     __('Do you want to %activate_link it?')
                  );
               } else {
                  $this->update(['id'    => $ID,
                                      'state' => self::TOBECONFIGURED]);
                  $message = sprintf(__('Plugin %1$s has been installed and must be configured!'), $this->fields['name']);
               }
               self::doHook('post_plugin_install', $this->fields['directory']);
            }
         } else {
            $type = WARNING;
            $message = sprintf(__('Plugin %1$s has no install function!'), $this->fields['name']);
         }
      } else {
         $message = sprintf(__('Plugin %1$s not found!'), $ID);
      }

      Session::addMessageAfterRedirect(
         $message,
         true,
         $type
      );
   }


   /**
    * activate a plugin
    *
    * @param integer $ID ID of the plugin (The `id` field, not directory)
    *
    * @return boolean about success
   **/
   function activate($ID) {
      global $PLUGIN_HOOKS;

      if ($this->getFromDB($ID)) {

         // Enable autoloader and load plugin hooks
         self::load($this->fields['directory'], true);

         // No activation if not CSRF compliant
         if (!isset($PLUGIN_HOOKS['csrf_compliant'][$this->fields['directory']])
             || !$PLUGIN_HOOKS['csrf_compliant'][$this->fields['directory']]) {
            Session::addMessageAfterRedirect(
               sprintf(__('Plugin %1$s is not CSRF compliant!'), $this->fields['name']),
               true,
               ERROR
            );
            return false;
         }

         $function = 'plugin_' . $this->fields['directory'] . '_check_prerequisites';
         if (function_exists($function)) {
            ob_start();
            $do_activate = $function();
            $msg = '';
            if (!$do_activate) {
               $msg = '<span class="error">' . ob_get_contents() . '</span>';
            }
            ob_end_clean();

            if (!$do_activate) {
               $this->unload($this->fields['directory']);

               Session::addMessageAfterRedirect(
                  sprintf(__('Plugin prerequisites are not matching, it cannot be activated.') . ' ' . $msg, $this->fields['name']),
                  true,
                  ERROR
               );
               return false;
            }
         }

         $function = 'plugin_' . $this->fields['directory'] . '_check_config';
         if (!function_exists($function) || $function()) {
            $this->update(['id'    => $ID,
                           'state' => self::ACTIVATED]);

            // Initialize session for the plugin
            if (isset($PLUGIN_HOOKS['init_session'][$this->fields['directory']])
                && is_callable($PLUGIN_HOOKS['init_session'][$this->fields['directory']])) {

               call_user_func($PLUGIN_HOOKS['init_session'][$this->fields['directory']]);
            }

            // Initialize profile for the plugin
            if (isset($PLUGIN_HOOKS['change_profile'][$this->fields['directory']])
                && is_callable($PLUGIN_HOOKS['change_profile'][$this->fields['directory']])) {

               call_user_func($PLUGIN_HOOKS['change_profile'][$this->fields['directory']]);
            }
            // reset menu
            if (isset($_SESSION['glpimenu'])) {
               unset($_SESSION['glpimenu']);
            }
            self::doHook('post_plugin_enable', $this->fields['directory']);

            Session::addMessageAfterRedirect(
               sprintf(__('Plugin %1$s has been activated!'), $this->fields['name']),
               true,
               INFO
            );

            return true;
         } else {
            $this->unload($this->fields['directory']);

            Session::addMessageAfterRedirect(
               sprintf(__('Plugin configuration must be done, it cannot be activated.') . ' ' . $msg, $this->fields['name']),
               true,
               ERROR
            );
            return false;
         }
      }

      Session::addMessageAfterRedirect(
         sprintf(__('Plugin %1$s not found!'), $ID),
         true,
         ERROR
      );
      return false;
   }


   /**
    * Unactivate a plugin
    *
    * @param integer $ID ID of the plugin (The `id` field, not directory)
    *
    * @return boolean
   **/
   function unactivate($ID) {

      if ($this->getFromDB($ID)) {
         $this->update([
            'id'    => $ID,
            'state' => self::NOTACTIVATED
         ]);
         $this->unload($this->fields['directory']);
         self::doHook('post_plugin_disable', $this->fields['directory']);

         // reset menu
         if (isset($_SESSION['glpimenu'])) {
            unset($_SESSION['glpimenu']);
         }

         Session::addMessageAfterRedirect(
            sprintf(__('Plugin %1$s has been deactivated!'), $this->fields['name']),
            true,
            INFO
         );

         return true;
      }

      Session::addMessageAfterRedirect(
         sprintf(__('Plugin %1$s not found!'), $ID),
         true,
         ERROR
      );

      return false;
   }


   /**
    * Unactivate all activated plugins for update process.
    * This will prevent any plugin class to be available through autoloader.
   **/
   function unactivateAll() {
      global $DB;

      $DB->update(
         $this->getTable(), [
            'state' => self::NOTACTIVATED
         ], [
            'state' => self::ACTIVATED
         ]
      );

      $dirs = array_keys(self::$activated_plugins);
      foreach ($dirs as $dir) {
         self::doHook('post_plugin_disable', $dir);
      }

      self::$activated_plugins = [];
      self::$loaded_plugins = [];

      // reset menu
      if (isset($_SESSION['glpimenu'])) {
         unset($_SESSION['glpimenu']);
      }
   }


   /**
    * clean a plugin
    *
    * @param $ID ID of the plugin
   **/
   function clean($ID) {

      if ($this->getFromDB($ID)) {
         // Clean crontask after "hard" remove
         CronTask::Unregister($this->fields['directory']);

         $this->unload($this->fields['directory']);
         self::doHook('post_plugin_clean', $this->fields['directory']);
         $this->delete(['id' => $ID]);
      }
   }


   /**
    * Is a plugin activated ?
    *
    * @param string $directory  Plugin directory
    *
    * @return boolean
    */
   function isActivated($directory) {
      // Make a lowercase comparison, as sometime this function is called based on
      // extraction of plugin name from a classname, which does not use same naming rules than directories.
      $activated_plugins = array_map('strtolower', self::$activated_plugins);
      if (in_array(strtolower($directory), $activated_plugins)) {
         // If plugin is marked as activated, no need to query DB on this case.
         return true;
      }

      // If plugin is not marked as activated, check on DB as it may have not been loaded yet.
      if ($this->getFromDBbyDir($directory)) {
         return ($this->fields['state'] == self::ACTIVATED) && $this->isLoadable($directory);
      }

      return false;
   }


   /**
    * Is a plugin updatable ?
    *
    * @param string $directory  Plugin directory
    *
    * @return boolean
    */
   function isUpdatable($directory) {
      // Make a lowercase comparison, as sometime this function is called based on
      // extraction of plugin name from a classname, which does not use same naming rules than directories.
      $activated_plugins = array_map('strtolower', self::$activated_plugins);
      if (in_array(strtolower($directory), $activated_plugins)) {
         // If plugin is marked as activated, no need to query DB on this case.
         return false;
      }

      // If plugin is not marked as activated, check on DB as it may have not been loaded yet.
      if ($this->getFromDBbyDir($directory)) {
         return ($this->fields['state'] == self::NOTUPDATED) && $this->isLoadable($directory);
      }

      return false;
   }


   /**
    * Is a plugin loadable ?
    *
    * @param string $directory  Plugin directory
    *
    * @return boolean
    */
   function isLoadable($directory) {
      return !empty($this->getInformationsFromDirectory($directory));
   }


   /**
    * Is a plugin installed ?
    *
    * @param string $directory  Plugin directory
    *
    * @return boolean
    */
   function isInstalled($directory) {

      if ($this->isPluginLoaded($directory)) {
         // If plugin is loaded, it is because it is installed and active. No need to query DB on this case.
         return true;
      }

      // If plugin is not loaded, check on DB as plugins may have not been loaded yet.
      if ($this->getFromDBbyDir($directory)) {
         return in_array($this->fields['state'], [self::ACTIVATED, self::TOBECONFIGURED, self::NOTACTIVATED])
            && $this->isLoadable($directory);
      }

      return false;
   }


   /**
    * Migrate itemtype from integer (0.72) to string (0.80)
    *
    * @param array $types        Array of (num=>name) of type manage by the plugin
    * @param array $glpitables   Array of GLPI table name used by the plugin
    * @param array $plugtables   Array of Plugin table name which have an itemtype
    *
    * @return void
   **/
   static function migrateItemType($types = [], $glpitables = [], $plugtables = []) {
      global $DB;

      $typetoname = [0  => "",// For tickets
                          1  => "Computer",
                          2  => "NetworkEquipment",
                          3  => "Printer",
                          4  => "Monitor",
                          5  => "Peripheral",
                          6  => "Software",
                          7  => "Contact",
                          8  => "Supplier",
                          9  => "Infocom",
                          10 => "Contract",
                          11 => "CartridgeItem",
                          12 => "DocumentType",
                          13 => "Document",
                          14 => "KnowbaseItem",
                          15 => "User",
                          16 => "Ticket",
                          17 => "ConsumableItem",
                          18 => "Consumable",
                          19 => "Cartridge",
                          20 => "SoftwareLicense",
                          21 => "Link",
                          22 => "State",
                          23 => "Phone",
                          24 => "Device",
                          25 => "Reminder",
                          26 => "Stat",
                          27 => "Group",
                          28 => "Entity",
                          29 => "ReservationItem",
                          30 => "AuthMail",
                          31 => "AuthLDAP",
                          32 => "OcsServer",
                          33 => "RegistryKey",
                          34 => "Profile",
                          35 => "MailCollector",
                          36 => "Rule",
                          37 => "Transfer",
                          38 => "SavedSearch",
                          39 => "SoftwareVersion",
                          40 => "Plugin",
                          41 => "Item_Disk",
                          42 => "NetworkPort",
                          43 => "TicketFollowup",
                          44 => "Budget"];

      // Filter tables that does not exists or does not contains an itemtype field.
      // This kind of case exist when current method is called from plugins that based their
      // logic on an old GLPI datamodel that may have changed upon time.
      // see https://github.com/pluginsGLPI/order/issues/111
      $glpitables = array_filter(
         $glpitables,
         function ($table) use ($DB) {
            return $DB->tableExists($table) && $DB->fieldExists($table, 'itemtype');
         }
      );

      //Add plugins types
      $typetoname = self::doHookFunction("migratetypes", $typetoname);

      foreach ($types as $num => $name) {
         $typetoname[$num] = $name;
         foreach ($glpitables as $table) {
            $DB->updateOrDie(
               $table, [
                  'itemtype'  => $name,
               ], [
                  'itemtype'  => $num
               ],
               "update itemtype of table $table for $name"
            );
         }
      }

      if (in_array('glpi_infocoms', $glpitables) && count($types)) {
         $entities    = getAllDataFromTable('glpi_entities');
         $entities[0] = "Root";

         foreach ($types as $num => $name) {
            $itemtable = getTableForItemType($name);
            if (!$DB->tableExists($itemtable)) {
               // Just for security, shouldn't append
               continue;
            }
            $do_recursive = false;
            if ($DB->fieldExists($itemtable, 'is_recursive')) {
               $do_recursive = true;
            }
            foreach ($entities as $entID => $val) {
               if ($do_recursive) {
                  // Non recursive ones
                  $sub_query = new \QuerySubQuery([
                     'SELECT' => 'id',
                     'FROM'   => $itemtable,
                     'WHERE'  => [
                        'entities_id'  => $entID,
                        'is_recursive' => 0
                     ]
                  ]);

                  $DB->updateOrDie(
                     'glpi_infocoms', [
                        'entities_id'  => $entID,
                        'is_recursive' => 0
                     ], [
                        'itemtype'  => $name,
                        'items_id'  => $sub_query
                     ],
                     "update entities_id and is_recursive=0 in glpi_infocoms for $name"
                  );

                  // Recursive ones
                  $sub_query = new \QuerySubQuery([
                     'SELECT' => 'id',
                     'FROM'   => $itemtable,
                     'WHERE'  => [
                        'entities_id'  => $entID,
                        'is_recursive' => 1
                     ]
                  ]);

                  $DB->updateOrDie(
                     'glpi_infocoms', [
                        'entities_id'  => $entID,
                        'is_recursive' => 1
                     ], [
                        'itemtype'  => $name,
                        'items_id'  => $sub_query
                     ],
                     "update entities_id and is_recursive=1 in glpi_infocoms for $name"
                  );
               } else {
                  $sub_query = new \QuerySubQuery([
                     'SELECT' => 'id',
                     'FROM'   => $itemtable,
                     'WHERE'  => [
                        'entities_id'  => $entID,
                     ]
                  ]);

                  $DB->updateOrDie(
                     'glpi_infocoms', [
                        'entities_id'  => $entID
                     ], [
                        'itemtype'  => $name,
                        'items_id'  => $sub_query
                     ],
                     "update entities_id in glpi_infocoms for $name"
                  );
               }
            } // each entity
         } // each plugin type
      }

      foreach ($typetoname as $num => $name) {
         foreach ($plugtables as $table) {
            $DB->updateOrDie(
               $table, [
                  'itemtype' => $name
               ], [
                  'itemtype' => $num
               ],
               "update itemtype of table $table for $name"
            );
         }
      }
   }


   /**
    * @param integer $width
   **/
   function showSystemInformations($width) {

      // No need to translate, this part always display in english (for copy/paste to forum)

      echo "\n<tr class='tab_bg_2'><th>Plugins list</th></tr>";
      echo "<tr class='tab_bg_1'><td><pre>\n&nbsp;\n";

      $plug     = new Plugin();
      $pluglist = $plug->find([], "name, directory");
      foreach ($pluglist as $plugin) {
         $name = Html::clean($plugin['name']);
         $version = Html::clean($plugin['version']);

         $msg  = substr(str_pad($plugin['directory'], 30), 0, 20).
                 " Name: ".Toolbox::substr(str_pad($name, 40), 0, 30).
                 " Version: ".str_pad($version, 10).
                 " State: ";

         $state = $plug->isLoadable($plugin['directory']) ? $plugin['state'] : self::TOBECLEANED;
         $msg .= self::getState($state);

         echo wordwrap("\t".$msg."\n", $width, "\n\t\t");
      }
      echo "\n</pre></td></tr>";
   }


   /**
    * Define a new class managed by a plugin
    *
    * @param string $itemtype Class name
    * @param array  $attrib   Array of attributes, a hashtable with index in
    *                         (classname, typename, reservation_types)
    *
    * @return bool
   **/
   static function registerClass($itemtype, $attrib = []) {
      global $CFG_GLPI;

      $plug = isPluginItemType($itemtype);
      if (!$plug) {
         return false;
      }

      $all_types = preg_grep('/.+_types/', array_keys($CFG_GLPI));
      $all_types[] = 'networkport_instantiations';

      $mapping = [
         'doc_types'       => 'document_types',
         'helpdesk_types'  => 'ticket_types',
         'netport_types'   => 'networkport_types'
      ];

      foreach ($mapping as $orig => $fixed) {
         if (isset($attrib[$orig])) {
            \Toolbox::deprecated(
               sprintf(
                  '%1$s type is deprecated, use %2$s instead.',
                  $orig,
                  $fixed
               )
            );
            $attrib[$fixed] = $attrib[$orig];
            unset($attrib[$orig]);
         }
      }

      $blacklist = ['device_types'];
      foreach ($all_types as $att) {
         if (!in_array($att, $blacklist) && isset($attrib[$att]) && $attrib[$att]) {
            $CFG_GLPI[$att][] = $itemtype;
            unset($attrib[$att]);
         }
      }

      if (isset($attrib['device_types']) && $attrib['device_types']
          && method_exists($itemtype, 'getItem_DeviceType')) {

         if (class_exists($itemtype::getItem_DeviceType())) {
            $CFG_GLPI['device_types'][] = $itemtype;
         }
         unset($attrib[$att]);
      }

      if (isset($attrib['addtabon'])) {
         if (!is_array($attrib['addtabon'])) {
            $attrib['addtabon'] = [$attrib['addtabon']];
         }
         foreach ($attrib['addtabon'] as $form) {
            CommonGLPI::registerStandardTab($form, $itemtype);
         }
      }

      //Manage entity forward from a source itemtype to this itemtype
      if (isset($attrib['forwardentityfrom'])) {
         CommonDBTM::addForwardEntity($attrib['forwardentityfrom'], $itemtype);
      }

      return true;
   }


   /**
    * This function executes a hook.
    *
    * @param string  $name   Name of hook to fire
    * @param mixed   $param  Parameters if needed : if object limit to the itemtype (default NULL)
    *
    * @return mixed $data
   **/
   static function doHook($name, $param = null) {
      global $PLUGIN_HOOKS;

      if ($param == null) {
         $data = func_get_args();
      } else {
         $data = $param;
      }

      // Apply hook only for the item
      if (($param != null) && is_object($param)) {
         $itemtype = get_class($param);
         if (isset($PLUGIN_HOOKS[$name]) && is_array($PLUGIN_HOOKS[$name])) {
            foreach ($PLUGIN_HOOKS[$name] as $plugin_key => $tab) {
               if (!Plugin::isPluginActive($plugin_key)) {
                  continue;
               }

               if (isset($tab[$itemtype])) {
                  self::includeHook($plugin_key);
                  if (is_callable($tab[$itemtype])) {
                     call_user_func($tab[$itemtype], $data);
                  }
               }
            }
         }

      } else { // Standard hook call
         if (isset($PLUGIN_HOOKS[$name]) && is_array($PLUGIN_HOOKS[$name])) {
            foreach ($PLUGIN_HOOKS[$name] as $plugin_key => $function) {
               if (!Plugin::isPluginActive($plugin_key)) {
                  continue;
               }

               self::includeHook($plugin_key);
               if (is_callable($function)) {
                  call_user_func($function, $data);
               }
            }
         }
      }
      /* Variable-length argument lists have a slight problem when */
      /* passing values by reference. Pity. This is a workaround.  */
      return $data;
   }


   /**
    * This function executes a hook.
    *
    * @param string $name   Name of hook to fire
    * @param mixed  $parm   Parameters (default NULL)
    *
    * @return mixed $data
   **/
   static function doHookFunction($name, $parm = null) {
      global $PLUGIN_HOOKS;

      $ret = $parm;
      if (isset($PLUGIN_HOOKS[$name]) && is_array($PLUGIN_HOOKS[$name])) {
         foreach ($PLUGIN_HOOKS[$name] as $plugin_key => $function) {
            if (!Plugin::isPluginActive($plugin_key)) {
               continue;
            }

            self::includeHook($plugin_key);
            if (is_callable($function)) {
               $ret = call_user_func($function, $ret);
            }
         }
      }
      /* Variable-length argument lists have a slight problem when */
      /* passing values by reference. Pity. This is a workaround.  */
      return $ret;
   }


   /**
    * This function executes a hook for 1 plugin.
    *
    * @param string          $plugin_key System name of the plugin
    * @param string|callable $hook     suffix used to build function to be called ("plugin_myplugin_{$hook}")
    *                                  or callable function
    * @param mixed           ...$args  [optional] One or more arguments passed to hook function
    *
    * @return mixed $data
   **/
   static function doOneHook($plugin_key, $hook, ...$args) {

      $plugin_key = strtolower($plugin_key);

      if (!Plugin::isPluginActive($plugin_key)) {
         return;
      }

      self::includeHook($plugin_key);

      if (is_string($hook) && !is_callable($hook)) {
         $hook = "plugin_" . $plugin_key . "_" . $hook;
      }

      if (is_callable($hook)) {
         return call_user_func_array($hook, $args);
      }
   }


   /**
    * Get dropdowns for plugins
    *
    * @return array Array containing plugin dropdowns
   **/
   static function getDropdowns() {

      $dps = [];
      foreach (self::getPlugins() as  $plug) {
         $tab = self::doOneHook($plug, 'getDropdown');
         if (is_array($tab)) {
            $dps = array_merge($dps, [self::getInfo($plug, 'name') => $tab]);
         }
      }
      return $dps;
   }


   /**
    * Get information from a plugin
    *
    * @param string $plugin System name (Plugin directory)
    * @param string $info   Wanted info (name, version, ...), NULL for all
    *
    * @since 0.84
    *
    * @return string|array The specific information value requested or an array of all information if $info is null.
   **/
   static function getInfo($plugin, $info = null) {

      $fct = 'plugin_version_'.strtolower($plugin);
      if (function_exists($fct)) {
         $res = $fct();
         if (!isset($res['requirements']) && isset($res['minGlpiVersion'])) {
            $res['requirements'] = ['glpi' => ['min' => $res['minGlpiVersion']]];
         }
      } else {
         Toolbox::logError("$fct method must be defined!");
         $res = [];
      }
      if (isset($info)) {
         return (isset($res[$info]) ? $res[$info] : '');
      }
      return $res;
   }

   /**
    * Returns plugin information from directory.
    *
    * @param string $directory
    *
    * @return array
    */
   public function getInformationsFromDirectory($directory) {

      $informations = [];
      foreach (PLUGINS_DIRECTORIES as $base_dir) {
         if (!is_dir($base_dir)) {
            continue;
         }
         $setup_file  = "$base_dir/$directory/setup.php";

         if (file_exists($setup_file)) {
            // Includes are made inside a function to prevent included files to override
            // variables used in this function.
            // For example, if the included files contains a $plugin variable, it will
            // replace the $plugin variable used here.
            $include_fct = function () use ($directory, $setup_file) {
               self::loadLang($directory);
               include_once($setup_file);
            };
            $include_fct();
            $informations = Toolbox::addslashes_deep(self::getInfo($directory));

            // plugin found, don't parse others directories
            break;
         }
      }

      return $informations;
   }


   /**
    * Get database relations for plugins
    *
    * @return array Array containing plugin database relations
   **/
   static function getDatabaseRelations() {

      $dps = [];
      foreach (self::getPlugins() as $plugin_key) {
         self::includeHook($plugin_key);
         $function2 = "plugin_".$plugin_key."_getDatabaseRelations";
         if (function_exists($function2)) {
            $dps = array_merge_recursive($dps, $function2());
         }
      }
      return $dps;
   }


   /**
    * Get additional search options managed by plugins
    *
    * @param $itemtype
    *
    * @return array Array containing plugin search options for given type
   **/
   static function getAddSearchOptions($itemtype) {

      $sopt = [];
      foreach (self::getPlugins() as $plugin_key) {
         self::includeHook($plugin_key);
         $function = "plugin_".$plugin_key."_getAddSearchOptions";
         if (function_exists($function)) {
            $tmp = $function($itemtype);
            if (is_array($tmp) && count($tmp)) {
               $sopt += $tmp;
            }
         }
      }
      return $sopt;
   }


   /**
    * Include the hook file for a plugin
    *
    * @param string $plugin_key
    */
   static function includeHook(string $plugin_key = "") {
      foreach (PLUGINS_DIRECTORIES as $base_dir) {
         if (file_exists("$base_dir/$plugin_key/hook.php")) {
            include_once("$base_dir/$plugin_key/hook.php");
            break;
         }
      }
   }


   /**
    * Get additional search options managed by plugins
    *
    * @since 9.2
    *
    * @param string $itemtype Item type
    *
    * @return array An *indexed* array of search options
    *
    * @see https://glpi-developer-documentation.rtfd.io/en/master/devapi/search.html
   **/
   static function getAddSearchOptionsNew($itemtype) {
      $options = [];

      foreach (self::getPlugins() as $plugin_key) {
         self::includeHook($plugin_key);
         $function = "plugin_".$plugin_key."_getAddSearchOptionsNew";
         if (function_exists($function)) {
            $tmp = $function($itemtype);
            foreach ($tmp as $opt) {
               if (!isset($opt['id'])) {
                  throw new \Exception($itemtype . ': invalid search option! ' . print_r($opt, true));
               }
               $optid = $opt['id'];
               unset($opt['id']);

               if (isset($options[$optid])) {
                  $message = "Duplicate key $optid ({$options[$optid]['name']}/{$opt['name']}) in ".
                     $itemtype . " searchOptions!";
                  Toolbox::logError($message);
               }

               foreach ($opt as $k => $v) {
                  $options[$optid][$k] = $v;
               }
            }
         }
      }

      return $options;
   }

   /**
    * Check if there is a plugin enabled that supports importing items
    *
    * @return boolean
    *
    * @since 0.84
   **/
   static function haveImport() {
      global $PLUGIN_HOOKS;

      return (isset($PLUGIN_HOOKS['import_item']) && count($PLUGIN_HOOKS['import_item']));
   }

   /**
    * Get an internationalized message for incompatible plugins (either core or php version)
    *
    * @param string $type Either 'php' or 'core', defaults to 'core'
    * @param string $min  Minimal required version
    * @param string $max  Maximal required version
    *
    * @since 9.2
    *
    * @return string
    */
   static public function messageIncompatible($type = 'core', $min = null, $max = null) {
      $type = ($type === 'core' ? __('GLPI') : __('PHP'));
      if ($min === null && $max !== null) {
         return sprintf(
            __('This plugin requires %1$s < %2$s.'),
            $type,
            $max
         );
      } else if ($min !== null && $max === null) {
         return sprintf(
            __('This plugin requires %1$s >= %2$s.'),
            $type,
            $min
         );

      } else {
         return sprintf(
            __('This plugin requires %1$s >= %2$s and < %3$s.'),
            $type,
            $min,
            $max
         );
      }
   }

   /**
    * Get an internationalized message for missing requirement (extension, other plugin, ...)
    *
    * @param string $type Type of what is missing, one of:
    *                     - ext (PHP module)
    *                     - plugin (other plugin)
    *                     - compil (compilation option)
    *                     - param (GLPI configuration parameter)
    * @param string $name Missing name
    *
    * @since 9.2
    *
    * @return string
    */
   static public function messageMissingRequirement($type, $name) {
      switch ($type) {
         case 'ext':
            return sprintf(
               __('This plugin requires PHP extension %1$s'),
               $name
            );
            break;
         case 'plugin':
            return sprintf(
               __('This plugin requires %1$s plugin'),
               $name
            );
            break;
         case 'compil':
            return sprintf(
               __('This plugin requires PHP compiled along with "%1$s"'),
               $name
            );
            break;
         case 'param':
            return sprintf(
               __('This plugin requires PHP parameter %1$s'),
               $name
            );
            break;
         case 'glpiparam':
            return sprintf(
               __('This plugin requires GLPI parameter %1$s'),
               $name
            );
            break;
         default:
            throw new \RuntimeException("messageMissing type $type is unknown!");
      }
   }

   /**
    * Check declared versions (GLPI, PHP, ...)
    *
    * @since 9.2
    *
    * @param string $name System name (Plugin directory)
    *
    * @return boolean
    */
   public function checkVersions($name) {
      $infos = self::getInfo($name);
      $ret = true;
      if (isset($infos['requirements'])) {
         if (isset($infos['requirements']['glpi'])) {
            $glpi = $infos['requirements']['glpi'];
            if (isset($glpi['min']) || isset($glpi['max'])) {
               $ret = $ret && $this->checkGlpiVersion($infos['requirements']['glpi']);
            }
            if (isset($glpi['params'])) {
               $ret = $ret && $this->checkGlpiParameters($glpi['params']);
            }
            if (isset($glpi['plugins'])) {
               $ret = $ret && $this->checkGlpiPlugins($glpi['plugins']);
            }
         }
         if (isset($infos['requirements']['php'])) {
            $php = $infos['requirements']['php'];
            if (isset($php['min']) || isset($php['max'])) {
               $ret = $ret && $this->checkPhpVersion($php);
            }
            if (isset($php['exts'])) {
               $ret = $ret && $this->checkPhpExtensions($php['exts']);
            }
            if (isset($php['params'])) {
               $ret = $ret && $this->checkPhpParameters($php['params']);
            }
         }
      }
      return $ret;
   }

   /**
    * Check for GLPI version
    *
    * @since 9.2
    * @since 9.3 Removed the 'dev' key of $info parameter.
    *
    * @param array $infos Requirements infos:
    *                     - min: minimal supported version,
    *                     - max: maximal supported version
    *                     One of min or max is required.
    *
    * @return boolean
    */
   public function checkGlpiVersion($infos) {
      if (!isset($infos['min']) && !isset($infos['max'])) {
         throw new LogicException('Either "min" or "max" is required for GLPI requirements!');
      }

      $glpiVersion = $this->isGlpiPrever() ? $this->getGlpiPrever() : $this->getGlpiVersion();

      $compat = true;
      if (isset($infos['min']) && !version_compare($glpiVersion, $infos['min'], '>=')) {
         $compat = false;
      }
      if (isset($infos['max']) && !version_compare($glpiVersion, $infos['max'], '<')) {
         $compat = false;
      }

      if (!$compat) {
         echo Plugin::messageIncompatible(
            'core',
            (isset($infos['min']) ? $infos['min'] : null),
            (isset($infos['max']) ? $infos['max'] : null)
         );
      }

      return $compat;
   }

   /**
    * Check for PHP version
    *
    * @since 9.2
    *
    * @param array $infos Requirements infos:
    *                     - min: minimal supported version,
    *                     - max: maximal supported version.
    *                     One of min or max is required.
    *
    * @return boolean
    */
   public function checkPhpVersion($infos) {
      $compat = true;

      if (isset($infos['min']) && isset($infos['max'])) {
         $compat = !(version_compare($this->getPhpVersion(), $infos['min'], 'lt') || version_compare($this->getPhpVersion(), $infos['max'], 'ge'));
      } else if (isset($infos['min'])) {
         $compat = !(version_compare($this->getPhpVersion(), $infos['min'], 'lt'));
      } else if (isset($infos['max'])) {
         $compat = !(version_compare($this->getPhpVersion(), $infos['max'], 'ge'));
      } else {
         throw new LogicException('Either "min" or "max" is required for PHP requirements!');
      }

      if (!$compat) {
         echo Plugin::messageIncompatible(
            'php',
            (isset($infos['min']) ? $infos['min'] : null),
            (isset($infos['max']) ? $infos['max'] : null)
         );
      }

      return $compat;
   }


   /**
    * Check fo required PHP extensions
    *
    * @since 9.2
    *
    * @param array $exts Extensions lists/config @see Config::checkExtensions()
    *
    * @return boolean
    */
   public function checkPhpExtensions($exts) {
      $report = Config::checkExtensions($exts);
      if (count($report['missing'])) {
         foreach (array_keys($report['missing']) as $ext) {
            echo self::messageMissingRequirement('ext', $ext) . '<br/>';
         }
         return false;
      }
      return true;
   }


   /**
    * Check expected GLPI parameters
    *
    * @since 9.2
    *
    * @param array $params Expected parameters to be setup
    *
    * @return boolean
    */
   public function checkGlpiParameters($params) {
      global $CFG_GLPI;

      $compat = true;
      foreach ($params as $param) {
         if (!isset($CFG_GLPI[$param]) || trim($CFG_GLPI[$param]) == '' || !$CFG_GLPI[$param]) {
            echo self::messageMissingRequirement('glpiparam', $param) . '<br/>';
            $compat = false;
         }
      }

      return $compat;
   }


   /**
    * Check expected PHP parameters
    *
    * @since 9.2
    *
    * @param array $params Expected parameters to be setup
    *
    * @return boolean
    */
   public function checkPhpParameters($params) {
      $compat = true;
      foreach ($params as $param) {
         if (!ini_get($param) || trim(ini_get($param)) == '') {
            echo self::messageMissingRequirement('param', $param) . '<br/>';
            $compat = false;
         }
      }

      return $compat;
   }


   /**
    * Check expected GLPI plugins
    *
    * @since 9.2
    *
    * @param array $plugins Expected plugins
    *
    * @return boolean
    */
   public function checkGlpiPlugins($plugins) {
      $compat = true;
      foreach ($plugins as $plugin) {
         if (!$this->isInstalled($plugin) || !$this->isActivated($plugin)) {
            echo self::messageMissingRequirement('plugin', $plugin) . '<br/>';
            $compat = false;
         }
      }

      return $compat;
   }


   /**
    * Get GLPI version
    * Used from unit tests to mock.
    *
    * @since 9.2
    *
    * @return string
    */
   public function getGlpiVersion() {
      return GLPI_VERSION;
   }

   /**
    * Get GLPI pre version
    * Used from unit tests to mock.
    *
    * @since 9.2
    *
    * @return string
    */
   public function getGlpiPrever() {
      return GLPI_PREVER;
   }

   /**
    * Check if GLPI version is a pre version
    *
    * @since 9.3
    *
    * @return string
    */
   public function isGlpiPrever() {
      return defined('GLPI_PREVER');
   }

   /**
    * Get PHP version
    * Used from unit tests to mock.
    *
    * @since 9.2
    *
    * @return string
    */
   public function getPhpVersion() {
      return PHP_VERSION;
   }

   /**
    * Return label for an integer plugin state
    *
    * @since 9.3
    *
    * @param  integer $state see this class constants (ex self::ANEW, self::ACTIVATED)
    * @return string  the label
    */
   static function getState($state = 0) {
      switch ($state) {
         case self::ANEW :
            return _x('status', 'New');

         case self::ACTIVATED :
            return _x('plugin', 'Enabled');

         case self::NOTINSTALLED :
            return _x('plugin', 'Not installed');

         case self::NOTUPDATED :
            return __('To update');

         case self::TOBECONFIGURED :
            return _x('plugin', 'Installed / not configured');

         case self::NOTACTIVATED :
            return _x('plugin', 'Installed / not activated');
      }

      return __('Error / to clean');
   }


   /**
    * Return key for an integer plugin state
    * purpose is to have a corresponding css class name
    *
    * @since 9.5
    *
    * @param  integer $state see this class constants (ex self::ANEW, self::ACTIVATED)
    * @return string  the key
    */
   static function getStateKey(int $state = 0): string {
      switch ($state) {
         case self::ANEW :
            return "new";

         case self::ACTIVATED :
            return "activated";

         case self::NOTINSTALLED :
            return "notinstalled";

         case self::NOTUPDATED :
            return "notupdated";

         case self::TOBECONFIGURED :
            return "tobeconfigured";

         case self::NOTACTIVATED :
            return "notactived";
      }

      return "";
   }

   /**
    * Get plugins list
    *
    * @since 9.3.2
    *
    * @return array
    */
   public static function getPlugins() {
      return self::$activated_plugins;
   }

   /**
    * Check if a plugin is loaded
    *
    * @since 9.3.2
    *
    * @param string $plugin_key  System name (Plugin directory)
    *
    * @return boolean
    */
   public static function isPluginLoaded($plugin_key) {
      // Make a lowercase comparison, as sometime this function is called based on
      // extraction of plugin name from a classname, which does not use same naming rules than directories.
      $loadedPlugins = array_map('strtolower', self::$loaded_plugins);
      return in_array(strtolower($plugin_key), $loadedPlugins);
   }

   /**
    * Check if a plugin is active
    *
    * @since 9.5.0
    *
    * @param string $plugin_key  System name (Plugin directory)
    *
    * @return boolean
    */
   public static function isPluginActive($plugin_key) {
      $plugin = new self();
      return $plugin->isActivated($plugin_key);
   }

   /**
    * Set plugin loaded
    *
    * @since 9.3.2
    * @deprecated 9.5.0
    *
    * @param integer $id   Plugin id
    * @param string  $name Plugin name
    *
    * @return void
    */
   public static function setLoaded($id, $name) {
      Toolbox::deprecated();
      if (!in_array($name, self::$loaded_plugins)) {
         self::$loaded_plugins[] = $name;
      }
   }

   /**
    * Set plugin unloaded
    *
    * @since 9.3.2
    * @deprecated 9.5.0
    *
    * @param integer $id Plugin id
    *
    * @return void
    */
   public static function setUnloaded($id) {
      Toolbox::deprecated();

      $plugin = new self();
      $plugin->getFromDB($id);
      $plugin->unload($plugin->fields['directory']);
   }

   /**
    * Set plugin unloaded from its name
    *
    * @since 9.3.2
    * @deprecated 9.5.0
    *
    * @param integer $name System name (Plugin directory)
    *
    * @return void
    */
   public static function setUnloadedByName($name) {
      Toolbox::deprecated();

      $plugin = new self();
      $plugin->unload($name);
   }

   function rawSearchOptions() {
      $tab = [];

      $tab[] = [
         'id'                 => 'common',
         'name'               => __('Characteristics')
      ];

      $tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'name',
         'name'               => __('Name'),
         'datatype'           => 'specific',
         'massiveaction'      => false, // implicit key==1
         'additionalfields'   => ['state', 'directory'],
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'directory',
         'name'               => __('Directory'),
         'massiveaction'      => false,
         'nosearch'           => true,
         'noremove'           => true
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => $this->getTable(),
         'field'              => 'version',
         'name'               => _n('Version', 'Versions', 1),
         'datatype'           => 'specific',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => $this->getTable(),
         'field'              => 'license',
         'name'               => SoftwareLicense::getTypeName(1),
         'datatype'           => 'specific',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => $this->getTable(),
         'field'              => 'state',
         'name'               => __('Status'),
         'searchtype'         => 'equals',
         'noremove'           => true,
         'additionalfields'   => ['directory'],
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => $this->getTable(),
         'field'              => 'author',
         'name'               => __('Authors'),
         'datatype'           => 'specific',
      ];

      $tab[] = [
         'id'                 => '7',
         'table'              => $this->getTable(),
         'field'              => 'homepage',
         'name'               => __('Website'),
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '8',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('Actions'),
         'massiveaction'      => false,
         'nosearch'           => true,
         'datatype'           => 'specific',
         'noremove'           => true,
         'additionalfields'   => ['directory']
      ];

      return $tab;
   }


   static function getSpecificValueToDisplay($field, $values, array $options = []) {
      global $PLUGIN_HOOKS, $CFG_GLPI;

      if (!is_array($values)) {
         $values = [$field => $values];
      }

      switch ($field) {
         case 'id':
            //action...
            $ID = $values[$field];

            $plugin = new self;
            $plugin->getFromDB($ID);

            $directory = $plugin->fields['directory'];
            $state = (int)$plugin->fields['state'];

            if ($plugin->isLoadable($directory)) {
               self::load($directory, true);
            } else {
               $state = self::TOBECLEANED;
            }

            $output = '';

            if (in_array($state, [self::ACTIVATED, self::TOBECONFIGURED], true)
                && isset($PLUGIN_HOOKS['config_page'][$directory])) {
               // Configuration button for activated or configurable plugins
               $plugin_dir = self::getWebDir($directory, true);
               $config_url = "$plugin_dir/".$PLUGIN_HOOKS['config_page'][$directory];
               $output .= '<a href="' . $config_url . '" title="' . __s('Configure') . '">'
                  . '<i class="fas fa-wrench fa-2x"></i>'
                  . '<span class="sr-only">' . __s('Configure') . '</span>'
                  . '</a>'
                  . '&nbsp;';
            }

            if ($state === self::ACTIVATED) {
               // Deactivate button for active plugins
               $output .= Html::getSimpleForm(
                  static::getFormURL(),
                  ['action' => 'unactivate'],
                  _x('button', 'Disable'),
                  ['id' => $ID],
                  'fa-fw fa-toggle-on fa-2x enabled'
               ) . '&nbsp;';
            } else if ($state === self::NOTACTIVATED) {
               // Activate button for configured and up to date plugins
               ob_start();
               $do_activate = $plugin->checkVersions($directory);
               if (!$do_activate) {
                  $output .= "<span class='error'>" . ob_get_contents() . "</span>";
               }
               ob_end_clean();
               $function = 'plugin_' . $directory . '_check_prerequisites';
               if (!isset($PLUGIN_HOOKS['csrf_compliant'][$directory])
                   || !$PLUGIN_HOOKS['csrf_compliant'][$directory]) {
                  $output .= "<span class='error'>" . __('Not CSRF compliant') . "</span>";
                  $do_activate = false;
               } else if (function_exists($function) && $do_activate) {
                  ob_start();
                  $do_activate = $function();
                  if (!$do_activate) {
                     $output .= '<span class="error">' . ob_get_contents() . '</span>';
                  }
                  ob_end_clean();
               }
               if ($do_activate) {
                  $output .= Html::getSimpleForm(
                     static::getFormURL(),
                     ['action' => 'activate'],
                     _x('button', 'Enable'),
                     ['id' => $ID],
                     'fa-fw fa-toggle-off fa-2x disabled'
                  ) . '&nbsp;';
               }
            }

            if (in_array($state, [self::ANEW, self::NOTINSTALLED, self::NOTUPDATED], true)) {
               // Install button for new, not installed or not up to date plugins
               if (function_exists("plugin_".$directory."_install")) {

                  $function   = 'plugin_' . $directory . '_check_prerequisites';

                  ob_start();
                  $do_install = $plugin->checkVersions($directory);
                  if (!$do_install) {
                     $output .= "<span class='error'>" . ob_get_contents() . "</span>";
                  }
                  ob_end_clean();

                  if ($do_install && function_exists($function)) {
                     ob_start();
                     $do_install = $function();
                     $msg = '';
                     if (!$do_install) {
                        $msg = '<span class="error">' . ob_get_contents() . '</span>';
                     }
                     ob_end_clean();
                     $output .= $msg;
                  }
                  if ($state == self::NOTUPDATED) {
                     $msg = _x('button', 'Upgrade');
                  } else {
                     $msg = _x('button', 'Install');
                  }
                  if ($do_install) {
                     $output .= Html::getSimpleForm(
                        static::getFormURL(),
                        ['action' => 'install'],
                        $msg,
                        ['id' => $ID],
                        'fa-fw fa-folder-plus fa-2x'
                     ) . '&nbsp;';
                  }
               } else {
                  $missing = '';
                  if (!function_exists("plugin_".$directory."_install")) {
                     $missing .= "plugin_".$directory."_install";
                  }
                  //TRANS: %s is the list of missing functions
                  $output .= sprintf(__('%1$s: %2$s'), __('Non-existent function'),
                         $missing);
               }
            }
            if (in_array($state, [self::ACTIVATED, self::NOTUPDATED, self::TOBECONFIGURED, self::NOTACTIVATED], true)) {
               // Uninstall button for installed plugins
               if (function_exists("plugin_".$directory."_uninstall")) {
                  $output .= Html::getSimpleForm(
                     static::getFormURL(),
                     ['action' => 'uninstall'],
                     _x('button', 'Uninstall'),
                     ['id' => $ID],
                     'fa-fw fa-folder-minus fa-2x'
                  ) . '&nbsp;';
               } else {
                  //TRANS: %s is the list of missing functions
                  $output .= sprintf(__('%1$s: %2$s'), __('Non-existent function'),
                               "plugin_".$directory."_uninstall");
               }
            } else if ($state === self::TOBECLEANED) {
               $output .= Html::getSimpleForm(
                  static::getFormURL(),
                  ['action' => 'clean'],
                  _x('button', 'Clean'),
                  ['id' => $ID],
                  'fa-fw fas fa-broom fa-2x'
               );
            }

            return "<div style='text-align:right'>$output</div>";
            break;
         case 'state':
            $plugin = new self();
            $state = $plugin->isLoadable($values['directory']) ? $values[$field] : self::TOBECLEANED;
            return self::getState($state);
            break;
         case 'homepage':
            $value = Html::entities_deep(Toolbox::formatOutputWebLink($values[$field]));
            if (!empty($value)) {
               return "<a href=\"".$value."\" target='_blank'>
                     <i class='fas fa-external-link-alt fa-2x'></i><span class='sr-only'>$value</span>
                  </a>";
            }
            return "&nbsp;";
            break;
         case 'name':
            $value = Html::clean($values[$field]);
            $state = $values['state'];
            $directory = $values['directory'];
            self::load($directory); // Load plugin to give it ability to define its config_page hook
            if (in_array($state, [self::ACTIVATED, self::TOBECONFIGURED])
               && isset($PLUGIN_HOOKS['config_page'][$directory])
            ) {
               $plugin_dir = self::getWebDir($directory, true);
               $config_url = "$plugin_dir/".$PLUGIN_HOOKS['config_page'][$directory];
               return "<a href='$config_url'><span class='b'>$value</span></a>";
            } else {
               return $value;
            }
            break;
         case 'author':
            return $value = Html::clean($values[$field], false);
            break;
         case 'license':
         case 'version':
            return $value = Html::clean($values[$field]);
            break;
      }

      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {
      if (!is_array($values)) {
         $values = [$field => $values];
      }
      $options['display'] = false;

      switch ($field) {
         case 'state':
            $tab = [
               self::ANEW           => _x('status', 'New'),
               self::ACTIVATED      => _x('plugin', 'Enabled'),
               self::NOTINSTALLED   => _x('plugin', 'Not installed'),
               self::NOTUPDATED     => __('To update'),
               self::TOBECONFIGURED => _x('plugin', 'Installed / not configured'),
               self::NOTACTIVATED   => _x('plugin', 'Installed / not activated'),
               self::TOBECLEANED    => __('Error / to clean')
            ];
            $options['value'] = $values[$field];
            return Dropdown::showFromArray($name, $tab, $options);
            break;
      }
   }

   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      $forbidden[] = 'clone';
      $forbidden[] = 'purge';
      return $forbidden;
   }


   /**
    * Return the system path for a given plugin key
    *
    * @since 9.5
    *
    * @param string $plugin_key plugin system key
    * @param bool $full true for absolute path
    *
    * @return false|string the path
    */
   static function getPhpDir(string $plugin_key = "", $full = true) {
      $directory = false;
      foreach (PLUGINS_DIRECTORIES as $plugins_directory) {
         if (is_dir("$plugins_directory/$plugin_key")) {
            $directory = "$plugins_directory/$plugin_key";
            break;
         }
      }

      if (!$full) {
         $directory = str_replace(GLPI_ROOT, "", $directory);
      }

      return str_replace('\\', '/', $directory);
   }


   /**
    * Return the web path for a given plugin key
    *
    * @since 9.5
    *
    * @param string $plugin_key plugin system key
    * @param bool $full if true, append root_doc from config
    * @param bool $use_url_base if true, url_base instead root_doc
    *
    * @return false|string the web path
    */
   static function getWebDir(string $plugin_key = "", $full = true, $use_url_base = false) {
      global $CFG_GLPI;

      $directory = self::getPhpDir($plugin_key, false);
      $directory = ltrim($directory, '/\\');

      if ($full) {
         $root = $use_url_base ? $CFG_GLPI['url_base'] : $CFG_GLPI["root_doc"];
         $directory = "$root/$directory";
      }

      return str_replace('\\', '/', $directory);
   }


   static function getIcon() {
      return "fas fa-puzzle-piece";
   }
}
