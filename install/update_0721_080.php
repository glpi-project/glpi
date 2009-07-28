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
      'glpi_dropdown_user_types'          => 'glpi_userstypes',
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
                  echo "A backup have been done to backup_NAME.</b></p>";
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
                           'noindex' => array(),
                           'tables' => array('glpi_tickets')),
                     ),
   'author' => array(array('to' => 'users_id',
                           'noindex' => array(),
                           'tables' => array('glpi_ticketsfollowups','glpi_knowbaseitems',
                              'glpi_tickets')),
                     ),
   'auto_update' => array(array('to' => 'autoupdatesystems_id',
                           'noindex' => array(''),
                           'tables' => array('glpi_computers',)),
                     ),
   'computer' => array(array('to' => 'items_id',
                           'noindex' => array('glpi_tickets'),
                           'tables' => array('glpi_tickets')),
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
                                       'noindex' => array(),
                                       'tables' => array('glpi_logs')),
                     ),
   'domain' => array(array('to' => 'domains_id',
                           'noindex' => array(''),
                           'tables' => array('glpi_computers','glpi_networkequipments',
                              'glpi_printers')),
                     ),
   'FK_computers' => array(array('to' => 'computers_id',
                           'noindex' => array(''),
                           'tables' => array('glpi_computers_devices','glpi_computersdisks',
                                       'glpi_softwareslicenses',)),
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
                              'glpi_profiles_users',)),
                     ),
   'FK_filesystems' => array(array('to' => 'filesystems_id',
                           'noindex' => array(''),
                           'tables' => array('glpi_computersdisks',)),
                     ),
   'FK_glpi_cartridges_type' => array(array('to' => 'cartridgesitems_id',
                           'noindex' => array(''),
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
                     'noindex' => array(''),
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
                           'noindex' => array(''),
                           'tables' => array('glpi_cartridges',)),
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
   'id_assign' => array(array('to' => 'users_id',
                           'noindex' => array(),
                           'tables' => array('glpi_ticketsplannings')),
                     ),
   'id_device' => array(array('to' => 'items_id',
                           'noindex' => array('glpi_reservationsitems'),
                           'tables' => array('glpi_reservationsitems')),
                     ),
   'id_user' => array(array('to' => 'users_id',
                           'noindex' => array(),
                           'tables' => array('glpi_consumables','glpi_reservations')),
                     ),
   'location' => array(array('to' => 'locations_id',
                           'noindex' => array('glpi_netpoints'),
                           'tables' => array('glpi_cartridgesitems','glpi_computers',
                              'glpi_consumablesitems','glpi_netpoints','glpi_monitors',
                              'glpi_networkequipments','glpi_peripherals','glpi_phones',
                              'glpi_printers','glpi_users','glpi_softwares')),
                     ),
   'model' => array(array('to' => 'computersmodels_id',
                           'noindex' => array(''),
                           'tables' => array('glpi_computers')),
                     array('to' => 'monitorsmodels_id',
                           'noindex' => array(''),
                           'tables' => array('glpi_monitors')),
                     array('to' => 'networkequipmentsmodels_id',
                           'noindex' => array(''),
                           'tables' => array('glpi_networkequipments')),
                     array('to' => 'peripheralsmodels_id',
                           'noindex' => array(''),
                           'tables' => array('glpi_peripherals')),
                     array('to' => 'phonesmodels_id_id',
                           'noindex' => array(''),
                           'tables' => array('glpi_phones')),
                     array('to' => 'printersmodels_id_id',
                           'noindex' => array(''),
                           'tables' => array('glpi_printers')),
                     ),
   'network' => array(array('to' => 'networks_id',
                           'noindex' => array(''),
                           'tables' => array('glpi_computers','glpi_networkequipments',
                              'glpi_printers')),
                     ),
   'on_device' => array(array('to' => 'items_id',
                           'noindex' => array('glpi_networkports'),
                           'tables' => array('glpi_networkports')),
                     ),
   'os' => array(array('to' => 'operatingsystems_id',
                           'noindex' => array(''),
                           'tables' => array('glpi_computers',)),
                     ),
   'os_sp' => array(array('to' => 'operatingsystemsservicepacks_id',
                           'noindex' => array(''),
                           'tables' => array('glpi_computers',)),
                     ),
   'os_version' => array(array('to' => 'operatingsystemsversions_id',
                           'noindex' => array(''),
                           'tables' => array('glpi_computers',)),
                     ),
   'parentID' => array(array('to' => 'knowbaseitemscategories_id',
                           'noindex' => array('glpi_knowbaseitemscategories'),
                           'tables' => array('glpi_knowbaseitemscategories')),
                        array('to' => 'locations_id',
                           'noindex' => array(''),
                           'tables' => array('glpi_locations')),
                        array('to' => 'ticketscategories_id',
                           'noindex' => array(''),
                           'tables' => array('glpi_ticketscategories')),
                        array('to' => 'entities_id',
                           'noindex' => array(''),
                           'tables' => array('glpi_entities')),
                     ),
   'platform' => array(array('to' => 'operatingsystems_id',
                           'noindex' => array(''),
                           'tables' => array('glpi_softwares',)),
                     ),
   'recipient' => array(array('to' => 'users_id_recipient',
                           'noindex' => array(),
                           'tables' => array('glpi_tickets')),
                     ),
   'server_id' => array(array('to' => 'authldaps_id',
                           'noindex' => array(''),
                           'tables' => array('glpi_authldapsreplicates')),
                     ),
   'tech_num' => array(array('to' => 'users_id_tech',
                              'noindex' => array(),
                              'tables' => array('glpi_cartridgesitems','glpi_computers',
                              'glpi_consumablesitems','glpi_monitors',
                              'glpi_networkequipments','glpi_peripherals','glpi_phones',
                              'glpi_printers','glpi_softwares')),
                     ),
   'type' => array(array('to' => 'cartridgesitemstypes_id','noindex' => array(''),
                           'tables' => array('glpi_cartridgesitems')),
                  array('to' => 'computerstypes_id', 'noindex' => array(''),
                           'tables' => array('glpi_computers')),
                  array('to' => 'consumablesitemstypes_id','noindex' => array(''),
                           'tables' => array('glpi_consumablesitems')),
                  array('to' => 'contactstypes_id', 'noindex' => array(''),
                           'tables' => array('glpi_contacts')),
                  array('to' => 'devicescasestypes_id', 'noindex' => array(''),
                           'tables' => array('glpi_devicescases')),
                  array('to' => 'devicesmemoriestypes_id', 'noindex' => array(''),
                           'tables' => array('glpi_devicesmemories')),
                  array('to' => 'supplierstypes_id', 'noindex' => array(''),
                           'tables' => array('glpi_suppliers')),
                  array('to' => 'monitorstypes_id', 'noindex' => array(''),
                           'tables' => array('glpi_monitors')),
                  array('to' => 'networkequipmentstypes_id', 'noindex' => array(''),
                           'tables' => array('glpi_networkequipments')),
                  array('to' => 'peripheralstypes_id', 'noindex' => array(''),
                           'tables' => array('glpi_peripherals')),
                  array('to' => 'phonestypes_id', 'noindex' => array(''),
                           'tables' => array('glpi_phones')),
                  array('to' => 'printerstypes_id', 'noindex' => array(''),
                           'tables' => array('glpi_printers')),
                  array('to' => 'softwareslicensestypes_id', 'noindex' => array(''),
                           'tables' => array('glpi_softwareslicenses')),
                  array('to' => 'userstypes_id', 'noindex' => array(''),
                           'tables' => array('glpi_users')),
                  array('to' => 'itemtype', 'noindex' => array('glpi_computers_items'),
                           'tables' => array('glpi_computers_items','glpi_displayprefs')),
                     ),
   );

   foreach ($foreignkeys as $oldname => $newnames) {
      foreach ($newnames as $tab){
         $newname=$tab['to'];
         foreach ($tab['tables'] as $table){
            $doindex=true;
            if (in_array($table,$tab['noindex'])){
               $doindex=false;
            }
            // Rename field
            if (FieldExists($table, $oldname)) {
               $query="ALTER TABLE `$table` CHANGE `$oldname` `$newname` INT( 11 ) NOT NULL DEFAULT '0' ";
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

   /// TODO Update glpi_ocsadmininfoslinks table for  : location -> locations_id network -> networks_id
   /// TODO Update tracking bookmarks for new columns fields
   /// TODO See if type -> itemtype need update bookmarks
   /// TODO upgrade XXX_update in ocs_links
   


   displayMigrationMessage("080", $LANG['update'][141] . ' - glpi_configs'); // Updating schema
	if (FieldExists('glpi_configs', 'license_deglobalisation')) {
		$query="ALTER TABLE `glpi_configs` DROP `license_deglobalisation`;";
      $DB->query($query) or die("0.80 alter clean glpi_configs table " . $LANG['update'][90] . $DB->error());
	}	

   displayMigrationMessage("080", $LANG['update'][141] . ' - glpi_mailcollectors'); // Updating schema

	if (!FieldExists("glpi_mailcollectors", "active")) {
		$query = "ALTER TABLE `glpi_mailcollectors` ADD `active` INT( 1 ) NOT NULL DEFAULT '1' ;";
      $DB->query($query) or die("0.80 add active in glpi_mailcollectors " . $LANG['update'][90] . $DB->error());
	}

   // Change mailgate search pref : add ative
	$query="SELECT DISTINCT FK_users FROM glpi_display WHERE type=".MAILGATE_TYPE.";";
	if ($result = $DB->query($query)){
		if ($DB->numrows($result)>0){
			while ($data = $DB->fetch_assoc($result)){
				$query="SELECT max(rank) FROM glpi_display WHERE FK_users='".$data['FK_users']."' AND type=".MAILGATE_TYPE.";";
				$result=$DB->query($query);
				$rank=$DB->result($result,0,0);
				$rank++;
				$query="SELECT * FROM glpi_display WHERE FK_users='".$data['FK_users']."' AND num=2 AND type=".MAILGATE_TYPE.";";
				if ($result2=$DB->query($query)){
					if ($DB->numrows($result2)==0){
						$query="INSERT INTO glpi_display (`type` ,`num` ,`rank` ,`FK_users`) VALUES ('".MAILGATE_TYPE."', '2', '".$rank++."', '".$data['FK_users']."');";
						$DB->query($query);
					}
				}
			}
		}
	}
   
   displayMigrationMessage("080", $LANG['update'][141] . ' - glpi_device_xxxx'); // Updating schema
         

	if (FieldExists("glpi_devicescontrols", "interface")) {
		$query="ALTER TABLE `glpi_devicescontrols` CHANGE `interface` `FK_interface` INT( 11 ) NOT NULL DEFAULT '0'";
      $DB->query($query) or die("0.80 alter interface in glpi_devicescontrols " . $LANG['update'][90] . $DB->error());
		if (isIndex("glpi_devicescontrols", "interface")) {
			$query="ALTER TABLE `glpi_devicescontrols` DROP INDEX `interface`, ADD INDEX `FK_interface` ( `FK_interface` ) ";
         $DB->query($query) or die("0.80 alter interface index in glpi_devicescontrols " . $LANG['update'][90] . $DB->error());
		}
	}

	if (FieldExists("glpi_devicesharddrives", "interface")) {
		$query="ALTER TABLE `glpi_devicesharddrives` CHANGE `interface` `FK_interface` INT( 11 ) NOT NULL DEFAULT '0'";
      $DB->query($query) or die("0.80 alter interface in glpi_devicesharddrives " . $LANG['update'][90] . $DB->error());
		if (isIndex("glpi_devicesharddrives", "interface")) {
			$query="ALTER TABLE `glpi_devicesharddrives` DROP INDEX `interface`, ADD INDEX `FK_interface` ( `FK_interface` ) ";
			$DB->query($query) or die("0.v alter interface index in glpi_devicesharddrives " . $LANG['update'][90] . $DB->error());
		}
	}

	if (FieldExists("glpi_devicesdrives", "interface")) {
		$query="ALTER TABLE `glpi_devicesdrives` CHANGE `interface` `FK_interface` INT( 11 ) NOT NULL DEFAULT '0'";
      $DB->query($query) or die("0.80 alter interface in glpi_devicesdrives " . $LANG['update'][90] . $DB->error());
		if (isIndex("glpi_devicesdrives", "interface")) {
			$query="ALTER TABLE `glpi_devicesdrives` DROP INDEX `interface`, ADD INDEX `FK_interface` ( `FK_interface` ) ";
			$DB->query($query) or die("0.v alter interface index in glpi_devicesdrives " . $LANG['update'][90] . $DB->error());
		}
	}

	if (!isIndex("glpi_devicesgraphiccards", "FK_interface")) {
		$query="ALTER TABLE `glpi_devicesgraphiccards` ADD INDEX `FK_interface` ( `FK_interface` ) ";
      $DB->query($query) or die("0.80 add interface index in glpi_devicesgraphiccards " . $LANG['update'][90] . $DB->error());
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

	if (!FieldExists("glpi_configs","add_norights_users")){
		$query = "ALTER TABLE `glpi_configs` ADD `add_norights_users` INT( 1 ) NOT NULL DEFAULT '1'";
      $DB->query($query) or die("0.80 add add_norights_users index in glpi_configs " . $LANG['update'][90] . $DB->error());
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


   if (!FieldExists("glpi_budgets","recursive")) {
      $query = "ALTER TABLE `glpi_budgets` ADD `recursive` tinyint(1) NOT NULL DEFAULT '0' AFTER `name`";
      $DB->query($query) or die("0.80 add recursive field in glpi_budgets" . $LANG['update'][90] . $DB->error());
   }
   if (!FieldExists("glpi_budgets","entities_id")) {
         $query = "ALTER TABLE `glpi_budgets` ADD `entities_id` int(11) NOT NULL default '0' AFTER `name`;";
         $DB->query($query) or die("0.80 add entities_id field in glpi_budgets" . $LANG['update'][90] . $DB->error());
   }
   if (!isIndex("glpi_budgets","entities_id")) {
      $query="ALTER TABLE `glpi_budgets` ADD INDEX `entities_id` (`entities_id`);";
      $DB->query($query) or die("0.80 create index entities_id in glpi_budgets " . $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists("glpi_budgets","deleted")) {
      $query = "ALTER TABLE `glpi_budgets` ADD `deleted` tinyint(1) NOT NULL DEFAULT '0'";
      $DB->query($query) or die("0.80 add deleted field in glpi_budgets" . $LANG['update'][90] . $DB->error());
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
