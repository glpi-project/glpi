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
   die("Sorry. You can't access directly to this file");
}

/**
 * Itil_ScheduledDowntime Class
 *
 * Relation between ITILEvents and ITILObjects
 * @since 10.0.0
**/
class Itil_ScheduledDowntime extends CommonDBRelation
{
   
   // From CommonDBRelation
   static public $itemtype_1          = 'ScheduledDowntime';
   static public $items_id_1          = 'scheduleddowntimes_id';

   static public $itemtype_2          = 'itemtype';
   static public $items_id_2          = 'items_id';
   static public $checkItem_2_Rights  = self::HAVE_VIEW_RIGHT_ON_ITEM;


   function getForbiddenStandardMassiveAction()
   {
      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }

   function canCreateItem()
   {
      $downtime = new ScheduledDowntime();

      if ($downtime->canUpdateItem()) {
         return true;
      }

      return parent::canCreateItem();
   }

   function post_addItem()
   {
      $downtime = new ScheduledDowntime();
      $input  = [
         'id'              => $this->fields[self::$items_id_1],
         'date_mod'        => $_SESSION["glpi_currenttime"],
      ];

      $downtime->update($input);
      parent::post_addItem();
   }

   function post_purgeItem()
   {

      $downtime = new ScheduledDowntime();
      $input  = [
         'id'              => $this->fields[self::$items_id_1],
         'date_mod'        => $_SESSION["glpi_currenttime"],
      ];

      $downtime->update($input);

      parent::post_purgeItem();
   }

   function prepareInputForAdd($input)
   {

      // Avoid duplicate entry
      if (countElementsInTable($this->getTable(), [self::$items_id_1 => $input[self::$items_id_1],
                                                   self::$itemtype_2 => $input[self::$itemtype_2],
                                                   self::$items_id_2 => $input[self::$items_id_2]]) > 0) {
         return false;
      }

      return parent::prepareInputForAdd($input);
   }

   /**
    * Display events for an item
    *
    * @param $item            CommonDBTM object for which the event tab need to be displayed
    * @param $withtemplate    withtemplate param (default 0)
   **/
   static function showForItil(CommonDBTM $item, $withtemplate = 0)
   {
      ScheduledDowntime::showListForItil(false, $item);
   }
}