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
use Glpi\Marketplace\Controller  as Controller;
use \Html;
use \Plugin;
use \Config;
use \CommonGLPI;
use \GLPINetwork;
use \Toolbox;

class View extends CommonGLPI {
   static $rightname = 'config';
   static $api       = null;

   public $get_item_to_display_tab = true;


   public const COL_PAGE = 12;

   /**
    * singleton return the current api instance
    *
    * @return PluginsApi
    */
   static function getAPI(): PluginsApi {
      return self::$api ?? (self::$api = new PluginsApi());
   }


   static function getTypeName($nb = 0) {
      return __('Marketplace');
   }


   static function canCreate() {
      return self::canUpdate();
   }


   static function getIcon() {
      return "fas fa-store";
   }


   static function getSearchURL($full = true) {
      global $CFG_GLPI;

      $dir = ($full ? $CFG_GLPI['root_doc'] : '');
      return "$dir/front/marketplace.php";
   }


   function defineTabs($options = []) {
      $tabs = [
         'no_all_tab' => true
      ];
      $this->addStandardTab(__CLASS__, $tabs, $options);

      return $tabs;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if ($item->getType() == __CLASS__) {
         return [
            self::createTabEntry(__("Installed")),
            self::createTabEntry(__("Discover")),
         ];
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() == __CLASS__) {
         switch ($tabnum) {
            case 0:
               self::installed();
            break;
            case 1:
            default:
               self::discover();
               break;
         }
      }

      return true;
   }


   /**
    * Check current reigstration status and display warning messages
    *
    * @return bool
    */
   static function checkRegister() {
      global $CFG_GLPI;

      $messages   = [];
      $registered = false;

      if (!GLPINetwork::isServicesAvailable()) {
         array_push(
            $messages,
            __("GLPI Network services website seems not available from your network or offline"),
            "<a href='".$CFG_GLPI['root_doc']."/front/config.form.php?forcetab=Config$5'>".
            __("Maybe you could setup a proxy").
            "</a> ".
            __("or please check later")
         );
      } else {
         $registered = GLPINetwork::isRegistered();
         if (!$registered) {
            $config_url = $CFG_GLPI['root_doc']."/front/config.form.php?forcetab=".
                        urlencode('GLPINetwork$1');

            array_push(
               $messages,
               __('Your GLPI Network registration is not valid.'),
               __('A registration, at least a free one, is required to use marketplace!'),
               "<a href='".GLPI_NETWORK_SERVICES."'>".__('Register on GLPI Network')."</a> ".
               __('and'). " ".
               "<a href='$config_url'>".__("fill your registration key in setup.")."</a>"
            );
         }
      }

      if (count($messages)) {
         echo "<div class='warning'>";
         echo "<i class='fa fa-exclamation-triangle fa-5x'></i>";
         echo "<ul><li>" . implode('</li><li>', $messages) . "</li></ul>";
         echo "<div class='sep'></div>";
         echo "</div>";
      }

      return $registered;
   }


   /**
    * Display installed tab (only currently installed plugins)
    *
    * @param bool   $force_refresh do not rely on cache to get plugins list
    * @param bool   $only_lis display only the li tags in return html (used by ajax queries)
    * @param string $tag_filter filter the plugin list by given tag
    * @param string $string_filter filter the plugin by given string
    *
    * @return void display things
    */
   static function installed(
      bool $force_refresh = false,
      bool $only_lis = false,
      string $string_filter = ""
   ) {
      if (!self::checkRegister()) {
         return;
      }

      $api         = self::getAPI();
      $plugin_inst = new Plugin;
      $plugin_inst->init(true); // reload plugins
      $installed   = $plugin_inst->getList();
      $apiplugins  = $api->getAllPlugins($force_refresh);

      $plugins = [];
      foreach ($installed as $plugin) {
         $key     = $plugin['directory'];
         $apidata = $apiplugins[$key] ?? [];

         if (strlen($string_filter)
             && strpos(strtolower(json_encode($plugin)), strtolower($string_filter)) === false) {
            continue;
         }

         $clean_plugin = [
            'key'          => $key,
            'name'         => $plugin['name'],
            'logo_url'     => $apidata['logo_url'] ?? "",
            'description'  => $apidata['descriptions'][0]['short_description'] ?? "",
            'authors'      => $apidata['authors'] ?? [['id' => 'all', 'name' => $plugin['author'] ?? ""]],
            'license'      => $apidata['license'] ?? $plugin['license'] ?? "",
            'note'         => $apidata['note'] ?? -1,
            'homepage_url' => $apidata['homepage_url'] ?? "",
            'issues_url'   => $apidata['issues_url'] ?? "",
            'readme_url'   => $apidata['readme_url'] ?? "",
            'version'      => $plugin['version'] ?? "",
         ];

         $plugins[] = $clean_plugin;
      }

      self::displayList($plugins, "installed", $only_lis);
   }

   /**
    * Display discover tab (all availble plugins)
    *
    * @param bool   $force_refresh do not rely on cache to get plugins list
    * @param bool   $only_lis display only the li tags in return html (used by ajax queries)
    * @param string $tag_filter filter the plugin list by given tag
    * @param string $string_filter filter the plugin by given string
    * @param int    $page What's sub page of plugin we want to display
    * @param string $sort sort-alpha-asc|sort-alpha-desc|sort-dl|sort-update|sort-added|sort-note
    *
    * @return void display things
    */
   static function discover(
      bool $force = false,
      bool $only_lis = false,
      string $tag_filter = "",
      string $string_filter = "",
      int $page = 1,
      string $sort = 'sort-alpha-asc'
   ) {
      if (!self::checkRegister()) {
         return;
      }

      $api     = self::getAPI();
      $plugins = $api->getPaginatedPlugins(
         $force,
         $tag_filter,
         $string_filter,
         $page,
         self::COL_PAGE,
         $sort
      );

      if (strlen($string_filter) > 0) {
         $nb_plugins = count($plugins);
      } else {
         $nb_plugins = $api->getNbPlugins($tag_filter);
      }

      self::displayList($plugins, "discover", $only_lis, $nb_plugins, $sort);
   }


   /**
    * Return HTML part for tags list
    *
    * @return string tags list
    */
   static function getTagsHtml() {
      $api  = self::getAPI();
      $tags = $api->getTopTags();

      $tags_li = "<li class='tag active' data-tag=''>".__("All")."</li>";
      foreach ($tags as $tag) {
         $tags_li.= "<li class='tag' data-tag='{$tag['key']}'>".ucfirst($tag['tag'])."</li>";
      }

      return "<ul class='plugins-tags'>{$tags_li}</ul>";
   }


   /**
    * Display a list of plugins
    *
    * @param array $plugins list of plugins returned by
    * - \Plugin::getList
    * - \Glpi\Marketplace\Api\Plugins::getPaginatedPlugins
    * @param string $tab current display tab (discover or installed)
    * @param bool $only_lis display only the li tags in return html (used by ajax queries)
    * @param int $nb_plugins total of plugins ($plugins contains only the current page)
    * @param string $sort sort-alpha-asc|sort-alpha-desc|sort-dl|sort-update|sort-added|sort-note
    *
    * @return false|void displays things
    */
   static function displayList(
      array $plugins = [],
      string $tab = "",
      bool $only_lis = false,
      int $nb_plugins = 0,
      string $sort = 'sort-alpha-asc'
   ) {
      if (!self::canView()) {
         return false;
      }

      header("X-GLPI-Marketplace-Total: $nb_plugins");

      $plugins_li = "";
      foreach ($plugins as $plugin) {
         $plugin['description'] = self::getLocalizedDescription($plugin);
         $plugins_li.= self::getPluginCard($plugin, $tab);
      }

      if (!$only_lis) {
         // check writable state
         if (!Controller::hasWriteAccess()) {
            echo "<div class='warning'>
               <i class='fa fa-exclamation-triangle fa-5x'></i>".
               sprintf(__("We can't write on the markeplace directory (%s)."), GLPI_MARKETPLACE_DIR)."<br>".
               __("If you want to ease the plugins download, please check permissions and ownership of this directory.")."<br>".
               __("Otherwise, you will need to download and unzip the plugins archives manually.")."<br>".
            "<br></div>";
         }

         $tags_list    = $tab != "installed"
            ? "<div class='left-panel'>".self::getTagsHtml()."</div>"
            : "";
         $pagination   = $tab != "installed"
            ? self::getPaginationHtml(1, $nb_plugins)
            : "";
         $sort_controls = "";
         if ($tab === "discover") {
            $sort_controls = "
            <select class='sort-control'>
               <option value='sort-alpha-asc'
                       ".($sort == "sort-alpha-asc" ? "selected" : "")."
                       data-icon='fas fa-lg fa-sort-alpha-down'>
                  ".__("Alpha ASC")."
               </option>
               <option value='sort-alpha-desc'
                       ".($sort == "sort-alpha-desc" ? "selected" : "")."
                       data-icon='fas fa-lg fa-sort-alpha-down-alt'>
                  ".__("Alpha DESC")."
               </option>
               <option value='sort-dl'
                       ".($sort == "sort-dl'" ? "selected" : "")."
                       data-icon='fas fa-lg fa-cloud-download-alt'>
                  ".__("Most popular")."
               </option>
               <option value='sort-update'
                       ".($sort == "sort-update'" ? "selected" : "")."
                       data-icon='fas fa-lg fa-history'>
                  ".__("Last updated")."
               </option>
               <option value='sort-added'
                       ".($sort == "sort-added'" ? "selected" : "")."
                       data-icon='fas fa-lg fa-calendar-plus'>
                  ".__("Most recent")."
               </option>
               <option value='sort-note'
                       ".($sort == "sort-note'" ? "selected" : "")."
                       data-icon='fas fa-lg fa-star'>
                  ".__("Best notes")."
               </option>
            </select>";
         }

         $yourplugin   = __("Your plugin here ? Contact us.");
         $networkmail  = GLPI_NETWORK_MAIL;
         $refresh_lbl  = __("Refresh plugin list");
         $search_label = __("Filter plugin list");

         $marketplace  = <<<HTML
         <div class='marketplace $tab' data-tab='{$tab}'>
            {$tags_list}
            <div class='right-panel'>
               <div class='top-panel'>
                  <input type='search' class='filter-list' placeholder='{$search_label}'>
                  <div class='controls'>
                     $sort_controls
                     <i class='fas fa-sync-alt refresh-plugin-list'
                        title='{$refresh_lbl}'></i>
                  </div>
               </div>
               <ul class='plugins'>
                  {$plugins_li}
               </ul>
               $pagination
               <a href="mailto:{$networkmail}" class="network-mail" target="_blank">
                  $yourplugin&nbsp;<i class="far fa-envelope"></i>
               </a>
            </div>
         </div>
         <script>
            var marketplace_total_plugin = {$nb_plugins};
         </script>
HTML;
         echo $marketplace;
      } else {
         echo $plugins_li;
      }

      $js = <<<JS
      $(document).ready(function() {
         // load button tooltips
         addTooltips();

         var displaySortIcon = function(option) {
            if (!option.element) {
               return option.element;
            }
            var element = option.element;
            var icon = $(element).data('icon');

            return $("<span><i class='"+icon+"'></i>&nbsp;"+option.text+"</span>");
         };

         $('.sort-control').select2({
            templateResult: displaySortIcon,
            templateSelection: displaySortIcon,
            width: 135,
         });
      });
JS;
      echo Html::scriptBlock($js);
   }

   /**
    * Return HTML part for plugin card
    *
    * @param array $plugin informations (title, description, etc) of the plugins
    * @param string $tab current displayed tab (installed or discover)
    *
    * @return string the plugin card
    */
   static function getPluginCard(array $plugin = [], string $tab = "discover"):string {
      $plugin_key   = $plugin['key'];
      $plugin_inst  = new Plugin;
      $plugin_inst->getFromDBbyDir($plugin_key);
      $plugin_state = Plugin::getStateKey($plugin_inst->fields['state'] ?? -1);
      $buttons      = self::getButtons($plugin_key);

      $authors = implode(', ', array_column($plugin['authors'] ?? [], 'name', 'id'));
      $authors_title = Html::clean($authors);
      $authors = strlen($authors)
         ? "<i class='fas fa-fw fa-user-friends'></i>$authors"
         : "";
      $licence = is_string($plugin['license']) && isset($plugin['license']) && strlen($plugin['license'])
         ? "<i class='fas fa-fw fa-balance-scale'></i>{$plugin['license']}"
         : "";
      $version = strlen($plugin['version'] ?? "")
         ? "<i class='fas fa-fw fa-code-branch'></i>".$plugin['version']
         : "";
      $stars = ($plugin['note'] ?? -1) > 0
         ? self::getStarsHtml($plugin['note'])
         : "";

      $home_url = strlen($plugin['homepage_url'] ?? "")
         ? "<a href='{$plugin['homepage_url']}' target='_blank' >
            <i class='fas fa-home add_tooltip' title='".__s("Homepage")."'></i>
            </a>"
         : "";
      $issues_url = strlen($plugin['issues_url'] ?? "")
         ? "<a href='{$plugin['issues_url']}' target='_blank' >
            <i class='fas fa-bug add_tooltip' title='".__s("Get help")."'></i>
            </a>"
         : "";
      $readme_url = strlen($plugin['readme_url'] ?? "")
         ? "<a href='{$plugin['readme_url']}' target='_blank' >
            <i class='fas fa-book add_tooltip' title='".__s("Readme")."'></i>
            </a>"
         : "";
      $icon    = self::getPluginIcon($plugin);
      $network = self::getNetworkInformations($plugin);

      if ($tab === "discover") {
         $card = <<<HTML
         <li class="plugin {$plugin_state}" data-key="{$plugin_key}">
            <div class="main">
               <span class="icon">{$icon}</span>
               <span class="details">
                  <h3 class="title">{$plugin['name']}</h3>
                  $network
                  <p class="description">{$plugin['description']}</p>
               </span>
               <span class="buttons">
                  {$buttons}
               </span>
            </div>
            <div class="footer">
               <span class="misc-left">
                  <div class="note">{$stars}</div>
                  <div class="links">
                     {$home_url}
                     {$issues_url}
                     {$readme_url}
                  </div>
               </span>
               <span class='misc-right'>
                  <div class="license">{$licence}</div>
                  <div class="authors" title="{$authors_title}">{$authors}</div>
                  <div class="version">{$version}</div>
               </span>
            </div>
         </li>
HTML;
      } else {
         $card = <<<HTML
         <li class="plugin {$plugin_state}" data-key="{$plugin_key}">
            <div class="main">
               <span class="icon">{$icon}</span>
               <span class="details">
                  <h3 class="title">{$plugin['name']}</h3>
                  <span class='misc-right'>
                     <div class="license">{$licence}</div>
                     <div class="authors" title="{$authors_title}">{$authors}</div>
                     <div class="version">{$version}</div>
                  </span>
               </span>
               <span class="buttons">
                  {$buttons}
               </span>
            </div>
            <div class="footer">
               <span class="misc-left">
                  <div class="links">
                     {$home_url}
                     {$issues_url}
                     {$readme_url}
                  </div>
               </span>
            </div>
         </li>
HTML;
      }

      return $card;
   }

   /**
    * Return HTML part for plugin stars
    *
    * @param float|int $value current stars note on 5
    *
    * @return string plugins stars html
    */
   static function getStarsHtml(float $value = 0):string {
      $value = min(floor($value * 2) / 2, 5);

      $stars = "";
      for ($i = 1; $i < 6; $i++) {
         if ($value >= $i) {
            $stars.= "<i class='fas fa-star'></i>";
         } else if ($value + 0.5 == $i) {
            $stars.= "<i class='fas fa-star-half-alt'></i>";
         } else {
            $stars.= "<i class='far fa-star'></i>";
         }
      }

      return $stars;
   }


   /**
    * Return HTML part for plugin buttons
    *
    * @param string $plugin_key system name for the plugin
    *
    * @return string the buttons html
    */
   static function getButtons(string $plugin_key = ""): string {
      global $CFG_GLPI, $PLUGIN_HOOKS;

      $rand               = mt_rand();
      $plugin_inst        = new Plugin;
      $exists             = $plugin_inst->getFromDBbyDir($plugin_key);
      $is_installed       = $plugin_inst->isInstalled($plugin_key);
      $is_actived         = $plugin_inst->isActivated($plugin_key);
      $mk_controller      = new Controller($plugin_key);
      $update_version     = $mk_controller->checkUpdate($plugin_inst);
      $has_an_update      = $update_version !== false;
      $can_be_overwritten = $mk_controller->canBeOverwritten();
      $can_be_downloaded  = $mk_controller->canBeDownloaded();
      $required_offers    = $mk_controller->getRequiredOffers();
      $can_be_updated     = $has_an_update && $can_be_overwritten;
      $can_be_cleaned     = $exists && !$plugin_inst->isLoadable($plugin_key);
      $config_page        = $PLUGIN_HOOKS['config_page'][$plugin_key] ?? "";

      $error = "";
      if ($exists) {
         ob_start();
         $do_activate = $plugin_inst->checkVersions($plugin_key);
         if (!$do_activate) {
            $error.= "<span class='error'>" . ob_get_contents() . "</span>";
         }
         ob_end_clean();
      }

      $buttons = "";

      if (strlen($error)) {
         $buttons .="<i class='fas fa-exclamation-triangle plugin-error' id='plugin-error-$rand'></i>";
         Html::showToolTip($error, [
            'applyto' => "plugin-error-$rand",
         ]);
      }

      if ($can_be_cleaned) {
         $buttons .="<button class='modify_plugin'
                             data-action='clean_plugin'
                             title='".__s("Clean")."'>
            <i class='fas fa-broom'></i>
         </button>";
      } else if ((!$exists && !$mk_controller->hasWriteAccess())
         || ($has_an_update && !$can_be_overwritten)) {
         $plugin_data = $mk_controller->getAPI()->getPlugin($plugin_key);
         if (array_key_exists('installation_url', $plugin_data) && $can_be_downloaded) {
            $warning = "";
            if ($has_an_update) {
               $warning = __s("The plugin has an available update but its directory is not writable.")."<br>";
            }

            $warning.= sprintf(
               __s("Download archive manually, you must uncompress it in plugins directory (%s)"),
               GLPI_ROOT . '/plugins'
            );

            // Use "marketplace.download.php" proxy if archive is downloadable from GLPI marketplace plugins API
            // as this API will refuse to serve the archive if registration key is not set in headers.
            $download_url = Toolbox::startsWith($plugin_data['installation_url'], GLPI_MARKETPLACE_PLUGINS_API_URI)
               ? $CFG_GLPI['root_doc'] . '/front/marketplace.download.php?key=' . $plugin_key
               : $plugin_data['installation_url'];

            $buttons .="<a href='{$download_url}' target='_blank'>
               <button title='$warning' class='add_tooltip download_manually'><i class='fas fa-archive'></i></button>
            </a>";
         }
      } else if ($can_be_downloaded) {
         if (!$exists) {
            $buttons .="<button class='modify_plugin'
                                data-action='download_plugin'
                                title='".__s("Download")."'>
               <i class='fas fa-cloud-download-alt'></i>
            </button>";
         } else if ($can_be_updated) {
            $update_title = sprintf(
               __s("A new version (%s) is available, update ?", 'marketplace'),
               $update_version
            );
            $buttons .="<button class='modify_plugin'
                                data-action='update_plugin'
                                title='{$update_title}'>
               <i class='fas fa-cloud-download-alt'></i>
            </button>";
         }
      }

      if ($mk_controller->requiresHigherOffer()) {
            $warning = sprintf(
               __s("You need a superior GLPI-Network offer to access to this plugin (%s)"),
               implode(', ', $required_offers)
            );

            $buttons .="<a href='".GLPI_NETWORK_SERVICES."' target='_blank'>
               <button class='add_tooltip need_offers' title='$warning'>
                  <i class='fas fa-exclamation-triangle'></i>
               </button>
            </a>";
      }

      if ($exists && !$can_be_cleaned && !$is_installed && !strlen($error)) {
         $buttons .="<button class='modify_plugin'
                             data-action='install_plugin'
                             title='".__s("Install")."'>
            <i class='fas fa-folder-plus'></i>
         </button>";
      }

      if ($is_installed) {
         if (!strlen($error)) {
            if ($is_actived) {
               $buttons .="<button class='modify_plugin'
                                   data-action='disable_plugin'
                                   title='".__s("Disable")."'>
                  <i class='fas fa-toggle-on'></i>
               </button>";
            } else {
               $buttons .="<button class='modify_plugin'
                                   data-action='enable_plugin'
                                   title='".__s("Enable")."'>
                  <i class='fas fa-toggle-off'></i>
               </button>";
            }
         }

         $buttons .="<button class='modify_plugin'
                             data-action='uninstall_plugin'
                             title='".__s("Uninstall")."'>
            <i class='fas fa-folder-minus'></i>
         </button>";

         if (!strlen($error) && $is_actived && $config_page) {
            $plugin_dir = Plugin::getWebDir($plugin_key, true);
               $config_url = "$plugin_dir/$config_page";
               $buttons .="<a href='$config_url'>
                  <button class='add_tooltip' title='".__s("Configure")."'>
                     <i class='fas fa-wrench'></i>
                  </button>
               </a>";
         }
      }

      return $buttons;
   }

   /**
    * Return HTML part for plugin logo/icon
    *
    * @param array $plugin data of the plugin.
    *                      If it contains a key logo_url, the current will be inserted in a img tag
    *                      else, it will use initials from plugin friendly name to construct
    *                      a short and colored logo
    *
    * @return string the jtml for plugin logo
    */
   static function getPluginIcon(array $plugin = []) {
      $icon = "";
      if (strlen($plugin['logo_url'])) {
         $icon = "<img src='{$plugin['logo_url']}'>";
      } else {
         $words = explode(" ", $plugin['name']);
         $initials = "";
         for ($i = 0; $i < 2; $i++) {
            if (isset($words[$i])) {
               $initials.= mb_substr($words[$i], 0, 1);
            }
         }
         $bg_color = Toolbox::getColorForString($initials);
         $fg_color = Toolbox::getFgColor($bg_color);
         $icon = "<span style='background-color: $bg_color; color: $fg_color'
                        class='icon-text'>$initials</span>";
      }

      return $icon;
   }


   /**
    * Return HTML part for Glpi Network informations for a given plugin
    * @param array $plugin data of the plugin.
    *                      if check agains plugin key if we need some subscription to use it
    * @return string the subscription information html
    */
   static function getNetworkInformations(array $plugin = []): string {
      $mk_controller  = new Controller($plugin['key']);
      $require_offers = $mk_controller->getRequiredOffers();

      $html = "";
      if (count($require_offers)) {
         $fst_offer  = array_splice($require_offers, 0, 1);
         $offerkey   = key($fst_offer);
         $offerlabel = current($fst_offer);

         $html = "<div class='offers'>
            <a href='".GLPI_NETWORK_SERVICES."' target='_blank'
               class='badge glpi-network'
               title='".__s("You must have a GLPI Network subscription to get this plugin")."'>
               <i class='fas fa-star'></i>".__('Network')."
            </a>
            <a href='".GLPI_NETWORK_SERVICES."' target='_blank'
               class='badge $offerkey'
               title='".sprintf(__s("You need at least the %s subscription level to get this plugin"), $offerlabel)."'>
               $offerlabel
            </a>
         </div>";
      }

      return $html;
   }


   /**
    * Retrieve localized description for a given plugin and matching the session lang
    *
    * @param array $plugin data of the plugin.
    *                      in the `description` key, we must found an array of localized descirption
    *                      indexed by lang key, return the good one
    * @param string $version short_description or long_description
    *
    * @return string the localized description
    */
   static function getLocalizedDescription(array $plugin = [], string $version = 'short_description'): string {
      global $CFG_GLPI;

      $userlang = $CFG_GLPI['languages'][$_SESSION['glpilanguage']][3] ?? "en";

      if (!isset($plugin['descriptions'])) {
         return "";
      }

      $description = "";
      $fallback = "";
      foreach ($plugin['descriptions'] as $current) {
         if ($current['lang'] == $userlang) {
            $description = $current[$version];
            break;
         }

         if ($current['lang'] == "en") {
            $fallback = $current[$version];
         }
      }

      if (strlen($description) === 0) {
         $description = $fallback;
      }

      return $description;
   }


   /**
    * Return HTML part for plugins pagination
    *
    * @param int $current_page
    * @param int $total
    * @param bool $only_li display only the li tags in return html (used by ajax queries)
    *
    * @return string the pagination html
    */
   static function getPaginationHtml(int $current_page = 1, int $total = 1, bool $only_li = false): string {
      if ($total <= self::COL_PAGE) {
         return "";
      }

      $nb_pages = ceil($total / self::COL_PAGE);

      $prev = max($current_page - 1, 1);
      $next = min($current_page + 1, $nb_pages);

      $p_cls = $current_page === 1
         ? "class='nav-disabled'"
         : "";
      $n_cls = $current_page == $nb_pages
         ? "class='nav-disabled'"
         : "";

      $html = "";
      if (!$only_li) {
         $html.= "<ul class='pagination'>";
      }
      $html.= "<li data-page='$prev' $p_cls><i class='fas fa-angle-left'></i></li>";
      $dots = false;
      for ($i = 1; $i <= $nb_pages; $i++) {
         if ($i >= 3
             && ($i < $current_page - 1
                 || $i > $current_page + 1)
             && $i < $nb_pages - 1) {
            if (!$dots) {
               $html.= "<li class='nav-disabled dots'>...</li>";
            }
            $dots = true;
            continue;
         }
         $dots = false;

         $current = ($current_page === $i)
            ? "class='current'"
            : "";
         $html.= "<li data-page='$i' $current>$i</li>";
      }
      $html.= "<li data-page='$next' $n_cls><i class='fas fa-angle-right'></i></li>";
      $html.= "<li class='nb_plugin'>".sprintf(_n("%s plugin", "%s plugins", $total), $total)."</li>";
      if (!$only_li) {
         $html.= "</ul>";
      }

      return $html;
   }


   /**
    * Display a dialog inviting the user to switch from former plugin list to marketplace new view.
    *
    * @return void display things
    */
   static function showFeatureSwitchDialog() {
      global $CFG_GLPI;

      if (isset($_POST['marketplace_replace'])) {
         $mp_value = isset($_POST['marketplace_replace_plugins_yes'])
            ? Controller::MP_REPLACE_YES
            : (isset($_POST['marketplace_replace_plugins_never'])
               ? Controller::MP_REPLACE_NEVER
               : Controller::MP_REPLACE_ASK);
         Config::setConfigurationValues('core', [
            'marketplace_replace_plugins' => $mp_value
         ]);

         // is user agree, redirect him to marketplace
         if ($mp_value === Controller::MP_REPLACE_YES) {
            Html::redirect($CFG_GLPI["root_doc"]."/front/marketplace.php");
         }

         // avoid annoying user for the current session
         $_SESSION['skip_marketplace_invitation'] = true;
      }

      // show modal for asking user preference
      if (Controller::getPluginPageConfig() == Controller::MP_REPLACE_ASK
          && !isset($_SESSION['skip_marketplace_invitation'])
          && GLPI_INSTALL_MODE !== 'CLOUD') {
         echo "<form id='marketplace_dialog' method='POST'>";
         echo Html::image($CFG_GLPI['root_doc']."/pics/screenshots/marketplace.png", [
            'style' => 'width: 600px',
         ]);
         echo "<br><br>";
         echo __("GLPI provides a new marketplace to download and install plugins.");
         echo "<br><br>";
         echo "<b>".__("Do you want to replace the plugins setup page by the new marketplace ?")."</b>";
         echo "<hr><br>";
         echo Html::submit("<i class='fa fa-check'></i>&nbsp;".__('Yes'), [
            'name' => 'marketplace_replace_plugins_yes'
         ]);
         echo "&nbsp;";
         echo Html::submit("<i class='fa fa-times'></i>&nbsp;".__('No'), [
            'name' => 'marketplace_replace_plugins_never',
            'class' => 'secondary'
            ]);
         echo "&nbsp;";
         echo Html::submit("<i class='fa fa-clock'></i>&nbsp;".__('Later'), [
            'name'  => 'marketplace_replace_plugins_later',
            'class' => 'secondary'
         ]);
         echo Html::hidden('marketplace_replace');

         Html::closeForm();

         echo Html::scriptBlock("$(document).ready(function() {
            $('#marketplace_dialog').dialog({
               'modal': true,
               'width': 'auto',
               'title': \"".__s("Switch to marketplace")."\"
            });
         });");
      }
   }

}
