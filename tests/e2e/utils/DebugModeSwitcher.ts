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

import { APIRequestContext } from 'playwright/test';
import { CsrfFetcher } from './CsrfFetcher';

// Mirrors Session::NORMAL_MODE and Session::DEBUG_MODE constants from PHP.
const enum DebugMode
{
    Normal = 0,
    Debug  = 2,
}

export class DebugModeSwitcher
{
    private request: APIRequestContext;

    private csrf: CsrfFetcher;

    public constructor(request: APIRequestContext, csrf: CsrfFetcher)
    {
        this.request = request;
        this.csrf = csrf;
    }

    public async enable(): Promise<void>
    {
        await this.set(DebugMode.Debug);
    }

    public async disable(): Promise<void>
    {
        await this.set(DebugMode.Normal);
    }

    private async set(mode: DebugMode): Promise<void>
    {
        // We can't simply post the wanted mode: `ajax/switchdebug.php` would
        // store the raw (thus string) POST value into
        // `$_SESSION['glpi_use_mode']`, while `Html::displayFooter()` compares
        // it *strictly* to the integer `Session::DEBUG_MODE`. The debug
        // toolbar would then never be rendered.
        // Going through the toggle branch of `ajax/switchdebug.php` (by not
        // sending any mode at all, which is also what the "Change mode" link
        // of the user menu does) makes GLPI store the integer constant.
        for (let i = 0; i < 3; i++) {
            if (await this.getCurrentMode() === mode) {
                return;
            }
            await this.toggle();
        }

        throw new Error('Failed to switch debug mode');
    }

    private async toggle(): Promise<void>
    {
        await this.request.post('/ajax/switchdebug.php', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-Glpi-Csrf-Token': await this.csrf.get(),
            },
        });
    }

    private async getCurrentMode(): Promise<DebugMode>
    {
        // The debug toolbar is only rendered when the session is *strictly* in
        // debug mode, which makes it a reliable marker.
        const response = await this.request.get('/front/preference.php');
        const body = await response.text();

        return body.includes('id="debug-toolbar-applet"')
            ? DebugMode.Debug
            : DebugMode.Normal
        ;
    }
}
