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

let api_token = null;
let oauth_token = null;

/**
 * @memberof Cypress.Chainable.prototype
 * @method login
 * @description Login to GLPI. This command will also reuse the session for subsequent calls when possible rather than logging in again.
 * @param {string} [username=e2e_tests] - Username
 * @param {string} [password=glpi] - Password
 * @returns Chainable
 */
Cypress.Commands.add('login', (username = 'e2e_tests', password = 'glpi') => {
    cy.clearAllCookies();
    cy.request('index.php').its('body').then((body) => {
        const $html = Cypress.$(body);

        // Parse page
        const csrf = $html.find('input[name=_glpi_csrf_token]').val();
        const username_input = $html.find('#login_name').prop('name');
        const password_input = $html.find('#login_password').prop('name');

        // Send login request
        cy.request({
            method: 'POST',
            url: '/front/login.php',
            form: true,
            body: {
                [username_input]: username,
                [password_input]: password,
                _glpi_csrf_token: csrf,
            }
        });
    });
});

/**
 * @memberof Cypress.Chainable.prototype
 * @method logout
 * @description Logout of GLPI
 * @returns Chainable
 */
Cypress.Commands.add('logout', () => {
    cy.request('/front/logout.php');
});

Cypress.Commands.add('getCsrfToken', () => {
    // Load any light page that have a form
    return cy.request('/front/preference.php').its('body').then((body) => {
        // Parse page
        const $html = Cypress.$(body);
        const csrf = $html.find('input[name=_glpi_csrf_token]').val();
        return csrf;
    });
});

/**
 * @memberof Cypress.Chainable.prototype
 * @method changeProfile
 * @description Change the profile of the current user. Only supports the default GLPI profiles.
 * @param {string} profile - Profile to change to
 */
Cypress.Commands.add('changeProfile', (profile) => {
    const profiles = new Map([
        ['Self-Service', 1],
        ['Observer',     2],
        ['Admin',        3],
        ['Super-Admin',  4],
        ['Hotliner',     5],
        ['Technician',   6],
        ['Supervisor',   7],
        ['Read-Only',    8],
    ]);
    const profile_id = profiles.get(profile);

    cy.getCsrfToken().then((token) => {
        // Send change profile request
        cy.request({
            method: 'POST',
            url: '/Session/ChangeProfile',
            form: true,
            body: {
                id: profile_id,
                _glpi_csrf_token: token,
            }
        });
    });
});

Cypress.Commands.add('changeEntity', (entity, is_recursive = false) => {
    cy.getCsrfToken().then((token) => {
        const params = {
            _glpi_csrf_token: token,
        };

        if (entity == 'all') {
            params['full_structure'] = true;
        } else {
            params['id'] = entity;
            params['is_recursive'] = is_recursive;
        }

        // Send change profile request
        cy.request({
            method: 'POST',
            url: '/Session/ChangeEntity',
            form: true,
            body: params
        });
    });
});

/**
 * @memberof Cypress.Chainable.prototype
 * @method iframe
 * @description Helper to interact with elements inside an iframe given the complexity of needing to wait for the iframe to load and needing to switch contexts to the iframe's document.
 *              You can optionally specify a url_pattern to match the iframe's content window location.
 * @param {string} url_pattern - Optional. A regular expression to match the iframe's content window location. Defaults to the baseUrl.
 * @example cy.get('iframe').iframe().find('body').type('Hello, World!');
 * @example cy.get('iframe').iframe('about:srcdoc').find('body').type('Hello, World!');
 */
Cypress.Commands.add('iframe', {prevSubject: 'element'}, (iframe, url_pattern) => {
    // if no url_pattern is provided, match on baseUrl
    const base_url = Cypress.config('baseUrl');
    if (url_pattern === undefined) {
        url_pattern = new RegExp(`^${base_url}`);
    }
    return cy.wrap(new Cypress.Promise(resolve => {
        // Check if the iframe's content window is already loaded to a page on the same domain
        if (
            iframe[0].contentWindow.location.href.match(url_pattern)
            && iframe.contents().find('body').length > 0
        ) {
            resolve(iframe.contents().find('body'));
            return;
        }
        iframe.on('load', () => {
            if (iframe[0].contentWindow.location.href.match(url_pattern)) {
                resolve(iframe.contents().find('body'));
            }
        });
    }));
});

/**
 * @memberof Cypress.Chainable.prototype
 * @method awaitTinyMCE
 * @description Assert that the subject is a TinyMCE editor and try to wait for tinyMCE to initialize the editor.
 */
Cypress.Commands.add('awaitTinyMCE',  {
    prevSubject: 'element',
}, (subject) => {
    cy.wrap(subject).parent().click(); // Trigger lazy loading
    cy.wrap(subject).parent().find('div.tox-tinymce').should('exist').find('iframe').iframe('about:srcdoc').find('p', {timeout: 10000});
});

Cypress.Commands.overwrite('type', (originalFn, subject, text, options) => {
    // If the subject is a textarea, see if there is a TinyMCE editor for it
    // If there is, set the content of the editor instead of the textarea
    if (!subject.is('textarea')) {
        return originalFn(subject, text, options);
    }

    cy.window().as('win');
    cy
        .get(`textarea[name="${subject.attr('name')}"]`)
        .invoke('attr', 'id')
        .as('textarea_id')
    ;

    cy.getMany(["@win", "@textarea_id"]).then(([win, textarea_id]) => {
        if (!win.tinymce.get(textarea_id)) {
            return originalFn(subject, text, options);
        }

        if (options === undefined || !options.interactive) {
            win.tinymce.get(textarea_id).setContent(text);
            return;
        }

        // Use 'should' off the 'window()' to wait for the required property to be set.
        cy.window().should('satisfy', () => {
            return typeof win.tinymce.get(textarea_id).dom.doc !== 'undefined';
        });
        cy.wrap(win.tinymce.get(textarea_id).dom.doc).within(() => {
            cy.get('#tinymce[contenteditable="true"]').should('exist');
            cy.get('#tinymce p').type(text, options);
        });
    });
});

Cypress.Commands.overwrite('select', (originalFn, subject, text, options) => {
    // Check if the subject is a select2 dropdown
    if (!subject.hasClass('select2-selection')) {
        return originalFn(subject, text, options);
    }

    const select_id = subject.attr('aria-labelledby').replace('select2-', '').replace('-container', '');

    // Add force option
    options = options || {};
    options.force = true;

    cy.get(`#${select_id}`).select(text, options);
});

/**
 * @memberof Cypress.Chainable.prototype
 * @method selectDate
 * @description Select a date in a flatpickr input
 * @param {string} date - Date to select
 * @param {boolean} interactive - Whether to use the flatpickr calendar or type the date
 * @returns Chainable
 */
Cypress.Commands.add('selectDate', {
    prevSubject: 'element',
}, (subject, date, interactive = true) => {
    // the subject should exist
    cy.wrap(subject).should('exist').then((subject) => {
        cy.wrap(subject).should('satisfy', (subject) => {
            return subject.attr('type') === 'hidden' && subject.hasClass('flatpickr-input');
        }).then((subject) => {
            if (subject.attr('type') === 'hidden' && subject.hasClass('flatpickr-input')) {
                if (interactive) {
                    cy.wrap(subject).parents('.flatpickr').find('input:not(.flatpickr-input)').click();
                    // Parse the date to get the desired year, month and day
                    const date_obj = new Date(date);
                    const year = date_obj.getFullYear();
                    const month = date_obj.getMonth();
                    const day = date_obj.getDate();

                    cy.get('.flatpickr-calendar.open').within(() => {
                        cy.get('.flatpickr-monthDropdown-months').select(month);
                        cy.get('input.cur-year').clear();
                        cy.get('input.cur-year').type(year);
                        cy.get('.flatpickr-day').contains(new RegExp(`^${day}$`)).click();
                    });
                } else {
                    cy.wrap(subject).parents('.flatpickr').find('input:not(.flatpickr-input)').type(date);
                }
            }
        });
    });
});

/**
 * @memberof Cypress.Chainable.prototype
 * @method blockGLPIDashboards
 * @description Block requests to /ajax/dashboard.php to make page ready faster and avoid some JS errors when navigating away during loading.
 */
Cypress.Commands.add('blockGLPIDashboards', () => {
    // Intercepts need defined in reverse order
    // Intercept all other requests to /ajax/dashboard.php and respond with an empty string
    cy.intercept({path: '/ajax/dashboard.php**'}, { body: '' });
    cy.intercept({path: '/ajax/dashboard.php?action=get_filter_data**'}, { body: '{}' });
});

/**
 * @memberof Cypress.Chainable.prototype
 * @method validateSelect2Loading
 * @description Verify that the results for a Select2 dropdown can load. Only works for dropdowns that call an AJAX endpoint for results.
 */
Cypress.Commands.add('validateSelect2Loading', {prevSubject: true}, (subject) => {
    let trigger_arrow = subject.siblings('.select2').find('.select2-selection__arrow');
    let mouse_event = 'mousedown';
    if (trigger_arrow.length === 0) {
        trigger_arrow = subject.siblings('.select2').find('.select2-selection--multiple');
        mouse_event = 'click';
        if (trigger_arrow.length === 0) {
            throw new Error('Could not find the select2 trigger arrow or multiple selection element.');
        }
    }

    cy.intercept('/ajax/**').as('ajax');
    cy.get(trigger_arrow).trigger(mouse_event, {which: 1, force: true});
    cy.wait('@ajax').then(() => {
        // change context to the window's document body
        cy.document().then((doc) => {
            cy.wrap(doc.body).find('.select2-dropdown').should('have.length', 1).within((container) => {
                Cypress.log({name: 'validateSelect2Loading', displayName: 'Select2 Loading', message: container.html()});
                cy.get('li:not(.select2-results__message)').should('exist').then(() => {
                    // Close the dropdown
                    cy.get(trigger_arrow).trigger(mouse_event, {which: 1, force: true});
                });
            });
        });
    });
});

/**
 * @memberof Cypress.Chainable.prototype
 * @method getMany
 * @description Get multiple elements and return their values
 * @param {string[]} names Array of selectors
 */
Cypress.Commands.add("getMany", (selectors) => {
    const values = [];
    for (const arg of selectors) {
        cy.get(arg).then((value) => values.push(value));
    }
    return cy.wrap(values);
});

/**
 * @memberof Cypress.Chainable.prototype
 * @method createWithAPI
 * @description Get  an item using the legacy API
 * @param {string} itemtype
 * @param {number} id
 */
Cypress.Commands.add("getWithAPI", (itemtype, id) => {
    const url = `${itemtype}/${id}`;
    return cy.initApi().doApiRequest("GET", url).then(response => {
        return response.body;
    });
});

/**
 * @memberof Cypress.Chainable.prototype
 * @method createWithAPI
 * @description Create an item using the legacy API
 * @param {string} itemtype API endpoint
 * @param {object} values Values to create the item with
 */
Cypress.Commands.add("createWithAPI", (itemtype, values) => {
    return cy.initApi().doApiRequest("POST", itemtype, values).then(response => {
        if (response.status !== 201) {
            throw new Error('Failed to create item');
        }

        // Session can't be re-used as active entities will be invalid...
        if (itemtype == "Entity") {
            api_token = null;
        }

        return response.body.id;
    });
});

/**
 * @memberof Cypress.Chainable.prototype
 * @method updateWithAPI
 * @description Update an item using the legacy API
 * @param {string} itemtype API endpoint
 * @param {object} values Values to update the item with
 */
Cypress.Commands.add("updateWithAPI", (itemtype, id, values) => {
    cy.initApi().doApiRequest("PUT", `${itemtype}/${id}`, values);
});

Cypress.Commands.add("deleteWithAPI", (itemtype, id) => {
    cy.initApi().doApiRequest("DELETE", `${itemtype}/${id}`);
});

Cypress.Commands.add("searchWithAPI", (itemtype, values) => {
    let url = `search/${itemtype}`;

    let i = 0;
    for (const criteria of values) {
        url += i == 0 ? "?" : "&";
        url += `criteria[${i}][link]=${criteria.link}`;
        url += `&criteria[${i}][field]=${criteria.field}`;
        url += `&criteria[${i}][searchtype]=${criteria.searchtype}`;
        url += `&criteria[${i}][value]=${criteria.value}`;
        i++;
    }

    return cy.initApi().doApiRequest("GET", url).then((response) => {
        if (response.body.count == 0) {
            // The "data" key does not exist in the API response if there are 0 results.
            return [];
        }

        return response.body.data;
    });
});

/**
 * @memberof Cypress.Chainable.prototype
 * @method initApi
 * @description Initialize the API session
 */
Cypress.Commands.add("initApi", () => {
    if (api_token !== null) {
        return api_token;
    }

    return cy.request({
        auth: {
            'user': 'e2e_tests',
            'pass': 'glpi',
        },
        method: 'POST',
        url: '/apirest.php/initSession',
    }).then((response) => {
        api_token = response.body.session_token;
        return api_token;
    });
});

/**
 * @memberof Cypress.Chainable.prototype
 * @method doApiRequest
 * @description Perform an API request
 * @param {string} token API token
 * @param {string} method HTTP method
 * @param {string} endpoint API endpoint
 * @param {object} values Values to send in the request
 */
Cypress.Commands.add("doApiRequest", {prevSubject: true}, (token, method, endpoint, values) => {
    return cy.request({
        method: method,
        url: `/apirest.php/${encodeURI(endpoint)}`,
        body: values !== undefined ? {input: values} : null,
        headers: {
            'Session-Token': token,
        }
    }).then((response) => {
        return response;
    });
});

/**
 * @memberof Cypress.Chainable.prototype
 * @method enableDebugMode
 * @description Enable debug mode in GLPI
 */
Cypress.Commands.add('enableDebugMode', () => {
    if (Cypress.$('#debug-toolbar-applet').length > 0) {
        return;
    }

    cy.getCsrfToken().then((csrf) => {
        cy.request({
            method: 'POST',
            url: '/ajax/switchdebug.php',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'X-Glpi-Csrf-Token': csrf,
            },
            body: {
                'debug': 'on',
            },
        }).then(() => {
            cy.reload();
        });
    });
});

/**
 * @memberof Cypress.Chainable.prototype
 * @method disableDebugMode
 * @description Disable debug mode in GLPI
 */
Cypress.Commands.add('disableDebugMode', () => {
    if (Cypress.$('#debug-toolbar-applet').length === 0) {
        return;
    }

    cy.getCsrfToken().then((csrf) => {
        cy.request({
            method: 'POST',
            url: '/ajax/switchdebug.php',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
                'X-Glpi-Csrf-Token': csrf,
            },
            body: {
                'debug': 'off',
            },
        }).then(() => {
            cy.reload();
        });
    });
});

Cypress.Commands.add('openEntitySelector', () => {
    cy.intercept('GET', '/ajax/entitytreesons.php*').as('load_data_request');

    cy.findAllByText('Select the desired entity').should('not.be.visible');
    cy.get('body').type('{ctrl}{alt}e');
    cy.findAllByText('Select the desired entity').should('be.visible');

    cy.wait('@load_data_request');
});

// The "startToDrag" and "dropDraggedItemAfter" commands are not perfect as they
// simulate dragging by moving the DOM node using jquery.
//
// It would be better to trigger real events like mousedown/mousemove/moveup or
// drag/dragstart/drop but I was no able to get it working with the html5sortable lib.
//
// Note: this also require to manually add `data-glpi-draggable-item` to draggable
// items as the lib doesn't give us any way to find the draggable container afaik.
Cypress.Commands.add('startToDrag', {prevSubject: true}, (subject) => {
    cy.wrap(subject).find(`[draggable=true]`).as('drag_source');
});
Cypress.Commands.add('dropDraggedItemAfter', {prevSubject: true}, (subject) => {
    cy.wrap(subject).find(`[draggable=true]`).as('drag_destination');
    cy.getMany(["@drag_source", "@drag_destination"]).then(([$source, $destination]) => {
        // move manually
        $source.closest('[data-glpi-draggable-item]').detach().insertAfter(
            $destination.closest('[data-glpi-draggable-item]')
        );
    });
});

Cypress.Commands.add('checkAndCloseAlert', (text) => {
    cy.findAllByRole('alert').as('alerts');
    cy.get('@alerts').should('contain.text', text);
    cy.get('@alerts').findByRole('button', {name: 'Close'}).click();
});

Cypress.Commands.add('validateBreadcrumbs', (breadcrumbs) => {
    cy.findByRole('banner').findAllByRole('list').eq(0).as('breadcrumbs');
    breadcrumbs.forEach((expected_breadcrumb, i) => {
        cy.get('@breadcrumbs')
            .findAllByRole('link')
            .eq(i)
            .should('contains.text', expected_breadcrumb)
        ;
    });
});

Cypress.Commands.add('validateMenuIsActive', (name) => {
    cy.findByRole('complementary')
        .findByRole('link', {'name': name})
        .should('have.class', 'active')
    ;
});

Cypress.Commands.add('openAccordionItem', (container_label, item_label) => {
    cy.findAllByRole('region', {name: container_label})
        .findByRole('button', {name: item_label})
        .should('have.class', 'collapsed')
        .click()
    ;
});


Cypress.Commands.add('closeAccordionItem', (container_label, item_label) => {
    cy.findAllByRole('region', {name: container_label})
        .findByRole('button', {name: item_label})
        .should('not.have.class', 'collapsed')
        .click()
    ;
});

Cypress.Commands.add('updateTestUserSettings', (settings) => {
    return cy.updateWithAPI('User', 7, settings);
});

Cypress.Commands.add('getRowCells', {prevSubject: true}, (subject) => {
    cy.wrap(subject).closest('table').find('thead th').then($headers => {
        if ($headers.length === 0) {
            return undefined;
        }
        const headers = [];
        $headers.each((i, th) => headers.push(Cypress.$(th).text().trim()));

        // map row cells to an object where keys are headers texts
        if (subject.prop('tagName').toLowerCase() !== 'tr') {
            throw new Error('getRowCells can only be called on a <tr> element');
        }
        return cy.wrap(subject).findAllByRole('cell').then($cells => {
            if (headers !== undefined && $cells.length !== headers.length) {
                throw new Error(`Number of cells (${ $cells.length }) does not match number of headers (${ headers.length })`);
            }
            const result = {};
            $cells.each((i, cell) => {
                const key = headers !== undefined ? headers[i] : i;
                result[key] = cell;
            });
            return result;
        });
    });
});

Cypress.Commands.add('glpiAPIRequest', ({
    method = 'GET',
    endpoint = '',
    headers = {},
    body = null,
    allow_failure = false,
    username = 'e2e_tests',
    password = 'glpi',
}) => {
    function getRequestOptions() {
        return {
            method: method,
            url: `/api.php/${endpoint}`,
            headers: {
                'Authorization': `Bearer ${oauth_token}`,
                ...headers,
            },
            body: body,
            failOnStatusCode: !allow_failure,
        };
    }
    if (oauth_token === null) {
        cy.request({
            method: 'POST',
            url: '/api.php/token',
            headers: {
                'Content-Type': 'application/json',
            },
            body: {
                'grant_type': 'password',
                'client_id': '9246d35072ff62193330003a8106d947fafe5ac036d11a51ebc7ca11b9bc135e',
                'client_secret': 'd2c4f3b8a0e1f7b5c6a9d1e4f3b8a0e1f7b5c6a9d1e4f3b8a0e1f7b5c6a9d1',
                'username': username,
                'password': password,
                'scope': 'email user api graphql'
            }
        }).then((response) => {
            if (response.status !== 200) {
                throw new Error('Failed to get API token');
            }
            oauth_token = response.body.access_token;
            return cy.request(getRequestOptions());
        });
    } else {
        return cy.request(getRequestOptions());
    }
});
