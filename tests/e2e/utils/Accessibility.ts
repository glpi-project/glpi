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

import AxeBuilder from '@axe-core/playwright';
import { type Page } from '@playwright/test';

/**
 * The accessibility standard the e2e suite enforces, declared in one place.
 *
 * Target: **WCAG 2.1 level AA**, plus the rules axe groups under `best-practice`.
 *
 * WCAG 2.1 AA is also what the two standards GLPI is held to transpose: EN 301 549 for the
 * European Union and RGAA v4 for France. axe tags its rules `EN-301-549` and `RGAAv4`
 * accordingly, so meeting WCAG 2.1 AA covers both without listing them separately.
 *
 * `best-practice` is not required by any standard, but it carries checks worth keeping --
 * content outside landmarks, heading order, valid ARIA parent/child nesting. Including it
 * keeps the suite as strict as it was before the tags were made explicit.
 *
 * WCAG 2.2 (`wcag22aa`) should cover RGAA v4 (`RGAAv4`), it's deliberately not enabled yet, like
 * European standards (`EN-301-549`)
 */
export const A11Y_STANDARD_TAGS = [
    'wcag2a',        // WCAG 2.0 level A
    'wcag2aa',       // WCAG 2.0 level AA
    'wcag21a',       // WCAG 2.1 level A
    'wcag21aa',      // WCAG 2.1 level AA
    //'wcag22aa',      // WCAG 2.2 level AA - deliberately not enabled yet. Also covers RGAA v4 from e2e pov.
    //'EN-301-549',    // European standard EN 301 549 - deliberately not enabled yet
    'best-practice', // axe recommendations, not required by a standard
];

/**
 * Build an axe scan configured for the suite's standard.
 *
 * Scope it with `.include()` and assert on `.violations`:
 *
 * ```ts
 * const results = await a11yScan(page).include('main').analyze();
 * expect(results.violations).toEqual([]);
 * ```
 *
 * @param page Playwright page to analyze.
 * @param tags Override the standard. Only for a test targeting one success criterion, e.g.
 *             `['wcag135']` for "Identify Input Purpose". Beware that an unknown tag matches
 *             no rule at all, which makes the scan pass while checking nothing.
 */
export function a11yScan(page: Page, tags: string[] = A11Y_STANDARD_TAGS): AxeBuilder
{
    return new AxeBuilder({ page }).withTags(tags);
}
