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

/**
 * @link https://glpi-developer-documentation.readthedocs.io/en/master/plugins/hooks.html
 * @note Documentation added on the constants here will be used to generate the plugin hook documentation.
 */
class Hooks
{
    /**
     * @deprecated 11.0.0
     */
    public const CSRF_COMPLIANT = 'csrf_compliant';

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
     * Register a function to be called when another plugin is disabled.
     * The function is called with the plugin name as a parameter.
     */
    public const POST_PLUGIN_DISABLE   = 'post_plugin_disable';

    /**
     * Register a function to be called when the plugin is cleaned from the database.
     * The function is called with the plugin name as a parameter.
     */
    public const POST_PLUGIN_CLEAN     = 'post_plugin_clean';

    /**
     * Register a function to be called when another plugin is installed.
     * The function is called with the plugin name as a parameter.
     */
    public const POST_PLUGIN_INSTALL   = 'post_plugin_install';

    /**
     * Register a function to be called when another plugin is uninstalled.
     * The function is called with the plugin name as a parameter.
     */
    public const POST_PLUGIN_UNINSTALL = 'post_plugin_uninstall';

    /**
     * Register a function to be called when another the plugin is enabled.
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
     * - 'itemtype' => The type of the item represented by the Kanban card
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

    /**
     * Register a function to get more LDAP -> Field mappings.
     * The function is called with an array containing the current mappings.
     * The function is expected to modify the given array as needed and return it.
     * @see AuthLDAP::getSyncFields
     */
    public const RETRIEVE_MORE_FIELD_FROM_LDAP = 'retrieve_more_field_from_ldap';

    /**
     * Register a function to add additional checks to the LDAP authentication.
     * The function is called with an array containing several properties including:
     * - 'dn' => The DN of the user
     * - login field => Login field value where 'login field' is the name of the login field (usually samaccountname or uid) set in the LDAP config in GLPI.
     * - sync field => Sync field value where 'sync field' is the name of the sync field (usually objectguid or entryuuid) set in the LDAP config in GLPI
     */
    public const RESTRICT_LDAP_AUTH            = 'restrict_ldap_auth';

    /**
     * Register a function to handle unlocking additional fields.
     * The function is called with the $_POST array containing several properties including:
     * - 'itemtype' => The type of the item for which the fields are unlocked
     * - 'id' => The ID of the item for which the fields are unlocked
     * - itemtype => Array of fields to unlock where 'itemtype' is the name of the item type (usually the same as the itemtype value).
     * The function is expected to return nothing.
     */
    public const UNLOCK_FIELDS                 = 'unlock_fields';

    /**
     * Register a function to optionally hide a config value in certain locations such as the API.
     * The function is called with an array containing several properties including:
     * - 'context' => The context of the config option ('core' for core GLPI configs)
     * - 'name' => The name of the config option
     * - 'value' => The value of the config option
     * The function is expected to modify the given array as needed (typically unsetting the value if it should be hidden) and return it.
     */
    public const UNDISCLOSED_CONFIG_VALUE      = 'undiscloseConfigValue';

    /**
     * Register a function to modify the actor results in the right panel of ITIL objects.
     * The function is called with an array containing several properties including:
     * - 'actors' => The current actor results
     * - 'params' => The parameters used to retrieve the actors
     * The function is expected to modify the given array as needed and return it.
     */
    public const FILTER_ACTORS                 = 'filter_actors';

    /**
     * Register a function to declare what the default display preferences are for an itemtype.
     * This is not used when no display preferences are set for the itemtype, but rather when the preferences are being reset.
     * Therefore, defaults should be set during the plugin installation and the result of the function should be the same as the default values set in the plugin installation.
     * Core GLPI itemtypes with display preferences set in `install/empty_data.php` will never use this hook.
     * The function is called with an array containing several properties including:
     * - 'itemtype' => The type of the item for which the display preferences are set
     * - 'prefs' => The current defaults (usually empty unless also modified by another plugin)
     * The function is expected to modify the given array as needed and return it.
     */
    public const DEFAULT_DISPLAY_PREFS         = 'default_display_prefs';

    /**
     * Must be set to true for some other hooks to function including:
     * - {@link self::AUTO_GET_RULE_CRITERIA}
     * - {@link self::AUTO_GET_RULE_ACTIONS}
     * - {@link self::AUTO_RULE_COLLECTION_PREPARE_INPUT_DATA_FOR_PROCESS}
     * - {@link self::AUTO_PRE_PROCESS_RULE_COLLECTION_PREVIEW_RESULTS}
     * - {@link self::AUTO_RULEIMPORTASSET_GET_SQL_RESTRICTION}
     * - {@link self::AUTO_RULEIMPORTASSET_ADD_GLOBAL_CRITERIA}
     */
    public const USE_RULES                     = 'use_rules';

    // Item hooks expecting an 'item' parameter
    /**
     * Register a function to be called when a notification recipient is to be added.
     * The function is called with the {@link NotificationTarget} object as a parameter.
     * The function is expected to return nothing.
     * The added notification target information can be found in the `recipient_data` property of the object. Modifying this information will have no effect.
     * The current list of all added notification targets can be found in the `target` property of the object.
     * If you wish to remove/modify targets, you must do so in the `target` property.
     */
    public const ADD_RECIPIENT_TO_TARGET   = 'add_recipient_to_target';

    /**
     * Register a function to be called to display some automatic inventory information.
     * The function is called with the item as a parameter.
     * The function is expected to return nothing, but the information may be output directly.
     * The function is only called for items that have the `is_dynamic` field, and it is set to 1.
     */
    public const AUTOINVENTORY_INFORMATION = 'autoinventory_information';

    /**
     * Register a function to be called to display extra Infocom form fields/information.
     * The function is called with the item as a parameter.
     * The function is expected to return nothing, but the information may be output directly.
     */
    public const INFOCOM                   = 'infocom';

    /**
     * Register a function to handle adding a plugin-specific notification target.
     * The function is called with the NotificationTarget object as a parameter.
     * The function is expected to return nothing.
     * The notification target data can be found in the `data` property of the object.
     * @see NotificationTarget::addToRecipientsList()
     */
    public const ITEM_ACTION_TARGETS       = 'item_action_targets';

    /**
     * Register a function to handle adding new possible recipients for notification targets.
     * The function is called with the NotificationTarget object as a parameter.
     * The function is expected to return nothing.
     * @see NotificationTarget::addTarget()
     */
    public const ITEM_ADD_TARGETS          = 'item_add_targets';

    /**
     * Register a function to handle the 'item_empty' lifecycle event for an item.
     * The function is called with the item as a parameter.
     * The function is expected to return nothing.
     * The hook is called at the very end of the process of initializing an empty item.
     * @see CommonDBTM::getEmpty()
     */
    public const ITEM_EMPTY                = 'item_empty';

    /**
     * Register a function to handle the 'pre_item_add' lifecycle event for an item.
     * The function is called with the item as a parameter.
     * The function is expected to return nothing.
     * This hook is called at the very beginning of the add process, before the input has been modified.
     * The input can be found in the `input` property of the item. Setting the `input` property to false will cancel the add process.
     * @see CommonDBTM::prepareInputForAdd()
     */
    public const PRE_ITEM_ADD              = 'pre_item_add';

    /**
     * Register a function to handle the 'post_prepareadd' lifecycle event for an item.
     * The function is called with the item as a parameter.
     * The function is expected to return nothing.
     * This hook is called after the input has been modified, but before the item is added to the database.
     * The input can be found in the `input` property of the item. Setting the `input` property to false will cancel the add process.
     * @see CommonDBTM::prepareInputForAdd()
     */
    public const POST_PREPAREADD           = 'post_prepareadd';

    /**
     * Register a function to handle the 'item_add' lifecycle event for an item.
     * The function is called with the item as a parameter.
     * The function is expected to return nothing.
     * This hook is called at the very end of the add process, after the item has been added to the database.
     */
    public const ITEM_ADD                  = 'item_add';

    /**
     * Register a function to handle the 'pre_item_update' lifecycle event for an item.
     * The function is called with the item as a parameter.
     * The function is expected to return nothing.
     * This hook is called at the very beginning of the update process, before the input has been modified.
     * The input can be found in the `input` property of the item. Setting the `input` property to false will cancel the update process.
     * @see CommonDBTM::prepareInputForUpdate()
     */
    public const PRE_ITEM_UPDATE           = 'pre_item_update';

    /**
     * Register a function to handle the 'item_update' lifecycle event for an item.
     * The function is called with the item as a parameter.
     * The function is expected to return nothing.
     * This hook is called at the very end of the update process, after the item has been updated in the database.
     * The input can be found in the `input` property of the item while the updated field names can be found in the `updates` property.
     * The old values of changed field can be found in the `oldvalues` property.
     */
    public const ITEM_UPDATE               = 'item_update';

    /**
     * Register a function to handle the 'pre_item_delete' lifecycle event for an item.
     * The function is called with the item as a parameter.
     * The function is expected to return nothing.
     * This hook is called at the very beginning of the soft-deletion process.
     * The input can be found in the `input` property of the item. Setting the `input` property to false will cancel the deletion process.
     */
    public const PRE_ITEM_DELETE           = 'pre_item_delete';

    /**
     * Register a function to handle the 'item_delete' lifecycle event for an item.
     * The function is called with the item as a parameter.
     * The function is expected to return nothing.
     * This hook is called at the very end of the soft-deletion process, after the item has been soft-deleted from the database (`is_deleted` set to 1).
     */
    public const ITEM_DELETE               = 'item_delete';

    /**
     * Register a function to handle the 'pre_item_purge' lifecycle event for an item.
     * The function is called with the item as a parameter.
     * The function is expected to return nothing.
     * This hook is called at the very beginning of the purge process.
     * The input can be found in the `input` property of the item. Setting the `input` property to false will cancel the purge process.
     */
    public const PRE_ITEM_PURGE            = 'pre_item_purge';

    /**
     * Register a function to handle the 'item_purge' lifecycle event for an item.
     * The function is called with the item as a parameter.
     * The function is expected to return nothing.
     * This hook is called at the very end of the purge process, after the item has been purged from the database.
     */
    public const ITEM_PURGE                = 'item_purge';

    /**
     * Register a function to handle the 'pre_item_restore' lifecycle event for an item.
     * The function is called with the item as a parameter.
     * The function is expected to return nothing.
     * This hook is called at the very beginning of the restore process.
     * The input can be found in the `input` property of the item. Setting the `input` property to false will cancel the restore process.
     */
    public const PRE_ITEM_RESTORE          = 'pre_item_restore';

    /**
     * Register a function to handle the 'item_restore' lifecycle event for an item.
     * The function is called with the item as a parameter.
     * The function is expected to return nothing.
     * This hook is called at the very end of the restore process, after the item has been restored in the database (`is_deleted` set to 0).
     */
    public const ITEM_RESTORE              = 'item_restore';

    /**
     * Register a function to handle adding data for a notification target.
     * The function is called with the NotificationTarget object as a parameter.
     * The function is expected to return nothing.
     * The notification target data can be found in the `data` property of the object.
     * @see NotificationTarget::getForTemplate()
     */
    public const ITEM_GET_DATA             = 'item_get_datas';

    /**
     * Register a function to handle adding events for a notification target.
     * The function is called with the NotificationTarget object as a parameter.
     * The function is expected to return nothing.
     * The notification target events can be found in the `events` property of the object.
     * @see NotificationTarget::getForTemplate()
     */
    public const ITEM_GET_EVENTS           = 'item_get_events';

    /**
     * Register a function to show additional statistics in the Statistics tab of Tickets, Changes and Problems.
     * The function is called with the item as a parameter.
     * The function is expected to return nothing, but the information may be output directly.
     */
    public const SHOW_ITEM_STATS           = 'show_item_stats';

    /**
     * Register a function to add additional permission restrictions for the item.
     * The function is called with the item as a parameter.
     * The function is expected to return nothing.
     * The permission being checked can be found in the `right` property of the item.
     * The input used to create, update or delete the item can be found in the `input` property of the item.
     * If you change the `right` property to any other value, it will be treated as a failed check. Take care when reading this property as it may have been changed by another plugin. If it isn't an integer greater than 0, you should assume the check already failed.
     */
    public const ITEM_CAN                  = 'item_can';

    /**
     * Register a function to show additional fields at the top of a Ticket, Change or Problem fields panel.
     * The function is called with the following parameters:
     * - 'item' => The item for which the fields are shown
     * - 'options' => An array of form parameters
     * @used-by templates/components/itilobject/fields_panel.html.twig
     */
    public const PRE_ITIL_INFO_SECTION   = 'pre_itil_info_section';

    /**
     * Register a function to show additional fields at the bottom of a Ticket, Change or Problem fields panel.
     *  The function is called with the following parameters:
     *  - 'item' => The item for which the fields are shown
     *  - 'options' => An array of form parameters
     * @used-by templates/components/itilobject/fields_panel.html.twig
     */
    public const POST_ITIL_INFO_SECTION  = 'post_itil_info_section';

    /**
     * Register a function to be called after an item is transferred to another entity.
     * The function is called with an array containing several properties including:
     * - 'type' => The type of the item being transferred.
     * - 'id' => The original ID of the item being transferred.
     * - 'newID' => The new ID of the item being transferred. If the item was cloned into the new entity, this ID will differ from the original ID.
     * - 'entities_id' => The ID of the destination entity.
     * The function is expected to return nothing.
     */
    public const ITEM_TRANSFER           = 'item_transfer';


    /**
     * Register a function to be called before showing an item in the timeline of a Ticket, Change or Problem.
     * The function is called with the following parameters:
     * - 'item' => The item being shown in the timeline
     * - 'options' => An array containing the following properties:
     *   - 'parent' => The Ticket, Change or Problem
     *   - 'rand' => A random number that may be used for unique element IDs within the timeline item HTML
     * The function is expected to return nothing, but the information may be output directly.
     *
     * @used-by templates/components/itilobject/timeline/timeline.html.twig
     */
    public const PRE_SHOW_ITEM           = 'pre_show_item';

    /**
     * Register a function to be called after showing an item in the timeline of a Ticket, Change or Problem.
     * The function is called with the following parameters:
     * - 'item' => The item being shown in the timeline
     * - 'options' => An array containing the following properties:
     *   - 'parent' => The Ticket, Change or Problem
     *   - 'rand' => A random number that may be used for unique element IDs within the timeline item HTML
     * The function is expected to return nothing, but the information may be output directly.
     *
     * @used-by templates/components/itilobject/timeline/timeline.html.twig
     */
    public const POST_SHOW_ITEM          = 'post_show_item';

    /**
     * Register a function to show additional fields at the top of an item form.
     * The function is called with the following parameters:
     * - 'item' => The item for which the fields are shown
     * - 'options' => An array of form parameters
     * The function is expected to return nothing, but the information may be output directly.
     */
    public const PRE_ITEM_FORM           = 'pre_item_form';

    /**
     * Register a function to show additional fields at the bottom of an item form.
     * The function is called with the following parameters:
     * - 'item' => The item for which the fields are shown
     * - 'options' => An array of form parameters
     * The function is expected to return nothing, but the information may be output directly.
     */
    public const POST_ITEM_FORM          = 'post_item_form';

    /**
     * Register a function to show additional content before the main content in a tab.
     * This function is not called for the main tab of a form.
     * The function is called with the following parameters:
     * - 'item' => The item for which the tab is shown
     * - 'options' => An array containing the following properties:
     *   - 'itemtype' => The type of the item being shown in the tab
     *   - 'tabnum' => The number of the tab being shown for the itemtype
     * The function is expected to return HTML content or an empty string.
     */
    public const PRE_SHOW_TAB            = 'pre_show_tab';

    /**
     * Register a function to show additional content after the main content in a tab.
     * This function is not called for the main tab of a form.
     * The function is called with the following parameters:
     * - 'item' => The item for which the tab is shown
     * - 'options' => An array containing the following properties:
     *   - 'itemtype' => The type of the item being shown in the tab
     *   - 'tabnum' => The number of the tab being shown for the itemtype
     * The function is expected to return HTML content or an empty string.
     */
    public const POST_SHOW_TAB           = 'post_show_tab';

    /**
     * Register a function to show additional content before the search result list for an itemtype.
     * The function is called with the following parameters:
     * - 'itemtype' => The type of the item being shown in the list
     * - 'options' => Unused. Always an empty array.
     * The function is expected to return nothing, but the information may be output directly.
     * @todo The options array probably should match the $params parameter of the `SearchEngine::show` function to have important information like the display type.
     */
    public const PRE_ITEM_LIST           = 'pre_item_list';

    /**
     * Register a function to show additional content after the search result list for an itemtype.
     * The function is called with the following parameters:
     * - 'itemtype' => The type of the item being shown in the list
     * - 'options' => Unused. Always an empty array.
     * The function is expected to return nothing, but the information may be output directly.
     * @todo The options array probably should match the $params parameter of the `SearchEngine::show` function to have important information like the display type.
     */
    public const POST_ITEM_LIST          = 'post_item_list';

    /**
     * Register a function to show action buttons in the footer of a Ticket, Change or Problem timeline.
     * This is how timeline actions were displayed before version 10.0, but now using the {@link self::TIMELINE_ANSWER_ACTIONS} is the preferred way.
     * The function is called with the following parameters:
     * - 'item' => The item for which the actions are shown
     * - 'rand' => A random number that may be used for unique element IDs within the HTML
     * The function is expected to return nothing, but the information may be output directly.
     */
    public const TIMELINE_ACTIONS        = 'timeline_actions';

    /**
     * Register a function to add new itemtypes to the answer/action split dropdown, and be made available to show in a Ticket, Change or Problem timeline.
     * The function is called with the following parameters:
     * - 'item' => The item for which the actions are shown
     * The function is expected to return an array of options to be added to the dropdown.
     * Each option should have a unique key and be an array with the following properties:
     * - 'type' => The type of the item to be used for the action. In some cases, this is a parent/abstract class such as ITILTask. This is used as a CSS class on the main timeline item element.
     * - 'class' => The actual type of the item to be used for the action such as TicketTask.
     * - 'icon' => The icon to be used for the action.
     * - 'label' => The label to be used for the action.
     * - 'short_label' => The short label to be used for the action.
     * - 'template' => The Twig template to use when showing related items in the timeline.
     * - 'item' => An instance of the related itemtype.
     * - 'hide_in_menu' => If true, the option is not available in the dropdown menu but the related items may still be shown in the timeline.
     */
    public const TIMELINE_ANSWER_ACTIONS = 'timeline_answer_actions';

    /**
     * @deprecated 11.0.0 Use `TIMELINE_ITEMS` instead. The usage of both hooks is the same.
     */
    public const SHOW_IN_TIMELINE        = 'show_in_timeline';

    /**
     * Register a function to add new items to the timeline of a Ticket, Change or Problem.
     * The function is called with the following parameters:
     * - 'item' => The item for which the actions are shown.
     * - 'timeline' => The array of items currently shown in the timeline. This is passed by reference.
     * The function is expected to modify the timeline array as needed.
     * The timeline item array contains arrays where the keys are typically "${itemtype}_${items_id}" and the values are arrays with the following properties:
     * - 'type' => The type of the item being shown in the timeline. This should match the 'class' property used in {@link self::TIMELINE_ANSWER_ACTIONS}.
     * - 'item' => Array of information to pass to the 'template' used in {@link self::TIMELINE_ANSWER_ACTIONS}, and notifications.
     */
    public const TIMELINE_ITEMS          = 'timeline_items';

    /**
     * Register a function to set the icon used by an item in the impact graph.
     * The function is called with the following parameters:
     * - 'itemtype' => The type of the item being shown in the graph
     * - 'items_id' => The ID of the item being shown in the graph
     * The function is expected to return a URL starting with a '/' relative to the GLPI root directory, or an empty string.
     */
    public const SET_ITEM_IMPACT_ICON    = 'set_item_impact_icon'; // (keys: itemtype, items_id)

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
    /**
     * Register a function to insert extra data into the PROLOG response from the server to an agent.
     * This includes netdiscovery and netinventory tasks to run (ip ranges, jobs configuration, credentials).
     * It excludes data from ESX, Deploy and Collect which are handled differently.
     * Wakeonlan related is outdated and not supported in glpi-agent.
     * Agent has to run netdiscovery and netinventory tasks if it receives data in PROLOG response for them.
     *
     * The function is called with the following parameters:
     * - 'params' => An array containing the following properties:
     *   - 'mode' => The response mode. See the `Glpi\Agent\CommunicationAgent::*_MODE` constants.
     *   - 'deviceid' => The device ID string assigned to the agent.
     *   - 'response' => An array containing the PROLOG response data which may differ based on the type of agent (GLPI Agent or an older type of agent).
     *
     * If the agent is a GLPI Agent, the response array will contain the following properties:
     * - 'expiration' => The inventory frequency in seconds.
     * - 'status' => Always 'ok'.
     * If the agent is not a GLPI Agent, the response array will contain the following properties (backwards compatibility with older types of agents):
     * - 'PROLOG_FREQ' => The inventory frequency in seconds.
     * - 'RESPONSE' => Always 'SEND'.
     *
     * The function is expected to modify the given array as needed and return it.
     */
    public const PROLOG_RESPONSE = 'prolog_response';

    /**
     * Register a function to modify the network discovery data sent from an agent.
     * The function is called with the following parameters:
     * - 'mode' => The response mode. See the `Glpi\Agent\CommunicationAgent::*_MODE` constants.
     * - 'inventory' => An `Glpi\Inventory\Inventory` object containing the discovery data.
     * - 'deviceid' => The device ID string assigned to the agent.
     * - 'response' => An array that can be filled with data to be sent back to the agent. This will be empty unless modified by another plugin.
     * - 'errors' => An array that can be filled with errors to be sent back to the agent. This may not exist unless added by another plugin.
     * - 'query' => Should be 'netdiscovery'.
     *
     * The function is expected to modify the given array as needed and return it.
     * Only the 'response' and 'errors' keys will be taken into account in the returned data.
     * If no response or error data is provided, the agent will be told that the server does not support network discovery.
     */
    public const NETWORK_DISCOVERY = 'network_discovery';

    /**
     * Register a function to modify the network inventory data sent from an agent.
     * The function is called with the following parameters:
     * - 'mode' => The response mode. See the `Glpi\Agent\CommunicationAgent::*_MODE` constants.
     * - 'inventory' => An `Glpi\Inventory\Inventory` object containing the inventory data.
     * - 'deviceid' => The device ID string assigned to the agent.
     * - 'response' => An array that can be filled with data to be sent back to the agent. This will be empty unless modified by another plugin.
     * - 'errors' => An array that can be filled with errors to be sent back to the agent. This may not exist unless added by another plugin.
     * - 'query' => Should be 'netinventory'.
     *
     * The function is expected to modify the given array as needed and return it.
     * Only the 'response' and 'errors' keys will be taken into account in the returned data.
     * If no response or error data is provided, the agent will be told that the server does not support network inventory.
     */
    public const NETWORK_INVENTORY = 'network_inventory';

    /**
     * Register a function to provide an agent with additional requested parameters for inventory.
     * An example of this usage can be found in the `databaseinventory` plugin which responds to the GLPI Agent's request for database credentials to allow it to collect database information.
     * The GLPI Agent will only ask for these parameters if the server indicates that it has them available in the inventory task response.
     *
     * The function is called with the following parameters:
     * - 'options' => An array containing the following properties:
     *   - 'content' => The request from the agent.
     *   - 'response' => An array that can be filled with data to be sent back to the agent. By default, it is an array with the following properties:
     *     - 'expiration' => The inventory frequency in seconds.
     *     - 'status' => Always 'ok'.
     *  - 'item' => The Agent item
     *
     * The function is expected to modify the given array as needed and return it.
     */
    public const INVENTORY_GET_PARAMS = 'inventory_get_params';

    /**
     * Register a function to be called before the inventory submission is handled.
     * The function is called with the following parameters:
     * - 'data' => An object containing the inventory data submitted by the agent.
     *
     * The function is expected to return the modified data object or null to cancel the inventory submission with no specific reason.
     * Throwing an Exception will cancel the inventory submission with the exception message as the reason.
     * To avoid unrelated exception messages from being sent to the agent, you must handle all exceptions (except the one you would throw to cancel the inventory) within the hook function.
     */
    public const PRE_INVENTORY = 'pre_inventory';

    /**
     * Register a function to be called after the inventory submission is handled.
     * The function is called with the following parameters:
     *  - 'data' => An object containing the inventory data submitted by the agent.
     * The function is expected to return nothing.
     * This hook is only called if the inventory submission was successful.
     */
    public const POST_INVENTORY = 'post_inventory';

    // Agent contact request related hooks

    /**
     * Register a function to be called when an agent asks if the server supports the inventory task.
     * The function is called with an array containing the following properties:
     * - 'options' => An array containing the following properties:
     *   - 'response' => An array that can be filled with data to be sent back to the agent. By default, it is an array with the following properties:
     *     - 'inventory' => An array containing the following properties:
     *       - 'server' => 'glpi' to indicate that GLPI natively supports the inventory task.
     *       - 'version' => The GLPI server version.
     * - 'item' => The Agent item
     * The function is expected to modify the given array as needed and return it.
     * Only the 'response' key will be taken into account in the returned data.
     */
    public const HANDLE_INVENTORY_TASK    = 'handle_inventory_task';

    /**
     * Register a function to be called when an agent asks if the server supports the network discovery task.
     * The function is called with an array containing the following properties:
     * - 'options' => An array containing the following properties:
     *   - 'response' => An array that can be filled with data to be sent back to the agent. It is an array that may contain the following properties:
     *     - 'inventory' => An array containing the following properties:
     *       - 'server' => The server/plugin that can handle the task.
     *       - 'version' => The server/plugin version.
     * - 'item' => The Agent item
     * The function is expected to modify the given array as needed and return it.
     * Only the 'response' key will be taken into account in the returned data.
     */
    public const HANDLE_NETDISCOVERY_TASK = 'handle_netdiscovery_task';

    /**
     * Register a function to be called when an agent asks if the server supports the network inventory task.
     * The function is called with an array containing the following properties:
     * - 'options' => An array containing the following properties:
     *   - 'response' => An array that can be filled with data to be sent back to the agent. It is an array that may contain the following properties:
     *     - 'inventory' => An array containing the following properties:
     *       - 'server' => The server/plugin that can handle the task.
     *       - 'version' => The server/plugin version.
     * - 'item' => The Agent item
     * The function is expected to modify the given array as needed and return it.
     * Only the 'response' key will be taken into account in the returned data.
     */
    public const HANDLE_NETINVENTORY_TASK = 'handle_netinventory_task';

    /**
     * Register a function to be called when an agent asks if the server supports the ESX inventory task.
     * The function is called with an array containing the following properties:
     * - 'options' => An array containing the following properties:
     *   - 'response' => An array that can be filled with data to be sent back to the agent. It is an array that may contain the following properties:
     *     - 'inventory' => An array containing the following properties:
     *       - 'server' => The server/plugin that can handle the task.
     *       - 'version' => The server/plugin version.
     * - 'item' => The Agent item
     * The function is expected to modify the given array as needed and return it.
     * Only the 'response' key will be taken into account in the returned data.
     */
    public const HANDLE_ESX_TASK          = 'handle_esx_task';

    /**
     * Register a function to be called when an agent asks if the server supports the collect task.
     * The function is called with an array containing the following properties:
     * - 'options' => An array containing the following properties:
     *   - 'response' => An array that can be filled with data to be sent back to the agent. It is an array that may contain the following properties:
     *     - 'inventory' => An array containing the following properties:
     *       - 'server' => The server/plugin that can handle the task.
     *       - 'version' => The server/plugin version.
     * - 'item' => The Agent item
     * The function is expected to modify the given array as needed and return it.
     * Only the 'response' key will be taken into account in the returned data.
     */
    public const HANDLE_COLLECT_TASK      = 'handle_collect_task';

    /**
     * Register a function to be called when an agent asks if the server supports the deploy task.
     * The function is called with an array containing the following properties:
     * - 'options' => An array containing the following properties:
     *   - 'response' => An array that can be filled with data to be sent back to the agent. It is an array that may contain the following properties:
     *     - 'inventory' => An array containing the following properties:
     *       - 'server' => The server/plugin that can handle the task.
     *       - 'version' => The server/plugin version.
     * - 'item' => The Agent item
     * The function is expected to modify the given array as needed and return it.
     * Only the 'response' key will be taken into account in the returned data.
     */
    public const HANDLE_DEPLOY_TASK       = 'handle_deploy_task';

    /**
     * Register a function to be called when an agent asks if the server supports the wake-on-lan task.
     * The function is called with an array containing the following properties:
     * - 'options' => An array containing the following properties:
     *   - 'response' => An array that can be filled with data to be sent back to the agent. It is an array that may contain the following properties:
     *     - 'inventory' => An array containing the following properties:
     *       - 'server' => The server/plugin that can handle the task.
     *       - 'version' => The server/plugin version.
     * - 'item' => The Agent item
     * The function is expected to modify the given array as needed and return it.
     * Only the 'response' key will be taken into account in the returned data.
     */
    public const HANDLE_WAKEONLAN_TASK    = 'handle_wakeonlan_task';

    /**
     * Register a function to be called when an agent asks if the server supports the remote inventory task.
     * The function is called with an array containing the following properties:
     * - 'options' => An array containing the following properties:
     *   - 'response' => An array that can be filled with data to be sent back to the agent. It is an array that may contain the following properties:
     *     - 'inventory' => An array containing the following properties:
     *       - 'server' => The server/plugin that can handle the task.
     *       - 'version' => The server/plugin version.
     * - 'item' => The Agent item
     * The function is expected to modify the given array as needed and return it.
     * Only the 'response' key will be taken into account in the returned data.
     */
    public const HANDLE_REMOTEINV_TASK    = 'handle_remoteinventory_task';

    /**
     * Add new agent cleanup actions.
     * The hook is expected to be an array where each value is an array with the following properties:
     * - 'label' => The label to be used for the action.
     * - 'render_callback' => Callable used to display the configuration field. The callable will be called with the inventory configuration values array.
     * - 'action_callback' => Callable used to perform the action. The callable will be called with the following parameters:
     *   - 'agent' => The agent to be cleaned
     *   - 'config' => The inventory configuration values array
     *   - 'item' => The asset that the agent is for
     */
    public const STALE_AGENT_CONFIG = 'stale_agent_config';

    /**
     * Add menu items.
     * The hook is expected to be an array where the keys are identiifers for the top-level menu items, and the values are arrays with the following properties:
     * - 'types' => Array of item types to be added
     * - 'icon' => The icon for the top-level menu item which is expected to be a Tabler icon CSS class
     */
    public const MENU_TOADD = 'menu_toadd';

    /**
     * Add a menu item in the simplified interface.
     * The hook is expected to be a URL relative to the plugin's directory.
     */
    public const HELPDESK_MENU_ENTRY = 'helpdesk_menu_entry';

    /**
     * Add an icon for the menu item added with the {@link self::HELPDESK_MENU_ENTRY} hook.
     * The hook is expected to be a Tabler icon CSS class.
     */
    public const HELPDESK_MENU_ENTRY_ICON = 'helpdesk_menu_entry_icon';

    // Dashboard hooks
    /**
     * Register a function to add new dashboard cards.
     * The function is called with no parameters.
     * The function is expected to return an array of dashboard cards.
     * Each key in the returned array should be a unique identifier for the card.
     * The value should be an array with the following properties (but not limited to):
     * - 'widgettype' => Array of widget types this card can use (pie, bar, line, etc)
     * - 'label' => The label to be used for the card
     * - 'group' => Group string to be used to organize the card in dropdowns
     * - 'filters' => An optional array of filters that can apply to this card
     */
    public const DASHBOARD_CARDS    = 'dashboard_cards';

    /**
     * Add new dashboard filters.
     * The hook is expected to be an array of classes which extend {@link Glpi\Dashboard\Filters\AbstractFilter}.
     */
    public const DASHBOARD_FILTERS  = 'dashboard_filters';

    /**
     * Add new dashboard color palettes.
     * The hook is expected to be an array where the keys are unique identifiers and the values are arrays of #rrggbb color strings.
     */
    public const DASHBOARD_PALETTES = 'dashboard_palettes';

    /**
     * Register a function to add new dashboard widget types.
     * The function is called with no parameters.
     * The function is expected to return an array where the keys are unique identifiers and the values are arrays with the following properties:
     * - 'label' => The label to be used for the widget type
     * - 'function' => A callable to be used to display the widget
     * - 'image' => The image to be used for the widget
     * - 'limit' => Indicate if the amount of data shown by the widget can be limited
     * - 'width' => The default width of cards using this widget
     * - 'height' => The default height of cards using this widget
     */
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
     * The middleware classes should extend {@link \Glpi\Api\HL\Middleware\AbstractMiddleware} and
     * implement either {@link \Glpi\Api\HL\Middleware\RequestMiddlewareInterface{ or {@link \Glpi\Api\HL\Middleware\ResponseMiddlewareInterface}.
     * The arrays may also contain values for 'priority' and 'condition' where priority is an integer (higher is more important) and condition is a callable.
     * If a condition is provided, that callable will be called with the current controller as a parameter, and it must return true for the middleware to be used, or false to not be.
     */
    public const API_MIDDLEWARE                = 'api_middleware';

    /**
     * Add new statistics reports.
     * The hook is expected to be an array where the keys are URLs relative to the plugin's directory and the values are the report names.
     */
    public const STATS = 'stats';

    /**
     * Register a function to add new email server protocols.
     * The function is called with no parameters.
     * The function is expected to return an array where the keys are the protocol name and the values are arrays with the following properties:
     * - 'label' => The label to be used for the protocol.
     * - 'protocol' => The name of the class to be used for the protocol. The class should use the `Laminas\Mail\Protocol\ProtocolTrait` trait.
     * - 'storage' => The name of the class to be used for the protocol storage. The class should extend the `Laminas\Mail\Storage\AbstractStorage` class.
     */
    public const MAIL_SERVER_PROTOCOLS = 'mail_server_protocols';

    // Function hooks that are currently automatically registered. Example: MassiveActions -> plugin_myplugin_MassiveActions

    /**
     * Automatic hook function to add new massive actions.
     * The function is called with the itemtype as a parameter.
     * The function is expected to return an array of massive action.
     * Only called if the plugin also uses the {@link self::USE_MASSIVE_ACTION} hook set to true.
     */
    public const AUTO_MASSIVE_ACTIONS = 'MassiveActions';

    /**
     * Automatic hook function to display the form for the "update" massive action for itemtypes or search options related to the plugin.
     * The function is called with the following parameters:
     * - 'itemtype' => The type of the item for which the fields are shown
     * - 'options' => The search option array
     * The function is expected to return true if the display is handled, or false if the default behavior should be used.
     */
    public const AUTO_MASSIVE_ACTIONS_FIELDS_DISPLAY = 'MassiveActionsFieldsDisplay';

    /**
     * Automatic hook function called to handle the export display of an itemtype added by the plugin.
     * The function is called with the $_GET array containing several properties including:
     * - 'item_type' => The type of the item for which the fields are shown
     * - 'display_type' => The numeric type of the display. See the constants in the `Search` class.
     * - 'export_all' => If all pages are being exported or just the current one.
     * The function is expected to return true if the display is handled, or false if the default behavior should be used.
     */
    public const AUTO_DYNAMIC_REPORT = 'dynamicReport';

    /**
     * Automatic hook function to add new itemtypes which can be linked to Tickets, Changes or Problems.
     * The function is called with the current array of plugin itemtypes allowed to be linked.
     * The function is expected to modify the given array as needed and return it.
     */
    public const AUTO_ASSIGN_TO_TICKET = 'AssignToTicket';

    /**
     * Automatic hook function called to get additional dropdown classes which would be displayed in Setup > Dropdowns.
     * The function is called with no parameters.
     * The function is expected to return an array where the class names are in the keys or null. For the array values, anything can be used, but typically it is just `null`.
     */
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
     * The function is expected to modify the given array as needed and return it.
     * Only called if the plugin also uses the {@link self::USE_RULES} hook set to true.
     */
    public const AUTO_RULEIMPORTASSET_GET_SQL_RESTRICTION = 'ruleImportAsset_getSqlRestriction';

    /**
     * Automatic hook function called with an array of the current global criteria.
     * The function is expected to modify the given array as needed and return it.
     */
    public const AUTO_RULEIMPORTASSET_ADD_GLOBAL_CRITERIA = 'ruleImportAsset_addGlobalCriteria';

    /**
     * Automatic hook function to display the value field for a search option criteria.
     * The function is called with an array with the following properties:
     * - 'name' => The HTML input name expected.
     * - searchtype' => The search type of the criteria (contains, equals, etc).
     * - 'searchoption' => The search option array related to the criteria.
     * - 'value' => The current value of the criteria.
     * The function is expected to output HTML content if it customizes the value field and then return true. If the default behavior is desired, the function should not output anything and return false.
     */
    public const AUTO_SEARCH_OPTION_VALUES = 'searchOptionsValues';

    /**
     * Automatic hook function to add URL parameters needed for a dynamic report/export.
     * The function is called with the itemtype as a parameter.
     * The function is expected to return a key/value array of parameters to add.
     */
    public const AUTO_ADD_PARAM_FOR_DYNAMIC_REPORT = 'addParamFordynamicReport';

    /**
     * Automatic hook function to add a JOIN clause to the SQL query for a search of itemtypes added by the plugin.
     * This can be a LEFT JOIN , INNER JOIN or RIGHT JOIN.
     * The function is called with the following parameters:
     * - 'itemtype' => The type of the items being searched.
     * - 'reference_table' => The name of the reference table. This should be the table for the itemtype.
     * - 'already_link_table' => An array of tables that are already joined.
     * The function is expected to return a SQL JOIN clause in the iterator array format, raw SQL string or an empty string if the default behavior should be used.
     */
    public const AUTO_ADD_DEFAULT_JOIN = 'addDefaultJoin';

    /**
     * Automatic hook function to add a SELECT clause to the SQL query for a searchof itemtypes added by the plugin.
     * The function is called with the following parameters:
     * - 'itemtype' => The type of the items being searched.
     * The function is expected to return a SQL SELECT clause as a string or an empty string if the default behavior should be used.
     */
    public const AUTO_ADD_DEFAULT_SELECT = 'addDefaultSelect';

    /**
     * Automatic hook function to add a WHERE clause to the SQL query for a searchof itemtypes added by the plugin.
     * The function is called with the following parameters:
     * - 'itemtype' => The type of the items being searched.
     * The function is expected to return a SQL WHERE clause in the iterator array format, raw SQL string or an empty string if the default behavior should be used.
     */
    public const AUTO_ADD_DEFAULT_WHERE = 'addDefaultWhere';

    /**
     * Automatic hook function to add a JOIN clause to the SQL query for a search.
     * The function is called with the following parameters:
     * - 'itemtype' => The type of the items being searched.
     * - 'join' => The current JOIN clause in the iterator format.
     * The function is expected to return the modified join array or an empty array if no join should be added.
     *  This function is called after the {@link self::AUTO_ADD_DEFAULT_JOIN} hook and after the default joins are added.
     */
    public const ADD_DEFAULT_JOIN = 'add_default_join';

    /**
     * Automatic hook function to add a WHERE clause to the SQL query for a search.
     * The function is called with the following parameters:
     * - 'itemtype' => The type of the items being searched.
     * - 'criteria' => The current WHERE clause in the iterator format.
     * The function is expected to return the modified criteria array or an empty array if no criteria should be added.
     * This function is called after the {@link self::AUTO_ADD_DEFAULT_WHERE} hook and after the default WHERE clauses are added.
     */
    public const ADD_DEFAULT_WHERE = 'add_default_where';

    /**
     * Automatic hook function to add a HAVING clause to the SQL query for a specific search criteria.
     * The function is called with the following parameters:
     * - 'link' => The linking operator (AND/OR) for the criteria.
     * - 'not' => Indicates if the criteria is negated.
     * - 'itemtype' => The type of the items being searched.
     * - 'search_option_id' => The ID of the search option of the criteria.
     * - 'search_value' => The value to search for.
     * - 'num' => A string in the form of "${itemtype}_{$search_option_id}". The alias of the related field in the SELECT clause will be "ITEM_{$num}".
     * The function is expected to return a SQL HAVING clause in the iterator array format, raw SQL string or an empty string if the default behavior should be used.
     */
    public const AUTO_ADD_HAVING = 'addHaving';

    /**
     * Automatic hook function to add a JOIN clause to the SQL query for a specific search criteria.
     * Despite the name, this can be a LEFT JOIN , INNER JOIN or RIGHT JOIN.
     * The function is called with the following parameters:
     * - 'itemtype' => The type of the items being searched.
     * - 'reference_table' => The name of the reference table. This is typically the table for the itemtype.
     * - 'new_table' => The name of the table to be joined. Typically, this is the table related to the search option.
     * - 'link_field' => The name of the field in the reference table that links to the new table.
     * - 'already_link_table' => An array of tables that are already joined.
     * The function is expected to return a SQL JOIN clause in the iterator array format, raw SQL string or an empty string if the default behavior should be used.
     */
    public const AUTO_ADD_LEFT_JOIN = 'addLeftJoin';

    /**
     * Automatic hook function to add an ORDER clause to the SQL query for a specific search criteria.
     * The function is called with the following parameters:
     * - 'itemtype' => The type of the items being searched.
     * - 'search_option_id' => The ID of the search option of the criteria.
     * - 'order' => The order requested (ASC/DESC).
     * - 'num' => A string in the form of "${itemtype}_{$search_option_id}". The alias of the related field in the SELECT clause will be "ITEM_{$num}".
     * The function is expected to return a SQL ORDER clause as a string or an empty string if the default behavior should be used.
     */
    public const AUTO_ADD_ORDER_BY = 'addOrderBy';

    /**
     * Automatic hook function to add a SELECT clause to the SQL query for a specific search criteria.
     * The function is called with the following parameters:
     * - 'itemtype' => The type of the items being searched.
     * - 'search_option_id' => The ID of the search option of the criteria.
     * - 'num' => A string in the form of "${itemtype}_{$search_option_id}". The alias of the related field in the clause returned should be "ITEM_{$num}".
     * The function is expected to return a SQL SELECT clause as a string or an empty string if the default behavior should be used.
     */
    public const AUTO_ADD_SELECT = 'addSelect';

    /**
     * Automatic hook function to add a WHERE clause to the SQL query for a specific search criteria.
     * The function is called with the following parameters:
     * - 'link' => No longer used but used to indicate the linking operator (AND/OR) for the criteria.
     * - 'not' => Indicates if the criteria is negated.
     * - 'itemtype' => The type of the items being searched.
     * - 'search_option_id' => The ID of the search option of the criteria.
     * - 'search_value' => The value to search for.
     * - 'search_type' => The type of the search (notcontains, contains, equals, etc.).
     * The function is expected to return a SQL WHERE clause in the iterator array format, raw SQL string or an empty string if the default behavior should be used.
     */
    public const AUTO_ADD_WHERE = 'addWhere';

    /**
     * Automatic hook function to show an HTML search result column value for an item of one of the itemtypes added by the plugin.
     * The function is called with the following parameters:
     * - 'itemtype' => The type of the result items.
     * - 'search_option_id' => The ID of the search option.
     * - 'data' => The data retrieved from the database.
     * - 'id' => The ID of the result item.
     * The function is expected to return the HTML content to display or an empty string if the default display should be used.
     */
    public const AUTO_GIVE_ITEM = 'giveItem';

    /**
     * Automatic hook function to change the display of a search result cell.
     * It is recommended to not use this hook and instead use the {@link self::AUTO_GIVE_ITEM} hook to customize the content.
     * This function is called with the following parameters:
     * - 'itemtype' => The type of the items being searched.
     * - 'search_option_id' => The ID of the search option.
     * - 'data' => The data retrieved from the database.
     * - 'num' => A string in the form of "${itemtype}_{$search_option_id}". The alias of the related field in the SELECT clause will be "ITEM_{$num}".
     * The function is expected to return a string with HTML attributes.
     */
    public const AUTO_DISPLAY_CONFIG_ITEM = 'displayConfigItem';

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
        //TODO Function or functional? Not always the first and I hope the second is true (they actually work).
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
