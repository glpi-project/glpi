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
use Document;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Marketplace\Api\Plugins as PluginsApi;
use GLPINetwork;
use Html;
use Plugin;
use Toolbox;

use function Safe\json_encode;
use function Safe\ob_end_clean;
use function Safe\ob_start;
use function Safe\parse_url;

class View extends CommonGLPI
{
    public static $rightname = 'config';
    public static $api       = null;

    public $get_item_to_display_tab = true;


    public const COL_PAGE = 12;

    protected static bool $offline_mode = false;

    /**
     * singleton return the current api instance
     *
     * @return PluginsApi
     */
    public static function getAPI(): PluginsApi
    {
        return self::$api ?? (self::$api = new PluginsApi());
    }


    public static function getTypeName($nb = 0)
    {
        return __('Marketplace');
    }


    public static function canCreate(): bool
    {
        return self::canUpdate();
    }


    public static function getIcon()
    {
        return "ti ti-building-store";
    }


    public static function getSearchURL($full = true)
    {
        global $CFG_GLPI;

        $dir = ($full ? $CFG_GLPI['root_doc'] : '');
        return "$dir/front/marketplace.php";
    }


    public function defineTabs($options = [])
    {
        $tabs = [
            'no_all_tab' => true,
        ];
        $this->addStandardTab(self::class, $tabs, $options);

        return $tabs;
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!Controller::isWebAllowed()) {
            return '';
        }
        if ($item->getType() == self::class) {
            return [
                self::createTabEntry(__("Installed")),
                self::createTabEntry(__("Discover")),
            ];
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!Controller::isWebAllowed()) {
            return false;
        }
        if ($item->getType() == self::class) {
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
     * Check current registration status and display warning messages
     *
     * @param bool $force Force re-check the registration status even if it was already checked
     * @return bool
     */
    public static function checkRegistrationStatus(bool $force = false)
    {
        if (!$force && static::$offline_mode) {
            return false;
        }

        global $CFG_GLPI;

        $messages   = [];
        $valid = false;

        if (!GLPINetwork::isServicesAvailable()) {
            $messages[] = sprintf(__s('%1$s services website seems not available from your network or offline'), 'GLPI Network');
            $messages[] = "<a href='" . htmlescape($CFG_GLPI['root_doc']) . "/front/config.form.php?forcetab=Config$5'>"
                . __s("Maybe you could setup a proxy")
                . "</a> "
                . __s("or please check later");
        } else {
            $registration_info = GLPINetwork::getRegistrationInformations();
            if (!$registration_info['is_valid']) {
                $valid = false;

                $config_url = $CFG_GLPI['root_doc'] . "/front/config.form.php?forcetab="
                        . urlencode('GLPINetwork$1');
                $messages[] = sprintf(__s('Your %1$s registration is not valid.'), 'GLPI Network');
                $messages[] = __s('A registration, at least a free one, is required to use marketplace!');
                $messages[] = "<a href='" . htmlescape(GLPI_NETWORK_SERVICES) . "'>" . sprintf(__s('Register on %1$s'), 'GLPI Network') . "</a> "
                    . __s('and') . " "
                    . "<a href='" . htmlescape($config_url) . "'>" . __s("fill your registration key in setup.") . "</a>";
            } elseif (!$registration_info['subscription']['is_running']) {
                $valid = false;
                $messages[] = sprintf(__s('Your %1$s subscription has been terminated.'), 'GLPI Network');
                $messages[] = "<a href='" . htmlescape(GLPI_NETWORK_SERVICES) . "'>" . sprintf(__s('Renew it on %1$s.'), 'GLPI Network') . "</a> ";
            } else {
                $valid = true;
            }
        }

        if (count($messages)) {
            echo "<div class='alert alert-important alert-warning d-flex'>";
            echo "<i class='fs-3x ti ti-alert-triangle'></i>";
            echo "<ul><li>" . implode('</li><li>', $messages) . "</li></ul>";
            echo "</div>";
        }

        static::$offline_mode = !$valid;
        return $valid;
    }


    /**
     * Display installed tab (only currently installed plugins)
     *
     * @param bool   $force_refresh do not rely on cache to get plugins list
     * @param bool   $only_lis display only the li tags in return html (used by ajax queries)
     * @param string $string_filter filter the plugin by given string
     *
     * @return void display things
     */
    public static function installed(
        bool $force_refresh = false,
        bool $only_lis = false,
        string $string_filter = ""
    ) {
        global $CFG_GLPI;

        $plugin_inst = new Plugin();
        $plugin_inst->checkStates(true); // force synchronization of the DB data with the filesystem data
        $installed   = $plugin_inst->getList();

        $apiplugins  = [];
        if (self::checkRegistrationStatus()) {
            $api         = self::getAPI();
            $apiplugins  = $api->getAllPlugins($force_refresh);
        }

        $plugins = [];
        foreach ($installed as $plugin) {
            $key     = $plugin['directory'];
            $apidata = $apiplugins[$key] ?? [];

            if (
                strlen($string_filter)
                && !str_contains(strtolower(json_encode($plugin)), strtolower($string_filter))
            ) {
                continue;
            }

            $logo_url = $apidata['logo_url'] ?? '';
            if (Document::isImage(\sprintf('%s/logo.png', Plugin::getPhpDir($key)))) {
                // Use the local logo.png file if it exists.
                $logo_url = sprintf('%s/Plugin/%s/Logo', $CFG_GLPI['root_doc'], $key);
            }

            $clean_plugin = [
                'key'           => $key,
                'name'          => $plugin['name'],
                'logo_url'      => $logo_url,
                'description'   => $apidata['descriptions'][0]['short_description'] ?? "",
                'authors'       => $apidata['authors'] ?? [['id' => 'all', 'name' => $plugin['author'] ?? ""]],
                'license'       => $apidata['license'] ?? $plugin['license'] ?? "",
                'note'          => $apidata['note'] ?? -1,
                'homepage_url'  => $apidata['homepage_url'] ?? "",
                'issues_url'    => $apidata['issues_url'] ?? "",
                'readme_url'    => $apidata['readme_url'] ?? "",
                'version'       => $plugin['version'] ?? "",
                'changelog_url' => $apidata['changelog_url'] ?? "",
            ];

            $plugins[] = $clean_plugin;
        }

        self::displayList($plugins, "installed", $only_lis);
    }

    /**
     * Display discover tab (all availble plugins)
     *
     * @param bool   $force do not rely on cache to get plugins list
     * @param bool   $only_lis display only the li tags in return html (used by ajax queries)
     * @param string $tag_filter filter the plugin list by given tag
     * @param string $string_filter filter the plugin by given string
     * @param int    $page What's sub page of plugin we want to display
     * @param string $sort sort-alpha-asc|sort-alpha-desc|sort-dl|sort-update|sort-added|sort-note
     *
     * @return void display things
     */
    public static function discover(
        bool $force = false,
        bool $only_lis = false,
        string $tag_filter = "",
        string $string_filter = "",
        int $page = 1,
        string $sort = 'sort-alpha-asc'
    ) {
        if (!self::checkRegistrationStatus()) {
            return;
        }

        $nb_plugins = 0;

        $api     = self::getAPI();
        $plugins = $api->getPaginatedPlugins(
            $force,
            $tag_filter,
            $string_filter,
            $page,
            self::COL_PAGE,
            $sort,
            $nb_plugins
        );

        header("X-GLPI-Marketplace-Total: $nb_plugins");
        self::displayList($plugins, "discover", $only_lis, $nb_plugins, $sort, $api->isListTruncated());
    }


    /**
     * Return HTML part for tags list
     *
     * @return string tags list
     */
    public static function getTagsHtml()
    {
        $api  = self::getAPI();
        $tags = $api->getTopTags();

        $tags_li = "<li class='tag active' data-tag=''>" . __s("All") . "</li>";
        foreach ($tags as $tag) {
            $tags_li .= "<li class='tag' data-tag='" . htmlescape($tag['key']) . "'>" . htmlescape(ucfirst($tag['tag'])) . "</li>";
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
    public static function displayList(
        array $plugins = [],
        string $tab = "",
        bool $only_lis = false,
        int $nb_plugins = 0,
        string $sort = 'sort-alpha-asc',
        bool $is_list_truncated = false
    ) {
        if (!self::canView()) {
            return false;
        }

        $messages = '';
        if ($is_list_truncated) {
            if (count($plugins) === 0) {
                $msg = sprintf(__('Unable to fetch plugin list due to %s services website unavailability. Please try again later.'), 'GLPI Network');
            } else {
                // Not completely offline. Do not treat as fully offline.
                $msg = sprintf(__('Plugin list may be truncated due to %s services website unavailability. Please try again later.'), 'GLPI Network');
            }
            $messages = '<li class="warning"><i class="ti ti-alert-triangle fs-3x"></i>' . htmlescape($msg) . '</li>';
        }

        $plugins_li = "";
        foreach ($plugins as $plugin) {
            $plugin['description'] = self::getLocalizedDescription($plugin);
            $plugins_li .= self::getPluginCard($plugin, $tab);
        }

        if (!$only_lis) {
            // check writable state
            if (!Controller::hasWriteAccess()) {
                echo "<div class='alert alert-warning'><i class='ti ti-alert-triangle fs-5x'></i>"
                      . htmlescape(sprintf(__("We can't write on the markeplace directory (%s)."), GLPI_MARKETPLACE_DIR))
                      . "<br>"
                      . __s("If you want to ease the plugins download, please check permissions and ownership of this directory.")
                      . "<br>"
                      . __s("Otherwise, you will need to download and unzip the plugins archives manually.")
                      . "<br>"
                      . "<br>"
                      . "</div>";
            }

            if (static::$offline_mode && $tab !== 'installed') {
                $marketplace  = <<<HTML
                <div class='marketplace $tab' data-tab='{$tab}'>
                    <div class='left-panel'></div>
                    <div class='right-panel'>
                        <div class='top-panel'>
                            <div class='controls'></div>
                        </div>
                        <ul class='plugins'>
                            {$messages}
                            {$plugins_li}
                        </ul>
                    </div>
                </div>
HTML;
                echo $marketplace;
                return;
            }
            $suspend_banner = $tab === "installed"
                ? (new Plugin())->getPluginsListSuspendBanner()
                : '';
            $tags_list    = $tab != "installed"
                ? "<div class='left-panel'>" . self::getTagsHtml() . "</div>"
                : "";
            $pagination   = $tab != "installed"
                ? self::getPaginationHtml(1, $nb_plugins)
                : "";
            $sort_controls = "";
            if ($tab === "discover") {
                $sort_controls = "
                    <select class='sort-control form-select form-select-sm'>
                        <option value='sort-alpha-asc'
                                " . ($sort == "sort-alpha-asc" ? "selected" : "") . "
                                data-icon='fs-2 ti ti-sort-ascending-letters'>
                            " . __s("Alpha ASC") . "
                        </option>
                        <option value='sort-alpha-desc'
                                " . ($sort == "sort-alpha-desc" ? "selected" : "") . "
                                data-icon='fs-2 ti ti-sort-descending-letters'>
                            " . __s("Alpha DESC") . "
                        </option>
                        <option value='sort-dl'
                                " . ($sort == "sort-dl'" ? "selected" : "") . "
                                data-icon='fs-2 ti ti-cloud-download'>
                            " . __s("Most popular") . "
                        </option>
                        <option value='sort-update'
                                " . ($sort == "sort-update'" ? "selected" : "") . "
                                data-icon='fs-2 ti ti-history'>
                            " . __s("Last updated") . "
                        </option>
                        <option value='sort-added'
                                " . ($sort == "sort-added'" ? "selected" : "") . "
                                data-icon='fs-2 ti ti-calendar-time'>
                            " . __s("Most recent") . "
                        </option>
                        <option value='sort-note'
                                " . ($sort == "sort-note'" ? "selected" : "") . "
                                data-icon='fs-2 ti ti-star'>
                            " . __s("Best notes") . "
                        </option>
                    </select>";
            }

            $yourplugin   = __s("Your plugin here? Contact us.");
            $networkmail  = htmlescape(GLPI_NETWORK_MAIL);
            $refresh_lbl  = __s("Refresh plugin list");
            $search_label = __s("Filter plugin list");

            $marketplace  = <<<HTML
                <div class='marketplace $tab' data-tab='{$tab}'>
                    {$tags_list}
                    <div class='right-panel'>
                        {$suspend_banner}
                        <div class='top-panel'>
                            <input type='search' class='filter-list form-control' placeholder='{$search_label}'>
                            <div class='controls'>
                                $sort_controls
                                <i class='ti ti-refresh refresh-plugin-list' title='{$refresh_lbl}'></i>
                            </div>
                        </div>
                        <ul class='plugins'>
                            {$messages}
                            {$plugins_li}
                        </ul>
                        $pagination
                        <a href="mailto:{$networkmail}" class="network-mail" target="_blank">
                            $yourplugin&nbsp;<i class="ti ti-mail"></i>
                        </a>
                    </div>
                </div>
                <script>
                    var marketplace_total_plugin = {$nb_plugins};
                </script>
HTML;
            echo $marketplace;
        } else {
            echo $messages . $plugins_li;
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

                    return $("<span><i class='" + _.escape(icon) + "'></i>&nbsp;" + _.escape(option.text) + "</span>");
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
     * @param array $plugin information (title, description, etc) of the plugins
     * @param string $tab current displayed tab (installed or discover)
     *
     * @return string the plugin card
     */
    public static function getPluginCard(array $plugin = [], string $tab = "discover"): string
    {
        $plugin_key   = $plugin['key'];
        $plugin_inst  = new Plugin();
        $plugin_inst->getFromDBbyDir($plugin_key);

        $plugin_info = [
            'key'           => $plugin['key'],
            'name'          => $plugin['name'],
            'description'   => $plugin['description'],
            'homepage_url'  => $plugin['homepage_url'],
            'issues_url'    => $plugin['issues_url'],
            'readme_url'    => $plugin['readme_url'],
            'changelog_url' => $plugin['changelog_url'],
            'license'       => $plugin['license'] ?? null,
            'version'       => $plugin['version'] ?? null,

            'icon'          => self::getPluginIcon($plugin),
            'state'         => Plugin::getStateKey($plugin_inst->fields['state'] ?? -1),
            'network_info'  => !static::$offline_mode ? self::getNetworkInformations($plugin) : '',
            'buttons'       => self::getButtons($plugin_key),
            'authors'       => array_column($plugin['authors'] ?? [], 'name', 'id'),
            'stars'         => ($plugin['note'] ?? -1) > 0 ? self::getStarsHtml($plugin['note']) : '',
        ];
        return TemplateRenderer::getInstance()->render('pages/setup/marketplace/card.html.twig', [
            'tab'    => $tab,
            'plugin' => $plugin_info,
        ]);
    }

    /**
     * Return HTML part for plugin stars
     *
     * @param float $value current stars note on 5
     *
     * @return string plugins stars html
     */
    public static function getStarsHtml(float $value = 0): string
    {
        $value = min(floor($value * 2) / 2, 5);

        $stars = "";
        for ($i = 1; $i < 6; $i++) {
            if ($value >= $i) {
                $stars .= "<i class='ti ti-star-filled'></i>";
            } elseif ($value + 0.5 == $i) {
                $stars .= "<i class='ti ti-star-half-filled'></i>";
            } else {
                $stars .= "<i class='ti ti-star'></i>";
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
    public static function getButtons(string $plugin_key = ""): string
    {
        global $CFG_GLPI, $PLUGIN_HOOKS;

        if ((new Plugin())->isPluginsExecutionSuspended()) {
            return \sprintf(
                '<span class="text-info" data-bs-toggle="tooltip" title="%s"><i class="ti ti-info-circle-filled"></i></span>',
                __s('The plugins maintenance actions are disabled when the plugins execution is suspended.')
            );
        }

        $plugin_inst        = new Plugin();
        $exists             = $plugin_inst->getFromDBbyDir($plugin_key);
        $is_installed       = $plugin_inst->isInstalled($plugin_key);
        $is_actived         = $plugin_inst->isActivated($plugin_key);

        // The following block of buttons require the marketplace to be online
        $mk_controller = !static::$offline_mode ? new Controller($plugin_key) : null;
        $web_update_version = $mk_controller ? $mk_controller->checkUpdate($plugin_inst) : false;
        $has_web_update = $web_update_version !== false;
        $is_available = $mk_controller && $mk_controller->isAvailable();
        $can_be_overwritten = $mk_controller && $mk_controller->canBeOverwritten();
        $can_be_downloaded = $mk_controller && $mk_controller->canBeDownloaded();
        $required_offers = $mk_controller ? $mk_controller->getRequiredOffers() : false;
        $can_be_updated = $has_web_update && $can_be_overwritten;

        $config_page        = $PLUGIN_HOOKS['config_page'][$plugin_key] ?? "";
        $must_be_cleaned   = $exists && !$plugin_inst->isLoadable($plugin_key);
        $has_local_install = $exists && !$must_be_cleaned && !$is_installed;
        $has_local_update  = $exists && !$must_be_cleaned && $plugin_inst->isUpdatable($plugin_key);

        $error = "";
        if ($exists && !$must_be_cleaned) {
            ob_start();
            $do_activate = $plugin_inst->checkVersions($plugin_key);
            if (!$do_activate) {
                $error .= "<span class='error'>" . htmlescape(ob_get_contents()) . "</span>";
            }
            ob_end_clean();

            $function = 'plugin_' . $plugin_key . '_check_prerequisites';
            if ($do_activate && function_exists($function)) {
                ob_start();
                if (!$function()) {
                    $error .= '<span class="error">' . htmlescape(ob_get_contents()) . '</span>';
                }
                ob_end_clean();
            }
        }
        $can_run_local_install = ($has_local_install || $has_local_update) && !strlen($error);

        $buttons = "";

        if (strlen($error)) {
            $rand = mt_rand();
            $buttons .= "<i class='ti ti-alert-triangle plugin-error' id='plugin-error-$rand'></i>";
            Html::showToolTip($error, [
                'applyto' => "plugin-error-$rand",
            ]);
        }

        if ($must_be_cleaned) {
            $buttons .= "
                <button class='modify_plugin'
                        data-action='clean_plugin'
                        title='" . __s("Clean") . "'>
                        <i class='ti ti-recycle'></i>
                </button>";
            if ($can_be_downloaded) {
                $buttons .= "
                    <button class='modify_plugin'
                            data-action='download_plugin'
                            title='" . __s("Download again") . "'>
                        <i class='ti ti-cloud-download'></i>
                    </button>";
            }
        } elseif (!$is_available) {
            if (!$can_run_local_install) {
                $rand = mt_rand();
                $buttons .= "<i class='ti ti-alert-triangle plugin-unavailable' id='plugin-tooltip-$rand'></i>";
                Html::showToolTip(
                    __s('This plugin is not available for your GLPI version.'),
                    [
                        'applyto' => "plugin-tooltip-$rand",
                    ]
                );
            }
        } elseif (
            (!$exists && !$mk_controller->hasWriteAccess())
            || ($has_web_update && !$can_be_overwritten && GLPI_MARKETPLACE_MANUAL_DOWNLOADS)
        ) {
            $plugin_data = $mk_controller->getAPI()->getPlugin($plugin_key);
            if (array_key_exists('installation_url', $plugin_data) && $can_be_downloaded) {
                $warning = "";

                if (!Controller::hasVcsDirectory($plugin_key)) {
                    if ($has_web_update) {
                        $warning = __s("The plugin has an available update but its directory is not writable.") . "<br>";
                    }

                    $warning .= sprintf(
                        __s("Download archive manually, you must uncompress it in plugins directory (%s)"),
                        \htmlescape(GLPI_ROOT . '/plugins')
                    );

                    // Use "marketplace.download.php" proxy if archive is downloadable from GLPI marketplace plugins API
                    // as this API will refuse to serve the archive if registration key is not set in headers.
                    $download_url = parse_url($plugin_data['installation_url'], PHP_URL_HOST) === parse_url(GLPI_MARKETPLACE_PLUGINS_API_URI, PHP_URL_HOST)
                        ? $CFG_GLPI['root_doc'] . '/front/marketplace.download.php?key=' . $plugin_key
                        : $plugin_data['installation_url'];

                    $buttons .= "<a href='" . \htmlescape($download_url) . "' target='_blank'>
                            <button title='$warning' class='add_tooltip download_manually'><i class='ti ti-archive'></i></button>
                        </a>";
                } else {
                    $warning = __s("The plugin has an available update but its local directory contains source versioning.") . "<br>";
                    $warning .= __s("To avoid overwriting a potential branch under development, downloading is disabled.");

                    $buttons .= "<button title='$warning' class='add_tooltip download_manually'>
                        <i class='ti ti-ban'></i>
                    </button>";
                }
            }
        } elseif ($can_be_downloaded) {
            if (!$exists) {
                $buttons .= "<button class='modify_plugin'
                                     data-action='download_plugin'
                                     title='" . __s("Download") . "'>
                        <i class='ti ti-cloud-download'></i>
                    </button>";
            } elseif ($can_be_updated) {
                $update_title = sprintf(
                    __s("A new version (%s) is available, update?", 'marketplace'),
                    htmlescape($web_update_version)
                );

                $buttons .= TemplateRenderer::getInstance()->render('components/plugin_update_modal.html.twig', [
                    'plugin_name' => $plugin_inst->getField('name'),
                    'to_version' => $web_update_version,
                    'modal_id' => 'updateModal' . $plugin_inst->getField('directory'),
                    'open_btn' => '<button data-bs-toggle="modal"
                                           data-bs-target="#updateModal' . htmlescape($plugin_inst->getField('directory')) . '"
                                           title="' . $update_title . '">
                                       <i class="ti ti-cloud-download"></i>
                                   </button>',
                    'update_btn' => '<a href="#" class="btn btn-danger w-100 modify_plugin"
                                           data-action="update_plugin"
                                           data-bs-dismiss="modal">
                                           ' . _sx("button", "Update") . '
                                       </a>',
                ]);
            }
        }

        if (!static::$offline_mode && $mk_controller->requiresHigherOffer()) {
            $warning = sprintf(
                __s("You need a superior GLPI-Network offer to access to this plugin (%s)"),
                htmlescape(implode(', ', $required_offers))
            );

            $buttons .= "<a href='" . GLPI_NETWORK_SERVICES . "' target='_blank'>
                    <button class='add_tooltip need_offers' title='$warning'>
                        <i class='ti ti-alert-triangle'></i>
                    </button>
                </a>";
        }

        if ($can_run_local_install) {
            if ($has_local_update) {
                $buttons .= TemplateRenderer::getInstance()->render('components/plugin_update_modal.html.twig', [
                    'plugin_name' => $plugin_inst->getField('name'),
                    'to_version' => $plugin_inst->getField('version'),
                    'modal_id' => 'updateModal' . $plugin_inst->getField('directory'),
                    'open_btn' => '<button data-bs-toggle="modal"
                                           data-bs-target="#updateModal' . \htmlescape($plugin_inst->getField('directory')) . '"
                                           title="' . __s("Update") . '">
                                       <i class="ti ti-caret-up"></i>
                                   </button>',
                    'update_btn' => '<a href="#" class="btn btn-info w-100 modify_plugin"
                                           data-action="install_plugin"
                                           data-bs-dismiss="modal">
                                           ' . _sx("button", "Update") . '
                                       </a>',
                ]);
            } else {
                $buttons .= "<button class='modify_plugin'
                                     data-action='install_plugin'
                                     title='" . __s("Install") . "'>
                        <i class='ti ti-folder-plus'></i>
                    </button>";
            }
        }

        if ($is_installed) {
            if (!strlen($error)) {
                if ($is_actived) {
                    $buttons .= "<button class='modify_plugin'
                                         data-action='disable_plugin'
                                         title='" . __s("Disable") . "'>
                            <i class='ti ti-toggle-right-filled'></i>
                        </button>";
                } else {
                    $buttons .= "<button class='modify_plugin'
                                         data-action='enable_plugin'
                                         title='" . __s("Enable") . "'>
                            <i class='ti ti-toggle-left-filled'></i>
                        </button>";
                }
            }

            $buttons .= '
                <button data-bs-toggle="modal"
                        data-bs-target="#uninstallModal' . htmlescape($plugin_inst->getField('directory')) . '"
                        title="' . __s("Uninstall") . '">
                    <i class="ti ti-folder-x"></i>
                </button>
            ';

            $buttons .= TemplateRenderer::getInstance()->render('components/danger_modal.html.twig', [
                'modal_id' => 'uninstallModal' . $plugin_inst->getField('directory'),
                'confirm_btn' => '<a href="#" class="btn btn-danger w-100 modify_plugin"
                                       data-action="uninstall_plugin"
                                       data-bs-dismiss="modal">
                                       ' . _sx("button", "Uninstall") . '
                                   </a>',
                'content' => sprintf(
                    __('By uninstalling the "%s" plugin you will lose all the data of the plugin.'),
                    $plugin_inst->getField('name')
                ),
            ]);

            if (!strlen($error) && $is_actived && $config_page) {
                $config_url = "{$CFG_GLPI['root_doc']}/plugins/{$plugin_key}/{$config_page}";
                $buttons .= "<a href='" . htmlescape($config_url) . "'>
                        <button class='add_tooltip' title='" . __s("Configure") . "'>
                            <i class='ti ti-tool'></i>
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
    public static function getPluginIcon(array $plugin = [])
    {
        $icon = "";

        $logo_url = htmlescape($plugin['logo_url']);
        if (strlen($logo_url)) {
            $icon = "<img src='{$logo_url}'>";
        } else {
            $words = explode(" ", Toolbox::stripTags($plugin['name']));
            $initials = "";
            for ($i = 0; $i < 2; $i++) {
                if (isset($words[$i])) {
                    $initials .= mb_substr($words[$i], 0, 1);
                }
            }
            $bg_color = htmlescape(Toolbox::getColorForString($initials));
            $fg_color = htmlescape(Toolbox::getFgColor($bg_color));
            $icon = "<span style='background-color: $bg_color; color: $fg_color'
                           class='icon-text'>" . htmlescape($initials) . "</span>";
        }

        return $icon;
    }


    /**
     * Return HTML part for Glpi Network information for a given plugin
     * @param array $plugin data of the plugin.
     *                      if check agains plugin key if we need some subscription to use it
     * @return string the subscription information html
     */
    public static function getNetworkInformations(array $plugin = []): string
    {
        $mk_controller  = new Controller($plugin['key']);
        $require_offers = $mk_controller->getRequiredOffers();

        $html = "";
        if (count($require_offers)) {
            $fst_offer  = array_splice($require_offers, 0, 1);
            $offerkey   = htmlescape(key($fst_offer));
            $offerlabel = htmlescape(current($fst_offer));

            $html = "<div class='offers'>
                    <a href='" . htmlescape(GLPI_NETWORK_SERVICES) . "' target='_blank'
                       class='badge glpi-network'
                       title='" . sprintf(__s("You must have a %s subscription to get this plugin"), 'GLPI Network') . "'>
                        <i class='ti ti-star-filled'></i>GLPI Network
                    </a>
                    <a href='" . htmlescape(GLPI_NETWORK_SERVICES) . "' target='_blank'
                       class='badge bg-azure $offerkey'
                       title='" . sprintf(__s("You need at least the %s subscription level to get this plugin"), $offerlabel) . "'>
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
    public static function getLocalizedDescription(array $plugin = [], string $version = 'short_description'): string
    {
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

        if ((string) $description === '') {
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
    public static function getPaginationHtml(int $current_page = 1, int $total = 1, bool $only_li = false): string
    {
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
            $html .= "<ul class='pagination'>";
        }
        $html .= "<li data-page='$prev' $p_cls><i class='ti ti-chevron-left'></i></li>";
        $dots = false;
        for ($i = 1; $i <= $nb_pages; $i++) {
            if (
                $i >= 3
                && ($i < $current_page - 1
                 || $i > $current_page + 1)
                && $i < $nb_pages - 1
            ) {
                if (!$dots) {
                    $html .= "<li class='nav-disabled dots'>...</li>";
                }
                $dots = true;
                continue;
            }
            $dots = false;

            $current = ($current_page === $i)
                ? "class='current'"
                : "";
            $html .= "<li data-page='$i' $current>$i</li>";
        }
        $html .= "<li data-page='$next' $n_cls><i class='ti ti-chevron-right'></i></li>";
        $html .= "<li class='nb_plugin'>" . sprintf(_sn("%s plugin", "%s plugins", $total), $total) . "</li>";
        if (!$only_li) {
            $html .= "</ul>";
        }

        return $html;
    }


    /**
     * Display a dialog inviting the user to switch from former plugin list to marketplace new view.
     *
     * @return void display things
     */
    public static function showFeatureSwitchDialog()
    {
        global $CFG_GLPI;

        if (isset($_POST['marketplace_replace'])) {
            $mp_value = isset($_POST['marketplace_replace_plugins_yes'])
            ? Controller::MP_REPLACE_YES
            : (isset($_POST['marketplace_replace_plugins_never'])
               ? Controller::MP_REPLACE_NEVER
               : Controller::MP_REPLACE_ASK);
            Config::setConfigurationValues('core', [
                'marketplace_replace_plugins' => $mp_value,
            ]);

            // is user agree, redirect him to marketplace
            if ($mp_value === Controller::MP_REPLACE_YES) {
                Html::redirect($CFG_GLPI["root_doc"] . "/front/marketplace.php");
            }

            // avoid annoying user for the current session
            $_SESSION['skip_marketplace_invitation'] = true;
        }

        // show modal for asking user preference
        if (
            Controller::getPluginPageConfig() == Controller::MP_REPLACE_ASK
            && !isset($_SESSION['skip_marketplace_invitation'])
            && GLPI_INSTALL_MODE !== 'CLOUD'
        ) {
            echo "<div class='card mb-4'>";
            echo "<div class='card-header card-title'>" . __s("Switch to marketplace") . "</div>";
            echo "<div class='card-body'>";
            echo "<form id='marketplace_dialog' method='POST'>";
            echo Html::image($CFG_GLPI['root_doc'] . "/pics/screenshots/marketplace.png", [
                'style' => 'width: 600px',
            ]);
            echo "<br><br>";
            echo __s("GLPI provides a new marketplace to download and install plugins.");
            echo "<br><br>";
            echo "<b>" . __s("Do you want to replace the plugins setup page by the new marketplace?") . "</b>";
            echo "</div>";
            echo "<div class='card-footer'>";
            echo Html::submit(__('Yes'), [
                'name'  => 'marketplace_replace_plugins_yes',
                'icon'  => 'ti ti-check',
                'class' => 'btn btn-primary',
            ]);
            echo "&nbsp;";
            echo Html::submit(__('No'), [
                'name' => 'marketplace_replace_plugins_never',
                'icon' => 'ti ti-x',
            ]);
            echo "&nbsp;";
            echo Html::submit(__('Later'), [
                'name'  => 'marketplace_replace_plugins_later',
                'icon' => 'ti ti-clock',
            ]);
            echo "</div>";
            echo Html::hidden('marketplace_replace');

            Html::closeForm();
            echo "</div>";
        }
    }
}
