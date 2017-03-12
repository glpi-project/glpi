<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/


if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

// _ before the link table name => no clean cache on this relation
// Table's names are in alphabetic order - Please respect it

$RELATION = array("glpi_authldaps"
                        => array('glpi_authldapreplicates' => 'authldaps_id',
                                 'glpi_entities'           => 'authldaps_id',),

                  "glpi_autoupdatesystems"
                        => array('glpi_computers' => 'autoupdatesystems_id'),

                  "glpi_bookmarks"
                        => array('glpi_bookmarks_users' => 'bookmarks_id'),

                  "glpi_budgets"
                        => array('glpi_infocoms' => 'budgets_id'),

                  "glpi_calendars"
                        => array('_glpi_calendarsegments'   => 'calendars_id',
                                 '_glpi_calendars_holidays' => 'calendars_id',
                                 'glpi_slas'                => 'calendars_id',
                                 'glpi_entities'            => 'calendars_id',),

                  "glpi_cartridgeitems"
                        => array('glpi_cartridges'                   => 'cartridgeitems_id',
                                 'glpi_cartridgeitems_printermodels' => 'cartridgeitems_id'),

                  "glpi_cartridgeitemtypes"
                        => array('glpi_cartridgeitems' => 'cartridgeitemtypes_id'),

                  "glpi_changes"
                        => array('glpi_changes_groups'    => 'changes_id',
                                 'glpi_changes_items'     => 'changes_id',
                                 'glpi_changes_problems'  => 'changes_id',
                                 'glpi_changes_projects'  => 'changes_id',
                                 'glpi_changes_suppliers' => 'changes_id',
                                 'glpi_changes_tickets'   => 'changes_id',
                                 'glpi_changes_users'     => 'changes_id',
                                 'glpi_changetasks'       => 'changes_id'),

                  "glpi_changetasks"
                        => array('glpi_changetasks'   => 'changetasks_id'),

                  "glpi_computermodels"
                        => array('glpi_computers' => 'computermodels_id'),

                  "glpi_computers"
                        => array('glpi_computers_items'                => 'computers_id',
                                 'glpi_computers_softwarelicenses'     => 'computers_id',
                                 'glpi_computers_softwareversions'     => 'computers_id',
                                 'glpi_computerdisks'                  => 'computers_id',
                                 'glpi_computervirtualmachines'        => 'computers_id'),

                  "glpi_computertypes"
                        => array('glpi_computers' => 'computertypes_id'),

                  "glpi_consumableitems"
                        => array('glpi_consumables' => 'consumableitems_id'),

                  "glpi_consumableitemtypes"
                        => array('glpi_consumableitems' => 'consumableitemtypes_id'),

                  "glpi_contacts"
                        => array('glpi_contacts_suppliers' => 'contacts_id'),

                  "glpi_contacttypes"
                        => array('glpi_contacts' => 'contacttypes_id'),

                  "glpi_contracts"
                        => array('glpi_contracts_items'     => 'contracts_id',
                                 'glpi_contracts_suppliers' => 'contracts_id'),

                  "glpi_contracttypes"
                        => array('glpi_contracts' => 'contracttypes_id'),

                  "glpi_devicecases"
                        => array('glpi_items_devicecases' => 'devicecases_id'),

                  "glpi_devicecasetypes"
                        => array('glpi_devicecases' => 'devicecasetypes_id'),

                  "glpi_devicecontrols"
                        => array('glpi_items_devicecontrols' => 'devicecontrols_id'),

                  "glpi_devicedrives"
                        => array('glpi_items_devicedrives' => 'devicedrives_id'),

                  "glpi_devicegraphiccards"
                        => array('glpi_items_devicegraphiccards' => 'devicegraphiccards_id'),

                  "glpi_deviceharddrives"
                        => array('glpi_items_deviceharddrives' => 'deviceharddrives_id'),

                  "glpi_devicememories"
                        => array('glpi_items_devicememories' => 'devicememories_id'),

                  "glpi_devicememorytypes"
                        => array('glpi_devicememories' => 'devicememorytypes_id'),

                  "glpi_devicemotherboards"
                        => array('glpi_items_devicemotherboards' => 'devicemotherboards_id'),

                  "glpi_devicenetworkcards"
                        => array('glpi_items_devicenetworkcards' => 'devicenetworkcards_id'),

                  "glpi_devicepcis"
                        => array('glpi_items_devicepcis' => 'devicepcis_id'),

                  "glpi_devicepowersupplies"
                        => array('glpi_items_devicepowersupplies' => 'devicepowersupplies_id'),

                  "glpi_deviceprocessors"
                        => array('glpi_items_deviceprocessors' => 'deviceprocessors_id'),

                  "glpi_devicesoundcards"
                        => array('glpi_items_devicesoundcards' => 'devicesoundcards_id'),

                  "glpi_documentcategories"
                        => array('glpi_configs'             => 'documentcategories_id_forticket',
                                 'glpi_documents'           => 'documentcategories_id',
                                 'glpi_documentcategories'  => 'documentcategories_id'),

                  "glpi_documents"
                        => array('glpi_documents_items' => 'documents_id'),

                  "glpi_domains"
                        => array('glpi_computers'         => 'domains_id',
                                 'glpi_printers'          => 'domains_id',
                                 'glpi_networkequipments' => 'domains_id'),

                  "glpi_entities"
                        => array('glpi_bookmarks'                       => 'entities_id',
                                 'glpi_budgets'                         => 'entities_id',
                                 'glpi_calendars'                       => 'entities_id',
                                 '_glpi_calendarsegments'               => 'entities_id',
                                 'glpi_cartridgeitems'                  => 'entities_id',
                                 '_glpi_cartridges'                     => 'entities_id',
                                 'glpi_changes'                         => 'entities_id',
                                 'glpi_computers'                       => 'entities_id',
                                 '_glpi_computerdisks'                  => 'entities_id',
                                 '_glpi_computervirtualmachines'        => 'entities_id',
                                 'glpi_consumableitems'                 => 'entities_id',
                                 '_glpi_consumables'                    => 'entities_id',
                                 'glpi_contacts'                        => 'entities_id',
                                 'glpi_contracts'                       => 'entities_id',
                                 'glpi_documents'                       => 'entities_id',
                                 '_glpi_documents_items'                => 'entities_id',
                                 '_glpi_entities'                       => 'entities_id',
                                 'glpi_entities'                        => 'entities_id_software',
                                 'glpi_entities_knowbaseitems'          => 'entities_id',
                                 'glpi_entities_reminders'              => 'entities_id',
                                 'glpi_fieldblacklists'                 => 'entities_id',
                                 'glpi_fieldunicities'                  => 'entities_id',
                                 'glpi_fqdns'                           => 'entities_id',
                                 'glpi_groups'                          => 'entities_id',
                                 'glpi_groups_knowbaseitems'            => 'entities_id',
                                 'glpi_groups_reminders'                => 'entities_id',
                                 'glpi_holidays'                        => 'entities_id',
                                 '_glpi_infocoms'                       => 'entities_id',
                                 'glpi_ipaddresses'                     => 'entities_id',
                                 'glpi_ipnetworks'                      => 'entities_id',
                                 'glpi_itilcategories'                  => 'entities_id',
                                 'glpi_knowbaseitemcategories'          => 'entities_id',
                                 'glpi_knowbaseitems_profiles'          => 'entities_id',
                                 'glpi_links'                           => 'entities_id',
                                 'glpi_locations'                       => 'entities_id',
                                 'glpi_monitors'                        => 'entities_id',
                                 'glpi_netpoints'                       => 'entities_id',
                                 'glpi_networkaliases'                  => 'entities_id',
                                 'glpi_networkequipments'               => 'entities_id',
                                 'glpi_networknames'                    => 'entities_id',
                                 '_glpi_networkports'                   => 'entities_id',
                                 'glpi_notifications'                   => 'entities_id',
                                 'glpi_peripherals'                     => 'entities_id',
                                 'glpi_phones'                          => 'entities_id',
                                 'glpi_printers'                        => 'entities_id',
                                 'glpi_problems'                        => 'entities_id',
                                 'glpi_projects'                        => 'entities_id',
                                 '_glpi_projecttasks'                   => 'entities_id',
                                 'glpi_profiles_reminders'              => 'entities_id',
                                 'glpi_profiles_users'                  => 'entities_id',
                                 '_glpi_reservationitems'               => 'entities_id',
                                 'glpi_rules'                           => 'entities_id',
                                 '_glpi_slalevels'                      => 'entities_id',
                                 'glpi_slas'                            => 'entities_id',
                                 'glpi_softwarelicenses'                => 'entities_id',
                                 'glpi_softwareversions'                => 'entities_id',
                                 'glpi_softwares'                       => 'entities_id',
                                 'glpi_solutiontemplates'               => 'entities_id',
                                 'glpi_solutiontypes'                   => 'entities_id',
                                 'glpi_taskcategories'                  => 'entities_id',
                                 'glpi_tasktemplates'                   => 'entities_id',
                                 'glpi_suppliers'                       => 'entities_id',
                                 'glpi_taskcategories'                  => 'entities_id',
                                 'glpi_ticketrecurrents'                => 'entities_id',
                                 'glpi_tickettemplates'                 => 'entities_id',
                                 'glpi_tickets'                         => 'entities_id',
                                 '_glpi_ticketvalidations'              => 'entities_id',
                                 'glpi_users'                           => 'entities_id'),

                  "glpi_filesystems"
                        => array('glpi_computerdisks' => 'filesystems_id'),

                  "glpi_fqdns"
                        => array('glpi_networkaliases'   => 'fqdns_id',
                                 'glpi_networknames'     => 'fqdns_id'),

                  "glpi_groups"
                        => array('glpi_cartridgeitems'       => 'groups_id_tech',
                                 'glpi_changes_groups'       => 'groups_id',
                                 'glpi_computers'            => array('groups_id_tech', 'groups_id'),
                                 'glpi_consumableitems'      => 'groups_id_tech',
                                 'glpi_groups'               => 'groups_id',
                                 'glpi_groups_knowbaseitems' => 'groups_id',
                                 'glpi_groups_problems'      => 'groups_id',
                                 'glpi_groups_reminders'     => 'groups_id',
                                 'glpi_groups_tickets'       => 'groups_id',
                                 'glpi_groups_users'         => 'groups_id',
                                 'glpi_itilcategories'       => 'groups_id',
                                 'glpi_monitors'             => array('groups_id_tech', 'groups_id'),
                                 'glpi_networkequipments'    => array('groups_id_tech', 'groups_id'),
                                 'glpi_peripherals'          => array('groups_id_tech', 'groups_id'),
                                 'glpi_phones'               => array('groups_id_tech', 'groups_id'),
                                 'glpi_printers'             => array('groups_id_tech', 'groups_id'),
                                 'glpi_projects'             => 'groups_id',
                                 'glpi_softwares'            => array('groups_id_tech', 'groups_id'),
                                 'glpi_softwarelicenses'     => array('groups_id_tech', 'groups_id')),

                  "glpi_holidays"
                        => array('glpi_calendars_holidays' => 'holidays_id',),

                  "glpi_interfacetypes"
                        => array('glpi_deviceharddrives'   => 'interfacetypes_id',
                                 'glpi_devicedrives'       => 'interfacetypes_id',
                                 'glpi_devicegraphiccards' => 'interfacetypes_id',
                                 'glpi_devicecontrols'     => 'interfacetypes_id'),

                  "glpi_ipaddresses"
                        => array('glpi_ipaddresses_ipnetworks'   => 'ipaddresses_id'),

                  "glpi_ipnetworks"
                        => array('glpi_ipaddresses_ipnetworks'   => 'ipnetworks_id',
                                 'glpi_ipnetworks'               => 'ipnetworks_id',
                                 'glpi_ipnetworks_vlans'         => 'ipnetworks_id'),

                  "glpi_knowbaseitemcategories"
                        => array('glpi_itilcategories'         => 'knowbaseitemcategories_id',
                                 'glpi_knowbaseitemcategories' => 'knowbaseitemcategories_id',
                                 'glpi_knowbaseitems'          => 'knowbaseitemcategories_id'),

                  "glpi_knowbaseitems"
                        => array('glpi_entities_knowbaseitems' => 'knowbaseitems_id',
                                 'glpi_groups_knowbaseitems'   => 'knowbaseitems_id',
                                 'glpi_knowbaseitems_profiles' => 'knowbaseitems_id',
                                 'glpi_knowbaseitems_users'    => 'knowbaseitems_id'),

                  "glpi_links"
                        => array('_glpi_links_itemtypes' => 'links_id'),

                  "glpi_locations"
                        => array('glpi_budgets'           => 'locations_id',
                                 'glpi_cartridgeitems'    => 'locations_id',
                                 'glpi_consumableitems'   => 'locations_id',
                                 'glpi_computers'         => 'locations_id',
                                 'glpi_locations'         => 'locations_id',
                                 'glpi_monitors'          => 'locations_id',
                                 'glpi_netpoints'         => 'locations_id',
                                 'glpi_networkequipments' => 'locations_id',
                                 'glpi_peripherals'       => 'locations_id',
                                 'glpi_phones'            => 'locations_id',
                                 'glpi_printers'          => 'locations_id',
                                 'glpi_softwares'         => 'locations_id',
                                 'glpi_softwarelicenses'  => 'locations_id',
                                 'glpi_tickets'           => 'locations_id',
                                 'glpi_users'             => 'locations_id'),

                  "glpi_manufacturers"
                        => array('glpi_cartridgeitems'      => 'manufacturers_id',
                                 'glpi_computers'           => 'manufacturers_id',
                                 'glpi_consumableitems'     => 'manufacturers_id',
                                 'glpi_devicecases'         => 'manufacturers_id',
                                 'glpi_devicecontrols'      => 'manufacturers_id',
                                 'glpi_devicedrives'        => 'manufacturers_id',
                                 'glpi_devicegraphiccards'  => 'manufacturers_id',
                                 'glpi_deviceharddrives'    => 'manufacturers_id',
                                 'glpi_devicenetworkcards'  => 'manufacturers_id',
                                 'glpi_devicemotherboards'  => 'manufacturers_id',
                                 'glpi_devicepcis'          => 'manufacturers_id',
                                 'glpi_devicepowersupplies' => 'manufacturers_id',
                                 'glpi_deviceprocessors'    => 'manufacturers_id',
                                 'glpi_devicememories'      => 'manufacturers_id',
                                 'glpi_devicesoundcards'    => 'manufacturers_id',
                                 'glpi_monitors'            => 'manufacturers_id',
                                 'glpi_networkequipments'   => 'manufacturers_id',
                                 'glpi_peripherals'         => 'manufacturers_id',
                                 'glpi_phones'              => 'manufacturers_id',
                                 'glpi_printers'            => 'manufacturers_id',
                                 'glpi_softwares'           => 'manufacturers_id',
                                 'glpi_softwarelicenses'    => 'manufacturers_id'),

                  "glpi_monitormodels"
                        => array('glpi_monitors' => 'monitormodels_id'),

                  "glpi_monitortypes"
                        => array('glpi_monitors' => 'monitortypes_id'),

                  "glpi_netpoints"
                        => array('glpi_networkportethernets'   => 'netpoints_id'),

                  "glpi_networkequipmentfirmwares"
                        => array('glpi_networkequipments' =>'networkequipmentfirmwares_id'),

                  "glpi_networkequipmentmodels"
                        => array('glpi_networkequipments' =>'networkequipmentmodels_id'),

                  "glpi_networkequipmenttypes"
                        => array('glpi_networkequipments' => 'networkequipmenttypes_id'),

                  "glpi_networknames"
                        => array('glpi_networkaliases'          => 'networknames_id'),

                  "glpi_networkports"
                        => array('glpi_networkportaggregates'     => 'networkports_id',
                                 'glpi_networkportaliases'        => array('networkports_id',
                                                                           'networkports_id_alias'),
                                 'glpi_networkportdialups'        => 'networkports_id',
                                 'glpi_networkportethernets'      => 'networkports_id',
                                 'glpi_networkportlocals'         => 'networkports_id',
                                 'glpi_networkports_vlans'        => 'networkports_id',
                                 'glpi_networkports_networkports' => array('networkports_id_1',
                                                                           'networkports_id_2'),
                                 'glpi_networkportwifis'          => 'networkports_id'),

                  "glpi_networkportwifis"
                        => array('glpi_networkportwifis' => 'networkportwifis_id'),

                  "glpi_networks"
                        => array('glpi_computers'         => 'networks_id',
                                 'glpi_printers'          => 'networks_id',
                                 'glpi_networkequipments' => 'networks_id'),

                  "glpi_operatingsystems"
                        => array('glpi_computers'        => 'operatingsystems_id',
                                 'glpi_softwareversions' => 'operatingsystems_id'),

                  "glpi_operatingsystemservicepacks"
                        => array('glpi_computers' => 'operatingsystemservicepacks_id'),

                  "glpi_operatingsystemversions"
                        => array('glpi_computers' => 'operatingsystemversions_id'),

                  "glpi_peripheralmodels"
                        => array('glpi_peripherals' =>' peripheralmodels_id'),

                  "glpi_peripheraltypes"
                        => array('glpi_peripherals' => 'peripheraltypes_id'),

                  "glpi_phonemodels"
                        => array('glpi_phones' => 'phonemodels_id'),

                  "glpi_phonepowersupplies"
                        => array('glpi_phones' => 'phonepowersupplies_id'),

                  "glpi_phonetypes"
                        => array('glpi_phones' => 'phonetypes_id'),

                  "glpi_printermodels"
                        => array('glpi_printers'                     => 'printermodels_id',
                                 'glpi_cartridgeitems_printermodels' => 'printermodels_id'),

                  "glpi_printers"
                        => array('glpi_cartridges' => 'printers_id'),

                  "glpi_printertypes"
                        => array('glpi_printers' => 'printertypes_id'),

                  "glpi_projects"
                        => array('glpi_projects'         => 'projects_id',
                                 'glpi_projecttasks'     => 'projects_id',
                                 'glpi_projectteams'     => 'projects_id',
                                 'glpi_changes_projects' => 'projects_id',
                                 'glpi_items_projects'   => 'projects_id'),

                  "glpi_projectstates"
                        => array('glpi_projects'        => 'projectstates_id',
                                 'glpi_projecttasks'    => 'projectstates_id'),

                  "glpi_projecttasks"
                        => array('glpi_projecttasks'         => 'projecttasks_id',
                                 'glpi_projecttasks_tickets' => 'projecttasks_id',
                                 'glpi_projecttaskteams'     => 'projecttasks_id'),

                  "glpi_projecttasktypes"
                        => array('glpi_projecttasks'   => 'projecttasktypes_id'),

                  "glpi_projecttypes"
                        => array('glpi_projects'   => 'projecttypes_id'),

                  "glpi_problems"
                        => array('glpi_changes_problems'   => 'problems_id',
                                 'glpi_groups_problems'    => 'problems_id',
                                 'glpi_items_problems'     => 'problems_id',
                                 'glpi_problems_suppliers' => 'problems_id',
                                 'glpi_problems_tickets'   => 'problems_id',
                                 'glpi_problems_users'     => 'problems_id',
                                 'glpi_problemtasks'       => 'problems_id'),

                  "glpi_profiles"
                        => array('glpi_knowbaseitems_profiles' => 'profiles_id',
                                 'glpi_profilerights'          => 'profiles_id',
                                 'glpi_profiles_reminders'     => 'profiles_id',
                                 'glpi_profiles_users'         => 'profiles_id',
                                 'glpi_users'                  => 'profiles_id'),

                  "glpi_reminders"
                        => array('glpi_entities_reminders'  => 'reminders_id',
                                 'glpi_groups_reminders'    => 'reminders_id',
                                 'glpi_profiles_reminders'  => 'reminders_id',
                                 'glpi_reminders_users'     => 'reminders_id',),

                  "glpi_requesttypes"
                        => array('glpi_ticketfollowups'  => 'requesttypes_id',
                                 'glpi_tickets'          => 'requesttypes_id',
                                 'glpi_users'            => 'default_requesttypes_id',
                                 'glpi_configs'          => 'default_requesttypes_id'),

                  "glpi_reservationitems"
                        => array('glpi_reservations' => 'reservationitems_id'),

                  "glpi_rules"
                        => array('glpi_ruleactions'                          => 'rules_id',
                                 'glpi_rulecriterias'                        => 'rules_id'),

                  "glpi_slalevels"
                        => array('glpi_slalevelactions'   => 'slalevels_id',
                                 'glpi_slalevelcriterias' => 'slalevels_id',
                                 'glpi_tickets'           => 'ttr_slalevels_id',
                                 'glpi_slalevels_tickets' => 'slalevels_id'),

                  "glpi_slts"
                        => array('glpi_slalevels' => 'slts_id',
                                 'glpi_tickets'   => array('slts_ttr_id',
                                                           'slts_tto_id')),

                  "glpi_softwarecategories"
                        => array('glpi_softwares' => 'softwarecategories_id',
                                 'glpi_configs'   => 'softwarecategories_id_ondelete'),

                  "glpi_softwarelicensetypes"
                        => array('glpi_softwarelicenses' =>'softwarelicensetypes_id'),

                  "glpi_softwareversions"
                        => array('glpi_computers_softwareversions'
                                                         => 'softwareversions_id',
                                 'glpi_softwarelicenses' => array('softwareversions_id_buy',
                                                                  'softwareversions_id_use')),

                  "glpi_softwarelicenses"
                        => array('glpi_computers_softwarelicenses' =>'softwarelicenses_id'),

                  "glpi_softwares"
                        => array('glpi_softwarelicenses' => 'softwares_id',
                                 'glpi_softwareversions' => 'softwares_id',
                                 'glpi_softwares'        => 'softwares_id'),

                  "glpi_solutiontypes"
                        => array(
                                 'glpi_changes'           => 'solutiontypes_id',
                                 'glpi_problems'          => 'solutiontypes_id',
                                 'glpi_tickets'           => 'solutiontypes_id',
                                 'glpi_solutiontemplates' => 'solutiontypes_id'),

                  "glpi_states"
                        => array('glpi_computers'         => 'states_id',
                                 'glpi_monitors'          => 'states_id',
                                 'glpi_networkequipments' => 'states_id',
                                 'glpi_peripherals'       => 'states_id',
                                 'glpi_phones'            => 'states_id',
                                 'glpi_printers'          => 'states_id',
                                 'glpi_softwarelicenses'  => 'states_id',
                                 'glpi_softwareversions'  => 'states_id',
                                 'glpi_states'            => 'states_id'),

                  "glpi_suppliers"
                        => array(
                                 'glpi_changes_suppliers'   => 'suppliers_id',
                                 'glpi_contacts_suppliers'  => 'suppliers_id',
                                 'glpi_contracts_suppliers' => 'suppliers_id',
                                 'glpi_infocoms'            => 'suppliers_id',
                                 'glpi_problems_suppliers'  => 'suppliers_id',
                                 'glpi_suppliers_tickets'   => 'suppliers_id',),

                  "glpi_suppliertypes"
                        => array('glpi_suppliers' => 'suppliertypes_id'),

                  "glpi_taskcategories"
                        => array(
                                 'glpi_changetasks'    => 'taskcategories_id',
                                 'glpi_problemtasks'   => 'taskcategories_id',
                                 'glpi_taskcategories' => 'taskcategories_id',
                                 'glpi_tickettasks'    => 'taskcategories_id',
                                 'glpi_tasktemplates'  => 'taskcategories_id'),

                  "glpi_itilcategories"
                        => array(
                                 'glpi_changes'         => 'itilcategories_id',
                                 'glpi_itilcategories'  => 'itilcategories_id',
                                 'glpi_tickets'         => 'itilcategories_id',
                                 'glpi_problems'        => 'itilcategories_id'),

                  "glpi_tickettemplates"
                        => array('glpi_entities'            => 'tickettemplates_id',
                                 'glpi_itilcategories'      => array('tickettemplates_id_incident',
                                                                     'tickettemplates_id_demand'),
                                 'glpi_ticketrecurrents'    => 'tickettemplates_id',
                                 '_glpi_tickettemplatehiddenfields'
                                                            => 'tickettemplates_id',
                                 '_glpi_tickettemplatepredefinedfields'
                                                            => 'tickettemplates_id',
                                 '_glpi_tickettemplatemandatoryfields'
                                                            => 'tickettemplates_id'),

                  "glpi_tickets"
                        => array('_glpi_documents'          => 'tickets_id',
                                 'glpi_changes_tickets'     => 'tickets_id',
                                 'glpi_groups_tickets'      => 'tickets_id',
                                 'glpi_problems_tickets'    => 'tickets_id',
                                 'glpi_projecttasks_tickets'=> 'tickets_id',
                                 'glpi_slalevels_tickets'   => 'tickets_id',
                                 'glpi_suppliers_tickets'   => 'tickets_id',
                                 'glpi_ticketfollowups'     => 'tickets_id',
                                 'glpi_ticketsatisfactions' => 'tickets_id',
                                 'glpi_tickettasks'         => 'tickets_id',
                                 'glpi_ticketvalidations'   => 'tickets_id',
                                 'glpi_tickets_tickets'     => array('tickets_id_1',
                                                                     'tickets_id_2'),
                                 'glpi_tickets_users'       => 'tickets_id'),

                  "glpi_solutiontypes"
                        => array(
                                    'glpi_changes'             => 'solutiontypes_id',
                                    'glpi_tickets'             => 'solutiontypes_id',
                                    'glpi_solutiontemplates'   => 'solutiontypes_id',
                                    'glpi_problems'            => 'solutiontypes_id'),

                  "glpi_ssovariables"
                        => array('glpi_configs' => 'ssovariables_id'),

                  "glpi_transfers"
                        => array('glpi_configs' => 'transfers_id_auto'),

                  "glpi_usercategories"
                        => array('glpi_users' => 'usercategories_id'),

                  "glpi_users"
                        => array('glpi_bookmarks'                 => 'users_id',
                                 'glpi_bookmarks_users'           => 'users_id',
                                 'glpi_cartridgeitems'            => 'users_id_tech',
                                 'glpi_changes'                   => array('users_id_recipient',
                                                                     'users_id_lastupdater'),
                                 'glpi_changes_users'             => 'users_id',
                                 'glpi_changetasks'               => array('users_id', 'users_id_tech'),
                                 'glpi_computers'                 => array('users_id_tech', 'users_id'),
                                 'glpi_consumableitems'           => 'users_id_tech',
                                 'glpi_displaypreferences'        => 'users_id',
                                 'glpi_documents'                 => 'users_id',
                                 'glpi_groups_users'              => 'users_id',
                                 'glpi_itilcategories'            => 'users_id',
                                 'glpi_knowbaseitems'             => 'users_id',
                                 'glpi_knowbaseitems_users'       => 'users_id',
                                 'glpi_monitors'                  => array('users_id_tech', 'users_id'),
                                 'glpi_networkequipments'         => array('users_id_tech', 'users_id'),
                                 'glpi_notimportedemails'         => 'users_id',
                                 'glpi_peripherals'               => array('users_id_tech', 'users_id'),
                                 'glpi_phones'                    => array('users_id_tech', 'users_id'),
                                 'glpi_printers'                  => array('users_id_tech', 'users_id'),
                                 'glpi_problems'                  => array('users_id_recipient',
                                                                     'users_id_lastupdater'),
                                 'glpi_problems_users'            => 'users_id',
                                 'glpi_problemtasks'              => array('users_id', 'users_id_tech'),
                                 'glpi_profiles_users'            => 'users_id',
                                 'glpi_projects'                  => 'users_id',
                                 'glpi_projecttasks'              => 'users_id',
                                 'glpi_reminders'                 => 'users_id',
                                 'glpi_reminders_users'           => 'users_id',
                                 'glpi_reservations'              => 'users_id',
                                 'glpi_softwares'                 => array('users_id_tech', 'users_id'),
                                 'glpi_softwarelicenses'          => array('users_id_tech', 'users_id'),
                                 'glpi_ticketfollowups'           => 'users_id',
                                 'glpi_tickets'                   => array('users_id_recipient',
                                                                     'users_id_lastupdater'),
                                 'glpi_tickets_users'             => 'users_id',
                                 'glpi_tickettasks'               => array('users_id', 'users_id_tech'),
                                 'glpi_ticketvalidations'         => 'users_id',
                                 'glpi_ticketvalidations'         => 'users_id_validate',
                                 'glpi_useremails'                => 'users_id'),

                  "glpi_usertitles"
                        => array('glpi_contacts'   => 'usertitles_id',
                                 'glpi_users'      => 'usertitles_id'),

                  "glpi_vlans"
                        => array('glpi_networkports_vlans' => 'vlans_id'),

                  "glpi_virtualmachinestates"
                        => array('glpi_computervirtualmachines' => 'virtualmachinestates_id'),

                  "glpi_virtualmachinesystems"
                        => array('glpi_computervirtualmachines' => 'virtualmachinesystems_id'),

                  "glpi_virtualmachinetypes"
                        => array('glpi_computervirtualmachines' => 'virtualmachinetypes_id'),

                  "glpi_wifinetworks"
                        => array('glpi_networkportwifis' => 'wifinetworks_id'),

                // link from devices tables (computers, software, ...) : only used for unrecurs check
                "_virtual_device" => array('glpi_contracts_items' => array('items_id', 'itemtype'),
                                           'glpi_documents_items' => array('items_id', 'itemtype'),
                                           'glpi_infocoms'        => array('items_id', 'itemtype'),
//                                            'glpi_ipaddresses'     => array('items_id', 'itemtype'),
//                                            'glpi_networknames'    => array('items_id', 'itemtype'),
                                           ),
                );

?>
