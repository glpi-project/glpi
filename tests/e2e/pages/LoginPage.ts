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

import { Locator, Page } from "@playwright/test";
import { GlpiPage } from "./GlpiPage";

export class LoginPage extends GlpiPage
{
    // Login form (shown when the user has no active session)
    public readonly login_input: Locator;
    public readonly password_input: Locator;
    public readonly remember_me_checkbox: Locator;
    public readonly login_source_dropdown: Locator;
    public readonly sign_in_button: Locator;

    // 2FA form
    public readonly continue_button: Locator;

    // OAuth authorization consent form
    public readonly oauth_authorization_heading: Locator;
    public readonly oauth_accept_button: Locator;
    public readonly oauth_deny_button: Locator;

    public constructor(page: Page)
    {
        super(page);

        this.login_input           = this.getTextbox('Login');
        this.password_input        = page.getByLabel('Password', { exact: true }).filter({ visible: true });
        this.remember_me_checkbox  = this.getCheckbox('Remember me');
        this.login_source_dropdown = this.getDropdownByLabel('Login source');
        this.sign_in_button        = this.getButton('Sign in');
        this.continue_button       = this.getButton('Continue');

        this.oauth_authorization_heading = page.getByRole('heading', { name: /wants to access your GLPI account/ });
        this.oauth_accept_button         = this.getButton('Accept');
        this.oauth_deny_button           = this.getButton('Deny');
    }

    public async goto(): Promise<void>
    {
        await this.page.goto('/');
    }

    public async gotoOauthAuthorize(
        client_id: string,
        scope: string,
        redirect_uri: string
    ): Promise<void> {
        await this.page.goto(
            `/api.php/Authorize?response_type=code&client_id=${client_id}&scope=${encodeURIComponent(scope)}&redirect_uri=${encodeURIComponent(redirect_uri)}`
        );
    }

    public async doLogin(
        username: string,
        password: string,
        remember_me: boolean = false
    ): Promise<void> {
        await this.login_input.fill(username);
        await this.password_input.fill(password);
        if (remember_me) {
            await this.remember_me_checkbox.check();
        } else {
            await this.remember_me_checkbox.uncheck();
        }
        await this.doSetDropdownValue(this.login_source_dropdown, 'GLPI internal database');
        await this.sign_in_button.click();
    }

    public async doGetAuthorizationCode(): Promise<string>
    {
        const current_url = this.page.url();
        const response = await this.page.request.fetch(`${current_url}&accept=1`, {
            maxRedirects: 0,
        });
        const location = response.headers()['location'] ?? '';
        const code = new URL(location, this.page.url()).searchParams.get('code');
        if (!code) {
            throw new Error(`No authorization code found in redirect location: ${location}`);
        }
        return code;
    }

    public async doExchangeCodeForToken(
        code: string,
        client_id: string,
        client_secret: string,
        redirect_uri: string
    ): Promise<{ access_token: string; refresh_token: string }> {
        const response = await this.page.request.post('/api.php/token', {
            data: {
                grant_type: 'authorization_code',
                client_id: client_id,
                client_secret: client_secret,
                code: code,
                redirect_uri: redirect_uri,
            },
            headers: {
                'Content-Type': 'application/json',
            },
        });
        return response.json();
    }

    public async doFillTotpCode(token: string): Promise<void>
    {
        for (let i = 0; i < 6; i++) {
            await this.page.getByRole('textbox', { name: `2FA code digit ${i + 1} of 6` }).fill(token[i]);
        }
        await this.page.getByRole('button', { name: 'Verify' }).click();
    }
}
