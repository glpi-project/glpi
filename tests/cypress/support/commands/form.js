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

Cypress.Commands.add('createFormWithAPI', (
    fields = {
        name: "My test form",
    }
) => {
    return cy.createWithAPI('Glpi\\Form\\Form', fields).then((form_id) => {
        return form_id;
    });
});

Cypress.Commands.add('visitFormTab', {prevSubject: true}, (
    form_id,
    tab_name
) => {
    const fully_qualified_tabs = new Map([
        ['Form', 'Glpi\\Form\\Form\\Form$main'],
        ['Policies', 'Glpi\\Form\\AccessControl\\FormAccessControl$1'],
        ['Destinations', 'Glpi\\Form\\Destination\\FormDestination$1'],
        ['ServiceCatalog', 'Glpi\\Form\\ServiceCatalog\\ServiceCatalog$1'],
    ]);
    const tab = fully_qualified_tabs.get(tab_name);

    return cy.visit(`/front/form/form.form.php?id=${form_id}&forcetab=${tab}`);
});

Cypress.Commands.add('saveFormEditorAndReload', () => {
    cy.findByRole('button', {'name': 'Save'}).click();
    cy.findByRole('alert')
        .should('contain.text', 'Item successfully updated')
    ;
    cy.reload();

    // Wait for the form to be reloaded
    cy.findByRole('button', { 'name': 'Save' }).should('exist');
});

Cypress.Commands.add('addQuestion', (name) => {
    cy.findByRole('button', {'name': 'Add a question'}).click();
    cy.focused().type(name); // Question name is focused by default
});

Cypress.Commands.add('addSection', (name) => {
    cy.findByRole('button', {'name': 'Add a section'}).click();
    cy.focused().type(name); // Section name is focused by default
});

Cypress.Commands.add('getFormSections', (form_id) => {
    return cy.initApi().doApiRequest('GET', `Glpi\\Form\\Form/${form_id}/Glpi\\Form\\Section`).then((response) => {
        return response.body;
    });
});

// TODO: refactor on playwright; too many args
Cypress.Commands.add('addQuestionToDefaultSectionWithAPI', (
    form_id,
    name,
    type,
    vertical_rank,
    horizontal_rank = 0,
    default_value = null,
    extra_data = null,
    is_mandatory = false,
) => {
    cy.getFormSections(form_id).then((sections) => {
        const section_id = sections[0].id;
        const question = {
            forms_sections_id: section_id,
            name             : name,
            type             : type,
            vertical_rank    : vertical_rank,
            horizontal_rank  : horizontal_rank,
            default_value    : default_value,
            extra_data       : extra_data,
            is_mandatory     : is_mandatory,
        };

        return cy.createWithAPI('Glpi\\Form\\Question', question).then((question_id) => {
            return question_id;
        });
    });
});

Cypress.Commands.add('changeQuestionType', {prevSubject: true}, (question, new_type) => {
    cy.wrap(question).within(() => {
        cy.getDropdownByLabelText('Question type').selectDropdownValue(new_type);
        cy.wrap(question).find('[data-glpi-loading="true"]').should('not.exist');
    });
});

Cypress.Commands.add('changeQuestionSubType', {prevSubject: true}, (question, new_sub_type) => {
    cy.wrap(question).within(() => {
        cy.getDropdownByLabelText('Question sub type').selectDropdownValue(new_sub_type);
        cy.wrap(question).find('[data-glpi-loading="true"]').should('not.exist');
    });
});

/**
 * Helper method to quickly import a given form from the fixtures directory.
 * This avoid having to build complex forms with the API or the UI, both of which
 * are not very convenient.
 */
Cypress.Commands.add('importForm', (filename) => {
    // TODO: maybe create an API command so we can bypass the UI for this and make it even faster
    cy.visit('/Form/Import');
    cy.findByLabelText("Select your file").selectFile(`fixtures/forms/${filename}`);

    // Preview
    cy.findByRole('button', {'name': "Preview import"}).click();
    cy.findAllByRole('row')
        .eq(1)
        .findByText("Ready to be imported")
        .should('exist')
    ;

    // Import and return id
    cy.findByRole('button', {'name': "Import"}).click();
    cy.findAllByRole('row')
        .eq(1)
        .findByRole("link")
        .invoke('attr', 'href')
        .then(href => {
            const url = new URL(href, "http://localhost");
            return url.searchParams.get("id");
        })
    ;
});
