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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

// _ before the link table name => no clean cache on this relation
// Table's names are in alphabetic order - Please respect it

$RELATION = ["glpi_authldaps"
                        => ['glpi_authldapreplicates' => 'authldaps_id',
                                 'glpi_entities'           => 'authldaps_id',],

                        "glpi_autoupdatesystems"
                        => ['glpi_computers' => 'autoupdatesystems_id'],

                        "glpi_savedsearches"
                        => ['glpi_savedsearches_users' => 'savedsearches_id'],

                        "glpi_budgets"
                        => ['glpi_infocoms' => 'budgets_id'],

                        "glpi_calendars"
                        => ['_glpi_calendarsegments'   => 'calendars_id',
                                 '_glpi_calendars_holidays' => 'calendars_id',
                                 'glpi_slms'                => 'calendars_id',
                                 'glpi_entities'            => 'calendars_id',],

                        "glpi_cartridgeitems"
                        => ['glpi_cartridges'                   => 'cartridgeitems_id',
                                 'glpi_cartridgeitems_printermodels' => 'cartridgeitems_id'],

                        "glpi_cartridgeitemtypes"
                        => ['glpi_cartridgeitems' => 'cartridgeitemtypes_id'],

                        "glpi_changes"
                        => ['glpi_changes_groups'    => 'changes_id',
                                 'glpi_changes_items'     => 'changes_id',
                                 'glpi_changes_problems'  => 'changes_id',
                                 'glpi_changes_suppliers' => 'changes_id',
                                 'glpi_changes_tickets'   => 'changes_id',
                                 'glpi_changes_users'     => 'changes_id',
                                 'glpi_changetasks'       => 'changes_id'],

                        "glpi_changetasks"
                        => ['glpi_changetasks'   => 'changetasks_id'],

                        "glpi_computermodels"
                        => ['glpi_computers' => 'computermodels_id'],

                        "glpi_computers"
                        => ['glpi_computers_items'                => 'computers_id',
                                 'glpi_computers_softwarelicenses'     => 'computers_id',
                                 'glpi_computers_softwareversions'     => 'computers_id',
                                 'glpi_computervirtualmachines'        => 'computers_id'],

                        "glpi_computertypes"
                        => ['glpi_computers' => 'computertypes_id'],

                        "glpi_certificatetypes"
                        => ['glpi_certificatetypes' => 'certificatetypes_id'],

                        "glpi_consumableitems"
                        => ['glpi_consumables' => 'consumableitems_id'],

                        "glpi_consumableitemtypes"
                        => ['glpi_consumableitems' => 'consumableitemtypes_id'],

                        "glpi_contacts"
                        => ['glpi_contacts_suppliers' => 'contacts_id'],

                        "glpi_contacttypes"
                        => ['glpi_contacts' => 'contacttypes_id'],

                        "glpi_contracts"
                        => ['glpi_contracts_items'     => 'contracts_id',
                                 'glpi_contracts_suppliers' => 'contracts_id'],

                        "glpi_contracttypes"
                        => ['glpi_contracts' => 'contracttypes_id'],

                        "glpi_datacenters"
                        => ['glpi_dcrooms' => 'datacenters_id'],

                        "glpi_dcrooms"
                        => ['glpi_racks' => 'dcrooms_id'],

                        "glpi_devicecases"
                        => ['glpi_items_devicecases' => 'devicecases_id'],

                        "glpi_devicecasetypes"
                        => ['glpi_devicecases' => 'devicecasetypes_id'],

                        "glpi_devicecontrols"
                        => ['glpi_items_devicecontrols' => 'devicecontrols_id'],

                        "glpi_devicedrives"
                        => ['glpi_items_devicedrives' => 'devicedrives_id'],

                        "glpi_devicegraphiccards"
                        => ['glpi_items_devicegraphiccards' => 'devicegraphiccards_id'],

                        "glpi_deviceharddrives"
                        => ['glpi_items_deviceharddrives' => 'deviceharddrives_id'],

                        "glpi_devicememories"
                        => ['glpi_items_devicememories' => 'devicememories_id'],

                        "glpi_devicememorytypes"
                        => ['glpi_devicememories' => 'devicememorytypes_id'],

                        "glpi_devicemotherboards"
                        => ['glpi_items_devicemotherboards' => 'devicemotherboards_id'],

                        "glpi_devicenetworkcards"
                        => ['glpi_items_devicenetworkcards' => 'devicenetworkcards_id'],

                        "glpi_devicepcis"
                        => ['glpi_items_devicepcis' => 'devicepcis_id'],

                        "glpi_devicepowersupplies"
                        => ['glpi_items_devicepowersupplies' => 'devicepowersupplies_id'],

                        "glpi_deviceprocessors"
                        => ['glpi_items_deviceprocessors' => 'deviceprocessors_id'],

                        "glpi_devicesoundcards"
                        => ['glpi_items_devicesoundcards' => 'devicesoundcards_id'],

                        "glpi_devicebatteries"
                        => ['glpi_items_devicebatteries' => 'devicebatteries_id'],

                        "glpi_devicefirmwares"
                        => ['glpi_items_devicefirmwares' => 'devicefirmwares_id'],

                        "glpi_devicesensors"
                        => ['glpi_items_devicesensors' => 'devicesensors_id'],

                        "glpi_devicesimcards"
                        => ['glpi_items_devicesimcards' => 'devicesimcards_id'],

                        "glpi_devicemotherboards"
                        => ['glpi_items_devicegenerics' => 'devicegenerics_id'],

                        "glpi_documentcategories"
                        => ['glpi_configs'             => 'documentcategories_id_forticket',
                                 'glpi_documents'           => 'documentcategories_id',
                                 'glpi_documentcategories'  => 'documentcategories_id'],

                        "glpi_documents"
                        => ['glpi_documents_items' => 'documents_id'],

                        "glpi_domains"
                        => ['glpi_computers'         => 'domains_id',
                                 'glpi_printers'          => 'domains_id',
                                 'glpi_networkequipments' => 'domains_id'],

                        "glpi_enclosuremodels"
                        => ['glpi_enclosures' => 'enclosuremodels_id'],

                        "glpi_entities"
                        => ['glpi_savedsearches'                   => 'entities_id',
                                 'glpi_budgets'                         => 'entities_id',
                                 'glpi_calendars'                       => 'entities_id',
                                 '_glpi_calendarsegments'               => 'entities_id',
                                 'glpi_cartridgeitems'                  => 'entities_id',
                                 '_glpi_cartridges'                     => 'entities_id',
                                 'glpi_changes'                         => 'entities_id',
                                 'glpi_computers'                       => 'entities_id',
                                 '_glpi_computervirtualmachines'        => 'entities_id',
                                 'glpi_consumableitems'                 => 'entities_id',
                                 '_glpi_consumables'                    => 'entities_id',
                                 'glpi_contacts'                        => 'entities_id',
                                 'glpi_contracts'                       => 'entities_id',
                                 'glpi_datacenters'                     => 'entities_id',
                                 'glpi_dcrooms'                         => 'entities_id',
                                 'glpi_documents'                       => 'entities_id',
                                 '_glpi_documents_items'                => 'entities_id',
                                 'glpi_enclosures'                      => 'entities_id',
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
                                 'glpi_pdus'                            => 'entities_id',
                                 'glpi_pdutypes'                        => 'entities_id',
                                 'glpi_peripherals'                     => 'entities_id',
                                 'glpi_phones'                          => 'entities_id',
                                 'glpi_printers'                        => 'entities_id',
                                 'glpi_problems'                        => 'entities_id',
                                 'glpi_projects'                        => 'entities_id',
                                 '_glpi_projecttasks'                   => 'entities_id',
                                 'glpi_profiles_reminders'              => 'entities_id',
                                 'glpi_profiles_users'                  => 'entities_id',
                                 'glpi_racks'                           => 'entities_id',
                                 'glpi_racktypes'                       => 'entities_id',
                                 '_glpi_reservationitems'               => 'entities_id',
                                 'glpi_rules'                           => 'entities_id',
                                 '_glpi_slalevels'                      => 'entities_id',
                                 'glpi_slms'                            => 'entities_id',
                                 'glpi_softwarelicenses'                => 'entities_id',
                                 'glpi_softwareversions'                => 'entities_id',
                                 'glpi_softwares'                       => 'entities_id',
                                 'glpi_solutiontemplates'               => 'entities_id',
                                 'glpi_solutiontypes'                   => 'entities_id',
                                 'glpi_taskcategories'                  => 'entities_id',
                                 'glpi_tasktemplates'                   => 'entities_id',
                                 'glpi_projecttasktemplates'            => 'entities_id',
                                 'glpi_suppliers'                       => 'entities_id',
                                 'glpi_taskcategories'                  => 'entities_id',
                                 'glpi_ticketrecurrents'                => 'entities_id',
                                 'glpi_tickettemplates'                 => 'entities_id',
                                 'glpi_tickets'                         => 'entities_id',
                                 '_glpi_ticketvalidations'              => 'entities_id',
                                 'glpi_users'                           => 'entities_id',
                                 'glpi_certificates'                    => 'entities_id'],

                        "glpi_filesystems"
                        => ['glpi_items_disks' => 'filesystems_id'],

                        "glpi_fqdns"
                        => ['glpi_networkaliases'   => 'fqdns_id',
                                 'glpi_networknames'     => 'fqdns_id'],

                        "glpi_groups" => [
                           'glpi_cartridgeitems'       => 'groups_id_tech',
                           'glpi_changes_groups'       => 'groups_id',
                           'glpi_computers'            => ['groups_id_tech', 'groups_id'],
                           'glpi_consumables'          => ['items_id', 'itemtype'],
                           'glpi_consumableitems'      => 'groups_id_tech',
                           'glpi_enclosures'           => 'groups_id_tech',
                           'glpi_groups'               => 'groups_id',
                           'glpi_groups_knowbaseitems' => 'groups_id',
                           'glpi_groups_problems'      => 'groups_id',
                           'glpi_groups_reminders'     => 'groups_id',
                           'glpi_groups_tickets'       => 'groups_id',
                           'glpi_groups_users'         => 'groups_id',
                           'glpi_itilcategories'       => 'groups_id',
                           'glpi_monitors'             => ['groups_id_tech', 'groups_id'],
                           'glpi_networkequipments'    => ['groups_id_tech', 'groups_id'],
                           'glpi_pdus'                 => 'groups_id_tech',
                           'glpi_peripherals'          => ['groups_id_tech', 'groups_id'],
                           'glpi_phones'               => ['groups_id_tech', 'groups_id'],
                           'glpi_printers'             => ['groups_id_tech', 'groups_id'],
                           'glpi_certificates'         => ['groups_id_tech', 'groups_id'],
                           'glpi_racks'                => 'groups_id_tech',
                           'glpi_projects'             => 'groups_id',
                           'glpi_softwarelicenses'     => ['groups_id_tech', 'groups_id'],
                           'glpi_softwares'            => ['groups_id_tech', 'groups_id']
                        ],

                        "glpi_holidays"
                        => ['glpi_calendars_holidays' => 'holidays_id',],

                        "glpi_interfacetypes"
                        => ['glpi_deviceharddrives'   => 'interfacetypes_id',
                                 'glpi_devicedrives'       => 'interfacetypes_id',
                                 'glpi_devicegraphiccards' => 'interfacetypes_id',
                                 'glpi_devicecontrols'     => 'interfacetypes_id'],

                        "glpi_ipaddresses"
                        => ['glpi_ipaddresses_ipnetworks'   => 'ipaddresses_id'],

                        "glpi_ipnetworks"
                        => ['glpi_ipaddresses_ipnetworks'   => 'ipnetworks_id',
                                 'glpi_ipnetworks'               => 'ipnetworks_id',
                                 'glpi_ipnetworks_vlans'         => 'ipnetworks_id'],

                        "glpi_knowbaseitemcategories"
                        => ['glpi_itilcategories'         => 'knowbaseitemcategories_id',
                                 'glpi_knowbaseitemcategories' => 'knowbaseitemcategories_id',
                                 'glpi_knowbaseitems'          => 'knowbaseitemcategories_id'],

                        "glpi_knowbaseitems"
                        => ['glpi_entities_knowbaseitems' => 'knowbaseitems_id',
                                 'glpi_groups_knowbaseitems'   => 'knowbaseitems_id',
                                 'glpi_knowbaseitems_profiles' => 'knowbaseitems_id',
                                 'glpi_knowbaseitems_users'    => 'knowbaseitems_id'],

                        "glpi_links"
                        => ['_glpi_links_itemtypes' => 'links_id'],

                        "glpi_locations"
                        => ['glpi_budgets'           => 'locations_id',
                                 'glpi_cartridgeitems'    => 'locations_id',
                                 'glpi_consumableitems'   => 'locations_id',
                                 'glpi_computers'         => 'locations_id',
                                 'glpi_datacenters'       => 'locations_id',
                                 'glpi_dcrooms'           => 'locations_id',
                                 'glpi_enclosures'        => 'locations_id',
                                 'glpi_locations'         => 'locations_id',
                                 'glpi_monitors'          => 'locations_id',
                                 'glpi_netpoints'         => 'locations_id',
                                 'glpi_networkequipments' => 'locations_id',
                                 'glpi_pdus'              => 'locations_id',
                                 'glpi_peripherals'       => 'locations_id',
                                 'glpi_phones'            => 'locations_id',
                                 'glpi_printers'          => 'locations_id',
                                 'glpi_racks'             => 'locations_id',
                                 'glpi_softwarelicenses'  => 'locations_id',
                                 'glpi_softwares'         => 'locations_id',
                                 'glpi_tickets'           => 'locations_id',
                                 'glpi_certificates'      => 'locations_id',
                                 'glpi_users'             => 'locations_id'],

                        "glpi_manufacturers"
                        => ['glpi_cartridgeitems'      => 'manufacturers_id',
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
                                 'glpi_devicefirmwares'     => 'manufacturers_id',
                                 'glpi_devicebatteries'     => 'manufacturers_id',
                                 'glpi_devicegenerics'      => 'manufacturers_id',
                                 'glpi_devicesensors'       => 'manufacturers_id',
                                 'glpi_devicesimcards'      => 'manufacturers_id',
                                 'glpi_enclosures'          => 'manufacturers_id',
                                 'glpi_monitors'            => 'manufacturers_id',
                                 'glpi_networkequipments'   => 'manufacturers_id',
                                 'glpi_pdus'                => 'manufacturers_id',
                                 'glpi_peripherals'         => 'manufacturers_id',
                                 'glpi_phones'              => 'manufacturers_id',
                                 'glpi_printers'            => 'manufacturers_id',
                                 'glpi_certificates'        => 'manufacturers_id',
                                 'glpi_racks'               => 'manufacturers_id',
                                 'glpi_softwarelicenses'    => 'manufacturers_id',
                                 'glpi_softwares'           => 'manufacturers_id'],

                        "glpi_monitormodels"
                        => ['glpi_monitors' => 'monitormodels_id'],

                        "glpi_monitortypes"
                        => ['glpi_monitors' => 'monitortypes_id'],

                        "glpi_netpoints"
                        => ['glpi_networkportethernets'   => 'netpoints_id'],

                        "glpi_networkequipmentmodels"
                        => ['glpi_networkequipments' =>'networkequipmentmodels_id'],

                        "glpi_networkequipmenttypes"
                        => ['glpi_networkequipments' => 'networkequipmenttypes_id'],

                        "glpi_networknames"
                        => ['glpi_networkaliases'          => 'networknames_id'],

                        "glpi_networkports"
                        => ['glpi_networkportaggregates'     => 'networkports_id',
                                 'glpi_networkportaliases'        => ['networkports_id',
                                                                           'networkports_id_alias'],
                                 'glpi_networkportdialups'        => 'networkports_id',
                                 'glpi_networkportethernets'      => 'networkports_id',
                                 'glpi_networkportlocals'         => 'networkports_id',
                                 'glpi_networkports_vlans'        => 'networkports_id',
                                 'glpi_networkports_networkports' => ['networkports_id_1',
                                                                           'networkports_id_2'],
                                 'glpi_networkportwifis'          => 'networkports_id'],

                        "glpi_networkportwifis"
                        => ['glpi_networkportwifis' => 'networkportwifis_id'],

                        "glpi_networks"
                        => ['glpi_computers'         => 'networks_id',
                                 'glpi_printers'          => 'networks_id',
                                 'glpi_networkequipments' => 'networks_id'],

                        "glpi_operatingsystems"
                        => ['glpi_items_operatingsystems'  => 'operatingsystems_id',
                                 'glpi_softwareversions'       => 'operatingsystems_id'],

                        "glpi_operatingsystemservicepacks"
                        => ['glpi_items_operatingsystems' => 'operatingsystemservicepacks_id'],

                        "glpi_operatingsystemversions"
                        => ['glpi_items_operatingsystems' => 'operatingsystemversions_id'],

                        "glpi_pdumodels"
                        => ['glpi_pdus' => 'pdumodels_id'],

                        "glpi_pdutypes"
                        => ['glpi_pdus' => 'pdutypes_id'],

                        "glpi_peripheralmodels"
                        => ['glpi_peripherals' => 'peripheralmodels_id'],

                        "glpi_peripheraltypes"
                        => ['glpi_peripherals' => 'peripheraltypes_id'],

                        "glpi_phonemodels"
                        => ['glpi_phones' => 'phonemodels_id'],

                        "glpi_phoneoperators"
                        => ['glpi_devicesimcards' => 'phoneoperators_id'],

                        "glpi_phonepowersupplies"
                        => ['glpi_phones' => 'phonepowersupplies_id'],

                        "glpi_phonetypes"
                        => ['glpi_phones' => 'phonetypes_id'],

                        "glpi_printermodels"
                        => ['glpi_printers'                     => 'printermodels_id',
                                 'glpi_cartridgeitems_printermodels' => 'printermodels_id'],

                        "glpi_printers"
                        => ['glpi_cartridges' => 'printers_id'],

                        "glpi_printertypes"
                        => ['glpi_printers' => 'printertypes_id'],

                        "glpi_projects"
                        => ['glpi_projects'         => 'projects_id',
                                 'glpi_projecttasks'     => 'projects_id',
                                 'glpi_projectteams'     => 'projects_id',
                                 'glpi_items_projects'   => 'projects_id',
                                 'glpi_itils_projects'   => 'projects_id'],

                        "glpi_projectstates"
                        => ['glpi_projects'        => 'projectstates_id',
                                 'glpi_projecttasks'    => 'projectstates_id'],

                        "glpi_projecttasks"
                        => ['glpi_projecttasks'         => 'projecttasks_id',
                                 'glpi_projecttasks_tickets' => 'projecttasks_id',
                                 'glpi_projecttaskteams'     => 'projecttasks_id'],

                        "glpi_projecttasktypes"
                        => ['glpi_projecttasks'   => 'projecttasktypes_id'],

                        "glpi_projecttypes"
                        => ['glpi_projects'   => 'projecttypes_id'],

                        "glpi_problems"
                        => ['glpi_changes_problems'   => 'problems_id',
                                 'glpi_groups_problems'    => 'problems_id',
                                 'glpi_items_problems'     => 'problems_id',
                                 'glpi_problems_suppliers' => 'problems_id',
                                 'glpi_problems_tickets'   => 'problems_id',
                                 'glpi_problems_users'     => 'problems_id',
                                 'glpi_problemtasks'       => 'problems_id'],

                        "glpi_profiles"
                        => ['glpi_knowbaseitems_profiles' => 'profiles_id',
                                 'glpi_profilerights'          => 'profiles_id',
                                 'glpi_profiles_reminders'     => 'profiles_id',
                                 'glpi_profiles_users'         => 'profiles_id',
                                 'glpi_users'                  => 'profiles_id'],

                        "glpi_rackmodels"
                        => ['glpi_racks' => 'rackmodels_id'],

                        "glpi_racktypes"
                        => ['glpi_racks' => 'racktypes_id'],

                        "glpi_reminders"
                        => ['glpi_entities_reminders'  => 'reminders_id',
                                 'glpi_groups_reminders'    => 'reminders_id',
                                 'glpi_profiles_reminders'  => 'reminders_id',
                                 'glpi_reminders_users'     => 'reminders_id',],

                        "glpi_requesttypes"
                        => ['glpi_itilfollowups'  => 'requesttypes_id',
                                 'glpi_tickets'          => 'requesttypes_id',
                                 'glpi_users'            => 'default_requesttypes_id',
                                 'glpi_configs'          => 'default_requesttypes_id'],

                        "glpi_reservationitems"
                        => ['glpi_reservations' => 'reservationitems_id'],

                        "glpi_rules"
                        => ['glpi_ruleactions'                          => 'rules_id',
                                 'glpi_rulecriterias'                        => 'rules_id'],

                        "glpi_slalevels"
                        => ['glpi_slalevelactions'   => 'slalevels_id',
                                 'glpi_slalevelcriterias' => 'slalevels_id',
                                 'glpi_tickets'           => 'ttr_slalevels_id',
                                 'glpi_slalevels_tickets' => 'slalevels_id'],

                        "glpi_slas"
                        => ['glpi_slalevels' => 'slas_id',
                            'glpi_tickets'   => ['slas_ttr_id', 'slas_tto_id']],

                        "glpi_olalevels"
                        => ['glpi_olalevelactions'   => 'olalevels_id',
                            'glpi_olalevelcriterias' => 'olalevels_id',
                            'glpi_tickets'           => 'ttr_olalevels_id',
                            'glpi_olalevels_tickets' => 'olalevels_id'],

                        "glpi_olas"
                        => ['glpi_slalevels' => 'slas_id',
                            'glpi_tickets'   => ['olas_ttr_id', 'olas_tto_id']],

                        "glpi_softwarecategories"
                        => ['glpi_softwares' => 'softwarecategories_id',
                            'glpi_configs'   => 'softwarecategories_id_ondelete'],

                        "glpi_softwarelicensetypes"
                        => ['glpi_softwarelicenses' =>'softwarelicensetypes_id'],

                        "glpi_softwareversions"
                        => ['glpi_computers_softwareversions' => 'softwareversions_id',
                            'glpi_softwarelicenses'           => ['softwareversions_id_buy',
                                                                  'softwareversions_id_use']],

                        "glpi_softwarelicenses"
                        => ['glpi_computers_softwarelicenses' =>'softwarelicenses_id'],

                        "glpi_softwares"
                        => ['glpi_softwarelicenses' => 'softwares_id',
                            'glpi_softwareversions' => 'softwares_id',
                            'glpi_softwares'        => 'softwares_id'],

                        "glpi_solutiontypes"
                        => ['glpi_changes'           => 'solutiontypes_id',
                            'glpi_problems'          => 'solutiontypes_id',
                            'glpi_tickets'           => 'solutiontypes_id',
                            'glpi_solutiontemplates' => 'solutiontypes_id'],

                        "glpi_states"
                        => ['glpi_computers'         => 'states_id',
                                 'glpi_enclosures'        => 'states_id',
                                 'glpi_monitors'          => 'states_id',
                                 'glpi_networkequipments' => 'states_id',
                                 'glpi_pdus'             => 'states_id',
                                 'glpi_peripherals'       => 'states_id',
                                 'glpi_phones'            => 'states_id',
                                 'glpi_printers'          => 'states_id',
                                 'glpi_certificates'      => 'states_id',
                                 'glpi_racks'             => 'states_id',
                                 'glpi_softwarelicenses'  => 'states_id',
                                 'glpi_softwareversions'  => 'states_id',
                                 'glpi_states'            => 'states_id'],

                        "glpi_suppliers"
                        => ['glpi_changes_suppliers'   => 'suppliers_id',
                            'glpi_contacts_suppliers'  => 'suppliers_id',
                            'glpi_contracts_suppliers' => 'suppliers_id',
                            'glpi_infocoms'            => 'suppliers_id',
                            'glpi_problems_suppliers'  => 'suppliers_id',
                            'glpi_suppliers_tickets'   => 'suppliers_id',],

                        "glpi_suppliertypes"
                        => ['glpi_suppliers' => 'suppliertypes_id'],

                        "glpi_taskcategories"
                        => ['glpi_changetasks'    => 'taskcategories_id',
                            'glpi_problemtasks'   => 'taskcategories_id',
                            'glpi_taskcategories' => 'taskcategories_id',
                            'glpi_tickettasks'    => 'taskcategories_id',
                            'glpi_tasktemplates'  => 'taskcategories_id'],

                        "glpi_itilcategories"
                        => ['glpi_changes'         => 'itilcategories_id',
                            'glpi_itilcategories'  => 'itilcategories_id',
                            'glpi_tickets'         => 'itilcategories_id',
                            'glpi_problems'        => 'itilcategories_id'],

                        "glpi_tickettemplates"
                        => ['glpi_entities'            => 'tickettemplates_id',
                            'glpi_itilcategories'      => ['tickettemplates_id_incident',
                                                           'tickettemplates_id_demand'],
                            'glpi_ticketrecurrents'    => 'tickettemplates_id',
                            '_glpi_tickettemplatehiddenfields'
                                                       => 'tickettemplates_id',
                            '_glpi_tickettemplatepredefinedfields'
                                                       => 'tickettemplates_id',
                            '_glpi_tickettemplatemandatoryfields'
                                                       => 'tickettemplates_id'],

                        "glpi_tickets"
                        => ['_glpi_documents'          => 'tickets_id',
                            'glpi_changes_tickets'     => 'tickets_id',
                            'glpi_groups_tickets'      => 'tickets_id',
                            'glpi_problems_tickets'    => 'tickets_id',
                            'glpi_projecttasks_tickets'=> 'tickets_id',
                            'glpi_slalevels_tickets'   => 'tickets_id',
                            'glpi_suppliers_tickets'   => 'tickets_id',
                            'glpi_ticketsatisfactions' => 'tickets_id',
                            'glpi_tickettasks'         => 'tickets_id',
                            'glpi_ticketvalidations'   => 'tickets_id',
                            'glpi_tickets_tickets'     => ['tickets_id_1', 'tickets_id_2'],
                            'glpi_tickets_users'       => 'tickets_id'],

                        "glpi_solutiontypes"
                        => ['glpi_changes'             => 'solutiontypes_id',
                            'glpi_tickets'             => 'solutiontypes_id',
                            'glpi_solutiontemplates'   => 'solutiontypes_id',
                            'glpi_problems'            => 'solutiontypes_id'],

                        "glpi_ssovariables"
                        => ['glpi_configs' => 'ssovariables_id'],

                        "glpi_transfers"
                        => ['glpi_configs' => 'transfers_id_auto'],

                        "glpi_usercategories"
                        => ['glpi_users' => 'usercategories_id'],

                        "glpi_users"
                        => ['glpi_savedsearches'             => 'users_id',
                                 'glpi_savedsearches_users'       => 'users_id',
                                 'glpi_cartridgeitems'            => 'users_id_tech',
                                 'glpi_changes'                   => ['users_id_recipient',
                                                                           'users_id_lastupdater'],
                                 'glpi_changes_users'             => 'users_id',
                                 'glpi_changetasks'               => ['users_id', 'users_id_tech'],
                                 'glpi_computers'                 => ['users_id_tech', 'users_id'],
                                 'glpi_consumableitems'           => 'users_id_tech',
                                 'glpi_displaypreferences'        => 'users_id',
                                 'glpi_documents'                 => 'users_id',
                                 'glpi_enclosures'                => 'users_id_tech',
                                 'glpi_groups_users'              => 'users_id',
                                 'glpi_itilcategories'            => 'users_id',
                                 'glpi_knowbaseitems'             => 'users_id',
                                 'glpi_knowbaseitems_users'       => 'users_id',
                                 'glpi_monitors'                  => ['users_id_tech', 'users_id'],
                                 'glpi_networkequipments'         => ['users_id_tech', 'users_id'],
                                 'glpi_notimportedemails'         => 'users_id',
                                 'glpi_pdus'                      => 'users_id_tech',
                                 'glpi_peripherals'               => ['users_id_tech', 'users_id'],
                                 'glpi_phones'                    => ['users_id_tech', 'users_id'],
                                 'glpi_printers'                  => ['users_id_tech', 'users_id'],
                                 'glpi_certificates'              => ['users_id_tech', 'users_id'],
                                 'glpi_problems'                  => ['users_id_recipient',
                                                                           'users_id_lastupdater'],
                                 'glpi_problems_users'            => 'users_id',
                                 'glpi_problemtasks'              => ['users_id', 'users_id_tech'],
                                 'glpi_profiles_users'            => 'users_id',
                                 'glpi_projects'                  => 'users_id',
                                 'glpi_projecttasks'              => 'users_id',
                                 'glpi_racks'                     => 'users_id_tech',
                                 'glpi_reminders'                 => 'users_id',
                                 'glpi_reminders_users'           => 'users_id',
                                 'glpi_reservations'              => 'users_id',
                                 'glpi_softwarelicenses'          => ['users_id_tech', 'users_id'],
                                 'glpi_softwares'                 => ['users_id_tech', 'users_id'],
                                 'glpi_itilfollowups'           => 'users_id',
                                 'glpi_tickets'                   => ['users_id_recipient',
                                                                           'users_id_lastupdater'],
                                 'glpi_tickets_users'             => 'users_id',
                                 'glpi_tickettasks'               => ['users_id', 'users_id_tech'],
                                 'glpi_ticketvalidations'         => 'users_id',
                                 'glpi_ticketvalidations'         => 'users_id_validate',
                                 'glpi_useremails'                => 'users_id'],

                        "glpi_usertitles"
                        => ['glpi_contacts'   => 'usertitles_id',
                            'glpi_users'      => 'usertitles_id'],

                        "glpi_vlans"
                        => ['glpi_networkports_vlans' => 'vlans_id'],

                        "glpi_virtualmachinestates"
                        => ['glpi_computervirtualmachines' => 'virtualmachinestates_id'],

                        "glpi_virtualmachinesystems"
                        => ['glpi_computervirtualmachines' => 'virtualmachinesystems_id'],

                        "glpi_virtualmachinetypes"
                        => ['glpi_computervirtualmachines' => 'virtualmachinetypes_id'],

                        "glpi_wifinetworks"
                        => ['glpi_networkportwifis' => 'wifinetworks_id'],

                         // link from devices tables (computers, software, ...) : only used for unrecurs check
                         "_virtual_device" => ['glpi_contracts_items' => ['items_id', 'itemtype'],
                                               'glpi_documents_items' => ['items_id', 'itemtype'],
                                               'glpi_infocoms'        => ['items_id', 'itemtype']
                        //                     'glpi_ipaddresses'     => array('items_id', 'itemtype'),
                        //                     'glpi_networknames'    => array('items_id', 'itemtype'),
                                                    ]
                ];
