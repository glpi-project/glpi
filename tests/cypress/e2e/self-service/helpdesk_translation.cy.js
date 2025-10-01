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

function addHelpdeskTranslations(tile_title, tile_description) {
    // Add a language translation
    cy.findByRole('button', { name: 'Add language' }).click();
    cy.getDropdownByLabelText('Select language to translate').as('languageDropdown');
    cy.get('@languageDropdown').should('have.value', '');
    cy.get('@languageDropdown').selectDropdownValue('Français');
    cy.findByRole('button', { name: 'Add' }).click();

    // Wait modal to be open
    cy.findByRole('dialog').should('have.attr', 'data-cy-shown', 'true');

    // Provide translations for helpdesk items
    cy.findByRole('table', { name: 'Helpdesk translations' }).as('helpdeskTranslationsTable');
    cy.get('@helpdeskTranslationsTable').findAllByRole('row').as('helpdeskTranslationsRows');

    // Find row index in helpdeskTranslationsRows where the category is the tile title
    cy.get('@helpdeskTranslationsRows').each((row, index) => {
        if (row.find('td[aria-label="Category"]').text().trim() === `GLPI page: ${tile_title}`) {
            cy.wrap(index).as('helpdeskTileRowIndex');
        }
    });

    cy.get('@helpdeskTileRowIndex').then((index) => {
        cy.get('@helpdeskTranslationsRows').eq(index + 1).within(() => {
            cy.findByRole('cell', { name: 'Translation name' }).contains('Title');
            cy.findByRole('cell', { name: 'Default value' }).contains(tile_title);
            cy.findByRole('cell', { name: 'Translated value' })
                .findByRole('textbox', { name: 'Enter translation' })
                .type(`${tile_title} in French`);
        });
        cy.get('@helpdeskTranslationsRows').eq(index + 2).within(() => {
            cy.findByRole('cell', { name: 'Translation name' }).contains('Description');
            cy.findByRole('cell', { name: 'Default value' }).contains(tile_description);
            cy.findByRole('cell', { name: 'Translated value' })
                .findByLabelText('Enter translation')
                .awaitTinyMCE()
                .type(`${tile_description} in French`);
        });
    });

    // Save the translations
    cy.findByRole('button', { name: 'Save translation' }).click();
    cy.checkAndCloseAlert('Item successfully updated');
}

function changeUserLanguage(language) {
    cy.updateTestUserSettings({
        'language': language
    });

    // We need to logout and login to apply the language change
    cy.logout();
    cy.login();
}

describe('Edit helpdesk translations', () => {
    let tile_title;
    let tile_description;

    beforeEach(() => {
        tile_title = `Test Helpdesk Tile ${Date.now()}`;
        tile_description = `This is a test tile for helpdesk translation ${Date.now()}`;

        cy.login();
        cy.changeProfile('Super-Admin');

        cy.createWithAPI('Glpi\\Helpdesk\\Tile\\GlpiPageTile', {
            'title': tile_title,
            'description': tile_description,
            'page': 'faq',
        }).as('tileId').then((tile_id) => {
            cy.createWithAPI('Glpi\\Helpdesk\\Tile\\Item_Tile', {
                'itemtype_item': 'Entity',
                'items_id_item': 1,
                'itemtype_tile': 'Glpi\\Helpdesk\\Tile\\GlpiPageTile',
                'items_id_tile': tile_id,
            });
        });

        // Navigate to the helpdesk translations tab in general configuration
        cy.visit('/front/config.form.php');

        // Find and click on the helpdesk translations tab
        cy.get('#tabspanel .nav-item').contains('Helpdesk translations').within((nav_link) => {
            cy.wrap(nav_link).click();
        });

        cy.findByRole('region', { name: 'Helpdesk translations' }).as('helpdeskTranslations');

        // Delete all helpdesk translations if any exist
        cy.get('@helpdeskTranslations').then((translations) => {
            if (translations.find('button[aria-label="Edit translation"]').length > 0) {
                cy.findAllByRole('button', { name: 'Edit translation' }).each(button => {
                    cy.wrap(button).click();
                    cy.findByRole('dialog').should('have.attr', 'data-cy-shown', 'true');
                    cy.findByRole('button', { name: 'Delete translation' }).click();
                    cy.checkAndCloseAlert('Item successfully purged');
                    cy.findByRole('dialog').should('not.exist');
                });
            }
        });
    });

    afterEach(() => {
        // Make sure the user language is reset to default
        cy.updateTestUserSettings({
            'language': null
        });

        // Remove the created tile
        cy.get('@tileId').then((tile_id) => {
            cy.deleteWithAPI('Glpi\\Helpdesk\\Tile\\GlpiPageTile', tile_id);
        });
    });

    it('can add a new language translation', () => {
        // Add a language translation
        cy.findByRole('button', { name: 'Add language' }).click();
        cy.getDropdownByLabelText('Select language to translate').as('languageDropdown');
        cy.get('@languageDropdown').should('have.value', '');
        cy.get('@languageDropdown').selectDropdownValue('Français');
        cy.findByRole('button', { name: 'Add' }).click();

        // Check we are on the helpdesk translation page
        cy.findByRole('table', { name: 'Helpdesk translations' }).should('exist').within(() => {
            cy.findAllByRole('cell', { name: 'Translation name' }).should('have.length.at.least', 1);
            cy.findAllByRole('cell', { name: 'Default value' }).should('have.length.at.least', 1);
            cy.findAllByRole('cell', { name: 'Translated value' }).should('have.length.at.least', 1);
        });

        // Close the modal
        cy.findByRole('dialog').as('modal');
        cy.get('@modal').should('have.attr', 'data-cy-shown', 'true');
        cy.get('@modal').findByRole('button', { name: 'Close' }).click();
        cy.get('@modal').should('not.exist');

        // Check columns values
        cy.get('#glpi-helpdesk-translations-languages').find('tbody:first>tr:first>td').eq(0).contains('Français');
        cy.get('#glpi-helpdesk-translations-languages').find('tbody:first>tr:first>td').eq(1).findByRole('progressbar').contains('0 %');
        cy.get('#glpi-helpdesk-translations-languages').find('tbody:first>tr:first>td').eq(2).invoke('text').then((text) => {
            expect(parseInt(text.trim())).to.be.greaterThan(0);
        });
        cy.get('#glpi-helpdesk-translations-languages').find('tbody:first>tr:first>td').eq(3).invoke('text').then((text) => {
            expect(text.trim()).to.equal('0');
        });
    });

    it('can add new translations', () => {
        addHelpdeskTranslations(tile_title, tile_description);

        // Open modal
        cy.findByRole('button', { name: 'Edit translation' }).click();
        cy.findByRole('dialog').should('have.attr', 'data-cy-shown', 'true');

        // Check the translations exist
        cy.get('@helpdeskTranslationsTable').findAllByRole('row').as('helpdeskTranslationsRows');
        cy.get('@helpdeskTranslationsRows').should('have.length.at.least', 3); // Header + at least one translation row
    });

    it('can view translations on helpdesk with default language', () => {
        addHelpdeskTranslations(tile_title, tile_description);

        cy.changeProfile('Self-Service');

        // Go to the helpdesk home page
        cy.visit('/');

        // Check the translations with GLPI default language
        cy.findByTestId('quick-access').contains(tile_title);
    });

    it('can view translations on helpdesk in French', () => {
        addHelpdeskTranslations(tile_title, tile_description);

        // Change the user language to French
        changeUserLanguage('fr_FR');
        cy.changeProfile('Self-Service');

        // Go to the helpdesk home page
        cy.visit('/');

        // Check that French translations are applied
        cy.findByRole('region', { name: 'Accès rapide' }).contains(`${tile_title} in French`);
    });

    it('can view translations on helpdesk in Spanish', () => {
        addHelpdeskTranslations(tile_title, tile_description);

        // Change the user language to Spanish
        changeUserLanguage('es_ES');
        cy.changeProfile('Self-Service');

        // Go to the helpdesk home page
        cy.visit('/');

        // Check that default translations are used (no Spanish translation)
        cy.findByTestId('quick-access').contains(tile_title);
    });

    it('can delete a helpdesk translation', () => {
        addHelpdeskTranslations(tile_title, tile_description);

        // Open modal
        cy.findByRole('button', { name: 'Edit translation' }).click();
        cy.findByRole('dialog').should('have.attr', 'data-cy-shown', 'true');

        // Delete the French translation
        cy.findByRole('button', { name: 'Delete translation' }).click();
        cy.checkAndCloseAlert('Item successfully purged');

        // Check if the French translation is deleted
        cy.findByRole('link', { name: 'Français' }).should('not.exist');
    });

    it('check helpdesk translation stats', () => {
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
        cy.get('@helpdeskTranslations').find('tbody tr').eq(0).find('td').eq(0).contains('Français');
        cy.get('@helpdeskTranslations').find('tbody tr').eq(0).find('td').eq(-3).findByRole('progressbar').contains('0 %');
        cy.get('@helpdeskTranslations').find('tbody tr').eq(0).find('td').eq(-2).invoke('text').then((text) => {
            expect(parseInt(text.trim())).to.be.greaterThan(0);
        });
        cy.get('@helpdeskTranslations').find('tbody tr').eq(0).find('td').eq(-1).invoke('text').then((text) => {
            expect(text.trim()).to.equal('0');
        });

        // Add translations
        cy.findByRole('button', { name: 'Edit translation' }).click();
        cy.findByRole('dialog').should('have.attr', 'data-cy-shown', 'true');

        cy.findByRole('table', { name: 'Helpdesk translations' }).as('helpdeskTranslationsTable');
        cy.get('@helpdeskTranslationsTable').findAllByRole('row').as('helpdeskTranslationsRows');

        // Add at least one translation
        cy.get('@helpdeskTranslationsRows').eq(2).within(() => {
            cy.findByRole('cell', { name: 'Translated value' }).findByRole('textbox', { name: 'Enter translation' })
                .type('Traduction de test');
        });

        // Save the translations
        cy.findByRole('button', { name: 'Save translation' }).click();

        // Go back to the helpdesk translations page
        cy.visit('/front/config.form.php');
        cy.get('#tabspanel .nav-item').contains('Helpdesk translations').within((nav_link) => {
            cy.wrap(nav_link).click();
        });

        // Check stats - progress should be greater than 0%
        cy.get('@helpdeskTranslations').find('tbody tr').eq(0).find('td').eq(-3).findByRole('progressbar').should('not.equal', '0 %');
        cy.get('@helpdeskTranslations').find('tbody tr').eq(0).find('td').eq(-2).invoke('text').then((text) => {
            expect(parseInt(text.trim())).to.be.greaterThan(0);
        });
        cy.get('@helpdeskTranslations').find('tbody tr').eq(0).find('td').eq(-1).invoke('text').then((text) => {
            expect(parseInt(text.trim())).to.be.equals(0);
        });
    });

    it('can detect translations to review when default value changes', () => {
        addHelpdeskTranslations(tile_title, tile_description);

        // Add a second language translation
        cy.findByRole('button', { name: 'Add language' }).click();
        cy.getDropdownByLabelText('Select language to translate').selectDropdownValue('Deutsch');
        cy.findByRole('button', { name: 'Add' }).click();

        // Close the modal for German
        cy.findByRole('dialog').as('modal');
        cy.get('@modal').should('have.attr', 'data-cy-shown', 'true');
        cy.get('@modal').findByRole('button', { name: 'Close' }).click();
        cy.get('@modal').should('not.exist');

        // Simulate changing a default value by updating a tile title
        cy.get('@tileId').then((tile_id) => {
            cy.updateWithAPI('Glpi\\Helpdesk\\Tile\\GlpiPageTile', tile_id, {
                'title': 'Modified Helpdesk Tile Title'
            });
        });

        // Reload page to reflect changes
        cy.visit('/front/config.form.php');

        // Find french row
        cy.get('#glpi-helpdesk-translations-languages').find('tbody:first>tr').each((row) => {
            cy.wrap(row).findByRole('button', { name: 'Edit translation' }).then((button) => {
                if (button.text().trim() === 'Français') {
                    cy.wrap(row).as('frenchRow');
                }
                if (button.text().trim() === 'Deutsch') {
                    cy.wrap(row).as('germanRow');
                }
            });
        });

        // Check stats
        cy.get('@frenchRow').find('td').eq(-1).invoke('text').then((text) => {
            expect(text.trim()).to.equal('1');
        });
        cy.get('@germanRow').find('td').eq(-1).invoke('text').then((text) => {
            expect(text.trim()).to.equal('0');
        });

        // Go to the French translation page and check that the translation is marked as "to review"
        cy.get('@frenchRow').findByRole('button', { name: 'Edit translation' }).click();
        cy.get('@helpdeskTranslationsRows').each((row, index) => {
            if (row.find('td[aria-label="Category"]').text().trim() === `GLPI page: ${tile_title}`) {
                cy.wrap(index).as('helpdeskTileRowIndex');
            }
        });

        cy.get('@helpdeskTileRowIndex').then((index) => {
            cy.get('@helpdeskTranslationsRows').eq(index + 1).within(() => {
                cy.get('.ti-alert-circle').should('exist');
            });
        });

        // Close the modal
        cy.findByRole('dialog').as('modal');
        cy.get('@modal').should('have.attr', 'data-cy-shown', 'true');
        cy.get('@modal').findByRole('button', { name: 'Close' }).click();
        cy.get('@modal').should('not.exist');

        // Go to the German translation page and check that the translation isn't marked as "to review"
        cy.get('@germanRow').findByRole('button', { name: 'Edit translation' }).click();
        cy.get('@helpdeskTranslationsRows').each((row) => {
            cy.wrap(row).find('.ti-alert-circle').should('not.exist');
        });
    });
});
