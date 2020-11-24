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

global $DB;

$dc_models = [ComputerModel::class, EnclosureModel::class, MonitorModel::class, NetworkEquipmentModel::class,
   PassiveDCEquipmentModel::class, PDUModel::class, PeripheralModel::class];

// Itemtypes with a 'front_picture' and 'rear picture' field
$front_rear_picture_itemtypes = [PhoneModel::class, PrinterModel::class];
// Itemtypes with a 'pictures' field that can contain one or more pictures
$misc_pictures_itemtypes = array_merge([PhoneModel::class, PrinterModel::class, Software::class, CartridgeItem::class, ConsumableItem::class,
   RackModel::class, SoftwareLicense::class, Datacenter::class, Contact::class, Supplier::class], $dc_models);

/** @var CommonDBTM $itemtype */
foreach ($front_rear_picture_itemtypes as $itemtype) {
   $table = $itemtype::getTable();
   if (!$DB->fieldExists($table, 'picture_front')) {
      $migration->addField($itemtype::getTable(), 'picture_front', 'text');
   }
   if (!$DB->fieldExists($table, 'picture_rear')) {
      $migration->addField($itemtype::getTable(), 'picture_rear', 'text');
   }
}

/** @var CommonDBTM $itemtype */
foreach ($misc_pictures_itemtypes as $itemtype) {
   $table = $itemtype::getTable();
   if (!$DB->fieldExists($table, 'pictures')) {
      $after = ($DB->fieldExists($table, 'picture_rear')) ? 'picture_rear' : '';
      $migration->addField($table, 'pictures', 'text', [
         'after'  => $after
      ]);
   }
}
