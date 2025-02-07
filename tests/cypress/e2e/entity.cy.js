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

let entity_id;

describe('Entity', () => {
    beforeEach(() => {
        const unique_id = (new Date()).getTime();
        cy.createWithAPI("Entity", {
            name: `Test entity ${unique_id}`,
        }).then((id) => {
            entity_id = id;
            cy.login();
        });
    });

    it('Can configure assistance properties', () => {
        cy.visit(`/front/entity.form.php?id=${entity_id}&forcetab=Entity$5`);
        cy.getDropdownByLabelText('Show tickets properties on helpdesk')
            .should('have.text', 'Inheritance of the parent entity')
            .selectDropdownValue('Yes')
        ;
        cy.findByRole('button', {'name': "Save"}).click();
        cy.getDropdownByLabelText('Show tickets properties on helpdesk')
            .should('have.text', 'Yes')
        ;
    });
});
