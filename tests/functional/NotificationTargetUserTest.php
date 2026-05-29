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

namespace tests\units;

use Glpi\Tests\DbTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/* Test for inc/notificationtargetuser.class.php */

class NotificationTargetUserTest extends DbTestCase
{
    public static function addDataForPasswordExpiresTemplateProvider()
    {
        global $CFG_GLPI;

        $time_in_past   = strtotime('-10 days');
        $time_in_future = strtotime('+10 days');
        $update_url     = $CFG_GLPI['url_base'] . '/front/updatepassword.php';

        return [
            // case 1: password already expired but account will not be locked
            [
                'expiration_time' => $time_in_past,
                'lock_delay'      => -1,
                'expected'        => [
                    '##user.password.expiration.date##' => date('Y-m-d H:i', $time_in_past),
                    '##user.account.lock.date##'        => null,
                    '##user.password.has_expired##'     => '1',
                    '##user.password.update.url##'      => $update_url,
                ],
            ],
            // case 2: password already expired and account will be locked
            [
                'expiration_time' => $time_in_past,
                'lock_delay'      => 15,
                'expected'        => [
                    '##user.password.expiration.date##' => date('Y-m-d H:i', $time_in_past),
                    '##user.account.lock.date##'        => date('Y-m-d H:i', strtotime('+15 days', $time_in_past)),
                    '##user.password.has_expired##'     => '1',
                    '##user.password.update.url##'      => $update_url,
                ],
            ],
            // case 3: password not yet expired but account will not be locked
            [
                'expiration_time' => $time_in_future,
                'lock_delay'      => -1,
                'expected'        => [
                    '##user.password.expiration.date##' => date('Y-m-d H:i', $time_in_future),
                    '##user.account.lock.date##'        => null,
                    '##user.password.has_expired##'     => '0',
                    '##user.password.update.url##'      => $update_url,
                ],
            ],
            // case 2: password not yet expired and account will be locked
            [
                'expiration_time' => $time_in_future,
                'lock_delay'      => 15,
                'expected'        => [
                    '##user.password.expiration.date##' => date('Y-m-d H:i', $time_in_future),
                    '##user.account.lock.date##'        => date('Y-m-d H:i', strtotime('+15 days', $time_in_future)),
                    '##user.password.has_expired##'     => '0',
                    '##user.password.update.url##'      => $update_url,
                ],
            ],
        ];
    }

    #[DataProvider('addDataForPasswordExpiresTemplateProvider')]
    public function testAddDataForPasswordExpiresTemplate(int $expiration_time, int $lock_delay, array $expected)
    {
        global $CFG_GLPI;

        $user = $this->getMockBuilder(\User::class)
            ->onlyMethods(['getPasswordExpirationTime'])
            ->getMock();
        $user->method('getPasswordExpirationTime')->willReturn($expiration_time);

        $cfg_backup = $CFG_GLPI;
        $CFG_GLPI['password_expiration_lock_delay'] = $lock_delay;
        $target = new \NotificationTargetUser(
            getItemByTypeName('Entity', '_test_root_entity', true),
            'passwordexpires',
            $user
        );
        $target->addDataForTemplate('passwordexpires');
        $CFG_GLPI = $cfg_backup;

        $this->checkTemplateData($target->data, $expected);
    }

    public static function passwordTokenUrlProvider(): array
    {
        $cases = [
            // Normal sha1 hex token (happy path) — no special chars, rawurlencode is a no-op
            'sha1 hex token'              => ['a3f4b2c1d5e6789012345678901234567890abcd'],
            // Base64 chars: + must become %2B, not a space (urldecode regression)
            'token with +'                => ['abc+def+ghi'],
            // Base64 chars: / must become %2F
            'token with /'                => ['abc/def/ghi'],
            // Base64 chars: = (padding) must become %3D
            'token with ='                => ['abc='],
            // All base64 special chars combined
            'token with +, / and ='       => ['aB3+cd/ef=='],
        ];

        $events = [
            'passwordinit'   => [
                'passwordinit',
                '##user.passwordiniturl##',
            ],
            'passwordforget' => [
                'passwordforget',
                '##user.passwordforgeturl##',
            ],
        ];

        $result = [];
        foreach ($events as $event_label => [$event, $url_tag]) {
            foreach ($cases as $token_label => [$raw_token]) {
                $result["$event_label / $token_label"] = [$event, $url_tag, $raw_token];
            }
        }
        return $result;
    }

    #[DataProvider('passwordTokenUrlProvider')]
    public function testPasswordTokenUrlIsProperlyEncoded(
        string $event,
        string $url_tag,
        string $raw_token
    ): void {
        $encrypted_token = (new \GLPIKey())->encrypt($raw_token);

        $user = new \User();
        $user->fields = ['password_forget_token' => $encrypted_token];

        $target = new \NotificationTargetUser(
            getItemByTypeName('Entity', '_test_root_entity', true),
            $event,
            $user
        );
        $target->addDataForTemplate($event);

        $url = $target->data[$url_tag];

        $this->assertStringNotContainsString(' ', $url, 'URL must not contain spaces');
        $this->assertStringContainsString('password_forget_token=' . rawurlencode($raw_token), $url);
    }

    private function checkTemplateData(array $data, array $expected)
    {
        foreach ($expected as $key => $value) {
            $this->assertSame($value, $data[$key]);
        }
    }
}
