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
               require_once(GLPI_ROOT.'/inc/computer.class.php');
               $this->obj=new Computer;
               break;

            case NETWORKING_TYPE :
               require_once(GLPI_ROOT.'/inc/networking.class.php');
               $this->obj=new Netdevice;
               break;

            case PRINTER_TYPE :
               require_once(GLPI_ROOT.'/inc/printer.class.php');
               $this->obj=new Printer;
               break;

            case MONITOR_TYPE :
               require_once(GLPI_ROOT.'/inc/monitor.class.php');
               $this->obj= new Monitor;
               break;

            case PERIPHERAL_TYPE :
               require_once(GLPI_ROOT.'/inc/peripheral.class.php');
               $this->obj= new Peripheral;
               break;

            case SOFTWARE_TYPE :
               require_once(GLPI_ROOT.'/inc/software.class.php');
               $this->obj= new Software;
               break;

            case CONTACT_TYPE :
               require_once(GLPI_ROOT.'/inc/contact.class.php');
               $this->obj= new Contact;
               break;

            case ENTERPRISE_TYPE :
               require_once(GLPI_ROOT.'/inc/supplier.class.php');
               $this->obj= new Supplier;
               break;

            case CONTRACT_TYPE :
               require_once(GLPI_ROOT.'/inc/contract.class.php');
               $this->obj= new Contract;
               break;

            case CARTRIDGEITEM_TYPE :
               require_once(GLPI_ROOT.'/inc/cartridgeitem.class.php');
               $this->obj= new CartridgeItem;
               break;

            case TYPEDOC_TYPE :
               require_once(GLPI_ROOT.'/inc/documenttype.class.php');
               $this->obj= new DocumentType;
               break;

            case DOCUMENT_TYPE :
               require_once(GLPI_ROOT.'/inc/document.class.php');
               $this->obj= new Document;
               break;

            case KNOWBASE_TYPE :
               require_once(GLPI_ROOT.'/inc/knowbase.class.php');
               $this->obj= new kbitem;
               break;

            case USER_TYPE :
               require_once(GLPI_ROOT.'/inc/user.class.php');
               $this->obj= new User;
               break;

            case TRACKING_TYPE :
               require_once(GLPI_ROOT.'/inc/tracking.class.php');
               $this->obj= new Job;
               break;

            case CONSUMABLEITEM_TYPE :
               require_once(GLPI_ROOT.'/inc/consumable.class.php');
               $this->obj= new ConsumableType;
               break;

            case CARTRIDGE_TYPE :
               require_once(GLPI_ROOT.'/inc/cartridge.class.php');
               $this->obj= new Cartridge;
               break;

            case CONSUMABLE_TYPE :
               require_once(GLPI_ROOT.'/inc/consumable.class.php');
               $this->obj= new Consumable;
               break;

            case SOFTWARELICENSE_TYPE :
               require_once(GLPI_ROOT.'/inc/software.class.php');
               $this->obj= new SoftwareLicense;
               break;

            case SOFTWAREVERSION_TYPE :
               require_once(GLPI_ROOT.'/inc/software.class.php');
               $this->obj= new SoftwareVersion;
               break;

            case LINK_TYPE :
               require_once(GLPI_ROOT.'/inc/link.class.php');
               $this->obj= new Link;
               break;

            case PHONE_TYPE :
               require_once(GLPI_ROOT.'/inc/phone.class.php');
               $this->obj= new Phone;
               break;

            case REMINDER_TYPE :
               require_once(GLPI_ROOT.'/inc/reminder.class.php');
               $this->obj= new Reminder;
               break;

            case GROUP_TYPE :
               require_once(GLPI_ROOT.'/inc/group.class.php');
               $this->obj= new Group;
               break;

            case ENTITY_TYPE :
               require_once(GLPI_ROOT.'/inc/entity.class.php');
               $this->obj= new Entity;
               break;

            case AUTH_MAIL_TYPE:
               require_once(GLPI_ROOT.'/inc/auth.class.php');
               $this->obj = new AuthMail;
               break;

            case AUTH_LDAP_TYPE:
               require_once(GLPI_ROOT.'/inc/auth.class.php');
               $this->obj = new AuthLDAP;
               break;

            case OCSNG_TYPE:
               require_once(GLPI_ROOT.'/inc/ocsng.class.php');
               $this->obj = new Ocsng;
               break;

            case REGISTRY_TYPE:
               require_once(GLPI_ROOT.'/inc/registrykey.class.php');
               $this->obj = new RegistryKey;
               break;

            case PROFILE_TYPE:
               require_once(GLPI_ROOT.'/inc/profile.class.php');
               $this->obj = new Profile;
               break;

            case MAILGATE_TYPE:
               require_once(GLPI_ROOT.'/inc/mailgate.class.php');
               $this->obj = new Mailgate;
               break;

            case INFOCOM_TYPE:
               require_once(GLPI_ROOT.'/inc/infocom.class.php');
               $this->obj = new InfoCom;
               break;

            case TRANSFER_TYPE:
               require_once(GLPI_ROOT.'/inc/transfer.class.php');
               $this->obj = new Transfer;
               break;

            case BOOKMARK_TYPE:
               require_once(GLPI_ROOT.'/inc/bookmark.class.php');
               $this->obj = new Bookmark;
               break;

            case BUDGET_TYPE:
               require_once(GLPI_ROOT.'/inc/budget.class.php');
               $this->obj = new Budget;
               break;

            case CRONTASK_TYPE:
               require_once(GLPI_ROOT.'/inc/crontask.class.php');
               $this->obj = new CronTask;
               break;

            case CRONTASKLOG_TYPE:
               require_once(GLPI_ROOT.'/inc/crontask.class.php');
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

            case ITEMSTATE_TYPE:
               $this->obj = new State();
               break;

            case REQUESTTYPE_TYPE :
               $this->obj = new RequestType();
               break;

            case MANUFACTURER_TYPE :
               $this->obj = new Manufacturer();
               break;

            case COMPUTERTYPE_TYPE :
               $this->obj = new ComputerType();
               break;

            case COMPUTERMODEL_TYPE :
               $this->obj = new ComputerModel();
               break;

            case NETWORKEQUIPMENTTYPE_TYPE :
               $this->obj = new NetworkEquipmentType();
               break;

            case NETWORKEQUIPMENTMODEL_TYPE :
               $this->obj = new NetworkEquipementModel();
               break;

            case PRINTERTYPE_TYPE :
               $this->obj = new PrinterType();
               break;

            case PRINTERMODEL_TYPE :
               $this->obj = new PrinterModel();
               break;

            case MONITORTYPE_TYPE :
               $this->obj = new MonitorType();
               break;

            case MONITORMODEL_TYPE :
               $this->obj = new MonitorModel();
               break;

            case PERIPHERALTYPE_TYPE :
               $this->obj = new PeripheralType();
               break;

            case PERIPHERALMODEL_TYPE :
               $this->obj = new PeripheralModel();
               break;

            case PHONETYPE_TYPE :
               $this->obj = new PhoneType();
               break;

            case PHONEMODEL_TYPE :
               $this->obj = new PhoneModel();
               break;

            case SOFTWARELICENSETYPE_TYPE :
               $this->obj = new SoftwareLicenseType();
               break;

            case CARTRIDGEITEMTYPE_TYPE :
               $this->obj = new CartridgeItemType();
               break;

            case CONSUMABLEITEMTYPE_TYPE :
               $this->obj = new ConsumableItemType();
               break;

            case CONTRACTTYPE_TYPE :
               $this->obj = new ContractType();
               break;

            case CONTACTTYPE_TYPE :
               $this->obj = new ContactType();
               break;

            case DEVICEMEMORYTYPE_TYPE :
               $this->obj = new DeviceMemoryType();
               break;

            case SUPPLIERTYPE_TYPE :
               $this->obj = new SupplierType();
               break;

            case INTERFACESTYPE_TYPE :
               $this->obj = new InterfaceType();
               break;

            case DEVICECASETYPE_TYPE :
               $this->obj = new DeviceCaseType();
               break;

            case PHONEPOWERSUPPLY_TYPE :
               $this->obj = new PhonePowerSupply();
               break;

            case FILESYSTEM_TYPE :
               $this->obj = new Filesystem();
               break;

            case DOCUMENTCATEGORY_TYPE :
               $this->obj = new DocumentCategory();
               break;

            case KNOWBASEITEMCATEGORY_TYPE :
               $this->obj = new KnowbaseItemCategory();
               break;

            case OPERATINGSYSTEM_TYPE :
               $this->obj = new OperatingSystem();
               break;

            case OPERATINGSYSTEMVERSION_TYPE :
               $this->obj = new OperatingSystemVersion();
               break;

            case OPERATINGSYSTEMSERVICEPACK_TYPE :
               $this->obj = new OperatingSystemServicePack();
               break;

            case AUTOUPDATESYSTEM_TYPE :
               $this->obj = new AutoUpdateSystem();
               break;

            case NETWORKINTERFACE_TYPE :
               $this->obj = new NetworkInterface();
               break;

            case NETWORKEQUIPMENTFIRMWARE_TYPE :
               $this->obj = new NetworkEquipmentFirmware();
               break;

            case DOMAIN_TYPE :
               $this->obj = new Domain();
               break;

            case NETWORK_TYPE :
               $this->obj = new Network();
               break;

            case VLAN_TYPE :
               $this->obj = new Vlan();
               break;

            case SOFTWARECATEGORY_TYPE :
               $this->obj = new SoftwareCategory();
               break;

            case USERTITLE_TYPE :
               $this->obj = new UserTitle();
               break;

            case USERCATEGORY_TYPE :
               $this->obj = new UserCategory();
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