/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

export class Color {
    constructor(red, green, blue) {
        /** @type {Number} Red channel 0-255 */
        this.r = Math.floor(red);
        /** @type {Number} Green channel 0-255 */
        this.g = Math.floor(green);
        /** @type {Number} Blue channel 0-255 */
        this.b = Math.floor(blue);
    }

    getHex() {
        return `#${this.r.toString(16).padStart(2, '0')}${this.g.toString(16).padStart(2, '0')}${this.b.toString(16).padStart(2, '0')}`;
    }

    /**
     * Get HSL representation of the color where H is 0-360, S is 0-100, and L is 0-100
     * @returns {Array} [H, S, L]
     */
    getHsl() {
        const r = this.r / 255;
        const g = this.g / 255;
        const b = this.b / 255;

        const max = Math.max(r, g, b);
        const min = Math.min(r, g, b);
        let h, s, l = (max + min) / 2;

        if (max === min) {
            h = s = 0;
        } else {
            const d = max - min;
            s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
            switch (max) {
                case r: h = (g - b) / d + (g < b ? 6 : 0); break;
                case g: h = (b - r) / d + 2; break;
                case b: h = (r - g) / d + 4; break;
            }
            h /= 6;
        }

        h = Math.round(h * 360);
        s = Math.round(s * 100);
        l = Math.round(l * 100);

        return [h, s, l];
    }

    static fromHex(hex) {
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        const rgb = result ? {
            r: parseInt(result[1], 16),
            g: parseInt(result[2], 16),
            b: parseInt(result[3], 16)
        } : null;
        if (!rgb) {
            throw new Error(`Invalid hex color: ${hex}`);
        }
        return new Color(rgb.r, rgb.g, rgb.b);
    }

    /**
     * Create a color from HSL values
     * @param h Hue 0-360
     * @param s Saturation 0-100
     * @param l Lightness 0-100
     * @return {Color}
     */
    static fromHsl(h, s, l) {
        h = h / 360;
        s = s / 100;
        l = l / 100;

        let r, g, b;
        if (s === 0) {
            r = g = b = l;
        } else {
            const hue2rgb = function hue2rgb(p, q, t) {
                if (t < 0) t += 1;
                if (t > 1) t -= 1;
                if (t < 1/6) return p + (q - p) * 6 * t;
                if (t < 1/2) return q;
                if (t < 2/3) return p + (q - p) * (2/3 - t) * 6;
                return p;
            };
            const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
            const p = 2 * l - q;
            r = hue2rgb(p, q, h + 1/3);
            g = hue2rgb(p, q, h);
            b = hue2rgb(p, q, h - 1/3);
        }
        return new Color(r * 255, g * 255, b * 255);
    }

    /**
     * Get contrast ratio between two colors
     * https://www.w3.org/TR/2008/REC-WCAG20-20081211/#contrast-ratiodef
     *
     * @param {Color} color
     * @returns {Number}
     */
    contrast(color) {
        return (this.luminance() + 0.05) / (color.luminance() + 0.05);
    }

    /**
     * Get luminance for a color
     * https://www.w3.org/TR/2008/REC-WCAG20-20081211/#relativeluminancedef
     *
     * @returns {Number}
     */
    luminance() {
        const a = [this.r, this.g, this.b].map(function (v) {
            v /= 255;
            return v <= 0.03928
                ? v / 12.92
                : Math.pow( (v + 0.055) / 1.055, 2.4 );
        });
        return (a[0] * 0.2126) + (a[1] * 0.7152) + (a[2] * 0.0722);
    }

    /**
     * Gets a pair of contrasting colors based on a hint color.
     * One of the colors will be a low-saturation color, the other will be a high-saturation color.
     * Alpha channel is not considered.
     * @param {Color} hint_color
     */
    static getContrastingColors(hint_color) {
        const hint_hsl = hint_color.getHsl();
        const hue = hint_hsl[0];

        // Lightness of the hint color
        const hint_l = hint_hsl[2];

        // Lightness of the contrasting colors
        let l1 = hint_l < 50 ? 90 : 25;
        let l2 = hint_l < 50 ? 25 : 90;

        return [
            Color.fromHsl(hue, 90, l1),
            Color.fromHsl(hue, 20, l2),
        ];
    }
}

// For old JS that still needs converted to modules
window.GLPI = window.GLPI || {};
window.GLPI.Color = Color;
