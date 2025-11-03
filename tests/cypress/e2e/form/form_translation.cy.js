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

function addTranslations() {
    // Add a language translation
    cy.findByRole('button', { name: 'Add language' }).click();
    cy.getDropdownByLabelText('Select language to translate').as('languageDropdown');
    cy.get('@languageDropdown').should('have.value', '');
    cy.get('@languageDropdown').selectDropdownValue('Français');
    cy.findByRole('button', { name: 'Add' }).click();

    // Wait modal to be open
    cy.findByRole('dialog').should('have.attr', 'data-cy-shown', 'true');

    // Provide translations
    cy.findByRole('table', { name: 'Form translations' }).as('formTranslationsTable');
    cy.get('@formTranslationsTable').findAllByRole('row').as('formTranslationsRows');
    cy.get('@formTranslationsRows').eq(2).within(() => {
        cy.findByRole('cell', { name: 'Translation name' }).contains('Form title');
        cy.findByRole('cell', { name: 'Default value' }).contains('Tests form translations');
        cy.findByRole('cell', { name: 'Translated value' })
            .findByRole('textbox', { name: 'Enter translation' })
            .type('Tester les traductions de formulaire');
    });
    cy.get('@formTranslationsRows').eq(3).within(() => {
        cy.findByRole('cell', { name: 'Translation name' }).contains('Form description');
        cy.findByRole('cell', { name: 'Default value' }).contains('This form is used to test form translations');
        cy.findByRole('cell', { name: 'Translated value' })
            .findByLabelText('Enter translation')
            .awaitTinyMCE()
            .type('Ce formulaire est utilisé pour tester les traductions de formulaire');
    });

    // Save the translations
    cy.findByRole('button', { name: 'Save translation' }).click();
    cy.checkAndCloseAlert('Item successfully updated');
}

function checkTranslations(title, description) {
    cy.findByTestId('form-title').should('exist').contains(title);
    cy.findByTestId('form-description').should('exist').contains(description);
}

function changeUserLanguage(language) {
    cy.updateTestUserSettings({
        'language': language
    });

    // We need to logout and login to apply the language change
    cy.logout();
    cy.login();
    cy.changeProfile('Super-Admin');
}

describe('Edit form translations', () => {
    beforeEach(() => {
        cy.createWithAPI('Glpi\\Form\\Form', {
            'name': 'Tests form translations',
            'header': 'This form is used to test form translations',
        }).as('form_id');

        cy.login();
        cy.changeProfile('Super-Admin');

        cy.get('@form_id').then((form_id) => {
            const tab = 'Glpi\\Form\\FormTranslation$1';
            cy.visit(`/front/form/form.form.php?id=${form_id}&forcetab=${tab}`);
        });

        cy.findByRole('region', { name: 'Form translations' }).as('formTranslations');
    });

    afterEach(() => {
        // Make sure the user language is reset to default
        cy.updateTestUserSettings({
            'language': null
        });
    });

    it('can add a new language translation', () => {
        // Add a language translation
        cy.findByRole('button', { name: 'Add language' }).click();
        cy.getDropdownByLabelText('Select language to translate').as('languageDropdown');
        cy.get('@languageDropdown').should('have.value', '');
        cy.get('@languageDropdown').selectDropdownValue('Français');
        cy.findByRole('button', { name: 'Add' }).click();

        // Check we are on the form translation page
        cy.findByRole('table', { name: 'Form translations' }).should('exist').within(() => {
            cy.findAllByRole('cell', { name: 'Translation name' }).should('have.length', 2);
            cy.findAllByRole('cell', { name: 'Default value' }).should('have.length', 2);
            cy.findAllByRole('cell', { name: 'Translated value' }).should('have.length', 2);
        });

        // Close the modal
        cy.findByRole('dialog').as('modal');
        cy.get('@modal').should('have.attr', 'data-cy-shown', 'true');
        cy.get('@modal').findByRole('button', { name: 'Close' }).click();
        cy.get('@modal').should('not.exist');

        // Check columns values
        cy.get('#glpi-form-translations-languages').find('tbody:first>tr:first>td').eq(0).contains('Français');
        cy.get('#glpi-form-translations-languages').find('tbody:first>tr:first>td').eq(1).findByRole('progressbar').contains('0 %');
        cy.get('#glpi-form-translations-languages').find('tbody:first>tr:first>td').eq(2).invoke('text').then((text) => {
            expect(text.trim()).to.equal('2');
        });
        cy.get('#glpi-form-translations-languages').find('tbody:first>tr:first>td').eq(3).invoke('text').then((text) => {
            expect(text.trim()).to.equal('0');
        });
    });

    it('can add new translations', () => {
        addTranslations();

        // Open modal
        cy.findByRole('button', { name: 'Edit translation' }).click();
        cy.findByRole('dialog').should('have.attr', 'data-cy-shown', 'true');

        // Check the translations
        cy.get('@formTranslationsTable').findAllByRole('row').as('formTranslationsRows');
        cy.get('@formTranslationsRows').eq(2).within(() => {
            cy.findByRole('cell', { name: 'Translation name' }).contains('Form title');
            cy.findByRole('cell', { name: 'Default value' }).contains('Tests form translations');
            cy.findByRole('cell', { name: 'Translated value' }).findByRole('textbox', { name: 'Enter translation' })
                .should('have.value', 'Tester les traductions de formulaire');
        });
        cy.get('@formTranslationsRows').eq(3).within(() => {
            cy.findByRole('cell', { name: 'Translation name' }).contains('Form description');
            cy.findByRole('cell', { name: 'Default value' }).contains('This form is used to test form translations');
            cy.findByRole('cell', { name: 'Translated value' }).findByLabelText('Enter translation')
                .should('have.value', '<p>Ce formulaire est utilisé pour tester les traductions de formulaire</p>');
        });
    });

    it('can view translations on form preview with default language', () => {
        addTranslations();

        // Go to the form preview
        cy.get('@form_id').then((form_id) => {
            cy.visit(`/Form/Render/${form_id}`);
        });

        // Check the translations with GLPI default language same as E2E user defined language
        checkTranslations('Tests form translations', 'This form is used to test form translations');
    });

    it('can view translations on form preview in French', () => {
        addTranslations();

        // Change the user language to French
        changeUserLanguage('fr_FR');

        // Go to the form preview
        cy.get('@form_id').then((form_id) => {
            cy.visit(`/Form/Render/${form_id}`);
        });

        checkTranslations('Tester les traductions de formulaire', 'Ce formulaire est utilisé pour tester les traductions de formulaire');
    });

    it('can view translations on form preview in Spanish', () => {
        addTranslations();

        // Change the user language to Spanish
        changeUserLanguage('es_ES');

        // Go to the form preview
        cy.get('@form_id').then((form_id) => {
            cy.visit(`/Form/Render/${form_id}`);
        });

        checkTranslations('Tests form translations', 'This form is used to test form translations');
    });

    it('can delete a form translation', () => {
        addTranslations();

        // Open modal
        cy.findByRole('button', { name: 'Edit translation' }).click();
        cy.findByRole('dialog').should('have.attr', 'data-cy-shown', 'true');

        // Delete the French translation
        cy.findByRole('button', { name: 'Delete translation' }).click();
        cy.checkAndCloseAlert('Item successfully purged');

        // Check if the French translation is deleted
        cy.findByRole('link', { name: 'Français' }).should('not.exist');
    });

    it('check form translation stats', () => {
        // Add a language translation
        cy.findByRole('button', { name: 'Add language' }).click();
        cy.getDropdownByLabelText('Select language to translate').selectDropdownValue('Français');
        cy.findByRole('button', { name: 'Add' }).click();

        // Close the modal
        cy.findByRole('dialog').as('modal');
        cy.get('@modal').should('have.attr', 'data-cy-shown', 'true');
        cy.get('@modal').findByRole('button', { name: 'Close' }).click();
        cy.get('@modal').should('not.exist');

        // Check stats
        cy.get('@formTranslations').find('tbody tr').eq(0).find('td').eq(0).contains('Français');
        cy.get('@formTranslations').find('tbody tr').eq(0).find('td').eq(-3).findByRole('progressbar').contains('0 %');
        cy.get('@formTranslations').find('tbody tr').eq(0).find('td').eq(-2).invoke('text').then((text) => {
            expect(text.trim()).to.equal('2');
        });
        cy.get('@formTranslations').find('tbody tr').eq(0).find('td').eq(-1).invoke('text').then((text) => {
            expect(text.trim()).to.equal('0');
        });

        // Add translations
        cy.findByRole('button', { name: 'Edit translation' }).click();
        cy.findByRole('dialog').should('have.attr', 'data-cy-shown', 'true');

        cy.findByRole('table', { name: 'Form translations' }).as('formTranslationsTable');
        cy.get('@formTranslationsTable').findAllByRole('row').as('formTranslationsRows');
        cy.get('@formTranslationsRows').eq(2).within(() => {
            cy.findByRole('cell', { name: 'Translation name' }).contains('Form title');
            cy.findByRole('cell', { name: 'Default value' }).contains('Tests form translations');
            cy.findByRole('cell', { name: 'Translated value' }).findByRole('textbox', { name: 'Enter translation' })
                .type('Tester les traductions de formulaire');
        });

        // Save the translations
        cy.findByRole('button', { name: 'Save translation' }).click();

        // Go back to the form translations page
        cy.get('@form_id').then((form_id) => {
            cy.visit(`/front/form/form.form.php?id=${form_id}&forcetab=Glpi\\Form\\FormTranslation$1`);
        });

        // Check stats
        cy.get('@formTranslations').find('tbody tr').eq(0).find('td').eq(-3).findByRole('progressbar').contains('50 %');
        cy.get('@formTranslations').find('tbody tr').eq(0).find('td').eq(-2).invoke('text').then((text) => {
            expect(text.trim()).to.equal('1');
        });
        cy.get('@formTranslations').find('tbody tr').eq(0).find('td').eq(-1).invoke('text').then((text) => {
            expect(text.trim()).to.equal('0');
        });
    });

    it('can detect translations to review when default value changes', () => {
        addTranslations();

        // Add a language translation
        cy.findByRole('button', { name: 'Add language' }).click();
        cy.getDropdownByLabelText('Select language to translate').selectDropdownValue('Deutsch');
        cy.findByRole('button', { name: 'Add' }).click();

        // Modify the default values of the form
        cy.get('@form_id').then((form_id) => {
            cy.updateWithAPI('Glpi\\Form\\Form', form_id, {
                'name': 'Tests form translations updated',
            });
        });

        // Reload the translation page
        cy.get('@form_id').then((form_id) => {
            const tab = 'Glpi\\Form\\FormTranslation$1';
            cy.visit(`/front/form/form.form.php?id=${form_id}&forcetab=${tab}`);
        });

        // Check stats
        cy.get('#glpi-form-translations-languages').find('tbody:first>tr').eq(0).find('td').eq(-1).invoke('text').then((text) => {
            expect(text.trim()).to.equal('1');
        });
        cy.get('#glpi-form-translations-languages').find('tbody:first>tr').eq(1).find('td').eq(-1).invoke('text').then((text) => {
            expect(text.trim()).to.equal('0');
        });

        // Go to the French translation page and check that the translation is marked as "to review"
        cy.findAllByRole('button', { name: 'Edit translation' }).eq(0).click();
        cy.findAllByRole('textbox', { name: 'Enter translation' }).eq(0).parent().within(() => {
            cy.get('.ti-alert-circle').should('exist');
        });

        // Close the modal
        cy.findByRole('dialog').as('modal');
        cy.get('@modal').should('have.attr', 'data-cy-shown', 'true');
        cy.get('@modal').findByRole('button', { name: 'Close' }).click();
        cy.get('@modal').should('not.exist');

        // Go to the German translation page and check that the translation isn't marked as "to review"
        cy.findAllByRole('button', { name: 'Edit translation' }).eq(1).click();
        cy.findAllByRole('textbox', { name: 'Enter translation' }).eq(0).parent().within(() => {
            cy.get('.ti-alert-circle').should('not.exist');
        });
    });

    it('can translate options of a radio question', () => {
        // Go to the form editor
        cy.findByRole('tab', { name: 'Form' }).click();

        // Add a radio question
        cy.findByRole('button', { name: 'Add a question' }).click();
        cy.findByRole('textbox', { name: 'Question name' }).type('Radio question');
        cy.getDropdownByLabelText('Question type').selectDropdownValue('Radio');

        // Add options to the question
        cy.findAllByRole('textbox', { name: 'Selectable option' }).eq(-1).type('First option');
        cy.findAllByRole('textbox', { name: 'Selectable option' }).eq(-1).type('Second option');
        cy.findAllByRole('textbox', { name: 'Selectable option' }).eq(-1).type('Third option');

        // Save the form
        cy.findByRole('button', { name: 'Save' }).click();
        cy.checkAndCloseAlert('Item successfully updated');

        // Go to the form translations page
        cy.findByRole('tab', { name: 'Form translations' }).click();

        // Add a language translation
        cy.findByRole('button', { name: 'Add language' }).click();
        cy.getDropdownByLabelText('Select language to translate').selectDropdownValue('Français');
        cy.findByRole('button', { name: 'Add' }).click();
        cy.findByRole('dialog').should('have.attr', 'data-cy-shown', 'true');

        // Add translations for options
        cy.findByRole('table', { name: 'Form translations' }).as('formTranslationsTable');
        cy.get('@formTranslationsTable').findAllByRole('row').as('formTranslationsRows');
        cy.get('@formTranslationsRows').eq(-3).within(() => {
            cy.findByRole('cell', { name: 'Translation name' }).contains('Radio Option');
            cy.findByRole('cell', { name: 'Default value' }).contains('First option');
            cy.findByRole('cell', { name: 'Translated value' }).findByRole('textbox', { name: 'Enter translation' })
                .type('Première option');
        });
        cy.get('@formTranslationsRows').eq(-2).within(() => {
            cy.findByRole('cell', { name: 'Translation name' }).contains('Radio Option');
            cy.findByRole('cell', { name: 'Default value' }).contains('Second option');
            cy.findByRole('cell', { name: 'Translated value' }).findByRole('textbox', { name: 'Enter translation' })
                .type('Deuxième option');
        });
        cy.get('@formTranslationsRows').eq(-1).within(() => {
            cy.findByRole('cell', { name: 'Translation name' }).contains('Radio Option');
            cy.findByRole('cell', { name: 'Default value' }).contains('Third option');
            cy.findByRole('cell', { name: 'Translated value' }).findByRole('textbox', { name: 'Enter translation' })
                .type('Troisième option');
        });

        // Save the translations
        cy.findByRole('button', { name: 'Save translation' }).click();
        cy.checkAndCloseAlert('Item successfully updated');

        // Go to the form preview
        cy.get('@form_id').then((form_id) => {
            cy.visit(`/Form/Render/${form_id}`);
        });

        // Check default translations
        checkTranslations('Tests form translations', 'This form is used to test form translations');
        cy.findByRole('heading', { name: 'Radio question' }).should('exist');
        cy.findByRole('radio', { name: 'First option' }).should('exist');
        cy.findByRole('radio', { name: 'Second option' }).should('exist');
        cy.findByRole('radio', { name: 'Third option' }).should('exist');

        // Change the user language to French
        changeUserLanguage('fr_FR');
        cy.reload();

        // Check the translations for the radio question
        checkTranslations('Tests form translations', 'This form is used to test form translations');
        cy.findByRole('heading', { name: 'Radio question' }).should('exist');
        cy.findByRole('radio', { name: 'Première option' }).should('exist');
        cy.findByRole('radio', { name: 'Deuxième option' }).should('exist');
        cy.findByRole('radio', { name: 'Troisième option' }).should('exist');
    });

    it('can translate default value of a long answer question', () => {
        // Go to the form editor
        cy.findByRole('tab', { name: 'Form' }).click();

        // Add a long answer question
        cy.findByRole('button', { name: 'Add a question' }).click();
        cy.findByRole('textbox', { name: 'Question name' }).type('Long answer question');
        cy.findByRole('option', {'name': 'New question'}).changeQuestionType('Long answer');

        // Set default value
        cy.findByRole('region', { name: 'Question details' })
            .findByLabelText('Default value').awaitTinyMCE().type('This is a long answer question');

        // Save the form
        cy.findByRole('button', { name: 'Save' }).click();
        cy.checkAndCloseAlert('Item successfully updated');

        // Go to the form translations page
        cy.findByRole('tab', { name: 'Form translations' }).click();

        // Add a language translation
        cy.findByRole('button', { name: 'Add language' }).click();
        cy.getDropdownByLabelText('Select language to translate').selectDropdownValue('Français');
        cy.findByRole('button', { name: 'Add' }).click();
        cy.findByRole('dialog').should('have.attr', 'data-cy-shown', 'true');

        // Add translations for the long answer question
        cy.findByRole('table', { name: 'Form translations' }).as('formTranslationsTable');
        cy.get('@formTranslationsTable').findAllByRole('row').as('formTranslationsRows');
        cy.get('@formTranslationsRows').eq(-1).within(() => {
            cy.findByRole('cell', { name: 'Translation name' }).contains('Default value');
            cy.findByRole('cell', { name: 'Default value' }).contains('This is a long answer question');
            cy.findByRole('cell', { name: 'Translated value' })
                .findByLabelText('Enter translation')
                .awaitTinyMCE()
                .type('Ceci est une question de texte long');
        });

        // Save the translations
        cy.findByRole('button', { name: 'Save translation' }).click();
        cy.checkAndCloseAlert('Item successfully updated');

        // Go to the form preview
        cy.get('@form_id').then((form_id) => {
            cy.visit(`/Form/Render/${form_id}`);
        });

        // Check default translations
        checkTranslations('Tests form translations', 'This form is used to test form translations');
        cy.findByRole('heading', { name: 'Long answer question' }).should('exist');
        cy.findAllByLabelText('Long answer question').awaitTinyMCE().should('have.text', 'This is a long answer question');

        // Change the user language to French
        changeUserLanguage('fr_FR');
        cy.reload();

        // Check the translations for the long answer question
        checkTranslations('Tests form translations', 'This form is used to test form translations');
        cy.findByRole('heading', { name: 'Long answer question' }).should('exist');
        cy.findAllByLabelText('Long answer question').awaitTinyMCE().should('have.text', 'Ceci est une question de texte long');
    });

    it('can translate default value of a short answer question', () => {
        // Go to the form editor
        cy.findByRole('tab', { name: 'Form' }).click();

        // Add a short answer question
        cy.findByRole('button', { name: 'Add a question' }).click();
        cy.findByRole('textbox', { name: 'Question name' }).type('Short answer question');
        cy.getDropdownByLabelText('Question type').selectDropdownValue('Short answer');

        // Set default value
        cy.findByRole('textbox', { name: 'Default value' }).type('This is a short answer question');

        // Save the form
        cy.findByRole('button', { name: 'Save' }).click();
        cy.checkAndCloseAlert('Item successfully updated');

        // Go to the form translations page
        cy.findByRole('tab', { name: 'Form translations' }).click();

        // Add a language translation
        cy.findByRole('button', { name: 'Add language' }).click();
        cy.getDropdownByLabelText('Select language to translate').selectDropdownValue('Français');
        cy.findByRole('button', { name: 'Add' }).click();
        cy.findByRole('dialog').should('have.attr', 'data-cy-shown', 'true');

        // Add translations for the short answer question
        cy.findByRole('table', { name: 'Form translations' }).as('formTranslationsTable');
        cy.get('@formTranslationsTable').findAllByRole('row').as('formTranslationsRows');
        cy.get('@formTranslationsRows').eq(-1).within(() => {
            cy.findByRole('cell', { name: 'Translation name' }).contains('Default value');
            cy.findByRole('cell', { name: 'Default value' }).contains('This is a short answer question');
            cy.findByRole('cell', { name: 'Translated value' }).findByRole('textbox', { name: 'Enter translation' })
                .type('Ceci est une question de réponse courte');
        });

        // Save the translations
        cy.findByRole('button', { name: 'Save translation' }).click();
        cy.checkAndCloseAlert('Item successfully updated');

        // Go to the form preview
        cy.get('@form_id').then((form_id) => {
            cy.visit(`/Form/Render/${form_id}`);
        });

        // Check default translations
        checkTranslations('Tests form translations', 'This form is used to test form translations');
        cy.findByRole('heading', { name: 'Short answer question' }).should('exist');
        cy.findByRole('textbox', { name: 'Short answer question' }).should('exist').should('have.value', 'This is a short answer question');

        // Change the user language to French
        changeUserLanguage('fr_FR');
        cy.reload();

        // Check the translations for the short answer question
        checkTranslations('Tests form translations', 'This form is used to test form translations');
        cy.findByRole('heading', { name: 'Short answer question' }).should('exist');
        cy.findByRole('textbox', { name: 'Short answer question' }).should('exist').should('have.value', 'Ceci est une question de réponse courte');
    });

    it('can translate description of a question', () => {
        // Go to the form editor
        cy.findByRole('tab', { name: 'Form' }).click();

        // Add a question with description
        cy.findByRole('button', { name: 'Add a question' }).click();
        cy.findByRole('region', { name: 'Question details' }).within(() => {
            cy.findByLabelText('Question description')
                .awaitTinyMCE()
                .type('This is a question with description');
        });

        // Save the form
        cy.findByRole('button', { name: 'Save' }).click();
        cy.checkAndCloseAlert('Item successfully updated');

        // Go to the form translations page
        cy.findByRole('tab', { name: 'Form translations' }).click();

        // Add a language translation
        cy.findByRole('button', { name: 'Add language' }).click();
        cy.getDropdownByLabelText('Select language to translate').selectDropdownValue('Français');
        cy.findByRole('button', { name: 'Add' }).click();
        cy.findByRole('dialog').should('have.attr', 'data-cy-shown', 'true');

        // Add translations for the question description
        cy.findByRole('table', { name: 'Form translations' }).as('formTranslationsTable');
        cy.get('@formTranslationsTable').findAllByRole('row').as('formTranslationsRows');
        cy.get('@formTranslationsRows').eq(-1).within(() => {
            cy.findByRole('cell', { name: 'Translation name' }).contains('Question description');
            cy.findByRole('cell', { name: 'Default value' }).contains('This is a question with description');
            cy.findByRole('cell', { name: 'Translated value' })
                .findByLabelText('Enter translation')
                .awaitTinyMCE()
                .type('Ceci est une question avec une description');
        });

        // Save the translations
        cy.findByRole('button', { name: 'Save translation' }).click();
        cy.checkAndCloseAlert('Item successfully updated');

        // Go to the form preview
        cy.get('@form_id').then((form_id) => {
            cy.visit(`/Form/Render/${form_id}`);
        });

        // Check default translation
        cy.findByRole('note', { name: 'Question description' }).should('exist').contains('This is a question with description');

        // Change the user language to French
        changeUserLanguage('fr_FR');
        cy.reload();

        // Check the translations for the question description
        cy.findByRole('note', { name: 'Question description' }).should('exist').contains('Ceci est une question avec une description');
    });
});
