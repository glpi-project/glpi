<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

/// Common DataBase Relation Table Manager Class
abstract class CommonDBChild extends CommonDBTM {

   // Mapping between DB fields
   public $itemtype; // Class name or field name (start with itemtype) for link to Parent
   public $items_id; // Field name

   // Make an history of the changes -
   // if true, will write a event in the history of parent for add/delete
   public $dohistory = false;


   /**
   * Get the ID of entity assigned to the object
   *
   * @return ID of the entity
   **/
   function getEntityID () {
      // Case of Duplicate Entity info to child
      if (parent::isEntityAssign()) {
         return parent::getEntityID();
      }

      if (preg_match('/^itemtype/', $this->itemtype)) {
         $type = $this->fields[$this->itemtype];
      } else {
         $type = $this->itemtype;
      }
      if (class_exists($type)) {
         $item = new $type();
         if ($item->getFromDB($this->fields[$this->items_id]) && $item->isEntityAssign()) {
            return $item->getEntityID();
         }
      }
      return -1;
   }

   function isEntityAssign() {
      // Case of Duplicate Entity info to child
      if (parent::isEntityAssign()) {
         return true;
      }

      if (preg_match('/^itemtype/', $this->itemtype)) {
         $type = $this->fields[$this->itemtype];
      } else {
         $type = $this->itemtype;
      }
      if (class_exists($type)) {
         $item = new $type();
         return $item->isEntityAssign();
      }
      return -1;
   }

   /**
    * Is the object may be recursive
    *
    * @return boolean
   **/
   function maybeRecursive() {
      // Case of Duplicate Entity info to child
      if (parent::maybeRecursive()) {
         return true;
      }

      if (preg_match('/^itemtype/', $this->itemtype)) {
         $type = $this->fields[$this->itemtype];
      } else {
         $type = $this->itemtype;
      }
      if (class_exists($type)) {
         $item = new $type();
         return $item->maybeRecursive();
      }
      return false;
   }

   /**
    * Is the object recursive
    *
    * @return boolean
    **/
   function isRecursive () {
      // Case of Duplicate Entity info to child
      if (parent::maybeRecursive()) {
          return parent::isRecursive();
      }

      if (preg_match('/^itemtype/', $this->itemtype)) {
         $type = $this->fields[$this->itemtype];
      } else {
         $type = $this->itemtype;
      }
      if (class_exists($type)) {
         $item = new $type();
         if ($item->getFromDB($this->fields[$this->items_id])) {
            return $item->isRecursive();
         }
      }
      return false;
   }

   /**
    * Actions done after the ADD of the item in the database
    *
    * @return nothing
    *
   **/
   function post_addItem() {

      if (isset($this->input['_no_history']) || !$this->dohistory) {
         return false;
      }
      if (preg_match('/^itemtype/', $this->itemtype)) {
         $type = $this->fields[$this->itemtype];
      } else {
         $type = $this->itemtype;
      }
      if (!class_exists($type)) {
         return false;
      }
      $item = new $type();
      if (!$item->dohistory) {
         return false;
      }
      $changes[0]='0';
      $changes[1]="";
      $changes[2]=addslashes($this->getNameID());
      Log::history($this->fields[$this->items_id],$type,$changes,get_class($this),
                   HISTORY_ADD_SUBITEM);
   }
   /**
    * Actions done after the DELETE of the item in the database
    *
    *@return nothing
    *
    **/
   function post_deleteFromDB() {

      if (isset($this->input['_no_history']) || !$this->dohistory) {
         return false;
      }
      if (preg_match('/^itemtype/', $this->itemtype)) {
         $type = $this->fields[$this->itemtype];
      } else {
         $type = $this->itemtype;
      }
      if (!class_exists($type)) {
         return false;
      }
      $item = new $type();
      if (!$item->dohistory) {
         return false;
      }
      $changes[0]='0';
      $changes[1]=addslashes($this->getNameID());
      $changes[2]="";
      Log::history($this->fields[$this->items_id],$type,$changes,get_class($this),
                   HISTORY_DELETE_SUBITEM);
   }

   /**
    * Clean the Relation Table when item of the relation is deleted
    * To be call from the cleanDBonPurge of each Item class
    *
    * @param $itemtype : type of the item
    * @param $item_id : id of the item
    */
   function cleanDBonItemDelete ($itemtype, $item_id) {
      global $DB;

      $query = "SELECT `id`
                FROM `".$this->getTable()."`";

      if ($itemtype == $this->itemtype) {
         $where = " WHERE `".$this->items_id."`='$item_id'";

      } else if (preg_match('/^itemtype/',$this->itemtype)) {
         $where = " WHERE (`".$this->itemtype."`='$itemtype'
                           AND `".$this->items_id."`='$item_id')";
      } else {
         return false;
      }

      $result = $DB->query($query.$where);
      while ($data = $DB->fetch_assoc($result)) {
         $data['_no_history'] = true; // Parent is deleted
         $this->delete($data);
      }
   }
}

?>
