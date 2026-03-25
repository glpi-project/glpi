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

/**
 * Auto-detection module for the Stencil editor.
 *
 * Provides canvas pixel-analysis functions to automatically compute a
 * rectangular bounding box around a port area when the user clicks on it.
 */

const MIN_REGION_PIXELS = 4;

/**
 * Returns the [r, g, b, a] color at position (x, y) from an ImageData object.
 *
 * @param {ImageData} image_data
 * @param {number}    width  Canvas / image width in pixels.
 * @param {number}    x
 * @param {number}    y
 * @returns {number[]} [r, g, b, a]
 */
export function getColorAt(image_data, width, x, y) {
    const index = (y * width + x) * 4;
    return [
        image_data.data[index],
        image_data.data[index + 1],
        image_data.data[index + 2],
        image_data.data[index + 3],
    ];
}

/**
 * Returns the Euclidean distance between two RGBA colors, ignoring alpha.
 *
 * @param {number[]} c1 [r, g, b, a]
 * @param {number[]} c2 [r, g, b, a]
 * @returns {number}
 */
export function colorDistance(c1, c2) {
    const dr = c1[0] - c2[0];
    const dg = c1[1] - c2[1];
    const db = c1[2] - c2[2];
    return Math.sqrt(dr * dr + dg * dg + db * db);
}

/**
 * Performs a flood-fill starting at (start_x, start_y) and returns the
 * axis-aligned bounding box of all pixels within `tolerance` of the seed
 * color.  Returns null when the resulting region is too small to be
 * meaningful.
 *
 * @param {ImageData} image_data
 * @param {number}    width       Width of the image in pixels.
 * @param {number}    height      Height of the image in pixels.
 * @param {number}    start_x     Click X coordinate (integer, within image).
 * @param {number}    start_y     Click Y coordinate (integer, within image).
 * @param {number}    tolerance   Maximum color distance [0-441] for inclusion.
 * @returns {{x: number, y: number, width: number, height: number}|null}
 */
export function detectBoundingBox(image_data, width, height, start_x, start_y, tolerance) {
    start_x = Math.max(0, Math.min(width - 1, Math.round(start_x)));
    start_y = Math.max(0, Math.min(height - 1, Math.round(start_y)));

    const seed_color = getColorAt(image_data, width, start_x, start_y);

    const visited = new Uint8Array(width * height);
    const queue = [start_x + start_y * width];
    visited[start_x + start_y * width] = 1;

    let min_x = start_x;
    let max_x = start_x;
    let min_y = start_y;
    let max_y = start_y;
    let pixel_count = 0;

    while (queue.length > 0) {
        const pos = queue.shift();
        const px = pos % width;
        const py = (pos - px) / width;

        const color = getColorAt(image_data, width, px, py);
        if (colorDistance(color, seed_color) > tolerance) {
            continue;
        }

        pixel_count++;
        if (px < min_x) { min_x = px; }
        if (px > max_x) { max_x = px; }
        if (py < min_y) { min_y = py; }
        if (py > max_y) { max_y = py; }

        const neighbors = [
            [px - 1, py],
            [px + 1, py],
            [px, py - 1],
            [px, py + 1],
        ];

        for (const [nx, ny] of neighbors) {
            if (nx < 0 || nx >= width || ny < 0 || ny >= height) {
                continue;
            }
            const n_pos = nx + ny * width;
            if (visited[n_pos]) {
                continue;
            }
            visited[n_pos] = 1;
            queue.push(n_pos);
        }
    }

    if (pixel_count < MIN_REGION_PIXELS) {
        return null;
    }

    return {
        x: min_x,
        y: min_y,
        width: max_x - min_x + 1,
        height: max_y - min_y + 1,
    };
}

/**
 * Generates grid-replicated bounding boxes given a template box and grid
 * parameters.
 *
 * @param {{x: number, y: number, width: number, height: number}} template_box
 * @param {number} cols        Number of columns.
 * @param {number} rows        Number of rows.
 * @param {number} spacing_x   Horizontal spacing between boxes (pixels).
 * @param {number} spacing_y   Vertical spacing between boxes (pixels).
 * @param {number} img_width   Image width constraint.
 * @param {number} img_height  Image height constraint.
 * @returns {{x: number, y: number, width: number, height: number}[]}
 */
export function generateGrid(template_box, cols, rows, spacing_x, spacing_y, img_width, img_height) {
    const boxes = [];
    const step_x = template_box.width + spacing_x;
    const step_y = template_box.height + spacing_y;

    for (let row = 0; row < rows; row++) {
        for (let col = 0; col < cols; col++) {
            const bx = template_box.x + col * step_x;
            const by = template_box.y + row * step_y;
            const bw = template_box.width;
            const bh = template_box.height;

            if (bx + bw > img_width || by + bh > img_height) {
                continue;
            }

            boxes.push({ x: bx, y: by, width: bw, height: bh });
        }
    }

    return boxes;
}

/**
 * Finds the most prominent (most frequently occurring) colour in a
 * rectangular region of an ImageData object and returns both the colour
 * and a seed pixel position (the pixel with that colour closest to the
 * centre of the region).  Fully-transparent pixels (alpha = 0) are
 * ignored.  Returns null when no opaque pixels are found.
 *
 * @param {ImageData} image_data
 * @param {number}    width    Width of the full image in pixels.
 * @param {number}    x        Left edge of the region (integer).
 * @param {number}    y        Top edge of the region (integer).
 * @param {number}    w        Width of the region (integer).
 * @param {number}    h        Height of the region (integer).
 * @returns {{color: number[], seed_x: number, seed_y: number}|null}
 */
export function getMostProminentColor(image_data, width, x, y, w, h) {
    const QUANT = 16;
    const color_counts = new Map();

    for (let py = y; py < y + h; py++) {
        for (let px = x; px < x + w; px++) {
            const color = getColorAt(image_data, width, px, py);
            if (color[3] === 0) {
                continue;
            }
            const qr = Math.round(color[0] / QUANT) * QUANT;
            const qg = Math.round(color[1] / QUANT) * QUANT;
            const qb = Math.round(color[2] / QUANT) * QUANT;
            const key = `${qr},${qg},${qb}`;
            color_counts.set(key, (color_counts.get(key) || 0) + 1);
        }
    }

    if (color_counts.size === 0) {
        return null;
    }

    let best_key = null;
    let best_count = 0;
    for (const [key, count] of color_counts) {
        if (count > best_count) {
            best_count = count;
            best_key = key;
        }
    }

    const [qbr, qbg, qbb] = best_key.split(',').map(Number);
    const cx = x + w / 2;
    const cy = y + h / 2;
    let best_dist = Infinity;
    let seed_x = Math.round(cx);
    let seed_y = Math.round(cy);
    let seed_color = null;

    for (let py = y; py < y + h; py++) {
        for (let px = x; px < x + w; px++) {
            const color = getColorAt(image_data, width, px, py);
            if (color[3] === 0) {
                continue;
            }
            const qr = Math.round(color[0] / QUANT) * QUANT;
            const qg = Math.round(color[1] / QUANT) * QUANT;
            const qb = Math.round(color[2] / QUANT) * QUANT;
            if (qr === qbr && qg === qbg && qb === qbb) {
                const dist = (px - cx) * (px - cx) + (py - cy) * (py - cy);
                if (dist < best_dist) {
                    best_dist = dist;
                    seed_x = px;
                    seed_y = py;
                    seed_color = color;
                }
            }
        }
    }

    return { color: seed_color, seed_x, seed_y };
}

if (typeof window !== 'undefined') {
    window.StencilAutoDetect = {
        getColorAt,
        colorDistance,
        detectBoundingBox,
        generateGrid,
        getMostProminentColor,
    };
}
