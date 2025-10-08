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
use Glpi\Api\HL\Router;
use Glpi\Application\Environment;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Cache\CacheManager;
use Glpi\Dashboard\Grid;
use Glpi\Event;
use Glpi\Helpdesk\HelpdeskTranslation;
use Glpi\Plugin\Hooks;
use Glpi\System\Diagnostic\SourceCodeIntegrityChecker;
use Glpi\System\RequirementsManager;
use Glpi\Toolbox\ArrayNormalizer;
use Glpi\UI\ThemeManager;
use Symfony\Component\HttpFoundation\Request;

use function Safe\chdir;
use function Safe\exec;
use function Safe\getcwd;
use function Safe\glob;
use function Safe\json_decode;
use function Safe\json_encode;
use function Safe\opcache_get_status;
use function Safe\parse_url;
use function Safe\preg_match;
use function Safe\preg_replace;

/**
 *  Config class
 **/
class Config extends CommonDBTM
{
    public const DELETE_ALL = -1;
    public const KEEP_ALL = 0;

    public const UNIT_MANAGEMENT = 0;
    public const GLOBAL_MANAGEMENT = 1;
    public const NO_MANAGEMENT = 2;

    public const TIMELINE_ACTION_BTN_MERGED = 0;
    public const TIMELINE_ACTION_BTN_SPLITTED = 1;

    public const TIMELINE_RELATIVE_DATE = 0;
    public const TIMELINE_ABSOLUTE_DATE = 1;

    // From CommonGLPI
    protected $displaylist         = false;

    // From CommonDBTM
    public $auto_message_on_action = false;
    public $showdebug              = true;

    public static $rightname              = 'config';

    public static $undisclosedFields      = [
        'proxy_passwd',
        'smtp_passwd',
        'smtp_oauth_client_id',
        'smtp_oauth_client_secret',
        'smtp_oauth_options',
        'smtp_oauth_refresh_token',
        'glpinetwork_registration_key',
        'ldap_pass', // this one should not exist anymore, but may be present when admin restored config dump after migration
    ];
    public static $saferUndisclosedFields = ['admin_email', 'replyto_email'];

    /**
     * Indicates whether the GLPI configuration has been loaded.
     * @var boolean
     */
    private static $loaded = false;

    public static function getTypeName($nb = 0)
    {
        return __('Setup');
    }


    public static function getMenuContent()
    {
        $menu = [];
        if (static::canView()) {
            $menu['title']   = _x('setup', 'General');
            $menu['page']    = Config::getFormURL(false);
            $menu['icon']    = Config::getIcon();

            $menu['options'][APIClient::class]['icon']            = APIClient::getIcon();
            $menu['options'][APIClient::class]['title']           = APIClient::getTypeName(Session::getPluralNumber());
            $menu['options'][APIClient::class]['page']            = Config::getFormURL(false) . '?forcetab=Config$8';
            $menu['options'][APIClient::class]['links']['search'] = Config::getFormURL(false) . '?forcetab=Config$8';
            $menu['options'][APIClient::class]['links']['add']    = '/front/apiclient.form.php';
        }
        if (count($menu)) {
            return $menu;
        }
        return false;
    }


    public static function canCreate(): bool
    {
        return false;
    }


    public function canViewItem(): bool
    {
        if (
            isset($this->fields['context'])
            && (
                in_array($this->fields['context'], ['core', 'inventory'], true) // GLPI config contexts
                || Plugin::isPluginActive($this->fields['context'])
            )
        ) {
            return true;
        }
        return false;
    }


    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addStandardTab(self::class, $ong, $options);
        $this->addStandardTab(DisplayPreference::class, $ong, $options);
        $this->addStandardTab(GLPINetwork::class, $ong, $options);
        $this->addStandardTab(HelpdeskTranslation::class, $ong, $options);
        $this->addStandardTab(Log::class, $ong, $options);

        return $ong;
    }

    public function prepareInputForUpdate($input)
    {
        global $CFG_GLPI;

        // Unset _no_history to not save it as a configuration value
        unset($input['_no_history']);

        // Update only an item
        if (isset($input['context'])) {
            return $input;
        }

        // Process configuration for plugins
        if (!empty($input['config_context'])) {
            $config_context = $input['config_context'];
            unset($input['id']);
            unset($input['_glpi_csrf_token']);
            unset($input['_update']);
            unset($input['config_context']);
            if (
                (!empty($input['config_class']))
                && (class_exists($input['config_class']))
                && (method_exists($input['config_class'], 'configUpdate'))
            ) {
                $config_method = $input['config_class'] . '::configUpdate';
                unset($input['config_class']);
                $input = call_user_func($config_method, $input);
            }
            static::setConfigurationValues($config_context, $input);
            return false;
        }

        // Trim automatically ending slash for url_base config as, for all existing occurrences,
        // this URL will be prepended to something that starts with a slash.
        if (isset($input["url_base"]) && !empty($input["url_base"])) {
            if (Toolbox::isValidWebUrl($input["url_base"])) {
                $input["url_base"] = rtrim($input["url_base"], '/');
            } else {
                Session::addMessageAfterRedirect(__s('Invalid base URL!'), false, ERROR);
                return false;
            }
        }

        $input = $this->handleSmtpInput($input);

        if (isset($input["proxy_passwd"]) && empty($input["proxy_passwd"])) {
            unset($input["proxy_passwd"]);
        }
        if (isset($input["_blank_proxy_passwd"]) && $input["_blank_proxy_passwd"]) {
            $input['proxy_passwd'] = '';
        }

        // Manage DB Slave process
        if (isset($input['_dbslave_status'])) {
            $already_active = DBConnection::isDBSlaveActive();

            if ($input['_dbslave_status']) {
                DBConnection::changeCronTaskStatus(true);

                if (!$already_active) {
                    // Activate Slave from the "system" tab
                    DBConnection::createDBSlaveConfig();
                } elseif (isset($input["_dbreplicate_dbhost"])) {
                    // Change parameter from the "replicate" tab
                    DBConnection::saveDBSlaveConf(
                        $input["_dbreplicate_dbhost"],
                        $input["_dbreplicate_dbuser"],
                        $input["_dbreplicate_dbpassword"],
                        $input["_dbreplicate_dbdefault"]
                    );
                }
            }

            if (!$input['_dbslave_status'] && $already_active) {
                DBConnection::deleteDBSlaveConfig();
                DBConnection::changeCronTaskStatus(false);
            }
        }

        // Matrix for Impact / Urgence / Priority
        if (isset($input['_matrix'])) {
            $tab = [];

            for ($urgency = 1; $urgency <= 5; $urgency++) {
                for ($impact = 1; $impact <= 5; $impact++) {
                    $priority               = $input["_matrix_{$urgency}_{$impact}"];
                    $tab[$urgency][$impact] = $priority;
                }
            }

            $input['priority_matrix'] = exportArrayToDB($tab);
            $input['urgency_mask']    = 0;
            $input['impact_mask']     = 0;

            for ($i = 1; $i <= 5; $i++) {
                if ($input["_urgency_{$i}"]) {
                    $input['urgency_mask'] += (1 << $i);
                }

                if ($input["_impact_{$i}"]) {
                    $input['impact_mask'] += (1 << $i);
                }
            }
        }

        if (isset($input['devices_in_menu'])) {
            $input['devices_in_menu'] = exportArrayToDB(
                ArrayNormalizer::normalizeValues($input['devices_in_menu'] ?: [], 'strval')
            );
        }

        // lock mechanism update
        if (isset($input['lock_use_lock_item']) && isset($input['lock_item_list'])) {
            $input['lock_item_list'] = exportArrayToDB(
                ArrayNormalizer::normalizeValues($input['lock_item_list'] ?: [], 'strval')
            );
        }

        if (isset($input[Impact::CONF_ENABLED])) {
            $input[Impact::CONF_ENABLED] = exportArrayToDB(
                ArrayNormalizer::normalizeValues($input[Impact::CONF_ENABLED] ?: [], 'strval')
            );
        }

        if (isset($input['planning_work_days'])) {
            $input['planning_work_days'] = exportArrayToDB(
                ArrayNormalizer::normalizeValues($input['planning_work_days'] ?: [], 'intval')
            );
        }

        // Beware : with new management system, we must update each value
        unset($input['id']);
        unset($input['_glpi_csrf_token']);
        unset($input['_update']);

        // Add skipMaintenance if maintenance mode update
        if (isset($input['maintenance_mode']) && $input['maintenance_mode']) {
            $_SESSION['glpiskipMaintenance'] = 1;
            $url = htmlescape($CFG_GLPI['root_doc'] . "/index.php?skipMaintenance=1");
            Session::addMessageAfterRedirect(
                sprintf(
                    __s('Maintenance mode activated. Backdoor using: %s'),
                    "<a href='$url'>$url</a>"
                ),
                false,
                WARNING
            );
        }

        // Automatically trim whitespaces around registration key.
        if (array_key_exists('glpinetwork_registration_key', $input) && !empty($input['glpinetwork_registration_key'])) {
            $input['glpinetwork_registration_key'] = trim($input['glpinetwork_registration_key']);
        }

        // Prevent invalid profile to be set as the lock profile.
        // User updating the config from GLPI's UI should not be able to send
        // invalid values but API or manual HTTP requests might be invalid.
        if (isset($input['lock_lockprofile_id'])) {
            $profile = Profile::getById($input['lock_lockprofile_id']);
            if (!$profile || $profile->fields['interface'] !== 'central') {
                // Invalid profile
                Session::addMessageAfterRedirect(
                    __s("The specified profile doesn't exist or is not allowed to access the central interface."),
                    false,
                    ERROR
                );
                unset($input['lock_lockprofile_id']);
            }
        }

        // Check the validity of `pdffont`
        if (isset($input['pdffont']) && !in_array($input['pdffont'], array_keys(GLPIPDF::getFontList()), true)) {
            Session::addMessageAfterRedirect(
                sprintf(
                    __s('The following field has an incorrect value: "%s".'),
                    __s('PDF export font')
                ),
                false,
                ERROR
            );
            unset($input['pdffont']);
        }

        $tfa_enforced_changed = isset($input['2fa_enforced']) && $input['2fa_enforced'] !== $CFG_GLPI['2fa_enforced'];
        $tfa_grace_days_changed = isset($input['2fa_grace_days']) && $input['2fa_grace_days'] !== $CFG_GLPI['2fa_grace_days'];
        if ($tfa_grace_days_changed || $tfa_enforced_changed) {
            $enforced = $input['2fa_enforced'] ?? $CFG_GLPI['2fa_enforced'];
            $grace_period = $input['2fa_grace_days'] ?? $CFG_GLPI['2fa_grace_days'];
            if ($enforced && $grace_period > 0) {
                $input['2fa_grace_date_start'] = $_SESSION['glpi_currenttime'];
            } else {
                $input['2fa_grace_date_start'] = null;
            }
        }

        // Prevent some input values to be saved in DB
        $values_to_filter = [
            '_dbslave_status',
            '_dbreplicate_dbhost',
            '_dbreplicate_dbuser',
            '_dbreplicate_dbpassword',
            '_dbreplicate_dbdefault',
        ];

        $input = array_filter($input, fn($key) => !in_array($key, $values_to_filter), ARRAY_FILTER_USE_KEY);

        static::setConfigurationValues('core', $input);

        return false;
    }

    /**
     * Handle SMTP input values.
     *
     * @param array $input
     *
     * @return array
     */
    private function handleSmtpInput(array $input): array
    {
        global $CFG_GLPI;

        if (array_key_exists('smtp_mode', $input) && $input['smtp_mode'] === MAIL_SMTPSSL) {
            $input['smtp_mode'] = MAIL_SMTP;
            Toolbox::deprecated('Usage of "MAIL_SMTPSSL" SMTP mode is deprecated. Switch to "MAIL_SMTP" mode.');
        }

        if (array_key_exists('smtp_mode', $input) && (int) $input['smtp_mode'] === MAIL_SMTPOAUTH) {
            $input['smtp_check_certificate'] = 1;
            $input['smtp_passwd']          = '';

            if (array_key_exists('smtp_oauth_client_secret', $input) && $input['smtp_oauth_client_secret'] === '') {
                // form does not contains existing password value for security reasons
                // prevent password to be overriden by an empty value
                unset($input['smtp_oauth_client_secret']);
            }

            if (array_key_exists('smtp_oauth_options', $input)) {
                if (is_array($input['smtp_oauth_options'])) {
                    $input['smtp_oauth_options'] = json_encode($input['smtp_oauth_options']);
                } else {
                    $input['smtp_oauth_options'] = '';
                }
            }

            $has_oauth_settings_changed = (array_key_exists('smtp_oauth_provider', $input) && $input['smtp_oauth_provider'] !== $CFG_GLPI['smtp_oauth_provider'])
                || (array_key_exists('smtp_oauth_client_id', $input) && $input['smtp_oauth_client_id'] !== $CFG_GLPI['smtp_oauth_client_id'])
                || (array_key_exists('smtp_oauth_options', $input) && $input['smtp_oauth_options'] !== $CFG_GLPI['smtp_oauth_options']);

            if ($has_oauth_settings_changed) {
                // clean credentials, they will have to be replaced by new ones
                $input['smtp_oauth_refresh_token'] = '';
                $input['smtp_username']            = '';
            }

            // remember whether the SMTP Oauth flow has to be triggered
            $_SESSION['redirect_to_smtp_oauth'] = (bool) ($input['_force_redirect_to_smtp_oauth'] ?? false) === true
                || $has_oauth_settings_changed
                || (string) $CFG_GLPI['smtp_oauth_refresh_token'] === '';

            // ensure value is not saved in DB
            unset($input['_force_redirect_to_smtp_oauth']);
        } elseif (array_key_exists('smtp_mode', $input) && (int) $input['smtp_mode'] !== MAIL_SMTPOAUTH) {
            // clean oauth related information
            $input['smtp_oauth_provider'] = '';
            $input['smtp_oauth_client_id'] = '';
            $input['smtp_oauth_client_secret'] = '';
            $input['smtp_oauth_options'] = '{}';
            $input['smtp_oauth_refresh_token'] = '';
        }

        if (isset($input['smtp_passwd']) && empty($input['smtp_passwd'])) {
            unset($input['smtp_passwd']);
        }
        if (isset($input["_blank_smtp_passwd"]) && $input["_blank_smtp_passwd"]) {
            $input['smtp_passwd'] = '';
        }

        return $input;
    }

    public static function unsetUndisclosedFields(&$fields)
    {
        if (isset($fields['context']) && isset($fields['name'])) {
            if (
                $fields['context'] == 'core'
                && in_array($fields['name'], self::$undisclosedFields)
            ) {
                unset($fields['value']);
            } else {
                $fields = Plugin::doHookFunction(Hooks::UNDISCLOSED_CONFIG_VALUE, $fields);
            }
        }
    }

    /**
     * Print the config form for display
     *
     * @return void
     **/
    public function showFormDisplay()
    {
        global $CFG_GLPI;

        if (!self::canView()) {
            return;
        }

        TemplateRenderer::getInstance()->display('pages/setup/general/general_setup.html.twig', [
            'canedit' => Session::haveRight(self::$rightname, UPDATE),
            'config'  => $CFG_GLPI,
        ]);
    }


    /**
     * Print the config form for restrictions
     *
     * @return void
     **/
    public function showFormInventory()
    {
        global $CFG_GLPI;

        if (!self::canView()) {
            return;
        }

        $canedit = static::canUpdate();
        $item_devices_types = [];
        foreach (Item_Devices::getDeviceTypes() as $itemtype) {
            $item_devices_types[$itemtype] = $itemtype::getTypeName();
        }

        TemplateRenderer::getInstance()->display('pages/setup/general/assets_setup.html.twig', [
            'config' => $CFG_GLPI,
            'item_devices_types' => $item_devices_types,
            'canedit' => $canedit,
        ]);
    }


    /**
     * Print the config form for restrictions
     *
     * @return void
     **/
    public function showFormAuthentication()
    {
        if (!Config::canUpdate()) {
            return;
        }

        $twig = TemplateRenderer::getInstance();
        $twig->display('pages/setup/authentication/setup.html.twig', [
            'token'                                    => Session::getNewCSRFToken(),
            'user_restored_ldap_choices'               => AuthLDAP::getLdapRestoredUserActionOptions(),
            'gmt_values'                               => Dropdown::getGMTValues(),
            'user_deleted_ldap_user_choices'           => AuthLDAP::getLdapDeletedUserActionOptions_User(),
            'user_deleted_ldap_groups_choices'         => AuthLDAP::getLdapDeletedUserActionOptions_Groups(),
            'user_deleted_ldap_authorizations_choices' => AuthLDAP::getLdapDeletedUserActionOptions_Authorizations(),
        ]);
    }


    /**
     * Print the config form for slave DB
     *
     * @return void
     **/
    public function showFormDBSlave()
    {
        global $CFG_GLPI, $DB;

        if (!static::canUpdate()) {
            return;
        }

        $DBslave = DBConnection::getDBSlaveConf();
        $replica_config = [
            'host' => is_array($DBslave->dbhost) ? implode(' ', $DBslave->dbhost) : $DBslave->dbhost,
            'default' => $DBslave->dbdefault,
            'user' => $DBslave->dbuser,
            'password' => rawurldecode($DBslave->dbpassword),
        ];

        $hosts = is_array($DBslave->dbhost) ? $DBslave->dbhost : [$DBslave->dbhost];
        $replication_delay = [];
        foreach (array_keys($hosts) as $host_num) {
            $replication_delay[$host_num] = DBConnection::getReplicateDelay($host_num);
        }

        $replication_status = DBConnection::getReplicationStatus();

        TemplateRenderer::getInstance()->display('pages/setup/general/dbreplica_setup.html.twig', [
            'config'             => $CFG_GLPI,
            'canedit'            => static::canUpdate(),
            'source_dbhost'      => $DB->dbhost,
            'replica_config'     => $replica_config,
            'replication_status' => $replication_status,
            'replication_delay'  => $replication_delay,
        ]);
    }

    /**
     * Print the config form for External API
     *
     * @since 9.1
     * @return void
     **/
    public function showFormAPI()
    {
        global $CFG_GLPI;

        if (!self::canView()) {
            return;
        }

        // Options just for new API
        $api_versions = Router::getAPIVersions();
        $legacy_version = array_filter($api_versions, static fn($version) => $version['api_version'] === '1');
        $legacy_version = reset($legacy_version);
        $current_version = array_filter($api_versions, static fn($version) => $version['version'] === Router::API_VERSION);
        $current_version = reset($current_version);
        $getting_started_doc = $current_version['endpoint'] . '/getting-started';
        $endpoint_doc = $current_version['endpoint'] . '/doc';

        TemplateRenderer::getInstance()->display('pages/setup/general/api_setup.html.twig', [
            'config_object' => new Config(),
            'config' => $CFG_GLPI,
            'canedit' => static::canUpdate(),
            'getting_started_doc_url' => $getting_started_doc,
            'endpoint_doc_url' => $endpoint_doc,
            'api_url' => $current_version['endpoint'],
            'legacy_doc_url' => $legacy_version['endpoint'],
            'legacy_api_url' => $legacy_version['endpoint'],
        ]);
        if ($CFG_GLPI['enable_api']) {
            TemplateRenderer::getInstance()->display('pages/setup/general/api_apiclients_section.html.twig');
        }
    }


    /**
     * Print the config form for connections
     *
     * @return void
     **/
    public function showFormHelpdesk()
    {
        global $CFG_GLPI;

        if (!self::canView()) {
            return;
        }
        $isimpact = [];
        for ($impact = 5; $impact >= 1; $impact--) {
            if ($impact === 3) {
                $isimpact[3] = 1;
            } else {
                $isimpact[$impact] = (($CFG_GLPI['impact_mask'] & (1 << $impact)) > 0);
            }
        }

        $isurgency = [];
        for ($urgency = 5; $urgency >= 1; $urgency--) {
            if ($urgency === 3) {
                $isurgency[3] = 1;
            } else {
                $isurgency[$urgency] = (($CFG_GLPI['urgency_mask'] & (1 << $urgency)) > 0);
            }
        }

        TemplateRenderer::getInstance()->display('pages/setup/general/assistance_setup.html.twig', [
            'config' => $CFG_GLPI,
            'is_impact' => $isimpact,
            'is_urgency' => $isurgency,
            'canedit' => static::canUpdate(),
        ]);
    }


    /**
     * Print the config form for default user prefs
     *
     * @param $data array containing datas
     * (CFG_GLPI for global config / glpi_users fields for user prefs)
     *
     * @return void
     **/
    public function showFormUserPrefs($data = [])
    {
        global $CFG_GLPI, $DB;

        $userpref  = false;
        $url       = Toolbox::getItemTypeFormURL(self::class);

        $canedit = static::canUpdate();
        $canedituser = Session::haveRight('personalization', UPDATE);
        if (array_key_exists('last_login', $data)) {
            $userpref = true;
            if ($data["id"] === Session::getLoginUserID()) {
                $url  = $CFG_GLPI['root_doc'] . "/front/preference.php";
            } else {
                $url  = User::getFormURL();
            }
        }

        $central_tabs = [
            1 => __('Personal View'),
            2 => __('Group View'),
            3 => __('Global View'),
            4 => _n('RSS feed', 'RSS feeds', Session::getPluralNumber()),
        ];
        $grid = new Grid('central');
        if ($grid::canViewOneDashboard()) {
            array_unshift($central_tabs, __('Dashboard'));
        }

        $palettes = $this->getPalettes(true);
        TemplateRenderer::getInstance()->display('pages/setup/general/preferences_setup.html.twig', [
            'is_user' => $userpref,
            'canedit' => (!$userpref && $canedit) || ($userpref && $canedituser),
            'form_path' => $url,
            'can_edit_config' => $canedit,
            'config' => $data,
            'palettes' => array_combine(array_keys($palettes), array_column($palettes, 'name')),
            'palettes_isdark' => array_combine(array_keys($palettes), array_column($palettes, 'dark')),
            'use_timezones' => $DB->use_timezones,
            'timezones' => $DB->use_timezones ? $DB->getTimezones() : [],
            'central_tabs' => $central_tabs,
        ]);
    }

    /**
     * Check if the "use_password_security" parameter is enabled
     *
     * @return bool
     */
    public static function arePasswordSecurityChecksEnabled(): bool
    {
        global $CFG_GLPI;

        return $CFG_GLPI["use_password_security"];
    }

    /**
     * Display security checks on password
     *
     * @param $field string id of the field containing password to check (default 'password')
     *
     * @since 0.84
     **/
    public static function displayPasswordSecurityChecks($field = 'password')
    {
        TemplateRenderer::getInstance()->display('components/user/password_security_checks.html.twig', [
            'field' => $field,
        ]);
    }


    /**
     * Display a report about system performance
     * - opcode cache (opcache)
     * - core cache
     * - translations cache
     *
     * @since 9.1
     **/
    public function showPerformanceInformations()
    {
        if (!Config::canUpdate()) {
            return false;
        }

        $opcache_info = false;
        $opcache_ext = 'Zend OPcache';
        $opcache_enabled = extension_loaded($opcache_ext) && ($opcache_info = opcache_get_status(false));
        $opcache_version = $opcache_enabled ? phpversion($opcache_ext) : '';

        $cache_manager = new CacheManager();
        $user_cache_ext = strtolower(get_class($cache_manager->getCacheStorageAdapter(CacheManager::CONTEXT_CORE)));
        $user_cache_ext = preg_replace('/^.*\\\([a-z]+?)(?:adapter)?$/', '$1', $user_cache_ext);
        $user_cache_version = phpversion($user_cache_ext);

        $trans_cache_adapter = strtolower(get_class($cache_manager->getCacheStorageAdapter(CacheManager::CONTEXT_TRANSLATIONS)));
        $trans_cache_adapter = preg_replace('/^.*\\\([a-z]+?)(?:adapter)?$/', '$1', $trans_cache_adapter);

        TemplateRenderer::getInstance()->display('pages/setup/general/performance.html.twig', [
            'opcache_ext' => $opcache_ext,
            'opcache_enabled' => $opcache_enabled,
            'opcache_version' => $opcache_version,
            'opcache_info' => $opcache_info,
            'user_cache_ext' => $user_cache_ext,
            'user_cache_version' => $user_cache_version,
            'trans_cache_adapter' => $trans_cache_adapter,
        ]);
    }

    public static function showSystemInfoTable()
    {
        global $CFG_GLPI, $DB;

        $oldlang = $_SESSION['glpilanguage'];
        // Keep this, for some function call which still use translation (ex showAllReplicateDelay)
        Session::loadLanguage('en_GB');

        // No need to translate, this part always display in english (for copy/paste to forum)

        // Try to compute a better version for .git
        $ver = GLPI_VERSION;
        if (is_dir(GLPI_ROOT . "/.git")) {
            $dir = getcwd();
            chdir(GLPI_ROOT);
            $returnCode = 1;
            $output = [];
            $gitrev = @exec('git show --format="%h" --no-patch 2>&1', $output, $returnCode);
            $gitbranch = '';
            if (!$returnCode) {
                $gitbranch = @exec('git symbolic-ref --quiet --short HEAD || git rev-parse --short HEAD 2>&1', $output, $returnCode);
            }
            chdir($dir);
            if (!$returnCode) {
                $ver .= '-git-' . $gitbranch . '-' . $gitrev;
            }
        }

        $core_requirements = (new RequirementsManager())->getCoreRequirementList($DB);
        $requirements = [];
        /* @var \Glpi\System\Requirement\RequirementInterface $requirement */
        foreach ($core_requirements as $k => $requirement) {
            if ($requirement->isOutOfContext()) {
                continue; // skip requirement if not relevant
            }

            $status = $requirement->isValidated()
            ? 'ok'
            : ($requirement->isOptional() ? 'warning' : 'ko');
            $requirements[$k] = [
                'status' => $status,
                'messages' => $requirement->getValidationMessages(),
            ];
        }

        $system_info_objs = [];
        foreach ($CFG_GLPI["systeminformations_types"] as $type) {
            $system_info_objs[] = getItemForItemtype($type);
        }

        Session::loadLanguage($oldlang);

        $files = array_merge(
            glob(GLPI_LOCAL_I18N_DIR . "/**/*.php"),
            glob(GLPI_LOCAL_I18N_DIR . "/**/*.mo")
        );
        sort($files);

        // Compute code integrity summary
        $code_integrity = null;
        if (Environment::get()->shouldExpectResourcesToChange() === false) {
            try {
                $code_integrity = (new SourceCodeIntegrityChecker())->getSummary();
            } catch (Throwable $e) {
                global $PHPLOGGER;
                $PHPLOGGER->error(
                    'Unable to get code integrity check summary.',
                    ['exception' => $e]
                );
            }
        }

        TemplateRenderer::getInstance()->display('pages/setup/general/systeminfo_table.html.twig', [
            'ver' => $ver,
            'language' => $oldlang,
            '_server' => $_SERVER,
            'db_info' => $DB->getInfo(),
            'core_requirements' => $requirements,
            'system_info_objs' => $system_info_objs,
            'locales_overrides' => $files,
            'code_integrity' => $code_integrity,
        ]);
    }

    /**
     * Display a HTML report about systeme information / configuration
     **/
    public function showSystemInformations()
    {
        global $CFG_GLPI;

        if (!static::canUpdate()) {
            return false;
        }

        TemplateRenderer::getInstance()->display('pages/setup/general/systeminfo_form.html.twig', [
            'config' => $CFG_GLPI,
            'canedit' => static::canUpdate(),
        ]);
        self::showSystemInfoTable();
    }


    /**
     * Dropdown for global management config
     *
     * @param string       $name   select name
     * @param string       $value  default value
     * @param integer|null $rand   rand
     **/
    public static function dropdownGlobalManagement($name, $value, $rand = null)
    {
        $choices = [
            self::UNIT_MANAGEMENT => __('Yes - Restrict to unit management'),
            self::GLOBAL_MANAGEMENT => __('Yes - Restrict to global management'),
            self::NO_MANAGEMENT => __('No'),
        ];
        Dropdown::showFromArray($name, $choices, ['value' => $value, 'rand' => $rand]);
    }


    /**
     * Get language in GLPI associated with the value coming from LDAP/SSO
     * Value can be, for example : English, en_EN, en-EN or en
     *
     * @param string $lang the value coming from LDAP/SSO
     *
     * @return string locale's php page in GLPI or '' is no language associated with the value
     **/
    public static function getLanguage($lang)
    {
        global $CFG_GLPI;

        // Alternative language code: en-EN --> en_EN
        $altLang = str_replace("-", "_", $lang);

        // Search in order : ID or extjs dico or tinymce dico / native lang / english name
        //                   / extjs dico / tinymce dico
        // ID  or extjs dico or tinymce dico
        foreach ($CFG_GLPI["languages"] as $ID => $language) {
            if (
                (strcasecmp($lang, $ID) == 0)
                || (strcasecmp($altLang, $ID) == 0)
                || (strcasecmp($lang, $language[2]) == 0)
                || (strcasecmp($lang, $language[3]) == 0)
            ) {
                return $ID;
            }
        }

        // native lang
        foreach ($CFG_GLPI["languages"] as $ID => $language) {
            if (strcasecmp($lang, $language[0]) == 0) {
                return $ID;
            }
        }

        // english lang name
        foreach ($CFG_GLPI["languages"] as $ID => $language) {
            if (strcasecmp($lang, $language[4]) == 0) {
                return $ID;
            }
        }

        return "";
    }

    /**
     * Display field unicity criterias form
     **/
    public function showFormFieldUnicity()
    {
        Toolbox::deprecated(
            message: "This method will be removed in the next version",
            version: "11.1.0"
        );
        $unicity = new FieldUnicity();
        $unicity->showForm(1);
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        switch (get_class($item)) {
            case Preference::class:
                return self::createTabEntry(text: __('Personalization'), icon: 'ti ti-adjustments');

            case User::class:
                if (
                    User::canUpdate()
                    && $item->currentUserHaveMoreRightThan($item->getID())
                ) {
                    return self::createTabEntry(__('Settings'));
                }
                break;

            case self::class:
                $tabs = [
                    1 => self::createTabEntry(__('General setup')),  // Display
                    2 => self::createTabEntry(__('Default values')), // Prefs
                    3 => self::createTabEntry(_n('Asset', 'Assets', Session::getPluralNumber()), 0, $item::getType(), 'ti ti-package'),
                    4 => self::createTabEntry(__('Assistance'), 0, $item::getType(), 'ti ti-headset'),
                    12 => self::createTabEntry(__('Management'), 0, $item::getType(), 'ti ti-wallet'),
                ];
                if (Config::canUpdate()) {
                    $tabs[9]  = self::createTabEntry(__('Logs purge'), 0, $item::getType(), Event::getIcon());
                    $tabs[5]  = self::createTabEntry(__('System'));
                    $tabs[10] = self::createTabEntry(__('Security'), 0, $item::getType(), 'ti ti-shield-lock');
                    $tabs[7]  = self::createTabEntry(__('Performance'), 0, $item::getType(), 'ti ti-dashboard');
                    $tabs[8]  = self::createTabEntry(__('API'), 0, $item::getType(), 'ti ti-api-app');
                    $tabs[11] = self::createTabEntry(Impact::getTypeName(), 0, $item::getType(), Impact::getIcon());
                }

                if (
                    DBConnection::isDBSlaveActive()
                    && Config::canUpdate()
                ) {
                    $tabs[6]  = self::createTabEntry(_n('SQL replica', 'SQL replicas', Session::getPluralNumber()), 0, $item::getType(), 'ti ti-database');  // Slave
                }
                return $tabs;

            case 'GLPINetwork':
                return self::createTabEntry(GLPINetwork::getTypeName(), 0, $item::getType(), GLPINetwork::getIcon());

            case Impact::getType():
                return self::createTabEntry(Impact::getTypeName());
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        global $CFG_GLPI;

        if ($item instanceof Preference) {
            $config = new self();
            $user   = new User();
            if ($user->getFromDB(Session::getLoginUserID())) {
                $user->computePreferences();
                $config->showFormUserPrefs($user->fields);
            }
        } elseif ($item instanceof User) {
            $config = new self();
            $item->computePreferences();
            $config->showFormUserPrefs($item->fields);
        } elseif ($item instanceof self) {
            switch ($tabnum) {
                case 1:
                    $item->showFormDisplay();
                    break;

                case 2:
                    $item->showFormUserPrefs($CFG_GLPI);
                    break;

                case 3:
                    $item->showFormInventory();
                    break;

                case 4:
                    $item->showFormHelpdesk();
                    break;

                case 5:
                    $item->showSystemInformations();
                    break;

                case 6:
                    $item->showFormDBSlave();
                    break;

                case 7:
                    $item->showPerformanceInformations();
                    break;

                case 8:
                    $item->showFormAPI();
                    break;

                case 9:
                    $item->showFormLogs();
                    break;

                case 10:
                    $item->showFormSecurity();
                    break;

                case 11:
                    Impact::showConfigForm();
                    break;

                case 12:
                    $item->showFormManagement();
                    break;
            }
        }
        return true;
    }

    /**
     * Display database engine checks report
     *
     * @since 9.3
     *
     * @param boolean $fordebug display for debug (no html required) (false by default)
     * @param string  $version  Version to check (mainly from install), defaults to null
     *
     * @return integer 2: missing extension,  1: missing optional extension, 0: OK,
     **/
    public static function displayCheckDbEngine($fordebug = false, $version = null)
    {
        global $CFG_GLPI;

        $error = 0;
        $result = self::checkDbEngine($version);
        $version = key($result);
        $db_ver = $result[$version];

        $ok_message = sprintf(__s('Database version seems correct (%s) - Perfect!'), $version);
        $ko_message = sprintf(__s('Your database engine version seems too old: %s.'), $version);

        if (!$db_ver) {
            $error = 2;
        }
        $message = $error > 0 ? $ko_message : $ok_message;

        if (isCommandLine()) {
            echo $message . "\n";
        } else {
            $img = "<img src='" . htmlescape($CFG_GLPI['root_doc']) . "/pics/";
            $img .= ($error > 0 ? "ko_min" : "ok_min") . ".png' alt='" . htmlescape($message) . "' title='" . htmlescape($message) . "'/>";

            if ($fordebug) {
                echo $img . htmlescape($message) . "\n";
            } else {
                $html = "<td";
                if ($error > 0) {
                    $html .= " class='red'";
                }
                $html .= ">";
                $html .= $img;
                $html .= '</td>';
                echo $html;
            }
        }
        return $error;
    }


    /**
     * Check for needed extensions
     *
     * @since 9.3
     *
     * @param string $raw Raw version to check (mainly from install), defaults to null
     *
     * @return array
     **/
    public static function checkDbEngine($raw = null)
    {
        if ($raw === null) {
            global $DB;
            $raw = $DB->getVersion();
        }

        $server  = preg_match('/-MariaDB/', $raw) ? 'MariaDB' : 'MySQL';
        $version = preg_replace('/^((\d+\.?)+).*$/', '$1', $raw);

        // MySQL >= 8.0 || MariaDB >= 10.6
        $is_supported = $server === 'MariaDB'
            ? version_compare($version, '10.6', '>=')
            : version_compare($version, '8.0', '>=');

        return [$version => $is_supported];
    }


    /**
     * Check for needed extensions
     *
     * @since 9.2 Method signature and return has changed
     *
     * @param null|array $list     Extensions list (from plugins)
     *
     * @return array [
     *                'error'     => integer 2: missing extension,  1: missing optionnal extension, 0: OK,
     *                'good'      => [ext => message],
     *                'missing'   => [ext => message],
     *                'may'       => [ext => message]
     *               ]
     **/
    public static function checkExtensions($list = null)
    {
        if ($list === null) {
            $extensions_to_check = [
                'mysqli'   => [
                    'required'  => true,
                ],
                'fileinfo' => [
                    'required'  => true,
                    'class'     => 'finfo',
                ],
                'json'     => [
                    'required'  => true,
                    'function'  => 'json_encode',
                ],
                'zlib'     => [
                    'required'  => true,
                ],
                'curl'      => [
                    'required'  => true,
                ],
                'gd'       => [
                    'required'  => true,
                ],
                'simplexml' => [
                    'required'  => true,
                ],
                'bcmath' => [
                    'required'  => true,
                ],
                //to sync/connect from LDAP
                'ldap'       => [
                    'required'  => false,
                ],
                //to enhance perfs
                'Zend OPcache' => [
                    'required'  => false,
                ],
                //for CAS lib
                'CAS'     => [
                    'required' => false,
                    'class'    => 'phpCAS',
                ],
                'exif' => [
                    'required'  => false,
                ],
                'intl' => [
                    'required' => true,
                ],
                'sodium' => [
                    'required' => false,
                ],
            ];
        } else {
            $extensions_to_check = $list;
        }

        $report = [
            'error'     => 0,
            'good'      => [],
            'missing'   => [],
            'may'       => [],
        ];

        //check for PHP extensions
        foreach ($extensions_to_check as $ext => $params) {
            $success = true;

            if (isset($params['call'])) {
                $success = call_user_func($params['call']);
            } elseif (isset($params['function'])) {
                if (!function_exists($params['function'])) {
                    $success = false;
                }
            } elseif (isset($params['class'])) {
                if (!class_exists($params['class'])) {
                    $success = false;
                }
            } else {
                if (!extension_loaded($ext)) {
                    $success = false;
                }
            }

            if ($success) {
                $msg = sprintf(__('%s extension is installed'), $ext);
                $report['good'][$ext] = $msg;
            } else {
                if (isset($params['required']) && $params['required'] === true) {
                    if ($report['error'] < 2) {
                        $report['error'] = 2;
                    }
                    $msg = sprintf(__('%s extension is missing'), $ext);
                    $report['missing'][$ext] = $msg;
                } else {
                    if ($report['error'] < 1) {
                        $report['error'] = 1;
                    }
                    $msg = sprintf(__('%s extension is not present'), $ext);
                    $report['may'][$ext] = $msg;
                }
            }
        }

        return $report;
    }


    /**
     * Get config values
     *
     * @since 0.85
     *
     * @param $context  string   context to get values (default for glpi is core)
     * @param $names    array    of config names to get
     *
     * @return array of config values
     **/
    public static function getConfigurationValues($context, array $names = [])
    {
        global $DB;

        $query = [
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'context'   => $context,
            ],
        ];

        if (count($names) > 0) {
            $query['WHERE']['name'] = $names;
        }

        $iterator = $DB->request($query);
        $result = [];
        foreach ($iterator as $line) {
            $result[$line['name']] = $line['value'];
        }
        return $result;
    }


    /**
     * Get config value
     *
     * @param $context  string   context to get values (default for glpi is core)
     * @param $name     string   config name
     *
     * @return mixed
     *
     * @since 10.0.0
     */
    public static function getConfigurationValue(string $context, string $name)
    {
        return self::getConfigurationValues($context, [$name])[$name] ?? null;
    }

    /**
     * Load legacy configuration into $CFG_GLPI global variable.
     *
     * @return boolean True for success, false if an error occurred
     *
     * @since 10.0.0 Parameter $older_to_latest is no longer used.
     */
    public static function loadLegacyConfiguration()
    {
        global $CFG_GLPI, $DB;

        // Compute URLs base path.
        $root_doc = '';
        if (isset($_SERVER['REQUEST_URI'])) {
            // $_SERVER['REQUEST_URI'] is set, meaning that GLPI is accessed from web server.
            $root_doc = Request::createFromGlobals()->getBasePath();
        }
        $CFG_GLPI['root_doc'] = $root_doc;
        $CFG_GLPI['typedoc_icon_dir'] = $root_doc . '/pics/icones';

        if (
            !DBConnection::isDbAvailable()
            || !$DB->tableExists('glpi_configs')
        ) {
            return false;
        }

        $iterator = $DB->request(['FROM' => 'glpi_configs']);

        if ($iterator->count() === 0) {
            return false;
        }

        $values = [];
        $allowed_context = ['core', 'inventory'];
        foreach ($iterator as $row) {
            if (!in_array($row['context'], $allowed_context)) {
                continue;
            }
            $values[$row['name']] = $row['value'];
        }

        $CFG_GLPI = array_merge($CFG_GLPI, $values);

        if (isset($CFG_GLPI['priority_matrix'])) {
            $CFG_GLPI['priority_matrix'] = importArrayFromDB($CFG_GLPI['priority_matrix']);
        }

        if (isset($CFG_GLPI['devices_in_menu'])) {
            $CFG_GLPI['devices_in_menu'] = importArrayFromDB($CFG_GLPI['devices_in_menu']);
        }

        if (isset($CFG_GLPI['lock_item_list'])) {
            $CFG_GLPI['lock_item_list'] = importArrayFromDB($CFG_GLPI['lock_item_list']);
        }

        if (
            isset($CFG_GLPI['lock_lockprofile_id'])
            && $CFG_GLPI['lock_use_lock_item']
            && $CFG_GLPI['lock_lockprofile_id'] > 0
            && !isset($CFG_GLPI['lock_lockprofile'])
        ) {
            $prof = new Profile();
            $prof->getFromDB($CFG_GLPI['lock_lockprofile_id']);
            $prof->cleanProfile();
            $CFG_GLPI['lock_lockprofile'] = $prof->fields;
        }

        if (isset($CFG_GLPI['planning_work_days'])) {
            $CFG_GLPI['planning_work_days'] = importArrayFromDB($CFG_GLPI['planning_work_days']);
        }

        if (isset($CFG_GLPI[Impact::CONF_ENABLED])) {
            $CFG_GLPI[Impact::CONF_ENABLED] = importArrayFromDB($CFG_GLPI[Impact::CONF_ENABLED]);
        }

        if (!isset($_SERVER['REQUEST_URI'])) {
            // $_SERVER['REQUEST_URI'] is not set, meaning that GLPI is probably access from CLI.
            // In this case, `$CFG_GLPI['root_doc']` has to be extracted from `$CFG_GLPI['url_base']`,
            // and it can only be done once configuration is loaded.

            // `$CFG_GLPI['root_doc']` is not supposed to be used in a CLI context,
            // but it is likely to be used indirectly by cron tasks.

            if (isset($CFG_GLPI['url_base'])) {
                $root_doc = parse_url($CFG_GLPI['url_base'], PHP_URL_PATH) ?: '';
                $CFG_GLPI['root_doc'] = $root_doc;
                $CFG_GLPI['typedoc_icon_dir'] = $root_doc . '/pics/icones';
            }
        }

        self::$loaded = true;

        return true;
    }

    /**
     * Indicates whether the legacy configuration has been correctly loaded.
     */
    public static function isLegacyConfigurationLoaded(): bool
    {
        return self::$loaded;
    }


    /**
     * Set config values : create or update entry
     *
     * @since 0.85
     *
     * @param $context  string context to get values (default for glpi is core)
     * @param $values   array  of config names to set
     *
     * @return void
     **/
    public static function setConfigurationValues($context, array $values = [])
    {

        $glpikey = new GLPIKey();

        $config = new self();
        foreach ($values as $name => $value) {
            // Encrypt config values according to list declared to GLPIKey service
            if (!empty($value) && $glpikey->isConfigSecured($context, $name)) {
                $value = $glpikey->encrypt($value);
            }

            if (
                $config->getFromDBByCrit([
                    'context'   => $context,
                    'name'      => $name,
                ])
            ) {
                $input = [
                    'id'        => $config->getID(),
                    'name'      => $name,
                    'context'   => $context,
                    'value'     => $value,
                ];

                $config->update($input);
            } else {
                $input = [
                    'context'   => $context,
                    'name'      => $name,
                    'value'     => $value,
                ];

                $config->add($input);
            }
        }

        //reload config for logged user
        if ($_SESSION['glpiID'] ?? false) {
            $user = new User();
            if ($user->getFromDB($_SESSION['glpiID'])) {
                $user->loadPreferencesInSession();
            }
        }
    }

    /**
     * Delete config entries
     *
     * @since 0.85
     *
     * @param $context string  context to get values (default for glpi is core)
     * @param $values  array   of config names to delete
     *
     * @return void
     **/
    public static function deleteConfigurationValues($context, array $values = [])
    {

        $config = new self();
        foreach ($values as $value) {
            if (
                $config->getFromDBByCrit([
                    'context'   => $context,
                    'name'      => $value,
                ])
            ) {
                $config->delete(['id' => $config->getID()]);
            }
        }
    }


    public function getRights($interface = 'central')
    {

        $values = parent::getRights();
        unset(
            $values[CREATE],
            $values[DELETE],
            $values[PURGE]
        );

        return $values;
    }

    /**
     * Get message that informs the user he is using an unstable version.
     *
     * @param bool $is_dev
     *
     * @return string
     */
    public static function agreeUnstableMessage(bool $is_dev)
    {
        return TemplateRenderer::getInstance()->render('install/agree_unstable.html.twig', [
            'is_dev' => $is_dev,
        ]);
    }

    /**
     * Get available palettes
     *
     * @param bool $expanded_info Get expanded info for each palette
     * @return array
     * @phpstan-return ($expanded_info is true ? array<string, array{name: string, dark: boolean}> : array<string, string>)
     */
    public function getPalettes(bool $expanded_info = false)
    {
        $all_themes = ThemeManager::getInstance()->getAllThemes();
        $themes = [];
        foreach ($all_themes as $theme) {
            if ($expanded_info) {
                $themes[$theme->getKey()] = [
                    'name' => $theme->getName(),
                    'dark' => $theme->isDarkTheme(),
                ];
            } else {
                $themes[$theme->getKey()] = $theme->getName();
            }
        }
        return $themes;
    }

    /**
     * Logs purge form
     *
     * @since 9.3
     *
     * @return void|boolean (display) Returns false if there is a rights error.
     */
    public function showFormLogs()
    {
        global $CFG_GLPI;

        if (!static::canUpdate()) {
            return false;
        }
        TemplateRenderer::getInstance()->display('pages/setup/general/logs_setup.html.twig', [
            'config' => $CFG_GLPI,
            'canedit' => static::canUpdate(),
        ]);
    }

    /**
     * Show intervals for logs purge
     *
     * @since 9.3
     *
     * @param string $name    Parameter name
     * @param mixed  $value   Parameter value
     * @param array  $options Options
     *
     * @return void
     */
    public static function showLogsInterval($name, $value, $options = [])
    {

        $values = [
            self::DELETE_ALL => __("Delete all"),
            self::KEEP_ALL   => __("Keep all"),
        ];
        for ($i = 1; $i < 121; $i++) {
            $values[$i] = sprintf(
                _n(
                    "Delete if older than %s month",
                    "Delete if older than %s months",
                    $i
                ),
                $i
            );
        }
        $options = array_merge([
            'value'   => $value,
            'display' => false,
            'class'   => 'purgelog_interval',
        ], $options);

        $out = "<div class='" . htmlescape($options['class']) . "'>";
        $out .= Dropdown::showFromArray($name, $values, $options);
        $out .= "</div>";

        echo $out;
    }

    /**
     * Security policy form
     *
     * @since 9.5.0
     *
     * @return void|boolean (display) Returns false if there is a rights error.
     */
    public function showFormSecurity()
    {
        global $CFG_GLPI;

        if (!Config::canUpdate()) {
            return false;
        }

        TemplateRenderer::getInstance()->display('pages/setup/general/security_setup.html.twig', [
            'canedit' => Session::haveRight(self::$rightname, UPDATE),
            'config'  => $CFG_GLPI,
        ]);
    }

    /**
     * Security form related to management entries.
     *
     * @since 10.0.0
     *
     * @return void|boolean (display) Returns false if there is a rights error.
     */
    public function showFormManagement()
    {
        global $CFG_GLPI;

        if (!self::canView()) {
            return false;
        }
        TemplateRenderer::getInstance()->display('pages/setup/general/management_setup.html.twig', [
            'config' => $CFG_GLPI,
            'canedit' => static::canUpdate(),
        ]);
    }

    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'   => 'common',
            'name' => __('Characteristics'),
        ];

        $tab[] = [
            'id'            => 1,
            'table'         => $this->getTable(),
            'field'         => 'value',
            'name'          => __('Value'),
            'massiveaction' => false,
        ];

        return $tab;
    }

    public function getLogTypeID()
    {
        return [$this->getType(), 1];
    }

    public function post_addItem()
    {
        $this->logConfigChange($this->fields['context'], $this->fields['name'], (string) $this->fields['value'], '');
    }

    public function post_updateItem($history = true)
    {
        global $CFG_GLPI, $DB;
        // Check if password expiration mechanism has been activated
        if (
            $this->fields['name'] == 'password_expiration_delay'
            && array_key_exists('value', $this->oldvalues)
            && (int) $this->oldvalues['value'] === -1
        ) {
            // As passwords will now expire, consider that "now" is the reference date of expiration delay
            $DB->update(
                User::getTable(),
                ['password_last_update' => $_SESSION['glpi_currenttime']],
                ['authtype' => Auth::DB_GLPI]
            );

            // Activate passwordexpiration automated task
            $DB->update(
                CronTask::getTable(),
                ['state' => 1,],
                ['name' => 'passwordexpiration']
            );
        }

        // If the `devices_in_menu` option changed, we should regenerate the menu
        if ($this->fields['name'] === 'devices_in_menu') {
            $CFG_GLPI['devices_in_menu'] = json_decode($this->fields['value']) ?? [];
            Html::generateMenuSession(true);
        }

        if (array_key_exists('value', $this->oldvalues)) {
            $newvalue = (string) $this->fields['value'];
            $oldvalue = (string) $this->oldvalues['value'];

            if ($newvalue === $oldvalue) {
                return;
            }

            // Ensure post update actions and hook that are using `$CFG_GLPI` will use the new value
            $array_fields = [
                'priority_matrix',
                'devices_in_menu',
                'lock_item_list',
                'planning_work_days',
                Impact::CONF_ENABLED,
            ];
            if (in_array($this->fields['name'], $array_fields, true)) {
                $CFG_GLPI[$this->fields['name']] = importArrayFromDB($newvalue);
            } else {
                $CFG_GLPI[$this->fields['name']] = $newvalue;
            }

            // avoid inserting truncated json in logs
            if (strlen($newvalue) > 255 && Toolbox::isJSON($newvalue)) {
                $newvalue = "{...}";
            }
            if (strlen($oldvalue) > 255 && Toolbox::isJSON($oldvalue)) {
                $oldvalue = "{...}";
            }

            $this->logConfigChange(
                $this->fields['context'],
                $this->fields['name'],
                $newvalue,
                $oldvalue
            );
        }
    }

    public function post_purgeItem()
    {
        $this->logConfigChange($this->fields['context'], $this->fields['name'], '', (string) $this->fields['value']);
    }

    /**
     * Log config change in history.
     *
     * @param string $context
     * @param string $name
     * @param string $newvalue
     * @param string $oldvalue
     *
     * @return void
     */
    private function logConfigChange(string $context, string $name, string $newvalue, string $oldvalue): void
    {
        $glpi_key = new GLPIKey();
        if ($glpi_key->isConfigSecured($context, $name)) {
            $newvalue = $oldvalue = '********';
        }
        $oldvalue = $name . ($context !== 'core' ? ' (' . $context . ') ' : ' ') . $oldvalue;

        if (in_array($name, NotificationMailingSetting::getRelatedConfigKeys(), true)) {
            // Specific case for email notification settings
            Log::history(
                1,
                NotificationMailingSetting::class,
                [1, $oldvalue, $newvalue]
            );
        } else {
            Log::constructHistory($this, ['value' => $oldvalue], ['value' => $newvalue]);
        }
    }

    /**
     * Get the GLPI Config without unsafe keys like passwords and emails (true on $safer)
     *
     * @param boolean $safer do we need to clean more (avoid emails disclosure)
     * @return array of $CFG_GLPI without unsafe keys
     *
     * @since 9.5
     */
    public static function getSafeConfig($safer = false)
    {
        global $CFG_GLPI;

        $excludedKeys = array_flip(self::$undisclosedFields);
        $safe_config  = array_diff_key($CFG_GLPI, $excludedKeys);

        if ($safer) {
            $excludedKeys = array_flip(self::$saferUndisclosedFields);
            $safe_config = array_diff_key($safe_config, $excludedKeys);
        }

        // override with session values
        foreach ($safe_config as $key => &$value) {
            $value = $_SESSION['glpi' . $key] ?? $value;
        }

        return $safe_config;
    }


    public static function getIcon()
    {
        return "ti ti-adjustments";
    }

    /**
     * Get UUID
     *
     * @param string $type UUID type (e.g. 'instance' or 'registration')
     *
     * @return string
     */
    final public static function getUuid($type)
    {
        $conf = self::getConfigurationValues('core', [$type . '_uuid']);
        $uuid = null;
        if (!isset($conf[$type . '_uuid']) || empty($conf[$type . '_uuid'])) {
            $uuid = self::generateUuid($type);
        } else {
            $uuid = $conf[$type . '_uuid'];
        }
        return $uuid;
    }

    /**
     * Generates an unique identifier and store it
     *
     * @param string $type UUID type (e.g. 'instance' or 'registration')
     *
     * @return string
     */
    final public static function generateUuid($type)
    {
        $uuid = Toolbox::getRandomString(40);
        self::setConfigurationValues('core', [$type . '_uuid' => $uuid]);
        return $uuid;
    }

    /**
     * Try to find a valid sender email from the GLPI configuration
     *
     * @param int|null $entities_id  Entity configuration to be used, default to
     *                               global configuration
     * @param bool     $no_reply     Should the configured "noreply" address be
     *                               used (default: false)
     *
     * @return array [email => sender address, name => sender name]
     */
    public static function getEmailSender(
        ?int $entities_id = null,
        bool $no_reply = false
    ): array {
        // Try to use the configured noreply address if no response is expected
        // for this notification
        if ($no_reply) {
            $sender = Config::getNoReplyEmailSender($entities_id);
            if ($sender['email'] !== null) {
                return $sender;
            } else {
                trigger_error('No-Reply address is not defined in configuration.', E_USER_WARNING);
            }
        }

        // Try to use the configured "from" email address
        $sender = Config::getFromEmailSender($entities_id);
        if ($sender['email'] !== null) {
            return $sender;
        }

        // Try to use the configured "admin" email address
        $sender = Config::getAdminEmailSender($entities_id);
        if ($sender['email'] !== null) {
            return $sender;
        }

        // No valid email was found
        trigger_error(
            'No email address is not defined in configuration.',
            E_USER_WARNING
        );

        // No values found
        return [
            'email' => null,
            'name'  => null,
        ];
    }

    /**
     * Try to find a valid "from" email from the GLPI configuration
     *
     * @param int|null $entities_id  Entity configuration to be used, default to
     *                               global configuration
     *
     * @return array [email => sender address, name => sender name]
     */
    public static function getFromEmailSender(?int $entities_id = null): array
    {
        return self::getEmailSenderFromEntityOrConfig('from_email', $entities_id);
    }

    /**
     * Try to find a valid "admin_email" email from the GLPI configuration
     *
     * @param int|null $entities_id  Entity configuration to be used, default to
     *                               global configuration
     *
     * @return array [email => sender address, name => sender name]
     */
    public static function getAdminEmailSender(?int $entities_id = null): array
    {
        return self::getEmailSenderFromEntityOrConfig('admin_email', $entities_id);
    }

    /**
     * Try to find a valid noreply email from the GLPI configuration
     *
     * @param int|null $entities_id  Entity configuration to be used, default to
     *                               global configuration
     *
     * @return array [email => noreply address, name => noreply name]
     */
    public static function getNoReplyEmailSender(?int $entities_id = null): array
    {
        return self::getEmailSenderFromEntityOrConfig('noreply_email', $entities_id);
    }

    /**
     * Try to find a valid replyto email from the GLPI configuration
     *
     * @param int|null $entities_id  Entity configuration to be used, default to
     *                               global configuration
     *
     * @return array [email => replyto address, name => replyto name]
     */
    public static function getReplyToEmailSender(?int $entities_id = null): array
    {
        return self::getEmailSenderFromEntityOrConfig('replyto_email', $entities_id);
    }

    /**
     * Try to find a valid email from the GLPI configuration
     *
     * @param string   $config_name  Configuration name
     * @param int|null $entities_id  Entity configuration to be used, default to
     *                               global configuration
     *
     * @return array [email => address, name => name]
     */
    private static function getEmailSenderFromEntityOrConfig(string $config_name, ?int $entities_id = null): array
    {
        global $CFG_GLPI;

        $email_config_name = $config_name;
        $name_config_name  = $config_name . '_name';

        // Check admin email in specified entity
        if (!is_null($entities_id)) {
            $entity_sender_email = trim(
                Entity::getUsedConfig($email_config_name, $entities_id, '', '')
            );
            $entity_sender_name = trim(
                Entity::getUsedConfig($name_config_name, $entities_id, '', '')
            );

            if (NotificationMailing::isUserAddressValid($entity_sender_email)) {
                return [
                    'email' => $entity_sender_email,
                    'name'  => $entity_sender_name,
                ];
            } elseif ($entity_sender_email !== '') {
                trigger_error(
                    sprintf(
                        'Invalid email address "%s" configured for entity "%s". Default administrator email will be used.',
                        $entity_sender_email,
                        $entities_id
                    ),
                    E_USER_WARNING
                );
            }
        }

        // Fallback to global configuration
        $global_sender_email = $CFG_GLPI[$email_config_name] ?? "";
        $global_sender_name  = $CFG_GLPI[$name_config_name]  ?? "";

        if (NotificationMailing::isUserAddressValid($global_sender_email)) {
            return [
                'email' => $global_sender_email,
                'name'  => $global_sender_name,
            ];
        } elseif ($global_sender_email !== '') {
            trigger_error(
                sprintf('Invalid email address "%s" configured in "%s".', $global_sender_email, $config_name),
                E_USER_WARNING
            );
        }

        // No valid values found
        return [
            'email' => null,
            'name'  => null,
        ];
    }

    /**
     * Override parent: "{itemtype} - {header name}" -> "{itemtype}"
     * There is only one config, no need to display the item name
     *
     * @return string
     */
    public function getBrowserTabName(): string
    {
        return self::getTypeName(1);
    }

    /**
     * Gets the ID of a random record from the config table with the specified context.
     *
     * Used as a hacky workaround when we require a valid glpi_configs record for rights checks.
     * We cannot rely on something being in ID 1 for the core context for example, because some clustering solutions may change how autoincrement works.
     * @return ?int
     * @internal
     */
    public static function getConfigIDForContext(string $context)
    {
        global $DB;
        $iterator = $DB->request([
            'SELECT' => ['MIN' => 'id AS id'],
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'context' => $context,
            ],
        ]);
        if (count($iterator)) {
            return $iterator->current()['id'];
        }
        return null;
    }

    public static function allowUnauthenticatedUploads(): bool
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        return (bool) ($CFG_GLPI['allow_unauthenticated_uploads'] ?? false);
    }

    public static function isHlApiEnabled(): bool
    {
        global $CFG_GLPI;
        return (bool) ($CFG_GLPI['enable_hlapi'] ?? 0);
    }
}
