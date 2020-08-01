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
use Toolbox;

/**
 * Clonable objects
 **/
trait Clonable {

   /**
    * Get relations class to clone along with current element
    *
    * @return CommonDBTM::class[]
    */
   abstract public function getCloneRelations() :array;

   /**
    * Clone the item's relations
    *
    * @param $source
    * @param $history
    * @since x.x.x
    */
   public function cloneRelations($source, $history) {
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

   /**
    * Prepare input datas for cloning the item
    *
    * @since x.x.x
    *
    * @param array $input datas used to add the item
    *
    * @return array the modified $input array
    **/
   function prepareInputForClone($input) {
      unset($input['id']);
      unset($input['date_mod']);
      unset($input['date_creation']);
      return $input;
   }

   /**
    * Clones the current item
    *
    * @since x.x.x
    *
    * @param array $override_input custom input to override
    * @param boolean $history do history log ? (true by default)
    *
    * @return integer The new ID of the clone (or false if fail)
    */
   public function clone(array $override_input = [], bool $history = true) {
      global $DB, $CFG_GLPI;

      if ($DB->isSlave()) {
         return false;
      }
      $new_item = new static();
      $input = Toolbox::addslashes_deep($this->fields);
      foreach ($override_input as $key => $value) {
         $input[$key] = $value;
      }
      $input = $new_item->prepareInputForClone($input);
      if (isset($input['id'])) {
         $input['_oldID'] =  $input['id'];
         unset($input['id']);
      }
      unset($input['date_creation']);
      unset($input['date_mod']);

      if (isset($input['template_name'])) {
         unset($input['template_name']);
      }
      if (isset($input['is_template'])) {
         unset($input['is_template']);
      }

      $input['clone'] = true;
      $newID = $new_item->add($input, [], $history);
      // If the item needs post clone (recursive cloning for example)
      $new_item->post_clone($this, $history);
      return $newID;
   }

   /**
    * @param $source
    * @param $history
    */
   public function post_clone($source, $history) {
      // For 9.5.x BC
      parent::post_clone($source, $history);
      $this->cloneRelations($source, $history);
   }
}
