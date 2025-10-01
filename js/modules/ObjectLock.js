/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

/* global glpi_confirm, glpi_alert, getAjaxCsrfToken */
/* global _ */

/**
 * @todo Candidate for a websocket-based feature (or a mix of Ajax + Server-Sent Events)
 */
class ObjectLock {
    /**
     * @param {{id: number, itemtype: string, itemtype_name: string, items_id: number}} lock
     * @param {{name: string}} user_data
     * @param {boolean} new_lock Was the item locked by the current user during this page request?
     */
    constructor(lock, user_data, new_lock = false) {
        this.lock = lock;
        this.user_data = user_data;
        this.new_lock = new_lock;
        this.lockStatusTimer = undefined;
        this.#registerListeners();
    }

    #registerListeners() {
        $('#alertMe').on('change', (e) => {
            const checked = e.target.checked;
            if (checked) {
                this.lockStatusTimer = setInterval(() => {
                    $.get({
                        url: `${CFG_GLPI.root_doc}/ajax/unlockobject.php`,
                        cache: false,
                        data: {
                            lockstatus: 1,
                            id: this.lock.id
                        },
                    }).then((data) => {
                        if (data === 0) {
                            clearInterval(this.lockStatusTimer);
                            glpi_confirm({
                                title: __('Item unlocked!'),
                                message: __('Reload page?'),
                                confirm_callback: () => {
                                    window.location.reload();
                                }
                            });
                        }
                    });
                }, 15000);
            } else {
                clearInterval(this.lockStatusTimer);
            }
        });

        $('button.ask-unlock-item').on('click', () => {
            glpi_confirm({
                title: `${_.escape(this.lock.itemtype_name)} #${_.escape(this.lock.items_id)}`,
                message: __('Ask for unlock this item?'),
                confirm_callback: () => {
                    $.post({
                        url: `${CFG_GLPI.root_doc}/ajax/unlockobject.php`,
                        cache: false,
                        data: {
                            requestunlock: 1,
                            id: this.lock.id
                        },
                        dataType: 'json'
                    }).then(() => {
                        glpi_alert({
                            title: __('Unlock request sent!'),
                            message: __('Request sent to %s').replace('%s', _.escape(this.user_data['name'])),
                        });
                    }, () => {
                        glpi_alert({
                            title: _n('Error', 'Errors', 1),
                            message: __('An error occurred while sending the unlock request'),
                        });
                    });
                }
            });
        });

        $('button.force-unlock-item').on('click', () => {
            glpi_confirm({
                title: `${this.lock.itemtype_name} #${this.lock.items_id}`,
                message: __('Force unlock this item?'),
                confirm_callback: () => {
                    $.post({
                        url: `${CFG_GLPI.root_doc}/ajax/unlockobject.php`,
                        cache: false,
                        data: {
                            unlock: 1,
                            force: 1,
                            id: this.lock.id,
                        },
                        dataType: 'json'
                    }).then(() => {
                        glpi_confirm({
                            title: __('Item unlocked!'),
                            message: __('Reload page?'),
                            confirm_callback: () => {
                                window.location.reload();
                            }
                        });
                    }, () => {
                        glpi_alert({
                            title: __('Item NOT unlocked!'),
                            message: __('Contact your GLPI admin!'),
                        });
                    });
                }
            });
        });

        if (this.new_lock) {
            $(window).on('beforeunload', () => {
                const fallback_request = () => {
                    $.post({
                        url: `${CFG_GLPI.root_doc}/ajax/unlockobject.php`,
                        async: false,
                        cache: false,
                        data: {
                            unlock: 1,
                            id: this.lock.id
                        },
                        dataType: 'json'
                    });
                };

                if (typeof window.fetch !== 'undefined') {
                    fetch(`${CFG_GLPI.root_doc}/ajax/unlockobject.php`, {
                        method: 'POST',
                        cache: 'no-cache',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/x-www-form-urlencoded;',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-Glpi-Csrf-Token': getAjaxCsrfToken(),
                        },
                        body: `unlock=1&id=${this.lock.id}`
                    }).catch(() => {
                        //fallback if fetch fails
                        fallback_request();
                    });
                } else {
                    //fallback for browsers with no fetch support
                    fallback_request();
                }
            });
        }
    }
}

export function initObjectLock(lock, user_data, new_lock = false) {
    new ObjectLock(lock, user_data, new_lock);
}
