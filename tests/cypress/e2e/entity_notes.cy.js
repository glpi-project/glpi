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

function addEntityNote(visible_on_tickets = false) {
    cy.findByRole('button', { name: 'Add a note' }).click();
    cy.findByLabelText('Content').awaitTinyMCE().type('This is a test note');

    if (visible_on_tickets) {
        cy.findByRole('checkbox', { name: 'Visible on tickets' }).check();
    }

    cy.findByRole('button', { name: 'Add' }).click();
}

function addEntityNoteWithAttachment(visible_on_tickets = false) {
    cy.findByRole('button', { name: 'Add a note' }).click();
    cy.findByLabelText('Content').awaitTinyMCE().type('This is a test note');

    if (visible_on_tickets) {
        cy.findByRole('checkbox', { name: 'Visible on tickets' }).check();
    }

    cy.get('input[type="file"]').selectFile('fixtures/uploads/bar.png');
    cy.findByRole('progressbar').should('contain', 'Upload successful');
    cy.get('input[type="file"]').selectFile('fixtures/uploads/bar.txt');
    cy.findByRole('progressbar').should('contain', 'Upload successful');
    cy.findByRole('progressbar').should('not.exist');

    cy.findByRole('button', { name: 'Add' }).click();
}

describe('Entity notes', () => {
    beforeEach(() => {
        cy.login();

        // Create entity
        cy.createWithAPI('Entity', {
            name: `EntityNotesTest ${Date.now()}`,
        }).as('entity_id').then((entity_id) => {
            cy.visit(`/front/entity.form.php?id=${entity_id}`);
            cy.findByRole('tab', { name: 'Notes' }).click();
        });
    });

    it('can add entity notes', () => {
        addEntityNote();

        cy.findByRole('button', { name: /^#\d+ - \d{4}-\d{2}-\d{2} \d{2}:\d{2}$/ }).click();
        cy.findByRole('tabpanel').find('.rich_text_container').should('contain', 'This is a test note');
    });

    it('can delete entity notes', () => {
        addEntityNote();

        // Delete the note
        cy.findByRole('button', { name: /^#\d+ - \d{4}-\d{2}-\d{2} \d{2}:\d{2}$/ }).click();
        cy.findByRole('button', { name: 'Delete' }).click();

        // Check that the note is deleted
        cy.findByRole('button', { name: /^#\d+ - \d{4}-\d{2}-\d{2} \d{2}:\d{2}$/ }).should('not.exist');
    });

    it('can edit entity notes', () => {
        addEntityNote();

        // Edit the note
        cy.findByRole('button', { name: /^#\d+ - \d{4}-\d{2}-\d{2} \d{2}:\d{2}$/ }).click();
        cy.findByRole('button', { name: 'Edit' }).click();
        cy.findByRole('dialog').within(() => {
            cy.findByLabelText('Content').awaitTinyMCE().clear();
            cy.findByLabelText('Content').awaitTinyMCE().type('This is an edited test note');
            cy.findByRole('button', { name: 'Update' }).click();
        });

        // Check that the note is edited
        cy.findByRole('dialog').should('not.exist');
        cy.findByRole('tabpanel').find('.rich_text_container').should('contain', 'This is an edited test note');
    });

    it('can edit entity notes with attachments', () => {
        addEntityNoteWithAttachment();

        // Edit the note
        cy.findByRole('button', { name: /^#\d+ - \d{4}-\d{2}-\d{2} \d{2}:\d{2}$/ }).click();
        cy.findByRole('button', { name: 'Edit' }).click();

        cy.findByRole('dialog').within(() => {
            cy.findByLabelText('Content').awaitTinyMCE().clear();
            cy.findByLabelText('Content').awaitTinyMCE().type('This is an edited test note');
            cy.findByRole('button', { name: 'Update' }).click();
        });

        // Check that the note is edited
        cy.findByRole('dialog').should('not.exist');
        cy.findByRole('tabpanel').find('.rich_text_container').should('contain', 'This is an edited test note');
    });

    it('entity note can be visible on tickets', () => {
        addEntityNote(true);
        cy.get('@entity_id').then((entity_id) => {
            // Create a ticket
            cy.createWithAPI('Ticket', {
                name: `Ticket ${Date.now()}`,
                entities_id: entity_id,
            }).then((ticket_id) => {
                cy.visit(`/front/ticket.form.php?id=${ticket_id}`);
                cy.findByRole('region', { name: 'Notes' }).should('exist').within(() => {
                    cy.findByRole('generic', { name: 'Entity notes 1' })
                        .find('p').should('contain', 'This is a test note');
                });
            });
        });
    });

    it('entity note attachments are visible on tickets', () => {
        addEntityNoteWithAttachment(true);

        cy.get('@entity_id').then((entity_id) => {
            // Create a ticket
            cy.createWithAPI('Ticket', {
                name: `Ticket ${Date.now()}`,
                entities_id: entity_id,
            }).then((ticket_id) => {
                cy.visit(`/front/ticket.form.php?id=${ticket_id}`);
                cy.findByRole('region', { name: 'Notes' }).should('exist').within(() => {
                    cy.findByRole('alert').within(() => {
                        cy.findByRole('figure').should('exist');
                        cy.findByRole('link', { name: 'File extension bar.txt' }).should('exist');
                    });
                });
            });
        });
    });

    it('can add an attachment to an entity note', () => {
        addEntityNote();

        // Add an attachment
        cy.findByRole('button', { name: /^#\d+ - \d{4}-\d{2}-\d{2} \d{2}:\d{2}$/ }).click();
        cy.findByRole('button', { name: 'Edit' }).click();

        cy.findByRole('dialog').within(() => {
            cy.get('input[type="file"]').selectFile('fixtures/uploads/bar.png');
            cy.findByRole('progressbar').should('contain', 'Upload successful');
            cy.get('input[type="file"]').selectFile('fixtures/uploads/bar.txt');
            cy.findByRole('progressbar').should('contain', 'Upload successful');
            cy.findByRole('progressbar').should('not.exist');
            cy.findByRole('button', { name: 'Update' }).click();
        });

        // Check that the attachment is added
        cy.findByRole('button', { name: /^#\d+ - \d{4}-\d{2}-\d{2} \d{2}:\d{2}$/ }).click();
        cy.findByRole('figure').should('exist');
        cy.findByRole('link', { name: 'File extension bar.txt' }).should('exist');
    });
});
