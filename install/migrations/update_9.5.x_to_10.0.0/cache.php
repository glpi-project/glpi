<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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
 * @var DB $DB
 * @var Migration $migration
 */

$had_custom_config = false;
if (countElementsInTable('glpi_configs', ['name' => 'cache_db', 'context' => 'core'])) {
   $DB->delete('glpi_configs', ['name' => 'cache_db', 'context' => 'core']);
   $had_custom_config = true;
}
if (countElementsInTable('glpi_configs', ['name' => 'cache_trans', 'context' => 'core'])) {
   $DB->delete('glpi_configs', ['name' => 'cache_trans', 'context' => 'core']);
   $had_custom_config = true;
}

$migration->displayWarning(
   'GLPI cache has been changed and will not use anymore APCu or Wincache extensions. '
   . ($had_custom_config ? 'Existing cache configuration will not be reused. ' : '')
   . 'Use "php bin/console cache:configure" command to configure cache system.'
);
