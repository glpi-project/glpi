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

import { expect, test } from '../../fixtures/glpi_fixture';
import { Profiles } from '../../utils/Profiles';

test("CSP block injected scripts", async ({ profile, page }) => {
    await profile.set(Profiles.SuperAdmin);

    // Watch for console errors
    const csp_error = page.waitForEvent('console', {
        // Chromium: "Content Security Policy", Firefox: "Content-Security-Policy"
        predicate: (msg) => msg.type() === 'error' && /Content.Security.Policy/.test(msg.text()),
    });

    // Watch for dialogs
    const dialogs: string[] = [];
    page.on('dialog', (dialog) => {
        dialogs.push(dialog.message());
        void dialog.dismiss();
    });

    // Trigger the potential XSS. The console should report the script was blocked
    // and the payload had no effect (no dialogs were opened).
    await page.goto('/test/xss?payload=<script>alert(1)</script>');
    expect((await csp_error).text()).toContain('inline script');
    expect(dialogs).toEqual([]);
});
