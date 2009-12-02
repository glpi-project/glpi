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

/**
 * InfoCom class
 */
class InfoCom extends CommonDBTM {

   // From CommonDBTM
   public $table = 'glpi_infocoms';
   public $type = INFOCOM_TYPE;
   public $dohistory=true;
   public $auto_message_on_action=true;

   static function getTypeName() {
      global $LANG;

      return $LANG['financial'][3];
   }

   function post_getEmpty() {
      global $CFG_GLPI;

   $this->fields["alert"]=$CFG_GLPI["default_infocom_alert"];
   }

   /**
    * Retrieve an item from the database for a device
    *
    *@param $ID ID of the device to retrieve infocom
    *@param $itemtype type of the device to retrieve infocom
    *@return true if succeed else false
   **/
   function getFromDBforDevice ($itemtype,$ID) {
      global $DB;

      $query = "SELECT *
                FROM `".$this->table."`
                WHERE `items_id` = '$ID'
                      AND `itemtype`='$itemtype'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)==1) {
            $data = $DB->fetch_assoc($result);
            foreach ($data as $key => $val) {
               $this->fields[$key] = $val;
            }
            return true;
         } else {
            $this->getEmpty();
            $this->fields["items_id"]=$ID;
            $this->fields["itemtype"]=$itemtype;
            return false;
         }
      } else {
         return false;
      }
   }

   function prepareInputForAdd($input) {
      global $CFG_GLPI;

      if (!$this->getFromDBforDevice($input['itemtype'],$input['items_id'])) {
         $input['alert']=$CFG_GLPI["default_infocom_alert"];
         return $input;
      }
      return false;
   }

   function prepareInputForUpdate($input) {

      if (isset($input["id"])) {
         $this->getFromDB($input["id"]);
      } else {
         if (!$this->getFromDBforDevice($input["itemtype"],$input["items_id"])) {
            $input2["items_id"]=$input["items_id"];
            $input2["itemtype"]=$input["itemtype"];
            $this->add($input2);
            $this->getFromDBforDevice($input["itemtype"],$input["items_id"]);
         }
         $input["id"]=$this->fields["id"];
      }

      if (isset($input['warranty_duration'])) {
         $input['_warranty_duration']=$this->fields['warranty_duration'];
      }
      return $input;
   }

   function pre_updateInDB($input,$updates,$oldvalues=array()) {

      // Clean end alert if buy_date is after old one
      // Or if duration is greater than old one
      if ((isset($oldvalues['buy_date']) && ($oldvalues['buy_date'] < $this->fields['buy_date']))
          || (isset($oldvalues['warranty_duration'])
          && ($oldvalues['warranty_duration'] < $this->fields['warranty_duration']))) {

         $alert=new Alert();
         $alert->clear($this->type,$this->fields['id'],ALERT_END);
      }
      return array($input,$updates);
   }

   /**
    * Is the object assigned to an entity
    *
    * @return boolean
   **/
   function isEntityAssign() {

      $ci=new CommonItem();
      $ci->setType($this->fields["itemtype"], true);

      return $ci->obj->isEntityAssign();
   }

   /**
    * Get the ID of entity assigned to the object
    *
    * @return ID of the entity
   **/
   function getEntityID () {

      $ci=new CommonItem();
      $ci->getFromDB($this->fields["itemtype"], $this->fields["items_id"]);

      return $ci->obj->getEntityID();
   }

   /**
    * Is the object may be recursive
    *
    * @return boolean
   **/
   function maybeRecursive() {

      $ci=new CommonItem();
      $ci->setType($this->fields["itemtype"], true);

      return $ci->obj->maybeRecursive();
   }

   /**
    * Is the object recursive
    *
    * Can be overloaded (ex : infocom)
    *
    * @return integer (0/1)
   **/
   function isRecursive() {

      $ci=new CommonItem();
      $ci->getFromDB($this->fields["itemtype"], $this->fields["items_id"]);

      return $ci->obj->isRecursive();
   }
}

?>
