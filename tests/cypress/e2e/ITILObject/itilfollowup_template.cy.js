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

describe("ITIL Followup Template Preservation", () => {
    let test_ticket_id;
    let empty_template_name;
    let test_pending_reason_name;
    let with_content_template_name;

    before(() => {
        const unique_id = Date.now();
        empty_template_name = `Empty Template ${unique_id}`;
        const pending_reason_name = `Test Pending Reason ${unique_id}`;

        cy.createWithAPI('Ticket', {
            name: `Test ticket for followup templates ${unique_id}`,
            content: 'Test ticket',
        }).then(ticket_id => {
            test_ticket_id = ticket_id;
        });

        cy.createWithAPI('ITILFollowupTemplate', {
            name: empty_template_name,
            content: '',
        });

        test_pending_reason_name = pending_reason_name;
        cy.createWithAPI('PendingReason', {
            name: pending_reason_name,
            comment: 'For e2e testing',
        });

        const with_content_name = `Template with Content ${unique_id}`;
        with_content_template_name = with_content_name;
        cy.createWithAPI('ITILFollowupTemplate', {
            name: with_content_name,
            content: '<p>Template test content</p>',
        });
    });

    beforeEach(() => {
        if (empty_template_name) {
            cy.wrap(empty_template_name).as('empty_template_name');
        }
        if (test_pending_reason_name) {
            cy.wrap(test_pending_reason_name).as('pending_reason_name');
        }
        if (with_content_template_name) {
            cy.wrap(with_content_template_name).as('with_content_template_name');
        }

        cy.login();
        cy.visit(`/front/ticket.form.php?id=${test_ticket_id}`);
        cy.findByRole('button', { name: 'Answer' }).should('be.visible');
    });

    it("preserves user's pending reason when applying template without pending reason", () => {
        cy.findByRole('button', { name: 'Answer' }).click();
        cy.get('.itilfollowup').should('be.visible');

        cy.get('input[name="pending"][type="checkbox"]').then($checkbox => {
            if (!$checkbox.is(':checked')) {
                cy.wrap($checkbox).check({ force: true });
            }
        });

        cy.get('[id^="pending-reasons-setup-"]').should('be.visible');

        cy.get('@pending_reason_name').then((pending_name) => {
            cy.get('.itilfollowup').first().within(() => {
                cy.get('select[name="pendingreasons_id"]')
                    .next('.select2-container')
                    .find('[role=combobox]')
                    .selectDropdownValue(pending_name);
            });

            cy.get('.itilfollowup').first().within(() => {
                cy.get('select[name="pendingreasons_id"]')
                    .parent()
                    .find('.select2-selection__rendered')
                    .should('contain', pending_name);
            });
        });

        cy.get('@empty_template_name').then((template_name) => {
            cy.get('.itilfollowup').first().within(() => {
                cy.get('select[name="itilfollowuptemplates_id"]')
                    .next('.select2-container')
                    .find('[role=combobox]')
                    .selectDropdownValue(template_name);
            });
        });

        cy.waitForNetworkIdle(500);

        cy.get('@pending_reason_name').then((pending_name) => {
            cy.get('.itilfollowup').first().within(() => {
                cy.get('select[name="pendingreasons_id"]')
                    .parent()
                    .find('.select2-selection__rendered')
                    .should('contain', pending_name);
            });
        });

        cy.get('.itilfollowup').first().within(() => {
            cy.get('input[name="pending"][type="checkbox"]').should('be.checked');
        });
    });

    it("preserves user's content when applying template without content", () => {
        cy.findByRole('button', { name: 'Answer' }).click();
        cy.get('.itilfollowup').should('be.visible');

        const user_content = 'User typed content';
        cy.get('.itilfollowup').first().within(() => {
            cy.get('.tox-tinymce').should('be.visible');
            cy.get('.tox-edit-area iframe').then($iframe => {
                const $body = $iframe.contents().find('body');
                cy.wrap($body).clear();
                cy.wrap($body).type(user_content);
            });
        });

        cy.get('.itilfollowup').first().within(() => {
            cy.get('.tox-edit-area iframe').then($iframe => {
                const $body = $iframe.contents().find('body');
                cy.wrap($body).should('contain', user_content);
            });
        });

        cy.get('@empty_template_name').then((template_name) => {
            cy.get('.itilfollowup').first().within(() => {
                cy.get('select[name="itilfollowuptemplates_id"]')
                    .next('.select2-container')
                    .find('[role=combobox]')
                    .selectDropdownValue(template_name);
            });
        });

        cy.waitForNetworkIdle(500);

        cy.get('.itilfollowup').first().within(() => {
            cy.get('.tox-edit-area iframe').then($iframe => {
                const $body = $iframe.contents().find('body');
                cy.wrap($body).should('contain', user_content);
            });
        });
    });

    it("replaces user's content when applying template with content", () => {
        cy.findByRole('button', { name: 'Answer' }).click();
        cy.get('.itilfollowup').should('be.visible');

        const user_content = 'User initial content';
        cy.get('.itilfollowup').first().within(() => {
            cy.get('.tox-tinymce').should('be.visible');
            cy.get('.tox-edit-area iframe').then($iframe => {
                const $body = $iframe.contents().find('body');
                cy.wrap($body).clear();
                cy.wrap($body).type(user_content);
            });
        });

        cy.get('.itilfollowup').first().within(() => {
            cy.get('.tox-edit-area iframe').then($iframe => {
                const $body = $iframe.contents().find('body');
                cy.wrap($body).should('contain', user_content);
            });
        });

        const template_content = 'Template test content';
        cy.get('@with_content_template_name').then((template_name) => {
            cy.get('.itilfollowup').first().within(() => {
                cy.get('select[name="itilfollowuptemplates_id"]')
                    .next('.select2-container')
                    .find('[role=combobox]')
                    .selectDropdownValue(template_name);
            });
        });

        cy.waitForNetworkIdle(500);

        cy.get('.itilfollowup').first().within(() => {
            cy.get('.tox-edit-area iframe').then($iframe => {
                const $body = $iframe.contents().find('body');
                cy.wrap($body).should('contain', template_content);
                cy.wrap($body).should('not.contain', user_content);
            });
        });
    });
});
