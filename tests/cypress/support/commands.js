/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

import _ from 'lodash';

let api_token = null;

/**
 * @memberof Cypress.Chainable.prototype
 * @method login
 * @description Login to GLPI. This command will also reuse the session for subsequent calls when possible rather than logging in again.
 * @param {string} [username=e2e_tests] - Username
 * @param {string} [password=glpi] - Password
 * @returns Chainable
 */
Cypress.Commands.add('login', (username = 'e2e_tests', password = 'glpi') => {
    cy.session(
        username,
        () => {
            cy.blockGLPIDashboards();
            cy.visit('/', {
                headers: {
                    'Accept-Language': 'en-GB,en;q=0.9',
                }
            });
            cy.title().should('eq', 'Authentication - GLPI');
            cy.findByRole('textbox', {'name': "Login"}).type(username);
            cy.findByLabelText("Password", {exact: false}).type(password);
            cy.findByRole('checkbox', {name: "Remember me"}).check();
            // Select 'local' from the 'auth' dropdown
            cy.findByLabelText("Login source").select('local', { force: true });
            // TODO: should be
            // cy.findByRole('combobox', {name: "Login source"}).select2('local', { force: true });

            cy.findByRole('button', {name: "Sign in"}).click();
            // After logging in, the url should contain /front/central.php or /front/helpdesk.public.php
            cy.url().should('match', /\/front\/(central|helpdesk.public).php/);
        },
        {
            validate: () => {
                cy.getCookies().should('have.length.gte', 2).then((cookies) => {
                    // Should be two cookies starting with 'glpi_' and one of them should end with '_rememberme'
                    expect(cookies.filter((cookie) => cookie.name.startsWith('glpi_'))).to.have.length(2);
                    expect(cookies.filter((cookie) => cookie.name.startsWith('glpi_') && cookie.name.endsWith('_rememberme'))).to.have.length(1);
                });
            },
            cacheAcrossSpecs: true,
        },
    );
});

/**
 * @memberof Cypress.Chainable.prototype
 * @method logout
 * @description Logout of GLPI
 * @returns Chainable
 */
Cypress.Commands.add('logout', () => {
    cy.findByRole('link', {name: 'User menu'}).click();
    cy.findByRole('link', {name: 'Logout'}).click();
});

/**
 * @memberof Cypress.Chainable.prototype
 * @method changeProfile
 * @description Change the profile of the current user. Only supports the default GLPI profiles.
 * @param {string} profile - Profile to change to
 * @param {boolean} [verify=false] - Whether to verify that the profile was changed
 */
Cypress.Commands.add('changeProfile', (profile, verify = false) => {
    // If on about:blank, we need to get back to GLPI.
    // Can happen at the start of a test if the login restored an existing session and therefore no redirect happened.
    // With testIsolation, cypress starts each test on about:blank.
    cy.url().then((url) => {
        if (url === 'about:blank') {
            cy.blockGLPIDashboards();
            cy.visit('/');
        }
    });
    // Pattern for the profile link text to match exactly except ignoring surrounding whitespace
    const profile_pattern = new RegExp(`^\\s*${_.escapeRegExp(profile)}\\s*$`);
    // Check if we are already on the desired profile
    cy.get('header a.user-menu-dropdown-toggle').then(() => {
        if (!Cypress.$('header a.user-menu-dropdown-toggle > div > div:nth-of-type(1)').text().match(profile_pattern)) {
            // Look for all <a> with href containing 'newprofile=' and find the one with the text matching the desired profile
            cy.get('div.user-menu a[href*="newprofile="]').contains(profile_pattern).first().invoke('attr', 'href').then((href) => {
                cy.blockGLPIDashboards();
                cy.visit(href, {
                    headers: {
                        // Cypress doesn't send this by default and it causes a real headache with GLPI since it is always used when redirecting back after a profile/entity change.
                        // This causes e2e tests to randomly fail with the browser ending up on a /front/null page.
                        Referer: Cypress.config('baseUrl'),
                    }
                }).then(() => {
                    if (verify) {
                        cy.get('header a.user-menu-dropdown-toggle > div > div:nth-of-type(1)').contains(profile_pattern);
                    }
                });
            });
        }
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
    cy.wrap(subject).siblings('div.tox-tinymce').should('exist').find('iframe').iframe('about:srcdoc').find('p', {timeout: 10000});
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

    cy.get('#' + select_id).select(text, options);
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

Cypress.Commands.add("getMany", (names) => {
    const values = [];
    for (const arg of names) {
        cy.get(arg).then((value) => values.push(value));
    }
    return cy.wrap(values);
});

Cypress.Commands.add("createWithAPI", (url, values) => {
    return cy.initApi().doApiRequest("POST", url, values).then(response => {
        if (response.status !== 201) {
            throw new Error('Failed to create item');
        }

        return response.body.id;
    });
});

Cypress.Commands.add("updateWithAPI", (url, values) => {
    cy.initApi().doApiRequest("PUT", url, values);
});

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

Cypress.Commands.add("doApiRequest", {prevSubject: true}, (token, method, endpoint, values) => {
    return cy.request({
        method: method,
        url: '/apirest.php/' + encodeURI(endpoint),
        body: {input: values},
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

    cy.request({
        method: 'POST',
        url: '/ajax/switchdebug.php',
        body: {
            'debug': 'on',
        },
    }).then(() => {
        cy.reload();
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
    cy.request({
        method: 'POST',
        url: '/ajax/switchdebug.php',
        body: {
            'debug': 'off',
        },
    }).then(() => {
        cy.reload();
    });
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
    cy.findByRole('alert').as('alert');
    cy.get('@alert').should('contain.text', text);
    cy.get('@alert').findByRole('button', {name: 'Close'}).click();
});
