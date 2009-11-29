<?php
/*
 * @version $Id: commondbtm.class.php 9363 2009-11-26 21:02:42Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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
abstract class CommonDBRelation extends CommonDBTM {

   // Mapping between DB fields
   var $itemtype_1; // Type ref or field name
   var $items_id_1; // Field name
   var $itemtype_2; // Type ref or field name
   var $items_id_2; // Field name

   /**
    * Check right on an item
    *
    * @param $ID ID of the item (-1 if new item)
    * @param $right Right to check : r / w / recursive
    * @param $input array of input data (used for adding item)
    *
    * @return boolean
   **/
   function can($ID,$right,&$input=NULL) {

      if ($ID>0) {
         if (!isset($this->fields['id']) || $this->fields['id']!=$ID) {
            // Item not found : no right
            if (!$this->getFromDB($ID)) {
               return false;
            }
         }
         $input = &$this->fields;
      }

      // Must can read first Item of the relation
      $ci1 = new CommonItem();
      $ci1->setType(is_numeric($this->itemtype_1) ? $this->itemtype_1 : $input[$this->itemtype_1],
                    true);
      if (!$ci1->obj->can($input[$this->items_id_1],'r')) {
         return false;
      }
      // Must can read second Item of the relation
      $ci2 = new CommonItem();
      $ci2->setType(is_numeric($this->itemtype_2) ? $this->itemtype_2 : $input[$this->itemtype_2],
                    true);
      if (!$ci2->obj->can($input[$this->items_id_2],'r')) {
         return false;
      }

      // Read right checked on both item
      if ($right=='r') {
         return true;
      }

      // Check entity compatibility
      if ($ci1->obj->isEntityAssign() && $ci2->obj->isEntityAssign()) {
         if ($ci1->obj->getEntityID() == $ci2->obj->getEntityID()) {
            $checkentity = true;
         } else if ($ci1->obj->isRecursive()
                    && in_array($ci1->obj->getEntityID(),
                                 getAncestorsOf("glpi_entities",$ci2->obj->getEntityID()))) {
            $checkentity = true;
         } else if ($ci2->obj->isRecursive()
                    && in_array($ci2->obj->getEntityID(),
                                getAncestorsOf("glpi_entities",$ci1->obj->getEntityID()))) {
            $checkentity = true;
         } else {
            // $checkentity is false => return
            return false;
         }
      } else {
         $checkentity = true;
      }
      // can write one item is enough
      if ($ci1->obj->can($input[$this->items_id_1],'w')
          || $ci2->obj->can($input[$this->items_id_2],'w')) {
         return true;
      }
      return false;
   }

   /**
    * Actions done after the ADD of the item in the database
    *
    *@param $newID ID of the new item
    *@param $input datas used to add the item
    *
    * @return nothing
    *
   **/
   function post_addItem($newID,$input) {

      if (isset($input['_no_history'])) {
         return false;
      }
      $ci1 = new CommonItem();
      $ci1->setType(is_numeric($this->itemtype_1) ? $this->itemtype_1 :
                    $this->fields[$this->itemtype_1], true);
      if (!$ci1->obj->getFromDB($this->fields[$this->items_id_1])) {
         return false;
      }
      $ci2 = new CommonItem();
      $ci2->setType(is_numeric($this->itemtype_2) ? $this->itemtype_2 :
                    $this->fields[$this->itemtype_2], true);
      if (!$ci2->obj->getFromDB($this->fields[$this->items_id_2])) {
         return false;
      }

      if ($ci1->obj->dohistory) {
         $changes[0]='0';
         $changes[1]="";
         $changes[2]=addslashes($ci2->getNameID());
         historyLog ($ci1->obj->fields["id"],$ci1->obj->type,$changes,$ci2->obj->type,
                     HISTORY_ADD_RELATION);
      }
      if ($ci2->obj->dohistory) {
         $changes[0]='0';
         $changes[1]="";
         $changes[2]=addslashes($ci1->getNameID());
         historyLog ($ci2->obj->fields["id"],$ci2->obj->type,$changes,$ci1->obj->type,
                     HISTORY_ADD_RELATION);
      }
   }
   /**
    * Actions done after the DELETE of the item in the database
    *
    *@param $ID ID of the item
    *
    *@return nothing
    *
    **/
   function post_deleteFromDB($ID) {

      if (isset($this->input['_no_history'])) {
         return false;
      }
      $ci1 = new CommonItem();
      $ci1->setType(is_numeric($this->itemtype_1) ? $this->itemtype_1 :
                    $this->fields[$this->itemtype_1], true);
      if (!$ci1->obj->getFromDB($this->fields[$this->items_id_1])) {
         return false;
      }
      $ci2 = new CommonItem();
      $ci2->setType(is_numeric($this->itemtype_2) ? $this->itemtype_2 :
                    $this->fields[$this->itemtype_2], true);
      if (!$ci2->obj->getFromDB($this->fields[$this->items_id_2])) {
         return false;
      }

      if ($ci1->obj->dohistory) {
         $changes[0]='0';
         $changes[1]=addslashes($ci2->getNameID());
         $changes[2]="";
         historyLog ($ci1->obj->fields["id"],$ci1->obj->type,$changes,$ci2->obj->type,
                     HISTORY_DEL_RELATION);
      }
      if ($ci2->obj->dohistory) {
         $changes[0]='0';
         $changes[1]=addslashes($ci1->getNameID());
         $changes[2]="";
         historyLog ($ci2->obj->fields["id"],$ci2->obj->type,$changes,$ci1->obj->type,
                     HISTORY_DEL_RELATION);
      }
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
                FROM `".$this->table."`";

      if ($itemtype==$this->itemtype_1) {
         $where = " WHERE `".$this->items_id_1."`='$item_id'";
      } else if (!is_numeric($this->itemtype_1)) {
         $where = " WHERE (`".$this->itemtype_1."`='$itemtype'
                           AND `".$this->items_id_1."`='$item_id')";
      } else {
         $where = '';
      }

      if ($itemtype==$this->itemtype_2) {
         $where .= (empty($where) ? " WHERE " : " OR ");
         $where .= " `".$this->items_id_2."`='$item_id'";
      } else if (!is_numeric($this->itemtype_2)) {
         $where .= (empty($where) ? " WHERE " : " OR ");
         $where .= " (`".$this->itemtype_2."`='$itemtype'
                      AND `".$this->items_id_2."`='$item_id')";
      }

      if (empty($where)) {
         return false;
      }
      $result = $DB->query($query.$where);
      while ($data = $DB->fetch_assoc($result)) {
         $this->delete(array('id'=>$data['id']));
      }
   }
}

?>