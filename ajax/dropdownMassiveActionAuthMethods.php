<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.
 
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

$AJAX_INCLUDE = 1;
include ('../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkRight('user', User::UPDATEAUTHENT);

if ($_POST["authtype"] > 0) {
   $name = 'massiveaction';

   if (isset($_POST['name'])) {
      $name = $_POST['name'];
   }

   switch($_POST["authtype"]) {
      case Auth::DB_GLPI :
         echo "<input type='hidden' name='auths_id' value='0'>";
         break;

      case Auth::LDAP :
      case Auth::EXTERNAL :
         AuthLDAP::dropdown(array('name'      => "auths_id",
                                  'condition' => "`is_active` = 1"));
         break;

      case Auth::MAIL :
         AuthMail::dropdown(array('name'      => "auths_id",
                                  'condition' => "`is_active` = 1"));
         break;
   }

   echo "&nbsp;<input type='submit' name='$name' class='submit' value=\""._sx('button','Post')."\">";
}
?>
