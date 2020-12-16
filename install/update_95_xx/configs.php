<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
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
$migration->addField('glpi_users', 'page_layout', 'char(20) COLLATE utf8_unicode_ci DEFAULT NULL', ['after' => 'palette']);

$migration->displayMessage('Add dark mode configuration / user preference');
Config::setConfigurationValues('core', ['dark_mode' => 0]);
$migration->addField('glpi_users', 'dark_mode', 'tinyint DEFAULT NULL', ['after' => 'page_layout']);

$migration->displayMessage('Add global menu folding config / user preference');
Config::setConfigurationValues('core', ['fold_menu' => 0]);
$migration->addField('glpi_users', 'fold_menu', 'tinyint DEFAULT NULL', ['after' => 'dark_mode']);

$migration->displayMessage('Add saved searches pin config / user preference');
Config::setConfigurationValues('core', ['savedsearches_pinned' => 0]);
$migration->addField('glpi_users', 'savedsearches_pinned', 'tinyint DEFAULT NULL', ['after' => 'fold_menu']);
