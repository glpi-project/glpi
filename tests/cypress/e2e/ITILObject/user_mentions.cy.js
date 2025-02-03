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

describe("User Mentions", () => {
    const profileUrl = '/front/profile.form.php?id=4&forcetab=Profile$3';
    const ticketUrl = (ticket_id) => `/front/ticket.form.php?id=${ticket_id}`;
    const notificationUrl = '/front/setup.notification.php';
    const followupBlock = '#new-ITILFollowup-block';
    const answerButton = '.btn.answer-action';
    const contentTextarea = 'textarea[name="content"]';
    const dropdownUsersUrl = '**/ajax/getDropdownUsers.php';

    beforeEach(() => {
        cy.login();
        cy.createWithAPI('Ticket', {
            'name': 'Test ticket',
            'content': '',
            '_users_id_requester': 7,
            '_users_id_observer': 2,
            '_users_id_assign': 4,
        }).as('ticket_id');
    });

    function setNotificationSettings() {
        cy.visit(notificationUrl);
        cy.get('#use_notifications').then($checkbox => {
            if (!$checkbox.is(':checked')) {
                cy.get('#use_notifications').click();
                cy.get('.card-footer button').contains('Save').click();
            }
        });
        cy.get('#notifications_mailing').then($checkbox => {
            if (!$checkbox.is(':checked')) {
                cy.get('#notifications_mailing').click();
                cy.get('.card-footer button').contains('Save').click();
            }
        });
    }

    function setUserMentionsSetting(setting) {
        cy.visit(profileUrl);
        cy.get(':nth-child(5) > .table').within(() => {
            cy.get('select').select(setting, {force: true});
        });
        cy.get('button').contains('Save').click();
    }

    function checkUserMentions(expectedLength) {
        cy.get('@ticket_id').then((ticket_id) => {
            cy.visit(ticketUrl(ticket_id));
        });
        cy.get(answerButton).contains('Answer').click();
        cy.get(followupBlock).within(() => {
            cy.intercept('POST', dropdownUsersUrl).as('getDropdownUsers');
            cy.get(contentTextarea).awaitTinyMCE().type('@');
        });
        cy.wait('@getDropdownUsers').then(() => {
            cy.get('.tox-collection__group')
                .children()
                .should('have.length', expectedLength);
        });
    }

    function checkUserMentionsFull() {
        cy.get('@ticket_id').then((ticket_id) => {
            cy.visit(ticketUrl(ticket_id));
        });
        cy.get(answerButton).contains('Answer').click();
        cy.get(followupBlock).within(() => {
            cy.intercept('POST', dropdownUsersUrl).as('getDropdownUsers');
            cy.get(contentTextarea).awaitTinyMCE().type('@');
        });
        cy.wait('@getDropdownUsers').then(() => {
            cy.get('.tox-collection__group')
                .children()
                .should('have.length.greaterThan', 3);
        });
    }

    it('Enable Notifications', () => {
        setNotificationSettings();
    });

    it('Disable User Mentions', () => {
        setUserMentionsSetting('Disabled');
    });

    it('Check if User Mentions are disabled', () => {
        cy.get('@ticket_id').then((ticket_id) => {
            cy.visit(ticketUrl(ticket_id));
        });
        cy.get(answerButton).contains('Answer').click();
        cy.get(followupBlock).within(() => {
            cy.get(contentTextarea).awaitTinyMCE().type('@');
        });
        cy.get('.tox-collection__group').should('not.exist');
    });

    it('Full User Mentions', () => {
        setUserMentionsSetting('Full');
    });

    it('Check if User Mentions are full', () => {
        checkUserMentionsFull();
    });

    it('Restrict User Mentions', () => {
        setUserMentionsSetting('Restricted');
    });

    it('Check if User Mentions are restricted', () => {
        checkUserMentions(3);
    });
});
