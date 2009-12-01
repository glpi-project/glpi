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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

include_once (GLPI_ROOT . "/inc/timer.class.php");

// Init Timer to compute time of display
$TIMER_DEBUG=new Script_Timer;
$TIMER_DEBUG->Start_Timer();

include_once (GLPI_ROOT . "/inc/dbmysql.class.php");
include_once (GLPI_ROOT . "/inc/commonglpi.class.php");
include_once (GLPI_ROOT . "/inc/commondbtm.class.php");
include_once (GLPI_ROOT . "/inc/commondbrelation.class.php");
include_once (GLPI_ROOT . "/inc/commonitem.class.php");
include_once (GLPI_ROOT . "/inc/common.function.php");
include_once (GLPI_ROOT . "/inc/db.function.php");
include_once (GLPI_ROOT . "/inc/auth.function.php");
include_once (GLPI_ROOT . "/inc/display.function.php");
include_once (GLPI_ROOT . "/inc/ajax.function.php");
include_once (GLPI_ROOT . "/inc/dropdown.function.php");
include_once (GLPI_ROOT . "/inc/config.class.php");
include_once (GLPI_ROOT . "/config/config.php");
include_once (GLPI_ROOT . "/inc/plugin.function.php");
include_once (GLPI_ROOT . "/inc/plugin.class.php");

// Load Language file
loadLanguage();

if ($_SESSION['glpi_use_mode']==DEBUG_MODE) {
   $SQL_TOTAL_REQUEST=0;
   $DEBUG_SQL["queries"]=array();
   $DEBUG_SQL["errors"]=array();
   $DEBUG_SQL["times"]=array();
}

if (!isset($AJAX_INCLUDE)) {
   include_once (GLPI_ROOT . "/inc/auth.class.php");
   include_once (GLPI_ROOT . "/inc/mailing.class.php");
   include_once (GLPI_ROOT . "/inc/mailing.function.php");
   include_once (GLPI_ROOT . "/inc/export.function.php");
   include_once (GLPI_ROOT . "/inc/log.function.php");
   include_once (GLPI_ROOT . "/inc/bookmark.class.php");
   include_once (GLPI_ROOT . "/inc/alert.class.php");

   // TODO : clean it after autoload
   include_once (GLPI_ROOT . "/inc/authmail.class.php");
   include_once (GLPI_ROOT . "/inc/authldap.class.php");
   include_once (GLPI_ROOT . "/inc/authldapreplicate.class.php");
   include_once (GLPI_ROOT . "/inc/bookmark_user.class.php");
   include_once (GLPI_ROOT . "/inc/commondropdown.class.php");
   include_once (GLPI_ROOT . "/inc/commontreedropdown.class.php");
   include_once (GLPI_ROOT . "/inc/ticketcategory.class.php");
   include_once (GLPI_ROOT . "/inc/taskcategory.class.php");
   include_once (GLPI_ROOT . "/inc/location.class.php");
   include_once (GLPI_ROOT . "/inc/netpoint.class.php");
   include_once (GLPI_ROOT . "/inc/state.class.php");
   include_once (GLPI_ROOT . "/inc/requesttype.class.php");
   include_once (GLPI_ROOT . "/inc/manufacturer.class.php");
   include_once (GLPI_ROOT . "/inc/computertype.class.php");
   include_once (GLPI_ROOT . "/inc/computermodel.class.php");
   include_once (GLPI_ROOT . "/inc/networkequipmenttype.class.php");
   include_once (GLPI_ROOT . "/inc/networkequipmentmodel.class.php");
   include_once (GLPI_ROOT . "/inc/printertype.class.php");
   include_once (GLPI_ROOT . "/inc/printermodel.class.php");
   include_once (GLPI_ROOT . "/inc/monitortype.class.php");
   include_once (GLPI_ROOT . "/inc/monitormodel.class.php");
   include_once (GLPI_ROOT . "/inc/peripheraltype.class.php");
   include_once (GLPI_ROOT . "/inc/peripheralmodel.class.php");
   include_once (GLPI_ROOT . "/inc/phonetype.class.php");
   include_once (GLPI_ROOT . "/inc/phonemodel.class.php");
   include_once (GLPI_ROOT . "/inc/softwarelicensetype.class.php");
   include_once (GLPI_ROOT . "/inc/cartridgeitemtype.class.php");
   include_once (GLPI_ROOT . "/inc/consumableitemtype.class.php");
   include_once (GLPI_ROOT . "/inc/contracttype.class.php");
   include_once (GLPI_ROOT . "/inc/contacttype.class.php");
   include_once (GLPI_ROOT . "/inc/devicememorytype.class.php");
   include_once (GLPI_ROOT . "/inc/suppliertype.class.php");
   include_once (GLPI_ROOT . "/inc/interfacetype.class.php");
   include_once (GLPI_ROOT . "/inc/devicecasetype.class.php");
   include_once (GLPI_ROOT . "/inc/phonepowersupply.class.php");
   include_once (GLPI_ROOT . "/inc/filesystem.class.php");
   include_once (GLPI_ROOT . "/inc/documenttype.class.php");
   include_once (GLPI_ROOT . "/inc/documentcategory.class.php");
   include_once (GLPI_ROOT . "/inc/knowbaseitemcategory.class.php");
   include_once (GLPI_ROOT . "/inc/operatingsystem.class.php");
   include_once (GLPI_ROOT . "/inc/operatingsystemversion.class.php");
   include_once (GLPI_ROOT . "/inc/operatingsystemservicepack.class.php");
   include_once (GLPI_ROOT . "/inc/autoupdatesystem.class.php");
   include_once (GLPI_ROOT . "/inc/networkinterface.class.php");
   include_once (GLPI_ROOT . "/inc/networkequipmentfirmware.class.php");
   include_once (GLPI_ROOT . "/inc/network.class.php");
   include_once (GLPI_ROOT . "/inc/vlan.class.php");
   include_once (GLPI_ROOT . "/inc/softwarecategory.class.php");
   include_once (GLPI_ROOT . "/inc/usertitle.class.php");
   include_once (GLPI_ROOT . "/inc/usercategory.class.php");
   include_once (GLPI_ROOT . "/inc/domain.class.php");
   include_once (GLPI_ROOT . "/inc/preference.class.php");
   include_once (GLPI_ROOT . "/inc/notification.class.php");
   include_once (GLPI_ROOT . "/inc/computer_item.class.php");
}

// Security system
if (isset($_POST)) {
   if (get_magic_quotes_gpc()) {
      $_POST = array_map('stripslashes_deep', $_POST);
   }

   $_POST = array_map('addslashes_deep', $_POST);
   $_POST = array_map('clean_cross_side_scripting_deep', $_POST);
}
if (isset($_GET)) {
   if (get_magic_quotes_gpc()) {
      $_GET = array_map('stripslashes_deep', $_GET);
   }
   $_GET = array_map('addslashes_deep', $_GET);
   $_GET = array_map('clean_cross_side_scripting_deep', $_GET);
}

// Mark if Header is loaded or not :
$HEADER_LOADED=false;
$FOOTER_LOADED=false;
if (isset($AJAX_INCLUDE)) {
   $HEADER_LOADED=true;
}

if (isset($_SESSION['glpiautoload']) && $_SESSION['glpiautoload']){
   function __autoload($classname) {
         $dir=GLPI_ROOT . "/inc/";
//         $classname="PluginWebapplicationsProfile";
         if (preg_match("/Plugin([A-Z][a-z]+)([A-Z]\w+)/",$classname,$matches) ){
            $dir=GLPI_ROOT . "/plugins/".strtolower($matches[1])."/inc/";
            $item=strtolower($matches[2]);
         } else { // Standard case
            $item=strtolower($classname);
         }
         if (file_exists("$dir$item.class.php")) {
            include_once ("$dir$item.class.php");
         }
/*         if (file_exists("$dir$item.function.php")) {
            include_once ("$dir$item.function.php");
         }
*/
   }
} else {
   if (isset($NEEDED_ITEMS) && is_array($NEEDED_ITEMS)) {
      foreach ($NEEDED_ITEMS as $item) {
         // TODO : hack waiting for autoload
         if ($item=='enterprise') {
            $item='supplier';
         }
         if ($item=='registry') {
            $item='registrykey';
         }
         if ($item=='cartridge') {
            $item='cartridgeitem';
            include_once (GLPI_ROOT . "/inc/cartridge.class.php");
         }

         if ($item=='consumable') {
            $item='consumableitem';
            include_once (GLPI_ROOT . "/inc/consumable.class.php");
         }
         if ($item=='setup') {
            include_once (GLPI_ROOT . "/inc/displaypreference.class.php");
         }

         if ($item=='computer') {
            include_once (GLPI_ROOT . "/inc/computerdisk.class.php");
         }
         if ($item=='document') {
            include_once (GLPI_ROOT . "/inc/document_item.class.php");
         }
         if ($item=='software') {
            include_once (GLPI_ROOT . "/inc/softwareversion.class.php");
            include_once (GLPI_ROOT . "/inc/softwarelicense.class.php");
         }
         if ($item=='contract') {
            include_once (GLPI_ROOT . "/inc/contract_item.class.php");
            include_once (GLPI_ROOT . "/inc/contract_supplier.class.php");
         }
         if ($item=='contact') {
            include_once (GLPI_ROOT . "/inc/contact_supplier.class.php");
         }
         if ($item=='entity') {
            include_once (GLPI_ROOT . "/inc/entitydata.class.php");
         }


         if (file_exists(GLPI_ROOT . "/inc/$item.class.php")) {
            include_once (GLPI_ROOT . "/inc/$item.class.php");
         }
         if (file_exists(GLPI_ROOT . "/inc/$item.function.php")) {
            include_once (GLPI_ROOT . "/inc/$item.function.php");
         }
      }
   }
}



/* On startup, register all plugins configured for use. */
if (!isset($AJAX_INCLUDE) && !isset($PLUGINS_INCLUDED)) {
   // PLugin already included
   $PLUGINS_INCLUDED=1;
   $LOADED_PLUGINS=array();
   if (!isset($_SESSION["glpi_plugins"])) {
      initPlugins();
   }
   if (isset($_SESSION["glpi_plugins"]) && is_array($_SESSION["glpi_plugins"])) {
      //doHook("config");
      if (count($_SESSION["glpi_plugins"])) {
         foreach ($_SESSION["glpi_plugins"] as $name) {
            usePlugin($name);
         }
      }
   }
}

// Get search_option array / need to be included after plugin definition
//if (isset($NEEDED_ITEMS) && in_array('search', $NEEDED_ITEMS)) {
//   $SEARCH_OPTION=getSearchOptions();
//}

if (!isset($_SESSION["MESSAGE_AFTER_REDIRECT"])) {
   $_SESSION["MESSAGE_AFTER_REDIRECT"]="";
}

// Manage tabs
if (isset($_REQUEST['glpi_tab']) && isset($_REQUEST['itemtype'])) {
   $_SESSION['glpi_tabs'][$_REQUEST['itemtype']]=$_REQUEST['glpi_tab'];
}
// Override list-limit if choosen
if (isset($_REQUEST['glpilist_limit'])) {
   $_SESSION['glpilist_limit']=$_REQUEST['glpilist_limit'];
}

?>
