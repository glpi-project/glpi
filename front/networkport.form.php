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


$np  = new NetworkPort();
$nn  = new NetworkPort_NetworkPort();
$npv = new NetworkPort_Vlan();

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

if (isset($_POST["add"])) {

   // Is a preselected mac adress selected ?
   if (isset($_POST['pre_mac'])) {
      if (!empty($_POST['pre_mac'])) {
         $_POST['mac'] = $_POST['pre_mac'];
      }
      unset($_POST['pre_mac']);
   }

   if (!isset($_POST["several"])) {
      $np->check(-1,'w',$_POST);
      $np->add($_POST);
      Event::log(0, "networkport", 5, "inventory", $_SESSION["glpiname"]." ".$LANG['log'][70]);
      glpi_header($_SERVER['HTTP_REFERER']);

   } else {
      checkRight("networking","w");

      $input = $_POST;
      unset($input['several']);
      unset($input['from_logical_number']);
      unset($input['to_logical_number']);

      for ($i=$_POST["from_logical_number"] ; $i<=$_POST["to_logical_number"] ; $i++) {
         $add = "";
         if ($i < 10) {
            $add = "0";
         }
         $input["logical_number"] = $i;
         $input["name"] = $_POST["name"].$add.$i;
         unset($np->fields["id"]);

         if ($np->can(-1,'w',$input)) {
            $np->add($input);
         }
      }
      Event::log(0, "networkport", 5, "inventory", $_SESSION["glpiname"]."  ".
                 ($_POST["to_logical_number"]-$_POST["from_logical_number"]+1)."  ".$LANG['log'][71]);
      glpi_header($_SERVER['HTTP_REFERER']);
   }

} else if (isset($_POST["delete"])) {
   $np->check($_POST['id'],'d');
   $np->delete($_POST);
   Event::log($_POST['id'], "networkport", 5, "inventory",
              $_SESSION["glpiname"]." ".$LANG['log'][73]);

   if (class_exists($np->fields['itemtype'])) {
      $item = new $np->fields['itemtype']();
      glpi_header($item->getFormURL().'?id='.$np->fields['items_id']);
   }
   glpi_header($CFG_GLPI["root_doc"]."/front/central.php");

} else if (isset($_POST["delete_several"])) {
   checkRight("networking","w");

   if (isset($_POST["del_port"]) && count($_POST["del_port"])) {
      foreach ($_POST["del_port"] as $port_id => $val) {
         if ($np->can($port_id,'d')) {
            $np->delete(array("id" => $port_id));
         }
      }
   }
   Event::log(0, "networkport", 5, "inventory", $_SESSION["glpiname"]." ".$LANG['log'][74]);
   glpi_header($_SERVER['HTTP_REFERER']);

}
// Interest of this massive action ?
/*else if(isset($_POST["move"])) {
   checkRight("networking","w");
   if (isset($_POST["del_port"]) && count($_POST["del_port"])) {
      foreach ($_POST["del_port"] as $port_id => $val) {
         if ($np->getFromDB($port_id)) {
            $input = array();
            $input['id'] = $port_id;
            $input['items_id'] = $_POST["device"];
            if ($np->can($input['id'],'w')) {
               $np->update($input);
            }
         }
      }
   }
   Event::log(0, "networkport", 5, "inventory", $_SESSION["glpiname"]."  ".$LANG['log'][75]);
   glpi_header($_SERVER['HTTP_REFERER']);

}*/ else if (isset($_POST["update"])) {
   $np->check($_POST['id'],'w');

   $np->update($_POST);
   Event::log($_POST["id"], "networkport", 4, "inventory",
              $_SESSION["glpiname"]." ".$LANG['log'][21]);
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["connect"])) {
   if (isset($_POST["dport"]) && count($_POST["dport"])) {

      foreach ($_POST["dport"] as $sport => $dport) {
         if ($sport && $dport) {
            $nn->add(array('networkports_id_1' => $sport,
                           'networkports_id_2' => $dport));
         }
      }
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_GET["disconnect"])) {
   $nn->check($_GET['id'],'d');

   if (isset($_GET["id"])) {
      $nn->delete($_GET);
      $fin = "";
      if (isset($_GET["sport"])) {
         $fin = "?sport=".$_GET["sport"];
      }
      glpi_header($_SERVER['HTTP_REFERER'].$fin);
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["assign_vlan_several"])) {
   checkRight("networking","w");
   if ($_POST["vlans_id"] >0) {

      if (isset($_POST["del_port"]) && count($_POST["del_port"])) {
         foreach ($_POST["del_port"] as $port_id => $val) {
            $npv->assignVlan($port_id,$_POST["vlans_id"]);
         }
      }
      Event::log(0, "networkport", 5, "inventory", $_SESSION["glpiname"]."  ".$LANG['log'][78]);
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST['assign_vlan'])) {
   $npv->check(-1,'w',$_POST);

   if (isset($_POST["vlans_id"]) && $_POST["vlans_id"] >0) {
      $npv->assignVlan($_POST["networkports_id"],$_POST["vlans_id"]);
      Event::log(0, "networkport", 5, "inventory", $_SESSION["glpiname"]." ".$LANG['log'][77]);
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_POST["unassign_vlan_several"])) {
   checkRight("networking","w");

   if ($_POST["vlans_id"] >0) {
      if (isset($_POST["del_port"]) && count($_POST["del_port"])) {
         foreach ($_POST["del_port"] as $port_id => $val) {
            // Check port write access
            if ($np->can($port_id,'w')) {
               $npv->unassignVlan($port_id,$_POST["vlans_id"]);
            }
         }
      }
      Event::log(0, "networkport", 5, "inventory", $_SESSION["glpiname"]."  ".$LANG['log'][80]);
   }
   glpi_header($_SERVER['HTTP_REFERER']);

} else if (isset($_GET['unassign_vlan'])) {
   $npv->check($_GET['id'],'d');

   $npv->unassignVlanbyID($_GET['id']);
   Event::log(0, "networkport", 5, "inventory", $_SESSION["glpiname"]."  ".$LANG['log'][79]);
   glpi_header($_SERVER['HTTP_REFERER']);

} else {
   if (empty($_GET["items_id"])) {
      $_GET["items_id"] = "";
   }
   if (empty($_GET["itemtype"])) {
      $_GET["itemtype"] = "";
   }
   if (empty($_GET["several"])) {
      $_GET["several"] = "";
   }
   checkRight("networking","w");
   commonHeader($LANG['title'][6],$_SERVER['PHP_SELF'],"inventory");

//   NetworkPort::showNetportForm($_SERVER['PHP_SELF'],$_GET["id"],$_GET["items_id"],$_GET["itemtype"],$_GET["several"]);
   $np->showForm($_GET["id"], $_GET);
   commonFooter();
}

?>