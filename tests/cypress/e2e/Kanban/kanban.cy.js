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

describe('Kanban', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin');
    });
    it('Global project kanban loads', () => {
        cy.visit('/front/project.form.php?showglobalkanban=1');

        //TODO I am well aware this test is not using the "accessible" selectors, but that isn't the point.
        // I am aware that the Kanban needs an accessibility review, but I will do that as a separate task with user experience in mind,
        // not just blindly adding labels, roles or data attributes to everything just to make the test look "nicer".
        cy.get('#kanban-app').within(() => {
            // The loading spinner should disappear when the kanban is loaded
            cy.get('.kanban-container').should('be.visible');
            cy.findByRole('status').should('not.exist');

            cy.get('.kanban-toolbar').within(() => {
                cy.get('select[name="kanban-board-switcher"]').should('have.value', '-1');
                cy.get('div.search-input').should('be.visible');
                cy.findByRole('button', {name: 'Add column'}).should('be.visible');
                cy.get('button.kanban-extra-toolbar-options').should('be.visible');

                cy.get('.search-input-tag-input').click();
                cy.get('.search-input-popover').should('be.visible');
                cy.get('.search-input-popover').within(() => {
                    const expected_tags = [
                        'title', 'type', 'milestone', 'content', 'deleted', 'team', 'user', 'group', 'supplier', 'contact'
                    ];
                    expected_tags.forEach((tag) => {
                        cy.get(`li[data-tag="${tag}"]`).within(() => {
                            cy.get('b').contains(tag);
                            cy.findByRole('button', {name: '!'}).should('be.visible').should('have.attr', 'title', 'Exclude');
                            if (['title', 'content'].includes(tag)) {
                                cy.findByRole('button', {name: '#'}).should('be.visible').should('have.attr', 'title', 'Regex');
                            }
                            cy.get('.text-muted').should('not.be.empty');
                        });
                    });
                });
            });
        });
    });
});
