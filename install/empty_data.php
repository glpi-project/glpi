<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

$tables = [];

$tables['glpi_apiclients'] = [
   [
      'id'               => 1,
      'entities_id'      => 0,
      'is_recursive'     => 1,
      'name'             => 'full access from localhost',
      'is_active'        => 1,
      'ipv4_range_start' => "2130706433", //value from MySQL INET_ATON('127.0.0.1')
      'ipv4_range_end'   => "2130706433", //value from MySQL INET_ATON('127.0.0.1')
      'ipv6'             => '::1',
   ],
];

$tables['glpi_blacklists'] = [
   'COLUMNS'   => ['type', 'name', 'value'],
   'VALUES'    => [
      [1, 'empty IP', ''],
      [1, 'localhost', '127.0.0.1'],
      [1, 'zero IP', '0.0.0.0'],
      [2, 'empty MAC', '']
   ]
];

$tables['glpi_calendars'] = [
   [
      'id'             => 1,
      'name'           => 'Default',
      'entities_id'    => 0,
      'is_recursive'   => 1,
      'comment'        => 'Default calendar',
      'cache_duration' => '[0,43200,43200,43200,43200,43200,0]',
   ],
];

$tables['glpi_calendarsegments'] = [];
for ($i = 1; $i < 6; ++$i) {
   $tables['glpi_calendarsegments'][] = [
      'calendars_id' => 1,
      'day'          => $i,
      'begin'        => '08:00:00',
      'end'          => '20:00:00',
   ];
}

$default_prefs = [
   'version'                                 => 'FILLED AT INSTALL',
   'show_jobs_at_login'                      => '0',
   'cut'                                     => '250',
   'list_limit'                              => '15',
   'list_limit_max'                          => '50',
   'url_maxlength'                           => '30',
   'event_loglevel'                          => '5',
   'notifications_mailing'                   => '0',
   'admin_email'                             => 'admsys@localhost',
   'admin_email_name'                        => '',
   'admin_reply'                             => '',
   'admin_reply_name'                        => '',
   'mailing_signature'                       => 'SIGNATURE',
   'use_anonymous_helpdesk'                  => '0',
   'use_anonymous_followups'                 => '0',
   'language'                                => 'en_GB',
   'priority_1'                              => '#fff2f2',
   'priority_2'                              => '#ffe0e0',
   'priority_3'                              => '#ffcece',
   'priority_4'                              => '#ffbfbf',
   'priority_5'                              => '#ffadad',
   'priority_6'                              => '#ff5555',
   'date_tax'                                => '2005-12-31',
   'cas_host'                                => '',
   'cas_port'                                => '443',
   'cas_uri'                                 => '',
   'cas_logout'                              => '',
   'existing_auth_server_field_clean_domain' => '0',
   'planning_begin'                          => '08:00:00',
   'planning_end'                            => '20:00:00',
   'utf8_conv'                               => '1',
   'use_public_faq'                          => '0',
   'url_base'                                => 'http://localhost/glpi/',
   'show_link_in_mail'                       => '0',
   'text_login'                              => '',
   'founded_new_version'                     => '',
   'dropdown_max'                            => '100',
   'ajax_wildcard'                           => '*',
   'ajax_limit_count'                        => '10',
   'use_ajax_autocompletion'                 => '1',
   'is_users_auto_add'                       => '1',
   'date_format'                             => '0',
   'number_format'                           => '0',
   'csv_delimiter'                           => ';',
   'is_ids_visible'                          => '0',
   'smtp_mode'                               => '0',
   'smtp_host'                               => '',
   'smtp_port'                               => '25',
   'smtp_username'                           => '',
   'proxy_name'                              => '',
   'proxy_port'                              => '8080',
   'proxy_user'                              => '',
   'add_followup_on_update_ticket'           => '1',
   'keep_tickets_on_delete'                  => '0',
   'time_step'                               => '5',
   'decimal_number'                          => '2',
   'helpdesk_doc_url'                        => '',
   'central_doc_url'                         => '',
   'documentcategories_id_forticket'         => '0',
   'monitors_management_restrict'            => '2',
   'phones_management_restrict'              => '2',
   'peripherals_management_restrict'         => '2',
   'printers_management_restrict'            => '2',
   'use_log_in_files'                        => '1',
   'time_offset'                             => '0',
   'is_contact_autoupdate'                   => '1',
   'is_user_autoupdate'                      => '1',
   'is_group_autoupdate'                     => '1',
   'is_location_autoupdate'                  => '1',
   'state_autoupdate_mode'                   => '0',
   'is_contact_autoclean'                    => '0',
   'is_user_autoclean'                       => '0',
   'is_group_autoclean'                      => '0',
   'is_location_autoclean'                   => '0',
   'state_autoclean_mode'                    => '0',
   'use_flat_dropdowntree'                   => '0',
   'use_autoname_by_entity'                  => '1',
   'softwarecategories_id_ondelete'          => '1',
   'x509_email_field'                        => '',
   'x509_cn_restrict'                        => '',
   'x509_o_restrict'                         => '',
   'x509_ou_restrict'                        => '',
   'default_mailcollector_filesize_max'      => '2097152',
   'followup_private'                        => '0',
   'task_private'                            => '0',
   'default_software_helpdesk_visible'       => '1',
   'names_format'                            => '0',
   'default_requesttypes_id'                 => '1',
   'use_noright_users_add'                   => '1',
   'cron_limit'                              => '5',
   'priority_matrix'                         => '{"1":{"1":1,"2":1,"3":2,"4":2,"5":2},"2":{"1":1,"2":2,"3":2,"4":3,"5":3},"3":{"1":2,"2":2,"3":3,"4":4,"5":4},"4":{"1":2,"2":3,"3":4,"4":4,"5":5},"5":{"1":2,"2":3,"3":4,"4":5,"5":5}}',
   'urgency_mask'                            => '62',
   'impact_mask'                             => '62',
   'user_deleted_ldap'                       => '0',
   'auto_create_infocoms'                    => '0',
   'use_slave_for_search'                    => '0',
   'proxy_passwd'                            => '',
   'smtp_passwd'                             => '',
   'transfers_id_auto'                       => '0',
   'show_count_on_tabs'                      => '1',
   'refresh_ticket_list'                     => '0',
   'set_default_tech'                        => '1',
   'allow_search_view'                       => '2',
   'allow_search_all'                        => '0',
   'allow_search_global'                     => '1',
   'display_count_on_home'                   => '5',
   'use_password_security'                   => '0',
   'password_min_length'                     => '8',
   'password_need_number'                    => '1',
   'password_need_letter'                    => '1',
   'password_need_caps'                      => '1',
   'password_need_symbol'                    => '1',
   'use_check_pref'                          => '0',
   'notification_to_myself'                  => '1',
   'duedateok_color'                         => '#06ff00',
   'duedatewarning_color'                    => '#ffb800',
   'duedatecritical_color'                   => '#ff0000',
   'duedatewarning_less'                     => '20',
   'duedatecritical_less'                    => '5',
   'duedatewarning_unit'                     => '%',
   'duedatecritical_unit'                    => '%',
   'realname_ssofield'                       => '',
   'firstname_ssofield'                      => '',
   'email1_ssofield'                         => '',
   'email2_ssofield'                         => '',
   'email3_ssofield'                         => '',
   'email4_ssofield'                         => '',
   'phone_ssofield'                          => '',
   'phone2_ssofield'                         => '',
   'mobile_ssofield'                         => '',
   'comment_ssofield'                        => '',
   'title_ssofield'                          => '',
   'category_ssofield'                       => '',
   'language_ssofield'                       => '',
   'entity_ssofield'                         => '',
   'registration_number_ssofield'            => '',
   'ssovariables_id'                         => '0',
   'translate_kb'                            => '0',
   'translate_dropdowns'                     => '0',
   'pdffont'                                 => 'helvetica',
   'keep_devices_when_purging_item'          => '0',
   'maintenance_mode'                        => '0',
   'maintenance_text'                        => '',
   'attach_ticket_documents_to_mail'         => '0',
   'backcreated'                             => '0',
   'task_state'                              => '1',
   'layout'                                  => 'lefttab',
   'palette'                                 => 'auror',
   'lock_use_lock_item'                      => '0',
   'lock_autolock_mode'                      => '1',
   'lock_directunlock_notification'          => '0',
   'lock_item_list'                          => '[]',
   'lock_lockprofile_id'                     => '8',
   'set_default_requester'                   => '1',
   'highcontrast_css'                        => '0',
   'smtp_check_certificate'                  => '1',
   'enable_api'                              => '0',
   'enable_api_login_credentials'            => '0',
   'enable_api_login_external_token'         => '1',
   'url_base_api'                            => 'http://localhost/glpi/api',
   'login_remember_time'                     => '604800',
   'login_remember_default'                  => '1',
   'use_notifications'                       => '0',
   'notifications_ajax'                      => '0',
   'notifications_ajax_check_interval'       => '5',
   'notifications_ajax_sound'                => null,
   'notifications_ajax_icon_url'             => '/pics/glpi.png',
   'dbversion'                               => 'FILLED AT INSTALL',
   'smtp_max_retries'                        => '5',
   'smtp_sender'                             => null,
   'from_email'                              => null,
   'from_email_name'                         => null,
   'instance_uuid'                           => null,
   'registration_uuid'                       => null,
   'smtp_retry_time'                         => '5',
   'purge_addrelation'                       => '0',
   'purge_deleterelation'                    => '0',
   'purge_createitem'                        => '0',
   'purge_deleteitem'                        => '0',
   'purge_restoreitem'                       => '0',
   'purge_updateitem'                        => '0',
   'purge_item_software_install'             => '0',
   'purge_software_item_install'             => '0',
   'purge_software_version_install'          => '0',
   'purge_infocom_creation'                  => '0',
   'purge_profile_user'                      => '0',
   'purge_group_user'                        => '0',
   'purge_adddevice'                         => '0',
   'purge_updatedevice'                      => '0',
   'purge_deletedevice'                      => '0',
   'purge_connectdevice'                     => '0',
   'purge_disconnectdevice'                  => '0',
   'purge_userdeletedfromldap'               => '0',
   'purge_comments'                          => '0',
   'purge_datemod'                           => '0',
   'purge_all'                               => '0',
   'purge_user_auth_changes'                 => '0',
   'purge_plugins'                           => '0',
   'display_login_source'                    => '1',
   'devices_in_menu'                         => '["Item_DeviceSimcard"]',
];

$default_prefs_values = [];
foreach ($default_prefs as $name => $value) {
   $default_prefs_values[] = [
      'context' => 'core',
      'name'    => $name,
      'value'   => $value,
   ];
}
$tables['glpi_configs'] = [
   'COLUMNS'   => ['context', 'name', 'value'],
   'VALUES'    => $default_prefs_values
];

$tables['glpi_crontasks'] = [
   'COLUMNS'   => ['id', 'itemtype', 'name', 'frequency', 'param', 'state', 'mode', 'lastrun', 'logs_lifetime'],
   'VALUES'    => [
      [2, 'CartridgeItem', 'cartridge', '86400', 10, 0, 1, null, 30],
      [3, 'ConsumableItem', 'consumable', '86400', 10, 0, 1, null, 30],
      [4, 'SoftwareLicense', 'software', '86400', null, 0, 1, null, 30],
      [5, 'Contract', 'contract', '86400', null, 1, 1, '2010-05-06 09:31:02', 30],
      [6, 'InfoCom', 'infocom', '86400', null, 1, 1, '2011-01-18 11:40:43', 30],
      [7, 'CronTask', 'logs', '86400', '30', 0, 1, null, 30],
      [9, 'MailCollector', 'mailgate', '600', '10', 1, 1, '2011-06-28 11:34:37', 30],
      [10, 'DBconnection', 'checkdbreplicate', '300', null, 0, 1, null, 30],
      [11, 'CronTask', 'checkupdate', '604800', null, 0, 1, null, 30],
      [12, 'CronTask', 'session', '86400', null, 1, 1, '2011-08-30 08:22:27', 30],
      [13, 'CronTask', 'graph', 3600, null, 1, 1, '2011-12-06 09:48:42', 30],
      [14, 'ReservationItem', 'reservation', 3600, null, 1, 1, '2012-04-05 20:31:57', 30],
      [15, 'Ticket', 'closeticket', 43200, null, 1, 1, '2012-04-05 20:31:57', 30],
      [16, 'Ticket', 'alertnotclosed', 43200, null, 1, 1, '2014-04-16 15:32:00', 30],
      [17, 'SlaLevel_Ticket', 'slaticket', 300, null, 1, 1, '2014-06-18 08:02:00', 30],
      [18, 'Ticket', 'createinquest', 86400, null, 1, 1, null, 30],
      [19, 'CronTask', 'watcher', 86400, null, 1, 1, null, 30],
      [20, 'TicketRecurrent', 'ticketrecurrent', 3600, null, 1, 1, null, 30],
      [21, 'PlanningRecall', 'planningrecall', 300, null, 1, 1, null, 30],
      [22, 'QueuedNotification', 'queuednotification', 60, 50, 1, 1, null, 30],
      [23, 'QueuedNotification', 'queuednotificationclean', 86400, 30, 1, 1, null, 30],
      [24, 'CronTask', 'temp', 3600, null, 1, 1, null, 30],
      [25, 'MailCollector', 'mailgateerror', 86400, null, 1, 1, null, 30],
      [26, 'CronTask', 'circularlogs', 86400, 4, 0, 1, null, 30],
      [27, 'ObjectLock', 'unlockobject', 86400, 4, 0, 1, null, 30],
      [28, 'SavedSearch', 'countAll', 604800, null, 0, 1, null, 10],
      [29, 'SavedSearch_Alert', 'savedsearchesalerts', 86400, null, 0, 1, null, 10],
      [30, 'Telemetry', 'telemetry', 2592000, null, 0, 1, null, 10],
      [31, 'Certificate', 'certificate', 86400, null, 0, 1, null, 10],
      [32, 'OlaLevel_Ticket', 'olaticket', 300, null, 1, 1, '2014-06-18 08:02:00', 30],
      [33, 'PurgeLogs', 'PurgeLogs', 604800, 24, 1, 2, null, 30],
      [34, 'Ticket', 'purgeticket', 43200, null, 0, 1, null, 30],
      [35, 'Document', 'cleanorphans', 43200, null, 0, 1, null, 30]
   ]
];

$tables['glpi_devicememorytypes'] = [
   'COLUMNS'   => ['name'],
   'VALUES'    => [
      ['EDO'],
      ['DDR'],
      ['SDRAM'],
      ['SDRAM-2'],
   ]
];

$tables['glpi_devicesimcardtypes'] = [
   'COLUMNS'   => ['name'],
   'VALUES'    => [
      ['Full SIM'],
      ['Mini SIM'],
      ['Micro SIM'],
      ['Nano SIM'],
   ]
];

$tables['glpi_displaypreferences'] = [
   'COLUMNS'   => ['itemtype', 'num', 'rank'],
   'VALUES'    => [
      ['Computer', '4', '4'],
      ['Computer', '45', '6'],
      ['Computer', '40', '5'],
      ['Computer', '5', '3'],
      ['Computer', '23', '2'],
      ['DocumentType', '3', '1'],
      ['Monitor', '31', '1'],
      ['Monitor', '23', '2'],
      ['Monitor', '3', '3'],
      ['Monitor', '4', '4'],
      ['Printer', '31', '1'],
      ['NetworkEquipment', '31', '1'],
      ['NetworkEquipment', '23', '2'],
      ['Printer', '23', '2'],
      ['Printer', '3', '3'],
      ['Software', '4', '3'],
      ['Software', '5', '2'],
      ['Software', '23', '1'],
      ['CartridgeItem', '4', '2'],
      ['CartridgeItem', '34', '1'],
      ['Peripheral', '3', '3'],
      ['Peripheral', '23', '2'],
      ['Peripheral', '31', '1'],
      ['Computer', '31', '1'],
      ['Computer', '3', '7'],
      ['Computer', '19', '8'],
      ['Computer', '17', '9'],
      ['NetworkEquipment', '3', '3'],
      ['NetworkEquipment', '4', '4'],
      ['NetworkEquipment', '11', '6'],
      ['NetworkEquipment', '19', '7'],
      ['Printer', '4', '4'],
      ['Printer', '19', '6'],
      ['Monitor', '19', '6'],
      ['Monitor', '7', '7'],
      ['Peripheral', '4', '4'],
      ['Peripheral', '19', '6'],
      ['Peripheral', '7', '7'],
      ['Contact', '3', '1'],
      ['Contact', '4', '2'],
      ['Contact', '5', '3'],
      ['Contact', '6', '4'],
      ['Contact', '9', '5'],
      ['Supplier', '9', '1'],
      ['Supplier', '3', '2'],
      ['Supplier', '4', '3'],
      ['Supplier', '5', '4'],
      ['Supplier', '10', '5'],
      ['Supplier', '6', '6'],
      ['Contract', '4', '1'],
      ['Contract', '3', '2'],
      ['Contract', '5', '3'],
      ['Contract', '6', '4'],
      ['Contract', '7', '5'],
      ['Contract', '11', '6'],
      ['CartridgeItem', '23', '3'],
      ['CartridgeItem', '3', '4'],
      ['DocumentType', '6', '2'],
      ['DocumentType', '4', '3'],
      ['DocumentType', '5', '4'],
      ['Document', '3', '1'],
      ['Document', '4', '2'],
      ['Document', '7', '3'],
      ['Document', '5', '4'],
      ['Document', '16', '5'],
      ['User', '34', '1'],
      ['User', '5', '3'],
      ['User', '6', '4'],
      ['User', '3', '5'],
      ['ConsumableItem', '34', '1'],
      ['ConsumableItem', '4', '2'],
      ['ConsumableItem', '23', '3'],
      ['ConsumableItem', '3', '4'],
      ['NetworkEquipment', '40', '5'],
      ['Printer', '40', '5',],
      ['Monitor', '40', '5',],
      ['Peripheral', '40', '5'],
      ['User', '8', '6'],
      ['Phone', '31', '1'],
      ['Phone', '23', '2'],
      ['Phone', '3', '3'],
      ['Phone', '4', '4'],
      ['Phone', '40', '5'],
      ['Phone', '19', '6'],
      ['Phone', '7', '7'],
      ['Group', '16', '1'],
      ['AllAssets', '31', '1'],
      ['ReservationItem', '4', '1'],
      ['ReservationItem', '3', '2'],
      ['Budget', '3', '2'],
      ['Software', '72', '4'],
      ['Software', '163', '5'],
      ['Budget', '5', '1'],
      ['Budget', '4', '3'],
      ['Budget', '19', '4'],
      ['CronTask', '8', '1'],
      ['CronTask', '3', '2'],
      ['CronTask', '4', '3'],
      ['CronTask', '7', '4'],
      ['RequestType', '14', '1'],
      ['RequestType', '15', '2'],
      ['NotificationTemplate', '4', '1'],
      ['NotificationTemplate', '16', '2'],
      ['Notification', '5', '1'],
      ['Notification', '6', '2'],
      ['Notification', '2', '3'],
      ['Notification', '4', '4'],
      ['Notification', '80', '5'],
      ['Notification', '86', '6'],
      ['MailCollector', '2', '1'],
      ['MailCollector', '19', '2'],
      ['AuthLDAP', '3', '1'],
      ['AuthLDAP', '19', '2'],
      ['AuthMail', '3', '1'],
      ['AuthMail', '19', '2'],
      ['IPNetwork', '18', '1'],
      ['WifiNetwork', '10', '1'],
      ['Profile', '2', '1'],
      ['Profile', '3', '2'],
      ['Profile', '19', '3'],
      ['Transfer', '19', '1'],
      ['TicketValidation', '3', '1'],
      ['TicketValidation', '2', '2'],
      ['TicketValidation', '8', '3'],
      ['TicketValidation', '4', '4'],
      ['TicketValidation', '9', '5'],
      ['TicketValidation', '7', '6'],
      ['NotImportedEmail', '2', '1'],
      ['NotImportedEmail', '5', '2'],
      ['NotImportedEmail', '4', '3'],
      ['NotImportedEmail', '6', '4'],
      ['NotImportedEmail', '16', '5'],
      ['NotImportedEmail', '19', '6'],
      ['RuleRightParameter', '11', '1'],
      ['Ticket', '12', '1'],
      ['Ticket', '19', '2'],
      ['Ticket', '15', '3'],
      ['Ticket', '3', '4'],
      ['Ticket', '4', '5'],
      ['Ticket', '5', '6'],
      ['Ticket', '7', '7'],
      ['Calendar', '19', '1'],
      ['Holiday', '11', '1'],
      ['Holiday', '12', '2'],
      ['Holiday', '13', '3'],
      ['SLA', '4', '1'],
      ['Ticket', '18', '8'],
      ['AuthLdap', '30', '3'],
      ['AuthMail', '6', '3'],
      ['FQDN', '11', '1'],
      ['FieldUnicity', '1', '1'],
      ['FieldUnicity', '80', '2'],
      ['FieldUnicity', '4', '3'],
      ['FieldUnicity', '3', '4'],
      ['FieldUnicity', '86', '5'],
      ['FieldUnicity', '30', '6'],
      ['Problem', '21', '1'],
      ['Problem', '12', '2'],
      ['Problem', '19', '3'],
      ['Problem', '15', '4'],
      ['Problem', '3', '5'],
      ['Problem', '7', '6'],
      ['Problem', '18', '7'],
      ['Vlan', '11', '1'],
      ['TicketRecurrent', '11', '1'],
      ['TicketRecurrent', '12', '2'],
      ['TicketRecurrent', '13', '3'],
      ['TicketRecurrent', '15', '4'],
      ['TicketRecurrent', '14', '5'],
      ['Reminder', '2', '1'],
      ['Reminder', '3', '2'],
      ['Reminder', '4', '3'],
      ['Reminder', '5', '4'],
      ['Reminder', '6', '5'],
      ['Reminder', '7', '6'],
      ['IPNetwork', '10', '2'],
      ['IPNetwork', '11', '3'],
      ['IPNetwork', '12', '4'],
      ['IPNetwork', '17', '5'],
      ['NetworkName', '12', '1'],
      ['NetworkName', '13', '2'],
      ['RSSFeed', '2', '1'],
      ['RSSFeed', '4', '2'],
      ['RSSFeed', '5', '3'],
      ['RSSFeed', '19', '4'],
      ['RSSFeed', '6', '5'],
      ['RSSFeed', '7', '6'],
      ['Blacklist', '12', '1'],
      ['Blacklist', '11', '2'],
      ['ReservationItem', '5', '3'],
      ['QueueMail', '16', '1'],
      ['QueueMail', '7', '2'],
      ['QueueMail', '20', '3'],
      ['QueueMail', '21', '4'],
      ['QueueMail', '22', '5'],
      ['QueueMail', '15', '6'],
      ['Change', '12', '1'],
      ['Change', '19', '2'],
      ['Change', '15', '3'],
      ['Change', '7', '4'],
      ['Change', '18', '5'],
      ['Project', '3', '1'],
      ['Project', '4', '2'],
      ['Project', '12', '3'],
      ['Project', '5', '4'],
      ['Project', '15', '5'],
      ['Project', '21', '6'],
      ['ProjectState', '12', '1'],
      ['ProjectState', '11', '2'],
      ['ProjectTask', '2', '1'],
      ['ProjectTask', '12', '2'],
      ['ProjectTask', '14', '3'],
      ['ProjectTask', '5', '4'],
      ['ProjectTask', '7', '5'],
      ['ProjectTask', '8', '6'],
      ['ProjectTask', '13', '7'],
      ['CartridgeItem', '9', '5'],
      ['ConsumableItem', '9', '5'],
      ['ReservationItem', '9', '4'],
      ['SoftwareLicense', '1', '1'],
      ['SoftwareLicense', '3', '2'],
      ['SoftwareLicense', '10', '3'],
      ['SoftwareLicense', '162', '4'],
      ['SoftwareLicense', '5', '5'],
      ['SavedSearch', '8', '1'],
      ['SavedSearch', '9', '1'],
      ['SavedSearch', '3', '1'],
      ['SavedSearch', '10', '1'],
      ['SavedSearch', '11', '1'],
      ['Plugin', '2', '1'],
      ['Plugin', '3', '2'],
      ['Plugin', '4', '3'],
      ['Plugin', '5', '4'],
      ['Plugin', '6', '5'],
      ['Plugin', '7', '6'],
      ['Plugin', '8', '7'],
   ]
];

$tables['glpi_documenttypes'] = [
   'COLUMNS'   => ['id', 'name', 'ext', 'icon'],
   'VALUES'    => [
      [1, 'JPEG', 'jpg', 'jpg-dist.png'],
      [2, 'PNG', 'png', 'png-dist.png'],
      [3, 'GIF', 'gif', 'gif-dist.png'],
      ['4', 'BMP', 'bmp', 'bmp-dist.png'],
      ['5', 'Photoshop', 'psd', 'psd-dist.png'],
      ['6', 'TIFF', 'tif', 'tif-dist.png'],
      ['7', 'AIFF', 'aiff', 'aiff-dist.png'],
      ['8', 'Windows Media', 'asf', 'asf-dist.png'],
      ['9', 'Windows Media', 'avi', 'avi-dist.png'],
      ['44', 'C source', 'c', 'c-dist.png'],
      ['27', 'RealAudio', 'rm', 'rm-dist.png'],
      ['16', 'Midi', 'mid', 'mid-dist.png'],
      ['17', 'QuickTime', 'mov', 'mov-dist.png'],
      ['18', 'MP3', 'mp3', 'mp3-dist.png'],
      ['19', 'MPEG', 'mpg', 'mpg-dist.png'],
      ['20', 'Ogg Vorbis', 'ogg', 'ogg-dist.png'],
      ['24', 'QuickTime', 'qt', 'qt-dist.png'],
      ['10', 'BZip', 'bz2', 'bz2-dist.png'],
      ['25', 'RealAudio', 'ra', 'ra-dist.png'],
      ['26', 'RealAudio', 'ram', 'ram-dist.png'],
      ['11', 'Word', 'doc', 'doc-dist.png'],
      ['12', 'DjVu', 'djvu', ''],
      ['42', 'MNG', 'mng', ''],
      ['13', 'PostScript', 'eps', 'ps-dist.png'],
      ['14', 'GZ', 'gz', 'gz-dist.png'],
      ['37', 'WAV', 'wav', 'wav-dist.png'],
      ['15', 'HTML', 'html', 'html-dist.png'],
      ['34', 'Flash', 'swf', 'swf-dist.png'],
      ['21', 'PDF', 'pdf', 'pdf-dist.png'],
      ['22', 'PowerPoint', 'ppt', 'ppt-dist.png'],
      ['23', 'PostScript', 'ps', 'ps-dist.png'],
      ['40', 'Windows Media', 'wmv', 'wmv-dist.png'],
      ['28', 'RTF', 'rtf', 'rtf-dist.png'],
      ['29', 'StarOffice', 'sdd', 'sdd-dist.png'],
      ['30', 'StarOffice', 'sdw', 'sdw-dist.png'],
      ['31', 'Stuffit', 'sit', 'sit-dist.png'],
      ['43', 'Adobe Illustrator', 'ai', 'ai-dist.png'],
      ['32', 'OpenOffice Impress', 'sxi', 'sxi-dist.png'],
      ['33', 'OpenOffice', 'sxw', 'sxw-dist.png'],
      ['46', 'DVI', 'dvi', 'dvi-dist.png'],
      ['35', 'TGZ', 'tgz', 'tgz-dist.png'],
      ['36', 'texte', 'txt', 'txt-dist.png'],
      ['49', 'RedHat/Mandrake/SuSE', 'rpm', 'rpm-dist.png'],
      ['38', 'Excel', 'xls', 'xls-dist.png'],
      ['39', 'XML', 'xml', 'xml-dist.png'],
      ['41', 'Zip', 'zip', 'zip-dist.png'],
      ['45', 'Debian', 'deb', 'deb-dist.png'],
      ['47', 'C header', 'h', 'h-dist.png'],
      ['48', 'Pascal', 'pas', 'pas-dist.png'],
      ['50', 'OpenOffice Calc', 'sxc', 'sxc-dist.png'],
      ['51', 'LaTeX', 'tex', 'tex-dist.png'],
      ['52', 'GIMP multi-layer', 'xcf', 'xcf-dist.png'],
      ['53', 'JPEG', 'jpeg', 'jpg-dist.png'],
      ['54', 'Oasis Open Office Writer', 'odt', 'odt-dist.png'],
      ['55', 'Oasis Open Office Calc', 'ods', 'ods-dist.png'],
      ['56', 'Oasis Open Office Impress', 'odp', 'odp-dist.png'],
      ['57', 'Oasis Open Office Impress Template', 'otp', 'odp-dist.png'],
      ['58', 'Oasis Open Office Writer Template', 'ott', 'odt-dist.png'],
      ['59', 'Oasis Open Office Calc Template', 'ots', 'ods-dist.png'],
      ['60', 'Oasis Open Office Math', 'odf', 'odf-dist.png'],
      ['61', 'Oasis Open Office Draw', 'odg', 'odg-dist.png'],
      ['62', 'Oasis Open Office Draw Template', 'otg', 'odg-dist.png'],
      ['63', 'Oasis Open Office Base', 'odb', 'odb-dist.png'],
      ['64', 'Oasis Open Office HTML', 'oth', 'oth-dist.png'],
      ['65', 'Oasis Open Office Writer Master', 'odm', 'odm-dist.png'],
      ['66', 'Oasis Open Office Chart', 'odc', ''],
      ['67', 'Oasis Open Office Image', 'odi', ''],
      ['68', 'Word XML', 'docx', 'doc-dist.png'],
      ['69', 'Excel XML', 'xlsx', 'xls-dist.png'],
      ['70', 'PowerPoint XML', 'pptx', 'ppt-dist.png'],
      ['71', 'Comma-Separated Values', 'csv', 'csv-dist.png'],
      ['72', 'Scalable Vector Graphics', 'svg', 'svg-dist.png'],
   ]
];

$tables['glpi_entities'] = [
   [
      'id'                                   => 0,
      'name'                                 => __('Root entity'),
      'entities_id'                          => -1,
      'completename'                         => __('Root entity'),
      'comment'                              => null,
      'level'                                => 1,
      'cartridges_alert_repeat'              => 0,
      'consumables_alert_repeat'             => 0,
      'use_licenses_alert'                   => 0,
      'send_licenses_alert_before_delay'     => 0,
      'use_certificates_alert'               => 0,
      'send_certificates_alert_before_delay' => 0,
      'use_contracts_alert'                  => 0,
      'send_contracts_alert_before_delay'    => 0,
      'use_infocoms_alert'                   => 0,
      'send_infocoms_alert_before_delay'     => 0,
      'use_reservations_alert'               => 0,
      'autoclose_delay'                      => -10,
      'notclosed_delay'                      => 0,
      'calendars_id'                         => 0,
      'auto_assign_mode'                     => -10,
      'tickettype'                           => 1,
      'inquest_config'                       => 1,
      'inquest_rate'                         => 0,
      'inquest_delay'                        => 0,
      'autofill_warranty_date'               => 0,
      'autofill_use_date'                    => 0,
      'autofill_buy_date'                    => 0,
      'autofill_delivery_date'               => 0,
      'autofill_order_date'                  => 0,
      'tickettemplates_id'                   => 1,
      'entities_id_software'                 => -10,
      'default_contract_alert'               => 0,
      'default_infocom_alert'                => 0,
      'default_cartridges_alarm_threshold'   => 10,
      'default_consumables_alarm_threshold'  => 10,
      'delay_send_emails'                    => 0,
      'is_notif_enable_default'              => 1,
      'autofill_decommission_date'           => 0,
      'suppliers_as_private'                 => 0,
      'enable_custom_css'                    => 0,
   ],
];

$tables['glpi_filesystems'] = [
   'COLUMNS'   => ['name'],
   'VALUES'    => [
      ['ext'],
      ['ext2'],
      ['ext3'],
      ['ext4'],
      ['FAT'],
      ['FAT32'],
      ['VFAT'],
      ['HFS'],
      ['HPFS'],
      ['HTFS'],
      ['JFS'],
      ['JFS2'],
      ['NFS'],
      ['NTFS'],
      ['ReiserFS'],
      ['SMBFS'],
      ['UDF'],
      ['UFS'],
      ['XFS'],
      ['ZFS'],
      ['APFS'],
   ]
];

$tables['glpi_interfacetypes'] = [
   'COLUMNS'   => ['name'],
   'VALUES'    => [
      ['IDE'],
      ['SATA'],
      ['SCSI'],
      ['USB'],
      ['AGP'],
      ['PCI'],
      ['PCIe'],
      ['PCI-X'],
   ]
];

$tables['glpi_notifications'] = [
   'COLUMNS'   => ['name', 'itemtype', 'event', 'is_recursive', 'is_active'],
   'VALUES'    => [
      ['Alert Tickets not closed', 'Ticket', 'alertnotclosed', 1, 1],
      ['New Ticket', 'Ticket', 'new', 1, 1],
      ['Update Ticket', 'Ticket', 'update', 1, 0],
      ['Close Ticket', 'Ticket', 'closed', 1, 1],
      ['Add Followup', 'Ticket', 'add_followup', 1, 1],
      ['Add Task', 'Ticket', 'add_task', 1, 1],
      ['Update Followup', 'Ticket', 'update_followup', 1, 1],
      ['Update Task', 'Ticket', 'update_task', 1, 1],
      ['Delete Followup', 'Ticket', 'delete_followup', 1, 1],
      ['Delete Task', 'Ticket', 'delete_task', 1, 1],
      ['Resolve ticket', 'Ticket', 'solved', 1, 1],
      ['Ticket Validation', 'Ticket', 'validation', 1, 1],
      ['New Reservation', 'Reservation', 'new', 1, 1],
      ['Update Reservation', 'Reservation', 'update', 1, 1],
      ['Delete Reservation', 'Reservation', 'delete', 1, 1],
      ['Alert Reservation', 'Reservation', 'alert', 1, 1],
      ['Contract Notice', 'Contract', 'notice', 1, 1],
      ['Contract End', 'Contract', 'end', 1, 1],
      ['MySQL Synchronization', 'DBConnection', 'desynchronization', 1, 1],
      ['Cartridges', 'CartridgeItem', 'alert', 1, 1],
      ['Consumables', 'ConsumableItem', 'alert', 1, 1],
      ['Infocoms', 'Infocom', 'alert', 1, 1],
      ['Software Licenses', 'SoftwareLicense', 'alert', 1, 1],
      ['Ticket Recall', 'Ticket', 'recall', 1, 1],
      ['Password Forget', 'User', 'passwordforget', 1, 1],
      ['Ticket Satisfaction', 'Ticket', 'satisfaction', 1, 1],
      ['Item not unique', 'FieldUnicity', 'refuse', 1, 1],
      ['Crontask Watcher', 'CronTask', 'alert', 1, 1],
      ['New Problem', 'Problem', 'new', 1, 1],
      ['Update Problem', 'Problem', 'update', 1, 1],
      ['Resolve Problem', 'Problem', 'solved', 1, 1],
      ['Add Task', 'Problem', 'add_task', 1, 1],
      ['Update Task', 'Problem', 'update_task', 1, 1],
      ['Delete Task', 'Problem', 'delete_task', 1, 1],
      ['Close Problem', 'Problem', 'closed', 1, 1],
      ['Delete Problem', 'Problem', 'delete', 1, 1],
      ['Ticket Validation Answer', 'Ticket', 'validation_answer', 1, 1],
      ['Contract End Periodicity', 'Contract', 'periodicity', 1, 1],
      ['Contract Notice Periodicity', 'Contract', 'periodicitynotice', 1, 1],
      ['Planning recall', 'PlanningRecall', 'planningrecall', 1, 1],
      ['Delete Ticket', 'Ticket', 'delete', 1, 1],
      ['New Change', 'Change', 'new', 1, 1],
      ['Update Change', 'Change', 'update', 1, 1],
      ['Resolve Change', 'Change', 'solved', 1, 1],
      ['Add Task', 'Change', 'add_task', 1, 1],
      ['Update Task', 'Change', 'update_task', 1, 1],
      ['Delete Task', 'Change', 'delete_task', 1, 1],
      ['Close Change', 'Change', 'closed', 1, 1],
      ['Delete Change', 'Change', 'delete', 1, 1],
      ['Ticket Satisfaction Answer', 'Ticket', 'replysatisfaction', 1, 1],
      ['Receiver errors', 'MailCollector', 'error', 1, 1],
      ['New Project', 'Project', 'new', 1, 1],
      ['Update Project', 'Project', 'update', 1, 1],
      ['Delete Project', 'Project', 'delete', 1, 1],
      ['New Project Task', 'ProjectTask', 'new', 1, 1],
      ['Update Project Task', 'ProjectTask', 'update', 1, 1],
      ['Delete Project Task', 'ProjectTask', 'delete', 1, 1],
      ['Request Unlock Items', 'ObjectLock', 'unlock', 1, 1],
      ['New user in requesters', 'Ticket', 'requester_user', 1, 1],
      ['New group in requesters', 'Ticket', 'requester_group', 1, 1],
      ['New user in observers', 'Ticket', 'observer_user', 1, 1],
      ['New group in observers', 'Ticket', 'observer_group', 1, 1],
      ['New user in assignees', 'Ticket', 'assign_user', 1, 1],
      ['New group in assignees', 'Ticket', 'assign_group', 1, 1],
      ['New supplier in assignees', 'Ticket', 'assign_supplier', 1, 1],
      ['Saved searches', 'SavedSearch_Alert', 'alert', 1, 1],
      ['Certificates', 'Certificate', 'alert', 1, 1],
   ]
];

$tables['glpi_notifications_notificationtemplates'] = [
   'COLUMNS'   => ['notifications_id', 'mode', 'notificationtemplates_id'],
   'VALUES'    => [
      ['1', 'mailing', 6],
      ['2', 'mailing', 4],
      ['3', 'mailing', 4],
      ['4', 'mailing', 4],
      ['5', 'mailing', 4],
      ['6', 'mailing', 4],
      ['7', 'mailing', 4],
      ['8', 'mailing', 4],
      ['9', 'mailing', 4],
      ['10', 'mailing', 4],
      ['11', 'mailing', 4],
      ['12', 'mailing', 7],
      ['13', 'mailing', 2],
      ['14', 'mailing', 2],
      ['15', 'mailing', 2],
      ['16', 'mailing', 3],
      ['17', 'mailing', 12],
      ['18', 'mailing', 12],
      ['19', 'mailing', 1],
      ['20', 'mailing', 8],
      ['21', 'mailing', 9],
      ['22', 'mailing', 10],
      ['23', 'mailing', 11],
      ['24', 'mailing', 4],
      ['25', 'mailing', 13],
      ['26', 'mailing', 14],
      ['27', 'mailing', 15],
      ['28', 'mailing', 16],
      ['29', 'mailing', 17],
      ['30', 'mailing', 17],
      ['31', 'mailing', 17],
      ['32', 'mailing', 17],
      ['33', 'mailing', 17],
      ['34', 'mailing', 17],
      ['35', 'mailing', 17],
      ['36', 'mailing', 17],
      ['37', 'mailing', 7],
      ['38', 'mailing', 12],
      ['39', 'mailing', 12],
      ['40', 'mailing', 18],
      ['41', 'mailing', 4],
      ['42', 'mailing', 19],
      ['43', 'mailing', 19],
      ['44', 'mailing', 19],
      ['45', 'mailing', 19],
      ['46', 'mailing', 19],
      ['47', 'mailing', 19],
      ['48', 'mailing', 19],
      ['49', 'mailing', 19],
      ['50', 'mailing', 14],
      ['51', 'mailing', 20],
      ['52', 'mailing', 21],
      ['53', 'mailing', 21],
      ['54', 'mailing', 21],
      ['55', 'mailing', 22],
      ['56', 'mailing', 22],
      ['57', 'mailing', 22],
      ['58', 'mailing', 23],
      ['59', 'mailing', 4],
      ['60', 'mailing', 4],
      ['61', 'mailing', 4],
      ['62', 'mailing', 4],
      ['63', 'mailing', 4],
      ['64', 'mailing', 4],
      ['65', 'mailing', 4],
      ['66', 'mailing', 24],
      ['67', 'mailing', 25],
   ]
];

$tables['glpi_notificationtargets'] = [
   'COLUMNS'   => ['items_id', 'type', 'notifications_id'],
   'VALUES'    => [
      ['3', '1', '13'],
      ['1', '1', '13'],
      ['3', '2', '2'],
      ['1', '1', '2'],
      ['1', '1', '3'],
      ['1', '1', '5'],
      ['1', '1', '4'],
      ['2', '1', '3'],
      ['4', '1', '3'],
      ['3', '1', '2'],
      ['3', '1', '3'],
      ['3', '1', '5'],
      ['3', '1', '4'],
      ['1', '1', '19'],
      ['14', '1', '12'],
      ['3', '1', '14'],
      ['1', '1', '14'],
      ['3', '1', '15'],
      ['1', '1', '15'],
      ['1', '1', '6'],
      ['3', '1', '6'],
      ['1', '1', '7'],
      ['3', '1', '7'],
      ['1', '1', '8'],
      ['3', '1', '8'],
      ['1', '1', '9'],
      ['3', '1', '9'],
      ['1', '1', '10'],
      ['3', '1', '10'],
      ['1', '1', '11'],
      ['3', '1', '11'],
      ['19', '1', '25'],
      ['3', '1', '26'],
      ['21', '1', '2'],
      ['21', '1', '3'],
      ['21', '1', '5'],
      ['21', '1', '4'],
      ['21', '1', '6'],
      ['21', '1', '7'],
      ['21', '1', '8'],
      ['21', '1', '9'],
      ['21', '1', '10'],
      ['21', '1', '11'],
      ['1', '1', '41'],
      ['1', '1', '28'],
      ['3', '1', '29'],
      ['1', '1', '29'],
      ['21', '1', '29'],
      ['2', '1', '30'],
      ['4', '1', '30'],
      ['3', '1', '30'],
      ['1', '1', '30'],
      ['21', '1', '30'],
      ['3', '1', '31'],
      ['1', '1', '31'],
      ['21', '1', '31'],
      ['3', '1', '32'],
      ['1', '1', '32'],
      ['21', '1', '32'],
      ['3', '1', '33'],
      ['1', '1', '33'],
      ['21', '1', '33'],
      ['3', '1', '34'],
      ['1', '1', '34'],
      ['21', '1', '34'],
      ['3', '1', '35'],
      ['1', '1', '35'],
      ['21', '1', '35'],
      ['3', '1', '36'],
      ['1', '1', '36'],
      ['21', '1', '36'],
      ['14', '1', '37'],
      ['3', '1', '40'],
      ['3', '1', '42'],
      ['1', '1', '42'],
      ['21', '1', '42'],
      ['2', '1', '43'],
      ['4', '1', '43'],
      ['3', '1', '43'],
      ['1', '1', '43'],
      ['21', '1', '43'],
      ['3', '1', '44'],
      ['1', '1', '44'],
      ['21', '1', '44'],
      ['3', '1', '45'],
      ['1', '1', '45'],
      ['21', '1', '45'],
      ['3', '1', '46'],
      ['1', '1', '46'],
      ['21', '1', '46'],
      ['3', '1', '47'],
      ['1', '1', '47'],
      ['21', '1', '47'],
      ['3', '1', '48'],
      ['1', '1', '48'],
      ['21', '1', '48'],
      ['3', '1', '49'],
      ['1', '1', '49'],
      ['21', '1', '49'],
      ['3', '1', '50'],
      ['2', '1', '50'],
      ['1', '1', '51'],
      ['27', '1', '52'],
      ['1', '1', '52'],
      ['28', '1', '52'],
      ['27', '1', '53'],
      ['1', '1', '53'],
      ['28', '1', '53'],
      ['27', '1', '54'],
      ['1', '1', '54'],
      ['28', '1', '54'],
      ['31', '1', '55'],
      ['1', '1', '55'],
      ['32', '1', '55'],
      ['31', '1', '56'],
      ['1', '1', '56'],
      ['32', '1', '56'],
      ['31', '1', '57'],
      ['1', '1', '57'],
      ['32', '1', '57'],
      ['19', '1', '58'],
      ['3', '1', '59'],
      ['13', '1', '60'],
      ['21', '1', '61'],
      ['20', '1', '62'],
      ['2', '1', '63'],
      ['23', '1', '64'],
      ['8', '1', '65'],
      ['19', '1', '66'],
   ]
];

$tables['glpi_notificationtemplates'] = [
   'COLUMNS'   => ['name', 'itemtype'],
   'VALUES'    => [
      ['MySQL Synchronization', 'DBConnection'],
      ['Reservations', 'Reservation'],
      ['Alert Reservation', 'Reservation'],
      ['Tickets', 'Ticket'],
      ['Tickets (Simple)', 'Ticket'],
      ['Alert Tickets not closed', 'Ticket'],
      ['Tickets Validation', 'Ticket'],
      ['Cartridges', 'CartridgeItem'],
      ['Consumables', 'ConsumableItem'],
      ['Infocoms', 'Infocom'],
      ['Licenses', 'SoftwareLicense'],
      ['Contracts', 'Contract'],
      ['Password Forget', 'User'],
      ['Ticket Satisfaction', 'Ticket'],
      ['Item not unique', 'FieldUnicity'],
      ['CronTask', 'CronTask'],
      ['Problems', 'Problem'],
      ['Planning recall', 'PlanningRecall'],
      ['Changes', 'Change'],
      ['Receiver errors', 'MailCollector'],
      ['Projects', 'Project'],
      ['Project Tasks', 'ProjectTask'],
      ['Unlock Item request', 'ObjectLock'],
      ['Saved searches alerts', 'SavedSearch_Alert'],
      ['Certificates', 'Certificate'],
   ]
];

$tables['glpi_notificationtemplatetranslations'] = [
   [
      'id'                       => '1',
      'notificationtemplates_id' => '1',
      'language'                 => '',
      'subject'                  => '##lang.dbconnection.title##',
      'content_text'             => '##lang.dbconnection.delay## : ##dbconnection.delay##',
      'content_html'             => '&lt;p&gt;##lang.dbconnection.delay## : ##dbconnection.delay##&lt;/p&gt;',
   ], [
      'id'                       => '2',
      'notificationtemplates_id' => '2',
      'language'                 => '',
      'subject'                  => '##reservation.action##',
      'content_text'             => '======================================================================
##lang.reservation.user##: ##reservation.user##
##lang.reservation.item.name##: ##reservation.itemtype## - ##reservation.item.name##
##IFreservation.tech## ##lang.reservation.tech## ##reservation.tech## ##ENDIFreservation.tech##
##lang.reservation.begin##: ##reservation.begin##
##lang.reservation.end##: ##reservation.end##
##lang.reservation.comment##: ##reservation.comment##
======================================================================',
      'content_html'             => '&lt;!-- description{ color: inherit; background: #ebebeb;border-style: solid;border-color: #8d8d8d; border-width: 0px 1px 1px 0px; } --&gt;
&lt;p&gt;&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.reservation.user##:&lt;/span&gt;##reservation.user##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.reservation.item.name##:&lt;/span&gt;##reservation.itemtype## - ##reservation.item.name##&lt;br /&gt;##IFreservation.tech## ##lang.reservation.tech## ##reservation.tech####ENDIFreservation.tech##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.reservation.begin##:&lt;/span&gt; ##reservation.begin##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.reservation.end##:&lt;/span&gt;##reservation.end##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.reservation.comment##:&lt;/span&gt; ##reservation.comment##&lt;/p&gt;',
   ], [
      'id'                       => '3',
      'notificationtemplates_id' => '3',
      'language'                 => '',
      'subject'                  => '##reservation.action##  ##reservation.entity##',
      'content_text'             => '##lang.reservation.entity## : ##reservation.entity##


##FOREACHreservations##
##lang.reservation.itemtype## : ##reservation.itemtype##

 ##lang.reservation.item## : ##reservation.item##

 ##reservation.url##

 ##ENDFOREACHreservations##',
      'content_html'             => '&lt;p&gt;##lang.reservation.entity## : ##reservation.entity## &lt;br /&gt; &lt;br /&gt;
##FOREACHreservations## &lt;br /&gt;##lang.reservation.itemtype## :  ##reservation.itemtype##&lt;br /&gt;
 ##lang.reservation.item## :  ##reservation.item##&lt;br /&gt; &lt;br /&gt;
 &lt;a href="##reservation.url##"&gt; ##reservation.url##&lt;/a&gt;&lt;br /&gt;
 ##ENDFOREACHreservations##&lt;/p&gt;',
   ], [
      'id'                       => '4',
      'notificationtemplates_id' => '4',
      'language'                 => '',
      'subject'                  => '##ticket.action## ##ticket.title##',
      'content_text'             => ' ##IFticket.storestatus=5##
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
 ##lang.ticket.numberoffollowups## : ##ticket.numberoffollowups##

##FOREACHfollowups##

 [##followup.date##] ##lang.followup.isprivate## : ##followup.isprivate##
 ##lang.followup.author## ##followup.author##
 ##lang.followup.description## ##followup.description##
 ##lang.followup.date## ##followup.date##
 ##lang.followup.requesttype## ##followup.requesttype##

##ENDFOREACHfollowups##
 ##lang.ticket.numberoftasks## : ##ticket.numberoftasks##

##FOREACHtasks##

 [##task.date##] ##lang.task.isprivate## : ##task.isprivate##
 ##lang.task.author## ##task.author##
 ##lang.task.description## ##task.description##
 ##lang.task.time## ##task.time##
 ##lang.task.category## ##task.category##

##ENDFOREACHtasks##',
      'content_html'             => '<!-- description{ color: inherit; background: #ebebeb; border-style: solid;border-color: #8d8d8d; border-width: 0px 1px 1px 0px; }    -->
<div>##IFticket.storestatus=5##</div>
<div>##lang.ticket.url## : <a href="##ticket.urlapprove##">##ticket.urlapprove##</a> <strong>&#160;</strong></div>
<div><strong>##lang.ticket.autoclosewarning##</strong></div>
<div><span style="color: #888888;"><strong><span style="text-decoration: underline;">##lang.ticket.solvedate##</span></strong></span> : ##ticket.solvedate##<br /><span style="text-decoration: underline; color: #888888;"><strong>##lang.ticket.solution.type##</strong></span> : ##ticket.solution.type##<br /><span style="text-decoration: underline; color: #888888;"><strong>##lang.ticket.solution.description##</strong></span> : ##ticket.solution.description## ##ENDIFticket.storestatus##</div>
<div>##ELSEticket.storestatus## ##lang.ticket.url## : <a href="##ticket.url##">##ticket.url##</a> ##ENDELSEticket.storestatus##</div>
<p class="description b"><strong>##lang.ticket.description##</strong></p>
<p><span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.ticket.title##</span>&#160;:##ticket.title## <br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.ticket.authors##</span>&#160;:##IFticket.authors## ##ticket.authors## ##ENDIFticket.authors##    ##ELSEticket.authors##--##ENDELSEticket.authors## <br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.ticket.creationdate##</span>&#160;:##ticket.creationdate## <br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.ticket.closedate##</span>&#160;:##ticket.closedate## <br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.ticket.requesttype##</span>&#160;:##ticket.requesttype##<br />
<br /><span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.ticket.item.name##</span>&#160;:
<p>##FOREACHitems##</p>
<div class="description b">##IFticket.itemtype## ##ticket.itemtype##&#160;- ##ticket.item.name## ##IFticket.item.model## ##lang.ticket.item.model## : ##ticket.item.model## ##ENDIFticket.item.model## ##IFticket.item.serial## ##lang.ticket.item.serial## : ##ticket.item.serial## ##ENDIFticket.item.serial## ##IFticket.item.otherserial## ##lang.ticket.item.otherserial## : ##ticket.item.otherserial## ##ENDIFticket.item.otherserial## ##ENDIFticket.itemtype## </div><br />
<p>##ENDFOREACHitems##</p>
##IFticket.assigntousers## <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.ticket.assigntousers##</span>&#160;: ##ticket.assigntousers## ##ENDIFticket.assigntousers##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;">##lang.ticket.status## </span>&#160;: ##ticket.status##<br /> ##IFticket.assigntogroups## <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.ticket.assigntogroups##</span>&#160;: ##ticket.assigntogroups## ##ENDIFticket.assigntogroups##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.ticket.urgency##</span>&#160;: ##ticket.urgency##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.ticket.impact##</span>&#160;: ##ticket.impact##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.ticket.priority##</span>&#160;: ##ticket.priority## <br /> ##IFticket.user.email##<span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.ticket.user.email##</span>&#160;: ##ticket.user.email ##ENDIFticket.user.email##    <br /> ##IFticket.category##<span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;">##lang.ticket.category## </span>&#160;:##ticket.category## ##ENDIFticket.category## ##ELSEticket.category## ##lang.ticket.nocategoryassigned## ##ENDELSEticket.category##    <br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.ticket.content##</span>&#160;: ##ticket.content##</p>
<br />##IFticket.storestatus=6##<br /><span style="text-decoration: underline;"><strong><span style="color: #888888;">##lang.ticket.solvedate##</span></strong></span> : ##ticket.solvedate##<br /><span style="color: #888888;"><strong><span style="text-decoration: underline;">##lang.ticket.solution.type##</span></strong></span> : ##ticket.solution.type##<br /><span style="text-decoration: underline; color: #888888;"><strong>##lang.ticket.solution.description##</strong></span> : ##ticket.solution.description##<br />##ENDIFticket.storestatus##</p>
<div class="description b">##lang.ticket.numberoffollowups##&#160;: ##ticket.numberoffollowups##</div>
<p>##FOREACHfollowups##</p>
<div class="description b"><br /> <strong> [##followup.date##] <em>##lang.followup.isprivate## : ##followup.isprivate## </em></strong><br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.followup.author## </span> ##followup.author##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.followup.description## </span> ##followup.description##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.followup.date## </span> ##followup.date##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.followup.requesttype## </span> ##followup.requesttype##</div>
<p>##ENDFOREACHfollowups##</p>
<div class="description b">##lang.ticket.numberoftasks##&#160;: ##ticket.numberoftasks##</div>
<p>##FOREACHtasks##</p>
<div class="description b"><br /> <strong> [##task.date##] <em>##lang.task.isprivate## : ##task.isprivate## </em></strong><br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.task.author##</span> ##task.author##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.task.description##</span> ##task.description##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.task.time##</span> ##task.time##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.task.category##</span> ##task.category##</div>
<p>##ENDFOREACHtasks##</p>',
   ], [
      'id'                       => '5',
      'notificationtemplates_id' => '12',
      'language'                 => '',
      'subject'                  => '##contract.action##  ##contract.entity##',
      'content_text'             => '##lang.contract.entity## : ##contract.entity##

##FOREACHcontracts##
##lang.contract.name## : ##contract.name##
##lang.contract.number## : ##contract.number##
##lang.contract.time## : ##contract.time##
##IFcontract.type####lang.contract.type## : ##contract.type####ENDIFcontract.type##
##contract.url##
##ENDFOREACHcontracts##',
      'content_html'             => '&lt;p&gt;##lang.contract.entity## : ##contract.entity##&lt;br /&gt;
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
      'id'                       => '6',
      'notificationtemplates_id' => '5',
      'language'                 => '',
      'subject'                  => '##ticket.action## ##ticket.title##',
      'content_text'             => '##lang.ticket.url## : ##ticket.url##

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
      'content_html'             => '&lt;div&gt;##lang.ticket.url## : &lt;a href="##ticket.url##"&gt;
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
&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;&#160
;&lt;/span&gt;&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; &lt;/span&gt;
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
      'id'                       => '15',
      'notificationtemplates_id' => '15',
      'language'                 => '',
      'subject'                  => '##lang.unicity.action##',
      'content_text'             => '##lang.unicity.entity## : ##unicity.entity##

##lang.unicity.itemtype## : ##unicity.itemtype##

##lang.unicity.message## : ##unicity.message##

##lang.unicity.action_user## : ##unicity.action_user##

##lang.unicity.action_type## : ##unicity.action_type##

##lang.unicity.date## : ##unicity.date##',
      'content_html'             => '&lt;p&gt;##lang.unicity.entity## : ##unicity.entity##&lt;/p&gt;
&lt;p&gt;##lang.unicity.itemtype## : ##unicity.itemtype##&lt;/p&gt;
&lt;p&gt;##lang.unicity.message## : ##unicity.message##&lt;/p&gt;
&lt;p&gt;##lang.unicity.action_user## : ##unicity.action_user##&lt;/p&gt;
&lt;p&gt;##lang.unicity.action_type## : ##unicity.action_type##&lt;/p&gt;
&lt;p&gt;##lang.unicity.date## : ##unicity.date##&lt;/p&gt;',
   ], [
      'id'                       => '7',
      'notificationtemplates_id' => '7',
      'language'                 => '',
      'subject'                  => '##ticket.action## ##ticket.title##',
      'content_text'             => '##FOREACHvalidations##

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
      'content_html'             => '&lt;div&gt;##FOREACHvalidations##&lt;/div&gt;
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
      'id'                       => '8',
      'notificationtemplates_id' => '6',
      'language'                 => '',
      'subject'                  => '##ticket.action## ##ticket.entity##',
      'content_text'             => '##lang.ticket.entity## : ##ticket.entity##

##FOREACHtickets##

##lang.ticket.title## : ##ticket.title##
 ##lang.ticket.status## : ##ticket.status##

 ##ticket.url##
 ##ENDFOREACHtickets##',
      'content_html'             => '&lt;table class="tab_cadre" border="1" cellspacing="2" cellpadding="3"&gt;
&lt;tbody&gt;
&lt;tr&gt;
&lt;td style="text-align: left;" width="auto" bgcolor="#cccccc"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##lang.ticket.authors##&lt;/span&gt;&lt;/td&gt;
&lt;td style="text-align: left;" width="auto" bgcolor="#cccccc"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##lang.ticket.title##&lt;/span&gt;&lt;/td&gt;
&lt;td style="text-align: left;" width="auto" bgcolor="#cccccc"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##lang.ticket.priority##&lt;/span&gt;&lt;/td&gt;
&lt;td style="text-align: left;" width="auto" bgcolor="#cccccc"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##lang.ticket.status##&lt;/span&gt;&lt;/td&gt;
&lt;td style="text-align: left;" width="auto" bgcolor="#cccccc"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##lang.ticket.attribution##&lt;/span&gt;&lt;/td&gt;
&lt;td style="text-align: left;" width="auto" bgcolor="#cccccc"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##lang.ticket.creationdate##&lt;/span&gt;&lt;/td&gt;
&lt;td style="text-align: left;" width="auto" bgcolor="#cccccc"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##lang.ticket.content##&lt;/span&gt;&lt;/td&gt;
&lt;/tr&gt;
##FOREACHtickets##
&lt;tr&gt;
&lt;td width="auto"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##ticket.authors##&lt;/span&gt;&lt;/td&gt;
&lt;td width="auto"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;&lt;a href="##ticket.url##"&gt;##ticket.title##&lt;/a&gt;&lt;/span&gt;&lt;/td&gt;
&lt;td width="auto"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##ticket.priority##&lt;/span&gt;&lt;/td&gt;
&lt;td width="auto"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##ticket.status##&lt;/span&gt;&lt;/td&gt;
&lt;td width="auto"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##IFticket.assigntousers####ticket.assigntousers##&lt;br /&gt;##ENDIFticket.assigntousers####IFticket.assigntogroups##&lt;br /&gt;##ticket.assigntogroups## ##ENDIFticket.assigntogroups####IFticket.assigntosupplier##&lt;br /&gt;##ticket.assigntosupplier## ##ENDIFticket.assigntosupplier##&lt;/span&gt;&lt;/td&gt;
&lt;td width="auto"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##ticket.creationdate##&lt;/span&gt;&lt;/td&gt;
&lt;td width="auto"&gt;&lt;span style="font-size: 11px; text-align: left;"&gt;##ticket.content##&lt;/span&gt;&lt;/td&gt;
&lt;/tr&gt;
##ENDFOREACHtickets##
&lt;/tbody&gt;
&lt;/table&gt;',
   ], [
      'id'                       => '9',
      'notificationtemplates_id' => '9',
      'language'                 => '',
      'subject'                  => '##consumable.action##  ##consumable.entity##',
      'content_text'             => '##lang.consumable.entity## : ##consumable.entity##


##FOREACHconsumables##
##lang.consumable.item## : ##consumable.item##


##lang.consumable.reference## : ##consumable.reference##

##lang.consumable.remaining## : ##consumable.remaining##

##consumable.url##

##ENDFOREACHconsumables##',
      'content_html'             => '&lt;p&gt;
##lang.consumable.entity## : ##consumable.entity##
&lt;br /&gt; &lt;br /&gt;##FOREACHconsumables##
&lt;br /&gt;##lang.consumable.item## : ##consumable.item##&lt;br /&gt;
&lt;br /&gt;##lang.consumable.reference## : ##consumable.reference##&lt;br /&gt;
##lang.consumable.remaining## : ##consumable.remaining##&lt;br /&gt;
&lt;a href="##consumable.url##"&gt; ##consumable.url##&lt;/a&gt;&lt;br /&gt;
   ##ENDFOREACHconsumables##&lt;/p&gt;',
   ], [
      'id'                       => '10',
      'notificationtemplates_id' => '8',
      'language'                 => '',
      'subject'                  => '##cartridge.action##  ##cartridge.entity##',
      'content_text'             => '##lang.cartridge.entity## : ##cartridge.entity##


##FOREACHcartridges##
##lang.cartridge.item## : ##cartridge.item##


##lang.cartridge.reference## : ##cartridge.reference##

##lang.cartridge.remaining## : ##cartridge.remaining##

##cartridge.url##
 ##ENDFOREACHcartridges##',
      'content_html'             => '&lt;p&gt;##lang.cartridge.entity## : ##cartridge.entity##
&lt;br /&gt; &lt;br /&gt;##FOREACHcartridges##
&lt;br /&gt;##lang.cartridge.item## :
##cartridge.item##&lt;br /&gt; &lt;br /&gt;
##lang.cartridge.reference## :
##cartridge.reference##&lt;br /&gt;
##lang.cartridge.remaining## :
##cartridge.remaining##&lt;br /&gt;
&lt;a href="##cartridge.url##"&gt;
##cartridge.url##&lt;/a&gt;&lt;br /&gt;
##ENDFOREACHcartridges##&lt;/p&gt;',
   ], [
      'id'                       => '11',
      'notificationtemplates_id' => '10',
      'language'                 => '',
      'subject'                  => '##infocom.action##  ##infocom.entity##',
      'content_text'             => '##lang.infocom.entity## : ##infocom.entity##


##FOREACHinfocoms##

##lang.infocom.itemtype## : ##infocom.itemtype##

##lang.infocom.item## : ##infocom.item##


##lang.infocom.expirationdate## : ##infocom.expirationdate##

##infocom.url##
 ##ENDFOREACHinfocoms##',
      'content_html'             => '&lt;p&gt;##lang.infocom.entity## : ##infocom.entity##
&lt;br /&gt; &lt;br /&gt;##FOREACHinfocoms##
&lt;br /&gt;##lang.infocom.itemtype## : ##infocom.itemtype##&lt;br /&gt;
##lang.infocom.item## : ##infocom.item##&lt;br /&gt; &lt;br /&gt;
##lang.infocom.expirationdate## : ##infocom.expirationdate##
&lt;br /&gt; &lt;a href="##infocom.url##"&gt;
##infocom.url##&lt;/a&gt;&lt;br /&gt;
##ENDFOREACHinfocoms##&lt;/p&gt;',
   ], [
      'id'                       => '12',
      'notificationtemplates_id' => '11',
      'language'                 => '',
      'subject'                  => '##license.action##  ##license.entity##',
      'content_text'             => '##lang.license.entity## : ##license.entity##

##FOREACHlicenses##

##lang.license.item## : ##license.item##

##lang.license.serial## : ##license.serial##

##lang.license.expirationdate## : ##license.expirationdate##

##license.url##
 ##ENDFOREACHlicenses##',
      'content_html'             => '&lt;p&gt;
##lang.license.entity## : ##license.entity##&lt;br /&gt;
##FOREACHlicenses##
&lt;br /&gt;##lang.license.item## : ##license.item##&lt;br /&gt;
##lang.license.serial## : ##license.serial##&lt;br /&gt;
##lang.license.expirationdate## : ##license.expirationdate##
&lt;br /&gt; &lt;a href="##license.url##"&gt; ##license.url##
&lt;/a&gt;&lt;br /&gt; ##ENDFOREACHlicenses##&lt;/p&gt;',
   ], [
      'id'                       => '13',
      'notificationtemplates_id' => '13',
      'language'                 => '',
      'subject'                  => '##user.action##',
      'content_text'             => '##user.realname## ##user.firstname##

##lang.passwordforget.information##

##lang.passwordforget.link## ##user.passwordforgeturl##',
      'content_html'             => '&lt;p&gt;&lt;strong&gt;##user.realname## ##user.firstname##&lt;/strong&gt;&lt;/p&gt;
&lt;p&gt;##lang.passwordforget.information##&lt;/p&gt;
&lt;p&gt;##lang.passwordforget.link## &lt;a title="##user.passwordforgeturl##" href="##user.passwordforgeturl##"&gt;##user.passwordforgeturl##&lt;/a&gt;&lt;/p&gt;',
   ], [
      'id'                       => '14',
      'notificationtemplates_id' => '14',
      'language'                 => '',
      'subject'                  => '##ticket.action## ##ticket.title##',
      'content_text'             => '##lang.ticket.title## : ##ticket.title##

##lang.ticket.closedate## : ##ticket.closedate##

##lang.satisfaction.text## ##ticket.urlsatisfaction##',
      'content_html'             =>'&lt;p&gt;##lang.ticket.title## : ##ticket.title##&lt;/p&gt;
&lt;p&gt;##lang.ticket.closedate## : ##ticket.closedate##&lt;/p&gt;
&lt;p&gt;##lang.satisfaction.text## &lt;a href="##ticket.urlsatisfaction##"&gt;##ticket.urlsatisfaction##&lt;/a&gt;&lt;/p&gt;',
   ], [
      'id'                       => '16',
      'notificationtemplates_id' => '16',
      'language'                 => '',
      'subject'                  => '##crontask.action##',
      'content_text'             => '##lang.crontask.warning##

##FOREACHcrontasks##
 ##crontask.name## : ##crontask.description##

##ENDFOREACHcrontasks##',
      'content_html'             => '&lt;p&gt;##lang.crontask.warning##&lt;/p&gt;
&lt;p&gt;##FOREACHcrontasks## &lt;br /&gt;&lt;a href="##crontask.url##"&gt;##crontask.name##&lt;/a&gt; : ##crontask.description##&lt;br /&gt; &lt;br /&gt;##ENDFOREACHcrontasks##&lt;/p&gt;',
   ], [
      'id'                       => '17',
      'notificationtemplates_id' => '17',
      'language'                 => '',
      'subject'                  => '##problem.action## ##problem.title##',
      'content_text'             => '##IFproblem.storestatus=5##
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
      'content_html'             => '&lt;p&gt;##IFproblem.storestatus=5##&lt;/p&gt;
&lt;div&gt;##lang.problem.url## : &lt;a href="##problem.urlapprove##"&gt;##problem.urlapprove##&lt;/a&gt;&lt;/div&gt;
&lt;div&gt;&lt;span style="color: #888888;"&gt;&lt;strong&gt;&lt;span style="text-decoration: underline;"&gt;##lang.problem.solvedate##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##problem.solvedate##&lt;br /&gt;&lt;span style="text-decoration: underline; color: #888888;"&gt;&lt;strong&gt;##lang.problem.solution.type##&lt;/strong&gt;&lt;/span&gt; : ##problem.solution.type##&lt;br /&gt;&lt;span style="text-decoration: underline; color: #888888;"&gt;&lt;strong&gt;##lang.problem.solution.description##&lt;/strong&gt;&lt;/span&gt; : ##problem.solution.description## ##ENDIFproblem.storestatus##&lt;/div&gt;
&lt;div&gt;##ELSEproblem.storestatus## ##lang.problem.url## : &lt;a href="##problem.url##"&gt;##problem.url##&lt;/a&gt; ##ENDELSEproblem.storestatus##&lt;/div&gt;
&lt;p class="description b"&gt;&lt;strong&gt;##lang.problem.description##&lt;/strong&gt;&lt;/p&gt;
&lt;p&gt;&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.problem.title##&lt;/span&gt;&#160;:##problem.title## &lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.problem.authors##&lt;/span&gt;&#160;:##IFproblem.authors## ##problem.authors## ##ENDIFproblem.authors##    ##ELSEproblem.authors##--##ENDELSEproblem.authors## &lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.problem.creationdate##&lt;/span&gt;&#160;:##problem.creationdate## &lt;br /&gt; ##IFproblem.assigntousers## &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.problem.assigntousers##&lt;/span&gt;&#160;: ##problem.assigntousers## ##ENDIFproblem.assigntousers##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.problem.status## &lt;/span&gt;&#160;: ##problem.status##&lt;br /&gt; ##IFproblem.assigntogroups## &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.problem.assigntogroups##&lt;/span&gt;&#160;: ##problem.assigntogroups## ##ENDIFproblem.assigntogroups##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.problem.urgency##&lt;/span&gt;&#160;: ##problem.urgency##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.problem.impact##&lt;/span&gt;&#160;: ##problem.impact##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.problem.priority##&lt;/span&gt; : ##problem.priority## &lt;br /&gt;##IFproblem.category##&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.problem.category## &lt;/span&gt;&#160;:##problem.category##  ##ENDIFproblem.category## ##ELSEproblem.category##  ##lang.problem.nocategoryassigned## ##ENDELSEproblem.category##    &lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.problem.content##&lt;/span&gt;&#160;: ##problem.content##&lt;/p&gt;
&lt;p&gt;##IFproblem.storestatus=6##&lt;br /&gt;&lt;span style="text-decoration: underline;"&gt;&lt;strong&gt;&lt;span style="color: #888888;"&gt;##lang.problem.solvedate##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##problem.solvedate##&lt;br /&gt;&lt;span style="color: #888888;"&gt;&lt;strong&gt;&lt;span style="text-decoration: underline;"&gt;##lang.problem.solution.type##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##problem.solution.type##&lt;br /&gt;&lt;span style="text-decoration: underline; color: #888888;"&gt;&lt;strong&gt;##lang.problem.solution.description##&lt;/strong&gt;&lt;/span&gt; : ##problem.solution.description##&lt;br /&gt;##ENDIFproblem.storestatus##&lt;/p&gt;
<div class="description b">##lang.problem.numberoffollowups##&#160;: ##problem.numberoffollowups##</div>
<p>##FOREACHfollowups##</p>
<div class="description b"><br /> <strong> [##followup.date##] <em>##lang.followup.isprivate## : ##followup.isprivate## </em></strong><br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.followup.author## </span> ##followup.author##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.followup.description## </span> ##followup.description##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.followup.date## </span> ##followup.date##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.followup.requesttype## </span> ##followup.requesttype##</div>
<p>##ENDFOREACHfollowups##</p>
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
      'id'                       => '18',
      'notificationtemplates_id' => '18',
      'language'                 => '',
      'subject'                  => '##recall.action##: ##recall.item.name##',
      'content_text'             => '##recall.action##: ##recall.item.name##

##recall.item.content##

##lang.recall.planning.begin##: ##recall.planning.begin##
##lang.recall.planning.end##: ##recall.planning.end##
##lang.recall.planning.state##: ##recall.planning.state##
##lang.recall.item.private##: ##recall.item.private##',
      'content_html'             => '&lt;p&gt;##recall.action##: &lt;a href="##recall.item.url##"&gt;##recall.item.name##&lt;/a&gt;&lt;/p&gt;
&lt;p&gt;##recall.item.content##&lt;/p&gt;
&lt;p&gt;##lang.recall.planning.begin##: ##recall.planning.begin##&lt;br /&gt;##lang.recall.planning.end##: ##recall.planning.end##&lt;br /&gt;##lang.recall.planning.state##: ##recall.planning.state##&lt;br /&gt;##lang.recall.item.private##: ##recall.item.private##&lt;br /&gt;&lt;br /&gt;&lt;/p&gt;
&lt;p&gt;&lt;br /&gt;&lt;br /&gt;&lt;/p&gt;',
   ], [
      'id'                       => '19',
      'notificationtemplates_id' => '19',
      'language'                 => '',
      'subject'                  => '##change.action## ##change.title##',
      'content_text'             => '##IFchange.storestatus=5##
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
      'content_html'             => '&lt;p&gt;##IFchange.storestatus=5##&lt;/p&gt;
&lt;div&gt;##lang.change.url## : &lt;a href="##change.urlapprove##"&gt;##change.urlapprove##&lt;/a&gt;&lt;/div&gt;
&lt;div&gt;&lt;span style="color: #888888;"&gt;&lt;strong&gt;&lt;span style="text-decoration: underline;"&gt;##lang.change.solvedate##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##change.solvedate##&lt;br /&gt;&lt;span style="text-decoration: underline; color: #888888;"&gt;&lt;strong&gt;##lang.change.solution.type##&lt;/strong&gt;&lt;/span&gt; : ##change.solution.type##&lt;br /&gt;&lt;span style="text-decoration: underline; color: #888888;"&gt;&lt;strong&gt;##lang.change.solution.description##&lt;/strong&gt;&lt;/span&gt; : ##change.solution.description## ##ENDIFchange.storestatus##&lt;/div&gt;
&lt;div&gt;##ELSEchange.storestatus## ##lang.change.url## : &lt;a href="##change.url##"&gt;##change.url##&lt;/a&gt; ##ENDELSEchange.storestatus##&lt;/div&gt;
&lt;p class="description b"&gt;&lt;strong&gt;##lang.change.description##&lt;/strong&gt;&lt;/p&gt;
&lt;p&gt;&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.change.title##&lt;/span&gt;&#160;:##change.title## &lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.change.authors##&lt;/span&gt;&#160;:##IFchange.authors## ##change.authors## ##ENDIFchange.authors##    ##ELSEchange.authors##--##ENDELSEchange.authors## &lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.change.creationdate##&lt;/span&gt;&#160;:##change.creationdate## &lt;br /&gt; ##IFchange.assigntousers## &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.change.assigntousers##&lt;/span&gt;&#160;: ##change.assigntousers## ##ENDIFchange.assigntousers##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.change.status## &lt;/span&gt;&#160;: ##change.status##&lt;br /&gt; ##IFchange.assigntogroups## &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.change.assigntogroups##&lt;/span&gt;&#160;: ##change.assigntogroups## ##ENDIFchange.assigntogroups##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.change.urgency##&lt;/span&gt;&#160;: ##change.urgency##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.change.impact##&lt;/span&gt;&#160;: ##change.impact##&lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.change.priority##&lt;/span&gt; : ##change.priority## &lt;br /&gt;##IFchange.category##&lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt;##lang.change.category## &lt;/span&gt;&#160;:##change.category##  ##ENDIFchange.category## ##ELSEchange.category##  ##lang.change.nocategoryassigned## ##ENDELSEchange.category##    &lt;br /&gt; &lt;span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"&gt; ##lang.change.content##&lt;/span&gt;&#160;: ##change.content##&lt;/p&gt;
&lt;p&gt;##IFchange.storestatus=6##&lt;br /&gt;&lt;span style="text-decoration: underline;"&gt;&lt;strong&gt;&lt;span style="color: #888888;"&gt;##lang.change.solvedate##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##change.solvedate##&lt;br /&gt;&lt;span style="color: #888888;"&gt;&lt;strong&gt;&lt;span style="text-decoration: underline;"&gt;##lang.change.solution.type##&lt;/span&gt;&lt;/strong&gt;&lt;/span&gt; : ##change.solution.type##&lt;br /&gt;&lt;span style="text-decoration: underline; color: #888888;"&gt;&lt;strong&gt;##lang.change.solution.description##&lt;/strong&gt;&lt;/span&gt; : ##change.solution.description##&lt;br /&gt;##ENDIFchange.storestatus##&lt;/p&gt;
<div class="description b">##lang.change.numberoffollowups##&#160;: ##change.numberoffollowups##</div>
<p>##FOREACHfollowups##</p>
<div class="description b"><br /> <strong> [##followup.date##] <em>##lang.followup.isprivate## : ##followup.isprivate## </em></strong><br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.followup.author## </span> ##followup.author##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.followup.description## </span> ##followup.description##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.followup.date## </span> ##followup.date##<br /> <span style="color: #8b8c8f; font-weight: bold; text-decoration: underline;"> ##lang.followup.requesttype## </span> ##followup.requesttype##</div>
<p>##ENDFOREACHfollowups##</p>
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
      'id'                       => '20',
      'notificationtemplates_id' => '20',
      'language'                 => '',
      'subject'                  => '##mailcollector.action##',
      'content_text'             => '##FOREACHmailcollectors##
##lang.mailcollector.name## : ##mailcollector.name##
##lang.mailcollector.errors## : ##mailcollector.errors##
##mailcollector.url##
##ENDFOREACHmailcollectors##',
      'content_html'             => '&lt;p&gt;##FOREACHmailcollectors##&lt;br /&gt;##lang.mailcollector.name## : ##mailcollector.name##&lt;br /&gt; ##lang.mailcollector.errors## : ##mailcollector.errors##&lt;br /&gt;&lt;a href="##mailcollector.url##"&gt;##mailcollector.url##&lt;/a&gt;&lt;br /&gt; ##ENDFOREACHmailcollectors##&lt;/p&gt;
&lt;p&gt;&lt;/p&gt;',
   ], [
      'id'                       => '21',
      'notificationtemplates_id' => '21',
      'language'                 => '',
      'subject'                  => '##project.action## ##project.name## ##project.code##',
      'content_text'             => '##lang.project.url## : ##project.url##

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
      'content_html'             => '&lt;p&gt;##lang.project.url## : &lt;a href="##project.url##"&gt;##project.url##&lt;/a&gt;&lt;/p&gt;
&lt;p&gt;&lt;strong&gt;##lang.project.description##&lt;/strong&gt;&lt;/p&gt;
&lt;p&gt;##lang.project.name## : ##project.name##&lt;br /&gt;##lang.project.code## : ##project.code##&lt;br /&gt; ##lang.project.manager## : ##project.manager##&lt;br /&gt;##lang.project.managergroup## : ##project.managergroup##&lt;br /&gt; ##lang.project.creationdate## : ##project.creationdate##&lt;br /&gt;##lang.project.priority## : ##project.priority## &lt;br /&gt;##lang.project.state## : ##project.state##&lt;br /&gt;##lang.project.type## : ##project.type##&lt;br /&gt;##lang.project.description## : ##project.description##&lt;/p&gt;
&lt;p&gt;##lang.project.numberoftasks## : ##project.numberoftasks##&lt;/p&gt;
&lt;div&gt;
&lt;p&gt;##FOREACHtasks##&lt;/p&gt;
&lt;div&gt;&lt;strong&gt;[##task.creationdate##] &lt;/strong&gt;&lt;br /&gt; ##lang.task.name## : ##task.name##&lt;br /&gt;##lang.task.state## : ##task.state##&lt;br /&gt;##lang.task.type## : ##task.type##&lt;br /&gt;##lang.task.percent## : ##task.percent##&lt;br /&gt;##lang.task.description## : ##task.description##&lt;/div&gt;
&lt;p&gt;##ENDFOREACHtasks##&lt;/p&gt;
&lt;/div&gt;',
   ], [
      'id'                       => '22',
      'notificationtemplates_id' => '22',
      'language'                 => '',
      'subject'                  => '##projecttask.action## ##projecttask.name##',
      'content_text'             => '##lang.projecttask.url## : ##projecttask.url##

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
      'content_html'             => '&lt;p&gt;##lang.projecttask.url## : &lt;a href="##projecttask.url##"&gt;##projecttask.url##&lt;/a&gt;&lt;/p&gt;
&lt;p&gt;&lt;strong&gt;##lang.projecttask.description##&lt;/strong&gt;&lt;/p&gt;
&lt;p&gt;##lang.projecttask.name## : ##projecttask.name##&lt;br /&gt;##lang.projecttask.project## : &lt;a href="##projecttask.projecturl##"&gt;##projecttask.project##&lt;/a&gt;&lt;br /&gt;##lang.projecttask.creationdate## : ##projecttask.creationdate##&lt;br /&gt;##lang.projecttask.state## : ##projecttask.state##&lt;br /&gt;##lang.projecttask.type## : ##projecttask.type##&lt;br /&gt;##lang.projecttask.description## : ##projecttask.description##&lt;/p&gt;
&lt;p&gt;##lang.projecttask.numberoftasks## : ##projecttask.numberoftasks##&lt;/p&gt;
&lt;div&gt;
&lt;p&gt;##FOREACHtasks##&lt;/p&gt;
&lt;div&gt;&lt;strong&gt;[##task.creationdate##] &lt;/strong&gt;&lt;br /&gt;##lang.task.name## : ##task.name##&lt;br /&gt;##lang.task.state## : ##task.state##&lt;br /&gt;##lang.task.type## : ##task.type##&lt;br /&gt;##lang.task.percent## : ##task.percent##&lt;br /&gt;##lang.task.description## : ##task.description##&lt;/div&gt;
&lt;p&gt;##ENDFOREACHtasks##&lt;/p&gt;
&lt;/div&gt;',
   ], [
      'id'                       => '23',
      'notificationtemplates_id' => '23',
      'language'                 => '',
      'subject'                  => '##objectlock.action##',
      'content_text'             => '##objectlock.type## ###objectlock.id## - ##objectlock.name##

      ##lang.objectlock.url##
      ##objectlock.url##

      ##lang.objectlock.date_mod##
      ##objectlock.date_mod##

      Hello ##objectlock.lockedby.firstname##,
      Could go to this item and unlock it for me?
      Thank you,
      Regards,
      ##objectlock.requester.firstname##',
      'content_html'             => '&lt;table&gt;
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
      'id'                       => '24',
      'notificationtemplates_id' => '24',
      'language'                 => '',
      'subject'                  => '##savedsearch.action## ##savedsearch.name##',
      'content_text'             => '##savedsearch.type## ###savedsearch.id## - ##savedsearch.name##

      ##savedsearch.message##

      ##lang.savedsearch.url##
      ##savedsearch.url##

      Regards,',
      'content_html'             => '&lt;table&gt;
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
      'id'                       => '25',
      'notificationtemplates_id' => '25',
      'language'                 => '',
      'subject'                  => '##certificate.action##  ##certificate.entity##',
      'content_text'             => '##lang.certificate.entity## : ##certificate.entity##

##FOREACHcertificates##

##lang.certificate.serial## : ##certificate.serial##

##lang.certificate.expirationdate## : ##certificate.expirationdate##

##certificate.url##
 ##ENDFOREACHcertificates##',
      'content_html'             => '&lt;p&gt;
##lang.certificate.entity## : ##certificate.entity##&lt;br /&gt;
##FOREACHcertificates##
&lt;br /&gt;##lang.certificate.name## : ##certificate.name##&lt;br /&gt;
##lang.certificate.serial## : ##certificate.serial##&lt;br /&gt;
##lang.certificate.expirationdate## : ##certificate.expirationdate##
&lt;br /&gt; &lt;a href="##certificate.url##"&gt; ##certificate.url##
&lt;/a&gt;&lt;br /&gt; ##ENDFOREACHcertificates##&lt;/p&gt;',
   ],
];

$tables['glpi_profilerights'] = [
   'COLUMNS'   => ['profiles_id', 'name', 'rights'],
   'VALUES'    => [
      ['1', 'computer', '0'],
      ['1', 'monitor', '0'],
      ['1', 'software', '0'],
      ['1', 'networking', '0'],
      ['1', 'internet', '0'],
      ['1', 'printer', '0'],
      ['1', 'peripheral', '0'],
      ['1', 'cartridge', '0'],
      ['1', 'consumable', '0'],
      ['1', 'phone', '0'],
      ['6', 'queuednotification', '0'],
      ['1', 'contact_enterprise', '0'],
      ['1', 'document', '0'],
      ['1', 'contract', '0'],
      ['1', 'infocom', '0'],
      ['1', 'knowbase', '2048'],
      ['1', 'reservation', '1024'],
      ['1', 'reports', '0'],
      ['1', 'dropdown', '0'],
      ['1', 'device', '0'],
      ['1', 'typedoc', '0'],
      ['1', 'link', '0'],
      ['1', 'config', '0'],
      ['1', 'rule_ticket', '0'],
      ['1', 'rule_import', '0'],
      ['1', 'rule_ldap', '0'],
      ['1', 'rule_softwarecategories', '0'],
      ['1', 'search_config', '0'],
      ['5', 'location', '0'],
      ['7', 'domain', '23'],
      ['1', 'profile', '0'],
      ['1', 'user', '0'],
      ['1', 'group', '0'],
      ['1', 'entity', '0'],
      ['1', 'transfer', '0'],
      ['1', 'logs', '0'],
      ['1', 'reminder_public', '1'],
      ['1', 'rssfeed_public', '1'],
      ['1', 'bookmark_public', '0'],
      ['1', 'backup', '0'],
      ['1', 'ticket', '5'],
      ['1', 'followup','5'],
      ['1', 'task', '1'],
      ['1', 'planning', '0'],
      ['2', 'state', '0'],
      ['2', 'taskcategory', '0'],
      ['1', 'statistic', '0'],
      ['1', 'password_update', '1'],
      ['1', 'show_group_hardware', '0'],
      ['1', 'rule_dictionnary_software', '0'],
      ['1', 'rule_dictionnary_dropdown', '0'],
      ['1', 'budget', '0'],
      ['1', 'notification', '0'],
      ['1', 'rule_mailcollector', '0'],
      ['7', 'solutiontemplate', '23'],
      ['1', 'calendar', '0'],
      ['1', 'slm', '0'],
      ['1', 'rule_dictionnary_printer', '0'],
      ['1', 'problem', '0'],
      ['2', 'netpoint', '0'],
      ['4', 'knowbasecategory', '23'],
      ['5', 'itilcategory', '0'],
      ['1', 'itiltemplate', '0'],
      ['1', 'ticketrecurrent', '0'],
      ['1', 'ticketcost', '0'],
      ['6', 'changevalidation', '20'],
      ['1', 'ticketvalidation', '0'],
      ['2', 'computer', '33'],
      ['2', 'monitor', '33'],
      ['2', 'software', '33'],
      ['2', 'networking', '33'],
      ['2', 'internet', '1'],
      ['2', 'printer', '33'],
      ['2', 'peripheral', '33'],
      ['2', 'cartridge', '33'],
      ['2', 'consumable', '33'],
      ['2', 'phone', '33'],
      ['5', 'queuednotification', '0'],
      ['2', 'contact_enterprise', '33'],
      ['2', 'document', '33'],
      ['2', 'contract', '33'],
      ['2', 'infocom', '1'],
      ['2', 'knowbase', '10241'],
      ['2', 'reservation', '1025'],
      ['2', 'reports', '1'],
      ['2', 'dropdown', '0'],
      ['2', 'device', '0'],
      ['2', 'typedoc', '1'],
      ['2', 'link', '1'],
      ['2', 'config', '0'],
      ['2', 'rule_ticket', '0'],
      ['2', 'rule_import', '0'],
      ['2', 'rule_ldap', '0'],
      ['2', 'rule_softwarecategories','0'],
      ['2', 'search_config', '1024'],
      ['4', 'location', '23'],
      ['6', 'domain', '0'],
      ['2', 'profile', '0'],
      ['2', 'user', '2049'],
      ['2', 'group', '33'],
      ['2', 'entity', '0'],
      ['2', 'transfer', '0'],
      ['2', 'logs', '0'],
      ['2', 'reminder_public', '1'],
      ['2', 'rssfeed_public', '1'],
      ['2', 'bookmark_public', '0'],
      ['2', 'backup', '0'],
      ['2', 'ticket', '168989'],
      ['2', 'followup', '5'],
      ['2', 'task', '1'],
      ['6', 'projecttask', '1025'],
      ['7', 'projecttask', '1025'],
      ['2', 'planning', '1'],
      ['1', 'state', '0'],
      ['1', 'taskcategory', '0'],
      ['2', 'statistic', '1'],
      ['2', 'password_update', '1'],
      ['2', 'show_group_hardware', '0'],
      ['2', 'rule_dictionnary_software', '0'],
      ['2', 'rule_dictionnary_dropdown', '0'],
      ['2', 'budget', '33'],
      ['2', 'notification', '0'],
      ['2', 'rule_mailcollector', '0'],
      ['5', 'solutiontemplate', '0'],
      ['6', 'solutiontemplate', '0'],
      ['2', 'calendar', '0'],
      ['2', 'slm', '0'],
      ['2', 'rule_dictionnary_printer', '0'],
      ['2', 'problem', '1057'],
      ['1', 'netpoint', '0'],
      ['3', 'knowbasecategory', '23'],
      ['4', 'itilcategory', '23'],
      ['2', 'itiltemplate', '0'],
      ['2', 'ticketrecurrent', '0'],
      ['2', 'ticketcost', '1'],
      ['4', 'changevalidation', '1044'],
      ['5', 'changevalidation', '20'],
      ['2', 'ticketvalidation', '15376'],
      ['3', 'computer', '127'],
      ['3', 'monitor', '127'],
      ['3', 'software', '127'],
      ['3', 'networking', '127'],
      ['3', 'internet', '31'],
      ['3', 'printer', '127'],
      ['3', 'peripheral', '127'],
      ['3', 'cartridge', '127'],
      ['3', 'consumable', '127'],
      ['3', 'phone', '127'],
      ['4', 'queuednotification', '31'],
      ['3', 'contact_enterprise', '127'],
      ['3', 'document', '127'],
      ['3', 'contract', '127'],
      ['3', 'infocom', '23'],
      ['3', 'knowbase', '14359'],
      ['3', 'reservation', '1055'],
      ['3', 'reports', '1'],
      ['3', 'dropdown', '23'],
      ['3', 'device', '23'],
      ['3', 'typedoc', '23'],
      ['3', 'link', '23'],
      ['3', 'config', '0'],
      ['3', 'rule_ticket', '1047'],
      ['3', 'rule_import', '0'],
      ['3', 'rule_ldap', '0'],
      ['3', 'rule_softwarecategories', '0'],
      ['3', 'search_config', '3072'],
      ['3', 'location', '23'],
      ['5', 'domain', '0'],
      ['3', 'profile', '1'],
      ['3', 'user', '7199'],
      ['3', 'group', '119'],
      ['3', 'entity', '33'],
      ['3', 'transfer',  '1'],
      ['3', 'logs', '1'],
      ['3', 'reminder_public', '23'],
      ['3', 'rssfeed_public','23'],
      ['3', 'bookmark_public', '23'],
      ['3', 'backup', '1024'],
      ['3', 'ticket', '261151'],
      ['3', 'followup', '15383'],
      ['3', 'task', '13329'],
      ['3', 'projecttask', '1121'],
      ['4', 'projecttask', '1121'],
      ['5', 'projecttask', '0'],
      ['3', 'planning', '3073'],
      ['7', 'taskcategory', '23'],
      ['7', 'netpoint', '23'],
      ['3', 'statistic', '1'],
      ['3', 'password_update', '1'],
      ['3', 'show_group_hardware', '0'],
      ['3', 'rule_dictionnary_software', '0'],
      ['3', 'rule_dictionnary_dropdown', '0'],
      ['3', 'budget', '127'],
      ['3', 'notification', '0'],
      ['3', 'rule_mailcollector', '23'],
      ['3', 'solutiontemplate', '23'],
      ['4', 'solutiontemplate', '23'],
      ['3', 'calendar', '23'],
      ['3', 'slm', '23'],
      ['3', 'rule_dictionnary_printer', '0'],
      ['3', 'problem', '1151'],
      ['2', 'knowbasecategory', '0'],
      ['3', 'itilcategory', '23'],
      ['3', 'itiltemplate', '23'],
      ['3', 'ticketrecurrent', '1'],
      ['3', 'ticketcost', '23'],
      ['2', 'changevalidation', '1044'],
      ['3', 'changevalidation', '1044'],
      ['3', 'ticketvalidation', '15376'],
      ['4', 'computer', '255'],
      ['4', 'monitor', '255'],
      ['4', 'software', '255'],
      ['4', 'networking', '255'],
      ['4', 'internet', '159'],
      ['4', 'printer', '255'],
      ['4', 'peripheral', '255'],
      ['4', 'cartridge', '255'],
      ['4', 'consumable', '255'],
      ['4', 'phone', '255'],
      ['4', 'contact_enterprise', '255'],
      ['4', 'document', '255'],
      ['4', 'contract', '255'],
      ['4', 'infocom', '23'],
      ['4', 'knowbase', '15383'],
      ['4', 'reservation', '1055'],
      ['4', 'reports', '1'],
      ['4', 'dropdown', '23'],
      ['4', 'device', '23'],
      ['4', 'typedoc', '23'],
      ['4', 'link', '159'],
      ['4', 'config', '3'],
      ['4', 'rule_ticket', '1047'],
      ['4', 'rule_import', '23'],
      ['4', 'rule_ldap', '23'],
      ['4', 'rule_softwarecategories', '23'],
      ['4', 'search_config', '3072'],
      ['2', 'location', '0'],
      ['4', 'domain', '23'],
      ['4', 'profile', '23'],
      ['4', 'user', '7327'],
      ['4', 'group', '119'],
      ['4', 'entity', '3327'],
      ['4', 'transfer', '23'],
      ['4', 'logs', '1'],
      ['4', 'reminder_public', '159'],
      ['4', 'rssfeed_public', '159'],
      ['4', 'bookmark_public', '23'],
      ['4', 'backup', '1045'],
      ['4', 'ticket', '261151'],
      ['4', 'followup', '15383'],
      ['4', 'task', '13329'],
      ['7', 'project', '1151'],
      ['1', 'projecttask', '0'],
      ['2', 'projecttask', '1025'],
      ['4', 'planning', '3073'],
      ['6', 'taskcategory', '0'],
      ['6', 'netpoint', '0'],
      ['4', 'statistic', '1'],
      ['4', 'password_update', '1'],
      ['4', 'show_group_hardware', '1'],
      ['4', 'rule_dictionnary_software', '23'],
      ['4', 'rule_dictionnary_dropdown', '23'],
      ['4', 'budget', '127'],
      ['4', 'notification', '23'],
      ['4', 'rule_mailcollector', '23'],
      ['1', 'solutiontemplate', '0'],
      ['2', 'solutiontemplate', '0'],
      ['4', 'calendar', '23'],
      ['4', 'slm', '23'],
      ['4', 'rule_dictionnary_printer', '23'],
      ['4', 'problem', '1151'],
      ['1', 'knowbasecategory', '0'],
      ['2', 'itilcategory', '0'],
      ['4', 'itiltemplate', '23'],
      ['4', 'ticketrecurrent', '23'],
      ['4', 'ticketcost', '23'],
      ['7', 'change', '1151'],
      ['1', 'changevalidation', '0'],
      ['4', 'ticketvalidation', '15376'],
      ['5', 'computer', '0'],
      ['5', 'monitor', '0'],
      ['5', 'software', '0'],
      ['5', 'networking', '0'],
      ['5', 'internet', '0'],
      ['5', 'printer', '0'],
      ['5', 'peripheral', '0'],
      ['5', 'cartridge', '0'],
      ['5', 'consumable', '0'],
      ['5', 'phone', '0'],
      ['3', 'queuednotification', '0'],
      ['5', 'contact_enterprise', '0'],
      ['5', 'document', '0'],
      ['5', 'contract', '0'],
      ['5', 'infocom', '0'],
      ['5', 'knowbase', '10240'],
      ['5', 'reservation', '0'],
      ['5', 'reports', '0'],
      ['5', 'dropdown', '0'],
      ['5', 'device', '0'],
      ['5', 'typedoc', '0'],
      ['5', 'link', '0'],
      ['5', 'config', '0'],
      ['5', 'rule_ticket', '0'],
      ['5', 'rule_import', '0'],
      ['5', 'rule_ldap', '0'],
      ['5', 'rule_softwarecategories', '0'],
      ['5', 'search_config', '0'],
      ['1', 'location', '0'],
      ['3', 'domain', '23'],
      ['5', 'profile', '0'],
      ['5', 'user', '1025'],
      ['5', 'group', '0'],
      ['5', 'entity', '0'],
      ['5', 'transfer', '0'],
      ['5', 'logs', '0'],
      ['5', 'reminder_public', '0'],
      ['5', 'rssfeed_public', '0'],
      ['5', 'bookmark_public', '0'],
      ['5', 'backup', '0'],
      ['5', 'ticket', '140295'],
      ['5', 'followup', '12295'],
      ['5', 'task', '8193'],
      ['4', 'project', '1151'],
      ['5', 'project', '1151'],
      ['6', 'project', '1151'],
      ['5', 'planning', '1'],
      ['5', 'taskcategory', '0'],
      ['5', 'netpoint', '0'],
      ['5', 'statistic', '1'],
      ['5', 'password_update', '1'],
      ['5', 'show_group_hardware', '0'],
      ['5', 'rule_dictionnary_software', '0'],
      ['5', 'rule_dictionnary_dropdown', '0'],
      ['5', 'budget', '0'],
      ['5', 'notification', '0'],
      ['5', 'rule_mailcollector', '0'],
      ['6', 'state', '0'],
      ['7', 'state', '23'],
      ['5', 'calendar', '0'],
      ['5', 'slm', '0'],
      ['5', 'rule_dictionnary_printer', '0'],
      ['5', 'problem', '1024'],
      ['7', 'knowbasecategory', '23'],
      ['1', 'itilcategory', '0'],
      ['5', 'itiltemplate', '0'],
      ['5', 'ticketrecurrent', '0'],
      ['5', 'ticketcost', '23'],
      ['5', 'change', '1054'],
      ['6', 'change', '1151'],
      ['5', 'ticketvalidation', '3088'],
      ['6', 'computer', '127'],
      ['6', 'monitor', '127'],
      ['6', 'software', '127'],
      ['6', 'networking', '127'],
      ['6', 'internet', '31'],
      ['6', 'printer', '127'],
      ['6', 'peripheral', '127'],
      ['6', 'cartridge', '127'],
      ['6', 'consumable', '127'],
      ['6', 'phone', '127'],
      ['2', 'queuednotification', '0'],
      ['6', 'contact_enterprise', '96'],
      ['6', 'document', '127'],
      ['6', 'contract', '96'],
      ['6', 'infocom', '0'],
      ['6', 'knowbase', '14359'],
      ['6', 'reservation', '1055'],
      ['6', 'reports', '1'],
      ['6', 'dropdown', '0'],
      ['6', 'device', '0'],
      ['6', 'typedoc', '0'],
      ['6', 'link', '0'],
      ['6', 'config', '0'],
      ['6', 'rule_ticket', '0'],
      ['6', 'rule_import', '0'],
      ['6', 'rule_ldap', '0'],
      ['6', 'rule_softwarecategories', '0'],
      ['6', 'search_config', '0'],
      ['2', 'domain', '0'],
      ['6', 'profile', '0'],
      ['6', 'user', '1055'],
      ['6', 'group', '1'],
      ['6', 'entity', '33'],
      ['6', 'transfer', '1'],
      ['6', 'logs', '0'],
      ['6', 'reminder_public', '23'],
      ['6', 'rssfeed_public', '23'],
      ['6', 'bookmark_public', '0'],
      ['6', 'backup', '0'],
      ['6', 'ticket', '166919'],
      ['6', 'followup', '13319'],
      ['6', 'task', '13329'],
      ['1', 'project', '0'],
      ['2', 'project', '1025'],
      ['3', 'project', '1151'],
      ['6', 'planning', '1'],
      ['4', 'taskcategory', '23'],
      ['4', 'netpoint', '23'],
      ['6', 'statistic', '1'],
      ['6', 'password_update', '1'],
      ['6', 'show_group_hardware', '0'],
      ['6', 'rule_dictionnary_software', '0'],
      ['6', 'rule_dictionnary_dropdown', '0'],
      ['6', 'budget', '96'],
      ['6', 'notification', '0'],
      ['6', 'rule_mailcollector', '0'],
      ['4', 'state', '23'],
      ['5', 'state', '0'],
      ['6', 'calendar', '0'],
      ['6', 'slm', '1'],
      ['6', 'rule_dictionnary_printer', '0'],
      ['6', 'problem', '1121'],
      ['6', 'knowbasecategory', '0'],
      ['7', 'itilcategory', '23'],
      ['7', 'location', '23'],
      ['6', 'itiltemplate', '1'],
      ['6', 'ticketrecurrent', '1'],
      ['6', 'ticketcost', '23'],
      ['3', 'change', '1151'],
      ['4', 'change', '1151'],
      ['6', 'ticketvalidation', '3088'],
      ['7', 'computer', '127'],
      ['7', 'monitor', '127'],
      ['7', 'software', '127'],
      ['7', 'networking', '127'],
      ['7', 'internet', '31'],
      ['7', 'printer', '127'],
      ['7', 'peripheral', '127'],
      ['7', 'cartridge', '127'],
      ['7', 'consumable', '127'],
      ['7', 'phone', '127'],
      ['1', 'queuednotification', '0'],
      ['7', 'contact_enterprise', '96'],
      ['7', 'document', '127'],
      ['7', 'contract', '96'],
      ['7', 'infocom', '0'],
      ['7', 'knowbase', '14359'],
      ['7', 'reservation', '1055'],
      ['7', 'reports', '1'],
      ['7', 'dropdown', '0'],
      ['7', 'device', '0'],
      ['7', 'typedoc', '0'],
      ['7', 'link', '0'],
      ['7', 'config', '0'],
      ['7', 'rule_ticket', '1047'],
      ['7', 'rule_import', '0'],
      ['7', 'rule_ldap', '0'],
      ['7', 'rule_softwarecategories', '0'],
      ['7', 'search_config', '0'],
      ['1', 'domain', '0'],
      ['7', 'profile', '0'],
      ['7', 'user', '1055'],
      ['7', 'group', '1'],
      ['7', 'entity', '33'],
      ['7', 'transfer', '1'],
      ['7', 'logs', '1'],
      ['7', 'reminder_public', '23'],
      ['7', 'rssfeed_public', '23'],
      ['7', 'bookmark_public', '0'],
      ['7', 'backup', '0'],
      ['7', 'ticket', '261151'],
      ['7', 'followup', '15383'],
      ['7', 'task', '13329'],
      ['7', 'queuednotification', '0'],
      ['7', 'planning', '3073'],
      ['3', 'taskcategory', '23'],
      ['3', 'netpoint', '23'],
      ['7', 'statistic', '1'],
      ['7', 'password_update', '1'],
      ['7', 'show_group_hardware', '0'],
      ['7', 'rule_dictionnary_software', '0'],
      ['7', 'rule_dictionnary_dropdown', '0'],
      ['7', 'budget', '96'],
      ['7', 'notification', '0'],
      ['7', 'rule_mailcollector', '23'],
      ['7', 'changevalidation', '1044'],
      ['3', 'state', '23'],
      ['7', 'calendar', '23'],
      ['7', 'slm', '23'],
      ['7', 'rule_dictionnary_printer', '0'],
      ['7', 'problem', '1151'],
      ['5', 'knowbasecategory', '0'],
      ['6', 'itilcategory', '0'],
      ['6', 'location', '0'],
      ['7', 'itiltemplate', '23'],
      ['7', 'ticketrecurrent', '1'],
      ['7', 'ticketcost', '23'],
      ['1', 'change', '0'],
      ['2', 'change', '1057'],
      ['7', 'ticketvalidation', '15376'],
      ['8', 'backup', '1'],
      ['8', 'bookmark_public', '1'],
      ['8', 'budget', '33'],
      ['8', 'calendar', '1'],
      ['8', 'cartridge', '33'],
      ['8', 'change', '1057'],
      ['8', 'changevalidation', '0'],
      ['8', 'computer', '33'],
      ['8', 'config', '1'],
      ['8', 'consumable', '33'],
      ['8', 'contact_enterprise', '33'],
      ['8', 'contract', '33'],
      ['8', 'device', '1'],
      ['8', 'document', '33'],
      ['8', 'domain', '1'],
      ['8', 'dropdown', '1'],
      ['8', 'entity', '33'],
      ['8', 'followup', '8193'],
      ['8', 'global_validation', '0'],
      ['8', 'group', '33'],
      ['8', 'infocom', '1'],
      ['8', 'internet', '1'],
      ['8', 'itilcategory', '1'],
      ['8', 'knowbase', '10241'],
      ['8', 'knowbasecategory', '1'],
      ['8', 'link', '1'],
      ['8', 'location', '1'],
      ['8', 'logs', '1'],
      ['8', 'monitor', '33'],
      ['8', 'netpoint', '1'],
      ['8', 'networking', '33'],
      ['8', 'notification', '1'],
      ['8', 'password_update', '0'],
      ['8', 'peripheral', '33'],
      ['8', 'phone', '33'],
      ['8', 'planning', '3073'],
      ['8', 'printer', '33'],
      ['8', 'problem', '1057'],
      ['8', 'profile', '1'],
      ['8', 'project', '1057'],
      ['8', 'projecttask', '33'],
      ['8', 'queuednotification', '1'],
      ['8', 'reminder_public', '1'],
      ['8', 'reports', '1'],
      ['8', 'reservation', '1'],
      ['8', 'rssfeed_public', '1'],
      ['8', 'rule_dictionnary_dropdown', '1'],
      ['8', 'rule_dictionnary_printer', '1'],
      ['8', 'rule_dictionnary_software', '1'],
      ['8', 'rule_import', '1'],
      ['8', 'rule_ldap', '1'],
      ['8', 'rule_mailcollector', '1'],
      ['8', 'rule_softwarecategories', '1'],
      ['8', 'rule_ticket', '1'],
      ['8', 'search_config', '0'],
      ['8', 'show_group_hardware', '1'],
      ['8', 'slm', '1'],
      ['8', 'software', '33'],
      ['8', 'solutiontemplate', '1'],
      ['8', 'state', '1'],
      ['8', 'statistic', '1'],
      ['8', 'task', '8193'],
      ['8', 'taskcategory', '1'],
      ['8', 'ticket', '138241'],
      ['8', 'ticketcost', '1'],
      ['8', 'ticketrecurrent', '1'],
      ['8', 'itiltemplate', '1'],
      ['8', 'ticketvalidation', '0'],
      ['8', 'transfer', '1'],
      ['8', 'typedoc', '1'],
      ['8', 'user', '1'],
      ['1', 'license', '0'],
      ['2', 'license', '33'],
      ['3', 'license', '127'],
      ['4', 'license', '255'],
      ['5', 'license', '0'],
      ['6', 'license', '127'],
      ['7', 'license', '127'],
      ['8', 'license', '33'],
      ['1', 'line', '0'],
      ['2', 'line', '33'],
      ['3', 'line', '127'],
      ['4', 'line', '255'],
      ['5', 'line', '0'],
      ['6', 'line', '127'],
      ['7', 'line', '127'],
      ['8', 'line', '33'],
      ['1', 'lineoperator', '0'],
      ['2', 'lineoperator', '33'],
      ['3', 'lineoperator', '23'],
      ['4', 'lineoperator', '23'],
      ['5', 'lineoperator', '0'],
      ['6', 'lineoperator', '0'],
      ['7', 'lineoperator', '23'],
      ['8', 'lineoperator', '1'],
      ['1', 'devicesimcard_pinpuk', '0'],
      ['2', 'devicesimcard_pinpuk', '1'],
      ['3', 'devicesimcard_pinpuk', '3'],
      ['4', 'devicesimcard_pinpuk', '3'],
      ['5', 'devicesimcard_pinpuk', '0'],
      ['6', 'devicesimcard_pinpuk', '3'],
      ['7', 'devicesimcard_pinpuk', '3'],
      ['8', 'devicesimcard_pinpuk', '1'],
      ['1', 'certificate', '0'],
      ['2', 'certificate', '33'],
      ['3', 'certificate', '127'],
      ['4', 'certificate', '255'],
      ['5', 'certificate', '0'],
      ['6', 'certificate', '127'],
      ['7', 'certificate', '127'],
      ['8', 'certificate', '33'],
      ['1', 'datacenter', '0'],
      ['2', 'datacenter', '1'],
      ['3', 'datacenter', '31'],
      ['4', 'datacenter', '31'],
      ['5', 'datacenter', '0'],
      ['6', 'datacenter', '31'],
      ['7', 'datacenter', '31'],
      ['8', 'datacenter', '1'],
      ['4', 'rule_asset', '1047'],
      ['1', 'personalization', '3'],
      ['2', 'personalization', '3'],
      ['3', 'personalization', '3'],
      ['4', 'personalization', '3'],
      ['5', 'personalization', '3'],
      ['6', 'personalization', '3'],
      ['7', 'personalization', '3'],
      ['8', 'personalization', '3'],
      ['1', 'rule_asset', '0'],
      ['2', 'rule_asset', '0'],
      ['3', 'rule_asset', '0'],
      ['5', 'rule_asset', '0'],
      ['6', 'rule_asset', '0'],
      ['7', 'rule_asset', '0'],
      ['8', 'rule_asset', '0'],
      ['1', 'global_validation', '0'],
      ['2', 'global_validation', '0'],
      ['3', 'global_validation', '0'],
      ['4', 'global_validation', '0'],
      ['5', 'global_validation', '0'],
      ['6', 'global_validation', '0'],
      ['7', 'global_validation', '0'],
      ['1', 'cluster', 0],
      ['2', 'cluster', 1],
      ['3', 'cluster', 31],
      ['4', 'cluster', 31],
      ['5', 'cluster', 0],
      ['6', 'cluster', 31],
      ['7', 'cluster', 31],
      ['8', 'cluster', 1],
      ['1', 'externalevent', 0],
      ['2', 'externalevent', 1],
      ['3', 'externalevent', 1055],
      ['4', 'externalevent', 1055],
      ['5', 'externalevent', 0],
      ['6', 'externalevent', 1],
      ['7', 'externalevent', 31],
      ['8', 'externalevent', 1]
   ]
];


$tables['glpi_profiles'] = [
   [
      'id'                     => '1',
      'name'                   => 'Self-Service',
      'interface'              => 'helpdesk',
      'is_default'             => '1',
      'helpdesk_hardware'      => '1',
      'helpdesk_item_type'     => '["Computer","Monitor","NetworkEquipment","Peripheral","Phone","Printer","Software", "DCRoom", "Rack", "Enclosure"]',
      'ticket_status'          => '{"1":{"2":0,"3":0,"4":0,"5":0,"6":0},"2":{"1":0,"3":0,"4":0,"5":0,"6":0},"3":{"1":0,"2":0,"4":0,"5":0,"6":0},"4":{"1":0,"2":0,"3":0,"5":0,"6":0},"5":{"1":0,"2":0,"3":0,"4":0},"6":{"1":0,"2":0,"3":0,"4":0,"5":0}}',
      'comment'                => '',
      'problem_status'         => '[]',
      'create_ticket_on_login' => '0',
      'tickettemplates_id'     => '0',
      'change_status'          => null,
   ], [
      'id'                     => '2',
      'name'                   => 'Observer',
      'interface'              => 'central',
      'is_default'             => '0',
      'helpdesk_hardware'      => '1',
      'helpdesk_item_type'     => '["Computer","Monitor","NetworkEquipment","Peripheral","Phone","Printer","Software", "DCRoom", "Rack", "Enclosure"]',
      'ticket_status'          => '[]',
      'comment'                => '',
      'problem_status'         => '[]',
      'create_ticket_on_login' => '0',
      'tickettemplates_id'     => '0',
      'change_status'          => null,
   ], [
      'id'                     => '3',
      'name'                   => 'Admin',
      'interface'              => 'central',
      'is_default'             => '0',
      'helpdesk_hardware'      => '3',
      'helpdesk_item_type'     => '["Computer","Monitor","NetworkEquipment","Peripheral","Phone","Printer","Software", "DCRoom", "Rack", "Enclosure"]',
      'ticket_status'          => '[]',
      'comment'                => '',
      'problem_status'         => '[]',
      'create_ticket_on_login' => '0',
      'tickettemplates_id'     => '0',
      'change_status'          => null,
   ], [
      'id'                     => '4',
      'name'                   => 'Super-Admin',
      'interface'              => 'central',
      'is_default'             => '0',
      'helpdesk_hardware'      => '3',
      'helpdesk_item_type'     => '["Computer","Monitor","NetworkEquipment","Peripheral","Phone","Printer","Software", "DCRoom", "Rack", "Enclosure"]',
      'ticket_status'          => '[]',
      'comment'                => '',
      'problem_status'         => '[]',
      'create_ticket_on_login' => '0',
      'tickettemplates_id'     => '0',
      'change_status'          => null,
   ], [
      'id'                     => '5',
      'name'                   => 'Hotliner',
      'interface'              => 'central',
      'is_default'             => '0',
      'helpdesk_hardware'      => '3',
      'helpdesk_item_type'     => '["Computer","Monitor","NetworkEquipment","Peripheral","Phone","Printer","Software", "DCRoom", "Rack", "Enclosure"]',
      'ticket_status'          => '[]',
      'comment'                => '',
      'problem_status'         => '[]',
      'create_ticket_on_login' => '1',
      'tickettemplates_id'     => '0',
      'change_status'          => null,
   ], [
      'id'                     => '6',
      'name'                   => 'Technician',
      'interface'              => 'central',
      'is_default'             => '0',
      'helpdesk_hardware'      => '3',
      'helpdesk_item_type'     => '["Computer","Monitor","NetworkEquipment","Peripheral","Phone","Printer","Software", "DCRoom", "Rack", "Enclosure"]',
      'ticket_status'          => '[]',
      'comment'                => '',
      'problem_status'         => '[]',
      'create_ticket_on_login' => '0',
      'tickettemplates_id'     => '0',
      'change_status'          => null,
   ], [
      'id'                     => '7',
      'name'                   => 'Supervisor',
      'interface'              => 'central',
      'is_default'             => '0',
      'helpdesk_hardware'      => '3',
      'helpdesk_item_type'     => '["Computer","Monitor","NetworkEquipment","Peripheral","Phone","Printer","Software", "DCRoom", "Rack", "Enclosure"]',
      'ticket_status'          => '[]',
      'comment'                => '',
      'problem_status'         => '[]',
      'create_ticket_on_login' => '0',
      'tickettemplates_id'     => '0',
      'change_status'          => null,
   ], [
      'id'                     => '8',
      'name'                   => 'Read-Only',
      'interface'              => 'central',
      'is_default'             => '0',
      'helpdesk_hardware'      => '0',
      'helpdesk_item_type'     => '[]',
      'ticket_status'          => '{"1":{"2":0,"3":0,"4":0,"5":0,"6":0},"2":{"1":0,"3":0,"4":0,"5":0,"6":0},"3":{"1":0,"2":0,"4":0,"5":0,"6":0},"4":{"1":0,"2":0,"3":0,"5":0,"6":0},"5":{"1":0,"2":0,"3":0,"4":0,"6":0},"6":{"1":0,"2":0,"3":0,"4":0,"5":0}}',
      'comment'                => 'This profile defines read-only access. It is used when objects are locked. It can also be used to give to users rights to unlock objects.',
      'problem_status'         => '{"1":{"7":0,"2":0,"3":0,"4":0,"5":0,"8":0,"6":0},"7":{"1":0,"2":0,"3":0,"4":0,"5":0,"8":0,"6":0},"2":{"1":0,"7":0,"3":0,"4":0,"5":0,"8":0,"6":0},"3":{"1":0,"7":0,"2":0,"4":0,"5":0,"8":0,"6":0},"4":{"1":0,"7":0,"2":0,"3":0,"5":0,"8":0,"6":0},"5":{"1":0,"7":0,"2":0,"3":0,"4":0,"8":0,"6":0},"8":{"1":0,"7":0,"2":0,"3":0,"4":0,"5":0,"6":0},"6":{"1":0,"7":0,"2":0,"3":0,"4":0,"5":0,"8":0}}',
      'create_ticket_on_login' => '0',
      'tickettemplates_id'     => '0',
      'change_status'          => '{"1":{"9":0,"10":0,"7":0,"4":0,"11":0,"12":0,"5":0,"8":0,"6":0},"9":{"1":0,"10":0,"7":0,"4":0,"11":0,"12":0,"5":0,"8":0,"6":0},"10":{"1":0,"9":0,"7":0,"4":0,"11":0,"12":0,"5":0,"8":0,"6":0},"7":{"1":0,"9":0,"10":0,"4":0,"11":0,"12":0,"5":0,"8":0,"6":0},"4":{"1":0,"9":0,"10":0,"7":0,"11":0,"12":0,"5":0,"8":0,"6":0},"11":{"1":0,"9":0,"10":0,"7":0,"4":0,"12":0,"5":0,"8":0,"6":0},"12":{"1":0,"9":0,"10":0,"7":0,"4":0,"11":0,"5":0,"8":0,"6":0},"5":{"1":0,"9":0,"10":0,"7":0,"4":0,"11":0,"12":0,"8":0,"6":0},"8":{"1":0,"9":0,"10":0,"7":0,"4":0,"11":0,"12":0,"5":0,"6":0},"6":{"1":0,"9":0,"10":0,"7":0,"4":0,"11":0,"12":0,"5":0,"8":0}}',
   ],
];

$tables['glpi_profiles_users'] = [
   'COLUMNS'   => ['id', 'users_id', 'profiles_id', 'entities_id', 'is_recursive', 'is_dynamic'],
   'VALUES'    => [
      ['2', '2', '4', '0', '1', '0'],
      ['3', '3', '1', '0', '1', '0'],
      ['4', '4', '6', '0', '1', '0'],
      ['5', '5', '2', '0', '1', '0'],
   ]
];

$tables['glpi_projectstates'] = [
   [
      'name'        => 'New',
      'color'       => '#06ff00',
      'is_finished' => '0',
   ], [
      'name'        => 'Processing',
      'color'       => '#ffb800',
      'is_finished' => '0',
   ], [
      'name'        => 'Closed',
      'color'       => '#ff0000',
      'is_finished' => '1',
   ],
];

$tables['glpi_requesttypes'] = [
   'COLUMNS'   => ['name', 'is_helpdesk_default', 'is_followup_default', 'is_mail_default', 'is_mailfollowup_default'],
   'VALUES'    => [
      ['Helpdesk', '1', '1', '0', '0'],
      ['E-Mail', '0', '0', '1', '1'],
      ['Phone', '0', '0', '0', '0'],
      ['Direct', '0', '0', '0', '0'],
      ['Written', '0', '0', '0', '0'],
      ['Other', '0', '0', '0', '0',],
   ]
];

$tables['glpi_ruleactions'] = [
   'COLUMNS'   => ['id', 'rules_id', 'action_type', 'field', 'value'],
   'VALUES'    => [
      ['6', '6', 'fromitem', 'locations_id', '1',],
      ['2', '2', 'assign', 'entities_id', '0',],
      ['3', '3', 'assign', 'entities_id', '0',],
      ['4', '4', 'assign', '_refuse_email_no_response', '1',],
      ['5', '5', 'assign', '_refuse_email_no_response', '1',],
      ['7', '7', 'fromuser', 'locations_id', '1',],
      ['8', '8', 'assign', '_import_category', '1',],
      ['9', '9', 'regex_result', '_affect_user_by_regex', '#0',],
      ['10', '10', 'regex_result', '_affect_user_by_regex', '#0',],
      ['11', '11', 'regex_result', '_affect_user_by_regex', '#0',],
   ]
];

$tables['glpi_rulecriterias'] = [
   'COLUMNS'   => ['id', 'rules_id', 'criteria', 'condition', 'pattern'],
   'VALUES'    => [
      [9, 6, 'locations_id', 9, 1],
      [2, 2, 'uid', 0, '*'],
      [3, 2, 'samaccountname', 0, '*'],
      [4, 2, 'MAIL_EMAIL', 0, '*'],
      [5, 3, 'subject', 6, '/.*/'],
      [6, 4, 'x-auto-response-suppress', 6, '/\\S+/'],
      [7, 5, 'auto-submitted', '6', '/^(?!.*no).+$/i'],
      [10, 6, 'items_locations', 8, 1],
      [11, 7, 'locations_id', 9, 1],
      [12, 7, 'users_locations', 8, 1],
      [13, 8, 'name', 0, '*'],
      [14, 9, '_itemtype', 0, 'Computer'],
      [15, 9, '_auto', 0, 1],
      [16, 9, 'contact', 6, '/(.*)@/'],
      [17, 10, '_itemtype', 0, 'Computer'],
      [18, 10, '_auto', 0, 1],
      [19, 10, 'contact', 6, '/(.*),/'],
      [20, 11, '_itemtype', 0, 'Computer'],
      [21, 11, '_auto', 0, 1],
      [22, 11, 'contact', 6, '/(.*)/'],
   ]
];

$tables['glpi_rulerightparameters'] = [
   'COLUMNS'   => ['id', 'name', 'value'],
   'VALUES'    => [
      [1, '(LDAP)Organization', 'o'],
      ['2', '(LDAP)Common Name', 'cn'],
      ['3', '(LDAP)Department Number', 'departmentnumber'],
      ['4', '(LDAP)Email', 'mail'],
      ['5', 'Object Class', 'objectclass'],
      ['6', '(LDAP)User ID', 'uid'],
      ['7', '(LDAP)Telephone Number', 'phone'],
      ['8', '(LDAP)Employee Number', 'employeenumber'],
      ['9', '(LDAP)Manager', 'manager'],
      ['10', '(LDAP)DistinguishedName', 'dn'],
      ['12', '(AD)User ID', 'samaccountname'],
      ['13', '(LDAP) Title', 'title'],
      ['14', '(LDAP) MemberOf', 'memberof'],
   ]
];

$tables['glpi_rules'] = [
   'COLUMNS'   => ['id', 'sub_type', 'ranking', 'name', 'description', 'match', 'is_active', 'is_recursive', 'uuid', 'condition'],
   'VALUES'    => [
      ['2', 'RuleRight', '1', 'Root', '', 'OR', '1', 0, '500717c8-2bd6e957-53a12b5fd35745.02608131', 0],
      ['3', 'RuleMailCollector', '3', 'Root', '', 'OR', '1', '0', '500717c8-2bd6e957-53a12b5fd36404.54713349', '0'],
      ['4', 'RuleMailCollector', '1', 'X-Auto-Response-Suppress', 'Exclude Auto-Reply emails using X-Auto-Response-Suppress header', 'AND', '0', '1', '500717c8-2bd6e957-53a12b5fd36d97.94503423', '0'],
      ['5', 'RuleMailCollector', '2', 'Auto-Reply Auto-Submitted', 'Exclude Auto-Reply emails using Auto-Submitted header', 'OR', '1', '1', '500717c8-2bd6e957-53a12b5fd376c2.87642651', '0'],
      ['6', 'RuleTicket', '1', 'Ticket location from item', '', 'AND', '0', '1', '500717c8-2bd6e957-53a12b5fd37f94.10365341', '1'],
      ['7', 'RuleTicket', '2', 'Ticket location from user', '', 'AND', '0', '1', '500717c8-2bd6e957-53a12b5fd38869.86002585', '1'],
      ['8', 'RuleSoftwareCategory', '1', 'Import category from inventory tool', '', 'AND', '0', '1', '500717c8-2bd6e957-53a12b5fd38869.86003425', '1'],
      ['9', 'RuleAsset', '1', 'Domain user assignation', '', 'AND', '1', '1', 'fbeb1115-7a37b143-5a3a6fc1afdc17.92779763', '3'],
      ['10', 'RuleAsset', '2', 'Multiple users: assign to the first', '', 'AND', '1', '1', 'fbeb1115-7a37b143-5a3a6fc1b03762.88595154', '3'],
      ['11', 'RuleAsset', '3', 'One user assignation', '', 'AND', '1', '1', 'fbeb1115-7a37b143-5a3a6fc1b073e1.16257440', '3'],
   ]
];

$tables['glpi_softwarecategories'] = [
   [
      'name'         => 'FUSION',
      'completename' => 'FUSION',
      'level'        => '1',
   ],
];

$tables['glpi_softwarelicensetypes'] = [
   [
      'name'         => 'OEM',
      'is_recursive' => 1,
      'completename' => 'OEM',
   ],
];

$tables['glpi_ssovariables'] = [
   'COLUMNS'   => ['name'],
   'VALUES'    => [
      ['HTTP_AUTH_USER'],
      ['REMOTE_USER'],
      ['PHP_AUTH_USER'],
      ['USERNAME'],
      ['REDIRECT_REMOTE_USER'],
      ['HTTP_REMOTE_USER']
   ]
];

$tables['glpi_tickettemplates'] = [
   [
      'name'         => 'Default',
      'is_recursive' => 1,
   ],
];

$tables['glpi_changetemplates'] = [
   [
      'name'         => 'Default',
      'is_recursive' => 1,
   ],
];

$tables['glpi_problemtemplates'] = [
   [
      'name'         => 'Default',
      'is_recursive' => 1,
   ],
];

$tables['glpi_tickettemplatemandatoryfields'] = [
   [
      'tickettemplates_id' => 1,
      'num'                => 21,
   ],
];

$tables['glpi_changetemplatemandatoryfields'] = [
   [
      'changetemplates_id' => 1,
      'num'                => 21,
   ],
];

$tables['glpi_problemtemplatemandatoryfields'] = [
   [
      'problemtemplates_id' => 1,
      'num'                 => 21,
   ],
];

$tables['glpi_transfers'] = [
   [
      'name'                => 'complete',
      'keep_ticket'         => '2',
      'keep_networklink'    => '2',
      'keep_reservation'    => 1,
      'keep_history'        => 1,
      'keep_device'         => 1,
      'keep_infocom'        => 1,
      'keep_dc_monitor'     => 1,
      'clean_dc_monitor'    => 1,
      'keep_dc_phone'       => 1,
      'clean_dc_phone'      => 1,
      'keep_dc_peripheral'  => 1,
      'clean_dc_peripheral' => 1,
      'keep_dc_printer'     => 1,
      'clean_dc_printer'    => 1,
      'keep_supplier'       => 1,
      'clean_supplier'      => 1,
      'keep_contact'        => 1,
      'clean_contact'       => 1,
      'keep_contract'       => 1,
      'clean_contract'      => 1,
      'keep_software'       => 1,
      'clean_software'      => 1,
      'keep_document'       => 1,
      'clean_document'      => 1,
      'keep_cartridgeitem'  => 1,
      'clean_cartridgeitem' => 1,
      'keep_cartridge'      => 1,
      'keep_consumable'     => 1,
      'keep_disk'           => 1,
   ],
];

$tables['glpi_users'] = [
   'COLUMNS'   => ['id', 'name', 'password', 'language', 'list_limit', 'authtype'],
   'VALUES'    => [
      ['2', 'glpi', '$2y$10$rXXzbc2ShaiCldwkw4AZL.n.9QSH7c0c9XJAyyjrbL9BwmWditAYm', null, '20', '1'],
      ['3', 'post-only', '$2y$10$dTMar1F3ef5X/H1IjX9gYOjQWBR1K4bERGf4/oTPxFtJE/c3vXILm', 'en_GB', '20', '1'],
      ['4', 'tech', '$2y$10$.xEgErizkp6Az0z.DHyoeOoenuh0RcsX4JapBk2JMD6VI17KtB1lO', 'en_GB', '20', '1'],
      ['5', 'normal', '$2y$10$Z6doq4zVHkSPZFbPeXTCluN1Q/r0ryZ3ZsSJncJqkN3.8cRiN0NV.', 'en_GB', '20', '1']
   ]
];

$tables['glpi_devicefirmwaretypes'] = [
   'COLUMNS'   => ['name'],
   'VALUES'    => [
      ['BIOS'],
      ['UEFI'],
      ['Firmware']
   ]
];

return $tables;
