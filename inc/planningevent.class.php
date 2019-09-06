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

trait PlanningEvent {

   /**
    * Display a Planning Item
    *
    * @param array $val the item to display
    *
    * @return string
   **/
   public function getAlreadyPlannedInformation(array $val) {
      $itemtype = $this->getType();
      if ($item = getItemForItemtype($itemtype)) {
         $objectitemtype = (method_exists($item, 'getItilObjectItemType') ? $item->getItilObjectItemType() : $itemtype);

         //TRANS: %1$s is a type, %2$$ is a date, %3$s is a date
         $out  = sprintf(__('%1$s: from %2$s to %3$s:'), $item->getTypeName(1),
                         Html::convDateTime($val["begin"]), Html::convDateTime($val["end"]));
         $out .= "<br/><a href='".$objectitemtype::getFormURLWithID($val[getForeignKeyFieldForItemType($objectitemtype)]);
         if ($item instanceof CommonITILTask) {
            $out .= "&amp;forcetab=".$itemtype."$1";
         }
         $out .= "'>";
         $out .= Html::resume_text($val["name"], 80).'</a>';

         return $out;
      }
   }

}
