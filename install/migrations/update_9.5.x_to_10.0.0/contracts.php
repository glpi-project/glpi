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
 * @var Migration $migration
 */

$migration->changeField(Contract::getTable(), 'use_monday', 'use_sunday', 'bool');
$migration->dropKey(Contract::getTable(), 'use_monday');
$migration->changeField(Contract::getTable(), 'monday_begin_hour', 'sunday_begin_hour', 'time', [
   'value'  => '00:00:00'
]);
$migration->changeField(Contract::getTable(), 'monday_end_hour', 'sunday_end_hour', 'time', [
   'value'  => '00:00:00'
]);
$migration->migrationOneTable(Contract::getTable());
$migration->addKey(Contract::getTable(), 'use_sunday');
