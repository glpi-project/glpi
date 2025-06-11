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

describe('Dropdown item form question type', () => {
    let uuid;

    beforeEach(() => {
        uuid = new Date().getTime();

        cy.createWithAPI('Glpi\\Form\\Form', {
            'name'     : 'Tests form for the dropdown item form question type suite',
            'is_active': true,
        }).as('form_id');

        cy.createWithAPI('ITILCategory', {
            'name': `Root category ${uuid}`,
        }).then((category_id) => {
            cy.createWithAPI('ITILCategory', {
                'name': `Subroot category ${uuid}`,
                'itilcategories_id': category_id,
            }).as('subcategory_id').then((subcategory_id) => {
                cy.createWithAPI('ITILCategory', {
                    'name': `Subsubroot category ${uuid}`,
                    'itilcategories_id': subcategory_id,
                });
            });
        });

        cy.login();
        cy.changeProfile('Super-Admin');

        cy.get('@form_id').then((form_id) => {
            const tab = 'Glpi\\Form\\Form$main';
            cy.visit(`/front/form/form.form.php?id=${form_id}&forcetab=${tab}`);

            // Add a question
            cy.addQuestion('Test dropdown item question');

            // Change question type
            cy.getDropdownByLabelText('Question type').selectDropdownValue("Item");

            // Change the question sub-type to Dropdowns
            cy.getDropdownByLabelText('Question sub type').selectDropdownValue("Dropdowns");

            // Select the ITIL Category itemtype
            cy.getDropdownByLabelText('Select a dropdown type').selectDropdownValue("ITIL categories");
        });
    });

    it('can open advanced configuration dropdown', () => {
        // Open the advanced configuration dropdown
        cy.findByRole('button', { name: 'Advanced configuration' }).click();

        // Check that the dropdown is open
        cy.findByRole('menu', { name: 'Advanced configuration' }).should('be.visible');

        // Check that the dropdown contains the expected fields
        cy.findByRole('menu', { name: 'Advanced configuration' }).within(() => {
            cy.findByLabelText('Filter ticket categories').should('exist');
            cy.findByLabelText('Subtree root').should('exist');
            cy.findByLabelText('Limit subtree depth').should('exist');
        });
    });

    it('can set advanced configuration fields', () => {
        // Open the advanced configuration dropdown
        cy.findByRole('button', { name: 'Advanced configuration' }).click();

        cy.findByRole('menu', { name: 'Advanced configuration' }).within(() => {
            // Check default values of the fields
            cy.findByLabelText('Filter ticket categories').invoke('val').should('deep.equal', ['request', 'incident', 'change', 'problem']);

            // Set the filter ticket categories field
            cy.getDropdownByLabelText('Filter ticket categories').selectDropdownValue('Request categories');

            // Set the subtree root field
            cy.getDropdownByLabelText('Subtree root').selectDropdownValue(`»Subroot category ${uuid}`);

            // Set the limit subtree depth field
            cy.findByLabelText('Limit subtree depth').clear();
            cy.findByLabelText('Limit subtree depth').type('3');
        });

        // Save form
        cy.findByRole('button', { name: 'Save' }).click();
        cy.reload();

        // Focus question
        cy.findByRole('region', { name: 'Question details' }).click();

        // Open the advanced configuration dropdown again
        cy.findByRole('button', { name: 'Advanced configuration' }).click();

        // Check that the fields have been set correctly
        cy.findByRole('menu', { name: 'Advanced configuration' }).within(() => {
            cy.findByLabelText('Filter ticket categories').invoke('val').should('deep.equal', ['incident', 'change', 'problem']);
            cy.get('@subcategory_id').then((subcategory_id) => {
                cy.findByLabelText('Subtree root').should('have.value', subcategory_id);
            });
            cy.findByLabelText('Limit subtree depth').should('have.value', '3');
        });
    });

    it('"Filter ticket categories" field can only displayed for ITIL categories', () => {
        // Open the advanced configuration dropdown
        cy.findByRole('button', { name: 'Advanced configuration' }).click();

        cy.findByRole('menu', { name: 'Advanced configuration' }).within(() => {
            // Check that the "Filter ticket categories" field is visible
            cy.findByLabelText('Filter ticket categories').should('be.visible');
        });

        // Change the question sub-type to "Dropdowns"
        cy.getDropdownByLabelText('Select a dropdown type').selectDropdownValue("Locations");

        // Open the advanced configuration dropdown
        cy.findByRole('button', { name: 'Advanced configuration' }).click();

        cy.findByRole('menu', { name: 'Advanced configuration' }).within(() => {
            // Check that the "Filter ticket categories" field is visible
            cy.findByLabelText('Filter ticket categories').should('not.be.visible');
        });
    });

    it('only "Visible in the simplified interface" ITIL categories are displayed in self service interface', () => {
        // Create a new ITIL category that is not visible in the simplified interface
        cy.createWithAPI('ITILCategory', {
            'name': `Hidden for self service category ${uuid}`,
            'is_helpdeskvisible': false,
        }).as('hidden_category_id');

        // Create a new ITIL category that is visible in the simplified interface
        cy.createWithAPI('ITILCategory', {
            'name': `Visible in self service category ${uuid}`,
            'is_helpdeskvisible': true,
        }).as('visible_category_id');

        // Save form
        cy.findByRole('button', { name: 'Save' }).click();
        cy.checkAndCloseAlert('Item successfully updated');

        // Render the form in self service interface
        cy.changeProfile('Self-Service');
        cy.get('@form_id').then((form_id) => {
            cy.visit(`/Form/Render/${form_id}`);
        });

        // Check that the dropdown contains only the visible ITIL categories
        cy.getDropdownByLabelText('Test dropdown item question')
            .hasDropdownValue(`»Hidden for self service category ${uuid}`, false);
        cy.getDropdownByLabelText('Test dropdown item question')
            .hasDropdownValue(`»Visible in self service category ${uuid}`, true);

        // Change back to Super-Admin profile
        cy.changeProfile('Super-Admin');
        cy.reload();

        // Check that the dropdown contains both ITIL categories in the form
        cy.getDropdownByLabelText('Test dropdown item question')
            .hasDropdownValue(`»Hidden for self service category ${uuid}`, true);
        cy.getDropdownByLabelText('Test dropdown item question')
            .hasDropdownValue(`»Visible in self service category ${uuid}`, true);
    });
});
