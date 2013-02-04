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
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

$AJAX_INCLUDE = 1;
if (strpos($_SERVER['PHP_SELF'],"uemailUpdate.php")) {
   define('GLPI_ROOT','..');
   include (GLPI_ROOT."/inc/includes.php");
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

Session::checkLoginUser();

if ((isset($_REQUEST['field']) && $_REQUEST["value"]>0)
    || (isset($_REQUEST['allow_email']) && $_REQUEST['allow_email'])) {
   $user          = new User();
   $default_email = "";
   $emails        = array();
   if ($user->getFromDB($_REQUEST["value"])) {
      $default_email = $user->getDefaultEmail();
      $emails        = $user->getAllEmails();
   }
   
   if (isset($_REQUEST['alternative_email']) && !empty($_REQUEST['alternative_email'])
      && empty($default_email)) {
      $default_email = $_REQUEST['alternative_email'];
   }   

   echo $LANG['job'][19].'&nbsp;:&nbsp;';

   $default_notif = true;
   if (isset($_REQUEST['use_notification'])) {
      $default_notif = $_REQUEST['use_notification'];
   }

   $rand = Dropdown::showYesNo($_REQUEST['field'].'[use_notification]', $default_notif);

   echo '<br>'.$LANG['mailing'][118]."&nbsp;:&nbsp;";
   // Only one email
   if (count($emails) ==  1
       && !empty($default_email)
       && NotificationMail::isUserAddressValid($default_email)) {
      echo $default_email;
      // Clean alternative email
      echo "<input type='hidden' size='25' name='".$_REQUEST['field']."[alternative_email]'
            value=''>";

   } else if (count($emails) > 1) {
      // Several emails : select in the list
      echo "<select name='".$_REQUEST['field']."[alternative_email]' value=''>";
      echo "<option value='' selected>$default_email</option>";
      foreach ($emails as $new_email) {
         if ($new_email != $default_email) {
            echo "<option value='$new_email'>$new_email</option>";
         }
      }
      echo "</select>";
   } else {
      echo "<input type='text' size='25' name='".$_REQUEST['field']."[alternative_email]'
            value='$default_email'>";
   }
}

Ajax::commonDropdownUpdateItem($_POST);
?>