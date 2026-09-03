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
import { Locator, Page } from '@playwright/test';
import { readFileSync } from 'fs';
import path from 'path';
import { expect, test } from '../../../fixtures/glpi_fixture';
import { GlpiPage } from '../../../pages/GlpiPage';
import { Profiles } from '../../../utils/Profiles';
import {
    getWorkerEntityName,
    getWorkerIndex,
} from '../../../utils/WorkerEntities';

test.describe('Import forms', () => {
    const getWorkerEntityCompleteName = (): string => {
        return `Root entity > E2E tests entity > ${getWorkerEntityName()}`;
    };

    const getWorkerFriendlyName = (): string => {
        const index = String(getWorkerIndex()).padStart(2, '0');
        return `E2E worker account ${index}`;
    };

    // The "export-of-2-forms.json" fixture references the cypress dataset
    // entity ("Root entity > E2ETestEntity"), which does not exist in the
    // playwright dataset. Rewrite it on the fly so "My valid form" is still
    // valid and only "My invalid form" needs its issues resolved.
    // TODO: create a dedicated fixture that is tailormade for this test.
    const doSelectExportOf2Forms = async (page: Page): Promise<void> => {
        const file_path = path.join(
            __dirname,
            '../../../../fixtures/export-of-2-forms.json'
        );
        const content = readFileSync(file_path).toString().replaceAll(
            'Root entity > E2ETestEntity',
            getWorkerEntityCompleteName()
        );

        await page.getByLabel("Select your file").setInputFiles({
            name: 'export-of-2-forms.json',
            mimeType: 'application/json',
            buffer: Buffer.from(content),
        });
    };

    const doOpenImporter = async (page: Page): Promise<void> => {
        await page.goto('/front/form/form.php');
        await page.getByRole('button', { name: "Import forms" }).click();
    };

    const getRows = (page: Page): Locator => {
        return page.getByRole('row');
    };

    const assertFormIsReadyToBeImported = async (
        row: Locator,
        name: string,
    ): Promise<void> => {
        await expect(row.getByText(name)).toBeAttached();
        await expect(row.getByText("Ready to be imported")).toBeAttached();
        await expect(row.getByRole("button", { name: "Resolve issues" }))
            .not.toBeAttached()
        ;
        await expect(row.getByRole("button", { name: "Remove form" }))
            .toBeAttached()
        ;
    };

    const assertFormCannotBeImported = async (
        row: Locator,
        name: string,
    ): Promise<void> => {
        await expect(row.getByText(name)).toBeAttached();
        await expect(row.getByText("Can't be imported")).toBeAttached();
        await expect(row.getByRole("button", { name: "Resolve issues" }))
            .toBeAttached()
        ;
        await expect(row.getByRole("button", { name: "Remove form" }))
            .toBeAttached()
        ;
    };

    test('can import forms whitout resolve issues', async ({
        page,
        profile,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const glpi_page = new GlpiPage(page);

        // Step 1: file selection
        await doOpenImporter(page);
        await doSelectExportOf2Forms(page);

        // Step 2: preview
        await glpi_page.getButton("Preview import").click();
        let preview = getRows(page);
        await assertFormIsReadyToBeImported(preview.nth(1), "My valid form");
        await assertFormCannotBeImported(preview.nth(2), "My invalid form");

        // Step 4: import
        await glpi_page.getButton("Import").click();
        preview = getRows(page);
        await expect(
            preview.nth(1).getByRole("link", { name: "My valid form" })
        ).toBeAttached();
        await expect(preview.nth(1).getByText("Imported")).toBeAttached();
        await expect(preview.nth(2).getByText("My invalid form"))
            .toBeAttached()
        ;
        await expect(preview.nth(2).getByText("Not imported")).toBeAttached();

        // Go back to first step
        await glpi_page.getLink("Import another file").click();
        await expect(page.getByLabel("Select your file")).toBeAttached();
    });

    test('can import forms with resolve issues', async ({ page, profile }) => {
        await profile.set(Profiles.SuperAdmin);
        const glpi_page = new GlpiPage(page);

        // Step 1: file selection
        await doOpenImporter(page);
        await doSelectExportOf2Forms(page);

        // Step 2: preview
        await glpi_page.getButton("Preview import").click();
        let preview = getRows(page);
        await assertFormIsReadyToBeImported(preview.nth(1), "My valid form");
        await assertFormCannotBeImported(preview.nth(2), "My invalid form");
        await preview.nth(2)
            .getByRole("button", { name: "Resolve issues" })
            .click()
        ;

        // Step 3: resolve issues
        const issues = getRows(page);
        await expect(issues.nth(1).getByText("Missing entity")).toBeAttached();
        const entity_dropdown = glpi_page.getDropdownByLabel(
            "Replacement value for 'Missing entity'"
        );
        await expect(entity_dropdown)
            .toHaveText(getWorkerEntityCompleteName())
        ;
        // The option is prefixed by a "»" in the tree dropdown but the
        // selected value is displayed with its completename, so the dropdown
        // content is checked separately.
        await glpi_page.doSetDropdownValue(
            entity_dropdown,
            `»${getWorkerEntityName()}`,
            true,
            false
        );
        await expect(entity_dropdown)
            .toHaveText(getWorkerEntityCompleteName())
        ;

        await expect(issues.nth(2).getByText("Missing user")).toBeAttached();
        await glpi_page.doSetDropdownValue(
            glpi_page.getDropdownByLabel(
                "Replacement value for 'Missing user'"
            ),
            getWorkerFriendlyName()
        );

        // Step 2: preview
        await glpi_page.getButton("Preview import").click();
        preview = getRows(page);
        await assertFormIsReadyToBeImported(preview.nth(1), "My valid form");
        await assertFormIsReadyToBeImported(preview.nth(2), "My invalid form");

        // Step 4: import
        await glpi_page.getButton("Import").click();
        preview = getRows(page);
        await expect(
            preview.nth(1).getByRole("link", { name: "My valid form" })
        ).toBeAttached();
        await expect(preview.nth(1).getByText("Imported")).toBeAttached();
        await expect(
            preview.nth(2).getByRole("link", { name: "My invalid form" })
        ).toBeAttached();
        await expect(preview.nth(2).getByText("Imported")).toBeAttached();

        // Go back to first step
        await glpi_page.getLink("Import another file").click();
        await expect(page.getByLabel("Select your file")).toBeAttached();
    });

    test('can remove forms from the import list', async ({ page, profile }) => {
        await profile.set(Profiles.SuperAdmin);
        const glpi_page = new GlpiPage(page);

        // Step 1: file selection
        await doOpenImporter(page);
        await doSelectExportOf2Forms(page);

        // Step 2: preview
        await glpi_page.getButton("Preview import").click();
        const preview = getRows(page);
        await assertFormIsReadyToBeImported(preview.nth(1), "My valid form");
        await assertFormCannotBeImported(preview.nth(2), "My invalid form");

        // Remove the second form
        await preview.nth(2)
            .getByRole("button", { name: "Remove form" })
            .click()
        ;
        await expect(preview.nth(1)).toBeAttached();
        await expect(preview.nth(2)).not.toBeAttached();

        // Remove the first form
        await preview.nth(1)
            .getByRole("button", { name: "Remove form" })
            .click()
        ;
        await expect(preview).toHaveCount(0);

        // Check if we are back to the first step
        await expect(page.getByLabel("Select your file")).toBeAttached();
    });

    test("can see errors on forms that can't be imported at all", async ({
        page,
        profile,
    }) => {
        await profile.set(Profiles.SuperAdmin);
        const glpi_page = new GlpiPage(page);

        // Step 1: file selection
        await doOpenImporter(page);
        await page.getByLabel("Select your file").setInputFiles(
            path.join(
                __dirname,
                '../../../../fixtures/forms/form-with-hammer-asset.json'
            )
        );

        // Step 2: preview
        await glpi_page.getButton("Preview import").click();
        const preview = getRows(page);
        await expect(preview.nth(1).getByText("Test form")).toBeAttached();
        await expect(
            preview.nth(1).getByText(
                "Unknown custom type: Glpi\\CustomAsset\\HammerAsset"
            )
        ).toBeAttached();
        await expect(
            preview.nth(1).getByRole("button", { name: "Resolve issues" })
        ).not.toBeAttached();
        await expect(
            preview.nth(1).getByRole("button", { name: "Remove form" })
        ).toBeAttached();
    });
});
