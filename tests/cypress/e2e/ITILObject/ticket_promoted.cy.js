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

describe("Ticket Promoted", () => {

    const content = 'Ticket content';
    const requesterID = 2;
    const techID = 4;
    const projectTaskName = 'Project Name';
    const categoryName = 'CatName';

    const checkDescriptionInput = () => {
        cy.get('#itil-form').first().within(() => {
            cy.get('textarea[name="content"]')
                .invoke('val')
                .should('contains', content);
        });
    };

    const checRequesterInput = () => {
        cy.get('span[data-actortype="requester"]').should('have.attr', 'data-itemtype', 'User').should('have.attr', 'data-items-id', requesterID);
    };

    const checTechInput = (itemtype, itemID) => {
        cy.get('span[data-actortype="assign"]')
            .filter(`[data-itemtype="${itemtype}"][data-items-id="${itemID}"]`)
            .should('exist');
    };

    const checkTitle = () => {
        cy.get('input[name="name"]').should('have.value', projectTaskName);
    };

    beforeEach(() => {
        cy.login();
        cy.createWithAPI('Ticket', {
            'name': 'Test ticket',
            'content': '',
        }).as('ticket_id').then((ticket_id) => {
            cy.createWithAPI('ITILFollowup', {
                'content': content,
                'items_id': ticket_id,
                'itemtype': 'Ticket',
                'users_id': requesterID,
            }).as('followup_id');
            cy.createWithAPI('Group', {
                'name': 'Group1',
            }).as('group_id').then((group_id) => {
                cy.createWithAPI('TicketTask', {
                    'content': content,
                    'tickets_id': ticket_id,
                    'users_id': requesterID,
                    'users_id_tech': techID,
                    'groups_id_tech': group_id,
                }).as('task_id');
            });
            cy.createWithAPI('ITILCategory', {
                'name': `${categoryName}-${ticket_id}`,
            }).as('itilcategory_id');
        });

        cy.createWithAPI('Project', {
            'content': 'Project',
        }).as('project_id').then((project_id) => {
            cy.createWithAPI('ProjectTask', {
                'name': projectTaskName,
                'content': content,
                'projects_id': project_id,
            }).as('projecttask_id');
        });
    });

    it('promote followup and change category', () => {
        cy.get('@followup_id').then((followup_id) => {
            cy.visit(`/front/ticket.form.php?_promoted_fup_id=${followup_id}`);
        });
        checkDescriptionInput();
        checRequesterInput();
        cy.get('@ticket_id').then((ticket_id) => {
            cy.getDropdownByLabelText('Category').selectDropdownValue(`»${categoryName}-${ticket_id}`);
        });
        checkDescriptionInput();
        checRequesterInput();
    });

    it('promote task and change category', () => {
        cy.get('@task_id').then((task_id) => {
            cy.visit(`/front/ticket.form.php?_promoted_task_id=${task_id}`);
        });
        checTechInput('User', techID);
        checkDescriptionInput();
        checRequesterInput();
        cy.get('@group_id').then((group_id) => {
            checTechInput('Group', group_id);
        });
        cy.get('@ticket_id').then((ticket_id) => {
            cy.getDropdownByLabelText('Category').selectDropdownValue(`»${categoryName}-${ticket_id}`);
        });
        checTechInput('User', techID);
        checkDescriptionInput();
        checRequesterInput();
    });

    it('promote project task and change category', () => {
        cy.get('@projecttask_id').then((projecttask_id) => {
            cy.visit(`/front/ticket.form.php?_projecttasks_id=${projecttask_id}`);
        });
        checkTitle();
        checkDescriptionInput();
        cy.get('@ticket_id').then((ticket_id) => {
            cy.getDropdownByLabelText('Category').selectDropdownValue(`»${categoryName}-${ticket_id}`);
        });
        checkTitle();
        checkDescriptionInput();
    });
});
