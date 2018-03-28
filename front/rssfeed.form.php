<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

/**
 * @since 0.84
 */

use Glpi\Event;

include ('../inc/includes.php');

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}
$rssfeed = new RSSFeed();
Session::checkLoginUser();

if (isset($_POST["add"])) {
   $rssfeed->check(-1, CREATE, $_POST);

   $newID = $rssfeed->add($_POST);
   Event::log($newID, "rssfeed", 4, "tools",
              sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"],
                      $rssfeed->fields["name"]));
   Html::redirect($rssfeed->getFormURLWithID($newID));

} else if (isset($_POST["purge"])) {
   $rssfeed->check($_POST["id"], PURGE);
   $rssfeed->delete($_POST, 1);
   Event::log($_POST["id"], "rssfeed", 4, "tools",
              //TRANS: %s is the user login
              sprintf(__('%s purges an item'), $_SESSION["glpiname"]));
   $rssfeed->redirectToList();

} else if (isset($_POST["update"])) {
   $rssfeed->check($_POST["id"], UPDATE);   // Right to update the rssfeed

   $rssfeed->update($_POST);
   Event::log($_POST["id"], "rssfeed", 4, "tools",
              //TRANS: %s is the user login
              sprintf(__('%s updates an item'), $_SESSION["glpiname"]));
   Html::back();

} else if (isset($_POST["addvisibility"])) {
   if (isset($_POST["_type"]) && !empty($_POST["_type"])
       && isset($_POST["rssfeeds_id"]) && $_POST["rssfeeds_id"]) {
      $item = null;
      switch ($_POST["_type"]) {
         case 'User' :
            if (isset($_POST['users_id']) && $_POST['users_id']) {
               $item = new RSSFeed_User();
            }
            break;

         case 'Group' :
            if (isset($_POST['groups_id']) && $_POST['groups_id']) {
               $item = new Group_RSSFeed();
            }
            break;

         case 'Profile' :
            if (isset($_POST['profiles_id']) && $_POST['profiles_id']) {
               $item = new Profile_RSSFeed();
            }
            break;

         case 'Entity' :
            $item = new Entity_RSSFeed();
            break;
      }
      if (!is_null($item)) {
         $item->add($_POST);
         Event::log($_POST["rssfeeds_id"], "rssfeed", 4, "tools",
                    //TRANS: %s is the user login
                    sprintf(__('%s adds a target'), $_SESSION["glpiname"]));
      }
   }
   Html::back();

} else {
   if (Session::getCurrentInterface() == "helpdesk") {
      Html::helpHeader(RSSFeed::getTypeName(Session::getPluralNumber()), '', $_SESSION["glpiname"]);
   } else {
      Html::header(RSSFeed::getTypeName(Session::getPluralNumber()), '', "tools", "rssfeed");
   }

   $rssfeed->display(['id' => $_GET["id"]]);

   if (Session::getCurrentInterface() == "helpdesk") {
      Html::helpFooter();
   } else {
      Html::footer();
   }
}
