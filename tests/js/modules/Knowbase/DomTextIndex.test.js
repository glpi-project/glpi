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

import { buildDomTextIndex, domRangeToOffsets, wrapOffsetsInMarks } from '/js/modules/Knowbase/DomTextIndex.js';

describe('buildDomTextIndex', () => {
    it('concatenates text nodes with no separator and records their offsets', () => {
        document.body.innerHTML = '<div id="root"><p>Hello</p><p>world</p></div>';
        const root = document.getElementById('root');

        const index = buildDomTextIndex(root);

        expect(index.text).toBe('Helloworld');
        expect(index.segments).toHaveLength(2);
        expect(index.segments[0]).toMatchObject({ start: 0, end: 5 });
        expect(index.segments[1]).toMatchObject({ start: 5, end: 10 });
    });
});

describe('domRangeToOffsets', () => {
    it('converts a Range within a single text node to plain-text offsets', () => {
        document.body.innerHTML = '<div id="root"><p>Hello world</p></div>';
        const root = document.getElementById('root');
        const index = buildDomTextIndex(root);
        const text_node = root.querySelector('p').firstChild;

        const range = document.createRange();
        range.setStart(text_node, 6);
        range.setEnd(text_node, 11);

        expect(domRangeToOffsets(index, range)).toEqual([6, 11]);
    });
});

describe('wrapOffsetsInMarks', () => {
    it('wraps the matched offsets in a single paragraph in one <mark>', () => {
        document.body.innerHTML = '<div id="root"><p>Hello world</p></div>';
        const root = document.getElementById('root');
        const index = buildDomTextIndex(root);

        wrapOffsetsInMarks(index, 6, 11, 42);

        const mark = root.querySelector('mark.kb-comment-highlight');
        expect(mark).not.toBeNull();
        expect(mark.textContent).toBe('world');
        expect(mark.dataset.commentId).toBe('42');
    });

    it('wraps each text node separately when the range crosses a block boundary', () => {
        document.body.innerHTML = '<div id="root"><p>Hello</p><p>world</p></div>';
        const root = document.getElementById('root');
        const index = buildDomTextIndex(root);

        wrapOffsetsInMarks(index, 3, 7, 7);

        const marks = root.querySelectorAll('mark.kb-comment-highlight');
        expect(marks).toHaveLength(2);
        expect(marks[0].textContent).toBe('lo');
        expect(marks[1].textContent).toBe('wo');
    });
});
