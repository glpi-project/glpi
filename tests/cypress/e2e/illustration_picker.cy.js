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

describe('Illustration picker', () => {
    beforeEach(() => {
        // Go to service catalog config on a freshly created form
        cy.login();
        cy.createFormWithAPI().visitFormTab('ServiceCatalog');
    });

    function openIllustrationPicker()
    {
        // Open illustration picker
        cy.findByRole('dialog').should('not.exist');
        cy.findByRole('button',  {'name': "Select an illustration"}).click();
        cy.findByRole('dialog').should('be.visible');
        cy.findByRole('dialog').should('have.attr', 'data-cy-shown', 'true');
    }

    it('Can pick an image', () => {
        // The default icon should be selected.
        cy.findByRole('img', {'name': 'Request a service'}).should('be.visible');

        // Open icon picker
        openIllustrationPicker();

        // Select another icon, the modal should close itself and the newly selected
        // icon must be displayed.
        cy.findByRole('img', {'name': 'Cartridge'}).click();
        cy.findByRole('dialog').should('not.exist');
        cy.findByRole('img', {'name': 'Cartridge'}).should('be.visible');
        cy.findByRole('img', {'name': 'Request a service'}).should('not.exist');

        // Save and make sure the newly selected image is here.
        cy.findByRole('button', {'name': 'Save changes'}).click();
        cy.findByRole('img', {'name': 'Cartridge'}).should('be.visible');
        cy.findByRole('img', {'name': 'Request a service'}).should('not.exist');
    });

    it('Can use pagination', () => {
        const icons_from_first_page = [
            'Cartridge',
            'Desktop 1',
            'Network equipment',
        ];
        const icons_from_second_page = [
            'Browse help articles',
            'Area chart',
            'Search chart',
        ];

        // We are on the first page by default.
        openIllustrationPicker();
        icons_from_first_page.forEach((name) => {
            cy.findByRole('img', {'name': name}).should('be.visible');
        });
        icons_from_second_page.forEach((name) => {
            cy.findByRole('img', {'name': name}).should('not.exist');
        });

        // Go to second page.
        cy.findByRole('button', {'name': 'Go to page 2'}).click();
        icons_from_first_page.forEach((name) => {
            cy.findByRole('img', {'name': name}).should('not.exist');
        });
        icons_from_second_page.forEach((name) => {
            cy.findByRole('img', {'name': name}).should('be.visible');
        });
        cy.findByRole('dialog').should('be.visible');
    });

    it('Can search for icons', () => {
        openIllustrationPicker();
        cy.findByRole('textbox', {'name': "Search"}).should('be.focused');
        cy.focused().type("Business Intelligence and Reporting");

        const expected_icons = [
            'Business Intelligence and Reporting 1',
            'Business Intelligence and Reporting 2',
            'Business Intelligence and Reporting 3',
        ];

        // Only 3 icons must be found
        cy.findByRole('dialog')
            .findAllByRole('img')
            .should('have.length', expected_icons.length)
        ;
        expected_icons.forEach((name) => {
            cy.findByRole('img', {'name': name}).should('be.visible');
        });
    });

    it('Can upload and use a custom icon', () => {
        // The default icon should be selected.
        cy.findByRole('img', {'name': 'Request a service'}).should('be.visible');

        // Open icon picker
        openIllustrationPicker();

        // Upload an icon
        cy.findByRole('tab', {name: "Upload your own illustration"}).click();
        cy.get('input[type=file]').selectFile("fixtures/uploads/bar.png");
        cy.findByText("Upload successful").should('be.visible');
        cy.findByRole('button', {name: "Use selected file"}).click();

        // Make sure the image is displayed and is valid
        cy
            .get('div[data-glpi-icon-picker-value-preview-custom]')
            .find('img:visible')
            .should('be.visible')
            .and('have.prop', 'naturalWidth')
            .should('be.greaterThan', 0)
        ;

        // Save changes
        cy.findByRole('button', {name: 'Save changes'}).click();
        cy
            .get('div[data-glpi-icon-picker-value-preview-custom]')
            .find('img:visible')
            .should('be.visible')
            .and('have.prop', 'naturalWidth')
            .should('be.greaterThan', 0)
        ;
    });

    it('Can pick an image searchable by tag', () => {
        // The default icon should be selected.
        cy.findByRole('img', {'name': 'Request a service'}).should('be.visible');

        // Open icon picker
        openIllustrationPicker();

        // Search for an icon by tag
        cy.findByRole('textbox', {'name': "Search"}).should('be.focused');
        cy.focused().type("planet");

        // Only 1 icon must be found
        cy.findByRole('dialog')
            .findAllByRole('img')
            .should('have.length', 1)
        ;

        // The icon must be the one we are looking for
        cy.findByRole('img', {'name': 'World'}).should('be.visible');
    });
});
