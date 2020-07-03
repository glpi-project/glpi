<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
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

namespace Glpi\Features;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use CommonDBConnexity;
use Toolbox;

/**
 * Clonable objects
 **/
trait Clonable {
   /**
    * Get relations class to clone along with current eleemnt
    *
    * @return CommonDBTM::class[]
    */
   abstract public function getCloneRelations() :array;

   public function post_clone($source, $history) {
      parent::post_clone($source, $history);

      $clone_relations = $this->getCloneRelations();
      foreach ($clone_relations as $classname) {
         if (!is_a($classname, CommonDBConnexity::class, true)) {
            Toolbox::logWarning(
               sprintf(
                  'Unable to clone elements of class %s as it does not extends "CommonDBConnexity"',
                  $classname
               )
            );
            continue;
         }

         $override_input[$classname::getItemField($this->getType())] = $this->getID();
         $relation_items = $classname::getItemsAssociatedTo($this->getType(), $source->getID());
         foreach ($relation_items as $relation_item) {
            $relation_item->clone($override_input, $history);
         }
      }
   }
}
