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

import { extractAnchor, locateAnchor, overlaps } from '/js/modules/Knowbase/CommentAnchor.js';

describe('extractAnchor', () => {
    it('extracts prefix/exact/suffix around the given range', () => {
        const text = 'The quick brown fox jumps over the lazy dog';
        const start = text.indexOf('brown fox');
        const end = start + 'brown fox'.length;

        const anchor = extractAnchor(text, start, end);

        expect(anchor.exact).toBe('brown fox');
        expect(anchor.prefix.endsWith('quick ')).toBe(true);
        expect(anchor.suffix.startsWith(' jumps')).toBe(true);
        expect(anchor.occurrence).toBe(0);
    });

    it('computes the occurrence index when the quote repeats', () => {
        const text = 'cat and dog and cat and dog';
        const start = text.lastIndexOf('cat');
        const end = start + 'cat'.length;

        const anchor = extractAnchor(text, start, end);

        expect(anchor.exact).toBe('cat');
        expect(anchor.occurrence).toBe(1);
    });
});

describe('locateAnchor', () => {
    it('finds the quote when the text is unchanged', () => {
        const text = 'The quick brown fox jumps over the lazy dog';
        const anchor = { prefix: 'quick ', exact: 'brown fox', suffix: ' jumps', occurrence: 0 };

        expect(locateAnchor(text, anchor)).toEqual([10, 19]);
    });

    it('returns null when the quote no longer exists', () => {
        const text = 'The text has completely changed';
        const anchor = { prefix: 'quick ', exact: 'brown fox', suffix: ' jumps', occurrence: 0 };

        expect(locateAnchor(text, anchor)).toBeNull();
    });

    it('disambiguates repeated quotes using prefix/suffix context', () => {
        const text = 'cat and dog and cat and dog';
        const anchor = { prefix: 'and ', exact: 'cat', suffix: ' and dog', occurrence: 1 };

        const [start, end] = locateAnchor(text, anchor);
        expect(text.slice(start, end)).toBe('cat');
        expect(start).toBe(text.lastIndexOf('cat'));
    });

    it('falls back to the first matching context if the recorded occurrence has shifted', () => {
        const text = 'unique quote appears once here';
        const anchor = { prefix: 'unique ', exact: 'quote', suffix: ' appears', occurrence: 5 };

        expect(locateAnchor(text, anchor)).toEqual([7, 12]);
    });
});

describe('overlaps', () => {
    it('returns only segments intersecting the given range, clipped to it', () => {
        const segments = [
            { start: 0, end: 5 },
            { start: 5, end: 10 },
            { start: 10, end: 20 },
        ];

        const result = overlaps(segments, 3, 12);

        expect(result).toEqual([
            { segment: segments[0], start: 3, end: 5 },
            { segment: segments[1], start: 5, end: 10 },
            { segment: segments[2], start: 10, end: 12 },
        ]);
    });

    it('returns an empty array when nothing overlaps', () => {
        const segments = [{ start: 0, end: 5 }];

        expect(overlaps(segments, 10, 20)).toEqual([]);
    });
});
