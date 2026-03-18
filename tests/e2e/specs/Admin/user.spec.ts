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
import { UserPage } from '../../pages/UserPage';
import { Profiles } from '../../utils/Profiles';
import { getWorkerEntityId } from '../../utils/WorkerEntities';

test('Change my password field', async ({ page, profile }) => {
    await profile.set(Profiles.SuperAdmin);
    const user_page = new UserPage(page);
    await user_page.gotoPreferences('User$1');
    await page.getByRole('button', { name: /Change password/ }).click();
    await expect(page).toHaveURL("/front/updatepassword.php");
});

test('Change other password field', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);

    // Create a user in the current entity so we have right to update his password
    const user_name = `pwduser_${crypto.randomUUID().slice(0, 8)}`;
    const user_id = await api.createItem('User', {
        name: user_name,
        password: 'testpassword',
        password2: 'testpassword',
    });
    await api.createItem('Profile_User', {
        users_id: user_id,
        profiles_id: Profiles.SuperAdmin,
        entities_id: getWorkerEntityId(),
        is_recursive: 1,
    });

    const user_page = new UserPage(page);
    await user_page.gotoUserForm(user_id, 'User$main');

    await user_page.getButton("Change password").click();
    const dialog = page.getByRole('dialog');
    await expect(dialog).toBeVisible();
    await expect(dialog.getByLabel('Password', { exact: true })).toBeVisible();
    await expect(dialog.getByLabel('Confirm password')).toBeVisible();
});

test('Change user picture', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);

    // Create a user in the current entity so we have right to update his picture
    const user_name = `pwduser_${crypto.randomUUID().slice(0, 8)}`;
    const user_id = await api.createItem('User', {
        name: user_name,
        password: 'testpassword',
        password2: 'testpassword',
    });
    await api.createItem('Profile_User', {
        users_id: user_id,
        profiles_id: Profiles.SuperAdmin,
        entities_id: getWorkerEntityId(),
        is_recursive: 1,
    });

    const user_page = new UserPage(page);
    await user_page.gotoUserForm(user_id, 'User$main');
    await user_page.doOpenChangePictureDialog();

    const dialog = page.getByRole('dialog');
    await expect(dialog).toBeVisible();

    const current_avatar = dialog.getByTestId('current-avatar');
    const default_avatar = dialog.getByTestId('default-avatar');
    const preview_avatar = dialog.getByTestId('preview-avatar');

    // Default state
    await expect(current_avatar).toBeVisible();
    await expect(default_avatar).toBeHidden();
    await expect(preview_avatar).toBeHidden();

    // Upload a picture
    await user_page.doUploadPicture('uploads/foo.png');
    await expect(preview_avatar).toBeVisible();
    await expect(default_avatar).toBeHidden();
    await expect(current_avatar).toBeHidden();

    // Remove upload
    await user_page.doRemoveUploadedFile();
    await expect(current_avatar).toBeVisible();
    await expect(default_avatar).toBeHidden();
    await expect(preview_avatar).toBeHidden();
});

test('Can add emails and set one as default', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);

    // Create a user to make sure it has no emails
    const uid = crypto.randomUUID().slice(0, 8);
    const user_name = `emailuser_${uid}`;
    const user_id = await api.createItem('User', {
        name: user_name,
        password: 'testpassword',
        password2: 'testpassword',
    });
    await api.createItem('Profile_User', {
        users_id: user_id,
        profiles_id: Profiles.SuperAdmin,
        entities_id: getWorkerEntityId(),
        is_recursive: 1,
    });

    const user_page = new UserPage(page);
    const email1 = `test_${uid}@test.test`;
    const email2 = `another_${uid}@test.test`;

    // Add first email
    await user_page.gotoUserForm(user_id, 'User$main');
    await user_page.getTextbox('Email address').fill(email1);
    await user_page.save_button.click();

    // Email should be added
    await expect(user_page.getTextbox('Email address').first()).toHaveValue(email1);
    await expect(user_page.getRadio('Set as default email')).toBeChecked();

    // Add second email
    await user_page.doAddNewEmailField();
    await user_page.getTextbox('Email address').nth(1).fill(email2);
    await user_page.save_button.click();

    // Both email should be visible
    await expect(user_page.getTextbox('Email address').nth(0)).toHaveValue(email2);
    await expect(user_page.getTextbox('Email address').nth(1)).toHaveValue(email1);
    await expect(user_page.getRadio('Set as default email').nth(0)).not.toBeChecked();
    await expect(user_page.getRadio('Set as default email').nth(1)).toBeChecked();
});

test('Can add and remove my substitutes', async ({ page, profile, api }) => {
    await profile.set(Profiles.SuperAdmin);

    // Create a user to use as a substitue
    const substitute_name = `substitute_${crypto.randomUUID().slice(0, 8)}`;
    const substitute_id = await api.createItem('User', {
        'name': substitute_name,
        'password': 'testpassword',
        'password2': 'testpassword',
    });
    await api.createItem('Profile_User', {
        'users_id': substitute_id,
        'profiles_id': Profiles.SuperAdmin,
        'entities_id': getWorkerEntityId(),
        'is_recursive': 1,
    });

    const user_page = new UserPage(page);
    await user_page.gotoPreferences('ValidatorSubstitute$1');

    // Add substitute
    const substitutes_dropdown = user_page.getDropdownByLabel('Approval substitutes');
    await user_page.doSearchAndClickDropdownValue(
        substitutes_dropdown,
        substitute_name,
    );
    await user_page.save_button.click();
    await expect(substitutes_dropdown).toContainText(substitute_name);

    // Remove substitute
    await user_page.doClearDropdownValue(substitutes_dropdown, substitute_name);
    await user_page.save_button.click();
    await expect(substitutes_dropdown).toHaveText('');
});

test('Can change own picture', async ({ page, profile }) => {
    await profile.set(Profiles.SelfService);
    const user_page = new UserPage(page);
    await user_page.gotoPreferences('User$1');

    // Add a new picture
    await user_page.doOpenChangePictureDialog();
    const dialog = page.getByRole('dialog');
    await expect(dialog).toBeVisible();
    await user_page.doUploadPicture('uploads/foo.png');
    await expect(dialog.getByAltText('Preview')).toBeVisible();
    await expect(dialog.getByAltText('Preview')).toHaveAttribute('src');
    await dialog.getByRole('button', { name: 'Close' }).click();
    await user_page.save_button.click();
    await expect(page.getByRole('alert')).toContainText('Item successfully updated');
    await expect(page.getByTestId('current-avatar-container')).not.toHaveText('E');

    // Clear the picture
    await user_page.doOpenChangePictureDialog();
    await expect(dialog).toBeVisible();
    const clear_checkbox = dialog.getByLabel('Clear');
    await clear_checkbox.click();
    await dialog.getByRole('button', { name: 'Close' }).click();
    await user_page.save_button.click();
    await expect(page.getByRole('alert')).toContainText('Item successfully updated');
    await expect(page.getByTestId('current-avatar-container')).toHaveText('E');
});
