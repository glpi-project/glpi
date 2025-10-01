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

namespace Glpi\Security;

use DateInterval;
use Entity;
use Exception;
use Glpi\Application\View\TemplateRenderer;
use GLPIKey;
use Group_User;
use JsonException;
use Profile_User;
use RobThree\Auth\Algorithm;
use RobThree\Auth\Providers\Qr\BaconQrCodeProvider;
use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\TwoFactorAuthException;
use Safe\DateTimeImmutable;
use SodiumException;

use function Safe\json_encode;

final class TOTPManager
{
    /**
     * @var integer Length of the secret in bits
     */
    public const SECRET_LENGTH_BITS = 160;

    /**
     * @var integer Number of digits in the TOTP code
     */
    public const CODE_LENGTH_DIGITS = 6;

    /**
     * @var integer Number of seconds the TOTP code is valid for.
     * For compatibility with Google Authenticator, this should be 30 because that app support anything else.
     */
    public const CODE_VALIDITY_SECONDS = 30;

    /**
     * @var array Algorithm used to generate the TOTP code
     * For compatibility with Google Authenticator, this should be sha1 (HMAC-SHA1. Still fairly secure).
     * Some authenticator apps may have a different default, and manual code entry may not have an option to
     * select the algorithm. Therefore, sha1 is the "default" we use (QR code).
     * When the user enters the code after registering, we will check against all algorithms here in case the
     * app is using a different one, and then store the used algorithm in the user's 2FA config.
     */
    public const CODE_ALGORITHM = ['sha1', 'sha256'];

    /**
     * @var integer 2FA is not enforced. Users can choose to enable it or not.
     */
    public const ENFORCEMENT_OPTIONAL = 0;

    /**
     * @var integer 2FA is enforced, but users have some time before they are forced to enable it.
     */
    public const ENFORCEMENT_MANDATORY_GRACE_PERIOD = 1;

    /**
     * @var integer 2FA is enforced, and users must enable it (no grace period remaining).
     */
    public const ENFORCEMENT_MANDATORY = 2;

    /**
     * @var integer Number of backup codes to generate
     */
    public const BACKUP_CODES_COUNT = 5;

    /**
     * @var integer Length of backup codes in characters
     */
    public const BACKUP_CODES_LENGTH = 16;

    /**
     * @var string Label to use for the brand in QR codes
     */
    public static string $brand_label = 'GLPI';

    /**
     * Mainly used to get the displayed issuer for the TOTP in Authenticator app
     * @return string
     */
    public function getIssuer(): string
    {
        global $CFG_GLPI;
        $label = $CFG_GLPI['app_name'] ?? self::$brand_label;

        $tfaSuffix = $CFG_GLPI['2fa_suffix'] ?? '';
        if (!empty($tfaSuffix)) {
            $label .= ' (' . $tfaSuffix . ')';
        }

        return $label;
    }

    /**
     * Get an instance of the TwoFactorAuth class
     * @param string $algorithm Algorithm used to generate the TOTP code.
     * @return TwoFactorAuth
     * @throws TwoFactorAuthException
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
     * Generate a new TOTP secret code
     * @return string The secret code
     * @throws TwoFactorAuthException
     */
    public function createSecret(): string
    {
        return $this->getTwoFactorAuth()->createSecret(self::SECRET_LENGTH_BITS);
    }

    /**
     * Set the TOTP secret code for a user
     * @param int $users_id ID of the user
     * @param string $secret The secret code
     * @return bool True on success, false otherwise
     */
    public function setSecretForUser(int $users_id, string $secret, ?string $algorithm = null): bool
    {
        global $DB;

        $encrypted_secret = (new GLPIKey())->encrypt($secret);
        return $DB->update('glpi_users', [
            '2fa'   => json_encode([
                'algorithm' => 'totp',
                'secret' => $encrypted_secret,
                'digest' => $algorithm ?? self::CODE_ALGORITHM[0],
                'digits' => self::CODE_LENGTH_DIGITS,
                'period' => self::CODE_VALIDITY_SECONDS,
            ]),
        ], [
            'id' => $users_id,
        ]) !== false;
    }


    /**
     * Disable 2FA for a user
     * @param int $users_id ID of the user
     * @return bool True on success, false otherwise
     */
    public function disable2FAForUser(int $users_id): bool
    {
        global $DB;

        return $DB->update('glpi_users', [
            '2fa'   => null,
        ], [
            'id' => $users_id,
        ]) !== false;
    }

    /**
     * Get the 2FA configuration for a user
     * @param int $users_id ID of the user
     * @return array|null The configuration, or null if 2FA is not enabled for the user
     * @throws JsonException
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
                $config['secret'] = (new GLPIKey())->decrypt($config['secret']);
                return $config;
            }
        } catch (SodiumException $e) {
            global $PHPLOGGER;
            $PHPLOGGER->error(
                "Unreadable TOTP secret for user ID {$users_id}: " . $e->getMessage(),
                ['exception' => $e]
            );

            return null;
        }
    }

    /**
     * Check if 2FA is enabled for a user
     * @param int $users_id ID of the user
     * @return bool True if 2FA is enabled, false otherwise
     * @throws JsonException
     */
    public function is2FAEnabled(int $users_id): bool
    {
        $config = $this->get2FAConfigForUser($users_id);

        return $config !== null && !empty($config['secret']);
    }

    /**
     * Verify a TOTP code for a user
     * @param string $code The code to verify
     * @param int $users_id ID of the user
     * @return bool True if the code is valid, false otherwise
     * @throws JsonException
     * @throws TwoFactorAuthException
     */
    public function verifyCodeForUser(string $code, int $users_id): bool
    {
        $config = $this->get2FAConfigForUser($users_id);
        if ($config['secret'] === null) {
            return false;
        }
        return $this->getTwoFactorAuth($config['digest'] ?? 'sha1')->verifyCode($config['secret'], $code);
    }

    /**
     * Verify a TOTP code for a secret
     * @param string $code The code to verify
     * @param string $secret The secret to use
     * @return string|false The algorithm matched if the code is valid, false otherwise
     * @throws TwoFactorAuthException
     */
    public function verifyCodeForSecret(string $code, string $secret): string|false
    {
        $match = false;
        foreach (self::CODE_ALGORITHM as $algorithm) {
            if ($this->getTwoFactorAuth($algorithm)->verifyCode($secret, $code)) {
                $match = $algorithm;
                break;
            }
        }
        return $match;
    }

    public function isBackupCodesAvailable(int $users_id): bool
    {
        global $DB;

        $tfa = $DB->request([
            'SELECT' => ['2fa'],
            'FROM' => 'glpi_users',
            'WHERE' => [
                'id' => $users_id,
            ],
            'LIMIT' => 1,
        ])->current()['2fa'] ?? null;

        if ($tfa === null) {
            return false;
        }

        $tfa = json_decode($tfa, true, 512, JSON_THROW_ON_ERROR);
        return isset($tfa['backup_codes']) && !empty($tfa['backup_codes']);
    }

    /**
     * Regenerate backup codes for a user.
     * Any previously generated codes are invalidated.
     * @param int $users_id ID of the user
     * @return array The new backup codes
     * @throws JsonException
     */
    public function regenerateBackupCodes(int $users_id): array
    {
        global $DB;

        $random_codes = [];
        $code_hashes = [];
        $code_chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        for ($i = 0; $i < self::BACKUP_CODES_COUNT; $i++) {
            $code = '';
            for ($j = 0; $j < self::BACKUP_CODES_LENGTH; $j++) {
                $code .= $code_chars[random_int(0, strlen($code_chars) - 1)];
            }
            $random_codes[] = $code;
            $code_hashes[] = password_hash($code, PASSWORD_BCRYPT);
        }

        $tfa = $DB->request([
            'SELECT' => ['2fa'],
            'FROM' => 'glpi_users',
            'WHERE' => [
                'id' => $users_id,
            ],
            'LIMIT' => 1,
        ])->current()['2fa'] ?? null;
        if ($tfa === null) {
            return [];
        }
        $tfa = json_decode($tfa, true, 512, JSON_THROW_ON_ERROR);
        $tfa['backup_codes'] = $code_hashes;
        $DB->update('glpi_users', [
            '2fa' => json_encode($tfa, JSON_THROW_ON_ERROR),
        ], [
            'id' => $users_id,
        ]);
        return $random_codes;
    }

    /**
     * Verify a backup code for a user
     * @param string $code Backup code
     * @param int $users_id User ID
     * @param bool $consume_code If true, the backup code will be consumed and will not be usable again
     * @return bool True if the code is valid, false otherwise
     * @throws JsonException
     */
    public function verifyBackupCodeForUser(string $code, int $users_id, bool $consume_code = true): bool
    {
        global $DB;

        $tfa = $DB->request([
            'SELECT' => ['2fa'],
            'FROM' => 'glpi_users',
            'WHERE' => [
                'id' => $users_id,
            ],
            'LIMIT' => 1,
        ])->current()['2fa'] ?? null;
        if ($tfa === null) {
            return false;
        }
        $tfa = json_decode($tfa, true, 512, JSON_THROW_ON_ERROR);
        if (!isset($tfa['backup_codes'])) {
            return false;
        }
        foreach ($tfa['backup_codes'] as $i => $hash) {
            if (password_verify($code, $hash)) {
                if ($consume_code) {
                    unset($tfa['backup_codes'][$i]);
                    $DB->update('glpi_users', [
                        '2fa' => json_encode($tfa, JSON_THROW_ON_ERROR),
                    ], [
                        'id' => $users_id,
                    ]);
                }
                return true;
            }
        }
        return false;
    }

    /**
     * Get the 2FA enforcement level for a user
     * @param int $users_id ID of the user
     * @return int One of the ENFORCEMENT_* constants
     * @phpstan-return self::ENFORCEMENT_*
     * @throws Exception
     */
    public function get2FAEnforcement(int $users_id): int
    {
        global $CFG_GLPI, $DB;

        $user_optional = $DB->request([
            'SELECT' => ['2fa_unenforced'],
            'FROM' => 'glpi_users',
            'WHERE' => [
                'id' => $users_id,
            ],
            'LIMIT' => 1,
        ])->current()['2fa_unenforced'] ?? 0;

        if ($user_optional) {
            return self::ENFORCEMENT_OPTIONAL;
        }

        $enforced = false;
        if ((int) $CFG_GLPI['2fa_enforced'] === 1) {
            $enforced = true;
        } else {
            // Check entity configuration
            $entities = PermissionManager::getInstance()->getAllEntities($users_id);
            foreach ($entities as $entity) {
                if ((int) Entity::getUsedConfig('2fa_enforcement_strategy', $entity, '', 0) === 1) {
                    $enforced = true;
                    break;
                }
            }
            if (!$enforced) {
                // Check profile configuration
                $profiles = Profile_User::getUserProfiles($users_id);
                if (!empty($profiles)) {
                    $enforced = $DB->request([
                        'SELECT' => ['2fa_enforced'],
                        'FROM' => 'glpi_profiles',
                        'WHERE' => [
                            'id' => $profiles,
                            '2fa_enforced' => 1,
                        ],
                    ])->count() > 0;
                }
            }
            if (!$enforced) {
                // Check group configuration
                $groups = Group_User::getUserGroups($users_id);
                $enforced = count(array_filter($groups, static fn($group) => $group['2fa_enforced'])) > 0;
            }
        }

        if ($enforced) {
            $in_grace_period = $this->getGracePeriodDaysLeft() > 0;
            return $in_grace_period ? self::ENFORCEMENT_MANDATORY_GRACE_PERIOD : self::ENFORCEMENT_MANDATORY;
        }
        return self::ENFORCEMENT_OPTIONAL;
    }

    /**
     * @return int Number of days left in the grace period
     * @throws Exception
     */
    public function getGracePeriodDaysLeft(): int
    {
        global $CFG_GLPI;

        $grace_days = $CFG_GLPI['2fa_grace_days'] ?? 0;
        $grace_start_date = $CFG_GLPI['2fa_grace_date_start'] ?? null;

        if ($grace_days > 0 && $grace_start_date !== null) {
            $grace_start_date = new DateTimeImmutable($grace_start_date);
            $grace_end_date = $grace_start_date->add(new DateInterval("P{$grace_days}D"));
            $now = new DateTimeImmutable();
            if ($now < $grace_end_date) {
                return $now->diff($grace_end_date)->days;
            }
        }
        return 0;
    }

    /**
     * Show a form asking the user for their TOTP code.
     * @return void
     */
    public function showTOTPPrompt(): void
    {
        TemplateRenderer::getInstance()->display('pages/2fa/2fa_request.html.twig', [
            'redirect' => $_GET['redirect'] ?? '',
        ]);
    }

    /**
     * Show a form to set up TOTP for the current user or manage the settings if it is set up already.
     * @param int $users_id ID of the user
     * @param bool $force_setup Force the setup form to be shown even if 2FA is already enabled
     * @param bool $regenerate_backup_codes Regenerate backup codes immediately when showing the status form
     * @return void
     * @throws JsonException
     * @throws TwoFactorAuthException
     */
    public function showTOTPConfigForm(int $users_id, bool $force_setup = false, bool $regenerate_backup_codes = false): void
    {
        global $CFG_GLPI;

        if (!$force_setup && $this->is2FAEnabled($users_id)) {
            $grace_period_end = null;
            $enforcement = $this->get2FAEnforcement($users_id);
            if ($enforcement === self::ENFORCEMENT_MANDATORY_GRACE_PERIOD) {
                $grace_period_end = (new DateTimeImmutable($CFG_GLPI['2fa_grace_date_start']))
                    ->add(new DateInterval("P{$CFG_GLPI['2fa_grace_days']}D"));
                // Get the date as a string
                $grace_period_end = $grace_period_end->format('Y-m-d H:i:s');
            }
            TemplateRenderer::getInstance()->display('pages/2fa/2fa_status.html.twig', [
                'enforcement' => $enforcement,
                'grace_period_end' => $grace_period_end,
                'regenerate_backup_codes' => $regenerate_backup_codes,
            ]);
        } else {
            $secret = $this->createSecret();
            $tfa = $this->getTwoFactorAuth();
            $qr = $tfa->getQRCodeImageAsDataUri($_SESSION['glpiname'], $secret);
            TemplateRenderer::getInstance()->display('pages/2fa/2fa_new_secret.html.twig', [
                'qrcode' => $qr,
                'secret' => $secret,
            ]);
        }
    }

    /**
     * Show a form to set up TOTP for the current user or manage the settings if it is set up already.
     * @param int $users_id User ID
     * @return void
     */
    public function showTOTPSetupForm(int $users_id): void
    {
        $secret = $this->createSecret();
        $tfa = $this->getTwoFactorAuth();
        $name = $this->getIssuer();
        if (isset($_SESSION['mfa_pre_auth'])) {
            $name = $_SESSION['mfa_pre_auth']['username'];
        } elseif (isset($_SESSION['glpiname'])) {
            $name = $_SESSION['glpiname'];
        }
        $qr = $tfa->getQRCodeImageAsDataUri($name, $secret);
        TemplateRenderer::getInstance()->display('pages/2fa/2fa_enforced_setup.html.twig', [
            'qrcode' => $qr,
            'secret' => $secret,
            'enforcement' => $this->get2FAEnforcement($users_id),
            'grace_period_days_left' => $this->getGracePeriodDaysLeft(),
        ]);
    }

    /**
     * Show the backup codes for the specified user.
     * Intended for use after setting up 2FA during the login process.
     * @return void
     */
    public function showBackupCodes(int $users_id): void
    {
        $redirect = $_SESSION['mfa_pre_auth']['redirect'] ?? null;

        TemplateRenderer::getInstance()->display('pages/2fa/2fa_backup_codes.html.twig', [
            'backup_codes' => $this->regenerateBackupCodes($users_id),
            'redirect'     => $redirect,
        ]);
    }
}
