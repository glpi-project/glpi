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

namespace Glpi\Plugin;

use Glpi\Api\HL as HL_API;
use Glpi\Features\Kanban;

/**
 * @link https://glpi-developer-documentation.readthedocs.io/en/master/plugins/hooks.html
 */
class Hooks
{
    // File hooks
    /**
     * Add CSS file in the head of all non-anonymous pages.
     * @see HookManager::registerCSSFile()
     */
    public const ADD_CSS               = 'add_css';

    /**
     * Add classic JavaScript file in the head of all non-anonymous pages.
     * @see HookManager::registerJavascriptFile()
     */
    public const ADD_JAVASCRIPT        = 'add_javascript';

    /**
     * Add ESM JavaScript module in the head of all non-anonymous pages.
     */
    public const ADD_JAVASCRIPT_MODULE = 'add_javascript_module';

    /**
     * Add a header tag in the head of all non-anonymous pages.
     */
    public const ADD_HEADER_TAG        = 'add_header_tag';

    /**
     * Register one or more on-demand JavaScript files.
     *
     * On-demand JS files are loaded based on the `$CFG_GLPI['javascript']` array.
     * Example: `$PLUGIN_HOOKS[Hooks::JAVASCRIPT]['your_js_name'] = ['path/to/your/file.js'];`
     */
    public const JAVASCRIPT            = 'javascript';

    // File hooks for anonymous pages

    /**
     * Add CSS file in the head of all anonymous pages.
     */
    public const ADD_CSS_ANONYMOUS_PAGE               = 'add_css_anonymous_page';

    /**
     * Add classic JavaScript file in the head of all anonymous pages.
     */
    public const ADD_JAVASCRIPT_ANONYMOUS_PAGE        = 'add_javascript_anonymous_page';

    /**
     * Add ESM JavaScript module in the head of all anonymous pages.
     */
    public const ADD_JAVASCRIPT_MODULE_ANONYMOUS_PAGE = 'add_javascript_module_anonymous_page';

    /**
     * Add a header tag in the head of all anonymous pages.
     */
    public const ADD_HEADER_TAG_ANONYMOUS_PAGE        = 'add_header_tag_anonymous_page';


    // Function hooks with no parameters
    /**
     * Register a function to be called when the entity is changed.
     */
    public const CHANGE_ENTITY               = 'change_entity';

    /**
     * Register a function to be called when the profile is changed.
     */
    public const CHANGE_PROFILE              = 'change_profile';

    /**
     * Register a function to output some content on the login page.
     */
    public const DISPLAY_LOGIN               = 'display_login';

    /**
     * Register a function to output some content on the standard (central) or simplified interface (helpdesk) home page.
     * This hook is called inside a table element.
     */
    public const DISPLAY_CENTRAL             = 'display_central';

    /**
     * Register a function to output some content before the network port list.
     */
    public const DISPLAY_NETPORT_LIST_BEFORE = 'display_netport_list_before';

    /**
     * Register a function to be called when the session is initialized.
     */
    public const INIT_SESSION                = 'init_session';

    /**
     * Register a function to be called after all plugins are initialized.
     */
    public const POST_INIT                   = 'post_init';

    /**
     * Register a URL relative to the plugin's root URL for the plugin's config page.
     */
    public const CONFIG_PAGE                 = 'config_page';

    /**
     * Set to true if the plugin wants to use the {@link self::AUTO_MASSIVE_ACTIONS} hook.
     * Example: $PLUGIN_HOOKS[Hooks::USE_MASSIVE_ACTION]['myplugin'] = true;
     */
    public const USE_MASSIVE_ACTION          = 'use_massive_action';

    /**
     * Set to true if the plugin wants to use the {@link self::AUTO_ASSIGN_TO_TICKET} hook.
     * Example: $PLUGIN_HOOKS[Hooks::ASSIGN_TO_TICKET]['myplugin'] = true;
     */
    public const ASSIGN_TO_TICKET            = 'assign_to_ticket';

    /**
     * Set to true if the plugin can import items. Adds the plugin as a source criteria for 'Rules for assigning an item to an entity'
     */
    public const IMPORT_ITEM                 = 'import_item';

    // Specific function hooks with parameters
    /**
     * Register a function to be called when the rules engine matches a rule.
     * The function is called with an array containing several properties including:
     * - 'sub_type' => The subtype of the rule (Example: RuleTicket)
     * - 'ruleid' => The ID of the rule
     * - 'input' => The input data sent to the rule engine
     * - 'output' => The current output data
     * The function is not expected to return anything and the data provided to it cannot be modified.
     */
    public const RULE_MATCHED          = 'rule_matched';

    /**
     * Register a function to be called when a vCard is generated.
     * The function is called with an array containing several properties including:
     * - 'item' => The item for which the vCard is generated
     * - 'data' => The vCard data
     * The function is expected to modify the given array as needed and return it.
     */
    public const VCARD_DATA            = 'vcard_data';

    /**
     * Register a function to be called when the plugin is disabled.
     * The function is called with the plugin name as a parameter.
     */
    public const POST_PLUGIN_DISABLE   = 'post_plugin_disable';

    /**
     * Register a function to be called when the plugin is cleaned from the database.
     * The function is called with the plugin name as a parameter.
     */
    public const POST_PLUGIN_CLEAN     = 'post_plugin_clean';

    /**
     * Register a function to be called when the plugin is installed.
     * The function is called with the plugin name as a parameter.
     */
    public const POST_PLUGIN_INSTALL   = 'post_plugin_install';

    /**
     * Register a function to be called when the plugin is uninstalled.
     * The function is called with the plugin name as a parameter.
     */
    public const POST_PLUGIN_UNINSTALL = 'post_plugin_uninstall';

    /**
     * Register a function to be called when the plugin is enabled.
     * The function is called with the plugin name as a parameter.
     */
    public const POST_PLUGIN_ENABLE    = 'post_plugin_enable' ;

    // Function hooks with parameters and output
    /**
     * Register a function to be called to show locked fields managed by the plugin.
     * The function is called with an array containing several properties including:
     * - 'item' => The item for which the locked fields are shown
     * - 'header' => Always false. //TODO WHY!?
     */
    public const DISPLAY_LOCKED_FIELDS         = 'display_locked_fields';

    /**
     * Register a function to define content to show before the main content of a Kanban card.
     * This function is called with an array containing several properties including:
     * - 'itemtype' => The type of the item represented by the Kanban card
     * - 'items_id' => The ID of the item represented by the Kanban card
     * The function is expected to return HTML content.
     */
    public const PRE_KANBAN_CONTENT            = 'pre_kanban_content';

    /**
     * Register a function to define content to show after the main content of a Kanban card.
     * This function is called with an array containing several properties including:
     * - 'itemtype' => The type of the item represented by the Kanban card
     * - 'items_id' => The ID of the item represented by the Kanban card
     * The function is expected to return HTML content.
     */
    public const POST_KANBAN_CONTENT           = 'post_kanban_content';

    /**
     * Register a function to redefine metadata for a Kanban card.
     * This function is called with an array containing several properties including:
     * - 'itemtype' => The type of the item represented by the Kanban card
     * - 'items_id' => The ID of the item represented by the Kanban card
     * - 'metadata' => The current metadata for the Kanban card
     * The function is expected to modify the given array as needed and return it.
     */
    public const KANBAN_ITEM_METADATA          = 'kanban_item_metadata';

    /**
     * Define extra Kanban filters by itemtype.
     * Example:
     * ```
     * $PLUGIN_HOOKS[Hooks::KANBAN_FILTERS]['myplugin'] = [
     *     'Ticket' => [
     *         'new_metadata_property' => [
     *             'description' => 'My new property'
     *             'supported_prefixes' => ['!']
     *         ]
     *     ]
     * ]
     * ```
     */
    public const KANBAN_FILTERS                = 'kanban_filters';

    /**
     * Register a function to display content at the beginning of the item details panel in the Kanban.
     * The function is called with an array containing several properties including:
     * - 'itemtype' => The type of the item represented by the Kanban card
     * - 'items_id' => The ID of the item represented by the Kanban card
     * The function is expected to return HTML content.
     * @used-by templates/components/kanban/item_panels/default_panel.html.twig
     */
    public const PRE_KANBAN_PANEL_CONTENT      = 'pre_kanban_panel_content';

    /**
     * Register a function to display content at the end of the item details panel in the Kanban.
     * The function is called with an array containing several properties including:
     * - 'itemtype' => The type of the item represented by the Kanban card
     * - 'items_id' => The ID of the item represented by the Kanban card
     * The function is expected to return HTML content.
     * @used-by templates/components/kanban/item_panels/default_panel.html.twig
     */
    public const POST_KANBAN_PANEL_CONTENT     = 'post_kanban_panel_content';

    /**
     * Register a function to display content at the beginning of the item details panel in the Kanban after the content from {@link self::PRE_KANBAN_PANEL_CONTENT} but before the default main content.
     * The function is called with an array containing several properties including:
     * - 'itemtype' => The type of the item represented by the Kanban card
     * - 'items_id' => The ID of the item represented by the Kanban card
     * The function is expected to return HTML content.
     * @used-by templates/components/kanban/item_panels/default_panel.html.twig
     */
    public const PRE_KANBAN_PANEL_MAIN_CONTENT = 'pre_kanban_panel_main_content';

    /**
     * Register a function to display content at the end of the item details panel in the Kanban after the default main content but before the content from {@link self::POST_KANBAN_PANEL_CONTENT}.
     * The function is called with an array containing several properties including:
     *  - 'itemtype' => The type of the item represented by the Kanban card
     * - 'items_id' => The ID of the item represented by the Kanban card
     * The function is expected to return HTML content.
     * @used-by templates/components/kanban/item_panels/default_panel.html.twig
     */
    public const POST_KANBAN_PANEL_MAIN_CONTENT = 'post_kanban_panel_main_content';

    /**
     * Register a function to redefine the GLPI menu.
     * The function is called with the current menu as a parameter.
     * The function is expected to modify the given array as needed and return it.
     * @see Html::generateMenuSession()
     */
    public const REDEFINE_MENUS                = 'redefine_menus';

    /**
     * Register a function to get more user field data from LDAP.
     * The function is called with an array containing the current fields for the user along with:
     * - '_ldap_result' => The LDAP query result
     * - '_ldap_conn' => The LDAP connection resource
     * The function is expected to modify the given array as needed and return it.
     */
    public const RETRIEVE_MORE_DATA_FROM_LDAP  = 'retrieve_more_data_from_ldap';
    public const RETRIEVE_MORE_FIELD_FROM_LDAP = 'retrieve_more_field_from_ldap';
    public const RESTRICT_LDAP_AUTH            = 'restrict_ldap_auth';
    public const UNLOCK_FIELDS                 = 'unlock_fields';
    public const UNDISCLOSED_CONFIG_VALUE      = 'undiscloseConfigValue';
    public const FILTER_ACTORS                 = 'filter_actors';
    public const DEFAULT_DISPLAY_PREFS         = 'default_display_prefs';
    public const USE_RULES                     = 'use_rules';

    // Item hooks expecting an 'item' parameter
    public const ADD_RECIPIENT_TO_TARGET   = 'add_recipient_to_target';
    public const AUTOINVENTORY_INFORMATION = 'autoinventory_information';
    public const INFOCOM                   = 'infocom';
    public const ITEM_ACTION_TARGETS       = 'item_action_targets';
    public const ITEM_ADD                  = 'item_add';
    public const ITEM_ADD_TARGETS          = 'item_add_targets';
    public const ITEM_CAN                  = 'item_can';
    public const ITEM_EMPTY                = 'item_empty';
    public const ITEM_DELETE               = 'item_delete';
    public const ITEM_GET_DATA             = 'item_get_datas';
    public const ITEM_GET_EVENTS           = 'item_get_events';
    public const ITEM_UPDATE               = 'item_update';
    public const ITEM_PURGE                = 'item_purge';
    public const ITEM_RESTORE              = 'item_restore';
    public const POST_PREPAREADD           = 'post_prepareadd';
    public const PRE_ITEM_ADD              = 'pre_item_add';
    public const PRE_ITEM_UPDATE           = 'pre_item_update';
    public const PRE_ITEM_DELETE           = 'pre_item_delete';
    public const PRE_ITEM_PURGE            = 'pre_item_purge';
    public const PRE_ITEM_RESTORE          = 'pre_item_restore';
    public const SHOW_ITEM_STATS           = 'show_item_stats';

    // Item hooks expecting an array parameter (available keys: item, options)
    /**
     * @used-by templates/components/itilobject/fields_panel.html.twig
     */
    public const PRE_ITIL_INFO_SECTION   = 'pre_itil_info_section';
    /**
     * @used-by templates/components/itilobject/fields_panel.html.twig
     */
    public const POST_ITIL_INFO_SECTION  = 'post_itil_info_section';
    public const ITEM_TRANSFER           = 'item_transfer';
    public const POST_ITEM_FORM          = 'post_item_form';
    public const POST_SHOW_ITEM          = 'post_show_item';
    public const POST_SHOW_TAB           = 'post_show_tab';
    public const POST_ITEM_LIST          = 'post_item_list';
    public const PRE_ITEM_FORM           = 'pre_item_form';
    public const PRE_SHOW_ITEM           = 'pre_show_item';
    public const PRE_SHOW_TAB            = 'pre_show_tab';
    public const PRE_ITEM_LIST           = 'pre_item_list';
    public const TIMELINE_ACTIONS        = 'timeline_actions';  // (keys: item, rand)
    public const TIMELINE_ANSWER_ACTIONS = 'timeline_answer_actions';  // (keys: item)
    /**
     * @deprecated 11.0.0 Use `TIMELINE_ITEMS` instead.
     */
    public const SHOW_IN_TIMELINE        = 'show_in_timeline';
    public const TIMELINE_ITEMS          = 'timeline_items';  // (keys: item)
    public const SET_ITEM_IMPACT_ICON    = 'set_item_impact_icon'; // (keys: itemtype, items_id)

    // Security hooks (data to encypt)
    /**
     * An array of database columns (example: glpi_mytable.myfield) that are stored using GLPI encrypting methods.
     * This allows plugin fields to be handled by the `glpi:security:changekey` command.
     * @since 9.4.6
     */
    public const SECURED_FIELDS  = 'secured_fields';

    /**
     * An array of configuration keys that are stored using GLPI encrypting methods.
     * This allows plugin configuration values to be handled by the `glpi:security:changekey` command.
     * @since 9.4.6
     */
    public const SECURED_CONFIGS = 'secured_configs';

    // Inventory hooks
    public const PROLOG_RESPONSE = 'prolog_response';
    public const NETWORK_DISCOVERY = 'network_discovery';
    public const NETWORK_INVENTORY = 'network_inventory';
    public const INVENTORY_GET_PARAMS = 'inventory_get_params';
    /** @var string Hook called before the inventory submission is handled.
     *              You may modify the inventory data which is passed as a parameter (stdClass) and return the modified data.
     *              Returning null will cancel the inventory submission with no specific reason.
     *              Throwing an Exception will cancel the inventory submission with the exception message as the reason.
     *              To avoid unrelated exception messages from being sent to the agent, you must handle all exceptions (except the one you would throw to cancel the inventory) within the hook function.
     */
    public const PRE_INVENTORY = 'pre_inventory';
    /** @var string Hook called after the inventory submission is handled.
     *              You may view the inventory data which is passed as a parameter (stdClass).
     *              Nothing is expected to be returned.
     *              This hook is only called if the inventory submission was successful.
     */
    public const POST_INVENTORY = 'post_inventory';

    // Agent contact request related hooks
    public const HANDLE_INVENTORY_TASK    = 'handle_inventory_task';
    public const HANDLE_NETDISCOVERY_TASK = 'handle_netdiscovery_task';
    public const HANDLE_NETINVENTORY_TASK = 'handle_netinventory_task';
    public const HANDLE_ESX_TASK          = 'handle_esx_task';
    public const HANDLE_COLLECT_TASK      = 'handle_collect_task';
    public const HANDLE_DEPLOY_TASK       = 'handle_deploy_task';
    public const HANDLE_WAKEONLAN_TASK    = 'handle_wakeonlan_task';
    public const HANDLE_REMOTEINV_TASK    = 'handle_remoteinventory_task';

    public const STALE_AGENT_CONFIG = 'stale_agent_config';

    // Menu Hooks
    public const MENU_TOADD = 'menu_toadd';
    public const HELPDESK_MENU_ENTRY = 'helpdesk_menu_entry';
    public const HELPDESK_MENU_ENTRY_ICON = 'helpdesk_menu_entry_icon';

    // Dashboard hooks
    public const DASHBOARD_CARDS    = 'dashboard_cards';
    public const DASHBOARD_FILTERS  = 'dashboard_filters';
    public const DASHBOARD_PALETTES = 'dashboard_palettes';
    public const DASHBOARD_TYPES    = 'dashboard_types';

    // HL API hooks
    /**
     * The hook function to call to redefine schemas.
     * Each time a controller's schemas are retrieved, the hook is called with a $data parameter.
     * The $data parameter will contain the Controller class name in the 'controller' key and an array of schemas in the 'schemas' key.
     * The function should return the modified $data array.
     * The controller value should not be changed as it would result in undefined behavior.
     */
    public const REDEFINE_API_SCHEMAS          = 'redefine_api_schemas';
    /**
     * This hook should provide an array of the plugin's API controller class names.
     */
    public const API_CONTROLLERS               = 'api_controllers';
    /**
     * This hook should provide an array of arrays containing a 'middlware' value that is the class name.
     * The middleware classes should extend {@link HL_API\Middleware\AbstractMiddleware} and
     * implement either {@link HL_API\Middleware\RequestMiddlewareInterface{ or {@link HL_API\Middleware\ResponseMiddlewareInterface}.
     * The arrays may also contain values for 'priority' and 'condition' where priority is an integer (higher is more important) and condition is a callable.
     * If a condition is provided, that callable will be called with the current controller as a parameter and it must return true for the middleware to be used, or false to not be.
     */
    public const API_MIDDLEWARE                = 'api_middleware';

    public const STATS = 'stats';
    public const MAIL_SERVER_PROTOCOLS = 'mail_server_protocols';
    public const ADD_DEFAULT_JOIN = 'add_default_join';
    public const ADD_DEFAULT_WHERE = 'add_default_where';

    // Function hooks that are currently automatically registered. Example: MassiveActions -> plugin_myplugin_MassiveActions

    /**
     * Automatic hook function called with the itemtype as a parameter and expects an array of massive action to be returned.
     * Only called if the plugin also uses the {@link self::USE_MASSIVE_ACTION} hook set to true.
     */
    public const AUTO_MASSIVE_ACTIONS = 'MassiveActions';

    public const AUTO_MASSIVE_ACTIONS_FIELDS_DISPLAY = 'MassiveActionsFieldsDisplay';

    public const AUTO_DYNAMIC_REPORT = 'dynamicReport';

    public const AUTO_ASSIGN_TO_TICKET = 'AssignToTicket';

    public const AUTO_GET_DROPDOWN = 'getDropdown';

    /**
     * Automatic hook function called with an array with the key 'rule_itemtype' set to the itemtype and 'values' set to the input sent to the rule engine.
     * The function is expected to return an array of criteria to add.
     * Only called if the plugin also uses the {@link self::USE_RULES} hook set to true.
     * @see Rule::getAllCriteria()
     */
    public const AUTO_GET_RULE_CRITERIA = 'getRuleCriteria';

    /**
     * Automatic hook function called with an array with the key 'rule_itemtype' set to the itemtype and 'values' set to the input sent to the rule engine.
     * The function is expected to return an array of actions to add.
     * Only called if the plugin also uses the {@link self::USE_RULES} hook set to true.
     * @see Rule::getAllActions()
     */
    public const AUTO_GET_RULE_ACTIONS = 'getRuleActions';

    /**
     * Only called if the plugin also uses the {@link self::USE_RULES} hook set to true.
     */
    public const AUTO_RULE_COLLECTION_PREPARE_INPUT_DATA_FOR_PROCESS = 'ruleCollectionPrepareInputDataForProcess';

    /**
     * Only called if the plugin also uses the {@link self::USE_RULES} hook set to true.
     */
    public const AUTO_PRE_PROCESS_RULE_COLLECTION_PREVIEW_RESULTS = 'preProcessRuleCollectionPreviewResults';

    /**
     * Automatic hook function called with an array containing several criteria including:
     * - 'where_entity' => the entity to restrict
     * - 'input' => the rule input
     * - 'criteria' => the rule criteria
     * - 'sql_where' => the SQL WHERE clause as a string
     * - 'sql_from' => the SQL FROM clause as a string
     * The function is expected to return the given array with any needed modifications.
     * Only called if the plugin also uses the {@link self::USE_RULES} hook set to true.
     */
    public const AUTO_RULEIMPORTASSET_GET_SQL_RESTRICTION = 'ruleImportAsset_getSqlRestriction';

    public const AUTO_RULEIMPORTASSET_ADD_GLOBAL_CRITERIA = 'ruleImportAsset_addGlobalCriteria';

    public const AUTO_SEARCH_OPTION_VALUES = 'searchOptionsValues';

    public const AUTO_DISPLAY_CONFIG_ITEM = 'displayConfigItem';

    public const AUTO_ADD_PARAM_FOR_DYNAMIC_REPORT = 'addParamFordynamicReport';

    public const AUTO_ADD_DEFAULT_JOIN = 'addDefaultJoin';

    public const AUTO_ADD_DEFAULT_SELECT = 'addDefaultSelect';

    public const AUTO_ADD_DEFAULT_WHERE = 'addDefaultWhere';

    public const AUTO_ADD_HAVING = 'addHaving';

    public const AUTO_ADD_LEFT_JOIN = 'addLeftJoin';

    public const AUTO_ADD_ORDER_BY = 'addOrderBy';

    public const AUTO_ADD_SELECT = 'addSelect';

    public const AUTO_ADD_WHERE = 'addWhere';

    public const AUTO_GIVE_ITEM = 'giveItem';

    /**
     * Automatic hook function to report status information through the GLPI status feature.
     * The function receives a parameter with the following keys:
     * - 'ok' => Always true
     * - '_public_only' => True if only non-sensitive/public information should be returned
     * The function is expected to return an array containing at least a 'status' key with a `StatusChecker::STATUS_*` value.
     * @link https://glpi-user-documentation.readthedocs.io/fr/latest/advanced/status.html
     */
    public const AUTO_STATUS = 'status';

    /**
     * Get file hooks
     *
     * @return array
     */
    public static function getFileHooks(): array
    {
        return [
            self::ADD_CSS,
            self::ADD_JAVASCRIPT,
            self::ADD_JAVASCRIPT_MODULE,
            self::JAVASCRIPT,
            self::ADD_CSS_ANONYMOUS_PAGE,
            self::ADD_JAVASCRIPT_ANONYMOUS_PAGE,
            self::ADD_JAVASCRIPT_MODULE_ANONYMOUS_PAGE,
            self::ADD_HEADER_TAG,
            self::ADD_HEADER_TAG_ANONYMOUS_PAGE,
        ];
    }

    /**
     * Get functionals hooks
     *
     * @return array
     */
    public static function getFunctionalHooks(): array
    {
        //TODO Function or functional? Not always the first and I sure hope the second is true (they actually work).
        return [
            self::CHANGE_ENTITY,
            self::CHANGE_PROFILE,
            self::CONFIG_PAGE,
            self::DISPLAY_LOCKED_FIELDS,
            self::DISPLAY_LOGIN,
            self::DISPLAY_CENTRAL,
            self::INIT_SESSION,
            self::POST_KANBAN_CONTENT,
            self::PRE_KANBAN_CONTENT,
            self::POST_INIT,
            self::RETRIEVE_MORE_DATA_FROM_LDAP,
            self::RETRIEVE_MORE_FIELD_FROM_LDAP,
            self::RESTRICT_LDAP_AUTH,
            self::RULE_MATCHED,
            self::UNDISCLOSED_CONFIG_VALUE,
            self::UNLOCK_FIELDS,
            self::VCARD_DATA,
        ];
    }

    /**
     * Get items hooks
     *
     * @return array
     */
    public static function getItemHooks(): array
    {
        return [
            self::ADD_RECIPIENT_TO_TARGET,
            self::ITEM_ACTION_TARGETS,
            self::ITEM_ADD,
            self::ITEM_ADD_TARGETS,
            self::ITEM_CAN,
            self::ITEM_EMPTY,
            self::ITEM_DELETE,
            self::ITEM_GET_DATA,
            self::ITEM_GET_EVENTS,
            self::ITEM_UPDATE,
            self::ITEM_PURGE,
            self::ITEM_RESTORE,
            self::ITEM_TRANSFER,
            self::PRE_ITEM_ADD,
            self::PRE_ITEM_UPDATE,
            self::PRE_ITEM_DELETE,
            self::PRE_ITEM_FORM,
            self::PRE_ITEM_PURGE,
            self::PRE_ITEM_RESTORE,
            self::PRE_SHOW_ITEM,
            self::PRE_SHOW_TAB,
            self::POST_ITEM_FORM,
            self::POST_SHOW_ITEM,
            self::POST_SHOW_TAB,
            self::POST_PREPAREADD,
            self::SHOW_ITEM_STATS,
            self::TIMELINE_ACTIONS,
        ];
    }
}
