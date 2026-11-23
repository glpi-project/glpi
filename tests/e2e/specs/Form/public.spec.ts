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

const PUBLIC_FORM_TOKEN = 'public-altcha-test-token';

test('Can submit a public form with altcha verification', async ({
    formImporter,
    anonymousPage,
}) => {
    // Act: setup and go to a public form with an anonymous session
    const info = await formImporter.importForm('public-form-with-altcha.json');
    await anonymousPage.goto(
        `/Form/Render/${info.getId()}?token=${PUBLIC_FORM_TOKEN}`
    );

    // Fill the form and check the altcha
    await anonymousPage.getByRole('textbox', { name: 'Name' }).fill('John Doe');
    await anonymousPage.getByText("I'm not a robot").click();
    await expect(anonymousPage.getByText('Verified')).toBeVisible();

    // Submit the form, which should now succeed
    await anonymousPage.getByRole('button', { name: 'Submit' }).click();
    await expect(anonymousPage.getByText('Form submitted')).toBeVisible();
});
