/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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
    it('Undo/Redo - Generic', () => {
        window.GLPIImpact.addToUndo('action1', {'something': 'test'});
        window.GLPIImpact.addToUndo('action2', {'else': 'test2'});
        window.GLPIImpact.addToUndo('action3', {'another': 'test3'});
        expect(window.GLPIImpact.undoStack.length).toBe(3);
        expect(window.GLPIImpact.redoStack.length).toBe(0);
        window.GLPIImpact.undo();
        expect(window.GLPIImpact.undoStack.length).toBe(2);
        expect(window.GLPIImpact.redoStack.length).toBe(1);
        expect(window.GLPIImpact.redoStack[0].code).toBe('action3');
        window.GLPIImpact.undo();
        expect(window.GLPIImpact.undoStack.length).toBe(1);
        expect(window.GLPIImpact.redoStack.length).toBe(2);
        expect(window.GLPIImpact.redoStack[1].code).toBe('action2');
        window.GLPIImpact.redo();
        expect(window.GLPIImpact.undoStack.length).toBe(2);
        expect(window.GLPIImpact.redoStack.length).toBe(1);
        expect(window.GLPIImpact.undoStack[1].code).toBe('action2');

        // undo all + some extra to test handling empty stack
        window.GLPIImpact.undo();
        window.GLPIImpact.undo();
        window.GLPIImpact.undo();
        window.GLPIImpact.undo();
        expect(window.GLPIImpact.undoStack.length).toBe(0);
        expect(window.GLPIImpact.redoStack.length).toBe(3);

        // redo all + some extra to test handling empty stack
        window.GLPIImpact.redo();
        window.GLPIImpact.redo();
        window.GLPIImpact.redo();
        window.GLPIImpact.redo();
        expect(window.GLPIImpact.undoStack.length).toBe(3);
        expect(window.GLPIImpact.redoStack.length).toBe(0);
    });
    it('Hidden selector', () => {
        window.GLPIImpact.directionVisibility = {};
        expect(window.GLPIImpact.DEFAULT_DEPTH).toBe(5);
        window.GLPIImpact.maxDepth = 3;
        expect(window.GLPIImpact.getHiddenSelector()).toBe(`[flag != 0], [depth > 3][depth !> ${Number.MAX_SAFE_INTEGER}]`);

        window.GLPIImpact.directionVisibility = {[window.GLPIImpact.FORWARD]: 1};
        expect(window.GLPIImpact.getHiddenSelector()).toBe(`[flag = 2], [depth > 3][depth !> ${Number.MAX_SAFE_INTEGER}]`);
        window.GLPIImpact.directionVisibility = {[window.GLPIImpact.BACKWARD]: 1};
        expect(window.GLPIImpact.getHiddenSelector()).toBe(`[flag = 1], [depth > 3][depth !> ${Number.MAX_SAFE_INTEGER}]`);
        window.GLPIImpact.directionVisibility = {
            [window.GLPIImpact.FORWARD]: 1,
            [window.GLPIImpact.BACKWARD]: 1
        };
        expect(window.GLPIImpact.getHiddenSelector()).toBe(`[flag = -1], [depth > 3][depth !> ${Number.MAX_SAFE_INTEGER}]`);
    });
    it('Get network styles', () => {
        const styles = window.GLPIImpact.getNetworkStyle();
        expect(styles).toBeArray();
        expect(styles.length).toBeGreaterThan(0);
        styles.forEach((style) => {
            expect(style).toBeObject();
            expect(style).toHaveProperty('selector');
            expect(style).toSatisfy((s) => Object.prototype.hasOwnProperty.call(s, 'style') || Object.prototype.hasOwnProperty.call(s, 'css'));
        });
    });
    it('Get preset layout', () => {
        const layout = window.GLPIImpact.getPresetLayout({
            'node1': {x: 100, y: 100},
            'node2': {x: 150, y: 200},
            'node3': {x: 200, y: 300},
        });
        expect(window.GLPIImpact.no_positions).toHaveLength(0);
        expect(layout.name).toBe('preset');
        expect(layout.positions).toBeFunction();

        expect(layout.positions({
            data: (k) => ({id: 'node1'}[k]),
            isParent: () => false,
        })).toEqual({x: 100, y: 100});
        expect(layout.positions({
            data: (k) => ({id: 'node2'}[k]),
            isParent: () => false,
        })).toEqual({x: 150, y: 200});
        expect(layout.positions({
            data: (k) => ({id: 'node3'}[k]),
            isParent: () => false,
        })).toEqual({x: 200, y: 300});
        expect(layout.positions({
            data: (k) => ({id: 'node3'}[k]),
            isParent: () => true,
        })).toEqual({x: 0, y: 0});
        expect(window.GLPIImpact.no_positions).toHaveLength(0);
        expect(layout.positions({
            data: (k) => ({id: 'node4'}[k]),
            isParent: () => false,
        })).toEqual({x: 0, y: 0});
        expect(window.GLPIImpact.no_positions).toHaveLength(1);
    });
    it('Make ID for nodes and edges', () => {
        expect(window.GLPIImpact.makeID(1, 'Computer', 2)).toBe('Computer::2');
        expect(window.GLPIImpact.makeID(2, 'start', 'node1')).toBe('start->node1');
        expect(window.GLPIImpact.makeID(99, 'start', 'node1')).toBeNull();
    });
    it('Make ID selector', () => {
        expect(window.GLPIImpact.makeIDSelector('node1')).toBe('[id=\'node1\']');
    });
});
