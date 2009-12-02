<?php
/*
 * @version $Id$
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

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

//!  Consumable Class
/**
  This class is used to manage the consumables.
  @see ConsumableItem
  @author Julien Dombre
 */
class Consumable extends CommonDBTM {

   // From CommonDBTM
   public $table = 'glpi_consumables';
   public $type = CONSUMABLE_TYPE;
   // by the Consumable type
   public $entity_assign = true;

   static function getTypeName() {
      global $LANG;

      return $LANG['consumables'][0];
   }

   function cleanDBonPurge($ID) {
      global $DB;

      $query = "DELETE
                FROM `glpi_infocoms`
                WHERE (`items_id` = '$ID'
                       AND `itemtype`='".$this->type."')";
      $result = $DB->query($query);
   }

   function prepareInputForAdd($input) {
      return array("consumableitems_id"=>$input["tID"],
                   "date_in"=>date("Y-m-d"));
   }

   function post_addItem($newID,$input) {

      // Add infocoms if exists for the licence
      $ic=new Infocom();

      if ($ic->getFromDBforDevice(CONSUMABLEITEM_TYPE,$this->fields["consumableitems_id"])) {
         unset($ic->fields["id"]);
         $ic->fields["items_id"]=$newID;
         $ic->fields["itemtype"]=$this->type;
         if (empty($ic->fields['use_date'])) {
            unset($ic->fields['use_date']);
         }
         if (empty($ic->fields['buy_date'])) {
            unset($ic->fields['buy_date']);
         }
         $ic->addToDB();
      }
   }

   function restore($input,$history=1) {
      global $DB;

      $query = "UPDATE
                `".$this->table."`
                SET `date_out` = NULL
                WHERE `id`='".$input["id"]."'";

      if ($result = $DB->query($query)) {
         return true;
      } else {
         return false;
      }
   }

   /**
    * UnLink a consumable linked to a printer
    *
    * UnLink the consumable identified by $ID
    *
    *@param $ID : consumable identifier
    *@param $users_id : ID of the user giving the consumable
    *
    *@return boolean
    *
    **/
   function out($ID,$users_id=0) {
      global $DB;

      $query = "UPDATE
                `".$this->table."`
                SET `date_out` = '".date("Y-m-d")."',
                    `users_id` = '$users_id'
                WHERE `id` = '$ID'";

      if ($result = $DB->query($query)) {
         return true;
      } else {
         return false;
      }
   }

   /**
    * Get the ID of entity assigned to the Consumable
    *
    * @return ID of the entity
   **/
   function getEntityID () {
      $ci=new ConsumableItem();
      $ci->getFromDB($this->fields["consumableitems_id"]);

      return $ci->getEntityID();
   }

}

?>