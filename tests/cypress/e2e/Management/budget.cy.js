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

describe('Budget', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin');
    });

    it('Item list display', () => {
        cy.createWithAPI('Budget', {
            name: 'Budget for E2E test',
            entity: 1
        }).as('budget');
        cy.createWithAPI('Computer', {
            name: 'Computer for budget',
            entity: 1
        }).as('computer');
        cy.createWithAPI('DeviceGraphicCard', {
            designation: 'Graphic card for budget',
            entity: 1
        }).as('gpu');

        cy.get('@gpu').then((gpu) => {
            cy.get('@computer').then((computer) => {
                cy.createWithAPI('Item_DeviceGraphicCard', {
                    itemtype: 'Computer',
                    items_id: computer,
                    devicegraphiccards_id: gpu,
                }).as('gpu_item');
            });
        });

        cy.get('@budget').then((budget) => {
            cy.get('@computer').then((computer) => {
                cy.createWithAPI('Infocom', {
                    itemtype: 'Computer',
                    items_id: computer,
                    budgets_id: budget,
                });
            }).then(() => {
                cy.get('@gpu_item').then((gpu_item) => {
                    cy.createWithAPI('Infocom', {
                        itemtype: 'Item_DeviceGraphicCard',
                        items_id: gpu_item,
                        budgets_id: budget,
                    }).then(() => {
                        cy.visit(`/front/budget.form.php?id=${budget}`);
                        cy.findByRole('tab', { name: /Items/i }).click();
                        cy.findAllByRole('table').findByRole('columnheader', { name: /Type/i }).closest('table').within(() => {
                            cy.findAllByRole('row').eq(1).getRowCells().then((cells) => {
                                cy.wrap(cells['Type']).should('contain.text', 'Computer');
                                cy.wrap(cells['Entity']).should('contain.text', 'Root entity > E2ETestEntity');
                                cy.wrap(cells['Name']).should('contain.text', 'Computer for budget');
                                cy.wrap(cells['Serial number']).should('contain.text', '-');
                                cy.wrap(cells['Inventory number']).should('contain.text', '-');
                                cy.wrap(cells['Value']).should('contain.text', '0.00');
                            });
                            cy.findAllByRole('row').eq(2).getRowCells().then((cells) => {
                                cy.wrap(cells['Type']).should('contain.text', 'Graphics card item');
                                cy.wrap(cells['Entity']).should('contain.text', 'Root entity > E2ETestEntity');
                                cy.wrap(cells['Name']).should('contain.text', 'Graphic card for budget');
                                cy.wrap(cells['Serial number']).should('contain.text', '-');
                                cy.wrap(cells['Inventory number']).should('contain.text', '-');
                                cy.wrap(cells['Value']).should('contain.text', '0.00');
                            });
                        });
                    });
                });
            });
        });
    });
});
