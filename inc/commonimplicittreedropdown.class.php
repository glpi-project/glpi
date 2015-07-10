<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.
 
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
*/

/** @file
* @brief 
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Class CommonImplicitTreeDropdown : Manage implicit tree, ie., trees that cannot be manage by
/// the user. For instance, Network hierarchy only depends on network addresses and netmasks.
/// @since 0.84
class CommonImplicitTreeDropdown extends CommonTreeDropdown {

   var $can_be_translated = true;

   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'CommonTreeDropdown'.MassiveAction::CLASS_ACTION_SEPARATOR.'move_under';
      return $forbidden;
   }


   /**
    * Method that must be overloaded. This method provides the ancestor of the current item
    * according to $this->input
    *
    * @return the id of the current object ancestor
   **/
   function getNewAncestor() {
      return 0; // By default, we rattach to the root element
   }


   /**
    * Method that must be overloaded. This method provides the list of all potential sons of the
    * current item according to $this->fields.
    *
    * @return array of IDs of the potential sons
   **/
   function getPotentialSons() {
      return array(); // By default, we don't have any son
   }


   /**
    * Used to set the ForeignKeyField
    *
    * @param $input datas used to add the item
    *
    * @return the modified $input array
   **/
   function prepareInputForAdd($input) {

      $input[$this->getForeignKeyField()] = $this->getNewAncestor();
      // We call the parent to manage tree
      return parent::prepareInputForAdd($input);
   }


   /**
    * Used to update the ForeignKeyField
    *
    * @param $input datas used to add the item
    *
    * @return the modified $input array
   **/
   function prepareInputForUpdate($input) {

      $input[$this->getForeignKeyField()] = $this->getNewAncestor();
      // We call the parent to manage tree
      return parent::prepareInputForUpdate($input);
   }


   /**
    * Used to update tree by redefining other items ForeignKeyField
    *
    * @return nothing
   **/
   function post_addItem() {

      $this->alterElementInsideTree("add");
      parent::post_addItem();
   }


   /**
    * Used to update tree by redefining other items ForeignKeyField
    *
    * @param $history   (default 1)
    *
    * @return nothing
   **/
   function post_updateItem($history=1) {

      $this->alterElementInsideTree("update");
      parent::post_updateItem($history);
   }


   /**
    * Used to update tree by redefining other items ForeignKeyField
    *
    * @return nothing
   **/
   function pre_deleteItem() {

      $this->alterElementInsideTree("delete");
      return parent::pre_deleteItem();
   }


   /**
    * The haveChildren=false must be define to be sure that CommonDropdown allows the deletion of a
    * node of the tree
   **/
   function haveChildren() {
      return false;
   }


   // Key function to manage the children of the node
   private function alterElementInsideTree($step) {
      global $DB;

      switch ($step) {
         case 'add' :
            $newParent     = $this->input[$this->getForeignKeyField()];
            $potentialSons = $this->getPotentialSons();
            break;

         case 'update' :
            $oldParent     = $this->fields[$this->getForeignKeyField()];
            $newParent     = $this->input[$this->getForeignKeyField()];
            $potentialSons = $this->getPotentialSons();
            break;

         case 'delete' :
            $oldParent     = $this->fields[$this->getForeignKeyField()];
            $potentialSons = array(); // Because there is no future sons !
            break;
      }

      /** Here :
       * $oldParent contains the old parent, to check its sons to attach them to it
       * $newParent contains the new parent, to check its sons to potentially attach them to this
       *            item.
       * $potentialSons list ALL potential childrens (sons as well as grandsons). That is use to
       *                update them. (See getPotentialSons())
      **/

      if ($step != "add") { // Because there is no old sons of new node
         // First, get all my current direct sons (old ones) that are not new potential sons
         $query = "SELECT `id`
                   FROM `".$this->getTable()."`
                   WHERE `".$this->getForeignKeyField()."` = '".$this->getID()."'
                         AND `id` NOT IN ('".implode("', '", $potentialSons)."')";
         $oldSons = array();
         foreach ($DB->request($query) as $oldSon) {
            $oldSons[] = $oldSon["id"];
         }
         if (count($oldSons) > 0) { // Then make them pointing to old parent
            $query = "UPDATE `".$this->getTable()."`
                      SET `".$this->getForeignKeyField()."` = '$oldParent'
                      WHERE `id` IN ('".implode("', '",$oldSons)."')";
            $DB->query($query);
            // Then, regenerate the old sons to reflect there new ancestors
            $this->regenerateTreeUnderID($oldParent, true, true);
            $this->recursiveCleanSonsAboveID($oldParent);
         }
      }

      if ($step != "delete") { // Because ther is no new sons for deleted nodes
         // And, get all direct sons of my new Father that must be attached to me (ie : that are
         // potential sons
         $query = "SELECT `id`
                   FROM `".$this->getTable()."`
                   WHERE `".$this->getForeignKeyField()."` = '$newParent'
                         AND `id` IN ('".implode("', '", $potentialSons)."')";
         $newSons = array();
         foreach ($DB->request($query) as $newSon) {
            $newSons[] = $newSon["id"];
         }
         if (count($newSons) > 0) { // Then make them pointing to me
            $query = "UPDATE `".$this->getTable()."`
                      SET `".$this->getForeignKeyField()."` = '".$this->getID()."'
                      WHERE `id` IN ('".implode("', '",$newSons)."')";
            $DB->query($query);
            // Then, regenerate the new sons to reflect there new ancestors
            $this->regenerateTreeUnderID($this->getID(), true, true);
            $this->recursiveCleanSonsAboveID($this->getID());
         }
      }
   }
}
?>
