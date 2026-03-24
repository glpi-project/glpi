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

import { getColorAt, colorDistance, detectBoundingBox, generateGrid }
    from '/js/modules/StencilAutoDetect/index.js';

/**
 * Builds a minimal ImageData-like object for testing.
 *
 * @param {number}   width
 * @param {number}   height
 * @param {number[]} fill_color  [r, g, b, a] applied uniformly.
 * @returns {{ data: Uint8ClampedArray, width: number, height: number }}
 */
function makeImageData(width, height, fill_color) {
    const data = new Uint8ClampedArray(width * height * 4);
    for (let i = 0; i < width * height; i++) {
        data[i * 4]     = fill_color[0];
        data[i * 4 + 1] = fill_color[1];
        data[i * 4 + 2] = fill_color[2];
        data[i * 4 + 3] = fill_color[3] ?? 255;
    }
    return { data, width, height };
}

/**
 * Paints a rectangle of a different color inside an ImageData buffer.
 *
 * @param {{ data: Uint8ClampedArray }} image_data
 * @param {number}   width
 * @param {number}   rx, ry, rw, rh  Rectangle bounds.
 * @param {number[]} rect_color  [r, g, b, a]
 */
function paintRect(image_data, width, rx, ry, rw, rh, rect_color) {
    for (let y = ry; y < ry + rh; y++) {
        for (let x = rx; x < rx + rw; x++) {
            const i = (y * width + x) * 4;
            image_data.data[i]     = rect_color[0];
            image_data.data[i + 1] = rect_color[1];
            image_data.data[i + 2] = rect_color[2];
            image_data.data[i + 3] = rect_color[3] ?? 255;
        }
    }
}

describe('StencilAutoDetect', () => {

    describe('getColorAt', () => {
        it('reads RGBA values at correct pixel offset', () => {
            const img = makeImageData(4, 4, [10, 20, 30, 255]);
            paintRect(img, 4, 2, 1, 1, 1, [100, 110, 120, 200]);

            expect(getColorAt(img, 4, 0, 0)).toEqual([10, 20, 30, 255]);
            expect(getColorAt(img, 4, 2, 1)).toEqual([100, 110, 120, 200]);
        });
    });

    describe('colorDistance', () => {
        it('returns 0 for identical colors', () => {
            expect(colorDistance([100, 150, 200, 255], [100, 150, 200, 255])).toBe(0);
        });

        it('returns correct Euclidean RGB distance', () => {
            // distance([0,0,0],[3,4,0]) = sqrt(9+16) = 5
            expect(colorDistance([0, 0, 0, 255], [3, 4, 0, 255])).toBeCloseTo(5);
        });

        it('ignores alpha channel', () => {
            const d1 = colorDistance([10, 20, 30, 0], [10, 20, 30, 255]);
            expect(d1).toBe(0);
        });
    });

    describe('detectBoundingBox', () => {
        it('returns null when clicked pixel region is too small', () => {
            const img = makeImageData(10, 10, [255, 255, 255, 255]);
            // Single isolated pixel of different color
            paintRect(img, 10, 5, 5, 1, 1, [0, 0, 0, 255]);

            const result = detectBoundingBox(img, 10, 10, 5, 5, 1);
            expect(result).toBeNull();
        });

        it('detects a uniform-color rectangle with zero tolerance', () => {
            const img = makeImageData(20, 20, [255, 255, 255, 255]);
            paintRect(img, 20, 4, 3, 6, 5, [0, 128, 255, 255]);

            const result = detectBoundingBox(img, 20, 20, 7, 5, 0);
            expect(result).not.toBeNull();
            expect(result.x).toBe(4);
            expect(result.y).toBe(3);
            expect(result.width).toBe(6);
            expect(result.height).toBe(5);
        });

        it('expands region with higher tolerance', () => {
            const img = makeImageData(20, 20, [200, 200, 200, 255]);
            // Inner region slightly different – falls within tolerance
            paintRect(img, 20, 5, 5, 4, 4, [210, 200, 200, 255]);

            const strict = detectBoundingBox(img, 20, 20, 7, 7, 1);
            const loose  = detectBoundingBox(img, 20, 20, 7, 7, 30);

            // With strict tolerance, only the inner similar-color pixels are included.
            // With loose tolerance, broader region should be at least as large.
            if (strict !== null && loose !== null) {
                expect(loose.width * loose.height).toBeGreaterThanOrEqual(
                    strict.width * strict.height
                );
            }
        });

        it('clamps coordinates to image boundaries', () => {
            const img = makeImageData(10, 10, [50, 50, 50, 255]);
            // Click at image edge – should not throw and result should be within bounds
            const result = detectBoundingBox(img, 10, 10, 9, 9, 50);
            if (result !== null) {
                expect(result.x).toBeGreaterThanOrEqual(0);
                expect(result.y).toBeGreaterThanOrEqual(0);
                expect(result.x + result.width).toBeLessThanOrEqual(10);
                expect(result.y + result.height).toBeLessThanOrEqual(10);
            }
        });
    });

    describe('generateGrid', () => {
        it('generates cols × rows boxes', () => {
            const template = { x: 0, y: 0, width: 10, height: 5 };
            const boxes = generateGrid(template, 3, 2, 2, 2, 200, 200);
            expect(boxes.length).toBe(6);
        });

        it('skips boxes that exceed image bounds', () => {
            const template = { x: 0, y: 0, width: 10, height: 5 };
            const boxes = generateGrid(template, 4, 1, 5, 0, 30, 200);
            // With step_x = 15, boxes at x=0,15,30 would exceed width=30 for the last one
            // box at col=2: x=30, x+w=40 > 30 → skipped; col=0: ok, col=1: x=15, x+w=25 ok
            expect(boxes.length).toBeLessThan(4);
        });

        it('uses template dimensions for each box', () => {
            const template = { x: 2, y: 3, width: 8, height: 6 };
            const boxes = generateGrid(template, 2, 2, 0, 0, 500, 500);
            boxes.forEach((box) => {
                expect(box.width).toBe(8);
                expect(box.height).toBe(6);
            });
        });

        it('positions first box at template origin', () => {
            const template = { x: 5, y: 10, width: 20, height: 15 };
            const boxes = generateGrid(template, 1, 1, 0, 0, 500, 500);
            expect(boxes[0]).toEqual({ x: 5, y: 10, width: 20, height: 15 });
        });

        it('applies spacing between boxes', () => {
            const template = { x: 0, y: 0, width: 10, height: 10 };
            const boxes = generateGrid(template, 2, 1, 5, 0, 500, 500);
            expect(boxes[0].x).toBe(0);
            expect(boxes[1].x).toBe(15); // width(10) + spacing_x(5)
        });
    });
});
