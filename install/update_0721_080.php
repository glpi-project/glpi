<?php


/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

// Update from 0.721 to 0.80

function update0721to080() {
	global $DB, $LANG;

	echo "<h3>".$LANG['install'][4]." -&gt; 0.80</h3>";
   displayMigrationMessage("080"); // Start

   displayMigrationMessage("080", $LANG['update'][141] . ' - Clean DB : rename tables'); // Updating schema

   $glpi_tables=array(
      'glpi_alerts'                       => 'glpi_alerts',
      'glpi_auth_ldap'                    => 'glpi_authldaps',
      'glpi_auth_ldap_replicate'          => 'glpi_authldapsreplicates',
      'glpi_auth_mail'                    => 'glpi_authmails',
      'glpi_dropdown_auto_update'         => 'glpi_autoupdatesystems',
      'glpi_bookmark'                     => 'glpi_bookmarks',
      'glpi_display_default'              => 'glpi_bookmarks_users',
      'glpi_dropdown_budget'              => 'glpi_budgets',
      'glpi_cartridges'                   => 'glpi_cartridges',
      'glpi_cartridges_type'              => 'glpi_cartridgesitems',
      'glpi_cartridges_assoc'             => 'glpi_cartridges_printersmodels',
      'glpi_dropdown_cartridge_type'      => 'glpi_cartridgesitemstypes',
      'glpi_computers'                    => 'glpi_computers',
      'glpi_computerdisks'                => 'glpi_computersdisks',
      'glpi_dropdown_model'               => 'glpi_computersmodels',
      'glpi_type_computers'               => 'glpi_computerstypes',
      'glpi_computer_device'              => 'glpi_computers_devices',
      'glpi_connect_wire'                 => 'glpi_computers_items',
      'glpi_inst_software'                => 'glpi_computers_softwaresversions',
      'glpi_config'                       => 'glpi_configs',
      'glpi_consumables'                  => 'glpi_consumables',
      'glpi_consumables_type'             => 'glpi_consumablesitems',
      'glpi_dropdown_consumable_type'     => 'glpi_consumablesitemstypes',
      'glpi_contact_enterprise'           => 'glpi_contacts_suppliers',
      'glpi_contacts'                     => 'glpi_contacts',
      'glpi_dropdown_contact_type'        => 'glpi_contactstypes',
      'glpi_contracts'                    => 'glpi_contracts',
      'glpi_dropdown_contract_type'       => 'glpi_contractstypes',
      'glpi_contract_device'              => 'glpi_contracts_items',
      'glpi_contract_enterprise'          => 'glpi_contracts_suppliers',
      'glpi_device_case'                  => 'glpi_devicescases',
      'glpi_dropdown_case_type'           => 'glpi_devicescasestypes',
      'glpi_device_control'               => 'glpi_devicescontrols',
      'glpi_device_drive'                 => 'glpi_devicesdrives',
      'glpi_device_gfxcard'               => 'glpi_devicesgraphiccards',
      'glpi_device_hdd'                   => 'glpi_devicesharddrives',
      'glpi_device_iface'                 => 'glpi_devicesnetworkcards',
      'glpi_device_moboard'               => 'glpi_devicesmotherboards',
      'glpi_device_pci'                   => 'glpi_devicespcis',
      'glpi_device_power'                 => 'glpi_devicespowersupplies',
      'glpi_device_processor'             => 'glpi_devicesprocessors',
      'glpi_device_ram'                   => 'glpi_devicesmemories',
      'glpi_dropdown_ram_type'            => 'glpi_devicesmemoriestypes',
      'glpi_device_sndcard'               => 'glpi_devicessoundcards',
      'glpi_display'                      => 'glpi_displayprefs',
      'glpi_docs'                         => 'glpi_documents',
      'glpi_dropdown_rubdocs'             => 'glpi_documentscategories',
      'glpi_type_docs'                    => 'glpi_documentstypes',
      'glpi_doc_device'                   => 'glpi_documents_items',
      'glpi_dropdown_domain'              => 'glpi_domains',
      'glpi_entities'                     => 'glpi_entities',
      'glpi_entities_data'                => 'glpi_entitiesdatas',
      'glpi_event_log'                    => 'glpi_events',
      'glpi_dropdown_filesystems'         => 'glpi_filesystems',
      'glpi_groups'                       => 'glpi_groups',
      'glpi_users_groups'                 => 'glpi_groups_users',
      'glpi_infocoms'                     => 'glpi_infocoms',
      'glpi_dropdown_interface'           => 'glpi_interfaces',
      'glpi_kbitems'                      => 'glpi_knowbaseitems',
      'glpi_dropdown_kbcategories'        => 'glpi_knowbaseitemscategories',
      'glpi_links'                        => 'glpi_links',
      'glpi_links_device'                 => 'glpi_links_itemtypes',
      'glpi_dropdown_locations'           => 'glpi_locations',
      'glpi_history'                      => 'glpi_logs',
      'glpi_mailgate'                     => 'glpi_mailcollectors',
      'glpi_mailing'                      => 'glpi_mailingsettings',
      'glpi_dropdown_manufacturer'        => 'glpi_manufacturers',
      'glpi_monitors'                     => 'glpi_monitors',
      'glpi_dropdown_model_monitors'      => 'glpi_monitorsmodels',
      'glpi_type_monitors'                => 'glpi_monitorstypes',
      'glpi_dropdown_netpoint'            => 'glpi_netpoints',
      'glpi_networking'                   => 'glpi_networkequipments',
      'glpi_dropdown_firmware'            => 'glpi_networkequipmentsfirmwares',
      'glpi_dropdown_model_networking'    => 'glpi_networkequipmentsmodels',
      'glpi_type_networking'              => 'glpi_networkequipmentstypes',
      'glpi_dropdown_iface'               => 'glpi_networkinterfaces',
      'glpi_networking_ports'             => 'glpi_networkports',
      'glpi_networking_vlan'              => 'glpi_networkports_vlans',
      'glpi_networking_wire'              => 'glpi_networkports_networkports',
      'glpi_dropdown_network'             => 'glpi_networks',
      'glpi_ocs_admin_link'               => 'glpi_ocsadmininfoslinks',
      'glpi_ocs_link'                     => 'glpi_ocslinks',
      'glpi_ocs_config'                   => 'glpi_ocsservers',
      'glpi_dropdown_os'                  => 'glpi_operatingsystems',
      'glpi_dropdown_os_sp'               => 'glpi_operatingsystemsservicepacks',
      'glpi_dropdown_os_version'          => 'glpi_operatingsystemsversions',
      'glpi_peripherals'                  => 'glpi_peripherals',
      'glpi_dropdown_model_peripherals'   => 'glpi_peripheralsmodels',
      'glpi_type_peripherals'             => 'glpi_peripheralstypes',
      'glpi_phones'                       => 'glpi_phones',
      'glpi_dropdown_model_phones'        => 'glpi_phonesmodels',
      'glpi_dropdown_phone_power'         => 'glpi_phonespowersupplies',
      'glpi_type_phones'                  => 'glpi_phonestypes',
      'glpi_plugins'                      => 'glpi_plugins',
      'glpi_printers'                     => 'glpi_printers',
      'glpi_dropdown_model_printers'      => 'glpi_printersmodels',
      'glpi_type_printers'                => 'glpi_printerstypes',
      'glpi_profiles'                     => 'glpi_profiles',
      'glpi_users_profiles'               => 'glpi_profiles_users',
      'glpi_registry'                     => 'glpi_registrykeys',
      'glpi_reminder'                     => 'glpi_reminders',
      'glpi_reservation_resa'             => 'glpi_reservations',
      'glpi_reservation_item'             => 'glpi_reservationsitems',
      'glpi_rules_descriptions'           => 'glpi_rules',
      'glpi_rules_actions'                => 'glpi_rulesactions',
      'glpi_rule_cache_model_computer'    => 'glpi_rulescachecomputersmodels',
      'glpi_rule_cache_type_computer'     => 'glpi_rulescachecomputerstypes',
      'glpi_rule_cache_manufacturer'      => 'glpi_rulescachemanufacturers',
      'glpi_rule_cache_model_monitor'     => 'glpi_rulescachemonitorsmodels',
      'glpi_rule_cache_type_monitor'      => 'glpi_rulescachemonitorstypes',
      'glpi_rule_cache_model_networking'  => 'glpi_rulescachenetworkequipmentsmodels',
      'glpi_rule_cache_type_networking'   => 'glpi_rulescachenetworkequipmentstypes',
      'glpi_rule_cache_os'                => 'glpi_rulescacheoperatingsystems',
      'glpi_rule_cache_os_sp'             => 'glpi_rulescacheoperatingsystemsservicepacks',
      'glpi_rule_cache_os_version'        => 'glpi_rulescacheoperatingsystemsversions',
      'glpi_rule_cache_model_peripheral'  => 'glpi_rulescacheperipheralsmodels',
      'glpi_rule_cache_type_peripheral'   => 'glpi_rulescacheperipheralstypes',
      'glpi_rule_cache_model_phone'       => 'glpi_rulescachephonesmodels',
      'glpi_rule_cache_type_phone'        => 'glpi_rulescachephonestypes',
      'glpi_rule_cache_model_printer'     => 'glpi_rulescacheprintersmodels',
      'glpi_rule_cache_type_printer'      => 'glpi_rulescacheprinterstypes',
      'glpi_rule_cache_software'          => 'glpi_rulescachesoftwares',
      'glpi_rules_criterias'              => 'glpi_rulescriterias',
      'glpi_rules_ldap_parameters'        => 'glpi_rulesldapparameters',
      'glpi_software'                     => 'glpi_softwares',
      'glpi_dropdown_software_category'   => 'glpi_softwarescategories',
      'glpi_softwarelicenses'             => 'glpi_softwareslicenses',
      'glpi_dropdown_licensetypes'        => 'glpi_softwareslicensestypes',
      'glpi_softwareversions'             => 'glpi_softwaresversions',
      'glpi_dropdown_state'               => 'glpi_states',
      'glpi_enterprises'                  => 'glpi_suppliers',
      'glpi_dropdown_enttype'             => 'glpi_supplierstypes',
      'glpi_tracking'                     => 'glpi_tickets',
      'glpi_dropdown_tracking_category'   => 'glpi_ticketscategories',
      'glpi_followups'                    => 'glpi_ticketsfollowups',
      'glpi_tracking_planning'            => 'glpi_ticketsplannings',
      'glpi_transfers'                    => 'glpi_transfers',
      'glpi_users'                        => 'glpi_users',
      'glpi_dropdown_user_titles'         => 'glpi_userstitles',
      'glpi_dropdown_user_types'          => 'glpi_userscategories',
      'glpi_dropdown_vlan'                => 'glpi_vlans',
   );
   $backup_tables=false;
	foreach ($glpi_tables as $original_table => $new_table) {
      if (strcmp($original_table,$new_table)!=0) {
         // Original table exists ?
            if (TableExists($original_table)) {
               // rename new tables if exists ?
               if (TableExists($new_table)) {
                  if (TableExists("backup_$new_table")) {
                     $query="DROP TABLE `backup_".$new_table."`";
                     $DB->query($query) or die("0.80 drop backup table backup_$new_table ". $LANG['update'][90] . $DB->error());
                  }
                  echo "<p><b>$new_table table already exists. ";
                  echo "A backup have been done to backup_$new_table.</b></p>";
                  $backup_tables=true;
                  $query="RENAME TABLE `$new_table` TO `backup_$new_table`";
                  $DB->query($query) or die("0.80 backup table $new_table " . $LANG['update'][90] . $DB->error());

               }
               // rename original table
               $query="RENAME TABLE `$original_table` TO `$new_table`";
               $DB->query($query) or die("0.80 rename $original_table to $new_table " . $LANG['update'][90] . $DB->error());
            }
      }
   }
   if ($backup_tables){
      echo "<div class='red'><p>You can delete backup tables if you have no need of them.</p></div>";
   }

   displayMigrationMessage("080", $LANG['update'][141] . ' - Clean DB : rename foreign keys'); // Updating schema

   $foreignkeys=array(
   'assign' => array(array('to' => 'users_id_assign',
                           'tables' => array('glpi_tickets')),
                     ),
   'assign_group' => array(array('to' => 'groups_id_assign',
                           'tables' => array('glpi_tickets')),
                     ),
   'assign_ent' => array(array('to' => 'suppliers_id_assign',
                           'tables' => array('glpi_tickets')),
                     ),
   'auth_method' => array(array('to' => 'authtype',
                           'noindex' => array('glpi_users'),
                           'tables' => array('glpi_users'),),
                     ),
   'author' => array(array('to' => 'users_id',
                           'tables' => array('glpi_ticketsfollowups','glpi_knowbaseitems',
                              'glpi_tickets')),
                     ),
   'auto_update' => array(array('to' => 'autoupdatesystems_id',
                           'tables' => array('glpi_computers',)),
                     ),
   'budget' => array(array('to' => 'budgets_id',
                           'tables' => array('glpi_infocoms')),
                     ),
   'buy_version' => array(array('to' => 'softwaresversions_id_buy',
                           'tables' => array('glpi_softwareslicenses')),
                     ),
   'category' => array(array('to' => 'ticketscategories_id',
                           'tables' => array('glpi_tickets')),
                      array('to' => 'softwarescategories_id',
                           'tables' => array('glpi_softwares')),
                     ),
   'categoryID' => array(array('to' => 'knowbaseitemscategories_id',
                           'tables' => array('glpi_knowbaseitems')),
                     ),
   'category_on_software_delete' => array(array('to' => 'softwarescategories_id_ondelete',
                           'noindex' => array('glpi_configs'),
                           'tables' => array('glpi_configs'),
                           'comments' => array('glpi_configs'=>'category applyed when a software is deleted')),
                     ),
   'cID' => array(array('to' => 'computers_id',
                           'tables' => array('glpi_computers_softwaresversions')),
                     ),
   'computer' => array(array('to' => 'items_id',
                           'noindex' => array('glpi_tickets'),
                           'tables' => array('glpi_tickets')),
                     ),
   'computer_id' => array(array('to' => 'computers_id',
                           'tables' => array('glpi_registrykeys')),
                     ),
   'contract_type' => array(array('to' => 'contractstypes_id',
                           'tables' => array('glpi_contracts')),
                     ),
   'default_rubdoc_tracking' => array(array('to' => 'documentscategories_id_forticket',
                           'noindex' => array('glpi_configs'),
                           'tables' => array('glpi_configs'),
                           'comments' => array('glpi_configs'=>'default category for documents added with a ticket')),
                     ),
   'default_state' => array(array('to' => 'states_id_default',
                           'noindex' => array('glpi_ocsservers'),
                           'tables' => array('glpi_ocsservers')),
                     ),
   'device_type' => array( array('to' => 'itemtype',
                           'noindex' => array('glpi_alerts','glpi_contracts_items',
                                 'glpi_bookmarks_users','glpi_documents_items','glpi_logs',
                                 'glpi_infocoms','glpi_links_itemtypes','glpi_networkports',
                                 'glpi_reservationsitems','glpi_tickets',),
                           'tables' => array('glpi_alerts','glpi_contracts_items',
                                 'glpi_documents_items','glpi_infocoms','glpi_bookmarks',
                                 'glpi_bookmarks_users','glpi_logs','glpi_links_itemtypes',
                                 'glpi_networkports','glpi_reservationsitems','glpi_tickets')),
                           array('to' => 'devicetype', 
                              'noindex' => array('glpi_computers_devices'),
                              'tables' => array('glpi_computers_devices')),
                     ),
   'device_internal_type' => array(array('to' => 'devicetype',
                                       'tables' => array('glpi_logs')),
                     ),
   'domain' => array(array('to' => 'domains_id',
                           'tables' => array('glpi_computers','glpi_networkequipments',
                              'glpi_printers')),
                     ),
   'end1' => array(array('to' => 'items_id',
                        'noindex' => array('glpi_computers_items'),
                        'tables' => array('glpi_computers_items'),
                        'comments' => array('glpi_computers_items'=>'RELATION to various table, according to itemtype (ID)')),
                  array('to' => 'networkports_id_1',
                        'noindex' => array('glpi_networkports_networkports'),
                        'tables' => array('glpi_networkports_networkports')),
                     ),
   'end2' => array(array('to' => 'computers_id',
                        'tables' => array('glpi_computers_items')),
                  array('to' => 'networkports_id_2',
                        'tables' => array('glpi_networkports_networkports')),
                     ),
   'extra_ldap_server' => array(array('to' => 'authldaps_id_extra',
                           'noindex' => array('glpi_configs'),
                           'tables' => array('glpi_configs'),
                           'comments' => array('glpi_configs'=>'extra server')),
                     ),
   'firmware' => array(array('to' => 'networkequipmentsfirmwares_id',
                           'tables' => array('glpi_networkequipments')),
                     ),
   'FK_bookmark' => array(array('to' => 'bookmarks_id',
                           'tables' => array('glpi_bookmarks_users')),
                     ),
   'FK_computers' => array(array('to' => 'computers_id',
                           'tables' => array('glpi_computers_devices','glpi_computersdisks',
                                       'glpi_softwareslicenses',)),
                     ),
   'FK_contact' => array(array('to' => 'contacts_id',
                           'tables' => array('glpi_contacts_suppliers')),
                     ),
   'FK_contract' => array(array('to' => 'contracts_id',
                           'noindex' => array('glpi_contracts_items'),
                           'tables' => array('glpi_contracts_suppliers','glpi_contracts_items')),
                     ),
   'FK_device' => array(array('to' => 'items_id',
                           'noindex' => array('glpi_alerts','glpi_contracts_items',
                                 'glpi_documents_items','glpi_infocoms'),
                           'tables' => array('glpi_alerts','glpi_contracts_items',
                                 'glpi_documents_items','glpi_infocoms')),
                        array('to' => 'devices_id',
                           'noindex' => array('glpi_computers_devices'),
                           'tables' => array('glpi_computers_devices')),
                     ),
   'FK_doc' => array(array('to' => 'documents_id',
                           'noindex' => array('glpi_documents_items'),
                           'tables' => array('glpi_documents_items')),
                     ),
   'FK_enterprise' => array(array('to' => 'suppliers_id',
                           'noindex' => array('glpi_contacts_suppliers','glpi_contracts_suppliers'),
                           'tables' => array('glpi_contacts_suppliers','glpi_contracts_suppliers',
                                    'glpi_infocoms')),
                     ),
   'FK_entities' => array(array('to' => 'entities_id',
                           'noindex' => array('glpi_locations','glpi_netpoints',
                              'glpi_entitiesdatas',),
                           'tables' => array('glpi_bookmarks','glpi_cartridgesitems',
                              'glpi_computers','glpi_consumablesitems','glpi_contacts',
                              'glpi_contracts','glpi_documents','glpi_locations',
                              'glpi_netpoints','glpi_suppliers','glpi_entitiesdatas',
                              'glpi_groups','glpi_knowbaseitems','glpi_links',
                              'glpi_mailcollectors','glpi_monitors','glpi_networkequipments',
                              'glpi_peripherals','glpi_phones','glpi_printers',
                              'glpi_reminders','glpi_rules','glpi_softwares',
                              'glpi_softwareslicenses','glpi_tickets','glpi_users',
                              'glpi_profiles_users',),
                           'default'=> array('glpi_bookmarks' => "-1")),
                     ),
   'FK_filesystems' => array(array('to' => 'filesystems_id',
                           'tables' => array('glpi_computersdisks',)),
                     ),
   'FK_glpi_cartridges_type' => array(array('to' => 'cartridgesitems_id',
                           'tables' => array('glpi_cartridges',
                              'glpi_cartridges_printersmodels')),
                     ),
   'FK_glpi_consumables_type' => array(array('to' => 'consumablesitems_id',
                           'noindex' => array('glpi_consumables'),
                           'tables' => array('glpi_consumables',)),
                     ),
   'FK_glpi_device' => array(array('to' => 'items_id',
                           'noindex' => array('glpi_logs'),
                           'tables' => array('glpi_logs')),
                     ),
   'FK_glpi_dropdown_model_printers' => array(array('to' => 'printersmodels_id',
                           'noindex' => array('glpi_cartridges_printersmodels'),
                           'tables' => array('glpi_cartridges_printersmodels',)),
                     ),
   'FK_glpi_enterprise' => array(array('to' => 'manufacturers_id',
                     'tables' => array('glpi_cartridgesitems','glpi_computers',
                        'glpi_consumablesitems','glpi_devicescases','glpi_devicescontrols',
                        'glpi_devicesdrives','glpi_devicesgraphiccards','glpi_devicesharddrives',
                        'glpi_devicesnetworkcards','glpi_devicesmotherboards','glpi_devicespcis',
                        'glpi_devicespowersupplies','glpi_devicesprocessors','glpi_devicesmemories',
                        'glpi_devicessoundcards','glpi_monitors','glpi_networkequipments',
                        'glpi_peripherals','glpi_phones','glpi_printers',
                        'glpi_softwares',)),
                     ),
   'FK_glpi_printers' => array(array('to' => 'printers_id',
                           'tables' => array('glpi_cartridges',)),
                     ),
   'FK_group' => array(array('to' => 'groups_id',
                           'tables' => array('glpi_tickets')),
                     ),
   'FK_groups' => array(array('to' => 'groups_id',
                           'tables' => array('glpi_computers','glpi_monitors',
                              'glpi_networkequipments','glpi_peripherals','glpi_phones',
                              'glpi_printers','glpi_softwares','glpi_groups_users')),
                     ),
   'FK_interface' => array(array('to' => 'interfaces_id',
                           'tables' => array('glpi_devicesgraphiccards')),
                     ),
   'FK_item' => array(array('to' => 'items_id',
                           'noindex' => array('glpi_mailingsettings'),
                           'tables' => array('glpi_mailingsettings')),
                     ),
   'FK_links' => array(array('to' => 'links_id',
                           'tables' => array('glpi_links_itemtypes')),
                     ),
   'FK_port' => array(array('to' => 'networkports_id',
                           'noindex' => array('glpi_networkports_vlans'),
                           'tables' => array('glpi_networkports_vlans')),
                     ),
   'FK_profiles' => array(array('to' => 'profiles_id',
                           'tables' => array('glpi_profiles_users','glpi_users')),
                     ),
   'FK_rules' => array(array('to' => 'rules_id',
                           'tables' => array('glpi_rulescriterias','glpi_rulesactions')),
                     ),
   'FK_tracking' => array(array('to' => 'tickets_id',
                           'tables' => array('glpi_documents')),
                     ),
   'FK_users' => array(array('to' => 'users_id',
                              'noindex' => array('glpi_displayprefs','glpi_bookmarks_users',
                                 'glpi_groups_users',),
                              'tables' => array('glpi_bookmarks', 'glpi_displayprefs',
                                 'glpi_documents', 'glpi_groups','glpi_reminders',
                                 'glpi_bookmarks_users','glpi_groups_users','glpi_profiles_users',
                                 'glpi_computers', 'glpi_monitors',
                                 'glpi_networkequipments', 'glpi_peripherals', 'glpi_phones',
                                 'glpi_printers','glpi_softwares')),
                     ),
   'FK_vlan' => array(array('to' => 'vlans_id',
                           'tables' => array('glpi_networkports_vlans')),
                     ),
   'glpi_id' => array(array('to' => 'computers_id',
                           'tables' => array('glpi_ocslinks')),
                     ),
   'id_assign' => array(array('to' => 'users_id',
                           'tables' => array('glpi_ticketsplannings')),
                     ),
   'id_auth' => array(array('to' => 'auths_id',
                           'noindex' => array('glpi_users'),
                           'tables' => array('glpi_users'),),
                     ),
   'id_device' => array(array('to' => 'items_id',
                           'noindex' => array('glpi_reservationsitems'),
                           'tables' => array('glpi_reservationsitems')),
                     ),
   'id_followup' => array(array('to' => 'ticketsfollowups_id',
                           'tables' => array('glpi_ticketsplannings')),
                     ),
   'id_item' => array(array('to' => 'reservationsitems_id',
                           'tables' => array('glpi_reservations')),
                     ),
   'id_user' => array(array('to' => 'users_id',
                           'tables' => array('glpi_consumables','glpi_reservations')),
                     ),
   'iface' => array(array('to' => 'networkinterfaces_id',
                           'tables' => array('glpi_networkports')),
                     ),
   'interface' => array(array('to' => 'interfaces_id',
                           'tables' => array('glpi_devicescontrols','glpi_devicesharddrives',
                                 'glpi_devicesdrives')),
                     ),
   'item' => array(array('to' => 'items_id',
                           'noindex' => array('glpi_events'),
                           'tables' => array('glpi_events')),
                     ),
   'link_if_status' => array(array('to' => 'states_id_linkif',
                           'noindex' => array('glpi_ocsservers'),
                           'tables' => array('glpi_ocsservers')),
                     ),
   'location' => array(array('to' => 'locations_id',
                           'noindex' => array('glpi_netpoints'),
                           'tables' => array('glpi_cartridgesitems','glpi_computers',
                              'glpi_consumablesitems','glpi_netpoints','glpi_monitors',
                              'glpi_networkequipments','glpi_peripherals','glpi_phones',
                              'glpi_printers','glpi_users','glpi_softwares')),
                     ),
   'model' => array(array('to' => 'computersmodels_id',
                           'tables' => array('glpi_computers')),
                     array('to' => 'monitorsmodels_id',
                           'tables' => array('glpi_monitors')),
                     array('to' => 'networkequipmentsmodels_id',
                           'tables' => array('glpi_networkequipments')),
                     array('to' => 'peripheralsmodels_id',
                           'tables' => array('glpi_peripherals')),
                     array('to' => 'phonesmodels_id',
                           'tables' => array('glpi_phones')),
                     array('to' => 'printersmodels_id',
                           'tables' => array('glpi_printers')),
                     ),
   'netpoint' => array(array('to' => 'netpoints_id',
                           'tables' => array('glpi_networkports')),
                     ),
   'network' => array(array('to' => 'networks_id',
                           'tables' => array('glpi_computers','glpi_networkequipments',
                              'glpi_printers')),
                     ),
   'ocs_id' => array(array('to' => 'ocsid',
                           'noindex' => array('glpi_ocslinks'),
                           'tables' => array('glpi_ocslinks')),
                     ),
   'ocs_server_id' => array(array('to' => 'ocsservers_id',
                           'noindex' => array('glpi_ocslinks'),
                           'tables' => array('glpi_ocsadmininfoslinks','glpi_ocslinks')),
                     ),
   'on_device' => array(array('to' => 'items_id',
                           'noindex' => array('glpi_networkports'),
                           'tables' => array('glpi_networkports')),
                     ),
   'os' => array(array('to' => 'operatingsystems_id',
                           'tables' => array('glpi_computers',)),
                     ),
   'os_sp' => array(array('to' => 'operatingsystemsservicepacks_id',
                           'tables' => array('glpi_computers',)),
                     ),
   'os_version' => array(array('to' => 'operatingsystemsversions_id',
                           'tables' => array('glpi_computers',)),
                     ),
   'parentID' => array(array('to' => 'knowbaseitemscategories_id',
                           'noindex' => array('glpi_knowbaseitemscategories'),
                           'tables' => array('glpi_knowbaseitemscategories')),
                        array('to' => 'locations_id',
                           'tables' => array('glpi_locations')),
                        array('to' => 'ticketscategories_id',
                           'tables' => array('glpi_ticketscategories')),
                        array('to' => 'entities_id',
                           'tables' => array('glpi_entities')),
                     ),
   'platform' => array(array('to' => 'operatingsystems_id',
                           'tables' => array('glpi_softwares',)),
                     ),
   'power' => array(array('to' => 'phonespowersupplies_id',
                           'tables' => array('glpi_phones')),
                     ),
   'recipient' => array(array('to' => 'users_id_recipient',
                           'tables' => array('glpi_tickets')),
                     ),
   'rubrique' => array(array('to' => 'documentscategories_id',
                           'tables' => array('glpi_documents')),
                     ),
   'rule_id' => array(array('to' => 'rules_id',
                           'tables' => array('glpi_rulescachemanufacturers',
                              'glpi_rulescachecomputersmodels','glpi_rulescachemonitorsmodels',
                              'glpi_rulescachenetworkequipmentsmodels','glpi_rulescacheperipheralsmodels',
                              'glpi_rulescachephonesmodels','glpi_rulescacheprintersmodels',
                              'glpi_rulescacheoperatingsystems','glpi_rulescacheoperatingsystemsservicepacks',
                              'glpi_rulescacheoperatingsystemsversions','glpi_rulescachesoftwares',
                              'glpi_rulescachecomputerstypes','glpi_rulescachemonitorstypes',
                              'glpi_rulescachenetworkequipmentstypes','glpi_rulescacheperipheralstypes',
                              'glpi_rulescachephonestypes','glpi_rulescacheprinterstypes',)),
                     ),
   'server_id' => array(array('to' => 'authldaps_id',
                           'tables' => array('glpi_authldapsreplicates')),
                     ),
   'sID' => array(array('to' => 'softwares_id',
                           'tables' => array('glpi_softwareslicenses','glpi_softwaresversions')),
                     ),
   'state' => array(array('to' => 'states_id',
                           'tables' => array('glpi_computers','glpi_monitors',
                              'glpi_networkequipments','glpi_peripherals','glpi_phones',
                              'glpi_printers','glpi_softwaresversions')),
                     ),
   'tech_num' => array(array('to' => 'users_id_tech',
                              'tables' => array('glpi_cartridgesitems','glpi_computers',
                              'glpi_consumablesitems','glpi_monitors',
                              'glpi_networkequipments','glpi_peripherals','glpi_phones',
                              'glpi_printers','glpi_softwares')),
                     ),
   'title' => array(array('to' => 'userstitles_id',
                           'tables' => array('glpi_users')),
                     ),
   'tracking' => array(array('to' => 'tickets_id',
                           'tables' => array('glpi_ticketsfollowups')),
                     ),
   'type' => array(array('to' => 'cartridgesitemstypes_id',
                           'tables' => array('glpi_cartridgesitems')),
                  array('to' => 'computerstypes_id', 
                           'tables' => array('glpi_computers')),
                  array('to' => 'consumablesitemstypes_id',
                           'tables' => array('glpi_consumablesitems')),
                  array('to' => 'contactstypes_id', 
                           'tables' => array('glpi_contacts')),
                  array('to' => 'devicescasestypes_id', 
                           'tables' => array('glpi_devicescases')),
                  array('to' => 'devicesmemoriestypes_id',
                           'tables' => array('glpi_devicesmemories')),
                  array('to' => 'supplierstypes_id', 
                           'tables' => array('glpi_suppliers')),
                  array('to' => 'monitorstypes_id', 
                           'tables' => array('glpi_monitors')),
                  array('to' => 'networkequipmentstypes_id',
                           'tables' => array('glpi_networkequipments')),
                  array('to' => 'peripheralstypes_id', 
                           'tables' => array('glpi_peripherals')),
                  array('to' => 'phonestypes_id', 
                           'tables' => array('glpi_phones')),
                  array('to' => 'printerstypes_id', 
                           'tables' => array('glpi_printers')),
                  array('to' => 'softwareslicensestypes_id',
                           'tables' => array('glpi_softwareslicenses')),
                  array('to' => 'userscategories_id', 
                           'tables' => array('glpi_users')),
                  array('to' => 'itemtype', 'noindex' => array('glpi_computers_items'),
                           'tables' => array('glpi_computers_items','glpi_displayprefs')),
                     ),
   'update_software' => array(array('to' => 'softwares_id',
                           'tables' => array('glpi_softwares')),
                     ),
   'use_version' => array(array('to' => 'softwaresversions_id_use',
                           'tables' => array('glpi_softwareslicenses')),
                     ),
   'vID' => array(array('to' => 'softwaresversions_id',
                           'tables' => array('glpi_computers_softwaresversions')),
                     ),
   );


   foreach ($foreignkeys as $oldname => $newnames) {
      foreach ($newnames as $tab){
         $newname=$tab['to'];
         foreach ($tab['tables'] as $table){
            $doindex=true;
            if (isset($tab['noindex'])&&in_array($table,$tab['noindex'])){
               $doindex=false;
            }
            // Rename field
            if (FieldExists($table, $oldname)) {
               $addcomment='';
               if (isset($tab['comments']) && isset($tab['comments'][$table])) {
                  $addcomment=" COMMENT '".$tab['comments'][$table]."' ";
               }
               $default_value=0;
               if (isset($tab['default']) && isset($tab['default'][$table])) {
                  $default_value=$tab['default'][$table];
               }
               // Manage NULL fields 
               $query="UPDATE `$table` SET `$oldname`='$default_value' WHERE `$oldname` IS NULL ";
               $DB->query($query) or die("0.80 prepare datas for update $oldname to $newname in $table " . $LANG['update'][90] . $DB->error());

               $query="ALTER TABLE `$table` CHANGE `$oldname` `$newname` INT( 11 ) NOT NULL DEFAULT '$default_value' $addcomment";
               $DB->query($query) or die("0.80 rename $oldname to $newname in $table " . $LANG['update'][90] . $DB->error());
            } else {
               echo "<div class='red'><p>Error : $table.$oldname does not exist.</p></div>";
            }
            // If do index : delete old one / create new one
            if ($doindex){
               if (isIndex($table, $oldname)) {
                  $query="ALTER TABLE `$table` DROP INDEX `$oldname`;";
                  $DB->query($query) or die("0.80 drop index $oldname in $table " . $LANG['update'][90] . $DB->error());
               }
               if (!isIndex($table, $newname)) {
                  $query="ALTER TABLE `$table` ADD INDEX `$newname` (`$newname`);";
                  $DB->query($query) or die("0.80 create index $newname in $table " . $LANG['update'][90] . $DB->error());
               }
            }
         }
      }
   }


   displayMigrationMessage("080", $LANG['update'][141] . ' - Clean DB : rename bool values'); // Updating schema

   $boolfields=array(
   'glpi_authldaps' => array(array('from' => 'ldap_use_tls', 'to' => 'use_tls', 'default' =>0, 'noindex'=>true),//
                           array('from' => 'use_dn', 'to' => 'use_dn', 'default' =>1, 'noindex'=>true),//
                     ),
   'glpi_bookmarks' => array(array('from' => 'private', 'to' => 'is_private', 'default' =>1 ),//
                           array('from' => 'recursive','to' => 'is_recursive', 'default' =>0 ),//
                     ),
   'glpi_cartridgesitems' => array(array('from' => 'deleted', 'to' => 'is_deleted', 'default' =>0 ),//
                     ),
   'glpi_computers' => array(array('from' => 'is_template', 'to' => 'is_template', 'default' =>0 ),//
                           array('from' => 'deleted', 'to' => 'is_deleted', 'default' =>0 ),//
                           array('from' => 'ocs_import', 'to' => 'is_ocs_import', 'default' =>0 ),//
                     ),
   'glpi_configs' => array(array('from' => 'jobs_at_login', 'to' => 'show_jobs_at_login', 'default' =>0, 'noindex'=>true),//
                           array('from' => 'mailing', 'to' => 'use_mailing', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'permit_helpdesk', 'to' => 'use_anonymous_helpdesk', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'existing_auth_server_field_clean_domain', 'to' => 'existing_auth_server_field_clean_domain', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'auto_assign', 'to' => 'use_auto_assign_to_tech', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'public_faq', 'to' => 'use_public_faq', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'url_in_mail', 'to' => 'show_link_in_mail', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'use_ajax', 'to' => 'use_ajax', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'ajax_autocompletion', 'to' => 'use_ajax_autocompletion', 'default' =>1, 'noindex'=>true ),//
                           array('from' => 'auto_add_users', 'to' => 'is_users_auto_add', 'default' =>1, 'noindex'=>true ),//
                           array('from' => 'view_ID', 'to' => 'is_ids_visible', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'ocs_mode', 'to' => 'use_ocs_mode', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'followup_on_update_ticket', 'to' => 'add_followup_on_update_ticket', 'default' =>1, 'noindex'=>true ),//
                           array('from' => 'licenses_alert', 'to' => 'use_licenses_alert', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'keep_tracking_on_delete', 'to' => 'keep_tickets_on_delete', 'default' =>1, 'noindex'=>true ),//
                           array('from' => 'use_errorlog', 'to' => 'use_log_in_files', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'autoupdate_link_contact', 'to' => 'is_contact_autoupdate', 'default' =>1, 'noindex'=>true ),//
                           array('from' => 'autoupdate_link_user', 'to' => 'is_user_autoupdate', 'default' =>1, 'noindex'=>true ),//
                           array('from' => 'autoupdate_link_group', 'to' => 'is_group_autoupdate', 'default' =>1, 'noindex'=>true ),//
                           array('from' => 'autoupdate_link_location', 'to' => 'is_location_autoupdate', 'default' =>1, 'noindex'=>true ),//
                           array('from' => 'autoclean_link_contact', 'to' => 'is_contact_autoclean', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'autoclean_link_user', 'to' => 'is_user_autoclean', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'autoclean_link_group', 'to' => 'is_group_autoclean', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'autoclean_link_location', 'to' => 'is_location_autoclean', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'flat_dropdowntree', 'to' => 'use_flat_dropdowntree', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'autoname_entity', 'to' => 'use_autoname_by_entity', 'default' =>1, 'noindex'=>true ),//
                           array('from' => 'expand_soft_categorized', 'to' => 'is_categorized_soft_expanded', 'default' =>1, 'noindex'=>true ),//
                           array('from' => 'expand_soft_not_categorized', 'to' => 'is_not_categorized_soft_expanded', 'default' =>1, 'noindex'=>true ),//
                           array('from' => 'dbreplicate_notify_desynchronization', 'to' => 'use_notification_on_dbreplicate_desync', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'ticket_title_mandatory', 'to' => 'is_ticket_title_mandatory', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'ticket_content_mandatory', 'to' => 'is_ticket_content_mandatory', 'default' =>1, 'noindex'=>true ),//
                           array('from' => 'ticket_category_mandatory', 'to' => 'is_ticket_category_mandatory', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'followup_private', 'to' => 'default_followup_private', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'software_helpdesk_visible', 'to' => 'default_software_helpdesk_visible', 'default' =>1, 'noindex'=>true ),//
                     ),
   'glpi_consumablesitems' => array(array('from' => 'deleted', 'to' => 'is_deleted', 'default' =>0 ),//
                     ),
   'glpi_contacts' => array(array('from' => 'recursive','to' => 'is_recursive', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'deleted', 'to' => 'is_deleted', 'default' =>0 ),//
                     ),
   'glpi_contracts' => array(array('from' => 'recursive','to' => 'is_recursive', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'deleted', 'to' => 'is_deleted', 'default' =>0 ),//
                           array('from' => 'monday', 'to' => 'use_monday', 'default' =>0 ),//
                           array('from' => 'saturday', 'to' => 'use_saturday', 'default' =>0 ),//
                     ),
   'glpi_devicescontrols' => array(array('from' => 'raid','to' => 'is_raid', 'default' =>0, 'noindex'=>true ),//
                     ),
   'glpi_devicesdrives' => array(array('from' => 'is_writer','to' => 'is_writer', 'default' =>1, 'noindex'=>true ),//
                     ),
   'glpi_devicespowersupplies' => array(array('from' => 'atx','to' => 'is_atx', 'default' =>1, 'noindex'=>true ),//
                     ),
   'glpi_documents' => array(array('from' => 'recursive','to' => 'is_recursive', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'deleted', 'to' => 'is_deleted', 'default' =>0 ),//
                     ),
   'glpi_documentstypes' => array(array('from' => 'upload','to' => 'is_uploadable', 'default' =>1, ),//
                     ),
   'glpi_groups' => array(array('from' => 'recursive','to' => 'is_recursive', 'default' =>0, 'noindex'=>true ),//
                     ),
   'glpi_knowbaseitems' => array(array('from' => 'recursive','to' => 'is_recursive', 'default' =>1, 'noindex'=>true ),//
                           array('from' => 'faq', 'to' => 'is_faq', 'default' =>0 ),//
                     ),
   'glpi_links' => array(array('from' => 'recursive','to' => 'is_recursive', 'default' =>1, 'noindex'=>true ),//
                     ),
   'glpi_monitors' => array(array('from' => 'deleted', 'to' => 'is_deleted', 'default' =>0 ),//
                        array('from' => 'is_template', 'to' => 'is_template', 'default' =>0,'noindex'=>true  ),//
                           array('from' => 'is_global', 'to' => 'is_global', 'default' =>0,'noindex'=>true  ),//
                           array('from' => 'flags_micro', 'to' => 'have_micro', 'default' =>0,'noindex'=>true  ),//
                           array('from' => 'flags_speaker', 'to' => 'have_speaker', 'default' =>0,'noindex'=>true  ),//
                           array('from' => 'flags_subd', 'to' => 'have_subd', 'default' =>0,'noindex'=>true  ),//
                           array('from' => 'flags_bnc', 'to' => 'have_bnc', 'default' =>0,'noindex'=>true  ),//
                           array('from' => 'flags_dvi', 'to' => 'have_dvi', 'default' =>0,'noindex'=>true  ),//
                           array('from' => 'flags_pivot', 'to' => 'have_pivot', 'default' =>0,'noindex'=>true  ),//

                     ),
   'glpi_networkequipments' => array(array('from' => 'recursive','to' => 'is_recursive', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'deleted', 'to' => 'is_deleted', 'default' =>0 ),//
                           array('from' => 'is_template', 'to' => 'is_template', 'default' =>0,'noindex'=>true  ),//
                     ),
   'glpi_ocslinks' => array(array('from' => 'auto_update','to' => 'use_auto_update', 'default' =>1),//
                     ),
   'glpi_ocsservers' => array(array('from' => 'import_periph','to' => 'import_periph', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_monitor','to' => 'import_monitor', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_software','to' => 'import_software', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_printer','to' => 'import_printer', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_general_name','to' => 'import_general_name', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_general_os','to' => 'import_general_os', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_general_serial','to' => 'import_general_serial', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_general_model','to' => 'import_general_model', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_general_enterprise','to' => 'import_general_manufacturer', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_general_type','to' => 'import_general_type', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_general_domain','to' => 'import_general_domain', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_general_contact','to' => 'import_general_contact', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_general_comments','to' => 'import_general_comment', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_device_processor','to' => 'import_device_processor', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_device_memory','to' => 'import_device_memory', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_device_hdd','to' => 'import_device_hdd', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_device_iface','to' => 'import_device_iface', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_device_gfxcard','to' => 'import_device_gfxcard', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_device_sound','to' => 'import_device_sound', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_device_drives','to' => 'import_device_drive', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_device_ports','to' => 'import_device_port', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_registry','to' => 'import_registry', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_os_serial','to' => 'import_os_serial', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_ip','to' => 'import_ip', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_disk','to' => 'import_disk', 'default' =>0,'noindex'=>true),//
                        array('from' => 'import_monitor_comments','to' => 'import_monitor_comment', 'default' =>0,'noindex'=>true),//
                        array('from' => 'glpi_link_enabled','to' => 'is_glpi_link_enabled', 'default' =>0,'noindex'=>true),//
                        array('from' => 'link_ip','to' => 'use_ip_to_link', 'default' =>0,'noindex'=>true),//
                        array('from' => 'link_name','to' => 'use_name_to_link', 'default' =>0,'noindex'=>true),//
                        array('from' => 'link_mac_address','to' => 'use_mac_to_link', 'default' =>0,'noindex'=>true),//
                        array('from' => 'link_serial','to' => 'use_serial_to_link', 'default' =>0,'noindex'=>true),//
                     ),
   'glpi_peripherals' => array(array('from' => 'deleted','to' => 'is_deleted', 'default' =>0),//
                           array('from' => 'is_template', 'to' => 'is_template', 'default' =>0,'noindex'=>true  ),//
                           array('from' => 'is_global', 'to' => 'is_global', 'default' =>0,'noindex'=>true  ),//
                     ),
   'glpi_phones' => array(array('from' => 'deleted','to' => 'is_deleted', 'default' =>0),//
                           array('from' => 'is_template', 'to' => 'is_template', 'default' =>0,'noindex'=>true  ),//
                           array('from' => 'is_global', 'to' => 'is_global', 'default' =>0,'noindex'=>true  ),//
                           array('from' => 'flags_hp', 'to' => 'have_hp', 'default' =>0,'noindex'=>true  ),//
                           array('from' => 'flags_casque', 'to' => 'have_headset', 'default' =>0,'noindex'=>true  ),//
                     ),
   'glpi_printers' => array(array('from' => 'recursive','to' => 'is_recursive', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'deleted', 'to' => 'is_deleted', 'default' =>0 ),//
                           array('from' => 'is_template', 'to' => 'is_template', 'default' =>0,'noindex'=>true  ),//
                           array('from' => 'is_global', 'to' => 'is_global', 'default' =>0,'noindex'=>true  ),//
                           array('from' => 'flags_usb', 'to' => 'have_usb', 'default' =>0,'noindex'=>true  ),//
                           array('from' => 'flags_par', 'to' => 'have_parallel', 'default' =>0,'noindex'=>true  ),//
                           array('from' => 'flags_serial', 'to' => 'have_serial', 'default' =>0,'noindex'=>true  ),//
                     ),
   'glpi_profiles_users' => array(array('from' => 'recursive','to' => 'is_recursive', 'default' =>1),//
                           array('from' => 'dynamic','to' => 'is_dynamic', 'default' =>0),//
                     ),
   'glpi_profiles' => array(array('from' => 'is_default','to' => 'is_default', 'default' =>0),//
                     ),
   'glpi_reminders' => array(array('from' => 'private', 'to' => 'is_private', 'default' =>1 ),//
                           array('from' => 'recursive','to' => 'is_recursive', 'default' =>0 ),//
                           array('from' => 'rv','to' => 'is_planned', 'default' =>0 ),//
                     ),
   'glpi_reservationsitems' => array(array('from' => 'active','to' => 'is_active', 'default' =>1),//
                     ),
   'glpi_rules' => array(array('from' => 'active','to' => 'is_active', 'default' =>1),//
                     ),
   'glpi_suppliers' => array(array('from' => 'recursive','to' => 'is_recursive', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'deleted', 'to' => 'is_deleted', 'default' =>0 ),//
                     ),
   'glpi_softwares' => array(array('from' => 'recursive','to' => 'is_recursive', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'deleted', 'to' => 'is_deleted', 'default' =>0 ),//
                           array('from' => 'helpdesk_visible', 'to' => 'is_helpdesk_visible', 'default' =>1 ),//
                           array('from' => 'is_template', 'to' => 'is_template', 'default' =>0,'noindex'=>true  ),//
                           array('from' => 'is_update', 'to' => 'is_update', 'default' =>0,'noindex'=>true ),//
                     ),
   'glpi_softwareslicenses' => array(array('from' => 'recursive','to' => 'is_recursive', 'default' =>0, 'noindex'=>true ),//
                     ),
   'glpi_tickets' => array(array('from' => 'emailupdates', 'to' => 'use_email_notification', 'default' =>0, 'noindex'=>true  ),//
                     ),
   'glpi_ticketsfollowups' => array(array('from' => 'private', 'to' => 'is_private', 'default' =>0 ),//
                     ),
   'glpi_users' => array(array('from' => 'deleted','to' => 'is_deleted', 'default' =>0),//
                        array('from' => 'active','to' => 'is_active', 'default' =>1),//
                        array('from' => 'jobs_at_login', 'to' => 'show_jobs_at_login', 'default' =>0, 'noindex'=>true),//
                        array('from' => 'followup_private', 'to' => 'default_followup_private', 'default' =>0, 'noindex'=>true ),//
                        array('from' => 'expand_soft_categorized', 'to' => 'is_categorized_soft_expanded', 'default' =>1, 'noindex'=>true ),//
                        array('from' => 'expand_soft_not_categorized', 'to' => 'is_not_categorized_soft_expanded', 'default' =>1, 'noindex'=>true ),//
                        array('from' => 'flat_dropdowntree', 'to' => 'use_flat_dropdowntree', 'default' =>0, 'noindex'=>true ),//
                        array('from' => 'view_ID', 'to' => 'is_ids_visible', 'default' =>0, 'noindex'=>true ),//
                     ),

   );

   foreach ($boolfields as $table => $tab) {
      foreach ($tab as $update){
         $newname=$update['to'];
         $oldname=$update['from'];
         $doindex=true;
         if (isset($update['noindex']) && $update['noindex']){
            $doindex=false;
         }
         // Rename field
         if (FieldExists($table, $oldname)) {
            $default_value=0;
            if (isset($update['default']) ) {
               $default_value=$update['default'];
            }
            // Manage NULL fields
            $query="UPDATE `$table` SET `$oldname`=0 WHERE `$oldname` IS NULL ;";
            $DB->query($query) or die("0.80 prepare datas for update $oldname to $newname in $table " . $LANG['update'][90] . $DB->error());

            // Manage not zero values
            $query="UPDATE `$table` SET `$oldname`=1 WHERE `$oldname` <> 0; ";
            $DB->query($query) or die("0.80 prepare datas for update $oldname to $newname in $table " . $LANG['update'][90] . $DB->error());

            $query="ALTER TABLE `$table` CHANGE `$oldname` `$newname` TINYINT( 1 ) NOT NULL DEFAULT '$default_value';";
            $DB->query($query) or die("0.80 rename $oldname to $newname in $table " . $LANG['update'][90] . $DB->error());
         } else {
            echo "<div class='red'><p>Error : $table.$oldname does not exist.</p></div>";
         }
         // If do index : delete old one / create new one
         if ($doindex){
            if (isIndex($table, $oldname)) {
               $query="ALTER TABLE `$table` DROP INDEX `$oldname`;";
               $DB->query($query) or die("0.80 drop index $oldname in $table " . $LANG['update'][90] . $DB->error());
            }
            if (!isIndex($table, $newname)) {
               $query="ALTER TABLE `$table` ADD INDEX `$newname` (`$newname`);";
               $DB->query($query) or die("0.80 create index $newname in $table " . $LANG['update'][90] . $DB->error());
            }
         }
      }
   }
   displayMigrationMessage("080", $LANG['update'][141] . ' - Clean DB : update text fields'); // Updating schema

   $textfields=array(
   'comments' => array('to' => 'comment',
                           'tables' => array('glpi_cartridgesitems','glpi_computers',
                                 'glpi_consumablesitems','glpi_contacts','glpi_contracts',
                                 'glpi_documents','glpi_autoupdatesystems','glpi_budgets',
                                 'glpi_cartridgesitemstypes','glpi_devicescasestypes','glpi_consumablesitemstypes',
                                 'glpi_contactstypes','glpi_contractstypes','glpi_domains',
                                 'glpi_supplierstypes','glpi_filesystems','glpi_networkequipmentsfirmwares',
                                 'glpi_networkinterfaces','glpi_interfaces',
                                 'glpi_knowbaseitemscategories','glpi_softwareslicensestypes','glpi_locations',
                                 'glpi_manufacturers','glpi_computersmodels','glpi_monitorsmodels',
                                 'glpi_networkequipmentsmodels','glpi_peripheralsmodels','glpi_phonesmodels',
                                 'glpi_printersmodels','glpi_netpoints','glpi_networks',
                                 'glpi_operatingsystems','glpi_operatingsystemsservicepacks','glpi_operatingsystemsversions',
                                 'glpi_phonespowersupplies','glpi_devicesmemoriestypes','glpi_documentscategories',
                                 'glpi_softwarescategories','glpi_states','glpi_ticketscategories',
                                 'glpi_userstitles','glpi_userscategories','glpi_vlans',
                                 'glpi_suppliers','glpi_entities','glpi_groups',
                                 'glpi_infocoms','glpi_monitors','glpi_phones',
                                 'glpi_printers','glpi_peripherals','glpi_networkequipments',
                                 'glpi_reservationsitems','glpi_rules','glpi_softwares',
                                 'glpi_softwareslicenses','glpi_softwaresversions','glpi_computerstypes',
                                 'glpi_monitorstypes','glpi_networkequipmentstypes','glpi_peripheralstypes',
                                 'glpi_phonestypes','glpi_printerstypes','glpi_users',),
                     ));
   foreach ($textfields as $oldname => $tab) {
      $newname=$tab['to'];
      foreach ($tab['tables'] as $table){
         // Rename field
         if (FieldExists($table, $oldname)) {

            $query="ALTER TABLE `$table` CHANGE `$oldname` `$newname` TEXT NULL DEFAULT NULL  ";
            $DB->query($query) or die("0.80 rename $oldname to $newname in $table " . $LANG['update'][90] . $DB->error());
         } else {
            echo "<div class='red'><p>Error : $table.$oldname does not exist.</p></div>";
         }
      }
   }

   displayMigrationMessage("080", $LANG['update'][141] . ' - Clean DB : post actions after renaming'); // Updating schema

   // Change defaults store values :
   if (FieldExists('glpi_softwares', 'sofwtares_id')) {
      $query="UPDATE glpi_softwares SET sofwtares_id=0 WHERE sofwtares_id < 0";
      $DB->query($query) or die("0.80 update default value of sofwtares_id in glpi_softwares " . $LANG['update'][90] . $DB->error());
   }
   if (FieldExists('glpi_users', 'authtype')) {
      $query="UPDATE glpi_users SET authtype=0 WHERE authtype < 0";
      $DB->query($query) or die("0.80 update default value of authtype in glpi_users " . $LANG['update'][90] . $DB->error());
   }
   if (FieldExists('glpi_users', 'auths_id')) {
      $query="UPDATE glpi_users SET auths_id=0 WHERE auths_id < 0";
      $DB->query($query) or die("0.80 update default value of auths_id in glpi_users " . $LANG['update'][90] . $DB->error());
   }
   // Update glpi_ocsadmininfoslinks table for new field name
   if (FieldExists('glpi_ocsadmininfoslinks', 'glpi_column')) {
      $query="UPDATE glpi_ocsadmininfoslinks SET glpi_column='locations_id' WHERE glpi_column = 'location'";
      $DB->query($query) or die("0.80 update value of glpi_column in glpi_ocsadmininfoslinks " . $LANG['update'][90] . $DB->error());
      $query="UPDATE glpi_ocsadmininfoslinks SET glpi_column='networks_id' WHERE glpi_column = 'network'";
      $DB->query($query) or die("0.80 update value of glpi_column in glpi_ocsadmininfoslinks " . $LANG['update'][90] . $DB->error());
      $query="UPDATE glpi_ocsadmininfoslinks SET glpi_column='groups_id' WHERE glpi_column = 'FK_groups'";
      $DB->query($query) or die("0.80 update value of glpi_column in glpi_ocsadmininfoslinks " . $LANG['update'][90] . $DB->error());
   }

   // Update tracking bookmarks for new columns fields
   if (FieldExists('glpi_bookmarks', 'query')) {
      $olds = array("category", "type", "author","assign",
               "assign_group","assign_ent","recipient");
   
      $news   = array("ticketscategories_id", "itemtype", "ice users_id","users_id_assign",
               "groups_id_assign","suppliers_id_assign","users_id_recipient");

      foreach ($olds as $key => $val){
         $olds[$key]="&$val=";
      }
      foreach ($news as $key => $val){
         $news[$key]="&$val=";
      }

      $query="SELECT ID, query FROM glpi_bookmarks WHERE type=".BOOKMARK_SEARCH." AND itemtype=".TRACKING_TYPE.";";
      if ($result = $DB->query($query)){
         if ($DB->numrows($result)>0){
            while ($data = $DB->fetch_assoc($result)){
               $query2="UPDATE glpi_bookmarks SET query='".addslashes(str_replace($olds,$news,$data['query']))."' WHERE ID=".$data['ID'].";";
               $DB->query($query2) or die("0.80 update tracking bookmarks " . $LANG['update'][90] . $DB->error());
            }
         }
      }
      // All search
      $olds = array("deleted",);
   
      $news   = array("is_deleted",);
      foreach ($olds as $key => $val){
         $olds[$key]="&$val=";
      }
      foreach ($news as $key => $val){
         $news[$key]="&$val=";
      }
      $query="SELECT ID, query FROM glpi_bookmarks WHERE type=".BOOKMARK_SEARCH." ;";
      if ($result = $DB->query($query)){
         if ($DB->numrows($result)>0){
            while ($data = $DB->fetch_assoc($result)){
               $query2="UPDATE glpi_bookmarks SET query='".addslashes(str_replace($olds,$news,$data['query']))."' WHERE ID=".$data['ID'].";";
               $DB->query($query2) or die("0.80 update all bookmarks " . $LANG['update'][90] . $DB->error());
            }
         }
      }

   }

   //// Upgrade rules datas
   // For RULE_AFFECT_RIGHTS
   $changes[RULE_AFFECT_RIGHTS]=array('FK_entities'=>'entities_id', 'FK_profiles'=>'profiles_id',
                        'recursive'=>'is_recursive','active'=>'is_active');
   // For RULE_DICTIONNARY_SOFTWARE
   $changes[RULE_DICTIONNARY_SOFTWARE]=array('helpdesk_visible'=>'is_helpdesk_visible');
   // For RULE_OCS_AFFECT_COMPUTER
   $changes[RULE_OCS_AFFECT_COMPUTER]=array('FK_entities'=>'entities_id');
   // For RULE_SOFTWARE_CATEGORY
   $changes[RULE_SOFTWARE_CATEGORY]=array('category'=>'softwarescategories_id','comment'=>'comment');
   // For RULE_TRACKING_AUTO_ACTION
   $changes[RULE_TRACKING_AUTO_ACTION]=array('category'=>'ticketscategories_id',
                           'author'=>'users_id','author_location'=>'users_locations',
                           'FK_group'=>'groups_id','assign'=>'users_id_assign',
                           'assign_group'=>'groups_id_assign','device_type'=>'itemtype',
                           'FK_entities'=>'entities_id');
   foreach ($changes as $ruletype => $tab){
      // Get rules
      $query = "SELECT GROUP_CONCAT(ID) FROM glpi_rules WHERE sub_type=".$ruletype." GROUP BY sub_type;";
      if ($result = $DB->query($query)){
         if ($DB->numrows($result)>0){
            // Get rule string
            $rules=$DB->result($result,0,0);
            // Update actions
            foreach ($tab as $old => $new){
               $query = "UPDATE glpi_rulesactions SET field='$new' WHERE field='$old' AND rules_id IN ($rules);";
               $DB->query($query) or die("0.80 update datas for rules actions " . $LANG['update'][90] . $DB->error());
            }
            // Update criterias
            foreach ($tab as $old => $new){
               $query = "UPDATE glpi_rulescriterias SET criteria='$new' WHERE criteria='$old' AND rules_id IN ($rules);";
               $DB->query($query) or die("0.80 update datas for rules criterias " . $LANG['update'][90] . $DB->error());
            }
         }
      }
   }


   displayMigrationMessage("080", $LANG['update'][141] . ' - glpi_configs'); // Updating schema
	if (FieldExists('glpi_configs', 'license_deglobalisation')) {
		$query="ALTER TABLE `glpi_configs` DROP `license_deglobalisation`;";
      $DB->query($query) or die("0.80 alter clean glpi_configs table " . $LANG['update'][90] . $DB->error());
	}	

   displayMigrationMessage("080", $LANG['update'][141] . ' - glpi_mailcollectors'); // Updating schema

   // Change mailgate search pref : add active
	if (!FieldExists("glpi_mailcollectors", "is_active")) {
		$query = "ALTER TABLE `glpi_mailcollectors` ADD `is_active` tinyint( 1 ) NOT NULL DEFAULT '1' ;";
      $DB->query($query) or die("0.80 add is_active in glpi_mailcollectors " . $LANG['update'][90] . $DB->error());
	}

   // Change mailgate search pref : add ative
	$query="SELECT DISTINCT users_id FROM glpi_displayprefs WHERE itemtype=".MAILGATE_TYPE.";";
	if ($result = $DB->query($query)){
		if ($DB->numrows($result)>0){
			while ($data = $DB->fetch_assoc($result)){
				$query="SELECT max(rank) FROM glpi_displayprefs WHERE users_id='".$data['users_id']."' AND itemtype=".MAILGATE_TYPE.";";
				$result=$DB->query($query);
				$rank=$DB->result($result,0,0);
				$rank++;
				$query="SELECT * FROM glpi_displayprefs WHERE users_id='".$data['users_id']."' AND num=2 AND itemtype=".MAILGATE_TYPE.";";
				if ($result2=$DB->query($query)){
					if ($DB->numrows($result2)==0){
						$query="INSERT INTO glpi_displayprefs (itemtype ,`num` ,`rank` ,users_id) VALUES ('".MAILGATE_TYPE."', '2', '".$rank++."', '".$data['users_id']."');";
						$DB->query($query);
					}
				}
			}
		}
	}
   
	
   displayMigrationMessage("080", $LANG['update'][141] . ' - glpi_rulescachesoftwares'); // Updating schema
	
	if (FieldExists("glpi_rulescachesoftwares","ignore_ocs_import")){
		$query = "ALTER TABLE `glpi_rulescachesoftwares` CHANGE `ignore_ocs_import` `ignore_ocs_import` CHAR( 1 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ";
      $DB->query($query) or die("0.80 alter table glpi_rulescachesoftwares " . $LANG['update'][90] . $DB->error());
	}
	if (!FieldExists("glpi_rulescachesoftwares","helpdesk_visible")){
		$query = "ALTER TABLE `glpi_rulescachesoftwares` ADD `helpdesk_visible` CHAR( 1 ) NULL ";
      $DB->query($query) or die("0.80 add helpdesk_visible index in glpi_rulescachesoftwares " . $LANG['update'][90] . $DB->error());
	}

   displayMigrationMessage("080", $LANG['update'][141] . ' - glpi_entities'); // Updating schema
   
   if (!FieldExists("glpi_entities","cache_sons")){
      $query = "ALTER TABLE `glpi_entities` ADD `cache_sons` LONGTEXT NOT NULL ; ";
      $DB->query($query) or die("0.80 add cache_sons field in glpi_entities " . $LANG['update'][90] . $DB->error());
   }
   
   if (!FieldExists("glpi_entities","cache_ancestors")){
      $query = "ALTER TABLE `glpi_entities` ADD `cache_ancestors` LONGTEXT NOT NULL ; ";
      $DB->query($query) or die("0.80 add cache_ancestors field in glpi_entities " . $LANG['update'][90] . $DB->error());
   }


   displayMigrationMessage("080", $LANG['update'][141] . ' - glpi_configs'); // Updating schema

   if (FieldExists("glpi_configs","use_cache")){
      $query = "ALTER TABLE `glpi_configs`  DROP `use_cache`;";
      $DB->query($query) or die("0.80 drop use_cache in glpi_configs " . $LANG['update'][90] . $DB->error());
   }

   if (FieldExists("glpi_configs","cache_max_size")){
      $query = "ALTER TABLE `glpi_configs`  DROP `cache_max_size`;";
      $DB->query($query) or die("0.80 drop cache_max_size in glpi_configs " . $LANG['update'][90] . $DB->error());
   }

	if (!FieldExists("glpi_configs","request_type")){
		$query = "ALTER TABLE `glpi_configs` ADD `request_type` INT( 1 ) NOT NULL DEFAULT 1";
      $DB->query($query) or die("0.80 add request_type index in glpi_configs " . $LANG['update'][90] . $DB->error());
	}

	if (!FieldExists("glpi_users","request_type")){
		$query = "ALTER TABLE `glpi_users` ADD `request_type` INT( 1 ) NULL";
      $DB->query($query) or die("0.80 add request_type index in glpi_users " . $LANG['update'][90] . $DB->error());
	}

	if (!FieldExists("glpi_configs","use_noright_users_add")){
		$query = "ALTER TABLE `glpi_configs` ADD `use_noright_users_add` tinyint( 1 ) NOT NULL DEFAULT '1'";
      $DB->query($query) or die("0.80 add use_noright_users_add index in glpi_configs " . $LANG['update'][90] . $DB->error());
	}

	displayMigrationMessage("080", $LANG['update'][141] . ' - glpi_budgets'); // Updating schema

	if (!FieldExists("glpi_profiles","budget")) {
		$query = "ALTER TABLE `glpi_profiles` ADD `budget` VARCHAR( 1 ) NULL ";
		$DB->query($query) or die("0.80 add budget index in glpi_profiles" . $LANG['update'][90] . $DB->error());

		$query = "UPDATE `glpi_profiles` SET `budget`='w' WHERE `name` IN ('super-admin','admin')";
		$DB->query($query) or die("0.80 add budget write right to super-admin and admin profiles" . $LANG['update'][90] . $DB->error());

		$query = "UPDATE `glpi_profiles` SET `budget`='r' WHERE `name`='normal'";
		$DB->query($query) or die("0.80 add budget write right to super-admin and admin profiles" . $LANG['update'][90] . $DB->error());

	}


   if (!FieldExists("glpi_budgets","is_recursive")) {
      $query = "ALTER TABLE `glpi_budgets` ADD `is_recursive` tinyint(1) NOT NULL DEFAULT '0' AFTER `name`";
      $DB->query($query) or die("0.80 add is_recursive field in glpi_budgets" . $LANG['update'][90] . $DB->error());
   }
   if (!FieldExists("glpi_budgets","entities_id")) {
         $query = "ALTER TABLE `glpi_budgets` ADD `entities_id` int(11) NOT NULL default '0' AFTER `name`;";
         $DB->query($query) or die("0.80 add entities_id field in glpi_budgets" . $LANG['update'][90] . $DB->error());
   }
   if (!isIndex("glpi_budgets","entities_id")) {
      $query="ALTER TABLE `glpi_budgets` ADD INDEX `entities_id` (`entities_id`);";
      $DB->query($query) or die("0.80 create index entities_id in glpi_budgets " . $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists("glpi_budgets","is_deleted")) {
      $query = "ALTER TABLE `glpi_budgets` ADD `is_deleted` tinyint(1) NOT NULL DEFAULT '0'";
      $DB->query($query) or die("0.80 add is_deleted field in glpi_budgets" . $LANG['update'][90] . $DB->error());
   }
   if (!isIndex("glpi_budgets","is_deleted")) {
      $query="ALTER TABLE `glpi_budgets` ADD INDEX `is_deleted` (`is_deleted`);";
      $DB->query($query) or die("0.80 create index is_deleted in glpi_budgets " . $LANG['update'][90] . $DB->error());
   }
   if (!FieldExists("glpi_budgets","begin_date")) {
      $query = "ALTER TABLE `glpi_budgets` ADD `begin_date` DATE NULL";
      $DB->query($query) or die("0.80 add begin_date field in glpi_budgets" . $LANG['update'][90] . $DB->error());
   }
   if (!FieldExists("glpi_budgets","end_date")) {
      $query = "ALTER TABLE `glpi_budgets` ADD `end_date` DATE NULL";
      $DB->query($query) or die("0.80 add end_date field in glpi_budgets" . $LANG['update'][90] . $DB->error());
   }
   if (!FieldExists("glpi_budgets","value")) {
      $query = "ALTER TABLE `glpi_budgets` ADD `value` DECIMAL( 20, 4 )  NOT NULL default '0.0000'";
      $DB->query($query) or die("0.80 add value field in glpi_budgets" . $LANG['update'][90] . $DB->error());
   }
   if (!FieldExists("glpi_budgets","is_template")) {
      $query = "ALTER TABLE `glpi_budgets` ADD `is_template` tinyint(1) NOT NULL default '0'";
      $DB->query($query) or die("0.80 add is_template field in glpi_budgets" . $LANG['update'][90] . $DB->error());
   }
   if (!isIndex("glpi_budgets","is_template")) {
      $query="ALTER TABLE `glpi_budgets` ADD INDEX `is_template` (`is_template`);";
      $DB->query($query) or die("0.80 create index is_template in glpi_budgets " . $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists("glpi_budgets","tplname")) {
      $query = "ALTER TABLE `glpi_budgets`  ADD `tplname` varchar(255) default NULL";
      $DB->query($query) or die("0.80 add tplname field in glpi_budgets" . $LANG['update'][90] . $DB->error());
   }

	// Display "Work ended." message - Keep this as the last action.
   displayMigrationMessage("080"); // End
}
?>
