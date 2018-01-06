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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 *  This class manages the mail settings
 */
class NotificationMailingSetting extends NotificationSetting {

   static public function getTypeName($nb = 0) {
      return __('Email followups configuration');
   }


   public function getEnableLabel() {
      return __('Enable followups via email');
   }


   static public function getMode() {
      return Notification_NotificationTemplate::MODE_MAIL;
   }


   function showFormConfig($options = []) {
      global $CFG_GLPI;

      if (!isset($options['display'])) {
         $options['display'] = true;
      }

      $out = "<form action='".Toolbox::getItemTypeFormURL(__CLASS__)."' method='post'>";
      $out .= "<div>";
      $out .= "<input type='hidden' name='id' value='1'>";
      $out .= "<table class='tab_cadre_fixe'>";
      $out .= "<tr class='tab_bg_1'><th colspan='4'>"._n('Email notification',
                                                         'Email notifications',
                                                         Session::getPluralNumber())."</th></tr>";

      if ($CFG_GLPI['notifications_mailing']) {
         $out .= "<tr class='tab_bg_2'>";
         $out .= "<td><label for='admin_email'>" . __('Administrator email') . "</label></td>";
         $out .= "<td><input type='text' name='admin_email' id='admin_email' size='40' value='".
                    $CFG_GLPI["admin_email"]."'>";
         if (!NotificationMailing::isUserAddressValid($CFG_GLPI["admin_email"])) {
             $out .= "<br/><span class='red'>&nbsp;".__('Invalid email address')."</span>";
         }
         $out .= "</td>";
         $out .= "<td><label for='admin_email_name'>" . __('Administrator name') . "</label></td>";
         $out .= "<td><input type='text' name='admin_email_name' id='admin_email_name' size='40' value='" .
                    $CFG_GLPI["admin_email_name"] . "'>";
         $out .= " </td></tr>";

         $out .= "<tr class='tab_bg_2'>";
         $out .= "<td><label for='from_email'>" . __('From email') . " <i class='pointer fa fa-info' title='" .
            __s('Address to use in from for sent emails.') . "\n" . __s('If not set, main or entity administrator email will be used.')  . "'></i></label></td>";
         $out .= "<td><input type='text' name='from_email' id='from_email' size='40' value='".
                    $CFG_GLPI["from_email"]."'>";
         if (!empty($CFG_GLPI['from_email']) &&!NotificationMailing::isUserAddressValid($CFG_GLPI["from_email"])) {
             $out .= "<br/><span class='red'>&nbsp;".__('Invalid email address')."</span>";
         }
         $out .= "</td>";
         $out .= "<td><label for='from_email_name'>" . __('From name') . " <i class='pointer fa fa-info' title='" .
            __s('Name to use in from for sent emails.') . "\n" . __s('If not set, main or entity administrator name will be used.')
            ."'><i></label></td>";
         $out .= "<td><input type='text' name='from_email_name' id='from_email_name' size='40' value='" .
                    $CFG_GLPI["from_email_name"] . "'>";
         $out .= " </td></tr>";

         $out .= "<tr class='tab_bg_2'>";
         $out .= "<td><label for='admin_reply'>" . __('Reply-to address') . " <i class='pointer fa fa-info' title='" .
            __s('Optionnal reply to address.') . "\n" . __s('If not set, main administrator email will be used.') . "'></i></label></td>";
         $out .= "<td><input type='text' name='admin_reply' id='admin_reply' size='40' value='" .
                    $CFG_GLPI["admin_reply"] . "'>";
         if (!empty($CFG_GLPI['admin_reply'])
             && !NotificationMailing::isUserAddressValid($CFG_GLPI["admin_reply"])) {
            $out .= "<br/><span class='red'>&nbsp;".__('Invalid email address')."</span>";
         }
         $out .= " </td>";
         $out .= "<td><label for='admin_reply_name'>" . __('Reply-to name') . " <i class='pointer fa fa-info' title='" .
            __s('Optionnal reply to name.') . "\n" . __s('If not set, main administrator name will be used.'). "'></i></label></td>";
         $out .= "<td><input type='text' name='admin_reply_name' id='admin_reply_name' size='40' value='" .
                    $CFG_GLPI["admin_reply_name"] . "'>";
         $out .= " </td></tr>";

         $out .= "<tr class='tab_bg_2'>";

         $attachrand = mt_rand();
         $out .= "<td><label for='dropdown_attach_ticket_documents_to_mail$attachrand'>" . __('Add documents into ticket notifications') . "</label></td><td>";
         $out .= Dropdown::showYesNo(
            "attach_ticket_documents_to_mail",
            $CFG_GLPI["attach_ticket_documents_to_mail"],
            -1,
            ['display' => false, 'rand' => $attachrand]
         );
         $out .= "</td>";
         $out .= "<td colspan='2'></td></tr>";

         $out .= "<tr class='tab_bg_2'>";
         $out .= "<td><label for='mailing_signature'>" . __('Email signature') . "</label></td>";
         $out .= "<td colspan='3'><textarea cols='60' rows='3' name='mailing_signature' id='mailing_signature'>".
                                $CFG_GLPI["mailing_signature"]."</textarea></td></tr>";

         $out .= "<tr class='tab_bg_2'>";
         $methodrand = mt_rand();
         $out .= "<td><label for='dropdown_smtp_mode$methodrand'>" . __('Way of sending emails') . "<label></td><td>";
         $mail_methods = [MAIL_MAIL    => __('PHP'),
                          MAIL_SMTP    => __('SMTP'),
                          MAIL_SMTPSSL => __('SMTP+SSL'),
                          MAIL_SMTPTLS => __('SMTP+TLS')];

         if (!function_exists('mail')) {
             $out .= "<tr class='tab_bg_2'><td class='center' colspan='2'>";
             $out .= "<span class='red'>" .
                    __('The PHP mail function is unknown or is not activated on your system.') .
                  "</span><br>". __('The use of a SMTP is needed.') . "</td></tr>";
             unset($mail_methods[MAIL_MAIL]);
         }

         $out .= Dropdown::showFromArray(
            "smtp_mode",
            $mail_methods,
            [
               'value'     => $CFG_GLPI["smtp_mode"],
               'display'   => false,
               'rand'      => $methodrand
            ]
         );
         $out .= Html::scriptBlock("$(function() {
            console.log($('[name=smtp_mode]'));
            $('[name=smtp_mode]').on('change', function() {
               var _val = $(this).find('option:selected').val();
               if (_val == '" . MAIL_MAIL . "') {
                  $('#smtp_config').addClass('starthidden');
               } else {
                  $('#smtp_config').removeClass('starthidden');
               }
            });
         });");
         $out .= "</td>";

         $out .= "<td><label for='smtp_max_retries'>" . __('Max. delivery retries') . "</label></td>";
         $out .= "<td><input type='text' name='smtp_max_retries' id='smtp_max_retries' size='5' value='" .
                       $CFG_GLPI["smtp_max_retries"] . "'></td>";
         $out .= "</tr>";

         $out .= "<tr class='tab_bg_2'>";
         $out .= "<td><label for='smtp_max_retries'>" . __('Try to deliver again in (minutes)') . "</label></td>";
         $out .= "<td>";
         $out .= Dropdown::showNumber('smtp_retry_time', [
                     'value'    => $CFG_GLPI["smtp_retry_time"],
                     'min'      => 0,
                     'max'      => 60,
                     'step'     => 1,
                     'display'  => false,
                 ]);
         $out .= "</td>";

         $out .= "</table>";

         $out .= "<table class='tab_cadre_fixe";
         if ($CFG_GLPI["smtp_mode"] == MAIL_MAIL) {
            $out .= " starthidden";
         }
         $out .= "' id='smtp_config'>";
         $out .= "<tr class='tab_bg_1'><th colspan='4'>".__('Mail server')."</th></tr>";
         $out .= "<tr class='tab_bg_2'>";
         $certrand = mt_rand();
         $out .= "<td><label for='dropdown_smtp_check_certificate$certrand'>" . __("Check certificate") . "</label></td>";
         $out .= "<td>";
         $out .= Dropdown::showYesNo(
            'smtp_check_certificate',
            $CFG_GLPI["smtp_check_certificate"],
            -1,
            ['display' => false, 'rand' => $certrand]
         );
         $out .= "</td>";
         $out .= "</tr>";

         $out .= "<tr class='tab_bg_2'><td><label for='smtp_host'>" . __('SMTP host') . "</label></td>";
         $out .= "<td><input type='text' name='smtp_host' id='smtp_host' size='40' value='".$CFG_GLPI["smtp_host"]."'>";
         $out.= "</td>";
         //TRANS: SMTP port
         $out .= "<td><label for='smtp_port'>" . __('Port') . "</label></td>";
         $out .= "<td><input type='text' name='smtp_port' id='smtp_port' size='5' value='".$CFG_GLPI["smtp_port"]."'>";
         $out .= "</td>";
         $out .= "</tr>";

         $out .= "<tr class='tab_bg_2'>";
         $out .= "<td><label for='smtp_username'>" . __('SMTP login (optional)') . "</label></td>";
         $out .= "<td><input type='text' name='smtp_username' id='smtp_username' size='40' value='" .
                    $CFG_GLPI["smtp_username"] . "'></td>";

         $out .= "<td><label for='smtp_passwd'>" . __('SMTP password (optional)') . "</label></td>";
         $out .= "<td><input type='password' name='smtp_passwd' id='smtp_passwd' size='40' value='' autocomplete='off'>";
         $out .= "<br><input type='checkbox' name='_blank_smtp_passwd'i id='_blank_smtp_passwd'>&nbsp;<label for='_blank_smtp_passwd'>".__('Clear') . "</label>";

         $out .= "</td></tr>";

         $out .= "<tr class='tab_bg_2'>";
         $out .= "<td><label for='smtp_sender'>" . __('Email sender') . " <i class='pointer fa fa-info' title='" .
            __s('May be required for some mails providers.') . "\n" . __s('If not set, main administrator email will be used.') . "'></i></span></label></td>";
         $out .= "<td colspan='3'</td><input type='text' name='smtp_sender' id='smtp_sender' value='{$CFG_GLPI['smtp_sender']}' /></tr>";

         $out .= "</tr>";
         $out .= "</table>";
      } else {
         $out .= "<tr><td colspan='4'>" . __('Notifications are disabled.')  .
                      "<a href='{$CFG_GLPI['root_doc']}/front/setup.notification.php'>" .
                        __('See configuration')."</a></td></tr>";
         $out .= "</table>";
      }
      $options['candel']     = false;
      if ($CFG_GLPI['notifications_mailing']) {
         $options['addbuttons'] = ['test_smtp_send' => __('Send a test email to the administrator')];
      }
      //do not satisfy display param since showFormButtons() will not :(
      echo $out;
      $this->showFormButtons($options);

   }

}
