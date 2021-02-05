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

namespace Glpi\Application\View\Extension;

use CommonDBTM;
use Twig\Extension\AbstractExtension;
use Twig\Extension\ExtensionInterface;
use Twig\TwigFunction;

/**
 * @since x.x.x
 */
class ModelExtension extends AbstractExtension implements ExtensionInterface {

   public function getFunctions() {
      return [
         new TwigFunction('getmodelPicture', [$this, 'getmodelPicture']),
      ];
   }

   public function getmodelPicture(CommonDBTM $item, string $picture_field = "picture_front"): string {
      if (!$item->isField($picture_field)) {
         return "";
      }


      $itemtype  = $item->getType();
      $modeltype = $itemtype."Model";
      if (class_exists($modeltype)) {
         $model = new $modeltype;
         $fk = getForeignKeyFieldForItemType($modeltype);
         if ($model->getFromDB(($item->fields[$fk]) ?? 0)) {
            return $model->fields[$picture_field];
         }
      }

      return "";
   }
}
