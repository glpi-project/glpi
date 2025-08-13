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

require('@jest/globals');

describe('Impact', () => {
    //let window_href_spy;
    beforeEach(() => {
        require('/js/impact.js');
        delete window.location;
        Object.defineProperty(window, 'location', {
            value: {
                href: '',
                reload: jest.fn().mockImplementation(() => {})
            },
            writable: true,
            configurable: true,
        });
        //window_href_spy = jest.spyOn(window.location, 'href');
    });
    it('Sets globals', () => {
        expect(window.GLPIImpact).toBeDefined();
    });
    it('Badge Hitbox Search Link Correct', () => {
        window.GLPIImpact.cy = {
            zoom: () => 1,
        };
        window.GLPIImpact.badgesHitboxes = [
            {
                "position": {
                    "x": 600,
                    "y": 420
                },
                "target": "/front/ticket.php?is_deleted=0&as_map=0&search=Search&itemtype=Ticket",
                "itemtype": "Computer",
                "id": "2",
                "id_option": 2
            },
            {
                "position": {
                    "x": 700,
                    "y": 420
                },
                "target": "/front/ticket.php?is_deleted=0&as_map=0&search=Search&itemtype=Ticket",
                "itemtype": "Computer",
                "id": "2",
            }
        ];
        window.location.href = '';
        window.GLPIImpact.checkBadgeHitboxes({x: 601, y: 421}, true, false);
        // This badge has a known ID option, so we should be using the metacriteria
        expect(window.location.href).toBe('/front/ticket.php?is_deleted=0&as_map=0&search=Search&itemtype=Ticket&criteria[0][link]=AND&criteria[0][field]=2&criteria[0][itemtype]=Computer&criteria[0][meta]=1&criteria[0][searchtype]=contains&criteria[0][value]=2&criteria[1][link]=AND&criteria[1][field]=14&criteria[1][searchtype]=equals&criteria[1][value]=1&criteria[2][link]=AND&criteria[2][field]=12&criteria[2][searchtype]=equals&criteria[2][value]=notold');
        window.GLPIImpact.checkBadgeHitboxes({x: 701, y: 421}, true, false);
        // This badge has no ID option, so we should be using a simple criteria as a fallback
        expect(window.location.href).toBe('/front/ticket.php?is_deleted=0&as_map=0&search=Search&itemtype=Ticket&criteria[0][link]=AND&criteria[0][field]=13&criteria[0][searchtype]=contains&criteria[0][value]=2&criteria[1][link]=AND&criteria[1][field]=131&criteria[1][searchtype]=equals&criteria[1][value]=Computer&criteria[2][link]=AND&criteria[2][field]=14&criteria[2][searchtype]=equals&criteria[2][value]=1&criteria[3][link]=AND&criteria[3][field]=12&criteria[3][searchtype]=equals&criteria[3][value]=notold');
        // Test outside hitbox
        window.location.href = '';
        window.GLPIImpact.checkBadgeHitboxes({x: 800, y: 421}, true, false);
        expect(window.location.href).toBe('');
    });
});
