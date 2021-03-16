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

namespace Glpi\Features;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use CommonDBConnexity;
use CommonDBTM;
use Session;
use Toolbox;

/**
 * Clonable objects
 **/
trait Clonable {

   /**
    * Get relations class to clone along with current element.
    *
    * @return CommonDBTM::class[]
    */
   abstract public function getCloneRelations() :array;

   /**
    * Clean input used to clone.
    *
    * @param array $input
    *
    * @return array
    *
    * @since x.x.x
    */
   private function cleanCloneInput(array $input): array {
      $properties_to_clean = [
         'id',
         'date_mod',
         'date_creation',
         'template_name',
         'is_template'
      ];
      foreach ($properties_to_clean as $property) {
         if (array_key_exists($property, $input)) {
            unset($input[$property]);
         }
      }

      return $input;
   }

   /**
    * Clone the item's relations.
    *
    * @param CommonDBTM $source
    * @param bool       $history
    *
    * @return void
    *
    * @since x.x.x
    */
   private function cloneRelations(CommonDBTM $source, bool $history): void {
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

         // Force entity / recursivity based on cloned parent, with fallback on session values
         $override_input['entities_id'] = $this->isEntityAssign() ? $this->getEntityID() : Session::getActiveEntity();
         $override_input['is_recursive'] = $this->maybeRecursive() ? $this->isRecursive() : Session::getIsActiveEntityRecursive();

         $relation_items = $classname::getItemsAssociatedTo($this->getType(), $source->getID());
         foreach ($relation_items as $relation_item) {
            $relation_item->clone($override_input, $history);
         }
      }
   }

   /**
    * Prepare input datas for cloning the item.
    * This empty method is meant to be redefined in objects that need a specific prepareInputForClone logic.
    *
    * @since x.x.x
    *
    * @param array $input datas used to add the item
    *
    * @return array the modified $input array
    */
   public function prepareInputForClone($input) {
      return $input;
   }

   /**
    * Clones the current item
    *
    * @since x.x.x
    *
    * @param array $override_input custom input to override
    * @param boolean $history do history log ?
    *
    * @return integer The new ID of the clone (or false if fail)
    */
   public function clone(array $override_input = [], bool $history = true) {
      global $DB;

      if ($DB->isSlave()) {
         return false;
      }
      $new_item = new static();
      $input = Toolbox::addslashes_deep($this->fields);
      foreach ($override_input as $key => $value) {
         $input[$key] = $value;
      }
      $input = $new_item->cleanCloneInput($input);
      $input = $new_item->prepareInputForClone($input);

      $input['clone'] = true;
      $newID = $new_item->add($input, [], $history);

      if ($newID !== false) {
         $new_item->cloneRelations($this, $history);
         $new_item->post_clone($this, $history);
      }

      return $newID;
   }

   /**
    * Post clone logic.
    * This empty method is meant to be redefined in objects that need a specific post_clone logic.
    *
    * @param $source
    * @param $history
    */
   public function post_clone($source, $history) {
   }
}
