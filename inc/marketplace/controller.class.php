<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
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

namespace Glpi\Marketplace;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}


use Glpi\Marketplace\Api\Plugins as PluginsApi;
use \wapmorgan\UnifiedArchive\UnifiedArchive;
use \wapmorgan\UnifiedArchive\Exceptions\ArchiveExtractionException;
use \Plugin;
use \Toolbox;
use \Session;
use \GLPINetwork;
use \CommonGLPI;
use \Config;
use \NotificationEvent;
use \CronTask;

class Controller extends CommonGLPI {
   protected $plugin_key = "";

   static $rightname = 'config';
   static $api       = null;

   const MP_REPLACE_ASK   = 1;
   const MP_REPLACE_YES   = 2;
   const MP_REPLACE_NEVER = 3;

   function __construct(string $plugin_key = "") {
      $this->plugin_key = $plugin_key;
   }


   static function getTypeName($nb = 0) {
      return __('Marketplace');
   }

   /**
    * singleton return the current api instance
    *
    * @return PluginsApi
    */
   static function getAPI(): PluginsApi {
      return self::$api ?? (self::$api = new PluginsApi());
   }


   /**
    * Download and uncompress plugin archive
    *
    * @return int plugin status, @see properties of \Plugin class
    */
   function downloadPlugin():int {
      if (!self::hasWriteAccess()) {
         return Plugin::UNKNOWN;
      }

      $api      = self::getAPI();
      $plugin   = $api->getPlugin($this->plugin_key, true);

      $url      = $plugin['installation_url'] ?? "";
      $filename = basename(parse_url($url, PHP_URL_PATH));
      $dest     = GLPI_TMP_DIR . '/' . $filename;

      if (!$api->downloadArchive($url, $dest, $this->plugin_key)) {
         Session::addMessageAfterRedirect(
            __('Unable to download plugin archive.'),
            false,
            ERROR
         );
         return Plugin::UNKNOWN;
      }

      // extract the archive
      if (!UnifiedArchive::canOpenArchive($dest)) {
         $type = UnifiedArchive::detectArchiveType($dest);
         Session::addMessageAfterRedirect(
            sprintf(__('Plugin archive format is not supported by your system : %s.'), $type),
            false,
            ERROR
         );
         return Plugin::UNKNOWN;
      }
      $archive = UnifiedArchive::open($dest);
      $error = $archive === null;
      if (!$error) {
         // clean dir in case of update
         Toolbox::deleteDir(GLPI_MARKETPLACE_DIR."/{$this->plugin_key}");

         try {
            // copy files
            $archive->extractFiles(GLPI_MARKETPLACE_DIR) !== false;
         } catch (ArchiveExtractionException $e) {
            $error = true;
         }
      }

      if ($error) {
         Session::addMessageAfterRedirect(
            __('Unable to extract plugin archive.'),
            false,
            ERROR
         );
         return Plugin::UNKNOWN;
      }

      $plugin_inst = new Plugin();
      $plugin_inst->checkPluginState($this->plugin_key);
      $plugin_inst->getFromDBbyDir($this->plugin_key);

      // inform api plugin has been downloaded
      $api->incrementPluginDownload($this->plugin_key);

      return $plugin_inst->fields['state'] ?? Plugin::UNKNOWN;
   }


   /**
    * Get plugin archive from its download URL and serve it to the browser.
    *
    * @return void
    */
   function proxifyPluginArchive(): void {
      // close session to prevent blocking other requests
      session_write_close();

      $api    = self::getAPI();
      $plugin = $api->getPlugin($this->plugin_key, true);

      if (!array_key_exists('installation_url', $plugin) || empty($plugin['installation_url'])) {
         return;
      }

      $url      = $plugin['installation_url'];
      $filename = basename(parse_url($url, PHP_URL_PATH));
      $dest     = GLPI_TMP_DIR . '/' . mt_rand() . '.' . $filename;

      if (!$api->downloadArchive($url, $dest, $this->plugin_key, false)) {
         http_response_code(500);
         echo(__('Unable to download plugin archive.'));
         return;
      }

      Toolbox::sendFile($dest, $filename);
   }

   /**
    * Check if plugin can be overwritten.
    *
    * @return bool
    */
   public function canBeOverwritten(): bool {
      foreach (PLUGINS_DIRECTORIES as $base_dir) {
         $is_in_marketplace_dir = realpath($base_dir) !== false
            && realpath($base_dir) === realpath(GLPI_MARKETPLACE_DIR);

         $plugin_dir = $base_dir . '/' . $this->plugin_key;
         $found_in_dir = file_exists($plugin_dir . '/setup.php');

         if ($found_in_dir && !$is_in_marketplace_dir) {
            // Plugin will not be loaded from marketplace directory as
            // it has been found in an higher priority directory.
            // So it cannot be overrided.
            return false;
         } else if ($found_in_dir && $is_in_marketplace_dir) {
            return is_writable($plugin_dir);
         }

         if ($is_in_marketplace_dir) {
            // Current directory is GLPI_MARKETPLACE_DIR, meaning that following
            // checked directories will have a lower priority than GLPI_MARKETPLACE_DIR
            // in autoload process.
            // No need to check them.
            break;
         }
      }

      return self::hasWriteAccess();
   }


   /**
    * Check if a given plugin has on update online
    *
    * @param Plugin $plugin_inst
    *
    * @return string|false new version number
    */
   function checkUpdate(Plugin $plugin_inst = null) {
      $api          = self::getAPI();
      $api_plugin   = $api->getPlugin($this->plugin_key);
      $local_plugin = $plugin_inst->fields;

      $api_version   = $api_plugin['version'] ?? "";
      $local_version = $local_plugin['version'] ?? "";

      if (strlen($api_version) && $api_version !== $local_version) {
         return $api_version;
      }

      return false;
   }


   /**
    * Check for plugins updates
    * Parse all installed plugin and check against API if a news version is available
    *
    * @return array of [plugin_key => new_version_num]
    */
   static function getAllUpdates() {
      $plugin_inst = new Plugin;
      $plugin_inst->init(true);
      $installed   = $plugin_inst->getList();

      $updates = [];

      foreach ($installed as $plugin) {
         $plugin_key = $plugin['directory'];
         $plugin_inst->getFromDBbyDir($plugin_key);

         $mk_controller = new self($plugin_key);
         if (false !== ($api_version = $mk_controller->checkUpdate($plugin_inst))) {
            $updates[$plugin_key] = $api_version;
         }
      }

      return $updates;
   }


   static function cronInfo($name) {
      return ['description' => __('Check all plugin updates')];
   }


   /**
    * Crontask : Check for plugins updates
    *
    * @param CronTask|null $task to log, if NULL display (default NULL)
    *
    * @return integer 0 : nothing to do 1 : done with success
    */
   static function cronCheckAllUpdates(CronTask $task = null):int {
      global $CFG_GLPI;

      $cron_status = 0;

      if (!GLPINetwork::isRegistered()) {
         return $cron_status;
      }

      $updates = self::getAllUpdates();
      if (count($updates)) {
         $cron_status = 1;
         $task->addVolume(count($updates));
         foreach ($updates as $plugin_key => $version) {
            $task->log(sprintf(__("New version for plugin %s: %s"), $plugin_key, $version));
         }

         if (!$CFG_GLPI["use_notifications"]) {
            return $cron_status;
         }

         NotificationEvent::raiseEvent('checkpluginsupdate', new self(), [
            'plugins' => $updates
         ]);
      }

      return $cron_status;
   }


   /**
    * Do the current plugin requires some Glpi Network offers
    *
    * @return array [offer ref => offer title]
    */
   function getRequiredOffers(): array {
      $api        = self::getAPI();
      $api_plugin = $api->getPlugin($this->plugin_key);
      $offers     = array_column(GLPINetwork::getOffers(), 'title', 'offer_reference');

      $trans_offers = array_intersect_key($offers, array_flip($api_plugin['required_offers'] ?? []));

      return $trans_offers;
   }


   /**
    * Check a plugin can be download
    *
    * @return bool
    */
   function canBeDownloaded() {
      $api        = self::getAPI();
      $api_plugin = $api->getPlugin($this->plugin_key);

      return strlen($api_plugin['installation_url'] ?? "") > 0;
   }

   /**
    * Check if plugin is eligible inside an higher offer.
    *
    * @return bool
    */
   public function requiresHigherOffer(): bool {
      $api_plugin = self::getAPI()->getPlugin($this->plugin_key);

      if (!isset($api_plugin['required_offers'])) {
         return false;
      }

      $registration_informations = GLPINetwork::getRegistrationInformations();
      if ($registration_informations['subscription'] !== null
          && $registration_informations['subscription']['is_running']) {
         if (in_array($registration_informations['subscription']['offer_reference'], $api_plugin['required_offers'])) {
            return false;
         }
      }

      return true;
   }


   /**
    * Install current plugin
    *
    * @param bool $disable_messages drop any messages after plugin installation
    *
    * @return bool
    */
   function installPlugin(bool $disable_messages = false):bool {
      $state =  $this->setPluginState("install");

      if ($disable_messages) {
         $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];
      }

      return $state== Plugin::NOTACTIVATED;
   }


   /**
    * Ununstall current plugin
    *
    * @return bool
    */
   function uninstallPlugin():bool {
      return $this->setPluginState("uninstall") == Plugin::NOTINSTALLED;
   }


   /**
    * Enable current plugin
    *
    * @return bool
    */
   function enablePlugin():bool {
      return $this->setPluginState("activate") == Plugin::ACTIVATED;
   }


   /**
    * Disable current plugin
    *
    * @return bool
    */
   function disablePlugin():bool {
      return $this->setPluginState("unactivate") == Plugin::NOTACTIVATED;
   }


   /**
    * Clean (remove database data) current plugin
    *
    * @return bool
    */
   function cleanPlugin():bool {
      $plugin   = new Plugin;
      if ($plugin->getFromDBbyDir($this->plugin_key)) {
         $plugin->clean($plugin->fields['id']);
      }

      if (!$plugin->getFromDBbyDir($this->plugin_key)) {
         return true;
      }

      return false;
   }

   /**
    * Check if marketplace controller has write access to install/update plugins source code.
    *
    * @return bool
    */
   public static function hasWriteAccess(): bool {
      return is_dir(GLPI_MARKETPLACE_DIR) && is_writable(GLPI_MARKETPLACE_DIR);
   }


   /**
    * Call an action method (install/enable/...) for the current plugin
    * method called internally by installPlugin, uninstallPlugin, enablePlugin, disablePlugin
    *
    * @param string $method
    *
    * @return int plugin status, @see properties of \Plugin class
    */
   private function setPluginState(string $method = ""): int {
      ob_start();
      $plugin   = new Plugin;
      $plugin->checkPluginState($this->plugin_key);
      if ($plugin->getFromDBbyDir($this->plugin_key)) {
         call_user_func([$plugin, $method], $plugin->fields['id']);
      }

      $plugin->checkPluginState($this->plugin_key);
      $plugin->getFromDBbyDir($this->plugin_key);

      // reload plugins
      $plugin->init(true);

      ob_end_clean();

      return $plugin->fields['state'] ?? -1;
   }


   /**
    * Return current config of for the replacement of former plugins list
    *
    * @return int config status (self::MP_REPLACE_ASK, self::MP_REPLACE_YES, self::MP_REPLACE_NEVER)
    */
   static function getPluginPageConfig() {
      $config = Config::getConfigurationValues('core', ['marketplace_replace_plugins']);

      return (int) ($config['marketplace_replace_plugins'] ?? self::MP_REPLACE_ASK);
   }
}
