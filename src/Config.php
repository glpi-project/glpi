<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

use Glpi\Agent\Communication\AbstractRequest;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Cache\CacheManager;
use Glpi\Dashboard\Grid;
use Glpi\Exception\PasswordTooWeakException;
use Glpi\Plugin\Hooks;
use Glpi\System\RequirementsManager;
use Glpi\Toolbox\Sanitizer;
use PHPMailer\PHPMailer\PHPMailer;

/**
 *  Config class
 **/
class Config extends CommonDBTM
{
    const DELETE_ALL = -1;
    const KEEP_ALL = 0;

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

            $menu['options']['apiclient']['icon']            = APIClient::getIcon();
            $menu['options']['apiclient']['title']           = APIClient::getTypeName(Session::getPluralNumber());
            $menu['options']['apiclient']['page']            = Config::getFormURL(false) . '?forcetab=Config$8';
            $menu['options']['apiclient']['links']['search'] = Config::getFormURL(false) . '?forcetab=Config$8';
            $menu['options']['apiclient']['links']['add']    = '/front/apiclient.form.php';
        }
        if (count($menu)) {
            return $menu;
        }
        return false;
    }


    public static function canCreate()
    {
        return false;
    }


    public function canViewItem()
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
        $this->addStandardTab(__CLASS__, $ong, $options);
        $this->addStandardTab('GLPINetwork', $ong, $options);
        $this->addStandardTab('Log', $ong, $options);

        return $ong;
    }

    public function prepareInputForUpdate($input)
    {
        /** @var array $CFG_GLPI */
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
            $this->setConfigurationValues($config_context, $input);
            return false;
        }

       // Trim automatically ending slash for url_base config as, for all existing occurrences,
       // this URL will be prepended to something that starts with a slash.
        if (isset($input["url_base"]) && !empty($input["url_base"])) {
            if (Toolbox::isValidWebUrl($input["url_base"])) {
                $input["url_base"] = rtrim($input["url_base"], '/');
            } else {
                Session::addMessageAfterRedirect(__('Invalid base URL!'), false, ERROR);
                return false;
            }
        }

        if (isset($input["url_base_api"]) && !empty($input["url_base_api"])) {
            if (!Toolbox::isValidWebUrl($input["url_base_api"])) {
                Session::addMessageAfterRedirect(__('Invalid API base URL!'), false, ERROR);
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
                } else if (isset($input["_dbreplicate_dbhost"])) {
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

        if (isset($input['_update_devices_in_menu'])) {
            $input['devices_in_menu'] = exportArrayToDB(
                (isset($input['devices_in_menu']) ? $input['devices_in_menu'] : [])
            );
        }

       // lock mechanism update
        if (isset($input['lock_use_lock_item'])) {
            $input['lock_item_list'] = exportArrayToDB((isset($input['lock_item_list'])
                                                      ? $input['lock_item_list'] : []));
        }

        if (isset($input[Impact::CONF_ENABLED])) {
            $input[Impact::CONF_ENABLED] = exportArrayToDB($input[Impact::CONF_ENABLED]);
        }

        if (isset($input['planning_work_days'])) {
            $input['planning_work_days'] = exportArrayToDB($input['planning_work_days']);
        }

       // Beware : with new management system, we must update each value
        unset($input['id']);
        unset($input['_glpi_csrf_token']);
        unset($input['_update']);

       // Add skipMaintenance if maintenance mode update
        if (isset($input['maintenance_mode']) && $input['maintenance_mode']) {
            $_SESSION['glpiskipMaintenance'] = 1;
            $url = $CFG_GLPI['root_doc'] . "/index.php?skipMaintenance=1";
            Session::addMessageAfterRedirect(
                sprintf(
                    __('Maintenance mode activated. Backdoor using: %s'),
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
                    __("The specified profile doesn't exist or is not allowed to access the central interface."),
                    false,
                    ERROR
                );
                unset($input['lock_lockprofile_id']);
            }
        }

        $this->setConfigurationValues('core', $input);

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
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (array_key_exists('smtp_mode', $input) && (int)$input['smtp_mode'] === MAIL_SMTPOAUTH) {
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
            $_SESSION['redirect_to_smtp_oauth'] = (bool)($input['_force_redirect_to_smtp_oauth'] ?? false) === true
                || $has_oauth_settings_changed
                || (string)$CFG_GLPI['smtp_oauth_refresh_token'] === '';

            // ensure value is not saved in DB
            unset($input['_force_redirect_to_smtp_oauth']);
        } elseif (array_key_exists('smtp_mode', $input) && (int)$input['smtp_mode'] !== MAIL_SMTPOAUTH) {
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
        /** @var array $CFG_GLPI */
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
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (!self::canView()) {
            return;
        }

        $rand = mt_rand();
        $canedit = Config::canUpdate();
        if ($canedit) {
            echo "<form name='form' action=\"" . Toolbox::getItemTypeFormURL(__CLASS__) . "\" method='post' data-track-changes='true'>";
        }
        echo "<div class='center' id='tabsbody'>";
        echo "<table class='tab_cadre_fixe'>";

        echo "<tr><th colspan='4'>" . _n('Asset', 'Assets', Session::getPluralNumber()) . "</th></tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td width='30%'><label for='dropdown_auto_create_infocoms$rand'>" . __('Enable the financial and administrative information by default') . "</label></td>";
        echo "<td  width='20%'>";
        Dropdown::ShowYesNo('auto_create_infocoms', $CFG_GLPI["auto_create_infocoms"], -1, ['rand' => $rand]);
        echo "</td><td width='20%'><label for='dropdown_monitors_management_restrict$rand'>" . __('Restrict monitor management') . "</label></td>";
        echo "<td width='30%'>";
        $this->dropdownGlobalManagement(
            "monitors_management_restrict",
            $CFG_GLPI["monitors_management_restrict"],
            $rand
        );
        echo "</td></tr>";

        echo "<tr class='tab_bg_2'><td><label for='dropdown_softwarecategories_id_ondelete$rand'>" . __('Software category deleted by the dictionary rules') .
           "</label></td><td>";
        SoftwareCategory::dropdown(['value' => $CFG_GLPI["softwarecategories_id_ondelete"],
            'name'  => "softwarecategories_id_ondelete",
            'rand'  => $rand
        ]);
        echo "</td><td><label for='dropdown_peripherals_management_restrict$rand'>" . __('Restrict device management') . "</label></td><td>";
        $this->dropdownGlobalManagement(
            "peripherals_management_restrict",
            $CFG_GLPI["peripherals_management_restrict"],
            $rand
        );
        echo "</td></tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td><label for='showdate$rand'>" . __('End of fiscal year') . "</label></td><td>";
        Html::showDateField("date_tax", ['value'      => $CFG_GLPI["date_tax"],
            'maybeempty' => false,
            'canedit'    => true,
            'min'        => '',
            'max'        => '',
            'showyear'   => false,
            'rand'       => $rand
        ]);
        echo "</td><td><label for='dropdown_phones_management_restrict$rand'>" . __('Restrict phone management') . "</label></td><td>";
        $this->dropdownGlobalManagement(
            "phones_management_restrict",
            $CFG_GLPI["phones_management_restrict"],
            $rand
        );
        echo "</td></tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td><label for='dropdown_use_autoname_by_entity$rand'>" . __('Automatic fields (marked by *)') . "</label></td><td>";
        $tab = [0 => __('Global'),
            1 => __('By entity')
        ];
        Dropdown::showFromArray(
            'use_autoname_by_entity',
            $tab,
            ['value' => $CFG_GLPI["use_autoname_by_entity"], 'rand' => $rand]
        );
        echo "</td><td><label for='dropdown_printers_management_restrict$rand'>" . __('Restrict printer management') . "</label></td><td>";
        $this->dropdownGlobalManagement(
            "printers_management_restrict",
            $CFG_GLPI["printers_management_restrict"],
            $rand
        );
        echo "</td></tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td><label for='devices_in_menu$rand'>" . __('Devices displayed in menu') . "</label></td>";
        echo "<td>";

        $dd_params = [
            'name'      => 'devices_in_menu',
            'values'    => $CFG_GLPI['devices_in_menu'],
            'display'   => true,
            'rand'      => $rand,
            'multiple'  => true,
            'size'      => 3
        ];

        $item_devices_types = [];
        foreach ($CFG_GLPI['itemdevices'] as $key => $itemtype) {
            if ($item = getItemForItemtype($itemtype)) {
                $item_devices_types[$itemtype] = $item->getTypeName();
            } else {
                unset($CFG_GLPI['itemdevices'][$key]);
            }
        }

        Dropdown::showFromArray($dd_params['name'], $item_devices_types, $dd_params);

        echo "<input type='hidden' name='_update_devices_in_menu' value='1'>";
        echo "</td></tr>\n";

        echo "</table>";

        echo "<br><table class='tab_cadre_fixe'>";
        echo "<tr>";
        echo "<th colspan='4'>" . __('Automatically update of the elements related to the computers');
        echo "</th><th colspan='2'>" . __('Unit management') . "</th></tr>";

        echo "<tr><th>&nbsp;</th>";
        echo "<th>" . __('Alternate username') . "</th>";
        echo "<th>" . User::getTypeName(1) . "</th>";
        echo "<th>" . Group::getTypeName(1) . "</th>";
        echo "<th>" . Location::getTypeName(1) . "</th>";
        echo "<th>" . __('Status') . "</th>";
        echo "</tr>";

        $fields = ["contact", "user", "group", "location"];
        echo "<tr class='tab_bg_2'>";
        echo "<td> " . __('When connecting or updating') . "</td>";
        $values = [
            __('Do not copy'),
            __('Copy'),
        ];

        foreach ($fields as $field) {
            echo "<td>";
            $fieldname = "is_" . $field . "_autoupdate";
            Dropdown::showFromArray($fieldname, $values, ['value' => $CFG_GLPI[$fieldname]]);
            echo "</td>";
        }

        echo "<td>";
        State::dropdownBehaviour(
            "state_autoupdate_mode",
            __('Copy computer status'),
            $CFG_GLPI["state_autoupdate_mode"]
        );
        echo "</td></tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td> " . __('When disconnecting') . "</td>";
        $values = [
            __('Do not delete'),
            __('Clear'),
        ];

        foreach ($fields as $field) {
            echo "<td>";
            $fieldname = "is_" . $field . "_autoclean";
            Dropdown::showFromArray($fieldname, $values, ['value' => $CFG_GLPI[$fieldname]]);
            echo "</td>";
        }

        echo "<td>";
        State::dropdownBehaviour(
            "state_autoclean_mode",
            __('Clear status'),
            $CFG_GLPI["state_autoclean_mode"]
        );
        echo "</td></tr>";

        if ($canedit) {
            echo "<tr class='tab_bg_2'>";
            echo "<td colspan='6' class='center'>";
            echo "<input type='submit' name='update' class='btn btn-primary' value=\"" . _sx('button', 'Save') . "\">";
            echo "</td></tr>";
        }

        echo "</table></div>";
        Html::closeForm();
    }


    /**
     * Print the config form for restrictions
     *
     * @return void
     **/
    public function showFormAuthentication()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (!Config::canUpdate()) {
            return;
        }

        echo "<form name='form' action=\"" . Toolbox::getItemTypeFormURL(__CLASS__) . "\" method='post' data-track-changes='true'>";
        echo "<div class='card' id='tabsbody'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr><th colspan='4'>" . __('Authentication') . "</th></tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td width='30%'>" . __('Automatically add users from an external authentication source') .
           "</td><td width='20%'>";
        Dropdown::showYesNo("is_users_auto_add", $CFG_GLPI["is_users_auto_add"]);
        echo "</td><td width='30%'>" . __('Add a user without accreditation from a LDAP directory') .
           "</td><td width='20%'>";
        Dropdown::showYesNo("use_noright_users_add", $CFG_GLPI["use_noright_users_add"]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td> " . __('Action when a user is deleted from the LDAP directory') . "</td><td>";
        AuthLDAP::dropdownUserDeletedActions($CFG_GLPI["user_deleted_ldap"]);
        echo "</td><td> " . __('Action when a user is restored in the LDAP directory') . "</td><td>";
        AuthLDAP::dropdownUserRestoredActions($CFG_GLPI["user_restored_ldap"]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td> " . __('GLPI server time zone') . "</td><td>";
        Dropdown::showGMT("time_offset", $CFG_GLPI["time_offset"]);
        echo "</td><td></td></tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td colspan='4' class='center'>";
        echo "<input type='submit' name='update_auth' class='btn btn-primary' value=\"" . _sx('button', 'Save') .
           "\">";
        echo "</td></tr>";

        echo "</table></div>";
        Html::closeForm();
    }


    /**
     * Print the config form for slave DB
     *
     * @return void
     **/
    public function showFormDBSlave()
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         * @var \DBmysql $DBslave
         */
        global $CFG_GLPI, $DB, $DBslave;

        if (!Config::canUpdate()) {
            return;
        }

        echo "<form name='form' action=\"" . Toolbox::getItemTypeFormURL(__CLASS__) . "\" method='post' data-track-changes='true'>";
        echo "<div class='center' id='tabsbody'>";
        echo "<input type='hidden' name='_dbslave_status' value='1'>";
        echo "<table class='tab_cadre_fixe'>";

        echo "<tr class='tab_bg_2'><th colspan='4'>" . _n('SQL replica', 'SQL replicas', Session::getPluralNumber()) .
           "</th></tr>";
        $DBslave = DBConnection::getDBSlaveConf();

        if (is_array($DBslave->dbhost)) {
            $host = implode(' ', $DBslave->dbhost);
        } else {
            $host = $DBslave->dbhost;
        }
        echo "<tr class='tab_bg_2'>";
        echo "<td>" . __('SQL server (MariaDB or MySQL)') . "</td>";
        echo "<td><input type='text' name='_dbreplicate_dbhost' size='40' value='$host'></td>";
        echo "<td>" . _n('Database', 'Databases', 1) . "</td>";
        echo "<td><input type='text' name='_dbreplicate_dbdefault' value='" . $DBslave->dbdefault . "'>";
        echo "</td></tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td>" . __('SQL user') . "</td>";
        echo "<td><input type='text' name='_dbreplicate_dbuser' value='" . $DBslave->dbuser . "'></td>";
        echo "<td>" . __('SQL password') . "</td>";
        echo "<td><input type='password' name='_dbreplicate_dbpassword' value='" .
                 rawurldecode($DBslave->dbpassword) . "'>";
        echo "</td></tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td>" . __('Use the replica for the search engine') . "</td><td>";
        $values = [0 => __('Never'),
            1 => __('If synced (all changes)'),
            2 => __('If synced (current user changes)'),
            3 => __('If synced or read-only account'),
            4 => __('Always')
        ];
        Dropdown::showFromArray(
            'use_slave_for_search',
            $values,
            ['value' => $CFG_GLPI["use_slave_for_search"]]
        );
        echo "<td colspan='2'>&nbsp;</td>";
        echo "</tr>";

        if ($DBslave->connected && !$DB->isSlave()) {
            echo "<tr class='tab_bg_2'><td colspan='4' class='center'>";
            DBConnection::showAllReplicateDelay();
            echo "</td></tr>";
        }

        echo "<tr class='tab_bg_2'><td colspan='4' class='center'>";
        echo "<input type='submit' name='update' class='btn btn-primary' value=\"" . _sx('button', 'Save') . "\">";
        echo "</td></tr>";

        echo "</table></div>";
        Html::closeForm();
    }


    /**
     * Print the config form for External API
     *
     * @since 9.1
     * @return void
     **/
    public function showFormAPI()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (!self::canView()) {
            return;
        }

        echo "<div class='center spaced' id='tabsbody'>";

        $rand = mt_rand();
        $canedit = Config::canUpdate();
        if ($canedit) {
            echo "<form name='form' action=\"" . Toolbox::getItemTypeFormURL(__CLASS__) . "\" method='post' data-track-changes='true'>";
        }
        echo "<table class='tab_cadre_fixe'>";

        echo "<tr><th colspan='4'>" . __('API') . "</th></tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td><label for='url_base_api'>" . __('URL of the API') . "</label></td>";
        echo "<td colspan='3'><input type='url' name='url_base_api' id='url_base_api' value='" . $CFG_GLPI["url_base_api"] . "' class='form-control'></td>";
        echo "</tr>";
        echo "<tr class='tab_bg_2'>";
        echo "<td><label for='dropdown_enable_api$rand'>" . __("Enable Rest API") . "</label></td>";
        echo "<td>";
        Dropdown::showYesNo("enable_api", $CFG_GLPI["enable_api"], -1, ['rand' => $rand]);
        echo "</td>";
        if ($CFG_GLPI["enable_api"]) {
            echo "<td colspan='2'>";
            $inline_doc_api = trim($CFG_GLPI['url_base_api'], '/') . "/";
            echo "<a href='$inline_doc_api'>" . __("API inline Documentation") . "</a>";
            echo "</td>";
        }
        echo "</tr>";

        echo "<tr><th colspan='4'>" . __('Authentication') . "</th></tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td><label for='dropdown_enable_api_login_credentials$rand'>";
        echo __("Enable login with credentials") . "</label>&nbsp;";
        Html::showToolTip(__("Allow to login to API and get a session token with user credentials"));
        echo "</td>";
        echo "<td>";
        Dropdown::showYesNo("enable_api_login_credentials", $CFG_GLPI["enable_api_login_credentials"], -1, ['rand' => $rand]);
        echo "</td>";
        echo "<td><label for='dropdown_enable_api_login_external_token$rand'>";
        echo __("Enable login with external token") . "</label>&nbsp;";
        Html::showToolTip(__("Allow to login to API and get a session token with user external token. See Remote access key in user Settings tab "));
        echo "</td>";
        echo "<td>";
        Dropdown::showYesNo("enable_api_login_external_token", $CFG_GLPI["enable_api_login_external_token"], -1, ['rand' => $rand]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'><td colspan='4' class='center'>";
        echo "<input type='submit' name='update' class='btn btn-primary' value=\"" . _sx('button', 'Save') . "\">";
        echo "<br><br><br>";
        echo "</td></tr>";

        echo "</table>";
        Html::closeForm();

        echo "<table class='tab_cadre_fixe'>";
        echo "<tr><td>";
        echo "<hr>";
        $buttons = [
            'apiclient.form.php' => __('Add API client'),
        ];
        Html::displayTitle(
            "",
            self::getTypeName(Session::getPluralNumber()),
            "",
            $buttons
        );
        Search::show("APIClient");
        echo "</td></tr>";
        echo "</table></div>";
    }


    /**
     * Print the config form for connections
     *
     * @return void
     **/
    public function showFormHelpdesk()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (!self::canView()) {
            return;
        }

        $rand = mt_rand();
        $canedit = Config::canUpdate();
        if ($canedit) {
            echo "<form name='form' action=\"" . Toolbox::getItemTypeFormURL(__CLASS__) . "\" method='post' data-track-changes='true'>";
        }
        echo "<div class='center spaced' id='tabsbody'>";
        echo "<table class='tab_cadre_fixe'>";

        echo "<tr><th colspan='4'>" . __('Assistance') . "</th></tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td width='30%'><label for='dropdown_time_step$rand'>" . __('Step for the hours (minutes)') . "</label></td>";
        echo "<td width='20%'>";
        Dropdown::showNumber('time_step', ['value' => $CFG_GLPI["time_step"],
            'min'   => 30,
            'max'   => 60,
            'step'  => 30,
            'toadd' => [1  => 1,
                5  => 5,
                10 => 10,
                15 => 15,
                20 => 20
            ],
            'rand'  => $rand
        ]);
        echo "</td>";
        echo "<td width='30%'><label for='dropdown_planning_begin$rand'>" . __('Limit of the schedules for planning') . "</label></td>";
        echo "<td width='20%'>";
        Dropdown::showHours('planning_begin', ['value' => $CFG_GLPI["planning_begin"], 'rand' => $rand]);
        echo "&nbsp;<label for='dropdown_planning_end$rand'>-></label>&nbsp;";
        Dropdown::showHours('planning_end', ['value' => $CFG_GLPI["planning_end"], 'rand' => $rand]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td><label for='dropdown_default_mailcollector_filesize_max$rand'>" . __('Default file size limit imported by the mails receiver') . "</label></td><td>";
        MailCollector::showMaxFilesize(
            'default_mailcollector_filesize_max',
            $CFG_GLPI["default_mailcollector_filesize_max"],
            $rand
        );
        echo "</td>";

        echo "<td><label for='dropdown_documentcategories_id_forticket$rand'>" . __('Default heading when adding a document to a ticket') . "</label></td><td>";
        DocumentCategory::dropdown(['value' => $CFG_GLPI["documentcategories_id_forticket"],
            'name'  => "documentcategories_id_forticket",
            'rand'  => $rand
        ]);
        echo "</td></tr>";
        echo "<tr class='tab_bg_2'><td><label for='dropdown_default_software_helpdesk_visible$rand'>" . __('By default, a software may be linked to a ticket') . "</label></td><td>";
        Dropdown::showYesNo(
            "default_software_helpdesk_visible",
            $CFG_GLPI["default_software_helpdesk_visible"],
            -1,
            ['rand' => $rand]
        );
        echo "</td>";

        echo "<td><label for='dropdown_keep_tickets_on_delete$rand'>" . __('Keep tickets when purging hardware in the inventory') . "</label></td><td>";
        Dropdown::showYesNo("keep_tickets_on_delete", $CFG_GLPI["keep_tickets_on_delete"], -1, ['rand' => $rand]);
        echo "</td></tr><tr class='tab_bg_2'><td><label for='dropdown_use_check_pref$rand'>" . __('Show personnal information in new ticket form (simplified interface)');
        echo "</label></td>";
        echo "<td>";
        Dropdown::showYesNo('use_check_pref', $CFG_GLPI['use_check_pref'], -1, ['rand' => $rand]);
        echo "</td>";

        echo "<td><label for='dropdown_use_anonymous_helpdesk$rand'>" . __('Allow anonymous ticket creation (helpdesk.receiver)') . "</label></td><td>";
        Dropdown::showYesNo("use_anonymous_helpdesk", $CFG_GLPI["use_anonymous_helpdesk"], -1, ['rand' => $rand]);
        echo "</td></tr><tr class='tab_bg_2'><td><label for='dropdown_use_anonymous_followups$rand'>" . __('Allow anonymous followups (receiver)') . "</label></td><td>";
        Dropdown::showYesNo("use_anonymous_followups", $CFG_GLPI["use_anonymous_followups"], -1, ['rand' => $rand]);
        echo "</td><td colspan='2'></td></tr>";

        echo "<tr>";
        echo "<td>";
        echo "<label for='dropdown_planning_work_days$rand'>" . __('Planning work days') . "</label>";
        echo "</td>";
        echo "<td colspan='3'>";
        Dropdown::showFromArray(
            "planning_work_days",
            [
                1 => __("Monday"),
                2 => __("Tuesday"),
                3 => __("Wednesday"),
                4 => __("Thursday"),
                5 => __("Friday"),
                6 => __("Saturday"),
                0 => __("Sunday"),
            ],
            [
                'values'   => $CFG_GLPI["planning_work_days"],
                'multiple' => true,
                'rand'     => $rand,
            ]
        );
        echo "</td>";
        echo "</tr>";
        echo "</table>";

        echo "<table class='tab_cadre_fixe'>";
        echo "<tr><th colspan='7'>" . __('Matrix of calculus for priority');
        echo "<input type='hidden' name='_matrix' value='1'></th></tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td class='b right' colspan='2'>" . __('Impact') . "</td>";

        $isimpact = [];
        for ($impact = 5; $impact >= 1; $impact--) {
            echo "<td class='center'>" . Ticket::getImpactName($impact) . '<br>';

            if ($impact == 3) {
                $isimpact[3] = 1;
                echo "<input type='hidden' name='_impact_3' value='1'>";
            } else {
                $isimpact[$impact] = (($CFG_GLPI['impact_mask'] & (1 << $impact)) > 0);
                Dropdown::showYesNo("_impact_{$impact}", $isimpact[$impact]);
            }
            echo "</td>";
        }
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td class='b' colspan='2'>" . __('Urgency') . "</td>";

        for ($impact = 5; $impact >= 1; $impact--) {
            echo "<td>&nbsp;</td>";
        }
        echo "</tr>";

        $isurgency = [];
        for ($urgency = 5; $urgency >= 1; $urgency--) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>" . Ticket::getUrgencyName($urgency) . "&nbsp;</td>";
            echo "<td>";

            if ($urgency == 3) {
                $isurgency[3] = 1;
                echo "<input type='hidden' name='_urgency_3' value='1'>";
            } else {
                $isurgency[$urgency] = (($CFG_GLPI['urgency_mask'] & (1 << $urgency)) > 0);
                Dropdown::showYesNo("_urgency_{$urgency}", $isurgency[$urgency]);
            }
            echo "</td>";

            for ($impact = 5; $impact >= 1; $impact--) {
                $pri = round(($urgency + $impact) / 2);

                if (isset($CFG_GLPI['priority_matrix'][$urgency][$impact])) {
                    $pri = $CFG_GLPI['priority_matrix'][$urgency][$impact];
                }

                if ($isurgency[$urgency] && $isimpact[$impact]) {
                    $bgcolor = $_SESSION["glpipriority_$pri"];
                    echo "<td class='center' bgcolor='$bgcolor'>";
                    Ticket::dropdownPriority([
                        'value' => $pri,
                        'name'  => "_matrix_{$urgency}_{$impact}",
                        'enable_filtering' => false,
                    ]);
                    echo "</td>";
                } else {
                    echo "<td><input type='hidden' name='_matrix_{$urgency}_{$impact}' value='$pri'>
                     </td>";
                }
            }
            echo "</tr>\n";
        }
        if ($canedit) {
            echo "<tr class='tab_bg_2'>";
            echo "<td colspan='7' class='center'>";
            echo "<input type='submit' name='update' class='btn btn-primary' value=\"" . _sx('button', 'Save') . "\">";
            echo "</td></tr>";
        }

        echo "</table></div>";
        Html::closeForm();
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
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $oncentral = (Session::getCurrentInterface() == "central");
        $userpref  = false;
        $url       = Toolbox::getItemTypeFormURL(__CLASS__);
        $rand      = mt_rand();

        $canedit = Config::canUpdate();
        $canedituser = Session::haveRight('personalization', UPDATE);
        if (array_key_exists('last_login', $data)) {
            $userpref = true;
            if ($data["id"] === Session::getLoginUserID()) {
                $url  = $CFG_GLPI['root_doc'] . "/front/preference.php";
            } else {
                $url  = User::getFormURL();
            }
        }

        if ((!$userpref && $canedit) || ($userpref && $canedituser)) {
            echo "<form name='form' action='$url' method='post' data-track-changes='true'>";
        }

       // Only set id for user prefs
        if ($userpref) {
            echo "<input type='hidden' name='id' value='" . $data['id'] . "'>";
        }
        echo "<div class='center' id='tabsbody'>";
        echo "<table class='tab_cadre_fixe'>";

        echo "<tr><th colspan='4'>" . __('Personalization') . "</th></tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td width='30%'><label for='dropdown_language$rand'>" . ($userpref ? __('Language') : __('Default language')) . "</label></td>";
        echo "<td width='20%'>";
        if (
            Config::canUpdate()
            || !GLPI_DEMO_MODE
        ) {
            Dropdown::showLanguages("language", ['value' => $data["language"], 'rand' => $rand]);
        } else {
            echo "&nbsp;";
        }

        echo "<td width='30%'><label for='dropdown_date_format$rand'>" . __('Date format') . "</label></td>";
        echo "<td width='20%'>";
        Dropdown::showFromArray('date_format', Toolbox::phpDateFormats(), ['value' => $data["date_format"], 'rand' => $rand]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td><label for='dropdown_names_format$rand'>" . __('Display order of surnames firstnames') . "</label></td><td>";
        $values = [User::REALNAME_BEFORE  => __('Surname, First name'),
            User::FIRSTNAME_BEFORE => __('First name, Surname')
        ];
        Dropdown::showFromArray('names_format', $values, ['value' => $data["names_format"], 'rand' => $rand]);
        echo "</td>";
        echo "<td><label for='dropdown_number_format$rand'>" . __('Number format') . "</label></td>";
        $values = [0 => '1 234.56',
            1 => '1,234.56',
            2 => '1 234,56',
            3 => '1234.56',
            4 => '1234,56'
        ];
        echo "<td>";
        Dropdown::showFromArray('number_format', $values, ['value' => $data["number_format"], 'rand' => $rand]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td><label for='dropdown_list_limit$rand'>" . __('Results to display by page') . "</label></td><td>";
       // Limit using global config
        $value = (($data['list_limit'] < $CFG_GLPI['list_limit_max'])
                ? $data['list_limit'] : $CFG_GLPI['list_limit_max']);
        Dropdown::showNumber('list_limit', ['value' => $value,
            'min'   => 5,
            'max'   => $CFG_GLPI['list_limit_max'],
            'step'  => 5,
            'rand'  => $rand
        ]);
        echo "</td>";
        echo "<td><label for='dropdown_backcreated$rand'>" . __('Go to created item after creation') . "</label></td>";
        echo "<td>";
        Dropdown::showYesNo("backcreated", $data["backcreated"], -1, ['rand' => $rand]);
        echo "</td>";

        echo "</tr>";

        if ($oncentral) {
            echo "<tr class='tab_bg_2'>";
            echo "<td><label for='dropdown_use_flat_dropdowntree$rand'>" . __('Display the tree dropdown complete name in dropdown inputs') . "</label></td><td>";
            Dropdown::showYesNo('use_flat_dropdowntree', $data["use_flat_dropdowntree"], -1, ['rand' => $rand]);
            echo "</td>";

            echo "<td><label for='dropdown_use_flat_dropdowntree_on_search_result$rand'>" . __('Display the complete name of tree dropdown in search results') . "</label></td><td>";
            Dropdown::showYesNo('use_flat_dropdowntree_on_search_result', $data["use_flat_dropdowntree_on_search_result"], -1, ['rand' => $rand]);
            echo "</td>";
            echo "</tr>";
        }

        echo "<tr class='tab_bg_2'>";
        if (
            !$userpref
            || ($CFG_GLPI['show_count_on_tabs'] != -1)
        ) {
            echo "<td><label for='dropdown_show_count_on_tabs$rand'>" . __('Display counters') . "</label></td><td>";

            $values = [0 => __('No'),
                1 => __('Yes')
            ];

            if (!$userpref) {
                $values[-1] = __('Never');
            }
            Dropdown::showFromArray(
                'show_count_on_tabs',
                $values,
                ['value' => $data["show_count_on_tabs"], 'rand' => $rand]
            );
            echo "</td>";
        } else {
            echo "<td colspan='2'>&nbsp;</td>";
        }
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        if ($oncentral) {
            echo "<td><label for='dropdown_is_ids_visible$rand'>" . __('Show GLPI ID') . "</label></td><td>";
            Dropdown::showYesNo("is_ids_visible", $data["is_ids_visible"], -1, ['rand' => $rand]);
            echo "</td>";
        } else {
            echo "<td colspan='2'></td>";
        }

        echo "<td><label for='dropdown_keep_devices_when_purging_item$rand'>" . __('Keep devices when purging an item') . "</label></td><td>";
        Dropdown::showYesNo(
            'keep_devices_when_purging_item',
            $data['keep_devices_when_purging_item'],
            -1,
            ['rand' => $rand]
        );
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td><label for='dropdown_notification_to_myself$rand'>" . __('Notifications for my changes') . "</label></td><td>";
        Dropdown::showYesNo("notification_to_myself", $data["notification_to_myself"], -1, ['rand' => $rand]);
        echo "</td>";
        if ($oncentral) {
            echo "<td><label for='dropdown_display_count_on_home$rand'>" . __('Results to display on home page') . "</label></td><td>";
            Dropdown::showNumber(
                'display_count_on_home',
                ['value' => $data['display_count_on_home'],
                    'min'   => 0,
                    'max'   => 30,
                    'rand'  => $rand
                ]
            );
            echo "</td>";
        } else {
            echo "<td colspan='2'>&nbsp;</td>";
        }
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td><label for='dropdown_pdffont$rand'>" . __('PDF export font') . "</label></td><td>";
        Dropdown::showFromArray(
            "pdffont",
            GLPIPDF::getFontList(),
            ['value' => $data["pdffont"],
                'width' => 200,
                'rand'  => $rand
            ]
        );
        echo "</td>";

        echo "<td><label for='dropdown_csv_delimiter$rand'>" . __('CSV delimiter') . "</label></td><td>";
        $values = [';' => ';',
            ',' => ','
        ];
        Dropdown::showFromArray('csv_delimiter', $values, ['value' => $data["csv_delimiter"], 'rand' => $rand]);

        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td><label for='theme-selector'>" . __("Color palette") . "</label></td><td>";
        echo Html::select(
            'palette',
            $this->getPalettes(),
            [
                'id'        => 'theme-selector',
                'selected'  => $data['palette']
            ]
        );
        echo Html::scriptBlock("
         function formatThemes(theme) {
             if (!theme.id) {
                return theme.text;
             }

             return $('<span></span>').html('<img src=\'../css/palettes/previews/' + theme.text.toLowerCase() + '.png\'/>'
                      + '&nbsp;' + theme.text);
         }
         $(\"#theme-selector\").select2({
             templateResult: formatThemes,
             templateSelection: formatThemes,
             width: '100%',
             escapeMarkup: function(m) { return m; }
         });
         $('label[for=theme-selector]').on('click', function(){ $('#theme-selector').select2('open'); });
      ");
        echo "</td>";
        echo "<td>";

        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td><label for='dropdown_page_layout$rand'>" . __('Page layout') . "</label></td>";
        echo "<td>";

        $global_layout_options = [
            'horizontal' => __('Horizontal (menu in header)'),
            'vertical'   => __('Vertical (menu in sidebar)'),
        ];
        echo Html::select(
            'page_layout',
            $global_layout_options,
            [
                'id'        => 'global-layout-selector',
                'selected'  => $data['page_layout']
            ]
        );

        echo Html::scriptBlock("
         function formatGlobalLayout(layout) {
             if (!layout.id) {
                return layout.text;
             }
             return $('<span></span>').html('<img src=\'../pics/layout/global_layout_' + layout.id.toLowerCase() + '.png\'/>'
                      + '&nbsp;' + layout.text);
         }
         $('#global-layout-selector').select2({
             dropdownAutoWidth: true,
             templateResult: formatGlobalLayout,
             templateSelection: formatGlobalLayout
         });
         $('label[for=global-layout-selector]').on('click', function(){
            $('#global-layout-selector').select2('open');
         });
      ");
        echo "</td>";

        echo "<td><label for='dropdown_richtext_layout$rand'>" . __('Rich text field layout') . "</label></td>";
        echo "<td>";
        Dropdown::showFromArray(
            'richtext_layout',
            [
                'inline'  => __('Inline (no toolbars)'),
                'classic' => __('Classic (toolbar on top)'),
            ],
            [
                'value' => $data["richtext_layout"],
            ]
        );
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'><td><label for='dropdown_highcontrast_css$rand'>" . __('Enable high contrast') . "</label></td>";
        echo "<td>";
        Dropdown::showYesNo('highcontrast_css', $data['highcontrast_css'], -1, ['rand' => $rand]);
        echo "</td>";
        echo "<td><label for='dropdown_timezone$rand'>" . __('Timezone') . "</label></td>";
        echo "<td>";
        if ($DB->use_timezones) {
            $timezones = $DB->getTimezones();
            Dropdown::showFromArray(
                'timezone',
                $timezones,
                [
                    'value'                 => $data["timezone"] ?? "",
                    'display_emptychoice'   => true,
                    'emptylabel'            => __('Use server configuration')
                ]
            );
        } else {
            echo __('Timezone usage has not been activated.')
            . ' '
            . sprintf(__('Run the "%1$s" command to activate it.'), 'php bin/console database:enable_timezones');
        }

        echo "<tr class='tab_bg_2'><td><label for='dropdown_default_central_tab$rand'>" . __('Default central tab') . "</label></td>";
        echo "<td>";
        $central = new Central();
        Dropdown::showFromArray('default_central_tab', $central->getTabNameForItem($central, 0), ['value' => $data['default_central_tab'], 'rand' => $rand]);
        echo "</td>";

        echo "<td><label for='dropdown_timeline_order$rand'>" . __('Timeline order') . "</label></td>";
        echo "<td>";
        Dropdown::showFromArray('timeline_order', [
            CommonITILObject::TIMELINE_ORDER_NATURAL => __('Natural order (old items on top, recent on bottom)'),
            CommonITILObject::TIMELINE_ORDER_REVERSE => __('Reverse order (old items on bottom, recent on top)'),
        ], [
            'value' => $data['timeline_order'],
            'rand' => $rand
        ]);
        echo "</td>";
        echo "</tr>";

        if ($oncentral) {
            echo "<tr class='tab_bg_1'><th colspan='4'>" . __('Assistance') . "</th></tr>";

            echo "<tr class='tab_bg_2'>";
            echo "<td><label for='dropdown_followup_private$rand'>" . __('Private followups by default') . "</label></td><td>";
            Dropdown::showYesNo("followup_private", $data["followup_private"], -1, ['rand' => $rand]);
            echo "</td><td><label for='dropdown_show_jobs_at_login$rand'>" . __('Show new tickets on the home page') . "</label></td><td>";
            if (
                Session::haveRightsOr(
                    "ticket",
                    [Ticket::READMY, Ticket::READALL, Ticket::READASSIGN]
                )
            ) {
                Dropdown::showYesNo("show_jobs_at_login", $data["show_jobs_at_login"], -1, ['rand' => $rand]);
            } else {
                echo Dropdown::getYesNo(0);
            }
            echo " </td></tr>";

            echo "<tr class='tab_bg_2'><td><label for='dropdown_task_private$rand'>" . __('Private tasks by default') . "</label></td><td>";
            Dropdown::showYesNo("task_private", $data["task_private"], -1, ['rand' => $rand]);
            echo "</td><td><label for='dropdown_default_requesttypes_id$rand'>" . __('Request sources by default') . "</label></td><td>";
            RequestType::dropdown([
                'value'      => $data["default_requesttypes_id"],
                'name'       => "default_requesttypes_id",
                'condition'  => ['is_active' => 1, 'is_ticketheader' => 1],
                'rand'       => $rand
            ]);
            echo "</td></tr>";

            echo "<tr class='tab_bg_2'><td><label for='dropdown_task_state$rand'>" . __('Tasks state by default') . "</label></td><td>";
            Planning::dropdownState("task_state", $data["task_state"], true, ['rand' => $rand]);
            echo "</td><td><label for='dropdown_refresh_views$rand'>" . __('Automatically refresh data (tickets list, project kanban) in minutes.') . "</label></td><td>";
            Dropdown::showNumber('refresh_views', ['value' => $data["refresh_views"],
                'min'   => 1,
                'max'   => 30,
                'step'  => 1,
                'toadd' => [0 => __('Never')],
                'rand'  => $rand
            ]);
            echo "</td></tr>";

            echo "<tr class='tab_bg_2'><td><label for='dropdown_set_default_tech$rand'>" . __('Pre-select me as a technician when creating a ticket') .
              "</label></td><td>";
            if (!$userpref || Session::haveRight('ticket', Ticket::OWN)) {
                Dropdown::showYesNo("set_default_tech", $data["set_default_tech"], -1, ['rand' => $rand]);
            } else {
                echo Dropdown::getYesNo(0);
            }
            echo "</td><td><label for='dropdown_set_default_requester$rand'>" . __('Pre-select me as a requester when creating a ticket') . "</label></td><td>";
            if (!$userpref || Session::haveRight('ticket', CREATE)) {
                Dropdown::showYesNo("set_default_requester", $data["set_default_requester"], -1, ['rand' => $rand]);
            } else {
                echo Dropdown::getYesNo(0);
            }

            echo "<tr class='tab_bg_2'><td><label for='timeline_action_btn_layout$rand'>" . __('Action button layout') .
              "</label></td><td>";
            Dropdown::showFromArray('timeline_action_btn_layout', [
                self::TIMELINE_ACTION_BTN_MERGED => __('Merged'),
                self::TIMELINE_ACTION_BTN_SPLITTED => __('Splitted'),
            ], [
                'value' => $data['timeline_action_btn_layout'],
                'rand' => $rand
            ]);
            echo "</td><td></td></tr>";
            echo "</td></tr>";

            echo "<tr class='tab_bg_2'><td><label for='timeline_date_format$rand'>" . __('Timeline date display') .
            "</label></td><td>";
            Dropdown::showFromArray('timeline_date_format', [
                self::TIMELINE_RELATIVE_DATE => __('Relative'),
                self::TIMELINE_ABSOLUTE_DATE => __('Precise'),
            ], [
                'value' => $data['timeline_date_format'],
                'rand' => $rand
            ]);
            echo "</td><td></td></tr>";

            echo "<tr class='tab_bg_2'>";
            echo "<td>" . __('Priority colors') . "</td>";
            echo "<td colspan='3'>";

            echo "<table><tr>";
            echo "<td><label for='dropdown_priority_1$rand'>1</label>&nbsp;";
            Html::showColorField('priority_1', ['value' => $data["priority_1"], 'rand' => $rand]);
            echo "</td>";
            echo "<td><label for='dropdown_priority_2$rand'>2</label>&nbsp;";
            Html::showColorField('priority_2', ['value' => $data["priority_2"], 'rand' => $rand]);
            echo "</td>";
            echo "<td><label for='dropdown_priority_3$rand'>3</label>&nbsp;";
            Html::showColorField('priority_3', ['value' => $data["priority_3"], 'rand' => $rand]);
            echo "</td>";
            echo "<td><label for='dropdown_priority_4$rand'>4</label>&nbsp;";
            Html::showColorField('priority_4', ['value' => $data["priority_4"], 'rand' => $rand]);
            echo "</td>";
            echo "<td><label for='dropdown_priority_5$rand'>5</label>&nbsp;";
            Html::showColorField('priority_5', ['value' => $data["priority_5"], 'rand' => $rand]);
            echo "</td>";
            echo "<td><label for='dropdown_priority_6$rand'>6</label>&nbsp;";
            Html::showColorField('priority_6', ['value' => $data["priority_6"], 'rand' => $rand]);
            echo "</td>";
            echo "</tr></table>";

            echo "</td></tr>";
        }

        echo "<tr><th colspan='4'>" . __('Due date progression') . "</th></tr>";

        echo "<tr class='tab_bg_1'>" .
           "<td>" . __('OK state color') . "</td>";
        echo "<td>";
        Html::showColorField('duedateok_color', ['value' => $data["duedateok_color"]]);
        echo "</td><td colspan='2'>&nbsp;</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Warning state color') . "</td>";
        echo "<td>";
        Html::showColorField('duedatewarning_color', ['value' => $data["duedatewarning_color"]]);
        echo "</td>";
        echo "<td>" . __('Warning state threshold') . "</td>";
        echo "<td>";
        Dropdown::showNumber("duedatewarning_less", ['value' => $data['duedatewarning_less']]);
        $elements = ['%'     => '%',
            'hours' => _n('Hour', 'Hours', Session::getPluralNumber()),
            'days'  => _n('Day', 'Days', Session::getPluralNumber())
        ];
        echo "&nbsp;";
        Dropdown::showFromArray(
            "duedatewarning_unit",
            $elements,
            ['value' => $data['duedatewarning_unit']]
        );
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>" .
           "<td>" . __('Critical state color') . "</td>";
        echo "<td>";
        Html::showColorField('duedatecritical_color', ['value' => $data["duedatecritical_color"]]);
        echo "</td>";
        echo "<td>" . __('Critical state threshold') . "</td>";
        echo "<td>";
        Dropdown::showNumber("duedatecritical_less", ['value' => $data['duedatecritical_less']]);
        echo "&nbsp;";
        $elements = ['%'    => '%',
            'hours' => _n('Hour', 'Hours', Session::getPluralNumber()),
            'days'  => _n('Day', 'Days', Session::getPluralNumber())
        ];
        Dropdown::showFromArray(
            "duedatecritical_unit",
            $elements,
            ['value' => $data['duedatecritical_unit']]
        );
        echo "</td></tr>";

        if ($oncentral && $CFG_GLPI["lock_use_lock_item"]) {
            echo "<tr class='tab_bg_1'><th colspan='4' class='center b'>" . __('Item locks') . "</th></tr>";

            echo "<tr class='tab_bg_2'>";
            echo "<td>" . __('Auto-lock Mode') . "</td><td>";
            Dropdown::showYesNo("lock_autolock_mode", $data["lock_autolock_mode"]);
            echo "</td><td>" . __('Direct Notification (requester for unlock will be the notification sender)') .
              "</td><td>";
            Dropdown::showYesNo("lock_directunlock_notification", $data["lock_directunlock_notification"]);
            echo "</td></tr>";
        }

        if (Grid::canViewOneDashboard()) {
            echo "<tr class='tab_bg_1'><th colspan='4' class='center b'>" . __('Dashboards') . "</th></tr>";

            echo "<tr class='tab_bg_2'>";
            echo "<td>" . __('Default for central') . "</td><td>";
            Grid::dropdownDashboard("default_dashboard_central", [
                'value' => $data['default_dashboard_central'],
                'display_emptychoice' => true
            ]);
            echo "</td><td>" . __('Default for Assets') .
             "</td><td>";
            Grid::dropdownDashboard("default_dashboard_assets", [
                'value' => $data['default_dashboard_assets'],
                'display_emptychoice' => true
            ]);
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('Default for Assistance') . "</td><td>";
            Grid::dropdownDashboard("default_dashboard_helpdesk", [
                'value' => $data['default_dashboard_helpdesk'],
                'display_emptychoice' => true
            ]);
            echo "</td><td>" . __('Default for tickets (mini dashboard)') .
             "</td><td>";
            Grid::dropdownDashboard("default_dashboard_mini_ticket", [
                'value' => $data['default_dashboard_mini_ticket'],
                'display_emptychoice' => true,
                'context'   => 'mini_core',
            ], true);
            echo "</td></tr>";
        }

        if ((!$userpref && $canedit) || ($userpref && $canedituser)) {
            echo "<tr class='tab_bg_2'>";
            echo "<td colspan='4' class='center'>";
            echo "<input type='submit' name='update' class='btn btn-primary' value=\"" . _sx('button', 'Save') . "\">";
            echo "</td></tr>";
        }

        echo "</table></div>";
        Html::closeForm();
    }

    /**
     * Check if the "use_password_security" parameter is enabled
     *
     * @return bool
     */
    public static function arePasswordSecurityChecksEnabled(): bool
    {
        /** @var array $CFG_GLPI */
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
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $needs = [];

        if ($CFG_GLPI["use_password_security"]) {
            printf(
                __('%1$s: %2$s'),
                __('Password minimum length'),
                "<span id='password_min_length' class='red'>" . $CFG_GLPI['password_min_length'] .
                "</span>"
            );
        }

        echo "<script type='text/javascript' >\n";
        echo "function passwordCheck() {\n";
        if ($CFG_GLPI["use_password_security"]) {
            echo "var pwd = " . Html::jsGetElementbyID($field) . ";";
            echo "if (pwd.val().length < " . $CFG_GLPI['password_min_length'] . ") {
               " . Html::jsGetElementByID('password_min_length') . ".addClass('red');
               " . Html::jsGetElementByID('password_min_length') . ".removeClass('green');
         } else {
               " . Html::jsGetElementByID('password_min_length') . ".addClass('green');
               " . Html::jsGetElementByID('password_min_length') . ".removeClass('red');
         }";
            if ($CFG_GLPI["password_need_number"]) {
                $needs[] = "<span id='password_need_number' class='red'>" . __('Digit') . "</span>";
                echo "var numberRegex = new RegExp('[0-9]', 'g');
            if (false == numberRegex.test(pwd.val())) {
                  " . Html::jsGetElementByID('password_need_number') . ".addClass('red');
                  " . Html::jsGetElementByID('password_need_number') . ".removeClass('green');
            } else {
                  " . Html::jsGetElementByID('password_need_number') . ".addClass('green');
                  " . Html::jsGetElementByID('password_need_number') . ".removeClass('red');
            }";
            }
            if ($CFG_GLPI["password_need_letter"]) {
                $needs[] = "<span id='password_need_letter' class='red'>" . __('Lowercase') . "</span>";
                echo "var letterRegex = new RegExp('[a-z]', 'g');
            if (false == letterRegex.test(pwd.val())) {
                  " . Html::jsGetElementByID('password_need_letter') . ".addClass('red');
                  " . Html::jsGetElementByID('password_need_letter') . ".removeClass('green');
            } else {
                  " . Html::jsGetElementByID('password_need_letter') . ".addClass('green');
                  " . Html::jsGetElementByID('password_need_letter') . ".removeClass('red');
            }";
            }
            if ($CFG_GLPI["password_need_caps"]) {
                $needs[] = "<span id='password_need_caps' class='red'>" . __('Uppercase') . "</span>";
                echo "var capsRegex = new RegExp('[A-Z]', 'g');
            if (false == capsRegex.test(pwd.val())) {
                  " . Html::jsGetElementByID('password_need_caps') . ".addClass('red');
                  " . Html::jsGetElementByID('password_need_caps') . ".removeClass('green');
            } else {
                  " . Html::jsGetElementByID('password_need_caps') . ".addClass('green');
                  " . Html::jsGetElementByID('password_need_caps') . ".removeClass('red');
            }";
            }
            if ($CFG_GLPI["password_need_symbol"]) {
                $needs[] = "<span id='password_need_symbol' class='red'>" . __('Symbol') . "</span>";
                echo "var capsRegex = new RegExp('[^a-zA-Z0-9_]', 'g');
            if (false == capsRegex.test(pwd.val())) {
                  " . Html::jsGetElementByID('password_need_symbol') . ".addClass('red');
                  " . Html::jsGetElementByID('password_need_symbol') . ".removeClass('green');
            } else {
                  " . Html::jsGetElementByID('password_need_symbol') . ".addClass('green');
                  " . Html::jsGetElementByID('password_need_symbol') . ".removeClass('red');
            }";
            }
        }
        echo "}";
        echo '</script>';
        if (count($needs)) {
            echo "<br>";
            printf(__('%1$s: %2$s'), __('Password must contains'), implode(', ', $needs));
        }
    }


    /**
     * Validate password based on security rules
     *
     * @since 0.84
     *
     * @param $password  string   password to validate
     * @param $display   boolean  display errors messages? (true by default)
     *
     * @throws PasswordTooWeakException when $display is false and the password does not matches the requirements
     *
     * @return boolean is password valid?
     **/
    public static function validatePassword($password, $display = true)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $ok = true;
        $exception = new \Glpi\Exception\PasswordTooWeakException();
        if ($CFG_GLPI["use_password_security"]) {
            if (Toolbox::strlen($password) < $CFG_GLPI['password_min_length']) {
                $ok = false;
                if ($display) {
                    Session::addMessageAfterRedirect(__('Password too short!'), false, ERROR);
                } else {
                    $exception->addMessage(__('Password too short!'));
                }
            }
            if (
                $CFG_GLPI["password_need_number"]
                && !preg_match("/[0-9]+/", $password)
            ) {
                $ok = false;
                if ($display) {
                    Session::addMessageAfterRedirect(
                        __('Password must include at least a digit!'),
                        false,
                        ERROR
                    );
                } else {
                    $exception->addMessage(__('Password must include at least a digit!'));
                }
            }
            if (
                $CFG_GLPI["password_need_letter"]
                && !preg_match("/[a-z]+/", $password)
            ) {
                $ok = false;
                if ($display) {
                    Session::addMessageAfterRedirect(
                        __('Password must include at least a lowercase letter!'),
                        false,
                        ERROR
                    );
                } else {
                    $exception->addMessage(__('Password must include at least a lowercase letter!'));
                }
            }
            if (
                $CFG_GLPI["password_need_caps"]
                && !preg_match("/[A-Z]+/", $password)
            ) {
                $ok = false;
                if ($display) {
                    Session::addMessageAfterRedirect(
                        __('Password must include at least a uppercase letter!'),
                        false,
                        ERROR
                    );
                } else {
                    $exception->addMessage(__('Password must include at least a uppercase letter!'));
                }
            }
            if (
                $CFG_GLPI["password_need_symbol"]
                && !preg_match("/\W+/", $password)
            ) {
                $ok = false;
                if ($display) {
                    Session::addMessageAfterRedirect(
                        __('Password must include at least a symbol!'),
                        false,
                        ERROR
                    );
                } else {
                    $exception->addMessage(__('Password must include at least a symbol!'));
                }
            }
        }
        if (!$ok && !$display) {
            throw $exception;
        }
        return $ok;
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

        echo "<div class='center' id='tabsbody'>";
        echo "<table class='tab_cadre_fixe'>";

        echo "<tr><th colspan='4'>" . __('PHP opcode cache') . "</th></tr>";
        $ext = 'Zend OPcache';
        if (extension_loaded($ext) && ($info = opcache_get_status(false))) {
            $msg = sprintf(__s('%s extension is installed'), $ext);
            echo "<tr><td>" . sprintf(__('The "%s" extension is installed'), $ext) . "</td>
               <td>" . phpversion($ext) . "</td>
               <td></td>
               <td class='icons_block'><i class='fa fa-check-circle ok' title='$msg'><span class='sr-only'>$msg</span></td></tr>";

           // Memory
            $used = $info['memory_usage']['used_memory'];
            $free = $info['memory_usage']['free_memory'];
            $rate = round(100.0 * $used / ($used + $free));
            $max  = Toolbox::getSize($used + $free);
            $used = Toolbox::getSize($used);
            echo "<tr><td>" . _n('Memory', 'Memories', 1) . "</td>
               <td>" . sprintf(__('%1$s / %2$s'), $used, $max) . "</td><td>";
            Html::displayProgressBar('100', $rate, ['simple'       => true,
                'forcepadding' => false
            ]);

            $class   = 'info-circle missing';
            $msg     = sprintf(__s('%1$s memory usage is too low or too high'), $ext);
            if ($rate > 5 && $rate < 75) {
                $class   = 'check-circle ok';
                $msg     = sprintf(__s('%1$s memory usage is correct'), $ext);
            }
            echo "</td><td class='icons_block'><i title='$msg' class='fa fa-$class'></td></tr>";

           // Hits
            $hits = $info['opcache_statistics']['hits'];
            $miss = $info['opcache_statistics']['misses'];
            $max  = $hits + $miss;
            $rate = round($info['opcache_statistics']['opcache_hit_rate']);
            echo "<tr><td>" . __('Hits rate') . "</td>
               <td>" . sprintf(__('%1$s / %2$s'), $hits, $max) . "</td><td>";
            Html::displayProgressBar('100', $rate, ['simple'       => true,
                'forcepadding' => false
            ]);

            $class   = 'info-circle missing';
            $msg     = sprintf(__s('%1$s hits rate is low'), $ext);
            if ($rate > 90) {
                $class   = 'check-circle ok';
                $msg     = sprintf(__s('%1$s hits rate is correct'), $ext);
            }
            echo "</td><td class='icons_block'><i title='$msg' class='fa fa-$class'></td></tr>";

           // Restart (1 seems ok, can happen)
            $max = $info['opcache_statistics']['oom_restarts'];
            echo "<tr><td>" . __('Out of memory restart') . "</td>
               <td>$max</td><td>";

            $class   = 'info-circle missing';
            $msg     = sprintf(__s('%1$s restart rate is too high'), $ext);
            if ($max < 2) {
                $class   = 'check-circle ok';
                $msg     = sprintf(__s('%1$s restart rate is correct'), $ext);
            }
            echo "</td><td class='icons_block'><i title='$msg' class='fa fa-$class'></td></tr>";

            if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
                echo "<tr><td></td><td colspan='3'>";
                echo '<form method="POST" action="' . static::getFormURL() . '" class="d-inline">';
                echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
                echo Html::hidden('reset_opcache', ['value' => 1]);
                echo '<button type="submit" class="btn btn-primary">';
                echo __('Reset');
                echo '</button>';
                echo '</form>';
                echo "</td></tr>";
            }
        } else {
            $msg = sprintf(__s('%s extension is not present'), $ext);
            echo "<tr><td colspan='3'>" . sprintf(__('Installing and enabling the "%s" extension may improve GLPI performance'), $ext) . "</td>
               <td class='icons_block'><i class='fa fa-info-circle missing' title='$msg'></i><span class='sr-only'>$msg</span></td></tr>";
        }

        echo "<tr><th colspan='4'>" . __('User data cache') . "</th></tr>";
        echo '<tr><td class="b">' . __('You can use "php bin/console cache:configure" command to configure cache system.') . '</td></tr>';
        $cache_manager = new CacheManager();
        $ext = strtolower(get_class($cache_manager->getCacheStorageAdapter(CacheManager::CONTEXT_CORE)));
        $ext = preg_replace('/^.*\\\([a-z]+?)(?:adapter)?$/', '$1', $ext);
        if (in_array($ext, ['memcached', 'redis'])) {
            $msg = sprintf(__s('The "%s" cache extension is installed'), $ext);
        } else {
            $msg = sprintf(__s('"%s" cache system is used'), $ext);
        }
        echo "<tr><td>" . $msg . "</td>
            <td>" . phpversion($ext) . "</td>
            <td></td>
            <td class='icons_block'><i class='fa fa-check-circle ok' title='$msg'></i><span class='sr-only'>$msg</span></td></tr>";

        if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
            echo "<tr><td></td><td colspan='3'>";
            echo '<form method="POST" action="' . static::getFormURL() . '" class="d-inline">';
            echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
            echo Html::hidden('reset_core_cache', ['value' => 1]);
            echo '<button type="submit" class="btn btn-primary">';
            echo __('Reset');
            echo '</button>';
            echo '</form>';
            echo "</td></tr>";
        }

        echo "<tr><th colspan='4'>" . __('Translation cache') . "</th></tr>";
        $adapter_class = strtolower(get_class($cache_manager->getCacheStorageAdapter(CacheManager::CONTEXT_TRANSLATIONS)));
        $adapter = preg_replace('/^.*\\\([a-z]+?)(?:adapter)?$/', '$1', $adapter_class);
        $msg = sprintf(__s('"%s" cache system is used'), $adapter);
        echo "<tr><td colspan='3'>" . $msg . "</td>
            <td class='icons_block'><i class='fa fa-check-circle ok' title='$msg'></i><span class='sr-only'>$msg</span></td></tr>";

        if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
            echo "<tr><td></td><td colspan='3'>";
            echo '<form method="POST" action="' . static::getFormURL() . '" style="d-inline">';
            echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
            echo Html::hidden('reset_translation_cache', ['value' => 1]);
            echo '<button type="submit" class="btn btn-primary">';
            echo __('Reset');
            echo '</button>';
            echo '</form>';
            echo "</td></tr>";
        }

        echo "</table></div>";
    }

    public static function showSystemInfoTable($params = [])
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $p = [
            'word_wrap_width' => 128
        ];
        $p = array_replace($p, $params);

        echo "<table id='system-info-table' class='tab_cadre_fixe'>";
        echo "<tr><th class='section-header'>" . __('Information about system installation and configuration') . "</th></tr>";
        echo "<tr class='tab_bg_1'><td></td></tr>";

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

        echo "<tr class='tab_bg_1'><td><pre class='section-content'>";
        echo "GLPI $ver (" . $CFG_GLPI['root_doc'] . " => " . GLPI_ROOT . ")\n";
        echo "Installation mode: " . GLPI_INSTALL_MODE . "\n";
        echo "Current language:" . $oldlang . "\n";
        echo "\n</pre></td></tr>";

        echo "<tr><th class='section-header'>Server</th></tr>\n";
        echo "<tr class='tab_bg_1'><td><pre class='section-content'>\n&nbsp;\n";
        echo wordwrap("Operating system: " . php_uname() . "\n", $p['word_wrap_width'], "\n\t");
        $exts = get_loaded_extensions();
        sort($exts);
        echo wordwrap(
            "PHP " . phpversion() . ' ' . php_sapi_name() . " (" . implode(', ', $exts) . ")\n",
            $p['word_wrap_width'],
            "\n\t"
        );
        $msg = "Setup: ";

        foreach (
            ['max_execution_time', 'memory_limit', 'post_max_size', 'safe_mode',
                'session.save_handler', 'upload_max_filesize', 'disable_functions'
            ] as $key
        ) {
            $msg .= $key . '="' . ini_get($key) . '" ';
        }
        echo wordwrap($msg . "\n", $p['word_wrap_width'], "\n\t");

        $msg = 'Software: ';
        if (isset($_SERVER["SERVER_SOFTWARE"])) {
            $msg .= $_SERVER["SERVER_SOFTWARE"];
        }
        if (isset($_SERVER["SERVER_SIGNATURE"])) {
            $msg .= ' (' . Toolbox::stripTags($_SERVER["SERVER_SIGNATURE"]) . ')';
        }
        echo wordwrap($msg . "\n", $p['word_wrap_width'], "\n\t");

        if (isset($_SERVER["HTTP_USER_AGENT"])) {
            echo "\t" . Sanitizer::encodeHtmlSpecialChars($_SERVER["HTTP_USER_AGENT"]) . "\n";
        }

        foreach ($DB->getInfo() as $key => $val) {
            echo "$key: $val\n\t";
        }
        echo "\n";

        $core_requirements = (new RequirementsManager())->getCoreRequirementList($DB);
       /* @var \Glpi\System\Requirement\RequirementInterface $requirement */
        foreach ($core_requirements as $requirement) {
            if ($requirement->isOutOfContext()) {
                continue; // skip requirement if not relevant
            }

            $img = $requirement->isValidated()
            ? 'ok'
            : ($requirement->isOptional() ? 'warning' : 'ko');
            $messages = Html::entities_deep($requirement->getValidationMessages());

            echo '<img src="' . $CFG_GLPI['root_doc'] . '/pics/' . $img . '_min.png"'
            . ' alt="' . implode(' ', $messages) . '" title="' . implode(' ', $messages) . '" />';
            echo implode("\n", $messages);

            echo "\n";
        }

        echo "\n</pre></td></tr>";

        echo "<tr><th class='section-header'>GLPI constants</th></tr>\n";
        echo "<tr class='tab_bg_1'><td><pre class='section-content'>\n&nbsp;\n";
        foreach (get_defined_constants() as $constant_name => $constant_value) {
            if (preg_match('/^GLPI_/', $constant_name)) {
                echo $constant_name . ': ' . json_encode($constant_value, JSON_UNESCAPED_SLASHES) . "\n";
            }
        }
        echo "\n</pre></td></tr>";

        self::showLibrariesInformation();

        foreach ($CFG_GLPI["systeminformations_types"] as $type) {
            $tmp = new $type();
            $tmp->showSystemInformations($p['word_wrap_width']);
        }

        Session::loadLanguage($oldlang);

        $files = array_merge(
            glob(GLPI_LOCAL_I18N_DIR . "/**/*.php"),
            glob(GLPI_LOCAL_I18N_DIR . "/**/*.mo")
        );
        sort($files);
        if (count($files)) {
            echo "<tr><th class='section-header'>Locales overrides</th></tr>\n";
            echo "<tr class='tab_bg_1'><td>\n";
            foreach ($files as $file) {
                echo "$file<br/>\n";
            }
            echo "</td></tr>";
        }

        echo "<tr class='tab_bg_2'><th>" . __('To copy/paste in your support request') . "</th></tr>\n";

        echo "</table>";
    }

    /**
     * Display a HTML report about systeme information / configuration
     **/
    public function showSystemInformations()
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        if (!Config::canUpdate()) {
            return false;
        }

        $rand = mt_rand();

        echo "<div class='center' id='tabsbody'>";
        echo "<form name='form' action=\"" . Toolbox::getItemTypeFormURL(__CLASS__) . "\" method='post' data-track-changes='true'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr><th colspan='4'>" . __('General setup') . "</th></tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td><label for='dropdown_event_loglevel$rand'>" . __('Log Level') . "</label></td><td>";

        $values = [
            1 => __('1- Critical (login error only)'),
            2 => __('2- Severe (not used)'),
            3 => __('3- Important (successful logins)'),
            4 => __('4- Notices (add, delete, tracking)'),
            5 => __('5- Complete (all)'),
        ];

        Dropdown::showFromArray(
            'event_loglevel',
            $values,
            ['value' => $CFG_GLPI["event_loglevel"], 'rand' => $rand]
        );
        echo "</td><td><label for='dropdown_cron_limit$rand'>" . __('Maximal number of automatic actions (run by CLI)') . "</label></td><td>";
        Dropdown::showNumber('cron_limit', ['value' => $CFG_GLPI["cron_limit"],
            'min'   => 1,
            'max'   => 30,
            'rand'  => $rand
        ]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td><label for='dropdown_use_log_in_files$rand'>" . __('Logs in files (SQL, email, automatic action...)') . "</label></td><td>";
        Dropdown::showYesNo("use_log_in_files", $CFG_GLPI["use_log_in_files"], -1, ['rand' => $rand]);
        echo "</td><td><label for='dropdown__dbslave_status$rand'>" . _n('SQL replica', 'SQL replicas', 1) . "</label></td><td>";
        $active = DBConnection::isDBSlaveActive();
        Dropdown::showYesNo("_dbslave_status", $active, -1, ['rand' => $rand]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td colspan='4' class='center b'>" . __('Maintenance mode');
        echo "</td></tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td><label for='dropdown_maintenance_mode$rand'>" . __('Maintenance mode') . "</label></td>";
        echo "<td>";
        Dropdown::showYesNo("maintenance_mode", $CFG_GLPI["maintenance_mode"], -1, ['rand' => $rand]);
        echo "</td>";
       //TRANS: Proxy port
        echo "<td><label for='maintenance_text'>" . __('Maintenance text') . "</label></td>";
        echo "<td>";
        echo "<textarea class='form-control' name='maintenance_text' id='maintenance_text'>" . $CFG_GLPI["maintenance_text"];
        echo "</textarea>";
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td colspan='4' class='center b'>" . __('Proxy configuration for upgrade check');
        echo "</td></tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td><label for='proxy_name'>" . __('Server') . "</label></td>";
        echo "<td><input type='text' name='proxy_name' id='proxy_name' value='" . $CFG_GLPI["proxy_name"] . "' class='form-control'></td>";
       //TRANS: Proxy port
        echo "<td><label for='proxy_port'>" . _n('Port', 'Ports', 1) . "</label></td>";
        echo "<td><input type='text' name='proxy_port' id='proxy_port' value='" . $CFG_GLPI["proxy_port"] . "' class='form-control'></td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td><label for='proxy_user'>" . __('Login') . "</label></td>";
        echo "<td><input type='text' name='proxy_user' id='proxy_user' value='" . $CFG_GLPI["proxy_user"] . "' class='form-control'></td>";
        echo "<td><label for='proxy_passwd'>" . __('Password') . "</label></td>";
        echo "<td><input type='password' name='proxy_passwd' id='proxy_passwd' value='' autocomplete='new-password' class='form-control'>";
        echo "<br><input type='checkbox' name='_blank_proxy_passwd' id='_blank_proxy_passwd'><label for='_blank_proxy_passwd'>" . __('Clear') . "</label>";
        echo "</td></tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td colspan='4' class='center'>";
        echo "<input type='submit' name='update' class='btn btn-primary' value=\"" . _sx('button', 'Save') . "\">";
        echo "</td></tr>";

        echo "</table>";
        Html::closeForm();
        $cleaner_script = <<<JS
        // Search all .section-content text content and Replace all instances of a '#' followed by a number so that there is a zero-width space between the # and the number
        $('.section-content').each(function() {
          $(this).html($(this).html().replace(/#(\d+)/g, '#\u200B$1'));
        });
JS;
        echo Html::scriptBlock($cleaner_script);


        echo "<p>" . Telemetry::getViewLink() . "</p>";

        $copy_msg = __('Copy system information');
        $copy_onclick = <<<JS
      copyTextToClipboard(tableToDetails('#system-info-table'));
      flashIconButton(this, 'btn btn-success', 'fas fa-check', 1500);
JS;
        echo <<<HTML
         <button type="button" name="copy-sysinfo" class="btn btn-secondary" onclick="{$copy_onclick}">
            <i class="far fa-copy me-2"></i>{$copy_msg}
         </button>
HTML;
        $check_new_version_msg = __('Check if a new version is available');
        echo <<<HTML
      <a class='btn btn-secondary' href='?check_version'>
         <i class="fas fa-sync me-2"></i>{$check_new_version_msg}
      </a>
HTML;
        self::showSystemInfoTable();
        echo "</div>\n";
    }


    /**
     * Retrieve full directory of a lib
     *
     * @param  $libstring  object, class or function
     *
     * @return false|string the path or false
     *
     * @since 9.1
     */
    public static function getLibraryDir($libstring)
    {
        if (is_object($libstring)) {
            return realpath(dirname((new ReflectionObject($libstring))->getFileName()));
        } else if (class_exists($libstring) || interface_exists($libstring)) {
            return realpath(dirname((new ReflectionClass($libstring))->getFileName()));
        } else if (function_exists($libstring)) {
           // Internal function have no file name
            $path = (new ReflectionFunction($libstring))->getFileName();
            return ($path ? realpath(dirname($path)) : false);
        }
        return false;
    }


    /**
     * get libraries list
     *
     * @param $all   (default false)
     * @return array dependencies list
     *
     * @since 9.4
     */
    public static function getLibraries($all = false)
    {
        $pm = new PHPMailer();
        $sp = new SimplePie();

       // use same name that in composer.json
        $deps = [[ 'name'    => 'htmlawed/htmlawed',
            'version' => hl_version() ,
            'check'   => 'hl_version'
        ],
            [ 'name'    => 'phpmailer/phpmailer',
                'version' => $pm::VERSION,
                'check'   => 'PHPMailer\\PHPMailer\\PHPMailer'
            ],
            [ 'name'    => 'simplepie/simplepie',
                'version' => SIMPLEPIE_VERSION,
                'check'   => $sp
            ],
            [ 'name'      => 'tecnickcom/tcpdf',
                'version' => TCPDF_STATIC::getTCPDFVersion(),
                'check'   => 'TCPDF'
            ],
            [ 'name'    => 'michelf/php-markdown',
                'check'   => 'Michelf\\Markdown'
            ],
            [ 'name'    => 'true/punycode',
                'check'   => 'TrueBV\\Punycode'
            ],
            [ 'name'    => 'iamcal/lib_autolink',
                'check'   => 'autolink'
            ],
            [ 'name'    => 'sabre/dav',
                'check'   => 'Sabre\\DAV\\Version'
            ],
            [ 'name'    => 'sabre/http',
                'check'   => 'Sabre\\HTTP\\Version'
            ],
            [ 'name'    => 'sabre/uri',
                'check'   => 'Sabre\\Uri\\Version'
            ],
            [ 'name'    => 'sabre/vobject',
                'check'   => 'Sabre\\VObject\\Component'
            ],
            [ 'name'    => 'laminas/laminas-i18n',
                'check'   => 'Laminas\\I18n\\Module'
            ],
            [ 'name'    => 'laminas/laminas-servicemanager',
                'check'   => 'Laminas\\ServiceManager\\ServiceManager'
            ],
            [ 'name'    => 'monolog/monolog',
                'check'   => 'Monolog\\Logger'
            ],
            [ 'name'    => 'sebastian/diff',
                'check'   => 'SebastianBergmann\\Diff\\Diff'
            ],
            [ 'name'    => 'donatj/phpuseragentparser',
                'check'   => 'donatj\\UserAgent\\UserAgentParser'
            ],
            [ 'name'    => 'elvanto/litemoji',
                'check'   => 'LitEmoji\\LitEmoji'
            ],
            [ 'name'    => 'symfony/console',
                'check'   => 'Symfony\\Component\\Console\\Application'
            ],
            [ 'name'    => 'scssphp/scssphp',
                'check'   => 'ScssPhp\ScssPhp\Compiler'
            ],
            [ 'name'    => 'laminas/laminas-mail',
                'check'   => 'Laminas\\Mail\\Protocol\\Imap'
            ],
            [ 'name'    => 'laminas/laminas-mime',
                'check'   => 'Laminas\\Mime\\Mime'
            ],
            [ 'name'    => 'rlanvin/php-rrule',
                'check'   => 'RRule\\RRule'
            ],
            [ 'name'    => 'ramsey/uuid',
                'check'   => 'Ramsey\\Uuid\\Uuid'
            ],
            [ 'name'    => 'psr/log',
                'check'   => 'Psr\\Log\\LoggerInterface'
            ],
            [ 'name'    => 'psr/simple-cache',
                'check'   => 'Psr\\SimpleCache\\CacheInterface'
            ],
            [ 'name'    => 'psr/cache',
                'check'   => 'Psr\\Cache\\CacheItemPoolInterface'
            ],
            [ 'name'    => 'league/csv',
                'check'   => 'League\\Csv\\Writer'
            ],
            [ 'name'    => 'mexitek/phpcolors',
                'check'   => 'Mexitek\\PHPColors\\Color'
            ],
            [ 'name'    => 'guzzlehttp/guzzle',
                'check'   => 'GuzzleHttp\\Client'
            ],
            [ 'name'    => 'guzzlehttp/psr7',
                'check'   => 'GuzzleHttp\\Psr7\\Response'
            ],
            [ 'name'    => 'glpi-project/inventory_format',
                'check'   => 'Glpi\Inventory\Converter'
            ],
            [ 'name'    => 'wapmorgan/unified-archive',
                'check'   => 'wapmorgan\\UnifiedArchive\\UnifiedArchive'
            ],
            [ 'name'    => 'paragonie/sodium_compat',
                'check'   => 'ParagonIE_Sodium_Compat'
            ],
            [ 'name'    => 'symfony/cache',
                'check'   => 'Symfony\\Component\\Cache\\Psr16Cache'
            ],
            [ 'name'    => 'html2text/html2text',
                'check'   => 'Html2Text\\Html2Text'
            ],
            [
                'name'    => 'symfony/css-selector',
                'check'   => 'Symfony\\Component\\CssSelector\\CssSelectorConverter'
            ],
            [ 'name'    => 'symfony/dom-crawler',
                'check'   => 'Symfony\\Component\\DomCrawler\\Crawler'
            ],
            [ 'name'    => 'twig/twig',
                'check'   => 'Twig\\Environment'
            ],
            [ 'name'    => 'twig/string-extra',
                'check'   => 'Twig\\Extra\\String\\StringExtension'
            ],
            [ 'name'    => 'symfony/polyfill-ctype',
                'check'   => 'ctype_digit'
            ],
            [ 'name'    => 'symfony/polyfill-iconv',
                'check'   => 'iconv'
            ],
            [ 'name'    => 'symfony/polyfill-mbstring',
                'check'   => 'mb_list_encodings'
            ],
            [ 'name'    => 'symfony/polyfill-php80',
                'check'   => 'str_contains'
            ],
            [
                'name'  => 'symfony/polyfill-php81',
                'check' => 'array_is_list'
            ],
            [
                'name'  => 'symfony/polyfill-php82',
                'check' => 'Symfony\\Polyfill\\Php82\\SensitiveParameterValue'
            ],
            [
                'name'  => 'league/oauth2-client',
                'check' => 'League\\OAuth2\\Client\\Provider\\AbstractProvider'
            ],
            [
                'name'  => 'league/oauth2-google',
                'check' => 'League\\OAuth2\\Client\\Provider\\Google'
            ],
            [
                'name'  => 'thenetworg/oauth2-azure',
                'check' => 'TheNetworg\\OAuth2\\Client\\Provider\\Azure'
            ],
        ];
        if (Toolbox::canUseCAS()) {
            $deps[] = [
                'name'    => 'phpCas',
                'version' => phpCAS::getVersion(),
                'check'   => 'phpCAS'
            ];
        }
        return $deps;
    }


    /**
     * show Libraries information in system information
     *
     * @since 0.84
     **/
    public static function showLibrariesInformation()
    {

       // No gettext

        echo "<tr class='tab_bg_2'><th class='section-header'>Libraries</th></tr>\n";
        echo "<tr class='tab_bg_1'><td><pre class='section-content'>\n&nbsp;\n";

        foreach (self::getLibraries() as $dep) {
            $path = self::getLibraryDir($dep['check']);
            if ($path) {
                echo "{$dep['name']} ";
                if (isset($dep['version'])) {
                    echo "version {$dep['version']} ";
                }
                echo "in ($path)\n";
            } else {
                echo "{$dep['name']} not found\n";
            }
        }

        echo "\n</pre></td></tr>";
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
        /** @var array $CFG_GLPI */
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


    public static function detectRootDoc()
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        if (isset($CFG_GLPI['root_doc'])) {
            return; // already computed
        }

        if (isset($_SERVER['REQUEST_URI'])) {
            // $_SERVER['REQUEST_URI'] is set, meaning that GLPI is accessed from web server.

            // In this case, `$CFG_GLPI['root_doc']` corresponds to the piece of path
            // that is common between `GLPI_ROOT` and $_SERVER['SCRIPT_NAME']
            // e.g. GLPI_ROOT=/var/www/glpi and $_SERVER['SCRIPT_NAME']=/glpi/front/index.php -> $CFG_GLPI['root_doc']=/glpi

            // We cannot rely on $_SERVER['REQUEST_URI'] value as it is a value defined by HTTP client.
            // $_SERVER['SCRIPT_NAME'] is consider safe as it is either set by GLPI router (see `/public/index.php`),
            // either it contains the path of PHP script executed by the web server.
            $script_path = $_SERVER['SCRIPT_NAME'];

            // Extract relative path of entry script directory
            // e.g. /var/www/mydomain.org/glpi/front/index.php -> /front
            $current_dir_relative = str_replace(
                str_replace(DIRECTORY_SEPARATOR, '/', realpath(GLPI_ROOT)),
                '',
                str_replace(DIRECTORY_SEPARATOR, '/', realpath(getcwd()))
            );

            // Extract relative path of script directory
            // e.g. /glpi/front/index.php -> /glpi/front
            $script_dir_relative = preg_replace(
                '/\/[0-9a-zA-Z\.\-\_]+\.php/',
                '',
                $script_path
            );
            // API exception (handles `RewriteRule api/(.*)$ apirest.php/$1`)
            if (strpos($script_dir_relative, 'api/') !== false) {
                $script_dir_relative = preg_replace("/(.*\/)api\/.*/", "$1", $script_dir_relative);
            }

            // Remove relative path of entry script directory
            // e.g. /glpi/front -> /glpi
            $root_doc = str_replace($current_dir_relative, '', $script_dir_relative);
            $root_doc = rtrim($root_doc, '/');

            $CFG_GLPI['root_doc'] = $root_doc;
        } else {
            // $_SERVER['REQUEST_URI'] is not set, meaning that GLPI is probably acces from CLI.
            // In this case, `$CFG_GLPI['root_doc']` has to be extracted from `$CFG_GLPI['url_base']`.

            $url_base = $CFG_GLPI['url_base'] ?? null;
            // $CFG_GLPI may have not been loaded yet, load value form DB if `$CFG_GLPI['url_base']` is not set.
            if (
                $url_base === null
                && $DB instanceof DBmysql
                && $DB->connected
                // table/field may not exists in edge case (e.g. update from GLPI < 0.85)
                && $DB->tableExists('glpi_configs')
                && $DB->fieldExists('glpi_configs', 'context')
            ) {
                $url_base = Config::getConfigurationValue('core', 'url_base');
            }

            if ($url_base !== null) {
                $CFG_GLPI['root_doc'] = parse_url($url_base, PHP_URL_PATH) ?? '';
            }
        }

        // Path for icon of document type (web mode only)
        if (isset($CFG_GLPI['root_doc'])) {
            $CFG_GLPI['typedoc_icon_dir'] = $CFG_GLPI['root_doc'] . '/pics/icones';
        }
    }


    /**
     * Display debug information for dbslave
     **/
    public function showDebug()
    {

        $options = [
            'diff' => 0,
            'name' => '',
        ];
        NotificationEvent::debugEvent(new DBConnection(), $options);
    }


    /**
     * Display field unicity criterias form
     **/
    public function showFormFieldUnicity()
    {

        $unicity = new FieldUnicity();
        $unicity->showForm(1, -1);
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        switch (get_class($item)) {
            case Preference::class:
                return __('Personalization');

            case User::class:
                if (
                    User::canUpdate()
                    && $item->currentUserHaveMoreRightThan($item->getID())
                ) {
                    return __('Settings');
                }
                break;

            case self::class:
                $tabs = [
                    1 => __('General setup'),  // Display
                    2 => __('Default values'), // Prefs
                    3 => _n('Asset', 'Assets', Session::getPluralNumber()),
                    4 => __('Assistance'),
                    12 => __('Management'),
                ];
                if (Config::canUpdate()) {
                    $tabs[9]  = __('Logs purge');
                    $tabs[5]  = __('System');
                    $tabs[10] = __('Security');
                    $tabs[7]  = __('Performance');
                    $tabs[8]  = __('API');
                    $tabs[11] = Impact::getTypeName();
                }

                if (
                    DBConnection::isDBSlaveActive()
                    && Config::canUpdate()
                ) {
                    $tabs[6]  = _n('SQL replica', 'SQL replicas', Session::getPluralNumber());  // Slave
                }
                return $tabs;

            case 'GLPINetwork':
                return 'GLPI Network';

            case Impact::getType():
                return Impact::getTypeName();
        }
        return '';
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if ($item instanceof Preference) {
            $config = new self();
            $user   = new User();
            if ($user->getFromDB(Session::getLoginUserID())) {
                $user->computePreferences();
                $config->showFormUserPrefs($user->fields);
            }
        } else if ($item instanceof User) {
            $config = new self();
            $item->computePreferences();
            $config->showFormUserPrefs($item->fields);
        } else if ($item instanceof self) {
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
     * @return integer 2: missing extension,  1: missing optionnal extension, 0: OK,
     **/
    public static function displayCheckDbEngine($fordebug = false, $version = null)
    {
        /** @var array $CFG_GLPI */
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
            $img = "<img src='" . $CFG_GLPI['root_doc'] . "/pics/";
            $img .= ($error > 0 ? "ko_min" : "ok_min") . ".png' alt='$message' title='$message'/>";

            if ($fordebug) {
                echo $img . $message . "\n";
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
            /** @var \DBmysql $DB */
            global $DB;
            $raw = $DB->getVersion();
        }

        $server  = preg_match('/-MariaDB/', $raw) ? 'MariaDB' : 'MySQL';
        $version = preg_replace('/^((\d+\.?)+).*$/', '$1', $raw);

       // MySQL >= 5.7 || MariaDB >= 10.2
        $is_supported = $server === 'MariaDB'
         ? version_compare($version, '10.2', '>=')
         : version_compare($version, '5.7', '>=');

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
                    'required'  => true
                ],
                'fileinfo' => [
                    'required'  => true,
                    'class'     => 'finfo'
                ],
                'json'     => [
                    'required'  => true,
                    'function'  => 'json_encode'
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
            //to sync/connect from LDAP
                'ldap'       => [
                    'required'  => false,
                ],
            //to enhance perfs
                'Zend OPcache' => [
                    'required'  => false
                ],
            //for CAS lib
                'CAS'     => [
                    'required' => false,
                    'class'    => 'phpCAS'
                ],
                'exif' => [
                    'required'  => false
                ],
                'intl' => [
                    'required' => true
                ],
                'sodium' => [
                    'required' => false
                ]
            ];
        } else {
            $extensions_to_check = $list;
        }

        $report = [
            'error'     => 0,
            'good'      => [],
            'missing'   => [],
            'may'       => []
        ];

       //check for PHP extensions
        foreach ($extensions_to_check as $ext => $params) {
            $success = true;

            if (isset($params['call'])) {
                $success = call_user_func($params['call']);
            } else if (isset($params['function'])) {
                if (!function_exists($params['function'])) {
                    $success = false;
                }
            } else if (isset($params['class'])) {
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
     * Get current DB version (compatible with all version of GLPI)
     *
     * @since 0.85
     *
     * @return string DB version
     **/
    public static function getCurrentDBVersion()
    {
        /** @var \DBmysql $DB */
        global $DB;

       //Default current case
        $select  = 'value AS version';
        $table   = 'glpi_configs';
        $where   = [
            'context'   => 'core',
            'name'      => 'version'
        ];

        if (!$DB->tableExists('glpi_configs')) {
            $select  = 'version';
            $table   = 'glpi_config';
            $where   = ['id' => 1];
        } else if ($DB->fieldExists('glpi_configs', 'version')) {
            $select  = 'version';
            $where   = ['id' => 1];
        }

        $row = $DB->request([
            'SELECT' => [$select],
            'FROM'   => $table,
            'WHERE'  => $where
        ])->current();

        return trim($row['version']);
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
        /** @var \DBmysql $DB */
        global $DB;

        $query = [
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'context'   => $context
            ]
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

        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $iterator = $DB->request(['FROM' => 'glpi_configs']);

        if ($iterator->count() === 0) {
            return false;
        }

        if ($iterator->count() === 1) {
           // 1 row = 0.78 to 0.84 config table schema
            $values = $iterator->current();
        } else {
           // multiple rows = 0.85+ config
            $values = [];
            $allowed_context = ['core', 'inventory'];
            foreach ($iterator as $row) {
                if (!in_array($row['context'], $allowed_context)) {
                    continue;
                }
                $values[$row['name']] = $row['value'];
            }
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

        return true;
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
                    'name'      => $name
                ])
            ) {
                $input = ['id'      => $config->getID(),
                    'context' => $context,
                    'value'   => $value
                ];

                $config->update($input);
            } else {
                $input = ['context' => $context,
                    'name'    => $name,
                    'value'   => $value
                ];

                $config->add($input);
            }
        }

        //reload config for loggedin user
        if ($_SESSION['glpiID'] ?? false) {
            $user = new \User();
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
                    'name'      => $value
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
        $msg = $is_dev
         ? __('You are using a development version, be careful!')
         : __('You are using a pre-release version, be careful!');

        $out = '<div class="alert alert-warning">
         <strong>' . $msg . '</strong>
         <br/>';
        $out .= "<div class='form-check'>
         <input type='checkbox' class='form-check-input' required='required' id='agree_unstable' name='agree_unstable'>
         <label for='agree_unstable' class='form-check-label'>" . __('I know I am using a unstable version.') . "</label>
      </div>
      </div>";
        $out .= "<script type=text/javascript>
            $(function() {
               $('[name=from_update]').on('click', function(event){
                  if(!$('#agree_unstable').is(':checked')) {
                     event.preventDefault();
                     alert('" . __('Please check the unstable version checkbox.') . "');
                  }
               });
            });
            </script>";
        return $out;
    }

    /**
     * Get available palettes
     *
     * @return array
     */
    public function getPalettes()
    {
        $themes_files = scandir(GLPI_ROOT . "/css/palettes/");
        $themes = [];
        foreach ($themes_files as $file) {
            if (preg_match('/^[^_].*\.scss$/', $file) === 1) {
                $name          = basename($file, '.scss');
                $themes[$name] = ucfirst($name);
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
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (!Config::canUpdate()) {
            return false;
        }

        echo "<form name='form' id='purgelogs_form' method='post' action='" . $this->getFormURL() . "' data-track-changes='true'>";
        echo "<div class='center'>";
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='tab_bg_1'><th colspan='4'>" . __("Logs purge configuration") .
           "</th></tr>";
        echo "<tr class='tab_bg_1 center'><td colspan='4'><i>" . __("Change all") . "</i>";
        echo Html::scriptBlock("function form_init_all(value) {
         $('#purgelogs_form .purgelog_interval select').val(value).trigger('change');;
      }");
        self::showLogsInterval(
            'init_all',
            0,
            [
                'on_change' => "form_init_all(this.value);",
                'class'     => ''
            ]
        );
        echo "</td></tr>";
        $config_id = self::getConfigIDForContext('core');
        echo "<input type='hidden' name='id' value='{$config_id}'>";

        echo "<tr class='tab_bg_1'><th colspan='4'>" . __("General") . "</th></tr>";
        echo "<tr class='tab_bg_1'><td class='center'>" . __("Add/update relation between items") .
           "</td><td>";
        self::showLogsInterval('purge_addrelation', $CFG_GLPI["purge_addrelation"]);
        echo "</td>";
        echo "<td>" . __("Delete relation between items") . "</td><td>";
        self::showLogsInterval('purge_deleterelation', $CFG_GLPI["purge_deleterelation"]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'><td class='center'>" . __("Add the item") . "</td><td>";
        self::showLogsInterval('purge_createitem', $CFG_GLPI["purge_createitem"]);
        echo "</td>";
        echo "<td>" . __("Delete the item") . "</td><td>";
        self::showLogsInterval('purge_deleteitem', $CFG_GLPI["purge_deleteitem"]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'><td class='center'>" . __("Restore the item") . "</td><td>";
        self::showLogsInterval('purge_restoreitem', $CFG_GLPI["purge_restoreitem"]);
        echo "</td>";

        echo "<td>" . __('Update the item') . "</td><td>";
        self::showLogsInterval('purge_updateitem', $CFG_GLPI["purge_updateitem"]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'><td class='center'>" . __("Comments") . "</td><td>";
        self::showLogsInterval('purge_comments', $CFG_GLPI["purge_comments"]);
        echo "</td>";
        echo "<td>" . __("Last update") . "</td><td>";
        self::showLogsInterval('purge_datemod', $CFG_GLPI["purge_datemod"]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'><td class='center'>" .
           __("Plugins") . "</td><td>";
        self::showLogsInterval('purge_plugins', $CFG_GLPI["purge_plugins"]);
        echo "</td>";
        echo "<td class='center'>" . RefusedEquipment::getTypeName(Session::getPluralNumber()) . "</td><td>";
        self::showLogsInterval('purge_refusedequipment', $CFG_GLPI["purge_refusedequipment"]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><th colspan='4'>" . _n('Software', 'Software', Session::getPluralNumber()) . "</th></tr>";
        echo "<tr class='tab_bg_1'><td class='center'>" .
           __("Installation/uninstallation of software on items") . "</td><td>";
        self::showLogsInterval(
            'purge_item_software_install',
            $CFG_GLPI["purge_item_software_install"]
        );
        echo "</td>";
        echo "<td>" . __("Installation/uninstallation versions on software") . "</td><td>";
        self::showLogsInterval(
            'purge_software_version_install',
            $CFG_GLPI["purge_software_version_install"]
        );
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'><td class='center'>" .
           __("Add/Remove items from software versions") . "</td><td>";
        self::showLogsInterval(
            'purge_software_item_install',
            $CFG_GLPI["purge_software_item_install"]
        );
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'><th colspan='4'>" . __('Financial and administrative information') .
           "</th></tr>";
        echo "<tr class='tab_bg_1'><td class='center'>" .
           __("Add financial information to an item") . "</td><td>";
        self::showLogsInterval('purge_infocom_creation', $CFG_GLPI["purge_infocom_creation"]);
        echo "</td>";
        echo "<td colspan='2'></td></tr>";

        echo "<tr class='tab_bg_1'><th colspan='4'>" . User::getTypeName(Session::getPluralNumber()) . "</th></tr>";

        echo "<tr class='tab_bg_1'><td class='center'>" .
           __("Add/remove profiles to users") . "</td><td>";
        self::showLogsInterval('purge_profile_user', $CFG_GLPI["purge_profile_user"]);
        echo "</td>";
        echo "<td>" . __("Add/remove groups to users") . "</td><td>";
        self::showLogsInterval('purge_group_user', $CFG_GLPI["purge_group_user"]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'><td class='center'>" .
           __("User authentication method changes") . "</td><td>";
        self::showLogsInterval('purge_user_auth_changes', $CFG_GLPI["purge_user_auth_changes"]);
        echo "</td>";
        echo "<td class='center'>" . __("Deleted user in LDAP directory") .
           "</td><td>";
        self::showLogsInterval('purge_userdeletedfromldap', $CFG_GLPI["purge_userdeletedfromldap"]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'><th colspan='4'>" . _n('Component', 'Components', Session::getPluralNumber()) . "</th></tr>";

        echo "<tr class='tab_bg_1'><td class='center'>" . __("Add component") . "</td><td>";
        self::showLogsInterval('purge_adddevice', $CFG_GLPI["purge_adddevice"]);
        echo "</td>";
        echo "<td>" . __("Update component") . "</td><td>";
        self::showLogsInterval('purge_updatedevice', $CFG_GLPI["purge_updatedevice"]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'><td class='center'>" . __("Disconnect a component") .
           "</td><td>";
        self::showLogsInterval('purge_disconnectdevice', $CFG_GLPI["purge_disconnectdevice"]);
        echo "</td>";
        echo "<td>" . __("Connect a component") . "</td><td>";
        self::showLogsInterval('purge_connectdevice', $CFG_GLPI["purge_connectdevice"]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'><td class='center'>" . __("Delete component") .
           "</td><td>";
        self::showLogsInterval('purge_deletedevice', $CFG_GLPI["purge_deletedevice"]);
        echo "</td>";
        echo "<td colspan='2'></td></tr>";

        echo "<tr class='tab_bg_1'><th colspan='4'>" . __("All sections") . "</th></tr>";

        echo "<tr class='tab_bg_1'><td class='center'>" . __("Purge all log entries") . "</td><td>";
        self::showLogsInterval('purge_all', $CFG_GLPI["purge_all"]);
        echo "</td>";
        echo "<td colspan='2'></td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td colspan='4' class='center'>";
        echo "<input type='submit' name='update' value=\"" . _sx('button', 'Save') . "\" class='btn btn-primary' >";
        echo"</td>";
        echo "</tr>";

        echo "</table></div>";
        Html::closeForm();
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
            'class'   => 'purgelog_interval'
        ], $options);

        $out = "<div class='{$options['class']}'>";
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
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (!Config::canUpdate()) {
            return false;
        }

        $rand = mt_rand();

        echo '<div class="center" id="tabsbody">';
        echo '<form name="form" action="' . Toolbox::getItemTypeFormURL(__CLASS__) . '" method="post" data-track-changes="true">';
        echo '<table class="tab_cadre_fixe">';
        echo '<tr><th colspan="4">' . __('Security setup') . '</th></tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td colspan="4" class="center b">' . __('Password security policy') . '</td>';
        echo '</tr>';

        echo '<tr class="tab_bg_2">';
        echo '<td>';
        echo '<label for="dropdown_use_password_security' . $rand . '">';
        echo __('Password security policy validation');
        echo '</label>';
        echo '</td>';
        echo '<td>';
        Dropdown::showYesNo(
            'use_password_security',
            $CFG_GLPI['use_password_security'],
            -1,
            [
                'rand' => $rand,
            ]
        );
        echo '</td>';
        echo '<td>';
        echo '<label for="dropdown_password_min_length' . $rand . '">';
        echo __('Password minimum length');
        echo '</label>';
        echo '</td>';
        echo '<td>';
        Dropdown::showNumber(
            'password_min_length',
            [
                'value' => $CFG_GLPI['password_min_length'],
                'min'   => 4,
                'max'   => 30,
                'rand'  => $rand
            ]
        );
        echo '</td>';
        echo '</tr>';

        echo '<tr class="tab_bg_2">';
        echo '<td>';
        echo '<label for="dropdown_password_need_number' . $rand . '">';
        echo __('Password need digit');
        echo '</label>';
        echo '</td>';
        echo '<td>';
        Dropdown::showYesNo(
            'password_need_number',
            $CFG_GLPI['password_need_number'],
            -1,
            [
                'rand' => $rand,
            ]
        );
        echo '</td>';
        echo '<td>';
        echo '<label for="dropdown_password_need_letter' . $rand . '">';
        echo __('Password need lowercase character');
        echo '</label>';
        echo '</td>';
        echo '<td>';
        Dropdown::showYesNo(
            'password_need_letter',
            $CFG_GLPI['password_need_letter'],
            -1,
            [
                'rand' => $rand,
            ]
        );
        echo '</td>';
        echo '</tr>';

        echo '<tr class="tab_bg_2">';
        echo '<td>';
        echo '<label for="dropdown_password_need_caps' . $rand . '">';
        echo __('Password need uppercase character');
        echo '</label>';
        echo '</td>';
        echo '<td>';
        Dropdown::showYesNo(
            'password_need_caps',
            $CFG_GLPI['password_need_caps'],
            -1,
            [
                'rand' => $rand,
            ]
        );
        echo '</td>';
        echo '<td>';
        echo '<label for="dropdown_password_need_symbol' . $rand . '">';
        echo __('Password need symbol');
        echo '</label>';
        echo '</td>';
        echo '<td>';
        Dropdown::showYesNo(
            'password_need_symbol',
            $CFG_GLPI['password_need_symbol'],
            -1,
            [
                'rand' => $rand,
            ]
        );
        echo '</td>';
        echo '</tr>';

        echo '<tr class="tab_bg_1">';
        echo '<td colspan="4" class="center b">' . __('Password expiration policy') . '</td>';
        echo '</tr>';

        echo '<tr class="tab_bg_2">';
        echo '<td>';
        echo '<label for="dropdown_password_expiration_delay' . $rand . '">';
        echo __('Password expiration delay (in days)');
        echo '</label>';
        echo '</td>';
        echo '<td>';
        Dropdown::showNumber(
            'password_expiration_delay',
            [
                'value' => $CFG_GLPI['password_expiration_delay'],
                'min'   => 30,
                'max'   => 365,
                'step'  => 15,
                'toadd' => [-1 => __('Never')],
                'rand'  => $rand
            ]
        );
        echo '</td>';
        echo '<td>';
        echo '<label for="dropdown_password_expiration_notice' . $rand . '">';
        echo __('Password expiration notice time (in days)');
        echo '</label>';
        echo '</td>';
        echo '<td>';
        Dropdown::showNumber(
            'password_expiration_notice',
            [
                'value' => $CFG_GLPI['password_expiration_notice'],
                'min'   => 0,
                'max'   => 30,
                'step'  => 1,
                'toadd' => [-1 => __('Notification disabled')],
                'rand'  => $rand
            ]
        );
        echo '</td>';
        echo '</tr>';

        echo '<tr class="tab_bg_2">';
        echo '<td>';
        echo '<label for="dropdown_password_expiration_lock_delay' . $rand . '">';
        echo __('Delay before account deactivation (in days)');
        echo '</label>';
        echo '</td>';
        echo '<td>';
        Dropdown::showNumber(
            'password_expiration_lock_delay',
            [
                'value' => $CFG_GLPI['password_expiration_lock_delay'],
                'min'   => 0,
                'max'   => 30,
                'step'  => 1,
                'toadd' => [-1 => __('Do not deactivate')],
                'rand'  => $rand
            ]
        );
        echo '</td>';
        echo '<td colspan="2"></td>';
        echo '</tr>';

        echo '<tr class="tab_bg_2">';
        echo '<td colspan="4" class="center">';
        echo '<input type="submit" name="update" class="btn btn-primary" value="' . _sx('button', 'Save') . '">';
        echo '</td>';
        echo '</tr>';

        echo '</table>';
        Html::closeForm();
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
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (!self::canView()) {
            return;
        }

        $rand = mt_rand();
        $canedit = Session::haveRight(self::$rightname, UPDATE);

        echo '<div class="center" id="tabsbody">';
        if ($canedit) {
            echo '<form name="form" action="' . Toolbox::getItemTypeFormURL(__CLASS__) . '" method="post" data-track-changes="true">';
        }
        echo '<table class="tab_cadre_fixe">';
        echo '<tr><th colspan="4">' . __('Documents setup') . '</th></tr>';

        echo '<tr class="tab_bg_2">';
        echo '<td>';
        echo '<label for="document_max_size' . $rand . '">';
        echo __('Document files maximum size (Mio)');
        echo '</label>';
        echo '</td>';
        echo '<td>';
        echo Html::input('document_max_size', [
            'type' => 'number',
            'min'  => 1,
            'value' => $CFG_GLPI['document_max_size'],
            'id' => 'document_max_size' . $rand,
        ]);
        echo '</td>';
        echo '<td colspan="2"></td>';
        echo '</tr>';

        if ($canedit) {
            echo '<tr class="tab_bg_2">';
            echo '<td colspan="4" class="center">';
            echo '<input type="submit" name="update" class="btn btn-primary" value="' . _sx('button', 'Save') . '">';
            echo '</td>';
            echo '</tr>';
        }

        echo '</table>';

        if ($canedit) {
            Html::closeForm();
        }

        echo '</div>';
    }

    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'   => 'common',
            'name' => __('Characteristics')
        ];

        $tab[] = [
            'id'            => 1,
            'table'         => $this->getTable(),
            'field'         => 'value',
            'name'          => __('Value'),
            'massiveaction' => false
        ];

        return $tab;
    }

    public function getLogTypeID()
    {
        return [$this->getType(), 1];
    }

    public function post_addItem()
    {
        $this->logConfigChange($this->fields['context'], $this->fields['name'], (string)$this->fields['value'], '');
    }

    public function post_updateItem($history = true)
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;
        // Check if password expiration mechanism has been activated
        if (
            $this->fields['name'] == 'password_expiration_delay'
            && array_key_exists('value', $this->oldvalues)
            && (int)$this->oldvalues['value'] === -1
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

        // If the `devices_in_menu` option changed, we should regenerate the menu (unless we are in debug mode where it is always regenerated)
        if ($this->fields['name'] === 'devices_in_menu' && $_SESSION['glpi_use_mode'] !== Session::DEBUG_MODE) {
            $CFG_GLPI['devices_in_menu'] = json_decode($this->fields['value']) ?? [];
            Html::generateMenuSession(true);
        }

        if (array_key_exists('value', $this->oldvalues)) {
            $newvalue = (string)$this->fields['value'];
            $oldvalue = (string)$this->oldvalues['value'];

            if ($newvalue === $oldvalue) {
                return;
            }

            // avoid inserting truncated json in logs
            if (strlen($newvalue) > 255 && Toolbox::isJSON($newvalue)) {
                $newvalue = "{...}";
            }
            if (strlen($oldvalue) > 255 && Toolbox::isJSON($oldvalue)) {
                $oldvalue = "{...}";
            }

            $CFG_GLPI[$this->fields['name']] = $newvalue; // Ensure post update actions and hook that are using `$CFG_GLPI` will use the new value

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
        $this->logConfigChange($this->fields['context'], $this->fields['name'], '', (string)$this->fields['value']);
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
        Log::constructHistory($this, ['value' => $oldvalue], ['value' => $newvalue]);
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
        /** @var array $CFG_GLPI */
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
        /** @var array $CFG_GLPI */
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
        /** @var \DBmysql $DB */
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
}
