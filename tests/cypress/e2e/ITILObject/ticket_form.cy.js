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

describe("Ticket Form", () => {
    beforeEach(() => {
        cy.login();
    });
    it('TODO List', () => {
        cy.visit('/front/ticket.php');
        cy.get('td[data-searchopt-content-id="1"] a').first().click();

        cy.get('.itil-timeline').should('exist').then((container) => {
            // Append fake content to the timeline
            container.append('<div class="timeline-item mb-3 ITILContent">Fake content</div>');
            container.append('<div class="timeline-item mb-3 ITILSolution">Fake content</div>');
            container.append('<div class="timeline-item mb-3 ITILFollowup">Fake content</div>');
            container.append('<div class="timeline-item mb-3 ITILTask info">Fake content</div>');
            container.append('<div class="timeline-item mb-3 ITILTask todo">Fake content</div>');
            container.append('<div class="timeline-item mb-3 ITILTask done">Fake content</div>');
            container.append('<div class="timeline-item mb-3 Document_Item">Fake content</div>');
            container.append('<div class="timeline-item mb-3 Log">Fake content</div>');
            container.append('<div class="timeline-item mb-3 KnowbaseItemComment">Fake content</div>');
            container.append('<div class="timeline-item mb-3 ITILReminder">Fake content</div>');

            cy.get('button.view-timeline-todo-list').click();
            cy.get('.timeline-item.ITILContent').should('not.be.visible');
            cy.get('.timeline-item.ITILSolution').should('not.be.visible');
            cy.get('.timeline-item.ITILFollowup').should('not.be.visible');
            cy.get('.timeline-item.ITILTask.todo').should('be.visible');
            cy.get('.timeline-item.ITILTask.done').should('be.visible');
            cy.get('.timeline-item.ITILTask.info').should('not.be.visible');
            cy.get('.timeline-item.Document_Item').should('not.be.visible');
            cy.get('.timeline-item.Log').should('not.be.visible');
            cy.get('.timeline-item.KnowbaseItemComment').should('not.be.visible');
            cy.get('.timeline-item.ITILReminder').should('not.be.visible');

            cy.get('button.view-timeline-todo-list').click();
            cy.get('.timeline-item.ITILContent').should('be.visible');
            cy.get('.timeline-item.ITILSolution').should('be.visible');
            cy.get('.timeline-item.ITILFollowup').should('be.visible');
            cy.get('.timeline-item.ITILTask.todo').should('be.visible');
            cy.get('.timeline-item.ITILTask.done').should('be.visible');
            cy.get('.timeline-item.ITILTask.info').should('be.visible');
            cy.get('.timeline-item.Document_Item').should('be.visible');
            cy.get('.timeline-item.Log').should('be.visible');
            cy.get('.timeline-item.KnowbaseItemComment').should('be.visible');
            cy.get('.timeline-item.ITILReminder').should('be.visible');
        });
    });

    it('Search for Solution', () => {
        cy.createWithAPI('Ticket', {
            name: 'apple',
            content: 'apple',
        }).as('ticket_id');
        cy.get('@ticket_id').then((ticket_id) => {
            cy.visit(`/front/ticket.form.php?id=${ticket_id}`);
            cy.get('.timeline-buttons .main-actions button.dropdown-toggle-split').click();
            cy.findByText('Add a solution').click();
            cy.findByLabelText('Search a solution').click();
            cy.get('#modal_searchSolution').within(() => {
                cy.findByLabelText('Search…').should('have.value', 'apple');
                cy.findAllByRole('listitem').should('have.length.at.least', 2);

                cy.findAllByTitle('Preview').first().click();
                cy.findByText('Subject').should('be.visible');
                cy.findByText('Content').should('be.visible');
                cy.findByText('Content').parent().next().invoke('text').should('not.be.empty').as('content');
                cy.findAllByRole('listitem').should('have.length', 0);
                cy.findByText('Back to results').click();

                cy.findAllByTitle('Use as a solution').first().click();
            });
            cy.get('#modal_searchSolution').should('not.exist');
            cy.get('@content').then((content) => {
                cy.get('textarea[name="content"]').awaitTinyMCE().should('contain.text', content.trim());
            });
        });
    });
});
