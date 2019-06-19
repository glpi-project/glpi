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
 * Itil_SIEMEvent Class
 *
 * Relation between SIEMEvents and ITILObjects
 * @since 10.0.0
**/
class Itil_SIEMEvent extends CommonDBRelation
{
   
   // From CommonDBRelation
   static public $itemtype_1          = 'SIEMEvent';
   static public $items_id_1          = 'siemevents_id';

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
      $event = new SIEMEvent();

      if ($event->canUpdateItem()) {
         return true;
      }

      return parent::canCreateItem();
   }

   function prepareInputForAdd($input)
   {

      // Avoid duplicate entry
      if (countElementsInTable($this->getTable(), ['siemevents_id' => $input['siemevents_id'],
                                                   'itemtype'   => $input['itemtype'],
                                                   'items_id'   => $input['items_id']]) > 0) {
         return false;
      }

      return parent::prepareInputForAdd($input);
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
   {

      if (!$withtemplate) {
         $nb = 0;
         switch ($item->getType()) {
            case Change::class :
            case Problem::class :
            case Ticket::class :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable(
                     self::getTable(),
                     [
                        'itemtype' => $item->getType(),
                        'items_id' => $item->getID(),
                     ]
                  );
               }
               return self::createTabEntry(SIEMEvent::getTypeName(Session::getPluralNumber()), $nb);
               break;

            case 'SIEMEvent' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable(self::getTable(), ['siemevents_id' => $item->getID()]);
               }
               return self::createTabEntry(_n('Itil item', 'Itil items', Session::getPluralNumber()), $nb);
         }
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
   {

      switch ($item->getType()) {
         case 'SIEMEvent' :
            self::showForSIEMEvent($item);
            break;
         default:
            self::showForItil($item);
            break;
      }
      return true;
   }

   /**
    * Display events for an item
    *
    * @param $item            CommonDBTM object for which the event tab need to be displayed
    * @param $withtemplate    withtemplate param (default 0)
   **/
   static function showForItil(CommonDBTM $item, $withtemplate = 0)
   {
      SIEMEvent::showListForItil(false, $item);
   }
}