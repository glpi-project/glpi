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

describe('Service catalog page', () => {

    function createActiveForm(name, category = 0) {
        cy.createFormWithAPI({
            'name': name,
            'description': "Lorem ipsum dolor sit amet, consectetur adipisicing elit.",
            'is_active': true,
            'forms_categories_id': category,
        }).as('form_id');

        cy.get('@form_id').then(form_id => {
            cy.createWithAPI('Glpi\\Form\\AccessControl\\FormAccessControl', {
                'forms_forms_id': form_id,
                'strategy'      : 'Glpi\\Form\\AccessControl\\ControlType\\AllowList',
                '_config'        : {
                    'user_ids': ['all'],
                    'groups_ids': [],
                    'profiles_ids': [],
                },
                'is_active'     : true,
            });
        });
    }

    beforeEach(() => {
        cy.login();
    });

    it('can pick a form in the service catalog', () => {
        const form_name = `Test form for service_catalog_page.cy.js ${(new Date()).getTime()}`;

        cy.changeProfile('Super-Admin');
        createActiveForm(form_name);

        cy.changeProfile('Self-Service', true);
        cy.visit('/ServiceCatalog');

        // Validate that the form is displayed correctly.
        cy.findByRole('region', {'name': form_name}).as('forms');
        cy.get('@forms').within(() => {
            cy.findByText(form_name).should('exist');
            cy.findByText("Lorem ipsum dolor sit amet, consectetur adipisicing elit.").should('exist');
        });

        // Go to form
        cy.get('@forms').click();
        cy.url().should('include', '/Form/Render');
    });

    it('can filter forms in the service catalog', () => {
        const form_name = `Test form for service_catalog_page.cy.js ${(new Date()).getTime()}`;

        cy.changeProfile('Super-Admin');
        createActiveForm(form_name);

        cy.changeProfile('Self-Service', true);
        cy.visit('/ServiceCatalog');
        cy.findByRole('region', {'name': form_name}).as('forms');
        cy.findByPlaceholderText('Search for forms...').as('filter_input');

        // Form should be visible as we have no filters yet
        cy.get('@forms').findByText(form_name).should('exist');

        // Filter out the form
        cy.get('@filter_input').type('nonexistent');
        cy.get('@forms').findByText(form_name).should('not.exist');

        // Filter in the form
        cy.get('@filter_input').clear();
        cy.get('@filter_input').type(form_name);
        cy.get('@forms').findByText(form_name).should('exist');

        // Check that an information message is displayed when there are no results
        cy.get('@filter_input').clear();
        cy.get('@filter_input').type("aaaaaaaaaaaaaaaaaaaaa");
        cy.findByText('No forms found').should('be.visible');
    });

    it('can pick a category in the service catalog', () => {
        const root_category_name = `Root category: ${(new Date()).getTime()}`;
        const child_category_name = `Child category: ${(new Date()).getTime()}`;
        const form_name = `Test form for service_catalog_page.cy.js ${(new Date()).getTime()}`;

        cy.createWithAPI('Glpi\\Form\\Category', {
            'name': root_category_name,
            'description': "Root category description.",
        }).as('root_category_id');
        cy.get('@root_category_id').then(root_category_id => {
            cy.createWithAPI('Glpi\\Form\\Category', {
                'name': child_category_name,
                'description': "Child category description.",
                'forms_categories_id': root_category_id,
            }).as('child_category_id');
        });
        cy.get('@child_category_id').then(child_category_id => {
            cy.changeProfile('Super-Admin');
            createActiveForm(form_name, child_category_id);
        });

        cy.changeProfile('Self-Service', true);
        cy.visit('/ServiceCatalog');

        // Validate that the root category is displayed correctly.
        cy.findByRole('region', {'name': root_category_name}).as('root_category');
        cy.get('@root_category').within(() => {
            cy.findByText(root_category_name).should('exist');
            cy.findByText("Root category description.").should('exist');
        });

        // Validate that the child category is displayed correctly.
        cy.get('@root_category').within(() => {
            cy.findByRole('region', {'name': child_category_name}).as('child_category');
            cy.get('@child_category').within(() => {
                cy.findByText(child_category_name).should('exist');
                cy.findByText("Child category description.").should('exist');
            });
        });

        // Form should be hidden until we click on the category
        cy.findByRole('region', {'name': form_name}).should('not.exist');
        cy.get('@child_category').click();
        cy.findByRole('region', {'name': form_name}).should('exist');
    });

    it('can use the service catalog on the central interface', () => {
        cy.changeProfile('Super-Admin');

        // Create a simple form
        const form_name = `Test form for service_catalog_page.cy.js ${(new Date()).getTime()}`;
        createActiveForm(form_name);
        cy.get('@form_id').visitFormTab('Form');
        cy.findByRole('button', {'name': 'Add a new question'}).click();
        cy.focused().type('Question 1');
        cy.findByRole('button', {'name': 'Save'}).click();
        cy.findByRole('alert')
            .should('contain.text', 'Item successfully updated')
        ;

        // Go to service catalog
        cy.visit('/ServiceCatalog');
        cy.validateBreadcrumbs(['Home', 'Assistance', 'Service catalog']);
        cy.validateMenuIsActive('Service catalog');

        // Go to our form
        cy.findByRole('region', {'name': form_name}).as('form');
        cy.get('@form').click();
        cy.url().should('include', '/Form/Render');
        cy.validateBreadcrumbs(['Home', 'Assistance', 'Service catalog']);
        cy.validateMenuIsActive('Service catalog');

        // Submit the form
        cy.findByRole('textbox', {'name': 'Question 1'}).type('Answer 1');
        cy.findByRole('button', {'name': 'Send form'}).click();
        cy.findByRole('alert')
            .should('contain.text', 'Item successfully created')
        ;
    });

    it('can navigate through the service catalog using the breadcrumbs', () => {
        function createCategory(name, category_id = 0) {
            return cy.createWithAPI('Glpi\\Form\\Category', {
                'name': name,
                'description': `${name} description.`,
                'forms_categories_id': category_id,
            });
        }

        const time = (new Date()).getTime();

        // Create a category tree with 3 levels
        cy.changeProfile('Super-Admin');
        createCategory(`Root Category ${time}`).then(category_id => {
            createCategory(`Child Category 1 ${time}`, category_id).then(category_id => {
                createCategory(`Child Category 2 ${time}`, category_id).then(category_id => {
                    createActiveForm('Form 1', category_id);
                });
            });
        });

        // Go to the service catalog
        cy.changeProfile('Self-Service', true);
        cy.visit('/ServiceCatalog');

        // Check breadcrumb
        cy.findByRole('navigation', {'name': 'Service catalog categories'}).within(() => {
            cy.findByRole('link', {'name': 'Service catalog'}).should('exist');
            cy.findByRole('link', {'name': `Root Category ${time}`}).should('not.exist');
            cy.findByRole('link', {'name': `Child Category 1 ${time}`}).should('not.exist');
            cy.findByRole('link', {'name': `Child Category 2 ${time}`}).should('not.exist');
        });

        // Go to the first child category
        cy.findByRole('region', {'name': `Root Category ${time}`}).within(() => {
            cy.findByRole('link', {'name': `Child Category 1 ${time}`}).click();
        });

        // Check breadcrumb
        cy.findByRole('navigation', {'name': 'Service catalog categories'}).within(() => {
            cy.findByRole('link', {'name': 'Service catalog'}).should('exist');
            cy.findByRole('link', {'name': `Root Category ${time}`}).should('not.exist');
            cy.findByRole('link', {'name': `Child Category 1 ${time}`}).should('exist');
            cy.findByRole('link', {'name': `Child Category 2 ${time}`}).should('not.exist');
        });

        // Check that the form is visible
        cy.findByRole('region', {'name': `Child Category 2 ${time}`}).within(() => {
            cy.findByRole('link', {'name': 'Form 1'}).should('exist');
        });

        // Go back to the root category
        cy.findByRole('link', {'name': 'Service catalog'}).click();

        // Check breadcrumb
        cy.findByRole('navigation', {'name': 'Service catalog categories'}).within(() => {
            cy.findByRole('link', {'name': 'Service catalog'}).should('exist');
            cy.findByRole('link', {'name': `Root Category ${time}`}).should('not.exist');
            cy.findByRole('link', {'name': `Child Category 1 ${time}`}).should('not.exist');
            cy.findByRole('link', {'name': `Child Category 2 ${time}`}).should('not.exist');
        });
    });

    it('can paginate through the service catalog', () => {
        const time = (new Date()).getTime();
        const forms_per_page = 12; // Default value from ServiceCatalogManager::ITEMS_PER_PAGE
        const total_forms = forms_per_page + 5; // Create more than one page worth of forms

        // Create enough forms to trigger pagination
        cy.changeProfile('Super-Admin');
        const formPromises = [];
        for (let i = 0; i < total_forms; i++) {
            formPromises.push(createActiveForm(`Form ${String.fromCharCode(65 + i)} ${time}`));
        }
        cy.wrap(Promise.all(formPromises));

        // Go to service catalog
        cy.changeProfile('Self-Service', true);
        cy.visit('/ServiceCatalog');

        // Filter forms to show only the ones created in this test
        cy.findByPlaceholderText('Search for forms...').type(time);

        // Verify first page content
        for (let i = 0; i < forms_per_page; i++) {
            cy.findByRole('region', {name: `Form ${String.fromCharCode(65 + i)} ${time}`}).should('exist');
        }
        // Verify items from second page are not visible
        cy.findByRole('region', {name: `Form ${String.fromCharCode(65 + forms_per_page)} ${time}`}).should('not.exist');

        // Test pagination controls visibility
        cy.findByRole('navigation', {name: 'Service catalog pages'}).within(() => {
            // First page active
            cy.findByRole('link', {name: '1'}).closest('li').should('have.class', 'active');
            // Second page link available
            cy.findByRole('link', {name: '2'}).should('exist');
            // Third page link not available
            cy.findByRole('link', {name: '3'}).should('not.exist');
            // Next/Last buttons enabled
            cy.findByRole('link', {name: 'Next page'}).closest('li').should('not.have.class', 'disabled');
            cy.findByRole('link', {name: 'Last page'}).closest('li').should('not.have.class', 'disabled');
            // Prev/First buttons disabled
            cy.findByRole('link', {name: 'Previous page'}).closest('li').should('have.class', 'disabled');
            cy.findByRole('link', {name: 'First page'}).closest('li').should('have.class', 'disabled');
        });

        // Go to second page
        cy.findByRole('link', {name: '2'}).click();

        // Verify second page content
        for (let i = 0; i < forms_per_page; i++) {
            cy.findByRole('region', {name: `Form ${String.fromCharCode(65 + i)} ${time}`}).should('not.exist');
        }
        for (let i = forms_per_page; i < total_forms; i++) {
            cy.findByRole('region', {name: `Form ${String.fromCharCode(65 + i)} ${time}`}).should('exist');
        }

        // Test pagination controls after page change
        cy.findByRole('navigation', {name: 'Service catalog pages'}).within(() => {
            // Second page active
            cy.findByRole('link', {name: '2'}).closest('li').should('have.class', 'active');
            // First page link available
            cy.findByRole('link', {name: '1'}).should('exist');
            // Third page link available
            cy.findByRole('link', {name: '3'}).should('not.exist');
            // Next/Last buttons disabled (on last page)
            cy.findByRole('link', {name: 'Next page'}).closest('li').should('have.class', 'disabled');
            cy.findByRole('link', {name: 'Last page'}).closest('li').should('have.class', 'disabled');
            // Prev/First buttons enabled
            cy.findByRole('link', {name: 'Previous page'}).closest('li').should('not.have.class', 'disabled');
            cy.findByRole('link', {name: 'First page'}).closest('li').should('not.have.class', 'disabled');
        });

        // Go back to first page using prev button
        cy.findByRole('link', {name: 'Previous page'}).click();

        // Verify first page content again
        for (let i = 0; i < forms_per_page; i++) {
            cy.findByRole('region', {name: `Form ${String.fromCharCode(65 + i)} ${time}`}).should('exist');
        }
        cy.findByRole('region', {name: `Form ${String.fromCharCode(65 + forms_per_page)} ${time}`}).should('not.exist');
    });
});
