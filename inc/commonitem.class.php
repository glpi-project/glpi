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
      if ($init_object && class_exists($itemtype)) {
         // For new object (ex dropdown) - Temporary
         $this->obj=new $itemtype();
      } else if ($init_object) {
         switch ($itemtype) {
            case 'Computer' :
               require_once(GLPI_ROOT.'/inc/computer.class.php');
               $this->obj=new Computer;
               break;

            case 'NetworkEquipment' :
               require_once(GLPI_ROOT.'/inc/networkequipment.class.php');
               $this->obj=new NetworkEquipment;
               break;

            case 'Printer' :
               require_once(GLPI_ROOT.'/inc/printer.class.php');
               $this->obj=new Printer;
               break;

            case 'Monitor' :
               require_once(GLPI_ROOT.'/inc/monitor.class.php');
               $this->obj= new Monitor;
               break;

            case 'Peripheral' :
               require_once(GLPI_ROOT.'/inc/peripheral.class.php');
               $this->obj= new Peripheral;
               break;

            case 'Software' :
               require_once(GLPI_ROOT.'/inc/software.class.php');
               $this->obj= new Software;
               break;

            case 'Contact' :
               require_once(GLPI_ROOT.'/inc/contact.class.php');
               $this->obj= new Contact;
               break;

            case 'Supplier' :
               require_once(GLPI_ROOT.'/inc/supplier.class.php');
               $this->obj= new Supplier;
               break;

            case 'Contract' :
               require_once(GLPI_ROOT.'/inc/contract.class.php');
               $this->obj= new Contract;
               break;

            case 'CartridgeItem' :
               require_once(GLPI_ROOT.'/inc/cartridgeitem.class.php');
               $this->obj= new CartridgeItem;
               break;

            case 'DocumentType' :
               require_once(GLPI_ROOT.'/inc/documenttype.class.php');
               $this->obj= new DocumentType;
               break;

            case 'Document' :
               require_once(GLPI_ROOT.'/inc/document.class.php');
               $this->obj= new Document;
               break;

            case 'KnowbaseItem' :
               require_once(GLPI_ROOT.'/inc/knowbaseitem.class.php');
               $this->obj= new KnowbaseItem;
               break;

            case 'User' :
               require_once(GLPI_ROOT.'/inc/user.class.php');
               $this->obj= new User;
               break;

            case 'Ticket' :
               require_once(GLPI_ROOT.'/inc/ticket.class.php');
               $this->obj= new Ticket;
               break;

            case 'ConsumableItem' :
               require_once(GLPI_ROOT.'/inc/consumableitem.class.php');
               $this->obj= new ConsumableItem;
               break;

            case 'Cartridge' :
               require_once(GLPI_ROOT.'/inc/cartridge.class.php');
               $this->obj= new Cartridge;
               break;

            case 'Consumable' :
               require_once(GLPI_ROOT.'/inc/consumable.class.php');
               $this->obj= new Consumable;
               break;

            case 'SoftwareLicense' :
               require_once(GLPI_ROOT.'/inc/software.class.php');
               $this->obj= new SoftwareLicense;
               break;

            case 'SoftwareVersion' :
               require_once(GLPI_ROOT.'/inc/software.class.php');
               $this->obj= new SoftwareVersion;
               break;

            case 'Link' :
               require_once(GLPI_ROOT.'/inc/link.class.php');
               $this->obj= new Link;
               break;

            case 'Phone' :
               require_once(GLPI_ROOT.'/inc/phone.class.php');
               $this->obj= new Phone;
               break;

            case 'Reminder' :
               require_once(GLPI_ROOT.'/inc/reminder.class.php');
               $this->obj= new Reminder;
               break;

            case 'Group' :
               require_once(GLPI_ROOT.'/inc/group.class.php');
               $this->obj= new Group;
               break;

            case 'Entity' :
               require_once(GLPI_ROOT.'/inc/entity.class.php');
               $this->obj= new Entity;
               break;

            case 'AuthMail':
               require_once(GLPI_ROOT.'/inc/auth.class.php');
               $this->obj = new AuthMail;
               break;

            case 'AuthLDAP':
               require_once(GLPI_ROOT.'/inc/auth.class.php');
               $this->obj = new AuthLDAP;
               break;

            case 'OcsServer':
               require_once(GLPI_ROOT.'/inc/ocsserver.class.php');
               $this->obj = new OcsServer;
               break;

            case 'RegistryKey':
               require_once(GLPI_ROOT.'/inc/registrykey.class.php');
               $this->obj = new RegistryKey;
               break;

            case 'Profile':
               require_once(GLPI_ROOT.'/inc/profile.class.php');
               $this->obj = new Profile;
               break;

            case 'MailCollector':
               require_once(GLPI_ROOT.'/inc/mailcollector.class.php');
               $this->obj = new MailCollector;
               break;

            case 'Infocom':
               require_once(GLPI_ROOT.'/inc/infocom.class.php');
               $this->obj = new Infocom;
               break;

            case 'Transfer':
               require_once(GLPI_ROOT.'/inc/transfer.class.php');
               $this->obj = new Transfer;
               break;

            case 'Bookmark':
               require_once(GLPI_ROOT.'/inc/bookmark.class.php');
               $this->obj = new Bookmark;
               break;

            case 'Budget':
               require_once(GLPI_ROOT.'/inc/budget.class.php');
               $this->obj = new Budget;
               break;

            default :
               // Plugin case
               if ($plug=isPluginItemType($itemtype)) {
                  $class=$itemtype;
                  $plug=$plug['plugin'];
                  if (file_exists(GLPI_ROOT . "/plugins/$plug/hook.php")) {
                     include_once(GLPI_ROOT . "/plugins/$plug/hook.php");
                  }
                  if (class_exists($class)) {
                     $this->obj = new $class();
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
         case '' :
            return $LANG['help'][30];
            break;

         case 'Computer' :
            return $LANG['help'][25];
            break;

         case 'NetworkEquipment' :
            return $LANG['help'][26];
            break;

         case 'Printer' :
            return $LANG['help'][27];
            break;

         case 'Monitor' :
            return $LANG['help'][28];
            break;

         case 'Pheripheral' :
            return $LANG['help'][29];
            break;

         case 'Software' :
            return $LANG['help'][31];
            break;

         case 'Contact' :
            return $LANG['common'][18];
            break;

         case 'Supplier' :
            return $LANG['financial'][26];
            break;

         case 'Contract' :
            return $LANG['financial'][1];
            break;

         case 'CartridgeItem' :
            return $LANG['cartridges'][12];
            break;

         case 'DocumentType' :
            return $LANG['document'][7];
            break;

         case 'Document' :
            return $LANG['Menu'][27];
            break;

         case 'KnowbaseItem' :
            return $LANG['title'][5];
            break;

         case 'User' :
            return $LANG['common'][34];
            break;

         case 'Ticket' :
            return $LANG['job'][38];
            break;

         case 'ConsumableItem' :
            return $LANG['consumables'][12];
            break;

         case 'Cartridge' :
            return $LANG['cartridges'][0];
            break;

         case 'Consumable' :
            return $LANG['consumables'][0];
            break;

         case 'SoftwareLicense' :
            return $LANG['software'][11];
            break;

         case 'SoftwareVersion' :
            return $LANG['software'][5];
            break;

         case 'Link' :
            return $LANG['setup'][87];
            break;

         case 'Phone' :
            return $LANG['help'][35];
            break;

         case 'Reminder' :
            return $LANG['title'][37];
            break;

         case 'Group' :
            return $LANG['common'][35];
            break;

         case 'Entity' :
            return $LANG['Menu'][37];
            break;

         case 'AuthMail':
            return $LANG['login'][3];
            break;

         case 'AuthLDAP':
            return $LANG['login'][2];
            break;

         case 'OcsServer':
            return $LANG['ocsng'][29];
            break;

         case 'RegistryKey':
            return $LANG['title'][43];
            break;

         case 'Profile':
            return $LANG['Menu'][35];
            break;

         case 'MailCollector':
            return $LANG['Menu'][39];
            break;

         case 'Infocom':
            return $LANG['financial'][3];
            break;

         case 'Budget' :
            return $LANG['financial'][87];
            break;

         default :
            // Plugin case
            if ($plug=isPluginItemType($this->itemtype)) {
               // Use plugin name if set
               if (isset($PLUGIN_HOOKS['plugin_typenames'][$this->itemtype])
                   && !empty($PLUGIN_HOOKS['plugin_typenames'][$this->itemtype])) {

                  return $PLUGIN_HOOKS['plugin_typenames'][$this->itemtype];
               } else { // Else use pluginname
                  $function="plugin_version_".$plug['plugin'];
                  if (function_exists($function)) {
                     $data=$function();
                     if (isset($data['name'])) {
                        return $data['name'];
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
      return NOT_AVAILABLE;
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
      return NOT_AVAILABLE;
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