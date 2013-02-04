<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
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
if (!isset($_GET["itemtype"])) {
   $_GET["itemtype"] = "";
}
if (!isset($_GET["items_id"])) {
   $_GET["items_id"] = "";
}
if (!isset($_GET["modify"])) {
   $_GET["modify"] = "";
}
if (!isset($_GET["delete"])) {
   $_GET["delete"] = "";
}
if (!isset($_GET["addtofaq"])) {
   $_GET["addtofaq"] = "";
}
if (!isset($_GET["removefromfaq"])) {
   $_GET["removefromfaq"] = "";
}

$kb = new KnowbaseItem();

if ($_GET["id"] == "new") {
   // on affiche le formulaire de saisie de l'item
   $kb->check(-1,'w');

   Html::header($LANG['title'][5],$_SERVER['PHP_SELF'],"utils","knowbase");
   $available_options = array('itemtype', 'items_id');
   $options           = array();
   foreach ($available_options as $key) {
      if (isset($_GET[$key])) {
         $options[$key] = $_GET[$key];
      }
   }
   $kb->showForm("",$options);
   Html::footer();

} else if (isset($_POST["add"])) {
   // ajoute un item dans la base de connaisssances
   $kb->check(-1,'w',$_POST);

   $newID = $kb->add($_POST);
   Event::log($newID, "knowbaseitem", 5, "tools", $_SESSION["glpiname"]." ".$LANG['log'][20]);
   Html::redirect($CFG_GLPI["root_doc"]."/front/knowbaseitem.php");

} else if (isset($_POST["update"])) {
   // actualiser  un item dans la base de connaissances
   $kb->check($_POST["id"],'w');

   $kb->update($_POST);
   Event::log($_POST["id"], "knowbaseitem", 5, "tools", $_SESSION["glpiname"]." ".$LANG['log'][21]);
   Html::redirect($CFG_GLPI["root_doc"]."/front/knowbaseitem.form.php?id=".$_POST['id']);

} else if (isset($_GET["id"]) && strcmp($_GET["modify"],"yes") == 0) {
   // modifier un item dans la base de connaissance
   $kb->check($_GET["id"],'r');

   Html::header($LANG['title'][5],$_SERVER['PHP_SELF'],"utils","knowbase");
   $kb->showForm($_GET["id"]);
   Html::footer();

} else if (isset($_GET["id"]) && strcmp($_GET["delete"],"yes") == 0) {
   // effacer un item dans la base de connaissances
   $kb->check($_GET["id"],'w');

   $kb->delete($_GET);
   Event::log($_GET["id"], "knowbaseitem", 5, "tools", $_SESSION["glpiname"]." ".$LANG['log'][22]);
   $kb->redirectToList();

} else if (isset($_GET["id"]) && strcmp($_GET["addtofaq"],"yes") == 0) {
   // ajouter  un item dans la faq
   $kb->check($_GET["id"],'w');
   $kb->addToFaq();
   Html::back();

} else if (isset($_GET["id"]) && strcmp($_GET["removefromfaq"],"yes") == 0) {
   // retirer  un item de la faq
   $kb->check($_GET["id"],'w');
   $kb->removeFromFaq($_GET["id"]);
   Html::back();

} else if (isset($_POST["addvisibility"])) {
   if (isset($_POST["_type"]) && !empty($_POST["_type"])
       && isset($_POST["knowbaseitems_id"]) && $_POST["knowbaseitems_id"]) {
      $item = NULL;
      switch ($_POST["_type"]) {
         case 'User' :
            if (isset($_POST['users_id']) && $_POST['users_id']) {
               $item = new KnowbaseItem_User();
            }
            break;

         case 'Group' :
            if (isset($_POST['groups_id']) && $_POST['groups_id']) {
               $item = new Group_KnowbaseItem();
            }
            break;

         case 'Profile' :
            if (isset($_POST['profiles_id']) && $_POST['profiles_id']) {
               $item = new KnowbaseItem_Profile();
            }
            break;

         case 'Entity' :
            $item = new Entity_KnowbaseItem();
            break;
      }
      if (!is_null($item)) {
         $item->add($_POST);
         Event::log($_POST["knowbaseitems_id"], "knowbaseitem", 4, "tools",
                    $_SESSION["glpiname"]." ".$LANG['log'][68]);
      }
   }
   Html::back();

}  else if (isset($_POST["deletevisibility"])) {
   if (isset($_POST["group"]) && count($_POST["group"])) {
      $item = new Group_KnowbaseItem();
      foreach ($_POST["group"] as $key => $val) {
         if ($item->can($key,'w')) {
            $item->delete(array('id' => $key));
         }
      }
   }

   if (isset($_POST["user"]) && count($_POST["user"])) {
      $item = new KnowbaseItem_User();
      foreach ($_POST["user"] as $key => $val) {
         if ($item->can($key,'w')) {
            $item->delete(array('id' => $key));
         }
      }
   }

   if (isset($_POST["entity"]) && count($_POST["entity"])) {
      $item = new Entity_KnowbaseItem();
      foreach ($_POST["entity"] as $key => $val) {
         if ($item->can($key,'w')) {
            $item->delete(array('id' => $key));
         }
      }
   }

   if (isset($_POST["profile"]) && count($_POST["profile"])) {
      $item = new KnowbaseItem_Profile();
      foreach ($_POST["profile"] as $key => $val) {
         if ($item->can($key,'w')) {
            $item->delete(array('id' => $key));
         }
      }
   }
   Event::log($_POST["knowbaseitems_id"], "knowbaseitem", 4, "tools",
              $_SESSION["glpiname"]." ".$LANG['log'][67]);
   Html::back();

} else if (empty($_GET["id"])) {
   // No id or no tickets id to create from solution
   Html::redirect($CFG_GLPI["root_doc"]."/front/knowbaseitem.php");

} else {
   // Affiche un item de la base de connaissances
   $kb->check($_GET["id"],'r');

   Html::header($LANG['title'][5],$_SERVER['PHP_SELF'],"utils","knowbase");

   $kb->showFull(true);

   Html::footer();
}
?>