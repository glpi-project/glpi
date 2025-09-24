<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Mail\SMTP\OauthConfig;

use function Safe\json_decode;

/**
 *  This class manages the mail settings
 */
class NotificationMailingSetting extends NotificationSetting
{
    public static function getTypeName($nb = 0)
    {
        return __('Email notifications configuration');
    }


    public function getEnableLabel()
    {
        return __('Enable email notifications');
    }


    public static function getMode()
    {
        return Notification_NotificationTemplate::MODE_MAIL;
    }

    public function defineTabs($options = [])
    {
        $ong = parent::defineTabs($options);
        $this->addStandardTab(Log::class, $ong, $options);

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
            'name' => __('Characteristics'),
        ];

        $tab[] = [
            'id'            => 1,
            'table'         => $this->getTable(),
            'field'         => 'value',
            'name'          => __('Value'),
            'massiveaction' => false,
        ];

        return $tab;
    }

    public function showFormConfig()
    {
        global $CFG_GLPI;

        $attach_documents_values = [
            self::ATTACH_NO_DOCUMENT       => __('No documents'),
            self::ATTACH_ALL_DOCUMENTS     => __('All documents'),
            self::ATTACH_FROM_TRIGGER_ONLY => __('Only documents related to the item that triggers the event'),
        ];

        $mail_methods = [
            MAIL_MAIL       => __('PHP'),
            MAIL_SMTP       => __('SMTP'),
            MAIL_SMTPTLS    => __('SMTP+TLS'),
            MAIL_SMTPOAUTH  => __('SMTP+OAUTH'),
        ];
        $is_mail_function_available = true;
        if (!function_exists('mail')) {
            unset($mail_methods[MAIL_MAIL]);
            $is_mail_function_available = false;
        }

        $providers_values = [];
        foreach (OauthConfig::getInstance()->getSupportedProviders() as $provider_class) {
            $providers_values[$provider_class] = $provider_class::getName();
        }

        $provider_options = Toolbox::isJSON($CFG_GLPI['smtp_oauth_options'])
                ? json_decode($CFG_GLPI['smtp_oauth_options'], true)
                : [];

        $supported_providers = OauthConfig::getInstance()->getSupportedProviders();

        TemplateRenderer::getInstance()->display('pages/setup/notification/mailing_setting.html.twig', [
            'attach_documents_values' => $attach_documents_values,
            'mail_methods' => $mail_methods,
            'is_mail_function_available' => $is_mail_function_available,
            'providers_values' => $providers_values,
            'provider_options' => $provider_options,
            'supported_providers' => $supported_providers,
        ]);
    }

    public static function getIcon()
    {
        return "ti ti-mail";
    }
}
