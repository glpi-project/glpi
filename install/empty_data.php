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

use Glpi\Inventory\Conf;
use Glpi\Socket;
use Glpi\Toolbox\Sanitizer;

// Use anonymous class so we can have constants that define special values without polluting the global table
// and adding unnecessary variables to IDE autocomplete data that may result in errors
$empty_data_builder = new class
{
    /** @var int Self-service profile ID */
    const PROFILE_SELF_SERVICE = 1;
    /** @var int Observer profile ID */
    const PROFILE_OBSERVER     = 2;
    /** @var int Admin profile ID */
    const PROFILE_ADMIN        = 3;
    /** @var int Super-admin profile ID */
    const PROFILE_SUPER_ADMIN  = 4;
    /** @var int Hotliner profile ID */
    const PROFILE_HOTLINER     = 5;
    /** @var int Technician profile ID */
    const PROFILE_TECHNICIAN   = 6;
    /** @var int Supervisor profile ID */
    const PROFILE_SUPERVISOR   = 7;
    /** @var int Read-only profile ID */
    const PROFILE_READ_ONLY    = 8;

    const USER_GLPI            = 2;
    const USER_POST_ONLY       = 3;
    const USER_TECH            = 4;
    const USER_NORMAL          = 5;
    const USER_SYSTEM          = 6;

    /** @var int Value indicating no rights */
    const RIGHT_NONE           = 0;

    public function getEmptyData(): array
    {
        $tables = [];

        $tables['glpi_apiclients'] = [
            [
                'id' => 1,
                'entities_id' => 0,
                'is_recursive' => 1,
                'name' => 'full access from localhost',
                'is_active' => 1,
                'ipv4_range_start' => "2130706433", //value from MySQL INET_ATON('127.0.0.1')
                'ipv4_range_end' => "2130706433", //value from MySQL INET_ATON('127.0.0.1')
                'ipv6' => '::1',
            ],
        ];

        foreach (Blacklist::getDefaults() as $type => $values) {
            foreach ($values as $value) {
                $tables['glpi_blacklists'][] = [
                    'type' => $type,
                    'name' => $value['name'],
                    'value' => $value['value'],
                ];
            }
        }


        $tables['glpi_calendars'] = [
            [
                'id' => 1,
                'name' => 'Default',
                'entities_id' => 0,
                'is_recursive' => 1,
                'comment' => 'Default calendar',
                'cache_duration' => '[0,43200,43200,43200,43200,43200,0]',
            ],
        ];

        $tables['glpi_calendarsegments'] = [];
        for ($i = 1; $i < 6; ++$i) {
            $tables['glpi_calendarsegments'][] = [
                'id' => $i,
                'calendars_id' => 1,
                'entities_id' => 0,
                'is_recursive' => 0,
                'day' => $i,
                'begin' => '08:00:00',
                'end' => '20:00:00',
            ];
        }

        $default_prefs = [
            'version' => 'FILLED AT INSTALL',
            'show_jobs_at_login' => '0',
            'cut' => '250',
            'list_limit' => '15',
            'list_limit_max' => '50',
            'url_maxlength' => '30',
            'event_loglevel' => '5',
            'notifications_mailing' => '0',
            'admin_email' => 'admsys@localhost',
            'admin_email_name' => '',
            'from_email' => '',
            'from_email_name' => '',
            'noreply_email' => '',
            'noreply_email_name' => '',
            'replyto_email' => '',
            'replyto_email_name' => '',
            'mailing_signature' => 'SIGNATURE',
            'use_anonymous_helpdesk' => '0',
            'use_anonymous_followups' => '0',
            'language' => 'en_GB',
            'priority_1' => '#fff2f2',
            'priority_2' => '#ffe0e0',
            'priority_3' => '#ffcece',
            'priority_4' => '#ffbfbf',
            'priority_5' => '#ffadad',
            'priority_6' => '#ff5555',
            'date_tax' => '2005-12-31',
            'cas_host' => '',
            'cas_port' => '443',
            'cas_uri' => '',
            'cas_logout' => '',
            'cas_version' => 'CAS_VERSION_2_0',
            'existing_auth_server_field_clean_domain' => '0',
            'planning_begin' => '08:00:00',
            'planning_end' => '20:00:00',
            'utf8_conv' => '1',
            'use_public_faq' => '0',
            'url_base' => 'http://localhost/glpi',
            'show_link_in_mail' => '0',
            'text_login' => '',
            'founded_new_version' => '',
            'dropdown_max' => '100',
            'ajax_wildcard' => '*',
            'ajax_limit_count' => '10',
            'is_users_auto_add' => '1',
            'date_format' => '0',
            'number_format' => '0',
            'csv_delimiter' => ';',
            'is_ids_visible' => '0',
            'smtp_mode' => '0',
            'smtp_host' => '',
            'smtp_port' => '25',
            'smtp_username' => '',
            'smtp_oauth_provider' => '',
            'smtp_oauth_client_id' => '',
            'smtp_oauth_client_secret' => '',
            'smtp_oauth_options' => '{}',
            'smtp_oauth_refresh_token' => '',
            'proxy_name' => '',
            'proxy_port' => '8080',
            'proxy_user' => '',
            'add_followup_on_update_ticket' => '1',
            'keep_tickets_on_delete' => '0',
            'time_step' => '5',
            'decimal_number' => '2',
            'helpdesk_doc_url' => '',
            'central_doc_url' => '',
            'documentcategories_id_forticket' => '0',
            'monitors_management_restrict' => Config::NO_MANAGEMENT,
            'phones_management_restrict' => Config::NO_MANAGEMENT,
            'peripherals_management_restrict' => Config::NO_MANAGEMENT,
            'printers_management_restrict' => Config::NO_MANAGEMENT,
            'use_log_in_files' => '1',
            'time_offset' => '0',
            'is_contact_autoupdate' => '1',
            'is_user_autoupdate' => '1',
            'is_group_autoupdate' => '1',
            'is_location_autoupdate' => '1',
            'state_autoupdate_mode' => '0',
            'is_contact_autoclean' => '0',
            'is_user_autoclean' => '0',
            'is_group_autoclean' => '0',
            'is_location_autoclean' => '0',
            'state_autoclean_mode' => '0',
            'use_flat_dropdowntree' => '0',
            'use_flat_dropdowntree_on_search_result' => '1',
            'use_autoname_by_entity' => '1',
            'softwarecategories_id_ondelete' => '1',
            'x509_email_field' => '',
            'x509_cn_restrict' => '',
            'x509_o_restrict' => '',
            'x509_ou_restrict' => '',
            'default_mailcollector_filesize_max' => '2097152',
            'followup_private' => '0',
            'task_private' => '0',
            'default_software_helpdesk_visible' => '1',
            'names_format' => '0',
            'default_requesttypes_id' => '1',
            'use_noright_users_add' => '1',
            'cron_limit' => '5',
            'priority_matrix' => '{"1":{"1":1,"2":1,"3":2,"4":2,"5":2},"2":{"1":1,"2":2,"3":2,"4":3,"5":3},"3":{"1":2,"2":2,"3":3,"4":4,"5":4},"4":{"1":2,"2":3,"3":4,"4":4,"5":5},"5":{"1":2,"2":3,"3":4,"4":5,"5":5}}',
            'urgency_mask' => '62',
            'impact_mask' => '62',
            'user_deleted_ldap' => '0',
            'user_restored_ldap' => '0',
            'auto_create_infocoms' => '0',
            'use_slave_for_search' => '0',
            'proxy_passwd' => '',
            'smtp_passwd' => '',
            'show_count_on_tabs' => '1',
            'refresh_views' => '0',
            'set_default_tech' => '1',
            'allow_search_view' => '2',
            'allow_search_all' => '0',
            'allow_search_global' => '1',
            'display_count_on_home' => '5',
            'use_password_security' => '0',
            'password_min_length' => '8',
            'password_need_number' => '1',
            'password_need_letter' => '1',
            'password_need_caps' => '1',
            'password_need_symbol' => '1',
            'use_check_pref' => '0',
            'notification_to_myself' => '1',
            'duedateok_color' => '#06ff00',
            'duedatewarning_color' => '#ffb800',
            'duedatecritical_color' => '#ff0000',
            'duedatewarning_less' => '20',
            'duedatecritical_less' => '5',
            'duedatewarning_unit' => '%',
            'duedatecritical_unit' => '%',
            'realname_ssofield' => '',
            'firstname_ssofield' => '',
            'email1_ssofield' => '',
            'email2_ssofield' => '',
            'email3_ssofield' => '',
            'email4_ssofield' => '',
            'phone_ssofield' => '',
            'phone2_ssofield' => '',
            'mobile_ssofield' => '',
            'comment_ssofield' => '',
            'title_ssofield' => '',
            'category_ssofield' => '',
            'language_ssofield' => '',
            'entity_ssofield' => '',
            'registration_number_ssofield' => '',
            'ssovariables_id' => '0',
            'ssologout_url' => '',
            'translate_kb' => '0',
            'translate_dropdowns' => '0',
            'translate_reminders' => '0',
            'pdffont' => 'dejavusans',
            'keep_devices_when_purging_item' => '0',
            'maintenance_mode' => '0',
            'maintenance_text' => '',
            'attach_ticket_documents_to_mail' => '0',
            'backcreated' => '0',
            'task_state' => '1',
            'palette' => 'auror',
            'page_layout' => 'vertical',
            'fold_menu' => '0',
            'fold_search' => '0',
            'savedsearches_pinned' => '0',
            'timeline_order' => 'natural',
            'itil_layout' => '',
            'richtext_layout' => 'classic',
            'lock_use_lock_item' => '0',
            'lock_autolock_mode' => '1',
            'lock_directunlock_notification' => '0',
            'lock_item_list' => '[]',
            'lock_lockprofile_id' => '8',
            'set_default_requester' => '1',
            'highcontrast_css' => '0',
            'default_central_tab' => '0',
            'smtp_check_certificate' => '1',
            'enable_api' => '0',
            'enable_api_login_credentials' => '0',
            'enable_api_login_external_token' => '1',
            'url_base_api' => 'http://localhost/glpi/api',
            'login_remember_time' => '604800',
            'login_remember_default' => '1',
            'use_notifications' => '0',
            'notifications_ajax' => '0',
            'notifications_ajax_check_interval' => '5',
            'notifications_ajax_sound' => null,
            'notifications_ajax_icon_url' => '/pics/glpi.png',
            'dbversion' => 'FILLED AT INSTALL',
            'smtp_max_retries' => '5',
            'smtp_sender' => null,
            'instance_uuid' => null,
            'registration_uuid' => null,
            'smtp_retry_time' => '5',
            'purge_addrelation' => '0',
            'purge_deleterelation' => '0',
            'purge_createitem' => '0',
            'purge_deleteitem' => '0',
            'purge_restoreitem' => '0',
            'purge_updateitem' => '0',
            'purge_item_software_install' => '0',
            'purge_software_item_install' => '0',
            'purge_software_version_install' => '0',
            'purge_infocom_creation' => '0',
            'purge_profile_user' => '0',
            'purge_group_user' => '0',
            'purge_adddevice' => '0',
            'purge_updatedevice' => '0',
            'purge_deletedevice' => '0',
            'purge_connectdevice' => '0',
            'purge_disconnectdevice' => '0',
            'purge_userdeletedfromldap' => '0',
            'purge_comments' => '0',
            'purge_datemod' => '0',
            'purge_all' => '0',
            'purge_user_auth_changes' => '0',
            'purge_plugins' => '0',
            'purge_refusedequipment' => '0',
            'display_login_source' => '1',
            'devices_in_menu' => '["Item_DeviceSimcard"]',
            'password_expiration_delay' => '-1',
            'password_expiration_notice' => '-1',
            'password_expiration_lock_delay' => '-1',
            'default_dashboard_central' => 'central',
            'default_dashboard_assets' => 'assets',
            'default_dashboard_helpdesk' => 'assistance',
            'default_dashboard_mini_ticket' => 'mini_tickets',
            Impact::CONF_ENABLED => exportArrayToDB(Impact::getDefaultItemtypes()),
            // Default size corresponds to the 'upload_max_filesize' directive in Mio (rounded down) or 1 Mio if 'upload_max_filesize' is too low.
            'document_max_size' => max(1, floor(Toolbox::return_bytes_from_ini_vars(ini_get('upload_max_filesize')) / 1024 / 1024)),
            'planning_work_days' => exportArrayToDB([0, 1, 2, 3, 4, 5, 6]),
            'system_user' => self::USER_SYSTEM,
            'support_legacy_data' => 0, // New installation should not support legacy data
            'initialized_rules_collections' => '[]',
            'timeline_action_btn_layout' => 0,
            'timeline_date_format' => 0,
        ];

        $tables['glpi_configs'] = [];
        foreach ($default_prefs as $name => $value) {
            $tables['glpi_configs'][] = [
                'context' => 'core',
                'name' => $name,
                'value' => $value,
            ];
        }

        foreach (\Glpi\Inventory\Conf::getDefaults() as $name => $value) {
            $tables['glpi_configs'][] = [
                'context' => 'inventory',
                'name' => $name,
                'value' => $value,
            ];
        }

        $tables['glpi_crontasks'] = [
            [
                'id' => 2,
                'itemtype' => 'CartridgeItem',
                'name' => 'cartridge',
                'frequency' => '86400',
                'param' => 10,
                'state' => CronTask::STATE_DISABLE,
                'mode' => CronTask::MODE_INTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 3,
                'itemtype' => 'ConsumableItem',
                'name' => 'consumable',
                'frequency' => '86400',
                'param' => 10,
                'state' => CronTask::STATE_DISABLE,
                'mode' => CronTask::MODE_INTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 4,
                'itemtype' => 'SoftwareLicense',
                'name' => 'software',
                'frequency' => '86400',
                'param' => null,
                'state' => CronTask::STATE_DISABLE,
                'mode' => CronTask::MODE_INTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 5,
                'itemtype' => 'Contract',
                'name' => 'contract',
                'frequency' => '86400',
                'param' => null,
                'state' => CronTask::STATE_WAITING,
                'mode' => CronTask::MODE_INTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 6,
                'itemtype' => 'Infocom',
                'name' => 'infocom',
                'frequency' => '86400',
                'param' => null,
                'state' => CronTask::STATE_WAITING,
                'mode' => CronTask::MODE_INTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 7,
                'itemtype' => 'CronTask',
                'name' => 'logs',
                'frequency' => '86400',
                'param' => '30',
                'state' => CronTask::STATE_DISABLE,
                'mode' => CronTask::MODE_INTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 9,
                'itemtype' => 'MailCollector',
                'name' => 'mailgate',
                'frequency' => '600',
                'param' => '10',
                'state' => CronTask::STATE_WAITING,
                'mode' => CronTask::MODE_INTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 10,
                'itemtype' => 'DBconnection',
                'name' => 'checkdbreplicate',
                'frequency' => '300',
                'param' => null,
                'state' => CronTask::STATE_DISABLE,
                'mode' => CronTask::MODE_INTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 11,
                'itemtype' => 'CronTask',
                'name' => 'checkupdate',
                'frequency' => '604800',
                'param' => null,
                'state' => CronTask::STATE_DISABLE,
                'mode' => CronTask::MODE_INTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 12,
                'itemtype' => 'CronTask',
                'name' => 'session',
                'frequency' => '86400',
                'param' => null,
                'state' => CronTask::STATE_WAITING,
                'mode' => CronTask::MODE_INTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 13,
                'itemtype' => 'CronTask',
                'name' => 'graph',
                'frequency' => 3600,
                'param' => null,
                'state' => CronTask::STATE_WAITING,
                'mode' => CronTask::MODE_INTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 14,
                'itemtype' => 'ReservationItem',
                'name' => 'reservation',
                'frequency' => 3600,
                'param' => null,
                'state' => CronTask::STATE_WAITING,
                'mode' => CronTask::MODE_INTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 15,
                'itemtype' => 'Ticket',
                'name' => 'closeticket',
                'frequency' => 43200,
                'param' => null,
                'state' => CronTask::STATE_WAITING,
                'mode' => CronTask::MODE_INTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 16,
                'itemtype' => 'Ticket',
                'name' => 'alertnotclosed',
                'frequency' => 43200,
                'param' => null,
                'state' => CronTask::STATE_WAITING,
                'mode' => CronTask::MODE_INTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 17,
                'itemtype' => 'SlaLevel_Ticket',
                'name' => 'slaticket',
                'frequency' => 300,
                'param' => null,
                'state' => CronTask::STATE_WAITING,
                'mode' => CronTask::MODE_INTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 18,
                'itemtype' => 'Ticket',
                'name' => 'createinquest',
                'frequency' => 86400,
                'param' => null,
                'state' => CronTask::STATE_WAITING,
                'mode' => CronTask::MODE_INTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 19,
                'itemtype' => 'CronTask',
                'name' => 'watcher',
                'frequency' => 86400,
                'param' => null,
                'state' => CronTask::STATE_WAITING,
                'mode' => CronTask::MODE_INTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 20,
                'itemtype' => 'CommonITILRecurrentCron',
                'name' => 'RecurrentItems',
                'frequency' => 3600,
                'param' => null,
                'state' => CronTask::STATE_WAITING,
                'mode' => CronTask::MODE_INTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 21,
                'itemtype' => 'PlanningRecall',
                'name' => 'planningrecall',
                'frequency' => 300,
                'param' => null,
                'state' => CronTask::STATE_WAITING,
                'mode' => CronTask::MODE_INTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 22,
                'itemtype' => 'QueuedNotification',
                'name' => 'queuednotification',
                'frequency' => 60,
                'param' => 50,
                'state' => CronTask::STATE_WAITING,
                'mode' => CronTask::MODE_INTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 23,
                'itemtype' => 'QueuedNotification',
                'name' => 'queuednotificationclean',
                'frequency' => 86400,
                'param' => 30,
                'state' => CronTask::STATE_WAITING,
                'mode' => CronTask::MODE_INTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 24,
                'itemtype' => 'CronTask',
                'name' => 'temp',
                'frequency' => 3600,
                'param' => null,
                'state' => CronTask::STATE_WAITING,
                'mode' => CronTask::MODE_INTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 25,
                'itemtype' => 'MailCollector',
                'name' => 'mailgateerror',
                'frequency' => 86400,
                'param' => null,
                'state' => CronTask::STATE_WAITING,
                'mode' => CronTask::MODE_INTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 26,
                'itemtype' => 'CronTask',
                'name' => 'circularlogs',
                'frequency' => 86400,
                'param' => 4,
                'state' => CronTask::STATE_DISABLE,
                'mode' => CronTask::MODE_INTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 27,
                'itemtype' => 'ObjectLock',
                'name' => 'unlockobject',
                'frequency' => 86400,
                'param' => 4,
                'state' => CronTask::STATE_DISABLE,
                'mode' => CronTask::MODE_INTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 28,
                'itemtype' => 'SavedSearch',
                'name' => 'countAll',
                'frequency' => 604800,
                'param' => null,
                'state' => CronTask::STATE_DISABLE,
                'mode' => CronTask::MODE_INTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 29,
                'itemtype' => 'SavedSearch_Alert',
                'name' => 'savedsearchesalerts',
                'frequency' => 86400,
                'param' => null,
                'state' => CronTask::STATE_DISABLE,
                'mode' => CronTask::MODE_INTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 30,
                'itemtype' => 'Telemetry',
                'name' => 'telemetry',
                'frequency' => 2592000,
                'param' => null,
                'state' => CronTask::STATE_DISABLE,
                'mode' => CronTask::MODE_INTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 31,
                'itemtype' => 'Certificate',
                'name' => 'certificate',
                'frequency' => 86400,
                'param' => null,
                'state' => CronTask::STATE_WAITING,
                'mode' => CronTask::MODE_INTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 32,
                'itemtype' => 'OlaLevel_Ticket',
                'name' => 'olaticket',
                'frequency' => 300,
                'param' => null,
                'state' => CronTask::STATE_WAITING,
                'mode' => CronTask::MODE_INTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 33,
                'itemtype' => 'PurgeLogs',
                'name' => 'PurgeLogs',
                'frequency' => 604800,
                'param' => 24,
                'state' => CronTask::STATE_WAITING,
                'mode' => CronTask::MODE_EXTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 34,
                'itemtype' => 'Ticket',
                'name' => 'purgeticket',
                'frequency' => 604800,
                'param' => null,
                'state' => CronTask::STATE_DISABLE,
                'mode' => CronTask::MODE_EXTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 35,
                'itemtype' => 'Document',
                'name' => 'cleanorphans',
                'frequency' => 604800,
                'param' => null,
                'state' => CronTask::STATE_DISABLE,
                'mode' => CronTask::MODE_EXTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 36,
                'itemtype' => 'User',
                'name' => 'passwordexpiration',
                'frequency' => 86400,
                'param' => 100,
                'state' => CronTask::STATE_DISABLE,
                'mode' => CronTask::MODE_EXTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 37,
                'itemtype' => 'Glpi\\Marketplace\\Controller',
                'name' => 'checkAllUpdates',
                'frequency' => 86400,
                'param' => null,
                'state' => CronTask::STATE_WAITING,
                'mode' => CronTask::MODE_EXTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 38,
                'itemtype' => CleanSoftwareCron::getType(),
                'name' => CleanSoftwareCron::TASK_NAME,
                'frequency' => MONTH_TIMESTAMP,
                'param' => 1000,
                'state' => CronTask::STATE_DISABLE,
                'mode' => CronTask::MODE_EXTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 300,
            ], [
                'id' => 39,
                'itemtype' => 'Domain',
                'name' => 'DomainsAlert',
                'frequency' => 86400,
                'param' => null,
                'state' => CronTask::STATE_WAITING,
                'mode' => CronTask::MODE_EXTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 42,
                'itemtype' => PendingReasonCron::getType(),
                'name' => PendingReasonCron::TASK_NAME,
                'frequency' => 30 * MINUTE_TIMESTAMP,
                'param' => null,
                'state' => CronTask::STATE_WAITING,
                'mode' => CronTask::MODE_EXTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 60,
            ], [
                'id' => 40,
                'itemtype' => 'Glpi\Inventory\Inventory',
                'name' => 'cleantemp',
                'frequency' => 86400,
                'param' => null,
                'state' => CronTask::STATE_DISABLE,
                'mode' => CronTask::MODE_EXTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 41,
                'itemtype' => 'Glpi\Inventory\Inventory',
                'name' => 'cleanorphans',
                'frequency' => 604800,
                'param' => null,
                'state' => CronTask::STATE_WAITING,
                'mode' => CronTask::MODE_EXTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ], [
                'id' => 43,
                'itemtype' => 'Agent',
                'name' => 'Cleanoldagents',
                'frequency' => DAY_TIMESTAMP,
                'param' => null,
                'state' => CronTask::STATE_WAITING,
                'mode' => CronTask::MODE_EXTERNAL,
                'lastrun' => null,
                'logs_lifetime' => 30,
            ],
        ];

        $dashboards_data = include_once __DIR__ . "/migrations/update_9.4.x_to_9.5.0/dashboards.php";
        $tables['glpi_dashboards_dashboards'] = [];
        $tables['glpi_dashboards_items'] = [];
        $i = $j = 1;
        foreach ($dashboards_data as $default_dashboard) {
            $items = $default_dashboard['_items'];
            unset($default_dashboard['_items']);
            $tables['glpi_dashboards_dashboards'][] = array_merge([
                'id' => $i
            ], $default_dashboard);

            foreach ($items as $item) {
                $tables['glpi_dashboards_items'][] = array_merge([
                    'id' => $j,
                    'dashboards_dashboards_id' => $i,
                ], $item);

                $j++;
            }

            $i++;
        }

        $tables['glpi_devicememorytypes'] = [
            [
                'id' => 1,
                'name' => 'EDO',
            ],
            [
                'id' => 2,
                'name' => 'DDR',
            ],
            [
                'id' => 3,
                'name' => 'SDRAM',
            ],
            [
                'id' => 4,
                'name' => 'SDRAM-2',
            ],
        ];

        $tables['glpi_devicesimcardtypes'] = [
            [
                'id' => 1,
                'name' => 'Full SIM',
            ],
            [
                'id' => 2,
                'name' => 'Mini SIM',
            ],
            [
                'id' => 3,
                'name' => 'Micro SIM',
            ],
            [
                'id' => 4,
                'name' => 'Nano SIM',
            ],
        ];

        $tables['glpi_displaypreferences'] = [
            [
                'itemtype' => 'Computer',
                'num' => '4',
                'rank' => '4',
            ], [
                'itemtype' => 'Computer',
                'num' => '45',
                'rank' => '6',
            ], [
                'itemtype' => 'Computer',
                'num' => '40',
                'rank' => '5',
            ], [
                'itemtype' => 'Computer',
                'num' => '5',
                'rank' => '3',
            ], [
                'itemtype' => 'Computer',
                'num' => '23',
                'rank' => '2',
            ], [
                'itemtype' => 'DocumentType',
                'num' => '3',
                'rank' => '1',
            ], [
                'itemtype' => 'Monitor',
                'num' => '31',
                'rank' => '1',
            ], [
                'itemtype' => 'Monitor',
                'num' => '23',
                'rank' => '2',
            ], [
                'itemtype' => 'Monitor',
                'num' => '3',
                'rank' => '3',
            ], [
                'itemtype' => 'Monitor',
                'num' => '4',
                'rank' => '4',
            ], [
                'itemtype' => 'Printer',
                'num' => '31',
                'rank' => '1',
            ], [
                'itemtype' => 'NetworkEquipment',
                'num' => '31',
                'rank' => '1',
            ], [
                'itemtype' => 'NetworkEquipment',
                'num' => '23',
                'rank' => '2',
            ], [
                'itemtype' => 'Printer',
                'num' => '23',
                'rank' => '2',
            ], [
                'itemtype' => 'Printer',
                'num' => '3',
                'rank' => '3',
            ], [
                'itemtype' => 'Software',
                'num' => '4',
                'rank' => '3',
            ], [
                'itemtype' => 'Software',
                'num' => '5',
                'rank' => '2',
            ], [
                'itemtype' => 'Software',
                'num' => '23',
                'rank' => '1',
            ], [
                'itemtype' => 'CartridgeItem',
                'num' => '4',
                'rank' => '2',
            ], [
                'itemtype' => 'CartridgeItem',
                'num' => '34',
                'rank' => '1',
            ], [
                'itemtype' => 'Peripheral',
                'num' => '3',
                'rank' => '3',
            ], [
                'itemtype' => 'Peripheral',
                'num' => '23',
                'rank' => '2',
            ], [
                'itemtype' => 'Peripheral',
                'num' => '31',
                'rank' => '1',
            ], [
                'itemtype' => 'Computer',
                'num' => '31',
                'rank' => '1',
            ], [
                'itemtype' => 'Computer',
                'num' => '3',
                'rank' => '7',
            ], [
                'itemtype' => 'Computer',
                'num' => '19',
                'rank' => '8',
            ], [
                'itemtype' => 'Computer',
                'num' => '17',
                'rank' => '9',
            ], [
                'itemtype' => 'NetworkEquipment',
                'num' => '3',
                'rank' => '3',
            ], [
                'itemtype' => 'NetworkEquipment',
                'num' => '4',
                'rank' => '4',
            ], [
                'itemtype' => 'NetworkEquipment',
                'num' => '11',
                'rank' => '6',
            ], [
                'itemtype' => 'NetworkEquipment',
                'num' => '19',
                'rank' => '7',
            ], [
                'itemtype' => 'Printer',
                'num' => '4',
                'rank' => '4',
            ], [
                'itemtype' => 'Printer',
                'num' => '19',
                'rank' => '6',
            ], [
                'itemtype' => 'Monitor',
                'num' => '19',
                'rank' => '6',
            ], [
                'itemtype' => 'Monitor',
                'num' => '7',
                'rank' => '7',
            ], [
                'itemtype' => 'Peripheral',
                'num' => '4',
                'rank' => '4',
            ], [
                'itemtype' => 'Peripheral',
                'num' => '19',
                'rank' => '6',
            ], [
                'itemtype' => 'Peripheral',
                'num' => '7',
                'rank' => '7',
            ], [
                'itemtype' => 'Contact',
                'num' => '3',
                'rank' => '1',
            ], [
                'itemtype' => 'Contact',
                'num' => '4',
                'rank' => '2',
            ], [
                'itemtype' => 'Contact',
                'num' => '5',
                'rank' => '3',
            ], [
                'itemtype' => 'Contact',
                'num' => '6',
                'rank' => '4',
            ], [
                'itemtype' => 'Contact',
                'num' => '9',
                'rank' => '5',
            ], [
                'itemtype' => 'Supplier',
                'num' => '9',
                'rank' => '1',
            ], [
                'itemtype' => 'Supplier',
                'num' => '3',
                'rank' => '2',
            ], [
                'itemtype' => 'Supplier',
                'num' => '4',
                'rank' => '3',
            ], [
                'itemtype' => 'Supplier',
                'num' => '5',
                'rank' => '4',
            ], [
                'itemtype' => 'Supplier',
                'num' => '10',
                'rank' => '5',
            ], [
                'itemtype' => 'Supplier',
                'num' => '6',
                'rank' => '6',
            ], [
                'itemtype' => 'Contract',
                'num' => '4',
                'rank' => '1',
            ], [
                'itemtype' => 'Contract',
                'num' => '3',
                'rank' => '2',
            ], [
                'itemtype' => 'Contract',
                'num' => '5',
                'rank' => '3',
            ], [
                'itemtype' => 'Contract',
                'num' => '6',
                'rank' => '4',
            ], [
                'itemtype' => 'Contract',
                'num' => '7',
                'rank' => '5',
            ], [
                'itemtype' => 'Contract',
                'num' => '11',
                'rank' => '6',
            ], [
                'itemtype' => 'CartridgeItem',
                'num' => '23',
                'rank' => '3',
            ], [
                'itemtype' => 'CartridgeItem',
                'num' => '3',
                'rank' => '4',
            ], [
                'itemtype' => 'DocumentType',
                'num' => '6',
                'rank' => '2',
            ], [
                'itemtype' => 'DocumentType',
                'num' => '4',
                'rank' => '3',
            ], [
                'itemtype' => 'DocumentType',
                'num' => '5',
                'rank' => '4',
            ], [
                'itemtype' => 'Document',
                'num' => '3',
                'rank' => '1',
            ], [
                'itemtype' => 'Document',
                'num' => '4',
                'rank' => '2',
            ], [
                'itemtype' => 'Document',
                'num' => '7',
                'rank' => '3',
            ], [
                'itemtype' => 'Document',
                'num' => '5',
                'rank' => '4',
            ], [
                'itemtype' => 'Document',
                'num' => '16',
                'rank' => '5',
            ], [
                'itemtype' => 'User',
                'num' => '34',
                'rank' => '1',
            ], [
                'itemtype' => 'User',
                'num' => '5',
                'rank' => '3',
            ], [
                'itemtype' => 'User',
                'num' => '6',
                'rank' => '4',
            ], [
                'itemtype' => 'User',
                'num' => '3',
                'rank' => '5',
            ], [
                'itemtype' => 'ConsumableItem',
                'num' => '34',
                'rank' => '1',
            ], [
                'itemtype' => 'ConsumableItem',
                'num' => '4',
                'rank' => '2',
            ], [
                'itemtype' => 'ConsumableItem',
                'num' => '23',
                'rank' => '3',
            ], [
                'itemtype' => 'ConsumableItem',
                'num' => '3',
                'rank' => '4',
            ], [
                'itemtype' => 'NetworkEquipment',
                'num' => '40',
                'rank' => '5',
            ], [
                'itemtype' => 'Printer',
                'num' => '40',
                'rank' => '5',
            ], [
                'itemtype' => 'Monitor',
                'num' => '40',
                'rank' => '5',
            ], [
                'itemtype' => 'Peripheral',
                'num' => '40',
                'rank' => '5',
            ], [
                'itemtype' => 'User',
                'num' => '8',
                'rank' => '6',
            ], [
                'itemtype' => 'Phone',
                'num' => '31',
                'rank' => '1',
            ], [
                'itemtype' => 'Phone',
                'num' => '23',
                'rank' => '2',
            ], [
                'itemtype' => 'Phone',
                'num' => '3',
                'rank' => '3',
            ], [
                'itemtype' => 'Phone',
                'num' => '4',
                'rank' => '4',
            ], [
                'itemtype' => 'Phone',
                'num' => '40',
                'rank' => '5',
            ], [
                'itemtype' => 'Phone',
                'num' => '19',
                'rank' => '6',
            ], [
                'itemtype' => 'Phone',
                'num' => '7',
                'rank' => '7',
            ], [
                'itemtype' => 'Group',
                'num' => '16',
                'rank' => '1',
            ], [
                'itemtype' => 'AllAssets',
                'num' => '31',
                'rank' => '1',
            ], [
                'itemtype' => 'ReservationItem',
                'num' => '4',
                'rank' => '1',
            ], [
                'itemtype' => 'ReservationItem',
                'num' => '3',
                'rank' => '2',
            ], [
                'itemtype' => 'Budget',
                'num' => '3',
                'rank' => '2',
            ], [
                'itemtype' => 'Software',
                'num' => '72',
                'rank' => '4',
            ], [
                'itemtype' => 'Software',
                'num' => '163',
                'rank' => '5',
            ], [
                'itemtype' => 'Budget',
                'num' => '5',
                'rank' => '1',
            ], [
                'itemtype' => 'Budget',
                'num' => '4',
                'rank' => '3',
            ], [
                'itemtype' => 'Budget',
                'num' => '19',
                'rank' => '4',
            ], [
                'itemtype' => 'CronTask',
                'num' => '8',
                'rank' => '1',
            ], [
                'itemtype' => 'CronTask',
                'num' => '3',
                'rank' => '2',
            ], [
                'itemtype' => 'CronTask',
                'num' => '4',
                'rank' => '3',
            ], [
                'itemtype' => 'CronTask',
                'num' => '7',
                'rank' => '4',
            ], [
                'itemtype' => 'RequestType',
                'num' => '14',
                'rank' => '1',
            ], [
                'itemtype' => 'RequestType',
                'num' => '15',
                'rank' => '2',
            ], [
                'itemtype' => 'NotificationTemplate',
                'num' => '4',
                'rank' => '1',
            ], [
                'itemtype' => 'NotificationTemplate',
                'num' => '16',
                'rank' => '2',
            ], [
                'itemtype' => 'Notification',
                'num' => '5',
                'rank' => '1',
            ], [
                'itemtype' => 'Notification',
                'num' => '6',
                'rank' => '2',
            ], [
                'itemtype' => 'Notification',
                'num' => '2',
                'rank' => '3',
            ], [
                'itemtype' => 'Notification',
                'num' => '4',
                'rank' => '4',
            ], [
                'itemtype' => 'Notification',
                'num' => '80',
                'rank' => '5',
            ], [
                'itemtype' => 'Notification',
                'num' => '86',
                'rank' => '6',
            ], [
                'itemtype' => 'MailCollector',
                'num' => '2',
                'rank' => '1',
            ], [
                'itemtype' => 'MailCollector',
                'num' => '19',
                'rank' => '2',
            ], [
                'itemtype' => 'AuthLDAP',
                'num' => '3',
                'rank' => '1',
            ], [
                'itemtype' => 'AuthLDAP',
                'num' => '19',
                'rank' => '2',
            ], [
                'itemtype' => 'AuthMail',
                'num' => '3',
                'rank' => '1',
            ], [
                'itemtype' => 'AuthMail',
                'num' => '19',
                'rank' => '2',
            ], [
                'itemtype' => 'IPNetwork',
                'num' => '18',
                'rank' => '1',
            ], [
                'itemtype' => 'WifiNetwork',
                'num' => '10',
                'rank' => '1',
            ], [
                'itemtype' => 'Profile',
                'num' => '2',
                'rank' => '1',
            ], [
                'itemtype' => 'Profile',
                'num' => '3',
                'rank' => '2',
            ], [
                'itemtype' => 'Profile',
                'num' => '19',
                'rank' => '3',
            ], [
                'itemtype' => 'Transfer',
                'num' => '19',
                'rank' => '1',
            ], [
                'itemtype' => 'TicketValidation',
                'num' => '3',
                'rank' => '1',
            ], [
                'itemtype' => 'TicketValidation',
                'num' => '2',
                'rank' => '2',
            ], [
                'itemtype' => 'TicketValidation',
                'num' => '8',
                'rank' => '3',
            ], [
                'itemtype' => 'TicketValidation',
                'num' => '4',
                'rank' => '4',
            ], [
                'itemtype' => 'TicketValidation',
                'num' => '9',
                'rank' => '5',
            ], [
                'itemtype' => 'TicketValidation',
                'num' => '7',
                'rank' => '6',
            ], [
                'itemtype' => 'NotImportedEmail',
                'num' => '2',
                'rank' => '1',
            ], [
                'itemtype' => 'NotImportedEmail',
                'num' => '5',
                'rank' => '2',
            ], [
                'itemtype' => 'NotImportedEmail',
                'num' => '4',
                'rank' => '3',
            ], [
                'itemtype' => 'NotImportedEmail',
                'num' => '6',
                'rank' => '4',
            ], [
                'itemtype' => 'NotImportedEmail',
                'num' => '16',
                'rank' => '5',
            ], [
                'itemtype' => 'NotImportedEmail',
                'num' => '19',
                'rank' => '6',
            ], [
                'itemtype' => 'RuleRightParameter',
                'num' => '11',
                'rank' => '1',
            ], [
                'itemtype' => 'Ticket',
                'num' => '12',
                'rank' => '1',
            ], [
                'itemtype' => 'Ticket',
                'num' => '19',
                'rank' => '2',
            ], [
                'itemtype' => 'Ticket',
                'num' => '15',
                'rank' => '3',
            ], [
                'itemtype' => 'Ticket',
                'num' => '3',
                'rank' => '4',
            ], [
                'itemtype' => 'Ticket',
                'num' => '4',
                'rank' => '5',
            ], [
                'itemtype' => 'Ticket',
                'num' => '5',
                'rank' => '6',
            ], [
                'itemtype' => 'Ticket',
                'num' => '7',
                'rank' => '7',
            ], [
                'itemtype' => 'Calendar',
                'num' => '19',
                'rank' => '1',
            ], [
                'itemtype' => 'Holiday',
                'num' => '11',
                'rank' => '1',
            ], [
                'itemtype' => 'Holiday',
                'num' => '12',
                'rank' => '2',
            ], [
                'itemtype' => 'Holiday',
                'num' => '13',
                'rank' => '3',
            ], [
                'itemtype' => 'SLA',
                'num' => '4',
                'rank' => '1',
            ], [
                'itemtype' => 'Ticket',
                'num' => '18',
                'rank' => '8',
            ], [
                'itemtype' => 'AuthLDAP',
                'num' => '30',
                'rank' => '3',
            ], [
                'itemtype' => 'AuthMail',
                'num' => '6',
                'rank' => '3',
            ], [
                'itemtype' => 'FQDN',
                'num' => '11',
                'rank' => '1',
            ], [
                'itemtype' => 'FieldUnicity',
                'num' => '1',
                'rank' => '1',
            ], [
                'itemtype' => 'FieldUnicity',
                'num' => '80',
                'rank' => '2',
            ], [
                'itemtype' => 'FieldUnicity',
                'num' => '4',
                'rank' => '3',
            ], [
                'itemtype' => 'FieldUnicity',
                'num' => '3',
                'rank' => '4',
            ], [
                'itemtype' => 'FieldUnicity',
                'num' => '86',
                'rank' => '5',
            ], [
                'itemtype' => 'FieldUnicity',
                'num' => '30',
                'rank' => '6',
            ], [
                'itemtype' => 'Problem',
                'num' => '21',
                'rank' => '1',
            ], [
                'itemtype' => 'Problem',
                'num' => '12',
                'rank' => '2',
            ], [
                'itemtype' => 'Problem',
                'num' => '19',
                'rank' => '3',
            ], [
                'itemtype' => 'Problem',
                'num' => '15',
                'rank' => '4',
            ], [
                'itemtype' => 'Problem',
                'num' => '3',
                'rank' => '5',
            ], [
                'itemtype' => 'Problem',
                'num' => '7',
                'rank' => '6',
            ], [
                'itemtype' => 'Problem',
                'num' => '18',
                'rank' => '7',
            ], [
                'itemtype' => 'Vlan',
                'num' => '11',
                'rank' => '1',
            ], [
                'itemtype' => 'TicketRecurrent',
                'num' => '11',
                'rank' => '1',
            ], [
                'itemtype' => 'TicketRecurrent',
                'num' => '12',
                'rank' => '2',
            ], [
                'itemtype' => 'TicketRecurrent',
                'num' => '13',
                'rank' => '3',
            ], [
                'itemtype' => 'TicketRecurrent',
                'num' => '15',
                'rank' => '4',
            ], [
                'itemtype' => 'TicketRecurrent',
                'num' => '14',
                'rank' => '5',
            ], [
                'itemtype' => 'Reminder',
                'num' => '2',
                'rank' => '1',
            ], [
                'itemtype' => 'Reminder',
                'num' => '3',
                'rank' => '2',
            ], [
                'itemtype' => 'Reminder',
                'num' => '4',
                'rank' => '3',
            ], [
                'itemtype' => 'Reminder',
                'num' => '5',
                'rank' => '4',
            ], [
                'itemtype' => 'Reminder',
                'num' => '6',
                'rank' => '5',
            ], [
                'itemtype' => 'Reminder',
                'num' => '7',
                'rank' => '6',
            ], [
                'itemtype' => 'IPNetwork',
                'num' => '10',
                'rank' => '2',
            ], [
                'itemtype' => 'IPNetwork',
                'num' => '11',
                'rank' => '3',
            ], [
                'itemtype' => 'IPNetwork',
                'num' => '12',
                'rank' => '4',
            ], [
                'itemtype' => 'IPNetwork',
                'num' => '17',
                'rank' => '5',
            ], [
                'itemtype' => 'NetworkName',
                'num' => '12',
                'rank' => '1',
            ], [
                'itemtype' => 'NetworkName',
                'num' => '13',
                'rank' => '2',
            ], [
                'itemtype' => 'RSSFeed',
                'num' => '2',
                'rank' => '1',
            ], [
                'itemtype' => 'RSSFeed',
                'num' => '4',
                'rank' => '2',
            ], [
                'itemtype' => 'RSSFeed',
                'num' => '5',
                'rank' => '3',
            ], [
                'itemtype' => 'RSSFeed',
                'num' => '19',
                'rank' => '4',
            ], [
                'itemtype' => 'RSSFeed',
                'num' => '6',
                'rank' => '5',
            ], [
                'itemtype' => 'RSSFeed',
                'num' => '7',
                'rank' => '6',
            ], [
                'itemtype' => 'Blacklist',
                'num' => '12',
                'rank' => '1',
            ], [
                'itemtype' => 'Blacklist',
                'num' => '11',
                'rank' => '2',
            ], [
                'itemtype' => 'ReservationItem',
                'num' => '5',
                'rank' => '3',
            ], [
                'itemtype' => 'QueuedNotification',
                'num' => '16',
                'rank' => '1',
            ], [
                'itemtype' => 'QueuedNotification',
                'num' => '7',
                'rank' => '2',
            ], [
                'itemtype' => 'QueuedNotification',
                'num' => '20',
                'rank' => '3',
            ], [
                'itemtype' => 'QueuedNotification',
                'num' => '21',
                'rank' => '4',
            ], [
                'itemtype' => 'QueuedNotification',
                'num' => '22',
                'rank' => '5',
            ], [
                'itemtype' => 'QueuedNotification',
                'num' => '15',
                'rank' => '6',
            ], [
                'itemtype' => 'Change',
                'num' => '12',
                'rank' => '1',
            ], [
                'itemtype' => 'Change',
                'num' => '19',
                'rank' => '2',
            ], [
                'itemtype' => 'Change',
                'num' => '15',
                'rank' => '3',
            ], [
                'itemtype' => 'Change',
                'num' => '7',
                'rank' => '4',
            ], [
                'itemtype' => 'Change',
                'num' => '18',
                'rank' => '5',
            ], [
                'itemtype' => 'Project',
                'num' => '3',
                'rank' => '1',
            ], [
                'itemtype' => 'Project',
                'num' => '4',
                'rank' => '2',
            ], [
                'itemtype' => 'Project',
                'num' => '12',
                'rank' => '3',
            ], [
                'itemtype' => 'Project',
                'num' => '5',
                'rank' => '4',
            ], [
                'itemtype' => 'Project',
                'num' => '15',
                'rank' => '5',
            ], [
                'itemtype' => 'Project',
                'num' => '21',
                'rank' => '6',
            ], [
                'itemtype' => 'ProjectState',
                'num' => '12',
                'rank' => '1',
            ], [
                'itemtype' => 'ProjectState',
                'num' => '11',
                'rank' => '2',
            ], [
                'itemtype' => 'ProjectTask',
                'num' => '2',
                'rank' => '1',
            ], [
                'itemtype' => 'ProjectTask',
                'num' => '12',
                'rank' => '2',
            ], [
                'itemtype' => 'ProjectTask',
                'num' => '14',
                'rank' => '3',
            ], [
                'itemtype' => 'ProjectTask',
                'num' => '5',
                'rank' => '4',
            ], [
                'itemtype' => 'ProjectTask',
                'num' => '7',
                'rank' => '5',
            ], [
                'itemtype' => 'ProjectTask',
                'num' => '8',
                'rank' => '6',
            ], [
                'itemtype' => 'ProjectTask',
                'num' => '13',
                'rank' => '7',
            ], [
                'itemtype' => 'CartridgeItem',
                'num' => '9',
                'rank' => '5',
            ], [
                'itemtype' => 'ConsumableItem',
                'num' => '9',
                'rank' => '5',
            ], [
                'itemtype' => 'ReservationItem',
                'num' => '9',
                'rank' => '4',
            ], [
                'itemtype' => 'SoftwareLicense',
                'num' => '1',
                'rank' => '1',
            ], [
                'itemtype' => 'SoftwareLicense',
                'num' => '3',
                'rank' => '2',
            ], [
                'itemtype' => 'SoftwareLicense',
                'num' => '10',
                'rank' => '3',
            ], [
                'itemtype' => 'SoftwareLicense',
                'num' => '162',
                'rank' => '4',
            ], [
                'itemtype' => 'SoftwareLicense',
                'num' => '5',
                'rank' => '5',
            ], [
                'itemtype' => 'SavedSearch',
                'num' => '8',
                'rank' => '1',
            ], [
                'itemtype' => 'SavedSearch',
                'num' => '9',
                'rank' => '1',
            ], [
                'itemtype' => 'SavedSearch',
                'num' => '3',
                'rank' => '1',
            ], [
                'itemtype' => 'SavedSearch',
                'num' => '10',
                'rank' => '1',
            ], [
                'itemtype' => 'SavedSearch',
                'num' => '11',
                'rank' => '1',
            ], [
                'itemtype' => 'Plugin',
                'num' => '2',
                'rank' => '1',
            ], [
                'itemtype' => 'Plugin',
                'num' => '3',
                'rank' => '2',
            ], [
                'itemtype' => 'Plugin',
                'num' => '4',
                'rank' => '3',
            ], [
                'itemtype' => 'Plugin',
                'num' => '5',
                'rank' => '4',
            ], [
                'itemtype' => 'Plugin',
                'num' => '6',
                'rank' => '5',
            ], [
                'itemtype' => 'Plugin',
                'num' => '7',
                'rank' => '6',
            ], [
                'itemtype' => 'Plugin',
                'num' => '8',
                'rank' => '7',
            ]
        ];

        $ADDTODISPLAYPREF['Cluster'] = [31, 19];
        $ADDTODISPLAYPREF['Domain'] = [3, 4, 2, 6, 7];
        $ADDTODISPLAYPREF['DomainRecord'] = [2, 3];
        $ADDTODISPLAYPREF['Appliance'] = [2, 3, 4, 5];
        $ADDTODISPLAYPREF['Lockedfield'] = [3, 13, 5];
        $ADDTODISPLAYPREF['Unmanaged'] = [2, 4, 3, 5, 7, 10, 18, 14, 15, 9];
        $ADDTODISPLAYPREF['NetworkPortType'] = [10, 11, 12];
        $ADDTODISPLAYPREF['NetworkPort'] = [3, 30, 31, 32, 33, 34, 35, 36, 38, 39, 40, 6];
        $ADDTODISPLAYPREF['USBVendor'] = [10, 11];
        $ADDTODISPLAYPREF['PCIVendor'] = [10, 11];
        $ADDTODISPLAYPREF['Agent'] = [2, 4, 10, 8, 11, 6, 15];
        $ADDTODISPLAYPREF['Database'] = [2, 3, 6, 9, 10];
        $ADDTODISPLAYPREF[Socket::class] = [5, 6, 9, 8, 7];
        $ADDTODISPLAYPREF['Cable'] = [4, 31, 6, 15, 24, 8, 10, 13, 14];

        foreach ($ADDTODISPLAYPREF as $type => $options) {
            $rank = 1;
            foreach ($options as $newval) {
                $tables['glpi_displaypreferences'][] = [
                    'itemtype' => $type,
                    'num' => $newval,
                    'rank' => $rank++,
                ];
            }
        }

        $tables['glpi_documenttypes'] = [
            [
                'id' => 1,
                'name' => 'JPEG',
                'ext' => 'jpg',
                'icon' => 'jpg-dist.png',
            ], [
                'id' => 2,
                'name' => 'PNG',
                'ext' => 'png',
                'icon' => 'png-dist.png',
            ], [
                'id' => 3,
                'name' => 'GIF',
                'ext' => 'gif',
                'icon' => 'gif-dist.png',
            ], [
                'id' => '4',
                'name' => 'BMP',
                'ext' => 'bmp',
                'icon' => 'bmp-dist.png',
            ], [
                'id' => '5',
                'name' => 'Photoshop',
                'ext' => 'psd',
                'icon' => 'psd-dist.png',
            ], [
                'id' => '6',
                'name' => 'TIFF',
                'ext' => 'tif',
                'icon' => 'tif-dist.png',
            ], [
                'id' => '7',
                'name' => 'AIFF',
                'ext' => 'aiff',
                'icon' => 'aiff-dist.png',
            ], [
                'id' => '8',
                'name' => 'Windows Media',
                'ext' => 'asf',
                'icon' => 'asf-dist.png',
            ], [
                'id' => '9',
                'name' => 'Windows Media',
                'ext' => 'avi',
                'icon' => 'avi-dist.png',
            ], [
                'id' => '44',
                'name' => 'C source',
                'ext' => 'c',
                'icon' => 'c-dist.png',
            ], [
                'id' => '27',
                'name' => 'RealAudio',
                'ext' => 'rm',
                'icon' => 'rm-dist.png',
            ], [
                'id' => '16',
                'name' => 'Midi',
                'ext' => 'mid',
                'icon' => 'mid-dist.png',
            ], [
                'id' => '17',
                'name' => 'QuickTime',
                'ext' => 'mov',
                'icon' => 'mov-dist.png',
            ], [
                'id' => '18',
                'name' => 'MP3',
                'ext' => 'mp3',
                'icon' => 'mp3-dist.png',
            ], [
                'id' => '19',
                'name' => 'MPEG',
                'ext' => 'mpg',
                'icon' => 'mpg-dist.png',
            ], [
                'id' => '20',
                'name' => 'Ogg Vorbis',
                'ext' => 'ogg',
                'icon' => 'ogg-dist.png',
            ], [
                'id' => '24',
                'name' => 'QuickTime',
                'ext' => 'qt',
                'icon' => 'qt-dist.png',
            ], [
                'id' => '10',
                'name' => 'BZip',
                'ext' => 'bz2',
                'icon' => 'bz2-dist.png',
            ], [
                'id' => '25',
                'name' => 'RealAudio',
                'ext' => 'ra',
                'icon' => 'ra-dist.png',
            ], [
                'id' => '26',
                'name' => 'RealAudio',
                'ext' => 'ram',
                'icon' => 'ram-dist.png',
            ], [
                'id' => '11',
                'name' => 'Word',
                'ext' => 'doc',
                'icon' => 'doc-dist.png',
            ], [
                'id' => '12',
                'name' => 'DjVu',
                'ext' => 'djvu',
                'icon' => '',
            ], [
                'id' => '42',
                'name' => 'MNG',
                'ext' => 'mng',
                'icon' => '',
            ], [
                'id' => '13',
                'name' => 'PostScript',
                'ext' => 'eps',
                'icon' => 'ps-dist.png',
            ], [
                'id' => '14',
                'name' => 'GZ',
                'ext' => 'gz',
                'icon' => 'gz-dist.png',
            ], [
                'id' => '37',
                'name' => 'WAV',
                'ext' => 'wav',
                'icon' => 'wav-dist.png',
            ], [
                'id' => '15',
                'name' => 'HTML',
                'ext' => 'html',
                'icon' => 'html-dist.png',
            ], [
                'id' => '34',
                'name' => 'Flash',
                'ext' => 'swf',
                'icon' => 'swf-dist.png',
            ], [
                'id' => '21',
                'name' => 'PDF',
                'ext' => 'pdf',
                'icon' => 'pdf-dist.png',
            ], [
                'id' => '22',
                'name' => 'PowerPoint',
                'ext' => 'ppt',
                'icon' => 'ppt-dist.png',
            ], [
                'id' => '23',
                'name' => 'PostScript',
                'ext' => 'ps',
                'icon' => 'ps-dist.png',
            ], [
                'id' => '40',
                'name' => 'Windows Media',
                'ext' => 'wmv',
                'icon' => 'wmv-dist.png',
            ], [
                'id' => '28',
                'name' => 'RTF',
                'ext' => 'rtf',
                'icon' => 'rtf-dist.png',
            ], [
                'id' => '29',
                'name' => 'StarOffice',
                'ext' => 'sdd',
                'icon' => 'sdd-dist.png',
            ], [
                'id' => '30',
                'name' => 'StarOffice',
                'ext' => 'sdw',
                'icon' => 'sdw-dist.png',
            ], [
                'id' => '31',
                'name' => 'Stuffit',
                'ext' => 'sit',
                'icon' => 'sit-dist.png',
            ], [
                'id' => '43',
                'name' => 'Adobe Illustrator',
                'ext' => 'ai',
                'icon' => 'ai-dist.png',
            ], [
                'id' => '32',
                'name' => 'OpenOffice Impress',
                'ext' => 'sxi',
                'icon' => 'sxi-dist.png',
            ], [
                'id' => '33',
                'name' => 'OpenOffice',
                'ext' => 'sxw',
                'icon' => 'sxw-dist.png',
            ], [
                'id' => '46',
                'name' => 'DVI',
                'ext' => 'dvi',
                'icon' => 'dvi-dist.png',
            ], [
                'id' => '35',
                'name' => 'TGZ',
                'ext' => 'tgz',
                'icon' => 'tgz-dist.png',
            ], [
                'id' => '36',
                'name' => 'texte',
                'ext' => 'txt',
                'icon' => 'txt-dist.png',
            ], [
                'id' => '49',
                'name' => 'RedHat/Mandrake/SuSE',
                'ext' => 'rpm',
                'icon' => 'rpm-dist.png',
            ], [
                'id' => '38',
                'name' => 'Excel',
                'ext' => 'xls',
                'icon' => 'xls-dist.png',
            ], [
                'id' => '39',
                'name' => 'XML',
                'ext' => 'xml',
                'icon' => 'xml-dist.png',
            ], [
                'id' => '41',
                'name' => 'Zip',
                'ext' => 'zip',
                'icon' => 'zip-dist.png',
            ], [
                'id' => '45',
                'name' => 'Debian',
                'ext' => 'deb',
                'icon' => 'deb-dist.png',
            ], [
                'id' => '47',
                'name' => 'C header',
                'ext' => 'h',
                'icon' => 'h-dist.png',
            ], [
                'id' => '48',
                'name' => 'Pascal',
                'ext' => 'pas',
                'icon' => 'pas-dist.png',
            ], [
                'id' => '50',
                'name' => 'OpenOffice Calc',
                'ext' => 'sxc',
                'icon' => 'sxc-dist.png',
            ], [
                'id' => '51',
                'name' => 'LaTeX',
                'ext' => 'tex',
                'icon' => 'tex-dist.png',
            ], [
                'id' => '52',
                'name' => 'GIMP multi-layer',
                'ext' => 'xcf',
                'icon' => 'xcf-dist.png',
            ], [
                'id' => '53',
                'name' => 'JPEG',
                'ext' => 'jpeg',
                'icon' => 'jpg-dist.png',
            ], [
                'id' => '54',
                'name' => 'Oasis Open Office Writer',
                'ext' => 'odt',
                'icon' => 'odt-dist.png',
            ], [
                'id' => '55',
                'name' => 'Oasis Open Office Calc',
                'ext' => 'ods',
                'icon' => 'ods-dist.png',
            ], [
                'id' => '56',
                'name' => 'Oasis Open Office Impress',
                'ext' => 'odp',
                'icon' => 'odp-dist.png',
            ], [
                'id' => '57',
                'name' => 'Oasis Open Office Impress Template',
                'ext' => 'otp',
                'icon' => 'odp-dist.png',
            ], [
                'id' => '58',
                'name' => 'Oasis Open Office Writer Template',
                'ext' => 'ott',
                'icon' => 'odt-dist.png',
            ], [
                'id' => '59',
                'name' => 'Oasis Open Office Calc Template',
                'ext' => 'ots',
                'icon' => 'ods-dist.png',
            ], [
                'id' => '60',
                'name' => 'Oasis Open Office Math',
                'ext' => 'odf',
                'icon' => 'odf-dist.png',
            ], [
                'id' => '61',
                'name' => 'Oasis Open Office Draw',
                'ext' => 'odg',
                'icon' => 'odg-dist.png',
            ], [
                'id' => '62',
                'name' => 'Oasis Open Office Draw Template',
                'ext' => 'otg',
                'icon' => 'odg-dist.png',
            ], [
                'id' => '63',
                'name' => 'Oasis Open Office Base',
                'ext' => 'odb',
                'icon' => 'odb-dist.png',
            ], [
                'id' => '64',
                'name' => 'Oasis Open Office HTML',
                'ext' => 'oth',
                'icon' => 'oth-dist.png',
            ], [
                'id' => '65',
                'name' => 'Oasis Open Office Writer Master',
                'ext' => 'odm',
                'icon' => 'odm-dist.png',
            ], [
                'id' => '66',
                'name' => 'Oasis Open Office Chart',
                'ext' => 'odc',
                'icon' => '',
            ], [
                'id' => '67',
                'name' => 'Oasis Open Office Image',
                'ext' => 'odi',
                'icon' => '',
            ], [
                'id' => '68',
                'name' => 'Word XML',
                'ext' => 'docx',
                'icon' => 'doc-dist.png',
            ], [
                'id' => '69',
                'name' => 'Excel XML',
                'ext' => 'xlsx',
                'icon' => 'xls-dist.png',
            ], [
                'id' => '70',
                'name' => 'PowerPoint XML',
                'ext' => 'pptx',
                'icon' => 'ppt-dist.png',
            ], [
                'id' => '71',
                'name' => 'Comma-Separated Values',
                'ext' => 'csv',
                'icon' => 'csv-dist.png',
            ], [
                'id' => '72',
                'name' => 'Scalable Vector Graphics',
                'ext' => 'svg',
                'icon' => 'svg-dist.png',
            ],
        ];

        $tables['glpi_entities'] = [
            [
                'id' => 0,
                'name' => __('Root entity'),
                'entities_id' => null,
                'completename' => __('Root entity'),
                'comment' => null,
                'level' => 1,
                'cartridges_alert_repeat' => 0,
                'consumables_alert_repeat' => 0,
                'use_licenses_alert' => 0,
                'send_licenses_alert_before_delay' => 0,
                'use_certificates_alert' => 0,
                'send_certificates_alert_before_delay' => 0,
                'certificates_alert_repeat_interval' => 0,
                'use_contracts_alert' => 0,
                'send_contracts_alert_before_delay' => 0,
                'use_infocoms_alert' => 0,
                'send_infocoms_alert_before_delay' => 0,
                'use_reservations_alert' => 0,
                'autoclose_delay' => -10,
                'notclosed_delay' => 0,
                'calendars_strategy' => 0,
                'auto_assign_mode' => -10,
                'tickettype' => 1,
                'inquest_config' => 1,
                'inquest_rate' => 0,
                'inquest_delay' => 0,
                'autofill_warranty_date' => 0,
                'autofill_use_date' => 0,
                'autofill_buy_date' => 0,
                'autofill_delivery_date' => 0,
                'autofill_order_date' => 0,
                'tickettemplates_strategy' => 0,
                'tickettemplates_id' => 1,
                'changetemplates_strategy' => 0,
                'changetemplates_id' => 1,
                'problemtemplates_strategy' => 0,
                'problemtemplates_id' => 1,
                'entities_strategy_software' => -10,
                'default_contract_alert' => 0,
                'default_infocom_alert' => 0,
                'default_cartridges_alarm_threshold' => 10,
                'default_consumables_alarm_threshold' => 10,
                'delay_send_emails' => 0,
                'is_notif_enable_default' => 1,
                'autofill_decommission_date' => 0,
                'suppliers_as_private' => 0,
                'enable_custom_css' => 0,
                'anonymize_support_agents' => 0,
                'display_users_initials' => 1,
                'contracts_strategy_default' => 0,
                'transfers_strategy' => 0,
            ],
        ];

        $tables['glpi_filesystems'] = [
            [
                'id' => 1,
                'name' => 'ext',
            ],
            [
                'id' => 2,
                'name' => 'ext2',
            ],
            [
                'id' => 3,
                'name' => 'ext3',
            ],
            [
                'id' => 4,
                'name' => 'ext4',
            ],
            [
                'id' => 5,
                'name' => 'FAT',
            ],
            [
                'id' => 6,
                'name' => 'FAT32',
            ],
            [
                'id' => 7,
                'name' => 'VFAT',
            ],
            [
                'id' => 8,
                'name' => 'HFS',
            ],
            [
                'id' => 9,
                'name' => 'HPFS',
            ],
            [
                'id' => 10,
                'name' => 'HTFS',
            ],
            [
                'id' => 11,
                'name' => 'JFS',
            ],
            [
                'id' => 12,
                'name' => 'JFS2',
            ],
            [
                'id' => 13,
                'name' => 'NFS',
            ],
            [
                'id' => 14,
                'name' => 'NTFS',
            ],
            [
                'id' => 15,
                'name' => 'ReiserFS',
            ],
            [
                'id' => 16,
                'name' => 'SMBFS',
            ],
            [
                'id' => 17,
                'name' => 'UDF',
            ],
            [
                'id' => 18,
                'name' => 'UFS',
            ],
            [
                'id' => 19,
                'name' => 'XFS',
            ],
            [
                'id' => 20,
                'name' => 'ZFS',
            ],
            [
                'id' => 21,
                'name' => 'APFS',
            ],
        ];

        $tables['glpi_interfacetypes'] = [
            [
                'id' => 1,
                'name' => 'IDE',
            ],
            [
                'id' => 2,
                'name' => 'SATA',
            ],
            [
                'id' => 3,
                'name' => 'SCSI',
            ],
            [
                'id' => 4,
                'name' => 'USB',
            ],
            [
                'id' => 5,
                'name' => 'AGP',
            ],
            [
                'id' => 6,
                'name' => 'PCI',
            ],
            [
                'id' => 7,
                'name' => 'PCIe',
            ],
            [
                'id' => 8,
                'name' => 'PCI-X',
            ],
        ];

        $tables['glpi_notifications'] = [
            [
                'id' => 1,
                'name' => 'Alert Tickets not closed',
                'itemtype' => 'Ticket',
                'event' => 'alertnotclosed',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 2,
                'name' => 'New Ticket',
                'itemtype' => 'Ticket',
                'event' => 'new',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 3,
                'name' => 'Update Ticket',
                'itemtype' => 'Ticket',
                'event' => 'update',
                'is_recursive' => 1,
                'is_active' => 0,
            ], [
                'id' => 4,
                'name' => 'Close Ticket',
                'itemtype' => 'Ticket',
                'event' => 'closed',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 5,
                'name' => 'Add Followup',
                'itemtype' => 'Ticket',
                'event' => 'add_followup',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 6,
                'name' => 'Add Task',
                'itemtype' => 'Ticket',
                'event' => 'add_task',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 7,
                'name' => 'Update Followup',
                'itemtype' => 'Ticket',
                'event' => 'update_followup',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 8,
                'name' => 'Update Task',
                'itemtype' => 'Ticket',
                'event' => 'update_task',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 9,
                'name' => 'Delete Followup',
                'itemtype' => 'Ticket',
                'event' => 'delete_followup',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 10,
                'name' => 'Delete Task',
                'itemtype' => 'Ticket',
                'event' => 'delete_task',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 11,
                'name' => 'Resolve ticket',
                'itemtype' => 'Ticket',
                'event' => 'solved',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 12,
                'name' => 'Ticket Validation',
                'itemtype' => 'Ticket',
                'event' => 'validation',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 13,
                'name' => 'New Reservation',
                'itemtype' => 'Reservation',
                'event' => 'new',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 14,
                'name' => 'Update Reservation',
                'itemtype' => 'Reservation',
                'event' => 'update',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 15,
                'name' => 'Delete Reservation',
                'itemtype' => 'Reservation',
                'event' => 'delete',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 16,
                'name' => 'Alert Reservation',
                'itemtype' => 'Reservation',
                'event' => 'alert',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 17,
                'name' => 'Contract Notice',
                'itemtype' => 'Contract',
                'event' => 'notice',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 18,
                'name' => 'Contract End',
                'itemtype' => 'Contract',
                'event' => 'end',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 19,
                'name' => 'MySQL Synchronization',
                'itemtype' => 'DBConnection',
                'event' => 'desynchronization',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 20,
                'name' => 'Cartridges',
                'itemtype' => 'CartridgeItem',
                'event' => 'alert',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 21,
                'name' => 'Consumables',
                'itemtype' => 'ConsumableItem',
                'event' => 'alert',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 22,
                'name' => 'Infocoms',
                'itemtype' => 'Infocom',
                'event' => 'alert',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 23,
                'name' => 'Software Licenses',
                'itemtype' => 'SoftwareLicense',
                'event' => 'alert',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 24,
                'name' => 'Ticket Recall',
                'itemtype' => 'Ticket',
                'event' => 'recall',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 25,
                'name' => 'Password Forget',
                'itemtype' => 'User',
                'event' => 'passwordforget',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 26,
                'name' => 'Ticket Satisfaction',
                'itemtype' => 'Ticket',
                'event' => 'satisfaction',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 27,
                'name' => 'Item not unique',
                'itemtype' => 'FieldUnicity',
                'event' => 'refuse',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 28,
                'name' => 'CronTask Watcher',
                'itemtype' => 'CronTask',
                'event' => 'alert',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 29,
                'name' => 'New Problem',
                'itemtype' => 'Problem',
                'event' => 'new',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 30,
                'name' => 'Update Problem',
                'itemtype' => 'Problem',
                'event' => 'update',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 31,
                'name' => 'Resolve Problem',
                'itemtype' => 'Problem',
                'event' => 'solved',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 32,
                'name' => 'Add Task',
                'itemtype' => 'Problem',
                'event' => 'add_task',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 33,
                'name' => 'Update Task',
                'itemtype' => 'Problem',
                'event' => 'update_task',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 34,
                'name' => 'Delete Task',
                'itemtype' => 'Problem',
                'event' => 'delete_task',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 35,
                'name' => 'Close Problem',
                'itemtype' => 'Problem',
                'event' => 'closed',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 36,
                'name' => 'Delete Problem',
                'itemtype' => 'Problem',
                'event' => 'delete',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 37,
                'name' => 'Ticket Validation Answer',
                'itemtype' => 'Ticket',
                'event' => 'validation_answer',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 38,
                'name' => 'Contract End Periodicity',
                'itemtype' => 'Contract',
                'event' => 'periodicity',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 39,
                'name' => 'Contract Notice Periodicity',
                'itemtype' => 'Contract',
                'event' => 'periodicitynotice',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 40,
                'name' => 'Planning recall',
                'itemtype' => 'PlanningRecall',
                'event' => 'planningrecall',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 41,
                'name' => 'Delete Ticket',
                'itemtype' => 'Ticket',
                'event' => 'delete',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 42,
                'name' => 'New Change',
                'itemtype' => 'Change',
                'event' => 'new',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 43,
                'name' => 'Update Change',
                'itemtype' => 'Change',
                'event' => 'update',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 44,
                'name' => 'Resolve Change',
                'itemtype' => 'Change',
                'event' => 'solved',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 45,
                'name' => 'Add Task',
                'itemtype' => 'Change',
                'event' => 'add_task',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 46,
                'name' => 'Update Task',
                'itemtype' => 'Change',
                'event' => 'update_task',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 47,
                'name' => 'Delete Task',
                'itemtype' => 'Change',
                'event' => 'delete_task',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 48,
                'name' => 'Close Change',
                'itemtype' => 'Change',
                'event' => 'closed',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 49,
                'name' => 'Delete Change',
                'itemtype' => 'Change',
                'event' => 'delete',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 50,
                'name' => 'Ticket Satisfaction Answer',
                'itemtype' => 'Ticket',
                'event' => 'replysatisfaction',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 51,
                'name' => 'Receiver errors',
                'itemtype' => 'MailCollector',
                'event' => 'error',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 52,
                'name' => 'New Project',
                'itemtype' => 'Project',
                'event' => 'new',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 53,
                'name' => 'Update Project',
                'itemtype' => 'Project',
                'event' => 'update',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 54,
                'name' => 'Delete Project',
                'itemtype' => 'Project',
                'event' => 'delete',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 55,
                'name' => 'New Project Task',
                'itemtype' => 'ProjectTask',
                'event' => 'new',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 56,
                'name' => 'Update Project Task',
                'itemtype' => 'ProjectTask',
                'event' => 'update',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 57,
                'name' => 'Delete Project Task',
                'itemtype' => 'ProjectTask',
                'event' => 'delete',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 58,
                'name' => 'Request Unlock Items',
                'itemtype' => 'ObjectLock',
                'event' => 'unlock',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 59,
                'name' => 'New user in requesters',
                'itemtype' => 'Ticket',
                'event' => 'requester_user',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 60,
                'name' => 'New group in requesters',
                'itemtype' => 'Ticket',
                'event' => 'requester_group',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 61,
                'name' => 'New user in observers',
                'itemtype' => 'Ticket',
                'event' => 'observer_user',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 62,
                'name' => 'New group in observers',
                'itemtype' => 'Ticket',
                'event' => 'observer_group',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 63,
                'name' => 'New user in assignees',
                'itemtype' => 'Ticket',
                'event' => 'assign_user',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 64,
                'name' => 'New group in assignees',
                'itemtype' => 'Ticket',
                'event' => 'assign_group',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 65,
                'name' => 'New supplier in assignees',
                'itemtype' => 'Ticket',
                'event' => 'assign_supplier',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 66,
                'name' => 'Saved searches',
                'itemtype' => 'SavedSearch_Alert',
                'event' => 'alert',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 67,
                'name' => 'Certificates',
                'itemtype' => 'Certificate',
                'event' => 'alert',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 68,
                'name' => 'Alert expired domains',
                'itemtype' => 'Domain',
                'event' => 'ExpiredDomains',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 69,
                'name' => 'Alert domains close expiries',
                'itemtype' => 'Domain',
                'event' => 'DomainsWhichExpire',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 70,
                'name' => 'Password expires alert',
                'itemtype' => 'User',
                'event' => 'passwordexpires',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 71,
                'name' => 'Check plugin updates',
                'itemtype' => 'Glpi\\Marketplace\\Controller',
                'event' => 'checkpluginsupdate',
                'is_recursive' => 1,
                'is_active' => 1,
            ], [
                'id' => 72,
                'name' => 'New user mentioned',
                'itemtype' => 'Ticket',
                'event' => 'user_mention',
                'is_recursive' => 1,
                'is_active' => 1,
            ],
        ];

        $tables['glpi_notifications_notificationtemplates'] = [
            [
                'id' => 1,
                'notifications_id' => '1',
                'mode' => 'mailing',
                'notificationtemplates_id' => 6,
            ], [
                'id' => 2,
                'notifications_id' => '2',
                'mode' => 'mailing',
                'notificationtemplates_id' => 4,
            ], [
                'id' => 3,
                'notifications_id' => '3',
                'mode' => 'mailing',
                'notificationtemplates_id' => 4,
            ], [
                'id' => 4,
                'notifications_id' => '4',
                'mode' => 'mailing',
                'notificationtemplates_id' => 4,
            ], [
                'id' => 5,
                'notifications_id' => '5',
                'mode' => 'mailing',
                'notificationtemplates_id' => 4,
            ], [
                'id' => 6,
                'notifications_id' => '6',
                'mode' => 'mailing',
                'notificationtemplates_id' => 4,
            ], [
                'id' => 7,
                'notifications_id' => '7',
                'mode' => 'mailing',
                'notificationtemplates_id' => 4,
            ], [
                'id' => 8,
                'notifications_id' => '8',
                'mode' => 'mailing',
                'notificationtemplates_id' => 4,
            ], [
                'id' => 9,
                'notifications_id' => '9',
                'mode' => 'mailing',
                'notificationtemplates_id' => 4,
            ], [
                'id' => 10,
                'notifications_id' => '10',
                'mode' => 'mailing',
                'notificationtemplates_id' => 4,
            ], [
                'id' => 11,
                'notifications_id' => '11',
                'mode' => 'mailing',
                'notificationtemplates_id' => 4,
            ], [
                'id' => 12,
                'notifications_id' => '12',
                'mode' => 'mailing',
                'notificationtemplates_id' => 7,
            ], [
                'id' => 13,
                'notifications_id' => '13',
                'mode' => 'mailing',
                'notificationtemplates_id' => 2,
            ], [
                'id' => 14,
                'notifications_id' => '14',
                'mode' => 'mailing',
                'notificationtemplates_id' => 2,
            ], [
                'id' => 15,
                'notifications_id' => '15',
                'mode' => 'mailing',
                'notificationtemplates_id' => 2,
            ], [
                'id' => 16,
                'notifications_id' => '16',
                'mode' => 'mailing',
                'notificationtemplates_id' => 3,
            ], [
                'id' => 17,
                'notifications_id' => '17',
                'mode' => 'mailing',
                'notificationtemplates_id' => 12,
            ], [
                'id' => 18,
                'notifications_id' => '18',
                'mode' => 'mailing',
                'notificationtemplates_id' => 12,
            ], [
                'id' => 19,
                'notifications_id' => '19',
                'mode' => 'mailing',
                'notificationtemplates_id' => 1,
            ], [
                'id' => 20,
                'notifications_id' => '20',
                'mode' => 'mailing',
                'notificationtemplates_id' => 8,
            ], [
                'id' => 21,
                'notifications_id' => '21',
                'mode' => 'mailing',
                'notificationtemplates_id' => 9,
            ], [
                'id' => 22,
                'notifications_id' => '22',
                'mode' => 'mailing',
                'notificationtemplates_id' => 10,
            ], [
                'id' => 23,
                'notifications_id' => '23',
                'mode' => 'mailing',
                'notificationtemplates_id' => 11,
            ], [
                'id' => 24,
                'notifications_id' => '24',
                'mode' => 'mailing',
                'notificationtemplates_id' => 4,
            ], [
                'id' => 25,
                'notifications_id' => '25',
                'mode' => 'mailing',
                'notificationtemplates_id' => 13,
            ], [
                'id' => 26,
                'notifications_id' => '26',
                'mode' => 'mailing',
                'notificationtemplates_id' => 14,
            ], [
                'id' => 27,
                'notifications_id' => '27',
                'mode' => 'mailing',
                'notificationtemplates_id' => 15,
            ], [
                'id' => 28,
                'notifications_id' => '28',
                'mode' => 'mailing',
                'notificationtemplates_id' => 16,
            ], [
                'id' => 29,
                'notifications_id' => '29',
                'mode' => 'mailing',
                'notificationtemplates_id' => 17,
            ], [
                'id' => 30,
                'notifications_id' => '30',
                'mode' => 'mailing',
                'notificationtemplates_id' => 17,
            ], [
                'id' => 31,
                'notifications_id' => '31',
                'mode' => 'mailing',
                'notificationtemplates_id' => 17,
            ], [
                'id' => 32,
                'notifications_id' => '32',
                'mode' => 'mailing',
                'notificationtemplates_id' => 17,
            ], [
                'id' => 33,
                'notifications_id' => '33',
                'mode' => 'mailing',
                'notificationtemplates_id' => 17,
            ], [
                'id' => 34,
                'notifications_id' => '34',
                'mode' => 'mailing',
                'notificationtemplates_id' => 17,
            ], [
                'id' => 35,
                'notifications_id' => '35',
                'mode' => 'mailing',
                'notificationtemplates_id' => 17,
            ], [
                'id' => 36,
                'notifications_id' => '36',
                'mode' => 'mailing',
                'notificationtemplates_id' => 17,
            ], [
                'id' => 37,
                'notifications_id' => '37',
                'mode' => 'mailing',
                'notificationtemplates_id' => 7,
            ], [
                'id' => 38,
                'notifications_id' => '38',
                'mode' => 'mailing',
                'notificationtemplates_id' => 12,
            ], [
                'id' => 39,
                'notifications_id' => '39',
                'mode' => 'mailing',
                'notificationtemplates_id' => 12,
            ], [
                'id' => 40,
                'notifications_id' => '40',
                'mode' => 'mailing',
                'notificationtemplates_id' => 18,
            ], [
                'id' => 41,
                'notifications_id' => '41',
                'mode' => 'mailing',
                'notificationtemplates_id' => 4,
            ], [
                'id' => 42,
                'notifications_id' => '42',
                'mode' => 'mailing',
                'notificationtemplates_id' => 19,
            ], [
                'id' => 43,
                'notifications_id' => '43',
                'mode' => 'mailing',
                'notificationtemplates_id' => 19,
            ], [
                'id' => 44,
                'notifications_id' => '44',
                'mode' => 'mailing',
                'notificationtemplates_id' => 19,
            ], [
                'id' => 45,
                'notifications_id' => '45',
                'mode' => 'mailing',
                'notificationtemplates_id' => 19,
            ], [
                'id' => 46,
                'notifications_id' => '46',
                'mode' => 'mailing',
                'notificationtemplates_id' => 19,
            ], [
                'id' => 47,
                'notifications_id' => '47',
                'mode' => 'mailing',
                'notificationtemplates_id' => 19,
            ], [
                'id' => 48,
                'notifications_id' => '48',
                'mode' => 'mailing',
                'notificationtemplates_id' => 19,
            ], [
                'id' => 49,
                'notifications_id' => '49',
                'mode' => 'mailing',
                'notificationtemplates_id' => 19,
            ], [
                'id' => 50,
                'notifications_id' => '50',
                'mode' => 'mailing',
                'notificationtemplates_id' => 14,
            ], [
                'id' => 51,
                'notifications_id' => '51',
                'mode' => 'mailing',
                'notificationtemplates_id' => 20,
            ], [
                'id' => 52,
                'notifications_id' => '52',
                'mode' => 'mailing',
                'notificationtemplates_id' => 21,
            ], [
                'id' => 53,
                'notifications_id' => '53',
                'mode' => 'mailing',
                'notificationtemplates_id' => 21,
            ], [
                'id' => 54,
                'notifications_id' => '54',
                'mode' => 'mailing',
                'notificationtemplates_id' => 21,
            ], [
                'id' => 55,
                'notifications_id' => '55',
                'mode' => 'mailing',
                'notificationtemplates_id' => 22,
            ], [
                'id' => 56,
                'notifications_id' => '56',
                'mode' => 'mailing',
                'notificationtemplates_id' => 22,
            ], [
                'id' => 57,
                'notifications_id' => '57',
                'mode' => 'mailing',
                'notificationtemplates_id' => 22,
            ], [
                'id' => 58,
                'notifications_id' => '58',
                'mode' => 'mailing',
                'notificationtemplates_id' => 23,
            ], [
                'id' => 59,
                'notifications_id' => '59',
                'mode' => 'mailing',
                'notificationtemplates_id' => 4,
            ], [
                'id' => 60,
                'notifications_id' => '60',
                'mode' => 'mailing',
                'notificationtemplates_id' => 4,
            ], [
                'id' => 61,
                'notifications_id' => '61',
                'mode' => 'mailing',
                'notificationtemplates_id' => 4,
            ], [
                'id' => 62,
                'notifications_id' => '62',
                'mode' => 'mailing',
                'notificationtemplates_id' => 4,
            ], [
                'id' => 63,
                'notifications_id' => '63',
                'mode' => 'mailing',
                'notificationtemplates_id' => 4,
            ], [
                'id' => 64,
                'notifications_id' => '64',
                'mode' => 'mailing',
                'notificationtemplates_id' => 4,
            ], [
                'id' => 65,
                'notifications_id' => '65',
                'mode' => 'mailing',
                'notificationtemplates_id' => 4,
            ], [
                'id' => 66,
                'notifications_id' => '66',
                'mode' => 'mailing',
                'notificationtemplates_id' => 24,
            ], [
                'id' => 67,
                'notifications_id' => '67',
                'mode' => 'mailing',
                'notificationtemplates_id' => 25,
            ], [
                'id' => 68,
                'notifications_id' => '68',
                'mode' => 'mailing',
                'notificationtemplates_id' => 26,
            ], [
                'id' => 69,
                'notifications_id' => '69',
                'mode' => 'mailing',
                'notificationtemplates_id' => 26,
            ], [
                'id' => 70,
                'notifications_id' => '70',
                'mode' => 'mailing',
                'notificationtemplates_id' => 27,
            ], [
                'id' => 71,
                'notifications_id' => '71',
                'mode' => 'mailing',
                'notificationtemplates_id' => 28,
            ], [
                'id' => 72,
                'notifications_id' => '72',
                'mode' => 'mailing',
                'notificationtemplates_id' => 4,
            ],
        ];

        $tables['glpi_notificationtargets'] = [
            [
                'id' => '1',
                'items_id' => '3',
                'type' => '1',
                'notifications_id' => '13',
            ], [
                'id' => '2',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '13',
            ], [
                'id' => '3',
                'items_id' => '3',
                'type' => '2',
                'notifications_id' => '2',
            ], [
                'id' => '4',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '2',
            ], [
                'id' => '5',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '3',
            ], [
                'id' => '6',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '5',
            ], [
                'id' => '7',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '4',
            ], [
                'id' => '8',
                'items_id' => '2',
                'type' => '1',
                'notifications_id' => '3',
            ], [
                'id' => '9',
                'items_id' => '4',
                'type' => '1',
                'notifications_id' => '3',
            ], [
                'id' => '10',
                'items_id' => '3',
                'type' => '1',
                'notifications_id' => '2',
            ], [
                'id' => '11',
                'items_id' => '3',
                'type' => '1',
                'notifications_id' => '3',
            ], [
                'id' => '12',
                'items_id' => '3',
                'type' => '1',
                'notifications_id' => '5',
            ], [
                'id' => '13',
                'items_id' => '3',
                'type' => '1',
                'notifications_id' => '4',
            ], [
                'id' => '14',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '19',
            ], [
                'id' => '15',
                'items_id' => '14',
                'type' => '1',
                'notifications_id' => '12',
            ], [
                'id' => '16',
                'items_id' => '3',
                'type' => '1',
                'notifications_id' => '14',
            ], [
                'id' => '17',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '14',
            ], [
                'id' => '18',
                'items_id' => '3',
                'type' => '1',
                'notifications_id' => '15',
            ], [
                'id' => '19',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '15',
            ], [
                'id' => '20',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '6',
            ], [
                'id' => '21',
                'items_id' => '3',
                'type' => '1',
                'notifications_id' => '6',
            ], [
                'id' => '22',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '7',
            ], [
                'id' => '23',
                'items_id' => '3',
                'type' => '1',
                'notifications_id' => '7',
            ], [
                'id' => '24',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '8',
            ], [
                'id' => '25',
                'items_id' => '3',
                'type' => '1',
                'notifications_id' => '8',
            ], [
                'id' => '26',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '9',
            ], [
                'id' => '27',
                'items_id' => '3',
                'type' => '1',
                'notifications_id' => '9',
            ], [
                'id' => '28',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '10',
            ], [
                'id' => '29',
                'items_id' => '3',
                'type' => '1',
                'notifications_id' => '10',
            ], [
                'id' => '30',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '11',
            ], [
                'id' => '31',
                'items_id' => '3',
                'type' => '1',
                'notifications_id' => '11',
            ], [
                'id' => '32',
                'items_id' => '19',
                'type' => '1',
                'notifications_id' => '25',
            ], [
                'id' => '33',
                'items_id' => '3',
                'type' => '1',
                'notifications_id' => '26',
            ], [
                'id' => '34',
                'items_id' => '21',
                'type' => '1',
                'notifications_id' => '2',
            ], [
                'id' => '35',
                'items_id' => '21',
                'type' => '1',
                'notifications_id' => '3',
            ], [
                'id' => '36',
                'items_id' => '21',
                'type' => '1',
                'notifications_id' => '5',
            ], [
                'id' => '37',
                'items_id' => '21',
                'type' => '1',
                'notifications_id' => '4',
            ], [
                'id' => '38',
                'items_id' => '21',
                'type' => '1',
                'notifications_id' => '6',
            ], [
                'id' => '39',
                'items_id' => '21',
                'type' => '1',
                'notifications_id' => '7',
            ], [
                'id' => '40',
                'items_id' => '21',
                'type' => '1',
                'notifications_id' => '8',
            ], [
                'id' => '41',
                'items_id' => '21',
                'type' => '1',
                'notifications_id' => '9',
            ], [
                'id' => '42',
                'items_id' => '21',
                'type' => '1',
                'notifications_id' => '10',
            ], [
                'id' => '43',
                'items_id' => '21',
                'type' => '1',
                'notifications_id' => '11',
            ], [
                'id' => '75',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '41',
            ], [
                'id' => '46',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '28',
            ], [
                'id' => '47',
                'items_id' => '3',
                'type' => '1',
                'notifications_id' => '29',
            ], [
                'id' => '48',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '29',
            ], [
                'id' => '49',
                'items_id' => '21',
                'type' => '1',
                'notifications_id' => '29',
            ], [
                'id' => '50',
                'items_id' => '2',
                'type' => '1',
                'notifications_id' => '30',
            ], [
                'id' => '51',
                'items_id' => '4',
                'type' => '1',
                'notifications_id' => '30',
            ], [
                'id' => '52',
                'items_id' => '3',
                'type' => '1',
                'notifications_id' => '30',
            ], [
                'id' => '53',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '30',
            ], [
                'id' => '54',
                'items_id' => '21',
                'type' => '1',
                'notifications_id' => '30',
            ], [
                'id' => '55',
                'items_id' => '3',
                'type' => '1',
                'notifications_id' => '31',
            ], [
                'id' => '56',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '31',
            ], [
                'id' => '57',
                'items_id' => '21',
                'type' => '1',
                'notifications_id' => '31',
            ], [
                'id' => '58',
                'items_id' => '3',
                'type' => '1',
                'notifications_id' => '32',
            ], [
                'id' => '59',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '32',
            ], [
                'id' => '60',
                'items_id' => '21',
                'type' => '1',
                'notifications_id' => '32',
            ], [
                'id' => '61',
                'items_id' => '3',
                'type' => '1',
                'notifications_id' => '33',
            ], [
                'id' => '62',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '33',
            ], [
                'id' => '63',
                'items_id' => '21',
                'type' => '1',
                'notifications_id' => '33',
            ], [
                'id' => '64',
                'items_id' => '3',
                'type' => '1',
                'notifications_id' => '34',
            ], [
                'id' => '65',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '34',
            ], [
                'id' => '66',
                'items_id' => '21',
                'type' => '1',
                'notifications_id' => '34',
            ], [
                'id' => '67',
                'items_id' => '3',
                'type' => '1',
                'notifications_id' => '35',
            ], [
                'id' => '68',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '35',
            ], [
                'id' => '69',
                'items_id' => '21',
                'type' => '1',
                'notifications_id' => '35',
            ], [
                'id' => '70',
                'items_id' => '3',
                'type' => '1',
                'notifications_id' => '36',
            ], [
                'id' => '71',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '36',
            ], [
                'id' => '72',
                'items_id' => '21',
                'type' => '1',
                'notifications_id' => '36',
            ], [
                'id' => '73',
                'items_id' => '14',
                'type' => '1',
                'notifications_id' => '37',
            ], [
                'id' => '74',
                'items_id' => '3',
                'type' => '1',
                'notifications_id' => '40',
            ], [
                'id' => '76',
                'items_id' => '3',
                'type' => '1',
                'notifications_id' => '42',
            ], [
                'id' => '77',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '42',
            ], [
                'id' => '78',
                'items_id' => '21',
                'type' => '1',
                'notifications_id' => '42',
            ], [
                'id' => '79',
                'items_id' => '2',
                'type' => '1',
                'notifications_id' => '43',
            ], [
                'id' => '80',
                'items_id' => '4',
                'type' => '1',
                'notifications_id' => '43',
            ], [
                'id' => '81',
                'items_id' => '3',
                'type' => '1',
                'notifications_id' => '43',
            ], [
                'id' => '82',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '43',
            ], [
                'id' => '83',
                'items_id' => '21',
                'type' => '1',
                'notifications_id' => '43',
            ], [
                'id' => '84',
                'items_id' => '3',
                'type' => '1',
                'notifications_id' => '44',
            ], [
                'id' => '85',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '44',
            ], [
                'id' => '86',
                'items_id' => '21',
                'type' => '1',
                'notifications_id' => '44',
            ], [
                'id' => '87',
                'items_id' => '3',
                'type' => '1',
                'notifications_id' => '45',
            ], [
                'id' => '88',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '45',
            ], [
                'id' => '89',
                'items_id' => '21',
                'type' => '1',
                'notifications_id' => '45',
            ], [
                'id' => '90',
                'items_id' => '3',
                'type' => '1',
                'notifications_id' => '46',
            ], [
                'id' => '91',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '46',
            ], [
                'id' => '92',
                'items_id' => '21',
                'type' => '1',
                'notifications_id' => '46',
            ], [
                'id' => '93',
                'items_id' => '3',
                'type' => '1',
                'notifications_id' => '47',
            ], [
                'id' => '94',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '47',
            ], [
                'id' => '95',
                'items_id' => '21',
                'type' => '1',
                'notifications_id' => '47',
            ], [
                'id' => '96',
                'items_id' => '3',
                'type' => '1',
                'notifications_id' => '48',
            ], [
                'id' => '97',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '48',
            ], [
                'id' => '98',
                'items_id' => '21',
                'type' => '1',
                'notifications_id' => '48',
            ], [
                'id' => '99',
                'items_id' => '3',
                'type' => '1',
                'notifications_id' => '49',
            ], [
                'id' => '100',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '49',
            ], [
                'id' => '101',
                'items_id' => '21',
                'type' => '1',
                'notifications_id' => '49',
            ], [
                'id' => '102',
                'items_id' => '3',
                'type' => '1',
                'notifications_id' => '50',
            ], [
                'id' => '103',
                'items_id' => '2',
                'type' => '1',
                'notifications_id' => '50',
            ], [
                'id' => '104',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '51',
            ], [
                'id' => '105',
                'items_id' => '27',
                'type' => '1',
                'notifications_id' => '52',
            ], [
                'id' => '106',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '52',
            ], [
                'id' => '107',
                'items_id' => '28',
                'type' => '1',
                'notifications_id' => '52',
            ], [
                'id' => '108',
                'items_id' => '27',
                'type' => '1',
                'notifications_id' => '53',
            ], [
                'id' => '109',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '53',
            ], [
                'id' => '110',
                'items_id' => '28',
                'type' => '1',
                'notifications_id' => '53',
            ], [
                'id' => '111',
                'items_id' => '27',
                'type' => '1',
                'notifications_id' => '54',
            ], [
                'id' => '112',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '54',
            ], [
                'id' => '113',
                'items_id' => '28',
                'type' => '1',
                'notifications_id' => '54',
            ], [
                'id' => '114',
                'items_id' => '31',
                'type' => '1',
                'notifications_id' => '55',
            ], [
                'id' => '115',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '55',
            ], [
                'id' => '116',
                'items_id' => '32',
                'type' => '1',
                'notifications_id' => '55',
            ], [
                'id' => '117',
                'items_id' => '31',
                'type' => '1',
                'notifications_id' => '56',
            ], [
                'id' => '118',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '56',
            ], [
                'id' => '119',
                'items_id' => '32',
                'type' => '1',
                'notifications_id' => '56',
            ], [
                'id' => '120',
                'items_id' => '31',
                'type' => '1',
                'notifications_id' => '57',
            ], [
                'id' => '121',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '57',
            ], [
                'id' => '122',
                'items_id' => '32',
                'type' => '1',
                'notifications_id' => '57',
            ], [
                'id' => '123',
                'items_id' => '19',
                'type' => '1',
                'notifications_id' => '58',
            ], [
                'id' => '124',
                'items_id' => '3',
                'type' => '1',
                'notifications_id' => '59',
            ], [
                'id' => '125',
                'items_id' => '13',
                'type' => '1',
                'notifications_id' => '60',
            ], [
                'id' => '126',
                'items_id' => '21',
                'type' => '1',
                'notifications_id' => '61',
            ], [
                'id' => '127',
                'items_id' => '20',
                'type' => '1',
                'notifications_id' => '62',
            ], [
                'id' => '128',
                'items_id' => '2',
                'type' => '1',
                'notifications_id' => '63',
            ], [
                'id' => '129',
                'items_id' => '9',
                'type' => '1',
                'notifications_id' => '64',
            ], [
                'id' => '130',
                'items_id' => '8',
                'type' => '1',
                'notifications_id' => '65',
            ], [
                'id' => '131',
                'items_id' => '19',
                'type' => '1',
                'notifications_id' => '66',
            ], [
                'id' => '132',
                'items_id' => '5',
                'type' => '1',
                'notifications_id' => '67',
            ], [
                'id' => '133',
                'items_id' => '23',
                'type' => '1',
                'notifications_id' => '67',
            ], [
                'id' => '134',
                'items_id' => '5',
                'type' => '1',
                'notifications_id' => '68',
            ], [
                'id' => '135',
                'items_id' => '23',
                'type' => '1',
                'notifications_id' => '68',
            ], [
                'id' => '136',
                'items_id' => '5',
                'type' => '1',
                'notifications_id' => '69',
            ], [
                'id' => '137',
                'items_id' => '23',
                'type' => '1',
                'notifications_id' => '69',
            ], [
                'id' => '138',
                'items_id' => '19',
                'type' => '1',
                'notifications_id' => '70',
            ], [
                'id' => '139',
                'items_id' => '1',
                'type' => '1',
                'notifications_id' => '71',
            ], [
                'id' => '140',
                'items_id' => '39',
                'type' => '1',
                'notifications_id' => '72',
            ],
        ];

        $tables['glpi_notificationtemplates'] = [
            [
                'id' => '1',
                'name' => 'MySQL Synchronization',
                'itemtype' => 'DBConnection',
            ], [
                'id' => '2',
                'name' => 'Reservations',
                'itemtype' => 'Reservation',
            ], [
                'id' => '3',
                'name' => 'Alert Reservation',
                'itemtype' => 'Reservation',
            ], [
                'id' => '4',
                'name' => 'Tickets',
                'itemtype' => 'Ticket',
            ], [
                'id' => '5',
                'name' => 'Tickets (Simple)',
                'itemtype' => 'Ticket',
            ], [
                'id' => '6',
                'name' => 'Alert Tickets not closed',
                'itemtype' => 'Ticket',
            ], [
                'id' => '7',
                'name' => 'Tickets Validation',
                'itemtype' => 'Ticket',
            ], [
                'id' => '8',
                'name' => 'Cartridges',
                'itemtype' => 'CartridgeItem',
            ], [
                'id' => '9',
                'name' => 'Consumables',
                'itemtype' => 'ConsumableItem',
            ], [
                'id' => '10',
                'name' => 'Infocoms',
                'itemtype' => 'Infocom',
            ], [
                'id' => '11',
                'name' => 'Licenses',
                'itemtype' => 'SoftwareLicense',
            ], [
                'id' => '12',
                'name' => 'Contracts',
                'itemtype' => 'Contract',
            ], [
                'id' => '13',
                'name' => 'Password Forget',
                'itemtype' => 'User',
            ], [
                'id' => '14',
                'name' => 'Ticket Satisfaction',
                'itemtype' => 'Ticket',
            ], [
                'id' => '15',
                'name' => 'Item not unique',
                'itemtype' => 'FieldUnicity',
            ], [
                'id' => '16',
                'name' => 'CronTask',
                'itemtype' => 'CronTask',
            ], [
                'id' => '17',
                'name' => 'Problems',
                'itemtype' => 'Problem',
            ], [
                'id' => '18',
                'name' => 'Planning recall',
                'itemtype' => 'PlanningRecall',
            ], [
                'id' => '19',
                'name' => 'Changes',
                'itemtype' => 'Change',
            ], [
                'id' => '20',
                'name' => 'Receiver errors',
                'itemtype' => 'MailCollector',
            ], [
                'id' => '21',
                'name' => 'Projects',
                'itemtype' => 'Project',
            ], [
                'id' => '22',
                'name' => 'Project Tasks',
                'itemtype' => 'ProjectTask',
            ], [
                'id' => '23',
                'name' => 'Unlock Item request',
                'itemtype' => 'ObjectLock',
            ], [
                'id' => '24',
                'name' => 'Saved searches alerts',
                'itemtype' => 'SavedSearch_Alert',
            ], [
                'id' => '25',
                'name' => 'Certificates',
                'itemtype' => 'Certificate',
            ], [
                'id' => '26',
                'name' => 'Alert domains',
                'itemtype' => 'Domain',
            ], [
                'id' => '27',
                'name' => 'Password expires alert',
                'itemtype' => 'User',
            ], [
                'id' => '28',
                'name' => 'Plugin updates',
                'itemtype' => 'Glpi\\Marketplace\\Controller',
            ],
        ];

        $tables['glpi_notificationtemplatetranslations'] = [
            [
                'id' => '1',
                'notificationtemplates_id' => '1',
                'language' => '',
                'subject' => '##lang.dbconnection.title##',
                'content_text' => '##lang.dbconnection.delay## : ##dbconnection.delay##',
                'content_html' => '&lt;p&gt;##lang.dbconnection.delay## : ##dbconnection.delay##&lt;/p&gt;',
            ], [
                'id' => '2',
                'notificationtemplates_id' => '2',
                'language' => '',
                'subject' => '##reservation.action##',
                'content_text' => '======================================================================
##lang.reservation.user##: ##reservation.user##
##lang.reservation.item.name##: ##reservation.itemtype## - ##reservation.item.name##
##IFreservation.tech## ##lang.reservation.tech## ##reservation.tech## ##ENDIFreservation.tech##
##lang.reservation.begin##: ##reservation.begin##
##lang.reservation.end##: ##reservation.end##
##lang.reservation.comment##: ##reservation.comment##
======================================================================',
                'content_html' => '&lt;!-- description{ color: inherit; background: #ebebeb;border-style: solid;border-color: #8d8d8d; border-width: 0px 1px 1px 0px; } --&gt;
&lt;p&gt;&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.reservation.user##:&lt;/span&gt;##reservation.user##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.reservation.item.name##:&lt;/span&gt;##reservation.itemtype## - ##reservation.item.name##&lt;br /&gt;##IFreservation.tech## ##lang.reservation.tech## ##reservation.tech####ENDIFreservation.tech##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.reservation.begin##:&lt;/span&gt; ##reservation.begin##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.reservation.end##:&lt;/span&gt;##reservation.end##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.reservation.comment##:&lt;/span&gt; ##reservation.comment##&lt;/p&gt;',
            ], [
                'id' => '3',
                'notificationtemplates_id' => '3',
                'language' => '',
                'subject' => '##reservation.action##  ##reservation.entity##',
                'content_text' => '##lang.reservation.entity## : ##reservation.entity##


##FOREACHreservations##
##lang.reservation.itemtype## : ##reservation.itemtype##

 ##lang.reservation.item## : ##reservation.item##

 ##reservation.url##

 ##ENDFOREACHreservations##',
                'content_html' => '&lt;p&gt;##lang.reservation.entity## : ##reservation.entity## &lt;br /&gt; &lt;br /&gt;
##FOREACHreservations## &lt;br /&gt;##lang.reservation.itemtype## :  ##reservation.itemtype##&lt;br /&gt;
 ##lang.reservation.item## :  ##reservation.item##&lt;br /&gt; &lt;br /&gt;
 &lt;a href="##reservation.url##"&gt; ##reservation.url##&lt;/a&gt;&lt;br /&gt;
 ##ENDFOREACHreservations##&lt;/p&gt;',
            ], [
                'id' => '4',
                'notificationtemplates_id' => '4',
                'language' => '',
                'subject' => '##ticket.action## ##ticket.title##',
                'content_text' => ' ##IFticket.storestatus=5##
 ##lang.ticket.url## : ##ticket.urlapprove##
 ##lang.ticket.autoclosewarning##
 ##lang.ticket.solvedate## : ##ticket.solvedate##
 ##lang.ticket.solution.type## : ##ticket.solution.type##
 ##lang.ticket.solution.description## : ##ticket.solution.description## ##ENDIFticket.storestatus##
 ##ELSEticket.storestatus## ##lang.ticket.url## : ##ticket.url## ##ENDELSEticket.storestatus##

 ##lang.ticket.description##

 ##lang.ticket.title## : ##ticket.title##
 ##lang.ticket.authors## : ##IFticket.authors## ##ticket.authors## ##ENDIFticket.authors## ##ELSEticket.authors##--##ENDELSEticket.authors##
 ##lang.ticket.creationdate## : ##ticket.creationdate##
 ##lang.ticket.closedate## : ##ticket.closedate##
 ##lang.ticket.requesttype## : ##ticket.requesttype##
##lang.ticket.item.name## :

##FOREACHitems##

 ##IFticket.itemtype##
  ##ticket.itemtype## - ##ticket.item.name##
  ##IFticket.item.model## ##lang.ticket.item.model## : ##ticket.item.model## ##ENDIFticket.item.model##
  ##IFticket.item.serial## ##lang.ticket.item.serial## : ##ticket.item.serial## ##ENDIFticket.item.serial##
  ##IFticket.item.otherserial## ##lang.ticket.item.otherserial## : ##ticket.item.otherserial## ##ENDIFticket.item.otherserial##
 ##ENDIFticket.itemtype##

##ENDFOREACHitems##
##IFticket.assigntousers## ##lang.ticket.assigntousers## : ##ticket.assigntousers## ##ENDIFticket.assigntousers##
 ##lang.ticket.status## : ##ticket.status##
##IFticket.assigntogroups## ##lang.ticket.assigntogroups## : ##ticket.assigntogroups## ##ENDIFticket.assigntogroups##
 ##lang.ticket.urgency## : ##ticket.urgency##
 ##lang.ticket.impact## : ##ticket.impact##
 ##lang.ticket.priority## : ##ticket.priority##
##IFticket.user.email## ##lang.ticket.user.email## : ##ticket.user.email ##ENDIFticket.user.email##
##IFticket.category## ##lang.ticket.category## : ##ticket.category## ##ENDIFticket.category## ##ELSEticket.category## ##lang.ticket.nocategoryassigned## ##ENDELSEticket.category##
 ##lang.ticket.content## : ##ticket.content##
 ##IFticket.storestatus=6##

 ##lang.ticket.solvedate## : ##ticket.solvedate##
 ##lang.ticket.solution.type## : ##ticket.solution.type##
 ##lang.ticket.solution.description## : ##ticket.solution.description##
 ##ENDIFticket.storestatus##

##FOREACHtimelineitems##
[##timelineitems.date##]
##lang.timelineitems.author## ##timelineitems.author##
##lang.timelineitems.description## ##timelineitems.description##
##lang.timelineitems.date## ##timelineitems.date##
##lang.timelineitems.position## ##timelineitems.position##
##lang.timelineitems.type## ##timelineitems.type##
##lang.timelineitems.typename## ##timelineitems.typename##
##ENDFOREACHtimelineitems##

##lang.ticket.numberoffollowups## : ##ticket.numberoffollowups##
##lang.ticket.numberoftasks## : ##ticket.numberoftasks##',
                'content_html' => '&lt;!-- description{ color: inherit; background: #ebebeb; border-style: solid;border-color: #8d8d8d; border-width: 0px 1px 1px 0px; }    --&gt;
&lt;div&gt;##IFticket.storestatus=5##&lt;/div&gt;
&lt;div&gt;##lang.ticket.url## : &lt;a href="##ticket.urlapprove##"&gt;##ticket.urlapprove##&lt;/a&gt; &lt;strong&gt;&#160;&lt;/strong&gt;&lt;/div&gt;
&lt;div&gt;&lt;strong&gt;##lang.ticket.autoclosewarning##&lt;/strong&gt;&lt;/div&gt;
&lt;div&gt;&lt;span style="color: #888888;"&gt;&lt;strong&gt;&lt;span style="text-decoration: underline;"&gt;##lang.ticket.solvedate##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##ticket.solvedate##&lt;br /&gt;&lt;span style="text-decoration: underline; color: #888888;"&gt;&lt;strong&gt;##lang.ticket.solution.type##&lt;/strong&gt;&lt;/span&gt; : ##ticket.solution.type##&lt;br /&gt;&lt;span style="text-decoration: underline; color: #888888;"&gt;&lt;strong&gt;##lang.ticket.solution.description##&lt;/strong&gt;&lt;/span&gt; : ##ticket.solution.description## ##ENDIFticket.storestatus##&lt;/div&gt;
&lt;div&gt;##ELSEticket.storestatus## ##lang.ticket.url## : &lt;a href="##ticket.url##"&gt;##ticket.url##&lt;/a&gt; ##ENDELSEticket.storestatus##&lt;/div&gt;
&lt;p class="description b"&gt;&lt;strong&gt;##lang.ticket.description##&lt;/strong&gt;&lt;/p&gt;
&lt;p&gt;&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.ticket.title##&lt;/span&gt;&#160;:##ticket.title## &lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.ticket.authors##&lt;/span&gt;&#160;:##IFticket.authors## ##ticket.authors## ##ENDIFticket.authors##    ##ELSEticket.authors##--##ENDELSEticket.authors## &lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.ticket.creationdate##&lt;/span&gt;&#160;:##ticket.creationdate## &lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.ticket.closedate##&lt;/span&gt;&#160;:##ticket.closedate## &lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.ticket.requesttype##&lt;/span&gt;&#160;:##ticket.requesttype##&lt;br /&gt;
&lt;br /&gt;&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.ticket.item.name##&lt;/span&gt;&#160;:
&lt;p&gt;##FOREACHitems##&lt;/p&gt;
&lt;div class="description b"&gt;##IFticket.itemtype## ##ticket.itemtype##&#160;- ##ticket.item.name## ##IFticket.item.model## ##lang.ticket.item.model## : ##ticket.item.model## ##ENDIFticket.item.model## ##IFticket.item.serial## ##lang.ticket.item.serial## : ##ticket.item.serial## ##ENDIFticket.item.serial## ##IFticket.item.otherserial## ##lang.ticket.item.otherserial## : ##ticket.item.otherserial## ##ENDIFticket.item.otherserial## ##ENDIFticket.itemtype## &lt;/div&gt;&lt;br /&gt;
&lt;p&gt;##ENDFOREACHitems##&lt;/p&gt;
##IFticket.assigntousers## &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.ticket.assigntousers##&lt;/span&gt;&#160;: ##ticket.assigntousers## ##ENDIFticket.assigntousers##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.ticket.status## &lt;/span&gt;&#160;: ##ticket.status##&lt;br /&gt; ##IFticket.assigntogroups## &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.ticket.assigntogroups##&lt;/span&gt;&#160;: ##ticket.assigntogroups## ##ENDIFticket.assigntogroups##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.ticket.urgency##&lt;/span&gt;&#160;: ##ticket.urgency##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.ticket.impact##&lt;/span&gt;&#160;: ##ticket.impact##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.ticket.priority##&lt;/span&gt;&#160;: ##ticket.priority## &lt;br /&gt; ##IFticket.user.email##&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.ticket.user.email##&lt;/span&gt;&#160;: ##ticket.user.email ##ENDIFticket.user.email##    &lt;br /&gt; ##IFticket.category##&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.ticket.category## &lt;/span&gt;&#160;:##ticket.category## ##ENDIFticket.category## ##ELSEticket.category## ##lang.ticket.nocategoryassigned## ##ENDELSEticket.category##    &lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.ticket.content##&lt;/span&gt;&#160;: ##ticket.content##&lt;/p&gt;
&lt;br /&gt;##IFticket.storestatus=6##&lt;br /&gt;&lt;span style="text-decoration: underline;"&gt;&lt;strong&gt;&lt;span style="color: #888888;"&gt;##lang.ticket.solvedate##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##ticket.solvedate##&lt;br /&gt;&lt;span style="color: #888888;"&gt;&lt;strong&gt;&lt;span style="text-decoration: underline;"&gt;##lang.ticket.solution.type##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##ticket.solution.type##&lt;br /&gt;&lt;span style="text-decoration: underline; color: #888888;"&gt;&lt;strong&gt;##lang.ticket.solution.description##&lt;/strong&gt;&lt;/span&gt; : ##ticket.solution.description##&lt;br /&gt;##ENDIFticket.storestatus##&lt;/p&gt;
&lt;p&gt;##FOREACHtimelineitems##&lt;/p&gt;
&lt;div class="description b"&gt;&lt;br /&gt;&lt;strong&gt; [##timelineitems.date##]&lt;/strong&gt;&lt;br /&gt;&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.timelineitems.author## &lt;/span&gt; &lt;span style="color: #000000; font-weight: bold; text-decoration: underline;"&gt;##timelineitems.author##&lt;/span&gt;&lt;br /&gt;&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.timelineitems.description## &lt;/span&gt; &lt;span style="color: #000000; font-weight: bold; text-decoration: underline;"&gt;##timelineitems.description##&lt;/span&gt;&lt;br /&gt;&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.timelineitems.date## &lt;/span&gt; &lt;span style="color: #000000; font-weight: bold; text-decoration: underline;"&gt;##timelineitems.date##&lt;/span&gt;&lt;br /&gt;&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.timelineitems.position## &lt;/span&gt;&lt;span style="color: #000000; font-weight: bold; text-decoration: underline;"&gt; ##timelineitems.position##&lt;/span&gt;&lt;/div&gt;
&lt;div class="description b"&gt;&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.timelineitems.type## &lt;/span&gt;&lt;span style="color: #000000; font-weight: bold; text-decoration: underline;"&gt; ##timelineitems.type##&lt;/span&gt;&lt;/div&gt;
&lt;div class="description b"&gt;&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.timelineitems.typename## &lt;/span&gt; &lt;span style="color: #000000; font-weight: bold; text-decoration: underline;"&gt;##timelineitems.typename##&lt;/span&gt;&lt;/div&gt;
&lt;p&gt;##ENDFOREACHtimelineitems##&lt;/p&gt;
&lt;div class="description b"&gt;##lang.ticket.numberoffollowups##&#160;: ##ticket.numberoffollowups##&lt;/div&gt;
&lt;div class="description b"&gt;##lang.ticket.numberoftasks##&#160;: ##ticket.numberoftasks##&lt;/div&gt;',
            ], [
                'id' => '5',
                'notificationtemplates_id' => '12',
                'language' => '',
                'subject' => '##contract.action##  ##contract.entity##',
                'content_text' => '##lang.contract.entity## : ##contract.entity##

##FOREACHcontracts##
##lang.contract.name## : ##contract.name##
##lang.contract.number## : ##contract.number##
##lang.contract.time## : ##contract.time##
##IFcontract.type####lang.contract.type## : ##contract.type####ENDIFcontract.type##
##contract.url##
##ENDFOREACHcontracts##',
                'content_html' => '&lt;p&gt;##lang.contract.entity## : ##contract.entity##&lt;br /&gt;
&lt;br /&gt;##FOREACHcontracts##&lt;br /&gt;##lang.contract.name## :
##contract.name##&lt;br /&gt;
##lang.contract.number## : ##contract.number##&lt;br /&gt;
##lang.contract.time## : ##contract.time##&lt;br /&gt;
##IFcontract.type####lang.contract.type## : ##contract.type##
##ENDIFcontract.type##&lt;br /&gt;
&lt;a href="##contract.url##"&gt;
##contract.url##&lt;/a&gt;&lt;br /&gt;
##ENDFOREACHcontracts##&lt;/p&gt;',
            ], [
                'id' => '6',
                'notificationtemplates_id' => '5',
                'language' => '',
                'subject' => '##ticket.action## ##ticket.title##',
                'content_text' => '##lang.ticket.url## : ##ticket.url##

##lang.ticket.description##


##lang.ticket.title##  :##ticket.title##

##lang.ticket.authors##  :##IFticket.authors##
##ticket.authors## ##ENDIFticket.authors##
##ELSEticket.authors##--##ENDELSEticket.authors##

##IFticket.category## ##lang.ticket.category##  :##ticket.category##
##ENDIFticket.category## ##ELSEticket.category##
##lang.ticket.nocategoryassigned## ##ENDELSEticket.category##

##lang.ticket.content##  : ##ticket.content##
##IFticket.itemtype##
##lang.ticket.item.name##  : ##ticket.itemtype## - ##ticket.item.name##
##ENDIFticket.itemtype##',
                'content_html' => '&lt;div&gt;##lang.ticket.url## : &lt;a href="##ticket.url##"&gt;
##ticket.url##&lt;/a&gt;&lt;/div&gt;
&lt;div class="description b"&gt;
##lang.ticket.description##&lt;/div&gt;
&lt;p&gt;&lt;span
style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;
##lang.ticket.title##&lt;/span&gt;&#160;:##ticket.title##
&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;
##lang.ticket.authors##&lt;/span&gt;
##IFticket.authors## ##ticket.authors##
##ENDIFticket.authors##
##ELSEticket.authors##--##ENDELSEticket.authors##
&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;
&lt;/span&gt;&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; &lt;/span&gt;
##IFticket.category##&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;
##lang.ticket.category## &lt;/span&gt;&#160;:##ticket.category##
##ENDIFticket.category## ##ELSEticket.category##
##lang.ticket.nocategoryassigned## ##ENDELSEticket.category##
&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;
##lang.ticket.content##&lt;/span&gt;&#160;:
##ticket.content##&lt;br /&gt;##IFticket.itemtype##
&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;
##lang.ticket.item.name##&lt;/span&gt;&#160;:
##ticket.itemtype## - ##ticket.item.name##
##ENDIFticket.itemtype##&lt;/p&gt;',
            ], [
                'id' => '15',
                'notificationtemplates_id' => '15',
                'language' => '',
                'subject' => '##lang.unicity.action##',
                'content_text' => '##lang.unicity.entity## : ##unicity.entity##

##lang.unicity.itemtype## : ##unicity.itemtype##

##lang.unicity.message## : ##unicity.message##

##lang.unicity.action_user## : ##unicity.action_user##

##lang.unicity.action_type## : ##unicity.action_type##

##lang.unicity.date## : ##unicity.date##',
                'content_html' => '&lt;p&gt;##lang.unicity.entity## : ##unicity.entity##&lt;/p&gt;
&lt;p&gt;##lang.unicity.itemtype## : ##unicity.itemtype##&lt;/p&gt;
&lt;p&gt;##lang.unicity.message## : ##unicity.message##&lt;/p&gt;
&lt;p&gt;##lang.unicity.action_user## : ##unicity.action_user##&lt;/p&gt;
&lt;p&gt;##lang.unicity.action_type## : ##unicity.action_type##&lt;/p&gt;
&lt;p&gt;##lang.unicity.date## : ##unicity.date##&lt;/p&gt;',
            ], [
                'id' => '7',
                'notificationtemplates_id' => '7',
                'language' => '',
                'subject' => '##ticket.action## ##ticket.title##',
                'content_text' => '##FOREACHvalidations##

##IFvalidation.storestatus=2##
##validation.submission.title##
##lang.validation.commentsubmission## : ##validation.commentsubmission##
##ENDIFvalidation.storestatus##
##ELSEvalidation.storestatus## ##validation.answer.title## ##ENDELSEvalidation.storestatus##

##lang.ticket.url## : ##ticket.urlvalidation##

##IFvalidation.status## ##lang.validation.status## : ##validation.status## ##ENDIFvalidation.status##
##IFvalidation.commentvalidation##
##lang.validation.commentvalidation## : ##validation.commentvalidation##
##ENDIFvalidation.commentvalidation##
##ENDFOREACHvalidations##',
                'content_html' => '&lt;div&gt;##FOREACHvalidations##&lt;/div&gt;
&lt;p&gt;##IFvalidation.storestatus=2##&lt;/p&gt;
&lt;div&gt;##validation.submission.title##&lt;/div&gt;
&lt;div&gt;##lang.validation.commentsubmission## : ##validation.commentsubmission##&lt;/div&gt;
&lt;div&gt;##ENDIFvalidation.storestatus##&lt;/div&gt;
&lt;div&gt;##ELSEvalidation.storestatus## ##validation.answer.title## ##ENDELSEvalidation.storestatus##&lt;/div&gt;
&lt;div&gt;&lt;/div&gt;
&lt;div&gt;
&lt;div&gt;##lang.ticket.url## : &lt;a href="##ticket.urlvalidation##"&gt; ##ticket.urlvalidation## &lt;/a&gt;&lt;/div&gt;
&lt;/div&gt;
&lt;p&gt;##IFvalidation.status## ##lang.validation.status## : ##validation.status## ##ENDIFvalidation.status##
&lt;br /&gt; ##IFvalidation.commentvalidation##&lt;br /&gt; ##lang.validation.commentvalidation## :
&#160; ##validation.commentvalidation##&lt;br /&gt; ##ENDIFvalidation.commentvalidation##
&lt;br /&gt;##ENDFOREACHvalidations##&lt;/p&gt;',
            ], [
                'id' => '8',
                'notificationtemplates_id' => '6',
                'language' => '',
                'subject' => '##ticket.action## ##ticket.entity##',
                'content_text' => '##FOREACHtickets##
##lang.ticket.authors##: ##ticket.authors##
##lang.ticket.title##: ##ticket.title##
##lang.ticket.priority##: ##ticket.priority##
##lang.ticket.status##: ##ticket.status##
##lang.ticket.attribution##: ##IFticket.assigntousers####ticket.assigntousers##
##ENDIFticket.assigntousers####IFticket.assigntogroups##
##ticket.assigntogroups## ##ENDIFticket.assigntogroups####IFticket.assigntosupplier##
##ticket.assigntosupplier## ##ENDIFticket.assigntosupplier##
##lang.ticket.creationdate##: ##ticket.creationdate##
##lang.ticket.content##: ##ticket.content## ##ENDFOREACHtickets##',
                'content_html' => '&lt;table class="tab_cadre" border="1" cellspacing="2" cellpadding="3"&gt;
&lt;tbody&gt;
&lt;tr&gt;
&lt;td style="text-align: left;" width="auto" bgcolor="#cccccc"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##lang.ticket.authors##&lt;/span&gt;&lt;/td&gt;
&lt;td style="text-align: left;" width="auto" bgcolor="#cccccc"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##lang.ticket.title##&lt;/span&gt;&lt;/td&gt;
&lt;td style="text-align: left;" width="auto" bgcolor="#cccccc"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##lang.ticket.priority##&lt;/span&gt;&lt;/td&gt;
&lt;td style="text-align: left;" width="auto" bgcolor="#cccccc"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##lang.ticket.status##&lt;/span&gt;&lt;/td&gt;
&lt;td style="text-align: left;" width="auto" bgcolor="#cccccc"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##lang.ticket.attribution##&lt;/span&gt;&lt;/td&gt;
&lt;td style="text-align: left;" width="auto" bgcolor="#cccccc"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##lang.ticket.creationdate##&lt;/span&gt;&lt;/td&gt;
&lt;td style="text-align: left;" width="auto" bgcolor="#cccccc"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##lang.ticket.content##&lt;/span&gt;##FOREACHtickets##&lt;/td&gt;
&lt;/tr&gt;
&lt;tr&gt;
&lt;td width="auto"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##ticket.authors##&lt;/span&gt;&lt;/td&gt;
&lt;td width="auto"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;&lt;a href="##ticket.url##"&gt;##ticket.title##&lt;/a&gt;&lt;/span&gt;&lt;/td&gt;
&lt;td width="auto"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##ticket.priority##&lt;/span&gt;&lt;/td&gt;
&lt;td width="auto"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##ticket.status##&lt;/span&gt;&lt;/td&gt;
&lt;td width="auto"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##IFticket.assigntousers####ticket.assigntousers##&lt;br /&gt;##ENDIFticket.assigntousers####IFticket.assigntogroups##&lt;br /&gt;##ticket.assigntogroups## ##ENDIFticket.assigntogroups####IFticket.assigntosupplier##&lt;br /&gt;##ticket.assigntosupplier## ##ENDIFticket.assigntosupplier##&lt;/span&gt;&lt;/td&gt;
&lt;td width="auto"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##ticket.creationdate##&lt;/span&gt;&lt;/td&gt;
&lt;td width="auto"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##ticket.content##&lt;/span&gt;##ENDFOREACHtickets##&lt;/td&gt;
&lt;/tr&gt;
&lt;/tbody&gt;
&lt;/table&gt;',
            ], [
                'id' => '9',
                'notificationtemplates_id' => '9',
                'language' => '',
                'subject' => '##consumable.action##  ##consumable.entity##',
                'content_text' => '##lang.consumable.entity## : ##consumable.entity##


##FOREACHconsumables##
##lang.consumable.item## : ##consumable.item##


##lang.consumable.reference## : ##consumable.reference##

##lang.consumable.remaining## : ##consumable.remaining##
##lang.consumable.stock_target## : ##consumable.stock_target##
##lang.consumable.to_order## : ##consumable.to_order##

##consumable.url##

##ENDFOREACHconsumables##',
                'content_html' => '&lt;p&gt;
##lang.consumable.entity## : ##consumable.entity##
&lt;br /&gt; &lt;br /&gt;##FOREACHconsumables##
&lt;br /&gt;##lang.consumable.item## : ##consumable.item##&lt;br /&gt;
&lt;br /&gt;##lang.consumable.reference## : ##consumable.reference##&lt;br /&gt;
##lang.consumable.remaining## : ##consumable.remaining##&lt;br /&gt;
##lang.consumable.stock_target## : ##consumable.stock_target##&lt;br /&gt;
##lang.consumable.to_order## : ##consumable.to_order##&lt;br /&gt;
&lt;a href="##consumable.url##"&gt; ##consumable.url##&lt;/a&gt;&lt;br /&gt;
   ##ENDFOREACHconsumables##&lt;/p&gt;',
            ], [
                'id' => '10',
                'notificationtemplates_id' => '8',
                'language' => '',
                'subject' => '##cartridge.action##  ##cartridge.entity##',
                'content_text' => '##lang.cartridge.entity## : ##cartridge.entity##


##FOREACHcartridges##
##lang.cartridge.item## : ##cartridge.item##


##lang.cartridge.reference## : ##cartridge.reference##

##lang.cartridge.remaining## : ##cartridge.remaining##
##lang.cartridge.stock_target## : ##cartridge.stock_target##
##lang.cartridge.to_order## : ##cartridge.to_order##

##cartridge.url##
 ##ENDFOREACHcartridges##',
                'content_html' => '&lt;p&gt;##lang.cartridge.entity## : ##cartridge.entity##
&lt;br /&gt; &lt;br /&gt;##FOREACHcartridges##
&lt;br /&gt;##lang.cartridge.item## :
##cartridge.item##&lt;br /&gt; &lt;br /&gt;
##lang.cartridge.reference## :
##cartridge.reference##&lt;br /&gt;
##lang.cartridge.remaining## :
##cartridge.remaining##&lt;br /&gt;
##lang.cartridge.stock_target## :
##cartridge.stock_target##&lt;br /&gt;
##lang.cartridge.to_order## :
##cartridge.to_order##&lt;br /&gt;
&lt;a href="##cartridge.url##"&gt;
##cartridge.url##&lt;/a&gt;&lt;br /&gt;
##ENDFOREACHcartridges##&lt;/p&gt;',
            ], [
                'id' => '11',
                'notificationtemplates_id' => '10',
                'language' => '',
                'subject' => '##infocom.action##  ##infocom.entity##',
                'content_text' => '##lang.infocom.entity## : ##infocom.entity##


##FOREACHinfocoms##

##lang.infocom.itemtype## : ##infocom.itemtype##

##lang.infocom.item## : ##infocom.item##


##lang.infocom.expirationdate## : ##infocom.expirationdate##

##infocom.url##
 ##ENDFOREACHinfocoms##',
                'content_html' => '&lt;p&gt;##lang.infocom.entity## : ##infocom.entity##
&lt;br /&gt; &lt;br /&gt;##FOREACHinfocoms##
&lt;br /&gt;##lang.infocom.itemtype## : ##infocom.itemtype##&lt;br /&gt;
##lang.infocom.item## : ##infocom.item##&lt;br /&gt; &lt;br /&gt;
##lang.infocom.expirationdate## : ##infocom.expirationdate##
&lt;br /&gt; &lt;a href="##infocom.url##"&gt;
##infocom.url##&lt;/a&gt;&lt;br /&gt;
##ENDFOREACHinfocoms##&lt;/p&gt;',
            ], [
                'id' => '12',
                'notificationtemplates_id' => '11',
                'language' => '',
                'subject' => '##license.action##  ##license.entity##',
                'content_text' => '##lang.license.entity## : ##license.entity##

##FOREACHlicenses##

##lang.license.item## : ##license.item##

##lang.license.serial## : ##license.serial##

##lang.license.expirationdate## : ##license.expirationdate##

##license.url##
 ##ENDFOREACHlicenses##',
                'content_html' => '&lt;p&gt;
##lang.license.entity## : ##license.entity##&lt;br /&gt;
##FOREACHlicenses##
&lt;br /&gt;##lang.license.item## : ##license.item##&lt;br /&gt;
##lang.license.serial## : ##license.serial##&lt;br /&gt;
##lang.license.expirationdate## : ##license.expirationdate##
&lt;br /&gt; &lt;a href="##license.url##"&gt; ##license.url##
&lt;/a&gt;&lt;br /&gt; ##ENDFOREACHlicenses##&lt;/p&gt;',
            ], [
                'id' => '13',
                'notificationtemplates_id' => '13',
                'language' => '',
                'subject' => '##user.action##',
                'content_text' => '##user.realname## ##user.firstname##

##lang.passwordforget.information##

##lang.passwordforget.link## ##user.passwordforgeturl##',
                'content_html' => '&lt;p&gt;&lt;strong&gt;##user.realname## ##user.firstname##&lt;/strong&gt;&lt;/p&gt;
&lt;p&gt;##lang.passwordforget.information##&lt;/p&gt;
&lt;p&gt;##lang.passwordforget.link## &lt;a title="##user.passwordforgeturl##" href="##user.passwordforgeturl##"&gt;##user.passwordforgeturl##&lt;/a&gt;&lt;/p&gt;',
            ], [
                'id' => '14',
                'notificationtemplates_id' => '14',
                'language' => '',
                'subject' => '##ticket.action## ##ticket.title##',
                'content_text' => '##lang.ticket.title## : ##ticket.title##

##lang.ticket.closedate## : ##ticket.closedate##

##lang.satisfaction.text## ##ticket.urlsatisfaction##',
                'content_html' => '&lt;p&gt;##lang.ticket.title## : ##ticket.title##&lt;/p&gt;
&lt;p&gt;##lang.ticket.closedate## : ##ticket.closedate##&lt;/p&gt;
&lt;p&gt;##lang.satisfaction.text## &lt;a href="##ticket.urlsatisfaction##"&gt;##ticket.urlsatisfaction##&lt;/a&gt;&lt;/p&gt;',
            ], [
                'id' => '16',
                'notificationtemplates_id' => '16',
                'language' => '',
                'subject' => '##crontask.action##',
                'content_text' => '##lang.crontask.warning##

##FOREACHcrontasks##
 ##crontask.name## : ##crontask.description##

##ENDFOREACHcrontasks##',
                'content_html' => '&lt;p&gt;##lang.crontask.warning##&lt;/p&gt;
&lt;p&gt;##FOREACHcrontasks## &lt;br /&gt;&lt;a href="##crontask.url##"&gt;##crontask.name##&lt;/a&gt; : ##crontask.description##&lt;br /&gt; &lt;br /&gt;##ENDFOREACHcrontasks##&lt;/p&gt;',
            ], [
                'id' => '17',
                'notificationtemplates_id' => '17',
                'language' => '',
                'subject' => '##problem.action## ##problem.title##',
                'content_text' => '##IFproblem.storestatus=5##
 ##lang.problem.url## : ##problem.urlapprove##
 ##lang.problem.solvedate## : ##problem.solvedate##
 ##lang.problem.solution.type## : ##problem.solution.type##
 ##lang.problem.solution.description## : ##problem.solution.description## ##ENDIFproblem.storestatus##
 ##ELSEproblem.storestatus## ##lang.problem.url## : ##problem.url## ##ENDELSEproblem.storestatus##

 ##lang.problem.description##

 ##lang.problem.title##  :##problem.title##
 ##lang.problem.authors##  :##IFproblem.authors## ##problem.authors## ##ENDIFproblem.authors## ##ELSEproblem.authors##--##ENDELSEproblem.authors##
 ##lang.problem.creationdate##  :##problem.creationdate##
 ##IFproblem.assigntousers## ##lang.problem.assigntousers##  : ##problem.assigntousers## ##ENDIFproblem.assigntousers##
 ##lang.problem.status##  : ##problem.status##
 ##IFproblem.assigntogroups## ##lang.problem.assigntogroups##  : ##problem.assigntogroups## ##ENDIFproblem.assigntogroups##
 ##lang.problem.urgency##  : ##problem.urgency##
 ##lang.problem.impact##  : ##problem.impact##
 ##lang.problem.priority## : ##problem.priority##
##IFproblem.category## ##lang.problem.category##  :##problem.category## ##ENDIFproblem.category## ##ELSEproblem.category## ##lang.problem.nocategoryassigned## ##ENDELSEproblem.category##
 ##lang.problem.content##  : ##problem.content##

##IFproblem.storestatus=6##
 ##lang.problem.solvedate## : ##problem.solvedate##
 ##lang.problem.solution.type## : ##problem.solution.type##
 ##lang.problem.solution.description## : ##problem.solution.description##
##ENDIFproblem.storestatus##
 ##lang.problem.numberoffollowups## : ##problem.numberoffollowups##

##FOREACHfollowups##

 [##followup.date##] ##lang.followup.isprivate## : ##followup.isprivate##
 ##lang.followup.author## ##followup.author##
 ##lang.followup.description## ##followup.description##
 ##lang.followup.date## ##followup.date##
 ##lang.followup.requesttype## ##followup.requesttype##

##ENDFOREACHfollowups##
 ##lang.problem.numberoftickets## : ##problem.numberoftickets##

##FOREACHtickets##
 [##ticket.date##] ##lang.problem.title## : ##ticket.title##
 ##lang.problem.content## ##ticket.content##

##ENDFOREACHtickets##
 ##lang.problem.numberoftasks## : ##problem.numberoftasks##

##FOREACHtasks##
 [##task.date##]
 ##lang.task.author## ##task.author##
 ##lang.task.description## ##task.description##
 ##lang.task.time## ##task.time##
 ##lang.task.category## ##task.category##

##ENDFOREACHtasks##
',
                'content_html' => '&lt;p&gt;##IFproblem.storestatus=5##&lt;/p&gt;
&lt;div&gt;##lang.problem.url## : &lt;a href="##problem.urlapprove##"&gt;##problem.urlapprove##&lt;/a&gt;&lt;/div&gt;
&lt;div&gt;&lt;span style="color: #888888;"&gt;&lt;strong&gt;&lt;span style="text-decoration: underline;"&gt;##lang.problem.solvedate##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##problem.solvedate##&lt;br /&gt;&lt;span style="text-decoration: underline; color: #888888;"&gt;&lt;strong&gt;##lang.problem.solution.type##&lt;/strong&gt;&lt;/span&gt; : ##problem.solution.type##&lt;br /&gt;&lt;span style="text-decoration: underline; color: #888888;"&gt;&lt;strong&gt;##lang.problem.solution.description##&lt;/strong&gt;&lt;/span&gt; : ##problem.solution.description## ##ENDIFproblem.storestatus##&lt;/div&gt;
&lt;div&gt;##ELSEproblem.storestatus## ##lang.problem.url## : &lt;a href="##problem.url##"&gt;##problem.url##&lt;/a&gt; ##ENDELSEproblem.storestatus##&lt;/div&gt;
&lt;p class="description b"&gt;&lt;strong&gt;##lang.problem.description##&lt;/strong&gt;&lt;/p&gt;
&lt;p&gt;&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.problem.title##&lt;/span&gt;&#160;:##problem.title## &lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.problem.authors##&lt;/span&gt;&#160;:##IFproblem.authors## ##problem.authors## ##ENDIFproblem.authors##    ##ELSEproblem.authors##--##ENDELSEproblem.authors## &lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.problem.creationdate##&lt;/span&gt;&#160;:##problem.creationdate## &lt;br /&gt; ##IFproblem.assigntousers## &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.problem.assigntousers##&lt;/span&gt;&#160;: ##problem.assigntousers## ##ENDIFproblem.assigntousers##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.problem.status## &lt;/span&gt;&#160;: ##problem.status##&lt;br /&gt; ##IFproblem.assigntogroups## &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.problem.assigntogroups##&lt;/span&gt;&#160;: ##problem.assigntogroups## ##ENDIFproblem.assigntogroups##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.problem.urgency##&lt;/span&gt;&#160;: ##problem.urgency##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.problem.impact##&lt;/span&gt;&#160;: ##problem.impact##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.problem.priority##&lt;/span&gt; : ##problem.priority## &lt;br /&gt;##IFproblem.category##&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.problem.category## &lt;/span&gt;&#160;:##problem.category##  ##ENDIFproblem.category## ##ELSEproblem.category##  ##lang.problem.nocategoryassigned## ##ENDELSEproblem.category##    &lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.problem.content##&lt;/span&gt;&#160;: ##problem.content##&lt;/p&gt;
&lt;p&gt;##IFproblem.storestatus=6##&lt;br /&gt;&lt;span style="text-decoration: underline;"&gt;&lt;strong&gt;&lt;span style="color: #888888;"&gt;##lang.problem.solvedate##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##problem.solvedate##&lt;br /&gt;&lt;span style="color: #888888;"&gt;&lt;strong&gt;&lt;span style="text-decoration: underline;"&gt;##lang.problem.solution.type##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##problem.solution.type##&lt;br /&gt;&lt;span style="text-decoration: underline; color: #888888;"&gt;&lt;strong&gt;##lang.problem.solution.description##&lt;/strong&gt;&lt;/span&gt; : ##problem.solution.description##&lt;br /&gt;##ENDIFproblem.storestatus##&lt;/p&gt;
&lt;div class="description b"&gt;##lang.problem.numberoffollowups##&#160;: ##problem.numberoffollowups##&lt;/div&gt;
&lt;p&gt;##FOREACHfollowups##&lt;/p&gt;
&lt;div class="description b"&gt;&lt;br /&gt; &lt;strong&gt; [##followup.date##] &lt;em&gt;##lang.followup.isprivate## : ##followup.isprivate## &lt;/em&gt;&lt;/strong&gt;&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.followup.author## &lt;/span&gt; ##followup.author##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.followup.description## &lt;/span&gt; ##followup.description##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.followup.date## &lt;/span&gt; ##followup.date##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.followup.requesttype## &lt;/span&gt; ##followup.requesttype##&lt;/div&gt;
&lt;p&gt;##ENDFOREACHfollowups##&lt;/p&gt;
&lt;div class="description b"&gt;##lang.problem.numberoftickets##&#160;: ##problem.numberoftickets##&lt;/div&gt;
&lt;p&gt;##FOREACHtickets##&lt;/p&gt;
&lt;div&gt;&lt;strong&gt; [##ticket.date##] &lt;em&gt;##lang.problem.title## : &lt;a href="##ticket.url##"&gt;##ticket.title## &lt;/a&gt;&lt;/em&gt;&lt;/strong&gt;&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; &lt;/span&gt;&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.problem.content## &lt;/span&gt; ##ticket.content##
&lt;p&gt;##ENDFOREACHtickets##&lt;/p&gt;
&lt;div class="description b"&gt;##lang.problem.numberoftasks##&#160;: ##problem.numberoftasks##&lt;/div&gt;
&lt;p&gt;##FOREACHtasks##&lt;/p&gt;
&lt;div class="description b"&gt;&lt;strong&gt;[##task.date##] &lt;/strong&gt;&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.task.author##&lt;/span&gt; ##task.author##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.task.description##&lt;/span&gt; ##task.description##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.task.time##&lt;/span&gt; ##task.time##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.task.category##&lt;/span&gt; ##task.category##&lt;/div&gt;
&lt;p&gt;##ENDFOREACHtasks##&lt;/p&gt;
&lt;/div&gt;',
            ], [
                'id' => '18',
                'notificationtemplates_id' => '18',
                'language' => '',
                'subject' => '##recall.action##: ##recall.item.name##',
                'content_text' => '##recall.action##: ##recall.item.name##

##recall.item.content##

##lang.recall.planning.begin##: ##recall.planning.begin##
##lang.recall.planning.end##: ##recall.planning.end##
##lang.recall.planning.state##: ##recall.planning.state##
##lang.recall.item.private##: ##recall.item.private##',
                'content_html' => '&lt;p&gt;##recall.action##: &lt;a href="##recall.item.url##"&gt;##recall.item.name##&lt;/a&gt;&lt;/p&gt;
&lt;p&gt;##recall.item.content##&lt;/p&gt;
&lt;p&gt;##lang.recall.planning.begin##: ##recall.planning.begin##&lt;br /&gt;##lang.recall.planning.end##: ##recall.planning.end##&lt;br /&gt;##lang.recall.planning.state##: ##recall.planning.state##&lt;br /&gt;##lang.recall.item.private##: ##recall.item.private##&lt;br /&gt;&lt;br /&gt;&lt;/p&gt;
&lt;p&gt;&lt;br /&gt;&lt;br /&gt;&lt;/p&gt;',
            ], [
                'id' => '19',
                'notificationtemplates_id' => '19',
                'language' => '',
                'subject' => '##change.action## ##change.title##',
                'content_text' => '##IFchange.storestatus=5##
 ##lang.change.url## : ##change.urlapprove##
 ##lang.change.solvedate## : ##change.solvedate##
 ##lang.change.solution.type## : ##change.solution.type##
 ##lang.change.solution.description## : ##change.solution.description## ##ENDIFchange.storestatus##
 ##ELSEchange.storestatus## ##lang.change.url## : ##change.url## ##ENDELSEchange.storestatus##

 ##lang.change.description##

 ##lang.change.title##  :##change.title##
 ##lang.change.authors##  :##IFchange.authors## ##change.authors## ##ENDIFchange.authors## ##ELSEchange.authors##--##ENDELSEchange.authors##
 ##lang.change.creationdate##  :##change.creationdate##
 ##IFchange.assigntousers## ##lang.change.assigntousers##  : ##change.assigntousers## ##ENDIFchange.assigntousers##
 ##lang.change.status##  : ##change.status##
 ##IFchange.assigntogroups## ##lang.change.assigntogroups##  : ##change.assigntogroups## ##ENDIFchange.assigntogroups##
 ##lang.change.urgency##  : ##change.urgency##
 ##lang.change.impact##  : ##change.impact##
 ##lang.change.priority## : ##change.priority##
##IFchange.category## ##lang.change.category##  :##change.category## ##ENDIFchange.category## ##ELSEchange.category## ##lang.change.nocategoryassigned## ##ENDELSEchange.category##
 ##lang.change.content##  : ##change.content##

##IFchange.storestatus=6##
 ##lang.change.solvedate## : ##change.solvedate##
 ##lang.change.solution.type## : ##change.solution.type##
 ##lang.change.solution.description## : ##change.solution.description##
##ENDIFchange.storestatus##
 ##lang.change.numberoffollowups## : ##change.numberoffollowups##

##FOREACHfollowups##

 [##followup.date##] ##lang.followup.isprivate## : ##followup.isprivate##
 ##lang.followup.author## ##followup.author##
 ##lang.followup.description## ##followup.description##
 ##lang.followup.date## ##followup.date##
 ##lang.followup.requesttype## ##followup.requesttype##

##ENDFOREACHfollowups##
 ##lang.change.numberofproblems## : ##change.numberofproblems##

##FOREACHproblems##
 [##problem.date##] ##lang.change.title## : ##problem.title##
 ##lang.change.content## ##problem.content##

##ENDFOREACHproblems##
 ##lang.change.numberoftasks## : ##change.numberoftasks##

##FOREACHtasks##
 [##task.date##]
 ##lang.task.author## ##task.author##
 ##lang.task.description## ##task.description##
 ##lang.task.time## ##task.time##
 ##lang.task.category## ##task.category##

##ENDFOREACHtasks##
',
                'content_html' => '&lt;p&gt;##IFchange.storestatus=5##&lt;/p&gt;
&lt;div&gt;##lang.change.url## : &lt;a href="##change.urlapprove##"&gt;##change.urlapprove##&lt;/a&gt;&lt;/div&gt;
&lt;div&gt;&lt;span style="color: #888888;"&gt;&lt;strong&gt;&lt;span style="text-decoration: underline;"&gt;##lang.change.solvedate##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##change.solvedate##&lt;br /&gt;&lt;span style="text-decoration: underline; color: #888888;"&gt;&lt;strong&gt;##lang.change.solution.type##&lt;/strong&gt;&lt;/span&gt; : ##change.solution.type##&lt;br /&gt;&lt;span style="text-decoration: underline; color: #888888;"&gt;&lt;strong&gt;##lang.change.solution.description##&lt;/strong&gt;&lt;/span&gt; : ##change.solution.description## ##ENDIFchange.storestatus##&lt;/div&gt;
&lt;div&gt;##ELSEchange.storestatus## ##lang.change.url## : &lt;a href="##change.url##"&gt;##change.url##&lt;/a&gt; ##ENDELSEchange.storestatus##&lt;/div&gt;
&lt;p class="description b"&gt;&lt;strong&gt;##lang.change.description##&lt;/strong&gt;&lt;/p&gt;
&lt;p&gt;&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.change.title##&lt;/span&gt;&#160;:##change.title## &lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.change.authors##&lt;/span&gt;&#160;:##IFchange.authors## ##change.authors## ##ENDIFchange.authors##    ##ELSEchange.authors##--##ENDELSEchange.authors## &lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.change.creationdate##&lt;/span&gt;&#160;:##change.creationdate## &lt;br /&gt; ##IFchange.assigntousers## &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.change.assigntousers##&lt;/span&gt;&#160;: ##change.assigntousers## ##ENDIFchange.assigntousers##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.change.status## &lt;/span&gt;&#160;: ##change.status##&lt;br /&gt; ##IFchange.assigntogroups## &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.change.assigntogroups##&lt;/span&gt;&#160;: ##change.assigntogroups## ##ENDIFchange.assigntogroups##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.change.urgency##&lt;/span&gt;&#160;: ##change.urgency##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.change.impact##&lt;/span&gt;&#160;: ##change.impact##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.change.priority##&lt;/span&gt; : ##change.priority## &lt;br /&gt;##IFchange.category##&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.change.category## &lt;/span&gt;&#160;:##change.category##  ##ENDIFchange.category## ##ELSEchange.category##  ##lang.change.nocategoryassigned## ##ENDELSEchange.category##    &lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.change.content##&lt;/span&gt;&#160;: ##change.content##&lt;/p&gt;
&lt;p&gt;##IFchange.storestatus=6##&lt;br /&gt;&lt;span style="text-decoration: underline;"&gt;&lt;strong&gt;&lt;span style="color: #888888;"&gt;##lang.change.solvedate##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##change.solvedate##&lt;br /&gt;&lt;span style="color: #888888;"&gt;&lt;strong&gt;&lt;span style="text-decoration: underline;"&gt;##lang.change.solution.type##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##change.solution.type##&lt;br /&gt;&lt;span style="text-decoration: underline; color: #888888;"&gt;&lt;strong&gt;##lang.change.solution.description##&lt;/strong&gt;&lt;/span&gt; : ##change.solution.description##&lt;br /&gt;##ENDIFchange.storestatus##&lt;/p&gt;
&lt;div class="description b"&gt;##lang.change.numberoffollowups##&#160;: ##change.numberoffollowups##&lt;/div&gt;
&lt;p&gt;##FOREACHfollowups##&lt;/p&gt;
&lt;div class="description b"&gt;&lt;br /&gt; &lt;strong&gt; [##followup.date##] &lt;em&gt;##lang.followup.isprivate## : ##followup.isprivate## &lt;/em&gt;&lt;/strong&gt;&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.followup.author## &lt;/span&gt; ##followup.author##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.followup.description## &lt;/span&gt; ##followup.description##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.followup.date## &lt;/span&gt; ##followup.date##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.followup.requesttype## &lt;/span&gt; ##followup.requesttype##&lt;/div&gt;
&lt;p&gt;##ENDFOREACHfollowups##&lt;/p&gt;
&lt;div class="description b"&gt;##lang.change.numberofproblems##&#160;: ##change.numberofproblems##&lt;/div&gt;
&lt;p&gt;##FOREACHproblems##&lt;/p&gt;
&lt;div&gt;&lt;strong&gt; [##problem.date##] &lt;em&gt;##lang.change.title## : &lt;a href="##problem.url##"&gt;##problem.title## &lt;/a&gt;&lt;/em&gt;&lt;/strong&gt;&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; &lt;/span&gt;&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.change.content## &lt;/span&gt; ##problem.content##
&lt;p&gt;##ENDFOREACHproblems##&lt;/p&gt;
&lt;div class="description b"&gt;##lang.change.numberoftasks##&#160;: ##change.numberoftasks##&lt;/div&gt;
&lt;p&gt;##FOREACHtasks##&lt;/p&gt;
&lt;div class="description b"&gt;&lt;strong&gt;[##task.date##] &lt;/strong&gt;&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.task.author##&lt;/span&gt; ##task.author##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.task.description##&lt;/span&gt; ##task.description##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.task.time##&lt;/span&gt; ##task.time##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.task.category##&lt;/span&gt; ##task.category##&lt;/div&gt;
&lt;p&gt;##ENDFOREACHtasks##&lt;/p&gt;
&lt;/div&gt;',
            ], [
                'id' => '20',
                'notificationtemplates_id' => '20',
                'language' => '',
                'subject' => '##mailcollector.action##',
                'content_text' => '##FOREACHmailcollectors##
##lang.mailcollector.name## : ##mailcollector.name##
##lang.mailcollector.errors## : ##mailcollector.errors##
##mailcollector.url##
##ENDFOREACHmailcollectors##',
                'content_html' => '&lt;p&gt;##FOREACHmailcollectors##&lt;br /&gt;##lang.mailcollector.name## : ##mailcollector.name##&lt;br /&gt; ##lang.mailcollector.errors## : ##mailcollector.errors##&lt;br /&gt;&lt;a href="##mailcollector.url##"&gt;##mailcollector.url##&lt;/a&gt;&lt;br /&gt; ##ENDFOREACHmailcollectors##&lt;/p&gt;
&lt;p&gt;&lt;/p&gt;',
            ], [
                'id' => '21',
                'notificationtemplates_id' => '21',
                'language' => '',
                'subject' => '##project.action## ##project.name## ##project.code##',
                'content_text' => '##lang.project.url## : ##project.url##

##lang.project.description##

##lang.project.name## : ##project.name##
##lang.project.code## : ##project.code##
##lang.project.manager## : ##project.manager##
##lang.project.managergroup## : ##project.managergroup##
##lang.project.creationdate## : ##project.creationdate##
##lang.project.priority## : ##project.priority##
##lang.project.state## : ##project.state##
##lang.project.type## : ##project.type##
##lang.project.description## : ##project.description##

##lang.project.numberoftasks## : ##project.numberoftasks##



##FOREACHtasks##

[##task.creationdate##]
##lang.task.name## : ##task.name##
##lang.task.state## : ##task.state##
##lang.task.type## : ##task.type##
##lang.task.percent## : ##task.percent##
##lang.task.description## : ##task.description##

##ENDFOREACHtasks##',
                'content_html' => '&lt;p&gt;##lang.project.url## : &lt;a href="##project.url##"&gt;##project.url##&lt;/a&gt;&lt;/p&gt;
&lt;p&gt;&lt;strong&gt;##lang.project.description##&lt;/strong&gt;&lt;/p&gt;
&lt;p&gt;##lang.project.name## : ##project.name##&lt;br /&gt;##lang.project.code## : ##project.code##&lt;br /&gt; ##lang.project.manager## : ##project.manager##&lt;br /&gt;##lang.project.managergroup## : ##project.managergroup##&lt;br /&gt; ##lang.project.creationdate## : ##project.creationdate##&lt;br /&gt;##lang.project.priority## : ##project.priority## &lt;br /&gt;##lang.project.state## : ##project.state##&lt;br /&gt;##lang.project.type## : ##project.type##&lt;br /&gt;##lang.project.description## : ##project.description##&lt;/p&gt;
&lt;p&gt;##lang.project.numberoftasks## : ##project.numberoftasks##&lt;/p&gt;
&lt;div&gt;
&lt;p&gt;##FOREACHtasks##&lt;/p&gt;
&lt;div&gt;&lt;strong&gt;[##task.creationdate##] &lt;/strong&gt;&lt;br /&gt; ##lang.task.name## : ##task.name##&lt;br /&gt;##lang.task.state## : ##task.state##&lt;br /&gt;##lang.task.type## : ##task.type##&lt;br /&gt;##lang.task.percent## : ##task.percent##&lt;br /&gt;##lang.task.description## : ##task.description##&lt;/div&gt;
&lt;p&gt;##ENDFOREACHtasks##&lt;/p&gt;
&lt;/div&gt;',
            ], [
                'id' => '22',
                'notificationtemplates_id' => '22',
                'language' => '',
                'subject' => '##projecttask.action## ##projecttask.name##',
                'content_text' => '##lang.projecttask.url## : ##projecttask.url##

##lang.projecttask.description##

##lang.projecttask.name## : ##projecttask.name##
##lang.projecttask.project## : ##projecttask.project##
##lang.projecttask.creationdate## : ##projecttask.creationdate##
##lang.projecttask.state## : ##projecttask.state##
##lang.projecttask.type## : ##projecttask.type##
##lang.projecttask.description## : ##projecttask.description##

##lang.projecttask.numberoftasks## : ##projecttask.numberoftasks##



##FOREACHtasks##

[##task.creationdate##]
##lang.task.name## : ##task.name##
##lang.task.state## : ##task.state##
##lang.task.type## : ##task.type##
##lang.task.percent## : ##task.percent##
##lang.task.description## : ##task.description##

##ENDFOREACHtasks##',
                'content_html' => '&lt;p&gt;##lang.projecttask.url## : &lt;a href="##projecttask.url##"&gt;##projecttask.url##&lt;/a&gt;&lt;/p&gt;
&lt;p&gt;&lt;strong&gt;##lang.projecttask.description##&lt;/strong&gt;&lt;/p&gt;
&lt;p&gt;##lang.projecttask.name## : ##projecttask.name##&lt;br /&gt;##lang.projecttask.project## : &lt;a href="##projecttask.projecturl##"&gt;##projecttask.project##&lt;/a&gt;&lt;br /&gt;##lang.projecttask.creationdate## : ##projecttask.creationdate##&lt;br /&gt;##lang.projecttask.state## : ##projecttask.state##&lt;br /&gt;##lang.projecttask.type## : ##projecttask.type##&lt;br /&gt;##lang.projecttask.description## : ##projecttask.description##&lt;/p&gt;
&lt;p&gt;##lang.projecttask.numberoftasks## : ##projecttask.numberoftasks##&lt;/p&gt;
&lt;div&gt;
&lt;p&gt;##FOREACHtasks##&lt;/p&gt;
&lt;div&gt;&lt;strong&gt;[##task.creationdate##] &lt;/strong&gt;&lt;br /&gt;##lang.task.name## : ##task.name##&lt;br /&gt;##lang.task.state## : ##task.state##&lt;br /&gt;##lang.task.type## : ##task.type##&lt;br /&gt;##lang.task.percent## : ##task.percent##&lt;br /&gt;##lang.task.description## : ##task.description##&lt;/div&gt;
&lt;p&gt;##ENDFOREACHtasks##&lt;/p&gt;
&lt;/div&gt;',
            ], [
                'id' => '23',
                'notificationtemplates_id' => '23',
                'language' => '',
                'subject' => '##objectlock.action##',
                'content_text' => '##objectlock.type## ###objectlock.id## - ##objectlock.name##

      ##lang.objectlock.url##
      ##objectlock.url##

      ##lang.objectlock.date_mod##
      ##objectlock.date_mod##

      Hello ##objectlock.lockedby.firstname##,
      Could go to this item and unlock it for me?
      Thank you,
      Regards,
      ##objectlock.requester.firstname##',
                'content_html' => '&lt;table&gt;
      &lt;tbody&gt;
      &lt;tr&gt;&lt;th colspan="2"&gt;&lt;a href="##objectlock.url##"&gt;##objectlock.type## ###objectlock.id## - ##objectlock.name##&lt;/a&gt;&lt;/th&gt;&lt;/tr&gt;
      &lt;tr&gt;
      &lt;td&gt;##lang.objectlock.url##&lt;/td&gt;
      &lt;td&gt;##objectlock.url##&lt;/td&gt;
      &lt;/tr&gt;
      &lt;tr&gt;
      &lt;td&gt;##lang.objectlock.date_mod##&lt;/td&gt;
      &lt;td&gt;##objectlock.date_mod##&lt;/td&gt;
      &lt;/tr&gt;
      &lt;/tbody&gt;
      &lt;/table&gt;
      &lt;p&gt;&lt;span style="font-size: small;"&gt;Hello ##objectlock.lockedby.firstname##,&lt;br /&gt;Could go to this item and unlock it for me?&lt;br /&gt;Thank you,&lt;br /&gt;Regards,&lt;br /&gt;##objectlock.requester.firstname## ##objectlock.requester.lastname##&lt;/span&gt;&lt;/p&gt;',
            ], [
                'id' => '24',
                'notificationtemplates_id' => '24',
                'language' => '',
                'subject' => '##savedsearch.action## ##savedsearch.name##',
                'content_text' => '##savedsearch.type## ###savedsearch.id## - ##savedsearch.name##

      ##savedsearch.message##

      ##lang.savedsearch.url##
      ##savedsearch.url##

      Regards,',
                'content_html' => '&lt;table&gt;
      &lt;tbody&gt;
      &lt;tr&gt;&lt;th colspan="2"&gt;&lt;a href="##savedsearch.url##"&gt;##savedsearch.type## ###savedsearch.id## - ##savedsearch.name##&lt;/a&gt;&lt;/th&gt;&lt;/tr&gt;
      &lt;tr&gt;&lt;td colspan="2"&gt;&lt;a href="##savedsearch.url##"&gt;##savedsearch.message##&lt;/a&gt;&lt;/td&gt;&lt;/tr&gt;
      &lt;tr&gt;
      &lt;td&gt;##lang.savedsearch.url##&lt;/td&gt;
      &lt;td&gt;##savedsearch.url##&lt;/td&gt;
      &lt;/tr&gt;
      &lt;/tbody&gt;
      &lt;/table&gt;
      &lt;p&gt;&lt;span style="font-size: small;"&gt;Hello &lt;br /&gt;Regards,&lt;/span&gt;&lt;/p&gt;',
            ], [
                'id' => '25',
                'notificationtemplates_id' => '25',
                'language' => '',
                'subject' => '##certificate.action##  ##certificate.name##',
                'content_text' => '##lang.certificate.entity## : ##certificate.entity##

##lang.certificate.serial## : ##certificate.serial##

##lang.certificate.expirationdate## : ##certificate.expirationdate##

##certificate.url##',
                'content_html' => '&lt;p&gt;
##lang.certificate.entity## : ##certificate.entity##&lt;br /&gt;
&lt;br /&gt;##lang.certificate.name## : ##certificate.name##&lt;br /&gt;
##lang.certificate.serial## : ##certificate.serial##&lt;br /&gt;
##lang.certificate.expirationdate## : ##certificate.expirationdate##
&lt;br /&gt; &lt;a href="##certificate.url##"&gt; ##certificate.url##
&lt;/a&gt;&lt;br /&gt;
&lt;/p&gt;',
            ], [
                'id' => '26',
                'notificationtemplates_id' => '26',
                'language' => '',
                'subject' => '##domain.action## : ##domain.name##',
                'content_text' => '##lang.domain.entity## :##domain.entity##
   ##lang.domain.name## : ##domain.name## - ##lang.domain.dateexpiration## : ##domain.dateexpiration##',
                'content_html' => '&lt;p&gt;##lang.domain.entity## :##domain.entity##&lt;br /&gt; &lt;br /&gt;
                        ##lang.domain.name##  : ##domain.name## - ##lang.domain.dateexpiration## :  ##domain.dateexpiration##&lt;br /&gt;
                        &lt;/p&gt;',

            ], [
                'id' => '27',
                'notificationtemplates_id' => '27',
                'language' => '',
                'subject' => '##user.action##',
                'content_text' => '##user.realname## ##user.firstname##,

##IFuser.password.has_expired=1##
##lang.password.has_expired.information##
##ENDIFuser.password.has_expired##
##ELSEuser.password.has_expired##
##lang.password.expires_soon.information##
##ENDELSEuser.password.has_expired##
##lang.user.password.expiration.date##: ##user.password.expiration.date##
##IFuser.account.lock.date##
##lang.user.account.lock.date##: ##user.account.lock.date##
##ENDIFuser.account.lock.date##

##password.update.link## ##user.password.update.url##',
                'content_html' => '&lt;p&gt;&lt;strong&gt;##user.realname## ##user.firstname##&lt;/strong&gt;&lt;/p&gt;

##IFuser.password.has_expired=1##
&lt;p&gt;##lang.password.has_expired.information##&lt;/p&gt;
##ENDIFuser.password.has_expired##
##ELSEuser.password.has_expired##
&lt;p&gt;##lang.password.expires_soon.information##&lt;/p&gt;
##ENDELSEuser.password.has_expired##
&lt;p&gt;##lang.user.password.expiration.date##: ##user.password.expiration.date##&lt;/p&gt;
##IFuser.account.lock.date##
&lt;p&gt;##lang.user.account.lock.date##: ##user.account.lock.date##&lt;/p&gt;
##ENDIFuser.account.lock.date##

&lt;p&gt;##lang.password.update.link## &lt;a href="##user.password.update.url##"&gt;##user.password.update.url##&lt;/a&gt;&lt;/p&gt;',

            ], [
                'id' => '28',
                'notificationtemplates_id' => '28',
                'language' => '',
                'subject' => '##lang.plugins_updates_available##',
                'content_text' => '##lang.plugins_updates_available##

##FOREACHplugins##
##plugin.name## :##plugin.old_version## -&gt; ##plugin.version##
##ENDFOREACHplugins##

##lang.marketplace.url## : ##marketplace.url##',
                'content_html' => '&lt;p&gt;##lang.plugins_updates_available##&lt;/p&gt;
&lt;ul&gt;##FOREACHplugins##
&lt;li&gt;##plugin.name## :##plugin.old_version## -&gt; ##plugin.version##&lt;/li&gt;
##ENDFOREACHplugins##&lt;/ul&gt;
&lt;p&gt;##lang.marketplace.url## : &lt;a title="##lang.marketplace.url##" href="##marketplace.url##" target="_blank" rel="noopener"&gt;##marketplace.url##&lt;/a&gt;&lt;/p&gt;'
            ],
        ];

        $tables['glpi_profilerights'] = [
            [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'computer',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'monitor',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'software',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'networking',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'internet',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'printer',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'peripheral',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'cartridge',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'consumable',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'phone',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'queuednotification',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'contact_enterprise',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'document',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'contract',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'infocom',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'knowbase',
                'rights' => KnowbaseItem::READFAQ,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'reservation',
                'rights' => ReservationItem::RESERVEANITEM,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'reports',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'dropdown',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'device',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'typedoc',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'link',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'config',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'rule_ticket',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'rule_import',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'rule_location',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'rule_ldap',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'rule_softwarecategories',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'search_config',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'location',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'domain',
                'rights' => READ | UPDATE | CREATE | DELETE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'profile',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'user',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'group',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'entity',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'transfer',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'logs',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'reminder_public',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'rssfeed_public',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'bookmark_public',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'backup',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'ticket',
                'rights' => READ | CREATE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'followup',
                'rights' => READ | CREATE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'task',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'planning',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'state',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'taskcategory',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'statistic',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'password_update',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'show_group_hardware',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'rule_dictionnary_software',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'rule_dictionnary_dropdown',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'budget',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'notification',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'rule_mailcollector',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'solutiontemplate',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'itilfollowuptemplate',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'calendar',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'slm',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'rule_dictionnary_printer',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'problem',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'cable_management',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'knowbasecategory',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'itilcategory',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'itiltemplate',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'ticketrecurrent',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'ticketcost',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'changevalidation',
                'rights' => CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'ticketvalidation',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'computer',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'monitor',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'software',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'networking',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'internet',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'printer',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'peripheral',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'cartridge',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'consumable',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'phone',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'queuednotification',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'contact_enterprise',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'document',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'contract',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'infocom',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'knowbase',
                'rights' => READ | KnowbaseItem::READFAQ | KnowbaseItem::COMMENTS,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'reservation',
                'rights' => READ | ReservationItem::RESERVEANITEM,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'reports',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'dropdown',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'device',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'typedoc',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'link',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'config',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'rule_ticket',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'rule_import',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'rule_location',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'rule_ldap',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'rule_softwarecategories',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'search_config',
                'rights' => DisplayPreference::PERSONAL,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'location',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'domain',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'profile',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'user',
                'rights' => READ | User::READAUTHENT,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'group',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'entity',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'transfer',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'logs',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'reminder_public',
                'rights' => READ | Reminder::PERSONAL,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'rssfeed_public',
                'rights' => READ | RSSFeed::PERSONAL,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'bookmark_public',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'backup',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'ticket',
                'rights' => READ | CREATE | DELETE | PURGE | Ticket::READALL | Ticket::READASSIGN | Ticket::OWN | Ticket::SURVEY,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'followup',
                'rights' => READ | CREATE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'task',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'projecttask',
                'rights' => READ | ProjectTask::UPDATEMY,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'projecttask',
                'rights' => READ | ProjectTask::UPDATEMY,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'planning',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'state',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'taskcategory',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'statistic',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'password_update',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'show_group_hardware',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'rule_dictionnary_software',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'rule_dictionnary_dropdown',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'budget',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'notification',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'rule_mailcollector',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'solutiontemplate',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'itilfollowuptemplate',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'solutiontemplate',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'itilfollowuptemplate',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'calendar',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'slm',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'rule_dictionnary_printer',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'problem',
                'rights' => Change::READMY | READNOTE | Change::READALL,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'cable_management',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'knowbasecategory',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'itilcategory',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'itiltemplate',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'ticketrecurrent',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'ticketcost',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'changevalidation',
                'rights' => CREATE | PURGE | CommonITILValidation::VALIDATE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'changevalidation',
                'rights' => CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'ticketvalidation',
                'rights' => PURGE | TicketValidation::CREATEREQUEST | TicketValidation::CREATEINCIDENT | TicketValidation::VALIDATEREQUEST | TicketValidation::VALIDATEINCIDENT,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'computer',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'monitor',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'software',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'networking',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'internet',
                'rights' => ALLSTANDARDRIGHT,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'printer',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'peripheral',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'cartridge',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'consumable',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'phone',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'queuednotification',
                'rights' => ALLSTANDARDRIGHT,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'contact_enterprise',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'document',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'contract',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'infocom',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'knowbase',
                'rights' => READ | UPDATE | CREATE | PURGE | KnowbaseItem::READFAQ | KnowbaseItem::PUBLISHFAQ | KnowbaseItem::COMMENTS,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'reservation',
                'rights' => READ | UPDATE | CREATE | DELETE | PURGE | ReservationItem::RESERVEANITEM,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'reports',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'dropdown',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'device',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'typedoc',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'link',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'config',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'rule_ticket',
                'rights' => READ | UPDATE | CREATE | PURGE | RuleTicket::PARENT,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'rule_import',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'rule_location',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'rule_ldap',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'rule_softwarecategories',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'search_config',
                'rights' => DisplayPreference::PERSONAL | DisplayPreference::GENERAL,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'location',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'domain',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'profile',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'user',
                'rights' => ALLSTANDARDRIGHT | User::IMPORTEXTAUTHUSERS | User::READAUTHENT | User::UPDATEAUTHENT,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'group',
                'rights' => READ | UPDATE | CREATE | PURGE | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'entity',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'transfer',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'logs',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'reminder_public',
                'rights' => READ | UPDATE | CREATE | PURGE | Reminder::PERSONAL,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'rssfeed_public',
                'rights' => READ | UPDATE | CREATE | PURGE | RSSFeed::PERSONAL,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'bookmark_public',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'backup',
                'rights' => '1024',
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'ticket',
                'rights' => ALLSTANDARDRIGHT | Ticket::READALL | Ticket::READGROUP | Ticket::READASSIGN | Ticket::ASSIGN
                    | Ticket::STEAL | Ticket::OWN | Ticket::CHANGEPRIORITY | Ticket::SURVEY,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'followup',
                'rights' => READ | UPDATE | CREATE | PURGE | ITILFollowup::UPDATEALL | ITILFollowup::ADDGROUPTICKET
                    | ITILFollowup::ADDALLTICKET | ITILFollowup::SEEPRIVATE | ITILFollowup::ADD_AS_OBSERVER,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'task',
                'rights' => CommonITILTask::SEEPUBLIC | PURGE | CommonITILTask::UPDATEALL
                    | CommonITILTask::ADDALLITEM | CommonITILTask::SEEPRIVATE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'projecttask',
                'rights' => ProjectTask::READMY | ProjectTask::UPDATEMY | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'projecttask',
                'rights' => ProjectTask::READMY | ProjectTask::UPDATEMY | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'projecttask',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'planning',
                'rights' => READ | Planning::READGROUP | Planning::READALL,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'taskcategory',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'cable_management',
                'rights' => READ | UPDATE | CREATE | DELETE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'statistic',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'password_update',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'show_group_hardware',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'rule_dictionnary_software',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'rule_dictionnary_dropdown',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'budget',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'notification',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'rule_mailcollector',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'solutiontemplate',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'itilfollowuptemplate',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'solutiontemplate',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'itilfollowuptemplate',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'calendar',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'slm',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'rule_dictionnary_printer',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'problem',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE | Problem::READALL,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'knowbasecategory',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'itilcategory',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'itiltemplate',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'ticketrecurrent',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'ticketcost',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'changevalidation',
                'rights' => CREATE | PURGE | CommonITILValidation::VALIDATE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'changevalidation',
                'rights' => CREATE | PURGE | CommonITILValidation::VALIDATE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'ticketvalidation',
                'rights' => PURGE | TicketValidation::CREATEREQUEST | TicketValidation::CREATEINCIDENT
                    | TicketValidation::VALIDATEREQUEST | TicketValidation::VALIDATEINCIDENT,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'computer',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE | UNLOCK,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'monitor',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE | UNLOCK,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'software',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE | UNLOCK,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'networking',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE | UNLOCK,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'internet',
                'rights' => ALLSTANDARDRIGHT | UNLOCK,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'printer',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE | UNLOCK,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'peripheral',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE | UNLOCK,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'cartridge',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE | UNLOCK,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'consumable',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE | UNLOCK,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'phone',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE | UNLOCK,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'contact_enterprise',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE | UNLOCK,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'document',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE | UNLOCK,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'contract',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE | UNLOCK,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'infocom',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'knowbase',
                'rights' => READ | UPDATE | CREATE | PURGE | KnowbaseItem::KNOWBASEADMIN | KnowbaseItem::READFAQ
                    | KnowbaseItem::PUBLISHFAQ | KnowbaseItem::COMMENTS,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'reservation',
                'rights' => READ | UPDATE | CREATE | DELETE | PURGE | ReservationItem::RESERVEANITEM,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'reports',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'dropdown',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'device',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'typedoc',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'link',
                'rights' => ALLSTANDARDRIGHT | UNLOCK,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'config',
                'rights' => READ | UPDATE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'rule_ticket',
                'rights' => READ | UPDATE | CREATE | PURGE | RuleTicket::PARENT,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'rule_import',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'rule_location',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'rule_ldap',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'rule_softwarecategories',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'search_config',
                'rights' => DisplayPreference::PERSONAL | DisplayPreference::GENERAL,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'location',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'domain',
                'rights' => READ | UPDATE | CREATE | DELETE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'profile',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'user',
                'rights' => ALLSTANDARDRIGHT | UNLOCK | User::IMPORTEXTAUTHUSERS | User::READAUTHENT | User::UPDATEAUTHENT,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'group',
                'rights' => READ | UPDATE | CREATE | PURGE | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'entity',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE | UNLOCK | Entity::READHELPDESK | Entity::UPDATEHELPDESK,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'transfer',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'logs',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'reminder_public',
                'rights' => ALLSTANDARDRIGHT | UNLOCK | Reminder::PERSONAL,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'rssfeed_public',
                'rights' => ALLSTANDARDRIGHT | UNLOCK | RSSFeed::PERSONAL,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'bookmark_public',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'backup',
                'rights' => '1045',
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'ticket',
                'rights' => ALLSTANDARDRIGHT | Ticket::READALL | Ticket::READGROUP | Ticket::READASSIGN | Ticket::ASSIGN
                    | Ticket::STEAL | Ticket::OWN | Ticket::CHANGEPRIORITY | Ticket::SURVEY,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'followup',
                'rights' => READ | UPDATE | CREATE | PURGE | ITILFollowup::UPDATEALL | ITILFollowup::ADDGROUPTICKET
                    | ITILFollowup::ADDALLTICKET | ITILFollowup::SEEPRIVATE | ITILFollowup::ADD_AS_OBSERVER,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'task',
                'rights' => CommonITILTask::SEEPUBLIC | PURGE | CommonITILTask::UPDATEALL
                    | CommonITILTask::ADDALLITEM | CommonITILTask::SEEPRIVATE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'project',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE | Project::READALL,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'projecttask',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'projecttask',
                'rights' => READ | ProjectTask::UPDATEMY,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'planning',
                'rights' => READ | Planning::READGROUP | Planning::READALL,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'taskcategory',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'cable_management',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'statistic',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'password_update',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'show_group_hardware',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'rule_dictionnary_software',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'rule_dictionnary_dropdown',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'budget',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'notification',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'rule_mailcollector',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'solutiontemplate',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'itilfollowuptemplate',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'solutiontemplate',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'itilfollowuptemplate',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'calendar',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'slm',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'rule_dictionnary_printer',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'problem',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE | Problem::READALL,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'knowbasecategory',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'itilcategory',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'itiltemplate',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'ticketrecurrent',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'ticketcost',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'change',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE | Change::READALL,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'changevalidation',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'ticketvalidation',
                'rights' => PURGE | TicketValidation::CREATEREQUEST | TicketValidation::CREATEINCIDENT | TicketValidation::VALIDATEREQUEST | TicketValidation::VALIDATEINCIDENT,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'computer',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'monitor',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'software',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'networking',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'internet',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'printer',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'peripheral',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'cartridge',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'consumable',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'phone',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'queuednotification',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'contact_enterprise',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'document',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'contract',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'infocom',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'knowbase',
                'rights' => KnowbaseItem::READFAQ | KnowbaseItem::COMMENTS,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'reservation',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'reports',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'dropdown',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'device',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'typedoc',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'link',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'config',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'rule_ticket',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'rule_import',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'rule_location',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'rule_ldap',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'rule_softwarecategories',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'search_config',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'location',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'domain',
                'rights' => READ | UPDATE | CREATE | DELETE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'profile',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'user',
                'rights' => READ | User::IMPORTEXTAUTHUSERS,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'group',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'entity',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'transfer',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'logs',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'reminder_public',
                'rights' => self::RIGHT_NONE | Reminder::PERSONAL,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'rssfeed_public',
                'rights' => self::RIGHT_NONE | RSSFeed::PERSONAL,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'bookmark_public',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'backup',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'ticket',
                'rights' => Ticket::READMY | UPDATE | CREATE | Ticket::READALL | Ticket::ASSIGN | Ticket::SURVEY,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'followup',
                'rights' => ITILFollowup::SEEPUBLIC | ITILFollowup::UPDATEMY | ITILFollowup::ADDMYTICKET
                    | ITILFollowup::ADDALLTICKET | ITILFollowup::SEEPRIVATE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'task',
                'rights' => READ | CommonITILTask::SEEPRIVATE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'project',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE | Project::READALL,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'project',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE | Project::READALL,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'project',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE | Project::READALL,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'planning',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'taskcategory',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'cable_management',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'statistic',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'password_update',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'show_group_hardware',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'rule_dictionnary_software',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'rule_dictionnary_dropdown',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'budget',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'notification',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'rule_mailcollector',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'state',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'state',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'calendar',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'slm',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'rule_dictionnary_printer',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'problem',
                'rights' => Problem::READALL,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'knowbasecategory',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'itilcategory',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'itiltemplate',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'ticketrecurrent',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'ticketcost',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'change',
                'rights' => UPDATE | CREATE | DELETE | PURGE | Change::READALL,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'change',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE | Change::READALL,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'ticketvalidation',
                'rights' => PURGE | TicketValidation::CREATEREQUEST | TicketValidation::CREATEINCIDENT,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'computer',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'monitor',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'software',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'networking',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'internet',
                'rights' => ALLSTANDARDRIGHT,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'printer',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'peripheral',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'cartridge',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'consumable',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'phone',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'queuednotification',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'contact_enterprise',
                'rights' => READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'document',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'contract',
                'rights' => READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'infocom',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'knowbase',
                'rights' => READ | UPDATE | CREATE | PURGE | KnowbaseItem::READFAQ | KnowbaseItem::PUBLISHFAQ | KnowbaseItem::COMMENTS,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'reservation',
                'rights' => READ | UPDATE | CREATE | DELETE | PURGE | ReservationItem::RESERVEANITEM,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'reports',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'dropdown',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'device',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'typedoc',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'link',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'config',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'rule_ticket',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'rule_import',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'rule_location',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'rule_ldap',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'rule_softwarecategories',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'search_config',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'domain',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'profile',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'user',
                'rights' => READ | UPDATE | CREATE | DELETE | PURGE | User::IMPORTEXTAUTHUSERS,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'group',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'entity',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'transfer',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'logs',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'reminder_public',
                'rights' => READ | UPDATE | CREATE | PURGE | Reminder::PERSONAL,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'rssfeed_public',
                'rights' => READ | UPDATE | CREATE | PURGE | RSSFeed::PERSONAL,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'bookmark_public',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'backup',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'ticket',
                'rights' => Ticket::READMY | UPDATE | CREATE | Ticket::READALL | Ticket::READGROUP
                    | Ticket::OWN | Ticket::SURVEY,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'followup',
                'rights' => ITILFollowup::SEEPUBLIC | ITILFollowup::UPDATEMY | ITILFollowup::ADDMYTICKET
                    | ITILFollowup::UPDATEALL | ITILFollowup::ADDALLTICKET | ITILFollowup::SEEPRIVATE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'task',
                'rights' => CommonITILTask::SEEPUBLIC | PURGE | CommonITILTask::UPDATEALL
                    | CommonITILTask::ADDALLITEM | CommonITILTask::SEEPRIVATE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'project',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'project',
                'rights' => READ | Project::READALL,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'project',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE | Project::READALL,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'planning',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'taskcategory',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'cable_management',
                'rights' => READ | UPDATE | CREATE | DELETE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'statistic',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'password_update',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'show_group_hardware',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'rule_dictionnary_software',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'rule_dictionnary_dropdown',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'budget',
                'rights' => READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'notification',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'rule_mailcollector',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'state',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'state',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'calendar',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'slm',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'rule_dictionnary_printer',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'problem',
                'rights' => Problem::READMY | Problem::READALL | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'knowbasecategory',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'itilcategory',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'location',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'itiltemplate',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'ticketrecurrent',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'ticketcost',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'change',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE | Change::READALL,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'change',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE | Change::READALL,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'ticketvalidation',
                'rights' => PURGE | TicketValidation::CREATEREQUEST | TicketValidation::CREATEINCIDENT,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'computer',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'monitor',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'software',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'networking',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'internet',
                'rights' => ALLSTANDARDRIGHT,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'printer',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'peripheral',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'cartridge',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'consumable',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'phone',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'queuednotification',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'contact_enterprise',
                'rights' => READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'document',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'contract',
                'rights' => READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'infocom',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'knowbase',
                'rights' => READ | UPDATE | CREATE | PURGE | KnowbaseItem::READFAQ | KnowbaseItem::PUBLISHFAQ | KnowbaseItem::COMMENTS,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'reservation',
                'rights' => READ | UPDATE | CREATE | DELETE | PURGE | ReservationItem::RESERVEANITEM,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'reports',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'dropdown',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'device',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'typedoc',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'link',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'config',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'rule_ticket',
                'rights' => READ | UPDATE | CREATE | PURGE | RuleTicket::PARENT,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'rule_import',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'rule_location',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'rule_ldap',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'rule_softwarecategories',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'search_config',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'domain',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'profile',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'user',
                'rights' => READ | UPDATE | CREATE | DELETE | PURGE | User::IMPORTEXTAUTHUSERS,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'group',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'entity',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'transfer',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'logs',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'reminder_public',
                'rights' => READ | UPDATE | CREATE | PURGE | Reminder::PERSONAL,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'rssfeed_public',
                'rights' => READ | UPDATE | CREATE | PURGE | RSSFeed::PERSONAL,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'bookmark_public',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'backup',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'ticket',
                'rights' => ALLSTANDARDRIGHT | Ticket::READALL | Ticket::READGROUP | Ticket::READASSIGN | Ticket::ASSIGN
                    | Ticket::STEAL | Ticket::OWN | Ticket::CHANGEPRIORITY | Ticket::SURVEY,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'followup',
                'rights' => READ | UPDATE | CREATE | PURGE | ITILFollowup::UPDATEALL | ITILFollowup::ADDGROUPTICKET
                    | ITILFollowup::ADDALLTICKET | ITILFollowup::SEEPRIVATE | ITILFollowup::ADD_AS_OBSERVER,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'task',
                'rights' => CommonITILTask::SEEPUBLIC | PURGE | CommonITILTask::UPDATEALL
                    | CommonITILTask::ADDALLITEM | CommonITILTask::SEEPRIVATE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'queuednotification',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'planning',
                'rights' => READ | Planning::READGROUP | Planning::READALL,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'taskcategory',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'cable_management',
                'rights' => READ | UPDATE | CREATE | DELETE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'statistic',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'password_update',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'show_group_hardware',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'rule_dictionnary_software',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'rule_dictionnary_dropdown',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'budget',
                'rights' => READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'notification',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'rule_mailcollector',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'changevalidation',
                'rights' => CREATE | PURGE | CommonITILValidation::VALIDATE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'state',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'calendar',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'slm',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'rule_dictionnary_printer',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'problem',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE | Problem::READALL,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'knowbasecategory',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'itilcategory',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'location',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'itiltemplate',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'ticketrecurrent',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'ticketcost',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'change',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'change',
                'rights' => Change::READMY | READNOTE | Change::READALL,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'ticketvalidation',
                'rights' => PURGE | TicketValidation::CREATEREQUEST | TicketValidation::CREATEINCIDENT | TicketValidation::VALIDATEREQUEST | TicketValidation::VALIDATEINCIDENT,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'backup',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'bookmark_public',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'budget',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'calendar',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'cartridge',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'change',
                'rights' => Change::READMY | READNOTE | Change::READALL,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'changevalidation',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'computer',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'config',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'consumable',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'contact_enterprise',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'contract',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'device',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'document',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'domain',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'dropdown',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'entity',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'followup',
                'rights' => READ | ITILFollowup::SEEPRIVATE,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'global_validation',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'group',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'infocom',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'internet',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'itilcategory',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'knowbase',
                'rights' => READ | KnowbaseItem::READFAQ | KnowbaseItem::COMMENTS,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'knowbasecategory',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'link',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'location',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'logs',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'monitor',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'cable_management',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'networking',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'notification',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'password_update',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'peripheral',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'phone',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'planning',
                'rights' => READ | Planning::READGROUP | Planning::READALL,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'printer',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'problem',
                'rights' => Change::READMY | READNOTE | Change::READALL,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'profile',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'project',
                'rights' => Change::READMY | READNOTE | Change::READALL,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'projecttask',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'queuednotification',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'reminder_public',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'reports',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'reservation',
                'rights' => READ | Reminder::PERSONAL,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'rssfeed_public',
                'rights' => READ | RSSFeed::PERSONAL,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'rule_dictionnary_dropdown',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'rule_dictionnary_printer',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'rule_dictionnary_software',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'rule_import',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'rule_location',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'rule_ldap',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'rule_mailcollector',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'rule_softwarecategories',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'rule_ticket',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'search_config',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'show_group_hardware',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'slm',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'software',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'solutiontemplate',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'itilfollowuptemplate',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'state',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'statistic',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'task',
                'rights' => READ | CommonITILTask::SEEPRIVATE,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'taskcategory',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'ticket',
                'rights' => Ticket::READMY | Ticket::READALL | Ticket::READGROUP | Ticket::READASSIGN | Ticket::SURVEY,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'ticketcost',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'ticketrecurrent',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'itiltemplate',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'ticketvalidation',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'transfer',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'typedoc',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'user',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'license',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'license',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'license',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'license',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE | UNLOCK,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'license',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'license',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'license',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'license',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'line',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'line',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'line',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'line',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE | UNLOCK,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'line',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'line',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'line',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'line',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'lineoperator',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'lineoperator',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'lineoperator',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'lineoperator',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'lineoperator',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'lineoperator',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'lineoperator',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'lineoperator',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'devicesimcard_pinpuk',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'devicesimcard_pinpuk',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'devicesimcard_pinpuk',
                'rights' => READ | UPDATE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'devicesimcard_pinpuk',
                'rights' => READ | UPDATE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'devicesimcard_pinpuk',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'devicesimcard_pinpuk',
                'rights' => READ | UPDATE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'devicesimcard_pinpuk',
                'rights' => READ | UPDATE,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'devicesimcard_pinpuk',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'certificate',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'certificate',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'certificate',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'certificate',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE | UNLOCK,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'certificate',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'certificate',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'certificate',
                'rights' => ALLSTANDARDRIGHT | READNOTE | UPDATENOTE,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'certificate',
                'rights' => READ | READNOTE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'datacenter',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'datacenter',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'datacenter',
                'rights' => ALLSTANDARDRIGHT,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'datacenter',
                'rights' => ALLSTANDARDRIGHT,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'datacenter',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'datacenter',
                'rights' => ALLSTANDARDRIGHT,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'datacenter',
                'rights' => ALLSTANDARDRIGHT,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'datacenter',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'rule_asset',
                'rights' => READ | UPDATE | CREATE | PURGE | RuleAsset::PARENT,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'personalization',
                'rights' => READ | UPDATE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'personalization',
                'rights' => READ | UPDATE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'personalization',
                'rights' => READ | UPDATE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'personalization',
                'rights' => READ | UPDATE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'personalization',
                'rights' => READ | UPDATE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'personalization',
                'rights' => READ | UPDATE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'personalization',
                'rights' => READ | UPDATE,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'personalization',
                'rights' => READ | UPDATE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'rule_asset',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'rule_asset',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'rule_asset',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'rule_asset',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'rule_asset',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'rule_asset',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'rule_asset',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'global_validation',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'global_validation',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'global_validation',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'global_validation',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'global_validation',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'global_validation',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'global_validation',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'cluster',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'cluster',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'cluster',
                'rights' => ALLSTANDARDRIGHT,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'cluster',
                'rights' => ALLSTANDARDRIGHT,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'cluster',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'cluster',
                'rights' => ALLSTANDARDRIGHT,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'cluster',
                'rights' => ALLSTANDARDRIGHT,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'cluster',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'externalevent',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'externalevent',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'externalevent',
                'rights' => ALLSTANDARDRIGHT | PlanningExternalEvent::MANAGE_BG_EVENTS,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'externalevent',
                'rights' => ALLSTANDARDRIGHT | PlanningExternalEvent::MANAGE_BG_EVENTS,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'externalevent',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'externalevent',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'externalevent',
                'rights' => ALLSTANDARDRIGHT,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'externalevent',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'dashboard',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'dashboard',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'dashboard',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'dashboard',
                'rights' => READ | UPDATE | CREATE | PURGE,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'dashboard',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'dashboard',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'dashboard',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'dashboard',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'appliance',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'appliance',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'appliance',
                'rights' => ALLSTANDARDRIGHT,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'appliance',
                'rights' => ALLSTANDARDRIGHT,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'appliance',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'appliance',
                'rights' => ALLSTANDARDRIGHT,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'appliance',
                'rights' => ALLSTANDARDRIGHT,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'appliance',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'inventory',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'inventory',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'inventory',
                'rights' => READ | Conf::IMPORTFROMFILE | Conf::UPDATECONFIG,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'inventory',
                'rights' => READ | Conf::IMPORTFROMFILE | Conf::UPDATECONFIG,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'inventory',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'inventory',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'inventory',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'inventory',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'pendingreason',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'pendingreason',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'pendingreason',
                'rights' => ALLSTANDARDRIGHT,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'pendingreason',
                'rights' => ALLSTANDARDRIGHT,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'pendingreason',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'pendingreason',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'pendingreason',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'pendingreason',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'database',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'database',
                'rights' => READ,
            ], [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'database',
                'rights' => ALLSTANDARDRIGHT,
            ], [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'database',
                'rights' => ALLSTANDARDRIGHT,
            ], [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'database',
                'rights' => self::RIGHT_NONE,
            ], [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'database',
                'rights' => ALLSTANDARDRIGHT,
            ], [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'database',
                'rights' => ALLSTANDARDRIGHT,
            ], [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'database',
                'rights' => READ,
            ],
            [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'recurrentchange',
                'rights' => self::RIGHT_NONE,
            ],
            [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'recurrentchange',
                'rights' => self::RIGHT_NONE,
            ],
            [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'recurrentchange',
                'rights' => READ,
            ],
            [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'recurrentchange',
                'rights' => ALLSTANDARDRIGHT,
            ],
            [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'recurrentchange',
                'rights' => self::RIGHT_NONE,
            ],
            [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'recurrentchange',
                'rights' => READ,
            ],
            [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'recurrentchange',
                'rights' => READ,
            ],
            [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'recurrentchange',
                'rights' => READ,
            ],
            [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'locked_field',
                'rights' => self::RIGHT_NONE,
            ],
            [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'locked_field',
                'rights' => self::RIGHT_NONE,
            ],
            [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'locked_field',
                'rights' => self::RIGHT_NONE,
            ],
            [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'locked_field',
                'rights' => CREATE | UPDATE,
            ],
            [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'locked_field',
                'rights' => self::RIGHT_NONE,
            ],
            [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'locked_field',
                'rights' => self::RIGHT_NONE,
            ],
            [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'locked_field',
                'rights' => self::RIGHT_NONE,
            ],
            [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'locked_field',
                'rights' => self::RIGHT_NONE,
            ],            [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'snmpcredential',
                'rights' => self::RIGHT_NONE,
            ],
            [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'snmpcredential',
                'rights' => self::RIGHT_NONE,
            ],
            [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'snmpcredential',
                'rights' => self::RIGHT_NONE,
            ],
            [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'snmpcredential',
                'rights' => ALLSTANDARDRIGHT,
            ],
            [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'snmpcredential',
                'rights' => self::RIGHT_NONE,
            ],
            [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'snmpcredential',
                'rights' => self::RIGHT_NONE,
            ],
            [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'snmpcredential',
                'rights' => self::RIGHT_NONE,
            ],
            [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'snmpcredential',
                'rights' => self::RIGHT_NONE,
            ],
            [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'refusedequipment',
                'rights' => self::RIGHT_NONE,
            ],
            [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'refusedequipment',
                'rights' => self::RIGHT_NONE,
            ],
            [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'refusedequipment',
                'rights' => self::RIGHT_NONE,
            ],
            [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'refusedequipment',
                'rights' => READ | UPDATE | PURGE,
            ],
            [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'refusedequipment',
                'rights' => self::RIGHT_NONE,
            ],
            [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'refusedequipment',
                'rights' => self::RIGHT_NONE,
            ],
            [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'refusedequipment',
                'rights' => self::RIGHT_NONE,
            ],
            [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'refusedequipment',
                'rights' => self::RIGHT_NONE,
            ],
            [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'agent',
                'rights' => self::RIGHT_NONE,
            ],
            [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'agent',
                'rights' => self::RIGHT_NONE,
            ],
            [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'agent',
                'rights' => self::RIGHT_NONE,
            ],
            [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'agent',
                'rights' => READ | UPDATE | PURGE,
            ],
            [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'agent',
                'rights' => self::RIGHT_NONE,
            ],
            [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'agent',
                'rights' => self::RIGHT_NONE,
            ],
            [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'agent',
                'rights' => self::RIGHT_NONE,
            ],
            [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'agent',
                'rights' => self::RIGHT_NONE,
            ],
            [
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'name' => 'unmanaged',
                'rights' => self::RIGHT_NONE,
            ],
            [
                'profiles_id' => self::PROFILE_OBSERVER,
                'name' => 'unmanaged',
                'rights' => self::RIGHT_NONE,
            ],
            [
                'profiles_id' => self::PROFILE_ADMIN,
                'name' => 'unmanaged',
                'rights' => self::RIGHT_NONE,
            ],
            [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'unmanaged',
                'rights' => READ | UPDATE | DELETE | PURGE,
            ],
            [
                'profiles_id' => self::PROFILE_HOTLINER,
                'name' => 'unmanaged',
                'rights' => self::RIGHT_NONE,
            ],
            [
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'name' => 'unmanaged',
                'rights' => self::RIGHT_NONE,
            ],
            [
                'profiles_id' => self::PROFILE_SUPERVISOR,
                'name' => 'unmanaged',
                'rights' => self::RIGHT_NONE,
            ],
            [
                'profiles_id' => self::PROFILE_READ_ONLY,
                'name' => 'unmanaged',
                'rights' => self::RIGHT_NONE,

            ],
            [
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'system_logs',
                'rights' => READ,

            ],
        ];


        $tables['glpi_profiles'] = [
            [
                'id' => self::PROFILE_SELF_SERVICE,
                'name' => 'Self-Service',
                'interface' => 'helpdesk',
                'is_default' => '1',
                'helpdesk_hardware' => '1',
                'helpdesk_item_type' => '["Computer","Monitor","NetworkEquipment","Peripheral","Phone","Printer","Software", "DCRoom", "Rack", "Enclosure", "Database"]',
                'ticket_status' => '{"1":{"2":0,"3":0,"4":0,"5":0,"6":0},"2":{"1":0,"3":0,"4":0,"5":0,"6":0},"3":{"1":0,"2":0,"4":0,"5":0,"6":0},"4":{"1":0,"2":0,"3":0,"5":0,"6":0},"5":{"1":0,"2":0,"3":0,"4":0},"6":{"1":0,"2":0,"3":0,"4":0,"5":0}}',
                'comment' => '',
                'problem_status' => '[]',
                'create_ticket_on_login' => '0',
                'tickettemplates_id' => '0',
                'change_status' => null,
                'managed_domainrecordtypes' => '[]',
            ], [
                'id' => self::PROFILE_OBSERVER,
                'name' => 'Observer',
                'interface' => 'central',
                'is_default' => '0',
                'helpdesk_hardware' => '1',
                'helpdesk_item_type' => '["Computer","Monitor","NetworkEquipment","Peripheral","Phone","Printer","Software", "DCRoom", "Rack", "Enclosure", "Database"]',
                'ticket_status' => '[]',
                'comment' => '',
                'problem_status' => '[]',
                'create_ticket_on_login' => '0',
                'tickettemplates_id' => '0',
                'change_status' => null,
                'managed_domainrecordtypes' => '[]',
            ], [
                'id' => self::PROFILE_ADMIN,
                'name' => 'Admin',
                'interface' => 'central',
                'is_default' => '0',
                'helpdesk_hardware' => '3',
                'helpdesk_item_type' => '["Computer","Monitor","NetworkEquipment","Peripheral","Phone","Printer","Software", "DCRoom", "Rack", "Enclosure", "Database"]',
                'ticket_status' => '[]',
                'comment' => '',
                'problem_status' => '[]',
                'create_ticket_on_login' => '0',
                'tickettemplates_id' => '0',
                'change_status' => null,
                'managed_domainrecordtypes' => '[-1]',
            ], [
                'id' => self::PROFILE_SUPER_ADMIN,
                'name' => 'Super-Admin',
                'interface' => 'central',
                'is_default' => '0',
                'helpdesk_hardware' => '3',
                'helpdesk_item_type' => '["Computer","Monitor","NetworkEquipment","Peripheral","Phone","Printer","Software", "DCRoom", "Rack", "Enclosure", "Database"]',
                'ticket_status' => '[]',
                'comment' => '',
                'problem_status' => '[]',
                'create_ticket_on_login' => '0',
                'tickettemplates_id' => '0',
                'change_status' => null,
                'managed_domainrecordtypes' => '[-1]',
            ], [
                'id' => self::PROFILE_HOTLINER,
                'name' => 'Hotliner',
                'interface' => 'central',
                'is_default' => '0',
                'helpdesk_hardware' => '3',
                'helpdesk_item_type' => '["Computer","Monitor","NetworkEquipment","Peripheral","Phone","Printer","Software", "DCRoom", "Rack", "Enclosure", "Database"]',
                'ticket_status' => '[]',
                'comment' => '',
                'problem_status' => '[]',
                'create_ticket_on_login' => '1',
                'tickettemplates_id' => '0',
                'change_status' => null,
                'managed_domainrecordtypes' => '[]',
            ], [
                'id' => self::PROFILE_TECHNICIAN,
                'name' => 'Technician',
                'interface' => 'central',
                'is_default' => '0',
                'helpdesk_hardware' => '3',
                'helpdesk_item_type' => '["Computer","Monitor","NetworkEquipment","Peripheral","Phone","Printer","Software", "DCRoom", "Rack", "Enclosure", "Database"]',
                'ticket_status' => '[]',
                'comment' => '',
                'problem_status' => '[]',
                'create_ticket_on_login' => '0',
                'tickettemplates_id' => '0',
                'change_status' => null,
                'managed_domainrecordtypes' => '[]',
            ], [
                'id' => self::PROFILE_SUPERVISOR,
                'name' => 'Supervisor',
                'interface' => 'central',
                'is_default' => '0',
                'helpdesk_hardware' => '3',
                'helpdesk_item_type' => '["Computer","Monitor","NetworkEquipment","Peripheral","Phone","Printer","Software", "DCRoom", "Rack", "Enclosure", "Database"]',
                'ticket_status' => '[]',
                'comment' => '',
                'problem_status' => '[]',
                'create_ticket_on_login' => '0',
                'tickettemplates_id' => '0',
                'change_status' => null,
                'managed_domainrecordtypes' => '[]',
            ], [
                'id' => self::PROFILE_READ_ONLY,
                'name' => 'Read-Only',
                'interface' => 'central',
                'is_default' => '0',
                'helpdesk_hardware' => '0',
                'helpdesk_item_type' => '[]',
                'ticket_status' => '{"1":{"2":0,"3":0,"4":0,"5":0,"6":0},"2":{"1":0,"3":0,"4":0,"5":0,"6":0},"3":{"1":0,"2":0,"4":0,"5":0,"6":0},"4":{"1":0,"2":0,"3":0,"5":0,"6":0},"5":{"1":0,"2":0,"3":0,"4":0,"6":0},"6":{"1":0,"2":0,"3":0,"4":0,"5":0}}',
                'comment' => 'This profile defines read-only access. It is used when objects are locked. It can also be used to give to users rights to unlock objects.',
                'problem_status' => '{"1":{"7":0,"2":0,"3":0,"4":0,"5":0,"8":0,"6":0},"7":{"1":0,"2":0,"3":0,"4":0,"5":0,"8":0,"6":0},"2":{"1":0,"7":0,"3":0,"4":0,"5":0,"8":0,"6":0},"3":{"1":0,"7":0,"2":0,"4":0,"5":0,"8":0,"6":0},"4":{"1":0,"7":0,"2":0,"3":0,"5":0,"8":0,"6":0},"5":{"1":0,"7":0,"2":0,"3":0,"4":0,"8":0,"6":0},"8":{"1":0,"7":0,"2":0,"3":0,"4":0,"5":0,"6":0},"6":{"1":0,"7":0,"2":0,"3":0,"4":0,"5":0,"8":0}}',
                'create_ticket_on_login' => '0',
                'tickettemplates_id' => '0',
                'change_status' => '{"1":{"9":0,"10":0,"7":0,"4":0,"11":0,"12":0,"5":0,"8":0,"6":0},"9":{"1":0,"10":0,"7":0,"4":0,"11":0,"12":0,"5":0,"8":0,"6":0},"10":{"1":0,"9":0,"7":0,"4":0,"11":0,"12":0,"5":0,"8":0,"6":0},"7":{"1":0,"9":0,"10":0,"4":0,"11":0,"12":0,"5":0,"8":0,"6":0},"4":{"1":0,"9":0,"10":0,"7":0,"11":0,"12":0,"5":0,"8":0,"6":0},"11":{"1":0,"9":0,"10":0,"7":0,"4":0,"12":0,"5":0,"8":0,"6":0},"12":{"1":0,"9":0,"10":0,"7":0,"4":0,"11":0,"5":0,"8":0,"6":0},"5":{"1":0,"9":0,"10":0,"7":0,"4":0,"11":0,"12":0,"8":0,"6":0},"8":{"1":0,"9":0,"10":0,"7":0,"4":0,"11":0,"12":0,"5":0,"6":0},"6":{"1":0,"9":0,"10":0,"7":0,"4":0,"11":0,"12":0,"5":0,"8":0}}',
                'managed_domainrecordtypes' => '[]',
            ],
        ];

        $tables['glpi_profiles_users'] = [
            [
                'id' => '2',
                'users_id' => self::USER_GLPI,
                'profiles_id' => self::PROFILE_SUPER_ADMIN,
                'entities_id' => '0',
                'is_recursive' => '1',
                'is_dynamic' => '0',
            ], [
                'id' => '3',
                'users_id' => self::USER_POST_ONLY,
                'profiles_id' => self::PROFILE_SELF_SERVICE,
                'entities_id' => '0',
                'is_recursive' => '1',
                'is_dynamic' => '0',
            ], [
                'id' => '4',
                'users_id' => self::USER_TECH,
                'profiles_id' => self::PROFILE_TECHNICIAN,
                'entities_id' => '0',
                'is_recursive' => '1',
                'is_dynamic' => '0',
            ], [
                'id' => '5',
                'users_id' => self::USER_NORMAL,
                'profiles_id' => self::PROFILE_OBSERVER,
                'entities_id' => '0',
                'is_recursive' => '1',
                'is_dynamic' => '0',
            ],
        ];

        $tables['glpi_projectstates'] = [
            [
                'id' => '1',
                'name' => 'New',
                'color' => '#06ff00',
                'is_finished' => '0',
            ], [
                'id' => '2',
                'name' => 'Processing',
                'color' => '#ffb800',
                'is_finished' => '0',
            ], [
                'id' => '3',
                'name' => 'Closed',
                'color' => '#ff0000',
                'is_finished' => '1',
            ],
        ];

        $tables['glpi_requesttypes'] = [
            [
                'id' => '1',
                'name' => 'Helpdesk',
                'is_helpdesk_default' => '1',
                'is_followup_default' => '1',
                'is_mail_default' => '0',
                'is_mailfollowup_default' => '0',
            ], [
                'id' => '2',
                'name' => 'E-Mail',
                'is_helpdesk_default' => '0',
                'is_followup_default' => '0',
                'is_mail_default' => '1',
                'is_mailfollowup_default' => '1',
            ], [
                'id' => '3',
                'name' => 'Phone',
                'is_helpdesk_default' => '0',
                'is_followup_default' => '0',
                'is_mail_default' => '0',
                'is_mailfollowup_default' => '0',
            ], [
                'id' => '4',
                'name' => 'Direct',
                'is_helpdesk_default' => '0',
                'is_followup_default' => '0',
                'is_mail_default' => '0',
                'is_mailfollowup_default' => '0',
            ], [
                'id' => '5',
                'name' => 'Written',
                'is_helpdesk_default' => '0',
                'is_followup_default' => '0',
                'is_mail_default' => '0',
                'is_mailfollowup_default' => '0',
            ], [
                'id' => '6',
                'name' => 'Other',
                'is_helpdesk_default' => '0',
                'is_followup_default' => '0',
                'is_mail_default' => '0',
                'is_mailfollowup_default' => '0',
            ],
        ];

        $tables['glpi_rulerightparameters'] = [
            [
                'id' => 1,
                'name' => '(LDAP)Organization',
                'value' => 'o',
            ], [
                'id' => '2',
                'name' => '(LDAP)Common Name',
                'value' => 'cn',
            ], [
                'id' => '3',
                'name' => '(LDAP)Department Number',
                'value' => 'departmentnumber',
            ], [
                'id' => '4',
                'name' => '(LDAP)Email',
                'value' => 'mail',
            ], [
                'id' => '5',
                'name' => 'Object Class',
                'value' => 'objectclass',
            ], [
                'id' => '6',
                'name' => '(LDAP)User ID',
                'value' => 'uid',
            ], [
                'id' => '7',
                'name' => '(LDAP)Telephone Number',
                'value' => 'phone',
            ], [
                'id' => '8',
                'name' => '(LDAP)Employee Number',
                'value' => 'employeenumber',
            ], [
                'id' => '9',
                'name' => '(LDAP)Manager',
                'value' => 'manager',
            ], [
                'id' => '10',
                'name' => '(LDAP)DistinguishedName',
                'value' => 'dn',
            ], [
                'id' => '12',
                'name' => '(AD)User ID',
                'value' => 'samaccountname',
            ], [
                'id' => '13',
                'name' => '(LDAP) Title',
                'value' => 'title',
            ], [
                'id' => '14',
                'name' => '(LDAP) MemberOf',
                'value' => 'memberof',
            ],
        ];

        $tables['glpi_softwarecategories'] = [
            [
                'id' => '1',
                'name' => 'Inventoried',
                'completename' => 'Software from inventories',
                'level' => '1',
            ],
        ];

        $tables['glpi_softwarelicensetypes'] = [
            [
                'id' => 1,
                'name' => 'OEM',
                'is_recursive' => 1,
                'completename' => 'OEM',
            ],
        ];

        $tables['glpi_ssovariables'] = [
            [
                'id' => 1,
                'name' => 'HTTP_AUTH_USER',
            ], [
                'id' => 2,
                'name' => 'REMOTE_USER',
            ], [
                'id' => 3,
                'name' => 'PHP_AUTH_USER',
            ], [
                'id' => 4,
                'name' => 'USERNAME',
            ], [
                'id' => 5,
                'name' => 'REDIRECT_REMOTE_USER',
            ], [
                'id' => 6,
                'name' => 'HTTP_REMOTE_USER',
            ],
        ];

        $tables['glpi_tickettemplates'] = [
            [
                'id' => 1,
                'name' => 'Default',
                'entities_id' => 0,
                'is_recursive' => 1,
            ],
        ];

        $tables['glpi_changetemplates'] = [
            [
                'id' => 1,
                'name' => 'Default',
                'entities_id' => 0,
                'is_recursive' => 1,
            ],
        ];

        $tables['glpi_problemtemplates'] = [
            [
                'id' => 1,
                'name' => 'Default',
                'entities_id' => 0,
                'is_recursive' => 1,
            ],
        ];

        $tables['glpi_tickettemplatemandatoryfields'] = [
            [
                'id' => 1,
                'tickettemplates_id' => 1,
                'num' => 21,
            ],
        ];

        $tables['glpi_changetemplatemandatoryfields'] = [
            [
                'id' => 1,
                'changetemplates_id' => 1,
                'num' => 21,
            ],
        ];

        $tables['glpi_problemtemplatemandatoryfields'] = [
            [
                'id' => 1,
                'problemtemplates_id' => 1,
                'num' => 21,
            ],
        ];

        $tables['glpi_transfers'] = [
            [
                'id' => '1',
                'name' => 'complete',
                'keep_ticket' => '2',
                'keep_networklink' => '2',
                'keep_reservation' => 1,
                'keep_history' => 1,
                'keep_device' => 1,
                'keep_infocom' => 1,
                'keep_dc_monitor' => 1,
                'clean_dc_monitor' => 1,
                'keep_dc_phone' => 1,
                'clean_dc_phone' => 1,
                'keep_dc_peripheral' => 1,
                'clean_dc_peripheral' => 1,
                'keep_dc_printer' => 1,
                'clean_dc_printer' => 1,
                'keep_supplier' => 1,
                'clean_supplier' => 1,
                'keep_contact' => 1,
                'clean_contact' => 1,
                'keep_contract' => 1,
                'clean_contract' => 1,
                'keep_software' => 1,
                'clean_software' => 1,
                'keep_document' => 1,
                'clean_document' => 1,
                'keep_cartridgeitem' => 1,
                'clean_cartridgeitem' => 1,
                'keep_cartridge' => 1,
                'keep_consumable' => 1,
                'keep_disk' => 1,
                'keep_certificate' => 1,
                'clean_certificate' => 1,
                'lock_updated_fields' => 0,
            ],
        ];

        $tables['glpi_users'] = [
            [
                'id' => self::USER_GLPI,
                'name' => 'glpi',
                'realname' => null,
                'password' => '$2y$10$rXXzbc2ShaiCldwkw4AZL.n.9QSH7c0c9XJAyyjrbL9BwmWditAYm',
                'language' => null,
                'list_limit' => '20',
                'authtype' => '1',
            ], [
                'id' => self::USER_POST_ONLY,
                'name' => 'post-only',
                'realname' => null,
                'password' => '$2y$10$dTMar1F3ef5X/H1IjX9gYOjQWBR1K4bERGf4/oTPxFtJE/c3vXILm',
                'language' => 'en_GB',
                'list_limit' => '20',
                'authtype' => '1',
            ], [
                'id' => self::USER_TECH,
                'name' => 'tech',
                'realname' => null,
                'password' => '$2y$10$.xEgErizkp6Az0z.DHyoeOoenuh0RcsX4JapBk2JMD6VI17KtB1lO',
                'language' => 'en_GB',
                'list_limit' => '20',
                'authtype' => '1',
            ], [
                'id' => self::USER_NORMAL,
                'name' => 'normal',
                'realname' => null,
                'password' => '$2y$10$Z6doq4zVHkSPZFbPeXTCluN1Q/r0ryZ3ZsSJncJqkN3.8cRiN0NV.',
                'language' => 'en_GB',
                'list_limit' => '20',
                'authtype' => '1',
            ], [
                'id' => self::USER_SYSTEM,
                'name' => 'glpi-system',
                'realname' => 'Support',
                'password' => '',
                'language' => null,
                'list_limit' => null,
                'authtype' => 1,
            ],
        ];

        $tables['glpi_devicefirmwaretypes'] = [
            [
                'id' => '1',
                'name' => 'BIOS',
            ],
            [
                'id' => '2',
                'name' => 'UEFI',
            ],
            [
                'id' => '3',
                'name' => 'Firmware',
            ],
        ];

        $tables[DomainRecordType::getTable()] = DomainRecordType::getDefaults();
        $tables[DomainRelation::getTable()] = DomainRelation::getDefaults();
        $tables[NetworkPortType::getTable()] = Sanitizer::encodeHtmlSpecialCharsRecursive(NetworkPortType::getDefaults());

        $tables['glpi_agenttypes'] = [
            [
                'id' => 1,
                'name' => 'Core'
            ]
        ];

        $tables[SNMPCredential::getTable()] = [
            [
                'id' => 1,
                'name' => 'Public community v1',
                'snmpversion' => 1,
                'community' => 'public'
            ],
            [
                'id' => 2,
                'name' => 'Public community v2c',
                'snmpversion' => 2,
                'community' => 'public'
            ]
        ];

        return $tables;
    }
};

return $empty_data_builder->getEmptyData();
