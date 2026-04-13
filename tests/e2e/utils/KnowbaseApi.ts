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

import { Api } from "./Api";
import { getWorkerEntityId } from "./WorkerEntities";

/**
 * Set of helper methods to interract with the KB throught the API
 */
export class KnowbaseApi
{
    private api: Api;

    public constructor(api: Api)
    {
        this.api = api;
    }

    public async createArticle({
        name = 'My article',
        answer = 'My content',
    } = {}): Promise<number> {
        return await this.api.createItem('KnowbaseItem', {
            name       : name,
            entities_id: getWorkerEntityId(),
            answer     : `<p>${answer}</p>`,
        });
    }

    public async addTranslation(kb_id: number, language: string, params: {
        name: string,
        answer: string,
    }): Promise<number> {
        return await this.api.createItem('KnowbaseItemTranslation', {
            knowbaseitems_id: kb_id,
            language        : language,
            name            : params.name,
            answer          : `<p>${params.answer}</p>`,
        });
    }

    public async updateTranslation(translation_id: number, params: {
        name: string,
        answer: string,
    }): Promise<number> {
        return await this.api.updateItem(
            'KnowbaseItemTranslation',
            translation_id,
            { name: params.name, answer: `<p>${params.answer}</p>`},
        );
    }
}
