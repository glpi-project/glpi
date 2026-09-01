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

import { test, expect } from '../../fixtures/glpi_fixture';
import { LoginPage } from '../../pages/LoginPage';
import { UserPage } from '../../pages/UserPage';
import { Profiles } from '../../utils/Profiles';
import AxeBuilder from '@axe-core/playwright';

// Main tab of the preference page, and of the admin user form.
const PREFERENCE_TAB = 'User$1';
const USER_FORM_TAB = 'User$main';

// Login page, logged-out.
test('login fields expose autocomplete tokens', async ({ anonymousPage }) => {
    const login = new LoginPage(anonymousPage);
    await login.goto();

    await expect(login.login_input).toHaveAttribute('autocomplete', 'username');
    await expect(login.password_input).toHaveAttribute('autocomplete', 'current-password');

    const a11y = await new AxeBuilder({ page: anonymousPage })
        .include('form')
        .withTags(['wcag135'])
        .analyze()
    ;
    expect(a11y.violations).toEqual([]);
});

// Lost-password "forget" email also carries autocomplete="email", but its form only renders
// with password-recovery notifications enabled (off in e2e), so it is verified manually.

// Preference page: tokens emitted only in the preference context.
test('profile identity fields expose autocomplete tokens when editing own profile', async ({ page, profile }) => {
    await profile.set(Profiles.SuperAdmin);
    const user_page = new UserPage(page);
    await user_page.gotoPreferences(PREFERENCE_TAB);

    await expect(page.getByLabel('Surname', { exact: true })).toHaveAttribute('autocomplete', 'family-name');
    await expect(page.getByLabel('First name', { exact: true })).toHaveAttribute('autocomplete', 'given-name');
    await expect(page.getByLabel('Middle name / Patronymic', { exact: true })).toHaveAttribute('autocomplete', 'additional-name');
    await expect(page.getByLabel('Phone', { exact: true })).toHaveAttribute('autocomplete', 'tel');
    // Qualified so autofill does not treat it as the same field as `phone`.
    await expect(page.getByLabel('Mobile phone', { exact: true })).toHaveAttribute('autocomplete', 'mobile tel');
    await expect(page.getByLabel('Phone 2', { exact: true })).toHaveAttribute('autocomplete', 'tel');
});

// Guard: the admin user form (outside the preference context) must NOT emit personal tokens.
test('admin user form does not expose personal autocomplete tokens (guard)', async ({ page, profile }) => {
    await profile.set(Profiles.SuperAdmin);
    const user_page = new UserPage(page);
    await page.goto(`/front/user.form.php?forcetab=${USER_FORM_TAB}`);

    await expect(page.getByLabel('Surname', { exact: true })).not.toHaveAttribute('autocomplete', 'family-name');

    // Email rows are built in PHP, so they carry their own guard.
    await expect(user_page.getEmailFields().first()).not.toHaveAttribute('autocomplete');
});

// User's own e-mail inputs on the profile page, server-rendered and client-added alike.
test('profile email inputs expose autocomplete=email when editing own profile', async ({ page, profile }) => {
    await profile.set(Profiles.SuperAdmin);
    const user_page = new UserPage(page);
    await user_page.gotoPreferences(PREFERENCE_TAB);

    const emails = user_page.getEmailFields();
    await expect(emails.first()).toHaveAttribute('autocomplete', 'email');

    // The "+" button builds its row from JS, which is a distinct render path.
    const initial_count = await emails.count();
    await user_page.doAddNewEmailField();
    await expect(emails).toHaveCount(initial_count + 1);
    await expect(emails.last()).toHaveAttribute('autocomplete', 'email');
});

// Regression: no invalid autocomplete tokens anywhere on the profile form.
test('profile form has no invalid autocomplete tokens', async ({ page, profile }) => {
    await profile.set(Profiles.SuperAdmin);
    const user_page = new UserPage(page);
    await user_page.gotoPreferences(PREFERENCE_TAB);

    // The tab loads over AJAX; without this axe would scan an empty page.
    await expect(page.getByLabel('Surname', { exact: true })).toBeVisible();

    const a11y = await new AxeBuilder({ page })
        .include('form')
        .withTags(['wcag135'])
        .analyze()
    ;
    expect(a11y.violations).toEqual([]);
});
