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

describe("ITIL Followups", () => {
    it("can add a followup to a new ticket", () => {
        cy.createWithAPI("Ticket", {
            name: "Open ticket",
            content: "",
        }).then((id) => {
            cy.login();
            cy.visit(`/front/ticket.form.php?id=${id}`);
            cy.findByRole('button', {name: "Answer"}).should('exist');
        });
    });

    it("can't add a followup to a closed ticket", () => {
        cy.createWithAPI("Ticket", {
            name: "Closed ticket",
            content: "",
            status: 6,
        }).then((id) => {
            cy.login();
            cy.visit(`/front/ticket.form.php?id=${id}`);
            cy.findByRole('button', {name: "Answer"}).should('not.exist');
        });
    });
});
