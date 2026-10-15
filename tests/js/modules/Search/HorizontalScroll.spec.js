/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

import HorizontalScroll from '/js/modules/Search/HorizontalScroll.js';

describe('Search horizontal scrolling', () => {
    let controller;
    let scroller;
    let table;
    let controls;

    const rectangle = (left, top, right, bottom) => ({left, top, right, bottom, width: right - left, height: bottom - top});
    const dimensions = (element, values) => {
        for (const [key, value] of Object.entries(values)) {
            Object.defineProperty(element, key, {configurable: true, value});
        }
    };

    beforeEach(() => {
        vi.stubGlobal('ResizeObserver', class {
            observe() {}
            disconnect() {}
        });
        vi.stubGlobal('matchMedia', vi.fn(() => ({matches: false})));
        document.body.innerHTML = `<div style="overflow-x: auto; overflow-y: auto">
            <div><table><tbody><tr><td>Result</td></tr></tbody></table></div>
            <div class="search-horizontal-scroll" hidden>
                <button data-scroll-direction="-1"></button><button data-scroll-direction="1"></button>
            </div>
        </div>`;
        scroller = document.body.firstElementChild;
        table = document.querySelector('table');
        controls = document.querySelector('.search-horizontal-scroll');
        dimensions(document.documentElement, {clientWidth: 1000, clientHeight: 800});
        dimensions(scroller, {clientWidth: 600, clientHeight: 500, scrollWidth: 1200});
        scroller.getBoundingClientRect = () => rectangle(100, 100, 700, 600);
        table.tBodies[0].getBoundingClientRect = () => rectangle(100, -500, 1300, 1500);
        controls.querySelectorAll('button').forEach(button => {
            button.getBoundingClientRect = () => rectangle(0, 0, 14, 32);
        });
        scroller.scrollBy = vi.fn();
        controller = new HorizontalScroll(table);
        controller.update();
    });

    afterEach(() => {
        controller.destroy();
        document.body.innerHTML = '';
        vi.unstubAllGlobals();
    });

    it('positions arrows outside the sides of a vertically clipped table', () => {
        expect(controls.hidden).toBe(false);
        expect(controls.children[0].style.top).toBe('350px');
        expect(controls.children[0].style.left).toBe('86px');
        expect(controls.children[1].style.left).toBe('700px');
        expect(controls.children[0].disabled).toBe(true);
        expect(controls.children[1].disabled).toBe(false);
    });

    it('keeps arrows within the viewport when there is no space outside the table', () => {
        dimensions(scroller, {clientWidth: 1000, scrollWidth: 1200});
        scroller.getBoundingClientRect = () => rectangle(0, 100, 1000, 600);
        table.tBodies[0].getBoundingClientRect = () => rectangle(0, -500, 1200, 1500);
        controller.update();
        expect(controls.children[0].style.left).toBe('0px');
        expect(controls.children[1].style.left).toBe('986px');
    });

    it('scrolls by part of the visible width and disables the arrow at the far edge', () => {
        controls.children[1].click();
        expect(scroller.scrollBy).toHaveBeenCalledWith({left: 300, behavior: 'smooth'});
        scroller.scrollLeft = 600;
        controller.update();
        expect(controls.children[0].disabled).toBe(false);
        expect(controls.children[1].disabled).toBe(true);
        window.matchMedia.mockReturnValue({matches: true});
        controls.children[0].click();
        expect(scroller.scrollBy).toHaveBeenLastCalledWith({left: -300, behavior: 'instant'});
    });

    it('hides controls when the table fits or is outside the visible area', () => {
        dimensions(scroller, {scrollWidth: 600});
        controller.update();
        expect(controls.hidden).toBe(true);
        dimensions(scroller, {scrollWidth: 1200});
        table.tBodies[0].getBoundingClientRect = () => rectangle(100, 900, 1300, 1500);
        controller.update();
        expect(controls.hidden).toBe(true);
    });

    it('changes scroll containers when a responsive inner wrapper becomes scrollable', () => {
        const wrapper = table.parentElement;
        wrapper.style.overflowX = 'auto';
        dimensions(wrapper, {clientWidth: 400, scrollWidth: 1200});
        wrapper.getBoundingClientRect = () => rectangle(100, 100, 500, 600);
        wrapper.scrollBy = vi.fn();
        controller.update();
        controls.children[1].click();
        expect(wrapper.scrollBy).toHaveBeenCalledWith({left: 200, behavior: 'smooth'});
        expect(scroller.scrollBy).not.toHaveBeenCalled();
    });

    it('handles physical left and right edges in RTL containers', () => {
        scroller.style.direction = 'rtl';
        controller.update();
        expect(controls.children[0].disabled).toBe(false);
        expect(controls.children[1].disabled).toBe(true);
        scroller.scrollLeft = -600;
        controller.update();
        expect(controls.children[0].disabled).toBe(true);
        expect(controls.children[1].disabled).toBe(false);
    });

    it('keeps buttons below sticky headers and above sticky pagination', () => {
        scroller.className = 'search-card';
        const heading = table.createTHead().insertRow().insertCell();
        // Search headers use th cells.
        heading.outerHTML = '<th>Heading</th>';
        table.tHead.querySelector('th').getBoundingClientRect = () => rectangle(100, 140, 700, 180);
        const header = document.createElement('div');
        header.className = 'search-header';
        header.getBoundingClientRect = () => rectangle(100, 100, 700, 140);
        const footer = document.createElement('div');
        footer.className = 'search-footer';
        footer.getBoundingClientRect = () => rectangle(100, 550, 700, 600);
        scroller.append(header, footer);
        controller.update();
        expect(controls.children[0].style.top).toBe('365px');
    });

    it('removes floating controls when results are removed', () => {
        table.remove();
        controller.update();
        expect(controls.isConnected).toBe(false);
    });
});
