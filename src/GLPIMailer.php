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
use Glpi\Mail\SMTP\OAuthTokenProvider;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

/** GLPIPhpMailer class
 *
 * @since 0.85
 **/
class GLPIMailer extends PHPMailer
{
    /**
     * Constructor
     *
     **/
    public function __construct()
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $this->WordWrap           = 80;

        $this->CharSet            = "utf-8";

        $this->Encoding           = self::ENCODING_QUOTED_PRINTABLE;

       // Comes from config
        $this->SetLanguage("en", Config::getLibraryDir("PHPMailer") . "/language/");

        if ($CFG_GLPI['smtp_mode'] != MAIL_MAIL) {
            $this->Mailer = "smtp";
            $this->Host   = $CFG_GLPI['smtp_host'] . ':' . $CFG_GLPI['smtp_port'];

            if ($CFG_GLPI['smtp_mode'] == MAIL_SMTPOAUTH) {
                $this->SMTPSecure = 'tls';
                $this->SMTPAuth = true;
                $this->AuthType = 'XOAUTH2';
                $provider = OauthConfig::getInstance()->getSmtpOauthProvider();
                if ($provider !== null) {
                    $client_id     = $CFG_GLPI['smtp_oauth_client_id'];
                    $client_secret = (new GLPIKey())->decrypt($CFG_GLPI['smtp_oauth_client_secret']);
                    $refresh_token = (new GLPIKey())->decrypt($CFG_GLPI['smtp_oauth_refresh_token']);

                    $this->setOAuth(
                        new OAuthTokenProvider(
                            [
                                'provider'     => $provider,
                                'clientId'     => $client_id,
                                'clientSecret' => $client_secret,
                                'refreshToken' => $refresh_token,
                                'userName'     => $CFG_GLPI['smtp_username'],
                            ]
                        )
                    );
                }
            } else {
                if ($CFG_GLPI['smtp_username'] != '') {
                    $this->SMTPAuth = true;
                    $this->Username = $CFG_GLPI['smtp_username'];
                    $this->Password = (new GLPIKey())->decrypt($CFG_GLPI['smtp_passwd']);
                }

                if ($CFG_GLPI['smtp_mode'] == MAIL_SMTPSSL) {
                    $this->SMTPSecure = "ssl";
                } else if ($CFG_GLPI['smtp_mode'] == MAIL_SMTPTLS) {
                    $this->SMTPSecure = "tls";
                } else {
                   // Don't automatically enable encryption if the GLPI config doesn't specify it
                    $this->SMTPAutoTLS = false;
                }

                if (!$CFG_GLPI['smtp_check_certificate']) {
                    $this->SMTPOptions = ['ssl' => ['verify_peer'       => false,
                        'verify_peer_name'  => false,
                        'allow_self_signed' => true
                    ]
                    ];
                }
            }
            if ($CFG_GLPI['smtp_sender'] != '') {
                $this->Sender = $CFG_GLPI['smtp_sender'];
            }
        }

        if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
            $this->SMTPDebug = SMTP::DEBUG_CONNECTION;
            $this->Debugoutput = function ($message, $level) {
                Toolbox::logInFile(
                    'mail-debug',
                    "$level - $message\n"
                );
            };
        }
    }

    public function smtpConnect($options = null)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $result = parent::smtpConnect($options);

        if (
            $this->oauth instanceof OAuthTokenProvider
            && $result === true
            && ($refresh_token = $this->oauth->getOauthToken()->getRefreshToken() ?? null) !== null
            && $refresh_token !== (new GLPIKey())->decrypt($CFG_GLPI['smtp_oauth_refresh_token'])
        ) {
            // The refresh token may be refreshed itself.
            // Be sure to always store any new refresh token.
            Config::setConfigurationValues(
                'core',
                [
                    'smtp_oauth_refresh_token' => $refresh_token,
                ]
            );
            $CFG_GLPI['smtp_oauth_refresh_token'] = (new GLPIKey())->encrypt($refresh_token);
        }

        return $result;
    }

    public static function validateAddress($address, $patternselect = "pcre8")
    {
        if (empty($address)) {
            return false;
        }
        $isValid = parent::validateAddress($address, $patternselect);
        if (!$isValid && str_ends_with($address, '@localhost')) {
           //since phpmailer6, @localhost address are no longer valid...
            $isValid = parent::ValidateAddress($address . '.me');
        }
        return $isValid;
    }

    public function setLanguage($langcode = 'en', $lang_path = '')
    {
        if ($lang_path == '') {
            $local_path = dirname(Config::getLibraryDir('PHPMailer\PHPMailer\PHPMailer'))  . '/language/';
            if (is_dir($local_path)) {
                $lang_path = $local_path;
            }
        }
        return parent::setLanguage($langcode, $lang_path);
    }
}
