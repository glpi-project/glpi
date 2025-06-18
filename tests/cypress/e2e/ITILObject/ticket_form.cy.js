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

describe("Ticket Form", () => {
    beforeEach(() => {
        cy.login();
    });

    it('TODO List', () => {
        cy.createWithAPI('Ticket', {
            name: 'Test TODO List',
            content: 'Test TODO List',
        }).as('ticket_id');
        cy.get('@ticket_id').then((ticket_id) => cy.visit(`/front/ticket.form.php?id=${ticket_id}`));

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
            name: 'Test search solution',
            content: 'Test search solution',
        }).as('ticket_id');
        cy.createWithAPI('KnowbaseItem', {
            name: 'Test kb item for search solution test',
            answer: 'Test kb item for search solution test',
            description: 'Test kb item for search solution test',
        }).as('knowbaseitem_id');
        cy.get('@ticket_id').then((ticket_id) => {
            cy.visit(`/front/ticket.form.php?id=${ticket_id}`);
            cy.get('.timeline-buttons .main-actions button.dropdown-toggle-split').click();
            cy.findByText('Add a solution').click();
            cy.get('.itilsolution').within(() => {
                cy.findByLabelText('Search in the knowledge base').click();
            });
            cy.get('#modal_search_knowbaseitem').within(() => {
                cy.findByLabelText('Search…').should('have.value', 'Test search solution');
                cy.findAllByRole('listitem').should('have.length.at.least', 1);

                cy.findAllByTitle('Preview').first().click();
                cy.findByText('Subject').should('be.visible');
                cy.findByText('Content').should('be.visible');
                cy.findByText('Content').parent().next().invoke('text').should('not.be.empty').as('content');
                cy.findAllByRole('listitem').should('have.length', 0);
                cy.findByText('Back to results').click();

                cy.findAllByTitle('Use this entry').first().click();
            });
            cy.get('#modal_search_knowbaseitem').should('not.exist');
            cy.get('@content').then((content) => {
                cy.get('textarea[name="content"]').awaitTinyMCE().should('contain.text', content.trim());
            });
        });
    });

    it('Search for Followup', () => {
        cy.createWithAPI('Ticket', {
            name: 'Test search followup',
            content: 'Test search followup',
        }).as('ticket_id');
        cy.createWithAPI('KnowbaseItem', {
            name: 'Test kb item for search followup test',
            answer: 'Test kb item for search followup test',
            description: 'Test kb item for search followup test',
        }).as('knowbaseitem_id');
        cy.get('@ticket_id').then((ticket_id) => {
            cy.visit(`/front/ticket.form.php?id=${ticket_id}`);
            cy.findByText('Answer').click();
            cy.get('.itilfollowup').within(() => {
                cy.findByLabelText('Search in the knowledge base').click();
            });
            cy.get('#modal_search_knowbaseitem').within(() => {
                cy.findByLabelText('Search…').should('have.value', 'Test search followup');
                cy.findAllByRole('listitem').should('have.length.at.least', 1);

                cy.findAllByTitle('Preview').first().click();
                cy.findByText('Subject').should('be.visible');
                cy.findByText('Content').should('be.visible');
                cy.findByText('Content').parent().next().invoke('text').should('not.be.empty').as('content');
                cy.findAllByRole('listitem').should('have.length', 0);
                cy.findByText('Back to results').click();

                cy.findAllByTitle('Use this entry').first().click();
            });
            cy.get('#modal_search_knowbaseitem').should('not.exist');
            cy.get('@content').then((content) => {
                cy.get('textarea[name="content"]').awaitTinyMCE().should('contain.text', content.trim());
            });
        });
    });

    it('Validation Template', () => {
        const rand = Math.floor(Math.random() * 1000);
        cy.createWithAPI('Ticket', {
            name: 'Test validation template',
            content: 'Test validation template',
        }).as('ticket_id');
        cy.createWithAPI('ITILValidationTemplate', {
            name: `test ${rand}`,
            content: 'test content',
            entities_id: 1,
        }).as('validationtemplates_id');
        cy.createWithAPI('ITILValidationTemplate', {
            name: `test no approver ${rand}`,
            content: 'no approver test content ',
            entities_id: 1,
        }).as('validationtemplates_id2');

        cy.get('@ticket_id').then((ticket_id) => {
            cy.get('@validationtemplates_id').then((validationtemplates_id) => {
                cy.createWithAPI('ITILValidationTemplate_Target', {
                    itilvalidationtemplates_id: validationtemplates_id,
                    itemtype: 'User',
                    items_id: 2
                });
                cy.visit(`/front/ticket.form.php?id=${ticket_id}`);
                cy.findByRole('button', { name: 'View other actions' }).click();
                cy.findByText('Ask for approval').click();
                cy.get('.ITILValidation.show').within(() => {
                    cy.getDropdownByLabelText('Template').selectDropdownValue(`test ${rand}`);
                    cy.getDropdownByLabelText('Approver type').should('have.text', 'User');
                    cy.getDropdownByLabelText('Select a user').should('have.text', 'glpi');
                    cy.findByLabelText('Comment').awaitTinyMCE().should('contain.text', 'test content');
                });
                cy.visit(`/front/ticket.form.php?id=${ticket_id}`);
                cy.findByRole('button', { name: 'View other actions' }).click();
                cy.findByText('Ask for approval').click();
                cy.get('.ITILValidation.show').within(() => {
                    cy.getDropdownByLabelText('Template').selectDropdownValue(`test no approver ${rand}`);
                    cy.getDropdownByLabelText('Approver type').should('have.text', '-----');
                    cy.getDropdownByLabelText('Select a user').should('not.exist');
                    cy.findByLabelText('Comment').awaitTinyMCE().should('contain.text', 'no approver test content');
                });
            });
        });
    });

    it('Switch between validation templates', () => {
        const rand = Math.floor(Math.random() * 1000);
        cy.createWithAPI('Ticket', {
            name: 'Test validation template switch',
            content: 'Test validation template switch',
        }).as('ticket_id');

        // Create validation template with user approver
        cy.createWithAPI('ITILValidationTemplate', {
            name: `test validation template with user ${rand}`,
            content: 'test content',
            entities_id: 1,
        }).as('user_validationtemplates_id').then((validationtemplates_id) => {
            cy.createWithAPI('ITILValidationTemplate_Target', {
                itilvalidationtemplates_id: validationtemplates_id,
                itemtype: 'User',
                items_id: 2 // glpi user
            });
        });

        // Create validation template with group approver
        cy.createWithAPI('ITILValidationTemplate', {
            name: `test validation template with group ${rand}`,
            content: 'test content',
            entities_id: 1,
        }).as('group_validationtemplates_id').then((validationtemplates_id) => {
            cy.createWithAPI('Group', {
                name: `test group ${rand}`,
            }).then((group_id) => {
                cy.createWithAPI('ITILValidationTemplate_Target', {
                    itilvalidationtemplates_id: validationtemplates_id,
                    itemtype: 'Group',
                    items_id: group_id
                });
            });
        });

        // Create validation template with group user(s) approver
        cy.createWithAPI('ITILValidationTemplate', {
            name: `test validation template with group user ${rand}`,
            content: 'test content',
            entities_id: 1,
        }).as('group_user_validationtemplates_id').then((validationtemplates_id) => {
            cy.createWithAPI('Group', {
                name: `test group user ${rand}`,
            }).then((group_id) => {
                cy.createWithAPI('Group_User', {
                    groups_id: group_id,
                    users_id: 2 // glpi user
                });
                cy.createWithAPI('ITILValidationTemplate_Target', {
                    itilvalidationtemplates_id: validationtemplates_id,
                    itemtype: 'User',
                    items_id: 2, // glpi user
                    groups_id: group_id
                });
            });
        });

        // Create validation template with no approver
        cy.createWithAPI('ITILValidationTemplate', {
            name: `test validation template with no approver ${rand}`,
            content: 'no approver test content',
            entities_id: 1,
        }).as('no_approver_validationtemplates_id');

        cy.get('@ticket_id').then((ticket_id) => {
            cy.visit(`/front/ticket.form.php?id=${ticket_id}`);
            cy.findByRole('button', { name: 'View other actions' }).click();
            cy.findByText('Ask for approval').click();
            cy.get('.ITILValidation.show').within(() => {
                // Select user validation template
                cy.getDropdownByLabelText('Template').selectDropdownValue(`test validation template with user ${rand}`);
                cy.getDropdownByLabelText('Approver type').should('have.text', 'User');
                cy.getDropdownByLabelText('Select a user').should('have.text', 'glpi');
                cy.findByLabelText('Comment').awaitTinyMCE().should('contain.text', 'test content');

                // Switch to group validation template
                cy.getDropdownByLabelText('Template').selectDropdownValue(`test validation template with group ${rand}`);
                cy.getDropdownByLabelText('Approver type').should('have.text', 'Group');
                cy.getDropdownByLabelText('Select a group').should('have.text', `test group ${rand}`);
                cy.findByLabelText('Comment').awaitTinyMCE().should('contain.text', 'test content');

                // Switch to group user validation template
                cy.getDropdownByLabelText('Template').selectDropdownValue(`test validation template with group user ${rand}`);
                cy.getDropdownByLabelText('Approver type').should('have.text', 'Group user(s)');
                cy.getDropdownByLabelText('Select a group').should('have.text', `test group user ${rand}`);
                cy.getDropdownByLabelText('Select users').should('have.text', '×glpi');
                cy.findByLabelText('Comment').awaitTinyMCE().should('contain.text', 'test content');

                // Switch to no approver validation template
                cy.getDropdownByLabelText('Template').selectDropdownValue(`test validation template with no approver ${rand}`);
                cy.getDropdownByLabelText('Approver type').should('have.text', '-----');
                cy.getDropdownByLabelText('Select a user').should('not.exist');
                cy.getDropdownByLabelText('Select a group').should('not.exist');
                cy.findByLabelText('Comment').awaitTinyMCE().should('contain.text', 'no approver test content');
            });
        });
    });

    it('Enter key in requester field reloads new ticket form', () => {
        cy.visit(`/front/ticket.form.php`);

        // intercept form submit
        cy.intercept('POST', '/front/ticket.form.php').as('submit');

        // Need to manually trigger the enter key event as 'typing' {enter} is not matching real-life behavior
        cy.findByLabelText('Requester').next().find('.select2-search__field').type('tec');
        cy.get('.select2-results__option--highlighted').contains('tech');
        cy.findByLabelText('Requester').next().find('.select2-search__field').trigger('keydown', {
            key: 'Enter',
            code: 'Enter',
            which: 13,
        });

        // We should still be creating a new ticket, but the form should have been 'submitted'
        cy.wait('@submit').its('response.statusCode').should('eq', 200);
        cy.url().should('match', /\/front\/ticket\.form\.php$/);
    });
});
