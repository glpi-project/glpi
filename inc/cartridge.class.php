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

//!  Cartridge Class
/**
 * This class is used to manage the cartridges.
 * @see CartridgeItem
 * @author Julien Dombre
 **/
class Cartridge extends CommonDBTM {

   // From CommonDBTM
   public $table = 'glpi_cartridges';
   public $type = CARTRIDGE_TYPE;
   // by the Cartridge Type
   public $entity_assign = true;

   function prepareInputForAdd($input) {
      return array("cartridgeitems_id"=>$input["tID"],
                   "date_in"=>date("Y-m-d"));
   }

   function post_addItem($newID,$input) {
      // Add infocoms if exists for the licence
      $ic=new Infocom();

      if ($ic->getFromDBforDevice(CARTRIDGEITEM_TYPE,$this->fields["cartridgeitems_id"])) {
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
                SET `date_out` = NULL, `date_use` = NULL, `printers_id` = '0'
                WHERE `id`='".$input["id"]."'";
      if ($result = $DB->query($query) && $DB->affected_rows() > 0) {
         return true;
      }
      return false;
   }

   // SPECIFIC FUNCTIONS
   /**
   * Update count pages value of a cartridge
   *
   *@param $ID ID of the cartridge
   *@param $pages  count pages value
   *
   *@return boolean : true for success
   **/
   function updatePages($ID,$pages) {
      global $DB;

      $query="UPDATE
              `".$this->table."`
              SET `pages`='$pages'
              WHERE `id`='$ID'";
      if ($result = $DB->query($query) && $DB->affected_rows() > 0) {
         return true;
      }
      return false;
   }

   /**
   * Link a cartridge to a printer.
   *
   * Link the first unused cartridge of type $Tid to the printer $pID
   *
   *@param $tID : cartridge type identifier
   *@param $pID : printer identifier
   *
   *@return boolean : true for success
   *
   **/
   function install($pID,$tID) {
      global $DB,$LANG;

      // Get first unused cartridge
      $query = "SELECT `id`
                FROM `".$this->table."`
                WHERE (`cartridgeitems_id` = '$tID'
                      AND `date_use` IS NULL)";
      $result = $DB->query($query);
      if ($DB->numrows($result)>0) {
         // Mise a jour cartouche en prenant garde aux insertion multiples
         $query = "UPDATE
                   `".$this->table."`
                   SET `date_use` = '".date("Y-m-d")."', `printers_id` = '$pID'
                   WHERE (`id`='".$DB->result($result,0,0)."'
                         AND `date_use` IS NULL)";
         if ($result = $DB->query($query) && $DB->affected_rows() > 0) {
            return true;
         }
      } else {
         addMessageAfterRedirect($LANG['cartridges'][34],false,ERROR);
      }
      return false;
   }

   /**
   * UnLink a cartridge linked to a printer
   *
   * UnLink the cartridge identified by $ID
   *
   *@param $ID : cartridge identifier
   *
   *@return boolean
   *
   **/
   function uninstall($ID) {
      global $DB;

      $query = "UPDATE
                `".$this->table."`
                SET `date_out` = '".date("Y-m-d")."'
                WHERE `id`='$ID'";
      if ($result = $DB->query($query) && $DB->affected_rows() > 0) {
         return true;
      }
      return false;
   }

   /**
   * Get the ID of entity assigned to the cartdrige
   *
   * @return ID of the entity
   **/
   function getEntityID () {
      $ci=new CartridgeItem();
      $ci->getFromDB($this->fields["cartridgeitems_id"]);
      return $ci->getEntityID();
   }

}

?>