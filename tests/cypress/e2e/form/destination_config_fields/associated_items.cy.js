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

describe('Associated items configuration', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin');

        // Create form
        cy.createFormWithAPI().as('form_id').visitFormTab('Form');

        // Save form
        cy.findByRole('button', {'name': 'Save'}).click();
        cy.checkAndCloseAlert('Item successfully updated');

        cy.get('@form_id').then((form_id) => {
            cy.createWithAPI('Computer', {
                'name': `My computer - ${form_id}`,
            }).as('computer_id');
            cy.createWithAPI('Computer', {
                'name': `My Assigned computer - ${form_id}`,
                'users_id': 7,
            });
            cy.createWithAPI('Monitor', {
                'name': `My Assigned monitor - ${form_id}`,
                'users_id': 7,
            });
        });
    });

    function addQuestionsAndSaveForm() {
        // Add a question of type Item
        cy.findByRole('button', {'name': "Add a question"}).click();
        cy.focused().type("My item question");
        cy.getDropdownByLabelText('Question type').selectDropdownValue('Item');
        cy.getDropdownByLabelText('Question sub type').selectDropdownValue('GLPI Objects');
        cy.getDropdownByLabelText('Select an itemtype').selectDropdownValue('Computers');

        // Add a question of type User Device with default configuration
        cy.findByRole('button', {'name': "Add a question"}).click();
        cy.focused().type("My user device question");
        cy.getDropdownByLabelText('Question type').selectDropdownValue('Item');
        cy.getDropdownByLabelText('Question sub type').selectDropdownValue('User Devices');
        cy.findByRole('checkbox', {'name': 'Allow multiple devices'}).should('not.be.checked');

        // Add a question of type User Device with multiple devices allowed
        cy.findByRole('button', {'name': "Add a question"}).click();
        cy.focused().type("My multiple user device question");
        cy.getDropdownByLabelText('Question type').selectDropdownValue('Item');
        cy.getDropdownByLabelText('Question sub type').selectDropdownValue('User Devices');
        cy.findByRole('checkbox', {'name': 'Allow multiple devices'}).check();

        // Save form
        cy.findByRole('button', {'name': 'Save'}).click();
        cy.checkAndCloseAlert('Item successfully updated');

        // Go to destination tab
        cy.findByRole('tab', { 'name': "Destinations 1" }).click();
    };

    it('can use all possibles configuration options', () => {
        // Add questions and save form
        addQuestionsAndSaveForm();

        // Retrieve configuration section
        cy.openAccordionItem('Destination fields accordion', 'Associated items');
        cy.findByRole('region', {'name': "Associated items configuration"}).as("config");
        cy.get('@config').getDropdownByLabelText('Associated items').as("associated_items_dropdown");

        // Default value
        cy.get('@associated_items_dropdown').should(
            'have.text',
            'Answer to last assets item question'
        );

        // Make sure hidden dropdowns are not displayed
        cy.get('@config').getDropdownByLabelText('Select the itemtype of the item to associate...').should('not.exist');
        cy.get('@config').getDropdownByLabelText('Select the item to associate...').should('not.exist');
        cy.get('@config').getDropdownByLabelText('Select questions...').should('not.exist');

        // Switch to "Specific items"
        cy.get('@associated_items_dropdown').selectDropdownValue('Specific items');
        cy.get('@config').getDropdownByLabelText('Select the itemtype of the item to associate...').as('specific_associated_items_itemtype_dropdown');
        cy.get('@specific_associated_items_itemtype_dropdown').selectDropdownValue('Computers');

        cy.get('@form_id').then((form_id) => {
            cy.get('@config').getDropdownByLabelText('Select the item to associate...').as('specific_associated_items_items_id_dropdown');
            cy.get('@specific_associated_items_items_id_dropdown').selectDropdownValue(`My computer - ${form_id}`);
        });

        cy.findByRole('button', {'name': 'Update item'}).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.openAccordionItem('Destination fields accordion', 'Associated items');
        cy.get('@associated_items_dropdown').should('have.text', 'Specific items');
        cy.get('@config').getDropdownByLabelText('Select the itemtype of the item to associate...').eq(0).should('have.text', 'Computers');
        cy.get('@form_id').then((form_id) => {
            cy.get('@specific_associated_items_items_id_dropdown').should('have.text', `My computer - ${form_id}`);
        });

        // Switch to "Answer from specific questions"
        cy.get('@associated_items_dropdown').selectDropdownValue('Answer from specific questions');
        cy.get('@config').getDropdownByLabelText('Select questions...').as('specific_answers_dropdown');
        cy.get('@specific_answers_dropdown').selectDropdownValue('My item question');
        cy.get('@specific_answers_dropdown').selectDropdownValue('My user device question');
        cy.get('@specific_answers_dropdown').selectDropdownValue('My multiple user device question');

        cy.findByRole('button', {'name': 'Update item'}).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.openAccordionItem('Destination fields accordion', 'Associated items');
        cy.get('@associated_items_dropdown').should('have.text', 'Answer from specific questions');
        cy.get('@specific_answers_dropdown').should('have.text', '×My item question×My user device question×My multiple user device question');

        // Switch to "All valid "Item" answers"
        cy.get('@associated_items_dropdown').selectDropdownValue('All valid "Item" answers');

        cy.findByRole('button', {'name': 'Update item'}).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.openAccordionItem('Destination fields accordion', 'Associated items');
        cy.get('@associated_items_dropdown').should('have.text', 'All valid "Item" answers');
    });

    it('can create ticket using default configuration', () => {
        // Add questions and save form
        addQuestionsAndSaveForm();

        // Go to preview
        cy.findByRole('tab', {'name': "Form"}).click();
        cy.findByRole('link', {'name': "Preview"})
            .invoke('removeAttr', 'target') // Cypress can't handle tab changes
            .click()
        ;

        // Fill form
        cy.get('@form_id').then((form_id) => {
            cy.getDropdownByLabelText("My item question").selectDropdownValue(`My computer - ${form_id}`);
            cy.getDropdownByLabelText("My user device question").selectDropdownValue(`Computers - My Assigned computer - ${form_id}`);
            cy.getDropdownByLabelText("My multiple user device question").selectDropdownValue(`Monitors - My Assigned monitor - ${  form_id}`);
        });
        cy.findByRole('button', {'name': 'Submit'}).click();
        cy.findByRole('link', {'name': 'My test form'}).click();

        // Check ticket linked items
        cy.get('@form_id').then((form_id) => {
            cy.findByRole('region', {'name': 'Items'}).findByRole('link', {'name': `My Assigned monitor - ${form_id} -`}).should('exist');
        });

        // Others possibles configurations are tested directly by the backend.
    });

    it('can define multiples specific items', () => {
        // Create a second computer and a monitor
        cy.get('@form_id').then((form_id) => {
            cy.createWithAPI('Computer', {
                'name': `My second computer - ${form_id}`,
            }).as('second_computer_id');
            cy.createWithAPI('Monitor', {
                'name': `My monitor - ${form_id}`,
            }).as('monitor_id');
        });

        // Go to destination tab
        cy.findByRole('tab', { 'name': "Destinations 1" }).click();

        // Retrieve configuration section
        cy.openAccordionItem('Destination fields accordion', 'Associated items');
        cy.findByRole('region', {'name': "Associated items configuration"}).as("config");
        cy.get('@config').getDropdownByLabelText('Associated items').as("associated_items_dropdown");

        // Switch to "Specific items"
        cy.get('@associated_items_dropdown').selectDropdownValue('Specific items');

        // Associate first computer
        cy.get('@config').find('[data-glpi-associated-items-specific-values-extra-field-item]').eq(0).getDropdownByLabelText('Select the itemtype of the item to associate...').as('specific_associated_items_itemtype_dropdown');
        cy.get('@specific_associated_items_itemtype_dropdown').selectDropdownValue('Computers');
        cy.get('@form_id').then((form_id) => {
            cy.get('@config').find('[data-glpi-associated-items-specific-values-extra-field-item]').eq(0).getDropdownByLabelText('Select the item to associate...').as('specific_associated_items_items_id_dropdown');
            cy.get('@specific_associated_items_items_id_dropdown').selectDropdownValue(`My computer - ${form_id}`);
        });

        // Add second computer
        cy.get('@config').find('[data-glpi-associated-items-specific-values-extra-field-item]').eq(1).getDropdownByLabelText('Select the itemtype of the item to associate...').as('specific_associated_items_itemtype_dropdown');
        cy.get('@specific_associated_items_itemtype_dropdown').selectDropdownValue('Computers');
        cy.get('@form_id').then((form_id) => {
            cy.get('@config').find('[data-glpi-associated-items-specific-values-extra-field-item]').eq(1).getDropdownByLabelText('Select the item to associate...').as('specific_associated_items_items_id_dropdown');
            cy.get('@specific_associated_items_items_id_dropdown').selectDropdownValue(`My second computer - ${form_id}`);
        });

        // Add monitor
        cy.get('@config').find('[data-glpi-associated-items-specific-values-extra-field-item]').eq(2).getDropdownByLabelText('Select the itemtype of the item to associate...').as('specific_associated_items_itemtype_dropdown');

        cy.intercept('/ajax/dropdownAllItems.php').as('dropdownAllItems');
        cy.get('@specific_associated_items_itemtype_dropdown').selectDropdownValue('Monitors');

        cy.wait('@dropdownAllItems').then(() => {
            cy.get('@form_id').then((form_id) => {
                cy.get('@config').find('[data-glpi-associated-items-specific-values-extra-field-item]').eq(2).getDropdownByLabelText('Select the item to associate...').as('specific_associated_items_items_id_dropdown');
                cy.get('@config').find('[data-glpi-associated-items-specific-values-extra-field-item]').eq(2).getDropdownByLabelText('Select the item to associate...').selectDropdownValue(`My monitor - ${form_id}`);
            });
        });

        // Update form destination and check persisted values
        cy.findByRole('button', {'name': 'Update item'}).click();
        cy.checkAndCloseAlert('Item successfully updated');
        cy.openAccordionItem('Destination fields accordion', 'Associated items');
        cy.get('@associated_items_dropdown').should('have.text', 'Specific items');
        cy.get('@form_id').then((form_id) => {
            cy.get('@config').find('[data-glpi-associated-items-specific-values-extra-field-item]').eq(0)
                .getDropdownByLabelText('Select the itemtype of the item to associate...')
                .should('have.text', 'Computers');
            cy.get('@config').find('[data-glpi-associated-items-specific-values-extra-field-item]').eq(0)
                .getDropdownByLabelText('Select the item to associate...')
                .should('have.text', `My computer - ${form_id}`);
            cy.get('@config').find('[data-glpi-associated-items-specific-values-extra-field-item]').eq(1)
                .getDropdownByLabelText('Select the itemtype of the item to associate...')
                .should('have.text', 'Computers');
            cy.get('@config').find('[data-glpi-associated-items-specific-values-extra-field-item]').eq(1)
                .getDropdownByLabelText('Select the item to associate...')
                .should('have.text', `My second computer - ${form_id}`);
            cy.get('@config').find('[data-glpi-associated-items-specific-values-extra-field-item]').eq(2)
                .getDropdownByLabelText('Select the itemtype of the item to associate...')
                .should('have.text', 'Monitors');
            cy.get('@config').find('[data-glpi-associated-items-specific-values-extra-field-item]').eq(2)
                .getDropdownByLabelText('Select the item to associate...')
                .should('have.text', `My monitor - ${form_id}`);
        });
    });

    it('can add a specifc item, add another strategy and add another specific item', () => {
        // Go to destination tab
        cy.findByRole('tab', { 'name': "Destinations 1" }).click();

        // Retrieve configuration section
        cy.openAccordionItem('Destination fields accordion', 'Associated items');
        cy.findByRole('region', {'name': "Associated items configuration"}).as("config");
        cy.get('@config').getDropdownByLabelText('Associated items').as("associated_items_dropdown");

        // Switch to "Specific items"
        cy.get('@associated_items_dropdown').selectDropdownValue('Specific items');

        // Associate computer
        cy.get('@config').find('[data-glpi-associated-items-specific-values-extra-field-item]').eq(0).getDropdownByLabelText('Select the itemtype of the item to associate...').as('specific_associated_items_itemtype_dropdown');
        cy.get('@specific_associated_items_itemtype_dropdown').selectDropdownValue('Computers');
        cy.get('@form_id').then((form_id) => {
            cy.get('@config').find('[data-glpi-associated-items-specific-values-extra-field-item]').eq(0).getDropdownByLabelText('Select the item to associate...').as('specific_associated_items_items_id_dropdown');
            cy.get('@specific_associated_items_items_id_dropdown').selectDropdownValue(`My computer - ${form_id}`);
        });

        // Add strategy
        cy.get('@config').findByRole('button', {'name': 'Combine with another option'}).click();
        cy.get('@config').find('[data-glpi-itildestination-field-config]').eq(1).getDropdownByLabelText('Select strategy...').as('specific_associated_items_strategy_dropdown');
        cy.get('@specific_associated_items_strategy_dropdown').selectDropdownValue('Answer from specific questions');

        // Associate monitor
        cy.get('@config').find('[data-glpi-associated-items-specific-values-extra-field-item]').eq(1).getDropdownByLabelText('Select the itemtype of the item to associate...').as('specific_associated_items_itemtype_dropdown');
        cy.get('@specific_associated_items_itemtype_dropdown').selectDropdownValue('Monitors');
        cy.get('@form_id').then((form_id) => {
            cy.get('@config').find('[data-glpi-associated-items-specific-values-extra-field-item]').eq(1).getDropdownByLabelText('Select the item to associate...').as('specific_associated_items_items_id_dropdown');
            cy.get('@specific_associated_items_items_id_dropdown').selectDropdownValue(`My Assigned monitor - ${form_id}`);
        });
    });
});
