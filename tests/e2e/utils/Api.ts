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

import axios, { AxiosInstance } from "axios";
import { Config } from "./Config";
import { WorkerSessionCache } from "./WorkerSessionCache";

/**
 * Utility class to interact with GLPI's API.
 * This help to setup tests by creating the needed items directly using the API
 * instead of the UI, which is much faster.
 */
export class Api
{
    private cache: WorkerSessionCache;

    public constructor(cache: WorkerSessionCache)
    {
        this.cache = cache;
    }

    public async getItem(itemtype: string, id: number): Promise<any>
    {
        const response = await this.doCrudRequest('GET', `${itemtype}/${id}`);
        return response.data;
    }

    public async createItem(itemtype: string, fields: object): Promise<number>
    {
        const response = await this.doCrudRequest('POST', itemtype, fields);
        if (response.status !== 201) {
            throw new Error('Failed to create item');
        }

        return Number(response.data.id);
    }

    public async updateItem(
        itemtype: string,
        id: number,
        fields: object
    ): Promise<any> {
        const response = await this.doCrudRequest(
            'PUT',
            `${itemtype}/${id}`,
            fields
        );
        return response.data;
    }

    public async purgeItem(itemtype: string, id: number): Promise<any>
    {
        const response = await this.doCrudRequest(
            'DELETE',
            `${itemtype}/${id}`,
        );
        return response.data;
    }

    private async initApiClient(): Promise<AxiosInstance>
    {
        const base_url = Config.getBaseUrl();
        const client = axios.create({
            baseURL: `${base_url}/apirest.php`,
        });

        // Init the session
        const response = await client.request({
            method: 'POST',
            url: '/initSession',
            auth: {
                'username': `e2e_api_account`,
                'password': `e2e_api_account`,
            }
        });
        const token = response.data.session_token;

        // Set the token as a default header for all future requests
        client.defaults.headers.common['Session-Token'] = token;

        // Store the client and token in cache
        return client;
    }

    private async doCrudRequest(
        method: string,
        endpoint: string,
        values: object|null = null
    ): Promise<any> {
        return await this.doApiRequest({
            method: method,
            url: encodeURI(endpoint),
            data: values !== null ? {input: values} : null,
        });
    }

    private async doApiRequest(params: {
        method: string,
        url: string,
        data?: object|null,
        headers?: object
    }): Promise<any> {
        let client = this.cache.getApiClient();
        if (client === null) {
            client = await this.initApiClient();
            this.cache.setApiClient(client);
        }

        try {
            return await client.request(params);
        } catch (error) {
            console.error(params);
            throw error;
        }
    }
}
