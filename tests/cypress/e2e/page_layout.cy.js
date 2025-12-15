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

describe('Page layout', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin');
    });
    // it('Accessibility', () => {
    //     cy.visit('/front/computer.php');
    //     cy.disableAnimations();
    //     cy.get('aside.navbar.sidebar .nav-link.dropdown-toggle:visible').each(($el) => {
    //         cy.wrap($el).click();
    //         cy.get('aside.navbar.sidebar').injectAndCheckA11y();
    //     });
    //
    //     cy.get('.navbar-nav.user-menu:visible').click();
    //     cy.get('.navbar-nav.user-menu:visible .dropdown-menu').injectAndCheckA11y();
    //
    //     cy.get('.navbar-nav.user-menu:visible .dropdown-menu a.entity-dropdown-toggle').click();
    //     cy.get('.navbar-nav.user-menu:visible .dropdown-menu a.entity-dropdown-toggle + .dropdown-menu').injectAndCheckA11y();
    //
    //     cy.get('header.navbar').injectAndCheckA11y();
    // });

    it('Fuzzy search modal', () => {
        cy.visit('/front/computer.php');
        // Open fuzzy search modal using Ctrl + Alt + G
        cy.get('body').type('{ctrl}{alt}g');
        cy.findByRole('dialog', { name: /Go to menu/ }).should('be.visible');
        cy.findByRole('button', { name: /Close/ }).click();
        cy.findByRole('dialog', { name: /Go to menu/ }).should('not.exist');
        // Open with Command + Option + G (MacOS)
        cy.get('body').type('{meta}{alt}g');
        cy.findByRole('dialog', { name: /Go to menu/ }).should('be.visible');
        cy.findByRole('button', { name: /Close/ }).click();
        cy.findByRole('dialog', { name: /Go to menu/ }).should('not.exist');
        // Open with button
        cy.findByRole('button', { name: /Find menu/ }).click();
        cy.findByRole('dialog', { name: /Go to menu/ }).should('be.visible');

        cy.findByRole('dialog', { name: /Go to menu/ }).within(() => {
            cy.findAllByRole('listitem').should('have.length.greaterThan', 3);
            cy.findByPlaceholderText('Start typing to find a menu').type('Computer');
            cy.findAllByRole('listitem').contains('Computer').should('have.length.greaterThan', 0);
            cy.findAllByRole('listitem').contains('Monitor').should('have.length', 0);
            cy.findByPlaceholderText('Start typing to find a menu').type('{selectAll}Cmptr');
            cy.findAllByRole('listitem').contains('Computer').should('have.length.greaterThan', 0);
            cy.findAllByRole('listitem').contains('Monitor').should('have.length', 0);

            cy.root().type('{downArrow}');
            cy.root().type('{downArrow}');
            cy.root().type('{downArrow}');
            cy.findAllByRole('listitem').eq(3).should('have.class', 'active');
            cy.root().type('{upArrow}');
            cy.findAllByRole('listitem').eq(2).should('have.class', 'active');
            cy.findAllByRole('listitem').eq(2).then(($el) => {
                const href = $el.find('a').attr('href');
                cy.root().type('{enter}');
                cy.url().should('include', href);
            });
        });
    });
});
