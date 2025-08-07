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

describe('Helpdesk Ticket Form', () => {
    let ticket_missing_field_id;

    before(() => {
        cy.initApi().createWithAPI('TicketTemplate', {
            name: 'Template for not solveable warning',
            entities_id: 1
        }).then(template_id => {
            cy.initApi().createWithAPI('TicketTemplateMandatoryField', {
                tickettemplates_id: template_id,
                num: 87 // External ID field
            });
            cy.initApi().createWithAPI('Ticket', {
                name: 'Ticket with template for not solveable warning',
                tickettemplates_id: template_id,
                entities_id: 1
            }).then(ticket_id => {
                ticket_missing_field_id = ticket_id;
            });
        });
    });

    beforeEach(() => {
        cy.login();
        cy.changeProfile('Self-Service');
    });

    it('Not solveable warning not shown', () => {
        cy.on('uncaught:exception', (err) => {
            // FIXME: Work around known issue with adding event listener on non-existing collapse field panel button when ticket details are not shown
            if (err.message.includes('Cannot read properties of null (reading \'addEventListener\')')) {
                return false;
            }
        });
        cy.visit(`/front/ticket.form.php?id=${ticket_missing_field_id}`);
        cy.get('#itil-footer').within(() => {
            cy.get('.ti-alert-triangle').should('not.exist');
        });
    });
});
