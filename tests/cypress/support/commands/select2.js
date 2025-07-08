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

Cypress.Commands.addQuery('getDropdownByLabelText', (
    label_text
) => {
    return (previous_subject) => {
        // Target might be the whole DOM or a specific existing DOM node
        const getNode = (selector) => {
            return previous_subject ? previous_subject.find(selector) : Cypress.$(selector);
        };

        // Look for a label with the given text
        const $label = getNode(`label:contains("${label_text}")`);

        let $select = null;
        if ($label.length > 0) {
            // Look for the select using the "for" attribute of the label
            const label_for = $label.attr("for");
            $select = getNode(`#${label_for}`);
        } else {
            // Fallback; try to use "aria-label" attribute
            $select = getNode(`select[aria-label="${label_text}"][data-select2-id]`);
        }

        // No select found, return empty jquery item
        if ($select.length === 0) {
            return cy.$$(null);
        }

        // Select container is the next node
        const $select2_container = $select.next();
        return $select2_container.find('[role=combobox]:visible');
    };
});

Cypress.Commands.add('selectDropdownValue', {prevSubject: true}, (
    subject,
    new_value
) => {
    cy.wrap(subject).click();

    // Select2 content is displayed at the root of the DOM, we must thus
    // "recalibrate" the within function using the entire document.
    // Without this, any call inside a `within` block would fail as the select2
    // content will be unreachable.
    cy.document().its('body').within(() => {
        // Reduce the scope to the dropdown
        if (subject.hasClass('select2-selection--multiple')) {
            cy.wrap(subject).find('.select2-search__field').then(($input) => {
                cy.get(`#${$input.attr('aria-controls')}`)
                    .findByRole('option', { name: new_value })
                    .click();
            });
        } else {
            const select2_id = subject.get(0).children[0].id.replace('-container', '');
            cy.get(`[id="${select2_id}-results"]`).findByRole('option', { name: new_value }).click();
        }
    });

});

Cypress.Commands.add('hasDropdownValue', {prevSubject: true}, (
    subject,
    expected_value,
    should_exist = true
) => {
    cy.wrap(subject).click();

    // Select2 content is displayed at the root of the DOM, we must thus
    // "recalibrate" the within function using the entire document.
    // Without this, any call inside a `within` block would fail as the select2
    // content will be unreachable.
    cy.document().its('body').within(() => {
        // Reduce the scope to the dropdown
        if (subject.hasClass('select2-selection--multiple')) {
            cy.wrap(subject).find('.select2-search__field').then(($input) => {
                cy.get(`#${$input.attr('aria-controls')}`)
                    .findByRole('option', { name: expected_value })
                    .should(should_exist ? 'exist' : 'not.exist');
            });
        } else {
            const select2_id = subject.get(0).children[0].id.replace('-container', '');
            cy.get(`[id="${select2_id}-results"]`).findByRole('option', { name: expected_value }).should(should_exist ? 'exist' : 'not.exist');
        }
    });

    // Close the dropdown
    cy.wrap(subject).click();

    // Ensure the dropdown is closed
    cy.get('body').should('not.have.class', 'select2-container--open');
});
