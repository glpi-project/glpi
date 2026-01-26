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

import axios, { AxiosInstance } from "axios";
import { openSync, closeSync, unlinkSync, existsSync } from "fs";
import { tmpdir } from "os";
import { join } from "path";
import { Config } from "./Config";
import { WorkerSessionCache } from "./WorkerSessionCache";

const ENTITY_LOCK_FILE = join(tmpdir(), 'glpi-e2e-entity-creation.lock');

type Tile = {
    title: string,
    description: string
    illustration: string,
    page: string
};

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

    public async getSubItems(itemtype: string, id: number, subitemtype: string): Promise<any[]>
    {
        const response = await this.doCrudRequest(
            'GET',
            `${itemtype}/${id}/${subitemtype}`
        );
        return response.data;
    }

    public async createItem(itemtype: string, fields: object): Promise<number>
    {
        if (itemtype === 'Entity') {
            // Hack for entities to prevent the issue described here:
            // https://github.com/glpi-project/glpi/issues/22625
            // Can be removed once the issue is resolved.
            return this.createItemWithLock(itemtype, fields);
        }

        return this.doCreateItem(itemtype, fields);
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

    public refreshSession(): void
    {
        // Delete the stored client to force a new session for the next API
        // requests.
        this.cache.removeApiClient();
    }

    public async asyncCreateTilesForItem(
        itemtype: string,
        id: number,
        tiles: Tile[]
    ): Promise<void> {
        // Create a few tiles
        const created_tiles = [];
        for (const tile of tiles) {
            created_tiles.push(
                this.createItem('Glpi\\Helpdesk\\Tile\\GlpiPageTile', tile)
            );
        }
        const tile_ids = await Promise.all(created_tiles);

        const linked_tiles = [];
        let i = 0;
        for (const tile_id of tile_ids) {
            linked_tiles.push(this.createItem('Glpi\\Helpdesk\\Tile\\Item_Tile', {
                'itemtype_item': itemtype,
                'items_id_item': id,
                'itemtype_tile': 'Glpi\\Helpdesk\\Tile\\GlpiPageTile',
                'items_id_tile': tile_id,
                'rank': i++,
            }));
        }
        await Promise.all(linked_tiles);
    }

    private async doCreateItem(itemtype: string, fields: object): Promise<number>
    {
        const response = await this.doCrudRequest('POST', itemtype, fields);
        if (response.status !== 201) {
            throw new Error('Failed to create item');
        }

        return Number(response.data.id);
    }

    private async createItemWithLock(itemtype: string, fields: object): Promise<number>
    {
        const lock = await this.acquireLock();
        try {
            return await this.doCreateItem(itemtype, fields);
        } finally {
            this.releaseLock(lock);
        }
    }

    private async acquireLock(): Promise<number>
    {
        const maxAttempts = 100;
        const retryDelay = 50;

        for (let attempt = 0; attempt < maxAttempts; attempt++) {
            try {
                const fd = openSync(ENTITY_LOCK_FILE, 'wx');
                return fd;
            } catch {
                await new Promise(resolve => setTimeout(resolve, retryDelay));
            }
        }

        throw new Error('Failed to acquire lock for Entity creation');
    }

    private releaseLock(fd: number): void
    {
        try {
            closeSync(fd);
        } finally {
            if (existsSync(ENTITY_LOCK_FILE)) {
                unlinkSync(ENTITY_LOCK_FILE);
            }
        }
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
