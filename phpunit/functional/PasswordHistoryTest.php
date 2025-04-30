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

use Auth;
use DbTestCase;
use Psr\Log\LogLevel;
use User;

final class PasswordHistoryTest extends DbTestCase
{
    /**
     * Data provider for testValidatePassword
     *
     * @return iterable
     */
    protected function testValidatePasswordProvider(): iterable
    {
        global $CFG_GLPI;

        // Tests users
        $new_user = new User();
        $this->assertTrue($new_user->getEmpty());
        $tu_user = getItemByTypeName('User', TU_USER);

        // Populate previous password data
        $this->updateItem("User", $tu_user->getID(), [
            'password_history' => exportArrayToDB([
                Auth::getPasswordHash("old password 1"), // Most recent
                Auth::getPasswordHash("old password 2"),
                Auth::getPasswordHash("old password 3"),
                Auth::getPasswordHash("old password 4"),
                Auth::getPasswordHash("old password 5"),
                Auth::getPasswordHash("old password 6"),
                Auth::getPasswordHash("old password 7"),
            ]),
        ]);
        $tu_user->getFromDB($tu_user->getID());

        // New user, no potential history to validate
        yield [
            'user'     => $new_user,
            'password' => "password",
            'expected' => true,
        ];

        // Try to reuse current password
        yield [
            'user'     => $tu_user,
            'password' => TU_PASS,
            'expected' => false,
        ];

        // Try to reuse current old password 1 (most recent) with no history enabled
        $CFG_GLPI['non_reusable_passwords_count'] = 1;
        yield [
            'user'     => $tu_user,
            'password' => "old password 1",
            'expected' => true,
        ];

        // Try to reuse current old password 1 with history enabled (length = 2)
        $CFG_GLPI['non_reusable_passwords_count'] = 3;
        yield [
            'user'     => $tu_user,
            'password' => "old password 1",
            'expected' => false,
        ];

        // Try to reuse current old password 2 with history enabled (length = 2)
        $CFG_GLPI['non_reusable_passwords_count'] = 3;
        yield [
            'user'     => $tu_user,
            'password' => "old password 2",
            'expected' => false,
        ];

        // Try to reuse current old password 3 with history enabled (length = 2)
        $CFG_GLPI['non_reusable_passwords_count'] = 3;
        yield [
            'user'     => $tu_user,
            'password' => "old password 3",
            'expected' => true,
        ];

        // Try to reuse current old password 3 with history enabled (length = 4)
        $CFG_GLPI['non_reusable_passwords_count'] = 5;
        yield [
            'user'     => $tu_user,
            'password' => "old password 3",
            'expected' => false,
        ];

        // Try to reuse current old password 7 with history enabled (length = 4)
        $CFG_GLPI['non_reusable_passwords_count'] = 5;
        yield [
            'user'     => $tu_user,
            'password' => "old password 7",
            'expected' => true,
        ];
    }

    /**
     * Test method for PasswordHistory->validatePassword()
     *
     * @return void
     */
    public function testValidatePassword(): void
    {
        foreach ($this->testValidatePasswordProvider() as $row) {
            $user = $row['user'];
            $password = $row['password'];
            $expected = $row['expected'];
            $this->assertEquals(
                $expected,
                \PasswordHistory::getInstance()->validatePassword($user, $password)
            );
        }
    }

    /**
     * Data provider for testUpdatePasswordHistory
     *
     * @return iterable
     */
    protected function testUpdatePasswordHistoryProvider(): iterable
    {
        global $CFG_GLPI;

        // Test subject
        $user = getItemByTypeName('User', TU_USER);

        // Update with empty password
        yield [
            'user' => $user,
            'password' => "",
            'expected' => [],
        ];

        // Update password with history disabled
        $CFG_GLPI['non_reusable_passwords_count'] = 1;
        $previous_password_1 = Auth::getPasswordHash("previous password 1");
        yield [
            'user' => $user,
            'password' => $previous_password_1,
            'expected' => [$previous_password_1],
        ];

        // Update password with history enabled
        $CFG_GLPI['non_reusable_passwords_count'] = 3;
        $previous_password_2 = Auth::getPasswordHash("previous password 2");
        yield [
            'user' => $user,
            'password' => $previous_password_2,
            'expected' => [$previous_password_2, $previous_password_1],
        ];

        // Update password with history enabled
        $CFG_GLPI['non_reusable_passwords_count'] = 3;
        $previous_password_3 = Auth::getPasswordHash("previous password 3");
        yield [
            'user' => $user,
            'password' => $previous_password_3,
            'expected' => [$previous_password_3, $previous_password_2, $previous_password_1],
        ];

        // Update password with history enabled
        $CFG_GLPI['non_reusable_passwords_count'] = 3;
        $previous_password_4 = Auth::getPasswordHash("previous password 4");
        yield [
            'user' => $user,
            'password' => $previous_password_4,
            'expected' => [$previous_password_4, $previous_password_3, $previous_password_2, $previous_password_1],
        ];

        // Update password with history enabled (going over the max limit of stored data, older password is removed from history)
        $CFG_GLPI['non_reusable_passwords_count'] = 3;
        $previous_password_5 = Auth::getPasswordHash("previous password 5");
        yield [
            'user' => $user,
            'password' => $previous_password_5,
            'expected' => [$previous_password_5, $previous_password_4, $previous_password_3, $previous_password_2],
        ];

        // Send an invalid password
        $CFG_GLPI['non_reusable_passwords_count'] = 3;
        $unhashed_password = "unhashed_password";
        yield [
            'user'     => $user,
            'password' => $unhashed_password,
            'expected' => [$previous_password_5, $previous_password_4, $previous_password_3, $previous_password_2],
            'warning'  => 'Unhashed password has not been added to passwords history.',
        ];
    }

    /**
     * Test method for PasswordHistory->updatePasswordHistory()
     *
     * @return void
     */
    public function testUpdatePasswordHistory(): void
    {
        foreach ($this->testUpdatePasswordHistoryProvider() as $row) {
            $user = $row['user'];
            $password = $row['password'];
            $expected = $row['expected'];
            $warning = $row['warning'] ?? null;

            \PasswordHistory::getInstance()->updatePasswordHistory($user, $password);
            if ($warning) {
                $this->hasPhpLogRecordThatContains($warning, LogLevel::WARNING);
            }

            // Check history
            $passwords = $this->callPrivateMethod(\PasswordHistory::getInstance(), 'getForUser', $user);
            $this->assertEquals($expected, $passwords);

            // Ensure stored data doesn't go over the max allowed size
            $this->assertLessThanOrEqual(\PasswordHistory::MAX_HISTORY_SIZE, count($passwords));
        }
    }
}
