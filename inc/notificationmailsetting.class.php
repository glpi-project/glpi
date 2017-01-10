<?php
/*
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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 *  This class manages the mail settings
 */
class NotificationMailSetting extends CommonDBTM {

   public $table           = 'glpi_configs';

   protected $displaylist  = false;

   static $rightname       = 'config';



   // Temproray hack for this class in 0.84
   static function getTable() {
      return 'glpi_configs';
   }


   static function getTypeName($nb=0) {
      return __('Email followups configuration');
   }


   function defineTabs($options=array()) {

      $ong = array();
      $this->addStandardTab(__CLASS__, $ong, $options);

      return $ong;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      switch ($item->getType()) {
         case __CLASS__ :
            $tabs[1] = __('Setup');
            return $tabs;
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType() == __CLASS__) {
         switch ($tabnum) {
            case 1 :
               $item->showFormMailServerConfig();
               break;
         }
      }
      return true;
   }


   /**
    * Print the mailing config form
    *
    * @param $ID        integer ID of the item
    * @param $options   array
    *     - target filename : where to go when done.
    *     - tabs integer : ID of the tab to display
    *
    * @return Nothing (display)
   **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI;

      if (!Config::canUpdate()) {
         return false;
      }
      if (!$CFG_GLPI['use_mailing']) {
         $options['colspan'] = 1;
      }

      $this->getFromDB($ID);
      return true;
   }


   function showFormMailServerConfig() {
      global $CFG_GLPI;

      echo "<form action='".Toolbox::getItemTypeFormURL(__CLASS__)."' method='post'>";
      echo "<div>";
      echo "<input type='hidden' name='id' value='1'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'><th colspan='4'>"._n('Notification', 'Notifications', Session::getPluralNumber())."</th></tr>";

      echo "<tr class='tab_bg_2'><td>" . __('Enable followup via email') . "</td><td>";
      Dropdown::showYesNo("use_mailing", $CFG_GLPI["use_mailing"]);
      echo "</td>";

      if ($CFG_GLPI['use_mailing']) {
         echo "<td colspan='2'></td></tr>";

         echo "<tr class='tab_bg_2'>";
         echo "<td>" . __('Administrator email') . "</td>";
         echo "<td><input type='text' name='admin_email' size='40' value='".
                    $CFG_GLPI["admin_email"]."'>";
         if (!NotificationMail::isUserAddressValid($CFG_GLPI["admin_email"])) {
             echo "<span class='red'>&nbsp;".__('Invalid email address')."</span>";
         }
         echo "</td>";
         echo "<td >" . __('Administrator name') . "</td>";
         echo "<td><input type='text' name='admin_email_name' size='40' value='" .
                    $CFG_GLPI["admin_email_name"] . "'>";
         echo " </td></tr>";

         echo "<tr class='tab_bg_2'>";
         echo "<td >" . __('Administrator reply-to email (if needed)') . "</td>";
         echo "<td><input type='text' name='admin_reply' size='40' value='" .
                    $CFG_GLPI["admin_reply"] . "'>";
         if (!NotificationMail::isUserAddressValid($CFG_GLPI["admin_reply"])) {
            echo "<span class='red'>&nbsp;".__('Invalid email address')."</span>";
         }
         echo " </td>";
         echo "<td >" . __('Response name (if needed)') . "</td>";
         echo "<td><input type='text' name='admin_reply_name' size='40' value='" .
                    $CFG_GLPI["admin_reply_name"] . "'>";
         echo " </td></tr>";

         echo "<tr class='tab_bg_2'>";
         echo "<td>" . __('Add documents into ticket notifications') . "</td><td>";
         Dropdown::showYesNo("attach_ticket_documents_to_mail",
                             $CFG_GLPI["attach_ticket_documents_to_mail"]);
         echo "</td>";
         echo "<td colspan='2'></td></tr>";

         if (!function_exists('mail')) {
             echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
             echo "<span class='red'>" .
                    __('The PHP mail function is unknown or is not activated on your system.') .
                  "</span><br>". __('The use of a SMTP is needed.') . "</td></tr>";
         }

         echo "<tr class='tab_bg_2'>";
         echo "<td>" . __('Email signature') . "</td>";
         echo "<td colspan='3'><textarea cols='60' rows='3' name='mailing_signature'>".
                                $CFG_GLPI["mailing_signature"]."</textarea></td></tr>";

         echo "<tr class='tab_bg_1'><th colspan='4'>".__('Mail server')."</th></tr>";
         echo "<tr class='tab_bg_2'><td>" . __('Way of sending emails') . "</td><td>";
         $mail_methods = array(MAIL_MAIL    => __('PHP'),
                               MAIL_SMTP    => __('SMTP'),
                               MAIL_SMTPSSL => __('SMTP+SSL'),
                               MAIL_SMTPTLS => __('SMTP+TLS'));
         Dropdown::showFromArray("smtp_mode", $mail_methods,
                                 array('value' => $CFG_GLPI["smtp_mode"]));
         echo "</td>";
         echo "<td >" . __("Check certificate") . "</td>";
         echo "<td>";
         Dropdown::showYesNo('smtp_check_certificate', $CFG_GLPI["smtp_check_certificate"]);
         echo "</td>";
         echo "</tr>";

         echo "<tr class='tab_bg_2'><td >" . __('SMTP host') . "</td>";
         echo "<td><input type='text' name='smtp_host' size='40' value='".$CFG_GLPI["smtp_host"]."'>";
         echo "</td>";
         //TRANS: SMTP port
         echo "<td >" . __('Port') . "</td>";
         echo "<td><input type='text' name='smtp_port' size='5' value='".$CFG_GLPI["smtp_port"]."'>";
         echo "</td>";
         echo "</tr>";

         echo "<tr class='tab_bg_2'>";
         echo "<td >" . __('SMTP login (optional)') . "</td>";
         echo "<td><input type='text' name='smtp_username' size='40' value='" .
                    $CFG_GLPI["smtp_username"] . "'></td>";

         echo "<td >" . __('SMTP password (optional)') . "</td>";
         echo "<td><input type='password' name='smtp_passwd' size='40' value='' autocomplete='off'>";
         echo "<br><input type='checkbox' name='_blank_smtp_passwd'>&nbsp;".__('Clear');

         echo "</td></tr>";

      } else {
         echo "<td colspan='2'></td></tr>";
      }
      $options['candel']     = false;
      if ($CFG_GLPI['use_mailing']) {
         $options['addbuttons'] = array('test_smtp_send' => __('Send a test email to the administrator'));
      }
      $this->showFormButtons($options);

   }

}
