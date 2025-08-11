<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\Marketplace;

use CommonGLPI;
use Config;
use CronTask;
use Glpi\Exception\Http\HttpException;
use Glpi\Marketplace\Api\Plugins as PluginsApi;
use GLPINetwork;
use NotificationEvent;
use Plugin;
use Safe\Exceptions\FilesystemException;
use Session;
use Symfony\Component\HttpFoundation\Response;
use Toolbox;
use wapmorgan\UnifiedArchive\Exceptions\ArchiveExtractionException;
use wapmorgan\UnifiedArchive\Formats;
use wapmorgan\UnifiedArchive\UnifiedArchive;

use function Safe\ini_set;
use function Safe\ob_end_clean;
use function Safe\ob_start;
use function Safe\parse_url;
use function Safe\realpath;
use function Safe\session_write_close;

/**
 * Nota: `CommonGLPI` is required here to be able to provide a displayable name for its crons and notifications.
 */
class Controller extends CommonGLPI
{
    protected $plugin_key = "";

    public static $rightname = 'config';
    public static $api       = null;

    /**
     * Prompt to replace the classic plugins page with the Marketplace
     * @var int
     */
    public const MP_REPLACE_ASK   = 1;
    /**
     * Replace the classic plugins page with the Marketplace
     * @var int
     */
    public const MP_REPLACE_YES   = 2;
    /**
     * Do not replace the classic plugins page with the Marketplace
     * @var int
     */
    public const MP_REPLACE_NEVER = 3;

    /**
     * Disable all access to the Marketplace.
     * @var int
     */
    public const MP_MODE_DISABLED = 0;
    /**
     * Allow access to the Marketplace via the CLI only.
     * @var int
     */
    public const MP_MODE_CLI_ONLY = 1;
    /**
     * Allow access to the Marketplace via the web interface only.
     * @var int
     */
    public const MP_MODE_WEB_ONLY = 2;
    /**
     * Allow access to the Marketplace via both the CLI and the web interface.
     * @var int
     */
    public const MP_MODE_FULL     = 3;

    public function __construct(string $plugin_key = "")
    {
        $this->plugin_key = $plugin_key;
    }


    public static function getTypeName($nb = 0)
    {
        return __('Marketplace');
    }

    /**
     * Check if the Marketplace can be used via the CLI.
     * @return bool
     */
    public static function isCLIAllowed(): bool
    {
        return (GLPI_MARKETPLACE_ENABLE & self::MP_MODE_CLI_ONLY) === self::MP_MODE_CLI_ONLY;
    }

    /**
     * Check if the Marketplace can be used via the web interface.
     * @return bool
     */
    public static function isWebAllowed(): bool
    {
        return (GLPI_MARKETPLACE_ENABLE & self::MP_MODE_WEB_ONLY) === self::MP_MODE_WEB_ONLY;
    }

    /**
     * singleton return the current api instance
     *
     * @return PluginsApi
     */
    public static function getAPI(): PluginsApi
    {
        return self::$api ?? (self::$api = new PluginsApi());
    }

    /**
     * Get the version information of the plugin
     *
     * @param array $plugin plugin data
     * @param string $version version to check
     * @return array|null Version information or null if no information about the specified version is available
     */
    private static function getPluginVersionInfo(array $plugin, string $version): ?array
    {
        $versions = $plugin['versions'] ?? [];
        foreach ($versions as $v) {
            if ($v['num'] === $version) {
                return $v;
            }
        }
        return null;
    }

    /**
     * Download and uncompress plugin archive
     *
     * @param bool $auto_install Automatically install the plugin after download
     * @param ?string $version Download a specific version of the plugin
     * @return bool
     */
    public function downloadPlugin($auto_install = true, ?string $version = null): bool
    {
        if (!self::hasWriteAccess()) {
            return false;
        }

        $api      = self::getAPI();
        $plugin   = $api->getPlugin($this->plugin_key, true);

        if ($version === null) {
            $url = $plugin['installation_url'] ?? "";
        } else {
            $specific_version = self::getPluginVersionInfo($plugin, $version);
            $url = null;
            if ($specific_version !== null) {
                $url = $specific_version['download_url'] ?? null;
            }
            if ($url === null) {
                Session::addMessageAfterRedirect(
                    __s('Cannot find the specified version of the plugin.'),
                    false,
                    ERROR
                );
                return false;
            }
        }
        $filename = basename(parse_url($url, PHP_URL_PATH));
        $dest     = GLPI_TMP_DIR . '/' . $filename;

        if (!$api->downloadArchive($url, $dest, $this->plugin_key)) {
            Session::addMessageAfterRedirect(
                __s('Unable to download plugin archive.'),
                false,
                ERROR
            );
            return false;
        }

        // extract the archive
        if (!UnifiedArchive::canOpen($dest)) {
            $type = Formats::detectArchiveFormat($dest);
            Session::addMessageAfterRedirect(
                htmlescape(sprintf(__('Plugin archive format is not supported by your system : %s.'), $type)),
                false,
                ERROR
            );
            return false;
        }

        // Some plugins archives may be huge, as they may embed some binaries.
        // Upgrade memory limit to 512M, which should be enough.
        $memory_limit = (int) Toolbox::getMemoryLimit();
        if ($memory_limit > 0 && $memory_limit < (512 * 1024 * 1024)) {
            ini_set('memory_limit', '512M');
        }

        $archive = UnifiedArchive::open($dest);
        $error = $archive === null;
        if (!$error) {
            // clean dir in case of update
            Toolbox::deleteDir(GLPI_MARKETPLACE_DIR . "/{$this->plugin_key}");

            try {
                // copy files
                $archive->extract(GLPI_MARKETPLACE_DIR) !== false;
            } catch (ArchiveExtractionException $e) {
                $error = true;
            }
        }

        if ($error) {
            Session::addMessageAfterRedirect(
                __s('Unable to extract plugin archive.'),
                false,
                ERROR
            );
            return false;
        }

        $plugin_inst = new Plugin();

        if (
            $plugin_inst->getFromDBbyDir($this->plugin_key)
            && !in_array($plugin_inst->fields['state'], [Plugin::ANEW, Plugin::NOTINSTALLED, Plugin::NOTUPDATED])
        ) {
            // Plugin was already existing, make it "not updated" before checking its state
            // to prevent message like 'Plugin "xxx" version changed. It has been deactivated as its update process has to be launched.'.
            $plugin_inst->update([
                'id'    => $plugin_inst->fields['id'],
                'state' => Plugin::NOTUPDATED,
            ]);
        }

        $plugin_inst->checkPluginState($this->plugin_key);
        $plugin_inst->getFromDBbyDir($this->plugin_key);

        // inform api the plugin has been downloaded
        $api->incrementPluginDownload($this->plugin_key, $plugin_inst->fields['version']);

        if (!$auto_install || $plugin_inst->getPluginOption($this->plugin_key, Plugin::OPTION_AUTOINSTALL_DISABLED, false)) {
            return true;
        }

        // try to install (or update) directly the plugin
        return $this->installPlugin();
    }


    /**
     * Get plugin archive from its download URL and serve it to the browser.
     */
    public function proxifyPluginArchive(): Response
    {
        // close session to prevent blocking other requests
        session_write_close();

        $api    = self::getAPI();
        $plugin = $api->getPlugin($this->plugin_key, true);

        if (!array_key_exists('installation_url', $plugin) || empty($plugin['installation_url'])) {
            $exception = new HttpException(500);
            $exception->setMessageToDisplay(__('Unable to download plugin archive.'));
            throw $exception;
        }

        $url      = $plugin['installation_url'];
        $filename = basename(parse_url($url, PHP_URL_PATH));
        $dest     = GLPI_TMP_DIR . '/' . mt_rand() . '.' . $filename;

        if (!$api->downloadArchive($url, $dest, $this->plugin_key, false)) {
            $exception = new HttpException(500);
            $exception->setMessageToDisplay(__('Unable to download plugin archive.'));
            throw $exception;
        }

        return Toolbox::getFileAsResponse($dest, $filename);
    }

    /**
     * Check if plugin can be overwritten.
     *
     * @return bool
     */
    public function canBeOverwritten(): bool
    {
        $found_in_marketplace_dir  = file_exists(GLPI_MARKETPLACE_DIR . '/' . $this->plugin_key . '/setup.php');

        // Compute marketplace dir priority
        $marketplace_priority = null;
        foreach (GLPI_PLUGINS_DIRECTORIES as $position => $base_dir) {
            try {
                if (realpath($base_dir) === realpath(GLPI_MARKETPLACE_DIR)) {
                    $marketplace_priority = -$position;
                    break;
                }
            } catch (FilesystemException $e) {
                //no error
            }
        }

        $found_outside_marketplace = false;
        $found_dir_priority        = null;
        foreach (GLPI_PLUGINS_DIRECTORIES as $position => $base_dir) {
            if (file_exists($base_dir . '/' . $this->plugin_key . '/setup.php')) {
                $found_outside_marketplace = true;
                $found_dir_priority = -$position;
                break; // Do not search in other directories with lower priorities
            }
        }

        if ($found_outside_marketplace) {
            if ($found_dir_priority > $marketplace_priority) {
                // Plugin has been found outside marketplace and marketplace priority is lower than its parent directory
                // -> disallow plugin update from marketplace as it cannot be loaded from there.
                return false;
            } elseif ($found_in_marketplace_dir) {
                // Plugin has been found on marketplace and marketplace priority is higher than other location
                // -> allow plugin update from marketplace as it is already loaded from there.
                return is_writable(GLPI_MARKETPLACE_DIR . '/' . $this->plugin_key) && !self::hasVcsDirectory($this->plugin_key);
            } else {
                // Plugin has been found outside marketplace and does not exist in marketplace
                // -> allow plugin update unless GLPI_MARKETPLACE_ALLOW_OVERRIDE is false.
                return GLPI_MARKETPLACE_ALLOW_OVERRIDE && self::hasWriteAccess() && !self::hasVcsDirectory($this->plugin_key);
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
    public function checkUpdate(?Plugin $plugin_inst = null)
    {
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
    public static function getAllUpdates()
    {
        $plugin_inst = new Plugin();
        $plugin_inst->checkStates(true); // force synchronization of the DB data with the filesystem data
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


    public static function cronInfo($name)
    {
        return ['description' => __('Check all plugin updates')];
    }


    /**
     * Crontask : Check for plugins updates
     *
     * @param CronTask|null $task to log, if NULL display (default NULL)
     *
     * @return integer 0 : nothing to do 1 : done with success
     */
    public static function cronCheckAllUpdates(?CronTask $task = null): int
    {
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
                'plugins' => $updates,
            ]);
        }

        return $cron_status;
    }


    /**
     * Do the current plugin requires some Glpi Network offers
     *
     * @return array [offer ref => offer title]
     */
    public function getRequiredOffers(): array
    {
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
    public function canBeDownloaded(?string $version = null)
    {
        $api        = self::getAPI();
        $api_plugin = $api->getPlugin($this->plugin_key);

        $url = '';
        if ($version !== null) {
            $specific_version = self::getPluginVersionInfo($api_plugin, $version);
            if ($specific_version !== null) {
                $url = $specific_version['download_url'] ?? '';
            }
        } else {
            $url = $api_plugin['installation_url'] ?? '';
        }
        return strlen($url) > 0;
    }

    /**
     * Check if a given plugin has available versions for current GLPI instance.
     *
     * @return bool
     */
    public function isAvailable()
    {
        $api          = self::getAPI();
        $api_plugin   = $api->getPlugin($this->plugin_key);
        return count($api_plugin['versions'] ?? []) > 0;
    }

    /**
     * Check if plugin is eligible inside an higher offer.
     *
     * @return bool
     */
    public function requiresHigherOffer(): bool
    {
        $api_plugin = self::getAPI()->getPlugin($this->plugin_key);

        if (empty($api_plugin['required_offers'])) {
            return false;
        }

        $registration_informations = GLPINetwork::getRegistrationInformations();
        if (
            $registration_informations['subscription'] !== null
            && $registration_informations['subscription']['is_running']
        ) {
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
    public function installPlugin(bool $disable_messages = false): bool
    {
        $state =  $this->setPluginState("install");

        if ($disable_messages) {
            $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];
        }

        return $state == Plugin::NOTACTIVATED;
    }


    /**
     * Ununstall current plugin
     *
     * @return bool
     */
    public function uninstallPlugin(): bool
    {
        return $this->setPluginState("uninstall") == Plugin::NOTINSTALLED;
    }


    /**
     * Enable current plugin
     *
     * @return bool
     */
    public function enablePlugin(): bool
    {
        return $this->setPluginState("activate") == Plugin::ACTIVATED;
    }


    /**
     * Disable current plugin
     *
     * @return bool
     */
    public function disablePlugin(): bool
    {
        return $this->setPluginState("unactivate") == Plugin::NOTACTIVATED;
    }


    /**
     * Clean (remove database data) current plugin
     *
     * @return bool
     */
    public function cleanPlugin(): bool
    {
        $plugin   = new Plugin();
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
    public static function hasWriteAccess(): bool
    {
        return is_dir(GLPI_MARKETPLACE_DIR) && is_writable(GLPI_MARKETPLACE_DIR);
    }

    /**
     * Check if a directory ".git|.hg|.svn" is present in plugins source code.
     *
     * @param string $plugin_key
     *
     * @return bool
     */
    public static function hasVcsDirectory(string $plugin_key): bool
    {
        $vcs_dirs = [
            '.git',
            '.hg', // Mercurial
            '.svn',
        ];
        foreach ($vcs_dirs as $vcs_dir) {
            if (is_dir(GLPI_MARKETPLACE_DIR . '/' . $plugin_key . '/' . $vcs_dir)) {
                return true;
            }
        }

        return false;
    }


    /**
     * Call an action method (install/enable/...) for the current plugin
     * method called internally by installPlugin, uninstallPlugin, enablePlugin, disablePlugin
     *
     * @param string $method
     *
     * @return int plugin status, @see properties of \Plugin class
     */
    private function setPluginState(string $method = ""): int
    {
        ob_start();
        $plugin   = new Plugin();
        $plugin->checkPluginState($this->plugin_key);
        if ($plugin->getFromDBbyDir($this->plugin_key)) {
            call_user_func([$plugin, $method], $plugin->fields['id']);
        }

        $plugin->checkPluginState($this->plugin_key);
        $plugin->getFromDBbyDir($this->plugin_key);

        // reload plugins
        // FIXME: The marketplace should use 2 distinct requests for its actions
        // 1. call the method (install, update, ...)
        // 2. call an endpoint to refresh the corresponding plugin card
        //
        // Indeed, forcing the plugins boot/init here may cause issues when trying to reload a plugin already loaded.
        $plugin->bootPlugins();
        $plugin->init();

        ob_end_clean();

        return $plugin->fields['state'] ?? -1;
    }


    /**
     * Return current config of for the replacement of former plugins list
     *
     * @return int config status (self::MP_REPLACE_ASK, self::MP_REPLACE_YES, self::MP_REPLACE_NEVER)
     */
    public static function getPluginPageConfig()
    {
        $config = Config::getConfigurationValues('core', ['marketplace_replace_plugins']);

        return (int) ($config['marketplace_replace_plugins'] ?? self::MP_REPLACE_ASK);
    }
}
