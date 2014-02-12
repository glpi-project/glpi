<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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

/** @file
* @brief
*/

include ('../inc/includes.php');

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}
if (!isset($_GET["item_itemtype"])) {
   $_GET["item_itemtype"] = "";
}
if (!isset($_GET["item_items_id"])) {
   $_GET["item_items_id"] = "";
}
if (!isset($_GET["modify"])) {
   $_GET["modify"] = "";
}

$kb = new KnowbaseItem();

if (isset($_POST["add"])) {
   // ajoute un item dans la base de connaisssances
   $kb->check(-1,'w',$_POST);

   $newID = $kb->add($_POST);
   Event::log($newID, "knowbaseitem", 5, "tools",
              sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $newID));
   Html::redirect($CFG_GLPI["root_doc"]."/front/knowbaseitem.php");

} else if (isset($_POST["update"])) {
   // actualiser  un item dans la base de connaissances
   $kb->check($_POST["id"],'w');

   $kb->update($_POST);
   Event::log($_POST["id"], "knowbaseitem", 5, "tools",
              //TRANS: %s is the user login
              sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
   Html::redirect($CFG_GLPI["root_doc"]."/front/knowbaseitem.form.php?id=".$_POST['id']);

} else if (isset($_POST["delete"])) {
   // effacer un item dans la base de connaissances
   $kb->check($_POST["id"],'d');

   $kb->delete($_POST);
   Event::log($_POST["id"], "knowbaseitem", 5, "tools",
              //TRANS: %s is the user login
              sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
   $kb->redirectToList();
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
                    //TRANS: %s is the user login
                    sprintf(__('%s adds a target'), $_SESSION["glpiname"]));
      }
   }
   Html::back();

} else if ($_GET["id"] == "new") {
   // on affiche le formulaire de saisie de l'item
   $kb->check(-1,'w');

   Html::header(KnowbaseItem::getTypeName(1), $_SERVER['PHP_SELF'], "utils", "knowbase");
   $available_options = array('item_itemtype', 'item_items_id');
   $options           = array();
   foreach ($available_options as $key) {
      if (isset($_GET[$key])) {
         $options[$key] = $_GET[$key];
      }
   }
   $kb->showForm("",$options);
   Html::footer();

} else if (empty($_GET["id"])) {
   // No id or no tickets id to create from solution
   Html::redirect($CFG_GLPI["root_doc"]."/front/knowbaseitem.php");

} else if (isset($_GET["id"])
           && ($_GET["modify"] == "yes")) {
   // modifier un item dans la base de connaissance
   $kb->check($_GET["id"],'r');

   Html::header(KnowbaseItem::getTypeName(1), $_SERVER['PHP_SELF'], "utils", "knowbase");
   $kb->showForm($_GET["id"]);
   Html::footer();

} else {
   // Affiche un item de la base de connaissances
   $kb->check($_GET["id"],'r');

   if (Session::getLoginUserID()) {
      if ($_SESSION["glpiactiveprofile"]["interface"] == "central") {
         Html::header(KnowbaseItem::getTypeName(1), $_SERVER['PHP_SELF'], "utils", "knowbase");
      } else {
         Html::helpHeader(__('FAQ'), $_SERVER['PHP_SELF']);
      }
      Html::helpHeader(__('FAQ'), $_SERVER['PHP_SELF'], $_SESSION["glpiname"]);
   } else {
      $_SESSION["glpilanguage"] = $CFG_GLPI['language'];
      // Anonymous FAQ
      Html::simpleHeader(__('FAQ'), array(__('Authentication') => $CFG_GLPI['root_doc'].'/',
                                          __('FAQ')            => $CFG_GLPI['root_doc'].'/front/helpdesk.faq.php'));
   }

   $kb->showFull(true);
   
   if (Session::getLoginUserID()) {
      if ($_SESSION["glpiactiveprofile"]["interface"] == "central") {
         Html::footer();
      } else {
         Html::helpFooter();
      }
   } else {
      Html::helpFooter();
   }
}
?>