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

use Glpi\Event;

include ('../inc/includes.php');

Session::checkRight("reservation", ReservationItem::RESERVEANITEM);

$rr = new Reservation();

if (Session::getCurrentInterface() == "helpdesk") {
   Html::helpHeader(__('Simplified interface'), $_SERVER['PHP_SELF'], $_SESSION["glpiname"]);
} else {
   Html::header(Reservation::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "tools", "reservationitem");
}

if (isset($_POST["update"])) {
   list($begin_year,$begin_month,$begin_day) = explode("-", $_POST['resa']["begin"]);
   Toolbox::manageBeginAndEndPlanDates($_POST['resa']);
   if (Session::haveRight("reservation", UPDATE)
       || (Session::getLoginUserID() === $_POST["users_id"])) {
      $_POST['_target'] = $_SERVER['PHP_SELF'];
      $_POST['_item']   = key($_POST["items"]);
      $_POST['begin']   = $_POST['resa']["begin"];
      $_POST['end']     = $_POST['resa']["end"];
      if ($rr->update($_POST)) {
         Html::redirect($CFG_GLPI["root_doc"]."/front/reservation.php?reservationitems_id=".
                        $_POST['_item']."&mois_courant=$begin_month&annee_courante=$begin_year");
      }
   }

} else if (isset($_POST["purge"])) {
   $reservationitems_id = key($_POST["items"]);
   if ($rr->delete($_POST, 1)) {
      Event::log($_POST["id"], "reservation", 4, "inventory",
                 //TRANS: %s is the user login
                 sprintf(__('%1$s purges the reservation for item %2$s'), $_SESSION["glpiname"],
                         $reservationitems_id));
   }

   list($begin_year,$begin_month,$begin_day) = explode("-", $rr->fields["begin"]);
   Html::redirect($CFG_GLPI["root_doc"]."/front/reservation.php?reservationitems_id=".
                  "$reservationitems_id&mois_courant=$begin_month&annee_courante=$begin_year");

} else if (isset($_POST["add"])) {
   $all_ok              = true;
   $reservationitems_id = 0;
   if (empty($_POST['users_id'])) {
      $_POST['users_id'] = Session::getLoginUserID();
   }
   Toolbox::manageBeginAndEndPlanDates($_POST['resa']);
   $dates_to_add = [];
   list($begin_year,$begin_month,$begin_day) = explode("-", $_POST['resa']["begin"]);
   if (isset($_POST['resa']["end"])) {
      // Compute dates to add.
      $dates_to_add[$_POST['resa']["begin"]] = $_POST['resa']["end"];

      if (isset($_POST['periodicity']) && is_array($_POST['periodicity'])
          && isset($_POST['periodicity']['type']) && !empty($_POST['periodicity']['type'])) {
         // Compute others dates to add.
         $dates_to_add += Reservation::computePeriodicities($_POST['resa']["begin"],
                                                            $_POST['resa']["end"],
                                                            $_POST['periodicity']);
      }
   }
   // Sort dates
   ksort($dates_to_add);
   if (count($dates_to_add)
       && count($_POST['items'])
       && isset($_POST['users_id'])) {

      foreach ($_POST['items'] as $reservationitems_id) {
         $input                        = [];
         $input['reservationitems_id'] = $reservationitems_id;
         $input['comment']             = $_POST['comment'];

         if (count($dates_to_add)) {
            $input['group'] = $rr->getUniqueGroupFor($reservationitems_id);
         }
         foreach ($dates_to_add as $begin => $end) {
            $input['begin']    = $begin;
            $input['end']      = $end;
            $input['users_id'] = $_POST['users_id'];

            if (Session::haveRight("reservation", UPDATE)
                || (Session::getLoginUserID() === $input["users_id"])) {
               unset($rr->fields["id"]);
               if ($newID = $rr->add($input)) {
                  Event::log($newID, "reservation", 4, "inventory",
                           sprintf(__('%1$s adds the reservation %2$s for item %3$s'),
                                   $_SESSION["glpiname"], $newID, $reservationitems_id));
               } else {
                  $all_ok = false;
               }
            }
         }
      }
   } else {
      $all_ok = false;
   }
   if ($all_ok) {
      $toadd = "";
      // Only one reservation : move to correct month
      if (count($_POST['items']) == 1) {
         $toadd  = "?reservationitems_id=$reservationitems_id";
         $toadd .= "&mois_courant=".intval($begin_month);
         $toadd .= "&annee_courante=".intval($begin_year);
      }
      Html::redirect($CFG_GLPI["root_doc"] . "/front/reservation.php$toadd");
   }
   //          $times  = $_POST["periodicity_times"];
   //          $begin  = $_POST["begin"];
   //          list($begin_year,$begin_month,$begin_day) = explode("-",$_POST["begin"]);
   //          $end    = $_POST["end"];
   //          $to_add = 1;
   //
   //          if ($_POST["periodicity"] == "week") {
   //             $to_add = 7;
   //          }
   //          $_POST['_target'] = $_SERVER['PHP_SELF'];
   //
   //          $_POST['_ok'] = true;
   //          if ($times > 1 ) {
   //             $_POST['group'] = $rr->getUniqueGroupFor($reservationitems_id);
   //          }

   //          for ($i=0 ; $i<$times && ($_POST['_ok']) ; $i++) {
   //             $_POST["begin"]  = date('Y-m-d H:i:s', strtotime($begin." +".($i*$to_add)." day"));
   //             $_POST["end"]    = date('Y-m-d H:i:s', strtotime($end." +".($i*$to_add)." day"));
   //
   //             if (Session::haveRight("reservation_central","w")
   //                || (Session::getLoginUserID() === $_POST["users_id"])) {
   //                unset($rr->fields["id"]);
   //                $_POST['_ok'] = $rr->add($_POST);
   //             }
   //          }

   //          if ($_POST['_ok']) {
   //             Event::log($_POST["reservationitems_id"], "reservation", 4, "inventory",
   //                      sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["_ok"]));
   //          } else {
   //             $all_ok = false;
   //          }
   //       }
   //    } else {
   //       $all_ok = false;
   //    }
   //    if ($all_ok) {
   //       $toadd = "";
   //       // Only one reservation : move to correct month
   //       if (count($_POST['items']) == 1) {
   //          $toadd  = "?reservationitems_id=$reservationitems_id";
   //          $toadd .= "&mois_courant=".intval($begin_month);
   //          $toadd .= "&annee_courante=".intval($begin_year);
   //       }
   //       Html::redirect($CFG_GLPI["root_doc"] . "/front/reservation.php$toadd");
   //    }

} else if (isset($_GET["id"])) {
   if (!isset($_GET['begin'])) {
      $_GET['begin'] = date('Y-m-d H:00:00');
   }
   if (empty($_GET["id"])
       && (!isset($_GET['item']) || (count($_GET['item']) == 0 ))) {
      Html::back();
   }
   if (!empty($_GET["id"])
       || (isset($_GET['item']) && isset($_GET['begin']))) {
      $rr->showForm($_GET['id'], $_GET);
   }
}

if (Session::getCurrentInterface() == "helpdesk") {
   Html::helpFooter();
} else {
   Html::footer();
}
