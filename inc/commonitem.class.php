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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}


/**
 *  Common Item of GLPI : Global simple interface to items - abstraction usage
 */
class CommonItem {

   //! Object Type depending of the itemtype
   var $obj = NULL;
   //! Device Type ID of the object
   var $itemtype=0;
   //! Device ID of the object
   var $items_id=0;

   /**
    * Get an Object / General Function
    *
    * Create a new Object depending of $itemtype and Get the item with the ID $items_id
    *
    * @param $itemtype Device Type ID of the object
    * @param $items_id Device ID of the object
    *
    * @return boolean : object founded and loaded
    */
   function getFromDB ($itemtype,$items_id) {

      $this->items_id=$items_id;
      $this->setType($itemtype,1);

      if ($this->obj!=NULL) {
         // Do not load devices
         return $this->obj->getFromDB($items_id);
      } else {
         return false;
      }
   }

   /**
    * Set the device type
    *
    * @param $itemtype Device Type ID of the object
    * @param $init_object Create an instance of the object ?
    *
    */
   function setType ($itemtype,$init_object=false) {
      global $PLUGIN_HOOKS;

      $this->itemtype=$itemtype;
      // Make new database object and fill variables
      if ($init_object) {
         switch ($itemtype) {
            case COMPUTER_TYPE :
               $this->obj=new Computer;
               break;

            case NETWORKING_TYPE :
               $this->obj=new Netdevice;
               break;

            case PRINTER_TYPE :
               $this->obj=new Printer;
               break;

            case MONITOR_TYPE :
               $this->obj= new Monitor;
               break;

            case PERIPHERAL_TYPE :
               $this->obj= new Peripheral;
               break;

            case SOFTWARE_TYPE :
               $this->obj= new Software;
               break;

            case CONTACT_TYPE :
               $this->obj= new Contact;
               break;

            case ENTERPRISE_TYPE :
               $this->obj= new Enterprise;
               break;

            case CONTRACT_TYPE :
               $this->obj= new Contract;
               break;

            case CARTRIDGEITEM_TYPE :
               $this->obj= new CartridgeType;
               break;

            case TYPEDOC_TYPE :
               $this->obj= new TypeDoc;
               break;

            case DOCUMENT_TYPE :
               $this->obj= new Document;
               break;

            case KNOWBASE_TYPE :
               $this->obj= new kbitem;
               break;

            case USER_TYPE :
               $this->obj= new User;
               break;

            case TRACKING_TYPE :
               $this->obj= new Job;
               break;

            case CONSUMABLEITEM_TYPE :
               $this->obj= new ConsumableType;
               break;

            case CARTRIDGE_TYPE :
               $this->obj= new Cartridge;
               break;

            case CONSUMABLE_TYPE :
               $this->obj= new Consumable;
               break;

            case SOFTWARELICENSE_TYPE :
               $this->obj= new SoftwareLicense;
               break;

            case SOFTWAREVERSION_TYPE :
               $this->obj= new SoftwareVersion;
               break;

            case LINK_TYPE :
               $this->obj= new Link;
               break;

            case PHONE_TYPE :
               $this->obj= new Phone;
               break;

            case REMINDER_TYPE :
               $this->obj= new Reminder;
               break;

            case GROUP_TYPE :
               $this->obj= new Group;
               break;

            case ENTITY_TYPE :
               $this->obj= new Entity;
               break;

            case AUTH_MAIL_TYPE:
               $this->obj = new AuthMail;
               break;

            case AUTH_LDAP_TYPE:
               $this->obj = new AuthLDAP;
               break;

            case OCSNG_TYPE:
               $this->obj = new Ocsng;
               break;

            case REGISTRY_TYPE:
               $this->obj = new Registry;
               break;

            case PROFILE_TYPE:
               $this->obj = new Profile;
               break;

            case MAILGATE_TYPE:
               $this->obj = new Mailgate;
               break;

            case INFOCOM_TYPE:
               $this->obj = new InfoCom;
               break;

            case TRANSFER_TYPE:
               $this->obj = new Transfer;
               break;

            case BOOKMARK_TYPE:
               $this->obj = new Bookmark;
               break;

            case BUDGET_TYPE:
               $this->obj = new Budget;
               break;

            case CRONTASK_TYPE:
               $this->obj = new CronTask;
               break;

            case CRONTASKLOG_TYPE:
               $this->obj = new CronTaskLog;
               break;

            case TICKETCATEGORY_TYPE:
               $this->obj = new TicketCategory();
               break;

            case TASKCATEGORY_TYPE:
               $this->obj = new TaskCategory();
               break;

            case LOCATION_TYPE:
               $this->obj = new Location();
               break;

            case NETPOINT_TYPE:
               $this->obj = new Netpoint();
               break;

            default :
               // Plugin case
               if ($itemtype>1000) {
                  if (isset($PLUGIN_HOOKS['plugin_classes'][$itemtype])) {
                     $class=$PLUGIN_HOOKS['plugin_classes'][$itemtype];
                     $plug=$PLUGIN_HOOKS['plugin_types'][$itemtype];
                     if (file_exists(GLPI_ROOT . "/plugins/$plug/hook.php")) {
                        include_once(GLPI_ROOT . "/plugins/$plug/hook.php");
                     }
                     if (class_exists($class)) {
                        $this->obj = new $class();
                     }
                  }
               }
               break;
         }
      }
   }

   /**
    * Get The Type Name of the Object
    *
    * @return String: name of the object type in the current language
    */
   function getType () {
      global $LANG,$PLUGIN_HOOKS;

      switch ($this->itemtype) {
         case GENERAL_TYPE :
            return $LANG['help'][30];
            break;

         case COMPUTER_TYPE :
            return $LANG['help'][25];
            break;

         case NETWORKING_TYPE :
            return $LANG['help'][26];
            break;

         case PRINTER_TYPE :
            return $LANG['help'][27];
            break;

         case MONITOR_TYPE :
            return $LANG['help'][28];
            break;

         case PERIPHERAL_TYPE :
            return $LANG['help'][29];
            break;

         case SOFTWARE_TYPE :
            return $LANG['help'][31];
            break;

         case CONTACT_TYPE :
            return $LANG['common'][18];
            break;

         case ENTERPRISE_TYPE :
            return $LANG['financial'][26];
            break;

         case CONTRACT_TYPE :
            return $LANG['financial'][1];
            break;

         case CARTRIDGEITEM_TYPE :
            return $LANG['cartridges'][12];
            break;

         case TYPEDOC_TYPE :
            return $LANG['document'][7];
            break;

         case DOCUMENT_TYPE :
            return $LANG['Menu'][27];
            break;

         case KNOWBASE_TYPE :
            return $LANG['title'][5];
            break;

         case USER_TYPE :
            return $LANG['common'][34];
            break;

         case TRACKING_TYPE :
            return $LANG['job'][38];
            break;

         case CONSUMABLEITEM_TYPE :
            return $LANG['consumables'][12];
            break;

         case CARTRIDGE_TYPE :
            return $LANG['cartridges'][0];
            break;

         case CONSUMABLE_TYPE :
            return $LANG['consumables'][0];
            break;

         case SOFTWARELICENSE_TYPE :
            return $LANG['software'][11];
            break;

         case SOFTWAREVERSION_TYPE :
            return $LANG['software'][5];
            break;

         case LINK_TYPE :
            return $LANG['setup'][87];
            break;

         case PHONE_TYPE :
            return $LANG['help'][35];
            break;

         case REMINDER_TYPE :
            return $LANG['title'][37];
            break;

         case GROUP_TYPE :
            return $LANG['common'][35];
            break;

         case ENTITY_TYPE :
            return $LANG['Menu'][37];
            break;

         case AUTH_MAIL_TYPE:
            return $LANG['login'][3];
            break;

         case AUTH_LDAP_TYPE:
            return $LANG['login'][2];
            break;

         case OCSNG_TYPE:
            return $LANG['ocsng'][29];
            break;

         case REGISTRY_TYPE:
            return $LANG['title'][43];
            break;

         case PROFILE_TYPE:
            return $LANG['Menu'][35];
            break;

         case MAILGATE_TYPE:
            return $LANG['Menu'][39];
            break;

         case INFOCOM_TYPE:
            return $LANG['financial'][3];
            break;

         case BUDGET_TYPE :
            return $LANG['financial'][87];
            break;

         default :
            // Plugin case
            if ($this->itemtype>1000) {
               // Use plugin name if set
               if (isset($PLUGIN_HOOKS['plugin_typenames'][$this->itemtype])
                   && !empty($PLUGIN_HOOKS['plugin_typenames'][$this->itemtype])) {

                  return $PLUGIN_HOOKS['plugin_typenames'][$this->itemtype];
               } else { // Else use pluginname
                  if (isset($PLUGIN_HOOKS['plugin_types'][$this->itemtype])) {
                     $function="plugin_version_".$PLUGIN_HOOKS['plugin_types'][$this->itemtype];
                     if (function_exists($function)) {
                        $data=$function();
                        if (isset($data['name'])) {
                           return $data['name'];
                        }
                     }
                  }
               }
            }
            // TODO => could be used for most type but requires object instanciation
            if (method_exists($this->obj,'getTypeName')) {
               return $this->obj->getTypeName();
            }
            break;
      }
      return "";
   }

   /**
    * Get the value of a field
    * @param $field field name
    * @return value of the field / false if not exists
    */
   function getField($field) {

      if ($this->itemtype && isset($this->obj)) {
         return ($this->obj->getField($field));
      }
      return false;
   }

   /**
    * Get The Name of the Object
    * @param $with_comment add comments to name
    * @return String: name of the object in the current language
    */
   function getName($with_comment=0) {

      if ($this->itemtype && isset($this->obj)) {
         return ($this->obj->getName($with_comment));
      }
      return "N/A";
   }

   /**
    * Get The Name of the Object with the ID if the config is set
    * @param $with_comment add comments to name
    * @return String: name of the object in the current language
    */
   function getNameID($with_comment=0) {

      if ($this->itemtype && isset($this->obj)) {
         return ($this->obj->getNameID($with_comment));
      }
      return "N/A";
   }

   /**
    * Get The link to the Object
    * @param $with_comment Display comments
    * @return String: link to the object type in the current language
    */
   function getLink($with_comment=0) {

      if ($this->itemtype && isset($this->obj)) {
         return ($this->obj->getLink($with_comment));
      }
      return '';
   }

   /**
    * Get comments of the Object
    *
    * @return String: comments of the object in the current language
    */
   function getComments() {

      if ($this->itemtype && isset($this->obj)) {
         return ($this->obj->getComments());
      }
      return '';
   }
}

?>