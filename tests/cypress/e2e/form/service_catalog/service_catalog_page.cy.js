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

describe('Service catalog page', () => {

    function createActiveForm(name, category = 0, description = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit.') {
        cy.createFormWithAPI({
            'name': name,
            'description': description,
            'is_active': true,
            'forms_categories_id': category,
        }).as('form_id');
    }

    function createKnowledgeBaseItem(name, options = {}) {
        const defaults = {
            'name': name,
            'answer': `Content for ${name}`,
            'description': `Description for ${name}`,
            'is_faq': 1,
            'show_in_service_catalog': 1,
            'forms_categories_id': 0, // Root by default
            'is_pinned': 0,
            '_visibility': {
                '_type': 'Entity',
                'entities_id': 1,
                'is_recursive': 1,
            }
        };

        const data = { ...defaults, ...options };
        return cy.createWithAPI('KnowbaseItem', data);
    }

    /**
     * Finds an item in the service catalog by navigating through paginated results.
     *
     * This function first navigates to the first page of the service catalog, then
     * searches through each page sequentially until it finds the specified item.
     * If the item is not found on the current page, it automatically clicks "Next page"
     * and continues searching until the item is found or all pages are exhausted.
     *
     * @param {string} item_name - The name of the item to search for in the service catalog.
     *                            This should match the aria-label attribute of the target region element.
     * @returns {Cypress.Chainable} A Cypress chainable that resolves to the found item element
     *                             with role="region" and aria-label matching the item_name.
     */
    function findItemInServiceCatalog(item_name) {
        function searchCurrentPage() {
            return cy.get('body').then($body => {
                // Check for both aria-label and aria-labelledby attributes
                const hasAriaLabel = $body.find(`[aria-label="${item_name}"]`).length > 0;
                const hasAriaLabelledBy = $body.find(`[aria-labelledby]`).filter((index, element) => {
                    const labelledbyId = element.getAttribute('aria-labelledby');
                    if (labelledbyId) {
                        const labelElement = $body.find(`#${labelledbyId}`);
                        return labelElement && labelElement.text().trim() === item_name;
                    }
                    return false;
                }).length > 0;

                if (hasAriaLabel || hasAriaLabelledBy) {
                    return cy.findByRole('region', { 'name': item_name });
                } else {
                    const $next = $body.find(`[aria-label="Next page"]`);
                    if ($next.length && !$next.closest('li').hasClass('disabled')) {
                        cy.findByRole('navigation', { 'name': 'Service catalog pages' })
                            .findAllByRole('link').parent().filter('.active').then($activeLink => {
                                const currentPage = parseInt($activeLink.text(), 10);

                                // Click the "Next page" button
                                cy.wrap($next).click();

                                // Wait for the next page link item was active
                                cy.findByRole('navigation', { 'name': 'Service catalog pages' })
                                    .findByRole('link', { 'name': currentPage + 1 }).parent().should('have.class', 'active');
                            });

                        return searchCurrentPage();
                    } else {
                        return cy.findByRole('region', { 'name': item_name });
                    }
                }
            });
        }

        return cy.get('body').then($body => {
            const $first = $body.find(`[aria-label="First page"]`);
            if ($first.length && !$first.closest('li').hasClass('disabled')) {
                cy.wrap($first).click();

                // Wait for the first page link item to be active
                cy.findByRole('navigation', { 'name': 'Service catalog pages' })
                    .findByRole('link', { 'name': 1 }).parent().should('have.class', 'active');
            }

            return searchCurrentPage();
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
        findItemInServiceCatalog(form_name).as('forms');
        cy.get('@forms').within(() => {
            cy.findByText(form_name).should('exist');
            cy.findByText("Lorem ipsum dolor sit amet, consectetur adipisicing elit.").should('exist');
        });

        // Go to form
        cy.get('@forms').click();
        cy.url().should('include', '/Form/Render');
    });

    it('can pick a knowledge base item in the service catalog', () => {
        const kb_name = `Test KB for service_catalog_page.cy.js ${(new Date()).getTime()}`;

        cy.changeProfile('Super-Admin');
        createKnowledgeBaseItem(kb_name);

        cy.changeProfile('Self-Service', true);
        cy.visit('/ServiceCatalog');

        // Validate that the KB item is displayed correctly
        findItemInServiceCatalog(kb_name).as('kb_item');
        cy.get('@kb_item').within(() => {
            cy.findByText(kb_name).should('exist');
            cy.findByText(`Description for ${kb_name}`).should('exist');
        });

        // Go to KB item
        cy.get('@kb_item').click();
        cy.url().should('include', '/front/helpdesk.faq.php');
        cy.findByText(kb_name).should('exist');
        cy.findByText(`Content for ${kb_name}`).should('exist');
    });

    it('can filter forms in the service catalog', () => {
        const form_name = `Test form for service_catalog_page.cy.js ${(new Date()).getTime()}`;

        cy.changeProfile('Super-Admin');
        createActiveForm(form_name);

        cy.changeProfile('Self-Service', true);
        cy.visit('/ServiceCatalog');
        findItemInServiceCatalog(form_name).as('forms');
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

    it('can filter knowledge base items in the service catalog', () => {
        const timestamp = (new Date()).getTime();
        const kb_name_1 = `Test Technical KB ${timestamp}`;
        const kb_name_2 = `Test User Guide ${timestamp}`;
        const kb_name_3 = `Test Hidden KB ${timestamp}`;

        cy.changeProfile('Super-Admin');
        createKnowledgeBaseItem(kb_name_1, {
            'description': 'Technical content about servers',
            'answer': 'Server configuration details'
        });
        createKnowledgeBaseItem(kb_name_2, {
            'description': 'User documentation',
            'answer': 'How to use the application'
        });
        createKnowledgeBaseItem(kb_name_3, {
            'description': 'Hidden documentation',
            'show_in_service_catalog': 0
        });

        cy.changeProfile('Self-Service', true);
        cy.visit('/ServiceCatalog');
        cy.findByPlaceholderText('Search for forms...').as('filter_input');

        // Both visible KB items should be displayed
        findItemInServiceCatalog(kb_name_1).should('exist');
        findItemInServiceCatalog(kb_name_2).should('exist');
        // Hidden KB item should not be displayed
        findItemInServiceCatalog(kb_name_3).should('not.exist');

        // Filter for technical content
        cy.get('@filter_input').type('technical');
        cy.waitForNetworkIdle(1000);
        findItemInServiceCatalog(kb_name_1).should('exist');
        findItemInServiceCatalog(kb_name_2).should('not.exist');

        // Filter for user documentation
        cy.get('@filter_input').clear();
        cy.get('@filter_input').type('user');
        cy.waitForNetworkIdle(1000);
        findItemInServiceCatalog(kb_name_1).should('not.exist');
        findItemInServiceCatalog(kb_name_2).should('exist');

        // Filter for content in the answer
        cy.get('@filter_input').clear();
        cy.get('@filter_input').type('application');
        cy.waitForNetworkIdle(1000);
        findItemInServiceCatalog(kb_name_1).should('not.exist');
        findItemInServiceCatalog(kb_name_2).should('exist');
    });

    it('can pick a category in the expanded service catalog', () => {
        const root_category_name = `Root category: ${(new Date()).getTime()}`;
        const child_category_name = `Child category: ${(new Date()).getTime()}`;
        const form_name = `Test form for service_catalog_page.cy.js ${(new Date()).getTime()}`;

        // Enable expanded service catalog
        cy.updateWithAPI('Entity', 1, {'expand_service_catalog': true});

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
        findItemInServiceCatalog(root_category_name).as('root_category');
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
        findItemInServiceCatalog(form_name).should('not.exist');
        findItemInServiceCatalog(child_category_name).click();
        cy.findByRole('region', {'name': form_name}).should('exist');
    });

    it('can categorize knowledge base items in the expanded service catalog', () => {
        const timestamp = (new Date()).getTime();
        const category_name = `KB Category ${timestamp}`;
        const kb_name_1 = `KB in category ${timestamp}`;
        const kb_name_2 = `KB at root ${timestamp}`;
        const kb_name_3 = `KB in a nested category ${timestamp}`;

        // Enable expanded service catalog
        cy.updateWithAPI('Entity', 1, {'expand_service_catalog': true});

        cy.changeProfile('Super-Admin');

        cy.createWithAPI('Glpi\\Form\\Category', {
            'name': category_name,
            'description': "Category for KB items",
        }).then(category_id => {
            createKnowledgeBaseItem(kb_name_1, {
                'forms_categories_id': category_id
            });
            createKnowledgeBaseItem(kb_name_2);

            cy.createWithAPI('Glpi\\Form\\Category', {
                'name': `Nested ${category_name}`,
                'description': "Category for KB items",
                'forms_categories_id': category_id,
            }).then(category_id => {
                createKnowledgeBaseItem(kb_name_3, {
                    'forms_categories_id': category_id
                });
            });
        });

        cy.changeProfile('Self-Service', true);
        cy.visit('/ServiceCatalog');

        // Root KB item should be visible
        findItemInServiceCatalog(kb_name_2).should('exist');
        // Categorized KB item should be visible at root
        findItemInServiceCatalog(kb_name_1).should('exist');
        // Category should be visible
        findItemInServiceCatalog(category_name).should('exist');
        // Nested category should be visible
        cy.findByRole('region', { 'name': `Nested ${category_name}` }).should('exist');
        // Categorized KB item should not be visible here
        findItemInServiceCatalog(kb_name_3).should('not.exist');

        // Navigate to nested category
        findItemInServiceCatalog(`Nested ${category_name}`).click();
        cy.waitForNetworkIdle(1000);

        // Now the categorized KB item should be visible
        findItemInServiceCatalog(kb_name_3).should('exist');

        // But other items should not be visible
        findItemInServiceCatalog(kb_name_1).should('not.exist');
        findItemInServiceCatalog(kb_name_2).should('not.exist');
    });

    it('can use the service catalog on the central interface', () => {
        cy.changeProfile('Super-Admin');

        // Create a simple form
        const time = (new Date()).getTime();
        const form_name = `Test form for service_catalog_page.cy.js ${time}`;
        createActiveForm(form_name);
        cy.get('@form_id').visitFormTab('Form');
        cy.findByRole('button', {'name': 'Add a question'}).click();
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
        cy.findByPlaceholderText('Search for forms...').type(time);
        findItemInServiceCatalog(form_name).as('form');
        cy.get('@form').click();
        cy.url().should('include', '/Form/Render');
        cy.validateBreadcrumbs(['Home', 'Assistance', 'Service catalog']);
        cy.validateMenuIsActive('Service catalog');

        // Submit the form
        cy.findByRole('textbox', {'name': 'Question 1'}).type('Answer 1');
        cy.findByRole('button', {'name': 'Submit'}).click();
        cy.findByRole('alert')
            .should('contain.text', 'Item successfully created')
        ;
    });

    it('can navigate through the expanded service catalog using the breadcrumbs', () => {
        function createCategory(name, category_id = 0) {
            return cy.createWithAPI('Glpi\\Form\\Category', {
                'name': name,
                'description': `${name} description.`,
                'forms_categories_id': category_id,
            });
        }

        // Enable expanded service catalog
        cy.updateWithAPI('Entity', 1, {'expand_service_catalog': true});

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

        // Filter forms to show only the ones created in this test
        cy.findByPlaceholderText('Search for forms...').type(time);

        // Check breadcrumb
        cy.findByRole('navigation', { 'name': 'Service catalog categories' }).within(() => {
            cy.findByRole('link', { 'name': 'Service catalog' }).should('exist');
            cy.findByRole('link', { 'name': `Root Category ${time}` }).should('not.exist');
            cy.findByRole('link', { 'name': `Child Category 1 ${time}` }).should('not.exist');
            cy.findByRole('link', { 'name': `Child Category 2 ${time}` }).should('not.exist');
        });

        // Go to the first child category
        cy.findByRole('region', { 'name': `Root Category ${time}` }).within(() => {
            cy.findByRole('link', { 'name': `Child Category 1 ${time}` }).click();
        });

        // Check breadcrumb
        cy.findByRole('navigation', { 'name': 'Service catalog categories' }).within(() => {
            cy.findByRole('link', { 'name': 'Service catalog' }).should('exist');
            cy.findByRole('link', { 'name': `Root Category ${time}` }).should('not.exist');
            cy.findByRole('link', { 'name': `Child Category 1 ${time}` }).should('exist');
            cy.findByRole('link', { 'name': `Child Category 2 ${time}` }).should('not.exist');
        });

        // Check that the form is visible
        cy.findByRole('region', { 'name': `Child Category 2 ${time}` }).within(() => {
            cy.findByRole('link', { 'name': 'Form 1' }).should('exist');
        });

        // Go back to the root category
        cy.findByRole('link', { 'name': 'Service catalog' }).click();

        // Check breadcrumb
        cy.findByRole('navigation', { 'name': 'Service catalog categories' }).within(() => {
            cy.findByRole('link', { 'name': 'Service catalog' }).should('exist');
            cy.findByRole('link', { 'name': `Root Category ${time}` }).should('not.exist');
            cy.findByRole('link', { 'name': `Child Category 1 ${time}` }).should('not.exist');
            cy.findByRole('link', { 'name': `Child Category 2 ${time}` }).should('not.exist');
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
            cy.findByRole('region', { name: `Form ${String.fromCharCode(65 + i)} ${time}` }).should('exist');
        }
        // Verify items from second page are not visible
        cy.findByRole('region', { name: `Form ${String.fromCharCode(65 + forms_per_page)} ${time}` }).should('not.exist');

        // Test pagination controls visibility
        cy.findByRole('navigation', { name: 'Service catalog pages' }).within(() => {
            // First page active
            cy.findByRole('link', { name: '1' }).closest('li').should('have.class', 'active');
            // Second page link available
            cy.findByRole('link', { name: '2' }).should('exist');
            // Third page link not available
            cy.findByRole('link', { name: '3' }).should('not.exist');
            // Next/Last buttons enabled
            cy.findByRole('link', { name: 'Next page' }).closest('li').should('not.have.class', 'disabled');
            cy.findByRole('link', { name: 'Last page' }).closest('li').should('not.have.class', 'disabled');
            // Prev/First buttons disabled
            cy.findByRole('link', { name: 'Previous page' }).closest('li').should('have.class', 'disabled');
            cy.findByRole('link', { name: 'First page' }).closest('li').should('have.class', 'disabled');
        });

        // Go to second page
        cy.findByRole('link', { name: '2' }).click();

        // Verify second page content
        for (let i = 0; i < forms_per_page; i++) {
            cy.findByRole('region', { name: `Form ${String.fromCharCode(65 + i)} ${time}` }).should('not.exist');
        }
        for (let i = forms_per_page; i < total_forms; i++) {
            cy.findByRole('region', { name: `Form ${String.fromCharCode(65 + i)} ${time}` }).should('exist');
        }

        // Test pagination controls after page change
        cy.findByRole('navigation', { name: 'Service catalog pages' }).within(() => {
            // Second page active
            cy.findByRole('link', { name: '2' }).closest('li').should('have.class', 'active');
            // First page link available
            cy.findByRole('link', { name: '1' }).should('exist');
            // Third page link available
            cy.findByRole('link', { name: '3' }).should('not.exist');
            // Next/Last buttons disabled (on last page)
            cy.findByRole('link', { name: 'Next page' }).closest('li').should('have.class', 'disabled');
            cy.findByRole('link', { name: 'Last page' }).closest('li').should('have.class', 'disabled');
            // Prev/First buttons enabled
            cy.findByRole('link', { name: 'Previous page' }).closest('li').should('not.have.class', 'disabled');
            cy.findByRole('link', { name: 'First page' }).closest('li').should('not.have.class', 'disabled');
        });

        // Go back to first page using prev button
        cy.findByRole('link', { name: 'Previous page' }).click();

        // Verify first page content again
        for (let i = 0; i < forms_per_page; i++) {
            cy.findByRole('region', { name: `Form ${String.fromCharCode(65 + i)} ${time}` }).should('exist');
        }
        cy.findByRole('region', { name: `Form ${String.fromCharCode(65 + forms_per_page)} ${time}` }).should('not.exist');
    });

    it('can display service catalog with form that has no description', () => {
        const form_name = `Test form without description ${(new Date()).getTime()}`;

        cy.changeProfile('Super-Admin');
        createActiveForm(form_name, 0, null);

        cy.changeProfile('Self-Service', true);
        cy.visit('/ServiceCatalog');

        // Search for the form
        cy.findByPlaceholderText('Search for forms...').as('filter_input');
        cy.get('@filter_input').type(form_name);

        // Validate that the form is displayed correctly.
        cy.findByRole('region', { 'name': form_name }).as('forms');
        cy.get('@forms').within(() => {
            cy.findByRole('heading', { 'name': form_name }).should('exist');
            cy.findByRole('heading', { 'name': form_name })
                .closest('section')
                .findByTestId('service-catalog-description')
                .invoke('text')
                .then((text) => {
                    expect(text.trim()).to.be.empty;
                })
            ;
        });
    });

    it('can change sort order in the service catalog', () => {
        const time = (new Date()).getTime();
        cy.changeProfile('Super-Admin');

        // Create forms with different names
        createActiveForm(`A form ${time}`);
        createActiveForm(`C form ${time}`);
        createActiveForm(`B form ${time}`);

        // Add a question to B form
        cy.get('@form_id').visitFormTab('Form');
        cy.findByRole('button', { 'name': 'Add a question' }).click();
        cy.focused().type('Question 1');
        cy.findByRole('button', { 'name': 'Save' }).click();

        cy.changeProfile('Self-Service', true);

        // Visit and answer to a form to increment popularity
        cy.visit('/ServiceCatalog');
        cy.findByPlaceholderText('Search for forms...').as('filter_input');
        cy.get('@filter_input').type(time);
        cy.findByRole('region', { 'name': `B form ${time}` }).click();
        cy.findByRole('button', { 'name': 'Submit' }).click();
        cy.findByRole('alert')
            .should('contain.text', 'Item successfully created');

        // Go back to the service catalog
        cy.visit('/ServiceCatalog');

        // Search with time to restrict the results
        cy.findByPlaceholderText('Search for forms...').as('filter_input');
        cy.get('@filter_input').type(time);

        // Check the default sort order
        cy.getDropdownByLabelText('Sort by').findByRole('textbox', { 'name': 'Most popular' }).should('exist');
        cy.findByRole('region', { 'name': `Forms` }).within(() => {
            cy.findAllByRole('link').should('have.length', 3);
            cy.findAllByRole('link').eq(0).findByRole('heading').contains(`B form ${time}`).should('exist');
            cy.findAllByRole('link').eq(1).findByRole('heading').contains(`A form ${time}`).should('exist');
            cy.findAllByRole('link').eq(2).findByRole('heading').contains(`C form ${time}`).should('exist');
        });

        // Change sort order to "Alphabetical"
        cy.getDropdownByLabelText('Sort by').selectDropdownValue('Alphabetical');

        // Check the new sort order
        cy.getDropdownByLabelText('Sort by').findByRole('textbox', { 'name': 'Alphabetical' }).should('exist');
        cy.findByRole('region', { 'name': `Forms` }).within(() => {
            cy.findAllByRole('link').should('have.length', 3);
            cy.findAllByRole('link').eq(0).findByRole('heading').contains(`A form ${time}`).should('exist');
            cy.findAllByRole('link').eq(1).findByRole('heading').contains(`B form ${time}`).should('exist');
            cy.findAllByRole('link').eq(2).findByRole('heading').contains(`C form ${time}`).should('exist');
        });

        // Change sort order to "Non alphabetical"
        cy.getDropdownByLabelText('Sort by').selectDropdownValue('Reverse alphabetical');

        // Check the new sort order
        cy.getDropdownByLabelText('Sort by').findByRole('textbox', { 'name': 'Reverse alphabetical' }).should('exist');
        cy.findByRole('region', { 'name': `Forms` }).within(() => {
            cy.findAllByRole('link').should('have.length', 3);
            cy.findAllByRole('link').eq(0).findByRole('heading').contains(`C form ${time}`).should('exist');
            cy.findAllByRole('link').eq(1).findByRole('heading').contains(`B form ${time}`).should('exist');
            cy.findAllByRole('link').eq(2).findByRole('heading').contains(`A form ${time}`).should('exist');
        });
    });

    it('deleted forms are not displayed in the service catalog', () => {
        cy.changeProfile('Super-Admin');

        const form_name = `Test form for service_catalog_page.cy.js ${(new Date()).getTime()}`;
        cy.createFormWithAPI({
            'name': form_name,
            'is_active': true,
        }).as('form_id');

        cy.changeProfile('Self-Service', true);
        cy.visit('/ServiceCatalog');

        cy.findByPlaceholderText('Search for forms...').as('filter_input');
        cy.get('@filter_input').type(form_name);

        // Validate that the form is displayed.
        cy.findByRole('region', { 'name': form_name }).as('forms');

        cy.changeProfile('Super-Admin');
        cy.get('@form_id').then(form_id => {
            // Visit the form to ensure it exists
            cy.visit(`front/form/form.form.php?id=${form_id}`);
        });

        // Delete the form
        cy.findByRole('button', { 'name': 'Put in trashbin' }).click();
        cy.findByRole('alert').should('contain.text', 'Item successfully deleted');

        // Go back to the service catalog
        cy.changeProfile('Self-Service', true);
        cy.visit('/ServiceCatalog');
        cy.findByPlaceholderText('Search for forms...').as('filter_input');
        cy.get('@filter_input').type(form_name);

        // Validate that the form is not displayed
        cy.findByRole('region', { 'name': form_name }).should('not.exist');
        cy.findByRole('region', { 'name': 'Forms' }).contains('No forms found').should('exist');

        // Purge the form
        cy.changeProfile('Super-Admin');
        cy.get('@form_id').then(form_id => {
            // Visit the form to ensure it exists
            cy.visit(`front/form/form.form.php?id=${form_id}`);
        });
        cy.findByRole('button', { 'name': 'Delete permanently' }).click();
        cy.findByRole('alert').should('contain.text', 'Item successfully purged');

        // Go back to the self-service page
        cy.changeProfile('Self-Service');
        cy.visit('/ServiceCatalog');
        cy.findByPlaceholderText('Search for forms...').as('filter_input');
        cy.get('@filter_input').type(form_name);

        // Validate that the form is still not displayed
        cy.findByRole('region', { 'name': form_name }).should('not.exist');
        cy.findByRole('region', { 'name': 'Forms' }).contains('No forms found').should('exist');
    });

    it('can filter forms, kb items and categories nested in category', () => {
        const uuid = Cypress._.uniqueId(Date.now().toString());

        // Arrange: Create categories, form and KB item
        cy.createWithAPI('Glpi\\Form\\Category', {
            'name': `Root Category ${uuid}`,
        }).then(category_id => {
            cy.createWithAPI('Glpi\\Form\\Category', {
                'name': `Nested category ${uuid}`,
                'forms_categories_id': category_id
            }).then(sub_category_id => {
                createActiveForm(`Nested form for ${sub_category_id}`, sub_category_id);
            });

            createActiveForm(`Nested form ${uuid}`, category_id);
            createKnowledgeBaseItem(`Nested KB item ${uuid}`, {'forms_categories_id': category_id});
        });

        // Act: Visit the service catalog and apply filters
        cy.visit('/ServiceCatalog');
        cy.findByPlaceholderText('Search for forms...').as('filter_input');
        cy.get('@filter_input').type(`Nested ${uuid}`);

        // Wait input debounce
        cy.waitForNetworkIdle(1000);

        // Assert: Check that the correct category, form and KB item are displayed
        findItemInServiceCatalog(`Nested category ${uuid}`).should('exist');
        findItemInServiceCatalog(`Nested form ${uuid}`).should('exist');
        findItemInServiceCatalog(`Nested KB item ${uuid}`).should('exist');
    });
});
