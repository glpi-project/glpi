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

import axios, { Axios } from 'axios';
import { Config } from './Config';

/**
 * Utility class to interact with GLPI's API.
 * This help to setup tests by creating the needed items directly using the API
 * instead of the UI, which is much faster.
 */
export class GlpiApi
{
    private client: Axios;
    private worker_id: number;
    private token: string|null = null;
    private user_id: number|null = null;

    constructor() {
        const base_url = Config.getBaseUrl();
        this.client = axios.create({
            baseURL: `${base_url}/apirest.php`,
        });
        this.worker_id = Number(process.env.TEST_PARALLEL_INDEX);

        // TODO: make this constructor private and use a getInstanceMethod
        // that will return an instance for a given worker id.
        // This way we only need one instance per worker for the whole
        // suite, avoiding unnecessary initSession() calls.
    }

    public async getItem(itemtype: string, id: number) {
        const response = await this.doCrudRequest('GET', `${itemtype}/${id}`);
        return response.data;
    }

    public async createItem(itemtype: string, fields: object) {
        const response = await this.doCrudRequest('POST', itemtype, fields);
        if (response.status !== 201) {
            throw new Error('Failed to create item');
        }

        return response.data.id;
    }

    public async updateItem(itemtype: string, id: number, fields: object) {
        const response = await this.doCrudRequest(
            'PUT',
            `${itemtype}/${id}`,
            fields
        );
        return response.data;
    }

    public async getCurrentUserId() {
        if (this.user_id === null) {
            const response = await this.doApiRequest({
                method: 'GET',
                url: '/getFullSession',
            });
            this.user_id = response.data.session.glpiID;
        }

        return this.user_id;
    }

    private async initSession() {

        const response = await this.client.request({
            method: 'POST',
            url: '/initSession',
            auth: {
                'username': `playwright_worker_${this.worker_id}`,
                'password': `playwright_worker_${this.worker_id}`,
            }
        });

        this.token = response.data.session_token;
    }

    private async doCrudRequest(
        method: string,
        endpoint: string,
        values: object|null = null
    ) {
        return await this.doApiRequest({
            method: method,
            url: encodeURI(endpoint),
            data: values !== null ? {input: values} : null,
        });
    }

    private async doApiRequest(params: {
        method: string,
        url: string,
        data?: object,
        headers?: object
    }) {
        if (this.token === null) {
            await this.initSession();
        }

        params.headers = {
            'Session-Token': this.token,
        };

        try {
            return await this.client.request(params);
        } catch (error) {
            console.error(params);
            throw error;
        }
    }
}
