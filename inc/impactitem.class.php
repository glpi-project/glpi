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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 9.5.0
 */
class ImpactItem extends CommonDBTM {

   /**
    * Find ImpactItem for a given CommonDBTM item
    *
    * @param CommonDBTM $item                The given item
    * @param bool       $create_if_missing   Should we create a new ImpactItem
    *                                        if none found ?
    * @return ImpactItem|bool ImpactItem object or false if not found and
    *                         creation is disabled
    */
   public static function findForItem(
      CommonDBTM $item,
      bool $create_if_missing = true
   ) {
      global $DB;

      $it = $DB->request([
         'SELECT' => [
            'glpi_impactitems.id',
         ],
         'FROM' => self::getTable(),
         'WHERE'  => [
            'glpi_impactitems.itemtype' => get_class($item),
            'glpi_impactitems.items_id' => $item->fields['id'],
         ]
      ]);

      $res = $it->next();
      $impact_item = new self();

      if ($res) {
         $id = $res['id'];
      } else if (!$res && $create_if_missing) {
         $id = $impact_item->add([
            'itemtype' => get_class($item),
            'items_id' => $item->fields['id']
         ]);
      } else {
         return false;
      }

      $impact_item->getFromDB($id);
      return $impact_item;
   }

   public function prepareInputForUpdate($input) {
      $max_depth = $input['max_depth'] ?? 0;

      if (intval($max_depth) <= 0) {
         // If value is not valid, reset to default
         $input['max_depth'] = Impact::DEFAULT_DEPTH;
      } else if ($max_depth >= Impact::MAX_DEPTH && $max_depth != Impact::NO_DEPTH_LIMIT) {
         // Set to no limit if greater than max
         $input['max_depth'] = Impact::NO_DEPTH_LIMIT;
      }

      return $input;
   }
}
