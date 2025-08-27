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

describe('Network ports graphical definition', () => {
    beforeEach(() => {
        cy.login();
        cy.changeProfile('Super-Admin');

        cy.createWithAPI('NetworkEquipmentModel', {
            'name': 'Test Network Equipment Model'
        }).as('networkEquipmentModel_id').then((id) => {
            cy.visit(`front/networkequipmentmodel.form.php?id=${id}`);
        });
    });

    function uploadFrontImage() {
        cy.get('input[type="file"][name="_uploader_picture_front[]"]').selectFile('fixtures/uploads/bar.png');
        cy.findByRole('progressbar').should('contain', 'Upload successful');
        cy.findByRole('progressbar').should('contain', 'Upload successful');
        cy.findByRole('progressbar').should('not.exist');
    }

    it('should manage network ports graphical definition', () => {
        // Upload an image for the front view of the network equipment
        uploadFrontImage();
        cy.findByRole('button', { name: 'Save' }).click();
        cy.checkAndCloseAlert('Item successfully updated');

        // Navigate to the graphical slot definition tab
        cy.findByRole('tab', { name: 'Graphical slot definition' }).click();

        // Set up 10 network ports
        cy.findByRole('spinbutton', { name: 'Set number of ports' }).clear();
        cy.findByRole('spinbutton', { name: 'Set number of ports' }).type(10);
        cy.findByRole('button', { name: 'Add' }).click();

        // Verify that all 10 port buttons are created with default styling
        for (let i = 1; i <= 10; i++) {
            cy.findByRole('button', { name: i })
                .should('exist')
                .and('not.have.class', 'btn-success')
                .and('not.have.class', 'btn-warning');
        }

        // Configure port 1 position on the image
        cy.findByRole('button', { name: '1' }).click();
        cy.get('.cropper-container > img').then((img) => {
            const cropper_sel = img.get(0).cropper.getCropperSelection();
            cropper_sel.$change(50, 50, 50, 50);
        });
        cy.findByRole('button', { name: 'Save port data' }).click();
        cy.findByRole('button', { name: '1' }).should('have.class', 'btn-success');

        // Configure port 2 position on the image
        cy.findByRole('button', { name: '2' }).click();
        cy.get('.cropper-container > img').then((img) => {
            const cropper_sel = img.get(0).cropper.getCropperSelection();
            cropper_sel.$change(150, 150, 50, 50);
        });
        cy.findByRole('button', { name: 'Save port data' }).click();
        cy.findByRole('button', { name: '2' }).should('have.class', 'btn-success');

        // Configure port 3 position on the image
        cy.findByRole('button', { name: '3' }).click();
        cy.get('.cropper-container > img').then((img) => {
            const cropper_sel = img.get(0).cropper.getCropperSelection();
            cropper_sel.$change(250, 250, 50, 50);
        });
        cy.findByRole('button', { name: 'Save port data' }).click();
        cy.findByRole('button', { name: '3' }).should('have.class', 'btn-success');

        // Reload page to verify data persistence
        cy.reload();

        // Test reset functionality on port 2
        cy.findByRole('button', { name: '2' }).click();
        cy.findByRole('button', { name: 'Reset' }).click();
        cy.findByRole('button', { name: '2' })
            .should('not.have.class', 'btn-success')
            .and('not.have.class', 'btn-warning')
            .and('have.class', 'btn-outline-secondary');

        // Test adding and removing additional zones
        cy.findByRole('button', { name: 'Add zone' }).click();
        cy.findByRole('button', { name: '11' }).should('exist');

        cy.findByRole('button', { name: 'Remove zone' }).click();
        cy.findByRole('button', { name: '11' }).should('not.exist');

        // Test clear all data functionality with confirmation
        cy.findByRole('button', { name: 'Are you sure?' }).should('not.exist');
        cy.findByRole('button', { name: 'Clear data' }).click();
        cy.findByRole('button', { name: 'Are you sure?' }).should('exist');
        cy.findByRole('button', { name: 'Are you sure?' }).click();

        // Verify that the interface is reset to initial state
        cy.findByRole('spinbutton', { name: 'Set number of ports' }).should('exist');
    });

    it('should view defined ports', () => {
        // Upload an image for the front view of the network equipment
        uploadFrontImage();
        cy.findByRole('button', { name: 'Save' }).click();
        cy.checkAndCloseAlert('Item successfully updated');

        // Navigate to the graphical slot definition tab
        cy.findByRole('tab', { name: 'Graphical slot definition' }).click();

        // Set up 3 network ports
        cy.findByRole('spinbutton', { name: 'Set number of ports' }).clear();
        cy.findByRole('spinbutton', { name: 'Set number of ports' }).type(5);
        cy.findByRole('button', { name: 'Add' }).click();

        // Configure ports
        for (let i = 1; i <= 5; i++) {
            cy.findByRole('button', { name: i.toString() }).click();
            cy.findByRole('textbox', { name: 'Port Label' }).clear();
            cy.findByRole('textbox', { name: 'Port Label' }).type(`Port ${i}`);
            cy.get('.cropper-container > img').then((img) => {
                const cropper_sel = img.get(0).cropper.getCropperSelection();
                cropper_sel.$change(50 * i, 50 * i, 50, 50);
            });
            cy.findByRole('button', { name: 'Save port data' }).click();
            cy.findByRole('button', { name: `Port ${i.toString()}` }).should('have.class', 'btn-success');
        }

        // Add network equipment
        cy.get('@networkEquipmentModel_id').then((model_id) => {
            cy.createWithAPI('NetworkEquipment', {
                'name': 'Test Network Equipment',
                'networkequipmentmodels_id': model_id
            }).as('networkEquipment_id').then((id) => {
                // Add network ports
                for (let i = 1; i <= 5; i++) {
                    cy.createWithAPI('NetworkPort', {
                        'name'          : `Port ${i}`,
                        'logical_number': i,
                        'itemtype'      : 'NetworkEquipment',
                        'items_id'      : id,
                        'ifstatus'      : i
                    });
                }

                cy.visit(`front/networkequipment.form.php?id=${id}`);
            });
        });

        // Navigate to the network ports tab
        cy.findByRole('tab', { name: 'Network ports 5' }).click();

        // Verify that the 5 ports are displayed with correct status
        cy.get('.stencil-view').within(() => {
            cy.findByRole('link', { name: 'Port 1' }).within(() => {
                cy.get('span.status-dot').should('have.class', 'status-green').should('have.attr', 'title', 'Connected');
            });
            cy.findByRole('link', { name: 'Port 2' }).within(() => {
                cy.get('span.status-dot').should('have.class', 'status-red').should('have.attr', 'title', 'Not connected');
            });
            cy.findByRole('link', { name: 'Port 3' }).within(() => {
                cy.get('span.status-dot').should('have.class', 'status-orange').should('have.attr', 'title', 'Testing');
            });
            cy.findByRole('link', { name: 'Port 4' }).within(() => {
                cy.get('span.status-dot').should('have.class', 'status-muted').should('have.attr', 'title', 'Unknown');
            });
            cy.findByRole('link', { name: 'Port 5' }).within(() => {
                cy.get('span.status-dot').should('have.class', 'status-muted').should('have.attr', 'title', 'Dormant');
            });
        });
    });
});
