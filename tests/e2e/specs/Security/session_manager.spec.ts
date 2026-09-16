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

import { randomUUID } from "crypto";
import { expect, test } from "../../fixtures/glpi_fixture";
import { LoginPage } from "../../pages/LoginPage";
import { Profiles } from "../../utils/Profiles";
import { getWorkerEntityId } from "../../utils/WorkerEntities";

test('Can terminate sessions', async ({
    page,
    anonymousPage,
    profile,
    api,
}) => {
    // The session list is part of the security configuration, which requires
    // more rights than the profile another test may have left on this session.
    await profile.set(Profiles.SuperAdmin);

    // Arrange: create a dedicated user with a unique name so this test's
    // session can't be confused with sessions left behind by other tests.
    const user_name = `session_manager_${randomUUID().slice(0, 8)}`;
    await api.createItem('User', {
        name: user_name,
        password: 'testpassword',
        password2: 'testpassword',
        _profiles_id: Profiles.Technician,
        _entities_id: getWorkerEntityId(),
        _is_recursive: 1,
    });

    // The main page is already logged in as an administrator.
    // Using the second anonymous page, log in as our new user.
    const login_page = new LoginPage(anonymousPage);
    await login_page.goto();
    await login_page.doLogin(user_name, 'testpassword');

    // Confirm login was succesful
    await anonymousPage.goto('/front/preference.php');
    await expect(
        anonymousPage.getByText("My settings").filter({ visible: true })
    ).toBeVisible();

    // Act: with the other page, terminate the session of our new user.
    const revoke_buttons = page.getByRole("button", { name: "Revoke", exact: true });

    await page.goto("/front/security/securityconfig.form.php?forcetab=Glpi\\Security\\SecurityConfig$2");
    await expect(page.getByText("Session list")).toBeVisible();
    await page.getByRole("textbox", { name: "User" }).fill(user_name);
    await page.getByRole("button", { name: "Filter" }).click();

    // Wait for the filtered list before acting on it: until the tab is
    // reloaded, the rows still are the unfiltered ones.
    await expect(revoke_buttons).toHaveCount(1);
    await revoke_buttons.click();

    const confirm_dialog = page.getByRole('dialog'); // Confirmation dialog
    // Bootstrap ignores a `hide()` requested while the modal is still fading in,
    // which would leave the dialog open forever, so wait for it to be shown.
    await expect(confirm_dialog).toHaveAttribute('data-cy-shown', 'true');
    const session_revoked = page.waitForResponse(
        (response) => response.url().endsWith('/Revoke')
            && response.request().method() === 'POST'
    );
    await confirm_dialog.getByRole("button", { name: "Revoke" }).click();
    await session_revoked;

    // Confirm the session was removed on the list
    await page.getByRole("textbox", { name: "User" }).fill(user_name);
    await page.getByRole("button", { name: "Filter" }).click();
    await expect(page.getByText("No results found")).toBeVisible();
    await expect(revoke_buttons).toHaveCount(0);

    // Assert: the session used by anonymousPage is correctly dismissed.
    await anonymousPage.reload();
    await expect(
        anonymousPage.getByText("Your session has expired")
    ).toBeVisible();
    await expect(
        anonymousPage.getByRole('link', { name: "Log in again" })
    ).toBeVisible();
});
