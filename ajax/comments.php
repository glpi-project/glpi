<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

$AJAX_INCLUDE = 1;
include ('../inc/includes.php');

// Send UTF8 Headers
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

// depreciation behavior
if (!isset($_POST["itemtype"]) && isset($_POST['table'])
    && $DB->tableExists($_POST['table'])) {
   Toolbox::deprecated();
   $_POST["itemtype"] = getItemTypeForTable($_POST['table']);
}

if (isset($_POST["itemtype"])
    && isset($_POST["value"])) {
   // Security
   if (!is_subclass_of($_POST["itemtype"], "CommonDBTM")) {
      exit();
   }

   switch ($_POST["itemtype"]) {
      case User::getType() :
         if ($_POST['value'] == 0) {
            $tmpname = [
               'link'    => $CFG_GLPI['root_doc']."/front/user.php",
               'comment' => "",
            ];
         } else {
            if (is_array($_POST["value"])) {
               $comments = [];
               foreach ($_POST["value"] as $users_id) {
                  $username   = getUserName($users_id, 2);
                  $comments[] = $username['comment'] ?? "";
               }
               $tmpname = [
                  'comment' => implode("<br>", $comments),
               ];
               unset($_POST['withlink']);
            } else {
               $tmpname = getUserName($_POST["value"], 2);
            }
         }
         echo $tmpname["comment"];

         if (isset($_POST['withlink'])) {
            echo "<script type='text/javascript' >\n";
            echo Html::jsGetElementbyID($_POST['withlink']).".attr('href', '".$tmpname['link']."');";
            echo "</script>\n";
         }
         break;

      default :
         if ($_POST["value"] > 0) {
            if (!Session::validateIDOR([
               'itemtype'    => $_POST['itemtype'],
               '_idor_token' => $_POST['_idor_token'] ?? ""
            ])) {
               exit();
            }

            $table = getTableForItemType($_POST['itemtype']);
            $tmpname = Dropdown::getDropdownName($table, $_POST["value"], 1);
            if (is_array($tmpname) && isset($tmpname["comment"])) {
                echo $tmpname["comment"];
            }
            if (isset($_POST['withlink'])) {
               echo "<script type='text/javascript' >\n";
               echo Html::jsGetElementbyID($_POST['withlink']).".
                    attr('href', '".$_POST['itemtype']::getFormURLWithID($_POST["value"])."');";
               echo "</script>\n";
            }
         }
   }
}
