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

/* global Y, YAwareness */

// Delay between two polls when nothing changes locally.
const POLL_INTERVAL = 1000;
// Delay to group the local changes (e.g. typing) into one request.
const PUSH_DELAY = 200;
// Delay before a new try when the server does not answer.
const RETRY_INTERVAL = 5000;

/**
 * @param {Uint8Array} bytes
 * @returns {string}
 */
function toBase64(bytes) {
    let binary = '';
    for (let i = 0; i < bytes.length; i += 0x8000) {
        binary += String.fromCharCode(...bytes.subarray(i, i + 0x8000));
    }
    return btoa(binary);
}

/**
 * @param {string} data
 * @returns {Uint8Array}
 */
function fromBase64(data) {
    return Uint8Array.from(atob(data), (char) => char.charCodeAt(0));
}

/**
 * Y.js provider that syncs a document through the GLPI backend with HTTP
 * polling (no websocket). The server only relays the Y.js updates: see
 * Glpi\Controller\Knowbase\CollabSyncController.
 *
 * Usage: `await connect()`, then `seed()` if `isEmpty`, then `start()`.
 */
export class PollingProvider {
    /** @type {Y.Doc} */
    doc;

    /** @type {YAwareness.Awareness} Read by the CollaborationCaret extension. */
    awareness;

    /** @type {{id: number, name: string}|null} */
    user = null;

    /** @type {boolean} True if no one edited the article yet: the document needs a seed. */
    isEmpty = false;

    /** @type {number} */
    #item_id;

    /** @type {number} Id of the last update received from the server. */
    #cursor = 0;

    /** @type {Uint8Array[]} Local document updates not sent yet. */
    #pending = [];

    /** @type {boolean} */
    #awareness_changed = false;

    /** @type {boolean} */
    #started = false;

    /** @type {boolean} */
    #destroyed = false;

    /** @type {function|null} Ends the current wait of the poll loop. */
    #wake_up = null;

    /** @type {number|null} */
    #push_timer = null;

    /**
     * @param {number} item_id
     * @param {Y.Doc} doc
     */
    constructor(item_id, doc) {
        this.#item_id = item_id;
        this.doc = doc;
        this.awareness = new YAwareness.Awareness(doc);

        doc.on('update', this.#onDocUpdate);
        this.awareness.on('update', this.#onAwarenessUpdate);
        window.addEventListener('pagehide', this.#onPageHide);
    }

    /**
     * Load the current state of the shared document.
     */
    async connect() {
        const data = await this.#sync();
        this.isEmpty = data.cursor === 0;
    }

    /**
     * Fill the empty shared document with the saved content.
     * If another client did it first, its content is used instead.
     * @param {Uint8Array} seed
     */
    async seed(seed) {
        const data = await this.#sync({ seed: toBase64(seed) });
        if (data.seeded) {
            Y.applyUpdate(this.doc, seed, this);
        }
        this.isEmpty = false;
    }

    /**
     * Start to send the local changes and to poll the remote ones.
     */
    start() {
        this.#started = true;
        this.#loop();
    }

    /**
     * Send the last local changes, tell the others that we leave, and stop.
     */
    destroy() {
        if (this.#destroyed) {
            return;
        }
        // Sends a "null" state: the others remove our caret at once.
        YAwareness.removeAwarenessStates(this.awareness, [this.doc.clientID], 'local');
        this.#destroyed = true;
        clearTimeout(this.#push_timer);
        this.#wake_up?.();

        // keepalive: the request must complete even if the page unloads.
        this.#sync({}, true).catch(() => {});

        this.doc.off('update', this.#onDocUpdate);
        this.awareness.off('update', this.#onAwarenessUpdate);
        this.awareness.destroy();
        window.removeEventListener('pagehide', this.#onPageHide);
    }

    #onPageHide = () => {
        this.destroy();
    };

    /**
     * @param {Uint8Array} update
     * @param {*} origin
     */
    #onDocUpdate = (update, origin) => {
        if (origin === this) {
            // Received from the server.
            return;
        }
        this.#pending.push(update);
        this.#requestPush();
    };

    #onAwarenessUpdate = ({ added, updated, removed }, origin) => {
        if (origin === this) {
            return;
        }
        if ([...added, ...updated, ...removed].includes(this.doc.clientID)) {
            this.#awareness_changed = true;
            this.#requestPush();
        }
    };

    /**
     * Send the local changes soon, without the full poll interval.
     */
    #requestPush() {
        if (!this.#started || this.#destroyed || this.#push_timer !== null) {
            return;
        }
        this.#push_timer = setTimeout(() => {
            this.#push_timer = null;
            this.#wake_up?.();
        }, PUSH_DELAY);
    }

    async #loop() {
        while (!this.#destroyed) {
            let delay = POLL_INTERVAL;
            try {
                await this.#sync();
            } catch (e) {
                console.warn('Collaborative edition: sync failed', e);
                delay = RETRY_INTERVAL;
            }
            if (this.#destroyed) {
                return;
            }
            if (this.#pending.length > 0 || this.#awareness_changed) {
                delay = Math.min(delay, PUSH_DELAY);
            }
            await new Promise((resolve) => {
                this.#wake_up = resolve;
                setTimeout(resolve, delay);
            });
            this.#wake_up = null;
        }
    }

    /**
     * Send the local changes and apply the remote ones.
     * @param {object} extra - More values for the request body
     * @param {boolean} keepalive
     * @returns {Promise<object>} The server response
     */
    async #sync(extra = {}, keepalive = false) {
        const updates = this.#pending.splice(0);
        const send_awareness = this.#awareness_changed;
        this.#awareness_changed = false;

        const body = {
            client_id: this.doc.clientID,
            since: this.#cursor,
            updates: updates.length > 0 ? [toBase64(Y.mergeUpdates(updates))] : [],
            ...extra,
        };
        if (send_awareness) {
            body.awareness = toBase64(YAwareness.encodeAwarenessUpdate(this.awareness, [this.doc.clientID]));
        }

        let data;
        try {
            const response = await fetch(`${CFG_GLPI.root_doc}/Knowbase/${this.#item_id}/Collab/Sync`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(body),
                keepalive,
            });
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            data = await response.json();
        } catch (e) {
            // Send them again with the next request.
            this.#pending.unshift(...updates);
            this.#awareness_changed ||= send_awareness;
            throw e;
        }

        if (this.#destroyed) {
            return data;
        }

        this.user = data.user;
        if (data.updates.length > 0) {
            Y.applyUpdate(this.doc, Y.mergeUpdates(data.updates.map(fromBase64)), this);
        }
        this.#cursor = data.cursor;
        for (const state of data.awareness) {
            YAwareness.applyAwarenessUpdate(this.awareness, fromBase64(state), this);
        }

        return data;
    }
}
