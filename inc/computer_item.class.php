<?php
/*
 * @version $Id: contract_item.class.php 9363 2009-11-26 21:02:42Z moyo $
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

// Relation between Computer and Items (monitor, printer, phone, peripheral only)
class Computer_Item extends CommonDBRelation{

   // From CommonDBTM
   public $table = 'glpi_computers_items';
   public $type = COMPUTERITEM_TYPE;

   // From CommonDBRelation
   public $itemtype_1 = COMPUTER_TYPE;
   public $items_id_1 = 'computers_id';

   public $itemtype_2 = 'itemtype';
   public $items_id_2 = 'items_id';

   /**
    * Check right on an item - overloaded to check is_global
    *
    * @param $ID ID of the item (-1 if new item)
    * @param $right Right to check : r / w / recursive
    * @param $input array of input data (used for adding item)
    *
    * @return boolean
   **/
   function can($ID,$right,&$input=NULL) {

      if ($ID<0) {
         // Ajout
         $item = new CommonItem();

         if (!$item->getFromDB($input['itemtype'],$input['items_id'])) {
            return false;
         }
         if ($item->getField('is_global')==0
             && countElementsInTable($this->table,
                                     "`itemtype`='".$input['itemtype']."'
                                      AND `items_id`='".$input['items_id']."'") > 0) {
               return false;
         }
      }
      return parent::can($ID,$right,$input);
   }

   /**
   * Prepare input datas for adding the relation
   *
   * Overloaded to check is Disconnect needed (during OCS sync)
   * and to manage autoupdate feature
   *
   *@param $input datas used to add the item
   *
   *@return the modified $input array
   *
   **/
   function prepareInputForAdd($input) {
      global $DB, $CFG_GLPI, $LANG;

      switch ($input['itemtype']) {
         case MONITOR_TYPE :
            $item = new Monitor();
            $ocstab = 'import_monitor';
            break;

         case PHONE_TYPE :
            // shoul really never occurs as OCS doesn't sync phone
            $item = new Phone();
            $ocstab = '';
            break;

         case PRINTER_TYPE :
            $item = new Printer();
            $ocstab = 'import_printer';
            break;

         case PERIPHERAL_TYPE :
            $item = new Peripheral();
            $ocstab = 'import_peripheral';
            break;

         default :
            return false;
      }
      if (!$item->getFromDB($input['items_id'])) {
         return false;
      }
      if (!$item->getField('is_global') ) {
         // Handle case where already used, should never happen (except from OCS sync)
         $query = "SELECT `id`, `computers_id`
                   FROM `glpi_computers_items`
                   WHERE `glpi_computers_items`.`items_id` = '".$input['items_id']."'
                         AND `glpi_computers_items`.`itemtype` = '".$input['itemtype']."'";
         $result = $DB->query($query);
         while ($data=$DB->fetch_assoc($result)) {
            $temp = clone $this;
            $temp->delete($data);
            if ($ocstab) {
               deleteInOcsArray($data["computers_id"],$data["id"],$ocstab);
            }
         }

         // Autoupdate some fields - should be in post_addItem (here to avoid more DB access)
         $comp=new Computer();
         $comp->getFromDB($input['computers_id']);
         $updates = array();

         if ($CFG_GLPI["is_location_autoupdate"]
             && $comp->fields['locations_id'] != $item->getField('locations_id')){
            $updates[]="locations_id";
            $item->fields['locations_id']=addslashes($comp->fields['locations_id']);
            addMessageAfterRedirect($LANG['computers'][48],true);
         }
         if (($CFG_GLPI["is_user_autoupdate"]
              && $comp->fields['users_id'] != $item->getField('users_id'))
             || ($CFG_GLPI["is_group_autoupdate"]
                 && $comp->fields['groups_id'] != $item->getField('groups_id'))) {
            if ($CFG_GLPI["is_user_autoupdate"]) {
               $updates[]="users_id";
               $item->fields['users_id']=$comp->fields['users_id'];
            }
            if ($CFG_GLPI["is_group_autoupdate"]) {
               $updates[]="groups_id";
               $item->fields['groups_id']=$comp->fields['groups_id'];
            }
            addMessageAfterRedirect($LANG['computers'][50],true);
         }

         if ($CFG_GLPI["is_contact_autoupdate"]
             && ($comp->fields['contact'] != $item->getField('contact')
                 || $comp->fields['contact_num'] != $item->getField('contact_num'))) {
            $updates[]="contact";
            $updates[]="contact_num";
            $item->fields['contact']=addslashes($comp->fields['contact']);
            $item->fields['contact_num']=addslashes($comp->fields['contact_num']);
            addMessageAfterRedirect($LANG['computers'][49],true);
         }
         if ($CFG_GLPI["state_autoupdate_mode"]<0
             && $comp->fields['states_id'] != $item->getField('states_id')) {
            $updates[]="states_id";
            $item->fields['states_id']=$comp->fields['states_id'];
            addMessageAfterRedirect($LANG['computers'][56],true);
         }
         if ($CFG_GLPI["state_autoupdate_mode"]>0
             && $item->getField('states_id') != $CFG_GLPI["state_autoupdate_mode"]) {
            $updates[]="states_id";
            $item->fields['states_id']=$CFG_GLPI["state_autoupdate_mode"];
         }
         if (count($updates)) {
            $item->updateInDB($updates);
         }
      }
      return $input;
   }

   /**
    * Actions done when item is deleted from the database
    * Overloaded to manage autoupdate feature
    *
    *@param $ID ID of the item
    *
    *@return nothing
    **/
   function cleanDBonPurge($ID) {

   }
}

?>