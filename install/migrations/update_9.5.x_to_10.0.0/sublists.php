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
 * @var array $ADDTOSUBDISPLAYPREF
 */


/** Add main column on displaypreferences */
if ($migration->addField(
      'glpi_displaypreferences',
      'is_main',
      'bool',
      ['value' => 1]
   )) {
   $migration->addKey('glpi_displaypreferences', 'is_main');
   $migration->dropKey('glpi_displaypreferences', 'unicity');
   $migration->migrationOneTable('glpi_displaypreferences');
   $migration->addKey(
      'glpi_displaypreferences',
      ['users_id', 'itemtype', 'num', 'is_main'],
      'unicity',
      'UNIQUE'
   );
}
/** /Add main column on displaypreferences */

/** add display preferences for sub items */
$ADDTOSUBDISPLAYPREF['Contract'] = [3, 4, 29, 5];
$ADDTOSUBDISPLAYPREF['Item_Disk'] = [2, 3, 4, 5, 6, 7, 8];
$ADDTOSUBDISPLAYPREF['Certificate'] = [7, 4, 8, 121, 10, 31];
$ADDTOSUBDISPLAYPREF['Notepad'] = [200, 201, 202, 203, 204];
$ADDTOSUBDISPLAYPREF['SoftwareVersion'] = [3, 31, 2, 122, 123, 124];
$ADDTOSUBDISPLAYPREF['ComputerVirtualMachine'] = [1, 6, 7, 5, 2, 3, 4, 8];
$ADDTOSUBDISPLAYPREF['NetworkPort'] = [3, 30, 31, 32, 33, 34, 35, 36, 38, 39, 40];
$ADDTOSUBDISPLAYPREF['Item_RemoteManagement'] = [1, 2, 997];
$ADDTOSUBDISPLAYPREF['Document'] = [2, 80, 73, 4, 7, 5, 6, 121];
$ADDTOSUBDISPLAYPREF['Certificate'] = [1, 7, 18, 8, 121, 10, 31];
/** /add display preferences for sub items */
