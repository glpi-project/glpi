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

class Hooks
{
    // Boolean hooks
    public const CSRF_COMPLIANT = 'csrf_compliant';

    // File hooks
    public const ADD_CSS               = 'add_css';
    public const ADD_JAVASCRIPT        = 'add_javascript';
    public const ADD_JAVASCRIPT_MODULE = 'add_javascript_module';
    public const ADD_HEADER_TAG        = 'add_header_tag';

    // File hooks for anonymous pages
    public const ADD_CSS_ANONYMOUS_PAGE               = 'add_css_anonymous_page';
    public const ADD_JAVASCRIPT_ANONYMOUS_PAGE        = 'add_javascript_anonymous_page';
    public const ADD_JAVASCRIPT_MODULE_ANONYMOUS_PAGE = 'add_javascript_module_anonymous_page';
    public const ADD_HEADER_TAG_ANONYMOUS_PAGE        = 'add_header_tag_anonymous_page';

    // Function hooks with no parameters
    public const CHANGE_ENTITY               = 'change_entity';
    public const CHANGE_PROFILE              = 'change_profile';
    public const DISPLAY_LOGIN               = 'display_login';
    public const DISPLAY_CENTRAL             = 'display_central';
    public const DISPLAY_NETPORT_LIST_BEFORE = 'display_netport_list_before';
    public const INIT_SESSION                = 'init_session';
    public const POST_INIT                   = 'post_init';
    public const CONFIG_PAGE                 = 'config_page';
    public const USE_MASSIVE_ACTION          = 'use_massive_action';
    public const IMPORT_ITEM                 = 'import_item';

    // Specific function hooks with parameters
    public const RULE_MATCHED          = 'rule_matched';
    public const VCARD_DATA            = 'vcard_data';
    public const POST_PLUGIN_DISABLE   = 'post_plugin_disable';
    public const POST_PLUGIN_CLEAN     = 'post_plugin_clean';
    public const POST_PLUGIN_INSTALL   = 'post_plugin_install';
    public const POST_PLUGIN_UNINSTALL = 'post_plugin_uninstall';
    public const POST_PLUGIN_ENABLE    = 'post_plugin_enable' ;

    // Function hooks with parameters and output
    public const DISPLAY_LOCKED_FIELDS         = 'display_locked_fields';
    public const POST_KANBAN_CONTENT           = 'post_kanban_content';
    public const PRE_KANBAN_CONTENT            = 'pre_kanban_content';
    public const KANBAN_ITEM_METADATA          = 'kanban_item_metadata';
    public const KANBAN_FILTERS                = 'kanban_filters';
    public const REDEFINE_MENUS                = 'redefine_menus';
    public const RETRIEVE_MORE_DATA_FROM_LDAP  = 'retrieve_more_data_from_ldap';
    public const RETRIEVE_MORE_FIELD_FROM_LDAP = 'retrieve_more_field_from_ldap';
    public const RESTRICT_LDAP_AUTH            = 'restrict_ldap_auth';
    public const UNLOCK_FIELDS                 = 'unlock_fields';
    public const UNDISCLOSED_CONFIG_VALUE      = 'undiscloseConfigValue';
    public const FILTER_ACTORS                 = 'filter_actors';
    public const DEFAULT_DISPLAY_PREFS         = 'default_display_prefs';

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
    public const PRE_ITIL_INFO_SECTION   = 'pre_itil_info_section';
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
    public const SECURED_FIELDS  = 'secured_fields';
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

    // Debug / Development hooks
    public const DEBUG_TABS = 'debug_tabs';

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
        ];
    }

    /**
     * Get functionals hooks
     *
     * @return array
     */
    public static function getFunctionalHooks(): array
    {
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
            self::ADD_HEADER_TAG,
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
