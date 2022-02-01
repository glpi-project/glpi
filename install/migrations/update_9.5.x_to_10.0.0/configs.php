<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
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

/**
 * @var Migration $migration
 */

$migration->displayMessage('Add page layout configuration / user preference');
Config::setConfigurationValues('core', ['page_layout' => 'vertical']);
$migration->addField('glpi_users', 'page_layout', 'char(20) DEFAULT NULL', ['after' => 'palette']);

$migration->displayMessage('Add global menu folding config / user preference');
Config::setConfigurationValues('core', ['fold_menu' => 0]);
$migration->addField('glpi_users', 'fold_menu', 'tinyint DEFAULT NULL', ['after' => 'page_layout']);

$migration->displayMessage('Add global menu folding config / user preference');
Config::setConfigurationValues('core', ['fold_search' => 0]);
$migration->addField('glpi_users', 'fold_search', 'tinyint DEFAULT NULL', ['after' => 'fold_menu']);

$migration->displayMessage('Add saved searches pin config / user preference');
Config::setConfigurationValues('core', ['savedsearches_pinned' => 0]);
$migration->addField('glpi_users', 'savedsearches_pinned', 'text', ['after' => 'fold_search', 'nodefault' => true]);

$migration->displayMessage('Add tinymce toolbar configuration / user preference');
Config::setConfigurationValues('core', ['richtext_layout' => 'inline']);
$migration->addField('glpi_users', 'richtext_layout', 'char(20) DEFAULT NULL', ['after' => 'savedsearches_pinned']);

$migration->displayMessage('Drop content layout configuration / user preference');
$migration->dropField('glpi_users', 'layout');
Config::deleteConfigurationValues('core', ['layout']);

$migration->displayMessage('Drop autocompletion configuration');
Config::deleteConfigurationValues('core', ['use_ajax_autocompletion']);

$migration->displayMessage('Add LDAP restore action preference');
Config::setConfigurationValues('core', ['user_restored_ldap' => 0]);

Config::setConfigurationValues('core', ['timeline_order' => 'natural']);
$migration->addField("glpi_users", "timeline_order", "char(20) DEFAULT NULL", ['after' => 'savedsearches_pinned']);

Config::setConfigurationValues('core', ['itil_layout' => 0]);
$migration->addField('glpi_users', 'itil_layout', 'text', ['after' => 'timeline_order']);

$migration->displayMessage('Drop obsolete automatic transfer configuration');
Config::deleteConfigurationValues('core', ['transfers_id_auto']);

$migration->addConfig([
    'from_email'         => '',
    'from_email_name'    => '',
    'noreply_email'      => '',
    'noreply_email_name' => '',
    'replyto_email'      => '',
    'replyto_email_name' => '',
]);
