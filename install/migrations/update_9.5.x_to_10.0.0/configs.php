<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

/**
 * @var Migration $migration
 */

$migration->displayMessage('Add new configurations / user preferences');
$migration->addConfig([
    'default_central_tab'   => 0,
    'page_layout'           => 'vertical',
    'fold_menu'             => 0,
    'fold_search'           => 0,
    'savedsearches_pinned'  => 0,
    'richtext_layout'       => 'inline',
    'user_restored_ldap'    => 0,
    'timeline_order'        => 'natural',
    'itil_layout'           => 0,
    'from_email'            => '',
    'from_email_name'       => '',
    'noreply_email'         => '',
    'noreply_email_name'    => '',
    'replyto_email'         => '',
    'replyto_email_name'    => '',
    'support_legacy_data'   => 1, // GLPI instances updated from GLPI < 10.0 should support legacy data
]);
$migration->addField("glpi_users", "default_central_tab", "tinyint DEFAULT 0");
$migration->addField('glpi_users', 'page_layout', 'char(20) DEFAULT NULL', ['after' => 'palette']);
$migration->addField('glpi_users', 'fold_menu', 'tinyint DEFAULT NULL', ['after' => 'page_layout']);
$migration->addField('glpi_users', 'fold_search', 'tinyint DEFAULT NULL', ['after' => 'fold_menu']);
$migration->addField('glpi_users', 'savedsearches_pinned', 'text', ['after' => 'fold_search', 'nodefault' => true]);
$migration->addField('glpi_users', 'richtext_layout', 'char(20) DEFAULT NULL', ['after' => 'savedsearches_pinned']);
$migration->addField("glpi_users", "timeline_order", "char(20) DEFAULT NULL", ['after' => 'savedsearches_pinned']);
$migration->addField('glpi_users', 'itil_layout', 'text', ['after' => 'timeline_order']);

$migration->displayMessage('Drop old configurations / user preferences');
$migration->dropField('glpi_users', 'layout');
Config::deleteConfigurationValues('core', ['layout']);
Config::deleteConfigurationValues('core', ['use_ajax_autocompletion']);
Config::deleteConfigurationValues('core', ['transfers_id_auto']);
