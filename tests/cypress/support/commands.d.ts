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

declare namespace Cypress {
    interface Chainable<Subject> {
        openEntitySelector(): Chainable<any>
        startToDrag(): Chainable<any>
        dropDraggedItemAfter(): Chainable<any>
        checkAndCloseAlert(text: string): Chainable<any>
        getCsrfToken(): Chainable<any>
        changeEntity(entity: string|number, is_recursive: boolean): Chainable<any>
        validateBreadcrumbs(breadcrumbs: array): Chainable<any>
        validateMenuIsActive(name: string): Chainable<any>
        updateTestUserSettings(object: settings): Chainable<any>
        createWithAPI(itemtype: string, values: array): Chainable<any>
        updateWithAPI(itemtype: string, id: number, values: array): Chainable<any>
        deleteWithAPI(itemtype): Chainable<any>
        searchWithAPI(itemtype: string, values, array): Chainable<any>
    }
}
