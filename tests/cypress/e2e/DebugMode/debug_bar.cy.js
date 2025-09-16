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

describe("Debug Bar", () => {
    beforeEach(() => {
        cy.login();
        cy.visit('/front/computer.php');
        cy.changeProfile('Super-Admin');
        cy.enableDebugMode();
    });
    after(() => {
        cy.disableDebugMode();
    });

    it('Debug bar controls', () => {
        cy.get('#debug-toolbar-applet').should('exist').within(() => {
            cy.findByRole('button', {name: 'Toggle debug bar'}).should('be.disabled');
            cy.get('#debug-toolbar-expanded-content').should('not.be.visible');

            cy.findByRole('button', {name: 'Toggle debug content area'}).click();
            cy.get('#debug-toolbar-expanded-content').should('be.visible');

            cy.findByRole('button', {name: 'Close'}).click();
            cy.get('.debug-toolbar-content').should('not.be.visible');

            cy.findByRole('button', {name: 'Toggle debug bar'}).click();
            cy.get('.debug-toolbar-content').should('be.visible');
            cy.get('#debug-toolbar-expanded-content').should('be.visible');

            cy.findByRole('button', {name: 'Toggle debug content area'}).click();
            cy.get('#debug-toolbar-expanded-content').should('not.be.visible');
        });
    });

    it('Server performance widget', () => {
        cy.get('#debug-toolbar-applet').should('exist').within(() => {
            cy.get('.debug-toolbar-widget[data-glpi-debug-widget-id="server_performance"]')
                .should('exist')
                .invoke('text').should('match', /\d+\s+ms\s+using\s+[\d.]+\s+MiB/);
            cy.get('.debug-toolbar-widget[data-glpi-debug-widget-id="server_performance"]').click();
            cy.get('#debug-toolbar-expanded-content').should('be.visible').within(() => {
                cy.get('.datagrid-title').contains('Initial Execution Time').next().invoke('text').should('match', /\d+\s+ms/);
                cy.get('.datagrid-title').contains('Total Execution Time').next().invoke('text').should('match', /\d+\s+ms/);
                cy.get('.datagrid-title').contains('Memory Usage').next().invoke('text').should('match', /\d+.+\s+MiB\s+\/\s+[\d.]+\s+MiB/);
                cy.get('.datagrid-title').contains('Memory Peak').next().invoke('text').should('match', /\d+.+\s+MiB\s+\/\s+[\d.]+\s+MiB/);
            });
        });
    });

    it('SQL requests', () => {
        cy.get('#debug-toolbar-applet').should('exist').within(() => {
            cy.get('.debug-toolbar-widget[data-glpi-debug-widget-id="sql"]')
                .should('exist')
                .invoke('text').should('match', /\d+\s+requests/);
            cy.get('.debug-toolbar-widget[data-glpi-debug-widget-id="sql"]').click();
            cy.get('#debug-toolbar-expanded-content').should('be.visible').within(() => {
                // 1st column should be alphanumeric
                cy.get('#debug-sql-request-table tr td:nth-child(1)').each(($el) => {
                    expect($el.text()).to.match(/^[a-z0-9]+$/);
                });
                // 2nd column should be numeric
                cy.get('#debug-sql-request-table tr td:nth-child(2)').each(($el) => {
                    expect($el.text()).to.match(/^\d+$/);
                });
                // 4th column should be a float ms value
                cy.get('#debug-sql-request-table tr td:nth-child(4)').each(($el) => {
                    expect($el.text()).to.match(/^\d+\.\d+\sms$/);
                });
                // 5th column should be a number
                cy.get('#debug-sql-request-table tr td:nth-child(5)').each(($el) => {
                    expect($el.text()).to.match(/^\d+$/);
                });
            });
        });
    });

    it('HTTP requests', () => {
        cy.get('#debug-toolbar-applet').should('exist').within(() => {
            cy.get('.debug-toolbar-widget[data-glpi-debug-widget-id="requests"]')
                .should('exist')
                .invoke('text').should('match', /\d+\s+requests/);
            cy.get('.debug-toolbar-widget[data-glpi-debug-widget-id="requests"]').click();

            cy.get('#debug-toolbar-expanded-content').within(() => {
                cy.get('#debug-requests-table tr').should('have.length.gte', 2);
                cy.get('#debug-requests-table tbody tr:first-child').should('have.class', 'table-active');

                // Summary Tab
                cy.get('.right-panel .nav .nav-link').contains('Summary').should('have.class', 'active');
                cy.get('.request-details-content-area').within(() => {
                    cy.get('h1').contains(/^Request Summary/).should('be.visible');
                    cy.get('td').contains(/Initial Execution Time:\s+\d+ ms/).should('be.visible');
                    cy.get('td').contains(/Memory Usage:\s+[\d.]+\s+MiB\s+\/\s+[\d.]+\s+MiB/).should('be.visible');
                    cy.get('td').contains(/Memory Peak:\s+[\d.]+\s+MiB\s+\/\s+[\d.]+\s+MiB/).should('be.visible');
                    cy.get('td').contains(/SQL Requests:\s+\d+/).should('be.visible');
                    cy.get('td').contains(/SQL Duration:\s+[\d.]+ ms/).should('be.visible');
                });

                // Globals Tab
                cy.get('.right-panel .nav .nav-link').contains('Globals').click();
                cy.get('.request-details-content-area').within(() => {
                    cy.get('.nav-item').contains('POST').click();
                    cy.get('.tab-pane[id^="debugpost"] .monaco-editor-container').should('exist');
                    cy.get('.nav-item').contains('GET').click();
                    cy.get('.tab-pane[id^="debugget"] .monaco-editor-container').should('exist');
                    cy.get('.nav-item').contains('SESSION').click();
                    cy.get('.tab-pane[id^="debugsession"] .monaco-editor-container').should('exist');
                    cy.get('.nav-item').contains('SERVER').click();
                    cy.get('.tab-pane[id^="debugserver"] .monaco-editor-container').should('exist');
                });

                // Profiler
                cy.get('.right-panel .nav .nav-link').contains('Profiler').click();
                cy.get('.request-details-content-area').within(() => {
                    cy.get('tr[data-profiler-section-id] > td[data-prop="category"]').each(($el) => {
                        cy.wrap($el).find('.category-badge').should('exist');
                    });
                    cy.get('tr[data-profiler-section-id] > td[data-prop="duration"]').each(($el) => {
                        expect($el.text()).to.match(/\d+\sms/);
                    });
                    cy.get('tr[data-profiler-section-id] > td[data-prop="percent_of_parent"]').each(($el) => {
                        expect($el.text()).to.match(/[\d.]%/);
                    });
                    cy.get('tr[data-profiler-section-id] > td[data-prop="auto_ended"]').each(($el) => {
                        expect($el.text()).to.match(/(Yes|No)/);
                    });
                });

                // SQL Tab
                cy.get('.right-panel .nav .nav-link').contains('SQL').click();
                cy.get('.request-details-content-area').within(() => {
                    cy.get('#debug-sql-request-table tr td:nth-of-type(1)').each(($el) => {
                        expect($el.text()).to.match(/^\d+$/);
                    });
                    cy.get('#debug-sql-request-table tr td:nth-of-type(3)').each(($el) => {
                        expect($el.text()).to.match(/^\d+\.\d+\sms$/);
                    });
                });
            });
        });
    });

    it('Client performance', () => {
        cy.get('#debug-toolbar-applet').should('exist').within(() => {
            cy.get('.debug-toolbar-widget[data-glpi-debug-widget-id="client_performance"]').click();
            cy.get('#debug-toolbar-expanded-content').should('be.visible').within(() => {
                cy.get('.datagrid-title').contains('Time to first paint').next().invoke('text').should('match', /\d+\s+ms/);
                cy.get('.datagrid-title').contains('Time to DOM interactive').next().invoke('text').should('match', /\d+\s+ms/);
                cy.get('.datagrid-title').contains('Time to DOM complete').next().invoke('text').should('match', /\d+\s+ms/);
                cy.get('.datagrid-title').contains('Total resources').next().invoke('text').should('match', /^\d+$/);
                cy.get('.datagrid-title').contains('Total resources size').next().invoke('text').should('match', /[\d.]+\s+MiB/);
                cy.get('.datagrid-title').contains('Used JS Heap').next().invoke('text').should('match', /[\d.]+\s+MiB/);
                cy.get('.datagrid-title').contains('Total JS Heap').next().invoke('text').should('match', /[\d.]+\s+MiB/);
                cy.get('.datagrid-title').contains('JS Heap Limit').next().invoke('text').should('match', /[\d.]+\s+MiB/);
            });

            cy.get('.debug-toolbar-widget[data-glpi-debug-widget-id="client_performance"]')
                .contains(/[\d.]\sms/);
        });
    });

    it('Search options', () => {
        cy.get('#debug-toolbar-applet').should('exist').within(() => {
            cy.get('.debug-toolbar-widget[data-glpi-debug-widget-id="search_options"]')
                .should('exist');
            cy.get('.debug-toolbar-widget[data-glpi-debug-widget-id="search_options"]').click();
            cy.get('#debug-toolbar-expanded-content').should('be.visible').within(() => {
                cy.get('.search-opts-table').should('not.exist');
                cy.intercept({
                    pathname: '/ajax/debug.php',
                    query: {
                        action: 'get_search_options',
                    },
                }).as('searchOptions');

                cy.findByLabelText('Itemtype').should('be.visible');
                cy.findByLabelText('Itemtype').select('Profile'); // Should always be available since it is required for the session, so already autoloaded.
                cy.wait('@searchOptions').its('response.statusCode').should('eq', 200);
                cy.get('.search-opts-table').should('exist');

                cy.findByRole('button', {name: 'Toggle manual input'}).click();
                cy.findByLabelText('Itemtype').clear();
                cy.findByLabelText('Itemtype').type('User{enter}'); // Should always be available since it is required for the session, so already autoloaded.
                cy.wait('@searchOptions').its('response.statusCode').should('eq', 200);
            });
        });
    });

    it('Theme switcher', () => {
        cy.get('#debug-toolbar-applet').should('exist').within(() => {
            cy.get('.debug-toolbar-widget[data-glpi-debug-widget-id="theme_switcher"]')
                .should('exist');
            cy.get('.debug-toolbar-widget[data-glpi-debug-widget-id="theme_switcher"]').click();
            cy.get('#debug-toolbar-expanded-content').should('be.visible').within(() => {
                cy.findByRole('combobox', {name: 'Palette'}).should('be.visible').select('midnight');
                cy.root().closest('html').invoke('attr', 'data-glpi-theme').should('eq', 'midnight');
                cy.root().closest('html').invoke('attr', 'data-glpi-theme-dark').should('eq', '1');

                cy.findByRole('combobox', {name: 'Palette'}).should('be.visible').select('auror');
                cy.root().closest('html').invoke('attr', 'data-glpi-theme').should('eq', 'auror');
                cy.root().closest('html').invoke('attr', 'data-glpi-theme-dark').should('eq', '0');
            });
        });
    });
});
