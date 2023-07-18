<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

/**
 *  This class manages the mail settings
 */
class NotificationMailingSetting extends NotificationSetting
{
    public const INHERIT        = -2;   // Inherit from global config
    public const NO_DOCUMENT    = 0;    // No document
    public const ALL_DOCUMENTS  = 1;    // All documents
    public const ONLY_TRIGGERED = 2;    // Only documents related to the triggering event

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

        $attach_documents_values = [
            self::NO_DOCUMENT       => __('No documents'),
            self::ALL_DOCUMENTS     => __('All documents'),
            self::ONLY_TRIGGERED    => __('Only documents related to the triggering event'),
        ];

        $mail_methods = [
            MAIL_MAIL       => __('PHP'),
            MAIL_SMTP       => __('SMTP'),
            MAIL_SMTPS      => __('SMTPS'),
            MAIL_SMTPOAUTH  => __('SMTP+OAUTH'),
        ];
        $mail_method_php = true;
        if (!function_exists('mail')) {
            unset($mail_methods[MAIL_MAIL]);
            $mail_method_php = false;
        }

        $providers_values = [];
        foreach (OauthConfig::getInstance()->getSupportedProviders() as $provider_class) {
            $providers_values[$provider_class] = $provider_class::getName();
        }

        $provider_options = Toolbox::isJSON($CFG_GLPI['smtp_oauth_options'])
                ? json_decode($CFG_GLPI['smtp_oauth_options'], true)
                : [];

        $supported_providers = OauthConfig::getInstance()->getSupportedProviders();

        if ($CFG_GLPI['notifications_mailing']) {
            $options['addbuttons'] = ['test_smtp_send' => __('Send a test email to the administrator')];
        }

        TemplateRenderer::getInstance()->display('pages/setup/notification/mailing_setting.html.twig', [
            'form_url' => Toolbox::getItemTypeFormURL(__CLASS__),
            'attach_documents_values' => $attach_documents_values,
            'mail_methods' => $mail_methods,
            'mail_method_php' => $mail_method_php,
            'authmail_name' => AuthMail::getTypeName(1),
            'providers_values' => $providers_values,
            'provider_options' => $provider_options,
            'supported_providers' => $supported_providers,
            'params' => $options,
        ]);
    }

    public static function getIcon()
    {
        return "far fa-envelope";
    }
}
