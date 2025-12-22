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

import { APIRequestContext } from "@playwright/test";
import { readFileSync } from "fs";
import path from "path";
import { getWorkerEntityId } from "./WorkerEntities";
import { CsrfFetcher } from "./CsrfFetcher";
import { JSDOM } from 'jsdom';
import { ImportedFormInfo } from "./ImportedFormInfo";

export class FormImporter
{
    private request: APIRequestContext;
    private csrf: CsrfFetcher;

    public constructor(request: APIRequestContext, csrf: CsrfFetcher)
    {
        this.request = request;
        this.csrf = csrf;
    }

    public async importForm(filename: string): Promise<ImportedFormInfo>
    {
        const file_path = path.join(__dirname, `../../fixtures/forms/${filename}`);

        // Note: this does not really use the API for now as there are no real
        // endpoint to import a form (so we do a simple POST request).
        // We'll refactor later when we add this endpoint.
        const response = await this.request.post('/Form/Import/Execute', {
            form: {
                json: readFileSync(file_path).toString(),
                // _glpi_csrf_token: this.cache.getCsrfToken(),
                'replacements[0][itemtype]'      : "Entity",
                'replacements[0][original_name]' : "Root entity",
                'replacements[0][replacement_id]': getWorkerEntityId(),
            },
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-Glpi-Csrf-Token': await this.csrf.get(),
            }
        });

        // Read some informations about the imported form using the raw html
        // response.
        const dom = new JSDOM(await response.text());
        const link = dom.window.document.querySelector(
            'a[href^="/front/form/form.form.php?id="]'
        ) as HTMLAnchorElement;

        const href = link.getAttribute('href');
        if (href === null) {
            throw new Error("Failed to locate link to form");
        }

        const url = new URL(href, 'http://localhost');
        const id = url.searchParams.get('id');
        if (id === null) {
            throw new Error("Failed to get form id");
        }

        return new ImportedFormInfo(parseInt(id), href, link.text);
    }
}
