<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
      Event::log($newID, "computers", 4, "inventory",
               $_SESSION["glpiname"]." ".$LANG['log'][20]." ".$_POST["name"].".");
   }
   glpi_header($_SERVER['HTTP_REFERER']);

// delete a computer
} else if (isset($_POST["delete"])) {
   $computer->check($_POST['id'],'d');
   $ok = $computer->delete($_POST);
   if ($ok) {
      Event::log($_POST["id"], "computers", 4, "inventory",
               $_SESSION["glpiname"]." ".$LANG['log'][22]." ".$computer->getField('name'));
   }
   $computer->redirectToList();

} else if (isset($_POST["restore"])) {
   $computer->check($_POST['id'],'d');

   if ($computer->restore($_POST)) {
      Event::log($_POST["id"],"computers", 4, "inventory",
               $_SESSION["glpiname"]." ".$LANG['log'][23]." ".$computer->getField('name'));
   }
   $computer->redirectToList();

} else if (isset($_REQUEST["purge"])) {
   $computer->check($_REQUEST['id'],'d');

   if ($computer->delete($_REQUEST,1)) {
      Event::log($_REQUEST["id"], "computers", 4, "inventory",
               $_SESSION["glpiname"]." ".$LANG['log'][24]." ".$computer->getField('name'));
   }
   $computer->redirectToList();

//update a computer
} else if (isset($_POST["update"])) {
   $computer->check($_POST['id'],'w');

   $computer->update($_POST);
   Event::log($_POST["id"], "computers", 4, "inventory", $_SESSION["glpiname"]." ".$LANG['log'][21]);

   glpi_header($_SERVER['HTTP_REFERER']);

// Disconnect a computer from a printer/monitor/phone/peripheral
} else if (isset($_GET["disconnect"])) {
   $conn = new Computer_Item();
   $conn->check($_GET["id"], 'w');
   $conn->delete($_GET);
   $computer->check($_GET['computers_id'],'w');

   Event::log($_GET["computers_id"], "computers", 5, "inventory",
            $_SESSION["glpiname"]." ".$LANG['log'][26]);
   glpi_header($_SERVER['HTTP_REFERER']);

// Connect a computer to a printer/monitor/phone/peripheral
} else if (isset($_POST["connect"]) && isset($_POST["items_id"]) && $_POST["items_id"]>0) {
   $conn = new Computer_Item();
   $conn->check(-1, 'w', $_POST);
   $conn->add($_POST);
   Event::log($_POST["computers_id"], "computers", 5, "inventory",
            $_SESSION["glpiname"] ." ".$LANG['log'][27]);
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["unlock_monitor"])) {
   $computer->check($_POST['id'],'w');

   if (isset($_POST["lockmonitor"]) && count($_POST["lockmonitor"])) {
      foreach ($_POST["lockmonitor"] as $key => $val) {
         OcsServer::deleteInOcsArray($_POST["id"],$key,"import_monitor");
      }
   }
   glpi_header($_SERVER['HTTP_REFERER']);
} else if (isset($_POST["unlock"])) {
   $computer->check($_POST['id'],'w');

   $actions = array("lockprinter"=> "import_printer",
                    "locksoft"   => "import_software",
                    "lockdisk"   => "import_disk",
                    "lockmonitor"=> "import_monitor",
                    "lockperiph" => "import_peripheral",
                    "lockip"     => "import_ip",
                    "lockdevice" => "import_device",
                    "lockfield"  => "computer_update");
   foreach ($actions as $lock => $field) {
      if (isset($_POST[$lock]) && count($_POST[$lock])) {
         foreach ($_POST[$lock] as $key => $val) {
            OcsServer::deleteInOcsArray($_POST["id"],$key,$field);
         }
      }
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["unlock_printer"])) {
   $computer->check($_POST['id'],'w');

   if (isset($_POST["lockprinter"]) && count($_POST["lockprinter"])) {
      foreach ($_POST["lockprinter"] as $key => $val) {
         OcsServer::deleteInOcsArray($_POST["id"],$key,"import_printer");
      }
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["unlock_soft"])) {
   $computer->check($_POST['id'],'w');

   if (isset($_POST["locksoft"]) && count($_POST["locksoft"])) {
      foreach ($_POST["locksoft"] as $key => $val) {
         OcsServer::deleteInOcsArray($_POST["id"],$key,"import_software");
      }
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["unlock_disk"])) {
   $computer->check($_POST['id'],'w');

   if (isset($_POST["lockdisk"]) && count($_POST["lockdisk"])) {
      foreach ($_POST["lockdisk"] as $key => $val) {
         OcsServer::deleteInOcsArray($_POST["id"],$key,"import_disk");
      }
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["unlock_periph"])) {
   $computer->check($_POST['id'],'w');

   if (isset($_POST["lockperiph"]) && count($_POST["lockperiph"])) {
      foreach ($_POST["lockperiph"] as $key => $val) {
         OcsServer::deleteInOcsArray($_POST["id"],$key,"import_peripheral");
      }
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["unlock_ip"])) {
   $computer->check($_POST['id'],'w');

   if (isset($_POST["lockip"]) && count($_POST["lockip"])) {
      foreach ($_POST["lockip"] as $key => $val) {
         OcsServer::deleteInOcsArray($_POST["id"],$key,"import_ip");
      }
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["unlock_field"])) {
   $computer->check($_POST['id'],'w');

   if (isset($_POST["lockfield"]) && count($_POST["lockfield"])) {
      foreach ($_POST["lockfield"] as $key => $val) {
         OcsServer::deleteInOcsArray($_POST["id"],$key,"computer_update");
      }
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["force_ocs_resynch"])) {
   $computer->check($_POST['id'],'w');

   //Get the ocs server id associated with the machine
   $ocsservers_id = OcsServer::getByMachineID($_POST["id"]);

   //Update the computer
   OcsServer::updateComputer($_POST["resynch_id"],$ocsservers_id,1,1);
   glpi_header($_SERVER['HTTP_REFERER']);

} else {//print computer informations
   commonHeader($LANG['Menu'][0],$_SERVER['PHP_SELF'],"inventory","computer");
   //show computer form to add
   $computer->showForm($_GET["id"], array('withtemplate' => $_GET["withtemplate"]));
   commonFooter();
}

?>
