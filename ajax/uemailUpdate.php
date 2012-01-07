<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

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

   echo __('Email followup').'&nbsp;';

   $default_notif = true;
   if (isset($_REQUEST['use_notification'])) {
      $default_notif = $_REQUEST['use_notification'];
   }

   if (isset($_REQUEST['alternative_email']) && !empty($_REQUEST['alternative_email'])) {
      $default_email = $_REQUEST['alternative_email'];
   }  
   
   $rand = Dropdown::showYesNo($_REQUEST['field'].'[use_notification]', $default_notif);


   $email_string = '';
   // Only one email
   if (count($emails) ==  1
       && !empty($default_email)
       && NotificationMail::isUserAddressValid($default_email)) {
      $email_string =  $default_email;

   } else if (count($emails) > 1) {
      // Several emails : select in the list
      $email_string = "<select name='".$_REQUEST['field']."[alternative_email]' value=''>";
      $email_string .= "<option value='' selected>$default_email</option>";
      foreach ($emails as $new_email) {
         if ($new_email != $default_email) {
            $email_string .= "<option value='$new_email'>$new_email</option>";
         }
      }
      $email_string .= "</select>";
   } else {
      $email_string = "<input type='text' size='25' name='".$_REQUEST['field']."[alternative_email]'
            value='$default_email'>";
   }
   
   echo '<br>';
   echo sprintf(__('Email: %s'), $email_string);
   
}

Ajax::commonDropdownUpdateItem($_POST);
?>