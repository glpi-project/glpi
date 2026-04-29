<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

/**
 * @var Migration $migration
 */

$tables = [
    'glpi_autoupdatesystems', 'glpi_savedsearches', 'glpi_changevalidations', 'glpi_dashboards_dashboards',
    'glpi_dashboards_filters', 'glpi_dashboards_items', 'glpi_devicecasemodels', 'glpi_devicecontrolmodels',
    'glpi_devicedrivemodels', 'glpi_devicegenericmodels', 'glpi_devicegenerictypes', 'glpi_devicegraphiccardmodels',
    'glpi_deviceharddrivemodels', 'glpi_deviceharddrivetypes', 'glpi_devicecameramodels', 'glpi_devicememorymodels',
    'glpi_devicemotherboardmodels', 'glpi_devicenetworkcardmodels', 'glpi_devicepcimodels', 'glpi_devicepowersupplymodels',
    'glpi_deviceprocessormodels', 'glpi_devicesensormodels', 'glpi_devicesensortypes', 'glpi_devicesoundcardmodels',
    'glpi_networkaliases', 'glpi_networkinterfaces', 'glpi_reservationitems', 'glpi_reservations',
    'glpi_softwarecategories', 'glpi_ticketrecurrents', 'glpi_recurrentchanges', 'glpi_tickettemplates',
    'glpi_changetemplates', 'glpi_problemtemplates', 'glpi_devicebatterymodels', 'glpi_devicefirmwaremodels',
    'glpi_domaintypes', 'glpi_domainrelations', 'glpi_domainrecordtypes', 'glpi_appliancetypes', 'glpi_applianceenvironments',
    'glpi_agenttypes', 'glpi_pendingreasons', 'glpi_pendingreasons_items', 'glpi_snmpcredentials', 'glpi_forms_categories',
    'glpi_oauthclients', 'glpi_defaultfilters',
];

foreach ($tables as $table) {
    $migration->addField($table, 'date_creation', 'datetime');
    $migration->addKey($table, 'date_creation');
    $migration->addField($table, 'date_mod', 'datetime');
    $migration->addKey($table, 'date_mod');
}
