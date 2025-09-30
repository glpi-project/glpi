<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
use Session;
use User;
use UserEmail;

class UserEmailTest extends DbTestCase
{
    public function testCan(): void
    {
        $users_passwords = [
            TU_USER     => TU_PASS,
            'glpi'      => 'glpi',
            'tech'      => 'tech',
            'normal'    => 'normal',
            'post-only' => 'postonly',
        ];

        $users_matrix = [
            TU_USER => [
                TU_USER     => true,
                'glpi'      => true,
                'tech'      => true,
                'normal'    => true,
                'post-only' => true,
            ],
            'glpi' => [
                TU_USER     => true,
                'glpi'      => true,
                'tech'      => true,
                'normal'    => true,
                'post-only' => true,
            ],
            'tech' => [
                TU_USER     => false,
                'glpi'      => false,
                'tech'      => true,
                'normal'    => false, // has some more rights somewhere
                'post-only' => true,
            ],
            'normal' => [
                TU_USER     => false,
                'glpi'      => false,
                'tech'      => false,
                'normal'    => true,
                'post-only' => true,
            ],
            'post-only' => [
                TU_USER     => false,
                'glpi'      => false,
                'tech'      => false,
                'normal'    => false,
                'post-only' => true,
            ],
        ];

        foreach ($users_matrix as $login => $targer_users_names) {
            $this->login($login, $users_passwords[$login]);

            foreach ($targer_users_names as $target_user_name => $can) {
                $target_user_id = \getItemByTypeName(User::class, $target_user_name, true);

                $input = [
                    'users_id' => $target_user_id,
                    'email'    => \bin2hex(\random_bytes(16)) . '@example.org',
                ];

                $item = new UserEmail();
                $this->assertEquals(
                    $can && Session::haveRight(User::$rightname, CREATE),
                    $item->can($item->getID(), CREATE, $input)
                );

                $item = $this->createItem(UserEmail::class, $input);
                $this->assertEquals(
                    $can && Session::haveRight(User::$rightname, READ),
                    $item->can($item->getID(), READ)
                );

                $this->assertEquals(
                    $can && Session::haveRight(User::$rightname, UPDATE),
                    $item->can($item->getID(), UPDATE)
                );

                $this->assertEquals(
                    $can && Session::haveRight(User::$rightname, DELETE),
                    $item->can($item->getID(), DELETE)
                );
            }
        }
    }
}
