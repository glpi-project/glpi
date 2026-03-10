<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

declare(strict_types=1);

namespace Glpi\Security;

use Glpi\Exception\RedirectException;
use RobThree\Auth\Algorithm;
use RobThree\Auth\Providers\Qr\BaconQrCodeProvider;
use RobThree\Auth\TwoFactorAuth;
use Safe\DateTime;

/**
 * $_SESSION[glpi_reauth_until] is set to a timestamp until which the user is considered re-authenticated.
 * $_SESSION[glpi_reauth_redirect] stores the URL to redirect after successful re-authentication. - ? doc a virer, géré par $this->setSuccessRedirectURL() et $this->getRedirectSuccessURL()
 * $_SESSION[glpi_reauth_postdata] stores the POST data to restore after successful re-authentication.
 */
class ReAuthManager
{
    /**
     * @var int Number of digits in the TOTP code
     */
    public const CODE_LENGTH_DIGITS = 6; // @todo copié de TOTPManager, à factoriser

    /**
     * @var int Number of seconds the TOTP code is valid for.
     * For compatibility with Google Authenticator, this should be 30 because that app support anything else.
     * Also notice that using a high value (e.g. 60) will cause verify to always fail.
     */
    public const CODE_VALIDITY_SECONDS = 30;  // @todo copié de TOTPManager, à factoriser

    /**
     * Reauth validity delay in seconds.
     * Beyond this time limit, the user must re-authenticate
     */
    public const REAUTH_DELAY_SECONDS = 1 * MINUTE_TIMESTAMP; // 1 for debuging, 15 * MINUTE_TIMESTAMP for production

    /**
     * Trigger redirect to re-authentication form if needed
     *
     * + store data to do the proper redirect after sucessfull reauthentication
     *
     * @throws RedirectException
     */
    public function checkReAuthenticationOrRedirect(): void
    {
        if (!$this->isReAuthenticated()) {
            $this->setSuccessRedirectURL(($_SERVER['REDIRECT_URL'] ?? '') . '?' . ($_SERVER['REDIRECT_QUERY_STRING'] ?? ''));// @todo maybe not the best way to retrieve the url + remove ? when no query string
            $this->setPostDataForRedirect($_POST);
            // @todo maybe it's better to store the url in get parameter ?
            throw new RedirectException("/ReAuth/Prompt"); // @todo éventuellement utilise le router pour générer l'url, mais ça peut alourdir inutilement le code. cf Manager
        }
    }

    /**
     * Check if datetime limit for re-authentication is still valid
     */
    public function isReAuthenticated(): bool
    {
        $current_limit_timestamp = $_SESSION['glpi_reauth_until'] ?? null;
        $calculated_limit_timestamp = (new DateTime($_SESSION['glpi_currenttime']))->getTimestamp();

        return $current_limit_timestamp !== null && $current_limit_timestamp > $calculated_limit_timestamp;
    }

    /**
     * Set the user as re-authenticated
     *
     * Do not use it to bypass re-authentication.
     * Used after successful login to avoid anoying users with a re-authentication form on first sudo check just after login.
     */
    public function initiate(): void
    {
        $this->authenticate();
    }

    public function verify(string $user_input_code): bool
    {
        $config = $this->get2FAConfigForUser($_SESSION['glpiID']);
        if ($config['secret'] === null) { // @todo peut ne pas être set et $config null -> \Glpi\Security\TOTPManager::is2FAEnabled - prob existant aussi dans TOTPManager
            return false;
        }

        return $this->getTwoFactorAuth($config['digest'] ?? 'sha1')->verifyCode($config['secret'], $user_input_code, 0);
    }

    public function authenticate(): void
    {
        $_SESSION['glpi_reauth_until'] = (new DateTime($_SESSION['glpi_currenttime']))
            ->modify('+' . self::REAUTH_DELAY_SECONDS . ' seconds')
            ->getTimestamp();
    }

    /**
     * @todo copié de TOTPManager, à factoriser
     */
    private function getTwoFactorAuth(string $algorithm = 'sha1'): TwoFactorAuth
    {
        static $tfa = null;
        if ($tfa === null) {
            $tfa = new TwoFactorAuth(
                new BaconQrCodeProvider(4, '#ffffff', '#000000', 'svg'),
                $this->getIssuer(),
                self::CODE_LENGTH_DIGITS,
                self::CODE_VALIDITY_SECONDS,
                Algorithm::from($algorithm)
            );
        }

        return $tfa;
    }

    /**
     * @todo copié de TOTPManager, à factoriser, utilisé par $this->getTwoFactorAuth()
     */
    public function getIssuer(): string
    {
        global $CFG_GLPI;
        $label = $CFG_GLPI['app_name'] ?? 'GLPI';

        $tfaSuffix = $CFG_GLPI['2fa_suffix'] ?? '';
        if (!empty($tfaSuffix)) {
            $label .= ' (' . $tfaSuffix . ')';
        }

        return $label;
    }

    public function getRedirectSuccessURL(): string
    {
        return $_SESSION['glpi_reauth_redirect']; // @todo prévoir un vrai fallback si vide
    }

    /**
     * @todo copié de TOTPManager, à factoriser
     */
    private function get2FAConfigForUser(int $users_id): ?array
    {
        global $DB;

        $it = $DB->request([
            'SELECT' => ['2fa'],
            'FROM' => 'glpi_users',
            'WHERE' => [
                'id' => $users_id,
            ],
            'LIMIT' => 1,
        ]);

        if (count($it) === 0) {
            return null;
        }
        try {
            $config = $it->current()['2fa'];
            if (empty($config)) {
                return null;
            }
            $config = json_decode($config, true, 512, JSON_THROW_ON_ERROR);
            if (!isset($config['secret'])) {
                return null;
            } else {
                $config['secret'] = (new \GLPIKey())->decrypt($config['secret']);
                return $config;
            }
        } catch (\SodiumException $e) {
            global $PHPLOGGER; // @todo bonne façon de faire ?
            $PHPLOGGER->error(
                "Unreadable TOTP secret for user ID {$users_id}: " . $e->getMessage(),
                ['exception' => $e]
            );

            return null;
        }
    }

    private function setSuccessRedirectURL(string $url): void
    {
        $_SESSION['glpi_reauth_redirect'] = $url;
    }

    private function setPostDataForRedirect(array $post): void
    {
        $_SESSION['glpi_reauth_postdata'] = $post;
    }

    public function getPostDataForRedirect(): array
    {
        return $_SESSION['glpi_reauth_postdata'] ?? [];
    }

}
