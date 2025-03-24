/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

// Bootstrap for all JS test modules

await import('jquery').then((jquery) => {
    window.$ = window.jQuery = jquery.default;
});
await import('@tabler/core');
await import('select2/dist/js/select2.full').then((select2) => {
    $.fn.select2 = select2;
});

// Add a flag variable to know in other scripts if they are run in tests. Should not affect how they behave, just how functions/vars in non-modules are bound.
window.GLPI_TEST_ENV = true;

// Set faux CFG_GLPI variable. We cannot get the real values since they are set inline in PHP.
window.CFG_GLPI = {
    root_doc: '/'
};

// Mock localization
// eslint-disable-next-line no-unused-vars
window.__ = function (msgid, domain /* , extra */) {
    return msgid;
};
// eslint-disable-next-line no-unused-vars
window._n = function (msgid, msgid_plural, n = 1, domain /* , extra */) {
    return n === 1 ? msgid : msgid_plural;
};
// eslint-disable-next-line no-unused-vars
window._x = function (msgctxt, msgid, domain /* , extra */) {
    return msgid;
};
// eslint-disable-next-line no-unused-vars
window._nx = function (msgctxt, msgid, msgid_plural, n = 1, domain /* , extra */) {
    return n === 1 ? msgid : msgid_plural;
};

window.AjaxMockResponse = class {

    constructor(url, method = 'GET', data = {}, response, is_persistent = false) {
        /**
         * The URL
         * @type string
         */
        this.url = url;
        this.method = method;
        this.data = data;
        this.response = response;
        this.is_persistent = is_persistent;
    }

    isMatch(url, method = 'GET', data = {}) {
        if (this.url !== url || this.method !== method) {
            return false;
        }
        let data_match = true;
        $.each(data, (k, v) => {
            // We specifically allow type coercion in the comparison
            if (this.data[k] !== undefined && this.data[k] != v) {
                data_match = false;
                return false;
            }
        });
        return data_match;
    }

    call(data) {
        return this.response(data);
    }
};

class AjaxMock {

    constructor() {
        /**
         * @type {AjaxMockResponse[]}
         */
        this.response_stack = [];
        $._ajax = $.ajax;
    }

    start() {
        this.response_stack = [];
        $.ajax = this.ajax;
    }

    end() {
        this.response_stack = [];
        $.ajax = $._ajax;
    }

    addMockResponse(response) {
        this.response_stack.push(response);
    }

    isResponseStackEmpty() {
        return this.response_stack.length === 0;
    }

    ajax() {
        let url, settings;

        if (arguments.length === 2) {
            [url, settings] = arguments;
        } else {
            settings = arguments[0];
            url = settings.url;
        }

        if (window.AjaxMock.isResponseStackEmpty()) {
            throw new Error('No mock responses in stack');
        }
        let result = undefined;
        for (let i = 0; i < window.AjaxMock.response_stack.length; i++) {
            const response = window.AjaxMock.response_stack[i];
            if (response.isMatch(url, settings.method, settings.data)) {
                result = response.call(settings.data);
                if (!response.is_persistent) {
                    window.AjaxMock.response_stack.splice(i, 1);
                }
            }
        }
        if (result !== undefined) {
            return Promise.resolve(result);
        } else {
            throw "No mock response found for " + url;
        }
    }
}
window.AjaxMock = new AjaxMock();

await import('../../js/common.js');
