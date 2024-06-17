<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

use Glpi\Mail\SMTP\OauthConfig;

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

    public function defineTabs($options = [])
    {
        $ong = parent::defineTabs($options);
        $this->addStandardTab('Log', $ong, $options);

        return $ong;
    }

    /**
     * Returns the keys of the config entries related to mail notifications.
     * @return string[]
     */
    public static function getRelatedConfigKeys(): array
    {
        return [
            'admin_email',
            'admin_email_name',
            'from_email',
            'from_email_name',
            'replyto_email',
            'replyto_email_name',
            'noreply_email',
            'noreply_email_name',
            'attach_ticket_documents_to_mail',
            'mailing_signature',
            'smtp_mode',
            'smtp_max_retries',
            'smtp_retry_time',
            'smtp_oauth_provider',
            'smtp_oauth_client_id',
            'smtp_oauth_client_secret',
            'smtp_oauth_options',
            'smtp_oauth_refresh_token',
            'smtp_check_certificate',
            'smtp_host',
            'smtp_port',
            'smtp_username',
            'smtp_passwd',
            'smtp_sender',
        ];
    }

    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'   => 'common',
            'name' => __('Characteristics')
        ];

        $tab[] = [
            'id'            => 1,
            'table'         => $this->getTable(),
            'field'         => 'value',
            'name'          => __('Value'),
            'massiveaction' => false
        ];

        return $tab;
    }

    public function showFormConfig($options = [])
    {
        /** @var array $CFG_GLPI */
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
            $rand = mt_rand();

            $out .= "<tr class='tab_bg_2'>";
            $out .= "<td><label for='admin_email'>" . __('Administrator email address') . "</label></td>";
            $out .= "<td><input type='email' class='form-control' name='admin_email' id='admin_email' value='" .
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
            $out .= "<td><input type='email' class='form-control' name='from_email' id='from_email' value='" .
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
            $out .= "<td><input type='email' class='form-control' name='replyto_email' id='replyto_email' value='" .
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
            $out .= "<td><input type='email' class='form-control' name='noreply_email' id='noreply_email' value='" .
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

            $out .= "<td><label for='dropdown_attach_ticket_documents_to_mail$rand'>" . __('Add documents into ticket notifications') . "</label></td><td>";
            $out .= Dropdown::showYesNo(
                "attach_ticket_documents_to_mail",
                $CFG_GLPI["attach_ticket_documents_to_mail"],
                -1,
                ['display' => false, 'rand' => $rand]
            );
            $out .= "</td>";
            $out .= "<td colspan='2'></td></tr>";

            $out .= "<tr class='tab_bg_2'>";
            $out .= "<td><label for='mailing_signature'>" . __('Email signature') . "</label></td>";
            $out .= "<td colspan='3'><textarea class='form-control' rows='3' name='mailing_signature' id='mailing_signature'>" .
                                $CFG_GLPI["mailing_signature"] . "</textarea></td></tr>";

            $out .= "<tr class='tab_bg_2'>";
            $out .= "<td><label for='dropdown_smtp_mode$rand'>" . __('Way of sending emails') . "<label></td><td>";
            $mail_methods = [
                MAIL_MAIL       => __('PHP'),
                MAIL_SMTP       => __('SMTP'),
                MAIL_SMTPSSL    => __('SMTP+SSL'),
                MAIL_SMTPTLS    => __('SMTP+TLS'),
                MAIL_SMTPOAUTH  => __('SMTP+OAUTH'),
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
                    'rand'      => $rand
                ]
            );
            $out .= Html::scriptBlock("
                $(function() {
                    $('[name=smtp_mode]').on('change', function() {
                        const value = $(this).find('option:selected').val();
                        const is_mail  = value === '" . MAIL_MAIL . "';
                        const is_oauth = value === '" . MAIL_SMTPOAUTH . "';

                        $('#smtp_config').toggleClass('starthidden', is_mail);

                        // show/hide elements not related to Oauth
                        $('#dropdown_smtp_check_certificate{$rand}').closest('tr').toggle(!is_oauth);
                        $('label[for=\"smtp_passwd\"]').toggle(!is_oauth);
                        $('#smtp_username').closest('tr').toggle(!is_oauth);

                        // show/hide elements related to Oauth
                        $('#oauth_redirect_alert{$rand}').toggleClass('d-none', !is_oauth);
                        $('#dropdown_smtp_oauth_provider$rand').closest('tr').toggle(is_oauth);
                        $('#smtp_oauth_client_id$rand').closest('tr').toggle(is_oauth);
                        $('#_force_redirect_to_smtp_oauth$rand').closest('tr').toggle(is_oauth);
                        $('[name=smtp_oauth_provider]').trigger('change'); // refresh additional params using dedicated method
                    });
                    $('[name=smtp_mode]').trigger('change');
                });
            ");
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

            $out .= "<table class='tab_cadre_fixe' id='smtp_config'>";
            $out .= "<tr class='tab_bg_1'><th colspan='4'>" . AuthMail::getTypeName(1) . "</th></tr>";

            $out .= "<tr class='tab_bg_2'>";
            $out .= "<td colspan='4'>";
            $out .= "<div id='oauth_redirect_alert{$rand}' class='d-flex alert alert-info'>";
            $out .= "<i class='fas fa-info-circle fa-2x alert-icon'></i>";
            $out .= __('Once the form has been validated, you will be redirected to your supplierʼs authentication page if necessary.');
            $out .= "</div>";
            $out .= "</td>";
            $out .= "</tr>";

            $out .= "<tr class='tab_bg_2'>";
            $out .= "<td><label for='dropdown_smtp_oauth_provider$rand'>" . __('Oauth provider') . "</label></td>";
            $out .= "<td>";
            $providers_values = [];
            foreach (OauthConfig::getInstance()->getSupportedProviders() as $provider_class) {
                $providers_values[$provider_class] = $provider_class::getName();
            }
            $out .= Dropdown::showFromArray(
                'smtp_oauth_provider',
                $providers_values,
                [
                    'display' => false,
                    'display_emptychoice' => true,
                    'rand' => $rand,
                    'value' => $CFG_GLPI['smtp_oauth_provider'],
                ]
            );
            $out .= "</td>";
            $out .= "<td>";
            $out .= _x('oauth', 'Callback URL');
            $out .= "<i class='pointer fa fa-info' title='" . _x('oauth', 'This is the callback URL that you will have to declare in your provider application.') . "'></i>";
            $out .= "</td>";
            $out .= "<td>";
            $out .= "<div class='d-flex align-items-center'>";
            $out .= "<input class='form-control' readonly='readonly' id='_smtp_oauth_callback_url{$rand}' value='{$CFG_GLPI['url_base']}/front/smtp_oauth2_callback.php'>";
            $out .= "<i class='fa fa-paste pointer disclose' onclick='$(\"#_smtp_oauth_callback_url{$rand}\").select(); document.execCommand(\"copy\");'></i>";
            $out .= "</div>";
            $out .= "</td>";
            $out .= "</tr>";

            $out .= "<tr class='tab_bg_2'>";
            $out .= "<td><label for='smtp_oauth_client_id{$rand}'>" . _x('oauth', 'Client ID') . "</label></td>";
            $out .= "<td>";
            $out .= "<input class='form-control' name='smtp_oauth_client_id' id='smtp_oauth_client_id{$rand}' value='{$CFG_GLPI["smtp_oauth_client_id"]}'>";
            $out .= "</td>";
            $out .= "<td><label for='smtp_oauth_client_secret{$rand}'>" . _x('oauth', 'Client secret') . "</label></td>";
            $out .= "<td>";
            $out .= "<input type='password' autocomplete='new-password' class='form-control' name='smtp_oauth_client_secret' id='smtp_oauth_client_secret{$rand}'>";
            $out .= "</td>";
            $out .= "</tr>";

            $provider_options = Toolbox::isJSON($CFG_GLPI['smtp_oauth_options'])
                ? json_decode($CFG_GLPI['smtp_oauth_options'], true)
                : [];
            foreach (OauthConfig::getInstance()->getSupportedProviders() as $provider_class) {
                foreach ($provider_class::getAdditionalParameters() as $param_specs) {
                    $out .= "<tr class='tab_bg_2'>";
                    $out .= "<td>";
                    $out .= "<label for='smtp_oauth_options_{$param_specs['key']}$rand'>{$param_specs['label']}</label>";
                    if (array_key_exists('helper', $param_specs)) {
                        $out .= "<i class='pointer fa fa-info' title='{$param_specs['helper']}'></i>";
                    }
                    $out .= "</td>";
                    $out .= "<td>";
                    $option_value = $CFG_GLPI['smtp_oauth_provider'] === $provider_class
                        ? ($provider_options[$param_specs['key']] ?? $param_specs['default'] ?? '')
                        : '';
                    $out .= "<input class='form-control'
                                    name='smtp_oauth_options[{$param_specs['key']}]'
                                    id='smtp_oauth_options_{$param_specs['key']}$rand'
                                    value='{$option_value}'
                                    data-oauth_additional_parameter='true'
                                    data-oauth_provider='{$provider_class}'
                            >";
                    $out .= "</td><td colspan='2'></td>";
                    $out .= "</tr>";
                }
            }
            // display/hide additionnal fields on provider change
            $out .= Html::scriptBlock(<<<JAVASCRIPT
                $(function() {
                    $('[name=smtp_oauth_provider]').on('change', function() {
                        const value = $(this).find('option:selected').val();
                        $(this.closest('form')).find('[data-oauth_additional_parameter="true"]').each(
                            function (key, field) {
                                const row = $(field).closest('tr');
                                const matches_current_provider = value === $(field).attr('data-oauth_provider');
                                row.toggle(matches_current_provider);
                                row.find('input, select').prop('disabled', !matches_current_provider);
                            }
                        );

                    });
                    $('[name=smtp_oauth_provider]').trigger('change');
                });
JAVASCRIPT
            );

            if ($CFG_GLPI['smtp_oauth_refresh_token'] !== '') {
                $out .= "<tr class='tab_bg_2'>";
                $out .= "<td>";
                $out .= "<label for='_force_redirect_to_smtp_oauth$rand'>" . _x('oauth', 'Force OAuth authentication refresh') . "</label>";
                $out .= "<i class='pointer fa fa-info' title='" . _x('oauth', 'You can use this option to force redirection to the OAuth authentication process. This will trigger generation of a new OAuth token.') . "'></i>";
                $out .= "</td>";
                $out .= "<td>";
                $out .= "<input type='checkbox' name='_force_redirect_to_smtp_oauth' id='_force_redirect_to_smtp_oauth$rand' value='1'>";
                $out .= "</td>";
                $out .= "</tr>";
            }

            $out .= "<tr class='tab_bg_2'>";
            $out .= "<td><label for='dropdown_smtp_check_certificate$rand'>" . __("Check certificate") . "</label></td>";
            $out .= "<td>";
            $out .= Dropdown::showYesNo(
                'smtp_check_certificate',
                $CFG_GLPI["smtp_check_certificate"],
                -1,
                ['display' => false, 'rand' => $rand]
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
