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
   die("Sorry. You can't access this file directly");
}

/**
 * ITILEventCategory class.
 * A category for an ITILEvent
 * @since 10.0.0
**/
class ITILEventCategory extends CommonTreeDropdown
{

   // From CommonDBTM
   public $dohistory          = true;
   public $can_be_translated  = true;

   static $rightname          = 'event';

   static function getTypeName($nb = 0)
   {
      return _n('Event category', 'Event categories', $nb);
   }

   function cleanDBonPurge()
   {
      Rule::cleanForItemCriteria($this);
   }

   static public function getCategoryName($category_id, $full = true) {
      global $DB;

      $iterator = $DB->request([
         'SELECT' => [$full ? 'completename' : 'name'],
         'FROM' => self::getTable(),
         'WHERE' => [
            'id' => $category_id
         ]
      ]);
      if ($iterator->count()) {
         return $iterator->next()[$full ? 'completename' : 'name'];
      }
      return '';
   }
}