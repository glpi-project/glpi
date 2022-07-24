<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

/**
 *  This class manages the mail settings
 */
class NotificationMailingSetting extends NotificationSetting
{
    public static function getTypeName($nb = 0)
    {
        return __('Email followups configuration');
    }


    public function getEnableLabel()
    {
        return __('Enable followups via email');
    }


    public static function getMode()
    {
        return Notification_NotificationTemplate::MODE_MAIL;
    }


    public function showFormConfig($options = [])
    {
        global $CFG_GLPI;

        if (!isset($options['display'])) {
            $options['display'] = true;
        }

        $out = "<form action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "' method='post'>";
        $out .= "<div>";
        $out .= "<input type='hidden' name='id' value='1'>";
        $out .= "<table class='tab_cadre_fixe'>";
        $out .= "<tr class='tab_bg_1'><th colspan='4'>" . _n(
            'Email notification',
            'Email notifications',
            Session::getPluralNumber()
        ) . "</th></tr>";

        if ($CFG_GLPI['notifications_mailing']) {
            $out .= "<tr class='tab_bg_2'>";
            $out .= "<td><label for='admin_email'>" . __('Administrator email address') . "</label></td>";
            $out .= "<td><input type='text' class='form-control' name='admin_email' id='admin_email' value='" .
                    $CFG_GLPI["admin_email"] . "'>";
            if (!NotificationMailing::isUserAddressValid($CFG_GLPI["admin_email"])) {
                $out .= "<br/><span class='red'>&nbsp;" . __('Invalid email address') . "</span>";
            }
            $out .= "</td>";
            $out .= "<td><label for='admin_email_name'>" . __('Administrator name') . "</label></td>";
            $out .= "<td><input type='text' class='form-control' name='admin_email_name' id='admin_email_name' value='" .
                    $CFG_GLPI["admin_email_name"] . "'>";
            $out .= " </td></tr>";

            $out .= "<tr class='tab_bg_2'>";
            $out .= "<td><label for='from_email'>" . __('Email sender address') . " <i class='pointer fa fa-info' title='" .
            __s('Address to use in from for sent emails.') . "\n" . __s('If not set, main or entity administrator email address will be used.')  . "'></i></label></td>";
            $out .= "<td><input type='text' class='form-control' name='from_email' id='from_email' value='" .
                    $CFG_GLPI["from_email"] . "'>";
            if (!empty($CFG_GLPI['from_email']) && !NotificationMailing::isUserAddressValid($CFG_GLPI["from_email"])) {
                $out .= "<br/><span class='red'>&nbsp;" . __('Invalid email address') . "</span>";
            }
            $out .= "</td>";
            $out .= "<td><label for='from_email_name'>" . __('Email sender name') . " <i class='pointer fa fa-info' title='" .
            __s('Name to use in from for sent emails.') . "\n" . __s('If not set, main or entity administrator email name will be used.')
            . "'><i></label></td>";
            $out .= "<td><input type='text' class='form-control' name='from_email_name' id='from_email_name' value='" .
                    $CFG_GLPI["from_email_name"] . "'>";
            $out .= " </td></tr>";

            $out .= "<tr class='tab_bg_2'>";
            $out .= "<td><label for='replyto_email'>" . __('Reply-To address') . " <i class='pointer fa fa-info' title='" .
            __s('Optionnal reply to address.') . "\n" . __s('If not set, main or entity administrator email address will be used.') . "'></i></label></td>";
            $out .= "<td><input type='text' class='form-control' name='replyto_email' id='replyto_email' value='" .
                    $CFG_GLPI["replyto_email"] . "'>";
            if (
                !empty($CFG_GLPI['replyto_email'])
                && !NotificationMailing::isUserAddressValid($CFG_GLPI["replyto_email"])
            ) {
                $out .= "<br/><span class='red'>&nbsp;" . __('Invalid email address') . "</span>";
            }
            $out .= " </td>";
            $out .= "<td><label for='replyto_email_name'>" . __('Reply-To name') . " <i class='pointer fa fa-info' title='" .
            __s('Optionnal reply to name.') . "\n" . __s('If not set, main or entity administrator name will be used.') . "'></i></label></td>";
            $out .= "<td><input type='text' class='form-control' name='replyto_email_name' id='replyto_email_name' value='" .
                    $CFG_GLPI["replyto_email_name"] . "'>";
            $out .= " </td></tr>";

            $out .= "<tr class='tab_bg_2'>";
            $out .= "<td><label for='noreply_email'>" . __('No-Reply address') . " <i class='pointer fa fa-info' title='" .
            __s('Optionnal No-Reply address.') . "\n" . __s('If set, it will be used for notifications that doesn\'t expect a reply.') . "'></i></label></td>";
            $out .= "<td><input type='text' class='form-control' name='noreply_email' id='noreply_email' value='" .
                    $CFG_GLPI["noreply_email"] . "'>";
            if (
                !empty($CFG_GLPI['noreply_email'])
                && !NotificationMailing::isUserAddressValid($CFG_GLPI["noreply_email"])
            ) {
                $out .= "<br/><span class='red'>&nbsp;" . __('Invalid email address') . "</span>";
            }
            $out .= " </td>";
            $out .= "<td><label for='noreply_email_name'>" . __('No-Reply name') . " <i class='pointer fa fa-info' title='" .
            __s('Optionnal No-Reply name.') . "\n" . __s('If not set, main or entity administrator name will be used.') . "'></i></label></td>";
            $out .= "<td><input type='text' class='form-control' name='noreply_email_name' id='noreply_email_name' value='" .
                    $CFG_GLPI["noreply_email_name"] . "'>";
            $out .= " </td></tr>";
            $out .= "</tr>";

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
            $out .= "<td colspan='3'><textarea class='form-control' rows='3' name='mailing_signature' id='mailing_signature'>" .
                                $CFG_GLPI["mailing_signature"] . "</textarea></td></tr>";

            $out .= "<tr class='tab_bg_2'>";
            $methodrand = mt_rand();
            $out .= "<td><label for='dropdown_smtp_mode$methodrand'>" . __('Way of sending emails') . "<label></td><td>";
            $mail_methods = [MAIL_MAIL    => __('PHP'),
                MAIL_SMTP    => __('SMTP'),
                MAIL_SMTPSSL => __('SMTP+SSL'),
                MAIL_SMTPTLS => __('SMTP+TLS')
            ];

            if (!function_exists('mail')) {
                $out .= "<tr class='tab_bg_2'><td class='center' colspan='2'>";
                $out .= "<span class='red'>" .
                    __('The PHP mail function is unknown or is not activated on your system.') .
                  "</span><br>" . __('The use of a SMTP is needed.') . "</td></tr>";
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
            $out .= "<td><input type='number' class='form-control' name='smtp_max_retries' id='smtp_max_retries' size='5' value='" .
                       $CFG_GLPI["smtp_max_retries"] . "'></td>";
            $out .= "</tr>";

            $out .= "<tr class='tab_bg_2'>";
            $out .= "<td><label for='smtp_max_retries'>" . __('Try to deliver again in (minutes)') . "</label></td>";

            $out .= "<td><input type='number' class='form-control' name='smtp_retry_time' id='smtp_retry_time' size='5' min='0' max='60' step='1' value='" .
            $CFG_GLPI["smtp_retry_time"] . "'></td>";
            $out .= "<td colspan='2'></td>";

            $out .= "</table>";

            $out .= "<table class='tab_cadre_fixe";
            if ($CFG_GLPI["smtp_mode"] == MAIL_MAIL) {
                $out .= " starthidden";
            }
            $out .= "' id='smtp_config'>";
            $out .= "<tr class='tab_bg_1'><th colspan='4'>" . AuthMail::getTypeName(1) . "</th></tr>";
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
            $out .= "</td><td colspan='2'></td>";
            $out .= "</tr>";

            $out .= "<tr class='tab_bg_2'><td><label for='smtp_host'>" . __('SMTP host') . "</label></td>";
            $out .= "<td><input type='text' class='form-control' name='smtp_host' id='smtp_host' value='" . $CFG_GLPI["smtp_host"] . "'>";
            $out .= "</td>";
           //TRANS: SMTP port
            $out .= "<td><label for='smtp_port'>" . _n('Port', 'Ports', 1) . "</label></td>";
            $out .= "<td><input type='number' class='form-control' name='smtp_port' id='smtp_port' size='5' value='" . $CFG_GLPI["smtp_port"] . "'>";
            $out .= "</td>";
            $out .= "</tr>";

            $out .= "<tr class='tab_bg_2'>";
            $out .= "<td><label for='smtp_username'>" . __('SMTP login (optional)') . "</label></td>";
            $out .= "<td><input type='text' class='form-control' name='smtp_username' id='smtp_username' value='" .
                    $CFG_GLPI["smtp_username"] . "'></td>";

            $out .= "<td><label for='smtp_passwd'>" . __('SMTP password (optional)') . "</label></td>";
            $out .= "<td><input type='password' class='form-control' name='smtp_passwd' id='smtp_passwd' value='' autocomplete='new-password'>";
            $out .= "<br><input type='checkbox' name='_blank_smtp_passwd'i id='_blank_smtp_passwd'>&nbsp;<label for='_blank_smtp_passwd'>" . __('Clear') . "</label>";

            $out .= "</td></tr>";

            $out .= "<tr class='tab_bg_2'>";
            $out .= "<td><label for='smtp_sender'>" . __('Email sender') . " <i class='pointer fa fa-info' title='" .
              __s('May be required for some mails providers.') . "\n" . __s('If not set, main administrator email will be used.') . "'></i></span></label></td>";
            $out .= "<td colspan='3'</td><input type='text' class='form-control' name='smtp_sender' id='smtp_sender' value='{$CFG_GLPI['smtp_sender']}' /></tr>";

            $out .= "</tr>";
            $out .= "</table>";
        } else {
            $out .= "<tr><td colspan='4'>" . __('Notifications are disabled.')  .
                     "<a href='{$CFG_GLPI['root_doc']}/front/setup.notification.php'>" .
                       __('See configuration') . "</a></td></tr>";
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

    public static function getIcon()
    {
        return "far fa-envelope";
    }
}
