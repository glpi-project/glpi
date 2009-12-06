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

// Update from 0.72.3 to 0.80

function update0723to080() {
	global $DB, $LANG;

	echo "<h3>".$LANG['install'][4]." -&gt; 0.80</h3>";
   displayMigrationMessage("080"); // Start

   displayMigrationMessage("080", $LANG['update'][141] . ' - Clean DB : rename tables'); // Updating schema

   $changes=array();
   $glpi_tables=array(
      'glpi_alerts'                       => 'glpi_alerts',
      'glpi_auth_ldap'                    => 'glpi_authldaps',
      'glpi_auth_ldap_replicate'          => 'glpi_authldapreplicates',
      'glpi_auth_mail'                    => 'glpi_authmails',
      'glpi_dropdown_auto_update'         => 'glpi_autoupdatesystems',
      'glpi_bookmark'                     => 'glpi_bookmarks',
      'glpi_display_default'              => 'glpi_bookmarks_users',
      'glpi_dropdown_budget'              => 'glpi_budgets',
      'glpi_cartridges'                   => 'glpi_cartridges',
      'glpi_cartridges_type'              => 'glpi_cartridgeitems',
      'glpi_cartridges_assoc'             => 'glpi_cartridges_printermodels',
      'glpi_dropdown_cartridge_type'      => 'glpi_cartridgeitemtypes',
      'glpi_computers'                    => 'glpi_computers',
      'glpi_computerdisks'                => 'glpi_computerdisks',
      'glpi_dropdown_model'               => 'glpi_computermodels',
      'glpi_type_computers'               => 'glpi_computertypes',
      'glpi_computer_device'              => 'glpi_computers_devices',
      'glpi_connect_wire'                 => 'glpi_computers_items',
      'glpi_inst_software'                => 'glpi_computers_softwareversions',
      'glpi_config'                       => 'glpi_configs',
      'glpi_consumables'                  => 'glpi_consumables',
      'glpi_consumables_type'             => 'glpi_consumableitems',
      'glpi_dropdown_consumable_type'     => 'glpi_consumableitemtypes',
      'glpi_contact_enterprise'           => 'glpi_contacts_suppliers',
      'glpi_contacts'                     => 'glpi_contacts',
      'glpi_dropdown_contact_type'        => 'glpi_contacttypes',
      'glpi_contracts'                    => 'glpi_contracts',
      'glpi_dropdown_contract_type'       => 'glpi_contracttypes',
      'glpi_contract_device'              => 'glpi_contracts_items',
      'glpi_contract_enterprise'          => 'glpi_contracts_suppliers',
      'glpi_device_case'                  => 'glpi_devicecases',
      'glpi_dropdown_case_type'           => 'glpi_devicecasetypes',
      'glpi_device_control'               => 'glpi_devicecontrols',
      'glpi_device_drive'                 => 'glpi_devicedrives',
      'glpi_device_gfxcard'               => 'glpi_devicegraphiccards',
      'glpi_device_hdd'                   => 'glpi_deviceharddrives',
      'glpi_device_iface'                 => 'glpi_devicenetworkcards',
      'glpi_device_moboard'               => 'glpi_devicemotherboards',
      'glpi_device_pci'                   => 'glpi_devicepcis',
      'glpi_device_power'                 => 'glpi_devicepowersupplies',
      'glpi_device_processor'             => 'glpi_deviceprocessors',
      'glpi_device_ram'                   => 'glpi_devicememories',
      'glpi_dropdown_ram_type'            => 'glpi_devicememorytypes',
      'glpi_device_sndcard'               => 'glpi_devicesoundcards',
      'glpi_display'                      => 'glpi_displaypreferences',
      'glpi_docs'                         => 'glpi_documents',
      'glpi_dropdown_rubdocs'             => 'glpi_documentcategories',
      'glpi_type_docs'                    => 'glpi_documenttypes',
      'glpi_doc_device'                   => 'glpi_documents_items',
      'glpi_dropdown_domain'              => 'glpi_domains',
      'glpi_entities'                     => 'glpi_entities',
      'glpi_entities_data'                => 'glpi_entitydatas',
      'glpi_event_log'                    => 'glpi_events',
      'glpi_dropdown_filesystems'         => 'glpi_filesystems',
      'glpi_groups'                       => 'glpi_groups',
      'glpi_users_groups'                 => 'glpi_groups_users',
      'glpi_infocoms'                     => 'glpi_infocoms',
      'glpi_dropdown_interface'           => 'glpi_interfacetypes',
      'glpi_kbitems'                      => 'glpi_knowbaseitems',
      'glpi_dropdown_kbcategories'        => 'glpi_knowbaseitemcategories',
      'glpi_links'                        => 'glpi_links',
      'glpi_links_device'                 => 'glpi_links_itemtypes',
      'glpi_dropdown_locations'           => 'glpi_locations',
      'glpi_history'                      => 'glpi_logs',
      'glpi_mailgate'                     => 'glpi_mailcollectors',
      'glpi_mailing'                      => 'glpi_mailingsettings',
      'glpi_dropdown_manufacturer'        => 'glpi_manufacturers',
      'glpi_monitors'                     => 'glpi_monitors',
      'glpi_dropdown_model_monitors'      => 'glpi_monitormodels',
      'glpi_type_monitors'                => 'glpi_monitortypes',
      'glpi_dropdown_netpoint'            => 'glpi_netpoints',
      'glpi_networking'                   => 'glpi_networkequipments',
      'glpi_dropdown_firmware'            => 'glpi_networkequipmentfirmwares',
      'glpi_dropdown_model_networking'    => 'glpi_networkequipmentmodels',
      'glpi_type_networking'              => 'glpi_networkequipmenttypes',
      'glpi_dropdown_iface'               => 'glpi_networkinterfaces',
      'glpi_networking_ports'             => 'glpi_networkports',
      'glpi_networking_vlan'              => 'glpi_networkports_vlans',
      'glpi_networking_wire'              => 'glpi_networkports_networkports',
      'glpi_dropdown_network'             => 'glpi_networks',
      'glpi_ocs_admin_link'               => 'glpi_ocsadmininfoslinks',
      'glpi_ocs_link'                     => 'glpi_ocslinks',
      'glpi_ocs_config'                   => 'glpi_ocsservers',
      'glpi_dropdown_os'                  => 'glpi_operatingsystems',
      'glpi_dropdown_os_sp'               => 'glpi_operatingsystemservicepacks',
      'glpi_dropdown_os_version'          => 'glpi_operatingsystemversions',
      'glpi_peripherals'                  => 'glpi_peripherals',
      'glpi_dropdown_model_peripherals'   => 'glpi_peripheralmodels',
      'glpi_type_peripherals'             => 'glpi_peripheraltypes',
      'glpi_phones'                       => 'glpi_phones',
      'glpi_dropdown_model_phones'        => 'glpi_phonemodels',
      'glpi_dropdown_phone_power'         => 'glpi_phonepowersupplies',
      'glpi_type_phones'                  => 'glpi_phonetypes',
      'glpi_plugins'                      => 'glpi_plugins',
      'glpi_printers'                     => 'glpi_printers',
      'glpi_dropdown_model_printers'      => 'glpi_printermodels',
      'glpi_type_printers'                => 'glpi_printertypes',
      'glpi_profiles'                     => 'glpi_profiles',
      'glpi_users_profiles'               => 'glpi_profiles_users',
      'glpi_registry'                     => 'glpi_registrykeys',
      'glpi_reminder'                     => 'glpi_reminders',
      'glpi_reservation_resa'             => 'glpi_reservations',
      'glpi_reservation_item'             => 'glpi_reservationitems',
      'glpi_rules_descriptions'           => 'glpi_rules',
      'glpi_rules_actions'                => 'glpi_ruleactions',
      'glpi_rule_cache_model_computer'    => 'glpi_rulecachecomputermodels',
      'glpi_rule_cache_type_computer'     => 'glpi_rulecachecomputertypes',
      'glpi_rule_cache_manufacturer'      => 'glpi_rulecachemanufacturers',
      'glpi_rule_cache_model_monitor'     => 'glpi_rulecachemonitormodels',
      'glpi_rule_cache_type_monitor'      => 'glpi_rulecachemonitortypes',
      'glpi_rule_cache_model_networking'  => 'glpi_rulecachenetworkequipmentmodels',
      'glpi_rule_cache_type_networking'   => 'glpi_rulecachenetworkequipmenttypes',
      'glpi_rule_cache_os'                => 'glpi_rulecacheoperatingsystems',
      'glpi_rule_cache_os_sp'             => 'glpi_rulecacheoperatingsystemservicepacks',
      'glpi_rule_cache_os_version'        => 'glpi_rulecacheoperatingsystemversions',
      'glpi_rule_cache_model_peripheral'  => 'glpi_rulecacheperipheralmodels',
      'glpi_rule_cache_type_peripheral'   => 'glpi_rulecacheperipheraltypes',
      'glpi_rule_cache_model_phone'       => 'glpi_rulecachephonemodels',
      'glpi_rule_cache_type_phone'        => 'glpi_rulecachephonetypes',
      'glpi_rule_cache_model_printer'     => 'glpi_rulecacheprintermodels',
      'glpi_rule_cache_type_printer'      => 'glpi_rulecacheprintertypes',
      'glpi_rule_cache_software'          => 'glpi_rulescachesoftwares',
      'glpi_rules_criterias'              => 'glpi_rulecriterias',
      'glpi_rules_ldap_parameters'        => 'glpi_ruleldapparameters',
      'glpi_software'                     => 'glpi_softwares',
      'glpi_dropdown_software_category'   => 'glpi_softwarecategories',
      'glpi_softwarelicenses'             => 'glpi_softwarelicenses',
      'glpi_dropdown_licensetypes'        => 'glpi_softwarelicensetypes',
      'glpi_softwareversions'             => 'glpi_softwareversions',
      'glpi_dropdown_state'               => 'glpi_states',
      'glpi_enterprises'                  => 'glpi_suppliers',
      'glpi_dropdown_enttype'             => 'glpi_suppliertypes',
      'glpi_tracking'                     => 'glpi_tickets',
      'glpi_dropdown_tracking_category'   => 'glpi_ticketcategories',
      'glpi_followups'                    => 'glpi_ticketfollowups',
      'glpi_tracking_planning'            => 'glpi_ticketplannings',
      'glpi_transfers'                    => 'glpi_transfers',
      'glpi_users'                        => 'glpi_users',
      'glpi_dropdown_user_titles'         => 'glpi_usertitles',
      'glpi_dropdown_user_types'          => 'glpi_usercategories',
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
      if (FieldExists($new_table,'ID')) {
         // ALTER ID -> id
         $changes[$new_table][]="CHANGE `ID` `id` INT( 11 ) NOT NULL AUTO_INCREMENT";

      }
   }
   if ($backup_tables) {
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
                           'tables' => array('glpi_ticketfollowups','glpi_knowbaseitems',
                              'glpi_tickets')),
                     ),
   'auto_update' => array(array('to' => 'autoupdatesystems_id',
                           'tables' => array('glpi_computers',)),
                     ),
   'budget' => array(array('to' => 'budgets_id',
                           'tables' => array('glpi_infocoms')),
                     ),
   'buy_version' => array(array('to' => 'softwareversions_id_buy',
                           'tables' => array('glpi_softwarelicenses')),
                     ),
   'category' => array(array('to' => 'ticketcategories_id',
                           'tables' => array('glpi_tickets')),
                      array('to' => 'softwarecategories_id',
                           'tables' => array('glpi_softwares')),
                     ),
   'categoryID' => array(array('to' => 'knowbaseitemcategories_id',
                           'tables' => array('glpi_knowbaseitems')),
                     ),
   'category_on_software_delete' => array(array('to' => 'softwarecategories_id_ondelete',
                           'noindex' => array('glpi_configs'),
                           'tables' => array('glpi_configs'),
                           'comments' => array('glpi_configs'=>'category applyed when a software is deleted')),
                     ),
   'cID' => array(array('to' => 'computers_id',
                           'tables' => array('glpi_computers_softwareversions')),
                     ),
   'computer' => array(array('to' => 'items_id',
                           'noindex' => array('glpi_tickets'),
                           'tables' => array('glpi_tickets')),
                     ),
   'computer_id' => array(array('to' => 'computers_id',
                           'tables' => array('glpi_registrykeys')),
                     ),
   'contract_type' => array(array('to' => 'contracttypes_id',
                           'tables' => array('glpi_contracts')),
                     ),
   'default_rubdoc_tracking' => array(array('to' => 'documentcategories_id_forticket',
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
                                 'glpi_reservationitems','glpi_tickets',),
                           'tables' => array('glpi_alerts','glpi_contracts_items',
                                 'glpi_documents_items','glpi_infocoms','glpi_bookmarks',
                                 'glpi_bookmarks_users','glpi_logs','glpi_links_itemtypes',
                                 'glpi_networkports','glpi_reservationitems','glpi_tickets')),
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
   'firmware' => array(array('to' => 'networkequipmentfirmwares_id',
                           'tables' => array('glpi_networkequipments')),
                     ),
   'FK_bookmark' => array(array('to' => 'bookmarks_id',
                           'tables' => array('glpi_bookmarks_users')),
                     ),
   'FK_computers' => array(array('to' => 'computers_id',
                           'tables' => array('glpi_computers_devices','glpi_computerdisks',
                                       'glpi_softwarelicenses',)),
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
                              'glpi_entitydatas',),
                           'tables' => array('glpi_bookmarks','glpi_cartridgeitems',
                              'glpi_computers','glpi_consumableitems','glpi_contacts',
                              'glpi_contracts','glpi_documents','glpi_locations',
                              'glpi_netpoints','glpi_suppliers','glpi_entitydatas',
                              'glpi_groups','glpi_knowbaseitems','glpi_links',
                              'glpi_mailcollectors','glpi_monitors','glpi_networkequipments',
                              'glpi_peripherals','glpi_phones','glpi_printers',
                              'glpi_reminders','glpi_rules','glpi_softwares',
                              'glpi_softwarelicenses','glpi_tickets','glpi_users',
                              'glpi_profiles_users',),
                           'default'=> array('glpi_bookmarks' => "-1")),
                     ),
   'FK_filesystems' => array(array('to' => 'filesystems_id',
                           'tables' => array('glpi_computerdisks',)),
                     ),
   'FK_glpi_cartridges_type' => array(array('to' => 'cartridgeitems_id',
                           'tables' => array('glpi_cartridges',
                              'glpi_cartridges_printermodels')),
                     ),
   'FK_glpi_consumables_type' => array(array('to' => 'consumableitems_id',
                           'noindex' => array(''),
                           'tables' => array('glpi_consumables',)),
                     ),
   'FK_glpi_device' => array(array('to' => 'items_id',
                           'noindex' => array('glpi_logs'),
                           'tables' => array('glpi_logs')),
                     ),
   'FK_glpi_dropdown_model_printers' => array(array('to' => 'printermodels_id',
                           'noindex' => array('glpi_cartridges_printermodels'),
                           'tables' => array('glpi_cartridges_printermodels',)),
                     ),
   'FK_glpi_enterprise' => array(array('to' => 'manufacturers_id',
                     'tables' => array('glpi_cartridgeitems','glpi_computers',
                        'glpi_consumableitems','glpi_devicecases','glpi_devicecontrols',
                        'glpi_devicedrives','glpi_devicegraphiccards','glpi_deviceharddrives',
                        'glpi_devicenetworkcards','glpi_devicemotherboards','glpi_devicepcis',
                        'glpi_devicepowersupplies','glpi_deviceprocessors','glpi_devicememories',
                        'glpi_devicesoundcards','glpi_monitors','glpi_networkequipments',
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
   'FK_interface' => array(array('to' => 'interfacetypes_id',
                           'tables' => array('glpi_devicegraphiccards')),
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
                           'tables' => array('glpi_rulecriterias','glpi_ruleactions')),
                     ),
   'FK_tracking' => array(array('to' => 'tickets_id',
                           'tables' => array('glpi_documents')),
                     ),
   'FK_users' => array(array('to' => 'users_id',
                              'noindex' => array('glpi_displaypreferences','glpi_bookmarks_users',
                                 'glpi_groups_users',),
                              'tables' => array('glpi_bookmarks', 'glpi_displaypreferences',
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
                           'tables' => array('glpi_ticketplannings')),
                     ),
   'id_auth' => array(array('to' => 'auths_id',
                           'noindex' => array('glpi_users'),
                           'tables' => array('glpi_users'),),
                     ),
   'id_device' => array(array('to' => 'items_id',
                           'noindex' => array('glpi_reservationitems'),
                           'tables' => array('glpi_reservationitems')),
                     ),
   'id_followup' => array(array('to' => 'ticketfollowups_id',
                           'tables' => array('glpi_ticketplannings')),
                     ),
   'id_item' => array(array('to' => 'reservationitems_id',
                           'tables' => array('glpi_reservations')),
                     ),
   'id_user' => array(array('to' => 'users_id',
                           'tables' => array('glpi_consumables','glpi_reservations')),
                     ),
   'iface' => array(array('to' => 'networkinterfaces_id',
                           'tables' => array('glpi_networkports')),
                     ),
   'interface' => array(array('to' => 'interfacetypes_id',
                           'tables' => array('glpi_devicecontrols','glpi_deviceharddrives',
                                 'glpi_devicedrives')),
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
                           'tables' => array('glpi_cartridgeitems','glpi_computers',
                              'glpi_consumableitems','glpi_netpoints','glpi_monitors',
                              'glpi_networkequipments','glpi_peripherals','glpi_phones',
                              'glpi_printers','glpi_users','glpi_softwares')),
                     ),
   'model' => array(array('to' => 'computermodels_id',
                           'tables' => array('glpi_computers')),
                     array('to' => 'monitormodels_id',
                           'tables' => array('glpi_monitors')),
                     array('to' => 'networkequipmentmodels_id',
                           'tables' => array('glpi_networkequipments')),
                     array('to' => 'peripheralmodels_id',
                           'tables' => array('glpi_peripherals')),
                     array('to' => 'phonemodels_id',
                           'tables' => array('glpi_phones')),
                     array('to' => 'printermodels_id',
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
   'os_sp' => array(array('to' => 'operatingsystemservicepacks_id',
                           'tables' => array('glpi_computers',)),
                     ),
   'os_version' => array(array('to' => 'operatingsystemversions_id',
                           'tables' => array('glpi_computers',)),
                     ),
   'parentID' => array(array('to' => 'knowbaseitemcategories_id',
                           'noindex' => array('glpi_knowbaseitemcategories'),
                           'tables' => array('glpi_knowbaseitemcategories')),
                        array('to' => 'locations_id',
                           'tables' => array('glpi_locations')),
                        array('to' => 'ticketcategories_id',
                           'tables' => array('glpi_ticketcategories')),
                        array('to' => 'entities_id',
                           'tables' => array('glpi_entities')),
                     ),
   'platform' => array(array('to' => 'operatingsystems_id',
                           'tables' => array('glpi_softwares',)),
                     ),
   'power' => array(array('to' => 'phonepowersupplies_id',
                           'tables' => array('glpi_phones')),
                     ),
   'recipient' => array(array('to' => 'users_id_recipient',
                           'tables' => array('glpi_tickets')),
                     ),
   'rubrique' => array(array('to' => 'documentcategories_id',
                           'tables' => array('glpi_documents')),
                     ),
   'rule_id' => array(array('to' => 'rules_id',
                           'tables' => array('glpi_rulecachemanufacturers',
                              'glpi_rulecachecomputermodels','glpi_rulecachemonitormodels',
                              'glpi_rulecachenetworkequipmentmodels','glpi_rulecacheperipheralmodels',
                              'glpi_rulecachephonemodels','glpi_rulecacheprintermodels',
                              'glpi_rulecacheoperatingsystems','glpi_rulecacheoperatingsystemservicepacks',
                              'glpi_rulecacheoperatingsystemversions','glpi_rulescachesoftwares',
                              'glpi_rulecachecomputertypes','glpi_rulecachemonitortypes',
                              'glpi_rulecachenetworkequipmenttypes','glpi_rulecacheperipheraltypes',
                              'glpi_rulecachephonetypes','glpi_rulecacheprintertypes',)),
                     ),
   'server_id' => array(array('to' => 'authldaps_id',
                           'tables' => array('glpi_authldapreplicates')),
                     ),
   'sID' => array(array('to' => 'softwares_id',
                           'tables' => array('glpi_softwarelicenses','glpi_softwareversions')),
                     ),
   'state' => array(array('to' => 'states_id',
                           'tables' => array('glpi_computers','glpi_monitors',
                              'glpi_networkequipments','glpi_peripherals','glpi_phones',
                              'glpi_printers','glpi_softwareversions')),
                     ),
   'tech_num' => array(array('to' => 'users_id_tech',
                              'tables' => array('glpi_cartridgeitems','glpi_computers',
                              'glpi_consumableitems','glpi_monitors',
                              'glpi_networkequipments','glpi_peripherals','glpi_phones',
                              'glpi_printers','glpi_softwares')),
                     ),
   'title' => array(array('to' => 'usertitles_id',
                           'tables' => array('glpi_users')),
                     ),
   'tracking' => array(array('to' => 'tickets_id',
                           'tables' => array('glpi_ticketfollowups')),
                     ),
   'type' => array(array('to' => 'cartridgeitemtypes_id',
                           'tables' => array('glpi_cartridgeitems')),
                  array('to' => 'computertypes_id',
                           'tables' => array('glpi_computers')),
                  array('to' => 'consumableitemtypes_id',
                           'tables' => array('glpi_consumableitems')),
                  array('to' => 'contacttypes_id',
                           'tables' => array('glpi_contacts')),
                  array('to' => 'devicecasetypes_id',
                           'tables' => array('glpi_devicecases')),
                  array('to' => 'devicememorytypes_id',
                           'tables' => array('glpi_devicememories')),
                  array('to' => 'suppliertypes_id',
                           'tables' => array('glpi_suppliers')),
                  array('to' => 'monitortypes_id',
                           'tables' => array('glpi_monitors')),
                  array('to' => 'networkequipmenttypes_id',
                           'tables' => array('glpi_networkequipments')),
                  array('to' => 'peripheraltypes_id',
                           'tables' => array('glpi_peripherals')),
                  array('to' => 'phonetypes_id',
                           'tables' => array('glpi_phones')),
                  array('to' => 'printertypes_id',
                           'tables' => array('glpi_printers')),
                  array('to' => 'softwarelicensetypes_id',
                           'tables' => array('glpi_softwarelicenses')),
                  array('to' => 'usercategories_id',
                           'tables' => array('glpi_users')),
                  array('to' => 'itemtype', 'noindex' => array('glpi_computers_items'),
                           'tables' => array('glpi_computers_items','glpi_displaypreferences')),
                     ),
   'update_software' => array(array('to' => 'softwares_id',
                           'tables' => array('glpi_softwares')),
                     ),
   'use_version' => array(array('to' => 'softwareversions_id_use',
                           'tables' => array('glpi_softwarelicenses')),
                     ),
   'vID' => array(array('to' => 'softwareversions_id',
                           'tables' => array('glpi_computers_softwareversions')),
                     ),
   );


   foreach ($foreignkeys as $oldname => $newnames) {
      foreach ($newnames as $tab) {
         $newname=$tab['to'];
         foreach ($tab['tables'] as $table) {
            $doindex=true;
            if (isset($tab['noindex'])&&in_array($table,$tab['noindex'])) {
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

               $changes[$table][]="CHANGE COLUMN `$oldname` `$newname` INT( 11 ) NOT NULL DEFAULT '$default_value' $addcomment";
            } else {
               echo "<div class='red'><p>Error : $table.$oldname does not exist.</p></div>";
            }
            // If do index : delete old one / create new one
            if ($doindex) {
               if (!isIndex($table, $newname)) {
                  $changes[$table][]="ADD INDEX `$newname` (`$newname`)";
               }
               if ($oldname!=$newname && isIndex($table, $oldname)) {
                  $changes[$table][]="DROP INDEX `$oldname`";
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
   'glpi_cartridgeitems' => array(array('from' => 'deleted', 'to' => 'is_deleted', 'default' =>0 ),//
                     ),
   'glpi_computers' => array(array('from' => 'is_template', 'to' => 'is_template', 'default' =>0),//
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
                           array('from' => 'ticket_title_mandatory', 'to' => 'is_ticket_title_mandatory', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'ticket_content_mandatory', 'to' => 'is_ticket_content_mandatory', 'default' =>1, 'noindex'=>true ),//
                           array('from' => 'ticket_category_mandatory', 'to' => 'is_ticket_category_mandatory', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'followup_private', 'to' => 'followup_private', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'software_helpdesk_visible', 'to' => 'default_software_helpdesk_visible', 'default' =>1, 'noindex'=>true ),//
                     ),
   'glpi_consumableitems' => array(array('from' => 'deleted', 'to' => 'is_deleted', 'default' =>0 ),//
                     ),
   'glpi_contacts' => array(array('from' => 'recursive','to' => 'is_recursive', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'deleted', 'to' => 'is_deleted', 'default' =>0 ),//
                     ),
   'glpi_contracts' => array(array('from' => 'recursive','to' => 'is_recursive', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'deleted', 'to' => 'is_deleted', 'default' =>0 ),//
                           array('from' => 'monday', 'to' => 'use_monday', 'default' =>0 ),//
                           array('from' => 'saturday', 'to' => 'use_saturday', 'default' =>0 ),//
                     ),
   'glpi_devicecontrols' => array(array('from' => 'raid','to' => 'is_raid', 'default' =>0, 'noindex'=>true ),//
                     ),
   'glpi_devicedrives' => array(array('from' => 'is_writer','to' => 'is_writer', 'default' =>1, 'noindex'=>true ),//
                     ),
   'glpi_devicepowersupplies' => array(array('from' => 'atx','to' => 'is_atx', 'default' =>1, 'noindex'=>true ),//
                     ),
   'glpi_documents' => array(array('from' => 'recursive','to' => 'is_recursive', 'default' =>0, 'noindex'=>true ),//
                           array('from' => 'deleted', 'to' => 'is_deleted', 'default' =>0 ),//
                     ),
   'glpi_documenttypes' => array(array('from' => 'upload','to' => 'is_uploadable', 'default' =>1, ),//
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
                        array('from' => 'import_device_modems','to' => 'import_device_modem', 'default' =>0,'noindex'=>true),//
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
                        array('from' => 'use_soft_dict','to' => 'use_soft_dict', 'default' =>0,'noindex'=>true),//
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
   'glpi_reservationitems' => array(array('from' => 'active','to' => 'is_active', 'default' =>1),//
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
   'glpi_softwarelicenses' => array(array('from' => 'recursive','to' => 'is_recursive', 'default' =>0, 'noindex'=>true ),//
                     ),
   'glpi_tickets' => array(array('from' => 'emailupdates', 'to' => 'use_email_notification', 'default' =>0, 'noindex'=>true  ),//
                     ),
   'glpi_ticketfollowups' => array(array('from' => 'private', 'to' => 'is_private', 'default' =>0 ),//
                     ),
   'glpi_users' => array(array('from' => 'deleted','to' => 'is_deleted', 'default' =>0),//
                        array('from' => 'active','to' => 'is_active', 'default' =>1),//
                        array('from' => 'jobs_at_login', 'to' => 'show_jobs_at_login', 'default' =>NULL,'maybenull'=>true, 'noindex'=>true),//
                        array('from' => 'followup_private', 'to' => 'followup_private', 'default' =>NULL, 'maybenull'=>true, 'noindex'=>true ),//
                        array('from' => 'expand_soft_categorized', 'to' => 'is_categorized_soft_expanded', 'default' =>NULL, 'maybenull'=>true, 'noindex'=>true ),//
                        array('from' => 'expand_soft_not_categorized', 'to' => 'is_not_categorized_soft_expanded', 'default' =>NULL, 'maybenull'=>true, 'noindex'=>true ),//
                        array('from' => 'flat_dropdowntree', 'to' => 'use_flat_dropdowntree', 'default' =>NULL, 'maybenull'=>true,'noindex'=>true ),//
                        array('from' => 'view_ID', 'to' => 'is_ids_visible', 'default' =>NULL, 'maybenull'=>true, 'noindex'=>true ),//
                     ),

   );

   foreach ($boolfields as $table => $tab) {
      foreach ($tab as $update) {
         $newname=$update['to'];
         $oldname=$update['from'];
         $doindex=true;
         if (isset($update['noindex']) && $update['noindex']) {
            $doindex=false;
         }
         // Rename field
         if (FieldExists($table, $oldname)) {
            $NULL="NOT NULL";
            if (isset($update['maybenull']) && $update['maybenull']) {
               $NULL="NULL";
            }

            $default="DEFAULT NULL";
            if (isset($update['default']) && !is_null($update['default'])) {
               $default="DEFAULT ".$update['default'];
            }

            // Manage NULL fields
            $query="UPDATE `$table` SET `$oldname`=0 WHERE `$oldname` IS NULL ;";
            $DB->query($query) or die("0.80 prepare datas for update $oldname to $newname in $table " . $LANG['update'][90] . $DB->error());

            // Manage not zero values
            $query="UPDATE `$table` SET `$oldname`=1 WHERE `$oldname` <> 0; ";
            $DB->query($query) or die("0.80 prepare datas for update $oldname to $newname in $table " . $LANG['update'][90] . $DB->error());

            $changes[$table][]="CHANGE `$oldname` `$newname` TINYINT( 1 ) $NULL $default";

         } else {
            echo "<div class='red'><p>Error : $table.$oldname does not exist.</p></div>";
         }
         // If do index : delete old one / create new one
         if ($doindex) {
            if (!isIndex($table, $newname)) {
               $changes[$table][]="ADD INDEX `$newname` (`$newname`)";
            }
            if ($newname!=$oldname && isIndex($table, $oldname)) {
               $changes[$table][]="DROP INDEX `$oldname`";

            }
         }
      }
   }
   displayMigrationMessage("080", $LANG['update'][141] . ' - Clean DB : update text fields'); // Updating schema

   $textfields=array(
   'comments' => array('to' => 'comment',
                           'tables' => array('glpi_cartridgeitems','glpi_computers',
                                 'glpi_consumableitems','glpi_contacts','glpi_contracts',
                                 'glpi_documents','glpi_autoupdatesystems','glpi_budgets',
                                 'glpi_cartridgeitemtypes','glpi_devicecasetypes','glpi_consumableitemtypes',
                                 'glpi_contacttypes','glpi_contracttypes','glpi_domains',
                                 'glpi_suppliertypes','glpi_filesystems','glpi_networkequipmentfirmwares',
                                 'glpi_networkinterfaces','glpi_interfacetypes',
                                 'glpi_knowbaseitemcategories','glpi_softwarelicensetypes','glpi_locations',
                                 'glpi_manufacturers','glpi_computermodels','glpi_monitormodels',
                                 'glpi_networkequipmentmodels','glpi_peripheralmodels','glpi_phonemodels',
                                 'glpi_printermodels','glpi_netpoints','glpi_networks',
                                 'glpi_operatingsystems','glpi_operatingsystemservicepacks','glpi_operatingsystemversions',
                                 'glpi_phonepowersupplies','glpi_devicememorytypes','glpi_documentcategories',
                                 'glpi_softwarecategories','glpi_states','glpi_ticketcategories',
                                 'glpi_usertitles','glpi_usercategories','glpi_vlans',
                                 'glpi_suppliers','glpi_entities','glpi_groups',
                                 'glpi_infocoms','glpi_monitors','glpi_phones',
                                 'glpi_printers','glpi_peripherals','glpi_networkequipments',
                                 'glpi_reservationitems','glpi_rules','glpi_softwares',
                                 'glpi_softwarelicenses','glpi_softwareversions','glpi_computertypes',
                                 'glpi_monitortypes','glpi_networkequipmenttypes','glpi_peripheraltypes',
                                 'glpi_phonetypes','glpi_printertypes','glpi_users',),
                     ),
      'notes' =>  array('to' => 'notepad', 'long'=>true,
                           'tables' => array('glpi_cartridgeitems','glpi_computers',
                              'glpi_consumableitems','glpi_contacts','glpi_contracts',
                              'glpi_documents','glpi_suppliers','glpi_entitydatas',
                              'glpi_printers','glpi_monitors','glpi_phones','glpi_peripherals',
                              'glpi_networkequipments','glpi_softwares')),

      'ldap_condition' =>  array('to' => 'condition',
                           'tables' => array('glpi_authldaps')),
      'import_printers' =>  array('to' => 'import_printer','long'=>true,
                           'tables' => array('glpi_ocslinks')),
      'contents' =>  array('to' => 'content','long'=>true,
                           'tables' => array('glpi_tickets','glpi_ticketfollowups')),
);
   foreach ($textfields as $oldname => $tab) {
      $newname=$tab['to'];
      $type="TEXT";
      if (isset($tab['long']) && $tab['long']) {
         $type="LONGTEXT";
      }
      foreach ($tab['tables'] as $table) {
         // Rename field
         if (FieldExists($table, $oldname)) {

            $query="ALTER TABLE `$table` CHANGE `$oldname` `$newname` $type NULL DEFAULT NULL  ";
            $DB->query($query) or die("0.80 rename $oldname to $newname in $table " . $LANG['update'][90] . $DB->error());
         } else {
            echo "<div class='red'><p>Error : $table.$oldname does not exist.</p></div>";
         }
      }
   }

   $varcharfields=array(
      'glpi_authldaps' => array(array('from' => 'ldap_host', 'to' => 'host', 'noindex'=>true),//
                        array('from' => 'ldap_basedn', 'to' => 'basedn', 'noindex'=>true),//
                        array('from' => 'ldap_rootdn', 'to' => 'rootdn', 'noindex'=>true),//
                        array('from' => 'ldap_pass', 'to' => 'rootdn_password', 'noindex'=>true),//
                        array('from' => 'ldap_login', 'to' => 'login_field', 'default'=>'uid','noindex'=>true,''),//
                        array('from' => 'ldap_field_group', 'to' => 'group_field', 'noindex'=>true),//
                        array('from' => 'ldap_group_condition', 'to' => 'group_condition', 'noindex'=>true),//
                        array('from' => 'ldap_field_group_member', 'to' => 'group_member_field', 'noindex'=>true),//
                        array('from' => 'ldap_field_email', 'to' => 'email_field', 'noindex'=>true),//
                        array('from' => 'ldap_field_realname', 'to' => 'realname_field', 'noindex'=>true),//
                        array('from' => 'ldap_field_firstname', 'to' => 'firstname_field', 'noindex'=>true),//
                        array('from' => 'ldap_field_phone', 'to' => 'phone_field', 'noindex'=>true),//
                        array('from' => 'ldap_field_phone2', 'to' => 'phone2_field', 'noindex'=>true),//
                        array('from' => 'ldap_field_mobile', 'to' => 'mobile_field', 'noindex'=>true),//
                        array('from' => 'ldap_field_comments', 'to' => 'comment_field', 'noindex'=>true),//
                        array('from' => 'ldap_field_title', 'to' => 'title_field', 'noindex'=>true),//
                        array('from' => 'ldap_field_type', 'to' => 'category_field', 'noindex'=>true),//
                        array('from' => 'ldap_field_language', 'to' => 'language_field', 'noindex'=>true),//
                     ),
      'glpi_authldapreplicates' => array(array('from' => 'ldap_host', 'to' => 'host', 'noindex'=>true),//
                     ),
      'glpi_authmails' => array(array('from' => 'imap_auth_server', 'to' => 'connect_string', 'noindex'=>true),//
                        array('from' => 'imap_host', 'to' => 'host', 'noindex'=>true),//
                     ),
      'glpi_computers' => array(array('from' => 'os_license_id', 'to' => 'os_licenseid', 'noindex'=>true),//
                        array('from' => 'tplname', 'to' => 'template_name', 'noindex'=>true),//
                     ),
      'glpi_configs' => array(array('from' => 'helpdeskhelp_url', 'to' => 'helpdesk_doc_url', 'noindex'=>true),//
                        array('from' => 'centralhelp_url', 'to' => 'central_doc_url', 'noindex'=>true),//
                     ),
      'glpi_contracts' => array(array('from' => 'compta_num', 'to' => 'accounting_number', 'noindex'=>true),//
                     ),
      'glpi_events' => array(array('from' => 'itemtype', 'to' => 'type', 'noindex'=>true),//
                     ),
      'glpi_infocoms' => array(array('from' => 'num_commande', 'to' => 'order_number', 'noindex'=>true),//
                        array('from' => 'bon_livraison', 'to' => 'delivery_number', 'noindex'=>true),//
                        array('from' => 'num_immo', 'to' => 'immo_number', 'noindex'=>true),//
                        array('from' => 'facture', 'to' => 'bill', 'noindex'=>true),//
                     ),
      'glpi_monitors' => array(array('from' => 'tplname', 'to' => 'template_name', 'noindex'=>true),//
                     ),
      'glpi_networkequipments' => array(array('from' => 'tplname', 'to' => 'template_name', 'noindex'=>true),//
               array('from' => 'ifmac', 'to' => 'mac', 'noindex'=>true),//
               array('from' => 'ifaddr', 'to' => 'ip', 'noindex'=>true),//
                     ),
      'glpi_networkports' => array(array('from' => 'ifmac', 'to' => 'mac', 'noindex'=>true),//
               array('from' => 'ifaddr', 'to' => 'ip', 'noindex'=>true),//
                     ),
      'glpi_peripherals' => array(array('from' => 'tplname', 'to' => 'template_name', 'noindex'=>true),//
                     ),
      'glpi_phones' => array(array('from' => 'tplname', 'to' => 'template_name', 'noindex'=>true),//
                     ),
      'glpi_printers' => array(array('from' => 'tplname', 'to' => 'template_name', 'noindex'=>true),//
                     array('from' => 'ramSize', 'to' => 'memory_size', 'noindex'=>true),//
                     ),
      'glpi_registrykeys' => array(array('from' => 'registry_hive', 'to' => 'hive', 'noindex'=>true),//
                     array('from' => 'registry_path', 'to' => 'path', 'noindex'=>true),//
                     array('from' => 'registry_value', 'to' => 'value', 'noindex'=>true),//
                     array('from' => 'registry_ocs_name', 'to' => 'ocs_name', 'noindex'=>true),//
                     ),
      'glpi_softwares' => array(array('from' => 'tplname', 'to' => 'template_name', 'noindex'=>true),//
                     ),
      'glpi_tickets' => array(array('from' => 'uemail', 'to' => 'user_email', 'noindex'=>true),//
                     ),
                  );
   foreach ($varcharfields as $table => $tab) {
      foreach ($tab as $update) {
         $newname=$update['to'];
         $oldname=$update['from'];
         $doindex=true;
         if (isset($update['noindex']) && $update['noindex']) {
            $doindex=false;
         }
         $default="DEFAULT NULL";
         if (isset($update['default']) && !is_null($update['default'])) {
            $default="DEFAULT '".$update['default']."'";
         }

         // Rename field
         if (FieldExists($table, $oldname)) {
            $query="ALTER TABLE `$table` CHANGE `$oldname` `$newname` VARCHAR( 255 ) NULL $default  ";
            $DB->query($query) or die("0.80 rename $oldname to $newname in $table " . $LANG['update'][90] . $DB->error());
         } else {
            echo "<div class='red'><p>Error : $table.$oldname does not exist.</p></div>";
         }
         // If do index : delete old one / create new one
         if ($doindex) {
            if (!isIndex($table, $newname)) {
            $changes[$table][]="ADD INDEX `$newname` (`$newname`)";
            }
            if ($newname!=$oldname && isIndex($table, $oldname)) {
               $changes[$table][]="DROP INDEX `$oldname`";
            }
         }
      }
   }

   $charfields=array(
      'glpi_profiles' => array(array('from' => 'user_auth_method', 'to' => 'user_authtype', 'length'=>1,'default' =>NULL, 'noindex'=>true),//
                  array('from' => 'rule_tracking', 'to' => 'rule_ticket', 'length'=>1,'default' =>NULL, 'noindex'=>true),//
                  array('from' => 'rule_softwarecategories', 'to' => 'rule_softwarecategories', 'length'=>1,'default' =>NULL, 'noindex'=>true),//
                  array('from' => 'rule_dictionnary_software', 'to' => 'rule_dictionnary_software', 'length'=>1,'default' =>NULL, 'noindex'=>true),//
                  array('from' => 'rule_dictionnary_dropdown', 'to' => 'rule_dictionnary_dropdown', 'length'=>1,'default' =>NULL, 'noindex'=>true),//
                        ),
      'glpi_configs' => array(array('from' => 'version', 'to' => 'version', 'length'=>10,'default' =>NULL, 'noindex'=>true),//
               array('from' => 'version', 'to' => 'version', 'length'=>10,'default' =>NULL, 'noindex'=>true),//
               array('from' => 'language', 'to' => 'language', 'length'=>10,'default' =>'en_GB', 'noindex'=>true, 'comments'=>'see define.php CFG_GLPI[language] array'),//
               array('from' => 'priority_1', 'to' => 'priority_1', 'length'=>20,'default' =>'#fff2f2', 'noindex'=>true),//
               array('from' => 'priority_2', 'to' => 'priority_2', 'length'=>20,'default' =>'#ffe0e0', 'noindex'=>true),//
               array('from' => 'priority_3', 'to' => 'priority_3', 'length'=>20,'default' =>'#ffcece', 'noindex'=>true),//
               array('from' => 'priority_4', 'to' => 'priority_4', 'length'=>20,'default' =>'#ffbfbf', 'noindex'=>true),//
               array('from' => 'priority_5', 'to' => 'priority_5', 'length'=>20,'default' =>'#ffadad', 'noindex'=>true),//
               array('from' => 'founded_new_version', 'to' => 'founded_new_version', 'length'=>10,'default' =>NULL, 'noindex'=>true),//
                        ),
      'glpi_rules' => array(array('from' => 'match', 'to' => 'match', 'length'=>10,'default' =>NULL, 'noindex'=>true,'comments'=>'see define.php *_MATCHING constant'),//
                        ),
      'glpi_users' => array(array('from' => 'language', 'to' => 'language', 'length'=>10,'default' =>NULL, 'noindex'=>true, 'comments'=>'see define.php CFG_GLPI[language] array'),//
               array('from' => 'priority_1', 'to' => 'priority_1', 'length'=>20,'default' =>NULL, 'noindex'=>true),//
               array('from' => 'priority_2', 'to' => 'priority_2', 'length'=>20,'default' =>NULL, 'noindex'=>true),//
               array('from' => 'priority_3', 'to' => 'priority_3', 'length'=>20,'default' =>NULL, 'noindex'=>true),//
               array('from' => 'priority_4', 'to' => 'priority_4', 'length'=>20,'default' =>NULL, 'noindex'=>true),//
               array('from' => 'priority_5', 'to' => 'priority_5', 'length'=>20,'default' =>NULL, 'noindex'=>true),//
                        ),

                     );
   foreach ($charfields as $table => $tab) {
      foreach ($tab as $update) {
         $newname=$update['to'];
         $oldname=$update['from'];
         $length=$update['length'];
         $doindex=true;
         if (isset($update['noindex']) && $update['noindex']) {
            $doindex=false;
         }
         $default="DEFAULT NULL";
         if (isset($update['default']) && !is_null($update['default'])) {
            $default="DEFAULT '".$update['default']."'";
         }
         $addcomment="";
         if (isset($update['comments']) ) {
            $addcomment="COMMENT '".$update['comments']."'";
         }

         // Rename field
         if (FieldExists($table, $oldname)) {
            $query="ALTER TABLE `$table` CHANGE `$oldname` `$newname` CHAR( $length ) NULL $default $addcomment ";
            $DB->query($query) or die("0.80 rename $oldname to $newname in $table " . $LANG['update'][90] . $DB->error());
         } else {
            echo "<div class='red'><p>Error : $table.$oldname does not exist.</p></div>";
         }
         // If do index : delete old one / create new one
         if ($doindex) {
            if (!isIndex($table, $newname)) {
               $changes[$table][]="ADD INDEX `$newname` (`$newname`)";
            }
            if ($oldname!=$newname && isIndex($table, $oldname)) {
               $changes[$table][]="DROP INDEX `$oldname`";
            }
         }
      }
   }
   $intfields=array(
      'glpi_authldaps' => array(array('from' => 'ldap_port', 'to' => 'port', 'default' =>389, 'noindex'=>true,'checkdatas'=>true),//
                     array('from' => 'ldap_search_for_groups', 'to' => 'group_search_type', 'default' =>0, 'noindex'=>true),//
                     array('from' => 'ldap_opt_deref', 'to' => 'deref_option', 'default' =>0, 'noindex'=>true),//
                     array('from' => 'timezone', 'to' => 'time_offset', 'default' =>0, 'noindex'=>true,'comments'=>'in seconds'),//
                              ),
      'glpi_authldapreplicates' => array(array('from' => 'ldap_port', 'to' => 'port', 'default' =>389, 'noindex'=>true,'checkdatas'=>true),//
                     ),
      'glpi_bookmarks' => array(array('from' => 'type', 'to' => 'type', 'default' =>0, 'noindex'=>true,'comments'=>'see define.php BOOKMARK_* constant'),//
                     ),
      'glpi_cartridgeitems' => array(array('from' => 'alarm', 'to' => 'alarm_threshold', 'default' =>10,),//
                              ),
      'glpi_configs' => array(array('from' => 'glpi_timezone', 'to' => 'time_offset', 'default' =>0, 'noindex'=>true,'comments'=>'in seconds'),//
                              array('from' => 'cartridges_alarm', 'to' => 'default_alarm_threshold', 'default' =>10, 'noindex'=>true),//
                              array('from' => 'event_loglevel', 'to' => 'event_loglevel', 'default' =>5, 'noindex'=>true),//
                              array('from' => 'cas_port', 'to' => 'cas_port', 'default' =>443, 'noindex'=>true,'checkdatas'=>true),//
                              array('from' => 'auto_update_check', 'to' => 'auto_update_check', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'dateformat', 'to' => 'date_format', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'numberformat', 'to' => 'number_format', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'proxy_port', 'to' => 'proxy_port', 'default' =>8080, 'noindex'=>true,'checkdatas'=>true),//
                              array('from' => 'contract_alerts', 'to' => 'default_contract_alert', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'infocom_alerts', 'to' => 'default_infocom_alert', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'cartridges_alert', 'to' => 'cartridges_alert_repeat', 'default' =>0, 'noindex'=>true,'comments'=>'in seconds'),//
                              array('from' => 'consumables_alert', 'to' => 'consumables_alert_repeat', 'default' =>0, 'noindex'=>true,'comments'=>'in seconds'),//
                              array('from' => 'monitors_management_restrict', 'to' => 'monitors_management_restrict', 'default' =>2, 'noindex'=>true),//
                              array('from' => 'phones_management_restrict', 'to' => 'phones_management_restrict', 'default' =>2, 'noindex'=>true),//
                              array('from' => 'peripherals_management_restrict', 'to' => 'peripherals_management_restrict', 'default' =>2, 'noindex'=>true),//
                              array('from' => 'printers_management_restrict', 'to' => 'printers_management_restrict', 'default' =>2, 'noindex'=>true),//
                              array('from' => 'autoupdate_link_state', 'to' => 'state_autoupdate_mode', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'autoclean_link_state', 'to' => 'state_autoclean_mode', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'name_display_order', 'to' => 'names_format', 'default' =>0, 'noindex'=>true,'comments'=>'see *NAME_BEFORE constant in define.php'),//
                              array('from' => 'dropdown_limit', 'to' => 'dropdown_chars_limit', 'default' =>50, 'noindex'=>true),//
                              array('from' => 'smtp_mode', 'to' => 'smtp_mode', 'default' =>0, 'noindex'=>true,'comments'=>'see define.php MAIL_* constant'),//
                              array('from' => 'mailgate_filesize_max', 'to' => 'default_mailcollector_filesize_max', 'default' =>2097152, 'noindex'=>true),//
                              ),
      'glpi_consumableitems' => array(array('from' => 'alarm', 'to' => 'alarm_threshold', 'default' =>10,),//
                              ),
      'glpi_contracts' => array(array('from' => 'duration', 'to' => 'duration', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'notice', 'to' => 'notice', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'periodicity', 'to' => 'periodicity', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'facturation', 'to' => 'billing', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'device_countmax', 'to' => 'max_links_allowed', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'alert', 'to' => 'alert', 'default' =>0),//
                              array('from' => 'renewal', 'to' => 'renewal', 'default' =>0, 'noindex'=>true),//
                              ),
      'glpi_displaypreferences' => array(array('from' => 'num', 'to' => 'num', 'default' =>0,),//
                              array('from' => 'rank', 'to' => 'rank', 'default' =>0,),//
                              ),
      'glpi_events' => array(array('from' => 'level', 'to' => 'level', 'default' =>0,),//
                              ),
      'glpi_infocoms' => array(array('from' => 'warranty_duration', 'to' => 'warranty_duration', 'default' =>0, 'noindex'=>true,),//
                        array('from' => 'amort_time', 'to' => 'sink_time', 'default' =>0, 'noindex'=>true,),//
                        array('from' => 'amort_type', 'to' => 'sink_type', 'default' =>0, 'noindex'=>true,),//
                        array('from' => 'alert', 'to' => 'alert', 'default' =>0),//
                              ),
      'glpi_logs' => array(array('from' => 'linked_action', 'to' => 'linked_action', 'default' =>0,'comments'=>'see define.php HISTORY_* constant'),//
                     ),
      'glpi_mailingsettings' => array(array('from' => 'item_type', 'to' => 'mailingtype', 'default' =>0,'noindex'=>true,'comments'=>'see define.php *_MAILING_TYPE constant'),//
                     ),
      'glpi_monitors' => array(array('from' => 'size', 'to' => 'size', 'default' =>0,'noindex'=>true),//
                     ),
      'glpi_printers' => array(array('from' => 'initial_pages', 'to' => 'init_pages_counter', 'default' =>0,'noindex'=>true,'checkdatas'=>true),//
                     ),
      'glpi_profiles' => array(array('from' => 'helpdesk_hardware', 'to' => 'helpdesk_hardware', 'default' =>0,'noindex'=>true),//
                     ),
      'glpi_plugins' => array(array('from' => 'state', 'to' => 'state', 'default' =>0,'comments'=>'see define.php PLUGIN_* constant'),//
                     ),
      'glpi_reminders' => array(array('from' => 'state', 'to' => 'state', 'default' =>0,),//
                     ),
      'glpi_ticketplannings' => array(array('from' => 'state', 'to' => 'state', 'default' =>1,),//
                     ),
      'glpi_rulecriterias' => array(array('from' => 'condition', 'to' => 'condition', 'default' =>0,'comments'=>'see define.php PATTERN_* and REGEX_* constant'),//
                     ),
      'glpi_rules' => array(array('from' => 'sub_type', 'to' => 'sub_type', 'default' =>0,'comments'=>'see define.php RULE_* constant'),//
                     ),
      'glpi_tickets' => array(array('from' => 'request_type', 'to' => 'request_type', 'default' =>0,'noindex'=>true,),//
                        array('from' => 'priority', 'to' => 'priority', 'default' =>1,'noindex'=>true,),//
                     ),
      'glpi_transfers' => array(array('from' => 'keep_tickets', 'to' => 'keep_ticket', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'keep_networklinks', 'to' => 'keep_networklink', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'keep_reservations', 'to' => 'keep_reservation', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'keep_history', 'to' => 'keep_history', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'keep_devices', 'to' => 'keep_device', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'keep_infocoms', 'to' => 'keep_infocom', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'keep_dc_monitor', 'to' => 'keep_dc_monitor', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'clean_dc_monitor', 'to' => 'clean_dc_monitor', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'keep_dc_phone', 'to' => 'keep_dc_phone', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'clean_dc_phone', 'to' => 'clean_dc_phone', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'keep_dc_peripheral', 'to' => 'keep_dc_peripheral', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'clean_dc_peripheral', 'to' => 'clean_dc_peripheral', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'keep_dc_printer', 'to' => 'keep_dc_printer', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'clean_dc_printer', 'to' => 'clean_dc_printer', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'keep_enterprises', 'to' => 'keep_supplier', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'clean_enterprises', 'to' => 'clean_supplier', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'keep_contacts', 'to' => 'keep_contact', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'clean_contacts', 'to' => 'clean_contact', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'keep_contracts', 'to' => 'keep_contract', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'clean_contracts', 'to' => 'clean_contract', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'keep_softwares', 'to' => 'keep_software', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'clean_softwares', 'to' => 'clean_software', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'keep_documents', 'to' => 'keep_document', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'clean_documents', 'to' => 'clean_document', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'keep_cartridges_type', 'to' => 'keep_cartridgeitem', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'clean_cartridges_type', 'to' => 'clean_cartridgeitem', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'keep_cartridges', 'to' => 'keep_cartridge', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'keep_consumables', 'to' => 'keep_consumable', 'default' =>0, 'noindex'=>true),//
                              ),
      'glpi_users' => array(array('from' => 'dateformat', 'to' => 'date_format', 'default' =>NULL, 'noindex'=>true, 'maybenull'=>true),//
                              array('from' => 'numberformat', 'to' => 'number_format', 'default' =>NULL, 'noindex'=>true, 'maybenull'=>true),//
                              array('from' => 'use_mode', 'to' => 'use_mode', 'default' =>0, 'noindex'=>true),//
                              array('from' => 'dropdown_limit', 'to' => 'dropdown_chars_limit', 'default' =>NULL, 'maybenull'=>true, 'noindex'=>true),//
                              ),
                     );
   foreach ($intfields as $table => $tab) {
      foreach ($tab as $update) {
         $newname=$update['to'];
         $oldname=$update['from'];
         $doindex=true;
         if (isset($update['noindex']) && $update['noindex']) {
            $doindex=false;
         }

         $default="DEFAULT NULL";
         if (isset($update['default']) && !is_null($update['default'])) {
            $default="DEFAULT ".$update['default']."";
         }

         $NULL="NOT NULL";
         if (isset($update['maybenull']) && $update['maybenull']) {
            $NULL="NULL";
         }
         $check_datas=false;
         if (isset($update['checkdatas']) ) {
            $check_datas=$update['checkdatas'];
         }
         $addcomment="";
         if (isset($update['comments']) ) {
            $addcomment="COMMENT '".$update['comments']."'";
         }

         // Rename field
         if (FieldExists($table, $oldname)) {
            if ($check_datas) {
               $query="SELECT id, $oldname FROM $table;";
               if ($result=$DB->query($query)) {
                  if ($DB->numrows($result)>0) {
                     while ($data = $DB->fetch_assoc($result)) {
                        if (empty($data[$oldname]) && isset($update['default'])) {
                           $data[$oldname]=$update['default'];
                        }
                        $query="UPDATE $table SET $oldname='".intval($data[$oldname])."' WHERE id = ".$data['id'].";";
                        $DB->query($query);
                     }
                  }
               }
            }
            $changes[$table][]="CHANGE `$oldname` `$newname` INT( 11 ) $NULL $default $addcomment";

         } else {
            echo "<div class='red'><p>Error : $table.$oldname does not exist.</p></div>";
         }
         // If do index : delete old one / create new one
         if ($doindex) {
            if (!isIndex($table, $newname)) {
               $changes[$table][]="ADD INDEX `$newname` (`$newname`)";
            }
            if ($newname!=$oldname && isIndex($table, $oldname)) {
               $changes[$table][]="DROP INDEX `$oldname`";
            }
         }
      }
   }

   displayMigrationMessage("080", $LANG['update'][141] . ' - Clean DB : others field changes'); // Updating schema

   if (FieldExists('glpi_alerts', 'date')) {
      $changes['glpi_alerts'][]="CHANGE `date` `date` DATETIME NOT NULL";
   }
   if (FieldExists('glpi_configs', 'date_fiscale')) {
      $changes['glpi_configs'][]="CHANGE `date_fiscale` `date_tax` DATE NOT NULL DEFAULT '2005-12-31'";
   }

   if (FieldExists('glpi_configs', 'sendexpire')) {
      $changes['glpi_configs'][]="DROP `sendexpire`";
   }
   if (FieldExists('glpi_configs', 'show_admin_doc')) {
      $changes['glpi_configs'][]="DROP `show_admin_doc`";
   }
   if (FieldExists('glpi_configs', 'licenses_management_restrict')) {
      $changes['glpi_configs'][]="DROP `licenses_management_restrict`";
   }
   if (FieldExists('glpi_configs', 'nextprev_item')) {
      $changes['glpi_configs'][]="DROP `nextprev_item`";
   }

   if (FieldExists('glpi_configs', 'logotxt')) {
      $changes['glpi_configs'][]="DROP `logotxt`";
   }

   if (FieldExists('glpi_configs', 'num_of_events')) {
      $changes['glpi_configs'][]="DROP `num_of_events`";
   }

   if (FieldExists('glpi_configs', 'tracking_order')) {
      $changes['glpi_configs'][]="DROP `tracking_order`";
   }

   if (FieldExists('glpi_contracts', 'bill_type')) {
      $changes['glpi_contracts'][]="DROP `bill_type`";
   }

   if (FieldExists('glpi_infocoms', 'amort_coeff')) {
      $changes['glpi_infocoms'][]="CHANGE `amort_coeff` `sink_coeff` FLOAT NOT NULL DEFAULT '0'";
   }

   if (FieldExists('glpi_ocsservers', 'import_software_comments')) {
      $changes['glpi_ocsservers'][]="DROP `import_software_comments`";
   }

   if (FieldExists('glpi_users', 'nextprev_item')) {
      $changes['glpi_users'][]="DROP `nextprev_item`";
   }

   if (FieldExists('glpi_users', 'num_of_events')) {
      $changes['glpi_users'][]="DROP `num_of_events`";
   }

   if (FieldExists('glpi_users', 'tracking_order')) {
      $changes['glpi_users'][]="DROP `tracking_order`";
   }

   if (FieldExists('glpi_ruleldapparameters', 'sub_type')) {
      $changes['glpi_ruleldapparameters'][]="DROP `sub_type`";
   }

   if (FieldExists('glpi_softwares', 'oldstate')) {
      $changes['glpi_softwares'][]="DROP `oldstate`";
   }

   if (FieldExists('glpi_users', 'password')) {
      $changes['glpi_users'][]="DROP `password`";
   }

   if (FieldExists('glpi_users', 'password_md5')) {
      $changes['glpi_users'][]="CHANGE `password_md5` `password` CHAR( 32 )  NULL DEFAULT NULL";
   }

   if (!FieldExists('glpi_mailcollectors', 'filesize_max')) {
      $changes['glpi_mailcollectors'][]="ADD `filesize_max` INT(11) NOT NULL DEFAULT 2097152";
   }


   displayMigrationMessage("080", $LANG['update'][141] . ' - Clean DB : index management'); // Updating schema

   if (!isIndex('glpi_alerts', 'unicity')) {
      $changes['glpi_alerts'][]="ADD UNIQUE `unicity` ( `itemtype` , `items_id` , `type` )";
   }

   if (!isIndex('glpi_cartridges_printermodels', 'unicity')) {
      $changes['glpi_cartridges_printermodels'][]="ADD UNIQUE `unicity` ( `printermodels_id` , `cartridgeitems_id`)";
   }

   if (!isIndex('glpi_computers_items', 'unicity')) {
      $changes['glpi_computers_items'][]="ADD UNIQUE `unicity` ( `itemtype` , `items_id`, `computers_id`)";
  }

   if (!isIndex('glpi_contacts_suppliers', 'unicity')) {
      $changes['glpi_contacts_suppliers'][]="ADD UNIQUE `unicity` ( `suppliers_id` , `contacts_id`)";
  }

   if (!isIndex('glpi_contracts_items', 'unicity')) {
      $changes['glpi_contracts_items'][]="ADD UNIQUE `unicity` ( `contracts_id` ,  `itemtype` , `items_id`)";
   }

   if (!isIndex('glpi_contracts_items', 'item')) {
      $changes['glpi_contracts_items'][]="ADD INDEX `item` ( `itemtype` , `items_id`)";
   }

   if (!isIndex('glpi_contracts_suppliers', 'unicity')) {
      $changes['glpi_contracts_suppliers'][]="ADD UNIQUE `unicity` ( `suppliers_id` , `contracts_id`)";
   }

   if (!isIndex('glpi_displaypreferences', 'unicity')) {
      $changes['glpi_displaypreferences'][]="ADD UNIQUE `unicity` ( `users_id` , `itemtype`, `num`)";
   }

   if (!isIndex('glpi_bookmarks_users', 'unicity')) {
      $changes['glpi_bookmarks_users'][]="ADD UNIQUE `unicity` ( `users_id` , `itemtype`)";
   }

   if (!isIndex('glpi_documents_items', 'unicity')) {
      $changes['glpi_documents_items'][]="ADD UNIQUE `unicity` ( `documents_id` , `itemtype`, `items_id`)";
   }

   if (!isIndex('glpi_documents_items', 'item')) {
      $changes['glpi_documents_items'][]="ADD INDEX `item` (  `itemtype`, `items_id`)";
   }

   if (!isIndex('glpi_knowbaseitemcategories', 'unicity')) {
      $changes['glpi_knowbaseitemcategories'][]="ADD UNIQUE `unicity` ( `knowbaseitemcategories_id` , `name`) ";
   }

   if (!isIndex('glpi_locations', 'unicity')) {
      $changes['glpi_locations'][]="ADD UNIQUE `unicity` ( `entities_id`, `locations_id` , `name`) ";
   }

   if (isIndex('glpi_locations', 'name')) {
      $changes['glpi_locations'][]="DROP INDEX `name` ";
   }

   if (!isIndex('glpi_netpoints', 'complete')) {
      $changes['glpi_netpoints'][]="ADD INDEX `complete` (`entities_id`,`locations_id`,`name`) ";
   }

   if (!isIndex('glpi_netpoints', 'location_name')) {
      $changes['glpi_netpoints'][]="ADD INDEX `location_name` (`locations_id`,`name`)";
   }

   if (!isIndex('glpi_entities', 'unicity')) {
      $changes['glpi_entities'][]="ADD UNIQUE `unicity` (`entities_id`,`name`)  ";
   }

   if (!isIndex('glpi_entitydatas', 'unicity')) {
      $changes['glpi_entitydatas'][]="ADD UNIQUE `unicity` (`entities_id`) ";
   }

   if (!isIndex('glpi_events', 'item')) {
      $changes['glpi_events'][]="ADD INDEX `item` (`type`,`items_id`) ";
   }

   if (!isIndex('glpi_logs', 'item')) {
      $changes['glpi_logs'][]="ADD INDEX `item` (`itemtype`,`items_id`)";
   }

   if (!isIndex('glpi_infocoms', 'unicity')) {
      $changes['glpi_infocoms'][]="ADD UNIQUE `unicity` (`itemtype`,`items_id`)  ";
   }
   if (!isIndex('glpi_knowbaseitems', 'date_mod')) {
      $changes['glpi_knowbaseitems'][]="ADD INDEX `date_mod` (`date_mod`) ";
   }

   if (!isIndex('glpi_networkequipments', 'date_mod')) {
      $changes['glpi_networkequipments'][]="ADD INDEX `date_mod` (`date_mod`)  ";
   }

   if (!isIndex('glpi_links_itemtypes', 'unicity')) {
      $changes['glpi_links_itemtypes'][]="ADD UNIQUE `unicity` (`itemtype`,`links_id`)   ";
   }

   if (!isIndex('glpi_mailingsettings', 'unicity')) {
      $changes['glpi_mailingsettings'][]="ADD UNIQUE `unicity` (`type`,`items_id`,`mailingtype`)  ";
   }

   if (!isIndex('glpi_networkports', 'item')) {
      $changes['glpi_networkports'][]="ADD INDEX `item` (`itemtype`,`items_id`) ";
   }

   if (!isIndex('glpi_networkports_vlans', 'unicity')) {
      $changes['glpi_networkports_vlans'][]="ADD UNIQUE `unicity` (`networkports_id`,`vlans_id`) ";
   }

   if (!isIndex('glpi_networkports_networkports', 'unicity')) {
      $changes['glpi_networkports_networkports'][]="ADD UNIQUE `unicity` (`networkports_id_1`,`networkports_id_2`)  ";
   }

   if (!isIndex('glpi_ocslinks', 'unicity')) {
      $changes['glpi_ocslinks'][]="ADD UNIQUE `unicity` (`ocsservers_id`,`ocsid`)   ";
   }

   if (!isIndex('glpi_peripherals', 'date_mod')) {
      $changes['glpi_peripherals'][]="ADD INDEX `date_mod` (`date_mod`)  ";
   }

   if (!isIndex('glpi_phones', 'date_mod')) {
      $changes['glpi_phones'][]="ADD INDEX `date_mod` (`date_mod`)  ";
   }

   if (!isIndex('glpi_plugins', 'unicity')) {
      $changes['glpi_plugins'][]="ADD UNIQUE `unicity` (`directory`)   ";
   }

   if (!isIndex('glpi_printers', 'date_mod')) {
      $changes['glpi_printers'][]="ADD INDEX `date_mod` (`date_mod`)  ";
   }

   if (!isIndex('glpi_reminders', 'date_mod')) {
      $changes['glpi_reminders'][]="ADD INDEX `date_mod` (`date_mod`)  ";
   }

   if (!isIndex('glpi_reservationitems', 'item')) {
      $changes['glpi_reservationitems'][]="ADD INDEX `item` (`itemtype`,`items_id`)   ";
   }

   if (!isIndex('glpi_tickets', 'item')) {
      $changes['glpi_tickets'][]="ADD INDEX `item` (`itemtype`,`items_id`)  ";
   }

   if (!isIndex('glpi_documenttypes', 'date_mod')) {
      $changes['glpi_documenttypes'][]="ADD INDEX `date_mod` (`date_mod`)  ";
   }

   if (!isIndex('glpi_documenttypes', 'unicity')) {
      $changes['glpi_documenttypes'][]="ADD UNIQUE `unicity` (`ext`)  ";
   }
   if (!isIndex('glpi_users', 'unicity')) {
      $changes['glpi_users'][]="ADD UNIQUE `unicity` (`name`)  ";
   }
   if (!isIndex('glpi_users', 'date_mod')) {
      $changes['glpi_users'][]="ADD INDEX `date_mod` (`date_mod`)  ";
   }
   if (!isIndex('glpi_users', 'authitem')) {
      $changes['glpi_users'][]="ADD INDEX `authitem` (`authtype`,`auths_id`) ";
   }
   if (!isIndex('glpi_groups_users', 'unicity')) {
      $changes['glpi_groups_users'][]="ADD UNIQUE `unicity` (`users_id`,`groups_id`)  ";
   }

   $indextodrop=array(
         'glpi_alerts' => array('alert','FK_device'),
         'glpi_cartridges_printermodels' => array('FK_glpi_type_printer'),
         'glpi_computers_devices' => array('FK_device'),
         'glpi_computers_items' => array('connect','type','end1','end1_2'),
         'glpi_consumables' => array('FK_glpi_cartridges_type'),
         'glpi_contacts_suppliers' => array('FK_enterprise'),
         'glpi_contracts_items' => array('FK_contract_device','device_type'),
         'glpi_contracts_suppliers' => array('FK_enterprise'),
         'glpi_displaypreferences' => array('display','FK_users'),
         'glpi_bookmarks_users' => array('FK_users'),
         'glpi_documents_items' => array('FK_doc_device','device_type','FK_device'),
         'glpi_knowbaseitemcategories' => array('parentID_2','parentID'),
         'glpi_locations' => array('FK_entities'),
         'glpi_netpoints' => array('FK_entities','location'),
         'glpi_entities' => array('name'/*,'parentID'*/),
         'glpi_entitydatas' => array('FK_entities'),
         'glpi_events' => array('comp','itemtype'),
         'glpi_logs' => array('FK_glpi_device'),
         'glpi_infocoms' => array('FK_device'),
         'glpi_computers_softwareversions' => array('sID'),
         'glpi_links_itemtypes' => array('link'),
         'glpi_mailingsettings' => array('mailings','FK_item'),
         'glpi_networkports' => array('device_type'),
         'glpi_networkports_vlans' => array('portvlan'),
         'glpi_networkports_networkports' => array('netwire','end1','end1_2'),
         'glpi_ocslinks' => array('ocs_server_id'),
         'glpi_plugins' => array('name'),
         'glpi_reservationitems' => array('reservationitem'),
         'glpi_tickets' => array('computer','device_type'),
         'glpi_documenttypes' => array('extension'),
         'glpi_users' => array('name'),
         'glpi_groups_users' => array('usergroup'),
      );
   foreach ($indextodrop as $table => $tab) {
      foreach ($tab as $indexname) {
         if (isIndex($table, $indexname)) {
            $changes[$table][]="DROP INDEX `$indexname`";
         }
      }
   }

   foreach ($changes as $table => $tab) {
      displayMigrationMessage("080", $LANG['update'][141] . ' - ' . $table); // Updating schema
      $query="ALTER TABLE `$table` ".implode($tab," ,\n").";";
      $DB->query($query) or die("0.80 multiple alter in $table " . $LANG['update'][90] . $DB->error());
   }


   displayMigrationMessage("080", $LANG['update'][141] . ' - Update itemtype fields'); // Updating schema

   // Convert itemtype to Class names
   $typetoname=array(
      GENERAL_TYPE => "",// For tickets
      COMPUTER_TYPE => "Computer",
      NETWORKING_TYPE => "NetworkEquipment",
      PRINTER_TYPE => "Printer",
      MONITOR_TYPE => "Monitor",
      PERIPHERAL_TYPE => "Peripheral",
      SOFTWARE_TYPE => "Software",
      CONTACT_TYPE => "Contact",
      ENTERPRISE_TYPE => "Supplier",
      INFOCOM_TYPE => "Infocom",
      CONTRACT_TYPE => "Contract",
      CARTRIDGEITEM_TYPE => "CartridgeItem",
      TYPEDOC_TYPE => "DocumentType",
      DOCUMENT_TYPE => "Document",
      KNOWBASE_TYPE => "KnowbaseItem",
      USER_TYPE => "User",
      TRACKING_TYPE => "Ticket",
      CONSUMABLEITEM_TYPE => "ConsumableItem",
      CONSUMABLE_TYPE => "Consumable",
      CARTRIDGE_TYPE => "Cartridge",
      SOFTWARELICENSE_TYPE => "SoftwareLicense",
      LINK_TYPE => "Link",
      STATE_TYPE => "State",
      PHONE_TYPE => "Phone",
      DEVICE_TYPE => "Device",
      REMINDER_TYPE => "Reminder",
      STAT_TYPE => "Stat",
      GROUP_TYPE => "Group",
      ENTITY_TYPE => "Entity",
      RESERVATION_TYPE => "ReservationItem",
      AUTH_MAIL_TYPE => "AuthMail",
      AUTH_LDAP_TYPE => "AuthLDAP",
      OCSNG_TYPE => "OcsServer",
      REGISTRY_TYPE => "RegistryKey",
      PROFILE_TYPE => "Profile",
      MAILGATE_TYPE => "MailCollector",
      RULE_TYPE => "Rule",
      TRANSFER_TYPE => "Transfer",
      BOOKMARK_TYPE => "Bookmark",
      SOFTWAREVERSION_TYPE => "SoftwareVersion",
      PLUGIN_TYPE => "Plugin",
      COMPUTERDISK_TYPE => "ComputerDisk",
      NETWORKING_PORT_TYPE => "NetworkPort",
      FOLLOWUP_TYPE => "TicketFollowup",
      BUDGET_TYPE => "Budget",
      // End is not used in 0.72.x
   );

   $itemtype_tables=array("glpi_alerts", "glpi_bookmarks", "glpi_bookmarks_users",
      "glpi_computers_items", "glpi_contracts_items", "glpi_displaypreferences",
      "glpi_documents_items", "glpi_infocoms", "glpi_links_itemtypes", "glpi_logs",
      "glpi_networkports", "glpi_reservationitems", "glpi_tickets",
      );

   foreach ($itemtype_tables as $table) {
      // Alter itemtype field
      $query = "ALTER TABLE `$table` CHANGE `itemtype` `itemtype` VARCHAR( 100 ) NOT NULL";
      $DB->query($query) or die("0.80 alter itemtype of table $table " . $LANG['update'][90] . $DB->error());

      // Update values
      foreach ($typetoname as $key => $val) {
         $query = "UPDATE `$table` SET `itemtype` = '$val' WHERE `itemtype` = '$key'";
         $DB->query($query) or die("0.80 update itemtype of table $table for $val " . $LANG['update'][90] . $DB->error());
      }
   }

   // Update glpi_profiles item_type

   displayMigrationMessage("080", $LANG['update'][141] . ' - Clean DB : post actions after renaming'); // Updating schema

   if (!isIndex('glpi_locations', 'name')) {
      $query=" ALTER TABLE `glpi_locations` ADD INDEX `name` (`name`)";
      $DB->query($query) or die("0.80 add name index in glpi_locations " . $LANG['update'][90] . $DB->error());
   }


   // Update values of mailcollectors
   $query="SELECT default_mailcollector_filesize_max FROM glpi_configs WHERE id=1";
   if ($result=$DB->query($query)) {
      if ($DB->numrows($result)>0) {
         $query="UPDATE glpi_mailcollectors SET filesize_max='".$DB->result($result,0,0)."';";
         $DB->query($query);
      }
   }


   // For compatiblity with updates from past versions
   regenerateTreeCompleteName("glpi_locations");
   regenerateTreeCompleteName("glpi_knowbaseitemcategories");
   regenerateTreeCompleteName("glpi_ticketcategories");

   // Update timezone values
   if (FieldExists('glpi_configs', 'time_offset')) {
      $query="UPDATE glpi_configs SET time_offset=time_offset*3600";
      $DB->query($query) or die("0.80 update time_offset value in glpi_configs " . $LANG['update'][90] . $DB->error());
   }
   if (FieldExists('glpi_authldaps', 'time_offset')) {
      $query="UPDATE glpi_authldaps SET time_offset=time_offset*3600";
      $DB->query($query) or die("0.80 update time_offset value in glpi_authldaps " . $LANG['update'][90] . $DB->error());
   }


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
               "assign_group","assign_ent","recipient","contents","name_contents");

      $news   = array("ticketcategories_id", "itemtype", "ice users_id","users_id_assign",
               "groups_id_assign","suppliers_id_assign","users_id_recipient","content","name_content");

      foreach ($olds as $key => $val) {
         $olds[$key]="&$val=";
      }
      foreach ($news as $key => $val) {
         $news[$key]="&$val=";
      }

      $query="SELECT id, query FROM glpi_bookmarks WHERE type=".BOOKMARK_SEARCH." AND itemtype=".TRACKING_TYPE.";";
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            while ($data = $DB->fetch_assoc($result)) {
               $query2="UPDATE glpi_bookmarks SET query='".addslashes(str_replace($olds,$news,$data['query']))."' WHERE id=".$data['id'].";";
               $DB->query($query2) or die("0.80 update tracking bookmarks " . $LANG['update'][90] . $DB->error());
            }
         }
      }
      // All search
      $olds = array("deleted",);

      $news   = array("is_deleted",);
      foreach ($olds as $key => $val) {
         $olds[$key]="&$val=";
      }
      foreach ($news as $key => $val) {
         $news[$key]="&$val=";
      }
      $query="SELECT id, query FROM glpi_bookmarks WHERE type=".BOOKMARK_SEARCH." ;";
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            while ($data = $DB->fetch_assoc($result)) {
               $query2="UPDATE glpi_bookmarks SET query='".addslashes(str_replace($olds,$news,$data['query']))."' WHERE id=".$data['id'].";";
               $DB->query($query2) or die("0.80 update all bookmarks " . $LANG['update'][90] . $DB->error());
            }
         }
      }

      // Update bookmarks due to FHS change
      $query2="UPDATE glpi_bookmarks SET path='front/documenttype.php' WHERE path='front/typedoc.php';";
      $DB->query($query2) or die("0.80 update typedoc bookmarks " . $LANG['update'][90] . $DB->error());
      $query2="UPDATE glpi_bookmarks SET path='front/consumableitem.php' WHERE path='front/consumable.php';";
      $DB->query($query2) or die("0.80 update consumable bookmarks " . $LANG['update'][90] . $DB->error());
      $query2="UPDATE glpi_bookmarks SET path='front/cartridgeitem.php' WHERE path='front/cartridge.php';";
      $DB->query($query2) or die("0.80 update cartridge bookmarks " . $LANG['update'][90] . $DB->error());
      $query2="UPDATE glpi_bookmarks SET path='front/ticket.php' WHERE path='front/tracking.php';";
      $DB->query($query2) or die("0.80 update ticket bookmarks " . $LANG['update'][90] . $DB->error());
      $query2="UPDATE glpi_bookmarks SET path='front/mailcollector.php' WHERE path='front/mailgate.php';";
      $DB->query($query2) or die("0.80 update mailcollector bookmarks " . $LANG['update'][90] . $DB->error());
      $query2="UPDATE glpi_bookmarks SET path='front/ocsserver.php' WHERE path='front/setup.ocsng.php';";
      $DB->query($query2) or die("0.80 update ocsserver bookmarks " . $LANG['update'][90] . $DB->error());
      $query2="UPDATE glpi_bookmarks SET path='front/supplier.php' WHERE path='front/enterprise.php';";
      $DB->query($query2) or die("0.80 update supplier bookmarks " . $LANG['update'][90] . $DB->error());
      $query2="UPDATE glpi_bookmarks SET path='front/networkequipment.php' WHERE path='front/networking.php';";
      $DB->query($query2) or die("0.80 update networkequipment bookmarks " . $LANG['update'][90] . $DB->error());
      $query2="UPDATE glpi_bookmarks SET path='front/states.php' WHERE path='front/state.php';";
      $DB->query($query2) or die("0.80 update states bookmarks " . $LANG['update'][90] . $DB->error());

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
   $changes[RULE_SOFTWARE_CATEGORY]=array('category'=>'softwarecategories_id','comment'=>'comment');
   // For RULE_TRACKING_AUTO_ACTION
   $changes[RULE_TRACKING_AUTO_ACTION]=array('category'        => 'ticketcategories_id',
                                             'author'          => 'users_id',
                                             'author_location' => 'users_locations',
                                             'FK_group'        => 'groups_id',
                                             'assign'          => 'users_id_assign',
                                             'assign_group'    => 'groups_id_assign',
                                             'device_type'     => 'itemtype',
                                             'FK_entities'     => 'entities_id',
                                             'contents'        => 'content',
                                             'request_type'    => 'requesttypes_id');

   $DB->query("SET SESSION group_concat_max_len = 9999999;");
   foreach ($changes as $ruletype => $tab) {
      // Get rules
      $query = "SELECT GROUP_CONCAT(id) FROM glpi_rules WHERE sub_type=".$ruletype." GROUP BY sub_type;";
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            // Get rule string
            $rules=$DB->result($result,0,0);
            // Update actions
            foreach ($tab as $old => $new) {
               $query = "UPDATE glpi_ruleactions SET field='$new' WHERE field='$old' AND rules_id IN ($rules);";
               $DB->query($query) or die("0.80 update datas for rules actions " . $LANG['update'][90] . $DB->error());
            }
            // Update criterias
            foreach ($tab as $old => $new) {
               $query = "UPDATE glpi_rulecriterias SET criteria='$new' WHERE criteria='$old' AND rules_id IN ($rules);";
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
	$query="SELECT DISTINCT users_id FROM glpi_displaypreferences WHERE itemtype=".MAILGATE_TYPE.";";
	if ($result = $DB->query($query)) {
		if ($DB->numrows($result)>0) {
			while ($data = $DB->fetch_assoc($result)) {
				$query="SELECT max(rank) FROM glpi_displaypreferences WHERE users_id='".$data['users_id']."' AND itemtype=".MAILGATE_TYPE.";";
				$result=$DB->query($query);
				$rank=$DB->result($result,0,0);
				$rank++;
				$query="SELECT * FROM glpi_displaypreferences WHERE users_id='".$data['users_id']."' AND num=2 AND itemtype=".MAILGATE_TYPE.";";
				if ($result2=$DB->query($query)) {
					if ($DB->numrows($result2)==0) {
						$query="INSERT INTO glpi_displaypreferences (itemtype ,`num` ,`rank` ,users_id) VALUES ('".MAILGATE_TYPE."', '2', '".$rank++."', '".$data['users_id']."');";
						$DB->query($query);
					}
				}
			}
		}
	}


   displayMigrationMessage("080", $LANG['update'][141] . ' - glpi_rulescachesoftwares'); // Updating schema

	if (FieldExists("glpi_rulescachesoftwares","ignore_ocs_import")) {
		$query = "ALTER TABLE `glpi_rulescachesoftwares` CHANGE `ignore_ocs_import` `ignore_ocs_import` CHAR( 1 ) NULL DEFAULT NULL ";
      $DB->query($query) or die("0.80 alter table glpi_rulescachesoftwares " . $LANG['update'][90] . $DB->error());
	}
	if (!FieldExists("glpi_rulescachesoftwares","is_helpdesk_visible")) {
		$query = "ALTER TABLE `glpi_rulescachesoftwares` ADD `is_helpdesk_visible` CHAR( 1 ) NULL ";
      $DB->query($query) or die("0.80 add is_helpdesk_visible index in glpi_rulescachesoftwares " . $LANG['update'][90] . $DB->error());
	}

   displayMigrationMessage("080", $LANG['update'][141] . ' - glpi_entities'); // Updating schema

   if (!FieldExists("glpi_entities","sons_cache")) {
      $query = "ALTER TABLE `glpi_entities` ADD `sons_cache` LONGTEXT NULL ; ";
      $DB->query($query) or die("0.80 add sons_cache field in glpi_entities " . $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists("glpi_entities","ancestors_cache")) {
      $query = "ALTER TABLE `glpi_entities` ADD `ancestors_cache` LONGTEXT NULL ; ";
      $DB->query($query) or die("0.80 add ancestors_cache field in glpi_entities " . $LANG['update'][90] . $DB->error());
   }


   displayMigrationMessage("080", $LANG['update'][141] . ' - glpi_configs'); // Updating schema

   if (FieldExists("glpi_configs","use_cache")) {
      $query = "ALTER TABLE `glpi_configs`  DROP `use_cache`;";
      $DB->query($query) or die("0.80 drop use_cache in glpi_configs " . $LANG['update'][90] . $DB->error());
   }

   if (FieldExists("glpi_configs","cache_max_size")) {
      $query = "ALTER TABLE `glpi_configs`  DROP `cache_max_size`;";
      $DB->query($query) or die("0.80 drop cache_max_size in glpi_configs " . $LANG['update'][90] . $DB->error());
   }

	if (!FieldExists("glpi_configs","default_request_type")) {
		$query = "ALTER TABLE `glpi_configs` ADD `default_request_type` INT( 11 ) NOT NULL DEFAULT 1";
      $DB->query($query) or die("0.80 add default_request_type index in glpi_configs " . $LANG['update'][90] . $DB->error());
	}

	if (!FieldExists("glpi_users","default_request_type")) {
		$query = "ALTER TABLE `glpi_users` ADD `default_request_type` INT( 11 ) NULL";
      $DB->query($query) or die("0.80 add default_request_type index in glpi_users " . $LANG['update'][90] . $DB->error());
	}

	if (!FieldExists("glpi_configs","use_noright_users_add")) {
		$query = "ALTER TABLE `glpi_configs` ADD `use_noright_users_add` tinyint( 1 ) NOT NULL DEFAULT '1'";
      $DB->query($query) or die("0.80 add use_noright_users_add index in glpi_configs " . $LANG['update'][90] . $DB->error());
	}

	displayMigrationMessage("080", $LANG['update'][141] . ' - glpi_budgets'); // Updating schema

	if (!FieldExists("glpi_profiles","budget")) {
		$query = "ALTER TABLE `glpi_profiles` ADD `budget` CHAR( 1 ) NULL ";
		$DB->query($query) or die("0.80 add budget index in glpi_profiles" . $LANG['update'][90] . $DB->error());

		$query = "UPDATE `glpi_profiles` SET `budget`='w' WHERE `name` IN ('super-admin','admin')";
		$DB->query($query) or die("0.80 add budget write right to super-admin and admin profiles" . $LANG['update'][90] . $DB->error());

		$query = "UPDATE `glpi_profiles` SET `budget`='r' WHERE `name`='normal'";
		$DB->query($query) or die("0.80 add budget write right to super-admin and admin profiles" . $LANG['update'][90] . $DB->error());

	}


   if (!FieldExists("glpi_budgets","is_recursive")) {
      $query = "ALTER TABLE `glpi_budgets` ADD `is_recursive` tinyint(1) NOT NULL DEFAULT '0' AFTER `name`";
      $DB->query($query) or die("0.80 add is_recursive field in glpi_budgets" . $LANG['update'][90] . $DB->error());

      // budgets in 0.72 were global
      $query = "UPDATE `glpi_budgets` SET `is_recursive` = '1';";
      $DB->query($query) or die("0.80 set is_recursive to true in glpi_budgets" . $LANG['update'][90] . $DB->error());
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

   if (!FieldExists("glpi_budgets","template_name")) {
      $query = "ALTER TABLE `glpi_budgets`  ADD `template_name` varchar(255) default NULL";
      $DB->query($query) or die("0.80 add template_name field in glpi_budgets" . $LANG['update'][90] . $DB->error());
   }

   displayMigrationMessage("080", $LANG['update'][141] . ' - ' . $LANG['crontask'][0]); // Updating schema
   if (!TableExists('glpi_crontasks')) {
      $query = "CREATE TABLE `glpi_crontasks` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `plugin` char(78) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'NULL (glpi) or plugin name',
        `itemtype` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
        `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'task name',
        `frequency` int(11) NOT NULL COMMENT 'second between launch',
        `param` int(11) DEFAULT NULL COMMENT 'task specify parameter',
        `state` int(11) NOT NULL DEFAULT '1' COMMENT '0:disabled, 1:waiting, 2:running',
        `mode` int(11) NOT NULL DEFAULT '1' COMMENT '1:internal, 2:external',
        `allowmode` int(11) NOT NULL DEFAULT '3' COMMENT '1:internal, 2:external, 3:both',
        `hourmin` int(11) NOT NULL DEFAULT '0',
        `hourmax` int(11) NOT NULL DEFAULT '24',
        `logs_lifetime` int(11) NOT NULL DEFAULT '30' COMMENT 'number of days',
        `lastrun` datetime DEFAULT NULL COMMENT 'last run date',
        `lastcode` int(11) DEFAULT NULL COMMENT 'last run return code',
        `comment` text COLLATE utf8_unicode_ci,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unicity` (`plugin`,`name`)
      ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
        COMMENT='Task run by internal / external cron.';";
      $DB->query($query) or die("0.80 create glpi_crontasks" . $LANG['update'][90] . $DB->error());

      $query="INSERT INTO `glpi_crontasks`
         (`id`, `plugin`, `itemtype`, `name`, `frequency`, `param`, `state`, `mode`, `allowmode`, `hourmin`, `hourmax`, `logs_lifetime`, `lastrun`, `lastcode`, `comment`)
         VALUES
         (1,  NULL, NULL, 'ocsng', 300, NULL, 1, 1, 3, 0, 24, 30, NULL, NULL, NULL),
         (2,  NULL, 'CartridgeItem', 'cartridge', 86400, 10, 0, 1, 3, 0, 24, 30, NULL, NULL, NULL),
         (3,  NULL, 'ConsumableItem', 'consumable', 86400, 10, 0, 1, 3, 0, 24, 30, NULL, NULL, NULL),
         (4,  NULL, 'SoftwareLicense', 'software', 86400, NULL, 0, 1, 3, 0, 24, 30, NULL, NULL, NULL),
         (5,  NULL, 'Contract', 'contract', 86400, NULL, 1, 1, 3, 0, 24, 30, NULL, NULL, NULL),
         (6,  NULL, 'InfoCom', 'infocom', 86400, NULL, 1, 1, 3, 0, 24, 30, NULL, NULL, NULL),
         (7,  NULL, 'CronTask', 'logs', 86400, 10, 1, 1, 3, 0, 24, 30, NULL, NULL, NULL),
         (8,  NULL, 'CronTask', 'optimize', 604800, NULL, 1, 1, 3, 0, 24, 30, NULL, NULL, NULL),
         (9,  NULL, 'MailCollector', 'mailgate', 600, 10, 1, 1, 3, 0, 24, 30, NULL, NULL, NULL),
         (10, NULL, 'DBconnection', 'check_dbreplicate', 300, NULL, 0, 0, 3, 0, 24, 30, NULL, NULL, NULL),
         (11, NULL, 'CronTask', 'check_update', 604800, NULL, 0, 1, 3, 0, 24, 30, NULL, NULL, NULL),
         (12, NULL, 'CronTask', 'session', 86400, NULL, 1, 1, 3, 0, 24, 30, NULL, NULL, NULL);";
      $DB->query($query) or die("0.80 populate glpi_crontasks" . $LANG['update'][90] . $DB->error());

      $query="INSERT INTO `glpi_displaypreferences` (`itemtype`, `num`, `rank`, `users_id`)
         VALUES ('Crontask', 8, 1, 0), ('Crontask', 3, 2, 0),
                ('Crontask', 4, 3, 0),  ('Crontask', 7, 4, 0);";
      $DB->query($query) or die("0.80 populate glpi_displaypreferences for glpi_crontasks" . $LANG['update'][90] . $DB->error());
   }

   if (!TableExists('glpi_crontasklogs')) {
      $query = "CREATE TABLE `glpi_crontasklogs` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `crontasks_id` int(11) NOT NULL,
        `crontasklogs_id` int(11) NOT NULL COMMENT 'id of ''start'' event',
        `date` datetime NOT NULL,
        `state` int(11) NOT NULL COMMENT '0:start, 1:run, 2:stop',
        `elapsed` float NOT NULL COMMENT 'time elapsed since start',
        `volume` int(11) NOT NULL COMMENT 'for statistics',
        `content` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'message',
        PRIMARY KEY (`id`),
        KEY `crontasks_id` (`crontasks_id`),
        KEY `crontasklogs_id` (`crontasklogs_id`),
        KEY `crontasklogs_id_state` (`crontasklogs_id`,`state`)
      ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;";
      $DB->query($query) or die("0.80 create glpi_crontasklogs" . $LANG['update'][90] . $DB->error());
   }
   // Retrieve core task lastrun date
   $tasks=array('ocsng','cartridge','consumable','software','contract','infocom',
               'logs','optimize','mailgate','DBConnection','check_update','session');
   foreach ($tasks as $task) {
      $lock=GLPI_CRON_DIR. '/' . $task . '.lock';
      if (is_readable($lock) && $stat=stat($lock)) {
         $DB->query("UPDATE `glpi_crontasks` SET `lastrun`='".date('Y-m-d H:i:s',$stat['mtime'])."'
                     WHERE `name`='$task'");
         unlink($lock);
      }
   }
   // Clean plugin lock
   foreach(glob(GLPI_CRON_DIR. '/*.lock') as $lock) {
      unlink($lock);
   }

   // disable ocsng cron if not activate
   if (FieldExists('glpi_configs','use_ocs_mode')) {
      $query="SELECT `use_ocs_mode` FROM `glpi_configs` WHERE `id`=1";
      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)>0) {
            $value=$DB->result($result,0,0);
            if ($value==0) {
               $query="UPDATE `glpi_crontasks` SET `state`='0' WHERE `name`='ocsng';";
               $DB->query($query);
            }
         }
      }
   }


   // Move glpi_config.expire_events to glpi_crontasks.param
   if (FieldExists('glpi_configs','expire_events')) {
      $query="SELECT `expire_events` FROM `glpi_configs` WHERE `id`=1";
      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)>0) {
            $value=$DB->result($result,0,0);
            if ($value>0) {
               $query="UPDATE `glpi_crontasks` SET `state`='1', `param`='$value' WHERE `name`='logs';";
            } else {
               $query="UPDATE `glpi_crontasks` SET `state`='0' WHERE `name`='logs';";
            }
            $DB->query($query);
         }
      }
      $query="ALTER TABLE `glpi_configs` DROP `expire_events`";
      $DB->query($query) or die("0.80 drop expire_events in glpi_configs" . $LANG['update'][90] . $DB->error());
   }

   // Move glpi_config.auto_update_check to glpi_crontasks.state
   if (FieldExists('glpi_configs','auto_update_check')) {
      $query="SELECT `auto_update_check` FROM `glpi_configs` WHERE id=1";
      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)>0) {
            $value=$DB->result($result,0,0);
            if ($value>0) {
               $value *= DAY_TIMESTAMP;
               $query="UPDATE `glpi_crontasks` SET `state`='1', `frequency`='$value' WHERE `name`='check_update';";
            } else {
               $query="UPDATE `glpi_crontasks` SET `state`='0' WHERE `name`='logs';";
            }
            $DB->query($query);
         }
      }
      $query="ALTER TABLE `glpi_configs` DROP `auto_update_check`";
      $DB->query($query) or die("0.80 drop auto_update_check in check_update" . $LANG['update'][90] . $DB->error());
   }

   if (FieldExists('glpi_configs','dbreplicate_maxdelay')) {
      $query="SELECT `dbreplicate_maxdelay` FROM `glpi_configs` WHERE id=1";
      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)>0) {
            $value=$DB->result($result,0,0);
            $value = intval($value/60);
            $query="UPDATE `glpi_crontasks` SET `state`='1', `frequency`='$value' WHERE `name`='check_dbreplicate';";
            $DB->query($query);
         }
      }
      $query="ALTER TABLE `glpi_configs` DROP `dbreplicate_maxdelay`";
      $DB->query($query) or die("0.80 drop dbreplicate_maxdelay in check_update" . $LANG['update'][90] . $DB->error());
   }
   if (FieldExists('glpi_configs','dbreplicate_notify_desynchronization')) {
      $query="ALTER TABLE `glpi_configs` DROP `dbreplicate_notify_desynchronization`";
      $DB->query($query) or die("0.80 drop dbreplicate_notify_desynchronization in check_update" . $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_configs','cron_limit')) {
      $query="ALTER TABLE `glpi_configs` ADD `cron_limit` TINYINT NOT NULL DEFAULT '1'
                           COMMENT 'Number of tasks execute by external cron'";
      $DB->query($query) or die("0.80 add cron_limit in glpi_configs" . $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_documents','sha1sum')) {
      $query="ALTER TABLE `glpi_documents`
                   ADD `sha1sum` CHAR(40) NULL DEFAULT NULL ,
                   ADD INDEX (`sha1sum`)";
      $DB->query($query) or die("0.80 add sha1sum in glpi_documents" . $LANG['update'][90] . $DB->error());
   }

   if (FieldExists('glpi_documents','filename')) {
        $query="ALTER TABLE `glpi_documents`
                  CHANGE `filename` `filename` VARCHAR( 255 ) NULL DEFAULT NULL
                  COMMENT 'for display and transfert'";
        $DB->query($query) or die("0.80 alter filename in glpi_documents" . $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_documents','filepath')) {
      $query="ALTER TABLE `glpi_documents`
                ADD `filepath` VARCHAR( 255 ) NULL
                COMMENT 'file storage path' AFTER `filename`";
      $DB->query($query) or die("0.80 add filepath in glpi_documents" . $LANG['update'][90] . $DB->error());

      $query = "UPDATE `glpi_documents` SET `filepath`=`filename`";
      $DB->query($query) or die("0.80 set value of filepath in glpi_documents" . $LANG['update'][90] . $DB->error());
   }

   displayMigrationMessage("080", $LANG['update'][141] . ' - ' . $LANG['setup'][79]); // Updating schema
   if (!FieldExists('glpi_ticketcategories','entities_id')) {
      $query = "ALTER TABLE `glpi_ticketcategories`
                    ADD `entities_id` INT NOT NULL DEFAULT '0' AFTER `id`,
                    ADD `is_recursive` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `entities_id`,
                    ADD INDEX (`entities_id`)";
      $DB->query($query) or die("0.80 add entities_id,is_recursive in glpi_ticketcategories" .
                                 $LANG['update'][90] . $DB->error());

      // Set existing categories recursive global
      $query = "UPDATE `glpi_ticketcategories` SET `is_recursive` = '1'";
      $DB->query($query) or die("0.80 set value of is_recursive in glpi_ticketcategories" .
                                $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_ticketcategories','knowbaseitemcategories_id')) {
      $query = "ALTER TABLE `glpi_ticketcategories`
                      ADD `knowbaseitemcategories_id` INT NOT NULL DEFAULT '0',
                      ADD INDEX ( `knowbaseitemcategories_id` )";

      $DB->query($query) or die("0.80 add knowbaseitemcategories_id in glpi_ticketcategories" .
                                 $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_ticketcategories','users_id')) {
      $query = "ALTER TABLE `glpi_ticketcategories`
                        ADD `users_id` INT NOT NULL DEFAULT '0',
                        ADD INDEX ( `users_id` ) ";

      $DB->query($query) or die("0.80 add users_id in glpi_ticketcategories" .
                                 $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_ticketcategories','groups_id')) {
      $query = "ALTER TABLE `glpi_ticketcategories`
                        ADD `groups_id` INT NOT NULL DEFAULT '0',
                        ADD INDEX ( `groups_id` ) ";

      $DB->query($query) or die("0.80 add groups_id in glpi_ticketcategories" .
                                 $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_ticketcategories','ancestors_cache')) {
      $query = "ALTER TABLE `glpi_ticketcategories`
                        ADD `ancestors_cache` LONGTEXT NULL,
                        ADD `sons_cache` LONGTEXT NULL";

      $DB->query($query) or die("0.80 add cache in glpi_ticketcategories" .
                                 $LANG['update'][90] . $DB->error());
   }



   // change item type management for helpdesk
   if (FieldExists('glpi_profiles','helpdesk_hardware_type')) {
      $query = "ALTER TABLE `glpi_profiles` ADD `helpdesk_item_type` TEXT NULL DEFAULT NULL AFTER `helpdesk_hardware_type` ;";
      $DB->query($query) or die("0.80 add  helpdesk_item_type in glpi_profiles" .
                                 $LANG['update'][90] . $DB->error());

      $query="SELECT id, helpdesk_hardware_type FROM glpi_profiles";
      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)>0) {
            while ($data=$DB->fetch_assoc($result)) {
               $types=$data['helpdesk_hardware_type'];
               $CFG_GLPI["helpdesk_types"] = array(COMPUTER_TYPE, NETWORKING_TYPE, PRINTER_TYPE, MONITOR_TYPE,
                                       PERIPHERAL_TYPE, SOFTWARE_TYPE, PHONE_TYPE);
               $tostore=array();

               foreach($CFG_GLPI["helpdesk_types"] as $itemtype) {
                  if (pow(2,$itemtype)&$types) {
                     $tostore[]=$typetoname[$itemtype];
                  }
               }
               $query="UPDATE `glpi_profiles`
                     SET `helpdesk_item_type`='".json_encode($tostore)."'
                     WHERE `id`='".$data['id']."'";

               $DB->query($query) or die("0.80 populate helpdesk_item_type" .
                                    $LANG['update'][90] . $DB->error());
            }
         }
      }
      $query = "ALTER TABLE `glpi_profiles` DROP `helpdesk_hardware_type`;";
      $DB->query($query) or die("0.80 drop helpdesk_hardware_type in glpi_profiles" .
                                 $LANG['update'][90] . $DB->error());

   }


   if (!FieldExists('glpi_profiles','helpdesk_status')) {
      $query = "ALTER TABLE `glpi_profiles`
                   ADD `helpdesk_status` TEXT NULL
                        COMMENT 'json encoded array of from/dest allowed status change'
                        AFTER `helpdesk_item_type`";
      $DB->query($query) or die("0.80 add helpdesk_status in glpi_profiles" .
                                 $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_profiles','update_priority')) {
      $query = "ALTER TABLE `glpi_profiles`
                ADD `update_priority` CHAR( 1 ) NULL DEFAULT NULL AFTER `update_ticket`";
      $DB->query($query) or die("0.80 add update_priority in glpi_profiles" .
                                 $LANG['update'][90] . $DB->error());

      $query = "UPDATE `glpi_profiles` SET `update_priority`=`update_ticket`";
      $DB->query($query) or die("0.80 set update_priority in glpi_profiles" .
                                 $LANG['update'][90] . $DB->error());

   }

   if (!TableExists('glpi_taskcategories')) {
      $query = "CREATE TABLE `glpi_taskcategories` (
           `id` int(11) NOT NULL auto_increment,
           `entities_id` int(11) NOT NULL default '0',
           `is_recursive` tinyint(1) NOT NULL default '0',
           `taskcategories_id` int(11) NOT NULL default '0',
           `name` varchar(255) default NULL,
           `completename` text,
           `comment` text,
           `level` int(11) NOT NULL default '0',
           `ancestors_cache` longtext,
           `sons_cache` longtext,
           PRIMARY KEY  (`id`),
           KEY `name` (`name`),
           KEY `taskcategories_id` (`taskcategories_id`),
           KEY `entities_id` (`entities_id`)
         ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
      $DB->query($query) or die("0.80 create glpi_taskcategories" . $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_documenttypes','comment')) {
      $query = "ALTER TABLE `glpi_documenttypes` ADD `comment` TEXT NULL ";
      $DB->query($query) or die("0.80 add comment in glpi_documenttypes" .
                                 $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_locations','is_recursive')) {
      $query = "ALTER TABLE `glpi_locations`
                        ADD `is_recursive` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `entities_id`,
                        ADD `ancestors_cache` LONGTEXT NULL,
                        ADD `sons_cache` LONGTEXT NULL";

      $DB->query($query) or die("0.80 add recursive, cache in glpi_locations" .
                                 $LANG['update'][90] . $DB->error());
   }
   if (!FieldExists('glpi_locations','building')) {
      $query = "ALTER TABLE `glpi_locations` ADD `building` VARCHAR( 255 ) NULL ,
                                             ADD `room` VARCHAR( 255 ) NULL ";

      $DB->query($query) or die("0.80 add building, room in glpi_locations" .
                                 $LANG['update'][90] . $DB->error());
   }

   if (!TableExists('glpi_requesttypes')) {
      $query="CREATE TABLE `glpi_requesttypes` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
              `is_helpdesk_default` tinyint(1) NOT NULL DEFAULT '0',
              `is_mail_default` tinyint(1) NOT NULL DEFAULT '0',
              `comment` text COLLATE utf8_unicode_ci,
              PRIMARY KEY (`id`),
              KEY `name` (`name`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->query($query) or die("0.80 create glpi_requesttypes" . $LANG['update'][90] . $DB->error());

      $DB->query("INSERT INTO `glpi_requesttypes` VALUES(1, '".
                  addslashes($LANG['Menu'][31])."', 1, 0, NULL)");
      $DB->query("INSERT INTO `glpi_requesttypes` VALUES(2, '".
                  addslashes($LANG['setup'][14])."', 0, 1, NULL)");
      $DB->query("INSERT INTO `glpi_requesttypes` VALUES(3, '".
                  addslashes($LANG['help'][35])."', 0, 0, NULL)");
      $DB->query("INSERT INTO `glpi_requesttypes` VALUES(4, '".
                  addslashes($LANG['tracking'][34])."', 0, 0, NULL)");
      $DB->query("INSERT INTO `glpi_requesttypes` VALUES(5, '".
                  addslashes($LANG['tracking'][35])."', 0, 0, NULL)");
      $DB->query("INSERT INTO `glpi_requesttypes` VALUES(6, '".
                  addslashes($LANG['common'][62])."', 0, 0, NULL)");
   }
   if (FieldExists('glpi_tickets','request_type')) {
      $query = "ALTER TABLE `glpi_tickets`
                      CHANGE `request_type` `requesttypes_id` INT( 11 ) NOT NULL DEFAULT '0'";

      $DB->query($query) or die("0.80 change requesttypes_id in glpi_tickets" .
                                 $LANG['update'][90] . $DB->error());
   }
   if (FieldExists('glpi_configs','default_request_type')) {
      $query = "ALTER TABLE `glpi_configs`
            CHANGE `default_request_type` `default_requesttypes_id` INT( 11 ) NOT NULL DEFAULT '1'";

      $DB->query($query) or die("0.80 change requesttypes_id in glpi_configs" .
                                 $LANG['update'][90] . $DB->error());
   }
   if (FieldExists('glpi_users','default_request_type')) {
      $query = "ALTER TABLE `glpi_users`
                CHANGE `default_request_type` `default_requesttypes_id` INT( 11 ) NULL DEFAULT NULL";

      $DB->query($query) or die("0.80 change requesttypes_id in glpi_users" .
                                 $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_groups','date_mod')) {
      $query = "ALTER TABLE `glpi_groups`
                ADD `date_mod` DATETIME NULL";

      $DB->query($query) or die("0.80 add date_mod to glpi_groups" .
                                 $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists("glpi_configs","priority_matrix")) {
      $query = "ALTER TABLE `glpi_configs`
                   ADD `priority_matrix` VARCHAR( 255 ) NULL
                      COMMENT 'json encoded array for Urgence / Impact to Protority'";
      $DB->query($query) or die("0.80 add priority_matrix  in glpi_configs " .
                                $LANG['update'][90] . $DB->error());
      $matrix = array(1=>array(1=>1,2=>1,3=>2,4=>2,4=>2,5=>2),
                      2=>array(1=>1,2=>2,3=>2,4=>3,4=>3,5=>3),
                      3=>array(1=>2,2=>2,3=>3,4=>4,4=>4,5=>4),
                      4=>array(1=>2,2=>3,3=>4,4=>4,4=>4,5=>5),
                      5=>array(1=>2,2=>3,3=>4,4=>5,4=>5,5=>5));
      $matrix = json_encode($matrix);
      $query = "UPDATE `glpi_configs` SET `priority_matrix`='$matrix' WHERE `id`='1'";
      $DB->query($query) or die("0.80 set default priority_matrix  in glpi_configs " .
                                $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists("glpi_configs","urgency_mask")) {
      $query = "ALTER TABLE `glpi_configs`
                      ADD `urgency_mask` INT( 11 ) NOT NULL DEFAULT '62',
                      ADD `impact_mask` INT( 11 ) NOT NULL DEFAULT '62'";
      $DB->query($query) or die("0.80 add urgency/impact_mask  in glpi_configs " .
                                $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists("glpi_users","priority_6")) {
      $query = "ALTER TABLE `glpi_users`
                      ADD `priority_6` CHAR( 20 ) NULL DEFAULT NULL AFTER `priority_5`";
      $DB->query($query) or die("0.80 add priority_6  in glpi_users " .
                                $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists("glpi_configs","priority_6")) {
      $query = "ALTER TABLE `glpi_configs`
                       ADD `priority_6` CHAR( 20 ) NOT NULL DEFAULT '#ff5555' AFTER `priority_5`";
      $DB->query($query) or die("0.80 add priority_6  in glpi_configs " .
                                $LANG['update'][90] . $DB->error());
   }

   if (!FieldExists('glpi_tickets','urgency')) {
      $query = "ALTER TABLE `glpi_tickets`
                      ADD `urgency` INT NOT NULL DEFAULT '1' AFTER `content`,
                      ADD `impact` INT NOT NULL DEFAULT '1' AFTER `urgency` ";
      $DB->query($query) or die("0.80 add urgency, impact to glpi_tickets" .
                                 $LANG['update'][90] . $DB->error());

      // set default trivial values for Impact and Urgence
      $query = "UPDATE `glpi_tickets` SET `urgency` = `priority`, `impact` = `priority`";
      $DB->query($query) or die("0.80 set urgency, impact in glpi_tickets" .
                                 $LANG['update'][90] . $DB->error());

      // Replace 'priority' (user choice un 0.72) by 'urgency' as criteria
      // Don't change "action" which is the result of user+tech evaluation.
      $query = "UPDATE `glpi_rulecriterias`
                SET `criteria`='urgency'
                WHERE `criteria`='priority'
                  AND `rules_id` IN (SELECT `id`
                                     FROM `glpi_rules`
                                     WHERE `sub_type`='".RULE_TRACKING_AUTO_ACTION."')";
      $DB->query($query) or die("0.80 fix priority/urgency in business rules " .
                                 $LANG['update'][90] . $DB->error());
   }

   // Display "Work ended." message - Keep this as the last action.
   displayMigrationMessage("080"); // End
}
?>
