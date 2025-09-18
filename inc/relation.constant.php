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
use Glpi\Asset\Asset_PeripheralAsset;
use Glpi\CalDAV\Contracts\CalDAVCompatibleItemInterface;
use Glpi\Socket;

/**
 * Relation constants between tables.
 *
 * This mapping is used for to detect links between objects.
 * For example, it is used to detect if items are associated to another item that
 * is going to be deleted, in order to prevent deletion or ask for user what to do with
 * linked items.
 *
 * Format is:
 * [
 *    'referenced_table_name' => [
 *       'linked_table_name_1' => 'foreign_key_1',
 *       'linked_table_name_2' => ['foreign_key_2', 'foreign_key_3'],
 *       'linked_table_name_2' => [['items_id', 'itemtype']],
 *    ]
 * ]
 * where:
 *  - 'referenced_table_name' is the name of a table having its id referenced in other tables,
 *  - 'linked_table_name_*' is the name of a table that have foreign keys referencing the table 'referenced_table_name',
 *  - 'foreign_key_*' is the name of the field that is a foreign key (can be ['items_id', 'itemtype']).
 *
 * /!\ "_" prefix is used to disable usage check on relations while deleting an item when they are
 *     handled by application.
 *     Applications handle specific usage check and links updates :
 *      - in `CommonDBTM::cleanRelationTable()` method,
 *      - in `$item::cleanDBonPurge()` method,
 *      - using `$forward_entity_to` values,
 *      - by `CommonTreeDropdown` logic for recursive keys.
 *     Relations will still be used to check ability to disable recursivity on an element.
 *
 * /!\ Table's names are in alphabetic order - Please respect it
 *
 * @var array $RELATION
 */
$RELATION = [

    'glpi_agents' => [
        'glpi_refusedequipments' => 'agents_id',
        'glpi_rulematchedlogs'   => 'agents_id',
        'glpi_unmanageds'        => 'agents_id',
    ],

    'glpi_agenttypes' => [
        '_glpi_agents' => 'agenttypes_id',
    ],

    'glpi_applianceenvironments' => [
        'glpi_appliances' => 'applianceenvironments_id',
    ],

    'glpi_appliances'     => [
        '_glpi_appliances_items' => 'appliances_id',
    ],

    'glpi_appliances_items' => [
        '_glpi_appliances_items_relations' => 'appliances_items_id',
    ],

    'glpi_appliancetypes' => [
        'glpi_appliances' => 'appliancetypes_id',
    ],

    'glpi_assets_assetdefinitions' => [
        '_glpi_assets_assets' => 'assets_assetdefinitions_id',
        '_glpi_assets_assetmodels' => 'assets_assetdefinitions_id',
        '_glpi_assets_assettypes' => 'assets_assetdefinitions_id',
        '_glpi_assets_customfielddefinitions' => 'assets_assetdefinitions_id',
    ],

    'glpi_assets_assetmodels' => [
        'glpi_assets_assets' => 'assets_assetmodels_id',
    ],

    'glpi_assets_assettypes' => [
        'glpi_assets_assets' => 'assets_assettypes_id',
    ],

    'glpi_databaseinstancetypes' => [
        'glpi_databaseinstances' => 'databaseinstancetypes_id',
    ],

    'glpi_authldaps' => [
        'glpi_authldapreplicates' => 'authldaps_id',
        'glpi_entities'           => 'authldaps_id',
        'glpi_users'              => 'auths_id',
    ],

    'glpi_authmails' => [
        'glpi_users' => 'auths_id',
    ],

    'glpi_autoupdatesystems' => [
        'glpi_clusters'          => 'autoupdatesystems_id',
        'glpi_computers'         => 'autoupdatesystems_id',
        'glpi_databaseinstances' => 'autoupdatesystems_id',
        'glpi_monitors'          => 'autoupdatesystems_id',
        'glpi_networkequipments' => 'autoupdatesystems_id',
        'glpi_peripherals'       => 'autoupdatesystems_id',
        'glpi_phones'            => 'autoupdatesystems_id',
        'glpi_printers'          => 'autoupdatesystems_id',
        'glpi_refusedequipments' => 'autoupdatesystems_id',
        'glpi_unmanageds'        => 'autoupdatesystems_id',
        'glpi_assets_assets'     => 'autoupdatesystems_id',
    ],

    'glpi_budgets' => [
        'glpi_changecosts'   => 'budgets_id',
        'glpi_contractcosts' => 'budgets_id',
        'glpi_infocoms'      => 'budgets_id',
        'glpi_problemcosts'  => 'budgets_id',
        'glpi_projectcosts'  => 'budgets_id',
        'glpi_ticketcosts'   => 'budgets_id',
    ],

    'glpi_budgettypes' => [
        'glpi_budgets' => 'budgettypes_id',
    ],

    'glpi_businesscriticities' => [
        '_glpi_businesscriticities' => 'businesscriticities_id',
        'glpi_infocoms'             => 'businesscriticities_id',
    ],

    'glpi_cablestrands' => [
        'glpi_cables' => 'cablestrands_id',
    ],

    'glpi_cabletypes' => [
        'glpi_cables' => 'cabletypes_id',
    ],

    'glpi_calendars' => [
        '_glpi_calendars_holidays' => 'calendars_id',
        '_glpi_calendarsegments'   => 'calendars_id',
        'glpi_entities'            => 'calendars_id',
        'glpi_olas'                => 'calendars_id',
        'glpi_slas'                => 'calendars_id',
        'glpi_slms'                => 'calendars_id',
        'glpi_recurrentchanges'    => 'calendars_id',
        'glpi_ticketrecurrents'    => 'calendars_id',
        'glpi_pendingreasons'     => 'calendars_id',
    ],

    'glpi_cartridgeitems' => [
        '_glpi_cartridgeitems_printermodels' => 'cartridgeitems_id',
        '_glpi_cartridges'                   => 'cartridgeitems_id',
    ],

    'glpi_cartridgeitemtypes' => [
        'glpi_cartridgeitems' => 'cartridgeitemtypes_id',
    ],

    'glpi_certificates' => [
        '_glpi_certificates_items' => 'certificates_id',
    ],

    'glpi_certificatetypes' => [
        'glpi_certificates' => 'certificatetypes_id',
    ],

    'glpi_changes' => [
        '_glpi_changecosts'           => 'changes_id',
        '_glpi_changes_changes'       => [
            'changes_id_1',
            'changes_id_2',
        ],
        '_glpi_changes_groups'        => 'changes_id',
        '_glpi_changes_items'         => 'changes_id',
        '_glpi_changes_problems'      => 'changes_id',
        '_glpi_changes_suppliers'     => 'changes_id',
        '_glpi_changes_tickets'       => 'changes_id',
        '_glpi_changes_users'         => 'changes_id',
        '_glpi_changesatisfactions'   => 'changes_id',
        '_glpi_changetasks'           => 'changes_id',
        '_glpi_changevalidations'     => 'changes_id',
        '_glpi_itils_projects'        => [['items_id', 'itemtype']],
        '_glpi_itilfollowups'         => [['items_id', 'itemtype']],
        '_glpi_itils_validationsteps' => [['items_id', 'itemtype']],
        '_glpi_itilsolutions'         => [['items_id', 'itemtype']],
    ],

    'glpi_changetemplates' => [
        'glpi_entities'                        => 'changetemplates_id',
        'glpi_itilcategories'                  => [
            'changetemplates_id',
        ],
        'glpi_changes'                         => 'changetemplates_id',
        '_glpi_changetemplatehiddenfields'     => 'changetemplates_id',
        '_glpi_changetemplatemandatoryfields'  => 'changetemplates_id',
        '_glpi_changetemplatepredefinedfields' => 'changetemplates_id',
        '_glpi_changetemplatereadonlyfields'   => 'changetemplates_id',
        'glpi_profiles'                        => 'changetemplates_id',
        'glpi_recurrentchanges'                => 'changetemplates_id',
    ],

    'glpi_clusters' => [
        '_glpi_items_clusters' => 'clusters_id',
    ],

    'glpi_clustertypes' => [
        'glpi_clusters' => 'clustertypes_id',
    ],

    'glpi_computermodels' => [
        'glpi_computers' => 'computermodels_id',
    ],

    'glpi_computers' => [
        'glpi_networknames' => [['items_id', 'itemtype']], // FIXME Find a list that can be used to declare this polymorphic relation
    ],

    'glpi_computertypes' => [
        'glpi_computers' => 'computertypes_id',
    ],

    'glpi_consumableitems' => [
        '_glpi_consumables' => 'consumableitems_id',
    ],

    'glpi_consumableitemtypes' => [
        'glpi_consumableitems' => 'consumableitemtypes_id',
    ],

    'glpi_contacts' => [
        '_glpi_contacts_suppliers' => 'contacts_id',
    ],

    'glpi_contacttypes' => [
        'glpi_contacts' => 'contacttypes_id',
    ],

    'glpi_contracts' => [
        '_glpi_contractcosts'       => 'contracts_id',
        '_glpi_contracts_items'     => 'contracts_id',
        '_glpi_contracts_suppliers' => 'contracts_id',
        'glpi_entities'             => 'contracts_id_default',
        '_glpi_tickets_contracts'   => 'contracts_id',
        '_glpi_contracts_users'    => 'contracts_id',
    ],

    'glpi_contracttypes' => [
        'glpi_contracts' => 'contracttypes_id',
    ],

    'glpi_crontasklogs' => [
        '_glpi_crontasklogs' => 'crontasklogs_id',
    ],

    'glpi_crontasks' => [
        '_glpi_crontasklogs' => 'crontasks_id',
    ],

    'glpi_dashboards_dashboards' => [
        '_glpi_dashboards_filters' => 'dashboards_dashboards_id',
        '_glpi_dashboards_items'   => 'dashboards_dashboards_id',
        '_glpi_dashboards_rights'  => 'dashboards_dashboards_id',
    ],

    'glpi_databaseinstancecategories' => [
        'glpi_databaseinstances' => 'databaseinstancecategories_id',
    ],

    'glpi_databaseinstances' => [
        'glpi_databases' => 'databaseinstances_id',
    ],

    'glpi_datacenters' => [
        'glpi_dcrooms' => 'datacenters_id',
    ],

    'glpi_dcrooms' => [
        'glpi_racks' => 'dcrooms_id',
    ],

    'glpi_devicebatteries' => [
        'glpi_items_devicebatteries' => 'devicebatteries_id',
    ],

    'glpi_devicebatterymodels' => [
        'glpi_devicebatteries' => 'devicebatterymodels_id',
    ],

    'glpi_devicecameramodels' => [
        'glpi_devicecameras' => 'devicecameramodels_id',
    ],

    'glpi_devicecameras' => [
        'glpi_items_devicecameras' => 'devicecameras_id',
    ],

    'glpi_devicebatterytypes' => [
        'glpi_devicebatteries' => 'devicebatterytypes_id',
    ],

    'glpi_devicecasemodels' => [
        'glpi_devicecases' => 'devicecasemodels_id',
    ],

    'glpi_devicecases' => [
        'glpi_items_devicecases' => 'devicecases_id',
    ],

    'glpi_devicecasetypes' => [
        'glpi_devicecases' => 'devicecasetypes_id',
    ],

    'glpi_devicecontrolmodels' => [
        'glpi_devicecontrols' => 'devicecontrolmodels_id',
    ],

    'glpi_devicecontrols' => [
        'glpi_items_devicecontrols' => 'devicecontrols_id',
    ],

    'glpi_devicedrivemodels' => [
        'glpi_devicedrives' => 'devicedrivemodels_id',
    ],

    'glpi_devicedrives' => [
        'glpi_items_devicedrives' => 'devicedrives_id',
    ],

    'glpi_devicefirmwaremodels' => [
        'glpi_devicefirmwares' => 'devicefirmwaremodels_id',
    ],

    'glpi_devicefirmwares' => [
        'glpi_items_devicefirmwares' => 'devicefirmwares_id',
    ],

    'glpi_devicefirmwaretypes' => [
        'glpi_devicefirmwares' => 'devicefirmwaretypes_id',
    ],

    'glpi_devicegenericmodels' => [
        'glpi_devicegenerics' => 'devicegenericmodels_id',
    ],

    'glpi_devicegenerics' => [
        'glpi_items_devicegenerics' => 'devicegenerics_id',
    ],

    'glpi_devicegenerictypes' => [
        'glpi_devicegenerics' => 'devicegenerictypes_id',
    ],

    'glpi_devicegraphiccardmodels' => [
        'glpi_devicegraphiccards' => 'devicegraphiccardmodels_id',
    ],

    'glpi_devicegraphiccards' => [
        'glpi_items_devicegraphiccards' => 'devicegraphiccards_id',
    ],

    'glpi_deviceharddrivemodels' => [
        'glpi_deviceharddrives' => 'deviceharddrivemodels_id',
    ],

    'glpi_deviceharddrivetypes' => [
        'glpi_deviceharddrives' => 'deviceharddrivetypes_id',
    ],

    'glpi_deviceharddrives' => [
        'glpi_items_deviceharddrives' => 'deviceharddrives_id',
    ],

    'glpi_devicememories' => [
        'glpi_items_devicememories' => 'devicememories_id',
    ],

    'glpi_devicememorymodels' => [
        'glpi_devicememories' => 'devicememorymodels_id',
    ],

    'glpi_devicememorytypes' => [
        'glpi_devicememories' => 'devicememorytypes_id',
    ],

    'glpi_devicemotherboardmodels' => [
        'glpi_devicemotherboards' => 'devicemotherboardmodels_id',
    ],

    'glpi_devicemotherboards' => [
        'glpi_items_devicemotherboards' => 'devicemotherboards_id',
    ],

    'glpi_devicenetworkcardmodels' => [
        'glpi_devicenetworkcards' => 'devicenetworkcardmodels_id',
        'glpi_devicepcis'         => 'devicenetworkcardmodels_id', // FIXME This field should probably removed
    ],

    'glpi_devicenetworkcards' => [
        'glpi_items_devicenetworkcards' => 'devicenetworkcards_id',
    ],

    'glpi_devicepcimodels' => [
        'glpi_devicepcis' => 'devicepcimodels_id',
    ],

    'glpi_devicepcis' => [
        'glpi_items_devicepcis' => 'devicepcis_id',
    ],

    'glpi_devicepowersupplies' => [
        'glpi_items_devicepowersupplies' => 'devicepowersupplies_id',
    ],

    'glpi_devicepowersupplymodels' => [
        'glpi_devicepowersupplies' => 'devicepowersupplymodels_id',
    ],

    'glpi_deviceprocessormodels' => [
        'glpi_deviceprocessors' => 'deviceprocessormodels_id',
    ],

    'glpi_deviceprocessors' => [
        'glpi_items_deviceprocessors' => 'deviceprocessors_id',
    ],

    'glpi_devicesensormodels' => [
        'glpi_devicesensors' => 'devicesensormodels_id',
    ],

    'glpi_devicesensors' => [
        'glpi_items_devicesensors' => 'devicesensors_id',
    ],

    'glpi_devicesensortypes' => [
        'glpi_devicesensors' => 'devicesensortypes_id',
    ],

    'glpi_devicesimcards' => [
        'glpi_items_devicesimcards' => 'devicesimcards_id',
    ],

    'glpi_devicesimcardtypes' => [
        'glpi_devicesimcards' => 'devicesimcardtypes_id',
    ],

    'glpi_devicesoundcardmodels' => [
        'glpi_devicesoundcards' => 'devicesoundcardmodels_id',
    ],

    'glpi_devicesoundcards' => [
        'glpi_items_devicesoundcards' => 'devicesoundcards_id',
    ],

    'glpi_documentcategories' => [
        'glpi_documentcategories' => 'documentcategories_id',
        'glpi_documents'          => 'documentcategories_id',
    ],

    'glpi_documents' => [
        '_glpi_documents_items' => 'documents_id',
    ],

    'glpi_domainrelations' => [
        'glpi_domains_items' => 'domainrelations_id',
    ],

    'glpi_domains'    => [
        '_glpi_domainrecords' => 'domains_id',
        '_glpi_domains_items' => 'domains_id',
    ],

    'glpi_domaintypes' => [
        'glpi_domains'  => 'domaintypes_id',
    ],

    'glpi_domainrecordtypes'    => [
        'glpi_domainrecords'  => 'domainrecordtypes_id',
    ],

    'glpi_dropdowns_dropdowndefinitions' => [
        '_glpi_dropdowns_dropdowns' => 'dropdowns_dropdowndefinitions_id',
    ],

    'glpi_dropdowns_dropdowns' => [
        'glpi_dropdowns_dropdowns' => 'dropdowns_dropdowns_id',
    ],

    'glpi_enclosuremodels' => [
        'glpi_enclosures' => 'enclosuremodels_id',
    ],

    'glpi_enclosures' => [
        '_glpi_items_enclosures' => 'enclosures_id',
    ],

    'glpi_entities' => [
        'glpi_agents'                      => 'entities_id',
        'glpi_apiclients'                  => 'entities_id',
        'glpi_appliances'                  => 'entities_id',
        'glpi_appliancetypes'              => 'entities_id',
        'glpi_assets_assets'               => 'entities_id',
        'glpi_budgets'                     => 'entities_id',
        'glpi_businesscriticities'         => 'entities_id',
        'glpi_cables'                      => 'entities_id',
        'glpi_calendars'                   => 'entities_id',
        '_glpi_calendarsegments'           => 'entities_id',
        'glpi_cartridgeitems'              => 'entities_id',
        '_glpi_cartridges'                 => 'entities_id',
        'glpi_certificates'                => 'entities_id',
        'glpi_certificatetypes'            => 'entities_id',
        '_glpi_changecosts'                => 'entities_id',
        'glpi_changes'                     => 'entities_id',
        'glpi_changetemplates'             => 'entities_id',
        '_glpi_changevalidations'          => 'entities_id',
        'glpi_clusters'                    => 'entities_id',
        'glpi_clustertypes'                => 'entities_id',
        'glpi_computers'                   => 'entities_id',
        'glpi_dropdowns_dropdowns'         => 'entities_id',
        'glpi_consumableitems'             => 'entities_id',
        '_glpi_consumables'                => 'entities_id',
        'glpi_contacts'                    => 'entities_id',
        '_glpi_contractcosts'              => 'entities_id',
        'glpi_contracts'                   => 'entities_id',
        'glpi_databaseinstances'           => 'entities_id',
        '_glpi_databases'                  => 'entities_id', // forwarded by Database
        'glpi_datacenters'                 => 'entities_id',
        'glpi_dcrooms'                     => 'entities_id',
        'glpi_devicebatteries'             => 'entities_id',
        'glpi_devicecameras'               => 'entities_id',
        'glpi_devicecases'                 => 'entities_id',
        'glpi_devicecontrols'              => 'entities_id',
        'glpi_devicedrives'                => 'entities_id',
        'glpi_devicefirmwares'             => 'entities_id',
        'glpi_devicegenerics'              => 'entities_id',
        'glpi_devicegraphiccards'          => 'entities_id',
        'glpi_deviceharddrives'            => 'entities_id',
        'glpi_devicememories'              => 'entities_id',
        'glpi_devicemotherboards'          => 'entities_id',
        'glpi_devicenetworkcards'          => 'entities_id',
        'glpi_devicepcis'                  => 'entities_id',
        'glpi_devicepowersupplies'         => 'entities_id',
        'glpi_deviceprocessors'            => 'entities_id',
        'glpi_devicesensors'               => 'entities_id',
        'glpi_devicesimcards'              => 'entities_id',
        'glpi_devicesoundcards'            => 'entities_id',
        'glpi_documents'                   => 'entities_id',
        '_glpi_documents_items'            => 'entities_id',
        'glpi_domainrelations'             => 'entities_id',
        'glpi_domainrecords'               => 'entities_id',
        'glpi_domainrecordtypes'           => 'entities_id',
        'glpi_domains'                     => 'entities_id',
        'glpi_domaintypes'                 => 'entities_id',
        'glpi_enclosures'                  => 'entities_id',
        '_glpi_entities'                   => 'entities_id',
        'glpi_entities'                    => 'entities_id_software',
        '_glpi_entities_knowbaseitems'     => 'entities_id',
        '_glpi_entities_reminders'         => 'entities_id',
        '_glpi_entities_rssfeeds'          => 'entities_id',
        'glpi_fieldblacklists'             => 'entities_id',
        'glpi_fieldunicities'              => 'entities_id',
        'glpi_forms_forms'                 => 'entities_id',
        'glpi_forms_answerssets'           => 'entities_id',
        'glpi_fqdns'                       => 'entities_id',
        'glpi_groups'                      => 'entities_id',
        'glpi_groups_knowbaseitems'        => 'entities_id',
        'glpi_groups_reminders'            => 'entities_id',
        'glpi_groups_rssfeeds'             => 'entities_id',
        'glpi_holidays'                    => 'entities_id',
        'glpi_imageformats'                => 'entities_id',
        'glpi_imageresolutions'            => 'entities_id',
        '_glpi_infocoms'                   => 'entities_id',
        'glpi_ipaddresses'                 => 'entities_id',
        'glpi_ipnetworks'                  => 'entities_id',
        '_glpi_items_devicebatteries'      => 'entities_id',
        '_glpi_items_devicecases'          => 'entities_id',
        '_glpi_items_devicecameras'        => 'entities_id', // forwarded by DeviceCamera
        '_glpi_items_devicecontrols'       => 'entities_id',
        '_glpi_items_devicedrives'         => 'entities_id',
        '_glpi_items_devicefirmwares'      => 'entities_id',
        '_glpi_items_devicegenerics'       => 'entities_id',
        '_glpi_items_devicegraphiccards'   => 'entities_id',
        '_glpi_items_deviceharddrives'     => 'entities_id',
        '_glpi_items_devicememories'       => 'entities_id',
        '_glpi_items_devicemotherboards'   => 'entities_id',
        '_glpi_items_devicenetworkcards'   => 'entities_id',
        '_glpi_items_devicepcis'           => 'entities_id',
        '_glpi_items_devicepowersupplies'  => 'entities_id',
        '_glpi_items_deviceprocessors'     => 'entities_id',
        '_glpi_items_devicesensors'        => 'entities_id',
        '_glpi_items_devicesimcards'       => 'entities_id',
        '_glpi_items_devicesoundcards'     => 'entities_id',
        '_glpi_items_disks'                => 'entities_id',
        '_glpi_items_operatingsystems'     => 'entities_id',
        '_glpi_items_softwareversions'     => 'entities_id',
        '_glpi_itemvirtualmachines'        => 'entities_id',
        'glpi_itilcategories'              => 'entities_id',
        'glpi_itilfollowuptemplates'       => 'entities_id',
        'glpi_itilvalidationtemplates'     => 'entities_id',
        'glpi_knowbaseitemcategories'      => 'entities_id',
        'glpi_knowbaseitems'               => 'entities_id',
        'glpi_knowbaseitems_profiles'      => 'entities_id',
        'glpi_lineoperators'               => 'entities_id',
        'glpi_lines'                       => 'entities_id',
        'glpi_links'                       => 'entities_id',
        'glpi_locations'                   => 'entities_id',
        'glpi_monitors'                    => 'entities_id',
        '_glpi_networkaliases'             => 'entities_id',
        'glpi_networkequipments'           => 'entities_id',
        'glpi_networknames'                => 'entities_id',
        '_glpi_networkports'               => 'entities_id',
        'glpi_networkporttypes'            => 'entities_id',
        'glpi_notifications'               => 'entities_id',
        '_glpi_olalevels'                  => 'entities_id',
        '_glpi_olas'                       => 'entities_id',
        'glpi_passivedcequipments'         => 'entities_id',
        'glpi_pcivendors'                  => 'entities_id',
        'glpi_pdus'                        => 'entities_id',
        'glpi_pdutypes'                    => 'entities_id',
        'glpi_pendingreasons'              => 'entities_id',
        'glpi_peripherals'                 => 'entities_id',
        'glpi_phones'                      => 'entities_id',
        'glpi_planningexternalevents'      => 'entities_id',
        'glpi_planningexternaleventtemplates' => 'entities_id',
        'glpi_printers'                    => 'entities_id',
        '_glpi_problemcosts'               => 'entities_id',
        'glpi_problems'                    => 'entities_id',
        'glpi_problemtemplates'            => 'entities_id',
        'glpi_profiles_reminders'          => 'entities_id',
        'glpi_profiles_rssfeeds'           => 'entities_id',
        '_glpi_profiles_users'             => 'entities_id',
        '_glpi_projectcosts'               => 'entities_id',
        'glpi_projects'                    => 'entities_id',
        '_glpi_projecttasks'               => 'entities_id',
        'glpi_projecttasktemplates'        => 'entities_id',
        'glpi_queuednotifications'         => 'entities_id',
        'glpi_racks'                       => 'entities_id',
        'glpi_racktypes'                   => 'entities_id',
        'glpi_recurrentchanges'            => 'entities_id',
        'glpi_refusedequipments'           => 'entities_id',
        '_glpi_reservationitems'           => 'entities_id',
        'glpi_rules'                       => 'entities_id',
        'glpi_savedsearches'               => 'entities_id',
        '_glpi_slalevels'                  => 'entities_id',
        '_glpi_slas'                       => 'entities_id',
        'glpi_slms'                        => 'entities_id',
        'glpi_softwarelicenses'            => 'entities_id',
        'glpi_softwarelicensetypes'        => 'entities_id',
        'glpi_softwares'                   => 'entities_id',
        '_glpi_softwareversions'           => 'entities_id',
        'glpi_solutiontemplates'           => 'entities_id',
        'glpi_solutiontypes'               => 'entities_id',
        'glpi_states'                      => 'entities_id',
        'glpi_suppliers'                   => 'entities_id',
        'glpi_taskcategories'              => 'entities_id',
        'glpi_tasktemplates'               => 'entities_id',
        '_glpi_ticketcosts'                => 'entities_id',
        'glpi_ticketrecurrents'            => 'entities_id',
        'glpi_tickets'                     => 'entities_id',
        'glpi_tickettemplates'             => 'entities_id',
        '_glpi_ticketvalidations'          => 'entities_id',
        'glpi_unmanageds'                  => 'entities_id',
        'glpi_usbvendors'                  => 'entities_id',
        'glpi_users'                       => 'entities_id',
        'glpi_vlans'                       => 'entities_id',
        'glpi_wifinetworks'                => 'entities_id',
        'glpi_webhooks'                    => 'entities_id',
        'glpi_queuedwebhooks'              => 'entities_id',
    ],

    'glpi_filesystems' => [
        'glpi_items_disks' => 'filesystems_id',
    ],

    'glpi_forms_answerssets' => [
        "_glpi_forms_destinations_answerssets_formdestinationitems" => "forms_answerssets_id",
    ],

    'glpi_forms_categories' => [
        'glpi_forms_categories' => 'forms_categories_id',
        'glpi_forms_forms' => 'forms_categories_id',
        'glpi_knowbaseitems' => 'forms_categories_id',
    ],

    'glpi_forms_forms' => [
        "_glpi_forms_accesscontrols_formaccesscontrols" => "forms_forms_id",
        "_glpi_forms_answerssets"                       => "forms_forms_id",
        "_glpi_forms_destinations_formdestinations"     => "forms_forms_id",
        "_glpi_forms_sections"                          => "forms_forms_id",
        "_glpi_helpdesks_tiles_formtiles"               => "forms_forms_id",
    ],

    'glpi_forms_sections' => [
        "_glpi_forms_questions" => "forms_sections_id",
        "_glpi_forms_comments" => "forms_sections_id",
    ],

    'glpi_fqdns' => [
        'glpi_networkaliases' => 'fqdns_id',
        'glpi_networknames'   => 'fqdns_id',
    ],

    'glpi_groups' => [
        '_glpi_changes_groups'       => 'groups_id',
        'glpi_changetasks'           => 'groups_id_tech',
        'glpi_groups'                => 'groups_id',
        '_glpi_groups_items'         => 'groups_id',
        '_glpi_groups_knowbaseitems' => 'groups_id',
        '_glpi_groups_problems'      => 'groups_id',
        '_glpi_groups_reminders'     => 'groups_id',
        '_glpi_groups_rssfeeds'      => 'groups_id',
        '_glpi_groups_tickets'       => 'groups_id',
        '_glpi_groups_users'         => 'groups_id',
        'glpi_itilcategories'        => 'groups_id',
        'glpi_planningexternalevents' => 'groups_id',
        'glpi_problemtasks'           => 'groups_id_tech',
        'glpi_projects'               => 'groups_id',
        'glpi_tasktemplates'          => 'groups_id_tech',
        'glpi_tickettasks'            => 'groups_id_tech',
        'glpi_users'                  => 'groups_id',
        'glpi_itilvalidationtemplates_targets' => 'groups_id',
    ],

    'glpi_holidays' => [
        '_glpi_calendars_holidays' => 'holidays_id',
    ],

    'glpi_imageformats' => [
        '_glpi_items_devicecameras_imageformats' => 'imageformats_id',
    ],

    'glpi_imageresolutions' => [
        '_glpi_items_devicecameras_imageresolutions' => 'imageresolutions_id',
    ],

    'glpi_impactcontexts' => [
        'glpi_impactitems' => 'impactcontexts_id',
    ],

    'glpi_interfacetypes' => [
        'glpi_devicecontrols'     => 'interfacetypes_id',
        'glpi_devicedrives'       => 'interfacetypes_id',
        'glpi_devicegraphiccards' => 'interfacetypes_id',
        'glpi_deviceharddrives'   => 'interfacetypes_id',
    ],

    'glpi_ipaddresses' => [
        '_glpi_ipaddresses_ipnetworks' => 'ipaddresses_id',
    ],

    'glpi_ipnetworks' => [
        '_glpi_ipaddresses_ipnetworks' => 'ipnetworks_id',
        'glpi_networknames'            => 'ipnetworks_id',
        'glpi_ipnetworks'              => 'ipnetworks_id',
        '_glpi_ipnetworks_vlans'       => 'ipnetworks_id',
    ],

    'glpi_items_devicecameras' => [
        '_glpi_items_devicecameras_imageformats' => 'items_devicecameras_id',
        '_glpi_items_devicecameras_imageresolutions' => 'items_devicecameras_id',
    ],

    'glpi_items_devicenetworkcards' => [
        'glpi_networkportethernets'     => 'items_devicenetworkcards_id',
        'glpi_networkportfiberchannels' => 'items_devicenetworkcards_id',
        'glpi_networkportwifis'         => 'items_devicenetworkcards_id',
    ],

    'glpi_itilcategories' => [
        'glpi_changes'        => 'itilcategories_id',
        'glpi_itilcategories' => 'itilcategories_id',
        'glpi_problems'       => 'itilcategories_id',
        'glpi_tickets'        => 'itilcategories_id',
    ],

    'glpi_itilfollowups' => [
        'glpi_itilsolutions' => 'itilfollowups_id',
    ],

    'glpi_itilfollowuptemplates' => [
        'glpi_pendingreasons' => 'itilfollowuptemplates_id',
    ],

    'glpi_itilvalidationtemplates' => [
        '_glpi_itilvalidationtemplates_targets' => 'itilvalidationtemplates_id',
        'glpi_changevalidations' => 'itilvalidationtemplates_id',
        'glpi_ticketvalidations' => 'itilvalidationtemplates_id',
    ],

    'glpi_itils_validationsteps' => [
        'glpi_ticketvalidations' => 'itils_validationsteps_id',
        'glpi_changevalidations' => 'itils_validationsteps_id',
    ],

    'glpi_knowbaseitemcategories' => [
        'glpi_itilcategories'            => 'knowbaseitemcategories_id',
        'glpi_knowbaseitemcategories'    => 'knowbaseitemcategories_id',
        '_glpi_knowbaseitems_knowbaseitemcategories' => 'knowbaseitemcategories_id',
        'glpi_taskcategories'            => 'knowbaseitemcategories_id',
    ],

    'glpi_knowbaseitems' => [
        '_glpi_entities_knowbaseitems'   => 'knowbaseitems_id',
        '_glpi_groups_knowbaseitems'     => 'knowbaseitems_id',
        '_glpi_knowbaseitems_comments'   => 'knowbaseitems_id',
        '_glpi_knowbaseitems_items'      => 'knowbaseitems_id',
        '_glpi_knowbaseitems_profiles'   => 'knowbaseitems_id',
        '_glpi_knowbaseitems_revisions'  => 'knowbaseitems_id',
        '_glpi_knowbaseitems_users'      => 'knowbaseitems_id',
        '_glpi_knowbaseitemtranslations' => 'knowbaseitems_id',
        '_glpi_knowbaseitems_knowbaseitemcategories' => 'knowbaseitems_id',
    ],

    'glpi_knowbaseitems_comments' => [
        'glpi_knowbaseitems_comments' => 'parent_comment_id',
    ],

    'glpi_lineoperators' => [
        'glpi_lines' => 'lineoperators_id',
    ],

    'glpi_lines' => [
        'glpi_items_devicesimcards' => 'lines_id',
        '_glpi_items_lines' => 'lines_id',
    ],

    'glpi_linetypes' => [
        'glpi_lines' => 'linetypes_id',
    ],

    'glpi_links' => [
        '_glpi_links_itemtypes' => 'links_id',
    ],

    'glpi_locations' => [
        'glpi_appliances'                => 'locations_id',
        'glpi_assets_assets'             => 'locations_id',
        'glpi_budgets'                   => 'locations_id',
        'glpi_cartridgeitems'            => 'locations_id',
        'glpi_certificates'              => 'locations_id',
        'glpi_changes'                   => 'locations_id',
        'glpi_computers'                 => 'locations_id',
        'glpi_contracts'                 => 'locations_id',
        'glpi_consumableitems'           => 'locations_id',
        'glpi_databaseinstances'         => 'locations_id',
        'glpi_datacenters'               => 'locations_id',
        'glpi_dcrooms'                   => 'locations_id',
        'glpi_devicegenerics'            => 'locations_id',
        'glpi_devicesensors'             => 'locations_id',
        'glpi_enclosures'                => 'locations_id',
        'glpi_items_devicebatteries'     => 'locations_id',
        'glpi_items_devicecameras'       => 'locations_id',
        'glpi_items_devicecases'         => 'locations_id',
        'glpi_items_devicecontrols'      => 'locations_id',
        'glpi_items_devicedrives'        => 'locations_id',
        'glpi_items_devicefirmwares'     => 'locations_id',
        'glpi_items_devicegenerics'      => 'locations_id',
        'glpi_items_devicegraphiccards'  => 'locations_id',
        'glpi_items_deviceharddrives'    => 'locations_id',
        'glpi_items_devicememories'      => 'locations_id',
        'glpi_items_devicemotherboards'  => 'locations_id',
        'glpi_items_devicenetworkcards'  => 'locations_id',
        'glpi_items_devicepcis'          => 'locations_id',
        'glpi_items_devicepowersupplies' => 'locations_id',
        'glpi_items_deviceprocessors'    => 'locations_id',
        'glpi_items_devicesensors'       => 'locations_id',
        'glpi_items_devicesimcards'      => 'locations_id',
        'glpi_items_devicesoundcards'    => 'locations_id',
        'glpi_lines'                     => 'locations_id',
        'glpi_locations'                 => 'locations_id',
        'glpi_monitors'                  => 'locations_id',
        'glpi_networkequipments'         => 'locations_id',
        'glpi_passivedcequipments'       => 'locations_id',
        'glpi_pdus'                      => 'locations_id',
        'glpi_peripherals'               => 'locations_id',
        'glpi_phones'                    => 'locations_id',
        'glpi_printers'                  => 'locations_id',
        'glpi_problems'                  => 'locations_id',
        'glpi_racks'                     => 'locations_id',
        'glpi_sockets'                   => 'locations_id',
        'glpi_softwarelicenses'          => 'locations_id',
        'glpi_softwares'                 => 'locations_id',
        'glpi_tickets'                   => 'locations_id',
        'glpi_unmanageds'                => 'locations_id',
        'glpi_users'                     => 'locations_id',
    ],

    'glpi_mailcollectors' => [
        'glpi_notimportedemails' => 'mailcollectors_id',
    ],

    'glpi_manufacturers' => [
        'glpi_appliances'          => 'manufacturers_id',
        'glpi_assets_assets'       => 'manufacturers_id',
        'glpi_cartridgeitems'      => 'manufacturers_id',
        'glpi_certificates'        => 'manufacturers_id',
        'glpi_itemantiviruses'    => 'manufacturers_id',
        'glpi_computers'           => 'manufacturers_id',
        'glpi_consumableitems'     => 'manufacturers_id',
        'glpi_databaseinstances'   => 'manufacturers_id',
        'glpi_devicebatteries'     => 'manufacturers_id',
        'glpi_devicecameras'       => 'manufacturers_id',
        'glpi_devicecases'         => 'manufacturers_id',
        'glpi_devicecontrols'      => 'manufacturers_id',
        'glpi_devicedrives'        => 'manufacturers_id',
        'glpi_devicefirmwares'     => 'manufacturers_id',
        'glpi_devicegenerics'      => 'manufacturers_id',
        'glpi_devicegraphiccards'  => 'manufacturers_id',
        'glpi_deviceharddrives'    => 'manufacturers_id',
        'glpi_devicememories'      => 'manufacturers_id',
        'glpi_devicemotherboards'  => 'manufacturers_id',
        'glpi_devicenetworkcards'  => 'manufacturers_id',
        'glpi_devicepcis'          => 'manufacturers_id',
        'glpi_devicepowersupplies' => 'manufacturers_id',
        'glpi_deviceprocessors'    => 'manufacturers_id',
        'glpi_devicesensors'       => 'manufacturers_id',
        'glpi_devicesimcards'      => 'manufacturers_id',
        'glpi_devicesoundcards'    => 'manufacturers_id',
        'glpi_enclosures'          => 'manufacturers_id',
        'glpi_monitors'            => 'manufacturers_id',
        'glpi_networkequipments'   => 'manufacturers_id',
        'glpi_passivedcequipments' => 'manufacturers_id',
        'glpi_pdus'                => 'manufacturers_id',
        'glpi_peripherals'         => 'manufacturers_id',
        'glpi_phones'              => 'manufacturers_id',
        'glpi_printers'            => 'manufacturers_id',
        'glpi_racks'               => 'manufacturers_id',
        'glpi_softwarelicenses'    => 'manufacturers_id',
        'glpi_softwares'           => 'manufacturers_id',
        'glpi_unmanageds'          => 'manufacturers_id',
    ],

    'glpi_monitormodels' => [
        'glpi_monitors' => 'monitormodels_id',
    ],

    'glpi_monitortypes' => [
        'glpi_monitors' => 'monitortypes_id',
    ],

    'glpi_networkequipmentmodels' => [
        'glpi_networkequipments' => 'networkequipmentmodels_id',
    ],

    'glpi_networkequipmenttypes' => [
        'glpi_networkequipments' => 'networkequipmenttypes_id',
    ],

    'glpi_networknames' => [
        '_glpi_networkaliases' => 'networknames_id',
    ],

    'glpi_networkportfiberchanneltypes' => [
        'glpi_networkportfiberchannels' => 'networkportfiberchanneltypes_id',
    ],

    'glpi_networkports' => [
        '_glpi_networkportaggregates'     => 'networkports_id',
        '_glpi_networkportaliases'        => 'networkports_id',
        'glpi_networkportaliases'         => 'networkports_id_alias',
        'glpi_networkportconnectionlogs'  => [
            'networkports_id_destination',
            'networkports_id_source',
        ],
        '_glpi_networkportdialups'        => 'networkports_id',
        '_glpi_networkportethernets'      => 'networkports_id',
        '_glpi_networkportfiberchannels'  => 'networkports_id',
        '_glpi_networkportlocals'         => 'networkports_id',
        '_glpi_networkportmetrics'        => 'networkports_id',
        '_glpi_networkports_networkports' => [
            'networkports_id_1',
            'networkports_id_2',
        ],
        '_glpi_networkports_vlans'        => 'networkports_id',
        '_glpi_networkportwifis'          => 'networkports_id',
        'glpi_sockets'                    => 'networkports_id',
    ],

    'glpi_networkportwifis' => [
        'glpi_networkportwifis' => 'networkportwifis_id',
    ],

    'glpi_networks' => [
        'glpi_computers'         => 'networks_id',
        'glpi_networkequipments' => 'networks_id',
        'glpi_printers'          => 'networks_id',
        'glpi_unmanageds'        => 'networks_id',
    ],

    'glpi_notifications' => [
        '_glpi_notifications_notificationtemplates' => 'notifications_id',
        '_glpi_notificationtargets'                 => 'notifications_id',
    ],

    'glpi_notificationtemplates' => [
        '_glpi_notifications_notificationtemplates' => 'notificationtemplates_id',
        '_glpi_notificationtemplatetranslations'    => 'notificationtemplates_id',
        '_glpi_queuednotifications'                 => 'notificationtemplates_id',
    ],

    'glpi_olalevels' => [
        '_glpi_olalevelactions'   => 'olalevels_id',
        '_glpi_olalevelcriterias' => 'olalevels_id',
        '_glpi_olalevels_tickets' => 'olalevels_id',
        'glpi_tickets'            => 'olalevels_id_ttr',
    ],

    'glpi_olas' => [
        'glpi_olalevels' => 'olas_id',
        'glpi_tickets'   => [
            'olas_id_ttr',
            'olas_id_tto',
        ],
    ],

    'glpi_operatingsystemarchitectures' => [
        'glpi_items_operatingsystems' => 'operatingsystemarchitectures_id',
    ],

    'glpi_operatingsystemeditions' => [
        'glpi_items_operatingsystems' => 'operatingsystemeditions_id',
    ],

    'glpi_operatingsystemkernels' => [
        'glpi_operatingsystemkernelversions' => 'operatingsystemkernels_id',
    ],

    'glpi_operatingsystemkernelversions' => [
        'glpi_items_operatingsystems' => 'operatingsystemkernelversions_id',
    ],

    'glpi_operatingsystems' => [
        'glpi_items_operatingsystems' => 'operatingsystems_id',
        'glpi_softwareversions'        => 'operatingsystems_id',
    ],

    'glpi_operatingsystemservicepacks' => [
        'glpi_items_operatingsystems' => 'operatingsystemservicepacks_id',
    ],

    'glpi_operatingsystemversions' => [
        'glpi_items_operatingsystems' => 'operatingsystemversions_id',
    ],

    'glpi_passivedcequipmentmodels' => [
        'glpi_passivedcequipments' => 'passivedcequipmentmodels_id',
    ],

    'glpi_passivedcequipmenttypes' => [
        'glpi_passivedcequipments' => 'passivedcequipmenttypes_id',
    ],

    'glpi_pendingreasons' => [
        '_glpi_pendingreasons_items' => 'pendingreasons_id',
        'glpi_itilreminders' => 'pendingreasons_id',
        'glpi_itilfollowuptemplates' => 'pendingreasons_id',
        'glpi_tasktemplates' => 'pendingreasons_id',
    ],

    'glpi_pdumodels' => [
        'glpi_pdus' => 'pdumodels_id',
    ],

    'glpi_pdus' => [
        '_glpi_pdus_racks' => 'pdus_id',
    ],

    'glpi_pdutypes' => [
        'glpi_pdus' => 'pdutypes_id',
    ],

    'glpi_peripheralmodels' => [
        'glpi_peripherals' => 'peripheralmodels_id',
    ],

    'glpi_peripheraltypes' => [
        'glpi_peripherals' => 'peripheraltypes_id',
    ],

    'glpi_phonemodels' => [
        'glpi_phones' => 'phonemodels_id',
    ],

    'glpi_phonepowersupplies' => [
        'glpi_phones' => 'phonepowersupplies_id',
    ],

    'glpi_phonetypes' => [
        'glpi_phones' => 'phonetypes_id',
    ],

    'glpi_planningeventcategories' => [
        'glpi_planningexternalevents' => 'planningeventcategories_id',
        'glpi_planningexternaleventtemplates' => 'planningeventcategories_id',
    ],

    'glpi_planningexternaleventtemplates' => [
        'glpi_planningexternalevents' => 'planningexternaleventtemplates_id',
    ],

    'glpi_plugs' => [
        '_glpi_items_plugs' => 'plugs_id',
    ],

    'glpi_printermodels' => [
        '_glpi_cartridgeitems_printermodels' => 'printermodels_id',
        'glpi_printers'                      => 'printermodels_id',
    ],

    'glpi_printers' => [
        '_glpi_cartridges'              => 'printers_id',
        '_glpi_printerlogs'             => ['items_id', 'itemtype'],
        '_glpi_printers_cartridgeinfos' => 'printers_id',
    ],

    'glpi_printertypes' => [
        'glpi_printers' => 'printertypes_id',
    ],

    'glpi_problems' => [
        '_glpi_changes_problems'   => 'problems_id',
        '_glpi_groups_problems'    => 'problems_id',
        '_glpi_items_problems'     => 'problems_id',
        '_glpi_itils_projects'     => [['items_id', 'itemtype']],
        '_glpi_itilfollowups'      => [['items_id', 'itemtype']],
        '_glpi_itilsolutions'      => [['items_id', 'itemtype']],
        '_glpi_problemcosts'       => 'problems_id',
        '_glpi_problems_problems'  => [
            'problems_id_1',
            'problems_id_2',
        ],
        '_glpi_problems_suppliers' => 'problems_id',
        '_glpi_problems_tickets'   => 'problems_id',
        '_glpi_problems_users'     => 'problems_id',
        '_glpi_problemtasks'       => 'problems_id',
    ],

    'glpi_problemtemplates' => [
        'glpi_entities'                         => 'problemtemplates_id',
        'glpi_itilcategories'                   => [
            'problemtemplates_id',
        ],
        'glpi_problems'                         => 'problemtemplates_id',
        '_glpi_problemtemplatehiddenfields'     => 'problemtemplates_id',
        '_glpi_problemtemplatemandatoryfields'  => 'problemtemplates_id',
        '_glpi_problemtemplatepredefinedfields' => 'problemtemplates_id',
        '_glpi_problemtemplatereadonlyfields'   => 'problemtemplates_id',
        'glpi_profiles'                         => 'problemtemplates_id',
    ],

    'glpi_profiles' => [
        '_glpi_knowbaseitems_profiles'         => 'profiles_id',
        '_glpi_profilerights'                  => 'profiles_id',
        '_glpi_profiles_reminders'             => 'profiles_id',
        '_glpi_profiles_rssfeeds'              => 'profiles_id',
        '_glpi_profiles_users'                 => 'profiles_id',
        'glpi_users'                           => 'profiles_id',
    ],

    'glpi_projects' => [
        '_glpi_itils_projects'      => 'projects_id',
        '_glpi_items_projects'      => 'projects_id',
        '_glpi_projectcosts'        => 'projects_id',
        'glpi_projects'             => 'projects_id',
        '_glpi_projecttasks'        => 'projects_id',
        'glpi_projecttasktemplates' => 'projects_id',
        '_glpi_projectteams'        => 'projects_id',
    ],

    'glpi_projectstates' => [
        'glpi_projects'             => 'projectstates_id',
        'glpi_projecttasks'         => 'projectstates_id',
        'glpi_projecttasktemplates' => 'projectstates_id',
    ],

    'glpi_projecttasks' => [
        '_glpi_projecttasklinks'     => [
            'projecttasks_id_source',
            'projecttasks_id_target',
        ],
        'glpi_projecttasks'          => 'projecttasks_id',
        '_glpi_projecttasks_tickets' => 'projecttasks_id',
        '_glpi_projecttaskteams'     => 'projecttasks_id',
        'glpi_projecttasktemplates'  => 'projecttasks_id',
    ],

    'glpi_projecttasktemplates' => [
        'glpi_projecttasks' => 'projecttasktemplates_id',
    ],

    'glpi_projecttasktypes' => [
        'glpi_projecttasks'         => 'projecttasktypes_id',
        'glpi_projecttasktemplates' => 'projecttasktypes_id',
    ],

    'glpi_projecttypes' => [
        'glpi_projects' => 'projecttypes_id',
    ],

    'glpi_rackmodels' => [
        'glpi_racks' => 'rackmodels_id',
    ],

    'glpi_racks' => [
        '_glpi_items_racks' => 'racks_id',
        '_glpi_pdus_racks'  => 'racks_id',
    ],

    'glpi_racktypes' => [
        'glpi_racks' => 'racktypes_id',
    ],

    'glpi_reminders' => [
        '_glpi_entities_reminders'   => 'reminders_id',
        '_glpi_groups_reminders'     => 'reminders_id',
        '_glpi_profiles_reminders'   => 'reminders_id',
        '_glpi_remindertranslations' => 'reminders_id',
        '_glpi_reminders_users'      => 'reminders_id',
    ],

    'glpi_requesttypes' => [
        'glpi_itilfollowups'         => 'requesttypes_id',
        'glpi_itilfollowuptemplates' => 'requesttypes_id',
        'glpi_tickets'               => 'requesttypes_id',
        'glpi_users'                 => 'default_requesttypes_id',
    ],

    'glpi_reservationitems' => [
        '_glpi_reservations' => 'reservationitems_id',
    ],

    'glpi_rssfeeds' => [
        '_glpi_entities_rssfeeds' => 'rssfeeds_id',
        '_glpi_groups_rssfeeds'   => 'rssfeeds_id',
        '_glpi_profiles_rssfeeds' => 'rssfeeds_id',
        '_glpi_rssfeeds_users'    => 'rssfeeds_id',
    ],

    'glpi_rules' => [
        'glpi_refusedequipments' => 'rules_id',
        '_glpi_ruleactions'      => 'rules_id',
        '_glpi_rulecriterias'    => 'rules_id',
        'glpi_rulematchedlogs'   => 'rules_id',
    ],

    'glpi_savedsearches' => [
        '_glpi_savedsearches_alerts' => 'savedsearches_id',
        '_glpi_savedsearches_users'  => 'savedsearches_id',
    ],

    'glpi_slalevels' => [
        '_glpi_slalevelactions'   => 'slalevels_id',
        '_glpi_slalevelcriterias' => 'slalevels_id',
        '_glpi_slalevels_tickets' => 'slalevels_id',
        'glpi_tickets'            => 'slalevels_id_ttr',
    ],

    'glpi_slas' => [
        'glpi_slalevels' => 'slas_id',
        'glpi_tickets'   => [
            'slas_id_ttr',
            'slas_id_tto',
        ],
    ],

    'glpi_slms' => [
        '_glpi_olas' => 'slms_id',
        '_glpi_slas' => 'slms_id',
    ],

    'glpi_snmpcredentials' => [
        'glpi_networkequipments' => 'snmpcredentials_id',
        'glpi_printers'          => 'snmpcredentials_id',
        'glpi_unmanageds'        => 'snmpcredentials_id',
    ],

    'glpi_socketmodels' => [
        'glpi_cables' => [
            'socketmodels_id_endpoint_a',
            'socketmodels_id_endpoint_b',
        ],
        'glpi_sockets' => 'socketmodels_id',
    ],

    'glpi_sockets' => [
        'glpi_cables' => [
            'sockets_id_endpoint_a',
            'sockets_id_endpoint_b',
        ],
    ],

    'glpi_softwarecategories' => [
        '_glpi_softwarecategories' => 'softwarecategories_id',
        'glpi_softwares'           => 'softwarecategories_id',
    ],

    'glpi_softwarelicenses' => [
        '_glpi_items_softwarelicenses'     => 'softwarelicenses_id',
        '_glpi_softwarelicenses'           => 'softwarelicenses_id',
        '_glpi_softwarelicenses_users'             => 'softwarelicenses_id',
    ],

    'glpi_softwarelicensetypes' => [
        'glpi_softwarelicenses'      => 'softwarelicensetypes_id',
        '_glpi_softwarelicensetypes' => 'softwarelicensetypes_id',
    ],

    'glpi_softwares' => [
        '_glpi_softwarelicenses' => 'softwares_id',
        'glpi_softwares'         => 'softwares_id',
        '_glpi_softwareversions' => 'softwares_id',
    ],

    'glpi_softwareversions' => [
        '_glpi_items_softwareversions'     => 'softwareversions_id',
        'glpi_softwarelicenses'            => [
            'softwareversions_id_buy',
            'softwareversions_id_use',
        ],
    ],

    'glpi_solutiontemplates' => [
        'glpi_pendingreasons' => 'solutiontemplates_id',
    ],

    'glpi_solutiontypes' => [
        'glpi_itilsolutions'     => 'solutiontypes_id',
        'glpi_solutiontemplates' => 'solutiontypes_id',
    ],

    'glpi_states' => [
        'glpi_appliances'                => 'states_id',
        'glpi_assets_assets'             => 'states_id',
        'glpi_cables'                    => 'states_id',
        'glpi_certificates'              => 'states_id',
        'glpi_clusters'                  => 'states_id',
        'glpi_computers'                 => 'states_id',
        'glpi_contracts'                 => 'states_id',
        'glpi_databaseinstances'         => 'states_id',
        'glpi_enclosures'                => 'states_id',
        'glpi_items_devicebatteries'     => 'states_id',
        'glpi_items_devicecameras'       => 'states_id',
        'glpi_items_devicecases'         => 'states_id',
        'glpi_items_devicecontrols'      => 'states_id',
        'glpi_items_devicedrives'        => 'states_id',
        'glpi_items_devicefirmwares'     => 'states_id',
        'glpi_items_devicegenerics'      => 'states_id',
        'glpi_items_devicegraphiccards'  => 'states_id',
        'glpi_items_deviceharddrives'    => 'states_id',
        'glpi_items_devicememories'      => 'states_id',
        'glpi_items_devicemotherboards'  => 'states_id',
        'glpi_items_devicenetworkcards'  => 'states_id',
        'glpi_items_devicepcis'          => 'states_id',
        'glpi_items_devicepowersupplies' => 'states_id',
        'glpi_items_deviceprocessors'    => 'states_id',
        'glpi_items_devicesensors'       => 'states_id',
        'glpi_items_devicesimcards'      => 'states_id',
        'glpi_items_devicesoundcards'    => 'states_id',
        'glpi_lines'                     => 'states_id',
        'glpi_monitors'                  => 'states_id',
        'glpi_networkequipments'         => 'states_id',
        'glpi_passivedcequipments'       => 'states_id',
        'glpi_pdus'                      => 'states_id',
        'glpi_peripherals'               => 'states_id',
        'glpi_phones'                    => 'states_id',
        'glpi_printers'                  => 'states_id',
        'glpi_racks'                     => 'states_id',
        'glpi_softwarelicenses'          => 'states_id',
        'glpi_softwareversions'          => 'states_id',
        'glpi_states'                    => 'states_id',
        'glpi_unmanageds'                => 'states_id',
    ],

    'glpi_suppliers' => [
        '_glpi_changes_suppliers'   => 'suppliers_id',
        '_glpi_contacts_suppliers'  => 'suppliers_id',
        '_glpi_contracts_suppliers' => 'suppliers_id',
        'glpi_infocoms'             => 'suppliers_id',
        '_glpi_problems_suppliers'  => 'suppliers_id',
        '_glpi_suppliers_tickets'   => 'suppliers_id',
    ],

    'glpi_suppliertypes' => [
        'glpi_suppliers' => 'suppliertypes_id',
    ],

    'glpi_taskcategories' => [
        'glpi_changetasks'    => 'taskcategories_id',
        'glpi_problemtasks'   => 'taskcategories_id',
        'glpi_taskcategories' => 'taskcategories_id',
        'glpi_tasktemplates'  => 'taskcategories_id',
        'glpi_tickettasks'    => 'taskcategories_id',
    ],

    'glpi_tasktemplates' => [
        'glpi_changetasks'  => 'tasktemplates_id',
        'glpi_problemtasks' => 'tasktemplates_id',
        'glpi_tickettasks'  => 'tasktemplates_id',
    ],

    'glpi_ticketrecurrents' => [
        '_glpi_items_ticketrecurrents' => 'ticketrecurrents_id',
    ],

    'glpi_tickets' => [
        '_glpi_changes_tickets'       => 'tickets_id',
        'glpi_documents'              => 'tickets_id',
        '_glpi_groups_tickets'        => 'tickets_id',
        '_glpi_items_tickets'         => 'tickets_id',
        '_glpi_itils_projects'        => [['items_id', 'itemtype']],
        '_glpi_itilfollowups'         => [['items_id', 'itemtype']],
        '_glpi_itils_validationsteps' => [['items_id', 'itemtype']],
        '_glpi_itilsolutions'         => [['items_id', 'itemtype']],
        '_glpi_olalevels_tickets'     => 'tickets_id',
        '_glpi_problems_tickets'      => 'tickets_id',
        '_glpi_projecttasks_tickets'  => 'tickets_id',
        '_glpi_slalevels_tickets'     => 'tickets_id',
        '_glpi_suppliers_tickets'     => 'tickets_id',
        '_glpi_ticketcosts'           => 'tickets_id',
        '_glpi_tickets_contracts'     => 'tickets_id',
        '_glpi_tickets_tickets'       => [
            'tickets_id_1',
            'tickets_id_2',
        ],
        '_glpi_tickets_users'         => 'tickets_id',
        '_glpi_ticketsatisfactions'   => 'tickets_id',
        '_glpi_tickettasks'           => 'tickets_id',
        '_glpi_ticketvalidations'     => 'tickets_id',
    ],

    'glpi_tickettemplates' => [
        'glpi_entities'                        => 'tickettemplates_id',
        'glpi_itilcategories'                  => [
            'tickettemplates_id_incident',
            'tickettemplates_id_demand',
        ],
        'glpi_profiles'                        => 'tickettemplates_id',
        'glpi_tickets'                         => 'tickettemplates_id',
        'glpi_ticketrecurrents'                => 'tickettemplates_id',
        '_glpi_tickettemplatehiddenfields'     => 'tickettemplates_id',
        '_glpi_tickettemplatemandatoryfields'  => 'tickettemplates_id',
        '_glpi_tickettemplatepredefinedfields' => 'tickettemplates_id',
        '_glpi_tickettemplatereadonlyfields'   => 'tickettemplates_id',
    ],

    'glpi_transfers' => [
        'glpi_entities' => 'transfers_id',
    ],

    'glpi_usercategories' => [
        'glpi_users' => 'usercategories_id',
    ],

    'glpi_users' => [
        'glpi_appliances'             => [
            'users_id_tech',
            'users_id',
        ],
        'glpi_assets_assets'            => [
            'users_id_tech',
            'users_id',
        ],
        'glpi_cables'                   => [
            'users_id_tech',
            'users_id',
        ],
        'glpi_cartridgeitems'           => [
            'users_id_tech',
            'users_id',
        ],
        'glpi_certificates'             => [
            'users_id_tech',
            'users_id',
        ],
        'glpi_changes'                  => [
            'users_id_recipient',
            'users_id_lastupdater',
        ],
        '_glpi_changes_users'           => 'users_id',
        'glpi_changetasks'              => [
            'users_id',
            'users_id_editor',
            'users_id_tech',
        ],
        'glpi_changevalidations'        => [
            'users_id',
            'users_id_validate',
        ],
        'glpi_clusters'                 => [
            'users_id_tech',
            'users_id',
        ],
        'glpi_computers'                => [
            'users_id_tech',
            'users_id',
        ],
        'glpi_consumableitems'          => [
            'users_id_tech',
            'users_id',
        ],
        '_glpi_dashboards_dashboards'   => 'users_id',
        'glpi_dashboards_filters'       => 'users_id',
        'glpi_databaseinstances'        => [
            'users_id_tech',
            'users_id',
        ],
        '_glpi_displaypreferences'      => 'users_id',
        'glpi_domains'                  => [
            'users_id_tech',
            'users_id',
        ],
        'glpi_domainrecords'            => [
            'users_id_tech',
            'users_id',
        ],
        'glpi_documents'                => 'users_id',
        'glpi_documents_items'          => 'users_id',
        'glpi_enclosures'               => [
            'users_id_tech',
            'users_id',
        ],
        'glpi_forms_answerssets'        => 'users_id',
        '_glpi_groups_users'            => 'users_id',
        'glpi_items_devicesimcards'     => [
            'users_id_tech',
            'users_id',
        ],
        '_glpi_items_kanbans'           => 'users_id',
        'glpi_itilcategories'           => 'users_id',
        'glpi_itilfollowups'            => [
            'users_id',
            'users_id_editor',
        ],
        'glpi_itilsolutions'            => [
            'users_id_approval',
            'users_id_editor',
            'users_id',
        ],
        'glpi_knowbaseitems'            => 'users_id',
        'glpi_knowbaseitems_comments'   => 'users_id',
        'glpi_knowbaseitems_revisions'  => 'users_id',
        '_glpi_knowbaseitems_users'     => 'users_id',
        'glpi_knowbaseitemtranslations' => 'users_id',
        'glpi_lines'                    => [
            'users_id_tech',
            'users_id',
        ],
        'glpi_monitors'                 => [
            'users_id_tech',
            'users_id',
        ],
        'glpi_networkequipments'        => [
            'users_id_tech',
            'users_id',
        ],
        'glpi_notepads'                 => [
            'users_id',
            'users_id_lastupdater',
        ],
        'glpi_notimportedemails'        => 'users_id',
        '_glpi_objectlocks'             => 'users_id',
        'glpi_passivedcequipments'      => [
            'users_id_tech',
            'users_id',
        ],
        'glpi_pdus'                     => [
            'users_id_tech',
            'users_id',
        ],
        'glpi_peripherals'              => [
            'users_id_tech',
            'users_id',
        ],
        'glpi_phones'                   => [
            'users_id_tech',
            'users_id',
        ],
        'glpi_planningexternalevents'   => 'users_id',
        'glpi_planningrecalls'          => 'users_id',
        'glpi_printers'                 => [
            'users_id_tech',
            'users_id',
        ],
        'glpi_problems'                 => [
            'users_id_recipient',
            'users_id_lastupdater',
        ],
        '_glpi_problems_users'          => 'users_id',
        'glpi_problemtasks'             => [
            'users_id',
            'users_id_editor',
            'users_id_tech',
        ],
        '_glpi_profiles_users'          => 'users_id',
        'glpi_projects'                 => 'users_id',
        'glpi_projecttasks'             => 'users_id',
        'glpi_projecttasktemplates'     => 'users_id',
        'glpi_racks'                    => [
            'users_id_tech',
            'users_id',
        ],
        '_glpi_reminders'               => 'users_id',
        '_glpi_reminders_users'         => 'users_id',
        '_glpi_remindertranslations'    => 'users_id',
        'glpi_reservations'             => 'users_id',
        'glpi_rssfeeds'                 => 'users_id',
        '_glpi_rssfeeds_users'          => 'users_id',
        '_glpi_savedsearches'           => 'users_id',
        '_glpi_savedsearches_users'     => 'users_id',
        'glpi_softwarelicenses'         => [
            'users_id_tech',
            'users_id',
        ],
        'glpi_softwares'                => [
            'users_id_tech',
            'users_id',
        ],
        'glpi_tasktemplates'            => 'users_id_tech',
        'glpi_tickets'                  => [
            'users_id_recipient',
            'users_id_lastupdater',
        ],
        '_glpi_tickets_users'           => 'users_id',
        'glpi_tickettasks'              => [
            'users_id',
            'users_id_editor',
            'users_id_tech',
        ],
        'glpi_ticketvalidations'        => [
            'users_id',
            'users_id_validate',
        ],
        'glpi_unmanageds'               => [
            'users_id_tech',
            'users_id',
        ],
        '_glpi_useremails'              => 'users_id',
        'glpi_users'                    => 'users_id_supervisor',
        '_glpi_validatorsubstitutes'     => [
            'users_id',
            'users_id_substitute',
        ],
        '_glpi_softwarelicenses_users'          => 'users_id',
        '_glpi_contracts_users'         => 'users_id',
    ],

    'glpi_usertitles' => [
        'glpi_contacts' => 'usertitles_id',
        'glpi_users'    => 'usertitles_id',
    ],

    'glpi_validationsteps' => [
        'glpi_itilvalidationtemplates' => 'validationsteps_id',
        'glpi_itils_validationsteps' => 'validationsteps_id',
    ],

    'glpi_virtualmachinestates' => [
        'glpi_itemvirtualmachines' => 'virtualmachinestates_id',
    ],

    'glpi_virtualmachinesystems' => [
        'glpi_itemvirtualmachines' => 'virtualmachinesystems_id',
    ],

    'glpi_virtualmachinetypes' => [
        'glpi_itemvirtualmachines' => 'virtualmachinetypes_id',
    ],

    'glpi_vlans' => [
        '_glpi_ipnetworks_vlans'   => 'vlans_id',
        '_glpi_networkports_vlans' => 'vlans_id',
    ],

    'glpi_wifinetworks' => [
        'glpi_networkportwifis' => 'wifinetworks_id',
    ],
    'glpi_webhooks' => [
        '_glpi_queuedwebhooks' => 'webhooks_id',
    ],

    'glpi_webhookcategories' => [
        'glpi_webhookcategories'    => 'webhookcategories_id',
        'glpi_webhooks'             => 'webhookcategories_id',
    ],

];

$add_mapping_entry = static function (string $source_table, string $target_table_key, string|array $relation_fields) use (&$RELATION) {
    if (!array_key_exists($source_table, $RELATION)) {
        $RELATION[$source_table] = [];
    }
    if (!array_key_exists($target_table_key, $RELATION[$source_table])) {
        $RELATION[$source_table][$target_table_key] = [];
    }
    if (!is_array($RELATION[$source_table][$target_table_key])) {
        $RELATION[$source_table][$target_table_key] = [$RELATION[$source_table][$target_table_key]];
    }

    if (!in_array($relation_fields, $RELATION[$source_table][$target_table_key], true)) {
        $RELATION[$source_table][$target_table_key][] = $relation_fields;
    }
};

// Add polymorphic relations based on configuration.
global $CFG_GLPI;
$specifically_managed_types = [
    Agent::class, // FIXME Agent should be a CommonDBChild with $mustBeAttached=true
    Consumable::class, // Consumables are handled manually to redefine `date_out` to `null`
    DatabaseInstance::class, // FIXME DatabaseInstance should be a CommonDBChild with $mustBeAttached=true
    Item_Cluster::class, // FIXME $mustBeAttached_1 and $mustBeAttached_2 should probably be set to true
    Item_Enclosure::class, // FIXME $mustBeAttached_1 and $mustBeAttached_2 should probably be set to true
    Item_Rack::class, // FIXME $mustBeAttached_1 and $mustBeAttached_2 should probably be set to true
];
$polymorphic_types_mapping = [
    Agent::class                   => $CFG_GLPI['agent_types'],
    Appliance_Item::class          => $CFG_GLPI['appliance_types'],
    Appliance_Item_Relation::class => $CFG_GLPI['appliance_relation_types'],
    Certificate_Item::class        => $CFG_GLPI['certificate_types'],
    Change_Item::class             => $CFG_GLPI['ticket_types'],
    Consumable::class              => $CFG_GLPI['consumables_types'],
    Contract_Item::class           => $CFG_GLPI['contract_types'],
    DatabaseInstance::class        => $CFG_GLPI['databaseinstance_types'],
    Document_Item::class           => Document::getItemtypesThatCanHave(),
    Domain_Item::class             => $CFG_GLPI['domain_types'],
    Infocom::class                 => Infocom::getItemtypesThatCanHave(),
    Item_Cluster::class            => $CFG_GLPI['cluster_types'],
    Item_Disk::class               => $CFG_GLPI['disk_types'],
    Item_Enclosure::class          => $CFG_GLPI['rackable_types'],
    Item_Kanban::class             => $CFG_GLPI['kanban_types'],
    Item_OperatingSystem::class    => $CFG_GLPI['operatingsystem_types'],
    Item_Problem::class            => $CFG_GLPI['ticket_types'],
    Item_Project::class            => $CFG_GLPI['project_asset_types'],
    Item_Rack::class               => $CFG_GLPI['rackable_types'],
    Item_SoftwareLicense::class    => $CFG_GLPI['software_types'],
    Item_SoftwareVersion::class    => $CFG_GLPI['software_types'],
    Item_Ticket::class             => $CFG_GLPI['ticket_types'],
    ItemAntivirus::class           => $CFG_GLPI['itemantivirus_types'],
    ItemVirtualMachine::class      => $CFG_GLPI['itemvirtualmachines_types'],
    KnowbaseItem_Item::class       => $CFG_GLPI['kb_types'],
    NetworkPort::class             => $CFG_GLPI['networkport_types'],
    ReservationItem::class         => $CFG_GLPI['reservation_types'],
    Socket::class                  => $CFG_GLPI['socket_types'],
    Item_Plug::class               => $CFG_GLPI['plug_types'],
];
foreach (Item_Devices::getDeviceTypes() as $itemdevice_itemtype) {
    $source_itemtypes = $itemdevice_itemtype::itemAffinity();
    if (in_array('*', $source_itemtypes)) {
        $source_itemtypes = $CFG_GLPI['itemdevices_types'];
    }
    $polymorphic_types_mapping[$itemdevice_itemtype] = $source_itemtypes;
    $specifically_managed_types[] = $itemdevice_itemtype; // Item_Devices is handled manually to take care of `keep_devices` option
}
$polymorphic_types_mapping[VObject::class] = [];
foreach ($CFG_GLPI['planning_types'] as $planning_itemtype) {
    if (is_a($planning_itemtype, CalDAVCompatibleItemInterface::class, true)) {
        $polymorphic_types_mapping[VObject::class][] = $planning_itemtype;
    }
}

foreach ($polymorphic_types_mapping as $target_itemtype => $source_itemtypes) {
    foreach ($source_itemtypes as $source_itemtype) {
        $target_table_key_prefix = '';
        if (
            in_array($target_itemtype, $specifically_managed_types)
            || (
                is_a($target_itemtype, CommonDBChild::class, true)
                && $target_itemtype::$itemtype === 'itemtype'
                && $target_itemtype::$items_id === 'items_id'
                && $target_itemtype::$mustBeAttached === true
            )
            || (
                is_a($target_itemtype, CommonDBRelation::class, true)
                && (
                    (
                        $target_itemtype::$itemtype_1 === 'itemtype'
                        && $target_itemtype::$items_id_1 === 'items_id'
                        && $target_itemtype::$mustBeAttached_1 === true
                    )
                    || (
                        $target_itemtype::$itemtype_2 === 'itemtype'
                        && $target_itemtype::$items_id_2 === 'items_id'
                        && $target_itemtype::$mustBeAttached_2 === true
                    )
                )
            )
        ) {
            // If item must be attached, target table key has to be prefixed by "_"
            // to be ignored by `CommonDBTM::cleanRelationData()`. Indeed, without usage of this prefix,
            // related item will be preserved with its foreign key defined to 0, making it an unwanted orphaned item.
            $target_table_key_prefix = '_';
        }
        /** @var class-string<CommonDBTM> $target_itemtype */
        $target_table_key = $target_table_key_prefix . $target_itemtype::getTable();
        $source_table     = $source_itemtype::getTable();

        $add_mapping_entry($source_table, $target_table_key, ['items_id', 'itemtype']);
    }
}

// IPAddress specific case
// mainitems_id/mainitemtype are mainly a copy of item related to source NetworkPort
foreach ($CFG_GLPI['networkport_types'] as $source_itemtype) {
    $target_table_key = IPAddress::getTable();
    $source_table     = $source_itemtype::getTable();

    $add_mapping_entry($source_table, $target_table_key, ['mainitems_id', 'mainitemtype']);
}

// Asset_PeripheralAsset specific case
foreach ($CFG_GLPI['directconnect_types'] as $directconnect_itemtype) {
    $target_table_key = Asset_PeripheralAsset::getTable();
    $source_table     = $directconnect_itemtype::getTable();

    $add_mapping_entry($source_table, $target_table_key, ['itemtype_peripheral', 'items_id_peripheral']);
}
foreach (Asset_PeripheralAsset::getPeripheralHostItemtypes() as $peripheralhost_itemtype) {
    $target_table_key = Asset_PeripheralAsset::getTable();
    $source_table     = $peripheralhost_itemtype::getTable();

    $add_mapping_entry($source_table, $target_table_key, ['itemtype_asset', 'items_id_asset']);
}

// Multiple groups assignments
$assignable_itemtypes = $CFG_GLPI['assignable_types'];
foreach ($assignable_itemtypes as $assignable_itemtype) {
    $source_table_key = $assignable_itemtype::getTable();

    $add_mapping_entry($source_table_key, '_glpi_groups_items', ['itemtype', 'items_id']);
}
