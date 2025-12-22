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

import { test, expect } from '../../fixtures/glpi_fixture';
import { DocumentPage } from '../../pages/DocumentPage';
import { Profiles } from '../../utils/Profiles';

test('Can upload and download a document', async ({ page, profile }) => {
    // Go to document page
    await profile.set(Profiles.SuperAdmin);
    const document_page = new DocumentPage(page);
    await document_page.gotoCreationPage();

    // Upload a file
    await document_page.doAddFileToUploadArea(
        "uploads/bar.txt",
        page.getByRole('main'),
    );
    await document_page.getAddButton().click();
    await expect(document_page.getNameInput()).toHaveValue('bar.txt');

    // Download the file
    const download_button = page.getByRole('main')
        .getByRole('link', {name: 'bar.txt'})
    ;
    const content = await document_page.doClickDownloadLinkAndGetcontent(
        page,
        download_button,
    );
    expect(content).toBe('bar');
});
