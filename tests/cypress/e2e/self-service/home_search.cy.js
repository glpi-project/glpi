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

describe('Helpdesk Search with FormProvider', () => {

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

        const data = {...defaults, ...options};
        return cy.createWithAPI('KnowbaseItem', data);
    }

    beforeEach(() => {
        cy.login();
    });

    it('can search forms without categories using FormProvider', () => {
        const timestamp = (new Date()).getTime();
        const form_name_1 = `Form No Category 1 ${timestamp}`;
        const form_name_2 = `Form No Category 2 ${timestamp}`;

        cy.changeProfile('Super-Admin');

        // Create forms without categories (forms_categories_id = 0)
        createActiveForm(form_name_1, 0, 'Form without any category assigned');
        createActiveForm(form_name_2, 0, 'Another form without category');

        cy.changeProfile('Self-Service', true);
        cy.visit('/Helpdesk');

        // Both forms should be visible when searching
        cy.findByPlaceholderText('Search for knowledge base entries or forms').type(timestamp.toString());

        // Wait for search results and verify both forms appear
        cy.findByRole('link', {'name': form_name_1}).should('exist');
        cy.findByRole('link', {'name': form_name_2}).should('exist');
    });

    it('can search forms with and without categories using FormProvider', () => {
        const timestamp = (new Date()).getTime();
        const category_name = `Test Category ${timestamp}`;
        const form_with_category = `Form With Category ${timestamp}`;
        const form_without_category = `Form Without Category ${timestamp}`;

        cy.changeProfile('Super-Admin');

        // Create a category
        cy.createWithAPI('Glpi\\Form\\Category', {
            'name': category_name,
            'description': "Category for testing FormProvider",
        }).then(category_id => {
            // Create form with category
            createActiveForm(form_with_category, category_id, 'Form that has a category');
        });

        // Create form without category
        createActiveForm(form_without_category, 0, 'Form that has no category');

        cy.changeProfile('Self-Service', true);
        cy.visit('/Helpdesk');

        // Search for forms - both should appear
        cy.findByPlaceholderText('Search for knowledge base entries or forms').type(timestamp.toString());

        // Verify both forms appear (testing FormProvider correctly handles null categories)
        cy.findByRole('link', {'name': form_with_category}).should('exist');
        cy.findByRole('link', {'name': form_without_category}).should('exist');

        // Test specific filtering
        cy.findByPlaceholderText('Search for knowledge base entries or forms').clear();
        cy.findByPlaceholderText('Search for knowledge base entries or forms').type('Without Category');

        cy.findByRole('link', {'name': form_without_category}).should('exist');
        cy.findByRole('link', {'name': form_with_category}).should('not.exist');
    });

    it('verifies FormProvider fuzzy matching works correctly', () => {
        const timestamp = (new Date()).getTime();
        const form_name = `Hardware Request Form ${timestamp}`;

        cy.changeProfile('Super-Admin');
        createActiveForm(form_name, 0, 'Request for computer equipment and hardware');

        cy.changeProfile('Self-Service', true);
        cy.visit('/Helpdesk');

        // Test fuzzy matching on name
        cy.findByPlaceholderText('Search for knowledge base entries or forms').type('hardware');
        cy.findByRole('link', {'name': form_name}).should('exist');

        // Test fuzzy matching on description
        cy.findByPlaceholderText('Search for knowledge base entries or forms').clear();
        cy.findByPlaceholderText('Search for knowledge base entries or forms').type('computer');
        cy.findByRole('link', {'name': form_name}).should('exist');

        // Test non-matching filter
        cy.findByPlaceholderText('Search for knowledge base entries or forms').clear();
        cy.findByPlaceholderText('Search for knowledge base entries or forms').type('nonexistent');
        cy.findByRole('link', {'name': form_name}).should('not.exist');
    });

    it('verifies pinned forms always appear regardless of filter', () => {
        const timestamp = (new Date()).getTime();
        const pinned_form = `Important Pinned Form ${timestamp}`;
        const regular_form = `Regular Form ${timestamp}`;

        cy.changeProfile('Super-Admin');

        // Create a pinned form
        cy.createFormWithAPI({
            'name': pinned_form,
            'description': 'This is a pinned form',
            'is_active': true,
            'is_pinned': true,
            'forms_categories_id': 0,
        }).as('pinned_form_id');

        // Create a regular form
        createActiveForm(regular_form, 0, 'This is a regular form');

        cy.changeProfile('Self-Service', true);
        cy.visit('/Helpdesk');

        // Search for something that doesn't match either form name/description
        cy.findByPlaceholderText('Search for knowledge base entries or forms').type('nonexistent');

        // Pinned form should still appear, regular form should not
        cy.findByRole('link', {'name': pinned_form}).should('exist');
        cy.findByRole('link', {'name': regular_form}).should('not.exist');

        // Unpin the pinned form
        cy.changeProfile('Super-Admin');
        cy.get('@pinned_form_id').then(form_id => {
            cy.updateWithAPI('Glpi\\Form\\Form', form_id, {
                'is_pinned': false,
            });
        });
    });

    it('can search both forms and FAQ items together', () => {
        const timestamp = (new Date()).getTime();
        const form_name = `Test Form ${timestamp}`;
        const faq_name = `Test FAQ ${timestamp}`;

        cy.changeProfile('Super-Admin');

        // Create a form
        createActiveForm(form_name, 0, 'Form for testing search');

        // Create a FAQ item
        createKnowledgeBaseItem(faq_name, {
            'description': 'FAQ for testing search'
        });

        cy.changeProfile('Self-Service', true);
        cy.visit('/Helpdesk');

        // Search should find both
        cy.findByPlaceholderText('Search for knowledge base entries or forms').type('test');

        cy.findByRole('link', {'name': form_name}).should('exist');
        cy.findByRole('link', {'name': faq_name}).should('exist');
    });
});
