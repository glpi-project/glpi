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

describe('Self-Service Tickets', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Self-Service', true);
    });
    it('Create and view ticket', () => {
        cy.visit('/');

        cy.get('aside.sidebar').contains('Create a ticket').click();
        cy.injectAndCheckA11y();
        cy.url().should('include', '/front/helpdesk.public.php?create_ticket=1');

        cy.get('form.new-itil-object').within(() => {
            cy.get('input[name="name"]').type('My first ticket');
            cy.get('select[name="itilcategories_id"]').validateSelect2Loading();
            cy.get('select[data-actor-type="observer"]').validateSelect2Loading();
            // Location dropdown may not be present if there are no locations
            if (Cypress.$('select[name="locations_id"]').length) {
                cy.get('select[name="locations_id"]').validateSelect2Loading();
            }
            cy.get('textarea[name="content"]').awaitTinyMCE().then(() => {
                cy.get('textarea[name="content"]').type('This is my first ticket in GLPI. I sure hope it works.', {interactive: true});
            });
            cy.get('button').contains('Submit message').click();
            cy.url().should('include', '/front/tracking.injector.php').then(() => {
                cy.wrap(Cypress.$('main#page img')).should('have.attr', 'src', '/pics/ok.png');
            });
        });

        // A toast notification should be shown with the ticket number
        cy.get('#messages_after_redirect .toast-body').should('be.visible').within((toast) => {
            cy.wrap(toast).invoke('text').should('contain', 'Thank you for using our automatic helpdesk system');
            cy.wrap(toast).find('a').click();
        });

        cy.url().should('include', '/front/ticket.form.php');

        // Check the available tabs
        cy.get('#tabspanel .nav-item').its('length').should('be.gte', 4);
        cy.get('#tabspanel .nav-item').contains('Ticket').click();
        cy.get('#tabspanel .nav-item').contains('Ticket').should('have.class', 'active');

        // Verify the ticket name and content are in the timeline
        cy.get('div.timeline-item:nth-child(1)').should('have.class', 'ITILContent');
        cy.get('div.timeline-item.ITILContent .card-title').should(f => expect(f.text().trim()).equals('My first ticket'));
        cy.get('div.timeline-item.ITILContent .rich_text_container').should(f => expect(f.text().trim()).equals('This is my first ticket in GLPI. I sure hope it works.'));

        // Check the details panel
        cy.get('#item-main').within(() => {
            cy.get('label').contains('Entity').next().within(() => {
                cy.get('.glpi-badge').should(badge => expect(badge.text().trim()).to.not.be.empty);
            });
            cy.get('label').contains('Opening date').next().within(() => {
                cy.get('input[type="text"]').invoke('val').should('match', /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/);
                cy.get('input[type="text"]').should('be.disabled');
                cy.get('button').click();
                cy.get('.flatpickr-calendar.open').should('not.exist');
            });
            cy.get('label').contains('Type').next().within(() => {
                cy.get('.select2-selection__rendered').invoke('text').should(t => expect(t.trim()).to.be.oneOf(['Incident', 'Request']));
                cy.get('select').should('be.disabled');
            });
            // No technician assigned and no updates, so category can still be changed
            cy.get('label').contains('Category').next().within(() => {
                cy.get('select').should('not.be.disabled');
            });
            cy.get('label').contains('Status').next().within((field) => {
                cy.wrap(field).should(f => expect(f.text().trim()).to.not.be.empty);
                cy.wrap(field).find('i').should('have.class', 'itilstatus');
            });
            // No technician assigned and no updates, so urgency can still be changed
            cy.get('label').contains('Urgency').next().within(() => {
                cy.get('.select2-selection__rendered').invoke('text').should(t => expect(t.trim()).to.be.oneOf([
                    'Very low', 'Low', 'Medium', 'High', 'Very high'
                ]));
                cy.get('select').should('not.be.disabled');
            });
            cy.get('label').contains('Priority').next().within(() => {
                cy.get('.select2-selection__rendered').invoke('text').should(t => expect(t.trim()).to.be.oneOf([
                    'Very low', 'Low', 'Medium', 'High', 'Very high', 'Major'
                ]));
                cy.get('select').should('be.disabled');
            });
            // Location dropdown may not be present if there are no locations
            if (Cypress.$('select[name="locations_id"]').length) {
                cy.get('label').contains('Location').next().within(() => {
                    cy.get('select').should('be.disabled');
                });
            }
            cy.get('label').contains('Approval').next().within((field) => {
                cy.wrap(field).invoke('text').should(t => expect(t.trim()).to.be.oneOf([
                    'Waiting for approval', 'Refused', 'Granted', 'Not subject to approval'
                ]));
            });
            cy.get('label').contains('External ID').next().within(() => {
                cy.get('input').should('be.disabled');
            });
        });

        cy.get('#heading-actor').closest('.accordion-item').within(() => {
            cy.findByLabelText("Requester").should('be.disabled');
            cy.findByLabelText("Observer").should('be.disabled');
            cy.findByLabelText("Assigned to").should('be.disabled');
        });

        // Statistics
        cy.get('#tabspanel .nav-item').contains('Statistics').should('exist');
        // Approvals should not be visible
        cy.get('#tabspanel .nav-item').contains('Approvals').should('not.exist');
        // Knowledge base
        cy.get('#tabspanel .nav-item').contains('Knowledge base').should('exist');
        // Items
        cy.get('#tabspanel .nav-item').contains('Items').should('exist');
        // All
        cy.get('#tabspanel .nav-item').contains('All').should('exist');
    });
});
