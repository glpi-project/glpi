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

describe('Dropdown form question type', () => {
    beforeEach(() => {
        cy.createWithAPI('Glpi\\Form\\Form', {
            'name': 'Tests form for the dropdown form question type suite',
        }).as('form_id');

        cy.login();
        cy.changeProfile('Super-Admin');

        cy.get('@form_id').then((form_id) => {
            const tab = 'Glpi\\Form\\Form$main';
            cy.visit(`/front/form/form.form.php?id=${form_id}&forcetab=${tab}`);

            // Add a question
            cy.findByRole("button", { name: "Add a question" }).should('exist').click();

            // Set the question name
            cy.findByRole("textbox", { name: "Question name" }).should('exist').type("Test dropdown question");

            // Store the question section
            cy.findByRole("option", { name: /New question|Test dropdown question/ }).should('exist').as('question');

            // Change question type
            cy.findByRole("combobox", { name: "Short answer" }).should('exist').select("Dropdown");

            // Check presence of the dropdown and the empty option
            cy.findByRole("combobox", { name: "-----" }).should('exist');
            cy.findByRole("textbox", { name: "Selectable option" }).should('exist');
        });
    });

    function checkOptionLabels(labels, multiple = false) {
        // Check if the option labels are correct
        cy.findAllByRole("textbox", { name: "Selectable option" })
            .should('have.length', labels.length + 1)
            .each((el, i) => {
                if (i < labels.length) {
                    cy.wrap(el).should('have.value', labels[i]);
                }
            });

        // Check in the select preview
        const selector = multiple ? ".multiple-preview-dropdown select" : ".single-preview-dropdown select";
        cy.get('@question').find(selector).find("option")
            .should('have.length', labels.length + (!multiple ? 1 : 0))
            .each((el, i) => {
                // Skip the empty option
                if (i === 0 && !multiple) return;

                cy.wrap(el).should('have.text', labels[i - (!multiple ? 1 : 0)]);
            });

        // Check if the last option label is empty
        cy.findAllByRole("textbox", { name: "Selectable option" }).last().should('have.value', '');
    }

    function checkSelectedOptions(indexes, multiple = false) {
        // Check in the select preview
        const selector = multiple ? ".multiple-preview-dropdown select" : ".single-preview-dropdown select";
        cy.get('@question').find(selector).find("option").should((options) => {
            options.each((i, el) => {
                if (i === 0 && !multiple) return;

                if (indexes.includes(i - (!multiple ? 1 : 0))) {
                    expect(el).to.be.selected;
                } else {
                    expect(el).not.to.be.selected;
                }
            });
        });
    }

    it('test adding and selecting options (simple)', () => {
        // Add a option
        cy.findByRole("textbox", { name: "Selectable option" }).type("Option 1");
        cy.findAllByRole("textbox", { name: "Selectable option" }).should('exist');

        // Check selected options and option labels
        checkSelectedOptions([]);
        checkOptionLabels(["Option 1"]);

        // Add a option
        cy.findAllByRole("textbox", { name: "Selectable option" }).eq(1).type("Option 2");

        // Select the first option in the select preview
        cy.findByRole("combobox", { name: "-----" }).closest(".single-preview-dropdown").find("select").select("Option 1", { force: true });

        // Check selected options and option labels
        checkSelectedOptions([0]);
        checkOptionLabels(["Option 1", "Option 2"]);

        // Add a option
        cy.findAllByRole("textbox", { name: "Selectable option" }).eq(2).type("Option 3");

        // Check selected options and option labels
        checkSelectedOptions([0]);
        checkOptionLabels(["Option 1", "Option 2", "Option 3"]);

        // Select the third option in the select preview
        cy.findByRole("combobox", { name: "Option 1" }).closest(".single-preview-dropdown").find("select").select("Option 3", { force: true });

        // Check selected options and option labels
        checkSelectedOptions([2]);
        checkOptionLabels(["Option 1", "Option 2", "Option 3"]);

        // Save the form (force is required because the button is hidden by a toast message)
        cy.findByRole("button", { name: "Save" }).click({ force: true });

        // Reload the page to check if the options are still selected
        cy.reload();

        // Update the question alias
        cy.findByRole("option", { name: /New question|Test dropdown question/ }).should('exist').as('question');

        // Click on the question
        cy.get("@question").click('top');

        // Check selected options and option labels
        checkSelectedOptions([2]);
        checkOptionLabels(["Option 1", "Option 2", "Option 3"]);
    });

    it('test adding and selecting options (multiple)', () => {
        // Change the question type to "Dropdown (multiple)"
        cy.findByRole("checkbox", { name: "Allow multiple options" }).check();

        // Add new options
        cy.findByRole("textbox", { name: "Selectable option" }).type("Option 1");
        cy.findAllByRole("textbox", { name: "Selectable option" }).eq(1).type("Option 2");
        cy.findAllByRole("textbox", { name: "Selectable option" }).eq(2).type("Option 3");

        // Check selected options and option labels
        checkSelectedOptions([], true);
        checkOptionLabels(["Option 1", "Option 2", "Option 3"], true);

        // Select the first option
        cy.get('@question').find(".multiple-preview-dropdown span.select2-selection--multiple").click();

        cy.get('@question').find('.multiple-preview-dropdown select').invoke('attr', 'id').then((id) => {
            cy.get(`#select2-${id}-results`).findByRole('option', { name: 'Option 1' }).should('exist').click();
        });

        // Check selected options and option labels
        checkSelectedOptions([0], true);
        checkOptionLabels(["Option 1", "Option 2", "Option 3"], true);

        // Select the second option
        cy.get('@question').find(".multiple-preview-dropdown span.select2-selection--multiple").click();

        cy.get('@question').find('.multiple-preview-dropdown select').invoke('attr', 'id').then((id) => {
            cy.get(`#select2-${id}-results`).findByRole('option', { name: 'Option 2' }).should('exist').click();
        });

        // Check selected options and option labels
        checkSelectedOptions([0, 1], true);
        checkOptionLabels(["Option 1", "Option 2", "Option 3"], true);

        // Unselect the first option
        cy.get('@question').find(".multiple-preview-dropdown span.select2-selection--multiple").click();

        cy.get('@question').find('.multiple-preview-dropdown select').invoke('attr', 'id').then((id) => {
            cy.get(`#select2-${id}-results`).findByRole('option', { name: 'Option 1' }).should('exist').click();
        });

        // Check selected options and option labels
        checkSelectedOptions([1], true);
        checkOptionLabels(["Option 1", "Option 2", "Option 3"], true);

        // Select the third option in the select preview
        cy.get('@question').find(".multiple-preview-dropdown span.select2-selection--multiple").click();

        cy.get('@question').find('.multiple-preview-dropdown select').invoke('attr', 'id').then((id) => {
            cy.get(`#select2-${id}-results`).findByRole('option', { name: 'Option 3' }).should('exist').click();
        });

        // Check selected options and option labels
        checkSelectedOptions([1, 2], true);
        checkOptionLabels(["Option 1", "Option 2", "Option 3"], true);

        // Save the form (force is required because the button is hidden by a toast message)
        cy.findByRole("button", { name: "Save" }).click({ force: true });

        // Reload the page to check if the options are still selected
        cy.reload();

        // Update the question alias
        cy.findByRole("option", { name: /New question|Test dropdown question/ }).should('exist').as('question');

        // Click on the question
        cy.get("@question").click('top');

        // Check selected options and option labels
        checkSelectedOptions([1, 2], true);
        checkOptionLabels(["Option 1", "Option 2", "Option 3"], true);
    });

    it('test transferring options from simple to multiple and vice versa', () => {
        // Add new options
        cy.findByRole("textbox", { name: "Selectable option" }).type("Option 1");
        cy.findAllByRole("textbox", { name: "Selectable option" }).eq(1).type("Option 2");
        cy.findAllByRole("textbox", { name: "Selectable option" }).eq(2).type("Option 3");

        // Select the first option
        cy.findByRole("combobox", { name: "-----" }).closest(".single-preview-dropdown").find("select").select("Option 1", { force: true });

        // Check selected options and option labels
        checkSelectedOptions([0]);
        checkOptionLabels(["Option 1", "Option 2", "Option 3"]);

        // Change the question type to "Dropdown (multiple)"
        cy.findByRole("checkbox", { name: "Allow multiple options" }).check();

        // Check selected options and option labels
        checkSelectedOptions([0], true);
        checkOptionLabels(["Option 1", "Option 2", "Option 3"], true);

        // Select the second option
        cy.get('@question').find(".multiple-preview-dropdown span.select2-selection--multiple").click();

        cy.get('@question').find('.multiple-preview-dropdown select').invoke('attr', 'id').then((id) => {
            cy.get(`#select2-${id}-results`).findByRole('option', { name: 'Option 2' }).should('exist').click();
        });

        // Check selected options and option labels
        checkSelectedOptions([0, 1], true);
        checkOptionLabels(["Option 1", "Option 2", "Option 3"], true);

        // Change the question type to "Dropdown (simple)"
        cy.findByRole("checkbox", { name: "Allow multiple options" }).uncheck();

        // Check selected options and option labels
        checkSelectedOptions([1]);
        checkOptionLabels(["Option 1", "Option 2", "Option 3"]);
    });

    it('test deleting options', () => {
        // Add new options
        cy.findByRole("textbox", { name: "Selectable option" }).type("Option 1");
        cy.findAllByRole("textbox", { name: "Selectable option" }).eq(1).type("Option 2");
        cy.findAllByRole("textbox", { name: "Selectable option" }).eq(2).type("Option 3");

        // Select the first option
        cy.findByRole("combobox", { name: "-----" }).closest(".single-preview-dropdown").find("select").select("Option 1", { force: true });

        // Check selected options and option labels
        checkSelectedOptions([0]);
        checkOptionLabels(["Option 1", "Option 2", "Option 3"]);

        // Delete the first option
        cy.findAllByRole("button", { name: "Remove option" }).eq(0).click();

        // Check selected options and option labels
        checkSelectedOptions([]);
        checkOptionLabels(["Option 2", "Option 3"]);
    });

    it('test end user view (simple)', () => {
        // Add new options
        cy.findByRole("textbox", { name: "Selectable option" }).type("Option 1");
        cy.findAllByRole("textbox", { name: "Selectable option" }).eq(1).type("Option 2");
        cy.findAllByRole("textbox", { name: "Selectable option" }).eq(2).type("Option 3");

        // Select the first option
        cy.findByRole("combobox", { name: "-----" }).closest(".single-preview-dropdown").find("select").select("Option 1", { force: true });

        // Check selected options and option labels
        checkSelectedOptions([0]);
        checkOptionLabels(["Option 1", "Option 2", "Option 3"]);

        // Save the form (force is required because the button is hidden by a toast message)
        cy.findByRole("button", { name: "Save" }).click({ force: true });

        // Go to preview page (remove the target="_blank" attribute to stay in the same window)
        cy.findByRole("link", { name: "Preview" })
            .invoke('attr', 'target', '_self')
            .click();

        // Check the question title
        cy.findByRole("heading", { name: "Test dropdown question" }).should('exist');

        // Open the dropdown
        cy.findByRole("combobox", { name: "Option 1" }).should('exist').click();

        // Check the question options
        cy.findByRole("combobox", { name: "Option 1" }).should('exist');
        cy.findByRole("option", { name: "Option 2" }).should('exist');
        cy.findByRole("option", { name: "Option 3" }).should('exist');
    });

    it('test end user view (multiple)', () => {
        // Change the question type to "Dropdown (multiple)"
        cy.findByRole("checkbox", { name: "Allow multiple options" }).check();

        // Add new options
        cy.findByRole("textbox", { name: "Selectable option" }).type("Option 1");
        cy.findAllByRole("textbox", { name: "Selectable option" }).eq(1).type("Option 2");
        cy.findAllByRole("textbox", { name: "Selectable option" }).eq(2).type("Option 3");

        // Select the first and third option
        cy.get('@question').find(".multiple-preview-dropdown span.select2-selection--multiple").click();
        cy.get('@question').find('.multiple-preview-dropdown select').invoke('attr', 'id').then((id) => {
            cy.get(`#select2-${id}-results`).findByRole('option', { name: 'Option 1' }).should('exist').click();

            // Open the dropdown
            cy.get('@question').find(".multiple-preview-dropdown span.select2-selection--multiple").click();
            cy.get(`#select2-${id}-results`).findByRole('option', { name: 'Option 3' }).should('exist').click();
        });

        // Check selected options and option labels
        checkSelectedOptions([0, 2], true);
        checkOptionLabels(["Option 1", "Option 2", "Option 3"], true);

        // Save the form (force is required because the button is hidden by a toast message)
        cy.findByRole("button", { name: "Save" }).click({ force: true });

        // Go to preview page (remove the target="_blank" attribute to stay in the same window)
        cy.findByRole("link", { name: "Preview" })
            .invoke('attr', 'target', '_self')
            .click();

        // Check the question title
        cy.findByRole("heading", { name: "Test dropdown question" }).should('exist');

        // Check if the default options are selected
        cy.findByRole("listitem", { name: "Option 1" }).should('exist');
        cy.findByRole("listitem", { name: "Option 2" }).should('not.exist');
        cy.findByRole("listitem", { name: "Option 3" }).should('exist');

        // Open the dropdown
        cy.findByRole("listitem", { name: "Option 1" }).should('exist').click();

        // Check the question options
        cy.findByRole("option", { name: "Option 1" }).should('exist');
        cy.findByRole("option", { name: "Option 2" }).should('exist');
        cy.findByRole("option", { name: "Option 3" }).should('exist');
    });

    it('test default option selection and reset to empty value', () => {
        // Add new options
        cy.findByRole("textbox", { name: "Selectable option" }).type("Option 1");
        cy.findAllByRole("textbox", { name: "Selectable option" }).eq(1).type("Option 2");
        cy.findAllByRole("textbox", { name: "Selectable option" }).eq(2).type("Option 3");

        // Select the first option
        cy.getDropdownByLabelText("Default option").selectDropdownValue("Option 1");

        // Check selected options and option labels
        checkSelectedOptions([0]);
        checkOptionLabels(["Option 1", "Option 2", "Option 3"]);

        // Save the form and reload the page
        cy.findByRole("button", { name: "Save" }).click();
        cy.checkAndCloseAlert("Item successfully updated");
        cy.reload();

        // Focus on the question
        cy.findByRole("option", { name: /New question|Test dropdown question/ }).click('top');

        // Check if the default option is selected
        cy.getDropdownByLabelText("Default option").should('have.text', 'Option 1');

        // Select another option as default
        cy.getDropdownByLabelText("Default option").selectDropdownValue("-----");

        // Save the form and reload the page
        cy.findByRole("button", { name: "Save" }).click();
        cy.checkAndCloseAlert("Item successfully updated");
        cy.reload();

        // Check if the new default option is selected
        cy.getDropdownByLabelText("Default option").should('have.text', '-----');
    });

    // Helper functions for visibility conditions tests
    function addDropdownOptions() {
        // Add new options
        cy.findByRole("textbox", { name: "Selectable option" }).type("Option 1");
        cy.findAllByRole("textbox", { name: "Selectable option" }).eq(1).type("Option 2");
        cy.findAllByRole("textbox", { name: "Selectable option" }).eq(2).type("Option 3");
    }

    function addSecondQuestionWithVisibilityCondition() {
        // Add a new question
        cy.findByRole("button", { name: "Add a question" }).click();
        cy.focused().type("Test visibility question");

        // Add a visibility condition
        cy.findAllByRole('region', { name: 'Question details' }).eq(1).within(() => {
            cy.findByRole('button', {'name': 'More actions'}).click();
            cy.findByRole('button', {'name': 'Configure visibility'}).click();
            cy.findByRole('radio', {'name': 'Visible if...'}).next().click();
            cy.getDropdownByLabelText('Item').selectDropdownValue("Test dropdown question");
            cy.getDropdownByLabelText('Value operator').selectDropdownValue("Is equal to");
            cy.getDropdownByLabelText('Value').selectDropdownValue("Option 2");
        });

        // Add visibility condition on submit button
        cy.findByRole('button', { name: 'Always visible' }).click();
        cy.findByRole('radio', { name: 'Visible if...' }).next().click();
        cy.getDropdownByLabelText('Item').selectDropdownValue("Test dropdown question");
        cy.getDropdownByLabelText('Value operator').selectDropdownValue("Is equal to");
        cy.getDropdownByLabelText('Value').selectDropdownValue("Option 2");
    }

    function saveFormAndGoToPreview() {
        // Save the form
        cy.findByRole("button", { name: "Save" }).click();
        cy.checkAndCloseAlert("Item successfully updated");

        // Go to preview page (remove the target="_blank" attribute to stay in the same window)
        cy.findByRole("link", { name: "Preview" })
            .invoke('attr', 'target', '_self')
            .click();
    }

    it('test visibility conditions with default option', () => {
        addDropdownOptions();

        // Select default option
        cy.getDropdownByLabelText("Default option").selectDropdownValue("Option 2");

        addSecondQuestionWithVisibilityCondition();
        saveFormAndGoToPreview();

        // Validate visibility of the question and submit button
        cy.findByRole("heading", { name: "Test visibility question" }).should('exist');
        cy.findByRole("button", { name: "Submit" }).should('exist');
    });

    it('test visibility conditions without default option', () => {
        addDropdownOptions();

        addSecondQuestionWithVisibilityCondition();
        saveFormAndGoToPreview();

        // Validate visibility of the question and submit button
        cy.findByRole("heading", { name: "Test visibility question" }).should('not.exist');
        cy.findByRole("button", { name: "Submit" }).should('not.exist');

        // Select the second option in the dropdown
        cy.getDropdownByLabelText('Test dropdown question').selectDropdownValue('Option 2');

        // Validate visibility of the question and submit button
        cy.findByRole("heading", { name: "Test visibility question" }).should('exist');
        cy.findByRole("button", { name: "Submit" }).should('exist');
    });
});
