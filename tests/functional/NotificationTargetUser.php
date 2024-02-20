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

namespace tests\units;

use DbTestCase;

/* Test for inc/notificationtargetuser.class.php */

class NotificationTargetUser extends DbTestCase
{
    protected function addDataForPasswordExpiresTemplateProvider()
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

    /**
     * @dataProvider addDataForPasswordExpiresTemplateProvider
     */
    public function testAddDataForPasswordExpiresTemplate(int $expiration_time, int $lock_delay, array $expected)
    {
        global $CFG_GLPI;

        $user = new \mock\User();
        $this->calling($user)->getPasswordExpirationTime = $expiration_time;

        $cfg_backup = $CFG_GLPI;
        $CFG_GLPI['password_expiration_lock_delay'] = $lock_delay;$target = new \NotificationTargetUser(
            getItemByTypeName('Entity', '_test_root_entity', true),
            'passwordexpires',
            $user
        );
        $target->addDataForTemplate('passwordexpires');
        $CFG_GLPI = $cfg_backup;

        $this->checkTemplateData($target->data, $expected);
    }

    private function checkTemplateData(array $data, array $expected)
    {
        $this->array($data)->hasKeys(array_keys($expected));
        foreach ($expected as $key => $value) {
            $this->variable($data[$key])->isIdenticalTo($value);
        }
    }
}
