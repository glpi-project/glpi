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


$NEEDED_ITEMS = array ('computer', 'contract', 'device', 'document', 'group', 'infocom', 'link',
                       'monitor', 'networking', 'ocsng', 'peripheral', 'phone', 'printer',
                       'registry', 'reservation', 'rulesengine', 'rule.dictionnary.dropdown',
                       'rule.dictionnary.software', 'rule.softwarecategories', 'search', 'setup',
                       'software', 'supplier', 'tracking', 'user');

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}
if (!isset($_GET["sort"])) {
   $_GET["sort"] = "";
}
if (!isset($_GET["order"])) {
   $_GET["order"] = "";
}
if (!isset($_GET["withtemplate"])) {
   $_GET["withtemplate"] = "";
}

$computer = new Computer();
//Add a new computer
if (isset($_POST["add"])) {
   $computer->check(-1,'w',$_POST);

   if ($newID = $computer->add($_POST)) {
      logEvent($newID, "computers", 4, "inventory",
               $_SESSION["glpiname"]." ".$LANG['log'][20]." ".$_POST["name"].".");
   }
   glpi_header($_SERVER['HTTP_REFERER']);

// delete a computer
} else if (isset($_POST["delete"])) {
   $computer->check($_POST['id'],'w');

   if (!empty($_POST["withtemplate"])) {
      $ok = $computer->delete($_POST,1);
   } else {
      $ok = $computer->delete($_POST);
   }
   if ($ok) {
      logEvent($_POST["id"], "computers", 4, "inventory",
               $_SESSION["glpiname"]." ".$LANG['log'][22]." ".$computer->getField('name'));
   }
   if (!empty($_POST["withtemplate"])) {
      glpi_header($CFG_GLPI["root_doc"]."/front/setup.templates.php");
   }
   glpi_header($CFG_GLPI["root_doc"]."/front/computer.php");

} else if (isset($_POST["restore"])) {
   $computer->check($_POST['id'],'w');

   if ($computer->restore($_POST)) {
      logEvent($_POST["id"],"computers", 4, "inventory",
               $_SESSION["glpiname"]." ".$LANG['log'][23]." ".$computer->getField('name'));
   }
   glpi_header($CFG_GLPI["root_doc"]."/front/computer.php");

} else if (isset($_POST["purge"]) || isset($_GET["purge"])) {
   $input["id"] = $_GET["id"];
   if (isset($_POST["purge"])) {
      $input["id"] = $_POST["id"];
   }

   $computer->check($input['id'],'w');

   if ($computer->delete($input,1)) {
      logEvent($input["id"], "computers", 4, "inventory",
               $_SESSION["glpiname"]." ".$LANG['log'][24]." ".$computer->getField('name'));
   }
   glpi_header($CFG_GLPI["root_doc"]."/front/computer.php");

//update a computer
} else if (isset($_POST["update"])) {
   $computer->check($_POST['id'],'w');

   if ($computer->update($_POST)) {
      logEvent($_POST["id"], "computers", 4, "inventory", $_SESSION["glpiname"]." ".$LANG['log'][21]);
   }
   glpi_header($_SERVER['HTTP_REFERER']);

//Disconnect a device
} else if (isset($_GET["disconnect"])) {
   $computer->check($_GET['computers_id'],'w');

   Disconnect($_GET["id"]);
   logEvent($_GET["computers_id"], "computers", 5, "inventory",
            $_SESSION["glpiname"]." ".$LANG['log'][26]);
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["connect"]) && isset($_POST["item"]) && $_POST["item"] >0) {
   $computer->check($_POST['computers_id'],'w');

   Connect($_POST["item"],$_POST["computers_id"],$_POST["itemtype"],$_POST["dohistory"]);
   logEvent($_POST["computers_id"], "computers", 5, "inventory",
            $_SESSION["glpiname"] ." ".$LANG['log'][27]);
   glpi_header($_SERVER['HTTP_REFERER']);

//Update a device specification
} else if(isset($_POST["update_device"])) {
   $computer->check($_POST['id'],'w');

   // Update quantity
   foreach ($_POST as $key => $val) {
      $data = explode("_",$key);
      if (count($data) == 2) {
         if ($data[0] == "quantity") {
            update_device_quantity($val,$data[1]);
         }
      }
   }

   // Update specificity
   foreach ($_POST as $key => $val) {
      $data = explode("_",$key);
      if (count($data) == 2) {
         if ($data[0] == "devicevalue") {
            update_device_specif($val,$data[1]);
         }
      }
   }

   logEvent($_POST["id"],"computers",4,"inventory",$_SESSION["glpiname"] ." ".$LANG['log'][28]);
   glpi_header($_SERVER['HTTP_REFERER']);

//add a new device
} elseif (isset($_POST["connect_device"])) {
   $computer->check($_POST['computers_id'],'w');

   if (isset($_POST["devices_id"]) && $_POST["devices_id"] >0) {
      compdevice_add($_POST["computers_id"],$_POST["devicetype"],$_POST["devices_id"]);
   }
   glpi_header($_SERVER['PHP_SELF']."?id=".$_POST["computers_id"]."&withtemplate=".$_POST["withtemplate"]);

} else if (isset($_POST["unlock_monitor"])) {
   $computer->check($_POST['id'],'w');

   if (isset($_POST["lockmonitor"]) && count($_POST["lockmonitor"])) {
      foreach ($_POST["lockmonitor"] as $key => $val) {
         deleteInOcsArray($_POST["id"],$key,"import_monitor");
      }
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["unlock_printer"])) {
   $computer->check($_POST['id'],'w');

   if (isset($_POST["lockprinter"]) && count($_POST["lockprinter"])) {
      foreach ($_POST["lockprinter"] as $key => $val) {
         deleteInOcsArray($_POST["id"],$key,"import_printer");
      }
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["unlock_soft"])) {
   $computer->check($_POST['id'],'w');

   if (isset($_POST["locksoft"]) && count($_POST["locksoft"])) {
      foreach ($_POST["locksoft"] as $key => $val) {
         deleteInOcsArray($_POST["id"],$key,"import_software");
      }
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["unlock_disk"])) {
   $computer->check($_POST['id'],'w');

   if (isset($_POST["lockdisk"]) && count($_POST["lockdisk"])) {
      foreach ($_POST["lockdisk"] as $key => $val) {
         deleteInOcsArray($_POST["id"],$key,"import_disk");
      }
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["unlock_periph"])) {
   $computer->check($_POST['id'],'w');

   if (isset($_POST["lockperiph"]) && count($_POST["lockperiph"])) {
      foreach ($_POST["lockperiph"] as $key => $val) {
         deleteInOcsArray($_POST["id"],$key,"import_peripheral");
      }
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["unlock_ip"])) {
   $computer->check($_POST['id'],'w');

   if (isset($_POST["lockip"]) && count($_POST["lockip"])) {
      foreach ($_POST["lockip"] as $key => $val) {
         deleteInOcsArray($_POST["id"],$key,"import_ip");
      }
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["unlock_field"])) {
   $computer->check($_POST['id'],'w');

   if (isset($_POST["lockfield"]) && count($_POST["lockfield"])) {
      foreach ($_POST["lockfield"] as $key => $val) {
         deleteInOcsArray($_POST["id"],$key,"computer_update");
      }
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["force_ocs_resynch"])) {
   $computer->check($_POST['id'],'w');

   //Get the ocs server id associated with the machine
   $ocsservers_id = getOCSServerByMachineID($_POST["id"]);

   //Update the computer
   ocsUpdateComputer($_POST["resynch_id"],$ocsservers_id,1,1);
   glpi_header($_SERVER['HTTP_REFERER']);

} else {//print computer informations
   commonHeader($LANG['Menu'][0],$_SERVER['PHP_SELF'],"inventory","computer");
   //show computer form to add
   $computer->showForm($_SERVER['PHP_SELF'],$_GET["id"], $_GET["withtemplate"]);
   commonFooter();
}

?>