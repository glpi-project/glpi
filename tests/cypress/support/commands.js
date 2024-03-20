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

/**
 * @memberof cy
 * @method login
 * @description Login to GLPI. This command will also reuse the session for subsequent calls when possible rather than logging in again.
 * @param {string} [username=e2e_tests] - Username
 * @param {string} [password=glpi] - Password
 * @returns Chainable
 */

/**
 * @memberof cy
 * @method waitForInputs
 * @description Wait for libraries to replace inputs with their own components
 * @returns Chainable
 */

/**
 * @memberof cy
 * @method selectDate
 * @description Select a date in a flatpickr input
 * @param {string} date - Date to select
 * @param {boolean} interactive - Whether to use the flatpickr calendar or type the date
 * @returns Chainable
 */

/**
 * @memberof cy
 * @method changeProfile
 * @description Change the profile of the current user. Only supports the default GLPI profiles.
 */


Cypress.Commands.add('login', (username = 'e2e_tests', password = 'glpi') => {
    cy.session(
        username,
        () => {
            cy.visit('/');
            cy.title().should('eq', 'Authentication - GLPI');
            cy.get('#login_name').type(username);
            cy.get('input[type="password"]').type(password);
            cy.get('#login_remember').check();
            // Select 'local' from the 'auth' dropdown
            cy.get('select[name="auth"]').select('local', { force: true });

            cy.get('button[type="submit"]').click();
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
        }
    );
});

Cypress.Commands.add('changeProfile', (profile) => {
    // If on about:blank, we need to get back to GLPI.
    // Can happen at the start of a test if the login restored an existing session and therefore no redirect happened.
    // With testIsolation, cypress starts each test on about:blank.
    cy.url().then((url) => {
        if (url === 'about:blank') {
            cy.visit('/');
        }
    });
    // Look for all <a> with href containing 'newprofile=' and find the one with the text matching the desired profile
    cy.get('div.user-menu a[href*="newprofile="]').contains(profile).first().invoke('attr', 'href').then((href) => {
        cy.visit(href);
    });
});

/**
 * Wait for libraries to replace inputs with their own components
 */
Cypress.Commands.add('waitForInputs', () => {
    // eslint-disable-next-line cypress/no-unnecessary-waiting
    cy.wait(1000);
});

Cypress.Commands.overwrite('type', (originalFn, subject, text, options) => {
    // If the subject is a textarea, see if there is a TinyMCE editor for it
    // If there is, set the content of the editor instead of the textarea
    if (subject.is('textarea')) {
        cy.get(`textarea[name="${subject.attr('name')}"]`).invoke('attr', 'id').then((textarea_id) => {
            cy.window().then((win) => {
                if (win.tinymce.get(textarea_id)) {
                    if (options !== undefined && options.interactive) {
                        // Use 'should' off the 'window()' to wait for the required property to be set.
                        cy.window().should('satisfy', () => {
                            return typeof win.tinymce.get(textarea_id).dom.doc !== 'undefined';
                        });
                        cy.wrap(win.tinymce.get(textarea_id).dom.doc).within(() => {
                            cy.get('#tinymce[contenteditable="true"]').should('exist');
                            cy.get('#tinymce p').type(text, options);
                        });
                    } else {
                        win.tinymce.get(textarea_id).setContent(text);
                    }
                    return;
                }
                originalFn(subject, text, options);
            });
        });
        return;
    }
    return originalFn(subject, text, options);
});

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

